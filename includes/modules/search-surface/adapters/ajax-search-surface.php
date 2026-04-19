<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function bw_ss_build_overlay_suggest_request( $query, $scope ) {
    return bw_fpw_build_engine_request(
        [
            'widget_id'        => 'bw-search-surface-overlay',
            'post_type'        => 'product',
            'context_slug'     => bw_ss_get_scope_context_slug( $scope ),
            'category'         => bw_ss_get_scope_default_category( $scope ),
            'search_enabled'   => 'yes',
            'search'           => $query,
            'per_page'         => 6,
            'page'             => 1,
            'offset'           => 0,
            'request_profile'  => 'suggest',
        ]
    );
}

function bw_ss_normalize_overlay_group_key( $group_key, $scope = 'all' ) {
    $group_key = sanitize_key( (string) $group_key );
    $scope     = bw_ss_normalize_scope_param( $scope );
    $groups    = function_exists( 'bw_ss_get_overlay_sidebar_groups_map' ) ? bw_ss_get_overlay_sidebar_groups_map() : [];
    $allowed   = isset( $groups[ $scope ] ) && is_array( $groups[ $scope ] ) ? $groups[ $scope ] : ( $groups['all'] ?? [] );

    foreach ( $allowed as $group ) {
        if ( $group_key === ( $group['key'] ?? '' ) ) {
            return $group_key;
        }
    }

    return '';
}

function bw_ss_get_overlay_candidate_post_ids( $scope, $query = '' ) {
    $scope        = bw_ss_normalize_scope_param( $scope );
    $context_slug = bw_ss_get_scope_context_slug( $scope );
    $category     = bw_ss_get_scope_default_category( $scope );
    $search       = function_exists( 'bw_fpw_normalize_search_query' )
        ? bw_fpw_normalize_search_query( $query )
        : sanitize_text_field( (string) $query );

    if ( '' !== $search && function_exists( 'bw_fpw_get_matching_post_ids' ) ) {
        return bw_fpw_get_matching_post_ids( 'product', $category, [], [], $search, null, null, $context_slug, [] );
    }

    if ( function_exists( 'bw_fpw_get_candidate_post_ids_without_search' ) ) {
        return bw_fpw_get_candidate_post_ids_without_search( 'product', $category, [], [], null, null, $context_slug, [] );
    }

    return [];
}

function bw_ss_build_overlay_category_items( $scope, $query = '' ) {
    $scope        = bw_ss_normalize_scope_param( $scope );
    $context_slug = bw_ss_get_scope_context_slug( $scope );
    $category     = bw_ss_get_scope_default_category( $scope );
    $items        = function_exists( 'bw_fpw_get_available_subcategories_data' )
        ? bw_fpw_get_available_subcategories_data( 'product', $category, [], $query, null, null, $context_slug, [] )
        : [];
    $results      = [];

    foreach ( (array) $items as $item ) {
        $term = get_term( isset( $item['term_id'] ) ? (int) $item['term_id'] : 0, 'product_cat' );

        if ( ! $term instanceof WP_Term || is_wp_error( $term ) ) {
            continue;
        }

        $results[] = [
            'label' => $term->name,
            'count' => isset( $item['count'] ) ? (int) $item['count'] : 0,
            'url'   => bw_ss_build_search_results_navigation_url(
                $query,
                $scope,
                [ 'category' => $term->slug ]
            ),
        ];
    }

    return $results;
}

function bw_ss_build_overlay_tag_items( $scope, $query = '' ) {
    $scope        = bw_ss_normalize_scope_param( $scope );
    $context_slug = bw_ss_get_scope_context_slug( $scope );
    $category     = bw_ss_get_scope_default_category( $scope );
    $items        = function_exists( 'bw_fpw_get_related_tags_data' )
        ? bw_fpw_get_related_tags_data( 'product', $category, [], $query, null, null, $context_slug, [] )
        : [];
    $results      = [];

    foreach ( (array) $items as $item ) {
        $term = get_term( isset( $item['term_id'] ) ? (int) $item['term_id'] : 0, 'product_tag' );

        if ( ! $term instanceof WP_Term || is_wp_error( $term ) ) {
            continue;
        }

        $results[] = [
            'label' => $term->name,
            'count' => isset( $item['count'] ) ? (int) $item['count'] : 0,
            'url'   => bw_ss_build_search_results_navigation_url(
                $query,
                $scope,
                [ 'tag' => $term->slug ]
            ),
        ];
    }

    return $results;
}

function bw_ss_build_overlay_year_items( $scope, $query = '' ) {
    $scope        = bw_ss_normalize_scope_param( $scope );
    $context_slug = bw_ss_get_scope_context_slug( $scope );
    $search       = function_exists( 'bw_fpw_normalize_search_query' )
        ? bw_fpw_normalize_search_query( $query )
        : sanitize_text_field( (string) $query );
    $counts       = [];

    if ( '' === $search && function_exists( 'bw_fpw_get_year_index' ) ) {
        $year_index = bw_fpw_get_year_index( $context_slug );
        $counts     = isset( $year_index['years'] ) && is_array( $year_index['years'] ) ? $year_index['years'] : [];
    } elseif ( function_exists( 'bw_fpw_get_year_postmap' ) ) {
        $post_map = bw_fpw_get_year_postmap( $context_slug );

        foreach ( bw_ss_get_overlay_candidate_post_ids( $scope, $query ) as $post_id ) {
            $year = isset( $post_map[ $post_id ] ) ? (int) $post_map[ $post_id ] : 0;

            if ( $year <= 0 ) {
                continue;
            }

            if ( ! isset( $counts[ $year ] ) ) {
                $counts[ $year ] = 0;
            }

            $counts[ $year ]++;
        }
    }

    if ( empty( $counts ) ) {
        return [];
    }

    krsort( $counts, SORT_NUMERIC );
    $results = [];

    foreach ( $counts as $year => $count ) {
        $results[] = [
            'label' => (string) $year,
            'count' => (int) $count,
            'url'   => bw_ss_build_search_results_navigation_url(
                $query,
                $scope,
                [ 'year' => (string) $year ]
            ),
        ];
    }

    return array_values( $results );
}

function bw_ss_build_overlay_advanced_group_items( $scope, $group_key, $query = '' ) {
    $scope        = bw_ss_normalize_scope_param( $scope );
    $context_slug = bw_ss_get_scope_context_slug( $scope );
    $group_key    = sanitize_key( (string) $group_key );

    if ( '' === $context_slug || ! function_exists( 'bw_fpw_get_supported_advanced_filter_groups_for_context' ) ) {
        return [];
    }

    $supported = bw_fpw_get_supported_advanced_filter_groups_for_context( $context_slug );

    if ( ! isset( $supported[ $group_key ] ) || ! function_exists( 'bw_fpw_build_advanced_filter_options_from_post_ids' ) ) {
        return [];
    }

    $results = [];
    $items   = bw_fpw_build_advanced_filter_options_from_post_ids( $context_slug, $group_key, bw_ss_get_overlay_candidate_post_ids( $scope, $query ) );

    foreach ( (array) $items as $item ) {
        if ( empty( $item['name'] ) ) {
            continue;
        }

        $results[] = [
            'label' => (string) $item['name'],
            'count' => isset( $item['count'] ) ? (int) $item['count'] : 0,
            'url'   => bw_ss_build_search_results_navigation_url(
                $query,
                $scope,
                [ $group_key => bw_ss_build_filter_value_slug( $item['name'] ) ]
            ),
        ];
    }

    return $results;
}

function bw_ss_build_overlay_browse_items( $scope, $group_key, $query = '' ) {
    $group_key   = bw_ss_normalize_overlay_group_key( $group_key, $scope );
    $definitions = bw_ss_get_group_definitions();
    $browse_type = isset( $definitions[ $group_key ] ) ? (string) $definitions[ $group_key ]['browse_type'] : '';

    switch ( $browse_type ) {
        case 'category':
            return bw_ss_build_overlay_category_items( $scope, $query );
        case 'tag':
            return bw_ss_build_overlay_tag_items( $scope, $query );
        case 'year':
            return bw_ss_build_overlay_year_items( $scope, $query );
        case 'advanced':
            return bw_ss_build_overlay_advanced_group_items( $scope, $group_key, $query );
        default:
            return [];
    }
}

function bw_ss_build_overlay_suggestions( $page_post_ids ) {
    $suggestions = [];

    foreach ( (array) $page_post_ids as $product_id ) {
        $preview = bw_ss_build_overlay_product_preview( $product_id );

        if ( ! empty( $preview ) ) {
            $suggestions[] = $preview;
        }
    }

    return $suggestions;
}

function bw_ss_send_overlay_throttled_response( $mode, $scope, $query ) {
    wp_send_json_success(
        [
            'mode'       => sanitize_key( (string) $mode ),
            'scope'      => bw_ss_normalize_scope_param( $scope ),
            'query'      => function_exists( 'bw_fpw_normalize_search_query' ) ? bw_fpw_normalize_search_query( $query ) : sanitize_text_field( (string) $query ),
            'throttled'  => true,
            'search_url' => bw_ss_build_search_results_navigation_url( $query, $scope ),
            'rows'       => [],
            'items'      => [],
            'group'      => '',
        ]
    );
}

function bw_ss_ajax_overlay_payload() {
    check_ajax_referer( 'bw_ss_overlay_nonce', 'nonce' );

    $mode  = sanitize_key( isset( $_POST['mode'] ) ? wp_unslash( $_POST['mode'] ) : 'trending' );
    $scope = bw_ss_normalize_scope_param( isset( $_POST['scope'] ) ? wp_unslash( $_POST['scope'] ) : 'all' );
    $group = bw_ss_normalize_overlay_group_key( isset( $_POST['group'] ) ? wp_unslash( $_POST['group'] ) : '', $scope );
    $query = function_exists( 'bw_fpw_normalize_search_query' )
        ? bw_fpw_normalize_search_query( isset( $_POST['query'] ) ? wp_unslash( $_POST['query'] ) : '' )
        : sanitize_text_field( (string) ( $_POST['query'] ?? '' ) );

    if ( bw_fpw_is_throttled_request( 'bw_ss_overlay_payload' ) ) {
        bw_ss_send_overlay_throttled_response( $mode, $scope, $query );
    }

    if ( 'suggest' === $mode && '' !== $query ) {
        $request = bw_ss_build_overlay_suggest_request( $query, $scope );
        $result  = bw_fpw_execute_search( $request );

        wp_send_json_success(
            [
                'mode'       => 'suggest',
                'scope'      => $scope,
                'query'      => $query,
                'search_url' => bw_ss_build_search_results_navigation_url( $query, $scope ),
                'items'      => bw_ss_build_overlay_suggestions( isset( $result['page_post_ids'] ) ? $result['page_post_ids'] : [] ),
                'has_more'   => ! empty( $result['has_more'] ),
            ]
        );
    }

    if ( 'browse' === $mode && '' !== $group ) {
        wp_send_json_success(
            [
                'mode'       => 'browse',
                'scope'      => $scope,
                'group'      => $group,
                'query'      => $query,
                'search_url' => bw_ss_build_search_results_navigation_url( $query, $scope ),
                'items'      => bw_ss_build_overlay_browse_items( $scope, $group, $query ),
            ]
        );
    }

    wp_send_json_success(
        [
            'mode'       => 'trending',
            'scope'      => $scope,
            'search_url' => bw_ss_build_search_results_navigation_url( '', $scope ),
            'rows'       => bw_ss_get_trending_rows( $scope ),
        ]
    );
}
