<?php
/**
 * Digital Assets Product Type
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
 * Digital Assets Product Class - extends WC_Product_Variable
 *
 * This product type has the same functionality as a Variable Product,
 * including attributes, variations, and all standard Variable Product tabs.
 */
class WC_Product_DigitalAssets extends WC_Product_Variable {

	/**
	 * Product type.
	 *
	 * @var string
	 */
	protected $product_type = 'digitalassets';

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
		return 'digitalassets';
	}

	/**
	 * Returns whether or not the product is virtual.
	 * Digital assets can be virtual by default.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function is_virtual( $context = 'view' ) {
		return parent::is_virtual( $context );
	}

	/**
	 * Returns whether or not the product is downloadable.
	 * Digital assets can be downloadable by default.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function is_downloadable( $context = 'view' ) {
		return parent::is_downloadable( $context );
	}
}
