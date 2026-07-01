<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize WooCommerce customizations for BW Elementor Widgets.
 */
function bw_mew_initialize_woocommerce_overrides()
{
    if (!class_exists('WooCommerce')) {
        return;
    }

    $my_account_file = BW_MEW_PATH . 'includes/woocommerce-overrides/class-bw-my-account.php';
    $supabase_file = BW_MEW_PATH . 'includes/woocommerce-overrides/class-bw-supabase-auth.php';
    $email_styles_file = BW_MEW_PATH . 'includes/woocommerce-overrides/class-bw-email-styles.php';

    if (file_exists($my_account_file)) {
        require_once $my_account_file;
    }

    if (file_exists($supabase_file)) {
        require_once $supabase_file;
    }

    if (file_exists($email_styles_file)) {
        require_once $email_styles_file;
    }
    // Force "Ship to a different address?" checkbox to be unchecked by default
    add_filter('woocommerce_ship_to_different_address_checked', '__return_false');

    $notice_manager_file = BW_MEW_PATH . 'includes/woocommerce-overrides/class-bw-notice-manager.php';
    if (file_exists($notice_manager_file)) {
        require_once $notice_manager_file;
        BW_Notice_Manager::init();
    }

    $stripe_api_client_file = BW_MEW_PATH . 'includes/Stripe/class-bw-stripe-api-client.php';
    if (file_exists($stripe_api_client_file)) {
        require_once $stripe_api_client_file;
    }

    $stripe_safe_logger_file = BW_MEW_PATH . 'includes/Utils/class-bw-stripe-safe-logger.php';
    if (file_exists($stripe_safe_logger_file)) {
        require_once $stripe_safe_logger_file;
    }

    $stripe_abstract_gateway_file = BW_MEW_PATH . 'includes/Gateways/class-bw-abstract-stripe-gateway.php';
    if (file_exists($stripe_abstract_gateway_file)) {
        require_once $stripe_abstract_gateway_file;
    }

    $klarna_gateway_file = BW_MEW_PATH . 'includes/Gateways/class-bw-klarna-gateway.php';
    if (file_exists($klarna_gateway_file)) {
        require_once $klarna_gateway_file;
    }

    $google_pay_file = BW_MEW_PATH . 'includes/woocommerce-overrides/class-bw-google-pay-gateway.php';
    if (file_exists($google_pay_file)) {
        require_once $google_pay_file;
    }

    add_filter('woocommerce_locate_template', 'bw_mew_locate_template', 1, 3);
    add_action('wp_enqueue_scripts', 'bw_mew_enqueue_related_products_assets', 30);
    add_action('wp_enqueue_scripts', 'bw_mew_enqueue_product_labels_assets', 20);
    add_action('wp_enqueue_scripts', 'bw_mew_enqueue_account_page_assets', 20);
    add_action('wp_enqueue_scripts', 'bw_mew_enqueue_checkout_assets', 20);
    add_action('wp_enqueue_scripts', 'bw_mew_enqueue_order_confirmation_assets', 20);
    add_filter('woocommerce_locate_core_template', 'bw_mew_locate_template', 1, 3);
    add_action('template_redirect', 'bw_mew_prepare_account_page_layout', 9);
    add_action('template_redirect', 'bw_mew_prepare_checkout_layout', 9);
    add_action('template_redirect', 'bw_mew_prepare_theme_title_bypass', 8);
    add_action('template_redirect', 'bw_mew_hide_single_product_notices', 9);
    add_action('template_redirect', 'bw_mew_prepare_single_product_labels', 9);
    add_action('woocommerce_checkout_update_order_review', 'bw_mew_sync_checkout_cart_quantities', 10, 1);
    add_filter('woocommerce_checkout_posted_data', 'bw_mew_sync_billing_from_shipping_mode', 20, 1);
    add_action('wp_ajax_bw_apply_coupon', 'bw_mew_ajax_apply_coupon');
    add_action('wp_ajax_nopriv_bw_apply_coupon', 'bw_mew_ajax_apply_coupon');
    add_action('wp_ajax_bw_remove_coupon', 'bw_mew_ajax_remove_coupon');
    add_action('wp_ajax_nopriv_bw_remove_coupon', 'bw_mew_ajax_remove_coupon');
    add_filter('the_title', 'bw_mew_filter_account_page_title', 10, 2);
    add_filter('woocommerce_available_payment_gateways', 'bw_mew_hide_paypal_advanced_card_processing');
    add_filter('wc_stripe_elements_options', 'bw_mew_customize_stripe_elements_style');
    add_filter('wc_stripe_elements_styling', 'bw_mew_customize_stripe_elements_style');
    add_filter('wc_stripe_upe_params', 'bw_mew_customize_stripe_upe_appearance');
    add_filter('body_class', 'bw_mew_add_section_heading_body_classes');
    add_action('woocommerce_checkout_before_customer_details', 'bw_mew_render_address_section_heading', 5);
    add_action('wp_enqueue_scripts', 'bw_mew_enqueue_cart_assets', 20);
    add_action('template_redirect', 'bw_mew_prepare_cart_layout', 9);
    add_filter('woocommerce_payment_gateways', 'bw_mew_add_google_pay_gateway');
}
add_action('plugins_loaded', 'bw_mew_initialize_woocommerce_overrides');

add_action('wp_enqueue_scripts', 'bw_mew_enqueue_supabase_bridge', 20);

add_action('wp_head', 'bw_mew_supabase_early_invite_redirect_hint', 1);

add_action('template_redirect', 'bw_mew_prevent_order_received_cache', 1);

add_action('template_redirect', 'bw_mew_handle_wallet_failed_return_redirect', 2);

add_action('template_redirect', 'bw_mew_hide_logged_in_account_title', 9);

/**
 * DEPRECATED FUNCTIONS
 *
 * The following functions have been moved to the BW_Social_Login class.
 * They are kept here as deprecated stubs for backward compatibility.
 * Use BW_Social_Login class methods directly instead.
 */

add_action('woocommerce_checkout_before_customer_details', 'bw_mew_render_express_divider', 100);

add_filter('woocommerce_shipping_package_name', 'bw_mew_checkout_shipping_package_name', 10, 4);

add_filter('woocommerce_cart_shipping_method_full_label', 'bw_mew_checkout_shipping_method_full_label', 10, 2);

// --- Runtime split (3b, BW-TASK-20260629): callbacks moved to focused files; all
// function names + hook registrations preserved (see files below). ---
require_once __DIR__ . '/runtime/templates.php';
require_once __DIR__ . '/runtime/account.php';
require_once __DIR__ . '/runtime/auth-social.php';
require_once __DIR__ . '/runtime/checkout.php';
require_once __DIR__ . '/runtime/order-received.php';
require_once __DIR__ . '/runtime/payment-gateways.php';
require_once __DIR__ . '/runtime/cart.php';
