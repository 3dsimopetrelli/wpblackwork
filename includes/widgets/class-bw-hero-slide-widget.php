<?php
/**
 * BW Hero Slide Elementor widget.
 *
 * @package BW_Elementor_Widgets
 */

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BW Hero Slide Widget
 *
 * Static-first hero section with future-ready mode scaffolding.
 */
class BW_Hero_Slide_Widget extends Widget_Base {

	/**
	 * Get widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'bw-hero-slide';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'BW-UI Hero Slide', 'bw-elementor-widgets' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-slider-push';
	}

	/**
	 * Get widget categories.
	 *
	 * @return array<int,string>
	 */
	public function get_categories() {
		return array( 'blackwork' );
	}

	/**
	 * Get style dependencies.
	 *
	 * @return array<int,string>
	 */
	public function get_style_depends() {
		return array( 'bw-hero-slide-style' );
	}

	/**
	 * Get script dependencies.
	 *
	 * @return array<int,string>
	 */
	public function get_script_depends() {
		return array( 'bw-hero-slide-script' );
	}

	/**
	 * Register all controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->register_content_controls();
		$this->register_buttons_controls();
		$this->register_layout_controls();
		$this->register_style_layout_controls();
		$this->register_style_title_controls();
		$this->register_style_subtitle_controls();
		$this->register_style_buttons_controls();
	}

	/**
	 * Register content controls.
	 *
	 * @return void
	 */
	private function register_content_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Content', 'bw-elementor-widgets' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'mode',
			array(
				'label'   => __( 'Mode', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'static',
				'options' => array(
					'static' => __( 'Static', 'bw-elementor-widgets' ),
					'slide'  => __( 'Slide', 'bw-elementor-widgets' ),
				),
			)
		);

		$this->add_control(
			'slide_mode_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Slide mode is reserved for a future implementation. In V1 the widget still renders the static hero layout.', 'bw-elementor-widgets' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
				'condition'       => array(
					'mode' => 'slide',
				),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => __( 'Title', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::WYSIWYG,
				'default'     => 'Explore <u>digital collections</u>,<br>buy <u>rare books and original prints</u>',
				'placeholder' => __( 'Enter the hero title (HTML allowed)', 'bw-elementor-widgets' ),
				'dynamic'     => array(
					'active' => true,
				),
			)
		);

		$this->add_control(
			'subtitle',
			array(
				'label'       => __( 'Subtitle', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => __( 'Fonts, textures, mockups and more — handpicked weekly so you can create faster with confidence.', 'bw-elementor-widgets' ),
				'placeholder' => __( 'Enter the hero subtitle', 'bw-elementor-widgets' ),
				'rows'        => 4,
				'label_block' => true,
				'dynamic'     => array(
					'active' => true,
				),
			)
		);

		$this->add_control(
			'background_image',
			array(
				'label'   => __( 'Background Image', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::MEDIA,
				'dynamic' => array(
					'active' => true,
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register CTA button controls.
	 *
	 * @return void
	 */
	private function register_buttons_controls() {
		$this->start_controls_section(
			'section_buttons',
			array(
				'label' => __( 'Buttons', 'bw-elementor-widgets' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'button_text',
			array(
				'label'       => __( 'Button Text', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Staff Selects', 'bw-elementor-widgets' ),
				'placeholder' => __( 'Enter button text', 'bw-elementor-widgets' ),
				'label_block' => true,
			)
		);

		$repeater->add_control(
			'link_type',
			array(
				'label'   => __( 'Link Type', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'manual_url',
				'options' => array(
					'manual_url'        => __( 'Manual URL', 'bw-elementor-widgets' ),
					'product_category'  => __( 'Product Category Archive', 'bw-elementor-widgets' ),
					'post_category'     => __( 'Post Category Archive', 'bw-elementor-widgets' ),
					'post_type_archive' => __( 'Archive Page', 'bw-elementor-widgets' ),
				),
			)
		);

		$repeater->add_control(
			'button_link',
			array(
				'label'       => __( 'Link', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => __( 'https://your-link.com', 'bw-elementor-widgets' ),
				'dynamic'     => array(
					'active' => true,
				),
				'condition'   => array(
					'link_type' => 'manual_url',
				),
			)
		);

		$repeater->add_control(
			'product_category_term_id',
			array(
				'label'       => __( 'Product Category', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'options'     => function_exists( 'bw_get_product_categories_options' ) ? bw_get_product_categories_options() : array(),
				'condition'   => array(
					'link_type' => 'product_category',
				),
			)
		);

		$repeater->add_control(
			'post_category_term_id',
			array(
				'label'       => __( 'Post Category', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'options'     => $this->get_post_category_options(),
				'condition'   => array(
					'link_type' => 'post_category',
				),
			)
		);

		$repeater->add_control(
			'archive_post_type',
			array(
				'label'     => __( 'Archive Page', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => $this->get_archive_post_type_options(),
				'condition' => array(
					'link_type' => 'post_type_archive',
				),
			)
		);

		$this->add_control(
			'buttons',
			array(
				'label'       => __( 'Buttons', 'bw-elementor-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ button_text || "Button" }}}',
				'default'     => array(
					array(
						'button_text' => __( 'Staff Selects', 'bw-elementor-widgets' ),
					),
					array(
						'button_text' => __( 'New Releases', 'bw-elementor-widgets' ),
					),
					array(
						'button_text' => __( 'Trending', 'bw-elementor-widgets' ),
					),
					array(
						'button_text' => __( 'Fonts', 'bw-elementor-widgets' ),
					),
				),
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
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_responsive_control(
			'hero_height',
			array(
				'label'      => __( 'Hero Height', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh', '%' ),
				'range'      => array(
					'px' => array(
						'min'  => 240,
						'max'  => 1600,
						'step' => 10,
					),
					'vh' => array(
						'min'  => 20,
						'max'  => 100,
						'step' => 1,
					),
					'%'  => array(
						'min'  => 20,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 72,
					'unit' => 'vh',
				),
				'selectors'  => array(
					'{{WRAPPER}}' => '--bw-hs-height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bw-hero-slide, {{WRAPPER}} .bw-hero-slide__inner' => 'min-height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'content_max_width',
			array(
				'label'      => __( 'Content Max Width', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'vw' ),
				'range'      => array(
					'px' => array(
						'min'  => 320,
						'max'  => 1600,
						'step' => 10,
					),
					'%'  => array(
						'min'  => 40,
						'max'  => 100,
						'step' => 1,
					),
					'vw' => array(
						'min'  => 30,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 1040,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}}'                         => '--bw-hs-content-max-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bw-hero-slide__content' => 'max-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register section/layout style controls.
	 *
	 * @return void
	 */
	private function register_style_layout_controls() {
		$this->start_controls_section(
			'section_style_layout',
			array(
				'label' => __( 'Layout', 'bw-elementor-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'content_alignment',
			array(
				'label'                => __( 'Content Alignment', 'bw-elementor-widgets' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => array(
					'left'   => array(
						'title' => __( 'Left', 'bw-elementor-widgets' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => __( 'Center', 'bw-elementor-widgets' ),
						'icon'  => 'eicon-text-align-center',
					),
				),
				'default'              => 'center',
				'toggle'               => false,
				'selectors_dictionary' => array(
					'left'   => '--bw-hs-items-align: flex-start; --bw-hs-text-align: left; --bw-hs-buttons-justify: flex-start;',
					'center' => '--bw-hs-items-align: center; --bw-hs-text-align: center; --bw-hs-buttons-justify: center;',
				),
				'selectors'            => array(
					'{{WRAPPER}}' => '{{VALUE}}',
				),
			)
		);

		$this->add_responsive_control(
			'section_padding',
			array(
				'label'      => __( 'Section Padding', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'default'    => array(
					'top'      => 60,
					'right'    => 24,
					'bottom'   => 60,
					'left'     => 24,
					'unit'     => 'px',
					'isLinked' => false,
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-hero-slide__inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'overlay_color',
			array(
				'label'     => __( 'Overlay Color', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(8, 8, 8, 0.86)',
				'selectors' => array(
					'{{WRAPPER}}' => '--bw-hs-overlay-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'glow_color',
			array(
				'label'     => __( 'Glow Color', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(170, 72, 255, 0.30)',
				'selectors' => array(
					'{{WRAPPER}}' => '--bw-hs-glow-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'overlay_opacity',
			array(
				'label'     => __( 'Overlay Opacity', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 1,
				'step'      => 0.01,
				'default'   => 1,
				'selectors' => array(
					'{{WRAPPER}}'                         => '--bw-hs-overlay-opacity: {{VALUE}};',
					'{{WRAPPER}} .bw-hero-slide__overlay' => 'opacity: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register title style controls.
	 *
	 * @return void
	 */
	private function register_style_title_controls() {
		$this->start_controls_section(
			'section_style_title',
			array(
				'label' => __( 'Title', 'bw-elementor-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .bw-hero-slide__title',
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => __( 'Color', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#FFFFFF',
				'selectors' => array(
					'{{WRAPPER}} .bw-hero-slide__title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'title_padding',
			array(
				'label'      => __( 'Padding', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}} .bw-hero-slide__title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register subtitle style controls.
	 *
	 * @return void
	 */
	private function register_style_subtitle_controls() {
		$this->start_controls_section(
			'section_style_subtitle',
			array(
				'label' => __( 'Subtitle', 'bw-elementor-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'subtitle_typography',
				'selector' => '{{WRAPPER}} .bw-hero-slide__subtitle',
			)
		);

		$this->add_control(
			'subtitle_color',
			array(
				'label'     => __( 'Color', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(255, 255, 255, 0.86)',
				'selectors' => array(
					'{{WRAPPER}} .bw-hero-slide__subtitle' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'subtitle_padding',
			array(
				'label'      => __( 'Padding', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}} .bw-hero-slide__subtitle' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register button style controls.
	 *
	 * @return void
	 */
	private function register_style_buttons_controls() {
		$this->start_controls_section(
			'section_style_buttons',
			array(
				'label' => __( 'Buttons', 'bw-elementor-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'button_border_width',
			array(
				'label'      => __( 'Border Width', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 8,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 1,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}}'                        => '--bw-hs-button-border-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bw-hero-slide__button' => 'border-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'button_border_radius',
			array(
				'label'      => __( 'Border Radius', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 999,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 999,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}}'                        => '--bw-hs-button-radius: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bw-hero-slide__button' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'button_glass_effect',
			array(
				'label'        => __( 'Glass Effect', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw-elementor-widgets' ),
				'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'button_fill_enabled',
			array(
				'label'        => __( 'Fill Enabled', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw-elementor-widgets' ),
				'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'button_fill_color',
			array(
				'label'     => __( 'Fill Color', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#FFFFFF',
				'selectors' => array(
					'{{WRAPPER}}' => '--bw-hs-button-fill-color: {{VALUE}};',
					'{{WRAPPER}} .bw-hero-slide__button::before' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_border_color',
			array(
				'label'     => __( 'Border Color', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(255, 255, 255, 0.16)',
				'selectors' => array(
					'{{WRAPPER}}'                        => '--bw-hs-button-border-color: {{VALUE}};',
					'{{WRAPPER}} .bw-hero-slide__button' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .bw-hero-slide__button-label',
			)
		);

		$this->add_control(
			'button_text_color',
			array(
				'label'     => __( 'Text Color', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#FFFFFF',
				'selectors' => array(
					'{{WRAPPER}} .bw-hero-slide__button' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'button_padding',
			array(
				'label'      => __( 'Button Padding', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'default'    => array(
					'top'      => 16,
					'right'    => 28,
					'bottom'   => 16,
					'left'     => 28,
					'unit'     => 'px',
					'isLinked' => false,
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-hero-slide__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'buttons_horizontal_gap',
			array(
				'label'      => __( 'Button Horizontal Gap', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 80,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 16,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}}'                         => '--bw-hs-buttons-column-gap: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bw-hero-slide__buttons' => 'column-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'buttons_vertical_gap',
			array(
				'label'      => __( 'Button Vertical Gap', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 80,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 16,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}}'                         => '--bw-hs-buttons-row-gap: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bw-hero-slide__buttons' => 'row-gap: {{SIZE}}{{UNIT}};',
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
		$settings         = $this->get_settings_for_display();
		$mode             = $this->normalize_mode( $settings['mode'] ?? 'static' );
		$background_image = $settings['background_image']['url'] ?? '';
		$title            = isset( $settings['title'] ) ? trim( (string) $settings['title'] ) : '';
		$subtitle         = isset( $settings['subtitle'] ) ? trim( (string) $settings['subtitle'] ) : '';
		$buttons          = isset( $settings['buttons'] ) && is_array( $settings['buttons'] ) ? $settings['buttons'] : array();

		$wrapper_classes = array(
			'bw-hero-slide-wrapper',
			'bw-hs-mode--' . sanitize_html_class( $mode ),
			( ( $settings['button_glass_effect'] ?? 'yes' ) === 'yes' ) ? 'bw-hs-glass-on' : 'bw-hs-glass-off',
			( ( $settings['button_fill_enabled'] ?? '' ) === 'yes' ) ? 'bw-hs-fill-on' : 'bw-hs-fill-off',
		);

		$this->add_render_attribute(
			'wrapper',
			array(
				'class' => implode( ' ', $wrapper_classes ),
			)
		);

		$this->add_render_attribute(
			'section',
			array(
				'class' => 'bw-hero-slide',
			)
		);

		if ( $background_image ) {
			$this->add_render_attribute(
				'media',
				array(
					'class'                 => 'bw-hero-slide__media',
					'style'                 => sprintf( 'background-image: url(%s);', esc_url( $background_image ) ),
					'data-background-image' => esc_url( $background_image ),
				)
			);
		} else {
			$this->add_render_attribute(
				'media',
				array(
					'class' => 'bw-hero-slide__media bw-hero-slide__media--empty',
				)
			);
		}

		?>
		<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor render attributes are escaped internally. ?>
		<div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
			<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor render attributes are escaped internally. ?>
			<section <?php echo $this->get_render_attribute_string( 'section' ); ?>>
				<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor render attributes are escaped internally. ?>
				<div <?php echo $this->get_render_attribute_string( 'media' ); ?> aria-hidden="true"></div>
				<div class="bw-hero-slide__overlay" aria-hidden="true"></div>
				<div class="bw-hero-slide__inner">
					<div class="bw-hero-slide__content">
						<div class="bw-hero-slide__copy">
							<?php if ( '' !== trim( wp_strip_all_tags( $title ) ) ) : ?>
								<h1 class="bw-hero-slide__title"><?php echo wp_kses( $this->normalize_title_markup( $title ), $this->get_allowed_title_html() ); ?></h1>
							<?php endif; ?>

							<?php if ( '' !== $subtitle ) : ?>
								<div class="bw-hero-slide__subtitle"><?php echo esc_html( $subtitle ); ?></div>
							<?php endif; ?>
						</div>

						<?php if ( ! empty( $buttons ) ) : ?>
							<div class="bw-hero-slide__buttons">
								<?php foreach ( $buttons as $index => $button ) : ?>
									<?php $this->render_button( $button, $index ); ?>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</section>
		</div>
		<?php
	}

	/**
	 * Normalize mode value.
	 *
	 * @param string $mode Raw mode.
	 * @return string
	 */
	private function normalize_mode( $mode ) {
		return in_array( $mode, array( 'static', 'slide' ), true ) ? $mode : 'static';
	}

	/**
	 * Render one CTA button.
	 *
	 * @param array<string,mixed> $button Button settings.
	 * @param int                 $index  Button index.
	 * @return void
	 */
	private function render_button( $button, $index ) {
		$button_text = isset( $button['button_text'] ) ? trim( (string) $button['button_text'] ) : '';
		if ( '' === $button_text ) {
			return;
		}

		$link_data = $this->resolve_button_link_data( $button );
		$has_link  = ! empty( $link_data['url'] );
		$key       = 'hero_button_' . $index;

		$this->add_render_attribute(
			$key,
			array(
				'class' => 'bw-hero-slide__button',
			)
		);

		if ( $has_link ) {
			if ( 'manual_url' === $link_data['type'] && ! empty( $button['button_link'] ) && is_array( $button['button_link'] ) ) {
				$this->add_link_attributes( $key, $button['button_link'] );
			} else {
				$this->add_render_attribute( $key, 'href', esc_url( $link_data['url'] ) );
			}
		}

		$tag = $has_link ? 'a' : 'span';
		?>
		<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor render attributes are escaped internally. ?>
		<<?php echo esc_html( $tag ); ?> <?php echo $this->get_render_attribute_string( $key ); ?>>
			<span class="bw-hero-slide__button-label"><?php echo esc_html( $button_text ); ?></span>
		</<?php echo esc_html( $tag ); ?>>
		<?php
	}

	/**
	 * Resolve one button link.
	 *
	 * @param array<string,mixed> $button Button settings.
	 * @return array<string,string>
	 */
	private function resolve_button_link_data( $button ) {
		$link_type = isset( $button['link_type'] ) ? sanitize_key( (string) $button['link_type'] ) : 'manual_url';

		if ( 'manual_url' === $link_type ) {
			$url = '';
			if ( ! empty( $button['button_link'] ) && is_array( $button['button_link'] ) ) {
				$url = $button['button_link']['url'] ?? '';
			}

			return array(
				'type' => 'manual_url',
				'url'  => is_string( $url ) ? $url : '',
			);
		}

		if ( 'product_category' === $link_type ) {
			$term_id = isset( $button['product_category_term_id'] ) ? absint( $button['product_category_term_id'] ) : 0;
			if ( $term_id > 0 ) {
				$link = get_term_link( $term_id, 'product_cat' );
				if ( ! is_wp_error( $link ) ) {
					return array(
						'type' => $link_type,
						'url'  => $link,
					);
				}
			}
		}

		if ( 'post_category' === $link_type ) {
			$term_id = isset( $button['post_category_term_id'] ) ? absint( $button['post_category_term_id'] ) : 0;
			if ( $term_id > 0 ) {
				$link = get_term_link( $term_id, 'category' );
				if ( ! is_wp_error( $link ) ) {
					return array(
						'type' => $link_type,
						'url'  => $link,
					);
				}
			}
		}

		if ( 'post_type_archive' === $link_type ) {
			$post_type = isset( $button['archive_post_type'] ) ? sanitize_key( (string) $button['archive_post_type'] ) : '';
			$link      = $this->get_archive_link_for_post_type( $post_type );

			if ( $link ) {
				return array(
					'type' => $link_type,
					'url'  => $link,
				);
			}
		}

		return array(
			'type' => $link_type,
			'url'  => '',
		);
	}

	/**
	 * Return post category options.
	 *
	 * @return array<int,string>
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

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $options;
		}

		foreach ( $terms as $term ) {
			$options[ $term->term_id ] = $term->name;
		}

		return $options;
	}

	/**
	 * Return archive-capable post type options.
	 *
	 * @return array<string,string>
	 */
	private function get_archive_post_type_options() {
		$options = array(
			'post' => __( 'Blog Archive', 'bw-elementor-widgets' ),
		);

		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		if ( empty( $post_types ) || ! is_array( $post_types ) ) {
			return $options;
		}

		foreach ( $post_types as $post_type ) {
			if ( ! isset( $post_type->name ) || 'attachment' === $post_type->name || 'post' === $post_type->name ) {
				continue;
			}

			if ( empty( $post_type->has_archive ) ) {
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

			$options[ $post_type->name ] = sprintf(
				/* translators: %s is the post type label. */
				__( '%s Archive', 'bw-elementor-widgets' ),
				$label
			);
		}

		asort( $options );

		return $options;
	}

	/**
	 * Resolve an archive URL for a post type.
	 *
	 * @param string $post_type Post type slug.
	 * @return string
	 */
	private function get_archive_link_for_post_type( $post_type ) {
		if ( 'post' === $post_type ) {
			$page_for_posts = (int) get_option( 'page_for_posts' );
			if ( $page_for_posts > 0 ) {
				$link = get_permalink( $page_for_posts );

				return is_string( $link ) ? $link : '';
			}

			$link = get_post_type_archive_link( 'post' );

			return $link ? $link : home_url( '/' );
		}

		if ( ! $post_type || ! post_type_exists( $post_type ) ) {
			return '';
		}

		$link = get_post_type_archive_link( $post_type );

		return $link ? $link : '';
	}

	/**
	 * Allowed HTML for the hero title.
	 *
	 * @return array<string,array<string,bool>>
	 */
	private function get_allowed_title_html() {
		return array(
			'br'     => array(),
			'em'     => array(),
			'span'   => array(
				'class' => true,
				'style' => true,
			),
			'strong' => array(),
			'u'      => array(),
		);
	}

	/**
	 * Normalize title markup coming from the editor before sanitizing it.
	 *
	 * WYSIWYG content can wrap the string in paragraph tags, which would be invalid
	 * inside the single H1 used by this widget.
	 *
	 * @param string $title Raw title content.
	 * @return string
	 */
	private function normalize_title_markup( $title ) {
		$markup = trim( (string) $title );

		$markup = preg_replace( '#</p>\s*<p>#i', '<br>', $markup );
		$markup = preg_replace( '#^<p>|</p>$#i', '', $markup );

		return is_string( $markup ) ? trim( $markup ) : '';
	}
}
