# Blackwork Admin Panel Map

## Purpose
This document is the official architectural reference for the WordPress admin panel `Blackwork Site`.
It consolidates the structural reality already audited in [`docs/50-ops/admin-panel-reality-audit.md`](../50-ops/admin-panel-reality-audit.md) without introducing implementation changes.

## 1) Global Admin Architecture

### 1.1 Menu registration
- Main menu (`blackwork-site-settings`): registered with `add_menu_page(...)` in `admin/class-blackwork-site-settings.php`.
- Main submenu alias (`blackwork-site-settings`): explicit `Site Settings` submenu registered with `add_submenu_page(...)` in `admin/class-blackwork-site-settings.php`.
- Header submenu (`bw-header-settings`): registered with `add_submenu_page(...)` in `includes/modules/header/admin/header-admin.php`.
- Mail Marketing submenu (`blackwork-mail-marketing`): registered with `add_submenu_page(...)` in `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`.

### 1.1.1 2026-03 Navigation Restore
- `Site Settings` submenu was explicitly restored under `Blackwork Site`.
- Purpose: prevent WordPress from opening the first child module (`All Templates`) when clicking the top-level menu.
- Result:
  - `Blackwork Site` consistently lands on the unified settings router (`bw_site_settings_page`).
  - Checkout / Supabase / Coming Soon / Redirect / Import / Loading tabs remain reachable from a stable entrypoint.

### 1.2 Capability model
- Primary capability baseline: `manage_options` (main menu + Header + Mail Marketing).
- Import Product tab extends gate with `manage_woocommerce` OR `manage_options`.

### 1.3 Save models
- Custom POST save model (most main tabs):
  - tab renderer handles submit
  - `check_admin_referer(...)`
  - sanitized values persisted via `update_option(...)`
- Settings API model (Header submenu):
  - `register_setting('bw_header_settings_group', 'bw_header_settings', sanitize_callback)`
  - persisted through `options.php`
- Custom modular save model (Mail Marketing submenu):
  - centralized `handle_post()`
  - tab-based payload parsing
  - explicit sanitization + `update_option(...)`

### 1.4 AJAX endpoints
Main settings module:
- `bw_google_pay_test_connection`
- `bw_google_maps_test_connection`
- `bw_klarna_test_connection`
- `bw_apple_pay_test_connection`
- `bw_apple_pay_verify_domain`

Mail Marketing module:
- `bw_brevo_test_connection`
- `bw_brevo_order_refresh_status`
- `bw_brevo_order_retry_subscribe`
- `bw_brevo_order_load_lists`
- `bw_brevo_user_check_status`
- `bw_brevo_user_sync_status`

### 1.5 Admin assets
Main settings assets:
- `admin/css/blackwork-site-settings.css`
- `admin/css/bw-admin-ui-kit.css` (shared Shopify-style Blackwork admin UI primitives; scoped to Blackwork Site pages)
- `admin/js/bw-redirects.js`
- `admin/js/bw-google-pay-admin.js`
- `admin/js/bw-klarna-admin.js`
- `admin/js/bw-apple-pay-admin.js`
- `admin/js/bw-checkout-subscribe.js` (Mail Marketing General tab context)
- `assets/js/bw-border-toggle-admin.js`

Header assets:
- `includes/modules/header/admin/header-admin.js`
- WordPress media uploader (`wp_enqueue_media()`)

Mail Marketing operational assets:
- `admin/js/bw-order-newsletter-status.js`
- `admin/js/bw-user-mail-marketing.js`
- `admin/css/bw-order-newsletter-status.css`

### 1.5.1 Shared Admin UI Kit pattern
- Kit file: `admin/css/bw-admin-ui-kit.css`
- Enqueue strategy:
  - centralized in `admin/class-blackwork-site-settings.php` via `bw_admin_enqueue_ui_kit_assets()`
  - gated by `bw_is_blackwork_site_admin_screen(...)`
  - loads only on Blackwork Site panel surfaces (`blackwork-site-settings` top-level and its subpages, including Mail Marketing)
- Scope strategy:
  - all reusable rules are namespaced under `.bw-admin-root` to prevent bleed into unrelated WordPress admin screens.
- Adoption pattern for other Blackwork admin pages:
  1. Wrap page container with `.bw-admin-root`.
  2. Compose layout with kit primitives (`.bw-admin-header`, `.bw-admin-action-bar`, `.bw-admin-card`, `.bw-admin-field-row`, `.bw-admin-table`).
  3. Keep native WordPress form controls and existing save handlers unchanged.

Current adoption:
- `Blackwork Site > Media Folders` settings page
- `Blackwork Site > Site Settings` router page (header, action bar, card-wrapped tabs/content, save-proxy CTA bound to existing tab submit buttons)
- `Blackwork Site > Mail Marketing` page (header, action bar with save CTA, UI-kit tabs, card-grouped General/Checkout settings)
- `Blackwork Site > Header` page (header shell, action bar save CTA, UI-kit primary tabs, and section-card settings groups)

## 2) Tab-by-Tab Structural Map

## Cart Pop-up
- Renderer: `bw_site_render_cart_popup_tab()`
- Save delegate: `bw_cart_popup_save_settings()` (`cart-popup/admin/settings-page.php`)
- Nonce: `bw_cart_popup_save` (`bw_cart_popup_nonce`)
- Option groups:
  - Activation and behavior: `bw_cart_popup_active`, `bw_cart_popup_show_floating_trigger`, `bw_cart_popup_slide_animation`, `bw_cart_popup_show_quantity_badge`
  - Panel and overlay: `bw_cart_popup_panel_width`, `bw_cart_popup_mobile_width`, `bw_cart_popup_overlay_color`, `bw_cart_popup_overlay_opacity`, `bw_cart_popup_panel_bg`
  - Checkout CTA style/content: `bw_cart_popup_checkout_*`
  - Continue CTA style/content: `bw_cart_popup_continue_*`
  - SVG/icons spacing: `bw_cart_popup_additional_svg`, `bw_cart_popup_empty_cart_svg`, `bw_cart_popup_svg_black`, `bw_cart_popup_cart_icon_margin_*`, `bw_cart_popup_empty_cart_padding_*`
  - Promo block: `bw_cart_popup_promo_*`, `bw_cart_popup_apply_button_font_weight`
  - Navigation URL: `bw_cart_popup_return_shop_url`
- Frontend consumers:
  - `cart-popup/cart-popup.php`
  - `cart-popup/frontend/cart-popup-frontend.php`
  - `woocommerce/templates/cart/cart-empty.php`
- Dependencies:
  - WooCommerce cart runtime and related AJAX operations (`bw_cart_popup_*`).

## BW Coming Soon
- Renderer: `bw_site_render_coming_soon_tab()`
- Nonce: `bw_coming_soon_save`
- Option keys:
  - `bw_coming_soon_active`
- Frontend consumer:
  - `BW_coming_soon/includes/functions.php`
- Dependencies:
  - global site request gating logic.

## Login Page
- Renderer: `bw_site_render_account_page_tab()`
- Nonce: `bw_account_page_save`
- Option groups:
  - Provider and visual: `bw_account_login_provider`, image/logo keys, provider-specific titles/subtitles
  - Social toggles (WordPress path): `bw_account_facebook`, `bw_account_google`, provider secrets/IDs
  - Supabase auth model: `bw_supabase_*` configuration set
  - Compatibility keys: `bw_account_login_title`, `bw_account_login_subtitle`
- Frontend consumers:
  - `woocommerce/templates/global/form-login.php`
  - `woocommerce/templates/myaccount/form-login.php`
  - `woocommerce/templates/checkout/order-received.php`
  - `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- Dependencies:
  - provider routing between WordPress and Supabase auth ecosystems.

## My Account Page
- Renderer: `bw_site_render_my_account_front_tab()`
- Nonce: `bw_myaccount_front_save`
- Option keys:
  - `bw_myaccount_black_box_text`
  - `bw_myaccount_support_link`
- Frontend consumers:
  - My Account customization templates/override layer
- Dependencies:
  - My Account rendering path.

## Checkout
- Renderer: `bw_site_render_checkout_tab()`
- Nonce: `bw_checkout_settings_save`
- Option groups:
  - Layout and branding: `bw_checkout_logo*`, `bw_checkout_*_bg*`, widths, paddings, border settings, thumbnail settings
  - Content and legal: `bw_checkout_legal_text`, footer text and toggles
  - Section behavior: `bw_checkout_hide_*`, heading labels, free order message/button text
  - Policy blocks: `bw_checkout_policy_refund|shipping|privacy|terms|contact`
  - Checkout field sync: `bw_checkout_fields_settings`
  - Supabase provisioning: `bw_supabase_checkout_provision_enabled`, invite/expired redirect URLs
  - Payment toggles/config:
    - Google Pay: `bw_google_pay_*`
    - Klarna: `bw_klarna_*`
    - Apple Pay: `bw_apple_pay_*`
  - Address autocomplete: `bw_google_maps_enabled`, API key, autofill/restrict flags
- Frontend consumers:
  - `woocommerce/woocommerce-init.php`
  - `woocommerce/templates/checkout/payment.php`
  - `includes/Gateways/class-bw-google-pay-gateway.php`
  - `includes/Gateways/class-bw-klarna-gateway.php`
  - `includes/Gateways/class-bw-apple-pay-gateway.php`
- Dependencies:
  - WooCommerce checkout rendering and gateway availability
  - Supabase service-role/project configuration for provisioning flows
  - Google Maps API validity for autocomplete path.

## Redirect
- Renderer: `bw_site_render_redirect_tab()`
- Nonce: `bw_redirects_save`
- Option keys:
  - `bw_redirects` (list of source/target rules)
- Frontend consumer:
  - `includes/class-bw-redirects.php`
- Dependencies:
  - request interception and redirect execution order.

## Import Product
- Renderer: `bw_site_render_import_product_tab()`
- Capability: `manage_woocommerce` OR `manage_options`
- Nonces:
  - `bw_import_upload`
  - `bw_import_run`
- Data model:
  - transient state (`bw_import_state_{user_id}`), not long-lived option set
- Inputs:
  - CSV file, update-existing toggle, column mapping array
- Runtime consumers:
  - import parser/mapping/product-save functions in `admin/class-blackwork-site-settings.php`
- Dependencies:
  - WooCommerce product APIs, taxonomy/meta/media write paths.

## Loading
- Renderer: `bw_site_render_loading_tab()`
- Nonce: `bw_loading_settings_save`
- Option keys:
  - `bw_loading_global_spinner_hidden`
- Frontend consumer:
  - `blackwork-core-plugin.php`
- Dependencies:
  - WooCommerce loading/spinner behavior hooks.

## Header (submenu)
- Renderer: `bw_header_render_admin_page()`
- Save model: Settings API (`bw_header_settings_group`)
- Option key:
  - `bw_header_settings` (array)
- Key groups:
  - General header state and branding
  - Menus and breakpoints
  - Mobile layout and icon media IDs
  - Labels and links
  - Smart scroll configuration (`features.smart_scroll`, `smart_header.*`)
- Frontend consumer:
  - `includes/modules/header/frontend/header-render.php`
- Dependencies:
  - nav menu availability
  - smart-scroll CSS/JS precedence logic.

## Mail Marketing (Brevo) (submenu)
- Renderer: `render_mail_marketing_page()` (`general`/`checkout` internal tabs)
- Save handler: `handle_post()`
- Nonce: `bw_mail_marketing_save`
- Option keys:
  - Canonical: `bw_mail_marketing_general_settings`, `bw_mail_marketing_checkout_settings`
  - Legacy bridge source: `bw_checkout_subscribe_settings`
- Key groups:
  - General: API key/base/list, default opt-in mode, DOI settings, sender identity, debug/sync flags
  - Checkout channel: enabled/default checked, label/privacy text, timing, channel mode, placement key, priority offset
- Consumers:
  - Mail marketing admin, order/user status tooling, checkout subscription execution path
- Dependencies:
  - Brevo API reachability and key validity
  - checkout channel insertion point consistency.

## 3) Cross-Domain Dependencies Model

### 3.1 Auth provider logic
- `bw_account_login_provider` is the primary selector.
- WordPress social flags and Supabase OAuth flags can coexist in storage, but only one provider path should drive runtime behavior.

### 3.2 Payment gateway toggle logic
- `bw_google_pay_enabled`, `bw_klarna_enabled`, `bw_apple_pay_enabled` are intent toggles.
- Effective runtime availability also depends on gateway class readiness, required keys, and checkout context.

### 3.3 Supabase provisioning
- Checkout provisioning is controlled by `bw_supabase_checkout_provision_enabled` + redirect URLs.
- Functional validity depends on upstream Supabase credentials/configuration from auth settings.

### 3.4 Smart Header override precedence
- With Smart Header OFF: general Header background settings apply.
- With Smart Header ON: scroll-driven `smart_header.*` colors/opacities/blur settings take precedence.

### 3.5 Mail Marketing split model
- The current architecture is split:
  - General account/config model
  - Checkout channel behavior model
- Legacy option remains as migration/fallback compatibility layer.

## 4) Configuration Coherence Model

### 4.1 Mixed save patterns
- The admin panel intentionally combines:
  - custom POST saves (main settings tabs)
  - Settings API save (Header)
  - modular custom handler (Mail Marketing)
- This is functional but creates heterogeneous validation and lifecycle semantics.

### 4.2 Soft gating model
- Multiple domains rely on toggle + dependency readiness rather than hard blocking.
- Example pattern: feature can be enabled in UI while external prerequisites remain incomplete.

### 4.3 Legacy compatibility keys
- Some legacy keys are still written/read to preserve backward compatibility and migration continuity.
- This compatibility layer is deliberate but increases cognitive load during maintenance and troubleshooting.

## Maintenance note
For operational checks, incident handling, and validation posture, refer to:
- [`docs/50-ops/admin-panel-reality-audit.md`](../50-ops/admin-panel-reality-audit.md)
- [`docs/50-ops/maintenance-decision-matrix.md`](../50-ops/maintenance-decision-matrix.md)
- [`docs/50-ops/blackwork-development-protocol.md`](../50-ops/blackwork-development-protocol.md)

## 5) Normative Architecture Principles

### 5.1 Provider isolation rule
- Provider-specific configuration can coexist in storage, but runtime execution must resolve to one active provider path per domain.
- Auth domain: WordPress provider and Supabase provider configurations are both maintainable, but the effective provider is selected by the provider selector and must not blend execution paths.
- Integration-level logic must avoid implicit fallback between providers unless explicitly defined and documented.

### 5.2 Feature toggle soft-gating philosophy
- Admin toggles express operational intent, not unconditional runtime guarantee.
- A feature marked as enabled remains subject to dependency readiness checks (credentials, external availability, plugin/gateway readiness, context constraints).
- Soft-gating is therefore a two-step model:
  1. Administrative enablement
  2. Runtime eligibility validation

### 5.3 Runtime state determination hierarchy
- Runtime state is determined by the following hierarchy:
  1. Hard capability/security constraints (permissions, nonce, required context)
  2. Domain selector state (for example provider selection)
  3. Feature toggle state
  4. Dependency readiness (keys, APIs, classes, external provider status)
  5. Compatibility and fallback layer (legacy keys/defaults)
- Any downstream state cannot override an upstream failed condition.

### 5.4 Precedence rules (header, auth, payments)
- Header precedence:
  - Smart Header OFF -> General header visual settings apply.
  - Smart Header ON -> `smart_header.*` scroll settings override General visual values where overlapping.
- Auth precedence:
  - `bw_account_login_provider` is authoritative for runtime provider routing.
  - Non-selected provider settings remain passive configuration.
- Payments precedence:
  - Gateway toggle enables candidacy only.
  - Effective availability requires gateway readiness checks and valid key/config state.
  - Checkout runtime decides final availability in context.

### 5.5 Legacy compatibility posture
- Legacy keys/options are preserved as a controlled compatibility layer for migration continuity and backward behavior stability.
- Canonical models remain the current split/domain options; legacy paths must be treated as bridge/fallback, not as primary design targets.
- Maintenance policy: compatibility may be retained long-term when it reduces migration risk, but architectural documentation must always identify the canonical source of truth.
