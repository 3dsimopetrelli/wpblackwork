<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_tbl_preview_debug_log')) {
    function bw_tbl_preview_debug_log($message, $context = [])
    {
        if (!defined('BW_TBL_DEBUG_PREVIEW') || !BW_TBL_DEBUG_PREVIEW) {
            return;
        }

        $suffix = '';
        if (is_array($context) && !empty($context)) {
            $suffix = ' ' . wp_json_encode($context);
        }

        error_log('[BW TBL Preview] ' . (string) $message . $suffix); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    }
}

if (!function_exists('bw_tbl_is_elementor_preview_request')) {
    function bw_tbl_is_elementor_preview_request()
    {
        if (is_admin() || wp_doing_ajax() || is_feed() || is_embed()) {
            return false;
        }

        if (!defined('ELEMENTOR_VERSION') || !class_exists('\\Elementor\\Plugin')) {
            return false;
        }

        $plugin = \Elementor\Plugin::$instance;
        $is_preview_mode = $plugin && isset($plugin->preview) && method_exists($plugin->preview, 'is_preview_mode') && $plugin->preview->is_preview_mode();

        if ($is_preview_mode) {
            return true;
        }

        return isset($_GET['elementor-preview']) || isset($_GET['elementor_library']);
    }
}

if (!function_exists('bw_tbl_get_elementor_preview_template_id')) {
    function bw_tbl_get_elementor_preview_template_id()
    {
        $template_id = isset($_GET['elementor-preview']) ? absint(wp_unslash($_GET['elementor-preview'])) : 0;

        if ($template_id <= 0 && isset($_GET['post'])) {
            $template_id = absint(wp_unslash($_GET['post']));
        }

        if ($template_id <= 0 && is_singular('bw_template')) {
            $template_id = absint(get_queried_object_id());
        }

        return $template_id > 0 ? $template_id : 0;
    }
}

if (!function_exists('bw_tbl_get_elementor_preview_single_product_context')) {
    function bw_tbl_get_elementor_preview_single_product_context()
    {
        if (!bw_tbl_is_elementor_preview_request()) {
            return [
                'apply' => false,
                'reason' => 'not_preview_request',
                'template_id' => 0,
                'product_id' => 0,
            ];
        }

        $template_id = bw_tbl_get_elementor_preview_template_id();
        if ($template_id <= 0) {
            return [
                'apply' => false,
                'reason' => 'missing_template_id',
                'template_id' => 0,
                'product_id' => 0,
            ];
        }

        $template_post = get_post($template_id);
        if (!($template_post instanceof WP_Post) || 'bw_template' !== $template_post->post_type) {
            return [
                'apply' => false,
                'reason' => 'not_bw_template',
                'template_id' => $template_id,
                'product_id' => 0,
            ];
        }

        $template_type = get_post_meta($template_id, 'bw_template_type', true);
        if (function_exists('bw_tbl_sanitize_template_type')) {
            $template_type = bw_tbl_sanitize_template_type($template_type);
        } else {
            $template_type = sanitize_key((string) $template_type);
        }

        if ('single_product' !== $template_type) {
            return [
                'apply' => false,
                'reason' => 'template_type_mismatch',
                'template_id' => $template_id,
                'product_id' => 0,
            ];
        }

        if (!function_exists('bw_tbl_get_single_product_preview_product_id') || !function_exists('bw_tbl_is_valid_preview_product')) {
            return [
                'apply' => false,
                'reason' => 'missing_preview_helpers',
                'template_id' => $template_id,
                'product_id' => 0,
            ];
        }

        $product_id = bw_tbl_get_single_product_preview_product_id(true);
        if (!bw_tbl_is_valid_preview_product($product_id)) {
            return [
                'apply' => false,
                'reason' => 'invalid_preview_product',
                'template_id' => $template_id,
                'product_id' => absint($product_id),
            ];
        }

        return [
            'apply' => true,
            'reason' => 'ok',
            'template_id' => $template_id,
            'product_id' => absint($product_id),
        ];
    }
}

if (!function_exists('bw_tbl_apply_elementor_single_product_preview_query_context')) {
    function bw_tbl_apply_elementor_single_product_preview_query_context()
    {
        static $applied = false;
        if ($applied) {
            return;
        }

        $context = bw_tbl_get_elementor_preview_single_product_context();
        bw_tbl_preview_debug_log('preview request detected', [
            'detected' => bw_tbl_is_elementor_preview_request(),
            'template_id' => $context['template_id'],
            'product_id' => $context['product_id'],
            'reason' => $context['reason'],
        ]);

        if (empty($context['apply'])) {
            return;
        }

        $product_id = absint($context['product_id']);
        $product_post = get_post($product_id);
        if (!($product_post instanceof WP_Post)) {
            bw_tbl_preview_debug_log('preview spoof skipped: product post not found', ['product_id' => $product_id]);
            return;
        }

        global $wp_query, $product;

        $GLOBALS['post'] = $product_post;
        setup_postdata($product_post);

        if ($wp_query instanceof WP_Query) {
            $wp_query->is_singular = true;
            $wp_query->is_single = true;
            $wp_query->is_page = false;
            $wp_query->is_archive = false;
            $wp_query->queried_object = $product_post;
            $wp_query->queried_object_id = $product_id;
            $wp_query->post = $product_post;
            $wp_query->posts = [$product_post];
            $wp_query->post_count = 1;
        }

        if (function_exists('wc_setup_product_data')) {
            wc_setup_product_data($product_post);
            $product = wc_get_product($product_id);
        } elseif (function_exists('wc_get_product')) {
            $product = wc_get_product($product_id);
        }

        $applied = true;

        bw_tbl_preview_debug_log('wp_query spoof applied', [
            'template_id' => $context['template_id'],
            'product_id' => $product_id,
            'applied' => true,
        ]);
    }
}
add_action('wp', 'bw_tbl_apply_elementor_single_product_preview_query_context', 20);
