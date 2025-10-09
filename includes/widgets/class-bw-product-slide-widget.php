<?php
use Elementor\Controls_Manager;
use Elementor\Repeater;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Widget_Bw_Slide_Showcase' ) ) {
    require_once __DIR__ . '/class-bw-slide-showcase-widget.php';
}

class Widget_Bw_Product_Slide extends Widget_Bw_Slide_Showcase {

    public function get_name() {
        return 'bw-product-slide';
    }

    public function get_title() {
        return 'BW Product Slide';
    }

    public function get_icon() {
        return 'eicon-post-slider';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_script_depends() {
        return [ 'slick-js', 'bw-product-slide-js' ];
    }

    public function get_style_depends() {
        return [ 'slick-css', 'bw-product-slide-style' ];
    }

    protected function register_controls() {
        parent::register_controls();

        $sections_to_remove = [
            'title_style_section',
            'subtitle_style_section',
            'info_style_section',
            'badge_style_section',
            'button_style_section',
        ];

        foreach ( $sections_to_remove as $section_id ) {
            $this->remove_control( $section_id );
        }

        $this->update_responsive_control(
            'column_width',
            [
                'selectors' => [
                    '{{WRAPPER}} .bw-product-slide-wrapper' => '--bw-product-slide-column-width: {{SIZE}}{{UNIT}}; --bw-column-width: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .bw-product-slide-item'     => '--bw-slide-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->update_control(
            'arrows_color',
            [
                'selectors' => [
                    '{{WRAPPER}} .bw-product-slide .bw-product-slide-arrows .bw-prev img, {{WRAPPER}} .bw-product-slide .bw-product-slide-arrows .bw-next img' => 'filter: brightness(0) saturate(100%) invert(0%) sepia(0%) saturate(0%) hue-rotate(0deg) brightness(0) contrast(100%) drop-shadow(0 0 0 {{VALUE}});',
                ],
            ]
        );

        $this->update_control(
            'arrows_size',
            [
                'default'   => [
                    'size' => 40,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bw-product-slide .bw-product-slide-arrows button img' => 'width: {{SIZE}}{{UNIT}}; height: auto;',
                ],
            ]
        );

        $this->update_control(
            'arrows_padding',
            [
                'selectors' => [
                    '{{WRAPPER}} .bw-product-slide .bw-product-slide-arrows button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->update_control(
            'arrows_vertical_offset',
            [
                'default'   => [
                    'size' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bw-product-slide' => '--bw-product-slide-arrow-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->update_control(
            'arrows_prev_horizontal_offset',
            [
                'default'   => [
                    'size' => 16,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bw-product-slide' => '--bw-product-slide-arrows-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->update_control(
            'arrows_next_horizontal_offset',
            [
                'default'   => [
                    'size' => 15,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bw-product-slide' => '--bw-product-slide-arrows-right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
    }

    protected function render() {
        $settings      = $this->get_settings_for_display();
        $columns       = isset( $settings['columns'] ) ? max( 1, absint( $settings['columns'] ) ) : 1;
        $gap           = isset( $settings['gap']['size'] ) ? max( 0, absint( $settings['gap']['size'] ) ) : 0;
        $image_height  = isset( $settings['image_height'] ) ? max( 0, absint( $settings['image_height'] ) ) : 0;
        $image_crop    = isset( $settings['image_crop'] ) && 'yes' === $settings['image_crop'];
        $include_ids   = isset( $settings['include_ids'] ) ? $this->parse_ids( $settings['include_ids'] ) : [];
        $post_type     = isset( $settings['post_type'] ) ? sanitize_key( $settings['post_type'] ) : 'product';
        $product_type  = isset( $settings['product_type'] ) ? sanitize_key( $settings['product_type'] ) : '';
        $product_cat   = isset( $settings['product_cat_parent'] ) ? absint( $settings['product_cat_parent'] ) : 0;
        $slides_scroll = isset( $settings['slides_to_scroll'] ) ? max( 1, absint( $settings['slides_to_scroll'] ) ) : 1;
        $column_width  = $this->get_column_width_value( $settings );
        $column_unit   = $this->get_column_width_unit( $settings );

        $available_post_types = $this->get_post_type_options();
        if ( empty( $available_post_types ) ) {
            $available_post_types = [ 'post' => __( 'Post', 'bw-elementor-widgets' ) ];
        }

        if ( ! array_key_exists( $post_type, $available_post_types ) ) {
            $post_type_keys = array_keys( $available_post_types );
            $post_type      = array_key_exists( 'product', $available_post_types ) ? 'product' : reset( $post_type_keys );
        }

        $query_args = [
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ];

        if ( ! empty( $include_ids ) ) {
            $query_args['post__in'] = $include_ids;
            $query_args['orderby']  = 'post__in';
        }

        if ( 'product' === $post_type ) {
            $tax_query = [];

            if ( $product_cat > 0 ) {
                $tax_query[] = [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => [ $product_cat ],
                ];
            }

            if ( in_array( $product_type, [ 'simple', 'variable', 'grouped', 'external' ], true ) ) {
                $tax_query[] = [
                    'taxonomy' => 'product_type',
                    'field'    => 'slug',
                    'terms'    => [ $product_type ],
                ];
            } elseif ( 'featured' === $product_type ) {
                $tax_query[] = [
                    'taxonomy' => 'product_visibility',
                    'field'    => 'slug',
                    'terms'    => [ 'featured' ],
                ];
            } elseif ( 'on_sale' === $product_type ) {
                if ( function_exists( 'wc_get_product_ids_on_sale' ) ) {
                    $sale_ids = wc_get_product_ids_on_sale();
                    $sale_ids = array_map( 'absint', (array) $sale_ids );
                    $sale_ids = array_filter( $sale_ids );

                    if ( ! empty( $sale_ids ) ) {
                        if ( isset( $query_args['post__in'] ) ) {
                            $query_args['post__in'] = array_values( array_intersect( $query_args['post__in'], $sale_ids ) );
                        } else {
                            $query_args['post__in'] = $sale_ids;
                        }
                    } else {
                        $query_args['post__in'] = [ 0 ];
                    }
                }
            }

            if ( ! empty( $tax_query ) ) {
                $query_args['tax_query'] = $tax_query;
            }
        }

        if ( isset( $query_args['post__in'] ) && empty( $query_args['post__in'] ) ) {
            $query_args['post__in'] = [ 0 ];
        }

        $slider_settings      = $this->prepare_slider_settings( $settings, $columns, $slides_scroll );
        $slider_settings_json = ! empty( $slider_settings ) ? wp_json_encode( $slider_settings ) : '';
        if ( $slider_settings_json ) {
            $slider_settings_json = htmlspecialchars( $slider_settings_json, ENT_QUOTES, 'UTF-8' );
        }

        $query  = new \WP_Query( $query_args );
        $slides = [];

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id   = get_the_ID();
                $image_url = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail_url( $post_id, 'large' ) : '';

                if ( ! $image_url ) {
                    continue;
                }

                $slides[] = [
                    'image' => [
                        'url' => $image_url,
                    ],
                    'title' => get_the_title( $post_id ),
                ];
            }
        }

        wp_reset_postdata();

        if ( empty( $slides ) ) {
            echo '<div class="bw-product-slide-placeholder">' . esc_html__( 'Nessun prodotto trovato.', 'bw-elementor-widgets' ) . '</div>';
            return;
        }

        $total_slides = count( $slides );
        $product_title = ! empty( $slides ) ? $slides[0]['title'] : '';
        $wrapper_style  = '--bw-product-slide-gap:' . $gap . 'px;';
        if ( $image_height > 0 ) {
            $wrapper_style .= '--bw-product-slide-image-height:' . $image_height . 'px;';
        } else {
            $wrapper_style .= '--bw-product-slide-image-height:auto;';
        }

        $has_custom_column_width = false;
        if ( null !== $column_width ) {
            $wrapper_style         .= '--bw-product-slide-column-width:' . $column_width . $column_unit . ';';
            $wrapper_style         .= '--bw-column-width:' . $column_width . $column_unit . ';';
            $wrapper_style         .= '--bw-slide-width:' . $column_width . $column_unit . ';';
            $has_custom_column_width = true;
        } else {
            $wrapper_style .= '--bw-product-slide-column-width:auto;';
            $wrapper_style .= '--bw-column-width:auto;';
            $wrapper_style .= '--bw-slide-width:auto;';
        }

        $object_fit  = $image_crop ? 'cover' : 'contain';
        $image_style = $this->build_image_style( $object_fit );
        ?>
        <div class="bw-product-slide">
            <div
                class="bw-product-slide-wrapper slick-slider"
                data-columns="<?php echo esc_attr( $columns ); ?>"
                data-image-crop="<?php echo esc_attr( $image_crop ? 'true' : 'false' ); ?>"
                <?php if ( $has_custom_column_width ) : ?>
                    data-has-column-width="true"
                <?php endif; ?>
                <?php if ( $slider_settings_json ) : ?>
                    data-slider-settings="<?php echo $slider_settings_json; ?>"
                <?php endif; ?>
                style="<?php echo esc_attr( $wrapper_style ); ?>"
            >
                <?php foreach ( $slides as $index => $slide ) : ?>
                    <div class="bw-product-slide-item" data-index="<?php echo esc_attr( $index + 1 ); ?>">
                        <img src="<?php echo esc_url( $slide['image']['url'] ); ?>" alt="<?php echo esc_attr( $slide['title'] ); ?>" style="<?php echo esc_attr( $image_style ); ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="bw-product-slide-ui">
                <div class="bw-product-slide-count">
                    <span class="current">1</span>/<span class="total"><?php echo esc_html( $total_slides ); ?></span>
                </div>
                <div class="bw-product-slide-arrows">
                    <button class="bw-prev" type="button" aria-label="<?php esc_attr_e( 'Slide precedente', 'bw-elementor-widgets' ); ?>">
                        <img src="<?php echo esc_url( BW_MEW_URL . 'assets/img/arrow-l.svg' ); ?>" alt="<?php esc_attr_e( 'Indietro', 'bw-elementor-widgets' ); ?>">
                    </button>
                    <button class="bw-next" type="button" aria-label="<?php esc_attr_e( 'Slide successiva', 'bw-elementor-widgets' ); ?>">
                        <img src="<?php echo esc_url( BW_MEW_URL . 'assets/img/arrow-d.svg' ); ?>" alt="<?php esc_attr_e( 'Avanti', 'bw-elementor-widgets' ); ?>">
                    </button>
                </div>
            </div>
            <!-- POPUP FULLSCREEN -->
            <div class="bw-product-slide-popup">
                <div class="bw-product-slide-popup-header">
                    <div class="bw-popup-title"><?php echo esc_html( $product_title ); ?></div>
                    <div class="bw-popup-close">
                        <button class="bw-popup-close-btn" aria-label="Close">
                            <svg class="close-icon" viewBox="0 0 40 40" width="50" height="50">
                                <line x1="10" y1="10" x2="30" y2="30"></line>
                                <line x1="30" y1="10" x2="10" y2="30"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="bw-popup-content">
                    <?php foreach ( $slides as $slide ) : ?>
                        <img src="<?php echo esc_url( $slide['image']['url'] ); ?>"
                             alt="<?php echo esc_attr( $slide['title'] ); ?>"
                             class="bw-popup-img">
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function get_column_width_value( $settings ) {
        if ( empty( $settings['column_width'] ) || ! is_array( $settings['column_width'] ) ) {
            return null;
        }

        $size = isset( $settings['column_width']['size'] ) ? $settings['column_width']['size'] : null;
        if ( null === $size || '' === $size ) {
            return null;
        }

        $size = (float) $size;
        if ( $size <= 0 ) {
            return null;
        }

        return $size;
    }

    private function get_column_width_unit( $settings ) {
        if ( empty( $settings['column_width'] ) || ! is_array( $settings['column_width'] ) ) {
            return 'px';
        }

        $unit          = isset( $settings['column_width']['unit'] ) ? $settings['column_width']['unit'] : 'px';
        $allowed_units = [ 'px', '%' ];

        if ( ! in_array( $unit, $allowed_units, true ) ) {
            $unit = 'px';
        }

        return $unit;
    }

    private function parse_ids( $ids_string ) {
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

    private function get_post_type_options() {
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

    private function prepare_slider_settings( $settings, $columns, $slides_scroll ) {
        $slider_settings = [
            'infinite'       => isset( $settings['infinite'] ) && 'yes' === $settings['infinite'],
            'slidesToShow'   => $columns,
            'slidesToScroll' => $slides_scroll,
            'autoplay'       => isset( $settings['autoplay'] ) && 'yes' === $settings['autoplay'],
            'autoplaySpeed'  => isset( $settings['autoplay_speed'] ) ? max( 100, absint( $settings['autoplay_speed'] ) ) : 3000,
            'speed'          => isset( $settings['speed'] ) ? max( 100, absint( $settings['speed'] ) ) : 500,
            'arrows'         => isset( $settings['arrows'] ) ? 'yes' === $settings['arrows'] : true,
            'dots'           => isset( $settings['dots'] ) && 'yes' === $settings['dots'],
            'fade'           => isset( $settings['fade'] ) && 'yes' === $settings['fade'],
            'centerMode'     => isset( $settings['center_mode'] ) && 'yes' === $settings['center_mode'],
            'variableWidth'  => isset( $settings['variable_width'] ) && 'yes' === $settings['variable_width'],
            'adaptiveHeight' => isset( $settings['adaptive_height'] ) && 'yes' === $settings['adaptive_height'],
            'pauseOnHover'   => isset( $settings['pause_on_hover'] ) ? 'yes' === $settings['pause_on_hover'] : true,
        ];

        $slider_settings['slidesToScroll'] = max( 1, min( $slider_settings['slidesToScroll'], $columns ) );

        $responsive = [];
        if ( ! empty( $settings['responsive'] ) && is_array( $settings['responsive'] ) ) {
            foreach ( $settings['responsive'] as $item ) {
                if ( empty( $item['breakpoint'] ) ) {
                    continue;
                }

                $breakpoint = absint( $item['breakpoint'] );
                if ( $breakpoint <= 0 ) {
                    continue;
                }

                $item_settings = [];

                if ( ! empty( $item['slides_to_show'] ) ) {
                    $item_settings['slidesToShow'] = max( 1, absint( $item['slides_to_show'] ) );
                }

                if ( ! empty( $item['slides_to_scroll'] ) ) {
                    $item_settings['slidesToScroll'] = max( 1, absint( $item['slides_to_scroll'] ) );
                }

                if ( isset( $item['responsive_infinite'] ) ) {
                    $item_settings['infinite'] = 'yes' === $item['responsive_infinite'];
                }

                if ( isset( $item['responsive_dots'] ) ) {
                    $item_settings['dots'] = 'yes' === $item['responsive_dots'];
                }

                if ( isset( $item['responsive_arrows'] ) ) {
                    $item_settings['arrows'] = 'yes' === $item['responsive_arrows'];
                }

                if ( isset( $item['responsive_center_mode'] ) ) {
                    $item_settings['centerMode'] = 'yes' === $item['responsive_center_mode'];
                }

                if ( isset( $item['responsive_variable_width'] ) ) {
                    $item_settings['variableWidth'] = 'yes' === $item['responsive_variable_width'];
                }

                if ( isset( $item_settings['slidesToShow'], $item_settings['slidesToScroll'] ) ) {
                    $item_settings['slidesToScroll'] = min( $item_settings['slidesToScroll'], $item_settings['slidesToShow'] );
                }

                if ( ! empty( $item_settings ) ) {
                    $responsive[] = [
                        'breakpoint' => $breakpoint,
                        'settings'   => $item_settings,
                    ];
                }
            }
        }

        if ( ! empty( $responsive ) ) {
            $slider_settings['responsive'] = $responsive;
        }

        return $slider_settings;
    }

    private function build_image_style( $object_fit ) {
        $allowed_fits = [ 'cover', 'contain', 'fill', 'none', 'scale-down' ];
        $fit_value    = in_array( $object_fit, $allowed_fits, true ) ? $object_fit : 'cover';

        return 'object-fit: ' . $fit_value . '; object-position: top center;';
    }
}
