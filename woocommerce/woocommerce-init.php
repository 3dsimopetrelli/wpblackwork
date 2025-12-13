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

    if ( file_exists( $my_account_file ) ) {
        require_once $my_account_file;
    }

    add_filter( 'woocommerce_locate_template', 'bw_mew_locate_template', 1, 3 );
    add_action( 'wp_enqueue_scripts', 'bw_mew_enqueue_related_products_assets', 30 );
    add_action( 'wp_enqueue_scripts', 'bw_mew_enqueue_account_page_assets', 20 );
    add_action( 'wp_enqueue_scripts', 'bw_mew_enqueue_checkout_assets', 20 );
    add_filter( 'woocommerce_locate_core_template', 'bw_mew_locate_template', 1, 3 );
    add_action( 'template_redirect', 'bw_mew_handle_social_login_requests', 5 );
    add_action( 'template_redirect', 'bw_mew_prepare_account_page_layout', 9 );
    add_action( 'template_redirect', 'bw_mew_prepare_checkout_layout', 9 );
    add_action( 'template_redirect', 'bw_mew_hide_single_product_notices', 9 );
    add_action( 'woocommerce_review_order_after_payment', 'bw_mew_render_checkout_legal_text', 5 );
    add_action( 'woocommerce_checkout_update_order_review', 'bw_mew_sync_checkout_cart_quantities', 10, 1 );
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
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_cart() ) {
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
        $js_version = filemtime( $js_file );
        wp_enqueue_script(
            'bw-checkout',
            BW_MEW_URL . 'assets/js/bw-checkout.js',
            [ 'jquery' ],
            $js_version,
            true
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
 * Move checkout notices inside the left column and keep AJAX updates working.
 */
function bw_mew_prepare_checkout_layout() {
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) ) {
        return;
    }

    remove_action( 'woocommerce_before_checkout_form', 'woocommerce_output_all_notices', 10 );
    add_action( 'bw_checkout_notices', 'woocommerce_output_all_notices', 10 );

    // Avoid rendering the payment section (and its button) twice by keeping it only in the left column.
    remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
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
 * Print the legal text block below the payment methods during checkout.
 */
function bw_mew_render_checkout_legal_text() {
    $settings = bw_mew_get_checkout_settings();

    if ( empty( $settings['legal_text'] ) ) {
        return;
    }

    echo '<div class="bw-checkout-legal-text">' . wp_kses_post( $settings['legal_text'] ) . '</div>';
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
 *
 * @return string
 */
function bw_mew_get_social_login_url( $provider ) {
    $allowed = [ 'facebook', 'google' ];
    if ( ! in_array( $provider, $allowed, true ) ) {
        return '';
    }

    return add_query_arg( 'bw_social_login', $provider, wc_get_page_permalink( 'myaccount' ) );
}

/**
 * Get the callback URL for the provider.
 *
 * @param string $provider Provider key.
 *
 * @return string
 */
function bw_mew_get_social_redirect_uri( $provider ) {
    $allowed = [ 'facebook', 'google' ];
    if ( ! in_array( $provider, $allowed, true ) ) {
        return '';
    }

    return add_query_arg( 'bw_social_login_callback', $provider, wc_get_page_permalink( 'myaccount' ) );
}

/**
 * Retrieve checkout style and content options.
 *
 * @return array{logo:string,left_bg:string,right_bg:string,border_color:string,legal_text:string}
 */
function bw_mew_get_checkout_settings() {
    $defaults = [
        'logo'         => '',
        'left_bg'      => '#ffffff',
        'right_bg'     => '#f7f7f7',
        'border_color' => '#e0e0e0',
        'legal_text'   => '',
    ];

    $settings = [
        'logo'         => esc_url_raw( get_option( 'bw_checkout_logo', $defaults['logo'] ) ),
        'left_bg'      => sanitize_hex_color( get_option( 'bw_checkout_left_bg_color', $defaults['left_bg'] ) ),
        'right_bg'     => sanitize_hex_color( get_option( 'bw_checkout_right_bg_color', $defaults['right_bg'] ) ),
        'border_color' => sanitize_hex_color( get_option( 'bw_checkout_border_color', $defaults['border_color'] ) ),
        'legal_text'   => get_option( 'bw_checkout_legal_text', $defaults['legal_text'] ),
    ];

    $settings['left_bg']      = $settings['left_bg'] ?: $defaults['left_bg'];
    $settings['right_bg']     = $settings['right_bg'] ?: $defaults['right_bg'];
    $settings['border_color'] = $settings['border_color'] ?: $defaults['border_color'];

    return $settings;
}

/**
 * Start OAuth flow by redirecting to the provider.
 *
 * @param string $provider Provider key.
 */
function bw_mew_social_login_redirect( $provider ) {
    $enabled     = (int) get_option( 'bw_account_' . $provider, 0 );
    $client_id   = 'facebook' === $provider ? get_option( 'bw_account_facebook_app_id', '' ) : get_option( 'bw_account_google_client_id', '' );
    $redirect    = bw_mew_get_social_redirect_uri( $provider );
    $account_url = wc_get_page_permalink( 'myaccount' );

    if ( ! $enabled || empty( $client_id ) || empty( $redirect ) ) {
        wc_add_notice( __( 'Social login is not available at the moment.', 'bw' ), 'error' );
        wp_safe_redirect( $account_url );
        exit;
    }

    $state = wp_generate_password( 12, false );
    set_transient( 'bw_social_state_' . $state, [ 'provider' => $provider ], MINUTE_IN_SECONDS * 15 );

    if ( 'facebook' === $provider ) {
        $auth_url = add_query_arg(
            [
                'client_id'     => $client_id,
                'redirect_uri'  => $redirect,
                'state'         => $state,
                'scope'         => 'email',
                'response_type' => 'code',
            ],
            'https://www.facebook.com/v19.0/dialog/oauth'
        );
    } else {
        $auth_url = add_query_arg(
            [
                'client_id'                   => $client_id,
                'redirect_uri'                => $redirect,
                'response_type'               => 'code',
                'scope'                       => 'openid email profile',
                'access_type'                 => 'online',
                'include_granted_scopes'      => 'true',
                'state'                       => $state,
                'prompt'                      => 'select_account',
            ],
            'https://accounts.google.com/o/oauth2/v2/auth'
        );
    }

    wp_safe_redirect( $auth_url );
    exit;
}

/**
 * Process OAuth callback and authenticate the user.
 *
 * @param string $provider Provider key.
 */
function bw_mew_process_social_login_callback( $provider ) {
    $state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
    $code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
    $saved = $state ? get_transient( 'bw_social_state_' . $state ) : false;
    $redirect_after = wc_get_page_permalink( 'myaccount' );

    if ( empty( $saved ) || $saved['provider'] !== $provider ) {
        wc_add_notice( __( 'The social login session has expired. Please try again.', 'bw' ), 'error' );
        wp_safe_redirect( $redirect_after );
        exit;
    }

    delete_transient( 'bw_social_state_' . $state );

    if ( empty( $code ) ) {
        wc_add_notice( __( 'Authorization code missing. Please try again.', 'bw' ), 'error' );
        wp_safe_redirect( $redirect_after );
        exit;
    }

    $redirect_uri = bw_mew_get_social_redirect_uri( $provider );

    if ( 'facebook' === $provider ) {
        $user_data = bw_mew_exchange_facebook_code( $code, $redirect_uri );
    } else {
        $user_data = bw_mew_exchange_google_code( $code, $redirect_uri );
    }

    if ( is_wp_error( $user_data ) ) {
        wc_add_notice( $user_data->get_error_message(), 'error' );
        wp_safe_redirect( $redirect_after );
        exit;
    }

    $email = isset( $user_data['email'] ) ? sanitize_email( $user_data['email'] ) : '';
    $name  = isset( $user_data['name'] ) ? sanitize_text_field( $user_data['name'] ) : $email;

    if ( empty( $email ) ) {
        wc_add_notice( __( 'We could not retrieve your email address. Please use the standard login.', 'bw' ), 'error' );
        wp_safe_redirect( $redirect_after );
        exit;
    }

    $login_result = bw_mew_login_or_register_social_user( $email, $name );

    if ( is_wp_error( $login_result ) ) {
        wc_add_notice( $login_result->get_error_message(), 'error' );
        wp_safe_redirect( $redirect_after );
        exit;
    }

    wp_safe_redirect( $redirect_after );
    exit;
}

/**
 * Exchange Facebook authorization code for user data.
 *
 * @param string $code         Authorization code.
 * @param string $redirect_uri Redirect URI used.
 *
 * @return array|WP_Error
 */
function bw_mew_exchange_facebook_code( $code, $redirect_uri ) {
    $app_id     = get_option( 'bw_account_facebook_app_id', '' );
    $app_secret = get_option( 'bw_account_facebook_app_secret', '' );

    if ( empty( $app_id ) || empty( $app_secret ) ) {
        return new WP_Error( 'bw_facebook_missing_config', __( 'Facebook login is not configured.', 'bw' ) );
    }

    $response = wp_remote_post(
        'https://graph.facebook.com/v19.0/oauth/access_token',
        [
            'body' => [
                'client_id'     => $app_id,
                'client_secret' => $app_secret,
                'redirect_uri'  => $redirect_uri,
                'code'          => $code,
            ],
        ]
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $decoded = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $decoded['access_token'] ) ) {
        return new WP_Error( 'bw_facebook_token_error', __( 'Unable to complete Facebook login.', 'bw' ) );
    }

    $user_response = wp_remote_get( add_query_arg(
        [
            'fields'        => 'id,name,email',
            'access_token'  => $decoded['access_token'],
        ],
        'https://graph.facebook.com/me'
    ) );

    if ( is_wp_error( $user_response ) ) {
        return $user_response;
    }

    $user_data = json_decode( wp_remote_retrieve_body( $user_response ), true );
    if ( empty( $user_data['email'] ) ) {
        return new WP_Error( 'bw_facebook_email_error', __( 'Facebook did not return an email address.', 'bw' ) );
    }

    return [
        'email' => $user_data['email'],
        'name'  => isset( $user_data['name'] ) ? $user_data['name'] : $user_data['email'],
    ];
}

/**
 * Exchange Google authorization code for user data.
 *
 * @param string $code         Authorization code.
 * @param string $redirect_uri Redirect URI used.
 *
 * @return array|WP_Error
 */
function bw_mew_exchange_google_code( $code, $redirect_uri ) {
    $client_id     = get_option( 'bw_account_google_client_id', '' );
    $client_secret = get_option( 'bw_account_google_client_secret', '' );

    if ( empty( $client_id ) || empty( $client_secret ) ) {
        return new WP_Error( 'bw_google_missing_config', __( 'Google login is not configured.', 'bw' ) );
    }

    $response = wp_remote_post(
        'https://oauth2.googleapis.com/token',
        [
            'body' => [
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri'  => $redirect_uri,
                'code'          => $code,
                'grant_type'    => 'authorization_code',
            ],
        ]
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $decoded = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $decoded['access_token'] ) ) {
        return new WP_Error( 'bw_google_token_error', __( 'Unable to complete Google login.', 'bw' ) );
    }

    $user_response = wp_remote_get(
        'https://www.googleapis.com/oauth2/v3/userinfo',
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $decoded['access_token'],
            ],
        ]
    );

    if ( is_wp_error( $user_response ) ) {
        return $user_response;
    }

    $user_data = json_decode( wp_remote_retrieve_body( $user_response ), true );
    if ( empty( $user_data['email'] ) ) {
        return new WP_Error( 'bw_google_email_error', __( 'Google did not return an email address.', 'bw' ) );
    }

    return [
        'email' => $user_data['email'],
        'name'  => isset( $user_data['name'] ) ? $user_data['name'] : $user_data['email'],
    ];
}

/**
 * Log the user in or register a new one from social data.
 *
 * @param string $email User email.
 * @param string $name  User display name.
 *
 * @return true|WP_Error
 */
function bw_mew_login_or_register_social_user( $email, $name ) {
    $user = get_user_by( 'email', $email );

    if ( ! $user ) {
        $registration_enabled = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );

        if ( ! $registration_enabled ) {
            return new WP_Error( 'bw_social_registration_disabled', __( 'Registrations are disabled. Please log in with an existing account.', 'bw' ) );
        }

        $username = sanitize_user( current( explode( '@', $email ) ) );
        $user_id  = wc_create_new_customer( $email, $username, wp_generate_password(), [ 'first_name' => $name ] );

        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }

        $user = get_user_by( 'id', $user_id );
    }

    wc_set_customer_auth_cookie( $user->ID );
    wp_set_current_user( $user->ID );

    return true;
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
