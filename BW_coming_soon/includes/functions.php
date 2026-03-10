<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get Coming Soon Brevo channel settings.
 *
 * @return array
 */
function bw_coming_soon_get_brevo_settings()
{
    $defaults = [
        'subscribe_enabled' => 0,
        'list_id_override' => 0,
        'channel_optin_mode' => 'inherit',
        'success_message' => __('Thanks for subscribing! Please check your inbox.', 'bw'),
        'error_message' => __('Unable to subscribe right now. Please try again later.', 'bw'),
    ];

    $stored = get_option('bw_coming_soon_brevo_settings', []);
    if (!is_array($stored)) {
        $stored = [];
    }

    $settings = array_merge($defaults, $stored);
    $settings['subscribe_enabled'] = !empty($settings['subscribe_enabled']) ? 1 : 0;
    $settings['list_id_override'] = absint($settings['list_id_override']);
    $settings['channel_optin_mode'] = sanitize_key((string) $settings['channel_optin_mode']);
    if (!in_array($settings['channel_optin_mode'], ['inherit', 'single_opt_in', 'double_opt_in'], true)) {
        $settings['channel_optin_mode'] = 'inherit';
    }
    $settings['success_message'] = sanitize_textarea_field((string) $settings['success_message']);
    $settings['error_message'] = sanitize_textarea_field((string) $settings['error_message']);

    return $settings;
}

/**
 * Resolve requester IP for lightweight throttling.
 *
 * @return string
 */
function bw_coming_soon_get_request_ip()
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    if ('' === $ip) {
        return 'unknown';
    }

    return strtolower($ip);
}

/**
 * Handle Coming Soon subscribe submission using canonical Brevo settings.
 */
function bw_coming_soon_handle_subscribe_submission()
{
    $request_method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper(sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD']))) : '';
    if ('POST' !== $request_method) {
        status_header(405);
        exit('Method Not Allowed.');
    }

    $redirect_url = home_url('/');
    if (wp_get_referer()) {
        $redirect_url = (string) wp_get_referer();
    }
    $redirect_url = remove_query_arg(['bw_cs'], $redirect_url);

    $nonce = isset($_POST['bw_coming_soon_subscribe_nonce'])
        ? sanitize_text_field(wp_unslash($_POST['bw_coming_soon_subscribe_nonce']))
        : '';
    if ('' === $nonce || !wp_verify_nonce($nonce, 'bw_coming_soon_subscribe')) {
        wp_safe_redirect(add_query_arg('bw_cs', 'err', $redirect_url));
        exit;
    }

    if (1 !== (int) get_option('bw_coming_soon_active', 0)) {
        wp_safe_redirect(add_query_arg('bw_cs', 'err', $redirect_url));
        exit;
    }

    $channel_settings = bw_coming_soon_get_brevo_settings();
    if (empty($channel_settings['subscribe_enabled'])) {
        wp_safe_redirect(add_query_arg('bw_cs', 'err', $redirect_url));
        exit;
    }

    $email = isset($_POST['bw_email']) ? sanitize_email(wp_unslash($_POST['bw_email'])) : '';
    if ('' === $email || !is_email($email) || empty($_POST['bw_privacy'])) {
        wp_safe_redirect(add_query_arg('bw_cs', 'err', $redirect_url));
        exit;
    }

    $cooldown_seconds = (int) apply_filters('bw_coming_soon_subscribe_cooldown_seconds', 120);
    $cooldown_seconds = max(30, min(600, $cooldown_seconds));
    $requester_ip = bw_coming_soon_get_request_ip();
    $cooldown_key = 'bw_cs_sub_rate_' . md5(strtolower($email) . '|' . $requester_ip);
    if (get_transient($cooldown_key)) {
        wp_safe_redirect(add_query_arg('bw_cs', 'err', $redirect_url));
        exit;
    }
    set_transient($cooldown_key, 1, $cooldown_seconds);

    if (!class_exists('BW_Mail_Marketing_Settings') || !class_exists('BW_Brevo_Client')) {
        wp_safe_redirect(add_query_arg('bw_cs', 'err', $redirect_url));
        exit;
    }

    $general_settings = BW_Mail_Marketing_Settings::get_general_settings();
    $api_key = isset($general_settings['api_key']) ? sanitize_text_field((string) $general_settings['api_key']) : '';
    if ('' === $api_key) {
        wp_safe_redirect(add_query_arg('bw_cs', 'err', $redirect_url));
        exit;
    }

    $list_id = $channel_settings['list_id_override'];
    if ($list_id <= 0) {
        $list_id = class_exists('BW_MailMarketing_Service')
            ? BW_MailMarketing_Service::resolve_marketing_list_id($general_settings)
            : (isset($general_settings['list_id']) ? absint($general_settings['list_id']) : 0);
    }

    if ($list_id <= 0) {
        wp_safe_redirect(add_query_arg('bw_cs', 'err', $redirect_url));
        exit;
    }

    $mode = $channel_settings['channel_optin_mode'];
    if ('inherit' === $mode) {
        $mode = (isset($general_settings['default_optin_mode']) && 'double_opt_in' === $general_settings['default_optin_mode'])
            ? 'double_opt_in'
            : 'single_opt_in';
    }

    $client = new BW_Brevo_Client($api_key, BW_Mail_Marketing_Settings::API_BASE_URL);
    $attributes = [
        'SOURCE' => 'coming_soon',
        'CONSENT_SOURCE' => 'coming_soon',
        'CONSENT_STATUS' => 'granted',
        'BW_ORIGIN_SYSTEM' => 'wp',
        'CONSENT_AT' => gmdate('c'),
    ];

    if ('double_opt_in' === $mode) {
        $template_id = isset($general_settings['double_optin_template_id']) ? absint($general_settings['double_optin_template_id']) : 0;
        $redirect_doi = isset($general_settings['double_optin_redirect_url']) ? esc_url_raw((string) $general_settings['double_optin_redirect_url']) : '';
        if ($template_id <= 0 || '' === $redirect_doi) {
            wp_safe_redirect(add_query_arg('bw_cs', 'err', $redirect_url));
            exit;
        }

        $sender = [];
        if (!empty($general_settings['sender_email'])) {
            $sender['email'] = sanitize_email((string) $general_settings['sender_email']);
        }
        if (!empty($general_settings['sender_name'])) {
            $sender['name'] = sanitize_text_field((string) $general_settings['sender_name']);
        }

        $result = $client->send_double_opt_in(
            $email,
            $template_id,
            $redirect_doi,
            [$list_id],
            $attributes,
            $sender
        );
    } else {
        $result = $client->upsert_contact(
            $email,
            $attributes,
            [$list_id]
        );
    }

    if (empty($result['success'])) {
        wp_safe_redirect(add_query_arg('bw_cs', 'err', $redirect_url));
        exit;
    }

    wp_safe_redirect(add_query_arg('bw_cs', 'ok', $redirect_url));
    exit;
}
add_action('admin_post_nopriv_bw_coming_soon_subscribe', 'bw_coming_soon_handle_subscribe_submission');
add_action('admin_post_bw_coming_soon_subscribe', 'bw_coming_soon_handle_subscribe_submission');

// Mostra la pagina coming soon se attiva
function bw_show_coming_soon() {
    // Se la modalità non è attiva, esci subito
    if (get_option('bw_coming_soon_active') != 1) {
        return;
    }

    // Evita di bloccare backend, API o processi CLI/cron
    if (
        is_user_logged_in() ||
        is_admin() ||
        (defined('REST_REQUEST') && REST_REQUEST) ||
        (defined('DOING_CRON') && DOING_CRON) ||
        (defined('WP_CLI') && WP_CLI)
    ) {
        return;
    }

    // Forza il client a non mettere in cache la pagina di coming soon
    status_header(503);
    nocache_headers();

    include plugin_dir_path(__FILE__) . '../public/coming-soon-template.php';
    exit;
}
add_action('template_redirect', 'bw_show_coming_soon');

// Legacy public Brevo subscription handler intentionally removed.
// Security hardening: this module no longer accepts unauthenticated POST-to-Brevo
// submissions with embedded credentials.
