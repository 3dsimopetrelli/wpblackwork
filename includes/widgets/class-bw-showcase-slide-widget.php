<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BW Showcase Slide Widget
 *
 * Embla-based showcase slider sourced from the product showcase metabox.
 * No popup support. Text color is always driven by the `_bw_texts_color` metabox field.
 */
class BW_Showcase_Slide_Widget extends Widget_Base {

    public function get_name() {
        return 'bw-showcase-slide';
    }

    public function get_title() {
        return __( 'BW-UI Showcase Slide', 'bw-elementor-widgets' );
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_script_depends() {
        return [ 'embla-js', 'embla-autoplay-js', 'bw-embla-core-js', 'bw-showcase-slide-script' ];
    }

    public function get_style_depends() {
        return [ 'bw-embla-core-css', 'bw-showcase-slide-style' ];
    }

    protected function register_controls() {
        $this->register_query_controls();
        $this->register_slider_controls();
        $this->register_breakpoint_controls();
        $this->register_style_controls();
    }

    private function register_query_controls() {
        $this->start_controls_section(
            'section_query',
            [
                'label' => __( 'Query', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'specific_ids',
            [
                'label'       => __( 'Product IDs', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => __( 'e.g. 120, 125, 129', 'bw-elementor-widgets' ),
                'description' => __( 'Enter the product IDs in the exact order they should appear in the showcase slider.', 'bw-elementor-widgets' ),
            ]
        );

        $this->end_controls_section();
    }

    private function register_slider_controls() {
        $this->start_controls_section(
            'section_slider_general',
            [
                'label' => __( 'Slider Settings', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'infinite_loop',
            [
                'label'        => __( 'Infinite Loop', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'autoplay',
            [
                'label'        => __( 'Autoplay', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $this->add_control(
            'autoplay_speed',
            [
                'label'     => __( 'Autoplay Speed (ms)', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 3000,
                'min'       => 1000,
                'max'       => 10000,
                'step'      => 500,
                'condition' => [
                    'autoplay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'pause_on_hover',
            [
                'label'        => __( 'Pause on Hover', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'autoplay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'drag_free',
            [
                'label'        => __( 'Drag Free', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => '',
                'description'  => __( 'Free-scroll drag: the slide does not snap to position, scrolls freely.', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'touch_drag',
            [
                'label'        => __( 'Touch Drag (Mobile & Tablet)', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'slide_align',
            [
                'label'   => __( 'Slide Alignment', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'start'  => __( 'Start (default)', 'bw-elementor-widgets' ),
                    'center' => __( 'Center', 'bw-elementor-widgets' ),
                    'end'    => __( 'End', 'bw-elementor-widgets' ),
                ],
                'default' => 'start',
            ]
        );

        $this->end_controls_section();
    }

    private function register_breakpoint_controls() {
        $this->start_controls_section(
            'section_breakpoints',
            [
                'label' => __( 'Responsive Breakpoints', 'bw-elementor-widgets' ),
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'breakpoint',
            [
                'label'   => __( 'Breakpoint (px)', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 1024,
            ]
        );

        $repeater->add_control(
            'slides_to_show',
            [
                'label'   => __( 'Slides to Show', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 1,
                'min'     => 1,
                'max'     => 10,
                'condition' => [
                    'frame_ratio' => 'none',
                ],
            ]
        );

        $repeater->add_control(
            'slides_to_scroll',
            [
                'label'   => __( 'Slides to Scroll', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 1,
                'min'     => 1,
                'max'     => 10,
            ]
        );

        $repeater->add_control(
            'show_arrows',
            [
                'label'        => __( 'Show Arrows', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $repeater->add_control(
            'show_dots',
            [
                'label'        => __( 'Show Dots', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $repeater->add_control(
            'start_offset_left',
            [
                'label'      => __( 'Start Offset Left', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 400,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'size' => 0,
                    'unit' => 'px',
                ],
                'description' => __( 'Adds left breathing room before the first visible slide without changing the card ratio.', 'bw-elementor-widgets' ),
            ]
        );

        $repeater->add_control(
            'center_mode',
            [
                'label'        => __( 'Center Mode', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $repeater->add_control(
            'frame_ratio',
            [
                'label'       => __( 'Frame Ratio', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'none',
                'options'     => [
                    'none' => __( 'Free / Existing Controls', 'bw-elementor-widgets' ),
                    '3_2'  => __( 'Classic Photo (3:2)', 'bw-elementor-widgets' ),
                    '4_3'  => __( 'Standard (4:3)', 'bw-elementor-widgets' ),
                    '1_1'  => __( 'Square (1:1)', 'bw-elementor-widgets' ),
                    '16_9' => __( 'Widescreen (16:9)', 'bw-elementor-widgets' ),
                ],
                'description' => __( 'Locks the slide frame to a fixed ratio. When enabled, image height/width controls are replaced by a dedicated fit mode.', 'bw-elementor-widgets' ),
            ]
        );

        $repeater->add_control(
            'frame_ratio_fit',
            [
                'label'     => __( 'Frame Fit', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'cover',
                'options'   => [
                    'cover'   => __( 'Cover', 'bw-elementor-widgets' ),
                    'contain' => __( 'Contain', 'bw-elementor-widgets' ),
                ],
                'condition' => [
                    'frame_ratio!' => 'none',
                ],
            ]
        );

        $repeater->add_control(
            'classic_photo_size',
            [
                'label'       => __( 'Classic Photo Size', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'balanced',
                'options'     => [
                    'balanced' => __( 'Balanced', 'bw-elementor-widgets' ),
                    'large'    => __( 'Large', 'bw-elementor-widgets' ),
                    'peek'     => __( 'XL Peek', 'bw-elementor-widgets' ),
                ],
                'description' => __( 'Keeps the 3:2 ratio but increases slide width to reveal the next card at the edge of the viewport.', 'bw-elementor-widgets' ),
                'condition'   => [
                    'frame_ratio' => '3_2',
                ],
            ]
        );

        $repeater->add_control(
            'variable_width',
            [
                'label'        => __( 'Variable Width', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => '',
                'description'  => __( 'Use slide natural width instead of a calculated fraction.', 'bw-elementor-widgets' ),
                'condition'    => [
                    'frame_ratio' => 'none',
                ],
            ]
        );

        $repeater->add_control(
            'slide_width',
            [
                'label'       => __( 'Slide Width (px)', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::NUMBER,
                'default'     => '',
                'min'         => 100,
                'max'         => 2000,
                'step'        => 10,
                'placeholder' => __( 'Auto', 'bw-elementor-widgets' ),
                'condition'   => [
                    'frame_ratio'     => 'none',
                    'variable_width!' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'image_height_heading',
            [
                'label'     => __( 'Image Height Settings', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'frame_ratio' => 'none',
                ],
            ]
        );

        $repeater->add_control(
            'image_height_mode',
            [
                'label'   => __( 'Height Mode', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'auto',
                'options' => [
                    'auto'    => __( 'Auto (Original)', 'bw-elementor-widgets' ),
                    'fixed'   => __( 'Fixed Height (Width Auto)', 'bw-elementor-widgets' ),
                    'contain' => __( 'Contain (Fixed Dimensions)', 'bw-elementor-widgets' ),
                    'cover'   => __( 'Cover (Fixed Dimensions)', 'bw-elementor-widgets' ),
                ],
                'condition' => [
                    'frame_ratio' => 'none',
                ],
            ]
        );

        $repeater->add_control(
            'image_height',
            [
                'label'      => __( 'Image Height', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'vh' ],
                'range'      => [
                    'px' => [
                        'min'  => 100,
                        'max'  => 1500,
                        'step' => 10,
                    ],
                    'vh' => [
                        'min'  => 10,
                        'max'  => 100,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'size' => 600,
                    'unit' => 'px',
                ],
                'condition'  => [
                    'frame_ratio'       => 'none',
                    'image_height_mode!' => 'auto',
                ],
            ]
        );

        $repeater->add_control(
            'image_width',
            [
                'label'      => __( 'Image Width', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range'      => [
                    'px' => [
                        'min'  => 100,
                        'max'  => 2000,
                        'step' => 10,
                    ],
                    '%' => [
                        'min'  => 10,
                        'max'  => 100,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'size' => 100,
                    'unit' => '%',
                ],
                'condition'  => [
                    'frame_ratio'       => 'none',
                    'image_height_mode' => [ 'contain', 'cover' ],
                ],
            ]
        );

        $this->add_control(
            'breakpoints',
            [
                'label'       => __( 'Breakpoints', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'default'     => [
                    [
                        'breakpoint'       => 1024,
                        'slides_to_show'   => 2,
                        'slides_to_scroll' => 1,
                        'show_arrows'      => 'yes',
                        'show_dots'        => '',
                    ],
                    [
                        'breakpoint'       => 767,
                        'slides_to_show'   => 1,
                        'slides_to_scroll' => 1,
                        'show_arrows'      => '',
                        'show_dots'        => 'yes',
                    ],
                ],
                'title_field' => 'Breakpoint: {{{ breakpoint }}}px',
            ]
        );

        $this->end_controls_section();
    }

    private function register_style_controls() {
        $this->start_controls_section(
            'section_style_text',
            [
                'label' => __( 'Text', 'bw-elementor-widgets' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'heading_title_typography',
            [
                'label'     => __( 'Title', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'none',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'title_typography',
                'selector' => '{{WRAPPER}} .bw-showcase-slide-title',
            ]
        );

        $this->add_control(
            'heading_subtitle_typography',
            [
                'label'     => __( 'Subtitle', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'subtitle_typography',
                'selector' => '{{WRAPPER}} .bw-showcase-slide-description',
            ]
        );

        $this->add_control(
            'heading_labels_typography',
            [
                'label'     => __( 'Labels', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'labels_typography',
                'selector' => '{{WRAPPER}} .bw-showcase-slide-badge',
            ]
        );

        $this->add_control(
            'heading_physical_info_typography',
            [
                'label'     => __( 'Physical Info', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'physical_info_typography',
                'selector' => '{{WRAPPER}} .bw-showcase-slide-physical-line',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_link_button',
            [
                'label' => __( 'Link Button', 'bw-elementor-widgets' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'link_button_typography',
                'selector' => '{{WRAPPER}} .bw-showcase-slide-button',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_images',
            [
                'label' => __( 'Images', 'bw-elementor-widgets' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'image_border_radius',
            [
                'label'      => __( 'Border Radius', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-showcase-slide-media, {{WRAPPER}} .bw-showcase-slide-image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'slides_spacing',
            [
                'label'      => __( 'Spacing Between Slides', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 100,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'size' => 10,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-ss-slide' => 'padding-left: {{SIZE}}{{UNIT}}; padding-right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'image_size',
            [
                'label'   => __( 'Image Size', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'full',
                'options' => [
                    'thumbnail'    => __( 'Thumbnail (150×150)', 'bw-elementor-widgets' ),
                    'medium'       => __( 'Medium (300×300)', 'bw-elementor-widgets' ),
                    'medium_large' => __( 'Medium Large (768×auto)', 'bw-elementor-widgets' ),
                    'large'        => __( 'Large (1024×1024)', 'bw-elementor-widgets' ),
                    'custom_1200'  => __( 'Custom (1200×auto)', 'bw-elementor-widgets' ),
                    'custom_1500'  => __( 'Custom (1500×auto)', 'bw-elementor-widgets' ),
                    'full'         => __( 'Full Size (Original)', 'bw-elementor-widgets' ),
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_navigation',
            [
                'label' => __( 'Navigation Arrows', 'bw-elementor-widgets' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'arrow_color',
            [
                'label'     => __( 'Arrow Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-ss-arrow' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'arrow_size',
            [
                'label'      => __( 'Arrow Size', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => 10,
                        'max'  => 100,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'size' => 24,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-ss-arrow' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'arrow_padding',
            [
                'label'      => __( 'Arrow Padding', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 50,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'size' => 10,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-ss-arrow' => 'padding: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'arrows_vertical_offset',
            [
                'label'      => __( 'Vertical Offset from Bottom', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => -50,
                        'max'  => 200,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'size' => 20,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-ss-arrows-container' => 'bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'arrows_horizontal_offset',
            [
                'label'      => __( 'Horizontal Offset from Right', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 200,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'size' => 20,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-ss-arrows-container' => 'right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'arrows_gap',
            [
                'label'      => __( 'Gap Between Arrows', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 50,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'size' => 10,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-ss-arrows-container' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_dots',
            [
                'label' => __( 'Dots (Pagination)', 'bw-elementor-widgets' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'dots_color',
            [
                'label'     => __( 'Dots Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(255, 255, 255, 0.35)',
                'selectors' => [
                    '{{WRAPPER}} .bw-ps-dots-list li button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dots_active_color',
            [
                'label'     => __( 'Active Dot Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#80FD03',
                'selectors' => [
                    '{{WRAPPER}} .bw-ps-dots-list li.is-active button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dots_size',
            [
                'label'      => __( 'Dots Size', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => 4,
                        'max'  => 30,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'size' => 10,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-ps-dots-list li button' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'dots_position',
            [
                'label'   => __( 'Position', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'left'   => __( 'Left', 'bw-elementor-widgets' ),
                    'center' => __( 'Center', 'bw-elementor-widgets' ),
                    'right'  => __( 'Right', 'bw-elementor-widgets' ),
                ],
                'default' => 'center',
            ]
        );

        $this->add_control(
            'dots_vertical_offset',
            [
                'label'      => __( 'Vertical Offset from Bottom', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => -50,
                        'max'  => 100,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'size' => 24,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-ss-dots-container' => 'bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_cursor',
            [
                'label' => __( 'Custom Cursor', 'bw-elementor-widgets' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'enable_custom_cursor',
            [
                'label'        => __( 'Enable Custom Cursor', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings  = $this->get_settings_for_display();
        $widget_id = $this->get_id();
        $ids       = BW_Widget_Helper::parse_ids( $settings['specific_ids'] ?? '' );
        $is_editor = class_exists( '\Elementor\Plugin' )
            && \Elementor\Plugin::$instance->editor
            && \Elementor\Plugin::$instance->editor->is_edit_mode();

        if ( empty( $ids ) ) {
            if ( $is_editor ) {
                $this->render_placeholder( __( 'Add product IDs in Query > Product IDs to populate the showcase slider.', 'bw-elementor-widgets' ) );
            }
            return;
        }

        $query = new WP_Query(
            [
                'post_type'      => 'product',
                'post__in'       => $ids,
                'orderby'        => 'post__in',
                'posts_per_page' => count( $ids ),
                'post_status'    => 'publish',
                'no_found_rows'  => true,
            ]
        );

        $posts = $query->posts;
        wp_reset_postdata();

        if ( empty( $posts ) ) {
            if ( $is_editor ) {
                $this->render_placeholder( __( 'No published products were found for the provided IDs.', 'bw-elementor-widgets' ) );
            }
            return;
        }

        $slides = [];
        foreach ( $posts as $post ) {
            $slide = $this->build_slide_data( $post );
            if ( ! empty( $slide['image_id'] ) || ! empty( $slide['image_url'] ) ) {
                $slides[] = $slide;
            }
        }

        if ( empty( $slides ) ) {
            if ( $is_editor ) {
                $this->render_placeholder( __( 'The selected products do not have showcase images or featured images available.', 'bw-elementor-widgets' ) );
            }
            return;
        }

        $config = [
            'widgetId'           => $widget_id,
            'enableCustomCursor' => ( $settings['enable_custom_cursor'] ?? 'yes' ) === 'yes',
            'dotsPosition'       => $settings['dots_position'] ?? 'center',
            'horizontal'         => [
                'infinite'        => ( $settings['infinite_loop'] ?? 'yes' ) === 'yes',
                'autoplay'        => ( $settings['autoplay'] ?? '' ) === 'yes',
                'autoplaySpeed'   => absint( $settings['autoplay_speed'] ?? 3000 ),
                'pauseOnHover'    => ( $settings['pause_on_hover'] ?? 'yes' ) === 'yes',
                'dragFree'        => ( $settings['drag_free'] ?? '' ) === 'yes',
                'enableTouchDrag' => ( $settings['touch_drag'] ?? 'yes' ) === 'yes',
                'align'           => $settings['slide_align'] ?? 'start',
                'responsive'      => $this->build_responsive_config( $settings ),
            ],
        ];

        $this->add_render_attribute(
            'wrapper',
            [
                'class'          => 'bw-showcase-slide-wrapper bw-ps-wrapper',
                'data-widget-id' => esc_attr( $widget_id ),
                'data-config'    => wp_json_encode( $config ),
            ]
        );

        ?>
        <div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
            <?php $this->render_horizontal_layout( $slides, $settings ); ?>
        </div>
        <?php
    }

    protected function render_horizontal_layout( $slides, $settings ) {
        $image_size    = $this->get_image_size( $settings['image_size'] ?? 'full' );
        $dots_position = $settings['dots_position'] ?? 'center';
        $is_loop       = ( $settings['infinite_loop'] ?? '' ) === 'yes';
        $is_center     = ( $settings['slide_align'] ?? 'start' ) === 'center';
        $last_index    = count( $slides ) - 1;

        $this->render_breakpoint_css( $settings );
        ?>
        <div class="bw-showcase-slide-horizontal bw-ps-horizontal">
            <div class="bw-embla-viewport bw-ss-embla-viewport">
                <div class="bw-embla-container">
                    <?php foreach ( $slides as $index => $slide ) : ?>
                        <?php
                        $is_first       = 0 === $index;
                        $is_loop_center = $is_loop && $is_center && $index === $last_index;
                        $is_eager       = ( $index < 2 ) || $is_loop_center;
                        ?>
                        <div class="bw-embla-slide bw-ss-slide" data-bw-index="<?php echo esc_attr( $index ); ?>">
                            <?php $this->render_slide_card( $slide, $image_size, $is_first, $is_eager || $is_first ); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bw-ss-arrows-container">
                <button class="bw-ss-arrow bw-ss-arrow-prev" aria-label="<?php esc_attr_e( 'Previous', 'bw-elementor-widgets' ); ?>">&#8592;</button>
                <button class="bw-ss-arrow bw-ss-arrow-next" aria-label="<?php esc_attr_e( 'Next', 'bw-elementor-widgets' ); ?>">&#8594;</button>
            </div>

            <div class="bw-ss-dots-container bw-ss-dots-<?php echo esc_attr( $dots_position ); ?>"></div>
        </div>
        <?php
    }

    private function render_slide_card( $slide, $image_size, $is_first, $is_eager ) {
        $img_attrs = [
            'loading'       => $is_eager ? 'eager' : 'lazy',
            'decoding'      => $is_first ? 'sync' : 'async',
            'fetchpriority' => $is_first ? 'high' : 'auto',
            'class'         => 'bw-embla-img bw-showcase-slide-image-el',
        ];
        $style = sprintf(
            '--bw-showcase-slide-text-color: %1$s; --bw-showcase-slide-badge-border-color: %1$s;',
            esc_attr( $slide['text_color'] )
        );
        ?>
        <article class="bw-showcase-slide-card" style="<?php echo esc_attr( $style ); ?>">
            <div class="bw-showcase-slide-media bw-ps-image">
                <div class="bw-showcase-slide-image">
                    <?php if ( $slide['image_id'] ) : ?>
                        <?php echo wp_get_attachment_image( $slide['image_id'], $image_size, false, $img_attrs ); ?>
                    <?php else : ?>
                        <img
                            src="<?php echo esc_url( $slide['image_url'] ); ?>"
                            alt="<?php echo esc_attr( $slide['title'] ); ?>"
                            class="bw-embla-img bw-showcase-slide-image-el"
                            loading="<?php echo esc_attr( $is_eager ? 'eager' : 'lazy' ); ?>"
                            decoding="<?php echo esc_attr( $is_first ? 'sync' : 'async' ); ?>"
                            fetchpriority="<?php echo esc_attr( $is_first ? 'high' : 'auto' ); ?>"
                        >
                    <?php endif; ?>
                </div>

                <div class="bw-showcase-slide-overlay">
                    <div class="bw-showcase-slide-copy">
                        <?php if ( $slide['title'] ) : ?>
                            <h2 class="bw-showcase-slide-title"><?php echo esc_html( $slide['title'] ); ?></h2>
                        <?php endif; ?>

                        <?php if ( $slide['description'] ) : ?>
                            <p class="bw-showcase-slide-description"><?php echo esc_html( $slide['description'] ); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="bw-showcase-slide-footer">
                        <?php if ( 'physical' === $slide['product_type'] ) : ?>
                            <?php if ( ! empty( $slide['physical_info'] ) ) : ?>
                                <div class="bw-showcase-slide-physical">
                                    <?php foreach ( $slide['physical_info'] as $line ) : ?>
                                        <p class="bw-showcase-slide-physical-line"><?php echo esc_html( $line ); ?></p>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php elseif ( ! empty( $slide['labels'] ) ) : ?>
                            <div class="bw-showcase-slide-labels">
                                <?php foreach ( $slide['labels'] as $label ) : ?>
                                    <span class="bw-showcase-slide-badge"><?php echo esc_html( $label ); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( $slide['button_url'] && $slide['button_text'] ) : ?>
                            <div class="bw-showcase-slide-cta">
                                <a href="<?php echo esc_url( $slide['button_url'] ); ?>" class="bw-showcase-slide-arrow" aria-label="<?php echo esc_attr( $slide['button_text'] ); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                        <path d="m9 18 6-6-6-6"/>
                                    </svg>
                                </a>
                                <a href="<?php echo esc_url( $slide['button_url'] ); ?>" class="bw-showcase-slide-button">
                                    <?php echo esc_html( $slide['button_text'] ); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </article>
        <?php
    }

    private function build_slide_data( WP_Post $post ) {
        $product_id    = (int) $post->ID;
        $all_meta      = get_post_meta( $product_id );
        $get_meta      = static function ( $key ) use ( $all_meta ) {
            return isset( $all_meta[ $key ][0] ) ? $all_meta[ $key ][0] : '';
        };
        $product_title = get_the_title( $product_id );

        $showcase_image = $get_meta( '_bw_showcase_image' );
        if ( '' === $showcase_image ) {
            $showcase_image = $get_meta( '_product_showcase_image' );
        }

        $image_id  = 0;
        $image_url = '';
        if ( '' !== $showcase_image ) {
            if ( is_numeric( $showcase_image ) ) {
                $image_id = absint( $showcase_image );
            } else {
                $image_url = esc_url_raw( $showcase_image );
            }
        }

        if ( ! $image_id && ! $image_url ) {
            $thumb_id = (int) get_post_thumbnail_id( $product_id );
            if ( $thumb_id ) {
                $image_id = $thumb_id;
            }
        }

        $product_type = sanitize_key( $get_meta( '_bw_product_type' ) );
        if ( ! in_array( $product_type, [ 'digital', 'physical' ], true ) ) {
            $product_type = 'digital';
        }

        $showcase_title = trim( (string) $get_meta( '_bw_showcase_title' ) );
        if ( '' === $showcase_title ) {
            $showcase_title = $product_title;
        }

        $text_color = sanitize_hex_color( $get_meta( '_bw_texts_color' ) );
        if ( ! $text_color ) {
            $text_color = '#ffffff';
        }

        return [
            'id'            => $product_id,
            'product_type'  => $product_type,
            'title'         => $showcase_title,
            'description'   => trim( (string) $get_meta( '_bw_showcase_description' ) ),
            'labels'        => $this->build_labels_list( $all_meta ),
            'physical_info' => $this->build_physical_info_list( $all_meta ),
            'button_text'   => $this->resolve_button_text( $all_meta ),
            'button_url'    => $this->resolve_button_url( $product_id, $all_meta ),
            'text_color'    => $text_color,
            'image_id'      => $image_id,
            'image_url'     => $image_url,
        ];
    }

    private function build_labels_list( $all_meta ) {
        $get_meta = static function ( $key ) use ( $all_meta ) {
            return isset( $all_meta[ $key ][0] ) ? $all_meta[ $key ][0] : '';
        };

        $product_type = sanitize_key( $get_meta( '_bw_product_type' ) );
        if ( ! in_array( $product_type, [ 'digital', 'physical' ], true ) ) {
            $product_type = 'digital';
        }

        $labels = [];

        if ( 'digital' === $product_type ) {
            $assets_count = $get_meta( '_bw_assets_count' );
            $file_size    = $get_meta( '_bw_file_size' );
            $formats      = $get_meta( '_bw_formats' );

            if ( '' !== $assets_count ) {
                $assets_number = absint( $assets_count );
                if ( $assets_number > 0 ) {
                    $labels[] = sprintf(
                        '%d %s',
                        $assets_number,
                        _n( 'Asset', 'Assets', $assets_number, 'bw-elementor-widgets' )
                    );
                }
            }

            if ( '' !== $file_size ) {
                $size_display = trim( wp_strip_all_tags( $file_size ) );
                if ( '' !== $size_display && ! preg_match( '/[a-zA-Z]/', $size_display ) ) {
                    $size_display .= 'MB';
                }
                if ( '' !== $size_display ) {
                    $labels[] = $size_display;
                }
            }

            if ( '' !== $formats ) {
                foreach ( explode( ',', $formats ) as $format ) {
                    $format = trim( wp_strip_all_tags( $format ) );
                    if ( '' !== $format ) {
                        $labels[] = $format;
                    }
                }
            }
        }

        return $labels;
    }

    private function build_physical_info_list( $all_meta ) {
        $get_meta = static function ( $key ) use ( $all_meta ) {
            return isset( $all_meta[ $key ][0] ) ? $all_meta[ $key ][0] : '';
        };

        $product_type = sanitize_key( $get_meta( '_bw_product_type' ) );
        if ( 'physical' !== $product_type ) {
            return [];
        }

        $lines = [];

        foreach ( [ '_bw_info_1', '_bw_info_2' ] as $info_key ) {
            $value = trim( wp_strip_all_tags( $get_meta( $info_key ) ) );
            if ( '' !== $value ) {
                $lines[] = $value;
            }
        }

        return $lines;
    }

    private function resolve_button_text( $all_meta ) {
        $raw = isset( $all_meta['_product_button_text'][0] ) ? (string) $all_meta['_product_button_text'][0] : '';
        $raw = trim( wp_strip_all_tags( $raw ) );

        return '' !== $raw ? $raw : __( 'View Details', 'bw-elementor-widgets' );
    }

    private function resolve_button_url( $product_id, $all_meta ) {
        $raw = isset( $all_meta['_product_button_link'][0] ) ? (string) $all_meta['_product_button_link'][0] : '';
        $raw = esc_url_raw( trim( $raw ) );

        if ( $raw ) {
            return $raw;
        }

        return get_permalink( $product_id );
    }

    protected function get_image_size( $size_setting ) {
        switch ( $size_setting ) {
            case 'custom_1200':
                return [ 1200, 0 ];
            case 'custom_1500':
                return [ 1500, 0 ];
            default:
                return $size_setting;
        }
    }

    private function get_frame_ratio_value( $ratio_key ) {
        switch ( $ratio_key ) {
            case '3_2':
                return '3 / 2';
            case '4_3':
                return '4 / 3';
            case '1_1':
                return '1 / 1';
            case '16_9':
                return '16 / 9';
            default:
                return '';
        }
    }

    private function get_classic_photo_size_value( $size_key ) {
        switch ( $size_key ) {
            case 'large':
                return '66%';
            case 'peek':
                return '74%';
            case 'balanced':
            default:
                return '58%';
        }
    }

    protected function render_breakpoint_css( $settings ) {
        $breakpoints = ! empty( $settings['breakpoints'] ) ? $settings['breakpoints'] : [];
        if ( empty( $breakpoints ) ) {
            return;
        }

        usort(
            $breakpoints,
            function ( $a, $b ) {
                return absint( $b['breakpoint'] ) - absint( $a['breakpoint'] );
            }
        );

        $widget_id      = $this->get_id();
        $el_prefix      = '.elementor-element-' . esc_attr( $widget_id );
        $sel_slide      = $el_prefix . ' .bw-ss-slide';
        $sel_arrows     = $el_prefix . ' .bw-ss-arrows-container';
        $sel_dots       = $el_prefix . ' .bw-ss-dots-container';
        $sel_viewport   = $el_prefix . ' .bw-ss-embla-viewport';
        $sel_horizontal = $el_prefix . ' .bw-showcase-slide-horizontal';
        $sel_media      = $el_prefix . ' .bw-showcase-slide-media';
        $sel_image      = $el_prefix . ' .bw-showcase-slide-image-el';
        $sel_image_wrap = $el_prefix . ' .bw-showcase-slide-image';

        $css = '<style>';

        foreach ( $breakpoints as $bp ) {
            $bp_px          = absint( $bp['breakpoint'] );
            $slides_to_show = max( 1, absint( $bp['slides_to_show'] ?? 1 ) );
            $variable_width = ( $bp['variable_width'] ?? '' ) === 'yes';
            $slide_width    = absint( $bp['slide_width'] ?? 0 );
            $show_arrows    = ( $bp['show_arrows'] ?? 'yes' ) === 'yes';
            $show_dots      = ( $bp['show_dots'] ?? '' ) === 'yes';
            $height_mode    = sanitize_key( $bp['image_height_mode'] ?? 'auto' );
            $image_height   = $bp['image_height'] ?? null;
            $image_width    = $bp['image_width'] ?? null;
            $frame_ratio    = $this->get_frame_ratio_value( $bp['frame_ratio'] ?? 'none' );
            $frame_fit      = sanitize_key( $bp['frame_ratio_fit'] ?? 'cover' );
            $classic_photo_size = $this->get_classic_photo_size_value( $bp['classic_photo_size'] ?? 'balanced' );
            $start_offset_left = $bp['start_offset_left'] ?? null;

            if ( $bp_px <= 0 ) {
                continue;
            }

            if ( '3 / 2' === $frame_ratio ) {
                $slide_size = $classic_photo_size;
            } elseif ( $variable_width ) {
                $slide_size = 'auto';
            } elseif ( $slide_width > 0 ) {
                $slide_size = $slide_width . 'px';
            } elseif ( $slides_to_show > 1 ) {
                $slide_size = 'calc(100% / ' . $slides_to_show . ')';
            } else {
                $slide_size = '100%';
            }

            $css .= '@media (max-width:' . $bp_px . 'px){';
            $css .= $sel_slide . '{flex:0 0 ' . $slide_size . ';}';

            if ( '3 / 2' === $frame_ratio ) {
                $css .= $sel_slide . '{max-width:' . $slide_size . ';}';
            }

            if ( ! empty( $start_offset_left['size'] ) ) {
                $css .= $sel_viewport . '{box-sizing:border-box;padding-left:' . (float) $start_offset_left['size'] . ( $start_offset_left['unit'] ?? 'px' ) . ';}';
            } else {
                $css .= $sel_viewport . '{box-sizing:border-box;padding-left:0;}';
            }

            $css .= $sel_arrows . '{display:' . ( $show_arrows ? 'flex' : 'none' ) . ';}';
            $css .= $sel_dots . '{display:' . ( $show_dots ? 'flex' : 'none' ) . ';}';

            if ( $frame_ratio ) {
                $css .= $sel_media . '{aspect-ratio:' . $frame_ratio . ';}';
                $css .= $sel_image_wrap . '{display:block;height:100%;}';
                $css .= $sel_image . '{width:100%;height:100%;object-fit:' . ( 'contain' === $frame_fit ? 'contain' : 'cover' ) . ';object-position:center;}';
            } else {
                // Explicitly reset ratio-led styles so "Free / Existing Controls" becomes
                // the sole authority for image sizing at this breakpoint.
                $css .= $sel_media . '{aspect-ratio:auto;}';
                $css .= $sel_image_wrap . '{display:block;height:auto;}';
                $css .= $sel_image . '{width:100%;height:auto;object-fit:unset;object-position:initial;}';
            }

            if ( ! $frame_ratio && in_array( $height_mode, [ 'fixed', 'contain', 'cover' ], true ) ) {
                $css .= $sel_horizontal . '{';
                $css .= '--bw-ss-initial-image-height-mode:' . $height_mode . ';';

                if ( ! empty( $image_height['size'] ) ) {
                    $css .= '--bw-ss-initial-image-height:' . (float) $image_height['size'] . ( $image_height['unit'] ?? 'px' ) . ';';
                }

                if ( in_array( $height_mode, [ 'contain', 'cover' ], true ) && ! empty( $image_width['size'] ) ) {
                    $css .= '--bw-ss-initial-image-width:' . (float) $image_width['size'] . ( $image_width['unit'] ?? 'px' ) . ';';
                }

                $css .= '}';
            }

            if ( ! $frame_ratio && ! empty( $image_height['size'] ) ) {
                $css .= $sel_image . '{height:' . (float) $image_height['size'] . ( $image_height['unit'] ?? 'px' ) . ';}';
            }

            if ( ! $frame_ratio && in_array( $height_mode, [ 'contain', 'cover' ], true ) && ! empty( $image_width['size'] ) ) {
                $image_width_value = (float) $image_width['size'] . ( $image_width['unit'] ?? 'px' );

                if ( $variable_width && '%' === ( $image_width['unit'] ?? 'px' ) ) {
                    $css .= $sel_slide . '{flex:0 0 ' . $image_width_value . ';max-width:' . $image_width_value . ';}';
                    $css .= $sel_image . '{width:100%;}';
                } else {
                    $css .= $sel_image . '{width:' . $image_width_value . ';}';
                }

                $css .= $sel_image_wrap . '{display:flex;align-items:center;justify-content:center;}';
            }

            if ( ! $frame_ratio && 'contain' === $height_mode ) {
                $css .= $sel_image . '{object-fit:contain;object-position:center;}';
            } elseif ( ! $frame_ratio && 'cover' === $height_mode ) {
                $css .= $sel_image . '{object-fit:cover;object-position:center;}';
            } elseif ( ! $frame_ratio && 'fixed' === $height_mode ) {
                $css .= $sel_image . '{width:auto;}';
            }

            $css .= '}';
        }

        $css .= '</style>';

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $css;
    }

    protected function build_responsive_config( $settings ) {
        $responsive = [];

        if ( ! empty( $settings['breakpoints'] ) ) {
            foreach ( $settings['breakpoints'] as $breakpoint ) {
                $image_height = null;
                if ( ! empty( $breakpoint['image_height'] ) && isset( $breakpoint['image_height']['size'] ) ) {
                    $image_height = [
                        'size' => (float) $breakpoint['image_height']['size'],
                        'unit' => $breakpoint['image_height']['unit'] ?? 'px',
                    ];
                }

                $image_width = null;
                if ( ! empty( $breakpoint['image_width'] ) && isset( $breakpoint['image_width']['size'] ) ) {
                    $image_width = [
                        'size' => (float) $breakpoint['image_width']['size'],
                        'unit' => $breakpoint['image_width']['unit'] ?? 'px',
                    ];
                }

                $responsive[] = [
                    'breakpoint'      => absint( $breakpoint['breakpoint'] ),
                    'frameRatio'      => $this->get_frame_ratio_value( $breakpoint['frame_ratio'] ?? 'none' ),
                    'frameRatioFit'   => $breakpoint['frame_ratio_fit'] ?? 'cover',
                    'imageHeightMode' => $breakpoint['image_height_mode'] ?? 'auto',
                    'imageHeight'     => $image_height,
                    'imageWidth'      => $image_width,
                    'slidesToScroll'  => max( 1, absint( $breakpoint['slides_to_scroll'] ?? 1 ) ),
                    'centerMode'      => ( $breakpoint['center_mode'] ?? '' ) === 'yes',
                    'variableWidth'   => ( $breakpoint['variable_width'] ?? '' ) === 'yes',
                ];
            }
        }

        return $responsive;
    }

    private function render_placeholder( $message ) {
        ?>
        <div class="bw-showcase-slide-placeholder">
            <div class="bw-showcase-slide-placeholder__inner">
                <?php echo esc_html( $message ); ?>
            </div>
        </div>
        <?php
    }
}
