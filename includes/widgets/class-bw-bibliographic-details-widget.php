<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Widget_Bw_Bibliographic_Details extends Widget_Base {
    public function get_name() {
        return 'bw-bibliographic-details';
    }

    public function get_title() {
        return __( 'BW Bibliographic Details', 'bw' );
    }

    public function get_icon() {
        return 'eicon-table';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_style_depends() {
        if ( ! wp_style_is( 'bw-bibliographic-details-style', 'registered' ) ) {
            $this->register_style();
        }

        return [ 'bw-bibliographic-details-style' ];
    }

    protected function register_controls() {
        $this->start_controls_section( 'section_style_box', [
            'label' => __( 'Box Style', 'bw' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'box_border_color', [
            'label' => __( 'Border Color', 'bw' ),
            'type'  => Controls_Manager::COLOR,
            'default' => '#000000',
            'selectors' => [
                '{{WRAPPER}} .bw-biblio-widget' => 'border-color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'box_border_width', [
            'label' => __( 'Border Width', 'bw' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range' => [
                'px' => [ 'min' => 0, 'max' => 10 ],
            ],
            'default' => [ 'size' => 1, 'unit' => 'px' ],
            'selectors' => [
                '{{WRAPPER}} .bw-biblio-widget' => 'border-width: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'box_border_radius', [
            'label' => __( 'Border Radius', 'bw' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range' => [ 'px' => [ 'min' => 0, 'max' => 50 ] ],
            'default' => [ 'size' => 8, 'unit' => 'px' ],
            'selectors' => [
                '{{WRAPPER}} .bw-biblio-widget' => 'border-radius: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'box_padding', [
            'label' => __( 'Padding', 'bw' ),
            'type'  => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%' ],
            'default' => [
                'top' => 16,
                'right' => 16,
                'bottom' => 16,
                'left' => 16,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} .bw-biblio-widget' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'section_style_title', [
            'label' => __( 'Title', 'bw' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name' => 'title_typography',
            'selector' => '{{WRAPPER}} .bw-biblio-title',
        ] );

        $this->add_control( 'title_color', [
            'label' => __( 'Color', 'bw' ),
            'type'  => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-biblio-title' => 'color: {{VALUE}};',
            ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'section_style_dividers', [
            'label' => __( 'Row Dividers', 'bw' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'divider_color', [
            'label' => __( 'Divider Color', 'bw' ),
            'type'  => Controls_Manager::COLOR,
            'default' => '#d9d9d9',
            'selectors' => [
                '{{WRAPPER}} .bw-biblio-row:not(:last-child)' => 'border-bottom-color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'divider_width', [
            'label' => __( 'Divider Weight', 'bw' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range' => [ 'px' => [ 'min' => 0, 'max' => 10 ] ],
            'default' => [ 'size' => 1, 'unit' => 'px' ],
            'selectors' => [
                '{{WRAPPER}} .bw-biblio-row:not(:last-child)' => 'border-bottom-width: {{SIZE}}{{UNIT}}; border-bottom-style: solid;',
            ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'section_style_labels', [
            'label' => __( 'Labels Typography', 'bw' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name' => 'labels_typography',
            'selector' => '{{WRAPPER}} .bw-biblio-label',
        ] );

        $this->add_control( 'labels_color', [
            'label' => __( 'Color', 'bw' ),
            'type'  => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-biblio-label' => 'color: {{VALUE}};',
            ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'section_style_values', [
            'label' => __( 'Values Typography', 'bw' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name' => 'values_typography',
            'selector' => '{{WRAPPER}} .bw-biblio-value',
        ] );

        $this->add_control( 'values_color', [
            'label' => __( 'Color', 'bw' ),
            'type'  => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-biblio-value' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_responsive_control( 'values_alignment', [
            'label' => __( 'Text Alignment', 'bw' ),
            'type'  => Controls_Manager::CHOOSE,
            'options' => [
                'left' => [
                    'title' => __( 'Left', 'bw' ),
                    'icon'  => 'eicon-text-align-left',
                ],
                'center' => [
                    'title' => __( 'Center', 'bw' ),
                    'icon'  => 'eicon-text-align-center',
                ],
                'right' => [
                    'title' => __( 'Right', 'bw' ),
                    'icon'  => 'eicon-text-align-right',
                ],
            ],
            'default' => 'left',
            'selectors' => [
                '{{WRAPPER}} .bw-biblio-value' => 'text-align: {{VALUE}};',
            ],
        ] );

        $this->end_controls_section();
    }

    protected function render() {
        $product_id = get_the_ID();

        if ( ! $product_id ) {
            return;
        }

        if ( function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $product_id );

            if ( ! $product ) {
                return;
            }

            $product_id = $product->get_id();
        }

        $fields = function_exists( 'bw_get_bibliographic_fields' )
            ? array_merge(
                bw_get_bibliographic_fields(),
                function_exists( 'bw_get_prints_bibliographic_fields' ) ? bw_get_prints_bibliographic_fields() : []
            )
            : [
                '_bw_biblio_title'     => __( 'Title', 'bw' ),
                '_bw_biblio_author'    => __( 'Author', 'bw' ),
                '_bw_biblio_publisher' => __( 'Publisher', 'bw' ),
                '_bw_biblio_year'      => __( 'Year', 'bw' ),
                '_bw_biblio_language'  => __( 'Language', 'bw' ),
                '_bw_biblio_binding'   => __( 'Binding', 'bw' ),
                '_bw_biblio_pages'     => __( 'Pages', 'bw' ),
                '_bw_biblio_edition'   => __( 'Edition', 'bw' ),
                '_bw_biblio_condition' => __( 'Condition', 'bw' ),
                '_bw_biblio_location'  => __( 'Location', 'bw' ),
                '_print_artist'        => __( 'Artist', 'bw' ),
                '_print_publisher'     => __( 'Publisher', 'bw' ),
                '_print_year'          => __( 'Year', 'bw' ),
                '_print_technique'     => __( 'Technique', 'bw' ),
                '_print_material'      => __( 'Material', 'bw' ),
                '_print_plate_size'    => __( 'Plate Size', 'bw' ),
                '_print_condition'     => __( 'Condition', 'bw' ),
            ];

        $this->add_render_attribute( 'wrapper', 'class', 'bw-biblio-widget' );

        echo '<div ' . $this->get_render_attribute_string( 'wrapper' ) . '>';
        echo '  <div class="bw-biblio-title">' . esc_html__( 'Bibliographic details', 'bw' ) . '</div>';
        echo '  <div class="bw-biblio-table">';

        foreach ( $fields as $meta_key => $label ) {
            $value = get_post_meta( $product_id, $meta_key, true );

            if ( '' === $value ) {
                continue;
            }

            echo '    <div class="bw-biblio-row">';
            echo '      <div class="bw-biblio-label">' . esc_html( $label ) . '</div>';
            echo '      <div class="bw-biblio-value">' . esc_html( $value ) . '</div>';
            echo '    </div>';
        }

        echo '  </div>';
        echo '</div>';
    }

    private function register_style() {
        $css_relative_path = 'assets/css/bw-bibliographic-details.css';
        $css_file          = $this->get_asset_path( $css_relative_path );
        $version           = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

        wp_register_style(
            'bw-bibliographic-details-style',
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
