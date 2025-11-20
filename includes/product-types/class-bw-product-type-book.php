<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( class_exists( 'WC_Product_Variable' ) && ! class_exists( 'WC_Product_Book' ) ) {

/**
 * WC_Product_Book Class
 *
 * Classe per prodotti di tipo "Books" - prodotti fisici solidi da spedire.
 *
 * Caratteristiche:
 * - Prodotto fisico (NON virtual)
 * - Prodotto da spedire (ha campi di peso, dimensioni, classe di spedizione)
 * - NON scaricabile (NON downloadable)
 * - Supporta variazioni (estende WC_Product_Variable)
 * - Mostra tutti i tab: General, Inventory, Shipping, Linked Products, Attributes, Variations, Advanced
 */
class WC_Product_Book extends WC_Product_Variable {

    /**
     * Product type identifier.
     *
     * @var string
     */
    protected $product_type = 'book';

    /**
     * Get the product type.
     *
     * @return string
     */
    public function get_type() {
        return 'book';
    }

    /**
     * Books are NEVER virtual - they are physical products that need shipping.
     *
     * @param string $context View or edit context.
     * @return bool
     */
    public function is_virtual( $context = 'view' ) {
        return false;
    }

    /**
     * Books are NEVER downloadable - they are physical products.
     *
     * @param string $context View or edit context.
     * @return bool
     */
    public function is_downloadable( $context = 'view' ) {
        return false;
    }

    /**
     * Books always need shipping since they are physical products.
     *
     * @return bool
     */
    public function needs_shipping() {
        return true;
    }
}

}
