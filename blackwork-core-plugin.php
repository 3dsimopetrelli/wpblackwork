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

    $showcase_label_tag_file = __DIR__ . '/includes/dynamic-tags/class-bw-showcase-label-tag.php';

    if (file_exists($showcase_label_tag_file)) {
        require_once $showcase_label_tag_file;
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

add_action('init', 'bw_enqueue_slick_slider_assets');
add_action('elementor/editor/after_enqueue_scripts', 'bw_enqueue_slick_slider_admin_script');
add_action('init', 'bw_register_divider_style');
add_action('init', 'bw_register_button_widget_assets');
add_action('init', 'bw_register_about_menu_widget_assets');
add_action('init', 'bw_register_wallpost_widget_assets');
add_action('elementor/frontend/after_register_scripts', 'bw_enqueue_about_menu_widget_assets');
add_action('elementor/editor/after_enqueue_scripts', 'bw_enqueue_about_menu_widget_assets');
add_action('wp_enqueue_scripts', 'bw_enqueue_custom_class_assets');
add_action('elementor/frontend/after_enqueue_styles', 'bw_enqueue_custom_class_assets');
add_action('elementor/editor/after_enqueue_styles', 'bw_enqueue_custom_class_assets');
add_action('init', 'bw_register_product_grid_widget_assets');
add_action('elementor/frontend/after_enqueue_scripts', 'bw_enqueue_product_grid_widget_assets');
add_action('elementor/editor/after_enqueue_scripts', 'bw_enqueue_product_grid_widget_assets');
add_action('init', 'bw_register_animated_banner_widget_assets');
add_action('elementor/frontend/after_enqueue_scripts', 'bw_enqueue_animated_banner_widget_assets');
add_action('elementor/editor/after_enqueue_scripts', 'bw_enqueue_animated_banner_widget_assets');
add_action('wp_enqueue_scripts', 'bw_enqueue_smart_header_assets');
add_action('init', 'bw_register_static_showcase_widget_assets');
add_action('init', 'bw_register_related_products_widget_assets');
add_action('elementor/frontend/after_enqueue_scripts', 'bw_enqueue_related_products_widget_assets');
add_action('elementor/editor/after_enqueue_scripts', 'bw_enqueue_related_products_widget_assets');
add_action('init', 'bw_register_price_variation_widget_assets');
add_action('init', 'bw_register_presentation_slide_widget_assets');
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

function bw_enqueue_slick_slider_assets()
{
    wp_register_style(
        'slick-css',
        'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css',
        [],
        '1.8.1'
    );

    wp_register_script(
        'slick-js',
        'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js',
        ['jquery'],
        '1.8.1',
        true
    );

    $slick_slider_css_file = __DIR__ . '/assets/css/bw-slick-slider.css';
    $slick_slider_version = file_exists($slick_slider_css_file) ? filemtime($slick_slider_css_file) : '1.0.0';

    wp_register_style(
        'bw-slick-slider-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-slick-slider.css',
        [],
        $slick_slider_version
    );

    $showcase_css_file = __DIR__ . '/assets/css/bw-slide-showcase.css';
    $showcase_version = file_exists($showcase_css_file) ? filemtime($showcase_css_file) : '1.0.0';

    wp_register_style(
        'bw-slide-showcase-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-slide-showcase.css',
        [],
        $showcase_version
    );

    $product_slide_css_file = __DIR__ . '/assets/css/bw-product-slide.css';
    $product_slide_version = file_exists($product_slide_css_file) ? filemtime($product_slide_css_file) : '1.0.0';

    wp_register_style(
        'bw-product-slide-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-product-slide.css',
        [],
        $product_slide_version
    );

    $bw_custom_class_css_file = __DIR__ . '/assets/css/bw-custom-class.css';
    $custom_class_version = file_exists($bw_custom_class_css_file) ? filemtime($bw_custom_class_css_file) : '1.0.0';

    wp_register_style(
        'bw-fullbleed-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-custom-class.css',
        [],
        $custom_class_version
    );

    $slick_slider_js_file = __DIR__ . '/assets/js/bw-slick-slider.js';
    $slick_slider_js_version = file_exists($slick_slider_js_file) ? filemtime($slick_slider_js_file) : BLACKWORK_PLUGIN_VERSION;

    wp_register_script(
        'bw-slick-slider-js',
        plugin_dir_url(__FILE__) . 'assets/js/bw-slick-slider.js',
        ['jquery', 'slick-js'],
        $slick_slider_js_version,
        true
    );

    $product_slide_js_file = __DIR__ . '/assets/js/bw-product-slide.js';
    $product_slide_version_js = file_exists($product_slide_js_file) ? filemtime($product_slide_js_file) : '1.0.0';
    $product_slide_deps = ['jquery', 'slick-js'];

    if (class_exists('WooCommerce')) {
        $product_slide_deps[] = 'wc-add-to-cart-variation';
    }

    wp_register_script(
        'bw-product-slide-js',
        plugin_dir_url(__FILE__) . 'assets/js/bw-product-slide.js',
        $product_slide_deps,
        $product_slide_version_js,
        true
    );

    wp_localize_script(
        'bw-slick-slider-js',
        'bwSlickSlider',
        [
            'assetsUrl' => plugin_dir_url(__FILE__) . 'assets/',
        ]
    );
}

/**
 * Return SRI metadata for pinned static CDN assets.
 *
 * @return array
 */
function bw_get_cdn_sri_map()
{
    return [
        'styles' => [
            'slick-css' => [
                'src' => 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css',
                'integrity' => 'sha384-MUdXdzn1OB/0zkr4yGLnCqZ/n9ut5N7Ifes9RP2d5xKsTtcPiuiwthWczWuiqFOn',
            ],
            'select2' => [
                'src' => 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
                'integrity' => 'sha384-OXVF05DQEe311p6ohU11NwlnX08FzMCsyoXzGOaL+83dKAb3qS17yZJxESl8YrJQ',
            ],
        ],
        'scripts' => [
            'slick-js' => [
                'src' => 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js',
                'integrity' => 'sha384-YGnnOBKslPJVs35GG0TtAZ4uO7BHpHlqJhs0XK3k6cuVb6EBtl+8xcvIIOKV5wB+',
            ],
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

function bw_enqueue_slick_slider_admin_script()
{
    $admin_js_file = __DIR__ . '/assets/js/bw-slick-slider-admin.js';
    $admin_js_version = file_exists($admin_js_file) ? filemtime($admin_js_file) : '1.0.0';

    wp_enqueue_script(
        'bw-slick-slider-admin',
        plugin_dir_url(__FILE__) . 'assets/js/bw-slick-slider-admin.js',
        ['jquery'],
        $admin_js_version,
        true
    );

    wp_localize_script(
        'bw-slick-slider-admin',
        'bwSlickSliderAdmin',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bw_get_child_categories'),
        ]
    );

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
    if (!wp_style_is('bw-fullbleed-style', 'registered')) {
        bw_enqueue_slick_slider_assets();
    }

    if (wp_style_is('bw-fullbleed-style', 'registered')) {
        wp_enqueue_style('bw-fullbleed-style');
    }
}

function bw_register_button_widget_assets()
{
    bw_register_widget_assets('button');
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

    wp_register_style(
        'bw-product-card-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-product-card.css',
        [],
        $product_card_css_version
    );

    // Register related products widget CSS
    $css_file = __DIR__ . '/assets/css/bw-related-products.css';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_register_style(
        'bw-related-products-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-related-products.css',
        ['bw-product-card-style'], // Dipende dal CSS delle card
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
        [],
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

    // Localize script for AJAX (only if WooCommerce is loaded)
    if (function_exists('get_woocommerce_currency_symbol')) {
        wp_localize_script(
            'bw-price-variation-script',
            'bwPriceVariation',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bw_price_variation_nonce'),
                'priceFormat' => [
                    'symbol' => html_entity_decode(get_woocommerce_currency_symbol()),
                    'decimals' => wc_get_price_decimals(),
                    'decimal_separator' => wc_get_price_decimal_separator(),
                    'thousand_separator' => wc_get_price_thousand_separator(),
                    'format' => html_entity_decode(get_woocommerce_price_format()),
                ],
            ]
        );
    }
}

function bw_register_presentation_slide_widget_assets()
{
    $css_file = __DIR__ . '/assets/css/bw-presentation-slide.css';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_register_style(
        'bw-presentation-slide-style',
        plugin_dir_url(__FILE__) . 'assets/css/bw-presentation-slide.css',
        [],
        $css_version
    );

    $js_file = __DIR__ . '/assets/js/bw-presentation-slide.js';
    $js_version = file_exists($js_file) ? filemtime($js_file) : '1.0.0';

    wp_register_script(
        'bw-presentation-slide-script',
        plugin_dir_url(__FILE__) . 'assets/js/bw-presentation-slide.js',
        ['jquery', 'slick-js'],
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
    $valid_order_by = ['date', 'modified', 'title', 'rand', 'id'];

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
            <p class="bw-fpw-empty-message"><?php esc_html_e('No content available', 'bw-elementor-widgets'); ?></p>
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

    if (bw_fpw_is_throttled_request('bw_fpw_get_tags')) {
        bw_fpw_send_throttled_response('bw_fpw_get_tags');
    }

    // PERFORMANCE: Check transient cache first (5 minutes)
    // Canonicalize set-like subcategory input for stable cache keys.
    $subcategories_for_key = $subcategories;
    sort($subcategories_for_key, SORT_NUMERIC);
    $subcats_hash = md5(wp_json_encode($subcategories_for_key));
    $transient_key = 'bw_fpw_tags_' . $post_type . '_' . $category_id . '_' . $subcats_hash;
    $cached_result = get_transient($transient_key);

    if (false !== $cached_result) {
        wp_send_json_success($cached_result);
        return;
    }

    // Get tags using existing helper function
    $tags = bw_fpw_get_related_tags_data($post_type, $category_id, $subcategories);

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

function bw_fpw_get_filtered_post_ids_for_tags($post_type, $category, $subcategories)
{
    $taxonomy = 'product' === $post_type ? 'product_cat' : 'category';
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

    $query_args = [
        'post_type' => $post_type,
        'post_status' => 'publish',
        'posts_per_page' => bw_fpw_get_tag_source_posts_limit(),
        'paged' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ];

    if (!empty($tax_query)) {
        $query_args['tax_query'] = $tax_query;
    }

    $query = new WP_Query($query_args);

    return $query->posts;
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

function bw_fpw_get_related_tags_data($post_type, $category = 'all', $subcategories = [])
{
    $tag_taxonomy = 'product' === $post_type ? 'product_tag' : 'post_tag';

    if ('all' === $category || empty($category)) {
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

    $post_ids = bw_fpw_get_filtered_post_ids_for_tags($post_type, $category, $subcategories);

    return bw_fpw_collect_tags_from_posts($tag_taxonomy, $post_ids);
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

function bw_fpw_generate_cache_key($params)
{
    $params = is_array($params) ? $params : [];

    $canonical_payload = [
        'schema' => 'v1',
        'widget_id' => isset($params['widget_id']) ? (string) $params['widget_id'] : '',
        'post_type' => isset($params['post_type']) ? (string) $params['post_type'] : bw_fpw_get_default_post_type(),
        'category' => isset($params['category']) ? (string) $params['category'] : 'all',
        'subcategories' => bw_fpw_normalize_array_for_cache_key(isset($params['subcategories']) ? $params['subcategories'] : []),
        'tags' => bw_fpw_normalize_array_for_cache_key(isset($params['tags']) ? $params['tags'] : []),
        'image_toggle' => !empty($params['image_toggle']) ? 1 : 0,
        'image_size' => isset($params['image_size']) ? (string) $params['image_size'] : 'large',
        'image_mode' => isset($params['image_mode']) ? (string) $params['image_mode'] : 'proportional',
        'hover_effect' => !empty($params['hover_effect']) ? 1 : 0,
        'open_cart_popup' => !empty($params['open_cart_popup']) ? 1 : 0,
        'order_by' => isset($params['order_by']) ? (string) $params['order_by'] : 'date',
        'order' => isset($params['order']) ? (string) $params['order'] : 'DESC',
        'per_page' => isset($params['per_page']) ? (int) $params['per_page'] : bw_fpw_get_default_per_page(),
        'page' => isset($params['page']) ? (int) $params['page'] : 1,
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

function bw_fpw_ajax_refresh_nonce()
{
    wp_send_json_success(['nonce' => wp_create_nonce('bw_fpw_nonce')]);
}

function bw_fpw_filter_posts()
{
    check_ajax_referer('bw_fpw_nonce', 'nonce');

    $widget_id = bw_fpw_normalize_widget_id(isset($_POST['widget_id']) ? wp_unslash($_POST['widget_id']) : '');
    $post_type = bw_fpw_normalize_post_type(isset($_POST['post_type']) ? wp_unslash($_POST['post_type']) : bw_fpw_get_default_post_type());
    $category = bw_fpw_normalize_term_selector(isset($_POST['category']) ? wp_unslash($_POST['category']) : 'all');
    $subcategories = bw_fpw_normalize_int_array(isset($_POST['subcategories']) ? wp_unslash($_POST['subcategories']) : [], 50);
    $tags = bw_fpw_normalize_int_array(isset($_POST['tags']) ? wp_unslash($_POST['tags']) : [], 50);
    $image_toggle = bw_fpw_normalize_bool(isset($_POST['image_toggle']) ? wp_unslash($_POST['image_toggle']) : null, false);
    $image_size = bw_fpw_normalize_image_size(isset($_POST['image_size']) ? wp_unslash($_POST['image_size']) : 'large');
    $image_mode = bw_fpw_normalize_image_mode(isset($_POST['image_mode']) ? wp_unslash($_POST['image_mode']) : 'proportional');
    $hover_effect = bw_fpw_normalize_bool(isset($_POST['hover_effect']) ? wp_unslash($_POST['hover_effect']) : null, false);
    $open_cart_popup = bw_fpw_normalize_bool(isset($_POST['open_cart_popup']) ? wp_unslash($_POST['open_cart_popup']) : null, false);
    $order_by = bw_fpw_normalize_order_by(isset($_POST['order_by']) ? wp_unslash($_POST['order_by']) : 'date');
    $order = bw_fpw_normalize_order(isset($_POST['order']) ? wp_unslash($_POST['order']) : 'DESC');
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
            'category' => $category,
            'subcategories' => $subcategories,
            'tags' => $tags,
            'image_toggle' => $image_toggle,
            'image_size' => $image_size,
            'image_mode' => $image_mode,
            'hover_effect' => $hover_effect,
            'open_cart_popup' => $open_cart_popup,
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

    $taxonomy = 'product' === $post_type ? 'product_cat' : 'category';
    $tag_taxonomy = 'product' === $post_type ? 'product_tag' : 'post_tag';

    $query_posts_per_page = $per_page > 0 ? $per_page + 1 : -1;

    $query_args = [
        'post_type' => $post_type,
        'posts_per_page' => $query_posts_per_page,
        'post_status' => 'publish',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'orderby' => $order_by,
        'order' => $order,
    ];

    if ($per_page > 0 && $offset > 0) {
        $query_args['offset'] = $offset;
    } else {
        $query_args['paged'] = $page;
    }

    $tax_query = [];

    // Category filter
    if ('all' !== $category && absint($category) > 0) {
        if (!empty($subcategories)) {
            // Filter by subcategories
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field' => 'term_id',
                'terms' => $subcategories,
            ];
        } else {
            // Filter by parent category
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field' => 'term_id',
                'terms' => [absint($category)],
            ];
        }
    }

    // Tags filter
    if (!empty($tags)) {
        $tax_query[] = [
            'taxonomy' => $tag_taxonomy,
            'field' => 'term_id',
            'terms' => $tags,
        ];
    }

    // Add tax_query if not empty
    if (!empty($tax_query)) {
        if (count($tax_query) > 1) {
            $tax_query['relation'] = 'AND';
        }
        $query_args['tax_query'] = $tax_query;
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
            <p class="bw-fpw-empty-message"><?php esc_html_e('No content available', 'bw-elementor-widgets'); ?></p>
            <button class="elementor-button bw-fpw-reset-filters" data-widget-id="<?php echo esc_attr($widget_id); ?>">
                <?php esc_html_e('RESET FILTERS', 'bw-elementor-widgets'); ?>
            </button>
        </div>
        <?php
    }

    wp_reset_postdata();

    $html = ob_get_clean();

    $related_tags = bw_fpw_get_related_tags_data($post_type, $category, $subcategories);
    $available_tags = wp_list_pluck($related_tags, 'term_id');

    $response_data = [
        'html' => $html,
        'tags_html' => bw_fpw_render_tag_markup($related_tags),
        'available_tags' => $available_tags,
        'has_posts' => $has_posts,
        'page' => $response_page,
        'per_page' => $per_page,
        'has_more' => $has_more,
        'next_page' => $next_page,
        'offset' => $offset,
        'loaded_count' => $per_page > 0 ? $offset + $rendered_posts : $rendered_posts,
        'next_offset' => $has_more ? $offset + $rendered_posts : 0,
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
