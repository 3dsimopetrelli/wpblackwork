<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( class_exists( 'WC_Product_Variable' ) && ! class_exists( 'WC_Product_Digital_Asset' ) ) {

/**
 * WC_Product_Digital_Asset Class
 *
 * Classe per prodotti di tipo "Digital Assets" - prodotti digitali virtuali scaricabili.
 *
 * Caratteristiche:
 * - Prodotto virtuale (virtual = true, nessun campo spedizione)
 * - Prodotto scaricabile (downloadable = true, con campi per file, download limit, expiry)
 * - Supporta variazioni (estende WC_Product_Variable)
 * - Mostra tutti i tab: General, Inventory, Linked Products, Attributes, Variations, Advanced
 * - NON mostra il tab Shipping (prodotti virtuali non hanno spedizione)
 */
class WC_Product_Digital_Asset extends WC_Product_Variable {

    /**
     * Product type identifier.
     *
     * @var string
     */
    protected $product_type = 'digital_asset';

    /**
     * Initialize the digital asset product.
     *
     * @param int|WC_Product $product Product ID or object.
     */
    public function __construct( $product ) {
        parent::__construct( $product );
    }

    /**
     * Get the product type.
     *
     * @return string
     */
    public function get_type() {
        return 'digital_asset';
    }

    /**
     * Digital Assets are ALWAYS virtual products (no shipping needed).
     *
     * @param string $context View or edit context.
     * @return bool
     */
    public function is_virtual( $context = 'view' ) {
        return true;
    }

    /**
     * Digital Assets are ALWAYS downloadable products.
     *
     * @param string $context View or edit context.
     * @return bool
     */
    public function is_downloadable( $context = 'view' ) {
        return true;
    }

    /**
     * Digital Assets never need shipping since they are virtual.
     *
     * @return bool
     */
    public function needs_shipping() {
        return false;
    }
}

}
