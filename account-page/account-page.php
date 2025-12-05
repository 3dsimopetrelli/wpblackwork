<?php
/**
 * BW Account Page Module
 *
 * Gestisce la pagina di login personalizzata per WooCommerce My Account
 *
 * @package BW_Elementor_Widgets
 */

if (!defined('ABSPATH')) {
    exit;
}

// Definisci costanti per il modulo
if (!defined('BW_ACCOUNT_PAGE_PATH')) {
    define('BW_ACCOUNT_PAGE_PATH', plugin_dir_path(__FILE__));
}
if (!defined('BW_ACCOUNT_PAGE_URL')) {
    define('BW_ACCOUNT_PAGE_URL', plugin_dir_url(__FILE__));
}

/**
 * Registra gli asset CSS per la pagina account
 */
function bw_account_page_register_assets() {
    wp_register_style(
        'bw-account-page',
        BW_ACCOUNT_PAGE_URL . 'assets/css/bw-account-page.css',
        [],
        '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'bw_account_page_register_assets');

/**
 * Intercetta il template della pagina My Account di WooCommerce
 */
function bw_account_page_custom_template($template) {
    // Verifica se siamo sulla pagina My Account di WooCommerce
    if (is_account_page()) {
        // Se l'utente NON è loggato, usa il nostro template custom
        if (!is_user_logged_in()) {
            // Carica il CSS personalizzato
            wp_enqueue_style('bw-account-page');

            // Usa il nostro template custom
            $custom_template = BW_ACCOUNT_PAGE_PATH . 'frontend/account-login-template.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
    }

    return $template;
}
add_filter('template_include', 'bw_account_page_custom_template', 99);

/**
 * Funzione helper per recuperare le opzioni della pagina account
 */
function bw_get_account_page_option($option_name, $default = '') {
    return get_option($option_name, $default);
}
