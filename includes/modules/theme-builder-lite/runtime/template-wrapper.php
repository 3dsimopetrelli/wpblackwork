<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_tbl_runtime_wrapper_template_path')) {
    function bw_tbl_runtime_wrapper_template_path()
    {
        return BW_MEW_PATH . 'includes/modules/theme-builder-lite/templates/runtime-template-wrapper.php';
    }
}

if (!function_exists('bw_tbl_runtime_set_active_template')) {
    function bw_tbl_runtime_set_active_template($template_id, $template_type)
    {
        $template_id = absint($template_id);
        if ($template_id <= 0) {
            return;
        }

        $GLOBALS['bw_tbl_runtime_template_id'] = $template_id;
        $GLOBALS['bw_tbl_runtime_template_type'] = sanitize_key((string) $template_type);
    }
}

if (!function_exists('bw_tbl_runtime_get_active_template_id')) {
    function bw_tbl_runtime_get_active_template_id()
    {
        $template_id = isset($GLOBALS['bw_tbl_runtime_template_id']) ? absint($GLOBALS['bw_tbl_runtime_template_id']) : 0;
        return $template_id > 0 ? $template_id : 0;
    }
}

if (!function_exists('bw_tbl_runtime_get_active_template_type')) {
    function bw_tbl_runtime_get_active_template_type()
    {
        $template_type = isset($GLOBALS['bw_tbl_runtime_template_type']) ? sanitize_key((string) $GLOBALS['bw_tbl_runtime_template_type']) : '';
        return '' !== $template_type ? $template_type : '';
    }
}

if (!function_exists('bw_tbl_runtime_render_template_content')) {
    function bw_tbl_runtime_render_template_content($template_id)
    {
        $template_id = absint($template_id);
        if ($template_id <= 0) {
            return '';
        }

        if (class_exists('\\Elementor\\Plugin')) {
            $plugin = \Elementor\Plugin::instance();
            if ($plugin && isset($plugin->frontend)) {
                try {
                    $content = $plugin->frontend->get_builder_content_for_display($template_id, true);
                } catch (\Throwable $exception) {
                    return '';
                }

                if (is_string($content) && '' !== trim($content)) {
                    return $content;
                }
            }
        }

        $post_content = get_post_field('post_content', $template_id);
        if (!is_string($post_content) || '' === trim($post_content)) {
            return '';
        }

        return (string) apply_filters('the_content', $post_content);
    }
}
