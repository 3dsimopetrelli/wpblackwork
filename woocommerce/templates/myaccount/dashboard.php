<?php
/**
 * My Account Dashboard
 *
 * @package WooCommerce/Templates
 * @version 8.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_user         = wp_get_current_user();
$identity             = bw_mew_get_dashboard_identity( $current_user->ID );
$full_name            = $identity['full_name'];
$account_email        = $identity['email'];
$black_box_text       = bw_mew_get_my_account_black_box_text();
$support_link         = bw_mew_get_my_account_support_link();
$orders               = bw_mew_get_recent_customer_orders( 3 );
$orders_url           = wc_get_endpoint_url( 'orders' );
$member_since         = $current_user->user_registered ? date_i18n( 'F Y', strtotime( $current_user->user_registered ) ) : '';
$latest_order         = ! empty( $orders ) && $orders[0] instanceof WC_Order ? $orders[0] : null;
$last_purchase        = $latest_order && $latest_order->get_date_created() ? date_i18n( 'F j, Y', $latest_order->get_date_created()->getTimestamp() ) : '';
$library_count        = bw_mew_get_customer_library_count( $current_user->ID );
$digital_orders       = bw_mew_get_dashboard_digital_orders( $current_user->ID, 6 );
$physical_orders      = bw_mew_get_dashboard_physical_orders( $current_user->ID, 6 );
$library_label        = sprintf(
    /* translators: %d: purchased product count */
    _n( '%d product in your library', '%d products in your library', $library_count, 'bw' ),
    $library_count
);
?>
<div class="bw-account-dashboard">
    <div class="bw-account-hero">
        <div class="bw-hero-box bw-hero-welcome">
            <div class="bw-hero-top-row">
                <p class="bw-hero-label"><?php esc_html_e( 'WELCOME BACK', 'bw' ); ?></p>
                <p class="bw-hero-verified">&#10003; <?php esc_html_e( 'Account verified', 'bw' ); ?></p>
            </div>

            <?php if ( '' !== $full_name ) : ?>
                <h2 class="bw-hero-title"><?php echo esc_html( $full_name ); ?></h2>
            <?php endif; ?>

            <?php if ( '' !== $account_email ) : ?>
                <p class="bw-hero-email"><?php echo esc_html( $account_email ); ?></p>
            <?php endif; ?>

            <div class="bw-hero-footer bw-hero-footer--stats">
                <div class="bw-hero-meta">
                    <p class="bw-hero-meta-item"><?php echo esc_html( sprintf( __( 'Member since %s', 'bw' ), $member_since ? $member_since : '—' ) ); ?></p>
                    <p class="bw-hero-meta-item"><?php echo esc_html( sprintf( __( 'Last purchase %s', 'bw' ), $last_purchase ? $last_purchase : '—' ) ); ?></p>
                </div>
                <p class="bw-hero-library">&#10003; <?php echo esc_html( $library_label ); ?></p>
            </div>
        </div>

        <div class="bw-hero-box bw-hero-message">
            <p class="bw-hero-help-title"><?php esc_html_e( 'Need help?', 'bw' ); ?></p>
            <div class="bw-hero-body"><?php echo wp_kses_post( wpautop( $black_box_text ) ); ?></div>
            <div class="bw-hero-footer">
                <a class="bw-hero-support-link" href="<?php echo esc_url( $support_link ); ?>"><?php esc_html_e( 'Contact support', 'bw' ); ?></a>
            </div>
        </div>
    </div>

    <section class="bw-dashboard-thanks">
        <p><?php esc_html_e( 'Thank you for your purchase. Your files are ready below.', 'bw' ); ?></p>
    </section>

    <section class="bw-dashboard-section bw-dashboard-section--digital">
        <div class="bw-order-card">
            <div class="bw-section-header bw-section-header--inside">
                <h3><?php esc_html_e( 'Your digital orders', 'bw' ); ?></h3>
            </div>
            <?php if ( ! empty( $digital_orders ) ) : ?>
                <ul class="bw-order-list">
                    <?php foreach ( $digital_orders as $row ) : ?>
                        <li class="bw-order-row bw-order-row--digital">
                            <div class="bw-order-thumb">
                                <?php if ( ! empty( $row['thumbnail'] ) ) : ?>
                                    <img src="<?php echo esc_url( $row['thumbnail'] ); ?>" alt="<?php echo esc_attr( $row['title'] ); ?>" loading="lazy" />
                                <?php else : ?>
                                    <span class="bw-order-thumb-placeholder" aria-hidden="true"></span>
                                <?php endif; ?>
                            </div>
                            <div class="bw-order-info">
                                <p class="bw-order-title"><?php echo esc_html( $row['title'] ); ?></p>
                                <p class="bw-order-meta"><?php echo esc_html( $row['license'] ); ?> <span aria-hidden="true">|</span> <?php echo esc_html( $row['date'] ); ?></p>
                            </div>
                            <p class="bw-order-price"><?php echo wp_kses_post( $row['price'] ); ?></p>
                            <div class="bw-order-action">
                                <?php if ( ! empty( $row['downloadUrl'] ) ) : ?>
                                    <a class="bw-order-btn bw-order-btn--download" href="<?php echo esc_url( $row['downloadUrl'] ); ?>"><?php esc_html_e( 'Download', 'bw' ); ?></a>
                                <?php else : ?>
                                    <a class="bw-order-btn bw-order-btn--details" href="<?php echo esc_url( $row['orderUrl'] ); ?>"><?php esc_html_e( 'View details', 'bw' ); ?></a>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="bw-empty-state"><?php esc_html_e( 'No digital orders found yet.', 'bw' ); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <section class="bw-dashboard-section bw-dashboard-section--physical">
        <div class="bw-order-card">
            <div class="bw-section-header bw-section-header--inside">
                <h3><?php esc_html_e( 'Physical orders', 'bw' ); ?></h3>
            </div>
            <?php if ( ! empty( $physical_orders ) ) : ?>
                <ul class="bw-order-list">
                    <?php foreach ( $physical_orders as $row ) : ?>
                        <li class="bw-order-row bw-order-row--physical">
                            <div class="bw-order-thumb">
                                <?php if ( ! empty( $row['thumbnail'] ) ) : ?>
                                    <img src="<?php echo esc_url( $row['thumbnail'] ); ?>" alt="<?php echo esc_attr( $row['title'] ); ?>" loading="lazy" />
                                <?php else : ?>
                                    <span class="bw-order-thumb-placeholder" aria-hidden="true"></span>
                                <?php endif; ?>
                            </div>
                            <div class="bw-order-info">
                                <p class="bw-order-title"><?php echo esc_html( $row['title'] ); ?></p>
                                <p class="bw-order-meta"><?php echo esc_html( $row['date'] ); ?></p>
                            </div>
                            <p class="bw-order-price"><?php echo wp_kses_post( $row['price'] ); ?></p>
                            <div class="bw-order-action">
                                <a class="bw-order-btn bw-order-btn--details" href="<?php echo esc_url( $row['orderUrl'] ); ?>"><?php esc_html_e( 'View order', 'bw' ); ?></a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="bw-empty-state"><?php esc_html_e( 'No physical orders found yet.', 'bw' ); ?></p>
            <?php endif; ?>
        </div>
    </section>
</div>
