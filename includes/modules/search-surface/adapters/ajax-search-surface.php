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

/**
 * Build the filter UI payload for the popup Filter mode.
 * Executes a minimal engine request to retrieve available facets for a given scope.
 */
function bw_ss_build_popup_filter_ui_payload( $scope ) {
    $empty = [
        'types'        => [],
        'tags'         => [],
        'year'         => [ 'supported' => false ],
        'advanced'     => [],
        'result_count' => 0,
        'context'      => '',
        'scope'        => $scope,
    ];

    if ( ! function_exists( 'bw_fpw_build_engine_request' ) || ! function_exists( 'bw_fpw_execute_search' ) ) {
        return $empty;
    }

    $context_slug = bw_ss_get_scope_context_slug( $scope );
    $category     = bw_ss_get_scope_default_category( $scope );
    $sort_key     = function_exists( 'bw_fpw_get_discovery_sort_default_key' ) ? bw_fpw_get_discovery_sort_default_key() : 'newest';

    $request = bw_fpw_build_engine_request(
        [
            'widget_id'        => 'bw-search-surface-filter',
            'post_type'        => 'product',
            'context_slug'     => $context_slug,
            'category'         => $category,
            'subcategories'    => [],
            'tags'             => [],
            'search_enabled'   => 'no',
            'search'           => '',
            'per_page'         => 1,
            'page'             => 1,
            'offset'           => 0,
            'request_profile'  => 'filter_ui',
            'sort_key'         => $sort_key,
            'order_by'         => 'date',
            'order'            => 'DESC',
        ]
    );

    $result    = bw_fpw_execute_search( $request );
    $filter_ui = isset( $result['filter_ui'] ) && is_array( $result['filter_ui'] ) ? $result['filter_ui'] : [];

    return [
        'types'        => isset( $filter_ui['types'] ) ? array_values( (array) $filter_ui['types'] ) : [],
        'tags'         => isset( $filter_ui['tags'] ) ? array_values( (array) $filter_ui['tags'] ) : [],
        'year'         => isset( $filter_ui['year'] ) ? $filter_ui['year'] : [ 'supported' => false ],
        'advanced'     => isset( $filter_ui['advanced'] ) ? $filter_ui['advanced'] : [],
        'result_count' => isset( $result['result_count'] ) ? (int) $result['result_count'] : 0,
        'context'      => $context_slug ?: 'mixed',
        'scope'        => $scope,
    ];
}

/**
 * Execute a filtered count-only search for popup filter live count updates.
 */
function bw_ss_get_popup_filter_result_count( $scope, $post_data ) {
    if ( ! function_exists( 'bw_fpw_build_engine_request' ) || ! function_exists( 'bw_fpw_execute_search' ) ) {
        return 0;
    }

    $context_slug = bw_ss_get_scope_context_slug( $scope );
    $category     = bw_ss_get_scope_default_category( $scope );
    $sort_key     = function_exists( 'bw_fpw_get_discovery_sort_default_key' ) ? bw_fpw_get_discovery_sort_default_key() : 'newest';

    $raw_subs      = isset( $post_data['subcategories'] ) ? wp_unslash( $post_data['subcategories'] ) : '';
    $subcategories = array_values( array_filter( array_map( 'absint', explode( ',', (string) $raw_subs ) ) ) );

    $raw_tags = isset( $post_data['tags'] ) ? wp_unslash( $post_data['tags'] ) : '';
    $tags     = array_values( array_filter( array_map( 'absint', explode( ',', (string) $raw_tags ) ) ) );

    $year_from = ! empty( $post_data['year_from'] ) ? absint( wp_unslash( $post_data['year_from'] ) ) : null;
    $year_to   = ! empty( $post_data['year_to'] ) ? absint( wp_unslash( $post_data['year_to'] ) ) : null;

    $advanced_keys = [ 'artist', 'author', 'publisher', 'source', 'technique' ];
    $advanced      = [];

    foreach ( $advanced_keys as $key ) {
        if ( empty( $post_data[ $key ] ) ) {
            continue;
        }

        $raw_values = array_filter( explode( ',', sanitize_text_field( wp_unslash( (string) $post_data[ $key ] ) ) ) );
        $tokens     = [];

        foreach ( $raw_values as $slug ) {
            $label    = trim( str_replace( '-', ' ', sanitize_text_field( $slug ) ) );
            $tokens[] = $label;
        }

        $advanced[ $key ] = $tokens;
    }

    $request = bw_fpw_build_engine_request(
        [
            'widget_id'       => 'bw-search-surface-filter-count',
            'post_type'       => 'product',
            'context_slug'    => $context_slug,
            'category'        => $category,
            'subcategories'   => $subcategories,
            'tags'            => $tags,
            'search_enabled'  => 'no',
            'search'          => '',
            'year_from'       => $year_from,
            'year_to'         => $year_to,
            'artist'          => $advanced['artist'] ?? [],
            'author'          => $advanced['author'] ?? [],
            'publisher'       => $advanced['publisher'] ?? [],
            'source'          => $advanced['source'] ?? [],
            'technique'       => $advanced['technique'] ?? [],
            'per_page'        => 1,
            'page'            => 1,
            'offset'          => 0,
            'request_profile' => 'count_only',
            'sort_key'        => $sort_key,
            'order_by'        => 'date',
            'order'           => 'DESC',
        ]
    );

    $result = bw_fpw_execute_search( $request );

    return isset( $result['result_count'] ) ? (int) $result['result_count'] : 0;
}

function bw_ss_send_overlay_throttled_response( $mode, $scope, $query ) {
    wp_send_json_success(
        [
            'mode'      => sanitize_key( (string) $mode ),
            'scope'     => bw_ss_normalize_scope_param( $scope ),
            'query'     => function_exists( 'bw_fpw_normalize_search_query' ) ? bw_fpw_normalize_search_query( $query ) : sanitize_text_field( (string) $query ),
            'throttled' => true,
            'items'     => [],
        ]
    );
}

function bw_ss_ajax_overlay_payload() {
    check_ajax_referer( 'bw_ss_overlay_nonce', 'nonce' );

    $mode  = sanitize_key( isset( $_POST['mode'] ) ? wp_unslash( $_POST['mode'] ) : 'trending' );
    $scope = bw_ss_normalize_scope_param( isset( $_POST['scope'] ) ? wp_unslash( $_POST['scope'] ) : 'all' );
    $query = function_exists( 'bw_fpw_normalize_search_query' )
        ? bw_fpw_normalize_search_query( isset( $_POST['query'] ) ? wp_unslash( $_POST['query'] ) : '' )
        : sanitize_text_field( (string) ( $_POST['query'] ?? '' ) );

    if ( bw_fpw_is_throttled_request( 'bw_ss_overlay_payload' ) ) {
        bw_ss_send_overlay_throttled_response( $mode, $scope, $query );
    }

    // ── Suggest mode ──────────────────────────────────────────────────────────
    if ( 'suggest' === $mode && '' !== $query ) {
        $request = bw_ss_build_overlay_suggest_request( $query, $scope );
        $result  = bw_fpw_execute_search( $request );

        wp_send_json_success(
            [
                'mode'         => 'suggest',
                'scope'        => $scope,
                'query'        => $query,
                'search_url'   => bw_ss_build_search_results_navigation_url( $query, $scope ),
                'items'        => bw_ss_build_overlay_suggestions( isset( $result['page_post_ids'] ) ? $result['page_post_ids'] : [] ),
                'has_more'     => ! empty( $result['has_more'] ),
                'result_count' => isset( $result['result_count'] ) ? (int) $result['result_count'] : 0,
            ]
        );
    }

    // ── Filter mode ───────────────────────────────────────────────────────────
    if ( 'filter' === $mode ) {
        wp_send_json_success(
            [
                'mode'      => 'filter',
                'scope'     => $scope,
                'filter_ui' => bw_ss_build_popup_filter_ui_payload( $scope ),
            ]
        );
    }

    // ── Filter count mode ─────────────────────────────────────────────────────
    if ( 'filter_count' === $mode ) {
        wp_send_json_success(
            [
                'mode'  => 'filter_count',
                'count' => bw_ss_get_popup_filter_result_count( $scope, $_POST ),
            ]
        );
    }

    // ── Feed modes: trending (staff-selected), new, sale, free ───────────────
    $feed_label_map = [
        'trending' => 'staff_select',
        'new'      => 'new',
        'sale'     => 'sale',
        'free'     => 'free_download',
    ];

    if ( isset( $feed_label_map[ $mode ] ) ) {
        $label_key   = $feed_label_map[ $mode ];
        $settings    = function_exists( 'bw_get_product_labels_settings' ) ? bw_get_product_labels_settings() : [];
        $product_ids = bw_ss_get_trending_product_ids_for_label( $label_key, $scope, 12, $settings );

        wp_send_json_success(
            [
                'mode'  => $mode,
                'scope' => $scope,
                'items' => bw_ss_build_overlay_suggestions( $product_ids ),
            ]
        );
    }

    // ── Fallback ──────────────────────────────────────────────────────────────
    $settings    = function_exists( 'bw_get_product_labels_settings' ) ? bw_get_product_labels_settings() : [];
    $product_ids = bw_ss_get_trending_product_ids_for_label( 'staff_select', $scope, 12, $settings );

    wp_send_json_success(
        [
            'mode'  => 'trending',
            'scope' => $scope,
            'items' => bw_ss_build_overlay_suggestions( $product_ids ),
        ]
    );
}
