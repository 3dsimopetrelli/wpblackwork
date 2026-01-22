<?php
/**
 * Brevo (Sendinblue) API client wrapper.
 *
 * Uses wp_remote_request with Brevo v3 endpoints.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Brevo_Client {
    /**
     * @var string
     */
    private $api_key;

    /**
     * @var string
     */
    private $base_url;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @param string $api_key  Brevo API key.
     * @param string $base_url Brevo API base URL.
     * @param int    $timeout  Request timeout in seconds.
     */
    public function __construct( $api_key, $base_url = 'https://api.brevo.com/v3', $timeout = 15 ) {
        $this->api_key  = (string) $api_key;
        $this->base_url = untrailingslashit( $base_url );
        $this->timeout  = absint( $timeout ) ?: 15;
    }

    /**
     * Test the API connection.
     *
     * @return array
     */
    public function get_account() {
        return $this->request( 'GET', '/account' );
    }

    /**
     * Create or update a contact and optionally assign list IDs.
     *
     * @param string $email      Email address.
     * @param array  $attributes Contact attributes.
     * @param array  $list_ids   List IDs.
     *
     * @return array
     */
    public function upsert_contact( $email, $attributes = [], $list_ids = [] ) {
        $payload = [
            'email'          => $email,
            'updateEnabled'  => true,
        ];

        if ( ! empty( $attributes ) ) {
            $payload['attributes'] = $attributes;
        }

        if ( ! empty( $list_ids ) ) {
            $payload['listIds'] = array_map( 'absint', $list_ids );
        }

        return $this->request( 'POST', '/contacts', $payload );
    }

    /**
     * Trigger double opt-in confirmation.
     *
     * @param string $email          Email address.
     * @param int    $template_id    Template ID.
     * @param string $redirect_url   Redirect URL after confirmation.
     * @param array  $list_ids       List IDs.
     * @param array  $attributes     Contact attributes.
     * @param array  $sender         Sender details.
     *
     * @return array
     */
    public function send_double_opt_in( $email, $template_id, $redirect_url, $list_ids = [], $attributes = [], $sender = [] ) {
        $payload = [
            'email'          => $email,
            'templateId'     => absint( $template_id ),
            'redirectionUrl' => $redirect_url,
            'includeListIds' => array_map( 'absint', $list_ids ),
        ];

        if ( ! empty( $attributes ) ) {
            $payload['attributes'] = $attributes;
        }

        if ( ! empty( $sender ) ) {
            $payload['sender'] = $sender;
        }

        return $this->request( 'POST', '/contacts/doubleOptinConfirmation', $payload );
    }

    /**
     * Perform an API request.
     *
     * @param string $method HTTP method.
     * @param string $path   API path.
     * @param array  $body   Request body.
     *
     * @return array
     */
    private function request( $method, $path, $body = [] ) {
        $url = $this->base_url . $path;

        $args = [
            'method'  => $method,
            'timeout' => $this->timeout,
            'headers' => [
                'accept'       => 'application/json',
                'api-key'      => $this->api_key,
                'content-type' => 'application/json',
            ],
        ];

        if ( ! empty( $body ) ) {
            $args['body'] = wp_json_encode( $body );
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'error'   => $response->get_error_message(),
                'code'    => 0,
            ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = [];

        if ( ! empty( $body ) ) {
            $decoded = json_decode( $body, true );
            if ( is_array( $decoded ) ) {
                $data = $decoded;
            }
        }

        if ( $code < 200 || $code >= 300 ) {
            $message = isset( $data['message'] ) ? $data['message'] : 'Brevo API error';
            return [
                'success' => false,
                'error'   => $message,
                'code'    => $code,
                'data'    => $data,
            ];
        }

        return [
            'success' => true,
            'code'    => $code,
            'data'    => $data,
        ];
    }
}
