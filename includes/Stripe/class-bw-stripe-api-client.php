<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lightweight Stripe API client wrapper based on wp_remote_request.
 */
class BW_Stripe_Api_Client {

	/**
	 * Perform a Stripe API request.
	 *
	 * @param string      $method          HTTP method.
	 * @param string      $endpoint        Stripe endpoint (absolute URL or /v1 path).
	 * @param string      $secret_key      Stripe secret key.
	 * @param array       $body            Request body.
	 * @param string|null $idempotency_key Optional idempotency key.
	 * @return array{ok:bool,status:int,data:array,error:string}
	 */
	public static function request( $method, $endpoint, $secret_key, $body = array(), $idempotency_key = null ) {
		$method = strtoupper( (string) $method );
		$url    = self::build_url( $endpoint );

		$headers = array(
			'Authorization'  => 'Bearer ' . $secret_key,
			'Content-Type'   => 'application/x-www-form-urlencoded',
			'Stripe-Version' => '2023-10-16',
		);

		if ( ! empty( $idempotency_key ) ) {
			$headers['Idempotency-Key'] = $idempotency_key;
		}

		$args = array(
			'method'  => $method,
			'headers' => $headers,
			'timeout' => 30,
		);

		if ( 'GET' !== $method && ! empty( $body ) ) {
			$args['body'] = $body;
		}

		$response = wp_remote_request( $url, $args );
		if ( is_wp_error( $response ) ) {
			return array(
				'ok'     => false,
				'status' => 0,
				'data'   => array(),
				'error'  => $response->get_error_message(),
			);
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );
		$data   = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$error_message = '';
		if ( isset( $data['error']['message'] ) ) {
			$error_message = sanitize_text_field( (string) $data['error']['message'] );
		}

		$ok = $status >= 200 && $status < 300 && empty( $data['error'] );

		return array(
			'ok'     => $ok,
			'status' => $status,
			'data'   => $data,
			'error'  => $error_message,
		);
	}

	/**
	 * Normalize Stripe endpoint URL.
	 *
	 * @param string $endpoint Endpoint string.
	 * @return string
	 */
	private static function build_url( $endpoint ) {
		$endpoint = (string) $endpoint;
		if ( 0 === strpos( $endpoint, 'https://' ) ) {
			return $endpoint;
		}

		$endpoint = '/' . ltrim( $endpoint, '/' );
		return 'https://api.stripe.com' . $endpoint;
	}
}
