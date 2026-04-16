<?php
/**
 * BW Widget Helper Class
 *
 * Provides shared utility methods for Elementor widgets to reduce code duplication.
 * Contains common functionality used across multiple widget classes.
 *
 * @package BW_Elementor_Widgets
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helper class for Elementor widgets with common utility methods.
 */
class BW_Widget_Helper {

    /**
     * Parse a comma-separated string of IDs into an array of integers.
     *
     * Used by widgets to parse IDs from control inputs (e.g., "1, 2, 3" -> [1, 2, 3]).
     *
     * @param string $ids_string Comma-separated string of IDs.
     * @return array<int> Array of unique integer IDs.
     */
    public static function parse_ids( $ids_string ) {
        if ( empty( $ids_string ) ) {
            return [];
        }

        $parts = array_filter( array_map( 'trim', explode( ',', $ids_string ) ) );
        $ids   = [];

        foreach ( $parts as $part ) {
            if ( is_numeric( $part ) ) {
                $ids[] = (int) $part;
            }
        }

        return array_unique( $ids );
    }

    /**
     * Extract slider value with unit from Elementor control settings.
     *
     * Handles responsive controls and returns normalized size/unit values.
     * Used for controls like width, height, spacing, etc.
     *
     * @param array       $settings      Widget settings array.
     * @param string      $control_id    Control ID to retrieve.
     * @param mixed       $default_size  Default size value if not found.
     * @param string      $default_unit  Default unit (e.g., 'px', '%', 'em').
     * @return array{size: mixed, unit: string} Array with 'size' and 'unit' keys.
     */
    public static function get_slider_value_with_unit( $settings, $control_id, $default_size = null, $default_unit = 'px' ) {
        if ( ! isset( $settings[ $control_id ] ) ) {
            return [
                'size' => $default_size,
                'unit' => $default_unit,
            ];
        }

        $value = $settings[ $control_id ];
        $size  = null;
        $unit  = $default_unit;

        if ( is_array( $value ) ) {
            if ( isset( $value['unit'] ) && '' !== $value['unit'] ) {
                $unit = $value['unit'];
            }

            if ( isset( $value['size'] ) && '' !== $value['size'] ) {
                $size = $value['size'];
            } elseif ( isset( $value['sizes'] ) && is_array( $value['sizes'] ) ) {
                // Responsive controls: try desktop, tablet, mobile in order
                foreach ( [ 'desktop', 'tablet', 'mobile' ] as $device ) {
                    if ( isset( $value['sizes'][ $device ] ) && '' !== $value['sizes'][ $device ] ) {
                        $size = $value['sizes'][ $device ];
                        break;
                    }
                }
            }
        } elseif ( '' !== $value && null !== $value ) {
            $size = $value;
        }

        if ( null === $size ) {
            $size = $default_size;
        }

        if ( is_numeric( $size ) ) {
            $size = (float) $size;
        }

        return [
            'size' => $size,
            'unit' => $unit,
        ];
    }

    /**
     * Get all public post types as options for select controls.
     *
     * Returns an associative array of post type slug => label.
     * Excludes 'attachment' post type and sorts alphabetically.
     *
     * @return array<string,string> Array of post type options [slug => label].
     */
    public static function get_post_type_options() {
        $post_types = get_post_types(
            [
                'public' => true,
            ],
            'objects'
        );

        $options = [];

        if ( empty( $post_types ) || ! is_array( $post_types ) ) {
            return $options;
        }

        foreach ( $post_types as $post_type ) {
            if ( ! isset( $post_type->name ) ) {
                continue;
            }

            if ( 'attachment' === $post_type->name ) {
                continue;
            }

            $label = '';

            if ( isset( $post_type->labels->singular_name ) && '' !== $post_type->labels->singular_name ) {
                $label = $post_type->labels->singular_name;
            } elseif ( isset( $post_type->label ) && '' !== $post_type->label ) {
                $label = $post_type->label;
            } else {
                $label = ucfirst( $post_type->name );
            }

            $options[ $post_type->name ] = $label;
        }

        asort( $options );

        return $options;
    }

    /**
     * Get published product options for select controls.
     *
     * The result is intentionally capped to keep editor controls responsive.
     *
     * @param int $limit Maximum number of products to load.
     * @return array<int,string> Array of product ID => title options.
     */
    public static function get_product_options( $limit = 200 ) {
        static $cache = [];

        $limit = max( 1, absint( $limit ) );
        $cache_key = 'limit_' . $limit;

        if ( isset( $cache[ $cache_key ] ) ) {
            return $cache[ $cache_key ];
        }

        if ( ! function_exists( 'get_posts' ) ) {
            $cache[ $cache_key ] = [];
            return $cache[ $cache_key ];
        }

        $posts = get_posts(
            [
                'post_type'              => 'product',
                'post_status'            => 'publish',
                'posts_per_page'         => $limit,
                'orderby'                => 'title',
                'order'                  => 'ASC',
                'fields'                 => 'all',
                'no_found_rows'          => true,
                'suppress_filters'       => true,
                'update_post_term_cache' => false,
                'update_post_meta_cache' => false,
            ]
        );

        $options = [];

        if ( ! empty( $posts ) && is_array( $posts ) ) {
            foreach ( $posts as $post ) {
                if ( ! isset( $post->ID ) ) {
                    continue;
                }

                $product_id = absint( $post->ID );
                if ( $product_id <= 0 ) {
                    continue;
                }

                $title = isset( $post->post_title ) ? trim( (string) $post->post_title ) : '';
                if ( '' === $title ) {
                    $title = sprintf( __( 'Product #%d', 'bw' ), $product_id );
                }

                $options[ $product_id ] = sprintf( '%s (#%d)', $title, $product_id );
            }
        }

        $cache[ $cache_key ] = $options;

        return $cache[ $cache_key ];
    }
}
