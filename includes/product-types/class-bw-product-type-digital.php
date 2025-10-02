<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( class_exists( 'WC_Product_Simple' ) && ! class_exists( 'WC_Product_Digital_Asset' ) ) {

class WC_Product_Digital_Asset extends WC_Product_Simple {

    /**
     * Product type identifier.
     *
     * @var string
     */
    protected $product_type = 'digital_asset';

    /**
     * WC_Product_Digital_Asset constructor.
     *
     * @param mixed $product Product to initialize.
     */
    public function __construct( $product = 0 ) {
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
}

}
