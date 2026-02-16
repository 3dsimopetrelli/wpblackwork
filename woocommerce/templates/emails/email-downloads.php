<?php
/**
 * Email Downloads (Blackwork override).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.4.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );
$account_url                = wc_get_page_permalink( 'myaccount' );
$billing_email              = isset( $order ) && is_a( $order, 'WC_Order' ) ? sanitize_email( (string) $order->get_billing_email() ) : '';
$download_query_args        = [
    'bw_after_login'   => 'downloads',
    'bw_post_checkout' => '1',
];

if ( $billing_email ) {
    $download_query_args['bw_invite_email'] = $billing_email;
}

$downloads_url = $account_url ? add_query_arg( $download_query_args, $account_url ) : wc_get_page_permalink( 'myaccount' );

if ( ! $downloads_url ) {
    $downloads_url = home_url( '/my-account/' );
}

$custom_columns = [
    'download-product' => __( 'Product', 'woocommerce' ),
    'download-file'    => __( 'Download', 'woocommerce' ),
];

?><h2 class="woocommerce-order-downloads__title<?php echo $email_improvements_enabled ? ' email-order-detail-heading' : ''; ?>"><?php esc_html_e( 'Downloads', 'woocommerce' ); ?></h2>

<table
	class="td font-family bw-email-downloads-table<?php echo $email_improvements_enabled ? ' email-order-details' : ''; ?>"
	cellspacing="0"
	cellpadding="<?php echo $email_improvements_enabled ? '0' : '6'; ?>"
	style="width: 100%; margin-bottom: 40px;"
	border="<?php echo $email_improvements_enabled ? '0' : '1'; ?>"
>
	<thead>
		<tr>
			<?php foreach ( $custom_columns as $column_id => $column_name ) : ?>
				<th class="td <?php echo array_key_last( $custom_columns ) === $column_id ? 'text-align-right' : 'text-align-left'; ?>" scope="col">
					<?php echo esc_html( $column_name ); ?>
				</th>
			<?php endforeach; ?>
		</tr>
	</thead>

	<?php foreach ( $downloads as $download ) : ?>
		<tr>
			<th class="td text-align-left" scope="row">
				<a href="<?php echo esc_url( get_permalink( $download['product_id'] ) ); ?>" class="bw-email-download-product-link"><?php echo wp_kses_post( $download['product_name'] ); ?></a>
			</th>
			<td class="td text-align-right">
				<a href="<?php echo esc_url( $downloads_url ); ?>" class="woocommerce-MyAccount-downloads-file button alt bw-email-download-btn">
					<span><?php esc_html_e( 'Download', 'woocommerce' ); ?></span>
				</a>
			</td>
		</tr>
	<?php endforeach; ?>
</table>
