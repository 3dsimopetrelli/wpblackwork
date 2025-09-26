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

add_action( 'elementor/widgets/register', function( $widgets_manager ) {
    require_once __DIR__ . '/includes/widgets/class-bw-products-slide-widget.php';
    $widgets_manager->register( new Widget_Bw_Products_Slide() );
} );

// Registrazione widget Elementor
function bw_widgets_register_elementor_widgets( $widgets_manager = null ) {
    BW_Widget_Loader::instance()->register_widgets( $widgets_manager );
}
add_action( 'elementor/widgets/register', 'bw_widgets_register_elementor_widgets' );
add_action( 'elementor/widgets/widgets_registered', 'bw_widgets_register_elementor_widgets' );

// Registrazione asset condivisi tra i widget
function bw_widgets_register_assets() {
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
        plugins_url( '/assets/css/bw-products-slide.css', __FILE__ ),
        [],
        '1.0.0'
    );

    wp_register_script(
        'bw-products-slide-script',
        plugins_url( '/assets/js/bw-products-slide.js', __FILE__ ),
        [ 'jquery', 'flickity-js' ],
        '1.0.0',
        true
    );
}
add_action( 'elementor/frontend/after_register_styles', 'bw_widgets_register_assets' );
add_action( 'elementor/frontend/after_register_scripts', 'bw_widgets_register_assets' );
add_action( 'elementor/editor/after_enqueue_styles', 'bw_widgets_register_assets' );
add_action( 'elementor/editor/after_enqueue_scripts', 'bw_widgets_register_assets' );
add_action( 'wp_enqueue_scripts', 'bw_widgets_register_assets' );
