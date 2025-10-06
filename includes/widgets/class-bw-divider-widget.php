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
                'px' => [ 'min' => 1, 'max' => 20, 'step' => 1 ],
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
                'px' => [ 'min' => 8, 'max' => 80, 'step' => 1 ],
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
                'px' => [ 'min' => 0, 'max' => 40, 'step' => 1 ],
            ],
            'default' => [ 'size' => 8, 'unit' => 'px' ],
            'selectors' => [
                '{{WRAPPER}} .bw-divider' => '--flags-gap: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();
    }

    protected function render() {
        $s = $this->get_settings_for_display();

        $color = ! empty( $s['line_color'] ) ? $s['line_color'] : '#000000';
        $thick_unit = isset( $s['line_thickness']['unit'] ) ? $s['line_thickness']['unit'] : 'px';
        $fsize_unit = isset( $s['flags_size']['unit'] ) ? $s['flags_size']['unit'] : 'px';
        $fgap_unit  = isset( $s['flags_gap']['unit'] ) ? $s['flags_gap']['unit'] : 'px';

        $thick = ! empty( $s['line_thickness']['size'] ) ? $s['line_thickness']['size'] . $thick_unit : '1px';
        $fsize = ! empty( $s['flags_size']['size'] ) ? $s['flags_size']['size'] . $fsize_unit : '24px';
        $fgap  = ! empty( $s['flags_gap']['size'] ) ? $s['flags_gap']['size'] . $fgap_unit : '8px';

        $left  = BW_MEW_URL . 'assets/img/img-divider-1.svg';
        $right = BW_MEW_URL . 'assets/img/img-divider-2.svg';

        echo '<div class="bw-divider"
                  style="--divider-color:' . esc_attr( $color ) . ';
                         --divider-thickness:' . esc_attr( $thick ) . ';
                         --flags-size:' . esc_attr( $fsize ) . ';
                         --flags-gap:' . esc_attr( $fgap ) . ';">
                <div class="bw-divider__line" role="presentation"></div>
                <div class="bw-divider__flags" aria-hidden="true">
                    <img class="bw-divider__flag bw-divider__flag--left"  src="' . esc_url( $left ) . '"  alt="" />
                    <img class="bw-divider__flag bw-divider__flag--right" src="' . esc_url( $right ) . '" alt="" />
                </div>
              </div>';
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
