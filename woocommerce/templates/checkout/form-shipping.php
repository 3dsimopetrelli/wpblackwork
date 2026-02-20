<?php
/**
 * Checkout shipping information form
 *
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined('ABSPATH') || exit;

$section_settings = get_option('bw_checkout_fields_settings', []);
$heading_settings = isset($section_settings['section_headings']) && is_array($section_settings['section_headings'])
    ? $section_settings['section_headings']
    : [];
$hide_additional = !empty($heading_settings['hide_additional_info']);
$shipping_heading = __('Shipping details', 'bw');
$needs_shipping_address = WC()->cart && WC()->cart->needs_shipping_address();
$shipping_fields = $checkout->get_checkout_fields('shipping');
$shipping_rendered = [];
$shipping_order = [
    'shipping_country',
    'shipping_first_name',
    'shipping_last_name',
    'shipping_company',
    'shipping_address_1',
    'shipping_address_2',
    'shipping_postcode',
    'shipping_city',
    'shipping_state',
];
?>

<div class="woocommerce-shipping-fields">
    <?php if ($needs_shipping_address && !empty($shipping_fields)): ?>
        <div class="bw-checkout-section-heading bw-checkout-section-heading--shipping-address">
            <h2 class="checkout-section-title checkout-shipping-address-title"><?php echo esc_html($shipping_heading); ?></h2>
        </div>

        <input type="hidden" name="ship_to_different_address" value="1" />

        <div class="bw-billing-address" data-default-mode="same">
            <div class="bw-billing-address__options" role="radiogroup" aria-label="<?php echo esc_attr($shipping_heading); ?>">
                <label class="bw-billing-address__option bw-billing-address__option--same">
                    <input type="radio" name="bw_shipping_address_mode" value="same" checked="checked" />
                    <span><?php esc_html_e('Same as billing details', 'bw'); ?></span>
                </label>
                <label class="bw-billing-address__option bw-billing-address__option--different">
                    <input type="radio" name="bw_shipping_address_mode" value="different" />
                    <span><?php esc_html_e('Use a different shipping address', 'bw'); ?></span>
                </label>
            </div>

            <div class="bw-billing-address__accordion" aria-hidden="true">
                <div class="woocommerce-shipping-fields__field-wrapper bw-billing-address__field-wrapper">
                    <?php do_action('woocommerce_before_checkout_shipping_form', $checkout); ?>

                    <?php foreach ($shipping_order as $key): ?>
                        <?php if (!isset($shipping_fields[$key])): ?>
                            <?php continue; ?>
                        <?php endif; ?>
                        <?php woocommerce_form_field($key, $shipping_fields[$key], $checkout->get_value($key)); ?>
                        <?php $shipping_rendered[$key] = true; ?>
                    <?php endforeach; ?>

                    <?php foreach ($shipping_fields as $key => $field): ?>
                        <?php if (isset($shipping_rendered[$key])): ?>
                            <?php continue; ?>
                        <?php endif; ?>
                        <?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
                    <?php endforeach; ?>

                    <?php do_action('woocommerce_after_checkout_shipping_form', $checkout); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (wc_ship_to_billing_address_only() && WC()->cart->needs_shipping()): ?>
    <h3><?php esc_html_e('Billing &amp; Shipping', 'woocommerce'); ?></h3>
<?php endif; ?>

<div class="woocommerce-additional-fields">
    <?php if (!$hide_additional): ?>
        <h3><?php esc_html_e('Additional information', 'woocommerce'); ?></h3>
    <?php endif; ?>

    <?php do_action('woocommerce_before_order_notes', $checkout); ?>

    <?php
    $order_fields = $checkout->get_checkout_fields('order');
    if (!empty($order_fields)) {
        foreach ($order_fields as $key => $field) {
            woocommerce_form_field($key, $field, $checkout->get_value($key));
        }
    }
    ?>

    <?php do_action('woocommerce_after_order_notes', $checkout); ?>
</div>
