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
