<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BW_TBL_SINGLE_PRODUCT_OPTION')) {
    define('BW_TBL_SINGLE_PRODUCT_OPTION', 'bw_theme_builder_lite_single_product_v1');
}

if (!function_exists('bw_tbl_runtime_debug_enabled')) {
    function bw_tbl_runtime_debug_enabled()
    {
        return defined('BW_TBL_DEBUG') && BW_TBL_DEBUG;
    }
}

if (!function_exists('bw_tbl_runtime_debug_log')) {
    function bw_tbl_runtime_debug_log($message, $context = [])
    {
        if (!bw_tbl_runtime_debug_enabled()) {
            return;
        }

        $suffix = '';
        if (is_array($context) && !empty($context)) {
            $suffix = ' ' . wp_json_encode($context);
        }

        error_log('[BW TBL Runtime] ' . (string) $message . $suffix); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
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

if (!function_exists('bw_tbl_default_single_product_option')) {
    function bw_tbl_default_single_product_option()
    {
        return [
            'enabled' => 0,
            'active_single_product_template_id' => 0,
            'include_product_cat' => [],
            'exclude_product_cat' => [],
        ];
    }
}

if (!function_exists('bw_tbl_get_single_product_option')) {
    function bw_tbl_get_single_product_option()
    {
        $saved = get_option(BW_TBL_SINGLE_PRODUCT_OPTION, []);
        if (!is_array($saved)) {
            $saved = [];
        }

        return array_replace(bw_tbl_default_single_product_option(), $saved);
    }
}

if (!function_exists('bw_tbl_is_valid_single_product_template')) {
    function bw_tbl_is_valid_single_product_template($template_id)
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

        return 'single_product' === $type;
    }
}

if (!function_exists('bw_tbl_sanitize_single_product_option')) {
    function bw_tbl_sanitize_single_product_option($input)
    {
        $input = is_array($input) ? $input : [];

        $enabled = !empty($input['enabled']) ? 1 : 0;
        $active_template_id = isset($input['active_single_product_template_id']) ? absint($input['active_single_product_template_id']) : 0;
        if (!bw_tbl_is_valid_single_product_template($active_template_id)) {
            $active_template_id = 0;
        }

        $include_product_cat = isset($input['include_product_cat']) && is_array($input['include_product_cat'])
            ? bw_tbl_filter_parent_product_cat_ids($input['include_product_cat'])
            : [];
        $exclude_product_cat = isset($input['exclude_product_cat']) && is_array($input['exclude_product_cat'])
            ? bw_tbl_filter_parent_product_cat_ids($input['exclude_product_cat'])
            : [];

        return [
            'enabled' => $enabled,
            'active_single_product_template_id' => $active_template_id,
            'include_product_cat' => $include_product_cat,
            'exclude_product_cat' => $exclude_product_cat,
        ];
    }
}

if (!function_exists('bw_tbl_get_single_product_template_choices')) {
    function bw_tbl_get_single_product_template_choices()
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
                        'value' => 'single_product',
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

if (!function_exists('bw_tbl_get_parent_product_category_choices')) {
    function bw_tbl_get_parent_product_category_choices()
    {
        $terms = get_terms(
            [
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
                'parent' => 0,
                'orderby' => 'name',
                'order' => 'ASC',
            ]
        );

        $choices = [];
        if (is_wp_error($terms) || !is_array($terms)) {
            return $choices;
        }

        foreach ($terms as $term) {
            if (!($term instanceof WP_Term)) {
                continue;
            }

            $choices[(int) $term->term_id] = (string) $term->name;
        }

        return $choices;
    }
}

if (!function_exists('bw_tbl_runtime_resolve_single_product_settings_winner')) {
    function bw_tbl_runtime_resolve_single_product_settings_winner($context = [])
    {
        $option = bw_tbl_get_single_product_option();

        // New settings surface takes precedence only when explicitly enabled.
        if (empty($option['enabled'])) {
            bw_tbl_runtime_debug_log('single_product settings path disabled');
            return [
                'handled' => false,
                'winner_id' => 0,
            ];
        }

        $template_id = isset($option['active_single_product_template_id']) ? absint($option['active_single_product_template_id']) : 0;
        if (!bw_tbl_is_valid_single_product_template($template_id)) {
            bw_tbl_runtime_debug_log('single_product settings invalid active template', ['template_id' => $template_id]);
            return [
                'handled' => true,
                'winner_id' => 0,
            ];
        }

        $product_cat_ids = isset($context['product_category_term_ids']) && is_array($context['product_category_term_ids'])
            ? array_values(array_filter(array_map('absint', $context['product_category_term_ids'])))
            : [];
        $expanded_product_cat_ids = [];
        foreach ($product_cat_ids as $term_id) {
            $term_id = absint($term_id);
            if ($term_id <= 0) {
                continue;
            }

            $expanded_product_cat_ids[$term_id] = $term_id;

            // Parent-only rules must also match products assigned to child terms.
            $ancestors = get_ancestors($term_id, 'product_cat', 'taxonomy');
            if (!is_array($ancestors)) {
                continue;
            }

            foreach ($ancestors as $ancestor_id) {
                $ancestor_id = absint($ancestor_id);
                if ($ancestor_id > 0) {
                    $expanded_product_cat_ids[$ancestor_id] = $ancestor_id;
                }
            }
        }
        $product_cat_map = array_fill_keys(array_values($expanded_product_cat_ids), true);

        $exclude = isset($option['exclude_product_cat']) && is_array($option['exclude_product_cat']) ? $option['exclude_product_cat'] : [];
        foreach ($exclude as $term_id) {
            $term_id = absint($term_id);
            if ($term_id > 0 && isset($product_cat_map[$term_id])) {
                bw_tbl_runtime_debug_log(
                    'single_product settings excluded by category',
                    ['template_id' => $template_id, 'term_id' => $term_id]
                );
                return [
                    'handled' => true,
                    'winner_id' => 0,
                ];
            }
        }

        $include = isset($option['include_product_cat']) && is_array($option['include_product_cat']) ? $option['include_product_cat'] : [];
        if (!empty($include)) {
            $matched = false;
            foreach ($include as $term_id) {
                $term_id = absint($term_id);
                if ($term_id > 0 && isset($product_cat_map[$term_id])) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                bw_tbl_runtime_debug_log(
                    'single_product settings include miss',
                    ['template_id' => $template_id, 'include' => $include, 'product_cats' => array_keys($product_cat_map)]
                );
                return [
                    'handled' => true,
                    'winner_id' => 0,
                ];
            }
        }

        bw_tbl_runtime_debug_log(
            'single_product settings match',
            ['template_id' => $template_id, 'product_cats' => array_keys($product_cat_map)]
        );
        return [
            'handled' => true,
            'winner_id' => $template_id,
        ];
    }
}
