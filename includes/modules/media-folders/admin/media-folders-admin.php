<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_enqueue_scripts', 'bw_mf_admin_enqueue_assets', 20);
add_action('admin_footer-upload.php', 'bw_mf_render_sidebar_mount', 20);
add_action('admin_footer-edit.php', 'bw_mf_render_sidebar_mount', 20);
add_action('current_screen', 'bw_mf_register_list_table_drag_column', 20);
add_filter('woocommerce_product_table_thumbnail_size', 'bw_mf_filter_product_admin_thumbnail_size', 20);
add_filter('woocommerce_admin_product_list_table_image_size', 'bw_mf_filter_product_admin_thumbnail_size', 20);
add_filter('woocommerce_product_list_table_thumbnail_size', 'bw_mf_filter_product_admin_thumbnail_size', 20);

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

if (!function_exists('bw_mf_is_product_list_screen')) {
    function bw_mf_is_product_list_screen()
    {
        if (!is_admin() || !function_exists('get_current_screen')) {
            return false;
        }

        $screen = get_current_screen();
        if (!$screen) {
            return false;
        }

        return $screen->base === 'edit' && $screen->post_type === 'product';
    }
}

if (!function_exists('bw_mf_get_product_admin_thumbnail_size')) {
    function bw_mf_get_product_admin_thumbnail_size()
    {
        $default = defined('BW_MF_PRODUCT_ADMIN_THUMB_SIZE') ? absint(BW_MF_PRODUCT_ADMIN_THUMB_SIZE) : 200;
        if ($default <= 0) {
            $default = 200;
        }

        $size = apply_filters('bw_mf_product_admin_thumbnail_size', $default);
        $size = absint($size);
        if ($size <= 0) {
            $size = $default;
        }

        return $size;
    }
}

if (!function_exists('bw_mf_filter_product_admin_thumbnail_size')) {
    function bw_mf_filter_product_admin_thumbnail_size($size)
    {
        if (!bw_mf_is_product_list_screen()) {
            return $size;
        }

        if (!bw_mf_is_post_type_enabled('product')) {
            return $size;
        }

        $thumb_size = bw_mf_get_product_admin_thumbnail_size();
        return [$thumb_size, $thumb_size];
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
        $taxonomy = bw_mf_get_taxonomy_for_post_type($post_type);
        if ($taxonomy === '') {
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
            'active' => bw_mf_get_active_filter_payload($post_type),
            'postType' => $post_type,
            'taxonomy' => $taxonomy,
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

if (!function_exists('bw_mf_register_list_table_drag_column')) {
    function bw_mf_register_list_table_drag_column()
    {
        if (!bw_mf_admin_is_supported_list_screen()) {
            return;
        }

        $post_type = bw_mf_get_current_screen_post_type();
        if ($post_type === '' || $post_type === 'attachment') {
            return;
        }

        add_filter("manage_{$post_type}_posts_columns", 'bw_mf_add_drag_handle_column', 999);
        add_filter("manage_edit-{$post_type}_columns", 'bw_mf_add_drag_handle_column', 999);
        add_action("manage_{$post_type}_posts_custom_column", 'bw_mf_render_drag_handle_column', 10, 2);
    }
}

if (!function_exists('bw_mf_add_drag_handle_column')) {
    function bw_mf_add_drag_handle_column($columns)
    {
        if (!is_array($columns)) {
            return $columns;
        }

        $post_type = bw_mf_get_current_screen_post_type();
        $priority_keys = $post_type === 'product'
            ? ['name', 'title', 'cb']
            : ['title', 'cb'];

        if (isset($columns['bw_mf_drag_handle'])) {
            unset($columns['bw_mf_drag_handle']);
        }

        $result = [];
        $inserted = false;
        foreach ($columns as $key => $label) {
            if (!$inserted && in_array($key, $priority_keys, true)) {
                $result['bw_mf_drag_handle'] = '';
                $inserted = true;
            }
            $result[$key] = $label;
        }

        if (!$inserted) {
            // No safe anchor found: preserve original columns unchanged (never append at end).
            return $columns;
        }

        return $result;
    }
}

if (!function_exists('bw_mf_render_drag_handle_column')) {
    function bw_mf_render_drag_handle_column($column_name, $post_id)
    {
        if ($column_name !== 'bw_mf_drag_handle') {
            return;
        }

        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        $title = get_the_title($post_id);
        if ($title === '') {
            $title = __('(no title)', 'bw');
        }

        printf(
            '<button type="button" class="bw-mf-row-drag-handle" draggable="true" data-post-id="%d" data-drag-title="%s" aria-label="%s"><span class="dashicons dashicons-move" aria-hidden="true"></span></button>',
            (int) $post_id,
            esc_attr($title),
            esc_attr__('Drag item to folder', 'bw')
        );
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
