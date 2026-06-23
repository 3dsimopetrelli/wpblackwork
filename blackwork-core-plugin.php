<?php
/**
 * Plugin Name: Blackwork Core Plugin
 * Description: Custom Elementor widgets and runtime modules for Blackwork Core.
 * Version: 2.1.0
 * Author: Simone Zanone & Mattia Maragno
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BW_MEW_URL')) {
    define('BW_MEW_URL', plugin_dir_url(__FILE__));
}

if (!defined('BW_MEW_PATH')) {
    define('BW_MEW_PATH', plugin_dir_path(__FILE__));
}

if (!defined('BLACKWORK_PLUGIN_VERSION')) {
    define('BLACKWORK_PLUGIN_VERSION', '2.1.0');
}

/**
 * Emergency recovery override.
 *
 * Define BLACKWORK_ALLOW_DEFAULT_WP_LOGIN as true in wp-config.php only as a
 * last-resort recovery measure if custom login protection locks you out.
 */

/**
 * SVG upload security (functions + filter registrations).
 *
 * Extracted to includes/svg-upload/svg-upload-handler.php (Phase 1).
 */
require_once plugin_dir_path(__FILE__) . 'includes/svg-upload/svg-upload-handler.php';

// Gestione redirect personalizzati
if (file_exists(plugin_dir_path(__FILE__) . 'includes/class-bw-redirects.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/class-bw-redirects.php';
}

// Duplicate page — adds a "Duplicate" row action to Pages and Posts
if (is_admin() && file_exists(plugin_dir_path(__FILE__) . 'includes/class-bw-duplicate-page.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/class-bw-duplicate-page.php';
}


// Includi il modulo BW Coming Soon
if (file_exists(plugin_dir_path(__FILE__) . 'BW_coming_soon/bw-coming-soon.php')) {
    require_once plugin_dir_path(__FILE__) . 'BW_coming_soon/bw-coming-soon.php';
}

// Includi il modulo BW Cart Pop-Up
if (file_exists(plugin_dir_path(__FILE__) . 'cart-popup/cart-popup.php')) {
    require_once plugin_dir_path(__FILE__) . 'cart-popup/cart-popup.php';
}

// Includi la pagina unificata Blackwork Site Settings
if (file_exists(plugin_dir_path(__FILE__) . 'includes/woocommerce-overrides/product-labels.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/woocommerce-overrides/product-labels.php';
}

// Includi la pagina unificata Blackwork Site Settings
if (file_exists(plugin_dir_path(__FILE__) . 'admin/class-blackwork-site-settings.php')) {
    require_once plugin_dir_path(__FILE__) . 'admin/class-blackwork-site-settings.php';
}

// SEO runtime compatibility layer (Rank Math + fallback metadata guards)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/seo/runtime-seo.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/seo/runtime-seo.php';
}

// Custom Header module (server-rendered, no Elementor dependency)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/modules/header/header-module.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/modules/header/header-module.php';
}

// Shared Search Engine module (filter/search domain)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/modules/search-engine/search-engine-module.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/modules/search-engine/search-engine-module.php';
}

// Search Surface module (plugin-owned results page consumer)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/modules/search-surface/search-surface-module.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/modules/search-surface/search-surface-module.php';
}

if (function_exists('bw_ss_on_plugin_activation')) {
    register_activation_hook(__FILE__, 'bw_ss_on_plugin_activation');
}

// Custom WordPress Login Shortcut module
if (file_exists(plugin_dir_path(__FILE__) . 'includes/modules/custom-wp-login/custom-wp-login-module.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/modules/custom-wp-login/custom-wp-login-module.php';
}

if (function_exists('bw_custom_wp_login_on_activation')) {
    register_activation_hook(__FILE__, 'bw_custom_wp_login_on_activation');
}

// Theme Builder Lite module (fonts + footer template runtime)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/modules/theme-builder-lite/theme-builder-lite-module.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/modules/theme-builder-lite/theme-builder-lite-module.php';
}

// Media Folders module (Media Library virtual folders)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/modules/media-folders/media-folders-module.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/modules/media-folders/media-folders-module.php';
}

// System Status module (admin diagnostics dashboard)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/modules/system-status/system-status-module.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/modules/system-status/system-status-module.php';
}

// Reviews module (custom reviews domain)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/modules/reviews/reviews-module.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/modules/reviews/reviews-module.php';
}

// Licenses module (reusable license terms for variation disclosure)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/modules/licenses/licenses-module.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/modules/licenses/licenses-module.php';
}

// Elementor Sticky Sidebar — JS-based sticky for container elements
if (file_exists(plugin_dir_path(__FILE__) . 'includes/modules/elementor-sticky-sidebar/elementor-sticky-sidebar-module.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/modules/elementor-sticky-sidebar/elementor-sticky-sidebar-module.php';
}

if (file_exists(plugin_dir_path(__FILE__) . 'includes/modules/link-page/link-page-module.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/modules/link-page/link-page-module.php';
}

// Checkout fields manager (admin + frontend)
$bw_checkout_fields_admin = plugin_dir_path(__FILE__) . 'includes/admin/checkout-fields/class-bw-checkout-fields-admin.php';
$bw_checkout_fields_frontend = plugin_dir_path(__FILE__) . 'includes/admin/checkout-fields/class-bw-checkout-fields-frontend.php';

// Checkout subscribe manager (admin + frontend)
$bw_checkout_subscribe_admin = plugin_dir_path(__FILE__) . 'includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php';
$bw_checkout_subscribe_frontend = plugin_dir_path(__FILE__) . 'includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php';
$bw_brevo_client = plugin_dir_path(__FILE__) . 'includes/integrations/brevo/class-bw-brevo-client.php';
$bw_brevo_lists_service = plugin_dir_path(__FILE__) . 'includes/integrations/brevo/class-bw-brevo-lists-service.php';
$bw_mailmarketing_service = plugin_dir_path(__FILE__) . 'includes/integrations/brevo/class-bw-mailmarketing-service.php';
$bw_mailmarketing_subscription_channel = plugin_dir_path(__FILE__) . 'includes/integrations/brevo/class-bw-mailmarketing-subscription-channel.php';

if (file_exists($bw_checkout_fields_admin)) {
    require_once $bw_checkout_fields_admin;
}

if (file_exists($bw_checkout_fields_frontend)) {
    require_once $bw_checkout_fields_frontend;
}

if (file_exists($bw_brevo_client)) {
    require_once $bw_brevo_client;
}

if (file_exists($bw_brevo_lists_service)) {
    require_once $bw_brevo_lists_service;
}

if (file_exists($bw_mailmarketing_service)) {
    require_once $bw_mailmarketing_service;
}

if (file_exists($bw_mailmarketing_subscription_channel)) {
    require_once $bw_mailmarketing_subscription_channel;
}

if (file_exists($bw_checkout_subscribe_admin)) {
    require_once $bw_checkout_subscribe_admin;
}

if (file_exists($bw_checkout_subscribe_frontend)) {
    require_once $bw_checkout_subscribe_frontend;
}

if (class_exists('BW_Checkout_Fields_Admin')) {
    BW_Checkout_Fields_Admin::init();
}

if (class_exists('BW_Checkout_Fields_Frontend')) {
    BW_Checkout_Fields_Frontend::init();
}

if (class_exists('BW_Checkout_Subscribe_Admin')) {
    BW_Checkout_Subscribe_Admin::init();
}

if (class_exists('BW_Checkout_Subscribe_Frontend')) {
    BW_Checkout_Subscribe_Frontend::init();
}

if (class_exists('BW_MailMarketing_Subscription_Channel')) {
    BW_MailMarketing_Subscription_Channel::init();
}


// Helper functions
require_once __DIR__ . '/includes/helpers.php';

// Widget helper class - Shared utility methods for widgets
require_once __DIR__ . '/includes/class-bw-widget-helper.php';

// Product Card Renderer - Centralized card rendering
require_once __DIR__ . '/includes/woocommerce-overrides/class-bw-product-card-renderer.php';

// WooCommerce overrides
require_once __DIR__ . '/woocommerce/woocommerce-init.php';

// Elementor Dynamic Tags
function bw_load_elementor_dynamic_tags()
{
    $artist_tag_file = __DIR__ . '/includes/dynamic-tags/class-bw-artist-name-tag.php';

    if (file_exists($artist_tag_file)) {
        require_once $artist_tag_file;
    }
}
add_action('elementor/init', 'bw_load_elementor_dynamic_tags');

// Loader dei widget
require_once __DIR__ . '/includes/class-bw-widget-loader.php';

/**
 * Initialize plugin components at the 'init' action to ensure proper translation loading.
 * This prevents WordPress 6.7.0+ warnings about translations being loaded too early.
 */
function bw_initialize_plugin_components()
{
    // Metabox per prodotti digitali
    require_once plugin_dir_path(__FILE__) . 'metabox/digital-products-metabox.php';
    // Metabox Bibliographic Details
    require_once plugin_dir_path(__FILE__) . 'metabox/bibliographic-details-metabox.php';
    // Metabox Images Showcase
    require_once plugin_dir_path(__FILE__) . 'metabox/images-showcase-metabox.php';
    // Metabox Artist Name
    require_once plugin_dir_path(__FILE__) . 'metabox/artist-name-metabox.php';
    // Metabox Product Slider
    require_once plugin_dir_path(__FILE__) . 'includes/product-types/class-bw-product-slider-metabox.php';
    // Campo URL completo per categorie prodotto
    require_once plugin_dir_path(__FILE__) . 'includes/category-url-field.php';
    // Metabox Variation License HTML
    require_once plugin_dir_path(__FILE__) . 'metabox/variation-license-html-field.php';
}
add_action('init', 'bw_initialize_plugin_components', 5);

/**
 * Clean up removed account description option.
 */
function bw_cleanup_account_description_option()
{
    $options = [
        'bw_account_description',
        'bw_account_back_text',
        'bw_account_back_url',
    ];

    foreach ($options as $option) {
        if (false !== get_option($option)) {
            delete_option($option);
        }
    }
}
add_action('init', 'bw_cleanup_account_description_option', 6);

/**
 * Load WooCommerce-specific components after WooCommerce initializes.
 */
function bw_initialize_woocommerce_components()
{
    require_once plugin_dir_path(__FILE__) . 'includes/product-types/product-types-init.php';
}
add_action('woocommerce_init', 'bw_initialize_woocommerce_components', 5);

/**
 * Asset registration, deprecated-widget cleanup, and CDN/SRI injection.
 *
 * Extracted from this bootstrap (Phase 1, BW-TASK-20260623):
 *  - includes/assets/asset-registry.php         widget/embla/panel asset register + enqueue
 *  - includes/widgets/widget-unregistration.php deprecated-widget cleanup
 *  - includes/assets/cdn-sri-manager.php         CDN SRI attribute injection
 */
require_once plugin_dir_path(__FILE__) . 'includes/assets/asset-registry.php';
require_once plugin_dir_path(__FILE__) . 'includes/widgets/widget-unregistration.php';
require_once plugin_dir_path(__FILE__) . 'includes/assets/cdn-sri-manager.php';

// Aggiungi categoria personalizzata "Black Work Widgets"
add_action('elementor/elements/categories_registered', function ($elements_manager) {
    $elements_manager->add_category(
        'blackwork',
        [
            'title' => __('Black Work Widgets', 'bw'),
            'icon' => 'fa fa-plug',
        ]
    );
});

/**
 * Handler AJAX per la ricerca live dei prodotti
 */
/**
 * Handler AJAX per la ricerca live dei prodotti
 *
 * NOTE: Moved to includes/modules/header/frontend/ajax-search.php
 * The new custom header module handles this.
 */


// ── BW Product Slider — cache invalidation ──────────────────────────────────

/**
 * Delete all BW Product Slider query transients when a product is saved.
 *
 * WordPress has no delete-by-prefix API, so we use a targeted DB query.
 * This only runs on product save events, not on every page load.
 *
 * @param int $post_id
 */
function bw_ps_clear_query_cache( $post_id ) {
    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        return;
    }
    if ( 'product' !== get_post_type( $post_id ) ) {
        return;
    }

    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_bw_ps_%'
            OR option_name LIKE '_transient_timeout_bw_ps_%'"
    );
}
add_action( 'save_post', 'bw_ps_clear_query_cache' );

/**
 * Delete all BW Mosaic Slider query transients when post content changes.
 *
 * @param int $post_id Post ID.
 */
function bw_mosaic_slider_clear_query_cache( $post_id ) {
    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        return;
    }

    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_bw_ms_%'
            OR option_name LIKE '_transient_timeout_bw_ms_%'"
    );
}
add_action( 'save_post', 'bw_mosaic_slider_clear_query_cache' );
