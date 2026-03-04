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

if (!function_exists('bw_mf_grid_debug_log')) {
    function bw_mf_grid_debug_log($message, $context = [])
    {
        if (!defined('BW_MF_DEBUG') || !BW_MF_DEBUG) {
            return;
        }

        $payload = '';
        if (is_array($context) && !empty($context)) {
            $encoded = wp_json_encode($context);
            $payload = $encoded ? ' ' . $encoded : '';
        }

        error_log('[BW_MF_DEBUG] ' . $message . $payload);
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
            ];
        }

        return $tax_query;
    }
}

if (!function_exists('bw_mf_merge_tax_query')) {
    function bw_mf_merge_tax_query($existing, array $addition)
    {
        if (!is_array($existing)) {
            $existing = [];
        }

        $relation = 'AND';
        if (isset($existing['relation']) && in_array($existing['relation'], ['AND', 'OR'], true)) {
            $relation = $existing['relation'];
            unset($existing['relation']);
        }

        $merged = [];
        foreach ($existing as $clause) {
            if (is_array($clause)) {
                $merged[] = $clause;
            }
        }

        foreach ($addition as $clause) {
            if (is_array($clause)) {
                $merged[] = $clause;
            }
        }

        if (!empty($merged)) {
            $merged['relation'] = $relation;
        }

        return $merged;
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
        $unassigned = isset($_GET['bw_media_unassigned']) && (string) $_GET['bw_media_unassigned'] === '1';
        if ($unassigned) {
            $folder_id = 0;
        }

        if (!$unassigned && $folder_id <= 0) {
            return;
        }

        $existing_tax_query = $query->get('tax_query');
        $mf_tax_query = bw_mf_apply_folder_tax_query([], $folder_id, $unassigned);
        $query->set('tax_query', bw_mf_merge_tax_query($existing_tax_query, $mf_tax_query));
        $query->set('post_type', 'attachment');
    }
}

if (!function_exists('bw_mf_filter_media_grid_query')) {
    function bw_mf_filter_media_grid_query($args, $query = [])
    {
        if (!is_array($args)) {
            bw_mf_grid_debug_log('grid filter bypass: args not array');
            return $args;
        }

        if (is_object($query) && property_exists($query, 'query_vars') && is_array($query->query_vars)) {
            $query = $query->query_vars;
        } elseif (!is_array($query)) {
            $query = [];
        }

        if ((!isset($query['bw_media_folder']) && !isset($query['bw_media_unassigned'])) && isset($_REQUEST['query']) && is_array($_REQUEST['query'])) {
            foreach (['bw_media_folder', 'bw_media_unassigned', 'bw_media_assigned'] as $key) {
                if (isset($_REQUEST['query'][$key])) {
                    $query[$key] = $_REQUEST['query'][$key];
                }
            }
        }

        if (!bw_mf_is_query_attachments_ajax()) {
            return $args;
        }

        $has_custom_filter = isset($query['bw_media_folder']) || isset($query['bw_media_unassigned']);
        if (!$has_custom_filter) {
            bw_mf_grid_debug_log('grid filter bypass: missing custom vars');
            return $args;
        }

        $folder_id = isset($query['bw_media_folder']) ? absint($query['bw_media_folder']) : 0;
        $unassigned = isset($query['bw_media_unassigned']) && (string) $query['bw_media_unassigned'] === '1';
        if ($unassigned) {
            $folder_id = 0;
        }

        if (!$unassigned && $folder_id <= 0) {
            bw_mf_grid_debug_log('grid filter bypass: invalid folder/unassigned payload', $query);
            return $args;
        }

        $existing_tax_query = isset($args['tax_query']) ? $args['tax_query'] : [];
        $mf_tax_query = bw_mf_apply_folder_tax_query([], $folder_id, $unassigned);
        $args['tax_query'] = bw_mf_merge_tax_query($existing_tax_query, $mf_tax_query);
        bw_mf_grid_debug_log('grid filter applied', [
            'folder_id' => $folder_id,
            'unassigned' => $unassigned ? 1 : 0,
            'tax_query' => $args['tax_query'],
        ]);

        return $args;
    }
}
