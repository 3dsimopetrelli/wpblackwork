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

// Registrazione eventuali asset globali
function bw_widgets_register_assets() {
    $plugin_dir_url  = plugin_dir_url( __FILE__ );
    $plugin_dir_path = plugin_dir_path( __FILE__ );

    // Flickity
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

    $style_path  = $plugin_dir_path . 'assets/css/bw-products-slide.css';
    $style_ver   = file_exists( $style_path ) ? filemtime( $style_path ) : null;
    $script_path = $plugin_dir_path . 'assets/js/bw-products-slide.js';
    $script_ver  = file_exists( $script_path ) ? filemtime( $script_path ) : null;

    // Widget assets
    wp_register_style(
        'bw-products-slide-style',
        $plugin_dir_url . 'assets/css/bw-products-slide.css',
        [],
        $style_ver
    );

    wp_register_script(
        'bw-products-slide-script',
        $plugin_dir_url . 'assets/js/bw-products-slide.js',
        [ 'jquery', 'flickity-js' ],
        $script_ver,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'bw_widgets_register_assets' );
add_action( 'elementor/frontend/after_register_styles', 'bw_widgets_register_assets' );
add_action( 'elementor/frontend/after_register_scripts', 'bw_widgets_register_assets' );

// Aggiungi categoria personalizzata "Black Work"
add_action( 'elementor/elements/categories_registered', static function( $elements_manager ) {
    if ( ! method_exists( $elements_manager, 'add_category' ) ) {
        return;
    }

    $elements_manager->add_category(
        'black-work',
        [
            'title' => __( 'Black Work', 'bw-elementor-widgets' ),
            'icon'  => 'fa fa-cube',
        ]
    );
} );
