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

// Includi il modulo BW Cart Pop-Up
if ( file_exists( plugin_dir_path( __FILE__ ) . 'cart-popup/cart-popup.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'cart-popup/cart-popup.php';
}

// Includi la pagina unificata Blackwork Site Settings
if ( file_exists( plugin_dir_path( __FILE__ ) . 'admin/class-blackwork-site-settings.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'admin/class-blackwork-site-settings.php';
}


// Helper functions
require_once __DIR__ . '/includes/helpers.php';

// WooCommerce overrides
require_once __DIR__ . '/woocommerce/woocommerce-init.php';

// Elementor Dynamic Tags
function bw_load_elementor_dynamic_tags() {
    $artist_tag_file = __DIR__ . '/includes/dynamic-tags/class-bw-artist-name-tag.php';

    if ( file_exists( $artist_tag_file ) ) {
        require_once $artist_tag_file;
    }
}
add_action( 'elementor/init', 'bw_load_elementor_dynamic_tags' );

// Loader dei widget
require_once __DIR__ . '/includes/class-bw-widget-loader.php';

/**
 * Initialize plugin components at the 'init' action to ensure proper translation loading.
 * This prevents WordPress 6.7.0+ warnings about translations being loaded too early.
 */
function bw_initialize_plugin_components() {
	// Tipi di prodotto personalizzati per WooCommerce (Digital Assets, Books, Prints)
	require_once plugin_dir_path( __FILE__ ) . 'includes/product-types/product-types-init.php';
        // Metabox per prodotti digitali
        require_once plugin_dir_path( __FILE__ ) . 'metabox/digital-products-metabox.php';
        // Metabox Bibliographic Details
        require_once plugin_dir_path( __FILE__ ) . 'metabox/bibliographic-details-metabox.php';
        // Metabox Images Showcase
        require_once plugin_dir_path( __FILE__ ) . 'metabox/images-showcase-metabox.php';
        // Metabox Artist Name
        require_once plugin_dir_path( __FILE__ ) . 'metabox/artist-name-metabox.php';
        // Metabox Product Slider
        require_once plugin_dir_path( __FILE__ ) . 'includes/product-types/class-bw-product-slider-metabox.php';
        // Campo URL completo per categorie prodotto
        require_once plugin_dir_path( __FILE__ ) . 'includes/category-url-field.php';
}
add_action( 'init', 'bw_initialize_plugin_components', 5 );

add_action('elementor/frontend/after_enqueue_scripts', 'bw_enqueue_slick_slider_assets');
add_action('elementor/editor/after_enqueue_scripts', 'bw_enqueue_slick_slider_assets');
add_action('elementor/preview/enqueue_scripts', 'bw_enqueue_slick_slider_assets');
add_action('elementor/editor/after_enqueue_scripts', 'bw_enqueue_slick_slider_admin_script');
add_action('init', 'bw_register_divider_style');
add_action( 'init', 'bw_register_button_widget_assets' );
add_action( 'init', 'bw_register_about_menu_widget_assets' );
add_action( 'init', 'bw_register_wallpost_widget_assets' );
add_action( 'init', 'bw_register_search_widget_assets' );
add_action( 'elementor/frontend/after_register_scripts', 'bw_register_search_widget_assets' );
add_action( 'elementor/frontend/after_register_styles', 'bw_register_search_widget_assets' );
add_action( 'elementor/frontend/after_enqueue_scripts', 'bw_enqueue_search_widget_assets' );
add_action( 'elementor/editor/after_enqueue_scripts', 'bw_enqueue_search_widget_assets' );
add_action( 'init', 'bw_register_navshop_widget_assets' );
add_action( 'elementor/frontend/after_register_scripts', 'bw_register_navshop_widget_assets' );
add_action( 'elementor/frontend/after_register_styles', 'bw_register_navshop_widget_assets' );
add_action( 'elementor/frontend/after_enqueue_scripts', 'bw_enqueue_navshop_widget_assets' );
add_action( 'elementor/editor/after_enqueue_scripts', 'bw_enqueue_navshop_widget_assets' );
add_action( 'elementor/frontend/after_register_scripts', 'bw_register_wallpost_widget_assets' );
add_action( 'elementor/frontend/after_register_styles', 'bw_register_wallpost_widget_assets' );
add_action( 'elementor/frontend/after_enqueue_scripts', 'bw_enqueue_wallpost_widget_assets' );
add_action( 'elementor/editor/after_enqueue_scripts', 'bw_enqueue_wallpost_widget_assets' );
add_action( 'elementor/frontend/after_register_scripts', 'bw_enqueue_about_menu_widget_assets' );
add_action( 'elementor/editor/after_enqueue_scripts', 'bw_enqueue_about_menu_widget_assets' );
add_action( 'init', 'bw_register_filtered_post_wall_widget_assets' );
add_action( 'elementor/frontend/after_register_scripts', 'bw_register_filtered_post_wall_widget_assets' );
add_action( 'elementor/frontend/after_register_styles', 'bw_register_filtered_post_wall_widget_assets' );
add_action( 'elementor/frontend/after_enqueue_scripts', 'bw_enqueue_filtered_post_wall_widget_assets' );
add_action( 'elementor/editor/after_enqueue_scripts', 'bw_enqueue_filtered_post_wall_widget_assets' );
add_action( 'init', 'bw_register_animated_banner_widget_assets' );
add_action( 'elementor/frontend/after_register_scripts', 'bw_register_animated_banner_widget_assets' );
add_action( 'elementor/frontend/after_register_styles', 'bw_register_animated_banner_widget_assets' );
add_action( 'elementor/frontend/after_enqueue_scripts', 'bw_enqueue_animated_banner_widget_assets' );
add_action( 'elementor/editor/after_enqueue_scripts', 'bw_enqueue_animated_banner_widget_assets' );
add_action( 'wp_enqueue_scripts', 'bw_enqueue_smart_header_assets' );
add_action( 'init', 'bw_register_static_showcase_widget_assets' );
add_action( 'elementor/frontend/after_register_styles', 'bw_register_static_showcase_widget_assets' );
add_action( 'init', 'bw_register_related_products_widget_assets' );
add_action( 'elementor/frontend/after_register_styles', 'bw_register_related_products_widget_assets' );

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

function bw_register_related_products_widget_assets() {
    if ( function_exists( 'bw_register_wallpost_widget_assets' ) ) {
        bw_register_wallpost_widget_assets();
    }

    $css_file    = __DIR__ . '/woocommerce/css/bw-related-products.css';
    $css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

    wp_register_style(
        'bw-related-products-style',
        plugin_dir_url( __FILE__ ) . 'woocommerce/css/bw-related-products.css',
        [ 'bw-wallpost-style' ],
        $css_version
    );
}

function bw_register_search_widget_assets() {
    $css_file = __DIR__ . '/assets/css/bw-search.css';
    $css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

    wp_register_style(
        'bw-search-style',
        plugin_dir_url( __FILE__ ) . 'assets/css/bw-search.css',
        [],
        $css_version
    );

    $js_file = __DIR__ . '/assets/js/bw-search.js';
    $js_version = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

    wp_register_script(
        'bw-search-script',
        plugin_dir_url( __FILE__ ) . 'assets/js/bw-search.js',
        [ 'jquery', 'imagesloaded', 'masonry' ],
        $js_version,
        true
    );

    // Localize script per AJAX
    wp_localize_script(
        'bw-search-script',
        'bwSearchAjax',
        [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'bw_search_nonce' ),
        ]
    );
}

function bw_enqueue_search_widget_assets() {
    if ( ! wp_style_is( 'bw-search-style', 'registered' ) || ! wp_script_is( 'bw-search-script', 'registered' ) ) {
        bw_register_search_widget_assets();
    }

    if ( wp_style_is( 'bw-search-style', 'registered' ) ) {
        wp_enqueue_style( 'bw-search-style' );
    }

    if ( wp_script_is( 'bw-search-script', 'registered' ) ) {
        wp_enqueue_script( 'bw-search-script' );
    }
}

function bw_enqueue_wallpost_widget_assets() {
    if ( ! wp_style_is( 'bw-wallpost-style', 'registered' ) || ! wp_script_is( 'bw-wallpost-js', 'registered' ) ) {
        bw_register_wallpost_widget_assets();
    }

    if ( wp_style_is( 'bw-wallpost-style', 'registered' ) ) {
        wp_enqueue_style( 'bw-wallpost-style' );
    }

    if ( wp_script_is( 'bw-wallpost-js', 'registered' ) ) {
        wp_enqueue_script( 'bw-wallpost-js' );
    }
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

function bw_register_navshop_widget_assets() {
    $css_file = __DIR__ . '/assets/css/bw-navshop.css';
    $css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

    wp_register_style(
        'bw-navshop-style',
        plugin_dir_url( __FILE__ ) . 'assets/css/bw-navshop.css',
        [],
        $css_version
    );

    $js_file = __DIR__ . '/assets/js/bw-navshop.js';
    $js_version = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

    wp_register_script(
        'bw-navshop-script',
        plugin_dir_url( __FILE__ ) . 'assets/js/bw-navshop.js',
        [ 'jquery' ],
        $js_version,
        true
    );
}

function bw_enqueue_navshop_widget_assets() {
    if ( ! wp_style_is( 'bw-navshop-style', 'registered' ) || ! wp_script_is( 'bw-navshop-script', 'registered' ) ) {
        bw_register_navshop_widget_assets();
    }

    if ( wp_style_is( 'bw-navshop-style', 'registered' ) ) {
        wp_enqueue_style( 'bw-navshop-style' );
    }

    if ( wp_script_is( 'bw-navshop-script', 'registered' ) ) {
        wp_enqueue_script( 'bw-navshop-script' );
    }
}

function bw_register_filtered_post_wall_widget_assets() {
    $css_file     = __DIR__ . '/assets/css/bw-filtered-post-wall.css';
    $css_version  = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

    wp_register_style(
        'bw-filtered-post-wall-style',
        plugin_dir_url( __FILE__ ) . 'assets/css/bw-filtered-post-wall.css',
        [],
        $css_version
    );

    $js_file    = __DIR__ . '/assets/js/bw-filtered-post-wall.js';
    $js_version = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

    wp_register_script(
        'bw-filtered-post-wall-js',
        plugin_dir_url( __FILE__ ) . 'assets/js/bw-filtered-post-wall.js',
        [ 'jquery', 'imagesloaded', 'masonry' ],
        $js_version,
        true
    );

    // Localize script per AJAX
    wp_localize_script(
        'bw-filtered-post-wall-js',
        'bwFilteredPostWallAjax',
        [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'bw_fpw_nonce' ),
        ]
    );
}

function bw_enqueue_filtered_post_wall_widget_assets() {
    if ( ! wp_style_is( 'bw-filtered-post-wall-style', 'registered' ) || ! wp_script_is( 'bw-filtered-post-wall-js', 'registered' ) ) {
        bw_register_filtered_post_wall_widget_assets();
    }

    if ( wp_style_is( 'bw-filtered-post-wall-style', 'registered' ) ) {
        wp_enqueue_style( 'bw-filtered-post-wall-style' );
    }

    if ( wp_script_is( 'bw-filtered-post-wall-js', 'registered' ) ) {
        wp_enqueue_script( 'bw-filtered-post-wall-js' );
    }
}

function bw_enqueue_smart_header_assets() {
    // Non caricare nell'admin di WordPress
    if ( is_admin() ) {
        return;
    }

    // Non caricare nell'editor di Elementor
    if ( defined( 'ELEMENTOR_VERSION' ) && \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
        return;
    }

    // Registra e carica CSS
    $css_file = __DIR__ . '/assets/css/bw-smart-header.css';
    $css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '2.0.0';

    wp_enqueue_style(
        'bw-smart-header-style',
        plugin_dir_url( __FILE__ ) . 'assets/css/bw-smart-header.css',
        [],
        $css_version
    );

    // Registra e carica JavaScript
    $js_file = __DIR__ . '/assets/js/bw-smart-header.js';
    $js_version = file_exists( $js_file ) ? filemtime( $js_file ) : '2.0.0';

    wp_enqueue_script(
        'bw-smart-header-script',
        plugin_dir_url( __FILE__ ) . 'assets/js/bw-smart-header.js',
        [ 'jquery' ],
        $js_version,
        true // Carica nel footer
    );

    // Passa configurazione al JavaScript tramite wp_localize_script
    wp_localize_script(
        'bw-smart-header-script',
        'bwSmartHeaderConfig',
        [
            'scrollDownThreshold' => 100,  // Pixel prima di nascondere header (scroll giù)
            'scrollUpThreshold'   => 0,    // IMMEDIATO (anche 1px verso l'alto)
            'scrollDelta'         => 1,    // Sensibilità rilevamento scroll
            'blurThreshold'       => 50,   // Pixel prima di attivare blur
            'throttleDelay'       => 16,   // ~60fps
            'headerSelector'      => '.smart-header',
            'debug'               => false // Imposta true per debug in console
        ]
    );
}

function bw_register_animated_banner_widget_assets() {
    $css_file = __DIR__ . '/assets/css/bw-animated-banner.css';
    $css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

    wp_register_style(
        'bw-animated-banner-style',
        plugin_dir_url( __FILE__ ) . 'assets/css/bw-animated-banner.css',
        [],
        $css_version
    );

    $js_file = __DIR__ . '/assets/js/bw-animated-banner.js';
    $js_version = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

    wp_register_script(
        'bw-animated-banner-script',
        plugin_dir_url( __FILE__ ) . 'assets/js/bw-animated-banner.js',
        [ 'jquery' ],
        $js_version,
        true
    );
}

function bw_enqueue_animated_banner_widget_assets() {
    if ( ! wp_style_is( 'bw-animated-banner-style', 'registered' ) || ! wp_script_is( 'bw-animated-banner-script', 'registered' ) ) {
        bw_register_animated_banner_widget_assets();
    }

    if ( wp_style_is( 'bw-animated-banner-style', 'registered' ) ) {
        wp_enqueue_style( 'bw-animated-banner-style' );
    }

    if ( wp_script_is( 'bw-animated-banner-script', 'registered' ) ) {
        wp_enqueue_script( 'bw-animated-banner-script' );
    }
}

function bw_register_static_showcase_widget_assets() {
    $css_file = __DIR__ . '/assets/css/bw-static-showcase.css';
    $css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

    wp_register_style(
        'bw-static-showcase-style',
        plugin_dir_url( __FILE__ ) . 'assets/css/bw-static-showcase.css',
        [],
        $css_version
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

/**
 * Handler AJAX per la ricerca live dei prodotti
 */
add_action( 'wp_ajax_bw_live_search_products', 'bw_live_search_products' );
add_action( 'wp_ajax_nopriv_bw_live_search_products', 'bw_live_search_products' );

function bw_live_search_products() {
    // Verifica nonce per sicurezza
    check_ajax_referer( 'bw_search_nonce', 'nonce' );

    // Ottieni parametri dalla richiesta
    $search_term  = isset( $_POST['search_term'] ) ? sanitize_text_field( $_POST['search_term'] ) : '';
    $categories   = isset( $_POST['categories'] ) ? array_map( 'sanitize_text_field', $_POST['categories'] ) : [];
    $product_type = isset( $_POST['product_type'] ) ? sanitize_text_field( $_POST['product_type'] ) : '';

    // Se il termine di ricerca è troppo corto, restituisci risultati vuoti
    if ( strlen( $search_term ) < 2 ) {
        wp_send_json_success( [
            'products' => [],
            'message'  => '',
        ] );
    }

    // Prepara gli argomenti della query
    $args = [
        'post_type'      => 'product',
        'posts_per_page' => 12,
        'post_status'    => 'publish',
        's'              => $search_term,
    ];

    // Prepara tax_query per filtri
    $tax_query = [];

    // Aggiungi filtro per categorie se specificato
    if ( ! empty( $categories ) ) {
        $tax_query[] = [
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => $categories,
        ];
    }

    // Aggiungi filtro per product type se specificato (include tipi personalizzati)
    if ( ! empty( $product_type ) && in_array( $product_type, [ 'simple', 'variable', 'grouped', 'external', 'digital_assets', 'books', 'prints' ], true ) ) {
        $tax_query[] = [
            'taxonomy' => 'product_type',
            'field'    => 'slug',
            'terms'    => $product_type,
        ];
    }

    // Aggiungi tax_query agli args se non è vuoto
    if ( ! empty( $tax_query ) ) {
        // Se c'è più di un filtro, specifica la relazione AND
        if ( count( $tax_query ) > 1 ) {
            $tax_query['relation'] = 'AND';
        }
        $args['tax_query'] = $tax_query;
    }

    // Esegui la query
    $query = new WP_Query( $args );

    $products = [];

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $product_id = get_the_ID();
            $product    = wc_get_product( $product_id );

            if ( ! $product ) {
                continue;
            }

            // Ottieni l'immagine in evidenza
            $image_id  = $product->get_image_id();
            $image_url = '';

            if ( $image_id ) {
                $image_url = wp_get_attachment_image_url( $image_id, 'medium' );
            }

            // Se non c'è immagine, usa un placeholder
            if ( ! $image_url ) {
                $image_url = wc_placeholder_img_src( 'medium' );
            }

            // Prepara i dati del prodotto
            $products[] = [
                'id'         => $product_id,
                'title'      => get_the_title(),
                'price_html' => $product->get_price_html(),
                'image_url'  => $image_url,
                'permalink'  => get_permalink( $product_id ),
            ];
        }
        wp_reset_postdata();
    }

    // Restituisci i risultati
    wp_send_json_success( [
        'products' => $products,
        'message'  => empty( $products ) ? __( 'Nessun prodotto trovato', 'bw' ) : '',
    ] );
}

/**
 * Handler AJAX per ottenere le subcategorie di una categoria
 */
add_action( 'wp_ajax_bw_fpw_get_subcategories', 'bw_fpw_get_subcategories' );
add_action( 'wp_ajax_nopriv_bw_fpw_get_subcategories', 'bw_fpw_get_subcategories' );

function bw_fpw_get_subcategories() {
    check_ajax_referer( 'bw_fpw_nonce', 'nonce' );

    $raw_category_id = isset( $_POST['category_id'] ) ? sanitize_text_field( wp_unslash( $_POST['category_id'] ) ) : '';
    $category_id     = 'all' === $raw_category_id ? 'all' : absint( $raw_category_id );
    $post_type       = isset( $_POST['post_type'] ) ? sanitize_key( $_POST['post_type'] ) : 'product';

    // PERFORMANCE: Check transient cache first (5 minutes)
    $transient_key = 'bw_fpw_subcats_' . $post_type . '_' . $category_id;
    $cached_result = get_transient( $transient_key );

    if ( false !== $cached_result ) {
        wp_send_json_success( $cached_result );
        return;
    }

    $taxonomy = 'product' === $post_type ? 'product_cat' : 'category';

    $get_terms_args = [
        'taxonomy'   => $taxonomy,
        'hide_empty' => true,
    ];

    if ( 'all' !== $category_id ) {
        $get_terms_args['parent'] = $category_id;
    }

    $subcategories = get_terms( $get_terms_args );

    if ( 'all' === $category_id && ! is_wp_error( $subcategories ) ) {
        $subcategories = array_filter(
            $subcategories,
            static function ( $term ) {
                return (int) $term->parent > 0;
            }
        );
    }

    if ( empty( $category_id ) && 'all' !== $category_id ) {
        wp_send_json_error( [ 'message' => 'Invalid category ID' ] );
    }

    if ( is_wp_error( $subcategories ) ) {
        wp_send_json_error( [ 'message' => $subcategories->get_error_message() ] );
    }

    $result = [];

    foreach ( $subcategories as $subcat ) {
        $result[] = [
            'term_id' => $subcat->term_id,
            'name'    => $subcat->name,
            'count'   => $subcat->count,
        ];
    }

    // PERFORMANCE: Cache result for 5 minutes
    set_transient( $transient_key, $result, 5 * MINUTE_IN_SECONDS );

    wp_send_json_success( $result );
}

/**
 * Handler AJAX per ottenere i tag di una categoria
 */
add_action( 'wp_ajax_bw_fpw_get_tags', 'bw_fpw_get_tags' );
add_action( 'wp_ajax_nopriv_bw_fpw_get_tags', 'bw_fpw_get_tags' );

function bw_fpw_get_tags() {
    check_ajax_referer( 'bw_fpw_nonce', 'nonce' );

    $raw_category_id = isset( $_POST['category_id'] ) ? sanitize_text_field( wp_unslash( $_POST['category_id'] ) ) : '';
    $category_id     = 'all' === $raw_category_id ? 'all' : absint( $raw_category_id );
    $post_type       = isset( $_POST['post_type'] ) ? sanitize_key( $_POST['post_type'] ) : 'product';
    $subcategories   = isset( $_POST['subcategories'] ) ? array_map( 'absint', (array) $_POST['subcategories'] ) : [];

    // PERFORMANCE: Check transient cache first (5 minutes)
    $subcats_hash  = md5( wp_json_encode( $subcategories ) );
    $transient_key = 'bw_fpw_tags_' . $post_type . '_' . $category_id . '_' . $subcats_hash;
    $cached_result = get_transient( $transient_key );

    if ( false !== $cached_result ) {
        wp_send_json_success( $cached_result );
        return;
    }

    // Get tags using existing helper function
    $tags = bw_fpw_get_related_tags_data( $post_type, $category_id, $subcategories );

    if ( empty( $tags ) ) {
        // Cache empty result too to avoid repeated queries
        set_transient( $transient_key, [], 5 * MINUTE_IN_SECONDS );
        wp_send_json_success( [] );
        return;
    }

    // PERFORMANCE: Cache result for 5 minutes
    set_transient( $transient_key, $tags, 5 * MINUTE_IN_SECONDS );

    wp_send_json_success( $tags );
}

function bw_fpw_get_filtered_post_ids_for_tags( $post_type, $category, $subcategories ) {
    $taxonomy  = 'product' === $post_type ? 'product_cat' : 'category';
    $tax_query = [];

    if ( 'all' !== $category && absint( $category ) > 0 ) {
        if ( ! empty( $subcategories ) ) {
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field'    => 'term_id',
                'terms'    => $subcategories,
            ];
        } else {
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field'    => 'term_id',
                'terms'    => [ absint( $category ) ],
            ];
        }
    }

    $query_args = [
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'tax_query'      => $tax_query,
    ];

    $query = new WP_Query( $query_args );

    return $query->posts;
}

function bw_fpw_collect_tags_from_posts( $taxonomy, $post_ids ) {
    if ( empty( $post_ids ) ) {
        return [];
    }

    $results = [];

    foreach ( $post_ids as $post_id ) {
        $terms = wp_get_object_terms( $post_id, $taxonomy );

        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            continue;
        }

        foreach ( $terms as $term ) {
            $term_id = (int) $term->term_id;

            if ( ! isset( $results[ $term_id ] ) ) {
                $results[ $term_id ] = [
                    'term_id' => $term_id,
                    'name'    => $term->name,
                    'count'   => 0,
                ];
            }

            $results[ $term_id ]['count']++;
        }
    }

    usort(
        $results,
        static function ( $a, $b ) {
            return strcmp( $a['name'], $b['name'] );
        }
    );

    return array_values( $results );
}

function bw_fpw_get_related_tags_data( $post_type, $category = 'all', $subcategories = [] ) {
    $tag_taxonomy = 'product' === $post_type ? 'product_tag' : 'post_tag';

    if ( 'all' === $category || empty( $category ) ) {
        $terms = get_terms(
            [
                'taxonomy'   => $tag_taxonomy,
                'hide_empty' => true,
            ]
        );

        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            return [];
        }

        $results = [];

        foreach ( $terms as $term ) {
            $results[] = [
                'term_id' => (int) $term->term_id,
                'name'    => $term->name,
                'count'   => (int) $term->count,
            ];
        }

        return $results;
    }

    $post_ids = bw_fpw_get_filtered_post_ids_for_tags( $post_type, $category, $subcategories );

    return bw_fpw_collect_tags_from_posts( $tag_taxonomy, $post_ids );
}

function bw_fpw_render_tag_markup( $tags ) {
    if ( empty( $tags ) ) {
        return '';
    }

    ob_start();

    foreach ( $tags as $tag ) {
        ?>
        <button class="bw-fpw-filter-option bw-fpw-tag-button" data-tag="<?php echo esc_attr( $tag['term_id'] ); ?>">
            <span class="bw-fpw-option-label"><?php echo esc_html( $tag['name'] ); ?></span> <span class="bw-fpw-option-count">(<?php echo esc_html( $tag['count'] ); ?>)</span>
        </button>
        <?php
    }

    return ob_get_clean();
}

/**
 * Handler AJAX per filtrare i post
 */
add_action( 'wp_ajax_bw_fpw_filter_posts', 'bw_fpw_filter_posts' );
add_action( 'wp_ajax_nopriv_bw_fpw_filter_posts', 'bw_fpw_filter_posts' );

function bw_fpw_filter_posts() {
    check_ajax_referer( 'bw_fpw_nonce', 'nonce' );

    $widget_id      = isset( $_POST['widget_id'] ) ? sanitize_text_field( $_POST['widget_id'] ) : '';
    $post_type      = isset( $_POST['post_type'] ) ? sanitize_key( $_POST['post_type'] ) : 'product';
    $category       = isset( $_POST['category'] ) ? sanitize_text_field( $_POST['category'] ) : 'all';
    $subcategories  = isset( $_POST['subcategories'] ) ? array_map( 'absint', (array) $_POST['subcategories'] ) : [];
    $tags           = isset( $_POST['tags'] ) ? array_map( 'absint', (array) $_POST['tags'] ) : [];
    $image_toggle   = isset( $_POST['image_toggle'] )
        ? filter_var( wp_unslash( $_POST['image_toggle'] ), FILTER_VALIDATE_BOOLEAN )
        : false;
    $image_size     = isset( $_POST['image_size'] ) ? sanitize_text_field( $_POST['image_size'] ) : 'large';
    $hover_effect   = isset( $_POST['hover_effect'] )
        ? filter_var( wp_unslash( $_POST['hover_effect'] ), FILTER_VALIDATE_BOOLEAN )
        : false;
    $open_cart_popup = isset( $_POST['open_cart_popup'] )
        ? filter_var( wp_unslash( $_POST['open_cart_popup'] ), FILTER_VALIDATE_BOOLEAN )
        : false;
    $order_by       = isset( $_POST['order_by'] ) ? sanitize_key( $_POST['order_by'] ) : 'date';
    $order          = isset( $_POST['order'] ) ? strtoupper( sanitize_key( $_POST['order'] ) ) : 'DESC';

    // Validate order_by
    $valid_order_by = [ 'date', 'modified', 'title', 'rand', 'ID' ];
    if ( ! in_array( $order_by, $valid_order_by, true ) ) {
        $order_by = 'date';
    }

    // Validate order
    if ( ! in_array( $order, [ 'ASC', 'DESC' ], true ) ) {
        $order = 'DESC';
    }

    // For random order, ignore ASC/DESC and skip caching
    $skip_cache = false;
    if ( 'rand' === $order_by ) {
        $order = 'ASC';
        $skip_cache = true; // Don't cache random results
    }

    // PERFORMANCE: Check transient cache first (3 minutes)
    // Skip cache for random order
    if ( ! $skip_cache ) {
        $cache_key_parts = [
            'bw_fpw_filter',
            $widget_id,
            $post_type,
            $category,
            md5( wp_json_encode( $subcategories ) ),
            md5( wp_json_encode( $tags ) ),
            $image_toggle ? '1' : '0',
            $image_size,
            $hover_effect ? '1' : '0',
            $open_cart_popup ? '1' : '0',
            $order_by,
            $order,
        ];
        $transient_key = implode( '_', $cache_key_parts );
        $transient_key = substr( $transient_key, 0, 172 ); // WordPress transient key max length

        $cached_result = get_transient( $transient_key );

        if ( false !== $cached_result ) {
            wp_send_json_success( $cached_result );
            return;
        }
    }

    $taxonomy     = 'product' === $post_type ? 'product_cat' : 'category';
    $tag_taxonomy = 'product' === $post_type ? 'product_tag' : 'post_tag';

    $query_args = [
        'post_type'      => $post_type,
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => $order_by,
        'order'          => $order,
    ];

    $tax_query = [];

    // Category filter
    if ( 'all' !== $category && absint( $category ) > 0 ) {
        if ( ! empty( $subcategories ) ) {
            // Filter by subcategories
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field'    => 'term_id',
                'terms'    => $subcategories,
            ];
        } else {
            // Filter by parent category
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field'    => 'term_id',
                'terms'    => [ absint( $category ) ],
            ];
        }
    }

    // Tags filter
    if ( ! empty( $tags ) ) {
        $tax_query[] = [
            'taxonomy' => $tag_taxonomy,
            'field'    => 'term_id',
            'terms'    => $tags,
        ];
    }

    // Add tax_query if not empty
    if ( ! empty( $tax_query ) ) {
        if ( count( $tax_query ) > 1 ) {
            $tax_query['relation'] = 'AND';
        }
        $query_args['tax_query'] = $tax_query;
    }

    $query = new WP_Query( $query_args );

    $has_posts = $query->have_posts();

    ob_start();

    if ( $has_posts ) {
        while ( $query->have_posts() ) {
            $query->the_post();

            $post_id   = get_the_ID();
            $permalink = get_permalink( $post_id );
            $title     = get_the_title( $post_id );
            $excerpt   = get_the_excerpt( $post_id );

            if ( empty( $excerpt ) ) {
                $excerpt = wp_trim_words( wp_strip_all_tags( get_the_content( null, false, $post_id ) ), 30 );
            }

            if ( ! empty( $excerpt ) && false === strpos( $excerpt, '<p' ) ) {
                $excerpt = '<p>' . $excerpt . '</p>';
            }

            $thumbnail_html = '';

            if ( $image_toggle && has_post_thumbnail( $post_id ) ) {
                $thumbnail_id = get_post_thumbnail_id( $post_id );

                if ( $thumbnail_id ) {
                    $thumbnail_html = wp_get_attachment_image(
                        $thumbnail_id,
                        $image_size,
                        false,
                        [
                            'loading' => 'eager',
                            'class'   => 'bw-slider-main',
                        ]
                    );
                }
            }

            $hover_image_html = '';
            if ( $hover_effect && 'product' === $post_type ) {
                $hover_image_id = (int) get_post_meta( $post_id, '_bw_slider_hover_image', true );

                if ( $hover_image_id ) {
                    $hover_image_html = wp_get_attachment_image(
                        $hover_image_id,
                        $image_size,
                        false,
                        [
                            'class'   => 'bw-slider-hover',
                            'loading' => 'eager',
                        ]
                    );
                }
            }

            $price_html     = '';
            $has_add_to_cart = false;
            $add_to_cart_url = '';

            if ( 'product' === $post_type ) {
                $price_html = bw_fpw_get_price_markup( $post_id );

                if ( function_exists( 'wc_get_product' ) ) {
                    $product = wc_get_product( $post_id );

                    if ( $product ) {
                        if ( $product->is_type( 'variable' ) ) {
                            $add_to_cart_url = $permalink;
                        } else {
                            $cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '';

                            if ( $cart_url ) {
                                $add_to_cart_url = add_query_arg( 'add-to-cart', $product->get_id(), $cart_url );
                            }
                        }

                        if ( ! $add_to_cart_url ) {
                            $add_to_cart_url = $permalink;
                        }

                        $has_add_to_cart = true;
                    }
                }
            }

            $view_label = 'product' === $post_type
                ? esc_html__( 'View Product', 'bw-elementor-widgets' )
                : esc_html__( 'Read More', 'bw-elementor-widgets' );
            ?>
            <article <?php post_class( 'bw-fpw-item' ); ?>>
                <div class="bw-fpw-card">
                    <div class="bw-slider-image-container">
                        <?php
                        $media_classes = [ 'bw-fpw-media' ];
                        if ( ! $thumbnail_html ) {
                            $media_classes[] = 'bw-fpw-media--placeholder';
                        }
                        ?>
                        <div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $media_classes ) ) ); ?>">
                            <?php if ( $thumbnail_html ) : ?>
                                <a class="bw-fpw-media-link" href="<?php echo esc_url( $permalink ); ?>">
                                    <div class="bw-fpw-image bw-slick-slider-image<?php echo $hover_image_html ? ' bw-fpw-image--has-hover bw-slick-slider-image--has-hover' : ''; ?>">
                                        <?php echo wp_kses_post( $thumbnail_html ); ?>
                                        <?php if ( $hover_image_html ) : ?>
                                            <?php echo wp_kses_post( $hover_image_html ); ?>
                                        <?php endif; ?>
                                    </div>
                                </a>

                                <div class="bw-fpw-overlay overlay-buttons has-buttons">
                                    <div class="bw-fpw-overlay-buttons<?php echo $has_add_to_cart ? ' bw-fpw-overlay-buttons--double' : ''; ?>">
                                        <a class="bw-fpw-overlay-button overlay-button overlay-button--view" href="<?php echo esc_url( $permalink ); ?>">
                                            <span class="bw-fpw-overlay-button__label overlay-button__label"><?php echo $view_label; ?></span>
                                        </a>
                                        <?php if ( 'product' === $post_type && $has_add_to_cart && $add_to_cart_url ) : ?>
                                            <a class="bw-fpw-overlay-button overlay-button overlay-button--cart" href="<?php echo esc_url( $add_to_cart_url ); ?>"<?php echo $open_cart_popup ? ' data-open-cart-popup="1"' : ''; ?>>
                                                <span class="bw-fpw-overlay-button__label overlay-button__label"><?php esc_html_e( 'Add to Cart', 'bw-elementor-widgets' ); ?></span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else : ?>
                                <span class="bw-fpw-image-placeholder" aria-hidden="true"></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="bw-fpw-content bw-slider-content">
                        <h3 class="bw-fpw-title">
                            <a href="<?php echo esc_url( $permalink ); ?>">
                                <?php echo esc_html( $title ); ?>
                            </a>
                        </h3>

                        <?php if ( ! empty( $excerpt ) ) : ?>
                            <div class="bw-fpw-description"><?php echo wp_kses_post( $excerpt ); ?></div>
                        <?php endif; ?>

                        <?php if ( $price_html ) : ?>
                            <div class="bw-fpw-price price"><?php echo wp_kses_post( $price_html ); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php
        }
    } else {
        ?>
        <div class="bw-fpw-empty-state">
            <p class="bw-fpw-empty-message"><?php esc_html_e( 'No content available', 'bw-elementor-widgets' ); ?></p>
            <button class="elementor-button bw-fpw-reset-filters" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                <?php esc_html_e( 'RESET FILTERS', 'bw-elementor-widgets' ); ?>
            </button>
        </div>
        <?php
    }

    wp_reset_postdata();

    $html = ob_get_clean();

    $related_tags  = bw_fpw_get_related_tags_data( $post_type, $category, $subcategories );
    $available_tags = wp_list_pluck( $related_tags, 'term_id' );

    $response_data = [
        'html'            => $html,
        'tags_html'       => bw_fpw_render_tag_markup( $related_tags ),
        'available_tags'  => $available_tags,
        'has_posts'       => $has_posts,
    ];

    // PERFORMANCE: Cache result for 3 minutes (skip random order)
    if ( ! $skip_cache && isset( $transient_key ) ) {
        set_transient( $transient_key, $response_data, 3 * MINUTE_IN_SECONDS );
    }

    wp_send_json_success( $response_data );
}

/**
 * Helper function per ottenere il markup del prezzo
 */
function bw_fpw_get_price_markup( $post_id ) {
    if ( ! $post_id ) {
        return '';
    }

    $format_price = static function ( $value ) {
        if ( '' === $value || null === $value ) {
            return '';
        }

        if ( function_exists( 'wc_price' ) && is_numeric( $value ) ) {
            return wc_price( $value );
        }

        if ( is_numeric( $value ) ) {
            $value = number_format_i18n( (float) $value, 2 );
        }

        return esc_html( $value );
    };

    if ( function_exists( 'wc_get_product' ) ) {
        $product = wc_get_product( $post_id );
        if ( $product ) {
            $price_html = $product->get_price_html();
            if ( ! empty( $price_html ) ) {
                return $price_html;
            }

            $regular_price = $product->get_regular_price();
            $sale_price    = $product->get_sale_price();
            $current_price = $product->get_price();

            $regular_markup = $format_price( $regular_price );
            $sale_markup    = $format_price( $sale_price );
            $current_markup = $format_price( $current_price );

            if ( $sale_markup && $regular_markup && $sale_markup !== $regular_markup ) {
                return '<span class="price-original"><del>' . $regular_markup . '</del></span>' .
                    '<span class="price-sale">' . $sale_markup . '</span>';
            }

            if ( $current_markup ) {
                return '<span class="price-regular">' . $current_markup . '</span>';
            }
        }
    }

    return '';
}
