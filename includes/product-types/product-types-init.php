<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . 'class-bw-product-type-digital.php';
require_once plugin_dir_path( __FILE__ ) . 'class-bw-product-type-book.php';
require_once plugin_dir_path( __FILE__ ) . 'class-bw-product-type-print.php';
require_once plugin_dir_path( __FILE__ ) . 'class-bw-product-slider-metabox.php';

/**
 * Personalizzazione tabs Product Data per i tipi di prodotto personalizzati.
 *
 * - Digital Assets: prodotto virtuale scaricabile (mostra tutti i tab tranne Shipping)
 * - Books: prodotto fisico da spedire (mostra tutti i tab incluso Shipping, nasconde campi downloadable)
 * - Prints: prodotto fisico da spedire (mostra tutti i tab incluso Shipping, nasconde campi downloadable)
 */
add_filter( 'woocommerce_product_data_tabs', function( $tabs ) {
    // DIGITAL ASSETS: Mostra tutti i tab per prodotti virtual/downloadable
    $tabs['general']['class'][]    = 'show_if_digital_asset';
    $tabs['inventory']['class'][]  = 'show_if_digital_asset';
    $tabs['attribute']['class'][]  = 'show_if_digital_asset';
    $tabs['variations']['class'][] = 'show_if_digital_asset';
    $tabs['advanced']['class'][]   = 'show_if_digital_asset';

    // Nasconde Shipping per Digital Assets (prodotti virtuali)
    if ( isset( $tabs['shipping'] ) ) {
        $tabs['shipping']['class'][] = 'hide_if_digital_asset';
    }

    // Mostra Linked Products per Digital Assets
    if ( isset( $tabs['linked_product'] ) ) {
        $tabs['linked_product']['class'][] = 'show_if_digital_asset';
    }

    // BOOKS: Mostra tutti i tab per prodotti fisici (incluso Shipping)
    $tabs['general']['class'][]   = 'show_if_book';
    $tabs['inventory']['class'][] = 'show_if_book';
    $tabs['advanced']['class'][]  = 'show_if_book';

    // Mostra esplicitamente il tab Shipping per Books
    if ( isset( $tabs['shipping'] ) ) {
        $tabs['shipping']['class'][] = 'show_if_book';
    }

    // Mostra Linked Products per Books
    if ( isset( $tabs['linked_product'] ) ) {
        $tabs['linked_product']['class'][] = 'show_if_book';
    }

    // Mostra Attributes per Books
    if ( isset( $tabs['attribute'] ) ) {
        $tabs['attribute']['class'][] = 'show_if_book';
    }

    // PRINTS: Mostra tutti i tab per prodotti fisici (incluso Shipping)
    $tabs['general']['class'][]   = 'show_if_print';
    $tabs['inventory']['class'][] = 'show_if_print';
    $tabs['advanced']['class'][]  = 'show_if_print';

    // Mostra esplicitamente il tab Shipping per Prints
    if ( isset( $tabs['shipping'] ) ) {
        $tabs['shipping']['class'][] = 'show_if_print';
    }

    // Mostra Linked Products per Prints
    if ( isset( $tabs['linked_product'] ) ) {
        $tabs['linked_product']['class'][] = 'show_if_print';
    }

    // Mostra Attributes per Prints
    if ( isset( $tabs['attribute'] ) ) {
        $tabs['attribute']['class'][] = 'show_if_print';
    }

    return $tabs;
} );

/**
 * JavaScript per gestire correttamente i campi e le opzioni dei product types personalizzati.
 */
add_action( 'admin_footer', function() {
    global $pagenow, $post;
    if ( $pagenow === 'post.php' && get_post_type( $post ) === 'product' ) : ?>
        <script type="text/javascript">
            jQuery(document).ready(function($){
                // DIGITAL ASSETS: Attivare i pannelli variabili e i campi downloadable
                $('.options_group.show_if_variable').addClass('show_if_digital_asset');
                $('#variable_product_options').addClass('show_if_digital_asset');
                $('.options_group.show_if_downloadable').addClass('show_if_digital_asset');
                $('.show_if_downloadable').addClass('show_if_digital_asset');

                // BOOKS: Nascondere completamente i campi downloadable (prodotto fisico)
                $('#product-type').on('change', function() {
                    var selected = $(this).val();

                    if (selected === 'book') {
                        // Nascondi tutti i campi downloadable per Books
                        $('.show_if_downloadable').hide();
                        $('._downloadable_files_field').hide();
                        $('#_downloadable').prop('checked', false).closest('.form-field').hide();
                        $('#_virtual').prop('checked', false).closest('.form-field').hide();
                    }

                    if (selected === 'print') {
                        // Nascondi tutti i campi downloadable per Prints
                        $('.show_if_downloadable').hide();
                        $('._downloadable_files_field').hide();
                        $('#_downloadable').prop('checked', false).closest('.form-field').hide();
                        $('#_virtual').prop('checked', false).closest('.form-field').hide();
                    }

                    if (selected === 'digital_asset') {
                        // Mostra i campi downloadable per Digital Assets
                        $('.show_if_downloadable').show();
                        $('._downloadable_files_field').show();
                        // Nascondi i checkbox virtual/downloadable perché sono sempre true
                        $('#_downloadable').closest('.form-field').hide();
                        $('#_virtual').closest('.form-field').hide();
                    }
                }).trigger('change');

                // Esegui anche al caricamento della pagina per prodotti esistenti
                var currentProductType = $('#product-type').val();
                if (currentProductType === 'book' || currentProductType === 'print') {
                    $('.show_if_downloadable').hide();
                    $('._downloadable_files_field').hide();
                    $('#_downloadable').prop('checked', false).closest('.form-field').hide();
                    $('#_virtual').prop('checked', false).closest('.form-field').hide();
                } else if (currentProductType === 'digital_asset') {
                    $('.show_if_downloadable').show();
                    $('._downloadable_files_field').show();
                    $('#_downloadable').closest('.form-field').hide();
                    $('#_virtual').closest('.form-field').hide();
                }
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
    add_action( 'woocommerce_process_product_meta', 'bw_force_product_type_meta_values', 10, 2 );
}
add_action( 'init', 'bw_register_custom_product_types' );

/**
 * Forza i valori corretti per _virtual e _downloadable in base al product type.
 *
 * Questo garantisce che:
 * - Digital Assets siano sempre virtual=yes e downloadable=yes
 * - Books siano sempre virtual=no e downloadable=no
 * - Prints siano sempre virtual=no e downloadable=no
 *
 * @param int    $post_id Product ID.
 * @param WP_Post $post    Post object.
 */
function bw_force_product_type_meta_values( $post_id, $post ) {
    // Ottieni il product type salvato
    $product_type = empty( $_POST['product-type'] ) ? 'simple' : sanitize_text_field( $_POST['product-type'] );

    switch ( $product_type ) {
        case 'digital_asset':
            // Digital Assets: sempre virtual e downloadable
            update_post_meta( $post_id, '_virtual', 'yes' );
            update_post_meta( $post_id, '_downloadable', 'yes' );
            break;

        case 'book':
        case 'print':
            // Books e Prints: MAI virtual o downloadable
            update_post_meta( $post_id, '_virtual', 'no' );
            update_post_meta( $post_id, '_downloadable', 'no' );
            // Rimuovi eventuali file downloadable che potrebbero essere stati salvati erroneamente
            delete_post_meta( $post_id, '_downloadable_files' );
            break;
    }
}

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
