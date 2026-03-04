<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('pre_get_posts', 'bw_mf_filter_media_list_query', 20);
add_filter('ajax_query_attachments_args', 'bw_mf_filter_media_grid_query', 20, 2);

if (!function_exists('bw_mf_is_upload_screen')) {
    function bw_mf_is_upload_screen()
    {
        if (!is_admin() || !function_exists('get_current_screen')) {
            return false;
        }

        $screen = get_current_screen();
        return $screen && $screen->id === 'upload';
    }
}

if (!function_exists('bw_mf_is_query_attachments_ajax')) {
    function bw_mf_is_query_attachments_ajax()
    {
        if (!is_admin() || !wp_doing_ajax()) {
            return false;
        }

        $action = isset($_REQUEST['action']) ? sanitize_key((string) $_REQUEST['action']) : '';
        return $action === 'query-attachments';
    }
}

if (!function_exists('bw_mf_apply_folder_tax_query')) {
    function bw_mf_apply_folder_tax_query(array $tax_query, $folder_id, $unassigned)
    {
        if ($unassigned) {
            $tax_query[] = [
                'taxonomy' => 'bw_media_folder',
                'operator' => 'NOT EXISTS',
            ];

            return $tax_query;
        }

        if ($folder_id > 0) {
            $tax_query[] = [
                'taxonomy' => 'bw_media_folder',
                'field' => 'term_id',
                'terms' => [$folder_id],
                'include_children' => true,
            ];
        }

        return $tax_query;
    }
}

if (!function_exists('bw_mf_filter_media_list_query')) {
    function bw_mf_filter_media_list_query($query)
    {
        if (!bw_mf_is_upload_screen() || !$query instanceof WP_Query || !$query->is_main_query()) {
            return;
        }

        $post_type = $query->get('post_type');
        if ($post_type && $post_type !== 'attachment') {
            return;
        }

        $folder_id = isset($_GET['bw_media_folder']) ? absint($_GET['bw_media_folder']) : 0;
        $unassigned = !empty($_GET['bw_media_unassigned']);

        if (!$unassigned && $folder_id <= 0) {
            return;
        }

        $tax_query = $query->get('tax_query');
        if (!is_array($tax_query)) {
            $tax_query = [];
        }

        $query->set('tax_query', bw_mf_apply_folder_tax_query($tax_query, $folder_id, $unassigned));
        $query->set('post_type', 'attachment');
    }
}

if (!function_exists('bw_mf_filter_media_grid_query')) {
    function bw_mf_filter_media_grid_query($args, $query)
    {
        if (!bw_mf_is_query_attachments_ajax()) {
            return $args;
        }

        $has_custom_filter = isset($query['bw_media_folder']) || isset($query['bw_media_unassigned']);
        if (!$has_custom_filter) {
            return $args;
        }

        $folder_id = isset($query['bw_media_folder']) ? absint($query['bw_media_folder']) : 0;
        $unassigned = !empty($query['bw_media_unassigned']);

        if (!$unassigned && $folder_id <= 0) {
            return $args;
        }

        $tax_query = isset($args['tax_query']) && is_array($args['tax_query']) ? $args['tax_query'] : [];
        $args['tax_query'] = bw_mf_apply_folder_tax_query($tax_query, $folder_id, $unassigned);

        return $args;
    }
}
