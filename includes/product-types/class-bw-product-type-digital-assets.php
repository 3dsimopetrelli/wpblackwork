<?php
/**
 * Digital Assets Product Type
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
 * Digital Assets Product Class
 *
 * Extends WC_Product_Variable to inherit all Variable product functionality:
 * - Price, inventory, shipping, tax
 * - Attributes, linked products, variations
 * - All standard product tabs and options
 *
 * Only difference: returns 'digital_assets' as product type for filtering.
 */
class BW_Product_Digital_Assets extends WC_Product_Variable {

	/**
	 * Get the product type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'digital_assets';
	}
}
