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
    'left_width'   => 62,
    'right_width'  => 38,
    'footer_text'  => 'Bendito Mockup. All rights reserved.',
];

$grid_inline_styles = sprintf(
    '--bw-checkout-left-col:%d%%; --bw-checkout-right-col:%d%%;',
    isset( $settings['left_width'] ) ? (int) $settings['left_width'] : 62,
    isset( $settings['right_width'] ) ? (int) $settings['right_width'] : 38
);

$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
$express_gateways   = [];
$standard_gateways  = [];

foreach ( $available_gateways as $gateway_id => $gateway ) {
    $token      = strtolower( $gateway_id . ' ' . $gateway->get_title() );
    $is_express = ( false !== strpos( $token, 'woopay' ) )
        || ( false !== strpos( $token, 'apple' ) )
        || ( false !== strpos( $token, 'gpay' ) )
        || ( false !== strpos( $token, 'google' ) )
        || ( false !== strpos( $token, 'vpay' ) );

    if ( $is_express ) {
        $express_gateways[ $gateway_id ] = $gateway;
    } else {
        $standard_gateways[ $gateway_id ] = $gateway;
    }
}

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
        <div class="bw-checkout-grid" style="<?php echo esc_attr( $grid_inline_styles ); ?>">
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
                    <?php if ( WC()->cart->needs_payment() && ! empty( $express_gateways ) ) : ?>
                        <div class="bw-checkout-express">
                            <div class="bw-checkout-express__title"><?php esc_html_e( 'Express checkout', 'woocommerce' ); ?></div>
                            <div class="bw-checkout-express__gateway-grid">
                                <ul class="wc_payment_methods payment_methods methods">
                                    <?php foreach ( $express_gateways as $gateway ) : ?>
                                        <?php wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) ); ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="bw-checkout-express__or"><span><?php esc_html_e( 'OR', 'woocommerce' ); ?></span></div>
                        </div>
                    <?php endif; ?>

                    <h3 class="bw-checkout-section-title"><?php esc_html_e( 'Payment', 'woocommerce' ); ?></h3>
                    <?php
                    $standard_gateway_filter = static function() use ( $standard_gateways ) {
                        return $standard_gateways;
                    };

                    $no_methods_filter = null;

                    if ( empty( $standard_gateways ) && ! empty( $express_gateways ) ) {
                        $no_methods_filter = static function() {
                            return '';
                        };

                        add_filter( 'woocommerce_no_available_payment_methods_message', $no_methods_filter, 9999 );
                        add_filter( 'woocommerce_no_available_payment_methods_message_with_link', $no_methods_filter, 9999 );
                    }

                    add_filter( 'woocommerce_available_payment_gateways', $standard_gateway_filter, 9999 );

                    wc_get_template(
                        'checkout/payment.php',
                        array(
                            'checkout'           => $checkout,
                            'order_button_text'  => $order_button_text,
                        )
                    );

                    remove_filter( 'woocommerce_available_payment_gateways', $standard_gateway_filter, 9999 );

                    if ( null !== $no_methods_filter ) {
                        remove_filter( 'woocommerce_no_available_payment_methods_message', $no_methods_filter, 9999 );
                        remove_filter( 'woocommerce_no_available_payment_methods_message_with_link', $no_methods_filter, 9999 );
                    }
                    ?>
                </div>

                <?php
                $shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
                ?>
                <div class="bw-checkout-return-to-shop">
                    <a href="<?php echo esc_url( $shop_url ); ?>" class="bw-return-to-shop-btn">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M15 8H1M1 8L8 1M1 8L8 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <?php esc_html_e( 'Return to shop', 'woocommerce' ); ?>
                    </a>
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

        <div class="bw-checkout-footer">
            <div class="bw-checkout-footer__content">
                <?php
                $footer_text = ! empty( $settings['footer_text'] ) ? $settings['footer_text'] : 'Bendito Mockup. All rights reserved.';
                $current_year = gmdate( 'Y' );
                ?>
                <p>Copyright &copy; <?php echo esc_html( $current_year ); ?>, <?php echo esc_html( $footer_text ); ?></p>
            </div>
        </div>
    </div>
</form>
