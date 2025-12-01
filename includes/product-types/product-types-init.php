<?php
/**
 * Custom Product Types Initialization
 *
 * Registers and configures custom WooCommerce product types:
 * - Digital Assets (Simple Product with custom type)
 * - Books (Simple Product with custom type)
 * - Prints (Simple Product with custom type)
 *
 * All three types extend WC_Product_Simple and inherit core simple product features:
 * - General tab: Price, Sale price, Tax options
 * - Inventory tab: Stock management
 * - Shipping tab: Weight, dimensions
 * - Linked products: Upsells, cross-sells
 * - Attributes: Product attributes
 * - Variations: UI can be enabled through show_if classes if needed
 * - Advanced tab: Purchase note, menu order, reviews
 *
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

/**
 * Add support for product type options (virtual, downloadable).
 * This shows checkboxes in the General tab for our custom product types.
 *
 * @param array $options Existing product type options.
 * @return array Modified options.
 */
function bw_add_custom_product_type_options( $options ) {
	// Add virtual and downloadable support for our custom types
	if ( isset( $options['virtual'] ) ) {
		$options['virtual']['wrapper_class'] .= ' show_if_digital_assets show_if_books show_if_prints';
	}
	if ( isset( $options['downloadable'] ) ) {
		$options['downloadable']['wrapper_class'] .= ' show_if_digital_assets show_if_books show_if_prints';
	}

	return $options;
}
add_filter( 'product_type_options', 'bw_add_custom_product_type_options' );

/**
 * Show/hide product data tabs for custom product types.
 * Ensures all necessary tabs are visible.
 *
 * @param array $tabs Product data tabs.
 * @return array Modified tabs.
 */
function bw_custom_product_data_tabs( $tabs ) {
	// Make sure our custom types show all the same tabs as variable products
	$custom_types = array( 'digital_assets', 'books', 'prints' );

	foreach ( $custom_types as $type ) {
		// General tab - always visible
		if ( isset( $tabs['general'] ) ) {
			$tabs['general']['class'][] = 'show_if_' . $type;
		}

		// Inventory tab
		if ( isset( $tabs['inventory'] ) ) {
			$tabs['inventory']['class'][] = 'show_if_' . $type;
		}

		// Shipping tab
		if ( isset( $tabs['shipping'] ) ) {
			$tabs['shipping']['class'][] = 'show_if_' . $type;
		}

		// Linked products tab
		if ( isset( $tabs['linked_product'] ) ) {
			$tabs['linked_product']['class'][] = 'show_if_' . $type;
		}

		// Attributes tab
		if ( isset( $tabs['attribute'] ) ) {
			$tabs['attribute']['class'][] = 'show_if_' . $type;
		}

		// Variations tab
		if ( isset( $tabs['variations'] ) ) {
			$tabs['variations']['class'][] = 'show_if_' . $type;
		}

		// Advanced tab
		if ( isset( $tabs['advanced'] ) ) {
			$tabs['advanced']['class'][] = 'show_if_' . $type;
		}
	}

	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'bw_custom_product_data_tabs', 10, 1 );

/**
 * Show product data panels for custom product types.
 * This ensures the General price fields are visible.
 */
function bw_custom_product_data_panels() {
        global $post;

        // Only run on product edit screen
        if ( ! $post || 'product' !== $post->post_type ) {
                return;
        }

        $custom_types = array( 'digital_assets', 'books', 'prints' );
        ?>
        <script type="text/javascript">
                jQuery(function($) {
                        const customTypes = <?php echo wp_json_encode( $custom_types ); ?>;

                        function extendShowIfClasses() {
                                customTypes.forEach(function(type) {
                                        // Mirror Simple/Variable visibility rules for custom types
                                        $('.show_if_simple, .show_if_variable').addClass('show_if_' + type);
                                        $('.hide_if_simple, .hide_if_variable').addClass('hide_if_' + type);

                                        $('#general_product_data, #inventory_product_data, #shipping_product_data, #linked_product_data, #product_attributes, #advanced_product_data, #product_variation_data, #variable_product_options').addClass('show_if_' + type);
                                        $('.options_group.pricing').addClass('show_if_' + type);
                                        $('.options_group.show_if_downloadable').addClass('show_if_' + type);

                                        $('.product_data_tabs li.general_options, .product_data_tabs li.inventory_options, .product_data_tabs li.shipping_options, .product_data_tabs li.linked_product_options, .product_data_tabs li.attribute_options, .product_data_tabs li.variations_options, .product_data_tabs li.advanced_options').addClass('show_if_' + type);
                                });
                        }

                        extendShowIfClasses();

                        // Trigger WooCommerce UI refresh to respect the new classes
                        $('select#product-type').trigger('change');
                });
        </script>
        <?php
}
add_action( 'woocommerce_product_data_panels', 'bw_custom_product_data_panels' );

/**
 * Ensure the product type is saved correctly and not overwritten.
 * This prevents the type from reverting to 'simple' after save.
 *
 * @param int $post_id Product ID.
 */
function bw_save_custom_product_type( $post_id ) {
	// Verify this is a product
	if ( 'product' !== get_post_type( $post_id ) ) {
		return;
	}

	// Check if we have a product type in the POST data
	if ( ! isset( $_POST['product-type'] ) ) {
		return;
	}

        $product_type = sanitize_text_field( wp_unslash( $_POST['product-type'] ) );
        $custom_types = array( 'digital_assets', 'books', 'prints' );

        // Only process our custom types
        if ( ! in_array( $product_type, $custom_types, true ) ) {
                return;
        }

        // Set the product type taxonomy term
        wp_set_object_terms( $post_id, $product_type, 'product_type', false );

        // Also store meta to keep WC_Product_Factory in sync for custom slugs
        update_post_meta( $post_id, '_product_type', $product_type );

        // Clear product cache
        wc_delete_product_transients( $post_id );
}
add_action( 'woocommerce_process_product_meta', 'bw_save_custom_product_type', 10, 1 );

/**
 * Ensure downloadable assets are saved for custom product types.
 *
 * WooCommerce core hooks downloadable file saving to product-type specific
 * actions (e.g. `woocommerce_process_product_meta_simple`). Our custom
 * product types need the same handler so that `_downloadable_files`,
 * `_download_limit`, `_download_expiry`, and related meta persist.
 */
function bw_register_downloadable_save_handlers() {
        if ( ! class_exists( 'WC_Meta_Box_Product_Data' ) && defined( 'WC_ABSPATH' ) ) {
                include_once WC_ABSPATH . 'includes/admin/meta-boxes/class-wc-meta-box-product-data.php';
        }

        if ( ! class_exists( 'WC_Meta_Box_Product_Data' ) ) {
                return;
        }

	$custom_types = array( 'digital_assets', 'books', 'prints' );
	
	// Wrapper to avoid fatal errors when WooCommerce fires the action with a
	// single argument (post ID). WooCommerce core expects two parameters
	// ($post_id, $post) for WC_Meta_Box_Product_Data::save(), but the
	// custom product type hooks only pass the post ID. By providing a default
	// null value for the second parameter we maintain compatibility with both
	// call signatures.
	$handler = static function( $post_id, $post = null ) {
		WC_Meta_Box_Product_Data::save( $post_id, $post );
		};
	
	foreach ( $custom_types as $type ) {
		add_action( 'woocommerce_process_product_meta_' . $type, $handler, 10, 2 );
	}
}
add_action( 'init', 'bw_register_downloadable_save_handlers' );

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
 * Shows the human-readable name for our custom types.
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

	// Map our custom types to readable names
	$type_labels = array(
		'digital_assets' => __( 'Digital Assets', 'bw' ),
		'books'          => __( 'Books', 'bw' ),
		'prints'         => __( 'Prints', 'bw' ),
		'simple'         => __( 'Simple product', 'woocommerce' ),
		'variable'       => __( 'Variable product', 'woocommerce' ),
		'grouped'        => __( 'Grouped product', 'woocommerce' ),
		'external'       => __( 'External/Affiliate product', 'woocommerce' ),
	);

	if ( isset( $type_labels[ $product_type ] ) ) {
		echo esc_html( $type_labels[ $product_type ] );
	} else {
		echo esc_html( ucfirst( $product_type ) );
	}
}
add_action( 'manage_product_posts_custom_column', 'bw_display_product_type_column', 10, 2 );
