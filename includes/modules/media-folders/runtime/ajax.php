<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BW_MF_ASSIGN_BATCH_LIMIT')) {
    define('BW_MF_ASSIGN_BATCH_LIMIT', 200);
}

if (!defined('BW_MF_COUNTS_CACHE_KEY')) {
    define('BW_MF_COUNTS_CACHE_KEY', 'bw_mf_folder_counts_v1');
}

if (!defined('BW_MF_COUNTS_CACHE_TTL')) {
    define('BW_MF_COUNTS_CACHE_TTL', 180);
}

add_action('wp_ajax_bw_media_get_folders_tree', 'bw_mf_ajax_get_folders_tree');
add_action('wp_ajax_bw_media_get_folder_counts', 'bw_mf_ajax_get_folder_counts');
add_action('wp_ajax_bw_media_create_folder', 'bw_mf_ajax_create_folder');
add_action('wp_ajax_bw_media_rename_folder', 'bw_mf_ajax_rename_folder');
add_action('wp_ajax_bw_media_delete_folder', 'bw_mf_ajax_delete_folder');
add_action('wp_ajax_bw_media_assign_folder', 'bw_mf_ajax_assign_folder');
add_action('wp_ajax_bw_media_update_folder_meta', 'bw_mf_ajax_update_folder_meta');
add_action('wp_ajax_bw_mf_set_folder_color', 'bw_mf_ajax_set_folder_color');
add_action('wp_ajax_bw_mf_reset_folder_color', 'bw_mf_ajax_reset_folder_color');
add_action('wp_ajax_bw_mf_toggle_folder_pin', 'bw_mf_ajax_toggle_folder_pin');
add_action('wp_ajax_bw_mf_get_corner_markers', 'bw_mf_ajax_get_corner_markers');

if (!function_exists('bw_mf_ajax_error')) {
    function bw_mf_ajax_error($message, $code = 400)
    {
        wp_send_json_error([
            'message' => $message,
        ], $code);
    }
}

if (!function_exists('bw_mf_assert_upload_context')) {
    function bw_mf_assert_upload_context()
    {
        $context = isset($_POST['bw_mf_context']) ? sanitize_key(wp_unslash($_POST['bw_mf_context'])) : '';
        if ($context !== 'upload') {
            bw_mf_ajax_error(__('Invalid media screen context.', 'bw'), 403);
        }
    }
}

if (!function_exists('bw_mf_user_can_manage_folders')) {
    function bw_mf_user_can_manage_folders()
    {
        return current_user_can('upload_files') && current_user_can('manage_categories');
    }
}

if (!function_exists('bw_mf_ajax_require')) {
    function bw_mf_ajax_require($expected_action, $capability)
    {
        if (!wp_doing_ajax() || !is_admin()) {
            bw_mf_ajax_error(__('Invalid request context.', 'bw'), 400);
        }

        $posted_action = isset($_POST['action']) ? sanitize_key(wp_unslash($_POST['action'])) : '';
        if ($posted_action !== $expected_action) {
            bw_mf_ajax_error(__('Invalid action.', 'bw'), 400);
        }

        if (!check_ajax_referer('bw_media_folders_nonce', 'nonce', false)) {
            bw_mf_ajax_error(__('Invalid nonce.', 'bw'), 403);
        }

        bw_mf_assert_upload_context();

        if (is_array($capability)) {
            foreach ($capability as $cap) {
                if (!current_user_can((string) $cap)) {
                    bw_mf_ajax_error(__('Insufficient permissions.', 'bw'), 403);
                }
            }
            return;
        }

        if ($capability === 'bw_mf_manage_folders') {
            if (!bw_mf_user_can_manage_folders()) {
                bw_mf_ajax_error(__('Insufficient permissions.', 'bw'), 403);
            }
            return;
        }

        if (!current_user_can((string) $capability)) {
            bw_mf_ajax_error(__('Insufficient permissions.', 'bw'), 403);
        }
    }
}

if (!function_exists('bw_mf_normalize_attachment_ids')) {
    function bw_mf_normalize_attachment_ids($raw_ids)
    {
        if (!is_array($raw_ids)) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(array_map('absint', $raw_ids))));
        if (empty($ids)) {
            return [];
        }

        $valid = [];
        foreach ($ids as $id) {
            if (get_post_type($id) === 'attachment') {
                $valid[] = $id;
            }
        }

        return $valid;
    }
}

if (!function_exists('bw_mf_get_unassigned_count')) {
    function bw_mf_get_unassigned_count()
    {
        $query = new WP_Query([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => false,
            'tax_query' => [
                [
                    'taxonomy' => 'bw_media_folder',
                    'operator' => 'NOT EXISTS',
                ],
            ],
        ]);

        return (int) $query->found_posts;
    }
}

if (!function_exists('bw_mf_build_folder_nodes')) {
    function bw_mf_build_folder_nodes()
    {
        $terms = get_terms([
            'taxonomy' => 'bw_media_folder',
            'hide_empty' => false,
            'hierarchical' => true,
        ]);

        if (is_wp_error($terms) || !is_array($terms)) {
            return [];
        }

        $counts_map = bw_mf_get_folder_counts_map($terms);

        $by_parent = [];
        foreach ($terms as $term) {
            $term_id = (int) $term->term_id;
            $parent_id = (int) $term->parent;
            $pinned = (int) get_term_meta($term->term_id, 'bw_mf_pinned', true);
            if ($pinned !== 1) {
                $pinned = (int) get_term_meta($term->term_id, 'bw_pinned', true);
            }

            if (!isset($by_parent[$parent_id])) {
                $by_parent[$parent_id] = [];
            }

            $by_parent[$parent_id][] = [
                'id' => $term_id,
                'name' => $term->name,
                'parent' => $parent_id,
                'count' => isset($counts_map[$term_id]) ? (int) $counts_map[$term_id] : 0,
                'color' => (string) get_term_meta($term->term_id, 'bw_color', true),
                'icon_color' => (string) get_term_meta($term->term_id, 'bw_mf_icon_color', true),
                'pinned' => $pinned ? 1 : 0,
                'sort' => (int) get_term_meta($term->term_id, 'bw_sort', true),
            ];
        }

        $nodes = [];
        $walk = static function ($parent_id) use (&$walk, &$nodes, &$by_parent) {
            if (!isset($by_parent[$parent_id]) || !is_array($by_parent[$parent_id])) {
                return;
            }

            usort($by_parent[$parent_id], static function ($a, $b) {
                if ((int) $a['pinned'] !== (int) $b['pinned']) {
                    return ((int) $b['pinned']) <=> ((int) $a['pinned']);
                }

                return strcasecmp((string) $a['name'], (string) $b['name']);
            });

            foreach ($by_parent[$parent_id] as $node) {
                $nodes[] = $node;
                $walk((int) $node['id']);
            }
        };

        $walk(0);

        return $nodes;
    }
}

if (!function_exists('bw_mf_get_folder_counts_map')) {
    function bw_mf_get_folder_counts_map($terms = null)
    {
        if (!is_array($terms)) {
            $terms = get_terms([
                'taxonomy' => 'bw_media_folder',
                'hide_empty' => false,
                'hierarchical' => true,
            ]);
        }

        if (is_wp_error($terms) || !is_array($terms) || empty($terms)) {
            return [];
        }

        $requested_term_ids = [];
        foreach ($terms as $term) {
            if ($term && isset($term->term_id)) {
                $requested_term_ids[] = (int) $term->term_id;
            }
        }

        if (empty($requested_term_ids)) {
            return [];
        }

        $requested_term_ids = array_values(array_unique($requested_term_ids));

        static $request_cache = null;
        if (null === $request_cache) {
            $request_cache = get_transient(BW_MF_COUNTS_CACHE_KEY);
            if (!is_array($request_cache)) {
                $request_cache = null;
            }
        }

        if (null === $request_cache) {
            $request_cache = bw_mf_get_folder_counts_map_batched($terms);
            if (!is_array($request_cache)) {
                $request_cache = bw_mf_get_folder_counts_map_fallback($terms);
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('[bw-media-folders] Falling back to per-term count queries.');
                }
            }

            if (is_array($request_cache)) {
                set_transient(BW_MF_COUNTS_CACHE_KEY, $request_cache, BW_MF_COUNTS_CACHE_TTL);
            }
        }

        $counts = [];
        foreach ($requested_term_ids as $term_id) {
            $counts[$term_id] = isset($request_cache[$term_id]) ? (int) $request_cache[$term_id] : 0;
        }

        return $counts;
    }
}

if (!function_exists('bw_mf_get_folder_counts_map_batched')) {
    function bw_mf_get_folder_counts_map_batched($terms)
    {
        global $wpdb;

        if (!is_array($terms) || empty($terms)) {
            return [];
        }

        $term_ids = [];
        $parent_map = [];

        foreach ($terms as $term) {
            if (!$term || !isset($term->term_id)) {
                continue;
            }

            $term_id = (int) $term->term_id;
            $parent_id = isset($term->parent) ? (int) $term->parent : 0;

            $term_ids[$term_id] = $term_id;
            $parent_map[$term_id] = $parent_id;

        }

        if (empty($term_ids)) {
            return [];
        }

        $tt_rows = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT term_taxonomy_id, term_id
                FROM {$wpdb->term_taxonomy}
                WHERE taxonomy = %s
                ",
                'bw_media_folder'
            ),
            ARRAY_A
        );

        if (!is_array($tt_rows) || empty($tt_rows)) {
            return array_fill_keys(array_values($term_ids), 0);
        }

        $term_taxonomy_to_term = [];
        foreach ($tt_rows as $row) {
            if (!isset($row['term_taxonomy_id'], $row['term_id'])) {
                continue;
            }

            $mapped_term_id = (int) $row['term_id'];
            if (!isset($term_ids[$mapped_term_id])) {
                continue;
            }

            $term_taxonomy_to_term[(int) $row['term_taxonomy_id']] = $mapped_term_id;
        }

        if (empty($term_taxonomy_to_term)) {
            return array_fill_keys(array_values($term_ids), 0);
        }

        $requested_tt_ids = array_values(array_unique(array_map('absint', array_keys($term_taxonomy_to_term))));
        if (empty($requested_tt_ids)) {
            return array_fill_keys(array_values($term_ids), 0);
        }

        $tt_id_placeholders = implode(',', array_fill(0, count($requested_tt_ids), '%d'));

        // Batch read all assignment relationships once, then fan out to ancestor terms in PHP.
        $sql = "
                SELECT DISTINCT tr.object_id, tr.term_taxonomy_id
                FROM {$wpdb->term_relationships} AS tr
                INNER JOIN {$wpdb->posts} AS p ON p.ID = tr.object_id
                INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
                WHERE tt.taxonomy = %s
                  AND p.post_type = %s
                  AND p.post_status = %s
                  AND tr.term_taxonomy_id IN ({$tt_id_placeholders})
                ORDER BY tr.object_id ASC
                ";

        $prepare_args = array_merge([$sql, 'bw_media_folder', 'attachment', 'inherit'], $requested_tt_ids);
        $prepared_sql = call_user_func_array([$wpdb, 'prepare'], $prepare_args);
        $rows = $wpdb->get_results($prepared_sql, ARRAY_A);

        if (!is_array($rows)) {
            return false;
        }

        $ancestors_map = [];
        foreach ($term_ids as $term_id) {
            $ancestors = [$term_id => true];
            $cursor = isset($parent_map[$term_id]) ? (int) $parent_map[$term_id] : 0;
            $guard = 0;
            while ($cursor > 0 && isset($term_ids[$cursor]) && $guard < 50) {
                $ancestors[$cursor] = true;
                $cursor = isset($parent_map[$cursor]) ? (int) $parent_map[$cursor] : 0;
                $guard++;
            }

            $ancestors_map[$term_id] = array_keys($ancestors);
        }

        $counts = array_fill_keys(array_values($term_ids), 0);
        $current_object_id = 0;
        $current_ancestors = [];

        $flush_current_object = static function () use (&$counts, &$current_ancestors) {
            if (empty($current_ancestors)) {
                return;
            }

            foreach ($current_ancestors as $ancestor_term_id => $_) {
                if (isset($counts[$ancestor_term_id])) {
                    $counts[$ancestor_term_id]++;
                }
            }
        };

        foreach ($rows as $row) {
            if (!isset($row['object_id'], $row['term_taxonomy_id'])) {
                continue;
            }

            $object_id = (int) $row['object_id'];
            $tt_id = (int) $row['term_taxonomy_id'];

            if (!isset($term_taxonomy_to_term[$tt_id])) {
                continue;
            }

            if ($current_object_id > 0 && $object_id !== $current_object_id) {
                $flush_current_object();
                $current_ancestors = [];
            }

            $current_object_id = $object_id;
            $direct_term_id = $term_taxonomy_to_term[$tt_id];

            if (!isset($ancestors_map[$direct_term_id])) {
                continue;
            }

            foreach ($ancestors_map[$direct_term_id] as $ancestor_term_id) {
                $current_ancestors[(int) $ancestor_term_id] = true;
            }
        }

        $flush_current_object();

        return $counts;
    }
}

if (!function_exists('bw_mf_get_folder_counts_map_fallback')) {
    function bw_mf_get_folder_counts_map_fallback($terms)
    {
        $counts = [];
        foreach ($terms as $term) {
            if (!$term || !isset($term->term_id)) {
                continue;
            }

            $term_id = (int) $term->term_id;
            $query = new WP_Query([
                'post_type' => 'attachment',
                'post_status' => 'inherit',
                'posts_per_page' => 1,
                'fields' => 'ids',
                'no_found_rows' => false,
                'tax_query' => [
                    [
                        'taxonomy' => 'bw_media_folder',
                        'field' => 'term_id',
                        'terms' => [$term_id],
                        'include_children' => true,
                    ],
                ],
            ]);

            $counts[$term_id] = (int) $query->found_posts;
        }

        return $counts;
    }
}

if (!function_exists('bw_mf_invalidate_folder_counts_cache')) {
    function bw_mf_invalidate_folder_counts_cache()
    {
        delete_transient(BW_MF_COUNTS_CACHE_KEY);
    }
}

if (!function_exists('bw_mf_invalidate_folder_counts_cache_on_set_terms')) {
    function bw_mf_invalidate_folder_counts_cache_on_set_terms($object_id, $terms, $tt_ids, $taxonomy)
    {
        if ($taxonomy !== 'bw_media_folder') {
            return;
        }

        bw_mf_invalidate_folder_counts_cache();
    }
}
add_action('set_object_terms', 'bw_mf_invalidate_folder_counts_cache_on_set_terms', 10, 4);
add_action('created_bw_media_folder', 'bw_mf_invalidate_folder_counts_cache');
add_action('edited_bw_media_folder', 'bw_mf_invalidate_folder_counts_cache');
add_action('delete_bw_media_folder', 'bw_mf_invalidate_folder_counts_cache');

if (!function_exists('bw_mf_get_folder_term_or_error')) {
    function bw_mf_get_folder_term_or_error($term_id)
    {
        $term = get_term($term_id, 'bw_media_folder');
        if (!$term || is_wp_error($term)) {
            bw_mf_ajax_error(__('Folder not found.', 'bw'), 404);
        }

        return $term;
    }
}

if (!function_exists('bw_mf_ajax_get_folders_tree')) {
    function bw_mf_ajax_get_folders_tree()
    {
        bw_mf_ajax_require('bw_media_get_folders_tree', 'upload_files');

        $all_count = wp_count_posts('attachment');
        $all_files = isset($all_count->inherit) ? (int) $all_count->inherit : 0;

        wp_send_json_success([
            'folders' => bw_mf_build_folder_nodes(),
            'counts' => [
                'all' => $all_files,
                'unassigned' => bw_mf_get_unassigned_count(),
            ],
        ]);
    }
}

if (!function_exists('bw_mf_ajax_get_folder_counts')) {
    function bw_mf_ajax_get_folder_counts()
    {
        bw_mf_ajax_require('bw_media_get_folder_counts', 'upload_files');

        $terms = get_terms([
            'taxonomy' => 'bw_media_folder',
            'hide_empty' => false,
            'hierarchical' => true,
        ]);

        $all_count = wp_count_posts('attachment');
        $all_files = isset($all_count->inherit) ? (int) $all_count->inherit : 0;

        wp_send_json_success([
            'folder_counts' => bw_mf_get_folder_counts_map($terms),
            'counts' => [
                'all' => $all_files,
                'unassigned' => bw_mf_get_unassigned_count(),
            ],
        ]);
    }
}

if (!function_exists('bw_mf_ajax_create_folder')) {
    function bw_mf_ajax_create_folder()
    {
        bw_mf_ajax_require('bw_media_create_folder', 'bw_mf_manage_folders');

        $name = isset($_POST['name']) ? sanitize_text_field((string) $_POST['name']) : '';
        $name = trim($name);
        $parent = isset($_POST['parent']) ? absint($_POST['parent']) : 0;

        if ($name === '') {
            bw_mf_ajax_error(__('Folder name is required.', 'bw'));
        }

        if ($parent > 0) {
            bw_mf_get_folder_term_or_error($parent);
        }

        $created = wp_insert_term($name, 'bw_media_folder', [
            'parent' => $parent,
        ]);

        if (is_wp_error($created)) {
            bw_mf_ajax_error($created->get_error_message(), 400);
        }

        wp_send_json_success([
            'term_id' => (int) $created['term_id'],
            'message' => __('Folder created.', 'bw'),
        ]);
    }
}

if (!function_exists('bw_mf_ajax_rename_folder')) {
    function bw_mf_ajax_rename_folder()
    {
        bw_mf_ajax_require('bw_media_rename_folder', 'bw_mf_manage_folders');

        $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field((string) $_POST['name']) : '';
        $name = trim($name);

        if ($term_id <= 0 || $name === '') {
            bw_mf_ajax_error(__('Invalid folder data.', 'bw'));
        }

        bw_mf_get_folder_term_or_error($term_id);

        $updated = wp_update_term($term_id, 'bw_media_folder', [
            'name' => $name,
        ]);

        if (is_wp_error($updated)) {
            bw_mf_ajax_error($updated->get_error_message(), 400);
        }

        wp_send_json_success([
            'term_id' => $term_id,
            'message' => __('Folder renamed.', 'bw'),
        ]);
    }
}

if (!function_exists('bw_mf_ajax_delete_folder')) {
    function bw_mf_ajax_delete_folder()
    {
        bw_mf_ajax_require('bw_media_delete_folder', 'bw_mf_manage_folders');

        $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
        if ($term_id <= 0) {
            bw_mf_ajax_error(__('Invalid folder.', 'bw'));
        }

        bw_mf_get_folder_term_or_error($term_id);

        $deleted = wp_delete_term($term_id, 'bw_media_folder');
        if (is_wp_error($deleted) || !$deleted) {
            bw_mf_ajax_error(__('Unable to delete folder.', 'bw'), 400);
        }

        wp_send_json_success([
            'term_id' => $term_id,
            'message' => __('Folder deleted.', 'bw'),
        ]);
    }
}

if (!function_exists('bw_mf_ajax_assign_folder')) {
    function bw_mf_ajax_assign_folder()
    {
        bw_mf_ajax_require('bw_media_assign_folder', 'upload_files');

        $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
        if ($term_id <= 0 && isset($_POST['folder_id'])) {
            $term_id = absint($_POST['folder_id']);
        }
        $attachment_ids = isset($_POST['attachment_ids']) ? bw_mf_normalize_attachment_ids($_POST['attachment_ids']) : [];

        if (empty($attachment_ids)) {
            bw_mf_ajax_error(__('No media selected.', 'bw'));
        }

        if (count($attachment_ids) > BW_MF_ASSIGN_BATCH_LIMIT) {
            bw_mf_ajax_error(__('Too many media items in one request.', 'bw'), 400);
        }

        if ($term_id > 0) {
            bw_mf_get_folder_term_or_error($term_id);
        }

        foreach ($attachment_ids as $attachment_id) {
            if ($term_id > 0) {
                wp_set_object_terms($attachment_id, [$term_id], 'bw_media_folder', false);
            } else {
                wp_set_object_terms($attachment_id, [], 'bw_media_folder', false);
            }
        }

        wp_send_json_success([
            'folder_id' => $term_id,
            'term_id' => $term_id,
            'assigned_ids' => $attachment_ids,
            'message' => __('Media updated.', 'bw'),
        ]);
    }
}

if (!function_exists('bw_mf_ajax_update_folder_meta')) {
    function bw_mf_ajax_update_folder_meta()
    {
        bw_mf_ajax_require('bw_media_update_folder_meta', 'bw_mf_manage_folders');

        $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
        if ($term_id <= 0) {
            bw_mf_ajax_error(__('Invalid folder.', 'bw'));
        }

        bw_mf_get_folder_term_or_error($term_id);

        $color = isset($_POST['color']) ? bw_mf_sanitize_hex_color($_POST['color']) : '';
        $pinned = isset($_POST['pinned']) ? (!empty($_POST['pinned']) ? 1 : 0) : 0;
        $sort = isset($_POST['sort']) ? absint($_POST['sort']) : 0;

        update_term_meta($term_id, 'bw_color', $color);
        update_term_meta($term_id, 'bw_pinned', $pinned);
        update_term_meta($term_id, 'bw_mf_pinned', $pinned);
        update_term_meta($term_id, 'bw_sort', $sort);

        wp_send_json_success([
            'term_id' => $term_id,
            'message' => __('Folder metadata updated.', 'bw'),
        ]);
    }
}

if (!function_exists('bw_mf_ajax_toggle_folder_pin')) {
    function bw_mf_ajax_toggle_folder_pin()
    {
        bw_mf_ajax_require('bw_mf_toggle_folder_pin', 'bw_mf_manage_folders');

        $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
        $pinned = isset($_POST['pinned']) ? (!empty($_POST['pinned']) ? 1 : 0) : 0;

        if ($term_id <= 0) {
            bw_mf_ajax_error(__('Invalid folder.', 'bw'), 400);
        }

        bw_mf_get_folder_term_or_error($term_id);
        update_term_meta($term_id, 'bw_mf_pinned', $pinned);
        update_term_meta($term_id, 'bw_pinned', $pinned);

        wp_send_json_success([
            'term_id' => $term_id,
            'pinned' => $pinned,
            'message' => __('Folder pin state updated.', 'bw'),
        ]);
    }
}

if (!function_exists('bw_mf_ajax_set_folder_color')) {
    function bw_mf_ajax_set_folder_color()
    {
        bw_mf_ajax_require('bw_mf_set_folder_color', 'bw_mf_manage_folders');

        $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
        $color = isset($_POST['color']) ? bw_mf_sanitize_hex_color($_POST['color']) : '';

        if ($term_id <= 0 || $color === '') {
            bw_mf_ajax_error(__('Invalid folder color data.', 'bw'), 400);
        }

        bw_mf_get_folder_term_or_error($term_id);
        update_term_meta($term_id, 'bw_mf_icon_color', $color);

        wp_send_json_success([
            'term_id' => $term_id,
            'color' => $color,
            'message' => __('Folder icon color updated.', 'bw'),
        ]);
    }
}

if (!function_exists('bw_mf_ajax_reset_folder_color')) {
    function bw_mf_ajax_reset_folder_color()
    {
        bw_mf_ajax_require('bw_mf_reset_folder_color', 'bw_mf_manage_folders');

        $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
        if ($term_id <= 0) {
            bw_mf_ajax_error(__('Invalid folder.', 'bw'), 400);
        }

        bw_mf_get_folder_term_or_error($term_id);
        delete_term_meta($term_id, 'bw_mf_icon_color');

        wp_send_json_success([
            'term_id' => $term_id,
            'message' => __('Folder icon color reset.', 'bw'),
        ]);
    }
}

if (!function_exists('bw_mf_ajax_get_corner_markers')) {
    function bw_mf_ajax_get_corner_markers()
    {
        bw_mf_ajax_require('bw_mf_get_corner_markers', 'upload_files');

        $raw_ids = isset($_POST['attachment_ids']) && is_array($_POST['attachment_ids']) ? $_POST['attachment_ids'] : [];
        $attachment_ids = array_values(array_unique(array_filter(array_map('absint', $raw_ids))));

        if (empty($attachment_ids)) {
            wp_send_json_success([
                'markers' => [],
            ]);
        }

        if (count($attachment_ids) > BW_MF_ASSIGN_BATCH_LIMIT) {
            $attachment_ids = array_slice($attachment_ids, 0, BW_MF_ASSIGN_BATCH_LIMIT);
        }

        $markers = [];
        foreach ($attachment_ids as $attachment_id) {
            $markers[$attachment_id] = [
                'assigned' => false,
                'color' => null,
                'folder_name' => '',
            ];

            $post = get_post($attachment_id);
            if (!$post || $post->post_type !== 'attachment') {
                continue;
            }

            $terms = wp_get_object_terms($attachment_id, 'bw_media_folder');
            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }

            $term = reset($terms);
            if (!$term || empty($term->term_id)) {
                continue;
            }

            $color = bw_mf_sanitize_hex_color((string) get_term_meta((int) $term->term_id, 'bw_mf_icon_color', true));
            $markers[$attachment_id]['assigned'] = true;
            $markers[$attachment_id]['color'] = ($color !== '') ? $color : null;
            $markers[$attachment_id]['folder_name'] = wp_strip_all_tags((string) $term->name);
        }

        wp_send_json_success([
            'markers' => $markers,
        ]);
    }
}
