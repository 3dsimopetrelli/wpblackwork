<?php
/**
 * Plugin Name: BW Elementor Widgets
 * Description: Collezione di widget personalizzati per Elementor
 * Version: 1.0.0
 * Author: Simone
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Loader dei widget
require_once __DIR__ . '/includes/class-bw-widget-loader.php';

// Registrazione asset condivisi tra i widget
function bw_widgets_register_assets() {
    $plugin_url = plugin_dir_url( __FILE__ );

    wp_register_style(
        'flickity-css',
        'https://unpkg.com/flickity@2.3.0/dist/flickity.css',
        [],
        '2.3.0'
    );

    wp_register_script(
        'flickity-js',
        'https://unpkg.com/flickity@2.3.0/dist/flickity.pkgd.min.js',
        [],
        '2.3.0',
        true
    );

    wp_register_style(
        'bw-products-slide-style',
        $plugin_url . 'assets/css/bw-products-slide.css',
        [],
        '1.0.0'
    );

    wp_register_script(
        'bw-products-slide-script',
        $plugin_url . 'assets/js/bw-products-slide.js',
        [ 'jquery', 'flickity-js' ],
        '1.0.0',
        true
    );
}
add_action( 'elementor/frontend/after_register_styles', 'bw_widgets_register_assets' );
add_action( 'elementor/frontend/after_register_scripts', 'bw_widgets_register_assets' );
add_action( 'wp_enqueue_scripts', 'bw_widgets_register_assets' );
