<?php
/**
 * Related Products
 *
 * This template displays related products using the Wall Post card layout.
 *
 * @package WooCommerce/Templates
 * @version 3.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( $related_products ) :
    $heading = apply_filters( 'woocommerce_product_related_products_heading', __( 'Related products', 'woocommerce' ) );

    $columns = absint( apply_filters( 'bw_mew_related_products_columns', 3 ) );
    $gap     = absint( apply_filters( 'bw_mew_related_products_gap', 24 ) );
    $image_height = absint( apply_filters( 'bw_mew_related_products_image_height', 625 ) );

    wc_set_loop_prop( 'name', 'related' );
    wc_set_loop_prop( 'columns', $columns );
    ?>

    <section class="related products bw-related-products bw-wallpost" style="--bw-wallpost-columns: <?php echo esc_attr( $columns ); ?>; --bw-wallpost-gap: <?php echo esc_attr( $gap ); ?>px; --bw-wallpost-image-height: <?php echo esc_attr( $image_height ); ?>px;">
        <?php if ( $heading ) : ?>
            <h2 class="bw-related-products__title"><?php echo esc_html( $heading ); ?></h2>
        <?php endif; ?>

        <div class="bw-related-products-grid bw-wallpost-grid">
            <?php foreach ( $related_products as $related_product ) :
                $post_object = get_post( $related_product->get_id() );
                setup_postdata( $GLOBALS['post'] =& $post_object );

                $product_id     = $related_product->get_id();
                $permalink      = function_exists( 'bw_get_safe_product_permalink' )
                    ? bw_get_safe_product_permalink( $related_product )
                    : get_permalink( $product_id );
                $title          = get_the_title( $product_id );
                $short_desc     = $related_product->get_short_description();
                $excerpt        = $short_desc ? wp_trim_words( wp_strip_all_tags( $short_desc ), 30 ) : '';
                $thumbnail_id   = $related_product->get_image_id();
                $thumbnail_html = $thumbnail_id ? wp_get_attachment_image( $thumbnail_id, 'woocommerce_thumbnail', false, [ 'class' => 'bw-slider-main', 'loading' => 'lazy' ] ) : '';

                $gallery_ids       = $related_product->get_gallery_image_ids();
                $hover_image_id    = $gallery_ids ? reset( $gallery_ids ) : 0;
                $hover_image_html  = $hover_image_id ? wp_get_attachment_image( $hover_image_id, 'woocommerce_thumbnail', false, [ 'class' => 'bw-slider-hover', 'loading' => 'lazy' ] ) : '';
                $price_html        = $related_product->get_price_html();
                $add_to_cart_url   = '';
                $has_add_to_cart   = true;

                if ( $related_product->is_type( 'variable' ) ) {
                    $add_to_cart_url = $permalink;
                } else {
                    $cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '';

                    if ( $cart_url ) {
                        $add_to_cart_url = add_query_arg( 'add-to-cart', $product_id, $cart_url );
                    }
                }

                if ( ! $add_to_cart_url ) {
                    $add_to_cart_url = $permalink;
                    $has_add_to_cart = false;
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
            <?php endforeach; ?>
        </div>
    </section>
    <?php
endif;

wp_reset_postdata();
