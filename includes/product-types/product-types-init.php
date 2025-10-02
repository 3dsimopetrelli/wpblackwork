<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . 'class-bw-product-type-digital.php';
require_once plugin_dir_path( __FILE__ ) . 'class-bw-product-type-book.php';
require_once plugin_dir_path( __FILE__ ) . 'class-bw-product-type-print.php';

// Mostrare le tabs di attributi, variazioni, spedizione anche per i custom types.
add_filter( 'woocommerce_product_data_tabs', function( $tabs ) {
    $custom_types = [ 'digital_asset', 'book', 'print' ];

    foreach ( $custom_types as $type ) {
        $tabs['attribute']['class'][]  = 'show_if_' . $type;
        $tabs['variations']['class'][] = 'show_if_' . $type;
        $tabs['shipping']['class'][]   = 'show_if_' . $type;
        $tabs['inventory']['class'][]  = 'show_if_' . $type;
    }

    return $tabs;
} );

// Aggiungere le sezioni pannelli variabili.
add_action( 'admin_footer', function() {
    global $pagenow, $post;

    if ( $pagenow === 'post.php' && $post instanceof WP_Post && get_post_type( $post ) === 'product' ) : ?>
        <script type="text/javascript">
            jQuery(document).ready(function($){
                var custom_types = ['digital_asset','book','print'];
                custom_types.forEach(function(type){
                    $('.options_group.show_if_variable').addClass('show_if_' + type);
                    $('#variable_product_options').addClass('show_if_' + type);
                });
            });
        </script>
    <?php endif;
} );

/**
 * Register custom product types and related functionality.
 */
function bw_register_custom_product_types() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    add_filter( 'product_type_selector', 'bw_add_product_type_options' );
    add_filter( 'woocommerce_product_class', 'bw_register_product_type_classes', 10, 2 );
    add_action( 'admin_menu', 'bw_register_product_type_admin_menu', 99 );
}
add_action( 'init', 'bw_register_custom_product_types' );

/**
 * Add custom product types to the product type selector dropdown.
 *
 * @param array $types Existing product types.
 *
 * @return array
 */
function bw_add_product_type_options( $types ) {
    $types['digital_asset'] = __( 'Digital Assets', 'bw-main-elementor-widgets' );
    $types['book']          = __( 'Books', 'bw-main-elementor-widgets' );
    $types['print']         = __( 'Prints', 'bw-main-elementor-widgets' );

    return $types;
}

/**
 * Map custom product type slugs to their respective classes.
 *
 * @param string $classname Class name determined by WooCommerce.
 * @param string $product_type Product type slug.
 *
 * @return string
 */
function bw_register_product_type_classes( $classname, $product_type ) {
    switch ( $product_type ) {
        case 'digital_asset':
            $classname = WC_Product_Digital_Asset::class;
            break;
        case 'book':
            $classname = WC_Product_Book::class;
            break;
        case 'print':
            $classname = WC_Product_Print::class;
            break;
    }

    return $classname;
}

/**
 * Register submenu pages for the custom product types.
 */
function bw_register_product_type_admin_menu() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    add_submenu_page(
        'edit.php?post_type=product',
        __( 'Digital Assets', 'bw-main-elementor-widgets' ),
        __( 'Digital Assets', 'bw-main-elementor-widgets' ),
        'manage_woocommerce',
        'bw-digital-asset-products',
        'bw_render_digital_asset_products_page'
    );

    add_submenu_page(
        'edit.php?post_type=product',
        __( 'Books', 'bw-main-elementor-widgets' ),
        __( 'Books', 'bw-main-elementor-widgets' ),
        'manage_woocommerce',
        'bw-book-products',
        'bw_render_book_products_page'
    );

    add_submenu_page(
        'edit.php?post_type=product',
        __( 'Prints', 'bw-main-elementor-widgets' ),
        __( 'Prints', 'bw-main-elementor-widgets' ),
        'manage_woocommerce',
        'bw-print-products',
        'bw_render_print_products_page'
    );
}

/**
 * Redirect submenu pages to the filtered product list.
 */
function bw_render_digital_asset_products_page() {
    bw_redirect_to_product_type_list( 'digital_asset' );
}

/**
 * Redirect submenu pages to the filtered product list.
 */
function bw_render_book_products_page() {
    bw_redirect_to_product_type_list( 'book' );
}

/**
 * Redirect submenu pages to the filtered product list.
 */
function bw_render_print_products_page() {
    bw_redirect_to_product_type_list( 'print' );
}

/**
 * Helper to redirect to the edit screen filtered by product type.
 *
 * @param string $product_type Product type slug.
 */
function bw_redirect_to_product_type_list( $product_type ) {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( esc_html__( 'You do not have permission to access this page.', 'bw-main-elementor-widgets' ) );
    }

    wp_safe_redirect( admin_url( 'edit.php?post_type=product&product_type=' . $product_type ) );
    exit;
}
