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
        return __( 'BW-UI Presentation Slider', 'bw-elementor-widgets' );
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_script_depends() {
        return [ 'embla-js', 'embla-autoplay-js', 'bw-embla-core-js', 'bw-presentation-slide-script' ];
    }

    public function get_style_depends() {
        return [ 'bw-embla-core-css', 'bw-presentation-slide-style' ];
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
                'description'  => __( 'Free-scroll drag: the slide does not snap to position, scrolls freely', 'bw-elementor-widgets' ),
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
                'default'     => 'start',
                'description' => __( 'Where to align the selected slide inside the carousel viewport', 'bw-elementor-widgets' ),
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
                'label'     => __( 'Image Height Settings', 'bw-elementor-widgets' ),
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
                    'auto'        => __( 'Auto (Original)', 'bw-elementor-widgets' ),
                    'fixed'       => __( 'Fixed Height (Width Auto)', 'bw-elementor-widgets' ),
                    'contain'     => __( 'Contain (Fixed Dimensions)', 'bw-elementor-widgets' ),
                    'cover'       => __( 'Cover (Fixed Dimensions)', 'bw-elementor-widgets' ),
                ],
                'description' => __( 'Control how images adapt to uniform height', 'bw-elementor-widgets' ),
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

        $this->add_control(
            'enable_popup_mobile',
            [
                'label'        => __( 'Enable on Mobile', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => [
                    'enable_popup' => 'yes',
                ],
                'description'  => __( 'Show popup zoom on touch devices (mobile/tablet)', 'bw-elementor-widgets' ),
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
                    '{{WRAPPER}} .bw-ps-dots-list li button' => 'background-color: {{VALUE}};',
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
                    'size' => -25,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-ps-dots-container' => 'bottom: {{SIZE}}{{UNIT}};',
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
            'popup_close_heading',
            [
                'label'     => __( 'Close Button', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'popup_close_typography',
                'selector' => '{{WRAPPER}} .bw-ps-popup-close',
            ]
        );

        $this->add_responsive_control(
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

        // Popup title calcolato solo se il popup è abilitato
        $popup_title = $settings['enable_popup'] === 'yes' ? $this->get_popup_title() : '';

        // Build configuration for JavaScript
        $config = [
            'widgetId'             => $widget_id,
            'layoutMode'           => $settings['layout_mode'],
            'enablePopup'          => $settings['enable_popup'] === 'yes',
            'enablePopupMobile'    => ( $settings['enable_popup_mobile'] ?? '' ) === 'yes',
            'enableCustomCursor'   => $settings['enable_custom_cursor'] === 'yes',
            'popupTitle'           => $popup_title,
            'dotsPosition'         => $settings['dots_position'] ?? 'center',
            'horizontal'           => [
                'infinite'         => $settings['infinite_loop'] === 'yes',
                'autoplay'         => $settings['autoplay'] === 'yes',
                'autoplaySpeed'    => absint( $settings['autoplay_speed'] ),
                'pauseOnHover'     => $settings['pause_on_hover'] === 'yes',
                'dragFree'         => ( $settings['drag_free'] ?? '' ) === 'yes',
                'align'            => $settings['slide_align'] ?? 'start',
                'responsive'       => $this->build_responsive_config( $settings ),
            ],
            'vertical'             => [
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
                $this->render_popup_modal_styles( $settings, $widget_id );
                $this->render_popup_modal( $images, $settings, $popup_title, $widget_id );
            }
            ?>
        </div>
        <?php
    }

    /**
     * Render widget-scoped popup styles.
     *
     * The popup overlay is moved to <body> via JS, so Elementor's {{WRAPPER}}
     * selectors no longer match reliably. We generate widget-scoped CSS tied to
     * the popup overlay data attribute instead.
     */
    protected function render_popup_modal_styles( $settings, $widget_id ) {
        $selector = '.bw-ps-popup-overlay[data-bw-ps-widget-id="' . esc_attr( $widget_id ) . '"]';
        $css      = '';

        $css .= $this->build_popup_typography_css(
            $settings,
            'popup_title_typography',
            $selector . ' .bw-ps-popup-title'
        );

        $css .= $this->build_popup_typography_css(
            $settings,
            'popup_close_typography',
            $selector . ' .bw-ps-popup-close, ' . $selector . ' .bw-ps-popup-close .bw-ps-popup-close-text'
        );

        $css .= $this->build_popup_responsive_dimension_css(
            $settings,
            'popup_close_size',
            $selector . ' .bw-ps-popup-close, ' . $selector . ' .bw-ps-popup-close .bw-ps-popup-close-text',
            'font-size'
        );

        if ( '' === trim( $css ) ) {
            return;
        }

        echo '<style class="bw-ps-popup-inline-style" data-bw-ps-widget-id="' . esc_attr( $widget_id ) . '">';
        echo wp_strip_all_tags( $css );
        echo '</style>';
    }

    /**
     * Build widget-scoped popup typography CSS with responsive tablet/mobile overrides.
     */
    protected function build_popup_typography_css( $settings, $prefix, $selector ) {
        $desktop = $this->build_popup_typography_declarations( $settings, $prefix );
        $tablet  = $this->build_popup_typography_declarations( $settings, $prefix, 'tablet' );
        $mobile  = $this->build_popup_typography_declarations( $settings, $prefix, 'mobile' );
        $css     = '';

        if ( '' !== $desktop ) {
            $css .= $selector . '{' . $desktop . '}';
        }

        if ( '' !== $tablet ) {
            $css .= '@media (max-width: 1024px){' . $selector . '{' . $tablet . '}}';
        }

        if ( '' !== $mobile ) {
            $css .= '@media (max-width: 767px){' . $selector . '{' . $mobile . '}}';
        }

        return $css;
    }

    /**
     * Build CSS declarations for popup typography controls.
     */
    protected function build_popup_typography_declarations( $settings, $prefix, $device = '' ) {
        $suffix       = $device ? '_' . $device : '';
        $declarations = [];

        if ( '' === $device ) {
            $static_map = [
                'font_family'     => 'font-family',
                'font_weight'     => 'font-weight',
                'text_transform'  => 'text-transform',
                'font_style'      => 'font-style',
                'text_decoration' => 'text-decoration',
            ];

            foreach ( $static_map as $setting_key => $css_property ) {
                $value = $settings[ $prefix . '_' . $setting_key ] ?? '';
                if ( '' === $value || null === $value ) {
                    continue;
                }
                $declarations[] = $css_property . ':' . sanitize_text_field( $value );
            }
        }

        $dimension_map = [
            'font_size'      => 'font-size',
            'line_height'    => 'line-height',
            'letter_spacing' => 'letter-spacing',
            'word_spacing'   => 'word-spacing',
        ];

        foreach ( $dimension_map as $setting_key => $css_property ) {
            $formatted = $this->format_popup_dimension_value( $settings[ $prefix . '_' . $setting_key . $suffix ] ?? null );
            if ( '' === $formatted ) {
                continue;
            }
            $declarations[] = $css_property . ':' . $formatted;
        }

        return implode( ';', $declarations );
    }

    /**
     * Build responsive CSS for slider-style popup controls.
     */
    protected function build_popup_responsive_dimension_css( $settings, $control_name, $selector, $property ) {
        $desktop = $this->format_popup_dimension_value( $settings[ $control_name ] ?? null );
        $tablet  = $this->format_popup_dimension_value( $settings[ $control_name . '_tablet' ] ?? null );
        $mobile  = $this->format_popup_dimension_value( $settings[ $control_name . '_mobile' ] ?? null );
        $css     = '';

        if ( '' !== $desktop ) {
            $css .= $selector . '{' . $property . ':' . $desktop . ';}';
        }

        if ( '' !== $tablet ) {
            $css .= '@media (max-width: 1024px){' . $selector . '{' . $property . ':' . $tablet . ';}}';
        }

        if ( '' !== $mobile ) {
            $css .= '@media (max-width: 767px){' . $selector . '{' . $property . ':' . $mobile . ';}}';
        }

        return $css;
    }

    /**
     * Format Elementor responsive dimension values for CSS output.
     */
    protected function format_popup_dimension_value( $value ) {
        if ( ! is_array( $value ) || ! isset( $value['size'] ) || '' === $value['size'] || null === $value['size'] ) {
            return '';
        }

        $unit = $value['unit'] ?? 'px';
        if ( ! in_array( $unit, [ 'px', 'em', 'rem', '%', 'vw', 'vh' ], true ) ) {
            $unit = 'px';
        }

        $size = (float) $value['size'];
        if ( 0.0 === $size && '0' !== (string) $value['size'] ) {
            return '';
        }

        $normalized = rtrim( rtrim( sprintf( '%.4F', $size ), '0' ), '.' );

        return $normalized . $unit;
    }

    /**
     * Get popup title - use product name if in product context
     */
    /**
     * Risolve il prodotto WooCommerce nel contesto corrente.
     * Prima prova la global $product, poi bw_tbl_resolve_product_context_id.
     * Restituisce WC_Product|null.
     */
    protected function get_product_context() {
        global $product;

        if ( $product && is_a( $product, 'WC_Product' ) ) {
            return $product;
        }

        if ( function_exists( 'bw_tbl_resolve_product_context_id' ) && function_exists( 'wc_get_product' ) ) {
            $resolution = bw_tbl_resolve_product_context_id( [ '__widget_class' => __CLASS__ ] );
            $product_id = isset( $resolution['id'] ) ? absint( $resolution['id'] ) : 0;
            if ( $product_id > 0 ) {
                $resolved = wc_get_product( $product_id );
                if ( $resolved && is_a( $resolved, 'WC_Product' ) ) {
                    return $resolved;
                }
            }
        }

        return null;
    }

    protected function get_popup_title() {
        $context_product = $this->get_product_context();

        if ( $context_product ) {
            return $context_product->get_name();
        }

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
            $context_product = $this->get_product_context();

            if ( $context_product ) {
                $attachment_ids = $context_product->get_gallery_image_ids();

                // Include featured image as first
                $featured_id = $context_product->get_image_id();
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
     * Render horizontal layout (Embla Carousel)
     */
    protected function render_horizontal_layout( $images, $settings ) {
        $image_size_setting = ! empty( $settings['image_size'] ) ? $settings['image_size'] : 'full';
        $image_size         = $this->get_image_size( $image_size_setting );
        $dots_position      = $settings['dots_position'] ?? 'center';

        // In loop + center mode Embla renders the LAST slide to the left of the
        // first (center) slide, so it is immediately visible. Mark it eager so
        // the browser fetches it in parallel with the first slides instead of
        // queuing it after all lazy images.
        $is_loop         = ( ( $settings['infinite_loop'] ?? '' ) === 'yes' );
        $is_center_align = ( ( $settings['slide_align'] ?? 'start' ) === 'center' );
        $last_index      = count( $images ) - 1;

        // CSS inline scoped per i breakpoint (slide sizes)
        $this->render_breakpoint_css( $settings );

        ?>
        <div class="bw-ps-horizontal">
            <!-- Embla viewport: overflow:hidden -->
            <div class="bw-embla-viewport bw-ps-embla-viewport">
                <!-- Embla container: display:flex -->
                <div class="bw-embla-container">
                    <?php foreach ( $images as $index => $image ) :
                        // Eager set: solo le slide VISIBILI al caricamento iniziale.
                        // - indice 0 (centro/prima) + indice 1 (destra) sempre eager.
                        // - Con loop+center, l'ultima slide è visibile a SINISTRA → eager
                        //   + fetchpriority="high" così scarica in parallelo con slide 0.
                        // Tenere il set piccolo (2-3 immagini) riduce la contesa di banda
                        // e fa sì che tutte e tre le slide visibili appaiano insieme.
                        $is_first        = ( 0 === $index );
                        $is_loop_center  = $is_loop && $is_center_align && ( $index === $last_index );
                        $is_eager        = ( $index < 2 ) || $is_loop_center;
                        $img_attrs       = [
                            'loading'       => $is_eager ? 'eager' : 'lazy',
                            'decoding'      => $is_first ? 'sync'  : 'async',
                            'fetchpriority' => ( $is_first || $is_loop_center ) ? 'high' : 'auto',
                            'class'         => 'bw-embla-img',
                        ];
                    ?>
                        <div class="bw-embla-slide bw-ps-slide"
                             data-bw-index="<?php echo esc_attr( $index ); ?>"
                             data-attachment-id="<?php echo esc_attr( $image['id'] ); ?>">
                            <div class="bw-ps-image bw-ps-image-clickable">
                                <?php echo wp_get_attachment_image( $image['id'], $image_size, false, $img_attrs ); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bw-ps-arrows-container">
                <button class="bw-ps-arrow bw-ps-arrow-prev" aria-label="<?php esc_attr_e( 'Previous', 'bw-elementor-widgets' ); ?>">&#8592;</button>
                <button class="bw-ps-arrow bw-ps-arrow-next" aria-label="<?php esc_attr_e( 'Next', 'bw-elementor-widgets' ); ?>">&#8594;</button>
            </div>

            <!-- Container dots: BWEmblaCore inietta la <ul> qui -->
            <div class="bw-ps-dots-container bw-ps-dots-<?php echo esc_attr( $dots_position ); ?>"></div>
        </div>
        <?php
    }

    /**
     * Genera un blocco <style> inline scoped per le slide sizes per breakpoint.
     * Embla con watchResize:true si ri-misura automaticamente al resize CSS.
     */
    protected function render_breakpoint_css( $settings ) {
        $breakpoints = ! empty( $settings['breakpoints'] ) ? $settings['breakpoints'] : [];
        if ( empty( $breakpoints ) ) {
            return;
        }

        $widget_id = $this->get_id();
        $selector  = '.elementor-element-' . esc_attr( $widget_id ) . ' .bw-ps-slide';

        $css = '<style>';
        foreach ( $breakpoints as $bp ) {
            $bp_px          = absint( $bp['breakpoint'] );
            $slides_to_show = max( 1, absint( $bp['slides_to_show'] ?? 1 ) );
            $variable_width = ( $bp['variable_width'] ?? '' ) === 'yes';
            $slide_width    = absint( $bp['slide_width'] ?? 0 );

            if ( $bp_px <= 0 ) {
                continue;
            }

            if ( $variable_width ) {
                $slide_size = 'auto';
            } elseif ( $slide_width > 0 ) {
                $slide_size = $slide_width . 'px';
            } elseif ( $slides_to_show > 1 ) {
                $slide_size = 'calc(100% / ' . $slides_to_show . ')';
            } else {
                $slide_size = '100%';
            }

            $css .= '@media (max-width:' . $bp_px . 'px){';
            $css .= $selector . '{flex:0 0 ' . $slide_size . ';}';
            $css .= '}';
        }
        $css .= '</style>';

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $css;
    }

    /**
     * Render vertical layout
     */
    protected function render_vertical_layout( $images, $settings ) {
        ?>
        <div class="bw-ps-vertical">
            <?php if ( $settings['enable_thumbnails'] === 'yes' ) : ?>
                <div class="bw-ps-thumbnails">
                    <?php foreach ( $images as $index => $image ) :
                        $is_first = ( 0 === $index );
                        $is_eager = ( $index < 5 );
                    ?>
                        <div class="bw-ps-thumb" data-bw-index="<?php echo esc_attr( $index ); ?>">
                            <?php
                            echo wp_get_attachment_image(
                                $image['id'],
                                'medium',
                                false,
                                [
                                    'loading'       => $is_eager ? 'eager' : 'lazy',
                                    'decoding'      => $is_first ? 'sync'  : 'async',
                                    'fetchpriority' => $is_first ? 'high'  : 'auto',
                                    'class'         => 'bw-embla-img',
                                ]
                            );
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="bw-ps-main-images">
                <?php foreach ( $images as $index => $image ) :
                    $is_first = ( 0 === $index );
                    $is_eager = ( $index < 5 );
                ?>
                    <div class="bw-ps-main-image" data-bw-index="<?php echo esc_attr( $index ); ?>" data-attachment-id="<?php echo esc_attr( $image['id'] ); ?>">
                        <div class="bw-ps-image bw-ps-image-clickable">
                            <?php
                            echo wp_get_attachment_image(
                                $image['id'],
                                'full',
                                false,
                                [
                                    'loading'       => $is_eager ? 'eager' : 'lazy',
                                    'decoding'      => $is_first ? 'sync'  : 'async',
                                    'fetchpriority' => $is_first ? 'high'  : 'auto',
                                    'class'         => 'bw-embla-img',
                                ]
                            );
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Responsive Embla layout (nascosto su desktop) -->
            <div class="bw-ps-vertical-responsive" style="display: none;">
                <!-- Main slider -->
                <div class="bw-embla-viewport bw-ps-main-viewport">
                    <div class="bw-embla-container">
                        <?php foreach ( $images as $index => $image ) :
                            $is_first  = ( 0 === $index );
                            $is_eager  = ( $index < 5 );
                            $img_attrs = [
                                'loading'       => $is_eager ? 'eager' : 'lazy',
                                'decoding'      => $is_first ? 'sync'  : 'async',
                                'fetchpriority' => $is_first ? 'high'  : 'auto',
                                'class'         => 'bw-embla-img',
                            ];
                        ?>
                            <div class="bw-embla-slide bw-ps-slide-main"
                                 data-bw-index="<?php echo esc_attr( $index ); ?>">
                                <?php echo wp_get_attachment_image( $image['id'], 'full', false, $img_attrs ); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Thumbnail slider -->
                <div class="bw-embla-viewport bw-ps-thumbs-viewport">
                    <div class="bw-embla-container">
                        <?php foreach ( $images as $index => $image ) : ?>
                            <div class="bw-embla-slide bw-ps-slide-thumb"
                                 data-bw-index="<?php echo esc_attr( $index ); ?>">
                                <?php
                                echo wp_get_attachment_image(
                                    $image['id'],
                                    'thumbnail',
                                    false,
                                    [
                                        'loading'  => 'lazy',
                                        'decoding' => 'async',
                                        'class'    => 'bw-embla-img',
                                    ]
                                );
                                ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render popup modal
     */
    protected function render_popup_modal( $images, $settings, $popup_title, $widget_id ) {
        // Get popup image size from settings
        $popup_image_size_setting = ! empty( $settings['popup_image_size'] ) ? $settings['popup_image_size'] : 'full';
        $popup_image_size = $this->get_image_size( $popup_image_size_setting );

        ?>
        <div class="bw-ps-popup-overlay" data-bw-ps-widget-id="<?php echo esc_attr( $widget_id ); ?>" aria-hidden="true">
            <div class="bw-ps-popup">
                <div class="bw-ps-popup-header">
                    <h3 class="bw-ps-popup-title"><?php echo esc_html( $popup_title ); ?></h3>
                    <button class="bw-ps-popup-close" type="button" aria-label="<?php esc_attr_e( 'Close', 'bw-elementor-widgets' ); ?>">
                        <span class="bw-ps-popup-close-text"><?php esc_html_e( 'Close', 'bw-elementor-widgets' ); ?></span>
                    </button>
                </div>
                <div class="bw-ps-popup-body">
                    <?php foreach ( $images as $index => $image ) : ?>
                        <div class="bw-ps-popup-image">
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
     * Build responsive configuration for JS (Embla).
     * slidesToShow è gestito da CSS inline in render_breakpoint_css().
     */
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
                    'showArrows'      => $breakpoint['show_arrows'] === 'yes',
                    'showDots'        => $breakpoint['show_dots'] === 'yes',
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
}
