<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Filtered_Post_Wall_Widget extends Widget_Base {

    public function get_name() {
        return 'bw-filtered-post-wall';
    }

    public function get_title() {
        return esc_html__( 'BW Filtered Post Wall', 'bw-elementor-widgets' );
    }

    public function get_icon() {
        return 'eicon-filter';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_script_depends() {
        return [ 'imagesloaded', 'masonry', 'bw-filtered-post-wall-js' ];
    }

    public function get_style_depends() {
        return [ 'bw-filtered-post-wall-style' ];
    }

    protected function register_controls() {
        $this->register_filter_controls();
        $this->register_responsive_filter_controls();
        $this->register_query_controls();
        $this->register_layout_controls();
        $this->register_image_controls();
        $this->register_style_controls();
    }

    private function register_responsive_filter_controls() {
        $this->start_controls_section( 'responsive_filter_section', [
            'label' => __( 'Responsive Filter', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
            'condition' => [ 'show_filters' => 'yes' ],
        ] );

        $this->add_control( 'filter_responsive_breakpoint', [
            'label'       => __( 'Breakpoint (px)', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::NUMBER,
            'default'     => 900,
            'min'         => 320,
            'description' => __( 'Sotto questo breakpoint i filtri diventano responsive.', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'responsive_filter_heading', [
            'label'     => __( 'Filters Button (Style Filters & Show Results)', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        // Icon Controls
        $this->add_control( 'responsive_filter_button_show_icon', [
            'label'        => __( 'Show Icon', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
            'label_off'    => __( 'No', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'responsive_filter_button_custom_icon', [
            'label'       => __( 'Custom Icon (SVG)', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::MEDIA,
            'media_types' => [ 'image/svg+xml' ],
            'description' => __( 'Upload a custom SVG icon. Leave empty to use the default filter icon.', 'bw-elementor-widgets' ),
            'condition'   => [ 'responsive_filter_button_show_icon' => 'yes' ],
        ] );

        $this->add_responsive_control( 'responsive_filter_button_icon_size', [
            'label'      => __( 'Icon Size', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 10, 'max' => 50 ] ],
            'default'    => [ 'size' => 16, 'unit' => 'px' ],
            'selectors'  => [
                '{{WRAPPER}} .bw-fpw-mobile-filter-button-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ],
            'condition'  => [ 'responsive_filter_button_show_icon' => 'yes' ],
        ] );

        $this->add_responsive_control( 'responsive_filter_button_icon_spacing', [
            'label'      => __( 'Icon Spacing', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 0, 'max' => 30 ] ],
            'default'    => [ 'size' => 8, 'unit' => 'px' ],
            'selectors'  => [
                '{{WRAPPER}} .bw-fpw-mobile-filter-button-icon' => 'margin-right: {{SIZE}}{{UNIT}};',
            ],
            'condition'  => [ 'responsive_filter_button_show_icon' => 'yes' ],
        ] );

        // Typography
        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'responsive_filter_button_typography',
            'selector' => '{{WRAPPER}} .bw-fpw-mobile-filter-button, {{WRAPPER}}.bw-fpw-apply-style-to-show-results .bw-fpw-mobile-apply',
        ] );

        // Padding
        $this->add_responsive_control( 'responsive_filter_button_padding', [
            'label'      => __( 'Padding', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'em', '%' ],
            'default'    => [
                'top'      => 12,
                'right'    => 16,
                'bottom'   => 12,
                'left'     => 16,
                'unit'     => 'px',
                'isLinked' => false,
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-fpw-mobile-filter-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                '{{WRAPPER}}.bw-fpw-apply-style-to-show-results .bw-fpw-mobile-apply' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
            ],
        ] );

        // Background and Text Color Tabs
        $this->start_controls_tabs( 'responsive_filter_button_style_tabs' );

        // Normal Tab
        $this->start_controls_tab(
            'responsive_filter_button_normal_tab',
            [
                'label' => __( 'Normal', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control( 'responsive_filter_button_background', [
            'label'     => __( 'Background', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [
                '{{WRAPPER}} .bw-fpw-mobile-filter-button' => 'background-color: {{VALUE}};',
                '{{WRAPPER}}.bw-fpw-apply-style-to-show-results .bw-fpw-mobile-apply' => 'background-color: {{VALUE}} !important;',
            ],
        ] );

        $this->add_control( 'responsive_filter_button_text_color', [
            'label'     => __( 'Text Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#000000',
            'selectors' => [
                '{{WRAPPER}} .bw-fpw-mobile-filter-button' => 'color: {{VALUE}};',
                '{{WRAPPER}}.bw-fpw-apply-style-to-show-results .bw-fpw-mobile-apply' => 'color: {{VALUE}} !important;',
            ],
        ] );

        $this->add_control( 'responsive_filter_button_border_color', [
            'label'     => __( 'Border Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#000000',
            'selectors' => [
                '{{WRAPPER}} .bw-fpw-mobile-filter-button' => 'border-color: {{VALUE}};',
                '{{WRAPPER}}.bw-fpw-apply-style-to-show-results .bw-fpw-mobile-apply' => 'border-color: {{VALUE}} !important;',
            ],
            'condition' => [ 'responsive_filter_button_border' => 'yes' ],
        ] );

        $this->end_controls_tab();

        // Hover Tab
        $this->start_controls_tab(
            'responsive_filter_button_hover_tab',
            [
                'label' => __( 'Hover', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control( 'responsive_filter_button_background_hover', [
            'label'     => __( 'Background', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-fpw-mobile-filter-button:hover' => 'background-color: {{VALUE}};',
                '{{WRAPPER}}.bw-fpw-apply-style-to-show-results .bw-fpw-mobile-apply:hover' => 'background-color: {{VALUE}} !important;',
            ],
        ] );

        $this->add_control( 'responsive_filter_button_text_color_hover', [
            'label'     => __( 'Text Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-fpw-mobile-filter-button:hover' => 'color: {{VALUE}};',
                '{{WRAPPER}}.bw-fpw-apply-style-to-show-results .bw-fpw-mobile-apply:hover' => 'color: {{VALUE}} !important;',
            ],
        ] );

        $this->add_control( 'responsive_filter_button_border_color_hover', [
            'label'     => __( 'Border Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-fpw-mobile-filter-button:hover' => 'border-color: {{VALUE}};',
                '{{WRAPPER}}.bw-fpw-apply-style-to-show-results .bw-fpw-mobile-apply:hover' => 'border-color: {{VALUE}} !important;',
            ],
            'condition' => [ 'responsive_filter_button_border' => 'yes' ],
        ] );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        // Border Controls
        $this->add_control( 'responsive_filter_button_border', [
            'label'        => __( 'Border', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'On', 'bw-elementor-widgets' ),
            'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'yes',
            'separator'    => 'before',
            'selectors_dictionary' => [
                '' => 'border: none !important;',
            ],
            'selectors'    => [
                '{{WRAPPER}} .bw-fpw-mobile-filter-button' => '{{VALUE}}',
                '{{WRAPPER}}.bw-fpw-apply-style-to-show-results .bw-fpw-mobile-apply' => '{{VALUE}}',
            ],
        ] );

        $this->add_responsive_control( 'responsive_filter_button_border_width', [
            'label'      => __( 'Border Width', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 0, 'max' => 10 ] ],
            'default'    => [ 'size' => 1, 'unit' => 'px' ],
            'selectors'  => [
                '{{WRAPPER}} .bw-fpw-mobile-filter-button' => 'border-width: {{SIZE}}{{UNIT}}; border-style: solid;',
                '{{WRAPPER}}.bw-fpw-apply-style-to-show-results .bw-fpw-mobile-apply' => 'border-width: {{SIZE}}{{UNIT}} !important; border-style: solid !important;',
            ],
            'condition'  => [ 'responsive_filter_button_border' => 'yes' ],
        ] );

        $this->add_responsive_control( 'responsive_filter_button_radius', [
            'label'      => __( 'Border Radius', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 50 ],
                '%'  => [ 'min' => 0, 'max' => 100 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-fpw-mobile-filter-button' => 'border-radius: {{SIZE}}{{UNIT}} !important;',
                '{{WRAPPER}}.bw-fpw-apply-style-to-show-results .bw-fpw-mobile-apply' => 'border-radius: {{SIZE}}{{UNIT}} !important;',
            ],
            'condition'  => [ 'responsive_filter_button_border' => 'yes' ],
        ] );

        // Apply same style to Show Results button
        $this->add_control( 'apply_style_to_show_results', [
            'label'        => __( 'Apply same style to Show Results', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'ON', 'bw-elementor-widgets' ),
            'label_off'    => __( 'OFF', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'no',
            'separator'    => 'before',
            'description'  => __( 'When ON, the Show Results button will use the same typography, colors, background, border, and padding as the Filters Button.', 'bw-elementor-widgets' ),
        ] );

        $this->end_controls_section();

        // Show Results Button Dedicated Controls (when not using same style as Filters Button)
        $this->start_controls_section( 'show_results_button_style', [
            'label'     => __( 'Show Results Button Style', 'bw-elementor-widgets' ),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => [
                'show_filters'                 => 'yes',
                'apply_style_to_show_results!' => 'yes',
            ],
        ] );

        // Typography
        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'show_results_button_typography',
            'label'    => __( 'Typography', 'bw-elementor-widgets' ),
            'selector' => '{{WRAPPER}}:not(.bw-fpw-apply-style-to-show-results) .bw-fpw-mobile-apply',
        ] );

        // Padding
        $this->add_responsive_control( 'show_results_button_padding', [
            'label'      => __( 'Padding', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'em', '%' ],
            'default'    => [
                'top'      => 12,
                'right'    => 18,
                'bottom'   => 12,
                'left'     => 18,
                'unit'     => 'px',
                'isLinked' => false,
            ],
            'selectors'  => [
                '{{WRAPPER}}:not(.bw-fpw-apply-style-to-show-results) .bw-fpw-mobile-apply' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
            ],
        ] );

        // Background and Text Color Tabs
        $this->start_controls_tabs( 'show_results_button_style_tabs' );

        // Normal Tab
        $this->start_controls_tab(
            'show_results_button_normal_tab',
            [
                'label' => __( 'Normal', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control( 'show_results_button_background', [
            'label'     => __( 'Background', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#111111',
            'selectors' => [
                '{{WRAPPER}}:not(.bw-fpw-apply-style-to-show-results) .bw-fpw-mobile-apply' => 'background-color: {{VALUE}} !important;',
            ],
        ] );

        $this->add_control( 'show_results_button_text_color', [
            'label'     => __( 'Text Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [
                '{{WRAPPER}}:not(.bw-fpw-apply-style-to-show-results) .bw-fpw-mobile-apply' => 'color: {{VALUE}} !important;',
            ],
        ] );

        $this->add_control( 'show_results_button_border_color', [
            'label'     => __( 'Border Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#111111',
            'selectors' => [
                '{{WRAPPER}}:not(.bw-fpw-apply-style-to-show-results) .bw-fpw-mobile-apply' => 'border-color: {{VALUE}} !important;',
            ],
            'condition' => [ 'show_results_button_border' => 'yes' ],
        ] );

        $this->end_controls_tab();

        // Hover Tab
        $this->start_controls_tab(
            'show_results_button_hover_tab',
            [
                'label' => __( 'Hover', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control( 'show_results_button_background_hover', [
            'label'     => __( 'Background', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}}:not(.bw-fpw-apply-style-to-show-results) .bw-fpw-mobile-apply:hover' => 'background-color: {{VALUE}} !important;',
            ],
        ] );

        $this->add_control( 'show_results_button_text_color_hover', [
            'label'     => __( 'Text Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}}:not(.bw-fpw-apply-style-to-show-results) .bw-fpw-mobile-apply:hover' => 'color: {{VALUE}} !important;',
            ],
        ] );

        $this->add_control( 'show_results_button_border_color_hover', [
            'label'     => __( 'Border Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}}:not(.bw-fpw-apply-style-to-show-results) .bw-fpw-mobile-apply:hover' => 'border-color: {{VALUE}} !important;',
            ],
            'condition' => [ 'show_results_button_border' => 'yes' ],
        ] );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        // Border Controls
        $this->add_control( 'show_results_button_border', [
            'label'        => __( 'Border', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'On', 'bw-elementor-widgets' ),
            'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'no',
            'separator'    => 'before',
            'selectors_dictionary' => [
                '' => 'border: none !important;',
            ],
            'selectors'    => [
                '{{WRAPPER}}:not(.bw-fpw-apply-style-to-show-results) .bw-fpw-mobile-apply' => '{{VALUE}}',
            ],
        ] );

        $this->add_responsive_control( 'show_results_button_border_width', [
            'label'      => __( 'Border Width', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 0, 'max' => 10 ] ],
            'default'    => [ 'size' => 1, 'unit' => 'px' ],
            'selectors'  => [
                '{{WRAPPER}}:not(.bw-fpw-apply-style-to-show-results) .bw-fpw-mobile-apply' => 'border-width: {{SIZE}}{{UNIT}} !important; border-style: solid !important;',
            ],
            'condition'  => [ 'show_results_button_border' => 'yes' ],
        ] );

        $this->add_responsive_control( 'show_results_button_radius', [
            'label'      => __( 'Border Radius', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 50 ],
                '%'  => [ 'min' => 0, 'max' => 100 ],
            ],
            'default'    => [ 'size' => 4, 'unit' => 'px' ],
            'selectors'  => [
                '{{WRAPPER}}:not(.bw-fpw-apply-style-to-show-results) .bw-fpw-mobile-apply' => 'border-radius: {{SIZE}}{{UNIT}} !important;',
            ],
            'condition'  => [ 'show_results_button_border' => 'yes' ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'responsive_filter_panel_style', [
            'label'     => __( 'Responsive Filter Panel', 'bw-elementor-widgets' ),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => [ 'show_filters' => 'yes' ],
        ] );

        $this->add_control( 'responsive_filter_panel_header_settings_heading', [
            'label'     => __( 'Header Settings', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_control( 'responsive_filter_panel_header_heading', [
            'label'     => __( 'Header “Filter products”', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'responsive_filter_panel_header_typography',
            'selector' => '{{WRAPPER}} .bw-fpw-mobile-filter-panel__title',
        ] );

        $this->add_control( 'responsive_filter_panel_header_color', [
            'label'     => __( 'Title Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-header-title-color: {{VALUE}};',
            ],
        ] );

        $this->add_responsive_control( 'responsive_filter_panel_header_alignment', [
            'label'   => __( 'Text Align', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'left'   => [
                    'title' => __( 'Left', 'bw-elementor-widgets' ),
                    'icon'  => 'eicon-text-align-left',
                ],
                'center' => [
                    'title' => __( 'Center', 'bw-elementor-widgets' ),
                    'icon'  => 'eicon-text-align-center',
                ],
                'right'  => [
                    'title' => __( 'Right', 'bw-elementor-widgets' ),
                    'icon'  => 'eicon-text-align-right',
                ],
            ],
            'default'   => 'left',
            'selectors' => [
                '{{WRAPPER}} .bw-fpw-mobile-filter-panel__title' => 'text-align: {{VALUE}};',
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper'     => '--bw-fpw-mobile-header-align: {{VALUE}};',
            ],
        ] );

        $this->add_responsive_control( 'responsive_filter_panel_header_padding', [
            'label'      => __( 'Title Padding', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'em', 'rem' ],
            'selectors'  => [
                '{{WRAPPER}} .bw-fpw-mobile-filter-panel__title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-header-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'responsive_filter_panel_header_background', [
            'label'     => __( 'Header Background', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-header-bg: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'responsive_filter_panel_close_heading', [
            'label'     => __( 'Close Button (X)', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_control( 'responsive_filter_panel_close_color', [
            'label'     => __( 'Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#000000',
            'selectors' => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-close-color: {{VALUE}};',
                '{{WRAPPER}} .bw-fpw-mobile-filter-close'    => 'color: {{VALUE}} !important;',
            ],
        ] );

        $this->add_responsive_control( 'responsive_filter_panel_close_size', [
            'label'      => __( 'Size', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', 'em', 'rem' ],
            'range'      => [
                'px' => [ 'min' => 8, 'max' => 48 ],
                'em' => [ 'min' => 0.5, 'max' => 3 ],
                'rem' => [ 'min' => 0.5, 'max' => 3 ],
            ],
            'default'    => [
                'size' => 20,
                'unit' => 'px',
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-close-size: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'responsive_filter_panel_close_top', [
            'label'      => __( 'Top Position', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 100 ],
                '%'  => [ 'min' => 0, 'max' => 100 ],
            ],
            'default'    => [
                'size' => 50,
                'unit' => '%',
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-close-top: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'responsive_filter_panel_close_right', [
            'label'      => __( 'Right Position', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 100 ],
                '%'  => [ 'min' => 0, 'max' => 100 ],
            ],
            'default'    => [
                'size' => 16,
                'unit' => 'px',
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-close-right: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'responsive_filter_panel_close_left', [
            'label'      => __( 'Left Position', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 100 ],
                '%'  => [ 'min' => 0, 'max' => 100 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-close-left: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'responsive_filter_panel_close_bottom', [
            'label'      => __( 'Bottom Position', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 100 ],
                '%'  => [ 'min' => 0, 'max' => 100 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-close-bottom: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'responsive_filter_panel_dropdown_title_heading', [
            'label'     => __( 'Drop Down Title', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_responsive_control( 'responsive_dropdowns_padding_top', [
            'label'      => __( 'Padding Top (Dropdown Container)', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', 'em', 'rem' ],
            'range'      => [
                'px'  => [ 'min' => -100, 'max' => 200 ],
                'em'  => [ 'min' => -10, 'max' => 20 ],
                'rem' => [ 'min' => -10, 'max' => 20 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-dropdowns-padding-top: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'responsive_dropdown_title_typography',
            'selector' => '{{WRAPPER}} .bw-fpw-mobile-dropdown-toggle, {{WRAPPER}} .bw-fpw-mobile-dropdown-label',
        ] );

        $this->add_control( 'responsive_dropdown_title_color', [
            'label'     => __( 'Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-dropdown-title-color: {{VALUE}};',
                '{{WRAPPER}} .bw-fpw-mobile-dropdown-toggle, {{WRAPPER}} .bw-fpw-mobile-dropdown-label' => 'color: {{VALUE}} !important;',
                '{{WRAPPER}} .bw-fpw-mobile-dropdown-icon' => 'border-color: {{VALUE}} !important;',
            ],
        ] );

        $this->add_control( 'responsive_dropdown_title_hover_color', [
            'label'     => __( 'Hover Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-dropdown-title-hover-color: {{VALUE}};',
                '{{WRAPPER}} .bw-fpw-mobile-dropdown-toggle:hover, {{WRAPPER}} .bw-fpw-mobile-dropdown-toggle:focus, {{WRAPPER}} .bw-fpw-mobile-dropdown-toggle:hover .bw-fpw-mobile-dropdown-label, {{WRAPPER}} .bw-fpw-mobile-dropdown-toggle:focus .bw-fpw-mobile-dropdown-label' => 'color: {{VALUE}} !important;',
                '{{WRAPPER}} .bw-fpw-mobile-dropdown-toggle:hover .bw-fpw-mobile-dropdown-icon, {{WRAPPER}} .bw-fpw-mobile-dropdown-toggle:focus .bw-fpw-mobile-dropdown-icon' => 'border-color: {{VALUE}} !important;',
            ],
        ] );

        $this->add_responsive_control( 'responsive_dropdown_title_margin', [
            'label'      => __( 'Margin', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'em', 'rem' ],
            'selectors'  => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-dropdown-title-margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'responsive_dropdown_title_padding', [
            'label'      => __( 'Padding', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'em', 'rem' ],
            'selectors'  => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-dropdown-title-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'responsive_dropdown_toggle_background', [
            'label'     => __( 'Background Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-dropdown-toggle-bg: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'responsive_dropdown_toggle_border', [
            'label'        => __( 'Border', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'On', 'bw-elementor-widgets' ),
            'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'no',
            'selectors'    => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-dropdown-toggle-border-style: {{VALUE}};',
            ],
            'selectors_dictionary' => [
                'yes' => 'solid',
            ],
        ] );

        $this->add_control( 'responsive_dropdown_toggle_border_color', [
            'label'     => __( 'Border Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#000000',
            'selectors' => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-dropdown-toggle-border-color: {{VALUE}};',
            ],
            'condition' => [ 'responsive_dropdown_toggle_border' => 'yes' ],
        ] );

        $this->add_responsive_control( 'responsive_dropdown_toggle_border_width', [
            'label'      => __( 'Border Width', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 0, 'max' => 10 ] ],
            'default'    => [ 'size' => 1, 'unit' => 'px' ],
            'selectors'  => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-dropdown-toggle-border-width: {{SIZE}}{{UNIT}};',
            ],
            'condition'  => [ 'responsive_dropdown_toggle_border' => 'yes' ],
        ] );

        $this->add_responsive_control( 'responsive_dropdown_toggle_border_radius', [
            'label'      => __( 'Border Radius', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 50 ],
                '%'  => [ 'min' => 0, 'max' => 100 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-dropdown-toggle-border-radius: {{SIZE}}{{UNIT}};',
            ],
            'condition'  => [ 'responsive_dropdown_toggle_border' => 'yes' ],
        ] );

        $this->add_control( 'responsive_filter_panel_dropdown_results_heading', [
            'label'     => __( 'Drop Down Results', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'           => 'responsive_dropdown_button_typography',
            'selector'       => '{{WRAPPER}} .bw-fpw-mobile-dropdown-options .bw-fpw-filter-option, {{WRAPPER}} .bw-fpw-mobile-dropdown-options .bw-fpw-option-label, {{WRAPPER}} .bw-fpw-mobile-dropdown-options .bw-fpw-option-count',
            'fields_options' => [
                'font_size' => [
                    'default' => [
                        'size' => 18,
                        'unit' => 'px',
                    ],
                ],
            ],
        ] );

        $this->add_control( 'responsive_dropdown_button_color', [
            'label'     => __( 'Text Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-button-color: {{VALUE}};',
                '{{WRAPPER}} .bw-fpw-mobile-dropdown-options .bw-fpw-filter-option, {{WRAPPER}} .bw-fpw-mobile-dropdown-options .bw-fpw-option-label, {{WRAPPER}} .bw-fpw-mobile-dropdown-options .bw-fpw-option-count' => 'color: {{VALUE}} !important;',
            ],
        ] );

        $this->add_control( 'responsive_dropdown_button_color_hover', [
            'label'     => __( 'Text Color Hover', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-button-hover-color: {{VALUE}};',
                '{{WRAPPER}} .bw-fpw-mobile-dropdown-options .bw-fpw-filter-option:hover, {{WRAPPER}} .bw-fpw-mobile-dropdown-options .bw-fpw-filter-option:focus, {{WRAPPER}} .bw-fpw-mobile-dropdown-options .bw-fpw-filter-option:hover .bw-fpw-option-label, {{WRAPPER}} .bw-fpw-mobile-dropdown-options .bw-fpw-filter-option:focus .bw-fpw-option-label, {{WRAPPER}} .bw-fpw-mobile-dropdown-options .bw-fpw-filter-option:hover .bw-fpw-option-count, {{WRAPPER}} .bw-fpw-mobile-dropdown-options .bw-fpw-filter-option:focus .bw-fpw-option-count' => 'color: {{VALUE}} !important;',
            ],
        ] );

        $this->add_responsive_control( 'responsive_dropdown_option_padding', [
            'label'      => __( 'Option Padding', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'em', 'rem' ],
            'selectors'  => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-option-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .bw-fpw-mobile-dropdown-options .bw-fpw-filter-option' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
            ],
        ] );

        $this->add_control( 'responsive_dropdown_button_background', [
            'label'     => __( 'Background', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => '--bw-fpw-mobile-button-bg: {{VALUE}};',
            ],
        ] );
        $this->end_controls_section();
    }

    private function register_filter_controls() {
        $this->start_controls_section( 'filter_section', [
            'label' => __( 'Filter Settings', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'show_filters', [
            'label'        => __( 'Show Filters', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
            'label_off'    => __( 'No', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'yes',
            'description'  => __( 'Mostra/nascondi i filtri di categoria e tag', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'show_categories', [
            'label'        => __( 'Show Categories', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'On', 'bw-elementor-widgets' ),
            'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => [ 'show_filters' => 'yes' ],
        ] );

        $this->add_control( 'filter_categories_title', [
            'label'       => __( 'Categories Title', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => __( 'Categories', 'bw-elementor-widgets' ),
            'condition'   => [ 'show_filters' => 'yes' ],
        ] );

        $this->add_control( 'show_subcategories', [
            'label'        => __( 'Show Subcategories', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'On', 'bw-elementor-widgets' ),
            'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => [ 'show_filters' => 'yes' ],
        ] );

        $this->add_control( 'filter_subcategories_title', [
            'label'       => __( 'Subcategories Title', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => __( 'Subcategories', 'bw-elementor-widgets' ),
            'condition'   => [ 'show_filters' => 'yes', 'show_subcategories' => 'yes' ],
        ] );

        $this->add_control( 'show_tags', [
            'label'        => __( 'Show Tags', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'On', 'bw-elementor-widgets' ),
            'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => [ 'show_filters' => 'yes' ],
        ] );

        $this->add_control( 'filter_tags_title', [
            'label'       => __( 'Tags Title', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => __( 'Tags', 'bw-elementor-widgets' ),
            'condition'   => [ 'show_filters' => 'yes', 'show_tags' => 'yes' ],
        ] );

        $this->add_control( 'show_all_button', [
            'label'        => __( 'Show “All” Option', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'On', 'bw-elementor-widgets' ),
            'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => [ 'show_filters' => 'yes' ],
        ] );

        $this->add_control( 'filter_position', [
            'label'   => __( 'Filter Position', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'top'    => __( 'Top', 'bw-elementor-widgets' ),
                'left'   => __( 'Left Sidebar', 'bw-elementor-widgets' ),
                'right'  => __( 'Right Sidebar', 'bw-elementor-widgets' ),
            ],
            'default'   => 'top',
            'condition' => [ 'show_filters' => 'yes' ],
        ] );

        $this->end_controls_section();

        // Filter Style Section
        $this->start_controls_section( 'filter_style_section', [
            'label'     => __( 'Filter Style', 'bw-elementor-widgets' ),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => [ 'show_filters' => 'yes' ],
        ] );

        $this->add_control( 'filter_label_color', [
            'label'     => __( 'Title Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#000000',
            'selectors' => [
                '{{WRAPPER}} .bw-fpw-filter-label' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'           => 'filter_label_typography',
            'selector'       => '{{WRAPPER}} .bw-fpw-filter-label',
            'fields_options' => [
                'font_size' => [
                    'default' => [
                        'size' => 18,
                        'unit' => 'px',
                    ],
                ],
            ],
        ] );

        $this->start_controls_tabs( 'filter_option_color_tabs' );

        $this->start_controls_tab( 'filter_option_color_tab', [
            'label' => __( 'Default', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'filter_option_color', [
            'label'     => __( 'Options Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#000000',
            'selectors' => [
                '{{WRAPPER}} .bw-fpw-filter-option' => 'color: {{VALUE}};',
            ],
        ] );

        $this->end_controls_tab();

        $this->start_controls_tab( 'filter_option_color_hover_tab', [
            'label' => __( 'Hover', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'filter_option_hover_color', [
            'label'     => __( 'Hover Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#000000',
            'selectors' => [
                '{{WRAPPER}} .bw-fpw-filter-option:hover, {{WRAPPER}} .bw-fpw-filter-option.active' => 'color: {{VALUE}};',
            ],
        ] );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'           => 'filter_option_typography',
            'selector'       => '{{WRAPPER}} .bw-fpw-filter-option',
            'fields_options' => [
                'font_size' => [
                    'default' => [
                        'size' => 18,
                        'unit' => 'px',
                    ],
                ],
            ],
        ] );

        $this->add_control( 'filter_count_color', [
            'label'     => __( 'Count Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#9b9b9b',
            'selectors' => [
                '{{WRAPPER}} .bw-fpw-option-count' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'filter_spacing_heading', [
            'label'     => __( 'Spacing', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_responsive_control( 'categories_title_margin_bottom', [
            'label'      => __( 'Categories Title – Margin Bottom', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', 'em', 'rem' ],
            'range'      => [
                'px'  => [ 'min' => -50, 'max' => 200 ],
                'em'  => [ 'min' => -5, 'max' => 12 ],
                'rem' => [ 'min' => -5, 'max' => 12 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-fpw-filter-row--categories .bw-fpw-filter-label' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'categories_list_margin_bottom', [
            'label'      => __( 'Categories – Margin Bottom', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', 'em', 'rem' ],
            'range'      => [
                'px'  => [ 'min' => -50, 'max' => 200 ],
                'em'  => [ 'min' => -5, 'max' => 12 ],
                'rem' => [ 'min' => -5, 'max' => 12 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-fpw-filter-row--categories .bw-fpw-filter-options' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'subcategories_title_margin_bottom', [
            'label'      => __( 'Subcategories Title – Margin Bottom', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', 'em', 'rem' ],
            'range'      => [
                'px'  => [ 'min' => -50, 'max' => 200 ],
                'em'  => [ 'min' => -5, 'max' => 12 ],
                'rem' => [ 'min' => -5, 'max' => 12 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-fpw-filter-row--subcategories .bw-fpw-filter-label' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
            'condition'  => [ 'show_subcategories' => 'yes' ],
        ] );

        $this->add_responsive_control( 'subcategories_list_margin_bottom', [
            'label'      => __( 'Subcategories – Margin Bottom', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', 'em', 'rem' ],
            'range'      => [
                'px'  => [ 'min' => -50, 'max' => 200 ],
                'em'  => [ 'min' => -5, 'max' => 12 ],
                'rem' => [ 'min' => -5, 'max' => 12 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-fpw-filter-row--subcategories .bw-fpw-filter-options' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
            'condition'  => [ 'show_subcategories' => 'yes' ],
        ] );

        $this->add_responsive_control( 'tags_title_margin_bottom', [
            'label'      => __( 'Tags Title – Margin Bottom', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', 'em', 'rem' ],
            'range'      => [
                'px'  => [ 'min' => -50, 'max' => 200 ],
                'em'  => [ 'min' => -5, 'max' => 12 ],
                'rem' => [ 'min' => -5, 'max' => 12 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-fpw-filter-row--tags .bw-fpw-filter-label' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
            'condition'  => [ 'show_tags' => 'yes' ],
        ] );

        $this->add_responsive_control( 'tags_list_margin_bottom', [
            'label'      => __( 'Tags – Margin Bottom', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', 'em', 'rem' ],
            'range'      => [
                'px'  => [ 'min' => -50, 'max' => 200 ],
                'em'  => [ 'min' => -5, 'max' => 12 ],
                'rem' => [ 'min' => -5, 'max' => 12 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-fpw-filter-row--tags .bw-fpw-filter-options' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
            'condition'  => [ 'show_tags' => 'yes' ],
        ] );

        $this->add_control( 'filter_box_background', [
            'label'     => __( 'Background Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => 'transparent',
            'selectors' => [
                '{{WRAPPER}} .bw-fpw-filters' => 'background-color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'filter_box_border', [
            'label'        => __( 'Show Border', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'On', 'bw-elementor-widgets' ),
            'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'no',
        ] );

        $this->add_responsive_control( 'filter_box_radius', [
            'label'      => __( 'Border Radius', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 50 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-fpw-filters' => 'border-radius: {{SIZE}}{{UNIT}};',
            ],
            'condition'  => [ 'filter_box_border' => 'yes' ],
        ] );

        $this->add_responsive_control( 'filter_box_border_width', [
            'label'      => __( 'Border Width', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 10 ],
            ],
            'default'    => [
                'size' => 1,
                'unit' => 'px',
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-fpw-filters' => 'border-width: {{SIZE}}{{UNIT}};',
            ],
            'condition'  => [ 'filter_box_border' => 'yes' ],
        ] );

        $this->add_control( 'filter_box_border_color', [
            'label'     => __( 'Border Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#e0e0e0',
            'selectors' => [
                '{{WRAPPER}} .bw-fpw-filters' => 'border-color: {{VALUE}}; border-style: solid;',
            ],
            'condition' => [ 'filter_box_border' => 'yes' ],
        ] );

        // Filter Select - Active State Styling
        $this->add_control( 'filter_select_heading', [
            'label'     => __( 'Filter Select', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'description' => __( 'Stile per evidenziare i filtri attivi (Categoria, Subcategory, Tag selezionati)', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'filter_select_bold', [
            'label'        => __( 'Bold', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'On', 'bw-elementor-widgets' ),
            'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'no',
            'description'  => __( 'Applica grassetto ai filtri attivi', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'filter_select_underline', [
            'label'        => __( 'Underscore', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'On', 'bw-elementor-widgets' ),
            'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'no',
            'description'  => __( 'Sottolinea i filtri attivi', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'filter_select_custom_color', [
            'label'        => __( 'Custom Color', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'On', 'bw-elementor-widgets' ),
            'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'no',
            'description'  => __( 'Usa un colore personalizzato per i filtri attivi', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'filter_select_color', [
            'label'     => __( 'Active Filter Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#000000',
            'selectors' => [
                '{{WRAPPER}} .bw-fpw-filter-option.active .bw-fpw-option-label' => 'color: {{VALUE}};',
            ],
            'condition' => [ 'filter_select_custom_color' => 'yes' ],
        ] );

        $this->end_controls_section();
    }

    private function register_query_controls() {
        $this->start_controls_section( 'query_section', [
            'label' => __( 'Query', 'bw-elementor-widgets' ),
        ] );

        $post_type_options = $this->get_post_type_options();
        if ( empty( $post_type_options ) ) {
            $post_type_options = [ 'post' => __( 'Post', 'bw-elementor-widgets' ) ];
        }

        $post_type_keys    = array_keys( $post_type_options );
        $default_post_type = array_key_exists( 'product', $post_type_options ) ? 'product' : reset( $post_type_keys );

        $this->add_control( 'post_type', [
            'label'   => __( 'Post Type', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'options' => $post_type_options,
            'default' => $default_post_type,
        ] );

        $this->add_control( 'parent_category', [
            'label'       => __( 'Categoria padre', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::SELECT2,
            'label_block' => true,
            'multiple'    => false,
            'options'     => function_exists( 'bw_get_parent_product_categories' ) ? bw_get_parent_product_categories() : [],
            'condition'   => [ 'post_type' => 'product' ],
        ] );

        $this->add_control( 'subcategory', [
            'label'       => __( 'Sotto-categoria', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::SELECT2,
            'label_block' => true,
            'multiple'    => true,
            'options'     => [],
            'condition'   => [
                'post_type'        => 'product',
                'parent_category!' => '',
            ],
            'description' => __( 'Seleziona una o più sottocategorie della categoria padre scelta.', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'specific_ids', [
            'label'       => __( 'ID specifici', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::TEXT,
            'placeholder' => __( 'es. 12, 45, 78', 'bw-elementor-widgets' ),
            'description' => __( 'Inserisci gli ID separati da virgola.', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'order_by', [
            'label'   => __( 'Ordina per', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'default' => 'date',
            'options' => [
                'date'     => __( 'Data pubblicazione', 'bw-elementor-widgets' ),
                'modified' => __( 'Data modifica', 'bw-elementor-widgets' ),
                'title'    => __( 'Titolo', 'bw-elementor-widgets' ),
                'rand'     => __( 'Casuale', 'bw-elementor-widgets' ),
                'ID'       => __( 'ID', 'bw-elementor-widgets' ),
            ],
        ] );

        $this->add_control( 'order', [
            'label'     => __( 'Direzione ordinamento', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'DESC',
            'options'   => [
                'ASC'  => __( 'Crescente (A → Z, 1 → 9, vecchio → nuovo)', 'bw-elementor-widgets' ),
                'DESC' => __( 'Decrescente (Z → A, 9 → 1, nuovo → vecchio)', 'bw-elementor-widgets' ),
            ],
            'condition' => [
                'order_by!' => 'rand',
            ],
        ] );

        $this->add_control( 'open_cart_popup', [
            'label'        => __( 'Apri cart pop-up su Add to Cart', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'Sì', 'bw-elementor-widgets' ),
            'label_off'    => __( 'No', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => '',
            'separator'    => 'before',
            'description'  => __( 'Se attivo, il pulsante Add to Cart apre il cart pop-up dopo l\'aggiunta al carrello.', 'bw-elementor-widgets' ),
            'condition'    => [ 'post_type' => 'product' ],
        ] );

        $this->end_controls_section();
    }

    private function register_layout_controls() {
        $this->start_controls_section( 'layout_section', [
            'label' => __( 'Layout', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'posts_per_page', [
            'label'   => __( 'Numero di post', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::NUMBER,
            'min'     => -1,
            'max'     => 100,
            'step'    => 1,
            'default' => 12,
        ] );

        $this->add_responsive_control( 'margin_top', [
            'label'      => __( 'Margine superiore', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%' ],
            'range'      => [
                'px' => [ 'min' => -200, 'max' => 200, 'step' => 1 ],
                '%'  => [ 'min' => -50, 'max' => 50, 'step' => 1 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => 'margin-top: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'margin_bottom', [
            'label'      => __( 'Margine inferiore', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%' ],
            'range'      => [
                'px' => [ 'min' => -200, 'max' => 200, 'step' => 1 ],
                '%'  => [ 'min' => -50, 'max' => 50, 'step' => 1 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-filtered-post-wall-wrapper' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();

        // Layout Desktop
        $this->start_controls_section( 'section_layout_desktop', [
            'label' => __( 'Layout Desktop', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'columns_desktop', [
            'label'   => __( 'Numero Colonne Desktop', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'default' => '4',
            'options' => [
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
                '5' => '5',
                '6' => '6',
            ],
        ] );

        $this->add_control( 'gap_desktop', [
            'label'   => __( 'Gap Colonne Desktop', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SLIDER,
            'default' => [
                'size' => 15,
                'unit' => 'px',
            ],
            'range'   => [
                'px' => [
                    'min' => 0,
                    'max' => 100,
                ],
            ],
        ] );

        $this->add_control( 'image_height_desktop', [
            'label'   => __( 'Altezza Immagine Desktop', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SLIDER,
            'default' => [
                'size' => 625,
                'unit' => 'px',
            ],
            'range'   => [
                'px' => [
                    'min' => 100,
                    'max' => 1000,
                ],
            ],
        ] );

        $this->end_controls_section();

        // Responsive Settings
        $this->start_controls_section( 'section_responsive', [
            'label' => __( 'Responsive', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ] );

        // TABLET SETTINGS
        $this->add_control( 'heading_tablet', [
            'label'     => __( 'Impostazioni Tablet', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_control( 'breakpoint_tablet_min', [
            'label'       => __( 'Larghezza Minima Tablet (px)', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::NUMBER,
            'default'     => 768,
            'min'         => 600,
            'max'         => 1200,
            'description' => __( 'Dispositivi con larghezza >= a questo valore saranno considerati tablet', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'breakpoint_tablet_max', [
            'label'       => __( 'Larghezza Massima Tablet (px)', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::NUMBER,
            'default'     => 1024,
            'min'         => 768,
            'max'         => 1400,
            'description' => __( 'Dispositivi con larghezza <= a questo valore saranno considerati tablet', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'columns_tablet', [
            'label'   => __( 'Numero Colonne Tablet', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'default' => '2',
            'options' => [
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
            ],
        ] );

        $this->add_control( 'gap_tablet', [
            'label'   => __( 'Gap Colonne Tablet', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SLIDER,
            'default' => [
                'size' => 10,
                'unit' => 'px',
            ],
            'range'   => [
                'px' => [
                    'min' => 0,
                    'max' => 100,
                ],
            ],
        ] );

        $this->add_control( 'image_height_tablet', [
            'label'   => __( 'Altezza Immagine Tablet', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SLIDER,
            'default' => [
                'size' => 400,
                'unit' => 'px',
            ],
            'range'   => [
                'px' => [
                    'min' => 100,
                    'max' => 800,
                ],
            ],
        ] );

        // MOBILE SETTINGS
        $this->add_control( 'heading_mobile', [
            'label'     => __( 'Impostazioni Mobile', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_control( 'breakpoint_mobile_max', [
            'label'       => __( 'Larghezza Massima Mobile (px)', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::NUMBER,
            'default'     => 767,
            'min'         => 320,
            'max'         => 900,
            'description' => __( 'Dispositivi con larghezza <= a questo valore saranno considerati mobile', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'columns_mobile', [
            'label'   => __( 'Numero Colonne Mobile', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'default' => '1',
            'options' => [
                '1' => '1',
                '2' => '2',
            ],
        ] );

        $this->add_control( 'gap_mobile', [
            'label'   => __( 'Gap Colonne Mobile', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SLIDER,
            'default' => [
                'size' => 10,
                'unit' => 'px',
            ],
            'range'   => [
                'px' => [
                    'min' => 0,
                    'max' => 50,
                ],
            ],
        ] );

        $this->add_control( 'image_height_mobile', [
            'label'   => __( 'Altezza Immagine Mobile', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SLIDER,
            'default' => [
                'size' => 300,
                'unit' => 'px',
            ],
            'range'   => [
                'px' => [
                    'min' => 150,
                    'max' => 600,
                ],
            ],
        ] );

        $this->end_controls_section();
    }

    private function register_image_controls() {
        // Sezione: Image Settings
        $this->start_controls_section( 'image_section', [
            'label' => __( 'Image Settings', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'image_toggle', [
            'label'        => __( 'Show Featured Image', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
            'label_off'    => __( 'No', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'yes',
            'description'  => __( 'Mostra/nascondi l\'immagine in evidenza', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'image_size', [
            'label'   => __( 'Image Size', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'thumbnail'    => __( 'Thumbnail', 'bw-elementor-widgets' ),
                'medium'       => __( 'Medium', 'bw-elementor-widgets' ),
                'medium_large' => __( 'Medium Large', 'bw-elementor-widgets' ),
                'large'        => __( 'Large', 'bw-elementor-widgets' ),
                'full'         => __( 'Full', 'bw-elementor-widgets' ),
            ],
            'default'   => 'large',
            'condition' => [ 'image_toggle' => 'yes' ],
        ] );

        $this->add_responsive_control( 'image_border_radius', [
            'label'      => __( 'Image Border Radius', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em' ],
            'default'    => [
                'top'      => '8',
                'right'    => '8',
                'bottom'   => '8',
                'left'     => '8',
                'unit'     => 'px',
                'isLinked' => true,
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-fpw-media'   => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .bw-fpw-media img' => 'border-radius: inherit;',
                '{{WRAPPER}} .bw-fpw-overlay' => 'border-radius: inherit;',
                '{{WRAPPER}} .bw-fpw-image'   => 'border-radius: inherit;',
            ],
            'condition' => [ 'image_toggle' => 'yes' ],
        ] );

        $this->add_control( 'image_object_fit', [
            'label'   => __( 'Image Object Fit', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'cover'   => __( 'Cover', 'bw-elementor-widgets' ),
                'contain' => __( 'Contain', 'bw-elementor-widgets' ),
                'fill'    => __( 'Fill', 'bw-elementor-widgets' ),
                'none'    => __( 'None', 'bw-elementor-widgets' ),
            ],
            'default'   => 'cover',
            'selectors' => [
                '{{WRAPPER}} .bw-fpw-media img' => 'object-fit: {{VALUE}};',
            ],
            'condition' => [ 'image_toggle' => 'yes' ],
        ] );

        $this->add_control( 'image_background_color', [
            'label'       => __( 'Background Immagine', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::COLOR,
            'default'     => 'transparent',
            'description' => __( 'Colore di sfondo per immagini PNG con trasparenze', 'bw-elementor-widgets' ),
            'selectors'   => [
                '{{WRAPPER}} .bw-fpw-media' => 'background-color: {{VALUE}};',
                '{{WRAPPER}} .bw-fpw-image' => 'background-color: {{VALUE}};',
            ],
            'condition'   => [ 'image_toggle' => 'yes' ],
        ] );

        $this->end_controls_section();

        // Sezione: Hover Effect
        $this->start_controls_section( 'hover_section', [
            'label' => __( 'Hover Effect', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'hover_effect', [
            'label'        => __( 'Enable Hover Effect', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
            'label_off'    => __( 'No', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'yes',
            'description'  => __( 'Attiva effetto fade al passaggio del mouse', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'hover_opacity', [
            'label'   => __( 'Hover Opacity', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SLIDER,
            'range'   => [
                'px' => [ 'min' => 0, 'max' => 1, 'step' => 0.1 ],
            ],
            'default' => [
                'size' => 0.7,
            ],
            'selectors' => [
                '{{WRAPPER}} .bw-filtered-post-wall' => '--bw-fpw-hover-opacity: {{SIZE}};',
            ],
            'condition' => [ 'hover_effect' => 'yes' ],
        ] );

        $this->end_controls_section();
    }

    private function register_style_controls() {
        $this->start_controls_section( 'typography_section', [
            'label' => __( 'Typography', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'title_typography_heading', [
            'label' => __( 'Titolo', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::HEADING,
        ] );

        $this->add_control( 'title_color', [
            'label'     => __( 'Colore titolo', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-filtered-post-wall .bw-fpw-title' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'title_typography',
            'selector' => '{{WRAPPER}} .bw-filtered-post-wall .bw-fpw-title',
            'fields_options' => [
                'font_size' => [
                    'default' => [
                        'size' => 14,
                        'unit' => 'px',
                    ],
                ],
            ],
        ] );

        $this->add_responsive_control( 'title_margin_top', [
            'label'      => __( 'Titolo - margine superiore', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => -100, 'max' => 200, 'step' => 1 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-filtered-post-wall .bw-fpw-title' => 'margin-top: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'title_margin_bottom', [
            'label'      => __( 'Titolo - margine inferiore', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => -100, 'max' => 200, 'step' => 1 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-filtered-post-wall .bw-fpw-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'description_typography_heading', [
            'label'     => __( 'Descrizione', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_control( 'description_color', [
            'label'     => __( 'Colore descrizione', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-filtered-post-wall .bw-fpw-description' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'description_typography',
            'selector' => '{{WRAPPER}} .bw-filtered-post-wall .bw-fpw-description',
            'fields_options' => [
                'font_size' => [
                    'default' => [
                        'size' => 14,
                        'unit' => 'px',
                    ],
                ],
            ],
        ] );

        $this->add_responsive_control( 'description_margin_top', [
            'label'      => __( 'Descrizione - margine superiore', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => -100, 'max' => 200, 'step' => 1 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-filtered-post-wall .bw-fpw-description' => 'margin-top: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'description_margin_bottom', [
            'label'      => __( 'Descrizione - margine inferiore', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => -100, 'max' => 200, 'step' => 1 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-filtered-post-wall .bw-fpw-description' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'price_typography_heading', [
            'label'     => __( 'Prezzo', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_control( 'price_color', [
            'label'     => __( 'Colore prezzo', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-filtered-post-wall .bw-fpw-price' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'price_typography',
            'selector' => '{{WRAPPER}} .bw-filtered-post-wall .bw-fpw-price',
            'fields_options' => [
                'font_size' => [
                    'default' => [
                        'size' => 14,
                        'unit' => 'px',
                    ],
                ],
            ],
        ] );

        $this->add_responsive_control( 'price_margin_top', [
            'label'      => __( 'Prezzo - margine superiore', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => -100, 'max' => 200, 'step' => 1 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-filtered-post-wall .bw-fpw-price' => 'margin-top: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'price_margin_bottom', [
            'label'      => __( 'Prezzo - margine inferiore', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => -100, 'max' => 200, 'step' => 1 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-filtered-post-wall .bw-fpw-price' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'overlay_buttons_section', [
            'label' => __( 'Overlay Buttons', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'overlay_buttons_typography',
            'selector' => '{{WRAPPER}} .bw-filtered-post-wall .bw-fpw-overlay-button',
        ] );

        $this->start_controls_tabs( 'overlay_buttons_color_tabs' );

        $this->start_controls_tab( 'overlay_buttons_color_normal', [
            'label' => __( 'Normal', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'overlay_buttons_text_color', [
            'label'     => __( 'Colore testo', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-filtered-post-wall' => '--bw-fpw-overlay-buttons-color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'overlay_buttons_background_color', [
            'label'     => __( 'Colore sfondo', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#FFFFFF',
            'selectors' => [
                '{{WRAPPER}} .bw-filtered-post-wall' => '--bw-fpw-overlay-buttons-background: {{VALUE}};',
            ],
        ] );

        $this->end_controls_tab();

        $this->start_controls_tab( 'overlay_buttons_color_hover', [
            'label' => __( 'Hover', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'overlay_buttons_text_color_hover', [
            'label'     => __( 'Colore testo (hover)', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-filtered-post-wall' => '--bw-fpw-overlay-buttons-color-hover: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'overlay_buttons_background_color_hover', [
            'label'     => __( 'Colore sfondo (hover)', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#80FD03',
            'selectors' => [
                '{{WRAPPER}} .bw-filtered-post-wall' => '--bw-fpw-overlay-buttons-background-hover: {{VALUE}};',
            ],
        ] );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control( 'overlay_buttons_border_radius', [
            'label'      => __( 'Raggio bordi', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 200 ],
                '%'  => [ 'min' => 0, 'max' => 50 ],
            ],
            'default'    => [
                'size' => 8,
                'unit' => 'px',
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-filtered-post-wall' => '--bw-fpw-overlay-buttons-radius: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'overlay_buttons_padding', [
            'label'      => __( 'Padding pulsanti', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em', 'rem' ],
            'default'    => [
                'top'      => '13',
                'right'    => '10',
                'bottom'   => '13',
                'left'     => '10',
                'unit'     => 'px',
                'isLinked' => false,
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-filtered-post-wall' => '--bw-fpw-overlay-buttons-padding-top: {{TOP}}{{UNIT}}; --bw-fpw-overlay-buttons-padding-right: {{RIGHT}}{{UNIT}}; --bw-fpw-overlay-buttons-padding-bottom: {{BOTTOM}}{{UNIT}}; --bw-fpw-overlay-buttons-padding-left: {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $widget_id = $this->get_id();

        $show_filters = isset( $settings['show_filters'] ) && 'yes' === $settings['show_filters'];
        $filter_position = isset( $settings['filter_position'] ) ? $settings['filter_position'] : 'top';

        // Render filters area
        $this->render_wrapper_start( $settings );

        if ( $show_filters ) {
            $this->render_filters( $settings, $widget_id );
        }

        $this->render_posts( $settings, $widget_id );

        $this->render_wrapper_end( $settings );
    }

    private function render_wrapper_start( $settings ) {
        $filter_position = isset( $settings['filter_position'] ) ? $settings['filter_position'] : 'top';
        $wrapper_classes = [ 'bw-filtered-post-wall-wrapper', 'bw-fpw-layout-' . $filter_position ];

        // Add filter select style classes (multiple can be active)
        if ( isset( $settings['filter_select_bold'] ) && 'yes' === $settings['filter_select_bold'] ) {
            $wrapper_classes[] = 'bw-fpw-select-bold';
        }
        if ( isset( $settings['filter_select_underline'] ) && 'yes' === $settings['filter_select_underline'] ) {
            $wrapper_classes[] = 'bw-fpw-select-underline';
        }
        if ( isset( $settings['filter_select_custom_color'] ) && 'yes' === $settings['filter_select_custom_color'] ) {
            $wrapper_classes[] = 'bw-fpw-select-custom-color';
        }

        // Add class when Show Results button should use same style as Filters button
        if ( isset( $settings['apply_style_to_show_results'] ) && 'yes' === $settings['apply_style_to_show_results'] ) {
            $wrapper_classes[] = 'bw-fpw-apply-style-to-show-results';
        }

        $responsive_breakpoint = isset( $settings['filter_responsive_breakpoint'] ) ? absint( $settings['filter_responsive_breakpoint'] ) : 900;

        echo '<div class="' . esc_attr( implode( ' ', $wrapper_classes ) ) . '" data-filter-breakpoint="' . esc_attr( $responsive_breakpoint ) . '">';
    }

    private function render_wrapper_end( $settings ) {
        echo '</div>';
    }

    private function render_filters( $settings, $widget_id ) {
        $post_type = isset( $settings['post_type'] ) ? sanitize_key( $settings['post_type'] ) : 'product';
        $categories_title      = isset( $settings['filter_categories_title'] ) ? $settings['filter_categories_title'] : __( 'Categories', 'bw-elementor-widgets' );
        $subcategories_title   = isset( $settings['filter_subcategories_title'] ) ? $settings['filter_subcategories_title'] : __( 'Subcategories', 'bw-elementor-widgets' );
        $tags_title            = isset( $settings['filter_tags_title'] ) ? $settings['filter_tags_title'] : __( 'Tags', 'bw-elementor-widgets' );
        $show_categories       = isset( $settings['show_categories'] ) ? 'yes' === $settings['show_categories'] : true;
        $show_subcategories    = isset( $settings['show_subcategories'] ) ? 'yes' === $settings['show_subcategories'] : true;
        $show_tags             = isset( $settings['show_tags'] ) ? 'yes' === $settings['show_tags'] : true;
        $show_all_button       = isset( $settings['show_all_button'] ) ? 'yes' === $settings['show_all_button'] : true;

        $taxonomy     = 'product' === $post_type ? 'product_cat' : 'category';
        $parent_terms = $show_categories
            ? get_terms(
                [
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => true,
                    'parent'     => 0,
                ]
            )
            : [];

        $tags = $show_tags ? $this->get_related_tags( $post_type, 'all', [] ) : [];
        $initial_subcategories = $show_subcategories ? $this->get_subcategories_data( $post_type, 'all' ) : [];

        $mobile_panel_title   = __( 'Filter products', 'bw-elementor-widgets' );
        $mobile_filters_title = __( 'Filters', 'bw-elementor-widgets' );
        $mobile_show_results  = __( 'Show results', 'bw-elementor-widgets' );
        $mobile_button_border = isset( $settings['responsive_filter_button_border'] ) ? 'yes' === $settings['responsive_filter_button_border'] : true;
        $mobile_button_classes = [ 'bw-fpw-mobile-filter-button' ];

        if ( ! $mobile_button_border ) {
            $mobile_button_classes[] = 'bw-fpw-mobile-filter-button--borderless';
        }

        // Icon logic
        $show_icon = isset( $settings['responsive_filter_button_show_icon'] ) && 'yes' === $settings['responsive_filter_button_show_icon'];
        $icon_html = '';

        if ( $show_icon ) {
            // Check for custom SVG
            if ( ! empty( $settings['responsive_filter_button_custom_icon']['url'] ) ) {
                $icon_html = '<img src="' . esc_url( $settings['responsive_filter_button_custom_icon']['url'] ) . '" class="bw-fpw-mobile-filter-button-icon" alt="" />';
            } else {
                // Default filter icon (SVG inline)
                $icon_html = '<svg class="bw-fpw-mobile-filter-button-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 7H21M6 12H18M9 17H15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
            }
        }
        ?>

        <div class="bw-fpw-mobile-filter" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
            <button class="<?php echo esc_attr( implode( ' ', $mobile_button_classes ) ); ?>" type="button">
                <?php
                if ( $show_icon ) {
                    echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }
                echo esc_html( $mobile_filters_title );
                ?>
            </button>

            <div class="bw-fpw-mobile-filter-panel" aria-hidden="true">
                <div class="bw-fpw-mobile-filter-panel__header">
                    <span class="bw-fpw-mobile-filter-panel__title"><?php echo esc_html( $mobile_panel_title ); ?></span>
                    <button class="bw-fpw-mobile-filter-close" type="button" aria-label="<?php esc_attr_e( 'Close filters', 'bw-elementor-widgets' ); ?>">&times;</button>
                </div>

                <div class="bw-fpw-mobile-filter-panel__body">
                    <?php if ( $show_categories ) : ?>
                        <div class="bw-fpw-mobile-filter-group bw-fpw-mobile-filter-group--categories" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                            <button class="bw-fpw-mobile-dropdown-toggle" type="button">
                                <span class="bw-fpw-mobile-dropdown-label"><?php echo esc_html( $categories_title ); ?></span>
                                <span class="bw-fpw-mobile-dropdown-icon"></span>
                            </button>
                            <div class="bw-fpw-mobile-dropdown-panel" aria-hidden="true">
                                <div class="bw-fpw-mobile-dropdown-options bw-fpw-filter-options bw-fpw-filter-options--categories" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                                    <?php if ( $show_all_button ) : ?>
                                        <button class="bw-fpw-filter-option bw-fpw-cat-button active" data-category="all">
                                            <span class="bw-fpw-option-label"><?php echo esc_html( __( 'All', 'bw-elementor-widgets' ) ); ?></span> <span class="bw-fpw-option-count">(<?php echo esc_html( $this->get_total_post_count( $post_type ) ); ?>)</span>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ( ! empty( $parent_terms ) && ! is_wp_error( $parent_terms ) ) : ?>
                                        <?php
                                        $has_active_category = $show_all_button;
                                        foreach ( $parent_terms as $category ) :
                                            $is_active = ! $has_active_category;
                                            if ( $is_active ) {
                                                $has_active_category = true;
                                            }
                                            ?>
                                            <button class="bw-fpw-filter-option bw-fpw-cat-button<?php echo $is_active ? ' active' : ''; ?>" data-category="<?php echo esc_attr( $category->term_id ); ?>">
                                                <span class="bw-fpw-option-label"><?php echo esc_html( $category->name ); ?></span> <span class="bw-fpw-option-count">(<?php echo esc_html( $category->count ); ?>)</span>
                                            </button>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( $show_subcategories ) : ?>
                        <div class="bw-fpw-mobile-filter-group bw-fpw-mobile-filter-group--subcategories" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                            <button class="bw-fpw-mobile-dropdown-toggle" type="button">
                                <span class="bw-fpw-mobile-dropdown-label"><?php echo esc_html( $subcategories_title ); ?></span>
                                <span class="bw-fpw-mobile-dropdown-icon"></span>
                            </button>
                            <div class="bw-fpw-mobile-dropdown-panel" aria-hidden="true">
                                <div class="bw-fpw-mobile-dropdown-options bw-fpw-filter-options bw-fpw-subcategories-container" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                                    <?php foreach ( $initial_subcategories as $subcategory ) : ?>
                                        <button class="bw-fpw-filter-option bw-fpw-subcat-button" data-subcategory="<?php echo esc_attr( $subcategory['term_id'] ); ?>">
                                            <span class="bw-fpw-option-label"><?php echo esc_html( $subcategory['name'] ); ?></span> <span class="bw-fpw-option-count">(<?php echo esc_html( $subcategory['count'] ); ?>)</span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( $show_tags && ! empty( $tags ) ) : ?>
                        <div class="bw-fpw-mobile-filter-group bw-fpw-mobile-filter-group--tags" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                            <button class="bw-fpw-mobile-dropdown-toggle" type="button">
                                <span class="bw-fpw-mobile-dropdown-label"><?php echo esc_html( $tags_title ); ?></span>
                                <span class="bw-fpw-mobile-dropdown-icon"></span>
                            </button>
                            <div class="bw-fpw-mobile-dropdown-panel" aria-hidden="true">
                                <div class="bw-fpw-mobile-dropdown-options bw-fpw-filter-options bw-fpw-tag-options" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                                    <?php foreach ( $tags as $tag ) : ?>
                                        <button class="bw-fpw-filter-option bw-fpw-tag-button" data-tag="<?php echo esc_attr( $tag['term_id'] ); ?>">
                                            <span class="bw-fpw-option-label"><?php echo esc_html( $tag['name'] ); ?></span> <span class="bw-fpw-option-count">(<?php echo esc_html( $tag['count'] ); ?>)</span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="bw-fpw-mobile-filter-panel__footer">
                    <button class="bw-fpw-mobile-apply" type="button"><?php echo esc_html( $mobile_show_results ); ?></button>
                </div>
            </div>
        </div>

        <div class="bw-fpw-filters" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
            <div class="bw-fpw-filter-rows">
                <?php if ( $show_categories ) : ?>
                    <div class="bw-fpw-filter-row bw-fpw-filter-row--categories" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                        <h3 class="bw-fpw-filter-label"><?php echo esc_html( $categories_title ); ?></h3>
                        <div class="bw-fpw-filter-options bw-fpw-filter-options--categories" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                            <?php if ( $show_all_button ) : ?>
                                <button class="bw-fpw-filter-option bw-fpw-cat-button active" data-category="all">
                                    <span class="bw-fpw-option-label"><?php echo esc_html( __( 'All', 'bw-elementor-widgets' ) ); ?></span> <span class="bw-fpw-option-count">(<?php echo esc_html( $this->get_total_post_count( $post_type ) ); ?>)</span>
                                </button>
                            <?php endif; ?>
                            <?php if ( ! empty( $parent_terms ) && ! is_wp_error( $parent_terms ) ) : ?>
                                <?php
                                $has_active_category = $show_all_button;
                                foreach ( $parent_terms as $category ) :
                                    $is_active = ! $has_active_category;
                                    if ( $is_active ) {
                                        $has_active_category = true;
                                    }
                                    ?>
                                    <button class="bw-fpw-filter-option bw-fpw-cat-button<?php echo $is_active ? ' active' : ''; ?>" data-category="<?php echo esc_attr( $category->term_id ); ?>">
                                        <span class="bw-fpw-option-label"><?php echo esc_html( $category->name ); ?></span> <span class="bw-fpw-option-count">(<?php echo esc_html( $category->count ); ?>)</span>
                                    </button>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ( $show_subcategories ) : ?>
                    <div class="bw-fpw-filter-row bw-fpw-filter-row--subcategories bw-fpw-filter-subcategories" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                        <h3 class="bw-fpw-filter-label"><?php echo esc_html( $subcategories_title ); ?></h3>
                        <div class="bw-fpw-filter-options bw-fpw-subcategories-container" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                            <?php foreach ( $initial_subcategories as $subcategory ) : ?>
                                <button class="bw-fpw-filter-option bw-fpw-subcat-button" data-subcategory="<?php echo esc_attr( $subcategory['term_id'] ); ?>">
                                    <span class="bw-fpw-option-label"><?php echo esc_html( $subcategory['name'] ); ?></span> <span class="bw-fpw-option-count">(<?php echo esc_html( $subcategory['count'] ); ?>)</span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ( $show_tags && ! empty( $tags ) ) : ?>
                    <div class="bw-fpw-filter-row bw-fpw-filter-row--tags bw-fpw-filter-tags" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                        <h3 class="bw-fpw-filter-label"><?php echo esc_html( $tags_title ); ?></h3>
                        <div class="bw-fpw-filter-options bw-fpw-tag-options" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                            <?php foreach ( $tags as $tag ) : ?>
                                <button class="bw-fpw-filter-option bw-fpw-tag-button" data-tag="<?php echo esc_attr( $tag['term_id'] ); ?>">
                                    <span class="bw-fpw-option-label"><?php echo esc_html( $tag['name'] ); ?></span> <span class="bw-fpw-option-count">(<?php echo esc_html( $tag['count'] ); ?>)</span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function render_posts( $settings, $widget_id ) {
        $post_type         = isset( $settings['post_type'] ) ? sanitize_key( $settings['post_type'] ) : 'post';
        $available_post_types = $this->get_post_type_options();

        if ( empty( $available_post_types ) ) {
            $available_post_types = [ 'post' => __( 'Post', 'bw-elementor-widgets' ) ];
        }

        if ( ! array_key_exists( $post_type, $available_post_types ) ) {
            $post_type_keys = array_keys( $available_post_types );
            $post_type      = array_key_exists( 'post', $available_post_types ) ? 'post' : reset( $post_type_keys );
        }

        $posts_per_page = isset( $settings['posts_per_page'] ) ? (int) $settings['posts_per_page'] : 12;
        if ( 0 === $posts_per_page ) {
            $posts_per_page = -1;
        }

        // Get layout values
        $columns_desktop = isset( $settings['columns_desktop'] ) ? max( 1, absint( $settings['columns_desktop'] ) ) : 4;
        $columns_desktop = max( 1, min( 6, $columns_desktop ) );
        $gap_desktop_data = $this->get_slider_value_with_unit( $settings, 'gap_desktop', 15, 'px' );
        $gap_desktop_size = isset( $gap_desktop_data['size'] ) ? (float) $gap_desktop_data['size'] : 15;
        if ( ! is_finite( $gap_desktop_size ) ) {
            $gap_desktop_size = 15;
        }
        $image_height_desktop_data = $this->get_slider_value_with_unit( $settings, 'image_height_desktop', 625, 'px' );
        $image_height_desktop = isset( $image_height_desktop_data['size'] ) ? (float) $image_height_desktop_data['size'] : 625;

        // Get tablet values
        $breakpoint_tablet_min = isset( $settings['breakpoint_tablet_min'] ) ? absint( $settings['breakpoint_tablet_min'] ) : 768;
        $breakpoint_tablet_max = isset( $settings['breakpoint_tablet_max'] ) ? absint( $settings['breakpoint_tablet_max'] ) : 1024;
        $columns_tablet  = isset( $settings['columns_tablet'] ) ? max( 1, absint( $settings['columns_tablet'] ) ) : 2;
        $columns_tablet  = max( 1, min( 4, $columns_tablet ) );
        $gap_tablet_data = $this->get_slider_value_with_unit( $settings, 'gap_tablet', 10, 'px' );
        $gap_tablet_size = isset( $gap_tablet_data['size'] ) ? (float) $gap_tablet_data['size'] : 10;
        if ( ! is_finite( $gap_tablet_size ) ) {
            $gap_tablet_size = 10;
        }
        $image_height_tablet_data = $this->get_slider_value_with_unit( $settings, 'image_height_tablet', 400, 'px' );
        $image_height_tablet = isset( $image_height_tablet_data['size'] ) ? (float) $image_height_tablet_data['size'] : 400;

        // Get mobile values
        $breakpoint_mobile_max = isset( $settings['breakpoint_mobile_max'] ) ? absint( $settings['breakpoint_mobile_max'] ) : 767;
        $columns_mobile  = isset( $settings['columns_mobile'] ) ? max( 1, absint( $settings['columns_mobile'] ) ) : 1;
        $columns_mobile  = max( 1, min( 2, $columns_mobile ) );
        $gap_mobile_data = $this->get_slider_value_with_unit( $settings, 'gap_mobile', 10, 'px' );
        $gap_mobile_size = isset( $gap_mobile_data['size'] ) ? (float) $gap_mobile_data['size'] : 10;
        if ( ! is_finite( $gap_mobile_size ) ) {
            $gap_mobile_size = 10;
        }
        $image_height_mobile_data = $this->get_slider_value_with_unit( $settings, 'image_height_mobile', 300, 'px' );
        $image_height_mobile = isset( $image_height_mobile_data['size'] ) ? (float) $image_height_mobile_data['size'] : 300;

        // Image controls
        $image_toggle    = isset( $settings['image_toggle'] ) && 'yes' === $settings['image_toggle'];
        $image_size      = isset( $settings['image_size'] ) ? $settings['image_size'] : 'large';
        $hover_effect    = isset( $settings['hover_effect'] ) && 'yes' === $settings['hover_effect'];
        $open_cart_popup = isset( $settings['open_cart_popup'] ) && 'yes' === $settings['open_cart_popup'];

        $include_ids = isset( $settings['specific_ids'] ) ? $this->parse_ids( $settings['specific_ids'] ) : [];

        $parent_category = isset( $settings['parent_category'] ) ? absint( $settings['parent_category'] ) : 0;
        $subcategories   = isset( $settings['subcategory'] ) ? array_filter( array_map( 'absint', (array) $settings['subcategory'] ) ) : [];

        // Get ordering settings
        $order_by = isset( $settings['order_by'] ) ? sanitize_key( $settings['order_by'] ) : 'date';
        $order    = isset( $settings['order'] ) ? strtoupper( sanitize_key( $settings['order'] ) ) : 'DESC';

        // Validate order_by
        $valid_order_by = [ 'date', 'modified', 'title', 'rand', 'ID' ];
        if ( ! in_array( $order_by, $valid_order_by, true ) ) {
            $order_by = 'date';
        }

        // Validate order
        if ( ! in_array( $order, [ 'ASC', 'DESC' ], true ) ) {
            $order = 'DESC';
        }

        // For random order, ignore ASC/DESC
        if ( 'rand' === $order_by ) {
            $order = 'ASC';
        }

        $query_args = [
            'post_type'      => $post_type,
            'posts_per_page' => $posts_per_page > 0 ? $posts_per_page : -1,
            'post_status'    => 'publish',
            'orderby'        => $order_by,
            'order'          => $order,
        ];

        if ( ! empty( $include_ids ) ) {
            $query_args['post__in'] = $include_ids;
            $query_args['orderby']  = 'post__in';
        }

        if ( 'product' === $post_type ) {
            $tax_query = [];

            if ( ! empty( $subcategories ) ) {
                $tax_query[] = [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $subcategories,
                ];
            } elseif ( $parent_category > 0 ) {
                $tax_query[] = [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => [ $parent_category ],
                ];
            }

            if ( ! empty( $tax_query ) ) {
                $query_args['tax_query'] = $tax_query;
            }
        }

        $wrapper_classes = [ 'bw-filtered-post-wall' ];
        $wrapper_style   = '--bw-fpw-columns:' . $columns_desktop . ';';
        $wrapper_style  .= '--bw-fpw-gap:' . $gap_desktop_size . 'px;';

        $grid_attributes = [
            'class'                       => 'bw-fpw-grid',
            'data-widget-id'              => $widget_id,
            'data-post-type'              => $post_type,
            'data-columns-desktop'        => $columns_desktop,
            'data-gap-desktop'            => $gap_desktop_size,
            'data-breakpoint-tablet-min'  => $breakpoint_tablet_min,
            'data-breakpoint-tablet-max'  => $breakpoint_tablet_max,
            'data-columns-tablet'         => $columns_tablet,
            'data-gap-tablet'             => $gap_tablet_size,
            'data-breakpoint-mobile-max'  => $breakpoint_mobile_max,
            'data-columns-mobile'         => $columns_mobile,
            'data-gap-mobile'             => $gap_mobile_size,
            'data-image-toggle'           => $image_toggle ? 'yes' : 'no',
            'data-image-size'             => $image_size,
            'data-hover-effect'           => $hover_effect ? 'yes' : 'no',
            'data-open-cart-popup'        => $open_cart_popup ? 'yes' : 'no',
            'data-order-by'               => $order_by,
            'data-order'                  => $order,
        ];

        $grid_attr_html = '';
        foreach ( $grid_attributes as $attr => $value ) {
            if ( '' === $value && 0 !== $value ) {
                continue;
            }

            $grid_attr_html .= sprintf( ' %s="%s"', esc_attr( $attr ), esc_attr( (string) $value ) );
        }

        $query = new \WP_Query( $query_args );
        ?>
        <div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>" style="<?php echo esc_attr( $wrapper_style ); ?>">
            <div<?php echo $grid_attr_html; ?>>
                <?php if ( $query->have_posts() ) : ?>
                    <?php
                    while ( $query->have_posts() ) :
                        $query->the_post();
                        $this->render_post_item( $settings, $post_type, $image_toggle, $image_size, $hover_effect, $open_cart_popup );
                    endwhile;
                    ?>
                <?php else : ?>
                    <div class="bw-fpw-placeholder">
                        <?php esc_html_e( 'Nessun contenuto disponibile.', 'bw-elementor-widgets' ); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <style>
            /* Mobile */
            @media (max-width: <?php echo esc_attr( $breakpoint_mobile_max ); ?>px) {
                .elementor-element-<?php echo esc_attr( $this->get_id() ); ?> .bw-fpw-media img {
                    height: <?php echo esc_attr( $image_height_mobile ); ?>px !important;
                }
            }

            /* Tablet */
            @media (min-width: <?php echo esc_attr( $breakpoint_tablet_min ); ?>px) and (max-width: <?php echo esc_attr( $breakpoint_tablet_max ); ?>px) {
                .elementor-element-<?php echo esc_attr( $this->get_id() ); ?> .bw-fpw-media img {
                    height: <?php echo esc_attr( $image_height_tablet ); ?>px !important;
                }
            }

            /* Desktop */
            @media (min-width: <?php echo esc_attr( $breakpoint_tablet_max + 1 ); ?>px) {
                .elementor-element-<?php echo esc_attr( $this->get_id() ); ?> .bw-fpw-media img {
                    height: <?php echo esc_attr( $image_height_desktop ); ?>px !important;
                }
            }
        </style>
        <?php
        wp_reset_postdata();
    }

    private function render_post_item( $settings, $post_type, $image_toggle, $image_size, $hover_effect, $open_cart_popup ) {
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

        if ( $image_toggle && has_post_thumbnail( $post_id ) ) {
            $thumbnail_args = [
                'loading' => 'lazy',
                'class'   => 'bw-slider-main',
            ];

            $thumbnail_html = get_the_post_thumbnail( $post_id, $image_size, $thumbnail_args );
        }

        $hover_image_html = '';
        if ( $hover_effect && 'product' === $post_type ) {
            $hover_image_id = (int) get_post_meta( $post_id, '_bw_slider_hover_image', true );

            if ( $hover_image_id ) {
                $hover_image_html = wp_get_attachment_image(
                    $hover_image_id,
                    $image_size,
                    false,
                    [
                        'class'   => 'bw-slider-hover',
                        'loading' => 'lazy',
                    ]
                );
            }
        }

        $price_html     = '';
        $has_add_to_cart = false;
        $add_to_cart_url = '';

        if ( 'product' === $post_type ) {
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

        $view_label = 'product' === $post_type
            ? esc_html__( 'View Product', 'bw-elementor-widgets' )
            : esc_html__( 'Read More', 'bw-elementor-widgets' );
        ?>
        <article <?php post_class( 'bw-fpw-item' ); ?>>
            <div class="bw-fpw-card">
                <div class="bw-slider-image-container">
                    <?php
                    $media_classes = [ 'bw-fpw-media' ];
                    if ( ! $thumbnail_html ) {
                        $media_classes[] = 'bw-fpw-media--placeholder';
                    }
                    ?>
                    <div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $media_classes ) ) ); ?>">
                        <?php if ( $thumbnail_html ) : ?>
                            <a class="bw-fpw-media-link" href="<?php echo esc_url( $permalink ); ?>">
                                <div class="bw-fpw-image bw-slick-slider-image<?php echo $hover_image_html ? ' bw-fpw-image--has-hover bw-slick-slider-image--has-hover' : ''; ?>">
                                    <?php echo wp_kses_post( $thumbnail_html ); ?>
                                    <?php if ( $hover_image_html ) : ?>
                                        <?php echo wp_kses_post( $hover_image_html ); ?>
                                    <?php endif; ?>
                                </div>
                            </a>

                            <div class="bw-fpw-overlay overlay-buttons has-buttons">
                                <div class="bw-fpw-overlay-buttons<?php echo $has_add_to_cart ? ' bw-fpw-overlay-buttons--double' : ''; ?>">
                                    <a class="bw-fpw-overlay-button overlay-button overlay-button--view" href="<?php echo esc_url( $permalink ); ?>">
                                        <span class="bw-fpw-overlay-button__label overlay-button__label"><?php echo $view_label; ?></span>
                                    </a>
                                    <?php if ( 'product' === $post_type && $has_add_to_cart && $add_to_cart_url ) : ?>
                                        <a class="bw-fpw-overlay-button overlay-button overlay-button--cart bw-btn-addtocart" href="<?php echo esc_url( $add_to_cart_url ); ?>"<?php echo $open_cart_popup ? ' data-open-cart-popup="1"' : ''; ?>>
                                            <span class="bw-fpw-overlay-button__label overlay-button__label"><?php esc_html_e( 'Add to Cart', 'bw-elementor-widgets' ); ?></span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else : ?>
                            <span class="bw-fpw-image-placeholder" aria-hidden="true"></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bw-fpw-content bw-slider-content">
                    <h3 class="bw-fpw-title">
                        <a href="<?php echo esc_url( $permalink ); ?>">
                            <?php echo esc_html( $title ); ?>
                        </a>
                    </h3>

                    <?php if ( ! empty( $excerpt ) ) : ?>
                        <div class="bw-fpw-description"><?php echo wp_kses_post( $excerpt ); ?></div>
                    <?php endif; ?>

                    <?php if ( $price_html ) : ?>
                        <div class="bw-fpw-price price"><?php echo wp_kses_post( $price_html ); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </article>
        <?php
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
            if ( ! isset( $post_type->name ) || 'attachment' === $post_type->name ) {
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

    private function get_total_post_count( $post_type ) {
        $counts = wp_count_posts( $post_type );

        if ( $counts && isset( $counts->publish ) ) {
            return (int) $counts->publish;
        }

        return 0;
    }

    private function get_filtered_post_ids( $post_type, $category, array $subcategories ) {
        $taxonomy = 'product' === $post_type ? 'product_cat' : 'category';

        $tax_query = [];

        if ( 'all' !== $category && absint( $category ) > 0 ) {
            if ( ! empty( $subcategories ) ) {
                $tax_query[] = [
                    'taxonomy' => $taxonomy,
                    'field'    => 'term_id',
                    'terms'    => $subcategories,
                ];
            } else {
                $tax_query[] = [
                    'taxonomy' => $taxonomy,
                    'field'    => 'term_id',
                    'terms'    => [ absint( $category ) ],
                ];
            }
        }

        $query_args = [
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => $tax_query,
        ];

        $query = new WP_Query( $query_args );

        return $query->posts;
    }

    private function get_subcategories_data( $post_type, $category = 'all' ) {
        $taxonomy = 'product' === $post_type ? 'product_cat' : 'category';

        $args = [
            'taxonomy'   => $taxonomy,
            'hide_empty' => true,
        ];

        if ( 'all' !== $category && ! empty( $category ) && absint( $category ) > 0 ) {
            $args['parent'] = absint( $category );
        }

        $terms = get_terms( $args );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return [];
        }

        if ( 'all' === $category ) {
            $terms = array_filter(
                $terms,
                static function ( $term ) {
                    return (int) $term->parent > 0;
                }
            );
        }

        $results = [];

        foreach ( $terms as $term ) {
            $results[] = [
                'term_id' => (int) $term->term_id,
                'name'    => $term->name,
                'count'   => (int) $term->count,
            ];
        }

        return $results;
    }

    private function collect_terms_from_posts( $taxonomy, array $post_ids ) {
        if ( empty( $post_ids ) ) {
            return [];
        }

        $terms_map = [];

        foreach ( $post_ids as $post_id ) {
            $terms = wp_get_object_terms( $post_id, $taxonomy );

            if ( empty( $terms ) || is_wp_error( $terms ) ) {
                continue;
            }

            foreach ( $terms as $term ) {
                $term_id = (int) $term->term_id;

                if ( ! isset( $terms_map[ $term_id ] ) ) {
                    $terms_map[ $term_id ] = [
                        'term_id' => $term_id,
                        'name'    => $term->name,
                        'count'   => 0,
                    ];
                }

                $terms_map[ $term_id ]['count']++;
            }
        }

        usort(
            $terms_map,
            static function ( $a, $b ) {
                return strcmp( $a['name'], $b['name'] );
            }
        );

        return $terms_map;
    }

    private function get_related_tags( $post_type, $category = 'all', array $subcategories = [] ) {
        $tag_taxonomy = 'product' === $post_type ? 'product_tag' : 'post_tag';

        if ( 'all' === $category || empty( $category ) ) {
            $terms = get_terms(
                [
                    'taxonomy'   => $tag_taxonomy,
                    'hide_empty' => true,
                ]
            );

            if ( empty( $terms ) || is_wp_error( $terms ) ) {
                return [];
            }

            $results = [];

            foreach ( $terms as $term ) {
                $results[] = [
                    'term_id' => (int) $term->term_id,
                    'name'    => $term->name,
                    'count'   => (int) $term->count,
                ];
            }

            return $results;
        }

        $post_ids = $this->get_filtered_post_ids( $post_type, $category, $subcategories );

        return $this->collect_terms_from_posts( $tag_taxonomy, $post_ids );
    }

    private function format_filter_label( $name, $count ) {
        $count = is_numeric( $count ) ? (int) $count : $count;

        return trim( sprintf( '%s (%s)', $name, $count ) );
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

    private function get_price_markup( $post_id ) {
        if ( ! $post_id ) {
            return '';
        }

        $format_price = static function ( $value ) {
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
