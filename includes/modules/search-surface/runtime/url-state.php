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

function bw_ss_get_search_results_route_candidates() {
    $candidates = [
        bw_ss_get_search_results_route_path(),
        '/search',
    ];

    $normalized = [];

    foreach ( $candidates as $candidate ) {
        $candidate = untrailingslashit( (string) $candidate );

        if ( '' === $candidate ) {
            $candidate = '/';
        }

        $normalized[ $candidate ] = $candidate;
    }

    return array_values( $normalized );
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

    if ( '1' === (string) get_query_var( 'bw_search_results', '' ) ) {
        return true;
    }

    return in_array( bw_ss_get_current_request_path(), bw_ss_get_search_results_route_candidates(), true );
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
    $definitions = bw_ss_get_scope_definitions();

    return isset( $definitions[ $scope ] ) ? (string) $definitions[ $scope ]['label'] : (string) $definitions['all']['label'];
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

function bw_ss_get_filter_param_key_for_group( $group_key ) {
    $group_key   = sanitize_key( (string) $group_key );
    $definitions = bw_ss_get_group_definitions();

    return isset( $definitions[ $group_key ] ) ? (string) $definitions[ $group_key ]['param_key'] : '';
}

function bw_ss_parse_query_value_list( $raw_value ) {
    $values  = is_array( $raw_value ) ? $raw_value : explode( ',', (string) $raw_value );
    $results = [];

    foreach ( $values as $value ) {
        $value = sanitize_text_field( wp_unslash( (string) $value ) );
        $value = trim( $value );

        if ( '' === $value ) {
            continue;
        }

        $results[] = $value;
    }

    return array_values( array_unique( $results ) );
}

function bw_ss_sanitize_query_arg_text_value( $raw_value ) {
    if ( is_array( $raw_value ) ) {
        return '';
    }

    return sanitize_text_field( wp_unslash( (string) $raw_value ) );
}

function bw_ss_sanitize_query_arg_list_value( $raw_value ) {
    if ( is_array( $raw_value ) ) {
        return array_values(
            array_filter(
                array_map( 'sanitize_text_field', wp_unslash( $raw_value ) ),
                static function ( $value ) {
                    return '' !== trim( (string) $value );
                }
            )
        );
    }

    return sanitize_text_field( wp_unslash( (string) $raw_value ) );
}

function bw_ss_sanitize_current_query_arg_value( $key, $raw_value ) {
    $key = sanitize_key( (string) $key );

    switch ( $key ) {
        case 'scope':
            return sanitize_key( bw_ss_sanitize_query_arg_text_value( $raw_value ) );
        case 'page':
            return max( 1, absint( bw_ss_sanitize_query_arg_text_value( $raw_value ) ) );
        case 'year':
        case 'q':
        case 'category':
            return bw_ss_sanitize_query_arg_text_value( $raw_value );
        case 'tag':
        case 'tags':
        case 'artist':
        case 'author':
        case 'publisher':
        case 'source':
        case 'technique':
            return bw_ss_sanitize_query_arg_list_value( $raw_value );
        default:
            return is_array( $raw_value )
                ? array_map( 'sanitize_text_field', wp_unslash( $raw_value ) )
                : sanitize_text_field( wp_unslash( (string) $raw_value ) );
    }
}

function bw_ss_build_filter_value_slug( $value ) {
    return sanitize_title( remove_accents( sanitize_text_field( (string) $value ) ) );
}

function bw_ss_resolve_product_term_from_query_value( $value, $taxonomy = 'product_cat' ) {
    $taxonomy = sanitize_key( (string) $taxonomy );

    if ( ! taxonomy_exists( $taxonomy ) ) {
        return null;
    }

    if ( is_numeric( $value ) ) {
        $term = get_term( absint( $value ), $taxonomy );
        return ( $term instanceof WP_Term && ! is_wp_error( $term ) ) ? $term : null;
    }

    $slug = sanitize_title( (string) $value );

    if ( '' === $slug ) {
        return null;
    }

    $term = get_term_by( 'slug', $slug, $taxonomy );

    return ( $term instanceof WP_Term && ! is_wp_error( $term ) ) ? $term : null;
}

function bw_ss_resolve_product_term_ids_from_query( $raw_value, $taxonomy = 'product_tag', $limit = 50 ) {
    $terms    = [];
    $resolved = [];

    foreach ( bw_ss_parse_query_value_list( $raw_value ) as $value ) {
        $term = bw_ss_resolve_product_term_from_query_value( $value, $taxonomy );

        if ( ! $term instanceof WP_Term ) {
            continue;
        }

        $term_id = (int) $term->term_id;

        if ( $term_id <= 0 || isset( $resolved[ $term_id ] ) ) {
            continue;
        }

        $resolved[ $term_id ] = $term_id;
        $terms[]              = $term;

        if ( count( $resolved ) >= $limit ) {
            break;
        }
    }

    return [
        'ids'   => array_values( $resolved ),
        'terms' => $terms,
    ];
}

function bw_ss_get_supported_advanced_filter_groups_for_scope( $scope ) {
    $context_slug = bw_ss_get_scope_context_slug( $scope );

    if ( '' === $context_slug || ! function_exists( 'bw_fpw_get_supported_advanced_filter_groups_for_context' ) ) {
        return [];
    }

    return (array) bw_fpw_get_supported_advanced_filter_groups_for_context( $context_slug );
}

function bw_ss_resolve_advanced_filter_tokens_from_query( $group_key, $raw_value, $scope ) {
    $group_key        = sanitize_key( (string) $group_key );
    $supported_groups = bw_ss_get_supported_advanced_filter_groups_for_scope( $scope );
    $requested_slugs  = array_map( 'sanitize_title', bw_ss_parse_query_value_list( $raw_value ) );

    if ( empty( $requested_slugs ) || ! isset( $supported_groups[ $group_key ] ) ) {
        return [];
    }

    $context_slug = bw_ss_get_scope_context_slug( $scope );
    $slug_map     = [];

    if ( function_exists( 'bw_fpw_get_advanced_filter_index' ) ) {
        $index       = bw_fpw_get_advanced_filter_index( $context_slug );
        $group_index = isset( $index['groups'][ $group_key ] ) && is_array( $index['groups'][ $group_key ] ) ? $index['groups'][ $group_key ] : [];
        $labels      = isset( $group_index['labels'] ) && is_array( $group_index['labels'] ) ? $group_index['labels'] : [];

        foreach ( $labels as $token => $label ) {
            $slug = bw_ss_build_filter_value_slug( $label );

            if ( '' === $slug || isset( $slug_map[ $slug ] ) ) {
                continue;
            }

            $slug_map[ $slug ] = [
                'value' => (string) $token,
                'label' => (string) $label,
            ];
        }
    }

    $resolved = [];

    foreach ( $requested_slugs as $slug ) {
        if ( '' === $slug || isset( $resolved[ $slug ] ) ) {
            continue;
        }

        if ( isset( $slug_map[ $slug ] ) ) {
            $resolved[ $slug ] = $slug_map[ $slug ];
            continue;
        }

        $fallback_label = trim( str_replace( '-', ' ', $slug ) );
        $fallback_value = function_exists( 'bw_fpw_normalize_filter_token_value' )
            ? bw_fpw_normalize_filter_token_value( $fallback_label )
            : sanitize_text_field( $fallback_label );

        if ( '' === $fallback_value ) {
            continue;
        }

        $resolved[ $slug ] = [
            'value' => $fallback_value,
            'label' => $fallback_label,
        ];
    }

    return array_values( $resolved );
}

function bw_ss_get_current_query_args() {
    $query_args = [];

    foreach ( $_GET as $key => $value ) {
        $sanitized_key = sanitize_key( (string) $key );

        if ( '' === $sanitized_key ) {
            continue;
        }

        $query_args[ $sanitized_key ] = bw_ss_sanitize_current_query_arg_value( $sanitized_key, $value );
    }

    return $query_args;
}

function bw_ss_normalize_int_array_from_url( $raw_value, $max = 50 ) {
    $raw   = is_array( $raw_value ) ? $raw_value : [];
    $clean = [];

    foreach ( $raw as $v ) {
        $id = absint( $v );
        if ( $id > 0 ) {
            $clean[] = $id;
        }
    }

    return array_values( array_unique( array_slice( $clean, 0, $max ) ) );
}

function bw_ss_build_search_results_state_from_url() {
    $query_args    = bw_ss_get_current_query_args();
    $scope         = bw_ss_normalize_scope_param( isset( $query_args['scope'] ) ? $query_args['scope'] : '' );
    $search        = function_exists( 'bw_fpw_normalize_search_query' )
        ? bw_fpw_normalize_search_query( isset( $query_args['q'] ) ? $query_args['q'] : '' )
        : sanitize_text_field( (string) ( $query_args['q'] ?? '' ) );
    $page          = function_exists( 'bw_fpw_normalize_positive_int' )
        ? bw_fpw_normalize_positive_int( isset( $query_args['page'] ) ? $query_args['page'] : 1, 1, 1, 1000 )
        : max( 1, absint( $query_args['page'] ?? 1 ) );
    $default_category = bw_ss_get_scope_default_category( $scope );
    $category_term    = bw_ss_resolve_product_term_from_query_value( $query_args['category'] ?? '', 'product_cat' );
    $tags_data        = bw_ss_resolve_product_term_ids_from_query(
        isset( $query_args['tag'] ) ? $query_args['tag'] : ( $query_args['tags'] ?? [] ),
        'product_tag'
    );
    $year             = function_exists( 'bw_fpw_normalize_year_bound' )
        ? bw_fpw_normalize_year_bound( $query_args['year'] ?? null )
        : null;
    $artist           = bw_ss_resolve_advanced_filter_tokens_from_query( 'artist', $query_args['artist'] ?? [], $scope );
    $author           = bw_ss_resolve_advanced_filter_tokens_from_query( 'author', $query_args['author'] ?? [], $scope );
    $publisher        = bw_ss_resolve_advanced_filter_tokens_from_query( 'publisher', $query_args['publisher'] ?? [], $scope );
    $source           = bw_ss_resolve_advanced_filter_tokens_from_query( 'source', $query_args['source'] ?? [], $scope );
    $technique        = bw_ss_resolve_advanced_filter_tokens_from_query( 'technique', $query_args['technique'] ?? [], $scope );

    // When ?category=<slug> is in the URL keep the scope default as the parent so the
    // Categories dropdown loads sibling categories rather than children of the clicked term.
    // The resolved term is treated as a selected subcategory so the JS filter button lights
    // up and the engine (via the new 'all'+subcategories branch) filters results correctly.
    $url_subcategory_ids = bw_ss_normalize_int_array_from_url( $query_args['subcategories'] ?? [] );
    if ( $category_term instanceof WP_Term ) {
        $category      = $default_category;
        $subcategories = array_values( array_unique( array_merge( [ (int) $category_term->term_id ], $url_subcategory_ids ) ) );
    } else {
        $category      = $default_category;
        $subcategories = $url_subcategory_ids;
    }

    return [
        'query'         => $search,
        'scope'         => $scope,
        'scope_label'   => bw_ss_get_scope_label( $scope ),
        'context_slug'  => bw_ss_get_scope_context_slug( $scope ),
        'page'          => $page,
        'category'      => $category,
        'subcategories' => $subcategories,
        'category_term' => $category_term instanceof WP_Term ? $category_term : null,
        'tags'          => isset( $tags_data['ids'] ) ? (array) $tags_data['ids'] : [],
        'tag_terms'     => isset( $tags_data['terms'] ) ? (array) $tags_data['terms'] : [],
        'year'          => [
            'from' => $year,
            'to'   => $year,
        ],
        'advanced'      => [
            'artist'    => $artist,
            'author' => $author,
            'publisher' => $publisher,
            'source' => $source,
            'technique' => $technique,
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

function bw_ss_build_search_results_query_args( $query = '', $scope = 'all', $filters = [] ) {
    $args  = [];
    $query = function_exists( 'bw_fpw_normalize_search_query' )
        ? bw_fpw_normalize_search_query( $query )
        : sanitize_text_field( (string) $query );
    $scope = bw_ss_normalize_scope_param( $scope );
    $filters = is_array( $filters ) ? $filters : [];

    $args['scope'] = $scope;

    if ( '' !== $query ) {
        $args['q'] = $query;
    }

    if ( ! empty( $filters['category'] ) ) {
        $args['category'] = sanitize_title( (string) $filters['category'] );
    }

    if ( ! empty( $filters['tag'] ) ) {
        $args['tag'] = implode( ',', array_map( 'sanitize_title', bw_ss_parse_query_value_list( $filters['tag'] ) ) );
    }

    if ( ! empty( $filters['year'] ) && is_scalar( $filters['year'] ) ) {
        $year = function_exists( 'bw_fpw_normalize_year_bound' ) ? bw_fpw_normalize_year_bound( $filters['year'] ) : absint( $filters['year'] );

        if ( ! empty( $year ) ) {
            $args['year'] = (string) $year;
        }
    }

    foreach ( [ 'author', 'artist', 'publisher', 'source', 'technique' ] as $key ) {
        if ( empty( $filters[ $key ] ) ) {
            continue;
        }

        $args[ $key ] = implode( ',', array_map( 'sanitize_title', bw_ss_parse_query_value_list( $filters[ $key ] ) ) );
    }

    return $args;
}

function bw_ss_build_search_results_navigation_url( $query = '', $scope = 'all', $filters = [] ) {
    return bw_ss_get_search_results_url( bw_ss_build_search_results_query_args( $query, $scope, $filters ) );
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

    if ( ! empty( $query_args['category'] ) && ! empty( $state['category_term'] ) && $state['category_term'] instanceof WP_Term ) {
        $chips[] = [
            'label' => sprintf( '%s: %s', __( 'Category', 'bw-elementor-widgets' ), (string) $state['category_term']->name ),
            'url'   => remove_query_arg( 'category', bw_ss_get_search_results_url( $query_args ) ),
        ];
    }

    if ( ! empty( $query_args['tag'] ) && ! empty( $state['tag_terms'] ) ) {
        foreach ( $state['tag_terms'] as $term ) {
            if ( ! $term instanceof WP_Term ) {
                continue;
            }

            $chips[] = [
                'label' => sprintf( '%s: %s', __( 'Style', 'bw-elementor-widgets' ), (string) $term->name ),
                'url'   => remove_query_arg( 'tag', bw_ss_get_search_results_url( $query_args ) ),
            ];
        }
    }

    if ( ! empty( $query_args['year'] ) && ! empty( $state['year']['from'] ) ) {
        $chips[] = [
            'label' => sprintf( '%s: %s', __( 'Year', 'bw-elementor-widgets' ), (string) $state['year']['from'] ),
            'url'   => remove_query_arg( 'year', bw_ss_get_search_results_url( $query_args ) ),
        ];
    }

    foreach ( [
        'artist'    => __( 'Artist', 'bw-elementor-widgets' ),
        'author'    => __( 'Author', 'bw-elementor-widgets' ),
        'publisher' => __( 'Publisher', 'bw-elementor-widgets' ),
        'source'    => __( 'Source', 'bw-elementor-widgets' ),
        'technique' => __( 'Technique', 'bw-elementor-widgets' ),
    ] as $key => $label ) {
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
