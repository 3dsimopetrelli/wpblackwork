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
                $product   = wc_get_product( $download['product_id'] );
                $thumbnail = $product ? $product->get_image( 'thumbnail' ) : wc_placeholder_img( 'thumbnail' );
                ?>
                <li class="bw-download-row">
                    <span class="bw-download-thumb"><?php echo wp_kses_post( $thumbnail ); ?></span>
                    <span class="bw-download-name"><?php echo esc_html( $download['product_name'] ); ?></span>
                    <a class="bw-download-button" href="<?php echo esc_url( $download['download_url'] ); ?>">
                        <span class="bw-download-icon" aria-hidden="true">⬇</span>
                        <?php esc_html_e( 'download', 'bw' ); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p class="bw-empty-state"><?php esc_html_e( 'No downloads available yet.', 'bw' ); ?></p>
    <?php endif; ?>
</div>
