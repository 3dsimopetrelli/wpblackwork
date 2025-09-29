<?php
if (!defined('ABSPATH')) {
    exit;
}

// Mostra la pagina coming soon se attiva
function bw_show_coming_soon() {
    if (!is_user_logged_in() && get_option('bw_coming_soon_active') == 1) {
        include plugin_dir_path(__FILE__) . '../public/coming-soon-template.php';
        exit;
    }
}
add_action('template_redirect', 'bw_show_coming_soon');
?>
