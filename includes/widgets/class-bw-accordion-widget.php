<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Widget_Bw_Accordion extends Widget_Base {

	public function get_name() {
		return 'bw-accordion';
	}

	public function get_title() {
		return __( 'BW Accordion', 'bw' );
	}

	public function get_icon() {
		return 'eicon-accordion';
	}

	public function get_categories() {
		return [ 'blackwork' ];
	}

	public function get_style_depends() {
		if ( function_exists( 'bw_register_widget_assets' ) && ( ! wp_style_is( 'bw-accordion-style', 'registered' ) || ! wp_script_is( 'bw-accordion-script', 'registered' ) ) ) {
			bw_register_widget_assets( 'accordion' );
		}

		return [ 'bw-accordion-style' ];
	}

	public function get_script_depends() {
		if ( function_exists( 'bw_register_widget_assets' ) && ( ! wp_style_is( 'bw-accordion-style', 'registered' ) || ! wp_script_is( 'bw-accordion-script', 'registered' ) ) ) {
			bw_register_widget_assets( 'accordion' );
		}

		return [ 'bw-accordion-script' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'Content', 'bw' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'accordion_title',
			[
				'label'       => __( 'Accordion Title', 'bw' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Accordion Title', 'bw' ),
				'placeholder' => __( 'Enter accordion title', 'bw' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'accordion_content',
			[
				'label'       => __( 'Accordion Content', 'bw' ),
				'type'        => Controls_Manager::WYSIWYG,
				'default'     => '',
				'placeholder' => __( 'Write the accordion content here', 'bw' ),
			]
		);

		$this->add_control(
			'initial_state',
			[
				'label'   => __( 'Initial State', 'bw' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'closed',
				'options' => [
					'closed' => __( 'Closed by Default', 'bw' ),
					'open'   => __( 'Open by Default', 'bw' ),
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_title',
			[
				'label' => __( 'Title', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .bw-accordion__title',
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => __( 'Text Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bw-accordion__title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'title_padding',
			[
				'label'      => __( 'Padding', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'default'    => [
					'top'    => 0,
					'right'  => 0,
					'bottom' => 0,
					'left'   => 0,
					'unit'   => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-accordion__header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_content',
			[
				'label' => __( 'Content', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'content_typography',
				'selector' => '{{WRAPPER}} .bw-accordion__content',
			]
		);

		$this->add_control(
			'content_color',
			[
				'label'     => __( 'Text Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bw-accordion__content' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'content_padding',
			[
				'label'      => __( 'Padding', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'default'    => [
					'top'    => 16,
					'right'  => 0,
					'bottom' => 0,
					'left'   => 0,
					'unit'   => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-accordion__panel-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_divider',
			[
				'label' => __( 'Divider', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'divider_color',
			[
				'label'     => __( 'Divider Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bw-accordion' => '--bw-accordion-divider-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'divider_thickness',
			[
				'label'      => __( 'Divider Thickness', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 10,
						'step' => 1,
					],
				],
				'default'    => [
					'size' => 1,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-accordion' => '--bw-accordion-divider-thickness: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'divider_spacing',
			[
				'label'      => __( 'Divider Spacing', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 80,
						'step' => 1,
					],
				],
				'default'    => [
					'size' => 16,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-accordion' => '--bw-accordion-divider-spacing: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_arrow',
			[
				'label' => __( 'Arrow', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'arrow_color',
			[
				'label'     => __( 'Arrow Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bw-accordion' => '--bw-accordion-arrow-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'arrow_size',
			[
				'label'      => __( 'Arrow Size', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 8,
						'max' => 40,
						'step' => 1,
					],
				],
				'default'    => [
					'size' => 16,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-accordion' => '--bw-accordion-arrow-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'arrow_stroke_width',
			[
				'label'   => __( 'Arrow Stroke Weight', 'bw' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 6,
				'step'    => 0.5,
				'default' => 2,
				'selectors' => [
					'{{WRAPPER}} .bw-accordion' => '--bw-accordion-arrow-stroke-width: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$title    = isset( $settings['accordion_title'] ) ? trim( (string) $settings['accordion_title'] ) : '';
		$content  = isset( $settings['accordion_content'] ) ? (string) $settings['accordion_content'] : '';
		$state    = isset( $settings['initial_state'] ) && 'open' === $settings['initial_state'] ? 'open' : 'closed';
		$is_open  = 'open' === $state;
		$widget_id = $this->get_id();

		if ( '' === $title ) {
			$title = __( 'Accordion Title', 'bw' );
		}

		if ( class_exists( '\Elementor\Utils' ) ) {
			$content = \Elementor\Utils::parse_text_editor( $content );
		} else {
			$content = wpautop( $content );
		}

		$content = wp_kses_post( $content );

		$this->add_render_attribute( 'wrapper', 'class', [
			'bw-accordion',
			$is_open ? 'is-open' : 'is-closed',
		] );
		$this->add_render_attribute( 'wrapper', 'data-bw-accordion-id', $widget_id );
		$this->add_render_attribute( 'header', 'class', 'bw-accordion__header' );
		$this->add_render_attribute( 'header', 'type', 'button' );
		$this->add_render_attribute( 'header', 'aria-expanded', $is_open ? 'true' : 'false' );
		$this->add_render_attribute( 'header', 'aria-controls', 'bw-accordion-panel-' . $widget_id );
		$this->add_render_attribute( 'header', 'id', 'bw-accordion-header-' . $widget_id );
		$this->add_render_attribute( 'panel', 'class', 'bw-accordion__panel' );
		$this->add_render_attribute( 'panel', 'id', 'bw-accordion-panel-' . $widget_id );
		$this->add_render_attribute( 'panel', 'aria-labelledby', 'bw-accordion-header-' . $widget_id );
		$this->add_render_attribute( 'panel', 'aria-hidden', $is_open ? 'false' : 'true' );

		if ( ! $is_open ) {
			$this->add_render_attribute( 'panel', 'style', 'height:0;overflow:hidden;opacity:0;' );
		}

		?>
		<div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
			<button <?php echo $this->get_render_attribute_string( 'header' ); ?>>
				<span class="bw-accordion__title"><?php echo esc_html( $title ); ?></span>
				<span class="bw-accordion__icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
						<path d="m6 9 6 6 6-6" fill="none"></path>
					</svg>
				</span>
			</button>
			<div class="bw-accordion__divider bw-accordion__divider--top" aria-hidden="true"></div>
			<div <?php echo $this->get_render_attribute_string( 'panel' ); ?>>
				<div class="bw-accordion__panel-inner">
					<div class="bw-accordion__content">
						<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<div class="bw-accordion__divider bw-accordion__divider--bottom" aria-hidden="true"></div>
				</div>
			</div>
		</div>
		<?php
	}
}
