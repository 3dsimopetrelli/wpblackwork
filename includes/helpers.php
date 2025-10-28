<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'bw_user_can_manage_content' ) ) {
    /**
     * Determine if the current user can manage content within Elementor widgets.
     *
     * Allows roles that manage posts or WooCommerce products to execute AJAX callbacks.
     *
     * @return bool
     */
    function bw_user_can_manage_content() {
        $capabilities = [
            'manage_options',
            'edit_posts',
            'edit_pages',
            'edit_products',
            'elementor_edit_posts',
        ];

        $can_manage = false;

        foreach ( $capabilities as $capability ) {
            if ( current_user_can( $capability ) ) {
                $can_manage = true;
                break;
            }
        }

        /**
         * Filter whether the current user can manage Elementor widget content.
         *
         * @since 1.0.0
         *
         * @param bool $can_manage Whether the user can manage widget content.
         */
        return (bool) apply_filters( 'bw_user_can_manage_content', $can_manage );
    }
}

if ( ! function_exists( 'bw_get_selectable_post_statuses' ) ) {
    /**
     * Retrieve the list of post statuses that can be selected via AJAX controls.
     *
     * @param string $post_type Optional post type slug.
     *
     * @return array<int,string>
     */
    function bw_get_selectable_post_statuses( $post_type = '' ) {
        $statuses = [ 'publish', 'future', 'draft', 'pending', 'private' ];

        /**
         * Filter the list of selectable post statuses for AJAX powered controls.
         *
         * @since 1.0.0
         *
         * @param array<int,string> $statuses  Post status slugs.
         * @param string            $post_type Post type slug.
         */
        return apply_filters( 'bw_selectable_post_statuses', $statuses, $post_type );
    }
}

if ( ! function_exists( 'bw_get_public_searchable_post_types' ) ) {
    /**
     * Retrieve a list of public post types that can be searched on the front end.
     *
     * @return array<int,string>
     */
    function bw_get_public_searchable_post_types() {
        $post_types = get_post_types(
            [
                'public'              => true,
                'exclude_from_search' => false,
            ],
            'names'
        );

        $post_types = array_values( $post_types );

        if ( empty( $post_types ) ) {
            $post_types = [ 'post' ];
        }

        /**
         * Filter the list of searchable public post types when the requester is not privileged.
         *
         * @since 1.0.0
         *
         * @param array<int,string> $post_types Post type slugs.
         */
        return (array) apply_filters( 'bw_public_searchable_post_types', $post_types );
    }
}

if ( ! function_exists( 'bw_normalize_requested_post_type' ) ) {
    /**
     * Normalize the requested post type depending on the current user's permissions.
     *
     * @param string $post_type   Requested post type.
     * @param bool   $can_manage  Whether the current requester can manage content.
     *
     * @return string|array<int,string>
     */
    function bw_normalize_requested_post_type( $post_type, $can_manage ) {
        if ( empty( $post_type ) || 'any' === $post_type ) {
            return $can_manage ? 'any' : bw_get_public_searchable_post_types();
        }

        if ( ! post_type_exists( $post_type ) ) {
            return $can_manage ? 'any' : bw_get_public_searchable_post_types();
        }

        if ( $can_manage ) {
            return $post_type;
        }

        $object = get_post_type_object( $post_type );

        if ( ! $object || ! is_post_type_viewable( $object ) || ! empty( $object->exclude_from_search ) ) {
            return 'post';
        }

        return $post_type;
    }
}

if ( ! function_exists( 'bw_get_product_categories_options' ) ) {
    /**
     * Retrieve all WooCommerce product categories.
     *
     * @return array<int,string>
     */
    function bw_get_product_categories_options() {
        $terms = get_terms(
            [
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]
        );

        $options = [];

        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            return $options;
        }

        foreach ( $terms as $term ) {
            $options[ $term->term_id ] = $term->name;
        }

        return $options;
    }
}

if ( ! function_exists( 'bw_get_parent_product_categories' ) ) {
    /**
     * Retrieve WooCommerce top-level product categories.
     *
     * @return array<int,string>
     */
    function bw_get_parent_product_categories() {
        $terms = get_terms(
            [
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'parent'     => 0,
            ]
        );

        $options = [];

        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            return $options;
        }

        foreach ( $terms as $term ) {
            $options[ $term->term_id ] = $term->name;
        }

        return $options;
    }
}

if ( ! function_exists( 'bw_ajax_get_child_categories' ) ) {
    /**
     * AJAX callback to fetch child categories for a given parent category.
     */
    function bw_ajax_get_child_categories() {
        if ( ! bw_user_can_manage_content() ) {
            wp_send_json_error();
        }

        check_ajax_referer( 'bw_get_child_categories', 'nonce' );

        $parent_id = isset( $_POST['parent_id'] ) ? absint( $_POST['parent_id'] ) : 0;

        if ( $parent_id <= 0 ) {
            wp_send_json_success( [] );
        }

        $terms = get_terms(
            [
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'parent'     => $parent_id,
            ]
        );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            wp_send_json_success( [] );
        }

        $options = [];

        foreach ( $terms as $term ) {
            $options[ $term->term_id ] = $term->name;
        }

        wp_send_json_success( $options );
    }
}

add_action( 'wp_ajax_bw_get_child_categories', 'bw_ajax_get_child_categories' );

if ( ! function_exists( 'bw_get_posts_titles_by_ids' ) ) {
    /**
     * Retrieve post titles keyed by ID preserving the provided order.
     *
     * @param array<int|string> $ids       Post IDs to fetch.
     * @param string            $post_type Optional post type filter.
     *
     * @return array<int,string>
     */
    function bw_get_posts_titles_by_ids( $ids, $post_type = 'any' ) {
        $ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );

        if ( empty( $ids ) ) {
            return [];
        }

        $can_manage   = bw_user_can_manage_content();
        $normalized   = bw_normalize_requested_post_type( $post_type, $can_manage );
        $post_status  = $can_manage
            ? bw_get_selectable_post_statuses( $post_type )
            : (array) apply_filters( 'bw_public_search_post_statuses', [ 'publish' ], $post_type );

        $query_args = [
            'post_type'      => $normalized,
            'post__in'       => $ids,
            'posts_per_page' => -1,
            'orderby'        => 'post__in',
            'post_status'    => $post_status,
        ];

        $posts = get_posts( $query_args );

        if ( empty( $posts ) ) {
            return [];
        }

        $titles = [];

        foreach ( $posts as $post ) {
            $titles[ $post->ID ] = get_the_title( $post );
        }

        $ordered = [];

        foreach ( $ids as $id ) {
            if ( isset( $titles[ $id ] ) ) {
                $ordered[ $id ] = $titles[ $id ];
            }
        }

        foreach ( $titles as $id => $title ) {
            if ( ! isset( $ordered[ $id ] ) ) {
                $ordered[ $id ] = $title;
            }
        }

        return $ordered;
    }
}

if ( ! function_exists( 'bw_ajax_get_posts_by_ids' ) ) {
    /**
     * AJAX callback to fetch post titles by ID for Select2 controls.
     */
    function bw_ajax_get_posts_by_ids() {
        check_ajax_referer( 'bw_get_posts_by_ids', 'nonce' );

        $ids_param = isset( $_POST['ids'] ) ? wp_unslash( $_POST['ids'] ) : [];
        $post_type = isset( $_POST['post_type'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : 'any';

        if ( ! is_array( $ids_param ) ) {
            $ids_param = array_filter( array_map( 'trim', explode( ',', (string) $ids_param ) ) );
        }

        $ids = array_filter( array_map( 'absint', $ids_param ) );

        if ( empty( $ids ) ) {
            wp_send_json_success( [] );
        }

        $posts = bw_get_posts_titles_by_ids( $ids, $post_type );

        wp_send_json_success( $posts );
    }
}

add_action( 'wp_ajax_bw_get_posts_by_ids', 'bw_ajax_get_posts_by_ids' );
add_action( 'wp_ajax_nopriv_bw_get_posts_by_ids', 'bw_ajax_get_posts_by_ids' );

if ( ! function_exists( 'bw_search_posts' ) ) {
    /**
     * AJAX callback to search posts by title for Select2 controls.
     */
    function bw_search_posts() {
        if ( ! check_ajax_referer( 'bw_search_posts', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid nonce', 'bw-elementor-widgets' ) ], 400 );
        }

        $request   = wp_unslash( $_REQUEST );
        $term      = isset( $request['q'] ) ? sanitize_text_field( $request['q'] ) : '';
        $post_type = isset( $request['post_type'] ) ? sanitize_text_field( $request['post_type'] ) : 'any';

        if ( ! empty( $post_type ) && 'any' !== $post_type && ! post_type_exists( $post_type ) ) {
            $post_type = 'any';
        }

        $can_manage  = bw_user_can_manage_content();
        $normalized  = bw_normalize_requested_post_type( $post_type, $can_manage );
        $post_status = $can_manage
            ? bw_get_selectable_post_statuses( $post_type )
            : (array) apply_filters( 'bw_public_search_post_statuses', [ 'publish' ], $post_type );

        $query = new WP_Query(
            [
                'post_type'      => $normalized,
                's'              => $term,
                'posts_per_page' => 20,
                'post_status'    => $post_status,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ]
        );

        $results = [];

        if ( $query->have_posts() ) {
            foreach ( $query->posts as $post ) {
                $results[] = [
                    'id'   => $post->ID,
                    'text' => get_the_title( $post ),
                ];
            }
        }

        wp_send_json( [ 'results' => $results ] );
    }
}

add_action( 'wp_ajax_bw_search_posts', 'bw_search_posts' );
add_action( 'wp_ajax_nopriv_bw_search_posts', 'bw_search_posts' );
