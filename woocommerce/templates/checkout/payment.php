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

defined('ABSPATH') || exit;

if (!wp_doing_ajax()) {
	do_action('woocommerce_review_order_before_payment');
}
?>
<div id="payment" class="woocommerce-checkout-payment">
	<h2 class="bw-payment-section-title"><?php esc_html_e('Payment', 'woocommerce'); ?></h2>
	<p class="bw-payment-section-subtitle">
		<?php esc_html_e('All transactions are secure and encrypted.', 'woocommerce'); ?>
	</p>

	<?php if (WC()->cart->needs_payment()): ?>
		<ul class="bw-payment-methods wc_payment_methods payment_methods methods">
			<?php
			if (!empty($available_gateways)) {
				// Preserve selected method across AJAX checkout updates.
				$chosen_method = isset( $_POST['payment_method'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
					? wc_clean( wp_unslash( $_POST['payment_method'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
					: WC()->session->get( 'chosen_payment_method' );
				// Fall back to the first gateway when nothing is stored yet.
				if ( empty( $chosen_method ) ) {
					$first         = reset( $available_gateways );
					$chosen_method = $first ? $first->id : '';
				}

				$gateway_count = 0;
				foreach ($available_gateways as $gateway) {
					$gateway_count++;
					$gateway_id = esc_attr($gateway->id);
					$is_checked = ( $gateway->id === $chosen_method ) ? 'checked="checked"' : '';
					?>
					<li class="bw-payment-method wc_payment_method payment_method_<?php echo $gateway_id; ?><?php echo $gateway->id === $chosen_method ? ' is-selected' : ''; ?>"
						data-gateway-id="<?php echo $gateway_id; ?>">
						<div class="bw-payment-method__header">
							<input id="payment_method_<?php echo $gateway_id; ?>" type="radio" class="input-radio"
								name="payment_method" value="<?php echo $gateway_id; ?>" <?php echo $is_checked; ?>
								data-order_button_text="<?php echo esc_attr($gateway->order_button_text); ?>" />
							<label for="payment_method_<?php echo $gateway_id; ?>" class="bw-payment-method__label">
								<span class="bw-payment-method__title">
									<?php echo wp_kses_post($gateway->get_title()); ?>
								</span>
								<?php
								// Show gateway icon with PayPal fallback logo.
								$gateway_type = strtolower($gateway_id);
								$is_card_gateway_icon = (strpos($gateway_type, 'stripe') !== false ||
									strpos($gateway_type, 'card') !== false ||
									strpos($gateway_type, 'credit') !== false ||
									strpos($gateway_type, 'debit') !== false);
								$is_google_pay_icon = (strpos($gateway_type, 'google') !== false ||
									strpos($gateway_type, 'googlepay') !== false ||
									'bw_google_pay' === $gateway_type);
								$is_paypal = (strpos($gateway_type, 'paypal') !== false ||
									strpos($gateway_type, 'ppcp') !== false);
								$icon_html = '';
								$icon_class = 'bw-payment-method__icon';
								$asset_base_url = defined('BW_MEW_URL')
									? BW_MEW_URL
									: content_url('/plugins/wpblackwork/');

								if ($is_card_gateway_icon) {
									$card_logo_url = trailingslashit($asset_base_url) . 'assets/images/payment-icons/card-outline.svg';
									$icon_html = '<img src="' . esc_url($card_logo_url) . '" alt="' . esc_attr__('Card', 'woocommerce') . '" loading="lazy" decoding="async" />';
									$icon_class .= ' bw-payment-method__icon--card';
								} elseif ($is_google_pay_icon) {
									$google_pay_logo_url = trailingslashit($asset_base_url) . 'assets/images/payment-icons/google-pay.svg';
									$icon_html = '<img src="' . esc_url($google_pay_logo_url) . '" alt="' . esc_attr__('Google Pay', 'woocommerce') . '" loading="lazy" decoding="async" />';
									$icon_class .= ' bw-payment-method__icon--google-pay';
								} elseif ($is_paypal) {
									$paypal_logo_url = trailingslashit($asset_base_url) . 'assets/images/payment-icons/paypal.svg';
									$icon_html = '<img src="' . esc_url($paypal_logo_url) . '" alt="' . esc_attr__('PayPal', 'woocommerce') . '" loading="lazy" decoding="async" />';
									$icon_class .= ' bw-payment-method__icon--paypal';
								}

								// If fallback is not used/available, use the gateway-provided icon.
								if (empty($icon_html)) {
									$icon_html = $gateway->get_icon();
								}

								if (!empty($icon_html)) {
									echo '<span class="' . esc_attr($icon_class) . '">' . wp_kses_post($icon_html) . '</span>';
								}
								?>
							</label>
						</div>

						<?php if ($gateway->has_fields() || $gateway->get_description()): ?>
							<div
								class="bw-payment-method__content payment_box payment_method_<?php echo $gateway_id; ?> <?php echo $gateway->id === $chosen_method ? 'is-open' : ''; ?>">
								<div class="bw-payment-method__inner">
									<?php
									// Hide description for PayPal (we show custom redirect message instead)
									$is_paypal_desc = (strpos($gateway_id, 'paypal') !== false ||
										strpos($gateway_id, 'ppcp') !== false);

									if ($gateway->get_description() && !$is_paypal_desc && 'bw_google_pay' !== $gateway_id && 'bw_klarna' !== $gateway_id):
										?>
										<div class="bw-payment-method__description">
											<?php echo wp_kses_post(wpautop(wptexturize($gateway->get_description()))); ?>
										</div>
									<?php endif; ?>

									<?php
									// Always call payment_fields() for card/stripe gateways even if has_fields() is false
									$is_card_gateway = (strpos($gateway_type, 'stripe') !== false ||
										strpos($gateway_type, 'card') !== false ||
										strpos($gateway_type, 'credit') !== false);

									if ($gateway->has_fields() || $is_card_gateway):
										?>
										<div class="bw-payment-method__fields">
											<?php if ($is_card_gateway): ?>
												<div class="bw-stripe-fields-wrapper">
													<?php $gateway->payment_fields(); ?>
												</div>
											<?php else: ?>
												<?php $gateway->payment_fields(); ?>
											<?php endif; ?>
										</div>
									<?php endif; ?>

									<?php
									// Distinguish PPCP (renders own buttons) from classic PayPal (redirect).
									$is_ppcp           = (strpos($gateway_id, 'ppcp') !== false);
									$is_classic_paypal = (strpos($gateway_id, 'paypal') !== false && !$is_ppcp);
									$is_google_pay     = (strpos($gateway_id, 'google') !== false ||
										strpos($gateway_id, 'googlepay') !== false);
									$is_bw_google_pay  = ('bw_google_pay' === $gateway_id);
									$is_bw_klarna      = ('bw_klarna' === $gateway_id);
									$is_bw_apple_pay   = ('bw_apple_pay' === $gateway_id);

									if ($is_classic_paypal):
										?>
										<div class="bw-paypal-redirect">
											<svg class="bw-paypal-redirect__icon" xmlns="http://www.w3.org/2000/svg"
												viewBox="-252.3 356.1 163 80.9">
												<path fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="2"
													d="M-108.9 404.1v30c0 1.1-.9 2-2 2H-231c-1.1 0-2-.9-2-2v-75c0-1.1.9-2 2-2h120.1c1.1 0 2 .9 2 2v37m-124.1-29h124.1">
												</path>
												<circle cx="-227.8" cy="361.9" r="1.8" fill="currentColor"></circle>
												<circle cx="-222.2" cy="361.9" r="1.8" fill="currentColor"></circle>
												<circle cx="-216.6" cy="361.9" r="1.8" fill="currentColor"></circle>
												<path fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="2"
													d="M-128.7 400.1H-92m-3.6-4.1 4 4.1-4 4.1"></path>
											</svg>
											<p class="bw-paypal-redirect__text">
												<?php echo esc_html('After clicking "PayPal", you will be redirected to PayPal to complete your purchase securely.'); ?>
											</p>
										</div>
										<?php
									elseif ($is_google_pay || $is_bw_google_pay):
										?>
										<div class="bw-google-pay-info">
											<svg class="bw-google-pay-info__icon" xmlns="http://www.w3.org/2000/svg"
												viewBox="-252.3 356.1 163 80.9">
												<path fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="2"
													d="M-108.9 404.1v30c0 1.1-.9 2-2 2H-231c-1.1 0-2-.9-2-2v-75c0-1.1.9-2 2-2h120.1c1.1 0 2 .9 2 2v37m-124.1-29h124.1">
												</path>
												<circle cx="-227.8" cy="361.9" r="1.8" fill="currentColor"></circle>
												<circle cx="-222.2" cy="361.9" r="1.8" fill="currentColor"></circle>
												<circle cx="-216.6" cy="361.9" r="1.8" fill="currentColor"></circle>
												<path fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="2"
													d="M-128.7 400.1H-92m-3.6-4.1 4 4.1-4 4.1"></path>
											</svg>
											<p class="bw-google-pay-info__text">
												<?php echo esc_html('After clicking "Google Pay", you will be redirected to Google Pay to complete your purchase securely.'); ?>
											</p>
										</div>
										<?php
									elseif ($is_bw_klarna):
										$bw_klarna_enabled = ('1' === get_option('bw_klarna_enabled', '0'));
										$klarna_pk         = (string) get_option('bw_klarna_publishable_key', '');
										$klarna_sk         = (string) get_option('bw_klarna_secret_key', '');
										$klarna_wc_settings = get_option('woocommerce_bw_klarna_settings', array());
										$klarna_wc_enabled  = isset($klarna_wc_settings['enabled']) && 'yes' === $klarna_wc_settings['enabled'];
										$klarna_ready       = ($bw_klarna_enabled && $klarna_wc_enabled && '' !== $klarna_pk && '' !== $klarna_sk);
										?>
										<div class="bw-klarna-info">
											<p class="bw-klarna-info__text<?php echo $klarna_ready ? '' : ' bw-klarna-info__text--error'; ?>">
												<?php
												echo esc_html(
													$klarna_ready
														? 'You\'ll be redirected to Klarna - Flexible payments to complete your purchase.'
														: 'Klarna is not configured. Activate Klarna (BlackWork) in WooCommerce > Settings > Payments.'
												);
												?>
											</p>
										</div>
										<?php
									elseif ($is_bw_apple_pay):
										$bw_apple_enabled = ('1' === get_option('bw_apple_pay_enabled', '0'));
										$apple_pk         = (string) get_option('bw_apple_pay_publishable_key', '');
										$apple_sk         = (string) get_option('bw_apple_pay_secret_key', '');
										$global_pk        = (string) get_option('bw_google_pay_publishable_key', '');
										$global_sk        = (string) get_option('bw_google_pay_secret_key', '');
										$apple_wc_settings = get_option('woocommerce_bw_apple_pay_settings', array());
										$apple_wc_enabled  = isset($apple_wc_settings['enabled']) && 'yes' === $apple_wc_settings['enabled'];
										$apple_pk_effective = '' !== $apple_pk ? $apple_pk : $global_pk;
										$apple_sk_effective = '' !== $apple_sk ? $apple_sk : $global_sk;
										$apple_ready       = ($bw_apple_enabled && $apple_wc_enabled && '' !== $apple_pk_effective && '' !== $apple_sk_effective);
										?>
										<div class="bw-apple-pay-info">
											<p class="bw-apple-pay-info__text<?php echo $apple_ready ? '' : ' bw-apple-pay-info__text--error'; ?>">
												<?php
												echo esc_html(
													$apple_ready
														? 'You\'ll be redirected to Apple Pay to complete your purchase.'
														: 'Apple Pay is not configured. Activate Apple Pay (BlackWork) in WooCommerce > Settings > Payments.'
												);
												?>
											</p>
										</div>
										<?php
									endif;
									?>
								</div>
							</div>
						<?php else: ?>
							<div
								class="bw-payment-method__content payment_box payment_method_<?php echo $gateway_id; ?> <?php echo $gateway->id === $chosen_method ? 'is-open' : ''; ?>">
								<div class="bw-payment-method__inner">
									<?php
									// Check if this is Google Pay for redirect message
									$is_google_pay_else = ((strpos($gateway_id, 'google') !== false ||
										strpos($gateway_id, 'googlepay') !== false));

									if ($is_google_pay_else || $is_bw_google_pay):
										?>
										<div class="bw-google-pay-info">
											<svg class="bw-google-pay-info__icon" xmlns="http://www.w3.org/2000/svg"
												viewBox="-252.3 356.1 163 80.9">
												<path fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="2"
													d="M-108.9 404.1v30c0 1.1-.9 2-2 2H-231c-1.1 0-2-.9-2-2v-75c0-1.1.9-2 2-2h120.1c1.1 0 2 .9 2 2v37m-124.1-29h124.1">
												</path>
												<circle cx="-227.8" cy="361.9" r="1.8" fill="currentColor"></circle>
												<circle cx="-222.2" cy="361.9" r="1.8" fill="currentColor"></circle>
												<circle cx="-216.6" cy="361.9" r="1.8" fill="currentColor"></circle>
												<path fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="2"
													d="M-128.7 400.1H-92m-3.6-4.1 4 4.1-4 4.1"></path>
											</svg>
											<p class="bw-google-pay-info__text">
												<?php echo esc_html('After clicking "Google Pay", you will be redirected to Google Pay to complete your purchase securely.'); ?>
											</p>
										</div>
										<?php
									elseif ('bw_klarna' === $gateway_id):
										$bw_klarna_enabled = ('1' === get_option('bw_klarna_enabled', '0'));
										$klarna_pk         = (string) get_option('bw_klarna_publishable_key', '');
										$klarna_sk         = (string) get_option('bw_klarna_secret_key', '');
										$klarna_wc_settings = get_option('woocommerce_bw_klarna_settings', array());
										$klarna_wc_enabled  = isset($klarna_wc_settings['enabled']) && 'yes' === $klarna_wc_settings['enabled'];
										$klarna_ready       = ($bw_klarna_enabled && $klarna_wc_enabled && '' !== $klarna_pk && '' !== $klarna_sk);
										?>
										<div class="bw-klarna-info">
											<p class="bw-klarna-info__text<?php echo $klarna_ready ? '' : ' bw-klarna-info__text--error'; ?>">
												<?php
												echo esc_html(
													$klarna_ready
														? 'You\'ll be redirected to Klarna - Flexible payments to complete your purchase.'
														: 'Klarna is not configured. Activate Klarna (BlackWork) in WooCommerce > Settings > Payments.'
												);
												?>
											</p>
										</div>
										<?php
									elseif ('bw_apple_pay' === $gateway_id):
										$bw_apple_enabled = ('1' === get_option('bw_apple_pay_enabled', '0'));
										$apple_pk         = (string) get_option('bw_apple_pay_publishable_key', '');
										$apple_sk         = (string) get_option('bw_apple_pay_secret_key', '');
										$global_pk        = (string) get_option('bw_google_pay_publishable_key', '');
										$global_sk        = (string) get_option('bw_google_pay_secret_key', '');
										$apple_wc_settings = get_option('woocommerce_bw_apple_pay_settings', array());
										$apple_wc_enabled  = isset($apple_wc_settings['enabled']) && 'yes' === $apple_wc_settings['enabled'];
										$apple_pk_effective = '' !== $apple_pk ? $apple_pk : $global_pk;
										$apple_sk_effective = '' !== $apple_sk ? $apple_sk : $global_sk;
										$apple_ready       = ($bw_apple_enabled && $apple_wc_enabled && '' !== $apple_pk_effective && '' !== $apple_sk_effective);
										?>
										<div class="bw-apple-pay-info">
											<p class="bw-apple-pay-info__text<?php echo $apple_ready ? '' : ' bw-apple-pay-info__text--error'; ?>">
												<?php
												echo esc_html(
													$apple_ready
														? 'You\'ll be redirected to Apple Pay to complete your purchase.'
														: 'Apple Pay is not configured. Activate Apple Pay (BlackWork) in WooCommerce > Settings > Payments.'
												);
												?>
											</p>
										</div>
										<?php
									else:
										?>
										<div class="bw-payment-method__selected-indicator">
											<svg class="bw-payment-check-icon" width="16" height="16" viewBox="0 0 16 16" fill="none"
												xmlns="http://www.w3.org/2000/svg">
												<circle cx="8" cy="8" r="7.5" fill="#27ae60" stroke="#27ae60" />
												<path d="M5 8L7 10L11 6" stroke="white" stroke-width="2" stroke-linecap="round"
													stroke-linejoin="round" />
											</svg>
											<span><?php echo wp_kses_post($gateway->get_title()); ?> selected</span>
										</div>
										<p class="bw-payment-method__instruction">
											<?php
											// Translators: %s is the payment method name
											printf(
												esc_html__('Click the "%s" button to submit your payment information and complete your order.', 'woocommerce'),
												esc_html($gateway->order_button_text ? $gateway->order_button_text : __('Place order', 'woocommerce'))
											);
											?>
										</p>
										<?php
									endif;
									?>
								</div>
							</div>
						<?php endif; ?>
					</li>
					<?php
				}
			} else {
				echo '<li class="woocommerce-notice woocommerce-notice--info woocommerce-info">' . apply_filters('woocommerce_no_available_payment_methods_message', esc_html__('Sorry, it seems that there are no available payment methods. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce')) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</ul>
	<?php endif; ?>

	<div class="form-row place-order">
		<noscript>
			<?php esc_html_e('Since your browser does not support JavaScript, or it is disabled, please ensure you click the <em>Update Totals</em> button before placing your order. You may be charged more than the amount stated above if you fail to do so.', 'woocommerce'); ?>
			<br /><button type="submit"
				class="button alt<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>"
				name="woocommerce_checkout_update_totals"
				value="<?php esc_attr_e('Update totals', 'woocommerce'); ?>"><?php esc_html_e('Update totals', 'woocommerce'); ?></button>
		</noscript>

		<?php wc_get_template('checkout/terms.php'); ?>

		<?php do_action('woocommerce_review_order_before_submit'); ?>

		<?php
		$default_button_text = apply_filters('woocommerce_order_button_text', __('Place order', 'woocommerce'));
		$first_gateway = !empty($available_gateways) ? reset($available_gateways) : null;
		$initial_button_text = $first_gateway && $first_gateway->order_button_text ? $first_gateway->order_button_text : $default_button_text;
		?>

		<div class="bw-mobile-total-row" aria-live="polite">
			<span class="bw-mobile-total-label"><?php esc_html_e('Total', 'woocommerce'); ?></span>
			<span class="bw-mobile-total-amount">—</span>
		</div>

		<div id="bw-google-pay-button-wrapper" style="display: none;">
			<div id="bw-google-pay-button"></div>
		</div>

		<div id="bw-apple-pay-button-wrapper" style="display: none;">
			<div id="bw-apple-pay-button"></div>
		</div>

		<button type="submit"
			class="button alt bw-place-order-btn<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>"
			name="woocommerce_checkout_place_order" id="place_order"
			value="<?php echo esc_attr($initial_button_text); ?>"
			data-value="<?php echo esc_attr($initial_button_text); ?>"
			data-default-text="<?php echo esc_attr($default_button_text); ?>">
			<span class="bw-place-order-btn__text"><?php echo esc_html($initial_button_text); ?></span>
		</button>

		<?php do_action('woocommerce_review_order_after_submit'); ?>

		<?php wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce'); ?>
	</div>
</div>
<?php
if (!wp_doing_ajax()) {
	do_action('woocommerce_review_order_after_payment');
}
