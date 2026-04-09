<?php
if (!defined('ABSPATH')) {
    exit;
}

function bw_fpw_execute_search(array $request): array
{
    $request_profile = isset($request['request_profile']) ? bw_fpw_normalize_request_profile($request['request_profile']) : 'full';
    $request['request_profile'] = $request_profile;
    $sort_config = bw_fpw_resolve_sort_config(
        $request['sort_key'],
        $request['default_order_by'],
        $request['default_order'],
        $request['post_type']
    );
    $request['effective_order_by'] = $sort_config['effective_order_by'];
    $request['effective_order'] = $sort_config['effective_order'];
    $skip_cache = false;

    if ('rand' === $request['effective_order_by']) {
        $request['effective_order'] = 'ASC';
        $skip_cache = true;
    }

    $is_append = ($request['page'] > 1 || $request['offset'] > 0);
    $force_filter_ui = !empty($request['include_filter_ui']) && 'suggest' !== $request_profile;
    $effective_context_slug = $request['context_slug'];

    if ('' === $effective_context_slug && 'product' === $request['post_type'] && 'all' !== $request['category']) {
        $effective_context_slug = bw_fpw_resolve_product_family_slug_from_term_id(absint($request['category']), 'product_cat');
    }

    $request['effective_context_slug'] = $effective_context_slug;
    $cache_key = '';
    $cached_result = false;

    if (!$skip_cache) {
        $cache_key = bw_fpw_build_engine_cache_key($request);
        $cached_result = bw_fpw_get_cached_search_result($cache_key);
    }

    if (is_array($cached_result)) {
        $cached_result['is_append'] = $is_append;
        $cached_result['skip_cache'] = $skip_cache;
        $cached_result['effective_context_slug'] = $effective_context_slug;
        return $cached_result;
    }

    $query_args = bw_fpw_build_engine_query_args($request, $sort_config, $is_append);
    $is_suggest = 'suggest' === $request_profile;
    $base_candidate_post_ids = [];
    $final_candidate_post_ids = [];
    $filter_ui_candidate_post_ids = null;
    $php_sort_result_count = null;
    $has_active_advanced_filters = bw_fpw_has_active_advanced_filter_selections($request['advanced_filters']);
    $supports_advanced_filters = !empty(bw_fpw_get_supported_advanced_filter_groups_for_context($effective_context_slug));
    $should_build_filter_ui = !$is_suggest && (!$is_append || $force_filter_ui);
    $needs_refined_advanced_filter_scope = $should_build_filter_ui
        && $supports_advanced_filters
        && (
            '' !== $request['search']
            || $has_active_advanced_filters
            || !bw_fpw_is_context_root_scope(
                $request['post_type'],
                $request['category'],
                $request['subcategories'],
                $request['tags'],
                $request['year_from'],
                $request['year_to'],
                $effective_context_slug
            )
        );

    if ('product' === $request['post_type']) {
        if ('' !== $request['search']) {
            $base_candidate_post_ids = bw_fpw_get_matching_post_ids(
                $request['post_type'],
                $request['category'],
                $request['subcategories'],
                $request['tags'],
                $request['search'],
                $request['year_from'],
                $request['year_to'],
                $effective_context_slug,
                []
            );
        } elseif ($has_active_advanced_filters) {
            $base_candidate_post_ids = bw_fpw_get_candidate_post_ids_without_search(
                $request['post_type'],
                $request['category'],
                $request['subcategories'],
                $request['tags'],
                $request['year_from'],
                $request['year_to'],
                $effective_context_slug,
                []
            );
        }

        if ($needs_refined_advanced_filter_scope) {
            if ('' !== $request['search'] || $has_active_advanced_filters) {
                $filter_ui_candidate_post_ids = $base_candidate_post_ids;
            } else {
                $filter_ui_candidate_post_ids = bw_fpw_get_candidate_post_ids_without_search(
                    $request['post_type'],
                    $request['category'],
                    $request['subcategories'],
                    $request['tags'],
                    $request['year_from'],
                    $request['year_to'],
                    $effective_context_slug,
                    []
                );
            }
        }
    }

    if ($is_suggest) {
        $query_args['no_found_rows'] = true;
    }

    $use_php_year_sort = 'year_int' === $request['effective_order_by']
        && bw_fpw_is_supported_context_slug($effective_context_slug)
        && ($has_active_advanced_filters || '' !== $request['search']);

    if ($use_php_year_sort) {
        // TODO: extract this PHP-sort execution path to a dedicated execution-planner surface in Phase 2 before adding new execution paths.
        if ($has_active_advanced_filters) {
            $candidate_ids = bw_fpw_apply_advanced_filters_to_post_ids($base_candidate_post_ids, $effective_context_slug, $request['advanced_filters']);
        } else {
            $candidate_ids = $base_candidate_post_ids;
        }

        if (count($candidate_ids) <= bw_fpw_get_php_sort_max_ids()) {
            unset($query_args['orderby'], $query_args['meta_key'], $query_args['offset'], $query_args['paged']);

            $post_map = bw_fpw_get_year_postmap($effective_context_slug);
            $sort_direction = ('ASC' === $request['effective_order']) ? 1 : -1;
            $missing_year_sentinel = ('ASC' === $request['effective_order']) ? PHP_INT_MAX : 0;
            usort($candidate_ids, static function ($a, $b) use ($post_map, $sort_direction, $missing_year_sentinel) {
                $ya = isset($post_map[$a]) ? $post_map[$a] : $missing_year_sentinel;
                $yb = isset($post_map[$b]) ? $post_map[$b] : $missing_year_sentinel;
                if ($ya === $yb) {
                    return 0;
                }
                return $sort_direction * ($ya < $yb ? -1 : 1);
            });

            $php_sort_result_count = count($candidate_ids);
            $page_ids = $request['per_page'] > 0
                ? array_slice($candidate_ids, $request['offset'], $request['per_page'] + 1)
                : $candidate_ids;

            $has_more = $request['per_page'] > 0 && count($page_ids) > $request['per_page'];
            $response_page = $request['per_page'] > 0 ? (int) floor($request['offset'] / $request['per_page']) + 1 : $request['page'];
            $next_page = $has_more ? $response_page + 1 : 0;

            if ($has_more) {
                $page_ids = array_slice($page_ids, 0, $request['per_page']);
            }

            $query_args['post__in'] = !empty($page_ids) ? array_map('absint', $page_ids) : [0];
            $query_args['orderby'] = 'post__in';
            $query_args['posts_per_page'] = !empty($page_ids) ? count($page_ids) : 1;
            $query_args['no_found_rows'] = true;

            $query = new WP_Query($query_args);
            $has_posts = !empty($page_ids) && $query->have_posts();
        } else {
            $candidate_ids = bw_fpw_prepare_post_in_values($candidate_ids);
            $query_args['post__in'] = !empty($candidate_ids) ? $candidate_ids : [0];

            $query = new WP_Query($query_args);

            $has_posts = $query->have_posts();
            $has_more = $request['per_page'] > 0 && $has_posts && $query->post_count > $request['per_page'];
            $response_page = $request['per_page'] > 0 ? (int) floor($request['offset'] / $request['per_page']) + 1 : $request['page'];
            $next_page = $has_more ? $response_page + 1 : 0;
        }
    } else {
        if ($has_active_advanced_filters) {
            $final_candidate_post_ids = bw_fpw_apply_advanced_filters_to_post_ids($base_candidate_post_ids, $effective_context_slug, $request['advanced_filters']);

            $final_candidate_post_ids = bw_fpw_prepare_post_in_values($final_candidate_post_ids);

            if (empty($final_candidate_post_ids)) {
                $query_args['post__in'] = [0];
            } else {
                $query_args['post__in'] = $final_candidate_post_ids;
            }
        } elseif ('' !== $request['search']) {
            $base_candidate_post_ids = bw_fpw_prepare_post_in_values($base_candidate_post_ids);

            if (empty($base_candidate_post_ids)) {
                $query_args['post__in'] = [0];
            } else {
                $query_args['post__in'] = $base_candidate_post_ids;
            }
        }

        $query = new WP_Query($query_args);

        $has_posts = $query->have_posts();
        $has_more = $request['per_page'] > 0 && $has_posts && $query->post_count > $request['per_page'];
        $response_page = $request['per_page'] > 0 ? (int) floor($request['offset'] / $request['per_page']) + 1 : $request['page'];
        $next_page = $has_more ? $response_page + 1 : 0;
    }

    $rendered_posts = 0;
    $page_post_ids = [];

    if ($has_posts) {
        while ($query->have_posts()) {
            $query->the_post();

            if ($request['per_page'] > 0 && $rendered_posts >= $request['per_page']) {
                break;
            }

            $page_post_ids[] = get_the_ID();
            $rendered_posts++;
        }
    }

    wp_reset_postdata();

    if (null !== $php_sort_result_count) {
        $result_count = $php_sort_result_count;
    } elseif ($is_suggest) {
        $result_count = null;
    } else {
        $result_count = ($is_append && !$should_build_filter_ui) ? null : (int) $query->found_posts;
    }

    $result = [
        'page_post_ids' => array_values(array_map('absint', $page_post_ids)),
        'result_count' => $result_count,
        'has_posts' => $has_posts,
        'page' => $response_page,
        'per_page' => $request['per_page'],
        'has_more' => $has_more,
        'next_page' => $next_page,
        'offset' => $request['offset'],
        'loaded_count' => $request['per_page'] > 0 ? $request['offset'] + $rendered_posts : $rendered_posts,
        'next_offset' => $has_more ? $request['offset'] + $rendered_posts : 0,
        'is_append' => $is_append,
        'skip_cache' => $skip_cache,
        'effective_context_slug' => $effective_context_slug,
    ];

    if ($should_build_filter_ui) {
        $result = array_merge(
            $result,
            bw_fpw_build_filter_ui_payload(
                $request['post_type'],
                $request['category'],
                $request['subcategories'],
                $request['tags'],
                $request['search'],
                $request['year_from'],
                $request['year_to'],
                $effective_context_slug,
                $request['advanced_filters'],
                $filter_ui_candidate_post_ids,
                $needs_refined_advanced_filter_scope,
                $result_count
            )
        );
    }

    if (!$skip_cache && '' !== $cache_key) {
        bw_fpw_store_cached_search_result(
            $cache_key,
            array_diff_key(
                $result,
                [
                    'is_append' => true,
                    'skip_cache' => true,
                    'effective_context_slug' => true,
                ]
            )
        );
    }

    return $result;
}
