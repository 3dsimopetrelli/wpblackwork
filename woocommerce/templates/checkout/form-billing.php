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
$needs_shipping_address = WC()->cart && WC()->cart->needs_shipping_address();

if (empty($fields)) {
    return;
}

$priority_keys = ['billing_email', 'bw_subscribe_newsletter'];
?>

<div class="woocommerce-billing-fields">
    <h3 style="display:none;"><?php esc_html_e('Billing details', 'woocommerce'); ?></h3>

    <?php do_action('woocommerce_before_checkout_billing_form', $checkout); ?>

    <div class="woocommerce-billing-fields__field-wrapper">
        <?php foreach ($priority_keys as $key): ?>
            <?php if (isset($fields[$key])): ?>
                <?php woocommerce_form_field($key, $fields[$key], $checkout->get_value($key)); ?>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if (!$needs_shipping_address): ?>
            <?php foreach ($fields as $key => $field): ?>
                <?php if (in_array($key, $priority_keys, true)): ?>
                    <?php continue; ?>
                <?php endif; ?>
                <?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php do_action('woocommerce_after_checkout_billing_form', $checkout); ?>
</div>
