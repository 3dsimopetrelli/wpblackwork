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


// Includi il modulo BW Coming Soon
if ( file_exists( plugin_dir_path( __FILE__ ) . 'BW_coming_soon/bw-coming-soon.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'BW_coming_soon/bw-coming-soon.php';
}


// Loader dei widget
require_once __DIR__ . '/includes/class-bw-widget-loader.php';

add_action('elementor/frontend/after_enqueue_scripts', 'bw_enqueue_flickity');
add_action('elementor/editor/after_enqueue_scripts', 'bw_enqueue_flickity');

function bw_enqueue_flickity() {
    wp_enqueue_style(
        'flickity-css',
        'https://unpkg.com/flickity@2.3.0/dist/flickity.min.css',
        [],
        '2.3.0'
    );

    wp_enqueue_script(
        'flickity-js',
        'https://unpkg.com/flickity@2.3.0/dist/flickity.pkgd.min.js',
        ['jquery'],
        '2.3.0',
        true
    );

    wp_enqueue_style(
        'bw-products-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-products-slide.css'
    );

    wp_enqueue_script(
        'bw-products-js',
        plugin_dir_url(__FILE__) . 'assets/js/bw-products-slide.js',
        ['jquery', 'flickity-js'],
        '1.0.0',
        true
    );
}

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
