<?php
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Widget_Bw_Products_Slide extends \Elementor\Widget_Base {

    public function get_name() {
        return 'bw_products_slide';
    }

    public function get_title() {
        return 'BW Products Slide';
    }

    public function get_icon() {
        return 'eicon-slider-full-screen';
    }

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
        // Query Section
        $this->start_controls_section( 'query_section', [
            'label' => __( 'Query', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'post_type', [
            'label'   => __( 'Post Type', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::TEXT,
            'default' => 'post',
        ] );

        $this->add_control( 'category', [
            'label'   => __( 'Category', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::TEXT,
            'default' => '',
        ] );

        $this->add_control( 'include_ids', [
            'label'       => __( 'Include IDs', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::TEXT,
            'description' => __( 'Inserisci gli ID separati da virgola', 'bw-elementor-widgets' ),
            'default'     => '',
        ] );

        $this->end_controls_section();

        // Display Section
        $this->start_controls_section( 'display_section', [
            'label' => __( 'Display Options', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'show_title', [
            'label'   => __( 'Mostra titolo', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ] );

        $this->add_control( 'show_subtitle', [
            'label'   => __( 'Mostra sottotitolo', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ] );

        $this->add_control( 'show_price', [
            'label'   => __( 'Mostra prezzo', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SWITCHER,
            'default' => '',
        ] );

        $this->end_controls_section();

        // Layout Section
        $this->start_controls_section( 'layout_section', [
            'label' => __( 'Layout', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'columns', [
            'label'   => __( 'Colonne', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'   => [
                'px' => [
                    'min' => 1,
                    'max' => 6,
                ],
            ],
            'default' => [
                'size' => 3,
            ],
        ] );

        $this->add_control( 'gap', [
            'label'   => __( 'Spazio tra colonne (px)', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::NUMBER,
            'default' => 20,
        ] );

        $this->end_controls_section();

        // Slider Section
        $this->start_controls_section( 'slider_section', [
            'label' => __( 'Slider Settings', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'autoplay_speed', [
            'label'   => __( 'Autoplay Speed (ms)', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::NUMBER,
            'default' => 3000,
        ] );

        $this->add_control( 'wrap_around', [
            'label'   => __( 'Loop infinito', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ] );

        $this->add_control( 'fade', [
            'label'   => __( 'Effetto Fade', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SWITCHER,
            'default' => '',
        ] );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $args = [
            'post_type'      => ! empty( $settings['post_type'] ) ? $settings['post_type'] : 'post',
            'posts_per_page' => -1,
        ];

        if ( ! empty( $settings['category'] ) ) {
            $args['category_name'] = $settings['category'];
        }

        if ( ! empty( $settings['include_ids'] ) ) {
            $ids = array_map( 'intval', array_filter( array_map( 'trim', explode( ',', $settings['include_ids'] ) ) ) );
            if ( $ids ) {
                $args['post__in'] = $ids;
            }
        }

        $query = new \WP_Query( $args );

        $columns  = isset( $settings['columns']['size'] ) ? (int) $settings['columns']['size'] : 3;
        $gap      = isset( $settings['gap'] ) ? (int) $settings['gap'] : 20;
        $autoplay = isset( $settings['autoplay_speed'] ) ? (int) $settings['autoplay_speed'] : 0;
        $wrap     = ( isset( $settings['wrap_around'] ) && 'yes' === $settings['wrap_around'] ) ? 'yes' : 'no';
        $fade     = ( isset( $settings['fade'] ) && 'yes' === $settings['fade'] ) ? 'yes' : 'no';

        if ( $query->have_posts() ) {
            echo '<div class="bw-products-slider"'
                . ' data-columns="' . esc_attr( $columns ) . '"'
                . ' data-gap="' . esc_attr( $gap ) . '"'
                . ' data-autoplay="' . esc_attr( $autoplay ) . '"'
                . ' data-wrap="' . esc_attr( $wrap ) . '"'
                . ' data-fade="' . esc_attr( $fade ) . '">';

            while ( $query->have_posts() ) {
                $query->the_post();

                echo '<div class="carousel-cell">';

                if ( has_post_thumbnail() ) {
                    echo '<img src="' . esc_url( get_the_post_thumbnail_url( get_the_ID(), 'medium' ) ) . '" alt="' . esc_attr( get_the_title() ) . '">';
                }

                echo '<div class="caption">';

                if ( ! empty( $settings['show_title'] ) ) {
                    echo '<h4>' . esc_html( get_the_title() ) . '</h4>';
                }

                if ( ! empty( $settings['show_subtitle'] ) ) {
                    echo '<p>' . esc_html( get_the_excerpt() ) . '</p>';
                }

                if ( ! empty( $settings['show_price'] ) && function_exists( 'wc_get_product' ) ) {
                    $product = wc_get_product( get_the_ID() );
                    if ( $product ) {
                        echo '<span class="price">' . wp_kses_post( $product->get_price_html() ) . '</span>';
                    }
                }

                echo '</div>';
                echo '</div>';
            }

            echo '</div>';

            wp_reset_postdata();
        } else {
            echo '<p>' . esc_html__( 'Nessun contenuto trovato.', 'bw-elementor-widgets' ) . '</p>';
        }
    }
}
