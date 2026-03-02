<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_tbl_register_elementor_fonts_integration')) {
    function bw_tbl_register_elementor_fonts_integration()
    {
        static $registered = false;
        if ($registered) {
            return;
        }

        add_filter('elementor/fonts/groups', 'bw_tbl_elementor_fonts_groups', 20);
        add_filter('elementor/fonts/additional_fonts', 'bw_tbl_elementor_additional_fonts', 20);
        add_action('elementor/editor/after_enqueue_styles', 'bw_tbl_enqueue_custom_fonts_css', 20);
        add_action('elementor/preview/enqueue_styles', 'bw_tbl_enqueue_custom_fonts_css', 20);

        $registered = true;
    }
}

if (!function_exists('bw_tbl_bootstrap_elementor_fonts_integration')) {
    function bw_tbl_bootstrap_elementor_fonts_integration()
    {
        if (did_action('elementor/loaded')) {
            bw_tbl_register_elementor_fonts_integration();
            return;
        }

        add_action('elementor/loaded', 'bw_tbl_register_elementor_fonts_integration', 20);
    }
}
add_action('plugins_loaded', 'bw_tbl_bootstrap_elementor_fonts_integration', 20);

if (!function_exists('bw_tbl_elementor_fonts_groups')) {
    function bw_tbl_elementor_fonts_groups($groups)
    {
        if (!did_action('elementor/loaded') || !bw_tbl_is_feature_enabled('custom_fonts_enabled')) {
            return $groups;
        }

        if (!is_array($groups)) {
            $groups = [];
        }

        if (!isset($groups['bw_tbl_custom_fonts'])) {
            $groups['bw_tbl_custom_fonts'] = __('Custom Fonts', 'bw');
        }

        return $groups;
    }
}

if (!function_exists('bw_tbl_elementor_additional_fonts')) {
    function bw_tbl_elementor_additional_fonts($additional_fonts)
    {
        if (!did_action('elementor/loaded') || !bw_tbl_is_feature_enabled('custom_fonts_enabled')) {
            return $additional_fonts;
        }

        $families = bw_tbl_get_custom_font_families();
        if (empty($families)) {
            return $additional_fonts;
        }

        if (!is_array($additional_fonts)) {
            $additional_fonts = [];
        }

        foreach ($families as $family) {
            $family = sanitize_text_field((string) $family);
            if ('' === $family || isset($additional_fonts[$family])) {
                continue;
            }

            // Elementor expects: font-family string => font-group key.
            $additional_fonts[$family] = 'bw_tbl_custom_fonts';
        }

        return $additional_fonts;
    }
}
