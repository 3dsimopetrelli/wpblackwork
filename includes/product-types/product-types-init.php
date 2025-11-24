<?php
/**
 * Custom Product Types Initialization
 *
 * Registers and configures custom WooCommerce product types:
 * - Digital Assets (like Variable Product)
 * - Books (like Simple Product)
 * - Prints (like Variable Product)
 *
 * @package BWElementorWidgets
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper function to get product type reliably.
 * Checks taxonomy first (WooCommerce native), then post meta as fallback.
 *
 * @param int $product_id Product ID.
 * @return string Product type or empty string.
 */
function bw_get_product_type( $product_id ) {
	if ( ! $product_id ) {
		return '';
	}

	// First, try to get from taxonomy (WooCommerce native method)
	$terms = get_the_terms( $product_id, 'product_type' );
	if ( $terms && ! is_wp_error( $terms ) ) {
		$term = current( $terms );
		if ( $term && isset( $term->name ) ) {
			return $term->name;
		}
	}

	// Fallback to post meta for backward compatibility
	$product_type = get_post_meta( $product_id, '_product_type', true );
	if ( $product_type ) {
		return $product_type;
	}

	return '';
}

/**
 * Load custom product type classes after WooCommerce is loaded.
 * This ensures WC_Product_Variable and WC_Product_Simple classes are available.
 */
function bw_load_custom_product_type_classes() {
	// Check if WooCommerce is active and loaded
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	// Include product type classes only after WooCommerce is ready
	require_once __DIR__ . '/class-wc-product-digitalassets.php';
	require_once __DIR__ . '/class-wc-product-books.php';
	require_once __DIR__ . '/class-wc-product-prints.php';
}
add_action( 'woocommerce_loaded', 'bw_load_custom_product_type_classes', 10 );

/**
 * Register custom product types with WooCommerce.
 *
 * @param string $classname Product class name.
 * @param string $product_type Product type.
 * @param string $post_type Post type.
 * @param int    $product_id Product ID.
 * @return string
 */
function bw_register_custom_product_types( $classname, $product_type, $post_type, $product_id ) {
	if ( 'digitalassets' === $product_type ) {
		$classname = 'WC_Product_DigitalAssets';
	} elseif ( 'books' === $product_type ) {
		$classname = 'WC_Product_Books';
	} elseif ( 'prints' === $product_type ) {
		$classname = 'WC_Product_Prints';
	}
	return $classname;
}
add_filter( 'woocommerce_product_class', 'bw_register_custom_product_types', 10, 4 );

/**
 * Save product type as post meta when product is saved.
 * This ensures the product type is persisted correctly and remains after updates.
 *
 * @param int     $post_id Product ID.
 * @param WP_Post $post Post object.
 */
function bw_save_product_type_meta( $post_id, $post ) {
	// Check if this is a product
	if ( 'product' !== $post->post_type ) {
		return;
	}

	// Check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check permissions
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Get product type from taxonomy (WooCommerce native)
	$product_type = bw_get_product_type( $post_id );

	// If we have a product type, save it as post meta for compatibility
	if ( $product_type ) {
		update_post_meta( $post_id, '_product_type', $product_type );
	}
}
add_action( 'save_post_product', 'bw_save_product_type_meta', 20, 2 );

/**
 * Sync product type when set via taxonomy.
 * This catches changes made through WooCommerce admin interface.
 *
 * @param int    $object_id Object ID.
 * @param array  $terms Array of term taxonomy IDs.
 * @param array  $tt_ids Array of term IDs.
 * @param string $taxonomy Taxonomy slug.
 */
function bw_sync_product_type_on_term_set( $object_id, $terms, $tt_ids, $taxonomy ) {
	// Only for product_type taxonomy
	if ( 'product_type' !== $taxonomy ) {
		return;
	}

	// Get the product type
	$product_type = bw_get_product_type( $object_id );

	// Save as post meta
	if ( $product_type ) {
		update_post_meta( $object_id, '_product_type', $product_type );
	}
}
add_action( 'set_object_terms', 'bw_sync_product_type_on_term_set', 10, 4 );

/**
 * Force save custom product type after WooCommerce saves the product.
 * This hook runs after WooCommerce's save_post handler to ensure custom types are preserved.
 *
 * @param int        $product_id Product ID.
 * @param WC_Product $product Product object.
 * @param bool       $update Whether this is an update or new product (optional, defaults to true).
 */
function bw_force_save_custom_product_type( $product_id, $product, $update = true ) {
	// Get the current product type from taxonomy
	$product_type = bw_get_product_type( $product_id );

	// If it's one of our custom types, ensure it stays that way
	if ( in_array( $product_type, array( 'digitalassets', 'books', 'prints' ), true ) ) {
		// Get current terms
		$terms = wp_get_object_terms( $product_id, 'product_type', array( 'fields' => 'slugs' ) );

		// If the term was removed or changed, restore it
		if ( empty( $terms ) || ! in_array( $product_type, $terms, true ) ) {
			wp_set_object_terms( $product_id, $product_type, 'product_type', false );
		}

		// Also ensure the meta is set
		update_post_meta( $product_id, '_product_type', $product_type );

		// Set appropriate flags based on product type
		if ( 'digitalassets' === $product_type ) {
			update_post_meta( $product_id, '_virtual', 'yes' );
			update_post_meta( $product_id, '_downloadable', 'yes' );
		} elseif ( 'books' === $product_type ) {
			update_post_meta( $product_id, '_virtual', 'no' );
			update_post_meta( $product_id, '_downloadable', 'no' );
		} elseif ( 'prints' === $product_type ) {
			update_post_meta( $product_id, '_virtual', 'no' );
			update_post_meta( $product_id, '_downloadable', 'no' );
		}
	}
}
add_action( 'woocommerce_update_product', 'bw_force_save_custom_product_type', 999, 3 );

/**
 * Add custom product types to the product type dropdown.
 *
 * @param array $types Existing product types.
 * @return array
 */
function bw_add_custom_product_types_selector( $types ) {
	$types['digitalassets'] = __( 'Digital Assets', 'bw' );
	$types['books']         = __( 'Books', 'bw' );
	$types['prints']        = __( 'Prints', 'bw' );
	return $types;
}
add_filter( 'product_type_selector', 'bw_add_custom_product_types_selector' );

/**
 * Show/hide product data tabs for custom product types.
 *
 * Digital Assets and Prints show same tabs as Variable Product.
 * Books shows same tabs as Simple Product.
 *
 * @param array $tabs Product data tabs.
 * @return array
 */
function bw_custom_product_tabs( $tabs ) {
	global $post, $product_object;

	// Get product type
	$product_type = '';
	if ( $product_object && is_object( $product_object ) ) {
		$product_type = $product_object->get_type();
	} elseif ( $post && $post->ID ) {
		$product_type = bw_get_product_type( $post->ID );
	}

	// For Digital Assets and Prints (Variable Product behavior)
	if ( in_array( $product_type, array( 'digitalassets', 'prints' ), true ) ) {
		// Show all tabs like a variable product
		// The 'variations' tab is handled separately by WooCommerce

		// Hide shipping tab if virtual (optional, can be customized)
		// Uncomment the following line if you want to hide shipping for digital products
		// unset( $tabs['shipping'] );
	}

	// For Books (Simple Product behavior)
	// Books already extend Simple Product, so tabs are correct by default
	// No modifications needed

	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'bw_custom_product_tabs', 10, 1 );

/**
 * Handle product type queries for custom types.
 *
 * This filter allows overriding the product type returned by WooCommerce.
 * The woocommerce_product_type_query filter passes 2 parameters:
 * - $override: false by default, or a string to override the product type
 * - $product_id: the ID of the product being queried
 *
 * If we return false, WooCommerce will use its normal logic.
 * If we return a string, WooCommerce will use that as the product type.
 *
 * @param bool|string $override False by default, or product type string.
 * @param int         $product_id Product ID.
 * @return bool|string
 */
function bw_variable_product_type_query( $override, $product_id ) {
	// If already overridden by another filter, respect that
	if ( false !== $override ) {
		return $override;
	}

	// Get the product type using our helper function
	$product_type = bw_get_product_type( $product_id );

	// If it's one of our custom types, return it
	if ( in_array( $product_type, array( 'digitalassets', 'prints', 'books' ), true ) ) {
		return $product_type;
	}

	// Otherwise, let WooCommerce handle it normally
	return $override;
}
add_filter( 'woocommerce_product_type_query', 'bw_variable_product_type_query', 10, 2 );

/**
 * Show/hide product options for custom product types.
 *
 * @param array $options Product type options.
 * @return array
 */
function bw_custom_product_type_options( $options ) {
	global $post, $product_object;

	// Get product type
	$product_type = '';
	if ( $product_object && is_object( $product_object ) ) {
		$product_type = $product_object->get_type();
	} elseif ( $post && $post->ID ) {
		$product_type = bw_get_product_type( $post->ID );
	}

	// Show options based on product type
	if ( in_array( $product_type, array( 'digitalassets', 'prints', 'books' ), true ) ) {
		// All custom types support virtual and downloadable options
		// These are already available by default
	}

	return $options;
}
add_filter( 'product_type_options', 'bw_custom_product_type_options' );

/**
 * Add JavaScript to handle product type switching in admin.
 * This ensures the correct tabs are shown when switching between product types.
 */
function bw_custom_product_type_admin_js() {
	global $post, $pagenow;

	if ( ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) && 'product' === get_post_type( $post ) ) {
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Add custom product types to WooCommerce's product type handling
			var customVariableTypes = ['digitalassets', 'prints'];
			var customSimpleTypes = ['books'];

			// Handle product type changes
			function handleProductTypeChange() {
				var product_type = $('#product-type').val();

				// Digital Assets and Prints behave like variable products
				if (customVariableTypes.indexOf(product_type) > -1) {
					// Show variable product elements
					$('.show_if_variable').show();
					$('.hide_if_variable').hide();

					// Show custom product type specific elements
					$('.show_if_' + product_type).show();
					$('.hide_if_' + product_type).hide();

					// Hide simple product pricing
					$('.options_group.pricing').hide();
					$('.general_options > .options_group:first').hide();

					// Show attributes tab (needed for variations)
					$('.product_attributes_options').show();
					$('li.attribute_tab').show();

					// Enable variations tab
					$('#variable_product_options').show();
					$('#variable_product_options_inner').show();
					$('li.variations_options').show();
					$('.variations_options').addClass('variations_tab');
				}

				// Books behave like simple products
				if (customSimpleTypes.indexOf(product_type) > -1) {
					// Show simple product elements
					$('.show_if_simple').show();
					$('.hide_if_simple').hide();

					// Show custom product type specific elements
					$('.show_if_' + product_type).show();
					$('.hide_if_' + product_type).hide();

					// Show simple product pricing
					$('.options_group.pricing').show();

					// Hide variable product elements
					$('.show_if_variable').hide();
					$('#variable_product_options').hide();
					$('#variable_product_options_inner').hide();
					$('li.variations_options').hide();
				}
			}

			// Attach to product type selector
			$('#product-type').on('change', handleProductTypeChange);

			// Trigger on page load to set initial state
			setTimeout(function() {
				handleProductTypeChange();
			}, 100);
		});
		</script>
		<?php
	}
}
add_action( 'admin_footer', 'bw_custom_product_type_admin_js' );

/**
 * Add custom classes to product type options to show/hide them correctly.
 *
 * @param string $classes Current classes.
 * @param string $type Product type.
 * @return string
 */
function bw_product_type_options_classes( $classes, $type ) {
	// Digital Assets and Prints act like variable products
	if ( in_array( $type, array( 'digitalassets', 'prints' ), true ) ) {
		$classes .= ' show_if_variable hide_if_simple';
	}

	// Books act like simple products
	if ( 'books' === $type ) {
		$classes .= ' show_if_simple hide_if_variable';
	}

	return $classes;
}
// This filter doesn't exist in WooCommerce by default, so we handle it with JavaScript above

/**
 * Ensure variations are loaded for custom variable product types.
 *
 * @param bool   $should_load Whether to load variations.
 * @param WC_Product $product Product object.
 * @return bool
 */
function bw_load_variations_for_custom_types( $should_load, $product ) {
	if ( is_object( $product ) && in_array( $product->get_type(), array( 'digitalassets', 'prints' ), true ) ) {
		return true;
	}
	return $should_load;
}
add_filter( 'woocommerce_load_variation_product_type', 'bw_load_variations_for_custom_types', 10, 2 );

/**
 * Add admin body classes for custom product types.
 * This helps with CSS targeting for show_if/hide_if classes.
 *
 * @param string $classes Current body classes.
 * @return string
 */
function bw_admin_body_class_for_product_types( $classes ) {
	global $post, $pagenow;

	if ( ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) && isset( $post ) && 'product' === get_post_type( $post ) ) {
		$product_type = bw_get_product_type( $post->ID );

		if ( in_array( $product_type, array( 'digitalassets', 'prints' ), true ) ) {
			$classes .= ' product-type-' . $product_type . ' product-type-variable';
		} elseif ( 'books' === $product_type ) {
			$classes .= ' product-type-books product-type-simple';
		}
	}

	return $classes;
}
add_filter( 'admin_body_class', 'bw_admin_body_class_for_product_types' );

/**
 * Make sure WooCommerce recognizes our custom types as variable when needed.
 *
 * @param array $types Array of product types.
 * @return array
 */
function bw_woocommerce_product_type_supports_variations( $types ) {
	$types[] = 'digitalassets';
	$types[] = 'prints';
	return $types;
}
add_filter( 'woocommerce_products_support_ajax_add_to_cart', 'bw_woocommerce_product_type_supports_variations' );

/**
 * Enable variation support for custom product types.
 *
 * @param bool   $is_variable Whether product is variable.
 * @param string $product_id Product ID.
 * @return bool
 */
function bw_product_is_variable( $is_variable, $product_id ) {
	$product = wc_get_product( $product_id );
	if ( $product && in_array( $product->get_type(), array( 'digitalassets', 'prints' ), true ) ) {
		return true;
	}
	return $is_variable;
}

/**
 * Add CSS to admin for better product type styling.
 */
function bw_custom_product_type_admin_css() {
	global $post, $pagenow;

	if ( ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) && 'product' === get_post_type( $post ) ) {
		?>
		<style type="text/css">
			/* Ensure custom product types show correct elements */
			.product-type-digitalassets .show_if_variable,
			.product-type-prints .show_if_variable {
				display: block !important;
			}

			.product-type-digitalassets .hide_if_variable,
			.product-type-prints .hide_if_variable {
				display: none !important;
			}

			.product-type-books .show_if_simple {
				display: block !important;
			}

			.product-type-books .hide_if_simple {
				display: none !important;
			}
		</style>
		<?php
	}
}
add_action( 'admin_head', 'bw_custom_product_type_admin_css' );

/**
 * Add custom product types to WooCommerce product type filter in admin.
 * This ensures the filter dropdown in Products list includes our custom types.
 *
 * @param array $output Array of product type options.
 * @return array
 */
function bw_add_custom_types_to_product_filter( $output ) {
	// The filter already includes all registered product types via product_type_selector filter
	// This function is kept for potential future customization
	return $output;
}
add_filter( 'woocommerce_product_filters', 'bw_add_custom_types_to_product_filter' );

/**
 * Ensure custom product types are searchable and filterable.
 * This makes sure WooCommerce recognizes our custom product types in queries.
 *
 * @param WP_Query $query The WordPress query object.
 */
function bw_make_custom_product_types_searchable( $query ) {
	// Only modify product queries in admin
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	// Only for product post type
	$post_type = $query->get( 'post_type' );
	if ( 'product' !== $post_type ) {
		return;
	}

	// If filtering by product_type taxonomy, ensure our types are included
	$tax_query = $query->get( 'tax_query' );
	if ( ! empty( $tax_query ) ) {
		foreach ( $tax_query as $key => $tax ) {
			if ( isset( $tax['taxonomy'] ) && 'product_type' === $tax['taxonomy'] ) {
				// WooCommerce is filtering by product type - our types are already registered
				// No modification needed as they're in the taxonomy
				break;
			}
		}
	}
}
add_action( 'pre_get_posts', 'bw_make_custom_product_types_searchable', 20 );

/**
 * Register custom product types as WooCommerce product types for filtering.
 * This ensures the custom types appear in WooCommerce's product type filter dropdown.
 */
function bw_register_custom_product_types_for_filtering() {
	// Register terms if they don't exist
	$custom_types = array( 'digitalassets', 'books', 'prints' );

	foreach ( $custom_types as $type ) {
		if ( ! term_exists( $type, 'product_type' ) ) {
			wp_insert_term( $type, 'product_type' );
		}
	}
}
add_action( 'init', 'bw_register_custom_product_types_for_filtering', 20 );

/**
 * Add Product Type column to WooCommerce products list.
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
function bw_add_product_type_column( $columns ) {
	// Insert the Product Type column after the Name column
	$new_columns = array();
	foreach ( $columns as $key => $value ) {
		$new_columns[ $key ] = $value;
		if ( 'name' === $key ) {
			$new_columns['product_type'] = __( 'Product Type', 'bw' );
		}
	}
	return $new_columns;
}
add_filter( 'manage_edit-product_columns', 'bw_add_product_type_column', 15 );

/**
 * Populate the Product Type column with product data.
 *
 * @param string $column Column name.
 * @param int    $post_id Product ID.
 */
function bw_populate_product_type_column( $column, $post_id ) {
	if ( 'product_type' === $column ) {
		$product_type = bw_get_product_type( $post_id );

		// Map product type slugs to display names
		$type_labels = array(
			'simple'        => __( 'Simple', 'bw' ),
			'variable'      => __( 'Variable', 'bw' ),
			'grouped'       => __( 'Grouped', 'bw' ),
			'external'      => __( 'External', 'bw' ),
			'digitalassets' => __( 'Digital Assets', 'bw' ),
			'books'         => __( 'Books', 'bw' ),
			'prints'        => __( 'Prints', 'bw' ),
		);

		// Get the display name, or use the slug if not mapped
		$display_name = isset( $type_labels[ $product_type ] ) ? $type_labels[ $product_type ] : ucfirst( $product_type );

		// Add a badge style for custom types
		$badge_class = '';
		if ( in_array( $product_type, array( 'digitalassets', 'books', 'prints' ), true ) ) {
			$badge_class = 'bw-custom-type';
		}

		echo '<span class="product-type-badge ' . esc_attr( $badge_class ) . '">' . esc_html( $display_name ) . '</span>';
	}
}
add_action( 'manage_product_posts_custom_column', 'bw_populate_product_type_column', 10, 2 );

/**
 * Make the Product Type column sortable.
 *
 * @param array $columns Sortable columns.
 * @return array Modified sortable columns.
 */
function bw_make_product_type_column_sortable( $columns ) {
	$columns['product_type'] = 'product_type';
	return $columns;
}
add_filter( 'manage_edit-product_sortable_columns', 'bw_make_product_type_column_sortable' );

/**
 * Handle sorting by product type.
 *
 * @param WP_Query $query The WordPress query object.
 */
function bw_product_type_column_orderby( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$orderby = $query->get( 'orderby' );

	if ( 'product_type' === $orderby ) {
		$query->set( 'orderby', 'meta_value' );
		$query->set( 'meta_key', '_product_type' );
	}
}
add_action( 'pre_get_posts', 'bw_product_type_column_orderby' );

/**
 * Add CSS styling for the Product Type column.
 */
function bw_product_type_column_styles() {
	$screen = get_current_screen();
	if ( $screen && 'edit-product' === $screen->id ) {
		?>
		<style>
			.column-product_type {
				width: 150px;
			}
			.product-type-badge {
				display: inline-block;
				padding: 3px 8px;
				border-radius: 3px;
				background-color: #f0f0f0;
				font-size: 12px;
				font-weight: 500;
			}
			.product-type-badge.bw-custom-type {
				background-color: #d4edda;
				color: #155724;
				border: 1px solid #c3e6cb;
			}
		</style>
		<?php
	}
}
add_action( 'admin_head', 'bw_product_type_column_styles' );
