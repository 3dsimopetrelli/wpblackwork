<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BW Title Product Widget
 *
 * Displays either a WooCommerce product title or a product-category name.
 * Typography is handled exclusively through the Elementor Typography group control.
 */
class Widget_Bw_Title_Product extends Widget_Base {

    public function get_name() {
        return 'bw-title-product';
    }

    public function get_title() {
        return __( 'BW Title Product', 'bw' );
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
            'label'       => __( 'Title Source', 'bw' ),
            'type'        => Controls_Manager::SELECT,
            'options'     => [
                'product'  => __( 'Single Product', 'bw' ),
                'category' => __( 'Product Category', 'bw' ),
                'page'     => __( 'Page', 'bw' ),
                'text'     => __( 'Text', 'bw' ),
            ],
            'default'     => 'product',
            'description' => __( 'Choose what this widget reads the title from.', 'bw' ),
        ] );

        $this->add_control( 'product_id', [
            'label'       => __( 'Product ID', 'bw' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => __( 'Leave empty to use current product', 'bw' ),
            'description' => __( 'ID of the product to preview in editor. Leave empty on single-product templates.', 'bw' ),
            'label_block' => true,
            'condition'   => [ 'title_source' => 'product' ],
        ] );

        $this->add_control( 'custom_text', [
            'label'       => __( 'Text', 'bw' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => __( 'Enter custom title text', 'bw' ),
            'label_block' => true,
            'condition'   => [ 'title_source' => 'text' ],
        ] );

        $this->add_control( 'page_id', [
            'label'       => __( 'Page ID', 'bw' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => __( 'Leave empty to use current page', 'bw' ),
            'description' => __( 'ID of the page to preview in editor. Leave empty on page templates.', 'bw' ),
            'label_block' => true,
            'condition'   => [ 'title_source' => 'page' ],
        ] );

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

        /* ── Style ───────────────────────────────────────────────────────── */

        $this->start_controls_section( 'section_style', [
            'label' => __( 'Style', 'bw' ),
            'tab'   => Controls_Manager::TAB_STYLE,
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

        $this->add_control( 'color', [
            'label'     => __( 'Text Color', 'bw' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-title-product' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'typography',
            'selector' => '{{WRAPPER}} .bw-title-product',
            'fields_options' => [
                'typography'     => [ 'default' => 'yes' ],
                'font_weight'    => [ 'default' => '700' ],
                'font_size'      => [
                    'default'        => [ 'size' => 100, 'unit' => 'px' ],
                    'tablet_default' => [ 'size' => 100, 'unit' => 'px' ],
                    'mobile_default' => [ 'size' => 100, 'unit' => 'px' ],
                ],
                'letter_spacing' => [
                    'default'        => [ 'size' => -3,   'unit' => 'px' ],
                    'tablet_default' => [ 'size' => -2,   'unit' => 'px' ],
                    'mobile_default' => [ 'size' => -1,   'unit' => 'px' ],
                ],
                'line_height'    => [
                    'default'        => [ 'size' => 110, 'unit' => '%' ],
                    'tablet_default' => [ 'size' => 110, 'unit' => '%' ],
                    'mobile_default' => [ 'size' => 110, 'unit' => '%' ],
                ],
            ],
        ] );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $allowed_tags = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p' ];
        $tag = in_array( $settings['html_tag'], $allowed_tags, true ) ? $settings['html_tag'] : 'h1';

        $is_editor = class_exists( '\Elementor\Plugin' )
            && \Elementor\Plugin::$instance->editor
            && \Elementor\Plugin::$instance->editor->is_edit_mode();

        $source = isset( $settings['title_source'] ) ? $settings['title_source'] : 'product';

        if ( 'text' === $source ) {
            $title = sanitize_text_field( $settings['custom_text'] ?? '' );
        } elseif ( 'page' === $source ) {
            $title = $this->resolve_page_title( $settings );
        } elseif ( 'category' === $source ) {
            $title = $this->resolve_category_title( $settings );
        } else {
            $title = $this->resolve_product_title( $settings );
        }

        if ( ! $title ) {
            if ( $is_editor ) {
                $placeholders = [
                    'text'     => __( 'Custom Text', 'bw' ),
                    'category' => __( 'Category Name', 'bw' ),
                    'page'     => __( 'Page Title', 'bw' ),
                    'product'  => __( 'Product Title', 'bw' ),
                ];
                $title = $placeholders[ $source ] ?? __( 'Product Title', 'bw' );
            } else {
                return;
            }
        }

        $this->add_render_attribute( 'title', 'class', 'bw-title-product' );
        $this->add_render_attribute( 'title', 'style', 'margin:0;padding:0;' );

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
    private function resolve_product_title( array $settings ): string {
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
    private function resolve_category_title( array $settings ): string {
        if ( ! empty( $settings['term_id'] ) ) {
            $term = get_term( absint( $settings['term_id'] ), 'product_cat' );
            if ( $term && ! is_wp_error( $term ) ) {
                return $term->name;
            }
        }

        $queried = get_queried_object();
        if ( $queried instanceof \WP_Term && 'product_cat' === $queried->taxonomy ) {
            return $queried->name;
        }

        return '';
    }

    /**
     * Resolve page title.
     * Priority: widget setting (page_id) -> queried object -> current post if page.
     */
    private function resolve_page_title( array $settings ): string {
        if ( ! empty( $settings['page_id'] ) ) {
            $page_id = absint( $settings['page_id'] );
            if ( $page_id > 0 && 'page' === get_post_type( $page_id ) ) {
                return get_the_title( $page_id );
            }
        }

        $queried_id = absint( get_queried_object_id() );
        if ( $queried_id > 0 && 'page' === get_post_type( $queried_id ) ) {
            return get_the_title( $queried_id );
        }

        $post_id = absint( get_the_ID() );
        if ( $post_id > 0 && 'page' === get_post_type( $post_id ) ) {
            return get_the_title( $post_id );
        }

        return '';
    }

    /**
     * JS template for live preview in the editor.
     */
    protected function content_template() {
        ?>
        <#
        var allowedTags = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p' ];
        var tag = ( allowedTags.indexOf( settings.html_tag ) !== -1 ) ? settings.html_tag : 'h1';
        var label;
        if ( settings.title_source === 'text' ) {
            label = settings.custom_text || 'Custom Text';
        } else if ( settings.title_source === 'category' ) {
            label = 'Category Name';
        } else {
            label = 'Product Title';
        }
        view.addRenderAttribute( 'title', 'class', 'bw-title-product' );
        view.addRenderAttribute( 'title', 'style', 'margin:0;padding:0;' );
        print( '<' + tag + ' ' + view.getRenderAttributeString( 'title' ) + '>' + label + '</' + tag + '>' );
        #>
        <?php
    }
}
