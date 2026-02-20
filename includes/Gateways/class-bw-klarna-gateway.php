<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klarna gateway via Stripe PaymentIntents.
 */
class BW_Klarna_Gateway extends BW_Abstract_Stripe_Gateway {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'bw_klarna';
		$this->icon               = '';
		$this->has_fields         = false;
		$current_supports         = is_array( $this->supports ) ? $this->supports : array();
		$this->supports           = array_values( array_unique( array_merge( array( 'products', 'refunds' ), $current_supports ) ) );
		$this->method_title       = 'Klarna (BlackWork)';
		$this->method_description = 'Klarna Flexible Payments via Stripe Payment Intents.';
		$this->log_source         = 'bw_klarna';

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = 'Klarna - Flexible Payments';
		$this->description = __( 'Pay in installments with Klarna.', 'bw' );

		// Klarna is configured in live mode from BlackWork > Checkout > Klarna Pay tab.
		$this->test_mode             = false;
		$this->enabled               = get_option( 'bw_klarna_enabled', '0' ) === '1' ? 'yes' : 'no';
		$this->secret_key            = (string) get_option( 'bw_klarna_secret_key', '' );
		$this->publishable_key       = (string) get_option( 'bw_klarna_publishable_key', '' );
		$this->statement_descriptor  = (string) get_option( 'bw_klarna_statement_descriptor', '' );
		$this->webhook_secret        = (string) get_option( 'bw_klarna_webhook_secret', '' );

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
				'label'   => __( 'Enable Klarna (BlackWork)', 'bw' ),
				'default' => 'no',
			),
		);
	}

	/**
	 * Render checkout info.
	 */
	public function payment_fields() {
		$bw_enabled  = get_option( 'bw_klarna_enabled', '0' ) === '1';
		$wc_settings = get_option( 'woocommerce_' . $this->id . '_settings', array() );
		$wc_enabled  = isset( $wc_settings['enabled'] ) && 'yes' === $wc_settings['enabled'];
		?>
		<div id="bw-klarna-accordion-container">
			<?php if ( ! $bw_enabled || ! $wc_enabled ) : ?>
				<p><?php esc_html_e( 'Activate Klarna (BlackWork) in WooCommerce > Settings > Payments.', 'bw' ); ?></p>
			<?php else : ?>
				<p><?php esc_html_e( 'After clicking "Place order", you will be redirected to Klarna to complete your purchase securely.', 'bw' ); ?></p>
			<?php endif; ?>
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

		if ( empty( $this->secret_key ) ) {
			wc_add_notice( __( 'Klarna gateway is not configured correctly. Please contact support.', 'bw' ), 'error' );
			$this->log_safe( 'error', 'Klarna: missing secret key.', array( 'source' => $this->get_log_source() ) );
			return;
		}

		$billing_email = sanitize_email( (string) $order->get_billing_email() );
		if ( '' === $billing_email ) {
			wc_add_notice( __( 'Billing email is required for Klarna.', 'bw' ), 'error' );
			return;
		}

		$billing_name = trim( $order->get_formatted_billing_full_name() );
		if ( '' === $billing_name ) {
			$billing_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		}

		$extra_body = array(
			'payment_method_types[]'                           => 'klarna',
			'payment_method_data[type]'                        => 'klarna',
			'payment_method_data[billing_details][email]'      => $billing_email,
			'payment_method_data[billing_details][name]'       => sanitize_text_field( $billing_name ),
			'payment_method_data[billing_details][phone]'      => sanitize_text_field( (string) $order->get_billing_phone() ),
			'payment_method_data[billing_details][address][line1]'       => sanitize_text_field( (string) $order->get_billing_address_1() ),
			'payment_method_data[billing_details][address][line2]'       => sanitize_text_field( (string) $order->get_billing_address_2() ),
			'payment_method_data[billing_details][address][city]'        => sanitize_text_field( (string) $order->get_billing_city() ),
			'payment_method_data[billing_details][address][state]'       => sanitize_text_field( (string) $order->get_billing_state() ),
			'payment_method_data[billing_details][address][postal_code]' => sanitize_text_field( (string) $order->get_billing_postcode() ),
			'payment_method_data[billing_details][address][country]'     => strtoupper( sanitize_text_field( (string) $order->get_billing_country() ) ),
		);

		$intent_data = $this->create_payment_intent_request( $order, '', $extra_body );
		if ( is_wp_error( $intent_data ) ) {
			wc_add_notice( $intent_data->get_error_message(), 'error' );
			$this->log_safe(
				'error',
				sprintf( 'Klarna PI create failed for order %d: %s', (int) $order_id, $intent_data->get_error_message() ),
				array( 'source' => $this->get_log_source(), 'order_id' => $order_id )
			);
			return;
		}

		$pi_id  = isset( $intent_data['id'] ) ? (string) $intent_data['id'] : '';
		$status = isset( $intent_data['status'] ) ? (string) $intent_data['status'] : '';

		if ( '' !== $pi_id ) {
			$this->persist_payment_intent_data( $order, $pi_id );
		}

		switch ( $status ) {
			case 'succeeded':
				$order->payment_complete( $pi_id );
				$order->add_order_note( sprintf( __( 'Klarna payment confirmed via Stripe. PaymentIntent: %s', 'bw' ), $pi_id ) );
				WC()->cart->empty_cart();
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);

			case 'requires_action':
				$auth_url = isset( $intent_data['next_action']['redirect_to_url']['url'] ) ? $intent_data['next_action']['redirect_to_url']['url'] : '';
				if ( ! empty( $auth_url ) ) {
					$order->update_status( 'pending', sprintf( __( 'Klarna authorization required. PaymentIntent: %s', 'bw' ), $pi_id ) );
					return array(
						'result'   => 'success',
						'redirect' => esc_url_raw( $auth_url ),
					);
				}
				wc_add_notice( __( 'Additional Klarna authentication is required. Please try again.', 'bw' ), 'error' );
				return;

			case 'processing':
				$order->update_status( 'on-hold', sprintf( __( 'Klarna payment is processing. PaymentIntent: %s', 'bw' ), $pi_id ) );
				WC()->cart->empty_cart();
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);

			case 'requires_payment_method':
			case 'canceled':
				$err_msg = __( 'Klarna payment was not completed. Please try again.', 'bw' );
				if ( isset( $intent_data['last_payment_error']['message'] ) ) {
					$err_msg = sanitize_text_field( (string) $intent_data['last_payment_error']['message'] );
				}
				wc_add_notice( $err_msg, 'error' );
				$this->log_safe(
					'warning',
					sprintf( 'Klarna: PaymentIntent status "%s" [PI: %s]', $status, $pi_id ),
					array( 'source' => $this->get_log_source(), 'order_id' => $order_id )
				);
				return;

			default:
				wc_add_notice( __( 'Klarna payment failed. Please try again.', 'bw' ), 'error' );
				$this->log_safe(
					'error',
					sprintf( 'Klarna: unexpected PaymentIntent status "%s" [PI: %s]', $status, $pi_id ),
					array( 'source' => $this->get_log_source(), 'order_id' => $order_id )
				);
				return;
		}
	}

	/**
	 * Return Klarna icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return '<span class="bw-payment-method__klarna-logo" aria-label="Klarna">Klarna</span>';
	}

	/** @inheritDoc */
	protected function get_live_secret_option_name() {
		return 'bw_klarna_secret_key';
	}

	/** @inheritDoc */
	protected function get_test_secret_option_name() {
		return 'bw_klarna_test_secret_key';
	}

	/** @inheritDoc */
	protected function get_live_webhook_secret_option_name() {
		return 'bw_klarna_webhook_secret';
	}

	/** @inheritDoc */
	protected function get_test_webhook_secret_option_name() {
		return 'bw_klarna_test_webhook_secret';
	}

	/** @inheritDoc */
	protected function get_order_meta_keys() {
		return array(
			'pi_id'            => '_bw_klarna_pi_id',
			'mode'             => '_bw_klarna_mode',
			'pm_id'            => '_bw_klarna_pm_id',
			'created_at'       => '_bw_klarna_created_at',
			'processed_events' => '_bw_klarna_processed_events',
			'refund_ids'       => '_bw_klarna_refund_ids',
			'last_refund_id'   => '_bw_klarna_last_refund_id',
		);
	}
}
