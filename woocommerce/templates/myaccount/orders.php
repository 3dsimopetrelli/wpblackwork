<?php
/**
 * My Orders
 *
 * Custom empty-state styling for Blackwork while preserving WooCommerce
 * orders table/actions behavior.
 *
 * @package WooCommerce\Templates
 * @version 9.5.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_orders', $has_orders );
?>

<?php if ( $has_orders ) : ?>
    <section class="bw-account-orders-card bw-account-orders-card--modern">
        <header class="bw-page-header bw-page-header--boxed">
            <h2 class="bw-section-title"><?php esc_html_e( 'My purchases', 'bw' ); ?></h2>
        </header>
        <div class="bw-purchases-table-wrap">
            <ul class="bw-purchases-table" role="list">
                <li class="bw-purchases-head" aria-hidden="true">
                    <span class="bw-purchases-col bw-purchases-col--product"><?php esc_html_e( 'Product', 'bw' ); ?></span>
                    <span class="bw-purchases-col"><?php esc_html_e( 'Order', 'bw' ); ?></span>
                    <span class="bw-purchases-col"><?php esc_html_e( 'Date', 'bw' ); ?></span>
                    <span class="bw-purchases-col"><?php esc_html_e( 'Price', 'bw' ); ?></span>
                    <span class="bw-purchases-col"><?php esc_html_e( 'Coupon', 'bw' ); ?></span>
                    <span class="bw-purchases-col"><?php esc_html_e( 'Bill', 'bw' ); ?></span>
                </li>
                <?php
                foreach ( $customer_orders->orders as $customer_order ) {
                    $order = wc_get_order( $customer_order );
                    if ( ! $order instanceof WC_Order ) {
                        continue;
                    }

                    $items      = $order->get_items( 'line_item' );
                    $first_item = reset( $items );

                    $product_id    = ( $first_item instanceof WC_Order_Item_Product ) ? (int) $first_item->get_product_id() : 0;
                    $product_title = ( $first_item instanceof WC_Order_Item_Product ) ? (string) $first_item->get_name() : __( 'Product', 'bw' );
                    $product_url   = $product_id > 0 ? get_permalink( $product_id ) : '';
                    $thumbnail_url = $product_id > 0 ? get_the_post_thumbnail_url( $product_id, 'thumbnail' ) : '';

                    $order_url    = $order->get_view_order_url();
                    $order_number = _x( '#', 'hash before order number', 'woocommerce' ) . $order->get_order_number();
                    $order_date   = $order->get_date_created() ? wc_format_datetime( $order->get_date_created() ) : '—';
                    $order_total  = $order->get_formatted_order_total();

                    $coupon_codes = $order->get_coupon_codes();
                    $coupon_parts = [];
                    foreach ( $coupon_codes as $coupon_code ) {
                        $coupon = new WC_Coupon( $coupon_code );
                        if ( $coupon && $coupon->get_id() && 'percent' === $coupon->get_discount_type() ) {
                            $coupon_parts[] = sprintf(
                                '%1$s (%2$s%%)',
                                $coupon_code,
                                wc_format_localized_decimal( $coupon->get_amount() )
                            );
                        } else {
                            $coupon_parts[] = $coupon_code;
                        }
                    }
                    $coupon_label = ! empty( $coupon_parts ) ? implode( ', ', $coupon_parts ) : '—';
                    ?>
                    <li class="bw-purchases-row">
                        <div class="bw-purchases-col bw-purchases-col--product" data-title="<?php esc_attr_e( 'Product', 'bw' ); ?>">
                            <div class="bw-purchases-product">
                                <div class="bw-purchases-thumb">
                                    <?php if ( $product_url ) : ?>
                                        <a href="<?php echo esc_url( $product_url ); ?>" target="_blank" rel="noopener noreferrer" class="bw-order-product-link" aria-label="<?php echo esc_attr( $product_title ); ?>">
                                    <?php endif; ?>
                                    <?php if ( $thumbnail_url ) : ?>
                                        <img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $product_title ); ?>" loading="lazy" />
                                    <?php else : ?>
                                        <span class="bw-order-thumb-placeholder" aria-hidden="true"></span>
                                    <?php endif; ?>
                                    <?php if ( $product_url ) : ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <p class="bw-order-title">
                                    <?php if ( $product_url ) : ?>
                                        <a href="<?php echo esc_url( $product_url ); ?>" target="_blank" rel="noopener noreferrer" class="bw-order-product-link"><?php echo esc_html( $product_title ); ?></a>
                                    <?php else : ?>
                                        <?php echo esc_html( $product_title ); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div class="bw-purchases-col" data-title="<?php esc_attr_e( 'Order', 'bw' ); ?>">
                            <a href="<?php echo esc_url( $order_url ); ?>" class="bw-purchases-order-link"><?php echo esc_html( $order_number ); ?></a>
                        </div>
                        <div class="bw-purchases-col" data-title="<?php esc_attr_e( 'Date', 'bw' ); ?>">
                            <?php echo esc_html( $order_date ); ?>
                        </div>
                        <div class="bw-purchases-col" data-title="<?php esc_attr_e( 'Price', 'bw' ); ?>">
                            <?php echo wp_kses_post( $order_total ); ?>
                        </div>
                        <div class="bw-purchases-col" data-title="<?php esc_attr_e( 'Coupon', 'bw' ); ?>">
                            <?php echo esc_html( $coupon_label ); ?>
                        </div>
                        <div class="bw-purchases-col" data-title="<?php esc_attr_e( 'Bill', 'bw' ); ?>">
                            <a href="<?php echo esc_url( $order_url ); ?>" class="bw-order-btn bw-order-btn--details"><?php esc_html_e( 'View', 'bw' ); ?></a>
                        </div>
                    </li>
                    <?php
                }
                ?>
            </ul>
        </div>

        <?php if ( 1 < (int) $customer_orders->max_num_pages ) : ?>
            <div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-MyAccount-pagination">
                <?php if ( 1 !== (int) $current_page ) : ?>
                    <a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', max( 1, $current_page - 1 ) ) ); ?>">
                        <?php esc_html_e( 'Previous', 'woocommerce' ); ?>
                    </a>
                <?php endif; ?>

                <?php if ( (int) $customer_orders->max_num_pages !== (int) $current_page ) : ?>
                    <a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ); ?>">
                        <?php esc_html_e( 'Next', 'woocommerce' ); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

<?php else : ?>

    <?php
    $shop_url = wc_get_page_permalink( 'shop' );
    if ( ! $shop_url ) {
        $shop_url = home_url( '/shop/' );
    }
    ?>
    <section class="bw-account-empty-orders" aria-live="polite">
        <header class="bw-page-header bw-page-header--boxed">
            <h2 class="bw-section-title"><?php esc_html_e( 'My purchases', 'bw' ); ?></h2>
        </header>
        <p class="bw-account-empty-orders__text"><?php esc_html_e( 'No order has been made yet.', 'woocommerce' ); ?></p>
        <a class="bw-account-empty-orders__cta elementor-button elementor-button-link elementor-size-md" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', $shop_url ) ); ?>">
            <?php esc_html_e( 'Browse products', 'woocommerce' ); ?>
        </a>
    </section>

<?php endif; ?>

<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>
