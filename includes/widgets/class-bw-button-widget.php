<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Widget_Bw_Button extends Widget_Base {

    public function get_name() {
        return 'bw-button';
    }

    public function get_title() {
        return __( 'BW Button', 'bw-elementor-widgets' );
    }

    public function get_icon() {
        return 'eicon-button';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_style_depends() {
        return [ 'bw-button-style' ];
    }

    protected function register_controls() {
        $this->register_content_controls();
        $this->register_style_controls();
    }

    private function register_content_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __( 'Content', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label'       => __( 'Text', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'The Workflow', 'bw-elementor-widgets' ),
                'placeholder' => __( 'Insert button text', 'bw-elementor-widgets' ),
                'label_block' => true,
                'dynamic'     => [ 'active' => true ],
                'render_type' => 'ui',
            ]
        );

        $this->add_control(
            'button_link',
            [
                'label'       => __( 'URL', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::URL,
                'placeholder' => __( 'https://your-link.com', 'bw-elementor-widgets' ),
                'default'     => [
                    'url' => '#',
                ],
                'show_external' => false,
                'dynamic'     => [ 'active' => true ],
                'render_type' => 'ui',
            ]
        );

        $this->add_control(
            'button_new_window',
            [
                'label'        => __( 'Apri in nuova finestra', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => '',
                'render_type'  => 'ui',
            ]
        );

        $this->end_controls_section();
    }

    private function register_style_controls() {
        $this->start_controls_section(
            'section_style_text',
            [
                'label' => __( 'Style Bottone Testo', 'bw-elementor-widgets' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->start_controls_tabs( 'tabs_button_text_colors' );

        $this->start_controls_tab(
            'tab_button_text_normal',
            [
                'label' => __( 'Normal', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'button_background_color',
            [
                'label'     => __( 'Background Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#80FD03',
                'selectors' => [
                    '{{WRAPPER}} .bw-button__label' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label'     => __( 'Text Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#080808',
                'selectors' => [
                    '{{WRAPPER}} .bw-button' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .bw-button__label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_button_text_hover',
            [
                'label' => __( 'Hover', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'button_background_color_hover',
            [
                'label'     => __( 'Background Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#80FD03',
                'selectors' => [
                    '{{WRAPPER}} .bw-button' => '--bw-button-label-hover-bg: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_text_color_hover',
            [
                'label'     => __( 'Text Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#080808',
                'selectors' => [
                    '{{WRAPPER}} .bw-button' => '--bw-button-label-hover-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_border_color_hover',
            [
                'label'     => __( 'Border Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#080808',
                'selectors' => [
                    '{{WRAPPER}} .bw-button' => '--bw-button-label-hover-border: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'button_border',
                'selector' => '{{WRAPPER}} .bw-button__label',
                'fields_options' => [
                    'border' => [
                        'default' => 'solid',
                    ],
                    'width'  => [
                        'default' => [
                            'top'    => 1,
                            'right'  => 1,
                            'bottom' => 1,
                            'left'   => 1,
                            'unit'   => 'px',
                        ],
                    ],
                    'color'  => [
                        'default' => '#080808',
                    ],
                ],
            ]
        );

        $this->add_responsive_control(
            'button_border_radius',
            [
                'label'      => __( 'Border Radius', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'default'    => [
                    'top'    => 999,
                    'right'  => 999,
                    'bottom' => 999,
                    'left'   => 999,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-button__label' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'button_typography',
                'selector' => '{{WRAPPER}} .bw-button__label',
                'fields_options' => [
                    'typography' => [
                        'default' => 'yes',
                    ],
                    'font_size'  => [
                        'default' => [
                            'unit' => 'px',
                            'size' => 24,
                        ],
                    ],
                ],
            ]
        );

        $this->add_responsive_control(
            'button_padding',
            [
                'label'      => __( 'Padding', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', 'rem' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-button__label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_icon',
            [
                'label' => __( 'Style Bottone Icona', 'bw-elementor-widgets' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'button_icon_type',
            [
                'label'   => __( 'Tipo Icona', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'default',
                'options' => [
                    'default' => __( 'Freccia predefinita', 'bw-elementor-widgets' ),
                    'custom'  => __( 'SVG personalizzato', 'bw-elementor-widgets' ),
                ],
            ]
        );

        $this->add_control(
            'button_custom_svg',
            [
                'label'       => __( 'SVG personalizzato', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::TEXTAREA,
                'placeholder' => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"></svg>',
                'description' => __( 'Incolla il markup SVG. Il colore verrà applicato automaticamente.', 'bw-elementor-widgets' ),
                'condition'   => [
                    'button_icon_type' => 'custom',
                ],
            ]
        );

        $this->start_controls_tabs( 'tabs_button_icon_colors' );

        $this->start_controls_tab(
            'tab_button_icon_normal',
            [
                'label' => __( 'Normal', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'button_icon_color',
            [
                'label'     => __( 'Icon Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#080808',
                'selectors' => [
                    '{{WRAPPER}} .bw-button__icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .bw-button__icon svg' => 'fill: {{VALUE}}; stroke: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_icon_background_color',
            [
                'label'     => __( 'Background Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#80FD03',
                'selectors' => [
                    '{{WRAPPER}} .bw-button__icon' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_icon_border_color',
            [
                'label'     => __( 'Border Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#080808',
                'selectors' => [
                    '{{WRAPPER}} .bw-button__icon' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_button_icon_hover',
            [
                'label' => __( 'Hover', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'button_icon_color_hover',
            [
                'label'     => __( 'Icon Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#080808',
                'selectors' => [
                    '{{WRAPPER}} .bw-button' => '--bw-button-icon-hover-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_icon_background_color_hover',
            [
                'label'     => __( 'Background Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#80FD03',
                'selectors' => [
                    '{{WRAPPER}} .bw-button' => '--bw-button-icon-hover-bg: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_icon_border_color_hover',
            [
                'label'     => __( 'Border Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#080808',
                'selectors' => [
                    '{{WRAPPER}} .bw-button' => '--bw-button-icon-hover-border: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'button_icon_size',
            [
                'label' => __( 'Icon Size', 'bw-elementor-widgets' ),
                'type'  => Controls_Manager::SLIDER,
                'range' => [
                    'em' => [
                        'min' => 0.5,
                        'max' => 3,
                        'step' => 0.1,
                    ],
                    'px' => [
                        'min' => 8,
                        'max' => 72,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 30,
                ],
                'size_units' => [ 'em', 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-button__icon' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_icon_padding',
            [
                'label'      => __( 'Icon Padding', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', 'rem' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-button__icon' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $this->add_render_attribute( 'button', 'class', 'bw-button' );

        $url = ! empty( $settings['button_link']['url'] ) ? $settings['button_link']['url'] : '#';
        $this->add_render_attribute( 'button', 'href', esc_url( $url ) );

        if ( ! empty( $settings['button_new_window'] ) && 'yes' === $settings['button_new_window'] ) {
            $this->add_render_attribute( 'button', 'target', '_blank' );
            $this->add_render_attribute( 'button', 'rel', 'noopener noreferrer' );
        }

        $label = ! empty( $settings['button_text'] ) ? $settings['button_text'] : __( 'The Workflow', 'bw-elementor-widgets' );
        ?>
        $icon_type    = ! empty( $settings['button_icon_type'] ) ? $settings['button_icon_type'] : 'default';
        $icon_content = '&#8250;';

        if ( 'custom' === $icon_type && ! empty( $settings['button_custom_svg'] ) ) {
            $sanitized_svg = $this->sanitize_custom_svg_markup( $settings['button_custom_svg'] );

            if ( $sanitized_svg ) {
                $icon_content = $sanitized_svg;
            }
        }
        ?>
        <a <?php echo $this->get_render_attribute_string( 'button' ); ?>>
            <span class="bw-button__icon"><?php echo $icon_content; ?></span>
            <span class="bw-button__label"><?php echo esc_html( $label ); ?></span>
        </a>
        <?php
    }

    protected function content_template() {
        ?>
        <#
        var link = '#';

        if ( settings.button_link && settings.button_link.url ) {
            link = settings.button_link.url;

            if (
                window.elementorCommon &&
                elementorCommon.helpers &&
                ( elementorCommon.helpers.sanitizeURL || elementorCommon.helpers.sanitizeUrl )
            ) {
                link = ( elementorCommon.helpers.sanitizeURL || elementorCommon.helpers.sanitizeUrl )( link );
            } else if (
                window.elementor &&
                elementor.helpers &&
                ( elementor.helpers.sanitizeURL || elementor.helpers.sanitizeUrl )
            ) {
                link = ( elementor.helpers.sanitizeURL || elementor.helpers.sanitizeUrl )( link );
            }
        }
        var openInNewWindow = settings.button_new_window && settings.button_new_window === 'yes';
        var label = settings.button_text ? settings.button_text : '<?php echo esc_js( __( 'The Workflow', 'bw-elementor-widgets' ) ); ?>';
        var iconType = settings.button_icon_type ? settings.button_icon_type : 'default';
        var iconMarkup = '&#8250;';

        if ( iconType === 'custom' && settings.button_custom_svg ) {
            iconMarkup = settings.button_custom_svg;
        }
        #>
        <a class="bw-button" href="{{ link }}" <# if ( openInNewWindow ) { #>target="_blank" rel="noopener noreferrer"<# } #>>
            <span class="bw-button__icon">{{{ iconMarkup }}}</span>
            <span class="bw-button__label">{{{ _.escape( label ) }}}</span>
        </a>
        <?php
    }

    private function sanitize_custom_svg_markup( $svg_markup ) {
        if ( empty( $svg_markup ) || ! is_string( $svg_markup ) ) {
            return '';
        }

        $allowed_tags = [
            'svg'            => [
                'class'               => true,
                'xmlns'               => true,
                'xmlns:xlink'         => true,
                'xlink:href'          => true,
                'version'             => true,
                'width'               => true,
                'height'              => true,
                'viewBox'             => true,
                'preserveAspectRatio' => true,
                'fill'                => true,
                'stroke'              => true,
                'stroke-width'        => true,
                'stroke-linecap'      => true,
                'stroke-linejoin'     => true,
                'stroke-miterlimit'   => true,
                'stroke-dasharray'    => true,
                'stroke-opacity'      => true,
                'fill-opacity'        => true,
                'aria-hidden'         => true,
                'focusable'           => true,
                'role'                => true,
                'style'               => true,
                'id'                  => true,
                'data-name'           => true,
                'data-id'             => true,
            ],
            'g'              => [
                'class'     => true,
                'fill'      => true,
                'stroke'    => true,
                'style'     => true,
                'id'        => true,
                'transform' => true,
                'data-name' => true,
                'data-id'   => true,
            ],
            'path'           => [
                'class'           => true,
                'fill'            => true,
                'stroke'          => true,
                'stroke-width'    => true,
                'stroke-linecap'  => true,
                'stroke-linejoin' => true,
                'stroke-miterlimit' => true,
                'stroke-dasharray'  => true,
                'stroke-opacity'    => true,
                'fill-opacity'      => true,
                'd'                => true,
                'style'            => true,
                'transform'        => true,
                'data-name'        => true,
                'data-id'          => true,
            ],
            'polygon'        => [
                'class'    => true,
                'fill'     => true,
                'stroke'   => true,
                'stroke-width' => true,
                'points'   => true,
                'style'    => true,
                'transform'=> true,
                'data-name'=> true,
                'data-id'  => true,
            ],
            'polyline'       => [
                'class'    => true,
                'fill'     => true,
                'stroke'   => true,
                'stroke-width' => true,
                'points'   => true,
                'style'    => true,
                'transform'=> true,
                'data-name'=> true,
                'data-id'  => true,
            ],
            'line'           => [
                'class'     => true,
                'x1'        => true,
                'y1'        => true,
                'x2'        => true,
                'y2'        => true,
                'stroke'    => true,
                'stroke-width' => true,
                'stroke-linecap' => true,
                'stroke-linejoin' => true,
                'style'     => true,
                'transform' => true,
                'data-name' => true,
                'data-id'   => true,
            ],
            'rect'           => [
                'class'  => true,
                'x'      => true,
                'y'      => true,
                'width'  => true,
                'height' => true,
                'rx'     => true,
                'ry'     => true,
                'fill'   => true,
                'stroke' => true,
                'stroke-width' => true,
                'style'  => true,
                'transform' => true,
                'data-name' => true,
                'data-id'   => true,
            ],
            'circle'         => [
                'class'  => true,
                'cx'     => true,
                'cy'     => true,
                'r'      => true,
                'fill'   => true,
                'stroke' => true,
                'stroke-width' => true,
                'style'  => true,
                'transform' => true,
                'data-name' => true,
                'data-id'   => true,
            ],
            'ellipse'        => [
                'class'  => true,
                'cx'     => true,
                'cy'     => true,
                'rx'     => true,
                'ry'     => true,
                'fill'   => true,
                'stroke' => true,
                'stroke-width' => true,
                'style'  => true,
                'transform' => true,
                'data-name' => true,
                'data-id'   => true,
            ],
            'title'          => [
                'class' => true,
            ],
            'desc'           => [
                'class' => true,
            ],
            'defs'           => [
                'class' => true,
            ],
            'linearGradient' => [
                'id'        => true,
                'x1'        => true,
                'y1'        => true,
                'x2'        => true,
                'y2'        => true,
                'gradientUnits' => true,
            ],
            'radialGradient' => [
                'id'            => true,
                'cx'            => true,
                'cy'            => true,
                'r'             => true,
                'fx'            => true,
                'fy'            => true,
                'gradientUnits' => true,
            ],
            'stop'           => [
                'offset'      => true,
                'stop-color'  => true,
                'stop-opacity'=> true,
                'style'       => true,
            ],
            'use'            => [
                'xlink:href' => true,
                'href'       => true,
                'x'          => true,
                'y'          => true,
                'width'      => true,
                'height'     => true,
                'transform'  => true,
                'style'      => true,
                'class'      => true,
            ],
            'mask'           => [
                'id'                => true,
                'maskUnits'         => true,
                'maskContentUnits'  => true,
                'x'                 => true,
                'y'                 => true,
                'width'             => true,
                'height'            => true,
            ],
            'clipPath'       => [
                'id'        => true,
                'clipPathUnits' => true,
                'transform' => true,
                'class'     => true,
            ],
            'symbol'         => [
                'id'        => true,
                'viewBox'   => true,
                'preserveAspectRatio' => true,
                'class'     => true,
            ],
            'pattern'        => [
                'id'            => true,
                'width'         => true,
                'height'        => true,
                'patternUnits'  => true,
                'patternContentUnits' => true,
                'patternTransform'    => true,
                'viewBox'       => true,
                'preserveAspectRatio' => true,
            ],
            'image'          => [
                'href'       => true,
                'xlink:href' => true,
                'x'          => true,
                'y'          => true,
                'width'      => true,
                'height'     => true,
                'preserveAspectRatio' => true,
                'class'      => true,
            ],
        ];

        $svg_markup = preg_replace( '/<\?(php|=).*\?>/i', '', $svg_markup );

        return trim( wp_kses( $svg_markup, $allowed_tags ) );
    }
}
