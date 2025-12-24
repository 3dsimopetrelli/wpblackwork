<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Initialize WooCommerce customizations for BW Elementor Widgets.
 */
function bw_mew_initialize_woocommerce_overrides() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    $my_account_file = BW_MEW_PATH . 'includes/woocommerce-overrides/class-bw-my-account.php';
    $social_login_file = BW_MEW_PATH . 'includes/woocommerce-overrides/class-bw-social-login.php';

    if ( file_exists( $my_account_file ) ) {
        require_once $my_account_file;
    }

    if ( file_exists( $social_login_file ) ) {
        require_once $social_login_file;
    }

    add_filter( 'woocommerce_locate_template', 'bw_mew_locate_template', 1, 3 );
    add_action( 'wp_enqueue_scripts', 'bw_mew_enqueue_related_products_assets', 30 );
    add_action( 'wp_enqueue_scripts', 'bw_mew_enqueue_account_page_assets', 20 );
    add_action( 'wp_enqueue_scripts', 'bw_mew_enqueue_checkout_assets', 20 );
    add_filter( 'woocommerce_locate_core_template', 'bw_mew_locate_template', 1, 3 );
    add_action( 'template_redirect', 'bw_mew_prepare_account_page_layout', 9 );
    add_action( 'template_redirect', 'bw_mew_prepare_checkout_layout', 9 );
    add_action( 'template_redirect', 'bw_mew_hide_single_product_notices', 9 );
    add_action( 'woocommerce_checkout_update_order_review', 'bw_mew_sync_checkout_cart_quantities', 10, 1 );
    add_action( 'wp_ajax_bw_remove_coupon', 'bw_mew_ajax_remove_coupon' );
    add_action( 'wp_ajax_nopriv_bw_remove_coupon', 'bw_mew_ajax_remove_coupon' );
}
add_action( 'plugins_loaded', 'bw_mew_initialize_woocommerce_overrides' );

/**
 * Force WooCommerce to use plugin templates before theme overrides.
 *
 * @param string $template      Located template path.
 * @param string $template_name Template name relative to template path.
 * @param string $template_path WooCommerce template path.
 *
 * @return string
 */
function bw_mew_locate_template( $template, $template_name, $template_path ) {
    $plugin_template_path = trailingslashit( BW_MEW_PATH . 'woocommerce/templates' );

    if ( $template_name && file_exists( $plugin_template_path . $template_name ) ) {
        return $plugin_template_path . $template_name;
    }

    return $template;
}

/**
 * Enqueue assets for WooCommerce related products layout.
 */
function bw_mew_enqueue_related_products_assets() {
    if ( ! class_exists( 'WooCommerce' ) || ! is_product() ) {
        return;
    }

    if ( function_exists( 'bw_register_wallpost_widget_assets' ) ) {
        bw_register_wallpost_widget_assets();
    }

    if ( wp_style_is( 'bw-wallpost-style', 'registered' ) && ! wp_style_is( 'bw-wallpost-style', 'enqueued' ) ) {
        wp_enqueue_style( 'bw-wallpost-style' );
    }

    $css_file = BW_MEW_PATH . 'woocommerce/css/bw-related-products.css';
    $version  = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

    wp_enqueue_style(
        'bw-related-products-style',
        plugin_dir_url( __FILE__ ) . 'css/bw-related-products.css',
        [ 'bw-wallpost-style' ],
        $version
    );
}

/**
 * Enqueue assets for the custom account/login layout.
 */
function bw_mew_enqueue_account_page_assets() {
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || is_user_logged_in() ) {
        return;
    }

    $css_file = BW_MEW_PATH . 'assets/css/bw-account-page.css';
    $css_version  = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';
    $js_file      = BW_MEW_PATH . 'assets/js/bw-account-page.js';
    $js_version   = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

    wp_enqueue_style(
        'bw-account-page',
        BW_MEW_URL . 'assets/css/bw-account-page.css',
        [],
        $css_version
    );

    wp_enqueue_script(
        'bw-account-page',
        BW_MEW_URL . 'assets/js/bw-account-page.js',
        [],
        $js_version,
        true
    );
}

/**
 * Enqueue assets for the custom checkout layout and expose colors as CSS variables.
 */
function bw_mew_enqueue_checkout_assets() {
    if ( ! bw_mew_is_checkout_request() ) {
        return;
    }

    $css_file = BW_MEW_PATH . 'assets/css/bw-checkout.css';
    $js_file  = BW_MEW_PATH . 'assets/js/bw-checkout.js';
    $version  = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';
    $settings = bw_mew_get_checkout_settings();

    wp_enqueue_style(
        'bw-checkout',
        BW_MEW_URL . 'assets/css/bw-checkout.css',
        [],
        $version
    );

    if ( file_exists( $js_file ) ) {
        $js_version   = filemtime( $js_file );
        $dependencies = [ 'jquery' ];

        if ( wp_script_is( 'wc-checkout', 'registered' ) ) {
            wp_enqueue_script( 'wc-checkout' );
            $dependencies[] = 'wc-checkout';
        }

        wp_enqueue_script(
            'bw-checkout',
            BW_MEW_URL . 'assets/js/bw-checkout.js',
            $dependencies,
            $js_version,
            true
        );

        wp_localize_script(
            'bw-checkout',
            'bwCheckoutParams',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'bw-checkout-nonce' ),
            )
        );
    }

    $inline_styles = '.bw-checkout-form{--bw-checkout-left-bg:' . esc_attr( $settings['left_bg'] ) . ';--bw-checkout-right-bg:' . esc_attr( $settings['right_bg'] ) . ';--bw-checkout-border-color:' . esc_attr( $settings['border_color'] ) . ';}';

    wp_add_inline_style( 'bw-checkout', $inline_styles );
}

/**
 * Add a specific body class and hide theme wrappers on the custom login page.
 */
function bw_mew_prepare_account_page_layout() {
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || is_user_logged_in() ) {
        return;
    }

    add_filter( 'woocommerce_show_page_title', '__return_false' );
    add_filter( 'body_class', static function ( $classes ) {
        $classes[] = 'bw-account-login-only';
        return $classes;
    } );

    // Ensure the hiding styles are available even if the theme prints header/footer.
    add_action( 'wp_enqueue_scripts', static function () {
        if ( wp_style_is( 'bw-account-page', 'enqueued' ) ) {
            $css = 'body.bw-account-login-only header, body.bw-account-login-only .site-header, body.bw-account-login-only footer, body.bw-account-login-only .site-footer, body.bw-account-login-only .woocommerce-breadcrumb, body.bw-account-login-only .page-title, body.bw-account-login-only .entry-title { display: none !important; } body.bw-account-login-only .entry-content { padding: 0; margin: 0; } body.bw-account-login-only .site-content, body.bw-account-login-only .content-area, body.bw-account-login-only .site-main { padding: 0; margin: 0; }';
            wp_add_inline_style( 'bw-account-page', $css );
        }
    }, 25 );
}

/**
 * Hide checkout notices and prepare layout.
 */
function bw_mew_prepare_checkout_layout() {
    if ( ! bw_mew_is_checkout_request() || ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) ) {
        return;
    }

    // Remove all WooCommerce notices from checkout page
    remove_action( 'woocommerce_before_checkout_form', 'woocommerce_output_all_notices', 10 );

    // Remove "Have a coupon?" banner that appears above checkout form
    remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

    // Avoid rendering the payment section (and its button) twice by keeping it only in the left column.
    remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
}

/**
 * Check if the current request should be treated as checkout.
 *
 * @return bool
 */
function bw_mew_is_checkout_request() {
    if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_cart() ) {
        return true;
    }

    if ( function_exists( 'is_page' ) ) {
        $checkout_page_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'checkout' ) : 0;
        if ( $checkout_page_id && is_page( $checkout_page_id ) ) {
            return true;
        }
    }

    if ( ! empty( $_POST['apply_coupon'] ) || ! empty( $_POST['woocommerce-apply-coupon-nonce'] ) ) {
        return true;
    }

    return false;
}

if ( ! function_exists( 'bw_mew_normalize_checkout_column_widths' ) ) {
    /**
     * Normalize checkout column widths ensuring sane bounds and total of 100%.
     *
     * @param int $left  Desired left column percentage.
     * @param int $right Desired right column percentage.
     *
     * @return array{left:int,right:int}
     */
    function bw_mew_normalize_checkout_column_widths( $left, $right ) {
        $left  = min( 90, max( 10, absint( $left ) ) );
        $right = min( 90, max( 10, absint( $right ) ) );

        if ( ( $left + $right ) > 100 ) {
            $total = $left + $right;
            $left  = (int) round( ( $left / $total ) * 100 );
            $right = 100 - $left;
        }

        return [
            'left'  => $left,
            'right' => $right,
        ];
    }
}

/**
 * Hide the standard WooCommerce notice wrapper on single product pages only.
 */
function bw_mew_hide_single_product_notices() {
    if ( ! function_exists( 'is_product' ) || ! is_product() ) {
        return;
    }

    remove_action( 'woocommerce_before_single_product', 'woocommerce_output_all_notices', 10 );
    remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_output_all_notices', 10 );
}

/**
 * Handle social login start and callback.
 *
 * @deprecated Use BW_Social_Login class instead.
 */
function bw_mew_handle_social_login_requests() {
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
        return;
    }

    if ( isset( $_GET['bw_social_login'] ) ) {
        bw_mew_social_login_redirect( sanitize_key( wp_unslash( $_GET['bw_social_login'] ) ) );
    }

    if ( isset( $_GET['bw_social_login_callback'] ) ) {
        bw_mew_process_social_login_callback( sanitize_key( wp_unslash( $_GET['bw_social_login_callback'] ) ) );
    }
}

/**
 * Sync cart quantities during checkout AJAX refreshes so totals update immediately.
 *
 * @param string $posted_data Serialized form data.
 */
function bw_mew_sync_checkout_cart_quantities( $posted_data ) {
    if ( empty( $posted_data ) || ! WC()->cart ) {
        return;
    }

    parse_str( $posted_data, $parsed );

    if ( empty( $parsed['cart'] ) || ! is_array( $parsed['cart'] ) ) {
        return;
    }

    foreach ( $parsed['cart'] as $cart_item_key => $values ) {
        if ( ! isset( $values['qty'] ) ) {
            continue;
        }

        $qty = max( 0, wc_stock_amount( wp_unslash( $values['qty'] ) ) );
        WC()->cart->set_quantity( $cart_item_key, $qty, false );
    }

    WC()->cart->calculate_totals();
}

/**
 * Build social login start URL.
 *
 * @param string $provider Social provider key.
 * @return string
 */
function bw_mew_get_social_login_url( $provider ) {
    if ( ! class_exists( 'BW_Social_Login' ) ) {
        return '';
    }

    return BW_Social_Login::get_login_url( $provider );
}

/**
 * Get the callback URL for the provider.
 *
 * @param string $provider Provider key.
 * @return string
 */
function bw_mew_get_social_redirect_uri( $provider ) {
    if ( ! class_exists( 'BW_Social_Login' ) ) {
        return '';
    }

    return BW_Social_Login::get_redirect_uri( $provider );
}

/**
 * Retrieve checkout style and content options.
 *
 * @return array{logo:string,logo_align:string,page_bg:string,grid_bg:string,left_bg:string,right_bg:string,border_color:string,legal_text:string,left_width:int,right_width:int,thumb_ratio:string,thumb_width:int,right_sticky_top:int,right_padding_top:int,right_padding_right:int,right_padding_bottom:int,right_padding_left:int,footer_copyright:string,show_return_to_shop:string}
 */
function bw_mew_get_checkout_settings() {
    $defaults = [
        'logo'                => '',
        'logo_align'          => 'left',
        'page_bg'             => '#ffffff',
        'grid_bg'             => '#ffffff',
        'logo_width'          => 200,
        'logo_padding_top'    => 0,
        'logo_padding_right'  => 0,
        'logo_padding_bottom' => 30,
        'logo_padding_left'   => 0,
        'show_order_heading'  => '1',
        'left_bg'             => '#ffffff',
        'right_bg'            => 'transparent',
        'border_color'        => '#262626',
        'legal_text'          => '',
        'left_width'          => 62,
        'right_width'         => 38,
        'thumb_ratio'         => 'square',
        'thumb_width'         => 110,
        'right_sticky_top'    => 20,
        'right_margin_top'    => 0,
        'right_padding_top'   => 0,
        'right_padding_right' => 0,
        'right_padding_bottom'=> 0,
        'right_padding_left'  => 28,
        'footer_copyright'    => '',
        'show_return_to_shop' => '1',
    ];

    $settings = [
        'logo'                => esc_url_raw( get_option( 'bw_checkout_logo', $defaults['logo'] ) ),
        'logo_align'          => sanitize_key( get_option( 'bw_checkout_logo_align', $defaults['logo_align'] ) ),
        'logo_width'          => absint( get_option( 'bw_checkout_logo_width', $defaults['logo_width'] ) ),
        'page_bg'             => sanitize_hex_color( get_option( 'bw_checkout_page_bg', get_option( 'bw_checkout_page_bg_color', $defaults['page_bg'] ) ) ),
        'grid_bg'             => sanitize_hex_color( get_option( 'bw_checkout_grid_bg', get_option( 'bw_checkout_grid_bg_color', $defaults['grid_bg'] ) ) ),
        'logo_padding_top'    => absint( get_option( 'bw_checkout_logo_padding_top', $defaults['logo_padding_top'] ) ),
        'logo_padding_right'  => absint( get_option( 'bw_checkout_logo_padding_right', $defaults['logo_padding_right'] ) ),
        'logo_padding_bottom' => absint( get_option( 'bw_checkout_logo_padding_bottom', $defaults['logo_padding_bottom'] ) ),
        'logo_padding_left'   => absint( get_option( 'bw_checkout_logo_padding_left', $defaults['logo_padding_left'] ) ),
        'show_order_heading'  => get_option( 'bw_checkout_show_order_heading', $defaults['show_order_heading'] ),
        'left_bg'             => sanitize_hex_color( get_option( 'bw_checkout_left_bg_color', $defaults['left_bg'] ) ),
        'right_bg'            => sanitize_hex_color( get_option( 'bw_checkout_right_bg_color', $defaults['right_bg'] ) ),
        'border_color'        => sanitize_hex_color( get_option( 'bw_checkout_border_color', $defaults['border_color'] ) ),
        'legal_text'          => get_option( 'bw_checkout_legal_text', $defaults['legal_text'] ),
        'left_width'          => absint( get_option( 'bw_checkout_left_width', $defaults['left_width'] ) ),
        'right_width'         => absint( get_option( 'bw_checkout_right_width', $defaults['right_width'] ) ),
        'thumb_ratio'         => sanitize_key( get_option( 'bw_checkout_thumb_ratio', $defaults['thumb_ratio'] ) ),
        'thumb_width'         => absint( get_option( 'bw_checkout_thumb_width', $defaults['thumb_width'] ) ),
        'right_sticky_top'    => absint( get_option( 'bw_checkout_right_sticky_top', $defaults['right_sticky_top'] ) ),
        'right_margin_top'    => absint( get_option( 'bw_checkout_right_margin_top', $defaults['right_margin_top'] ) ),
        'right_padding_top'   => absint( get_option( 'bw_checkout_right_padding_top', $defaults['right_padding_top'] ) ),
        'right_padding_right' => absint( get_option( 'bw_checkout_right_padding_right', $defaults['right_padding_right'] ) ),
        'right_padding_bottom'=> absint( get_option( 'bw_checkout_right_padding_bottom', $defaults['right_padding_bottom'] ) ),
        'right_padding_left'  => absint( get_option( 'bw_checkout_right_padding_left', $defaults['right_padding_left'] ) ),
        'footer_copyright'    => get_option( 'bw_checkout_footer_copyright_text', $defaults['footer_copyright'] ),
        'show_return_to_shop' => get_option( 'bw_checkout_show_return_to_shop', $defaults['show_return_to_shop'] ),
    ];

    $settings['logo_align']   = in_array( $settings['logo_align'], [ 'left', 'center', 'right' ], true ) ? $settings['logo_align'] : $defaults['logo_align'];
    $settings['page_bg']      = $settings['page_bg'] ?: $defaults['page_bg'];
    $settings['grid_bg']      = $settings['grid_bg'] ?: $defaults['grid_bg'];
    $settings['left_bg']      = $settings['left_bg'] ?: $defaults['left_bg'];
    $settings['right_bg']     = $settings['right_bg'] ?: $defaults['right_bg'];
    $settings['border_color'] = $settings['border_color'] ?: $defaults['border_color'];
    $settings['thumb_ratio']  = in_array( $settings['thumb_ratio'], [ 'square', 'portrait', 'landscape' ], true ) ? $settings['thumb_ratio'] : $defaults['thumb_ratio'];

    // Validate thumb_width bounds
    if ( $settings['thumb_width'] < 50 ) {
        $settings['thumb_width'] = 50;
    }
    if ( $settings['thumb_width'] > 300 ) {
        $settings['thumb_width'] = 300;
    }

    if ( function_exists( 'bw_mew_normalize_checkout_column_widths' ) ) {
        $normalized               = bw_mew_normalize_checkout_column_widths( $settings['left_width'], $settings['right_width'] );
        $settings['left_width']   = $normalized['left'];
        $settings['right_width']  = $normalized['right'];
    }

    if ( function_exists( 'bw_mew_normalize_checkout_column_widths' ) ) {
        $normalized               = bw_mew_normalize_checkout_column_widths( $settings['left_width'], $settings['right_width'] );
        $settings['left_width']   = $normalized['left'];
        $settings['right_width']  = $normalized['right'];
    }

    $settings['footer_copyright']    = wp_kses_post( $settings['footer_copyright'] );
    $settings['show_return_to_shop'] = '1' === (string) $settings['show_return_to_shop'] ? '1' : '0';

    return $settings;
}

/**
 * DEPRECATED FUNCTIONS
 *
 * The following functions have been moved to the BW_Social_Login class.
 * They are kept here as deprecated stubs for backward compatibility.
 * Use BW_Social_Login class methods directly instead.
 */

/**
 * @deprecated Use BW_Social_Login::start_oauth_flow() instead.
 */
function bw_mew_social_login_redirect( $provider ) {
    // Deprecated: Handled by BW_Social_Login class.
}

/**
 * @deprecated Use BW_Social_Login::process_oauth_callback() instead.
 */
function bw_mew_process_social_login_callback( $provider ) {
    // Deprecated: Handled by BW_Social_Login class.
}

/**
 * @deprecated Use BW_Social_Login::exchange_facebook_code() instead.
 */
function bw_mew_exchange_facebook_code( $code, $redirect_uri ) {
    // Deprecated: Handled by BW_Social_Login class.
    return new WP_Error( 'bw_deprecated', __( 'This function is deprecated.', 'bw' ) );
}

/**
 * @deprecated Use BW_Social_Login::exchange_google_code() instead.
 */
function bw_mew_exchange_google_code( $code, $redirect_uri ) {
    // Deprecated: Handled by BW_Social_Login class.
    return new WP_Error( 'bw_deprecated', __( 'This function is deprecated.', 'bw' ) );
}

/**
 * @deprecated Use BW_Social_Login::login_or_register_user() instead.
 */
function bw_mew_login_or_register_social_user( $email, $name ) {
    // Deprecated: Handled by BW_Social_Login class.
    return new WP_Error( 'bw_deprecated', __( 'This function is deprecated.', 'bw' ) );
}

/**
 * Retrieve the passwordless URL if configured.
 *
 * @return string
 */
function bw_mew_get_passwordless_url() {
    $url = get_option( 'bw_account_passwordless_url', '' );

    if ( ! empty( $url ) ) {
        return esc_url( $url );
    }

    return '';
}

/**
 * AJAX handler to remove coupon from cart.
 */
function bw_mew_ajax_remove_coupon() {
    check_ajax_referer( 'bw-checkout-nonce', 'nonce' );

    if ( ! class_exists( 'WooCommerce' ) || ! WC()->cart ) {
        wp_send_json_error( array( 'message' => __( 'Cart not available.', 'woocommerce' ) ) );
    }

    $coupon_code = isset( $_POST['coupon'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon'] ) ) : '';

    if ( empty( $coupon_code ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid coupon code.', 'woocommerce' ) ) );
    }

    // Verify the coupon is actually applied before attempting removal
    $applied_coupons = WC()->cart->get_applied_coupons();
    if ( ! in_array( strtolower( $coupon_code ), array_map( 'strtolower', $applied_coupons ), true ) ) {
        wp_send_json_error( array( 'message' => __( 'Coupon is not applied.', 'woocommerce' ) ) );
    }

    // Remove the coupon
    $removed = WC()->cart->remove_coupon( $coupon_code );

    if ( ! $removed ) {
        wp_send_json_error( array( 'message' => __( 'Coupon could not be removed.', 'woocommerce' ) ) );
    }

    // Calculate totals after removing coupon
    WC()->cart->calculate_totals();

    // CRITICAL: Persist the cart session so the coupon removal is saved
    if ( WC()->session ) {
        WC()->cart->persistent_cart_update();
        WC()->session->save_data();
    }

    // Clear any cart-related caches
    wc_clear_notices();

    wp_send_json_success( array(
        'message'         => __( 'Coupon removed successfully.', 'woocommerce' ),
        'applied_coupons' => WC()->cart->get_applied_coupons(),
    ) );
}
