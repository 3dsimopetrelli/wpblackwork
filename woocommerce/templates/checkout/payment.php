<?php
/**
 * Checkout Payment Section - Shopify-style Accordion
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/payment.php.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.1.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_before_payment' );
}
?>
<div id="payment" class="woocommerce-checkout-payment">
	<?php if ( WC()->cart->needs_payment() ) : ?>
		<ul class="bw-payment-methods wc_payment_methods payment_methods methods">
			<?php
			if ( ! empty( $available_gateways ) ) {
				$gateway_count = 0;
				foreach ( $available_gateways as $gateway ) {
					$gateway_count++;
					$gateway_id = esc_attr( $gateway->id );
					$is_checked = $gateway_count === 1 ? 'checked="checked"' : '';
					?>
					<li class="bw-payment-method wc_payment_method payment_method_<?php echo $gateway_id; ?>" data-gateway-id="<?php echo $gateway_id; ?>">
						<div class="bw-payment-method__header">
							<input
								id="payment_method_<?php echo $gateway_id; ?>"
								type="radio"
								class="input-radio"
								name="payment_method"
								value="<?php echo $gateway_id; ?>"
								<?php echo $is_checked; ?>
								data-order_button_text="<?php echo esc_attr( $gateway->order_button_text ); ?>"
							/>
							<label for="payment_method_<?php echo $gateway_id; ?>" class="bw-payment-method__label">
								<span class="bw-payment-method__title">
									<?php echo wp_kses_post( $gateway->get_title() ); ?>
								</span>
								<?php if ( $gateway->get_icon() ) : ?>
									<span class="bw-payment-method__icons">
										<?php echo wp_kses_post( $gateway->get_icon() ); ?>
									</span>
								<?php endif; ?>
							</label>
						</div>

						<?php if ( $gateway->has_fields() || $gateway->get_description() ) : ?>
							<div class="bw-payment-method__content payment_box payment_method_<?php echo $gateway_id; ?>" <?php echo $gateway_count === 1 ? 'style="display:block;"' : 'style="display:none;"'; ?>>
								<div class="bw-payment-method__inner">
									<?php if ( $gateway->get_description() ) : ?>
										<div class="bw-payment-method__description">
											<?php echo wp_kses_post( wpautop( wptexturize( $gateway->get_description() ) ) ); ?>
										</div>
									<?php endif; ?>

									<?php if ( $gateway->has_fields() ) : ?>
										<div class="bw-payment-method__fields">
											<?php $gateway->payment_fields(); ?>
										</div>
									<?php endif; ?>
								</div>
							</div>
						<?php else : ?>
							<div class="bw-payment-method__content payment_box payment_method_<?php echo $gateway_id; ?>" <?php echo $gateway_count === 1 ? 'style="display:block;"' : 'style="display:none;"'; ?>>
								<div class="bw-payment-method__inner">
									<div class="bw-payment-method__selected-indicator">
										<svg class="bw-payment-check-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
											<circle cx="8" cy="8" r="7.5" fill="#27ae60" stroke="#27ae60"/>
											<path d="M5 8L7 10L11 6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
										<span><?php echo wp_kses_post( $gateway->get_title() ); ?> selected</span>
									</div>
									<p class="bw-payment-method__instruction">
										<?php
										// Translators: %s is the payment method name
										printf(
											esc_html__( 'Click the "%s" button to submit your payment information and complete your order.', 'woocommerce' ),
											esc_html( $gateway->order_button_text ? $gateway->order_button_text : __( 'Place order', 'woocommerce' ) )
										);
										?>
									</p>
								</div>
							</div>
						<?php endif; ?>
					</li>
					<?php
				}
			} else {
				echo '<li class="woocommerce-notice woocommerce-notice--info woocommerce-info">' . apply_filters( 'woocommerce_no_available_payment_methods_message', esc_html__( 'Sorry, it seems that there are no available payment methods. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</ul>
	<?php endif; ?>

	<div class="form-row place-order">
		<noscript>
			<?php esc_html_e( 'Since your browser does not support JavaScript, or it is disabled, please ensure you click the <em>Update Totals</em> button before placing your order. You may be charged more than the amount stated above if you fail to do so.', 'woocommerce' ); ?>
			<br/><button type="submit" class="button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="woocommerce_checkout_update_totals" value="<?php esc_attr_e( 'Update totals', 'woocommerce' ); ?>"><?php esc_html_e( 'Update totals', 'woocommerce' ); ?></button>
		</noscript>

		<?php wc_get_template( 'checkout/terms.php' ); ?>

		<?php do_action( 'woocommerce_review_order_before_submit' ); ?>

		<?php
		$default_button_text = apply_filters( 'woocommerce_order_button_text', __( 'Place order', 'woocommerce' ) );
		$first_gateway = ! empty( $available_gateways ) ? reset( $available_gateways ) : null;
		$initial_button_text = $first_gateway && $first_gateway->order_button_text ? $first_gateway->order_button_text : $default_button_text;
		?>

		<button
			type="submit"
			class="button alt bw-place-order-btn<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"
			name="woocommerce_checkout_place_order"
			id="place_order"
			value="<?php echo esc_attr( $initial_button_text ); ?>"
			data-value="<?php echo esc_attr( $initial_button_text ); ?>"
			data-default-text="<?php echo esc_attr( $default_button_text ); ?>"
		>
			<span class="bw-place-order-btn__text"><?php echo esc_html( $initial_button_text ); ?></span>
		</button>

		<?php do_action( 'woocommerce_review_order_after_submit' ); ?>

		<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
	</div>
</div>
<?php
if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_after_payment' );
}
