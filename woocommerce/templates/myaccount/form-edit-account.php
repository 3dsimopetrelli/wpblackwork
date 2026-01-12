<?php
/**
 * Edit account form
 *
 * @package WooCommerce/Templates
 * @version 7.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$customer_id = get_current_user_id();
$customer    = wp_get_current_user();
$pending_email = get_user_meta( $customer_id, 'bw_supabase_pending_email', true );

/**
 * Hook: woocommerce_before_edit_account_form.
 */
$before_form_output = '';

if ( has_action( 'woocommerce_before_edit_account_form' ) ) {
    ob_start();
    do_action( 'woocommerce_before_edit_account_form' );
    $before_form_output = (string) ob_get_clean();

    $account_details_heading = wp_kses_post( __( 'Account details', 'woocommerce' ) );
    $heading_pattern         = '/<h[1-6][^>]*>\s*' . preg_quote( $account_details_heading, '/' ) . '\s*<\/h[1-6]>/i';

    if ( $before_form_output ) {
        $before_form_output = preg_replace( $heading_pattern, '', $before_form_output );
        echo $before_form_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
?>
<div class="bw-settings">
    <header class="bw-page-header">
        <h2><?php esc_html_e( 'Settings', 'bw' ); ?></h2>
        <div class="bw-tab-switcher" role="tablist">
            <button class="bw-tab is-active" type="button" data-target="#bw-tab-profile" aria-selected="true"><?php esc_html_e( 'Profile', 'bw' ); ?></button>
            <button class="bw-tab" type="button" data-target="#bw-tab-security" aria-selected="false"><?php esc_html_e( 'Security', 'bw' ); ?></button>
        </div>
    </header>
    <h2 class="screen-reader-text"><?php esc_html_e( 'Account details', 'woocommerce' ); ?></h2>

    <div class="bw-tab-panels">
        <div class="bw-tab-panel is-active" id="bw-tab-profile">
            <form class="bw-settings-form" data-bw-supabase-profile-form>
                <section class="bw-settings-block">
                    <h3><?php esc_html_e( 'Profile', 'bw' ); ?></h3>
                    <div class="bw-grid">
                        <div class="bw-field">
                            <label for="bw_profile_first_name"><?php esc_html_e( 'First name', 'woocommerce' ); ?> <span class="required">*</span></label>
                            <input type="text" name="first_name" id="bw_profile_first_name" autocomplete="given-name" value="<?php echo esc_attr( $customer->first_name ); ?>" required />
                        </div>
                        <div class="bw-field">
                            <label for="bw_profile_last_name"><?php esc_html_e( 'Last name', 'woocommerce' ); ?> <span class="required">*</span></label>
                            <input type="text" name="last_name" id="bw_profile_last_name" autocomplete="family-name" value="<?php echo esc_attr( $customer->last_name ); ?>" required />
                        </div>
                        <div class="bw-field">
                            <label for="bw_profile_display_name"><?php esc_html_e( 'Display name', 'woocommerce' ); ?> <span class="required">*</span></label>
                            <input type="text" name="display_name" id="bw_profile_display_name" value="<?php echo esc_attr( $customer->display_name ); ?>" required />
                            <p class="form-row form-row-wide">
                                <span class="description"><?php esc_html_e( 'This name is shown in your account and on reviews.', 'bw' ); ?></span>
                            </p>
                        </div>
                        <div class="bw-field">
                            <label for="bw_profile_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?></label>
                            <input type="email" name="email" id="bw_profile_email" autocomplete="email" value="<?php echo esc_attr( $customer->user_email ); ?>" readonly />
                            <p class="form-row form-row-wide">
                                <span class="description"><?php esc_html_e( 'To change your email address use the Security tab.', 'bw' ); ?></span>
                            </p>
                        </div>
                    </div>
                    <div class="bw-account-form__messages">
                        <div class="bw-account-form__error" role="alert" aria-live="polite" hidden></div>
                        <div class="bw-account-form__success" role="status" aria-live="polite" hidden></div>
                    </div>
                    <p>
                        <button type="submit" class="button"><?php esc_html_e( 'Save profile', 'bw' ); ?></button>
                    </p>
                </section>
            </form>
        </div>

        <div class="bw-tab-panel" id="bw-tab-security">
            <div class="woocommerce-message bw-account-security__notice" data-bw-pending-email-banner <?php echo $pending_email ? '' : 'hidden'; ?>>
                <?php
                if ( $pending_email ) {
                    printf(
                        /* translators: %s is the pending email address. */
                        esc_html__( 'Confirm your new email address (%s) from the confirmation email we sent you.', 'bw' ),
                        esc_html( $pending_email )
                    );
                }
                ?>
            </div>

            <form class="bw-settings-form" data-bw-supabase-password-form>
                <section class="bw-settings-block">
                    <h3><?php esc_html_e( 'Change password', 'bw' ); ?></h3>
                    <div class="bw-grid">
                        <div class="bw-field">
                            <label for="bw_security_password"><?php esc_html_e( 'New password', 'woocommerce' ); ?> <span class="required">*</span></label>
                            <input type="password" name="new_password" id="bw_security_password" autocomplete="new-password" required />
                        </div>
                        <div class="bw-field">
                            <label for="bw_security_password_confirm"><?php esc_html_e( 'Confirm new password', 'woocommerce' ); ?> <span class="required">*</span></label>
                            <input type="password" name="confirm_password" id="bw_security_password_confirm" autocomplete="new-password" required />
                        </div>
                    </div>
                    <div class="bw-account-form__messages">
                        <div class="bw-account-form__error" role="alert" aria-live="polite" hidden></div>
                        <div class="bw-account-form__success" role="status" aria-live="polite" hidden></div>
                    </div>
                    <p>
                        <button type="submit" class="button"><?php esc_html_e( 'Update password', 'bw' ); ?></button>
                    </p>
                </section>
            </form>

            <form class="bw-settings-form" data-bw-supabase-email-form>
                <section class="bw-settings-block">
                    <h3><?php esc_html_e( 'Change email', 'bw' ); ?></h3>
                    <div class="bw-grid">
                        <div class="bw-field">
                            <label for="bw_security_email"><?php esc_html_e( 'New email address', 'woocommerce' ); ?> <span class="required">*</span></label>
                            <input type="email" name="email" id="bw_security_email" autocomplete="email" required />
                        </div>
                    </div>
                    <div class="bw-account-form__messages">
                        <div class="bw-account-form__error" role="alert" aria-live="polite" hidden></div>
                        <div class="bw-account-form__success" role="status" aria-live="polite" hidden></div>
                    </div>
                    <p>
                        <button type="submit" class="button"><?php esc_html_e( 'Update email', 'bw' ); ?></button>
                    </p>
                </section>
            </form>
        </div>
    </div>
</div>

<?php
/**
 * Hook: woocommerce_after_edit_account_form.
 */
do_action( 'woocommerce_after_edit_account_form' );
