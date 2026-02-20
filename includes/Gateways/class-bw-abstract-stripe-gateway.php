<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base abstract class for Stripe-powered BW custom gateways.
 */
abstract class BW_Abstract_Stripe_Gateway extends WC_Payment_Gateway {

	/** @var bool */
	protected $test_mode = false;

	/** @var string */
	protected $secret_key = '';

	/** @var string */
	protected $publishable_key = '';

	/** @var string */
	protected $statement_descriptor = '';

	/** @var string */
	protected $webhook_secret = '';

	/** @var string */
	protected $log_source = 'bw_stripe_gateway';

	/**
	 * Get option name for live secret key.
	 *
	 * @return string
	 */
	abstract protected function get_live_secret_option_name();

	/**
	 * Get option name for test secret key.
	 *
	 * @return string
	 */
	abstract protected function get_test_secret_option_name();

	/**
	 * Get option name for live webhook secret.
	 *
	 * @return string
	 */
	abstract protected function get_live_webhook_secret_option_name();

	/**
	 * Get option name for test webhook secret.
	 *
	 * @return string
	 */
	abstract protected function get_test_webhook_secret_option_name();

	/**
	 * Return order meta key map used by this gateway.
	 *
	 * @return array
	 */
	abstract protected function get_order_meta_keys();

	/**
	 * Process WooCommerce refund and create a real Stripe refund.
	 *
	 * @param int        $order_id WooCommerce order ID.
	 * @param float|null $amount   Refund amount in store currency (null = full).
	 * @param string     $reason   Refund reason from WooCommerce.
	 * @return bool|WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_Error( 'bw_gpay_refund_order_missing', __( 'Order not found.', 'bw' ) );
		}

		if ( $this->id !== $order->get_payment_method() ) {
			return new WP_Error( 'bw_gpay_refund_invalid_gateway', __( 'Refund allowed only for this payment method.', 'bw' ) );
		}

		$pi_id = (string) $order->get_meta( $this->get_meta_key( 'pi_id' ), true );
		if ( empty( $pi_id ) ) {
			return new WP_Error( 'bw_gpay_refund_pi_missing', __( 'Missing Stripe PaymentIntent ID for this order.', 'bw' ) );
		}

		$mode       = $this->get_mode_for_order( $order );
		$secret_key = $this->get_secret_key_for_mode( $mode );
		if ( empty( $secret_key ) ) {
			return new WP_Error( 'bw_gpay_refund_secret_missing', __( 'Stripe secret key is missing for the order mode.', 'bw' ) );
		}

		$amount_cents = null;
		if ( null !== $amount && '' !== $amount ) {
			$amount_cents = (int) round( (float) $amount * 100 );
			if ( $amount_cents <= 0 ) {
				return new WP_Error( 'bw_gpay_refund_invalid_amount', __( 'Invalid refund amount.', 'bw' ) );
			}

			$order_total_cents = (int) round( (float) $order->get_total() * 100 );
			if ( $amount_cents > $order_total_cents ) {
				return new WP_Error( 'bw_gpay_refund_amount_too_high', __( 'Refund amount exceeds order total.', 'bw' ) );
			}
		}

		$clean_reason    = sanitize_text_field( (string) $reason );
		$reason_hash     = substr( md5( $clean_reason ), 0, 12 );
		$idempotency_amt = null === $amount_cents ? 'full' : (string) $amount_cents;
		$idem_pi         = preg_replace( '/[^a-zA-Z0-9_]/', '', $pi_id );
		$idempotency_key = sprintf( 'bw_gpay_refund_%d_%s_%s_%s', (int) $order_id, $idem_pi, $idempotency_amt, $reason_hash );

		$refund_body = array(
			'payment_intent'        => $pi_id,
			'metadata[wc_order_id]' => (string) $order_id,
			'metadata[bw_gateway]'  => $this->id,
			'metadata[mode]'        => $mode,
		);

		if ( null !== $amount_cents ) {
			$refund_body['amount'] = $amount_cents;
		}

		if ( '' !== $clean_reason ) {
			$refund_body['metadata[wc_refund_reason]'] = $clean_reason;
		}

		$allowed_refund_reasons = array( 'duplicate', 'fraudulent', 'requested_by_customer' );
		if ( in_array( $clean_reason, $allowed_refund_reasons, true ) ) {
			$refund_body['reason'] = $clean_reason;
		}

		$result = BW_Stripe_Api_Client::request( 'POST', '/v1/refunds', $secret_key, $refund_body, $idempotency_key );

		if ( ! $result['ok'] ) {
			$retry_with_charge = false;
			if ( ! empty( $result['error'] ) ) {
				$retry_with_charge = false !== strpos( strtolower( $result['error'] ), 'payment_intent' );
			}

			if ( $retry_with_charge ) {
				$pi_result = BW_Stripe_Api_Client::request( 'GET', '/v1/payment_intents/' . rawurlencode( $pi_id ), $secret_key );
				$latest_charge = ( $pi_result['ok'] && ! empty( $pi_result['data']['latest_charge'] ) )
					? sanitize_text_field( (string) $pi_result['data']['latest_charge'] )
					: '';

				if ( '' !== $latest_charge ) {
					$retry_body = $refund_body;
					unset( $retry_body['payment_intent'] );
					$retry_body['charge'] = $latest_charge;

					$result = BW_Stripe_Api_Client::request( 'POST', '/v1/refunds', $secret_key, $retry_body, $idempotency_key );
				}
			}
		}

		if ( ! $result['ok'] || empty( $result['data']['id'] ) ) {
			$user_message = __( 'Stripe refund failed. Please verify Stripe settings and try again.', 'bw' );
			if ( ! empty( $result['error'] ) ) {
				$user_message = $result['error'];
			}

			$this->log_safe(
				'warning',
				sprintf( 'Stripe refund failed for order %d (PI %s).', (int) $order_id, $pi_id ),
				array( 'source' => $this->get_log_source() )
			);

			return new WP_Error( 'bw_gpay_refund_failed', $user_message );
		}

		$refund_id = sanitize_text_field( (string) $result['data']['id'] );
		if ( '' === $refund_id ) {
			return new WP_Error( 'bw_gpay_refund_invalid_response', __( 'Invalid Stripe refund response.', 'bw' ) );
		}

		$existing_refunds = $order->get_meta( $this->get_meta_key( 'refund_ids' ), true );
		if ( ! is_array( $existing_refunds ) ) {
			$existing_refunds = array();
		}
		if ( ! in_array( $refund_id, $existing_refunds, true ) ) {
			$existing_refunds[] = $refund_id;
		}

		$order->update_meta_data( $this->get_meta_key( 'refund_ids' ), $existing_refunds );
		$order->update_meta_data( $this->get_meta_key( 'last_refund_id' ), $refund_id );
		$order->save();

		$refund_total = null === $amount_cents ? (float) $order->get_total() : ( $amount_cents / 100 );
		$formatted_refund = wc_price(
			$refund_total,
			array(
				'currency' => $order->get_currency(),
			)
		);

		$note = sprintf(
			/* translators: 1: Stripe refund id, 2: formatted amount */
			__( 'Stripe refund completed. Refund ID: %1$s — Amount: %2$s', 'bw' ),
			$refund_id,
			wp_strip_all_tags( $formatted_refund )
		);
		if ( '' !== $clean_reason ) {
			$note .= ' — ' . sprintf( __( 'Reason: %s', 'bw' ), $clean_reason );
		}
		$order->add_order_note( $note );

		return true;
	}

	/**
	 * Handle Stripe webhooks posted to /?wc-api={gateway_id}
	 *
	 * @return void
	 */
	public function handle_webhook() {
		$payload    = file_get_contents( 'php://input' );
		$sig_header = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ) : '';
		$secret     = ! empty( $this->webhook_secret ) ? $this->webhook_secret : $this->get_webhook_secret_for_mode( $this->get_gateway_mode() );

		if ( empty( $secret ) ) {
			$this->log_safe( 'warning', 'Stripe webhook secret missing.', array( 'source' => $this->get_log_source() ) );
			status_header( 400 );
			exit( 'No webhook secret configured.' );
		}

		if ( ! $this->verify_stripe_signature( $payload, $sig_header, $secret ) ) {
			$this->log_safe( 'error', 'Invalid Stripe webhook signature.', array( 'source' => $this->get_log_source() ) );
			status_header( 400 );
			exit( 'Invalid signature.' );
		}

		$event = json_decode( $payload, true );
		if ( ! is_array( $event ) ) {
			status_header( 400 );
			exit( 'Invalid payload.' );
		}

		$event_id = isset( $event['id'] ) ? sanitize_text_field( (string) $event['id'] ) : '';
		$type     = isset( $event['type'] ) ? (string) $event['type'] : '';
		$pi       = isset( $event['data']['object'] ) && is_array( $event['data']['object'] ) ? $event['data']['object'] : array();
		$pi_id    = isset( $pi['id'] ) ? sanitize_text_field( (string) $pi['id'] ) : '';

		$order_id = 0;
		if ( isset( $pi['metadata']['wc_order_id'] ) ) {
			$order_id = (int) $pi['metadata']['wc_order_id'];
		} elseif ( isset( $pi['metadata']['order_id'] ) ) {
			$order_id = (int) $pi['metadata']['order_id'];
		}

		if ( ! $order_id ) {
			$this->respond_ok();
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			$this->respond_ok();
		}

		if ( $this->id !== $order->get_payment_method() ) {
			$this->respond_ok();
		}

		if ( isset( $pi['metadata']['bw_gateway'] ) && $this->id !== $pi['metadata']['bw_gateway'] ) {
			$this->respond_ok();
		}

		$saved_pi_id = (string) $order->get_meta( $this->get_meta_key( 'pi_id' ), true );
		if ( ! empty( $saved_pi_id ) && ! empty( $pi_id ) && $saved_pi_id !== $pi_id ) {
			$this->log_safe(
				'warning',
				sprintf( 'Webhook PI mismatch for order %d (saved=%s incoming=%s).', $order_id, $saved_pi_id, $pi_id ),
				array( 'source' => $this->get_log_source(), 'event_id' => $event_id )
			);
			$this->respond_ok();
		}

		if ( ! empty( $event_id ) && $this->is_event_processed( $order, $event_id ) ) {
			$this->respond_ok();
		}

		switch ( $type ) {
			case 'payment_intent.succeeded':
				if ( ! $order->is_paid() ) {
					$order->payment_complete( $pi_id );
					$order->add_order_note( sprintf( __( 'Payment confirmed via Stripe Webhook. PaymentIntent: %s', 'bw' ), $pi_id ) );
				}
				break;

			case 'payment_intent.payment_failed':
				if ( ! $order->is_paid() && 'failed' !== $order->get_status() ) {
					$err_msg = isset( $pi['last_payment_error']['message'] ) ? sanitize_text_field( (string) $pi['last_payment_error']['message'] ) : 'unknown';
					$order->update_status( 'failed', sprintf( __( 'Payment failed via Stripe Webhook. PI: %1$s — %2$s', 'bw' ), $pi_id, $err_msg ) );
				}
				break;
		}

		if ( ! empty( $event_id ) ) {
			$this->mark_event_processed( $order, $event_id );
		}

		status_header( 200 );
		exit( 'OK' );
	}

	/**
	 * Persist PaymentIntent identifiers and transaction id on the order.
	 *
	 * @param WC_Order $order             WooCommerce order.
	 * @param string   $pi_id             Stripe PI id.
	 * @param string   $payment_method_id Stripe PM id.
	 * @return void
	 */
	protected function persist_payment_intent_data( WC_Order $order, $pi_id, $payment_method_id = '' ) {
		$pi_id = sanitize_text_field( (string) $pi_id );
		if ( '' === $pi_id ) {
			return;
		}

		$order->update_meta_data( $this->get_meta_key( 'pi_id' ), $pi_id );
		$order->update_meta_data( $this->get_meta_key( 'mode' ), $this->get_gateway_mode() );
		$order->update_meta_data( $this->get_meta_key( 'created_at' ), time() );

		if ( '' !== $payment_method_id ) {
			$order->update_meta_data( $this->get_meta_key( 'pm_id' ), sanitize_text_field( (string) $payment_method_id ) );
		}

		if ( $pi_id !== (string) $order->get_transaction_id() ) {
			$order->set_transaction_id( $pi_id );
		}

		$order->save();
	}

	/**
	 * Create Stripe PaymentIntent request and normalize errors.
	 *
	 * @param WC_Order $order             WooCommerce order.
	 * @param string   $payment_method_id Stripe payment method id.
	 * @param array    $extra_body        Extra body params.
	 * @return array|WP_Error
	 */
	protected function create_payment_intent_request( WC_Order $order, $payment_method_id, $extra_body = array() ) {
		$amount = (int) round( $order->get_total() * 100 );
		if ( $amount <= 0 ) {
			return new WP_Error( 'bw_gpay_zero_amount', __( 'This order does not require a card payment.', 'bw' ) );
		}

		$body = array(
			'amount'                 => $amount,
			'currency'               => strtolower( get_woocommerce_currency() ),
			'payment_method_types[]' => 'card',
			'confirm'                => 'true',
			'return_url'             => $this->get_return_url( $order ),
			'metadata[wc_order_id]'  => $order->get_id(),
			'metadata[order_id]'     => $order->get_id(),
			'metadata[bw_gateway]'   => $this->id,
			'metadata[site_url]'     => home_url(),
			'metadata[mode]'         => $this->get_gateway_mode(),
		);

		if ( ! empty( $payment_method_id ) ) {
			$body['payment_method'] = $payment_method_id;
		}

		if ( ! empty( $this->statement_descriptor ) ) {
			$body['statement_descriptor'] = substr( sanitize_text_field( $this->statement_descriptor ), 0, 22 );
		}

		if ( ! empty( $extra_body ) && is_array( $extra_body ) ) {
			$body = array_merge( $body, $extra_body );
		}

		$idempotency_source = ! empty( $payment_method_id ) ? (string) $payment_method_id : 'no_pm';
		$idempotency_key    = 'bw_' . sanitize_key( (string) $this->id ) . '_' . $order->get_id() . '_' . md5( $idempotency_source );
		$result = BW_Stripe_Api_Client::request( 'POST', '/v1/payment_intents', $this->secret_key, $body, $idempotency_key );

		if ( ! $result['ok'] ) {
			if ( 0 === (int) $result['status'] ) {
				return new WP_Error( 'bw_gpay_connection_error', __( 'Connection error while processing payment.', 'bw' ) );
			}

			if ( ! empty( $result['error'] ) ) {
				return new WP_Error( 'bw_gpay_stripe_error', $result['error'] );
			}

			return new WP_Error( 'bw_gpay_stripe_http_error', __( 'Stripe rejected the payment request. Please try again.', 'bw' ) );
		}

		if ( empty( $result['data'] ) || ! is_array( $result['data'] ) ) {
			return new WP_Error( 'bw_gpay_stripe_invalid', __( 'Invalid response from Stripe.', 'bw' ) );
		}

		return $result['data'];
	}

	/**
	 * Check whether a Stripe event was already processed for this order.
	 *
	 * @param WC_Order $order    WooCommerce order.
	 * @param string   $event_id Stripe event id.
	 * @return bool
	 */
	protected function is_event_processed( WC_Order $order, $event_id ) {
		$processed = $order->get_meta( $this->get_meta_key( 'processed_events' ), true );
		if ( ! is_array( $processed ) ) {
			return false;
		}
		return in_array( $event_id, $processed, true );
	}

	/**
	 * Mark Stripe event as processed and keep rolling history.
	 *
	 * @param WC_Order $order    WooCommerce order.
	 * @param string   $event_id Stripe event id.
	 * @return void
	 */
	protected function mark_event_processed( WC_Order $order, $event_id ) {
		$processed = $order->get_meta( $this->get_meta_key( 'processed_events' ), true );
		if ( ! is_array( $processed ) ) {
			$processed = array();
		}

		$processed[] = $event_id;
		$processed   = array_values( array_unique( $processed ) );
		if ( count( $processed ) > 20 ) {
			$processed = array_slice( $processed, -20 );
		}

		$order->update_meta_data( $this->get_meta_key( 'processed_events' ), $processed );
		$order->save();
	}

	/**
	 * Verify Stripe-Signature header.
	 *
	 * @param string $payload    Raw request body.
	 * @param string $sig_header Stripe-Signature header.
	 * @param string $secret     Webhook signing secret.
	 * @return bool
	 */
	protected function verify_stripe_signature( $payload, $sig_header, $secret ) {
		if ( empty( $payload ) || empty( $sig_header ) || empty( $secret ) ) {
			return false;
		}

		$timestamp  = null;
		$signatures = array();

		foreach ( explode( ',', $sig_header ) as $part ) {
			$part = trim( $part );
			if ( 0 === strpos( $part, 't=' ) ) {
				$timestamp = substr( $part, 2 );
			} elseif ( 0 === strpos( $part, 'v1=' ) ) {
				$signatures[] = substr( $part, 3 );
			}
		}

		if ( null === $timestamp || empty( $signatures ) ) {
			return false;
		}

		if ( abs( time() - (int) $timestamp ) > 300 ) {
			return false;
		}

		$expected = hash_hmac( 'sha256', $timestamp . '.' . $payload, $secret );
		foreach ( $signatures as $signature ) {
			if ( hash_equals( $expected, $signature ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return HTTP 200 quickly for ignorable events.
	 *
	 * @param string $message Response message.
	 * @return void
	 */
	protected function respond_ok( $message = 'OK' ) {
		status_header( 200 );
		exit( $message );
	}

	/**
	 * Resolve a meta key by logical name.
	 *
	 * @param string $logical_key Logical key.
	 * @return string
	 */
	protected function get_meta_key( $logical_key ) {
		$keys = $this->get_order_meta_keys();
		return isset( $keys[ $logical_key ] ) ? $keys[ $logical_key ] : '';
	}

	/**
	 * Resolve Stripe mode for an order with backward compatibility.
	 *
	 * @param WC_Order $order WooCommerce order.
	 * @return string
	 */
	protected function get_mode_for_order( WC_Order $order ) {
		$mode = (string) $order->get_meta( $this->get_meta_key( 'mode' ), true );
		if ( 'test' !== $mode && 'live' !== $mode ) {
			$mode = $this->get_gateway_mode();
		}
		return $mode;
	}

	/**
	 * Current gateway mode.
	 *
	 * @return string
	 */
	protected function get_gateway_mode() {
		return $this->test_mode ? 'test' : 'live';
	}

	/**
	 * Resolve secret key by mode.
	 *
	 * @param string $mode Stripe mode.
	 * @return string
	 */
	protected function get_secret_key_for_mode( $mode ) {
		$option = ( 'test' === $mode ) ? $this->get_test_secret_option_name() : $this->get_live_secret_option_name();
		return (string) get_option( $option, '' );
	}

	/**
	 * Resolve webhook secret by mode.
	 *
	 * @param string $mode Stripe mode.
	 * @return string
	 */
	protected function get_webhook_secret_for_mode( $mode ) {
		$option = ( 'test' === $mode ) ? $this->get_test_webhook_secret_option_name() : $this->get_live_webhook_secret_option_name();
		return (string) get_option( $option, '' );
	}

	/**
	 * Get log source string.
	 *
	 * @return string
	 */
	protected function get_log_source() {
		if ( ! empty( $this->log_source ) ) {
			return (string) $this->log_source;
		}
		return (string) $this->id;
	}

	/**
	 * Safe logging helper.
	 *
	 * @param string $level   Level.
	 * @param string $message Message.
	 * @param array  $context Context.
	 * @return void
	 */
	protected function log_safe( $level, $message, $context = array() ) {
		BW_Stripe_Safe_Logger::log( $level, $message, $context );
	}
}
