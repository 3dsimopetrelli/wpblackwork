<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Safe logger helper for Stripe-related gateways.
 */
class BW_Stripe_Safe_Logger {

	/**
	 * Log a message with sanitized context.
	 *
	 * @param string $level   Log level.
	 * @param string $message Log message.
	 * @param array  $context Log context.
	 * @return void
	 */
	public static function log( $level, $message, $context = array() ) {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		$logger = wc_get_logger();
		if ( ! is_object( $logger ) || ! method_exists( $logger, $level ) ) {
			$level = 'info';
		}

		if ( ! is_array( $context ) ) {
			$context = array();
		}

		$sanitized = self::sanitize_value( $context );
		$logger->{$level}( (string) $message, $sanitized );
	}

	/**
	 * Recursively sanitize context values.
	 *
	 * @param mixed $value Context value.
	 * @return mixed
	 */
	private static function sanitize_value( $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = self::sanitize_value( $item );
			}
			return $value;
		}

		if ( is_string( $value ) ) {
			$lower = strtolower( $value );
			if (
				false !== strpos( $lower, 'sk_live_' ) ||
				false !== strpos( $lower, 'sk_test_' ) ||
				false !== strpos( $lower, 'pk_live_' ) ||
				false !== strpos( $lower, 'pk_test_' ) ||
				false !== strpos( $lower, 'whsec_' )
			) {
				return '[redacted]';
			}
		}

		return $value;
	}
}
