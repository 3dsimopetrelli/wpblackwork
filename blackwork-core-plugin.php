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


// Includi il modulo BW Coming Soon
if (file_exists(plugin_dir_path(__FILE__) . 'BW_coming_soon/bw-coming-soon.php')) {
    require_once plugin_dir_path(__FILE__) . 'BW_coming_soon/bw-coming-soon.php';
}

// Includi il modulo BW Cart Pop-Up
if (file_exists(plugin_dir_path(__FILE__) . 'cart-popup/cart-popup.php')) {
    require_once plugin_dir_path(__FILE__) . 'cart-popup/cart-popup.php';
}

// Includi la pagina unificata Blackwork Site Settings
if (file_exists(plugin_dir_path(__FILE__) . 'admin/class-blackwork-site-settings.php')) {
    require_once plugin_dir_path(__FILE__) . 'admin/class-blackwork-site-settings.php';
}

// Custom Header module (server-rendered, no Elementor dependency)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/modules/header/header-module.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/modules/header/header-module.php';
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
    // Register product card CSS (shared)
    $product_card_css_file = __DIR__ . '/assets/css/bw-product-card.css';
    $product_card_css_version = file_exists($product_card_css_file) ? filemtime($product_card_css_file) : '1.0.0';
    $product_card_js_file = __DIR__ . '/assets/js/bw-product-card.js';
    $product_card_js_version = file_exists($product_card_js_file) ? filemtime($product_card_js_file) : '1.0.0';

    wp_register_style(
        'bw-product-card-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-product-card.css',
        [],
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


/**
 * Handler AJAX per ottenere le subcategorie di una categoria
 */
function bw_fpw_get_allowed_post_types()
{
    return ['product', 'post'];
}

function bw_fpw_get_default_post_type()
{
    return 'product';
}

function bw_fpw_get_default_per_page()
{
    return 24;
}

function bw_fpw_get_max_per_page()
{
    return 100;
}

function bw_fpw_get_tag_source_posts_limit()
{
    return 300;
}

function bw_fpw_normalize_post_type($raw_post_type)
{
    $post_type = sanitize_key((string) $raw_post_type);
    $allowed_post_types = bw_fpw_get_allowed_post_types();

    if (!in_array($post_type, $allowed_post_types, true)) {
        return bw_fpw_get_default_post_type();
    }

    return $post_type;
}

function bw_fpw_normalize_widget_id($raw_widget_id)
{
    $widget_id = sanitize_text_field((string) $raw_widget_id);
    return substr($widget_id, 0, 64);
}

function bw_fpw_normalize_term_selector($raw_value)
{
    $value = sanitize_text_field((string) $raw_value);

    if ('all' === strtolower($value)) {
        return 'all';
    }

    $term_id = absint($value);
    return $term_id > 0 ? $term_id : 'all';
}

function bw_fpw_normalize_int_array($raw_values, $max_items = 50)
{
    $values = is_array($raw_values) ? $raw_values : [$raw_values];
    $normalized = [];

    foreach ($values as $value) {
        $int_value = absint($value);
        if ($int_value > 0) {
            $normalized[] = $int_value;
        }
    }

    $normalized = array_values(array_unique($normalized));

    if (count($normalized) > $max_items) {
        $normalized = array_slice($normalized, 0, $max_items);
    }

    return $normalized;
}

function bw_fpw_normalize_positive_int($raw_value, $default, $min, $max)
{
    $value = absint($raw_value);

    if ($value < $min) {
        $value = $default;
    }

    if ($value > $max) {
        $value = $max;
    }

    return $value;
}

function bw_fpw_normalize_bool($raw_value, $default = false)
{
    if (null === $raw_value) {
        return (bool) $default;
    }

    $normalized = filter_var($raw_value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    return null === $normalized ? (bool) $default : (bool) $normalized;
}

function bw_fpw_normalize_order_by($raw_order_by)
{
    $order_by = sanitize_key((string) $raw_order_by);
    $valid_order_by = ['date', 'modified', 'title', 'rand', 'id', 'year_int'];

    if (!in_array($order_by, $valid_order_by, true)) {
        return 'date';
    }

    return 'id' === $order_by ? 'ID' : $order_by;
}

function bw_fpw_normalize_order($raw_order)
{
    $order = strtoupper(sanitize_key((string) $raw_order));
    return in_array($order, ['ASC', 'DESC'], true) ? $order : 'DESC';
}

function bw_fpw_normalize_sort_key($raw_sort_key)
{
    $sort_key = sanitize_key((string) $raw_sort_key);
    $valid_sort_keys = ['default', 'recent', 'oldest', 'title_asc', 'title_desc', 'year_asc', 'year_desc'];

    return in_array($sort_key, $valid_sort_keys, true) ? $sort_key : 'default';
}

function bw_fpw_resolve_sort_config($sort_key, $default_order_by, $default_order, $post_type = 'product')
{
    $normalized_sort_key = bw_fpw_normalize_sort_key($sort_key);
    $effective_order_by = bw_fpw_normalize_order_by($default_order_by);
    $effective_order = bw_fpw_normalize_order($default_order);
    $query_args = [
        'orderby' => $effective_order_by,
        'order' => $effective_order,
    ];

    switch ($normalized_sort_key) {
        case 'recent':
            $effective_order_by = 'date';
            $effective_order = 'DESC';
            $query_args = [
                'orderby' => 'date',
                'order' => 'DESC',
            ];
            break;
        case 'oldest':
            $effective_order_by = 'date';
            $effective_order = 'ASC';
            $query_args = [
                'orderby' => 'date',
                'order' => 'ASC',
            ];
            break;
        case 'title_asc':
            $effective_order_by = 'title';
            $effective_order = 'ASC';
            $query_args = [
                'orderby' => 'title',
                'order' => 'ASC',
            ];
            break;
        case 'title_desc':
            $effective_order_by = 'title';
            $effective_order = 'DESC';
            $query_args = [
                'orderby' => 'title',
                'order' => 'DESC',
            ];
            break;
        case 'year_asc':
        case 'year_desc':
            if ('product' !== $post_type) {
                break;
            }

            $effective_order_by = 'meta_value_num';
            $effective_order = 'year_asc' === $normalized_sort_key ? 'ASC' : 'DESC';
            $query_args = [
                'meta_key' => bw_fpw_get_canonical_year_meta_key(),
                'meta_type' => 'NUMERIC',
                'orderby' => [
                    'meta_value_num' => $effective_order,
                    'date' => 'DESC',
                    'ID' => 'DESC',
                ],
                'order' => $effective_order,
            ];
            break;
        case 'default':
        default:
            break;
    }

    if ('rand' === $effective_order_by) {
        $effective_order = 'ASC';
        $query_args['orderby'] = 'rand';
        $query_args['order'] = 'ASC';
    }

    return [
        'sort_key' => $normalized_sort_key,
        'effective_order_by' => $effective_order_by,
        'effective_order' => $effective_order,
        'query_args' => $query_args,
    ];
}

function bw_fpw_normalize_image_size($raw_image_size)
{
    $image_size = sanitize_key((string) $raw_image_size);
    $allowed_sizes = ['thumbnail', 'medium', 'medium_large', 'large', 'full', 'woocommerce_thumbnail', 'woocommerce_single', 'woocommerce_gallery_thumbnail'];

    if (!in_array($image_size, $allowed_sizes, true)) {
        return 'large';
    }

    return $image_size;
}

function bw_fpw_normalize_image_mode($raw_image_mode)
{
    $image_mode = sanitize_key((string) $raw_image_mode);

    if (!in_array($image_mode, ['proportional', 'cover'], true)) {
        return 'proportional';
    }

    return $image_mode;
}

function bw_fpw_get_request_fingerprint()
{
    $remote_addr = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : 'unknown';
    $user_agent = substr($user_agent, 0, 128);

    return md5($remote_addr . '|' . $user_agent);
}

function bw_fpw_is_throttled_request($action_key)
{
    $is_logged_in = is_user_logged_in();

    // Authenticated users get higher limits keyed by user ID (accurate, avoids
    // IP collisions on shared networks). Anonymous users get tighter limits
    // keyed by IP + UA fingerprint.
    if ($is_logged_in) {
        $limits = [
            'bw_fpw_get_subcategories' => ['limit' => 300, 'window' => 60],
            'bw_fpw_get_tags'          => ['limit' => 300, 'window' => 60],
            'bw_fpw_filter_posts'      => ['limit' => 200, 'window' => 60],
        ];
        $fingerprint = 'u' . get_current_user_id();
    } else {
        $limits = [
            'bw_fpw_get_subcategories' => ['limit' => 60, 'window' => 60],
            'bw_fpw_get_tags'          => ['limit' => 50, 'window' => 60],
            'bw_fpw_filter_posts'      => ['limit' => 35, 'window' => 60],
        ];
        $fingerprint = bw_fpw_get_request_fingerprint();
    }

    $config = isset($limits[$action_key]) ? $limits[$action_key] : ['limit' => 40, 'window' => 60];
    $transient_key = 'bw_fpw_rl_' . md5($action_key . '|' . $fingerprint);
    $bucket = get_transient($transient_key);

    if (!is_array($bucket) || !isset($bucket['count'])) {
        $bucket = ['count' => 0];
    }

    $bucket['count'] = (int) $bucket['count'] + 1;
    set_transient($transient_key, $bucket, (int) $config['window']);

    return $bucket['count'] > (int) $config['limit'];
}

function bw_fpw_send_throttled_response($action_key, $widget_id = '')
{
    if ('bw_fpw_filter_posts' === $action_key) {
        $safe_widget_id = bw_fpw_normalize_widget_id($widget_id);
        ob_start();
        ?>
        <div class="bw-fpw-empty-state">
            <p class="bw-fpw-empty-message"><?php esc_html_e('No results found.', 'bw-elementor-widgets'); ?></p>
            <button class="elementor-button bw-fpw-reset-filters" data-widget-id="<?php echo esc_attr($safe_widget_id); ?>">
                <?php esc_html_e('RESET FILTERS', 'bw-elementor-widgets'); ?>
            </button>
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(
            [
                'html' => $html,
                'tags_html' => '',
                'available_tags' => [],
                'has_posts' => false,
                'throttled' => true,
            ]
        );
    }

    wp_send_json_success([]);
}

add_action('wp_ajax_bw_fpw_get_subcategories', 'bw_fpw_get_subcategories');
add_action('wp_ajax_nopriv_bw_fpw_get_subcategories', 'bw_fpw_get_subcategories');

function bw_fpw_get_subcategories()
{
    check_ajax_referer('bw_fpw_nonce', 'nonce');

    $category_id = bw_fpw_normalize_term_selector(isset($_POST['category_id']) ? wp_unslash($_POST['category_id']) : 'all');
    $post_type = bw_fpw_normalize_post_type(isset($_POST['post_type']) ? wp_unslash($_POST['post_type']) : bw_fpw_get_default_post_type());

    if (bw_fpw_is_throttled_request('bw_fpw_get_subcategories')) {
        bw_fpw_send_throttled_response('bw_fpw_get_subcategories');
    }

    // PERFORMANCE: Check transient cache first (5 minutes)
    $transient_key = 'bw_fpw_subcats_' . $post_type . '_' . $category_id;
    $cached_result = get_transient($transient_key);

    if (false !== $cached_result) {
        wp_send_json_success($cached_result);
        return;
    }

    $taxonomy = 'product' === $post_type ? 'product_cat' : 'category';

    $get_terms_args = [
        'taxonomy' => $taxonomy,
        'hide_empty' => true,
    ];

    if ('all' !== $category_id) {
        $get_terms_args['parent'] = $category_id;
    }

    $subcategories = get_terms($get_terms_args);

    if ('all' === $category_id && !is_wp_error($subcategories)) {
        $subcategories = array_filter(
            $subcategories,
            static function ($term) {
                return (int) $term->parent > 0;
            }
        );
    }

    if (is_wp_error($subcategories)) {
        wp_send_json_error(['message' => $subcategories->get_error_message()]);
    }

    $result = [];

    foreach ($subcategories as $subcat) {
        $result[] = [
            'term_id' => $subcat->term_id,
            'name' => $subcat->name,
            'count' => $subcat->count,
        ];
    }

    // PERFORMANCE: Cache result for 15 minutes
    set_transient($transient_key, $result, 15 * MINUTE_IN_SECONDS);

    wp_send_json_success($result);
}

/**
 * Handler AJAX per ottenere i tag di una categoria
 */
add_action('wp_ajax_bw_fpw_get_tags', 'bw_fpw_get_tags');
add_action('wp_ajax_nopriv_bw_fpw_get_tags', 'bw_fpw_get_tags');

function bw_fpw_get_tags()
{
    check_ajax_referer('bw_fpw_nonce', 'nonce');

    $category_id = bw_fpw_normalize_term_selector(isset($_POST['category_id']) ? wp_unslash($_POST['category_id']) : 'all');
    $post_type = bw_fpw_normalize_post_type(isset($_POST['post_type']) ? wp_unslash($_POST['post_type']) : bw_fpw_get_default_post_type());
    $subcategories = bw_fpw_normalize_int_array(isset($_POST['subcategories']) ? wp_unslash($_POST['subcategories']) : [], 50);
    $normalized_year_range = bw_fpw_normalize_year_range(
        isset($_POST['year_from']) ? wp_unslash($_POST['year_from']) : null,
        isset($_POST['year_to'])   ? wp_unslash($_POST['year_to'])   : null
    );
    $year_from = $normalized_year_range['from'];
    $year_to   = $normalized_year_range['to'];

    if (bw_fpw_is_throttled_request('bw_fpw_get_tags')) {
        bw_fpw_send_throttled_response('bw_fpw_get_tags');
    }

    // PERFORMANCE: Check transient cache first (5 minutes)
    // Canonicalize set-like subcategory input for stable cache keys.
    $subcategories_for_key = $subcategories;
    sort($subcategories_for_key, SORT_NUMERIC);
    $cache_params_hash = md5(wp_json_encode([
        'subcats'    => $subcategories_for_key,
        'year_from'  => $year_from,
        'year_to'    => $year_to,
    ]));
    $transient_key = 'bw_fpw_tags_' . $post_type . '_' . $category_id . '_' . $cache_params_hash;
    $cached_result = get_transient($transient_key);

    if (false !== $cached_result) {
        wp_send_json_success($cached_result);
        return;
    }

    // Get tags using existing helper function
    $tags = bw_fpw_get_related_tags_data($post_type, $category_id, $subcategories, '', $year_from, $year_to);

    if (empty($tags)) {
        // Cache empty result too to avoid repeated queries
        set_transient($transient_key, [], 15 * MINUTE_IN_SECONDS);
        wp_send_json_success([]);
        return;
    }

    // PERFORMANCE: Cache result for 15 minutes
    set_transient($transient_key, $tags, 15 * MINUTE_IN_SECONDS);

    wp_send_json_success($tags);
}

function bw_fpw_get_filtered_post_ids_for_tags($post_type, $category, $subcategories, $year_from = null, $year_to = null, $context_slug = '', $advanced_filters = [])
{
    return bw_fpw_get_candidate_post_ids_without_search(
        $post_type,
        $category,
        $subcategories,
        [],
        $year_from,
        $year_to,
        $context_slug,
        $advanced_filters,
        bw_fpw_get_tag_source_posts_limit()
    );
}

function bw_fpw_get_candidate_post_ids_without_search($post_type, $category, $subcategories = [], $tags = [], $year_from = null, $year_to = null, $context_slug = '', $advanced_filters = [], $posts_per_page = -1)
{
    $query_args = [
        'post_type' => $post_type,
        'post_status' => 'publish',
        'posts_per_page' => is_numeric($posts_per_page) ? (int) $posts_per_page : -1,
        'paged' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ];

    $tax_query = bw_fpw_build_tax_query($post_type, $category, $subcategories, $tags);

    if (!empty($tax_query)) {
        $query_args['tax_query'] = $tax_query;
    }

    if (null !== $year_from || null !== $year_to) {
        $canonical_year_key = bw_fpw_get_canonical_year_meta_key();

        if (null !== $year_from && null !== $year_to) {
            $query_args['meta_query'] = [[
                'key' => $canonical_year_key,
                'value' => [(int) $year_from, (int) $year_to],
                'compare' => 'BETWEEN',
                'type' => 'NUMERIC',
            ]];
        } elseif (null !== $year_from) {
            $query_args['meta_query'] = [[
                'key' => $canonical_year_key,
                'value' => (int) $year_from,
                'compare' => '>=',
                'type' => 'NUMERIC',
            ]];
        } else {
            $query_args['meta_query'] = [[
                'key' => $canonical_year_key,
                'value' => (int) $year_to,
                'compare' => '<=',
                'type' => 'NUMERIC',
            ]];
        }
    }

    $query = new WP_Query($query_args);
    $post_ids = array_values(array_map('absint', (array) $query->posts));

    if (!bw_fpw_has_active_advanced_filter_selections($advanced_filters)) {
        return $post_ids;
    }

    return bw_fpw_apply_advanced_filters_to_post_ids($post_ids, $context_slug, $advanced_filters);
}

function bw_fpw_normalize_search_query($search)
{
    if (!is_string($search)) {
        return '';
    }

    $normalized = sanitize_text_field(wp_unslash($search));
    $normalized = preg_replace('/\s+/', ' ', trim($normalized));

    if (!is_string($normalized)) {
        return '';
    }

    if (function_exists('mb_substr')) {
        return mb_substr($normalized, 0, 100);
    }

    return substr($normalized, 0, 100);
}

function bw_fpw_normalize_search_value($search)
{
    if (!is_string($search) || '' === $search) {
        return '';
    }

    $search = remove_accents(mb_strtolower($search, 'UTF-8'));
    $search = preg_replace('/\s+/', ' ', trim($search));

    return is_string($search) ? $search : '';
}

function bw_fpw_get_supported_product_family_slugs()
{
    return ['digital-collections', 'books', 'prints'];
}

function bw_fpw_get_canonical_year_meta_key()
{
    return '_bw_filter_year_int';
}

function bw_fpw_get_canonical_author_meta_key()
{
    return '_bw_filter_author_text';
}

function bw_fpw_get_canonical_artist_meta_key()
{
    return '_bw_filter_artist_text';
}

function bw_fpw_get_canonical_publisher_meta_key()
{
    return '_bw_filter_publisher_text';
}

function bw_fpw_get_canonical_source_meta_key()
{
    return '_bw_filter_source_text';
}

function bw_fpw_get_canonical_technique_meta_key()
{
    return '_bw_filter_technique_text';
}

function bw_fpw_get_advanced_filter_group_definitions()
{
    return [
        'artist' => [
            'label' => 'Artist',
            'contexts' => ['digital-collections', 'prints'],
            'canonical_key' => bw_fpw_get_canonical_artist_meta_key(),
            'source_map_key' => 'artist_keys',
            'searchable' => true,
        ],
        'author' => [
            'label' => 'Author',
            'contexts' => ['books'],
            'canonical_key' => bw_fpw_get_canonical_author_meta_key(),
            'source_map_key' => 'author_keys',
            'searchable' => true,
        ],
        'publisher' => [
            'label' => 'Publisher',
            'contexts' => ['digital-collections', 'books', 'prints'],
            'canonical_key' => bw_fpw_get_canonical_publisher_meta_key(),
            'source_map_key' => 'publisher_keys',
            'searchable' => true,
        ],
        'source' => [
            'label' => 'Source',
            'contexts' => ['digital-collections'],
            'canonical_key' => bw_fpw_get_canonical_source_meta_key(),
            'source_map_key' => 'source_keys',
            'searchable' => false,
        ],
        'technique' => [
            'label' => 'Technique',
            'contexts' => ['digital-collections', 'prints'],
            'canonical_key' => bw_fpw_get_canonical_technique_meta_key(),
            'source_map_key' => 'technique_keys',
            'searchable' => false,
        ],
    ];
}

function bw_fpw_get_advanced_filter_group_keys()
{
    return array_keys(bw_fpw_get_advanced_filter_group_definitions());
}

function bw_fpw_is_advanced_filter_group($group_key)
{
    return isset(bw_fpw_get_advanced_filter_group_definitions()[$group_key]);
}

function bw_fpw_get_canonical_meta_key_for_advanced_filter_group($group_key)
{
    $definitions = bw_fpw_get_advanced_filter_group_definitions();
    return isset($definitions[$group_key]['canonical_key']) ? (string) $definitions[$group_key]['canonical_key'] : '';
}

function bw_fpw_get_source_meta_map_key_for_filter_group($group_key)
{
    switch ($group_key) {
        case 'year':
            return 'year_keys';
        case 'author':
            return 'author_keys';
        default:
            $definitions = bw_fpw_get_advanced_filter_group_definitions();
            return isset($definitions[$group_key]['source_map_key']) ? (string) $definitions[$group_key]['source_map_key'] : '';
    }
}

function bw_fpw_get_all_filter_canonical_meta_keys()
{
    $keys = [
        bw_fpw_get_canonical_year_meta_key(),
        bw_fpw_get_canonical_author_meta_key(),
    ];

    foreach (bw_fpw_get_advanced_filter_group_keys() as $group_key) {
        $canonical_key = bw_fpw_get_canonical_meta_key_for_advanced_filter_group($group_key);
        if ('' !== $canonical_key) {
            $keys[] = $canonical_key;
        }
    }

    return array_values(array_unique($keys));
}

function bw_fpw_get_product_filter_source_meta_map()
{
    return [
        'digital-collections' => [
            'year_keys' => ['_digital_year'],
            'author_keys' => ['_bw_artist_name', '_digital_artist_name'],
            'artist_keys' => ['_digital_artist_name', '_bw_artist_name'],
            'publisher_keys' => ['_digital_publisher'],
            'source_keys' => ['_digital_source'],
            'technique_keys' => ['_digital_technique'],
        ],
        'books' => [
            'year_keys' => ['_bw_biblio_year'],
            'author_keys' => ['_bw_biblio_author', '_bw_artist_name', '_digital_artist_name'],
            'artist_keys' => [],
            'publisher_keys' => ['_bw_biblio_publisher'],
            'source_keys' => [],
            'technique_keys' => [],
        ],
        'prints' => [
            'year_keys' => ['_print_year'],
            'author_keys' => ['_print_artist', '_bw_artist_name', '_digital_artist_name'],
            'artist_keys' => ['_print_artist', '_bw_artist_name', '_digital_artist_name'],
            'publisher_keys' => ['_print_publisher'],
            'source_keys' => [],
            'technique_keys' => ['_print_technique'],
        ],
    ];
}

function bw_fpw_get_all_filter_source_meta_keys_for_group($group_key)
{
    $map_key = bw_fpw_get_source_meta_map_key_for_filter_group($group_key);
    $source_map = bw_fpw_get_product_filter_source_meta_map();
    $keys = [];

    if ('' === $map_key) {
        return [];
    }

    foreach ($source_map as $context_map) {
        if (!empty($context_map[$map_key]) && is_array($context_map[$map_key])) {
            $keys = array_merge($keys, $context_map[$map_key]);
        }
    }

    return array_values(array_unique(array_filter($keys)));
}

function bw_fpw_get_all_filter_source_year_meta_keys()
{
    return bw_fpw_get_all_filter_source_meta_keys_for_group('year');
}

function bw_fpw_get_all_filter_source_author_meta_keys()
{
    return bw_fpw_get_all_filter_source_meta_keys_for_group('author');
}

function bw_fpw_get_all_filter_relevant_meta_keys()
{
    return array_values(
        array_unique(
            array_merge(
                bw_fpw_get_all_filter_source_year_meta_keys(),
                bw_fpw_get_all_filter_source_author_meta_keys(),
                bw_fpw_get_all_filter_source_meta_keys_for_group('artist'),
                bw_fpw_get_all_filter_source_meta_keys_for_group('publisher'),
                bw_fpw_get_all_filter_source_meta_keys_for_group('source'),
                bw_fpw_get_all_filter_source_meta_keys_for_group('technique'),
                bw_fpw_get_all_filter_canonical_meta_keys()
            )
        )
    );
}

function bw_fpw_normalize_context_slug($context_slug)
{
    if (!is_string($context_slug)) {
        return '';
    }

    $normalized = sanitize_title(wp_unslash($context_slug));

    if ('mixed' === $normalized) {
        return 'mixed';
    }

    return in_array($normalized, bw_fpw_get_supported_product_family_slugs(), true) ? $normalized : '';
}

function bw_fpw_is_supported_context_slug($context_slug)
{
    return in_array(bw_fpw_normalize_context_slug($context_slug), bw_fpw_get_supported_product_family_slugs(), true);
}

function bw_fpw_resolve_product_family_slug_from_term_id($term_id, $taxonomy = 'product_cat')
{
    $term_id = absint($term_id);
    if ($term_id <= 0) {
        return '';
    }

    $term = get_term($term_id, $taxonomy);
    if (!$term instanceof WP_Term || is_wp_error($term)) {
        return '';
    }

    $supported = bw_fpw_get_supported_product_family_slugs();
    $lineage = array_reverse(get_ancestors($term_id, $taxonomy, 'taxonomy'));
    $lineage[] = $term_id;

    foreach ($lineage as $candidate_id) {
        $candidate = get_term((int) $candidate_id, $taxonomy);
        if ($candidate instanceof WP_Term && !is_wp_error($candidate) && in_array($candidate->slug, $supported, true)) {
            return $candidate->slug;
        }
    }

    return '';
}

function bw_fpw_resolve_product_family_slug_from_product($post_id)
{
    $post_id = absint($post_id);
    if ($post_id <= 0 || 'product' !== get_post_type($post_id)) {
        return '';
    }

    $term_ids = wp_get_post_terms($post_id, 'product_cat', ['fields' => 'ids']);
    if (is_wp_error($term_ids) || empty($term_ids)) {
        return '';
    }

    $slugs = [];
    foreach ($term_ids as $term_id) {
        $slug = bw_fpw_resolve_product_family_slug_from_term_id((int) $term_id, 'product_cat');
        if ('' !== $slug) {
            $slugs[$slug] = true;
        }
    }

    $resolved = array_keys($slugs);
    if (1 === count($resolved)) {
        return $resolved[0];
    }

    return count($resolved) > 1 ? 'mixed' : '';
}

function bw_fpw_extract_year_int($value)
{
    if (is_int($value) || is_float($value)) {
        $year = (int) $value;
        return $year > 0 ? $year : null;
    }

    if (!is_string($value)) {
        return null;
    }

    $value = trim($value);
    if ('' === $value) {
        return null;
    }

    if (preg_match('/(?<!\d)(\d{3,4})(?!\d)/', $value, $matches)) {
        $year = (int) $matches[1];
        return $year > 0 ? $year : null;
    }

    return null;
}

function bw_fpw_normalize_filter_token_label($value)
{
    if (!is_scalar($value)) {
        return '';
    }

    $normalized = sanitize_text_field(wp_unslash((string) $value));
    $normalized = preg_replace('/\s+/', ' ', trim($normalized));

    return is_string($normalized) ? $normalized : '';
}

function bw_fpw_normalize_filter_token_value($value)
{
    $label = bw_fpw_normalize_filter_token_label($value);

    if ('' === $label) {
        return '';
    }

    $normalized = remove_accents($label);
    $normalized = strtolower($normalized);
    $normalized = preg_replace('/\s+/', ' ', trim($normalized));

    return is_string($normalized) ? $normalized : '';
}

function bw_fpw_extract_filter_tokens_from_value($value)
{
    $segments = is_array($value) ? $value : explode(',', (string) $value);
    $tokens = [];

    foreach ($segments as $segment) {
        $label = bw_fpw_normalize_filter_token_label($segment);
        $normalized_value = bw_fpw_normalize_filter_token_value($label);

        if ('' === $label || '' === $normalized_value || isset($tokens[$normalized_value])) {
            continue;
        }

        $tokens[$normalized_value] = [
            'value' => $normalized_value,
            'label' => $label,
        ];
    }

    return array_values($tokens);
}

function bw_fpw_join_filter_token_labels_for_storage($tokens)
{
    $labels = [];

    foreach ((array) $tokens as $token) {
        if (!is_array($token) || empty($token['label'])) {
            continue;
        }

        $labels[] = (string) $token['label'];
    }

    return implode(', ', $labels);
}

function bw_fpw_normalize_filter_token_selection_array($values, $limit = 50)
{
    if (!is_array($values)) {
        $values = [$values];
    }

    $normalized = [];

    foreach ($values as $value) {
        $normalized_value = bw_fpw_normalize_filter_token_value($value);

        if ('' === $normalized_value || isset($normalized[$normalized_value])) {
            continue;
        }

        $normalized[$normalized_value] = $normalized_value;

        if (count($normalized) >= $limit) {
            break;
        }
    }

    return array_values($normalized);
}

function bw_fpw_normalize_author_text($value)
{
    if (!is_string($value)) {
        return '';
    }

    return trim(sanitize_text_field(wp_unslash($value)));
}

function bw_fpw_get_context_source_meta_map($context_slug)
{
    $map = bw_fpw_get_product_filter_source_meta_map();
    $normalized = bw_fpw_normalize_context_slug($context_slug);

    return isset($map[$normalized]) ? $map[$normalized] : null;
}

function bw_fpw_get_candidate_source_meta_keys($context_slug, $kind)
{
    $kind = bw_fpw_get_source_meta_map_key_for_filter_group($kind);
    $map = bw_fpw_get_context_source_meta_map($context_slug);

    if (is_array($map) && !empty($map[$kind])) {
        return $map[$kind];
    }

    switch ($kind) {
        case 'author_keys':
            return bw_fpw_get_all_filter_source_author_meta_keys();
        case 'year_keys':
            return bw_fpw_get_all_filter_source_year_meta_keys();
        case 'artist_keys':
            return bw_fpw_get_all_filter_source_meta_keys_for_group('artist');
        case 'publisher_keys':
            return bw_fpw_get_all_filter_source_meta_keys_for_group('publisher');
        case 'source_keys':
            return bw_fpw_get_all_filter_source_meta_keys_for_group('source');
        case 'technique_keys':
            return bw_fpw_get_all_filter_source_meta_keys_for_group('technique');
        default:
            return [];
    }
}

function bw_fpw_compute_canonical_year_for_product($post_id)
{
    $context_slug = bw_fpw_resolve_product_family_slug_from_product($post_id);
    $meta_keys = bw_fpw_get_candidate_source_meta_keys($context_slug, 'year');

    foreach ($meta_keys as $meta_key) {
        $year = bw_fpw_extract_year_int(get_post_meta($post_id, $meta_key, true));
        if (null !== $year) {
            return $year;
        }
    }

    return null;
}

function bw_fpw_compute_canonical_author_for_product($post_id)
{
    $context_slug = bw_fpw_resolve_product_family_slug_from_product($post_id);
    $meta_keys = bw_fpw_get_candidate_source_meta_keys($context_slug, 'author');

    $tokens = [];

    foreach ($meta_keys as $meta_key) {
        $tokens = array_merge($tokens, bw_fpw_extract_filter_tokens_from_value((string) get_post_meta($post_id, $meta_key, true)));
        if (!empty($tokens)) {
            return bw_fpw_join_filter_token_labels_for_storage($tokens);
        }
    }

    return '';
}

function bw_fpw_compute_canonical_text_for_product($post_id, $group_key)
{
    $context_slug = bw_fpw_resolve_product_family_slug_from_product($post_id);
    $meta_keys = bw_fpw_get_candidate_source_meta_keys($context_slug, $group_key);
    $seen = [];
    $tokens = [];

    foreach ($meta_keys as $meta_key) {
        foreach (bw_fpw_extract_filter_tokens_from_value((string) get_post_meta($post_id, $meta_key, true)) as $token) {
            if (empty($token['value']) || isset($seen[$token['value']])) {
                continue;
            }

            $seen[$token['value']] = true;
            $tokens[] = $token;
        }
    }

    return bw_fpw_join_filter_token_labels_for_storage($tokens);
}

function bw_fpw_sync_product_filter_meta($post_id)
{
    static $sync_in_progress = [];

    $post_id = absint($post_id);
    if ($post_id <= 0 || 'product' !== get_post_type($post_id)) {
        return;
    }

    if (!empty($sync_in_progress[$post_id])) {
        return;
    }

    $sync_in_progress[$post_id] = true;

    $canonical_year_key = bw_fpw_get_canonical_year_meta_key();
    $canonical_author_key = bw_fpw_get_canonical_author_meta_key();
    $advanced_group_definitions = bw_fpw_get_advanced_filter_group_definitions();

    $year = bw_fpw_compute_canonical_year_for_product($post_id);
    $author = bw_fpw_compute_canonical_author_for_product($post_id);

    if (null !== $year) {
        update_post_meta($post_id, $canonical_year_key, (int) $year);
    } else {
        delete_post_meta($post_id, $canonical_year_key);
    }

    if ('' !== $author) {
        update_post_meta($post_id, $canonical_author_key, $author);
    } else {
        delete_post_meta($post_id, $canonical_author_key);
    }

    foreach ($advanced_group_definitions as $group_key => $definition) {
        $canonical_key = isset($definition['canonical_key']) ? (string) $definition['canonical_key'] : '';
        if ('' === $canonical_key || ('author' === $group_key && $canonical_key === $canonical_author_key)) {
            continue;
        }

        $value = bw_fpw_compute_canonical_text_for_product($post_id, $group_key);

        if ('' !== $value) {
            update_post_meta($post_id, $canonical_key, $value);
        } else {
            delete_post_meta($post_id, $canonical_key);
        }
    }

    unset($sync_in_progress[$post_id]);
}

/**
 * Returns the current cache generation counter for a context slug.
 * Cached in a static variable so the option is read at most once per request.
 */
function bw_fpw_get_cache_generation($context_slug)
{
    static $cache = [];
    $opt = 'bw_fpw_cache_gen_' . ('' === $context_slug ? 'all' : sanitize_key($context_slug));
    if (!array_key_exists($opt, $cache)) {
        $cache[$opt] = (int) get_option($opt, 0);
    }
    return $cache[$opt];
}

/**
 * Increments the cache generation counter for each supplied context slug.
 * Old transients with the previous generation hash become unreachable and
 * expire naturally — no expensive DELETE LIKE query needed.
 *
 * @param string|string[] $context_slugs One or more context slugs to bump.
 */
function bw_fpw_bump_cache_generation($context_slugs)
{
    $slugs = array_unique(array_filter((array) $context_slugs, 'is_string'));
    foreach ($slugs as $slug) {
        $opt = 'bw_fpw_cache_gen_' . ('' === $slug ? 'all' : sanitize_key($slug));
        update_option($opt, (int) get_option($opt, 0) + 1, false);
    }
}

/**
 * Invalidates the product-grid transient cache.
 *
 * Pass one or more context slugs to invalidate only those contexts;
 * omit the argument for a full (all-contexts) invalidation.
 * Uses a generation counter instead of a nuclear DELETE LIKE query.
 *
 * @param string|string[]|null $context_slugs Contexts to invalidate, or null for all.
 */
function bw_fpw_clear_grid_transient_cache($context_slugs = null)
{
    if (null === $context_slugs) {
        // Nuclear: bump every known context + the catch-all buckets
        $all_slugs = array_merge(['', 'mixed'], bw_fpw_get_supported_product_family_slugs());
    } else {
        // Scoped: bump the given slug(s) and always include '' and 'mixed',
        // because mixed-context grids contain products from all families.
        $given = array_unique(array_filter(array_map('strval', (array) $context_slugs), 'is_string'));
        $all_slugs = array_unique(array_merge($given, ['', 'mixed']));
    }

    bw_fpw_bump_cache_generation($all_slugs);
}

function bw_fpw_get_year_index_transient_key($context_slug)
{
    $normalized = bw_fpw_normalize_context_slug($context_slug);
    return 'bw_fpw_year_index_' . ($normalized ?: 'unknown');
}

function bw_fpw_clear_year_index_transients($context_slug = '')
{
    global $wpdb;

    $normalized = bw_fpw_normalize_context_slug($context_slug);
    if ('' !== $normalized) {
        $transient_key = bw_fpw_get_year_index_transient_key($normalized);
        delete_transient($transient_key);
        return;
    }

    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_bw_fpw_year_index_%'
            OR option_name LIKE '_transient_timeout_bw_fpw_year_index_%'"
    );
}

function bw_fpw_get_context_root_term_id($context_slug)
{
    $normalized = bw_fpw_normalize_context_slug($context_slug);
    if ('' === $normalized) {
        return 0;
    }

    $term = get_term_by('slug', $normalized, 'product_cat');
    return $term instanceof WP_Term ? (int) $term->term_id : 0;
}

function bw_fpw_build_year_quick_ranges($years_map)
{
    if (!is_array($years_map) || empty($years_map)) {
        return [];
    }

    $years = array_keys($years_map);
    $years = array_map('intval', $years);
    sort($years, SORT_NUMERIC);

    $distinct_years = array_values(array_unique($years));
    $total = count($distinct_years);
    if (0 === $total) {
        return [];
    }

    $bucket_count = min(4, $total);
    $bucket_size = (int) ceil($total / $bucket_count);
    $ranges = [];

    for ($index = 0; $index < $bucket_count; $index++) {
        $start_offset = $index * $bucket_size;
        if (!isset($distinct_years[$start_offset])) {
            break;
        }

        $end_offset = min($total - 1, (($index + 1) * $bucket_size) - 1);
        $from = (int) $distinct_years[$start_offset];
        $to = (int) $distinct_years[$end_offset];

        $ranges[] = [
            'key' => $from . '-' . $to,
            'label' => $from === $to ? (string) $from : $from . '–' . $to,
            'from' => $from,
            'to' => $to,
        ];
    }

    return $ranges;
}

function bw_fpw_build_year_index($context_slug)
{
    $context_slug = bw_fpw_normalize_context_slug($context_slug);
    if (!bw_fpw_is_supported_context_slug($context_slug)) {
        return [
            'context' => $context_slug ?: 'mixed',
            'supported' => false,
            'min_year' => null,
            'max_year' => null,
            'years' => [],
            'quick_ranges' => [],
        ];
    }

    $root_term_id = bw_fpw_get_context_root_term_id($context_slug);
    if ($root_term_id <= 0) {
        return [
            'context' => $context_slug,
            'supported' => false,
            'min_year' => null,
            'max_year' => null,
            'years' => [],
            'quick_ranges' => [],
        ];
    }

    $query = new WP_Query([
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'tax_query' => [[
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => [$root_term_id],
            'include_children' => true,
        ]],
    ]);

    $years = [];

    // Collect valid IDs first, then bulk-load canonical year meta in a single query
    // to avoid N+1 DB hits (no per-product get_post_meta or sync writes here).
    $product_ids = array_values(array_filter(array_map('absint', (array) $query->posts)));

    if (!empty($product_ids)) {
        global $wpdb;
        $canonical_year_key = bw_fpw_get_canonical_year_meta_key();
        $ids_in = implode(',', $product_ids); // safe: all values are absint
        $rows   = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id IN ($ids_in)",
                $canonical_year_key
            )
        );

        foreach ((array) $rows as $row) {
            $year = bw_fpw_extract_year_int($row->meta_value);
            if (null === $year) {
                continue;
            }

            if (!isset($years[$year])) {
                $years[$year] = 0;
            }

            $years[$year]++;
        }
    }

    if (!empty($years)) {
        ksort($years, SORT_NUMERIC);
    }

    $year_keys = array_keys($years);
    $min_year = !empty($year_keys) ? (int) reset($year_keys) : null;
    $max_year = !empty($year_keys) ? (int) end($year_keys) : null;

    return [
        'context' => $context_slug,
        'supported' => !empty($years),
        'min_year' => $min_year,
        'max_year' => $max_year,
        'years' => $years,
        'quick_ranges' => bw_fpw_build_year_quick_ranges($years),
    ];
}

function bw_fpw_get_year_index($context_slug)
{
    $context_slug = bw_fpw_normalize_context_slug($context_slug);
    if (!bw_fpw_is_supported_context_slug($context_slug)) {
        return [
            'context' => $context_slug ?: 'mixed',
            'supported' => false,
            'min_year' => null,
            'max_year' => null,
            'years' => [],
            'quick_ranges' => [],
        ];
    }

    $transient_key = bw_fpw_get_year_index_transient_key($context_slug);
    $cached = get_transient($transient_key);

    if (is_array($cached)) {
        return $cached;
    }

    $index = bw_fpw_build_year_index($context_slug);
    set_transient($transient_key, $index, 30 * MINUTE_IN_SECONDS);

    return $index;
}

function bw_fpw_get_year_filter_ui($context_slug)
{
    $index = bw_fpw_get_year_index($context_slug);

    return [
        'supported' => !empty($index['supported']),
        'context' => isset($index['context']) ? (string) $index['context'] : 'mixed',
        'min' => isset($index['min_year']) ? $index['min_year'] : null,
        'max' => isset($index['max_year']) ? $index['max_year'] : null,
        'quick_ranges' => isset($index['quick_ranges']) && is_array($index['quick_ranges']) ? array_values($index['quick_ranges']) : [],
    ];
}

function bw_fpw_get_advanced_filter_index_transient_key($context_slug)
{
    $normalized = bw_fpw_normalize_context_slug($context_slug);
    return 'bw_fpw_advanced_filter_index_' . ($normalized ?: 'unknown');
}

function bw_fpw_clear_advanced_filter_index_transients($context_slug = '')
{
    global $wpdb;

    $normalized = bw_fpw_normalize_context_slug($context_slug);
    if ('' !== $normalized) {
        delete_transient(bw_fpw_get_advanced_filter_index_transient_key($normalized));
        return;
    }

    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_bw_fpw_advanced_filter_index_%'
            OR option_name LIKE '_transient_timeout_bw_fpw_advanced_filter_index_%'"
    );
}

function bw_fpw_get_supported_advanced_filter_groups_for_context($context_slug)
{
    $normalized = bw_fpw_normalize_context_slug($context_slug);
    $definitions = bw_fpw_get_advanced_filter_group_definitions();
    $supported = [];

    foreach ($definitions as $group_key => $definition) {
        $contexts = isset($definition['contexts']) && is_array($definition['contexts']) ? $definition['contexts'] : [];

        if (in_array($normalized, $contexts, true)) {
            $supported[$group_key] = $definition;
        }
    }

    return $supported;
}

function bw_fpw_build_advanced_filter_index($context_slug)
{
    global $wpdb;

    $context_slug = bw_fpw_normalize_context_slug($context_slug);
    $supported_groups = bw_fpw_get_supported_advanced_filter_groups_for_context($context_slug);

    if (empty($supported_groups)) {
        return [
            'context' => $context_slug ?: 'mixed',
            'supported' => false,
            'post_ids' => [],
            'groups' => [],
        ];
    }

    $root_term_id = bw_fpw_get_context_root_term_id($context_slug);
    if ($root_term_id <= 0) {
        return [
            'context' => $context_slug,
            'supported' => false,
            'post_ids' => [],
            'groups' => [],
        ];
    }

    $query = new WP_Query([
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'tax_query' => [[
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => [$root_term_id],
            'include_children' => true,
        ]],
    ]);

    $product_ids = array_values(array_filter(array_map('absint', (array) $query->posts)));
    $index = [
        'context' => $context_slug,
        'supported' => !empty($product_ids),
        'post_ids' => $product_ids,
        'groups' => [],
    ];

    foreach ($supported_groups as $group_key => $definition) {
        $index['groups'][$group_key] = [
            'supported' => true,
            'labels' => [],
            'counts' => [],
            'post_map' => [],
        ];
    }

    if (empty($product_ids)) {
        return $index;
    }

    $meta_keys = [];
    foreach ($supported_groups as $group_key => $definition) {
        $canonical_key = isset($definition['canonical_key']) ? (string) $definition['canonical_key'] : '';
        if ('' !== $canonical_key) {
            $meta_keys[] = $canonical_key;
        }

        $meta_keys = array_merge($meta_keys, bw_fpw_get_candidate_source_meta_keys($context_slug, $group_key));
    }

    $meta_keys = array_values(array_unique(array_filter($meta_keys)));

    if (empty($meta_keys)) {
        return $index;
    }

    $ids_in = implode(',', $product_ids);
    $meta_placeholders = implode(',', array_fill(0, count($meta_keys), '%s'));
    $query_args = array_merge($meta_keys, [$ids_in]);
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta}
             WHERE meta_key IN ({$meta_placeholders}) AND post_id IN ({$ids_in})",
            $meta_keys
        )
    );

    $meta_by_post = [];
    foreach ((array) $rows as $row) {
        $post_id = absint($row->post_id);
        $meta_key = is_string($row->meta_key) ? $row->meta_key : '';

        if ($post_id <= 0 || '' === $meta_key) {
            continue;
        }

        $meta_by_post[$post_id][$meta_key] = isset($row->meta_value) ? (string) $row->meta_value : '';
    }

    foreach ($product_ids as $post_id) {
        $post_meta = isset($meta_by_post[$post_id]) ? $meta_by_post[$post_id] : [];

        foreach ($supported_groups as $group_key => $definition) {
            $canonical_key = isset($definition['canonical_key']) ? (string) $definition['canonical_key'] : '';
            $tokens = [];
            $seen = [];

            if ('' !== $canonical_key && !empty($post_meta[$canonical_key])) {
                $tokens = bw_fpw_extract_filter_tokens_from_value($post_meta[$canonical_key]);
            }

            if (empty($tokens)) {
                foreach (bw_fpw_get_candidate_source_meta_keys($context_slug, $group_key) as $source_meta_key) {
                    if (empty($post_meta[$source_meta_key])) {
                        continue;
                    }

                    foreach (bw_fpw_extract_filter_tokens_from_value($post_meta[$source_meta_key]) as $token) {
                        if (empty($token['value']) || isset($seen[$token['value']])) {
                            continue;
                        }

                        $seen[$token['value']] = true;
                        $tokens[] = $token;
                    }
                }
            }

            if (empty($tokens)) {
                continue;
            }

            $token_values = [];
            foreach ($tokens as $token) {
                if (empty($token['value']) || empty($token['label'])) {
                    continue;
                }

                $value = (string) $token['value'];
                $label = (string) $token['label'];
                $token_values[] = $value;

                if (!isset($index['groups'][$group_key]['labels'][$value])) {
                    $index['groups'][$group_key]['labels'][$value] = $label;
                }

                if (!isset($index['groups'][$group_key]['counts'][$value])) {
                    $index['groups'][$group_key]['counts'][$value] = 0;
                }

                $index['groups'][$group_key]['counts'][$value]++;
            }

            if (!empty($token_values)) {
                $index['groups'][$group_key]['post_map'][$post_id] = array_values(array_unique($token_values));
            }
        }
    }

    foreach ($index['groups'] as $group_key => $group_index) {
        if (empty($group_index['counts'])) {
            $index['groups'][$group_key]['supported'] = false;
        } else {
            ksort($index['groups'][$group_key]['counts'], SORT_NATURAL | SORT_FLAG_CASE);
        }
    }

    return $index;
}

function bw_fpw_get_advanced_filter_index($context_slug)
{
    $context_slug = bw_fpw_normalize_context_slug($context_slug);

    if (empty(bw_fpw_get_supported_advanced_filter_groups_for_context($context_slug))) {
        return [
            'context' => $context_slug ?: 'mixed',
            'supported' => false,
            'post_ids' => [],
            'groups' => [],
        ];
    }

    $transient_key = bw_fpw_get_advanced_filter_index_transient_key($context_slug);
    $cached = get_transient($transient_key);

    if (is_array($cached)) {
        return $cached;
    }

    $index = bw_fpw_build_advanced_filter_index($context_slug);
    set_transient($transient_key, $index, 30 * MINUTE_IN_SECONDS);

    return $index;
}

function bw_fpw_get_empty_advanced_filter_selections()
{
    $selections = [];

    foreach (bw_fpw_get_advanced_filter_group_keys() as $group_key) {
        $selections[$group_key] = [];
    }

    return $selections;
}

function bw_fpw_normalize_advanced_filter_selections($filters)
{
    $normalized = bw_fpw_get_empty_advanced_filter_selections();

    if (!is_array($filters)) {
        return $normalized;
    }

    foreach ($normalized as $group_key => $values) {
        $normalized[$group_key] = bw_fpw_normalize_filter_token_selection_array(
            isset($filters[$group_key]) ? $filters[$group_key] : [],
            50
        );
    }

    return $normalized;
}

function bw_fpw_has_active_advanced_filter_selections($filters)
{
    foreach (bw_fpw_normalize_advanced_filter_selections($filters) as $values) {
        if (!empty($values)) {
            return true;
        }
    }

    return false;
}

function bw_fpw_apply_advanced_filters_to_post_ids($post_ids, $context_slug, $filters, $ignore_group = '')
{
    $normalized_filters = bw_fpw_normalize_advanced_filter_selections($filters);
    $context_slug = bw_fpw_normalize_context_slug($context_slug);
    $supported_groups = bw_fpw_get_supported_advanced_filter_groups_for_context($context_slug);
    $index = bw_fpw_get_advanced_filter_index($context_slug);
    $candidate_ids = is_array($post_ids)
        ? array_values(array_unique(array_map('absint', $post_ids)))
        : array_values(array_map('absint', isset($index['post_ids']) ? (array) $index['post_ids'] : []));

    if (empty($candidate_ids) || empty($supported_groups)) {
        return $candidate_ids;
    }

    $candidate_lookup = array_fill_keys($candidate_ids, true);

    foreach ($normalized_filters as $group_key => $selected_tokens) {
        if ($group_key === $ignore_group || empty($selected_tokens) || !isset($supported_groups[$group_key])) {
            continue;
        }

        $selected_lookup = array_fill_keys($selected_tokens, true);
        $post_map = isset($index['groups'][$group_key]['post_map']) ? (array) $index['groups'][$group_key]['post_map'] : [];

        foreach (array_keys($candidate_lookup) as $post_id) {
            $post_tokens = isset($post_map[$post_id]) ? (array) $post_map[$post_id] : [];
            $matched = false;

            foreach ($post_tokens as $token) {
                if (isset($selected_lookup[$token])) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                unset($candidate_lookup[$post_id]);
            }
        }

        if (empty($candidate_lookup)) {
            break;
        }
    }

    return array_map('intval', array_keys($candidate_lookup));
}

function bw_fpw_build_advanced_filter_options_from_post_ids($context_slug, $group_key, $post_ids)
{
    $index = bw_fpw_get_advanced_filter_index($context_slug);
    $group_index = isset($index['groups'][$group_key]) ? (array) $index['groups'][$group_key] : [];
    $labels = isset($group_index['labels']) ? (array) $group_index['labels'] : [];
    $post_map = isset($group_index['post_map']) ? (array) $group_index['post_map'] : [];
    $candidate_ids = is_array($post_ids)
        ? array_values(array_unique(array_map('absint', $post_ids)))
        : array_values(array_map('absint', isset($index['post_ids']) ? (array) $index['post_ids'] : []));
    $counts = [];
    $options = [];

    foreach ($candidate_ids as $post_id) {
        if (empty($post_map[$post_id])) {
            continue;
        }

        foreach ((array) $post_map[$post_id] as $token) {
            if (!isset($counts[$token])) {
                $counts[$token] = 0;
            }

            $counts[$token]++;
        }
    }

    foreach ($counts as $token => $count) {
        $label = isset($labels[$token]) ? (string) $labels[$token] : '';
        if ('' === $label) {
            continue;
        }

        $options[] = [
            'value' => (string) $token,
            'name' => $label,
            'count' => (int) $count,
        ];
    }

    usort(
        $options,
        static function ($a, $b) {
            if ($a['count'] === $b['count']) {
                return strcmp($a['name'], $b['name']);
            }

            return $b['count'] <=> $a['count'];
        }
    );

    return array_values($options);
}

function bw_fpw_get_advanced_filter_ui($context_slug, $post_ids = null, $filters = [])
{
    $context_slug = bw_fpw_normalize_context_slug($context_slug);
    $supported_groups = bw_fpw_get_supported_advanced_filter_groups_for_context($context_slug);
    $base_post_ids = is_array($post_ids) ? array_values(array_unique(array_map('absint', $post_ids))) : null;
    $ui = [];

    foreach (bw_fpw_get_advanced_filter_group_definitions() as $group_key => $definition) {
        if (!isset($supported_groups[$group_key])) {
            $ui[$group_key] = [
                'supported' => false,
                'options' => [],
            ];
            continue;
        }

        $scoped_post_ids = bw_fpw_apply_advanced_filters_to_post_ids($base_post_ids, $context_slug, $filters, $group_key);

        $ui[$group_key] = [
            'supported' => true,
            'options' => bw_fpw_build_advanced_filter_options_from_post_ids($context_slug, $group_key, $scoped_post_ids),
        ];
    }

    return $ui;
}

function bw_fpw_normalize_year_bound($value)
{
    if (null === $value || '' === $value) {
        return null;
    }

    return bw_fpw_extract_year_int(is_scalar($value) ? (string) $value : '');
}

function bw_fpw_normalize_year_range($from, $to)
{
    $normalized_from = bw_fpw_normalize_year_bound($from);
    $normalized_to = bw_fpw_normalize_year_bound($to);

    if (null !== $normalized_from && null !== $normalized_to && $normalized_from > $normalized_to) {
        $temp = $normalized_from;
        $normalized_from = $normalized_to;
        $normalized_to = $temp;
    }

    return [
        'from' => $normalized_from,
        'to' => $normalized_to,
    ];
}

function bw_fpw_build_tax_query($post_type, $category = 'all', $subcategories = [], $tags = [])
{
    $taxonomy = 'product' === $post_type ? 'product_cat' : 'category';
    $tag_taxonomy = 'product' === $post_type ? 'product_tag' : 'post_tag';
    $tax_query = [];

    if ('all' !== $category && absint($category) > 0) {
        if (!empty($subcategories)) {
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field' => 'term_id',
                'terms' => $subcategories,
            ];
        } else {
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field' => 'term_id',
                'terms' => [absint($category)],
            ];
        }
    }

    if (!empty($tags)) {
        $tax_query[] = [
            'taxonomy' => $tag_taxonomy,
            'field' => 'term_id',
            'terms' => $tags,
        ];
    }

    if (count($tax_query) > 1) {
        $tax_query['relation'] = 'AND';
    }

    return $tax_query;
}

function bw_fpw_has_active_refinement_filters($subcategories = [], $tags = [], $search = '')
{
    return !empty($subcategories) || !empty($tags) || '' !== bw_fpw_normalize_search_query($search);
}

function bw_fpw_get_empty_state_message($subcategories = [], $tags = [], $search = '')
{
    return 'No results found.';
}

function bw_fpw_get_matching_post_ids($post_type, $category, $subcategories, $tags, $search, $year_from = null, $year_to = null, $context_slug = '', $advanced_filters = [], $ignore_advanced_group = '')
{
    global $wpdb;

    $normalized_search = bw_fpw_normalize_search_value($search);
    $taxonomy          = 'product' === $post_type ? 'product_cat' : 'category';
    $tag_taxonomy      = 'product' === $post_type ? 'product_tag' : 'post_tag';
    $post_type_safe    = sanitize_key($post_type);

    // ---- Resolve category term IDs (include children) without WP_Query ----
    $cat_term_ids = [];
    if ('all' !== $category && absint($category) > 0) {
        $cat_id = absint($category);
        if (!empty($subcategories)) {
            $cat_term_ids = array_values(array_filter(array_map('absint', (array) $subcategories)));
        } else {
            $cat_term_ids = [$cat_id];
            $children     = get_term_children($cat_id, $taxonomy);
            if (is_array($children) && !empty($children)) {
                $cat_term_ids = array_merge($cat_term_ids, array_map('absint', $children));
            }
        }
    }

    $tag_term_ids = array_values(array_filter(array_map('absint', (array) $tags)));

    // ---- Pure-SQL query: no WP_Query, no hook/filter interference ----
    $joins  = '';
    $wheres = [
        "p.post_type   = '" . esc_sql($post_type_safe) . "'",
        "p.post_status = 'publish'",
    ];

    if (!empty($cat_term_ids)) {
        $ids_in   = implode(',', $cat_term_ids);
        $joins   .= " INNER JOIN {$wpdb->term_relationships} tr_cat"
                  . "   ON  tr_cat.object_id = p.ID"
                  . " INNER JOIN {$wpdb->term_taxonomy} tt_cat"
                  . "   ON  tt_cat.term_taxonomy_id = tr_cat.term_taxonomy_id";
        $wheres[] = "tt_cat.taxonomy = '" . esc_sql($taxonomy) . "'";
        $wheres[] = "tt_cat.term_id IN ({$ids_in})";
    }

    if (!empty($tag_term_ids)) {
        $ids_in   = implode(',', $tag_term_ids);
        $joins   .= " INNER JOIN {$wpdb->term_relationships} tr_tag"
                  . "   ON  tr_tag.object_id = p.ID"
                  . " INNER JOIN {$wpdb->term_taxonomy} tt_tag"
                  . "   ON  tt_tag.term_taxonomy_id = tr_tag.term_taxonomy_id";
        $wheres[] = "tt_tag.taxonomy = '" . esc_sql($tag_taxonomy) . "'";
        $wheres[] = "tt_tag.term_id IN ({$ids_in})";
    }

    if (null !== $year_from || null !== $year_to) {
        $canonical_year_key = esc_sql(bw_fpw_get_canonical_year_meta_key());
        $year_meta_where = "pm_year.meta_key = '{$canonical_year_key}'";

        if (null !== $year_from && null !== $year_to) {
            $year_meta_where .= ' AND CAST(pm_year.meta_value AS UNSIGNED) BETWEEN ' . (int) $year_from . ' AND ' . (int) $year_to;
        } elseif (null !== $year_from) {
            $year_meta_where .= ' AND CAST(pm_year.meta_value AS UNSIGNED) >= ' . (int) $year_from;
        } else {
            $year_meta_where .= ' AND CAST(pm_year.meta_value AS UNSIGNED) <= ' . (int) $year_to;
        }

        $wheres[] = "p.ID IN (SELECT pm_year.post_id FROM {$wpdb->postmeta} pm_year WHERE {$year_meta_where})";
    }

    // ---- Text search via SQL LIKE — single query, no PHP post-processing needed ----
    // LOWER() ensures case-insensitive matching regardless of DB collation.
    // The search term is already accent-normalized by bw_fpw_normalize_search_value.
    if ('' !== $normalized_search) {
        $like        = '%' . $wpdb->esc_like($normalized_search) . '%';
        $like_sql    = "'" . esc_sql($like) . "'";
        $tax_sql     = "'" . esc_sql($taxonomy) . "'";
        $tag_tax_sql = "'" . esc_sql($tag_taxonomy) . "'";
        $searchable_meta_keys = array_values(
            array_unique(
                array_merge(
                    [bw_fpw_get_canonical_year_meta_key(), bw_fpw_get_canonical_author_meta_key()],
                    array_filter([
                        bw_fpw_get_canonical_artist_meta_key(),
                        bw_fpw_get_canonical_publisher_meta_key(),
                    ]),
                    bw_fpw_get_all_filter_source_year_meta_keys(),
                    bw_fpw_get_all_filter_source_author_meta_keys(),
                    bw_fpw_get_all_filter_source_meta_keys_for_group('artist'),
                    bw_fpw_get_all_filter_source_meta_keys_for_group('publisher')
                )
            )
        );
        $searchable_meta_keys_sql = "'" . implode("','", array_map('esc_sql', $searchable_meta_keys)) . "'";
        $wheres[]    = "(LOWER(p.post_title) LIKE {$like_sql}"
                     . " OR LOWER(p.post_name) LIKE {$like_sql}"
                     . " OR LOWER(p.post_excerpt) LIKE {$like_sql}"
                     . " OR LOWER(p.post_content) LIKE {$like_sql}"
                     . " OR p.ID IN ("
                     . "   SELECT tr2.object_id"
                     . "   FROM {$wpdb->term_relationships} tr2"
                     . "   INNER JOIN {$wpdb->term_taxonomy} tt2"
                     . "     ON tt2.term_taxonomy_id = tr2.term_taxonomy_id"
                     . "   INNER JOIN {$wpdb->terms} t2"
                     . "     ON t2.term_id = tt2.term_id"
                     . "   WHERE tt2.taxonomy IN ({$tax_sql}, {$tag_tax_sql})"
                     . "   AND LOWER(t2.name) LIKE {$like_sql}"
                     . " )"
                     . " OR p.ID IN ("
                     . "   SELECT pm.post_id"
                     . "   FROM {$wpdb->postmeta} pm"
                     . "   WHERE pm.meta_key IN ({$searchable_meta_keys_sql})"
                     . "   AND LOWER(pm.meta_value) LIKE {$like_sql}"
                     . "))";
    }

    $sql     = "SELECT DISTINCT p.ID FROM {$wpdb->posts} p"
             . $joins
             . ' WHERE ' . implode(' AND ', $wheres);
    $raw_col = $wpdb->get_col($sql);
    $post_ids = array_map('absint', (array) $raw_col);

    if (!bw_fpw_has_active_advanced_filter_selections($advanced_filters)) {
        return $post_ids;
    }

    return bw_fpw_apply_advanced_filters_to_post_ids($post_ids, $context_slug, $advanced_filters, $ignore_advanced_group);
}
function bw_fpw_collect_tags_from_posts($taxonomy, $post_ids)
{
    if (empty($post_ids)) {
        return [];
    }

    $post_ids = bw_fpw_normalize_int_array($post_ids, bw_fpw_get_tag_source_posts_limit());

    if (empty($post_ids)) {
        return [];
    }

    $terms = wp_get_object_terms(
        $post_ids,
        $taxonomy,
        [
            'fields' => 'all_with_object_id',
        ]
    );

    if (empty($terms) || is_wp_error($terms)) {
        return [];
    }

    $results = [];

    foreach ($terms as $term) {
        $term_id = (int) $term->term_id;

        if (!isset($results[$term_id])) {
            $results[$term_id] = [
                'term_id' => $term_id,
                'name' => $term->name,
                'count' => 0,
            ];
        }

        $results[$term_id]['count']++;
    }

    usort(
        $results,
        static function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        }
    );

    return array_values($results);
}

function bw_fpw_get_related_tags_data($post_type, $category = 'all', $subcategories = [], $search = '', $year_from = null, $year_to = null, $context_slug = '', $advanced_filters = [])
{
    $tag_taxonomy = 'product' === $post_type ? 'product_tag' : 'post_tag';
    $normalized_search = bw_fpw_normalize_search_value($search);

    if (('all' === $category || empty($category)) && '' === $normalized_search && null === $year_from && null === $year_to && !bw_fpw_has_active_advanced_filter_selections($advanced_filters)) {
        $terms = get_terms(
            [
                'taxonomy' => $tag_taxonomy,
                'hide_empty' => true,
            ]
        );

        if (empty($terms) || is_wp_error($terms)) {
            return [];
        }

        $results = [];

        foreach ($terms as $term) {
            $results[] = [
                'term_id' => (int) $term->term_id,
                'name' => $term->name,
                'count' => (int) $term->count,
            ];
        }

        return $results;
    }

    $post_ids = '' === $normalized_search
        ? bw_fpw_get_filtered_post_ids_for_tags($post_type, $category, $subcategories, $year_from, $year_to, $context_slug, $advanced_filters)
        : bw_fpw_get_matching_post_ids($post_type, $category, $subcategories, [], $normalized_search, $year_from, $year_to, $context_slug, $advanced_filters);

    return bw_fpw_collect_tags_from_posts($tag_taxonomy, $post_ids);
}

function bw_fpw_get_available_subcategories_data($post_type, $category = 'all', $tags = [], $search = '', $year_from = null, $year_to = null, $context_slug = '', $advanced_filters = [])
{
    $taxonomy = 'product' === $post_type ? 'product_cat' : 'category';
    $normalized_search = bw_fpw_normalize_search_value($search);
    $post_ids = '' === $normalized_search
        // Apply the same post-count cap used by the tags path to prevent unbounded wp_get_object_terms calls.
        ? bw_fpw_get_candidate_post_ids_without_search($post_type, $category, [], $tags, $year_from, $year_to, $context_slug, $advanced_filters, bw_fpw_get_tag_source_posts_limit())
        : bw_fpw_get_matching_post_ids($post_type, $category, [], $tags, $normalized_search, $year_from, $year_to, $context_slug, $advanced_filters);

    if (empty($post_ids)) {
        return [];
    }

    $terms = wp_get_object_terms(
        $post_ids,
        $taxonomy,
        [
            'fields' => 'all_with_object_id',
        ]
    );

    if (empty($terms) || is_wp_error($terms)) {
        return [];
    }

    $results = [];
    $parent_category_id = 'all' !== $category ? absint($category) : 0;

    foreach ($terms as $term) {
        $term_id = (int) $term->term_id;
        $term_parent = (int) $term->parent;

        if ('all' === $category) {
            if ($term_parent <= 0) {
                continue;
            }
        } elseif ($term_parent !== $parent_category_id) {
            continue;
        }

        if (!isset($results[$term_id])) {
            $results[$term_id] = [
                'term_id' => $term_id,
                'name' => $term->name,
                'count' => 0,
            ];
        }

        $results[$term_id]['count']++;
    }

    usort(
        $results,
        static function ($a, $b) {
            if ($a['count'] === $b['count']) {
                return strcmp($a['name'], $b['name']);
            }

            return $b['count'] <=> $a['count'];
        }
    );

    return array_values($results);
}

function bw_fpw_render_tag_markup($tags)
{
    if (empty($tags)) {
        return '';
    }

    ob_start();

    foreach ($tags as $tag) {
        ?>
        <button class="bw-fpw-filter-option bw-fpw-tag-button" data-tag="<?php echo esc_attr($tag['term_id']); ?>">
            <span class="bw-fpw-option-label"><?php echo esc_html($tag['name']); ?></span> <span
                class="bw-fpw-option-count">(<?php echo esc_html($tag['count']); ?>)</span>
        </button>
        <?php
    }

    return ob_get_clean();
}

function bw_fpw_normalize_array_for_cache_key($values)
{
    if (!is_array($values)) {
        return [];
    }

    $normalized = array_map('absint', $values);
    $normalized = array_filter(
        $normalized,
        static function ($value) {
            return $value > 0;
        }
    );
    $normalized = array_values(array_unique($normalized));
    sort($normalized, SORT_NUMERIC);

    return $normalized;
}

function bw_fpw_normalize_token_array_for_cache_key($values)
{
    $normalized = bw_fpw_normalize_filter_token_selection_array($values, 50);
    sort($normalized, SORT_NATURAL | SORT_FLAG_CASE);

    return array_values($normalized);
}

function bw_fpw_generate_cache_key($params)
{
    $params = is_array($params) ? $params : [];
    $search_enabled = bw_fpw_normalize_bool(isset($params['search_enabled']) ? $params['search_enabled'] : null, true);
    $search_value = $search_enabled
        ? bw_fpw_normalize_search_query(isset($params['search']) ? (string) $params['search'] : '')
        : '';

    $context_slug_for_key = isset($params['context_slug']) ? (string) $params['context_slug'] : '';

    $canonical_payload = [
        'schema' => 'v6',
        'widget_id' => isset($params['widget_id']) ? (string) $params['widget_id'] : '',
        'post_type' => isset($params['post_type']) ? (string) $params['post_type'] : bw_fpw_get_default_post_type(),
        'context_slug' => $context_slug_for_key,
        'cache_gen' => bw_fpw_get_cache_generation($context_slug_for_key),
        'category' => isset($params['category']) ? (string) $params['category'] : 'all',
        'subcategories' => bw_fpw_normalize_array_for_cache_key(isset($params['subcategories']) ? $params['subcategories'] : []),
        'tags' => bw_fpw_normalize_array_for_cache_key(isset($params['tags']) ? $params['tags'] : []),
        'search_enabled' => $search_enabled ? 1 : 0,
        'search' => $search_value,
        'year_from' => bw_fpw_normalize_year_bound(isset($params['year_from']) ? $params['year_from'] : null),
        'year_to' => bw_fpw_normalize_year_bound(isset($params['year_to']) ? $params['year_to'] : null),
        'artist' => bw_fpw_normalize_token_array_for_cache_key(isset($params['artist']) ? $params['artist'] : []),
        'author' => bw_fpw_normalize_token_array_for_cache_key(isset($params['author']) ? $params['author'] : []),
        'publisher' => bw_fpw_normalize_token_array_for_cache_key(isset($params['publisher']) ? $params['publisher'] : []),
        'source' => bw_fpw_normalize_token_array_for_cache_key(isset($params['source']) ? $params['source'] : []),
        'technique' => bw_fpw_normalize_token_array_for_cache_key(isset($params['technique']) ? $params['technique'] : []),
        'image_toggle' => !empty($params['image_toggle']) ? 1 : 0,
        'image_size' => isset($params['image_size']) ? (string) $params['image_size'] : 'large',
        'image_mode' => isset($params['image_mode']) ? (string) $params['image_mode'] : 'proportional',
        'hover_effect' => !empty($params['hover_effect']) ? 1 : 0,
        'open_cart_popup' => !empty($params['open_cart_popup']) ? 1 : 0,
        'sort_key' => bw_fpw_normalize_sort_key(isset($params['sort_key']) ? $params['sort_key'] : 'default'),
        'order_by' => isset($params['order_by']) ? (string) $params['order_by'] : 'date',
        'order' => isset($params['order']) ? (string) $params['order'] : 'DESC',
        'per_page' => isset($params['per_page']) ? (int) $params['per_page'] : bw_fpw_get_default_per_page(),
        'page' => isset($params['page']) ? (int) $params['page'] : 1,
        'offset' => isset($params['offset']) ? (int) $params['offset'] : 0,
    ];

    $payload_json = wp_json_encode($canonical_payload);
    if (!is_string($payload_json) || '' === $payload_json) {
        $payload_json = serialize($canonical_payload);
    }

    $hash = hash('sha256', $payload_json);

    return 'bw_fpw_' . $hash;
}

/**
 * Handler AJAX per filtrare i post
 */
add_action('wp_ajax_bw_fpw_filter_posts', 'bw_fpw_filter_posts');
add_action('wp_ajax_nopriv_bw_fpw_filter_posts', 'bw_fpw_filter_posts');

/**
 * Endpoint leggero per rinnovare il nonce scaduto (es. tab lasciato inattivo).
 * Non richiede autenticazione — il nonce stesso è la protezione CSRF.
 */
add_action('wp_ajax_bw_fpw_refresh_nonce', 'bw_fpw_ajax_refresh_nonce');
add_action('wp_ajax_nopriv_bw_fpw_refresh_nonce', 'bw_fpw_ajax_refresh_nonce');

/**
 * Flush product grid transient cache when a product or post is saved/updated.
 */
add_action('save_post', 'bw_fpw_clear_grid_transients');
function bw_fpw_clear_grid_transients($post_id)
{
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    if ('product' === get_post_type($post_id)) {
        $family = bw_fpw_resolve_product_family_slug_from_product($post_id);
        // Scope invalidation to this product's family (+ 'mixed'/'') when unambiguous.
        $slugs = ('' !== $family && 'mixed' !== $family) ? [$family] : null;
        bw_fpw_clear_grid_transient_cache($slugs);
        bw_fpw_clear_advanced_filter_index_transients($slugs ? $slugs[0] : '');
        bw_fpw_sync_product_filter_meta($post_id);
    } else {
        bw_fpw_clear_grid_transient_cache();
        bw_fpw_clear_advanced_filter_index_transients();
    }

    bw_fpw_clear_year_index_transients();
}

function bw_fpw_handle_product_filter_meta_change($meta_id_or_ids, $object_id, $meta_key, $meta_value = '')
{
    $object_id = absint($object_id);
    if ($object_id <= 0 || 'product' !== get_post_type($object_id)) {
        return;
    }

    if (!in_array($meta_key, bw_fpw_get_all_filter_relevant_meta_keys(), true)) {
        return;
    }

    if (in_array($meta_key, bw_fpw_get_all_filter_canonical_meta_keys(), true)) {
        return;
    }

    bw_fpw_sync_product_filter_meta($object_id);

    $family = bw_fpw_resolve_product_family_slug_from_product($object_id);
    $slugs = ('' !== $family && 'mixed' !== $family) ? [$family] : null;
    bw_fpw_clear_grid_transient_cache($slugs);
    bw_fpw_clear_year_index_transients();
    bw_fpw_clear_advanced_filter_index_transients();
}
add_action('added_post_meta', 'bw_fpw_handle_product_filter_meta_change', 10, 4);
add_action('updated_post_meta', 'bw_fpw_handle_product_filter_meta_change', 10, 4);
add_action('deleted_post_meta', 'bw_fpw_handle_product_filter_meta_change', 10, 4);

function bw_fpw_handle_product_filter_term_change($object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids)
{
    $object_id = absint($object_id);
    if ($object_id <= 0 || 'product' !== get_post_type($object_id) || 'product_cat' !== $taxonomy) {
        return;
    }

    bw_fpw_sync_product_filter_meta($object_id);
    bw_fpw_clear_grid_transient_cache();
    bw_fpw_clear_year_index_transients();
    bw_fpw_clear_advanced_filter_index_transients();
}
add_action('set_object_terms', 'bw_fpw_handle_product_filter_term_change', 10, 6);

function bw_fpw_handle_product_filter_status_change($new_status, $old_status, $post)
{
    if (!$post instanceof WP_Post || 'product' !== $post->post_type || $new_status === $old_status) {
        return;
    }

    bw_fpw_sync_product_filter_meta($post->ID);
    bw_fpw_clear_grid_transient_cache();
    bw_fpw_clear_year_index_transients();
    bw_fpw_clear_advanced_filter_index_transients();
}
add_action('transition_post_status', 'bw_fpw_handle_product_filter_status_change', 10, 3);

function bw_fpw_ajax_refresh_nonce()
{
    wp_send_json_success(['nonce' => wp_create_nonce('bw_fpw_nonce')]);
}

function bw_fpw_filter_posts()
{
    try {
        bw_fpw_filter_posts_inner();
    } catch (\Throwable $e) {
        error_log('[bw_fpw_filter_posts] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        wp_send_json_error(['message' => 'server_error', 'debug' => $e->getMessage()]);
    }
}

function bw_fpw_filter_posts_inner()
{
    check_ajax_referer('bw_fpw_nonce', 'nonce');

    $widget_id = bw_fpw_normalize_widget_id(isset($_POST['widget_id']) ? wp_unslash($_POST['widget_id']) : '');
    $post_type = bw_fpw_normalize_post_type(isset($_POST['post_type']) ? wp_unslash($_POST['post_type']) : bw_fpw_get_default_post_type());
    $context_slug = bw_fpw_normalize_context_slug(isset($_POST['context_slug']) ? wp_unslash($_POST['context_slug']) : '');
    $category = bw_fpw_normalize_term_selector(isset($_POST['category']) ? wp_unslash($_POST['category']) : 'all');
    $subcategories = bw_fpw_normalize_int_array(isset($_POST['subcategories']) ? wp_unslash($_POST['subcategories']) : [], 50);
    $tags = bw_fpw_normalize_int_array(isset($_POST['tags']) ? wp_unslash($_POST['tags']) : [], 50);
    $search_enabled = bw_fpw_normalize_bool(isset($_POST['search_enabled']) ? wp_unslash($_POST['search_enabled']) : null, true);
    $search = $search_enabled
        ? bw_fpw_normalize_search_query(isset($_POST['search']) ? wp_unslash($_POST['search']) : '')
        : '';
    $normalized_year_range = bw_fpw_normalize_year_range(
        isset($_POST['year_from']) ? wp_unslash($_POST['year_from']) : null,
        isset($_POST['year_to']) ? wp_unslash($_POST['year_to']) : null
    );
    $year_from = $normalized_year_range['from'];
    $year_to = $normalized_year_range['to'];
    $advanced_filters = bw_fpw_normalize_advanced_filter_selections([
        'artist' => isset($_POST['artist']) ? wp_unslash($_POST['artist']) : [],
        'author' => isset($_POST['author']) ? wp_unslash($_POST['author']) : [],
        'publisher' => isset($_POST['publisher']) ? wp_unslash($_POST['publisher']) : [],
        'source' => isset($_POST['source']) ? wp_unslash($_POST['source']) : [],
        'technique' => isset($_POST['technique']) ? wp_unslash($_POST['technique']) : [],
    ]);
    $image_toggle = bw_fpw_normalize_bool(isset($_POST['image_toggle']) ? wp_unslash($_POST['image_toggle']) : null, false);
    $image_size = bw_fpw_normalize_image_size(isset($_POST['image_size']) ? wp_unslash($_POST['image_size']) : 'large');
    $image_mode = bw_fpw_normalize_image_mode(isset($_POST['image_mode']) ? wp_unslash($_POST['image_mode']) : 'proportional');
    $hover_effect = bw_fpw_normalize_bool(isset($_POST['hover_effect']) ? wp_unslash($_POST['hover_effect']) : null, false);
    $open_cart_popup = bw_fpw_normalize_bool(isset($_POST['open_cart_popup']) ? wp_unslash($_POST['open_cart_popup']) : null, false);
    $default_order_by = bw_fpw_normalize_order_by(isset($_POST['order_by']) ? wp_unslash($_POST['order_by']) : 'date');
    $default_order = bw_fpw_normalize_order(isset($_POST['order']) ? wp_unslash($_POST['order']) : 'DESC');
    $sort_key = bw_fpw_normalize_sort_key(isset($_POST['sort_key']) ? wp_unslash($_POST['sort_key']) : 'default');
    $sort_config = bw_fpw_resolve_sort_config($sort_key, $default_order_by, $default_order, $post_type);
    $order_by = $sort_config['effective_order_by'];
    $order = $sort_config['effective_order'];
    $raw_per_page = isset($_POST['per_page']) ? wp_unslash($_POST['per_page']) : bw_fpw_get_default_per_page();
    $normalized_per_page = is_numeric($raw_per_page) ? (int) $raw_per_page : bw_fpw_get_default_per_page();
    $per_page = $normalized_per_page <= 0
        ? -1
        : bw_fpw_normalize_positive_int(
            $normalized_per_page,
            bw_fpw_get_default_per_page(),
            1,
            bw_fpw_get_max_per_page()
        );
    $page = bw_fpw_normalize_positive_int(
        isset($_POST['page']) ? wp_unslash($_POST['page']) : 1,
        1,
        1,
        1000
    );
    $raw_offset = isset($_POST['offset']) ? wp_unslash($_POST['offset']) : null;
    $offset = is_numeric($raw_offset) ? max(0, (int) $raw_offset) : 0;

    if (bw_fpw_is_throttled_request('bw_fpw_filter_posts')) {
        bw_fpw_send_throttled_response('bw_fpw_filter_posts', $widget_id);
    }

    // For random order, ignore ASC/DESC and skip caching
    $skip_cache = false;
    if ('rand' === $order_by) {
        $order = 'ASC';
        $skip_cache = true; // Don't cache random results
    }

    // PERFORMANCE: Check transient cache first (3 minutes)
    // Skip cache for random order
    if (!$skip_cache) {
        $cache_key_params = [
            'widget_id' => $widget_id,
            'post_type' => $post_type,
            'context_slug' => $context_slug,
            'category' => $category,
            'subcategories' => $subcategories,
            'tags' => $tags,
            'search_enabled' => $search_enabled,
            'search' => $search,
            'year_from' => $year_from,
            'year_to' => $year_to,
            'artist' => $advanced_filters['artist'],
            'author' => $advanced_filters['author'],
            'publisher' => $advanced_filters['publisher'],
            'source' => $advanced_filters['source'],
            'technique' => $advanced_filters['technique'],
            'image_toggle' => $image_toggle,
            'image_size' => $image_size,
            'image_mode' => $image_mode,
            'hover_effect' => $hover_effect,
            'open_cart_popup' => $open_cart_popup,
            'sort_key' => $sort_key,
            'order_by' => $order_by,
            'order' => $order,
            'per_page' => $per_page,
            'page' => $page,
            'offset' => $offset,
        ];
        $transient_key = bw_fpw_generate_cache_key($cache_key_params);

        $cached_result = get_transient($transient_key);

        if (false !== $cached_result) {
            wp_send_json_success($cached_result);
            return;
        }
    }

    $query_posts_per_page = $per_page > 0 ? $per_page + 1 : -1;

    // On append loads (page 2+) the result count is already known client-side;
    // skip SQL_CALC_FOUND_ROWS to save an expensive full-table scan.
    $is_append = ($page > 1 || $offset > 0);

    $query_args = [
        'post_type' => $post_type,
        'posts_per_page' => $query_posts_per_page,
        'post_status' => 'publish',
        'no_found_rows' => $is_append,
        'ignore_sticky_posts' => true,
    ];

    foreach ((array) $sort_config['query_args'] as $query_arg_key => $query_arg_value) {
        $query_args[$query_arg_key] = $query_arg_value;
    }

    // year_int sorting uses a numeric meta field; remap to WP_Query meta_value_num.
    // Handles direct order_by=year_int (not via sort_key).
    if ('year_int' === $order_by) {
        $query_args['orderby']  = 'meta_value_num';
        $query_args['meta_key'] = bw_fpw_get_canonical_year_meta_key();
    }

    if ($per_page > 0 && $offset > 0) {
        $query_args['offset'] = $offset;
    } else {
        $query_args['paged'] = $page;
    }

    $tax_query = bw_fpw_build_tax_query($post_type, $category, $subcategories, $tags);
    if (!empty($tax_query)) {
        $query_args['tax_query'] = $tax_query;
    }

    if (null !== $year_from || null !== $year_to) {
        $canonical_year_key = bw_fpw_get_canonical_year_meta_key();
        $meta_query = isset($query_args['meta_query']) && is_array($query_args['meta_query']) ? $query_args['meta_query'] : [];

        if (null !== $year_from && null !== $year_to) {
            $meta_query[] = [
                'key' => $canonical_year_key,
                'value' => [(int) $year_from, (int) $year_to],
                'compare' => 'BETWEEN',
                'type' => 'NUMERIC',
            ];
        } elseif (null !== $year_from) {
            $meta_query[] = [
                'key' => $canonical_year_key,
                'value' => (int) $year_from,
                'compare' => '>=',
                'type' => 'NUMERIC',
            ];
        } else {
            $meta_query[] = [
                'key' => $canonical_year_key,
                'value' => (int) $year_to,
                'compare' => '<=',
                'type' => 'NUMERIC',
            ];
        }

        $query_args['meta_query'] = $meta_query;
    }

    $effective_context_slug = $context_slug;

    if ('' === $effective_context_slug && 'product' === $post_type && 'all' !== $category) {
        $effective_context_slug = bw_fpw_resolve_product_family_slug_from_term_id(absint($category), 'product_cat');
    }

    $base_candidate_post_ids = [];
    $final_candidate_post_ids = [];
    $has_active_advanced_filters = bw_fpw_has_active_advanced_filter_selections($advanced_filters);
    $supports_advanced_filters = !empty(bw_fpw_get_supported_advanced_filter_groups_for_context($effective_context_slug));

    if ('product' === $post_type) {
        if ('' !== $search) {
            $base_candidate_post_ids = bw_fpw_get_matching_post_ids(
                $post_type,
                $category,
                $subcategories,
                $tags,
                $search,
                $year_from,
                $year_to,
                $effective_context_slug,
                []
            );
        } elseif ($has_active_advanced_filters || $supports_advanced_filters) {
            $base_candidate_post_ids = bw_fpw_get_candidate_post_ids_without_search(
                $post_type,
                $category,
                $subcategories,
                $tags,
                $year_from,
                $year_to,
                $effective_context_slug,
                []
            );
        }
    }

    if ($has_active_advanced_filters) {
        $final_candidate_post_ids = bw_fpw_apply_advanced_filters_to_post_ids($base_candidate_post_ids, $effective_context_slug, $advanced_filters);

        if (empty($final_candidate_post_ids)) {
            $query_args['post__in'] = [0];
        } else {
            $query_args['post__in'] = $final_candidate_post_ids;
        }
    } elseif ('' !== $search) {
        if (empty($base_candidate_post_ids)) {
            $query_args['post__in'] = [0];
        } else {
            $query_args['post__in'] = $base_candidate_post_ids;
        }
    }

    $query = new WP_Query($query_args);

    $has_posts = $query->have_posts();
    $has_more = $per_page > 0 && $has_posts && $query->post_count > $per_page;
    $response_page = $per_page > 0 ? (int) floor($offset / $per_page) + 1 : $page;
    $next_page = $has_more ? $response_page + 1 : 0;
    $rendered_posts = 0;
    $image_loading = ($page > 1 || $offset > 0) ? 'lazy' : 'eager';

    ob_start();

    if ($has_posts) {
        while ($query->have_posts()) {
            $query->the_post();

            if ($per_page > 0 && $rendered_posts >= $per_page) {
                break;
            }

            $post_id = get_the_ID();

            if (
                'product' === $post_type &&
                class_exists('BW_Product_Card_Component') &&
                function_exists('wc_get_product')
            ) {
                $product = wc_get_product($post_id);

                if ($product) {
                    echo BW_Product_Card_Component::render(
                        $product,
                        [
                            'image_size' => $image_size,
                            'image_mode' => $image_mode,
                            'image_loading' => $image_loading,
                            'hover_image_loading' => 'lazy',
                            'show_image' => $image_toggle,
                            'show_hover_image' => $image_toggle && $hover_effect,
                            'hover_image_source' => 'meta',
                            'show_title' => true,
                            'show_description' => true,
                            'description_mode' => 'auto',
                            'show_price' => true,
                            'show_buttons' => true,
                            'show_add_to_cart' => true,
                            'open_cart_popup' => $open_cart_popup,
                            'wrapper_classes' => 'bw-fpw-item',
                            'card_classes' => 'bw-fpw-card',
                            'media_classes' => 'bw-fpw-media',
                            'media_link_classes' => 'bw-fpw-media-link',
                            'image_wrapper_classes' => 'bw-fpw-image',
                            'content_classes' => 'bw-fpw-content bw-slider-content',
                            'title_classes' => 'bw-fpw-title',
                            'description_classes' => 'bw-fpw-description',
                            'price_classes' => 'bw-fpw-price price',
                            'overlay_classes' => 'bw-fpw-overlay overlay-buttons has-buttons',
                            'overlay_buttons_classes' => 'bw-fpw-overlay-buttons',
                            'view_button_classes' => 'bw-fpw-overlay-button overlay-button overlay-button--view',
                            'cart_button_classes' => 'bw-fpw-overlay-button overlay-button overlay-button--cart bw-btn-addtocart',
                            'placeholder_classes' => 'bw-fpw-image-placeholder',
                        ]
                    );
                    $rendered_posts++;
                    continue;
                }
            }

            $permalink = get_permalink($post_id);
            $title = get_the_title($post_id);
            $excerpt = get_the_excerpt($post_id);

            if (empty($excerpt)) {
                $excerpt = wp_trim_words(wp_strip_all_tags(get_the_content(null, false, $post_id)), 30);
            }

            if (!empty($excerpt) && false === strpos($excerpt, '<p')) {
                $excerpt = '<p>' . $excerpt . '</p>';
            }

            $thumbnail_html = '';

            if ($image_toggle && has_post_thumbnail($post_id)) {
                $thumbnail_id = get_post_thumbnail_id($post_id);

                if ($thumbnail_id) {
                    $thumbnail_html = wp_get_attachment_image(
                        $thumbnail_id,
                        $image_size,
                        false,
                        [
                            'loading' => $image_loading,
                            'class' => 'bw-slider-main',
                        ]
                    );
                }
            }

            $hover_image_html = '';
            if ($hover_effect && 'product' === $post_type) {
                $hover_image_id = (int) get_post_meta($post_id, '_bw_slider_hover_image', true);

                if ($hover_image_id) {
                    $hover_image_html = wp_get_attachment_image(
                        $hover_image_id,
                        $image_size,
                        false,
                        [
                            'class' => 'bw-slider-hover',
                            'loading' => $image_loading,
                        ]
                    );
                }
            }

            $price_html = '';
            $has_add_to_cart = false;
            $add_to_cart_url = '';

            if ('product' === $post_type) {
                $price_html = bw_fpw_get_price_markup($post_id);

                if (function_exists('wc_get_product')) {
                    $product = wc_get_product($post_id);

                    if ($product) {
                        if ($product->is_type('variable')) {
                            $add_to_cart_url = $permalink;
                        } else {
                            $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : '';

                            if ($cart_url) {
                                $add_to_cart_url = add_query_arg('add-to-cart', $product->get_id(), $cart_url);
                            }
                        }

                        if (!$add_to_cart_url) {
                            $add_to_cart_url = $permalink;
                        }

                        $has_add_to_cart = true;
                    }
                }
            }

            $view_label = 'product' === $post_type
                ? esc_html__('View Product', 'bw-elementor-widgets')
                : esc_html__('Read More', 'bw-elementor-widgets');
            ?>
            <article <?php post_class('bw-fpw-item'); ?>>
                <div class="bw-fpw-card">
                    <div class="bw-slider-image-container">
                        <?php
                        $media_classes = ['bw-fpw-media'];
                        if (!$thumbnail_html) {
                            $media_classes[] = 'bw-fpw-media--placeholder';
                        }
                        ?>
                        <div class="<?php echo esc_attr(implode(' ', array_map('sanitize_html_class', $media_classes))); ?>">
                            <?php if ($thumbnail_html): ?>
                                <a class="bw-fpw-media-link" href="<?php echo esc_url($permalink); ?>">
                                    <div
                                        class="bw-fpw-image bw-slick-slider-image<?php echo $hover_image_html ? ' bw-fpw-image--has-hover bw-slick-slider-image--has-hover' : ''; ?>">
                                        <?php echo wp_kses_post($thumbnail_html); ?>
                                        <?php if ($hover_image_html): ?>
                                            <?php echo wp_kses_post($hover_image_html); ?>
                                        <?php endif; ?>
                                    </div>
                                </a>

                                <div class="bw-fpw-overlay overlay-buttons has-buttons">
                                    <div
                                        class="bw-fpw-overlay-buttons<?php echo $has_add_to_cart ? ' bw-fpw-overlay-buttons--double' : ''; ?>">
                                        <a class="bw-fpw-overlay-button overlay-button overlay-button--view"
                                            href="<?php echo esc_url($permalink); ?>">
                                            <span
                                                class="bw-fpw-overlay-button__label overlay-button__label"><?php echo $view_label; ?></span>
                                        </a>
                                        <?php if ('product' === $post_type && $has_add_to_cart && $add_to_cart_url): ?>
                                            <a class="bw-fpw-overlay-button overlay-button overlay-button--cart"
                                                href="<?php echo esc_url($add_to_cart_url); ?>" <?php echo $open_cart_popup ? ' data-open-cart-popup="1"' : ''; ?>>
                                                <span
                                                    class="bw-fpw-overlay-button__label overlay-button__label"><?php esc_html_e('Add to Cart', 'bw-elementor-widgets'); ?></span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="bw-fpw-image-placeholder" aria-hidden="true"></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="bw-fpw-content bw-slider-content">
                        <h3 class="bw-fpw-title">
                            <a href="<?php echo esc_url($permalink); ?>">
                                <?php echo esc_html($title); ?>
                            </a>
                        </h3>

                        <?php if (!empty($excerpt)): ?>
                            <div class="bw-fpw-description"><?php echo wp_kses_post($excerpt); ?></div>
                        <?php endif; ?>

                        <?php if ($price_html): ?>
                            <div class="bw-fpw-price price"><?php echo wp_kses_post($price_html); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php
            $rendered_posts++;
        }
    } elseif (1 === $page) {
        ?>
        <div class="bw-fpw-empty-state">
            <p class="bw-fpw-empty-message"><?php echo esc_html(bw_fpw_get_empty_state_message($subcategories, $tags, $search)); ?></p>
            <button class="elementor-button bw-fpw-reset-filters" data-widget-id="<?php echo esc_attr($widget_id); ?>">
                <?php esc_html_e('RESET FILTERS', 'bw-elementor-widgets'); ?>
            </button>
        </div>
        <?php
    }

    wp_reset_postdata();

    $html = ob_get_clean();

    $related_tags = bw_fpw_get_related_tags_data($post_type, $category, $subcategories, $search, $year_from, $year_to, $effective_context_slug, $advanced_filters);
    $available_types = bw_fpw_get_available_subcategories_data($post_type, $category, $tags, $search, $year_from, $year_to, $effective_context_slug, $advanced_filters);
    $available_tags = wp_list_pluck($related_tags, 'term_id');
    // On append loads no_found_rows is true, so found_posts is 0; preserve null
    // to signal JS that the displayed count should not be overwritten.
    $result_count = $is_append ? null : (int) $query->found_posts;

    $year_ui = 'product' === $post_type ? bw_fpw_get_year_filter_ui($effective_context_slug) : [
        'supported' => false,
        'context' => $effective_context_slug ?: 'mixed',
        'min' => null,
        'max' => null,
        'quick_ranges' => [],
    ];
    $advanced_filter_ui = 'product' === $post_type
        ? bw_fpw_get_advanced_filter_ui(
            $effective_context_slug,
            $base_candidate_post_ids,
            $advanced_filters
        )
        : [];

    $response_data = [
        'html' => $html,
        'tags_html' => bw_fpw_render_tag_markup($related_tags),
        'available_tags' => $available_tags,
        'available_types' => wp_list_pluck($available_types, 'term_id'),
        'result_count' => $result_count,
        'has_posts' => $has_posts,
        'page' => $response_page,
        'per_page' => $per_page,
        'has_more' => $has_more,
        'next_page' => $next_page,
        'offset' => $offset,
        'loaded_count' => $per_page > 0 ? $offset + $rendered_posts : $rendered_posts,
        'next_offset' => $has_more ? $offset + $rendered_posts : 0,
        'filter_ui' => [
            'types' => array_values($available_types),
            'tags' => array_values($related_tags),
            'result_count' => $result_count,
            'year' => $year_ui,
            'advanced' => $advanced_filter_ui,
        ],
    ];

    // PERFORMANCE: Cache result for 10 minutes (skip random order)
    if (!$skip_cache && isset($transient_key)) {
        set_transient($transient_key, $response_data, 10 * MINUTE_IN_SECONDS);
    }

    wp_send_json_success($response_data);
}

/**
 * Helper function per ottenere il markup del prezzo
 */
function bw_fpw_get_price_markup($post_id)
{
    if (!$post_id) {
        return '';
    }

    $format_price = static function ($value) {
        if ('' === $value || null === $value) {
            return '';
        }

        if (function_exists('wc_price') && is_numeric($value)) {
            return wc_price($value);
        }

        if (is_numeric($value)) {
            $value = number_format_i18n((float) $value, 2);
        }

        return esc_html($value);
    };

    if (function_exists('wc_get_product')) {
        $product = wc_get_product($post_id);
        if ($product) {
            $price_html = $product->get_price_html();
            if (!empty($price_html)) {
                return $price_html;
            }

            $regular_price = $product->get_regular_price();
            $sale_price = $product->get_sale_price();
            $current_price = $product->get_price();

            $regular_markup = $format_price($regular_price);
            $sale_markup = $format_price($sale_price);
            $current_markup = $format_price($current_price);

            if ($sale_markup && $regular_markup && $sale_markup !== $regular_markup) {
                return '<span class="price-original"><del>' . $regular_markup . '</del></span>' .
                    '<span class="price-sale">' . $sale_markup . '</span>';
            }

            if ($current_markup) {
                return '<span class="price-regular">' . $current_markup . '</span>';
            }
        }
    }

    $regular_price = get_post_meta($post_id, '_regular_price', true);
    $sale_price = get_post_meta($post_id, '_sale_price', true);
    $current_price = get_post_meta($post_id, '_price', true);

    if ('' === $current_price && '' === $regular_price && '' === $sale_price) {
        $additional_keys = ['price', 'product_price'];
        foreach ($additional_keys as $meta_key) {
            $meta_value = get_post_meta($post_id, $meta_key, true);
            if ('' !== $meta_value && null !== $meta_value) {
                $current_price = $meta_value;
                break;
            }
        }
    }

    $regular_markup = $format_price($regular_price);
    $sale_markup = $format_price($sale_price);
    $current_markup = $format_price($current_price);

    if ($sale_markup && $regular_markup && $sale_markup !== $regular_markup) {
        return '<span class="price-original"><del>' . $regular_markup . '</del></span>' .
            '<span class="price-sale">' . $sale_markup . '</span>';
    }

    if ($current_markup) {
        return '<span class="price-regular">' . $current_markup . '</span>';
    }

    if ($regular_markup) {
        return '<span class="price-regular">' . $regular_markup . '</span>';
    }

    return '';
}

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
