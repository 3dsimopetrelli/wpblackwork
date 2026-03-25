<?php
/**
 * BW Mosaic Slider Elementor widget.
 *
 * @package BW_Elementor_Widgets
 */

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BW Mosaic Slider Widget
 *
 * Embla-based mixed-content slider with:
 * - desktop asymmetric 5-item mosaic pages
 * - mobile linear 1-card slider fallback below 1000px
 * - product rendering delegated to BW_Product_Card_Component
 */
class BW_Mosaic_Slider_Widget extends Widget_Base {

	private const ITEMS_PER_PAGE    = 5;
	private const MOBILE_BREAKPOINT = 1000;

	/**
	 * Get widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'bw-mosaic-slider';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'BW-UI Mosaic Slider', 'bw-elementor-widgets' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-post-slider';
	}

	/**
	 * Get Elementor categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'blackwork' );
	}

	/**
	 * Get script dependencies.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'embla-js', 'embla-autoplay-js', 'bw-embla-core-js', 'bw-mosaic-slider-script' );
	}

	/**
	 * Get style dependencies.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'bw-product-card-style', 'bw-embla-core-css', 'bw-mosaic-slider-style' );
	}

	/**
	 * Register all widget controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->register_query_controls();
		$this->register_layout_controls();
		$this->register_slider_controls();
		$this->register_card_controls();
		$this->register_style_controls();
	}

	/**
	 * Register query controls.
	 *
	 * @return void
	 */
	private function register_query_controls() {
		$this->start_controls_section(
			'section_query',
			array(
				'label' => __( 'Query', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'post_type',
			array(
				'label'   => __( 'Source Type', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'product',
				'options' => array(
					'product' => __( 'Product', 'bw-elementor-widgets' ),
					'post'    => __( 'Post', 'bw-elementor-widgets' ),
				),
			)
		);

		$this->add_control(
			'product_parent_category',
			array(
				'label'       => __( 'Product Category', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => false,
				'options'     => function_exists( 'bw_get_parent_product_categories' ) ? bw_get_parent_product_categories() : array(),
				'condition'   => array( 'post_type' => 'product' ),
			)
		);

		$this->add_control(
			'product_subcategory',
			array(
				'label'       => __( 'Product Sub-category', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => function_exists( 'bw_get_product_categories_options' ) ? bw_get_product_categories_options() : array(),
				'condition'   => array(
					'post_type'                => 'product',
					'product_parent_category!' => '',
				),
				'description' => __( 'Optional child categories. When selected, they override the parent category filter.', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'post_parent_category',
			array(
				'label'       => __( 'Post Category', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => false,
				'options'     => $this->get_parent_post_category_options(),
				'condition'   => array( 'post_type' => 'post' ),
			)
		);

		$this->add_control(
			'post_subcategory',
			array(
				'label'       => __( 'Post Sub-category', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => $this->get_post_category_options(),
				'condition'   => array(
					'post_type'             => 'post',
					'post_parent_category!' => '',
				),
				'description' => __( 'Optional child categories. When selected, they override the parent category filter.', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'specific_ids',
			array(
				'label'       => __( 'Manual IDs', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'e.g. 120, 125, 129', 'bw-elementor-widgets' ),
				'description' => __( 'Manual IDs override taxonomy and ordering filters. If Randomize is enabled, only this selected set is shuffled.', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'posts_per_page',
			array(
				'label'   => __( 'Item Count', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 10,
				'min'     => 1,
				'max'     => 50,
			)
		);

		$this->add_control(
			'order_by',
			array(
				'label'     => __( 'Order By', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'date',
				'options'   => array(
					'date'     => __( 'Publish Date', 'bw-elementor-widgets' ),
					'modified' => __( 'Modified Date', 'bw-elementor-widgets' ),
					'title'    => __( 'Title', 'bw-elementor-widgets' ),
					'ID'       => __( 'ID', 'bw-elementor-widgets' ),
				),
				'condition' => array(
					'randomize!' => 'yes',
				),
			)
		);

		$this->add_control(
			'order',
			array(
				'label'     => __( 'Order', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'DESC',
				'options'   => array(
					'ASC'  => __( 'Ascending', 'bw-elementor-widgets' ),
					'DESC' => __( 'Descending', 'bw-elementor-widgets' ),
				),
				'condition' => array(
					'randomize!' => 'yes',
				),
			)
		);

		$this->add_control(
			'randomize',
			array(
				'label'        => __( 'Randomize Items', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'When enabled, the query becomes randomized. Deterministic transient caching is skipped.', 'bw-elementor-widgets' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register layout controls.
	 *
	 * @return void
	 */
	private function register_layout_controls() {
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => __( 'Layout', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'layout_variant',
			array(
				'label'   => __( 'Desktop Mosaic Variant', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'center',
				'options' => array(
					'center'       => __( 'Big Post Center', 'bw-elementor-widgets' ),
					'center_split' => __( 'Big Center Split', 'bw-elementor-widgets' ),
					'left'         => __( 'Big Post Left', 'bw-elementor-widgets' ),
					'right'        => __( 'Big Post Right', 'bw-elementor-widgets' ),
				),
			)
		);

		$this->add_control(
			'mobile_breakpoint_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				/* translators: %d: pixel breakpoint value */
				'raw'             => esc_html( sprintf( __( 'Below %dpx the desktop mosaic is disabled and the widget switches to a responsive Embla slider. Use the Style > Layout controls to set how many cards remain visible on tablet and mobile, including partial next-slide visibility.', 'bw-elementor-widgets' ), self::MOBILE_BREAKPOINT ) ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register slider controls.
	 *
	 * @return void
	 */
	private function register_slider_controls() {
		$this->start_controls_section(
			'section_slider',
			array(
				'label' => __( 'Slider Settings', 'bw-elementor-widgets' ),
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
				'default'   => 3500,
				'min'       => 1000,
				'max'       => 15000,
				'step'      => 250,
				'condition' => array(
					'autoplay' => 'yes',
				),
			)
		);

		$this->add_control(
			'autoplay_pause_on_focus',
			array(
				'label'        => __( 'Pause on Focus', 'bw-elementor-widgets' ),
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
			)
		);

		$this->add_control(
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

		$this->add_control(
			'show_dots',
			array(
				'label'        => __( 'Show Dots', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'dots_position',
			array(
				'label'     => __( 'Dots Position', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'center',
				'options'   => array(
					'start'  => __( 'Left', 'bw-elementor-widgets' ),
					'center' => __( 'Center', 'bw-elementor-widgets' ),
					'end'    => __( 'Right', 'bw-elementor-widgets' ),
				),
				'condition' => array(
					'show_dots' => 'yes',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register card controls.
	 *
	 * @return void
	 */
	private function register_card_controls() {
		$this->start_controls_section(
			'section_cards',
			array(
				'label' => __( 'Card Settings', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'show_title',
			array(
				'label'        => __( 'Show Title', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_description',
			array(
				'label'        => __( 'Show Description', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'show_price',
			array(
				'label'        => __( 'Show Price', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'post_type' => 'product',
				),
			)
		);

		$this->add_control(
			'show_buttons',
			array(
				'label'        => __( 'Show Overlay Buttons', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'post_type' => 'product',
				),
			)
		);

		$this->add_responsive_control(
			'hide_overlay_buttons',
			array(
				'label'        => __( 'Hide Overlay Buttons', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array(
					'post_type'    => 'product',
					'show_buttons' => 'yes',
				),
				'description'  => __( 'Hide overlay buttons on the selected responsive breakpoints.', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'image_size',
			array(
				'label'   => __( 'Image Size', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'large',
				'options' => array(
					'thumbnail'    => __( 'Thumbnail (150×150)', 'bw-elementor-widgets' ),
					'medium'       => __( 'Medium (300×300)', 'bw-elementor-widgets' ),
					'medium_large' => __( 'Medium Large (768×auto)', 'bw-elementor-widgets' ),
					'large'        => __( 'Large (1024×1024)', 'bw-elementor-widgets' ),
					'full'         => __( 'Full Size (Original)', 'bw-elementor-widgets' ),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register style controls.
	 *
	 * @return void
	 */
	private function register_style_controls() {
		$this->start_controls_section(
			'section_style_layout',
			array(
				'label' => __( 'Layout', 'bw-elementor-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'auto_scale_mosaic',
			array(
				'label'        => __( 'Auto Scale Mosaic', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw-elementor-widgets' ),
				'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'When enabled, the desktop mosaic scales proportionally as the available width shrinks. When disabled, use the manual Desktop Mosaic Height control.', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'auto_scale_square',
			array(
				'label'        => __( 'Auto Scale Square Format', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw-elementor-widgets' ),
				'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array(
					'auto_scale_mosaic' => 'yes',
				),
				'description'  => __( 'Force the autoscaled desktop mosaic into a square canvas ratio.', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'desktop_mosaic_height',
			array(
				'label'      => __( 'Desktop Mosaic Height', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array(
					'px' => array(
						'min'  => 320,
						'max'  => 1400,
						'step' => 10,
					),
					'vh' => array(
						'min'  => 30,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 700,
					'unit' => 'px',
				),
				'condition'  => array(
					'auto_scale_mosaic!' => 'yes',
				),
				'selectors'  => array(
					'{{WRAPPER}}' => '--bw-ms-desktop-height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'horizontal_gap',
			array(
				'label'      => __( 'Horizontal Gap', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 60,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 18,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}}' => '--bw-ms-column-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'vertical_gap',
			array(
				'label'      => __( 'Vertical Gap', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 60,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 18,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}}' => '--bw-ms-row-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'tablet_visible_slides',
			array(
				'label'       => __( 'Tablet Visible Slides', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 3.2,
				'min'         => 1,
				'max'         => 4.5,
				'step'        => 0.1,
				'description' => __( 'Use decimals like 3.2 to reveal a portion of the next slide.', 'bw-elementor-widgets' ),
			)
		);

		$this->add_control(
			'mobile_visible_slides',
			array(
				'label'       => __( 'Mobile Visible Slides', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 2.2,
				'min'         => 1,
				'max'         => 3.5,
				'step'        => 0.1,
				'description' => __( 'Use decimals like 2.2 to reveal a portion of the next slide.', 'bw-elementor-widgets' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_images',
			array(
				'label' => __( 'Images', 'bw-elementor-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'image_border_radius',
			array(
				'label'      => __( 'Image Border Radius', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 60,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 18,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}}' => '--bw-ms-image-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_text',
			array(
				'label' => __( 'Text', 'bw-elementor-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'heading_title_typography',
			array(
				'label'     => __( 'Title', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .bw-ms-title',
			)
		);

		$this->add_responsive_control(
			'title_padding',
			array(
				'label'      => __( 'Title Padding', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}} .bw-ms-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'heading_description_typography',
			array(
				'label'     => __( 'Description', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'description_typography',
				'selector' => '{{WRAPPER}} .bw-ms-description',
			)
		);

		$this->add_responsive_control(
			'description_padding',
			array(
				'label'      => __( 'Description Padding', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}} .bw-ms-description' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'heading_price_typography',
			array(
				'label'     => __( 'Price', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'price_typography',
				'selector'  => '{{WRAPPER}} .bw-ms-price',
				'condition' => array(
					'post_type' => 'product',
				),
			)
		);

		$this->add_responsive_control(
			'price_padding',
			array(
				'label'      => __( 'Price Padding', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}} .bw-ms-price' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'condition'  => array(
					'post_type' => 'product',
				),
			)
		);

		$this->add_control(
			'heading_text_spacing',
			array(
				'label'     => __( 'Spacing', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'text_items_gap',
			array(
				'label'      => __( 'Text Items Gap', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 40,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 12,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-ms-content, {{WRAPPER}} .bw-ms-slot .bw-product-card .bw-ms-content, {{WRAPPER}} .bw-ms-editorial-shell .bw-ms-content' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output.
	 *
	 * @return void
	 */
	protected function render() {
		$settings  = $this->get_settings_for_display();
		$widget_id = $this->get_id();
		$post_type = $this->resolve_post_type( $settings['post_type'] ?? 'product' );
		$is_editor = class_exists( '\Elementor\Plugin' )
			&& \Elementor\Plugin::$instance->editor
			&& \Elementor\Plugin::$instance->editor->is_edit_mode();

		$args       = $this->build_query_args( $settings, $post_type );
		$randomized = ( $settings['randomize'] ?? '' ) === 'yes';
		$posts      = null;
		$cache_key  = '';

		if ( ! $randomized && ! $is_editor ) {
			$key_data = array(
				'v'              => 1,
				'post_type'      => $args['post_type'],
				'posts_per_page' => $args['posts_per_page'],
				'orderby'        => $args['orderby'],
				'order'          => $args['order'] ?? 'DESC',
				'post__in'       => $args['post__in'] ?? array(),
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Taxonomy clauses are part of the cache identity.
				'tax_query'      => $args['tax_query'] ?? array(),
			);
			$cache_key = 'bw_ms_' . md5( wp_json_encode( $key_data ) );
			$cached    = get_transient( $cache_key );

			if ( is_array( $cached ) && ! empty( $cached ) ) {
				$posts = array_values( array_filter( array_map( 'get_post', $cached ) ) );
			}
		}

		if ( null === $posts ) {
			$query = new WP_Query( $args );
			$posts = is_array( $query->posts ) ? $query->posts : array();
			wp_reset_postdata();

			if ( $cache_key && ! empty( $posts ) ) {
				set_transient( $cache_key, wp_list_pluck( $posts, 'ID' ), 5 * MINUTE_IN_SECONDS );
			}
		}

		if ( empty( $posts ) ) {
			$this->render_placeholder( __( 'No content found for the current Mosaic Slider query.', 'bw-elementor-widgets' ) );
			return;
		}

		$desktop_pages = array_chunk( $posts, self::ITEMS_PER_PAGE );
		$config        = array(
			'widgetId'           => $widget_id,
			'showArrows'         => ( $settings['show_arrows'] ?? 'yes' ) === 'yes',
			'showDots'           => ( $settings['show_dots'] ?? 'yes' ) === 'yes',
			'dotsPosition'       => $settings['dots_position'] ?? 'center',
			// Single source of truth: JS reads this instead of its own hardcoded constant.
			'mobileBreakpoint'   => self::MOBILE_BREAKPOINT,
			// Responsive overlay-button state mirrored to JS for future-proofing.
			'hideOverlayButtons' => array(
				'desktop' => ( $settings['hide_overlay_buttons'] ?? '' ) === 'yes',
				'tablet'  => ( $settings['hide_overlay_buttons_tablet'] ?? '' ) === 'yes',
				'mobile'  => ( $settings['hide_overlay_buttons_mobile'] ?? '' ) === 'yes',
			),
			'desktop'            => $this->build_slider_config( $settings, 'desktop' ),
			'mobile'             => $this->build_slider_config( $settings, 'mobile' ),
		);

		$wrapper_classes = array( 'bw-mosaic-slider-wrapper' );
		if ( ( $settings['show_arrows'] ?? 'yes' ) !== 'yes' ) {
			$wrapper_classes[] = 'bw-ms-hide-arrows';
		}
		if ( ( $settings['show_dots'] ?? 'yes' ) !== 'yes' ) {
			$wrapper_classes[] = 'bw-ms-hide-dots';
		}
		if ( ( $settings['auto_scale_mosaic'] ?? '' ) === 'yes' ) {
			$wrapper_classes[] = 'bw-ms-auto-scale';
		}
		if ( ( $settings['auto_scale_square'] ?? '' ) === 'yes' ) {
			$wrapper_classes[] = 'bw-ms-auto-scale-square';
		}
		if ( ( $settings['hide_overlay_buttons'] ?? '' ) === 'yes' ) {
			$wrapper_classes[] = 'bw-ms-hide-overlay-buttons-desktop';
		}
		if ( ( $settings['hide_overlay_buttons_tablet'] ?? '' ) === 'yes' ) {
			$wrapper_classes[] = 'bw-ms-hide-overlay-buttons-tablet';
		}
		if ( ( $settings['hide_overlay_buttons_mobile'] ?? '' ) === 'yes' ) {
			$wrapper_classes[] = 'bw-ms-hide-overlay-buttons-mobile';
		}

		$inline_style_rules = array();
		$tablet_visible     = $this->normalize_visible_slides_setting( $settings['tablet_visible_slides'] ?? 3.2, 3.2 );
		$mobile_visible     = $this->normalize_visible_slides_setting( $settings['mobile_visible_slides'] ?? 2.2, 2.2 );

		$inline_style_rules[] = '--bw-ms-tablet-visible-slides: ' . $tablet_visible . ';';
		$inline_style_rules[] = '--bw-ms-mobile-visible-slides: ' . $mobile_visible . ';';

		$this->add_render_attribute(
			'wrapper',
			array(
				'class'          => implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ),
				'data-widget-id' => esc_attr( $widget_id ),
				'data-config'    => esc_attr( wp_json_encode( $config ) ),
				'style'          => implode( ' ', $inline_style_rules ),
			)
		);

		?>
		<div
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor render attributes are escaped internally.
			echo $this->get_render_attribute_string( 'wrapper' );
			?>
		>
			<?php $this->render_desktop_layout( $desktop_pages, $settings, $post_type ); ?>
			<?php $this->render_mobile_layout( $posts, $settings, $post_type ); ?>
		</div>
		<?php
	}

	/**
	 * Render the desktop mosaic viewport.
	 *
	 * @param array  $desktop_pages Grouped 5-item desktop pages.
	 * @param array  $settings      Widget settings.
	 * @param string $post_type     Active source type.
	 * @return void
	 */
	private function render_desktop_layout( array $desktop_pages, array $settings, $post_type ) {
		$variant    = $this->resolve_layout_variant( $settings['layout_variant'] ?? 'center' );
		$image_size = $settings['image_size'] ?? 'large';
		?>
		<div class="bw-ms-layout bw-ms-layout--desktop">
			<div class="bw-ms-desktop-shell">
				<div class="bw-embla-viewport bw-ms-embla-viewport bw-ms-desktop-viewport">
					<div class="bw-embla-container">
						<?php foreach ( $desktop_pages as $page_index => $page_posts ) : ?>
							<?php $page_variant = $this->resolve_page_layout_variant( $variant, $page_index ); ?>
							<div class="bw-embla-slide bw-ms-desktop-slide">
								<div class="bw-ms-page bw-ms-page--<?php echo esc_attr( $page_variant ); ?>">
									<?php foreach ( $this->build_page_slots( $page_posts ) as $slot_name => $slot_post ) : ?>
										<?php
										$is_featured = 'featured' === $slot_name;
										$loading     = 0 === $page_index ? 'eager' : 'lazy';
										$priority    = ( 0 === $page_index && $is_featured ) ? 'high' : '';
										?>
										<div class="bw-ms-slot bw-ms-slot--<?php echo esc_attr( $slot_name ); ?>">
											<?php
											// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped inside render_item_card() and delegated renderers.
											echo $this->render_item_card( $slot_post, $settings, $post_type, $image_size, $loading, $priority, $is_featured, 'desktop' );
											?>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="bw-ms-arrows-container bw-ms-arrows-container--desktop">
					<button class="bw-ms-arrow bw-ms-arrow-prev bw-ms-arrow-prev-desktop" aria-label="<?php esc_attr_e( 'Previous', 'bw-elementor-widgets' ); ?>">&#8592;</button>
					<button class="bw-ms-arrow bw-ms-arrow-next bw-ms-arrow-next-desktop" aria-label="<?php esc_attr_e( 'Next', 'bw-elementor-widgets' ); ?>">&#8594;</button>
				</div>

				<div class="bw-ms-dots-container bw-ms-dots-container--desktop"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the mobile linear viewport.
	 *
	 * @param array  $posts     Queried posts.
	 * @param array  $settings  Widget settings.
	 * @param string $post_type Active source type.
	 * @return void
	 */
	private function render_mobile_layout( array $posts, array $settings, $post_type ) {
		$image_size = $settings['image_size'] ?? 'large';
		?>
		<div class="bw-ms-layout bw-ms-layout--mobile">
			<div class="bw-ms-mobile-shell">
				<div class="bw-embla-viewport bw-ms-embla-viewport bw-ms-mobile-viewport">
					<div class="bw-embla-container">
						<?php foreach ( $posts as $index => $post ) : ?>
							<?php
							$loading  = 0 === $index ? 'eager' : 'lazy';
							$priority = 0 === $index ? 'high' : '';
							?>
							<div class="bw-embla-slide bw-ms-mobile-slide">
								<div class="bw-ms-mobile-card-shell">
									<?php
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped inside render_item_card() and delegated renderers.
									echo $this->render_item_card( $post, $settings, $post_type, $image_size, $loading, $priority, false, 'mobile' );
									?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="bw-ms-arrows-container bw-ms-arrows-container--mobile">
					<button class="bw-ms-arrow bw-ms-arrow-prev bw-ms-arrow-prev-mobile" aria-label="<?php esc_attr_e( 'Previous', 'bw-elementor-widgets' ); ?>">&#8592;</button>
					<button class="bw-ms-arrow bw-ms-arrow-next bw-ms-arrow-next-mobile" aria-label="<?php esc_attr_e( 'Next', 'bw-elementor-widgets' ); ?>">&#8594;</button>
				</div>

				<div class="bw-ms-dots-container bw-ms-dots-container--mobile"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Build shared slider config for a viewport mode.
	 *
	 * @param array  $settings Widget settings.
	 * @param string $mode     Desktop or mobile mode.
	 * @return array
	 */
	private function build_slider_config( array $settings, $mode ) {
		// Desktop always disables touch drag; mobile reads from the Touch Drag control.
		$enable_touch_drag = 'mobile' === $mode
			? ( ( $settings['touch_drag'] ?? 'yes' ) === 'yes' )
			: false;

		return array(
			'infinite'        => ( $settings['infinite_loop'] ?? 'yes' ) === 'yes',
			'autoplay'        => ( $settings['autoplay'] ?? '' ) === 'yes',
			'autoplaySpeed'   => absint( $settings['autoplay_speed'] ?? 3500 ),
			'pauseOnHover'    => ( $settings['pause_on_hover'] ?? 'yes' ) === 'yes',
			'stopOnFocusIn'   => ( $settings['autoplay_pause_on_focus'] ?? 'yes' ) === 'yes',
			'dragFree'        => ( $settings['drag_free'] ?? '' ) === 'yes',
			'enableTouchDrag' => $enable_touch_drag,
			'enableMouseDrag' => ( $settings['mouse_drag'] ?? 'yes' ) === 'yes',
			'align'           => 'start',
		);
	}

	/**
	 * Build deterministic WP_Query arguments for the widget.
	 *
	 * @param array  $settings  Widget settings.
	 * @param string $post_type Active source type.
	 * @return array
	 */
	private function build_query_args( array $settings, $post_type ) {
		$args = array(
			'post_type'      => $post_type,
			'posts_per_page' => max( 1, absint( $settings['posts_per_page'] ?? 10 ) ),
			'post_status'    => 'publish',
			// Skips the SQL_CALC_FOUND_ROWS / COUNT(*) query — pagination is not used here.
			'no_found_rows'  => true,
		);

		$manual_ids = BW_Widget_Helper::parse_ids( $settings['specific_ids'] ?? '' );
		$randomize  = ( $settings['randomize'] ?? '' ) === 'yes';

		if ( ! empty( $manual_ids ) ) {
			if ( $randomize ) {
				shuffle( $manual_ids );
			}

			$args['post__in']       = $manual_ids;
			$args['posts_per_page'] = count( $manual_ids );
			$args['orderby']        = 'post__in';

			return $args;
		}

		if ( $randomize ) {
			$args['orderby'] = 'rand';
		} else {
			$args['orderby'] = $settings['order_by'] ?? 'date';
			$args['order']   = $settings['order'] ?? 'DESC';
		}

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Taxonomy filtering is the declared query contract for this widget.
		$tax_query = $this->build_tax_query( $settings, $post_type );
		if ( ! empty( $tax_query ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Taxonomy filtering is the declared query contract for this widget.
			$args['tax_query'] = $tax_query;
		}

		return $args;
	}

	/**
	 * Build taxonomy query clauses from widget settings.
	 *
	 * @param array  $settings  Widget settings.
	 * @param string $post_type Active source type.
	 * @return array
	 */
	private function build_tax_query( array $settings, $post_type ) {
		if ( 'product' === $post_type ) {
			$subcats = array_filter( array_map( 'absint', (array) ( $settings['product_subcategory'] ?? array() ) ) );
			if ( ! empty( $subcats ) ) {
				return array(
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => $subcats,
						'operator' => 'IN',
					),
				);
			}

			$parent = absint( $settings['product_parent_category'] ?? 0 );
			if ( $parent > 0 ) {
				return array(
					array(
						'taxonomy'         => 'product_cat',
						'field'            => 'term_id',
						'terms'            => array( $parent ),
						'include_children' => true,
						'operator'         => 'IN',
					),
				);
			}

			return array();
		}

		$subcats = array_filter( array_map( 'absint', (array) ( $settings['post_subcategory'] ?? array() ) ) );
		if ( ! empty( $subcats ) ) {
			return array(
				array(
					'taxonomy' => 'category',
					'field'    => 'term_id',
					'terms'    => $subcats,
					'operator' => 'IN',
				),
			);
		}

		$parent = absint( $settings['post_parent_category'] ?? 0 );
		if ( $parent > 0 ) {
			return array(
				array(
					'taxonomy'         => 'category',
					'field'            => 'term_id',
					'terms'            => array( $parent ),
					'include_children' => true,
					'operator'         => 'IN',
				),
			);
		}

		return array();
	}

	/**
	 * Map a 5-item batch to the desktop slot contract.
	 *
	 * @param array $page_posts Posts in the current batch.
	 * @return array
	 */
	private function build_page_slots( array $page_posts ) {
		$slots      = array();
		$slot_names = array( 'featured', 'support-1', 'support-2', 'support-3', 'support-4' );

		foreach ( $page_posts as $index => $post ) {
			if ( ! isset( $slot_names[ $index ] ) ) {
				break;
			}

			$slots[ $slot_names[ $index ] ] = $post;
		}

		return $slots;
	}

	/**
	 * Render one product/editorial card item.
	 *
	 * @param WP_Post $post          Post object.
	 * @param array   $settings      Widget settings.
	 * @param string  $post_type     Active source type.
	 * @param string  $image_size    Requested image size.
	 * @param string  $image_loading Loading mode.
	 * @param string  $fetchpriority Fetch priority attribute.
	 * @param bool    $is_featured   Whether the tile is featured.
	 * @param string  $context       Desktop or mobile context.
	 * @return string
	 */
	private function render_item_card( WP_Post $post, array $settings, $post_type, $image_size, $image_loading, $fetchpriority, $is_featured, $context ) {
		if ( 'product' === $post_type && class_exists( 'BW_Product_Card_Component' ) && method_exists( 'BW_Product_Card_Component', 'render' ) && function_exists( 'wc_get_product' ) ) {
			$has_product_content = ( $settings['show_title'] ?? 'yes' ) === 'yes'
				|| ( $settings['show_description'] ?? '' ) === 'yes'
				|| ( $settings['show_price'] ?? 'yes' ) === 'yes';
			$card_classes        = array(
				'bw-ms-card',
				$is_featured ? 'bw-ms-card--featured' : 'bw-ms-card--support',
				'bw-ms-card--' . $context,
			);
			$content_classes     = array( 'bw-ms-content', 'bw-slider-content' );

			if ( ! $has_product_content ) {
				$content_classes[] = 'bw-ms-content--empty';
			}

			return BW_Product_Card_Component::render(
				$post->ID,
				array(
					'image_size'              => $image_size,
					'image_mode'              => 'cover',
					'image_loading'           => $image_loading,
					'hover_image_loading'     => 'lazy',
					'image_fetchpriority'     => $fetchpriority,
					'show_title'              => ( $settings['show_title'] ?? 'yes' ) === 'yes',
					'show_description'        => ( $settings['show_description'] ?? '' ) === 'yes',
					'description_mode'        => 'auto',
					'show_price'              => ( $settings['show_price'] ?? 'yes' ) === 'yes',
					'show_buttons'            => ( $settings['show_buttons'] ?? 'yes' ) === 'yes',
					'show_add_to_cart'        => true,
					'open_cart_popup'         => false,
					'hover_image_source'      => 'meta',
					'wrapper_classes'         => 'bw-ms-item bw-ms-item--product',
					'card_classes'            => implode( ' ', $card_classes ),
					'media_classes'           => 'bw-ms-media',
					'media_link_classes'      => 'bw-ms-media-link',
					'image_wrapper_classes'   => 'bw-ms-image',
					'content_classes'         => implode( ' ', $content_classes ),
					'title_classes'           => 'bw-ms-title',
					'description_classes'     => 'bw-ms-description',
					'price_classes'           => 'bw-ms-price price',
					'overlay_classes'         => 'bw-ms-overlay overlay-buttons has-buttons',
					'overlay_buttons_classes' => 'bw-ms-overlay-buttons',
					'view_button_classes'     => 'bw-ms-overlay-button overlay-button overlay-button--view',
					'cart_button_classes'     => 'bw-ms-overlay-button overlay-button overlay-button--cart bw-btn-addtocart',
					'placeholder_classes'     => 'bw-ms-image-placeholder',
				)
			);
		}

		return $this->render_editorial_card( $post, $settings, $image_size, $image_loading, $fetchpriority, $is_featured, $context );
	}

	/**
	 * Render the local editorial card path for non-product content.
	 *
	 * @param WP_Post $post          Post object.
	 * @param array   $settings      Widget settings.
	 * @param string  $image_size    Requested image size.
	 * @param string  $image_loading Loading mode.
	 * @param string  $fetchpriority Fetch priority attribute.
	 * @param bool    $is_featured   Whether the tile is featured.
	 * @param string  $context       Desktop or mobile context.
	 * @return string
	 */
	private function render_editorial_card( WP_Post $post, array $settings, $image_size, $image_loading, $fetchpriority, $is_featured, $context ) {
		$post_id      = (int) $post->ID;
		$permalink    = get_permalink( $post_id );
		$title        = get_the_title( $post_id );
		$show_title   = ( $settings['show_title'] ?? 'yes' ) === 'yes';
		$show_excerpt = ( $settings['show_description'] ?? '' ) === 'yes';
		$excerpt      = '';

		if ( $show_excerpt ) {
			$excerpt = get_the_excerpt( $post_id );
			if ( '' === $excerpt ) {
				$excerpt = wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ), 24 );
			}
		}

		$card_classes = array(
			'bw-ms-item',
			'bw-ms-item--editorial',
			'bw-ms-editorial-card',
			$is_featured ? 'bw-ms-card--featured' : 'bw-ms-card--support',
			'bw-ms-card--' . $context,
		);

		$image_html = '';
		if ( has_post_thumbnail( $post_id ) ) {
			$attrs = array(
				'class'   => 'bw-ms-editorial-image bw-embla-img',
				'loading' => $image_loading,
			);

			if ( '' !== $fetchpriority ) {
				$attrs['fetchpriority'] = $fetchpriority;
			}

			$image_html = get_the_post_thumbnail( $post_id, $image_size, $attrs );
		}

		ob_start();
		?>
		<article class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $card_classes ) ) ); ?>">
			<div class="bw-ms-editorial-shell">
				<a class="bw-ms-editorial-media" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
					<?php if ( $image_html ) : ?>
						<?php echo wp_kses_post( $image_html ); ?>
					<?php else : ?>
						<span class="bw-ms-editorial-placeholder" aria-hidden="true"></span>
					<?php endif; ?>
				</a>

				<?php if ( $show_title || ( $show_excerpt && '' !== $excerpt ) ) : ?>
					<div class="bw-ms-content">
						<?php if ( $show_title ) : ?>
							<h3 class="bw-ms-title">
								<a href="<?php echo esc_url( $permalink ); ?>">
									<?php echo esc_html( $title ); ?>
								</a>
							</h3>
						<?php endif; ?>

						<?php if ( $show_excerpt && '' !== $excerpt ) : ?>
							<div class="bw-ms-description">
								<p><?php echo esc_html( $excerpt ); ?></p>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</article>
		<?php

		return ob_get_clean();
	}

	/**
	 * Resolve the allowed post type.
	 *
	 * @param string $post_type Raw post type.
	 * @return string
	 */
	private function resolve_post_type( $post_type ) {
		$post_type = sanitize_key( (string) $post_type );

		if ( ! in_array( $post_type, array( 'product', 'post' ), true ) ) {
			return 'product';
		}

		return $post_type;
	}

	/**
	 * Resolve the allowed desktop variant.
	 *
	 * @param string $variant Raw layout variant.
	 * @return string
	 */
	private function resolve_layout_variant( $variant ) {
		$variant = sanitize_key( (string) $variant );

		if ( ! in_array( $variant, array( 'center', 'center_split', 'left', 'right' ), true ) ) {
			return 'center';
		}

		return $variant;
	}

	/**
	 * Resolve the concrete page class for the current desktop page.
	 *
	 * Big Center Split alternates the split composition per page to keep the
	 * featured rhythm balanced across the slider sequence.
	 *
	 * @param string $variant    Selected widget variant.
	 * @param int    $page_index Zero-based page index.
	 * @return string
	 */
	private function resolve_page_layout_variant( $variant, $page_index ) {
		if ( 'center_split' !== $variant ) {
			return $variant;
		}

		return 0 === ( $page_index % 2 ) ? 'split-left' : 'split-right';
	}

	/**
	 * Normalize the visible slide count for responsive mobile/tablet layouts.
	 *
	 * @param mixed $value    Raw control value.
	 * @param float $fallback Fallback value.
	 * @return string
	 */
	private function normalize_visible_slides_setting( $value, $fallback ) {
		$value = is_numeric( $value ) ? (float) $value : (float) $fallback;
		$value = max( 1, $value );

		return rtrim( rtrim( sprintf( '%.2f', $value ), '0' ), '.' );
	}

	/**
	 * Get top-level post categories.
	 *
	 * @return array
	 */
	private function get_parent_post_category_options() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => false,
				'parent'     => 0,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		$options = array();
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return $options;
		}

		foreach ( $terms as $term ) {
			$options[ $term->term_id ] = $term->name;
		}

		return $options;
	}

	/**
	 * Get all post categories.
	 *
	 * @return array
	 */
	private function get_post_category_options() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		$options = array();
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return $options;
		}

		foreach ( $terms as $term ) {
			$options[ $term->term_id ] = $term->name;
		}

		return $options;
	}

	/**
	 * Render a generic placeholder.
	 *
	 * @param string $message Placeholder message.
	 * @return void
	 */
	private function render_placeholder( $message ) {
		?>
		<div class="bw-ms-placeholder">
			<div class="bw-ms-placeholder__inner">
				<?php echo esc_html( $message ); ?>
			</div>
		</div>
		<?php
	}
}
