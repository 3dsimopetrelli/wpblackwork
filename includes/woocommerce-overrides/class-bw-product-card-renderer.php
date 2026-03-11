<?php
/**
 * BW Product Card Renderer (Compatibility Bridge)
 *
 * Backward-compatible wrapper delegating to BW_Product_Card_Component.
 *
 * @package BW_Main_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BW_Product_Card_Component' ) ) {
	require_once dirname( __DIR__ ) . '/components/product-card/class-bw-product-card-component.php';
}

/**
 * Class BW_Product_Card_Renderer
 *
 * Legacy public API kept for compatibility with existing callers.
 */
class BW_Product_Card_Renderer {

	/**
	 * Render a product card.
	 *
	 * @param int|WC_Product $product Product ID or object.
	 * @param array          $settings Card settings.
	 * @return string
	 */
	public static function render_card( $product, $settings = [] ) {
		if ( ! class_exists( 'BW_Product_Card_Component' ) ) {
			return '';
		}

		return BW_Product_Card_Component::render( $product, $settings );
	}

	/**
	 * Render multiple product cards.
	 *
	 * @param array $products Product IDs or objects.
	 * @param array $settings Card settings.
	 * @return string
	 */
	public static function render_cards( $products, $settings = [] ) {
		if ( ! class_exists( 'BW_Product_Card_Component' ) ) {
			return '';
		}

		return BW_Product_Card_Component::render_many( $products, $settings );
	}
}
