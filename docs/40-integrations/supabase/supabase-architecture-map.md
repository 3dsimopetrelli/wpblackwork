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
