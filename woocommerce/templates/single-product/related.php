<?php
/**
 * Related Products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/related.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     10.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( $related_products ) :
    /**
     * Ensure all images of related products are lazy loaded by increasing the
     * current media count to WordPress's lazy loading threshold if needed.
     * Because wp_increase_content_media_count() is a private function, we
     * check for its existence before use.
     */
    if ( function_exists( 'wp_increase_content_media_count' ) ) {
        $content_media_count = wp_increase_content_media_count( 0 );
        if ( $content_media_count < wp_omit_loading_attr_threshold() ) {
            wp_increase_content_media_count( wp_omit_loading_attr_threshold() - $content_media_count );
        }
    }

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
            <?php
            foreach ( $related_products as $related_product ) :
                if ( ! class_exists( 'BW_Product_Card_Renderer' ) ) {
                    continue;
                }

                echo BW_Product_Card_Renderer::render_card(
                    $related_product,
                    [
                        'image_size'           => 'woocommerce_thumbnail',
                        'show_image'           => true,
                        'show_hover_image'     => true,
                        'hover_image_source'   => 'gallery_first',
                        'show_title'           => true,
                        'show_description'     => true,
                        'description_mode'     => 'short_only',
                        'show_price'           => true,
                        'show_buttons'         => true,
                        'show_add_to_cart'     => true,
                        'open_cart_popup'      => true,
                        'use_wc_product_class' => true,
                    ]
                );
            endforeach;
            ?>
        </div>
    </section>
    <?php
endif;

wp_reset_postdata();
