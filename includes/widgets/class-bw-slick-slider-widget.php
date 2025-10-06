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

        $this->add_control( 'content_type', [
            'label'   => __( 'Tipo di contenuto', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'post'    => __( 'Post', 'bw-elementor-widgets' ),
                'product' => __( 'Product', 'bw-elementor-widgets' ),
            ],
            'default' => 'post',
        ] );

        $this->add_control( 'post_categories', [
            'label'       => __( 'Categoria', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::SELECT2,
            'label_block' => true,
            'options'     => $this->get_taxonomy_terms_options( 'category' ),
            'multiple'    => true,
            'condition'   => [ 'content_type' => 'post' ],
        ] );

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

        $this->add_control(
            'product_type',
            [
                'label'     => __( 'Product Type', 'bw' ),
                'type'      => Controls_Manager::SELECT,
                'options'   => [
                    ''               => __( 'All', 'bw' ),
                    'digital_asset'  => __( 'Digital Assets', 'bw' ),
                    'book'           => __( 'Books', 'bw' ),
                    'print'          => __( 'Prints', 'bw' ),
                ],
                'default'   => '',
                'condition' => [ 'content_type' => 'product' ],
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

        $this->end_controls_section();

        $this->start_controls_section( 'typography_section', [
            'label' => __( 'Typography', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'title_typography_heading', [
            'label' => __( 'Titolo', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::HEADING,
        ] );

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

        $this->end_controls_section();

        $this->start_controls_section( 'images_section', [
            'label' => __( 'Immagini', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'image_height', [
            'label'   => __( 'Altezza immagini (px)', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::NUMBER,
            'min'     => 0,
            'default' => 420,
        ] );

        $this->add_control( 'image_crop', [
            'label'        => __( 'Ritaglio proporzioni', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'On', 'bw-elementor-widgets' ),
            'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_responsive_control( 'image_border_radius', [
            'label'      => __( 'Border Radius', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em' ],
            'selectors'  => [
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

        $this->start_controls_section(
            'section_view_buttons',
            [
                'label' => __( 'View Buttons', 'bw-elementor-widgets' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'view_buttons_enable',
            [
                'label'        => __( 'Enable Buttons', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'On', 'bw-elementor-widgets' ),
                'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_responsive_control(
            'view_buttons_padding',
            [
                'label'      => __( 'Buttons Padding', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'default'    => [
                    'top'    => 16,
                    'right'  => 16,
                    'bottom' => 16,
                    'left'   => 16,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-ss__overlay' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition'  => [
                    'view_buttons_enable' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section( 'overlay_buttons_section', [
            'label' => __( 'Overlay Buttons', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'      => 'overlay_buttons_typography',
            'selector'  => '{{WRAPPER}} .bw-slick-slider .bw-ss__btn',
            'condition' => [ 'view_buttons_enable' => 'yes' ],
        ] );

        $this->start_controls_tabs( 'overlay_buttons_color_tabs', [
            'condition' => [ 'view_buttons_enable' => 'yes' ],
        ] );

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
            'condition' => [ 'view_buttons_enable' => 'yes' ],
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
            'condition' => [ 'view_buttons_enable' => 'yes' ],
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
            'condition' => [ 'view_buttons_enable' => 'yes' ],
        ] );

        $this->end_controls_section();
    }

    protected function render() {
        $settings      = $this->get_settings_for_display();
        $view_buttons_enabled = isset( $settings['view_buttons_enable'] )
            ? ( 'yes' === $settings['view_buttons_enable'] )
            : ( isset( $settings['overlay_buttons_enable'] ) ? 'yes' === $settings['overlay_buttons_enable'] : true );
        $content_type  = isset( $settings['content_type'] ) && 'product' === $settings['content_type'] ? 'product' : 'post';
        $columns       = isset( $settings['columns'] ) ? max( 1, absint( $settings['columns'] ) ) : 3;
        $gap           = isset( $settings['gap']['size'] ) ? max( 0, absint( $settings['gap']['size'] ) ) : 24;
        $image_height  = isset( $settings['image_height'] ) ? max( 0, absint( $settings['image_height'] ) ) : 0;
        $image_crop    = isset( $settings['image_crop'] ) && 'yes' === $settings['image_crop'];
        $include_ids   = isset( $settings['include_ids'] ) ? $this->parse_ids( $settings['include_ids'] ) : [];
        $product_type  = isset( $settings['product_type'] ) ? sanitize_key( $settings['product_type'] ) : '';
        $product_cat_parent = isset( $settings['product_cat_parent'] ) ? absint( $settings['product_cat_parent'] ) : 0;
        $product_cat_child  = isset( $settings['product_cat_child'] ) ? array_filter( array_map( 'absint', (array) $settings['product_cat_child'] ) ) : [];
        $slides_scroll = isset( $settings['slides_to_scroll'] ) ? max( 1, absint( $settings['slides_to_scroll'] ) ) : 1;

        $query_args = [
            'post_type'      => 'product' === $content_type ? 'product' : 'post',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ];

        if ( ! empty( $include_ids ) ) {
            $query_args['post__in'] = $include_ids;
            $query_args['orderby']  = 'post__in';
        }

        if ( 'product' === $content_type ) {
            $tax_query    = [];

            if ( ! empty( $product_cat_child ) ) {
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

            if ( ! empty( $product_type ) ) {
                $tax_query[] = [
                    'taxonomy' => 'product_type',
                    'field'    => 'slug',
                    'terms'    => $product_type,
                ];
            }

            if ( ! empty( $tax_query ) ) {
                $query_args['tax_query'] = $tax_query;
            }
        } else {
            $category_ids = isset( $settings['post_categories'] ) ? array_filter( array_map( 'absint', (array) $settings['post_categories'] ) ) : [];
            if ( ! empty( $category_ids ) ) {
                $query_args['category__in'] = $category_ids;
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
            $wrapper_style .= '--bw-image-height:' . $image_height . 'px;';
        } else {
            $wrapper_style .= '--bw-image-height:auto;';
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

                    $thumbnail_html = '';
                    if ( has_post_thumbnail( $post_id ) ) {
                        $thumbnail_args = [ 'loading' => 'lazy' ];
                        $thumbnail_html = get_the_post_thumbnail( $post_id, 'large', $thumbnail_args );
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
                            <div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $media_classes ) ) ); ?>">
                                <?php if ( $thumbnail_html ) : ?>
                                    <a class="bw-slick-item__media-link bw-ss__media-link" href="<?php echo esc_url( $permalink ); ?>">
                                        <?php echo wp_kses_post( $thumbnail_html ); ?>
                                    </a>
                                <?php else : ?>
                                    <span class="bw-slick-item__image-placeholder" aria-hidden="true"></span>
                                <?php endif; ?>
                                <?php if ( $thumbnail_html && $view_buttons_enabled ) : ?>
                                    <div class="overlay-buttons bw-ss__overlay has-buttons">
                                        <div class="bw-ss__buttons<?php echo $has_add_to_cart ? ' bw-ss__buttons--double' : ''; ?>">
                                            <a class="overlay-button overlay-button--view bw-ss__btn bw-view-btn" href="<?php echo esc_url( $permalink ); ?>">
                                                <span class="overlay-button__label"><?php esc_html_e( 'View Product', 'bw-elementor-widgets' ); ?></span>
                                            </a>
                                            <?php if ( $has_add_to_cart && $add_to_cart_url ) : ?>
                                                <a class="overlay-button overlay-button--cart bw-ss__btn bw-btn-addtocart" href="<?php echo esc_url( $add_to_cart_url ); ?>">
                                                    <span class="overlay-button__label"><?php esc_html_e( 'Add to Cart', 'bw-elementor-widgets' ); ?></span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="bw-slick-item__content bw-ss__content">
                                <h3 class="bw-slick-item__title bw-slick-title">
                                    <a href="<?php echo esc_url( $permalink ); ?>">
                                        <?php echo esc_html( $title ); ?>
                                    </a>
                                </h3>

                                <?php if ( ! empty( $excerpt ) ) : ?>
                                    <div class="bw-slick-item__excerpt bw-slick-description"><?php echo wp_kses_post( $excerpt ); ?></div>
                                <?php endif; ?>

                                <?php if ( $price_html ) : ?>
                                    <div class="bw-slick-item__price price bw-slick-price"><?php echo wp_kses_post( $price_html ); ?></div>
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
