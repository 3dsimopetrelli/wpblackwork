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
        return [ 'blackwork' ];
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
        $relative_svg_path = 'assets/img/' . $svg_filename;

        $svg_markup = $this->get_svg_markup( $svg_filename );
        $image_url  = $this->get_asset_url( $relative_svg_path );

        echo '<div ' . $this->get_render_attribute_string( 'wrapper' ) . '>';

        if ( $svg_markup ) {
            echo $svg_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            printf(
                '<img src="%1$s" alt="%2$s" class="bw-divider__image" />',
                esc_url( $image_url ),
                esc_attr__( 'Divider', 'bw-elementor-widgets' )
            );
        }

        echo '</div>';
    }

    private function register_style() {
        $css_relative_path = 'assets/css/bw-divider.css';
        $css_file          = $this->get_asset_path( $css_relative_path );
        $version           = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

        wp_register_style(
            'bw-divider-style',
            $this->get_asset_url( $css_relative_path ),
            [],
            $version
        );
    }

    private function get_svg_markup( $filename ) {
        $file_path = $this->get_asset_path( 'assets/img/' . $filename );

        if ( ! file_exists( $file_path ) ) {
            return '';
        }

        $svg_content = file_get_contents( $file_path );

        if ( false === $svg_content ) {
            return '';
        }

        $prepared_svg = $this->prepare_svg_markup( $svg_content );

        return wp_kses( $prepared_svg, $this->get_allowed_svg_html() );
    }

    private function prepare_svg_markup( $svg_content ) {
        if ( empty( $svg_content ) ) {
            return '';
        }

        $libxml_previous_state = libxml_use_internal_errors( true );

        $dom = new DOMDocument();

        if ( ! $dom->loadXML( $svg_content ) ) {
            libxml_clear_errors();
            libxml_use_internal_errors( $libxml_previous_state );

            return $svg_content;
        }

        libxml_clear_errors();
        libxml_use_internal_errors( $libxml_previous_state );

        $paths = $dom->getElementsByTagName( 'path' );

        foreach ( $paths as $path ) {
            $path->setAttribute( 'fill', 'none' );
            $path->setAttribute( 'stroke', 'currentColor' );
            $path->removeAttribute( 'style' );
        }

        return $dom->saveXML( $dom->documentElement );
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
            'title' => true,
            'desc' => true,
        ];
    }

    private function get_asset_url( $relative_path ) {
        $base_url = defined( 'BW_MEW_URL' ) ? BW_MEW_URL : plugins_url( '/', $this->get_plugin_main_file() );

        return trailingslashit( $base_url ) . ltrim( $relative_path, '/' );
    }

    private function get_asset_path( $relative_path ) {
        $base_path = defined( 'BW_MEW_PATH' ) ? BW_MEW_PATH : dirname( $this->get_plugin_main_file() );

        return trailingslashit( $base_path ) . ltrim( $relative_path, '/' );
    }

    private function get_plugin_main_file() {
        return dirname( __FILE__, 3 ) . '/bw-main-elementor-widgets.php';
    }
}
