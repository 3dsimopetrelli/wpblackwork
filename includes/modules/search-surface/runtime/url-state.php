<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function bw_ss_get_search_results_route_path() {
    $path = wp_parse_url( home_url( '/search/' ), PHP_URL_PATH );

    if ( ! is_string( $path ) || '' === $path ) {
        return '/search';
    }

    return untrailingslashit( $path );
}

function bw_ss_get_current_request_path() {
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $request_path = wp_parse_url( (string) $request_uri, PHP_URL_PATH );

    if ( ! is_string( $request_path ) || '' === $request_path ) {
        return '/';
    }

    return untrailingslashit( $request_path );
}

function bw_ss_is_search_results_request() {
    if ( is_admin() || wp_doing_ajax() ) {
        return false;
    }

    return bw_ss_get_current_request_path() === bw_ss_get_search_results_route_path();
}

function bw_ss_normalize_scope_param( $raw_scope ) {
    $scope = sanitize_key( (string) $raw_scope );

    switch ( $scope ) {
        case '':
        case 'all':
        case 'mixed':
            return 'all';
        case 'digital':
        case 'digital-collections':
            return 'digital-collections';
        case 'books':
            return 'books';
        case 'prints':
            return 'prints';
        default:
            return 'all';
    }
}

function bw_ss_get_scope_label( $scope ) {
    switch ( $scope ) {
        case 'digital-collections':
            return __( 'Digital Collections', 'bw-elementor-widgets' );
        case 'books':
            return __( 'Books', 'bw-elementor-widgets' );
        case 'prints':
            return __( 'Prints', 'bw-elementor-widgets' );
        case 'all':
        default:
            return __( 'All', 'bw-elementor-widgets' );
    }
}

function bw_ss_get_scope_context_slug( $scope ) {
    // "All" is the explicit mixed-mode boundary for Search Surface consumers:
    // pass an empty engine context slug instead of relying on implicit mixed->''
    // conversion later in the stack.
    return 'all' === $scope ? '' : $scope;
}

function bw_ss_get_scope_default_category( $scope ) {
    $context_slug = bw_ss_get_scope_context_slug( $scope );

    if ( '' === $context_slug ) {
        return 'all';
    }

    if ( function_exists( 'bw_fpw_get_context_root_term_id' ) ) {
        $root_term_id = (int) bw_fpw_get_context_root_term_id( $context_slug );

        if ( $root_term_id > 0 ) {
            return $root_term_id;
        }
    }

    return 'all';
}

function bw_ss_get_current_query_args() {
    $query_args = [];

    foreach ( $_GET as $key => $value ) {
        $sanitized_key = sanitize_key( (string) $key );

        if ( '' === $sanitized_key ) {
            continue;
        }

        $query_args[ $sanitized_key ] = $value;
    }

    return $query_args;
}

function bw_ss_build_search_results_state_from_url() {
    $query_args = bw_ss_get_current_query_args();
    $scope      = bw_ss_normalize_scope_param( isset( $query_args['scope'] ) ? $query_args['scope'] : '' );
    $search     = function_exists( 'bw_fpw_normalize_search_query' )
        ? bw_fpw_normalize_search_query( isset( $query_args['q'] ) ? $query_args['q'] : '' )
        : sanitize_text_field( (string) ( $query_args['q'] ?? '' ) );
    $page       = function_exists( 'bw_fpw_normalize_positive_int' )
        ? bw_fpw_normalize_positive_int( isset( $query_args['page'] ) ? $query_args['page'] : 1, 1, 1, 1000 )
        : max( 1, absint( $query_args['page'] ?? 1 ) );
    $author     = function_exists( 'bw_fpw_extract_filter_tokens_from_value' )
        ? bw_fpw_extract_filter_tokens_from_value( $query_args['author'] ?? [] )
        : [];
    $source     = function_exists( 'bw_fpw_extract_filter_tokens_from_value' )
        ? bw_fpw_extract_filter_tokens_from_value( $query_args['source'] ?? [] )
        : [];
    $category   = bw_ss_get_scope_default_category( $scope );

    return [
        'query'         => $search,
        'scope'         => $scope,
        'scope_label'   => bw_ss_get_scope_label( $scope ),
        'context_slug'  => bw_ss_get_scope_context_slug( $scope ),
        'page'          => $page,
        'category'      => $category,
        'advanced'      => [
            'author' => $author,
            'source' => $source,
        ],
        'query_args'    => $query_args,
    ];
}

function bw_ss_build_search_results_title( $state ) {
    $query = isset( $state['query'] ) ? trim( (string) $state['query'] ) : '';

    if ( '' !== $query ) {
        return sprintf(
            /* translators: %s is the search query. */
            __( 'Search results for "%s"', 'bw-elementor-widgets' ),
            $query
        );
    }

    return __( 'Filtered results', 'bw-elementor-widgets' );
}

function bw_ss_get_search_results_url( $args = [] ) {
    $base_url = home_url( '/search/' );
    $args     = is_array( $args ) ? $args : [];
    $args     = array_filter(
        $args,
        static function ( $value ) {
            if ( is_array( $value ) ) {
                return ! empty( $value );
            }

            return null !== $value && '' !== $value;
        }
    );

    return empty( $args ) ? $base_url : add_query_arg( $args, $base_url );
}

function bw_ss_build_search_results_query_args( $query = '', $scope = 'all' ) {
    $args  = [];
    $query = function_exists( 'bw_fpw_normalize_search_query' )
        ? bw_fpw_normalize_search_query( $query )
        : sanitize_text_field( (string) $query );
    $scope = bw_ss_normalize_scope_param( $scope );

    $args['scope'] = $scope;

    if ( '' !== $query ) {
        $args['q'] = $query;
    }

    return $args;
}

function bw_ss_build_search_results_navigation_url( $query = '', $scope = 'all' ) {
    return bw_ss_get_search_results_url( bw_ss_build_search_results_query_args( $query, $scope ) );
}

function bw_ss_build_active_chip_links( $state ) {
    $chips      = [];
    $query_args = isset( $state['query_args'] ) && is_array( $state['query_args'] ) ? $state['query_args'] : [];

    if ( ! empty( $state['query'] ) ) {
        $chips[] = [
            'label' => sprintf(
                /* translators: %s is the search query. */
                __( 'Query: %s', 'bw-elementor-widgets' ),
                (string) $state['query']
            ),
            'url'   => remove_query_arg( 'q', bw_ss_get_search_results_url( $query_args ) ),
        ];
    }

    if ( ! empty( $state['scope'] ) && 'all' !== $state['scope'] ) {
        $chips[] = [
            'label' => (string) $state['scope_label'],
            'url'   => remove_query_arg( 'scope', bw_ss_get_search_results_url( $query_args ) ),
        ];
    }

    foreach ( [ 'author' => __( 'Author', 'bw-elementor-widgets' ), 'source' => __( 'Source', 'bw-elementor-widgets' ) ] as $key => $label ) {
        $values = isset( $state['advanced'][ $key ] ) && is_array( $state['advanced'][ $key ] ) ? $state['advanced'][ $key ] : [];

        foreach ( $values as $token ) {
            if ( empty( $token['label'] ) ) {
                continue;
            }

            $chips[] = [
                'label' => sprintf( '%s: %s', $label, (string) $token['label'] ),
                'url'   => remove_query_arg( $key, bw_ss_get_search_results_url( $query_args ) ),
            ];
        }
    }

    return $chips;
}
