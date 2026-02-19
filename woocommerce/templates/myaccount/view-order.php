<?php
/**
 * View Order (customized for BlackWork).
 *
 * @package WooCommerce\Templates
 * @version 10.1.0
 */

defined( 'ABSPATH' ) || exit;

$notes = $order->get_customer_order_notes();

$pdf_link_html = '';
if ( shortcode_exists( 'wcpdf_download_pdf' ) ) {
	$pdf_link_html = do_shortcode(
		sprintf(
			'[wcpdf_download_pdf order_ids="%d" template_type="invoice" display="download"]',
			(int) $order_id
		)
	);

	if ( '' === trim( wp_strip_all_tags( (string) $pdf_link_html ) ) ) {
		$pdf_link_html = do_shortcode(
			sprintf(
				'[wcpdf_download_pdf order_ids="%d" template_type="invoice"]',
				(int) $order_id
			)
		);
	}
}
?>
<div class="bw-view-order">
	<header class="bw-view-order__header">
		<p class="bw-view-order__status">
		<?php
		echo wp_kses_post(
			apply_filters(
				'woocommerce_order_details_status',
				sprintf(
					/* translators: 1: order number 2: order date 3: order status */
					esc_html__( 'Order #%1$s was placed on %2$s and is currently %3$s.', 'woocommerce' ),
					'<mark class="order-number">' . $order->get_order_number() . '</mark>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'<mark class="order-date">' . wc_format_datetime( $order->get_date_created() ) . '</mark>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'<mark class="order-status">' . wc_get_order_status_name( $order->get_status() ) . '</mark>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				),
				$order
			)
		);
		?>
		</p>
		<?php if ( $pdf_link_html ) : ?>
			<div class="bw-view-order__actions">
				<div class="bw-view-order__pdf">
					<?php echo wp_kses_post( $pdf_link_html ); ?>
				</div>
			</div>
		<?php endif; ?>
	</header>

	<?php if ( $notes ) : ?>
		<section class="bw-view-order__updates">
			<h2><?php esc_html_e( 'Order updates', 'woocommerce' ); ?></h2>
			<ol class="woocommerce-OrderUpdates commentlist notes">
				<?php foreach ( $notes as $note ) : ?>
					<li class="woocommerce-OrderUpdate comment note">
						<div class="woocommerce-OrderUpdate-inner comment_container">
							<div class="woocommerce-OrderUpdate-text comment-text">
								<p class="woocommerce-OrderUpdate-meta meta"><?php echo date_i18n( esc_html__( 'l jS \o\f F Y, h:ia', 'woocommerce' ), strtotime( $note->comment_date ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
								<div class="woocommerce-OrderUpdate-description description">
									<?php echo wpautop( wptexturize( $note->comment_content ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
								<div class="clear"></div>
							</div>
							<div class="clear"></div>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
		</section>
	<?php endif; ?>
</div>

<?php do_action( 'woocommerce_view_order', $order_id ); ?>
