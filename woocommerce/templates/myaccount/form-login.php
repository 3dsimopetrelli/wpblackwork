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

$login_image = get_option( 'bw_account_login_image', '' );
$logo        = get_option( 'bw_account_logo', '' );
$facebook    = (int) get_option( 'bw_account_facebook', 0 );
$google      = (int) get_option( 'bw_account_google', 0 );
$description = get_option( 'bw_account_description', '' );
$back_text   = get_option( 'bw_account_back_text', 'go back to store' );
$back_url    = get_option( 'bw_account_back_url', '' );
$back_url    = $back_url ? $back_url : home_url( '/' );
?>

<?php do_action( 'woocommerce_before_customer_login_form' ); ?>

<div class="bw-account-login">
    <div class="bw-account-login__media" <?php if ( $login_image ) : ?>style="background-image: url('<?php echo esc_url( $login_image ); ?>');"<?php endif; ?>></div>
    <div class="bw-account-login__content-wrapper">
        <div class="bw-account-login__content">
            <?php woocommerce_output_all_notices(); ?>

            <?php if ( $logo ) : ?>
                <div class="bw-account-login__logo">
                    <img src="<?php echo esc_url( $logo ); ?>" alt="<?php esc_attr_e( 'Account logo', 'bw' ); ?>" />
                </div>
            <?php endif; ?>

            <div class="bw-account-login__social">
                <span class="bw-account-login__social-label"><?php esc_html_e( 'Log in with', 'woocommerce' ); ?></span>
                <div class="bw-account-login__social-links">
                    <?php if ( $facebook ) : ?>
                        <a class="bw-account-login__social-button bw-account-login__social-button--facebook" href="#" data-social-provider="facebook"><?php esc_html_e( 'Facebook', 'woocommerce' ); ?></a>
                    <?php endif; ?>
                    <?php if ( $google ) : ?>
                        <a class="bw-account-login__social-button bw-account-login__social-button--google" href="#" data-social-provider="google"><?php esc_html_e( 'Google', 'woocommerce' ); ?></a>
                    <?php endif; ?>
                </div>
            </div>

            <form class="woocommerce-form woocommerce-form-login login bw-account-login__form" method="post">
                <?php do_action( 'woocommerce_login_form_start' ); ?>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" placeholder="<?php esc_attr_e( 'your email', 'woocommerce' ); ?>" value="<?php echo isset( $_POST['username'] ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" />
                </p>
                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" placeholder="<?php esc_attr_e( 'your password', 'woocommerce' ); ?>" />
                </p>

                <?php do_action( 'woocommerce_login_form' ); ?>

                <p class="form-row bw-account-login__controls">
                    <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme bw-account-login__remember">
                        <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span><?php esc_html_e( 'Remember me', 'woocommerce' ); ?></span>
                    </label>
                    <a class="bw-account-login__lost-password" href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'woocommerce' ); ?></a>
                </p>

                <p class="form-row">
                    <input type="hidden" name="redirect" value="<?php echo esc_url( apply_filters( 'woocommerce_login_redirect', wc_get_page_permalink( 'myaccount' ) ) ); ?>" />
                    <?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
                    <button type="submit" class="woocommerce-button button bw-account-login__submit" name="login" value="<?php esc_attr_e( 'Log in', 'woocommerce' ); ?>"><?php esc_html_e( 'Log In', 'woocommerce' ); ?></button>
                </p>

                <?php do_action( 'woocommerce_login_form_end' ); ?>
            </form>

            <a class="bw-account-login__passwordless" href="#" data-login-method="passwordless"><?php esc_html_e( 'Log in Without Password', 'woocommerce' ); ?></a>

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
