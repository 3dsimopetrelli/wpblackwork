<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_tbl_register_template_cpt')) {
    function bw_tbl_register_template_cpt()
    {
        $labels = [
            'name' => __('BW Templates', 'bw'),
            'singular_name' => __('BW Template', 'bw'),
            'menu_name' => __('BW Templates', 'bw'),
            'name_admin_bar' => __('BW Template', 'bw'),
            'add_new' => __('Add New', 'bw'),
            'add_new_item' => __('Add New Template', 'bw'),
            'new_item' => __('New Template', 'bw'),
            'edit_item' => __('Edit Template', 'bw'),
            'view_item' => __('View Template', 'bw'),
            'all_items' => __('All Templates', 'bw'),
            'search_items' => __('Search Templates', 'bw'),
            'not_found' => __('No templates found.', 'bw'),
            'not_found_in_trash' => __('No templates found in Trash.', 'bw'),
        ];

        register_post_type(
            'bw_template',
            [
                'labels' => $labels,
                'public' => false,
                'publicly_queryable' => true,
                'show_ui' => true,
                'show_in_menu' => 'blackwork-site-settings',
                'show_in_admin_bar' => true,
                'show_in_rest' => true,
                'supports' => ['title', 'editor', 'revisions'],
                'capability_type' => 'post',
                'exclude_from_search' => true,
                'has_archive' => false,
                'query_var' => true,
                'rewrite' => [
                    'slug' => 'bw-template',
                    'with_front' => false,
                ],
                'menu_position' => 80,
                'menu_icon' => 'dashicons-layout',
            ]
        );
    }
}
add_action('init', 'bw_tbl_register_template_cpt', 9);

if (!function_exists('bw_tbl_add_elementor_cpt_support')) {
    function bw_tbl_add_elementor_cpt_support($post_types)
    {
        if (!is_array($post_types)) {
            $post_types = [];
        }

        $post_types[] = 'bw_template';

        return array_values(array_unique($post_types));
    }
}
add_filter('elementor/cpt_support', 'bw_tbl_add_elementor_cpt_support');

if (!function_exists('bw_tbl_ensure_elementor_cpt_support_option')) {
    function bw_tbl_ensure_elementor_cpt_support_option()
    {
        $supported = get_option('elementor_cpt_support', []);
        if (!is_array($supported)) {
            $supported = [];
        }

        if (!in_array('bw_template', $supported, true)) {
            $supported[] = 'bw_template';
            $supported = array_values(array_unique($supported));
            update_option('elementor_cpt_support', $supported);
        }
    }
}
add_action('admin_init', 'bw_tbl_ensure_elementor_cpt_support_option', 20);
add_action('update_option_' . BW_TBL_FEATURE_FLAGS_OPTION, 'bw_tbl_ensure_elementor_cpt_support_option', 10, 0);

if (!function_exists('bw_tbl_maybe_flush_template_rewrite_rules')) {
    function bw_tbl_maybe_flush_template_rewrite_rules()
    {
        $version_key = 'bw_tbl_rewrite_rules_version';
        $version = (string) get_option($version_key, '');
        $target = '1';

        if ($version === $target) {
            return;
        }

        flush_rewrite_rules(false);
        update_option($version_key, $target, false);
    }
}
add_action('admin_init', 'bw_tbl_maybe_flush_template_rewrite_rules', 30);
