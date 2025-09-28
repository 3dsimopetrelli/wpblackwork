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
    $plugin_dir  = plugin_dir_path( __FILE__ );
    $plugin_url  = plugins_url( '', __FILE__ );
    $style_file  = $plugin_dir . 'assets/css/bw-products-slide.css';
    $script_file = $plugin_dir . 'assets/js/bw-products-slide.js';

    $style_version  = file_exists( $style_file ) ? filemtime( $style_file ) : false;
    $script_version = file_exists( $script_file ) ? filemtime( $script_file ) : false;

    wp_register_style( 'flickity-css', 'https://unpkg.com/flickity@2.3.0/dist/flickity.css', [], '2.3.0' );
    wp_register_script( 'flickity-js', 'https://unpkg.com/flickity@2.3.0/dist/flickity.pkgd.min.js', [], '2.3.0', true );

    wp_register_style( 'bw-products-slide-style', $plugin_url . '/assets/css/bw-products-slide.css', [], $style_version );
    wp_register_script( 'bw-products-slide-script', $plugin_url . '/assets/js/bw-products-slide.js', [ 'jquery', 'flickity-js' ], $script_version, true );
}
add_action( 'wp_enqueue_scripts', 'bw_widgets_register_assets' );
add_action( 'elementor/frontend/after_register_scripts', 'bw_widgets_register_assets' );
add_action( 'elementor/frontend/after_register_styles', 'bw_widgets_register_assets' );

add_action( 'elementor/preview/enqueue_scripts', static function() {
    wp_enqueue_script( 'flickity-js' );
    wp_enqueue_script( 'bw-products-slide-script' );
    wp_add_inline_script( 'bw-products-slide-script', 'console.log("✅ BW Products JS caricato anche in editor Elementor");' );
} );

add_action( 'elementor/preview/enqueue_styles', static function() {
    wp_enqueue_style( 'flickity-css' );
    wp_enqueue_style( 'bw-products-slide-style' );
} );

add_action( 'elementor/frontend/after_enqueue_scripts', static function() {
    wp_enqueue_script( 'flickity-js' );
    wp_enqueue_script( 'bw-products-slide-script' );
} );

add_action( 'elementor/frontend/after_enqueue_styles', static function() {
    wp_enqueue_style( 'flickity-css' );
    wp_enqueue_style( 'bw-products-slide-style' );
} );

add_action( 'elementor/editor/after_enqueue_scripts', static function() {
    wp_enqueue_script( 'flickity-js' );
    wp_enqueue_script( 'bw-products-slide-script' );
} );

add_action( 'elementor/editor/after_enqueue_styles', static function() {
    wp_enqueue_style( 'flickity-css' );
    wp_enqueue_style( 'bw-products-slide-style' );
} );

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
