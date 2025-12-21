<?php
if (!defined('ABSPATH')) {
    exit;
}

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

// Gestione iscrizione newsletter con Brevo
function bw_handle_subscription() {
    if (isset($_POST['bw_subscribe'])) {
        $email = sanitize_email($_POST['bw_email']);

        if (!empty($email)) {
            $api_key = "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";

            $url = "https://api.brevo.com/v3/contacts";
            $body = array(
                "email" => $email,
                "listIds" => array(4) // ID della lista BW list 2025
            );

            $response = wp_remote_post($url, array(
                "headers" => array(
                    "accept" => "application/json",
                    "api-key" => $api_key,
                    "content-type" => "application/json",
                ),
                "body" => json_encode($body),
            ));

            if (!is_wp_error($response)) {
                wp_redirect(add_query_arg("bw_subscribed", "1", $_SERVER["REQUEST_URI"]));
                exit;
            }
        }
    }
}
add_action("init", "bw_handle_subscription");
