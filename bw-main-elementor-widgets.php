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


// Helper functions
require_once __DIR__ . '/includes/helpers.php';

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
	// Metabox Images Showcase
	require_once plugin_dir_path( __FILE__ ) . 'metabox/images-showcase-metabox.php';
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
add_action( 'wp_enqueue_scripts', 'bw_enqueue_smart_header_assets' );

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
    $search_term = isset( $_POST['search_term'] ) ? sanitize_text_field( $_POST['search_term'] ) : '';
    $categories  = isset( $_POST['categories'] ) ? array_map( 'sanitize_text_field', $_POST['categories'] ) : [];

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

    // Aggiungi filtro per categorie se specificato
    if ( ! empty( $categories ) ) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $categories,
            ],
        ];
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
