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
            'media_folders' => 1,
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
