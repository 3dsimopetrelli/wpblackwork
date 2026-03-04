<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BW_MF_ASSIGN_BATCH_LIMIT')) {
    define('BW_MF_ASSIGN_BATCH_LIMIT', 200);
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
        $context = isset($_POST['bw_mf_context']) ? sanitize_key((string) $_POST['bw_mf_context']) : '';
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

        $posted_action = isset($_POST['action']) ? sanitize_key((string) $_POST['action']) : '';
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
