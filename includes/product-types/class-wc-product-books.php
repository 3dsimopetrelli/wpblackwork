<?php
/**
 * Books Product Type
 *
 * Custom product type that behaves exactly like a Simple Product.
 *
 * @package BWElementorWidgets
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Books Product Class - extends WC_Product_Simple
 *
 * This product type has the same functionality as a Simple Product,
 * with all standard Simple Product tabs and fields.
 */
class WC_Product_Books extends WC_Product_Simple {

	/**
	 * Initialize the product type.
	 *
	 * @param mixed $product Product object or ID.
	 */
	public function __construct( $product = 0 ) {
		$this->product_type = 'books';
		parent::__construct( $product );
	}

	/**
	 * Get the product type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'books';
	}
}
