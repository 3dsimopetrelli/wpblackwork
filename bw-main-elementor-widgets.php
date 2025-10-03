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


// Helper functions
require_once __DIR__ . '/includes/helpers.php';

// Loader dei widget
require_once __DIR__ . '/includes/class-bw-widget-loader.php';

// Tipi di prodotto personalizzati per WooCommerce
require_once plugin_dir_path( __FILE__ ) . 'includes/product-types/product-types-init.php';

add_action('elementor/frontend/after_enqueue_scripts', 'bw_enqueue_flickity');
add_action('elementor/editor/after_enqueue_scripts', 'bw_enqueue_flickity');
add_action('elementor/preview/enqueue_scripts', 'bw_enqueue_flickity');
add_action('elementor/editor/after_enqueue_scripts', 'bw_enqueue_slick_slider_admin_script');

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

    wp_enqueue_script(
        'bw-slick-slider-js',
        plugin_dir_url(__FILE__) . 'assets/js/bw-slick-slider.js',
        ['jquery', 'slick-js'],
        filemtime( __DIR__ . '/assets/js/bw-slick-slider.js' ),
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
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'quickViewNonce' => wp_create_nonce( 'bw_quick_view_nonce' ),
            'i18n'      => [
                'loading' => __( 'Caricamento prodotto…', 'bw' ),
                'error'   => __( 'Impossibile caricare le informazioni del prodotto.', 'bw' ),
            ],
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
