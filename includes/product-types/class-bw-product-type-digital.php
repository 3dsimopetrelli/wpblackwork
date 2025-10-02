<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( class_exists( 'WC_Product_Variable' ) && ! class_exists( 'WC_Product_Digital_Asset' ) ) {

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
     * Always treat digital assets as virtual products.
     *
     * @return bool
     */
    public function is_virtual() {
        return true;
    }

    /**
     * Always treat digital assets as downloadable products.
     *
     * @return bool
     */
    public function is_downloadable() {
        return true;
    }
}

}
