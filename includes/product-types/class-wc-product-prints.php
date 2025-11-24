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
	 * Product type.
	 *
	 * @var string
	 */
	protected $product_type = 'prints';

	/**
	 * Initialize the product type.
	 *
	 * @param mixed $product Product object or ID.
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
		return 'prints';
	}

	/**
	 * Get children product IDs.
	 * Override to ensure we always return an array, never null.
	 *
	 * @param bool $visible_only If true, only return visible children.
	 * @return array
	 */
	public function get_children( $visible_only = false ) {
		$children = parent::get_children( $visible_only );
		return is_array( $children ) ? $children : array();
	}

	/**
	 * Get available variations for this variable product.
	 * Override to ensure we always return an array, never null.
	 *
	 * @return array
	 */
	public function get_available_variations() {
		$variations = parent::get_available_variations();
		return is_array( $variations ) ? $variations : array();
	}

	/**
	 * Get visible children product IDs.
	 * Override to ensure we always return an array, never null.
	 *
	 * @return array
	 */
	public function get_visible_children() {
		$children = parent::get_visible_children();
		return is_array( $children ) ? $children : array();
	}
}
