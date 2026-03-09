# Supabase Architecture Map

## 1) Purpose + Scope
This document is the deep technical architecture reference for the Supabase integration layer in Blackwork.
It describes current implementation reality for authentication/session bridging, onboarding flows, user mapping, and checkout provisioning couplings.

Scope boundaries:
- Supabase integration internals only.
- Provider-agnostic auth policy is documented in `../auth/auth-architecture-map.md`.
- No refactor guidance, no code change proposals.

## 2) Supabase Integration Modes

### Native mode (direct API interaction)
Runtime uses Blackwork handlers to call Supabase Auth endpoints directly.
Primary implementation hub:
- `includes/woocommerce-overrides/class-bw-supabase-auth.php`

Observed native endpoints/operations:
- Password login (`auth/v1/token?grant_type=password`)
- Token refresh (`auth/v1/token?grant_type=refresh_token`)
- Current user fetch (`auth/v1/user`)
- Invite send (`auth/v1/invite` or `auth/v1/admin/invite`, with fallback)
- User update (password/profile/email flows)

### OIDC mode (via OpenID Connect Generic Client broker)
Blackwork resolves OIDC broker details through helper functions:
- `bw_oidc_is_active()`
- `bw_oidc_get_auth_url()`
- `bw_oidc_get_redirect_uri()`
- `bw_oidc_get_provider_base_url()`

Broker settings source:
- option `openid_connect_generic_settings` (owned by OpenID Connect Generic Client plugin)

### Mode resolution logic
Supabase branch is controlled by:
- `bw_account_login_provider` = `supabase`

Mode-level controls:
- `bw_supabase_login_mode` (`native` | `oidc`, normalized)
- `bw_supabase_with_plugins` (OIDC plugin integration toggle)

Current implementation reality:
- Native API and bridge flows are always present.
- OIDC broker capability is exposed/configurable and used for redirect-based login paths.
- WordPress session is still established through plugin-side bridging/session logic.

### Hard dependencies per mode
Native mode hard dependencies:
- `bw_supabase_project_url`
- `bw_supabase_anon_key`
- nonce `bw-supabase-login` for AJAX operations

OIDC mode hard dependencies:
- OpenID Connect Generic Client plugin active/configured
- valid OIDC auth URL and redirect URI
- callback/session continuity back to WordPress account context

## 3) Configuration Model
Core config keys observed in runtime/admin:

- Project URL: `bw_supabase_project_url`
- Anon/Public key: `bw_supabase_anon_key`
- Service role key (server-only): `bw_supabase_service_role_key`
- Session storage mode: `bw_supabase_session_storage` (`cookie` | `usermeta`)
- JWT cookie base name: `bw_supabase_jwt_cookie_name`
- User linking toggle: `bw_supabase_enable_wp_user_linking`
- Auto-create WP user toggle: `bw_supabase_create_wp_users`
- Login mode: `bw_supabase_login_mode`
- OIDC integration toggle: `bw_supabase_with_plugins`
- Registration mode: `bw_supabase_registration_mode`
- Invite provisioning toggle: `bw_supabase_checkout_provision_enabled`
- Invite redirect URL: `bw_supabase_invite_redirect_url`
- Expired link redirect URL: `bw_supabase_expired_link_redirect_url`
- OAuth/magic/password capability toggles:
  - `bw_supabase_magic_link_enabled`
  - `bw_supabase_oauth_google_enabled`
  - `bw_supabase_oauth_facebook_enabled`
  - `bw_supabase_oauth_apple_enabled`
  - `bw_supabase_login_password_enabled`
- Debug flag: `bw_supabase_debug_log`

## 4) Runtime Auth Flows (Native Mode)

### Login (AJAX -> Supabase -> token -> WP mapping)
Primary handlers:
- `bw_mew_handle_supabase_login()`
- `bw_mew_handle_supabase_token_login()`

Flow model:
1. Client submits credentials (or token payload) to `admin-ajax.php`.
2. Server validates nonce (`bw-supabase-login`).
3. Server calls Supabase endpoint(s).
4. Supabase payload is validated; email/user resolution occurs.
5. WP user is linked/created (depending on toggles).
6. WP session is established (`wp_set_auth_cookie`).
7. Supabase tokens are persisted by configured storage strategy.
8. Response returns redirect/flags for account continuation.

### Invite / password setup
Invite triggers and resend flows are implemented server-side:
- `bw_mew_handle_supabase_checkout_invite()`
- `bw_mew_send_supabase_invite()`
- `bw_mew_handle_supabase_resend_invite()`

Password setup endpoints:
- `bw_mew_handle_supabase_create_password()`
- `bw_mew_handle_set_password_modal()`

Onboarding metadata (`bw_supabase_onboarded`) controls whether user remains in setup state.

### Magic Link (OTP) + Create Password (new user) flow
Lifecycle (native path):
1. User submits email and receives magic-link/OTP email from Supabase.
2. User lands with callback payload (`hash` or `code`), which is routed into callback/bridge context.
3. OTP verification step resolves token validity and produces session token material for bridge continuation.
4. Token bridge posts to `bw_supabase_token_login`; backend verifies Supabase user and establishes WP session authority.
5. Modal/password gating requires an active WP logged-in session before sensitive password-set actions are accepted.
6. Password creation gate (`bw_mew_handle_supabase_create_password()` or modal path) is mandatory for onboarding/new-user state.
7. Onboarding marker flips to onboarded (`bw_supabase_onboarded = 1`) when password setup is successfully completed.
8. Order claim attachment runs on mapped user context (`bw_mew_claim_guest_orders_for_user`), making prior guest assets/downloads attachable to the activated account.

### Reset password
Recovery/invite callbacks are routed into account callback flow (`bw_auth_callback`, `type=recovery|invite`), then handled by bridge + password update routines.
Reset URL behavior is configured via redirect options and callback discipline.

### Token refresh (where applicable)
Automatic refresh helper:
- `bw_mew_get_supabase_access_token_with_refresh()`

Behavior:
- If access token missing but refresh token exists, plugin calls Supabase refresh endpoint.
- On success, refreshed session is stored via `bw_mew_supabase_store_session()`.

### Error normalization
Handlers consistently return JSON error payloads with user-facing messages.
Observed normalization patterns:
- Configuration missing -> explicit `400` errors
- Supabase unreachable -> `500` path
- Invalid token/session verification -> `401`
- Invite resend throttle -> `429`

## 5) Runtime Auth Flows (OIDC Mode)

### Redirect -> Broker -> Callback
High-level sequence:
1. User enters auth flow with Supabase provider in OIDC mode.
2. Browser is redirected to OIDC broker auth URL.
3. Broker handles authorize/code exchange.
4. Browser returns to callback/account URL (`bw_auth_callback` pattern).

### Token handling ownership
- OIDC protocol exchange ownership is broker-side (OpenID Connect Generic Client plugin).
- Blackwork owns downstream account callback orchestration and WordPress session continuity logic.

### WP user mapping after broker auth
After callback/token bridge reaches WordPress context, existing Supabase/WP linking rules apply:
- find by email
- optionally create user
- set role/customer profile metadata
- update onboarding markers

### Redirect discipline
Callback paths are funneled toward account endpoints to avoid fragmented auth state.
Bridge scripts (`bw-supabase-bridge.js`, account scripts) coordinate hash/code callback handling and session confirmation before redirect.

Callback convergence hardening (R-AUTH-04):
- callback terminal fallback is explicit for unhandled/failed callback payloads to avoid loader dead-ends.
- refresh/re-entry behavior is repeat-safe and converges to a deterministic account terminal route.
- stale client callback markers (`bw_auth_in_progress`, handled callback/session flags) are actively cleaned to prevent loop resurrection from prior sessions.

## 6) Token & Session Model

### JWT storage strategy
Configured by `bw_supabase_session_storage`:
- `cookie`: httpOnly cookies
- `usermeta`: user_meta token persistence (with cookie fallback in retrieval routines)

### Cookie naming and scope
Cookie base key:
- `bw_supabase_jwt_cookie_name` (default `bw_supabase_session`)

Derived cookies:
- `<base>_access`
- `<base>_refresh`

Attributes used:
- `httponly=true`
- `samesite=Lax`
- `secure` tied to SSL context

### User meta storage structure
When usermeta storage is active, keys include:
- `bw_supabase_access_token`
- `bw_supabase_refresh_token`
- `bw_supabase_expires_at`

### Expiry handling
- Access token expiration from Supabase payload (`expires_in`).
- Refresh path attempts token renewal when access token is absent.

### Logout invalidation semantics
- WordPress logout clears Supabase session cookies via `bw_mew_clear_supabase_session_cookies()`.
- WP logout redirect handling is coordinated in account layer.

## 7) WP User Mapping Model

### Auto-create vs link-only
Controls:
- `bw_supabase_enable_wp_user_linking`
- `bw_supabase_create_wp_users`

Behavior:
- If linking enabled, plugin attempts find-by-email.
- If user missing and create enabled, plugin creates WP user and sets customer role when eligible.

### Metadata keys used
Observed mapping/onboarding keys:
- `bw_supabase_user_id`
- `bw_supabase_onboarded`
- `bw_supabase_invited`
- `bw_supabase_invite_sent_at`
- `bw_supabase_invite_resend_count`
- `bw_supabase_invite_error`
- `bw_supabase_onboarding_error`
- `bw_supabase_pending_email`

### Onboarding marker
Primary state marker:
- `bw_supabase_onboarded` (1 = onboarded, otherwise onboarding/pending)
- Writes are convergence-guarded in runtime to avoid branch-timing drift across token-login/modal/callback paths.
- Non-authorized marker downgrades are blocked; stale missing markers for authenticated ready users are reconciled under explicit safety conditions.

### Order ownership implications
After successful Supabase/WP mapping, guest orders/download permissions can be attached to the resolved WP user (`bw_mew_claim_guest_orders_for_user` and related helpers), which affects My Account order/download access.

## 7.5) Full Lifecycle Model (Guest Purchase -> Account Activation -> Downloads)

### A) Guest checkout with provisioning enabled
- Preconditions:
  - provider branch in Supabase context
  - `bw_supabase_checkout_provision_enabled = 1`
  - valid Supabase project URL + service role key for invite API calls
- On eligible order status hooks, invite workflow triggers from order lifecycle handlers.

### B) Dual-email expectation
- Email 1: order confirmation email (commerce confirmation path).
- Email 2: Supabase invite/activation email (account activation path).
- Architecture assumes both channels can arrive at different times without breaking activation continuity.

### C) Logged-out order-received gating behavior
- Order-received templates evaluate provider/provision flags.
- If auth/account activation is still required, CTA routes user to account activation/login gate instead of assuming direct account access.

### D) Invite click -> callback bridge -> onboarding gate
1. User clicks invite link and lands with invite/recovery callback payload.
2. Callback is routed to `bw_auth_callback` account path (bridge entrypoint).
3. Bridge exchanges/verifies session and creates WP session context.
4. If `bw_supabase_onboarded != 1`, onboarding gate remains active (set-password/modal/password required).

### E) Create password -> unlock account downloads
- Password completion flips onboarding state to onboarded.
- Guest order ownership/download permissions are attached to mapped WP user.
- My Account downloads/orders become accessible in normal session flow.

### F) Non-break invariants
- Invite confirmation links must preserve valid confirmation callback semantics and redirect targets.
- Flow must avoid redirect loops between callback/account endpoints.
- Invite/callback transitions must avoid visual auth-state flash before route stabilization.

## 7.6) Auth Callback & Anti-Flash Architecture Model

### A) `?bw_auth_callback=1` routing purpose
- This query flag is the canonical callback-routing contract for auth transitions (invite/recovery/token bridge).
- It forces the account template path into callback loader mode instead of normal My Account content rendering.

### B) Early `wp_head` guard
- An early frontend guard in runtime bootstrap (`wp_head`) detects callback/hash/code contexts and normalizes redirect targets to callback-safe entrypoints.
- Goal: prevent landing-page flash and inconsistent first route during invite/callback transitions.

### C) First-paint suppression
- Callback states activate preload suppression classes/logic (`auth_in_progress` orchestration) so UI does not paint standard account layout before bridge resolution.
- The callback loader acts as a controlled interim state while session bridge decisions complete.

### D) Bridge state machine (`auth_in_progress`)
Observed bridge phases:
1. detect callback payload/hash/code
2. mark auth transition in progress
3. execute token/code bridge to WP session endpoint(s)
4. poll/confirm WP session state
5. perform single redirect to resolved target
6. clear transitional state markers

### E) Logged-in stale callback auto-clean
- If user is already authenticated and callback params are stale, callback query parameters are cleaned automatically to avoid ghost callback states and re-bridging.

### F) Modal gating dependency on WP session
- Password/onboarding modal actions depend on valid WP session presence.
- Bridge must establish WP session before modal-based onboarding actions can complete safely.

### G) Non-break invariants
- No flash: standard My Account content must not appear before callback bridge resolves state.
- No loop: callback redirects must converge in one direction and stop after session confirmation.
- No ghost loader: callback loader must be cleared once state is resolved (authenticated target or explicit failure path).

## 8) Security Boundaries

### Service role key isolation
- `bw_supabase_service_role_key` is used server-side for invite/admin operations.
- Must not be exposed to browser runtime.

### Client-exposed vs server-only config
Client-localized auth config includes project URL + anon key.
Service role and sensitive admin credentials remain server-only.

### RLS expectations
Anon key flows assume correct Supabase project/RLS policy posture.
Security relies on backend endpoint discipline plus Supabase policy enforcement.

### Redirect sanitization
- Redirect values are normalized through `bw_mew_supabase_sanitize_redirect_url()`.
- Invite/expired-link redirects are validated/sanitized before usage.
- Email template invariant: Supabase email templates must preserve `{{ .ConfirmationURL }}` to keep callback/token bridge valid.
- Expired-link invariant: `otp_expired` outcomes must route to the configured expired-link redirect (`bw_supabase_expired_link_redirect_url` when set).
- Callback route invariant: callback contexts must not render standard My Account content before bridge resolution is completed.

### CSRF/nonce model
Supabase AJAX endpoints consistently gate requests with:
- `check_ajax_referer( 'bw-supabase-login', 'nonce' )`

### Password policy bifurcation invariant
- Onboarding password validation and strict profile-password validation are distinct policy lanes.
- Architecture requires both validators to keep their own contracts so onboarding completion and post-onboarding profile security checks do not conflict.

### Anti-token-leak invariant
- Tokens are never intended to be logged in clear form.
- Session token placement is constrained to secure cookies/usermeta and controlled bridge payload handling.

## 9) High-Risk Zones
1. `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- Core of Supabase API calls, token/session persistence, invite/onboarding logic.

2. Account bridge scripts
- `assets/js/bw-account-page.js`
- `assets/js/bw-supabase-bridge.js`
- High coupling between callback parsing, token bridge, and redirect timing.

3. Login template overrides
- `woocommerce/templates/myaccount/form-login.php`
- `woocommerce/templates/myaccount/my-account.php`
- Callback/flow routing correctness depends on these templates.

4. User creation/linking logic
- Wrong email mapping or onboarding flags can lock users out or mis-assign account state.

5. Checkout coupling points
- Invite provisioning (`bw_supabase_checkout_provision_enabled`) and order lifecycle hooks connect checkout completion to Supabase onboarding.

## 10) Failure Model

### Supabase API failure
Symptoms:
- login/token verification errors
- profile sync failures
- invite send errors

Degrade behavior:
- explicit error responses
- onboarding flow pauses; no silent success assumed

### OIDC broker failure
Symptoms:
- missing auth URL/callback configuration
- redirect loop or callback not resolving session

Degrade behavior:
- account callback remains unresolved; user re-entry required

### Token expiry
Symptoms:
- missing/expired access token
- protected updates fail until refresh/re-auth

Degrade behavior:
- refresh attempt via refresh token when available
- fallback to login when refresh unavailable/invalid

### Partial onboarding
Symptoms:
- `bw_supabase_onboarded` remains false
- user session exists but password/setup still required

Degrade behavior:
- set-password gating and onboarding notices remain active

### Provider misconfiguration fallback
If provider is set to Supabase but required config/dependencies are missing, runtime returns controlled errors and avoids pretending auth success.

## 11) Maintenance & Regression References
- [Regression Protocol](../../50-ops/regression-protocol.md)
- [Auth Architecture Map](../auth/auth-architecture-map.md)
- [Checkout Architecture Map](../../30-features/checkout/checkout-architecture-map.md)

## 12) Canonical Recovery Flow Inventory (Rollback-Stable Baseline)

### Flow 1 — Provider selection (WordPress vs Supabase)
- Business purpose: route authentication/provisioning behavior by active provider mode.
- User-visible trigger: user opens My Account/login or checkout-linked auth surfaces.
- Entry point: provider option read from runtime settings (`bw_account_login_provider`).
- Control handoff: template/bootstrap reads provider, then routes to WP-native or Supabase-aware logic.
- PHP owner files: `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- JS owner files: `assets/js/bw-account-page.js`, `assets/js/bw-my-account.js`
- Template owner files: `woocommerce/templates/myaccount/form-login.php`, `woocommerce/templates/myaccount/my-account.php`
- Key functions/classes: Supabase auth class provider checks and conditional hook wiring.
- Supabase endpoints: none when provider=`wordpress`; Supabase stack enabled when provider=`supabase`.
- Nonce requirements: inherited by downstream AJAX/auth handlers.
- Token handling responsibility: disabled/bypassed in WordPress mode, enabled in Supabase mode.
- WP session authority point: WordPress-only auth in WP mode; token bridge/session exchange in Supabase mode.
- State/meta markers: provider option gates onboarding/token/session behaviors.
- Redirect behavior: provider-dependent My Account/auth paths.
- Expected terminal state: deterministic provider branch with no cross-mode leakage.
- Likely failure modes: wrong provider value, mixed hooks, stale mode assumptions.
- User-visible symptoms: unexpected login UI/flow, missing Supabase onboarding, or wrong CTA behavior.
- First evidence to check: provider option value, boot logs, template branch selection.
- canonical owner: `docs/40-integrations/supabase/supabase-architecture-map.md`
- supporting docs: `docs/40-integrations/auth/auth-architecture-map.md`, `docs/30-features/my-account/my-account-complete-guide.md`
- cross-domain dependencies: WooCommerce account templates, plugin option storage.
- criticality classification: auth-critical.

### Flow 2 — Native Supabase login (email/password)
- Business purpose: authenticate via Supabase password grant and establish WordPress session.
- User-visible trigger: submit email/password form in Supabase login mode.
- Entry point: `admin-ajax.php` action for Supabase password login.
- Control handoff: JS submits AJAX -> PHP validates nonce -> Supabase `/auth/v1/token?grant_type=password` -> WP user resolve/create -> session set.
- PHP owner files: `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- JS owner files: `assets/js/bw-account-page.js`, `assets/js/bw-my-account.js`
- Template owner files: `woocommerce/templates/myaccount/form-login.php`
- Key functions/classes: login handler, user mapping helpers, token persistence helpers.
- Supabase endpoints: `POST /auth/v1/token?grant_type=password`, optional profile/user reads.
- Nonce requirements: required and enforced on AJAX login action.
- Token handling responsibility: plugin persists access/refresh token (cookie/usermeta strategy).
- WP session authority point: `wp_set_auth_cookie` after successful Supabase auth + user mapping.
- State/meta markers: user linkage/meta + onboarding marker behavior as applicable.
- Redirect behavior: success redirects to My Account clean state.
- Expected terminal state: authenticated WP session aligned with Supabase identity.
- Likely failure modes: nonce fail, Supabase 4xx/5xx, mapping conflict, token persistence error.
- User-visible symptoms: login fails, generic auth error, session not retained.
- First evidence to check: AJAX response payload, nonce status, Supabase response body, debug log auth traces.
- canonical owner: `docs/40-integrations/supabase/supabase-architecture-map.md`
- supporting docs: `docs/50-ops/runbooks/supabase-runbook.md`
- cross-domain dependencies: WP user APIs, WooCommerce account endpoint routing.
- criticality classification: auth-critical, session-critical.

### Flow 3 — Callback/token bridge login (invite, recovery, magic link)
- Business purpose: convert Supabase callback tokens into authoritative WP authenticated session.
- User-visible trigger: click invite/magic/recovery link from email.
- Entry point: callback marker `?bw_auth_callback=1` and hash/code payload processing.
- Control handoff: browser lands on callback route -> `assets/js/bw-supabase-bridge.js` extracts token/codes -> AJAX/bridge exchange -> PHP validates and sets session -> clean My Account destination.
- PHP owner files: `woocommerce/woocommerce-init.php`, `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- JS owner files: `assets/js/bw-supabase-bridge.js`
- Template owner files: `woocommerce/templates/myaccount/auth-callback.php`, `woocommerce/templates/myaccount/my-account.php`
- Key functions/classes: callback routing bootstrap, token-login handlers, session exchange helpers.
- Supabase endpoints: callback code exchange/token validation paths, user/session endpoints as needed.
- Nonce requirements: token-login policy uses controlled same-site callback allowances for callback completion paths.
- Token handling responsibility: bridge JS captures callback token data; PHP persists final tokens.
- WP session authority point: WP cookie set once callback token is validated/mapped.
- State/meta markers: callback/query markers and onboarding marker updates.
- Redirect behavior: callback URL cleaned and redirected to stable My Account state.
- Expected terminal state: no callback fragment left, user in deterministic session state.
- Likely failure modes: hash not parsed, callback marker mismatch, bridge script not loaded, nonce/policy rejection.
- User-visible symptoms: callback loop, stuck loading, landing on wrong account state.
- First evidence to check: browser network for bridge actions, callback params, JS console, debug log callback traces.
- canonical owner: `docs/40-integrations/supabase/supabase-architecture-map.md`
- supporting docs: `docs/40-integrations/supabase/supabase-create-password.md`, `docs/50-ops/runbooks/supabase-runbook.md`
- cross-domain dependencies: email template redirect URLs, My Account route availability.
- criticality classification: auth-critical, session-critical.

### Flow 4 — Post-checkout guest provisioning (automatic invite)
- Business purpose: provision guest buyers into Supabase onboarding without manual account creation.
- User-visible trigger: guest order transitions to eligible paid/lifecycle statuses.
- Entry point: WooCommerce order status hooks in Supabase auth class.
- Control handoff: Woo order status event -> eligibility checks -> invite send attempt -> meta/throttle update.
- PHP owner files: `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- JS owner files: none required for server-side invite dispatch.
- Template owner files: checkout/order-received templates consume resulting state.
- Key functions/classes: invite dispatcher, throttle guards, user creation/linkage helpers.
- Supabase endpoints: `POST /auth/v1/invite`
- Nonce requirements: not AJAX user-initiated; server-side trusted hook path.
- Token handling responsibility: none directly; invite mail initiates later callback/token flow.
- WP session authority point: not created here unless separate login flow occurs.
- State/meta markers: invite sent/attempt timestamps, throttle markers, onboarding-related user/order meta.
- Redirect behavior: none; affects downstream account CTA behavior.
- Expected terminal state: invite sent once per throttle policy, state recorded.
- Likely failure modes: missing service-role context, duplicate suppression, misclassified status eligibility.
- User-visible symptoms: no invite email after purchase, no onboarding progression.
- First evidence to check: debug log invite trace lines, order meta markers, status transition timing.
- canonical owner: `docs/40-integrations/supabase/supabase-architecture-map.md`
- supporting docs: `docs/30-features/checkout/complete-guide.md`, `docs/50-ops/runbooks/supabase-runbook.md`
- cross-domain dependencies: WooCommerce order lifecycle, Supabase email deliverability.
- criticality classification: checkout-coupled, onboarding-critical.

### Flow 5 — Order received guest + Supabase CTA behavior
- Business purpose: show post-checkout guidance matching guest onboarding state under Supabase provider.
- User-visible trigger: visit `/checkout/order-received/...` after purchase.
- Entry point: checkout order-received template render.
- Control handoff: template inspects login/provider/order state and selects branch.
- PHP owner files: `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- JS owner files: optional UI scripts only.
- Template owner files: `woocommerce/templates/checkout/order-received.php`
- Key functions/classes: branch checks for guest/logged-in/onboarded and provider mode.
- Supabase endpoints: none directly during render.
- Nonce requirements: none for passive render branch.
- Token handling responsibility: none directly in this render layer.
- WP session authority point: branch reads current WP session state.
- State/meta markers: order/user onboarding markers and provider setting.
- Redirect behavior: branch-specific CTA behavior; reminder branch is intentionally static.
- Expected terminal state: guest sees setup reminder; onboarded/logged user sees account-oriented confirmation.
- Likely failure modes: wrong branch conditions, stale user marker, provider mismatch.
- User-visible symptoms: wrong CTA text/action, session-expired message shown incorrectly, confusion after checkout.
- First evidence to check: template trace logs, branch trace markers, provider option, current user/order IDs.
- canonical owner: `docs/40-integrations/supabase/supabase-create-password.md`
- supporting docs: `docs/30-features/checkout/complete-guide.md`
- cross-domain dependencies: checkout template override load order.
- criticality classification: checkout-coupled, onboarding-critical.

### Flow 6 — Create password / onboarding gate
- Business purpose: force password setup completion before full account access when onboarding pending.
- User-visible trigger: post-callback authenticated state with onboarding marker incomplete.
- Entry point: My Account gate checks (`bw_supabase_onboarded != 1`) and set-password surfaces.
- Control handoff: account page render -> gate condition -> set-password modal/page handlers -> success updates marker.
- PHP owner files: `includes/woocommerce-overrides/class-bw-my-account.php`, `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- JS owner files: `assets/js/bw-password-modal.js`, `assets/js/bw-my-account.js`
- Template owner files: `woocommerce/templates/myaccount/set-password.php`, `woocommerce/templates/myaccount/my-account.php`
- Key functions/classes: onboarding gate checks, create/set password handlers, marker convergence logic.
- Supabase endpoints: `POST /auth/v1/user` (password update with bearer token/session).
- Nonce requirements: required on create/set password AJAX handlers.
- Token handling responsibility: valid Supabase access context required to update password.
- WP session authority point: WP session may exist before marker is set; gate controls access completion.
- State/meta markers: `bw_supabase_onboarded` and related onboarding flags.
- Redirect behavior: success returns to clean My Account logged-in state.
- Expected terminal state: password set successfully and onboarding marker persisted as complete.
- Likely failure modes: missing Supabase session, validator mismatch, duplicate-password edge, AJAX failure.
- User-visible symptoms: "Unable to update password", repeated gate, can’t reach downloads/orders.
- First evidence to check: password handler logs, network response body, onboarding marker value, token presence.
- canonical owner: `docs/40-integrations/supabase/supabase-create-password.md`
- supporting docs: `docs/30-features/my-account/my-account-complete-guide.md`, `docs/50-ops/runbooks/supabase-runbook.md`
- cross-domain dependencies: callback bridge (Flow 3), order claim visibility (Flow 7).
- criticality classification: onboarding-critical, auth-critical.

### Flow 7 — Guest order claim to authenticated account
- Business purpose: bind guest orders/download permissions to authenticated mapped account.
- User-visible trigger: user completes onboarding/login and opens My Account orders/downloads.
- Entry point: mapping/claim logic on auth completion and order lookup surfaces.
- Control handoff: auth success -> WP user resolution/link -> guest order reassignment/association -> account views render purchases.
- PHP owner files: `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- JS owner files: none required for core claim.
- Template owner files: `woocommerce/templates/myaccount/my-account.php`, order/download templates.
- Key functions/classes: user/email mapping, order claim helpers.
- Supabase endpoints: indirectly via auth identity source.
- Nonce requirements: dependent on initiating action (login/callback handlers).
- Token handling responsibility: auth identity continuity across claim operations.
- WP session authority point: claim is meaningful only after valid WP authenticated user context.
- State/meta markers: claimed order ownership/user_id and linked meta.
- Redirect behavior: user remains in My Account with claimed resources visible.
- Expected terminal state: previously guest orders and downloads visible under authenticated account.
- Likely failure modes: email mismatch, claim not triggered, race with onboarding marker.
- User-visible symptoms: "No order has been made yet" despite paid order.
- First evidence to check: order user_id/meta, mapping logs, auth identity email, claim helper execution traces.
- canonical owner: `docs/40-integrations/supabase/supabase-architecture-map.md`
- supporting docs: `docs/30-features/my-account/my-account-complete-guide.md`, `docs/50-ops/runbooks/supabase-runbook.md`
- cross-domain dependencies: WooCommerce order ownership model.
- criticality classification: onboarding-critical, checkout-coupled.

### Flow 8 — Resend invite email
- Business purpose: recover onboarding when initial invite expired/lost.
- User-visible trigger: user lands in onboarding reminder screen and requests resend.
- Entry point: resend invite form/action in login template.
- Control handoff: JS/UI submit -> PHP resend handler -> Supabase invite endpoint or active-account short-circuit.
- PHP owner files: `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- JS owner files: `assets/js/bw-account-page.js`
- Template owner files: `woocommerce/templates/myaccount/form-login.php`
- Key functions/classes: resend handler, active-account detection, response message mapper.
- Supabase endpoints: `POST /auth/v1/invite`.
- Nonce requirements: required for resend AJAX/form submission.
- Token handling responsibility: no new login session directly; invite email restarts callback/token path.
- WP session authority point: not created by resend itself.
- State/meta markers: invite resend attempt metadata and throttle guards.
- Redirect behavior: generally same screen with success/error status.
- Expected terminal state: new invite sent OR explicit "already active" guidance to normal login.
- Likely failure modes: throttling, Supabase reject, invalid email context.
- User-visible symptoms: resend appears to do nothing, repeated error, confusing status copy.
- First evidence to check: resend action response, invite logs, throttle markers.
- canonical owner: `docs/40-integrations/supabase/supabase-create-password.md`
- supporting docs: `docs/50-ops/runbooks/supabase-runbook.md`
- cross-domain dependencies: email template health, provider mode.
- criticality classification: onboarding-critical.

### Flow 9 — Expired link handling (`otp_expired`)
- Business purpose: deterministic recovery path when invite/magic link token is invalid/expired.
- User-visible trigger: user clicks old/used/expired email link.
- Entry point: callback error parameters include `error_code=otp_expired`.
- Control handoff: callback parser detects expired token -> plugin redirects to configured expired-link URL.
- PHP owner files: `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- JS owner files: `assets/js/bw-supabase-bridge.js` (error-state parsing where applicable)
- Template owner files: login/recovery template surfaces at destination URL.
- Key functions/classes: expired-link detector, redirect resolver.
- Supabase endpoints: callback verification path yields expired status.
- Nonce requirements: not primary in read/redirect step.
- Token handling responsibility: rejected/expired token must not be promoted.
- WP session authority point: no WP login completion when token expired.
- State/meta markers: error code markers in callback/query state.
- Redirect behavior: to configured expired-link page with resend entry.
- Expected terminal state: user lands on recovery UI and can resend invite.
- Likely failure modes: redirect URL missing/not configured, parser miss, stale fragment handling.
- User-visible symptoms: lands on wrong page (home or generic my-account), no clear recovery path.
- First evidence to check: final URL, callback params, redirect setting, bridge logs.
- canonical owner: `docs/40-integrations/supabase/supabase-create-password.md`
- supporting docs: `docs/50-ops/runbooks/supabase-runbook.md`
- cross-domain dependencies: Supabase redirect allow-list and email template links.
- criticality classification: onboarding-critical, callback-critical.

### Flow 10 — Refresh token and session persistence
- Business purpose: maintain authenticated continuity when access token expires but refresh token remains valid.
- User-visible trigger: authenticated session continues across page loads/expiry windows.
- Entry point: server-side token check detects missing/expired access token.
- Control handoff: PHP checks persistence store -> if refresh token present attempts refresh -> updates stored tokens -> continues request.
- PHP owner files: `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- JS owner files: bridge/account scripts consume resulting state.
- Template owner files: My Account templates render based on refreshed session state.
- Key functions/classes: token load/save helpers, refresh request helper, fallback strategy.
- Supabase endpoints: token refresh endpoint (`/auth/v1/token?grant_type=refresh_token`).
- Nonce requirements: server-to-server refresh path; nonce not primary control.
- Token handling responsibility: plugin owns dual-mode session persistence (cookie/usermeta) with fallback.
- WP session authority point: WP session remains authoritative once user is authenticated; token refresh supports continued integration calls.
- State/meta markers: persisted access/refresh token stores and expiry-related metadata.
- Redirect behavior: usually none; silent continuity unless refresh fails.
- Expected terminal state: refreshed token set and uninterrupted account experience.
- Likely failure modes: refresh token missing/invalid, persistence write/read mismatch between cookie/usermeta stores.
- User-visible symptoms: sudden logout-like state, create-password/session-missing errors, repeated callback prompts.
- First evidence to check: token store presence (cookie/usermeta), refresh call responses, fallback branch logs.
- canonical owner: `docs/40-integrations/supabase/supabase-architecture-map.md`
- supporting docs: `docs/50-ops/runbooks/supabase-runbook.md`
- cross-domain dependencies: browser cookie behavior, WP usermeta availability.
- criticality classification: session-critical, auth-critical.

### Flow 11 — Logout and session cleanup
- Business purpose: terminate WP auth and invalidate plugin-side runtime session context.
- User-visible trigger: user clicks logout from My Account.
- Entry point: WordPress logout flow + Supabase integration cleanup hooks.
- Control handoff: WP logout action -> plugin cleanup routines expire Supabase-related cookies/session artifacts.
- PHP owner files: `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- JS owner files: minimal; browser transitions to logged-out view.
- Template owner files: `woocommerce/templates/myaccount/my-account.php`, `woocommerce/templates/myaccount/form-login.php`
- Key functions/classes: logout cleanup hooks and cookie expiration helpers.
- Supabase endpoints: none required for local logout cleanup path.
- Nonce requirements: standard WP logout protections.
- Token handling responsibility: cookies/session artifacts expired in logout path; not all usermeta token records are cleared in the same path.
- WP session authority point: WP logout finalizes unauthenticated state.
- State/meta markers: cookie/session cleanup markers; usermeta may persist for later controlled use.
- Redirect behavior: standard post-logout My Account/login view.
- Expected terminal state: user is logged out in WordPress and active session cookies removed.
- Likely failure modes: stale callback marker URL, partial cleanup perception, redirect to callback loading screen.
- User-visible symptoms: seeing callback/loading view after logout refresh, confusion about session state.
- First evidence to check: current URL query params (`bw_auth_callback`), logout hook execution, cookie state.
- canonical owner: `docs/40-integrations/supabase/supabase-architecture-map.md`
- supporting docs: `docs/50-ops/runbooks/supabase-runbook.md`
- cross-domain dependencies: WP core logout routing, client cache/state.
- criticality classification: session-critical.

### Runtime clarifications that must remain explicit
- Invite provisioning hooks are broader than just `processing`/`completed`; documentation and triage must not assume only two statuses.
- Token-login nonce policy includes controlled same-site callback allowances; this is intentional and part of callback hardening.
- Two different password validators exist and are intentionally split by flow scope.
- Order-received onboarding reminder branch contains a static/non-clickable onboarding reminder CTA by design.
- Logout cleanup expires cookies but does not clear all token usermeta in the same path.
- Session persistence uses dual-mode session persistence (cookie/usermeta) with fallback.

## 13) Protected Runtime Surfaces (Governance Alert Matrix)

> ⚠️ Supabase Protected Surface  
> This file participates in the canonical Supabase flow architecture.  
> Any modification requires:  
> - Supabase Flow Risk Alert  
> - mandatory regression smoke verification  
> - rollback comparison against the rollback-stable baseline  
> Changes on protected surfaces must never be merged without flow validation.

| Surface | Why sensitive | Canonical flows at risk | Severity | Coupling class | Likely user-visible symptoms | First smoke tests after change | Rollback comparison mandatory |
|---|---|---|---|---|---|---|---|
| `includes/woocommerce-overrides/class-bw-supabase-auth.php` | Core auth/provisioning/token/session authority and invite dispatch | 1,2,3,4,6,7,8,9,10,11 | Critical | auth/session/callback/onboarding/claim/checkout | Login fails, callback loops, invite missing, orders not claimed, session drops | S1 guest purchase->invite, S2 callback bridge login, S3 create password complete, S4 claimed orders visible, S5 logout cleanup | Yes |
| `includes/woocommerce-overrides/class-bw-my-account.php` | Onboarding gate and My Account access gating | 6,7 | Critical | onboarding/session/auth | User enters account without password setup or gets stuck in gate | S3 create password completion + gate unlock, S4 orders/downloads post-onboarding | Yes |
| `woocommerce/woocommerce-init.php` | Callback bootstrap wiring and flow routing | 3,6,9,11 | Critical | callback/session/auth | Auth callback not resolving, loader loops, wrong landing state | S2 callback bridge end-to-end, S6 expired link route, S7 logout then clean login page | Yes |
| `assets/js/bw-supabase-bridge.js` | Client callback parser and JS->PHP bridge handoff | 3,6,9,10 | Critical | callback/session/onboarding | Callback stuck, missing session handoff, expired link mishandled | S2 callback bridge with invite link, S6 expired link handling, S8 refresh/re-entry continuity | Yes |
| `assets/js/bw-account-page.js` | Login/onboarding form actions (including resend) | 2,6,8 | High | auth/onboarding | Submit actions fail, resend unusable, messaging mismatch | S9 native login submit, S10 resend invite path | Yes |
| `assets/js/bw-my-account.js` | My Account state transitions and onboarding UI behavior | 3,6,10,11 | High | session/onboarding/callback | Incorrect state rendering, repeated gates, stale callback visuals | S3 create-password modal/page behavior, S8 refresh continuity, S7 logout path | Yes |
| `assets/js/bw-password-modal.js` | Password setup UX validation and submit orchestration | 6 | High | onboarding/auth | Button/state mismatch, false pass/fail, password submit errors | S3 onboarding password creation with valid/invalid samples | Yes |
| `woocommerce/templates/myaccount/form-login.php` | Entry UI for login, resend invite, provider-specific messaging | 1,2,8,9 | High | auth/onboarding/callback | Wrong login method shown, resend flow confusion, recovery blocked | S9 native login, S10 resend invite, S6 expired-link recovery page | Yes |
| `woocommerce/templates/myaccount/my-account.php` | Account shell where callback/onboarding gating appears | 1,3,6,7,11 | High | session/onboarding/auth | Wrong account state, premature access, post-logout anomalies | S2 callback to My Account, S3 gate->unlock, S7 logout and re-open My Account | Yes |
| `woocommerce/templates/myaccount/auth-callback.php` | Dedicated callback view and control transfer point | 3,9 | Critical | callback/session | Stuck callback screen, no session bridge completion | S2 callback flow from invite/magic link, S6 expired callback behavior | Yes |
| `woocommerce/templates/myaccount/set-password.php` | Password setup screen for onboarding completion | 6 | High | onboarding/auth | Cannot complete password, conflicting validation signals | S3 set-password completion and post-success redirect | Yes |
| `woocommerce/templates/checkout/order-received.php` | Post-checkout branch logic and onboarding reminder behavior | 4,5,7 | Critical | checkout/onboarding/claim | Wrong thank-you CTA, wrong guest guidance, purchase visibility confusion | S1 guest purchase order-received branch, S4 order claim visibility after setup | Yes |

### Governance enforcement
- Any PR touching one or more surfaces above must raise a **Supabase Flow Risk Alert** in task notes.
- Minimum regression gate: run smoke set S1-S4 for Critical surfaces and targeted smoke for High/Medium surfaces.
- Comparator requirement: diff behavior against the Rollback-Stable Baseline documented in Section 12 before merge approval.
