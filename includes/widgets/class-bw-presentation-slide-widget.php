<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BW Presentation Slide Widget
 *
 * Horizontal carousel or vertical elevator layout with popup functionality
 * and custom cursor support.
 */
class BW_Presentation_Slide_Widget extends Widget_Base {

    public function get_name() {
        return 'bw-presentation-slide';
    }

    public function get_title() {
        return __( 'BW Presentation Slide', 'bw-elementor-widgets' );
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_script_depends() {
        return [ 'slick-js', 'bw-presentation-slide-script' ];
    }

    public function get_style_depends() {
        return [ 'slick-css', 'bw-presentation-slide-style' ];
    }

    protected function register_controls() {
        // Content → General Section
        $this->start_controls_section(
            'section_general',
            [
                'label' => __( 'General', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'layout_mode',
            [
                'label'   => __( 'Layout Mode', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'horizontal' => __( 'Horizontal', 'bw-elementor-widgets' ),
                    'vertical'   => __( 'Vertical', 'bw-elementor-widgets' ),
                ],
                'default' => 'horizontal',
            ]
        );

        $this->add_control(
            'images_source',
            [
                'label'   => __( 'Images Source', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'custom' => __( 'Custom Gallery', 'bw-elementor-widgets' ),
                    'query'  => __( 'Query (Product Gallery)', 'bw-elementor-widgets' ),
                ],
                'default' => 'custom',
            ]
        );

        $this->add_control(
            'gallery',
            [
                'label'      => __( 'Add Images', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::GALLERY,
                'default'    => [],
                'show_label' => false,
                'condition'  => [
                    'images_source' => 'custom',
                ],
            ]
        );

        $this->end_controls_section();

        // Slider → General (Horizontal)
        $this->start_controls_section(
            'section_slider_general',
            [
                'label'     => __( 'Slider Settings', 'bw-elementor-widgets' ),
                'condition' => [
                    'layout_mode' => 'horizontal',
                ],
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
            'transition_speed',
            [
                'label'   => __( 'Transition Speed (ms)', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 500,
                'min'     => 100,
                'max'     => 3000,
                'step'    => 100,
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

        $this->end_controls_section();

        // Slider → Breakpoints (Horizontal)
        $this->start_controls_section(
            'section_breakpoints',
            [
                'label'     => __( 'Responsive Breakpoints', 'bw-elementor-widgets' ),
                'condition' => [
                    'layout_mode' => 'horizontal',
                ],
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
            'variable_width',
            [
                'label'        => __( 'Variable Width', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => '',
                'description'  => __( 'Use original image width', 'bw-elementor-widgets' ),
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
                'description' => __( 'Set fixed width for slides (leave empty for auto)', 'bw-elementor-widgets' ),
                'condition'   => [
                    'variable_width!' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'image_height_heading',
            [
                'label'     => __( 'Image Height', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $repeater->add_control(
            'image_height_mode',
            [
                'label'       => __( 'Height Mode', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'auto',
                'options'     => [
                    'auto'    => __( 'Auto', 'bw-elementor-widgets' ),
                    'fixed'   => __( 'Fixed Height', 'bw-elementor-widgets' ),
                    'contain' => __( 'Contain', 'bw-elementor-widgets' ),
                    'cover'   => __( 'Cover', 'bw-elementor-widgets' ),
                ],
            ]
        );

        $repeater->add_control(
            'image_height',
            [
                'label'      => __( 'Height (px)', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::NUMBER,
                'default'    => 600,
                'min'        => 100,
                'max'        => 1500,
                'step'       => 10,
                'condition'  => [
                    'image_height_mode!' => 'auto',
                ],
            ]
        );

        $repeater->add_control(
            'image_width',
            [
                'label'      => __( 'Width (px)', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::NUMBER,
                'default'    => '',
                'min'        => 100,
                'max'        => 2000,
                'step'       => 10,
                'placeholder' => __( 'Auto', 'bw-elementor-widgets' ),
                'condition'  => [
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
                        'slides_to_show'   => 3,
                        'slides_to_scroll' => 1,
                        'show_arrows'      => 'yes',
                        'show_dots'        => '',
                    ],
                    [
                        'breakpoint'       => 768,
                        'slides_to_show'   => 1,
                        'slides_to_scroll' => 1,
                        'show_arrows'      => '',
                        'show_dots'        => 'yes',
                        'center_mode'      => 'yes',
                    ],
                ],
                'title_field' => 'Breakpoint: {{{ breakpoint }}}px',
            ]
        );

        $this->end_controls_section();

        // Vertical → Desktop
        $this->start_controls_section(
            'section_vertical_desktop',
            [
                'label'     => __( 'Vertical Desktop', 'bw-elementor-widgets' ),
                'condition' => [
                    'layout_mode' => 'vertical',
                ],
            ]
        );

        $this->add_control(
            'enable_thumbnails',
            [
                'label'        => __( 'Enable Thumbnails', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'smooth_scroll',
            [
                'label'        => __( 'Smooth Scroll (Elevator)', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->end_controls_section();

        // Vertical → Responsive
        $this->start_controls_section(
            'section_vertical_responsive',
            [
                'label'     => __( 'Vertical Responsive', 'bw-elementor-widgets' ),
                'condition' => [
                    'layout_mode' => 'vertical',
                ],
            ]
        );

        $this->add_control(
            'enable_responsive_mode',
            [
                'label'        => __( 'Enable Responsive Mode', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => __( 'Switch to Slick slider layout on mobile/tablet', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'responsive_breakpoint',
            [
                'label'     => __( 'Breakpoint (px)', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 1024,
                'min'       => 320,
                'max'       => 1920,
                'condition' => [
                    'enable_responsive_mode' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'thumbs_slides_to_show',
            [
                'label'     => __( 'Thumbnails Slides to Show', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 4,
                'min'       => 2,
                'max'       => 10,
                'condition' => [
                    'enable_responsive_mode' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Popup Settings - Only Enable/Disable
        $this->start_controls_section(
            'section_popup',
            [
                'label' => __( 'Popup Settings', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'enable_popup',
            [
                'label'        => __( 'Enable Popup', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->end_controls_section();

        // ========================================
        // STYLE TAB
        // ========================================

        // Style → Images
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
                    '{{WRAPPER}} .bw-ps-image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .bw-ps-slide' => 'padding-left: {{SIZE}}{{UNIT}}; padding-right: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'layout_mode' => 'horizontal',
                ],
            ]
        );

        $this->add_control(
            'image_size',
            [
                'label'       => __( 'Image Size', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'full',
                'options'     => [
                    'thumbnail'      => __( 'Thumbnail (150×150)', 'bw-elementor-widgets' ),
                    'medium'         => __( 'Medium (300×300)', 'bw-elementor-widgets' ),
                    'medium_large'   => __( 'Medium Large (768×auto)', 'bw-elementor-widgets' ),
                    'large'          => __( 'Large (1024×1024)', 'bw-elementor-widgets' ),
                    'custom_1200'    => __( 'Custom (1200×auto)', 'bw-elementor-widgets' ),
                    'custom_1500'    => __( 'Custom (1500×auto)', 'bw-elementor-widgets' ),
                    'full'           => __( 'Full Size (Original)', 'bw-elementor-widgets' ),
                ],
                'condition'   => [
                    'layout_mode' => 'horizontal',
                ],
                'description' => __( 'Select image size for gallery slides', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'popup_image_size',
            [
                'label'       => __( 'Popup Image Size', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'full',
                'options'     => [
                    'thumbnail'      => __( 'Thumbnail (150×150)', 'bw-elementor-widgets' ),
                    'medium'         => __( 'Medium (300×300)', 'bw-elementor-widgets' ),
                    'medium_large'   => __( 'Medium Large (768×auto)', 'bw-elementor-widgets' ),
                    'large'          => __( 'Large (1024×1024)', 'bw-elementor-widgets' ),
                    'custom_1200'    => __( 'Custom (1200×auto)', 'bw-elementor-widgets' ),
                    'custom_1500'    => __( 'Custom (1500×auto)', 'bw-elementor-widgets' ),
                    'custom_2000'    => __( 'Custom (2000×auto)', 'bw-elementor-widgets' ),
                    'full'           => __( 'Full Size (Original)', 'bw-elementor-widgets' ),
                ],
                'condition'   => [
                    'layout_mode' => 'horizontal',
                    'enable_popup' => 'yes',
                ],
                'description' => __( 'Select image size for popup gallery', 'bw-elementor-widgets' ),
            ]
        );

        $this->end_controls_section();

        // Style → Navigation (Horizontal)
        $this->start_controls_section(
            'section_style_navigation',
            [
                'label'     => __( 'Navigation Arrows', 'bw-elementor-widgets' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'layout_mode' => 'horizontal',
                ],
            ]
        );

        $this->add_control(
            'arrow_color',
            [
                'label'     => __( 'Arrow Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-ps-arrow' => 'color: {{VALUE}};',
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
                    '{{WRAPPER}} .bw-ps-arrow' => 'font-size: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .bw-ps-arrow' => 'padding: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .bw-ps-arrows-container' => 'bottom: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .bw-ps-arrows-container' => 'right: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .bw-ps-arrows-container' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style → Dots (Pagination)
        $this->start_controls_section(
            'section_style_dots',
            [
                'label'     => __( 'Dots (Pagination)', 'bw-elementor-widgets' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'layout_mode' => 'horizontal',
                ],
            ]
        );

        $this->add_control(
            'dots_color',
            [
                'label'     => __( 'Dots Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(0, 0, 0, 0.3)',
                'selectors' => [
                    '{{WRAPPER}} .slick-dots li button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dots_active_color',
            [
                'label'     => __( 'Active Dot Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .slick-dots li.slick-active button' => 'background-color: {{VALUE}};',
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
                    '{{WRAPPER}} .slick-dots li button' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
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
                    'size' => -25,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .slick-dots' => 'bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style → Popup
        $this->start_controls_section(
            'section_style_popup',
            [
                'label'     => __( 'Popup', 'bw-elementor-widgets' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_popup' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'popup_overlay_bg',
            [
                'label'     => __( 'Overlay Background', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(255, 255, 255, 0.95)',
                'selectors' => [
                    '{{WRAPPER}} .bw-ps-popup-overlay' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'popup_max_width',
            [
                'label'      => __( 'Max Image Width (px)', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => 800,
                        'max'  => 3000,
                        'step' => 100,
                    ],
                ],
                'default'    => [
                    'size' => 2000,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-ps-popup-image img' => 'max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'popup_header_heading',
            [
                'label'     => __( 'Header', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'popup_header_padding',
            [
                'label'      => __( 'Header Padding', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', 'rem' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-ps-popup-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'popup_header_bg',
            [
                'label'     => __( 'Header Background', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .bw-ps-popup-header' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'popup_title_heading',
            [
                'label'     => __( 'Title', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'popup_title_typography',
                'selector' => '{{WRAPPER}} .bw-ps-popup-title',
            ]
        );

        $this->add_control(
            'popup_title_color',
            [
                'label'     => __( 'Title Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-ps-popup-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'popup_close_heading',
            [
                'label'     => __( 'Close Button', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'popup_close_color',
            [
                'label'     => __( 'Close Button Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-ps-popup-close' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'popup_close_hover_color',
            [
                'label'     => __( 'Close Button Hover Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .bw-ps-popup-close:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'popup_close_size',
            [
                'label'      => __( 'Close Button Size', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => 16,
                        'max'  => 60,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'size' => 24,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-ps-popup-close' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style → Vertical Thumbnails
        $this->start_controls_section(
            'section_style_vertical_thumbs',
            [
                'label'     => __( 'Vertical Thumbnails', 'bw-elementor-widgets' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'layout_mode' => 'vertical',
                ],
            ]
        );

        $this->add_responsive_control(
            'thumbnails_width',
            [
                'label'      => __( 'Thumbnails Width', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => 80,
                        'max'  => 400,
                        'step' => 10,
                    ],
                ],
                'default'    => [
                    'size' => 150,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-ps-thumbnails' => 'flex: 0 0 {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style → Custom Cursor
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

        $this->add_control(
            'hide_system_cursor',
            [
                'label'        => __( 'Hide System Cursor', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'enable_custom_cursor' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'cursor_zoom_text',
            [
                'label'     => __( 'Zoom Text', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::TEXT,
                'default'   => __( 'ZOOM', 'bw-elementor-widgets' ),
                'condition' => [
                    'enable_custom_cursor' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $widget_id = $this->get_id();

        // Get images based on source
        $images = $this->get_images_for_render( $settings );

        if ( empty( $images ) ) {
            echo '<p>' . esc_html__( 'No images available.', 'bw-elementor-widgets' ) . '</p>';
            return;
        }

        // Get popup title - use product name if available
        $popup_title = $this->get_popup_title();

        // Build configuration for JavaScript
        $config = [
            'widgetId'             => $widget_id,
            'layoutMode'           => $settings['layout_mode'],
            'enablePopup'          => $settings['enable_popup'] === 'yes',
            'enableCustomCursor'   => $settings['enable_custom_cursor'] === 'yes',
            'hideSystemCursor'     => $settings['hide_system_cursor'] === 'yes',
            'cursorZoomText'       => $settings['cursor_zoom_text'],
            'popupTitle'           => $popup_title,
            'dotsPosition'         => $settings['dots_position'] ?? 'center',
            'horizontal'           => [
                'infinite'         => $settings['infinite_loop'] === 'yes',
                'autoplay'         => $settings['autoplay'] === 'yes',
                'autoplaySpeed'    => absint( $settings['autoplay_speed'] ),
                'speed'            => absint( $settings['transition_speed'] ),
                'pauseOnHover'     => $settings['pause_on_hover'] === 'yes',
                'responsive'       => $this->build_responsive_config( $settings ),
            ],
            'vertical'             => [
                'enableThumbnails'    => $settings['enable_thumbnails'] === 'yes',
                'smoothScroll'        => $settings['smooth_scroll'] === 'yes',
                'enableResponsive'    => $settings['enable_responsive_mode'] === 'yes',
                'responsiveBreakpoint' => absint( $settings['responsive_breakpoint'] ),
                'thumbsSlidesToShow'  => absint( $settings['thumbs_slides_to_show'] ),
            ],
        ];

        $this->add_render_attribute( 'wrapper', [
            'class'               => 'bw-ps-wrapper',
            'data-widget-id'      => $widget_id,
            'data-layout-mode'    => $settings['layout_mode'],
            'data-config'         => wp_json_encode( $config ),
            'data-dots-position'  => $settings['dots_position'] ?? 'center',
        ] );

        ?>
        <div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
            <?php
            if ( $settings['layout_mode'] === 'horizontal' ) {
                $this->render_horizontal_layout( $images, $settings );
            } else {
                $this->render_vertical_layout( $images, $settings );
            }

            if ( $settings['enable_popup'] === 'yes' ) {
                $this->render_popup_modal( $images, $settings, $popup_title );
            }
            ?>
        </div>
        <?php
    }

    /**
     * Get popup title - use product name if in product context
     */
    protected function get_popup_title() {
        global $product;

        if ( $product && is_a( $product, 'WC_Product' ) ) {
            return $product->get_name();
        }

        // Fallback to post title
        if ( is_singular() ) {
            return get_the_title();
        }

        return __( 'Gallery', 'bw-elementor-widgets' );
    }

    /**
     * Get images for rendering based on source
     */
    protected function get_images_for_render( $settings ) {
        $images = [];

        if ( $settings['images_source'] === 'custom' && ! empty( $settings['gallery'] ) ) {
            foreach ( $settings['gallery'] as $image ) {
                $images[] = [
                    'id'  => $image['id'],
                    'url' => $image['url'],
                ];
            }
        } elseif ( $settings['images_source'] === 'query' ) {
            // Get current product gallery images
            global $product;
            if ( $product && is_a( $product, 'WC_Product' ) ) {
                $attachment_ids = $product->get_gallery_image_ids();

                // Include featured image as first
                $featured_id = $product->get_image_id();
                if ( $featured_id ) {
                    array_unshift( $attachment_ids, $featured_id );
                }

                foreach ( $attachment_ids as $attachment_id ) {
                    $images[] = [
                        'id'  => $attachment_id,
                        'url' => wp_get_attachment_url( $attachment_id ),
                    ];
                }
            }
        }

        return $images;
    }

    /**
     * Get image size for wp_get_attachment_image
     * Converts custom sizes to array format [width, height]
     */
    protected function get_image_size( $size_setting ) {
        switch ( $size_setting ) {
            case 'custom_1200':
                return [ 1200, 0 ]; // 1200×auto
            case 'custom_1500':
                return [ 1500, 0 ]; // 1500×auto
            case 'custom_2000':
                return [ 2000, 0 ]; // 2000×auto
            default:
                return $size_setting; // WordPress default sizes
        }
    }

    /**
     * Render horizontal layout
     */
    protected function render_horizontal_layout( $images, $settings ) {
        // Get image size from settings
        $image_size_setting = ! empty( $settings['image_size'] ) ? $settings['image_size'] : 'full';
        $image_size = $this->get_image_size( $image_size_setting );

        ?>
        <div class="bw-ps-horizontal">
            <div class="bw-ps-slider-horizontal">
                <?php foreach ( $images as $index => $image ) : ?>
                    <div class="bw-ps-slide" data-bw-index="<?php echo esc_attr( $index ); ?>" data-attachment-id="<?php echo esc_attr( $image['id'] ); ?>">
                        <div class="bw-ps-image bw-ps-image-clickable">
                            <?php
                            echo wp_get_attachment_image(
                                $image['id'],
                                $image_size,
                                false,
                                [
                                    'loading'  => 'lazy',
                                    'decoding' => 'async',
                                ]
                            );
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="bw-ps-arrows-container">
                <button class="bw-ps-arrow bw-ps-arrow-prev" aria-label="<?php esc_attr_e( 'Previous', 'bw-elementor-widgets' ); ?>">
                    ←
                </button>
                <button class="bw-ps-arrow bw-ps-arrow-next" aria-label="<?php esc_attr_e( 'Next', 'bw-elementor-widgets' ); ?>">
                    →
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Render vertical layout
     */
    protected function render_vertical_layout( $images, $settings ) {
        ?>
        <div class="bw-ps-vertical">
            <?php if ( $settings['enable_thumbnails'] === 'yes' ) : ?>
                <div class="bw-ps-thumbnails">
                    <?php foreach ( $images as $index => $image ) : ?>
                        <div class="bw-ps-thumb" data-index="<?php echo esc_attr( $index ); ?>">
                            <?php
                            echo wp_get_attachment_image(
                                $image['id'],
                                'medium',
                                false,
                                [
                                    'loading'  => 'lazy',
                                    'decoding' => 'async',
                                ]
                            );
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="bw-ps-main-images">
                <?php foreach ( $images as $index => $image ) : ?>
                    <div class="bw-ps-main-image" data-bw-index="<?php echo esc_attr( $index ); ?>" data-attachment-id="<?php echo esc_attr( $image['id'] ); ?>">
                        <div class="bw-ps-image bw-ps-image-clickable">
                            <?php
                            echo wp_get_attachment_image(
                                $image['id'],
                                'full',
                                false,
                                [
                                    'loading'  => 'lazy',
                                    'decoding' => 'async',
                                ]
                            );
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Responsive Slick layout (hidden on desktop) -->
            <div class="bw-ps-vertical-responsive" style="display: none;">
                <div class="bw-ps-slider-main">
                    <?php foreach ( $images as $index => $image ) : ?>
                        <div class="bw-ps-slide-main" data-bw-index="<?php echo esc_attr( $index ); ?>">
                            <?php
                            echo wp_get_attachment_image(
                                $image['id'],
                                'full',
                                false,
                                [
                                    'loading'  => 'lazy',
                                    'decoding' => 'async',
                                ]
                            );
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="bw-ps-slider-thumbs">
                    <?php foreach ( $images as $index => $image ) : ?>
                        <div class="bw-ps-slide-thumb">
                            <?php
                            echo wp_get_attachment_image(
                                $image['id'],
                                'thumbnail',
                                false,
                                [
                                    'loading'  => 'lazy',
                                    'decoding' => 'async',
                                ]
                            );
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render popup modal
     */
    protected function render_popup_modal( $images, $settings, $popup_title ) {
        // Get popup image size from settings
        $popup_image_size_setting = ! empty( $settings['popup_image_size'] ) ? $settings['popup_image_size'] : 'full';
        $popup_image_size = $this->get_image_size( $popup_image_size_setting );

        ?>
        <div class="bw-ps-popup-overlay" style="display: none;">
            <div class="bw-ps-popup">
                <div class="bw-ps-popup-header">
                    <h3 class="bw-ps-popup-title"><?php echo esc_html( $popup_title ); ?></h3>
                    <button class="bw-ps-popup-close" aria-label="<?php esc_attr_e( 'Close', 'bw-elementor-widgets' ); ?>">
                        ×
                    </button>
                </div>
                <div class="bw-ps-popup-body">
                    <?php foreach ( $images as $index => $image ) : ?>
                        <div class="bw-ps-popup-image" data-index="<?php echo esc_attr( $index ); ?>">
                            <?php
                            echo wp_get_attachment_image(
                                $image['id'],
                                $popup_image_size,
                                false,
                                [
                                    'loading'  => 'lazy',
                                    'decoding' => 'async',
                                ]
                            );
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Build responsive configuration for Slick
     */
    protected function build_responsive_config( $settings ) {
        $responsive = [];

        if ( ! empty( $settings['breakpoints'] ) ) {
            foreach ( $settings['breakpoints'] as $breakpoint ) {
                $config = [
                    'breakpoint'       => absint( $breakpoint['breakpoint'] ),
                    'showArrows'       => $breakpoint['show_arrows'] === 'yes',
                    'settings'         => [
                        'slidesToShow'   => absint( $breakpoint['slides_to_show'] ),
                        'slidesToScroll' => absint( $breakpoint['slides_to_scroll'] ),
                        'arrows'         => false, // Always false, we use custom arrows
                        'dots'           => $breakpoint['show_dots'] === 'yes',
                        'centerMode'     => $breakpoint['center_mode'] === 'yes',
                        'variableWidth'  => $breakpoint['variable_width'] === 'yes',
                    ],
                ];

                // Add slide width if specified and variable width is off
                if ( ! empty( $breakpoint['slide_width'] ) && $breakpoint['variable_width'] !== 'yes' ) {
                    $config['slideWidth'] = absint( $breakpoint['slide_width'] );
                }

                // Add image height settings
                $config['imageHeightMode'] = ! empty( $breakpoint['image_height_mode'] ) ? $breakpoint['image_height_mode'] : 'auto';
                if ( ! empty( $breakpoint['image_height'] ) && $config['imageHeightMode'] !== 'auto' ) {
                    $config['imageHeight'] = absint( $breakpoint['image_height'] );
                }
                if ( ! empty( $breakpoint['image_width'] ) && in_array( $config['imageHeightMode'], [ 'contain', 'cover' ] ) ) {
                    $config['imageWidth'] = absint( $breakpoint['image_width'] );
                }

                $responsive[] = $config;
            }
        }

        return $responsive;
    }
}
