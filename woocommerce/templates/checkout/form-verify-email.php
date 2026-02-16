<?php
/**
 * Email verification page.
 *
 * Custom Blackwork session-expired style for order-received guests.
 *
 * @package WooCommerce\Templates
 * @version 7.9.0
 *
 * @var bool   $failed_submission Indicates if the last attempt to verify failed.
 * @var string $verify_url        The URL for the email verification form.
 */

defined( 'ABSPATH' ) || exit;
?>
<?php
if ( $failed_submission ) {
    wc_print_notice( esc_html__( 'We were unable to verify the email address you provided. Please log in and try again.', 'woocommerce' ), 'error' );
}
?>
<section class="bw-verify-email-cta" aria-label="<?php esc_attr_e( 'Login required', 'wpblackwork' ); ?>">
    <h2 class="bw-verify-email-cta__title">
        <?php esc_html_e( 'Uh-oh, your session has expired', 'wpblackwork' ); ?>
    </h2>
    <p class="bw-verify-email-cta__lead">
        <?php esc_html_e( 'Click the button below to continue.', 'wpblackwork' ); ?>
    </p>
    <p class="bw-verify-email-cta__lead bw-verify-email-cta__lead--secondary">
        <?php esc_html_e( 'You will be redirected to the login page where you can sign in to your account.', 'wpblackwork' ); ?>
    </p>

    <p class="bw-verify-email-cta__actions">
        <a class="button bw-verify-email-cta__button" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
            <?php esc_html_e( 'Go to Login', 'wpblackwork' ); ?>
        </a>
    </p>

    <hr class="bw-verify-email-cta__divider" />

    <p class="bw-verify-email-cta__footnote">
        <?php esc_html_e( 'You can log in by entering your email address since your account is already registered.', 'wpblackwork' ); ?>
    </p>
</section>
