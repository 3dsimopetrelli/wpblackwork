<?php
/**
 * Custom Product Types Initialization
 *
 * Registers and configures custom WooCommerce product types:
 * - Digital Assets (Simple Product with custom type)
 * - Books (Simple Product with custom type)
 * - Prints (Simple Product with custom type)
 *
 * All three types extend WC_Product_Simple and inherit all Simple product features.
 * They only differ in their type slug for filtering and custom queries.
 *
 * @package BWElementorWidgets
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load custom product type classes after WooCommerce is loaded.
 * This ensures WC_Product_Simple class is available.
 */
function bw_load_custom_product_type_classes() {
	// Check if WooCommerce is active and loaded
	if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WC_Product_Simple' ) ) {
		return;
	}

	// Include product type classes
	require_once __DIR__ . '/class-bw-product-type-digital-assets.php';
	require_once __DIR__ . '/class-bw-product-type-books.php';
	require_once __DIR__ . '/class-bw-product-type-prints.php';
}
add_action( 'woocommerce_loaded', 'bw_load_custom_product_type_classes', 10 );

/**
 * Add custom product types to the product type dropdown in admin.
 *
 * @param array $types Existing product types.
 * @return array Modified product types.
 */
function bw_add_custom_product_types_selector( $types ) {
	$types['digital_assets'] = __( 'Digital Assets', 'bw' );
	$types['books']          = __( 'Books', 'bw' );
	$types['prints']         = __( 'Prints', 'bw' );

	return $types;
}
add_filter( 'product_type_selector', 'bw_add_custom_product_types_selector' );

/**
 * Map custom product type slugs to their PHP classes.
 *
 * @param string $classname   Product class name.
 * @param string $product_type Product type slug.
 * @param string $post_type    Post type.
 * @param int    $product_id   Product ID.
 * @return string Modified class name.
 */
function bw_register_custom_product_types( $classname, $product_type, $post_type, $product_id ) {
	if ( 'digital_assets' === $product_type ) {
		$classname = 'BW_Product_Digital_Assets';
	} elseif ( 'books' === $product_type ) {
		$classname = 'BW_Product_Books';
	} elseif ( 'prints' === $product_type ) {
		$classname = 'BW_Product_Prints';
	}

	return $classname;
}
add_filter( 'woocommerce_product_class', 'bw_register_custom_product_types', 10, 4 );

/**
 * Register custom product types as taxonomy terms.
 * This ensures the types are available for filtering and queries.
 */
function bw_register_custom_product_type_terms() {
	// Only run if WooCommerce is active
	if ( ! taxonomy_exists( 'product_type' ) ) {
		return;
	}

	$custom_types = array( 'digital_assets', 'books', 'prints' );

	foreach ( $custom_types as $type ) {
		if ( ! term_exists( $type, 'product_type' ) ) {
			wp_insert_term( $type, 'product_type' );
		}
	}
}
add_action( 'init', 'bw_register_custom_product_type_terms', 20 );
