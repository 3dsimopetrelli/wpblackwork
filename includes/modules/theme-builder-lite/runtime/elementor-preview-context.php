<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_tbl_apply_elementor_single_product_preview_context')) {
    function bw_tbl_apply_elementor_single_product_preview_context()
    {
        if (is_admin() || wp_doing_ajax() || is_feed() || is_embed()) {
            return;
        }

        if (!defined('ELEMENTOR_VERSION') || !class_exists('\\Elementor\\Plugin')) {
            return;
        }

        if (!function_exists('bw_tbl_is_elementor_editor_request') || !bw_tbl_is_elementor_editor_request()) {
            return;
        }

        $template_id = isset($_GET['elementor-preview']) ? absint(wp_unslash($_GET['elementor-preview'])) : 0;
        if ($template_id <= 0) {
            return;
        }

        $template_post = get_post($template_id);
        if (!($template_post instanceof WP_Post) || 'bw_template' !== $template_post->post_type) {
            return;
        }

        $template_type = get_post_meta($template_id, 'bw_template_type', true);
        if (function_exists('bw_tbl_sanitize_template_type')) {
            $template_type = bw_tbl_sanitize_template_type($template_type);
        } else {
            $template_type = sanitize_key((string) $template_type);
        }

        if ('single_product' !== $template_type) {
            return;
        }

        if (!function_exists('bw_tbl_get_single_product_preview_product_id') || !function_exists('bw_tbl_is_valid_preview_product')) {
            return;
        }

        $product_id = bw_tbl_get_single_product_preview_product_id(true);
        if (!bw_tbl_is_valid_preview_product($product_id)) {
            if (function_exists('bw_tbl_runtime_debug_log')) {
                bw_tbl_runtime_debug_log('single_product preview context skipped: invalid preview product', ['template_id' => $template_id]);
            }
            return;
        }

        $product_post = get_post($product_id);
        if (!($product_post instanceof WP_Post)) {
            return;
        }

        /*
         * Editor safety contract:
         * - never override the queried preview document post (`bw_template`)
         * - inject only Woo product globals for widget context
         */
        if (function_exists('wc_setup_product_data')) {
            wc_setup_product_data($product_post);
        } elseif (function_exists('wc_get_product')) {
            $GLOBALS['product'] = wc_get_product($product_id);
        }

        if (function_exists('bw_tbl_runtime_debug_log')) {
            bw_tbl_runtime_debug_log('single_product preview context applied', ['template_id' => $template_id, 'product_id' => $product_id]);
        }
    }
}
add_action('wp', 'bw_tbl_apply_elementor_single_product_preview_context', 5);
