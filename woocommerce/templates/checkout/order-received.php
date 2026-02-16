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

if ( function_exists( 'bw_mew_render_order_received_logo_header' ) ) {
	bw_mew_render_order_received_logo_header();
}
?>

<?php if ( $is_guest_order_received_gate ) : ?>
	<h1 class="bw-verify-email-cta__title">
		<span class="bw-verify-email-cta__title-line"><?php esc_html_e( 'Uh-oh', 'wpblackwork' ); ?></span>
		<span class="bw-verify-email-cta__title-line"><?php esc_html_e( 'your session has', 'wpblackwork' ); ?></span>
		<span class="bw-verify-email-cta__title-line"><?php esc_html_e( 'expired', 'wpblackwork' ); ?></span>
	</h1>
<?php elseif ( $is_custom_order_received ) : ?>
	<?php
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
	$login_provider  = strtolower( (string) get_option( 'bw_account_login_provider', 'wordpress' ) );
	$provision_enabled = '1' === (string) get_option( 'bw_supabase_checkout_provision_enabled', '0' );
	$show_email_reminder_cta = ! is_user_logged_in() && ( 'supabase' === $login_provider || $provision_enabled );
	$my_account_url  = wc_get_page_permalink( 'myaccount' );
	$cta_url         = $my_account_url;
	$cta_label       = __( 'Go to your account', 'wpblackwork' );

	if ( $show_email_reminder_cta ) {
		$cta_url   = add_query_arg(
			[
				'bw_post_checkout' => '1',
				'bw_invite_email'  => $billing_email,
			],
			$my_account_url
		);
		$cta_label = __( 'Check your email to finish account setup', 'wpblackwork' );
	}
	?>
	<section class="bw-order-confirmed" aria-label="<?php esc_attr_e( 'Order confirmed', 'wpblackwork' ); ?>">
		<div class="bw-order-confirmed__hero">
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
			<p class="bw-order-confirmed__cta bw-verify-email-cta__actions">
				<?php if ( $show_email_reminder_cta ) : ?>
					<span class="elementor-button elementor-button-link bw-order-confirmed__cta-button--static" role="button" aria-disabled="true">
						<span class="elementor-button-content-wrapper">
							<span class="elementor-button-text"><?php echo esc_html( $cta_label ); ?></span>
						</span>
					</span>
				<?php else : ?>
					<a class="elementor-button-link elementor-button" href="<?php echo esc_url( $cta_url ); ?>">
						<span class="elementor-button-content-wrapper">
							<span class="elementor-button-text"><?php echo esc_html( $cta_label ); ?></span>
						</span>
					</a>
				<?php endif; ?>
			</p>
			<p class="bw-order-confirmed__lead"><?php esc_html_e( 'Your files are available inside your account.', 'wpblackwork' ); ?></p>
			<p class="bw-order-confirmed__lead bw-order-confirmed__lead--secondary"><?php esc_html_e( 'You can download them anytime.', 'wpblackwork' ); ?></p>
		</div>

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
				<div class="bw-order-next__icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" role="presentation" focusable="false">
						<path d="M3 7.5h18v9H3z" fill="none" stroke="currentColor" stroke-width="1.8" />
						<path d="M3 8l9 6 9-6" fill="none" stroke="currentColor" stroke-width="1.8" />
					</svg>
				</div>
				<div>
					<h3><?php esc_html_e( 'Order confirmation email', 'wpblackwork' ); ?></h3>
					<p><?php esc_html_e( 'We have sent an order confirmation email with your order details and receipt.', 'wpblackwork' ); ?></p>
				</div>
			</div>
			<div class="bw-order-next__step">
				<div class="bw-order-next__icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" role="presentation" focusable="false">
						<rect x="6.5" y="11" width="11" height="8" rx="1.8" fill="none" stroke="currentColor" stroke-width="1.8" />
						<path d="M9 11V8.7a3 3 0 0 1 6 0V11" fill="none" stroke="currentColor" stroke-width="1.8" />
					</svg>
				</div>
				<div>
					<h3><?php esc_html_e( 'Account setup email', 'wpblackwork' ); ?></h3>
					<p><?php esc_html_e( 'We have also sent a second email to help you create your password and secure your account.', 'wpblackwork' ); ?></p>
				</div>
			</div>
			<div class="bw-order-next__step">
				<div class="bw-order-next__icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" role="presentation" focusable="false">
						<circle cx="12" cy="8" r="3.3" fill="none" stroke="currentColor" stroke-width="1.8" />
						<path d="M5.5 19c0-3.2 2.9-5.8 6.5-5.8s6.5 2.6 6.5 5.8" fill="none" stroke="currentColor" stroke-width="1.8" />
					</svg>
				</div>
				<div>
					<h3><?php esc_html_e( 'Access your account', 'wpblackwork' ); ?></h3>
					<p><?php esc_html_e( 'After setting your password, log in to view your order, download your files, and manage your account details anytime.', 'wpblackwork' ); ?></p>
				</div>
			</div>
		</section>

		<p class="bw-order-confirmed__home-cta bw-verify-email-cta__actions">
			<a class="elementor-button-link elementor-button" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<span class="elementor-button-content-wrapper">
					<span class="elementor-button-text"><?php esc_html_e( 'Go to Home', 'wpblackwork' ); ?></span>
				</span>
			</a>
		</p>
	</section>
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
