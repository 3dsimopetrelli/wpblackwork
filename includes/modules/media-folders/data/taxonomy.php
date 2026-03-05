<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'bw_mf_register_taxonomy', 10);

if (!function_exists('bw_mf_taxonomy_map')) {
    function bw_mf_taxonomy_map()
    {
        return [
            'attachment' => 'bw_media_folder',
            'post' => 'bw_post_folder',
            'page' => 'bw_page_folder',
            'product' => 'bw_product_folder',
        ];
    }
}

if (!function_exists('bw_mf_get_supported_taxonomies')) {
    function bw_mf_get_supported_taxonomies()
    {
        return array_values(array_unique(array_values(bw_mf_taxonomy_map())));
    }
}

if (!function_exists('bw_mf_get_taxonomy_for_post_type')) {
    function bw_mf_get_taxonomy_for_post_type($post_type)
    {
        $post_type = sanitize_key((string) $post_type);
        $map = bw_mf_taxonomy_map();
        if (!isset($map[$post_type])) {
            return '';
        }

        return $map[$post_type];
    }
}

if (!function_exists('bw_mf_register_taxonomy')) {
    function bw_mf_register_taxonomy()
    {
        foreach (bw_mf_taxonomy_map() as $post_type => $taxonomy) {
            $label_base = $post_type === 'attachment' ? __('Media Folder', 'bw') : __('Content Folder', 'bw');
            register_taxonomy(
                $taxonomy,
                [$post_type],
                [
                    'labels' => [
                        'name' => sprintf(__('%s Folders', 'bw'), ucfirst($post_type)),
                        'singular_name' => $label_base,
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
}
