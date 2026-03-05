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
            'media_folders_use_media' => 1,
            'media_folders_use_posts' => 0,
            'media_folders_use_pages' => 0,
            'media_folders_use_products' => 0,
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

if (!function_exists('bw_mf_supported_post_type_flags')) {
    function bw_mf_supported_post_type_flags()
    {
        return [
            'attachment' => 'media_folders_use_media',
            'post' => 'media_folders_use_posts',
            'page' => 'media_folders_use_pages',
            'product' => 'media_folders_use_products',
        ];
    }
}

if (!function_exists('bw_mf_get_enabled_post_types')) {
    function bw_mf_get_enabled_post_types()
    {
        if (!bw_mf_is_enabled()) {
            return [];
        }

        $flags = bw_core_get_flags();
        $enabled = [];
        foreach (bw_mf_supported_post_type_flags() as $post_type => $flag_key) {
            if (!empty($flags[$flag_key])) {
                $enabled[] = $post_type;
            }
        }

        if (empty($enabled)) {
            $enabled[] = 'attachment';
        }

        return array_values(array_unique($enabled));
    }
}

if (!function_exists('bw_mf_is_post_type_enabled')) {
    function bw_mf_is_post_type_enabled($post_type)
    {
        $post_type = sanitize_key((string) $post_type);
        if ($post_type === '') {
            return false;
        }

        return in_array($post_type, bw_mf_get_enabled_post_types(), true);
    }
}

if (!function_exists('bw_mf_get_context_for_post_type')) {
    function bw_mf_get_context_for_post_type($post_type)
    {
        $post_type = sanitize_key((string) $post_type);
        if ($post_type === 'attachment') {
            return 'upload';
        }

        return $post_type;
    }
}

if (!function_exists('bw_mf_get_post_type_for_context')) {
    function bw_mf_get_post_type_for_context($context)
    {
        $context = sanitize_key((string) $context);
        if ($context === 'upload') {
            return 'attachment';
        }

        return $context;
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
