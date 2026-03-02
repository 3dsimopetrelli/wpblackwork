<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_tbl_runtime_should_bypass_template_resolver')) {
    function bw_tbl_runtime_should_bypass_template_resolver()
    {
        if (is_admin() || wp_doing_ajax() || is_feed() || is_embed()) {
            return true;
        }

        if (is_singular('bw_template')) {
            return true;
        }

        if (function_exists('bw_tbl_is_elementor_preview') && bw_tbl_is_elementor_preview()) {
            return true;
        }

        if (function_exists('bw_tbl_is_elementor_editor_request') && bw_tbl_is_elementor_editor_request()) {
            return true;
        }

        if (function_exists('is_cart') && is_cart()) {
            return true;
        }

        if (function_exists('is_checkout') && is_checkout()) {
            return true;
        }

        if (function_exists('is_account_page') && is_account_page()) {
            return true;
        }

        if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url()) {
            return true;
        }

        return false;
    }
}

if (!function_exists('bw_tbl_runtime_resolve_request_template_type')) {
    function bw_tbl_runtime_resolve_request_template_type()
    {
        if (is_404()) {
            return 'error_404';
        }

        if (is_search()) {
            return 'search';
        }

        if (is_singular('post')) {
            return 'single_post';
        }

        if (is_page()) {
            return 'single_page';
        }

        if (function_exists('is_shop') && is_shop()) {
            return '';
        }

        if (function_exists('is_product_taxonomy') && is_product_taxonomy()) {
            return '';
        }

        if (function_exists('is_post_type_archive') && is_post_type_archive('product')) {
            return '';
        }

        if (is_home() || is_archive()) {
            return 'archive';
        }

        return '';
    }
}

if (!function_exists('bw_tbl_runtime_get_template_priority')) {
    function bw_tbl_runtime_get_template_priority($template_id)
    {
        $priority = get_post_meta($template_id, 'bw_template_priority', true);
        $priority = is_numeric($priority) ? (int) $priority : 10;
        return $priority;
    }
}

if (!function_exists('bw_tbl_runtime_build_context')) {
    function bw_tbl_runtime_build_context($template_type)
    {
        $template_type = sanitize_key((string) $template_type);
        $context = [
            'template_type' => $template_type,
            'post_id' => 0,
            'page_id' => 0,
            'post_category_term_ids' => [],
            'archive_kind' => '',
            'archive_term_id' => 0,
            'archive_post_types' => [],
        ];

        if ('single_post' === $template_type) {
            $post_id = get_queried_object_id();
            $post_id = absint($post_id);
            $context['post_id'] = $post_id;

            if ($post_id > 0) {
                $terms = wp_get_post_terms($post_id, 'category', ['fields' => 'ids']);
                if (is_array($terms)) {
                    $term_ids = [];
                    foreach ($terms as $term_id) {
                        $term_id = absint($term_id);
                        if ($term_id > 0) {
                            $term_ids[$term_id] = $term_id;
                        }
                    }
                    $context['post_category_term_ids'] = array_values($term_ids);
                }
            }
        } elseif ('single_page' === $template_type) {
            $context['page_id'] = absint(get_queried_object_id());
        } elseif ('archive' === $template_type) {
            if (is_home()) {
                $context['archive_kind'] = 'blog';
            } elseif (is_category()) {
                $term = get_queried_object();
                $context['archive_kind'] = 'category';
                $context['archive_term_id'] = isset($term->term_id) ? absint($term->term_id) : 0;
            } elseif (is_tag()) {
                $term = get_queried_object();
                $context['archive_kind'] = 'tag';
                $context['archive_term_id'] = isset($term->term_id) ? absint($term->term_id) : 0;
            } elseif (is_post_type_archive()) {
                $context['archive_kind'] = 'post_type';
                $raw_post_type = get_query_var('post_type');
                $post_types = is_array($raw_post_type) ? $raw_post_type : [$raw_post_type];
                $normalized = [];
                foreach ($post_types as $post_type) {
                    $post_type = sanitize_key((string) $post_type);
                    if ('' === $post_type || 'product' === $post_type || 'bw_template' === $post_type) {
                        continue;
                    }
                    $normalized[$post_type] = $post_type;
                }
                $context['archive_post_types'] = array_values($normalized);
            } else {
                $context['archive_kind'] = 'generic';
            }
        }

        return $context;
    }
}

if (!function_exists('bw_tbl_runtime_get_candidates')) {
    function bw_tbl_runtime_get_candidates($template_type, $context = [])
    {
        $template_type = sanitize_key((string) $template_type);
        if ('' === $template_type) {
            return [];
        }

        $query = new WP_Query(
            [
                'post_type' => 'bw_template',
                'post_status' => 'publish',
                'posts_per_page' => 200,
                'fields' => 'ids',
                'no_found_rows' => true,
                'orderby' => 'ID',
                'order' => 'ASC',
                'meta_query' => [
                    [
                        'key' => 'bw_template_type',
                        'value' => $template_type,
                    ],
                ],
            ]
        );

        $candidates = [];
        foreach ($query->posts as $template_id) {
            $template_id = absint($template_id);
            if ($template_id <= 0) {
                continue;
            }

            if (!bw_tbl_template_matches_rules($template_id, $context, null)) {
                continue;
            }

            $candidates[] = [
                'id' => $template_id,
                'priority' => bw_tbl_runtime_get_template_priority($template_id),
            ];
        }

        if (empty($candidates)) {
            return [];
        }

        usort(
            $candidates,
            static function ($a, $b) {
                $priority_a = isset($a['priority']) ? (int) $a['priority'] : 10;
                $priority_b = isset($b['priority']) ? (int) $b['priority'] : 10;
                if ($priority_a !== $priority_b) {
                    return $priority_b <=> $priority_a;
                }

                $id_a = isset($a['id']) ? (int) $a['id'] : 0;
                $id_b = isset($b['id']) ? (int) $b['id'] : 0;
                return $id_a <=> $id_b;
            }
        );

        return $candidates;
    }
}

if (!function_exists('bw_tbl_runtime_select_winner')) {
    function bw_tbl_runtime_select_winner($template_type, $context = [])
    {
        $candidates = bw_tbl_runtime_get_candidates($template_type, $context);
        if (empty($candidates)) {
            return 0;
        }

        $winner = $candidates[0];
        $winner_id = isset($winner['id']) ? absint($winner['id']) : 0;
        return $winner_id > 0 ? $winner_id : 0;
    }
}

if (!function_exists('bw_tbl_runtime_resolve_template_include')) {
    function bw_tbl_runtime_resolve_template_include($template)
    {
        if (!bw_tbl_is_feature_enabled('templates_enabled')) {
            return $template;
        }

        if (bw_tbl_runtime_should_bypass_template_resolver()) {
            return $template;
        }

        $template_type = bw_tbl_runtime_resolve_request_template_type();
        if ('' === $template_type) {
            return $template;
        }

        $context = bw_tbl_runtime_build_context($template_type);
        $winner_id = bw_tbl_runtime_select_winner($template_type, $context);
        if ($winner_id <= 0) {
            return $template;
        }

        $rendered = bw_tbl_runtime_render_template_content($winner_id);
        if (!is_string($rendered) || '' === trim($rendered)) {
            return $template;
        }

        $wrapper_template = bw_tbl_runtime_wrapper_template_path();
        if (!is_string($wrapper_template) || '' === $wrapper_template || !file_exists($wrapper_template)) {
            return $template;
        }

        bw_tbl_runtime_set_active_template($winner_id, $template_type);

        return $wrapper_template;
    }
}
add_filter('template_include', 'bw_tbl_runtime_resolve_template_include', 50);
