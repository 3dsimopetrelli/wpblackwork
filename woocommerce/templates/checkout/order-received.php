<?php
/**
 * "Order received" message.
 *
 * Override for order-received guest/login-gate scenario.
 *
 * @package WooCommerce\Templates
 * @version 8.8.0
 *
 * @var WC_Order|false $order
 */

defined( 'ABSPATH' ) || exit;

$is_guest_order_received_gate = ! is_user_logged_in()
	&& function_exists( 'is_wc_endpoint_url' )
	&& is_wc_endpoint_url( 'order-received' )
	&& false === $order;
?>

<?php if ( $is_guest_order_received_gate ) : ?>
	<h1 class="bw-verify-email-cta__title">
		<?php esc_html_e( 'Uh-oh, your session has expired', 'wpblackwork' ); ?>
	</h1>
<?php else : ?>
	<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">
		<?php
		$message = apply_filters(
			'woocommerce_thankyou_order_received_text',
			esc_html( __( 'Thank you. Your order has been received.', 'woocommerce' ) ),
			$order
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $message;
		?>
	</p>
<?php endif; ?>
