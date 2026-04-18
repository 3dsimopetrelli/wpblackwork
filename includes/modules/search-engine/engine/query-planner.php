<?php
if (!defined('ABSPATH')) {
    exit;
}

function bw_fpw_resolve_sort_config($sort_key, $default_order_by, $default_order, $post_type = 'product')
{
    $normalized_sort_key = bw_fpw_normalize_sort_key($sort_key);
    $effective_order_by = bw_fpw_normalize_order_by($default_order_by);
    $effective_order = bw_fpw_normalize_order($default_order);
    $query_args = [
        'orderby' => $effective_order_by,
        'order' => $effective_order,
    ];

    switch ($normalized_sort_key) {
        case 'newest':
            $effective_order_by = 'date';
            $effective_order = 'DESC';
            $query_args = [
                'orderby' => 'date',
                'order' => 'DESC',
            ];
            break;
        case 'oldest':
            $effective_order_by = 'date';
            $effective_order = 'ASC';
            $query_args = [
                'orderby' => 'date',
                'order' => 'ASC',
            ];
            break;
        case 'title_asc':
            $effective_order_by = 'title';
            $effective_order = 'ASC';
            $query_args = [
                'orderby' => 'title',
                'order' => 'ASC',
            ];
            break;
        case 'title_desc':
            $effective_order_by = 'title';
            $effective_order = 'DESC';
            $query_args = [
                'orderby' => 'title',
                'order' => 'DESC',
            ];
            break;
        case 'year_asc':
        case 'year_desc':
            if ('product' !== $post_type) {
                $effective_order_by = 'date';
                $effective_order = 'DESC';
                $query_args = [
                    'orderby' => 'date',
                    'order' => 'DESC',
                ];
                break;
            }

            $effective_order_by = 'meta_value_num';
            $effective_order = 'year_asc' === $normalized_sort_key ? 'ASC' : 'DESC';
            $query_args = [
                'meta_key' => bw_fpw_get_canonical_year_meta_key(),
                'meta_type' => 'NUMERIC',
                'orderby' => [
                    'meta_value_num' => $effective_order,
                    'date' => 'DESC',
                    'ID' => 'DESC',
                ],
                'order' => $effective_order,
            ];
            break;
        default:
            break;
    }

    return [
        'sort_key' => $normalized_sort_key,
        'effective_order_by' => $effective_order_by,
        'effective_order' => $effective_order,
        'query_args' => $query_args,
    ];
}

function bw_fpw_build_tax_query($post_type, $category = 'all', $subcategories = [], $tags = [])
{
    $taxonomy = 'product' === $post_type ? 'product_cat' : 'category';
    $tag_taxonomy = 'product' === $post_type ? 'product_tag' : 'post_tag';
    $tax_query = [];

    if ('all' !== $category && absint($category) > 0) {
        if (!empty($subcategories)) {
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field' => 'term_id',
                'terms' => $subcategories,
            ];
        } else {
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field' => 'term_id',
                'terms' => [absint($category)],
            ];
        }
    } elseif (!empty($subcategories)) {
        // category = 'all' with explicit subcategory IDs: filter directly by those term IDs.
        // Used by the search surface when ?category=<slug> maps to a subcategory selection.
        $tax_query[] = [
            'taxonomy' => $taxonomy,
            'field' => 'term_id',
            'terms' => $subcategories,
        ];
    }

    if (!empty($tags)) {
        $tax_query[] = [
            'taxonomy' => $tag_taxonomy,
            'field' => 'term_id',
            'terms' => $tags,
        ];
    }

    if (count($tax_query) > 1) {
        $tax_query['relation'] = 'AND';
    }

    return $tax_query;
}

function bw_fpw_build_engine_query_args($request, $sort_config, $is_append)
{
    $per_page = isset($request['per_page']) ? (int) $request['per_page'] : bw_fpw_get_default_per_page();
    $offset = isset($request['offset']) ? (int) $request['offset'] : 0;
    $query_posts_per_page = $per_page > 0 ? $per_page + 1 : -1;
    $query_args = [
        'post_type' => $request['post_type'],
        'posts_per_page' => $query_posts_per_page,
        'post_status' => 'publish',
        'no_found_rows' => $is_append && empty($request['include_filter_ui']),
        'ignore_sticky_posts' => true,
    ];

    foreach ((array) $sort_config['query_args'] as $query_arg_key => $query_arg_value) {
        $query_args[$query_arg_key] = $query_arg_value;
    }

    if ('year_int' === $sort_config['effective_order_by']) {
        $query_args['orderby'] = 'meta_value_num';
        $query_args['meta_key'] = bw_fpw_get_canonical_year_meta_key();
    }

    if ($per_page > 0 && $offset > 0) {
        $query_args['offset'] = $offset;
    } else {
        $query_args['paged'] = isset($request['page']) ? (int) $request['page'] : 1;
    }

    $tax_query = bw_fpw_build_tax_query(
        $request['post_type'],
        $request['category'],
        $request['subcategories'],
        $request['tags']
    );
    if (!empty($tax_query)) {
        $query_args['tax_query'] = $tax_query;
    }

    if (null !== $request['year_from'] || null !== $request['year_to']) {
        $canonical_year_key = bw_fpw_get_canonical_year_meta_key();
        $meta_query = isset($query_args['meta_query']) && is_array($query_args['meta_query']) ? $query_args['meta_query'] : [];

        if (null !== $request['year_from'] && null !== $request['year_to']) {
            $meta_query[] = [
                'key' => $canonical_year_key,
                'value' => [(int) $request['year_from'], (int) $request['year_to']],
                'compare' => 'BETWEEN',
                'type' => 'NUMERIC',
            ];
        } elseif (null !== $request['year_from']) {
            $meta_query[] = [
                'key' => $canonical_year_key,
                'value' => (int) $request['year_from'],
                'compare' => '>=',
                'type' => 'NUMERIC',
            ];
        } else {
            $meta_query[] = [
                'key' => $canonical_year_key,
                'value' => (int) $request['year_to'],
                'compare' => '<=',
                'type' => 'NUMERIC',
            ];
        }

        $query_args['meta_query'] = $meta_query;
    }

    return $query_args;
}

function bw_fpw_is_context_root_scope($post_type, $category, $subcategories = [], $tags = [], $year_from = null, $year_to = null, $context_slug = '')
{
    if ('product' !== $post_type || !bw_fpw_is_supported_context_slug($context_slug)) {
        return false;
    }

    if (!empty($subcategories) || !empty($tags) || null !== $year_from || null !== $year_to) {
        return false;
    }

    if ('all' === $category || empty($category)) {
        return true;
    }

    $root_term_id = bw_fpw_get_context_root_term_id($context_slug);

    return $root_term_id > 0 && absint($category) === $root_term_id;
}
