<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Supabase Auth handlers for the My Account login experience.
 */

/**
 * Get the configured default login provider.
 *
 * @return string
 */
function bw_mew_get_login_provider() {
    $provider = get_option( 'bw_account_login_provider', 'wordpress' );

    return in_array( $provider, [ 'wordpress', 'supabase' ], true ) ? $provider : 'wordpress';
}

/**
 * Handle Supabase password login via AJAX.
 */
function bw_mew_handle_supabase_login() {
    check_ajax_referer( 'bw-supabase-login', 'nonce' );

    if ( is_user_logged_in() ) {
        wp_send_json_error(
            [ 'message' => __( 'You are already logged in.', 'bw' ) ],
            400
        );
    }

    $email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';

    if ( ! $email || ! $password ) {
        wp_send_json_error(
            [ 'message' => __( 'Email and password are required.', 'bw' ) ],
            400
        );
    }

    $project_url = get_option( 'bw_supabase_project_url', '' );
    $anon_key    = get_option( 'bw_supabase_anon_key', '' );
    $debug_log   = (bool) get_option( 'bw_supabase_debug_log', 0 );

    if ( ! $project_url || ! $anon_key ) {
        wp_send_json_error(
            [ 'message' => __( 'Supabase is not configured yet.', 'bw' ) ],
            400
        );
    }

    $endpoint = trailingslashit( untrailingslashit( $project_url ) ) . 'auth/v1/token?grant_type=password';

    $response = wp_remote_post(
        $endpoint,
        [
            'headers' => [
                'apikey'       => $anon_key,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'timeout' => 15,
            'body'    => wp_json_encode(
                [
                    'email'    => $email,
                    'password' => $password,
                ]
            ),
        ]
    );

    if ( is_wp_error( $response ) ) {
        if ( $debug_log ) {
            error_log( sprintf( 'Supabase login error: %s', $response->get_error_message() ) );
        }

        wp_send_json_error(
            [ 'message' => __( 'Unable to reach Supabase. Please try again.', 'bw' ) ],
            500
        );
    }

    $status_code = wp_remote_retrieve_response_code( $response );
    $body        = wp_remote_retrieve_body( $response );
    $payload     = json_decode( $body, true );

    if ( $debug_log ) {
        error_log( sprintf( 'Supabase login status: %d', (int) $status_code ) );
    }

    if ( $status_code < 200 || $status_code >= 300 ) {
        $message = __( 'Invalid credentials. Please try again.', 'bw' );

        if ( is_array( $payload ) ) {
            $message = $payload['error_description'] ?? $payload['msg'] ?? $message;
        }

        wp_send_json_error(
            [ 'message' => $message ],
            401
        );
    }

    if ( ! is_array( $payload ) ) {
        wp_send_json_error(
            [ 'message' => __( 'Unexpected response from Supabase.', 'bw' ) ],
            500
        );
    }

    $stored = bw_mew_supabase_store_session( $payload, $email );

    if ( ! $stored ) {
        wp_send_json_error(
            [ 'message' => __( 'Supabase did not return session tokens.', 'bw' ) ],
            500
        );
    }

    wp_send_json_success(
        [
            'redirect' => wc_get_page_permalink( 'myaccount' ),
        ]
    );
}
add_action( 'wp_ajax_nopriv_bw_supabase_login', 'bw_mew_handle_supabase_login' );
add_action( 'wp_ajax_bw_supabase_login', 'bw_mew_handle_supabase_login' );

/**
 * Handle Supabase signup via AJAX.
 */
function bw_mew_handle_supabase_register() {
    check_ajax_referer( 'bw-supabase-login', 'nonce' );

    if ( is_user_logged_in() ) {
        wp_send_json_error(
            [ 'message' => __( 'You are already logged in.', 'bw' ) ],
            400
        );
    }

    $email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';

    if ( ! $email || ! $password ) {
        wp_send_json_error(
            [ 'message' => __( 'Email and password are required.', 'bw' ) ],
            400
        );
    }

    $project_url = get_option( 'bw_supabase_project_url', '' );
    $anon_key    = get_option( 'bw_supabase_anon_key', '' );
    $debug_log   = (bool) get_option( 'bw_supabase_debug_log', 0 );

    if ( ! $project_url || ! $anon_key ) {
        wp_send_json_error(
            [ 'message' => __( 'Supabase is not configured yet.', 'bw' ) ],
            400
        );
    }

    $endpoint = trailingslashit( untrailingslashit( $project_url ) ) . 'auth/v1/signup';

    $response = wp_remote_post(
        $endpoint,
        [
            'headers' => [
                'apikey'       => $anon_key,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'timeout' => 15,
            'body'    => wp_json_encode(
                [
                    'email'    => $email,
                    'password' => $password,
                ]
            ),
        ]
    );

    if ( is_wp_error( $response ) ) {
        if ( $debug_log ) {
            error_log( sprintf( 'Supabase register error: %s', $response->get_error_message() ) );
        }

        wp_send_json_error(
            [ 'message' => __( 'Unable to reach Supabase. Please try again.', 'bw' ) ],
            500
        );
    }

    $status_code = wp_remote_retrieve_response_code( $response );
    $body        = wp_remote_retrieve_body( $response );
    $payload     = json_decode( $body, true );

    if ( $debug_log ) {
        error_log( sprintf( 'Supabase register status: %d', (int) $status_code ) );
    }

    if ( $status_code < 200 || $status_code >= 300 ) {
        $message = __( 'Unable to register with Supabase.', 'bw' );

        if ( is_array( $payload ) ) {
            $message = $payload['error_description'] ?? $payload['msg'] ?? $message;
        }

        wp_send_json_error(
            [ 'message' => $message ],
            401
        );
    }

    if ( ! is_array( $payload ) ) {
        wp_send_json_error(
            [ 'message' => __( 'Unexpected response from Supabase.', 'bw' ) ],
            500
        );
    }

    $stored = bw_mew_supabase_store_session( $payload, $email );

    if ( $stored ) {
        wp_send_json_success(
            [
                'redirect' => wc_get_page_permalink( 'myaccount' ),
            ]
        );
    }

    wp_send_json_success(
        [
            'message' => __( 'Check your email to confirm your Supabase account.', 'bw' ),
        ]
    );
}
add_action( 'wp_ajax_nopriv_bw_supabase_register', 'bw_mew_handle_supabase_register' );
add_action( 'wp_ajax_bw_supabase_register', 'bw_mew_handle_supabase_register' );

/**
 * Store Supabase session tokens in cookies/usermeta.
 *
 * @param array  $payload Supabase response payload.
 * @param string $email   Email to link when needed.
 *
 * @return bool
 */
function bw_mew_supabase_store_session( array $payload, $email ) {
    $access_token  = $payload['access_token'] ?? '';
    $refresh_token = $payload['refresh_token'] ?? '';
    $expires_in    = isset( $payload['expires_in'] ) ? absint( $payload['expires_in'] ) : 0;
    $supabase_user = $payload['user'] ?? [];
    $user_email    = isset( $supabase_user['email'] ) ? sanitize_email( $supabase_user['email'] ) : $email;

    if ( ! $access_token || ! $refresh_token ) {
        return false;
    }

    $storage     = get_option( 'bw_supabase_session_storage', 'cookie' );
    $storage     = in_array( $storage, [ 'cookie', 'usermeta' ], true ) ? $storage : 'cookie';
    $cookie_base = get_option( 'bw_supabase_jwt_cookie_name', 'bw_supabase_session' );
    $cookie_base = sanitize_key( $cookie_base ) ?: 'bw_supabase_session';
    $secure      = is_ssl();
    $link_users  = (bool) get_option( 'bw_supabase_enable_wp_user_linking', 0 );
    $user        = null;

    if ( $link_users && $user_email ) {
        $user = get_user_by( 'email', $user_email );
        if ( $user instanceof WP_User ) {
            wp_set_current_user( $user->ID );
            wp_set_auth_cookie( $user->ID, true, $secure );

            if ( 'usermeta' === $storage ) {
                update_user_meta( $user->ID, 'bw_supabase_access_token', $access_token );
                update_user_meta( $user->ID, 'bw_supabase_refresh_token', $refresh_token );
                update_user_meta( $user->ID, 'bw_supabase_expires_at', time() + max( 0, $expires_in ) );
            }
        }
    }

    if ( 'cookie' === $storage || ! ( $user instanceof WP_User ) ) {
        $access_expires  = time() + ( $expires_in ? $expires_in : HOUR_IN_SECONDS );
        $refresh_expires = time() + ( 30 * DAY_IN_SECONDS );

        setcookie(
            $cookie_base . '_access',
            $access_token,
            [
                'expires'  => $access_expires,
                'path'     => COOKIEPATH ? COOKIEPATH : '/',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );

        setcookie(
            $cookie_base . '_refresh',
            $refresh_token,
            [
                'expires'  => $refresh_expires,
                'path'     => COOKIEPATH ? COOKIEPATH : '/',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }

    return true;
}
