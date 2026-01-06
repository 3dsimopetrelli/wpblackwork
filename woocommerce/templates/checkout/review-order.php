<?php
/**
 * Review order table
 *
 * @package WooCommerce/Templates
 * @version 8.3.0
 */

defined( 'ABSPATH' ) || exit;

$cart_items = WC()->cart->get_cart();
$checkout_settings = function_exists( 'bw_mew_get_checkout_settings' ) ? bw_mew_get_checkout_settings() : [];
$thumb_ratio       = isset( $checkout_settings['thumb_ratio'] ) ? $checkout_settings['thumb_ratio'] : 'square';
$thumb_width       = isset( $checkout_settings['thumb_width'] ) ? absint( $checkout_settings['thumb_width'] ) : 110;
$thumb_map         = [
    'square'    => '1 / 1',
    'portrait'  => '2 / 3',
    'landscape' => '3 / 2',
];
$thumb_ratio       = array_key_exists( $thumb_ratio, $thumb_map ) ? $thumb_ratio : 'square';
$thumb_aspect      = $thumb_map[ $thumb_ratio ];
?>

<div class="bw-order-summary woocommerce-checkout-review-order bw-thumb-ratio-<?php echo esc_attr( $thumb_ratio ); ?>">
    <div class="bw-order-summary__loader" aria-hidden="true"></div>

    <table class="shop_table woocommerce-checkout-review-order-table bw-review-table" style="--bw-thumb-aspect: <?php echo esc_attr( $thumb_aspect ); ?>; --bw-thumb-width: <?php echo esc_attr( $thumb_width ); ?>px;">
        <tbody>
            <?php
            do_action( 'woocommerce_review_order_before_cart_contents' );

            foreach ( $cart_items as $cart_item_key => $cart_item ) {
                $product = $cart_item['data'];

                if ( ! $product || ! $product->exists() || 0 >= $cart_item['quantity'] || ! apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
                    continue;
                }

                $product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $product->is_visible() ? $product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
                ?>
                <tr class="cart_item bw-review-item" data-cart-item="<?php echo esc_attr( $cart_item_key ); ?>">
                    <td class="product-thumbnail" style="width: <?php echo esc_attr( $thumb_width ); ?>px; min-width: <?php echo esc_attr( $thumb_width ); ?>px; max-width: <?php echo esc_attr( $thumb_width ); ?>px; padding-right: 0; padding-bottom: 15px;">
                        <div class="bw-review-item__media" style="width: <?php echo esc_attr( $thumb_width ); ?>px; aspect-ratio: <?php echo esc_attr( $thumb_aspect ); ?>;">
                            <?php
                            $thumbnail_id = $product->get_image_id();
                            if ( $thumbnail_id ) {
                                $image_url = wp_get_attachment_image_url( $thumbnail_id, 'medium' );
                                $image_alt = get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );
                                if ( $product_permalink ) {
                                    echo '<a href="' . esc_url( $product_permalink ) . '"><img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $image_alt ?: $product->get_name() ) . '"></a>';
                                } else {
                                    echo '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $image_alt ?: $product->get_name() ) . '">';
                                }
                            } else {
                                echo wc_placeholder_img();
                            }
                            ?>
                        </div>
                    </td>

                    <td class="product-name" style="padding-left: 15px;">
                        <div class="bw-review-item__content">
                            <div class="bw-review-item__header">
                                <div class="bw-review-item__title">
                                    <?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $product_permalink ? sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $product->get_name() ) : $product->get_name(), $cart_item, $cart_item_key ) ); ?>
                                </div>
                                <?php
                                $remove_link = apply_filters(
                                    'woocommerce_cart_item_remove_link',
                                    sprintf(
                                        '<a href="%s" class="bw-review-item__remove remove" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s">&times;</a>',
                                        esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
                                        esc_attr__( 'Remove this item', 'woocommerce' ),
                                        esc_attr( $product->get_id() ),
                                        esc_attr( $cart_item_key ),
                                        esc_attr( $product->get_sku() )
                                    ),
                                    $cart_item_key
                                );
                                echo $remove_link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                ?>
                                <div class="bw-review-item__price"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $product, $cart_item['quantity'] ), $cart_item, $cart_item_key ) ); ?></div>
                            </div>

                            <div class="bw-review-item__controls">
                                <div class="bw-qty-control">
                                    <?php
                                    if ( $product->is_sold_individually() ) {
                                        echo '<span class="bw-qty-badge">' . esc_html__( 'Sold individually', 'woocommerce' ) . '</span>';
                                    } else {
                                        echo '<div class="bw-qty-shell">';
                                        echo '<button type="button" class="bw-qty-btn bw-qty-btn--minus" aria-label="' . esc_attr__( 'Reduce quantity', 'woocommerce' ) . '">-</button>';
                                        echo apply_filters(
                                            'woocommerce_cart_item_quantity',
                                            woocommerce_quantity_input(
                                                [
                                                    'input_name'   => "cart[{$cart_item_key}][qty]",
                                                    'input_value'  => $cart_item['quantity'],
                                                    'max_value'    => $product->get_max_purchase_quantity(),
                                                    'min_value'    => 0,
                                                    'product_name' => $product->get_name(),
                                                ],
                                                $product,
                                                false
                                            ),
                                            $cart_item_key,
                                            $cart_item
                                        );
                                        echo '<button type="button" class="bw-qty-btn bw-qty-btn--plus" aria-label="' . esc_attr__( 'Increase quantity', 'woocommerce' ) . '">+</button>';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                                <a href="<?php echo esc_url( wc_get_cart_remove_url( $cart_item_key ) ); ?>"
                                   class="bw-review-item__remove-text remove"
                                   aria-label="<?php esc_attr_e( 'Remove this item', 'woocommerce' ); ?>"
                                   data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
                                   data-cart_item_key="<?php echo esc_attr( $cart_item_key ); ?>"
                                   data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>">
                                    <?php esc_html_e( 'Remove', 'woocommerce' ); ?>
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php
            }

            do_action( 'woocommerce_review_order_after_cart_contents' );
            ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">
                    <div class="bw-review-coupon">
                        <?php woocommerce_checkout_coupon_form(); ?>
                        <div class="bw-coupon-message" id="bw-coupon-message"></div>
                    </div>
                </td>
            </tr>

            <tr class="bw-total-row bw-total-row--subtotal">
                <th scope="row"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
                <td><?php wc_cart_totals_subtotal_html(); ?></td>
            </tr>

            <?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
                <tr class="bw-total-row bw-total-row--coupon cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
                    <th scope="row">
                        <span class="bw-coupon-label"><?php esc_html_e( 'Coupon:', 'woocommerce' ); ?></span>
                        <span class="bw-coupon-chip">
                            <span class="bw-coupon-chip__icon"></span>
                            <?php echo esc_html( $code ); ?>
                        </span>
                    </th>
                    <td data-title="<?php echo esc_attr( wc_cart_totals_coupon_label( $coupon, false ) ); ?>">
                        <span class="bw-coupon-value">
                            <?php echo wc_price( -WC()->cart->get_coupon_discount_amount( $code ) ); ?>
                            <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'remove_coupon', rawurlencode( $code ) ), 'remove_coupon' ) ); ?>" class="woocommerce-remove-coupon" data-coupon="<?php echo esc_attr( $code ); ?>" aria-label="<?php esc_attr_e( 'Remove coupon', 'woocommerce' ); ?>"><?php esc_html_e( '[Remove]', 'woocommerce' ); ?></a>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
                <?php do_action( 'woocommerce_review_order_before_shipping' ); ?>
                <?php wc_cart_totals_shipping_html(); ?>
                <?php do_action( 'woocommerce_review_order_after_shipping' ); ?>
            <?php endif; ?>

            <?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
                <tr class="bw-total-row">
                    <th scope="row"><?php echo esc_html( $fee->name ); ?></th>
                    <td><?php wc_cart_totals_fee_html( $fee ); ?></td>
                </tr>
            <?php endforeach; ?>

            <?php
            if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) {
                if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) {
                    foreach ( WC()->cart->get_tax_totals() as $code => $tax ) {
                        ?>
                        <tr class="bw-total-row tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
                            <th scope="row"><?php echo esc_html( $tax->label ); ?></th>
                            <td><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr class="bw-total-row">
                        <th scope="row"><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></th>
                        <td><?php wc_cart_totals_taxes_total_html(); ?></td>
                    </tr>
                    <?php
                }
            }
            ?>

            <?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

            <tr class="bw-total-row bw-total-row--grand order-total">
                <th scope="row"><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
                <td><?php wc_cart_totals_order_total_html(); ?></td>
            </tr>

            <?php do_action( 'woocommerce_review_order_after_order_total' ); ?>
        </tfoot>
    </table>
</div>
