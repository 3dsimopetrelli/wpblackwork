<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'bw_mf_register_taxonomy', 10);

if (!function_exists('bw_mf_register_taxonomy')) {
    function bw_mf_register_taxonomy()
    {
        register_taxonomy(
            'bw_media_folder',
            ['attachment'],
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
