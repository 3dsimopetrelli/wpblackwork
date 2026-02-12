<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_header_cart_count_fragment')) {
    /**
     * Keeps cart badge synced via WooCommerce fragments for custom header.
     *
     * @param array<string,string> $fragments
     * @return array<string,string>
     */
    function bw_header_cart_count_fragment($fragments)
    {
        if (!function_exists('WC')) {
            return $fragments;
        }

        $wc = WC();
        if (!$wc || !isset($wc->cart) || !$wc->cart) {
            return $fragments;
        }

        $count = max(0, (int) $wc->cart->get_cart_contents_count());
        $class = $count > 0 ? 'bw-navshop__cart-count' : 'bw-navshop__cart-count is-empty';
        $markup = '<span class="' . esc_attr($class) . '">' . esc_html($count) . '</span>';

        $fragments['.bw-navshop__cart .bw-navshop__cart-count'] = $markup;

        return $fragments;
    }
}
add_filter('woocommerce_add_to_cart_fragments', 'bw_header_cart_count_fragment');
