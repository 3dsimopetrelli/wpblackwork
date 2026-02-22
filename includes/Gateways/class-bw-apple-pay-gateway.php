<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apple Pay gateway via Stripe Payment Intents API.
 */
class BW_Apple_Pay_Gateway extends BW_Abstract_Stripe_Gateway {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'bw_apple_pay';
		$this->icon               = '';
		$this->has_fields         = true;
		$current_supports         = is_array( $this->supports ) ? $this->supports : array();
		$this->supports           = array_values( array_unique( array_merge( array( 'products', 'refunds' ), $current_supports ) ) );
		$this->method_title       = 'Apple Pay (BlackWork)';
		$this->method_description = 'Apple Pay integration via Stripe Payment Intents for BlackWork checkout.';
		$this->log_source         = 'bw_apple_pay';

		$this->init_form_fields();
		$this->init_settings();

		$this->title             = 'Apple Pay';
		$this->description       = '';
		$this->order_button_text = __( 'Place order with Apple Pay', 'bw' );

		$this->test_mode            = false; // Apple Pay tab is live-only in this implementation.
		$this->enabled              = get_option( 'bw_apple_pay_enabled', '0' ) === '1' ? 'yes' : 'no';
		$this->secret_key           = $this->resolve_live_secret_key();
		$this->publishable_key      = $this->resolve_live_publishable_key();
		$this->statement_descriptor = (string) get_option( 'bw_apple_pay_statement_descriptor', '' );
		$this->webhook_secret       = (string) get_option( 'bw_apple_pay_webhook_secret', '' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_api_' . $this->id, array( $this, 'handle_webhook' ) );
	}

	/**
	 * Init gateway settings fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'bw' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Apple Pay (BlackWork)', 'bw' ),
				'default' => 'no',
			),
		);
	}

	/**
	 * Render checkout fields container.
	 *
	 * @return void
	 */
	public function payment_fields() {
		$bw_enabled   = get_option( 'bw_apple_pay_enabled', '0' ) === '1';
		$wc_settings  = get_option( 'woocommerce_' . $this->id . '_settings', array() );
		$wc_enabled   = isset( $wc_settings['enabled'] ) && 'yes' === $wc_settings['enabled'];
		$apple_pk     = (string) get_option( 'bw_apple_pay_publishable_key', '' );
		$fallback_pk  = (string) get_option( 'bw_google_pay_publishable_key', '' );
		$pk_available = '' !== $apple_pk || '' !== $fallback_pk;
		?>
		<div id="bw-apple-pay-accordion-container">
			<div id="bw-apple-pay-accordion-placeholder">
				<?php if ( ! $bw_enabled || ! $wc_enabled ) : ?>
					<p><?php esc_html_e( 'Activate Apple Pay (BlackWork) in WooCommerce > Settings > Payments.', 'bw' ); ?></p>
				<?php elseif ( ! $pk_available ) : ?>
					<p><?php esc_html_e( 'Apple Pay is not configured. Add a live publishable key in BlackWork > Checkout > Apple Pay.', 'bw' ); ?></p>
				<?php else : ?>
					<p><?php esc_html_e( 'Initializing Apple Pay…', 'bw' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Process payment.
	 *
	 * @param int $order_id WooCommerce order id.
	 * @return array|void
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wc_add_notice( __( 'Order not found. Please try again.', 'bw' ), 'error' );
			return;
		}

		if ( $order->get_payment_method() && $this->id !== $order->get_payment_method() ) {
			wc_add_notice( __( 'Invalid payment method for this order.', 'bw' ), 'error' );
			return;
		}

		$payment_method_id = isset( $_POST['bw_apple_pay_method_id'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			? sanitize_text_field( wp_unslash( $_POST['bw_apple_pay_method_id'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			: '';

		if ( '' === $payment_method_id ) {
			wc_add_notice( __( 'Apple Pay session missing. Please try again.', 'bw' ), 'error' );
			return;
		}

		if ( empty( $this->secret_key ) ) {
			wc_add_notice( __( 'Apple Pay gateway is not configured correctly. Please contact support.', 'bw' ), 'error' );
			$this->log_safe( 'error', 'Apple Pay: missing secret key.', array( 'source' => $this->get_log_source() ) );
			return;
		}

		$intent_data = $this->create_payment_intent_request( $order, $payment_method_id );
		if ( is_wp_error( $intent_data ) ) {
			wc_add_notice( $intent_data->get_error_message(), 'error' );
			$this->log_safe(
				'error',
				sprintf( 'Apple Pay PI create failed for order %d: %s', (int) $order_id, $intent_data->get_error_message() ),
				array( 'source' => $this->get_log_source(), 'order_id' => $order_id )
			);
			return;
		}

		$pi_id  = isset( $intent_data['id'] ) ? (string) $intent_data['id'] : '';
		$status = isset( $intent_data['status'] ) ? (string) $intent_data['status'] : '';

		if ( '' !== $pi_id ) {
			$this->persist_payment_intent_data( $order, $pi_id, $payment_method_id );
		}

		switch ( $status ) {
			case 'succeeded':
			case 'processing':
				if ( ! $order->is_paid() ) {
					$order->update_status( 'on-hold', sprintf( __( 'Apple Pay return received. Awaiting Stripe webhook confirmation. PaymentIntent: %s', 'bw' ), $pi_id ) );
				}
				$order->add_order_note( sprintf( __( 'Apple Pay return received. Awaiting Stripe webhook confirmation. PaymentIntent: %s', 'bw' ), $pi_id ) );
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);

			case 'requires_action':
				$auth_url = isset( $intent_data['next_action']['redirect_to_url']['url'] ) ? (string) $intent_data['next_action']['redirect_to_url']['url'] : '';
				if ( '' !== $auth_url ) {
					if ( ! $order->is_paid() ) {
						$order->update_status( 'pending', sprintf( __( 'Apple Pay authentication required. PaymentIntent: %s', 'bw' ), $pi_id ) );
					}
					return array(
						'result'   => 'success',
						'redirect' => esc_url_raw( $auth_url ),
					);
				}
				wc_add_notice( __( 'Additional authentication is required. Please try again.', 'bw' ), 'error' );
				return;

			case 'requires_payment_method':
			case 'canceled':
				$err_msg = __( 'Apple Pay payment was not completed. Please try again.', 'bw' );
				if ( isset( $intent_data['last_payment_error']['message'] ) ) {
					$err_msg = sanitize_text_field( (string) $intent_data['last_payment_error']['message'] );
				}
				wc_add_notice( $err_msg, 'error' );
				$this->log_safe(
					'warning',
					sprintf( 'Apple Pay: PaymentIntent status "%s" [PI: %s]', $status, $pi_id ),
					array( 'source' => $this->get_log_source(), 'order_id' => $order_id )
				);
				return;

			default:
				wc_add_notice( __( 'Apple Pay payment failed. Please try again.', 'bw' ), 'error' );
				$this->log_safe(
					'error',
					sprintf( 'Apple Pay: unexpected PaymentIntent status "%s" [PI: %s]', $status, $pi_id ),
					array( 'source' => $this->get_log_source(), 'order_id' => $order_id )
				);
				return;
		}
	}

	/**
	 * Return Apple Pay icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return '<span class="bw-payment-method__applepay-logo" role="img" aria-label="' . esc_attr__( 'Apple Pay', 'bw' ) . '"></span>';
	}

	/** @inheritDoc */
	protected function get_live_secret_option_name() {
		return 'bw_apple_pay_secret_key';
	}

	/** @inheritDoc */
	protected function get_test_secret_option_name() {
		return 'bw_apple_pay_test_secret_key';
	}

	/** @inheritDoc */
	protected function get_live_webhook_secret_option_name() {
		return 'bw_apple_pay_webhook_secret';
	}

	/** @inheritDoc */
	protected function get_test_webhook_secret_option_name() {
		return 'bw_apple_pay_test_webhook_secret';
	}

	/** @inheritDoc */
	protected function get_order_meta_keys() {
		return array(
			'pi_id'            => '_bw_apple_pay_pi_id',
			'mode'             => '_bw_apple_pay_mode',
			'pm_id'            => '_bw_apple_pay_pm_id',
			'created_at'       => '_bw_apple_pay_created_at',
			'processed_events' => '_bw_apple_pay_processed_events',
			'refund_ids'       => '_bw_apple_pay_refund_ids',
			'last_refund_id'   => '_bw_apple_pay_last_refund_id',
		);
	}

	/**
	 * Resolve Apple Pay live secret key with fallback to global live key.
	 *
	 * @return string
	 */
	private function resolve_live_secret_key() {
		$apple_secret = (string) get_option( 'bw_apple_pay_secret_key', '' );
		if ( '' !== $apple_secret ) {
			return $apple_secret;
		}
		return (string) get_option( 'bw_google_pay_secret_key', '' );
	}

	/**
	 * Resolve Apple Pay live publishable key with fallback to global live key.
	 *
	 * @return string
	 */
	private function resolve_live_publishable_key() {
		$apple_pk = (string) get_option( 'bw_apple_pay_publishable_key', '' );
		if ( '' !== $apple_pk ) {
			return $apple_pk;
		}
		return (string) get_option( 'bw_google_pay_publishable_key', '' );
	}
}
