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
        // Button Settings
        $this->start_controls_section(
            'section_content',
            [
                'label' => __( 'Button Settings', 'bw' ),
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

        // Popup Content Settings
        $this->start_controls_section(
            'section_popup_content',
            [
                'label' => __( 'Popup Content', 'bw' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'popup_header_text',
            [
                'label'       => __( 'Header Text', 'bw' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( "Type what you're looking for", 'bw' ),
                'placeholder' => __( 'Enter header text', 'bw' ),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'popup_placeholder',
            [
                'label'       => __( 'Search Placeholder', 'bw' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Type...', 'bw' ),
                'placeholder' => __( 'Enter placeholder text', 'bw' ),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'popup_hint_text',
            [
                'label'       => __( 'Hint Text', 'bw' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Hit enter to search or ESC to close', 'bw' ),
                'placeholder' => __( 'Enter hint text', 'bw' ),
                'label_block' => true,
            ]
        );

        $this->end_controls_section();

        // Category Filters Settings
        $this->start_controls_section(
            'section_category_filters',
            [
                'label' => __( 'Category Filters', 'bw' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'enable_category_filters',
            [
                'label'        => __( 'Enable Category Filters', 'bw' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw' ),
                'label_off'    => __( 'No', 'bw' ),
                'return_value' => 'yes',
                'default'      => 'no',
            ]
        );

        // Get all product categories for the dropdown
        $categories_options = [];
        $categories_list    = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ]);

        if ( ! is_wp_error( $categories_list ) && ! empty( $categories_list ) ) {
            foreach ( $categories_list as $cat ) {
                $categories_options[ $cat->term_id ] = $cat->name;
            }
        }

        $this->add_control(
            'category_ids',
            [
                'label'       => __( 'Select Categories', 'bw' ),
                'type'        => Controls_Manager::SELECT2,
                'multiple'    => true,
                'options'     => $categories_options,
                'default'     => [],
                'description' => __( 'Select categories to show in filters. Leave empty to show all categories.', 'bw' ),
                'label_block' => true,
                'condition'   => [
                    'enable_category_filters' => 'yes',
                ],
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

        // Popup Style Section
        $this->start_controls_section(
            'section_popup_style',
            [
                'label' => __( 'Popup Style', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'popup_background_color',
            [
                'label'     => __( 'Background Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#FFFFFF',
                'selectors' => [
                    '{{WRAPPER}} .bw-search-overlay, body .bw-search-overlay[data-widget-id="{{ID}}"]' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'popup_padding',
            [
                'label'      => __( 'Padding', 'bw' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'default'    => [
                    'top'    => 40,
                    'right'  => 20,
                    'bottom' => 40,
                    'left'   => 20,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-search-overlay__container, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-search-overlay__container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'heading_popup_header',
            [
                'label'     => __( 'Header Typography', 'bw' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'popup_header_typography',
                'selector' => '{{WRAPPER}} .bw-search-overlay__title, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-search-overlay__title',
            ]
        );

        $this->add_control(
            'popup_header_color',
            [
                'label'     => __( 'Header Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-search-overlay__title, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-search-overlay__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'heading_popup_input',
            [
                'label'     => __( 'Search Input', 'bw' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'popup_input_typography',
                'selector' => '{{WRAPPER}} .bw-search-overlay__input, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-search-overlay__input',
            ]
        );

        $this->add_control(
            'popup_input_color',
            [
                'label'     => __( 'Input Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#080808',
                'selectors' => [
                    '{{WRAPPER}} .bw-search-overlay__input, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-search-overlay__input' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'popup_input_placeholder_color',
            [
                'label'     => __( 'Placeholder Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#C0C0C0',
                'selectors' => [
                    '{{WRAPPER}} .bw-search-overlay__input::placeholder, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-search-overlay__input::placeholder' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'popup_input_border_color',
            [
                'label'     => __( 'Input Border Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#080808',
                'selectors' => [
                    '{{WRAPPER}} .bw-search-overlay__input-wrapper, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-search-overlay__input-wrapper' => 'border-bottom-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'popup_input_border_width',
            [
                'label'      => __( 'Input Border Width', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 10, 'step' => 1 ],
                ],
                'default'    => [ 'size' => 1, 'unit' => 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-search-overlay__input-wrapper, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-search-overlay__input-wrapper' => 'border-bottom-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'heading_popup_hint',
            [
                'label'     => __( 'Hint Text', 'bw' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'popup_hint_typography',
                'selector' => '{{WRAPPER}} .bw-search-overlay__hint, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-search-overlay__hint',
            ]
        );

        $this->add_control(
            'popup_hint_color',
            [
                'label'     => __( 'Hint Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#606060',
                'selectors' => [
                    '{{WRAPPER}} .bw-search-overlay__hint, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-search-overlay__hint' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'heading_close_button',
            [
                'label'     => __( 'Close Button', 'bw' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'close_button_color',
            [
                'label'     => __( 'Icon Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#080808',
                'selectors' => [
                    '{{WRAPPER}} .bw-search-overlay__close, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-search-overlay__close' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'close_button_background',
            [
                'label'     => __( 'Background Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'transparent',
                'selectors' => [
                    '{{WRAPPER}} .bw-search-overlay__close, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-search-overlay__close' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'close_button_border_color',
            [
                'label'     => __( 'Border Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-search-overlay__close, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-search-overlay__close' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'close_button_border_width',
            [
                'label'      => __( 'Border Width', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 5, 'step' => 1 ],
                ],
                'default'    => [ 'size' => 1, 'unit' => 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-search-overlay__close, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-search-overlay__close' => 'border-width: {{SIZE}}{{UNIT}}; border-style: solid;',
                ],
            ]
        );

        $this->add_responsive_control(
            'close_button_border_radius',
            [
                'label'      => __( 'Border Radius', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 100, 'step' => 1 ],
                    '%'  => [ 'min' => 0, 'max' => 100 ],
                ],
                'default'    => [ 'size' => 50, 'unit' => '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-search-overlay__close, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-search-overlay__close' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->start_controls_tabs( 'tabs_close_button_hover' );

        $this->start_controls_tab(
            'tab_close_button_hover',
            [
                'label' => __( 'Hover', 'bw' ),
            ]
        );

        $this->add_control(
            'close_button_color_hover',
            [
                'label'     => __( 'Icon Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-search-overlay__close:hover, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-search-overlay__close:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'close_button_background_hover',
            [
                'label'     => __( 'Background Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#E8E8E8',
                'selectors' => [
                    '{{WRAPPER}} .bw-search-overlay__close:hover, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-search-overlay__close:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'close_button_border_color_hover',
            [
                'label'     => __( 'Border Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-search-overlay__close:hover, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-search-overlay__close:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->end_controls_section();

        // Category Buttons Style Section
        $this->start_controls_section(
            'section_category_buttons_style',
            [
                'label'     => __( 'Category Buttons Style', 'bw' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_category_filters' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'category_button_typography',
                'selector' => '{{WRAPPER}} .bw-category-filter, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-category-filter',
            ]
        );

        $this->start_controls_tabs( 'tabs_category_button_colors' );

        $this->start_controls_tab(
            'tab_category_button_normal',
            [
                'label' => __( 'Normal', 'bw' ),
            ]
        );

        $this->add_control(
            'category_button_text_color',
            [
                'label'     => __( 'Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-category-filter, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-category-filter' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'category_button_background_color',
            [
                'label'     => __( 'Background Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#FFFFFF',
                'selectors' => [
                    '{{WRAPPER}} .bw-category-filter, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-category-filter' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'category_button_border_color',
            [
                'label'     => __( 'Border Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-category-filter, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-category-filter' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_category_button_hover',
            [
                'label' => __( 'Hover', 'bw' ),
            ]
        );

        $this->add_control(
            'category_button_text_color_hover',
            [
                'label'     => __( 'Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-category-filter:hover, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-category-filter:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'category_button_background_color_hover',
            [
                'label'     => __( 'Background Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-category-filter:hover, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-category-filter:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'category_button_border_color_hover',
            [
                'label'     => __( 'Border Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-category-filter:hover, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-category-filter:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_category_button_active',
            [
                'label' => __( 'Active', 'bw' ),
            ]
        );

        $this->add_control(
            'category_button_text_color_active',
            [
                'label'     => __( 'Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#FFFFFF',
                'selectors' => [
                    '{{WRAPPER}} .bw-category-filter.is-active, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-category-filter.is-active' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'category_button_background_color_active',
            [
                'label'     => __( 'Background Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-category-filter.is-active, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-category-filter.is-active' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'category_button_border_color_active',
            [
                'label'     => __( 'Border Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-category-filter.is-active, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-category-filter.is-active' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_control(
            'category_button_border_width',
            [
                'label'      => __( 'Border Width', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 5, 'step' => 1 ],
                ],
                'default'    => [ 'size' => 1, 'unit' => 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-category-filter, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-category-filter' => 'border-width: {{SIZE}}{{UNIT}};',
                ],
                'separator'  => 'before',
            ]
        );

        $this->add_responsive_control(
            'category_button_border_radius',
            [
                'label'      => __( 'Border Radius', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 50, 'step' => 1 ],
                    '%'  => [ 'min' => 0, 'max' => 100 ],
                ],
                'default'    => [ 'size' => 4, 'unit' => 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-category-filter, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-category-filter' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'category_button_padding',
            [
                'label'      => __( 'Padding', 'bw' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'default'    => [
                    'top'    => 10,
                    'right'  => 20,
                    'bottom' => 10,
                    'left'   => 20,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-category-filter, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-category-filter' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'category_button_margin',
            [
                'label'      => __( 'Margin', 'bw' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'default'    => [
                    'top'    => 0,
                    'right'  => 8,
                    'bottom' => 8,
                    'left'   => 0,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-category-filter, body .bw-search-overlay[data-widget-id="{{ID}}"] .bw-category-filter' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
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
        $this->render_search_overlay( $settings );
    }

    private function render_search_overlay( $settings ) {
        // Get custom texts
        $header_text = isset( $settings['popup_header_text'] ) && '' !== trim( $settings['popup_header_text'] )
            ? $settings['popup_header_text']
            : __( "Type what you're looking for", 'bw' );

        $placeholder = isset( $settings['popup_placeholder'] ) && '' !== trim( $settings['popup_placeholder'] )
            ? $settings['popup_placeholder']
            : __( 'Type...', 'bw' );

        $hint_text = isset( $settings['popup_hint_text'] ) && '' !== trim( $settings['popup_hint_text'] )
            ? $settings['popup_hint_text']
            : __( 'Hit enter to search or ESC to close', 'bw' );

        // Category filters
        $enable_filters = isset( $settings['enable_category_filters'] ) && 'yes' === $settings['enable_category_filters'];
        $categories = [];

        if ( $enable_filters ) {
            // category_ids is now an array from SELECT2 control
            $category_ids = isset( $settings['category_ids'] ) && is_array( $settings['category_ids'] ) && ! empty( $settings['category_ids'] )
                ? $settings['category_ids']
                : [];

            if ( ! empty( $category_ids ) ) {
                // Get specific categories by ID
                $categories = get_terms([
                    'taxonomy'   => 'product_cat',
                    'include'    => $category_ids,
                    'hide_empty' => false,
                ]);
            } else {
                // Get all categories
                $categories = get_terms([
                    'taxonomy'   => 'product_cat',
                    'hide_empty' => false,
                ]);
            }

            if ( is_wp_error( $categories ) ) {
                $categories = [];
            }
        }

        // Widget ID for CSS targeting
        $widget_id = $this->get_id();
        ?>
        <div class="bw-search-overlay" data-widget-id="<?php echo esc_attr( $widget_id ); ?>" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Search', 'bw' ); ?>">
            <div class="bw-search-overlay__container">
                <button class="bw-search-overlay__close" type="button" aria-label="<?php esc_attr_e( 'Close search', 'bw' ); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>

                <div class="bw-search-overlay__content">
                    <h2 class="bw-search-overlay__title"><?php echo esc_html( $header_text ); ?></h2>

                    <form class="bw-search-overlay__form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <div class="bw-search-overlay__input-wrapper">
                            <input
                                type="search"
                                name="s"
                                class="bw-search-overlay__input"
                                placeholder="<?php echo esc_attr( $placeholder ); ?>"
                                aria-label="<?php esc_attr_e( 'Search', 'bw' ); ?>"
                                autocomplete="off"
                            />
                        </div>

                        <?php if ( $enable_filters && ! empty( $categories ) ) : ?>
                            <div class="bw-search-overlay__filters">
                                <?php foreach ( $categories as $category ) : ?>
                                    <button
                                        type="button"
                                        class="bw-category-filter"
                                        data-category-id="<?php echo esc_attr( $category->term_id ); ?>"
                                        data-category-slug="<?php echo esc_attr( $category->slug ); ?>"
                                    >
                                        <?php echo esc_html( $category->name ); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="product_cat" class="bw-selected-category" value="" />
                        <?php endif; ?>
                    </form>

                    <p class="bw-search-overlay__hint"><?php echo esc_html( $hint_text ); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
}
