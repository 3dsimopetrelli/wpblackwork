<?php
/**
 * Login form.
 *
 * Overrides WooCommerce global form login to provide a custom "expired session"
 * CTA only when rendered on the order-received endpoint.
 *
 * @package WooCommerce\Templates
 * @version 9.2.0
 *
 * @var string $message  Login message.
 * @var string $redirect Redirect URL after login.
 * @var bool   $hidden   Whether the form should be hidden.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_user_logged_in() ) {
	return;
}

$is_order_received_login_gate = function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' );

if ( $is_order_received_login_gate ) :
	?>
	<section class="bw-verify-email-cta" aria-label="<?php esc_attr_e( 'Login required', 'wpblackwork' ); ?>">
		<p class="bw-verify-email-cta__actions">
			<a class="elementor-button-link elementor-button" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
				<span class="elementor-button-content-wrapper">
					<span class="elementor-button-text"><?php esc_html_e( 'Please log in to your account to view this order.', 'wpblackwork' ); ?></span>
				</span>
			</a>
		</p>

		<p class="bw-verify-email-cta__lead">
			<?php esc_html_e( 'Click the button to continue.', 'wpblackwork' ); ?>
		</p>
		<p class="bw-verify-email-cta__lead bw-verify-email-cta__lead--secondary">
			<?php esc_html_e( 'You will be redirected to the login page where you can sign in to your account.', 'wpblackwork' ); ?>
		</p>

		<p class="bw-verify-email-cta__footnote">
			<?php esc_html_e( 'You can log in by entering your email address since your account is already registered.', 'wpblackwork' ); ?>
		</p>
	</section>
	<?php
	return;
endif;
?>
<form class="woocommerce-form woocommerce-form-login login" method="post" <?php echo ( $hidden ) ? 'style="display:none;"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<?php do_action( 'woocommerce_login_form_start' ); ?>

	<?php echo ( $message ) ? wpautop( wptexturize( $message ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<p class="form-row form-row-first">
		<label for="username"><?php esc_html_e( 'Username or email', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
		<input type="text" class="input-text" name="username" id="username" autocomplete="username" required aria-required="true" />
	</p>
	<p class="form-row form-row-last">
		<label for="password"><?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
		<input class="input-text woocommerce-Input" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" />
	</p>
	<div class="clear"></div>

	<?php do_action( 'woocommerce_login_form' ); ?>

	<p class="form-row">
		<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
			<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span><?php esc_html_e( 'Remember me', 'woocommerce' ); ?></span>
		</label>
		<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
		<input type="hidden" name="redirect" value="<?php echo esc_url( $redirect ); ?>" />
		<button type="submit" class="woocommerce-button button woocommerce-form-login__submit<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="login" value="<?php esc_attr_e( 'Login', 'woocommerce' ); ?>"><?php esc_html_e( 'Login', 'woocommerce' ); ?></button>
	</p>
	<p class="lost_password">
		<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'woocommerce' ); ?></a>
	</p>

	<div class="clear"></div>

	<?php do_action( 'woocommerce_login_form_end' ); ?>

</form>
