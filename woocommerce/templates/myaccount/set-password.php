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
</div>
