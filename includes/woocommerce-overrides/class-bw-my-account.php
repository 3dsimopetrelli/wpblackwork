<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Custom My Account layout helpers and hooks.
 */

/**
 * Filter WooCommerce account menu items to keep only the desired entries.
 *
 * @param array $items Menu items.
 *
 * @return array
 */
function bw_mew_filter_account_menu_items( $items ) {
    $order          = [ 'dashboard', 'downloads', 'orders', 'edit-account', 'customer-logout' ];
    $filtered_items = [];

    foreach ( $order as $endpoint ) {
        if ( 'orders' === $endpoint ) {
            $label = __( 'My purchases', 'bw' );
        } else {
            if ( ! isset( $items[ $endpoint ] ) ) {
                continue;
            }

            $label = $items[ $endpoint ];
            if ( 'edit-account' === $endpoint ) {
                $label = __( 'settings', 'bw' );
            } elseif ( 'customer-logout' === $endpoint ) {
                $label = __( 'logout', 'bw' );
            }
        }

        $filtered_items[ $endpoint ] = $label;
    }

    return $filtered_items;
}
add_filter( 'woocommerce_account_menu_items', 'bw_mew_filter_account_menu_items', 20 );

/**
 * Register the set-password endpoint under My Account.
 */
function bw_mew_register_set_password_endpoint() {
    add_rewrite_endpoint( 'set-password', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'bw_mew_register_set_password_endpoint' );

/**
 * Add the set-password query var to WooCommerce endpoints.
 *
 * @param array $vars WooCommerce query vars.
 *
 * @return array
 */
function bw_mew_add_set_password_query_var( $vars ) {
    $vars['set-password'] = 'set-password';

    return $vars;
}
add_filter( 'woocommerce_get_query_vars', 'bw_mew_add_set_password_query_var' );

/**
 * Render set-password endpoint content.
 */
function bw_mew_render_set_password_endpoint() {
    wc_get_template( 'myaccount/set-password.php' );
}
add_action( 'woocommerce_account_set-password_endpoint', 'bw_mew_render_set_password_endpoint' );

/**
 * Enforce onboarding lock until Supabase password is set.
 */
function bw_mew_enforce_supabase_onboarding_lock() {
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_user_logged_in() ) {
        return;
    }

    $onboarded = (int) get_user_meta( get_current_user_id(), 'bw_supabase_onboarded', true );
    if ( 1 === $onboarded ) {
        return;
    }

    if ( is_wc_endpoint_url( 'set-password' ) || is_wc_endpoint_url( 'customer-logout' ) ) {
        return;
    }

    wp_safe_redirect( wc_get_account_endpoint_url( 'set-password' ) );
    exit;
}
add_action( 'template_redirect', 'bw_mew_enforce_supabase_onboarding_lock' );

/**
 * Add body class when onboarding lock is active.
 *
 * @param array $classes Body classes.
 *
 * @return array
 */
function bw_mew_add_onboarding_body_class( $classes ) {
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
        return $classes;
    }

    $onboarded = is_user_logged_in()
        ? (int) get_user_meta( get_current_user_id(), 'bw_supabase_onboarded', true )
        : 0;

    if ( is_wc_endpoint_url( 'set-password' ) || 1 !== $onboarded ) {
        $classes[] = 'bw-onboarding-lock';
    }

    return $classes;
}
add_filter( 'body_class', 'bw_mew_add_onboarding_body_class' );

/**
 * Enqueue assets for the logged-in my account area.
 */
function bw_mew_enqueue_my_account_assets() {
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
        return;
    }

    if ( ! is_user_logged_in() && ! is_wc_endpoint_url( 'set-password' ) ) {
        return;
    }

    $css_file = BW_MEW_PATH . 'assets/css/bw-my-account.css';
    $css_ver  = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';
    $js_file  = BW_MEW_PATH . 'assets/js/bw-my-account.js';
    $js_ver   = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

    wp_enqueue_style(
        'bw-my-account',
        BW_MEW_URL . 'assets/css/bw-my-account.css',
        [],
        $css_ver
    );

    wp_enqueue_script(
        'bw-my-account',
        BW_MEW_URL . 'assets/js/bw-my-account.js',
        [],
        $js_ver,
        true
    );

    wp_localize_script(
        'bw-my-account',
        'bwAccountOnboarding',
        [
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'bw-supabase-login' ),
            'projectUrl'  => get_option( 'bw_supabase_project_url', '' ),
            'anonKey'     => get_option( 'bw_supabase_anon_key', '' ),
            'setPasswordUrl' => wc_get_account_endpoint_url( 'set-password' ),
            'redirectUrl' => wc_get_page_permalink( 'myaccount' ),
            'debug'       => (bool) get_option( 'bw_supabase_debug_log', 0 ),
            'userEmail'   => is_user_logged_in() ? wp_get_current_user()->user_email : '',
        ]
    );
}
add_action( 'wp_enqueue_scripts', 'bw_mew_enqueue_my_account_assets', 25 );

/**
 * Helper to get the black box text content.
 *
 * @return string
 */
function bw_mew_get_my_account_black_box_text() {
    $default = __( 'Your mockups will always be here, available to download. Please enjoy them!', 'bw' );

    return get_option( 'bw_myaccount_black_box_text', $default );
}

/**
 * Get recent orders for the current customer.
 *
 * @param int $limit Number of orders to return.
 *
 * @return array
 */
function bw_mew_get_recent_customer_orders( $limit = 3 ) {
    if ( ! is_user_logged_in() ) {
        return [];
    }

    $args = [
        'limit'        => absint( $limit ),
        'customer'     => get_current_user_id(),
        'orderby'      => 'date',
        'order'        => 'DESC',
        'status'       => apply_filters( 'woocommerce_my_account_my_orders_query_statuses', [ 'wc-completed', 'wc-processing', 'wc-on-hold' ] ),
    ];

    return wc_get_orders( $args );
}

/**
 * Retrieve available coupons for the current customer.
 *
 * @return array
 */
function bw_mew_get_customer_coupons() {
    if ( ! is_user_logged_in() ) {
        return [];
    }

    $customer_email = wp_get_current_user()->user_email;

    // Smart Coupons compatibility.
    if ( function_exists( 'wc_sc_get_available_coupons' ) ) {
        return wc_sc_get_available_coupons( $customer_email );
    }

    $coupons = wc_get_coupons(
        [
            'orderby' => 'date',
            'order'   => 'DESC',
            'limit'   => -1,
            'return'  => 'objects',
        ]
    );

    $available = [];

    foreach ( $coupons as $coupon ) {
        if ( ! $coupon instanceof WC_Coupon ) {
            continue;
        }

        $email_restrictions = array_map( 'strtolower', (array) $coupon->get_email_restrictions() );
        if ( ! empty( $email_restrictions ) && ! in_array( strtolower( $customer_email ), $email_restrictions, true ) ) {
            continue;
        }

        $available[] = $coupon;
    }

    return $available;
}
