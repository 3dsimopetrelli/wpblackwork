<?php
/**
 * Cart Page
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.9.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_cart'); ?>

<div class="bw-cart-container">
    <div class="bw-cart-grid">
        <form class="woocommerce-cart-form bw-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>"
            method="post">
            <?php do_action('woocommerce_before_cart_table'); ?>

            <div class="bw-cart-items-column">
                <div class="bw-cart-notices">
                    <?php wc_print_notices(); ?>
                </div>

                <div class="bw-cart-items-list">
                    <?php do_action('woocommerce_before_cart_contents'); ?>

                    <?php
                    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                        $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                        $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
                        $product_name = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);

                        if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
                            $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                            ?>
                            <div
                                class="bw-cart-item <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>">

                                <div class="bw-cart-item__image">
                                    <?php
                                    $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);
                                    if (!$product_permalink) {
                                        echo $thumbnail; // PHPCS: XSS ok.
                                    } else {
                                        printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail); // PHPCS: XSS ok.
                                    }
                                    ?>
                                </div>

                                <div class="bw-cart-item__content">
                                    <div class="bw-cart-item__name" data-title="<?php esc_attr_e('Product', 'woocommerce'); ?>">
                                        <?php
                                        if (!$product_permalink) {
                                            echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key) . '&nbsp;');
                                        } else {
                                            echo wp_kses_post(apply_filters('woocommerce_cart_item_name', sprintf('<a href="%s">%s</a>', esc_url($product_permalink), $_product->get_name()), $cart_item, $cart_item_key));
                                        }

                                        do_action('woocommerce_after_cart_item_name', $cart_item, $cart_item_key);

                                        echo wc_get_formatted_cart_item_data($cart_item); // PHPCS: XSS ok.
                                
                                        if ($_product->backorders_require_notification() && $_product->is_on_backorder($cart_item['quantity'])) {
                                            echo wp_kses_post(apply_filters('woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__('Available on backorder', 'woocommerce') . '</p>', $product_id));
                                        }
                                        ?>
                                    </div>

                                    <div class="bw-cart-item__meta">
                                        <div class="bw-cart-item__quantity"
                                            data-title="<?php esc_attr_e('Quantity', 'woocommerce'); ?>">
                                            <?php if ($_product->is_sold_individually()): ?>
                                                <div class="bw-cart-qty-badge">
                                                    <?php esc_html_e('Sold individually', 'woocommerce'); ?>
                                                </div>
                                                <?php printf('<input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key); ?>
                                            <?php else: ?>
                                                <div class="bw-qty-pill">
                                                    <button type="button" class="minus" aria-label="Decrease quantity">-</button>
                                                    <?php
                                                    $product_quantity = woocommerce_quantity_input(
                                                        array(
                                                            'input_name' => "cart[{$cart_item_key}][qty]",
                                                            'input_value' => $cart_item['quantity'],
                                                            'max_value' => $_product->get_max_purchase_quantity(),
                                                            'min_value' => '0',
                                                            'product_name' => $_product->get_name(),
                                                        ),
                                                        $_product,
                                                        false
                                                    );
                                                    echo apply_filters('woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item);
                                                    ?>
                                                    <button type="button" class="plus" aria-label="Increase quantity">+</button>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="bw-cart-item__remove">
                                            <?php
                                            echo apply_filters(
                                                'woocommerce_cart_item_remove_link',
                                                sprintf(
                                                    '<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">Remove</a>',
                                                    esc_url(wc_get_cart_remove_url($cart_item_key)),
                                                    esc_attr(sprintf(__('Remove %s from cart', 'woocommerce'), $product_name)),
                                                    esc_attr($product_id),
                                                    esc_attr($_product->get_sku())
                                                ),
                                                $cart_item_key
                                            );
                                            ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="bw-cart-item__price" data-title="<?php esc_attr_e('Price', 'woocommerce'); ?>">
                                    <?php echo apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key); ?>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    ?>

                    <?php do_action('woocommerce_cart_contents'); ?>

                    <div class="actions" style="display: none;">
                        <button type="submit" class="button" name="update_cart"
                            value="<?php esc_attr_e('Update cart', 'woocommerce'); ?>"><?php esc_html_e('Update cart', 'woocommerce'); ?></button>
                        <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
                    </div>

                    <?php do_action('woocommerce_after_cart_contents'); ?>
                </div>
            </div>

            <?php do_action('woocommerce_after_cart_table'); ?>
        </form>

        <div class="bw-cart-totals-column">
            <div class="cart-collaterals">
                <?php
                /**
                 * Cart collaterals hook.
                 *
                 * @hooked woocommerce_cart_totals - 10
                 */
                do_action('woocommerce_cart_collaterals');
                ?>
            </div>
        </div>
    </div>

</div>
</div>

<?php do_action('woocommerce_after_cart'); ?>