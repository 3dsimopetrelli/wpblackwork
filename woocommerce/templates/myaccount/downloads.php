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

$customer_id = get_current_user_id();
$downloads   = bw_mew_get_dashboard_digital_orders( $customer_id, 200 );

if ( ! is_array( $downloads ) ) {
    $downloads = [];
}
?>
<div class="bw-downloads">
    <header class="bw-page-header bw-page-header--boxed">
        <h2 class="bw-section-title"><?php esc_html_e( 'Downloads', 'bw' ); ?></h2>
    </header>

    <?php if ( $downloads ) : ?>
        <ul class="bw-order-list">
            <?php foreach ( $downloads as $row ) : ?>
                <li class="bw-order-row bw-order-row--digital bw-order-row--no-price">
                    <div class="bw-order-thumb">
                        <?php if ( ! empty( $row['productUrl'] ) ) : ?>
                            <a href="<?php echo esc_url( $row['productUrl'] ); ?>" target="_blank" rel="noopener noreferrer" class="bw-order-product-link" aria-label="<?php echo esc_attr( $row['title'] ); ?>">
                        <?php endif; ?>
                            <?php if ( ! empty( $row['thumbnail'] ) ) : ?>
                                <img src="<?php echo esc_url( $row['thumbnail'] ); ?>" alt="<?php echo esc_attr( $row['title'] ); ?>" loading="lazy" />
                            <?php else : ?>
                                <span class="bw-order-thumb-placeholder" aria-hidden="true"></span>
                            <?php endif; ?>
                        <?php if ( ! empty( $row['productUrl'] ) ) : ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="bw-order-info">
                        <p class="bw-order-title">
                            <?php if ( ! empty( $row['productUrl'] ) ) : ?>
                                <a href="<?php echo esc_url( $row['productUrl'] ); ?>" target="_blank" rel="noopener noreferrer" class="bw-order-product-link"><?php echo esc_html( $row['title'] ); ?></a>
                            <?php else : ?>
                                <?php echo esc_html( $row['title'] ); ?>
                            <?php endif; ?>
                        </p>
                        <p class="bw-order-meta"><?php echo esc_html( $row['license'] ); ?> <span aria-hidden="true">|</span> <?php echo esc_html( $row['date'] ); ?></p>
                    </div>
                    <div class="bw-order-action">
                        <?php if ( ! empty( $row['downloadUrl'] ) ) : ?>
                            <a class="bw-download-button" href="<?php echo esc_url( $row['downloadUrl'] ); ?>" download>
                                <span class="bw-download-icon" aria-hidden="true">
                                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M10 2v10" stroke="currentColor" stroke-width="2"/>
                                        <path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="2" fill="none"/>
                                        <path d="M3 15h14v3H3z" fill="currentColor"/>
                                    </svg>
                                </span>
                                <span><?php esc_html_e( 'Download', 'bw' ); ?></span>
                            </a>
                        <?php else : ?>
                            <span class="bw-download-unavailable" aria-disabled="true">
                                <?php esc_html_e( 'Unavailable', 'bw' ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p class="bw-downloads-empty"><?php esc_html_e( 'No downloads available.', 'bw' ); ?></p>
    <?php endif; ?>
</div>
