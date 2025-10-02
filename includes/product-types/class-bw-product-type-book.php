<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( class_exists( 'WC_Product_Simple' ) && ! class_exists( 'WC_Product_Book' ) ) {

class WC_Product_Book extends WC_Product_Simple {

    /**
     * Product type identifier.
     *
     * @var string
     */
    protected $product_type = 'book';

    /**
     * WC_Product_Book constructor.
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
        return 'book';
    }
}

}
