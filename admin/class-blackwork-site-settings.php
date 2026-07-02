<?php
/**
 * Blackwork Site Settings Page
 *
 * Pagina unificata sotto Settings con tab per Cart Pop-up e BW Coming Soon
 *
 * @package BW_Elementor_Widgets
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BW_SITE_LAYOUT_OPTION')) {
    define('BW_SITE_LAYOUT_OPTION', 'bw_site_layout_settings_v1');
}

// --- Runtime split (3a, BW-TASK-20260629): callbacks moved to focused files.
// Required before the hook registrations below so every referenced function is
// defined at load time. All function names + registrations are preserved. ---
require_once __DIR__ . '/site-settings/menu.php';
require_once __DIR__ . '/site-settings/assets.php';
require_once __DIR__ . '/site-settings/gateway-validators.php';
require_once __DIR__ . '/site-settings/page-dispatch.php';
require_once __DIR__ . '/site-settings/product-import-export/csv-template.php';
require_once __DIR__ . '/site-settings/product-import-export/digital-csv-builder.php';
require_once __DIR__ . '/site-settings/product-import-export/export-engine.php';
require_once __DIR__ . '/site-settings/product-import-export/import-engine.php';
require_once __DIR__ . '/site-settings/product-import-export/import-locking.php';
require_once __DIR__ . '/site-settings/tabs/info-layout-labels.php';
require_once __DIR__ . '/site-settings/tabs/account.php';
require_once __DIR__ . '/site-settings/tabs/checkout.php';
require_once __DIR__ . '/site-settings/tabs/cart-shipping-redirect-comingsoon.php';
require_once __DIR__ . '/site-settings/tabs/import-product.php';
require_once __DIR__ . '/site-settings/layout-runtime.php';

add_action('admin_menu', 'bw_site_settings_menu');
add_action('admin_menu', 'bw_product_labels_admin_menu', 61);
add_action('admin_menu', 'bw_site_settings_force_default_submenu', 999);
add_action('admin_enqueue_scripts', 'bw_site_settings_admin_menu_icon_styles');
add_action('admin_enqueue_scripts', 'bw_admin_enqueue_ui_kit_assets', 12);
add_action('admin_enqueue_scripts', 'bw_site_settings_admin_assets');
add_action('wp_ajax_bw_google_pay_test_connection', 'bw_google_pay_test_connection_ajax_handler');
add_action('wp_ajax_bw_google_maps_test_connection', 'bw_google_maps_test_connection_ajax_handler');
add_action('wp_ajax_bw_klarna_test_connection', 'bw_klarna_test_connection_ajax_handler');
add_action('wp_ajax_bw_apple_pay_test_connection', 'bw_apple_pay_test_connection_ajax_handler');
add_action('wp_ajax_bw_apple_pay_verify_domain', 'bw_apple_pay_verify_domain_ajax_handler');
add_action('admin_init', 'bw_product_import_template_maybe_download', 4);
add_action('admin_init', 'bw_digital_csv_builder_maybe_download', 5);
add_action('admin_init', 'bw_export_maybe_handle_admin_request', 5);

// bw_site_render_google_pay_tab() removed — settings are managed inside
// bw_site_render_checkout_tab() under the "Google Pay" sub-tab.
