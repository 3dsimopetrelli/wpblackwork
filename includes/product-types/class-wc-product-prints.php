<?php
/**
 * Prints Product Type
 *
 * Custom product type that behaves exactly like a Variable Product.
 *
 * @package BWElementorWidgets
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prints Product Class - extends WC_Product_Variable
 *
 * This product type has the same functionality as a Variable Product,
 * including attributes, variations, and all standard Variable Product tabs.
 */
class WC_Product_Prints extends WC_Product_Variable {

	/**
	 * Initialize the product type.
	 *
	 * @param mixed $product Product object or ID.
	 */
	public function __construct( $product = 0 ) {
		$this->product_type = 'prints';
		parent::__construct( $product );
	}

	/**
	 * Get the product type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'prints';
	}
}
