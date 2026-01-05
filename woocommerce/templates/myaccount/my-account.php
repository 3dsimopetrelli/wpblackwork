<?php
/**
 * My Account page
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 8.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
wc_print_notices();
// phpcs:enable

if ( ! is_user_logged_in() ) {
    wc_get_template( 'myaccount/form-login.php', [ 'redirect' => wc_get_page_permalink( 'myaccount' ) ] );
    return;
}
?>
<div class="bw-account-layout">
    <aside class="bw-account-navigation">
        <?php do_action( 'woocommerce_before_account_navigation' ); ?>
        <?php wc_get_template( 'myaccount/navigation.php' ); ?>
        <?php do_action( 'woocommerce_after_account_navigation' ); ?>
    </aside>

    <div class="bw-account-content" id="bw-account-content">
        <?php $account_title = get_post_field( 'post_title', get_queried_object_id(), 'raw' ); ?>
        <header class="bw-account-page-header">
            <h1 class="bw-account-title"><?php echo esc_html( $account_title ); ?></h1>
        </header>
        <?php do_action( 'woocommerce_account_content' ); ?>
    </div>
</div>
