<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BW_TBL_FEATURE_FLAGS_OPTION')) {
    define('BW_TBL_FEATURE_FLAGS_OPTION', 'bw_theme_builder_lite_flags');
}

if (!defined('BW_TBL_CUSTOM_FONTS_OPTION')) {
    define('BW_TBL_CUSTOM_FONTS_OPTION', 'bw_custom_fonts_v1');
}

if (!defined('BW_TBL_FOOTER_OPTION')) {
    define('BW_TBL_FOOTER_OPTION', 'bw_theme_builder_lite_footer_v1');
}

if (!defined('BW_TBL_SHOP_OPTION')) {
    define('BW_TBL_SHOP_OPTION', 'bw_theme_builder_lite_shop_v1');
}

if (!function_exists('bw_tbl_feature_flag_defaults')) {
    function bw_tbl_feature_flag_defaults()
    {
        return [
            'enabled' => 0,
            'custom_fonts_enabled' => 0,
            'footer_override_enabled' => 0,
            'templates_enabled' => 0,
            'hide_pro_upgrade_panels' => 0,
        ];
    }
}

if (!function_exists('bw_tbl_get_feature_flags')) {
    function bw_tbl_get_feature_flags()
    {
        $saved = get_option(BW_TBL_FEATURE_FLAGS_OPTION, []);
        if (!is_array($saved)) {
            $saved = [];
        }

        return array_replace(bw_tbl_feature_flag_defaults(), $saved);
    }
}

if (!function_exists('bw_tbl_is_feature_enabled')) {
    function bw_tbl_is_feature_enabled($key)
    {
        $flags = bw_tbl_get_feature_flags();
        if (empty($flags['enabled'])) {
            return false;
        }

        return !empty($flags[$key]);
    }
}

if (!function_exists('bw_tbl_sanitize_feature_flags')) {
    function bw_tbl_sanitize_feature_flags($input)
    {
        $input = is_array($input) ? $input : [];
        $defaults = bw_tbl_feature_flag_defaults();

        return [
            'enabled' => !empty($input['enabled']) ? 1 : 0,
            'custom_fonts_enabled' => !empty($input['custom_fonts_enabled']) ? 1 : 0,
            'footer_override_enabled' => !empty($input['footer_override_enabled']) ? 1 : 0,
            'templates_enabled' => !empty($input['templates_enabled']) ? 1 : 0,
            'hide_pro_upgrade_panels' => !empty($input['hide_pro_upgrade_panels']) ? 1 : 0,
        ] + $defaults;
    }
}
