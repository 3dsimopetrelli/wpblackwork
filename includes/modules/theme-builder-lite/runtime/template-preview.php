<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_tbl_preview_template_path')) {
    function bw_tbl_preview_template_path()
    {
        return BW_MEW_PATH . 'includes/modules/theme-builder-lite/templates/single-bw-template.php';
    }
}

if (!function_exists('bw_tbl_include_single_template_preview')) {
    function bw_tbl_include_single_template_preview($template)
    {
        if (is_admin() || !is_singular('bw_template')) {
            return $template;
        }

        $preview_template = bw_tbl_preview_template_path();
        if (!file_exists($preview_template)) {
            return $template;
        }

        return $preview_template;
    }
}
add_filter('template_include', 'bw_tbl_include_single_template_preview', 99);

if (!function_exists('bw_tbl_add_noindex_for_bw_template')) {
    function bw_tbl_add_noindex_for_bw_template($robots)
    {
        if (!is_singular('bw_template')) {
            return $robots;
        }

        if (!is_array($robots)) {
            $robots = [];
        }

        $robots['noindex'] = true;
        $robots['nofollow'] = true;
        $robots['noarchive'] = true;

        return $robots;
    }
}
add_filter('wp_robots', 'bw_tbl_add_noindex_for_bw_template');
