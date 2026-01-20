<?php
/**
 * Login Form
 *
 * @package WooCommerce/Templates
 * @version 8.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

$login_image         = get_option( 'bw_account_login_image', '' );
$login_image_id      = (int) get_option( 'bw_account_login_image_id', 0 );
$logo                = get_option( 'bw_account_logo', '' );
$logo_id             = (int) get_option( 'bw_account_logo_id', 0 );
$logo_width          = (int) get_option( 'bw_account_logo_width', 180 );
$logo_padding_top    = (int) get_option( 'bw_account_logo_padding_top', 0 );
$logo_padding_bottom = (int) get_option( 'bw_account_logo_padding_bottom', 30 );
$login_title         = get_option( 'bw_account_login_title', 'Log in to Blackwork' );
$login_subtitle      = get_option(
    'bw_account_login_subtitle',
    "If you are new, we will create your account automatically.\nNew or returning, this works the same."
);
$show_social_buttons = (int) get_option( 'bw_account_show_social_buttons', 1 );
$login_mode          = get_option( 'bw_supabase_login_mode', 'native' );
$login_mode           = in_array( $login_mode, [ 'native', 'oidc' ], true ) ? $login_mode : 'native';
$magic_link_enabled     = (int) get_option( 'bw_supabase_magic_link_enabled', 1 );
$oauth_google_enabled   = (int) get_option( 'bw_supabase_oauth_google_enabled', 1 );
$oauth_facebook_enabled = (int) get_option( 'bw_supabase_oauth_facebook_enabled', 1 );
$oauth_apple_enabled    = (int) get_option( 'bw_supabase_oauth_apple_enabled', 0 );
$password_login_enabled = (int) get_option( 'bw_supabase_login_password_enabled', 1 );

$login_image_url = $login_image;
if ( $login_image_id ) {
    $login_image_attachment = wp_get_attachment_url( $login_image_id );
    if ( $login_image_attachment ) {
        $login_image_url = $login_image_attachment;
    }
}

$logo_url = $logo;
if ( $logo_id ) {
    $logo_attachment = wp_get_attachment_url( $logo_id );
    if ( $logo_attachment ) {
        $logo_url = $logo_attachment;
    }
}

$logo_styles = sprintf(
    '--bw-logo-width:%dpx; --bw-logo-pt:%dpx; --bw-logo-pb:%dpx;',
    absint( $logo_width ),
    absint( $logo_padding_top ),
    absint( $logo_padding_bottom )
);
$login_subtitle_html = nl2br( esc_html( $login_subtitle ) );
?>

<div class="bw-account-login-page">
    <div class="bw-account-login">
        <div class="bw-account-login__media" <?php if ( $login_image_url ) : ?>style="background-image: url('<?php echo esc_url( $login_image_url ); ?>');"<?php endif; ?>></div>
        <div class="bw-account-login__content-wrapper">
            <div class="bw-account-login__content">
                <?php do_action( 'woocommerce_before_customer_login_form' ); ?>
                <?php wc_print_notices(); ?>
                <?php if ( isset( $_GET['bw_email_confirmed'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['bw_email_confirmed'] ) ) ) : ?>
                    <div class="woocommerce-message bw-account-login__notice">
                        <?php esc_html_e( 'Email confirmed. Please log in.', 'bw' ); ?>
                    </div>
                <?php endif; ?>

                <?php if ( $logo_url ) : ?>
                    <div class="bw-account-login__logo" style="<?php echo esc_attr( $logo_styles ); ?>">
                        <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'Account logo', 'bw' ); ?>" />
                    </div>
                <?php endif; ?>

                <?php if ( $login_title || $login_subtitle ) : ?>
                    <div class="bw-account-login__intro">
                        <?php if ( $login_title ) : ?>
                            <h2 class="bw-account-login__title"><?php echo esc_html( $login_title ); ?></h2>
                        <?php endif; ?>
                        <?php if ( $login_subtitle ) : ?>
                            <p class="bw-account-login__subtitle"><?php echo wp_kses( $login_subtitle_html, [ 'br' => [] ] ); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="bw-account-auth" data-bw-default-tab="login" data-bw-email-confirmed="<?php echo isset( $_GET['bw_email_confirmed'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['bw_email_confirmed'] ) ) ) : ''; ?>">
                    <div class="bw-account-auth__panels">
                        <div class="bw-account-auth__panel is-active is-visible" data-bw-auth-panel="login">
                            <div class="bw-auth-screen bw-auth-screen--magic is-active is-visible" data-bw-screen="magic">
                                <form class="bw-account-login__form bw-account-login__form--supabase" method="post" action="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" data-bw-supabase-form data-bw-supabase-action="magic-link">
                                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                        <label for="bw_supabase_magic_email"><?php esc_html_e( 'Email', 'bw' ); ?> <span class="required">*</span></label>
                                        <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="bw_supabase_magic_email" autocomplete="email" required />
                                    </p>

                                <div class="bw-account-login__error" role="alert" aria-live="polite" hidden></div>
                                <div class="bw-account-login__success" role="status" aria-live="polite" hidden></div>

                                    <p class="form-row bw-account-login__actions">
                                        <button type="submit" class="woocommerce-button button bw-account-login__submit" data-bw-supabase-submit <?php echo $magic_link_enabled ? '' : 'disabled'; ?>><?php esc_html_e( 'Send code', 'bw' ); ?></button>
                                    </p>
                                </form>

                                <?php if ( $show_social_buttons && ( $oauth_google_enabled || $oauth_facebook_enabled || $oauth_apple_enabled ) ) : ?>
                                    <div class="bw-account-login__divider">
                                        <span><?php esc_html_e( 'or', 'bw' ); ?></span>
                                    </div>
                                    <div class="bw-account-login__oauth">
                                        <?php if ( $oauth_google_enabled ) : ?>
                                            <button type="button" class="woocommerce-button button bw-account-login__oauth-button bw-account-login__oauth-button--google" data-bw-oauth-provider="google">
                                                <?php esc_html_e( 'Continue with Google', 'bw' ); ?>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ( $oauth_facebook_enabled ) : ?>
                                            <button type="button" class="woocommerce-button button bw-account-login__oauth-button bw-account-login__oauth-button--facebook" data-bw-oauth-provider="facebook">
                                                <?php esc_html_e( 'Continue with Facebook', 'bw' ); ?>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ( $oauth_apple_enabled ) : ?>
                                            <button type="button" class="woocommerce-button button bw-account-login__oauth-button bw-account-login__oauth-button--apple" data-bw-oauth-provider="apple">
                                                <?php esc_html_e( 'Continue with Apple', 'bw' ); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ( $password_login_enabled ) : ?>
                                    <button type="button" class="woocommerce-button button bw-auth-btn bw-auth-btn--password" data-bw-go-password><?php esc_html_e( 'Login with password', 'bw' ); ?></button>
                                <?php endif; ?>

                            </div>

                            <?php if ( $password_login_enabled ) : ?>
                                <div class="bw-auth-screen bw-auth-screen--password" data-bw-screen="password">
                                    <form class="bw-account-login__form bw-account-login__form--supabase" data-bw-supabase-form data-bw-supabase-action="password-login">
                                        <p class="bw-account-login__note"><?php esc_html_e( 'Use your email and password to sign in.', 'bw' ); ?></p>

                                        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                            <label for="bw_supabase_login_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?> <span class="required">*</span></label>
                                            <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="bw_supabase_login_email" autocomplete="email" required />
                                        </p>

                                        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                            <label for="bw_supabase_login_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?> <span class="required">*</span></label>
                                            <input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="bw_supabase_login_password" autocomplete="current-password" required />
                                        </p>

                                        <div class="bw-account-login__error" role="alert" aria-live="polite" hidden></div>
                                        <div class="bw-account-login__success" role="status" aria-live="polite" hidden></div>

                                        <p class="form-row bw-account-login__actions">
                                            <button type="submit" class="woocommerce-button button bw-account-login__submit" data-bw-supabase-submit><?php esc_html_e( 'Log in', 'bw' ); ?></button>
                                        </p>
                                    </form>
                                    <p class="bw-account-login__back-to-login">
                                        <button type="button" class="bw-account-login__back-link" data-bw-go-magic>← <?php esc_html_e( 'Back to Login', 'bw' ); ?></button>
                                    </p>
                                </div>

                            <?php endif; ?>

                            <div class="bw-auth-screen bw-auth-screen--otp" data-bw-screen="otp">
                                <h3 class="bw-account-login__title"><?php esc_html_e( 'Enter the 6-digit code', 'bw' ); ?></h3>
                                <p class="bw-account-login__note">
                                    <?php esc_html_e( 'We sent a code to', 'bw' ); ?> <span data-bw-otp-email></span>
                                </p>
                                <form class="bw-account-login__form bw-account-login__form--otp" data-bw-otp-form>
                                    <div class="bw-otp-inputs" data-bw-otp-inputs>
                                        <input type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*" maxlength="1" aria-label="<?php esc_attr_e( 'Digit 1', 'bw' ); ?>" data-bw-otp-digit />
                                        <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" aria-label="<?php esc_attr_e( 'Digit 2', 'bw' ); ?>" data-bw-otp-digit />
                                        <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" aria-label="<?php esc_attr_e( 'Digit 3', 'bw' ); ?>" data-bw-otp-digit />
                                        <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" aria-label="<?php esc_attr_e( 'Digit 4', 'bw' ); ?>" data-bw-otp-digit />
                                        <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" aria-label="<?php esc_attr_e( 'Digit 5', 'bw' ); ?>" data-bw-otp-digit />
                                        <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" aria-label="<?php esc_attr_e( 'Digit 6', 'bw' ); ?>" data-bw-otp-digit />
                                    </div>

                                    <div class="bw-account-login__error" role="alert" aria-live="polite" hidden></div>
                                    <div class="bw-account-login__success" role="status" aria-live="polite" hidden></div>

                                    <p class="form-row bw-account-login__actions">
                                        <button type="submit" class="woocommerce-button button bw-account-login__submit" data-bw-otp-confirm disabled><?php esc_html_e( 'Confirm', 'bw' ); ?></button>
                                    </p>
                                    <button type="button" class="bw-account-login__register-link" data-bw-otp-resend><?php esc_html_e( 'Resend code', 'bw' ); ?></button>
                                    <p class="bw-account-login__back-to-login">
                                        <button type="button" class="bw-account-login__back-link" data-bw-go-magic>← <?php esc_html_e( 'Back to Login', 'bw' ); ?></button>
                                    </p>
                                </form>
                            </div>

                            <div class="bw-auth-screen bw-auth-screen--create-password" data-bw-screen="create-password">
                                <h3 class="bw-account-login__title"><?php esc_html_e( 'Create your password', 'bw' ); ?></h3>
                                <p class="bw-account-login__note"><?php esc_html_e( 'Set a password to finish creating your account.', 'bw' ); ?></p>
                                <form class="bw-account-login__form bw-account-login__form--supabase" data-bw-create-password-form>
                                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                        <label for="bw_supabase_create_password"><?php esc_html_e( 'New password', 'woocommerce' ); ?> <span class="required">*</span></label>
                                        <input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="new_password" id="bw_supabase_create_password" autocomplete="new-password" required data-bw-password-input />
                                    </p>
                                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                        <label for="bw_supabase_create_password_confirm"><?php esc_html_e( 'Confirm password', 'woocommerce' ); ?> <span class="required">*</span></label>
                                        <input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="confirm_password" id="bw_supabase_create_password_confirm" autocomplete="new-password" required data-bw-password-confirm />
                                    </p>
                                    <ul class="bw-account-login__rules">
                                        <li data-bw-password-rule="length"><?php esc_html_e( 'At least 8 characters', 'bw' ); ?></li>
                                        <li data-bw-password-rule="upper"><?php esc_html_e( 'At least 1 uppercase letter', 'bw' ); ?></li>
                                        <li data-bw-password-rule="number"><?php esc_html_e( 'At least 1 number or special character', 'bw' ); ?></li>
                                    </ul>

                                    <div class="bw-account-login__error" role="alert" aria-live="polite" hidden></div>
                                    <div class="bw-account-login__success" role="status" aria-live="polite" hidden></div>

                                    <p class="form-row bw-account-login__actions">
                                        <button type="submit" class="woocommerce-button button bw-account-login__submit" data-bw-create-password-submit disabled><?php esc_html_e( 'Save and continue', 'bw' ); ?></button>
                                    </p>
                                </form>
                                <p class="bw-account-login__back-to-login">
                                    <button type="button" class="bw-account-login__back-link" data-bw-go-magic>← <?php esc_html_e( 'Back to Login', 'bw' ); ?></button>
                                </p>
                            </div>

                        </div>
                    </div>

                </div>

                <?php do_action( 'woocommerce_after_customer_login_form' ); ?>
            </div>
        </div>
    </div>
</div>
