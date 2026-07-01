<?php
/**
 * Media Folders — AJAX endpoint handlers (folder CRUD, meta, tree/counts).
 *
 * Extracted verbatim from runtime/ajax.php (Phase 3d, BW-TASK-20260629).
 * Function bodies and hook registrations are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_mf_get_folder_term_or_error')) {
    function bw_mf_get_folder_term_or_error($term_id, $taxonomy)
    {
        $term = get_term($term_id, $taxonomy);
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
        $post_type = bw_mf_get_request_post_type();
        $taxonomy = bw_mf_get_request_taxonomy();
        $summary = bw_mf_get_summary_counts($post_type, $taxonomy);

        wp_send_json_success([
            'folders' => bw_mf_build_folder_nodes($post_type, $taxonomy),
            'counts' => [
                'all' => (int) $summary['all'],
                'unassigned' => (int) $summary['unassigned'],
            ],
        ]);
    }
}

if (!function_exists('bw_mf_ajax_get_folder_counts')) {
    function bw_mf_ajax_get_folder_counts()
    {
        bw_mf_ajax_require('bw_media_get_folder_counts', 'upload_files');
        $post_type = bw_mf_get_request_post_type();
        $taxonomy = bw_mf_get_request_taxonomy();

        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'hierarchical' => true,
        ]);

        $summary = bw_mf_get_summary_counts($post_type, $taxonomy);

        wp_send_json_success([
            'folder_counts' => bw_mf_get_folder_counts_map($terms, $post_type, $taxonomy),
            'counts' => [
                'all' => (int) $summary['all'],
                'unassigned' => (int) $summary['unassigned'],
            ],
        ]);
    }
}

if (!function_exists('bw_mf_ajax_create_folder')) {
    function bw_mf_ajax_create_folder()
    {
        bw_mf_ajax_require('bw_media_create_folder', 'bw_mf_manage_folders');
        $taxonomy = bw_mf_get_request_taxonomy();

        $name = isset($_POST['name']) ? sanitize_text_field((string) $_POST['name']) : '';
        $name = trim($name);
        $parent = isset($_POST['parent']) ? absint(wp_unslash((string) $_POST['parent'])) : 0;

        if ($name === '') {
            bw_mf_ajax_error(__('Folder name is required.', 'bw'));
        }

        if ($parent > 0) {
            bw_mf_get_folder_term_or_error($parent, $taxonomy);
        }

        $created = wp_insert_term($name, $taxonomy, [
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
        $taxonomy = bw_mf_get_request_taxonomy();

        $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field((string) $_POST['name']) : '';
        $name = trim($name);

        if ($term_id <= 0 || $name === '') {
            bw_mf_ajax_error(__('Invalid folder data.', 'bw'));
        }

        bw_mf_get_folder_term_or_error($term_id, $taxonomy);

        $updated = wp_update_term($term_id, $taxonomy, [
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
        $taxonomy = bw_mf_get_request_taxonomy();

        $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
        if ($term_id <= 0) {
            bw_mf_ajax_error(__('Invalid folder.', 'bw'));
        }

        bw_mf_get_folder_term_or_error($term_id, $taxonomy);

        $deleted = wp_delete_term($term_id, $taxonomy);
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
        $post_type = bw_mf_get_request_post_type();
        $taxonomy = bw_mf_get_request_taxonomy();

        $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
        if ($term_id <= 0 && isset($_POST['folder_id'])) {
            $term_id = absint($_POST['folder_id']);
        }
        $object_ids = isset($_POST['attachment_ids']) ? bw_mf_normalize_object_ids($_POST['attachment_ids'], $post_type) : [];

        if (empty($object_ids)) {
            bw_mf_ajax_error(__('No items selected.', 'bw'));
        }

        if (count($object_ids) > BW_MF_ASSIGN_BATCH_LIMIT) {
            bw_mf_ajax_error(__('Too many items in one request.', 'bw'), 400);
        }

        if ($term_id > 0) {
            bw_mf_get_folder_term_or_error($term_id, $taxonomy);
        }

        $existing_map = [];
        $existing_rows = wp_get_object_terms($object_ids, $taxonomy, [
            'fields' => 'all_with_object_id',
            'orderby' => 'none',
            'update_term_meta_cache' => false,
        ]);
        if (!is_wp_error($existing_rows) && is_array($existing_rows)) {
            foreach ($existing_rows as $row) {
                if (!isset($row->object_id, $row->term_id)) {
                    continue;
                }
                $oid = (int) $row->object_id;
                if (!isset($existing_map[$oid])) {
                    $existing_map[$oid] = [];
                }
                $existing_map[$oid][] = (int) $row->term_id;
            }
        }

        $assigned_ids = [];
        $duplicate_ids = [];

        bw_mf_suspend_cache_invalidation();
        wp_defer_term_counting(true);
        try {
            foreach ($object_ids as $object_id) {
                $current_terms = isset($existing_map[$object_id]) && is_array($existing_map[$object_id])
                    ? array_values(array_unique(array_map('absint', $existing_map[$object_id])))
                    : [];
                sort($current_terms);

                $next_terms = $term_id > 0 ? [$term_id] : [];
                $next_terms = array_values(array_unique(array_map('absint', $next_terms)));
                sort($next_terms);

                if ($current_terms === $next_terms) {
                    if ($term_id > 0) {
                        $duplicate_ids[] = $object_id;
                    }
                    continue;
                }

                wp_set_object_terms($object_id, $next_terms, $taxonomy, false);
                $assigned_ids[] = $object_id;
            }
        } finally {
            wp_defer_term_counting(false);
            bw_mf_resume_cache_invalidation();
        }

        if (!empty($assigned_ids)) {
            bw_mf_invalidate_folder_counts_cache($post_type, $taxonomy);
        }

        $message = __('Items updated.', 'bw');
        $notice_type = 'updated';
        if (!empty($duplicate_ids) && empty($assigned_ids)) {
            $message = count($duplicate_ids) === 1
                ? __('This item already exists in that folder.', 'bw')
                : __('These items already exist in that folder.', 'bw');
            $notice_type = 'duplicate';
        } elseif (!empty($duplicate_ids)) {
            $message = count($duplicate_ids) === 1
                ? __('Item updated. One selected item already existed in that folder.', 'bw')
                : __('Items updated. Some selected items already existed in that folder.', 'bw');
            $notice_type = 'partial-duplicate';
        }

        wp_send_json_success([
            'folder_id' => $term_id,
            'term_id' => $term_id,
            'assigned_ids' => array_values($assigned_ids),
            'duplicate_ids' => array_values($duplicate_ids),
            'requested_ids' => array_values($object_ids),
            'notice_type' => $notice_type,
            'message' => $message,
        ]);
    }
}

if (!function_exists('bw_mf_ajax_update_folder_meta')) {
    function bw_mf_ajax_update_folder_meta()
    {
        bw_mf_ajax_require('bw_media_update_folder_meta', 'bw_mf_manage_folders');
        $taxonomy = bw_mf_get_request_taxonomy();

        $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
        if ($term_id <= 0) {
            bw_mf_ajax_error(__('Invalid folder.', 'bw'));
        }

        bw_mf_get_folder_term_or_error($term_id, $taxonomy);

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
        $taxonomy = bw_mf_get_request_taxonomy();

        $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
        $pinned = isset($_POST['pinned']) ? (!empty($_POST['pinned']) ? 1 : 0) : 0;

        if ($term_id <= 0) {
            bw_mf_ajax_error(__('Invalid folder.', 'bw'), 400);
        }

        bw_mf_get_folder_term_or_error($term_id, $taxonomy);
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
        $taxonomy = bw_mf_get_request_taxonomy();

        $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
        $color = isset($_POST['color']) ? bw_mf_sanitize_hex_color($_POST['color']) : '';

        if ($term_id <= 0 || $color === '') {
            bw_mf_ajax_error(__('Invalid folder color data.', 'bw'), 400);
        }

        bw_mf_get_folder_term_or_error($term_id, $taxonomy);
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
        $taxonomy = bw_mf_get_request_taxonomy();

        $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
        if ($term_id <= 0) {
            bw_mf_ajax_error(__('Invalid folder.', 'bw'), 400);
        }

        bw_mf_get_folder_term_or_error($term_id, $taxonomy);
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
        $post_type = bw_mf_get_request_post_type();
        $taxonomy = bw_mf_get_request_taxonomy();

        $raw_ids = [];
        if (isset($_POST['object_ids']) && is_array($_POST['object_ids'])) {
            $raw_ids = $_POST['object_ids'];
        } elseif (isset($_POST['attachment_ids']) && is_array($_POST['attachment_ids'])) {
            $raw_ids = $_POST['attachment_ids'];
        }

        $object_ids = bw_mf_normalize_object_ids($raw_ids, $post_type);
        if (empty($object_ids)) {
            wp_send_json_success([
                'markers' => [],
            ]);
        }

        if (count($object_ids) > BW_MF_ASSIGN_BATCH_LIMIT) {
            $object_ids = array_slice($object_ids, 0, BW_MF_ASSIGN_BATCH_LIMIT);
        }

        $markers = [];
        foreach ($object_ids as $object_id) {
            $markers[$object_id] = [
                'assigned' => false,
                'color' => null,
                'folder_name' => '',
            ];
        }

        $terms = wp_get_object_terms($object_ids, $taxonomy, [
            'fields' => 'all_with_object_id',
            'orderby' => 'none',
            'update_term_meta_cache' => false,
        ]);
        if (!is_wp_error($terms) && is_array($terms)) {
            $term_colors = [];
            foreach ($terms as $term) {
                if (!isset($term->object_id, $term->term_id, $term->name)) {
                    continue;
                }

                $object_id = (int) $term->object_id;
                if ($object_id <= 0 || !isset($markers[$object_id]) || !empty($markers[$object_id]['assigned'])) {
                    continue;
                }

                $term_id = (int) $term->term_id;
                if (!isset($term_colors[$term_id])) {
                    $term_colors[$term_id] = bw_mf_sanitize_hex_color((string) get_term_meta($term_id, 'bw_mf_icon_color', true));
                }

                $markers[$object_id]['assigned'] = true;
                $markers[$object_id]['color'] = ($term_colors[$term_id] !== '') ? $term_colors[$term_id] : null;
                $markers[$object_id]['folder_name'] = wp_strip_all_tags((string) $term->name);
            }
        }

        wp_send_json_success([
            'markers' => $markers,
        ]);
    }
}
