<?php
/**
 * WooCommerce runtime — checkout layout, assets, headers, shipping labels.
 *
 * Extracted verbatim from woocommerce-init.php (Phase 3b, BW-TASK-20260629). Function bodies are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue assets for the custom checkout layout and expose colors as CSS variables.
 */
function bw_mew_enqueue_checkout_assets()
{
    if (!bw_mew_is_checkout_request()) {
        return;
    }

    $css_file = BW_MEW_PATH . 'assets/css/bw-checkout.css';
    $js_file = BW_MEW_PATH . 'assets/js/bw-checkout.js';
    $version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';
    $settings = bw_mew_get_checkout_settings();

    wp_enqueue_style(
        'bw-checkout',
        BW_MEW_URL . 'assets/css/bw-checkout.css',
        [],
        $version
    );

    if (file_exists($js_file)) {
        $js_version = filemtime($js_file);
        $dependencies = ['jquery'];

        if (wp_script_is('wc-checkout', 'registered')) {
            wp_enqueue_script('wc-checkout');
            $dependencies[] = 'wc-checkout';
        }

        wp_enqueue_script(
            'bw-checkout',
            BW_MEW_URL . 'assets/js/bw-checkout.js',
            $dependencies,
            $js_version,
            true
        );

        // Get free order message and button text from settings
        $free_order_message = get_option('bw_checkout_free_order_message', '');
        $free_order_button_text = get_option('bw_checkout_free_order_button_text', '');

        if (empty($free_order_message)) {
            $free_order_message = __('Your order is free. Complete your details and click Place order.', 'bw');
        }
        if (empty($free_order_button_text)) {
            $free_order_button_text = __('Confirm free order', 'bw');
        }

        wp_localize_script(
            'bw-checkout',
            'bwCheckoutParams',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bw-checkout-nonce'),
                'freeOrderMessage' => wp_kses_post(wpautop($free_order_message)),
                'freeOrderButtonText' => esc_html($free_order_button_text),
            )
        );

        // Google Maps settings for floating labels + autocomplete
        $google_maps_enabled = get_option('bw_google_maps_enabled', '0');
        $google_maps_api_key = get_option('bw_google_maps_api_key', '');
        $google_maps_autofill = get_option('bw_google_maps_autofill', '1');
        $google_maps_restrict = get_option('bw_google_maps_restrict_country', '1');

        // Load Google Maps API if enabled and API key exists
        if ('1' === $google_maps_enabled && !empty($google_maps_api_key)) {
            wp_enqueue_script(
                'google-maps-places',
                'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($google_maps_api_key) . '&libraries=places',
                [],
                null,
                true
            );
        }

        // Pass Google Maps settings to JavaScript
        wp_localize_script(
            'bw-checkout',
            'bwGoogleMapsSettings',
            array(
                'enabled' => '1' === $google_maps_enabled && !empty($google_maps_api_key),
                'autoFillCityPostcode' => '1' === $google_maps_autofill,
                'restrictToCountry' => '1' === $google_maps_restrict,
            )
        );
    }

    $inline_styles = '.bw-checkout-form{--bw-checkout-left-bg:' . esc_attr($settings['left_bg']) . ';--bw-checkout-right-bg:' . esc_attr($settings['right_bg']) . ';--bw-checkout-border-color:' . esc_attr($settings['border_color']) . ';}';

    wp_add_inline_style('bw-checkout', $inline_styles);

    // Enqueue payment methods assets (Shopify-style accordion)
    $payment_css_file = BW_MEW_PATH . 'assets/css/bw-payment-methods.css';
    $payment_js_file = BW_MEW_PATH . 'assets/js/bw-payment-methods.js';

    if (file_exists($payment_css_file)) {
        wp_enqueue_style(
            'bw-payment-methods',
            BW_MEW_URL . 'assets/css/bw-payment-methods.css',
            ['bw-checkout'],
            filemtime($payment_css_file)
        );
    }

    if (file_exists($payment_js_file)) {
        wp_enqueue_script(
            'bw-payment-methods',
            BW_MEW_URL . 'assets/js/bw-payment-methods.js',
            ['jquery', 'wc-checkout'],
            filemtime($payment_js_file),
            true
        );
    }

    // Enqueue checkout notices assets (moves notices into left column with custom styling)
    $notices_css_file = BW_MEW_PATH . 'assets/css/bw-checkout-notices.css';
    $notices_js_file = BW_MEW_PATH . 'assets/js/bw-checkout-notices.js';

    if (file_exists($notices_css_file)) {
        wp_enqueue_style(
            'bw-checkout-notices',
            BW_MEW_URL . 'assets/css/bw-checkout-notices.css',
            ['bw-checkout'],
            filemtime($notices_css_file)
        );
    }

    if (file_exists($notices_js_file)) {
        wp_enqueue_script(
            'bw-checkout-notices',
            BW_MEW_URL . 'assets/js/bw-checkout-notices.js',
            ['jquery', 'wc-checkout'],
            filemtime($notices_js_file),
            true
        );
    }

    // Enqueue Stripe UPE cleaner to hide "Card" accordion header
    $stripe_upe_cleaner_file = BW_MEW_PATH . 'assets/js/bw-stripe-upe-cleaner.js';
    if (file_exists($stripe_upe_cleaner_file)) {
        wp_enqueue_script(
            'bw-stripe-upe-cleaner',
            BW_MEW_URL . 'assets/js/bw-stripe-upe-cleaner.js',
            ['jquery', 'wc-checkout'],
            filemtime($stripe_upe_cleaner_file),
            true
        );
    }

    $enqueue_stripe_v3 = static function () {
        if (wp_script_is('bw-stripe-v3', 'enqueued') || wp_script_is('stripe', 'enqueued')) {
            return;
        }

        if (wp_script_is('stripe', 'registered')) {
            wp_enqueue_script('stripe');
            return;
        }

        if (!wp_script_is('bw-stripe-v3', 'registered')) {
            wp_register_script('bw-stripe-v3', 'https://js.stripe.com/v3/', [], null, true);
        }

        wp_enqueue_script('bw-stripe-v3');

        if (!wp_script_is('stripe', 'registered')) {
            wp_register_script('stripe', false, ['bw-stripe-v3'], null, true);
        }
    };

    // Google Pay Integration
    if (get_option('bw_google_pay_enabled', '0') === '1') {
        $google_pay_js = BW_MEW_PATH . 'assets/js/bw-google-pay.js';
        if (file_exists($google_pay_js)) {
            $enqueue_stripe_v3();
            wp_enqueue_script(
                'bw-google-pay',
                BW_MEW_URL . 'assets/js/bw-google-pay.js',
                ['jquery', 'stripe', 'wc-checkout'],
                filemtime($google_pay_js),
                true
            );

            $test_mode = get_option('bw_google_pay_test_mode', '0') === '1';
            $pub_key = $test_mode
                ? get_option('bw_google_pay_test_publishable_key', '')
                : get_option('bw_google_pay_publishable_key', '');

            wp_localize_script('bw-google-pay', 'bwGooglePayParams', [
                'publishableKey'   => $pub_key,
                'testMode'         => $test_mode,
                'country'          => WC()->countries->get_base_country(),
                'currency'         => strtolower( get_woocommerce_currency() ),
                'ajaxCheckoutUrl'  => add_query_arg( 'wc-ajax', 'checkout', home_url( '/' ) ),
                'adminDebug'       => (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')),
            ]);
        }
    }

    // Apple Pay Integration (live-only).
    if (get_option('bw_apple_pay_enabled', '0') === '1') {
        $apple_pay_js = BW_MEW_PATH . 'assets/js/bw-apple-pay.js';
        if (file_exists($apple_pay_js)) {
            $enqueue_stripe_v3();
            wp_enqueue_script(
                'bw-apple-pay',
                BW_MEW_URL . 'assets/js/bw-apple-pay.js',
                ['jquery', 'stripe', 'wc-checkout'],
                filemtime($apple_pay_js),
                true
            );

            $apple_pub_key = (string) get_option('bw_apple_pay_publishable_key', '');
            if ('' === $apple_pub_key) {
                $apple_pub_key = (string) get_option('bw_google_pay_publishable_key', '');
            }

            wp_localize_script('bw-apple-pay', 'bwApplePayParams', [
                'publishableKey'   => $apple_pub_key,
                'country'          => WC()->countries->get_base_country(),
                'currency'         => strtolower(get_woocommerce_currency()),
                'ajaxCheckoutUrl'  => add_query_arg('wc-ajax', 'checkout', home_url('/')),
                'adminDebug'       => (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')),
            ]);
        }
    }
}

/**
 * Hide checkout notices and prepare layout.
 */
function bw_mew_prepare_checkout_layout()
{
    // Remove all WooCommerce notices from checkout page
    // remove_action('woocommerce_before_checkout_form', 'woocommerce_output_all_notices', 10);

    // Remove "Have a coupon?" banner that appears above checkout form
    remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);

    // Avoid rendering the payment section (and its button) twice by keeping it only in the left column.
    // Avoid rendering the payment section (and its button) twice by keeping it only in the left column.
    remove_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20);
}

/**
 * Check if the current request should be treated as checkout.
 *
 * @return bool
 */
function bw_mew_is_checkout_request()
{
    if (
        function_exists('is_checkout')
        && is_checkout()
        && !is_cart()
        && (!function_exists('is_order_received_page') || !is_order_received_page())
    ) {
        return true;
    }

    if (function_exists('is_page')) {
        $checkout_page_id = function_exists('wc_get_page_id') ? wc_get_page_id('checkout') : 0;
        if (
            $checkout_page_id
            && is_page($checkout_page_id)
            && (!function_exists('is_order_received_page') || !is_order_received_page())
        ) {
            return true;
        }
    }

    if (!empty($_POST['apply_coupon']) || !empty($_POST['woocommerce-apply-coupon-nonce'])) {
        return true;
    }

    return false;
}

if (!function_exists('bw_mew_normalize_checkout_column_widths')) {
    /**
     * Normalize checkout column widths ensuring sane bounds and total of 100%.
     *
     * @param int $left  Desired left column percentage.
     * @param int $right Desired right column percentage.
     *
     * @return array{left:int,right:int}
     */
    function bw_mew_normalize_checkout_column_widths($left, $right)
    {
        $left = min(90, max(10, absint($left)));
        $right = min(90, max(10, absint($right)));

        if (($left + $right) > 100) {
            $total = $left + $right;
            $left = (int) round(($left / $total) * 100);
            $right = 100 - $left;
        }

        return [
            'left' => $left,
            'right' => $right,
        ];
    }
}

/**
 * Hide the standard WooCommerce notice wrapper on single product pages only.
 */
function bw_mew_hide_single_product_notices()
{
    if (!function_exists('is_product') || !is_product()) {
        return;
    }

    remove_action('woocommerce_before_single_product', 'woocommerce_output_all_notices', 10);
    remove_action('woocommerce_before_single_product_summary', 'woocommerce_output_all_notices', 10);
}

/**
 * Sync cart quantities during checkout AJAX refreshes so totals update immediately.
 *
 * @param string $posted_data Serialized form data.
 */
function bw_mew_sync_checkout_cart_quantities($posted_data)
{
    if (empty($posted_data) || !WC()->cart) {
        return;
    }

    parse_str($posted_data, $parsed);

    if (empty($parsed['cart']) || !is_array($parsed['cart'])) {
        return;
    }

    foreach ($parsed['cart'] as $cart_item_key => $values) {
        if (!isset($values['qty'])) {
            continue;
        }

        $qty = max(0, wc_stock_amount(wp_unslash($values['qty'])));
        WC()->cart->set_quantity($cart_item_key, $qty, false);
    }

    WC()->cart->calculate_totals();
}

/**
 * Sync shipping address from billing fields when checkout mode is "same as billing".
 *
 * @param array $data Posted checkout data.
 * @return array
 */
function bw_mew_sync_billing_from_shipping_mode($data)
{
    if (!is_array($data)) {
        return $data;
    }

    $needs_shipping_address = WC()->cart && WC()->cart->needs_shipping_address();
    $mode = isset($data['bw_shipping_address_mode']) ? sanitize_key((string) $data['bw_shipping_address_mode']) : 'same';
    $data['bw_shipping_address_mode'] = $mode;

    if (!$needs_shipping_address) {
        return $data;
    }

    // Shipping fields are always shown when a shipping address is required.
    $data['ship_to_different_address'] = 1;

    if ('different' === $mode) {
        return $data;
    }

    $map = [
        'billing_first_name' => 'shipping_first_name',
        'billing_last_name' => 'shipping_last_name',
        'billing_company' => 'shipping_company',
        'billing_country' => 'shipping_country',
        'billing_address_1' => 'shipping_address_1',
        'billing_address_2' => 'shipping_address_2',
        'billing_city' => 'shipping_city',
        'billing_postcode' => 'shipping_postcode',
        'billing_state' => 'shipping_state',
    ];

    foreach ($map as $billing_key => $shipping_key) {
        if (!array_key_exists($billing_key, $data)) {
            continue;
        }

        $billing_value = is_string($data[$billing_key]) ? trim($data[$billing_key]) : $data[$billing_key];
        $data[$shipping_key] = $billing_value;
    }

    return $data;
}

/**
 * Retrieve checkout style and content options.
 *
 * @return array{logo:string,logo_align:string,page_bg:string,grid_bg:string,left_bg:string,right_bg:string,border_color:string,legal_text:string,left_width:int,right_width:int,thumb_ratio:string,thumb_width:int,right_sticky_top:int,right_padding_top:int,right_padding_right:int,right_padding_bottom:int,right_padding_left:int,footer_copyright:string,show_return_to_shop:string}
 */
function bw_mew_get_checkout_settings()
{
    $defaults = [
        'logo' => '',
        'logo_align' => 'left',
        'page_bg' => '#ffffff',
        'grid_bg' => '#ffffff',
        'logo_width' => 200,
        'logo_padding_top' => 0,
        'logo_padding_right' => 0,
        'logo_padding_bottom' => 30,
        'logo_padding_left' => 0,
        'show_order_heading' => '1',
        'left_bg' => '#ffffff',
        'right_bg' => 'transparent',
        'border_color' => '#262626',
        'legal_text' => '',
        'left_width' => 62,
        'right_width' => 38,
        'thumb_ratio' => 'square',
        'thumb_width' => 110,
        'right_sticky_top' => 20,
        'right_margin_top' => 0,
        'right_padding_top' => 0,
        'right_padding_right' => 0,
        'right_padding_bottom' => 0,
        'right_padding_left' => 28,
        'footer_copyright' => '',
        'show_footer_copyright' => '1',
        'show_return_to_shop' => '1',
    ];

    $settings = [
        'logo' => esc_url_raw(get_option('bw_checkout_logo', $defaults['logo'])),
        'logo_align' => sanitize_key(get_option('bw_checkout_logo_align', $defaults['logo_align'])),
        'logo_width' => absint(get_option('bw_checkout_logo_width', $defaults['logo_width'])),
        'page_bg' => sanitize_hex_color(get_option('bw_checkout_page_bg', get_option('bw_checkout_page_bg_color', $defaults['page_bg']))),
        'grid_bg' => sanitize_hex_color(get_option('bw_checkout_grid_bg', get_option('bw_checkout_grid_bg_color', $defaults['grid_bg']))),
        'logo_padding_top' => absint(get_option('bw_checkout_logo_padding_top', $defaults['logo_padding_top'])),
        'logo_padding_right' => absint(get_option('bw_checkout_logo_padding_right', $defaults['logo_padding_right'])),
        'logo_padding_bottom' => absint(get_option('bw_checkout_logo_padding_bottom', $defaults['logo_padding_bottom'])),
        'logo_padding_left' => absint(get_option('bw_checkout_logo_padding_left', $defaults['logo_padding_left'])),
        'show_order_heading' => get_option('bw_checkout_show_order_heading', $defaults['show_order_heading']),
        'left_bg' => sanitize_hex_color(get_option('bw_checkout_left_bg_color', $defaults['left_bg'])),
        'right_bg' => sanitize_hex_color(get_option('bw_checkout_right_bg_color', $defaults['right_bg'])),
        'border_color' => sanitize_hex_color(get_option('bw_checkout_border_color', $defaults['border_color'])),
        'legal_text' => get_option('bw_checkout_legal_text', $defaults['legal_text']),
        'left_width' => absint(get_option('bw_checkout_left_width', $defaults['left_width'])),
        'right_width' => absint(get_option('bw_checkout_right_width', $defaults['right_width'])),
        'thumb_ratio' => sanitize_key(get_option('bw_checkout_thumb_ratio', $defaults['thumb_ratio'])),
        'thumb_width' => absint(get_option('bw_checkout_thumb_width', $defaults['thumb_width'])),
        'right_sticky_top' => absint(get_option('bw_checkout_right_sticky_top', $defaults['right_sticky_top'])),
        'right_margin_top' => absint(get_option('bw_checkout_right_margin_top', $defaults['right_margin_top'])),
        'right_padding_top' => absint(get_option('bw_checkout_right_padding_top', $defaults['right_padding_top'])),
        'right_padding_right' => absint(get_option('bw_checkout_right_padding_right', $defaults['right_padding_right'])),
        'right_padding_bottom' => absint(get_option('bw_checkout_right_padding_bottom', $defaults['right_padding_bottom'])),
        'right_padding_left' => absint(get_option('bw_checkout_right_padding_left', $defaults['right_padding_left'])),
        'footer_copyright' => get_option('bw_checkout_footer_copyright_text', $defaults['footer_copyright']),
        'show_footer_copyright' => get_option('bw_checkout_show_footer_copyright', $defaults['show_footer_copyright']),
        'show_return_to_shop' => get_option('bw_checkout_show_return_to_shop', $defaults['show_return_to_shop']),
    ];

    $settings['logo_align'] = in_array($settings['logo_align'], ['left', 'center', 'right'], true) ? $settings['logo_align'] : $defaults['logo_align'];
    $settings['page_bg'] = $settings['page_bg'] ?: $defaults['page_bg'];
    $settings['grid_bg'] = $settings['grid_bg'] ?: $defaults['grid_bg'];
    $settings['left_bg'] = $settings['left_bg'] ?: $defaults['left_bg'];
    $settings['right_bg'] = $settings['right_bg'] ?: $defaults['right_bg'];
    $settings['border_color'] = $settings['border_color'] ?: $defaults['border_color'];
    $settings['thumb_ratio'] = in_array($settings['thumb_ratio'], ['square', 'portrait', 'landscape'], true) ? $settings['thumb_ratio'] : $defaults['thumb_ratio'];

    // Validate thumb_width bounds
    if ($settings['thumb_width'] < 50) {
        $settings['thumb_width'] = 50;
    }
    if ($settings['thumb_width'] > 300) {
        $settings['thumb_width'] = 300;
    }

    if (function_exists('bw_mew_normalize_checkout_column_widths')) {
        $normalized = bw_mew_normalize_checkout_column_widths($settings['left_width'], $settings['right_width']);
        $settings['left_width'] = $normalized['left'];
        $settings['right_width'] = $normalized['right'];
    }

    $settings['footer_copyright'] = wp_kses_post($settings['footer_copyright']);
    $settings['show_footer_copyright'] = '1' === (string) $settings['show_footer_copyright'] ? '1' : '0';
    $settings['show_return_to_shop'] = '1' === (string) $settings['show_return_to_shop'] ? '1' : '0';

    return $settings;
}

/**
 * Render minimal checkout header with logo and cart icon.
 */
function bw_mew_render_checkout_header()
{
    if (!function_exists('is_checkout') || !is_checkout()) {
        return;
    }

    // Get checkout settings
    $settings = bw_mew_get_checkout_settings();
    $logo_align = !empty($settings['logo_align']) ? $settings['logo_align'] : 'center';
    $logo_width = !empty($settings['logo_width']) ? absint($settings['logo_width']) : 200;
    $logo_padding_top = isset($settings['logo_padding_top']) ? absint($settings['logo_padding_top']) : 0;
    $logo_padding_right = isset($settings['logo_padding_right']) ? absint($settings['logo_padding_right']) : 0;
    $logo_padding_bottom = isset($settings['logo_padding_bottom']) ? absint($settings['logo_padding_bottom']) : 0;
    $logo_padding_left = isset($settings['logo_padding_left']) ? absint($settings['logo_padding_left']) : 0;

    // Get logo - prefer theme custom logo, fallback to checkout settings logo
    $logo_url = '';
    $home_url = home_url('/');

    if (function_exists('has_custom_logo') && has_custom_logo()) {
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_data = wp_get_attachment_image_src($custom_logo_id, 'full');
            if ($logo_data) {
                $logo_url = $logo_data[0];
            }
        }
    }

    // Fallback to checkout settings logo if theme logo not available
    if (empty($logo_url)) {
        $logo_url = !empty($settings['logo']) ? $settings['logo'] : '';
    }

    // Get cart URL
    $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');

    // Build inline styles for logo
    $logo_styles = sprintf(
        'max-width: %dpx; padding: %dpx %dpx %dpx %dpx;',
        $logo_width,
        $logo_padding_top,
        $logo_padding_right,
        $logo_padding_bottom,
        $logo_padding_left
    );

    // Render header only if we have a logo
    if (!empty($logo_url)):
        ?>
        <div class="bw-minimal-checkout-header">
            <div
                class="bw-minimal-checkout-header__inner bw-minimal-checkout-header__inner--<?php echo esc_attr($logo_align); ?>">
                <a href="<?php echo esc_url($home_url); ?>" class="bw-minimal-checkout-header__logo"
                    style="<?php echo esc_attr($logo_styles); ?>">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" />
                </a>
                <a href="<?php echo esc_url($cart_url); ?>" class="bw-minimal-checkout-header__cart"
                    aria-label="<?php esc_attr_e('View cart', 'woocommerce'); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="4" y="6" width="16" height="14" rx="2" stroke="currentColor" stroke-width="1.5" fill="none" />
                        <path d="M8 6V5C8 3.34315 9.34315 2 11 2H13C14.6569 2 16 3.34315 16 5V6" stroke="currentColor"
                            stroke-width="1.5" fill="none" />
                    </svg>
                </a>
            </div>
        </div>
        <?php
    endif;
}

/**
 * Render free order banner when cart total is 0.
 * Note: Stripe Express Checkout provides its own "OR" separator.
 */
function bw_mew_render_express_divider()
{
    if (!function_exists('is_checkout') || !is_checkout()) {
        return;
    }

    // Check if cart total is 0
    $is_free = false;
    if (function_exists('WC') && WC()->cart) {
        $total = WC()->cart->get_total('edit');
        $is_free = (0 == $total || '0' === $total || 0.0 === (float) $total);
    }

    // Get free order message from settings
    $free_message = get_option('bw_checkout_free_order_message', '');
    if (empty($free_message)) {
        $free_message = __('Your order is free. Complete your details and click Place order.', 'bw');
    }

    if ($is_free) {
        // Render free order banner (visible immediately with bw-free-order-active class)
        $free_message_text = trim(wp_strip_all_tags((string) $free_message));
        if ('' === $free_message_text) {
            $free_message_text = __('Your order is free. Complete your details and click Place order.', 'bw');
        }
        ?>
        <div class="bw-free-order-banner bw-free-order-active">
            <div class="bw-free-order-banner__content">
                <p class="bw-free-order-banner__text"><?php echo esc_html($free_message_text); ?></p>
            </div>
        </div>
        <?php
    } else {
        // Render custom OR divider between Express buttons and form (only when order is NOT free)
        ?>
        <div class="bw-express-divider"><span><?php esc_html_e('OR', 'bw'); ?></span></div>
        <?php
    }
}

/**
 * Add body classes for section heading customizations.
 *
 * @param array $classes Body classes.
 * @return array
 */
function bw_mew_add_section_heading_body_classes($classes)
{
    if (!is_checkout() || is_wc_endpoint_url()) {
        return $classes;
    }

    $hide_billing = get_option('bw_checkout_hide_billing_heading', '0');
    $hide_additional = get_option('bw_checkout_hide_additional_heading', '0');

    if ('1' === $hide_billing) {
        $classes[] = 'bw-hide-billing-heading';
    }

    if ('1' === $hide_additional) {
        $classes[] = 'bw-hide-additional-heading';
    }

    return $classes;
}

/**
 * Render custom address section heading before checkout customer details.
 */
function bw_mew_render_address_section_heading()
{
    $label = get_option('bw_checkout_address_heading_label', '');

    if (empty($label)) {
        return;
    }

    echo '<h3 class="bw-checkout-section-heading bw-checkout-section-heading--delivery">' . esc_html($label) . '</h3>';
}

/**
 * Use a clean checkout-only shipping package heading.
 *
 * @param string $package_name Existing shipping package name.
 * @return string
 */
function bw_mew_checkout_shipping_package_name($package_name)
{
    if (!function_exists('is_checkout') || !is_checkout() || (function_exists('is_order_received_page') && is_order_received_page())) {
        return $package_name;
    }

    return __('Shipping', 'bw');
}

/**
 * Simplify the checkout shipping method label when WooCommerce exposes a single
 * available rate. This preserves native amount formatting while avoiding
 * duplicate "Flat rate" text in the right column.
 *
 * @param string           $label  Default WooCommerce label.
 * @param WC_Shipping_Rate $method Shipping method object.
 * @return string
 */
function bw_mew_checkout_shipping_method_full_label($label, $method)
{
    if (!function_exists('is_checkout') || !is_checkout() || (function_exists('is_order_received_page') && is_order_received_page())) {
        return $label;
    }

    if (!class_exists('WC_Shipping_Rate') || !$method instanceof WC_Shipping_Rate) {
        return $label;
    }

    $packages = WC()->shipping()->get_packages();
    if (!is_array($packages) || 1 !== count($packages)) {
        return $label;
    }

    $package = reset($packages);
    $rates = isset($package['rates']) && is_array($package['rates']) ? $package['rates'] : [];

    if (1 !== count($rates)) {
        return $label;
    }

    $has_cost = 0 < (float) $method->cost;
    $hide_cost = !$has_cost && in_array($method->get_method_id(), ['free_shipping', 'local_pickup'], true);

    if ($has_cost && !$hide_cost) {
        $display = WC()->cart->display_prices_including_tax()
            ? wc_price($method->cost + $method->get_shipping_tax())
            : wc_price($method->cost);

        if ($method->get_shipping_tax() > 0) {
            if (WC()->cart->display_prices_including_tax() && !wc_prices_include_tax()) {
                $display .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
            } elseif (!WC()->cart->display_prices_including_tax() && wc_prices_include_tax()) {
                $display .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
            }
        }

        return $display;
    }

    return $label;
}
