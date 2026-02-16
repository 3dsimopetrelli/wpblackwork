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

$is_custom_order_received = ( $order instanceof WC_Order );

if ( $order instanceof WC_Order ) {
	error_log(
		sprintf(
			'BW order-received trace v2: order_id=%d is_user_logged_in=%d current_user_id=%d order_user_id=%d key_in_url=%d',
			(int) $order->get_id(),
			is_user_logged_in() ? 1 : 0,
			(int) get_current_user_id(),
			(int) $order->get_user_id(),
			isset( $_GET['key'] ) ? 1 : 0 // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		)
	);
}
?>

<?php if ( $is_guest_order_received_gate ) : ?>
	<?php error_log( 'BW order-received branch: guest-gate' ); ?>
	<h1 class="bw-verify-email-cta__title">
		<span class="bw-verify-email-cta__title-line"><?php esc_html_e( 'Uh-oh', 'wpblackwork' ); ?></span>
		<span class="bw-verify-email-cta__title-line"><?php esc_html_e( 'your session has', 'wpblackwork' ); ?></span>
		<span class="bw-verify-email-cta__title-line"><?php esc_html_e( 'expired', 'wpblackwork' ); ?></span>
	</h1>
<?php elseif ( $is_custom_order_received ) : ?>
	<?php
	error_log( 'BW order-received branch: custom-order-confirmed' );
	remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );

	$item_lines = array();
	foreach ( $order->get_items() as $item ) {
		$name       = $item->get_name();
		$quantity   = (int) $item->get_quantity();
		$item_lines[] = sprintf( '%1$s × %2$d', $name, $quantity );
	}
	$product_line = implode( ', ', $item_lines );

	$billing_name = trim( $order->get_formatted_billing_full_name() );
	if ( '' === $billing_name ) {
		$billing_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
	}

	$subtotal_amount = (float) $order->get_subtotal();
	$discount_amount = (float) $order->get_discount_total();
	$total_amount    = (float) $order->get_total();
	$billing_address = $order->get_formatted_billing_address();
	$billing_email   = $order->get_billing_email();
	?>
	<!-- BW order-received custom hero v2 -->
	<section class="bw-order-confirmed" aria-label="<?php esc_attr_e( 'Order confirmed', 'wpblackwork' ); ?>">
		<header class="bw-order-confirmed__hero">
			<h1 class="bw-order-confirmed__title"><?php esc_html_e( 'THANK YOU', 'wpblackwork' ); ?></h1>
			<p class="bw-order-confirmed__subtitle"><?php esc_html_e( 'Order confirmed', 'wpblackwork' ); ?></p>
			<p class="bw-order-confirmed__meta">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: order number, 2: date */
						__( 'Order #%1$s · %2$s', 'wpblackwork' ),
						$order->get_order_number(),
						wc_format_datetime( $order->get_date_created() )
					)
				);
				?>
			</p>
			<p class="bw-order-confirmed__cta">
				<a class="elementor-button-link elementor-button" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
					<span class="elementor-button-content-wrapper">
						<span class="elementor-button-text"><?php esc_html_e( 'Go to your account', 'wpblackwork' ); ?></span>
					</span>
				</a>
			</p>
			<p class="bw-order-confirmed__lead"><?php esc_html_e( 'Your files are available inside your account.', 'wpblackwork' ); ?></p>
			<p class="bw-order-confirmed__lead bw-order-confirmed__lead--secondary"><?php esc_html_e( 'You can download them anytime.', 'wpblackwork' ); ?></p>
		</header>

		<div class="bw-order-confirmed__cards">
			<section class="bw-order-card">
				<h2 class="bw-order-card__title"><?php esc_html_e( 'Order summary', 'wpblackwork' ); ?></h2>
				<div class="bw-order-card__rows">
					<div class="bw-order-card__row">
						<span><?php esc_html_e( 'Email', 'wpblackwork' ); ?></span>
						<span><?php echo esc_html( $billing_email ); ?></span>
					</div>
					<div class="bw-order-card__row">
						<span><?php esc_html_e( 'Product', 'wpblackwork' ); ?></span>
						<span><?php echo esc_html( $product_line ); ?></span>
					</div>
					<div class="bw-order-card__row">
						<span><?php esc_html_e( 'Subtotal', 'wpblackwork' ); ?></span>
						<span><?php echo wp_kses_post( wc_price( $subtotal_amount ) ); ?></span>
					</div>
					<div class="bw-order-card__row">
						<span><?php esc_html_e( 'Discount', 'wpblackwork' ); ?></span>
						<span><?php echo wp_kses_post( wc_price( -1 * abs( $discount_amount ) ) ); ?></span>
					</div>
					<div class="bw-order-card__row bw-order-card__row--total">
						<span><?php esc_html_e( 'Total', 'wpblackwork' ); ?></span>
						<span><?php echo wp_kses_post( wc_price( $total_amount ) ); ?></span>
					</div>
				</div>
			</section>

			<section class="bw-order-card">
				<h2 class="bw-order-card__title"><?php esc_html_e( 'Billing address', 'wpblackwork' ); ?></h2>
				<div class="bw-order-card__address">
					<?php if ( '' !== $billing_name ) : ?>
						<p><?php echo esc_html( $billing_name ); ?></p>
					<?php endif; ?>
					<?php if ( '' !== $billing_address ) : ?>
						<div class="bw-order-card__address-lines"><?php echo wp_kses_post( wpautop( $billing_address ) ); ?></div>
					<?php endif; ?>
					<?php if ( '' !== $billing_email ) : ?>
						<p><?php echo esc_html( $billing_email ); ?></p>
					<?php endif; ?>
				</div>
			</section>
		</div>

		<section class="bw-order-next">
			<h2 class="bw-order-next__title"><?php esc_html_e( 'What happens next?', 'wpblackwork' ); ?></h2>
			<div class="bw-order-next__step">
				<div class="bw-order-next__icon" aria-hidden="true">✉</div>
				<div>
					<h3><?php esc_html_e( 'Email sent', 'wpblackwork' ); ?></h3>
					<p><?php esc_html_e( 'We have sent you a confirmation email.', 'wpblackwork' ); ?></p>
					<p><?php esc_html_e( 'Please confirm your account and create a secure password to activate it.', 'wpblackwork' ); ?></p>
				</div>
			</div>
			<div class="bw-order-next__step">
				<div class="bw-order-next__icon" aria-hidden="true">👤</div>
				<div>
					<h3><?php esc_html_e( 'Access your account', 'wpblackwork' ); ?></h3>
					<p><?php esc_html_e( 'Log in to manage your order, download your files (if digital), or update your account details.', 'wpblackwork' ); ?></p>
				</div>
			</div>
		</section>
	</section>
<?php else : ?>
	<?php error_log( 'BW order-received branch: fallback-default' ); ?>
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
