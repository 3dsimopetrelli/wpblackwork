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
// Metabox Images Showcase
require_once plugin_dir_path( __FILE__ ) . 'metabox/images-showcase-metabox.php';

add_action('elementor/frontend/after_enqueue_scripts', 'bw_enqueue_slick_slider_assets');
add_action('elementor/editor/after_enqueue_scripts', 'bw_enqueue_slick_slider_assets');
add_action('elementor/preview/enqueue_scripts', 'bw_enqueue_slick_slider_assets');
add_action('elementor/editor/after_enqueue_scripts', 'bw_enqueue_slick_slider_admin_script');
add_action('init', 'bw_register_divider_style');
add_action( 'init', 'bw_register_button_widget_assets' );
add_action( 'init', 'bw_register_about_menu_widget_assets' );
add_action( 'init', 'bw_register_wallpost_widget_assets' );
add_action( 'elementor/frontend/after_register_scripts', 'bw_enqueue_about_menu_widget_assets' );
add_action( 'elementor/editor/after_enqueue_scripts', 'bw_enqueue_about_menu_widget_assets' );

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

    $slick_slider_css_file = __DIR__ . '/assets/css/bw-slick-slider.css';
    $slick_slider_version  = file_exists( $slick_slider_css_file ) ? filemtime( $slick_slider_css_file ) : '1.0.0';

    wp_enqueue_style(
        'bw-slick-slider-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-slick-slider.css',
        [],
        $slick_slider_version
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

    $bw_custom_class_css_file = __DIR__ . '/assets/css/bw-custom-class.css';
    $custom_class_version  = file_exists( $bw_custom_class_css_file ) ? filemtime( $bw_custom_class_css_file ) : '1.0.0';

    wp_enqueue_style(
        'bw-fullbleed-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-custom-class.css',
        [],
        $custom_class_version
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
    $admin_js_file     = __DIR__ . '/assets/js/bw-slick-slider-admin.js';
    $admin_js_version  = file_exists( $admin_js_file ) ? filemtime( $admin_js_file ) : '1.0.0';

    wp_enqueue_script(
        'bw-slick-slider-admin',
        plugin_dir_url(__FILE__) . 'assets/js/bw-slick-slider-admin.js',
        ['jquery'],
        $admin_js_version,
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

function bw_register_button_widget_assets() {
    $css_file = __DIR__ . '/assets/css/bw-button.css';
    $css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

    wp_register_style(
        'bw-button-style',
        plugin_dir_url( __FILE__ ) . 'assets/css/bw-button.css',
        [],
        $css_version
    );

    $js_file = __DIR__ . '/assets/js/bw-button.js';

    if ( file_exists( $js_file ) ) {
        wp_register_script(
            'bw-button-script',
            plugin_dir_url( __FILE__ ) . 'assets/js/bw-button.js',
            [ 'jquery' ],
            filemtime( $js_file ),
            true
        );
    }
}

function bw_register_about_menu_widget_assets() {
    $css_file = __DIR__ . '/assets/css/bw-about-menu.css';
    $css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

    wp_register_style(
        'bw-about-menu-style',
        plugin_dir_url( __FILE__ ) . 'assets/css/bw-about-menu.css',
        [],
        $css_version
    );

    $js_file = __DIR__ . '/assets/js/bw-about-menu.js';

    if ( file_exists( $js_file ) ) {
        wp_register_script(
            'bw-about-menu-script',
            plugin_dir_url( __FILE__ ) . 'assets/js/bw-about-menu.js',
            [],
            filemtime( $js_file ),
            true
        );
    }
}

function bw_register_wallpost_widget_assets() {
    $css_file     = __DIR__ . '/assets/css/bw-wallpost.css';
    $css_version  = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

    wp_register_style(
        'bw-wallpost-style',
        plugin_dir_url( __FILE__ ) . 'assets/css/bw-wallpost.css',
        [],
        $css_version
    );

    $js_file    = __DIR__ . '/assets/js/bw-wallpost.js';
    $js_version = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

    wp_register_script(
        'bw-wallpost-js',
        plugin_dir_url( __FILE__ ) . 'assets/js/bw-wallpost.js',
        [ 'jquery', 'imagesloaded', 'masonry' ],
        $js_version,
        true
    );
}

function bw_enqueue_about_menu_widget_assets() {
    if ( ! wp_style_is( 'bw-about-menu-style', 'registered' ) || ! wp_script_is( 'bw-about-menu-script', 'registered' ) ) {
        bw_register_about_menu_widget_assets();
    }

    if ( wp_style_is( 'bw-about-menu-style', 'registered' ) ) {
        wp_enqueue_style( 'bw-about-menu-style' );
    }

    if ( wp_script_is( 'bw-about-menu-script', 'registered' ) ) {
        wp_enqueue_script( 'bw-about-menu-script' );
    }
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
