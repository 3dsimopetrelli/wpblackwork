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
| `wp_ajax_bw_fpw_filter_posts` | 10 (default) | `bw_fpw_filter_posts` | `includes/modules/search-engine/search-engine-module.php` | Search / Product Grid | 1 | No | Registered by module bootstrap; callback implemented in Product Grid adapter over shared search engine. |
| `wp_ajax_nopriv_bw_fpw_filter_posts` | 10 (default) | `bw_fpw_filter_posts` | `includes/modules/search-engine/search-engine-module.php` | Search / Product Grid | 1 | No | Guest Product Grid filter endpoint; same adapter/runtime contract. |
| `wp_ajax_bw_fpw_get_subcategories` | 10 (default) | `bw_fpw_get_subcategories` | `includes/modules/search-engine/search-engine-module.php` | Search / Product Grid | 1 | No | Product Grid subcategory AJAX surface; registration moved out of monolith. |
| `wp_ajax_nopriv_bw_fpw_get_subcategories` | 10 (default) | `bw_fpw_get_subcategories` | `includes/modules/search-engine/search-engine-module.php` | Search / Product Grid | 1 | No | Guest subcategory endpoint; still uses separate transient path by design. |
| `wp_ajax_bw_fpw_get_tags` | 10 (default) | `bw_fpw_get_tags` | `includes/modules/search-engine/search-engine-module.php` | Search / Product Grid | 1 | No | Product Grid tags AJAX surface; adapter endpoint over shared engine/facet data. |
| `wp_ajax_nopriv_bw_fpw_get_tags` | 10 (default) | `bw_fpw_get_tags` | `includes/modules/search-engine/search-engine-module.php` | Search / Product Grid | 1 | No | Guest tags endpoint. |
| `wp_ajax_bw_fpw_refresh_nonce` | 10 (default) | `bw_fpw_ajax_refresh_nonce` | `includes/modules/search-engine/search-engine-module.php` | Search / Product Grid | 1 | No | Product Grid nonce refresh endpoint; contract preserved after extraction. |
| `wp_ajax_nopriv_bw_fpw_refresh_nonce` | 10 (default) | `bw_fpw_ajax_refresh_nonce` | `includes/modules/search-engine/search-engine-module.php` | Search / Product Grid | 1 | No | Guest nonce refresh endpoint. |
| `save_post` | 10 (default) | `bw_fpw_clear_grid_transients` | `includes/modules/search-engine/search-engine-module.php` | Search / Product Grid | 1 | No | Search-domain invalidation registration moved to module bootstrap; callback clears search/filter cache generations and syncs canonical filter state. |
| `added_post_meta` | 10 | `bw_fpw_handle_product_filter_meta_change` | `includes/modules/search-engine/search-engine-module.php` | Search / Product Grid | 1 | No | Search-domain canonical filter-meta sync / invalidation hook registration. |
| `updated_post_meta` | 10 | `bw_fpw_handle_product_filter_meta_change` | `includes/modules/search-engine/search-engine-module.php` | Search / Product Grid | 1 | No | Search-domain canonical filter-meta sync / invalidation hook registration. |
| `deleted_post_meta` | 10 | `bw_fpw_handle_product_filter_meta_change` | `includes/modules/search-engine/search-engine-module.php` | Search / Product Grid | 1 | No | Search-domain canonical filter-meta sync / invalidation hook registration. |
| `set_object_terms` | 10 | `bw_fpw_handle_product_filter_term_change` | `includes/modules/search-engine/search-engine-module.php` | Search / Product Grid | 1 | No | Search-domain term-change invalidation registration. |
| `transition_post_status` | 10 | `bw_fpw_handle_product_filter_status_change` | `includes/modules/search-engine/search-engine-module.php` | Search / Product Grid | 1 | No | Search-domain status-change invalidation registration. |
| `bw_fpw_async_rebuild_advanced_filter_index` | 10 (default) | `bw_fpw_async_rebuild_advanced_filter_index_callback` | `includes/modules/search-engine/search-engine-module.php` | Search / Product Grid | 1 | No | Async advanced-filter index rebuild trigger now registered by module bootstrap with named callback. |
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

### 3.0 Search-domain module bootstrap (Product Grid search/filter runtime)
Search-domain runtime registrations now live in:
- `includes/modules/search-engine/search-engine-module.php`

Operational ownership split:
- module bootstrap owns hook registration only
- Product Grid adapter owns AJAX surface behavior and response assembly
- shared engine owns normalization, query planning, candidate resolution, indexes, cache, and invalidation callbacks

Governance note:
- search-domain hook additions/priority changes should now be reviewed against the shared search-engine boundary, not added back into `blackwork-core-plugin.php`
- Product Grid AJAX contract remains a regression-sensitive surface even though ownership moved out of the monolith

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
