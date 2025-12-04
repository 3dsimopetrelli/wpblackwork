<?php
/**
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product.php.
 *
 * UTILIZZO:
 * - Copia questo file in yourtheme/woocommerce/content-product.php
 * - Le card prodotto verranno renderizzate in stile BW Wallpost
 * - Personalizza le opzioni nell'array $card_settings qui sotto
 *
 * @package BW_Main_Elementor_Widgets
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Ensure visibility.
if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

// Check if the card renderer class exists
if ( ! class_exists( 'BW_Product_Card_Renderer' ) ) {
	// Fallback to default WooCommerce template
	wc_get_template_part( 'content', 'product' );
	return;
}

// Configure card settings
$card_settings = apply_filters( 'bw_woocommerce_product_card_settings', [
	'image_size'          => 'large',
	'show_image'          => true,
	'show_hover_image'    => true,
	'show_title'          => true,
	'show_description'    => false, // Di solito non serve negli archivi
	'show_price'          => true,
	'show_buttons'        => true,
	'show_add_to_cart'    => true,
	'open_cart_popup'     => false, // Imposta a true se hai il cart popup attivo
] );

// Render the product card
echo BW_Product_Card_Renderer::render_card( $product, $card_settings );
