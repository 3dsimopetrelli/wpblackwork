<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Plugin;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Button_Widget extends Widget_Base {
    public function get_name() {
        return 'bw-button';
    }

    public function get_title() {
        return __( 'BW Button', 'bw' );
    }

    public function get_icon() {
        return 'eicon-button';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_style_depends() {
        if ( ! wp_style_is( 'bw-button-style', 'registered' ) && function_exists( 'bw_register_button_widget_assets' ) ) {
            bw_register_button_widget_assets();
        }

        return [ 'bw-button-style' ];
    }

    public function get_script_depends() {
        return wp_script_is( 'bw-button-script', 'registered' ) ? [ 'bw-button-script' ] : [];
    }

    protected function register_controls() {
        $this->register_text_controls();
        $this->register_icon_controls();
    }

    private function register_text_controls() {
        $this->start_controls_section(
            'section_text_style',
            [
                'label' => __( 'Style Text Button', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label'       => __( 'Text', 'bw' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'The Workflow', 'bw' ),
                'placeholder' => __( 'Enter button text', 'bw' ),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'button_link',
            [
                'label'       => __( 'Link', 'bw' ),
                'type'        => Controls_Manager::URL,
                'placeholder' => __( 'https://your-link.com', 'bw' ),
                'dynamic'     => [ 'active' => true ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'button_typography',
                'selector' => '{{WRAPPER}} .bw-button__label',
            ]
        );

        $this->start_controls_tabs( 'tabs_button_colors' );

        $this->start_controls_tab(
            'tab_button_colors_normal',
            [
                'label' => __( 'Normal', 'bw' ),
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label'     => __( 'Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#080808',
                'selectors' => [
                    '{{WRAPPER}} .bw-button'       => 'color: {{VALUE}};',
                    '{{WRAPPER}} .bw-button__label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_background_color',
            [
                'label'     => __( 'Background Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#80FD03',
                'selectors' => [
                    '{{WRAPPER}} .bw-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_border_color',
            [
                'label'     => __( 'Border Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#080808',
                'selectors' => [
                    '{{WRAPPER}} .bw-button' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_button_colors_hover',
            [
                'label' => __( 'Hover', 'bw' ),
            ]
        );

        $this->add_control(
            'button_text_color_hover',
            [
                'label'     => __( 'Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#080808',
                'selectors' => [
                    '{{WRAPPER}} .bw-button:hover, {{WRAPPER}} .bw-button:focus'        => 'color: {{VALUE}};',
                    '{{WRAPPER}} .bw-button:hover .bw-button__label, {{WRAPPER}} .bw-button:focus .bw-button__label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_background_color_hover',
            [
                'label'     => __( 'Background Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#80FD03',
                'selectors' => [
                    '{{WRAPPER}} .bw-button:hover, {{WRAPPER}} .bw-button:focus' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_border_color_hover',
            [
                'label'     => __( 'Border Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#080808',
                'selectors' => [
                    '{{WRAPPER}} .bw-button:hover, {{WRAPPER}} .bw-button:focus' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_control(
            'button_border_width',
            [
                'label'      => __( 'Border Width', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 10, 'step' => 1 ],
                ],
                'default'    => [ 'size' => 1, 'unit' => 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-button' => 'border-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_border_radius',
            [
                'label'      => __( 'Border Radius', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 1000, 'step' => 1 ],
                ],
                'default'    => [ 'size' => 999, 'unit' => 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_padding',
            [
                'label'      => __( 'Padding', 'bw' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'default'    => [
                    'top'    => 12,
                    'right'  => 26,
                    'bottom' => 12,
                    'left'   => 26,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_alignment',
            [
                'label'        => __( 'Alignment', 'bw' ),
                'type'         => Controls_Manager::CHOOSE,
                'options'      => [
                    'left'   => [
                        'title' => __( 'Left', 'bw' ),
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __( 'Center', 'bw' ),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'right'  => [
                        'title' => __( 'Right', 'bw' ),
                        'icon'  => 'eicon-text-align-right',
                    ],
                    'justify' => [
                        'title' => __( 'Justify', 'bw' ),
                        'icon'  => 'eicon-text-align-justify',
                    ],
                ],
                'default'      => 'left',
                'selectors'    => [
                    '{{WRAPPER}}' => 'text-align: {{VALUE}};',
                ],
                'toggle'       => false,
            ]
        );

        $this->end_controls_section();
    }

    private function register_icon_controls() {
        $this->start_controls_section(
            'section_icon_style',
            [
                'label' => __( 'Style Icon Button', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'custom_icon',
            [
                'label'       => __( 'Custom SVG', 'bw' ),
                'type'        => Controls_Manager::MEDIA,
                'media_types' => [ 'svg' ],
                'description' => __( 'Upload a custom SVG to replace the default arrow.', 'bw' ),
            ]
        );

        $this->start_controls_tabs( 'tabs_icon_colors' );

        $this->start_controls_tab(
            'tab_icon_normal',
            [
                'label' => __( 'Normal', 'bw' ),
            ]
        );

        $this->add_control(
            'icon_color',
            [
                'label'     => __( 'Icon Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#080808',
                'selectors' => [
                    '{{WRAPPER}} .bw-button__icon' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'icon_background_color',
            [
                'label'     => __( 'Icon Background', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#80FD03',
                'selectors' => [
                    '{{WRAPPER}} .bw-button__icon' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'icon_border_color',
            [
                'label'     => __( 'Icon Border', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#080808',
                'selectors' => [
                    '{{WRAPPER}} .bw-button__icon' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_icon_hover',
            [
                'label' => __( 'Hover', 'bw' ),
            ]
        );

        $this->add_control(
            'icon_color_hover',
            [
                'label'     => __( 'Icon Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#080808',
                'selectors' => [
                    '{{WRAPPER}} .bw-button:hover .bw-button__icon, {{WRAPPER}} .bw-button:focus .bw-button__icon' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'icon_background_color_hover',
            [
                'label'     => __( 'Icon Background', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#80FD03',
                'selectors' => [
                    '{{WRAPPER}} .bw-button:hover .bw-button__icon, {{WRAPPER}} .bw-button:focus .bw-button__icon' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'icon_border_color_hover',
            [
                'label'     => __( 'Icon Border', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#080808',
                'selectors' => [
                    '{{WRAPPER}} .bw-button:hover .bw-button__icon, {{WRAPPER}} .bw-button:focus .bw-button__icon' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_control(
            'icon_size',
            [
                'label'      => __( 'Icon Size', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [ 'min' => 10, 'max' => 200 ],
                ],
                'default'    => [ 'size' => 30, 'unit' => 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-button__icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_padding',
            [
                'label'      => __( 'Icon Padding', 'bw' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'default'    => [
                    'top'    => 4,
                    'right'  => 4,
                    'bottom' => 4,
                    'left'   => 4,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-button__icon' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $text     = isset( $settings['button_text'] ) && '' !== trim( $settings['button_text'] )
            ? $settings['button_text']
            : __( 'The Workflow', 'bw' );

        $style_data = $this->prepare_button_style_data( $settings );
        $style_vars = isset( $style_data['inline'] ) ? $style_data['inline'] : '';

        $this->add_render_attribute( 'button', 'class', 'bw-button' );
        if ( ! empty( $style_vars ) ) {
            $this->add_render_attribute( 'button', 'style', $style_vars );
        }

        $tag = 'div';

        if ( ! empty( $settings['button_link']['url'] ) ) {
            $tag = 'a';
            $this->add_link_attributes( 'button', $settings['button_link'] );
        }

        $icon_markup = $this->get_icon_markup( $settings );

        $label_markup = sprintf( '<span class="bw-button__label">%s</span>', esc_html( $text ) );

        echo sprintf(
            '<%1$s %2$s>%3$s%4$s</%1$s>',
            esc_attr( $tag ),
            $this->get_render_attribute_string( 'button' ),
            $icon_markup,
            $label_markup
        );

        if ( ! empty( $style_data['responsive'] ) ) {
            $this->print_responsive_style_variables( $style_data['responsive'] );
        }
    }

    private function prepare_button_style_data( array $settings ) {
        $background_normal = isset( $settings['button_background_color'] ) && '' !== $settings['button_background_color']
            ? $settings['button_background_color']
            : '#80FD03';

        $background_hover = isset( $settings['button_background_color_hover'] ) && '' !== $settings['button_background_color_hover']
            ? $settings['button_background_color_hover']
            : $background_normal;

        $border_normal = isset( $settings['button_border_color'] ) && '' !== $settings['button_border_color']
            ? $settings['button_border_color']
            : '#080808';

        $border_hover = isset( $settings['button_border_color_hover'] ) && '' !== $settings['button_border_color_hover']
            ? $settings['button_border_color_hover']
            : $border_normal;

        $border_width = $this->format_slider_value( isset( $settings['button_border_width'] ) ? $settings['button_border_width'] : null, '1px' );
        $border_radius = $this->format_slider_value( isset( $settings['button_border_radius'] ) ? $settings['button_border_radius'] : null, '999px' );

        $padding        = isset( $settings['button_padding'] ) && is_array( $settings['button_padding'] ) ? $settings['button_padding'] : [];
        $padding_top    = $this->format_dimension_value( $padding, 'top', '12px' );
        $padding_right  = $this->format_dimension_value( $padding, 'right', '26px' );
        $padding_bottom = $this->format_dimension_value( $padding, 'bottom', '12px' );
        $padding_left   = $this->format_dimension_value( $padding, 'left', '26px' );

        $inline_variables = [
            '--bw-button-bg'             => $background_normal,
            '--bw-button-bg-hover'       => $background_hover,
            '--bw-button-border-color'   => $border_normal,
            '--bw-button-border-color-hover' => $border_hover,
            '--bw-button-border-width'   => $border_width,
            '--bw-button-border-radius'  => $border_radius,
            '--bw-button-padding-top'    => $padding_top,
            '--bw-button-padding-right'  => $padding_right,
            '--bw-button-padding-bottom' => $padding_bottom,
            '--bw-button-padding-left'   => $padding_left,
        ];

        $style_parts = [];

        foreach ( $inline_variables as $var => $value ) {
            if ( '' === $value ) {
                continue;
            }
            $style_parts[] = sprintf( '%s:%s', $var, esc_attr( $value ) );
        }

        $responsive_variables = [];

        foreach ( [ 'tablet', 'mobile' ] as $device ) {
            $device_variables = [];

            $border_radius_device = $this->format_slider_value(
                isset( $settings[ 'button_border_radius_' . $device ] ) ? $settings[ 'button_border_radius_' . $device ] : null,
                ''
            );

            if ( '' !== $border_radius_device ) {
                $device_variables['--bw-button-border-radius'] = $border_radius_device;
            }

            $padding_device = isset( $settings[ 'button_padding_' . $device ] ) && is_array( $settings[ 'button_padding_' . $device ] )
                ? $settings[ 'button_padding_' . $device ]
                : [];

            $padding_top_device    = $this->format_dimension_value( $padding_device, 'top', '' );
            $padding_right_device  = $this->format_dimension_value( $padding_device, 'right', '' );
            $padding_bottom_device = $this->format_dimension_value( $padding_device, 'bottom', '' );
            $padding_left_device   = $this->format_dimension_value( $padding_device, 'left', '' );

            if ( '' !== $padding_top_device ) {
                $device_variables['--bw-button-padding-top'] = $padding_top_device;
            }
            if ( '' !== $padding_right_device ) {
                $device_variables['--bw-button-padding-right'] = $padding_right_device;
            }
            if ( '' !== $padding_bottom_device ) {
                $device_variables['--bw-button-padding-bottom'] = $padding_bottom_device;
            }
            if ( '' !== $padding_left_device ) {
                $device_variables['--bw-button-padding-left'] = $padding_left_device;
            }

            if ( ! empty( $device_variables ) ) {
                $responsive_variables[ $device ] = $device_variables;
            }
        }

        $inline_style = implode( ';', $style_parts );

        if ( '' !== $inline_style ) {
            $inline_style .= ';';
        }

        return [
            'inline'      => $inline_style,
            'responsive'  => $responsive_variables,
        ];
    }

    private function format_slider_value( $value, $default ) {
        if ( empty( $value ) || ! is_array( $value ) ) {
            return $default;
        }

        $size = isset( $value['size'] ) ? $value['size'] : '';
        if ( '' === $size && 0 !== $size ) {
            return $default;
        }

        $unit = isset( $value['unit'] ) && '' !== $value['unit'] ? $value['unit'] : 'px';

        return $size . $unit;
    }

    private function format_dimension_value( array $dimensions, $side, $default ) {
        $unit = isset( $dimensions['unit'] ) && '' !== $dimensions['unit'] ? $dimensions['unit'] : 'px';

        if ( isset( $dimensions[ $side ] ) && '' !== $dimensions[ $side ] && null !== $dimensions[ $side ] ) {
            return $dimensions[ $side ] . $unit;
        }

        return $default;
    }

    private function print_responsive_style_variables( array $responsive_variables ) {
        if ( empty( $responsive_variables ) ) {
            return;
        }

        $breakpoints = $this->get_breakpoints_config();

        if ( empty( $breakpoints ) ) {
            return;
        }

        $css_rules = '';
        $element_id = $this->get_id();

        foreach ( $responsive_variables as $device => $variables ) {
            if ( empty( $variables ) || ! isset( $breakpoints[ $device ] ) ) {
                continue;
            }

            $media_query = $breakpoints[ $device ];

            if ( empty( $media_query ) ) {
                continue;
            }

            $declarations = [];

            foreach ( $variables as $var => $value ) {
                $declarations[] = sprintf( '%s:%s', $var, esc_attr( $value ) );
            }

            if ( empty( $declarations ) ) {
                continue;
            }

            $css_rules .= sprintf(
                '@media %1$s { .elementor-element-%2$s .bw-button{%3$s} }',
                esc_html( $media_query ),
                esc_attr( $element_id ),
                implode( ';', $declarations )
            );
        }

        if ( '' !== $css_rules ) {
            echo '<style>' . wp_strip_all_tags( $css_rules ) . '</style>';
        }
    }

    private function get_breakpoints_config() {
        $config = [];

        if ( class_exists( '\\Elementor\\Plugin' ) && isset( Plugin::$instance ) && isset( Plugin::$instance->breakpoints ) ) {
            $breakpoints = Plugin::$instance->breakpoints->get_breakpoints();

            if ( is_array( $breakpoints ) ) {
                foreach ( [ 'tablet', 'mobile' ] as $device ) {
                    if ( isset( $breakpoints[ $device ] ) ) {
                        $breakpoint = $breakpoints[ $device ];

                        if ( is_object( $breakpoint ) && method_exists( $breakpoint, 'get_value' ) && method_exists( $breakpoint, 'get_direction' ) ) {
                            $value     = $breakpoint->get_value();
                            $direction = $breakpoint->get_direction();

                            if ( $value ) {
                                $config[ $device ] = sprintf( '(%s-width: %dpx)', esc_attr( $direction ), (int) $value );
                            }
                        }
                    }
                }
            }
        }

        if ( empty( $config ) ) {
            $config = [
                'tablet' => '(max-width: 1024px)',
                'mobile' => '(max-width: 767px)',
            ];
        }

        return $config;
    }

    private function get_icon_markup( array $settings ) {
        $custom_icon = isset( $settings['custom_icon'] ) ? (array) $settings['custom_icon'] : [];
        $icon_html   = '';

        if ( ! empty( $custom_icon['id'] ) ) {
            $path = get_attached_file( $custom_icon['id'] );
            if ( $path && file_exists( $path ) ) {
                $icon_html = $this->sanitize_svg( file_get_contents( $path ) );
            }
        }

        if ( empty( $icon_html ) && ! empty( $custom_icon['url'] ) ) {
            $response = wp_safe_remote_get( $custom_icon['url'] );

            if ( ! is_wp_error( $response ) ) {
                $icon_html = $this->sanitize_svg( wp_remote_retrieve_body( $response ) );
            }
        }

        if ( empty( $icon_html ) ) {
            $icon_html = $this->get_default_arrow_svg();
        }

        return sprintf(
            '<span class="bw-button__icon-wrap"><span class="bw-button__icon">%s</span></span>',
            $icon_html
        );
    }

    private function get_default_arrow_svg() {
        return '<svg class="bw-button__icon-default" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    private function sanitize_svg( $svg_content ) {
        if ( empty( $svg_content ) || is_wp_error( $svg_content ) ) {
            return '';
        }

        $allowed_tags = [
            'svg'    => [
                'class'       => true,
                'xmlns'       => true,
                'width'       => true,
                'height'      => true,
                'viewbox'     => true,
                'aria-hidden' => true,
                'role'        => true,
                'focusable'   => true,
                'fill'        => true,
                'stroke'      => true,
                'stroke-width' => true,
                'stroke-linecap' => true,
                'stroke-linejoin' => true,
                'preserveaspectratio' => true,
            ],
            'g'      => [ 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'class' => true ],
            'path'   => [ 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'class' => true ],
            'circle' => [ 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'class' => true ],
            'ellipse'=> [ 'cx' => true, 'cy' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'class' => true ],
            'rect'   => [ 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'class' => true ],
            'polygon'=> [ 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'class' => true ],
            'polyline'=> [ 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'class' => true ],
            'line'   => [ 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true, 'class' => true ],
            'title'  => [],
            'desc'   => [],
        ];

        return wp_kses( $svg_content, $allowed_tags );
    }
}
