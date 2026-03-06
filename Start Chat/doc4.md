# Start Chat — Documentation Pack 4

Generated from repository docs snapshot (excluding docs/tasks).


---

## Source: `docs/40-integrations/supabase/audits/2026-02-13-supabase-create-password-audit.md`

# Supabase Create Password Audit (2026-02-13)

## Scope
- Reviewed: `SUPABASE_CREATE_PASSWORD.md`
- Checked linked files:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-supabase-bridge.js`
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/woocommerce-init.php`
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/woocommerce-overrides/class-bw-supabase-auth.php`
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/woocommerce-overrides/class-bw-my-account.php`
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-password-modal.js`
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/css/bw-password-modal.css`
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/myaccount/form-login.php`

## Result
- Core flow is wired correctly (bridge + AJAX endpoints + modal rendering conditions).
- All linked files exist.
- One functional inconsistency found in password policy validation.

## Finding
1. UI password rules and backend requirements are inconsistent.
   - UI accepts: length + uppercase + (number OR special char):
     - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-account-page.js:381`
     - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-password-modal.js:116`
     - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/myaccount/form-login.php:493`
     - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/woocommerce-overrides/class-bw-my-account.php:506`
   - Backend `bw_supabase_create_password` requires: length + lowercase + uppercase + number + special char:
     - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/woocommerce-overrides/class-bw-supabase-auth.php:895`
   - Impact: valid UI passwords can be rejected server-side during OTP signup create-password flow.

2. Modal endpoint uses weaker backend policy than create-password endpoint.
   - `bw_set_password_modal` checks only length >= 8:
     - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/woocommerce-overrides/class-bw-supabase-auth.php:1498`
     - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/woocommerce-overrides/class-bw-supabase-auth.php:1530`
   - Impact: different behavior between onboarding paths.

## Notes
- Runtime lint/tests could not be executed in this environment because `php` and `node` binaries are not available in PATH.

---

## Addendum (2026-02-18) - Post-Audit Stabilization

After the initial audit, the checkout -> invite -> create-password flow was hardened to remove My Account transition glitches.

### Implemented hardening
- Early callback/preload guard in head:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/woocommerce-init.php`
- Bridge transition-state management (`bw_auth_in_progress`):
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-supabase-bridge.js`
- Callback rendering guards in account templates:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/myaccount/my-account.php`
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/myaccount/form-login.php`
- Logged-in stale callback URL cleanup:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/woocommerce-overrides/class-bw-my-account.php`

### Resolved regressions
1. Guest flow flash of logged-out My Account before popup.
2. Logged-in refresh showing `Completing sign-in` on stale `?bw_auth_callback=1`.

### Current canonical reference
- Use:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/SUPABASE_CREATE_PASSWORD.md`
- This file now includes:
  - full target behavior,
  - stable baseline rules,
  - mandatory regression checklist,
  - debug-first smoke matrix.


---

## Source: `docs/40-integrations/supabase/audits/README.md`

# Supabase Audits

## Files
- [2026-02-13-supabase-create-password-audit.md](2026-02-13-supabase-create-password-audit.md): focused audit report for create-password flow.


---

## Source: `docs/40-integrations/supabase/supabase-architecture-map.md`

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


---

## Source: `docs/40-integrations/supabase/supabase-create-password.md`

# Supabase Create Password - Technical Recovery Notes

## Scope
This document tracks the current "Set Password" flow for `Login Provider = Supabase` on WooCommerce My Account, including where logic lives and how to recover quickly if the flow breaks.

## Target Behavior
- Provider `wordpress`:
  - No Supabase checks.
  - No password gating modal.
  - Default WordPress/WooCommerce behavior.
- Provider `supabase`:
  - User reaches the site from Supabase confirmation/invite link.
  - Supabase token is bridged to a WordPress session.
  - On `/my-account/`, if `bw_supabase_onboarded != 1`, a blocking modal appears.
  - User sets password in Supabase, then modal closes and account becomes usable.

## Important Concept
The modal appears only for logged-in WP users.  
If the user is not logged in, modal cannot open.

## Main Files
- Bridge script:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-supabase-bridge.js`
- Bridge enqueue/localize:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/woocommerce-init.php`
- Supabase token login + modal AJAX endpoints:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/woocommerce-overrides/class-bw-supabase-auth.php`
- Modal HTML + enqueue:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/woocommerce-overrides/class-bw-my-account.php`
- Modal JS:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-password-modal.js`
- Modal CSS:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/css/bw-password-modal.css`
- Supabase login template + invite error rendering:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/myaccount/form-login.php`

## Current Endpoints
- `bw_supabase_token_login`:
  - Uses Supabase `access_token` to fetch `/auth/v1/user`.
  - Creates/authenticates WP session and sets onboarding flags.
- `bw_get_password_status`:
  - Returns `{ enabled, needs_password }` for modal gating.
- `bw_set_password_modal`:
  - Updates password in Supabase and marks onboarding complete.
- `bw_supabase_create_password`:
  - Create-password endpoint used in signup OTP flow.

## Known Failure Modes
1. Invite link expired (`otp_expired`):
   - User cannot be authenticated from that link.
   - User is redirected to `Supabase expired link redirect URL` (default: `/link-expired/`).
   - Must request/generate a fresh invite link.

2. User lands on site but remains logged out:
   - Bridge not executed, bridge failed, or invite token invalid/expired.
   - Check `admin-ajax.php` call for `action=bw_supabase_token_login`.

3. Modal not visible even if provider is Supabase:
   - User is not logged in.
   - Or user meta `bw_supabase_onboarded` is already `1`.

## Supabase Email Template Rules (Critical)
- Use `{{ .ConfirmationURL }}` as CTA href.
- Do not replace CTA with a static `/my-account/` URL.
- Supabase allow-list must include:
  - `https://blackwork.pro/my-account/`
  - `https://blackwork.pro/my-account/set-password/` (compatibility)
  - `https://blackwork.pro/link-expired/` (expired-link fallback page)

## New Checkout Setting (Supabase Provider Tab)
- `Supabase expired link redirect URL`:
  - Default: `https://blackwork.pro/link-expired/`
  - Used when callback hash contains `error_code=otp_expired`.

## Quick Test Checklist
1. Set `Login Provider = Supabase`.
2. Create a fresh invite (do not reuse expired link).
3. Open invite link immediately.
4. Confirm user is logged in on `/my-account/`.
5. Confirm modal appears if `bw_supabase_onboarded = 0`.
6. Submit password and confirm modal closes.

## Fast Debug Checklist
1. Browser URL after click:
   - Check for `#error_code=otp_expired`.
2. Network:
   - `POST admin-ajax.php` with `action=bw_supabase_token_login`.
   - Verify HTTP status and JSON payload.
3. WP user meta:
   - `bw_supabase_onboarded`
   - `bw_supabase_invited`
   - `bw_supabase_user_id`
4. Provider setting:
   - `bw_account_login_provider` must be `supabase`.

## Rollback Strategy
If a regression appears, revert only affected files (keep small blast radius):
- Bridge layer:
  - `assets/js/bw-supabase-bridge.js`
  - `woocommerce/woocommerce-init.php`
- Modal layer:
  - `includes/woocommerce-overrides/class-bw-my-account.php`
  - `assets/js/bw-password-modal.js`
  - `assets/css/bw-password-modal.css`
- Auth/AJAX layer:
  - `includes/woocommerce-overrides/class-bw-supabase-auth.php`

Then retest with the "Quick Test Checklist" above.

---

## Order-Received Flow (Guest + Supabase) — Current Stable Behavior

Date of latest validated behavior: **February 16, 2026**.

### Goal
After a guest checkout (Supabase provider enabled), the user must:
1. Receive order confirmation email.
2. Receive Supabase invite/account-setup email.
3. Click invite email CTA.
4. Land in My Account flow and set password.
5. Access downloads/order history securely.

### Current UX on `/checkout/order-received/...`
- Custom hero and cards are rendered by:
  - `woocommerce/templates/checkout/order-received.php`
- Main CTA behavior:
  - If user is **not logged in** and provider/provisioning uses Supabase:
    - CTA is static reminder text (no account redirect).
    - Label: `Check your email to finish account setup`
  - Otherwise:
    - CTA links to My Account.
- "What happens next?" explains:
  - order confirmation email,
  - account setup email,
  - account access after password setup.
- Checkout logo is reused on order-received through:
  - `woocommerce/woocommerce-init.php` (`bw_mew_render_order_received_logo_header`)
  - `assets/css/bw-order-confirmation.css`

### Files that define this behavior
- Template + copy + CTA logic:
  - `woocommerce/templates/checkout/order-received.php`
- Styling/layout/responsive:
  - `assets/css/bw-order-confirmation.css`
- Logo header renderer + checkout settings reuse:
  - `woocommerce/woocommerce-init.php`
- Supabase invite/send + resend message copy:
  - `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- My Account login template (post-checkout notices/resend block):
  - `woocommerce/templates/myaccount/form-login.php`
- My Account page styling:
  - `assets/css/bw-account-page.css`

---

## Non-Break Rules (Must Always Hold)

1. Do not remove `{{ .ConfirmationURL }}` from Supabase email template CTA.
2. Do not bypass token bridge when provider is Supabase.
3. Do not redirect guest order-received users directly into broken login loops.
4. Do not change order-received CTA semantics without updating both:
   - CTA logic in template,
   - explanatory text in "What happens next?".
5. Any CSS/layout change must be verified on desktop + mobile (especially logo/header overlap).

---

## Mandatory Regression Checklist (Run After Any Related Change)

### A. Guest checkout with Supabase enabled
1. Place new guest order with a fresh email.
2. Confirm order status reaches `processing` or `completed`.
3. Confirm debug log contains invite trace and `status 200` send to Supabase.
4. Open order-received page:
   - custom hero visible,
   - CTA text is `Check your email to finish account setup`,
   - order summary + billing box visible.

### B. Email delivery + invite link
1. Confirm two emails expected by user:
   - order confirmation,
   - account setup/invite.
2. Click invite CTA quickly.
3. Confirm My Account flow starts and password setup is available.

### C. Expired link behavior
1. Reuse old/expired invite link.
2. Confirm fallback to expired-link flow/page (no broken loop).
3. From fallback, request new invite and complete onboarding.

### D. Existing-user resend behavior
1. From post-checkout My Account notice, trigger resend for an existing account.
2. Confirm message:
   - `Your account is already active. Use Magic Link, code, or password to sign in.`
3. Confirm sign-in methods still work (Magic Link/code/password).

### E. Visual stability
1. Verify logo placement on order-received (desktop + mobile).
2. Verify hero typography/button wrapping (no overlap on narrow screens).
3. Verify boxes stack/order as expected on mobile.

---

## Quick Incident Triage (If It Breaks Again)

1. Check `debug.log` for:
   - `Supabase invite trace`,
   - `Supabase invite sent ... status 200`,
   - `BW template trace` for `checkout/order-received.php`.
2. If invite sent is 200 but email missing:
   - inspect Supabase Auth email provider health/settings and resend from dashboard.
3. If order-received layout not updated:
   - confirm deployed plugin version and clear all caches (server/CDN/plugin/browser).
4. If onboarding stuck:
   - verify bridge request `action=bw_supabase_token_login`,
   - inspect `bw_supabase_onboarded` user meta and session state.

---

## Latest Implemented Updates (2026-02-16 -> 2026-02-18)

### 1) Invite callback hardening + loader (anti-flash architecture)
- Added a dedicated callback route on My Account:
  - `?bw_auth_callback=1`
- Purpose:
  - consume Supabase hash/token first,
  - then redirect to clean My Account state,
  - reduce visible transitions before password setup.
- Files:
  - `woocommerce/templates/myaccount/auth-callback.php`
  - `woocommerce/templates/myaccount/my-account.php`
  - `assets/js/bw-supabase-bridge.js`
  - `assets/css/bw-account-page.css`
  - `woocommerce/woocommerce-init.php`

### 2) Order received split UX (guest/supabase vs normal)
- Guest + Supabase path shows dedicated onboarding-oriented CTA/copy.
- Logged-in/normal path uses account-oriented order confirmation layout.
- File:
  - `woocommerce/templates/checkout/order-received.php`
- Style:
  - `assets/css/bw-order-confirmation.css`

### 3) Post-checkout resend block redesigned in My Account
- New post-checkout section:
  - "Activate your account"
  - "Activation email sent"
  - resend email input + CTA
  - "Change email" control
- Current resend notice copy:
  - `Your account is already active. Use Magic Link, code, or password to sign in.`
- File:
  - `woocommerce/templates/myaccount/form-login.php`
- Style/behavior:
  - `assets/css/bw-account-page.css`
  - `assets/js/bw-my-account.js`

### 4) "Change email" control normalized
- Removed button-like black background behavior.
- Kept link-style behavior:
  - normal text,
  - underline only on hover/focus.
- File:
  - `assets/css/bw-account-page.css`

### 5) Resend/onboarding card and typography alignment
- Title/spacing + activation card typography aligned to current design:
  - intro title margin top 20px,
  - activation title sizing refined,
  - classic login container width maintained.
- File:
  - `assets/css/bw-account-page.css`

### 6) Floating labels applied to login/auth fields (checkout-consistent pattern)
- Reused existing BW floating-label architecture (`bw-field-wrapper`, `bw-floating-label`, `has-value`) without importing full checkout stylesheet.
- Applied to login-related email/password inputs and resend email input.
- Files:
  - `assets/js/bw-my-account.js`
  - `assets/css/bw-account-page.css`

---

## Stabilization Status (Updated 2026-02-18)

### Issue fixed: Logged-out My Account flash before Create Password popup
- Previous symptom:
  - after clicking Supabase invite link, users could briefly see logged-out My Account UI before popup/create-password state.
- Root cause:
  - callback transition relied on late JS timing in some paths.
  - `?bw_auth_callback=1` could remain in URL and be interpreted outside intended guest transition.
- Implemented fix set:
  1. **Early head-level preloader + redirect guard** (`wp_head`, priority 1)
     - file: `woocommerce/woocommerce-init.php`
     - adds ultra-early callback detection, auth-in-progress state, first-paint suppression on account page during transition.
  2. **Bridge state hardening**
     - file: `assets/js/bw-supabase-bridge.js`
     - stores/clears `bw_auth_in_progress` deterministically and cleans preload class when bridge completes.
  3. **Template guard for guest transition**
     - files:
       - `woocommerce/templates/myaccount/my-account.php`
       - `woocommerce/templates/myaccount/form-login.php`
     - auth callback template rendered only in proper transition context.
  4. **Logged-in stale callback cleanup**
     - file: `includes/woocommerce-overrides/class-bw-my-account.php`
     - if logged-in user lands on `/my-account/?bw_auth_callback=1`, redirects to clean `/my-account/`.

### Regression fixed: Loader shown to already logged-in users
- Symptom:
  - logged-in refresh on `/my-account/?bw_auth_callback=1` showed "Completing sign-in".
- Resolution:
  - callback/preload logic now explicitly bypassed when `is_user_logged_in() = true`.
  - stale callback query is auto-cleaned.

### Current expected behavior (must hold)
1. **Guest invite click (Supabase)**:
   - no visible logged-out form flash,
   - callback/loader path runs,
   - popup/create-password flow opens.
2. **Logged-in user refresh**:
   - never sees callback loader,
   - stays on normal My Account dashboard.
3. **Expired invite link**:
   - redirects to configured expired-link page (or set-password if already logged-in path applies).
4. **OTP create-password (new user)**:
   - validation must follow onboarding UI rules exactly:
     - at least 8 characters,
     - at least 1 uppercase letter,
     - at least 1 number **or** special character.

---

## Stable Baseline Rules (Do Not Break)

1. Supabase email CTA must keep `{{ .ConfirmationURL }}`.
2. For Supabase provider, order flow must always keep post-checkout onboarding path active.
3. Resend flow must remain available from My Account post-checkout state.
4. Any change to onboarding/login UI must be tested on desktop + mobile.
5. Any change touching bridge/callback must be tested with:
   - fresh invite link,
   - expired invite link,
   - already-activated account,
   - logged-in refresh on clean `/my-account/` and stale `/my-account/?bw_auth_callback=1`.

---

## Debug-First Smoke Matrix (Use After Any Auth/Checkout Change)

### Flow A — New guest purchase (primary flow)
1. Place order as guest with new email.
2. Confirm order-received page renders onboarding version.
3. Confirm Supabase invite log contains status 200.
4. Click invite link.
5. Confirm no logged-out page flash before create-password UI.

### Flow B — Logged-in safety
1. Login with existing active account.
2. Open `/my-account/` and refresh.
3. Open `/my-account/?bw_auth_callback=1` manually.
4. Confirm auto-clean redirect to `/my-account/` and no loader persistence.

### Flow C — Expired invite
1. Re-open old invite link.
2. Confirm expired behavior path is deterministic and non-looping.

### Log lines to verify quickly (`wp-content/debug.log`)
- `Supabase invite trace ... entered trigger`
- `Supabase invite sent ... status 200`
- `BW template trace: template_name=checkout/order-received.php source=plugin`
- `BW order-received branch: custom-order-confirmed`

---

## Password Rules Alignment (Updated 2026-02-18)

### Issue observed
- In OTP/new-user create-password flow, UI showed all rules green and enabled submit, but backend returned:
  - `Password does not meet the requirements.`
- Example reported:
  - `CiaoSimone1` should pass onboarding UI (8 + uppercase + number) but was rejected.

### Root cause
- Endpoint `bw_mew_handle_supabase_create_password()` was still using strict validator:
  - `bw_mew_supabase_password_meets_requirements()`
- That strict validator requires:
  - lowercase + uppercase + number + symbol (and length).

### Final mapping (stable)
- **Onboarding flows (must match onboarding UI):**
  - `bw_mew_supabase_password_meets_onboarding_requirements()`
  - used by:
    - `bw_mew_handle_supabase_create_password()`
    - `bw_mew_handle_set_password_modal()`
- **Logged-in account password update (advanced profile rules):**
  - `bw_mew_supabase_password_meets_requirements()`
  - used by:
    - `bw_mew_handle_supabase_update_password()`

### Files touched for this fix
- `includes/woocommerce-overrides/class-bw-supabase-auth.php`

### Quick verification set
1. OTP new-user flow:
   - `CiaoSimone1` => must pass.
   - `CiaoSimone!` => must pass.
2. Logged-in profile password update:
   - strict 5-rule policy remains active.


---

## Source: `docs/50-ops/README.md`

# 50 Ops

Operational runbooks and maintenance references that are cross-domain.

## Maintenance System
- [maintenance-framework.md](maintenance-framework.md)
- [incident-classification.md](incident-classification.md)
- [maintenance-workflow.md](maintenance-workflow.md)
- [regression-protocol.md](regression-protocol.md)
- [maintenance-decision-matrix.md](maintenance-decision-matrix.md)
- [runbooks/](runbooks/README.md)

## Official Development Protocol
- [blackwork-development-protocol.md](blackwork-development-protocol.md)

This protocol governs all future tasks.

## Control Model
- Framework = theory and principles.
- Runbooks = operational execution guides by domain.
- Decision matrix = control logic for choosing mandatory actions.


---

## Source: `docs/50-ops/admin-panel-reality-audit.md`

# Admin Panel Reality Audit — Blackwork Site

## Protocol Classification (before analysis)
- Domain: `ops` + `architecture` (admin configuration system audit).
- Incident type (decision matrix): Architectural/Integration visibility gap (audit task, no fix).
- Risk level: `L2` (functional configuration reliability risk if settings are inconsistent).
- Affected systems: WordPress admin settings UI, WooCommerce checkout/payment/auth integrations, frontend behavior toggles.
- Integration impact: Stripe gateways (Google Pay/Klarna/Apple Pay), Supabase auth/provisioning, Brevo mail marketing, Google Maps.
- Regression scope for this task: N/A (read-only audit, no code change).

## Scope audited
Admin menu `Blackwork Site` and related submenus/tabs:
- Cart Pop-up
- BW Coming Soon
- Login Page
- My Account Page
- Checkout
- Redirect
- Import Product
- Loading
- Header (submenu)
- Mail Marketing (Brevo submenu)

## 1) Global admin structure map

### Menu registration and capabilities
- Main menu: `add_menu_page(..., 'manage_options', 'blackwork-site-settings', ...)` in `admin/class-blackwork-site-settings.php`.
- Submenu `Header`: `add_submenu_page(..., 'manage_options', 'bw-header-settings', ...)` in `includes/modules/header/admin/header-admin.php`.
- Submenu `Mail Marketing`: `add_submenu_page(..., 'manage_options', 'blackwork-mail-marketing', ...)` in `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`.

### Main tab router (`blackwork-site-settings`)
In `bw_site_settings_page()` (`admin/class-blackwork-site-settings.php`), the `tab` query arg selects renderers:
- `cart-popup` -> `bw_site_render_cart_popup_tab()`
- `bw-coming-soon` -> `bw_site_render_coming_soon_tab()`
- `account-page` -> `bw_site_render_account_page_tab()`
- `my-account-page` -> `bw_site_render_my_account_front_tab()`
- `checkout` -> `bw_site_render_checkout_tab()`
- `redirect` -> `bw_site_render_redirect_tab()`
- `import-product` -> `bw_site_render_import_product_tab()`
- `loading` -> `bw_site_render_loading_tab()`

### Nonce and save model
- Most tabs in `admin/class-blackwork-site-settings.php` use custom POST handlers + `check_admin_referer(...)` + `update_option(...)`.
- `Header` uses WordPress Settings API (`register_setting`) with `sanitize_callback`.
- `Mail Marketing` uses custom `handle_post()` with nonce and explicit sanitization.

### AJAX endpoints
From `admin/class-blackwork-site-settings.php`:
- `bw_google_pay_test_connection`
- `bw_google_maps_test_connection`
- `bw_klarna_test_connection`
- `bw_apple_pay_test_connection`
- `bw_apple_pay_verify_domain`

From `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`:
- `bw_brevo_test_connection`
- `bw_brevo_order_refresh_status`
- `bw_brevo_order_retry_subscribe`
- `bw_brevo_order_load_lists`
- `bw_brevo_user_check_status`
- `bw_brevo_user_sync_status`

### Admin assets enqueued
`admin/class-blackwork-site-settings.php`:
- CSS: `admin/css/blackwork-site-settings.css`
- JS: `admin/js/bw-redirects.js`
- JS: `admin/js/bw-google-pay-admin.js` (nonce localized)
- JS: `admin/js/bw-klarna-admin.js` (nonce localized)
- JS: `admin/js/bw-apple-pay-admin.js` (nonce localized)
- JS: `admin/js/bw-checkout-subscribe.js` (Mail Marketing General only)
- JS: `assets/js/bw-border-toggle-admin.js`

`includes/modules/header/admin/header-admin.php`:
- `includes/modules/header/admin/header-admin.js`
- `wp_enqueue_media()`

`includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`:
- `admin/js/bw-order-newsletter-status.js`
- `admin/js/bw-user-mail-marketing.js`
- `admin/css/bw-order-newsletter-status.css`

## 2) Tab-by-tab reality map

## Cart Pop-up
- Renderer: `bw_site_render_cart_popup_tab()` -> save delegated to `bw_cart_popup_save_settings()` in `cart-popup/admin/settings-page.php`.
- Nonce: `bw_cart_popup_save` (`bw_cart_popup_nonce`).
- Option keys stored:
  - Toggle/behavior: `bw_cart_popup_active`, `bw_cart_popup_show_floating_trigger`, `bw_cart_popup_slide_animation`, `bw_cart_popup_show_quantity_badge`, `bw_cart_popup_return_shop_url`.
  - Layout/colors: `bw_cart_popup_panel_width`, `bw_cart_popup_mobile_width`, `bw_cart_popup_overlay_color`, `bw_cart_popup_overlay_opacity`, `bw_cart_popup_panel_bg`, `bw_cart_popup_svg_black`.
  - Checkout button: `bw_cart_popup_checkout_text`, `bw_cart_popup_checkout_bg`, `bw_cart_popup_checkout_bg_hover`, `bw_cart_popup_checkout_text_color`, `bw_cart_popup_checkout_text_hover`, `bw_cart_popup_checkout_font_size`, `bw_cart_popup_checkout_border_radius`, `bw_cart_popup_checkout_border_enabled`, `bw_cart_popup_checkout_border_width`, `bw_cart_popup_checkout_border_style`, `bw_cart_popup_checkout_border_color`, `bw_cart_popup_checkout_padding_*`.
  - Continue button: `bw_cart_popup_continue_text`, `bw_cart_popup_continue_url`, `bw_cart_popup_continue_bg`, `bw_cart_popup_continue_bg_hover`, `bw_cart_popup_continue_text_color`, `bw_cart_popup_continue_text_hover`, `bw_cart_popup_continue_font_size`, `bw_cart_popup_continue_border_radius`, `bw_cart_popup_continue_border_enabled`, `bw_cart_popup_continue_border_width`, `bw_cart_popup_continue_border_style`, `bw_cart_popup_continue_border_color`, `bw_cart_popup_continue_padding_*`.
  - Icons/SVG/spacings: `bw_cart_popup_additional_svg`, `bw_cart_popup_empty_cart_svg`, `bw_cart_popup_cart_icon_margin_*`, `bw_cart_popup_empty_cart_padding_*`.
  - Promo section: `bw_cart_popup_promo_section_label`, `bw_cart_popup_promo_input_padding_*`, `bw_cart_popup_promo_placeholder_font_size`, `bw_cart_popup_apply_button_font_weight`.
- When enabled:
  - Frontend cart panel/trigger rendered and styled via `cart-popup/frontend/cart-popup-frontend.php` and JS `cart-popup/assets/js/bw-cart-popup.js`.
  - AJAX cart operations enabled (`bw_cart_popup_*` endpoints in frontend module).
- Frontend consumers:
  - `cart-popup/cart-popup.php`
  - `cart-popup/frontend/cart-popup-frontend.php`
  - `woocommerce/templates/cart/cart-empty.php`

## BW Coming Soon
- Renderer: `bw_site_render_coming_soon_tab()`.
- Nonce: `bw_coming_soon_save`.
- Option key: `bw_coming_soon_active`.
- When enabled:
  - Site gate behavior controlled by `BW_coming_soon/includes/functions.php`.
- Frontend consumer:
  - `BW_coming_soon/includes/functions.php`

## Login Page
- Renderer: `bw_site_render_account_page_tab()`.
- Nonce: `bw_account_page_save`.
- Main option keys:
  - Provider/UI: `bw_account_login_provider`, `bw_account_login_image`, `bw_account_login_image_id`, `bw_account_logo`, `bw_account_logo_id`, `bw_account_logo_width`, `bw_account_logo_padding_top`, `bw_account_logo_padding_bottom`, `bw_account_login_title_supabase`, `bw_account_login_subtitle_supabase`, `bw_account_login_title_wordpress`, `bw_account_login_subtitle_wordpress`, `bw_account_show_social_buttons`, `bw_account_passwordless_url`.
  - Legacy compatibility keys still written: `bw_account_login_title`, `bw_account_login_subtitle`.
  - WordPress social config (when provider=wordpress): `bw_account_facebook`, `bw_account_google`, `bw_account_facebook_app_id`, `bw_account_facebook_app_secret`, `bw_account_google_client_id`, `bw_account_google_client_secret`.
  - Supabase auth config (when provider=supabase): `bw_supabase_*` auth/session/provider/oauth keys, including project URL/keys and provider toggles.
- When enabled/configured:
  - Auth provider selection drives frontend login flow and order-received/login templates.
  - Supabase path impacts plugin auth layer.
- Frontend consumers:
  - `woocommerce/templates/global/form-login.php`
  - `woocommerce/templates/myaccount/form-login.php`
  - `woocommerce/templates/checkout/order-received.php`
  - `includes/woocommerce-overrides/class-bw-supabase-auth.php`

## My Account Page
- Renderer: `bw_site_render_my_account_front_tab()`.
- Nonce: `bw_myaccount_front_save`.
- Option keys: `bw_myaccount_black_box_text`, `bw_myaccount_support_link`.
- When enabled:
  - Updates My Account page custom content/support link rendering.
- Frontend consumers:
  - Consumer logic is in WooCommerce/my-account override templates and related customization layer (reads these options at render time).

## Checkout
- Renderer: `bw_site_render_checkout_tab()`.
- Nonce: `bw_checkout_settings_save`.
- Option keys stored:
  - Layout/UI: `bw_checkout_logo`, `bw_checkout_logo_align`, `bw_checkout_logo_width`, `bw_checkout_logo_padding_*`, `bw_checkout_page_bg`, `bw_checkout_grid_bg`, `bw_checkout_left_bg_color`, `bw_checkout_right_bg_color`, `bw_checkout_right_sticky_top`, `bw_checkout_right_margin_top`, `bw_checkout_right_padding_*`, `bw_checkout_border_color`, `bw_checkout_left_width`, `bw_checkout_right_width`, `bw_checkout_thumb_ratio`, `bw_checkout_thumb_width`, `bw_checkout_show_order_heading`.
  - Text/content: `bw_checkout_legal_text`, `bw_checkout_footer_text`, `bw_checkout_footer_copyright_text`, `bw_checkout_show_footer_copyright`, `bw_checkout_show_return_to_shop`.
  - Section headings/free order: `bw_checkout_hide_billing_heading`, `bw_checkout_hide_additional_heading`, `bw_checkout_address_heading_label`, `bw_checkout_free_order_message`, `bw_checkout_free_order_button_text`.
  - Policies (array options): `bw_checkout_policy_refund`, `bw_checkout_policy_shipping`, `bw_checkout_policy_privacy`, `bw_checkout_policy_terms`, `bw_checkout_policy_contact`.
  - Checkout fields snapshot: `bw_checkout_fields_settings`.
  - Supabase provisioning: `bw_supabase_checkout_provision_enabled`, `bw_supabase_invite_redirect_url`, `bw_supabase_expired_link_redirect_url`.
  - Payments/gateway settings:
    - Google Pay: `bw_google_pay_*`
    - Klarna: `bw_klarna_*`
    - Apple Pay: `bw_apple_pay_*`
  - Google Maps address autocomplete: `bw_google_maps_enabled`, `bw_google_maps_api_key`, `bw_google_maps_autofill`, `bw_google_maps_restrict_country`.
- When enabled:
  - Alters checkout visual/layout behavior and policy widgets.
  - Enables/disables gateway wrappers and test connection flows.
  - Enables Google Maps autocomplete behavior and verification endpoint.
- Frontend consumers:
  - `woocommerce/woocommerce-init.php`
  - `woocommerce/templates/checkout/payment.php`
  - `includes/Gateways/class-bw-google-pay-gateway.php`
  - `includes/Gateways/class-bw-klarna-gateway.php`
  - `includes/Gateways/class-bw-apple-pay-gateway.php`

## Redirect
- Renderer: `bw_site_render_redirect_tab()`.
- Nonce: `bw_redirects_save`.
- Option key: `bw_redirects` (array of `source_url` + `target_url`).
- When enabled:
  - Runtime redirect rules loaded and applied by redirect module.
- Frontend consumer:
  - `includes/class-bw-redirects.php`

## Import Product
- Renderer: `bw_site_render_import_product_tab()`.
- Capability gate: `manage_woocommerce` OR `manage_options`.
- Nonces:
  - Upload/analyze: `bw_import_upload`
  - Run import: `bw_import_run`
- Persistent state model:
  - Uses transient per-user (`bw_import_state_{user_id}`), not a stable option key.
- Form fields:
  - `bw_import_csv` (file), `bw_import_update_existing`, `bw_import_mapping[...]`.
- When run:
  - Parses CSV, maps columns, creates/updates WooCommerce products and related tax/meta/media data.
- Runtime functions:
  - `bw_import_handle_upload_request()`, `bw_import_handle_run_request()`, CSV parsing/mapping helpers in `admin/class-blackwork-site-settings.php`.

## Loading
- Renderer: `bw_site_render_loading_tab()`.
- Nonce: `bw_loading_settings_save`.
- Option key: `bw_loading_global_spinner_hidden`.
- When enabled:
  - Hides default WooCommerce spinner/loading overlay behavior in frontend layer.
- Frontend consumer:
  - `blackwork-core-plugin.php`.

## Header (submenu)
- Renderer: `bw_header_render_admin_page()`.
- Save model: Settings API (`settings_fields('bw_header_settings_group')`, `options.php`).
- Capability: `manage_options`.
- Registered setting:
  - Option key `bw_header_settings` (`BW_HEADER_OPTION_KEY`), type array.
  - Sanitize callback: `bw_header_sanitize_settings()`.
- Main fields in option array:
  - General: `enabled`, `header_title`, `background_color`, `background_transparent`, `logo_attachment_id`, `logo_width`, `logo_height`, `menus.desktop_menu_id`, `menus.mobile_menu_id`, `breakpoints.mobile`, `inner_padding_unit`, `inner_padding.*`.
  - Mobile layout/icons/labels/links: `mobile_layout.*`, `icons.*`, `labels.*`, `links.*`.
  - Scroll/smart header: `features.smart_scroll`, `smart_header.*` (thresholds, colors, opacities, blur params).
- When enabled:
  - Custom header render + smart-scroll behavior on frontend.
- Frontend consumer:
  - `includes/modules/header/frontend/header-render.php`

## Mail Marketing (Brevo) (submenu)
- Renderer: `render_mail_marketing_page()` with internal tabs `general` / `checkout`.
- Save handler: `handle_post()`.
- Nonce: `bw_mail_marketing_save`.
- Capability: `manage_options`.
- Options:
  - `bw_mail_marketing_general_settings`
  - `bw_mail_marketing_checkout_settings`
  - Legacy fallback/migration source: `bw_checkout_subscribe_settings`
- General fields:
  - API key/base/list, opt-in mode, DOI template/redirect, sender identity, debug log, resubscribe policy, sync flags.
- Checkout channel fields:
  - Enabled, default checked, label/privacy text, subscribe timing, channel opt-in mode, field placement key, priority offset.
- When enabled:
  - Checkout opt-in rendered/applied; contacts synced to Brevo; order/user status tooling available in admin.
- Frontend/backend consumers:
  - `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php` (admin + order/user ops)
  - Checkout integration and subscription execution path in same module ecosystem.

## 3) Dependencies between settings (observed)
- `bw_account_login_provider` controls which auth settings block is operational (WordPress social vs Supabase settings).
- Checkout payment toggles (`bw_google_pay_enabled`, `bw_klarna_enabled`, `bw_apple_pay_enabled`) combine with gateway plugin readiness and keys before effective availability.
- `bw_supabase_checkout_provision_enabled` depends on valid Supabase service-role/project configuration.
- Mail Marketing split model depends on migration bridge from `bw_checkout_subscribe_settings`.
- Header `features.smart_scroll` changes precedence: general background vs smart-header scroll colors/opacities.

## 4) Unused/dead/conflicting/missing-validation findings

### Likely unused or legacy settings
- Legacy login title keys still written for compatibility:
  - `bw_account_login_title`
  - `bw_account_login_subtitle`
- Obsolete cart-popup option cleanup exists (`delete_option('bw_cart_popup_checkout_url')`), indicating historical option drift.
- Mail marketing legacy option `bw_checkout_subscribe_settings` is still bridge source; split options are canonical.

### Potential conflicting flags/configurations
- Auth domain conflicts possible:
  - WordPress social toggles + Supabase OAuth toggles can coexist in DB, while provider switch decides runtime path.
- Payment domain conflicts:
  - Admin toggle may be ON while underlying gateway/plugin keys are incomplete -> UI says enabled, runtime not fully ready.
- Supabase plugin mode conflict:
  - `bw_supabase_with_plugins` + login mode combinations can produce mismatch (already partially warned in UI text).

### Missing or weak validation patterns
- Main settings tabs use custom POST handling instead of centralized `register_setting` schemas, creating uneven validation guarantees vs Header tab.
- Some save paths in main settings rely on direct `$_POST` reads (sanitized, but not always consistently `wp_unslash` first).
- Several cross-domain dependency validations are informational only (not hard-blocked), e.g. enabling feature without mandatory upstream keys.
- Redirect tab accepts free-form source/target strings (sanitized text/URL), but no duplicate/conflict detection (same source repeated with different targets).

## 5) Hooks inventory (high-level)
- Main settings module hooks:
  - `admin_menu`, `admin_enqueue_scripts`, `wp_ajax_*` for gateway/maps/domain checks.
- Header module hooks:
  - `admin_menu`, `admin_enqueue_scripts`, `admin_init` (`register_setting`).
- Mail marketing module hooks:
  - `admin_menu`, `admin_init`, `wp_ajax_*`, order-list filters/columns, bulk actions, admin notices, metaboxes.

## 6) Reality-audit conclusion
- The admin panel is functionally rich but architecturally mixed:
  - Main menu tabs rely mostly on custom save/update flows.
  - Header uses robust Settings API discipline.
  - Mail Marketing is a full subsystem with its own model, migration layer, and operational hooks.
- Highest structural risks are not immediate code errors, but configuration coherence risks:
  - parallel legacy/current options,
  - provider-mode divergence,
  - cross-integration toggles without hard dependency gates.


---

## Source: `docs/50-ops/audits/cart-checkout-js-structure-analysis.md`

# Cart / Checkout JS Structure Analysis

## 1) JS files by area

### Cart
- `assets/js/bw-cart.js`
- `includes/modules/header/assets/js/bw-navshop.js`
- `assets/js/bw-price-variation.js`

### Cart popup
- `cart-popup/assets/js/bw-cart-popup.js`

### Checkout
- `assets/js/bw-checkout.js`
- `assets/js/bw-checkout-notices.js`
- `assets/js/bw-payment-methods.js`
- `assets/js/bw-google-pay.js`
- `assets/js/bw-apple-pay.js`
- `assets/js/bw-stripe-upe-cleaner.js`

### Fragment refresh / lifecycle glue
- `cart-popup/assets/js/bw-cart-popup.js`
- `assets/js/bw-checkout.js`
- `assets/js/bw-payment-methods.js`
- `assets/js/bw-google-pay.js`
- `assets/js/bw-apple-pay.js`
- `assets/js/bw-premium-loader.js`

### Coupon handling
- `cart-popup/assets/js/bw-cart-popup.js`
- `assets/js/bw-checkout.js`

## 2) Per-file behavior

| File | Main listened events | Uses Woo fragment events | Mutates cart state | Mutates payment state |
|---|---|---|---|---|
| `assets/js/bw-cart.js` | `change` qty, `click` plus/minus, `updated_cart_totals` | Yes (`updated_cart_totals`) | Yes (triggers `update_cart`) | No |
| `cart-popup/assets/js/bw-cart-popup.js` | `added_to_cart`, `wc_fragments_refreshed`, `wc_fragment_refresh`, promo/coupon clicks, qty/remove clicks, floating trigger click | Yes (`wc_fragment_refresh`, `wc_fragments_refreshed`) | Yes (AJAX add/remove/update qty, apply/remove coupon) | No direct payment mutation (only triggers `update_checkout`) |
| `assets/js/bw-checkout.js` | `update_checkout`, `updated_checkout`, `checkout_error`, `applied_coupon`, `removed_coupon`, qty/remove clicks | Yes (checkout fragment lifecycle: `update_checkout`/`updated_checkout`) | Yes (checkout cart qty/remove + apply/remove coupon AJAX) | No direct gateway truth mutation; payment UI delegated elsewhere |
| `assets/js/bw-payment-methods.js` | `change` payment radios, `updated_checkout`, `checkout_error`, custom `payment_method_selected` | Yes (`updated_checkout`) | No | Yes (payment method selection/UI orchestration) |
| `assets/js/bw-google-pay.js` | `change` payment radios, `updated_checkout`, Stripe `paymentmethod`/`cancel`, click trigger | Yes (`updated_checkout`) | No | Yes (sets hidden method id, wallet UI/state, triggers checkout update) |
| `assets/js/bw-apple-pay.js` | `change` payment radios, `updated_checkout`, Stripe `paymentmethod`/`cancel`, click trigger | Yes (`updated_checkout`) | No | Yes (sets hidden method id, wallet UI/state, triggers checkout update) |
| `assets/js/bw-stripe-upe-cleaner.js` | `updated_checkout` | Yes (`updated_checkout`) | No | Yes (payment DOM cleanup/visibility) |
| `includes/modules/header/assets/js/bw-navshop.js` | click on `.bw-navshop__cart[data-use-popup=yes]` | No | No | No |
| `assets/js/bw-price-variation.js` | add-to-cart button click, triggers `adding_to_cart`/`added_to_cart` | Indirect (fires add-to-cart events, not fragment listener) | Yes (add-to-cart flow trigger) | No |
| `assets/js/bw-premium-loader.js` | `update_checkout`, `updated_checkout`, `adding_to_cart`, `added_to_cart` | Yes (checkout update lifecycle) | No | No |

## 3) Shared JS between Cart and Checkout

Yes.

- `assets/js/bw-premium-loader.js` is explicitly shared (handles both cart add-to-cart lifecycle and checkout lifecycle).
- `cart-popup/assets/js/bw-cart-popup.js` is cart-popup focused but triggers checkout refresh (`update_checkout`) and Woo fragment refresh, so it touches both flows operationally.

## 4) Order details duplicated for desktop/responsive? JS bound twice?

Yes, markup is duplicated at runtime in checkout mobile mode.

- `assets/js/bw-checkout.js` clones the order summary from desktop right column into `#bw-order-summary-panel` (mobile accordion), including coupon/input structures.
- JS binding is designed to avoid duplicate handlers on the same element:
  - floating-label init uses `data-bw-floating-label-initialized` guard.
  - coupon apply click uses delegated handler with `.off('click.bwCoupon').on(...)`.
- Practically, there are two DOM instances (desktop + mobile clone), each with its own element-level listeners; this is intentional. No clear double-bind on the same node was found.

## 5) Floating cart icon: fragment-aware or only UI toggle?

For floating cart trigger in popup (`.bw-cart-floating-trigger`):

- It is mainly UI-toggle/visibility logic (`click`, `scroll`, class toggle `hidden/is-visible`).
- It is updated from cart data reload paths (`loadCartContents`, `updateBadge`, `toggleFloatingTrigger`), not from a dedicated fragment-specific visibility listener.
- There is a fragment listener in `bw-cart-popup.js` (`wc_fragments_refreshed wc_fragment_refresh`), but that block updates button states, not floating-trigger visibility directly.


---

## Source: `docs/50-ops/audits/checkout-payment-selector-audit.md`

# Checkout Payment Selector Audit

## 1) Audit Scope
Real anchors analyzed:
- `woocommerce/templates/checkout/payment.php`
- `assets/js/bw-payment-methods.js`
- `assets/js/bw-stripe-upe-cleaner.js`
- `woocommerce/woocommerce-init.php`:
  - `add_filter('woocommerce_payment_gateways', 'bw_mew_add_google_pay_gateway')`
  - `add_filter('woocommerce_available_payment_gateways', 'bw_mew_hide_paypal_advanced_card_processing')`
  - `add_filter('wc_stripe_upe_params', 'bw_mew_customize_stripe_upe_appearance')`
- Wallet coupling scripts:
  - `assets/js/bw-google-pay.js`
  - `assets/js/bw-apple-pay.js`
- Gateway runtime classes:
  - `includes/Gateways/class-bw-abstract-stripe-gateway.php`
  - `includes/Gateways/class-bw-google-pay-gateway.php`
  - `includes/Gateways/class-bw-apple-pay-gateway.php`
  - `includes/Gateways/class-bw-klarna-gateway.php`

Fragment refresh triggers considered:
- WooCommerce `updated_checkout` lifecycle (checkout fragment replacement)
- custom `update_checkout` triggers from wallet scripts on cancel/error
- `checkout_error` listeners used to recover UI state

## 2) Selection Determinism Analysis
### How active method is decided
- Server-side (`payment.php`):
  - priority 1: `$_POST['payment_method']`
  - priority 2: `WC()->session->get('chosen_payment_method')`
  - fallback: first available gateway
- Client-side (`bw-payment-methods.js`):
  - enforces single checked radio in `syncAccordionState()`
  - tracks `BW_LAST_SELECTED_METHOD`
  - if checked radio disappears/disabled, picks remembered or first enabled fallback

Determinism conclusion:
- radio input `name="payment_method"` remains the intended authority.
- JS actively re-normalizes UI and checked state after events and fragment updates.

### What happens on fragment refresh
- `updated_checkout` handlers run in `bw-payment-methods.js`, `bw-google-pay.js`, `bw-apple-pay.js`, `bw-stripe-upe-cleaner.js`.
- `bw-payment-methods.js` re-syncs:
  - selected radio
  - `.is-selected`/`.is-open` classes
  - action button visibility (wallet vs place order)
- wallet scripts re-dedupe their DOM and re-apply availability UI.

### Gateway availability changes mid-session
- Server can remove gateways via `woocommerce_available_payment_gateways` (e.g. `ppcp-credit-card-gateway`, `woocommerce_payments` removed).
- If selected gateway is no longer present after refresh:
  - server fallback picks first available
  - client fallback re-selects remembered valid gateway or first enabled gateway.

### Is radio state always authoritative?
- In checkout submit contract: yes, `payment_method` radio is the main selector.
- Wallet flows add hidden IDs (`bw_google_pay_method_id`, `bw_apple_pay_method_id`) but gateway PHP still validates order payment method and requires wallet method id only for that selected wallet gateway.

### Is UI ever source-of-truth instead of gateway?
- UI is not intended as authority, but multiple UI normalizers operate in parallel.
- Effective authority remains:
  - server available gateways + checked radio at submit
  - webhook confirmation for final payment truth.

## 3) UPE vs Custom Selector Coupling
- Duplicate controls risk exists by design because Stripe UPE can inject accordion/tab controls.
- Current cleanup layers:
  1. `wc_stripe_upe_params` appearance rules hide Stripe tabs/accordion headers.
  2. `bw-stripe-upe-cleaner.js` force-hides Stripe UPE accordion nodes with inline `display:none !important`, MutationObserver, and polling.
  3. custom selector (`payment.php` + `bw-payment-methods.js`) remains visible control layer.

If UPE and custom selector disagree:
- custom radio can still indicate one gateway while UPE internal controls reappear transiently.
- mitigation exists (cleaner + sync), but it depends on fragile DOM selectors (`.p-AccordionButton...`, `data-testid`).
- this coupling is functional but structurally brittle to Stripe markup changes.

## 4) Failure Modes (Enumerate)
| Failure mode | Severity | Domain impacted | Protecting invariant | Current mitigation | Risk level |
|---|---|---|---|---|---|
| Fragment reload loses active state | High | Checkout, Payments | One checked `payment_method` must exist | server fallback + `syncAccordionState()` fallback | Medium |
| Double active gateway (UI mismatch) | High | Checkout | selected radio = visible active method | JS forces single checked radio, toggles `.is-selected` | Medium |
| Gateway available server-side but hidden client-side | Medium | Checkout UI | submit authority is server + radio | re-sync on `updated_checkout`, mutation observer | Medium |
| Wallet eligibility mismatch (selected wallet but unavailable on device) | High | Checkout, Payments | unavailable wallet cannot remain actionable | `BW_GPAY_AVAILABLE` / `BW_APPLE_PAY_AVAILABLE`, disable selection + fallback | Medium |
| Race between JS sync and fragment injection | High | Checkout, Payments | post-refresh state converges before submit | multiple event hooks + scheduled sync (`bwScheduleSync`) | Medium-High |
| UPE duplicate controls reappear | Medium | Checkout UI | custom selector remains unique visible control | UPE params hide + `bw-stripe-upe-cleaner.js` | Medium-High |
| Stale wallet hidden field survives method switch | Medium | Payments submit path | selected gateway must match processed gateway | gateway `process_payment()` checks method + required wallet method id | Low-Medium |

## 5) Idempotency & Submission Path
- Posted submit contract:
  - `payment_method=<gateway_id>` from selected radio
  - optional wallet hidden field:
    - `bw_google_pay_method_id`
    - `bw_apple_pay_method_id`
- Wallet scripts submit checkout through Woo AJAX endpoint after tokenizing payment method.
- If stale DOM exists:
  - selector script attempts to restore a single valid checked radio.
  - wallet hidden IDs can persist, but gateway handlers require matching `payment_method` and validate method-id presence/format in their own lane.

Cross-domain authority check:
- no direct authority inversion detected in selector path.
- final payment authority still resolves in gateway webhook/order state, not in selector UI.

## 6) Hardening Gaps
Structurally fragile:
- heavy reliance on concurrent JS handlers across four scripts on `updated_checkout`.
- UPE suppression depends on Stripe DOM/class selectors that may change.
- Apple Pay script contains duplicated function definitions (`submitCheckoutWithApplePay`, `runAvailabilityCheck`), increasing behavior drift risk.

Structurally safe:
- server-side chosen-method fallback in template.
- selector script enforces single checked radio and fallback selection.
- gateway processing validates method-specific requirements.

Requires stronger test coverage:
- repeated fragment refresh with gateway changes.
- wallet selection -> cancel -> switch gateway -> submit.
- UPE markup/version changes and selector coexistence.
- checkout error recovery with preserved deterministic selection.

## 7) Audit Verdict
- Determinism: **Mostly deterministic**
- Risk category: **Medium-High**
- Safe for controlled refactor: **Yes, with constraints**
  - safe only if selector contract and webhook/payment authority boundaries remain unchanged
  - requires strict regression on fragment refresh, wallet availability transitions, and UPE cleanup behavior


---

## Source: `docs/50-ops/audits/header-system-technical-audit.md`

# Header System Technical Audit

## Executive Summary
Il sistema header del plugin ha due percorsi runtime:

1. **Percorso principale attuale (Custom Header Module)** sotto `includes/modules/header/*`, attivato da opzione `bw_header_settings[enabled]`.
2. **Percorso legacy Smart Header** (`assets/js/bw-smart-header.js` + `assets/css/bw-smart-header.css`) caricato solo quando il modulo custom **non** e abilitato.

Il modulo custom renderizza un header desktop+mobile proprio, gestisce Smart Scroll (hide/show + scrolled state + dark-zone detection), search overlay AJAX, menu mobile off-canvas e cart badge WooCommerce fragments. La surface admin e "Blackwork Site -> Header", con storage unico in `wp_options` (`bw_header_settings`).

---

## 1) File Inventory

### PHP (rendering/hooks/admin/templates)
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/header-module.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/frontend/assets.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/frontend/header-render.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/frontend/fragments.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/frontend/ajax-search.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/helpers/menu.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/helpers/svg.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/admin/settings-schema.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/admin/header-admin.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/templates/header.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/templates/parts/mobile-nav.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/templates/parts/search-overlay.php`

Legacy/fallback:
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/blackwork-core-plugin.php` (`bw_enqueue_smart_header_assets`)

### JS
Custom header:
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/assets/js/header-init.js`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/assets/js/bw-navigation.js`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/assets/js/bw-navshop.js`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/assets/js/bw-search.js`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/admin/header-admin.js`

Legacy smart header:
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-smart-header.js`

### CSS
Custom header:
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/assets/css/header-layout.css`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/assets/css/bw-navigation.css`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/assets/css/bw-navshop.css`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/assets/css/bw-search.css`

Legacy:
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/css/bw-smart-header.css`

### Assets (icons/images/svg)
- Icone default header sono **inline SVG** in PHP (`bw_header_default_*_svg`).
- Close icon mobile nav e **data URI SVG** in CSS (`bw-navigation.css`).
- Logo/hamburger/search/cart custom caricabili via **Media Library attachment IDs** (non file statici fissi del modulo).

### Enqueue + conditional loading
- Custom frontend enqueue: `bw_header_enqueue_assets()` su `wp_enqueue_scripts` priority 20.
  - Condizioni: `!is_admin()`, `bw_header_is_enabled() === true`, non Elementor preview.
  - Dequeue legacy handles `bw-*-style/script`, `bw-smart-header-style/script`.
- Custom admin enqueue: `bw_header_admin_enqueue_assets()` su `admin_enqueue_scripts`.
  - Solo quando `$_GET['page'] === 'bw-header-settings'`.
- Legacy enqueue: `bw_enqueue_smart_header_assets()` su `wp_enqueue_scripts`.
  - Salta se custom header enabled.
  - Salta in admin e Elementor preview.

---

## 2) Admin Panel / Settings Surface

Admin page:
- **Blackwork Site -> Header**
- `add_submenu_page('blackwork-site-settings', ..., 'bw-header-settings', capability 'manage_options', ...)`
- Storage: `wp_options`, option key **`bw_header_settings`** (`BW_HEADER_OPTION_KEY`)
- Sanitization: `bw_header_sanitize_settings`
- Register: `register_setting('bw_header_settings_group', BW_HEADER_OPTION_KEY, ...)`
- Nonce: standard WordPress `settings_fields()` / `options.php` flow.

Field map (key -> default -> type -> storage/effect):
- `enabled` -> `0` -> checkbox -> `wp_options`; abilita render custom header.
- `header_title` -> `Blackwork Header` -> text -> `aria-label` header.
- `background_color` -> `#efefef` -> color -> bg base (solo se smart scroll OFF).
- `background_transparent` -> `0` -> checkbox -> bg transparente generale.
- `inner_padding_unit` -> `px` -> select(px/% ) -> padding desktop container.
- `inner_padding[top/right/bottom/left]` -> `18/28/18/28` -> number -> padding `.bw-custom-header__inner`.
- `logo_attachment_id` -> `0` -> media hidden ID -> logo output.
- `logo_width` -> `54` -> number.
- `logo_height` -> `54` -> number.
- `menus[desktop_menu_id]` -> `0` -> select menu.
- `menus[mobile_menu_id]` -> `0` -> select menu (fallback desktop).
- `breakpoints[mobile]` -> `1024` -> number -> switch desktop/mobile states.

Mobile layout:
- `mobile_layout[right_icons_gap]` -> `16` number.
- `mobile_layout[inner_padding][t/r/b/l]` -> `14/18/14/18` number.
- `mobile_layout[hamburger_padding][t/r/b/l]` -> `0`.
- `mobile_layout[hamburger_margin][t/r/b/l]` -> `0`.
- `mobile_layout[search_padding][t/r/b/l]` -> `0`.
- `mobile_layout[search_margin][t/r/b/l]` -> `0`.
- `mobile_layout[cart_padding][t/r/b/l]` -> `0`.
- `mobile_layout[cart_margin][t/r/b/l]` -> `0`.
- `mobile_layout[cart_badge_offset_x/y]` -> `0/0`.
- `mobile_layout[cart_badge_size]` -> `1.2`.
- `mobile_layout[desktop_cart_badge_offset_x/y]` -> `0/0`.
- `mobile_layout[desktop_cart_badge_size]` -> `1.2`.

Icons:
- `icons[mobile_hamburger_attachment_id]` -> `0`.
- `icons[mobile_search_attachment_id]` -> `0`.
- `icons[mobile_cart_attachment_id]` -> `0`.

Labels/links:
- `labels[search/account/cart]` -> `Search/Account/Cart`.
- `links[account/cart]` -> `/my-account/` `/cart/`.

Feature flags:
- `features[search]` -> `1` (non esposto direttamente nella UI attuale; preservato da sanitize se non postato).
- `features[navigation]` -> `1` (idem).
- `features[navshop]` -> `1` (idem).
- `features[smart_scroll]` -> `0` (tab Header Scroll).

Smart Header Scroll tab:
- `smart_header[scroll_down_threshold]` -> `100` number.
- `smart_header[scroll_up_threshold]` -> `0` number.
- `smart_header[scroll_delta]` -> `1` number.
- `smart_header[header_bg_color]` -> `#efefef` color.
- `smart_header[header_bg_opacity]` -> `1` range+number.
- `smart_header[header_scrolled_bg_color]` -> `#efefef` color.
- `smart_header[header_scrolled_bg_opacity]` -> `0.86` range+number.
- `smart_header[menu_blur_enabled]` -> `1` checkbox.
- `smart_header[menu_blur_amount]` -> `20` number.
- `smart_header[menu_blur_radius]` -> `12` number.
- `smart_header[menu_blur_tint_color]` -> `#ffffff` color.
- `smart_header[menu_blur_tint_opacity]` -> `0.15` range+number.
- `smart_header[menu_blur_scrolled_tint_color]` -> `#ffffff` color.
- `smart_header[menu_blur_scrolled_tint_opacity]` -> `0.15` range+number.
- `smart_header[menu_blur_padding_top/right/bottom/left]` -> `5/10/5/10` number.

---

## 3) Frontend Rendering Contract

- Entry point: `bw_header_render_frontend()` hooked on `wp_body_open` (priority 5).
- Theme header override:
  - `bw_header_disable_theme_header()` on `wp` removes `hello_elementor_render_header` if present.
  - `bw_header_theme_header_fallback_css()` on `wp_head` hides typical theme header selectors.
- Render conditions: non admin, non ajax/feed/embed, non Elementor preview, `enabled=1`.
- Template used: `includes/modules/header/templates/header.php`.
- Menu output: `wp_nav_menu()` via `bw_header_render_menu()`, with injected link class `bw-navigation__link`.
- Logo/icons: media attachment or fallback inline SVG.
- Cart/account links:
  - account URL from settings or default `/my-account/`.
  - cart URL from settings or default `/cart/`.
  - cart count from Woo `WC()->cart->get_cart_contents_count()`.
  - cart popup intent via `data-use-popup="yes"` (JS opens `window.BW_CartPopup` if available).

---

## 4) Smart Header Scroll Model (state machine)

### Trigger model (custom header path)
Source: `header-init.js` + localized `bwHeaderConfig`.

Inputs:
- `scrollDownThreshold`, `scrollUpThreshold`, `scrollDelta`, `breakpoint`.
- Current scroll position/direction.
- Dark-zone overlap detection (manual `.smart-header-dark-zone` + auto background analysis).

### States
- `top/reset`: near top (`<=2px`), removes hidden/visible transition classes.
- `visible`: class `bw-header-visible`.
- `hidden`: class `bw-header-hidden`.
- `scrolled`: class `bw-header-scrolled` if `scrollTop > 2`.
- `on-dark-zone`: class `bw-header-on-dark` when overlap >=30%.
- responsive mode class: `is-mobile` / `is-desktop`.

### State transitions
- On load: `boot()` ensures header in body, sets mobile/desktop class, initializes sticky logic, removes preload class.
- On scroll:
  - If top: reset.
  - If below activation point (`max(headerHeight, scrollDownThreshold)`): stays visible logic.
  - If past activation:
    - scrolling down by delta > `scrollDelta` => hidden.
    - scrolling up with `upDelta >= scrollUpThreshold` => visible.
- On resize: recompute offsets and state classes.
- On dark-zone observer callback/scroll: toggles `bw-header-on-dark`.

### What changes per state
- CSS classes only (no payment/business mutation).
- `bw-header-scrolled` drives background style swap.
- Hidden/visible drives `transform: translateY(...)`.
- Inline CSS (from PHP) controls smart bg/scrolled bg opacity and blur panel values.

### Precedence “General” vs “Header Scroll”
- If `features.smart_scroll=1`: smart header colors/opacities override general background.
- If smart scroll off: uses `background_transparent` / `background_color`.
- Mobile scrolled white override forced via high-specificity inline media rule.

### Exact JS events/handlers (custom path)
- `DOMContentLoaded` -> `boot()`.
- `window.scroll` (passive + rAF) -> `onScroll()`.
- `window.resize` -> recalc + responsive class + dark overlap check.
- `IntersectionObserver` callback -> dark-zone overlap evaluation.

Legacy path (when custom header disabled):
- `assets/js/bw-smart-header.js` with jQuery-ready init, `window.scroll`, `window.resize`, `window.load`, `beforeunload`, optional `elementor/frontend/init`.
- Classes: `.visible`, `.hidden`, `.scrolled`, `.smart-header--on-dark`.

---

## 5) Responsive Header Model

- Breakpoint source:
  - Dynamic option `breakpoints.mobile` (default 1024) used by JS and inline CSS.
  - Static CSS also includes media at `1024`, `769`, `768`.
- Markup strategy:
  - **Duplicated structures**: separate desktop block (`.bw-custom-header__desktop`) and mobile block (`.bw-custom-header__mobile`) in same template.
- Mobile menu behavior:
  - Off-canvas overlay panel (`.bw-navigation__mobile-overlay` + `.bw-navigation__mobile-panel`).
  - Toggle button opens/closes; close on overlay click, ESC, link click.
- Mobile toggle JS:
  - `bw-navigation.js` binds toggle/close/overlay/document keydown/link click.
  - Moves overlay to `<body>` to avoid transformed parent constraints.
- Clone/double-binding risk:
  - Navigation guarded by `data-bw-navigation-initialized`.
  - Search widget guarded per root via `data('bw-search-initialized')`, but each instance adds global `document keydown` listener; multiple widgets = multiple keydown handlers (functional ma potenziale overhead).

---

## 6) Coupling / Dependencies

- Elementor:
  - Enqueue skip in preview mode.
  - Theme header removal specifically for Hello Elementor hook.
- WooCommerce:
  - Cart count read from `WC()->cart`.
  - Fragment sync via `woocommerce_add_to_cart_fragments`.
  - Search AJAX queries `product` + `wc_get_product`.
  - Mobile auth link fallback to `wc_get_page_permalink('myaccount')`.
- Cart popup integration:
  - `bw-navshop.js` depends on global `window.BW_CartPopup.openPanel()`, fallback to cart URL.
- Shared runtime:
  - jQuery used by search/navshop/admin and legacy smart header.
  - Native JS in `header-init.js` + `bw-navigation.js`.
  - `IntersectionObserver` optional optimization with fallback check logic.

---

## 7) Risk & Regression Hotspots

High-risk hotspots:
- Global hooks affecting all frontend pages:
  - `wp_body_open` render injection.
  - `wp` header disable theme hook.
  - `wp_head` fallback CSS hide.
- Smart scroll listeners:
  - Scroll + resize + dark-zone detection on all pages.
- Dual-system coexistence:
  - Custom header + legacy smart header handles/dequeue order; potential conflicts if custom enable detection fails.
- Duplicated desktop/mobile markup:
  - parallel elements (cart/search/account) can divergere styling/behavior.
- Settings precedence ambiguity:
  - General bg vs smart scroll bg vs mobile forced white override.
- CSS specificity:
  - heavy use of `!important` + inline CSS may collide with theme/Elementor styles.
- Minor code bug in legacy cleanup:
  - `removeEventListener('resize', throttledScrollHandler)` appears mismatched handler name (legacy script only).

Short regression checklist:
- Desktop header render and menu dropdown.
- Mobile header render and off-canvas open/close (toggle, overlay click, ESC, link click).
- Smart scroll on/off transitions (top, down hide, up show, scrolled background).
- Page refresh at mid-scroll.
- Mobile scrolled background override.
- Search overlay open/close, live AJAX results, ESC close.
- Cart icon count sync after add-to-cart (fragment update).
- Cart icon click with/without cart-popup global.
- Account/cart link correctness.
- Elementor preview/editor (header not wrongly injected).

---

## 8) Unknowns / where to look

- Se in produzione esistono override tema/plugin su selettori header non presenti qui: **unknown**.
  - Verifica in theme files e in eventuali mu-plugins.
- Se sono presenti runtime asset media specifici (logo/icon attachments): **unknown** nel repo perche dipendono da upload DB/media library.
  - Verifica in `wp_posts/wp_postmeta` attachments e option `bw_header_settings`.


---

## Source: `docs/50-ops/audits/my-account-domain-audit.md`

# My Account Domain Audit

## 1) Audit Scope (Real anchors)
Anchors analyzed:
- `includes/woocommerce-overrides/class-bw-my-account.php`
- `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- `woocommerce/templates/myaccount/dashboard.php`
- `woocommerce/templates/myaccount/downloads.php`
- `woocommerce/templates/myaccount/orders.php`
- `woocommerce/templates/myaccount/form-edit-account.php`
- `woocommerce/templates/myaccount/view-order.php`
- `woocommerce/templates/myaccount/form-login.php`
- `woocommerce/templates/myaccount/auth-callback.php`
- `assets/js/bw-my-account.js`
- `assets/js/bw-account-page.js`
- `assets/js/bw-supabase-bridge.js`
- `assets/js/bw-password-modal.js`

## 2) Navigation & Endpoint Determinism
Endpoint map and labels (from `bw_mew_filter_account_menu_items()`):
- `dashboard`
- `downloads`
- `orders` -> label `My purchases`
- `edit-account` -> label `settings`
- `customer-logout` -> label `logout`
- custom endpoint registered: `set-password`

Routing normalization and callback handling:
- guest email entrypoints with `bw_after_login=orders|downloads` are normalized to My Account root before login.
- post-login redirect to `orders`/`downloads` occurs only when session is valid and onboarding gate allows it.
- unauthenticated invite/recovery transitions are forced to `?bw_auth_callback=1` loader route.
- stale logged-in callback query is cleaned by redirect to clean My Account URL.

Stale callback cleanup behavior:
- `bw_mew_cleanup_logged_in_auth_callback_query()` removes stale callback mode for logged-in users.
- bridge JS also cleans callback/auth params and hash to converge URL state.

What breaks if endpoints drift:
- menu labels can point to missing/unhandled endpoints.
- onboarding lock can trap users on wrong route.
- email entrypoint redirects (`orders`/`downloads`) fail silently if endpoint names change.

## 3) Data Authority & Write Boundaries
### Dashboard (name/email/support)
- Surface: `dashboard.php` + helpers (`bw_mew_get_dashboard_identity`, support link/text getters).
- Truth owner:
  - identity: WP user + user meta (with fallback from latest order billing names)
  - support/link text: plugin options
- Writes:
  - name sync from latest purchase can update `first_name`, `last_name`, and display name when missing/generic.
- Must never be overridden:
  - payment/order authority must not be changed by dashboard rendering logic.

### Orders (digital/physical separation)
- Surfaces: `orders.php` + dashboard digital/physical lists.
- Truth owner: WooCommerce orders + line items.
- Writes:
  - display only; no order state write in templates.
- Must never be overridden:
  - order lifecycle/status must remain Woo authority.

### Downloads visibility
- Surfaces: `downloads.php` + dashboard digital rows.
- Truth owner: Woo order/download entitlement + linked ownership.
- Writes:
  - no direct writes in template; read-only rendering.
- Must never be overridden:
  - auth/onboarding UI must not fake download entitlement.

### Billing fields auto-sync from latest order
- Surface: `bw_mew_sync_account_address_fields_from_latest_order()`.
- Truth owner: Woo customer meta in WP user meta namespace.
- Writes:
  - fills only missing billing/shipping fields from latest valid order.
- Must never be overridden:
  - existing user-entered fields should not be overwritten by order data.

### Shipping same-as-billing toggle
- Surfaces: `form-edit-account.php` + `bw_mew_handle_profile_update()` + `bw-my-account.js`.
- Truth owner: Woo customer meta (`shipping_*`, `billing_*`).
- Writes:
  - if checked, shipping fields copied from billing values.
  - if unchecked, submitted shipping fields validated and persisted.
- Must never be overridden:
  - JS visibility state must not replace server validation/persistence.

### Security: change password lane
- Surfaces:
  - settings lane: `bw_supabase_update_password`
  - onboarding lane: `bw_set_password_modal` / `bw_supabase_create_password`
- Truth owner:
  - credential: Supabase
  - session authority: WP login state
  - onboarding marker: local user meta `bw_supabase_onboarded`
- Writes:
  - Supabase password update endpoint
  - onboarding marker update + guest order claim on success
- Must never be overridden:
  - payment/order authority cannot be changed by password operations.

### Security: change email lane
- Surfaces: `bw_supabase_update_email` + pending-email banners (`bw_supabase_pending_email`) + callback flags.
- Truth owner:
  - confirmed email identity via Supabase confirmation flow
  - local pending state in WP user meta
- Writes:
  - sets pending email locally, updates Supabase user, sets confirmation redirect markers
- Must never be overridden:
  - unconfirmed pending email must not be treated as final identity.

## 4) Supabase Coupling & Gating
Onboarding marker meaning in My Account:
- `bw_supabase_onboarded != 1` means account setup not complete for Supabase mode.
- used by onboarding gate checks (`bw_user_needs_onboarding`) and modal decision logic.

Password modal gating dependence on logged-in state:
- modal renders only for logged-in users in Supabase provider mode.
- AJAX `bw_get_password_status`/`bw_set_password_modal` requires provider + session checks.
- if Supabase token/session missing, flow degrades to explicit re-login path.

Callback loader/anti-flash pathway:
- template-level callback surface: `myaccount/auth-callback.php`.
- route forcing: guest invite/set-password transitions redirected to callback URL.
- JS bridge (`bw-supabase-bridge.js`) maintains `bw_auth_in_progress`, cleans stale params, and avoids normal My Account render before bridge resolution.

Guest-to-account claim interaction with downloads/orders:
- claim routine `bw_mew_claim_guest_orders_for_user()` invoked in token-login and modal set-password success paths.
- intent: idempotent attachment of guest orders/download ownership to authenticated WP user.

## 5) Failure Modes (Enumerate)
| Failure case | Severity | Invariant that should prevent it | Current mitigation | Risk level |
|---|---|---|---|---|
| Logged-out flash on callback | High | callback must resolve auth before standard My Account render | callback template route + early callback redirect hint + auth preload class | Medium |
| Ghost loader (callback never resolves) | High | callback must converge or degrade to clean retry state | stale callback cleanup + session check endpoint + parameter cleanup | Medium-High |
| `bw_supabase_onboarded` stuck in wrong state | High | onboarding marker transitions must be deterministic | explicit marker writes in token-login, invite, modal flows + conditional checks | Medium |
| Billing/shipping empty despite prior orders | Medium | missing customer meta should be backfilled from latest order | `bw_mew_sync_account_address_fields_from_latest_order()` on account page | Low-Medium |
| Email change pending banner stuck | Medium | pending email must clear when confirmed email matches callback user data | `bw_mew_apply_supabase_user_to_wp()` clears `bw_supabase_pending_email` on confirmed match | Medium |
| Password validator mismatch (onboarding vs profile) | Medium | validator scope must match lane requirements | separate validators: onboarding (`bw_mew_supabase_password_meets_onboarding_requirements`) vs profile strict (`...meets_requirements`) | Medium |
| View-order invoice button duplication | Low-Medium | invoice action should render once per order view | primary shortcode render guarded; potential duplication still possible via external hooks/plugins (`woocommerce_view_order`) | Medium |

## 6) Hardening Gaps
Structurally fragile:
- high coupling between route/query-state logic and multiple JS bridges (`bw-account-page`, `bw-supabase-bridge`, `bw-my-account`, modal JS).
- callback and onboarding flows depend on synchronized URL param cleanup; drift can reintroduce loop/flash states.
- duplicated/overlapping UX state handlers across JS files increase race potential.

Structurally safe:
- clear authority split exists (WP session vs Supabase credentials vs Woo orders).
- endpoint/menu map is explicit and filtered centrally.
- server-side validation and nonce checks exist for write actions.

Requires regression coverage:
- callback invite/recovery to authenticated My Account without flash/loop.
- onboarding marker transitions across token login, create-password, modal set-password.
- email change pending/confirmed cycle including security tab auto-focus and URL cleanup.
- downloads/orders visibility after guest claim paths.
- billing/shipping auto-sync + same-as-billing toggle submit behavior.

## 7) Audit Verdict
- Determinism: **Mostly deterministic**
- Risk category: **Medium-High**
- Safe for controlled refactor: **Yes, with constraints**
  - preserve callback convergence and anti-flash pathway
  - keep onboarding marker transitions idempotent
  - maintain strict authority boundaries (WP session, Woo order state, Supabase identity lane)


---

## Source: `docs/50-ops/audits/redirect-engine-technical-audit.md`

# Redirect Engine — Technical Audit (Current Implementation)

## 1) Executive Summary
The Redirect feature provides admin-managed URL rewrite rules under the Blackwork Site admin panel and applies them on frontend requests before most other plugin layout hooks.  
Admin location: `Blackwork Site -> Redirect` (`?page=blackwork-site-settings&tab=redirect`).  
Current runtime behavior supports only permanent redirects (`301`) via `wp_safe_redirect()`, with exact match on normalized `path + query`.  
Primary risks observed:
- Loop/chain safety is partial (self-loop guard exists; multi-rule chains/cycles are not fully analyzed).
- Matching precedence can override normal frontend routes because it runs early on `template_redirect` (priority `5`).
- Performance is linear per request (`O(n)` on stored rules, no cache).
- Security is generally constrained by nonce/capability and `wp_safe_redirect`, but external-host behavior depends on WordPress allowed host policy/filters.

## 2) Entry Points & File Inventory
### Core bootstrap/load
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/blackwork-core-plugin.php`
  - Responsibility: plugin bootstrap.
  - Key point: conditionally requires Redirect runtime file:
    - `includes/class-bw-redirects.php`.

### Runtime redirect engine
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/class-bw-redirects.php`
  - Responsibility: normalization + frontend redirect execution.
  - Key functions:
    - `bw_normalize_redirect_path($url)`
    - `bw_maybe_redirect_request()`
  - Hook:
    - `add_action('template_redirect', 'bw_maybe_redirect_request', 5)`

### Admin UI + save handler
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/admin/class-blackwork-site-settings.php`
  - Responsibility: admin menu/tab rendering, form handling, option persistence.
  - Key functions:
    - `bw_site_settings_menu()` (registers top-level menu)
    - `bw_site_settings_page()` (tab router; includes Redirect tab)
    - `bw_site_render_redirect_tab()` (render + save for redirects)
    - `bw_site_settings_admin_assets($hook)` (enqueues redirect admin JS/CSS)
  - Key admin identifiers:
    - Menu slug: `blackwork-site-settings`
    - Redirect tab slug: `tab=redirect`
    - Nonce action/key: `bw_redirects_save` / `bw_redirects_nonce`

### Admin JS
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/admin/js/bw-redirects.js`
  - Responsibility: add/remove repeater rows in Redirect tab UI.
  - Behavior: DOM only (no AJAX save; submit is regular form POST).

### Admin CSS
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/admin/css/blackwork-site-settings.css`
  - Responsibility: Redirect table row action styling (`.bw-redirects-table .bw-redirect-actions`).

### Enqueue model
- JS handle: `bw-redirects-admin`
- Enqueued from: `bw_site_settings_admin_assets($hook)` in `admin/class-blackwork-site-settings.php`
- Scope: all allowed Blackwork admin pages (not redirect-tab-only), filtered by hook/page checks:
  - `toplevel_page_blackwork-site-settings`
  - `blackwork-site-settings_page_blackwork-mail-marketing`
  - `blackwork-site_page_blackwork-mail-marketing`
  - or `?page=blackwork-mail-marketing`

## 3) Storage Model
Redirect rules are stored in `wp_options` under option key:
- `bw_redirects`

Storage structure (array of rules):
```php
[
  [
    'source' => '/promo/black-friday',
    'target' => 'https://example.com/page'
  ],
  ...
]
```

Observed normalization/sanitization:
- On save:
  - `source_url` is trimmed, validated by `bw_normalize_redirect_path()` (must parse as URL/path and produce non-empty normalized value), then stored as sanitized raw text (`sanitize_text_field($source_raw)`), not the normalized result.
  - `target_url` is trimmed and sanitized with `esc_url_raw()`.
- On runtime match:
  - Request URI and stored source are normalized through `bw_normalize_redirect_path()`:
    - path always forced to leading slash
    - query (if any) appended as raw `?query`
- No explicit persistent ordering metadata; execution order is array order as stored.
- No explicit priority field per rule.
- Trailing slash and query ordering canonicalization are not rewritten; comparison remains strict string equality after normalization.

## 4) Admin Workflow
1. Admin opens `Blackwork Site -> Redirect`.
2. UI renders repeater rows from `bw_redirects` (or one blank row when empty).
3. Admin adds/removes rows client-side via `admin/js/bw-redirects.js`.
4. On submit (`bw_redirects_submit`):
   - capability check: `current_user_can('manage_options')`
   - nonce check: `check_admin_referer('bw_redirects_save', 'bw_redirects_nonce')`
   - loops through posted `bw_redirects[*][target_url|source_url]`
   - skips invalid entries (empty/invalid source or target after sanitization/normalization checks)
   - persists sanitized list with `update_option('bw_redirects', $sanitized)`
5. Success message shown: `Redirect salvati con successo!`
6. Edit/remove model:
   - No dedicated CRUD endpoints.
   - Editing is overwrite-by-resubmit.
   - Deletion is omission from submitted list.

Error surfacing:
- No granular per-row validation errors are displayed.
- Invalid rows are silently skipped.

## 5) Runtime Application Model
Runtime function: `bw_maybe_redirect_request()` (`includes/class-bw-redirects.php`).

Hook:
- `template_redirect` at priority `5`.

Execution flow:
1. Skip in admin (`is_admin()`).
2. Load `bw_redirects` option.
3. Read request from `$_SERVER['REQUEST_URI']`.
4. Normalize request path/query via `bw_normalize_redirect_path()`.
5. Iterate rules sequentially:
   - normalize source
   - sanitize target (`esc_url_raw`)
   - exact compare: `normalized_request === normalized_source`
6. Loop guard checks before redirect:
   - skip if full current URL equals target
   - skip if normalized target path equals normalized source
7. Execute:
   - `wp_safe_redirect($safe_target, 301); exit;`

Matching characteristics:
- Exact match only (no wildcard/prefix/regex).
- Querystring-sensitive exact string match.
- Trailing slash differences can produce non-match.
- Redirect type is fixed to `301`.

## 6) Precedence & Interactions
### WordPress canonical redirects
- This engine runs on `template_redirect` priority `5`.
- WordPress canonical redirect typically runs later (`template_redirect`, default priority `10` in core).
- Result: matched Blackwork redirect exits early and usually wins before canonical logic.

### WooCommerce/plugin template_redirect hooks in this plugin
Within this plugin, several template_redirect hooks are present at priorities `1`, `2`, `5`, `6+`, `9+` across checkout/account modules.
- Blackwork redirect at `5` runs:
  - after priority `1/2` handlers
  - before many layout/account handlers at `6/7/9/...`
- On matched rule, execution exits and downstream hooks do not run.

### Rank Math / other redirect plugins
- No direct integration logic detected in this plugin for Rank Math or third-party redirect plugins.
- Effective precedence against external plugins is **unknown** without active plugin list and their hook priorities.
- Where to verify:
  - active plugins + their `template_redirect` hooks
  - `has_action('template_redirect', ...)` inspection at runtime

## 7) Loop Prevention & Safety
Implemented:
- Self-target prevention by comparing current full URL with target.
- Source-target same-path prevention (`target_path === normalized_source`).

Not implemented:
- No global graph/cycle detection across multiple rules (A->B->A).
- No max-hop protection internal to this module (relies on browser/server behavior across multiple requests).
- No validation against redirecting protected/critical routes (e.g., checkout/account endpoints).

Admin validation sufficiency:
- Basic field sanitization and parse checks exist.
- Structural loop-chain safety is partial.

## 8) Performance Model
- Runtime complexity: `O(n)` per frontend request (`foreach` all rules).
- Storage access: reads full `bw_redirects` option each request where hook runs.
- No in-memory index, no transient/object-cache strategy in this module.
- For large rule sets (hundreds/thousands), request-time scan cost scales linearly.
- Early exit on first matched rule reduces cost only when matches are near start.

## 9) Security & Permissions
Admin-side:
- Capability check on submit: `manage_options`.
- Nonce verification: `bw_redirects_save`.
- Input sanitization:
  - source: `sanitize_text_field` (+ normalization validity gate)
  - target: `esc_url_raw`
- Output escaping in form: `esc_attr`.

Runtime-side:
- Uses `wp_safe_redirect` (safer than `wp_redirect`) + fixed status code `301`.
- Open redirect profile:
  - Target allows URL input format, but `wp_safe_redirect` enforces safe host policy.
  - Final behavior for external hosts depends on WordPress allowed redirect host rules/filters (`allowed_redirect_hosts`), not overridden in this module.
- No direct header injection primitives observed (no manual `Location` header output).

## 10) Failure Modes
- Invalid rule input:
  - skipped silently at save time.
- Conflicting rules:
  - first matching rule in stored order wins.
- Redirect target non-existing route:
  - redirect still executed; destination may 404.
- Endpoint collisions:
  - rules can match Woo/account/checkout paths due to early template_redirect execution.
- Loop chains:
  - simple self-loop guard exists; multi-step loops not comprehensively blocked.
- Permalink or path format drift:
  - exact string matching may break with slash/query variations.
- Multilingual/path prefix scenarios:
  - no locale-aware matching logic detected.

## 11) Verified Risk Summary
- Routing integrity risk: **Medium-High**
  - Exact-match engine with early hook and no explicit route protection can override critical frontend routes.
- SEO risk: **Medium**
  - Always-301 behavior; misconfigured rules can create persistent wrong canonical destinations.
- Performance risk: **Medium**
  - Linear scan per request, no indexing/caching.
- Security risk: **Medium**
  - Admin access + nonce controls are present; runtime relies on `wp_safe_redirect`.
  - External redirect behavior depends on global allowed-host policy (environment-dependent).

## 12) Open Questions / Unknowns
- External plugin precedence (Rank Math/other redirect plugins): **unknown** from repository-only inspection.
  - Check active plugin stack and runtime hook priorities.
- Effective `allowed_redirect_hosts` policy in target environment: **unknown** in this plugin.
  - Check theme/mu-plugin/other plugin filters.
- Production scale of `bw_redirects` dataset: **unknown**.
  - Check actual option size/count in DB.
- Operational ordering guarantees when admin reorders rules: **unknown** (UI does not expose drag-sort; order depends on submitted array positions).


---

## Source: `docs/50-ops/audits/repo-root-hygiene-audit.md`

# Repo Root Hygiene Audit (Current Implementation)

Date: 2026-02-27
Scope: root-level files audit only (no runtime refactor)

## A) Plugin Header Metadata Target

Confirmed main plugin header file:
- `blackwork-core-plugin.php`

Confirmed display metadata fields in header block:
- `Plugin Name`
- `Description`
- `Version`
- `Author`

## B) Root File Inventory and Actions

| Root file | Purpose (evidence-based) | Required for runtime? | Recommended action | Notes |
|---|---|---:|---|---|
| `.DS_Store` | macOS Finder metadata file (OS artifact). | No | DELETE | Already covered by `.gitignore` (`.DS_Store`). Remove tracked copy from git index. |
| `AGENTS.md` | Agent workflow rules; explicitly referenced by root `README.md` and `docs/00-overview/README.md`. | No (runtime), Yes (workflow governance) | KEEP | Governance/tooling contract file at repo root is intentional. |
| `CLAUDE.md` | Assistant/tooling guidance; referenced by root `README.md` and `docs/00-overview/README.md`. | No (runtime), Yes (tooling context) | KEEP | Keep until tooling policy is migrated and links updated. |
| `CHANGELOG.md` | Change history; referenced by templates and ops/governance docs. | No (runtime), Yes (release/documentation discipline) | KEEP | Required by maintenance/documentation workflows. |
| `README.md` | Root repository entrypoint; points to docs and root governance docs. | No (runtime), Yes (repo onboarding) | KEEP | Should remain concise and accurate. |
| `checkout-css-fix.patch` | Standalone patch artifact with CSS diff; no runtime loader/reference found. | No | MOVE | Move to `docs/99-archive/root/checkout-css-fix.patch` as historical artifact. |
| `composer.json` | Composer dev tooling config (PHPCS scripts and standards). | No (frontend runtime), Yes (developer lint pipeline) | KEEP | Required for mandatory checks (`composer run lint:main`). |
| `composer.lock` | Locked dependency tree for composer dev tools. | No (frontend runtime), Yes (reproducible dev tooling) | KEEP | Keep with `composer.json` for deterministic tooling. |
| `elementor-smart-header.html` | Legacy standalone instructional/prototype HTML for smart header; no runtime load reference found. | No | MOVE | Move to `docs/99-archive/root/elementor-smart-header.html`. |
| `smart-header.html` | Legacy standalone smart header reference/prototype HTML; no runtime load reference found. | No | MOVE | Move to `docs/99-archive/root/smart-header.html`. |

## C) Safe Git Actions

### DELETE actions

For tracked OS artifact:

```bash
git rm .DS_Store
```

### MOVE actions (conservative archival)

```bash
mkdir -p docs/99-archive/root
git mv checkout-css-fix.patch docs/99-archive/root/checkout-css-fix.patch
git mv elementor-smart-header.html docs/99-archive/root/elementor-smart-header.html
git mv smart-header.html docs/99-archive/root/smart-header.html
```

## D) .gitignore Additions

Current `.gitignore` already contains:
- `.DS_Store`

No mandatory new `.gitignore` entries are required for the audited files.

## E) Runtime Safety Statement

The recommended actions above are metadata/repository hygiene actions only.
They MUST NOT alter runtime behavior because:
- Plugin bootstrap file path/name remains unchanged.
- Plugin folder slug remains unchanged.
- Text domain and internal prefixes remain unchanged.
- No hooks, options, runtime modules, or templates are modified.


---

## Source: `docs/50-ops/audits/search-system-technical-audit.md`

# Search System — Technical Audit (Current Implementation)

## 1) Executive Summary

The current Blackwork search runtime is a **header overlay live search for WooCommerce products**. It is implemented inside the custom Header module and uses `admin-ajax.php` (not REST) for live results.

Admin configuration is not exposed as a dedicated top-level “Search” tab; it is currently managed through:
- **Blackwork Site → Header** submenu page slug: `bw-header-settings`
- Header admin tabs: `General` and `Header Scroll`
- Search-related controls are in the Header `General` tab (label/icon/mobile spacing), while live-search query behavior is mostly hardcoded in runtime.

Main runtime surfaces:
- Frontend search trigger + overlay markup (header template)
- Frontend JS widget (`bw-search.js`) with debounce + AJAX
- AJAX endpoint (`bw_live_search_products`) querying WooCommerce products

Main risks observed:
- Performance risk on large catalogs due to uncached `WP_Query` text search per request
- Relevance limitations due to reliance on native `s` query + fixed `posts_per_page=12`
- Partial filter drift: backend accepts category/type filters, frontend currently sends no active category/type constraints
- No verified implementation found for “initial letter detection / initials indexing” in current runtime path

## 2) Entry Points & File Inventory

### 2.1 Admin Settings UI (Search-related controls)

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/admin/header-admin.php`
  - Responsibilities:
    - Registers Header submenu (`bw-header-settings`) under `blackwork-site-settings`
    - Renders settings form containing search label/icon/mobile spacing controls
  - Key functions:
    - `bw_header_admin_menu()`
    - `bw_header_admin_enqueue_assets()`
    - `bw_header_render_admin_page()`

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/admin/settings-schema.php`
  - Responsibilities:
    - Defines defaults, option schema, sanitization, registration
  - Key functions:
    - `bw_header_default_settings()`
    - `bw_header_get_settings()`
    - `bw_header_sanitize_settings()`
    - `bw_header_register_settings()`

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/admin/header-admin.js`
  - Responsibilities:
    - Admin media picker interactions and tab toggling in Header settings
  - Search relevance:
    - Handles icon upload UI used by search icon settings

### 2.2 Frontend Rendering + Runtime

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/frontend/header-render.php`
  - Responsibilities:
    - Builds header markup and injects search blocks
    - Hooks header rendering to `wp_body_open`
  - Key functions:
    - `bw_header_render_search_block()`
    - `bw_header_render_frontend()`
  - Hooks:
    - `add_action('wp_body_open', 'bw_header_render_frontend', 5)`

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/templates/parts/search-overlay.php`
  - Responsibilities:
    - Search overlay HTML structure, form, results container, loading/message nodes

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/frontend/assets.php`
  - Responsibilities:
    - Enqueues search CSS/JS
    - Localizes AJAX config (`bwSearchAjax`)
    - Applies dynamic inline CSS tied to header settings
  - Key function:
    - `bw_header_enqueue_assets()`
  - Hook:
    - `add_action('wp_enqueue_scripts', 'bw_header_enqueue_assets', 20)`

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/assets/js/bw-search.js`
  - Responsibilities:
    - Search widget lifecycle, overlay open/close, debounce, AJAX calls, rendering live results
  - Key runtime object:
    - `class BWSearchWidget`

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/assets/css/bw-search.css`
  - Responsibilities:
    - Search button styles, overlay behavior, results grid, loading state, responsive rules
    - Includes classes for category filters (`.bw-search-overlay__filters`, `.bw-category-filter`)

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/assets/css/header-layout.css`
  - Responsibilities:
    - Header layout rules including placement/display of search block

### 2.3 Search Backend Endpoint(s)

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/frontend/ajax-search.php`
  - Responsibilities:
    - Live product search endpoint for header overlay
  - Key function:
    - `bw_header_live_search_products()`
  - Hooks:
    - `add_action('wp_ajax_bw_live_search_products', 'bw_header_live_search_products')`
    - `add_action('wp_ajax_nopriv_bw_live_search_products', 'bw_header_live_search_products')`

### 2.4 Related but Separate Search Endpoint (not header live search)

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/metabox/digital-products-metabox.php`
  - Key function:
    - `bw_search_products_ajax()`
  - Hook:
    - `add_action('wp_ajax_bw_search_products', 'bw_search_products_ajax')`
  - Scope:
    - Admin Select2 product lookup for metabox workflows, not the frontend header search runtime.

### 2.5 REST Routes

- No `register_rest_route(...)` specific to the current header search runtime was found in inspected code for this feature path.

## 3) Admin Configuration Model

### 3.1 Option Keys and Storage

Primary option key:
- `bw_header_settings` (stored in `wp_options`)

Default and merge model:
- `bw_header_default_settings()` defines defaults
- `bw_header_get_settings()` merges saved values with defaults using `array_replace_recursive`

Sanitization:
- `bw_header_sanitize_settings()` sanitizes all persisted header/search-related fields
- `register_setting('bw_header_settings_group', BW_HEADER_OPTION_KEY, [...])`

### 3.2 Search-related Config Fields

Within `bw_header_settings`:
- `labels.search` (default: `Search`)
- `icons.mobile_search_attachment_id` (default: `0`)
- `mobile_layout.search_padding.{top,right,bottom,left}` (default all `0`)
- `mobile_layout.search_margin.{top,right,bottom,left}` (default all `0`)
- `features.search` (default: `1`)

Observed behavior notes:
- `features.search` exists in schema and frontend feature gating, but current admin form does not expose a dedicated checkbox for it in the inspected Header page; sanitization preserves existing value when key is not posted.
- No admin field was found for live-search relevance tuning (min chars, result limit, query strategy, category presets).

### 3.3 Fields for Filters / Initials Behavior

- No dedicated admin fields for “initial letter detection / initials indexing” were found in current search runtime settings.
- No dedicated admin filter configuration for header live search categories/product type was found in inspected Header settings UI.

## 4) Frontend UX Model

### 4.1 Where Search Input Appears

Search button is rendered in Header runtime in both desktop and mobile blocks via `bw_header_render_search_block()` and `search-overlay.php`.

User flow:
1. User clicks search button (`.bw-search-button`)
2. JS opens fullscreen overlay (`.bw-search-overlay` with `is-active`)
3. User types in search input
4. Debounced AJAX live search executes after 300ms and min length >= 2
5. Results render in `.bw-search-results__grid`

### 4.2 Result Rendering Surface

Rendered as card grid in overlay (`bw-search.js`):
- Product image
- Product title
- Product `price_html`
- Product link

Fallback messages:
- Empty/no results: message from backend or default localized text
- Network/server error: generic client error message

### 4.3 Filters UI and Behavior

Observed implementation state:
- Backend endpoint supports `categories[]` and `product_type`
- Frontend JS currently submits `categories: []` and does not submit `product_type`
- CSS has filter UI classes, but overlay template contains no filter controls markup by default

Conclusion:
- Filter capability exists partially at endpoint level, but active UI-to-query filter mapping is not fully wired in the current header overlay implementation.

### 4.4 Initials/Letter UX

- No alphabet bar, initials jump navigation, or letter grouping UI was found in active search overlay template/JS path.

## 5) Query & Filter Model (Core)

### 5.1 Query Builder

In `bw_header_live_search_products()`:
- `post_type = product`
- `post_status = publish`
- `posts_per_page = 12`
- `s = <search_term>`

### 5.2 Search Target Scope

Current target:
- WooCommerce products only

No evidence in this endpoint of:
- simultaneous post/page search
- explicit title-only search override
- custom relevance scoring

### 5.3 Taxonomy Filters

Supported by endpoint when provided:
- `product_cat` by slug from `categories[]`
- `product_type` taxonomy from `product_type` input (allowed values: `simple`, `variable`, `grouped`, `external`)

Tax query relation:
- `AND` when both category and product type filters are present

### 5.4 Meta/Price/Availability Filters

No explicit `meta_query` for price, stock, or custom fields in this endpoint.

### 5.5 Sorting/Pagination Model

Sorting:
- Uses default WordPress search ordering (`WP_Query` default for `s` context)

Pagination:
- No pagination/infinite-scroll in current live endpoint response
- Hard response cap is 12 products per request

### 5.6 UI Filter → Query Mapping (Current State)

- Search input text (`.bw-search-overlay__input`) → `$_POST['search_term']` → `args['s']`
- Category filters UI: **not active in rendered template**; JS posts empty categories array
- Product type filter UI: **not found in active overlay implementation**

## 6) Initial Letter Detection / Indexing Model

### 6.1 Current Implementation Status

No verified implementation of initial-letter detection/indexing was found in the active Search runtime path.

Not found in inspected runtime surfaces:
- header search endpoint (`ajax-search.php`)
- header search frontend JS (`bw-search.js`)
- search overlay template (`search-overlay.php`)
- search settings schema/admin page for header

### 6.2 Normalization/Storage/Edge Rules

Because initials indexing logic was not found:
- normalization rules (case/accent/punctuation/stopwords): **unknown**
- precomputed vs runtime initials index: **unknown**
- initials storage schema: **unknown**
- numeric/symbol/accented-letter behavior for initials: **unknown**

Where to verify next:
- archived docs and prior legacy implementations in `docs/99-archive/` for historical behavior
- any removed/legacy widget code if restored outside current branch

## 7) Runtime Integration & Hooks

### 7.1 Key Hooks for Search Runtime

- `wp_enqueue_scripts` (priority 20)
  - callback: `bw_header_enqueue_assets()`
  - enqueues JS/CSS and localizes AJAX config

- `wp_body_open` (priority 5)
  - callback: `bw_header_render_frontend()`
  - injects header + search trigger/overlay markup

- AJAX hooks:
  - `wp_ajax_bw_live_search_products`
  - `wp_ajax_nopriv_bw_live_search_products`
  - callback: `bw_header_live_search_products()`

### 7.2 Header/Navigation Integration

Search is structurally coupled to the custom Header module:
- rendered inside header template blocks (desktop/mobile)
- overlay moved to `<body>` at runtime by JS for fullscreen behavior

### 7.3 Elementor / Theme Interaction

- Header render/enqueue short-circuits in Elementor preview mode (`is_preview_mode()` checks)
- Theme header disabled/fallback CSS managed separately in header runtime, affecting where search appears

### 7.4 Cross-reference Risk (runtime-hook-map)

Search runtime uses Tier-1 style hooks/endpoints in current governance mapping; changes to hook order or header injection points can alter visibility/timing of search UI and endpoint behavior.

## 8) Performance & Caching

### 8.1 Caching

For the header live search endpoint:
- No transient/object cache layer detected
- No custom index table detected

### 8.2 Client-side Throttling

`bw-search.js` includes:
- 300ms debounce before AJAX call
- abort of in-flight request on new input

### 8.3 Query Complexity

Server query characteristics:
- full-text-like `s` search over product posts
- optional taxonomy constraints
- no explicit result cache

Potential hotspots under large catalog:
- repeated `WP_Query` calls for active typing sessions
- relevance and latency sensitivity depends on DB size/index state

### 8.4 Expected Large-catalog Behavior (from implementation)

- Response is capped to 12 items per request, which bounds payload size.
- Request count can still be high during rapid typing sessions because each debounced term triggers a new query.

## 9) Error Handling & Observability

### 9.1 API/Server Error Surfacing

- Nonce failure or WordPress AJAX failure returns standard WP error response.
- Client side handles failed responses with generic messages:
  - `Errore durante la ricerca`
  - `Errore di connessione`

### 9.2 Empty Result Handling

- Backend returns success with empty products + message (`Nessun prodotto trovato`)
- Frontend renders empty state message in overlay

### 9.3 Logging

- No dedicated search logging subsystem was found for this endpoint path.
- Observability is primarily via browser/network inspection and standard WP AJAX responses.

## 10) Security

### 10.1 Admin Surface

- Header settings page requires `manage_options`.
- Settings persistence uses WordPress Settings API (`settings_fields`, `register_setting`, sanitize callback).

### 10.2 AJAX Endpoint Security

`bw_header_live_search_products()`:
- Nonce check: `check_ajax_referer('bw_search_nonce', 'nonce')`
- Input sanitization:
  - `sanitize_text_field` for `search_term` and `product_type`
  - `array_map('sanitize_text_field', ...)` for `categories`
- Public access allowed via `wp_ajax_nopriv_*` (expected for guest search UX)

### 10.3 Output Safety

- Product title escaped in JS via `escapeHtml()` before insertion
- `price_html` is injected as HTML from WooCommerce output
- URL/image data are inserted into markup from server response

### 10.4 Data Leakage Considerations

- Endpoint returns only basic product card fields (id/title/price/image/permalink).
- No sensitive user/session data exposure identified in inspected response payload.

## 11) Verified Risk Summary

- Relevance risk: **Medium**
  - Native `s` query and fixed limit may not align with advanced merchandising/relevance expectations.

- Performance risk: **Medium to High** (catalog-size dependent)
  - No server-side cache/index layer on live search endpoint; frequent AJAX search calls under load.

- Security risk: **Low to Medium**
  - Nonce + sanitization present; public endpoint exists by design; no explicit rate-limiting found.

- UX regression risk: **Medium**
  - Overlay and runtime depend on header injection and JS initialization guards.
  - Filter UI drift risk: CSS/backend support exists, but active template/JS filter controls are not fully wired.

## 12) Open Questions / Unknowns

1. Initial letter detection / initials indexing implementation:
- **Unknown / not found in active code path.**
- Next place to check: archived docs or legacy removed code outside current runtime modules.

2. Intended product filter UX for live search:
- Backend supports category/type, but current active template/JS does not expose complete filter controls.
- Historical intent likely documented in archived header architecture docs; runtime currently does not prove full filter UX.

3. Search admin “tab” requirement alignment:
- No dedicated Search tab slug found in current admin navigation.
- Search configuration appears embedded in Header settings (`bw-header-settings`) rather than standalone search tab.

4. Any external search engine integration (Algolia/Elastic/custom index):
- **Not found** in inspected implementation for this feature path.


---

## Source: `docs/50-ops/audits/theme-builder-lite-phase1-implementation.md`

# Theme Builder Lite - Phase 1 Implementation Audit

## 1) Scope Delivered
Phase 1 includes only:
- Custom Fonts
- Footer Template

Excluded from Phase 1:
- Single product template override
- Condition engine include/exclude resolver
- Woo template takeover

## 2) File Inventory

### Core bootstrap
- `blackwork-core-plugin.php`

### Theme Builder Lite module
- `includes/modules/theme-builder-lite/theme-builder-lite-module.php`
- `includes/modules/theme-builder-lite/config/feature-flags.php`
- `includes/modules/theme-builder-lite/cpt/template-cpt.php`
- `includes/modules/theme-builder-lite/cpt/template-meta.php`
- `includes/modules/theme-builder-lite/fonts/custom-fonts.php`
- `includes/modules/theme-builder-lite/runtime/footer-runtime.php`
- `includes/modules/theme-builder-lite/runtime/template-preview.php`
- `includes/modules/theme-builder-lite/templates/single-bw-template.php`
- `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.php`
- `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.js`

### Documentation
- `docs/30-features/theme-builder-lite/theme-builder-lite-spec.md`
- `docs/10-architecture/theme-builder-lite/runtime-hook-map.md`
- `docs/50-ops/audits/theme-builder-lite-phase1-implementation.md`

## 3) Options and Meta Keys Introduced

### Options
- `bw_theme_builder_lite_flags`
  - `enabled`
  - `custom_fonts_enabled`
  - `footer_override_enabled`

- `bw_custom_fonts_v1`
  - `version`
  - `fonts[]`
    - `font_family`
    - `sources.woff2`
    - `sources.woff`
    - `font_weight`
    - `font_style`

- `bw_theme_builder_lite_footer_v1`
  - `version`
  - `active_footer_template_id`

- `bw_tbl_rewrite_rules_version`
  - one-time rewrite migration marker for `bw_template` preview permalink stability

### Post Type and Meta
- CPT: `bw_template`
- Post meta: `bw_template_type` (Phase 1 value: `footer`)
- Elementor support automation:
  - Filter path: `elementor/cpt_support` includes `bw_template`
  - Option sync path: `elementor_cpt_support` is auto-updated in admin to include `bw_template`
  - Result: `Edit with Elementor` is available without manual Elementor settings changes

## 4) Feature Flags
- Master switch: `bw_theme_builder_lite_flags[enabled]`
- Fonts switch: `bw_theme_builder_lite_flags[custom_fonts_enabled]`
- Footer switch: `bw_theme_builder_lite_flags[footer_override_enabled]`

## 4.1) Admin UI Tabs and Option Mapping
- Tab `Settings` (default):
  - `bw_theme_builder_lite_flags[enabled]`
- Tab `Fonts`:
  - `bw_theme_builder_lite_flags[custom_fonts_enabled]`
  - `bw_custom_fonts_v1[...]`
  - Fonts table is hidden when `custom_fonts_enabled=0`
- Tab `Footer`:
  - `bw_theme_builder_lite_flags[footer_override_enabled]`
  - `bw_theme_builder_lite_footer_v1[active_footer_template_id]`
  - Active footer selector is hidden when `footer_override_enabled=0`

Persistence rule:
- Single settings form persists all option keys across tabs.

## 4.2) Footer Override - Final Rendering Contract
- Activation conditions:
  - `bw_theme_builder_lite_flags[enabled]=1`
  - `bw_theme_builder_lite_flags[footer_override_enabled]=1`
  - active template resolves to published `bw_template` with `bw_template_type=footer`
- Rendering priority:
  - Elementor builder content first
  - fallback to classic content via `the_content`
- Preview safeguards:
  - override bypass on Elementor editor/preview requests
  - override bypass on `is_singular('bw_template')`
  - dedicated `template_include` preview template ensures valid 200 render context for Elementor
- Fail-open invariant:
  - invalid template/content/exception path returns to theme footer with no hard failure

## 4.3) Elementor Editor Freeze Resolution Evidence
- Root cause confirmed in Phase 1 hardening:
  - Elementor preview URL for `bw_template` returned 404, causing editor-side save-handle bootstrap failure.
- Mitigation implemented:
  - `bw_template` previewability contract + rewrite stabilization
  - noindex guard for previewable template URLs
  - strict admin asset enqueue scoping to Theme Builder Lite settings page
- Result:
  - `Edit with Elementor` for footer templates loads without stuck loading overlay.

## 5) Rollback Steps
1. Open `Blackwork Site -> Theme Builder Lite`.
2. Disable `Enable Theme Builder Lite` (master switch).
3. Save settings.
4. Verify frontend:
   - Theme footer is visible again.
   - No custom `@font-face` output from Theme Builder Lite.
5. Optional partial rollback:
   - disable only `Custom Fonts` or only `Footer Override`.

## 6) Test Checklist

### Admin checks
- [ ] Theme Builder Lite submenu is visible under Blackwork Site.
- [ ] Fonts rows can be added/removed and media picker selects WOFF2/WOFF URLs.
- [ ] Invalid/non-font sources are dropped after save.
- [ ] `bw_template` CPT appears and template type metabox is present.
- [ ] Active footer dropdown lists published `bw_template` posts with type `footer`.

### Frontend checks
- [ ] With flags off, no footer override and no custom fonts output.
- [ ] With fonts enabled + valid sources, `@font-face` CSS is present.
- [ ] With footer override enabled + valid active template, Elementor footer content renders.
- [ ] With missing/invalid active template, theme footer remains (fail-open).
- [ ] Header behavior remains unchanged.


---

## Source: `docs/50-ops/blackwork-development-protocol.md`

# Blackwork Development & Maintenance Protocol

## Purpose
This protocol defines the mandatory workflow for any task in Blackwork:
- Bug fix
- Refactor
- Integration update
- UX change
- Feature evolution

No task is allowed outside this protocol.

## Phase 1 — Classification
Before touching code:
- Use `incident-classification.md`
- Use `maintenance-decision-matrix.md`
- Identify:
  - Domain
  - Risk level
  - Impacted systems
  - Regression scope

Output required:
- Written action plan

No implementation allowed in this phase.

## Phase 2 — Controlled Implementation
Rules:
- Follow `maintenance-workflow.md`
- Follow relevant domain runbook
- Preserve architecture boundaries
- Do not modify unrelated domains
- Flag architectural deviation

Output required:
- Files modified
- Scope summary

## Phase 3 — Regression Validation
Mandatory for:
- Level 1
- Level 2

Includes:
- Global regression protocol
- Domain-specific checklist

No release allowed without regression confirmation.

## Phase 4 — Documentation Synchronization
Based on `maintenance-decision-matrix.md`:

Update:
- `CHANGELOG.md`
- Feature docs (if required)
- Runbook (if knowledge improved)
- ADR (if architecture changed)

Maintenance is NOT complete until documentation is aligned.

## Golden Rules
- No direct fix without classification.
- No merge without regression.
- No change without documentation sync.
- Architecture boundaries must be preserved.


---

## Source: `docs/50-ops/checkout-reality-audit.md`

# Checkout Reality Audit

## Maintenance Task Classification
- Incident type: Documentation alignment / reality audit
- Classification reference: `docs/50-ops/incident-classification.md`
- Decision matrix path used: Architecture/Integration visibility audit (no implementation)
- Primary domain: Checkout
- Secondary domains: Payments, Auth, Integrations, Frontend runtime
- Related integrations: Stripe (Google Pay/Apple Pay/Klarna), Supabase, Google Maps, Brevo
- Risk level: L2 (configuration/runtime coupling complexity)
- Release blocking status: No (audit-only)
- Regression scope: Not required (no code changes)
- Required documentation updates:
  - `CHANGELOG.md`: No
  - Feature docs: No
  - Runbook: No
  - ADR: No

## Global Overview
This audit maps the actual checkout domain as implemented in code. The checkout stack is composed of:
- Admin writer layer in `admin/class-blackwork-site-settings.php` (Checkout tab and sub-tabs).
- Frontend runtime orchestrator in `woocommerce/woocommerce-init.php`.
- Checkout templates in `woocommerce/templates/checkout/`.
- Gateway implementations in `includes/Gateways/` and `includes/woocommerce-overrides/`.
- Checkout field configurator (`bw_checkout_fields_settings`) via:
  - `includes/admin/checkout-fields/class-bw-checkout-fields-admin.php`
  - `includes/admin/checkout-fields/class-bw-checkout-fields-frontend.php`

## Settings Map

### A) Checkout layout and visual settings
Written by Checkout admin UI controls in `bw_site_render_checkout_tab()`.

Keys:
- `bw_checkout_logo`
- `bw_checkout_logo_align`
- `bw_checkout_logo_width`
- `bw_checkout_logo_padding_top`
- `bw_checkout_logo_padding_right`
- `bw_checkout_logo_padding_bottom`
- `bw_checkout_logo_padding_left`
- `bw_checkout_show_order_heading`
- `bw_checkout_page_bg`
- `bw_checkout_grid_bg`
- `bw_checkout_left_bg_color`
- `bw_checkout_right_bg_color`
- `bw_checkout_right_sticky_top`
- `bw_checkout_right_margin_top`
- `bw_checkout_right_padding_top`
- `bw_checkout_right_padding_right`
- `bw_checkout_right_padding_bottom`
- `bw_checkout_right_padding_left`
- `bw_checkout_border_color`
- `bw_checkout_left_width`
- `bw_checkout_right_width`
- `bw_checkout_thumb_ratio`
- `bw_checkout_thumb_width`

Defaults (detectable):
- Primary defaults are enforced in `bw_mew_get_checkout_settings()` in `woocommerce/woocommerce-init.php`.
- Legacy fallback still present for background keys:
  - `bw_checkout_page_bg_color` (fallback source)
  - `bw_checkout_grid_bg_color` (fallback source)

### B) Footer/legal/content settings
Keys:
- `bw_checkout_legal_text`
- `bw_checkout_footer_text`
- `bw_checkout_footer_copyright_text`
- `bw_checkout_show_footer_copyright`
- `bw_checkout_show_return_to_shop`

Defaults:
- `show_footer_copyright` -> `'1'`
- `show_return_to_shop` -> `'1'`

### C) Free-order and heading behavior
Keys:
- `bw_checkout_hide_billing_heading`
- `bw_checkout_hide_additional_heading`
- `bw_checkout_address_heading_label`
- `bw_checkout_free_order_message`
- `bw_checkout_free_order_button_text`

Defaults:
- Free-order message/button fallback strings are applied runtime in `bw_mew_enqueue_checkout_assets()` if empty.
- Heading defaults are partially inherited from `bw_checkout_fields_settings.section_headings`.

### D) Policy content (nested arrays)
Keys (array options):
- `bw_checkout_policy_refund`
- `bw_checkout_policy_shipping`
- `bw_checkout_policy_privacy`
- `bw_checkout_policy_terms`
- `bw_checkout_policy_contact`

Array shape:
- `enabled`
- `title`
- `subtitle`
- `content`

Default assumptions:
- In admin load, each policy defaults to:
  - `enabled = '1'`
  - empty text fields

### E) Checkout fields schema (nested master array)
Key:
- `bw_checkout_fields_settings`

Managed by Checkout Fields module (`BW_Checkout_Fields_Admin` / `BW_Checkout_Fields_Frontend`).

Structure:
- `version`
- `billing` / `shipping` / `order` / `account` field maps
- each field stores: `enabled`, `priority`, `width`, `label`, `required`
- `section_headings`:
  - `hide_billing_details`
  - `hide_additional_info`
  - `address_heading_text`

Default assumptions:
- baseline payload can be just `['version' => 1]`
- section heading defaults in frontend:
  - `hide_billing_details = 0`
  - `hide_additional_info = 0`
  - `address_heading_text = 'Delivery'`

### F) Payments toggles and credentials
Google Pay keys:
- `bw_google_pay_enabled`
- `bw_google_pay_test_mode`
- `bw_google_pay_publishable_key`
- `bw_google_pay_secret_key`
- `bw_google_pay_test_publishable_key`
- `bw_google_pay_test_secret_key`
- `bw_google_pay_statement_descriptor`
- `bw_google_pay_webhook_secret`
- `bw_google_pay_test_webhook_secret`

Klarna keys:
- `bw_klarna_enabled`
- `bw_klarna_publishable_key`
- `bw_klarna_secret_key`
- `bw_klarna_statement_descriptor`
- `bw_klarna_webhook_secret`

Apple Pay keys:
- `bw_apple_pay_enabled`
- `bw_apple_pay_express_helper_enabled`
- `bw_apple_pay_publishable_key`
- `bw_apple_pay_secret_key`
- `bw_apple_pay_statement_descriptor`
- `bw_apple_pay_webhook_secret`

Note:
- Gateway classes reference additional test keys for Klarna/Apple (`*_test_*`) that are not written by current checkout admin UI controls.

### G) Supabase checkout provisioning
Keys:
- `bw_supabase_checkout_provision_enabled`
- `bw_supabase_invite_redirect_url`
- `bw_supabase_expired_link_redirect_url`

Dependency keys read elsewhere:
- `bw_supabase_project_url`
- `bw_supabase_anon_key`
- `bw_supabase_service_role_key`

### H) Google Maps / autocomplete
Keys:
- `bw_google_maps_enabled`
- `bw_google_maps_api_key`
- `bw_google_maps_autofill`
- `bw_google_maps_restrict_country`

### I) Admin control mapping model
The admin UI mostly uses a direct naming convention:
- input `name` == option key (or array key for policies).
- policy editor writes `bw_checkout_policy_{name}[enabled|title|subtitle|content]`.
- checkout fields tab writes `bw_checkout_fields_settings` via its dedicated module.

## Consumers Map

### Runtime settings consumers (core)
- `woocommerce/woocommerce-init.php`
  - `bw_mew_get_checkout_settings()` reads and normalizes core checkout style/content keys.
  - `bw_mew_enqueue_checkout_assets()` reads checkout settings + free-order text + Google Maps keys.
  - `bw_mew_render_address_section_heading()` reads address heading label key.
  - heading suppression logic reads hide flags.

### Template consumers (checkout overrides)
Files involved:
- `woocommerce/templates/checkout/form-billing.php`
- `woocommerce/templates/checkout/form-checkout.php`
- `woocommerce/templates/checkout/form-coupon.php`
- `woocommerce/templates/checkout/form-shipping.php`
- `woocommerce/templates/checkout/form-verify-email.php`
- `woocommerce/templates/checkout/order-received.php`
- `woocommerce/templates/checkout/payment.php`
- `woocommerce/templates/checkout/review-order.php`

Most coupling-heavy template:
- `woocommerce/templates/checkout/payment.php`
  - evaluates readiness for `bw_klarna` and `bw_apple_pay`
  - checks plugin setting arrays like `woocommerce_bw_klarna_settings`, `woocommerce_bw_apple_pay_settings`
  - binds custom accordion/payment method UI structure consumed by frontend JS

### Gateway consumers
- `includes/Gateways/class-bw-google-pay-gateway.php`
- `includes/Gateways/class-bw-klarna-gateway.php`
- `includes/Gateways/class-bw-apple-pay-gateway.php`
- `includes/woocommerce-overrides/class-bw-google-pay-gateway.php`

These classes read enable flags and credentials and define payment processing/webhook key access.

### Checkout fields consumers
- Admin writer:
  - `includes/admin/checkout-fields/class-bw-checkout-fields-admin.php`
- Frontend applier:
  - `includes/admin/checkout-fields/class-bw-checkout-fields-frontend.php`
  - filter: `woocommerce_checkout_fields`

### Checkout subscribe (mail marketing in checkout path)
- `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php`
  - injects checkout field `bw_subscribe_newsletter`
  - stores checkout opt-in meta
  - executes Brevo subscription flow on created/paid order hooks

## Hook Map

### Checkout initialization and runtime wiring
From `woocommerce/woocommerce-init.php`:
- `add_action('plugins_loaded', 'bw_mew_initialize_woocommerce_overrides')`
- `add_action('wp_enqueue_scripts', 'bw_mew_enqueue_checkout_assets', 20)`
- `add_action('template_redirect', 'bw_mew_prepare_checkout_layout', 9)`
- `add_action('woocommerce_checkout_update_order_review', 'bw_mew_sync_checkout_cart_quantities', 10, 1)`
- `add_filter('woocommerce_checkout_posted_data', 'bw_mew_sync_billing_from_shipping_mode', 20, 1)`
- `add_action('woocommerce_checkout_before_customer_details', 'bw_mew_render_address_section_heading', 5)`
- `add_filter('woocommerce_available_payment_gateways', 'bw_mew_hide_paypal_advanced_card_processing')`
- `add_filter('wc_stripe_elements_options', 'bw_mew_customize_stripe_elements_style')`
- `add_filter('wc_stripe_elements_styling', 'bw_mew_customize_stripe_elements_style')`
- `add_filter('wc_stripe_upe_params', 'bw_mew_customize_stripe_upe_appearance')`
- AJAX coupon flow:
  - `wp_ajax_bw_apply_coupon` / `wp_ajax_nopriv_bw_apply_coupon`
  - `wp_ajax_bw_remove_coupon` / `wp_ajax_nopriv_bw_remove_coupon`

### Checkout fields module hooks
From `class-bw-checkout-fields-frontend.php`:
- `woocommerce_checkout_fields` (apply visibility/priority/required/label/width)
- `wp_enqueue_scripts` (enqueue checkout field CSS)
- `body_class` (state classes)

### Checkout subscribe/Brevo hooks in checkout flow
From `class-bw-checkout-subscribe-frontend.php`:
- `woocommerce_before_checkout_billing_form`
- `woocommerce_checkout_fields`
- `woocommerce_form_field`
- `woocommerce_checkout_create_order`
- `woocommerce_checkout_update_order_meta`
- `woocommerce_checkout_order_processed`
- `woocommerce_order_status_processing`
- `woocommerce_order_status_completed`
- `wp_enqueue_scripts`

### Admin-side checkout hooks and AJAX tests
From `admin/class-blackwork-site-settings.php`:
- `add_action('admin_enqueue_scripts', 'bw_site_settings_admin_assets')`
- AJAX test endpoints:
  - `wp_ajax_bw_google_pay_test_connection`
  - `wp_ajax_bw_google_maps_test_connection`
  - `wp_ajax_bw_klarna_test_connection`
  - `wp_ajax_bw_apple_pay_test_connection`
  - `wp_ajax_bw_apple_pay_verify_domain`

## Assets Map

### Frontend assets (checkout)
Enqueued in `bw_mew_enqueue_checkout_assets()`:
- CSS:
  - `assets/css/bw-checkout.css`
  - `assets/css/bw-payment-methods.css`
  - `assets/css/bw-checkout-notices.css`
- JS:
  - `assets/js/bw-checkout.js`
  - `assets/js/bw-payment-methods.js`
  - `assets/js/bw-checkout-notices.js`
  - `assets/js/bw-stripe-upe-cleaner.js`
  - `assets/js/bw-google-pay.js` (conditional)
  - `assets/js/bw-apple-pay.js` (conditional)
  - external: Stripe SDK (`https://js.stripe.com/v3/`)
  - external: Google Maps Places SDK (conditional)

Localized payloads:
- `bwCheckoutParams` (nonce + free order content)
- `bwGoogleMapsSettings`
- `bwGooglePayParams`
- `bwApplePayParams`

### Admin assets (checkout-related)
From `bw_site_settings_admin_assets()`:
- `admin/js/bw-google-pay-admin.js`
- `admin/js/bw-klarna-admin.js`
- `admin/js/bw-apple-pay-admin.js`
- `admin/js/bw-checkout-subscribe.js` (Mail Marketing General)
- `admin/css/blackwork-site-settings.css`
- shared helper: `assets/js/bw-border-toggle-admin.js`

### DOM-manipulation scripts affecting payment/checkout UI
- `assets/js/bw-payment-methods.js`
  - controls payment accordion selection state
  - toggles place-order button visibility vs wallet buttons
  - applies fallback selection when chosen gateway unavailable
- `assets/js/bw-google-pay.js`
  - conditionally disables/enables Google Pay row
  - mounts payment request button and injects hidden method field
- `assets/js/bw-apple-pay.js`
  - conditionally disables/enables Apple Pay row
  - manipulates express checkout wrappers and placeholders
- `assets/js/bw-stripe-upe-cleaner.js`
  - force-hides Stripe UPE accordion rows (card/klarna-related UI cleanup)
- `assets/js/bw-checkout.js`
  - checkout layout interactions, free-order banner behavior, shipping accordion, express checkout visibility adjustments

## Coupling Map

### Payments coupling (Apple Pay / Google Pay / Klarna)
Coupling model:
- Admin toggle indicates intent (`*_enabled`).
- Runtime availability additionally depends on:
  - gateway registration/availability in WooCommerce
  - required keys present
  - plugin-level settings arrays (`woocommerce_bw_*_settings`)
  - device/browser wallet capability checks (JS-side for wallet flows)

Notable precedence/fallback:
- Apple Pay publishable/secret fallback may use Google Pay keys in specific code paths.
- `bw_apple_pay_express_helper_enabled` controls express fallback behavior in `bwApplePayParams`.

### Stripe/UPE/payment selector coupling
- Checkout uses a custom payment accordion (`woocommerce/templates/checkout/payment.php` + `assets/js/bw-payment-methods.js`).
- Stripe UPE appearance is customized via:
  - `wc_stripe_elements_options`
  - `wc_stripe_elements_styling`
  - `wc_stripe_upe_params`
- `bw-stripe-upe-cleaner.js` forcibly removes duplicate/undesired UPE rows to align with custom selector UX.

### Supabase provisioning coupling
- Provisioning toggle/key flow:
  - `bw_supabase_checkout_provision_enabled`
  - `bw_supabase_invite_redirect_url`
  - `bw_supabase_expired_link_redirect_url`
- Operational dependency:
  - valid Supabase config (`project_url`, `anon_key`, service role where required)
- Runtime usage appears in checkout/order/login-related templates and Supabase auth override class.

### Google Maps / autocomplete coupling
- Enabled when both conditions are true:
  - `bw_google_maps_enabled = '1'`
  - `bw_google_maps_api_key` present
- Behavior flags:
  - `bw_google_maps_autofill`
  - `bw_google_maps_restrict_country`
- Coupled layers:
  - admin test endpoint + admin UI test button
  - frontend Google Places enqueue
  - `bw-checkout.js` runtime behavior via localized settings

## Risk & Gaps

### High-risk change zones
- `woocommerce/templates/checkout/payment.php`
  - central payment rendering/selection logic; high blast radius for checkout submit path.
- `assets/js/bw-payment-methods.js`
  - controls radio state, fallback gateway selection, and button visibility; race conditions with WooCommerce fragment refresh are possible.
- `assets/js/bw-google-pay.js` and `assets/js/bw-apple-pay.js`
  - wallet availability checks + hidden field injection + checkout submission coupling.
- `woocommerce/woocommerce-init.php` (enqueue + localized params + gateway gating)
  - acts as orchestration hub; small changes can affect multiple integrations simultaneously.

### Precedence rules observed
- Checkout style settings are normalized in `bw_mew_get_checkout_settings()`; invalid/missing values fallback to internal defaults.
- For Apple Pay, Google Pay keys can act as fallback in parts of the flow.
- Payment toggles alone do not guarantee runtime availability; readiness checks still gate behavior.

### Dead/legacy/consistency gaps
- Legacy fallback keys still read:
  - `bw_checkout_page_bg_color`
  - `bw_checkout_grid_bg_color`
- Gateway classes reference Klarna/Apple test key option names (`*_test_*`) not written by current checkout admin UI.
- Mixed save architecture:
  - Checkout tab writes options directly
  - Checkout fields use dedicated option module with separate nonce/process
- Soft-gating side effect:
  - UI may indicate feature enabled while runtime prerequisites are incomplete.

### Validation/conflict gaps
- No centralized schema (`register_setting`) for main checkout options; validation is distributed in manual handlers.
- Cross-domain dependency checks (e.g., Supabase provisioning without full credential set) are not strongly blocked at save time.
- Payment method conflicts can be introduced by external gateway UI re-renders; code mitigates via JS re-sync logic, but complexity remains high.

## Conclusion
The checkout domain is operationally rich but highly coupled across admin settings, template overrides, frontend orchestration scripts, and external integrations.

Recommended next step for official architecture mapping:
1. Promote this audit into a stable `docs/10-architecture/checkout-architecture-map.md` with explicit state machine diagrams (admin intent -> runtime eligibility -> checkout execution).
2. Define canonical precedence tables for payment availability and Supabase provisioning.
3. Add a formal validation matrix for save-time dependency checks (soft-gating policy made explicit per integration).


---

## Source: `docs/50-ops/incident-classification.md`

# Incident Classification

## Level 1 - Critical
Examples:
- Checkout broken
- Payment failure
- Auth failure
- Security issue

Response priority:
- Immediate triage and same-day mitigation.

Documentation requirement:
- Update incident notes and impacted domain docs.
- Add changelog entry.
- Add ADR note if architectural contract changed.

Test depth:
- Full regression protocol (mandatory).
- Incident-specific deep verification.

## Level 2 - Functional
Examples:
- Feature partially broken
- Integration unstable
- Layout breaking

Response priority:
- High priority, planned in current maintenance cycle.

Documentation requirement:
- Update affected domain documentation.
- Add changelog entry.

Test depth:
- Full regression protocol (mandatory).
- Focused tests on impacted domains.

## Level 3 - Cosmetic
Examples:
- CSS issue
- Minor UI inconsistency
- Non-blocking bug

Response priority:
- Medium/low priority based on visibility.

Documentation requirement:
- Update docs only if behavior/contracts/usage notes changed.

Test depth:
- Targeted validation on touched surface.
- Full regression protocol optional.


---

## Source: `docs/50-ops/maintenance-decision-matrix.md`

# Maintenance Decision Matrix

## 1. Purpose
This matrix prevents random fixes.

Every maintenance action must pass through a decision filter before implementation. The filter determines impact scope, required validation depth, documentation obligations, and release implications.

## 2. Decision Tree

### A) Issue Nature
Classify the issue first:
- UI only
- Business logic
- Integration
- Security
- Architectural

### B) Impact Surface
Then classify impact scope:
- Checkout
- Payments
- Auth
- External APIs
- Multiple domains

### C) Change Origin
Then classify incident origin:
- Regression
- New bug
- Side effect of refactor
- External provider change

### Decision Flow
1. Identify issue nature (A).
2. Map affected domain(s) (B).
3. Determine origin type (C).
4. Assign incident level using `incident-classification.md`.
5. Apply required actions from the matrix table.
6. Execute validation and documentation updates before closure.

## 3. Required Actions Table

| Incident Type | Update `CHANGELOG.md` | Update feature doc | Update runbook | Create ADR | Run full regression | Run domain regression only |
|---|---|---|---|---|---|---|
| UI only (minor, Level 3) | No* | No* | No | No | No | Yes |
| UI regression (Level 2) | Yes | Yes | Yes | No | Yes | No |
| Business logic bug (single domain) | Yes | Yes | Yes | No | Yes | No |
| Integration failure (payments/auth/API) | Yes | Yes | Yes | Maybe** | Yes | No |
| Security issue | Yes | Yes | Yes | Yes** | Yes | No |
| Architectural issue/change | Yes | Yes | Yes | Yes | Yes | No |
| Refactor side effect | Yes | Yes | Yes | Maybe** | Yes | No |
| External provider change impact | Yes | Yes | Yes | Maybe** | Yes | No |

Notes:
- `No*`: becomes `Yes` if user-visible behavior, constraints, or troubleshooting guidance changed.
- `Maybe**`: mandatory if contracts, system boundaries, or decision-level architecture changed.

## 4. Release Blocking Rules
- Level 1 issues block release.
- Integration failures block release.
- Security issues always block release.
- Minor UI issues (Level 3 cosmetic) do not block release.

## 5. Documentation Discipline
No maintenance task is complete until all three conditions are true:
- Regression passed.
- Documentation updated at required level.
- Decision recorded (incident type + chosen action path).


---

## Source: `docs/50-ops/maintenance-framework.md`

# Maintenance Framework

## Scope
In Blackwork, maintenance is the discipline of keeping the system stable, secure, and coherent while preserving architectural intent.

## Maintenance Categories

### Bug Fix
Correction of behavior that deviates from expected domain documentation.

### Refactor
Internal improvement of structure/readability/performance without changing documented behavior.

### Security Patch
Targeted change that reduces or removes a security risk (validation, authorization, sanitization, exposure).

### UX Regression
Fix for degraded user interaction introduced by recent changes (flows, feedback, consistency, accessibility).

### Integration Break
Recovery of failing contracts between Blackwork and external services (payments, auth, Brevo, Supabase).

## Operating Principles
- Maintenance must preserve architecture and domain contracts.
- No feature modification is allowed without explicit domain alignment.
- Any maintenance outcome must be reflected in documentation updates.
- Historical continuity matters: operational knowledge must remain traceable.


---

## Source: `docs/50-ops/maintenance-workflow.md`

# Maintenance Workflow

## Official Sequence
1. Identify domain (`feature` / `integration` / `architecture`).
2. Read related documentation before touching code.
3. Define impact scope (files, runtime surfaces, dependent integrations).
4. Implement the fix/refactor/patch.
5. Run the regression protocol.
6. Update documentation and memory:
   - `CHANGELOG.md`
   - Relevant domain `README.md`
   - ADR if architectural decision changed

## Mandatory Rule
No direct fix is allowed without first reading related documentation for the impacted domain.

## Output Expectation
Each maintenance task should leave:
- a clear implementation delta,
- validated runtime behavior,
- synchronized documentation state.


---

## Source: `docs/50-ops/regression-coverage-map.md`

# Regression Coverage Map

## 1) Purpose
This document defines which regression journeys protect which governance invariants.
It is the operational link between governance contracts and release validation.

Scope:
- map invariant -> concrete regression journey
- map journey -> protected risk IDs
- identify explicit coverage gaps
- define minimum release gate for Tier 0 safety

## 2) Invariant Coverage Table
| Invariant | Source Document | Covered By Test Journey | Risk ID Protected | Coverage Level (Full/Partial/Gap) |
|---|---|---|---|---|
| Payment authority invariant | `system-normative-charter.md` | R-01, R-02, R-03, R-04 | R-PAY-08, R-CHK-01 | Partial |
| Webhook idempotency invariant | `callback-contracts.md` | R-04, R-01 | R-PAY-08 | Partial |
| Checkout selector determinism | `blast-radius-consolidation-map.md`, checkout audit | R-07, R-01, R-02 | R-CHK-01, R-PAY-02, R-PAY-03 | Partial |
| Callback convergence invariant | `callback-contracts.md`, `system-state-matrix.md` | R-05, R-06 | R-AUTH-04 | Partial |
| Auth/session authority boundary | `cross-domain-state-dictionary.md`, `system-state-matrix.md` | R-05, R-06, R-02 | R-AUTH-04, R-AUTH-05 | Partial |
| Onboarding marker semantics | `system-state-matrix.md`, My Account audit | R-05, R-02 | R-AUTH-05 | Partial |
| Guest claim idempotency | `system-state-matrix.md`, hardening plan | R-01, R-05 | R-SUPA-06 | Partial |
| Download entitlement invariant | `system-state-matrix.md`, My Account audit | R-01, R-02 | R-SUPA-06 | Partial |
| Consent local authority invariant | `system-normative-charter.md`, risk register | R-08, R-09 | R-BRE-09 | Full |
| Authority non-override rule | `system-normative-charter.md`, `callback-contracts.md` | R-01, R-04, R-05, R-08 | R-PAY-08, R-AUTH-04, R-BRE-09 | Partial |

## 3) Regression Test Journeys
### R-01 Guest checkout paid -> webhook -> claim -> downloads
- Validates invariants:
  - payment authority
  - claim idempotency
  - download entitlement
  - authority non-override
- Domains touched: Checkout, Payments, Supabase, My Account
- Failure signals:
  - paid order without accessible claimed downloads after onboarding
  - duplicate or missing claim linkage
  - UI indicates success but order/payment not converged

### R-02 Logged-in Supabase checkout paid
- Validates invariants:
  - payment authority
  - auth/session boundary
  - onboarding marker semantics
- Domains touched: Checkout, Payments, Auth/Supabase, My Account
- Failure signals:
  - paid order path diverges by onboarding marker unexpectedly
  - authenticated user routed into callback/loader loop
  - selected gateway differs from processed gateway

### R-03 Payment failure path
- Validates invariants:
  - payment authority
  - order lifecycle consistency
  - non-false-success UX
- Domains touched: Checkout, Payments
- Failure signals:
  - failed/canceled payment shown as successful order
  - order state converges incorrectly to processing/completed

### R-04 Webhook replay simulation
- Validates invariants:
  - webhook idempotency
  - authority non-override
- Domains touched: Payments, Order lifecycle
- Failure signals:
  - duplicate side effects/order transitions on replay
  - conflicting status transitions from repeated events

### R-05 Supabase invite -> OTP -> password -> onboard
- Validates invariants:
  - callback convergence
  - auth/session authority
  - onboarding marker semantics
  - guest claim idempotency
- Domains touched: Auth/Supabase, My Account, Orders/Downloads
- Failure signals:
  - callback loop or ghost loader
  - onboarding marker stuck/misaligned
  - claimed orders not attached after onboarding completion

### R-06 Email change flow
- Validates invariants:
  - auth/session boundary
  - pending email semantics
  - callback convergence
- Domains touched: My Account, Auth/Supabase
- Failure signals:
  - pending email banner never clears after confirmation
  - security tab redirect loops or stale callback params
  - local email state updates without confirmation path

### R-07 Fragment refresh stress (checkout)
- Validates invariants:
  - selector determinism
  - payment UI/submission coherence
- Domains touched: Checkout, Payments
- Failure signals:
  - double active gateway
  - selected radio and visible panel mismatch
  - wallet button visibility mismatched with eligibility

### R-08 Consent opt-in + Brevo sync
- Validates invariants:
  - consent local authority
  - non-blocking commerce
- Domains touched: Checkout, Brevo, Order hooks
- Failure signals:
  - opt-in not persisted locally
  - sync write missing despite valid consent and paid trigger
  - checkout/payment impacted by Brevo sync issue

### R-09 Consent no-opt-in
- Validates invariants:
  - consent local authority
  - no unauthorized remote write
- Domains touched: Checkout, Brevo
- Failure signals:
  - Brevo sync attempt executed without local consent
  - consent metadata contradictory (`no consent` + `synced`)

## 4) Coverage Gaps
Invariants not fully covered:
- webhook idempotency under mixed gateway/provider edge events is only partially covered (R-04 baseline replay only).
- authority non-override across simultaneous callback + payment return events lacks dedicated stress scenario.

Transitions not explicitly tested:
- payment confirmed while callback bridge is mid-resolution (high-risk transitional race).
- repeated callback invocations with stale query/hash in mixed logged-in/logged-out transitions.

Tier 0 surfaces without full coverage:
- UPE/custom selector coupling resilience to Stripe DOM version drift.
- callback anti-flash behavior under repeated hard refresh on callback URL.
- claim idempotency under repeated onboarding completion retries.

## 5) Release Gate Definition
Minimum journeys required before release:
- R-01
- R-02
- R-03
- R-04
- R-05
- R-07
- R-08
- R-09

Stop conditions:
- any Tier 0 journey fails
- callback convergence fails (loop/ghost loader/flash)
- payment/order authority divergence detected
- consent gate violated (remote sync without local consent)

Escalate to re-audit when:
- two or more failures occur in the same cross-domain transition family
- failures indicate invariant ambiguity instead of implementation bug
- new behavior touches authority boundaries not mapped in current state matrix

## 6) References
- [System Normative Charter](../00-governance/system-normative-charter.md)
- [Unified Callback Contracts](../00-governance/callback-contracts.md)
- [Blast-Radius Consolidation Map](../00-governance/blast-radius-consolidation-map.md)
- [Risk Register](../00-governance/risk-register.md)
- [System State Matrix](../00-governance/system-state-matrix.md)
- [Checkout Payment Selector Audit](../50-ops/audits/checkout-payment-selector-audit.md)
- [My Account Domain Audit](../50-ops/audits/my-account-domain-audit.md)


---

## Source: `docs/50-ops/regression-protocol.md`

# Regression Protocol

## Mandatory Checks
After any maintenance task, validate:
- Checkout test
- Payment gateway test
- Auth test
- Cart popup test
- Header behavior test
- My Account test
- CSS regression scan
- Console errors scan

## Applicability by Incident Level
- Level 1: mandatory
- Level 2: mandatory
- Level 3: recommended based on impact

## Execution Notes
- Run targeted checks first for the impacted domain.
- Then run cross-domain sanity checks to detect side effects.
- Record anomalies and link them to incident level and affected domain docs.


---

## Source: `docs/50-ops/release-gate.md`

# Blackwork Release Gate

## Purpose
The Release Gate is the final operational validation stage before production deployment.

It ensures that:
- runtime stability
- governance compliance
- rollback readiness

are explicitly verified before shipping.

## When it runs
The Release Gate runs after task closure and before deployment.

## Mandatory Checks

1. PHP syntax check

Run `php -l` on all modified PHP files.

2. Project lint

Run `composer run lint:main`.

3. Checkout smoke test

Verify:
- checkout loads
- payment method selector works
- place order button works

4. Webhook replay safety

Verify duplicate webhook delivery does not mutate order state twice.

5. Redirect engine sanity

Verify protected routes are not redirectable.

6. Admin settings save flow

Verify admin settings save successfully with valid nonce and fail with invalid nonce.

7. Media library smoke test

Verify media modal and admin media list still function.

8. Documentation alignment

Verify:
- risk register updated if needed
- task closure artifact present
- docs reflect behavior changes

9. Rollback readiness

Verify a revert path is documented.

10. Task lifecycle compliance

Verify:
- Task Start Template exists
- Task Closure Template exists
- determinism verification is present


---

## Source: `docs/50-ops/runbooks/README.md`

# Runbooks

Runbooks are operational maintenance guides.

They differ from feature documentation:
- Feature docs describe implementation and behavior contracts.
- Runbooks describe how to safely maintain, diagnose, and verify domains in production-like conditions.

Runbooks are not architecture guides. They translate architecture and feature contracts into repeatable operational procedures.

Available runbooks:
- [Checkout](checkout-runbook.md)
- [Payments](payments-runbook.md)
- [Auth](auth-runbook.md)
- [Supabase](supabase-runbook.md)
- [Brevo](brevo-runbook.md)
- [Header](header-runbook.md)


---

## Source: `docs/50-ops/runbooks/auth-runbook.md`

# Auth Runbook

## 1. Domain Scope
Includes account access flows and provider logic across WordPress auth, Supabase auth, and social login coupling.

Related folders:
- `docs/40-integrations/auth/`
- `docs/40-integrations/supabase/`
- `docs/30-features/my-account/`

Related docs:
- `../../40-integrations/auth/social-login-setup-guide.md`
- `../../40-integrations/supabase/supabase-create-password.md`
- `../../30-features/my-account/my-account-complete-guide.md`
- `../regression-protocol.md`

## 2. Critical Risk Points
- Provider mismatch (WordPress vs Supabase) causing wrong gate behavior.
- Login state drift between frontend and backend session assumptions.
- Social login callback/config mismatches.
- Account page UX regressions around auth gating.

High-risk integrations and dependencies:
- Supabase token bridge + onboarding flow.
- WordPress session and WooCommerce account rendering.
- OAuth provider app settings and callback URLs.

## 3. Pre-Maintenance Checklist
- Read auth/supabase/my-account docs first.
- Confirm active provider mode and expected branching behavior.
- Identify fragile areas: create-password gating, callback handling, session transitions.

## 4. Safe Fix Protocol
- Keep provider-specific behavior isolated and explicit.
- Avoid mixed assumptions across WordPress and Supabase paths.
- Do not alter callback contracts without integration review.
- ADR required when auth provider strategy or cross-provider architecture changes.

## 5. Regression Checklist (Domain Specific)
- Validate login/logout cycle for active provider.
- Validate social login button/callback flow.
- Validate provider-specific account access rules.
- Validate onboarding/password gate behavior where applicable.
- Scan console and server logs for auth callback/session errors.

## 6. Documentation Update Requirements
- Update `CHANGELOG.md` for auth behavior changes.
- Update auth/supabase/my-account docs when flows or constraints change.
- Update ADR for provider model or auth architecture changes.


---

## Source: `docs/50-ops/runbooks/brevo-runbook.md`

# Brevo Runbook

## 1. Domain Scope
Includes newsletter subscription capture, consent-driven sync, Brevo API coupling, and admin diagnostics/reporting for marketing flows.

Related folders:
- `docs/40-integrations/brevo/`
- `docs/30-features/checkout/`

Related docs:
- `../../40-integrations/brevo/brevo-mail-marketing-architecture.md`
- `../../40-integrations/brevo/subscribe.md`
- `../../40-integrations/brevo/mail-marketing-qa-checklist.md`
- `../regression-protocol.md`

## 2. Critical Risk Points
- Consent capture not persisted correctly at checkout.
- API failures causing silent contact sync gaps.
- Status mapping inconsistencies (subscribed/unsubscribed/pending).
- Admin UI diagnostics diverging from actual runtime state.

High-risk integrations and dependencies:
- Brevo API credentials/list/attribute mapping.
- Checkout field injection and order meta persistence.
- Retry/resync logic.

## 3. Pre-Maintenance Checklist
- Read Brevo architecture and governance docs.
- Verify expected consent model (single vs double opt-in).
- Identify fragile areas: meta keys, sync trigger conditions, retry logic.

## 4. Safe Fix Protocol
- Preserve consent and compliance semantics.
- Keep metadata keys and mapping changes explicit and documented.
- Do not alter sync state machine without review.
- ADR required if Brevo integration architecture/state model changes.

## 5. Regression Checklist (Domain Specific)
- Validate checkout consent field display and persistence.
- Validate sync behavior for opted-in and non-opted-in flows.
- Validate duplicate email and unsubscribed handling.
- Validate admin diagnostics (order/user panels and filters).
- Scan console/log output and API responses for errors.

## 6. Documentation Update Requirements
- Update `CHANGELOG.md` for Brevo runtime/consent/sync changes.
- Update Brevo architecture/subscribe/checklist docs when behavior changes.
- Update ADR for structural changes to Brevo integration model.


---

## Source: `docs/50-ops/runbooks/checkout-runbook.md`

# Checkout Runbook

## 1. Domain Scope
Includes checkout templates, checkout UI behavior, coupon flow, order review interactions, and checkout-specific maintenance guidance.

Related folders:
- `docs/30-features/checkout/`
- `docs/40-integrations/payments/`

Related docs:
- `../../30-features/checkout/complete-guide.md`
- `../../30-features/checkout/maintenance-guide.md`
- `../../40-integrations/payments/payments-overview.md`
- `../regression-protocol.md`

## 2. Critical Risk Points
- Checkout rendering break due to template/CSS coupling.
- Coupon and totals desynchronization after AJAX updates.
- Payment section state drift (selected method vs visible action).
- Layout regressions at responsive breakpoints.

High-risk integrations and dependencies:
- WooCommerce checkout lifecycle (`updated_checkout`, notices, totals).
- Payments orchestration and gateway UI sync.
- Cart popup interactions that affect cart/checkout continuity.

## 3. Pre-Maintenance Checklist
- Read checkout guides and payments overview first.
- Identify fragile areas: coupon logic, totals, payment accordion, responsive layout.
- Map related integrations: payments gateways, auth handoff after purchase, analytics/event hooks if present.

## 4. Safe Fix Protocol
- Apply minimal, domain-scoped changes.
- Preserve documented checkout contract and WooCommerce flow.
- Do not alter payment completion semantics without payments review.
- Do not rewrite template structure broadly without regression plan.
- ADR required if checkout architecture/contracts are changed.

## 5. Regression Checklist (Domain Specific)
- Validate desktop/mobile checkout rendering.
- Validate billing/shipping input behavior and validation notices.
- Validate coupon apply/remove and totals recalculation.
- Validate payment method selection and CTA visibility consistency.
- Validate place-order and redirect/thank-you path.
- Scan browser console for JS errors/warnings on checkout interactions.

## 6. Documentation Update Requirements
- Update `CHANGELOG.md` for every maintenance change affecting behavior.
- Update checkout domain docs when behavior, constraints, or troubleshooting notes change.
- Update ADR when checkout architecture or contracts are modified.


---

## Source: `docs/50-ops/runbooks/header-runbook.md`

# Header Runbook

## 1. Domain Scope
Includes custom header rendering, navigation/search/navshop interactions, and runtime behavior in desktop/mobile contexts.

Related folders:
- `docs/30-features/header/`
- `docs/30-features/navigation/`
- `docs/30-features/smart-header/`

Related docs:
- `../../30-features/header/custom-header-architecture.md`
- `../../30-features/navigation/custom-navigation.md`
- `../../30-features/smart-header/smart-header-guide.md`
- `../regression-protocol.md`

## 2. Critical Risk Points
- Mobile drawer interaction regressions (open/close/ESC/overlay).
- Search overlay break due to DOM move/clipping assumptions.
- Cart/account trigger regressions in navshop.
- Smart header behavior drift during scroll/responsive transitions.

High-risk integrations and dependencies:
- Header module frontend assets and selectors.
- Cart popup coupling from header cart trigger.
- Woo fragments for cart count updates.

## 3. Pre-Maintenance Checklist
- Read header/navigation/smart-header docs first.
- Confirm expected selector contracts and behavior states.
- Identify fragile areas: mobile breakpoints, overlay behavior, cart popup trigger.

## 4. Safe Fix Protocol
- Preserve CSS class/selector contracts used by module JS.
- Isolate visual fixes from behavior logic when possible.
- Do not rename core selectors without full review.
- ADR required for header architecture contract changes.

## 5. Regression Checklist (Domain Specific)
- Test desktop header rendering and interactions.
- Test mobile navigation open/close and escape paths.
- Test search overlay open/close and live-search behavior.
- Test navshop cart/account actions and cart count updates.
- Test smart-header scroll behavior and dark/light transitions.
- Scan console for JS errors on page load and interactions.

## 6. Documentation Update Requirements
- Update `CHANGELOG.md` for behavior-impacting header changes.
- Update header/navigation/smart-header docs when contracts or expected behavior change.
- Update ADR for structural module contract changes.


---

## Source: `docs/50-ops/runbooks/payments-runbook.md`

# Payments Runbook

## 1. Domain Scope
Includes gateway orchestration, payment UI state, Stripe-based flows, webhook-driven completion, and custom gateway behavior.

Related folders:
- `docs/40-integrations/payments/`
- `docs/30-features/checkout/`

Related docs:
- `../../40-integrations/payments/payments-overview.md`
- `../../40-integrations/payments/gateway-google-pay-guide.md`
- `../../40-integrations/payments/gateway-apple-pay-guide.md`
- `../../40-integrations/payments/gateway-klarna-guide.md`
- `../../40-integrations/payments/payment-test-checklist.md`
- `../regression-protocol.md`

## 2. Critical Risk Points
- Stripe webhook mismatch or missed events causing order state drift.
- Express checkout controls not aligned with selected method.
- Fallback logic failure when wallet method is unavailable.
- Cross-gateway side effects due to weak ownership checks.

High-risk integrations and dependencies:
- Stripe PaymentIntent and webhook contracts.
- WooCommerce order status transitions.
- Frontend method orchestration scripts.

## 3. Pre-Maintenance Checklist
- Read payments overview and gateway-specific docs.
- Verify current webhook assumptions and idempotency rules.
- Identify fragile areas: method ownership checks, fallback paths, return vs webhook timing.

## 4. Safe Fix Protocol
- Preserve webhook-first payment completion model.
- Keep gateway boundaries strict; avoid cross-mutation of orders.
- Keep fallback behavior explicit and testable.
- Do not change gateway state machine without explicit review.
- ADR required when payment architecture, webhook strategy, or status model changes.

## 5. Regression Checklist (Domain Specific)
- Test successful flow for each enabled custom gateway.
- Test failed/cancelled flow and retry behavior.
- Test express checkout logic visibility and action sync.
- Test gateway fallback logic when wallet method is unavailable.
- Confirm webhook processing updates order status correctly.
- Scan console/network for payment-related JS/API errors.

## 6. Documentation Update Requirements
- Update `CHANGELOG.md` for any payment flow or gateway behavior change.
- Update affected gateway docs and payments overview.
- Update ADR when payment contracts, webhook model, or fallback strategy changes.


---

## Source: `docs/50-ops/runbooks/supabase-runbook.md`

# Supabase Runbook

## 1. Domain Scope
Includes Supabase-linked account provisioning, callback bridging, create-password lifecycle, and related audits.

Related folders:
- `docs/40-integrations/supabase/`
- `docs/40-integrations/supabase/audits/`
- `docs/40-integrations/auth/`

Related docs:
- `../../40-integrations/supabase/supabase-create-password.md`
- `../../40-integrations/supabase/audits/README.md`
- `../../40-integrations/supabase/audits/2026-02-13-supabase-create-password-audit.md`
- `../../40-integrations/auth/social-login-setup-guide.md`
- `../regression-protocol.md`

## 2. Critical Risk Points
- Broken token bridge between Supabase callback and WP session.
- Password flow regressions (modal gating, invite link handling, expired links).
- Inconsistent policy validation between UI and backend.
- Onboarding state stuck or bypassed.

High-risk integrations and dependencies:
- Supabase invite/confirmation templates.
- Account callback handlers and AJAX endpoints.
- My Account template rendering conditions.

## 3. Pre-Maintenance Checklist
- Read Supabase canonical doc and latest audit.
- Confirm expected provider mode and flow entry points.
- Identify fragile areas: invite callback, create-password modal, post-checkout onboarding.

## 4. Safe Fix Protocol
- Preserve stable baseline rules defined in Supabase docs.
- Apply incremental changes with focused verification.
- Do not modify callback URLs/contracts casually.
- ADR required for structural changes in Supabase auth architecture.

## 5. Regression Checklist (Domain Specific)
- Test new guest purchase flow with Supabase onboarding.
- Test logged-in safety path (no incorrect gating).
- Test expired/invalid invite behavior.
- Test password policy validation consistency UI/backend.
- Check browser console and `debug.log` for Supabase/auth errors.

## 6. Documentation Update Requirements
- Update `CHANGELOG.md` for flow-affecting Supabase changes.
- Update Supabase main doc and audits when findings/fixes evolve.
- Update ADR when Supabase architectural decisions change.


---

## Source: `docs/50-ops/runtime-hook-map.md`

# Runtime Hook Map

## 1) Purpose and usage rules
This document is the single runtime hook inventory for Blackwork Core (WordPress + WooCommerce execution layer).

Usage rules:
- This map is operational and governance-facing: use it before changing hook priority, callback registration, or hook lifecycle behavior.
- Tier 0-sensitive hooks MUST be reviewed against governance docs before modification.
- Priority changes on listed Tier 0 hooks MUST be treated as regression-sensitive changes.
- If a callback is conditionally registered, that condition is part of the contract.
- If any runtime registration is not provable from current code inspection, it is marked `unknown` with lookup path.

Scope note:
- This map focuses on runtime/frontend and Woo runtime paths.
- Admin-only hooks are intentionally excluded unless they affect runtime coupling.

## 2) Hook inventory
| Hook | Priority | Callback function/method | File path | Domain owner | Tier (0 / 1 / 2) | Authority Surface (Yes / No) | Notes |
|---|---:|---|---|---|---:|---|---|
| `plugins_loaded` | 10 (default) | `bw_mew_initialize_woocommerce_overrides` | `woocommerce/woocommerce-init.php` | Checkout/Payments/Auth | 1 | No | Runtime bootstrap for Woo overrides and multiple downstream hook registrations. |
| `template_redirect` | **5** | `bw_maybe_redirect_request` | `includes/class-bw-redirects.php` | Redirect | 0 | Yes | Tier 0 redirect engine entrypoint; runs early and may short-circuit request with `wp_safe_redirect`. |
| `template_redirect` | 10 (default) | `bw_show_coming_soon` | `BW_coming_soon/includes/functions.php` | Coming Soon | 1 | No | Runtime page-gating surface; priority interplay with redirect/cart/checkout paths. |
| `template_redirect` | 5 | `BW_Social_Login::handle_requests` | `includes/woocommerce-overrides/class-bw-social-login.php` | Auth | 0 | Yes | Social login callback/redirect handling, early in redirect stack. |
| `template_redirect` | 6 | `bw_mew_force_auth_callback_for_guest_transitions` | `includes/woocommerce-overrides/class-bw-my-account.php` | My Account/Supabase | 0 | Yes | Guest->callback enforcement path. |
| `template_redirect` | 7 | `bw_mew_cleanup_logged_in_auth_callback_query` | `includes/woocommerce-overrides/class-bw-my-account.php` | My Account/Supabase | 0 | Yes | Stale callback query cleanup. |
| `template_redirect` | 8 | `bw_mew_prepare_theme_title_bypass` | `woocommerce/woocommerce-init.php` | Checkout/My Account UI | 1 | No | Title bypass setup before layout prep. |
| `template_redirect` | 9 | `bw_mew_prepare_account_page_layout` | `woocommerce/woocommerce-init.php` | My Account | 1 | No | Layout wiring for account pages. |
| `template_redirect` | 9 | `bw_mew_prepare_checkout_layout` | `woocommerce/woocommerce-init.php` | Checkout | 1 | No | Layout wiring for checkout pages. |
| `template_redirect` | 9 | `bw_mew_prepare_cart_layout` | `woocommerce/woocommerce-init.php` | Cart | 1 | No | Layout wiring for cart pages. |
| `template_redirect` | 9 | `bw_mew_hide_single_product_notices` | `woocommerce/woocommerce-init.php` | Product UX | 1 | No | Notice suppression in product flow. |
| `template_redirect` | 9 | `bw_mew_hide_logged_in_account_title` | `woocommerce/woocommerce-init.php` | My Account | 1 | No | Account title suppression. |
| `template_redirect` | 1 | `bw_mew_prevent_order_received_cache` | `woocommerce/woocommerce-init.php` | Checkout/Payments | 0 | No | Early order-received cache-control logic. |
| `template_redirect` | 2 | `bw_mew_handle_wallet_failed_return_redirect` | `woocommerce/woocommerce-init.php` | Payments/Checkout | 0 | Yes | Early failed wallet return handling. |
| `template_redirect` | 11 | `bw_mew_sync_profile_names_from_purchase_data` | `includes/woocommerce-overrides/class-bw-my-account.php` | My Account | 1 | No | Runtime profile sync from order data. |
| `template_redirect` | 11 | `bw_mew_sync_account_address_fields_from_latest_order` | `includes/woocommerce-overrides/class-bw-my-account.php` | My Account | 1 | No | Runtime address sync from latest order. |
| `template_redirect` | 12 | `bw_mew_handle_profile_update` | `includes/woocommerce-overrides/class-bw-my-account.php` | My Account | 1 | No | Profile update POST handling. |
| `template_redirect` | 15 | `bw_mew_normalize_guest_email_entrypoint` | `includes/woocommerce-overrides/class-bw-my-account.php` | My Account/Supabase | 0 | Yes | Entry normalization for guest email paths. |
| `template_redirect` | 20 | `bw_mew_handle_email_entrypoint_redirect` | `includes/woocommerce-overrides/class-bw-my-account.php` | My Account/Supabase | 0 | Yes | Endpoint redirect continuation. |
| `template_redirect` | 25 | `bw_mew_redirect_order_verify_email_for_supabase` | `includes/woocommerce-overrides/class-bw-my-account.php` | My Account/Supabase | 0 | Yes | Verify-email redirect gating. |
| `wp_body_open` | **5** | `bw_header_render_frontend` | `includes/modules/header/frontend/header-render.php` | Header | 0 | Yes | Tier 0 global header injection point. |
| `wp` | 1 | `bw_header_disable_theme_header` | `includes/modules/header/frontend/header-render.php` | Header | 1 | No | Removes theme header renderer for custom header mode. |
| `wp_head` | 99 | `bw_header_theme_header_fallback_css` | `includes/modules/header/frontend/header-render.php` | Header | 1 | No | Fallback CSS to hide theme header. |
| `wp_head` | 1 | `bw_mew_supabase_early_invite_redirect_hint` | `woocommerce/woocommerce-init.php` | Supabase/Auth | 1 | No | Early callback/invite hinting path. |
| `wp_enqueue_scripts` | 20 | `bw_header_enqueue_assets` | `includes/modules/header/frontend/assets.php` | Header | 2 | No | Frontend header JS/CSS payload. |
| `woocommerce_add_to_cart_fragments` | 10 (default) | `bw_header_cart_count_fragment` | `includes/modules/header/frontend/fragments.php` | Header/Woo | 1 | No | Fragment-based cart badge sync for header. |
| `wp_ajax_bw_live_search_products` | 10 (default) | `bw_header_live_search_products` | `includes/modules/header/frontend/ajax-search.php` | Header | 1 | No | Header live search AJAX endpoint. |
| `wp_ajax_nopriv_bw_live_search_products` | 10 (default) | `bw_header_live_search_products` | `includes/modules/header/frontend/ajax-search.php` | Header | 1 | No | Guest live search endpoint. |
| `wp_enqueue_scripts` | 10 (default) | `bw_cart_popup_register_assets` | `cart-popup/cart-popup.php` | Cart Popup | 2 | No | Registers cart popup assets + localized config. |
| `wp_enqueue_scripts` | 20 | `bw_cart_popup_enqueue_assets` | `cart-popup/cart-popup.php` | Cart Popup | 2 | No | Enqueues cart popup runtime assets. |
| `woocommerce_loop_add_to_cart_link` | 10 | `bw_cart_popup_hide_view_cart_button` | `cart-popup/cart-popup.php` | Cart Popup | 1 | No | Suppresses View Cart loop behavior coupling. |
| `wp_head` | 10 (default) | `bw_cart_popup_hide_view_cart_css` | `cart-popup/cart-popup.php` | Cart Popup | 1 | No | CSS fallback for Woo View Cart link hiding. |
| `wp_footer` | 10 (default) | `bw_cart_popup_render_panel` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | 1 | No | Renders popup panel DOM in frontend. |
| `wp_head` | 10 (default) | `bw_cart_popup_dynamic_css` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | 1 | No | Runtime CSS vars/styles for popup config. |
| `woocommerce_add_to_cart_fragments` | 10 (default) | `bw_cart_popup_update_fragments` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | 1 | No | Cart popup fragment response extension. |
| `wp_ajax_bw_cart_popup_add_to_cart` | 10 (default) | `bw_cart_popup_ajax_add_to_cart` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | 1 | No | Popup add-to-cart action. |
| `wp_ajax_nopriv_bw_cart_popup_add_to_cart` | 10 (default) | `bw_cart_popup_ajax_add_to_cart` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | 1 | No | Guest popup add-to-cart action. |
| `wp_ajax_bw_cart_popup_get_contents` | 10 (default) | `bw_cart_popup_get_cart_contents` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | 1 | No | Popup content refresh endpoint. |
| `wp_ajax_nopriv_bw_cart_popup_get_contents` | 10 (default) | `bw_cart_popup_get_cart_contents` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | 1 | No | Guest popup content refresh endpoint. |
| `wp_ajax_bw_cart_popup_remove_item` | 10 (default) | `bw_cart_popup_remove_item` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | 1 | No | Remove cart line via popup. |
| `wp_ajax_nopriv_bw_cart_popup_remove_item` | 10 (default) | `bw_cart_popup_remove_item` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | 1 | No | Guest remove cart line via popup. |
| `wp_ajax_bw_cart_popup_update_quantity` | 10 (default) | `bw_cart_popup_update_quantity` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | 1 | No | Quantity update via popup. |
| `wp_ajax_nopriv_bw_cart_popup_update_quantity` | 10 (default) | `bw_cart_popup_update_quantity` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | 1 | No | Guest quantity update via popup. |
| `wp_ajax_bw_cart_popup_apply_coupon` | 10 (default) | `bw_cart_popup_apply_coupon` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | 1 | No | Coupon apply via popup. |
| `wp_ajax_nopriv_bw_cart_popup_apply_coupon` | 10 (default) | `bw_cart_popup_apply_coupon` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | 1 | No | Guest coupon apply via popup. |
| `wp_ajax_bw_cart_popup_remove_coupon` | 10 (default) | `bw_cart_popup_remove_coupon` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | 1 | No | Coupon removal via popup. |
| `wp_ajax_nopriv_bw_cart_popup_remove_coupon` | 10 (default) | `bw_cart_popup_remove_coupon` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | 1 | No | Guest coupon removal via popup. |
| `woocommerce_locate_template` | 1 | `bw_mew_locate_template` | `woocommerce/woocommerce-init.php` | Checkout/My Account | 1 | No | Template override resolver (plugin templates first). |
| `woocommerce_locate_core_template` | 1 | `bw_mew_locate_template` | `woocommerce/woocommerce-init.php` | Checkout/My Account | 1 | No | Core template override resolver. |
| `wp_enqueue_scripts` | 20 | `bw_mew_enqueue_checkout_assets` | `woocommerce/woocommerce-init.php` | Checkout/Payments | 2 | No | Checkout runtime JS/CSS bundle. |
| `wp_enqueue_scripts` | 20 | `bw_mew_enqueue_cart_assets` | `woocommerce/woocommerce-init.php` | Cart | 2 | No | Cart runtime asset bundle. |
| `wp_enqueue_scripts` | 20 | `bw_mew_enqueue_account_page_assets` | `woocommerce/woocommerce-init.php` | My Account | 2 | No | Account runtime assets. |
| `wp_enqueue_scripts` | 20 | `bw_mew_enqueue_order_confirmation_assets` | `woocommerce/woocommerce-init.php` | Checkout/Orders | 2 | No | Order-received runtime assets. |
| `wp_enqueue_scripts` | 20 | `bw_mew_enqueue_supabase_bridge` | `woocommerce/woocommerce-init.php` | Supabase/Auth | 2 | No | Auth bridge frontend script enqueue. |
| `wp_enqueue_scripts` | 30 | `bw_mew_enqueue_related_products_assets` | `woocommerce/woocommerce-init.php` | Product UX | 2 | No | Related products frontend assets. |
| `woocommerce_checkout_update_order_review` | 10 | `bw_mew_sync_checkout_cart_quantities` | `woocommerce/woocommerce-init.php` | Checkout/Cart | 0 | No | Sync quantities during checkout order review update. |
| `woocommerce_checkout_posted_data` | 20 | `bw_mew_sync_billing_from_shipping_mode` | `woocommerce/woocommerce-init.php` | Checkout | 1 | No | Posted-data normalization path. |
| `woocommerce_available_payment_gateways` | 10 (default) | `bw_mew_hide_paypal_advanced_card_processing` | `woocommerce/woocommerce-init.php` | Payments | 1 | No | Gateway availability filter. |
| `wc_stripe_upe_params` | 10 (default) | `bw_mew_customize_stripe_upe_appearance` | `woocommerce/woocommerce-init.php` | Payments | 1 | No | Stripe UPE params appearance adaptation. |
| `woocommerce_payment_gateways` | 10 (default) | `bw_mew_add_google_pay_gateway` | `woocommerce/woocommerce-init.php` | Payments | 1 | No | Registers custom payment gateway. |
| `wp_ajax_bw_apply_coupon` | 10 (default) | `bw_mew_ajax_apply_coupon` | `woocommerce/woocommerce-init.php` | Checkout | 1 | No | Checkout coupon apply endpoint. |
| `wp_ajax_nopriv_bw_apply_coupon` | 10 (default) | `bw_mew_ajax_apply_coupon` | `woocommerce/woocommerce-init.php` | Checkout | 1 | No | Guest checkout coupon apply endpoint. |
| `wp_ajax_bw_remove_coupon` | 10 (default) | `bw_mew_ajax_remove_coupon` | `woocommerce/woocommerce-init.php` | Checkout | 1 | No | Checkout coupon remove endpoint. |
| `wp_ajax_nopriv_bw_remove_coupon` | 10 (default) | `bw_mew_ajax_remove_coupon` | `woocommerce/woocommerce-init.php` | Checkout | 1 | No | Guest checkout coupon remove endpoint. |
| `woocommerce_order_status_processing` | 10 | `bw_mew_handle_supabase_checkout_invite` | `includes/woocommerce-overrides/class-bw-supabase-auth.php` | Supabase/Checkout | 0 | Yes | Invite/provisioning trigger on paid-like state. |
| `woocommerce_order_status_completed` | 10 | `bw_mew_handle_supabase_checkout_invite` | `includes/woocommerce-overrides/class-bw-supabase-auth.php` | Supabase/Checkout | 0 | Yes | Invite/provisioning trigger on completed state. |
| `woocommerce_order_status_on-hold` | 10 | `bw_mew_handle_supabase_checkout_invite` | `includes/woocommerce-overrides/class-bw-supabase-auth.php` | Supabase/Checkout | 0 | Yes | Invite trigger on on-hold state. |
| `woocommerce_payment_complete` | 10 | `bw_mew_handle_supabase_checkout_invite` | `includes/woocommerce-overrides/class-bw-supabase-auth.php` | Supabase/Checkout | 0 | Yes | Invite trigger on payment completion. |
| `woocommerce_thankyou` | 20 | `bw_mew_handle_supabase_checkout_invite` | `includes/woocommerce-overrides/class-bw-supabase-auth.php` | Supabase/Checkout | 0 | Yes | Post-order fallback trigger. |
| `woocommerce_order_status_processing` | 10 (default) | `BW_Checkout_Subscribe_Frontend::maybe_subscribe_on_paid` | `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php` | Brevo/Checkout | 1 | No | Subscribe timing coupling to paid states. |
| `woocommerce_order_status_completed` | 10 (default) | `BW_Checkout_Subscribe_Frontend::maybe_subscribe_on_paid` | `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php` | Brevo/Checkout | 1 | No | Subscribe timing coupling to paid states. |
| `woocommerce_checkout_create_order` | 10 | `BW_Checkout_Subscribe_Frontend::save_consent_meta_on_create_order` | `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php` | Brevo/Checkout | 1 | No | Consent metadata capture at order creation. |
| `woocommerce_checkout_update_order_meta` | 10 | `BW_Checkout_Subscribe_Frontend::save_consent_meta` | `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php` | Brevo/Checkout | 1 | No | Consent metadata persistence path. |
| `woocommerce_checkout_order_processed` | 20 | `BW_Checkout_Subscribe_Frontend::maybe_subscribe_on_created` | `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php` | Brevo/Checkout | 1 | No | Optional subscribe timing at order processed. |

## 3) Special attention

### 3.1 `template_redirect` usage (Redirect + Checkout/Auth stack)
`template_redirect` is a collision-prone Tier 0 surface in this codebase.

Critical ordering currently observed:
- Priority 1: `bw_mew_prevent_order_received_cache`
- Priority 2: `bw_mew_handle_wallet_failed_return_redirect`
- Priority 5: `bw_maybe_redirect_request` (Redirect Engine)
- Priority 5: `BW_Social_Login::handle_requests`
- Priority 6/7: My Account callback guards
- Priority 8/9+: layout and UI-prep callbacks
- Priority 15/20/25: email-entrypoint normalization/redirect flows

Governance note:
- Any priority change in this stack can alter redirect authority, callback convergence, and checkout return behavior.

### 3.2 `wp_body_open` injection (Header)
Global header runtime injection is attached to:
- Hook: `wp_body_open`
- Priority: `5`
- Callback: `bw_header_render_frontend`
- File: `includes/modules/header/frontend/header-render.php`

Why sensitive:
- It defines top-level header render lifecycle across pages.
- Coupled with theme-header suppression (`wp`/`wp_head` callbacks) and Woo cart fragment badge updates.

### 3.3 Woo fragment related hooks/events (Cart Popup + Header)
PHP hooks:
- `woocommerce_add_to_cart_fragments` -> `bw_header_cart_count_fragment` (`includes/modules/header/frontend/fragments.php`)
- `woocommerce_add_to_cart_fragments` -> `bw_cart_popup_update_fragments` (`cart-popup/frontend/cart-popup-frontend.php`)

Frontend JS events (runtime lifecycle, not PHP hooks):
- `wc_fragment_refresh`
- `wc_fragments_refreshed`
- `updated_checkout`
- `updated_shipping_method`

Observed JS listeners include:
- `cart-popup/assets/js/bw-cart-popup.js`
- `assets/js/bw-checkout.js`
- `assets/js/bw-payment-methods.js`
- `assets/js/bw-google-pay.js`
- `assets/js/bw-apple-pay.js`
- `assets/js/bw-premium-loader.js`

Risk note:
- Fragment/event timing mismatches can create UI desync between cart count, checkout payment UI, and popup state.

## 4) Collision warnings

### Tier 0-sensitive hooks
The following hooks are Tier 0-sensitive and require strict change control:
- `template_redirect` (Redirect/Checkout/Auth/My Account convergence)
- `wp_body_open` (global Header injection)
- `woocommerce_checkout_update_order_review` (checkout recomputation path)
- `woocommerce_order_status_*` + `woocommerce_payment_complete` (Supabase/Brevo post-payment side-effects)

Normative controls for Tier 0 hooks:
- Tier 0 hook priority MUST NOT change without explicit reference to this document.
- Tier 0 hook priority modification REQUIRES ADR.
- Regression checklist execution REQUIRED before and after Tier 0 hook priority modification.
- Changing hook priority is equivalent to altering system behavior contract.

### Do not change priority notes
- `includes/class-bw-redirects.php` -> `add_action('template_redirect', 'bw_maybe_redirect_request', 5)`:
  - Priority MUST NOT change without redirect precedence re-validation.
- `includes/modules/header/frontend/header-render.php` -> `add_action('wp_body_open', 'bw_header_render_frontend', 5)`:
  - Priority MUST NOT change without header/theme override regression checks.
- `woocommerce/woocommerce-init.php` early callbacks (priorities `1` and `2`) and layout callbacks (`8/9`):
  - Priorities MUST NOT change without checkout return + wallet + account redirect regression suite.

## Runtime Governance Rule
- Runtime hook priority is part of the system behavioral contract.
- Tier 0 hooks are Frozen Authority Surfaces.
- Modifications are only allowed via roadmap-authorized implementation tasks.
- Any deviation MUST be documented through ADR.

## 5) Unknowns / where to look
- Some hook registrations occur inside class initialization paths (`::init()` / constructor registration). If runtime behavior differs from this map, verify bootstrap sequence in:
  - `blackwork-core-plugin.php`
  - `woocommerce/woocommerce-init.php`
  - `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php`
- Third-party plugin hook collisions (Rank Math/SEO/redirect plugins) are unknown from repository-only inspection. Inspect active plugin hook stack at runtime (`template_redirect`, `wp`, `wp_head`, Woo payment/order hooks).


---

## Source: `docs/60-adr/ADR-001-upe-vs-custom-selector.md`

# ADR-001: UPE vs Custom Selector Strategy

## Status
Accepted

This decision is binding and may only be altered through a superseding ADR.

## Context

The checkout payment surface combines two layers:

- Stripe UPE components (provider-driven UI/components).
- Blackwork custom selector (`woocommerce/templates/checkout/payment.php` + `assets/js/bw-payment-methods.js`) responsible for orchestration of visible payment state, fallback behavior, and submit-path determinism.

Architecture maps and governance artifacts identify this surface as Tier 0 with high blast radius. Payment truth, UI selection state, wallet visibility, fragment refresh behavior, and submit orchestration converge at this boundary.

Without explicit authority assignment, mixed rendering between UPE and custom selector can produce:

- Duplicated or conflicting payment controls
- Mismatch between visible selection and submitted gateway
- Non-deterministic checkout behavior after fragment refresh
- Instability in fallback and multi-gateway scenarios

## Decision

Blackwork formally establishes the following authority boundary:

- The **Custom Selector** is the sole orchestration authority for checkout payment selection and actionable visible state.
- **Stripe UPE is a provider component layer and MUST NOT assume orchestration authority.**
- UPE-rendered elements MUST remain compliant with selector contract and MUST NOT override selector-selected submission state.

Authoritative submission state is bound to:

- Active radio/payment method selection
- Synchronized UI visibility
- Deterministic submit payload coherence

No provider integration may supersede selector authority without a superseding ADR.

This decision aligns with the Authority Hierarchy doctrine (ADR-002).

## Alternatives Considered

### 1. UPE as full orchestration authority
Rejected.
Conflicts with current multi-gateway architecture and selector-based orchestration model. Would destabilize fallback logic and fragment refresh determinism.

### 2. Dual authority (UPE + Custom Selector)
Rejected.
Creates ambiguous source-of-truth and increases race-condition risk during fragment refresh and re-render cycles.

### 3. Coexistence without explicit authority definition
Rejected.
Fails governance-grade clarity and allows architectural drift.

## Consequences

- Payment UI governance remains deterministic under selector control.
- UPE integration work is compatibility-scoped, not authority-expanding.
- Suppression/cleanup layers preventing duplicate controls remain valid architecture.
- Any change affecting selector/UPE coupling is classified Tier 0 high-risk.
- Mandatory regression journey validation is required for any modification at this boundary.

## Blast Radius Impact

This decision directly impacts:

- Checkout payment rendering
- Cart → Checkout transition
- Fragment refresh cycles
- Wallet visibility layers
- Gateway fallback logic
- Submit orchestration determinism

## Invariants Protected

- Visible payment selection MUST match submitted gateway.
- Provider components MUST NOT override checkout orchestration authority.
- UI integration failures MUST NOT alter payment/order authority boundaries.
- Payment truth remains provider confirmation + local order mapping (never UI state).
- Re-render cycles MUST converge to a single stable actionable method state.


---

## Source: `docs/60-adr/ADR-002-authority-hierarchy.md`

# ADR-002: Authority Hierarchy (Payment/Auth/Provisioning)

## Status
Accepted

This decision is binding and may only be altered through a superseding ADR.

## Context

Blackwork runtime flows cross multiple domains: checkout execution, cart transitions, provider confirmation, authentication/session continuity, provisioning/claim, and entitlement activation.

Architecture maps define these domains structurally. ADR-001 formalizes selector authority for checkout orchestration. This ADR formalizes the global top-down authority hierarchy to eliminate ambiguity and prevent circular authority assumptions between payment, identity, and provisioning layers.

Cart state, checkout UI, success screens, and frontend fragments are operational or presentation layers. They are NOT authority surfaces.

Without a strict hierarchy, the system can drift into invalid patterns:

- UI success states treated as payment truth
- Authentication state interpreted as payment confirmation
- Provisioning activation without validated payment and validated identity
- Downstream layers mutating upstream authority
- Circular override loops between domains

This ADR eliminates those failure modes.

## Decision

Blackwork formally adopts a strict three-layer authority hierarchy:

1. Payment Authority (highest)
2. Authentication Authority (middle)
3. Provisioning Authority (lower)

All cross-domain runtime decisions MUST respect this order.

Lower layers MAY read upstream authority.
Lower layers MUST NOT mutate, reinterpret, or override upstream authority.

Authority precedence is strictly acyclic and MUST remain:

Payment > Authentication > Provisioning

## Authority Layers (Explicit Definitions)

### 1) Payment Authority

Payment authority is established exclusively by provider-confirmed payment outcome and authoritative local order-state convergence.

Payment Authority MUST include:
- Provider confirmation channels (webhook/callback with trust validation)
- Deterministic, idempotent local order-state mapping
- Convergence under retries or duplicate events

Payment Authority MUST NOT be inferred from:
- Frontend success screens
- Client-side UI state
- Pre-confirmation redirects
- Cart or checkout rendering state

Payment truth exists independently of UI surfaces.

### 2) Authentication Authority

Authentication authority is established by validated identity, session continuity, and token trust boundaries.

Authentication Authority MUST include:
- Verified identity/session validity
- Authenticated principal continuity
- Token and session trust enforcement

Authentication Authority MUST NOT:
- Override payment truth
- Rewrite payment/order lifecycle outcomes
- Imply entitlement activation without validated provisioning rules

Authentication state is a prerequisite for identity-dependent transitions but not a commerce authority layer.

### 3) Provisioning Authority

Provisioning authority governs claim eligibility, entitlement activation, and access-state transitions.

Provisioning Authority MUST include:
- Claim eligibility validation
- Entitlement activation only after upstream predicates are satisfied
- Repeat-safe, idempotent convergence for claim flows

Provisioning Authority MUST NOT:
- Activate access without validated payment and validated identity
- Alter payment/order authority
- Infer identity validity from UI state alone

Provisioning is a downstream consequence layer, never an upstream authority layer.

## Non-Authority Surfaces

The following are explicitly non-authoritative:

- Cart state
- Checkout rendering
- Payment success screens
- Fragment refresh cycles
- Frontend UI indicators

These surfaces MAY reflect authority but MUST NOT create or redefine it.

## Cross-Layer Rules

- UI state is never authority.
- Payment confirmation CANNOT be inferred from frontend surfaces.
- Authentication MUST NOT override payment truth.
- Provisioning MUST NOT activate without validated payment AND validated identity.
- Lower layers MUST NOT supersede higher authority layers.
- Cross-layer reads are allowed; cross-layer authority takeover is prohibited.
- Callback and webhook handlers MUST remain idempotent and converge to a single stable state.
- In any conflict, higher-layer authority MUST prevail.

## Alternatives Considered

### 1) Flat authority model
Rejected.
Creates circular authority and non-deterministic state resolution.

### 2) Auth-first model
Rejected.
Violates payment truth boundaries and enables premature entitlement activation.

### 3) Provisioning-led model
Rejected.
Inverts trust hierarchy and allows downstream layers to redefine commerce or identity truth.

### 4) Soft or undocumented precedence
Rejected.
Insufficient for governance-grade enforcement and prone to architectural drift.

## Consequences

- Cross-domain decisions become deterministic.
- Cart and Checkout can be refactored safely without affecting payment truth.
- Claim flows are structurally gated by validated upstream authority.
- Tier 0 intersections (payment/auth/provisioning) require explicit hierarchy validation during regression.
- ADR-001 remains aligned: checkout orchestration authority does not redefine payment truth.

## Invariants Protected

- Payment truth MUST originate from provider confirmation + authoritative local order mapping.
- Authentication continuity MUST be validated before identity-dependent transitions.
- Provisioning activation MUST require valid payment truth AND valid identity.
- No UI surface can create, upgrade, downgrade, or override authority state.
- Repeated callbacks/retries MUST converge and MUST NOT create cross-layer divergence.
- Authority precedence MUST remain strictly acyclic: Payment > Authentication > Provisioning.

