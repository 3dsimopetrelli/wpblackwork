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
