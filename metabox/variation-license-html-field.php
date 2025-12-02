<?php
/**
 * Variation License HTML Field
 *
 * Adds a custom HTML textarea field to product variations
 * for storing license terms/conditions HTML
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add custom field to variation settings
 */
add_action( 'woocommerce_variation_options_pricing', 'bw_add_variation_license_html_field', 10, 3 );

function bw_add_variation_license_html_field( $loop, $variation_data, $variation ) {
	$variation_id = $variation->ID;
	$license_html = get_post_meta( $variation_id, '_bw_variation_license_html', true );
	?>
	<div class="form-row form-row-full bw-variation-license-html-wrapper">
		<label>
			<?php esc_html_e( 'License Terms HTML', 'bw' ); ?>
			<?php echo wc_help_tip( __( 'Enter the HTML content for the license terms box. This will be displayed when the variation is selected in the BW Price Variation widget.', 'bw' ) ); ?>
		</label>
		<textarea
			id="bw_variation_license_html_<?php echo esc_attr( $loop ); ?>"
			name="bw_variation_license_html[<?php echo esc_attr( $loop ); ?>]"
			class="bw-variation-license-html-field"
			rows="8"
			placeholder="<?php esc_attr_e( 'Enter HTML for license terms (e.g., <strong>END PRODUCTS</strong>: Up to 5,000<br><strong>NUMBER PROJECTS</strong>: 1 single project)', 'bw' ); ?>"
			style="width: 100%; font-family: monospace; font-size: 13px;"
		><?php echo esc_textarea( $license_html ); ?></textarea>
		<p class="description">
			<?php esc_html_e( 'You can use HTML tags like <strong>, <br>, <p>, <a>, etc. This content will appear in the license box below the variation buttons.', 'bw' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Save custom field value
 */
add_action( 'woocommerce_save_product_variation', 'bw_save_variation_license_html_field', 10, 2 );

function bw_save_variation_license_html_field( $variation_id, $i ) {
	if ( isset( $_POST['bw_variation_license_html'][ $i ] ) ) {
		$license_html = wp_kses_post( $_POST['bw_variation_license_html'][ $i ] );
		update_post_meta( $variation_id, '_bw_variation_license_html', $license_html );
	} else {
		delete_post_meta( $variation_id, '_bw_variation_license_html' );
	}
}

/**
 * AJAX handler to get variation license HTML
 */
add_action( 'wp_ajax_bw_get_variation_license_html', 'bw_get_variation_license_html' );
add_action( 'wp_ajax_nopriv_bw_get_variation_license_html', 'bw_get_variation_license_html' );

function bw_get_variation_license_html() {
	// Verify nonce
	check_ajax_referer( 'bw_price_variation_nonce', 'nonce' );

	$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;

	if ( ! $variation_id ) {
		wp_send_json_error( [ 'message' => 'Invalid variation ID' ] );
	}

	// Get the license HTML from variation meta
	$license_html = get_post_meta( $variation_id, '_bw_variation_license_html', true );

	if ( empty( $license_html ) ) {
		wp_send_json_success( [ 'html' => '' ] );
	}

	// Return the HTML (already sanitized on save with wp_kses_post)
	wp_send_json_success( [ 'html' => $license_html ] );
}

/**
 * Add CSS for the field in admin
 */
add_action( 'admin_head', 'bw_variation_license_html_field_css' );

function bw_variation_license_html_field_css() {
	global $pagenow, $post_type;

	if ( ( $pagenow === 'post.php' || $pagenow === 'post-new.php' ) && $post_type === 'product' ) {
		?>
		<style>
			.bw-variation-license-html-wrapper {
				padding: 10px 12px;
				background: #f9f9f9;
				border: 1px solid #ddd;
				border-radius: 4px;
				margin-top: 10px;
			}

			.bw-variation-license-html-wrapper label {
				font-weight: 600;
				margin-bottom: 8px;
				display: block;
			}

			.bw-variation-license-html-field {
				border: 1px solid #8c8f94;
				border-radius: 4px;
				padding: 8px;
			}

			.bw-variation-license-html-field:focus {
				border-color: #2271b1;
				outline: 2px solid transparent;
				box-shadow: 0 0 0 1px #2271b1;
			}

			.bw-variation-license-html-wrapper .description {
				font-size: 12px;
				color: #646970;
				font-style: italic;
				margin-top: 8px;
			}
		</style>
		<?php
	}
}
