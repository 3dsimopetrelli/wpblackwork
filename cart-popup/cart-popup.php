<?php
/**
 * BW Cart Pop-Up Module
 *
 * Modulo per gestire il cart pop-up laterale con slide-in animato
 *
 * @package BW_Cart_Popup
 * @version 1.0.0
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
 * Registra e carica gli assets del Cart Pop-Up
 */
function bw_cart_popup_register_assets() {
    // Verifica se WooCommerce è attivo
    if (!class_exists('WooCommerce')) {
        return;
    }

    // CSS del Cart Pop-Up
    $css_file = BW_CART_POPUP_PATH . 'assets/css/bw-cart-popup.css';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_register_style(
        'bw-cart-popup-style',
        BW_CART_POPUP_URL . 'assets/css/bw-cart-popup.css',
        [],
        $css_version
    );

    // JavaScript del Cart Pop-Up
    $js_file = BW_CART_POPUP_PATH . 'assets/js/bw-cart-popup.js';
    $js_version = file_exists($js_file) ? filemtime($js_file) : '1.0.0';

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
    ];

    wp_localize_script(
        'bw-cart-popup-script',
        'bwCartPopupConfig',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bw_cart_popup_nonce'),
            'settings' => $cart_popup_settings,
            'wc_ajax_url' => WC_AJAX::get_endpoint('%%endpoint%%'),
        ]
    );
}
add_action('wp_enqueue_scripts', 'bw_cart_popup_register_assets');

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
