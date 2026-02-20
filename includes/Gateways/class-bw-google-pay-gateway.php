<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Google Pay gateway via Stripe Payment Intents API.
 */
class BW_Google_Pay_Gateway extends BW_Abstract_Stripe_Gateway {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'bw_google_pay';
		$this->icon               = '';
		$this->has_fields         = true;
		$current_supports         = is_array( $this->supports ) ? $this->supports : array();
		$this->supports           = array_values( array_unique( array_merge( array( 'products', 'refunds' ), $current_supports ) ) );
		$this->method_title       = 'Google Pay (BlackWork)';
		$this->method_description = 'Implementazione Google Pay tramite Stripe Payment Intents per il checkout BlackWork.';
		$this->log_source         = 'bw_google_pay';

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
	 * Init gateway settings fields.
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
	 * Render checkout fields container.
	 */
	public function payment_fields() {
		$bw_enabled  = get_option( 'bw_google_pay_enabled', '0' ) === '1';
		$wc_settings = get_option( 'woocommerce_' . $this->id . '_settings', array() );
		$wc_enabled  = isset( $wc_settings['enabled'] ) && 'yes' === $wc_settings['enabled'];
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
	 * Process payment.
	 *
	 * @param int $order_id Order id.
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

		$payment_method_id = isset( $_POST['bw_google_pay_method_id'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			? sanitize_text_field( wp_unslash( $_POST['bw_google_pay_method_id'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			: '';

		if ( empty( $payment_method_id ) ) {
			wc_add_notice( __( 'Errore nella comunicazione con Google Pay. Riprova.', 'bw' ), 'error' );
			return;
		}

		if ( empty( $this->secret_key ) ) {
			wc_add_notice( __( 'Il gateway Google Pay non è configurato correttamente. Contatta il supporto.', 'bw' ), 'error' );
			$this->log_safe( 'error', 'Google Pay: chiave segreta mancante.', array( 'source' => $this->get_log_source() ) );
			return;
		}

		$intent_data = $this->create_payment_intent_request( $order, $payment_method_id );
		if ( is_wp_error( $intent_data ) ) {
			wc_add_notice( $intent_data->get_error_message(), 'error' );
			$this->log_safe(
				'error',
				sprintf( 'Google Pay PI create failed for order %d: %s', (int) $order_id, $intent_data->get_error_message() ),
				array( 'source' => $this->get_log_source(), 'order_id' => $order_id )
			);
			return;
		}

		$pi_id  = isset( $intent_data['id'] ) ? (string) $intent_data['id'] : '';
		$status = isset( $intent_data['status'] ) ? (string) $intent_data['status'] : '';

		if ( ! empty( $pi_id ) ) {
			$this->persist_payment_intent_data( $order, $pi_id, $payment_method_id );
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
				$auth_url = isset( $intent_data['next_action']['redirect_to_url']['url'] ) ? $intent_data['next_action']['redirect_to_url']['url'] : '';
				if ( ! empty( $auth_url ) ) {
					$order->update_status( 'pending', sprintf( __( '3DS richiesto. PaymentIntent: %s', 'bw' ), $pi_id ) );
					return array(
						'result'   => 'success',
						'redirect' => esc_url_raw( $auth_url ),
					);
				}
				wc_add_notice( __( 'Autenticazione aggiuntiva richiesta. Riprova o scegli un altro metodo.', 'bw' ), 'error' );
				return;

			case 'processing':
				$order->update_status( 'on-hold', sprintf( __( 'Pagamento in elaborazione. PaymentIntent: %s', 'bw' ), $pi_id ) );
				WC()->cart->empty_cart();
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);

			case 'requires_payment_method':
			case 'canceled':
				$err_msg = __( 'Google Pay was not completed. Please try again.', 'bw' );
				if ( isset( $intent_data['last_payment_error']['message'] ) ) {
					$err_msg = sanitize_text_field( (string) $intent_data['last_payment_error']['message'] );
				}
				wc_add_notice( $err_msg, 'error' );
				$this->log_safe(
					'warning',
					sprintf( 'Google Pay: PaymentIntent status "%s" [PI: %s]', $status, $pi_id ),
					array( 'source' => $this->get_log_source(), 'order_id' => $order_id )
				);
				return;

			default:
				wc_add_notice( __( 'Il pagamento non è andato a buon fine. Riprova.', 'bw' ), 'error' );
				$this->log_safe(
					'error',
					sprintf( 'Google Pay: unexpected PaymentIntent status "%s" [PI: %s]', $status, $pi_id ),
					array( 'source' => $this->get_log_source(), 'order_id' => $order_id )
				);
				return;
		}
	}

	/**
	 * Return Google Pay icon.
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

	/**
	 * @inheritDoc
	 */
	protected function get_live_secret_option_name() {
		return 'bw_google_pay_secret_key';
	}

	/**
	 * @inheritDoc
	 */
	protected function get_test_secret_option_name() {
		return 'bw_google_pay_test_secret_key';
	}

	/**
	 * @inheritDoc
	 */
	protected function get_live_webhook_secret_option_name() {
		return 'bw_google_pay_webhook_secret';
	}

	/**
	 * @inheritDoc
	 */
	protected function get_test_webhook_secret_option_name() {
		return 'bw_google_pay_test_webhook_secret';
	}

	/**
	 * @inheritDoc
	 */
	protected function get_order_meta_keys() {
		return array(
			'pi_id'            => '_bw_gpay_pi_id',
			'mode'             => '_bw_gpay_mode',
			'pm_id'            => '_bw_gpay_pm_id',
			'created_at'       => '_bw_gpay_created_at',
			'processed_events' => '_bw_gpay_processed_events',
			'refund_ids'       => '_bw_gpay_refund_ids',
			'last_refund_id'   => '_bw_gpay_last_refund_id',
		);
	}
}
