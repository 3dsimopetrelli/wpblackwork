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
            'selectors' => [
                '{{WRAPPER}} .bw-divider' => '--divider-color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'line_thickness', [
            'label' => __( 'Line Thickness', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range' => [
                'px' => [ 'min' => 1, 'max' => 20 ],
            ],
            'default' => [ 'size' => 1, 'unit' => 'px' ],
            'selectors' => [
                '{{WRAPPER}} .bw-divider' => '--divider-thickness: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'flags_size', [
            'label' => __( 'Flags Size', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range' => [
                'px' => [ 'min' => 8, 'max' => 120 ],
            ],
            'default' => [ 'size' => 24, 'unit' => 'px' ],
            'selectors' => [
                '{{WRAPPER}} .bw-divider' => '--flags-size: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'flags_gap', [
            'label' => __( 'Flags Gap', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range' => [
                'px' => [ 'min' => 0, 'max' => 80 ],
            ],
            'default' => [ 'size' => 8, 'unit' => 'px' ],
            'selectors' => [
                '{{WRAPPER}} .bw-divider' => '--flags-gap: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'flags_margin_top', [
            'label' => __( 'Flags Margin Top', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range' => [
                'px' => [ 'min' => -50, 'max' => 100 ],
            ],
            'default' => [ 'size' => 0, 'unit' => 'px' ],
            'selectors' => [
                '{{WRAPPER}} .bw-divider' => '--flags-margin-top: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'left_svg', [
            'label' => __( 'Left SVG Icon', 'bw' ),
            'type' => Controls_Manager::MEDIA,
            'media_types' => [ 'svg' ],
            'default' => [
                'url' => BW_MEW_URL . 'assets/img/img-divider-1.svg',
            ],
        ] );

        $this->add_control( 'right_svg', [
            'label' => __( 'Right SVG Icon', 'bw' ),
            'type' => Controls_Manager::MEDIA,
            'media_types' => [ 'svg' ],
            'default' => [
                'url' => BW_MEW_URL . 'assets/img/img-divider-2.svg',
            ],
        ] );

        $this->add_control( 'swap_icons', [
            'label' => __( 'Swap Icons Position', 'bw-elementor-widgets' ),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => __( 'Yes', 'bw-elementor-widgets' ),
            'label_off' => __( 'No', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default' => '',
            'description' => __( 'Swap the position of left and right icons', 'bw-elementor-widgets' ),
        ] );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $left  = ! empty( $settings['left_svg']['url'] )
            ? $settings['left_svg']['url']
            : BW_MEW_URL . 'assets/img/img-divider-1.svg';

        $right = ! empty( $settings['right_svg']['url'] )
            ? $settings['right_svg']['url']
            : BW_MEW_URL . 'assets/img/img-divider-2.svg';

        // Swap icons if the swap_icons setting is enabled
        if ( 'yes' === $settings['swap_icons'] ) {
            $temp  = $left;
            $left  = $right;
            $right = $temp;
        }

        $this->add_render_attribute( 'divider', 'class', 'bw-divider' );

        echo '<div ' . $this->get_render_attribute_string( 'divider' ) . '>';
        echo '    <div class="bw-divider__line" role="presentation"></div>';
        echo '    <div class="bw-divider__flags" aria-hidden="true">';
        echo '        <img class="bw-divider__flag bw-divider__flag--left"  src="' . esc_url( $left ) . '"  alt="" />';
        echo '        <img class="bw-divider__flag bw-divider__flag--right" src="' . esc_url( $right ) . '" alt="" />';
        echo '    </div>';
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
