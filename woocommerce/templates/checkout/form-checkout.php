<?php
/**
 * Checkout Form
 *
 * @package WooCommerce
 */

defined('ABSPATH') || exit;

$settings = function_exists('bw_mew_get_checkout_settings') ? bw_mew_get_checkout_settings() : [
    'logo' => '',
    'logo_align' => 'left',
    'page_bg' => '#ffffff',
    'grid_bg' => '#ffffff',
    'left_bg' => '#ffffff',
    'right_bg' => '#f7f7f7',
    'border_color' => '#e0e0e0',
    'legal_text' => '',
    'footer_copyright' => '',
    'show_return_to_shop' => '1',
    'left_width' => 62,
    'right_width' => 38,
];

$right_padding_top = isset($settings['right_padding_top']) ? absint($settings['right_padding_top']) : 0;
$right_padding_right = isset($settings['right_padding_right']) ? absint($settings['right_padding_right']) : 0;
$right_padding_bottom = isset($settings['right_padding_bottom']) ? absint($settings['right_padding_bottom']) : 0;
$right_padding_left = isset($settings['right_padding_left']) ? absint($settings['right_padding_left']) : 28;
$right_sticky_top = isset($settings['right_sticky_top']) ? absint($settings['right_sticky_top']) : 20;
$right_margin_top = isset($settings['right_margin_top']) ? absint($settings['right_margin_top']) : 0;
$page_bg = isset($settings['page_bg']) ? esc_attr($settings['page_bg']) : '#ffffff';
$grid_bg = isset($settings['grid_bg']) ? esc_attr($settings['grid_bg']) : '#ffffff';

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
    isset($settings['left_width']) ? (int) $settings['left_width'] : 62,
    isset($settings['right_width']) ? (int) $settings['right_width'] : 38,
    $page_bg,
    $grid_bg,
    isset($settings['left_bg']) ? esc_attr($settings['left_bg']) : '#ffffff',
    isset($settings['right_bg']) ? esc_attr($settings['right_bg']) : 'transparent',
    isset($settings['border_color']) ? esc_attr($settings['border_color']) : '#262626',
    $right_sticky_top,
    $right_spacing_vars
);

$right_column_inline_styles = sprintf(
    '%s background:%s; padding:%dpx %dpx %dpx %dpx; margin-top:%dpx;',
    $right_spacing_vars,
    isset($settings['right_bg']) ? esc_attr($settings['right_bg']) : 'transparent',
    $right_padding_top,
    $right_padding_right,
    $right_padding_bottom,
    $right_padding_left,
    $right_margin_top
);

$page_background_styles = sprintf(
    'body.woocommerce-checkout{--bw-checkout-page-bg:%1$s; background:%1$s;} body.woocommerce-checkout .bw-checkout-grid{--bw-checkout-grid-bg:%2$s;}',
    $page_bg,
    $grid_bg
);

$checkout = WC()->checkout();
$order_button_text = apply_filters('woocommerce_order_button_text', __('Place order', 'woocommerce'));

do_action('woocommerce_before_checkout_form', $checkout);

// If checkout registration is disabled and not logged in, the user cannot checkout.
if (!$checkout->is_registration_enabled() && $checkout->is_registration_required() && !is_user_logged_in()) {
    echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in to checkout.', 'woocommerce')));
    return;
}
?>

<style id="bw-checkout-page-background">
    <?php echo esc_html($page_background_styles); ?>
</style>

<?php
// Render minimal checkout header
if (function_exists('bw_mew_render_checkout_header')) {
    bw_mew_render_checkout_header();
}
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout bw-checkout-form"
    action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">
    <div class="bw-checkout-wrapper" style="<?php echo esc_attr($grid_inline_styles); ?>">
        <div class="bw-checkout-grid" style="<?php echo esc_attr($grid_inline_styles); ?>">
            <button type="button" class="bw-order-summary-toggle" aria-expanded="false" aria-controls="order_review">
                <span class="bw-order-summary-label"><?php esc_html_e('Order summary', 'woocommerce'); ?></span>
                <span class="bw-order-summary-total" aria-live="polite">—</span>
                <span class="bw-order-summary-caret" aria-hidden="true"></span>
            </button>
            <div class="bw-checkout-left">
                <?php
                $skeleton_enabled = false; // get_option('bw_loading_checkout_skeleton_enabled', '1') === '1';
                $skeleton_col1 = get_option('bw_loading_skeleton_col1', '');
                $skeleton_col2 = get_option('bw_loading_skeleton_col2', '');

                $loader_inline_style = '';
                if ($skeleton_col1)
                    $loader_inline_style .= '--bw-skeleton-col1: url(' . esc_url($skeleton_col1) . '); ';
                if ($skeleton_col2)
                    $loader_inline_style .= '--bw-skeleton-col2: url(' . esc_url($skeleton_col2) . '); ';
                ?>

                <?php if ($skeleton_enabled): ?>
                    <div class="bw-checkout-left__loader" style="<?php echo esc_attr($loader_inline_style); ?>"></div>
                <?php endif; ?>

                <?php if ($checkout->get_checkout_fields()): ?>
                    <?php do_action('woocommerce_checkout_before_customer_details'); ?>

                    <div id="customer_details" class="bw-checkout-customer-details">
                        <!-- Standard Stripe Payment Request Wrapper -->
                        <div id="wc-stripe-payment-request-wrapper" style="clear:both;padding-top:1.5em;"></div>
                        <div id="wc-stripe-payment-request-button-separator"
                            style="clear:both;padding-top:1.5em;display:none;"></div>

                        <?php
                        // Render OR divider manually to ensure correct order
                        if (function_exists('bw_mew_render_express_divider')) {
                            bw_mew_render_express_divider();
                        }
                        ?>

                        <?php do_action('woocommerce_checkout_billing'); ?>
                        <?php do_action('woocommerce_checkout_shipping'); ?>
                    </div>

                    <?php do_action('woocommerce_checkout_after_customer_details'); ?>
                <?php endif; ?>

                <div class="bw-checkout-payment">
                    <?php
                    wc_get_template(
                        'checkout/payment.php',
                        array(
                            'checkout' => $checkout,
                            'order_button_text' => $order_button_text,
                        )
                    );
                    ?>

                    <?php if (!empty($settings['legal_text'])): ?>
                        <div class="bw-checkout-legal">
                            <?php echo wp_kses_post($settings['legal_text']); ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    $show_return_link = !empty($settings['show_return_to_shop']) && '0' !== (string) $settings['show_return_to_shop'];
                    $footer_copy = isset($settings['footer_copyright']) ? $settings['footer_copyright'] : '';
                    $shop_url = wc_get_page_permalink('shop');
                    ?>

                    <?php if (($show_return_link && $shop_url) || !empty($footer_copy)): ?>
                        <div class="bw-checkout-left-footer">
                            <?php if ($show_return_link && $shop_url): ?>
                                <a class="bw-checkout-return-to-shop" href="<?php echo esc_url($shop_url); ?>">
                                    <?php esc_html_e('Return to shop', 'woocommerce'); ?>
                                </a>
                            <?php endif; ?>

                            <?php
                            $show_copyright = !isset($settings['show_footer_copyright']) || '1' === (string) $settings['show_footer_copyright'];
                            if ($show_copyright && !empty($footer_copy)):
                                ?>
                                <div class="bw-checkout-copyright">
                                    <?php
                                    printf(
                                        'Copyright © %1$s, %2$s',
                                        esc_html(date('Y')),
                                        wp_kses_post($footer_copy)
                                    );
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php
                // Policy Footer and Modals (Left Column Placement)
                $policy_keys = ['refund', 'shipping', 'privacy', 'terms', 'contact'];
                $footer_policies = [];

                foreach ($policy_keys as $key) {
                    $data = get_option("bw_checkout_policy_{$key}", []);
                    if (!empty($data['title'])) {
                        $footer_policies[$key] = $data;
                    }
                }
                ?>

                <?php if (!empty($footer_policies)): ?>
                    <div class="bw-checkout-footer-links">
                        <?php foreach ($footer_policies as $key => $data): ?>
                            <a href="#" class="bw-policy-link" data-policy="<?php echo esc_attr($key); ?>"
                                data-title="<?php echo esc_attr($data['title']); ?>"
                                data-subtitle="<?php echo esc_attr($data['subtitle']); ?>"
                                data-content="<?php echo esc_attr($data['content']); ?>">
                                <?php echo esc_html($data['title']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <script>
                                        window.bwPolicyContent = <?php echo json_encode($footer_policies); ?>;
                    </script>
                <?php endif; ?>
            </div>

            <?php do_action('woocommerce_checkout_before_order_review_heading'); ?>

            <?php if (isset($settings['show_order_heading']) && $settings['show_order_heading'] === '1'): ?>
                <div class="bw-checkout-order-heading__wrap"
                    style="<?php echo esc_attr($right_spacing_vars . ' margin-top:' . $right_sticky_top . 'px;'); ?>">
                    <h3 id="order_review_heading" class="bw-checkout-order-heading">
                        <?php esc_html_e('Your order', 'woocommerce'); ?>
                    </h3>
                </div>
            <?php endif; ?>

            <div class="bw-checkout-right" id="order_review"
                style="<?php echo esc_attr($right_column_inline_styles); ?>">
                <?php if ($skeleton_enabled): ?>
                    <div class="bw-checkout-right__loader" style="<?php echo esc_attr($loader_inline_style); ?>"></div>
                <?php endif; ?>

                <?php do_action('woocommerce_checkout_before_order_review'); ?>

                <div id="order_review_inner" class="woocommerce-checkout-review-order">
                    <?php do_action('woocommerce_checkout_order_review'); ?>
                </div>

                <?php do_action('woocommerce_checkout_after_order_review'); ?>
            </div>
        </div>

        <?php if (!empty($footer_policies)): ?>
            <div id="bw-policy-modal" class="bw-policy-modal" style="display:none;">
                <div class="bw-policy-modal__overlay"></div>
                <div class="bw-policy-modal__container">
                    <button type="button" class="bw-policy-modal__close" aria-label="Close">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12"></path>
                        </svg>
                    </button>
                    <div class="bw-policy-modal__content">
                        <h2 class="bw-policy-modal__title"></h2>
                        <h3 class="bw-policy-modal__subtitle"></h3>
                        <div class="bw-policy-modal__body"></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</form>