<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Backward-compatible bootstrap for BW Google Pay gateway.
 *
 * Keeps legacy include path while loading the refactored gateway architecture.
 */
$bw_gateway_dependencies = array(
	BW_MEW_PATH . 'includes/Utils/class-bw-stripe-safe-logger.php',
	BW_MEW_PATH . 'includes/Stripe/class-bw-stripe-api-client.php',
	BW_MEW_PATH . 'includes/Gateways/class-bw-abstract-stripe-gateway.php',
	BW_MEW_PATH . 'includes/Gateways/class-bw-google-pay-gateway.php',
	BW_MEW_PATH . 'includes/Gateways/class-bw-klarna-gateway.php',
	BW_MEW_PATH . 'includes/Gateways/class-bw-apple-pay-gateway.php',
);

foreach ( $bw_gateway_dependencies as $bw_gateway_file ) {
	if ( file_exists( $bw_gateway_file ) ) {
		require_once $bw_gateway_file;
	}
}
