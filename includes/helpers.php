<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
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
add_action( 'wp_ajax_bw_get_quick_view', 'bw_ajax_get_quick_view_product' );
add_action( 'wp_ajax_nopriv_bw_get_quick_view', 'bw_ajax_get_quick_view_product' );

if ( ! function_exists( 'bw_get_quick_view_add_to_cart_html' ) ) {
    /**
     * Capture the WooCommerce add to cart form markup for a given product.
     *
     * @param \WC_Product $product WooCommerce product instance.
     *
     * @return string
     */
    function bw_get_quick_view_add_to_cart_html( $wc_product ) {
        if ( ! $wc_product instanceof \WC_Product ) {
            return '';
        }

        global $product, $post;

        $previous_product = isset( $product ) ? $product : null;
        $previous_post    = isset( $post ) ? $post : null;
        $post_object      = get_post( $wc_product->get_id() );

        if ( $post_object instanceof \WP_Post ) {
            $post = $post_object; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.OverrideProhibited
            setup_postdata( $post );
        }

        $product = $wc_product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.OverrideProhibited

        ob_start();

        if ( $wc_product->is_type( 'variable' ) && function_exists( 'woocommerce_variable_add_to_cart' ) ) {
            woocommerce_variable_add_to_cart();
        } elseif ( $wc_product->is_type( 'grouped' ) && function_exists( 'woocommerce_grouped_add_to_cart' ) ) {
            woocommerce_grouped_add_to_cart();
        } elseif ( $wc_product->is_type( 'external' ) && function_exists( 'woocommerce_external_add_to_cart' ) ) {
            woocommerce_external_add_to_cart();
        } elseif ( function_exists( 'woocommerce_simple_add_to_cart' ) ) {
            woocommerce_simple_add_to_cart();
        }

        $html = ob_get_clean();

        if ( $post_object instanceof \WP_Post ) {
            wp_reset_postdata();
        }

        if ( $previous_product instanceof \WC_Product ) {
            $product = $previous_product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.OverrideProhibited
        } else {
            unset( $product );
        }

        if ( $previous_post instanceof \WP_Post ) {
            $post = $previous_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.OverrideProhibited
        }

        return $html;
    }
}

if ( ! function_exists( 'bw_ajax_get_quick_view_product' ) ) {
    /**
     * AJAX callback to retrieve WooCommerce product information for Quick View.
     */
    function bw_ajax_get_quick_view_product() {
        check_ajax_referer( 'bw_quick_view_nonce', 'nonce' );

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

        if ( $product_id <= 0 ) {
            wp_send_json_error(
                [ 'message' => __( 'Prodotto non valido.', 'bw' ) ],
                400
            );
        }

        if ( ! function_exists( 'wc_get_product' ) ) {
            wp_send_json_error(
                [ 'message' => __( 'WooCommerce non è attivo.', 'bw' ) ],
                400
            );
        }

        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            wp_send_json_error(
                [ 'message' => __( 'Prodotto non trovato.', 'bw' ) ],
                404
            );
        }

        $image_id  = $product->get_image_id();
        $image_url = '';
        $image_alt = '';

        if ( $image_id ) {
            $image_url = wp_get_attachment_image_url( $image_id, 'large' );
            $image_alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
        }

        if ( empty( $image_url ) && function_exists( 'wc_placeholder_img_src' ) ) {
            $image_url = wc_placeholder_img_src();
        }

        $description = $product->get_short_description();

        if ( empty( $description ) ) {
            $description = $product->get_description();
        }

        if ( function_exists( 'wc_format_content' ) ) {
            $description = wc_format_content( $description );
        } else {
            $description = wpautop( do_shortcode( $description ) );
        }

        $price_html = $product->get_price_html();
        $cart_html  = bw_get_quick_view_add_to_cart_html( $product );

        $variations_html = '';
        $add_to_cart_html = $cart_html;

        if ( $product->is_type( 'variable' ) ) {
            $variations_html  = $cart_html;
            $add_to_cart_html = '';
        }

        $data = [
            'id'              => $product_id,
            'title'           => $product->get_name(),
            'image'           => $image_url,
            'image_alt'       => $image_alt,
            'description'     => $description,
            'price_html'      => $price_html,
            'variations_html' => $variations_html,
            'add_to_cart_html'=> $add_to_cart_html,
            'permalink'       => get_permalink( $product_id ),
        ];

        wp_send_json_success( $data );
    }
}
