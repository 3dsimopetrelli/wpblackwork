<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Widget_Bw_Slick_Slider extends Widget_Base {

    public function get_name() {
        return 'bw-slick-slider';
    }

    public function get_title() {
        return 'BW Slick Slider';
    }

    public function get_icon() {
        return 'eicon-slider-device';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_script_depends() {
        return [ 'slick-js', 'bw-slick-slider-js' ];
    }

    public function get_style_depends() {
        return [ 'slick-css', 'bw-slick-slider-style' ];
    }

    protected function register_controls() {
        $this->start_controls_section( 'query_section', [
            'label' => __( 'Query', 'bw-elementor-widgets' ),
        ] );

        $post_type_options = $this->get_post_type_options();
        if ( empty( $post_type_options ) ) {
            $post_type_options = [ 'post' => __( 'Post', 'bw-elementor-widgets' ) ];
        }

        $post_type_keys    = array_keys( $post_type_options );
        $default_post_type = array_key_exists( 'post', $post_type_options ) ? 'post' : reset( $post_type_keys );

        $this->add_control( 'content_type', [
            'label'   => __( 'Post Type', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'options' => $post_type_options,
            'default' => $default_post_type,
        ] );

        $this->add_control(
            'specific_posts',
            [
                'label'       => __( 'Post specifici', 'bw' ),
                'type'        => Controls_Manager::SELECT2,
                'label_block' => true,
                'multiple'    => true,
                'options'     => [],
                'description' => __( 'Cerca e seleziona post o prodotti specifici per titolo', 'bw' ),
                'render_type' => 'none',
                'autocomplete' => [
                    'object'  => 'post',
                    'display' => 'detailed',
                    'query'   => [
                        'post_type'      => $post_type_keys,
                        'posts_per_page' => 10,
                    ],
                ],
            ]
        );

        $this->add_control( 'post_categories', [
            'label'       => __( 'Categoria', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::SELECT2,
            'label_block' => true,
            'options'     => $this->get_taxonomy_terms_options( 'category' ),
            'multiple'    => true,
            'condition'   => [ 'content_type' => 'post' ],
        ] );

        $this->add_control(
            'product_categories',
            [
                'label'       => __( 'Categoria', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::SELECT2,
                'label_block' => true,
                'multiple'    => true,
                'options'     => bw_get_product_categories_options(),
                'condition'   => [ 'content_type' => 'product' ],
                'description' => __( 'Seleziona una o più categorie prodotto.', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'product_cat_parent',
            [
                'label'       => __( 'Categoria Padre', 'bw' ),
                'type'        => Controls_Manager::SELECT2,
                'label_block' => true,
                'multiple'    => false,
                'options'     => bw_get_parent_product_categories(),
                'condition'   => [ 'content_type' => 'product' ],
            ]
        );

        $this->add_control(
            'product_cat_child',
            [
                'label'       => __( 'Sotto-categoria', 'bw' ),
                'type'        => Controls_Manager::SELECT2,
                'label_block' => true,
                'multiple'    => true,
                'options'     => [],
                'condition'   => [
                    'content_type'       => 'product',
                    'product_cat_parent!' => '',
                ],
                'description' => __( 'Seleziona una o più sottocategorie della categoria padre scelta.', 'bw' ),
            ]
        );

        $this->add_control( 'include_ids', [
            'label'       => __( 'ID specifici', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::TEXT,
            'placeholder' => __( 'es. 12, 45, 78', 'bw-elementor-widgets' ),
            'description' => __( 'Inserisci gli ID separati da virgola.', 'bw-elementor-widgets' ),
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'layout_section', [
            'label' => __( 'Layout', 'bw-elementor-widgets' ),
        ] );

        $column_options = [];
        foreach ( range( 2, 7 ) as $column ) {
            $column_options[ $column ] = (string) $column;
        }

        $this->add_control( 'columns', [
            'label'   => __( 'Numero colonne', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'options' => $column_options,
            'default' => '3',
        ] );

        $this->add_control( 'gap', [
            'label' => __( 'Spazio tra colonne', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range' => [
                'px' => [ 'min' => 0, 'max' => 80, 'step' => 1 ],
            ],
            'default' => [ 'size' => 24, 'unit' => 'px' ],
        ] );

        $this->add_responsive_control( 'side_padding', [
            'label' => __( 'Side Padding', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::DIMENSIONS,
            'allowed_dimensions' => [ 'left', 'right' ],
            'size_units' => [ 'px', '%' ],
            'range' => [
                'px' => [ 'min' => 0, 'max' => 200, 'step' => 1 ],
                '%'  => [ 'min' => 0, 'max' => 30, 'step' => 1 ],
            ],
            'selectors' => [
                '{{WRAPPER}} .bw-slick-slider' => 'padding-left: {{LEFT}}{{UNIT}}; padding-right: {{RIGHT}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'top_spacing', [
            'label' => __( 'Top Spacing', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%' ],
            'range' => [
                'px' => [ 'min' => 0, 'max' => 200, 'step' => 1 ],
                '%'  => [ 'min' => 0, 'max' => 30, 'step' => 1 ],
            ],
            'selectors' => [
                '{{WRAPPER}} .bw-slick-slider' => 'padding-top: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'bottom_spacing', [
            'label' => __( 'Bottom Spacing', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%' ],
            'range' => [
                'px' => [ 'min' => 0, 'max' => 200, 'step' => 1 ],
                '%'  => [ 'min' => 0, 'max' => 30, 'step' => 1 ],
            ],
            'selectors' => [
                '{{WRAPPER}} .bw-slick-slider' => 'padding-bottom: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'slide_padding', [
            'label' => __( 'Slide Padding', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em', 'rem' ],
            'selectors' => [
                '{{WRAPPER}} .bw-slick-slider .bw-slick-item__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .bw-slick-slider .bw-ss__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_text_box',
            [
                'label' => __( 'Contenitore Testi', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'text_box_background_color',
            [
                'label'     => __( 'Background contenitore testi', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-slick-slider-text-box' => 'background-color: {{VALUE}};',
                ],
                'default' => 'transparent',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section( 'typography_section', [
            'label' => __( 'Typography', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'title_typography_heading', [
            'label' => __( 'Titolo', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::HEADING,
        ] );

        $this->add_control(
            'title_color',
            [
                'label' => __( 'Titolo - Colore', 'bw' ),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-slider-title' => 'color: {{VALUE}};',
                ],
                'default' => '#080808',
            ]
        );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'title_typography',
            'selector' => '{{WRAPPER}} .bw-slick-slider .bw-slick-title',
        ] );

        $this->add_responsive_control( 'title_margin_top', [
            'label' => __( 'Margin Top', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range' => [
                'px' => [ 'min' => -100, 'max' => 200, 'step' => 1 ],
            ],
            'selectors' => [
                '{{WRAPPER}} .bw-slick-slider .bw-slick-title' => 'margin-top: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'title_margin_bottom', [
            'label' => __( 'Margin Bottom', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range' => [
                'px' => [ 'min' => -100, 'max' => 200, 'step' => 1 ],
            ],
            'selectors' => [
                '{{WRAPPER}} .bw-slick-slider .bw-slick-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'description_typography_heading', [
            'label' => __( 'Descrizione', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_control(
            'description_color',
            [
                'label' => __( 'Descrizione - Colore', 'bw' ),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-slider-description' => 'color: {{VALUE}};',
                ],
                'default' => '#080808',
            ]
        );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'description_typography',
            'selector' => '{{WRAPPER}} .bw-slick-slider .bw-slick-description',
        ] );

        $this->add_responsive_control( 'description_margin_top', [
            'label' => __( 'Margin Top', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range' => [
                'px' => [ 'min' => -100, 'max' => 200, 'step' => 1 ],
            ],
            'selectors' => [
                '{{WRAPPER}} .bw-slick-slider .bw-slick-description' => 'margin-top: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'description_margin_bottom', [
            'label' => __( 'Margin Bottom', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range' => [
                'px' => [ 'min' => -100, 'max' => 200, 'step' => 1 ],
            ],
            'selectors' => [
                '{{WRAPPER}} .bw-slick-slider .bw-slick-description' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'price_typography_heading', [
            'label' => __( 'Prezzo', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_control(
            'price_color',
            [
                'label' => __( 'Prezzo - Colore', 'bw' ),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-slider-price' => 'color: {{VALUE}};',
                ],
                'default' => '#080808',
            ]
        );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'price_typography',
            'selector' => '{{WRAPPER}} .bw-slick-slider .bw-slick-price',
        ] );

        $this->add_responsive_control( 'price_margin_top', [
            'label' => __( 'Margin Top', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range' => [
                'px' => [ 'min' => -100, 'max' => 200, 'step' => 1 ],
            ],
            'selectors' => [
                '{{WRAPPER}} .bw-slick-slider .bw-slick-price' => 'margin-top: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'price_margin_bottom', [
            'label' => __( 'Margin Bottom', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range' => [
                'px' => [ 'min' => -100, 'max' => 200, 'step' => 1 ],
            ],
            'selectors' => [
                '{{WRAPPER}} .bw-slick-slider .bw-slick-price' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_arrows_style',
            [
                'label' => __( 'Navigation Arrows', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'arrows_color',
            [
                'label' => __( 'Arrow Color', 'bw' ),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-slick-prev img, {{WRAPPER}} .bw-slick-next img' => 'filter: brightness(0) saturate(100%) invert(0%) sepia(0%) saturate(0%) hue-rotate(0deg) brightness(0) contrast(100%) drop-shadow(0 0 0 {{VALUE}});',
                ],
            ]
        );

        $this->add_responsive_control(
            'arrows_size',
            [
                'label' => __( 'Arrow Size', 'bw' ),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [ 'min' => 10, 'max' => 100 ],
                ],
                'default' => [
                    'size' => 30,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bw-slick-prev img, {{WRAPPER}} .bw-slick-next img' => 'width: {{SIZE}}{{UNIT}}; height: auto;',
                ],
            ]
        );

        $this->add_responsive_control(
            'arrows_padding',
            [
                'label' => __( 'Arrow Padding', 'bw' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'default' => [
                    'top' => 0,
                    'right' => 0,
                    'bottom' => 0,
                    'left' => 0,
                    'unit' => 'px',
                    'isLinked' => true,
                ],
                'selectors' => [
                    '{{WRAPPER}} .bw-slick-prev, {{WRAPPER}} .bw-slick-next' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'arrows_vertical_offset',
            [
                'label' => __( 'Vertical Offset', 'bw' ),
                'type'  => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [ 'min' => -300, 'max' => 300, 'step' => 1 ],
                    '%'  => [ 'min' => -100, 'max' => 100, 'step' => 1 ],
                ],
                'default' => [
                    'size' => 15,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bw-slick-prev, {{WRAPPER}} .bw-slick-next' => 'bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'arrows_prev_horizontal_offset',
            [
                'label' => __( 'Previous Arrow Horizontal Offset', 'bw' ),
                'type'  => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [ 'min' => -300, 'max' => 300, 'step' => 1 ],
                    '%'  => [ 'min' => -100, 'max' => 100, 'step' => 1 ],
                ],
                'default' => [
                    'size' => 55,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bw-slick-prev' => 'right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'arrows_next_horizontal_offset',
            [
                'label' => __( 'Next Arrow Horizontal Offset', 'bw' ),
                'type'  => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [ 'min' => -300, 'max' => 300, 'step' => 1 ],
                    '%'  => [ 'min' => -100, 'max' => 100, 'step' => 1 ],
                ],
                'default' => [
                    'size' => 15,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bw-slick-next' => 'right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section( 'images_section', [
            'label' => __( 'Immagini', 'bw-elementor-widgets' ),
        ] );

        $this->add_responsive_control( 'image_height', [
            'label'      => __( 'Altezza immagini', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 1200, 'step' => 1 ],
                '%'  => [ 'min' => 0, 'max' => 100, 'step' => 1 ],
            ],
            'default'    => [
                'size' => 420,
                'unit' => 'px',
            ],
            'render_type' => 'template',
            'selectors'  => [
                '{{WRAPPER}} .bw-slick-slider' => '--bw-image-height: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'image_column_width', [
            'label'      => __( 'Larghezza colonna', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%' ],
            'range'      => [
                'px' => [ 'min' => 100, 'max' => 800, 'step' => 1 ],
                '%'  => [ 'min' => 10, 'max' => 100, 'step' => 1 ],
            ],
            'render_type' => 'template',
            'selectors'  => [
                '{{WRAPPER}} .bw-slick-slider' => '--bw-column-width: {{SIZE}}{{UNIT}};',
            ],
            'description' => __( 'Controlla la larghezza massima delle colonne dello slider.', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'image_crop', [
            'label'        => __( 'Ritaglio proporzioni', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'On', 'bw-elementor-widgets' ),
            'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control(
            'image_container_bg',
            [
                'label' => __( 'Background contenitore immagine', 'bw' ),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-slick-slider .bw-slider-image-container' => 'background-color: {{VALUE}};',
                ],
                'default' => 'transparent',
            ]
        );

        $this->add_responsive_control( 'image_border_radius', [
            'label'      => __( 'Border Radius', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em' ],
            'selectors'  => [
                '{{WRAPPER}} .bw-slick-slider .bw-slider-image-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .bw-ss__media'   => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .bw-ss__media img' => 'border-radius: inherit;',
                '{{WRAPPER}} .bw-ss__overlay' => 'border-radius: inherit;',
            ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'slider_section', [
            'label' => __( 'Slider Settings', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'infinite', [
            'label'        => __( 'Infinite', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'slides_to_scroll', [
            'label'   => __( 'Slides To Scroll', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::NUMBER,
            'min'     => 1,
            'default' => 1,
        ] );

        $this->add_control( 'autoplay', [
            'label'        => __( 'Autoplay', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $this->add_control( 'autoplay_speed', [
            'label'   => __( 'Autoplay Speed (ms)', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::NUMBER,
            'min'     => 100,
            'step'    => 100,
            'default' => 3000,
        ] );

        $this->add_control( 'speed', [
            'label'   => __( 'Transition Speed (ms)', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::NUMBER,
            'min'     => 100,
            'step'    => 50,
            'default' => 500,
        ] );

        $this->add_control(
            'drag_smoothness',
            [
                'label'       => __( 'Fluidità Drag', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::SLIDER,
                'size_units'  => [ 'px' ],
                'range'       => [
                    'px' => [ 'min' => 0, 'max' => 100, 'step' => 1 ],
                ],
                'default'     => [ 'size' => 60, 'unit' => 'px' ],
                'description' => __( 'Regola la fluidità del trascinamento manuale: valori più alti rendono il drag più morbido.', 'bw-elementor-widgets' ),
                'render_type' => 'template',
            ]
        );

        $this->add_control( 'arrows', [
            'label'        => __( 'Arrows', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'dots', [
            'label'        => __( 'Dots', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $this->add_control( 'fade', [
            'label'        => __( 'Fade', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $this->add_control( 'center_mode', [
            'label'        => __( 'Center Mode', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $this->add_control( 'variable_width', [
            'label'        => __( 'Variable Width', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $this->add_control( 'adaptive_height', [
            'label'        => __( 'Adaptive Height', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $this->add_control( 'pause_on_hover', [
            'label'        => __( 'Pause On Hover', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $repeater = new Repeater();
        $repeater->add_control( 'breakpoint', [
            'label'   => __( 'Breakpoint (px)', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::NUMBER,
            'min'     => 320,
            'default' => 1024,
        ] );

        $repeater->add_control( 'slides_to_show', [
            'label'   => __( 'Slides To Show', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'options' => $column_options,
            'default' => '2',
        ] );

        $repeater->add_control( 'slides_to_scroll', [
            'label'   => __( 'Slides To Scroll', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::NUMBER,
            'min'     => 1,
            'default' => 1,
        ] );

        $repeater->add_control( 'responsive_infinite', [
            'label'        => __( 'Infinite', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $repeater->add_control( 'responsive_dots', [
            'label'        => __( 'Dots', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $repeater->add_control( 'responsive_arrows', [
            'label'        => __( 'Arrows', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $repeater->add_control( 'responsive_center_mode', [
            'label'        => __( 'Center Mode', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $repeater->add_control( 'responsive_variable_width', [
            'label'        => __( 'Variable Width', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $this->add_control( 'responsive', [
            'label'       => __( 'Responsive', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $repeater->get_controls(),
            'title_field' => __( 'Breakpoint: {{breakpoint}}px', 'bw-elementor-widgets' ),
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'overlay_buttons_section', [
            'label' => __( 'Overlay Buttons', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'      => 'overlay_buttons_typography',
            'selector'  => '{{WRAPPER}} .bw-slick-slider .bw-ss__btn',
        ] );

        $this->start_controls_tabs( 'overlay_buttons_color_tabs' );

        $this->start_controls_tab( 'overlay_buttons_color_normal', [
            'label' => __( 'Normal', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'overlay_buttons_text_color', [
            'label'     => __( 'Text Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#000000',
            'selectors' => [
                '{{WRAPPER}} .bw-slick-slider' => '--bw-overlay-buttons-color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'overlay_buttons_background_color', [
            'label'     => __( 'Background Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [
                '{{WRAPPER}} .bw-slick-slider' => '--bw-overlay-buttons-background: {{VALUE}};',
            ],
        ] );

        $this->end_controls_tab();

        $this->start_controls_tab( 'overlay_buttons_color_hover', [
            'label' => __( 'Hover', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'overlay_buttons_text_color_hover', [
            'label'     => __( 'Text Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#000000',
            'selectors' => [
                '{{WRAPPER}} .bw-slick-slider' => '--bw-overlay-buttons-color-hover: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'overlay_buttons_background_color_hover', [
            'label'     => __( 'Background Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#f5f5f5',
            'selectors' => [
                '{{WRAPPER}} .bw-slick-slider' => '--bw-overlay-buttons-background-hover: {{VALUE}};',
            ],
        ] );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control( 'overlay_buttons_border_radius', [
            'label'     => __( 'Border Radius', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::SLIDER,
            'size_units'=> [ 'px' ],
            'range'     => [ 'px' => [ 'min' => 0, 'max' => 60, 'step' => 1 ] ],
            'default'   => [ 'size' => 12, 'unit' => 'px' ],
            'selectors' => [
                '{{WRAPPER}} .bw-slick-slider' => '--bw-overlay-buttons-radius: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'overlay_buttons_padding_vertical', [
            'label'     => __( 'Vertical Padding', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::SLIDER,
            'size_units'=> [ 'px' ],
            'range'     => [ 'px' => [ 'min' => 0, 'max' => 60, 'step' => 1 ] ],
            'default'   => [ 'size' => 12, 'unit' => 'px' ],
            'selectors' => [
                '{{WRAPPER}} .bw-slick-slider' => '--bw-overlay-buttons-padding-y: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'overlay_buttons_padding_horizontal', [
            'label'     => __( 'Horizontal Padding', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::SLIDER,
            'size_units'=> [ 'px' ],
            'range'     => [ 'px' => [ 'min' => 0, 'max' => 80, 'step' => 1 ] ],
            'default'   => [ 'size' => 16, 'unit' => 'px' ],
            'selectors' => [
                '{{WRAPPER}} .bw-slick-slider' => '--bw-overlay-buttons-padding-x: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();
    }

    protected function render() {
        $settings            = $this->get_settings_for_display();
        $content_type        = isset( $settings['content_type'] ) ? sanitize_key( $settings['content_type'] ) : 'post';
        $available_post_types = $this->get_post_type_options();
        if ( empty( $available_post_types ) ) {
            $available_post_types = [ 'post' => __( 'Post', 'bw-elementor-widgets' ) ];
        }

        if ( ! array_key_exists( $content_type, $available_post_types ) ) {
            $post_type_keys = array_keys( $available_post_types );
            $content_type   = array_key_exists( 'post', $available_post_types ) ? 'post' : reset( $post_type_keys );
        }
        $columns       = isset( $settings['columns'] ) ? max( 1, absint( $settings['columns'] ) ) : 3;
        $gap           = isset( $settings['gap']['size'] ) ? max( 0, absint( $settings['gap']['size'] ) ) : 24;
        $image_height_data = $this->get_slider_value_with_unit( $settings, 'image_height', 420, 'px' );
        $image_height      = isset( $image_height_data['size'] ) ? max( 0, (float) $image_height_data['size'] ) : 0;
        $image_height_unit = isset( $image_height_data['unit'] ) ? $image_height_data['unit'] : 'px';
        $column_width_data = $this->get_slider_value_with_unit( $settings, 'image_column_width', null, 'px' );
        $column_width      = isset( $column_width_data['size'] ) ? $column_width_data['size'] : null;
        $column_width_unit = isset( $column_width_data['unit'] ) ? $column_width_data['unit'] : 'px';
        $image_crop    = isset( $settings['image_crop'] ) && 'yes' === $settings['image_crop'];
        $include_ids   = isset( $settings['include_ids'] ) ? $this->parse_ids( $settings['include_ids'] ) : [];
        $specific_posts = isset( $settings['specific_posts'] )
            ? array_filter( array_map( 'absint', (array) $settings['specific_posts'] ) )
            : [];
        $product_categories = isset( $settings['product_categories'] )
            ? array_filter( array_map( 'absint', (array) $settings['product_categories'] ) )
            : [];
        $product_cat_parent = isset( $settings['product_cat_parent'] ) ? absint( $settings['product_cat_parent'] ) : 0;
        $product_cat_child  = isset( $settings['product_cat_child'] ) ? array_filter( array_map( 'absint', (array) $settings['product_cat_child'] ) ) : [];
        $slides_scroll = isset( $settings['slides_to_scroll'] ) ? max( 1, absint( $settings['slides_to_scroll'] ) ) : 1;

        $query_args = [
            'post_type'      => $content_type,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ];

        if ( ! empty( $specific_posts ) ) {
            $query_args['post__in'] = $specific_posts;
            $query_args['orderby']  = 'post__in';
        } else {
            if ( ! empty( $include_ids ) ) {
                $query_args['post__in'] = $include_ids;
                $query_args['orderby']  = 'post__in';
            }

            if ( 'product' === $content_type ) {
                $tax_query = [];

                if ( ! empty( $product_categories ) ) {
                    $tax_query[] = [
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => $product_categories,
                    ];
                } elseif ( ! empty( $product_cat_child ) ) {
                    $tax_query[] = [
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => $product_cat_child,
                    ];
                } elseif ( $product_cat_parent > 0 ) {
                    $tax_query[] = [
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => [ $product_cat_parent ],
                    ];
                }

                if ( ! empty( $tax_query ) ) {
                    $query_args['tax_query'] = $tax_query;
                }
            } elseif ( 'post' === $content_type ) {
                $category_ids = isset( $settings['post_categories'] ) ? array_filter( array_map( 'absint', (array) $settings['post_categories'] ) ) : [];
                if ( ! empty( $category_ids ) ) {
                    $query_args['category__in'] = $category_ids;
                }
            }
        }

        $slider_settings = $this->prepare_slider_settings( $settings, $columns, $slides_scroll );

        $wrapper_classes = [ 'bw-slick-slider' ];
        if ( ! $image_crop ) {
            $wrapper_classes[] = 'bw-slick-slider--no-crop';
        }

        $wrapper_style = '--bw-columns:' . $columns . ';';
        $wrapper_style .= '--bw-gap:' . $gap . 'px;';
        if ( $image_height > 0 ) {
            $wrapper_style .= '--bw-image-height:' . $image_height . $image_height_unit . ';';
        } else {
            $wrapper_style .= '--bw-image-height:auto;';
        }

        if ( null !== $column_width && '' !== $column_width && (float) $column_width > 0 ) {
            $wrapper_style .= '--bw-column-width:' . $column_width . $column_width_unit . ';';
        } else {
            $wrapper_style .= '--bw-column-width:auto;';
        }

        $slider_settings_json = ! empty( $slider_settings ) ? wp_json_encode( $slider_settings ) : '';
        if ( $slider_settings_json ) {
            $slider_settings_json = htmlspecialchars( $slider_settings_json, ENT_QUOTES, 'UTF-8' );
        }

        $query = new \WP_Query( $query_args );
        ?>
        <div
            class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>"
            data-columns="<?php echo esc_attr( $columns ); ?>"
            <?php if ( $slider_settings_json ) : ?>
                data-slider-settings="<?php echo $slider_settings_json; ?>"
            <?php endif; ?>
            style="<?php echo esc_attr( $wrapper_style ); ?>"
        >
            <?php if ( $query->have_posts() ) : ?>
                <?php
                while ( $query->have_posts() ) :
                    $query->the_post();

                    $post_id   = get_the_ID();
                    $permalink = get_permalink( $post_id );
                    $title     = get_the_title( $post_id );
                    $excerpt   = get_the_excerpt( $post_id );

                    if ( empty( $excerpt ) ) {
                        $excerpt = wp_trim_words( wp_strip_all_tags( get_the_content( null, false, $post_id ) ), 30 );
                    }

                    if ( ! empty( $excerpt ) && false === strpos( $excerpt, '<p' ) ) {
                        $excerpt = '<p>' . $excerpt . '</p>';
                    }

                    $thumbnail_html   = '';
                    $hover_image_html = '';

                    if ( has_post_thumbnail( $post_id ) ) {
                        $thumbnail_args = [
                            'loading' => 'lazy',
                            'class'   => 'bw-slider-main',
                        ];

                        $thumbnail_html = get_the_post_thumbnail( $post_id, 'large', $thumbnail_args );
                    }

                    if ( 'product' === $content_type ) {
                        $hover_image_id = (int) get_post_meta( $post_id, '_bw_slider_hover_image', true );

                        if ( $hover_image_id ) {
                            $hover_image_html = wp_get_attachment_image(
                                $hover_image_id,
                                'large',
                                false,
                                [
                                    'class'   => 'bw-slider-hover',
                                    'loading' => 'lazy',
                                ]
                            );
                        }
                    }

                    $price_html = '';
                    $has_add_to_cart = false;
                    $add_to_cart_url = '';
                    if ( 'product' === $content_type ) {
                        $price_html = $this->get_price_markup( $post_id );

                        if ( function_exists( 'wc_get_product' ) ) {
                            $product = wc_get_product( $post_id );

                            if ( $product ) {
                                if ( $product->is_type( 'variable' ) ) {
                                    $add_to_cart_url = $permalink;
                                } else {
                                    $cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '';

                                    if ( $cart_url ) {
                                        $add_to_cart_url = add_query_arg( 'add-to-cart', $product->get_id(), $cart_url );
                                    }
                                }

                                if ( ! $add_to_cart_url ) {
                                    $add_to_cart_url = $permalink;
                                }

                                $has_add_to_cart = true;
                            }
                        }
                    }

                    ?>
                    <article <?php post_class( 'bw-slick-item' ); ?>>
                        <div class="bw-slick-item__inner bw-ss__card">
                            <?php
                            $media_classes = [ 'bw-slick-item__image', 'bw-ss__media' ];
                            if ( ! $thumbnail_html ) {
                                $media_classes[] = 'bw-slick-item__image--placeholder';
                            }
                            ?>
                            <div class="bw-slider-image-container">
                                <div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $media_classes ) ) ); ?>">
                                    <?php if ( $thumbnail_html ) : ?>
                                        <a class="bw-slick-item__media-link bw-ss__media-link" href="<?php echo esc_url( $permalink ); ?>">
                                            <div class="bw-slick-slider-image<?php echo $hover_image_html ? ' bw-slick-slider-image--has-hover' : ''; ?>">
                                                <?php echo wp_kses_post( $thumbnail_html ); ?>
                                                <?php if ( $hover_image_html ) : ?>
                                                    <?php echo wp_kses_post( $hover_image_html ); ?>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    <?php else : ?>
                                        <span class="bw-slick-item__image-placeholder" aria-hidden="true"></span>
                                    <?php endif; ?>
                                    <?php if ( $thumbnail_html ) : ?>
                                        <div class="overlay-buttons bw-ss__overlay has-buttons">
                                            <div class="bw-ss__buttons bw-slide-buttons<?php echo $has_add_to_cart ? ' bw-ss__buttons--double' : ''; ?>">
                                                <a class="overlay-button overlay-button--view bw-ss__btn bw-view-btn bw-slide-button" href="<?php echo esc_url( $permalink ); ?>">
                                                    <span class="overlay-button__label"><?php esc_html_e( 'View Product', 'bw-elementor-widgets' ); ?></span>
                                                </a>
                                                <?php if ( $has_add_to_cart && $add_to_cart_url ) : ?>
                                                    <a class="overlay-button overlay-button--cart bw-ss__btn bw-btn-addtocart bw-slide-button" href="<?php echo esc_url( $add_to_cart_url ); ?>">
                                                        <span class="overlay-button__label"><?php esc_html_e( 'Add to Cart', 'bw-elementor-widgets' ); ?></span>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="bw-slick-item__content bw-ss__content bw-slider-content bw-slick-slider-text-box">
                                <h3 class="bw-slick-item__title bw-slick-title bw-slider-title">
                                    <a href="<?php echo esc_url( $permalink ); ?>">
                                        <?php echo esc_html( $title ); ?>
                                    </a>
                                </h3>

                                <?php if ( ! empty( $excerpt ) ) : ?>
                                    <div class="bw-slick-item__excerpt bw-slick-description bw-slider-description"><?php echo wp_kses_post( $excerpt ); ?></div>
                                <?php endif; ?>

                                <?php if ( $price_html ) : ?>
                                    <div class="bw-slick-item__price price bw-slick-price bw-slider-price"><?php echo wp_kses_post( $price_html ); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else : ?>
                <div class="bw-slick-placeholder">
                    <div class="bw-slick-placeholder__inner">
                        <?php esc_html_e( 'Nessun contenuto disponibile.', 'bw-elementor-widgets' ); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
    }

    private function get_slider_value_with_unit( $settings, $control_id, $default_size = null, $default_unit = 'px' ) {
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

    private function get_taxonomy_terms_options( $taxonomy ) {
        $options = [];

        if ( ! taxonomy_exists( $taxonomy ) ) {
            return $options;
        }

        $terms = get_terms( [
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
        ] );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return $options;
        }

        foreach ( $terms as $term ) {
            $options[ $term->term_id ] = $term->name;
        }

        return $options;
    }

    private function prepare_slider_settings( $settings, $columns, $slides_scroll ) {
        $slider_settings = [
            'infinite'        => isset( $settings['infinite'] ) && 'yes' === $settings['infinite'],
            'slidesToShow'    => $columns,
            'slidesToScroll'  => $slides_scroll,
            'autoplay'        => isset( $settings['autoplay'] ) && 'yes' === $settings['autoplay'],
            'autoplaySpeed'   => isset( $settings['autoplay_speed'] ) ? max( 100, absint( $settings['autoplay_speed'] ) ) : 3000,
            'speed'           => isset( $settings['speed'] ) ? max( 100, absint( $settings['speed'] ) ) : 500,
            'arrows'          => isset( $settings['arrows'] ) ? 'yes' === $settings['arrows'] : true,
            'dots'            => isset( $settings['dots'] ) && 'yes' === $settings['dots'],
            'fade'            => isset( $settings['fade'] ) && 'yes' === $settings['fade'],
            'centerMode'      => isset( $settings['center_mode'] ) && 'yes' === $settings['center_mode'],
            'variableWidth'   => isset( $settings['variable_width'] ) && 'yes' === $settings['variable_width'],
            'adaptiveHeight'  => isset( $settings['adaptive_height'] ) && 'yes' === $settings['adaptive_height'],
            'pauseOnHover'    => isset( $settings['pause_on_hover'] ) ? 'yes' === $settings['pause_on_hover'] : true,
        ];

        $slider_settings['dragSmoothness'] = $this->get_drag_smoothness_value( $settings );

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

    private function get_drag_smoothness_value( $settings ) {
        $default = 60.0;

        if ( ! isset( $settings['drag_smoothness'] ) ) {
            return $default;
        }

        $value = $settings['drag_smoothness'];

        if ( is_array( $value ) ) {
            $value = isset( $value['size'] ) ? $value['size'] : null;
        }

        if ( null === $value || '' === $value ) {
            return $default;
        }

        $number = is_numeric( $value ) ? (float) $value : $default;

        if ( ! is_finite( $number ) ) {
            $number = $default;
        }

        return max( 0, min( 100, $number ) );
    }

    private function get_price_markup( $post_id ) {
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
}
