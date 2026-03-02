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

if (!function_exists('bw_tbl_admin_format_terms')) {
    function bw_tbl_admin_format_terms($taxonomy, $term_ids)
    {
        $taxonomy = sanitize_key((string) $taxonomy);
        $term_ids = is_array($term_ids) ? $term_ids : [];
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
            echo esc_html(bw_tbl_admin_template_type_label($type));
            return;
        }

        if ('bw_tbl_priority' === $column) {
            $priority = get_post_meta($post_id, 'bw_template_priority', true);
            $priority = is_numeric($priority) ? (int) $priority : 10;
            echo esc_html((string) $priority);
            return;
        }

        if ('bw_tbl_applies_to' === $column) {
            echo esc_html(bw_tbl_admin_rules_summary($post_id));
        }
    }
}
add_action('manage_bw_template_posts_custom_column', 'bw_tbl_admin_render_list_column', 10, 2);

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
