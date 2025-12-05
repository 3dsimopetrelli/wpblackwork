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

    add_filter( 'woocommerce_locate_template', 'bw_mew_locate_template', 1, 3 );
    add_action( 'wp_enqueue_scripts', 'bw_mew_enqueue_related_products_assets', 30 );
    add_action( 'wp_enqueue_scripts', 'bw_mew_enqueue_account_page_assets', 20 );
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
    $version  = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

    wp_enqueue_style(
        'bw-account-page',
        BW_MEW_URL . 'assets/css/bw-account-page.css',
        [],
        $version
    );
}
