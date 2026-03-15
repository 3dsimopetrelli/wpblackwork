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
 * Displays either a WooCommerce product title or a product-category name.
 * The source is configurable via the Title Source control.
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

        $this->add_control( 'title_source', [
            'label'   => __( 'Title Source', 'bw' ),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'product'  => __( 'Single Product', 'bw' ),
                'category' => __( 'Product Category', 'bw' ),
            ],
            'default'     => 'product',
            'description' => __( 'Choose what this widget reads the title from.', 'bw' ),
        ] );

        // ── Product source fields ─────────────────────────────────────────

        $this->add_control( 'product_id', [
            'label'       => __( 'Product ID', 'bw' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => __( 'Leave empty to use current product', 'bw' ),
            'description' => __( 'ID of the product to preview in editor. Leave empty on single-product templates.', 'bw' ),
            'label_block' => true,
            'condition'   => [ 'title_source' => 'product' ],
        ] );

        // ── Category source fields ────────────────────────────────────────

        $this->add_control( 'term_id', [
            'label'       => __( 'Category ID', 'bw' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => __( 'Leave empty to use current category', 'bw' ),
            'description' => __( 'ID of the product category to preview in editor. Leave empty on category archive templates.', 'bw' ),
            'label_block' => true,
            'condition'   => [ 'title_source' => 'category' ],
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

        $is_editor = class_exists( '\Elementor\Plugin' )
            && \Elementor\Plugin::$instance->editor
            && \Elementor\Plugin::$instance->editor->is_edit_mode();

        $source = isset( $settings['title_source'] ) ? $settings['title_source'] : 'product';
        $title  = '';

        if ( 'category' === $source ) {
            $title = $this->resolve_category_title( $settings, $is_editor );
        } else {
            $title = $this->resolve_product_title( $settings, $is_editor );
        }

        if ( ! $title ) {
            if ( $is_editor ) {
                $title = ( 'category' === $source )
                    ? __( 'Category Name', 'bw' )
                    : __( 'Product Title', 'bw' );
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
     * Resolve product title.
     * Priority: widget setting → context helper → current post (if product).
     */
    private function resolve_product_title( array $settings, bool $is_editor ): string {
        // 1. Explicit product_id in settings.
        $product_id = ! empty( $settings['product_id'] ) ? absint( $settings['product_id'] ) : 0;

        // 2. Context helper (Theme Builder single-product template preview).
        if ( ! $product_id && function_exists( 'bw_tbl_resolve_product_context_id' ) ) {
            $resolution = bw_tbl_resolve_product_context_id( array_merge( $settings, [ '__widget_class' => __CLASS__ ] ) );
            $product_id = isset( $resolution['id'] ) ? absint( $resolution['id'] ) : 0;
        }

        // 3. Current post, only when it is a product.
        if ( ! $product_id ) {
            $post_id = absint( get_the_ID() );
            if ( 'product' === get_post_type( $post_id ) ) {
                $product_id = $post_id;
            }
        }

        if ( ! $product_id || ! function_exists( 'wc_get_product' ) ) {
            return '';
        }

        $product = wc_get_product( $product_id );
        return $product ? $product->get_name() : '';
    }

    /**
     * Resolve category title.
     * Priority: widget setting (term_id) → queried object on category archive.
     */
    private function resolve_category_title( array $settings, bool $is_editor ): string {
        // 1. Explicit term_id in settings (for editor preview).
        if ( ! empty( $settings['term_id'] ) ) {
            $term = get_term( absint( $settings['term_id'] ), 'product_cat' );
            if ( $term && ! is_wp_error( $term ) ) {
                return $term->name;
            }
        }

        // 2. Current queried object on product category archive.
        $queried = get_queried_object();
        if ( $queried instanceof \WP_Term && 'product_cat' === $queried->taxonomy ) {
            return $queried->name;
        }

        return '';
    }

    /**
     * JS template for live preview in the editor.
     * Server re-render via AJAX provides the real title when settings change.
     */
    protected function content_template() {
        ?>
        <#
        var allowedTags = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p' ];
        var tag = ( allowedTags.indexOf( settings.html_tag ) !== -1 ) ? settings.html_tag : 'h1';
        var placeholder = ( settings.title_source === 'category' ) ? 'Category Name' : 'Product Title';
        view.addRenderAttribute( 'title', 'class', 'bw-title-product' );
        print( '<' + tag + ' ' + view.getRenderAttributeString( 'title' ) + '>' + placeholder + '</' + tag + '>' );
        #>
        <?php
    }
}
