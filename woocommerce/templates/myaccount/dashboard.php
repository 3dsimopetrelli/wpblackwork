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

$current_user   = wp_get_current_user();
$display_name   = $current_user->display_name ? $current_user->display_name : $current_user->user_login;
$black_box_text = bw_mew_get_my_account_black_box_text();
$orders         = bw_mew_get_recent_customer_orders( 3 );
$orders_url     = wc_get_endpoint_url( 'orders' );
$downloads_url  = wc_get_endpoint_url( 'downloads' );
$account_url    = wc_get_endpoint_url( 'edit-account' );
$shop_url       = wc_get_page_permalink( 'shop' );
?>
<div class="bw-account-dashboard">
    <div class="bw-account-hero">
        <div class="bw-hero-box bw-hero-welcome">
            <p class="bw-hero-label"><?php esc_html_e( 'WELCOME TO YOUR DASHBOARD', 'bw' ); ?></p>
            <h2 class="bw-hero-title"><?php echo esc_html( $display_name ); ?></h2>
            <div class="bw-hero-footer">
                <p class="bw-hero-description">
                    <?php esc_html_e( 'Here you have quick access to your', 'bw' ); ?>
                    <a href="<?php echo esc_url( $orders_url ); ?>"><?php esc_html_e( 'invoices', 'bw' ); ?></a>
                    <?php esc_html_e( 'and your', 'bw' ); ?>
                    <a href="<?php echo esc_url( $account_url ); ?>"><?php esc_html_e( 'data', 'bw' ); ?></a>.
                    <?php esc_html_e( 'You can also', 'bw' ); ?>
                    <a href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'continue shopping', 'bw' ); ?></a>
                </p>
            </div>
        </div>
        <div class="bw-hero-box bw-hero-message">
            <p class="bw-hero-label"><?php esc_html_e( 'HEY', 'bw' ); ?></p>
            <div class="bw-hero-body"><?php echo wp_kses_post( wpautop( $black_box_text ) ); ?></div>
            <div class="bw-hero-footer">
                <a class="bw-hero-link" href="<?php echo esc_url( $orders_url ); ?>"><?php esc_html_e( 'MY PURCHASES', 'bw' ); ?></a>
            </div>
        </div>
    </div>

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
