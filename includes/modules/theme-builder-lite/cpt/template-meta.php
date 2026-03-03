<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_tbl_template_type_allowed_values')) {
    function bw_tbl_template_type_allowed_values()
    {
        return ['footer', 'single_post', 'single_page', 'single_product', 'product_archive', 'archive', 'search', 'error_404'];
    }
}

if (!function_exists('bw_tbl_rules_allowed_types')) {
    function bw_tbl_rules_allowed_types()
    {
        return ['post_category', 'post_id', 'page_id', 'product_category', 'product_id', 'product_archive_shop', 'product_archive_category', 'product_archive_tag', 'archive_blog', 'archive_category', 'archive_tag', 'archive_post_type'];
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

if (!function_exists('bw_tbl_qe_post_int_list')) {
    function bw_tbl_qe_post_int_list($key)
    {
        if (!isset($_POST[$key])) {
            return [];
        }

        $raw = wp_unslash($_POST[$key]);
        if (!is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $item) {
            $id = absint($item);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        $ids = array_values($ids);
        sort($ids, SORT_NUMERIC);
        return $ids;
    }
}

if (!function_exists('bw_tbl_qe_post_csv_ids')) {
    function bw_tbl_qe_post_csv_ids($key)
    {
        if (!isset($_POST[$key])) {
            return [];
        }

        return bw_tbl_parse_csv_ids((string) wp_unslash($_POST[$key]));
    }
}

if (!function_exists('bw_tbl_qe_post_checkbox')) {
    function bw_tbl_qe_post_checkbox($key)
    {
        return !empty($_POST[$key]);
    }
}

if (!function_exists('bw_tbl_qe_debug_log')) {
    function bw_tbl_qe_debug_log($message, $context = [])
    {
        if (!defined('WP_DEBUG_LOG') || !WP_DEBUG_LOG) {
            return;
        }

        $suffix = '';
        if (is_array($context) && !empty($context)) {
            $suffix = ' ' . wp_json_encode($context);
        }

        error_log('[BW TBL QE] ' . (string) $message . $suffix); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
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

            if ('post_category' === $type || 'product_category' === $type) {
                $rules[] = [
                    'type' => $type,
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

if (!function_exists('bw_tbl_archive_post_type_options')) {
    function bw_tbl_archive_post_type_options()
    {
        $post_types = get_post_types(
            [
                'public' => true,
                'has_archive' => true,
            ],
            'objects'
        );

        $options = [];
        foreach ($post_types as $post_type => $obj) {
            $post_type = sanitize_key((string) $post_type);
            if (in_array($post_type, ['attachment', 'bw_template', 'product'], true)) {
                continue;
            }

            $label = isset($obj->labels->singular_name) && '' !== (string) $obj->labels->singular_name
                ? (string) $obj->labels->singular_name
                : $post_type;
            $options[$post_type] = $label;
        }

        asort($options, SORT_NATURAL | SORT_FLAG_CASE);
        return $options;
    }
}

if (!function_exists('bw_tbl_parse_archive_rules_section')) {
    function bw_tbl_parse_archive_rules_section($raw_section)
    {
        $raw_section = is_array($raw_section) ? $raw_section : [];
        $rules = [];

        $blog = !empty($raw_section['archive_blog']) ? 1 : 0;
        if ($blog) {
            $rules[] = [
                'type' => 'archive_blog',
            ];
        }

        $category_ids = [];
        if (isset($raw_section['archive_category']) && is_array($raw_section['archive_category'])) {
            foreach ($raw_section['archive_category'] as $term_id) {
                $term_id = absint($term_id);
                if ($term_id > 0) {
                    $category_ids[$term_id] = $term_id;
                }
            }
        }
        $category_ids = array_values($category_ids);
        sort($category_ids, SORT_NUMERIC);
        if (!empty($category_ids)) {
            $rules[] = [
                'type' => 'archive_category',
                'terms' => $category_ids,
            ];
        }

        $tag_ids = [];
        if (isset($raw_section['archive_tag']) && is_array($raw_section['archive_tag'])) {
            foreach ($raw_section['archive_tag'] as $term_id) {
                $term_id = absint($term_id);
                if ($term_id > 0) {
                    $tag_ids[$term_id] = $term_id;
                }
            }
        }
        $tag_ids = array_values($tag_ids);
        sort($tag_ids, SORT_NUMERIC);
        if (!empty($tag_ids)) {
            $rules[] = [
                'type' => 'archive_tag',
                'terms' => $tag_ids,
            ];
        }

        $allowed_post_types = array_keys(bw_tbl_archive_post_type_options());
        $allowed_post_types = array_fill_keys($allowed_post_types, true);
        $post_types = [];
        if (isset($raw_section['archive_post_type']) && is_array($raw_section['archive_post_type'])) {
            foreach ($raw_section['archive_post_type'] as $post_type) {
                $post_type = sanitize_key((string) $post_type);
                if ('' === $post_type || !isset($allowed_post_types[$post_type])) {
                    continue;
                }
                $post_types[$post_type] = $post_type;
            }
        }
        $post_types = array_values($post_types);
        sort($post_types, SORT_STRING);
        if (!empty($post_types)) {
            $rules[] = [
                'type' => 'archive_post_type',
                'post_types' => $post_types,
            ];
        }

        return $rules;
    }
}

if (!function_exists('bw_tbl_get_archive_rules_for_ui')) {
    function bw_tbl_get_archive_rules_for_ui($post_id, $section)
    {
        $raw = get_post_meta($post_id, 'bw_tbl_display_rules_v1', true);
        $raw = is_array($raw) ? $raw : [];
        $rows = isset($raw[$section]) && is_array($raw[$section]) ? $raw[$section] : [];

        $result = [
            'archive_blog' => 0,
            'archive_category' => [],
            'archive_tag' => [],
            'archive_post_type' => [],
        ];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $type = isset($row['type']) ? sanitize_key((string) $row['type']) : '';
            if ('archive_blog' === $type) {
                $result['archive_blog'] = 1;
                continue;
            }

            if ('archive_category' === $type) {
                $terms = isset($row['terms']) && is_array($row['terms']) ? $row['terms'] : [];
                foreach ($terms as $term_id) {
                    $term_id = absint($term_id);
                    if ($term_id > 0) {
                        $result['archive_category'][$term_id] = $term_id;
                    }
                }
                continue;
            }

            if ('archive_tag' === $type) {
                $terms = isset($row['terms']) && is_array($row['terms']) ? $row['terms'] : [];
                foreach ($terms as $term_id) {
                    $term_id = absint($term_id);
                    if ($term_id > 0) {
                        $result['archive_tag'][$term_id] = $term_id;
                    }
                }
                continue;
            }

            if ('archive_post_type' === $type) {
                $post_types = isset($row['post_types']) && is_array($row['post_types']) ? $row['post_types'] : [];
                foreach ($post_types as $post_type) {
                    $post_type = sanitize_key((string) $post_type);
                    if ('' !== $post_type) {
                        $result['archive_post_type'][$post_type] = $post_type;
                    }
                }
            }
        }

        $result['archive_category'] = array_values($result['archive_category']);
        sort($result['archive_category'], SORT_NUMERIC);
        $result['archive_tag'] = array_values($result['archive_tag']);
        sort($result['archive_tag'], SORT_NUMERIC);
        $result['archive_post_type'] = array_values($result['archive_post_type']);
        sort($result['archive_post_type'], SORT_STRING);

        return $result;
    }
}

if (!function_exists('bw_tbl_parse_single_product_rules_section')) {
    function bw_tbl_parse_single_product_rules_section($raw_section)
    {
        $raw_section = is_array($raw_section) ? $raw_section : [];
        $rules = [];

        $category_ids = [];
        if (isset($raw_section['product_category']) && is_array($raw_section['product_category'])) {
            foreach ($raw_section['product_category'] as $term_id) {
                $term_id = absint($term_id);
                if ($term_id > 0) {
                    $category_ids[$term_id] = $term_id;
                }
            }
        }
        $category_ids = array_values($category_ids);
        sort($category_ids, SORT_NUMERIC);
        $category_ids = bw_tbl_filter_parent_product_cat_ids($category_ids);
        if (!empty($category_ids)) {
            $rules[] = [
                'type' => 'product_category',
                'terms' => $category_ids,
            ];
        }

        $product_ids = isset($raw_section['product_id']) ? bw_tbl_parse_csv_ids((string) $raw_section['product_id']) : [];
        if (!empty($product_ids)) {
            $rules[] = [
                'type' => 'product_id',
                'ids' => $product_ids,
            ];
        }

        return $rules;
    }
}

if (!function_exists('bw_tbl_parse_single_post_rules_section')) {
    function bw_tbl_parse_single_post_rules_section($raw_section)
    {
        $raw_section = is_array($raw_section) ? $raw_section : [];
        $rules = [];

        $category_ids = [];
        if (isset($raw_section['post_category']) && is_array($raw_section['post_category'])) {
            foreach ($raw_section['post_category'] as $term_id) {
                $term_id = absint($term_id);
                if ($term_id > 0) {
                    $category_ids[$term_id] = $term_id;
                }
            }
        }
        $category_ids = array_values($category_ids);
        sort($category_ids, SORT_NUMERIC);
        if (!empty($category_ids)) {
            $rules[] = [
                'type' => 'post_category',
                'terms' => $category_ids,
            ];
        }

        $post_ids = isset($raw_section['post_id']) ? bw_tbl_parse_csv_ids((string) $raw_section['post_id']) : [];
        if (!empty($post_ids)) {
            $rules[] = [
                'type' => 'post_id',
                'ids' => $post_ids,
            ];
        }

        return $rules;
    }
}

if (!function_exists('bw_tbl_parse_single_page_rules_section')) {
    function bw_tbl_parse_single_page_rules_section($raw_section)
    {
        $raw_section = is_array($raw_section) ? $raw_section : [];
        $rules = [];

        $page_ids = isset($raw_section['page_id']) ? bw_tbl_parse_csv_ids((string) $raw_section['page_id']) : [];
        if (!empty($page_ids)) {
            $rules[] = [
                'type' => 'page_id',
                'ids' => $page_ids,
            ];
        }

        return $rules;
    }
}

if (!function_exists('bw_tbl_get_single_product_rules_for_ui')) {
    function bw_tbl_get_single_product_rules_for_ui($post_id, $section)
    {
        $raw = get_post_meta($post_id, 'bw_tbl_display_rules_v1', true);
        $raw = is_array($raw) ? $raw : [];
        $rows = isset($raw[$section]) && is_array($raw[$section]) ? $raw[$section] : [];

        $result = [
            'product_category' => [],
            'product_id' => [],
        ];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $type = isset($row['type']) ? sanitize_key((string) $row['type']) : '';
            if ('product_category' === $type) {
                $terms = isset($row['terms']) && is_array($row['terms']) ? $row['terms'] : [];
                foreach ($terms as $term_id) {
                    $term_id = absint($term_id);
                    if ($term_id > 0) {
                        $result['product_category'][$term_id] = $term_id;
                    }
                }
                continue;
            }

            if ('product_id' === $type) {
                $ids = isset($row['ids']) && is_array($row['ids']) ? $row['ids'] : [];
                foreach ($ids as $id) {
                    $id = absint($id);
                    if ($id > 0) {
                        $result['product_id'][$id] = $id;
                    }
                }
            }
        }

        $result['product_category'] = array_values($result['product_category']);
        sort($result['product_category'], SORT_NUMERIC);
        $result['product_category'] = bw_tbl_filter_parent_product_cat_ids($result['product_category']);
        $result['product_id'] = array_values($result['product_id']);
        sort($result['product_id'], SORT_NUMERIC);

        return $result;
    }
}

if (!function_exists('bw_tbl_parse_product_archive_rules_section')) {
    function bw_tbl_parse_product_archive_rules_section($raw_section)
    {
        $raw_section = is_array($raw_section) ? $raw_section : [];
        $rules = [];

        $shop = !empty($raw_section['product_archive_shop']) ? 1 : 0;
        if ($shop) {
            $rules[] = [
                'type' => 'product_archive_shop',
            ];
        }

        $category_ids = [];
        if (isset($raw_section['product_archive_category']) && is_array($raw_section['product_archive_category'])) {
            foreach ($raw_section['product_archive_category'] as $term_id) {
                $term_id = absint($term_id);
                if ($term_id > 0) {
                    $category_ids[$term_id] = $term_id;
                }
            }
        }
        $category_ids = array_values($category_ids);
        sort($category_ids, SORT_NUMERIC);
        $category_ids = bw_tbl_filter_parent_product_cat_ids($category_ids);
        if (!empty($category_ids)) {
            $rules[] = [
                'type' => 'product_archive_category',
                'terms' => $category_ids,
            ];
        }

        $tag_ids = [];
        if (isset($raw_section['product_archive_tag']) && is_array($raw_section['product_archive_tag'])) {
            foreach ($raw_section['product_archive_tag'] as $term_id) {
                $term_id = absint($term_id);
                if ($term_id > 0) {
                    $tag_ids[$term_id] = $term_id;
                }
            }
        }
        $tag_ids = array_values($tag_ids);
        sort($tag_ids, SORT_NUMERIC);
        if (!empty($tag_ids)) {
            $rules[] = [
                'type' => 'product_archive_tag',
                'terms' => $tag_ids,
            ];
        }

        return $rules;
    }
}

if (!function_exists('bw_tbl_get_product_archive_rules_for_ui')) {
    function bw_tbl_get_product_archive_rules_for_ui($post_id, $section)
    {
        $raw = get_post_meta($post_id, 'bw_tbl_display_rules_v1', true);
        $raw = is_array($raw) ? $raw : [];
        $rows = isset($raw[$section]) && is_array($raw[$section]) ? $raw[$section] : [];

        $result = [
            'product_archive_shop' => 0,
            'product_archive_category' => [],
            'product_archive_tag' => [],
        ];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $type = isset($row['type']) ? sanitize_key((string) $row['type']) : '';
            if ('product_archive_shop' === $type) {
                $result['product_archive_shop'] = 1;
                continue;
            }

            if ('product_archive_category' === $type) {
                $terms = isset($row['terms']) && is_array($row['terms']) ? $row['terms'] : [];
                foreach ($terms as $term_id) {
                    $term_id = absint($term_id);
                    if ($term_id > 0) {
                        $result['product_archive_category'][$term_id] = $term_id;
                    }
                }
                continue;
            }

            if ('product_archive_tag' === $type) {
                $terms = isset($row['terms']) && is_array($row['terms']) ? $row['terms'] : [];
                foreach ($terms as $term_id) {
                    $term_id = absint($term_id);
                    if ($term_id > 0) {
                        $result['product_archive_tag'][$term_id] = $term_id;
                    }
                }
            }
        }

        $result['product_archive_category'] = array_values($result['product_archive_category']);
        sort($result['product_archive_category'], SORT_NUMERIC);
        $result['product_archive_category'] = bw_tbl_filter_parent_product_cat_ids($result['product_archive_category']);
        $result['product_archive_tag'] = array_values($result['product_archive_tag']);
        sort($result['product_archive_tag'], SORT_NUMERIC);

        return $result;
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
        if ('post_category' === $type || 'product_category' === $type) {
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
            'bw_tbl_template_rules_guidance_metabox',
            __('Display Rules', 'bw'),
            'bw_tbl_render_template_rules_guidance_metabox',
            'bw_template',
            'normal',
            'default'
        );

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

if (!function_exists('bw_tbl_render_template_rules_guidance_metabox')) {
    function bw_tbl_render_template_rules_guidance_metabox()
    {
        $list_url = admin_url('edit.php?post_type=bw_template');
        ?>
        <p><?php esc_html_e('Conditions are managed from Templates list via Quick Edit.', 'bw'); ?></p>
        <p style="margin-top:10px;">
            <a class="button button-secondary" href="<?php echo esc_url($list_url); ?>"><?php esc_html_e('Open Templates List', 'bw'); ?></a>
        </p>
        <?php
    }
}

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
                <option value="single_product" <?php selected($current, 'single_product'); ?>><?php esc_html_e('Single Product', 'bw'); ?></option>
                <option value="product_archive" <?php selected($current, 'product_archive'); ?>><?php esc_html_e('Product Archive', 'bw'); ?></option>
                <option value="archive" <?php selected($current, 'archive'); ?>><?php esc_html_e('Archive', 'bw'); ?></option>
                <option value="search" <?php selected($current, 'search'); ?>><?php esc_html_e('Search Results', 'bw'); ?></option>
                <option value="error_404" <?php selected($current, 'error_404'); ?>><?php esc_html_e('Error 404', 'bw'); ?></option>
            </select>
        </p>
        <p class="description"><?php esc_html_e('Phase 2 supports Footer, Single Post, Single Page, Single Product, Product Archive, Archive, Search Results, and Error 404.', 'bw'); ?></p>
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
        $template_type = bw_tbl_sanitize_template_type(get_post_meta($post->ID, 'bw_template_type', true));
        $priority = get_post_meta($post->ID, 'bw_template_priority', true);
        $priority = is_numeric($priority) ? (int) $priority : 10;
        if ($priority < 0 || $priority > 999) {
            $priority = 10;
        }

        $summary = function_exists('bw_tbl_admin_rules_summary')
            ? bw_tbl_admin_rules_summary($post->ID)
            : __('All (within type)', 'bw');
        $list_url = admin_url('edit.php?post_type=bw_template');
        $type_label = function_exists('bw_tbl_admin_template_type_label')
            ? bw_tbl_admin_template_type_label($template_type)
            : $template_type;
        ?>
        <p>
            <strong><?php esc_html_e('Template Type', 'bw'); ?>:</strong>
            <span><?php echo esc_html($type_label); ?></span>
        </p>
        <p>
            <strong><?php esc_html_e('Priority', 'bw'); ?>:</strong>
            <span><?php echo esc_html((string) $priority); ?></span>
        </p>
        <p>
            <strong><?php esc_html_e('Applies To', 'bw'); ?>:</strong>
            <span><?php echo esc_html($summary); ?></span>
        </p>
        <p style="margin-top:12px;">
            <a class="button button-primary" href="<?php echo esc_url($list_url); ?>"><?php esc_html_e('Go To BW Templates List', 'bw'); ?></a>
        </p>
        <p class="description">
            <?php esc_html_e('Edit conditions using Quick Edit in the list to avoid conflicting UI.', 'bw'); ?>
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

        $doing_ajax = defined('DOING_AJAX') && DOING_AJAX;
        $request_action = isset($_POST['action']) ? sanitize_key(wp_unslash($_POST['action'])) : '';
        $is_inline_save_request = $doing_ajax && 'inline-save' === $request_action;
        $has_qe_marker = !empty($_POST['bw_tbl_qe_present']);

        bw_tbl_qe_debug_log(
            'save_post entry',
            [
                'post_id' => absint($post_id),
                'post_type' => get_post_type($post_id),
                'doing_ajax' => $doing_ajax ? 1 : 0,
                'current_action' => current_action(),
                'request_action' => $request_action,
                'has_qe_marker' => $has_qe_marker ? 1 : 0,
                'has_qe_nonce' => isset($_POST['bw_tbl_quick_edit_nonce']) ? 1 : 0,
                'has_inline_nonce' => isset($_POST['_inline_edit']) ? 1 : 0,
                'has_include_product_cat' => isset($_POST['bw_tbl_qe_include_product_cat']) ? 1 : 0,
                'has_exclude_product_cat' => isset($_POST['bw_tbl_qe_exclude_product_cat']) ? 1 : 0,
                'has_include_product_ids' => isset($_POST['bw_tbl_qe_include_product_ids']) ? 1 : 0,
                'has_exclude_product_ids' => isset($_POST['bw_tbl_qe_exclude_product_ids']) ? 1 : 0,
            ]
        );

        if (!$has_qe_marker && !$is_inline_save_request) {
            return;
        }

        $quick_edit_nonce_ok = isset($_POST['bw_tbl_quick_edit_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bw_tbl_quick_edit_nonce'])), 'bw_tbl_quick_edit_save');
        $inline_nonce_ok = isset($_POST['_inline_edit']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_inline_edit'])), 'inlineeditnonce');
        if (!$quick_edit_nonce_ok && !$inline_nonce_ok) {
            bw_tbl_qe_debug_log('save_post blocked by nonce');
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            bw_tbl_qe_debug_log('save_post blocked by capability', ['post_id' => absint($post_id)]);
            return;
        }

        if ('bw_template' !== get_post_type($post_id)) {
            bw_tbl_qe_debug_log('save_post skipped non-bw_template', ['post_id' => absint($post_id)]);
            return;
        }

        $quick_mode = !empty($_POST['bw_tbl_quick_edit_mode']);
        if (!$quick_mode) {
            bw_tbl_qe_debug_log('save_post skipped no quick mode', ['post_id' => absint($post_id)]);
            return;
        }

        $posted_type_raw = isset($_POST['bw_tbl_qe_template_type']) ? sanitize_key((string) wp_unslash($_POST['bw_tbl_qe_template_type'])) : '';
        $posted_type = '' !== $posted_type_raw ? bw_tbl_sanitize_template_type($posted_type_raw) : bw_tbl_sanitize_template_type(get_post_meta($post_id, 'bw_template_type', true));

        if (isset($_POST['bw_tbl_qe_priority']) && is_numeric(wp_unslash($_POST['bw_tbl_qe_priority']))) {
            $priority = (int) wp_unslash($_POST['bw_tbl_qe_priority']);
            if ($priority < 0) {
                $priority = 0;
            }
            if ($priority > 999) {
                $priority = 999;
            }
            update_post_meta($post_id, 'bw_template_priority', $priority);
        }

        $supported_types = ['single_post', 'single_page', 'archive', 'single_product', 'product_archive'];
        if (!in_array($posted_type, $supported_types, true)) {
            bw_tbl_qe_debug_log('save_post skipped unsupported type', ['post_id' => absint($post_id), 'posted_type' => $posted_type]);
            return;
        }

        $normalized_rules = ['include' => [], 'exclude' => []];

        if ('single_post' === $posted_type) {
            $include_cats = bw_tbl_qe_post_int_list('bw_tbl_qe_include_post_cat');
            $exclude_cats = bw_tbl_qe_post_int_list('bw_tbl_qe_exclude_post_cat');
            $include_ids = bw_tbl_qe_post_csv_ids('bw_tbl_qe_include_post_ids');
            $exclude_ids = bw_tbl_qe_post_csv_ids('bw_tbl_qe_exclude_post_ids');

            if (!empty($include_cats)) {
                $normalized_rules['include'][] = ['type' => 'post_category', 'terms' => $include_cats];
            }
            if (!empty($include_ids)) {
                $normalized_rules['include'][] = ['type' => 'post_id', 'ids' => $include_ids];
            }
            if (!empty($exclude_cats)) {
                $normalized_rules['exclude'][] = ['type' => 'post_category', 'terms' => $exclude_cats];
            }
            if (!empty($exclude_ids)) {
                $normalized_rules['exclude'][] = ['type' => 'post_id', 'ids' => $exclude_ids];
            }
        } elseif ('single_page' === $posted_type) {
            $include_ids = bw_tbl_qe_post_csv_ids('bw_tbl_qe_include_page_ids');
            $exclude_ids = bw_tbl_qe_post_csv_ids('bw_tbl_qe_exclude_page_ids');

            if (!empty($include_ids)) {
                $normalized_rules['include'][] = ['type' => 'page_id', 'ids' => $include_ids];
            }
            if (!empty($exclude_ids)) {
                $normalized_rules['exclude'][] = ['type' => 'page_id', 'ids' => $exclude_ids];
            }
        } elseif ('archive' === $posted_type) {
            $include_blog = bw_tbl_qe_post_checkbox('bw_tbl_qe_include_archive_blog');
            $exclude_blog = bw_tbl_qe_post_checkbox('bw_tbl_qe_exclude_archive_blog');
            $include_cats = bw_tbl_qe_post_int_list('bw_tbl_qe_include_archive_cat');
            $exclude_cats = bw_tbl_qe_post_int_list('bw_tbl_qe_exclude_archive_cat');

            if ($include_blog) {
                $normalized_rules['include'][] = ['type' => 'archive_blog'];
            }
            if (!empty($include_cats)) {
                $normalized_rules['include'][] = ['type' => 'archive_category', 'terms' => $include_cats];
            }
            if ($exclude_blog) {
                $normalized_rules['exclude'][] = ['type' => 'archive_blog'];
            }
            if (!empty($exclude_cats)) {
                $normalized_rules['exclude'][] = ['type' => 'archive_category', 'terms' => $exclude_cats];
            }
        } elseif ('single_product' === $posted_type) {
            $include_cats = bw_tbl_filter_parent_product_cat_ids(bw_tbl_qe_post_int_list('bw_tbl_qe_include_product_cat'));
            $exclude_cats = bw_tbl_filter_parent_product_cat_ids(bw_tbl_qe_post_int_list('bw_tbl_qe_exclude_product_cat'));
            $include_ids = bw_tbl_qe_post_csv_ids('bw_tbl_qe_include_product_ids');
            $exclude_ids = bw_tbl_qe_post_csv_ids('bw_tbl_qe_exclude_product_ids');

            if (!empty($include_cats)) {
                $normalized_rules['include'][] = ['type' => 'product_category', 'terms' => $include_cats];
            }
            if (!empty($include_ids)) {
                $normalized_rules['include'][] = ['type' => 'product_id', 'ids' => $include_ids];
            }
            if (!empty($exclude_cats)) {
                $normalized_rules['exclude'][] = ['type' => 'product_category', 'terms' => $exclude_cats];
            }
            if (!empty($exclude_ids)) {
                $normalized_rules['exclude'][] = ['type' => 'product_id', 'ids' => $exclude_ids];
            }
        } elseif ('product_archive' === $posted_type) {
            $include_shop = bw_tbl_qe_post_checkbox('bw_tbl_qe_include_product_archive_shop');
            $exclude_shop = bw_tbl_qe_post_checkbox('bw_tbl_qe_exclude_product_archive_shop');
            $include_cats = bw_tbl_filter_parent_product_cat_ids(bw_tbl_qe_post_int_list('bw_tbl_qe_include_product_archive_cat'));
            $exclude_cats = bw_tbl_filter_parent_product_cat_ids(bw_tbl_qe_post_int_list('bw_tbl_qe_exclude_product_archive_cat'));
            $include_tags = bw_tbl_qe_post_int_list('bw_tbl_qe_include_product_archive_tag');
            $exclude_tags = bw_tbl_qe_post_int_list('bw_tbl_qe_exclude_product_archive_tag');

            if ($include_shop) {
                $normalized_rules['include'][] = ['type' => 'product_archive_shop'];
            }
            if (!empty($include_cats)) {
                $normalized_rules['include'][] = ['type' => 'product_archive_category', 'terms' => $include_cats];
            }
            if (!empty($include_tags)) {
                $normalized_rules['include'][] = ['type' => 'product_archive_tag', 'terms' => $include_tags];
            }
            if ($exclude_shop) {
                $normalized_rules['exclude'][] = ['type' => 'product_archive_shop'];
            }
            if (!empty($exclude_cats)) {
                $normalized_rules['exclude'][] = ['type' => 'product_archive_category', 'terms' => $exclude_cats];
            }
            if (!empty($exclude_tags)) {
                $normalized_rules['exclude'][] = ['type' => 'product_archive_tag', 'terms' => $exclude_tags];
            }
        }

        update_post_meta($post_id, 'bw_tbl_display_rules_v1', $normalized_rules);
        bw_tbl_qe_debug_log(
            'save_post rules saved',
            [
                'post_id' => absint($post_id),
                'posted_type' => $posted_type,
                'saved_rules' => get_post_meta($post_id, 'bw_tbl_display_rules_v1', true),
            ]
        );
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
