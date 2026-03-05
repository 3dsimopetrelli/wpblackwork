<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_enqueue_scripts', 'bw_mf_admin_enqueue_assets', 20);
add_action('admin_footer-upload.php', 'bw_mf_render_sidebar_mount', 20);
add_action('admin_footer-edit.php', 'bw_mf_render_sidebar_mount', 20);

if (!function_exists('bw_mf_get_current_screen_post_type')) {
    function bw_mf_get_current_screen_post_type()
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

if (!function_exists('bw_mf_admin_is_supported_list_screen')) {
    function bw_mf_admin_is_supported_list_screen()
    {
        $post_type = bw_mf_get_current_screen_post_type();
        if ($post_type === '') {
            return false;
        }

        return bw_mf_is_post_type_enabled($post_type);
    }
}

if (!function_exists('bw_mf_get_active_filter_payload')) {
    function bw_mf_get_active_filter_payload($post_type)
    {
        $mode = isset($_GET['mode']) ? sanitize_key(wp_unslash($_GET['mode'])) : 'list';
        if ($post_type !== 'attachment' || !in_array($mode, ['list', 'grid'], true)) {
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
        if (!in_array($hook_suffix, ['upload.php', 'edit.php'], true)) {
            return;
        }

        if (!bw_mf_admin_is_supported_list_screen()) {
            return;
        }

        $post_type = bw_mf_get_current_screen_post_type();
        if ($post_type === '') {
            return;
        }

        $screen_context = bw_mf_get_context_for_post_type($post_type);
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
            'active' => bw_mf_get_active_filter_payload($post_type),
            'postType' => $post_type,
            'screenContext' => $screen_context,
            'cornerIndicatorEnabled' => ($post_type === 'attachment' && $corner_enabled) ? 1 : 0,
            'flags' => [
                'cornerIndicator' => ($post_type === 'attachment' && $corner_enabled) ? 1 : 0,
            ],
            'badgeTooltipEnabled' => ($post_type === 'attachment' && $corner_enabled && $badge_tooltip_enabled) ? 1 : 0,
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
        if (!bw_mf_admin_is_supported_list_screen()) {
            return;
        }

        include __DIR__ . '/media-folders-sidebar.php';
    }
}
