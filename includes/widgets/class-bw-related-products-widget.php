<?php
use Elementor\Controls_Manager;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Related_Products_Widget extends Widget_Base {

    public function get_name() {
        return 'bw-related-products';
    }

    public function get_title() {
        return esc_html__( 'BW Related Products', 'bw-elementor-widgets' );
    }

    public function get_icon() {
        return 'eicon-product-related';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_style_depends() {
        return [ 'bw-related-products-style' ];
    }

    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'section_content',
            [
                'label' => __( 'Content', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'query_by',
            [
                'label'   => __( 'Query by', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'category',
                'options' => [
                    'category'    => __( 'Category', 'bw-elementor-widgets' ),
                    'subcategory' => __( 'Subcategory', 'bw-elementor-widgets' ),
                    'tag'         => __( 'Tag', 'bw-elementor-widgets' ),
                ],
            ]
        );

        $this->end_controls_section();

        // Spacing Section
        $this->start_controls_section(
            'section_spacing',
            [
                'label' => __( 'Spacing', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_responsive_control(
            'margin_top',
            [
                'label'      => __( 'Margin Top', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em', 'rem' ],
                'range'      => [
                    'px'  => [ 'min' => 0, 'max' => 300 ],
                    'em'  => [ 'min' => 0, 'max' => 20 ],
                    'rem' => [ 'min' => 0, 'max' => 20 ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-related-products-widget' => 'margin-top: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'margin_bottom',
            [
                'label'      => __( 'Margin Bottom', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em', 'rem' ],
                'range'      => [
                    'px'  => [ 'min' => 0, 'max' => 300 ],
                    'em'  => [ 'min' => 0, 'max' => 20 ],
                    'rem' => [ 'min' => 0, 'max' => 20 ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-related-products-widget' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Get related products based on query type
     *
     * @param WC_Product $product Current product
     * @param string     $query_by Query type: category, subcategory, or tag
     * @param array      $args Query arguments
     * @return array Array of product IDs
     */
    protected function get_related_products_by_type( $product, $query_by, $args ) {
        $product_id       = $product->get_id();
        $posts_per_page   = isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : 4;
        $related_ids      = [];

        switch ( $query_by ) {
            case 'subcategory':
                $related_ids = $this->get_related_by_subcategory( $product_id );
                break;

            case 'tag':
                $related_ids = $this->get_related_by_tag( $product_id );
                break;

            case 'category':
            default:
                $related_ids = wc_get_related_products(
                    $product_id,
                    $posts_per_page,
                    $product->get_upsell_ids()
                );
                break;
        }

        // Limit results
        if ( count( $related_ids ) > $posts_per_page ) {
            $related_ids = array_slice( $related_ids, 0, $posts_per_page );
        }

        return $related_ids;
    }

    /**
     * Get related products by subcategory
     *
     * @param int $product_id Current product ID
     * @return array Array of product IDs
     */
    protected function get_related_by_subcategory( $product_id ) {
        $product_categories = wp_get_post_terms( $product_id, 'product_cat', [ 'fields' => 'ids' ] );

        if ( empty( $product_categories ) || is_wp_error( $product_categories ) ) {
            return [];
        }

        // Find subcategories (categories with parent)
        $subcategories = [];
        foreach ( $product_categories as $cat_id ) {
            $term = get_term( $cat_id, 'product_cat' );
            if ( $term && ! is_wp_error( $term ) && $term->parent > 0 ) {
                $subcategories[] = $cat_id;
            }
        }

        if ( empty( $subcategories ) ) {
            return [];
        }

        // Query products with the same subcategory
        $query_args = [
            'post_type'      => 'product',
            'posts_per_page' => 50,
            'post__not_in'   => [ $product_id ],
            'orderby'        => 'rand',
            'fields'         => 'ids',
            'tax_query'      => [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $subcategories,
                ],
            ],
        ];

        $query = new \WP_Query( $query_args );
        return $query->posts;
    }

    /**
     * Get related products by tag
     *
     * @param int $product_id Current product ID
     * @return array Array of product IDs
     */
    protected function get_related_by_tag( $product_id ) {
        $product_tags = wp_get_post_terms( $product_id, 'product_tag', [ 'fields' => 'ids' ] );

        if ( empty( $product_tags ) || is_wp_error( $product_tags ) ) {
            return [];
        }

        // Query products with the same tags
        $query_args = [
            'post_type'      => 'product',
            'posts_per_page' => 50,
            'post__not_in'   => [ $product_id ],
            'orderby'        => 'rand',
            'fields'         => 'ids',
            'tax_query'      => [
                [
                    'taxonomy' => 'product_tag',
                    'field'    => 'term_id',
                    'terms'    => $product_tags,
                    'operator' => 'IN',
                ],
            ],
        ];

        $query = new \WP_Query( $query_args );
        return $query->posts;
    }

    /**
     * Try to resolve the current product context.
     *
     * The widget normally relies on the global $product set on single product
     * pages, but Elementor's editor preview does not always flag the request
     * as a product view. In that case we fall back to the queried object ID or
     * the currently edited post ID so the widget can still render something in
     * the editor.
     *
     * @return WC_Product|null
     */
    protected function get_current_product() {
        if ( ! function_exists( 'wc_get_product' ) ) {
            return null;
        }

        global $product;

        if ( $product instanceof WC_Product ) {
            return $product;
        }

        $queried_id = get_queried_object_id();
        if ( $queried_id ) {
            $maybe_product = wc_get_product( $queried_id );
            if ( $maybe_product instanceof WC_Product ) {
                return $maybe_product;
            }
        }

        if ( class_exists( '\\Elementor\\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            $editor_product = wc_get_product( get_the_ID() );
            if ( $editor_product instanceof WC_Product ) {
                return $editor_product;
            }
        }

        return null;
    }

    protected function render() {
        if ( ! function_exists( 'wc_get_related_products' ) ) {
            return;
        }

        $product = $this->get_current_product();

        if ( ! $product instanceof WC_Product ) {
            if ( class_exists( '\\Elementor\\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                echo '<div class="bw-related-products-widget bw-related-products-empty">';
                echo '<p>' . esc_html__( 'Seleziona un prodotto in anteprima per mostrare i correlati.', 'bw-elementor-widgets' ) . '</p>';
                echo '</div>';
            }
            return;
        }

        $settings = $this->get_settings_for_display();
        $query_by = isset( $settings['query_by'] ) ? $settings['query_by'] : 'category';

        $args = apply_filters(
            'woocommerce_output_related_products_args',
            [
                'posts_per_page' => 4,
                'columns'        => 4,
                'orderby'        => 'rand',
                'order'          => 'desc',
            ]
        );

        $related_product_ids = $this->get_related_products_by_type( $product, $query_by, $args );

        if ( empty( $related_product_ids ) ) {
            echo '<div class="bw-related-products-widget bw-related-products-empty">';
            echo '<p>' . esc_html__( 'Non ci sono post correlati.', 'bw-elementor-widgets' ) . '</p>';
            echo '</div>';
            return;
        }

        $heading = apply_filters( 'bw_mew_related_products_widget_heading', __( 'You might also like', 'bw-elementor-widgets' ) );
        $columns = absint( apply_filters( 'bw_mew_related_products_columns', isset( $args['columns'] ) ? $args['columns'] : 3 ) );
        $gap     = absint( apply_filters( 'bw_mew_related_products_gap', 24 ) );
        $image_height = absint( apply_filters( 'bw_mew_related_products_image_height', 625 ) );

        echo '<div class="bw-related-products-widget">';
        echo '<section class="related products bw-related-products" style="--bw-wallpost-columns: ' . esc_attr( $columns ) . '; --bw-wallpost-gap: ' . esc_attr( $gap ) . 'px; --bw-wallpost-image-height: ' . esc_attr( $image_height ) . 'px;">';

        if ( $heading ) {
            echo '<h2 class="bw-related-products__title">' . esc_html( $heading ) . '</h2>';
        }

        echo '<ul class="bw-related-products__titles">';

        foreach ( $related_product_ids as $related_product_id ) {
            $related_product = wc_get_product( $related_product_id );

            if ( ! $related_product || ! $related_product->is_visible() ) {
                continue;
            }

            $permalink = $related_product->get_permalink();
            $title     = $related_product->get_name();

            echo '<li class="bw-related-products__item">';
            echo '<h3 class="bw-related-products__name"><a href="' . esc_url( $permalink ) . '">' . esc_html( $title ) . '</a></h3>';
            echo '</li>';
        }

        echo '</ul>';
        echo '</section>';
        echo '</div>';
    }
}
