<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Placeholder gateway for future Klarna integration via Stripe.
 *
 * Not registered in WooCommerce yet.
 */
class BW_Klarna_Gateway extends BW_Abstract_Stripe_Gateway {

	/**
	 * Constructor placeholder.
	 */
	public function __construct() {
		$this->id                 = 'bw_klarna';
		$this->method_title       = 'Klarna (BlackWork)';
		$this->method_description = 'Placeholder gateway. Implementation pending.';
	}

	/**
	 * Placeholder payment processing.
	 *
	 * @param int $order_id WooCommerce order id.
	 * @return array|void
	 */
	public function process_payment( $order_id ) {
		wc_add_notice( __( 'Klarna is not configured yet.', 'bw' ), 'error' );
		return;
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
