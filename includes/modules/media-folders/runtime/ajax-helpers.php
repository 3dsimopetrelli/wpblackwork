<?php
/**
 * Media Folders — AJAX request / permission / normalization helpers.
 *
 * Extracted verbatim from runtime/ajax.php (Phase 3d, BW-TASK-20260629).
 * Function bodies and hook registrations are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_mf_ajax_error')) {
    function bw_mf_ajax_error($message, $code = 400)
    {
        wp_send_json_error([
            'message' => $message,
        ], $code);
    }
}

if (!function_exists('bw_mf_get_request_post_type')) {
    function bw_mf_get_request_post_type()
    {
        $context = isset($_POST['bw_mf_context']) ? sanitize_key(wp_unslash($_POST['bw_mf_context'])) : '';
        if ($context === '') {
            bw_mf_ajax_error(__('Invalid media screen context.', 'bw'), 403);
        }

        $post_type = bw_mf_get_post_type_for_context($context);
        if ($post_type === '') {
            bw_mf_ajax_error(__('Invalid media screen context.', 'bw'), 403);
        }

        if (!bw_mf_is_post_type_enabled($post_type)) {
            bw_mf_ajax_error(__('Invalid media screen context.', 'bw'), 403);
        }

        return $post_type;
    }
}

if (!function_exists('bw_mf_get_request_taxonomy')) {
    function bw_mf_get_request_taxonomy()
    {
        $post_type = bw_mf_get_request_post_type();
        $taxonomy = bw_mf_get_taxonomy_for_post_type($post_type);
        if ($taxonomy === '') {
            bw_mf_ajax_error(__('Invalid folder taxonomy context.', 'bw'), 403);
        }

        return $taxonomy;
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

        bw_mf_get_request_post_type();

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

if (!function_exists('bw_mf_normalize_object_ids')) {
    function bw_mf_normalize_object_ids($raw_ids, $post_type)
    {
        if (!is_array($raw_ids)) {
            return [];
        }

        $post_type = sanitize_key((string) $post_type);
        if ($post_type === '') {
            return [];
        }

        $ids = array_values(array_unique(array_filter(array_map('absint', $raw_ids))));
        if (empty($ids)) {
            return [];
        }

        $validated_ids = get_posts([
            'post_type' => $post_type,
            'post__in' => $ids,
            'post_status' => ($post_type === 'attachment') ? 'inherit' : 'any',
            'post_status__not_in' => ($post_type === 'attachment') ? [] : ['trash', 'auto-draft'],
            'fields' => 'ids',
            'posts_per_page' => count($ids),
            'orderby' => 'post__in',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'cache_results' => true,
        ]);

        if (!is_array($validated_ids) || empty($validated_ids)) {
            return [];
        }

        return array_values(array_unique(array_map('absint', $validated_ids)));
    }
}
