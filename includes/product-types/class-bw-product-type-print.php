<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( class_exists( 'WC_Product_Variable' ) && ! class_exists( 'WC_Product_Print' ) ) {

/**
 * WC_Product_Print Class
 *
 * Classe per prodotti di tipo "Prints" - stampe fisiche da spedire.
 *
 * Caratteristiche:
 * - Prodotto fisico (NON virtual)
 * - Prodotto da spedire (ha campi di peso, dimensioni, classe di spedizione)
 * - NON scaricabile (NON downloadable)
 * - Supporta variazioni (estende WC_Product_Variable)
 * - Mostra tutti i tab: General, Inventory, Shipping, Linked Products, Attributes, Variations, Advanced
 */
class WC_Product_Print extends WC_Product_Variable {

    /**
     * Product type identifier.
     *
     * @var string
     */
    protected $product_type = 'print';

    /**
     * Get the product type.
     *
     * @return string
     */
    public function get_type() {
        return 'print';
    }

    /**
     * Prints are NEVER virtual - they are physical products that need shipping.
     *
     * @param string $context View or edit context.
     * @return bool
     */
    public function is_virtual( $context = 'view' ) {
        return false;
    }

    /**
     * Prints are NEVER downloadable - they are physical products.
     *
     * @param string $context View or edit context.
     * @return bool
     */
    public function is_downloadable( $context = 'view' ) {
        return false;
    }

    /**
     * Prints always need shipping since they are physical products.
     *
     * @return bool
     */
    public function needs_shipping() {
        return true;
    }
}

}
