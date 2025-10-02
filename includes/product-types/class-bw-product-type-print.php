<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( class_exists( 'WC_Product_Simple' ) && ! class_exists( 'WC_Product_Print' ) ) {

class WC_Product_Print extends WC_Product_Simple {

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
}

}
