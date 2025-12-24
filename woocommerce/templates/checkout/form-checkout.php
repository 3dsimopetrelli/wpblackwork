<?php
/**
 * Checkout Form
 *
 * @package WooCommerce
 */

defined( 'ABSPATH' ) || exit;

$settings = function_exists( 'bw_mew_get_checkout_settings' ) ? bw_mew_get_checkout_settings() : [
    'logo'         => '',
    'logo_align'   => 'left',
    'page_bg'      => '#ffffff',
    'grid_bg'      => '#ffffff',
    'left_bg'      => '#ffffff',
    'right_bg'     => '#f7f7f7',
    'border_color' => '#e0e0e0',
    'legal_text'   => '',
    'footer_copyright' => '',
    'show_return_to_shop' => '1',
    'left_width'   => 62,
    'right_width'  => 38,
];

$right_padding_top    = isset( $settings['right_padding_top'] ) ? absint( $settings['right_padding_top'] ) : 0;
$right_padding_right  = isset( $settings['right_padding_right'] ) ? absint( $settings['right_padding_right'] ) : 0;
$right_padding_bottom = isset( $settings['right_padding_bottom'] ) ? absint( $settings['right_padding_bottom'] ) : 0;
$right_padding_left   = isset( $settings['right_padding_left'] ) ? absint( $settings['right_padding_left'] ) : 28;
$right_sticky_top     = isset( $settings['right_sticky_top'] ) ? absint( $settings['right_sticky_top'] ) : 20;
$right_margin_top     = isset( $settings['right_margin_top'] ) ? absint( $settings['right_margin_top'] ) : 0;
$page_bg              = isset( $settings['page_bg'] ) ? esc_attr( $settings['page_bg'] ) : '#ffffff';
$grid_bg              = isset( $settings['grid_bg'] ) ? esc_attr( $settings['grid_bg'] ) : '#ffffff';

$right_spacing_vars = sprintf(
    '--bw-checkout-right-pad-top:%1$dpx; --bw-checkout-right-pad-right:%2$dpx; --bw-checkout-right-pad-bottom:%3$dpx; --bw-checkout-right-pad-left:%4$dpx; --bw-checkout-right-sticky-top:%5$dpx; --bw-checkout-right-margin-top:%6$dpx;',
    $right_padding_top,
    $right_padding_right,
    $right_padding_bottom,
    $right_padding_left,
    $right_sticky_top,
    $right_margin_top
);

$grid_inline_styles = sprintf(
    '--bw-checkout-left-col:%d%%; --bw-checkout-right-col:%d%%; --bw-checkout-page-bg:%s; --bw-checkout-grid-bg:%s; --bw-checkout-left-bg:%s; --bw-checkout-right-bg:%s; --bw-checkout-border-color:%s; --bw-checkout-right-sticky-top:%dpx; %s',
    isset( $settings['left_width'] ) ? (int) $settings['left_width'] : 62,
    isset( $settings['right_width'] ) ? (int) $settings['right_width'] : 38,
    $page_bg,
    $grid_bg,
    isset( $settings['left_bg'] ) ? esc_attr( $settings['left_bg'] ) : '#ffffff',
    isset( $settings['right_bg'] ) ? esc_attr( $settings['right_bg'] ) : 'transparent',
    isset( $settings['border_color'] ) ? esc_attr( $settings['border_color'] ) : '#262626',
    $right_sticky_top,
    $right_spacing_vars
);

$right_column_inline_styles = sprintf(
    '%s background:%s; padding:%dpx %dpx %dpx %dpx; top:%dpx; margin-top:%dpx;',
    $right_spacing_vars,
    isset( $settings['right_bg'] ) ? esc_attr( $settings['right_bg'] ) : 'transparent',
    $right_padding_top,
    $right_padding_right,
    $right_padding_bottom,
    $right_padding_left,
    $right_sticky_top,
    $right_sticky_top
);

$page_background_styles = sprintf(
    'body.woocommerce-checkout{--bw-checkout-page-bg:%1$s; background:%1$s;} body.woocommerce-checkout .bw-checkout-grid{--bw-checkout-grid-bg:%2$s;}',
    $page_bg,
    $grid_bg
);

$checkout = WC()->checkout();
$order_button_text = apply_filters( 'woocommerce_order_button_text', __( 'Place order', 'woocommerce' ) );

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
    echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
    return;
}
?>

<style id="bw-checkout-page-background">
    <?php echo esc_html( $page_background_styles ); ?>
</style>

<form name="checkout" method="post" class="checkout woocommerce-checkout bw-checkout-form" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
    <div class="bw-checkout-wrapper" style="<?php echo esc_attr( $grid_inline_styles ); ?>">
        <div class="bw-checkout-grid" style="<?php echo esc_attr( $grid_inline_styles ); ?>">
            <div class="bw-checkout-left">
                <?php if ( ! empty( $settings['logo'] ) ) : ?>
                    <div class="bw-checkout-logo bw-checkout-logo--<?php echo esc_attr( isset( $settings['logo_align'] ) ? $settings['logo_align'] : 'left' ); ?>" style="padding: <?php echo esc_attr( $settings['logo_padding_top'] ); ?>px <?php echo esc_attr( $settings['logo_padding_right'] ); ?>px <?php echo esc_attr( $settings['logo_padding_bottom'] ); ?>px <?php echo esc_attr( $settings['logo_padding_left'] ); ?>px;">
                        <img src="<?php echo esc_url( $settings['logo'] ); ?>" alt="<?php esc_attr_e( 'Checkout logo', 'bw' ); ?>" style="max-width: <?php echo esc_attr( $settings['logo_width'] ); ?>px;" />
                    </div>
                <?php endif; ?>

                <?php if ( $checkout->get_checkout_fields() ) : ?>
                    <?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

                    <div id="customer_details" class="bw-checkout-customer-details">
                        <?php do_action( 'woocommerce_checkout_billing' ); ?>
                        <?php do_action( 'woocommerce_checkout_shipping' ); ?>
                    </div>

                    <?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
                <?php endif; ?>

                <div class="bw-checkout-payment">
                    <h3 class="bw-checkout-section-title"><?php esc_html_e( 'Payment', 'woocommerce' ); ?></h3>
                    <?php
                    wc_get_template(
                        'checkout/payment.php',
                        array(
                            'checkout'          => $checkout,
                            'order_button_text' => $order_button_text,
                        )
                    );
                    ?>

                    <?php if ( ! empty( $settings['legal_text'] ) ) : ?>
                        <div class="bw-checkout-legal">
                            <?php echo wp_kses_post( $settings['legal_text'] ); ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    $show_return_link = ! empty( $settings['show_return_to_shop'] ) && '0' !== (string) $settings['show_return_to_shop'];
                    $footer_copy      = isset( $settings['footer_copyright'] ) ? $settings['footer_copyright'] : '';
                    $shop_url         = wc_get_page_permalink( 'shop' );
                    ?>

                    <?php if ( ( $show_return_link && $shop_url ) || ! empty( $footer_copy ) ) : ?>
                        <div class="bw-checkout-left-footer">
                            <?php if ( $show_return_link && $shop_url ) : ?>
                                <a class="bw-checkout-return-to-shop" href="<?php echo esc_url( $shop_url ); ?>">
                                    <?php esc_html_e( 'Return to shop', 'woocommerce' ); ?>
                                </a>
                            <?php endif; ?>

                            <?php if ( ! empty( $footer_copy ) ) : ?>
                                <div class="bw-checkout-copyright">
                                    <?php
                                    printf(
                                        'Copyright © %1$s, %2$s',
                                        esc_html( date( 'Y' ) ),
                                        wp_kses_post( $footer_copy )
                                    );
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>

            <?php if ( $settings['show_order_heading'] === '1' ) : ?>
                <div class="bw-checkout-order-heading__wrap" style="<?php echo esc_attr( $right_spacing_vars . ' margin-top:' . $right_sticky_top . 'px;' ); ?>">
                    <h3 id="order_review_heading" class="bw-checkout-order-heading"><?php esc_html_e( 'Your order', 'woocommerce' ); ?></h3>
                </div>
            <?php endif; ?>

            <div class="bw-checkout-right" id="order_review" style="<?php echo esc_attr( $right_column_inline_styles ); ?>">
                <?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

                <div id="order_review_inner" class="woocommerce-checkout-review-order">
                    <?php do_action( 'woocommerce_checkout_order_review' ); ?>
                </div>

                <?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
            </div>
        </div>
    </div>
</form>
