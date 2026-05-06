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

if (!defined('BW_CORE_PLUGIN_FILE')) {
    define('BW_CORE_PLUGIN_FILE', __FILE__);
}

if (!defined('BLACKWORK_PLUGIN_VERSION')) {
    define('BLACKWORK_PLUGIN_VERSION', '2.1.0');
}

function bw_runtime_debug_notice()
{
    if (!is_admin()) {
        return;
    }

    if (!current_user_can('manage_options')) {
        return;
    }

    if (!isset($_GET['bw_runtime_debug'])) {
        return;
    }

    $debug_flag = sanitize_text_field(wp_unslash($_GET['bw_runtime_debug']));
    if ('1' !== (string) $debug_flag) {
        return;
    }

    $link_page_relative_path = 'includes/modules/link-page/link-page-module.php';
    $link_page_absolute_path = plugin_dir_path(__FILE__) . $link_page_relative_path;

    $data = [
        '__FILE__' => __FILE__,
        'plugin_dir_path' => plugin_dir_path(__FILE__),
        'plugin_basename' => plugin_basename(__FILE__),
        'link_page_module_exists' => file_exists($link_page_absolute_path),
        'link_page_module_path' => $link_page_absolute_path,
        'bw_link_page_add_admin_menu_exists' => function_exists('bw_link_page_add_admin_menu'),
        'bw_link_page_module_loaded_marker' => defined('BW_LINK_PAGE_MODULE_LOADED'),
    ];

    echo '<div class="notice notice-info"><p><strong>BW Runtime Debug</strong></p><pre style="white-space:pre-wrap;">' . esc_html(wp_json_encode($data, JSON_PRETTY_PRINT)) . '</pre></div>';
}
add_action('admin_notices', 'bw_runtime_debug_notice');

/**
 * Enable safe SVG uploads for administrators/editors and prevent raster metadata processing.
 */
function bw_mew_allow_svg_uploads($mimes)
{
    if (current_user_can('manage_options')) {
        $mimes['svg'] = 'image/svg+xml';
    }

    return $mimes;
}
add_filter('upload_mimes', 'bw_mew_allow_svg_uploads');

function bw_mew_get_svg_upload_error_message($type)
{
    $messages = [
        'svgz_not_allowed' => __('SVG upload blocked: compressed SVGZ files are not allowed.', 'bw-elementor-widgets'),
        'read_failed' => __('SVG upload blocked: file could not be read for validation.', 'bw-elementor-widgets'),
        'invalid_xml' => __('SVG upload blocked: file is malformed or not a valid SVG document.', 'bw-elementor-widgets'),
        'unsafe_content' => __('SVG upload blocked: unsafe content was detected.', 'bw-elementor-widgets'),
        'sanitize_failed' => __('SVG upload blocked: sanitizer failed to produce a safe result.', 'bw-elementor-widgets'),
        'write_failed' => __('SVG upload blocked: sanitized content could not be persisted.', 'bw-elementor-widgets'),
    ];

    return isset($messages[$type]) ? $messages[$type] : __('SVG upload blocked: validation failed.', 'bw-elementor-widgets');
}

function bw_mew_get_svg_allowed_tags()
{
    return [
        'svg' => [
            'class' => true,
            'id' => true,
            'xmlns' => true,
            'xmlns:xlink' => true,
            'viewbox' => true,
            'width' => true,
            'height' => true,
            'role' => true,
            'aria-hidden' => true,
            'focusable' => true,
            'preserveaspectratio' => true,
        ],
        'g' => [
            'class' => true,
            'id' => true,
            'transform' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'opacity' => true,
            'clip-path' => true,
            'mask' => true,
            'filter' => true,
        ],
        'path' => [
            'class' => true,
            'id' => true,
            'd' => true,
            'transform' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'stroke-linecap' => true,
            'stroke-linejoin' => true,
            'stroke-miterlimit' => true,
            'stroke-dasharray' => true,
            'stroke-dashoffset' => true,
            'opacity' => true,
            'fill-rule' => true,
            'clip-rule' => true,
        ],
        'rect' => [
            'class' => true,
            'id' => true,
            'x' => true,
            'y' => true,
            'width' => true,
            'height' => true,
            'rx' => true,
            'ry' => true,
            'transform' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'opacity' => true,
        ],
        'circle' => [
            'class' => true,
            'id' => true,
            'cx' => true,
            'cy' => true,
            'r' => true,
            'transform' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'opacity' => true,
        ],
        'ellipse' => [
            'class' => true,
            'id' => true,
            'cx' => true,
            'cy' => true,
            'rx' => true,
            'ry' => true,
            'transform' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'opacity' => true,
        ],
        'line' => [
            'class' => true,
            'id' => true,
            'x1' => true,
            'y1' => true,
            'x2' => true,
            'y2' => true,
            'transform' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'opacity' => true,
        ],
        'polyline' => [
            'class' => true,
            'id' => true,
            'points' => true,
            'transform' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'opacity' => true,
        ],
        'polygon' => [
            'class' => true,
            'id' => true,
            'points' => true,
            'transform' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'opacity' => true,
        ],
        'title' => [],
        'desc' => [],
        'defs' => [],
        'clippath' => [
            'id' => true,
            'clipPathUnits' => true,
        ],
        'mask' => [
            'id' => true,
            'x' => true,
            'y' => true,
            'width' => true,
            'height' => true,
            'maskUnits' => true,
            'maskContentUnits' => true,
        ],
        'symbol' => [
            'id' => true,
            'viewbox' => true,
            'preserveaspectratio' => true,
        ],
        'use' => [
            'href' => true,
            'xlink:href' => true,
            'x' => true,
            'y' => true,
            'width' => true,
            'height' => true,
            'transform' => true,
        ],
        'lineargradient' => [
            'id' => true,
            'x1' => true,
            'y1' => true,
            'x2' => true,
            'y2' => true,
            'gradientunits' => true,
            'gradienttransform' => true,
            'spreadmethod' => true,
        ],
        'radialgradient' => [
            'id' => true,
            'cx' => true,
            'cy' => true,
            'r' => true,
            'fx' => true,
            'fy' => true,
            'gradientunits' => true,
            'gradienttransform' => true,
            'spreadmethod' => true,
        ],
        'stop' => [
            'offset' => true,
            'stop-color' => true,
            'stop-opacity' => true,
        ],
    ];
}

function bw_mew_svg_sanitize_content($content)
{
    $content = (string) $content;
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
    $content = str_replace("\0", '', $content);
    $content = preg_replace('/<\?(?:xml|php).*?\?>/is', '', $content);
    $content = preg_replace('/<!DOCTYPE.*?>/is', '', $content);
    $content = preg_replace('/<!ENTITY.*?>/is', '', $content);

    if (null === $content) {
        return '';
    }

    return wp_kses($content, bw_mew_get_svg_allowed_tags(), ['http', 'https', 'mailto']);
}

function bw_mew_svg_is_valid_document($svg)
{
    if ('' === trim((string) $svg)) {
        return false;
    }

    if (!class_exists('DOMDocument')) {
        return false;
    }

    $previous = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $loaded = $dom->loadXML((string) $svg, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_COMPACT);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if (!$loaded || !$dom->documentElement) {
        return false;
    }

    if ('svg' !== strtolower($dom->documentElement->nodeName)) {
        return false;
    }

    $dangerous_tags = ['script', 'foreignobject', 'iframe', 'object', 'embed', 'audio', 'video'];
    $xpath = new DOMXPath($dom);
    foreach ($dangerous_tags as $tag) {
        if ($xpath->query('//*[translate(local-name(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="' . $tag . '"]')->length > 0) {
            return false;
        }
    }

    $nodes = $dom->getElementsByTagName('*');
    foreach ($nodes as $node) {
        if (!$node->hasAttributes()) {
            continue;
        }

        foreach ($node->attributes as $attribute) {
            $attr_name = strtolower($attribute->nodeName);
            $attr_value = strtolower(trim((string) $attribute->nodeValue));

            if (0 === strpos($attr_name, 'on')) {
                return false;
            }

            if (in_array($attr_name, ['href', 'xlink:href'], true)) {
                if ('' === $attr_value || '#' === $attr_value || 0 === strpos($attr_value, '#')) {
                    continue;
                }

                if (0 === strpos($attr_value, 'javascript:') || 0 === strpos($attr_value, 'data:')) {
                    return false;
                }
            }
        }
    }

    return true;
}

function bw_mew_svg_upload_prefilter($file)
{
    $filename = isset($file['name']) ? (string) $file['name'] : '';
    $tmp_name = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($extension, ['svg', 'svgz'], true)) {
        return $file;
    }

    if (!current_user_can('manage_options')) {
        $file['error'] = bw_mew_get_svg_upload_error_message('unsafe_content');
        return $file;
    }

    if ('svgz' === $extension) {
        $file['error'] = bw_mew_get_svg_upload_error_message('svgz_not_allowed');
        return $file;
    }

    if ('' === $tmp_name || !is_readable($tmp_name)) {
        $file['error'] = bw_mew_get_svg_upload_error_message('read_failed');
        return $file;
    }

    $raw_content = file_get_contents($tmp_name);
    if (false === $raw_content || '' === trim((string) $raw_content)) {
        $file['error'] = bw_mew_get_svg_upload_error_message('read_failed');
        return $file;
    }

    if (false !== strpos((string) $raw_content, "\0")) {
        $file['error'] = bw_mew_get_svg_upload_error_message('invalid_xml');
        return $file;
    }

    $sanitized = bw_mew_svg_sanitize_content((string) $raw_content);
    if ('' === trim((string) $sanitized)) {
        $file['error'] = bw_mew_get_svg_upload_error_message('sanitize_failed');
        return $file;
    }

    if (!bw_mew_svg_is_valid_document($sanitized)) {
        $file['error'] = bw_mew_get_svg_upload_error_message('unsafe_content');
        return $file;
    }

    $bytes_written = file_put_contents($tmp_name, $sanitized, LOCK_EX);
    if (false === $bytes_written) {
        $file['error'] = bw_mew_get_svg_upload_error_message('write_failed');
        return $file;
    }

    return $file;
}
add_filter('wp_handle_upload_prefilter', 'bw_mew_svg_upload_prefilter');

function bw_mew_fix_svg_filetype($data, $file, $filename, $mimes)
{
    $extension = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));

    if ('svgz' === $extension) {
        return $data;
    }

    if ('svg' !== $extension) {
        return $data;
    }

    if (!is_string($file) || '' === $file || !is_readable($file)) {
        return $data;
    }

    $content = file_get_contents($file);
    if (false === $content) {
        return $data;
    }

    $sanitized = bw_mew_svg_sanitize_content((string) $content);
    if (!bw_mew_svg_is_valid_document($sanitized)) {
        return $data;
    }

    $filetype = wp_check_filetype($filename, $mimes);

    if (!empty($filetype['ext']) && 'svg' === $filetype['ext']) {
        $data['ext'] = 'svg';
        $data['type'] = 'image/svg+xml';
        $data['proper_filename'] = $filename;
    }

    return $data;
}
add_filter('wp_check_filetype_and_ext', 'bw_mew_fix_svg_filetype', 10, 4);

function bw_mew_skip_svg_metadata($metadata, $attachment_id)
{
    $mime = get_post_mime_type($attachment_id);

    if ('image/svg+xml' === $mime) {
        return [];
    }

    return $metadata;
}
add_filter('wp_generate_attachment_metadata', 'bw_mew_skip_svg_metadata', 10, 2);

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

add_action('init', 'bw_register_divider_style');
add_action('init', 'bw_register_button_widget_assets');
add_action('init', 'bw_register_about_menu_widget_assets');
add_action('init', 'bw_register_wallpost_widget_assets');
// about-menu: editor only — frontend assets handled via get_style_depends()/get_script_depends()
add_action('elementor/editor/after_enqueue_scripts', 'bw_enqueue_about_menu_widget_assets');
// bw-custom-class.css (full-section + layout utility classes)
add_action('elementor/frontend/after_enqueue_styles', 'bw_enqueue_custom_class_assets');
add_action('elementor/editor/after_enqueue_styles', 'bw_enqueue_custom_class_assets');
add_action('wp_enqueue_scripts', 'bw_enqueue_custom_class_assets', 35);
add_action('init', 'bw_register_product_grid_widget_assets');
// product-grid: editor only — frontend assets handled via get_style_depends()/get_script_depends()
add_action('elementor/editor/after_enqueue_scripts', 'bw_enqueue_product_grid_widget_assets');
add_action('init', 'bw_register_animated_banner_widget_assets');
add_action('init', 'bw_register_psychadelic_banner_widget_assets');
// animated-banner: editor only — frontend assets handled via get_style_depends()/get_script_depends()
add_action('elementor/editor/after_enqueue_scripts', 'bw_enqueue_animated_banner_widget_assets');
add_action('wp_enqueue_scripts', 'bw_enqueue_smart_header_assets');
add_action('init', 'bw_register_static_showcase_widget_assets');
add_action('init', 'bw_register_related_products_widget_assets');
// related-products: editor only — frontend assets handled via get_style_depends()/get_script_depends()
add_action('elementor/editor/after_enqueue_scripts', 'bw_enqueue_related_products_widget_assets');
add_action('init', 'bw_register_price_variation_widget_assets');
add_action('init', 'bw_register_trust_box_widget_assets');
add_action('init', 'bw_register_presentation_slide_widget_assets');
add_action('init', 'bw_register_basic_slide_widget_assets');
add_action('init', 'bw_register_product_slider_widget_assets');
add_action('init', 'bw_register_showcase_slide_widget_assets');
add_action('init', 'bw_register_mosaic_slider_widget_assets');
add_action('init', 'bw_register_hero_slide_widget_assets');
add_action('init', 'bw_register_product_details_widget_assets');
add_action('init', 'bw_register_reviews_widget_assets');
add_action('elementor/widgets/register', 'bw_unregister_removed_blackwork_widgets', 999);
add_action('elementor/widgets/widgets_registered', 'bw_unregister_removed_blackwork_widgets', 999);

/**
 * Defensive cleanup for removed widgets that may still be registered by stale caches/flows.
 *
 * @param mixed $widgets_manager Elementor widgets manager when provided by hook.
 *
 * @return void
 */
function bw_unregister_removed_blackwork_widgets($widgets_manager = null)
{
    if (null === $widgets_manager && class_exists('\Elementor\Plugin')) {
        $widgets_manager = \Elementor\Plugin::instance()->widgets_manager;
    }

    if (!is_object($widgets_manager)) {
        return;
    }

    $removed_widgets = [
        'bw-add-to-cart',
        'bw-add-to-cart-variation',
        'bw-wallpost',
        'bw-slick-slider',
    ];

    foreach ($removed_widgets as $widget_slug) {
        if (method_exists($widgets_manager, 'unregister')) {
            $widgets_manager->unregister($widget_slug);
            continue;
        }

        if (method_exists($widgets_manager, 'unregister_widget_type')) {
            $widgets_manager->unregister_widget_type($widget_slug);
        }
    }
}

function bw_register_embla_assets()
{
    $embla_core_file = __DIR__ . '/assets/js/vendor/embla-carousel.umd.js';
    $embla_core_ver = file_exists($embla_core_file) ? filemtime($embla_core_file) : '8.6.0';

    wp_register_script(
        'embla-js',
        plugin_dir_url(__FILE__) . 'assets/js/vendor/embla-carousel.umd.js',
        [],
        $embla_core_ver,
        true
    );

    $embla_autoplay_file = __DIR__ . '/assets/js/vendor/embla-carousel-autoplay.umd.js';
    $embla_autoplay_ver = file_exists($embla_autoplay_file) ? filemtime($embla_autoplay_file) : '8.1.7';

    wp_register_script(
        'embla-autoplay-js',
        plugin_dir_url(__FILE__) . 'assets/js/vendor/embla-carousel-autoplay.umd.js',
        ['embla-js'],
        $embla_autoplay_ver,
        true
    );

    $bw_embla_core_css_file = __DIR__ . '/assets/css/bw-embla-core.css';
    $bw_embla_core_css_ver = file_exists($bw_embla_core_css_file) ? filemtime($bw_embla_core_css_file) : '1.0.0';

    wp_register_style(
        'bw-embla-core-css',
        plugin_dir_url(__FILE__) . 'assets/css/bw-embla-core.css',
        [],
        $bw_embla_core_css_ver
    );

    $bw_embla_core_js_file = __DIR__ . '/assets/js/bw-embla-core.js';
    $bw_embla_core_js_ver = file_exists($bw_embla_core_js_file) ? filemtime($bw_embla_core_js_file) : '1.0.0';

    wp_register_script(
        'bw-embla-core-js',
        plugin_dir_url(__FILE__) . 'assets/js/bw-embla-core.js',
        ['embla-js', 'embla-autoplay-js'],
        $bw_embla_core_js_ver,
        true
    );
}
add_action('init', 'bw_register_embla_assets');

function bw_register_fullbleed_style()
{
    $bw_custom_class_css_file = __DIR__ . '/assets/css/bw-custom-class.css';
    $custom_class_version = file_exists($bw_custom_class_css_file) ? filemtime($bw_custom_class_css_file) : '1.0.0';

    wp_register_style(
        'bw-fullbleed-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-custom-class.css',
        [],
        $custom_class_version
    );
}
add_action('init', 'bw_register_fullbleed_style');

/**
 * Return SRI metadata for pinned static CDN assets.
 *
 * @return array
 */
function bw_get_cdn_sri_map()
{
    return [
        'styles' => [
            'select2' => [
                'src' => 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
                'integrity' => 'sha384-OXVF05DQEe311p6ohU11NwlnX08FzMCsyoXzGOaL+83dKAb3qS17yZJxESl8YrJQ',
            ],
        ],
        'scripts' => [
            'supabase-js' => [
                'src' => 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2.43.4/dist/umd/supabase.min.js',
                'integrity' => 'sha384-BV2dqVU6K3gwMR3iiAIxuWbMYbnQYo7u3jQXlR9cCWtBUVeIrrcuzn50r50eu9zk',
            ],
            'select2' => [
                'src' => 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
                'integrity' => 'sha384-d3UHjPdzJkZuk5H3qKYMLRyWLAQBJbby2yr2Q58hXXtAGF8RSNO9jpLDlKKPv5v3',
            ],
        ],
    ];
}

/**
 * Inject integrity/crossorigin on selected script tags.
 *
 * @param string $tag    Script tag HTML.
 * @param string $handle Script handle.
 * @param string $src    Script source URL.
 *
 * @return string
 */
function bw_add_cdn_sri_script_attributes($tag, $handle, $src)
{
    $map = bw_get_cdn_sri_map();
    if (empty($map['scripts'][$handle])) {
        return $tag;
    }

    $entry = $map['scripts'][$handle];
    if (empty($entry['src']) || empty($entry['integrity']) || strpos((string) $src, $entry['src']) !== 0) {
        return $tag;
    }

    if (strpos($tag, ' integrity=') !== false) {
        return $tag;
    }

    return str_replace(
        '<script ',
        '<script integrity="' . esc_attr($entry['integrity']) . '" crossorigin="anonymous" ',
        $tag
    );
}
add_filter('script_loader_tag', 'bw_add_cdn_sri_script_attributes', 10, 3);

/**
 * Inject integrity/crossorigin on selected stylesheet tags.
 *
 * @param string $html   Stylesheet tag HTML.
 * @param string $handle Style handle.
 * @param string $href   Stylesheet URL.
 * @param string $media  Media attribute.
 *
 * @return string
 */
function bw_add_cdn_sri_style_attributes($html, $handle, $href, $media)
{
    $map = bw_get_cdn_sri_map();
    if (empty($map['styles'][$handle])) {
        return $html;
    }

    $entry = $map['styles'][$handle];
    if (empty($entry['src']) || empty($entry['integrity']) || strpos((string) $href, $entry['src']) !== 0) {
        return $html;
    }

    if (strpos($html, ' integrity=') !== false) {
        return $html;
    }

    return preg_replace(
        '/\\s*\\/?>\\s*$/',
        ' integrity="' . esc_attr($entry['integrity']) . '" crossorigin="anonymous" />',
        $html,
        1
    );
}
add_filter('style_loader_tag', 'bw_add_cdn_sri_style_attributes', 10, 4);

function bw_enqueue_elementor_widget_panel_assets()
{
    $panel_css_file = __DIR__ . '/assets/css/bw-elementor-widget-panel.css';
    $panel_css_version = file_exists($panel_css_file) ? filemtime($panel_css_file) : '1.0.0';

    wp_enqueue_style(
        'bw-elementor-widget-panel-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-elementor-widget-panel.css',
        [],
        $panel_css_version
    );

    $panel_js_file = __DIR__ . '/assets/js/bw-elementor-widget-panel.js';
    $panel_js_version = file_exists($panel_js_file) ? filemtime($panel_js_file) : '1.0.0';

    wp_enqueue_script(
        'bw-elementor-widget-panel-script',
        plugin_dir_url(__FILE__) . 'assets/js/bw-elementor-widget-panel.js',
        ['jquery'],
        $panel_js_version,
        true
    );

    wp_localize_script(
        'bw-elementor-widget-panel-script',
        'bwElementorWidgetPanelData',
        [
            'productGridDesktopFilterGroupsByContext' => bw_get_product_grid_desktop_filter_groups_by_context(),
            'productCategoryContextByTermId' => bw_get_product_category_context_map_for_editor(),
        ]
    );
}
add_action('elementor/editor/after_enqueue_scripts', 'bw_enqueue_elementor_widget_panel_assets');

function bw_register_divider_style()
{
    $css_file = __DIR__ . '/assets/css/bw-divider.css';
    $version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_register_style(
        'bw-divider-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-divider.css',
        [],
        $version
    );
}

function bw_enqueue_custom_class_assets()
{
    wp_enqueue_style('bw-fullbleed-style');
}

function bw_register_button_widget_assets()
{
    bw_register_widget_assets('button');
}

function bw_register_big_text_widget_assets()
{
    bw_register_widget_assets('big-text', [], false);
}

function bw_register_go_to_app_widget_assets()
{
    bw_register_widget_assets('go-to-app', [], false);
}

function bw_footer_template_contains_widget_slug($widget_slug)
{
    if (!function_exists('bw_tbl_get_runtime_footer_template_id')) {
        return false;
    }

    $template_id = absint(bw_tbl_get_runtime_footer_template_id());
    if ($template_id <= 0) {
        return false;
    }

    $widget_slug = sanitize_key((string) $widget_slug);
    if ('' === $widget_slug) {
        return false;
    }

    $elementor_data = get_post_meta($template_id, '_elementor_data', true);
    if (is_string($elementor_data) && false !== strpos($elementor_data, $widget_slug)) {
        return true;
    }

    if (is_array($elementor_data)) {
        $encoded = wp_json_encode($elementor_data);
        if (is_string($encoded) && false !== strpos($encoded, $widget_slug)) {
            return true;
        }
    }

    $post_content = get_post_field('post_content', $template_id);

    return is_string($post_content) && false !== strpos($post_content, $widget_slug);
}

function bw_maybe_enqueue_go_to_app_widget_runtime_assets()
{
    if (is_admin()) {
        return;
    }

    if (!bw_footer_template_contains_widget_slug('bw-go-to-app')) {
        return;
    }

    if (!wp_style_is('bw-go-to-app-style', 'registered')) {
        bw_register_go_to_app_widget_assets();
    }

    wp_enqueue_style('bw-go-to-app-style');
}
add_action('wp_enqueue_scripts', 'bw_maybe_enqueue_go_to_app_widget_runtime_assets', 30);

function bw_register_about_menu_widget_assets()
{
    bw_register_widget_assets('about-menu', []);
}

function bw_register_wallpost_widget_assets()
{
    $css_file = __DIR__ . '/assets/css/bw-wallpost.css';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_register_style(
        'bw-wallpost-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-wallpost.css',
        [],
        $css_version
    );
}

function bw_register_related_products_widget_assets()
{
    $product_labels_css_file = __DIR__ . '/assets/css/bw-product-labels.css';
    $product_labels_css_version = file_exists($product_labels_css_file) ? filemtime($product_labels_css_file) : '1.0.0';

    wp_register_style(
        'bw-product-labels-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-product-labels.css',
        [],
        $product_labels_css_version
    );

    // Register product card CSS (shared)
    $product_card_css_file = __DIR__ . '/assets/css/bw-product-card.css';
    $product_card_css_version = file_exists($product_card_css_file) ? filemtime($product_card_css_file) : '1.0.0';
    $product_card_js_file = __DIR__ . '/assets/js/bw-product-card.js';
    $product_card_js_version = file_exists($product_card_js_file) ? filemtime($product_card_js_file) : '1.0.0';

    wp_register_style(
        'bw-product-card-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-product-card.css',
        ['bw-product-labels-style'],
        $product_card_css_version
    );

    wp_register_script(
        'bw-product-card-script',
        plugin_dir_url(__FILE__) . 'assets/js/bw-product-card.js',
        [],
        $product_card_js_version,
        true
    );

    // Register related products widget CSS
    $css_file = __DIR__ . '/assets/css/bw-related-products.css';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_register_style(
        'bw-related-products-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-related-products.css',
        ['bw-wallpost-style', 'bw-product-card-style'],
        $css_version
    );
}

function bw_enqueue_related_products_widget_assets()
{
    if (!wp_style_is('bw-product-card-style', 'registered') || !wp_style_is('bw-related-products-style', 'registered')) {
        bw_register_related_products_widget_assets();
    }

    // Enqueue product card CSS first (dependency)
    if (wp_style_is('bw-product-card-style', 'registered')) {
        wp_enqueue_style('bw-product-card-style');
    }

    // Then enqueue related products CSS
    if (wp_style_is('bw-related-products-style', 'registered')) {
        wp_enqueue_style('bw-related-products-style');
    }
}

function bw_enqueue_about_menu_widget_assets()
{
    if (!wp_style_is('bw-about-menu-style', 'registered') || !wp_script_is('bw-about-menu-script', 'registered')) {
        bw_register_about_menu_widget_assets();
    }

    if (wp_style_is('bw-about-menu-style', 'registered')) {
        wp_enqueue_style('bw-about-menu-style');
    }

    if (wp_script_is('bw-about-menu-script', 'registered')) {
        wp_enqueue_script('bw-about-menu-script');
    }
}

function bw_register_product_grid_widget_assets()
{
    static $product_grid_assets_localized = false;

    $css_file = __DIR__ . '/assets/css/bw-product-grid.css';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_register_style(
        'bw-product-grid-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-product-grid.css',
        ['bw-wallpost-style', 'bw-product-card-style'],
        $css_version
    );

    $js_file = __DIR__ . '/assets/js/bw-product-grid.js';
    $js_version = file_exists($js_file) ? filemtime($js_file) : '1.0.0';

    wp_register_script(
        'bw-product-grid-js',
        plugin_dir_url(__FILE__) . 'assets/js/bw-product-grid.js',
        ['jquery', 'imagesloaded', 'masonry'],
        $js_version,
        true
    );

    // Localize once to avoid duplicate globals/nonces across multi-hook registration.
    if (!$product_grid_assets_localized) {
        wp_localize_script(
            'bw-product-grid-js',
            'bwProductGridAjax',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bw_fpw_nonce'),
            ]
        );
        $product_grid_assets_localized = true;
    }
}

function bw_enqueue_product_grid_widget_assets()
{
    if (!wp_style_is('bw-product-grid-style', 'registered') || !wp_script_is('bw-product-grid-js', 'registered')) {
        bw_register_product_grid_widget_assets();
    }

    if (wp_style_is('bw-product-grid-style', 'registered')) {
        wp_enqueue_style('bw-product-grid-style');
    }

    if (wp_script_is('bw-product-grid-js', 'registered')) {
        wp_enqueue_script('bw-product-grid-js');
    }
}

function bw_enqueue_smart_header_assets()
{
    // Checks if the new custom header is enabled to avoid conflicts.
    // The new header uses 'includes/modules/header/assets/js/header-init.js' which now includes Dark Zone logic.
    if (function_exists('bw_header_is_enabled') && bw_header_is_enabled()) {
        return;
    }

    // Non caricare nell'admin di WordPress
    if (is_admin()) {
        return;
    }

    // Non caricare nell'editor di Elementor
    if (defined('ELEMENTOR_VERSION') && \Elementor\Plugin::$instance->preview->is_preview_mode()) {
        return;
    }

    // Registra e carica CSS
    $css_file = __DIR__ . '/assets/css/bw-smart-header.css';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '2.0.0';

    wp_enqueue_style(
        'bw-smart-header-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-smart-header.css',
        [],
        $css_version
    );

    // Registra e carica JavaScript
    $js_file = __DIR__ . '/assets/js/bw-smart-header.js';
    $js_version = file_exists($js_file) ? filemtime($js_file) : '2.0.0';

    wp_enqueue_script(
        'bw-smart-header-script',
        plugin_dir_url(__FILE__) . 'assets/js/bw-smart-header.js',
        ['jquery'],
        $js_version,
        true // Carica nel footer
    );

    // Passa configurazione al JavaScript tramite wp_localize_script
    wp_localize_script(
        'bw-smart-header-script',
        'bwSmartHeaderConfig',
        [
            'scrollDownThreshold' => 100,  // Pixel prima di nascondere header (scroll giù)
            'scrollUpThreshold' => 0,    // IMMEDIATO (anche 1px verso l'alto)
            'scrollDelta' => 1,    // Sensibilità rilevamento scroll
            'blurThreshold' => 50,   // Pixel prima di attivare blur
            'throttleDelay' => 16,   // ~60fps
            'headerSelector' => '.smart-header',
            'debug' => false // Imposta true per debug in console
        ]
    );
}

/**
 * Lightweight WooCommerce Loader Overrides
 */
function bw_enqueue_wc_loader_overrides()
{
    if (get_option('bw_loading_global_spinner_hidden', 1)) {
        $custom_css = "
            /* Hide default WooCommerce spinner and masks */
            .blockUI.blockOverlay {
                display: none !important;
                background: transparent !important;
                opacity: 0 !important;
            }
            .blockUI.blockMsg.blockElement,
            .woocommerce .loader,
            .woocommerce .blockUI.blockMsg::before {
                display: none !important;
                opacity: 0 !important;
            }
        ";
        wp_add_inline_style('woocommerce-general', $custom_css);
    }
}
add_action('wp_enqueue_scripts', 'bw_enqueue_wc_loader_overrides', 20);

function bw_register_animated_banner_widget_assets()
{
    $css_file = __DIR__ . '/assets/css/bw-animated-banner.css';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_register_style(
        'bw-animated-banner-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-animated-banner.css',
        [],
        $css_version
    );

    $js_file = __DIR__ . '/assets/js/bw-animated-banner.js';
    $js_version = file_exists($js_file) ? filemtime($js_file) : '1.0.0';

    wp_register_script(
        'bw-animated-banner-script',
        plugin_dir_url(__FILE__) . 'assets/js/bw-animated-banner.js',
        ['jquery'],
        $js_version,
        true
    );
}

function bw_enqueue_animated_banner_widget_assets()
{
    if (!wp_style_is('bw-animated-banner-style', 'registered') || !wp_script_is('bw-animated-banner-script', 'registered')) {
        bw_register_animated_banner_widget_assets();
    }

    if (wp_style_is('bw-animated-banner-style', 'registered')) {
        wp_enqueue_style('bw-animated-banner-style');
    }

    if (wp_script_is('bw-animated-banner-script', 'registered')) {
        wp_enqueue_script('bw-animated-banner-script');
    }
}

function bw_register_static_showcase_widget_assets()
{
    $css_file = __DIR__ . '/assets/css/bw-static-showcase.css';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_register_style(
        'bw-static-showcase-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-static-showcase.css',
        [],
        $css_version
    );

    $js_file = __DIR__ . '/assets/js/bw-static-showcase.js';
    $js_version = file_exists($js_file) ? filemtime($js_file) : '1.0.0';

    wp_register_script(
        'bw-static-showcase-script',
        plugin_dir_url(__FILE__) . 'assets/js/bw-static-showcase.js',
        [],
        $js_version,
        true
    );
}

function bw_register_psychadelic_banner_widget_assets()
{
    bw_register_widget_assets('psychadelic-banner', [], false);
}

function bw_register_price_variation_widget_assets()
{
    $css_file = __DIR__ . '/assets/css/bw-price-variation.css';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_register_style(
        'bw-price-variation-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-price-variation.css',
        [],
        $css_version
    );

    $js_file = __DIR__ . '/assets/js/bw-price-variation.js';
    $js_version = file_exists($js_file) ? filemtime($js_file) : '1.0.0';

    wp_register_script(
        'bw-price-variation-script',
        plugin_dir_url(__FILE__) . 'assets/js/bw-price-variation.js',
        ['jquery'],
        $js_version,
        true
    );
}

function bw_register_trust_box_widget_assets()
{
    $css_file = __DIR__ . '/assets/css/bw-trust-box.css';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_register_style(
        'bw-trust-box-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-trust-box.css',
        ['bw-embla-core-css'],
        $css_version
    );

    $js_file = __DIR__ . '/assets/js/bw-trust-box.js';
    $js_version = file_exists($js_file) ? filemtime($js_file) : '1.0.0';

    wp_register_script(
        'bw-trust-box-script',
        plugin_dir_url(__FILE__) . 'assets/js/bw-trust-box.js',
        ['jquery', 'embla-js', 'embla-autoplay-js', 'bw-embla-core-js'],
        $js_version,
        true
    );
}

function bw_register_reviews_widget_assets()
{
    $css_file = __DIR__ . '/assets/css/bw-reviews.css';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_register_style(
        'bw-reviews-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-reviews.css',
        [],
        $css_version
    );

    $js_file = __DIR__ . '/assets/js/bw-reviews.js';
    $js_version = file_exists($js_file) ? filemtime($js_file) : '1.0.0';

    wp_register_script(
        'bw-reviews-script',
        plugin_dir_url(__FILE__) . 'assets/js/bw-reviews.js',
        ['jquery'],
        $js_version,
        true
    );
}

/**
 * Localize bw-price-variation-script on any page where the widget may appear.
 */
function bw_localize_price_variation_widget_assets()
{
    if (!function_exists('get_woocommerce_currency_symbol')) {
        return;
    }

    wp_localize_script(
        'bw-price-variation-script',
        'bwPriceVariation',
        [
            'ajaxUrl'     => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('bw_price_variation_nonce'),
            'priceFormat' => [
                'symbol'             => html_entity_decode(get_woocommerce_currency_symbol()),
                'decimals'           => wc_get_price_decimals(),
                'decimal_separator'  => wc_get_price_decimal_separator(),
                'thousand_separator' => wc_get_price_thousand_separator(),
                'format'             => html_entity_decode(get_woocommerce_price_format()),
            ],
        ]
    );
}
add_action('wp_enqueue_scripts', 'bw_localize_price_variation_widget_assets', 15);

function bw_register_product_details_widget_assets()
{
    bw_register_widget_assets( 'product-details', [ 'jquery' ] );
}

function bw_register_presentation_slide_widget_assets()
{
    $css_file = __DIR__ . '/assets/css/bw-presentation-slide.css';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_register_style(
        'bw-presentation-slide-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-presentation-slide.css',
        ['bw-embla-core-css'],
        $css_version
    );

    $js_file = __DIR__ . '/assets/js/bw-presentation-slide.js';
    $js_version = file_exists($js_file) ? filemtime($js_file) : '1.0.0';

    wp_register_script(
        'bw-presentation-slide-script',
        plugin_dir_url(__FILE__) . 'assets/js/bw-presentation-slide.js',
        ['jquery', 'embla-js', 'embla-autoplay-js', 'bw-embla-core-js'],
        $js_version,
        true
    );
}

function bw_enqueue_presentation_slide_widget_assets()
{
    if (!wp_style_is('bw-presentation-slide-style', 'registered') || !wp_script_is('bw-presentation-slide-script', 'registered')) {
        bw_register_presentation_slide_widget_assets();
    }

    if (wp_style_is('bw-presentation-slide-style', 'registered')) {
        wp_enqueue_style('bw-presentation-slide-style');
    }

    if (wp_script_is('bw-presentation-slide-script', 'registered')) {
        wp_enqueue_script('bw-presentation-slide-script');
    }
}

function bw_register_basic_slide_widget_assets()
{
    $css_file = __DIR__ . '/assets/css/bw-basic-slide.css';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_register_style(
        'bw-basic-slide-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-basic-slide.css',
        ['bw-embla-core-css'],
        $css_version
    );

    $js_file = __DIR__ . '/assets/js/bw-basic-slide.js';
    $js_version = file_exists($js_file) ? filemtime($js_file) : '1.0.0';

    wp_register_script(
        'bw-basic-slide-script',
        plugin_dir_url(__FILE__) . 'assets/js/bw-basic-slide.js',
        ['jquery', 'embla-js', 'embla-autoplay-js', 'bw-embla-core-js'],
        $js_version,
        true
    );
}

function bw_register_product_slider_widget_assets()
{
    $css_file = __DIR__ . '/assets/css/bw-product-slider.css';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_register_style(
        'bw-product-slider-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-product-slider.css',
        ['bw-embla-core-css'],
        $css_version
    );

    $js_file = __DIR__ . '/assets/js/bw-product-slider.js';
    $js_version = file_exists($js_file) ? filemtime($js_file) : '1.0.0';

    wp_register_script(
        'bw-product-slider-script',
        plugin_dir_url(__FILE__) . 'assets/js/bw-product-slider.js',
        ['jquery', 'embla-js', 'embla-autoplay-js', 'bw-embla-core-js'],
        $js_version,
        true
    );
}

function bw_register_showcase_slide_widget_assets()
{
    $css_file = __DIR__ . '/assets/css/bw-showcase-slide.css';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_register_style(
        'bw-showcase-slide-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-showcase-slide.css',
        ['bw-embla-core-css'],
        $css_version
    );

    $js_file = __DIR__ . '/assets/js/bw-showcase-slide.js';
    $js_version = file_exists($js_file) ? filemtime($js_file) : '1.0.0';

    wp_register_script(
        'bw-showcase-slide-script',
        plugin_dir_url(__FILE__) . 'assets/js/bw-showcase-slide.js',
        ['jquery', 'embla-js', 'embla-autoplay-js', 'bw-embla-core-js'],
        $js_version,
        true
    );
}

function bw_register_mosaic_slider_widget_assets()
{
    $css_file = __DIR__ . '/assets/css/bw-mosaic-slider.css';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_register_style(
        'bw-mosaic-slider-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-mosaic-slider.css',
        ['bw-product-card-style', 'bw-embla-core-css'],
        $css_version
    );

    $js_file = __DIR__ . '/assets/js/bw-mosaic-slider.js';
    $js_version = file_exists($js_file) ? filemtime($js_file) : '1.0.0';

    wp_register_script(
        'bw-mosaic-slider-script',
        plugin_dir_url(__FILE__) . 'assets/js/bw-mosaic-slider.js',
        ['jquery', 'embla-js', 'embla-autoplay-js', 'bw-embla-core-js'],
        $js_version,
        true
    );
}

function bw_register_hero_slide_widget_assets()
{
    bw_register_widget_assets('hero-slide', ['jquery'], true);
}

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
