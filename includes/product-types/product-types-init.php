<?php
/**
 * Product Type Column Display
 *
 * Manages the display of the Product Type column in the WooCommerce products list.
 *
 * @package BWElementorWidgets
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Restore the Product Type column in the products list if it's been removed.
 * This ensures the column is visible in the admin product list.
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
function bw_restore_product_type_column( $columns ) {
	// Check if the product_type column exists
	if ( ! isset( $columns['product_type'] ) ) {
		// Add it after the name column
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( 'name' === $key ) {
				$new_columns['product_type'] = __( 'Type', 'woocommerce' );
			}
		}
		return $new_columns;
	}

	return $columns;
}
add_filter( 'manage_edit-product_columns', 'bw_restore_product_type_column', 20 );

/**
 * Display the product type in the product list column.
 * Shows the human-readable name for standard WooCommerce product types.
 *
 * @param string $column_name Column name.
 * @param int    $post_id     Product ID.
 */
function bw_display_product_type_column( $column_name, $post_id ) {
	if ( 'product_type' !== $column_name ) {
		return;
	}

	$product = wc_get_product( $post_id );
	if ( ! $product ) {
		return;
	}

	$product_type = $product->get_type();

	// Map standard WooCommerce types to readable names
	$type_labels = array(
		'simple'   => __( 'Simple product', 'woocommerce' ),
		'variable' => __( 'Variable product', 'woocommerce' ),
		'grouped'  => __( 'Grouped product', 'woocommerce' ),
		'external' => __( 'External/Affiliate product', 'woocommerce' ),
	);

	if ( isset( $type_labels[ $product_type ] ) ) {
		echo esc_html( $type_labels[ $product_type ] );
	} else {
		echo esc_html( ucfirst( $product_type ) );
	}
}
add_action( 'manage_product_posts_custom_column', 'bw_display_product_type_column', 10, 2 );
