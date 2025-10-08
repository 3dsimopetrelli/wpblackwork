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
if ( file_exists( plugin_dir_path( __FILE__ ) . 'BW_coming_soon/bw-coming-soon.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'BW_coming_soon/bw-coming-soon.php';
}


// Helper functions
require_once __DIR__ . '/includes/helpers.php';

// Loader dei widget
require_once __DIR__ . '/includes/class-bw-widget-loader.php';
// Tipi di prodotto personalizzati per WooCommerce
require_once plugin_dir_path( __FILE__ ) . 'includes/product-types/product-types-init.php';
// Metabox per prodotti digitali
require_once plugin_dir_path( __FILE__ ) . 'metabox/digital-products-metabox.php';

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
        plugin_dir_url(__FILE__) . 'assets/css/bw-slick-slider.css',
        [],
        '1.0.0'
    );

    $showcase_css_file = __DIR__ . '/assets/css/bw-slide-showcase.css';
    $showcase_version  = file_exists( $showcase_css_file ) ? filemtime( $showcase_css_file ) : '1.0.0';

    wp_enqueue_style(
        'bw-slide-showcase-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-slide-showcase.css',
        [],
        $showcase_version
    );

    $product_slide_css_file = __DIR__ . '/assets/css/bw-product-slide.css';
    $product_slide_version  = file_exists( $product_slide_css_file ) ? filemtime( $product_slide_css_file ) : '1.0.0';

    wp_enqueue_style(
        'bw-product-slide-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-product-slide.css',
        [],
        $product_slide_version
    );

    wp_enqueue_script(
        'bw-slick-slider-js',
        plugin_dir_url(__FILE__) . 'assets/js/bw-slick-slider.js',
        ['jquery', 'slick-js'],
        filemtime( __DIR__ . '/assets/js/bw-slick-slider.js' ),
        true
    );

    $product_slide_js_file = __DIR__ . '/assets/js/bw-product-slide.js';
    $product_slide_version_js = file_exists( $product_slide_js_file ) ? filemtime( $product_slide_js_file ) : '1.0.0';

    wp_enqueue_script(
        'bw-product-slide-js',
        plugin_dir_url(__FILE__) . 'assets/js/bw-product-slide.js',
        [ 'jquery', 'slick-js' ],
        $product_slide_version_js,
        true
    );

    if ( class_exists( 'WooCommerce' ) ) {
        wp_enqueue_script( 'wc-add-to-cart-variation' );
    }

    wp_localize_script(
        'bw-slick-slider-js',
        'bwSlickSlider',
        [
            'assetsUrl' => plugin_dir_url(__FILE__) . 'assets/',
        ]
    );
}

function bw_enqueue_slick_slider_admin_script() {
    wp_enqueue_script(
        'bw-slick-slider-admin',
        plugin_dir_url(__FILE__) . 'assets/js/bw-slick-slider-admin.js',
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
    $css_file = __DIR__ . '/assets/css/bw-divider.css';
    $version  = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

    wp_register_style(
        'bw-divider-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-divider.css',
        [],
        $version
    );
}

// Aggiungi categoria personalizzata "Black Work Widgets"
add_action( 'elementor/elements/categories_registered', function( $elements_manager ) {
    $elements_manager->add_category(
        'blackwork',
        [
            'title' => __( 'Black Work Widgets', 'bw' ),
            'icon'  => 'fa fa-plug',
        ]
    );
} );
