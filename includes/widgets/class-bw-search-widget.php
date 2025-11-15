<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Plugin;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Search_Widget extends Widget_Base {
    public function get_name() {
        return 'bw-search';
    }

    public function get_title() {
        return __( 'BW Search', 'bw' );
    }

    public function get_icon() {
        return 'eicon-search';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_style_depends() {
        if ( ! wp_style_is( 'bw-search-style', 'registered' ) && function_exists( 'bw_register_search_widget_assets' ) ) {
            bw_register_search_widget_assets();
        }

        return [ 'bw-search-style' ];
    }

    public function get_script_depends() {
        if ( ! wp_script_is( 'bw-search-script', 'registered' ) && function_exists( 'bw_register_search_widget_assets' ) ) {
            bw_register_search_widget_assets();
        }

        return [ 'bw-search-script' ];
    }

    protected function register_controls() {
        $this->register_content_controls();
        $this->register_style_controls();
    }

    private function register_content_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __( 'Settings', 'bw' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'button_label',
            [
                'label'       => __( 'Button Label', 'bw' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Search', 'bw' ),
                'placeholder' => __( 'Enter button label', 'bw' ),
                'label_block' => true,
            ]
        );

        $this->end_controls_section();
    }

    private function register_style_controls() {
        $this->start_controls_section(
            'section_button_style',
            [
                'label' => __( 'Button Style', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'button_typography',
                'selector' => '{{WRAPPER}} .bw-search-button__label',
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
                    '{{WRAPPER}} .bw-search-button'       => 'color: {{VALUE}};',
                    '{{WRAPPER}} .bw-search-button__label' => 'color: {{VALUE}};',
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
                    '{{WRAPPER}} .bw-search-button__label' => 'background-color: {{VALUE}};',
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
                    '{{WRAPPER}} .bw-search-button__label' => 'border-color: {{VALUE}};',
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
                    '{{WRAPPER}} .bw-search-button:hover, {{WRAPPER}} .bw-search-button:focus'        => 'color: {{VALUE}};',
                    '{{WRAPPER}} .bw-search-button:hover .bw-search-button__label, {{WRAPPER}} .bw-search-button:focus .bw-search-button__label' => 'color: {{VALUE}};',
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
                    '{{WRAPPER}} .bw-search-button:hover .bw-search-button__label, {{WRAPPER}} .bw-search-button:focus .bw-search-button__label' => 'background-color: {{VALUE}};',
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
                    '{{WRAPPER}} .bw-search-button:hover .bw-search-button__label, {{WRAPPER}} .bw-search-button:focus .bw-search-button__label' => 'border-color: {{VALUE}};',
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
                    '{{WRAPPER}} .bw-search-button__label' => 'border-width: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .bw-search-button__label' => 'border-radius: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .bw-search-button__label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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

    protected function render() {
        $settings = $this->get_settings_for_display();
        $label = isset( $settings['button_label'] ) && '' !== trim( $settings['button_label'] )
            ? $settings['button_label']
            : __( 'Search', 'bw' );

        $this->add_render_attribute( 'button', 'class', 'bw-search-button' );
        $this->add_render_attribute( 'button', 'type', 'button' );
        $this->add_render_attribute( 'button', 'aria-label', __( 'Open search', 'bw' ) );

        $label_markup = sprintf( '<span class="bw-search-button__label">%s</span>', esc_html( $label ) );

        echo sprintf(
            '<button %s>%s</button>',
            $this->get_render_attribute_string( 'button' ),
            $label_markup
        );

        // Render overlay
        $this->render_search_overlay();
    }

    private function render_search_overlay() {
        ?>
        <div class="bw-search-overlay" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Search', 'bw' ); ?>">
            <div class="bw-search-overlay__container">
                <button class="bw-search-overlay__close" type="button" aria-label="<?php esc_attr_e( 'Close search', 'bw' ); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>

                <div class="bw-search-overlay__content">
                    <h2 class="bw-search-overlay__title"><?php esc_html_e( "Type what you're looking for", 'bw' ); ?></h2>

                    <form class="bw-search-overlay__form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <div class="bw-search-overlay__input-wrapper">
                            <input
                                type="search"
                                name="s"
                                class="bw-search-overlay__input"
                                placeholder=""
                                aria-label="<?php esc_attr_e( 'Search', 'bw' ); ?>"
                                autocomplete="off"
                            />
                        </div>
                    </form>

                    <p class="bw-search-overlay__hint"><?php esc_html_e( 'Hit enter to search or ESC to close', 'bw' ); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
}
