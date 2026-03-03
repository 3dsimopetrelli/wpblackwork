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

if (!function_exists('bw_tbl_get_elementor_preview_product_id')) {
    function bw_tbl_get_elementor_preview_product_id()
    {
        if (is_admin() || wp_doing_ajax() || is_feed() || is_embed()) {
            return 0;
        }

        if (!defined('ELEMENTOR_VERSION') || !class_exists('\\Elementor\\Plugin')) {
            return 0;
        }

        $is_editor_request = function_exists('bw_tbl_is_elementor_editor_request') && bw_tbl_is_elementor_editor_request();
        $is_preview_mode = function_exists('bw_tbl_is_elementor_preview') && bw_tbl_is_elementor_preview();
        if (!$is_editor_request && !$is_preview_mode) {
            return 0;
        }

        $template_id = isset($_GET['elementor-preview']) ? absint(wp_unslash($_GET['elementor-preview'])) : 0;
        if ($template_id <= 0 && is_singular('bw_template')) {
            $template_id = absint(get_queried_object_id());
        }
        if ($template_id <= 0 && isset($_GET['post'])) {
            $candidate = absint(wp_unslash($_GET['post']));
            if ($candidate > 0) {
                $candidate_post = get_post($candidate);
                if ($candidate_post instanceof WP_Post && 'bw_template' === $candidate_post->post_type) {
                    $template_id = $candidate;
                }
            }
        }
        if ($template_id <= 0) {
            return 0;
        }

        $template_post = get_post($template_id);
        if (!($template_post instanceof WP_Post) || 'bw_template' !== $template_post->post_type) {
            return 0;
        }

        $template_type = get_post_meta($template_id, 'bw_template_type', true);
        if (function_exists('bw_tbl_sanitize_template_type')) {
            $template_type = bw_tbl_sanitize_template_type($template_type);
        } else {
            $template_type = sanitize_key((string) $template_type);
        }

        if ('single_product' !== $template_type) {
            return 0;
        }

        if (!function_exists('bw_tbl_get_single_product_preview_product_id') || !function_exists('bw_tbl_is_valid_preview_product')) {
            return 0;
        }

        $product_id = bw_tbl_get_single_product_preview_product_id(true);
        return bw_tbl_is_valid_preview_product($product_id) ? $product_id : 0;
    }
}

if (!function_exists('bw_tbl_with_elementor_preview_product_context')) {
    function bw_tbl_with_elementor_preview_product_context($callback)
    {
        if (!is_callable($callback)) {
            return;
        }

        $product_id = bw_tbl_get_elementor_preview_product_id();
        if ($product_id <= 0) {
            return;
        }

        $product_post = get_post($product_id);
        if (!($product_post instanceof WP_Post)) {
            return;
        }

        $previous_post = isset($GLOBALS['post']) ? $GLOBALS['post'] : null;
        $previous_product = isset($GLOBALS['product']) ? $GLOBALS['product'] : null;

        $GLOBALS['post'] = $product_post;
        setup_postdata($product_post);
        if (function_exists('wc_setup_product_data')) {
            wc_setup_product_data($product_post);
        } elseif (function_exists('wc_get_product')) {
            $GLOBALS['product'] = wc_get_product($product_id);
        }

        call_user_func($callback);

        if ($previous_post instanceof WP_Post) {
            $GLOBALS['post'] = $previous_post;
            setup_postdata($previous_post);
        } else {
            unset($GLOBALS['post']);
        }

        if (null !== $previous_product) {
            $GLOBALS['product'] = $previous_product;
        } else {
            unset($GLOBALS['product']);
        }
    }
}

if (!function_exists('bw_tbl_elementor_preview_before_render')) {
    function bw_tbl_elementor_preview_before_render($element)
    {
        if (!defined('ELEMENTOR_VERSION') || !class_exists('\\Elementor\\Widget_Base')) {
            return;
        }

        if (!($element instanceof \Elementor\Widget_Base)) {
            return;
        }

        if (!bw_tbl_get_elementor_preview_product_id()) {
            return;
        }

        $GLOBALS['bw_tbl_preview_render_depth'] = isset($GLOBALS['bw_tbl_preview_render_depth'])
            ? (int) $GLOBALS['bw_tbl_preview_render_depth'] + 1
            : 1;

        $depth = (int) $GLOBALS['bw_tbl_preview_render_depth'];
        if (!isset($GLOBALS['bw_tbl_preview_state']) || !is_array($GLOBALS['bw_tbl_preview_state'])) {
            $GLOBALS['bw_tbl_preview_state'] = [];
        }

        $GLOBALS['bw_tbl_preview_state'][$depth] = [
            'post' => isset($GLOBALS['post']) ? $GLOBALS['post'] : null,
            'product' => isset($GLOBALS['product']) ? $GLOBALS['product'] : null,
        ];

        $product_id = bw_tbl_get_elementor_preview_product_id();
        $product_post = $product_id > 0 ? get_post($product_id) : null;
        if (!($product_post instanceof WP_Post)) {
            return;
        }

        $GLOBALS['post'] = $product_post;
        setup_postdata($product_post);
        if (function_exists('wc_setup_product_data')) {
            wc_setup_product_data($product_post);
        } elseif (function_exists('wc_get_product')) {
            $GLOBALS['product'] = wc_get_product($product_id);
        }
    }
}
add_action('elementor/frontend/before_render', 'bw_tbl_elementor_preview_before_render', 1);

if (!function_exists('bw_tbl_elementor_preview_after_render')) {
    function bw_tbl_elementor_preview_after_render($element)
    {
        if (!defined('ELEMENTOR_VERSION') || !class_exists('\\Elementor\\Widget_Base')) {
            return;
        }

        if (!($element instanceof \Elementor\Widget_Base)) {
            return;
        }

        $depth = isset($GLOBALS['bw_tbl_preview_render_depth']) ? (int) $GLOBALS['bw_tbl_preview_render_depth'] : 0;
        if ($depth <= 0) {
            return;
        }

        $state = isset($GLOBALS['bw_tbl_preview_state'][$depth]) && is_array($GLOBALS['bw_tbl_preview_state'][$depth])
            ? $GLOBALS['bw_tbl_preview_state'][$depth]
            : null;

        if (is_array($state)) {
            $previous_post = isset($state['post']) ? $state['post'] : null;
            $previous_product = isset($state['product']) ? $state['product'] : null;

            if ($previous_post instanceof WP_Post) {
                $GLOBALS['post'] = $previous_post;
                setup_postdata($previous_post);
            } else {
                unset($GLOBALS['post']);
            }

            if (null !== $previous_product) {
                $GLOBALS['product'] = $previous_product;
            } else {
                unset($GLOBALS['product']);
            }

            unset($GLOBALS['bw_tbl_preview_state'][$depth]);
        }

        $GLOBALS['bw_tbl_preview_render_depth'] = $depth - 1;
        if ($GLOBALS['bw_tbl_preview_render_depth'] <= 0) {
            unset($GLOBALS['bw_tbl_preview_render_depth']);
            unset($GLOBALS['bw_tbl_preview_state']);
        }
    }
}
add_action('elementor/frontend/after_render', 'bw_tbl_elementor_preview_after_render', 999);
