<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Title_Product_Widget extends Widget_Base {
    public function get_name() {
        return 'bw-title-product';
    }

    public function get_title() {
        return __( 'Title Product', 'bw-elementor-widgets' );
    }

    public function get_icon() {
        return 'eicon-t-letter';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __( 'Content', 'bw-elementor-widgets' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'html_tag',
            [
                'label'   => __( 'HTML Tag', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'h1',
                'options' => [
                    'h1'   => 'H1',
                    'h2'   => 'H2',
                    'h3'   => 'H3',
                    'h4'   => 'H4',
                    'h5'   => 'H5',
                    'h6'   => 'H6',
                    'div'  => 'div',
                    'span' => 'span',
                    'p'    => 'p',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_title',
            [
                'label' => __( 'Title', 'bw-elementor-widgets' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'title_alignment',
            [
                'label'   => __( 'Alignment', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __( 'Left', 'bw-elementor-widgets' ),
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __( 'Center', 'bw-elementor-widgets' ),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __( 'Right', 'bw-elementor-widgets' ),
                        'icon'  => 'eicon-text-align-right',
                    ],
                    'justify' => [
                        'title' => __( 'Justified', 'bw-elementor-widgets' ),
                        'icon'  => 'eicon-text-align-justify',
                    ],
                ],
                'default'   => 'left',
                'selectors' => [
                    '{{WRAPPER}} .bw-title-product-widget' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'           => 'title_typography',
                'selector'       => '{{WRAPPER}} .bw-title-product',
                'fields_options' => [
                    'typography'  => [
                        'default' => 'yes',
                    ],
                    'font_size'   => [
                        'default' => [
                            'size' => 100,
                            'unit' => 'px',
                        ],
                    ],
                    'line_height' => [
                        'default' => [
                            'size' => 110,
                            'unit' => '%',
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label'     => __( 'Text Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-title-product' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $product = $this->resolve_product();

        if ( ! $product ) {
            return;
        }

        $settings = $this->get_settings_for_display();
        $tag      = isset( $settings['html_tag'] ) ? sanitize_key( $settings['html_tag'] ) : 'h1';
        $allowed  = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p' ];

        if ( ! in_array( $tag, $allowed, true ) ) {
            $tag = 'h1';
        }

        $title = trim( (string) $product->get_name() );

        if ( '' === $title ) {
            return;
        }

        $this->add_render_attribute( 'wrapper', 'class', 'bw-title-product-widget' );
        $this->add_render_attribute( 'title', 'class', 'bw-title-product' );

        echo '<div ' . $this->get_render_attribute_string( 'wrapper' ) . '>';
        printf(
            '<%1$s %2$s>%3$s</%1$s>',
            tag_escape( $tag ),
            $this->get_render_attribute_string( 'title' ),
            esc_html( $title )
        );
        echo '</div>';
    }

    /**
     * Resolve the current WooCommerce product using the safest shared contract.
     *
     * Resolution order:
     * 1. Current queried product object.
     * 2. Shared Theme Builder Lite product context resolver.
     * 3. Global WooCommerce product object as final fallback.
     *
     * @return WC_Product|null
     */
    private function resolve_product() {
        if ( ! function_exists( 'wc_get_product' ) ) {
            return null;
        }

        $queried_id = absint( get_queried_object_id() );
        if ( $queried_id > 0 && 'product' === get_post_type( $queried_id ) ) {
            $queried_product = wc_get_product( $queried_id );
            if ( $queried_product instanceof WC_Product ) {
                return $queried_product;
            }
        }

        if ( function_exists( 'bw_tbl_resolve_product_context_id' ) ) {
            $resolution = bw_tbl_resolve_product_context_id( [ '__widget_class' => __CLASS__ ] );
            $product_id = isset( $resolution['id'] ) ? absint( $resolution['id'] ) : 0;

            if ( $product_id > 0 ) {
                $resolved_product = wc_get_product( $product_id );
                if ( $resolved_product instanceof WC_Product ) {
                    return $resolved_product;
                }
            }
        }

        global $product;
        if ( $product instanceof WC_Product ) {
            return $product;
        }

        return null;
    }
}
