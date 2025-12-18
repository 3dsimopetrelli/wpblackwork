<?php
/**
 * Review order table
 *
 * @package WooCommerce/Templates
 * @version 8.3.0
 */

defined( 'ABSPATH' ) || exit;

$cart_items = WC()->cart->get_cart();
?>

<div class="bw-order-summary woocommerce-checkout-review-order">
    <div class="bw-order-summary__loader" aria-hidden="true"></div>

    <table class="shop_table woocommerce-checkout-review-order-table bw-review-table">
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
                    <td class="product-thumbnail">
                        <div class="bw-review-item__media">
                            <?php echo $product_permalink ? '<a href="' . esc_url( $product_permalink ) . '">' . apply_filters( 'woocommerce_cart_item_thumbnail', $product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key ) . '</a>' : apply_filters( 'woocommerce_cart_item_thumbnail', $product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    </td>

                    <td class="product-name">
                        <div class="bw-review-item__content">
                            <div class="bw-review-item__header">
                                <div class="bw-review-item__title">
                                    <?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $product_permalink ? sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $product->get_name() ) : $product->get_name(), $cart_item, $cart_item_key ) ); ?>
                                </div>
                                <div class="bw-review-item__price"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $product, $cart_item['quantity'] ), $cart_item, $cart_item_key ) ); ?></div>
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
                            </div>

                            <div class="bw-review-item__controls">
                                <div class="bw-qty-control">
                                    <?php
                                    if ( $product->is_sold_individually() ) {
                                        echo '<span class="bw-qty-static">' . sprintf( /* translators: %s: quantity */ esc_html__( 'Qty: %s', 'woocommerce' ), esc_html( $cart_item['quantity'] ) ) . '</span>';
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
                                        if ( $product_permalink ) {
                                            echo '<a href="' . esc_url( $product_permalink ) . '" class="bw-qty-edit">' . esc_html__( 'Edit', 'woocommerce' ) . '</a>';
                                        }
                                    }
                                    ?>
                                </div>
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
                    </div>
                </td>
            </tr>

            <tr class="bw-total-row bw-total-row--subtotal">
                <th scope="row"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
                <td><?php wc_cart_totals_subtotal_html(); ?></td>
            </tr>

            <?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
                <tr class="bw-total-row bw-total-row--coupon coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
                    <th scope="row"><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
                    <td><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
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
