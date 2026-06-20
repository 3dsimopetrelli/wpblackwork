<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BW_License_Table_Widget extends Widget_Base {

	public function get_name() {
		return 'bw-license-table';
	}

	public function get_title() {
		return __( 'BW License Table', 'bw' );
	}

	public function get_icon() {
		return 'eicon-table-of-contents';
	}

	public function get_categories() {
		return [ 'blackwork' ];
	}

	public function get_style_depends() {
		if ( function_exists( 'bw_register_widget_assets' ) && ( ! wp_style_is( 'bw-license-table-style', 'registered' ) || ! wp_script_is( 'bw-license-table-script', 'registered' ) ) ) {
			bw_register_widget_assets( 'license-table' );
		}

		return [ 'bw-license-table-style' ];
	}

	public function get_script_depends() {
		if ( function_exists( 'bw_register_widget_assets' ) && ( ! wp_style_is( 'bw-license-table-style', 'registered' ) || ! wp_script_is( 'bw-license-table-script', 'registered' ) ) ) {
			bw_register_widget_assets( 'license-table' );
		}

		return [ 'bw-license-table-script' ];
	}

	protected function register_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	private function register_content_controls() {
		$this->start_controls_section(
			'section_license_header',
			[
				'label' => __( 'License Header', 'bw' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'license_title',
			[
				'label'       => __( 'License Title', 'bw' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Commercial License', 'bw' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'license_description',
			[
				'label'       => __( 'Short Description', 'bw' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => __( 'For professional, business, editorial, educational, marketing, and client projects where the artwork supports a larger finished work.', 'bw' ),
				'rows'        => 4,
				'placeholder' => __( 'Describe what this license covers.', 'bw' ),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_license_rows',
			[
				'label' => __( 'License Rows', 'bw' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'rows_preset',
			[
				'label'       => __( 'Starting Rows', 'bw' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'commercial',
				'options'     => [
					'commercial' => __( 'Commercial License Defaults', 'bw' ),
					'empty'      => __( 'Empty Table / Clear Default Rows', 'bw' ),
				],
				'description' => __( 'Use the default Commercial preset or switch to an empty table and add your own rows manually.', 'bw' ),
			]
		);

		$allowed_repeater    = $this->build_license_rows_repeater(
			__( 'Commercial Use', 'bw' ),
			'✓',
			__( 'Use Blackwork assets in professional or business projects such as websites, brochures, advertising campaigns, presentations, or company materials.', 'bw' )
		);
		$restricted_repeater = $this->build_license_rows_repeater(
			__( 'Products for Sale', 'bw' ),
			'✕',
			__( 'The artwork may not be used as the main value of products being sold, such as posters, notebooks, art prints, calendars, card decks, or digital products.', 'bw' )
		);

		$this->add_control(
			'allowed_license_rows_heading',
			[
				'label' => __( 'Allowed License Rows', 'bw' ),
				'type'  => Controls_Manager::HEADING,
			]
		);

		$this->add_control(
			'allowed_license_rows',
			[
				'label'       => __( 'Allowed License Rows', 'bw' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $allowed_repeater->get_controls(),
				'default'     => [],
				'title_field' => '{{{ feature_title }}}',
			]
		);

		$this->add_control(
			'show_allowed_divider',
			[
				'label'        => __( 'Show Allowed Divider', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'allowed_divider_text',
			[
				'label'       => __( 'Divider Text', 'bw' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Allowed Uses', 'bw' ),
				'label_block' => true,
				'condition'   => [
					'show_allowed_divider' => 'yes',
				],
			]
		);

		$this->add_control(
			'restricted_license_rows_heading',
			[
				'label'     => __( 'Restricted License Rows', 'bw' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'restricted_license_rows',
			[
				'label'       => __( 'Restricted License Rows', 'bw' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $restricted_repeater->get_controls(),
				'default'     => [],
				'title_field' => '{{{ feature_title }}}',
			]
		);

		$this->add_control(
			'show_restricted_divider',
			[
				'label'        => __( 'Show Restricted Divider', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'restricted_divider_text',
			[
				'label'       => __( 'Restricted Section Title', 'bw' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Cannot Be Used For', 'bw' ),
				'label_block' => true,
				'condition'   => [
					'show_restricted_divider' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_footer_cta',
			[
				'label' => __( 'Footer CTA', 'bw' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'show_footer_cta',
			[
				'label'        => __( 'Show Footer CTA', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'footer_cta_text',
			[
				'label'       => __( 'Footer Text', 'bw' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Need a custom license?', 'bw' ),
				'label_block' => true,
				'condition'   => [
					'show_footer_cta' => 'yes',
				],
			]
		);

		$this->add_control(
			'footer_cta_url',
			[
				'label'         => __( 'Footer Link URL', 'bw' ),
				'type'          => Controls_Manager::URL,
				'placeholder'   => __( 'https://your-link.com', 'bw' ),
				'show_external' => false,
				'default'       => [
					'url' => '',
				],
				'condition'     => [
					'show_footer_cta' => 'yes',
				],
			]
		);

		$this->add_control(
			'footer_cta_new_tab',
			[
				'label'        => __( 'Open In New Tab', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw' ),
				'label_off'    => __( 'No', 'bw' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => [
					'show_footer_cta' => 'yes',
				],
			]
		);

		$this->add_control(
			'stick_footer_cta_to_bottom',
			[
				'label'        => __( 'Stick Footer CTA To Bottom', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => [
					'show_footer_cta' => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_style_controls() {
		$this->start_controls_section(
			'section_wrapper_style',
			[
				'label' => __( 'Wrapper / Card', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'wrapper_background_color',
			[
				'label'     => __( 'Background Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-card-bg: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'wrapper_border',
				'selector' => '{{WRAPPER}} .bw-license-table-widget',
			]
		);

		$this->add_responsive_control(
			'wrapper_border_radius',
			[
				'label'      => __( 'Border Radius', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'wrapper_padding',
			[
				'label'      => __( 'Padding', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'default'    => [
					'top'      => 24,
					'right'    => 24,
					'bottom'   => 24,
					'left'     => 24,
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'wrapper_box_shadow',
				'selector' => '{{WRAPPER}} .bw-license-table-widget',
			]
		);

		$this->add_responsive_control(
			'rows_gap',
			[
				'label'      => __( 'Gap Between Rows', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 40,
					],
				],
				'default'    => [
					'size' => 12,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-row-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'card_minimum_height',
			[
				'label'      => __( 'Card Minimum Height', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vh' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 1600,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
					'vh' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-min-height: {{SIZE}}{{UNIT}}; min-height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_header_style',
			[
				'label' => __( 'Header', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'header_title_typography',
				'selector' => '{{WRAPPER}} .bw-license-table-widget__title',
			]
		);

		$this->add_control(
			'header_title_color',
			[
				'label'     => __( 'Title Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#080808',
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-title-color: {{VALUE}};',
					'{{WRAPPER}} .bw-license-table-widget .bw-license-table-widget__title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'header_title_alignment',
			[
				'label'     => __( 'Title Alignment', 'bw' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'    => [
						'title' => __( 'Left', 'bw' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center'  => [
						'title' => __( 'Center', 'bw' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'   => [
						'title' => __( 'Right', 'bw' ),
						'icon'  => 'eicon-text-align-right',
					],
					'justify' => [
						'title' => __( 'Justify', 'bw' ),
						'icon'  => 'eicon-text-align-justify',
					],
				],
				'default'   => 'left',
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-title-align: {{VALUE}};',
					'{{WRAPPER}} .bw-license-table-widget .bw-license-table-widget__title' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'header_title_padding',
			[
				'label'      => __( 'Title Padding', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-title-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .bw-license-table-widget .bw-license-table-widget__title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'header_description_typography',
				'selector' => '{{WRAPPER}} .bw-license-table-widget__description',
			]
		);

		$this->add_control(
			'header_description_color',
			[
				'label'     => __( 'Description Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#666666',
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-description-color: {{VALUE}};',
					'{{WRAPPER}} .bw-license-table-widget .bw-license-table-widget__description' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'header_description_alignment',
			[
				'label'     => __( 'Description Alignment', 'bw' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'    => [
						'title' => __( 'Left', 'bw' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center'  => [
						'title' => __( 'Center', 'bw' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'   => [
						'title' => __( 'Right', 'bw' ),
						'icon'  => 'eicon-text-align-right',
					],
					'justify' => [
						'title' => __( 'Justify', 'bw' ),
						'icon'  => 'eicon-text-align-justify',
					],
				],
				'default'   => 'left',
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-description-align: {{VALUE}};',
					'{{WRAPPER}} .bw-license-table-widget .bw-license-table-widget__description' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'header_description_padding',
			[
				'label'      => __( 'Description Padding', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-description-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .bw-license-table-widget .bw-license-table-widget__description' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'header_spacing',
			[
				'label'      => __( 'Header Spacing', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 60,
					],
				],
				'default'    => [
					'size' => 20,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-header-spacing: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_divider_style',
			[
				'label' => __( 'Divider', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'divider_typography',
				'selector' => '{{WRAPPER}} .bw-license-table-widget__divider-label',
			]
		);

		$this->add_control(
			'divider_text_color',
			[
				'label'     => __( 'Text Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#080808',
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-divider-color: {{VALUE}};',
					'{{WRAPPER}} .bw-license-table-widget .bw-license-table-widget__divider-label' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'divider_alignment',
			[
				'label'     => __( 'Alignment', 'bw' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'    => [
						'title' => __( 'Left', 'bw' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center'  => [
						'title' => __( 'Center', 'bw' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'   => [
						'title' => __( 'Right', 'bw' ),
						'icon'  => 'eicon-text-align-right',
					],
					'justify' => [
						'title' => __( 'Justify', 'bw' ),
						'icon'  => 'eicon-text-align-justify',
					],
				],
				'default'   => 'left',
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-divider-align: {{VALUE}};',
					'{{WRAPPER}} .bw-license-table-widget .bw-license-table-widget__divider' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'divider_padding',
			[
				'label'      => __( 'Padding', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-divider-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .bw-license-table-widget .bw-license-table-widget__divider' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'divider_margin',
			[
				'label'      => __( 'Margin', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-divider-margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .bw-license-table-widget .bw-license-table-widget__divider' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'restricted_title_margin_top',
			[
				'label'      => __( 'Restricted Title Margin Top', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'rem', 'em' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 120,
					],
				],
				'default'    => [
					'size' => 24,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-restricted-divider-margin-top: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bw-license-table-widget .bw-license-table-widget__section--restricted > .bw-license-table-widget__divider' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'divider_line_enabled',
			[
				'label'        => __( 'Divider Line', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'divider_line_top_enabled',
			[
				'label'        => __( 'Top Line', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => [
					'divider_line_enabled' => 'yes',
				],
			]
		);

		$this->add_control(
			'divider_line_bottom_enabled',
			[
				'label'        => __( 'Bottom Line', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => [
					'divider_line_enabled' => 'yes',
				],
			]
		);

		$this->add_control(
			'divider_line_color',
			[
				'label'     => __( 'Divider Line Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#dddddd',
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-divider-line-color: {{VALUE}};',
				],
				'condition' => [
					'divider_line_enabled' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'divider_line_thickness',
			[
				'label'      => __( 'Divider Thickness', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 8,
					],
				],
				'default'    => [
					'size' => 1,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-divider-line-thickness: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'divider_line_enabled' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'divider_line_spacing',
			[
				'label'      => __( 'Divider Spacing', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 40,
					],
				],
				'default'    => [
					'size' => 10,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-divider-line-spacing: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'divider_line_enabled' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_feature_style',
			[
				'label' => __( 'Feature Column', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'feature_typography',
				'selector' => '{{WRAPPER}} .bw-license-table-widget__feature',
			]
		);

		$this->add_control(
			'feature_text_color',
			[
				'label'     => __( 'Text Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#080808',
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-feature-color: {{VALUE}};',
					'{{WRAPPER}} .bw-license-table-widget .bw-license-table-widget__feature, {{WRAPPER}} .bw-license-table-widget .bw-license-table-widget__feature *' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'feature_background_color',
			[
				'label'     => __( 'Background Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-feature-bg: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'feature_padding',
			[
				'label'      => __( 'Padding', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-feature-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'feature_border_radius',
			[
				'label'      => __( 'Border Radius', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-feature-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_permission_style',
			[
				'label' => __( 'Permission Column', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'permission_typography',
				'selector' => '{{WRAPPER}} .bw-license-table-widget__permission',
			]
		);

		$this->add_control(
			'permission_text_color',
			[
				'label'     => __( 'Text Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#080808',
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-permission-color: {{VALUE}};',
					'{{WRAPPER}} .bw-license-table-widget .bw-license-table-widget__permission, {{WRAPPER}} .bw-license-table-widget .bw-license-table-widget__permission *' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'permission_background_color',
			[
				'label'     => __( 'Background Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-permission-bg: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'permission_padding',
			[
				'label'      => __( 'Padding', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-permission-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'permission_border_radius',
			[
				'label'      => __( 'Border Radius', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-permission-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_example_style',
			[
				'label' => __( 'Example / Tooltip Column', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'example_typography',
				'selector' => '{{WRAPPER}} .bw-license-table-widget__example, {{WRAPPER}} .bw-license-table-widget__tooltip, {{WRAPPER}} .bw-license-table-widget__tooltip-mobile',
			]
		);

		$this->add_control(
			'example_text_color',
			[
				'label'     => __( 'Text Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#444444',
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-example-color: {{VALUE}}; --bw-license-table-tooltip-trigger-color: {{VALUE}};',
					'{{WRAPPER}} .bw-license-table-widget .bw-license-table-widget__example, {{WRAPPER}} .bw-license-table-widget .bw-license-table-widget__example *' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'example_background_color',
			[
				'label'     => __( 'Background Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-example-bg: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'example_padding',
			[
				'label'      => __( 'Padding', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-example-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'example_border_radius',
			[
				'label'      => __( 'Border Radius', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-example-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'tooltip_background_color',
			[
				'label'     => __( 'Tooltip Background Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#080808',
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-tooltip-bg: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'tooltip_text_color',
			[
				'label'     => __( 'Tooltip Text Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-tooltip-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'tooltip_trigger_color',
			[
				'label'     => __( 'Tooltip Trigger Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#666666',
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-tooltip-trigger-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'tooltip_trigger_size',
			[
				'label'      => __( 'Tooltip Trigger Size', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 10,
						'max' => 40,
					],
				],
				'default'    => [
					'size' => 15,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-tooltip-trigger-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'tooltip_trigger_background',
			[
				'label'     => __( 'Tooltip Trigger Background', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-tooltip-trigger-bg: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'tooltip_trigger_border_radius',
			[
				'label'      => __( 'Tooltip Trigger Border Radius', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-tooltip-trigger-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'tooltip_border_radius',
			[
				'label'      => __( 'Tooltip Border Radius', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-tooltip-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'tooltip_width',
			[
				'label'      => __( 'Tooltip Width', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vw' ],
				'range'      => [
					'px' => [
						'min' => 160,
						'max' => 520,
					],
				],
				'default'    => [
					'size' => 280,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-tooltip-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_row_style',
			[
				'label' => __( 'Rows', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'row_background_color',
			[
				'label'     => __( 'Row Background Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-row-bg: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'enable_alternate_rows',
			[
				'label'        => __( 'Alternate Row Background', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'alternate_row_background_color',
			[
				'label'     => __( 'Alternate Row Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f7f7f7',
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-row-alt-bg: {{VALUE}};',
				],
				'condition' => [
					'enable_alternate_rows' => 'yes',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'row_border',
				'selector' => '{{WRAPPER}} .bw-license-table-widget__row',
			]
		);

		$this->add_responsive_control(
			'row_border_radius',
			[
				'label'      => __( 'Border Radius', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-row-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'row_padding',
			[
				'label'      => __( 'Padding', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'default'    => [
					'top'      => 18,
					'right'    => 18,
					'bottom'   => 18,
					'left'     => 18,
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-row-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'row_inner_gap',
			[
				'label'      => __( 'Row Gap', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 48,
					],
				],
				'default'    => [
					'size' => 16,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-column-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_footer_style',
			[
				'label' => __( 'Footer CTA', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'footer_typography',
				'selector' => '{{WRAPPER}} .bw-license-table-widget__footer-link, {{WRAPPER}} .bw-license-table-widget__footer-text',
			]
		);

		$this->add_control(
			'footer_text_color',
			[
				'label'     => __( 'Footer Text Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#080808',
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-footer-color: {{VALUE}};',
					'{{WRAPPER}} .bw-license-table-widget .bw-license-table-widget__footer-link, {{WRAPPER}} .bw-license-table-widget .bw-license-table-widget__footer-text' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'footer_hover_color',
			[
				'label'     => __( 'Footer Hover Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#666666',
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-footer-hover-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'footer_alignment',
			[
				'label'     => __( 'Footer Alignment', 'bw' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'    => [
						'title' => __( 'Left', 'bw' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center'  => [
						'title' => __( 'Center', 'bw' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'   => [
						'title' => __( 'Right', 'bw' ),
						'icon'  => 'eicon-text-align-right',
					],
					'justify' => [
						'title' => __( 'Justify', 'bw' ),
						'icon'  => 'eicon-text-align-justify',
					],
				],
				'default'   => 'left',
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-footer-align: {{VALUE}};',
					'{{WRAPPER}} .bw-license-table-widget .bw-license-table-widget__footer' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'footer_top_padding',
			[
				'label'      => __( 'Footer Top Padding', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'rem', 'em' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 120,
					],
				],
				'default'    => [
					'size' => 20,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-footer-padding-top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'footer_bottom_padding',
			[
				'label'      => __( 'Footer Bottom Padding', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'rem', 'em' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 120,
					],
				],
				'default'    => [
					'size' => 0,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-footer-padding-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'footer_divider_color',
			[
				'label'     => __( 'Footer Divider Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#dddddd',
				'selectors' => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-footer-divider-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'footer_divider_thickness',
			[
				'label'      => __( 'Footer Divider Thickness', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 8,
					],
				],
				'default'    => [
					'size' => 1,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-license-table-widget' => '--bw-license-table-footer-divider-thickness: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$sections = $this->get_effective_sections( $settings );
		$allowed_rows = $sections['allowed'];
		$restricted_rows = $sections['restricted'];

		if ( empty( $allowed_rows ) && empty( $restricted_rows ) ) {
			return;
		}

		$wrapper_classes = [ 'bw-license-table-widget' ];

		if ( 'yes' === ( isset( $settings['enable_alternate_rows'] ) ? $settings['enable_alternate_rows'] : '' ) ) {
			$wrapper_classes[] = 'bw-license-table-widget--alternate-rows';
		}

		if ( isset( $settings['stick_footer_cta_to_bottom'] ) && 'yes' === $settings['stick_footer_cta_to_bottom'] ) {
			$wrapper_classes[] = 'bw-license-table-widget--footer-sticky';
		}

		$this->add_render_attribute(
			'wrapper',
			[
				'class' => $wrapper_classes,
			]
		);

		$title       = isset( $settings['license_title'] ) ? trim( (string) $settings['license_title'] ) : '';
		$description = isset( $settings['license_description'] ) ? trim( (string) $settings['license_description'] ) : '';
		$show_allowed_divider    = isset( $settings['show_allowed_divider'] ) && 'yes' === $settings['show_allowed_divider'];
		$show_restricted_divider = isset( $settings['show_restricted_divider'] ) && 'yes' === $settings['show_restricted_divider'];
		$allowed_divider_text    = isset( $settings['allowed_divider_text'] ) ? trim( (string) $settings['allowed_divider_text'] ) : '';
		$restricted_divider_text = isset( $settings['restricted_divider_text'] ) ? trim( (string) $settings['restricted_divider_text'] ) : '';
		$divider_classes         = $this->get_divider_classes( $settings );
		$show_footer_cta         = isset( $settings['show_footer_cta'] ) && 'yes' === $settings['show_footer_cta'];
		$footer_cta_text         = isset( $settings['footer_cta_text'] ) ? trim( (string) $settings['footer_cta_text'] ) : '';
		$footer_cta_url          = isset( $settings['footer_cta_url']['url'] ) ? trim( (string) $settings['footer_cta_url']['url'] ) : '';
		$footer_new_tab          = isset( $settings['footer_cta_new_tab'] ) && 'yes' === $settings['footer_cta_new_tab'];

		?>
		<div <?php echo $this->get_render_attribute_string( 'wrapper' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="bw-license-table-widget__body">
				<?php if ( '' !== $title || '' !== $description ) : ?>
					<div class="bw-license-table-widget__header">
						<?php if ( '' !== $title ) : ?>
							<h3 class="bw-license-table-widget__title"><?php echo esc_html( $title ); ?></h3>
						<?php endif; ?>

						<?php if ( '' !== $description ) : ?>
							<div class="bw-license-table-widget__description"><?php echo wp_kses_post( nl2br( esc_html( $description ) ) ); ?></div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $allowed_rows ) ) : ?>
					<div class="bw-license-table-widget__section bw-license-table-widget__section--allowed">
						<?php if ( $show_allowed_divider && '' !== $allowed_divider_text ) : ?>
							<div class="<?php echo esc_attr( implode( ' ', $divider_classes ) ); ?>">
								<span class="bw-license-table-widget__divider-label"><?php echo esc_html( $allowed_divider_text ); ?></span>
							</div>
						<?php endif; ?>
						<div class="bw-license-table-widget__rows">
							<?php $this->render_rows_markup( $allowed_rows, 'allowed' ); ?>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $restricted_rows ) ) : ?>
					<div class="bw-license-table-widget__section bw-license-table-widget__section--restricted">
						<?php if ( $show_restricted_divider && '' !== $restricted_divider_text ) : ?>
							<div class="<?php echo esc_attr( implode( ' ', $divider_classes ) ); ?>">
								<span class="bw-license-table-widget__divider-label"><?php echo esc_html( $restricted_divider_text ); ?></span>
							</div>
						<?php endif; ?>
						<div class="bw-license-table-widget__rows">
							<?php $this->render_rows_markup( $restricted_rows, 'restricted' ); ?>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $show_footer_cta && '' !== $footer_cta_text ) : ?>
				<div class="bw-license-table-widget__footer">
					<?php if ( '' !== $footer_cta_url ) : ?>
						<a
							class="bw-license-table-widget__footer-link"
							href="<?php echo esc_url( $footer_cta_url ); ?>"
							<?php if ( $footer_new_tab ) : ?>
								target="_blank" rel="noopener noreferrer"
							<?php endif; ?>
						><?php echo esc_html( $footer_cta_text ); ?></a>
					<?php else : ?>
						<span class="bw-license-table-widget__footer-text"><?php echo esc_html( $footer_cta_text ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	private function get_default_allowed_rows() {
		return [
			[
				'feature_title'    => __( 'Commercial Use', 'bw' ),
				'permission_text'  => '✓',
				'explanation_text' => __( 'Use Blackwork assets in professional or business projects such as websites, brochures, advertising campaigns, presentations, or company materials.', 'bw' ),
				'use_tooltip'      => 'yes',
			],
			[
				'feature_title'    => __( 'Client Work', 'bw' ),
				'permission_text'  => '✓',
				'explanation_text' => __( 'Create projects for paying clients, including branding, packaging, editorial layouts, websites, and marketing materials.', 'bw' ),
				'use_tooltip'      => 'yes',
			],
			[
				'feature_title'    => __( 'Advertising & Marketing', 'bw' ),
				'permission_text'  => '✓',
				'explanation_text' => __( 'Use assets in social media campaigns, newsletters, online advertising, flyers, banners, and promotional content.', 'bw' ),
				'use_tooltip'      => 'yes',
			],
			[
				'feature_title'    => __( 'Websites & Social Media', 'bw' ),
				'permission_text'  => '✓',
				'explanation_text' => __( 'Publish assets on websites, blogs, e-commerce stores, newsletters, Instagram, Facebook, LinkedIn, and other platforms.', 'bw' ),
				'use_tooltip'      => 'yes',
			],
			[
				'feature_title'    => __( 'Books & Editorial Projects', 'bw' ),
				'permission_text'  => '✓',
				'explanation_text' => __( 'Use illustrations within books, magazines, newspapers, journals, reports, and editorial publications.', 'bw' ),
				'use_tooltip'      => 'yes',
			],
			[
				'feature_title'    => __( 'Educational Projects', 'bw' ),
				'permission_text'  => '✓',
				'explanation_text' => __( 'Use assets for teaching, research, museum content, academic publications, presentations, and educational materials.', 'bw' ),
				'use_tooltip'      => 'yes',
			],
			[
				'feature_title'    => __( 'Branding & Packaging', 'bw' ),
				'permission_text'  => '✓',
				'explanation_text' => __( 'Include assets in visual identities, labels, packaging, and commercial brand materials.', 'bw' ),
				'use_tooltip'      => 'yes',
			],
			[
				'feature_title'    => __( 'Print Runs', 'bw' ),
				'permission_text'  => __( '✓ Up to 5,000 Units', 'bw' ),
				'explanation_text' => __( 'Produce up to 5,000 physical copies such as books, brochures, magazines, catalogs, or packaging materials where the artwork supports a larger finished work.', 'bw' ),
				'use_tooltip'      => 'yes',
			],
		];
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	private function get_default_restricted_rows() {
		return [
			[
				'feature_title'    => __( 'Products for Sale', 'bw' ),
				'permission_text'  => '✕',
				'explanation_text' => __( 'The artwork may not be used as the main value of products being sold, such as posters, notebooks, art prints, calendars, card decks, or digital products.', 'bw' ),
				'use_tooltip'      => 'yes',
			],
			[
				'feature_title'    => __( 'Merchandise', 'bw' ),
				'permission_text'  => '✕',
				'explanation_text' => __( 'Merchandise featuring the artwork is prohibited, including apparel, mugs, tote bags, stickers, postcards, and similar products.', 'bw' ),
				'use_tooltip'      => 'yes',
			],
			[
				'feature_title'    => __( 'Print-on-Demand', 'bw' ),
				'permission_text'  => '✕',
				'explanation_text' => __( 'Print-on-demand services such as Printful, Printify, Gelato, Shopify POD, Etsy POD, or similar services are not permitted.', 'bw' ),
				'use_tooltip'      => 'yes',
			],
			[
				'feature_title'    => __( 'Resale of Original Files', 'bw' ),
				'permission_text'  => '✕',
				'explanation_text' => __( 'Original files may not be sold through Etsy, Gumroad, Creative Market, marketplaces, stock libraries, digital asset stores, or similar platforms.', 'bw' ),
				'use_tooltip'      => 'yes',
			],
			[
				'feature_title'    => __( 'Redistribution of Files', 'bw' ),
				'permission_text'  => '✕',
				'explanation_text' => __( 'Original files may not be shared through bundles, memberships, resource libraries, archives, download services, client downloads, or third-party file access.', 'bw' ),
				'use_tooltip'      => 'yes',
			],
			[
				'feature_title'    => __( 'AI Training', 'bw' ),
				'permission_text'  => '✕',
				'explanation_text' => __( 'AI training, machine learning, dataset creation, fine-tuning, embedding generation, computer vision, and model development are excluded and require a separate license.', 'bw' ),
				'use_tooltip'      => 'yes',
			],
		];
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	private function get_default_rows() {
		return array_merge( $this->get_default_allowed_rows(), $this->get_default_restricted_rows() );
	}

	/**
	 * @param mixed $rows Raw repeater rows.
	 * @return array<int, array<string, string>>
	 */
	private function normalize_rows( $rows ) {
		if ( ! is_array( $rows ) ) {
			return [];
		}

		$normalized = [];

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$feature_title    = isset( $row['feature_title'] ) ? trim( (string) $row['feature_title'] ) : '';
			$permission_text  = isset( $row['permission_text'] ) ? trim( (string) $row['permission_text'] ) : '';
			$explanation_text = isset( $row['explanation_text'] ) ? trim( (string) $row['explanation_text'] ) : '';
			$use_tooltip      = ( isset( $row['use_tooltip'] ) && 'yes' === $row['use_tooltip'] ) ? 'yes' : '';

			if ( '' === $feature_title && '' === $permission_text && '' === $explanation_text ) {
				continue;
			}

			$normalized[] = [
				'feature_title'    => $feature_title,
				'permission_text'  => $permission_text,
				'explanation_text' => $explanation_text,
				'use_tooltip'      => $use_tooltip,
			];
		}

		return $normalized;
	}

	/**
	 * Resolve rows for output while preserving backward compatibility.
	 *
	 * Default instances keep the Commercial preset. If the editor explicitly
	 * switches to the empty-table mode, untouched preset rows are ignored and
	 * only user-added / user-edited rows remain.
	 *
	 * @param array<string, mixed> $settings Widget settings.
	 * @return array<int, array<string, string>>
	 */
	private function get_effective_rows( $settings ) {
		$rows       = $this->normalize_rows( isset( $settings['license_rows'] ) ? $settings['license_rows'] : [] );
		$rows_preset = isset( $settings['rows_preset'] ) ? (string) $settings['rows_preset'] : 'commercial';

		if ( 'empty' !== $rows_preset ) {
			return $rows;
		}

		$default_rows = $this->get_default_rows();

		return array_values(
			array_filter(
				$rows,
				function ( $row ) use ( $default_rows ) {
					foreach ( $default_rows as $default_row ) {
						if ( $this->rows_match( $row, $default_row ) ) {
							return false;
						}
					}

					return true;
				}
			)
		);
	}

	/**
	 * @param array<string, mixed> $settings Widget settings.
	 * @return array{allowed: array<int, array<string, string>>, restricted: array<int, array<string, string>>}
	 */
	private function get_effective_sections( $settings ) {
		$rows_preset        = isset( $settings['rows_preset'] ) ? (string) $settings['rows_preset'] : 'commercial';
		$allowed_rows       = $this->normalize_rows( isset( $settings['allowed_license_rows'] ) ? $settings['allowed_license_rows'] : [] );
		$restricted_rows    = $this->normalize_rows( isset( $settings['restricted_license_rows'] ) ? $settings['restricted_license_rows'] : [] );
		$legacy_rows        = $this->get_effective_rows( $settings );
		$has_new_structure  = ! empty( $allowed_rows ) || ! empty( $restricted_rows );

		if ( $has_new_structure ) {
			return [
				'allowed'    => $allowed_rows,
				'restricted' => $restricted_rows,
			];
		}

		if ( ! empty( $legacy_rows ) ) {
			return [
				'allowed'    => $legacy_rows,
				'restricted' => [],
			];
		}

		if ( 'empty' === $rows_preset ) {
			return [
				'allowed'    => [],
				'restricted' => [],
			];
		}

		return [
			'allowed'    => $this->get_default_allowed_rows(),
			'restricted' => $this->get_default_restricted_rows(),
		];
	}

	/**
	 * Compare normalized repeater rows.
	 *
	 * @param array<string, string> $row         Runtime row.
	 * @param array<string, string> $default_row Default preset row.
	 * @return bool
	 */
	private function rows_match( $row, $default_row ) {
		$keys = [ 'feature_title', 'permission_text', 'explanation_text', 'use_tooltip' ];

		foreach ( $keys as $key ) {
			$row_value         = isset( $row[ $key ] ) ? (string) $row[ $key ] : '';
			$default_row_value = isset( $default_row[ $key ] ) ? (string) $default_row[ $key ] : '';

			if ( $row_value !== $default_row_value ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param string $feature_default Default feature title.
	 * @param string $permission_default Default permission text.
	 * @param string $explanation_default Default explanation.
	 * @return Repeater
	 */
	private function build_license_rows_repeater( $feature_default, $permission_default, $explanation_default ) {
		$repeater = new Repeater();

		$repeater->add_control(
			'feature_title',
			[
				'label'       => __( 'Feature Title', 'bw' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => $feature_default,
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'permission_text',
			[
				'label'       => __( 'Permission Text', 'bw' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => $permission_default,
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'explanation_text',
			[
				'label'       => __( 'Example / Explanation', 'bw' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => $explanation_default,
				'rows'        => 4,
				'placeholder' => __( 'Describe how this permission works in practice.', 'bw' ),
			]
		);

		$repeater->add_control(
			'use_tooltip',
			[
				'label'        => __( 'Tooltip Mode', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => __( 'When enabled, the explanation is shown in a tooltip on desktop and inline on mobile.', 'bw' ),
			]
		);

		return $repeater;
	}

	/**
	 * @param array<string, mixed> $settings Widget settings.
	 * @return array<int, string>
	 */
	private function get_divider_classes( $settings ) {
		$classes = [ 'bw-license-table-widget__divider' ];
		$lines_enabled = isset( $settings['divider_line_enabled'] ) && 'yes' === $settings['divider_line_enabled'];

		if ( ! $lines_enabled ) {
			return $classes;
		}

		if ( isset( $settings['divider_line_top_enabled'] ) && 'yes' === $settings['divider_line_top_enabled'] ) {
			$classes[] = 'bw-license-table-widget__divider--top-line';
		}

		if ( isset( $settings['divider_line_bottom_enabled'] ) && 'yes' === $settings['divider_line_bottom_enabled'] ) {
			$classes[] = 'bw-license-table-widget__divider--bottom-line';
		}

		return $classes;
	}

	/**
	 * @param array<int, array<string, string>> $rows Section rows.
	 * @param string                            $section_key Section key.
	 * @return void
	 */
	private function render_rows_markup( $rows, $section_key ) {
		foreach ( $rows as $index => $row ) {
			$row_classes          = [ 'bw-license-table-widget__row' ];
			$tooltip_id           = sprintf( 'bw-license-table-tooltip-%1$s-%2$s-%3$d', esc_attr( $this->get_id() ), esc_attr( $section_key ), (int) $index );
			$is_symbol_permission = in_array( $row['permission_text'], [ '✓', '✕' ], true );

			if ( 'yes' === $row['use_tooltip'] && '' !== $row['explanation_text'] ) {
				$row_classes[] = 'bw-license-table-widget__row--tooltip';
			}

			if ( $is_symbol_permission ) {
				$row_classes[] = 'bw-license-table-widget__row--symbol-permission';
			}
			?>
			<div class="<?php echo esc_attr( implode( ' ', $row_classes ) ); ?>">
				<div class="bw-license-table-widget__cell bw-license-table-widget__feature">
					<span class="bw-license-table-widget__mobile-label"><?php esc_html_e( 'Feature', 'bw' ); ?></span>
					<div class="bw-license-table-widget__feature-text"><?php echo wp_kses_post( nl2br( esc_html( $row['feature_title'] ) ) ); ?></div>
				</div>

				<div class="bw-license-table-widget__cell bw-license-table-widget__permission">
					<span class="bw-license-table-widget__mobile-label"><?php esc_html_e( 'Permission', 'bw' ); ?></span>
					<div class="bw-license-table-widget__permission-text"><?php echo wp_kses_post( nl2br( esc_html( $row['permission_text'] ) ) ); ?></div>
				</div>

				<div class="bw-license-table-widget__cell bw-license-table-widget__example">
					<span class="bw-license-table-widget__mobile-label"><?php esc_html_e( 'Details', 'bw' ); ?></span>

					<?php if ( 'yes' === $row['use_tooltip'] && '' !== $row['explanation_text'] ) : ?>
						<div class="bw-license-table-widget__tooltip-wrap">
							<button
								type="button"
								class="bw-license-table-widget__tooltip-trigger"
								aria-label="<?php echo esc_attr( sprintf( __( 'Show explanation for %s', 'bw' ), $row['feature_title'] ) ); ?>"
								aria-describedby="<?php echo esc_attr( $tooltip_id ); ?>"
							>
								<span aria-hidden="true">?</span>
							</button>
							<span
								id="<?php echo esc_attr( $tooltip_id ); ?>"
								class="bw-license-table-widget__tooltip is-hidden"
								role="tooltip"
								hidden
							><?php echo wp_kses_post( nl2br( esc_html( $row['explanation_text'] ) ) ); ?></span>
							<div class="bw-license-table-widget__tooltip-mobile">
								<?php echo wp_kses_post( nl2br( esc_html( $row['explanation_text'] ) ) ); ?>
							</div>
						</div>
					<?php else : ?>
						<div class="bw-license-table-widget__example-text">
							<?php echo wp_kses_post( nl2br( esc_html( $row['explanation_text'] ) ) ); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
			<?php
		}
	}
}
