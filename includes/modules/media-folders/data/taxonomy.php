<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'bw_mf_register_taxonomy', 10);

if (!function_exists('bw_mf_register_taxonomy')) {
    function bw_mf_register_taxonomy()
    {
        $object_types = bw_mf_get_enabled_post_types();
        if (empty($object_types)) {
            $object_types = ['attachment'];
        }

        register_taxonomy(
            'bw_media_folder',
            $object_types,
            [
                'labels' => [
                    'name' => __('Media Folders', 'bw'),
                    'singular_name' => __('Media Folder', 'bw'),
                ],
                'public' => false,
                'show_ui' => false,
                'show_in_rest' => false,
                'hierarchical' => true,
                'show_admin_column' => false,
                'query_var' => false,
                'rewrite' => false,
            ]
        );
    }
}
