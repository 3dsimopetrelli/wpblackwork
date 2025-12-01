<?php
/**
 * Digital Assets Product Type
 *
 * Custom product type that extends Simple Product with all its features.
 * Has a different type slug for filtering and custom queries.
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
 * Extends WC_Product_Simple to inherit all Simple product functionality:
 * - Price, inventory, shipping, tax
 * - Attributes, linked products
 * - All standard product tabs and options
 *
 * Only difference: returns 'digital_assets' as product type for filtering.
 */
class BW_Product_Digital_Assets extends WC_Product_Simple {

	/**
	 * Get the product type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'digital_assets';
	}
}
