<?php
/**
 * My Downloads
 *
 * @package WooCommerce/Templates
 * @version 3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$downloads = wc_get_customer_available_downloads();

if ( ! is_array( $downloads ) ) {
    $downloads = [];
}
?>
<div class="bw-downloads">
    <?php if ( $downloads ) : ?>
        <div class="bw-download-list">
            <?php foreach ( $downloads as $download ) :
                if ( is_array( $download ) ) {
                    $download_item = $download;
                } elseif ( is_object( $download ) ) {
                    $download_item = (array) $download;
                } else {
                    $download_item = [];
                }

                $product_id   = isset( $download_item['product_id'] ) ? absint( $download_item['product_id'] ) : 0;
                $download_url = '';

                if ( ! empty( $download_item['download_url'] ) ) {
                    $download_url = $download_item['download_url'];
                } elseif ( ! empty( $download_item['file'] ) ) {
                    $download_url = $download_item['file'];
                }

                $product       = $product_id ? wc_get_product( $product_id ) : null;
                $product_name  = $product ? $product->get_name() : '';
                $download_name = $product_name;

                if ( ! $download_name && ! empty( $download_item['product_name'] ) ) {
                    $download_name = (string) $download_item['product_name'];
                }

                if ( ! $download_name && ! empty( $download_item['download_name'] ) ) {
                    $download_name = (string) $download_item['download_name'];
                }

                if ( ! $download_name ) {
                    $download_name = __( 'Product', 'bw' );
                }

                $thumbnail = $product ? $product->get_image( 'thumbnail' ) : wc_placeholder_img( 'thumbnail' );

                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    if ( empty( $download_item ) || ! $download_url ) {
                        error_log( '[BW Downloads] malformed item keys: ' . implode( ',', array_keys( $download_item ) ) );
                    }
                }
                ?>
                <div class="bw-download-row">
                    <div class="bw-download-thumb"><?php echo wp_kses_post( $thumbnail ); ?></div>
                    <div class="bw-download-title"><?php echo esc_html( $download_name ); ?></div>
                    <div class="bw-download-action">
                        <?php if ( $download_url ) : ?>
                            <a class="bw-download-button" href="<?php echo esc_url( $download_url ); ?>" download>
                                <span class="bw-download-icon" aria-hidden="true">
                                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M10 2v10" stroke="currentColor" stroke-width="2"/>
                                        <path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="2" fill="none"/>
                                        <path d="M3 15h14v3H3z" fill="currentColor"/>
                                    </svg>
                                </span>
                                <?php esc_html_e( 'Download', 'bw' ); ?>
                            </a>
                        <?php else : ?>
                            <span class="bw-download-button is-disabled" aria-disabled="true">
                                <?php esc_html_e( 'Unavailable', 'bw' ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p class="bw-downloads-empty"><?php esc_html_e( 'No downloads available.', 'bw' ); ?></p>
    <?php endif; ?>
</div>
