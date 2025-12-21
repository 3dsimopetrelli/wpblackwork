<?php
/**
 * Checkout Form
 *
 * @package WooCommerce
 */

defined( 'ABSPATH' ) || exit;

$settings = function_exists( 'bw_mew_get_checkout_settings' ) ? bw_mew_get_checkout_settings() : [
    'logo'         => '',
    'left_bg'      => '#ffffff',
    'right_bg'     => '#f7f7f7',
    'border_color' => '#e0e0e0',
    'legal_text'   => '',
];

$checkout = WC()->checkout();
$order_button_text = apply_filters( 'woocommerce_order_button_text', __( 'Place order', 'woocommerce' ) );

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
    echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
    return;
}
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout bw-checkout-form" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
    <div class="bw-checkout-wrapper">
        <div class="bw-checkout-grid">
            <div class="bw-checkout-left">
                <?php if ( ! empty( $settings['logo'] ) ) : ?>
                    <div class="bw-checkout-logo" style="padding: <?php echo esc_attr( $settings['logo_padding_top'] ); ?>px <?php echo esc_attr( $settings['logo_padding_right'] ); ?>px <?php echo esc_attr( $settings['logo_padding_bottom'] ); ?>px <?php echo esc_attr( $settings['logo_padding_left'] ); ?>px;">
                        <img src="<?php echo esc_url( $settings['logo'] ); ?>" alt="<?php esc_attr_e( 'Checkout logo', 'bw' ); ?>" style="max-width: <?php echo esc_attr( $settings['logo_width'] ); ?>px;" />
                    </div>
                <?php endif; ?>

                <div class="bw-checkout-express">
                    <div class="bw-checkout-express__title"><?php esc_html_e( 'Express checkout', 'woocommerce' ); ?></div>
                    <div class="bw-checkout-express__buttons" role="group" aria-label="<?php esc_attr_e( 'Express checkout options', 'woocommerce' ); ?>">
                        <button type="button" class="bw-express-btn bw-express-btn--shop"><?php esc_html_e( 'Shop', 'bw' ); ?></button>
                        <button type="button" class="bw-express-btn bw-express-btn--paypal"><?php esc_html_e( 'PayPal', 'woocommerce' ); ?></button>
                        <button type="button" class="bw-express-btn bw-express-btn--gpay"><?php esc_html_e( 'G Pay', 'bw' ); ?></button>
                    </div>
                    <div class="bw-checkout-express__divider" role="separator" aria-label="<?php esc_attr_e( 'OR', 'woocommerce' ); ?>">
                        <span><?php esc_html_e( 'OR', 'woocommerce' ); ?></span>
                    </div>
                </div>

                <?php if ( $checkout->get_checkout_fields() ) : ?>
                    <?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

                    <div id="customer_details" class="bw-checkout-customer-details">
                        <?php do_action( 'woocommerce_checkout_billing' ); ?>
                        <?php do_action( 'woocommerce_checkout_shipping' ); ?>
                    </div>

                    <?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
                <?php endif; ?>

                <div class="bw-checkout-payment">
                    <div class="bw-payment-express-heading" aria-label="<?php esc_attr_e( 'Express checkout options', 'woocommerce' ); ?>"><?php esc_html_e( 'Express checkout', 'woocommerce' ); ?></div>
                    <h3 class="bw-checkout-section-title"><?php esc_html_e( 'Payment', 'woocommerce' ); ?></h3>
                    <?php wc_get_template(
                        'checkout/payment.php',
                        array(
                            'checkout'           => $checkout,
                            'order_button_text'  => $order_button_text,
                        )
                    ); ?>
                </div>
            </div>

            <?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>

            <?php if ( $settings['show_order_heading'] === '1' ) : ?>
                <div class="bw-checkout-order-heading__wrap">
                    <h3 id="order_review_heading" class="bw-checkout-order-heading"><?php esc_html_e( 'Your order', 'woocommerce' ); ?></h3>
                </div>
            <?php endif; ?>

            <div class="bw-checkout-right" id="order_review">
                <?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

                <div id="order_review_inner" class="woocommerce-checkout-review-order">
                    <?php do_action( 'woocommerce_checkout_order_review' ); ?>
                </div>

                <?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
            </div>
        </div>
    </div>
</form>
