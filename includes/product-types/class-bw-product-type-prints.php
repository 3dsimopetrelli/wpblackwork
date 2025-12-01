<?php
/**
 * Prints Product Type
 *
 * Custom product type that extends Variable Product to support all features:
 * - Price, inventory, shipping, tax
 * - Attributes, variations
 * - All standard product tabs and options
 *
 * @package BWElementorWidgets
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prints Product Class
 *
 * Extends WC_Product_Variable to inherit all Variable product functionality:
 * - Price, inventory, shipping, tax
 * - Attributes, linked products, variations
 * - All standard product tabs and options
 *
 * Only difference: returns 'prints' as product type for filtering.
 */
class BW_Product_Prints extends WC_Product_Variable {

	/**
	 * Get the product type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'prints';
	}
}
