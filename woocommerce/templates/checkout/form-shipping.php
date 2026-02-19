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
$address_heading = isset($heading_settings['address_heading_text']) ? sanitize_text_field($heading_settings['address_heading_text']) : __('Delivery', 'bw');
$billing_heading = __('Billing address', 'bw');
$needs_shipping_address = WC()->cart && WC()->cart->needs_shipping_address();
$billing_fields = $checkout->get_checkout_fields('billing');
$billing_rendered = [];
$billing_excluded = ['billing_email', 'bw_subscribe_newsletter'];
$billing_order = [
    'billing_country',
    'billing_first_name',
    'billing_last_name',
    'billing_company',
    'billing_address_1',
    'billing_address_2',
    'billing_postcode',
    'billing_city',
    'billing_state',
    'billing_phone',
];
?>

<div class="woocommerce-shipping-fields">
    <?php if ($needs_shipping_address): ?>
        <div class="bw-checkout-section-heading bw-checkout-section-heading--delivery">
            <h2 class="checkout-section-title checkout-delivery-title"><?php echo esc_html($address_heading); ?></h2>
        </div>

        <input type="hidden" name="ship_to_different_address" value="1" />

        <div class="shipping_address" style="display:block;">
            <?php do_action('woocommerce_before_checkout_shipping_form', $checkout); ?>

            <div class="woocommerce-shipping-fields__field-wrapper">
                <?php
                $fields = $checkout->get_checkout_fields('shipping');

                foreach ($fields as $key => $field) {
                    woocommerce_form_field($key, $field, $checkout->get_value($key));
                }
                ?>
            </div>

            <?php do_action('woocommerce_after_checkout_shipping_form', $checkout); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($billing_fields)): ?>
        <div class="bw-billing-address <?php echo $needs_shipping_address ? '' : 'is-different bw-billing-address--standalone'; ?>" data-default-mode="<?php echo $needs_shipping_address ? 'same' : 'different'; ?>">
            <h2 class="checkout-section-title checkout-billing-title"><?php echo esc_html($billing_heading); ?></h2>

            <?php if ($needs_shipping_address): ?>
                <div class="bw-billing-address__options" role="radiogroup" aria-label="<?php echo esc_attr($billing_heading); ?>">
                    <label class="bw-billing-address__option bw-billing-address__option--same">
                        <input type="radio" name="bw_billing_address_mode" value="same" checked="checked" />
                        <span><?php esc_html_e('Same as shipping address', 'bw'); ?></span>
                    </label>
                    <label class="bw-billing-address__option bw-billing-address__option--different">
                        <input type="radio" name="bw_billing_address_mode" value="different" />
                        <span><?php esc_html_e('Use a different billing address', 'bw'); ?></span>
                    </label>
                </div>
            <?php else: ?>
                <input type="hidden" name="bw_billing_address_mode" value="different" />
            <?php endif; ?>

            <div class="bw-billing-address__accordion" aria-hidden="<?php echo $needs_shipping_address ? 'true' : 'false'; ?>">
                <div class="woocommerce-billing-fields__field-wrapper bw-billing-address__field-wrapper">
                    <?php foreach ($billing_order as $key): ?>
                        <?php if (!isset($billing_fields[$key])): ?>
                            <?php continue; ?>
                        <?php endif; ?>
                        <?php if (in_array($key, $billing_excluded, true)): ?>
                            <?php continue; ?>
                        <?php endif; ?>
                        <?php woocommerce_form_field($key, $billing_fields[$key], $checkout->get_value($key)); ?>
                        <?php $billing_rendered[$key] = true; ?>
                    <?php endforeach; ?>

                    <?php foreach ($billing_fields as $key => $field): ?>
                        <?php if (isset($billing_rendered[$key])): ?>
                            <?php continue; ?>
                        <?php endif; ?>
                        <?php if (in_array($key, $billing_excluded, true)): ?>
                            <?php continue; ?>
                        <?php endif; ?>
                        <?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
                    <?php endforeach; ?>
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
