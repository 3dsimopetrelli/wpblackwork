<?php
/**
 * Login Form
 *
 * This template supports two login providers:
 * - 'wordpress': Standard WooCommerce login form with optional social login
 * - 'supabase': Custom Supabase auth (magic link, OTP, OAuth)
 *
 * @package WooCommerce/Templates
 * @version 8.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Determine which login provider to use
$login_provider = get_option( 'bw_account_login_provider', 'wordpress' );

// Common settings (used by both providers)
$login_image         = get_option( 'bw_account_login_image', '' );
$login_image_id      = (int) get_option( 'bw_account_login_image_id', 0 );
$logo                = get_option( 'bw_account_logo', '' );
$logo_id             = (int) get_option( 'bw_account_logo_id', 0 );
$logo_width          = (int) get_option( 'bw_account_logo_width', 180 );
$logo_padding_top    = (int) get_option( 'bw_account_logo_padding_top', 0 );
$logo_padding_bottom = (int) get_option( 'bw_account_logo_padding_bottom', 30 );
$legacy_login_title  = get_option( 'bw_account_login_title', 'Log in to Blackwork' );
$legacy_login_subtitle = get_option(
    'bw_account_login_subtitle',
    "If you are new, we will create your account automatically.\nNew or returning, this works the same."
);
$login_title_supabase = get_option( 'bw_account_login_title_supabase', $legacy_login_title );
$login_subtitle_supabase = get_option( 'bw_account_login_subtitle_supabase', $legacy_login_subtitle );
$login_title_wordpress = get_option( 'bw_account_login_title_wordpress', $legacy_login_title );
$login_subtitle_wordpress = get_option( 'bw_account_login_subtitle_wordpress', $legacy_login_subtitle );
$login_title = 'wordpress' === $login_provider ? $login_title_wordpress : $login_title_supabase;
$login_subtitle = 'wordpress' === $login_provider ? $login_subtitle_wordpress : $login_subtitle_supabase;

// WordPress provider settings
$wp_facebook_enabled = (int) get_option( 'bw_account_facebook', 0 );
$wp_google_enabled   = (int) get_option( 'bw_account_google', 0 );
$wp_registration_enabled = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
$wp_generate_username = 'yes' === get_option( 'woocommerce_registration_generate_username' );
$wp_generate_password = 'yes' === get_option( 'woocommerce_registration_generate_password' );
$wp_default_screen = 'login';

if ( 'wordpress' === $login_provider ) {
    if ( isset( $_GET['action'] ) && 'register' === sanitize_key( wp_unslash( $_GET['action'] ) ) && $wp_registration_enabled ) {
        $wp_default_screen = 'register';
    }

    if ( isset( $_POST['register'] ) && $wp_registration_enabled ) {
        $wp_default_screen = 'register';
    }

    if ( isset( $_POST['bw_wp_lost_password_submit'] ) ) {
        $wp_default_screen = 'lost-password';
        $lost_password_nonce = isset( $_POST['bw_lost_password_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_lost_password_nonce'] ) ) : '';
        $lost_password_login = isset( $_POST['user_login'] ) ? sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) : '';

        if ( ! wp_verify_nonce( $lost_password_nonce, 'bw_lost_password_action' ) ) {
            wc_add_notice( __( 'Security check failed. Please try again.', 'bw' ), 'error' );
        } elseif ( '' === $lost_password_login ) {
            wc_add_notice( __( 'Please enter a username or email address.', 'bw' ), 'error' );
        } else {
            $reset_result = retrieve_password( $lost_password_login );

            if ( is_wp_error( $reset_result ) ) {
                $error_messages = $reset_result->get_error_messages();
                if ( ! empty( $error_messages ) ) {
                    foreach ( $error_messages as $error_message ) {
                        wc_add_notice( $error_message, 'error' );
                    }
                } else {
                    wc_add_notice( __( 'Unable to process your request. Please try again.', 'bw' ), 'error' );
                }
            } else {
                wc_add_notice( __( 'Check your email for the confirmation link.', 'bw' ), 'success' );
            }
        }
    }
}

// Supabase provider settings (only loaded if supabase)
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

$has_cover = ! empty( $login_image_url );
$post_checkout_gate = isset( $_GET['bw_post_checkout'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['bw_post_checkout'] ) );
$wrapper_class = 'bw-account-login-page bw-full-section' . ( $has_cover ? ' bw-account-login-page--has-cover' : '' );
$wrapper_class .= ' bw-account-login-page--provider-' . sanitize_html_class( $login_provider );
if ( $post_checkout_gate ) {
    $wrapper_class .= ' bw-account-login-page--post-checkout';
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

<div class="<?php echo esc_attr( $wrapper_class ); ?>">
    <div class="bw-account-login">
        <div class="bw-account-login__media" <?php if ( $login_image_url ) : ?>style="--bw-login-cover: url('<?php echo esc_url( $login_image_url ); ?>');"<?php endif; ?>></div>
        <div class="bw-account-login__content-wrapper">
            <div class="bw-account-login__content">
                <?php
                $wp_notices_html = '';
                $supabase_notices_html = '';
                if ( 'wordpress' === $login_provider ) {
                    // WooCommerce prints notices in this hook by default; suppress top rendering for WordPress provider.
                    $restore_wc_output_notices = false;
                    $restore_wc_print_notices = false;
                    if ( has_action( 'woocommerce_before_customer_login_form', 'woocommerce_output_all_notices' ) ) {
                        remove_action( 'woocommerce_before_customer_login_form', 'woocommerce_output_all_notices', 10 );
                        $restore_wc_output_notices = true;
                    }
                    if ( has_action( 'woocommerce_before_customer_login_form', 'wc_print_notices' ) ) {
                        remove_action( 'woocommerce_before_customer_login_form', 'wc_print_notices', 10 );
                        $restore_wc_print_notices = true;
                    }

                    do_action( 'woocommerce_before_customer_login_form' );

                    if ( $restore_wc_output_notices ) {
                        add_action( 'woocommerce_before_customer_login_form', 'woocommerce_output_all_notices', 10 );
                    }
                    if ( $restore_wc_print_notices ) {
                        add_action( 'woocommerce_before_customer_login_form', 'wc_print_notices', 10 );
                    }

                    ob_start();
                    wc_print_notices();
                    $wp_notices_html = trim( ob_get_clean() );
                } else {
                    // Keep Supabase notices in a controlled position under intro text.
                    $restore_wc_output_notices = false;
                    $restore_wc_print_notices = false;
                    if ( has_action( 'woocommerce_before_customer_login_form', 'woocommerce_output_all_notices' ) ) {
                        remove_action( 'woocommerce_before_customer_login_form', 'woocommerce_output_all_notices', 10 );
                        $restore_wc_output_notices = true;
                    }
                    if ( has_action( 'woocommerce_before_customer_login_form', 'wc_print_notices' ) ) {
                        remove_action( 'woocommerce_before_customer_login_form', 'wc_print_notices', 10 );
                        $restore_wc_print_notices = true;
                    }

                    do_action( 'woocommerce_before_customer_login_form' );

                    if ( $restore_wc_output_notices ) {
                        add_action( 'woocommerce_before_customer_login_form', 'woocommerce_output_all_notices', 10 );
                    }
                    if ( $restore_wc_print_notices ) {
                        add_action( 'woocommerce_before_customer_login_form', 'wc_print_notices', 10 );
                    }

                    ob_start();
                    wc_print_notices();
                    $supabase_notices_html = trim( ob_get_clean() );
                }
                ?>
                <?php
                $email_confirmed = isset( $_GET['bw_email_confirmed'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['bw_email_confirmed'] ) );
                $invite_error_code = isset( $_GET['bw_invite_error'] ) ? sanitize_key( wp_unslash( $_GET['bw_invite_error'] ) ) : '';
                $invite_error_desc = isset( $_GET['bw_invite_error_description'] ) ? sanitize_text_field( wp_unslash( $_GET['bw_invite_error_description'] ) ) : '';
                $post_checkout_email = isset( $_GET['bw_invite_email'] ) ? sanitize_email( wp_unslash( $_GET['bw_invite_email'] ) ) : '';
                ?>

                <?php if ( $logo_url ) : ?>
                    <div class="bw-account-login__logo" style="<?php echo esc_attr( $logo_styles ); ?>">
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="bw-account-login__logo-link" aria-label="<?php esc_attr_e( 'Go to homepage', 'bw' ); ?>">
                            <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'Account logo', 'bw' ); ?>" />
                        </a>
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

                <?php if ( 'supabase' === $login_provider && $post_checkout_gate ) : ?>
                    <div class="bw-account-login__intro-notices bw-account-login__form-notices bw-account-login__intro-notices--post-checkout">
                        <div class="woocommerce-info" role="status">
                            <?php esc_html_e( 'Check your email and click the invite link to create your password.', 'bw' ); ?>
                        </div>
                        <p>
                            <?php esc_html_e( 'Didn\'t receive it? Request a new invite email here.', 'bw' ); ?>
                        </p>
                        <p class="bw-account-login__actions">
                            <input
                                class="woocommerce-Input woocommerce-Input--text input-text bw-account-login__resend-email"
                                type="email"
                                data-bw-resend-email
                                value="<?php echo esc_attr( $post_checkout_email ); ?>"
                                autocomplete="email"
                                placeholder="<?php esc_attr_e( 'Email address', 'bw' ); ?>"
                            />
                        </p>
                        <p class="bw-account-login__actions">
                            <button type="button" class="woocommerce-button button bw-account-login__resend-invite" data-bw-resend-invite>
                                <?php esc_html_e( 'Resend invite email', 'bw' ); ?>
                            </button>
                        </p>
                        <p class="bw-account-set-password__error" role="alert" hidden></p>
                        <p class="bw-account-set-password__notice" data-bw-resend-notice hidden></p>
                    </div>
                <?php endif; ?>

                <?php if ( 'supabase' === $login_provider && ( $email_confirmed || $invite_error_code || $supabase_notices_html ) ) : ?>
                    <div class="bw-account-login__intro-notices bw-account-login__form-notices">
                        <?php if ( $email_confirmed ) : ?>
                            <div class="woocommerce-message" role="status">
                                <?php esc_html_e( 'Email confirmed. Please log in.', 'bw' ); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( $invite_error_code ) : ?>
                            <?php
                            $invite_error_message = __( 'The invite link is invalid or expired. Please request a new invite.', 'bw' );
                            if ( 'otp_expired' === $invite_error_code ) {
                                $invite_error_message = __( 'This invite link has expired. Please request a new invite.', 'bw' );
                            }
                            if ( $invite_error_desc ) {
                                $invite_error_message = $invite_error_desc;
                            }
                            ?>
                            <div class="woocommerce-error" role="alert">
                                <?php echo esc_html( $invite_error_message ); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( $supabase_notices_html ) : ?>
                            <?php echo wp_kses_post( $supabase_notices_html ); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ( 'wordpress' === $login_provider ) : ?>
                    <!-- WordPress/WooCommerce Login Provider -->
                    <div class="bw-account-auth bw-account-auth--wordpress" data-bw-wp-default-screen="<?php echo esc_attr( $wp_default_screen ); ?>">
                        <div class="bw-account-auth__tabs bw-account-auth__tabs--wordpress">
                            <button type="button" class="bw-account-auth__tab<?php echo 'login' === $wp_default_screen ? ' is-active' : ''; ?>" data-bw-wp-auth-tab="login">
                                <?php esc_html_e( 'Login', 'bw' ); ?>
                            </button>
                            <?php if ( $wp_registration_enabled ) : ?>
                                <button type="button" class="bw-account-auth__tab<?php echo 'register' === $wp_default_screen ? ' is-active' : ''; ?>" data-bw-wp-auth-tab="register">
                                    <?php esc_html_e( 'Register', 'bw' ); ?>
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="bw-account-auth__panels bw-account-auth__panels--wordpress">
                            <div class="bw-auth-screen bw-auth-screen--wp-login<?php echo 'login' === $wp_default_screen ? ' is-active is-visible' : ''; ?>" data-bw-wp-screen="login">
                                <form class="woocommerce-form woocommerce-form-login login bw-account-login__form bw-account-login__form--wordpress" method="post">

                                    <?php do_action( 'woocommerce_login_form_start' ); ?>

                                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                        <label for="username"><?php esc_html_e( 'Username or email address', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
                                        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required />
                                    </p>
                                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                        <label for="password"><?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
                                        <input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" required />
                                    </p>

                                    <?php do_action( 'woocommerce_login_form' ); ?>

                                    <p class="form-row bw-account-login__remember">
                                        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
                                            <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
                                            <span><?php esc_html_e( 'Remember me', 'woocommerce' ); ?></span>
                                        </label>
                                    </p>

                                    <p class="form-row bw-account-login__actions">
                                        <?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
                                        <button type="submit" class="woocommerce-button button woocommerce-form-login__submit bw-account-login__submit" name="login" value="<?php esc_attr_e( 'Log in', 'woocommerce' ); ?>"><?php esc_html_e( 'Log in', 'woocommerce' ); ?></button>
                                    </p>
                                    <?php if ( $wp_notices_html && 'login' === $wp_default_screen ) : ?>
                                        <div class="bw-account-login__form-notices">
                                            <?php echo wp_kses_post( $wp_notices_html ); ?>
                                        </div>
                                    <?php endif; ?>
                                    <p class="woocommerce-LostPassword lost_password bw-account-login__lost-password-wrap">
                                        <button type="button" class="bw-account-login__lost-password-toggle bw-account-login__lost-password" data-bw-wp-go="lost-password"><?php esc_html_e( 'Lost your password?', 'woocommerce' ); ?></button>
                                    </p>

                                    <?php do_action( 'woocommerce_login_form_end' ); ?>
                                </form>

                                <?php if ( $wp_google_enabled || $wp_facebook_enabled ) : ?>
                                    <div class="bw-account-login__divider">
                                        <span><?php esc_html_e( 'or', 'bw' ); ?></span>
                                    </div>
                                    <div class="bw-account-login__oauth bw-account-login__oauth--wordpress">
                                        <?php if ( $wp_google_enabled && function_exists( 'bw_mew_get_social_login_url' ) ) : ?>
                                            <a href="<?php echo esc_url( bw_mew_get_social_login_url( 'google' ) ); ?>" class="woocommerce-button button bw-account-login__oauth-button bw-account-login__oauth-button--google">
                                                <?php esc_html_e( 'Continue with Google', 'bw' ); ?>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ( $wp_facebook_enabled && function_exists( 'bw_mew_get_social_login_url' ) ) : ?>
                                            <a href="<?php echo esc_url( bw_mew_get_social_login_url( 'facebook' ) ); ?>" class="woocommerce-button button bw-account-login__oauth-button bw-account-login__oauth-button--facebook">
                                                <?php esc_html_e( 'Continue with Facebook', 'bw' ); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ( $wp_registration_enabled ) : ?>
                                <div class="bw-auth-screen bw-auth-screen--wp-register<?php echo 'register' === $wp_default_screen ? ' is-active is-visible' : ''; ?>" data-bw-wp-screen="register">
                                    <form method="post" class="woocommerce-form woocommerce-form-register register bw-account-login__form bw-account-login__form--wordpress" <?php do_action( 'woocommerce_register_form_tag' ); ?>>
                                        <?php do_action( 'woocommerce_register_form_start' ); ?>

                                        <?php if ( ! $wp_generate_username ) : ?>
                                            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                                <label for="reg_username"><?php esc_html_e( 'Username', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
                                                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" />
                                            </p>
                                        <?php endif; ?>

                                        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                            <label for="reg_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
                                            <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" />
                                        </p>

                                        <?php if ( ! $wp_generate_password ) : ?>
                                            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                                <label for="reg_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
                                                <input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" />
                                            </p>
                                        <?php else : ?>
                                            <p><?php esc_html_e( 'A link to set a new password will be sent to your email address.', 'woocommerce' ); ?></p>
                                        <?php endif; ?>

                                        <?php do_action( 'woocommerce_register_form' ); ?>
                                        <?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
                                        <p class="woocommerce-form-row form-row bw-account-login__actions">
                                            <button type="submit" class="woocommerce-Button woocommerce-button button bw-account-login__submit" name="register" value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>"><?php esc_html_e( 'Register', 'woocommerce' ); ?></button>
                                        </p>
                                        <?php if ( $wp_notices_html && 'register' === $wp_default_screen ) : ?>
                                            <div class="bw-account-login__form-notices">
                                                <?php echo wp_kses_post( $wp_notices_html ); ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php do_action( 'woocommerce_register_form_end' ); ?>
                                    </form>
                                </div>
                            <?php endif; ?>

                            <div class="bw-auth-screen bw-auth-screen--wp-lost-password<?php echo 'lost-password' === $wp_default_screen ? ' is-active is-visible' : ''; ?>" data-bw-wp-screen="lost-password">
                                <form method="post" class="woocommerce-ResetPassword lost_reset_password bw-account-login__form bw-account-login__form--wordpress">
                                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                        <label for="user_login_lost"><?php esc_html_e( 'Username or email address', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
                                        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="user_login" id="user_login_lost" autocomplete="username email" value="<?php echo ( ! empty( $_POST['user_login'] ) ) ? esc_attr( wp_unslash( $_POST['user_login'] ) ) : ''; ?>" />
                                    </p>
                                    <?php wp_nonce_field( 'bw_lost_password_action', 'bw_lost_password_nonce' ); ?>
                                    <p class="woocommerce-form-row form-row bw-account-login__actions">
                                        <button type="submit" class="woocommerce-button button bw-account-login__submit" name="bw_wp_lost_password_submit" value="1"><?php esc_html_e( 'Reset password', 'woocommerce' ); ?></button>
                                    </p>
                                    <?php if ( $wp_notices_html && 'lost-password' === $wp_default_screen ) : ?>
                                        <div class="bw-account-login__form-notices">
                                            <?php echo wp_kses_post( $wp_notices_html ); ?>
                                        </div>
                                    <?php endif; ?>
                                </form>
                                <p class="bw-account-login__back-to-login">
                                    <button type="button" class="bw-account-login__back-link" data-bw-wp-go="login">← <?php esc_html_e( 'Back to Login', 'bw' ); ?></button>
                                </p>
                            </div>
                        </div>
                    </div>

                <?php else : ?>
                    <!-- Supabase Login Provider -->
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
                                        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bw-account-login__field">
                                            <label for="bw_supabase_login_email"><?php esc_html_e( 'Email', 'bw' ); ?> <span class="required">*</span></label>
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
                <?php endif; ?>

                <?php do_action( 'woocommerce_after_customer_login_form' ); ?>
            </div>
        </div>
    </div>
</div>
