# Auth Architecture Map

## 1) Purpose + Scope
This document is the official architecture reference for Blackwork authentication integration.
It describes current runtime behavior for provider selection (`WordPress` vs `Supabase`) and the OIDC broker dependency used for Supabase redirect login.

Scope boundaries:
- Documentation-only architecture model.
- Based on current plugin implementation reality.
- No code refactor guidance.

## 2) Admin Provider Switch Model
### Login Provider toggle (WordPress | Supabase)
- Option key: `bw_account_login_provider`.
- Values: `wordpress`, `supabase`.
- Saved from admin settings in `admin/class-blackwork-site-settings.php`.

### Runtime selection behavior
- My Account and login gates read `bw_account_login_provider` at runtime.
- Main consumers:
  - `woocommerce/templates/myaccount/form-login.php`
  - `woocommerce/templates/global/form-login.php`
  - `woocommerce/templates/checkout/order-received.php`
  - `includes/woocommerce-overrides/class-bw-my-account.php`
  - `includes/woocommerce-overrides/class-bw-supabase-auth.php` (provider-guarded handlers)

### Field rendering dependency
- Admin UI uses provider-scoped sections (`.bw-login-provider-section`) shown/hidden with JS in account settings screen.
- Supabase-only fields (project URL/keys, OIDC mode, session storage, onboarding controls) render under provider `supabase`.
- WordPress-only social settings render under provider `wordpress`.

### Config readiness gates (Supabase + OIDC)
- Core Supabase runtime config: `bw_supabase_project_url`, `bw_supabase_anon_key`.
- Mode keys: `bw_supabase_login_mode` (`native` | `oidc`), `bw_supabase_with_plugins`.
- OIDC broker config source: `openid_connect_generic_settings` (owned by OpenID Connect Generic Client plugin).
- OIDC helper access:
  - `bw_oidc_is_active()`
  - `bw_oidc_get_auth_url()`
  - `bw_oidc_get_redirect_uri()`

### Fallback behavior if Supabase/OIDC is misconfigured
- If provider is `supabase` but required config is missing, runtime returns auth errors and/or does not expose successful login path.
- If OIDC plugin is not active/configured, helper-derived OIDC URLs resolve empty and admin shows warning.
- Login mode normalization falls back to `native` when mode is invalid.

## 3) Provider Model
### WordPress native auth (primary local)
- Uses WooCommerce/WordPress account flows and WP auth/session.
- Includes optional WP-side social login controls from account settings.

### Supabase via OIDC broker (OpenID Connect Generic Client)
- Supabase provider can run in:
  - `native`: plugin-managed Supabase auth API + bridge flows.
  - `oidc`: redirect-based auth via OpenID Connect Generic Client broker.
- Broker handles authorization endpoint/callback exchange; Blackwork consumes broker URL/state indirectly.

### Social providers as sub-providers
- In Supabase context, Google/Facebook/Apple toggles are treated as Supabase/OIDC-related identity paths.
- In WordPress context, social provider controls are local provider settings.

### Provider precedence rules
1. `bw_account_login_provider` decides active provider family.
2. If provider is `supabase`, `bw_supabase_login_mode` decides native vs OIDC flow shape.
3. If `oidc` mode is selected, broker availability/config is a hard dependency for redirect auth.

## 4) State Hierarchy
Authentication state model used by templates, AJAX handlers, and gating logic:

1. `Anonymous`
- User not authenticated in WP context.
- Login/register UI shown according to provider.

2. `Pending verification / onboarding` (when applicable)
- Typical Supabase invite/password setup state.
- User may have account linkage metadata but not fully onboarded (`bw_supabase_onboarded` not set to 1).

3. `Authenticated`
- Active WP session established.
- For Supabase modes, session token context is also stored (cookie/usermeta according to configuration).

4. `Session expired / missing token`
- WP or Supabase token context is missing/expired.
- Runtime handlers request re-authentication or show recovery path.

5. `Logout`
- WP logout state reached; Supabase-linked session artifacts may also be cleared by auth flows/handlers.

## 5) Runtime Flow Model
### A) WordPress login flow
1. Provider resolves as `wordpress`.
2. WooCommerce login form submits with WP nonce (`woocommerce-login-nonce`).
3. WordPress authentication establishes WP session cookies.
4. User continues to account/checkout target.

### B) Supabase/OIDC redirect flow
1. Provider resolves as `supabase`.
2. Login mode resolves as `oidc`.
3. Client receives broker auth URL via helper/localized data (`bw_oidc_get_auth_url()`).
4. Browser redirects to provider authorize flow through OIDC broker.
5. Callback is completed by OIDC plugin endpoint.
6. WordPress session returns to account target/callback route.

### C) Session establishment + redirect back to target
- Native Supabase flows localize auth parameters via `bwAccountAuth` / `bwSupabaseBridge`.
- Callback and redirect URLs include account-oriented targets (`/my-account/`, `bw_auth_callback` patterns).

### D) Checkout gating (applicable)
- Checkout/order-received templates check provider + provisioning keys.
- Supabase provider and/or provisioning can alter post-order CTA and account activation path.

### E) Failure-safe degrade path
- If Supabase/OIDC readiness fails, user-facing flow degrades to retry/error paths instead of silent success.
- Provider-guarded handlers avoid executing Supabase-only logic when provider is `wordpress`.

## 6) Redirect/Callback Contract
### Callback endpoint ownership
- OIDC callback endpoint ownership is broker-side (OpenID Connect Generic Client plugin).
- Blackwork does not implement the OIDC token-exchange endpoint itself.

### Required params
- Standard OIDC callback expectations include `state` and `code`.
- Validation semantics are expected to be enforced by the broker plugin.

### Nonce/state validation expectations
- Blackwork AJAX auth handlers use `check_ajax_referer( 'bw-supabase-login', 'nonce' )`.
- OIDC redirect `state` validation is broker responsibility.

### Redirect target discipline
- Blackwork computes safe account/callback destinations (`my-account`, controlled query args).
- Redirect URL sanitization helper exists for Supabase invite/reset redirect paths (`bw_mew_supabase_sanitize_redirect_url`).
- Open redirect risk is mitigated by restricting/sanitizing redirect targets.

## 7) Storage + Session Model
### WP session assumptions
- WordPress auth remains session authority for site access.
- WooCommerce/My Account/Checkout gating relies on WP user/session state.

### Supabase/OIDC token storage
- Native Supabase token storage strategy key: `bw_supabase_session_storage` (`cookie` | `usermeta`).
- Cookie name base: `bw_supabase_jwt_cookie_name`.
- Token persistence helpers live in `includes/woocommerce-overrides/class-bw-supabase-auth.php`.
- OIDC broker-specific token internals are plugin-owned.

### OIDC identity to WP user mapping
- Mapping/linking controls exist through:
  - `bw_supabase_enable_wp_user_linking`
  - `bw_supabase_create_wp_users`
- Supabase onboarding/user link metadata is stored on WP users (`user_meta`).

### Logout invalidation semantics
- WP logout invalidates WordPress session.
- Supabase/OIDC linked session artifacts are handled by their respective auth flows/storage handlers.

## 8) Security Model
- CSRF protection:
  - WP login form nonce (`woocommerce-login-nonce`).
  - Supabase AJAX nonce (`bw-supabase-login`).
- Token leakage prevention:
  - Service role key intended server-side only.
  - Browser-localized config uses anon/public keys.
- Secrets isolation:
  - `bw_supabase_service_role_key` is configured for backend calls and must never be exposed client-side.
- Rate limiting expectations:
  - Supabase endpoints and resend flows contain anti-abuse/rate-limit behavior patterns.
- Audit/logging touchpoints:
  - Optional debug logging (`bw_supabase_debug_log`) with operational traces (without credential dumps by design intent).

## 9) High-Risk Zones
1. Provider switch logic (`bw_account_login_provider`)
- Misalignment can break all login-entry surfaces simultaneously (account, checkout, post-order gates).

2. Login templates and account scripts
- `woocommerce/templates/myaccount/form-login.php`
- `assets/js/bw-account-page.js`
- `assets/js/bw-supabase-bridge.js`
- High coupling between UI state, provider mode, and callback/query behavior.

3. OIDC plugin settings + redirect URIs
- External dependency (OpenID Connect Generic Client).
- Incorrect redirect URI/endpoint config breaks Supabase OIDC login despite valid Blackwork settings.

4. User mapping/creation paths
- WP user linkage and onboarding metadata can impact account access continuity and order ownership flows.

5. Checkout gate coupling
- Provider/provision flags influence order-received/login-gate behaviors and post-checkout account activation CTA.

## 10) Maintenance & Regression References
- [Regression Protocol](../../50-ops/regression-protocol.md)
- [Checkout Architecture Map](../../30-features/checkout/checkout-architecture-map.md)
- [Auth Setup Guide (current setup)](./social-login-setup-guide.md)
