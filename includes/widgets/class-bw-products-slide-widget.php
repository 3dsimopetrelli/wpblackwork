<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Widget_Bw_Products_Slide extends Widget_Base {

    public function get_name() {
        return 'bw_products_slide';
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
        return [ 'flickity-js', 'bw-products-slide-script' ];
    }

    public function get_style_depends() {
        return [ 'flickity-css', 'bw-products-slide-style' ];
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

        $this->add_control('autoplay', [
            'label' => __( 'Autoplay', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => __( 'On', 'plugin-name' ),
            'label_off' => __( 'Off', 'plugin-name' ),
            'return_value' => 'yes',
            'default' => 'yes',
        ]);

        $this->add_control('autoplay_speed', [
            'label' => __( 'Durata autoplay (ms)', 'plugin-name' ),
            'type' => Controls_Manager::NUMBER,
            'min' => 100,
            'step' => 100,
            'default' => 3000,
            'condition' => [
                'autoplay' => 'yes',
            ],
        ]);

        $this->add_control('prev_next_buttons', [
            'label' => __( 'Mostra arrows', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => __( 'Show', 'plugin-name' ),
            'label_off' => __( 'Hide', 'plugin-name' ),
            'return_value' => 'yes',
            'default' => 'yes',
        ]);

        $this->add_control('page_dots', [
            'label' => __( 'Mostra dots', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => __( 'Show', 'plugin-name' ),
            'label_off' => __( 'Hide', 'plugin-name' ),
            'return_value' => 'yes',
            'default' => 'yes',
        ]);

        $this->add_control('wrap_around', [
            'label' => __( 'Wrap-around', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => __( 'On', 'plugin-name' ),
            'label_off' => __( 'Off', 'plugin-name' ),
            'return_value' => 'yes',
            'default' => 'yes',
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
                'selector' => '{{WRAPPER}} .bw-products-slider .carousel-cell .caption h4',
            ]
        );

        $this->add_control('title_color', [
            'label' => __( 'Colore', 'plugin-name' ),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-products-slider .carousel-cell .caption h4' => 'color: {{VALUE}};',
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
                'selector' => '{{WRAPPER}} .bw-products-slider .carousel-cell .caption p',
            ]
        );

        $this->add_control('description_color', [
            'label' => __( 'Colore', 'plugin-name' ),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-products-slider .carousel-cell .caption p' => 'color: {{VALUE}};',
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
                'selector' => '{{WRAPPER}} .bw-products-slider .carousel-cell .caption .price',
            ]
        );

        $this->add_control('price_color', [
            'label' => __( 'Colore', 'plugin-name' ),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-products-slider .carousel-cell .caption .price' => 'color: {{VALUE}};',
            ],
        ]);

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $args = [
            'posts_per_page' => -1,
        ];

        $post_type = ! empty( $settings['post_type'] ) ? $settings['post_type'] : 'post';

        if ( 'all' === $post_type ) {
            $args['post_type'] = get_post_types( [ 'public' => true ] );
            $args['orderby']   = 'date';
            $args['order']     = 'DESC';
        } else {
            $args['post_type'] = $post_type;
        }

        if ( $settings['category'] ) {
            $args['category_name'] = $settings['category'];
        }

        if ( $settings['include_ids'] ) {
            $ids = array_filter( array_map( 'intval', array_map( 'trim', explode( ',', $settings['include_ids'] ) ) ) );

            if ( ! empty( $ids ) ) {
                $args['post__in'] = $ids;
            }
        }

        $query = new \WP_Query( $args );

        if ( ! $query->have_posts() ) {
            wp_reset_postdata();
            return;
        }

        $columns = isset( $settings['columns'] ) ? (int) $settings['columns'] : 3;
        $columns = max( 2, min( 6, $columns ) );

        $gap = isset( $settings['gap'] ) ? (int) $settings['gap'] : 20;
        $gap = max( 0, $gap );

        $image_height = isset( $settings['image_height'] ) ? (int) $settings['image_height'] : 0;

        $autoplay_enabled = isset( $settings['autoplay'] ) && 'yes' === $settings['autoplay'];
        $autoplay_speed   = isset( $settings['autoplay_speed'] ) ? (int) $settings['autoplay_speed'] : 3000;
        $autoplay_speed   = $autoplay_speed > 0 ? $autoplay_speed : 3000;

        $prev_next_buttons = isset( $settings['prev_next_buttons'] ) && 'yes' === $settings['prev_next_buttons'];
        $page_dots         = isset( $settings['page_dots'] ) && 'yes' === $settings['page_dots'];
        $wrap_around       = isset( $settings['wrap_around'] ) && 'yes' === $settings['wrap_around'];
        $fade              = isset( $settings['fade'] ) && 'yes' === $settings['fade'];

        $show_title    = isset( $settings['show_title'] ) && 'yes' === $settings['show_title'];
        $show_subtitle = isset( $settings['show_subtitle'] ) && 'yes' === $settings['show_subtitle'];
        $show_price    = isset( $settings['show_price'] ) && 'yes' === $settings['show_price'];

        $style_parts = [
            '--columns:' . $columns,
            '--gap:' . $gap . 'px',
            '--image-height:' . ( $image_height > 0 ? $image_height . 'px' : 'auto' ),
        ];

        $style_attr = implode( ';', $style_parts );

        if ( substr( $style_attr, -1 ) !== ';' ) {
            $style_attr .= ';';
        }

        $slider_attributes = [
            'class="bw-products-slider"',
            'data-columns="' . esc_attr( $columns ) . '"',
            'data-gap="' . esc_attr( $gap ) . '"',
            'data-auto-play="' . esc_attr( $autoplay_enabled ? 'yes' : 'no' ) . '"',
            'data-auto-play-speed="' . esc_attr( $autoplay_speed ) . '"',
            'data-prev-next-buttons="' . esc_attr( $prev_next_buttons ? 'yes' : 'no' ) . '"',
            'data-page-dots="' . esc_attr( $page_dots ? 'yes' : 'no' ) . '"',
            'data-wrap-around="' . esc_attr( $wrap_around ? 'yes' : 'no' ) . '"',
            'data-fade="' . esc_attr( $fade ? 'yes' : 'no' ) . '"',
            'style="' . esc_attr( $style_attr ) . '"',
        ];

        echo '<div ' . implode( ' ', $slider_attributes ) . '>';

        while ( $query->have_posts() ) : $query->the_post();
            echo '<div class="carousel-cell">';
                if ( has_post_thumbnail() ) {
                    echo '<img src="' . esc_url( get_the_post_thumbnail_url( get_the_ID(), 'medium' ) ) . '" alt="' . esc_attr( get_the_title() ) . '">';
                }
                echo '<div class="caption">';
                    if ( $show_title ) {
                        echo '<h4>' . esc_html( get_the_title() ) . '</h4>';
                    }
                    if ( $show_subtitle ) {
                        echo '<p>' . esc_html( get_the_excerpt() ) . '</p>';
                    }
                    if ( $show_price && function_exists( 'wc_get_product' ) ) {
                        $product = wc_get_product( get_the_ID() );
                        if ( $product ) {
                            echo '<span class="price">' . wp_kses_post( $product->get_price_html() ) . '</span>';
                        }
                    }
                echo '</div>';
            echo '</div>';
        endwhile;

        echo '</div>';
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
