<?php
/**
 * Email verification page.
 *
 * Custom Blackwork styling wrapper for verify-email flow on order-received.
 *
 * @package WooCommerce\Templates
 * @version 7.9.0
 *
 * @var bool   $failed_submission Indicates if the last attempt to verify failed.
 * @var string $verify_url        The URL for the email verification form.
 */

defined( 'ABSPATH' ) || exit;
?>
<form name="checkout" method="post" class="woocommerce-form woocommerce-verify-email bw-verify-email-form" action="<?php echo esc_url( $verify_url ); ?>">
    <?php
    wp_nonce_field( 'wc_verify_email', 'check_submission' );

    if ( $failed_submission ) {
        wc_print_notice( esc_html__( 'We were unable to verify the email address you provided. Please try again.', 'woocommerce' ), 'error' );
    }
    ?>

    <p class="bw-verify-email-form__intro">
        <?php
        printf(
            /* translators: 1: opening login link 2: closing login link */
            esc_html__( 'To view this page, you must either %1$slogin%2$s or verify the email address associated with the order.', 'woocommerce' ),
            '<a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '">',
            '</a>'
        );
        ?>
    </p>

    <p class="form-row bw-verify-email-form__field">
        <input type="email" class="input-text" name="email" id="email" autocomplete="email" placeholder=" " required />
        <label for="email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
    </p>

    <p class="form-row bw-verify-email-form__actions">
        <button type="submit" class="woocommerce-button button <?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ); ?>" name="verify" value="1">
            <?php esc_html_e( 'Verify', 'woocommerce' ); ?>
        </button>
    </p>
</form>
