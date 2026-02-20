<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BW_Google_Pay_Gateway
 *
 * Custom Google Pay gateway via Stripe Payment Intents API.
 * Integrates with the BlackWork custom checkout accordion.
 */
class BW_Google_Pay_Gateway extends WC_Payment_Gateway {

	/** @var bool */
	private $test_mode;

	/** @var string */
	private $secret_key;

	/** @var string */
	private $publishable_key;

	/** @var string */
	private $statement_descriptor;

	/** @var string */
	private $webhook_secret;

	/** @var string */
	private $log_source = 'bw-google-pay';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id                 = 'bw_google_pay';
		$this->icon               = '';
		$this->has_fields         = true;
		$this->method_title       = 'Google Pay (BlackWork)';
		$this->method_description = 'Implementazione Google Pay tramite Stripe Payment Intents per il checkout BlackWork.';

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = 'Google Pay';
		$this->description = __( 'Paga velocemente e in sicurezza con il tuo account Google.', 'bw' );

		$this->test_mode  = get_option( 'bw_google_pay_test_mode', '0' ) === '1';
		$this->enabled    = get_option( 'bw_google_pay_enabled', '0' ) === '1' ? 'yes' : 'no';

		$this->secret_key = $this->test_mode
			? get_option( 'bw_google_pay_test_secret_key', '' )
			: get_option( 'bw_google_pay_secret_key', '' );

		$this->publishable_key = $this->test_mode
			? get_option( 'bw_google_pay_test_publishable_key', '' )
			: get_option( 'bw_google_pay_publishable_key', '' );

		$this->statement_descriptor = get_option( 'bw_google_pay_statement_descriptor', '' );

		$this->webhook_secret = $this->test_mode
			? get_option( 'bw_google_pay_test_webhook_secret', '' )
			: get_option( 'bw_google_pay_webhook_secret', '' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_api_' . $this->id, array( $this, 'handle_webhook' ) );
	}

	/**
	 * Initialize form fields (standard WC gateway settings page).
	 * Full configuration is managed in Blackwork > Site Settings > Google Pay.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Abilita/Disabilita', 'bw' ),
				'type'    => 'checkbox',
				'label'   => __( 'Abilita Google Pay (BlackWork)', 'bw' ),
				'default' => 'no',
			),
		);
	}

	/**
	 * Render the Google Pay button container inside the payment accordion.
	 */
	public function payment_fields() {
		$bw_enabled = get_option( 'bw_google_pay_enabled', '0' ) === '1';
		$wc_settings = get_option( 'woocommerce_' . $this->id . '_settings', array() );
		$wc_enabled = isset( $wc_settings['enabled'] ) && 'yes' === $wc_settings['enabled'];

		?>
		<div id="bw-google-pay-accordion-container">
			<div id="bw-google-pay-accordion-placeholder">
				<?php if ( ! $bw_enabled || ! $wc_enabled ) : ?>
					<p><?php esc_html_e( 'Activate Google Pay (BlackWork) in WooCommerce > Settings > Payments.', 'bw' ); ?></p>
				<?php else : ?>
					<p><?php esc_html_e( 'Initializing Google Pay…', 'bw' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Process the payment by creating and confirming a Stripe PaymentIntent.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return array|void
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wc_add_notice( __( 'Ordine non trovato. Riprova.', 'bw' ), 'error' );
			return;
		}

		if ( $order->get_payment_method() && $this->id !== $order->get_payment_method() ) {
			wc_add_notice( __( 'Metodo di pagamento non valido per questo ordine.', 'bw' ), 'error' );
			return;
		}

		// Retrieve the Stripe PaymentMethod ID injected by the frontend.
		$payment_method_id = isset( $_POST['bw_google_pay_method_id'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			? sanitize_text_field( wp_unslash( $_POST['bw_google_pay_method_id'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			: '';

		if ( empty( $payment_method_id ) ) {
			wc_add_notice( __( 'Errore nella comunicazione con Google Pay. Riprova.', 'bw' ), 'error' );
			return;
		}

		if ( empty( $this->secret_key ) ) {
			wc_add_notice( __( 'Il gateway Google Pay non è configurato correttamente. Contatta il supporto.', 'bw' ), 'error' );
			wc_get_logger()->error( 'Google Pay: chiave segreta mancante.', array( 'source' => 'bw-google-pay' ) );
			return;
		}

		// Build the PaymentIntent payload.
		$amount   = (int) round( $order->get_total() * 100 );
		$currency = strtolower( get_woocommerce_currency() );
		if ( $amount <= 0 ) {
			wc_add_notice( __( 'Questo ordine non richiede un pagamento Google Pay.', 'bw' ), 'error' );
			return;
		}

		$pi_body = array(
			'amount'                    => $amount,
			'currency'                  => $currency,
			'payment_method'            => $payment_method_id,
			'payment_method_types[]'    => 'card',
			'confirm'                   => 'true',
			'return_url'                => $this->get_return_url( $order ),
			'metadata[wc_order_id]'     => $order_id,
			'metadata[order_id]'        => $order_id,
			'metadata[bw_gateway]'      => $this->id,
			'metadata[site_url]'        => home_url(),
			'metadata[mode]'            => $this->test_mode ? 'test' : 'live',
		);

		if ( ! empty( $this->statement_descriptor ) ) {
			$pi_body['statement_descriptor'] = substr( sanitize_text_field( $this->statement_descriptor ), 0, 22 );
		}

		$response = wp_remote_post(
			'https://api.stripe.com/v1/payment_intents',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->secret_key,
					'Content-Type'  => 'application/x-www-form-urlencoded',
					'Stripe-Version'=> '2023-10-16',
					'Idempotency-Key' => 'bw_gpay_' . $order_id . '_' . md5( $payment_method_id ),
				),
				'body'    => $pi_body,
				'timeout' => 30,
			)
		);

		// Handle connection-level errors.
		if ( is_wp_error( $response ) ) {
			wc_add_notice( __( 'Errore di connessione durante il pagamento. Riprova.', 'bw' ), 'error' );
			wc_get_logger()->error(
				'Google Pay: connessione Stripe fallita: ' . $response->get_error_message(),
				array( 'source' => $this->log_source )
			);
			return;
		}

		$http_code = (int) wp_remote_retrieve_response_code( $response );

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $http_code < 200 || $http_code >= 300 ) {
			$error_msg = __( 'Stripe rejected the payment request. Please try again.', 'bw' );
			if ( is_array( $data ) && isset( $data['error']['message'] ) ) {
				$error_msg = sanitize_text_field( (string) $data['error']['message'] );
			}
			wc_add_notice( $error_msg, 'error' );
			wc_get_logger()->error(
				sprintf( 'Google Pay: Stripe HTTP %d - %s', $http_code, $error_msg ),
				array( 'source' => $this->log_source, 'order_id' => $order_id )
			);
			return;
		}

		if ( ! is_array( $data ) ) {
			wc_add_notice( __( 'Risposta non valida da Stripe. Riprova.', 'bw' ), 'error' );
			return;
		}

		// Handle Stripe API errors.
		if ( isset( $data['error'] ) ) {
			$error_msg  = sanitize_text_field( $data['error']['message'] ?? __( 'Pagamento rifiutato. Riprova.', 'bw' ) );
			$error_code = $data['error']['code'] ?? '';
			wc_add_notice( $error_msg, 'error' );
			wc_get_logger()->error(
				sprintf( 'Google Pay: errore Stripe [%s]: %s', $error_code, $error_msg ),
				array( 'source' => $this->log_source, 'order_id' => $order_id )
			);
			return;
		}

		$pi_id  = $data['id'] ?? '';
		$status = $data['status'] ?? '';

		if ( ! empty( $pi_id ) ) {
			$order->update_meta_data( '_bw_gpay_pi_id', sanitize_text_field( $pi_id ) );
			$order->update_meta_data( '_bw_gpay_mode', $this->test_mode ? 'test' : 'live' );
			$order->update_meta_data( '_bw_gpay_pm_id', sanitize_text_field( $payment_method_id ) );
			$order->update_meta_data( '_bw_gpay_created_at', time() );
			$order->save();
		}

		switch ( $status ) {

			case 'succeeded':
				$order->payment_complete( $pi_id );
				$order->add_order_note(
					sprintf(
						/* translators: PaymentIntent ID */
						__( 'Pagamento Google Pay confermato via Stripe. PaymentIntent: %s', 'bw' ),
						$pi_id
					)
				);
				WC()->cart->empty_cart();
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);

			case 'requires_action':
				// 3DS authentication required – redirect to Stripe-hosted page.
				$auth_url = $data['next_action']['redirect_to_url']['url'] ?? '';
				if ( ! empty( $auth_url ) ) {
					$order->update_status(
						'pending',
						sprintf( __( '3DS richiesto. PaymentIntent: %s', 'bw' ), $pi_id )
					);
					return array(
						'result'   => 'success',
						'redirect' => esc_url_raw( $auth_url ),
					);
				}
				wc_add_notice( __( 'Autenticazione aggiuntiva richiesta. Riprova o scegli un altro metodo.', 'bw' ), 'error' );
				return;

			case 'processing':
				// Async confirmation (rare for Google Pay).
				$order->update_status(
					'on-hold',
					sprintf( __( 'Pagamento in elaborazione. PaymentIntent: %s', 'bw' ), $pi_id )
				);
				WC()->cart->empty_cart();
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);

			case 'requires_payment_method':
			case 'canceled':
				$err_msg = __( 'Google Pay was not completed. Please try again.', 'bw' );
				if ( isset( $data['last_payment_error']['message'] ) ) {
					$err_msg = sanitize_text_field( (string) $data['last_payment_error']['message'] );
				}
				wc_add_notice( $err_msg, 'error' );
				wc_get_logger()->warning(
					sprintf( 'Google Pay: PaymentIntent status "%s" [PI: %s]', $status, $pi_id ),
					array( 'source' => $this->log_source, 'order_id' => $order_id )
				);
				return;

			default:
				wc_add_notice( __( 'Il pagamento non è andato a buon fine. Riprova.', 'bw' ), 'error' );
				wc_get_logger()->error(
					sprintf( 'Google Pay: stato PaymentIntent inatteso "%s" [PI: %s]', $status, $pi_id ),
					array( 'source' => $this->log_source, 'order_id' => $order_id )
				);
				return;
		}
	}

	/**
	 * Handle Stripe webhooks posted to /?wc-api=bw_google_pay
	 *
	 * Supported events:
	 *   - payment_intent.succeeded  → completes pending orders
	 *   - payment_intent.payment_failed → logs the failure
	 */
	public function handle_webhook() {
		$payload    = file_get_contents( 'php://input' );
		$sig_header = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ) : '';

		if ( empty( $this->webhook_secret ) ) {
			wc_get_logger()->warning( 'Google Pay Webhook: nessuna chiave webhook configurata.', array( 'source' => $this->log_source ) );
			status_header( 400 );
			exit( 'No webhook secret configured.' );
		}

		if ( ! $this->verify_stripe_signature( $payload, $sig_header, $this->webhook_secret ) ) {
			wc_get_logger()->error( 'Google Pay Webhook: firma non valida.', array( 'source' => $this->log_source ) );
			status_header( 400 );
			exit( 'Invalid signature.' );
		}

		$event = json_decode( $payload, true );
		if ( ! is_array( $event ) ) {
			status_header( 400 );
			exit( 'Invalid payload.' );
		}

		$event_id = isset( $event['id'] ) ? sanitize_text_field( (string) $event['id'] ) : '';
		$type  = $event['type'] ?? '';
		$pi    = $event['data']['object'] ?? array();
		$pi_id = isset( $pi['id'] ) ? sanitize_text_field( (string) $pi['id'] ) : '';

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

		// Anti-conflict: only handle events for this gateway.
		if ( $this->id !== $order->get_payment_method() ) {
			$this->respond_ok();
		}

		// Optional metadata hardening: if present and different, ignore.
		if ( isset( $pi['metadata']['bw_gateway'] ) && $this->id !== $pi['metadata']['bw_gateway'] ) {
			$this->respond_ok();
		}

		$saved_pi_id = (string) $order->get_meta( '_bw_gpay_pi_id', true );
		if ( ! empty( $saved_pi_id ) && ! empty( $pi_id ) && $saved_pi_id !== $pi_id ) {
			wc_get_logger()->warning(
				sprintf( 'Google Pay Webhook: PI mismatch for order %d (saved=%s incoming=%s).', $order_id, $saved_pi_id, $pi_id ),
				array( 'source' => $this->log_source, 'event_id' => $event_id )
			);
			$this->respond_ok();
		}

		// Idempotency: ignore already processed events.
		if ( ! empty( $event_id ) && $this->is_event_processed( $order, $event_id ) ) {
			$this->respond_ok();
		}

		switch ( $type ) {

			case 'payment_intent.succeeded':
				if ( ! $order->is_paid() ) {
					$order->payment_complete( $pi_id );
					$order->add_order_note(
						sprintf( __( 'Pagamento confermato via Stripe Webhook. PaymentIntent: %s', 'bw' ), $pi_id )
					);
				}
				break;

			case 'payment_intent.payment_failed':
				$err_msg   = $pi['last_payment_error']['message'] ?? 'unknown';

				if ( $order->is_paid() ) {
					break;
				}

				if ( 'failed' !== $order->get_status() ) {
					$order->update_status(
						'failed',
						sprintf( __( 'Pagamento fallito via Stripe Webhook. PI: %s — %s', 'bw' ), $pi_id, sanitize_text_field( $err_msg ) )
					);
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
	 * Check whether this Stripe event was already processed for the order.
	 *
	 * @param WC_Order $order    WooCommerce order.
	 * @param string   $event_id Stripe event id.
	 * @return bool
	 */
	private function is_event_processed( WC_Order $order, $event_id ) {
		$processed = $order->get_meta( '_bw_gpay_processed_events', true );
		if ( ! is_array( $processed ) ) {
			return false;
		}
		return in_array( $event_id, $processed, true );
	}

	/**
	 * Mark Stripe event as processed and keep a rolling history.
	 *
	 * @param WC_Order $order    WooCommerce order.
	 * @param string   $event_id Stripe event id.
	 * @return void
	 */
	private function mark_event_processed( WC_Order $order, $event_id ) {
		$processed = $order->get_meta( '_bw_gpay_processed_events', true );
		if ( ! is_array( $processed ) ) {
			$processed = array();
		}

		$processed[] = $event_id;
		$processed = array_values( array_unique( $processed ) );
		if ( count( $processed ) > 20 ) {
			$processed = array_slice( $processed, -20 );
		}

		$order->update_meta_data( '_bw_gpay_processed_events', $processed );
		$order->save();
	}

	/**
	 * Return HTTP 200 quickly for ignorable webhooks.
	 *
	 * @param string $message Optional response body.
	 * @return void
	 */
	private function respond_ok( $message = 'OK' ) {
		status_header( 200 );
		exit( $message );
	}

	/**
	 * Verify the Stripe-Signature header (HMAC SHA-256).
	 *
	 * @param string $payload       Raw request body.
	 * @param string $sig_header    Value of Stripe-Signature HTTP header.
	 * @param string $secret        Webhook endpoint signing secret.
	 * @return bool
	 */
	private function verify_stripe_signature( $payload, $sig_header, $secret ) {
		if ( empty( $payload ) || empty( $sig_header ) || empty( $secret ) ) {
			return false;
		}

		$timestamp  = null;
		$signatures = array();

		foreach ( explode( ',', $sig_header ) as $part ) {
			$part = trim( $part );
			if ( strpos( $part, 't=' ) === 0 ) {
				$timestamp = substr( $part, 2 );
			} elseif ( strpos( $part, 'v1=' ) === 0 ) {
				$signatures[] = substr( $part, 3 );
			}
		}

		if ( null === $timestamp || empty( $signatures ) ) {
			return false;
		}

		// Reject events older than 5 minutes (replay attack protection).
		if ( abs( time() - (int) $timestamp ) > 300 ) {
			return false;
		}

		$expected = hash_hmac( 'sha256', $timestamp . '.' . $payload, $secret );

		foreach ( $signatures as $sig ) {
			if ( hash_equals( $expected, $sig ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return the Google Pay SVG icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return '<svg class="bw-payment-method__gpay-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" aria-label="Google Pay">
			<path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
			<path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
			<path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.07H2.18c-.77 1.54-1.21 3.27-1.21 5.1s.44 3.55 1.21 5.1l3.66-2.17z"/>
			<path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.66l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
		</svg>';
	}
}
