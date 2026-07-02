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

if (!defined('BW_MF_TREE_CACHE_KEY')) {
    define('BW_MF_TREE_CACHE_KEY', 'bw_mf_folder_tree_v1');
}

if (!defined('BW_MF_TREE_CACHE_TTL')) {
    define('BW_MF_TREE_CACHE_TTL', 180);
}

if (!defined('BW_MF_SUMMARY_CACHE_KEY')) {
    define('BW_MF_SUMMARY_CACHE_KEY', 'bw_mf_folder_summary_v1');
}

if (!defined('BW_MF_CACHE_GROUP')) {
    define('BW_MF_CACHE_GROUP', 'bw_media_folders');
}

// AJAX runtime split into focused files (Phase 3d, BW-TASK-20260629).
// Behavior-preserving: all handler/helper/counts function names and the
// wp_ajax_* registrations below are unchanged.
require_once __DIR__ . '/ajax-helpers.php';
require_once __DIR__ . '/folder-counts.php';
require_once __DIR__ . '/ajax-handlers.php';

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
