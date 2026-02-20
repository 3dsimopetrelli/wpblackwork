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
		$this->description       = __( 'Pay quickly and securely with Apple Pay.', 'bw' );
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
		$svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 165.5211 105.9651" aria-hidden="true" focusable="false">
<g>
	<path d="M150.6981,0H14.8232c-0.5659,0-1.1328,0-1.6977,0.0033C12.648,0.0067,12.1716,0.012,11.6952,0.025c-1.039,0.0281-2.0869,0.0894-3.1129,0.2738C7.5399,0.4864,6.5699,0.7924,5.6236,1.2742c-0.9303,0.4731-1.782,1.0919-2.5201,1.8303c-0.7384,0.7384-1.3572,1.5887-1.8302,2.52C0.7914,6.5708,0.4852,7.5411,0.2989,8.5843c-0.1854,1.0263-0.2471,2.074-0.2751,3.1119c-0.0128,0.4764-0.0183,0.9528-0.0214,1.4291c-0.0033,0.5661-0.0022,1.1318-0.0022,1.6989V91.142c0,0.5671-0.0011,1.1318,0.0022,1.699c0.0031,0.4763,0.0086,0.9527,0.0214,1.4291c0.028,1.037,0.0897,2.0847,0.2751,3.1107c0.1863,1.0436,0.4925,2.0135,0.9744,2.9599c0.473,0.9313,1.0918,1.7827,1.8302,2.52c0.7381,0.7396,1.5898,1.3583,2.5201,1.8302c0.9463,0.4831,1.9163,0.7892,2.9587,0.9767c1.026,0.1832,2.0739,0.2456,3.1129,0.2737c0.4764,0.0108,0.9528,0.0172,1.4303,0.0194c0.5649,0.0044,1.1318,0.0044,1.6977,0.0044h135.8749c0.5649,0,1.1318,0,1.6966-0.0044c0.4764-0.0022,0.9528-0.0086,1.4314-0.0194c1.0368-0.0281,2.0845-0.0905,3.113-0.2737c1.041-0.1875,2.0112-0.4936,2.9576-0.9767c0.9313-0.4719,1.7805-1.0906,2.5201-1.8302c0.7372-0.7373,1.356-1.5887,1.8302-2.52c0.483-0.9464,0.7889-1.9163,0.9743-2.9599c0.1855-1.026,0.2457-2.0737,0.2738-3.1107c0.013-0.4764,0.0194-0.9528,0.0216-1.4291c0.0044-0.5672,0.0044-1.1319,0.0044-1.699V14.8242c0-0.5671,0-1.1328-0.0044-1.6989c-0.0022-0.4763-0.0086-0.9527-0.0216-1.4291c-0.0281-1.0379-0.0883-2.0856-0.2738-3.1119c-0.1854-1.0432-0.4913-2.0135-0.9743-2.9598c-0.4742-0.9313-1.093-1.7816-1.8302-2.52c-0.7396-0.7384-1.5888-1.3572-2.5201-1.8303c-0.9464-0.4818-1.9166-0.7878-2.9576-0.9754c-1.0285-0.1844-2.0762-0.2457-3.113-0.2738c-0.4786-0.013-0.955-0.0183-1.4314-0.0217C151.8299,0,151.263,0,150.6981,0L150.6981,0z"/>
	<path fill="#FFFFFF" d="M150.6981,3.532l1.6715,0.0032c0.4528,0.0032,0.9056,0.0081,1.3609,0.0205c0.792,0.0214,1.7185,0.0643,2.5821,0.2191c0.7507,0.1352,1.3803,0.3408,1.9845,0.6484c0.5965,0.3031,1.143,0.7003,1.6202,1.1768c0.479,0.4797,0.8767,1.0271,1.1838,1.6302c0.3059,0.5995,0.5102,1.2261,0.6446,1.9823c0.1544,0.8542,0.1971,1.7832,0.2188,2.5801c0.0122,0.4498,0.0182,0.8996,0.0204,1.3601c0.0043,0.5569,0.0042,1.1135,0.0042,1.6715V91.142c0,0.558,0.0001,1.1136-0.0043,1.6824c-0.0021,0.4497-0.0081,0.8995-0.0204,1.3501c-0.0216,0.7957-0.0643,1.7242-0.2206,2.5885c-0.1325,0.7458-0.3367,1.3725-0.6443,1.975c-0.3062,0.6016-0.7033,1.1484-1.1802,1.6251c-0.4799,0.48-1.0246,0.876-1.6282,1.1819c-0.5997,0.3061-1.2282,0.5115-1.9715,0.6453c-0.8811,0.157-1.8464,0.2002-2.5734,0.2199c-0.4574,0.0103-0.9126,0.0165-1.3789,0.0187c-0.5557,0.0043-1.1134,0.0042-1.6692,0.0042H14.8232c-0.0074,0-0.0146,0-0.0221,0c-0.5494,0-1.0999,0-1.6593-0.0043c-0.4561-0.0021-0.9112-0.0082-1.3512-0.0182c-0.7436-0.0201-1.7095-0.0632-2.5834-0.2193c-0.7497-0.1348-1.3782-0.3402-1.9858-0.6503c-0.5979-0.3032-1.1422-0.6988-1.6223-1.1797c-0.4764-0.4756-0.8723-1.0207-1.1784-1.6232c-0.3064-0.6019-0.5114-1.2305-0.6462-1.9852c-0.1558-0.8626-0.1986-1.7874-0.22-2.5777c-0.0122-0.4525-0.0173-0.9049-0.0202-1.3547l-0.0022-1.3279l0.0001-0.3506V14.8242l-0.0001-0.3506l0.0021-1.3251c0.003-0.4525,0.0081-0.9049,0.0203-1.357c0.0214-0.7911,0.0642-1.7163,0.2213-2.5861C3.9094,8.4575,4.1143,7.8289,4.4223,7.224C4.726,6.6261,5.1226,6.0803,5.6015,5.6015c0.477-0.4772,1.0231-0.8739,1.6248-1.1799C7.8274,4.1155,8.4571,3.91,9.2068,3.7751c0.8638-0.1552,1.7909-0.198,2.5849-0.2195c0.4526-0.0123,0.9052-0.0172,1.3544-0.0203l1.6771-0.0033H150.6981"/>
	<g>
		<g>
			<path d="M43.5084,35.7697c1.4032-1.755,2.3554-4.1116,2.1043-6.5197c-2.0541,0.1022-4.5606,1.3551-6.0118,3.1116c-1.303,1.5041-2.4563,3.9593-2.1557,6.2665C39.751,38.8281,42.0547,37.4756,43.5084,35.7697"/>
			<path d="M45.5865,39.0786c-3.3486-0.1995-6.1956,1.9004-7.7948,1.9004c-1.5999,0-4.0487-1.7999-6.6972-1.7514c-3.4472,0.0506-6.6458,1.9997-8.3952,5.0996c-3.598,6.2015-0.9495,15.4004,2.5494,20.4511c1.6992,2.4988,3.7469,5.2501,6.4452,5.1512c2.5494-0.1,3.5486-1.6507,6.6475-1.6507c3.0966,0,3.9967,1.6507,6.6954,1.6007c2.7986-0.05,4.5482-2.5,6.2474-5.0011c1.9492-2.8485,2.7471-5.5989,2.7973-5.7499c-0.0502-0.05-5.3964-2.101-5.446-8.2509c-0.0505-5.1494,4.1974-7.5987,4.3973-7.7506C50.634,39.5791,46.8859,39.1791,45.5865,39.0786"/>
		</g>
		<g>
			<path d="M78.9732,32.1102c7.278,0,12.3464,5.0168,12.3464,12.3209c0,7.3302-5.1722,12.3733-12.5284,12.3733H70.733v12.8142h-5.8225V32.1102H78.9732z M70.733,51.9172h6.6804c5.0689,0,7.9538-2.729,7.9538-7.46c0-4.7305-2.8849-7.434-7.9278-7.434H70.733V51.9172z"/>
			<path d="M92.7641,61.8472c0-4.8092,3.6651-7.5645,10.4231-7.9801l7.252-0.4423v-2.0792c0-3.0413-2.0015-4.7049-5.5623-4.7049c-2.9376,0-5.069,1.5076-5.5107,3.821h-5.2509c0.1564-4.8609,4.731-8.3956,10.9175-8.3956c6.6543,0,10.9952,3.4831,10.9952,8.8894v18.6631h-5.3808v-4.4964h-0.1298c-1.5337,2.9371-4.913,4.7822-8.5781,4.7822C96.5329,69.9044,92.7641,66.6815,92.7641,61.8472z M110.4392,59.4296v-2.1058l-6.4723,0.4161c-3.639,0.2337-5.5362,1.5854-5.5362,3.9509c0,2.2873,1.9754,3.7694,5.0684,3.7694C107.4499,65.4602,110.4392,62.9382,110.4392,59.4296z"/>
			<path d="M120.9746,79.6522v-4.4964c0.364,0.0512,1.2475,0.1033,1.7152,0.1033c2.5736,0,4.0291-1.091,4.9131-3.8987l0.5199-1.6636l-9.8516-27.2928h6.0822l6.8624,22.1457h0.1298l6.8624-22.1457h5.9268l-10.2156,28.6706c-2.3394,6.5761-5.0168,8.734-10.6834,8.734C122.7941,79.8086,121.3642,79.7565,120.9746,79.6522z"/>
		</g>
	</g>
</g>
</svg>
SVG;

		return '<span class="bw-payment-method__applepay-logo" role="img" aria-label="' . esc_attr__( 'Apple Pay', 'bw' ) . '">' . $svg . '</span>';
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
