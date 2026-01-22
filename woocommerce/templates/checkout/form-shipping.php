<?php
/**
 * Checkout shipping information form
 *
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

$section_settings = get_option( 'bw_checkout_fields_settings', [] );
$heading_settings = isset( $section_settings['section_headings'] ) && is_array( $section_settings['section_headings'] )
    ? $section_settings['section_headings']
    : [];
$hide_additional  = ! empty( $heading_settings['hide_additional_info'] );
?>

<div class="woocommerce-shipping-fields">
    <?php if ( true === WC()->cart->needs_shipping_address() ) : ?>
        <h3><?php esc_html_e( 'Ship to a different address?', 'woocommerce' ); ?></h3>

        <div class="shipping_address">
            <?php do_action( 'woocommerce_before_checkout_shipping_form', $checkout ); ?>

            <div class="woocommerce-shipping-fields__field-wrapper">
                <?php
                $fields = $checkout->get_checkout_fields( 'shipping' );

                foreach ( $fields as $key => $field ) {
                    woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
                }
                ?>
            </div>

            <?php do_action( 'woocommerce_after_checkout_shipping_form', $checkout ); ?>
        </div>
    <?php endif; ?>
</div>

<?php if ( wc_ship_to_billing_address_only() && WC()->cart->needs_shipping() ) : ?>
    <h3><?php esc_html_e( 'Billing &amp; Shipping', 'woocommerce' ); ?></h3>
<?php endif; ?>

<div class="woocommerce-additional-fields">
    <?php if ( ! $hide_additional ) : ?>
        <h3><?php esc_html_e( 'Additional information', 'woocommerce' ); ?></h3>
    <?php endif; ?>

    <?php do_action( 'woocommerce_before_order_notes', $checkout ); ?>

    <?php
    $order_fields = $checkout->get_checkout_fields( 'order' );
    if ( ! empty( $order_fields ) ) {
        foreach ( $order_fields as $key => $field ) {
            woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
        }
    }
    ?>

    <?php do_action( 'woocommerce_after_order_notes', $checkout ); ?>
</div>
