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
                'show_ui' => true,
                'show_in_menu' => 'blackwork-site-settings',
                'show_in_admin_bar' => true,
                'supports' => ['title', 'editor', 'revisions'],
                'capability_type' => 'post',
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
