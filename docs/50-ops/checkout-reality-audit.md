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
