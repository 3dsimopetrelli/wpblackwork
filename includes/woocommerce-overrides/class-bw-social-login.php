<?php
/**
 * Social Login Handler for Facebook and Google OAuth.
 *
 * @package BW_Elementor_Widgets
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BW_Social_Login
 *
 * Handles OAuth 2.0 authentication flow for Facebook and Google login.
 */
class BW_Social_Login {

	/**
	 * Allowed social providers.
	 *
	 * @var array
	 */
	private static $allowed_providers = [ 'facebook', 'google' ];

	/**
	 * Settings cache.
	 *
	 * @var array|null
	 */
	private static $settings_cache = null;

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'template_redirect', [ __CLASS__, 'handle_requests' ], 5 );
	}

	/**
	 * Get social login settings with caching.
	 *
	 * @return array Settings array.
	 */
	public static function get_settings() {
		if ( null !== self::$settings_cache ) {
			return self::$settings_cache;
		}

		self::$settings_cache = [
			'facebook' => [
				'enabled'    => (int) get_option( 'bw_account_facebook', 0 ),
				'app_id'     => get_option( 'bw_account_facebook_app_id', '' ),
				'app_secret' => get_option( 'bw_account_facebook_app_secret', '' ),
			],
			'google'   => [
				'enabled'       => (int) get_option( 'bw_account_google', 0 ),
				'client_id'     => get_option( 'bw_account_google_client_id', '' ),
				'client_secret' => get_option( 'bw_account_google_client_secret', '' ),
			],
		];

		return self::$settings_cache;
	}

	/**
	 * Clear settings cache (useful after save in admin).
	 */
	public static function clear_cache() {
		self::$settings_cache = null;
	}

	/**
	 * Check if a provider is valid.
	 *
	 * @param string $provider Provider key.
	 * @return bool
	 */
	private static function is_valid_provider( $provider ) {
		return in_array( $provider, self::$allowed_providers, true );
	}

	/**
	 * Log debug message if WP_DEBUG is enabled.
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 */
	private static function debug_log( $message, $context = [] ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			$context_str = ! empty( $context ) ? ' | ' . wp_json_encode( $context ) : '';
			error_log( '[BW Social Login] ' . $message . $context_str );
		}
	}

	/**
	 * Check rate limiting for social login attempts.
	 *
	 * @param string $ip IP address.
	 * @return bool True if allowed, false if rate limited.
	 */
	private static function check_rate_limit( $ip ) {
		$attempts_key = 'bw_social_login_attempts_' . md5( $ip );
		$attempts     = (int) get_transient( $attempts_key );

		if ( $attempts > 20 ) {
			self::debug_log( 'Rate limit exceeded', [ 'ip' => $ip, 'attempts' => $attempts ] );
			return false;
		}

		set_transient( $attempts_key, $attempts + 1, HOUR_IN_SECONDS );
		return true;
	}

	/**
	 * Handle social login requests (start and callback).
	 */
	public static function handle_requests() {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['bw_social_login'] ) ) {
			$provider = sanitize_key( wp_unslash( $_GET['bw_social_login'] ) );
			self::start_oauth_flow( $provider );
		}

		if ( isset( $_GET['bw_social_login_callback'] ) ) {
			$provider = sanitize_key( wp_unslash( $_GET['bw_social_login_callback'] ) );
			self::process_oauth_callback( $provider );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Build social login start URL.
	 *
	 * @param string $provider Social provider key.
	 * @return string Login URL or empty string.
	 */
	public static function get_login_url( $provider ) {
		if ( ! self::is_valid_provider( $provider ) ) {
			return '';
		}

		return add_query_arg( 'bw_social_login', $provider, wc_get_page_permalink( 'myaccount' ) );
	}

	/**
	 * Get the OAuth callback URL for the provider.
	 *
	 * @param string $provider Provider key.
	 * @return string Redirect URI or empty string.
	 */
	public static function get_redirect_uri( $provider ) {
		if ( ! self::is_valid_provider( $provider ) ) {
			return '';
		}

		return add_query_arg( 'bw_social_login_callback', $provider, wc_get_page_permalink( 'myaccount' ) );
	}

	/**
	 * Start OAuth flow by redirecting to the provider.
	 *
	 * @param string $provider Provider key.
	 */
	private static function start_oauth_flow( $provider ) {
		if ( ! self::is_valid_provider( $provider ) ) {
			self::debug_log( 'Invalid provider', [ 'provider' => $provider ] );
			return;
		}

		// Rate limiting check.
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( ! self::check_rate_limit( $ip ) ) {
			wc_add_notice( __( 'Too many login attempts. Please try again later.', 'bw' ), 'error' );
			wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
			exit;
		}

		$settings    = self::get_settings();
		$config      = $settings[ $provider ] ?? [];
		$enabled     = $config['enabled'] ?? 0;
		$client_id   = 'facebook' === $provider ? ( $config['app_id'] ?? '' ) : ( $config['client_id'] ?? '' );
		$redirect    = self::get_redirect_uri( $provider );
		$account_url = wc_get_page_permalink( 'myaccount' );

		if ( ! $enabled || empty( $client_id ) || empty( $redirect ) ) {
			self::debug_log( 'OAuth not configured', [
				'provider'  => $provider,
				'enabled'   => $enabled,
				'has_id'    => ! empty( $client_id ),
				'redirect'  => $redirect,
			] );
			wc_add_notice( __( 'Social login is not available at the moment.', 'bw' ), 'error' );
			wp_safe_redirect( $account_url );
			exit;
		}

		// Generate and store state token.
		$state = wp_generate_password( 16, false );
		set_transient( 'bw_social_state_' . $state, [ 'provider' => $provider ], 15 * MINUTE_IN_SECONDS );

		self::debug_log( 'Starting OAuth flow', [
			'provider' => $provider,
			'state'    => substr( $state, 0, 8 ) . '...',
		] );

		if ( 'facebook' === $provider ) {
			$auth_url = add_query_arg(
				[
					'client_id'     => $client_id,
					'redirect_uri'  => $redirect,
					'state'         => $state,
					'scope'         => 'email',
					'response_type' => 'code',
				],
				'https://www.facebook.com/v19.0/dialog/oauth'
			);
		} else {
			$auth_url = add_query_arg(
				[
					'client_id'              => $client_id,
					'redirect_uri'           => $redirect,
					'response_type'          => 'code',
					'scope'                  => 'openid email profile',
					'access_type'            => 'online',
					'include_granted_scopes' => 'true',
					'state'                  => $state,
					'prompt'                 => 'select_account',
				],
				'https://accounts.google.com/o/oauth2/v2/auth'
			);
		}

		wp_safe_redirect( $auth_url );
		exit;
	}

	/**
	 * Process OAuth callback and authenticate the user.
	 *
	 * @param string $provider Provider key.
	 */
	private static function process_oauth_callback( $provider ) {
		if ( ! self::is_valid_provider( $provider ) ) {
			self::debug_log( 'Invalid provider in callback', [ 'provider' => $provider ] );
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$state        = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		$code         = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		$error        = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$saved        = $state ? get_transient( 'bw_social_state_' . $state ) : false;
		$redirect_url = wc_get_page_permalink( 'myaccount' );

		// Handle user cancellation.
		if ( ! empty( $error ) ) {
			self::debug_log( 'OAuth error from provider', [
				'provider' => $provider,
				'error'    => $error,
			] );

			if ( 'access_denied' === $error ) {
				wc_add_notice( __( 'Login cancelled. You can try again anytime.', 'bw' ), 'notice' );
			} else {
				wc_add_notice( __( 'An error occurred during login. Please try again.', 'bw' ), 'error' );
			}

			wp_safe_redirect( $redirect_url );
			exit;
		}

		// Validate state token.
		if ( empty( $saved ) || $saved['provider'] !== $provider ) {
			self::debug_log( 'Invalid or expired state', [
				'provider'       => $provider,
				'state_valid'    => ! empty( $saved ),
				'provider_match' => isset( $saved['provider'] ) ? ( $saved['provider'] === $provider ) : false,
			] );
			wc_add_notice( __( 'The social login session has expired. Please try again.', 'bw' ), 'error' );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		delete_transient( 'bw_social_state_' . $state );

		// Validate authorization code.
		if ( empty( $code ) ) {
			self::debug_log( 'Missing authorization code', [ 'provider' => $provider ] );
			wc_add_notice( __( 'Authorization code missing. Please try again.', 'bw' ), 'error' );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		$redirect_uri = self::get_redirect_uri( $provider );

		// Exchange code for user data.
		if ( 'facebook' === $provider ) {
			$user_data = self::exchange_facebook_code( $code, $redirect_uri );
		} else {
			$user_data = self::exchange_google_code( $code, $redirect_uri );
		}

		if ( is_wp_error( $user_data ) ) {
			self::debug_log( 'Failed to exchange code', [
				'provider' => $provider,
				'error'    => $user_data->get_error_message(),
			] );
			wc_add_notice( $user_data->get_error_message(), 'error' );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		$email = isset( $user_data['email'] ) ? sanitize_email( $user_data['email'] ) : '';
		$name  = isset( $user_data['name'] ) ? sanitize_text_field( $user_data['name'] ) : $email;

		if ( empty( $email ) ) {
			self::debug_log( 'No email from provider', [ 'provider' => $provider ] );
			wc_add_notice( __( 'We could not retrieve your email address. Please use the standard login.', 'bw' ), 'error' );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		$login_result = self::login_or_register_user( $email, $name, $provider );

		if ( is_wp_error( $login_result ) ) {
			self::debug_log( 'Failed to login/register user', [
				'provider' => $provider,
				'email'    => $email,
				'error'    => $login_result->get_error_message(),
			] );
			wc_add_notice( $login_result->get_error_message(), 'error' );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		self::debug_log( 'User logged in successfully', [
			'provider' => $provider,
			'email'    => $email,
		] );

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Exchange Facebook authorization code for user data.
	 *
	 * @param string $code         Authorization code.
	 * @param string $redirect_uri Redirect URI used.
	 * @return array|WP_Error User data or error.
	 */
	private static function exchange_facebook_code( $code, $redirect_uri ) {
		$settings   = self::get_settings();
		$app_id     = $settings['facebook']['app_id'] ?? '';
		$app_secret = $settings['facebook']['app_secret'] ?? '';

		if ( empty( $app_id ) || empty( $app_secret ) ) {
			return new WP_Error( 'bw_facebook_missing_config', __( 'Facebook login is not configured.', 'bw' ) );
		}

		// Exchange code for access token.
		$response = wp_remote_post(
			'https://graph.facebook.com/v19.0/oauth/access_token',
			[
				'body'    => [
					'client_id'     => $app_id,
					'client_secret' => $app_secret,
					'redirect_uri'  => $redirect_uri,
					'code'          => $code,
				],
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $decoded['access_token'] ) ) {
			$error_msg = isset( $decoded['error']['message'] ) ? $decoded['error']['message'] : '';
			self::debug_log( 'Facebook token exchange failed', [ 'error' => $error_msg ] );
			return new WP_Error( 'bw_facebook_token_error', __( 'Unable to complete Facebook login.', 'bw' ) );
		}

		// Get user info.
		$user_response = wp_remote_get(
			add_query_arg(
				[
					'fields'       => 'id,name,email',
					'access_token' => $decoded['access_token'],
				],
				'https://graph.facebook.com/me'
			),
			[ 'timeout' => 15 ]
		);

		if ( is_wp_error( $user_response ) ) {
			return $user_response;
		}

		$user_data = json_decode( wp_remote_retrieve_body( $user_response ), true );
		if ( empty( $user_data['email'] ) ) {
			return new WP_Error( 'bw_facebook_email_error', __( 'Facebook did not return an email address.', 'bw' ) );
		}

		return [
			'email' => $user_data['email'],
			'name'  => isset( $user_data['name'] ) ? $user_data['name'] : $user_data['email'],
		];
	}

	/**
	 * Exchange Google authorization code for user data.
	 *
	 * @param string $code         Authorization code.
	 * @param string $redirect_uri Redirect URI used.
	 * @return array|WP_Error User data or error.
	 */
	private static function exchange_google_code( $code, $redirect_uri ) {
		$settings      = self::get_settings();
		$client_id     = $settings['google']['client_id'] ?? '';
		$client_secret = $settings['google']['client_secret'] ?? '';

		if ( empty( $client_id ) || empty( $client_secret ) ) {
			return new WP_Error( 'bw_google_missing_config', __( 'Google login is not configured.', 'bw' ) );
		}

		// Exchange code for access token.
		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			[
				'body'    => [
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'redirect_uri'  => $redirect_uri,
					'code'          => $code,
					'grant_type'    => 'authorization_code',
				],
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $decoded['access_token'] ) ) {
			$error_msg = isset( $decoded['error_description'] ) ? $decoded['error_description'] : '';
			self::debug_log( 'Google token exchange failed', [ 'error' => $error_msg ] );
			return new WP_Error( 'bw_google_token_error', __( 'Unable to complete Google login.', 'bw' ) );
		}

		// Get user info.
		$user_response = wp_remote_get(
			'https://www.googleapis.com/oauth2/v3/userinfo',
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $decoded['access_token'],
				],
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $user_response ) ) {
			return $user_response;
		}

		$user_data = json_decode( wp_remote_retrieve_body( $user_response ), true );
		if ( empty( $user_data['email'] ) ) {
			return new WP_Error( 'bw_google_email_error', __( 'Google did not return an email address.', 'bw' ) );
		}

		return [
			'email' => $user_data['email'],
			'name'  => isset( $user_data['name'] ) ? $user_data['name'] : $user_data['email'],
		];
	}

	/**
	 * Log the user in or register a new one from social data.
	 *
	 * @param string $email    User email.
	 * @param string $name     User display name.
	 * @param string $provider Social provider used.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private static function login_or_register_user( $email, $name, $provider ) {
		$user = get_user_by( 'email', $email );

		if ( ! $user ) {
			$registration_enabled = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );

			if ( ! $registration_enabled ) {
				return new WP_Error( 'bw_social_registration_disabled', __( 'Registrations are disabled. Please log in with an existing account.', 'bw' ) );
			}

			$username = sanitize_user( current( explode( '@', $email ) ) );

			// Ensure unique username.
			$base_username = $username;
			$counter       = 1;
			while ( username_exists( $username ) ) {
				$username = $base_username . $counter;
				$counter++;
			}

			$user_id = wc_create_new_customer( $email, $username, wp_generate_password(), [ 'first_name' => $name ] );

			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}

			// Store the social provider used for registration.
			update_user_meta( $user_id, 'bw_registered_via', $provider );

			$user = get_user_by( 'id', $user_id );

			self::debug_log( 'New user registered', [
				'provider' => $provider,
				'email'    => $email,
				'user_id'  => $user_id,
			] );
		}

		// Update last login provider.
		update_user_meta( $user->ID, 'bw_last_login_provider', $provider );
		update_user_meta( $user->ID, 'bw_last_login_time', current_time( 'mysql' ) );

		wc_set_customer_auth_cookie( $user->ID );
		wp_set_current_user( $user->ID );

		return true;
	}
}

// Initialize on plugins_loaded.
add_action( 'plugins_loaded', [ 'BW_Social_Login', 'init' ] );
