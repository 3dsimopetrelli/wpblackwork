<?php
/**
 * BW Cart Pop-Up Module
 *
 * Modulo per gestire il cart pop-up laterale con slide-in animato
 *
 * @package BW_Cart_Popup
 * @version 1.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

// Definisci le costanti del modulo
if (!defined('BW_CART_POPUP_PATH')) {
    define('BW_CART_POPUP_PATH', plugin_dir_path(__FILE__));
}

if (!defined('BW_CART_POPUP_URL')) {
    define('BW_CART_POPUP_URL', plugin_dir_url(__FILE__));
}

// Includi il pannello admin
require_once BW_CART_POPUP_PATH . 'admin/settings-page.php';

// Includi la logica frontend
require_once BW_CART_POPUP_PATH . 'frontend/cart-popup-frontend.php';

/**
 * Determine whether Cart Popup runtime assets/markup are needed for current request.
 *
 * Conservative defaults keep compatibility for common trigger surfaces while
 * allowing explicit integrations to force runtime through a filter.
 *
 * @return bool
 */
function bw_cart_popup_should_load_assets() {
    // Verifica se WooCommerce è attivo
    if (!class_exists('WooCommerce')) {
        return false;
    }

    $is_active = (int) get_option('bw_cart_popup_active', 0) === 1;
    $has_floating_trigger = (int) get_option('bw_cart_popup_show_floating_trigger', 0) === 1;
    $disable_on_checkout = (int) get_option('bw_cart_popup_disable_on_checkout', 1) === 1;

    if ($disable_on_checkout && function_exists('is_checkout') && is_checkout()) {
        return (bool) apply_filters('bw_cart_popup_should_load_assets', false);
    }

    // Keep header/cart integration behavior unchanged when custom header runtime is active.
    $has_header_popup_integration = function_exists('bw_header_is_enabled') && bw_header_is_enabled();

    // Preserve common WooCommerce trigger contexts.
    $is_woocommerce_context = function_exists('is_woocommerce') && is_woocommerce();
    if (!$is_woocommerce_context && function_exists('is_cart') && is_cart()) {
        $is_woocommerce_context = true;
    }
    if (!$is_woocommerce_context && function_exists('is_checkout') && is_checkout()) {
        $is_woocommerce_context = true;
    }

    // Preserve popup usage on Elementor-built singular pages where widget triggers are likely.
    $is_elementor_singular = false;
    if (function_exists('is_singular') && is_singular()) {
        $post_id = absint(get_queried_object_id());
        if ($post_id > 0) {
            $is_elementor_singular = metadata_exists('post', $post_id, '_elementor_data')
                || 'builder' === get_post_meta($post_id, '_elementor_edit_mode', true);
        }
    }

    $should_load = $is_active
        || $has_floating_trigger
        || $has_header_popup_integration
        || $is_woocommerce_context
        || $is_elementor_singular;

    /**
     * Filter effective Cart Popup runtime requirement for current request.
     *
     * @param bool $should_load Computed default requirement.
     */
    return (bool) apply_filters('bw_cart_popup_should_load_assets', $should_load);
}

/**
 * Cleanup: Rimuove l'opzione obsoleta bw_cart_popup_checkout_url
 * Questa opzione è stata rimossa per garantire che il pulsante checkout
 * porti sempre alla pagina di checkout WooCommerce standard
 */
function bw_cart_popup_cleanup_obsolete_options() {
    // Elimina l'opzione obsoleta se esiste
    delete_option('bw_cart_popup_checkout_url');
}
add_action('admin_init', 'bw_cart_popup_cleanup_obsolete_options');

/**
 * Registra e carica gli assets del Cart Pop-Up
 */
function bw_cart_popup_register_assets() {
    // Verifica se WooCommerce è attivo
    if (!class_exists('WooCommerce')) {
        return;
    }

    // CSS del Cart Pop-Up
    $css_file = BW_CART_POPUP_PATH . 'assets/css/bw-cart-popup.css';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.1';

    wp_register_style(
        'bw-cart-popup-style',
        BW_CART_POPUP_URL . 'assets/css/bw-cart-popup.css',
        [],
        $css_version
    );

    // JavaScript del Cart Pop-Up
    $js_file = BW_CART_POPUP_PATH . 'assets/js/bw-cart-popup.js';
    $js_version = file_exists($js_file) ? filemtime($js_file) : '1.0.1';

    wp_register_script(
        'bw-cart-popup-script',
        BW_CART_POPUP_URL . 'assets/js/bw-cart-popup.js',
        ['jquery'],
        $js_version,
        true
    );

    // Localizza script per AJAX e configurazioni
    $cart_popup_settings = [
        'active' => get_option('bw_cart_popup_active', 0),
        'slide_animation' => get_option('bw_cart_popup_slide_animation', 1),
        'panel_width' => get_option('bw_cart_popup_panel_width', 400),
        'overlay_color' => get_option('bw_cart_popup_overlay_color', '#000000'),
        'overlay_opacity' => get_option('bw_cart_popup_overlay_opacity', 0.5),
        'panel_bg_color' => get_option('bw_cart_popup_panel_bg', '#ffffff'),
        'checkout_btn_text' => get_option('bw_cart_popup_checkout_text', 'Proceed to checkout'),
        'checkout_btn_color' => get_option('bw_cart_popup_checkout_color', '#28a745'),
        'continue_btn_text' => get_option('bw_cart_popup_continue_text', 'Continue shopping'),
        'continue_btn_color' => get_option('bw_cart_popup_continue_color', '#6c757d'),
        'show_quantity_badge' => get_option('bw_cart_popup_show_quantity_badge', 1),
        'show_floating_trigger' => get_option('bw_cart_popup_show_floating_trigger', 0),
    ];

    $wc_ajax_url = admin_url('admin-ajax.php');
    if ( class_exists( 'WC_AJAX' ) && method_exists( 'WC_AJAX', 'get_endpoint' ) ) {
        $wc_ajax_url = WC_AJAX::get_endpoint('%%endpoint%%');
    }

    $checkout_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/');

    wp_localize_script(
        'bw-cart-popup-script',
        'bwCartPopupConfig',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bw_cart_popup_nonce'),
            'settings' => $cart_popup_settings,
            'checkoutUrl' => $checkout_url,
            'shopUrl' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/'),
            'cartUrl' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/'),
            'wc_ajax_url' => $wc_ajax_url,
        ]
    );
}
/**
 * Carica gli assets del Cart Pop-Up nel frontend
 * NOTA: Gli assets vengono sempre caricati perché sono necessari anche per i widget
 * (anche se l'opzione globale cart popup è disattivata)
 */
function bw_cart_popup_enqueue_assets() {
    // Verifica se WooCommerce è attivo
    if (!class_exists('WooCommerce')) {
        return;
    }

    // Non caricare nell'admin
    if (is_admin()) {
        return;
    }

    if (!bw_cart_popup_should_load_assets()) {
        return;
    }

    // Registra gli assets se non già fatto
    if (!wp_style_is('bw-cart-popup-style', 'registered') || !wp_script_is('bw-cart-popup-script', 'registered')) {
        bw_cart_popup_register_assets();
    }

    // Carica CSS e JS
    wp_enqueue_style('bw-cart-popup-style');
    wp_enqueue_script('bw-cart-popup-script');
}
add_action('wp_enqueue_scripts', 'bw_cart_popup_enqueue_assets', 20);

/**
 * Rimuove completamente il link "View cart" di WooCommerce
 * Questo previene che WooCommerce aggiunga il link dopo l'Add to Cart
 */
function bw_cart_popup_remove_view_cart_link($message, $product_id = null, $product_data = null) {
    // Rimuovi il link "View cart" dal messaggio di successo di WooCommerce
    // Il messaggio originale contiene un link <a href="...">View cart</a> che vogliamo rimuovere
    return $message;
}

/**
 * Filtro per modificare il template WooCommerce add-to-cart
 * Rimuove il link "added_to_cart wc-forward" che WooCommerce aggiunge automaticamente
 */
function bw_cart_popup_hide_view_cart_button($button, $product, $args) {
    // Non modificare il pulsante principale, solo impedire l'aggiunta del link "View cart"
    return $button;
}
add_filter('woocommerce_loop_add_to_cart_link', 'bw_cart_popup_hide_view_cart_button', 10, 3);

/**
 * Rimuove il link "View cart" via CSS come ulteriore sicurezza
 * Questo CSS inline viene aggiunto solo se necessario
 */
function bw_cart_popup_hide_view_cart_css() {
    echo '<style>.added_to_cart.wc-forward { display: none !important; }</style>';
}
add_action('wp_head', 'bw_cart_popup_hide_view_cart_css');
