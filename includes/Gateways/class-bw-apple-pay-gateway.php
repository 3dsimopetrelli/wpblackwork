<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Placeholder gateway for future Apple Pay integration via Stripe.
 *
 * Not registered in WooCommerce yet.
 */
class BW_Apple_Pay_Gateway extends BW_Abstract_Stripe_Gateway {

	/**
	 * Constructor placeholder.
	 */
	public function __construct() {
		$this->id                 = 'bw_apple_pay';
		$this->method_title       = 'Apple Pay (BlackWork)';
		$this->method_description = 'Placeholder gateway. Implementation pending.';
	}

	/**
	 * Placeholder payment processing.
	 *
	 * @param int $order_id WooCommerce order id.
	 * @return array|void
	 */
	public function process_payment( $order_id ) {
		wc_add_notice( __( 'Apple Pay is not configured yet.', 'bw' ), 'error' );
		return;
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
}
