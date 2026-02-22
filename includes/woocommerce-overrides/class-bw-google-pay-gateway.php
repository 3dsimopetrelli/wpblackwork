<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BW_Google_Pay_Gateway
 *
 * Custom Google Pay gateway via Stripe Payment Intents API.
 * Integrates with the BlackWork custom checkout accordion.
 *
 * Settings are managed exclusively through Blackwork > Site Settings > Google Pay
 * (not through the standard WooCommerce gateway settings page, except the
 * enable/disable toggle which mirrors the bw_google_pay_enabled option).
 */
class BW_Google_Pay_Gateway extends WC_Payment_Gateway {

	/** @var string Current Stripe API version. */
	const STRIPE_API_VERSION = '2024-12-18';

	/** @var string Stripe Payment Intents endpoint. */
	const STRIPE_PI_ENDPOINT = 'https://api.stripe.com/v1/payment_intents';

	/** @var string Stripe Refunds endpoint. */
	const STRIPE_REFUNDS_ENDPOINT = 'https://api.stripe.com/v1/refunds';

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

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id                 = 'bw_google_pay';
		$this->icon               = '';
		$this->has_fields         = true;
		$this->method_title       = 'Google Pay (BlackWork)';
		$this->method_description = 'Implementazione Google Pay tramite Stripe Payment Intents per il checkout BlackWork.';

		$this->supports = array( 'products', 'refunds' );

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
	 * Show the gateway only when the store currency is supported by Stripe
	 * and the site is served over HTTPS (required by Google Pay).
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( ! parent::is_available() ) {
			return false;
		}

		// Google Pay / Stripe requires HTTPS (except on localhost for testing).
		$is_localhost = in_array(
			$_SERVER['SERVER_NAME'] ?? '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			array( 'localhost', '127.0.0.1', '::1' ),
			true
		);
		if ( ! $is_localhost && ! is_ssl() ) {
			return false;
		}

		// Stripe supports a large subset of ISO 4217 currencies.
		// List the ones relevant to this store; extend as needed.
		$supported_currencies = array(
			'EUR', 'USD', 'GBP', 'CHF', 'SEK', 'NOK', 'DKK', 'PLN',
			'CZK', 'HUF', 'RON', 'BGN', 'HRK', 'CAD', 'AUD', 'NZD',
			'SGD', 'HKD', 'JPY', 'MXN', 'BRL',
		);
		if ( ! in_array( get_woocommerce_currency(), $supported_currencies, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Render the Google Pay button container inside the payment accordion.
	 */
	public function payment_fields() {
		?>
		<div id="bw-google-pay-accordion-container">
			<div id="bw-google-pay-accordion-placeholder">
				<p><?php esc_html_e( 'Inizializzazione Google Pay…', 'bw' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Process the payment by creating and confirming a Stripe PaymentIntent.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wc_add_notice( __( 'Ordine non trovato. Riprova.', 'bw' ), 'error' );
			return array( 'result' => 'failure' );
		}

		// Guard: order already paid (race between process_payment and webhook).
		if ( $order->is_paid() ) {
			WC()->cart->empty_cart();
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}

		// Retrieve the Stripe PaymentMethod ID injected by the frontend.
		// The WooCommerce checkout form is nonce-protected at the WC level
		// (woocommerce-process-checkout-nonce), so process_payment() is only
		// reachable after WC core has already verified the nonce.
		$payment_method_id = isset( $_POST['bw_google_pay_method_id'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			? sanitize_text_field( wp_unslash( $_POST['bw_google_pay_method_id'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			: '';

		// PaymentMethod IDs from Stripe always start with "pm_".
		if ( empty( $payment_method_id ) || strpos( $payment_method_id, 'pm_' ) !== 0 ) {
			wc_add_notice( __( 'Errore nella comunicazione con Google Pay. Riprova.', 'bw' ), 'error' );
			return array( 'result' => 'failure' );
		}

		if ( empty( $this->secret_key ) ) {
			wc_add_notice( __( 'Il gateway Google Pay non è configurato correttamente. Contatta il supporto.', 'bw' ), 'error' );
			wc_get_logger()->error( 'Google Pay: chiave segreta mancante.', array( 'source' => 'bw-google-pay' ) );
			return array( 'result' => 'failure' );
		}

		// Build the PaymentIntent payload.
		$amount   = (int) round( $order->get_total() * 100 );
		$currency = strtolower( get_woocommerce_currency() );

		$pi_body = array(
			'amount'                              => $amount,
			'currency'                            => $currency,
			'payment_method'                      => $payment_method_id,
			'payment_method_types'                => array( 'card' ), // Google Pay uses card type
			'confirm'                             => 'true',
			'return_url'                          => $this->get_return_url( $order ),
			'metadata[order_id]'                  => $order_id,
			'metadata[order_key]'                 => $order->get_order_key(),
			'metadata[site_url]'                  => home_url(),
			'metadata[mode]'                      => $this->test_mode ? 'test' : 'live',
		);

		if ( ! empty( $this->statement_descriptor ) ) {
			$pi_body['statement_descriptor'] = substr( sanitize_text_field( $this->statement_descriptor ), 0, 22 );
		}

		// Idempotency key prevents duplicate charges on retries.
		// Bound to order_id + order_key so retries for the same order are safe.
		$idempotency_key = 'order_' . $order_id . '_' . md5( $order->get_order_key() );

		$response = wp_remote_post(
			self::STRIPE_PI_ENDPOINT,
			array(
				'headers' => array(
					'Authorization'  => 'Bearer ' . $this->secret_key,
					'Content-Type'   => 'application/x-www-form-urlencoded',
					'Stripe-Version' => self::STRIPE_API_VERSION,
					'Idempotency-Key'=> $idempotency_key,
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
				array( 'source' => 'bw-google-pay' )
			);
			return array( 'result' => 'failure' );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) ) {
			wc_add_notice( __( 'Risposta non valida da Stripe. Riprova.', 'bw' ), 'error' );
			return array( 'result' => 'failure' );
		}

		// Handle Stripe API errors — log full detail, show generic message to user.
		if ( isset( $data['error'] ) ) {
			$stripe_message = $data['error']['message'] ?? '';
			$error_code     = $data['error']['code'] ?? '';
			$error_type     = $data['error']['type'] ?? '';

			wc_get_logger()->error(
				sprintf( 'Google Pay: errore Stripe [%s/%s]: %s', $error_type, $error_code, $stripe_message ),
				array( 'source' => 'bw-google-pay' )
			);

			// Show a safe message to the customer (no Stripe internal details).
			$user_message = $this->get_user_friendly_error( $error_code, $error_type );
			wc_add_notice( $user_message, 'error' );
			return array( 'result' => 'failure' );
		}

		$pi_id  = $data['id'] ?? '';
		$status = $data['status'] ?? '';

		switch ( $status ) {

			case 'succeeded':
				// Guard again in case webhook already completed this order.
				if ( $order->is_paid() ) {
					WC()->cart->empty_cart();
					return array(
						'result'   => 'success',
						'redirect' => $this->get_return_url( $order ),
					);
				}
				$order->payment_complete( $pi_id );
				$order->set_transaction_id( $pi_id );
				$order->save();
				$order->add_order_note(
					sprintf(
						/* translators: PaymentIntent ID */
						__( 'Pagamento Google Pay confermato via Stripe. PaymentIntent: %s', 'bw' ),
						esc_html( $pi_id )
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
					$order->set_transaction_id( $pi_id );
					$order->update_status(
						'pending',
						sprintf( __( '3DS richiesto. PaymentIntent: %s', 'bw' ), esc_html( $pi_id ) )
					);
					$order->save();
					return array(
						'result'   => 'success',
						'redirect' => esc_url_raw( $auth_url ),
					);
				}
				wc_add_notice( __( 'Autenticazione aggiuntiva richiesta. Riprova o scegli un altro metodo.', 'bw' ), 'error' );
				return array( 'result' => 'failure' );

			case 'processing':
				// Async confirmation (rare for Google Pay).
				$order->set_transaction_id( $pi_id );
				$order->update_status(
					'on-hold',
					sprintf( __( 'Pagamento in elaborazione. PaymentIntent: %s', 'bw' ), esc_html( $pi_id ) )
				);
				$order->save();
				WC()->cart->empty_cart();
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);

			default:
				wc_add_notice( __( 'Il pagamento non è andato a buon fine. Riprova.', 'bw' ), 'error' );
				wc_get_logger()->error(
					sprintf( 'Google Pay: stato PaymentIntent inatteso "%s" [PI: %s]', esc_html( $status ), esc_html( $pi_id ) ),
					array( 'source' => 'bw-google-pay' )
				);
				return array( 'result' => 'failure' );
		}
	}

	/**
	 * Process a refund for a Google Pay order via Stripe Refunds API.
	 *
	 * @param int    $order_id WooCommerce order ID.
	 * @param float  $amount   Amount to refund (WC format, e.g. 12.50).
	 * @param string $reason   Reason for the refund.
	 * @return bool|WP_Error   True on success, WP_Error on failure.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_Error( 'bw_gpay_refund', __( 'Ordine non trovato.', 'bw' ) );
		}

		$pi_id = $order->get_transaction_id();
		if ( empty( $pi_id ) ) {
			return new WP_Error( 'bw_gpay_refund', __( 'Nessun PaymentIntent associato a questo ordine.', 'bw' ) );
		}

		if ( empty( $this->secret_key ) ) {
			return new WP_Error( 'bw_gpay_refund', __( 'Gateway non configurato correttamente.', 'bw' ) );
		}

		$refund_body = array(
			'payment_intent' => $pi_id,
		);

		if ( null !== $amount ) {
			$refund_body['amount'] = (int) round( (float) $amount * 100 );
		}

		if ( ! empty( $reason ) ) {
			// Stripe accepts: duplicate, fraudulent, requested_by_customer.
			$allowed_reasons        = array( 'duplicate', 'fraudulent', 'requested_by_customer' );
			$refund_body['reason']  = in_array( $reason, $allowed_reasons, true ) ? $reason : 'requested_by_customer';
		}

		$refund_body['metadata[order_id]']  = $order_id;
		$refund_body['metadata[refunded_by]'] = get_current_user_id();

		$response = wp_remote_post(
			self::STRIPE_REFUNDS_ENDPOINT,
			array(
				'headers' => array(
					'Authorization'   => 'Bearer ' . $this->secret_key,
					'Content-Type'    => 'application/x-www-form-urlencoded',
					'Stripe-Version'  => self::STRIPE_API_VERSION,
					'Idempotency-Key' => 'refund_order_' . $order_id . '_' . md5( $pi_id . (string) $amount ),
				),
				'body'    => $refund_body,
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			wc_get_logger()->error(
				'Google Pay Refund: connessione Stripe fallita: ' . $response->get_error_message(),
				array( 'source' => 'bw-google-pay' )
			);
			return new WP_Error( 'bw_gpay_refund', __( 'Errore di connessione durante il rimborso.', 'bw' ) );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $data['error'] ) ) {
			$msg = $data['error']['message'] ?? 'Stripe error';
			wc_get_logger()->error(
				'Google Pay Refund: ' . $msg,
				array( 'source' => 'bw-google-pay' )
			);
			return new WP_Error( 'bw_gpay_refund', $msg );
		}

		if ( isset( $data['id'] ) && isset( $data['status'] ) && in_array( $data['status'], array( 'succeeded', 'pending' ), true ) ) {
			$order->add_order_note(
				sprintf(
					/* translators: 1: Refund ID, 2: Amount */
					__( 'Rimborso Google Pay elaborato via Stripe. Refund ID: %1$s — Importo: %2$s', 'bw' ),
					esc_html( $data['id'] ),
					wc_price( $amount )
				)
			);
			return true;
		}

		wc_get_logger()->error(
			'Google Pay Refund: risposta inattesa: ' . wp_json_encode( $data ),
			array( 'source' => 'bw-google-pay' )
		);
		return new WP_Error( 'bw_gpay_refund', __( 'Risposta non valida da Stripe durante il rimborso.', 'bw' ) );
	}

	/**
	 * Handle Stripe webhooks posted to /?wc-api=bw_google_pay
	 *
	 * Supported events:
	 *   - payment_intent.succeeded  → completes pending orders
	 *   - payment_intent.payment_failed → logs the failure
	 */
	public function handle_webhook() {
		$payload = file_get_contents( 'php://input' );

		// Read the raw header — do NOT sanitize_text_field() as it can strip
		// characters from the HMAC hex string, causing verification to always fail.
		$sig_header = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			? wp_unslash( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			: '';

		if ( empty( $this->webhook_secret ) ) {
			wc_get_logger()->warning( 'Google Pay Webhook: nessuna chiave webhook configurata.', array( 'source' => 'bw-google-pay' ) );
			status_header( 400 );
			exit( 'No webhook secret configured.' );
		}

		if ( ! $this->verify_stripe_signature( $payload, $sig_header, $this->webhook_secret ) ) {
			wc_get_logger()->error( 'Google Pay Webhook: firma non valida.', array( 'source' => 'bw-google-pay' ) );
			status_header( 400 );
			exit( 'Invalid signature.' );
		}

		$event = json_decode( $payload, true );
		$type  = $event['type'] ?? '';
		$pi    = $event['data']['object'] ?? array();

		switch ( $type ) {

			case 'payment_intent.succeeded':
				$order_id = $pi['metadata']['order_id'] ?? 0;
				$pi_id    = $pi['id'] ?? '';
				if ( $order_id ) {
					$order = wc_get_order( (int) $order_id );
					if ( ! $order ) {
						break;
					}
					// Guard: only process orders that belong to this gateway.
					if ( $order->get_payment_method() !== $this->id ) {
						break;
					}
					// Guard: PI mismatch — reject if a different PI is already saved.
					$saved_pi = $order->get_transaction_id();
					if ( $saved_pi && $pi_id && $saved_pi !== $pi_id ) {
						wc_get_logger()->warning(
							sprintf( 'Google Pay Webhook: PI mismatch ordine %d — salvato=%s ricevuto=%s', $order_id, $saved_pi, $pi_id ),
							array( 'source' => 'bw-google-pay' )
						);
						break;
					}
					// Guard: event idempotency — skip if already processed.
					$event_id = $event['id'] ?? '';
					if ( $event_id && $order->get_meta( '_bw_stripe_event_' . $event_id ) ) {
						break;
					}
					if ( ! $order->is_paid() ) {
						if ( $event_id ) {
							$order->update_meta_data( '_bw_stripe_event_' . $event_id, time() );
						}
						$order->payment_complete( $pi_id );
						$order->set_transaction_id( $pi_id );
						$order->save();
						$order->add_order_note(
							sprintf( __( 'Pagamento confermato via Stripe Webhook. PaymentIntent: %s', 'bw' ), esc_html( $pi_id ) )
						);
					}
				}
				break;

			case 'payment_intent.payment_failed':
				$order_id = $pi['metadata']['order_id'] ?? 0;
				$pi_id    = $pi['id'] ?? '';
				$err_msg  = $pi['last_payment_error']['message'] ?? 'unknown';
				if ( $order_id ) {
					$order = wc_get_order( (int) $order_id );
					if ( ! $order ) {
						break;
					}
					// Guard: only process orders that belong to this gateway.
					if ( $order->get_payment_method() !== $this->id ) {
						break;
					}
					if ( ! $order->has_status( 'failed' ) ) {
						$order->update_status(
							'failed',
							sprintf(
								__( 'Pagamento fallito via Stripe Webhook. PI: %s — %s', 'bw' ),
								esc_html( $pi_id ),
								sanitize_text_field( $err_msg )
							)
						);
					}
				}
				break;
		}

		status_header( 200 );
		exit( 'OK' );
	}

	/**
	 * Verify the Stripe-Signature header (HMAC SHA-256).
	 *
	 * @param string $payload     Raw request body.
	 * @param string $sig_header  Value of Stripe-Signature HTTP header.
	 * @param string $secret      Webhook endpoint signing secret.
	 * @return bool
	 */
	private function verify_stripe_signature( $payload, $sig_header, $secret ) {
		if ( empty( $payload ) || empty( $sig_header ) || empty( $secret ) ) {
			return false;
		}

		$timestamp  = null;
		$signatures = array();

		foreach ( explode( ',', $sig_header ) as $part ) {
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
	 * Map Stripe error codes to safe, user-friendly messages.
	 * Full Stripe errors are logged; customers never see internal details.
	 *
	 * @param string $error_code Stripe error code (e.g. "card_declined").
	 * @param string $error_type Stripe error type (e.g. "card_error").
	 * @return string
	 */
	private function get_user_friendly_error( $error_code, $error_type ) {
		$messages = array(
			'card_declined'           => __( 'La carta è stata rifiutata. Prova con un\'altra carta.', 'bw' ),
			'insufficient_funds'      => __( 'Fondi insufficienti. Prova con un\'altra carta.', 'bw' ),
			'expired_card'            => __( 'La carta è scaduta. Prova con un\'altra carta.', 'bw' ),
			'incorrect_cvc'          => __( 'Il codice CVC non è corretto.', 'bw' ),
			'processing_error'        => __( 'Errore durante l\'elaborazione del pagamento. Riprova.', 'bw' ),
			'card_velocity_exceeded'  => __( 'Troppi tentativi di pagamento. Attendi qualche minuto e riprova.', 'bw' ),
		);

		if ( isset( $messages[ $error_code ] ) ) {
			return $messages[ $error_code ];
		}

		// Generic fallback for card errors vs. other errors.
		if ( 'card_error' === $error_type ) {
			return __( 'Il pagamento con questa carta non è andato a buon fine. Prova con un\'altra carta.', 'bw' );
		}

		return __( 'Il pagamento non è andato a buon fine. Riprova o scegli un altro metodo.', 'bw' );
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
