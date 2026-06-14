<?php
/**
 * Variation License Terms selector field.
 *
 * Replaces the legacy inline variation license table with a reusable License
 * dropdown while preserving legacy variation meta for compatibility fallback.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'woocommerce_variation_options_pricing', 'bw_add_variation_license_selector_field', 10, 3 );

if ( ! function_exists( 'bw_add_variation_license_selector_field' ) ) {
	function bw_add_variation_license_selector_field( $loop, $variation_data, $variation ) {
		$variation_id        = $variation->ID;
		$selected_license_id = function_exists( 'bw_get_variation_selected_license_id' )
			? bw_get_variation_selected_license_id( $variation_id )
			: absint( get_post_meta( $variation_id, '_bw_variation_license_id', true ) );
		$license_options     = function_exists( 'bw_get_license_options' ) ? bw_get_license_options() : [];
		?>
		<div class="form-row form-row-full bw-variation-license-selector-wrapper">
			<label for="bw_variation_license_id_<?php echo esc_attr( $loop ); ?>">
				<?php esc_html_e( 'License Terms', 'bw' ); ?>
			</label>
			<select
				id="bw_variation_license_id_<?php echo esc_attr( $loop ); ?>"
				name="bw_variation_license_id[<?php echo esc_attr( $loop ); ?>]"
				class="bw-variation-license-selector"
			>
				<option value=""><?php esc_html_e( 'Select a license', 'bw' ); ?></option>
				<?php foreach ( $license_options as $license_id => $license_title ) : ?>
					<option value="<?php echo esc_attr( $license_id ); ?>" <?php selected( $selected_license_id, absint( $license_id ) ); ?>>
						<?php echo esc_html( $license_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="description">
				<?php esc_html_e( 'Select the reusable License Terms record used by this variation.', 'bw' ); ?>
			</p>
			<details class="bw-variation-license-selector-helper">
				<summary><?php esc_html_e( 'Show metakeys', 'bw' ); ?></summary>
				<p><strong><?php esc_html_e( 'Selected variation license:', 'bw' ); ?></strong> <code>_bw_variation_license_id</code></p>
				<p><strong><?php esc_html_e( 'Reusable License rows:', 'bw' ); ?></strong> <code>_bw_license_rows</code></p>
				<p><strong><?php esc_html_e( 'Legacy variation rows:', 'bw' ); ?></strong> <code>_bw_variation_license_col1</code>, <code>_bw_variation_license_col2</code></p>
				<p><strong><?php esc_html_e( 'Transport/import keys:', 'bw' ); ?></strong> <code>_bw_variation_license_col1_json</code>, <code>_bw_variation_license_col2_json</code></p>
				<p><strong><?php esc_html_e( 'Current selected license ID:', 'bw' ); ?></strong> <?php echo $selected_license_id ? esc_html( (string) $selected_license_id ) : esc_html__( 'None', 'bw' ); ?></p>
			</details>
		</div>
		<?php
	}
}

add_action( 'woocommerce_save_product_variation', 'bw_save_variation_license_selector_field', 10, 2 );

if ( ! function_exists( 'bw_save_variation_license_selector_field' ) ) {
	function bw_save_variation_license_selector_field( $variation_id, $i ) {
		$selected_license_id = isset( $_POST['bw_variation_license_id'][ $i ] )
			? absint( wp_unslash( $_POST['bw_variation_license_id'][ $i ] ) )
			: 0;

		if ( $selected_license_id > 0 ) {
			update_post_meta( $variation_id, '_bw_variation_license_id', $selected_license_id );
			return;
		}

		delete_post_meta( $variation_id, '_bw_variation_license_id' );
	}
}

add_action( 'admin_head', 'bw_variation_license_selector_field_css' );

if ( ! function_exists( 'bw_variation_license_selector_field_css' ) ) {
	function bw_variation_license_selector_field_css() {
		global $pagenow, $post_type;

		if ( ( 'post.php' !== $pagenow && 'post-new.php' !== $pagenow ) || 'product' !== $post_type ) {
			return;
		}
		?>
		<style>
			.bw-variation-license-selector-wrapper {
				padding: 10px 12px;
				background: #f9f9f9;
				border: 1px solid #ddd;
				border-radius: 4px;
				margin-top: 10px;
			}
			.bw-variation-license-selector-wrapper label {
				display: block;
				margin-bottom: 6px;
				font-weight: 600;
			}
			.bw-variation-license-selector {
				width: 100%;
				max-width: 100%;
			}
			.bw-variation-license-selector-wrapper .description {
				margin-top: 8px;
				font-size: 12px;
			}
			.bw-variation-license-selector-helper {
				margin-top: 8px;
				font-size: 11px;
				color: #646970;
			}
			.bw-variation-license-selector-helper summary {
				cursor: pointer;
				font-weight: 500;
			}
			.bw-variation-license-selector-helper p {
				margin: 6px 0;
			}
		</style>
		<?php
	}
}
