<?php
/**
 * Blackwork Site Settings — payment-gateway test-connection AJAX handlers.
 *
 * Extracted verbatim from class-blackwork-site-settings.php (Phase 3a, BW-TASK-20260629). Function bodies are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX handler to test Stripe connection for Google Pay settings.
 */
function bw_google_pay_test_connection_ajax_handler()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'bw')]);
    }

    check_ajax_referer('bw_google_pay_test_connection', 'nonce');

    $mode = isset($_POST['mode']) && 'test' === sanitize_key(wp_unslash($_POST['mode'])) ? 'test' : 'live';
    $secret_key = isset($_POST['secret_key']) ? sanitize_text_field(wp_unslash($_POST['secret_key'])) : '';
    $publishable_key = isset($_POST['publishable_key']) ? sanitize_text_field(wp_unslash($_POST['publishable_key'])) : '';

    if ('' === $secret_key) {
        wp_send_json_error(['message' => __('Secret key is required.', 'bw')]);
    }

    $expected_secret_prefix = 'test' === $mode ? 'sk_test_' : 'sk_live_';
    $expected_publishable_prefix = 'test' === $mode ? 'pk_test_' : 'pk_live_';

    if (0 !== strpos($secret_key, $expected_secret_prefix)) {
        wp_send_json_error([
            'message' => 'test' === $mode
                ? __('The selected Test Mode requires a key starting with sk_test_.', 'bw')
                : __('The selected Live Mode requires a key starting with sk_live_.', 'bw'),
        ]);
    }

    if ('' !== $publishable_key && 0 !== strpos($publishable_key, $expected_publishable_prefix)) {
        wp_send_json_error([
            'message' => 'test' === $mode
                ? __('The selected Test Mode requires a publishable key starting with pk_test_.', 'bw')
                : __('The selected Live Mode requires a publishable key starting with pk_live_.', 'bw'),
        ]);
    }

    $response = wp_remote_get(
        'https://api.stripe.com/v1/account',
        [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
            ],
        ]
    );

    if (is_wp_error($response)) {
        wp_send_json_error([
            'message' => sprintf(
                /* translators: %s: WP error message */
                __('Unable to reach Stripe API: %s', 'bw'),
                $response->get_error_message()
            ),
        ]);
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $payload = json_decode($body, true);

    if ($status_code < 200 || $status_code >= 300) {
        $stripe_error = '';
        if (is_array($payload) && isset($payload['error']['message'])) {
            $stripe_error = sanitize_text_field((string) $payload['error']['message']);
        }

        wp_send_json_error([
            'message' => $stripe_error
                ? sprintf(__('Stripe API error: %s', 'bw'), $stripe_error)
                : __('Stripe API rejected the request. Please verify your keys.', 'bw'),
        ]);
    }

    if (!is_array($payload) || !isset($payload['id'])) {
        wp_send_json_error(['message' => __('Unexpected Stripe response. Please try again.', 'bw')]);
    }

    $api_mode = !empty($payload['livemode']) ? 'live' : 'test';
    // Some Stripe account payloads can report an unexpected livemode value even when
    // the API key pair is correct. Prefix validation above already guarantees key mode.
    $effective_mode = $mode;
    if ($api_mode !== $mode) {
        $effective_mode = $mode;
    }

    wp_send_json_success([
        'message' => sprintf(
            /* translators: 1: mode label, 2: Stripe account id */
            __('Connected successfully (%1$s mode) - Account: %2$s', 'bw'),
            'test' === $effective_mode ? __('Test', 'bw') : __('Live', 'bw'),
            sanitize_text_field((string) $payload['id'])
        ),
        'mode' => $effective_mode,
    ]);
}

/**
 * AJAX handler to test Google Maps + Places API connectivity for checkout autocomplete.
 */
function bw_google_maps_test_connection_ajax_handler()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'bw')]);
    }

    check_ajax_referer('bw_google_maps_test_connection', 'nonce');

    $api_key = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';

    if ('' === $api_key) {
        wp_send_json_error(['message' => __('Google Maps API key is required.', 'bw')]);
    }

    if (0 !== strpos($api_key, 'AIza')) {
        wp_send_json_error([
            'message' => __('The API key format looks invalid. It should start with "AIza".', 'bw'),
        ]);
    }

    $site_referer = home_url('/');

    $maps_js_url = add_query_arg(
        [
            'key' => $api_key,
            'libraries' => 'places',
            'v' => 'weekly',
        ],
        'https://maps.googleapis.com/maps/api/js'
    );

    $maps_js_response = wp_remote_get(
        $maps_js_url,
        [
            'timeout' => 20,
            'headers' => [
                'Referer' => $site_referer,
            ],
        ]
    );

    if (is_wp_error($maps_js_response)) {
        wp_send_json_error([
            'message' => sprintf(
                /* translators: %s: WP error message */
                __('Unable to reach Google Maps JavaScript API: %s', 'bw'),
                $maps_js_response->get_error_message()
            ),
        ]);
    }

    $maps_status_code = (int) wp_remote_retrieve_response_code($maps_js_response);
    $maps_body = (string) wp_remote_retrieve_body($maps_js_response);

    if ($maps_status_code < 200 || $maps_status_code >= 300) {
        wp_send_json_error([
            'message' => sprintf(
                /* translators: %d: HTTP status code */
                __('Google Maps JavaScript API returned HTTP %d.', 'bw'),
                $maps_status_code
            ),
        ]);
    }

    $known_maps_errors = [
        'RefererNotAllowedMapError' => __('Referrer is not allowed. Add your domain to HTTP referrer restrictions.', 'bw'),
        'InvalidKeyMapError' => __('Invalid API key.', 'bw'),
        'ApiNotActivatedMapError' => __('Maps JavaScript API or Places API is not enabled in Google Cloud.', 'bw'),
        'ApiProjectMapError' => __('The key is linked to a project that cannot use this API.', 'bw'),
        'BillingNotEnabledMapError' => __('Billing is not enabled for this Google Cloud project.', 'bw'),
        'ExpiredKeyMapError' => __('This API key is expired.', 'bw'),
    ];

    foreach ($known_maps_errors as $error_code => $error_message) {
        if (false !== strpos($maps_body, $error_code)) {
            wp_send_json_error([
                'message' => $error_message,
                'details' => sprintf(
                    /* translators: %s: Google Maps error code */
                    __('Google error code: %s', 'bw'),
                    $error_code
                ),
            ]);
        }
    }

    wp_send_json_success([
        'message' => __('Google Maps key is valid for Maps JavaScript API and referrer restrictions look correct.', 'bw'),
        'details' => sprintf(
            /* translators: %s: tested site URL */
            __('Tested referrer: %s. Note: server-side Places Web Service is intentionally not checked when key uses HTTP referrer restrictions.', 'bw'),
            esc_url_raw($site_referer)
        ),
    ]);
}

/**
 * AJAX handler to test Stripe connection for Klarna settings (live mode).
 */
function bw_klarna_test_connection_ajax_handler()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'bw')]);
    }

    check_ajax_referer('bw_klarna_test_connection', 'nonce');

    $secret_key = isset($_POST['secret_key']) ? sanitize_text_field(wp_unslash($_POST['secret_key'])) : '';
    $publishable_key = isset($_POST['publishable_key']) ? sanitize_text_field(wp_unslash($_POST['publishable_key'])) : '';

    if ('' === $secret_key) {
        wp_send_json_error(['message' => __('Secret key is required.', 'bw')]);
    }

    if (0 !== strpos($secret_key, 'sk_live_')) {
        wp_send_json_error([
            'message' => __('Klarna live mode requires a key starting with sk_live_.', 'bw'),
        ]);
    }

    if ('' !== $publishable_key && 0 !== strpos($publishable_key, 'pk_live_')) {
        wp_send_json_error([
            'message' => __('Klarna live mode requires a publishable key starting with pk_live_.', 'bw'),
        ]);
    }

    $response = wp_remote_get(
        'https://api.stripe.com/v1/account',
        [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
            ],
        ]
    );

    if (is_wp_error($response)) {
        wp_send_json_error([
            'message' => sprintf(
                /* translators: %s: WP error message */
                __('Unable to reach Stripe API: %s', 'bw'),
                $response->get_error_message()
            ),
        ]);
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $payload = json_decode($body, true);

    if ($status_code < 200 || $status_code >= 300) {
        $stripe_error = '';
        if (is_array($payload) && isset($payload['error']['message'])) {
            $stripe_error = sanitize_text_field((string) $payload['error']['message']);
        }

        wp_send_json_error([
            'message' => $stripe_error
                ? sprintf(__('Stripe API error: %s', 'bw'), $stripe_error)
                : __('Stripe API rejected the request. Please verify your keys.', 'bw'),
        ]);
    }

    if (!is_array($payload) || !isset($payload['id'])) {
        wp_send_json_error(['message' => __('Unexpected Stripe response. Please try again.', 'bw')]);
    }

    wp_send_json_success([
        'message' => sprintf(
            /* translators: %s: Stripe account id */
            __('Connected successfully (Live mode) - Account: %s', 'bw'),
            sanitize_text_field((string) $payload['id'])
        ),
        'mode' => 'live',
    ]);
}

/**
 * AJAX handler to test Stripe connection for Apple Pay settings (live mode only).
 */
function bw_apple_pay_test_connection_ajax_handler()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'bw')]);
    }

    check_ajax_referer('bw_apple_pay_test_connection', 'nonce');

    $secret_key = isset($_POST['secret_key']) ? sanitize_text_field(wp_unslash($_POST['secret_key'])) : '';
    $publishable_key = isset($_POST['publishable_key']) ? sanitize_text_field(wp_unslash($_POST['publishable_key'])) : '';

    if ('' === $secret_key) {
        $secret_key = (string) get_option('bw_google_pay_secret_key', '');
    }
    if ('' === $publishable_key) {
        $publishable_key = (string) get_option('bw_google_pay_publishable_key', '');
    }

    if ('' === $secret_key) {
        wp_send_json_error(['message' => __('Live secret key is required. Add Apple Pay key or configure global live keys.', 'bw')]);
    }

    if (0 === strpos($secret_key, 'sk_test_')) {
        wp_send_json_error([
            'message' => __('Apple Pay LIVE mode does not accept test keys. Use a secret key starting with sk_live_.', 'bw'),
        ]);
    }

    if ('' !== $publishable_key && 0 === strpos($publishable_key, 'pk_test_')) {
        wp_send_json_error([
            'message' => __('Apple Pay LIVE mode does not accept test publishable keys. Use a key starting with pk_live_.', 'bw'),
        ]);
    }

    if (0 !== strpos($secret_key, 'sk_live_')) {
        wp_send_json_error([
            'message' => __('Apple Pay LIVE mode requires a key starting with sk_live_.', 'bw'),
        ]);
    }

    if ('' !== $publishable_key && 0 !== strpos($publishable_key, 'pk_live_')) {
        wp_send_json_error([
            'message' => __('Apple Pay LIVE mode requires a publishable key starting with pk_live_.', 'bw'),
        ]);
    }

    $response = wp_remote_get(
        'https://api.stripe.com/v1/account',
        [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
            ],
        ]
    );

    if (is_wp_error($response)) {
        wp_send_json_error([
            'message' => sprintf(
                /* translators: %s: WP error message */
                __('Unable to reach Stripe API: %s', 'bw'),
                $response->get_error_message()
            ),
        ]);
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $payload = json_decode($body, true);

    if ($status_code < 200 || $status_code >= 300) {
        $stripe_error = '';
        if (is_array($payload) && isset($payload['error']['message'])) {
            $stripe_error = sanitize_text_field((string) $payload['error']['message']);
        }

        wp_send_json_error([
            'message' => $stripe_error
                ? sprintf(__('Stripe API error: %s', 'bw'), $stripe_error)
                : __('Stripe API rejected the request. Please verify your keys.', 'bw'),
        ]);
    }

    if (!is_array($payload) || !isset($payload['id'])) {
        wp_send_json_error(['message' => __('Unexpected Stripe response. Please try again.', 'bw')]);
    }

    wp_send_json_success([
        'message' => sprintf(
            /* translators: %s: Stripe account id */
            __('Connected successfully (Live mode) - Account: %s', 'bw'),
            sanitize_text_field((string) $payload['id'])
        ),
        'mode' => 'live',
    ]);
}

/**
 * AJAX handler to verify Apple Pay domain status in Stripe (live mode only).
 */
function bw_apple_pay_verify_domain_ajax_handler()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'bw')]);
    }

    check_ajax_referer('bw_apple_pay_test_connection', 'nonce');

    $secret_key = isset($_POST['secret_key']) ? sanitize_text_field(wp_unslash($_POST['secret_key'])) : '';
    if ('' === $secret_key) {
        $secret_key = (string) get_option('bw_apple_pay_secret_key', '');
    }
    if ('' === $secret_key) {
        $secret_key = (string) get_option('bw_google_pay_secret_key', '');
    }

    if ('' === $secret_key) {
        wp_send_json_error(['message' => __('Live secret key is required before checking domain verification.', 'bw')]);
    }

    if (0 === strpos($secret_key, 'sk_test_')) {
        wp_send_json_error([
            'message' => __('Apple Pay LIVE mode does not accept test keys. Use a secret key starting with sk_live_.', 'bw'),
        ]);
    }

    if (0 !== strpos($secret_key, 'sk_live_')) {
        wp_send_json_error([
            'message' => __('Apple Pay LIVE mode requires a secret key starting with sk_live_.', 'bw'),
        ]);
    }

    $site_domain = wp_parse_url(home_url('/'), PHP_URL_HOST);
    $site_domain = is_string($site_domain) ? strtolower(trim($site_domain)) : '';
    if ('' === $site_domain) {
        wp_send_json_error(['message' => __('Unable to detect your site domain.', 'bw')]);
    }

    $response = wp_remote_get(
        'https://api.stripe.com/v1/payment_method_domains?limit=100',
        [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
            ],
        ]
    );

    if (is_wp_error($response)) {
        wp_send_json_error([
            'message' => sprintf(
                /* translators: %s: WP error message */
                __('Unable to reach Stripe API: %s', 'bw'),
                $response->get_error_message()
            ),
        ]);
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $payload = json_decode($body, true);

    if ($status_code < 200 || $status_code >= 300) {
        $stripe_error = '';
        if (is_array($payload) && isset($payload['error']['message'])) {
            $stripe_error = sanitize_text_field((string) $payload['error']['message']);
        }

        wp_send_json_error([
            'message' => $stripe_error
                ? sprintf(__('Stripe API error: %s', 'bw'), $stripe_error)
                : __('Stripe API rejected the request while checking domain verification.', 'bw'),
        ]);
    }

    if (!is_array($payload) || !isset($payload['data']) || !is_array($payload['data'])) {
        wp_send_json_error(['message' => __('Unexpected Stripe response while checking domain verification.', 'bw')]);
    }

    $domain_variants = array_unique(array_filter([
        $site_domain,
        preg_replace('/^www\./', '', $site_domain),
        'www.' . preg_replace('/^www\./', '', $site_domain),
    ]));

    $matched = null;
    foreach ($payload['data'] as $item) {
        if (!is_array($item) || empty($item['domain_name'])) {
            continue;
        }
        $domain_name = strtolower((string) $item['domain_name']);
        if (in_array($domain_name, $domain_variants, true)) {
            $matched = $item;
            break;
        }
    }

    if (null === $matched) {
        wp_send_json_error([
            'message' => sprintf(
                /* translators: %s: domain name */
                __('Domain not found in Stripe Payment Method Domains: %s. Add and verify it in Stripe Dashboard > Settings > Payment method domains.', 'bw'),
                $site_domain
            ),
        ]);
    }

    $is_enabled = isset($matched['enabled']) ? (bool) $matched['enabled'] : false;
    $matched_domain = sanitize_text_field((string) $matched['domain_name']);

    if (!$is_enabled) {
        wp_send_json_error([
            'message' => sprintf(
                /* translators: %s: domain name */
                __('Domain found but not enabled: %s. Enable it in Stripe Payment method domains.', 'bw'),
                $matched_domain
            ),
        ]);
    }

    wp_send_json_success([
        'message' => sprintf(
            /* translators: %s: domain name */
            __('Domain verified and enabled in Stripe: %s', 'bw'),
            $matched_domain
        ),
        'domain' => $matched_domain,
        'enabled' => true,
    ]);
}
