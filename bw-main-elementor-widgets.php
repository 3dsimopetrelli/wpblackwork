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

if ( ! defined( 'BW_MEW_URL' ) ) {
    define( 'BW_MEW_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'BW_MEW_PATH' ) ) {
    define( 'BW_MEW_PATH', plugin_dir_path( __FILE__ ) );
}


// Includi il modulo BW Coming Soon
if ( file_exists( BW_MEW_PATH . 'BW_coming_soon/bw-coming-soon.php' ) ) {
    require_once BW_MEW_PATH . 'BW_coming_soon/bw-coming-soon.php';
}


// Helper functions
require_once BW_MEW_PATH . 'includes/helpers.php';

// Loader dei widget
require_once BW_MEW_PATH . 'includes/class-bw-widget-loader.php';

// Tipi di prodotto personalizzati per WooCommerce
require_once BW_MEW_PATH . 'includes/product-types/product-types-init.php';

add_action('elementor/frontend/after_enqueue_scripts', 'bw_enqueue_slick_slider_assets');
add_action('elementor/editor/after_enqueue_scripts', 'bw_enqueue_slick_slider_assets');
add_action('elementor/preview/enqueue_scripts', 'bw_enqueue_slick_slider_assets');
add_action('elementor/editor/after_enqueue_scripts', 'bw_enqueue_slick_slider_admin_script');
add_action('init', 'bw_register_divider_style');

function bw_enqueue_slick_slider_assets() {
    wp_enqueue_style(
        'slick-css',
        'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css',
        [],
        '1.8.1'
    );

    wp_enqueue_script(
        'slick-js',
        'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js',
        ['jquery'],
        '1.8.1',
        true
    );

    wp_enqueue_style(
        'bw-slick-slider-style',
        BW_MEW_URL . 'assets/css/bw-slick-slider.css',
        [],
        '1.0.0'
    );

    wp_enqueue_script(
        'bw-slick-slider-js',
        BW_MEW_URL . 'assets/js/bw-slick-slider.js',
        ['jquery', 'slick-js'],
        filemtime( BW_MEW_PATH . 'assets/js/bw-slick-slider.js' ),
        true
    );

    if ( class_exists( 'WooCommerce' ) ) {
        wp_enqueue_script( 'wc-add-to-cart-variation' );
    }

    wp_localize_script(
        'bw-slick-slider-js',
        'bwSlickSlider',
        [
            'assetsUrl' => BW_MEW_URL . 'assets/',
        ]
    );
}

function bw_enqueue_slick_slider_admin_script() {
    wp_enqueue_script(
        'bw-slick-slider-admin',
        BW_MEW_URL . 'assets/js/bw-slick-slider-admin.js',
        ['jquery'],
        '1.0.0',
        true
    );

    wp_localize_script(
        'bw-slick-slider-admin',
        'bwSlickSliderAdmin',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('bw_get_child_categories'),
        ]
    );
}

function bw_register_divider_style() {
    $css_file = BW_MEW_PATH . 'assets/css/bw-divider.css';
    $version  = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

    wp_register_style(
        'bw-divider-style',
        BW_MEW_URL . 'assets/css/bw-divider.css',
        [],
        $version
    );
}

// Aggiungi categoria personalizzata "Black Work Widgets"
add_action( 'elementor/elements/categories_registered', static function( $elements_manager ) {
    if ( ! method_exists( $elements_manager, 'add_category' ) ) {
        return;
    }

    $elements_manager->add_category(
        'black-work',
        [
            'title' => __( 'Black Work Widgets', 'bw-elementor-widgets' ),
            'icon'  => 'fa fa-cube',
        ]
    );
} );
