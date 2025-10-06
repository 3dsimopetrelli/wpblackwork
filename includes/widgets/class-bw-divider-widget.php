<?php
use Elementor\Controls_Manager;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Widget_Bw_Divider extends Widget_Base {
    public function get_name() {
        return 'bw-divider';
    }

    public function get_title() {
        return 'BW Divider';
    }

    public function get_icon() {
        return 'eicon-divider';
    }

    public function get_categories() {
        return [ 'black-work' ];
    }

    public function get_style_depends() {
        if ( ! wp_style_is( 'bw-divider-style', 'registered' ) ) {
            $this->register_style();
        }

        return [ 'bw-divider-style' ];
    }

    protected function register_controls() {
        $this->start_controls_section( 'section_divider_spacing', [
            'label' => __( 'Spacing', 'bw-elementor-widgets' ),
        ] );

        $this->add_responsive_control( 'margin_top', [
            'label' => __( 'Margin Top', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range' => [
                'px' => [ 'min' => 0, 'max' => 200, 'step' => 1 ],
            ],
            'selectors' => [
                '{{WRAPPER}} .bw-divider' => 'margin-top: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'margin_bottom', [
            'label' => __( 'Margin Bottom', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range' => [
                'px' => [ 'min' => 0, 'max' => 200, 'step' => 1 ],
            ],
            'selectors' => [
                '{{WRAPPER}} .bw-divider' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'section_divider_style', [
            'label' => __( 'Divider', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'line_color', [
            'label'   => __( 'Line Color', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::COLOR,
            'default' => '#000000',
        ] );

        $this->add_control( 'line_thickness', [
            'label' => __( 'Line Thickness', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range' => [
                'px' => [ 'min' => 1, 'max' => 20, 'step' => 1 ],
            ],
            'default' => [ 'size' => 1, 'unit' => 'px' ],
        ] );

        $this->add_control( 'divider_style', [
            'label'   => __( 'Divider Style', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'style1' => __( 'Style 1', 'bw-elementor-widgets' ),
                'style2' => __( 'Style 2', 'bw-elementor-widgets' ),
            ],
            'default' => 'style1',
        ] );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $wrapper_styles = [];

        if ( ! empty( $settings['line_color'] ) ) {
            $wrapper_styles[] = '--bw-divider-color: ' . esc_attr( $settings['line_color'] ) . ';';
        }

        $line_thickness = isset( $settings['line_thickness']['size'] ) ? (float) $settings['line_thickness']['size'] : 0;
        if ( $line_thickness > 0 ) {
            $wrapper_styles[] = '--bw-divider-stroke-width: ' . $line_thickness . 'px;';
        }

        $this->add_render_attribute( 'wrapper', 'class', 'bw-divider' );

        if ( ! empty( $wrapper_styles ) ) {
            $this->add_render_attribute( 'wrapper', 'style', implode( ' ', $wrapper_styles ) );
        }

        $style_choice = ! empty( $settings['divider_style'] ) ? $settings['divider_style'] : 'style1';
        $svg_filename = 'style2' === $style_choice ? 'img-divider-2.svg' : 'img-divider-1.svg';

        $svg_markup = $this->get_svg_markup( $svg_filename );
        $image_url  = $this->get_asset_url( 'assets/img/' . $svg_filename );

        echo '<div ' . $this->get_render_attribute_string( 'wrapper' ) . '>';

        if ( $svg_markup ) {
            echo $svg_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            printf(
                '<img src="%1$s" alt="%2$s" />',
                esc_url( $image_url ),
                esc_attr__( 'Divider', 'bw-elementor-widgets' )
            );
        }

        echo '</div>';
    }

    private function register_style() {
        $css_relative_path = 'assets/css/bw-divider.css';
        $css_file          = $this->get_plugin_path( $css_relative_path );
        $version           = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

        wp_register_style(
            'bw-divider-style',
            $this->get_asset_url( $css_relative_path ),
            [],
            $version
        );
    }

    private function get_svg_markup( $filename ) {
        $file_path = $this->get_plugin_path( 'assets/img/' . $filename );

        if ( ! file_exists( $file_path ) ) {
            return '';
        }

        $svg_content = file_get_contents( $file_path );

        if ( false === $svg_content ) {
            return '';
        }

        return wp_kses( $svg_content, $this->get_allowed_svg_html() );
    }

    private function get_allowed_svg_html() {
        return [
            'svg' => [
                'class' => true,
                'xmlns' => true,
                'width' => true,
                'height' => true,
                'viewBox' => true,
                'fill' => true,
                'stroke' => true,
                'stroke-width' => true,
                'role' => true,
                'aria-hidden' => true,
                'focusable' => true,
                'style' => true,
                'preserveAspectRatio' => true,
            ],
            'g' => [
                'clip-path' => true,
                'fill' => true,
                'stroke' => true,
                'stroke-width' => true,
                'style' => true,
                'class' => true,
                'id' => true,
            ],
            'path' => [
                'd' => true,
                'fill' => true,
                'stroke' => true,
                'stroke-width' => true,
                'style' => true,
                'clip-path' => true,
                'transform' => true,
                'id' => true,
            ],
            'rect' => [
                'width' => true,
                'height' => true,
                'fill' => true,
                'stroke' => true,
                'stroke-width' => true,
                'style' => true,
                'clip-path' => true,
                'transform' => true,
                'x' => true,
                'y' => true,
                'rx' => true,
                'ry' => true,
                'id' => true,
            ],
            'defs' => [
                'id' => true,
            ],
            'clipPath' => [
                'id' => true,
            ],
            'clippath' => [
                'id' => true,
            ],
            'title' => true,
            'desc' => true,
        ];
    }

    private function get_asset_url( $relative_path ) {
        $relative_path = ltrim( $relative_path, '/' );

        if ( defined( 'BW_MEW_URL' ) ) {
            return trailingslashit( BW_MEW_URL ) . $relative_path;
        }

        return plugins_url( $relative_path, $this->get_plugin_main_file() );
    }

    private function get_plugin_path( $relative_path ) {
        $relative_path = ltrim( $relative_path, '/' );

        if ( defined( 'BW_MEW_PATH' ) ) {
            return trailingslashit( BW_MEW_PATH ) . $relative_path;
        }

        return trailingslashit( dirname( $this->get_plugin_main_file() ) ) . $relative_path;
    }

    private function get_plugin_main_file() {
        return dirname( __FILE__, 3 ) . '/bw-main-elementor-widgets.php';
    }
}
