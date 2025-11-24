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
	} elseif ( $post ) {
		$product_type = get_post_meta( $post->ID, '_product_type', true );
		if ( empty( $product_type ) ) {
			$terms = get_the_terms( $post->ID, 'product_type' );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$product_type = current( $terms )->name;
			}
		}
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
 * Show variations tab for Digital Assets and Prints.
 *
 * @param bool   $show Whether to show variations tab.
 * @param string $product_type Current product type.
 * @return bool
 */
function bw_show_variations_tab( $show, $product_type ) {
	if ( in_array( $product_type, array( 'digitalassets', 'prints' ), true ) ) {
		return true;
	}
	return $show;
}
add_filter( 'woocommerce_product_type_supports', 'bw_product_type_supports', 10, 2 );

/**
 * Define what features each custom product type supports.
 *
 * @param bool   $supports Whether the product type supports the feature.
 * @param string $feature Feature name.
 * @param WC_Product $product Product object (optional).
 * @return bool
 */
function bw_product_type_supports( $supports, $feature ) {
	global $post, $product_object;

	// Get product type
	$product_type = '';
	if ( $product_object && is_object( $product_object ) ) {
		$product_type = $product_object->get_type();
	} elseif ( $post ) {
		$product_type = get_post_meta( $post->ID, '_product_type', true );
	}

	// Digital Assets and Prints support same features as Variable Product
	if ( in_array( $product_type, array( 'digitalassets', 'prints' ), true ) ) {
		// Support all variable product features
		if ( in_array( $feature, array( 'ajax_add_to_cart' ), true ) ) {
			return false; // Variable products don't support ajax add to cart
		}
		return true;
	}

	// Books support same features as Simple Product
	if ( 'books' === $product_type ) {
		// Support all simple product features
		return true;
	}

	return $supports;
}

/**
 * Add custom product types to the list of variable product types.
 * This ensures variations work correctly for Digital Assets and Prints.
 *
 * @param array $types Variable product types.
 * @return array
 */
function bw_add_variable_product_types( $types ) {
	$types[] = 'digitalassets';
	$types[] = 'prints';
	return $types;
}
add_filter( 'woocommerce_product_type_query', 'bw_variable_product_type_query', 10, 3 );

/**
 * Handle product type queries for custom types.
 *
 * @param bool   $is_type Whether product is of the queried type.
 * @param string $type Product type being queried.
 * @param WC_Product $product Product object.
 * @return bool
 */
function bw_variable_product_type_query( $is_type, $type, $product ) {
	// Ensure we have a valid product object
	if ( ! is_object( $product ) || ! method_exists( $product, 'get_type' ) ) {
		return $is_type;
	}

	$product_type = $product->get_type();

	// Digital Assets and Prints are treated as variable products
	if ( 'variable' === $type && in_array( $product_type, array( 'digitalassets', 'prints' ), true ) ) {
		return true;
	}

	// Books is treated as simple product
	if ( 'simple' === $type && 'books' === $product_type ) {
		return true;
	}

	return $is_type;
}

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
	} elseif ( $post ) {
		$product_type = get_post_meta( $post->ID, '_product_type', true );
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
		$product_type = get_post_meta( $post->ID, '_product_type', true );

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
