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

// Legacy public Brevo subscription handler intentionally removed.
// Security hardening: this module no longer accepts unauthenticated POST-to-Brevo
// submissions with embedded credentials.
