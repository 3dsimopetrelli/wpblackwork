<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Big_Text_Widget extends Widget_Base {
    private const DEFAULT_TEXT = "Blackwork.pro — heritage studio and digital library.\nAn archive spanning centuries of antiquarian material,\nillustrated rare books and prints, curated and\nprepared for creative use, made available in vector\nand high-resolution formats for designers, artists,\nand researchers.";

    public function get_name() {
        return 'bw-big-text';
    }

    public function get_title() {
        return __( 'BW-UI Big Text', 'bw' );
    }

    public function get_icon() {
        return 'eicon-t-letter';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_style_depends() {
        if ( ! wp_style_is( 'bw-big-text-style', 'registered' ) && function_exists( 'bw_register_big_text_widget_assets' ) ) {
            bw_register_big_text_widget_assets();
        } elseif ( ! wp_style_is( 'bw-big-text-style', 'registered' ) && function_exists( 'bw_register_widget_assets' ) ) {
            bw_register_widget_assets( 'big-text', [], false );
        }

        return [ 'bw-big-text-style' ];
    }

    protected function register_controls() {
        $this->register_content_controls();
        $this->register_style_controls();
    }

    private function register_content_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __( 'Content', 'bw' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'text_content',
            [
                'label'       => __( 'Text', 'bw' ),
                'type'        => Controls_Manager::TEXTAREA,
                'default'     => self::DEFAULT_TEXT,
                'placeholder' => __( 'Write the statement shown by the widget.', 'bw' ),
                'description' => __( 'Allowed inline HTML: <strong>, <em>, <a>, <br>. In Editorial Lines mode, each non-empty newline becomes a line group.', 'bw' ),
                'label_block' => true,
                'dynamic'     => [ 'active' => true ],
            ]
        );

        $this->add_control(
            'composition_mode',
            [
                'label'   => __( 'Composition Mode', 'bw' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'editorial_lines',
                'options' => [
                    'auto_balance'    => __( 'Auto Balance', 'bw' ),
                    'controlled_width' => __( 'Controlled Width', 'bw' ),
                    'editorial_lines' => __( 'Editorial Lines', 'bw' ),
                ],
            ]
        );

        $this->add_responsive_control(
            'max_text_width',
            [
                'label'      => __( 'Max Text Width', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'ch', 'rem', '%', 'vw', 'px' ],
                'range'      => [
                    'ch'  => [ 'min' => 8, 'max' => 48, 'step' => 0.5 ],
                    'rem' => [ 'min' => 8, 'max' => 60, 'step' => 0.25 ],
                    '%'   => [ 'min' => 20, 'max' => 100, 'step' => 1 ],
                    'vw'  => [ 'min' => 20, 'max' => 100, 'step' => 1 ],
                    'px'  => [ 'min' => 240, 'max' => 2200, 'step' => 10 ],
                ],
                'default'    => [
                    'size' => 24,
                    'unit' => 'ch',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-big-text__inner' => '--bw-big-text-max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'text_align',
            [
                'label'   => __( 'Alignment', 'bw' ),
                'type'    => Controls_Manager::CHOOSE,
                'options' => [
                    'left'   => [
                        'title' => __( 'Left', 'bw' ),
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __( 'Center', 'bw' ),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'right'  => [
                        'title' => __( 'Right', 'bw' ),
                        'icon'  => 'eicon-text-align-right',
                    ],
                ],
                'default'   => 'left',
                'selectors' => [
                    '{{WRAPPER}} .bw-big-text' => '{{VALUE}};',
                ],
                'selectors_dictionary' => [
                    'left'   => 'text-align: left; --bw-big-text-line-align: flex-start',
                    'center' => 'text-align: center; --bw-big-text-line-align: center',
                    'right'  => 'text-align: right; --bw-big-text-line-align: flex-end',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function register_style_controls() {
        $this->start_controls_section(
            'section_style_typography',
            [
                'label' => __( 'Typography', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'font_size_mode',
            [
                'label'   => __( 'Font Size Mode', 'bw' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'fluid',
                'options' => [
                    'fluid' => __( 'Fluid', 'bw' ),
                    'fixed' => __( 'Fixed', 'bw' ),
                ],
            ]
        );

        $this->add_control(
            'fluid_font_size_min',
            [
                'label'      => __( 'Fluid Min Font Size', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [ 'min' => 18, 'max' => 160, 'step' => 1 ],
                ],
                'default'    => [
                    'size' => 36,
                    'unit' => 'px',
                ],
                'condition'  => [
                    'font_size_mode' => 'fluid',
                ],
            ]
        );

        $this->add_control(
            'fluid_font_size_max',
            [
                'label'      => __( 'Fluid Max Font Size', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [ 'min' => 18, 'max' => 220, 'step' => 1 ],
                ],
                'default'    => [
                    'size' => 88,
                    'unit' => 'px',
                ],
                'condition'  => [
                    'font_size_mode' => 'fluid',
                ],
            ]
        );

        $this->add_control(
            'fluid_viewport_min',
            [
                'label'      => __( 'Fluid Min Viewport', 'bw' ),
                'type'       => Controls_Manager::NUMBER,
                'default'    => 480,
                'min'        => 240,
                'max'        => 4000,
                'step'       => 1,
                'condition'  => [
                    'font_size_mode' => 'fluid',
                ],
            ]
        );

        $this->add_control(
            'fluid_viewport_max',
            [
                'label'      => __( 'Fluid Max Viewport', 'bw' ),
                'type'       => Controls_Manager::NUMBER,
                'default'    => 1600,
                'min'        => 320,
                'max'        => 5000,
                'step'       => 1,
                'condition'  => [
                    'font_size_mode' => 'fluid',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'           => 'text_typography',
                'selector'       => '{{WRAPPER}} .bw-big-text__content',
                'exclude'        => [ 'font_size', 'line_height', 'letter_spacing' ],
                'fields_options' => [
                    'font_weight' => [
                        'default' => '400',
                    ],
                ],
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label'     => __( 'Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#111111',
                'selectors' => [
                    '{{WRAPPER}} .bw-big-text__content'   => 'color: {{VALUE}};',
                    '{{WRAPPER}} .bw-big-text__content a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'line_height',
            [
                'label'      => __( 'Line Height', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'em', '%', 'px' ],
                'range'      => [
                    'em' => [ 'min' => 0.7, 'max' => 2, 'step' => 0.01 ],
                    '%'  => [ 'min' => 70, 'max' => 220, 'step' => 1 ],
                    'px' => [ 'min' => 18, 'max' => 240, 'step' => 1 ],
                ],
                'default'    => [
                    'size' => 0.94,
                    'unit' => 'em',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-big-text__content' => 'line-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'letter_spacing',
            [
                'label'      => __( 'Letter Spacing', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'em', 'px' ],
                'range'      => [
                    'em' => [ 'min' => -0.2, 'max' => 0.2, 'step' => 0.005 ],
                    'px' => [ 'min' => -8, 'max' => 12, 'step' => 0.1 ],
                ],
                'default'    => [
                    'size' => -0.04,
                    'unit' => 'em',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-big-text__content' => 'letter-spacing: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_layout',
            [
                'label' => __( 'Layout', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'section_padding',
            [
                'label'      => __( 'Section Padding', 'bw' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'rem', '%' ],
                'default'    => [
                    'top'      => 0,
                    'right'    => 0,
                    'bottom'   => 0,
                    'left'     => 0,
                    'unit'     => 'px',
                    'isLinked' => false,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-big-text' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'editorial_line_gap',
            [
                'label'      => __( 'Editorial Line Gap', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'em', 'px' ],
                'range'      => [
                    'em' => [ 'min' => -0.3, 'max' => 1.5, 'step' => 0.01 ],
                    'px' => [ 'min' => -40, 'max' => 80, 'step' => 1 ],
                ],
                'default'    => [
                    'size' => 0.02,
                    'unit' => 'em',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-big-text__lines' => 'row-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings      = $this->get_settings_for_display();
        $raw_content   = isset( $settings['text_content'] ) ? (string) $settings['text_content'] : '';
        $content       = '' !== trim( $raw_content ) ? $raw_content : self::DEFAULT_TEXT;
        $mode          = isset( $settings['composition_mode'] ) ? sanitize_key( $settings['composition_mode'] ) : 'editorial_lines';
        $font_size_mode = isset( $settings['font_size_mode'] ) ? sanitize_key( $settings['font_size_mode'] ) : 'fluid';

        $this->add_render_attribute(
            'wrapper',
            'class',
            [
                'bw-big-text',
                'bw-big-text--mode-' . $mode,
                'bw-big-text--font-' . $font_size_mode,
            ]
        );

        $fluid_expression = $this->build_fluid_font_size_expression( $settings );
        if ( '' !== $fluid_expression ) {
            $this->add_render_attribute( 'wrapper', 'style', '--bw-big-text-fluid-size: ' . $fluid_expression . ';' );
        }

        echo '<section ' . $this->get_render_attribute_string( 'wrapper' ) . '>';
        echo '<div class="bw-big-text__inner">';

        if ( 'editorial_lines' === $mode ) {
            $line_groups = $this->build_editorial_line_groups( $content );

            if ( empty( $line_groups ) ) {
                echo '</div></section>';
                return;
            }

            echo '<div class="bw-big-text__content bw-big-text__lines">';
            foreach ( $line_groups as $line_group ) {
                echo '<div class="bw-big-text__line">' . $line_group . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized by build_editorial_line_groups().
            }
            echo '</div>';
        } else {
            $sanitized_content = $this->sanitize_text_content( $this->normalize_auto_content( $content ) );

            if ( '' === trim( wp_strip_all_tags( $sanitized_content ) ) ) {
                echo '</div></section>';
                return;
            }

            echo '<div class="bw-big-text__content bw-big-text__text">' . $sanitized_content . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized by sanitize_text_content().
        }

        echo '</div>';
        echo '</section>';
    }

    private function build_fluid_font_size_expression( array $settings ): string {
        $font_size_mode = isset( $settings['font_size_mode'] ) ? sanitize_key( $settings['font_size_mode'] ) : 'fluid';
        if ( 'fluid' !== $font_size_mode ) {
            return '';
        }

        $min_size = isset( $settings['fluid_font_size_min']['size'] ) ? (float) $settings['fluid_font_size_min']['size'] : 36;
        $max_size = isset( $settings['fluid_font_size_max']['size'] ) ? (float) $settings['fluid_font_size_max']['size'] : 88;
        $min_vw   = isset( $settings['fluid_viewport_min'] ) ? (float) $settings['fluid_viewport_min'] : 480;
        $max_vw   = isset( $settings['fluid_viewport_max'] ) ? (float) $settings['fluid_viewport_max'] : 1600;

        $min_size = max( 1, $min_size );
        $max_size = max( $min_size, $max_size );
        $min_vw   = max( 1, $min_vw );
        $max_vw   = max( $min_vw + 1, $max_vw );

        $slope      = ( $max_size - $min_size ) / ( $max_vw - $min_vw );
        $preferred  = $min_size - ( $slope * $min_vw );
        $preferred  = round( $preferred, 4 );
        $slope_vw   = round( $slope * 100, 4 );
        $min_size   = round( $min_size, 4 );
        $max_size   = round( $max_size, 4 );

        return sprintf(
            'clamp(%1$spx, calc(%2$spx + %3$svw), %4$spx)',
            $this->format_number( $min_size ),
            $this->format_number( $preferred ),
            $this->format_number( $slope_vw ),
            $this->format_number( $max_size )
        );
    }

    private function build_editorial_line_groups( string $content ): array {
        $raw_lines   = preg_split( '/\R+/u', $content ) ?: [];
        $line_groups = [];

        foreach ( $raw_lines as $raw_line ) {
            $line = trim( (string) $raw_line );
            if ( '' === $line ) {
                continue;
            }

            $sanitized_line = $this->sanitize_text_content( $line );
            if ( '' === trim( wp_strip_all_tags( $sanitized_line ) ) ) {
                continue;
            }

            $line_groups[] = $sanitized_line;
        }

        return $line_groups;
    }

    private function normalize_auto_content( string $content ): string {
        return preg_replace( '/\s*\R+\s*/u', ' ', trim( $content ) ) ?: '';
    }

    private function sanitize_text_content( string $content ): string {
        $allowed_html = [
            'br'     => [],
            'strong' => [],
            'b'      => [],
            'em'     => [],
            'i'      => [],
            'a'      => [
                'href'   => [],
                'target' => [],
                'rel'    => [],
                'title'  => [],
            ],
        ];

        return wp_kses( $content, $allowed_html );
    }

    private function format_number( float $value ): string {
        $formatted = rtrim( rtrim( number_format( $value, 4, '.', '' ), '0' ), '.' );

        return '' !== $formatted ? $formatted : '0';
    }
}
