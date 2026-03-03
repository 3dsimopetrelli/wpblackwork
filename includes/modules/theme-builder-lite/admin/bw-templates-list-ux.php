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
        $product_archive = [
            'include_shop' => bw_tbl_admin_has_rule_type($include, 'product_archive_shop') ? 1 : 0,
            'include_categories' => bw_tbl_admin_rule_values_by_type($include, 'product_archive_category', 'terms'),
            'include_tags' => bw_tbl_admin_rule_values_by_type($include, 'product_archive_tag', 'terms'),
            'exclude_shop' => bw_tbl_admin_has_rule_type($exclude, 'product_archive_shop') ? 1 : 0,
            'exclude_categories' => bw_tbl_admin_rule_values_by_type($exclude, 'product_archive_category', 'terms'),
            'exclude_tags' => bw_tbl_admin_rule_values_by_type($exclude, 'product_archive_tag', 'terms'),
        ];
        $single_post = [
            'include_categories' => bw_tbl_admin_rule_values_by_type($include, 'post_category', 'terms'),
            'include_ids' => bw_tbl_admin_rule_values_by_type($include, 'post_id', 'ids'),
            'exclude_categories' => bw_tbl_admin_rule_values_by_type($exclude, 'post_category', 'terms'),
            'exclude_ids' => bw_tbl_admin_rule_values_by_type($exclude, 'post_id', 'ids'),
        ];
        $single_page = [
            'include_ids' => bw_tbl_admin_rule_values_by_type($include, 'page_id', 'ids'),
            'exclude_ids' => bw_tbl_admin_rule_values_by_type($exclude, 'page_id', 'ids'),
        ];
        $archive = [
            'include_blog' => bw_tbl_admin_has_rule_type($include, 'archive_blog') ? 1 : 0,
            'include_categories' => bw_tbl_admin_rule_values_by_type($include, 'archive_category', 'terms'),
            'exclude_blog' => bw_tbl_admin_has_rule_type($exclude, 'archive_blog') ? 1 : 0,
            'exclude_categories' => bw_tbl_admin_rule_values_by_type($exclude, 'archive_category', 'terms'),
        ];

        $first_non_empty_section = '';
        if (!empty($single_product['include_categories']) || !empty($single_product['exclude_categories']) || !empty($single_product['include_ids']) || !empty($single_product['exclude_ids'])) {
            $first_non_empty_section = 'single_product';
        } elseif (1 === (int) $product_archive['include_shop'] || 1 === (int) $product_archive['exclude_shop'] || !empty($product_archive['include_categories']) || !empty($product_archive['exclude_categories']) || !empty($product_archive['include_tags']) || !empty($product_archive['exclude_tags'])) {
            $first_non_empty_section = 'product_archive';
        } elseif (!empty($single_post['include_categories']) || !empty($single_post['exclude_categories']) || !empty($single_post['include_ids']) || !empty($single_post['exclude_ids'])) {
            $first_non_empty_section = 'single_post';
        } elseif (!empty($single_page['include_ids']) || !empty($single_page['exclude_ids'])) {
            $first_non_empty_section = 'single_page';
        } elseif (1 === (int) $archive['include_blog'] || 1 === (int) $archive['exclude_blog'] || !empty($archive['include_categories']) || !empty($archive['exclude_categories'])) {
            $first_non_empty_section = 'archive';
        }

        return [
            'type' => $type,
            'type_label' => bw_tbl_admin_template_type_label($type),
            'priority' => $priority,
            'last_section' => $last_section,
            'first_non_empty_section' => $first_non_empty_section,
            'single_product' => $single_product,
            'product_archive' => $product_archive,
            'single_post' => $single_post,
            'single_page' => $single_page,
            'archive' => $archive,
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

            echo '<select multiple="multiple" size="5" name="' . esc_attr($name) . '" id="' . esc_attr($id) . '" class="' . esc_attr($id) . '" style="width:100%;">';
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
            echo '<select multiple="multiple" name="' . esc_attr($name) . '" id="' . esc_attr($id) . '" class="' . esc_attr($id) . '" style="width:100%;"></select>';
            return;
        }

        $dropdown = preg_replace('/<select\s/i', '<select multiple="multiple" size="5" data-multiple="1" class="' . esc_attr($id) . '" style="width:100%;" ', $dropdown, 1);
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
                <label>
                    <span class="title"><?php esc_html_e('Section', 'bw'); ?></span>
                    <select name="bw_tbl_qe_section" class="bw-tbl-qe-section-select">
                        <option value="single_product"><?php esc_html_e('Single Product Conditions', 'bw'); ?></option>
                        <option value="product_archive"><?php esc_html_e('Product Archive Conditions', 'bw'); ?></option>
                        <option value="single_post"><?php esc_html_e('Single Post Conditions', 'bw'); ?></option>
                        <option value="single_page"><?php esc_html_e('Single Page Conditions', 'bw'); ?></option>
                        <option value="archive"><?php esc_html_e('Archive Conditions', 'bw'); ?></option>
                    </select>
                </label>

                <label>
                    <span class="title"><?php esc_html_e('Priority', 'bw'); ?></span>
                    <span class="input-text-wrap"><input type="number" min="0" max="999" step="1" name="bw_tbl_qe_priority" class="bw-tbl-qe-priority" value="10" /></span>
                </label>

                <div class="bw-tbl-qe-section" data-section="single_product" style="margin-top:10px;">
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

                <div class="bw-tbl-qe-section" data-section="product_archive" style="margin-top:10px;">
                    <p><strong><?php esc_html_e('Product Archive Conditions', 'bw'); ?></strong></p>
                    <label><input type="checkbox" name="bw_tbl_qe_include_product_archive_shop" class="bw-tbl-qe-pa-inc-shop" value="1" /> <?php esc_html_e('Include Shop page', 'bw'); ?></label>
                    <p><?php esc_html_e('Include - Product Categories', 'bw'); ?></p>
                    <?php bw_tbl_admin_quick_edit_taxonomy_multiselect('product_cat', 'bw_tbl_qe_include_product_archive_cat[]', 'bw-tbl-qe-pa-inc-cat'); ?>
                    <p><?php esc_html_e('Include - Product Tags', 'bw'); ?></p>
                    <?php bw_tbl_admin_quick_edit_taxonomy_multiselect('product_tag', 'bw_tbl_qe_include_product_archive_tag[]', 'bw-tbl-qe-pa-inc-tag'); ?>
                    <label><input type="checkbox" name="bw_tbl_qe_exclude_product_archive_shop" class="bw-tbl-qe-pa-exc-shop" value="1" /> <?php esc_html_e('Exclude Shop page', 'bw'); ?></label>
                    <p><?php esc_html_e('Exclude - Product Categories', 'bw'); ?></p>
                    <?php bw_tbl_admin_quick_edit_taxonomy_multiselect('product_cat', 'bw_tbl_qe_exclude_product_archive_cat[]', 'bw-tbl-qe-pa-exc-cat'); ?>
                    <p><?php esc_html_e('Exclude - Product Tags', 'bw'); ?></p>
                    <?php bw_tbl_admin_quick_edit_taxonomy_multiselect('product_tag', 'bw_tbl_qe_exclude_product_archive_tag[]', 'bw-tbl-qe-pa-exc-tag'); ?>
                </div>

                <div class="bw-tbl-qe-section" data-section="single_post" style="margin-top:10px;">
                    <p><strong><?php esc_html_e('Single Post Conditions', 'bw'); ?></strong></p>
                    <p><?php esc_html_e('Include - Post Categories', 'bw'); ?></p>
                    <?php bw_tbl_admin_quick_edit_taxonomy_multiselect('category', 'bw_tbl_qe_include_post_cat[]', 'bw-tbl-qe-post-inc-cat'); ?>
                    <p><?php esc_html_e('Include - Post IDs', 'bw'); ?></p>
                    <input type="text" name="bw_tbl_qe_include_post_ids" class="widefat bw-tbl-qe-post-inc-ids" />
                    <p><?php esc_html_e('Exclude - Post Categories', 'bw'); ?></p>
                    <?php bw_tbl_admin_quick_edit_taxonomy_multiselect('category', 'bw_tbl_qe_exclude_post_cat[]', 'bw-tbl-qe-post-exc-cat'); ?>
                    <p><?php esc_html_e('Exclude - Post IDs', 'bw'); ?></p>
                    <input type="text" name="bw_tbl_qe_exclude_post_ids" class="widefat bw-tbl-qe-post-exc-ids" />
                </div>

                <div class="bw-tbl-qe-section" data-section="single_page" style="margin-top:10px;">
                    <p><strong><?php esc_html_e('Single Page Conditions', 'bw'); ?></strong></p>
                    <p><?php esc_html_e('Include - Page IDs', 'bw'); ?></p>
                    <input type="text" name="bw_tbl_qe_include_page_ids" class="widefat bw-tbl-qe-page-inc-ids" />
                    <p><?php esc_html_e('Exclude - Page IDs', 'bw'); ?></p>
                    <input type="text" name="bw_tbl_qe_exclude_page_ids" class="widefat bw-tbl-qe-page-exc-ids" />
                </div>

                <div class="bw-tbl-qe-section" data-section="archive" style="margin-top:10px;">
                    <p><strong><?php esc_html_e('Archive Conditions', 'bw'); ?></strong></p>
                    <label><input type="checkbox" name="bw_tbl_qe_include_archive_blog" class="bw-tbl-qe-arc-inc-blog" value="1" /> <?php esc_html_e('Include Blog archive', 'bw'); ?></label>
                    <p><?php esc_html_e('Include - Categories', 'bw'); ?></p>
                    <?php bw_tbl_admin_quick_edit_taxonomy_multiselect('category', 'bw_tbl_qe_include_archive_cat[]', 'bw-tbl-qe-arc-inc-cat'); ?>
                    <label><input type="checkbox" name="bw_tbl_qe_exclude_archive_blog" class="bw-tbl-qe-arc-exc-blog" value="1" /> <?php esc_html_e('Exclude Blog archive', 'bw'); ?></label>
                    <p><?php esc_html_e('Exclude - Categories', 'bw'); ?></p>
                    <?php bw_tbl_admin_quick_edit_taxonomy_multiselect('category', 'bw_tbl_qe_exclude_archive_cat[]', 'bw-tbl-qe-arc-exc-cat'); ?>
                </div>

                <div class="bw-tbl-qe-note" style="margin-top:10px;">
                    <p class="description"><?php esc_html_e('Applies globally per type; no additional rules.', 'bw'); ?></p>
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

        wp_enqueue_script(
            'bw-tbl-quickedit',
            plugin_dir_url(__FILE__) . 'bw-templates-quickedit.js',
            ['jquery', 'inline-edit-post'],
            '1.0.0',
            true
        );
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
