<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('pre_get_posts', 'bw_mf_filter_media_list_query', 20);
add_filter('ajax_query_attachments_args', 'bw_mf_filter_media_grid_query', 20, 2);

if (!function_exists('bw_mf_get_current_list_screen_post_type')) {
    function bw_mf_get_current_list_screen_post_type()
    {
        if (!is_admin()) {
            return '';
        }

        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if ($screen) {
                if ($screen->id === 'upload') {
                    return 'attachment';
                }

                if ($screen->base === 'edit' && !empty($screen->post_type)) {
                    return sanitize_key((string) $screen->post_type);
                }
            }
        }

        global $pagenow;
        if ($pagenow === 'upload.php') {
            return 'attachment';
        }

        if ($pagenow === 'edit.php') {
            if (isset($_GET['post_type'])) {
                return sanitize_key((string) wp_unslash($_GET['post_type']));
            }

            return 'post';
        }

        return '';
    }
}

if (!function_exists('bw_mf_is_rest_request')) {
    function bw_mf_is_rest_request()
    {
        return defined('REST_REQUEST') && REST_REQUEST;
    }
}

if (!function_exists('bw_mf_is_doing_cron')) {
    function bw_mf_is_doing_cron()
    {
        return defined('DOING_CRON') && DOING_CRON;
    }
}

if (!function_exists('bw_mf_is_supported_admin_list_pagenow')) {
    function bw_mf_is_supported_admin_list_pagenow()
    {
        if (!is_admin()) {
            return false;
        }

        global $pagenow;
        return in_array((string) $pagenow, ['upload.php', 'edit.php'], true);
    }
}

if (!function_exists('bw_mf_is_supported_list_screen')) {
    function bw_mf_is_supported_list_screen()
    {
        if (!bw_mf_is_supported_admin_list_pagenow()) {
            return false;
        }

        $post_type = bw_mf_get_current_list_screen_post_type();
        if ($post_type === '') {
            return false;
        }

        return bw_mf_is_post_type_enabled($post_type);
    }
}

if (!function_exists('bw_mf_get_list_filter_payload')) {
    function bw_mf_get_list_filter_payload()
    {
        $folder = 0;
        $unassigned = false;

        if (isset($_GET['bw_media_folder'])) {
            $folder = absint(wp_unslash((string) $_GET['bw_media_folder']));
        }
        if (isset($_GET['bw_media_unassigned'])) {
            $unassigned = (string) wp_unslash((string) $_GET['bw_media_unassigned']) === '1';
        }
        if ($unassigned) {
            $folder = 0;
        }

        return [
            'folder_id' => $folder,
            'unassigned' => $unassigned,
            'has_filter' => $unassigned || $folder > 0,
        ];
    }
}

if (!function_exists('bw_mf_has_list_filter_params')) {
    function bw_mf_has_list_filter_params()
    {
        $payload = bw_mf_get_list_filter_payload();
        return !empty($payload['has_filter']);
    }
}

if (!function_exists('bw_mf_is_query_attachments_ajax')) {
    function bw_mf_is_query_attachments_ajax()
    {
        if (!is_admin() || !wp_doing_ajax()) {
            return false;
        }

        if (bw_mf_is_rest_request() || bw_mf_is_doing_cron()) {
            return false;
        }

        $action = isset($_REQUEST['action']) ? sanitize_key((string) $_REQUEST['action']) : '';
        return $action === 'query-attachments';
    }
}

if (!function_exists('bw_mf_get_grid_filter_payload')) {
    function bw_mf_get_grid_filter_payload($query)
    {
        $normalized = [];

        if (is_object($query) && property_exists($query, 'query_vars') && is_array($query->query_vars)) {
            $normalized = $query->query_vars;
        } elseif (is_array($query)) {
            $normalized = $query;
        }

        if ((!isset($normalized['bw_media_folder']) && !isset($normalized['bw_media_unassigned'])) && isset($_REQUEST['query']) && is_array($_REQUEST['query'])) {
            foreach (['bw_media_folder', 'bw_media_unassigned'] as $key) {
                if (isset($_REQUEST['query'][$key])) {
                    if (is_scalar($_REQUEST['query'][$key]) || $_REQUEST['query'][$key] === null) {
                        $normalized[$key] = wp_unslash((string) $_REQUEST['query'][$key]);
                    }
                }
            }
        }

        $folder = isset($normalized['bw_media_folder']) && (is_scalar($normalized['bw_media_folder']) || $normalized['bw_media_folder'] === null)
            ? absint((string) $normalized['bw_media_folder'])
            : 0;
        $unassigned = isset($normalized['bw_media_unassigned']) && (is_scalar($normalized['bw_media_unassigned']) || $normalized['bw_media_unassigned'] === null) && (string) $normalized['bw_media_unassigned'] === '1';
        if ($unassigned) {
            $folder = 0;
        }

        return [
            'folder_id' => $folder,
            'unassigned' => $unassigned,
            'has_filter' => $unassigned || $folder > 0,
        ];
    }
}

if (!function_exists('bw_mf_should_apply_list_query_filter')) {
    function bw_mf_should_apply_list_query_filter($query)
    {
        if (!is_admin() || bw_mf_is_rest_request() || bw_mf_is_doing_cron() || wp_doing_ajax()) {
            return false;
        }

        if (!$query instanceof WP_Query || !$query->is_main_query()) {
            return false;
        }

        if (!bw_mf_has_list_filter_params()) {
            return false;
        }

        if (!bw_mf_is_supported_list_screen()) {
            return false;
        }

        $screen_post_type = bw_mf_get_current_list_screen_post_type();
        if ($screen_post_type === '') {
            return false;
        }

        $query_post_type = $query->get('post_type');
        if (is_array($query_post_type)) {
            $query_post_type = array_map('sanitize_key', $query_post_type);
            if (!in_array($screen_post_type, $query_post_type, true)) {
                return false;
            }
        } elseif ($query_post_type && sanitize_key((string) $query_post_type) !== $screen_post_type) {
            return false;
        }

        return true;
    }
}

if (!function_exists('bw_mf_should_apply_grid_query_filter')) {
    function bw_mf_should_apply_grid_query_filter($query, $args = [])
    {
        if (!bw_mf_is_query_attachments_ajax()) {
            return false;
        }

        if (!is_array($args)) {
            return false;
        }

        if (isset($args['post_type']) && sanitize_key((string) $args['post_type']) !== 'attachment') {
            return false;
        }

        $normalized = [];
        if (is_object($query) && property_exists($query, 'query_vars') && is_array($query->query_vars)) {
            $normalized = $query->query_vars;
        } elseif (is_array($query)) {
            $normalized = $query;
        }
        if (isset($normalized['post_type']) && sanitize_key((string) $normalized['post_type']) !== 'attachment') {
            return false;
        }

        if ('' === bw_mf_get_valid_media_folder_taxonomy('attachment')) {
            return false;
        }

        $payload = bw_mf_get_grid_filter_payload($query);
        return !empty($payload['has_filter']);
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
    function bw_mf_apply_folder_tax_query(array $tax_query, $folder_id, $unassigned, $taxonomy)
    {
        $taxonomy = sanitize_key((string) $taxonomy);
        if ($taxonomy === '') {
            return $tax_query;
        }

        if (!taxonomy_exists($taxonomy)) {
            return $tax_query;
        }

        if ($unassigned) {
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'operator' => 'NOT EXISTS',
            ];

            return $tax_query;
        }

        if ($folder_id > 0) {
            $tax_query[] = [
                'taxonomy' => $taxonomy,
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

        $existing_relation = 'AND';
        if (isset($existing['relation']) && in_array($existing['relation'], ['AND', 'OR'], true)) {
            $existing_relation = $existing['relation'];
            unset($existing['relation']);
        }

        $existing_clauses = [];
        foreach ($existing as $clause) {
            if (is_array($clause)) {
                $existing_clauses[] = $clause;
            }
        }

        $addition_clauses = [];
        foreach ($addition as $clause) {
            if (is_array($clause)) {
                $addition_clauses[] = $clause;
            }
        }

        if (empty($addition_clauses)) {
            if (!empty($existing_clauses) && count($existing_clauses) > 1) {
                $existing_clauses['relation'] = $existing_relation;
            }
            return $existing_clauses;
        }

        if (empty($existing_clauses)) {
            if (count($addition_clauses) > 1) {
                $addition_clauses['relation'] = 'AND';
            }
            return $addition_clauses;
        }

        $existing_group = $existing_clauses;
        if (count($existing_group) > 1) {
            $existing_group['relation'] = $existing_relation;
        }

        $merged = [$existing_group];
        foreach ($addition_clauses as $clause) {
            $merged[] = $clause;
        }
        $merged['relation'] = 'AND';

        return $merged;
    }
}

if (!function_exists('bw_mf_filter_media_list_query')) {
    function bw_mf_filter_media_list_query($query)
    {
        static $running = false;
        if ($running) {
            return;
        }

        if (!bw_mf_should_apply_list_query_filter($query)) {
            return;
        }

        $screen_post_type = bw_mf_get_current_list_screen_post_type();
        if ($screen_post_type === '') {
            return;
        }
        $taxonomy = bw_mf_get_valid_media_folder_taxonomy($screen_post_type);
        if ($taxonomy === '') {
            return;
        }

        $post_type = $query->get('post_type');
        if (is_array($post_type)) {
            $post_type = array_map('sanitize_key', $post_type);
            if (!in_array($screen_post_type, $post_type, true)) {
                return;
            }
        } elseif ($post_type && sanitize_key((string) $post_type) !== $screen_post_type) {
            return;
        }

        $payload = bw_mf_get_list_filter_payload();
        $folder_id = (int) $payload['folder_id'];
        $unassigned = !empty($payload['unassigned']);
        if (!$payload['has_filter']) {
            return;
        }

        $running = true;
        $existing_tax_query = $query->get('tax_query');
        $mf_tax_query = bw_mf_apply_folder_tax_query([], $folder_id, $unassigned, $taxonomy);
        if (!empty($mf_tax_query)) {
            $query->set('tax_query', bw_mf_merge_tax_query($existing_tax_query, $mf_tax_query));
            $query->set('post_type', $screen_post_type);
        }
        $running = false;
    }
}

if (!function_exists('bw_mf_filter_media_grid_query')) {
    function bw_mf_filter_media_grid_query(...$filter_args)
    {
        $args = $filter_args[0] ?? [];
        $query = $filter_args[1] ?? [];

        if (!is_array($args)) {
            bw_mf_grid_debug_log('grid filter bypass: args not array');
            return $args;
        }

        if (!bw_mf_should_apply_grid_query_filter($query, $args)) {
            bw_mf_grid_debug_log('grid filter bypass: missing custom vars');
            return $args;
        }

        $payload = bw_mf_get_grid_filter_payload($query);
        $folder_id = (int) $payload['folder_id'];
        $unassigned = !empty($payload['unassigned']);
        if (!$payload['has_filter']) {
            bw_mf_grid_debug_log('grid filter bypass: invalid folder/unassigned payload', $query);
            return $args;
        }

        $taxonomy = bw_mf_get_valid_media_folder_taxonomy('attachment');
        if ($taxonomy === '') {
            bw_mf_grid_debug_log('grid filter bypass: invalid attachment taxonomy');
            return $args;
        }

        $existing_tax_query = isset($args['tax_query']) ? $args['tax_query'] : [];
        $mf_tax_query = bw_mf_apply_folder_tax_query([], $folder_id, $unassigned, $taxonomy);
        if (empty($mf_tax_query)) {
            bw_mf_grid_debug_log('grid filter bypass: empty tax query');
            return $args;
        }

        $args['tax_query'] = bw_mf_merge_tax_query($existing_tax_query, $mf_tax_query);
        $args['post_type'] = 'attachment';
        bw_mf_grid_debug_log('grid filter applied', [
            'folder_id' => $folder_id,
            'unassigned' => $unassigned ? 1 : 0,
            'tax_query' => $args['tax_query'],
        ]);

        return $args;
    }
}

if (!function_exists('bw_mf_get_valid_media_folder_taxonomy')) {
    function bw_mf_get_valid_media_folder_taxonomy($post_type)
    {
        $post_type = sanitize_key((string) $post_type);
        if ($post_type === '') {
            return '';
        }

        $taxonomy = bw_mf_get_taxonomy_for_post_type($post_type);
        $taxonomy = sanitize_key((string) $taxonomy);
        if ($taxonomy === '' || !taxonomy_exists($taxonomy)) {
            return '';
        }

        $object = get_taxonomy($taxonomy);
        if (!$object || empty($object->object_type) || !in_array($post_type, (array) $object->object_type, true)) {
            return '';
        }

        return $taxonomy;
    }
}
