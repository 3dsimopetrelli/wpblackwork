<?php
/**
 * Checkout billing information form
 *
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined('ABSPATH') || exit;

$checkout = WC()->checkout();
$fields = $checkout->get_checkout_fields('billing');

if (empty($fields)) {
    return;
}

$section_settings = get_option('bw_checkout_fields_settings', []);
$heading_settings = isset($section_settings['section_headings']) && is_array($section_settings['section_headings'])
    ? $section_settings['section_headings']
    : [];
$address_heading = isset($heading_settings['address_heading_text']) ? sanitize_text_field($heading_settings['address_heading_text']) : __('Delivery', 'bw');

if ('' === $address_heading) {
    $address_heading = __('Delivery', 'bw');
}

/*
if ( function_exists( 'WC' ) && WC()->cart && ! WC()->cart->needs_shipping() && __( 'Delivery', 'bw' ) === $address_heading ) {
    $address_heading = __( 'Address', 'bw' );
}
*/

$priority_keys = ['billing_email', 'bw_subscribe_newsletter'];
$rendered_keys = [];

$has_address_fields = false;
$address_keys = [
    'billing_address_1',
    'billing_address_2',
    'billing_city',
    'billing_postcode',
    'billing_state',
    'billing_country',
];

foreach ($address_keys as $key) {
    if (isset($fields[$key])) {
        $has_address_fields = true;
        break;
    }
}
?>

<div class="woocommerce-billing-fields">
    <?php if (wc_ship_to_billing_address_only() && WC()->cart->needs_shipping()): ?>
        <h3><?php esc_html_e('Billing &amp; Shipping', 'woocommerce'); ?></h3>
    <?php else: ?>
        <h3><?php esc_html_e('Billing details', 'woocommerce'); ?></h3>
    <?php endif; ?>

    <?php do_action('woocommerce_before_checkout_billing_form', $checkout); ?>

    <div class="woocommerce-billing-fields__field-wrapper">
        <?php foreach ($priority_keys as $key): ?>
            <?php if (isset($fields[$key])): ?>
                <?php woocommerce_form_field($key, $fields[$key], $checkout->get_value($key)); ?>
                <?php $rendered_keys[$key] = true; ?>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if ($has_address_fields): ?>
            <div class="bw-checkout-section-heading bw-checkout-section-heading--delivery">
                <h2 class="checkout-section-title checkout-delivery-title"><?php echo esc_html($address_heading); ?></h2>
            </div>
        <?php endif; ?>

        <?php foreach ($fields as $key => $field): ?>
            <?php if (isset($rendered_keys[$key])): ?>
                <?php continue; ?>
            <?php endif; ?>
            <?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
        <?php endforeach; ?>
    </div>

    <?php do_action('woocommerce_after_checkout_billing_form', $checkout); ?>
</div>