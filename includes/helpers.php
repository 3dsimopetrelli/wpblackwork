<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
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
        if ( ! current_user_can( 'edit_posts' ) ) {
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

if ( ! function_exists( 'bw_search_posts' ) ) {
    /**
     * AJAX callback to search posts by title for Select2 controls.
     */
    function bw_search_posts() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die();
        }

        $term      = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
        $post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : 'any';

        if ( ! empty( $post_type ) && 'any' !== $post_type && ! post_type_exists( $post_type ) ) {
            $post_type = 'any';
        }

        $query = new WP_Query(
            [
                'post_type'      => ! empty( $post_type ) ? $post_type : 'any',
                's'              => $term,
                'posts_per_page' => 20,
                'post_status'    => 'publish',
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
