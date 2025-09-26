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

// Include il loader dei widget.
require_once __DIR__ . '/includes/class-bw-widget-loader.php';

// Inizializza il loader per collegare le azioni di Elementor.
$bw_widget_loader = BW_Widget_Loader::instance();
$bw_widget_loader->register_hooks();

/**
 * Registra gli asset condivisi dei widget BW.
 */
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
        plugins_url( 'assets/css/bw-products-slide.css', __FILE__ ),
        [],
        '1.0.0'
    );

    wp_register_script(
        'bw-products-slide-script',
        plugins_url( 'assets/js/bw-products-slide.js', __FILE__ ),
        [ 'jquery', 'flickity-js' ],
        '1.0.0',
        true
    );
}
add_action( 'wp_enqueue_scripts', 'bw_widgets_register_assets' );
add_action( 'elementor/frontend/after_register_styles', 'bw_widgets_register_assets' );
add_action( 'elementor/frontend/after_register_scripts', 'bw_widgets_register_assets' );

/**
 * Aggiunge la categoria personalizzata Black Work ad Elementor.
 *
 * @param \Elementor\Elements_Manager $elements_manager Gestore delle categorie di Elementor.
 */
function bw_widgets_register_category( $elements_manager ) {
    $elements_manager->add_category(
        'black-work',
        [
            'title' => __( 'Black Work', 'bw-elementor-widgets' ),
            'icon'  => 'fa fa-cube',
        ]
    );
}
add_action( 'elementor/elements/categories_registered', 'bw_widgets_register_category' );
