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
$facebook            = (int) get_option( 'bw_account_facebook', 0 );
$google              = (int) get_option( 'bw_account_google', 0 );
$passwordless_url    = function_exists( 'bw_mew_get_passwordless_url' ) ? bw_mew_get_passwordless_url() : '';
$facebook_url        = ( $facebook && function_exists( 'bw_mew_get_social_login_url' ) ) ? bw_mew_get_social_login_url( 'facebook' ) : '';
$google_url          = ( $google && function_exists( 'bw_mew_get_social_login_url' ) ) ? bw_mew_get_social_login_url( 'google' ) : '';
$has_facebook        = $facebook && $facebook_url;
$has_google          = $google && $google_url;
$description         = get_option( 'bw_account_description', '' );
$back_text           = get_option( 'bw_account_back_text', 'go back to store' );
$back_url            = get_option( 'bw_account_back_url', '' );
$back_url            = $back_url ? $back_url : home_url( '/' );
$login_provider      = get_option( 'bw_account_login_provider', 'wordpress' );
$lost_password_url   = wc_lostpassword_url();
$registration_enabled = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
$generate_username    = 'yes' === get_option( 'woocommerce_registration_generate_username' );
$generate_password    = 'yes' === get_option( 'woocommerce_registration_generate_password' );
$active_tab           = ( isset( $_GET['action'] ) && 'lostpassword' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) ? 'lostpassword' : ( ( $registration_enabled && ( ( isset( $_GET['action'] ) && 'register' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) || isset( $_POST['register'] ) ) ) ? 'register' : 'login' );
?>

<div class="bw-account-login-page">
    <div class="bw-account-login">
        <div class="bw-account-login__media" <?php if ( $login_image ) : ?>style="background-image: url('<?php echo esc_url( $login_image ); ?>');"<?php endif; ?>></div>
        <div class="bw-account-login__content-wrapper">
            <div class="bw-account-login__content">
                <?php do_action( 'woocommerce_before_customer_login_form' ); ?>
                <?php wc_print_notices(); ?>

                <?php if ( $logo ) : ?>
                    <div class="bw-account-login__logo" style="padding-top: <?php echo absint( $logo_padding_top ); ?>px; padding-bottom: <?php echo absint( $logo_padding_bottom ); ?>px;">
                        <img src="<?php echo esc_url( $logo ); ?>" alt="<?php esc_attr_e( 'Account logo', 'bw' ); ?>" style="max-width: <?php echo absint( $logo_width ); ?>px;" />
                    </div>
                <?php endif; ?>

                <?php if ( 'supabase' === $login_provider ) : ?>
                    <div class="bw-account-auth" data-bw-default-tab="<?php echo esc_attr( $active_tab ); ?>">
                        <div class="bw-account-auth__tabs bw-account-auth__tabs--dual">
                            <button class="bw-account-auth__tab <?php echo 'login' === $active_tab ? 'is-active' : ''; ?>" type="button" data-bw-auth-tab="login"><?php esc_html_e( 'Login', 'woocommerce' ); ?></button>
                            <button class="bw-account-auth__tab <?php echo 'register' === $active_tab ? 'is-active' : ''; ?>" type="button" data-bw-auth-tab="register"><?php esc_html_e( 'Register', 'woocommerce' ); ?></button>
                        </div>

                        <div class="bw-account-auth__panels">
                            <div class="bw-account-auth__panel <?php echo 'login' === $active_tab ? 'is-active is-visible' : ''; ?>" data-bw-auth-panel="login">
                                <form class="bw-account-login__form bw-account-login__form--supabase" data-bw-supabase-form data-bw-supabase-action="login">
                                    <p class="bw-account-login__note"><?php esc_html_e( 'Use your Supabase account credentials to continue.', 'bw' ); ?></p>

                                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                        <label for="bw_supabase_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?> <span class="required">*</span></label>
                                        <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="bw_supabase_email" autocomplete="email" required />
                                    </p>
                                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                        <label for="bw_supabase_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?> <span class="required">*</span></label>
                                        <input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="bw_supabase_password" autocomplete="current-password" required />
                                    </p>

                                    <div class="bw-account-login__error" role="alert" aria-live="polite" hidden></div>
                                    <div class="bw-account-login__success" role="status" aria-live="polite" hidden></div>

                                    <p class="form-row bw-account-login__actions">
                                        <button type="submit" class="woocommerce-button button bw-account-login__submit" data-bw-supabase-submit><?php esc_html_e( 'Log In', 'woocommerce' ); ?></button>
                                    </p>

                                    <p class="bw-account-login__back-to-login">
                                        <button type="button" class="bw-account-login__back-link" data-bw-auth-tab="lostpassword"><?php esc_html_e( 'Lost your password?', 'woocommerce' ); ?></button>
                                    </p>
                                </form>
                            </div>

                            <div class="bw-account-auth__panel <?php echo 'register' === $active_tab ? 'is-active is-visible' : ''; ?>" data-bw-auth-panel="register">
                                <form class="bw-account-login__form bw-account-login__form--supabase" data-bw-supabase-form data-bw-supabase-action="register">
                                    <p class="bw-account-login__note"><?php esc_html_e( 'Create your Supabase account.', 'bw' ); ?></p>

                                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                        <label for="bw_supabase_register_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?> <span class="required">*</span></label>
                                        <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="bw_supabase_register_email" autocomplete="email" required />
                                    </p>
                                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                        <label for="bw_supabase_register_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?> <span class="required">*</span></label>
                                        <input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="bw_supabase_register_password" autocomplete="new-password" required />
                                    </p>

                                    <div class="bw-account-login__error" role="alert" aria-live="polite" hidden></div>
                                    <div class="bw-account-login__success" role="status" aria-live="polite" hidden></div>

                                    <p class="form-row bw-account-login__actions">
                                        <button type="submit" class="woocommerce-button button bw-account-login__submit" data-bw-supabase-submit><?php esc_html_e( 'Register', 'woocommerce' ); ?></button>
                                    </p>
                                </form>
                            </div>

                            <div class="bw-account-auth__panel" data-bw-auth-panel="lostpassword">
                                <form class="bw-account-login__form bw-account-login__form--supabase" data-bw-supabase-form data-bw-supabase-action="recover">
                                    <p class="bw-account-login__note"><?php esc_html_e( 'Enter your email to receive a password reset link.', 'bw' ); ?></p>

                                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                        <label for="bw_supabase_recover_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?> <span class="required">*</span></label>
                                        <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="bw_supabase_recover_email" autocomplete="email" required />
                                    </p>

                                    <div class="bw-account-login__error" role="alert" aria-live="polite" hidden></div>
                                    <div class="bw-account-login__success" role="status" aria-live="polite" hidden></div>

                                    <p class="form-row bw-account-login__actions">
                                        <button type="submit" class="woocommerce-button button bw-account-login__submit" data-bw-supabase-submit><?php esc_html_e( 'Send reset link', 'bw' ); ?></button>
                                    </p>

                                    <p class="bw-account-login__back-to-login">
                                        <button type="button" class="bw-account-login__back-link" data-bw-auth-tab="login">← <?php esc_html_e( 'Back to login', 'woocommerce' ); ?></button>
                                    </p>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="bw-account-auth" data-bw-default-tab="<?php echo esc_attr( $active_tab ); ?>">
                        <div class="bw-account-auth__tabs <?php echo $registration_enabled ? 'bw-account-auth__tabs--dual' : 'bw-account-auth__tabs--single'; ?>">
                            <button class="bw-account-auth__tab <?php echo 'login' === $active_tab ? 'is-active' : ''; ?>" type="button" data-bw-auth-tab="login"><?php esc_html_e( 'Login', 'woocommerce' ); ?></button>
                            <?php if ( $registration_enabled ) : ?>
                                <button class="bw-account-auth__tab <?php echo 'register' === $active_tab ? 'is-active' : ''; ?>" type="button" data-bw-auth-tab="register"><?php esc_html_e( 'Register', 'woocommerce' ); ?></button>
                            <?php endif; ?>
                        </div>

                        <div class="bw-account-auth__panels">
                            <div class="bw-account-auth__panel <?php echo 'login' === $active_tab ? 'is-active is-visible' : ''; ?>" data-bw-auth-panel="login">
                                <?php if ( $has_google || $has_facebook ) : ?>
                                    <div class="bw-account-login__social-row">
                                        <?php if ( $has_google ) : ?>
                                            <a class="bw-account-login__social-button bw-account-login__social-button--google" href="<?php echo esc_url( $google_url ); ?>" data-social-provider="google">
                                                <span class="bw-account-login__social-icon" aria-hidden="true">
                                                    <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg" role="presentation">
                                                        <path d="M17.64 9.2c0-.64-.06-1.25-.17-1.84H9v3.48h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.92c1.71-1.57 2.68-3.88 2.68-6.62Z" fill="#4285F4"/>
                                                        <path d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.92-2.26c-.81.54-1.85.86-3.04.86a5.26 5.26 0 0 1-4.99-3.68H1.02v2.32A9 9 0 0 0 9 18Z" fill="#34A853"/>
                                                        <path d="M4.01 10.74A5.26 5.26 0 0 1 3.73 9c0-.6.1-1.18.27-1.74V4.94H1.02A9 9 0 0 0 0 9c0 1.46.35 2.83.97 4.06l3.04-2.32Z" fill="#FBBC05"/>
                                                        <path d="M9 3.48c1.32 0 2.51.45 3.44 1.33l2.58-2.58A9 9 0 0 0 1 4.94l3.01 2.32A5.26 5.26 0 0 1 9 3.48Z" fill="#EA4335"/>
                                                    </svg>
                                                </span>
                                                <span class="bw-account-login__social-text"><?php esc_html_e( 'Login with Google', 'woocommerce' ); ?></span>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ( $has_facebook ) : ?>
                                            <a class="bw-account-login__social-button bw-account-login__social-button--facebook" href="<?php echo esc_url( $facebook_url ); ?>" data-social-provider="facebook">
                                                <span class="bw-account-login__social-icon" aria-hidden="true">
                                                    <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg" role="presentation">
                                                        <path d="M15.84 0H2.16A2.16 2.16 0 0 0 0 2.16v13.68A2.16 2.16 0 0 0 2.16 18h7.36v-6.66H7.5V8.78h2.02V6.64c0-2 1.22-3.1 3-3.1.86 0 1.6.06 1.82.09v2.1h-1.25c-.98 0-1.17.47-1.17 1.15v1.5h2.34l-.3 2.56h-2.04V18h3.98A2.16 2.16 0 0 0 18 15.84V2.16A2.16 2.16 0 0 0 15.84 0Z" fill="#fff"/>
                                                    </svg>
                                                </span>
                                                <span class="bw-account-login__social-text"><?php esc_html_e( 'Login with Facebook', 'woocommerce' ); ?></span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <form class="woocommerce-form woocommerce-form-login login bw-account-login__form" method="post" action="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
                                    <?php do_action( 'woocommerce_login_form_start' ); ?>

                                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                        <label for="username"><?php esc_html_e( 'Username or email address', 'woocommerce' ); ?> <span class="required">*</span></label>
                                        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo isset( $_POST['username'] ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" />
                                    </p>
                                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                        <label for="password"><?php esc_html_e( 'Password', 'woocommerce' ); ?> <span class="required">*</span></label>
                                        <input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" />
                                    </p>

                                    <?php do_action( 'woocommerce_login_form' ); ?>

                                    <p class="form-row bw-account-login__controls">
                                        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme bw-account-login__remember">
                                            <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span><?php esc_html_e( 'Remember me', 'woocommerce' ); ?></span>
                                        </label>
                                        <button type="button" class="bw-account-login__lost-password" data-bw-auth-tab="lostpassword"><?php esc_html_e( 'Lost your password?', 'woocommerce' ); ?></button>
                                    </p>

                                    <p class="form-row bw-account-login__actions">
                                        <input type="hidden" name="redirect" value="<?php echo esc_url( apply_filters( 'woocommerce_login_redirect', wc_get_page_permalink( 'myaccount' ) ) ); ?>" />
                                        <?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
                                        <button type="submit" class="woocommerce-button button bw-account-login__submit" name="login" value="<?php esc_attr_e( 'Log in', 'woocommerce' ); ?>"><?php esc_html_e( 'Log In', 'woocommerce' ); ?></button>
                                    </p>

                                    <?php do_action( 'woocommerce_login_form_end' ); ?>
                                </form>

                                <?php if ( $passwordless_url ) : ?>
                                    <a class="bw-account-login__passwordless" href="<?php echo esc_url( $passwordless_url ); ?>" data-login-method="passwordless"><?php esc_html_e( 'Log in Without Password', 'woocommerce' ); ?></a>
                                <?php endif; ?>
                            </div>

                            <?php if ( $registration_enabled ) : ?>
                                <div class="bw-account-auth__panel <?php echo 'register' === $active_tab ? 'is-active is-visible' : ''; ?>" data-bw-auth-panel="register">
                                    <form method="post" class="woocommerce-form woocommerce-form-register register bw-account-login__form" action="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" <?php do_action( 'woocommerce_register_form_tag' ); ?>>
                                        <?php do_action( 'woocommerce_register_form_start' ); ?>

                                        <?php if ( ! $generate_username ) : ?>
                                            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                                <label for="reg_username"><?php esc_html_e( 'Username', 'woocommerce' ); ?> <span class="required">*</span></label>
                                                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo isset( $_POST['username'] ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" />
                                            </p>
                                        <?php endif; ?>

                                        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                            <label for="reg_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?> <span class="required">*</span></label>
                                            <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo isset( $_POST['email'] ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" />
                                        </p>

                                        <?php if ( ! $generate_password ) : ?>
                                            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                                <label for="reg_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?> <span class="required">*</span></label>
                                                <input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" />
                                            </p>
                                        <?php else : ?>
                                            <p class="bw-account-login__note"><?php esc_html_e( 'A link to set a new password will be sent to your email address.', 'woocommerce' ); ?></p>
                                        <?php endif; ?>

                                        <?php do_action( 'woocommerce_register_form' ); ?>

                                        <?php do_action( 'woocommerce_register_form_end' ); ?>

                                        <p class="woocommerce-form-row form-row bw-account-login__actions">
                                            <?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
                                            <button type="submit" class="woocommerce-Button woocommerce-button button bw-account-login__submit" name="register" value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>"><?php esc_html_e( 'Register', 'woocommerce' ); ?></button>
                                        </p>
                                    </form>
                                </div>
                            <?php endif; ?>

                            <div class="bw-account-auth__panel <?php echo 'lostpassword' === $active_tab ? 'is-active is-visible' : ''; ?>" data-bw-auth-panel="lostpassword">
                                <form method="post" class="woocommerce-ResetPassword lost_reset_password bw-account-login__form" action="<?php echo esc_url( wc_lostpassword_url() ); ?>">
                                    <p class="bw-account-login__note"><?php esc_html_e( 'Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.', 'woocommerce' ); ?></p>

                                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                        <label for="user_login"><?php esc_html_e( 'Username or email', 'woocommerce' ); ?> <span class="required">*</span></label>
                                        <input class="woocommerce-Input woocommerce-Input--text input-text" type="text" name="user_login" id="user_login" autocomplete="username" />
                                    </p>

                                    <?php do_action( 'woocommerce_lostpassword_form' ); ?>

                                    <p class="woocommerce-form-row form-row bw-account-login__actions">
                                        <input type="hidden" name="wc_reset_password" value="true" />
                                        <?php wp_nonce_field( 'lost_password', 'woocommerce-lost-password-nonce' ); ?>
                                        <button type="submit" class="woocommerce-Button button bw-account-login__submit" value="<?php esc_attr_e( 'Reset password', 'woocommerce' ); ?>"><?php esc_html_e( 'Reset password', 'woocommerce' ); ?></button>
                                    </p>

                                    <p class="bw-account-login__back-to-login">
                                        <button type="button" class="bw-account-login__back-link" data-bw-auth-tab="login">← <?php esc_html_e( 'Back to login', 'woocommerce' ); ?></button>
                                    </p>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ( $description ) : ?>
                    <div class="bw-account-login__description"><?php echo wpautop( wp_kses_post( $description ) ); ?></div>
                <?php endif; ?>

                <div class="bw-account-login__back">
                    <a href="<?php echo esc_url( $back_url ); ?>"><?php echo esc_html( $back_text ); ?> <span aria-hidden="true">→</span></a>
                </div>

                <?php do_action( 'woocommerce_after_customer_login_form' ); ?>
            </div>
        </div>
    </div>
</div>
