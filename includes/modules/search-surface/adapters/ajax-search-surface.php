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

    wp_send_json_success(
        [
            'mode'       => 'trending',
            'scope'      => $scope,
            'search_url' => bw_ss_build_search_results_navigation_url( '', $scope ),
            'rows'       => bw_ss_get_trending_rows( $scope ),
        ]
    );
}
