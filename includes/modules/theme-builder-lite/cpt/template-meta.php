<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_tbl_template_type_allowed_values')) {
    function bw_tbl_template_type_allowed_values()
    {
        return ['footer', 'single_post', 'single_page', 'archive', 'search', 'error_404'];
    }
}

if (!function_exists('bw_tbl_rules_allowed_types')) {
    function bw_tbl_rules_allowed_types()
    {
        return ['post_category', 'post_id', 'page_id', 'archive_blog', 'archive_category', 'archive_tag', 'archive_post_type'];
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
                <option value="archive" <?php selected($current, 'archive'); ?>><?php esc_html_e('Archive', 'bw'); ?></option>
                <option value="search" <?php selected($current, 'search'); ?>><?php esc_html_e('Search Results', 'bw'); ?></option>
                <option value="error_404" <?php selected($current, 'error_404'); ?>><?php esc_html_e('Error 404', 'bw'); ?></option>
            </select>
        </p>
        <p class="description"><?php esc_html_e('Phase 2 supports Footer, Single Post, Single Page, Archive, Search Results, and Error 404.', 'bw'); ?></p>
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
        $template_type = bw_tbl_sanitize_template_type(get_post_meta($post->ID, 'bw_template_type', true));
        $archive_include = bw_tbl_get_archive_rules_for_ui($post->ID, 'include');
        $archive_exclude = bw_tbl_get_archive_rules_for_ui($post->ID, 'exclude');
        $archive_post_type_options = bw_tbl_archive_post_type_options();
        $category_terms = get_terms(
            [
                'taxonomy' => 'category',
                'hide_empty' => false,
            ]
        );
        $tag_terms = get_terms(
            [
                'taxonomy' => 'post_tag',
                'hide_empty' => false,
            ]
        );
        ?>
        <p>
            <label for="bw-template-priority-field"><strong><?php esc_html_e('Priority', 'bw'); ?></strong></label><br />
            <input id="bw-template-priority-field" type="number" min="0" max="999" step="1" name="bw_template_priority" value="<?php echo esc_attr((string) $priority); ?>" />
            <span class="description"><?php esc_html_e('Higher priority wins. Tie-break: lower template ID.', 'bw'); ?></span>
        </p>

        <hr />

        <div id="bw-tbl-standard-rules-panel" style="<?php echo 'archive' === $template_type ? 'display:none;' : ''; ?>">
            <p><strong><?php esc_html_e('Include Rules', 'bw'); ?></strong></p>
            <?php bw_tbl_render_rules_rows('include', $include_rows); ?>

            <p style="margin-top:14px;"><strong><?php esc_html_e('Exclude Rules', 'bw'); ?></strong></p>
            <?php bw_tbl_render_rules_rows('exclude', $exclude_rows); ?>
        </div>

        <div id="bw-tbl-archive-rules-panel" style="<?php echo 'archive' === $template_type ? '' : 'display:none;'; ?>">
            <p><strong><?php esc_html_e('Include Rules (Archive)', 'bw'); ?></strong></p>
            <p>
                <label>
                    <input type="checkbox" name="bw_tbl_archive_rules[include][archive_blog]" value="1" <?php checked(!empty($archive_include['archive_blog'])); ?> />
                    <?php esc_html_e('Blog archive (posts index)', 'bw'); ?>
                </label>
            </p>
            <p>
                <label for="bw-tbl-archive-include-category"><?php esc_html_e('Category archives', 'bw'); ?></label><br />
                <select id="bw-tbl-archive-include-category" name="bw_tbl_archive_rules[include][archive_category][]" multiple size="5" style="width:100%;">
                    <?php if (!is_wp_error($category_terms) && is_array($category_terms)) : ?>
                        <?php foreach ($category_terms as $term) : ?>
                            <option value="<?php echo esc_attr((string) $term->term_id); ?>" <?php selected(in_array((int) $term->term_id, $archive_include['archive_category'], true)); ?>><?php echo esc_html($term->name); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </p>
            <p>
                <label for="bw-tbl-archive-include-tag"><?php esc_html_e('Tag archives', 'bw'); ?></label><br />
                <select id="bw-tbl-archive-include-tag" name="bw_tbl_archive_rules[include][archive_tag][]" multiple size="5" style="width:100%;">
                    <?php if (!is_wp_error($tag_terms) && is_array($tag_terms)) : ?>
                        <?php foreach ($tag_terms as $term) : ?>
                            <option value="<?php echo esc_attr((string) $term->term_id); ?>" <?php selected(in_array((int) $term->term_id, $archive_include['archive_tag'], true)); ?>><?php echo esc_html($term->name); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </p>
            <p>
                <label for="bw-tbl-archive-include-posttype"><?php esc_html_e('Post type archives', 'bw'); ?></label><br />
                <select id="bw-tbl-archive-include-posttype" name="bw_tbl_archive_rules[include][archive_post_type][]" multiple size="5" style="width:100%;">
                    <?php foreach ($archive_post_type_options as $post_type => $label) : ?>
                        <option value="<?php echo esc_attr($post_type); ?>" <?php selected(in_array($post_type, $archive_include['archive_post_type'], true)); ?>><?php echo esc_html($label . ' (' . $post_type . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p style="margin-top:14px;"><strong><?php esc_html_e('Exclude Rules (Archive)', 'bw'); ?></strong></p>
            <p>
                <label>
                    <input type="checkbox" name="bw_tbl_archive_rules[exclude][archive_blog]" value="1" <?php checked(!empty($archive_exclude['archive_blog'])); ?> />
                    <?php esc_html_e('Blog archive (posts index)', 'bw'); ?>
                </label>
            </p>
            <p>
                <label for="bw-tbl-archive-exclude-category"><?php esc_html_e('Category archives', 'bw'); ?></label><br />
                <select id="bw-tbl-archive-exclude-category" name="bw_tbl_archive_rules[exclude][archive_category][]" multiple size="5" style="width:100%;">
                    <?php if (!is_wp_error($category_terms) && is_array($category_terms)) : ?>
                        <?php foreach ($category_terms as $term) : ?>
                            <option value="<?php echo esc_attr((string) $term->term_id); ?>" <?php selected(in_array((int) $term->term_id, $archive_exclude['archive_category'], true)); ?>><?php echo esc_html($term->name); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </p>
            <p>
                <label for="bw-tbl-archive-exclude-tag"><?php esc_html_e('Tag archives', 'bw'); ?></label><br />
                <select id="bw-tbl-archive-exclude-tag" name="bw_tbl_archive_rules[exclude][archive_tag][]" multiple size="5" style="width:100%;">
                    <?php if (!is_wp_error($tag_terms) && is_array($tag_terms)) : ?>
                        <?php foreach ($tag_terms as $term) : ?>
                            <option value="<?php echo esc_attr((string) $term->term_id); ?>" <?php selected(in_array((int) $term->term_id, $archive_exclude['archive_tag'], true)); ?>><?php echo esc_html($term->name); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </p>
            <p>
                <label for="bw-tbl-archive-exclude-posttype"><?php esc_html_e('Post type archives', 'bw'); ?></label><br />
                <select id="bw-tbl-archive-exclude-posttype" name="bw_tbl_archive_rules[exclude][archive_post_type][]" multiple size="5" style="width:100%;">
                    <?php foreach ($archive_post_type_options as $post_type => $label) : ?>
                        <option value="<?php echo esc_attr($post_type); ?>" <?php selected(in_array($post_type, $archive_exclude['archive_post_type'], true)); ?>><?php echo esc_html($label . ' (' . $post_type . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
        </div>

        <p class="description" style="margin-top:12px;">
            <?php esc_html_e('For search and 404 template types, rules are not required. Empty include/exclude means match-all within the selected template type.', 'bw'); ?>
        </p>
        <script>
            (function () {
                var typeField = document.getElementById('bw-template-type-field');
                var archivePanel = document.getElementById('bw-tbl-archive-rules-panel');
                var standardPanel = document.getElementById('bw-tbl-standard-rules-panel');
                if (!typeField || !archivePanel || !standardPanel) {
                    return;
                }

                function syncPanels() {
                    var isArchive = typeField.value === 'archive';
                    archivePanel.style.display = isArchive ? '' : 'none';
                    standardPanel.style.display = isArchive ? 'none' : '';
                }

                typeField.addEventListener('change', syncPanels);
                syncPanels();
            })();
        </script>
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
        $posted_type = isset($_POST['bw_template_type']) ? bw_tbl_sanitize_template_type(wp_unslash($_POST['bw_template_type'])) : bw_tbl_sanitize_template_type(get_post_meta($post_id, 'bw_template_type', true));
        $normalized_rules = ['include' => [], 'exclude' => []];

        if ('archive' === $posted_type) {
            $archive_rules_raw = isset($_POST['bw_tbl_archive_rules']) && is_array($_POST['bw_tbl_archive_rules']) ? wp_unslash($_POST['bw_tbl_archive_rules']) : [];
            $normalized_rules['include'] = bw_tbl_parse_archive_rules_section(isset($archive_rules_raw['include']) ? $archive_rules_raw['include'] : []);
            $normalized_rules['exclude'] = bw_tbl_parse_archive_rules_section(isset($archive_rules_raw['exclude']) ? $archive_rules_raw['exclude'] : []);
        } else {
            $include_raw = isset($raw_rules['include']) && is_array($raw_rules['include']) ? $raw_rules['include'] : [];
            $exclude_raw = isset($raw_rules['exclude']) && is_array($raw_rules['exclude']) ? $raw_rules['exclude'] : [];
            $normalized_rules['include'] = bw_tbl_parse_rule_rows($include_raw);
            $normalized_rules['exclude'] = bw_tbl_parse_rule_rows($exclude_raw);
        }

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
