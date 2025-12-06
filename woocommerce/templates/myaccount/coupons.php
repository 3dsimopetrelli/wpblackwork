<?php
/**
 * Customer coupons
 *
 * @package WooCommerce/Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$coupons = bw_mew_get_customer_coupons();
?>
<div class="bw-coupons">
    <header class="bw-page-header">
        <h2><?php esc_html_e( 'Available Coupons', 'bw' ); ?></h2>
    </header>

    <?php if ( ! empty( $coupons ) ) : ?>
        <div class="bw-coupons-grid">
            <?php foreach ( $coupons as $coupon ) :
                $code           = $coupon instanceof WC_Coupon ? $coupon->get_code() : ( $coupon['code'] ?? '' );
                $description    = $coupon instanceof WC_Coupon ? $coupon->get_description() : ( $coupon['description'] ?? '' );
                $date_created   = $coupon instanceof WC_Coupon ? $coupon->get_date_created() : null;
                $date_expires   = $coupon instanceof WC_Coupon ? $coupon->get_date_expires() : null;
                $usage_limit    = $coupon instanceof WC_Coupon ? $coupon->get_usage_limit() : 0;
                $usage_count    = $coupon instanceof WC_Coupon ? $coupon->get_usage_count() : 0;
                $start_date     = $date_created ? $date_created->date_i18n( 'M j, Y' ) : '';
                $end_date       = $date_expires ? $date_expires->date_i18n( 'M j, Y' ) : __( 'No expiry', 'bw' );
                $already_used   = $usage_limit && $usage_count >= $usage_limit;
                ?>
                <article class="bw-coupon-card">
                    <div class="bw-coupon-header">
                        <span class="bw-coupon-label"><?php esc_html_e( 'Coupon Code:', 'bw' ); ?></span>
                        <span class="bw-coupon-code"><?php echo esc_html( $code ); ?></span>
                    </div>
                    <p class="bw-coupon-description"><?php echo esc_html( $description ); ?></p>
                    <p class="bw-coupon-validity">
                        <?php esc_html_e( 'Valid from', 'bw' ); ?> <?php echo esc_html( $start_date ); ?>
                        <?php esc_html_e( 'to', 'bw' ); ?> <?php echo esc_html( $end_date ); ?>
                    </p>
                    <?php if ( $already_used ) : ?>
                        <span class="bw-coupon-badge"><?php esc_html_e( 'Coupon already used', 'bw' ); ?></span>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p class="bw-empty-state"><?php esc_html_e( 'No coupons available right now.', 'bw' ); ?></p>
    <?php endif; ?>
</div>
