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

if (!function_exists('bw_tbl_admin_rule_values_by_type')) {
    function bw_tbl_admin_rule_values_by_type($rules, $rule_type, $value_key)
    {
        $rules = is_array($rules) ? $rules : [];
        $rule_type = sanitize_key((string) $rule_type);
        $value_key = sanitize_key((string) $value_key);
        $result = [];

        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $type = isset($rule['type']) ? sanitize_key((string) $rule['type']) : '';
            if ($type !== $rule_type) {
                continue;
            }

            $values = isset($rule[$value_key]) && is_array($rule[$value_key]) ? $rule[$value_key] : [];
            foreach ($values as $value) {
                $value = 'terms' === $value_key ? absint($value) : (int) $value;
                if ($value > 0) {
                    $result[$value] = $value;
                }
            }
        }

        $result = array_values($result);
        if ('terms' === $value_key && in_array($rule_type, ['product_category', 'product_archive_category'], true)) {
            $result = bw_tbl_filter_parent_product_cat_ids($result);
        }
        sort($result, SORT_NUMERIC);

        return $result;
    }
}

if (!function_exists('bw_tbl_admin_has_rule_type')) {
    function bw_tbl_admin_has_rule_type($rules, $rule_type)
    {
        $rules = is_array($rules) ? $rules : [];
        $rule_type = sanitize_key((string) $rule_type);

        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $type = isset($rule['type']) ? sanitize_key((string) $rule['type']) : '';
            if ($type === $rule_type) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('bw_tbl_admin_quick_edit_payload')) {
    function bw_tbl_admin_quick_edit_payload($post_id)
    {
        $post_id = absint($post_id);
        $type = get_post_meta($post_id, 'bw_template_type', true);
        if (function_exists('bw_tbl_sanitize_template_type')) {
            $type = bw_tbl_sanitize_template_type($type);
        } else {
            $type = sanitize_key((string) $type);
        }

        $priority = get_post_meta($post_id, 'bw_template_priority', true);
        $priority = is_numeric($priority) ? (int) $priority : 10;

        $raw = get_post_meta($post_id, 'bw_tbl_display_rules_v1', true);
        $raw = is_array($raw) ? $raw : [];
        $include = isset($raw['include']) && is_array($raw['include']) ? $raw['include'] : [];
        $exclude = isset($raw['exclude']) && is_array($raw['exclude']) ? $raw['exclude'] : [];
        $last_section = sanitize_key((string) get_post_meta($post_id, 'bw_tbl_qe_last_section', true));

        $single_product = [
            'include_categories' => bw_tbl_admin_rule_values_by_type($include, 'product_category', 'terms'),
            'include_ids' => bw_tbl_admin_rule_values_by_type($include, 'product_id', 'ids'),
            'exclude_categories' => bw_tbl_admin_rule_values_by_type($exclude, 'product_category', 'terms'),
            'exclude_ids' => bw_tbl_admin_rule_values_by_type($exclude, 'product_id', 'ids'),
        ];
        return [
            'type' => $type,
            'type_label' => bw_tbl_admin_template_type_label($type),
            'priority' => $priority,
            'last_section' => $last_section,
            'single_product' => $single_product,
        ];
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
            $summary = bw_tbl_admin_rules_summary($post_id);
            $payload = bw_tbl_admin_quick_edit_payload($post_id);
            echo esc_html($summary);
            echo '<span class="bw-tbl-qe-data" style="display:none;" data-bw-qe="' . esc_attr(wp_json_encode($payload)) . '"></span>';
        }
    }
}
add_action('manage_bw_template_posts_custom_column', 'bw_tbl_admin_render_list_column', 10, 2);

if (!function_exists('bw_tbl_admin_quick_edit_taxonomy_multiselect')) {
    function bw_tbl_admin_quick_edit_taxonomy_multiselect($taxonomy, $name, $id)
    {
        $taxonomy = sanitize_key((string) $taxonomy);
        $name = (string) $name;
        $id = sanitize_html_class((string) $id);

        if ('product_cat' === $taxonomy) {
            $terms = get_terms(
                [
                    'taxonomy' => 'product_cat',
                    'hide_empty' => false,
                    'parent' => 0,
                    'orderby' => 'name',
                    'order' => 'ASC',
                ]
            );

            echo '<select multiple="multiple" data-placeholder="' . esc_attr__('Select categories...', 'bw') . '" name="' . esc_attr($name) . '" id="' . esc_attr($id) . '" class="bw-tbl-qe-taxonomy-select wc-enhanced-select ' . esc_attr($id) . '" style="width:100%;">';
            if (!is_wp_error($terms) && is_array($terms)) {
                foreach ($terms as $term) {
                    if (!($term instanceof WP_Term)) {
                        continue;
                    }
                    echo '<option value="' . esc_attr((string) $term->term_id) . '">' . esc_html($term->name) . '</option>';
                }
            }
            echo '</select>';
            return;
        }

        $dropdown = wp_dropdown_categories(
            [
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'name' => $name,
                'id' => $id,
                'show_option_none' => false,
                'echo' => false,
                'hierarchical' => true,
                'orderby' => 'name',
                'value_field' => 'term_id',
            ]
        );

        if (!is_string($dropdown) || '' === $dropdown) {
            echo '<select multiple="multiple" data-placeholder="' . esc_attr__('Select terms...', 'bw') . '" name="' . esc_attr($name) . '" id="' . esc_attr($id) . '" class="bw-tbl-qe-taxonomy-select wc-enhanced-select ' . esc_attr($id) . '" style="width:100%;"></select>';
            return;
        }

        $dropdown = preg_replace('/<select\s/i', '<select multiple="multiple" data-placeholder="' . esc_attr__('Select terms...', 'bw') . '" data-multiple="1" class="bw-tbl-qe-taxonomy-select wc-enhanced-select ' . esc_attr($id) . '" style="width:100%;" ', $dropdown, 1);
        echo $dropdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

if (!function_exists('bw_tbl_admin_quick_edit_custom_box')) {
    function bw_tbl_admin_quick_edit_custom_box($column_name, $post_type)
    {
        if ('bw_template' !== $post_type || 'bw_tbl_applies_to' !== $column_name) {
            return;
        }
        ?>
        <fieldset class="inline-edit-col-right bw-tbl-qe-wrap">
            <div class="inline-edit-col">
                <input type="hidden" name="bw_tbl_quick_edit_mode" value="1" />
                <input type="hidden" name="bw_tbl_qe_present" value="1" />
                <input type="hidden" name="bw_tbl_qe_priority_touched" class="bw-tbl-qe-priority-touched" value="0" />
                <input type="hidden" name="bw_tbl_qe_rules_touched" class="bw-tbl-qe-rules-touched" value="0" />
                <?php wp_nonce_field('bw_tbl_quick_edit_save', 'bw_tbl_quick_edit_nonce'); ?>

                <p><strong><?php esc_html_e('Template Type', 'bw'); ?>:</strong> <span class="bw-tbl-qe-type-label">-</span></p>
                <input type="hidden" name="bw_tbl_qe_template_type" class="bw-tbl-qe-type" value="" />
                <?php // Future sections (archive/page/post/product archive) will be reintroduced in a dedicated Phase 2 hardening task. ?>
                <div class="bw-tbl-qe-section bw-tbl-qe-section-single-product" style="margin-top:10px;">
                    <p><strong><?php esc_html_e('Single Product Conditions', 'bw'); ?></strong></p>
                    <p><?php esc_html_e('Include - Product Categories', 'bw'); ?></p>
                    <?php bw_tbl_admin_quick_edit_taxonomy_multiselect('product_cat', 'bw_tbl_qe_include_product_cat[]', 'bw-tbl-qe-sp-inc-cat'); ?>
                    <p><?php esc_html_e('Include - Product IDs', 'bw'); ?></p>
                    <input type="text" name="bw_tbl_qe_include_product_ids" class="widefat bw-tbl-qe-sp-inc-ids" />
                    <p><?php esc_html_e('Exclude - Product Categories', 'bw'); ?></p>
                    <?php bw_tbl_admin_quick_edit_taxonomy_multiselect('product_cat', 'bw_tbl_qe_exclude_product_cat[]', 'bw-tbl-qe-sp-exc-cat'); ?>
                    <p><?php esc_html_e('Exclude - Product IDs', 'bw'); ?></p>
                    <input type="text" name="bw_tbl_qe_exclude_product_ids" class="widefat bw-tbl-qe-sp-exc-ids" />
                </div>
            </div>
        </fieldset>
        <?php
    }
}
add_action('quick_edit_custom_box', 'bw_tbl_admin_quick_edit_custom_box', 10, 2);

if (!function_exists('bw_tbl_admin_enqueue_quick_edit_assets')) {
    function bw_tbl_admin_enqueue_quick_edit_assets($hook)
    {
        if ('edit.php' !== $hook) {
            return;
        }

        $post_type = isset($_GET['post_type']) ? sanitize_key(wp_unslash($_GET['post_type'])) : '';
        if ('bw_template' !== $post_type) {
            return;
        }

        $script_path = plugin_dir_path(__FILE__) . 'bw-templates-quickedit.js';
        $script_version = file_exists($script_path) ? (string) filemtime($script_path) : '1.0.0';
        $deps = ['jquery', 'inline-edit-post'];
        if (wp_script_is('wc-enhanced-select', 'registered')) {
            $deps[] = 'wc-enhanced-select';
        } elseif (wp_script_is('selectWoo', 'registered')) {
            $deps[] = 'selectWoo';
        } elseif (wp_script_is('select2', 'registered')) {
            $deps[] = 'select2';
        }

        wp_enqueue_script(
            'bw-tbl-quickedit',
            plugin_dir_url(__FILE__) . 'bw-templates-quickedit.js',
            $deps,
            $script_version,
            true
        );

        if (wp_script_is('wc-enhanced-select', 'registered')) {
            wp_enqueue_script('wc-enhanced-select');
        } elseif (wp_script_is('selectWoo', 'registered')) {
            wp_enqueue_script('selectWoo');
        } elseif (wp_script_is('select2', 'registered')) {
            wp_enqueue_script('select2');
        }

        if (wp_style_is('select2', 'registered')) {
            wp_enqueue_style('select2');
        } elseif (wp_style_is('selectWoo', 'registered')) {
            wp_enqueue_style('selectWoo');
        }
    }
}
add_action('admin_enqueue_scripts', 'bw_tbl_admin_enqueue_quick_edit_assets');

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
