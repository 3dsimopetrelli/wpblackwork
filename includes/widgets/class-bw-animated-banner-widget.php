<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Animated_Banner_Widget extends Widget_Base {
    public function get_name() {
        return 'bw-animated-banner';
    }

    public function get_title() {
        return __( 'BW Animated Banner', 'bw' );
    }

    public function get_icon() {
        return 'eicon-animation-text';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_style_depends() {
        if ( ! wp_style_is( 'bw-animated-banner-style', 'registered' ) && function_exists( 'bw_register_animated_banner_widget_assets' ) ) {
            bw_register_animated_banner_widget_assets();
        }

        return [ 'bw-animated-banner-style' ];
    }

    public function get_script_depends() {
        if ( ! wp_script_is( 'bw-animated-banner-script', 'registered' ) && function_exists( 'bw_register_animated_banner_widget_assets' ) ) {
            bw_register_animated_banner_widget_assets();
        }

        return [ 'bw-animated-banner-script' ];
    }

    protected function register_controls() {
        $this->register_content_controls();
        $this->register_style_controls();
    }

    private function register_content_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __( 'Content', 'bw' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'banner_content',
            [
                'label'       => __( 'Content', 'bw' ),
                'type'        => Controls_Manager::TEXTAREA,
                'default'     => __( 'SUBSCRIBERS ONLY &nbsp;&nbsp;&nbsp;&nbsp; GET THE CODE &nbsp;&nbsp;&nbsp;&nbsp; 30% OFF EVERYTHING &nbsp;&nbsp;&nbsp;&nbsp; NOV 24-30', 'bw' ),
                'placeholder' => __( 'Enter banner text (HTML allowed: <strong>, <b>, <a>)', 'bw' ),
                'description' => __( 'You can use basic HTML tags: <strong>, <b>, <a>', 'bw' ),
                'label_block' => true,
                'dynamic'     => [ 'active' => true ],
            ]
        );

        $this->add_responsive_control(
            'items_spacing',
            [
                'label'      => __( 'Items Spacing', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 200, 'step' => 1 ],
                    'em' => [ 'min' => 0, 'max' => 10, 'step' => 0.1 ],
                ],
                'default'    => [ 'size' => 0, 'unit' => 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-animated-banner__item:not(:last-child)' => 'margin-right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'scroll_speed',
            [
                'label'       => __( 'Scroll Speed', 'bw' ),
                'type'        => Controls_Manager::SLIDER,
                'range'       => [
                    'px' => [ 'min' => 10, 'max' => 200, 'step' => 5 ],
                ],
                'default'     => [ 'size' => 50, 'unit' => 'px' ],
                'description' => __( 'Higher value = faster scroll', 'bw' ),
            ]
        );

        $this->end_controls_section();
    }

    private function register_style_controls() {
        $this->start_controls_section(
            'section_style',
            [
                'label' => __( 'Style', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'background_color',
            [
                'label'     => __( 'Background Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#B5FF2B',
                'selectors' => [
                    '{{WRAPPER}} .bw-animated-banner' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'text_typography',
                'selector' => '{{WRAPPER}} .bw-animated-banner__content',
                'fields_options' => [
                    'typography' => [
                        'default' => 'custom',
                    ],
                    'font_size' => [
                        'default' => [
                            'size' => 18,
                            'unit' => 'px',
                        ],
                    ],
                    'font_weight' => [
                        'default' => '700',
                    ],
                    'text_transform' => [
                        'default' => 'uppercase',
                    ],
                ],
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label'     => __( 'Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-animated-banner__content' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .bw-animated-banner__content a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'banner_height',
            [
                'label'      => __( 'Height', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'vh' ],
                'range'      => [
                    'px' => [ 'min' => 30, 'max' => 300, 'step' => 1 ],
                    'vh' => [ 'min' => 1, 'max' => 50, 'step' => 1 ],
                ],
                'default'    => [ 'size' => 60, 'unit' => 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-animated-banner' => 'min-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'banner_padding',
            [
                'label'      => __( 'Padding', 'bw' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'default'    => [
                    'top'    => 15,
                    'right'  => 0,
                    'bottom' => 15,
                    'left'   => 0,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-animated-banner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $content  = isset( $settings['banner_content'] ) ? $settings['banner_content'] : '';

        if ( empty( $content ) ) {
            return;
        }

        // Sanitize content allowing basic HTML
        $allowed_html = [
            'strong' => [],
            'b'      => [],
            'a'      => [
                'href'   => [],
                'target' => [],
                'rel'    => [],
                'title'  => [],
            ],
        ];
        $clean_content = wp_kses( $content, $allowed_html );

        // Get scroll speed
        $scroll_speed = isset( $settings['scroll_speed']['size'] ) ? absint( $settings['scroll_speed']['size'] ) : 50;

        // Calculate animation duration based on speed
        // Lower speed = longer duration, Higher speed = shorter duration
        $animation_duration = max( 10, 100 - $scroll_speed );

        $widget_id = $this->get_id();
        ?>
        <div class="bw-animated-banner" data-speed="<?php echo esc_attr( $scroll_speed ); ?>" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
            <div class="bw-animated-banner__track" style="animation-duration: <?php echo esc_attr( $animation_duration ); ?>s;">
                <div class="bw-animated-banner__content bw-animated-banner__item">
                    <?php echo $clean_content; ?>
                </div>
                <div class="bw-animated-banner__content bw-animated-banner__item" aria-hidden="true">
                    <?php echo $clean_content; ?>
                </div>
                <div class="bw-animated-banner__content bw-animated-banner__item" aria-hidden="true">
                    <?php echo $clean_content; ?>
                </div>
                <div class="bw-animated-banner__content bw-animated-banner__item" aria-hidden="true">
                    <?php echo $clean_content; ?>
                </div>
                <div class="bw-animated-banner__content bw-animated-banner__item" aria-hidden="true">
                    <?php echo $clean_content; ?>
                </div>
            </div>
        </div>
        <?php
    }

    protected function content_template() {
        ?>
        <#
        var content = settings.banner_content || '';
        var scrollSpeed = settings.scroll_speed.size || 50;
        var animationDuration = Math.max(10, 100 - scrollSpeed);

        if ( content ) {
        #>
        <div class="bw-animated-banner" data-speed="{{ scrollSpeed }}" data-widget-id="{{ view.model.id }}">
            <div class="bw-animated-banner__track" style="animation-duration: {{ animationDuration }}s;">
                <div class="bw-animated-banner__content bw-animated-banner__item">{{{ content }}}</div>
                <div class="bw-animated-banner__content bw-animated-banner__item" aria-hidden="true">{{{ content }}}</div>
                <div class="bw-animated-banner__content bw-animated-banner__item" aria-hidden="true">{{{ content }}}</div>
                <div class="bw-animated-banner__content bw-animated-banner__item" aria-hidden="true">{{{ content }}}</div>
                <div class="bw-animated-banner__content bw-animated-banner__item" aria-hidden="true">{{{ content }}}</div>
            </div>
        </div>
        <# } #>
        <?php
    }
}
