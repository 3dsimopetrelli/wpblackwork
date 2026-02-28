<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_tbl_template_type_allowed_values')) {
    function bw_tbl_template_type_allowed_values()
    {
        return ['footer'];
    }
}

if (!function_exists('bw_tbl_sanitize_template_type')) {
    function bw_tbl_sanitize_template_type($value)
    {
        $value = sanitize_key((string) $value);
        $allowed = bw_tbl_template_type_allowed_values();

        if (!in_array($value, $allowed, true)) {
            return 'footer';
        }

        return $value;
    }
}

if (!function_exists('bw_tbl_register_template_type_meta')) {
    function bw_tbl_register_template_type_meta()
    {
        register_post_meta(
            'bw_template',
            'bw_template_type',
            [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => 'bw_tbl_sanitize_template_type',
                'auth_callback' => static function () {
                    return current_user_can('manage_options');
                },
            ]
        );
    }
}
add_action('init', 'bw_tbl_register_template_type_meta', 10);

if (!function_exists('bw_tbl_add_template_type_metabox')) {
    function bw_tbl_add_template_type_metabox()
    {
        add_meta_box(
            'bw_tbl_template_type_metabox',
            __('Template Type', 'bw'),
            'bw_tbl_render_template_type_metabox',
            'bw_template',
            'side',
            'default'
        );
    }
}
add_action('add_meta_boxes', 'bw_tbl_add_template_type_metabox');

if (!function_exists('bw_tbl_render_template_type_metabox')) {
    function bw_tbl_render_template_type_metabox($post)
    {
        wp_nonce_field('bw_tbl_template_type_save', 'bw_tbl_template_type_nonce');

        $current = get_post_meta($post->ID, 'bw_template_type', true);
        $current = bw_tbl_sanitize_template_type($current);
        ?>
        <p>
            <label for="bw-template-type-field"><?php esc_html_e('Type', 'bw'); ?></label>
            <select id="bw-template-type-field" name="bw_template_type" class="widefat">
                <option value="footer" <?php selected($current, 'footer'); ?>><?php esc_html_e('Footer', 'bw'); ?></option>
            </select>
        </p>
        <p class="description"><?php esc_html_e('Phase 1 supports only Footer templates.', 'bw'); ?></p>
        <?php
    }
}

if (!function_exists('bw_tbl_save_template_type_metabox')) {
    function bw_tbl_save_template_type_metabox($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!isset($_POST['bw_tbl_template_type_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bw_tbl_template_type_nonce'])), 'bw_tbl_template_type_save')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $post_type = get_post_type($post_id);
        if ('bw_template' !== $post_type) {
            return;
        }

        $raw = isset($_POST['bw_template_type']) ? wp_unslash($_POST['bw_template_type']) : 'footer';
        update_post_meta($post_id, 'bw_template_type', bw_tbl_sanitize_template_type($raw));
    }
}
add_action('save_post_bw_template', 'bw_tbl_save_template_type_metabox');

if (!function_exists('bw_tbl_default_template_type_on_insert')) {
    function bw_tbl_default_template_type_on_insert($post_id, $post, $update)
    {
        if ('bw_template' !== $post->post_type) {
            return;
        }

        if ($update) {
            return;
        }

        if (!metadata_exists('post', $post_id, 'bw_template_type')) {
            update_post_meta($post_id, 'bw_template_type', 'footer');
        }
    }
}
add_action('wp_insert_post', 'bw_tbl_default_template_type_on_insert', 10, 3);
