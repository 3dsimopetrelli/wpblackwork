<?php
/**
 * Prints Product Type
 *
 * Custom product type that extends Simple Product so it mirrors WooCommerce's
 * default behaviour while keeping its own product type slug.
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
 * Extends WC_Product_Simple to inherit native WooCommerce behaviour for pricing
 * and inventory while only changing the product type identifier used for
 * filtering.
 */
class BW_Product_Prints extends WC_Product_Simple {

	/**
	 * Get the product type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'prints';
	}
}
