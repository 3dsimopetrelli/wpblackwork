<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BW-SP Title Product Widget
 *
 * Displays the WooCommerce product title with configurable HTML tag,
 * typography and alignment. Designed for single-product templates.
 */
class Widget_Bw_Title_Product extends Widget_Base {

    public function get_name() {
        return 'bw-title-product';
    }

    public function get_title() {
        return __( 'Title Product', 'bw' );
    }

    public function get_icon() {
        return 'eicon-product-title';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    protected function register_controls() {

        /* ── Content ─────────────────────────────────────────────────────── */

        $this->start_controls_section( 'section_content', [
            'label' => __( 'Content', 'bw' ),
        ] );

        $this->add_control( 'html_tag', [
            'label'   => __( 'HTML Tag', 'bw' ),
            'type'    => Controls_Manager::SELECT,
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
            'default' => 'h1',
        ] );

        $this->end_controls_section();

        /* ── Style: Typography ───────────────────────────────────────────── */

        $this->start_controls_section( 'section_style_typography', [
            'label' => __( 'Typography', 'bw' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'color', [
            'label'     => __( 'Color', 'bw' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-title-product' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'typography',
            'selector' => '{{WRAPPER}} .bw-title-product',
        ] );

        $this->add_responsive_control( 'font_size', [
            'label'      => __( 'Font Size', 'bw' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', 'em', 'rem', 'vw' ],
            'range'      => [
                'px'  => [ 'min' => 10, 'max' => 120, 'step' => 1 ],
                'em'  => [ 'min' => 0.5, 'max' => 8,  'step' => 0.1 ],
                'rem' => [ 'min' => 0.5, 'max' => 8,  'step' => 0.1 ],
                'vw'  => [ 'min' => 1,   'max' => 15, 'step' => 0.1 ],
            ],
            'default'        => [ 'size' => 48, 'unit' => 'px' ],
            'tablet_default' => [ 'size' => 36, 'unit' => 'px' ],
            'mobile_default' => [ 'size' => 26, 'unit' => 'px' ],
            'selectors' => [
                '{{WRAPPER}} .bw-title-product' => 'font-size: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'line_height', [
            'label'      => __( 'Line Height', 'bw' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'em', 'px' ],
            'range'      => [
                'em' => [ 'min' => 0.8, 'max' => 3, 'step' => 0.05 ],
                'px' => [ 'min' => 10,  'max' => 200 ],
            ],
            'default'        => [ 'size' => 1.1, 'unit' => 'em' ],
            'tablet_default' => [ 'size' => 1.1, 'unit' => 'em' ],
            'mobile_default' => [ 'size' => 1.2, 'unit' => 'em' ],
            'selectors' => [
                '{{WRAPPER}} .bw-title-product' => 'line-height: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'text_align', [
            'label'   => __( 'Alignment', 'bw' ),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'left'   => [ 'title' => __( 'Left', 'bw' ),   'icon' => 'eicon-text-align-left' ],
                'center' => [ 'title' => __( 'Center', 'bw' ), 'icon' => 'eicon-text-align-center' ],
                'right'  => [ 'title' => __( 'Right', 'bw' ),  'icon' => 'eicon-text-align-right' ],
            ],
            'default'        => 'left',
            'tablet_default' => 'left',
            'mobile_default' => 'left',
            'selectors' => [
                '{{WRAPPER}} .bw-title-product' => 'text-align: {{VALUE}};',
            ],
        ] );

        $this->end_controls_section();

        /* ── Style: Spacing ──────────────────────────────────────────────── */

        $this->start_controls_section( 'section_style_spacing', [
            'label' => __( 'Spacing', 'bw' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_responsive_control( 'margin', [
            'label'      => __( 'Margin', 'bw' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'em', '%' ],
            'default' => [
                'top'      => '0',
                'right'    => '0',
                'bottom'   => '12',
                'left'     => '0',
                'unit'     => 'px',
                'isLinked' => false,
            ],
            'tablet_default' => [
                'top'      => '0',
                'right'    => '0',
                'bottom'   => '10',
                'left'     => '0',
                'unit'     => 'px',
                'isLinked' => false,
            ],
            'mobile_default' => [
                'top'      => '0',
                'right'    => '0',
                'bottom'   => '8',
                'left'     => '0',
                'unit'     => 'px',
                'isLinked' => false,
            ],
            'selectors' => [
                '{{WRAPPER}} .bw-title-product' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'padding', [
            'label'      => __( 'Padding', 'bw' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'em', '%' ],
            'default' => [
                'top'      => '0',
                'right'    => '0',
                'bottom'   => '0',
                'left'     => '0',
                'unit'     => 'px',
                'isLinked' => true,
            ],
            'tablet_default' => [
                'top'      => '0',
                'right'    => '0',
                'bottom'   => '0',
                'left'     => '0',
                'unit'     => 'px',
                'isLinked' => true,
            ],
            'mobile_default' => [
                'top'      => '0',
                'right'    => '0',
                'bottom'   => '0',
                'left'     => '0',
                'unit'     => 'px',
                'isLinked' => true,
            ],
            'selectors' => [
                '{{WRAPPER}} .bw-title-product' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        // Sanitize tag.
        $allowed_tags = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p' ];
        $tag = in_array( $settings['html_tag'], $allowed_tags, true ) ? $settings['html_tag'] : 'h1';

        // Resolve product ID: context helper → post ID.
        $product_id = 0;
        if ( function_exists( 'bw_tbl_resolve_product_context_id' ) ) {
            $resolution = bw_tbl_resolve_product_context_id( array_merge( $settings, [ '__widget_class' => __CLASS__ ] ) );
            $product_id = isset( $resolution['id'] ) ? absint( $resolution['id'] ) : 0;
        }
        if ( ! $product_id ) {
            $product_id = absint( get_the_ID() );
        }

        // Get title.
        $title = '';
        if ( $product_id && function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $product_id );
            if ( $product ) {
                $title = $product->get_name();
            }
        }
        if ( ! $title && $product_id ) {
            $title = get_the_title( $product_id );
        }

        // Editor placeholder.
        if ( ! $title ) {
            $is_editor = class_exists( '\Elementor\Plugin' )
                && \Elementor\Plugin::$instance->editor
                && \Elementor\Plugin::$instance->editor->is_edit_mode();

            if ( $is_editor ) {
                $title = __( 'Product Title', 'bw' );
            } else {
                return;
            }
        }

        $this->add_render_attribute( 'title', 'class', 'bw-title-product' );

        printf(
            '<%1$s %2$s>%3$s</%1$s>',
            esc_attr( $tag ),
            $this->get_render_attribute_string( 'title' ),
            esc_html( $title )
        );
    }

    /**
     * JS template for live preview in the editor.
     */
    protected function content_template() {
        ?>
        <#
        var allowedTags = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p' ];
        var tag = ( allowedTags.indexOf( settings.html_tag ) !== -1 ) ? settings.html_tag : 'h1';
        #>
        <{{{ tag }}} class="bw-title-product">
            Product Title
        </{{{ tag }}}>
        <?php
    }
}
