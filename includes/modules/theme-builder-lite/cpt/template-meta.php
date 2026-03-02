<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_tbl_template_type_allowed_values')) {
    function bw_tbl_template_type_allowed_values()
    {
        return ['footer', 'single_post', 'single_page', 'search', 'error_404'];
    }
}

if (!function_exists('bw_tbl_rules_allowed_types')) {
    function bw_tbl_rules_allowed_types()
    {
        return ['post_category', 'post_id', 'page_id'];
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

if (!function_exists('bw_tbl_parse_csv_ids')) {
    function bw_tbl_parse_csv_ids($raw)
    {
        $raw = is_string($raw) ? $raw : '';
        if ('' === trim($raw)) {
            return [];
        }

        $parts = preg_split('/[\s,]+/', $raw);
        if (!is_array($parts)) {
            return [];
        }

        $ids = [];
        foreach ($parts as $part) {
            $id = absint($part);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        $ids = array_values($ids);
        sort($ids, SORT_NUMERIC);
        return $ids;
    }
}

if (!function_exists('bw_tbl_parse_rule_rows')) {
    function bw_tbl_parse_rule_rows($rows)
    {
        $rows = is_array($rows) ? $rows : [];
        $allowed_types = bw_tbl_rules_allowed_types();
        $rules = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $type = isset($row['type']) ? sanitize_key((string) $row['type']) : '';
            if (!in_array($type, $allowed_types, true)) {
                continue;
            }

            $values = isset($row['values']) ? bw_tbl_parse_csv_ids((string) $row['values']) : [];
            if (empty($values)) {
                continue;
            }

            if ('post_category' === $type) {
                $rules[] = [
                    'type' => 'post_category',
                    'terms' => $values,
                ];
                continue;
            }

            $rules[] = [
                'type' => $type,
                'ids' => $values,
            ];
        }

        return $rules;
    }
}

if (!function_exists('bw_tbl_normalize_saved_rule_for_ui')) {
    function bw_tbl_normalize_saved_rule_for_ui($rule)
    {
        if (!is_array($rule)) {
            return null;
        }

        $type = isset($rule['type']) ? sanitize_key((string) $rule['type']) : '';
        if (!in_array($type, bw_tbl_rules_allowed_types(), true)) {
            return null;
        }

        $values = [];
        if ('post_category' === $type) {
            $values = isset($rule['terms']) && is_array($rule['terms']) ? $rule['terms'] : [];
        } else {
            $values = isset($rule['ids']) && is_array($rule['ids']) ? $rule['ids'] : [];
        }

        $values = array_filter(array_map('absint', $values));
        if (empty($values)) {
            return null;
        }

        $values = array_values(array_unique($values));
        sort($values, SORT_NUMERIC);

        return [
            'type' => $type,
            'values' => implode(',', $values),
        ];
    }
}

if (!function_exists('bw_tbl_get_rules_for_ui')) {
    function bw_tbl_get_rules_for_ui($post_id, $section)
    {
        $raw = get_post_meta($post_id, 'bw_tbl_display_rules_v1', true);
        $raw = is_array($raw) ? $raw : [];
        $rows = isset($raw[$section]) && is_array($raw[$section]) ? $raw[$section] : [];

        $ui_rows = [];
        foreach ($rows as $row) {
            $normalized = bw_tbl_normalize_saved_rule_for_ui($row);
            if (is_array($normalized)) {
                $ui_rows[] = $normalized;
            }
        }

        while (count($ui_rows) < 3) {
            $ui_rows[] = [
                'type' => '',
                'values' => '',
            ];
        }

        return array_slice($ui_rows, 0, 3);
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

        add_meta_box(
            'bw_tbl_template_rules_metabox',
            __('Display Rules', 'bw'),
            'bw_tbl_render_template_rules_metabox',
            'bw_template',
            'normal',
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
                <option value="single_post" <?php selected($current, 'single_post'); ?>><?php esc_html_e('Single Post', 'bw'); ?></option>
                <option value="single_page" <?php selected($current, 'single_page'); ?>><?php esc_html_e('Single Page', 'bw'); ?></option>
                <option value="search" <?php selected($current, 'search'); ?>><?php esc_html_e('Search Results', 'bw'); ?></option>
                <option value="error_404" <?php selected($current, 'error_404'); ?>><?php esc_html_e('Error 404', 'bw'); ?></option>
            </select>
        </p>
        <p class="description"><?php esc_html_e('Phase 2 Step 1 supports Footer, Single Post, Single Page, Search Results, and Error 404.', 'bw'); ?></p>
        <?php
    }
}

if (!function_exists('bw_tbl_render_rules_rows')) {
    function bw_tbl_render_rules_rows($section, $rows)
    {
        $section = 'exclude' === $section ? 'exclude' : 'include';
        $rows = is_array($rows) ? $rows : [];
        $allowed_types = bw_tbl_rules_allowed_types();
        ?>
        <table class="widefat striped" style="margin-top:6px;">
            <thead>
                <tr>
                    <th style="width:180px;"><?php esc_html_e('Rule Type', 'bw'); ?></th>
                    <th><?php esc_html_e('Values (comma-separated IDs)', 'bw'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $index => $row) : ?>
                    <?php
                    $type = isset($row['type']) ? sanitize_key((string) $row['type']) : '';
                    $values = isset($row['values']) ? (string) $row['values'] : '';
                    ?>
                    <tr>
                        <td>
                            <select name="bw_tbl_display_rules[<?php echo esc_attr($section); ?>][<?php echo esc_attr((string) $index); ?>][type]">
                                <option value=""><?php esc_html_e('No rule', 'bw'); ?></option>
                                <?php foreach ($allowed_types as $allowed_type) : ?>
                                    <option value="<?php echo esc_attr($allowed_type); ?>" <?php selected($type, $allowed_type); ?>>
                                        <?php echo esc_html($allowed_type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input
                                type="text"
                                class="widefat"
                                name="bw_tbl_display_rules[<?php echo esc_attr($section); ?>][<?php echo esc_attr((string) $index); ?>][values]"
                                value="<?php echo esc_attr($values); ?>"
                                placeholder="<?php esc_attr_e('e.g. 12,34,56', 'bw'); ?>"
                            />
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
}

if (!function_exists('bw_tbl_render_template_rules_metabox')) {
    function bw_tbl_render_template_rules_metabox($post)
    {
        wp_nonce_field('bw_tbl_template_rules_save', 'bw_tbl_template_rules_nonce');

        $priority = get_post_meta($post->ID, 'bw_template_priority', true);
        $priority = is_numeric($priority) ? (int) $priority : 10;
        if ($priority < 0 || $priority > 999) {
            $priority = 10;
        }

        $include_rows = bw_tbl_get_rules_for_ui($post->ID, 'include');
        $exclude_rows = bw_tbl_get_rules_for_ui($post->ID, 'exclude');
        ?>
        <p>
            <label for="bw-template-priority-field"><strong><?php esc_html_e('Priority', 'bw'); ?></strong></label><br />
            <input id="bw-template-priority-field" type="number" min="0" max="999" step="1" name="bw_template_priority" value="<?php echo esc_attr((string) $priority); ?>" />
            <span class="description"><?php esc_html_e('Higher priority wins. Tie-break: lower template ID.', 'bw'); ?></span>
        </p>

        <hr />

        <p><strong><?php esc_html_e('Include Rules', 'bw'); ?></strong></p>
        <?php bw_tbl_render_rules_rows('include', $include_rows); ?>

        <p style="margin-top:14px;"><strong><?php esc_html_e('Exclude Rules', 'bw'); ?></strong></p>
        <?php bw_tbl_render_rules_rows('exclude', $exclude_rows); ?>

        <p class="description" style="margin-top:12px;">
            <?php esc_html_e('For search and 404 template types, rules are not required. Empty include/exclude means match-all within the selected template type.', 'bw'); ?>
        </p>
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

if (!function_exists('bw_tbl_save_template_rules_metabox')) {
    function bw_tbl_save_template_rules_metabox($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!isset($_POST['bw_tbl_template_rules_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bw_tbl_template_rules_nonce'])), 'bw_tbl_template_rules_save')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if ('bw_template' !== get_post_type($post_id)) {
            return;
        }

        $priority = isset($_POST['bw_template_priority']) ? (int) wp_unslash($_POST['bw_template_priority']) : 10;
        if ($priority < 0) {
            $priority = 0;
        }
        if ($priority > 999) {
            $priority = 999;
        }
        update_post_meta($post_id, 'bw_template_priority', $priority);

        $raw_rules = isset($_POST['bw_tbl_display_rules']) && is_array($_POST['bw_tbl_display_rules']) ? wp_unslash($_POST['bw_tbl_display_rules']) : [];
        $include_raw = isset($raw_rules['include']) && is_array($raw_rules['include']) ? $raw_rules['include'] : [];
        $exclude_raw = isset($raw_rules['exclude']) && is_array($raw_rules['exclude']) ? $raw_rules['exclude'] : [];

        $normalized_rules = [
            'include' => bw_tbl_parse_rule_rows($include_raw),
            'exclude' => bw_tbl_parse_rule_rows($exclude_raw),
        ];

        update_post_meta($post_id, 'bw_tbl_display_rules_v1', $normalized_rules);
    }
}
add_action('save_post_bw_template', 'bw_tbl_save_template_rules_metabox');

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
