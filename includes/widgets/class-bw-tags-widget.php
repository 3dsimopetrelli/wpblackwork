<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Tags_Widget extends Widget_Base {
    public function get_name() {
        return 'bw-tags';
    }

    public function get_title() {
        return __( 'BW Tags', 'bw' );
    }

    public function get_icon() {
        return 'eicon-tag';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_style_depends() {
        $handle      = 'bw-tags-style';
        $style_file  = BW_MEW_PATH . 'assets/css/bw-tags.css';
        $style_url   = BW_MEW_URL . 'assets/css/bw-tags.css';
        $version     = file_exists( $style_file ) ? filemtime( $style_file ) : false;

        if ( ! wp_style_is( $handle, 'registered' ) ) {
            wp_register_style( $handle, $style_url, [], $version );
        }

        return [ $handle ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_style_tags',
            [
                'label' => __( 'Tags', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'tags_typography',
                'selector' => '{{WRAPPER}} .bw-tags__link',
                'fields_options' => [
                    'typography' => [
                        'default' => 'yes',
                    ],
                    'font_size'  => [
                        'default' => [
                            'unit' => 'px',
                            'size' => 16,
                        ],
                    ],
                ],
            ]
        );

        $this->start_controls_tabs( 'tabs_tags_colors' );

        $this->start_controls_tab(
            'tab_tags_colors_normal',
            [
                'label' => __( 'Normal', 'bw' ),
            ]
        );

        $this->add_control(
            'tags_text_color',
            [
                'label'     => __( 'Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-tags__link' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tags_background_color',
            [
                'label'     => __( 'Background Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .bw-tags__link' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tags_border_toggle',
            [
                'label'        => __( 'Border', 'bw' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'On', 'bw' ),
                'label_off'    => __( 'Off', 'bw' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'selectors'    => [
                    '{{WRAPPER}} .bw-tags__link' => 'border-style: {{VALUE}};',
                ],
                'selectors_dictionary' => [
                    'yes' => 'solid',
                    ''    => 'none',
                ],
            ]
        );

        $this->add_control(
            'tags_border_width',
            [
                'label'      => __( 'Border Width', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 10,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 1,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-tags__link' => 'border-width: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'tags_border_toggle' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'tags_border_color',
            [
                'label'     => __( 'Border Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-tags__link' => 'border-color: {{VALUE}};',
                ],
                'condition' => [
                    'tags_border_toggle' => 'yes',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_tags_colors_hover',
            [
                'label' => __( 'Hover', 'bw' ),
            ]
        );

        $this->add_control(
            'tags_text_color_hover',
            [
                'label'     => __( 'Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-tags__link:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tags_background_color_hover',
            [
                'label'     => __( 'Background Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-tags__link:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tags_border_color_hover',
            [
                'label'     => __( 'Border Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-tags__link:hover' => 'border-color: {{VALUE}};',
                ],
                'condition' => [
                    'tags_border_toggle' => 'yes',
                ],
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_responsive_control(
            'tags_horizontal_gap',
            [
                'label'      => __( 'Horizontal Gap', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 8,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-tags__list' => 'column-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'tags_vertical_gap',
            [
                'label'      => __( 'Vertical Gap', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 8,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-tags__list' => 'row-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        if ( ! function_exists( 'wc_get_product' ) ) {
            return;
        }

        $product = wc_get_product();

        if ( ! $product ) {
            $product_id = get_queried_object_id();
            $product    = $product_id ? wc_get_product( $product_id ) : null;
        }

        if ( ! $product ) {
            return;
        }

        $tags = get_the_terms( $product->get_id(), 'product_tag' );

        if ( empty( $tags ) || is_wp_error( $tags ) ) {
            return;
        }

        echo '<div class="bw-tags"><ul class="bw-tags__list">';

        foreach ( $tags as $tag ) {
            $link = get_term_link( $tag );

            if ( is_wp_error( $link ) ) {
                continue;
            }

            printf(
                '<li class="bw-tags__item"><a class="bw-tags__link" href="%1$s">%2$s</a></li>',
                esc_url( $link ),
                esc_html( $tag->name )
            );
        }

        echo '</ul></div>';
    }
}
