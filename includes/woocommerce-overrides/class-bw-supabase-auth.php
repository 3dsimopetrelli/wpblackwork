<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Supabase Auth handlers for the My Account login experience.
 */

/**
 * Retrieve normalized Supabase configuration and diagnostics.
 *
 * @return array<string,mixed>
 */
function bw_mew_get_supabase_config() {
    $project_url = trim( (string) get_option( 'bw_supabase_project_url', '' ) );
    $anon_key    = trim( (string) get_option( 'bw_supabase_anon_key', '' ) );

    return [
        'project_url' => $project_url,
        'anon_key'    => $anon_key,
        'has_url'     => (bool) $project_url,
        'has_anon'    => (bool) $anon_key,
    ];
}

/**
 * Build admin-only diagnostics for Supabase config.
 *
 * @param array<string,mixed> $config Config array.
 *
 * @return array<string,string>
 */
function bw_mew_supabase_build_diagnostics( array $config ) {
    $url = $config['project_url'] ?? '';
    $url_display = $url ? wp_parse_url( $url, PHP_URL_HOST ) : '';

    return [
        'missing_project_url' => empty( $config['has_url'] ) ? 'yes' : 'no',
        'missing_anon_key'    => empty( $config['has_anon'] ) ? 'yes' : 'no',
        'project_url_host'    => $url_display ? $url_display : 'empty',
        'anon_key_present'    => empty( $config['has_anon'] ) ? 'empty' : 'present',
        'options'             => 'bw_supabase_project_url, bw_supabase_anon_key, bw_supabase_with_plugins, bw_supabase_login_mode, bw_supabase_registration_mode, bw_supabase_checkout_provision_enabled, bw_supabase_invite_redirect_url',
    ];
}

/**
 * Return diagnostics to admins when debug logging is enabled.
 *
 * @param array<string,mixed> $config Config array.
 *
 * @return array<string,mixed>
 */
function bw_mew_supabase_debug_payload( array $config ) {
    $debug_log = (bool) get_option( 'bw_supabase_debug_log', 0 );

    if ( ! $debug_log || ! current_user_can( 'manage_options' ) ) {
        return [];
    }

    return [
        'diagnostics' => bw_mew_supabase_build_diagnostics( $config ),
    ];
}

/**
 * Sanitize and normalize redirect URLs for Supabase email confirmations.
 *
 * @param string $url Raw URL.
 *
 * @return string
 */
function bw_mew_supabase_sanitize_redirect_url( $url ) {
    $url = trim( (string) $url );

    if ( ! $url ) {
        return '';
    }

    $url = preg_replace( '/\s+/', '', $url );
    $url = preg_replace( '/\.+$/', '', $url );

    if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
        $url = site_url( $url );
    }

    if ( 0 === strpos( $url, 'http://' ) ) {
        $url = 'https://' . substr( $url, 7 );
    }

    return esc_url_raw( $url );
}

/**
 * Retrieve the Supabase access token for the current user.
 *
 * @param int $user_id User ID.
 *
 * @return string
 */
function bw_mew_get_supabase_access_token( $user_id ) {
    $storage     = get_option( 'bw_supabase_session_storage', 'cookie' );
    $storage     = in_array( $storage, [ 'cookie', 'usermeta' ], true ) ? $storage : 'cookie';
    $cookie_base = get_option( 'bw_supabase_jwt_cookie_name', 'bw_supabase_session' );
    $cookie_base = sanitize_key( $cookie_base ) ?: 'bw_supabase_session';
    $token       = '';

    if ( 'usermeta' === $storage && $user_id ) {
        $token = (string) get_user_meta( $user_id, 'bw_supabase_access_token', true );
        $token = $token ? sanitize_text_field( $token ) : '';
    }

    if ( ! $token ) {
        $cookie_name = $cookie_base . '_access';
        if ( isset( $_COOKIE[ $cookie_name ] ) ) {
            $token = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
        }
    }

    if ( ! $token && $user_id ) {
        $meta_token = (string) get_user_meta( $user_id, 'bw_supabase_access_token', true );
        $token      = $meta_token ? sanitize_text_field( $meta_token ) : '';
    }

    return $token;
}

/**
 * Request the Supabase user profile with an access token.
 *
 * @param string $access_token Supabase access token.
 * @param string $context      Context for logging.
 *
 * @return array{status:int,payload:array<string,mixed>}|WP_Error
 */
function bw_mew_fetch_supabase_user( $access_token, $context = '' ) {
    $config    = bw_mew_get_supabase_config();
    $debug_log = (bool) get_option( 'bw_supabase_debug_log', 0 );

    if ( empty( $config['has_url'] ) || empty( $config['has_anon'] ) ) {
        return new WP_Error( 'bw_supabase_missing_config', __( 'Supabase is not configured yet.', 'bw' ) );
    }

    $endpoint = trailingslashit( untrailingslashit( $config['project_url'] ) ) . 'auth/v1/user';
    $response = wp_remote_get(
        $endpoint,
        [
            'headers' => [
                'apikey'       => $config['anon_key'],
                'Authorization' => 'Bearer ' . $access_token,
                'Accept'       => 'application/json',
            ],
            'timeout' => 15,
        ]
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $status_code = wp_remote_retrieve_response_code( $response );
    $payload     = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $debug_log ) {
        $context_label = $context ? $context : 'unknown';
        error_log( sprintf( 'Supabase user fetch (%s) endpoint: %s', $context_label, $endpoint ) );
        error_log( sprintf( 'Supabase user fetch (%s) status: %d', $context_label, (int) $status_code ) );
    }

    if ( $status_code < 200 || $status_code >= 300 || ! is_array( $payload ) ) {
        return new WP_Error( 'bw_supabase_user_fetch_failed', __( 'Unable to fetch Supabase user.', 'bw' ) );
    }

    return [
        'status'  => (int) $status_code,
        'payload' => $payload,
    ];
}

/**
 * Apply Supabase user data to WordPress.
 *
 * @param int                  $user_id       WordPress user ID.
 * @param array<string,mixed>  $supabase_user Supabase user payload.
 * @param string               $context       Context for logging.
 *
 * @return void
 */
function bw_mew_apply_supabase_user_to_wp( $user_id, array $supabase_user, $context = '' ) {
    $user_meta = isset( $supabase_user['user_metadata'] ) && is_array( $supabase_user['user_metadata'] )
        ? $supabase_user['user_metadata']
        : [];
    $first_name = isset( $user_meta['first_name'] ) ? sanitize_text_field( $user_meta['first_name'] ) : '';
    $last_name  = isset( $user_meta['last_name'] ) ? sanitize_text_field( $user_meta['last_name'] ) : '';
    $display_name = '';

    if ( isset( $user_meta['display_name'] ) ) {
        $display_name = sanitize_text_field( $user_meta['display_name'] );
    } elseif ( isset( $user_meta['full_name'] ) ) {
        $display_name = sanitize_text_field( $user_meta['full_name'] );
    } elseif ( isset( $user_meta['name'] ) ) {
        $display_name = sanitize_text_field( $user_meta['name'] );
    }

    if ( $first_name ) {
        update_user_meta( $user_id, 'first_name', $first_name );
    }

    if ( $last_name ) {
        update_user_meta( $user_id, 'last_name', $last_name );
    }

    $update_args = [ 'ID' => $user_id ];
    if ( $display_name ) {
        $update_args['display_name'] = $display_name;
    }

    $email = isset( $supabase_user['email'] ) ? sanitize_email( $supabase_user['email'] ) : '';
    $email_confirmed = ! empty( $supabase_user['email_confirmed_at'] ) || ! empty( $supabase_user['confirmed_at'] );
    $pending_email = $user_id ? get_user_meta( $user_id, 'bw_supabase_pending_email', true ) : '';

    if ( $email && $email_confirmed ) {
        if ( $pending_email && strtolower( $pending_email ) === strtolower( $email ) ) {
            delete_user_meta( $user_id, 'bw_supabase_pending_email' );
        }

        $update_args['user_email'] = $email;
    }

    if ( count( $update_args ) > 1 ) {
        wp_update_user( $update_args );
    }
}

/**
 * Sync Supabase user metadata into WordPress.
 *
 * @param int    $user_id User ID.
 * @param string $context Context for logging.
 *
 * @return void
 */
function bw_mew_sync_supabase_user( $user_id, $context = '' ) {
    if ( ! $user_id ) {
        return;
    }

    $access_token = bw_mew_get_supabase_access_token( $user_id );
    if ( ! $access_token ) {
        return;
    }

    $response = bw_mew_fetch_supabase_user( $access_token, $context );
    if ( is_wp_error( $response ) ) {
        return;
    }

    bw_mew_apply_supabase_user_to_wp( $user_id, $response['payload'], $context );
}

/**
 * Sync Supabase user metadata on authenticated page loads.
 */
function bw_mew_sync_supabase_user_on_load() {
    if ( is_admin() || wp_doing_ajax() || ! is_user_logged_in() ) {
        return;
    }

    bw_mew_sync_supabase_user( get_current_user_id(), 'page-load' );
}
add_action( 'init', 'bw_mew_sync_supabase_user_on_load', 20 );

/**
 * Use a Supabase access token to create a WordPress session after confirmation.
 */
function bw_mew_handle_supabase_token_login() {
    $nonce_valid = check_ajax_referer( 'bw-supabase-login', 'nonce', false );

    // For invite/hash callbacks from public pages, the localized nonce can be stale
    // because of full-page cache. In that case we continue and rely on Supabase token
    // verification below. Keep strict nonce checks for already-authenticated requests.
    if ( ! $nonce_valid && is_user_logged_in() ) {
        wp_send_json_error(
            [ 'message' => __( 'Security check failed. Please refresh and try again.', 'bw' ) ],
            403
        );
    }

    if ( is_user_logged_in() ) {
        wp_send_json_success(
            [
                'redirect' => wc_get_page_permalink( 'myaccount' ),
            ]
        );
    }

    $access_token  = isset( $_POST['access_token'] ) ? sanitize_text_field( wp_unslash( $_POST['access_token'] ) ) : '';
    $refresh_token = isset( $_POST['refresh_token'] ) ? sanitize_text_field( wp_unslash( $_POST['refresh_token'] ) ) : '';
    $token_type    = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
    if ( ! $access_token ) {
        wp_send_json_error(
            [ 'message' => __( 'Missing access token.', 'bw' ) ],
            400
        );
    }

    $config    = bw_mew_get_supabase_config();
    $debug_log = (bool) get_option( 'bw_supabase_debug_log', 0 );

    if ( empty( $config['has_url'] ) || empty( $config['has_anon'] ) ) {
        if ( $debug_log ) {
            error_log( sprintf( 'Supabase config missing (token login): %s', wp_json_encode( bw_mew_supabase_build_diagnostics( $config ) ) ) );
        }

        wp_send_json_error(
            array_merge(
                [ 'message' => __( 'Supabase is not configured yet.', 'bw' ) ],
                bw_mew_supabase_debug_payload( $config )
            ),
            400
        );
    }

    $endpoint = trailingslashit( untrailingslashit( $config['project_url'] ) ) . 'auth/v1/user';

    $response = wp_remote_get(
        $endpoint,
        [
            'headers' => [
                'apikey'       => $config['anon_key'],
                'Authorization' => 'Bearer ' . $access_token,
                'Accept'       => 'application/json',
            ],
            'timeout' => 15,
        ]
    );

    if ( is_wp_error( $response ) ) {
        if ( $debug_log ) {
            error_log( sprintf( 'Supabase token login error: %s', $response->get_error_message() ) );
        }

        wp_send_json_error(
            [ 'message' => __( 'Unable to reach Supabase. Please try again.', 'bw' ) ],
            500
        );
    }

    $status_code = wp_remote_retrieve_response_code( $response );
    $body        = wp_remote_retrieve_body( $response );
    $payload     = json_decode( $body, true );

    if ( $status_code < 200 || $status_code >= 300 || ! is_array( $payload ) ) {
        wp_send_json_error(
            [ 'message' => __( 'Unable to verify Supabase session.', 'bw' ) ],
            401
        );
    }

    $email = isset( $payload['email'] ) ? sanitize_email( $payload['email'] ) : '';
    if ( ! $email ) {
        wp_send_json_error(
            [ 'message' => __( 'Supabase user email is missing.', 'bw' ) ],
            400
        );
    }

    $create_users = (bool) get_option( 'bw_supabase_create_wp_users', 1 );
    $user         = get_user_by( 'email', $email );

    if ( ! $user && $create_users ) {
        $user_id = wp_create_user( $email, wp_generate_password( 32, true ), $email );
        if ( ! is_wp_error( $user_id ) ) {
            $user = get_user_by( 'id', $user_id );
        }
    }

    if ( ! $user instanceof WP_User ) {
        wp_send_json_error(
            [ 'message' => __( 'No matching WordPress user found.', 'bw' ) ],
            404
        );
    }

    $guard_key = 'bw_supabase_token_login_' . md5( $access_token . '|' . $token_type );
    if ( get_transient( $guard_key ) ) {
        wp_send_json_success(
            [
                'redirect' => wc_get_page_permalink( 'myaccount' ),
            ]
        );
    }
    set_transient( $guard_key, 1, 30 );

    wp_set_current_user( $user->ID );
    wp_set_auth_cookie( $user->ID, true, is_ssl() );

    if ( $refresh_token ) {
        bw_mew_supabase_store_session(
            [
                'access_token'  => $access_token,
                'refresh_token' => $refresh_token,
                'expires_in'    => HOUR_IN_SECONDS,
                'user'          => [
                    'email' => $email,
                ],
            ],
            $email
        );
    }

    $already_onboarded       = function_exists( 'bw_user_needs_onboarding' )
        ? ! bw_user_needs_onboarding( $user->ID )
        : ( 1 === (int) get_user_meta( $user->ID, 'bw_supabase_onboarded', true ) );
    $is_invite               = 'invite' === $token_type;
    $needs_password_cookie   = isset( $_COOKIE['bw_post_otp_needs_password'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['bw_post_otp_needs_password'] ) ) : '';
    $needs_password_for_otp  = 'otp' === $token_type && '1' === $needs_password_cookie;

    if ( $needs_password_for_otp ) {
        update_user_meta( $user->ID, 'bw_supabase_onboarded', 0 );
        delete_user_meta( $user->ID, 'bw_supabase_invited' );
    } elseif ( $is_invite && ! $already_onboarded ) {
        update_user_meta( $user->ID, 'bw_supabase_onboarded', 0 );
        update_user_meta( $user->ID, 'bw_supabase_invited', 1 );
    } elseif ( ! $is_invite ) {
        update_user_meta( $user->ID, 'bw_supabase_onboarded', 1 );
        delete_user_meta( $user->ID, 'bw_supabase_invited' );
    }

    delete_user_meta( $user->ID, 'bw_supabase_invite_error' );
    delete_user_meta( $user->ID, 'bw_supabase_onboarding_error' );
    bw_mew_apply_supabase_user_to_wp( $user->ID, $payload, 'token-login' );

    $needs_onboarding = $is_invite && ! $already_onboarded;
    if ( $needs_password_for_otp || $needs_onboarding ) {
        $redirect_url = add_query_arg( 'bw_set_password', '1', wc_get_page_permalink( 'myaccount' ) );
    } else {
        $redirect_url = wc_get_page_permalink( 'myaccount' );
    }

    if ( $debug_log ) {
        error_log(
            sprintf(
                'Supabase token login success (type=%s) → redirect %s',
                $token_type ? $token_type : 'none',
                $redirect_url
            )
        );
    }

    if ( $refresh_token ) {
        bw_mew_supabase_store_session(
            [
                'access_token'  => $access_token,
                'refresh_token' => $refresh_token,
                'expires_in'    => HOUR_IN_SECONDS,
                'user'          => [
                    'email' => $email,
                ],
            ],
            $email
        );
    }

    wp_send_json_success(
        [
            'redirect'        => $redirect_url,
            'needs_password'  => $needs_password_for_otp,
        ]
    );
}
add_action( 'wp_ajax_nopriv_bw_supabase_token_login', 'bw_mew_handle_supabase_token_login' );
add_action( 'wp_ajax_bw_supabase_token_login', 'bw_mew_handle_supabase_token_login' );

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

    $config    = bw_mew_get_supabase_config();
    $debug_log = (bool) get_option( 'bw_supabase_debug_log', 0 );

    if ( empty( $config['has_url'] ) || empty( $config['has_anon'] ) ) {
        if ( $debug_log ) {
            error_log( sprintf( 'Supabase config missing: %s', wp_json_encode( bw_mew_supabase_build_diagnostics( $config ) ) ) );
        }

        wp_send_json_error(
            array_merge(
                [ 'message' => __( 'Supabase is not configured yet.', 'bw' ) ],
                bw_mew_supabase_debug_payload( $config )
            ),
            400
        );
    }

    // Supabase password grant endpoint (server-side).
    $endpoint = trailingslashit( untrailingslashit( $config['project_url'] ) ) . 'auth/v1/token?grant_type=password';

    $response = wp_remote_post(
        $endpoint,
        [
            'headers' => [
                'apikey'       => $config['anon_key'],
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

    // Store tokens in secure cookies or usermeta and optionally link to WP users.
    $stored = bw_mew_supabase_store_session( $payload, $email );

    if ( ! $stored ) {
        wp_send_json_error(
            [ 'message' => __( 'Supabase did not return session tokens.', 'bw' ) ],
            500
        );
    }

    if ( ! is_user_logged_in() ) {
        $link_users   = (bool) get_option( 'bw_supabase_enable_wp_user_linking', 0 );
        $create_users = (bool) get_option( 'bw_supabase_create_wp_users', 1 );

        if ( ! $link_users ) {
            wp_send_json_error(
                [ 'message' => __( 'Login succeeded, but WordPress user linking is disabled. Enable “Link Supabase users to WP users” to complete login.', 'bw' ) ],
                403
            );
        }

        if ( ! $create_users ) {
            wp_send_json_error(
                [ 'message' => __( 'Login succeeded, but no matching WordPress user exists. Enable automatic WP user creation or create the user manually.', 'bw' ) ],
                404
            );
        }

        wp_send_json_error(
            [ 'message' => __( 'Login succeeded, but a WordPress session could not be created.', 'bw' ) ],
            500
        );
    }

    $user_id = get_current_user_id();
    if ( $user_id ) {
        update_user_meta( $user_id, 'bw_supabase_onboarded', 1 );
        delete_user_meta( $user_id, 'bw_supabase_invited' );
        delete_user_meta( $user_id, 'bw_supabase_invite_error' );
        delete_user_meta( $user_id, 'bw_supabase_onboarding_error' );
        if ( isset( $payload['user'] ) && is_array( $payload['user'] ) ) {
            bw_mew_apply_supabase_user_to_wp( $user_id, $payload['user'], 'password-login' );
        }
    }

    if ( $debug_log ) {
        error_log( 'Supabase login success → set onboarded=1 → redirect /my-account/' );
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
 * Check if a Supabase user exists by email.
 */
function bw_mew_handle_supabase_email_exists() {
    check_ajax_referer( 'bw-supabase-login', 'nonce' );

    $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    if ( ! $email || ! is_email( $email ) ) {
        wp_send_json_error(
            [
                'ok'     => false,
                'exists' => false,
                'reason' => 'invalid_email',
            ],
            400
        );
    }

    $config    = bw_mew_get_supabase_config();
    $debug_log = (bool) get_option( 'bw_supabase_debug_log', 0 );
    $service_key = trim( (string) get_option( 'bw_supabase_service_role_key', '' ) );

    if ( empty( $config['has_url'] ) || ! $service_key ) {
        if ( $debug_log ) {
            error_log( sprintf( 'Supabase email exists skipped (missing service role). email_hash=%s', hash( 'sha256', strtolower( $email ) ) ) );
        }
        wp_send_json_success(
            [
                'ok'     => false,
                'exists' => false,
                'reason' => 'service_role_missing',
                'status' => 200,
            ]
        );
    }

    $endpoint = trailingslashit( untrailingslashit( $config['project_url'] ) ) . 'auth/v1/admin/users';
    $endpoint = add_query_arg(
        [
            'page'     => 1,
            'per_page' => 100,
        ],
        $endpoint
    );

    $response = wp_remote_get(
        $endpoint,
        [
            'headers' => [
                'apikey'       => $service_key,
                'Authorization' => 'Bearer ' . $service_key,
                'Accept'       => 'application/json',
            ],
            'timeout' => 15,
        ]
    );

    if ( is_wp_error( $response ) ) {
        if ( $debug_log ) {
            error_log( sprintf( 'Supabase email exists error: %s', $response->get_error_message() ) );
        }
        wp_send_json_success(
            [
                'ok'     => false,
                'exists' => false,
                'reason' => 'request_failed',
                'status' => 500,
            ]
        );
    }

    $status_code = wp_remote_retrieve_response_code( $response );
    $payload     = json_decode( wp_remote_retrieve_body( $response ), true );
    $exists      = false;
    $users       = [];

    if ( is_array( $payload ) ) {
        if ( isset( $payload['users'] ) && is_array( $payload['users'] ) ) {
            $users = $payload['users'];
        } elseif ( isset( $payload[0] ) ) {
            $users = $payload;
        }
    }

    if ( $users ) {
        $email_lower = strtolower( $email );
        foreach ( $users as $user ) {
            if ( ! is_array( $user ) || empty( $user['email'] ) ) {
                continue;
            }
            if ( strtolower( (string) $user['email'] ) === $email_lower ) {
                $exists = true;
                break;
            }
        }
    }

    if ( $debug_log ) {
        error_log(
            sprintf(
                'Supabase email exists (status=%d, exists=%s, endpoint=%s, email_hash=%s)',
                (int) $status_code,
                $exists ? 'yes' : 'no',
                $endpoint,
                hash( 'sha256', strtolower( $email ) )
            )
        );
    }

    wp_send_json_success(
        [
            'ok'     => true,
            'exists' => $exists,
            'reason' => '',
            'status' => (int) $status_code,
        ]
    );
}
add_action( 'wp_ajax_nopriv_bw_supabase_email_exists', 'bw_mew_handle_supabase_email_exists' );
add_action( 'wp_ajax_bw_supabase_email_exists', 'bw_mew_handle_supabase_email_exists' );

/**
 * Update Supabase user fields for authenticated requests.
 *
 * @param string               $access_token Supabase access token.
 * @param array<string,mixed>  $payload      Update payload.
 * @param string               $context      Context for logging.
 *
 * @return array{status:int,payload:array<string,mixed>}|WP_Error
 */
function bw_mew_supabase_update_user( $access_token, array $payload, $context = '' ) {
    $config    = bw_mew_get_supabase_config();
    $debug_log = (bool) get_option( 'bw_supabase_debug_log', 0 );

    if ( empty( $config['has_url'] ) || empty( $config['has_anon'] ) ) {
        return new WP_Error( 'bw_supabase_missing_config', __( 'Supabase is not configured yet.', 'bw' ) );
    }

    $endpoint = trailingslashit( untrailingslashit( $config['project_url'] ) ) . 'auth/v1/user';
    $response = wp_remote_request(
        $endpoint,
        [
            'method'  => 'PUT',
            'headers' => [
                'apikey'       => $config['anon_key'],
                'Authorization' => 'Bearer ' . $access_token,
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'timeout' => 15,
            'body'    => wp_json_encode( $payload ),
        ]
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $status_code = wp_remote_retrieve_response_code( $response );
    $body        = wp_remote_retrieve_body( $response );
    $decoded     = json_decode( $body, true );

    if ( $debug_log ) {
        $context_label = $context ? $context : 'unknown';
        error_log( sprintf( 'Supabase update user (%s) endpoint: %s', $context_label, $endpoint ) );
        error_log( sprintf( 'Supabase update user (%s) status: %d', $context_label, (int) $status_code ) );
    }

    if ( $status_code < 200 || $status_code >= 300 || ! is_array( $decoded ) ) {
        $message = __( 'Supabase update failed.', 'bw' );
        if ( is_array( $decoded ) ) {
            $remote_message = '';
            if ( isset( $decoded['msg'] ) && is_string( $decoded['msg'] ) ) {
                $remote_message = $decoded['msg'];
            } elseif ( isset( $decoded['message'] ) && is_string( $decoded['message'] ) ) {
                $remote_message = $decoded['message'];
            }

            if ( $remote_message ) {
                $message = $remote_message;
            }
        }

        return new WP_Error(
            'bw_supabase_update_failed',
            $message,
            [ 'status' => (int) $status_code ]
        );
    }

    return [
        'status'  => (int) $status_code,
        'payload' => $decoded,
    ];
}

/**
 * Update Supabase profile metadata via AJAX.
 */
function bw_mew_handle_supabase_update_profile() {
    check_ajax_referer( 'bw-supabase-login', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( [ 'message' => __( 'You must be logged in.', 'bw' ) ], 401 );
    }

    $user_id      = get_current_user_id();
    $access_token = bw_mew_get_supabase_access_token( $user_id );

    if ( ! $access_token ) {
        wp_send_json_error( [ 'message' => __( 'Supabase session is missing.', 'bw' ) ], 401 );
    }

    $first_name   = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
    $last_name    = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
    $display_name = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';

    if ( ! $first_name || ! $last_name || ! $display_name ) {
        wp_send_json_error( [ 'message' => __( 'Please complete all profile fields.', 'bw' ) ], 400 );
    }

    $payload = [
        'data' => [
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => $display_name,
        ],
    ];

    $response = bw_mew_supabase_update_user( $access_token, $payload, 'profile-update' );
    if ( is_wp_error( $response ) ) {
        wp_send_json_error( [ 'message' => $response->get_error_message() ], 500 );
    }

    update_user_meta( $user_id, 'first_name', $first_name );
    update_user_meta( $user_id, 'last_name', $last_name );
    wp_update_user(
        [
            'ID'           => $user_id,
            'display_name' => $display_name,
        ]
    );

    if ( isset( $response['payload'] ) && is_array( $response['payload'] ) ) {
        bw_mew_apply_supabase_user_to_wp( $user_id, $response['payload'], 'profile-update' );
    }

    wp_send_json_success(
        [
            'message' => __( 'Profile updated.', 'bw' ),
        ]
    );
}
add_action( 'wp_ajax_bw_supabase_update_profile', 'bw_mew_handle_supabase_update_profile' );

/**
 * Validate Supabase password rules.
 *
 * @param string $password Supabase password.
 *
 * @return bool
 */
function bw_mew_supabase_password_meets_requirements( $password ) {
    $length_ok    = strlen( $password ) >= 8;
    $lowercase_ok = (bool) preg_match( '/[a-z]/', $password );
    $uppercase_ok = (bool) preg_match( '/[A-Z]/', $password );
    $number_ok    = (bool) preg_match( '/[0-9]/', $password );
    $symbol_ok    = (bool) preg_match( '/[^A-Za-z0-9]/', $password );

    return $length_ok && $lowercase_ok && $uppercase_ok && $number_ok && $symbol_ok;
}

/**
 * Update Supabase password via AJAX.
 */
function bw_mew_handle_supabase_update_password() {
    check_ajax_referer( 'bw-supabase-login', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( [ 'message' => __( 'You must be logged in.', 'bw' ) ], 401 );
    }

    $user_id      = get_current_user_id();
    $access_token = bw_mew_get_supabase_access_token( $user_id );

    if ( ! $access_token ) {
        wp_send_json_error( [ 'message' => __( 'Supabase session is missing.', 'bw' ) ], 401 );
    }

    $new_password     = isset( $_POST['new_password'] ) ? (string) wp_unslash( $_POST['new_password'] ) : '';
    $confirm_password = isset( $_POST['confirm_password'] ) ? (string) wp_unslash( $_POST['confirm_password'] ) : '';

    if ( ! $new_password || ! $confirm_password ) {
        wp_send_json_error( [ 'message' => __( 'Please enter and confirm your new password.', 'bw' ) ], 400 );
    }

    if ( $new_password !== $confirm_password ) {
        wp_send_json_error( [ 'message' => __( 'Passwords do not match.', 'bw' ) ], 400 );
    }

    if ( ! bw_mew_supabase_password_meets_requirements( $new_password ) ) {
        wp_send_json_error( [ 'message' => __( 'Password does not meet the requirements.', 'bw' ) ], 400 );
    }

    $response = bw_mew_supabase_update_user(
        $access_token,
        [ 'password' => $new_password ],
        'password-update'
    );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( [ 'message' => $response->get_error_message() ], 500 );
    }

    wp_send_json_success(
        [
            'message' => __( 'Password updated.', 'bw' ),
        ]
    );
}
add_action( 'wp_ajax_bw_supabase_update_password', 'bw_mew_handle_supabase_update_password' );

/**
 * Create/assign Supabase password during onboarding (pre-login flow).
 *
 * This endpoint is used by the My Account login experience when the user
 * verified OTP and must create a password before the WP session bridge.
 */
function bw_mew_handle_supabase_create_password() {
    check_ajax_referer( 'bw-supabase-login', 'nonce' );

    $access_token  = isset( $_POST['access_token'] ) ? sanitize_text_field( wp_unslash( $_POST['access_token'] ) ) : '';
    $new_password  = isset( $_POST['new_password'] ) ? (string) wp_unslash( $_POST['new_password'] ) : '';
    $confirm_password = isset( $_POST['confirm_password'] ) ? (string) wp_unslash( $_POST['confirm_password'] ) : '';

    if ( ! $access_token ) {
        wp_send_json_error( [ 'message' => __( 'Missing access token.', 'bw' ) ], 400 );
    }

    if ( ! $new_password || ! $confirm_password ) {
        wp_send_json_error( [ 'message' => __( 'Please enter and confirm your new password.', 'bw' ) ], 400 );
    }

    if ( $new_password !== $confirm_password ) {
        wp_send_json_error( [ 'message' => __( 'Passwords do not match.', 'bw' ) ], 400 );
    }

    if ( ! bw_mew_supabase_password_meets_requirements( $new_password ) ) {
        wp_send_json_error( [ 'message' => __( 'Password does not meet the requirements.', 'bw' ) ], 400 );
    }

    $response = bw_mew_supabase_update_user(
        $access_token,
        [ 'password' => $new_password ],
        'create-password'
    );

    if ( is_wp_error( $response ) ) {
        $status = (int) $response->get_error_data( 'status' );
        if ( $status < 400 || $status > 599 ) {
            $status = 500;
        }
        wp_send_json_error( [ 'message' => $response->get_error_message() ], $status );
    }

    wp_send_json_success(
        [
            'message' => __( 'Password updated.', 'bw' ),
            'user'    => isset( $response['payload'] ) && is_array( $response['payload'] ) ? $response['payload'] : [],
        ]
    );
}
add_action( 'wp_ajax_nopriv_bw_supabase_create_password', 'bw_mew_handle_supabase_create_password' );
add_action( 'wp_ajax_bw_supabase_create_password', 'bw_mew_handle_supabase_create_password' );

/**
 * Update Supabase email via AJAX.
 */
function bw_mew_handle_supabase_update_email() {
    check_ajax_referer( 'bw-supabase-login', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( [ 'message' => __( 'You must be logged in.', 'bw' ) ], 401 );
    }

    $user_id      = get_current_user_id();
    $access_token = bw_mew_get_supabase_access_token( $user_id );

    if ( ! $access_token ) {
        wp_send_json_error( [ 'message' => __( 'Supabase session is missing.', 'bw' ) ], 401 );
    }

    $email         = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $confirm_email = isset( $_POST['confirm_email'] ) ? sanitize_email( wp_unslash( $_POST['confirm_email'] ) ) : '';

    if ( ! $email ) {
        wp_send_json_error( [ 'message' => __( 'Please enter a valid email address.', 'bw' ) ], 400 );
    }

    if ( ! $confirm_email || strtolower( $email ) !== strtolower( $confirm_email ) ) {
        wp_send_json_error( [ 'message' => __( 'Email addresses do not match.', 'bw' ) ], 400 );
    }

    $response = bw_mew_supabase_update_user(
        $access_token,
        [ 'email' => $email ],
        'email-update'
    );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( [ 'message' => $response->get_error_message() ], 500 );
    }

    update_user_meta( $user_id, 'bw_supabase_pending_email', $email );

    wp_send_json_success(
        [
            'message'      => __( 'Please confirm your new email address from the email we sent.', 'bw' ),
            'pendingEmail' => $email,
        ]
    );
}
add_action( 'wp_ajax_bw_supabase_update_email', 'bw_mew_handle_supabase_update_email' );

/**
 * Check if the current session is authenticated.
 */
function bw_mew_handle_supabase_session_check() {
    check_ajax_referer( 'bw-supabase-login', 'nonce' );

    wp_send_json_success(
        [
            'loggedIn' => is_user_logged_in(),
        ]
    );
}
add_action( 'wp_ajax_bw_supabase_check_wp_session', 'bw_mew_handle_supabase_session_check' );
add_action( 'wp_ajax_nopriv_bw_supabase_check_wp_session', 'bw_mew_handle_supabase_session_check' );

/**
 * Invite Supabase users after guest checkout when orders become valid.
 *
 * @param int $order_id Order ID.
 */
function bw_mew_supabase_invite_trace( $order_id, $message, $debug_log = false ) {
    if ( ! $debug_log || ! $order_id ) {
        return;
    }

    $log_line = sprintf( 'Supabase invite trace (order %d): %s', (int) $order_id, (string) $message );
    error_log( $log_line );

    $trace_key = '_bw_supabase_invite_trace';
    $trace     = get_post_meta( $order_id, $trace_key, true );
    if ( ! is_array( $trace ) ) {
        $decoded = json_decode( (string) $trace, true );
        $trace   = is_array( $decoded ) ? $decoded : [];
    }

    $trace[] = gmdate( 'c' ) . ' ' . (string) $message;
    update_post_meta( $order_id, $trace_key, $trace );
}

function bw_mew_handle_supabase_checkout_invite( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order instanceof WC_Order ) {
        return;
    }

    $order_status = $order->get_status();
    if ( in_array( $order_status, [ 'failed', 'cancelled', 'refunded', 'trash' ], true ) ) {
        return;
    }

    $debug_log = (bool) get_option( 'bw_supabase_debug_log', 0 );
    $log_ctx   = sprintf( 'order %d', (int) $order_id );
    bw_mew_supabase_invite_trace( $order_id, sprintf( 'entered trigger (status=%s)', $order_status ), $debug_log );

    $provider = get_option( 'bw_account_login_provider', 'wordpress' );
    if ( 'supabase' !== $provider ) {
        if ( $debug_log ) {
            error_log( sprintf( 'Supabase invite skipped (%s): provider=%s', $log_ctx, $provider ) );
        }
        bw_mew_supabase_invite_trace( $order_id, sprintf( 'skip: provider=%s', $provider ), $debug_log );
        return;
    }

    $provision_enabled = get_option( 'bw_supabase_checkout_provision_enabled', '0' );
    if ( '1' !== $provision_enabled ) {
        if ( $debug_log ) {
            error_log( sprintf( 'Supabase invite skipped (%s): checkout provisioning disabled', $log_ctx ) );
        }
        bw_mew_supabase_invite_trace( $order_id, 'skip: checkout provisioning disabled', $debug_log );
        return;
    }

    $order_user_id = (int) $order->get_user_id();
    $order_user    = $order_user_id ? get_user_by( 'id', $order_user_id ) : null;
    $email         = sanitize_email( (string) $order->get_billing_email() );

    if ( ! $email && $order_user instanceof WP_User ) {
        $email = sanitize_email( (string) $order_user->user_email );
    }

    if ( ! $email ) {
        if ( $debug_log ) {
            error_log( sprintf( 'Supabase invite skipped (%s): missing billing email', $log_ctx ) );
        }
        bw_mew_supabase_invite_trace( $order_id, 'skip: missing billing email', $debug_log );
        return;
    }

    $config         = bw_mew_get_supabase_config();
    $service_key    = trim( (string) get_option( 'bw_supabase_service_role_key', '' ) );
    $redirect_to    = get_option( 'bw_supabase_invite_redirect_url', '' );
    $default_redirect = wc_get_page_permalink( 'myaccount' );
    $redirect_to    = $redirect_to ? $redirect_to : $default_redirect;
    $last_invite_at = (int) $order->get_meta( '_bw_supabase_invite_sent_at' );
    $now            = time();
    $min_interval   = 10 * MINUTE_IN_SECONDS;

    if ( empty( $config['has_url'] ) || ! $service_key ) {
        if ( $debug_log ) {
            error_log(
                sprintf(
                    'Supabase invite skipped (missing config). Order %d, email %s, redirect %s',
                    $order_id,
                    $email,
                    $redirect_to
                )
            );
        }
        bw_mew_supabase_invite_trace( $order_id, 'skip: missing config (project_url/service_role)', $debug_log );
        return;
    }

    // Resolve user by billing email first, to avoid false positives when an order is
    // associated with an already-onboarded account that differs from billing email.
    $user = get_user_by( 'email', $email );

    if ( ! $user instanceof WP_User && $order_user instanceof WP_User ) {
        $order_user_email = sanitize_email( (string) $order_user->user_email );
        if ( $order_user_email && strtolower( $order_user_email ) === strtolower( $email ) ) {
            $user = $order_user;
        }
    }

    if ( $user instanceof WP_User ) {
        $onboarded = (int) get_user_meta( $user->ID, 'bw_supabase_onboarded', true );
        if ( 1 === $onboarded ) {
            if ( $debug_log ) {
                error_log( sprintf( 'Supabase invite skipped (%s): user %d already onboarded', $log_ctx, (int) $user->ID ) );
            }
            bw_mew_supabase_invite_trace( $order_id, sprintf( 'skip: user %d already onboarded', (int) $user->ID ), $debug_log );
            return;
        }
    }

    if ( $order->get_meta( '_bw_supabase_invite_sent' ) && $last_invite_at && ( $now - $last_invite_at ) < $min_interval ) {
        if ( $debug_log ) {
            error_log( sprintf( 'Supabase invite skipped (%s): throttled (%d seconds since last send)', $log_ctx, (int) ( $now - $last_invite_at ) ) );
        }
        bw_mew_supabase_invite_trace( $order_id, 'skip: throttled', $debug_log );
        return;
    }

    if ( ! $user instanceof WP_User ) {
        $base_username = sanitize_user( current( explode( '@', $email ) ), true );
        $username      = $base_username ?: 'customer';
        $suffix        = 1;

        while ( username_exists( $username ) ) {
            $username = $base_username ? $base_username . $suffix : 'customer' . $suffix;
            $suffix++;
        }

        $user_id = wp_create_user( $username, wp_generate_password( 32, true ), $email );
        if ( ! is_wp_error( $user_id ) ) {
            $user = get_user_by( 'id', $user_id );
            if ( $user instanceof WP_User ) {
                $user->set_role( 'customer' );
                bw_mew_supabase_invite_trace( $order_id, sprintf( 'created WP user %d for %s', (int) $user_id, $email ), $debug_log );
            }
        } else {
            bw_mew_supabase_invite_trace( $order_id, sprintf( 'wp_create_user failed: %s', $user_id->get_error_message() ), $debug_log );
        }
    }

    $result = bw_mew_send_supabase_invite(
        [
            'email'       => $email,
            'redirect_to' => $redirect_to,
            'service_key' => $service_key,
            'project_url' => $config['project_url'],
            'debug_log'   => $debug_log,
            'context'     => $log_ctx,
        ]
    );

    if ( 'sent' === $result['status'] ) {
        bw_mew_supabase_invite_trace( $order_id, 'invite sent to Supabase', $debug_log );
        $order->update_meta_data( '_bw_supabase_invite_sent', 1 );
        $order->update_meta_data( '_bw_supabase_invite_sent_at', $now );
        $order->delete_meta_data( '_bw_supabase_invite_error' );
        $resend_count = (int) $order->get_meta( '_bw_supabase_invite_resend_count' );
        $order->update_meta_data( '_bw_supabase_invite_resend_count', $resend_count + 1 );
        $order->save();

        if ( $user instanceof WP_User ) {
            update_user_meta( $user->ID, 'bw_supabase_invited', 1 );
            update_user_meta( $user->ID, 'bw_supabase_invite_sent_at', $now );
            update_user_meta( $user->ID, 'bw_supabase_invite_resend_count', $resend_count + 1 );
            update_user_meta( $user->ID, 'bw_supabase_onboarded', 0 );
            if ( $result['user_id'] ) {
                update_user_meta( $user->ID, 'bw_supabase_user_id', sanitize_text_field( $result['user_id'] ) );
            }
        }

        return;
    }

    if ( 'exists' === $result['status'] ) {
        bw_mew_supabase_invite_trace( $order_id, 'invite status=exists (already exists)', $debug_log );
        $order->update_meta_data( '_bw_supabase_invite_sent', 1 );
        $order->update_meta_data( '_bw_supabase_invite_sent_at', $now );
        $order->save();
        return;
    }

    $order->update_meta_data( '_bw_supabase_invite_error', $result['body'] ?? '' );
    bw_mew_supabase_invite_trace( $order_id, 'invite failed (see _bw_supabase_invite_error)', $debug_log );
    $order->save();
}
add_action( 'woocommerce_order_status_processing', 'bw_mew_handle_supabase_checkout_invite', 10, 1 );
add_action( 'woocommerce_order_status_completed', 'bw_mew_handle_supabase_checkout_invite', 10, 1 );
add_action( 'woocommerce_order_status_on-hold', 'bw_mew_handle_supabase_checkout_invite', 10, 1 );
add_action( 'woocommerce_payment_complete', 'bw_mew_handle_supabase_checkout_invite', 10, 1 );
add_action( 'woocommerce_thankyou', 'bw_mew_handle_supabase_checkout_invite', 20, 1 );

/**
 * Send Supabase invite via Admin API.
 *
 * @param array $args Invite args.
 *
 * @return array{status:string,user_id:string,body:string}
 */
function bw_mew_send_supabase_invite( array $args ) {
    $email       = $args['email'] ?? '';
    $redirect_to = $args['redirect_to'] ?? '';
    $service_key = $args['service_key'] ?? '';
    $project_url = $args['project_url'] ?? '';
    $debug_log   = (bool) ( $args['debug_log'] ?? false );
    $context     = $args['context'] ?? 'manual';
    $force_admin = (bool) ( $args['force_admin'] ?? false );
    $user_id     = '';

    if ( ! $email || ! $project_url || ! $service_key ) {
        return [
            'status'  => 'invalid',
            'user_id' => '',
            'body'    => '',
        ];
    }

    $project_url = preg_replace( '#/auth/v1/?$#', '', untrailingslashit( (string) $project_url ) );
    $primary_endpoint = $force_admin ? $project_url . '/auth/v1/admin/invite' : $project_url . '/auth/v1/invite';
    $fallback_endpoint = $force_admin ? $project_url . '/auth/v1/invite' : $project_url . '/auth/v1/admin/invite';
    $redirect_to = $redirect_to ? $redirect_to : '';
    $redirect_to = bw_mew_supabase_sanitize_redirect_url( $redirect_to );
    if ( $debug_log && $redirect_to && false !== strpos( $redirect_to, '?' ) ) {
        error_log( sprintf( 'Supabase invite redirect_to contains query params (%s): %s', $context, $redirect_to ) );
    }

    if ( $debug_log ) {
        error_log(
            sprintf(
                'Supabase invite primary endpoint (%s): %s',
                $context,
                $primary_endpoint
            )
        );
        error_log(
            sprintf(
                'Supabase invite redirect_to (%s): %s',
                $context,
                $redirect_to ? $redirect_to : 'empty'
            )
        );
    }

    $request_args = [
        'headers' => [
            'apikey'       => $service_key,
            'Authorization' => 'Bearer ' . $service_key,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ],
        'timeout' => 15,
        'body'    => wp_json_encode(
            [
                'email' => $email,
                'redirect_to' => $redirect_to,
                'data' => $redirect_to ? [ 'redirect_to' => $redirect_to ] : new stdClass(),
            ]
        ),
    ];

    $response = wp_remote_post( $primary_endpoint, $request_args );
    $status_code = is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response );
    $final_endpoint = $primary_endpoint;

    $should_try_fallback = ! is_wp_error( $response )
        && $fallback_endpoint !== $primary_endpoint
        && ( (int) $status_code < 200 || (int) $status_code >= 300 );

    if ( $should_try_fallback ) {
        if ( $debug_log ) {
            error_log(
                sprintf(
                    'Supabase invite fallback endpoint (%s): %s (primary status %d)',
                    $context,
                    $fallback_endpoint,
                    (int) $status_code
                )
            );
        }
        $response = wp_remote_post( $fallback_endpoint, $request_args );
        $final_endpoint = $fallback_endpoint;
    }

    if ( is_wp_error( $response ) ) {
        if ( $debug_log ) {
            error_log(
                sprintf(
                    'Supabase invite error (%s, email %s): %s',
                    $context,
                    $email,
                    $response->get_error_message()
                )
            );
        }

        return [
            'status'  => 'error',
            'user_id' => '',
            'body'    => '',
        ];
    }

    $status_code = wp_remote_retrieve_response_code( $response );
    $body        = wp_remote_retrieve_body( $response );
    $payload     = json_decode( $body, true );
    $already_exists = false;

    if ( is_array( $payload ) ) {
        $message = $payload['msg'] ?? $payload['message'] ?? '';
        if ( is_string( $message ) && false !== stripos( $message, 'already' ) ) {
            $already_exists = true;
        }
        if ( isset( $payload['id'] ) ) {
            $user_id = (string) $payload['id'];
        }
    }

    if ( $status_code >= 200 && $status_code < 300 ) {
        if ( $debug_log ) {
            error_log(
                sprintf(
                    'Supabase invite sent (%s). Email %s, endpoint %s, status %d',
                    $context,
                    $email,
                    $final_endpoint,
                    $status_code
                )
            );
        }

        return [
            'status'  => 'sent',
            'user_id' => $user_id,
            'body'    => $body,
        ];
    }

    if ( $already_exists ) {
        if ( $debug_log ) {
            error_log(
                sprintf(
                    'Supabase invite skipped (user exists, %s). Email %s, endpoint %s, status %d',
                    $context,
                    $email,
                    $final_endpoint,
                    $status_code
                )
            );
        }

        return [
            'status'  => 'exists',
            'user_id' => $user_id,
            'body'    => $body,
        ];
    }

    if ( $debug_log ) {
        error_log(
            sprintf(
                'Supabase invite failed (%s). Email %s, endpoint %s, status %d',
                $context,
                $email,
                $final_endpoint,
                $status_code
            )
        );
        error_log( sprintf( 'Supabase invite response body (%s): %s', $context, $body ) );
    }

    return [
        'status'  => 'error',
        'user_id' => $user_id,
        'body'    => $body,
    ];
}

/**
 * Resend Supabase invite via AJAX.
 */
function bw_mew_handle_supabase_resend_invite() {
    check_ajax_referer( 'bw-supabase-login', 'nonce' );

    $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    if ( ! $email ) {
        wp_send_json_error(
            [ 'message' => __( 'Email is required.', 'bw' ) ],
            400
        );
    }

    $rate_key = 'bw_supabase_invite_resend_' . md5( strtolower( $email ) );
    if ( get_transient( $rate_key ) ) {
        wp_send_json_error(
            [ 'message' => __( 'Please wait a moment before requesting another invite.', 'bw' ) ],
            429
        );
    }

    $config      = bw_mew_get_supabase_config();
    $service_key = trim( (string) get_option( 'bw_supabase_service_role_key', '' ) );
    $debug_log   = (bool) get_option( 'bw_supabase_debug_log', 0 );
    $redirect_to = get_option( 'bw_supabase_invite_redirect_url', '' );
    $default_redirect = wc_get_page_permalink( 'myaccount' );
    $redirect_to = $redirect_to ? $redirect_to : $default_redirect;

    if ( empty( $config['has_url'] ) || ! $service_key ) {
        wp_send_json_error(
            [ 'message' => __( 'Supabase is not configured for invites.', 'bw' ) ],
            400
        );
    }

    $result = bw_mew_send_supabase_invite(
        [
            'email'       => $email,
            'redirect_to' => $redirect_to,
            'service_key' => $service_key,
            'project_url' => $config['project_url'],
            'debug_log'   => $debug_log,
            'context'     => 'manual resend',
            'force_admin' => true,
        ]
    );

    if ( 'sent' === $result['status'] ) {
        set_transient( $rate_key, 1, 2 * MINUTE_IN_SECONDS );
        $user = get_user_by( 'email', $email );
        if ( $user instanceof WP_User ) {
            update_user_meta( $user->ID, 'bw_supabase_invited', 1 );
            update_user_meta( $user->ID, 'bw_supabase_invite_sent_at', time() );
            update_user_meta( $user->ID, 'bw_supabase_onboarded', 0 );
        }
        wp_send_json_success(
            [ 'message' => __( 'Invite sent. Please check your email.', 'bw' ) ]
        );
    }

    if ( 'exists' === $result['status'] ) {
        wp_send_json_error(
            [ 'message' => __( 'User already exists. Please use the login or reset password flow.', 'bw' ) ],
            409
        );
    }

    wp_send_json_error(
        [ 'message' => __( 'Unable to send invite. Please try again later.', 'bw' ) ],
        500
    );
}
add_action( 'wp_ajax_nopriv_bw_supabase_resend_invite', 'bw_mew_handle_supabase_resend_invite' );
add_action( 'wp_ajax_bw_supabase_resend_invite', 'bw_mew_handle_supabase_resend_invite' );

/**
 * AJAX: Check if Supabase password gating modal should be shown.
 *
 * Returns enabled=false if provider is WordPress.
 * Returns needs_password=true if user has not completed onboarding.
 */
function bw_mew_handle_get_password_status() {
    check_ajax_referer( 'bw-supabase-login', 'nonce' );

    $provider = get_option( 'bw_account_login_provider', 'wordpress' );

    // If provider is WordPress, no modal needed
    if ( 'supabase' !== $provider ) {
        wp_send_json_success( [
            'enabled'        => false,
            'needs_password' => false,
        ] );
    }

    // Must be logged in
    if ( ! is_user_logged_in() ) {
        wp_send_json_success( [
            'enabled'        => true,
            'needs_password' => false,
        ] );
    }

    $user_id        = get_current_user_id();
    $needs_password = function_exists( 'bw_user_needs_onboarding' )
        ? bw_user_needs_onboarding( $user_id )
        : ( 1 !== (int) get_user_meta( $user_id, 'bw_supabase_onboarded', true ) );

    wp_send_json_success( [
        'enabled'        => true,
        'needs_password' => $needs_password,
    ] );
}
add_action( 'wp_ajax_bw_get_password_status', 'bw_mew_handle_get_password_status' );
add_action( 'wp_ajax_nopriv_bw_get_password_status', 'bw_mew_handle_get_password_status' );

/**
 * AJAX: Set Supabase password from gating modal.
 *
 * Updates password in Supabase and marks user as onboarded.
 */
function bw_mew_handle_set_password_modal() {
    check_ajax_referer( 'bw-supabase-login', 'nonce' );

    // Check provider
    $provider = get_option( 'bw_account_login_provider', 'wordpress' );
    if ( 'supabase' !== $provider ) {
        wp_send_json_error( [ 'message' => __( 'Password management is handled by WordPress.', 'bw' ) ], 400 );
    }

    // Must be logged in
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( [ 'message' => __( 'You must be logged in.', 'bw' ) ], 401 );
    }

    $user_id      = get_current_user_id();
    $access_token = bw_mew_get_supabase_access_token( $user_id );

    if ( ! $access_token ) {
        wp_send_json_error( [ 'message' => __( 'Supabase session is missing. Please log in again.', 'bw' ) ], 401 );
    }

    $new_password     = isset( $_POST['new_password'] ) ? (string) wp_unslash( $_POST['new_password'] ) : '';
    $confirm_password = isset( $_POST['confirm_password'] ) ? (string) wp_unslash( $_POST['confirm_password'] ) : '';

    if ( ! $new_password || ! $confirm_password ) {
        wp_send_json_error( [ 'message' => __( 'Please enter and confirm your password.', 'bw' ) ], 400 );
    }

    if ( $new_password !== $confirm_password ) {
        wp_send_json_error( [ 'message' => __( 'Passwords do not match.', 'bw' ) ], 400 );
    }

    if ( strlen( $new_password ) < 8 ) {
        wp_send_json_error( [ 'message' => __( 'Password must be at least 8 characters.', 'bw' ) ], 400 );
    }

    // Update password in Supabase
    $response = bw_mew_supabase_update_user(
        $access_token,
        [ 'password' => $new_password ],
        'password-modal'
    );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( [ 'message' => $response->get_error_message() ], 500 );
    }

    // Mark user as onboarded
    update_user_meta( $user_id, 'bw_supabase_onboarded', 1 );
    delete_user_meta( $user_id, 'bw_supabase_invited' );
    delete_user_meta( $user_id, 'bw_supabase_invite_error' );
    delete_user_meta( $user_id, 'bw_supabase_onboarding_error' );

    wp_send_json_success( [
        'message' => __( 'Password saved successfully.', 'bw' ),
    ] );
}
add_action( 'wp_ajax_bw_set_password_modal', 'bw_mew_handle_set_password_modal' );

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
    $create_users = (bool) get_option( 'bw_supabase_create_wp_users', 1 );
    $user        = null;

    if ( $link_users && $user_email ) {
        $user = get_user_by( 'email', $user_email );
        if ( ! $user && $create_users ) {
            $user_id = wp_create_user( $user_email, wp_generate_password( 32, true ), $user_email );
            if ( ! is_wp_error( $user_id ) ) {
                $user = get_user_by( 'id', $user_id );
            }
        }
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

/**
 * Clear Supabase session cookies on logout.
 */
function bw_mew_clear_supabase_session_cookies() {
    $cookie_base = get_option( 'bw_supabase_jwt_cookie_name', 'bw_supabase_session' );
    $cookie_base = sanitize_key( $cookie_base ) ?: 'bw_supabase_session';
    $secure      = is_ssl();
    $path        = COOKIEPATH ? COOKIEPATH : '/';
    $domain      = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';
    $cookie_args = [
        'expires'  => time() - HOUR_IN_SECONDS,
        'path'     => $path,
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ];

    if ( $domain ) {
        $cookie_args['domain'] = $domain;
    }

    setcookie( $cookie_base . '_access', '', $cookie_args );
    setcookie( $cookie_base . '_refresh', '', $cookie_args );
}
add_action( 'wp_logout', 'bw_mew_clear_supabase_session_cookies' );
