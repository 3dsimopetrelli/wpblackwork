<?php
use Elementor\Controls_Manager;
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
		return array( 'blackwork' );
	}

	public function get_script_depends() {
		return array( 'embla-js', 'embla-autoplay-js', 'bw-embla-core-js', 'bw-presentation-slide-script' );
	}

	public function get_style_depends() {
		return array( 'bw-embla-core-css', 'bw-presentation-slide-style' );
	}

	protected function register_controls() {
		// Content → General Section
		$this->start_controls_section(
			'section_general',
			array(
				'label' => __( 'General', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'layout_mode',
			array(
				'label'   => __( 'Layout Mode', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'horizontal' => __( 'Horizontal', 'bw-elementor-widgets' ),
					'vertical'   => __( 'Vertical', 'bw-elementor-widgets' ),
				),
				'default' => 'horizontal',
			)
		);

		$this->add_control(
			'images_source',
			array(
				'label'   => __( 'Images Source', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'custom' => __( 'Custom Gallery', 'bw-elementor-widgets' ),
					'query'  => __( 'Query (Product Gallery)', 'bw-elementor-widgets' ),
				),
				'default' => 'custom',
			)
		);

		$this->add_control(
			'gallery',
			array(
				'label'      => __( 'Add Images', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::GALLERY,
				'default'    => array(),
				'show_label' => false,
				'condition'  => array(
					'images_source' => 'custom',
				),
			)
		);

		$this->end_controls_section();

		// Slider → General (Horizontal)
		$this->start_controls_section(
			'section_slider_general',
			array(
				'label'     => __( 'Slider Settings', 'bw-elementor-widgets' ),
				'condition' => array(
					'layout_mode' => 'horizontal',
				),
			)
		);

		$this->add_control(
			'infinite_loop',
			array(
				'label'        => __( 'Infinite Loop', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'        => __( 'Autoplay', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'autoplay_speed',
			array(
				'label'     => __( 'Autoplay Speed (ms)', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 3000,
				'min'       => 1000,
				'max'       => 10000,
				'step'      => 500,
				'condition' => array(
					'autoplay' => 'yes',
				),
			)
		);

		$this->add_control(
			'pause_on_hover',
			array(
				'label'        => __( 'Pause on Hover', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'autoplay' => 'yes',
				),
			)
		);

		$this->add_control(
			'drag_free',
			array(
				'label'        => __( 'Drag Free', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'Free-scroll drag: the slide does not snap to position, scrolls freely', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'include_featured_image',
			array(
				'label'        => __( 'Include Featured Image', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'images_source' => 'query',
				),
			)
		);

		$this->add_control(
			'touch_drag',
			array(
				'label'        => __( 'Touch Drag (Mobile & Tablet)', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => __( 'Allow swiping with fingers to navigate slides on touch devices.', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'mouse_drag',
			array(
				'label'        => __( 'Mouse / Trackpad Drag (Desktop)', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => __( 'Allow click-drag with a mouse and horizontal trackpad swipe to navigate slides on desktop. Horizontal trackpad gestures are intercepted to prevent accidental browser back/forward navigation.', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'slide_align',
			array(
				'label'       => __( 'Slide Alignment', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => array(
					'start'  => __( 'Start (default)', 'bw-elementor-widgets' ),
					'center' => __( 'Center', 'bw-elementor-widgets' ),
					'end'    => __( 'End', 'bw-elementor-widgets' ),
				),
				'default'     => 'start',
				'description' => __( 'Where to align the selected slide inside the carousel viewport', 'bw-elementor-widgets' ),
			)
		);

		$this->end_controls_section();

		// Slider → Breakpoints (Horizontal)
		$this->start_controls_section(
			'section_breakpoints',
			array(
				'label'     => __( 'Responsive Breakpoints', 'bw-elementor-widgets' ),
				'condition' => array(
					'layout_mode' => 'horizontal',
				),
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'breakpoint',
			array(
				'label'   => __( 'Breakpoint (px)', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 1024,
			)
		);

		$repeater->add_control(
			'slides_to_show',
			array(
				'label'   => __( 'Slides to Show', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 1,
				'min'     => 1,
				'max'     => 10,
			)
		);

		$repeater->add_control(
			'slides_to_scroll',
			array(
				'label'   => __( 'Slides to Scroll', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 1,
				'min'     => 1,
				'max'     => 10,
			)
		);

		$repeater->add_control(
			'show_arrows',
			array(
				'label'        => __( 'Show Arrows', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$repeater->add_control(
			'show_dots',
			array(
				'label'        => __( 'Show Dots', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$repeater->add_control(
			'center_mode',
			array(
				'label'        => __( 'Center Mode', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$repeater->add_control(
			'variable_width',
			array(
				'label'        => __( 'Variable Width', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'Use original image width', 'bw-elementor-widgets' ),
			)
		);

		$repeater->add_control(
			'slide_width',
			array(
				'label'       => __( 'Slide Width (px)', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => '',
				'min'         => 100,
				'max'         => 2000,
				'step'        => 10,
				'placeholder' => __( 'Auto', 'bw-elementor-widgets' ),
				'description' => __( 'Set fixed width for slides (leave empty for auto)', 'bw-elementor-widgets' ),
				'condition'   => array(
					'variable_width!' => 'yes',
				),
			)
		);

		$repeater->add_control(
			'image_height_heading',
			array(
				'label'     => __( 'Image Height Settings', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$repeater->add_control(
			'image_height_mode',
			array(
				'label'       => __( 'Height Mode', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'auto',
				'options'     => array(
					'auto'    => __( 'Auto (Original)', 'bw-elementor-widgets' ),
					'fixed'   => __( 'Fixed Height (Width Auto)', 'bw-elementor-widgets' ),
					'contain' => __( 'Contain (Fixed Dimensions)', 'bw-elementor-widgets' ),
					'cover'   => __( 'Cover (Fixed Dimensions)', 'bw-elementor-widgets' ),
				),
				'description' => __( 'Control how images adapt to uniform height', 'bw-elementor-widgets' ),
			)
		);

		$repeater->add_control(
			'image_height',
			array(
				'label'      => __( 'Image Height', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array(
					'px' => array(
						'min'  => 100,
						'max'  => 1500,
						'step' => 10,
					),
					'vh' => array(
						'min'  => 10,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 600,
					'unit' => 'px',
				),
				'condition'  => array(
					'image_height_mode!' => 'auto',
				),
			)
		);

		$repeater->add_control(
			'image_width',
			array(
				'label'      => __( 'Image Width', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min'  => 100,
						'max'  => 2000,
						'step' => 10,
					),
					'%'  => array(
						'min'  => 10,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 100,
					'unit' => '%',
				),
				'condition'  => array(
					'image_height_mode' => array( 'contain', 'cover' ),
				),
			)
		);

		$this->add_control(
			'breakpoints',
			array(
				'label'       => __( 'Breakpoints', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array(
						'breakpoint'       => 1024,
						'slides_to_show'   => 2,
						'slides_to_scroll' => 1,
						'show_arrows'      => 'yes',
						'show_dots'        => '',
					),
					array(
						'breakpoint'       => 767,
						'slides_to_show'   => 1,
						'slides_to_scroll' => 1,
						'show_arrows'      => '',
						'show_dots'        => 'yes',
					),
				),
				'title_field' => 'Breakpoint: {{{ breakpoint }}}px',
			)
		);

		$this->end_controls_section();

		// Vertical → Desktop
		$this->start_controls_section(
			'section_vertical_desktop',
			array(
				'label'     => __( 'Vertical Desktop', 'bw-elementor-widgets' ),
				'condition' => array(
					'layout_mode' => 'vertical',
				),
			)
		);

		$this->add_control(
			'enable_thumbnails',
			array(
				'label'        => __( 'Enable Thumbnails', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'smooth_scroll',
			array(
				'label'        => __( 'Smooth Scroll (Elevator)', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		// Vertical → Responsive
		$this->start_controls_section(
			'section_vertical_responsive',
			array(
				'label'     => __( 'Vertical Responsive', 'bw-elementor-widgets' ),
				'condition' => array(
					'layout_mode' => 'vertical',
				),
			)
		);

		$this->add_control(
			'enable_responsive_mode',
			array(
				'label'        => __( 'Enable Responsive Mode', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => __( 'Switch to Embla slider layout on mobile/tablet', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'responsive_breakpoint',
			array(
				'label'     => __( 'Breakpoint (px)', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 1024,
				'min'       => 320,
				'max'       => 1920,
				'condition' => array(
					'enable_responsive_mode' => 'yes',
				),
			)
		);

		$this->add_control(
			'thumbs_slides_to_show',
			array(
				'label'     => __( 'Thumbnails Slides to Show', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 4,
				'min'       => 2,
				'max'       => 10,
				'condition' => array(
					'enable_responsive_mode' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		// Popup Settings - Only Enable/Disable
		$this->start_controls_section(
			'section_popup',
			array(
				'label' => __( 'Popup Settings', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'enable_popup',
			array(
				'label'        => __( 'Enable Popup', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'enable_popup_mobile',
			array(
				'label'        => __( 'Enable on Mobile', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array(
					'enable_popup' => 'yes',
				),
				'description'  => __( 'Show popup zoom on touch devices (mobile/tablet)', 'bw-elementor-widgets' ),
			)
		);

		$this->end_controls_section();

		// ========================================
		// STYLE TAB
		// ========================================

		// Style → Images
		$this->start_controls_section(
			'section_style_images',
			array(
				'label' => __( 'Images', 'bw-elementor-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		BW_Widget_Helper::add_dimensions_control(
			$this,
			'image_border_radius',
			__( 'Border Radius', 'bw-elementor-widgets' ),
			'{{WRAPPER}} .bw-ps-image img',
			'border-radius',
			array( 'px', '%' )
		);

		$this->add_responsive_control(
			'slides_spacing',
			array(
				'label'      => __( 'Spacing Between Slides', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 10,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-ps-slide' => 'padding-left: {{SIZE}}{{UNIT}}; padding-right: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'layout_mode' => 'horizontal',
				),
			)
		);

		$this->add_control(
			'image_size',
			array(
				'label'       => __( 'Image Size', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'full',
				'options'     => array(
					'thumbnail'    => __( 'Thumbnail (150×150)', 'bw-elementor-widgets' ),
					'medium'       => __( 'Medium (300×300)', 'bw-elementor-widgets' ),
					'medium_large' => __( 'Medium Large (768×auto)', 'bw-elementor-widgets' ),
					'large'        => __( 'Large (1024×1024)', 'bw-elementor-widgets' ),
					'custom_1200'  => __( 'Custom (1200×auto)', 'bw-elementor-widgets' ),
					'custom_1500'  => __( 'Custom (1500×auto)', 'bw-elementor-widgets' ),
					'full'         => __( 'Full Size (Original)', 'bw-elementor-widgets' ),
				),
				'condition'   => array(
					'layout_mode' => 'horizontal',
				),
				'description' => __( 'Select image size for gallery slides', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'popup_image_size',
			array(
				'label'       => __( 'Popup Image Size', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'full',
				'options'     => array(
					'thumbnail'    => __( 'Thumbnail (150×150)', 'bw-elementor-widgets' ),
					'medium'       => __( 'Medium (300×300)', 'bw-elementor-widgets' ),
					'medium_large' => __( 'Medium Large (768×auto)', 'bw-elementor-widgets' ),
					'large'        => __( 'Large (1024×1024)', 'bw-elementor-widgets' ),
					'custom_1200'  => __( 'Custom (1200×auto)', 'bw-elementor-widgets' ),
					'custom_1500'  => __( 'Custom (1500×auto)', 'bw-elementor-widgets' ),
					'custom_2000'  => __( 'Custom (2000×auto)', 'bw-elementor-widgets' ),
					'full'         => __( 'Full Size (Original)', 'bw-elementor-widgets' ),
				),
				'condition'   => array(
					'layout_mode'  => 'horizontal',
					'enable_popup' => 'yes',
				),
				'description' => __( 'Select image size for popup gallery', 'bw-elementor-widgets' ),
			)
		);

		$this->end_controls_section();

		// Style → Navigation (Horizontal)
		$this->start_controls_section(
			'section_style_navigation',
			array(
				'label'     => __( 'Navigation Arrows', 'bw-elementor-widgets' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'layout_mode' => 'horizontal',
				),
			)
		);

		$this->add_control(
			'arrow_color',
			array(
				'label'     => __( 'Arrow Color', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#000000',
				'selectors' => array(
					'{{WRAPPER}} .bw-ps-arrow' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_size',
			array(
				'label'      => __( 'Arrow Size', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 10,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 24,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-ps-arrow svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'arrow_padding',
			array(
				'label'      => __( 'Arrow Padding', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 50,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 10,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-ps-arrow' => 'padding: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'arrows_vertical_offset',
			array(
				'label'      => __( 'Vertical Offset from Bottom', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => -50,
						'max'  => 200,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 20,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-ps-arrows-container' => 'bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'arrows_horizontal_offset',
			array(
				'label'      => __( 'Horizontal Offset from Right', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 200,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 20,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-ps-arrows-container' => 'right: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'arrows_gap',
			array(
				'label'      => __( 'Gap Between Arrows', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 50,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 10,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-ps-arrows-container' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// Style → Dots (Pagination)
		$this->start_controls_section(
			'section_style_dots',
			array(
				'label'     => __( 'Dots (Pagination)', 'bw-elementor-widgets' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'layout_mode' => 'horizontal',
				),
			)
		);

		$this->add_control(
			'dots_color',
			array(
				'label'     => __( 'Dots Color', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(0, 0, 0, 0.3)',
				'selectors' => array(
					'{{WRAPPER}} .bw-ps-dots-list li button' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'dots_active_color',
			array(
				'label'     => __( 'Active Dot Color', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#000000',
				'selectors' => array(
					'{{WRAPPER}} .bw-ps-dots-list li.is-active button' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'dots_size',
			array(
				'label'      => __( 'Dots Size', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 4,
						'max'  => 30,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 10,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-ps-dots-list li button' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'dots_position',
			array(
				'label'   => __( 'Position', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'left'   => __( 'Left', 'bw-elementor-widgets' ),
					'center' => __( 'Center', 'bw-elementor-widgets' ),
					'right'  => __( 'Right', 'bw-elementor-widgets' ),
				),
				'default' => 'center',
			)
		);

		$this->add_control(
			'dots_vertical_offset',
			array(
				'label'      => __( 'Vertical Offset from Bottom', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => -50,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => -25,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-ps-dots-container' => 'bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// Style → Vertical Thumbnails
		$this->start_controls_section(
			'section_style_vertical_thumbs',
			array(
				'label'     => __( 'Vertical Thumbnails', 'bw-elementor-widgets' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'layout_mode' => 'vertical',
				),
			)
		);

		$this->add_responsive_control(
			'thumbnails_width',
			array(
				'label'      => __( 'Thumbnails Width', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 80,
						'max'  => 400,
						'step' => 10,
					),
				),
				'default'    => array(
					'size' => 150,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-ps-thumbnails' => 'flex: 0 0 {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// Style → Custom Cursor
		$this->start_controls_section(
			'section_style_cursor',
			array(
				'label' => __( 'Custom Cursor', 'bw-elementor-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'enable_custom_cursor',
			array(
				'label'        => __( 'Enable Custom Cursor', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings  = $this->get_settings_for_display();
		$widget_id = $this->get_id();

		// Get images based on source
		$images = $this->get_images_for_render( $settings );

		if ( empty( $images ) ) {
			echo '<p>' . esc_html__( 'No images available.', 'bw-elementor-widgets' ) . '</p>';
			return;
		}

		// Compute popup title only when popup is enabled
		$popup_title = $settings['enable_popup'] === 'yes' ? $this->get_popup_title() : '';

		// Build configuration for JavaScript
		$config = array(
			'widgetId'           => $widget_id,
			'layoutMode'         => $settings['layout_mode'],
			'enablePopup'        => $settings['enable_popup'] === 'yes',
			'enablePopupMobile'  => ( $settings['enable_popup_mobile'] ?? '' ) === 'yes',
			'enableCustomCursor' => $settings['enable_custom_cursor'] === 'yes',
			'popupTitle'         => $popup_title,
			'dotsPosition'       => $settings['dots_position'] ?? 'center',
			'horizontal'         => array(
				'infinite'        => $settings['infinite_loop'] === 'yes',
				'autoplay'        => $settings['autoplay'] === 'yes',
				'autoplaySpeed'   => absint( $settings['autoplay_speed'] ),
				'pauseOnHover'    => $settings['pause_on_hover'] === 'yes',
				'dragFree'        => ( $settings['drag_free'] ?? '' ) === 'yes',
				'enableTouchDrag' => ( $settings['touch_drag'] ?? 'yes' ) === 'yes',
				'enableMouseDrag' => ( $settings['mouse_drag'] ?? 'yes' ) === 'yes',
				'align'           => $settings['slide_align'] ?? 'start',
				'responsive'      => $this->build_responsive_config( $settings ),
			),
			'vertical'           => array(
				'smoothScroll'         => $settings['smooth_scroll'] === 'yes',
				'enableResponsive'     => $settings['enable_responsive_mode'] === 'yes',
				'responsiveBreakpoint' => absint( $settings['responsive_breakpoint'] ),
				'thumbsSlidesToShow'   => absint( $settings['thumbs_slides_to_show'] ),
			),
		);

		$this->add_render_attribute(
			'wrapper',
			array(
				'class'              => 'bw-ps-wrapper',
				'data-widget-id'     => $widget_id,
				'data-layout-mode'   => $settings['layout_mode'],
				'data-config'        => wp_json_encode( $config ),
				'data-dots-position' => $settings['dots_position'] ?? 'center',
			)
		);

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
	 * Resolve the current WooCommerce product context.
	 * Tries global $product first, then bw_tbl_resolve_product_context_id.
	 * Returns WC_Product|null.
	 */
	protected function get_product_context() {
		global $product;

		if ( $product && is_a( $product, 'WC_Product' ) ) {
			return $product;
		}

		if ( function_exists( 'bw_tbl_resolve_product_context_id' ) && function_exists( 'wc_get_product' ) ) {
			$resolution = bw_tbl_resolve_product_context_id( array( '__widget_class' => __CLASS__ ) );
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

	/**
	 * Get popup title — uses product name when in a product context.
	 */
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
		$images = array();

		if ( $settings['images_source'] === 'custom' && ! empty( $settings['gallery'] ) ) {
			foreach ( $settings['gallery'] as $image ) {
				$images[] = array(
					'id'  => $image['id'],
					'url' => $image['url'],
				);
			}
		} elseif ( $settings['images_source'] === 'query' ) {
			$context_product = $this->get_product_context();

			if ( $context_product ) {
				$attachment_ids = array();
				$gallery_ids    = $context_product->get_gallery_image_ids();
				$gallery_ids    = is_array( $gallery_ids ) ? array_map( 'absint', $gallery_ids ) : array();

				if ( ( $settings['include_featured_image'] ?? 'yes' ) === 'yes' ) {
					$featured_id = absint( $context_product->get_image_id() );
					if ( $featured_id ) {
						$attachment_ids[] = $featured_id;
					}
				}

				foreach ( $gallery_ids as $gallery_id ) {
					if ( $gallery_id ) {
						$attachment_ids[] = $gallery_id;
					}
				}

				$attachment_ids = array_values( array_filter( array_unique( $attachment_ids ) ) );
				foreach ( $attachment_ids as $attachment_id ) {
					$images[] = array(
						'id'  => $attachment_id,
						'url' => wp_get_attachment_url( $attachment_id ),
					);
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
				return array( 1200, 0 ); // 1200×auto
			case 'custom_1500':
				return array( 1500, 0 ); // 1500×auto
			case 'custom_2000':
				return array( 2000, 0 ); // 2000×auto
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

		// Inline CSS scoped to widget ID for breakpoint slide sizes
		$this->render_breakpoint_css( $settings );

		?>
		<div class="bw-ps-horizontal">
			<!-- Embla viewport: overflow:hidden -->
			<div class="bw-embla-viewport bw-ps-embla-viewport">
				<!-- Embla container: display:flex -->
				<div class="bw-embla-container">
					<?php
					foreach ( $images as $index => $image ) :
						// Eager set: only slides VISIBLE on initial load.
						// - index 0 (center/first) + index 1 (right) are always eager.
						// - With loop+center, the last slide is visible on the LEFT → eager
						// + fetchpriority="high" so it downloads in parallel with slide 0.
						// All other slides use render_lazy_img(): NO src, only data-bw-src.
						// JS activates download via _loadAdjacentSlides() when slides are "nearby".
						// We do NOT use loading="lazy" for other slides because overflow:hidden
						// does not reliably prevent download in all browsers, causing sequential
						// loading of all slides before the last one (visible on the left) arrives.
						$is_first       = ( 0 === $index );
						$is_loop_center = $is_loop && $is_center_align && ( $index === $last_index );
						$is_eager       = ( $index < 2 ) || $is_loop_center;
						?>
						<div class="bw-embla-slide bw-ps-slide"
							data-bw-index="<?php echo esc_attr( $index ); ?>"
							data-attachment-id="<?php echo esc_attr( $image['id'] ); ?>">
							<div class="bw-ps-image bw-ps-image-clickable">
								<?php
								if ( $is_eager ) :
									echo wp_get_attachment_image(
										$image['id'],
										$image_size,
										false,
										array(
											'loading'  => 'eager',
											'decoding' => $is_first ? 'sync' : 'async',
											'fetchpriority' => ( $is_first || $is_loop_center ) ? 'high' : 'auto',
											'class'    => 'bw-embla-img',
										)
									);
								else :
                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									echo $this->render_lazy_img( $image['id'], $image_size );
								endif;
								?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="bw-ps-arrows-container">
				<button class="bw-ps-arrow bw-ps-arrow-prev" aria-label="<?php esc_attr_e( 'Previous', 'bw-elementor-widgets' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M6 8L2 12L6 16"/><path d="M2 12H22"/></svg>
				</button>
				<button class="bw-ps-arrow bw-ps-arrow-next" aria-label="<?php esc_attr_e( 'Next', 'bw-elementor-widgets' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M18 8L22 12L18 16"/><path d="M2 12H22"/></svg>
				</button>
			</div>

			<!-- Container dots: BWEmblaCore inietta la <ul> qui -->
			<div class="bw-ps-dots-container bw-ps-dots-<?php echo esc_attr( $dots_position ); ?>"></div>
		</div>
		<?php
	}

	/**
	 * Emit a scoped inline <style> block for breakpoint rules:
	 * slide sizes, arrow visibility, and dots visibility.
	 *
	 * WHY CSS INSTEAD OF JS for arrows/dots:
	 * The old JS approach (_updateArrowsVisibility) read $(window).width() in a
	 * resize listener with 150 ms debounce. In the Elementor editor this creates a
	 * race condition: the widget re-initialises via element_ready and calls the
	 * function while the iframe is still transitioning, reading the wrong width.
	 * Result: unstable state — arrows visible when they should be hidden (or vice
	 * versa), requiring a publish + refresh to fix.
	 *
	 * With CSS @media max-width, visibility responds instantly to the viewport,
	 * with no JS, no debounce, no race condition — and it works in the editor too.
	 *
	 * EMISSION ORDER: largest breakpoint first, smallest last.
	 *
	 * @media max-width rules overlap (e.g. max-width:400px is a subset of
	 * max-width:2000px). Descending order ensures the smallest breakpoint appears
	 * LATER in the stylesheet and correctly overrides larger ones thanks to normal
	 * CSS cascade (same specificity — last declaration wins).
	 */
	protected function render_breakpoint_css( $settings ) {
		$breakpoints = ! empty( $settings['breakpoints'] ) ? $settings['breakpoints'] : array();
		if ( empty( $breakpoints ) ) {
			return;
		}

		// Descending order: largest → smallest, for correct CSS cascade.
		usort(
			$breakpoints,
			function ( $a, $b ) {
				return absint( $b['breakpoint'] ) - absint( $a['breakpoint'] );
			}
		);

		$widget_id  = $this->get_id();
		$el_prefix  = '.elementor-element-' . esc_attr( $widget_id );
		$sel_slide  = $el_prefix . ' .bw-ps-slide';
		$sel_arrows = $el_prefix . ' .bw-ps-arrows-container';
		$sel_dots   = $el_prefix . ' .bw-ps-dots-container';

		$css = '<style>';
		foreach ( $breakpoints as $bp ) {
			$bp_px          = absint( $bp['breakpoint'] );
			$slides_to_show = max( 1, absint( $bp['slides_to_show'] ?? 1 ) );
			$variable_width = ( $bp['variable_width'] ?? '' ) === 'yes';
			$slide_width    = absint( $bp['slide_width'] ?? 0 );
			// Switcher OFF → '' (empty string); ON → 'yes'.
			// Default 'yes' for show_arrows (arrows visible when not set).
			// Default ''  for show_dots   (dots hidden when not set).
			$show_arrows = ( $bp['show_arrows'] ?? 'yes' ) === 'yes';
			$show_dots   = ( $bp['show_dots'] ?? '' ) === 'yes';

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
			$css .= $sel_slide . '{flex:0 0 ' . $slide_size . ';}';
			$css .= $sel_arrows . '{display:' . ( $show_arrows ? 'flex' : 'none' ) . ';}';
			$css .= $sel_dots . '{display:' . ( $show_dots ? 'flex' : 'none' ) . ';}';
			$css .= '}';
		}
		$css .= '</style>';

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $css;
	}

	/**
	 * Emit scoped CSS for the vertical layout's responsive breakpoint.
	 *
	 * The static CSS in bw-presentation-slide.css hard-codes 1024px for the
	 * vertical desktop→responsive switch. If the user configures a different
	 * breakpoint, the static CSS and JS fall out of sync: the CSS shows the
	 * responsive layout (without Embla initialised) at sizes the JS doesn't
	 * expect. This method emits scoped rules using the correct breakpoint with
	 * higher specificity than the static rule (.elementor-element-X vs generic
	 * selector), overriding the hard-coded 1024px.
	 *
	 * Emits both max-width (show responsive) and min-width (show desktop) to
	 * cover all cases, including scaling down from a 27" monitor to mobile.
	 */
	protected function render_vertical_responsive_css( $settings ) {
		$breakpoint = absint( $settings['responsive_breakpoint'] ?? 1024 );
		if ( $breakpoint <= 0 ) {
			return;
		}

		$widget_id  = $this->get_id();
		$prefix     = '.elementor-element-' . esc_attr( $widget_id );
		$sel_thumbs = $prefix . ' .bw-ps-vertical .bw-ps-thumbnails';
		$sel_main   = $prefix . ' .bw-ps-vertical .bw-ps-main-images';
		$sel_resp   = $prefix . ' .bw-ps-vertical .bw-ps-vertical-responsive';

		$enable_responsive = ( $settings['enable_responsive_mode'] ?? '' ) === 'yes';

		$css = '<style>';

		if ( $enable_responsive ) {
			// Below breakpoint: show responsive layout, hide desktop layout
			$css .= '@media (max-width:' . $breakpoint . 'px){';
			$css .= $sel_thumbs . ',' . $sel_main . '{display:none!important;}';
			$css .= $sel_resp . '{display:block!important;}';
			$css .= '}';
			// Above breakpoint: show desktop layout, hide responsive layout
			// (overrides the hard-coded 1024px in static CSS when bp < 1024)
			$css .= '@media (min-width:' . ( $breakpoint + 1 ) . 'px){';
			$css .= $sel_resp . '{display:none!important;}';
			$css .= '}';
		} else {
			// Responsive mode disabled: keep the responsive div permanently hidden,
			// even at ≤1024px where the static CSS would show it with display:block !important
			$css .= $sel_resp . '{display:none!important;}';
		}

		$css .= '</style>';

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $css;
	}

	/**
	 * Render vertical layout
	 */
	protected function render_vertical_layout( $images, $settings ) {
		$this->render_vertical_responsive_css( $settings );
		?>
		<div class="bw-ps-vertical">
			<?php if ( $settings['enable_thumbnails'] === 'yes' ) : ?>
				<div class="bw-ps-thumbnails">
					<?php
					foreach ( $images as $index => $image ) :
						$is_first = ( 0 === $index );
						$is_eager = ( $index < 5 );
						?>
						<div class="bw-ps-thumb" data-bw-index="<?php echo esc_attr( $index ); ?>">
							<?php
							echo wp_get_attachment_image(
								$image['id'],
								'medium',
								false,
								array(
									'loading'       => $is_eager ? 'eager' : 'lazy',
									'decoding'      => $is_first ? 'sync' : 'async',
									'fetchpriority' => $is_first ? 'high' : 'auto',
									'class'         => 'bw-embla-img',
								)
							);
							?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="bw-ps-main-images">
				<?php
				foreach ( $images as $index => $image ) :
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
								array(
									'loading'       => $is_eager ? 'eager' : 'lazy',
									'decoding'      => $is_first ? 'sync' : 'async',
									'fetchpriority' => $is_first ? 'high' : 'auto',
									'class'         => 'bw-embla-img',
								)
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
						<?php
						foreach ( $images as $index => $image ) :
							$is_first  = ( 0 === $index );
							$is_eager  = ( $index < 5 );
							$img_attrs = array(
								'loading'       => $is_eager ? 'eager' : 'lazy',
								'decoding'      => $is_first ? 'sync' : 'async',
								'fetchpriority' => $is_first ? 'high' : 'auto',
								'class'         => 'bw-embla-img',
							);
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
									array(
										'loading'  => 'lazy',
										'decoding' => 'async',
										'class'    => 'bw-embla-img',
									)
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
	protected function render_popup_modal( $images, $settings, $popup_title ) {
		// Get popup image size from settings
		$popup_image_size_setting = ! empty( $settings['popup_image_size'] ) ? $settings['popup_image_size'] : 'full';
		$popup_image_size         = $this->get_image_size( $popup_image_size_setting );

		?>
		<div class="bw-ps-popup-overlay" aria-hidden="true">
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
								array(
									'loading'  => 'lazy',
									'decoding' => 'async',
									'class'    => 'bw-embla-img',
								)
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
	 * Render a src-less <img> placeholder for non-eager carousel slides.
	 *
	 * The browser does NOT download these images at parse time: data-bw-src /
	 * data-bw-srcset / data-bw-sizes replace src/srcset/sizes. JS activates the
	 * download only when the slide is "close" to the current one.
	 *
	 * Why not loading="lazy": loading="lazy" inside an overflow:hidden container
	 * is not respected by all browsers — many download off-screen images anyway,
	 * causing sequential loading of all slides before the last one
	 * (visible on the left in loop+center mode) finishes.
	 *
	 * BWEmblaCore.initImageLoading() handles the fade-in correctly: without a
	 * src, img.naturalWidth === 0, so it attaches a 'load' listener that fires
	 * when JS sets img.src.
	 *
	 * @param  int          $image_id  WordPress attachment ID
	 * @param  string|array $size      Image size (string or array [w,h])
	 * @return string                  HTML <img> tag (unescaped — caller must phpcs:ignore)
	 */
	protected function render_lazy_img( $image_id, $size ) {
		$src_data = wp_get_attachment_image_src( $image_id, $size );
		if ( ! $src_data ) {
			return '';
		}

		list( $src, $width, $height ) = $src_data;
		$alt                          = trim( strip_tags( get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ) );
		$srcset                       = wp_get_attachment_image_srcset( $image_id, $size );
		$sizes                        = wp_get_attachment_image_sizes( $image_id, $size );

		$html  = '<img class="bw-embla-img"';
		$html .= ' data-bw-src="' . esc_url( $src ) . '"';
		if ( $srcset ) {
			$html .= ' data-bw-srcset="' . esc_attr( $srcset ) . '"';
		}
		if ( $sizes ) {
			$html .= ' data-bw-sizes="' . esc_attr( $sizes ) . '"';
		}
		if ( $width ) {
			$html .= ' width="' . absint( $width ) . '"';
		}
		if ( $height ) {
			$html .= ' height="' . absint( $height ) . '"';
		}
		$html .= ' alt="' . esc_attr( $alt ) . '"';
		$html .= ' decoding="async">';

		return $html;
	}

	/**
	 * Build responsive configuration for JS (Embla).
	 * slidesToShow è gestito da CSS inline in render_breakpoint_css().
	 */
	protected function build_responsive_config( $settings ) {
		$responsive = array();

		if ( ! empty( $settings['breakpoints'] ) ) {
			foreach ( $settings['breakpoints'] as $breakpoint ) {
				$image_height = null;
				if ( ! empty( $breakpoint['image_height'] ) && isset( $breakpoint['image_height']['size'] ) ) {
					$image_height = array(
						'size' => (float) $breakpoint['image_height']['size'],
						'unit' => $breakpoint['image_height']['unit'] ?? 'px',
					);
				}

				$image_width = null;
				if ( ! empty( $breakpoint['image_width'] ) && isset( $breakpoint['image_width']['size'] ) ) {
					$image_width = array(
						'size' => (float) $breakpoint['image_width']['size'],
						'unit' => $breakpoint['image_width']['unit'] ?? 'px',
					);
				}

				$responsive[] = array(
					'breakpoint'      => absint( $breakpoint['breakpoint'] ),
					'imageHeightMode' => $breakpoint['image_height_mode'] ?? 'auto',
					'imageHeight'     => $image_height,
					'imageWidth'      => $image_width,
					'slidesToScroll'  => max( 1, absint( $breakpoint['slides_to_scroll'] ?? 1 ) ),
					'centerMode'      => ( $breakpoint['center_mode'] ?? '' ) === 'yes',
					'variableWidth'   => ( $breakpoint['variable_width'] ?? '' ) === 'yes',
				);
			}
		}

		return $responsive;
	}
}
