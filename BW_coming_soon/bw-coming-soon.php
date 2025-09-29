<?php
/*
Submodule: BW Coming Soon
Description: Parte del plugin wpblackwork per mostrare una pagina Coming Soon personalizzata con video background e form newsletter collegato a Brevo.
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/functions.php';
require_once plugin_dir_path(__FILE__) . 'admin/settings-page.php';

function bw_coming_soon_activate() {
    add_option('bw_coming_soon_active', 0);
}
register_activation_hook(__FILE__, 'bw_coming_soon_activate');
?>
