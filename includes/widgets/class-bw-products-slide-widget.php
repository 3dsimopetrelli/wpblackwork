<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Widget_Bw_Products_Slide extends Widget_Base {

    public function get_name() {
        return 'bw-products-slide';
    }

    public function get_title() {
        return 'BW Products Slide';
    }

    public function get_icon() {
        return 'eicon-slider-full-screen';
    }

    // 👇 Ora usa la categoria "Black Work"
    public function get_categories() {
        return [ 'black-work' ];
    }

    public function get_script_depends() {
        return [ 'flickity-js', 'bw-products-js' ];
    }

    public function get_style_depends() {
        return [ 'flickity-css', 'bw-products-style' ];
    }

    protected function register_controls() {
        // SECTION QUERY
        $this->start_controls_section('query_section', [
            'label' => __( 'Query', 'plugin-name' ),
        ]);

        $this->add_control('post_type', [
            'label' => __( 'Post Type', 'plugin-name' ),
            'type' => Controls_Manager::SELECT,
            'options' => $this->get_post_type_options(),
            'default' => 'post',
        ]);
        $this->add_control('category', [
            'label' => __( 'Category', 'plugin-name' ),
            'type' => Controls_Manager::TEXT,
            'default' => ''
        ]);
        $this->add_control('include_ids', [
            'label' => __( 'Include IDs', 'plugin-name' ),
            'type' => Controls_Manager::TEXT,
            'default' => ''
        ]);
        $this->end_controls_section();

        // SECTION DISPLAY OPTIONS
        $this->start_controls_section('display_section', [
            'label' => __( 'Display Options', 'plugin-name' ),
        ]);
        $this->add_control('show_title', [
            'label' => __( 'Mostra titolo', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => 'yes'
        ]);
        $this->add_control('show_subtitle', [
            'label' => __( 'Mostra sottotitolo', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => 'yes'
        ]);
        $this->add_control('show_price', [
            'label' => __( 'Mostra prezzo', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => ''
        ]);
        $this->end_controls_section();

        // SECTION LAYOUT
        $this->start_controls_section('layout_section', [
            'label' => __( 'Layout', 'plugin-name' ),
        ]);

        $this->add_control('columns', [
            'label' => __( 'Numero di colonne', 'plugin-name' ),
            'type' => Controls_Manager::NUMBER,
            'min' => 2,
            'max' => 6,
            'step' => 1,
            'default' => 3,
        ]);

        $this->add_control('gap', [
            'label' => __( 'Spazio tra item (px)', 'plugin-name' ),
            'type' => Controls_Manager::NUMBER,
            'min' => 0,
            'default' => 20,
        ]);

        $this->add_control('image_height', [
            'label' => __( 'Altezza immagini (px)', 'plugin-name' ),
            'type' => Controls_Manager::NUMBER,
            'min' => 0,
            'default' => 0,
        ]);
        $this->end_controls_section();

        // SECTION SLIDER SETTINGS
        $this->start_controls_section('slider_section', [
            'label' => __( 'Slider Settings', 'plugin-name' ),
        ]);

        $this->add_control('slider_settings_general_heading', [
            'label' => __( 'General Options', 'plugin-name' ),
            'type' => Controls_Manager::HEADING,
        ]);

        $this->add_control('cell_align', [
            'label' => __( 'Cell Align', 'plugin-name' ),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'left' => __( 'Left', 'plugin-name' ),
                'center' => __( 'Center', 'plugin-name' ),
                'right' => __( 'Right', 'plugin-name' ),
            ],
            'default' => 'left',
        ]);

        $this->add_control('contain', [
            'label' => __( 'Contain', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => 'yes',
        ]);

        $this->add_control('group_cells', [
            'label' => __( 'Group Cells', 'plugin-name' ),
            'type' => Controls_Manager::SELECT,
            'options' => [
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
                'auto' => __( 'Auto', 'plugin-name' ),
            ],
            'default' => '1',
        ]);

        $this->add_control('wrap_around', [
            'label' => __( 'Wrap Around', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => '',
        ]);

        $this->add_control('free_scroll', [
            'label' => __( 'Free Scroll', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => '',
        ]);

        $this->add_control('free_scroll_friction', [
            'label' => __( 'Free Scroll Friction', 'plugin-name' ),
            'type' => Controls_Manager::SLIDER,
            'size_units' => [ '' ],
            'range' => [
                '' => [
                    'min' => 0,
                    'max' => 1,
                    'step' => 0.01,
                ],
            ],
            'default' => [ 'size' => 0.075 ],
        ]);

        $this->add_control('friction', [
            'label' => __( 'Friction', 'plugin-name' ),
            'type' => Controls_Manager::SLIDER,
            'size_units' => [ '' ],
            'range' => [
                '' => [
                    'min' => 0,
                    'max' => 1,
                    'step' => 0.01,
                ],
            ],
            'default' => [ 'size' => 0.28 ],
        ]);

        $this->add_control('selected_attraction', [
            'label' => __( 'Selected Attraction', 'plugin-name' ),
            'type' => Controls_Manager::SLIDER,
            'size_units' => [ '' ],
            'range' => [
                '' => [
                    'min' => 0,
                    'max' => 1,
                    'step' => 0.01,
                ],
            ],
            'default' => [ 'size' => 0.025 ],
        ]);

        $this->add_control('draggable', [
            'label' => __( 'Draggable', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => 'yes',
        ]);

        $this->add_control('drag_threshold', [
            'label' => __( 'Drag Threshold', 'plugin-name' ),
            'type' => Controls_Manager::NUMBER,
            'min' => 0,
            'default' => 3,
        ]);

        $this->add_control('percent_position', [
            'label' => __( 'Percent Position', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => 'yes',
        ]);

        $this->add_control('adaptive_height', [
            'label' => __( 'Adaptive Height', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => '',
        ]);

        $this->add_control('resize', [
            'label' => __( 'Resize', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => 'yes',
        ]);

        $this->add_control('watch_css', [
            'label' => __( 'Watch CSS', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => '',
        ]);

        $this->add_control('images_loaded', [
            'label' => __( 'Images Loaded', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => 'yes',
        ]);

        $this->add_control('set_gallery_size', [
            'label' => __( 'Set Gallery Size', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => 'yes',
        ]);

        $this->add_control('slider_settings_navigation_heading', [
            'label' => __( 'Navigation', 'plugin-name' ),
            'type' => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('prev_next_buttons', [
            'label' => __( 'Prev/Next Buttons', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => 'yes',
        ]);

        $this->add_control('page_dots', [
            'label' => __( 'Page Dots', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => 'yes',
        ]);

        $this->add_control('accessibility', [
            'label' => __( 'Accessibility', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => 'yes',
        ]);

        $this->add_control('as_nav_for', [
            'label' => __( 'As Nav For', 'plugin-name' ),
            'type' => Controls_Manager::TEXT,
            'placeholder' => '.selector',
            'default' => '',
        ]);

        $this->add_control('right_to_left', [
            'label' => __( 'Right To Left', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => '',
        ]);

        $this->add_control('slider_settings_autoplay_heading', [
            'label' => __( 'Autoplay', 'plugin-name' ),
            'type' => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('auto_play', [
            'label' => __( 'Autoplay (ms)', 'plugin-name' ),
            'type' => Controls_Manager::NUMBER,
            'min' => 0,
            'step' => 100,
            'default' => 0,
        ]);

        $this->add_control('pause_auto_play_on_hover', [
            'label' => __( 'Pause On Hover', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => 'yes',
        ]);

        $this->add_control('slider_settings_extra_heading', [
            'label' => __( 'Extra', 'plugin-name' ),
            'type' => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('initial_index', [
            'label' => __( 'Initial Index', 'plugin-name' ),
            'type' => Controls_Manager::NUMBER,
            'min' => 0,
            'default' => 0,
        ]);

        $this->add_control('arrow_shape', [
            'label' => __( 'Arrow Shape', 'plugin-name' ),
            'type' => Controls_Manager::TEXTAREA,
            'rows' => 3,
            'default' => 'M 0,50 L 60,100 L 50,90 L 20,60 L 80,60 L 80,40 L 20,40 L 50,10 Z',
        ]);

        $this->add_control('fade', [
            'label' => __( 'Effetto Fade', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => __( 'Fade', 'plugin-name' ),
            'label_off' => __( 'Slide', 'plugin-name' ),
            'return_value' => 'yes',
            'default' => '',
        ]);

        $this->end_controls_section();

        $this->start_controls_section('style_section', [
            'label' => __( 'Style', 'plugin-name' ),
            'tab' => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('title_heading', [
            'label' => __( 'Titolo', 'plugin-name' ),
            'type' => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .bw-products-slider .bw-products-slide-item__content .product-title',
            ]
        );

        $this->add_control('title_color', [
            'label' => __( 'Colore', 'plugin-name' ),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-products-slider .bw-products-slide-item__content .product-title' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('description_heading', [
            'label' => __( 'Descrizione', 'plugin-name' ),
            'type' => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'description_typography',
                'selector' => '{{WRAPPER}} .bw-products-slider .bw-products-slide-item__content .product-description',
            ]
        );

        $this->add_control('description_color', [
            'label' => __( 'Colore', 'plugin-name' ),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-products-slider .bw-products-slide-item__content .product-description' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('price_heading', [
            'label' => __( 'Prezzo', 'plugin-name' ),
            'type' => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'price_typography',
                'selector' => '{{WRAPPER}} .bw-products-slider .bw-products-slide-item__content .product-price',
            ]
        );

        $this->add_control('price_color', [
            'label' => __( 'Colore', 'plugin-name' ),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-products-slider .bw-products-slide-item__content .product-price' => 'color: {{VALUE}};',
            ],
        ]);

        $this->end_controls_section();
    }

    protected function get_price_markup( $post_id ) {
        if ( ! $post_id ) {
            return '';
        }

        $format_price = static function( $value ) {
            if ( '' === $value || null === $value ) {
                return '';
            }

            if ( function_exists( 'wc_price' ) && is_numeric( $value ) ) {
                return wc_price( $value );
            }

            if ( is_numeric( $value ) ) {
                $value = number_format_i18n( (float) $value, 2 );
            }

            return esc_html( $value );
        };

        if ( function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $post_id );
            if ( $product ) {
                $price_html = $product->get_price_html();
                if ( ! empty( $price_html ) ) {
                    return $price_html;
                }

                $regular_price = $product->get_regular_price();
                $sale_price    = $product->get_sale_price();
                $current_price = $product->get_price();

                $regular_markup = $format_price( $regular_price );
                $sale_markup    = $format_price( $sale_price );
                $current_markup = $format_price( $current_price );

                if ( $sale_markup && $regular_markup && $sale_markup !== $regular_markup ) {
                    return '<span class="price-original"><del>' . $regular_markup . '</del></span>' .
                        '<span class="price-sale">' . $sale_markup . '</span>';
                }

                if ( $current_markup ) {
                    return '<span class="price-regular">' . $current_markup . '</span>';
                }
            }
        }

        $regular_price = get_post_meta( $post_id, '_regular_price', true );
        $sale_price    = get_post_meta( $post_id, '_sale_price', true );
        $current_price = get_post_meta( $post_id, '_price', true );

        if ( '' === $current_price && '' === $regular_price && '' === $sale_price ) {
            $additional_keys = [ 'price', 'product_price' ];
            foreach ( $additional_keys as $meta_key ) {
                $meta_value = get_post_meta( $post_id, $meta_key, true );
                if ( '' !== $meta_value && null !== $meta_value ) {
                    $current_price = $meta_value;
                    break;
                }
            }
        }

        $regular_markup = $format_price( $regular_price );
        $sale_markup    = $format_price( $sale_price );
        $current_markup = $format_price( $current_price );

        if ( $sale_markup && $regular_markup && $sale_markup !== $regular_markup ) {
            return '<span class="price-original"><del>' . $regular_markup . '</del></span>' .
                '<span class="price-sale">' . $sale_markup . '</span>';
        }

        if ( $current_markup ) {
            return '<span class="price-regular">' . $current_markup . '</span>';
        }

        if ( $regular_markup ) {
            return '<span class="price-regular">' . $regular_markup . '</span>';
        }

        return '';
    }

    protected function render() {
        $settings     = $this->get_settings_for_display();
        $carousel_id  = 'bw-products-slider-' . $this->get_id();
        $post_type    = ! empty( $settings['post_type'] ) && 'all' !== $settings['post_type'] ? $settings['post_type'] : 'any';
        $columns      = ! empty( $settings['columns'] ) ? absint( $settings['columns'] ) : 3;
        $gap          = isset( $settings['gap'] ) ? max( 0, absint( $settings['gap'] ) ) : 20;
        $image_height = isset( $settings['image_height'] ) ? absint( $settings['image_height'] ) : 0;

        $bool_setting = static function ( array $slider_settings, $key, $default = false ) {
            if ( isset( $slider_settings[ $key ] ) ) {
                return 'yes' === $slider_settings[ $key ];
            }

            return $default;
        };

        $float_setting = static function ( array $slider_settings, $key, $default ) {
            if ( ! isset( $slider_settings[ $key ] ) ) {
                return (float) $default;
            }

            $value = $slider_settings[ $key ];

            if ( is_array( $value ) ) {
                if ( isset( $value['size'] ) && '' !== $value['size'] ) {
                    $value = $value['size'];
                } elseif ( isset( $value['value'] ) && '' !== $value['value'] ) {
                    $value = $value['value'];
                } else {
                    return (float) $default;
                }
            }

            if ( '' === $value || null === $value || ! is_numeric( $value ) ) {
                return (float) $default;
            }

            return (float) $value;
        };

        $int_setting = static function ( array $slider_settings, $key, $default = 0 ) {
            if ( isset( $slider_settings[ $key ] ) && '' !== $slider_settings[ $key ] && null !== $slider_settings[ $key ] ) {
                return absint( $slider_settings[ $key ] );
            }

            return absint( $default );
        };

        $cell_align = isset( $settings['cell_align'] ) && in_array( $settings['cell_align'], [ 'left', 'center', 'right' ], true ) ? $settings['cell_align'] : 'left';
        $contain    = $bool_setting( $settings, 'contain', true );

        $group_cells = isset( $settings['group_cells'] ) ? $settings['group_cells'] : '1';
        if ( 'auto' !== $group_cells ) {
            $group_cells = max( 1, absint( $group_cells ) );
        }

        $wrap_around            = $bool_setting( $settings, 'wrap_around', false );
        $free_scroll            = $bool_setting( $settings, 'free_scroll', false );
        $free_scroll_friction   = $float_setting( $settings, 'free_scroll_friction', 0.075 );
        $friction               = $float_setting( $settings, 'friction', 0.28 );
        $selected_attraction    = $float_setting( $settings, 'selected_attraction', 0.025 );
        $draggable              = $bool_setting( $settings, 'draggable', true );
        $drag_threshold         = max( 0, $int_setting( $settings, 'drag_threshold', 3 ) );
        $percent_position       = $bool_setting( $settings, 'percent_position', true );
        $adaptive_height        = $bool_setting( $settings, 'adaptive_height', false );
        $resize                 = $bool_setting( $settings, 'resize', true );
        $watch_css              = $bool_setting( $settings, 'watch_css', false );
        $images_loaded          = $bool_setting( $settings, 'images_loaded', true );
        $set_gallery_size       = $bool_setting( $settings, 'set_gallery_size', true );
        $prev_next_buttons      = $bool_setting( $settings, 'prev_next_buttons', true );
        $page_dots              = $bool_setting( $settings, 'page_dots', true );
        $accessibility          = $bool_setting( $settings, 'accessibility', true );
        $as_nav_for             = isset( $settings['as_nav_for'] ) ? sanitize_text_field( $settings['as_nav_for'] ) : '';
        $right_to_left          = $bool_setting( $settings, 'right_to_left', false );
        $auto_play              = isset( $settings['auto_play'] ) ? max( 0, absint( $settings['auto_play'] ) ) : 0;
        $pause_on_hover         = $bool_setting( $settings, 'pause_auto_play_on_hover', true );
        $initial_index          = max( 0, $int_setting( $settings, 'initial_index', 0 ) );
        $arrow_shape            = isset( $settings['arrow_shape'] ) ? sanitize_textarea_field( $settings['arrow_shape'] ) : '';
        $fade                   = isset( $settings['fade'] ) && 'yes' === $settings['fade'];

        $bool_to_string = static function ( $value ) {
            return $value ? 'true' : 'false';
        };

        $slider_data_attributes = [
            'data-cell-align'                => $cell_align,
            'data-contain'                   => $bool_to_string( $contain ),
            'data-group-cells'               => 'auto' === $group_cells ? 'auto' : (string) $group_cells,
            'data-wrap-around'               => $bool_to_string( $wrap_around ),
            'data-free-scroll'               => $bool_to_string( $free_scroll ),
            'data-free-scroll-friction'      => (string) $free_scroll_friction,
            'data-friction'                  => (string) $friction,
            'data-selected-attraction'       => (string) $selected_attraction,
            'data-draggable'                 => $bool_to_string( $draggable ),
            'data-drag-threshold'            => (string) $drag_threshold,
            'data-percent-position'          => $bool_to_string( $percent_position ),
            'data-adaptive-height'           => $bool_to_string( $adaptive_height ),
            'data-resize'                    => $bool_to_string( $resize ),
            'data-watch-css'                 => $bool_to_string( $watch_css ),
            'data-images-loaded'             => $bool_to_string( $images_loaded ),
            'data-set-gallery-size'          => $bool_to_string( $set_gallery_size ),
            'data-prev-next-buttons'         => $bool_to_string( $prev_next_buttons ),
            'data-page-dots'                 => $bool_to_string( $page_dots ),
            'data-accessibility'             => $bool_to_string( $accessibility ),
            'data-as-nav-for'                => $as_nav_for,
            'data-right-to-left'             => $bool_to_string( $right_to_left ),
            'data-auto-play'                 => (string) $auto_play,
            'data-pause-auto-play-on-hover'  => $bool_to_string( $pause_on_hover ),
            'data-initial-index'             => (string) $initial_index,
            'data-arrow-shape'               => $arrow_shape,
            'data-fade'                      => $fade ? 'yes' : 'no',
        ];

        $data_attribute_string = '';
        foreach ( $slider_data_attributes as $attribute => $value ) {
            $data_attribute_string .= sprintf( ' %s="%s"', esc_attr( $attribute ), esc_attr( $value ) );
        }

        $query_args = [
            'post_type'           => $post_type,
            'posts_per_page'      => -1,
            'ignore_sticky_posts' => true,
        ];

        if ( ! empty( $settings['include_ids'] ) ) {
            $include_ids = array_filter( array_map( 'absint', explode( ',', $settings['include_ids'] ) ) );
            if ( ! empty( $include_ids ) ) {
                $query_args['post__in']       = $include_ids;
                $query_args['orderby']        = 'post__in';
                $query_args['posts_per_page'] = count( $include_ids );
            }
        }

        if ( ! empty( $settings['category'] ) ) {
            $category = sanitize_text_field( $settings['category'] );

            if ( 'product' === $post_type && taxonomy_exists( 'product_cat' ) ) {
                $query_args['tax_query'] = [
                    [
                        'taxonomy' => 'product_cat',
                        'field'    => 'slug',
                        'terms'    => array_map( 'sanitize_title', array_map( 'trim', explode( ',', $category ) ) ),
                    ],
                ];
            } else {
                $query_args['category_name'] = $category;
            }
        }

        $slider_classes = [ 'bw-products-slider' ];
        if ( $fade ) {
            $slider_classes[] = 'bw-products-slider--fade';
        }

        $wrapper_style  = '--bw-columns:' . max( 1, $columns ) . ';';
        $wrapper_style .= '--bw-gutter:' . max( 0, $gap ) . 'px;';
        $wrapper_style .= '--bw-gap:' . max( 0, $gap ) . 'px;';
        if ( $image_height > 0 ) {
            $wrapper_style .= '--bw-image-height:' . $image_height . 'px;';
        } else {
            $wrapper_style .= '--bw-image-height:none;';
        }

        $query = new \WP_Query( $query_args );
        ?>
        <div
            id="<?php echo esc_attr( $carousel_id ); ?>"
            class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $slider_classes ) ) ); ?>"
            <?php echo $data_attribute_string; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            style="<?php echo esc_attr( $wrapper_style ); ?>"
        >
            <?php if ( $query->have_posts() ) : ?>
                <?php
                while ( $query->have_posts() ) :
                    $query->the_post();

                    $post_id    = get_the_ID();
                    $permalink  = get_permalink( $post_id );
                    $title      = get_the_title( $post_id );
                    $excerpt    = get_the_excerpt( $post_id );
                    $media_html = '';

                    if ( has_post_thumbnail( $post_id ) ) {
                        $media_html = get_the_post_thumbnail( $post_id, 'large', [ 'class' => 'product-image', 'loading' => 'lazy' ] );
                    }

                    if ( empty( $excerpt ) ) {
                        $excerpt = wp_trim_words( wp_strip_all_tags( get_the_content( null, false, $post_id ) ), 20 );
                    }

                    if ( ! empty( $excerpt ) && false === strpos( $excerpt, '<p' ) ) {
                        $excerpt = '<p>' . $excerpt . '</p>';
                    }

                    $price_html = '';
                    if ( isset( $settings['show_price'] ) && 'yes' === $settings['show_price'] ) {
                        $price_html = $this->get_price_markup( $post_id );
                    }
                    ?>
                    <article <?php post_class( 'bw-products-slide-item carousel-cell product-slide' ); ?>>
                        <?php if ( $media_html ) : ?>
                            <div class="bw-products-slide-item__media">
                                <a class="product-link" href="<?php echo esc_url( $permalink ); ?>">
                                    <?php echo wp_kses_post( $media_html ); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="bw-products-slide-item__content">
                            <?php if ( isset( $settings['show_title'] ) && 'yes' === $settings['show_title'] ) : ?>
                                <h3 class="product-title">
                                    <a class="product-link" href="<?php echo esc_url( $permalink ); ?>">
                                        <?php echo esc_html( $title ); ?>
                                    </a>
                                </h3>
                            <?php endif; ?>

                            <?php if ( isset( $settings['show_subtitle'] ) && 'yes' === $settings['show_subtitle'] && ! empty( $excerpt ) ) : ?>
                                <div class="product-description"><?php echo wp_kses_post( $excerpt ); ?></div>
                            <?php endif; ?>

                            <?php if ( isset( $settings['show_price'] ) && 'yes' === $settings['show_price'] && ! empty( $price_html ) ) : ?>
                                <div class="product-price price"><?php echo wp_kses_post( $price_html ); ?></div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else : ?>
                <div class="bw-products-slide-item carousel-cell product-slide">
                    <div class="bw-products-slide-item__content">
                        <p class="product-description"><?php esc_html_e( 'Nessun contenuto disponibile.', 'plugin-name' ); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
    }

    private function get_post_type_options() {
        $options = [ 'all' => __( 'ALL', 'plugin-name' ) ];

        $post_types = get_post_types( [ 'public' => true ], 'objects' );

        foreach ( $post_types as $post_type => $object ) {
            $label = isset( $object->labels->singular_name ) ? $object->labels->singular_name : $post_type;
            $options[ $post_type ] = $label;
        }

        return $options;
    }
}
