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
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => __( 'Accordion Title', 'bw' ),
				'placeholder' => __( 'Enter accordion title', 'bw' ),
				'label_block' => true,
				'rows'        => 2,
			]
		);

		$this->add_control(
			'title_html_tag',
			[
				'label'   => __( 'Title HTML Tag', 'bw' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => [
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'DIV',
					'span' => 'SPAN',
				],
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

		$this->add_control(
			'icon_type',
			[
				'label'   => __( 'Icon Type', 'bw' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'plus_x',
				'options' => [
					'plus_x'    => __( 'Plus / X', 'bw' ),
					'arrow'     => __( 'Arrow', 'bw' ),
					'custom_svg' => __( 'Custom SVG', 'bw' ),
				],
			]
		);

		$this->add_control(
			'custom_svg',
			[
				'label'       => __( 'Custom SVG', 'bw' ),
				'type'        => Controls_Manager::TEXTAREA,
				'placeholder' => __( '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor">...</svg>', 'bw' ),
				'rows'        => 6,
				'condition'   => [
					'icon_type' => 'custom_svg',
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
			'closed_state_heading',
			[
				'type'            => Controls_Manager::HEADING,
				'label'           => __( 'Closed State', 'bw' ),
				'separator'       => 'before',
			]
		);

		$this->add_control(
			'closed_title_color',
			[
				'label'     => __( 'Closed Title Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#080808',
				'selectors' => [
					'{{WRAPPER}} .bw-accordion' => '--bw-accordion-title-color-closed: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'closed_header_background',
			[
				'label'     => __( 'Closed Background Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bw-accordion' => '--bw-accordion-header-bg-closed: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'open_state_heading',
			[
				'type'      => Controls_Manager::HEADING,
				'label'     => __( 'Open State', 'bw' ),
				'separator' => 'before',
			]
		);

		$this->add_control(
			'open_title_color',
			[
				'label'     => __( 'Open Title Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#777777',
				'selectors' => [
					'{{WRAPPER}} .bw-accordion' => '--bw-accordion-title-color-open: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'open_header_background',
			[
				'label'     => __( 'Open Background Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bw-accordion' => '--bw-accordion-header-bg-open: {{VALUE}};',
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
					'{{WRAPPER}} .bw-accordion' => '--bw-accordion-content-color: {{VALUE}};',
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

		$this->add_responsive_control(
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

		$this->add_responsive_control(
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
			'section_style_icon',
			[
				'label' => __( 'Icon', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'arrow_color',
			[
				'label'     => __( 'Icon Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bw-accordion' => '--bw-accordion-icon-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'arrow_size',
			[
				'label'      => __( 'Icon Size', 'bw' ),
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
					'size' => 20,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-accordion' => '--bw-accordion-icon-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'arrow_stroke_width',
			[
				'label'   => __( 'Icon Stroke Weight', 'bw' ),
				'type'    => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'   => [
					'px' => [
						'min'  => 1,
						'max'  => 4,
						'step' => 0.25,
					],
				],
				'default' => [
					'size' => 2,
					'unit' => 'px',
				],
				'selectors' => [
					'{{WRAPPER}} .bw-accordion' => '--bw-accordion-icon-stroke-width: {{SIZE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$title     = isset( $settings['accordion_title'] ) ? trim( (string) $settings['accordion_title'] ) : '';
		$title_tag = $this->get_allowed_title_tag( isset( $settings['title_html_tag'] ) ? $settings['title_html_tag'] : 'h3' );
		$content   = isset( $settings['accordion_content'] ) ? (string) $settings['accordion_content'] : '';
		$state     = isset( $settings['initial_state'] ) && 'open' === $settings['initial_state'] ? 'open' : 'closed';
		$is_open   = 'open' === $state;
		$icon_type = isset( $settings['icon_type'] ) ? sanitize_key( (string) $settings['icon_type'] ) : 'plus_x';
		$widget_id = $this->get_id();

		if ( '' === $title ) {
			$title = __( 'Accordion Title', 'bw' );
		}

		if ( ! in_array( $icon_type, [ 'plus_x', 'arrow', 'custom_svg' ], true ) ) {
			$icon_type = 'plus_x';
		}

		if (
			class_exists( '\Elementor\Plugin' )
			&& isset( \Elementor\Plugin::instance()->frontend )
			&& method_exists( \Elementor\Plugin::instance()->frontend, 'parse_text_editor' )
		) {
			$content = \Elementor\Plugin::instance()->frontend->parse_text_editor( $content );
		} else {
			$content = wpautop( $content );
		}

		$content = wp_kses_post( $content );
		$icon    = $this->get_icon_markup( $icon_type, isset( $settings['custom_svg'] ) ? (string) $settings['custom_svg'] : '' );

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
				<<?php echo esc_attr( $title_tag ); ?> class="bw-accordion__title"><?php echo esc_html( $title ); ?></<?php echo esc_attr( $title_tag ); ?>>
				<?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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

	private function get_allowed_title_tag( $tag ) {
		$allowed_tags = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span' ];
		$tag          = sanitize_key( (string) $tag );

		return in_array( $tag, $allowed_tags, true ) ? $tag : 'h3';
	}

	private function get_icon_markup( $icon_type, $custom_svg ) {
		if ( 'custom_svg' === $icon_type ) {
			$sanitized_svg = $this->sanitize_svg_markup( $custom_svg );

			if ( '' !== $sanitized_svg ) {
				return sprintf(
					'<span class="bw-accordion__icon bw-accordion__icon--custom" aria-hidden="true"><span class="bw-accordion__icon-svg">%s</span></span>',
					$sanitized_svg
				);
			}

			$icon_type = 'arrow';
		}

		if ( 'arrow' === $icon_type ) {
			return '<span class="bw-accordion__icon bw-accordion__icon--arrow" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="m6 9 6 6 6-6" fill="none"></path></svg></span>';
		}

		return '<span class="bw-accordion__icon bw-accordion__icon--plus-x" aria-hidden="true"></span>';
	}

	private function sanitize_svg_markup( $svg_content ) {
		if ( empty( $svg_content ) || false === stripos( (string) $svg_content, '<svg' ) ) {
			return '';
		}

		$allowed_tags = [
			'svg'      => [
				'class'                 => true,
				'xmlns'                 => true,
				'width'                 => true,
				'height'                => true,
				'viewbox'               => true,
				'aria-hidden'           => true,
				'role'                  => true,
				'focusable'             => true,
				'fill'                  => true,
				'stroke'                => true,
				'stroke-width'          => true,
				'stroke-linecap'        => true,
				'stroke-linejoin'       => true,
				'stroke-miterlimit'     => true,
				'preserveaspectratio'   => true,
				'fill-rule'             => true,
				'clip-rule'             => true,
				'style'                 => true,
			],
			'g'        => [
				'fill'             => true,
				'stroke'           => true,
				'stroke-width'     => true,
				'stroke-linecap'   => true,
				'stroke-linejoin'  => true,
				'stroke-miterlimit'=> true,
				'class'            => true,
				'fill-rule'        => true,
				'clip-rule'        => true,
				'transform'        => true,
				'style'            => true,
			],
			'path'     => [
				'd'                => true,
				'fill'             => true,
				'stroke'           => true,
				'stroke-width'     => true,
				'stroke-linecap'   => true,
				'stroke-linejoin'  => true,
				'stroke-miterlimit'=> true,
				'class'            => true,
				'fill-rule'        => true,
				'clip-rule'        => true,
				'transform'        => true,
				'style'            => true,
			],
			'circle'   => [ 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'class' => true, 'style' => true ],
			'ellipse'  => [ 'cx' => true, 'cy' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'class' => true, 'style' => true ],
			'rect'     => [ 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'class' => true, 'style' => true ],
			'polygon'  => [ 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'class' => true, 'style' => true ],
			'polyline' => [ 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'class' => true, 'style' => true ],
			'line'     => [ 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'class' => true, 'style' => true ],
			'title'    => [],
			'desc'     => [],
		];

		return wp_kses( (string) $svg_content, $allowed_tags );
	}
}
