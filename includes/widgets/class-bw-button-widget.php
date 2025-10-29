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
            'section_style',
            [
                'label' => __( 'Style', 'bw-elementor-widgets' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->start_controls_tabs( 'tabs_button_colors' );

        $this->start_controls_tab(
            'tab_button_normal',
            [
                'label' => __( 'Normal', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'button_background_color',
            [
                'label'     => __( 'Background Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label'     => __( 'Text Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-button, {{WRAPPER}} .bw-button .bw-button__label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_border_color',
            [
                'label'     => __( 'Border Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-button' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_icon_color',
            [
                'label'     => __( 'Icon Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-button__icon' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_button_hover',
            [
                'label' => __( 'Hover', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'button_background_color_hover',
            [
                'label'     => __( 'Background Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-button' => '--bw-button-hover-bg: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_text_color_hover',
            [
                'label'     => __( 'Text Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-button' => '--bw-button-hover-text: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_border_color_hover',
            [
                'label'     => __( 'Border Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-button' => '--bw-button-hover-border: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_icon_color_hover',
            [
                'label'     => __( 'Icon Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-button' => '--bw-button-hover-icon: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'button_border_width',
            [
                'label'   => __( 'Border Width', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::SLIDER,
                'default' => [
                    'size' => 1,
                    'unit' => 'px',
                ],
                'range'   => [
                    'px' => [
                        'min' => 0,
                        'max' => 10,
                        'step' => 1,
                    ],
                ],
                'size_units' => [ 'px' ],
                'selectors' => [
                    '{{WRAPPER}} .bw-button' => 'border-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'button_border',
                'selector' => '{{WRAPPER}} .bw-button',
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
                    '{{WRAPPER}} .bw-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'button_typography',
                'selector' => '{{WRAPPER}} .bw-button__label',
            ]
        );

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
                        'max' => 48,
                        'step' => 1,
                    ],
                ],
                'size_units' => [ 'em', 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-button__icon' => 'font-size: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .bw-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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

        if ( 'yes' === $settings['button_new_window'] ) {
            $this->add_render_attribute( 'button', 'target', '_blank' );
            $this->add_render_attribute( 'button', 'rel', 'noopener noreferrer' );
        }

        $label = ! empty( $settings['button_text'] ) ? $settings['button_text'] : __( 'The Workflow', 'bw-elementor-widgets' );
        ?>
        <a <?php echo $this->get_render_attribute_string( 'button' ); ?>>
            <span class="bw-button__icon">&#8250;</span>
            <span class="bw-button__label"><?php echo esc_html( $label ); ?></span>
        </a>
        <?php
    }

    protected function content_template() {
        ?>
        <#
        var link = settings.button_link.url ? elementor.helpers.sanitizeURL( settings.button_link.url ) : '#';
        var openInNewWindow = settings.button_new_window === 'yes';
        var label = settings.button_text ? settings.button_text : '<?php echo esc_js( __( 'The Workflow', 'bw-elementor-widgets' ) ); ?>';
        #>
        <a class="bw-button" href="{{ link }}" <# if ( openInNewWindow ) { #>target="_blank" rel="noopener noreferrer"<# } #>>
            <span class="bw-button__icon">&#8250;</span>
            <span class="bw-button__label">{{{ _.escape( label ) }}}</span>
        </a>
        <?php
    }
}
