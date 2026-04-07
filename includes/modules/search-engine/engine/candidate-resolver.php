<?php
if (!defined('ABSPATH')) {
    exit;
}

function bw_fpw_get_filtered_post_ids_for_tags($post_type, $category, $subcategories, $year_from = null, $year_to = null, $context_slug = '', $advanced_filters = [])
{
    return bw_fpw_get_candidate_post_ids_without_search(
        $post_type,
        $category,
        $subcategories,
        [],
        $year_from,
        $year_to,
        $context_slug,
        $advanced_filters,
        bw_fpw_get_tag_source_posts_limit()
    );
}

function bw_fpw_get_candidate_post_ids_without_search($post_type, $category, $subcategories = [], $tags = [], $year_from = null, $year_to = null, $context_slug = '', $advanced_filters = [], $posts_per_page = null)
{
    $max_candidates = bw_fpw_get_max_candidate_set_size();
    $resolved_posts_per_page = is_numeric($posts_per_page) ? (int) $posts_per_page : $max_candidates;

    if ($resolved_posts_per_page <= 0) {
        $resolved_posts_per_page = $max_candidates;
    }

    if ($max_candidates > 0) {
        $resolved_posts_per_page = min($resolved_posts_per_page, $max_candidates);
    }

    $query_args = [
        'post_type' => $post_type,
        'post_status' => 'publish',
        'posts_per_page' => $resolved_posts_per_page,
        'paged' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ];

    $tax_query = bw_fpw_build_tax_query($post_type, $category, $subcategories, $tags);

    if (!empty($tax_query)) {
        $query_args['tax_query'] = $tax_query;
    }

    if (null !== $year_from || null !== $year_to) {
        $canonical_year_key = bw_fpw_get_canonical_year_meta_key();

        if (null !== $year_from && null !== $year_to) {
            $query_args['meta_query'] = [[
                'key' => $canonical_year_key,
                'value' => [(int) $year_from, (int) $year_to],
                'compare' => 'BETWEEN',
                'type' => 'NUMERIC',
            ]];
        } elseif (null !== $year_from) {
            $query_args['meta_query'] = [[
                'key' => $canonical_year_key,
                'value' => (int) $year_from,
                'compare' => '>=',
                'type' => 'NUMERIC',
            ]];
        } else {
            $query_args['meta_query'] = [[
                'key' => $canonical_year_key,
                'value' => (int) $year_to,
                'compare' => '<=',
                'type' => 'NUMERIC',
            ]];
        }
    }

    $query = new WP_Query($query_args);
    $post_ids = array_values(array_map('absint', (array) $query->posts));

    if (!bw_fpw_has_active_advanced_filter_selections($advanced_filters)) {
        return $post_ids;
    }

    return bw_fpw_apply_advanced_filters_to_post_ids($post_ids, $context_slug, $advanced_filters);
}
