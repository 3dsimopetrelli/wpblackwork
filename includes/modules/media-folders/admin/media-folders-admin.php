<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_enqueue_scripts', 'bw_mf_admin_enqueue_assets', 20);
add_action('admin_footer-upload.php', 'bw_mf_render_sidebar_mount', 20);

if (!function_exists('bw_mf_admin_is_upload_screen')) {
    function bw_mf_admin_is_upload_screen()
    {
        if (!is_admin() || !function_exists('get_current_screen')) {
            return false;
        }

        $screen = get_current_screen();
        return $screen && $screen->id === 'upload';
    }
}

if (!function_exists('bw_mf_get_active_filter_payload')) {
    function bw_mf_get_active_filter_payload()
    {
        $mode = isset($_GET['mode']) ? sanitize_key(wp_unslash($_GET['mode'])) : 'list';
        if (!in_array($mode, ['list', 'grid'], true)) {
            $mode = 'list';
        }

        return [
            'folder' => isset($_GET['bw_media_folder']) ? absint(wp_unslash($_GET['bw_media_folder'])) : 0,
            'unassigned' => (isset($_GET['bw_media_unassigned']) && '1' === sanitize_key(wp_unslash($_GET['bw_media_unassigned']))) ? 1 : 0,
            'mode' => $mode,
        ];
    }
}

if (!function_exists('bw_mf_admin_enqueue_assets')) {
    function bw_mf_admin_enqueue_assets($hook_suffix)
    {
        if ($hook_suffix !== 'upload.php' || !bw_mf_admin_is_upload_screen()) {
            return;
        }

        $css_path = __DIR__ . '/assets/media-folders.css';
        $js_path = __DIR__ . '/assets/media-folders.js';
        $corner_enabled = bw_mf_is_corner_indicator_enabled();
        $badge_tooltip_enabled = function_exists('bw_mf_get_badge_tooltip_enabled') ? bw_mf_get_badge_tooltip_enabled() : false;

        $css_version = file_exists($css_path) ? (string) filemtime($css_path) : '1.0.0';
        $js_version = file_exists($js_path) ? (string) filemtime($js_path) : '1.0.0';

        wp_enqueue_style(
            'bw-media-folders',
            plugin_dir_url(__FILE__) . 'assets/media-folders.css',
            [],
            $css_version
        );

        wp_enqueue_script(
            'bw-media-folders',
            plugin_dir_url(__FILE__) . 'assets/media-folders.js',
            ['jquery'],
            $js_version,
            true
        );

        wp_localize_script('bw-media-folders', 'bwMediaFolders', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bw_media_folders_nonce'),
            'active' => bw_mf_get_active_filter_payload(),
            'cornerIndicatorEnabled' => $corner_enabled ? 1 : 0,
            'flags' => [
                'cornerIndicator' => $corner_enabled ? 1 : 0,
            ],
            'badgeTooltipEnabled' => ($corner_enabled && $badge_tooltip_enabled) ? 1 : 0,
            'text' => [
                'newFolderPrompt' => __('Folder name', 'bw'),
                'renamePrompt' => __('Rename folder', 'bw'),
                'createSubPrompt' => __('Subfolder name', 'bw'),
                'confirmDelete' => __('Delete this folder?', 'bw'),
                'selectMedia' => __('Select at least one media item.', 'bw'),
            ],
        ]);
    }
}

if (!function_exists('bw_mf_render_sidebar_mount')) {
    function bw_mf_render_sidebar_mount()
    {
        if (!bw_mf_admin_is_upload_screen()) {
            return;
        }

        include __DIR__ . '/media-folders-sidebar.php';
    }
}
