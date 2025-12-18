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

// The "Specific Posts" control and related AJAX handlers were removed.

if ( ! function_exists( 'bw_register_widget_assets' ) ) {
    /**
     * Register CSS and JS assets for a widget.
     *
     * Consolidates the pattern used by all widget asset registration functions.
     *
     * @param string $widget_name     Widget name (e.g., 'button', 'wallpost').
     * @param array  $js_dependencies Optional. JavaScript dependencies. Default ['jquery'].
     * @param bool   $register_js     Optional. Whether to register JavaScript. Default true.
     *
     * @return void
     */
    function bw_register_widget_assets( $widget_name, $js_dependencies = [ 'jquery' ], $register_js = true ) {
        // Register CSS
        $css_file    = BW_MEW_PATH . "assets/css/bw-{$widget_name}.css";
        $css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

        wp_register_style(
            "bw-{$widget_name}-style",
            BW_MEW_URL . "assets/css/bw-{$widget_name}.css",
            [],
            $css_version
        );

        // Register JavaScript (optional)
        if ( $register_js ) {
            $js_file = BW_MEW_PATH . "assets/js/bw-{$widget_name}.js";

            if ( file_exists( $js_file ) ) {
                wp_register_script(
                    "bw-{$widget_name}-script",
                    BW_MEW_URL . "assets/js/bw-{$widget_name}.js",
                    $js_dependencies,
                    filemtime( $js_file ),
                    true
                );
            }
        }
    }
}

if ( ! function_exists( 'bw_get_safe_product_permalink' ) ) {
    /**
     * Get a safe permalink for a product.
     *
     * @param int|\WC_Product|null $post_id Product ID or WC_Product object.
     *
     * @return string Safe permalink URL.
     */
    function bw_get_safe_product_permalink( $post_id = null ) {
        try {
            // Handle WC_Product objects
            if ( $post_id instanceof \WC_Product ) {
                return esc_url( $post_id->get_permalink() );
            }

            // Handle numeric IDs
            if ( is_numeric( $post_id ) && $post_id > 0 ) {
                return esc_url( get_permalink( $post_id ) );
            }

            // Fallback
            return bw_get_fallback_url();
        } catch ( \Exception $e ) {
            return bw_get_fallback_url();
        }
    }
}

if ( ! function_exists( 'bw_get_fallback_url' ) ) {
    /**
     * Get a fallback URL when product permalink is invalid.
     *
     * @return string Fallback URL (shop page or home).
     */
    function bw_get_fallback_url() {
        // Try to get shop page URL
        if ( function_exists( 'wc_get_page_permalink' ) ) {
            $shop_url = wc_get_page_permalink( 'shop' );
            if ( ! empty( $shop_url ) && filter_var( $shop_url, FILTER_VALIDATE_URL ) ) {
                return esc_url( $shop_url );
            }
        }

        // Fallback to home
        return esc_url( home_url( '/' ) );
    }
}
