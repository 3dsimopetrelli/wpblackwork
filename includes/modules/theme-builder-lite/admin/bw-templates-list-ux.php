<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_tbl_admin_template_type_label')) {
    function bw_tbl_admin_template_type_label($type)
    {
        $type = sanitize_key((string) $type);
        $labels = [
            'footer' => __('Footer', 'bw'),
            'single_post' => __('Single Post', 'bw'),
            'single_page' => __('Single Page', 'bw'),
            'single_product' => __('Single Product', 'bw'),
            'product_archive' => __('Product Archive', 'bw'),
            'archive' => __('Archive', 'bw'),
            'search' => __('Search', 'bw'),
            'error_404' => __('Error 404', 'bw'),
        ];

        return isset($labels[$type]) ? $labels[$type] : __('Unknown', 'bw');
    }
}

if (!function_exists('bw_tbl_admin_allowed_template_types')) {
    function bw_tbl_admin_allowed_template_types()
    {
        if (function_exists('bw_tbl_template_type_allowed_values')) {
            $types = bw_tbl_template_type_allowed_values();
            return is_array($types) ? array_values($types) : [];
        }

        return ['footer', 'single_post', 'single_page', 'single_product', 'product_archive', 'archive', 'search', 'error_404'];
    }
}

if (!function_exists('bw_tbl_filter_parent_product_cat_ids')) {
    function bw_tbl_filter_parent_product_cat_ids($term_ids)
    {
        $term_ids = is_array($term_ids) ? $term_ids : [];
        if (empty($term_ids)) {
            return [];
        }

        $valid = [];
        foreach ($term_ids as $term_id) {
            $term_id = absint($term_id);
            if ($term_id <= 0) {
                continue;
            }

            $term = get_term($term_id, 'product_cat');
            if (!($term instanceof WP_Term) || is_wp_error($term)) {
                continue;
            }

            if ((int) $term->parent !== 0) {
                continue;
            }

            $valid[$term_id] = $term_id;
        }

        $valid = array_values($valid);
        sort($valid, SORT_NUMERIC);
        return $valid;
    }
}

if (!function_exists('bw_tbl_admin_format_terms')) {
    function bw_tbl_admin_format_terms($taxonomy, $term_ids)
    {
        $taxonomy = sanitize_key((string) $taxonomy);
        $term_ids = is_array($term_ids) ? $term_ids : [];
        if ('product_cat' === $taxonomy) {
            $term_ids = bw_tbl_filter_parent_product_cat_ids($term_ids);
        }
        if (empty($term_ids)) {
            return '';
        }

        $names = [];
        foreach ($term_ids as $term_id) {
            $term_id = absint($term_id);
            if ($term_id <= 0) {
                continue;
            }

            $term = get_term($term_id, $taxonomy);
            if ($term instanceof WP_Term && !is_wp_error($term)) {
                $names[] = $term->name;
            }
        }

        if (empty($names)) {
            return '';
        }

        return implode(', ', $names);
    }
}

if (!function_exists('bw_tbl_admin_rules_part_to_text')) {
    function bw_tbl_admin_rules_part_to_text($rules, $section)
    {
        $rules = is_array($rules) ? $rules : [];
        $section = 'exclude' === $section ? 'exclude' : 'include';
        $chunks = [];

        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $type = isset($rule['type']) ? sanitize_key((string) $rule['type']) : '';
            if ('' === $type) {
                continue;
            }

            if ('product_archive_shop' === $type) {
                $chunks[] = __('Shop', 'bw');
                continue;
            }

            if ('product_category' === $type || 'product_archive_category' === $type) {
                $terms = isset($rule['terms']) ? $rule['terms'] : [];
                $names = bw_tbl_admin_format_terms('product_cat', $terms);
                if ('' !== $names) {
                    $chunks[] = sprintf(__('In Product Category: %s', 'bw'), $names);
                }
                continue;
            }

            if ('product_archive_tag' === $type) {
                $terms = isset($rule['terms']) ? $rule['terms'] : [];
                $names = bw_tbl_admin_format_terms('product_tag', $terms);
                if ('' !== $names) {
                    $chunks[] = sprintf(__('In Product Tag: %s', 'bw'), $names);
                }
                continue;
            }

            if ('post_category' === $type || 'archive_category' === $type) {
                $terms = isset($rule['terms']) ? $rule['terms'] : [];
                $names = bw_tbl_admin_format_terms('category', $terms);
                if ('' !== $names) {
                    $chunks[] = sprintf(__('In Category: %s', 'bw'), $names);
                }
                continue;
            }

            if ('archive_tag' === $type) {
                $terms = isset($rule['terms']) ? $rule['terms'] : [];
                $names = bw_tbl_admin_format_terms('post_tag', $terms);
                if ('' !== $names) {
                    $chunks[] = sprintf(__('In Tag: %s', 'bw'), $names);
                }
                continue;
            }
        }

        $chunks = array_values(array_unique(array_filter($chunks)));
        if (empty($chunks)) {
            return 'include' === $section ? __('All (within type)', 'bw') : '';
        }

        return implode(', ', $chunks);
    }
}

if (!function_exists('bw_tbl_admin_rules_summary')) {
    function bw_tbl_admin_rules_summary($post_id)
    {
        $raw = get_post_meta($post_id, 'bw_tbl_display_rules_v1', true);
        $raw = is_array($raw) ? $raw : [];
        $include = isset($raw['include']) && is_array($raw['include']) ? $raw['include'] : [];
        $exclude = isset($raw['exclude']) && is_array($raw['exclude']) ? $raw['exclude'] : [];

        $include_text = bw_tbl_admin_rules_part_to_text($include, 'include');
        $exclude_text = bw_tbl_admin_rules_part_to_text($exclude, 'exclude');

        $summary = $include_text;
        if ('' !== $exclude_text) {
            $summary .= '; ' . sprintf(__('Excluding: %s', 'bw'), $exclude_text);
        }

        $summary = trim($summary);
        if ('' === $summary) {
            $summary = __('All (within type)', 'bw');
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($summary) > 80) {
                $summary = rtrim(mb_substr($summary, 0, 79)) . '…';
            }
        } elseif (strlen($summary) > 80) {
            $summary = rtrim(substr($summary, 0, 79)) . '...';
        }

        return $summary;
    }
}

if (!function_exists('bw_tbl_admin_single_product_settings_summary')) {
    function bw_tbl_admin_single_product_settings_summary($post_id)
    {
        if (!function_exists('bw_tbl_get_single_product_rules_option')) {
            return '';
        }

        $post_id = absint($post_id);
        $option = bw_tbl_get_single_product_rules_option();
        if (empty($option['enabled'])) {
            return '';
        }

        $rules = isset($option['rules']) && is_array($option['rules']) ? $option['rules'] : [];
        if (empty($rules)) {
            return '';
        }

        $parts = [];
        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $template_id = isset($rule['template_id']) ? absint($rule['template_id']) : 0;
            if ($template_id !== $post_id) {
                continue;
            }

            $include = isset($rule['include_product_cat']) && is_array($rule['include_product_cat']) ? $rule['include_product_cat'] : [];
            $exclude = isset($rule['exclude_product_cat']) && is_array($rule['exclude_product_cat']) ? $rule['exclude_product_cat'] : [];

            $include_names = bw_tbl_admin_format_terms('product_cat', $include);
            $exclude_names = bw_tbl_admin_format_terms('product_cat', $exclude);
            $summary = '' !== $include_names ? sprintf(__('In Product Category: %s', 'bw'), $include_names) : __('All (within type)', 'bw');
            if ('' !== $exclude_names) {
                $summary .= '; ' . sprintf(__('Excluding: In Product Category: %s', 'bw'), $exclude_names);
            }
            $parts[] = $summary;
        }

        if (empty($parts)) {
            return '';
        }

        return implode(' | ', $parts);
    }
}

if (!function_exists('bw_tbl_admin_product_archive_settings_summary')) {
    function bw_tbl_admin_product_archive_settings_summary($post_id)
    {
        if (!function_exists('bw_tbl_get_product_archive_rules_option')) {
            return '';
        }

        $post_id = absint($post_id);
        $option = bw_tbl_get_product_archive_rules_option();
        if (empty($option['enabled'])) {
            return '';
        }

        $rules = isset($option['rules']) && is_array($option['rules']) ? $option['rules'] : [];
        if (empty($rules)) {
            return '';
        }

        $parts = [];
        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $template_id = isset($rule['template_id']) ? absint($rule['template_id']) : 0;
            if ($template_id !== $post_id) {
                continue;
            }

            $include = isset($rule['include_product_cat']) && is_array($rule['include_product_cat']) ? $rule['include_product_cat'] : [];
            $exclude = isset($rule['exclude_product_cat']) && is_array($rule['exclude_product_cat']) ? $rule['exclude_product_cat'] : [];

            $include_names = bw_tbl_admin_format_terms('product_cat', $include);
            $exclude_names = bw_tbl_admin_format_terms('product_cat', $exclude);
            $summary = '' !== $include_names ? sprintf(__('On Product Category: %s', 'bw'), $include_names) : __('Match-all', 'bw');
            if ('' !== $exclude_names) {
                $summary .= '; ' . sprintf(__('Excluding: On Product Category: %s', 'bw'), $exclude_names);
            }
            $parts[] = $summary;
        }

        if (empty($parts)) {
            return '';
        }

        return implode(' | ', $parts);
    }
}

if (!function_exists('bw_tbl_admin_is_active_footer_template')) {
    function bw_tbl_admin_is_active_footer_template($post_id)
    {
        $post_id = absint($post_id);
        if ($post_id <= 0 || !function_exists('bw_tbl_get_feature_flags') || !function_exists('bw_tbl_get_footer_option')) {
            return false;
        }

        $flags = bw_tbl_get_feature_flags();
        if (empty($flags['footer_override_enabled'])) {
            return false;
        }

        $footer_option = bw_tbl_get_footer_option();
        $active_footer_id = isset($footer_option['active_footer_template_id']) ? absint($footer_option['active_footer_template_id']) : 0;

        return $active_footer_id > 0 && $active_footer_id === $post_id;
    }
}

if (!function_exists('bw_tbl_admin_is_active_single_product_template')) {
    function bw_tbl_admin_is_active_single_product_template($post_id)
    {
        $post_id = absint($post_id);
        if ($post_id <= 0 || !function_exists('bw_tbl_get_single_product_rules_option')) {
            return false;
        }

        $option = bw_tbl_get_single_product_rules_option();
        if (empty($option['enabled'])) {
            return false;
        }

        $rules = isset($option['rules']) && is_array($option['rules']) ? $option['rules'] : [];
        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $template_id = isset($rule['template_id']) ? absint($rule['template_id']) : 0;
            if ($template_id > 0 && $template_id === $post_id) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('bw_tbl_admin_is_active_product_archive_template')) {
    function bw_tbl_admin_is_active_product_archive_template($post_id)
    {
        $post_id = absint($post_id);
        if ($post_id <= 0 || !function_exists('bw_tbl_get_product_archive_rules_option')) {
            return false;
        }

        $option = bw_tbl_get_product_archive_rules_option();
        if (empty($option['enabled'])) {
            return false;
        }

        $rules = isset($option['rules']) && is_array($option['rules']) ? $option['rules'] : [];
        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $template_id = isset($rule['template_id']) ? absint($rule['template_id']) : 0;
            if ($template_id > 0 && $template_id === $post_id) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('bw_tbl_admin_is_linked_template')) {
    function bw_tbl_admin_is_linked_template($post_id)
    {
        $post_id = absint($post_id);
        if ($post_id <= 0) {
            return false;
        }

        if (bw_tbl_admin_is_active_footer_template($post_id)) {
            return true;
        }

        if (bw_tbl_admin_is_active_single_product_template($post_id)) {
            return true;
        }

        if (bw_tbl_admin_is_active_product_archive_template($post_id)) {
            return true;
        }

        return false;
    }
}

if (!function_exists('bw_tbl_admin_template_link_badges')) {
    function bw_tbl_admin_template_link_badges($post_id, $template_type)
    {
        $post_id = absint($post_id);
        $template_type = sanitize_key((string) $template_type);
        if ($post_id <= 0) {
            return [];
        }

        $badges = [];
        $is_footer_active = ('footer' === $template_type) && bw_tbl_admin_is_active_footer_template($post_id);
        $is_single_product_active = ('single_product' === $template_type) && bw_tbl_admin_is_active_single_product_template($post_id);
        $is_product_archive_active = ('product_archive' === $template_type) && bw_tbl_admin_is_active_product_archive_template($post_id);

        if ($is_footer_active) {
            $badges[] = [
                'text' => __('Applies to: Footer', 'bw'),
                'tone' => 'info',
            ];
        }

        if ($is_single_product_active) {
            $badges[] = [
                'text' => __('Applies to: Single Product', 'bw'),
                'tone' => 'info',
            ];
        }

        if ($is_product_archive_active) {
            $badges[] = [
                'text' => __('Applies to: Product Archive', 'bw'),
                'tone' => 'info',
            ];
        }

        if (!$is_footer_active && !$is_single_product_active && !$is_product_archive_active) {
            $badges[] = [
                'text' => __('Not linked', 'bw'),
                'tone' => 'warn',
            ];
        }

        return $badges;
    }
}

if (!function_exists('bw_tbl_admin_list_columns')) {
    function bw_tbl_admin_list_columns($columns)
    {
        $columns = is_array($columns) ? $columns : [];
        $new_columns = [];

        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;
            if ('title' === $key) {
                $new_columns['bw_tbl_type'] = __('Type', 'bw');
                $new_columns['bw_tbl_priority'] = __('Priority', 'bw');
                $new_columns['bw_tbl_applies_to'] = __('Applies To', 'bw');
            }
        }

        return $new_columns;
    }
}
add_filter('manage_edit-bw_template_columns', 'bw_tbl_admin_list_columns');

if (!function_exists('bw_tbl_admin_render_list_column')) {
    function bw_tbl_admin_render_list_column($column, $post_id)
    {
        $post_id = absint($post_id);
        if ($post_id <= 0) {
            return;
        }

        if ('bw_tbl_type' === $column) {
            $type = get_post_meta($post_id, 'bw_template_type', true);
            if (function_exists('bw_tbl_sanitize_template_type')) {
                $type = bw_tbl_sanitize_template_type($type);
            } else {
                $type = sanitize_key((string) $type);
            }

            if (!current_user_can('edit_post', $post_id)) {
                echo esc_html(bw_tbl_admin_template_type_label($type));
                return;
            }

            $allowed_types = bw_tbl_admin_allowed_template_types();
            $is_linked = bw_tbl_admin_is_linked_template($post_id) ? '1' : '0';

            echo '<select class="bw-tbl-inline-type-select" data-post-id="' . esc_attr((string) $post_id) . '" data-linked="' . esc_attr($is_linked) . '" style="min-width:160px;">';
            foreach ($allowed_types as $allowed_type) {
                $allowed_type = sanitize_key((string) $allowed_type);
                if ('' === $allowed_type) {
                    continue;
                }
                echo '<option value="' . esc_attr($allowed_type) . '" ' . selected($type, $allowed_type, false) . '>' . esc_html(bw_tbl_admin_template_type_label($allowed_type)) . '</option>';
            }
            echo '</select>';
            echo '<span class="bw-tbl-inline-type-status" style="margin-left:8px;color:#50575e;"></span>';
            return;
        }

        if ('bw_tbl_priority' === $column) {
            $priority = get_post_meta($post_id, 'bw_template_priority', true);
            $priority = is_numeric($priority) ? (int) $priority : 10;
            echo esc_html((string) $priority);
            return;
        }

        if ('bw_tbl_applies_to' === $column) {
            $type = get_post_meta($post_id, 'bw_template_type', true);
            $type = function_exists('bw_tbl_sanitize_template_type') ? bw_tbl_sanitize_template_type($type) : sanitize_key((string) $type);

            $badges = bw_tbl_admin_template_link_badges($post_id, $type);
            if (!empty($badges)) {
                foreach ($badges as $badge) {
                    $text = isset($badge['text']) ? (string) $badge['text'] : '';
                    $tone = isset($badge['tone']) ? sanitize_key((string) $badge['tone']) : 'info';
                    $tone_class = 'warn' === $tone ? 'bw-admin-pill--warn' : 'bw-admin-pill--info';
                    if ('' === $text) {
                        continue;
                    }
                    echo '<span class="bw-tbl-pill bw-admin-pill ' . esc_attr($tone_class) . '">' . esc_html($text) . '</span>';
                }
            }

            if ('single_product' === $type && bw_tbl_admin_is_active_single_product_template($post_id)) {
                $settings_summary = bw_tbl_admin_single_product_settings_summary($post_id);
                if ('' !== $settings_summary) {
                    echo '<div style="margin-top:6px;">' . esc_html($settings_summary) . '</div>';
                    return;
                }
            }

            if ('product_archive' === $type && bw_tbl_admin_is_active_product_archive_template($post_id)) {
                $settings_summary = bw_tbl_admin_product_archive_settings_summary($post_id);
                if ('' !== $settings_summary) {
                    echo '<div style="margin-top:6px;">' . esc_html($settings_summary) . '</div>';
                    return;
                }
            }

            if (!empty($badges)) {
                return;
            }

            echo esc_html(bw_tbl_admin_rules_summary($post_id));
        }
    }
}
add_action('manage_bw_template_posts_custom_column', 'bw_tbl_admin_render_list_column', 10, 2);

if (!function_exists('bw_tbl_admin_footer_display_state')) {
    function bw_tbl_admin_footer_display_state($states, $post)
    {
        if (!($post instanceof WP_Post) || 'bw_template' !== $post->post_type) {
            return $states;
        }

        $post_id = isset($post->ID) ? absint($post->ID) : 0;
        if ($post_id <= 0) {
            return $states;
        }

        $type = get_post_meta($post_id, 'bw_template_type', true);
        $type = function_exists('bw_tbl_sanitize_template_type') ? bw_tbl_sanitize_template_type($type) : sanitize_key((string) $type);
        $badges = bw_tbl_admin_template_link_badges($post_id, $type);
        foreach ($badges as $badge) {
            $text = isset($badge['text']) ? (string) $badge['text'] : '';
            if ('' === $text) {
                continue;
            }
            $key = 'bw_tbl_state_' . md5($text);
            $states[$key] = $text;
        }
        return $states;
    }
}
add_filter('display_post_states', 'bw_tbl_admin_footer_display_state', 10, 2);

if (!function_exists('bw_tbl_admin_type_filter_dropdown')) {
    function bw_tbl_admin_type_filter_dropdown()
    {
        global $typenow;
        if ('bw_template' !== $typenow) {
            return;
        }

        $current = isset($_GET['bw_template_type_filter']) ? sanitize_key(wp_unslash($_GET['bw_template_type_filter'])) : '';
        $types = function_exists('bw_tbl_template_type_allowed_values')
            ? bw_tbl_template_type_allowed_values()
            : ['footer', 'single_post', 'single_page', 'single_product', 'product_archive', 'archive', 'search', 'error_404'];
        ?>
        <select name="bw_template_type_filter">
            <option value=""><?php esc_html_e('All Types', 'bw'); ?></option>
            <?php foreach ($types as $type) : ?>
                <option value="<?php echo esc_attr($type); ?>" <?php selected($current, $type); ?>><?php echo esc_html(bw_tbl_admin_template_type_label($type)); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }
}
add_action('restrict_manage_posts', 'bw_tbl_admin_type_filter_dropdown');

if (!function_exists('bw_tbl_admin_apply_type_filter')) {
    function bw_tbl_admin_apply_type_filter($query)
    {
        if (!($query instanceof WP_Query) || !is_admin() || !$query->is_main_query()) {
            return;
        }

        $post_type = $query->get('post_type');
        if ('bw_template' !== $post_type) {
            return;
        }

        $selected = isset($_GET['bw_template_type_filter']) ? sanitize_key(wp_unslash($_GET['bw_template_type_filter'])) : '';
        if ('' === $selected) {
            return;
        }

        $allowed = function_exists('bw_tbl_template_type_allowed_values') ? bw_tbl_template_type_allowed_values() : [];
        if (!in_array($selected, $allowed, true)) {
            return;
        }

        $query->set(
            'meta_query',
            [
                [
                    'key' => 'bw_template_type',
                    'value' => $selected,
                ],
            ]
        );
    }
}
add_action('pre_get_posts', 'bw_tbl_admin_apply_type_filter');

if (!function_exists('bw_tbl_admin_enqueue_inline_type_assets')) {
    function bw_tbl_admin_enqueue_inline_type_assets($hook)
    {
        if ('edit.php' !== $hook) {
            return;
        }

        $post_type = isset($_GET['post_type']) ? sanitize_key(wp_unslash($_GET['post_type'])) : '';
        if ('bw_template' !== $post_type) {
            return;
        }

        $script_path = BW_MEW_PATH . 'includes/modules/theme-builder-lite/admin/bw-template-type-inline.js';
        wp_enqueue_script(
            'bw-tbl-inline-template-type',
            BW_MEW_URL . 'includes/modules/theme-builder-lite/admin/bw-template-type-inline.js',
            ['jquery'],
            file_exists($script_path) ? filemtime($script_path) : '1.0.0',
            true
        );

        wp_localize_script(
            'bw-tbl-inline-template-type',
            'bwTblInlineType',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bw_tbl_inline_type_update'),
                'confirmMessage' => __('This template is currently linked. Changing type may break links. Continue?', 'bw'),
                'saving' => __('Saving…', 'bw'),
                'saved' => __('Saved', 'bw'),
                'error' => __('Error', 'bw'),
                'listTitle' => __('All Templates', 'bw'),
                'listSubtitle' => __('Manage templates, type, priority, and applies-to rules.', 'bw'),
                'actionHelper' => __('Filter, search, and manage your templates.', 'bw'),
            ]
        );
    }
}
add_action('admin_enqueue_scripts', 'bw_tbl_admin_enqueue_inline_type_assets');

if (!function_exists('bw_tbl_admin_ajax_update_template_type')) {
    function bw_tbl_admin_ajax_update_template_type()
    {
        if (!check_ajax_referer('bw_tbl_inline_type_update', 'nonce', false)) {
            wp_send_json_error(['message' => __('Invalid nonce.', 'bw')], 403);
        }

        $post_id = isset($_POST['post_id']) ? absint(wp_unslash($_POST['post_id'])) : 0;
        $new_type = isset($_POST['template_type']) ? sanitize_key(wp_unslash($_POST['template_type'])) : '';

        if ($post_id <= 0 || '' === $new_type) {
            wp_send_json_error(['message' => __('Invalid request.', 'bw')], 400);
        }

        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Unauthorized.', 'bw')], 403);
        }

        $post = get_post($post_id);
        if (!($post instanceof WP_Post) || 'bw_template' !== $post->post_type) {
            wp_send_json_error(['message' => __('Invalid template.', 'bw')], 400);
        }

        $allowed = bw_tbl_admin_allowed_template_types();
        if (!in_array($new_type, $allowed, true)) {
            wp_send_json_error(['message' => __('Invalid template type.', 'bw')], 400);
        }

        update_post_meta($post_id, 'bw_template_type', $new_type);

        wp_send_json_success(
            [
                'type' => $new_type,
                'label' => bw_tbl_admin_template_type_label($new_type),
            ]
        );
    }
}
add_action('wp_ajax_bw_tbl_update_template_type', 'bw_tbl_admin_ajax_update_template_type');
