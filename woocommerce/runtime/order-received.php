<?php
/**
 * WooCommerce runtime — order-received / confirmation + wallet return.
 *
 * Extracted verbatim from woocommerce-init.php (Phase 3b, BW-TASK-20260629). Function bodies are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue assets for the order confirmation (thank you) page.
 */
function bw_mew_enqueue_order_confirmation_assets()
{
    if (!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url('order-received')) {
        return;
    }

    $css_file = BW_MEW_PATH . 'assets/css/bw-order-confirmation.css';
    $version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_enqueue_style(
        'bw-order-confirmation',
        BW_MEW_URL . 'assets/css/bw-order-confirmation.css',
        [],
        $version
    );
}

/**
 * Prevent full-page cache from serving stale order-received pages.
 */
function bw_mew_prevent_order_received_cache()
{
    if (!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url('order-received')) {
        return;
    }

    if (function_exists('nocache_headers')) {
        nocache_headers();
    }

    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }
}

/**
 * Safe debug logger for wallet flows.
 * Active only for admin users when WP_DEBUG is enabled.
 *
 * @param string $context Log context label.
 * @param array  $data    Safe debug payload.
 * @return void
 */
function bw_mew_wallet_debug_log($context, $data = array())
{
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }

    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        return;
    }

    $safe_context = sanitize_key((string) $context);
    $safe_data = is_array($data) ? $data : array();

    error_log('[BW Wallet Debug][' . $safe_context . '] ' . wp_json_encode($safe_data));
}

/**
 * Redirect wallet failed/canceled returns away from order-received to checkout.
 */
function bw_mew_handle_wallet_failed_return_redirect()
{
    if (!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url('order-received')) {
        return;
    }

    $redirect_status = isset($_GET['redirect_status']) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ? sanitize_text_field(wp_unslash($_GET['redirect_status'])) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        : '';
    if ('failed' !== $redirect_status && 'canceled' !== $redirect_status) {
        return;
    }

    $order_id = absint(get_query_var('order-received'));
    if ($order_id <= 0 && isset($_GET['order-received'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $order_id = absint(wp_unslash($_GET['order-received'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }
    if ($order_id <= 0) {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    $payment_method = $order->get_payment_method();
    if (!in_array($payment_method, array('bw_klarna', 'bw_google_pay', 'bw_apple_pay'), true)) {
        return;
    }

    $is_cart_empty = true;
    if (function_exists('WC') && WC()->cart) {
        $is_cart_empty = (0 === (int) WC()->cart->get_cart_contents_count());
    }
    bw_mew_wallet_debug_log('return_router', array(
        'gateway_id'      => (string) $payment_method,
        'order_id'        => (int) $order_id,
        'order_status'    => (string) $order->get_status(),
        'redirect_status' => (string) $redirect_status,
        'is_paid'         => (bool) $order->is_paid(),
        'is_cart_empty'   => (bool) $is_cart_empty,
    ));

    // If payment is already confirmed by webhook, allow normal thank-you flow.
    if ($order->is_paid()) {
        return;
    }

    wc_add_notice(
        __('Payment was canceled or not completed. Please choose another payment method and try again.', 'bw'),
        'error'
    );

    // Reset pending-payment session state so a new Klarna attempt can start cleanly.
    if (function_exists('WC') && WC()->session) {
        WC()->session->set('order_awaiting_payment', false);
        WC()->session->set('chosen_payment_method', $payment_method);
        WC()->session->set('reload_checkout', true);
    }

    $order->add_order_note(__('Customer returned from wallet flow with failed/canceled status.', 'bw'));

    $checkout_url = function_exists('wc_get_checkout_url')
        ? wc_get_checkout_url()
        : wc_get_page_permalink('checkout');

    wp_safe_redirect($checkout_url);
    exit;
}

/**
 * Render checkout logo header for order-received pages (logo only).
 *
 * Reuses checkout logo source and sizing options to keep visual consistency.
 *
 * @return void
 */
function bw_mew_render_order_received_logo_header()
{
    $settings = bw_mew_get_checkout_settings();
    $logo_width = !empty($settings['logo_width']) ? absint($settings['logo_width']) : 200;
    $logo_padding_top = isset($settings['logo_padding_top']) ? absint($settings['logo_padding_top']) : 0;
    $logo_padding_right = isset($settings['logo_padding_right']) ? absint($settings['logo_padding_right']) : 0;
    $logo_padding_bottom = isset($settings['logo_padding_bottom']) ? absint($settings['logo_padding_bottom']) : 0;
    $logo_padding_left = isset($settings['logo_padding_left']) ? absint($settings['logo_padding_left']) : 0;

    $logo_url = '';
    if (function_exists('has_custom_logo') && has_custom_logo()) {
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_data = wp_get_attachment_image_src($custom_logo_id, 'full');
            if ($logo_data) {
                $logo_url = $logo_data[0];
            }
        }
    }

    if (empty($logo_url)) {
        $logo_url = !empty($settings['logo']) ? $settings['logo'] : '';
    }

    if (empty($logo_url)) {
        return;
    }

    $header_styles = 'position:absolute;top:0;left:0;width:100%;background:transparent;border:0;z-index:100;pointer-events:none;';
    $inner_styles  = 'width:100%;max-width:none;margin:0;padding:22px 28px;display:flex;align-items:center;justify-content:flex-start;';
    $anchor_styles = 'pointer-events:auto;display:inline-flex;align-items:center;justify-content:flex-start;text-decoration:none;';

    $logo_styles = sprintf(
        'max-width: %dpx; padding: %dpx %dpx %dpx %dpx;',
        $logo_width,
        $logo_padding_top,
        $logo_padding_right,
        $logo_padding_bottom,
        $logo_padding_left
    );
    ?>
    <div class="bw-minimal-checkout-header bw-minimal-checkout-header--order-received" style="<?php echo esc_attr($header_styles); ?>">
        <div class="bw-minimal-checkout-header__inner bw-minimal-checkout-header__inner--left" style="<?php echo esc_attr($inner_styles); ?>">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="bw-minimal-checkout-header__logo"
                style="<?php echo esc_attr($anchor_styles . $logo_styles); ?>">
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" />
            </a>
        </div>
    </div>
    <?php
}
