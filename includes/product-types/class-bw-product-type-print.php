<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( class_exists( 'WC_Product_Variable' ) && ! class_exists( 'WC_Product_Print' ) ) {

class WC_Product_Print extends WC_Product_Variable {

    /**
     * Product type identifier.
     *
     * @var string
     */
    protected $product_type = 'print';

    /**
     * WC_Product_Print constructor.
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
        return 'print';
    }
}

}
