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
    <header class="bw-page-header">
        <h2><?php esc_html_e( 'My purchases', 'bw' ); ?></h2>
        <div class="bw-tab-switcher" role="tablist">
            <button class="bw-tab is-active" type="button" role="tab" aria-selected="true"><?php esc_html_e( 'Downloads', 'bw' ); ?></button>
            <button class="bw-tab" type="button" role="tab" aria-selected="false" disabled><?php esc_html_e( 'Invoices', 'bw' ); ?></button>
        </div>
    </header>

    <?php if ( $downloads ) : ?>
        <ul class="bw-download-list">
            <?php foreach ( $downloads as $download ) :
                $download_item = [];

                if ( is_array( $download ) ) {
                    $download_item = $download;
                } elseif ( is_object( $download ) ) {
                    $download_item = (array) $download;
                }

                $product_id   = isset( $download_item['product_id'] ) ? absint( $download_item['product_id'] ) : 0;
                $download_url = ! empty( $download_item['download_url'] ) ? $download_item['download_url'] : '';
                $product      = $product_id ? wc_get_product( $product_id ) : null;
                $thumbnail    = $product ? $product->get_image( 'thumbnail' ) : wc_placeholder_img( 'thumbnail' );
                $product_name = $product ? $product->get_name() : '';

                if ( ! $product_name && ! empty( $download_item['product_name'] ) ) {
                    $product_name = (string) $download_item['product_name'];
                }

                if ( ! $product_name ) {
                    $product_name = __( 'Product', 'bw' );
                }

                $download_name = ! empty( $download_item['download_name'] ) ? (string) $download_item['download_name'] : $product_name;

                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    $missing_keys = [];
                    $expected_keys = [ 'product_id', 'download_url', 'product_name', 'download_name', 'order_id' ];

                    foreach ( $expected_keys as $expected_key ) {
                        if ( empty( $download_item[ $expected_key ] ) ) {
                            $missing_keys[] = $expected_key;
                        }
                    }

                    if ( empty( $download_item ) || ! empty( $missing_keys ) || ! $download_url ) {
                        $sanitized_summary = [
                            'product_id'   => $product_id,
                            'has_url'      => (bool) $download_url,
                            'keys_present' => array_keys( $download_item ),
                            'missing_keys' => $missing_keys,
                        ];

                        error_log( '[BW MyAccount Downloads] malformed download item: ' . print_r( $sanitized_summary, true ) );
                    }
                }
                ?>
                <li class="bw-download-row">
                    <span class="bw-download-thumb"><?php echo wp_kses_post( $thumbnail ); ?></span>
                    <span class="bw-download-name"><?php echo esc_html( $download_name ); ?></span>
                    <?php if ( $download_url ) : ?>
                        <a class="bw-download-button" href="<?php echo esc_url( $download_url ); ?>">
                            <span class="bw-download-icon" aria-hidden="true">⬇</span>
                            <?php esc_html_e( 'download', 'bw' ); ?>
                        </a>
                    <?php else : ?>
                        <span class="bw-download-button" aria-disabled="true">
                            <?php esc_html_e( 'Download unavailable', 'bw' ); ?>
                        </span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p class="bw-empty-state"><?php esc_html_e( 'No downloads available yet.', 'bw' ); ?></p>
    <?php endif; ?>
</div>
