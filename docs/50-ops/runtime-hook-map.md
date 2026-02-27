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
| Hook | Priority | Callback function/method | File path | Domain owner | Notes | Tier | Authority Surface |
|---|---:|---|---|---|---|---:|---|
| `plugins_loaded` | 10 (default) | `bw_mew_initialize_woocommerce_overrides` | `woocommerce/woocommerce-init.php` | Checkout/Payments/Auth | Runtime bootstrap for Woo overrides and multiple downstream hook registrations. | 1 | No |
| `template_redirect` | **5** | `bw_maybe_redirect_request` | `includes/class-bw-redirects.php` | Redirect | Tier 0 redirect engine entrypoint; runs early and may short-circuit request with `wp_safe_redirect`. | 0 | Yes |
| `template_redirect` | 10 (default) | `bw_show_coming_soon` | `BW_coming_soon/includes/functions.php` | Coming Soon | Runtime page-gating surface; priority interplay with redirect/cart/checkout paths. | 1 | No |
| `template_redirect` | 5 | `BW_Social_Login::handle_requests` | `includes/woocommerce-overrides/class-bw-social-login.php` | Auth | Social login callback/redirect handling, early in redirect stack. | 0 | Yes |
| `template_redirect` | 6 | `bw_mew_force_auth_callback_for_guest_transitions` | `includes/woocommerce-overrides/class-bw-my-account.php` | My Account/Supabase | Guest->callback enforcement path. | 0 | Yes |
| `template_redirect` | 7 | `bw_mew_cleanup_logged_in_auth_callback_query` | `includes/woocommerce-overrides/class-bw-my-account.php` | My Account/Supabase | Stale callback query cleanup. | 0 | Yes |
| `template_redirect` | 8 | `bw_mew_prepare_theme_title_bypass` | `woocommerce/woocommerce-init.php` | Checkout/My Account UI | Title bypass setup before layout prep. | 1 | No |
| `template_redirect` | 9 | `bw_mew_prepare_account_page_layout` | `woocommerce/woocommerce-init.php` | My Account | Layout wiring for account pages. | 1 | No |
| `template_redirect` | 9 | `bw_mew_prepare_checkout_layout` | `woocommerce/woocommerce-init.php` | Checkout | Layout wiring for checkout pages. | 1 | No |
| `template_redirect` | 9 | `bw_mew_prepare_cart_layout` | `woocommerce/woocommerce-init.php` | Cart | Layout wiring for cart pages. | 1 | No |
| `template_redirect` | 9 | `bw_mew_hide_single_product_notices` | `woocommerce/woocommerce-init.php` | Product UX | Notice suppression in product flow. | 1 | No |
| `template_redirect` | 9 | `bw_mew_hide_logged_in_account_title` | `woocommerce/woocommerce-init.php` | My Account | Account title suppression. | 1 | No |
| `template_redirect` | 1 | `bw_mew_prevent_order_received_cache` | `woocommerce/woocommerce-init.php` | Checkout/Payments | Early order-received cache-control logic. | 0 | No |
| `template_redirect` | 2 | `bw_mew_handle_wallet_failed_return_redirect` | `woocommerce/woocommerce-init.php` | Payments/Checkout | Early failed wallet return handling. | 0 | Yes |
| `template_redirect` | 11 | `bw_mew_sync_profile_names_from_purchase_data` | `includes/woocommerce-overrides/class-bw-my-account.php` | My Account | Runtime profile sync from order data. | 1 | No |
| `template_redirect` | 11 | `bw_mew_sync_account_address_fields_from_latest_order` | `includes/woocommerce-overrides/class-bw-my-account.php` | My Account | Runtime address sync from latest order. | 1 | No |
| `template_redirect` | 12 | `bw_mew_handle_profile_update` | `includes/woocommerce-overrides/class-bw-my-account.php` | My Account | Profile update POST handling. | 1 | No |
| `template_redirect` | 15 | `bw_mew_normalize_guest_email_entrypoint` | `includes/woocommerce-overrides/class-bw-my-account.php` | My Account/Supabase | Entry normalization for guest email paths. | 0 | Yes |
| `template_redirect` | 20 | `bw_mew_handle_email_entrypoint_redirect` | `includes/woocommerce-overrides/class-bw-my-account.php` | My Account/Supabase | Endpoint redirect continuation. | 0 | Yes |
| `template_redirect` | 25 | `bw_mew_redirect_order_verify_email_for_supabase` | `includes/woocommerce-overrides/class-bw-my-account.php` | My Account/Supabase | Verify-email redirect gating. | 0 | Yes |
| `wp_body_open` | **5** | `bw_header_render_frontend` | `includes/modules/header/frontend/header-render.php` | Header | Tier 0 global header injection point. | 0 | No |
| `wp` | 1 | `bw_header_disable_theme_header` | `includes/modules/header/frontend/header-render.php` | Header | Removes theme header renderer for custom header mode. | 1 | No |
| `wp_head` | 99 | `bw_header_theme_header_fallback_css` | `includes/modules/header/frontend/header-render.php` | Header | Fallback CSS to hide theme header. | 1 | No |
| `wp_head` | 1 | `bw_mew_supabase_early_invite_redirect_hint` | `woocommerce/woocommerce-init.php` | Supabase/Auth | Early callback/invite hinting path. | 1 | No |
| `wp_enqueue_scripts` | 20 | `bw_header_enqueue_assets` | `includes/modules/header/frontend/assets.php` | Header | Frontend header JS/CSS payload. | 1 | No |
| `woocommerce_add_to_cart_fragments` | 10 (default) | `bw_header_cart_count_fragment` | `includes/modules/header/frontend/fragments.php` | Header/Woo | Fragment-based cart badge sync for header. | 0 | No |
| `wp_ajax_bw_live_search_products` | 10 (default) | `bw_header_live_search_products` | `includes/modules/header/frontend/ajax-search.php` | Header | Header live search AJAX endpoint. | 1 | No |
| `wp_ajax_nopriv_bw_live_search_products` | 10 (default) | `bw_header_live_search_products` | `includes/modules/header/frontend/ajax-search.php` | Header | Guest live search endpoint. | 1 | No |
| `wp_enqueue_scripts` | 10 (default) | `bw_cart_popup_register_assets` | `cart-popup/cart-popup.php` | Cart Popup | Registers cart popup assets + localized config. | 1 | No |
| `wp_enqueue_scripts` | 20 | `bw_cart_popup_enqueue_assets` | `cart-popup/cart-popup.php` | Cart Popup | Enqueues cart popup runtime assets. | 1 | No |
| `woocommerce_loop_add_to_cart_link` | 10 | `bw_cart_popup_hide_view_cart_button` | `cart-popup/cart-popup.php` | Cart Popup | Suppresses View Cart loop behavior coupling. | 1 | No |
| `wp_head` | 10 (default) | `bw_cart_popup_hide_view_cart_css` | `cart-popup/cart-popup.php` | Cart Popup | CSS fallback for Woo View Cart link hiding. | 1 | No |
| `wp_footer` | 10 (default) | `bw_cart_popup_render_panel` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | Renders popup panel DOM in frontend. | 1 | No |
| `wp_head` | 10 (default) | `bw_cart_popup_dynamic_css` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | Runtime CSS vars/styles for popup config. | 1 | No |
| `woocommerce_add_to_cart_fragments` | 10 (default) | `bw_cart_popup_update_fragments` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | Cart popup fragment response extension. | 0 | No |
| `wp_ajax_bw_cart_popup_add_to_cart` | 10 (default) | `bw_cart_popup_ajax_add_to_cart` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | Popup add-to-cart action. | 1 | No |
| `wp_ajax_nopriv_bw_cart_popup_add_to_cart` | 10 (default) | `bw_cart_popup_ajax_add_to_cart` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | Guest popup add-to-cart action. | 1 | No |
| `wp_ajax_bw_cart_popup_get_contents` | 10 (default) | `bw_cart_popup_get_cart_contents` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | Popup content refresh endpoint. | 1 | No |
| `wp_ajax_nopriv_bw_cart_popup_get_contents` | 10 (default) | `bw_cart_popup_get_cart_contents` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | Guest popup content refresh endpoint. | 1 | No |
| `wp_ajax_bw_cart_popup_remove_item` | 10 (default) | `bw_cart_popup_remove_item` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | Remove cart line via popup. | 1 | No |
| `wp_ajax_nopriv_bw_cart_popup_remove_item` | 10 (default) | `bw_cart_popup_remove_item` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | Guest remove cart line via popup. | 1 | No |
| `wp_ajax_bw_cart_popup_update_quantity` | 10 (default) | `bw_cart_popup_update_quantity` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | Quantity update via popup. | 1 | No |
| `wp_ajax_nopriv_bw_cart_popup_update_quantity` | 10 (default) | `bw_cart_popup_update_quantity` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | Guest quantity update via popup. | 1 | No |
| `wp_ajax_bw_cart_popup_apply_coupon` | 10 (default) | `bw_cart_popup_apply_coupon` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | Coupon apply via popup. | 1 | No |
| `wp_ajax_nopriv_bw_cart_popup_apply_coupon` | 10 (default) | `bw_cart_popup_apply_coupon` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | Guest coupon apply via popup. | 1 | No |
| `wp_ajax_bw_cart_popup_remove_coupon` | 10 (default) | `bw_cart_popup_remove_coupon` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | Coupon removal via popup. | 1 | No |
| `wp_ajax_nopriv_bw_cart_popup_remove_coupon` | 10 (default) | `bw_cart_popup_remove_coupon` | `cart-popup/frontend/cart-popup-frontend.php` | Cart Popup | Guest coupon removal via popup. | 1 | No |
| `woocommerce_locate_template` | 1 | `bw_mew_locate_template` | `woocommerce/woocommerce-init.php` | Checkout/My Account | Template override resolver (plugin templates first). | 1 | No |
| `woocommerce_locate_core_template` | 1 | `bw_mew_locate_template` | `woocommerce/woocommerce-init.php` | Checkout/My Account | Core template override resolver. | 1 | No |
| `wp_enqueue_scripts` | 20 | `bw_mew_enqueue_checkout_assets` | `woocommerce/woocommerce-init.php` | Checkout/Payments | Checkout runtime JS/CSS bundle. | 1 | No |
| `wp_enqueue_scripts` | 20 | `bw_mew_enqueue_cart_assets` | `woocommerce/woocommerce-init.php` | Cart | Cart runtime asset bundle. | 1 | No |
| `wp_enqueue_scripts` | 20 | `bw_mew_enqueue_account_page_assets` | `woocommerce/woocommerce-init.php` | My Account | Account runtime assets. | 1 | No |
| `wp_enqueue_scripts` | 20 | `bw_mew_enqueue_order_confirmation_assets` | `woocommerce/woocommerce-init.php` | Checkout/Orders | Order-received runtime assets. | 1 | No |
| `wp_enqueue_scripts` | 20 | `bw_mew_enqueue_supabase_bridge` | `woocommerce/woocommerce-init.php` | Supabase/Auth | Auth bridge frontend script enqueue. | 1 | No |
| `wp_enqueue_scripts` | 30 | `bw_mew_enqueue_related_products_assets` | `woocommerce/woocommerce-init.php` | Product UX | Related products frontend assets. | 1 | No |
| `woocommerce_checkout_update_order_review` | 10 | `bw_mew_sync_checkout_cart_quantities` | `woocommerce/woocommerce-init.php` | Checkout/Cart | Sync quantities during checkout order review update. | 0 | No |
| `woocommerce_checkout_posted_data` | 20 | `bw_mew_sync_billing_from_shipping_mode` | `woocommerce/woocommerce-init.php` | Checkout | Posted-data normalization path. | 1 | No |
| `woocommerce_available_payment_gateways` | 10 (default) | `bw_mew_hide_paypal_advanced_card_processing` | `woocommerce/woocommerce-init.php` | Payments | Gateway availability filter. | 1 | No |
| `wc_stripe_upe_params` | 10 (default) | `bw_mew_customize_stripe_upe_appearance` | `woocommerce/woocommerce-init.php` | Payments | Stripe UPE params appearance adaptation. | 1 | No |
| `woocommerce_payment_gateways` | 10 (default) | `bw_mew_add_google_pay_gateway` | `woocommerce/woocommerce-init.php` | Payments | Registers custom payment gateway. | 1 | No |
| `wp_ajax_bw_apply_coupon` | 10 (default) | `bw_mew_ajax_apply_coupon` | `woocommerce/woocommerce-init.php` | Checkout | Checkout coupon apply endpoint. | 1 | No |
| `wp_ajax_nopriv_bw_apply_coupon` | 10 (default) | `bw_mew_ajax_apply_coupon` | `woocommerce/woocommerce-init.php` | Checkout | Guest checkout coupon apply endpoint. | 1 | No |
| `wp_ajax_bw_remove_coupon` | 10 (default) | `bw_mew_ajax_remove_coupon` | `woocommerce/woocommerce-init.php` | Checkout | Checkout coupon remove endpoint. | 1 | No |
| `wp_ajax_nopriv_bw_remove_coupon` | 10 (default) | `bw_mew_ajax_remove_coupon` | `woocommerce/woocommerce-init.php` | Checkout | Guest checkout coupon remove endpoint. | 1 | No |
| `woocommerce_order_status_processing` | 10 | `bw_mew_handle_supabase_checkout_invite` | `includes/woocommerce-overrides/class-bw-supabase-auth.php` | Supabase/Checkout | Invite/provisioning trigger on paid-like state. | 0 | Yes |
| `woocommerce_order_status_completed` | 10 | `bw_mew_handle_supabase_checkout_invite` | `includes/woocommerce-overrides/class-bw-supabase-auth.php` | Supabase/Checkout | Invite/provisioning trigger on completed state. | 0 | Yes |
| `woocommerce_order_status_on-hold` | 10 | `bw_mew_handle_supabase_checkout_invite` | `includes/woocommerce-overrides/class-bw-supabase-auth.php` | Supabase/Checkout | Invite trigger on on-hold state. | 0 | Yes |
| `woocommerce_payment_complete` | 10 | `bw_mew_handle_supabase_checkout_invite` | `includes/woocommerce-overrides/class-bw-supabase-auth.php` | Supabase/Checkout | Invite trigger on payment completion. | 0 | Yes |
| `woocommerce_thankyou` | 20 | `bw_mew_handle_supabase_checkout_invite` | `includes/woocommerce-overrides/class-bw-supabase-auth.php` | Supabase/Checkout | Post-order fallback trigger. | 0 | Yes |
| `woocommerce_order_status_processing` | 10 (default) | `BW_Checkout_Subscribe_Frontend::maybe_subscribe_on_paid` | `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php` | Brevo/Checkout | Subscribe timing coupling to paid states. | 1 | No |
| `woocommerce_order_status_completed` | 10 (default) | `BW_Checkout_Subscribe_Frontend::maybe_subscribe_on_paid` | `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php` | Brevo/Checkout | Subscribe timing coupling to paid states. | 1 | No |
| `woocommerce_checkout_create_order` | 10 | `BW_Checkout_Subscribe_Frontend::save_consent_meta_on_create_order` | `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php` | Brevo/Checkout | Consent metadata capture at order creation. | 1 | No |
| `woocommerce_checkout_update_order_meta` | 10 | `BW_Checkout_Subscribe_Frontend::save_consent_meta` | `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php` | Brevo/Checkout | Consent metadata persistence path. | 1 | No |
| `woocommerce_checkout_order_processed` | 20 | `BW_Checkout_Subscribe_Frontend::maybe_subscribe_on_created` | `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php` | Brevo/Checkout | Optional subscribe timing at order processed. | 1 | No |

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
- `woocommerce_add_to_cart_fragments` (header/cart popup cart-state reflection)
- `woocommerce_checkout_update_order_review` (checkout recomputation path)
- `woocommerce_order_status_*` + `woocommerce_payment_complete` (Supabase/Brevo post-payment side-effects)

Normative controls for Tier 0 hooks:
- Any Tier 0 hook priority change MUST include explicit reference to this document.
- Any Tier 0 hook priority change MUST include regression verification evidence.
- Any Tier 0 hook priority change that affects authority behavior MUST be formalized via ADR.

### Do not change priority notes
- `includes/class-bw-redirects.php` -> `add_action('template_redirect', 'bw_maybe_redirect_request', 5)`:
  - Priority MUST NOT be changed without redirect precedence re-validation.
- `includes/modules/header/frontend/header-render.php` -> `add_action('wp_body_open', 'bw_header_render_frontend', 5)`:
  - Priority MUST NOT be changed without header/theme override regression checks.
- `woocommerce/woocommerce-init.php` early callbacks (priorities `1` and `2`) and layout callbacks (`8/9`):
  - Priorities MUST NOT be reordered without checkout return + wallet + account redirect regression suite.

## Runtime Governance Rule
- Runtime hook priority is part of the system contract.
- Changing priority is equivalent to changing runtime behavior.
- Tier 0 hooks are Frozen Authority Surfaces unless a roadmap item explicitly authorizes modification.

## 5) Unknowns / where to look
- Some hook registrations occur inside class initialization paths (`::init()` / constructor registration). If runtime behavior differs from this map, verify bootstrap sequence in:
  - `bw-main-elementor-widgets.php`
  - `woocommerce/woocommerce-init.php`
  - `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php`
- Third-party plugin hook collisions (Rank Math/SEO/redirect plugins) are unknown from repository-only inspection. Inspect active plugin hook stack at runtime (`template_redirect`, `wp`, `wp_head`, Woo payment/order hooks).
