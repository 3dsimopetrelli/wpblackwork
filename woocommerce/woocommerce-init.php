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

    $my_account_file   = BW_MEW_PATH . 'includes/woocommerce-overrides/class-bw-my-account.php';
    $supabase_file     = BW_MEW_PATH . 'includes/woocommerce-overrides/class-bw-supabase-auth.php';

    if ( file_exists( $my_account_file ) ) {
        require_once $my_account_file;
    }

    if ( file_exists( $supabase_file ) ) {
        require_once $supabase_file;
    }

    add_filter( 'woocommerce_locate_template', 'bw_mew_locate_template', 1, 3 );
    add_action( 'wp_enqueue_scripts', 'bw_mew_enqueue_related_products_assets', 30 );
    add_action( 'wp_enqueue_scripts', 'bw_mew_enqueue_account_page_assets', 20 );
    add_action( 'wp_enqueue_scripts', 'bw_mew_enqueue_checkout_assets', 20 );
    add_filter( 'woocommerce_locate_core_template', 'bw_mew_locate_template', 1, 3 );
    add_action( 'template_redirect', 'bw_mew_prepare_account_page_layout', 9 );
    add_action( 'template_redirect', 'bw_mew_prepare_checkout_layout', 9 );
    add_action( 'template_redirect', 'bw_mew_prepare_theme_title_bypass', 8 );
    add_action( 'template_redirect', 'bw_mew_hide_single_product_notices', 9 );
    add_action( 'woocommerce_checkout_update_order_review', 'bw_mew_sync_checkout_cart_quantities', 10, 1 );
    add_action( 'wp_ajax_bw_apply_coupon', 'bw_mew_ajax_apply_coupon' );
    add_action( 'wp_ajax_nopriv_bw_apply_coupon', 'bw_mew_ajax_apply_coupon' );
    add_action( 'wp_ajax_bw_remove_coupon', 'bw_mew_ajax_remove_coupon' );
    add_action( 'wp_ajax_nopriv_bw_remove_coupon', 'bw_mew_ajax_remove_coupon' );
    add_filter( 'the_title', 'bw_mew_filter_account_page_title', 10, 2 );
    add_filter( 'woocommerce_available_payment_gateways', 'bw_mew_hide_paypal_advanced_card_processing' );
    add_filter( 'wc_stripe_elements_options', 'bw_mew_customize_stripe_elements_style' );
    add_filter( 'wc_stripe_upe_params', 'bw_mew_customize_stripe_upe_appearance' );
}
add_action( 'plugins_loaded', 'bw_mew_initialize_woocommerce_overrides' );

/**
 * Remove the theme-rendered page title on WooCommerce account pages.
 *
 * @param string $title   The post title.
 * @param int    $post_id Post ID.
 *
 * @return string
 */
function bw_mew_filter_account_page_title( $title, $post_id ) {
    if ( is_admin() ) {
        return $title;
    }

    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
        return $title;
    }

    if ( ! is_main_query() || ! in_the_loop() ) {
        return $title;
    }

    if ( ! is_singular() ) {
        return $title;
    }

    return '';
}

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

    wp_enqueue_script(
        'supabase-js',
        'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2',
        [],
        null,
        true
    );

    wp_enqueue_style(
        'bw-account-page',
        BW_MEW_URL . 'assets/css/bw-account-page.css',
        [],
        $css_version
    );

    wp_enqueue_script(
        'bw-account-page',
        BW_MEW_URL . 'assets/js/bw-account-page.js',
        [ 'supabase-js' ],
        $js_version,
        true
    );

    wp_localize_script(
        'bw-account-page',
        'bwAccountAuth',
        [
            'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
            'nonce'           => wp_create_nonce( 'bw-supabase-login' ),
            'supabaseWithOidc' => (int) get_option( 'bw_supabase_with_plugins', 0 ),
            'loginMode'       => get_option( 'bw_supabase_login_mode', 'native' ),
            'oidcAuthUrl'      => function_exists( 'bw_oidc_get_auth_url' ) ? bw_oidc_get_auth_url() : '',
            'autoLoginAfterConfirm' => (int) get_option( 'bw_supabase_auto_login_after_confirm', 0 ),
            'projectUrl'      => get_option( 'bw_supabase_project_url', '' ),
            'anonKey'         => get_option( 'bw_supabase_anon_key', '' ),
            'magicLinkRedirectUrl' => get_option( 'bw_supabase_magic_link_redirect_url', site_url( '/my-account/' ) ),
            'oauthRedirectUrl' => get_option( 'bw_supabase_oauth_redirect_url', site_url( '/my-account/' ) ),
            'resetRedirectUrl' => site_url( '/my-account/' ),
            'magicLinkEnabled' => (int) get_option( 'bw_supabase_magic_link_enabled', 1 ),
            'oauthGoogleEnabled' => (int) get_option( 'bw_supabase_oauth_google_enabled', 1 ),
            'oauthFacebookEnabled' => (int) get_option( 'bw_supabase_oauth_facebook_enabled', 1 ),
            'oauthAppleEnabled' => (int) get_option( 'bw_supabase_oauth_apple_enabled', 0 ),
            'passwordLoginEnabled' => (int) get_option( 'bw_supabase_login_password_enabled', 1 ),
            'otpAllowSignup' => (int) get_option( 'bw_supabase_otp_allow_signup', 1 ),
            'debug' => (int) get_option( 'bw_supabase_debug_log', 0 ),
            'cookieBase' => sanitize_key( (string) get_option( 'bw_supabase_jwt_cookie_name', 'bw_supabase_session' ) ) ?: 'bw_supabase_session',
            'messages' => [
                'missingConfig' => esc_html__( 'Supabase configuration is missing.', 'bw' ),
                'enterEmail' => esc_html__( 'Please enter your email address.', 'bw' ),
                'magicLinkError' => esc_html__( 'Unable to send magic link.', 'bw' ),
                'loginError' => esc_html__( 'Unable to login.', 'bw' ),
                'otpSent' => esc_html__( 'If the email is valid, we sent you a code.', 'bw' ),
                'enterOtp' => esc_html__( 'Please enter the 6-digit code.', 'bw' ),
                'otpVerifyError' => esc_html__( 'Unable to verify the code.', 'bw' ),
                'otpInvalid' => esc_html__( 'Invalid or expired code. Please try again.', 'bw' ),
                'otpResent' => esc_html__( 'We sent you a new code.', 'bw' ),
                'otpResendError' => esc_html__( 'Unable to resend the code right now.', 'bw' ),
                'otpSignupDisabledNeutral' => esc_html__( 'We could not send a code. Please try a different email.', 'bw' ),
                'otpRateLimit' => esc_html__( 'Too many attempts. Please wait and try again.', 'bw' ),
                'otpRedirectInvalid' => esc_html__( 'Login is unavailable right now. Please contact support.', 'bw' ),
                'createPasswordError' => esc_html__( 'Unable to update password.', 'bw' ),
                'passwordMismatch' => esc_html__( 'Passwords do not match.', 'bw' ),
                'passwordRules' => esc_html__( 'Please meet all password requirements.', 'bw' ),
                'oauthAppleSoon' => esc_html__( 'Apple login is coming soon.', 'bw' ),
                'supabaseSdkMissing' => esc_html__( 'Supabase JS SDK is not loaded.', 'bw' ),
            ],
        ]
    );
}

/**
 * Enqueue Supabase invite token bridge on the frontend.
 */
function bw_mew_enqueue_supabase_bridge() {
    if ( is_user_logged_in() ) {
        return;
    }

    $js_file    = BW_MEW_PATH . 'assets/js/bw-supabase-bridge.js';
    if ( ! file_exists( $js_file ) ) {
        return;
    }

    wp_enqueue_script(
        'bw-supabase-bridge',
        BW_MEW_URL . 'assets/js/bw-supabase-bridge.js',
        [ 'supabase-js' ],
        filemtime( $js_file ),
        true
    );

    wp_localize_script(
        'bw-supabase-bridge',
        'bwSupabaseBridge',
        [
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'nonce'         => wp_create_nonce( 'bw-supabase-login' ),
            'setPasswordUrl' => wc_get_account_endpoint_url( 'set-password' ),
            'projectUrl'    => get_option( 'bw_supabase_project_url', '' ),
            'anonKey'       => get_option( 'bw_supabase_anon_key', '' ),
            'debug'         => (int) get_option( 'bw_supabase_debug_log', 0 ),
        ]
    );
}
add_action( 'wp_enqueue_scripts', 'bw_mew_enqueue_supabase_bridge', 20 );

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

    // FORCE CACHE BUST - Remove after testing
    $version .= '.forcebust';
    $settings = bw_mew_get_checkout_settings();

    wp_enqueue_style(
        'bw-checkout',
        BW_MEW_URL . 'assets/css/bw-checkout.css',
        [],
        $version
    );

    if ( file_exists( $js_file ) ) {
        $js_version   = filemtime( $js_file ) . '.forcebust';
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

    // Enqueue payment methods assets (Shopify-style accordion)
    $payment_css_file = BW_MEW_PATH . 'assets/css/bw-payment-methods.css';
    $payment_js_file  = BW_MEW_PATH . 'assets/js/bw-payment-methods.js';

    if ( file_exists( $payment_css_file ) ) {
        wp_enqueue_style(
            'bw-payment-methods',
            BW_MEW_URL . 'assets/css/bw-payment-methods.css',
            [ 'bw-checkout' ],
            filemtime( $payment_css_file )
        );
    }

    if ( file_exists( $payment_js_file ) ) {
        wp_enqueue_script(
            'bw-payment-methods',
            BW_MEW_URL . 'assets/js/bw-payment-methods.js',
            [ 'jquery', 'wc-checkout' ],
            filemtime( $payment_js_file ),
            true
        );
    }

    // Enqueue checkout notices assets (moves notices into left column with custom styling)
    $notices_css_file = BW_MEW_PATH . 'assets/css/bw-checkout-notices.css';
    $notices_js_file  = BW_MEW_PATH . 'assets/js/bw-checkout-notices.js';

    if ( file_exists( $notices_css_file ) ) {
        wp_enqueue_style(
            'bw-checkout-notices',
            BW_MEW_URL . 'assets/css/bw-checkout-notices.css',
            [ 'bw-checkout' ],
            filemtime( $notices_css_file )
        );
    }

    if ( file_exists( $notices_js_file ) ) {
        wp_enqueue_script(
            'bw-checkout-notices',
            BW_MEW_URL . 'assets/js/bw-checkout-notices.js',
            [ 'jquery', 'wc-checkout' ],
            filemtime( $notices_js_file ),
            true
        );
    }

    // Enqueue Stripe UPE cleaner to hide "Card" accordion header
    $stripe_upe_cleaner_file = BW_MEW_PATH . 'assets/js/bw-stripe-upe-cleaner.js';
    if ( file_exists( $stripe_upe_cleaner_file ) ) {
        wp_enqueue_script(
            'bw-stripe-upe-cleaner',
            BW_MEW_URL . 'assets/js/bw-stripe-upe-cleaner.js',
            [ 'jquery', 'wc-checkout' ],
            filemtime( $stripe_upe_cleaner_file ),
            true
        );
    }
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
 * Hide the default page title on logged-in account pages.
 */
function bw_mew_hide_logged_in_account_title() {
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_user_logged_in() ) {
        return;
    }

    add_filter( 'woocommerce_show_page_title', '__return_false' );
}
add_action( 'template_redirect', 'bw_mew_hide_logged_in_account_title', 9 );

/**
 * Determine whether the theme title bypass is enabled for a context.
 *
 * @param string $context Context key (account|checkout).
 *
 * @return bool
 */
function bw_mew_is_theme_title_bypass_enabled( $context ) {
    $enabled = false;

    if ( 'account' === $context ) {
        $enabled = function_exists( 'is_account_page' ) && is_account_page() && is_user_logged_in();
    } elseif ( 'checkout' === $context ) {
        $enabled = bw_mew_is_checkout_request()
            && ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-received' ) );
    }

    return (bool) apply_filters( 'bw_mew_enable_theme_title_bypass', $enabled, $context );
}

/**
 * Prepare theme title bypass helpers on account/checkout pages.
 */
function bw_mew_prepare_theme_title_bypass() {
    if ( ! bw_mew_is_theme_title_bypass_enabled( 'account' ) && ! bw_mew_is_theme_title_bypass_enabled( 'checkout' ) ) {
        return;
    }

    add_filter( 'body_class', 'bw_mew_add_theme_title_bypass_class' );
    add_action( 'wp_enqueue_scripts', 'bw_mew_dequeue_theme_title_styles', 100 );
    add_action( 'wp_enqueue_scripts', 'bw_mew_enqueue_theme_title_bypass_css', 100 );
}

/**
 * Add a body class for the theme title bypass scope.
 *
 * @param array $classes Body classes.
 *
 * @return array
 */
function bw_mew_add_theme_title_bypass_class( $classes ) {
    if ( bw_mew_is_theme_title_bypass_enabled( 'account' ) || bw_mew_is_theme_title_bypass_enabled( 'checkout' ) ) {
        $classes[] = 'bw-theme-title-bypass';
    }

    return $classes;
}

/**
 * Dequeue parent theme title styles on targeted pages.
 */
function bw_mew_dequeue_theme_title_styles() {
    if ( ! bw_mew_is_theme_title_bypass_enabled( 'account' ) && ! bw_mew_is_theme_title_bypass_enabled( 'checkout' ) ) {
        return;
    }

    $handles = (array) apply_filters( 'bw_mew_theme_title_bypass_handles', [], [
        'account'  => bw_mew_is_theme_title_bypass_enabled( 'account' ),
        'checkout' => bw_mew_is_theme_title_bypass_enabled( 'checkout' ),
    ] );

    foreach ( $handles as $handle ) {
        if ( ! is_string( $handle ) || '' === $handle ) {
            continue;
        }

        wp_dequeue_style( $handle );
        wp_deregister_style( $handle );
    }
}

/**
 * Add scoped reset styles to neutralize theme title CSS on targeted pages.
 */
function bw_mew_enqueue_theme_title_bypass_css() {
    if ( ! bw_mew_is_theme_title_bypass_enabled( 'account' ) && ! bw_mew_is_theme_title_bypass_enabled( 'checkout' ) ) {
        return;
    }

    $css = '.bw-theme-title-bypass .page-header .entry-title,'
        . '.bw-theme-title-bypass .entry-header .entry-title,'
        . '.bw-theme-title-bypass .page-title{'
        . 'font-size:inherit;line-height:inherit;letter-spacing:normal;'
        . 'text-align:inherit;font-weight:inherit;}';

    if ( bw_mew_is_theme_title_bypass_enabled( 'account' ) && wp_style_is( 'bw-my-account', 'enqueued' ) ) {
        wp_add_inline_style( 'bw-my-account', $css );
    }

    if ( bw_mew_is_theme_title_bypass_enabled( 'checkout' ) && wp_style_is( 'bw-checkout', 'enqueued' ) ) {
        wp_add_inline_style( 'bw-checkout', $css );
    }
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
 * Render minimal checkout header with logo and cart icon.
 */
function bw_mew_render_checkout_header() {
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
        return;
    }

    // Get checkout settings
    $settings = bw_mew_get_checkout_settings();
    $logo_align = ! empty( $settings['logo_align'] ) ? $settings['logo_align'] : 'center';
    $logo_width = ! empty( $settings['logo_width'] ) ? absint( $settings['logo_width'] ) : 200;
    $logo_padding_top = isset( $settings['logo_padding_top'] ) ? absint( $settings['logo_padding_top'] ) : 0;
    $logo_padding_right = isset( $settings['logo_padding_right'] ) ? absint( $settings['logo_padding_right'] ) : 0;
    $logo_padding_bottom = isset( $settings['logo_padding_bottom'] ) ? absint( $settings['logo_padding_bottom'] ) : 0;
    $logo_padding_left = isset( $settings['logo_padding_left'] ) ? absint( $settings['logo_padding_left'] ) : 0;

    // Get logo - prefer theme custom logo, fallback to checkout settings logo
    $logo_url = '';
    $home_url = home_url( '/' );

    if ( function_exists( 'has_custom_logo' ) && has_custom_logo() ) {
        $custom_logo_id = get_theme_mod( 'custom_logo' );
        if ( $custom_logo_id ) {
            $logo_data = wp_get_attachment_image_src( $custom_logo_id, 'full' );
            if ( $logo_data ) {
                $logo_url = $logo_data[0];
            }
        }
    }

    // Fallback to checkout settings logo if theme logo not available
    if ( empty( $logo_url ) ) {
        $logo_url = ! empty( $settings['logo'] ) ? $settings['logo'] : '';
    }

    // Get cart URL
    $cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );

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
    if ( ! empty( $logo_url ) ) :
        ?>
        <div class="bw-minimal-checkout-header">
            <div class="bw-minimal-checkout-header__inner bw-minimal-checkout-header__inner--<?php echo esc_attr( $logo_align ); ?>">
                <a href="<?php echo esc_url( $home_url ); ?>" class="bw-minimal-checkout-header__logo" style="<?php echo esc_attr( $logo_styles ); ?>">
                    <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" />
                </a>
                <a href="<?php echo esc_url( $cart_url ); ?>" class="bw-minimal-checkout-header__cart" aria-label="<?php esc_attr_e( 'View cart', 'woocommerce' ); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="4" y="6" width="16" height="14" rx="2" stroke="currentColor" stroke-width="1.5" fill="none"/>
                        <path d="M8 6V5C8 3.34315 9.34315 2 11 2H13C14.6569 2 16 3.34315 16 5V6" stroke="currentColor" stroke-width="1.5" fill="none"/>
                    </svg>
                </a>
            </div>
        </div>
        <?php
    endif;
}

/**
 * Render custom express checkout divider with perfect continuous lines.
 * Replaces the default WCPay separator with a cleaner implementation.
 */
function bw_mew_render_express_divider() {
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
        return;
    }

    // Check if WCPay express checkout separator exists in the page
    // If it does, our CSS will hide it and show this custom one instead
    ?>
    <div class="bw-express-divider">
        <span>OR</span>
    </div>
    <?php
}
add_action( 'woocommerce_checkout_before_customer_details', 'bw_mew_render_express_divider', 100 );

/**
 * AJAX handler to remove coupon from cart.
 *
 * FIX: This handler now properly synchronizes with WooCommerce's checkout refresh mechanism
 * to prevent the coupon from re-applying after removal. The key changes:
 * 1. Force WC session commit BEFORE responding to ensure session persistence
 * 2. Trigger WooCommerce's 'removed_coupon' action for proper event integration
 * 3. Return hash to help client detect if cart state changed
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

    // Remove the coupon using WooCommerce's standard method
    $removed = WC()->cart->remove_coupon( $coupon_code );

    if ( ! $removed ) {
        wp_send_json_error( array( 'message' => __( 'Coupon could not be removed.', 'woocommerce' ) ) );
    }

    // Calculate totals after removing coupon
    WC()->cart->calculate_totals();

    // CRITICAL FIX: Force immediate session persistence with multiple safety checks
    // This ensures the coupon removal is saved BEFORE the checkout fragment refresh reads the session
    if ( WC()->session ) {
        // Save cart data to session immediately
        WC()->cart->persistent_cart_update();

        // Force session data write to database/storage
        WC()->session->save_data();

        // Additional safety: set the cart session explicitly
        WC()->session->set( 'cart', serialize( WC()->cart->get_cart_for_session() ) );
        WC()->session->set( 'applied_coupons', WC()->cart->get_applied_coupons() );
        WC()->session->set( 'coupon_discount_totals', WC()->cart->get_coupon_discount_totals() );
        WC()->session->set( 'coupon_discount_tax_totals', WC()->cart->get_coupon_discount_tax_totals() );

        // Force one more save to commit all the above
        WC()->session->save_data();
    }

    // Clear any cart-related caches that might cause stale data
    wc_clear_notices();

    // Trigger WooCommerce's standard removed_coupon action for proper event integration
    // This allows other plugins/code to react to coupon removal
    do_action( 'woocommerce_removed_coupon', $coupon_code );

    wp_send_json_success( array(
        'message'         => __( 'Coupon removed successfully.', 'woocommerce' ),
        'applied_coupons' => WC()->cart->get_applied_coupons(),
        'cart_hash'       => WC()->cart->get_cart_hash(), // Return hash to verify state change
    ) );
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
function bw_mew_ajax_apply_coupon() {
    check_ajax_referer( 'bw-checkout-nonce', 'nonce' );

    if ( ! class_exists( 'WooCommerce' ) || ! WC()->cart ) {
        wp_send_json_error( array( 'message' => __( 'Cart not available.', 'woocommerce' ) ) );
    }

    $coupon_code = isset( $_POST['coupon_code'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) : '';

    if ( empty( $coupon_code ) ) {
        wp_send_json_error( array( 'message' => __( 'Please enter a coupon code.', 'woocommerce' ) ) );
    }

    // Clear any existing notices to ensure clean validation
    wc_clear_notices();

    // Apply the coupon using WooCommerce's standard method
    $applied = WC()->cart->apply_coupon( $coupon_code );

    if ( ! $applied ) {
        // Get error message from WooCommerce notices
        $error_notices = wc_get_notices( 'error' );
        $error_message = __( 'Invalid coupon code.', 'woocommerce' );

        if ( ! empty( $error_notices ) && is_array( $error_notices ) ) {
            $first_error = reset( $error_notices );
            if ( is_array( $first_error ) && isset( $first_error['notice'] ) ) {
                $error_message = wp_strip_all_tags( (string) $first_error['notice'] );
            } elseif ( is_string( $first_error ) ) {
                $error_message = wp_strip_all_tags( $first_error );
            }
        }

        wc_clear_notices();
        wp_send_json_error( array( 'message' => $error_message ) );
    }

    // Calculate totals after applying coupon
    WC()->cart->calculate_totals();

    // CRITICAL FIX: Force immediate session persistence with multiple safety checks
    // This ensures the coupon application is saved BEFORE the checkout fragment refresh reads the session
    if ( WC()->session ) {
        // Save cart data to session immediately
        WC()->cart->persistent_cart_update();

        // Force session data write to database/storage
        WC()->session->save_data();

        // Additional safety: set the cart session explicitly
        WC()->session->set( 'cart', serialize( WC()->cart->get_cart_for_session() ) );
        WC()->session->set( 'applied_coupons', WC()->cart->get_applied_coupons() );
        WC()->session->set( 'coupon_discount_totals', WC()->cart->get_coupon_discount_totals() );
        WC()->session->set( 'coupon_discount_tax_totals', WC()->cart->get_coupon_discount_tax_totals() );

        // Force one more save to commit all the above
        WC()->session->save_data();
    }

    // Clear any notices
    wc_clear_notices();

    // Trigger WooCommerce's standard applied_coupon action for proper event integration
    // This allows other plugins/code to react to coupon application
    do_action( 'woocommerce_applied_coupon', $coupon_code );

    wp_send_json_success( array(
        'message'         => __( 'Coupon applied successfully.', 'woocommerce' ),
        'applied_coupons' => WC()->cart->get_applied_coupons(),
        'cart_hash'       => WC()->cart->get_cart_hash(), // Return hash to verify state change
    ) );
}

/**
 * Hide PayPal Advanced Card Processing gateway (duplicate of Stripe).
 * We use Stripe for all card payments.
 *
 * @param array $available_gateways Available payment gateways.
 * @return array Filtered payment gateways.
 */
function bw_mew_hide_paypal_advanced_card_processing( $available_gateways ) {
    if ( ! is_admin() && is_checkout() ) {
        // Remove PayPal Advanced Card Processing (ppcp-credit-card-gateway)
        // This is a duplicate of Stripe and causes confusion
        if ( isset( $available_gateways['ppcp-credit-card-gateway'] ) ) {
            unset( $available_gateways['ppcp-credit-card-gateway'] );
        }

        // Also remove WooCommerce Payments if present (another duplicate)
        if ( isset( $available_gateways['woocommerce_payments'] ) ) {
            unset( $available_gateways['woocommerce_payments'] );
        }
    }

    return $available_gateways;
}

/**
 * Customize Stripe Elements styling to match Shopify design.
 * Uses WooCommerce Stripe Gateway filter to configure appearance.
 *
 * @param array $options Stripe Elements options.
 * @return array Modified options with custom styling.
 */
function bw_mew_customize_stripe_elements_style( $options ) {
    // Shopify-style appearance configuration
    $options['appearance'] = array(
        'theme' => 'flat',
        'variables' => array(
            'colorPrimary' => '#000000',
            'colorBackground' => '#ffffff',
            'colorText' => '#1f2937',
            'colorDanger' => '#991b1b',
            'colorSuccess' => '#27ae60',
            'fontFamily' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            'fontSizeBase' => '15px',
            'fontWeightNormal' => '400',
            'fontWeightMedium' => '500',
            'borderRadius' => '8px',
            'spacingUnit' => '4px',
        ),
        'rules' => array(
            '.Input' => array(
                'border' => '1px solid #d1d5db',
                'boxShadow' => '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                'padding' => '16px 18px',
                'transition' => 'border-color 0.2s ease, box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
            ),
            '.Input:hover' => array(
                'borderColor' => '#9ca3af',
            ),
            '.Input:focus' => array(
                'borderColor' => '#3b82f6',
                'boxShadow' => '0 0 0 3px rgba(59, 130, 246, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                'outline' => 'none',
            ),
            '.Input--invalid' => array(
                'borderColor' => '#fecaca',
                'backgroundColor' => '#fef2f2',
            ),
            '.Label' => array(
                'display' => 'none',
            ),
        ),
    );

    return $options;
}

/**
 * Customize Stripe UPE (Unified Payment Element) appearance.
 * This filter modifies the JavaScript params passed to Stripe UPE iframe.
 *
 * @param array $params UPE initialization params.
 * @return array Modified params with custom appearance.
 */
function bw_mew_customize_stripe_upe_appearance( $params ) {
    // Add appearance configuration to UPE params
    $params['appearance'] = array(
        'theme' => 'flat',
        'variables' => array(
            'colorPrimary' => '#000000',
            'colorBackground' => '#ffffff',
            'colorText' => '#1f2937',
            'colorDanger' => '#991b1b',
            'colorSuccess' => '#27ae60',
            'fontFamily' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            'fontSizeBase' => '15px',
            'fontWeightNormal' => '400',
            'fontWeightMedium' => '500',
            'borderRadius' => '8px',
            'spacingUnit' => '4px',
        ),
        'rules' => array(
            '.Input' => array(
                'border' => '1px solid #d1d5db',
                'boxShadow' => '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                'padding' => '16px 18px',
            ),
            '.Input:hover' => array(
                'borderColor' => '#9ca3af',
            ),
            '.Input:focus' => array(
                'borderColor' => '#3b82f6',
                'boxShadow' => '0 0 0 3px rgba(59, 130, 246, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.05)',
            ),
            '.Input--invalid' => array(
                'borderColor' => '#fecaca',
                'backgroundColor' => '#fef2f2',
            ),
            '.Label' => array(
                'display' => 'none',
            ),
            // FIX 1: Error message icon positioning - use !important to override Stripe defaults
            '.Error' => array(
                'display' => 'flex',
                'flexDirection' => 'row',
                'alignItems' => 'flex-start',
                'gap' => '8px',
                'marginTop' => '8px',
                'fontSize' => '13px',
                'lineHeight' => '1.4',
                'color' => '#991b1b',
            ),
            '.ErrorIcon' => array(
                'flexShrink' => '0',
                'width' => '16px',
                'height' => '16px',
                'minWidth' => '16px',
                'marginTop' => '2px',
                'marginRight' => '0',
                'marginBottom' => '0',
                'marginLeft' => '0',
                'display' => 'inline-flex',
            ),
            '.ErrorText' => array(
                'flex' => '1 1 auto',
                'marginTop' => '0',
                'marginRight' => '0',
                'marginBottom' => '0',
                'marginLeft' => '0',
                'display' => 'inline-block',
            ),
            '.Tab' => array(
                'display' => 'none',
            ),
            '.TabLabel' => array(
                'display' => 'none',
            ),
            '.TabIcon' => array(
                'display' => 'none',
            ),
            '.Block' => array(
                'display' => 'none',
            ),
            '.AccordionItem' => array(
                'display' => 'none',
            ),
            '.AccordionItemHeader' => array(
                'display' => 'none',
            ),
            '.PickerItem' => array(
                'display' => 'none',
            ),
            '.PaymentMethod' => array(
                'padding' => '0',
                'border' => 'none',
            ),
            '.PaymentMethodHeader' => array(
                'display' => 'none',
            ),
        ),
    );

    // FIX 3: Configure fields to auto-collect billing details from WooCommerce checkout form
    // This prevents the "You specified 'never' but did not pass billing_details.name" error
    $params['fields'] = array(
        'billingDetails' => array(
            'name' => 'auto',    // Auto-collect from WC checkout form
            'email' => 'auto',   // Auto-collect from WC checkout form
            'phone' => 'auto',   // Auto-collect from WC checkout form
            'address' => array(
                'country' => 'auto',
                'line1' => 'auto',
                'line2' => 'auto',
                'city' => 'auto',
                'state' => 'auto',
                'postalCode' => 'auto',
            ),
        ),
    );

    return $params;
}
