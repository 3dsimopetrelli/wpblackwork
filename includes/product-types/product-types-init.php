<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . 'class-bw-product-type-digital.php';
require_once plugin_dir_path( __FILE__ ) . 'class-bw-product-type-book.php';
require_once plugin_dir_path( __FILE__ ) . 'class-bw-product-type-print.php';
require_once plugin_dir_path( __FILE__ ) . 'class-bw-product-slider-metabox.php';

/**
 * Personalizzazione tabs Product Data SOLO per i tipi di prodotto CUSTOM.
 *
 * IMPORTANTE: Questo filtro NON modifica i product type standard di WooCommerce
 * (Simple, Grouped, External, Variable). Aggiunge solo le classi show_if_* per
 * i custom product types, lasciando intatte tutte le funzionalità standard.
 *
 * - Digital Assets: prodotto virtuale scaricabile con variazioni
 * - Books: prodotto fisico da spedire con variazioni
 * - Prints: prodotto fisico da spedire con variazioni
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

    // Mostra Variations per Books
    if ( isset( $tabs['variations'] ) ) {
        $tabs['variations']['class'][] = 'show_if_book';
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

    // Mostra Variations per Prints
    if ( isset( $tabs['variations'] ) ) {
        $tabs['variations']['class'][] = 'show_if_print';
    }

    return $tabs;
}, 10, 1 );

/**
 * JavaScript per gestire correttamente i campi e le opzioni dei product types personalizzati.
 *
 * IMPORTANTE: Questo JavaScript NON modifica il comportamento dei product type standard
 * di WooCommerce. Gestisce SOLO i custom product types: DigitalAssets, Books, Prints.
 */
add_action( 'admin_footer', function() {
    global $pagenow, $post;
    if ( $pagenow === 'post.php' && get_post_type( $post ) === 'product' ) : ?>
        <script type="text/javascript">
            jQuery(document).ready(function($){
                /**
                 * STEP 1: Aggiungi le classi show_if_* per abilitare i pannelli variabili
                 * per i nostri custom product types.
                 */

                // DIGITAL ASSETS: Attivare i pannelli variabili e i campi downloadable
                $('.options_group.show_if_variable').addClass('show_if_digital_asset');
                $('#variable_product_options').addClass('show_if_digital_asset');
                $('.options_group.show_if_downloadable').addClass('show_if_digital_asset');
                $('.show_if_downloadable').addClass('show_if_digital_asset');

                // BOOKS: Attivare i pannelli variabili (prodotto fisico con variazioni)
                $('.options_group.show_if_variable').addClass('show_if_book');
                $('#variable_product_options').addClass('show_if_book');

                // PRINTS: Attivare i pannelli variabili (prodotto fisico con variazioni)
                $('.options_group.show_if_variable').addClass('show_if_print');
                $('#variable_product_options').addClass('show_if_print');

                /**
                 * STEP 2: Abilita il checkbox "Used for variations" negli attributi.
                 *
                 * Questo è fondamentale per permettere la creazione di variazioni.
                 * Il checkbox è normalmente mostrato solo per product type "variable",
                 * ma noi dobbiamo mostrarlo anche per i nostri custom product types.
                 */
                function enableVariationsCheckbox() {
                    var productType = $('#product-type').val();

                    // Abilita per i nostri custom product types
                    if (productType === 'digital_asset' || productType === 'book' || productType === 'print') {
                        // Trova tutti gli attributi e mostra il checkbox "Used for variations"
                        $('.woocommerce_attribute').each(function() {
                            var $attribute = $(this);

                            // Mostra il wrapper del checkbox "enable_variation"
                            $attribute.find('.enable_variation').parent().show();

                            // Mostra anche la label "Visible on the product page"
                            $attribute.find('.enable_variation').parent().prev('label').show();
                        });

                        // Mostra anche i controlli nella barra degli attributi
                        $('.toolbar .variation_actions').show();
                    }
                }

                // Esegui al caricamento della pagina
                enableVariationsCheckbox();

                // Esegui quando cambia il product type
                $('#product-type').on('change', function() {
                    enableVariationsCheckbox();
                });

                // Osserva quando vengono aggiunti nuovi attributi e abilita il checkbox
                var attributesContainer = $('.product_attributes');
                if (attributesContainer.length) {
                    var observer = new MutationObserver(function(mutations) {
                        // Ritarda leggermente per dare tempo a WooCommerce di renderizzare l'attributo
                        setTimeout(function() {
                            enableVariationsCheckbox();
                        }, 100);
                    });

                    observer.observe(attributesContainer[0], {
                        childList: true,
                        subtree: true
                    });
                }

                /**
                 * STEP 3: Gestisci la visibilità dei campi downloadable/virtual
                 * in base al product type.
                 */
                function handleDownloadableFields() {
                    var productType = $('#product-type').val();

                    if (productType === 'book' || productType === 'print') {
                        // BOOKS & PRINTS: Nascondere completamente i campi downloadable (prodotti fisici)
                        $('.show_if_downloadable').hide();
                        $('._downloadable_files_field').hide();
                        $('#_downloadable').prop('checked', false).closest('.form-field').hide();
                        $('#_virtual').prop('checked', false).closest('.form-field').hide();
                    }
                    else if (productType === 'digital_asset') {
                        // DIGITAL ASSETS: Mostrare i campi downloadable
                        $('.show_if_downloadable').show();
                        $('._downloadable_files_field').show();

                        // Nascondi i checkbox virtual/downloadable perché sono sempre true
                        // (gestiti automaticamente dalla classe del prodotto)
                        $('#_downloadable').closest('.form-field').hide();
                        $('#_virtual').closest('.form-field').hide();
                    }
                    // Per tutti gli altri product types (standard WooCommerce),
                    // non facciamo nulla - lasciamo il comportamento predefinito
                }

                // Esegui al caricamento della pagina
                handleDownloadableFields();

                // Esegui quando cambia il product type
                $('#product-type').on('change', function() {
                    handleDownloadableFields();
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
    add_action( 'woocommerce_process_product_meta', 'bw_force_product_type_meta_values', 10, 2 );
}
add_action( 'init', 'bw_register_custom_product_types' );

/**
 * Forza i valori corretti per _virtual e _downloadable SOLO per i custom product types.
 *
 * IMPORTANTE: Questa funzione agisce SOLO sui custom product types (DigitalAssets, Books, Prints).
 * I product type standard di WooCommerce (Simple, Grouped, External, Variable) NON sono modificati.
 *
 * Garantisce che:
 * - Digital Assets: virtual=yes, downloadable=yes (prodotto digitale scaricabile)
 * - Books: virtual=no, downloadable=no (prodotto fisico da spedire)
 * - Prints: virtual=no, downloadable=no (prodotto fisico da spedire)
 *
 * NOTA sugli attributi: Gli attributi (_product_attributes) sono sempre specifici del prodotto.
 * Ogni prodotto ha il proprio set di attributi salvato nel meta _product_attributes.
 * Le variazioni sono create correttamente per ogni prodotto in base ai suoi attributi.
 *
 * @param int    $post_id Product ID.
 * @param WP_Post $post    Post object.
 */
function bw_force_product_type_meta_values( $post_id, $post ) {
    // Ottieni il product type salvato
    $product_type = empty( $_POST['product-type'] ) ? 'simple' : sanitize_text_field( $_POST['product-type'] );

    // Applica le modifiche SOLO ai custom product types
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

        // I product type standard WooCommerce (simple, grouped, external, variable)
        // non vengono toccati - mantengono il comportamento predefinito di WooCommerce
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
