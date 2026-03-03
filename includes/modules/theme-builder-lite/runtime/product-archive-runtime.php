<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BW_TBL_PRODUCT_ARCHIVE_RULES_OPTION')) {
    define('BW_TBL_PRODUCT_ARCHIVE_RULES_OPTION', 'bw_theme_builder_lite_product_archive_rules_v2');
}

if (!function_exists('bw_tbl_default_product_archive_rules_option')) {
    function bw_tbl_default_product_archive_rules_option()
    {
        return [
            'enabled' => 0,
            'rules' => [],
        ];
    }
}

if (!function_exists('bw_tbl_is_valid_product_archive_template')) {
    function bw_tbl_is_valid_product_archive_template($template_id)
    {
        $template_id = absint($template_id);
        if ($template_id <= 0) {
            return false;
        }

        $post = get_post($template_id);
        if (!$post || 'bw_template' !== $post->post_type || 'publish' !== $post->post_status) {
            return false;
        }

        $type = get_post_meta($template_id, 'bw_template_type', true);
        if (function_exists('bw_tbl_sanitize_template_type')) {
            $type = bw_tbl_sanitize_template_type($type);
        } else {
            $type = sanitize_key((string) $type);
        }

        return 'product_archive' === $type;
    }
}

if (!function_exists('bw_tbl_sanitize_product_archive_rule')) {
    function bw_tbl_sanitize_product_archive_rule($rule)
    {
        $rule = is_array($rule) ? $rule : [];

        $template_id = isset($rule['template_id']) ? absint($rule['template_id']) : 0;
        if (!bw_tbl_is_valid_product_archive_template($template_id)) {
            $template_id = 0;
        }

        $include_mode = isset($rule['include_mode']) ? sanitize_key((string) $rule['include_mode']) : '';
        $raw_include = isset($rule['include_product_cat']) && is_array($rule['include_product_cat'])
            ? bw_tbl_filter_parent_product_cat_ids($rule['include_product_cat'])
            : [];
        if ('selected' !== $include_mode && 'all' !== $include_mode) {
            $include_mode = !empty($raw_include) ? 'selected' : 'all';
        }
        $include_product_cat = ('selected' === $include_mode) ? $raw_include : [];

        $exclude_enabled = !empty($rule['exclude_enabled']);
        $exclude_product_cat = ($exclude_enabled && isset($rule['exclude_product_cat']) && is_array($rule['exclude_product_cat']))
            ? bw_tbl_filter_parent_product_cat_ids($rule['exclude_product_cat'])
            : [];

        return [
            'template_id' => $template_id,
            'include_product_cat' => $include_product_cat,
            'exclude_product_cat' => $exclude_product_cat,
        ];
    }
}

if (!function_exists('bw_tbl_sanitize_product_archive_rules_option')) {
    function bw_tbl_sanitize_product_archive_rules_option($input)
    {
        $input = is_array($input) ? $input : [];
        $enabled = !empty($input['enabled']) ? 1 : 0;

        $raw_rules = isset($input['rules']) && is_array($input['rules']) ? $input['rules'] : [];
        $rules = [];
        foreach ($raw_rules as $raw_rule) {
            $rule = bw_tbl_sanitize_product_archive_rule($raw_rule);
            if ($rule['template_id'] <= 0) {
                continue;
            }
            $rules[] = $rule;
        }

        return [
            'enabled' => $enabled,
            'rules' => array_values($rules),
        ];
    }
}

if (!function_exists('bw_tbl_get_product_archive_rules_option')) {
    function bw_tbl_get_product_archive_rules_option()
    {
        $saved = get_option(BW_TBL_PRODUCT_ARCHIVE_RULES_OPTION, []);
        return bw_tbl_sanitize_product_archive_rules_option($saved);
    }
}

if (!function_exists('bw_tbl_get_product_archive_rules_template_ids')) {
    function bw_tbl_get_product_archive_rules_template_ids($option = null)
    {
        $option = is_array($option) ? $option : bw_tbl_get_product_archive_rules_option();
        $rules = isset($option['rules']) && is_array($option['rules']) ? $option['rules'] : [];

        $template_ids = [];
        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $template_id = isset($rule['template_id']) ? absint($rule['template_id']) : 0;
            if ($template_id <= 0) {
                continue;
            }
            if (!bw_tbl_is_valid_product_archive_template($template_id)) {
                continue;
            }
            $template_ids[$template_id] = $template_id;
        }

        return array_values($template_ids);
    }
}

if (!function_exists('bw_tbl_get_product_archive_template_choices')) {
    function bw_tbl_get_product_archive_template_choices()
    {
        $query = new WP_Query(
            [
                'post_type' => 'bw_template',
                'post_status' => 'publish',
                'posts_per_page' => 200,
                'fields' => 'ids',
                'orderby' => 'title',
                'order' => 'ASC',
                'no_found_rows' => true,
                'meta_query' => [
                    [
                        'key' => 'bw_template_type',
                        'value' => 'product_archive',
                    ],
                ],
            ]
        );

        $choices = [];
        foreach ($query->posts as $template_id) {
            $template_id = absint($template_id);
            if ($template_id <= 0) {
                continue;
            }

            $title = get_the_title($template_id);
            if (!is_string($title) || '' === trim($title)) {
                $title = sprintf(__('Template #%d', 'bw'), $template_id);
            }

            $choices[$template_id] = $title;
        }

        return $choices;
    }
}

if (!function_exists('bw_tbl_runtime_resolve_product_archive_settings_winner')) {
    function bw_tbl_runtime_resolve_product_archive_settings_winner($context = [])
    {
        $kind = isset($context['product_archive_kind']) ? sanitize_key((string) $context['product_archive_kind']) : '';
        if ('product_cat' !== $kind) {
            return [
                'handled' => false,
                'winner_id' => 0,
            ];
        }

        $option = bw_tbl_get_product_archive_rules_option();
        if (empty($option['enabled'])) {
            return [
                'handled' => false,
                'winner_id' => 0,
            ];
        }

        $rules = isset($option['rules']) && is_array($option['rules']) ? $option['rules'] : [];
        if (empty($rules)) {
            return [
                'handled' => true,
                'winner_id' => 0,
            ];
        }

        $term_id = isset($context['product_archive_term_id']) ? absint($context['product_archive_term_id']) : 0;
        $expanded_term_ids = [];
        if ($term_id > 0) {
            $expanded_term_ids[$term_id] = $term_id;
            $ancestors = get_ancestors($term_id, 'product_cat', 'taxonomy');
            if (is_array($ancestors)) {
                foreach ($ancestors as $ancestor_id) {
                    $ancestor_id = absint($ancestor_id);
                    if ($ancestor_id > 0) {
                        $expanded_term_ids[$ancestor_id] = $ancestor_id;
                    }
                }
            }
        }
        $term_map = array_fill_keys(array_values($expanded_term_ids), true);

        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $sanitized_rule = bw_tbl_sanitize_product_archive_rule($rule);
            $template_id = isset($sanitized_rule['template_id']) ? absint($sanitized_rule['template_id']) : 0;
            if ($template_id <= 0) {
                continue;
            }

            $excluded = false;
            $exclude = isset($sanitized_rule['exclude_product_cat']) && is_array($sanitized_rule['exclude_product_cat']) ? $sanitized_rule['exclude_product_cat'] : [];
            foreach ($exclude as $exclude_term_id) {
                $exclude_term_id = absint($exclude_term_id);
                if ($exclude_term_id > 0 && isset($term_map[$exclude_term_id])) {
                    $excluded = true;
                    break;
                }
            }
            if ($excluded) {
                continue;
            }

            $include = isset($sanitized_rule['include_product_cat']) && is_array($sanitized_rule['include_product_cat']) ? $sanitized_rule['include_product_cat'] : [];
            if (empty($include)) {
                return [
                    'handled' => true,
                    'winner_id' => $template_id,
                ];
            }

            foreach ($include as $include_term_id) {
                $include_term_id = absint($include_term_id);
                if ($include_term_id > 0 && isset($term_map[$include_term_id])) {
                    return [
                        'handled' => true,
                        'winner_id' => $template_id,
                    ];
                }
            }
        }

        return [
            'handled' => true,
            'winner_id' => 0,
        ];
    }
}
