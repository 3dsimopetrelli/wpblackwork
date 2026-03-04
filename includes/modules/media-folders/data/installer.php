<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BW_CORE_FLAGS_OPTION')) {
    define('BW_CORE_FLAGS_OPTION', 'bw_core_flags');
}

if (!function_exists('bw_core_default_flags')) {
    function bw_core_default_flags()
    {
        return [
            'media_folders' => 0,
            'media_folders_corner_indicator' => 0,
        ];
    }
}

if (!function_exists('bw_core_get_flags')) {
    function bw_core_get_flags()
    {
        $saved = get_option(BW_CORE_FLAGS_OPTION, []);
        if (!is_array($saved)) {
            $saved = [];
        }

        return array_replace(bw_core_default_flags(), $saved);
    }
}

if (!function_exists('bw_mf_is_enabled')) {
    function bw_mf_is_enabled()
    {
        $flags = bw_core_get_flags();
        return !empty($flags['media_folders']);
    }
}

if (!function_exists('bw_mf_is_corner_indicator_enabled')) {
    function bw_mf_is_corner_indicator_enabled()
    {
        $flags = bw_core_get_flags();
        return !empty($flags['media_folders']) && !empty($flags['media_folders_corner_indicator']);
    }
}

if (!function_exists('bw_core_update_flags')) {
    function bw_core_update_flags(array $partial)
    {
        $flags = bw_core_get_flags();
        foreach ($partial as $key => $value) {
            $flags[(string) $key] = !empty($value) ? 1 : 0;
        }

        return update_option(BW_CORE_FLAGS_OPTION, $flags);
    }
}
