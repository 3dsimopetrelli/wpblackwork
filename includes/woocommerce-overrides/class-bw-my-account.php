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
    $order          = [ 'dashboard', 'downloads', 'orders', 'invoices', 'edit-account', 'customer-logout' ];
    $filtered_items = [];

    foreach ( $order as $endpoint ) {
        if ( 'orders' === $endpoint ) {
            $label = __( 'My purchases', 'bw' );
        } elseif ( 'invoices' === $endpoint ) {
            $label = __( 'Invoices', 'bw' );
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
 * Enqueue assets for the logged-in my account area.
 */
function bw_mew_enqueue_my_account_assets() {
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_user_logged_in() ) {
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
