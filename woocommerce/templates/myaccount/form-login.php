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
$logo                = get_option( 'bw_account_logo', '' );
$logo_width          = (int) get_option( 'bw_account_logo_width', 180 );
$logo_padding_top    = (int) get_option( 'bw_account_logo_padding_top', 0 );
$logo_padding_bottom = (int) get_option( 'bw_account_logo_padding_bottom', 30 );
$description         = get_option( 'bw_account_description', '' );
$show_description    = apply_filters( 'bw_account_show_description', true );
$back_text           = get_option( 'bw_account_back_text', 'go back to store' );
$back_url            = get_option( 'bw_account_back_url', '' );
$back_url            = $back_url ? $back_url : home_url( '/' );
$registration_mode   = get_option( 'bw_supabase_registration_mode', 'R2' );
$provider_signup_url = get_option( 'bw_supabase_provider_signup_url', '' );
$registration_mode    = in_array( $registration_mode, [ 'R1', 'R2', 'R3' ], true ) ? $registration_mode : 'R2';
$show_supabase_register = 'R3' !== $registration_mode;
$magic_link_enabled     = (int) get_option( 'bw_supabase_magic_link_enabled', 1 );
$oauth_google_enabled   = (int) get_option( 'bw_supabase_oauth_google_enabled', 1 );
$oauth_facebook_enabled = (int) get_option( 'bw_supabase_oauth_facebook_enabled', 1 );
$active_tab = ( $show_supabase_register && ( ( isset( $_GET['action'] ) && 'register' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) || isset( $_POST['register'] ) ) )
    ? 'register'
    : 'login';

if ( ! $show_supabase_register && 'register' === $active_tab ) {
    $active_tab = 'login';
}
?>

<div class="bw-account-login-page">
    <div class="bw-account-login">
        <div class="bw-account-login__media" <?php if ( $login_image ) : ?>style="background-image: url('<?php echo esc_url( $login_image ); ?>');"<?php endif; ?>></div>
        <div class="bw-account-login__content-wrapper">
            <div class="bw-account-login__content">
                <?php do_action( 'woocommerce_before_customer_login_form' ); ?>
                <?php wc_print_notices(); ?>
                <?php if ( isset( $_GET['bw_email_confirmed'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['bw_email_confirmed'] ) ) ) : ?>
                    <div class="woocommerce-message bw-account-login__notice">
                        <?php esc_html_e( 'Email confirmed. Please log in.', 'bw' ); ?>
                    </div>
                <?php endif; ?>

                <?php if ( $logo ) : ?>
                    <div class="bw-account-login__logo" style="padding-top: <?php echo absint( $logo_padding_top ); ?>px; padding-bottom: <?php echo absint( $logo_padding_bottom ); ?>px;">
                        <img src="<?php echo esc_url( $logo ); ?>" alt="<?php esc_attr_e( 'Account logo', 'bw' ); ?>" style="max-width: <?php echo absint( $logo_width ); ?>px;" />
                    </div>
                <?php endif; ?>

                <div class="bw-account-auth" data-bw-default-tab="<?php echo esc_attr( $active_tab ); ?>" data-bw-email-confirmed="<?php echo isset( $_GET['bw_email_confirmed'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['bw_email_confirmed'] ) ) ) : ''; ?>">
                    <div class="bw-account-auth__panels">
                        <div class="bw-account-auth__panel <?php echo 'login' === $active_tab ? 'is-active is-visible' : ''; ?>" data-bw-auth-panel="login">
                            <form class="bw-account-login__form bw-account-login__form--supabase" data-bw-supabase-form data-bw-supabase-action="magic-link">
                                <p class="bw-account-login__note"><?php esc_html_e( 'Enter your email and we will send you a login link.', 'bw' ); ?></p>

                                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                    <label for="bw_supabase_magic_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?> <span class="required">*</span></label>
                                    <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="bw_supabase_magic_email" autocomplete="email" required />
                                </p>

                                <div class="bw-account-login__error" role="alert" aria-live="polite" hidden></div>
                                <div class="bw-account-login__success" role="status" aria-live="polite" hidden></div>

                                <p class="form-row bw-account-login__actions">
                                    <button type="submit" class="woocommerce-button button bw-account-login__submit" data-bw-supabase-submit <?php echo $magic_link_enabled ? '' : 'disabled'; ?>><?php esc_html_e( 'Continue', 'bw' ); ?></button>
                                </p>
                            </form>

                            <div class="bw-account-login__divider">
                                <span><?php esc_html_e( 'or', 'bw' ); ?></span>
                            </div>

                            <div class="bw-account-login__oauth">
                                <button type="button" class="woocommerce-button button bw-account-login__oauth-button bw-account-login__oauth-button--google" data-bw-oauth-provider="google" <?php echo $oauth_google_enabled ? '' : 'disabled'; ?>>
                                    <?php esc_html_e( 'Continue with Google', 'bw' ); ?>
                                </button>
                                <button type="button" class="woocommerce-button button bw-account-login__oauth-button bw-account-login__oauth-button--facebook" data-bw-oauth-provider="facebook" <?php echo $oauth_facebook_enabled ? '' : 'disabled'; ?>>
                                    <?php esc_html_e( 'Continue with Facebook', 'bw' ); ?>
                                </button>
                            </div>

                            <?php if ( $show_supabase_register ) : ?>
                                <p class="bw-account-login__register">
                                    <button type="button" class="bw-account-login__register-link" data-bw-auth-tab="register"><?php esc_html_e( 'Register', 'bw' ); ?></button>
                                </p>
                            <?php endif; ?>
                        </div>

                        <?php if ( $show_supabase_register ) : ?>
                            <div class="bw-account-auth__panel <?php echo 'register' === $active_tab ? 'is-active is-visible' : ''; ?>" data-bw-auth-panel="register">
                                <?php if ( 'R1' === $registration_mode ) : ?>
                                    <div class="bw-account-login__form bw-account-login__form--supabase">
                                        <p class="bw-account-login__note"><?php esc_html_e( 'Create your account on the provider.', 'bw' ); ?></p>
                                        <p class="form-row bw-account-login__actions">
                                            <a class="woocommerce-button button bw-account-login__submit <?php echo $provider_signup_url ? '' : 'is-disabled'; ?>" href="<?php echo esc_url( $provider_signup_url ? $provider_signup_url : '#' ); ?>" <?php echo $provider_signup_url ? '' : 'aria-disabled="true"'; ?>>
                                                <?php esc_html_e( 'Create account', 'bw' ); ?>
                                            </a>
                                        </p>
                                        <?php if ( ! $provider_signup_url ) : ?>
                                            <p class="bw-account-login__note"><?php esc_html_e( 'Add a Provider Signup URL in Blackworksite > Account to enable this action.', 'bw' ); ?></p>
                                        <?php endif; ?>
                                        <p class="bw-account-login__back-to-login">
                                            <button type="button" class="bw-account-login__back-link" data-bw-auth-tab="login">← <?php esc_html_e( 'Go back to login', 'bw' ); ?></button>
                                        </p>
                                    </div>
                                <?php else : ?>
                                    <form class="bw-account-login__form bw-account-login__form--supabase" data-bw-register-form>
                                        <div class="bw-account-register-step is-active" data-bw-register-step="email">
                                            <p class="bw-account-login__note"><?php esc_html_e( 'Create your account with your email.', 'bw' ); ?></p>
                                            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                                <label for="bw_supabase_register_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?> <span class="required">*</span></label>
                                                <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="bw_supabase_register_email" autocomplete="email" required />
                                            </p>
                                            <div class="bw-account-login__error" role="alert" aria-live="polite" hidden></div>
                                            <div class="bw-account-login__success" role="status" aria-live="polite" hidden></div>
                                            <p class="form-row bw-account-login__actions">
                                                <button type="button" class="woocommerce-button button bw-account-login__submit" data-bw-register-continue><?php esc_html_e( 'Continue', 'bw' ); ?></button>
                                            </p>
                                        </div>

                                        <div class="bw-account-register-step" data-bw-register-step="password">
                                            <p class="bw-account-login__note"><?php esc_html_e( 'Create a password for your account.', 'bw' ); ?></p>
                                            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                                <label for="bw_supabase_register_password"><?php esc_html_e( 'Create password', 'woocommerce' ); ?> <span class="required">*</span></label>
                                                <input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="bw_supabase_register_password" autocomplete="new-password" required />
                                            </p>
                                            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                                <label for="bw_supabase_register_password_confirm"><?php esc_html_e( 'Confirm password', 'woocommerce' ); ?> <span class="required">*</span></label>
                                                <input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="confirm_password" id="bw_supabase_register_password_confirm" autocomplete="new-password" required />
                                            </p>
                                            <ul class="bw-account-login__rules">
                                                <li><?php esc_html_e( 'At least 8 characters.', 'bw' ); ?></li>
                                                <li><?php esc_html_e( 'Use a mix of letters and numbers.', 'bw' ); ?></li>
                                            </ul>
                                            <div class="bw-account-login__error" role="alert" aria-live="polite" hidden></div>
                                            <div class="bw-account-login__success" role="status" aria-live="polite" hidden></div>
                                            <p class="form-row bw-account-login__actions">
                                                <button type="submit" class="woocommerce-button button bw-account-login__submit" data-bw-register-submit><?php esc_html_e( 'Create account', 'bw' ); ?></button>
                                            </p>
                                            <p class="bw-account-login__back-to-login">
                                                <button type="button" class="bw-account-login__back-link" data-bw-auth-tab="login">← <?php esc_html_e( 'Go back to login', 'bw' ); ?></button>
                                            </p>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ( $description && $show_description ) : ?>
                        <div class="bw-auth-description"><?php echo wpautop( wp_kses_post( $description ) ); ?></div>
                    <?php endif; ?>
                </div>

                <div class="bw-account-login__back">
                    <a href="<?php echo esc_url( $back_url ); ?>"><?php echo esc_html( $back_text ); ?> <span aria-hidden="true">→</span></a>
                </div>

                <?php do_action( 'woocommerce_after_customer_login_form' ); ?>
            </div>
        </div>
    </div>
</div>
