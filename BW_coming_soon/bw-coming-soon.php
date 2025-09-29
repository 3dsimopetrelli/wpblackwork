<?php
/*
Submodule: BW Coming Soon
Description: Parte del plugin wpblackwork per mostrare una pagina Coming Soon personalizzata.
*/

if (!defined('ABSPATH')) {
    exit;
}

// Includi funzioni e admin
require_once plugin_dir_path(__FILE__) . 'includes/functions.php';
require_once plugin_dir_path(__FILE__) . 'admin/settings-page.php';

// Attivazione: imposta opzione di default
function bw_coming_soon_activate() {
    add_option('bw_coming_soon_active', 0);
}
register_activation_hook(__FILE__, 'bw_coming_soon_activate');
?>
