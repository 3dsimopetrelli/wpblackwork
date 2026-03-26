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

        $this->start_controls_section( 'section_style_responsive_title', [
            'label' => __( 'Responsive Title', 'bw' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_responsive_control( 'max_text_width', [
            'label'      => __( 'Max Text Width', 'bw' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'ch', 'rem', '%', 'vw', 'px' ],
            'range'      => [
                'ch'  => [ 'min' => 4, 'max' => 40, 'step' => 0.5 ],
                'rem' => [ 'min' => 4, 'max' => 60, 'step' => 0.25 ],
                '%'   => [ 'min' => 20, 'max' => 100, 'step' => 1 ],
                'vw'  => [ 'min' => 20, 'max' => 100, 'step' => 1 ],
                'px'  => [ 'min' => 160, 'max' => 2200, 'step' => 10 ],
            ],
            'default'    => [
                'size' => 100,
                'unit' => '%',
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-title-product' => '--bw-title-product-max-width: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'font_size_mode', [
            'label'   => __( 'Font Size Mode', 'bw' ),
            'type'    => Controls_Manager::SELECT,
            'default' => 'fluid',
            'options' => [
                'fluid' => __( 'Fluid', 'bw' ),
                'fixed' => __( 'Fixed', 'bw' ),
            ],
        ] );

        $this->add_responsive_control( 'fixed_font_size', [
            'label'      => __( 'Fixed Font Size', 'bw' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => 12, 'max' => 220, 'step' => 1 ],
            ],
            'default'    => [
                'size' => 100,
                'unit' => 'px',
            ],
            'tablet_default' => [
                'size' => 100,
                'unit' => 'px',
            ],
            'mobile_default' => [
                'size' => 100,
                'unit' => 'px',
            ],
            'condition'  => [
                'font_size_mode' => 'fixed',
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-title-product' => 'font-size: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'fluid_font_size_min', [
            'label'      => __( 'Fluid Min Font Size', 'bw' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => 12, 'max' => 180, 'step' => 1 ],
            ],
            'default'    => [
                'size' => 36,
                'unit' => 'px',
            ],
            'condition'  => [
                'font_size_mode' => 'fluid',
            ],
        ] );

        $this->add_control( 'fluid_font_size_max', [
            'label'      => __( 'Fluid Max Font Size', 'bw' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => 12, 'max' => 260, 'step' => 1 ],
            ],
            'default'    => [
                'size' => 100,
                'unit' => 'px',
            ],
            'condition'  => [
                'font_size_mode' => 'fluid',
            ],
        ] );

        $this->add_control( 'fluid_viewport_min', [
            'label'     => __( 'Fluid Min Viewport', 'bw' ),
            'type'      => Controls_Manager::NUMBER,
            'default'   => 480,
            'min'       => 240,
            'max'       => 4000,
            'step'      => 1,
            'condition' => [
                'font_size_mode' => 'fluid',
            ],
        ] );

        $this->add_control( 'fluid_viewport_max', [
            'label'     => __( 'Fluid Max Viewport', 'bw' ),
            'type'      => Controls_Manager::NUMBER,
            'default'   => 1600,
            'min'       => 320,
            'max'       => 5000,
            'step'      => 1,
            'condition' => [
                'font_size_mode' => 'fluid',
            ],
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
                'left'   => [ 'title' => __( 'Left', 'bw' ),   'icon' => 'eicon-text-align-left' ],
                'center' => [ 'title' => __( 'Center', 'bw' ), 'icon' => 'eicon-text-align-center' ],
                'right'  => [ 'title' => __( 'Right', 'bw' ),  'icon' => 'eicon-text-align-right' ],
            ],
            'default'        => 'left',
            'tablet_default' => 'left',
            'mobile_default' => 'left',
            'selectors' => [
                '{{WRAPPER}} .bw-title-product' => '{{VALUE}};',
            ],
            'selectors_dictionary' => [
                'left'   => 'text-align: left; margin-left: 0; margin-right: auto;',
                'center' => 'text-align: center; margin-left: auto; margin-right: auto;',
                'right'  => 'text-align: right; margin-left: auto; margin-right: 0;',
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
            'exclude'  => [ 'font_size' ],
            'fields_options' => [
                'typography'     => [ 'default' => 'yes' ],
                'font_weight'    => [ 'default' => '500' ],
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
        $this->add_render_attribute( 'title', 'style', 'margin:0;padding:0;display:block;inline-size:100%;max-inline-size:min(var(--bw-title-product-max-width, 100%), 100%);' );

        $fluid_expression = $this->build_fluid_font_size_expression( $settings );
        if ( '' !== $fluid_expression ) {
            $this->add_render_attribute( 'title', 'style', '--bw-title-product-fluid-size:' . $fluid_expression . ';font-size:var(--bw-title-product-fluid-size);' );
        }

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
        var fontSizeMode = settings.font_size_mode || 'fluid';
        var minSize = settings.fluid_font_size_min && settings.fluid_font_size_min.size ? parseFloat( settings.fluid_font_size_min.size ) : 36;
        var maxSize = settings.fluid_font_size_max && settings.fluid_font_size_max.size ? parseFloat( settings.fluid_font_size_max.size ) : 100;
        var minViewport = settings.fluid_viewport_min ? parseFloat( settings.fluid_viewport_min ) : 480;
        var maxViewport = settings.fluid_viewport_max ? parseFloat( settings.fluid_viewport_max ) : 1600;
        var titleStyle = 'margin:0;padding:0;display:block;inline-size:100%;max-inline-size:min(var(--bw-title-product-max-width, 100%), 100%);';

        if ( fontSizeMode === 'fluid' ) {
            minSize = Math.max( 1, minSize );
            maxSize = Math.max( minSize, maxSize );
            minViewport = Math.max( 1, minViewport );
            maxViewport = Math.max( minViewport + 1, maxViewport );

            var slope = ( maxSize - minSize ) / ( maxViewport - minViewport );
            var preferred = minSize - ( slope * minViewport );
            var slopeVw = slope * 100;
            titleStyle += '--bw-title-product-fluid-size:clamp('
                + minSize + 'px, calc(' + preferred + 'px + ' + slopeVw + 'vw), ' + maxSize + 'px);font-size:var(--bw-title-product-fluid-size);';
        }

        view.addRenderAttribute( 'title', 'style', titleStyle );
        print( '<' + tag + ' ' + view.getRenderAttributeString( 'title' ) + '>' + label + '</' + tag + '>' );
        #>
        <?php
    }

    private function build_fluid_font_size_expression( array $settings ): string {
        $font_size_mode = isset( $settings['font_size_mode'] ) ? sanitize_key( $settings['font_size_mode'] ) : 'fluid';
        if ( 'fluid' !== $font_size_mode ) {
            return '';
        }

        $min_size = isset( $settings['fluid_font_size_min']['size'] ) ? (float) $settings['fluid_font_size_min']['size'] : 36;
        $max_size = isset( $settings['fluid_font_size_max']['size'] ) ? (float) $settings['fluid_font_size_max']['size'] : 100;
        $min_vw   = isset( $settings['fluid_viewport_min'] ) ? (float) $settings['fluid_viewport_min'] : 480;
        $max_vw   = isset( $settings['fluid_viewport_max'] ) ? (float) $settings['fluid_viewport_max'] : 1600;

        $min_size = max( 1, $min_size );
        $max_size = max( $min_size, $max_size );
        $min_vw   = max( 1, $min_vw );
        $max_vw   = max( $min_vw + 1, $max_vw );

        $slope     = ( $max_size - $min_size ) / ( $max_vw - $min_vw );
        $preferred = round( $min_size - ( $slope * $min_vw ), 4 );
        $slope_vw  = round( $slope * 100, 4 );
        $min_size  = round( $min_size, 4 );
        $max_size  = round( $max_size, 4 );

        return sprintf(
            'clamp(%1$spx, calc(%2$spx + %3$svw), %4$spx)',
            $this->format_number( $min_size ),
            $this->format_number( $preferred ),
            $this->format_number( $slope_vw ),
            $this->format_number( $max_size )
        );
    }

    private function format_number( float $value ): string {
        $formatted = rtrim( rtrim( number_format( $value, 4, '.', '' ), '0' ), '.' );

        return '' !== $formatted ? $formatted : '0';
    }
}
