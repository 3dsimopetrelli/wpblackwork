<?php
/**
 * WooCommerce runtime — cart layout, assets, and coupon AJAX.
 *
 * Extracted verbatim from woocommerce-init.php (Phase 3b, BW-TASK-20260629). Function bodies are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX handler to remove coupon from cart.
 *
 * FIX: This handler now properly synchronizes with WooCommerce's checkout refresh mechanism
 * to prevent the coupon from re-applying after removal. The key changes:
 * 1. Force WC session commit BEFORE responding to ensure session persistence
 * 2. Trigger WooCommerce's 'removed_coupon' action for proper event integration
 * 3. Return hash to help client detect if cart state changed
 */
function bw_mew_ajax_remove_coupon()
{
    $request_method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper(sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD']))) : '';
    if ('POST' !== $request_method) {
        wp_send_json_error(array('message' => __('Method Not Allowed.', 'woocommerce')), 405);
    }

    check_ajax_referer('bw-checkout-nonce', 'nonce');

    if (!class_exists('WooCommerce') || !WC()->cart) {
        wp_send_json_error(array('message' => __('Cart not available.', 'woocommerce')));
    }

    $coupon_code = isset($_POST['coupon']) ? sanitize_text_field(wp_unslash($_POST['coupon'])) : '';

    if (empty($coupon_code)) {
        wp_send_json_error(array('message' => __('Invalid coupon code.', 'woocommerce')));
    }

    // Verify the coupon is actually applied before attempting removal
    $applied_coupons = WC()->cart->get_applied_coupons();
    if (!in_array(strtolower($coupon_code), array_map('strtolower', $applied_coupons), true)) {
        wp_send_json_error(array('message' => __('Coupon is not applied.', 'woocommerce')));
    }

    // Remove the coupon using WooCommerce's standard method
    $removed = WC()->cart->remove_coupon($coupon_code);

    if (!$removed) {
        wp_send_json_error(array('message' => __('Coupon could not be removed.', 'woocommerce')));
    }

    // Calculate totals after removing coupon
    WC()->cart->calculate_totals();

    // CRITICAL FIX: Force immediate session persistence with multiple safety checks
    // This ensures the coupon removal is saved BEFORE the checkout fragment refresh reads the session
    if (WC()->session) {
        // Save cart data to session immediately
        WC()->cart->persistent_cart_update();

        // Force session data write to database/storage
        WC()->session->save_data();

        // Additional safety: set the cart session explicitly
        WC()->session->set('cart', serialize(WC()->cart->get_cart_for_session()));
        WC()->session->set('applied_coupons', WC()->cart->get_applied_coupons());
        WC()->session->set('coupon_discount_totals', WC()->cart->get_coupon_discount_totals());
        WC()->session->set('coupon_discount_tax_totals', WC()->cart->get_coupon_discount_tax_totals());

        // Force one more save to commit all the above
        WC()->session->save_data();
    }

    // Clear any cart-related caches that might cause stale data
    wc_clear_notices();

    // Trigger WooCommerce's standard removed_coupon action for proper event integration
    // This allows other plugins/code to react to coupon removal
    do_action('woocommerce_removed_coupon', $coupon_code);

    wp_send_json_success(array(
        'message' => __('Coupon removed successfully.', 'woocommerce'),
        'applied_coupons' => WC()->cart->get_applied_coupons(),
        'cart_hash' => WC()->cart->get_cart_hash(), // Return hash to verify state change
    ));
}

/**
 * AJAX handler to apply coupon to cart.
 *
 * FIX: This custom handler ensures proper session persistence when applying coupons.
 * Without this, the coupon would be applied server-side but not reflected in the checkout
 * fragments due to race conditions between session writes and checkout refresh reads.
 *
 * Key differences from WooCommerce's standard apply_coupon endpoint:
 * 1. Forces immediate session persistence with multiple safety checks
 * 2. Explicitly sets all coupon-related session data
 * 3. Returns cart hash to help client detect state changes
 * 4. Properly integrates with WooCommerce's action hooks
 */
function bw_mew_ajax_apply_coupon()
{
    $request_method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper(sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD']))) : '';
    if ('POST' !== $request_method) {
        wp_send_json_error(array('message' => __('Method Not Allowed.', 'woocommerce')), 405);
    }

    check_ajax_referer('bw-checkout-nonce', 'nonce');

    if (!class_exists('WooCommerce') || !WC()->cart) {
        wp_send_json_error(array('message' => __('Cart not available.', 'woocommerce')));
    }

    $coupon_code = isset($_POST['coupon_code']) ? sanitize_text_field(wp_unslash($_POST['coupon_code'])) : '';

    if (empty($coupon_code)) {
        wp_send_json_error(array('message' => __('Please enter a coupon code.', 'woocommerce')));
    }

    // Clear any existing notices to ensure clean validation
    wc_clear_notices();

    // Apply the coupon using WooCommerce's standard method
    $applied = WC()->cart->apply_coupon($coupon_code);

    if (!$applied) {
        // Get error message from WooCommerce notices
        $error_notices = wc_get_notices('error');
        $error_message = __('Invalid coupon code.', 'woocommerce');

        if (!empty($error_notices) && is_array($error_notices)) {
            $first_error = reset($error_notices);
            if (is_array($first_error) && isset($first_error['notice'])) {
                $error_message = wp_strip_all_tags((string) $first_error['notice']);
            } elseif (is_string($first_error)) {
                $error_message = wp_strip_all_tags($first_error);
            }
        }

        wc_clear_notices();
        wp_send_json_error(array('message' => $error_message));
    }

    // Calculate totals after applying coupon
    WC()->cart->calculate_totals();

    // CRITICAL FIX: Force immediate session persistence with multiple safety checks
    // This ensures the coupon application is saved BEFORE the checkout fragment refresh reads the session
    if (WC()->session) {
        // Save cart data to session immediately
        WC()->cart->persistent_cart_update();

        // Force session data write to database/storage
        WC()->session->save_data();

        // Additional safety: set the cart session explicitly
        WC()->session->set('cart', serialize(WC()->cart->get_cart_for_session()));
        WC()->session->set('applied_coupons', WC()->cart->get_applied_coupons());
        WC()->session->set('coupon_discount_totals', WC()->cart->get_coupon_discount_totals());
        WC()->session->set('coupon_discount_tax_totals', WC()->cart->get_coupon_discount_tax_totals());

        // Force one more save to commit all the above
        WC()->session->save_data();
    }

    // Clear any notices
    wc_clear_notices();

    // Trigger WooCommerce's standard applied_coupon action for proper event integration
    // This allows other plugins/code to react to coupon application
    do_action('woocommerce_applied_coupon', $coupon_code);

    wp_send_json_success(array(
        'message' => __('Coupon applied successfully.', 'woocommerce'),
        'applied_coupons' => WC()->cart->get_applied_coupons(),
        'cart_hash' => WC()->cart->get_cart_hash(), // Return hash to verify state change
    ));
}

/**
 * Enqueue assets for the custom cart layout.
 */
function bw_mew_enqueue_cart_assets()
{
    if (!function_exists('is_cart') || !is_cart()) {
        return;
    }

    $css_file = BW_MEW_PATH . 'assets/css/bw-cart.css';
    $js_file = BW_MEW_PATH . 'assets/js/bw-cart.js';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';
    $js_version = file_exists($js_file) ? filemtime($js_file) : '1.0.0';

    wp_enqueue_style(
        'bw-cart',
        BW_MEW_URL . 'assets/css/bw-cart.css',
        [],
        $css_version
    );

    wp_enqueue_script(
        'bw-cart',
        BW_MEW_URL . 'assets/js/bw-cart.js',
        ['jquery'],
        $js_version,
        true
    );
}

/**
 * Prepare cart layout by hiding theme elements if necessary.
 */
function bw_mew_prepare_cart_layout()
{
    if (!function_exists('is_cart') || !is_cart()) {
        return;
    }

    add_filter('woocommerce_show_page_title', '__return_false');

    // Unhook cross-sells from their default position in collaterals
    remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display');

    // Remove shipping from cart totals
    add_filter('woocommerce_cart_ready_to_calc_shipping', '__return_false', 99);
    add_filter('woocommerce_shipping_calculator_enabled', '__return_false', 99);
    add_filter('woocommerce_cart_needs_shipping', '__return_false', 99);
}
