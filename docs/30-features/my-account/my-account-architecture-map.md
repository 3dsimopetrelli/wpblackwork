# My Account Architecture Map

## 1) Purpose + Scope
This document is the official architecture reference for the My Account domain.
It defines how My Account orchestrates account surfaces, authentication/session state, WooCommerce data ownership, and Supabase onboarding coupling.

Scope includes:
- account rendering and endpoint surfaces
- auth/onboarding gating behavior
- orders/downloads visibility contracts
- settings update lanes (profile, billing, shipping, security)

Out of scope:
- implementation refactors
- UI copy changes
- operational runbooks

## 2) Runtime Components
Primary templates:
- `woocommerce/templates/myaccount/my-account.php`
- `woocommerce/templates/myaccount/navigation.php`
- `woocommerce/templates/myaccount/dashboard.php`
- `woocommerce/templates/myaccount/downloads.php`
- `woocommerce/templates/myaccount/orders.php`
- `woocommerce/templates/myaccount/form-edit-account.php`
- `woocommerce/templates/myaccount/form-login.php`
- `woocommerce/templates/myaccount/auth-callback.php`
- `woocommerce/templates/myaccount/set-password.php`

Core PHP handlers and orchestrators:
- `includes/woocommerce-overrides/class-bw-my-account.php`
- `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- `woocommerce/woocommerce-init.php` (bridge preload, callback normalization, asset enqueue)

Client-side controllers:
- `assets/js/bw-account-page.js` (auth screens, OTP/magic link, create-password, bridge coordination)
- `assets/js/bw-supabase-bridge.js` (callback token bridge, anti-loop handling, auth-in-progress state)
- `assets/js/bw-my-account.js` (settings tabs, floating labels, password field UX)
- `assets/js/bw-password-modal.js` (blocking onboarding password modal for Supabase mode)

Bridge handlers that affect My Account render:
- `bw_mew_force_auth_callback_for_guest_transitions()`
- `bw_mew_cleanup_logged_in_auth_callback_query()`
- `bw_mew_enforce_supabase_onboarding_lock()`
- `bw_mew_handle_email_entrypoint_redirect()`
- Supabase AJAX handlers: `bw_get_password_status`, `bw_set_password_modal`, token/session bridge actions in `class-bw-supabase-auth.php`

## 3) Navigation & Surface Model
### Left navigation nodes
Menu is filtered and ordered by `bw_mew_filter_account_menu_items()`:
- `dashboard`
- `downloads`
- `orders` (labeled "My purchases")
- `edit-account` (labeled "settings")
- `customer-logout` (labeled "logout")

### Settings sub-tabs
`form-edit-account.php` defines four settings panels:
- Profile
- Billing Details
- Shipping Details
- Security

`bw-my-account.js` controls tab activation (`.bw-tab` -> `.bw-tab-panel`) and form UX helpers.

### Gating rules
- Logged-out users on My Account (non `set-password`) are routed to login surface.
- Callback-like auth transitions (`code`, `type=invite|recovery`, `bw_auth_callback=1`, `bw_set_password=1`) render callback loader flow instead of standard login.
- In Supabase provider mode, users with `bw_supabase_onboarded != 1` are onboarding-gated; modal/bridge flows are authoritative for progressing access.
- Post-email CTA routes (`bw_after_login=orders|downloads`) are held on root My Account until session/onboarding is stable, then redirected.

## 4) Data Authority Model
Display name + profile fields:
- Authority owner: WordPress user profile (`wp_users` + user meta).
- Written by: profile form flow in My Account (`bw_mew_handle_profile_update()`), and Supabase bridge sync when applicable.

Billing/shipping data:
- Authority owner: WooCommerce customer data (`WC_Customer` / user meta billing_* and shipping_*).
- Written by: billing/shipping forms in `form-edit-account.php` handled by `bw_mew_handle_profile_update()`.

Orders (digital vs physical):
- Authority owner: WooCommerce order records and line items.
- Read/rendered via My Account templates and helper queries in `class-bw-my-account.php`.

Downloads permissions:
- Authority owner: WooCommerce downloadable item entitlement + order linkage.
- Presentation grouped as digital downloads in `downloads.php`; access remains constrained by Woo/order ownership and linked account state.

"Account verified" indicator:
- Effective signal is onboarding/session readiness, principally tied to `bw_supabase_onboarded` in Supabase mode.
- In WordPress provider mode, onboarding lock is not applied.

Email and password management:
- Email change in Supabase lane uses pending-email lifecycle (`bw_supabase_pending_email`) and callback confirmation.
- Password change uses Supabase API lane when provider is Supabase; onboarding modal and security tab both depend on valid Supabase session bridge.

## 5) Auth & Verification Surface
"Account verified" means:
- provider and runtime indicate a stable authenticated WP session
- for Supabase mode, onboarding marker is completed (`bw_supabase_onboarded = 1`)

Dependency on Supabase onboarding marker:
- Marker is read by `bw_user_needs_onboarding()`.
- Marker is flipped by set-password completion and selected callback/token flows in `class-bw-supabase-auth.php`.

Failure-safe behavior (not verified):
- My Account stays in gated/auth-transition experience (callback loader, password modal, or login surface depending on state).
- No forced state mutation of order/payment truth.
- Session-missing password attempts degrade to explicit re-login path.

## 6) Orders & Downloads Surface
Digital orders list contract:
- `downloads.php` renders digital entitlements through My Account helper layer.
- Download action is enabled only when concrete downloadable URL is available.

Physical orders list contract:
- `orders.php` renders order-centric purchase history, including physical products and links to order detail.
- Counts and display are derived from Woo orders and item product types.

Downloads readiness contract:
- Requires both ownership linkage and resolved account/auth state.
- In Supabase guest-to-account scenarios, readiness may depend on claim process completion.

Guest-to-account claim coupling:
- `bw_mew_claim_guest_orders_for_user()` links guest orders/download permissions to authenticated account email.
- Claim execution is invoked in Supabase bridge paths (token-login and set-password modal completion).

## 7) Settings Flows
### 7.1 Profile update flow (local WP/Woo authority)
- Entrypoint: Profile tab form submit (`bw_account_profile_submit`).
- Authority owner: WordPress profile (name/display fields).
- Callback/redirect dependency: post-save redirect to `edit-account`.
- Invariants: nonce required, validation errors surfaced as notices, no silent partial save.

### 7.2 Billing/Shipping update flow (Woo customer meta authority)
- Entrypoint: Billing and Shipping tab forms (`bw_account_billing_submit`, `bw_account_shipping_submit`).
- Authority owner: WooCommerce customer data (`WC_Customer` + meta fallback).
- Callback/redirect dependency: post-save redirect to `edit-account`.
- Invariants: required Woo address fields validated; shipping "same as billing" path remains explicit.

### 7.3 Security: Change Password (Supabase lane vs profile lane)
- Entrypoint: Security tab password form (`data-bw-supabase-password-form`) and modal (`bw-password-modal.js`) for onboarding gate.
- Authority owner: Supabase identity credential state; WP session remains local authority for logged-in status.
- Callback/redirect dependency: requires valid bridged Supabase session token; missing session forces re-login.
- Invariants: no password update without session + nonce; no success state without onboarding marker convergence.

### 7.4 Security: Change Email (double-confirm + callback + UI confirmation)
- Entrypoint: Security email form (`data-bw-supabase-email-form`).
- Authority owner: confirmed identity email (Supabase confirmation path reflected locally).
- Callback/redirect dependency: confirmation callback sets/consumes query markers and pending-email state.
- Invariants: double-confirm required, pending email banner shown until confirmation, no loop/no flash/no silent fail.

## 8) High-Risk Zones (Blast Radius)
Primary blast-radius files/classes/scripts/hooks:
- `woocommerce/templates/myaccount/my-account.php` (entry routing and callback surface switch)
- `woocommerce/templates/myaccount/form-edit-account.php` (security/settings authority and UX)
- `includes/woocommerce-overrides/class-bw-my-account.php` (endpoint registration, gating, redirect normalization, save handlers)
- `includes/woocommerce-overrides/class-bw-supabase-auth.php` (session bridge, onboarding marker lifecycle, order claim, invite/callback handlers)
- `assets/js/bw-supabase-bridge.js` (callback normalization, auth-in-progress state, redirect convergence)
- `assets/js/bw-account-page.js` (OTP/auth screen state machine and token bridge interaction)
- `assets/js/bw-password-modal.js` (blocking onboarding modal and set-password writes)
- `woocommerce/templates/checkout/order-received.php` (post-order CTA branch feeding My Account onboarding path)

Risk characteristics:
- My Account depends on callback correctness; minor query/redirect changes can produce loops, stale loaders, or unauthenticated flashes.
- Security flows have split authority (WP session + Supabase credentials) and are sensitive to token/session race conditions.
- Guest order claim logic can affect downloads visibility and must remain idempotent.

## 9) Maintenance & Regression References
- [Checkout Architecture Map](../checkout/checkout-architecture-map.md)
- [Supabase Architecture Map](../../40-integrations/supabase/supabase-architecture-map.md)
- [Auth Architecture Map](../../40-integrations/auth/auth-architecture-map.md)
- [Unified Callback Contracts](../../00-governance/callback-contracts.md)
- [Cross-Domain State Dictionary](../../00-governance/cross-domain-state-dictionary.md)
- [Regression Protocol](../../50-ops/regression-protocol.md)
