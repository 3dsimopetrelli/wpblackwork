<?php
/**
 * WooCommerce runtime — Stripe / PayPal / Google Pay gateway tweaks.
 *
 * Extracted verbatim from woocommerce-init.php (Phase 3b, BW-TASK-20260629). Function bodies are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hide PayPal Advanced Card Processing gateway (duplicate of Stripe).
 * We use Stripe for all card payments.
 *
 * @param array $available_gateways Available payment gateways.
 * @return array Filtered payment gateways.
 */
function bw_mew_hide_paypal_advanced_card_processing($available_gateways)
{
    if (!is_admin() && is_checkout()) {
        // Remove PayPal Advanced Card Processing (ppcp-credit-card-gateway)
        // This is a duplicate of Stripe and causes confusion
        if (isset($available_gateways['ppcp-credit-card-gateway'])) {
            unset($available_gateways['ppcp-credit-card-gateway']);
        }

        // Also remove WooCommerce Payments if present (another duplicate)
        if (isset($available_gateways['woocommerce_payments'])) {
            unset($available_gateways['woocommerce_payments']);
        }
    }

    return $available_gateways;
}

/**
 * Customize Stripe Elements styling to match Shopify design.
 * Uses WooCommerce Stripe Gateway filter to configure appearance.
 *
 * @param array $options Stripe Elements options.
 * @return array Modified options with custom styling.
 */
function bw_mew_customize_stripe_elements_style($options)
{
    // Ensure 8px border radius for inputs (Legacy Style API)
    $style = array(
        'base' => array(
            'color' => '#1f2937',
            'fontFamily' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            'fontSize' => '15px',
            '::placeholder' => array(
                'color' => '#9ca3af',
            ),
        ),
        'invalid' => array(
            'color' => '#991b1b',
        ),
    );

    // If this is the 'styling' filter, return just the style array
    if (current_filter() === 'wc_stripe_elements_styling') {
        return $style;
    }

    // Otherwise, assume it's 'elements_options' and add Appearance API (UPE/Modern)
    $options['style'] = $style;
    $options['appearance'] = array(
        'theme' => 'flat',
        'variables' => array(
            'colorPrimary' => '#000000',
            'colorBackground' => '#ffffff',
            'colorText' => '#1f2937',
            'colorDanger' => '#991b1b',
            'colorSuccess' => '#27ae60',
            'fontFamily' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            'fontSizeBase' => '15px',
            'fontWeightNormal' => '400',
            'fontWeightMedium' => '500',
            'borderRadius' => '0px',
            'spacingUnit' => '4px',
            'gridRowGap' => '20px',
            'gridColumnGap' => '12px',
        ),
        'rules' => array(
            '.Input' => array(
                'border' => '1px solid #d1d5db',
                'boxShadow' => 'none',
                'padding' => '16px 18px',
                'borderRadius' => '8px',
            ),
            '.Label' => array(
                'display' => 'block',
                'textAlign' => 'center',
                'marginBottom' => '8px',
            ),
            '.Block' => array(
                'backgroundColor' => 'transparent',
                'border' => 'none',
                'boxShadow' => 'none',
                'padding' => '0',
                'margin' => '0',
            ),
            '.PaymentElement' => array(
                'padding' => '0',
            ),
        ),
    );

    return $options;
}

/**
 * Customize Stripe UPE (Unified Payment Element) appearance.
 * This filter modifies the JavaScript params passed to Stripe UPE iframe.
 *
 * @param array $params UPE initialization params.
 * @return array Modified params with custom appearance.
 */
function bw_mew_customize_stripe_upe_appearance($params)
{
    // Add appearance configuration to UPE params
    $params['appearance'] = array(
        'theme' => 'flat',
        'variables' => array(
            'colorPrimary' => '#000000',
            'colorBackground' => '#ffffff',
            'colorText' => '#1f2937',
            'colorDanger' => '#991b1b',
            'colorSuccess' => '#27ae60',
            'fontFamily' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            'fontSizeBase' => '15px',
            'fontWeightNormal' => '400',
            'fontWeightMedium' => '500',
            'borderRadius' => '0px',
            'spacingUnit' => '4px',
            'gridRowGap' => '20px',
            'gridColumnGap' => '12px',
        ),
        'rules' => array(
            '.Input' => array(
                'border' => '1px solid #d1d5db',
                'boxShadow' => 'none',
                'padding' => '16px 18px',
                'borderRadius' => '8px',
            ),
            '.Input:hover' => array(
                'borderColor' => '#9ca3af',
            ),
            '.Input:focus' => array(
                'borderColor' => '#3b82f6',
                'boxShadow' => '0 0 0 3px rgba(59, 130, 246, 0.1)',
            ),
            '.Input--invalid' => array(
                'borderColor' => '#fecaca',
                'backgroundColor' => '#fef2f2',
            ),
            '.Label' => array(
                'display' => 'block',
                'textAlign' => 'center',
                'marginBottom' => '8px',
            ),
            '.Block' => array(
                'backgroundColor' => 'transparent',
                'border' => 'none',
                'boxShadow' => 'none',
                'padding' => '0',
                'margin' => '0',
            ),
            '.BlockItem' => array(
                'backgroundColor' => 'transparent',
                'border' => 'none',
                'boxShadow' => 'none',
                'padding' => '0',
                'margin' => '0',
            ),
            // FIX 1: Error message icon positioning - use !important to override Stripe defaults
            '.Error' => array(
                'display' => 'flex',
                'flexDirection' => 'row',
                'alignItems' => 'flex-start',
                'gap' => '8px',
                'marginTop' => '8px',
                'fontSize' => '13px',
                'lineHeight' => '1.4',
                'color' => '#991b1b',
            ),
            '.ErrorIcon' => array(
                'flexShrink' => '0',
                'width' => '16px',
                'height' => '16px',
                'minWidth' => '16px',
                'marginTop' => '2px',
                'marginRight' => '0',
                'marginBottom' => '0',
                'marginLeft' => '0',
                'display' => 'inline-flex',
            ),
            '.ErrorText' => array(
                'flex' => '1 1 auto',
                'marginTop' => '0',
                'marginRight' => '0',
                'marginBottom' => '0',
                'marginLeft' => '0',
                'display' => 'inline-block',
            ),
            '.Tab' => array(
                'display' => 'none',
            ),
            '.TabLabel' => array(
                'display' => 'none',
            ),
            '.TabIcon' => array(
                'display' => 'none',
            ),
            '.Accordion' => array(
                'border' => 'none',
                'boxShadow' => 'none',
                'padding' => '0',
                'margin' => '0 !important',
            ),
            '.AccordionItem' => array(
                'border' => 'none',
                'boxShadow' => 'none',
                'backgroundColor' => 'transparent',
                'padding' => '0',
                'margin' => '0 !important',
            ),
            '.AccordionItemHeader' => array(
                'display' => 'none',
            ),
            '.PickerItem' => array(
                'display' => 'none',
            ),
            '.PaymentMethod' => array(
                'padding' => '0',
                'border' => 'none',
                'margin' => '0',
            ),
            '.PaymentMethodHeader' => array(
                'display' => 'none !important',
            ),
            '.PaymentElement' => array(
                'padding' => '0',
            ),
            '.AccordionButton' => array(
                'display' => 'none !important',
            ),
        ),
    );

    // FIX 3: Configure fields to auto-collect billing details from WooCommerce checkout form
    // This prevents the "You specified 'never' but did not pass billing_details.name" error
    $params['fields'] = array(
        'billingDetails' => array(
            'name' => 'auto',    // Auto-collect from WC checkout form
            'email' => 'auto',   // Auto-collect from WC checkout form
            'phone' => 'auto',   // Auto-collect from WC checkout form
            'address' => array(
                'country' => 'auto',
                'line1' => 'auto',
                'line2' => 'auto',
                'city' => 'auto',
                'state' => 'auto',
                'postalCode' => 'auto',
            ),
        ),
    );

    return $params;
}

/**
 * Add BlackWork custom gateways to WooCommerce payment gateways.
 *
 * @param array $gateways WooCommerce gateways.
 * @return array
 */
function bw_mew_add_google_pay_gateway($gateways)
{
    if (class_exists('BW_Google_Pay_Gateway')) {
        $gateways[] = 'BW_Google_Pay_Gateway';
    }
    if (class_exists('BW_Klarna_Gateway')) {
        $gateways[] = 'BW_Klarna_Gateway';
    }
    if (class_exists('BW_Apple_Pay_Gateway')) {
        $gateways[] = 'BW_Apple_Pay_Gateway';
    }
    return $gateways;
}
