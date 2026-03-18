<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BW Product Description Widget.
 *
 * Displays the current WooCommerce product description with preserved HTML markup.
 */
class Widget_Bw_Product_Description extends Widget_Base {

    public function get_name() {
        return 'bw-product-description';
    }

    public function get_title() {
        return __( 'BW-SP Product Description', 'bw' );
    }

    public function get_icon() {
        return 'eicon-product-content';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    protected function register_controls() {
        $this->start_controls_section( 'section_content', [
            'label' => __( 'Content', 'bw' ),
        ] );

        $this->add_control( 'product_id', [
            'label'       => __( 'Product ID', 'bw' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => __( 'Leave empty to use current product', 'bw' ),
            'description' => __( 'ID of the product to preview in editor. Leave empty on single-product templates.', 'bw' ),
            'label_block' => true,
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'section_style', [
            'label' => __( 'Style', 'bw' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_responsive_control( 'text_align', [
            'label'   => __( 'Alignment', 'bw' ),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'left'    => [ 'title' => __( 'Left', 'bw' ), 'icon' => 'eicon-text-align-left' ],
                'center'  => [ 'title' => __( 'Center', 'bw' ), 'icon' => 'eicon-text-align-center' ],
                'right'   => [ 'title' => __( 'Right', 'bw' ), 'icon' => 'eicon-text-align-right' ],
                'justify' => [ 'title' => __( 'Justified', 'bw' ), 'icon' => 'eicon-text-align-justify' ],
            ],
            'default'   => 'left',
            'selectors' => [
                '{{WRAPPER}} .bw-product-description' => 'text-align: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'color', [
            'label'     => __( 'Text Color', 'bw' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-product-description' => 'color: {{VALUE}};',
                '{{WRAPPER}} .bw-product-description p' => 'color: {{VALUE}};',
                '{{WRAPPER}} .bw-product-description li' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'           => 'typography',
            'selector'       => '{{WRAPPER}} .bw-product-description, {{WRAPPER}} .bw-product-description p, {{WRAPPER}} .bw-product-description li',
            'fields_options' => [
                'typography' => [ 'default' => 'yes' ],
            ],
        ] );

        $this->end_controls_section();
    }

    protected function render() {
        $settings   = $this->get_settings_for_display();
        $is_editor  = class_exists( '\Elementor\Plugin' )
            && \Elementor\Plugin::$instance->editor
            && \Elementor\Plugin::$instance->editor->is_edit_mode();
        $product_id = $this->resolve_product_id( $settings );

        if ( ! $product_id || ! function_exists( 'wc_get_product' ) ) {
            if ( $is_editor ) {
                $this->render_editor_placeholder();
            }
            return;
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            if ( $is_editor ) {
                $this->render_editor_placeholder();
            }
            return;
        }

        $description = trim( (string) $product->get_description() );
        if ( '' === $description ) {
            if ( $is_editor ) {
                $this->render_editor_placeholder();
            }
            return;
        }

        $this->add_render_attribute( 'wrapper', 'class', 'bw-product-description' );

        echo '<div ' . $this->get_render_attribute_string( 'wrapper' ) . '>';
        echo wp_kses_post( apply_filters( 'the_content', $description ) );
        echo '</div>';
    }

    private function resolve_product_id( array $settings ): int {
        $product_id = ! empty( $settings['product_id'] ) ? absint( $settings['product_id'] ) : 0;

        if ( ! $product_id && function_exists( 'bw_tbl_resolve_product_context_id' ) ) {
            $resolution = bw_tbl_resolve_product_context_id( array_merge( $settings, [ '__widget_class' => __CLASS__ ] ) );
            $product_id = isset( $resolution['id'] ) ? absint( $resolution['id'] ) : 0;
        }

        if ( ! $product_id ) {
            $post_id = absint( get_the_ID() );
            if ( 'product' === get_post_type( $post_id ) ) {
                $product_id = $post_id;
            }
        }

        return $product_id;
    }

    private function render_editor_placeholder() {
        $this->add_render_attribute( 'wrapper', 'class', 'bw-product-description' );

        echo '<div ' . $this->get_render_attribute_string( 'wrapper' ) . '>';
        echo wp_kses_post( wpautop( __( 'Product description', 'bw' ) ) );
        echo '</div>';
    }

    protected function content_template() {
        ?>
        <#
        view.addRenderAttribute( 'wrapper', 'class', 'bw-product-description' );
        print( '<div ' + view.getRenderAttributeString( 'wrapper' ) + '><p>Product description</p></div>' );
        #>
        <?php
    }
}
