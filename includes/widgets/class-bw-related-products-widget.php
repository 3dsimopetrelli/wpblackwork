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

    protected function render() {
        if ( ! function_exists( 'wc_get_related_products' ) || ! is_product() ) {
            return;
        }

        global $product;

        if ( empty( $product ) || ! $product instanceof WC_Product ) {
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
        echo '<section class="related products bw-related-products bw-wallpost" style="--bw-wallpost-columns: ' . esc_attr( $columns ) . '; --bw-wallpost-gap: ' . esc_attr( $gap ) . 'px; --bw-wallpost-image-height: ' . esc_attr( $image_height ) . 'px;">';

        if ( $heading ) {
            echo '<h2 class="bw-related-products__title">' . esc_html( $heading ) . '</h2>';
        }

        echo '<div class="bw-related-products-grid bw-wallpost-grid">';

        foreach ( $related_product_ids as $related_product_id ) {
            $related_product = wc_get_product( $related_product_id );

            if ( ! $related_product || ! $related_product->is_visible() ) {
                continue;
            }

            $permalink     = $related_product->get_permalink();
            $title         = $related_product->get_name();
            $short_desc    = $related_product->get_short_description();
            $excerpt       = $short_desc ? wp_trim_words( wp_strip_all_tags( $short_desc ), 30 ) : '';
            $thumbnail_id  = $related_product->get_image_id();
            $thumbnail_html = $thumbnail_id ? wp_get_attachment_image( $thumbnail_id, 'woocommerce_thumbnail', false, [ 'class' => 'bw-slider-main', 'loading' => 'lazy' ] ) : '';

            $gallery_ids      = $related_product->get_gallery_image_ids();
            $hover_image_id   = $gallery_ids ? reset( $gallery_ids ) : 0;
            $hover_image_html = $hover_image_id ? wp_get_attachment_image( $hover_image_id, 'woocommerce_thumbnail', false, [ 'class' => 'bw-slider-hover', 'loading' => 'lazy' ] ) : '';

            $price_html      = $related_product->get_price_html();
            $add_to_cart_url = '';
            $has_add_to_cart = true;

            if ( $related_product->is_type( 'variable' ) ) {
                $add_to_cart_url = $permalink;
            } else {
                $cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '';

                if ( $cart_url && $related_product->is_purchasable() && $related_product->is_in_stock() ) {
                    $add_to_cart_url = add_query_arg( 'add-to-cart', $related_product_id, $cart_url );
                }
            }

            if ( ! $add_to_cart_url ) {
                $add_to_cart_url = $permalink;
                $has_add_to_cart = false;
            }

            $badge_html = '';

            if ( $related_product->is_on_sale() ) {
                $badge_html = '<span class="onsale">' . esc_html__( 'Sale!', 'woocommerce' ) . '</span>';
            }

            ?>
            <article <?php wc_product_class( 'bw-wallpost-item bw-slick-item', $related_product ); ?>>
                <div class="bw-wallpost-card bw-slick-item__inner bw-ss__card">
                    <div class="bw-slider-image-container">
                        <?php
                        $media_classes = [ 'bw-wallpost-media', 'bw-slick-item__image', 'bw-ss__media' ];
                        if ( ! $thumbnail_html ) {
                            $media_classes[] = 'bw-wallpost-media--placeholder';
                            $media_classes[] = 'bw-slick-item__image--placeholder';
                        }
                        ?>
                        <div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $media_classes ) ) ); ?>">
                            <?php if ( $badge_html ) : ?>
                                <div class="bw-related-products__badge"><?php echo wp_kses_post( $badge_html ); ?></div>
                            <?php endif; ?>

                            <?php if ( $thumbnail_html ) : ?>
                                <a class="bw-wallpost-media-link bw-slick-item__media-link bw-ss__media-link" href="<?php echo esc_url( $permalink ); ?>">
                                    <div class="bw-wallpost-image bw-slick-slider-image<?php echo $hover_image_html ? ' bw-wallpost-image--has-hover bw-slick-slider-image--has-hover' : ''; ?>">
                                        <?php echo wp_kses_post( $thumbnail_html ); ?>
                                        <?php if ( $hover_image_html ) : ?>
                                            <?php echo wp_kses_post( $hover_image_html ); ?>
                                        <?php endif; ?>
                                    </div>
                                </a>

                                <div class="bw-wallpost-overlay overlay-buttons bw-ss__overlay has-buttons">
                                    <div class="bw-wallpost-overlay-buttons bw-ss__buttons bw-slide-buttons<?php echo $has_add_to_cart ? ' bw-wallpost-overlay-buttons--double bw-ss__buttons--double' : ''; ?>">
                                        <a class="bw-wallpost-overlay-button overlay-button overlay-button--view bw-ss__btn bw-view-btn bw-slide-button" href="<?php echo esc_url( $permalink ); ?>">
                                            <span class="bw-wallpost-overlay-button__label overlay-button__label"><?php esc_html_e( 'View Product', 'bw-elementor-widgets' ); ?></span>
                                        </a>
                                        <?php if ( $has_add_to_cart && $add_to_cart_url ) : ?>
                                            <a class="bw-wallpost-overlay-button overlay-button overlay-button--cart bw-ss__btn bw-btn-addtocart bw-slide-button" href="<?php echo esc_url( $add_to_cart_url ); ?>" data-open-cart-popup="1">
                                                <span class="bw-wallpost-overlay-button__label overlay-button__label"><?php esc_html_e( 'Add to Cart', 'bw-elementor-widgets' ); ?></span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else : ?>
                                <span class="bw-wallpost-image-placeholder bw-slick-item__image-placeholder" aria-hidden="true"></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="bw-wallpost-content bw-slick-item__content bw-ss__content bw-slider-content bw-slick-slider-text-box">
                        <h3 class="bw-wallpost-title bw-slick-item__title bw-slick-title bw-slider-title">
                            <a href="<?php echo esc_url( $permalink ); ?>">
                                <?php echo esc_html( $title ); ?>
                            </a>
                        </h3>

                        <?php if ( $excerpt ) : ?>
                            <div class="bw-wallpost-description bw-slick-item__excerpt bw-slick-description bw-slider-description"><?php echo wp_kses_post( $excerpt ); ?></div>
                        <?php endif; ?>

                        <?php if ( $price_html ) : ?>
                            <div class="bw-wallpost-price bw-slick-item__price price bw-slick-price bw-slider-price"><?php echo wp_kses_post( $price_html ); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php
        }

        echo '</div>';
        echo '</section>';
        echo '</div>';
    }
}
