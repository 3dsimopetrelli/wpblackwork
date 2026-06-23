<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BW_Newsletter_Subscription_Widget extends Widget_Base {

	public function get_name() {
		return 'bw-newsletter-subscription';
	}

	public function get_title() {
		return __( 'Newsletter Subscription', 'bw' );
	}

	public function get_icon() {
		return 'eicon-mail';
	}

	public function get_categories() {
		return array( 'blackwork' );
	}

	public function get_style_depends() {
		return array( 'bw-newsletter-subscription-style' );
	}

	public function get_script_depends() {
		return array( 'bw-newsletter-subscription-script' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Content', 'bw' ),
			)
		);

		$this->add_control(
			'style_variant',
			array(
				'label'   => __( 'Style', 'bw' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'footer',
				'options' => array(
					'footer'  => __( 'Style Footer', 'bw' ),
					'section' => __( 'Style Section', 'bw' ),
				),
			)
		);

		$this->add_control(
			'show_name_field',
			array(
				'label'        => __( 'Show name field', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'button_inside_email_field',
			array(
				'label'        => __( 'Button inside email field', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array(
					'style_variant' => 'footer',
				),
			)
		);

		$this->add_control(
			'subscribe_button_text',
			array(
				'label'       => __( 'Subscribe Button Text', 'bw' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Subscribe', 'bw' ),
				'placeholder' => __( 'Subscribe', 'bw' ),
			)
		);

		$this->add_control(
			'email_placeholder_text',
			array(
				'label'       => __( 'Email Placeholder Text', 'bw' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Your email', 'bw' ),
				'placeholder' => __( 'Your email', 'bw' ),
			)
		);

		$this->add_control(
			'privacy_custom_text_enabled',
			array(
				'label'        => __( 'Custom privacy text', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'privacy_custom_text',
			array(
				'label'       => __( 'Privacy Text', 'bw' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 4,
				'default'     => '',
				'description' => __( 'HTML allowed. Used only when Custom privacy text is enabled.', 'bw' ),
				'condition'   => array(
					'privacy_custom_text_enabled' => 'yes',
				),
			)
		);

		$this->add_control(
			'footer_title',
			array(
				'label'       => __( 'Title', 'bw' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => __( 'PRIVATE ACCESS TO NEW RELEASES', 'bw' ),
				'description' => __( 'HTML allowed. Used when Style Footer is selected.', 'bw' ),
				'condition'   => array(
					'style_variant' => 'footer',
				),
			)
		);

		$this->add_control(
			'footer_subtitle',
			array(
				'label'       => __( 'Subtitle', 'bw' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => __( 'Early access to rare books, prints, and curated selections. No noise. Only what matters.', 'bw' ),
				'description' => __( 'HTML allowed. Used when Style Footer is selected.', 'bw' ),
				'condition'   => array(
					'style_variant' => 'footer',
				),
			)
		);

		$this->add_control(
			'section_title',
			array(
				'label'     => __( 'Title', 'bw' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Step Inside the Archive', 'bw' ),
				'condition' => array(
					'style_variant' => 'section',
				),
			)
		);

		$this->add_control(
			'section_subtitle',
			array(
				'label'     => __( 'Subtitle', 'bw' ),
				'type'      => Controls_Manager::TEXTAREA,
				'default'   => __( 'Get free sample files, early access to new collections, and rare finds from our archive.', 'bw' ),
				'rows'      => 3,
				'condition' => array(
					'style_variant' => 'section',
				),
			)
		);

		$this->add_control(
			'section_background_color',
			array(
				'label'     => __( 'Background Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#050505',
				'condition' => array(
					'style_variant' => 'section',
				),
			)
		);

		$this->add_control(
			'section_height',
			array(
				'label'      => __( 'Section Height', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'vh' ),
				'range'      => array(
					'vh' => array(
						'min'  => 40,
						'max'  => 140,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 72,
					'unit' => 'vh',
				),
				'condition'  => array(
					'style_variant' => 'section',
				),
			)
		);

		$this->add_control(
			'section_content_layout_heading',
			array(
				'label'     => __( 'Section Content Layout', 'bw' ),
				'type'      => Controls_Manager::HEADING,
				'condition' => array(
					'style_variant' => 'section',
				),
			)
		);

		$this->add_responsive_control(
			'section_content_width',
			array(
				'label'      => __( 'Content Width', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'vw' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 1600,
						'step' => 1,
					),
					'%'  => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
					'vw' => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}}' => '--bw-ns-section-content-width: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'style_variant' => 'section',
				),
			)
		);

		$this->add_responsive_control(
			'section_content_max_width',
			array(
				'label'      => __( 'Content Max Width', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'vw' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 1600,
						'step' => 1,
					),
					'%'  => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
					'vw' => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}}' => '--bw-ns-section-content-max-width: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'style_variant' => 'section',
				),
			)
		);

		$this->add_responsive_control(
			'section_content_min_width',
			array(
				'label'      => __( 'Content Min Width', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'vw' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 1600,
						'step' => 1,
					),
					'%'  => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
					'vw' => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}}' => '--bw-ns-section-content-min-width: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'style_variant' => 'section',
				),
			)
		);

		$this->add_responsive_control(
			'section_content_alignment',
			array(
				'label'                => __( 'Content Alignment', 'bw' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => array(
					'left'   => array(
						'title' => __( 'Left', 'bw' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => __( 'Center', 'bw' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => __( 'Right', 'bw' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default'              => 'center',
				'selectors_dictionary' => array(
					'left'   => '--bw-ns-section-content-margin-left: 0; --bw-ns-section-content-margin-right: auto;',
					'center' => '--bw-ns-section-content-margin-left: auto; --bw-ns-section-content-margin-right: auto;',
					'right'  => '--bw-ns-section-content-margin-left: auto; --bw-ns-section-content-margin-right: 0;',
				),
				'selectors'            => array(
					'{{WRAPPER}}' => '{{VALUE}}',
				),
				'condition'            => array(
					'style_variant' => 'section',
				),
			)
		);

		$this->add_control(
			'section_enable_background_image',
			array(
				'label'        => __( 'Enable Background Image', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array(
					'style_variant' => 'section',
				),
			)
		);

		$this->add_control(
			'section_background_image',
			array(
				'label'     => __( 'Background Image', 'bw' ),
				'type'      => Controls_Manager::MEDIA,
				'condition' => array(
					'style_variant'                   => 'section',
					'section_enable_background_image' => 'yes',
				),
			)
		);

		$this->add_control(
			'section_background_image_position',
			array(
				'label'     => __( 'Background Image Position', 'bw' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'left',
				'options'   => array(
					'left'   => __( 'Left', 'bw' ),
					'center' => __( 'Center', 'bw' ),
					'right'  => __( 'Right', 'bw' ),
				),
				'condition' => array(
					'style_variant'                   => 'section',
					'section_enable_background_image' => 'yes',
				),
			)
		);

		$this->add_control(
			'section_background_image_fit',
			array(
				'label'     => __( 'Background Image Fit', 'bw' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'contain',
				'options'   => array(
					'contain' => __( 'Contain', 'bw' ),
					'cover'   => __( 'Cover', 'bw' ),
				),
				'condition' => array(
					'style_variant'                   => 'section',
					'section_enable_background_image' => 'yes',
				),
			)
		);

		$this->add_control(
			'section_background_image_repeat',
			array(
				'label'     => __( 'Background Image Repeat', 'bw' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'no-repeat',
				'options'   => array(
					'no-repeat' => __( 'No Repeat', 'bw' ),
					'repeat'    => __( 'Repeat', 'bw' ),
					'repeat-x'  => __( 'Repeat Horizontally', 'bw' ),
					'repeat-y'  => __( 'Repeat Vertically', 'bw' ),
				),
				'condition' => array(
					'style_variant'                   => 'section',
					'section_enable_background_image' => 'yes',
				),
			)
		);

		$this->add_control(
			'section_enable_gradient_overlay',
			array(
				'label'        => __( 'Enable Gradient Overlay', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'style_variant' => 'section',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_widget',
			array(
				'label' => __( 'Wrap', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		BW_Widget_Helper::add_dimensions_control(
			$this,
			'widget_padding',
			__( 'Padding', 'bw' ),
			'{{WRAPPER}} .bw-newsletter-subscription-shell',
			'padding',
			array( 'px', '%', 'em', 'rem', 'vw', 'vh' )
		);

		BW_Widget_Helper::add_color_var_control(
			$this,
			'widget_text_color',
			__( 'Background Color', 'bw' ),
			'{{WRAPPER}}',
			'--bw-ns-widget-bg'
		);

		BW_Widget_Helper::add_dimensions_control(
			$this,
			'widget_border_radius',
			__( 'Border Radius', 'bw' ),
			'{{WRAPPER}}',
			'--bw-ns-widget-radius',
			array( 'px', '%', 'em', 'rem', 'vw', 'vh' )
		);

		BW_Widget_Helper::add_color_var_control(
			$this,
			'footer_title_color',
			__( 'Title Color', 'bw' ),
			'{{WRAPPER}}',
			'--bw-ns-footer-title-color',
			'rgba(111, 111, 111, 0.11)',
			array(
				'condition' => array(
					'style_variant' => 'footer',
				),
			)
		);

		BW_Widget_Helper::add_color_var_control(
			$this,
			'footer_subtitle_color',
			__( 'Description Color', 'bw' ),
			'{{WRAPPER}}',
			'--bw-ns-footer-subtitle-color',
			'#080808',
			array(
				'condition' => array(
					'style_variant' => 'footer',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_section',
			array(
				'label'     => __( 'Style Section', 'bw' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'style_variant' => 'section',
				),
			)
		);

		$this->add_control(
			'section_content_position',
			array(
				'label'   => __( 'Content Position', 'bw' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'center',
				'options' => array(
					'left'   => __( 'Left', 'bw' ),
					'center' => __( 'Center', 'bw' ),
					'right'  => __( 'Right', 'bw' ),
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'           => 'section_title_typography',
				'label'          => __( 'Title Typography', 'bw' ),
				'selector'       => '{{WRAPPER}} .bw-newsletter-subscription-section-title',
				'fields_options' => array(
					'font_weight' => array(
						'default' => '500',
					),
					'font_style'  => array(
						'default' => 'normal',
					),
				),
			)
		);

		BW_Widget_Helper::add_color_var_control(
			$this,
			'section_title_color',
			__( 'Title Color', 'bw' ),
			'{{WRAPPER}}',
			'--bw-ns-section-title-color',
			'#F7F7F2'
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'           => 'section_subtitle_typography',
				'label'          => __( 'Subtitle Typography', 'bw' ),
				'selector'       => '{{WRAPPER}} .bw-newsletter-subscription-section-subtitle, {{WRAPPER}} .bw-newsletter-subscription-section-subtitle p',
				'fields_options' => array(
					'font_weight' => array(
						'default' => '400',
					),
					'font_style'  => array(
						'default' => 'normal',
					),
				),
			)
		);

		BW_Widget_Helper::add_color_var_control(
			$this,
			'section_subtitle_color',
			__( 'Subtitle Color', 'bw' ),
			'{{WRAPPER}}',
			'--bw-ns-section-subtitle-color',
			'rgba(247, 247, 242, 0.86)'
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'section_privacy_typography',
				'label'    => __( 'Privacy Typography', 'bw' ),
				'selector' => '{{WRAPPER}} .bw-newsletter-subscription-consent, {{WRAPPER}} .bw-newsletter-subscription-consent__text, {{WRAPPER}} .bw-newsletter-subscription-consent__label, {{WRAPPER}} .bw-newsletter-subscription-consent__link',
			)
		);

		BW_Widget_Helper::add_color_var_control(
			$this,
			'section_privacy_color',
			__( 'Privacy Color', 'bw' ),
			'{{WRAPPER}}',
			'--bw-ns-section-privacy-color',
			'rgba(247, 247, 242, 0.84)'
		);

		BW_Widget_Helper::add_color_var_control(
			$this,
			'section_overlay_color',
			__( 'Gradient Color 1', 'bw' ),
			'{{WRAPPER}}',
			'--bw-ns-overlay-color',
			'rgba(8, 8, 8, 0.82)',
			array(
				'condition' => array(
					'style_variant'                   => 'section',
					'section_enable_gradient_overlay' => 'yes',
				),
			)
		);

		BW_Widget_Helper::add_color_var_control(
			$this,
			'section_overlay_secondary_color',
			__( 'Gradient Color 2', 'bw' ),
			'{{WRAPPER}}',
			'--bw-ns-overlay-color-secondary',
			'rgba(8, 8, 8, 0.12)',
			array(
				'condition' => array(
					'style_variant'                   => 'section',
					'section_enable_gradient_overlay' => 'yes',
				),
			)
		);

		$this->add_control(
			'section_overlay_angle',
			array(
				'label'      => __( 'Gradient Angle', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'deg' ),
				'range'      => array(
					'deg' => array(
						'min'  => 0,
						'max'  => 360,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 135,
					'unit' => 'deg',
				),
				'selectors'  => array(
					'{{WRAPPER}}' => '--bw-ns-overlay-angle: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'style_variant'                   => 'section',
					'section_enable_gradient_overlay' => 'yes',
				),
			)
		);

		BW_Widget_Helper::add_color_var_control(
			$this,
			'section_glow_color',
			__( 'Glow Color', 'bw' ),
			'{{WRAPPER}}',
			'--bw-ns-glow-color',
			'rgba(128, 253, 3, 0.16)',
			array(
				'condition' => array(
					'style_variant'                   => 'section',
					'section_enable_gradient_overlay' => 'yes',
				),
			)
		);

		$this->add_control(
			'section_overlay_opacity',
			array(
				'label'     => __( 'Overlay Opacity', 'bw' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 1,
				'step'      => 0.01,
				'default'   => 1,
				'selectors' => array(
					'{{WRAPPER}}' => '--bw-ns-overlay-opacity: {{VALUE}};',
					'{{WRAPPER}} .bw-newsletter-subscription-section-overlay' => 'opacity: {{VALUE}};',
				),
				'condition' => array(
					'style_variant'                   => 'section',
					'section_enable_gradient_overlay' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_section_form',
			array(
				'label'     => __( 'Section Form Width', 'bw' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'style_variant' => 'section',
				),
			)
		);

		$this->add_responsive_control(
			'section_form_width',
			array(
				'label'      => __( 'Form Width', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'vw' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 1600,
						'step' => 1,
					),
					'%'  => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
					'vw' => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}}' => '--bw-ns-section-form-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'section_form_max_width',
			array(
				'label'      => __( 'Form Max Width', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'vw' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 1600,
						'step' => 1,
					),
					'%'  => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
					'vw' => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}}' => '--bw-ns-section-form-max-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'section_form_min_width',
			array(
				'label'      => __( 'Form Min Width', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'vw' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 1600,
						'step' => 1,
					),
					'%'  => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
					'vw' => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}}' => '--bw-ns-section-form-min-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'section_form_alignment',
			array(
				'label'                => __( 'Form Alignment', 'bw' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => array(
					'left'   => array(
						'title' => __( 'Left', 'bw' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => __( 'Center', 'bw' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => __( 'Right', 'bw' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default'              => 'center',
				'selectors_dictionary' => array(
					'left'   => '--bw-ns-section-form-margin-left: 0; --bw-ns-section-form-margin-right: auto;',
					'center' => '--bw-ns-section-form-margin-left: auto; --bw-ns-section-form-margin-right: auto;',
					'right'  => '--bw-ns-section-form-margin-left: auto; --bw-ns-section-form-margin-right: 0;',
				),
				'selectors'            => array(
					'{{WRAPPER}}' => '{{VALUE}}',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_input',
			array(
				'label' => __( 'Input Field', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'input_typography',
				'label'    => __( 'Typography', 'bw' ),
				'selector' => '{{WRAPPER}} .bw-newsletter-subscription__input--footer, {{WRAPPER}} .bw-newsletter-subscription-widget--section .bw-newsletter-subscription__input--section, {{WRAPPER}} .bw-newsletter-subscription__input--footer::placeholder, {{WRAPPER}} .bw-newsletter-subscription-widget--section .bw-newsletter-subscription__input--section::placeholder',
			)
		);

		$this->add_control(
			'input_text_color',
			array(
				'label'     => __( 'Text Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bw-newsletter-subscription__input--footer, {{WRAPPER}} .bw-newsletter-subscription-widget--section .bw-newsletter-subscription__input--section' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'input_placeholder_color',
			array(
				'label'     => __( 'Placeholder Text Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bw-newsletter-subscription__input--footer::placeholder, {{WRAPPER}} .bw-newsletter-subscription-widget--section .bw-newsletter-subscription__input--section::placeholder' => 'color: {{VALUE}};',
					'{{WRAPPER}} .bw-newsletter-subscription__input--footer::-webkit-input-placeholder, {{WRAPPER}} .bw-newsletter-subscription-widget--section .bw-newsletter-subscription__input--section::-webkit-input-placeholder' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'input_background_color',
			array(
				'label'     => __( 'Background Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bw-newsletter-subscription__input--footer, {{WRAPPER}} .bw-newsletter-subscription-widget--section .bw-newsletter-subscription__input--section' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'input_border_color',
			array(
				'label'     => __( 'Border Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bw-newsletter-subscription__input--footer, {{WRAPPER}} .bw-newsletter-subscription-widget--section .bw-newsletter-subscription__input--section' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'input_border_hover_color',
			array(
				'label'     => __( 'Border Hover Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bw-newsletter-subscription__input--footer:hover, {{WRAPPER}} .bw-newsletter-subscription-widget--section .bw-newsletter-subscription__input--section:hover' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'input_border_focus_color',
			array(
				'label'     => __( 'Border Focus Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bw-newsletter-subscription__input--footer:focus, {{WRAPPER}} .bw-newsletter-subscription-widget--section .bw-newsletter-subscription__input--section:focus' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .bw-newsletter-subscription__input--footer:focus-visible, {{WRAPPER}} .bw-newsletter-subscription-widget--section .bw-newsletter-subscription__input--section:focus-visible' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'input_border_width',
			array(
				'label'      => __( 'Border Width', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 12,
						'step' => 1,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-newsletter-subscription__input--footer, {{WRAPPER}} .bw-newsletter-subscription-widget--section .bw-newsletter-subscription__input--section' => 'border-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'input_border_focus_width',
			array(
				'label'      => __( 'Focus Border Width', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 12,
						'step' => 1,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-newsletter-subscription__input--footer:focus, {{WRAPPER}} .bw-newsletter-subscription-widget--section .bw-newsletter-subscription__input--section:focus' => 'border-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bw-newsletter-subscription__input--footer:focus-visible, {{WRAPPER}} .bw-newsletter-subscription-widget--section .bw-newsletter-subscription__input--section:focus-visible' => 'border-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		BW_Widget_Helper::add_dimensions_control(
			$this,
			'input_border_radius',
			__( 'Border Radius', 'bw' ),
			'{{WRAPPER}} .bw-newsletter-subscription__input--footer, {{WRAPPER}} .bw-newsletter-subscription-widget--section .bw-newsletter-subscription__input--section',
			'border-radius',
			array( 'px', '%' )
		);

		$this->add_responsive_control(
			'input_padding',
			array(
				'label'      => __( 'Padding', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .bw-newsletter-subscription__input--footer, {{WRAPPER}} .bw-newsletter-subscription-widget--section .bw-newsletter-subscription__input--section' => 'padding: {{TOP}}{{UNIT}} calc({{RIGHT}}{{UNIT}} + var(--bw-ns-input-padding-right-inline-offset, 0px)) {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'input_height',
			array(
				'label'      => __( 'Height', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px'  => array(
						'min'  => 24,
						'max'  => 120,
						'step' => 1,
					),
					'em'  => array(
						'min'  => 1,
						'max'  => 8,
						'step' => 0.1,
					),
					'rem' => array(
						'min'  => 1,
						'max'  => 8,
						'step' => 0.1,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-newsletter-subscription__input--footer, {{WRAPPER}} .bw-newsletter-subscription-widget--section .bw-newsletter-subscription__input--section' => 'height: {{SIZE}}{{UNIT}}; min-height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'input_box_shadow',
				'label'    => __( 'Box Shadow', 'bw' ),
				'selector' => '{{WRAPPER}} .bw-newsletter-subscription__input--footer, {{WRAPPER}} .bw-newsletter-subscription-widget--section .bw-newsletter-subscription__input--section',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_button',
			array(
				'label' => __( 'Subscribe Button', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'button_typography',
				'label'    => __( 'Typography', 'bw' ),
				'selector' => '{{WRAPPER}} .bw-newsletter-subscription-button__label',
			)
		);

		BW_Widget_Helper::add_color_var_control(
			$this,
			'button_text_color',
			__( 'Text Color', 'bw' ),
			'{{WRAPPER}}',
			'--bw-ns-button-text-color'
		);

		BW_Widget_Helper::add_color_var_control(
			$this,
			'button_text_hover_color',
			__( 'Text Hover Color', 'bw' ),
			'{{WRAPPER}}',
			'--bw-ns-button-text-color-hover'
		);

		BW_Widget_Helper::add_color_var_control(
			$this,
			'button_background_color',
			__( 'Background Color', 'bw' ),
			'{{WRAPPER}}',
			'--bw-ns-button-bg'
		);

		BW_Widget_Helper::add_color_var_control(
			$this,
			'button_background_hover_color',
			__( 'Background Hover Color', 'bw' ),
			'{{WRAPPER}}',
			'--bw-ns-button-bg-hover'
		);

		BW_Widget_Helper::add_color_var_control(
			$this,
			'button_border_color',
			__( 'Border Color', 'bw' ),
			'{{WRAPPER}}',
			'--bw-ns-button-border-color'
		);

		BW_Widget_Helper::add_color_var_control(
			$this,
			'button_border_hover_color',
			__( 'Border Hover Color', 'bw' ),
			'{{WRAPPER}}',
			'--bw-ns-button-border-color-hover'
		);

		$this->add_responsive_control(
			'button_border_width',
			array(
				'label'      => __( 'Border Width', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 12,
						'step' => 1,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}}' => '--bw-ns-button-border-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		BW_Widget_Helper::add_dimensions_control(
			$this,
			'button_border_radius',
			__( 'Border Radius', 'bw' ),
			'{{WRAPPER}}',
			'--bw-ns-button-border-radius',
			array( 'px', '%', 'em', 'rem' )
		);

		$this->add_responsive_control(
			'button_padding',
			array(
				'label'      => __( 'Padding', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}}' => '--bw-ns-button-padding-top: {{TOP}}{{UNIT}}; --bw-ns-button-padding-right: {{RIGHT}}{{UNIT}}; --bw-ns-button-padding-bottom: {{BOTTOM}}{{UNIT}}; --bw-ns-button-padding-left: {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		if ( ! class_exists( 'BW_Mail_Marketing_Settings' ) ) {
			return;
		}

		$widget_settings     = $this->get_settings_for_display();
		$raw_widget_settings = $this->get_data( 'settings' );
		if ( ! is_array( $raw_widget_settings ) ) {
			$raw_widget_settings = array();
		}
		$settings      = BW_Mail_Marketing_Settings::get_subscription_settings();
		$is_editor     = class_exists( '\Elementor\Plugin' )
			&& \Elementor\Plugin::$instance->editor
			&& \Elementor\Plugin::$instance->editor->is_edit_mode();
		$style_variant = isset( $widget_settings['style_variant'] ) ? sanitize_key( $widget_settings['style_variant'] ) : 'footer';
		if ( ! in_array( $style_variant, array( 'footer', 'section' ), true ) ) {
			$style_variant = 'footer';
		}

		$show_name_field             = $this->resolve_show_name_field_visibility( $widget_settings, $raw_widget_settings, $style_variant );
		$button_inside_email_field   = 'footer' === $style_variant && $this->is_widget_switch_enabled( $widget_settings['button_inside_email_field'] ?? '' );
		$email_input_variant_class   = 'section' === $style_variant ? 'bw-newsletter-subscription__input--section' : 'bw-newsletter-subscription__input--footer';
		$privacy_custom_text_enabled = $this->is_widget_switch_enabled( $widget_settings['privacy_custom_text_enabled'] ?? '' );
		$privacy_custom_text         = '';
		if ( $privacy_custom_text_enabled && ! empty( $widget_settings['privacy_custom_text'] ) ) {
			$privacy_custom_text = trim( (string) $widget_settings['privacy_custom_text'] );
		}
		$name_label        = ! empty( $settings['name_label'] ) ? $settings['name_label'] : __( 'Name', 'bw' );
		$email_label       = ! empty( $settings['email_label'] ) ? $settings['email_label'] : __( 'Email address', 'bw' );
		$email_placeholder = isset( $widget_settings['email_placeholder_text'] ) ? trim( (string) $widget_settings['email_placeholder_text'] ) : '';
		if ( '' === $email_placeholder ) {
			$email_placeholder = __( 'Your email', 'bw' );
		}
		$consent_text     = ! empty( $settings['consent_prefix'] ) ? $settings['consent_prefix'] : __( 'I agree to the', 'bw' );
		$consent_required = ! isset( $settings['consent_required'] ) || ! empty( $settings['consent_required'] );

		if ( empty( $settings['enabled'] ) && ! $is_editor ) {
			return;
		}

		if ( $is_editor && class_exists( 'BW_Mail_Marketing_Settings' ) ) {
			$general_settings = BW_Mail_Marketing_Settings::get_general_settings();
			if ( empty( $general_settings['api_key'] ) ) {
				echo '<div class="bw-newsletter-subscription-preview-notice" style="margin-bottom:12px;">';
				esc_html_e( 'Brevo API key is not configured. Set it in Mail Marketing > General before this widget can submit.', 'bw' );
				echo '</div>';
			}
		}

		$privacy_url = ! empty( $settings['privacy_url'] )
			? esc_url_raw( (string) $settings['privacy_url'] )
			: ( function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '' );
		$widget_id   = 'bw-mm-subscription-' . esc_attr( $this->get_id() );
		$message_id  = $widget_id . '-message';
		$consent_id  = $widget_id . '-privacy';
		$button_text = isset( $widget_settings['subscribe_button_text'] ) ? trim( (string) $widget_settings['subscribe_button_text'] ) : '';
		if ( '' === $button_text ) {
			$button_text = __( 'Subscribe', 'bw' );
		}
		$footer_title             = isset( $widget_settings['footer_title'] ) ? wp_kses_post( $widget_settings['footer_title'] ) : __( 'PRIVATE ACCESS TO NEW RELEASES', 'bw' );
		$footer_subtitle          = isset( $widget_settings['footer_subtitle'] ) ? wp_kses_post( $widget_settings['footer_subtitle'] ) : __( 'Early access to rare books, prints, and curated selections. No noise. Only what matters.', 'bw' );
		$section_title            = isset( $widget_settings['section_title'] ) ? wp_kses_post( $widget_settings['section_title'] ) : __( 'Step Inside the Archive', 'bw' );
		$section_subtitle         = isset( $widget_settings['section_subtitle'] ) ? wp_kses_post( $widget_settings['section_subtitle'] ) : __( 'Get free sample files, early access to new collections, and rare finds from our archive.', 'bw' );
		$section_background_color = '#050505';
		if ( ! empty( $widget_settings['section_background_color'] ) ) {
			$section_background_color = $this->sanitize_widget_color_value( $widget_settings['section_background_color'], '#050505' );
		}
		$section_height = 72;
		if ( isset( $widget_settings['section_height']['size'] ) && '' !== $widget_settings['section_height']['size'] ) {
			$section_height = max( 40, min( 140, (int) $widget_settings['section_height']['size'] ) );
		}

		$section_background_image = '';
		if ( ! empty( $widget_settings['section_background_image']['url'] ) ) {
			$section_background_image = esc_url_raw( $widget_settings['section_background_image']['url'] );
		}
		$background_image_toggle_set = array_key_exists( 'section_enable_background_image', $raw_widget_settings );
		$background_image_enabled    = $this->is_widget_switch_enabled( $widget_settings['section_enable_background_image'] ?? '' );
		if ( ! $background_image_toggle_set && '' !== $section_background_image ) {
			$background_image_enabled = true;
		}

		$section_image_position = isset( $widget_settings['section_background_image_position'] ) ? sanitize_key( $widget_settings['section_background_image_position'] ) : 'left';
		if ( ! in_array( $section_image_position, array( 'left', 'center', 'right' ), true ) ) {
			$section_image_position = 'left';
		}

		$section_image_fit = isset( $widget_settings['section_background_image_fit'] ) ? sanitize_key( $widget_settings['section_background_image_fit'] ) : 'contain';
		if ( ! in_array( $section_image_fit, array( 'contain', 'cover' ), true ) ) {
			$section_image_fit = 'contain';
		}

		$section_image_repeat = isset( $widget_settings['section_background_image_repeat'] ) ? sanitize_key( $widget_settings['section_background_image_repeat'] ) : 'no-repeat';
		if ( ! in_array( $section_image_repeat, array( 'no-repeat', 'repeat', 'repeat-x', 'repeat-y' ), true ) ) {
			$section_image_repeat = 'no-repeat';
		}

		$gradient_overlay_toggle_set = array_key_exists( 'section_enable_gradient_overlay', $raw_widget_settings );
		$gradient_overlay_enabled    = $this->is_widget_switch_enabled( $widget_settings['section_enable_gradient_overlay'] ?? 'yes' );
		if ( ! $gradient_overlay_toggle_set ) {
			$gradient_overlay_enabled = true;
		}

		$section_content_position = isset( $widget_settings['section_content_position'] ) ? sanitize_key( $widget_settings['section_content_position'] ) : 'center';
		if ( ! in_array( $section_content_position, array( 'left', 'center', 'right' ), true ) ) {
			$section_content_position = 'center';
		}

		$widget_classes = array(
			'bw-newsletter-subscription-widget',
			'bw-newsletter-subscription-widget--' . $style_variant,
		);
		if ( $button_inside_email_field ) {
			$widget_classes[] = 'bw-newsletter-subscription-widget--button-inline';
		}

		$widget_style = '';
		if ( 'section' === $style_variant ) {
			$widget_style = sprintf(
				'--bw-ns-section-bg:%1$s; --bw-ns-section-height:%2$dvh; --bw-ns-section-image-fit:%3$s; --bw-ns-section-image-repeat:%4$s;',
				esc_attr( $section_background_color ?: '#050505' ),
				$section_height,
				esc_attr( $section_image_fit ),
				esc_attr( $section_image_repeat )
			);
		}

		$art_style = '';
		if ( '' !== $section_background_image ) {
			$art_style = sprintf( 'background-image:url(%s);', esc_url( $section_background_image ) );
		}
		?>
		<div
			class="<?php echo esc_attr( implode( ' ', $widget_classes ) . ( empty( $settings['enabled'] ) ? ' is-disabled-preview' : '' ) ); ?>"
			id="<?php echo esc_attr( $widget_id ); ?>"
			<?php if ( 'section' === $style_variant ) : ?>
				data-section-art-position="<?php echo esc_attr( $section_image_position ); ?>"
				data-section-content-position="<?php echo esc_attr( $section_content_position ); ?>"
			<?php endif; ?>
			<?php if ( '' !== $widget_style ) : ?>
				style="<?php echo esc_attr( $widget_style ); ?>"
			<?php endif; ?>
		>
			<div class="bw-newsletter-subscription-shell">
				<?php if ( empty( $settings['enabled'] ) && $is_editor ) : ?>
					<div class="bw-newsletter-subscription-preview-notice">
						<?php esc_html_e( 'This widget is currently disabled in Mail Marketing > Subscription, but it remains visible here for layout preview.', 'bw' ); ?>
					</div>
				<?php endif; ?>

				<?php if ( 'section' === $style_variant && $background_image_enabled && '' !== $section_background_image ) : ?>
					<div class="bw-newsletter-subscription-section-art" aria-hidden="true" style="<?php echo esc_attr( $art_style ); ?>"></div>
				<?php endif; ?>

				<?php if ( 'section' === $style_variant && $gradient_overlay_enabled ) : ?>
					<div class="bw-newsletter-subscription-section-overlay" aria-hidden="true"></div>
				<?php endif; ?>

				<?php if ( 'footer' === $style_variant && ( '' !== trim( wp_strip_all_tags( $footer_title ) ) || '' !== trim( wp_strip_all_tags( $footer_subtitle ) ) ) ) : ?>
					<div class="bw-newsletter-subscription-footer-copy">
						<?php if ( '' !== trim( wp_strip_all_tags( $footer_title ) ) ) : ?>
							<h3 class="bw-newsletter-subscription-footer-title"><?php echo wp_kses_post( $footer_title ); ?></h3>
						<?php endif; ?>

						<?php if ( '' !== trim( wp_strip_all_tags( $footer_subtitle ) ) ) : ?>
							<div class="bw-newsletter-subscription-footer-subtitle"><?php echo wpautop( wp_kses_post( $footer_subtitle ) ); ?></div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<form
					class="<?php echo esc_attr( 'bw-newsletter-subscription-form bw-newsletter-subscription__form-wrap' . ( $button_inside_email_field ? ' is-button-inline' : '' ) ); ?>"
					method="post"
					novalidate
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'bw_mail_marketing_subscription_submit' ) ); ?>"
					data-consent-required="<?php echo $consent_required ? '1' : '0'; ?>"
				>
					<?php if ( 'section' === $style_variant ) : ?>
						<div class="bw-newsletter-subscription__section-content">
					<?php endif; ?>

						<noscript>
							<p class="bw-newsletter-subscription-noscript">
								<?php esc_html_e( 'JavaScript is required to submit this form.', 'bw' ); ?>
							</p>
						</noscript>

						<?php if ( 'section' === $style_variant && ( '' !== trim( wp_strip_all_tags( $section_title ) ) || '' !== trim( wp_strip_all_tags( $section_subtitle ) ) ) ) : ?>
							<div class="bw-newsletter-subscription-section-copy">
								<?php if ( '' !== trim( wp_strip_all_tags( $section_title ) ) ) : ?>
									<h2 class="bw-newsletter-subscription-section-title"><?php echo wp_kses_post( $section_title ); ?></h2>
								<?php endif; ?>

								<?php if ( '' !== trim( wp_strip_all_tags( $section_subtitle ) ) ) : ?>
									<div class="bw-newsletter-subscription-section-subtitle"><?php echo wpautop( wp_kses_post( $section_subtitle ) ); ?></div>
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<?php if ( 'section' === $style_variant ) : ?>
							<div class="bw-newsletter-subscription__section-form-area">
						<?php endif; ?>

						<?php if ( $show_name_field ) : ?>
							<div class="bw-newsletter-subscription-field">
								<label class="bw-newsletter-subscription-label" for="<?php echo esc_attr( $widget_id . '-name' ); ?>">
									<?php echo esc_html( $name_label ); ?>
								</label>
								<input
									id="<?php echo esc_attr( $widget_id . '-name' ); ?>"
									class="bw-newsletter-subscription-input"
									type="text"
									name="name"
									autocomplete="name"
									placeholder="<?php echo esc_attr( $name_label ); ?>"
									aria-label="<?php echo esc_attr( $name_label ); ?>"
								/>
							</div>
						<?php endif; ?>

						<?php if ( 'section' === $style_variant || $button_inside_email_field ) : ?>
							<div class="bw-newsletter-subscription-inline">
								<div class="bw-newsletter-subscription-field bw-newsletter-subscription-field--email">
									<label class="bw-newsletter-subscription-label" for="<?php echo esc_attr( $widget_id . '-email' ); ?>">
										<?php echo esc_html( $email_label ); ?>
									</label>
									<input
										id="<?php echo esc_attr( $widget_id . '-email' ); ?>"
										class="<?php echo esc_attr( 'bw-newsletter-subscription-input bw-newsletter-subscription__input bw-newsletter-subscription__input--email ' . $email_input_variant_class ); ?>"
										type="email"
										name="email"
										autocomplete="email"
										placeholder="<?php echo esc_attr( $email_placeholder ); ?>"
										aria-label="<?php echo esc_attr( $email_label ); ?>"
										aria-describedby="<?php echo esc_attr( $message_id ); ?>"
										aria-invalid="false"
										required
									/>
								</div>

								<button class="bw-newsletter-subscription-button" type="submit" aria-disabled="false">
									<span class="bw-newsletter-subscription-button__label"><?php echo esc_html( $button_text ); ?></span>
								</button>
							</div>
						<?php else : ?>
							<div class="bw-newsletter-subscription-field">
								<label class="bw-newsletter-subscription-label" for="<?php echo esc_attr( $widget_id . '-email' ); ?>">
									<?php echo esc_html( $email_label ); ?>
								</label>
								<input
									id="<?php echo esc_attr( $widget_id . '-email' ); ?>"
									class="<?php echo esc_attr( 'bw-newsletter-subscription-input bw-newsletter-subscription__input bw-newsletter-subscription__input--email ' . $email_input_variant_class ); ?>"
									type="email"
									name="email"
									autocomplete="email"
									placeholder="<?php echo esc_attr( $email_placeholder ); ?>"
									aria-label="<?php echo esc_attr( $email_label ); ?>"
									aria-describedby="<?php echo esc_attr( $message_id ); ?>"
									aria-invalid="false"
									required
								/>
							</div>
						<?php endif; ?>

						<?php if ( 'section' === $style_variant ) : ?>
							</div>
						<?php endif; ?>

						<div class="bw-newsletter-subscription-consent">
							<input
								id="<?php echo esc_attr( $consent_id ); ?>"
								class="bw-newsletter-subscription-consent__checkbox"
								type="checkbox"
								name="privacy"
								value="1"
								aria-describedby="<?php echo esc_attr( $message_id ); ?>"
								aria-invalid="false"
								<?php echo $consent_required ? 'required' : ''; ?>
							/>
							<?php if ( '' !== $privacy_custom_text ) : ?>
								<span class="bw-newsletter-subscription-consent__text bw-newsletter-subscription-consent__text--custom">
									<?php echo wp_kses_post( $privacy_custom_text ); ?>
								</span>
							<?php else : ?>
								<span class="bw-newsletter-subscription-consent__text">
									<label class="bw-newsletter-subscription-consent__label" for="<?php echo esc_attr( $consent_id ); ?>">
										<?php echo esc_html( $consent_text ); ?>
									</label>
									<?php
									$privacy_link_label = ! empty( $settings['privacy_link_label'] )
										? $settings['privacy_link_label']
										: __( 'Privacy Policy', 'bw' );
									?>
									<?php if ( ! empty( $privacy_url ) ) : ?>
										<a class="bw-newsletter-subscription-consent__link" href="<?php echo esc_url( $privacy_url ); ?>" target="_blank" rel="noopener noreferrer">
											<?php echo esc_html( $privacy_link_label ); ?>
										</a>
									<?php elseif ( ! empty( $privacy_link_label ) ) : ?>
										<span class="bw-newsletter-subscription-consent__link">
											<?php echo esc_html( $privacy_link_label ); ?>
										</span>
									<?php endif; ?>
								</span>
							<?php endif; ?>
						</div>

						<?php if ( 'footer' === $style_variant && ! $button_inside_email_field ) : ?>
							<button class="bw-newsletter-subscription-button" type="submit" aria-disabled="false">
								<span class="bw-newsletter-subscription-button__label"><?php echo esc_html( $button_text ); ?></span>
							</button>
						<?php endif; ?>

						<div
							id="<?php echo esc_attr( $message_id ); ?>"
							class="bw-newsletter-subscription-message"
							aria-live="polite"
							aria-atomic="true"
							role="status"
						></div>

					<?php if ( 'section' === $style_variant ) : ?>
						</div>
					<?php endif; ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Resolve name field visibility from canonical + legacy widget settings.
	 *
	 * Backward compatibility:
	 * - legacy footer widgets used `show_name_field`
	 * - legacy section widgets used `section_show_name_field`
	 * - unsaved/legacy section widgets without either key should remain hidden
	 *
	 * @param array  $widget_settings     Settings merged for display.
	 * @param array  $raw_widget_settings Raw saved widget settings.
	 * @param string $style_variant       Active style variant.
	 *
	 * @return bool
	 */
	private function resolve_show_name_field_visibility( $widget_settings, $raw_widget_settings, $style_variant ) {
		$widget_settings     = is_array( $widget_settings ) ? $widget_settings : array();
		$raw_widget_settings = is_array( $raw_widget_settings ) ? $raw_widget_settings : array();

		if ( 'section' === $style_variant ) {
			if ( array_key_exists( 'section_show_name_field', $widget_settings ) ) {
				return $this->is_widget_switch_enabled( $widget_settings['section_show_name_field'] );
			}

			if ( array_key_exists( 'section_show_name_field', $raw_widget_settings ) ) {
				return $this->is_widget_switch_enabled( $raw_widget_settings['section_show_name_field'] );
			}

			return false;
		}

		if ( array_key_exists( 'show_name_field', $widget_settings ) ) {
			return $this->is_widget_switch_enabled( $widget_settings['show_name_field'] );
		}

		if ( array_key_exists( 'show_name_field', $raw_widget_settings ) ) {
			return $this->is_widget_switch_enabled( $raw_widget_settings['show_name_field'] );
		}

		return false;
	}

	/**
	 * Normalize Elementor switcher values to a boolean state.
	 *
	 * Elementor commonly stores enabled switchers as `yes`, but older saved
	 * widgets or editor states can surface equivalent truthy values.
	 *
	 * @param mixed $value Switcher value.
	 *
	 * @return bool
	 */
	private function is_widget_switch_enabled( $value ) {
		if ( true === $value ) {
			return true;
		}

		if ( false === $value || null === $value ) {
			return false;
		}

		if ( is_int( $value ) || is_float( $value ) ) {
			return 0 !== (int) $value;
		}

		if ( ! is_string( $value ) ) {
			return false;
		}

		$value = strtolower( trim( $value ) );

		return in_array( $value, array( 'yes', '1', 'true', 'on' ), true );
	}

	/**
	 * Sanitize Elementor color control values for safe inline CSS usage.
	 *
	 * Accepts the formats the control can realistically emit for this widget:
	 * - hex / hex8
	 * - rgb()
	 * - rgba()
	 * - transparent
	 *
	 * Falls back to the provided default when the value is not recognized.
	 *
	 * @param string $value    Raw color value.
	 * @param string $fallback Fallback color.
	 *
	 * @return string
	 */
	private function sanitize_widget_color_value( $value, $fallback ) {
		$value    = is_string( $value ) ? trim( $value ) : '';
		$fallback = is_string( $fallback ) && '' !== trim( $fallback ) ? trim( $fallback ) : '#050505';

		if ( '' === $value ) {
			return $fallback;
		}

		$hex_color = sanitize_hex_color( $value );
		if ( is_string( $hex_color ) && '' !== $hex_color ) {
			return $hex_color;
		}

		if ( preg_match( '/^rgba?\(\s*(\d{1,3}\s*,\s*){2}\d{1,3}(?:\s*,\s*(0|1|0?\.\d+))?\s*\)$/i', $value ) ) {
			return preg_replace( '/\s+/', '', $value );
		}

		if ( 'transparent' === strtolower( $value ) ) {
			return 'transparent';
		}

		return $fallback;
	}
}
