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

    <section class="bw-dashboard-section">
        <div class="bw-section-header">
            <h3><?php esc_html_e( 'Latest invoices', 'bw' ); ?></h3>
        </div>
        <?php if ( ! empty( $orders ) ) : ?>
            <ul class="bw-invoices-list">
                <?php foreach ( $orders as $order ) :
                    $date_created = $order->get_date_created();
                    $date_display = $date_created ? strtolower( date_i18n( 'F j, Y', $date_created->getTimestamp() ) ) : '';
                    ?>
                    <li class="bw-invoice-row">
                        <span class="bw-invoice-id">#<?php echo esc_html( $order->get_order_number() ); ?></span>
                        <span class="bw-invoice-date"><?php echo esc_html( $date_display ); ?></span>
                        <span class="bw-invoice-total"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
                        <a class="bw-invoice-view" href="<?php echo esc_url( $order->get_view_order_url() ); ?>"><?php esc_html_e( 'view', 'bw' ); ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p class="bw-empty-state"><?php esc_html_e( 'No invoices found yet.', 'bw' ); ?></p>
        <?php endif; ?>

        <a class="bw-view-all" href="<?php echo esc_url( $orders_url ); ?>"><?php esc_html_e( 'view all', 'bw' ); ?></a>
    </section>
</div>
