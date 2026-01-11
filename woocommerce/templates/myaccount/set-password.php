<?php
/**
 * Set Password endpoint content.
 *
 * @package WooCommerce/Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="bw-account-set-password">
    <h2 class="bw-account-set-password__title"><?php esc_html_e( 'Set Password', 'bw' ); ?></h2>
    <p class="bw-account-set-password__intro"><?php esc_html_e( 'Complete your account setup by choosing a new password.', 'bw' ); ?></p>

    <form class="bw-account-set-password__form" data-bw-set-password-form>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="bw_set_password"><?php esc_html_e( 'New password', 'bw' ); ?> <span class="required">*</span></label>
            <input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="new_password" id="bw_set_password" autocomplete="new-password" required />
        </p>

        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="bw_set_password_confirm"><?php esc_html_e( 'Confirm new password', 'bw' ); ?> <span class="required">*</span></label>
            <input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="confirm_password" id="bw_set_password_confirm" autocomplete="new-password" required />
        </p>

        <div class="bw-account-set-password__error" role="alert" aria-live="polite" hidden></div>
        <div class="bw-account-set-password__success" role="status" aria-live="polite" hidden></div>

        <p class="form-row">
            <button type="submit" class="woocommerce-button button bw-account-set-password__submit"><?php esc_html_e( 'Save password', 'bw' ); ?></button>
        </p>
    </form>

    <div class="bw-account-set-password__missing-token" data-bw-missing-token hidden>
        <p><?php esc_html_e( 'This page must be opened from the Supabase invite email link. Please click “Accept the invite” again.', 'bw' ); ?></p>
        <p class="bw-account-set-password__resend-row">
            <label class="bw-account-set-password__label" for="bw_resend_invite_email"><?php esc_html_e( 'Email address', 'bw' ); ?></label>
            <input class="woocommerce-Input woocommerce-Input--text input-text bw-account-set-password__email" type="email" id="bw_resend_invite_email" data-bw-resend-email autocomplete="email" />
            <button class="woocommerce-button button bw-account-set-password__cta" type="button" data-bw-resend-invite>
                <?php esc_html_e( 'Request a new invite', 'bw' ); ?>
            </button>
        </p>
        <p class="bw-account-set-password__notice" data-bw-resend-notice hidden></p>
    </div>
</div>
