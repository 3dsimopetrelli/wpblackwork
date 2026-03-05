<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('pre_get_posts', 'bw_mf_filter_media_list_query', 20);
add_filter('ajax_query_attachments_args', 'bw_mf_filter_media_grid_query', 20, 2);

if (!function_exists('bw_mf_get_current_list_screen_post_type')) {
    function bw_mf_get_current_list_screen_post_type()
    {
        if (!is_admin() || !function_exists('get_current_screen')) {
            return '';
        }

        $screen = get_current_screen();
        if (!$screen) {
            return '';
        }

        if ($screen->id === 'upload') {
            return 'attachment';
        }

        if ($screen->base === 'edit' && !empty($screen->post_type)) {
            return sanitize_key((string) $screen->post_type);
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
                    $normalized[$key] = wp_unslash((string) $_REQUEST['query'][$key]);
                }
            }
        }

        $folder = isset($normalized['bw_media_folder']) ? absint($normalized['bw_media_folder']) : 0;
        $unassigned = isset($normalized['bw_media_unassigned']) && (string) $normalized['bw_media_unassigned'] === '1';
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
        if ($query_post_type && $query_post_type !== $screen_post_type) {
            return false;
        }

        return true;
    }
}

if (!function_exists('bw_mf_should_apply_grid_query_filter')) {
    function bw_mf_should_apply_grid_query_filter($query)
    {
        if (!bw_mf_is_query_attachments_ajax()) {
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
        if (!bw_mf_should_apply_list_query_filter($query)) {
            return;
        }

        $screen_post_type = bw_mf_get_current_list_screen_post_type();
        if ($screen_post_type === '') {
            return;
        }
        $taxonomy = bw_mf_get_taxonomy_for_post_type($screen_post_type);
        if ($taxonomy === '') {
            return;
        }

        $post_type = $query->get('post_type');
        if ($post_type && $post_type !== $screen_post_type) {
            return;
        }

        $payload = bw_mf_get_list_filter_payload();
        $folder_id = (int) $payload['folder_id'];
        $unassigned = !empty($payload['unassigned']);
        if (!$payload['has_filter']) {
            return;
        }

        $existing_tax_query = $query->get('tax_query');
        $mf_tax_query = bw_mf_apply_folder_tax_query([], $folder_id, $unassigned, $taxonomy);
        $query->set('tax_query', bw_mf_merge_tax_query($existing_tax_query, $mf_tax_query));
        $query->set('post_type', $screen_post_type);
    }
}

if (!function_exists('bw_mf_filter_media_grid_query')) {
    function bw_mf_filter_media_grid_query($args, $query = [])
    {
        if (!is_array($args)) {
            bw_mf_grid_debug_log('grid filter bypass: args not array');
            return $args;
        }

        if (!bw_mf_should_apply_grid_query_filter($query)) {
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

        $existing_tax_query = isset($args['tax_query']) ? $args['tax_query'] : [];
        $mf_tax_query = bw_mf_apply_folder_tax_query([], $folder_id, $unassigned, 'bw_media_folder');
        $args['tax_query'] = bw_mf_merge_tax_query($existing_tax_query, $mf_tax_query);
        bw_mf_grid_debug_log('grid filter applied', [
            'folder_id' => $folder_id,
            'unassigned' => $unassigned ? 1 : 0,
            'tax_query' => $args['tax_query'],
        ]);

        return $args;
    }
}
