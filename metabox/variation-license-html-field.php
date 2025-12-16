<?php
/**
 * Variation License HTML Field
 *
 * Adds custom table fields to product variations
 * for storing license terms/conditions entries.
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 * Add custom field to variation settings.
 */
add_action( 'woocommerce_variation_options_pricing', 'bw_add_variation_license_html_field', 10, 3 );

function bw_add_variation_license_html_field( $loop, $variation_data, $variation ) {
$variation_id = $variation->ID;
$column_one   = bw_get_variation_license_column( $variation_id, '_bw_variation_license_col1' );
$column_two   = bw_get_variation_license_column( $variation_id, '_bw_variation_license_col2' );
?>
<div class="form-row form-row-full bw-variation-license-html-wrapper">
<label>
<?php esc_html_e( 'License Terms', 'bw' ); ?>
<?php echo wc_help_tip( __( 'Populate up to 10 rows per column. These entries replace the old HTML block and will appear in the license box under the variation buttons.', 'bw' ) ); ?>
</label>
<p class="description" style="margin-top: 0;">
<?php esc_html_e( 'Only non-empty rows are displayed on the product.', 'bw' ); ?>
</p>
<div class="bw-variation-license-table">
<div class="bw-variation-license-table__header">
<span><?php esc_html_e( 'Column 1', 'bw' ); ?></span>
<span><?php esc_html_e( 'Column 2', 'bw' ); ?></span>
</div>
<?php for ( $i = 0; $i < 10; $i++ ) : ?>
<div class="bw-variation-license-table__row">
<input
type="text"
name="bw_variation_license_col1[<?php echo esc_attr( $loop ); ?>][<?php echo esc_attr( $i ); ?>]"
value="<?php echo esc_attr( $column_one[ $i ] ); ?>"
placeholder="<?php printf( esc_attr__( 'Row %d label', 'bw' ), $i + 1 ); ?>"
class="bw-variation-license-table__input"
/>
<input
type="text"
name="bw_variation_license_col2[<?php echo esc_attr( $loop ); ?>][<?php echo esc_attr( $i ); ?>]"
value="<?php echo esc_attr( $column_two[ $i ] ); ?>"
placeholder="<?php printf( esc_attr__( 'Row %d value', 'bw' ), $i + 1 ); ?>"
class="bw-variation-license-table__input"
/>
</div>
<?php endfor; ?>
</div>
</div>
<?php
}

/**
 * Save custom field value.
 */
add_action( 'woocommerce_save_product_variation', 'bw_save_variation_license_html_field', 10, 2 );

function bw_save_variation_license_html_field( $variation_id, $i ) {
$col1_values = isset( $_POST['bw_variation_license_col1'][ $i ] ) && is_array( $_POST['bw_variation_license_col1'][ $i ] )
? array_values( $_POST['bw_variation_license_col1'][ $i ] )
: [];

$col2_values = isset( $_POST['bw_variation_license_col2'][ $i ] ) && is_array( $_POST['bw_variation_license_col2'][ $i ] )
? array_values( $_POST['bw_variation_license_col2'][ $i ] )
: [];

$col1_sanitized = [];
$col2_sanitized = [];

for ( $index = 0; $index < 10; $index++ ) {
$col1_sanitized[ $index ] = isset( $col1_values[ $index ] ) ? sanitize_text_field( wp_unslash( $col1_values[ $index ] ) ) : '';
$col2_sanitized[ $index ] = isset( $col2_values[ $index ] ) ? sanitize_text_field( wp_unslash( $col2_values[ $index ] ) ) : '';
}

update_post_meta( $variation_id, '_bw_variation_license_col1', $col1_sanitized );
update_post_meta( $variation_id, '_bw_variation_license_col2', $col2_sanitized );

// Remove the legacy HTML meta to avoid stale content.
delete_post_meta( $variation_id, '_bw_variation_license_html' );
}

/**
 * AJAX handler to get variation license HTML.
 */
add_action( 'wp_ajax_bw_get_variation_license_html', 'bw_get_variation_license_html' );
add_action( 'wp_ajax_nopriv_bw_get_variation_license_html', 'bw_get_variation_license_html' );

function bw_get_variation_license_html() {
// Verify nonce.
check_ajax_referer( 'bw_price_variation_nonce', 'nonce' );

$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;

if ( ! $variation_id ) {
wp_send_json_error( [ 'message' => 'Invalid variation ID' ] );
}

$license_html = bw_get_variation_license_table_html( $variation_id );

wp_send_json_success( [ 'html' => $license_html ] );
}

/**
 * Add CSS for the field in admin.
 */
add_action( 'admin_head', 'bw_variation_license_html_field_css' );

function bw_variation_license_html_field_css() {
global $pagenow, $post_type;

if ( ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) && 'product' === $post_type ) {
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

.bw-variation-license-table {
display: grid;
grid-template-columns: 1fr;
gap: 6px;
}

.bw-variation-license-table__header {
display: grid;
grid-template-columns: 1fr 1fr;
gap: 10px;
font-weight: 600;
color: #000;
font-size: 13px;
}

.bw-variation-license-table__row {
display: grid;
grid-template-columns: 1fr 1fr;
gap: 10px;
}

.bw-variation-license-table__input {
border: 1px solid #8c8f94;
border-radius: 4px;
padding: 8px;
width: 100%;
box-sizing: border-box;
}

.bw-variation-license-table__input:focus {
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

/**
 * Helper to fetch a column of variation license rows with safe defaults.
 *
 * @param int    $variation_id Variation ID.
 * @param string $meta_key     Meta key to read.
 *
 * @return array
 */
function bw_get_variation_license_column( $variation_id, $meta_key ) {
$values = get_post_meta( $variation_id, $meta_key, true );

if ( ! is_array( $values ) ) {
$values = [];
}

$normalized = [];

for ( $index = 0; $index < 10; $index++ ) {
$normalized[ $index ] = isset( $values[ $index ] ) ? sanitize_text_field( wp_unslash( $values[ $index ] ) ) : '';
}

return $normalized;
}

/**
 * Build the HTML table for a variation's license terms.
 *
 * @param int $variation_id Variation ID.
 *
 * @return string
 */
function bw_get_variation_license_table_html( $variation_id ) {
$col1 = bw_get_variation_license_column( $variation_id, '_bw_variation_license_col1' );
$col2 = bw_get_variation_license_column( $variation_id, '_bw_variation_license_col2' );
$rows = [];

for ( $index = 0; $index < 10; $index++ ) {
$left  = isset( $col1[ $index ] ) ? trim( $col1[ $index ] ) : '';
$right = isset( $col2[ $index ] ) ? trim( $col2[ $index ] ) : '';

if ( '' === $left && '' === $right ) {
continue;
}

$rows[] = [
'left'  => $left,
'right' => $right,
];
}

if ( empty( $rows ) ) {
return '';
}

$markup = '<div class="bw-license-table-wrapper"><table class="bw-license-table"><tbody>';

foreach ( $rows as $row ) {
$markup .= sprintf(
'<tr><td class="bw-license-table__cell bw-license-table__cell--label">%1$s</td><td class="bw-license-table__cell bw-license-table__cell--value">%2$s</td></tr>',
esc_html( $row['left'] ),
esc_html( $row['right'] )
);
}

$markup .= '</tbody></table></div>';

return $markup;
}
