<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function bw_ss_get_trending_row_definitions() {
    return [
        'staff_picks' => [
            'label_key' => 'staff_select',
            'title'     => __( 'Staff Selected', 'bw-elementor-widgets' ),
            'limit'     => 6,
        ],
        'sale'        => [
            'label_key' => 'sale',
            'title'     => __( 'Sale', 'bw-elementor-widgets' ),
            'limit'     => 6,
        ],
        'new'         => [
            'label_key' => 'new',
            'title'     => __( 'New', 'bw-elementor-widgets' ),
            'limit'     => 6,
        ],
        'free'        => [
            'label_key' => 'free_download',
            'title'     => __( 'Free Download', 'bw-elementor-widgets' ),
            'limit'     => 6,
        ],
    ];
}

function bw_ss_is_product_in_scope( $product_id, $scope ) {
    $scope      = bw_ss_normalize_scope_param( $scope );
    $product_id = absint( $product_id );

    if ( $product_id <= 0 ) {
        return false;
    }

    if ( 'all' === $scope ) {
        return true;
    }

    return function_exists( 'bw_fpw_resolve_product_family_slug_from_product' )
        && bw_fpw_resolve_product_family_slug_from_product( $product_id ) === $scope;
}

function bw_ss_limit_product_ids_for_scope( $product_ids, $scope, $limit ) {
    $scope    = bw_ss_normalize_scope_param( $scope );
    $limit    = max( 1, absint( $limit ) );
    $resolved = [];

    foreach ( (array) $product_ids as $product_id ) {
        $product_id = absint( $product_id );

        if ( $product_id <= 0 || isset( $resolved[ $product_id ] ) ) {
            continue;
        }

        if ( 'publish' !== get_post_status( $product_id ) ) {
            continue;
        }

        if ( ! bw_ss_is_product_in_scope( $product_id, $scope ) ) {
            continue;
        }

        $resolved[ $product_id ] = $product_id;

        if ( count( $resolved ) >= $limit ) {
            break;
        }
    }

    return array_values( $resolved );
}

function bw_ss_get_recent_product_ids_for_trending( $limit = 48 ) {
    return get_posts(
        [
            'post_type'              => 'product',
            'post_status'            => 'publish',
            'posts_per_page'         => max( 1, absint( $limit ) ),
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'ignore_sticky_posts'    => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]
    );
}

function bw_ss_get_trending_product_ids_for_label( $label_key, $scope, $limit, $settings = null ) {
    $settings  = is_array( $settings ) ? $settings : bw_get_product_labels_settings();
    $label_key = sanitize_key( (string) $label_key );
    $limit     = max( 1, absint( $limit ) );

    switch ( $label_key ) {
        case 'staff_select':
            if ( empty( $settings['staff_enabled'] ) ) {
                return [];
            }

            return bw_ss_limit_product_ids_for_scope( bw_get_product_label_staff_ids( $settings ), $scope, $limit );

        case 'sale':
            if ( empty( $settings['sale_enabled'] ) || ! function_exists( 'wc_get_product_ids_on_sale' ) ) {
                return [];
            }

            return bw_ss_limit_product_ids_for_scope( wc_get_product_ids_on_sale(), $scope, $limit );

        case 'new':
            if ( empty( $settings['new_enabled'] ) ) {
                return [];
            }

            $matches = [];

            foreach ( bw_ss_get_recent_product_ids_for_trending( $limit * 12 ) as $product_id ) {
                $product_id = absint( $product_id );

                if ( $product_id <= 0 || ! bw_ss_is_product_in_scope( $product_id, $scope ) ) {
                    continue;
                }

                if ( bw_product_matches_new_label( $product_id, $settings ) ) {
                    $matches[] = $product_id;
                }

                if ( count( $matches ) >= $limit ) {
                    break;
                }
            }

            return $matches;

        case 'free_download':
            if ( empty( $settings['free_enabled'] ) || ! function_exists( 'wc_get_product' ) ) {
                return [];
            }

            $matches = [];

            foreach ( bw_ss_get_recent_product_ids_for_trending( $limit * 12 ) as $product_id ) {
                $product_id = absint( $product_id );

                if ( $product_id <= 0 || ! bw_ss_is_product_in_scope( $product_id, $scope ) ) {
                    continue;
                }

                $product = wc_get_product( $product_id );

                if ( $product && bw_product_matches_free_download_label( $product, $settings ) ) {
                    $matches[] = $product_id;
                }

                if ( count( $matches ) >= $limit ) {
                    break;
                }
            }

            return $matches;
    }

    return [];
}

function bw_ss_build_overlay_product_preview( $product_id ) {
    $product_id = absint( $product_id );

    if ( $product_id <= 0 || ! function_exists( 'wc_get_product' ) ) {
        return [];
    }

    $product = wc_get_product( $product_id );

    if ( ! $product ) {
        return [];
    }

    if ( $product->is_type( 'variation' ) && $product->get_parent_id() > 0 ) {
        $product_id = (int) $product->get_parent_id();
        $product    = wc_get_product( $product_id );

        if ( ! $product ) {
            return [];
        }
    }

    $description = trim( wp_strip_all_tags( $product->get_short_description() ) );

    if ( '' === $description ) {
        $excerpt = get_the_excerpt( $product_id );
        $description = trim( wp_strip_all_tags( is_string( $excerpt ) ? $excerpt : '' ) );
    }

    if ( '' === $description ) {
        $description = wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $product_id ) ), 16 );
    }

    $image_url = get_the_post_thumbnail_url( $product_id, 'woocommerce_thumbnail' );

    if ( ! is_string( $image_url ) || '' === $image_url ) {
        $image_url = function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( 'woocommerce_thumbnail' ) : '';
    }

    $labels_html = function_exists( 'bw_render_product_labels' )
        ? bw_render_product_labels( $product, 'archive' )
        : '';

    return [
        'id'          => $product_id,
        'title'       => get_the_title( $product_id ),
        'permalink'   => get_permalink( $product_id ),
        'image_url'   => is_string( $image_url ) ? $image_url : '',
        'description' => $description,
        'price_html'  => function_exists( 'bw_fpw_get_price_markup' ) ? bw_fpw_get_price_markup( $product_id ) : '',
        'labels_html' => $labels_html,
    ];
}

function bw_ss_get_trending_rows( $scope = 'all' ) {
    $scope    = bw_ss_normalize_scope_param( $scope );
    $settings = bw_get_product_labels_settings();
    $rows     = [];

    foreach ( bw_ss_get_trending_row_definitions() as $row_key => $definition ) {
        $label_key = isset( $definition['label_key'] ) ? sanitize_key( $definition['label_key'] ) : '';
        $limit     = isset( $definition['limit'] ) ? absint( $definition['limit'] ) : 6;
        $product_ids = bw_ss_get_trending_product_ids_for_label( $label_key, $scope, $limit, $settings );
        $products    = [];

        foreach ( $product_ids as $product_id ) {
            $preview = bw_ss_build_overlay_product_preview( $product_id );

            if ( ! empty( $preview ) ) {
                $products[] = $preview;
            }
        }

        if ( empty( $products ) ) {
            continue;
        }

        $rows[] = [
            'key'       => $row_key,
            'label_key' => $label_key,
            'title'     => isset( $definition['title'] ) ? (string) $definition['title'] : $row_key,
            'products'  => $products,
        ];
    }

    return $rows;
}
