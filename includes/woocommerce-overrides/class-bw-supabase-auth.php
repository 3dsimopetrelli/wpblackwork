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

    if ( 'usermeta' === $storage && $user_id ) {
        $token = (string) get_user_meta( $user_id, 'bw_supabase_access_token', true );
        return $token ? sanitize_text_field( $token ) : '';
    }

    $cookie_name = $cookie_base . '_access';
    if ( isset( $_COOKIE[ $cookie_name ] ) ) {
        return sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
    }

    return '';
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
    check_ajax_referer( 'bw-supabase-login', 'nonce' );

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

    wp_set_current_user( $user->ID );
    wp_set_auth_cookie( $user->ID, true, is_ssl() );
    update_user_meta( $user->ID, 'bw_supabase_onboarded', 1 );
    delete_user_meta( $user->ID, 'bw_supabase_invite_error' );
    delete_user_meta( $user->ID, 'bw_supabase_onboarding_error' );

    if ( $debug_log ) {
        error_log( 'Supabase token login success → set onboarded=1 → redirect /my-account/' );
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
    update_user_meta( $user->ID, 'bw_supabase_onboarded', 1 );
    delete_user_meta( $user->ID, 'bw_supabase_invite_error' );
    delete_user_meta( $user->ID, 'bw_supabase_onboarding_error' );
    bw_mew_apply_supabase_user_to_wp( $user->ID, $payload, 'token-login' );

    if ( $debug_log ) {
        error_log( 'Supabase token login success → set onboarded=1 → redirect /my-account/' );
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
    update_user_meta( $user->ID, 'bw_supabase_onboarded', 1 );
    delete_user_meta( $user->ID, 'bw_supabase_invite_error' );
    delete_user_meta( $user->ID, 'bw_supabase_onboarding_error' );
    bw_mew_apply_supabase_user_to_wp( $user->ID, $payload, 'token-login' );

    if ( $debug_log ) {
        error_log( 'Supabase token login success → set onboarded=1 → redirect /my-account/' );
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

    $already_onboarded = function_exists( 'bw_user_needs_onboarding' )
        ? ! bw_user_needs_onboarding( $user->ID )
        : ( 1 === (int) get_user_meta( $user->ID, 'bw_supabase_onboarded', true ) );
    $is_invite = 'invite' === $token_type;

    if ( $is_invite && ! $already_onboarded ) {
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
    $redirect_url     = $needs_onboarding
        ? wc_get_account_endpoint_url( 'set-password' )
        : wc_get_page_permalink( 'myaccount' );

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

    $already_onboarded = function_exists( 'bw_user_needs_onboarding' )
        ? ! bw_user_needs_onboarding( $user->ID )
        : ( 1 === (int) get_user_meta( $user->ID, 'bw_supabase_onboarded', true ) );
    $is_invite = 'invite' === $token_type;

    if ( $is_invite && ! $already_onboarded ) {
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
    $redirect_url     = $needs_onboarding
        ? wc_get_account_endpoint_url( 'set-password' )
        : wc_get_page_permalink( 'myaccount' );

    if ( $debug_log ) {
        error_log(
            sprintf(
                'Supabase token login success (type=%s) → redirect %s',
                $token_type ? $token_type : 'none',
                $redirect_url
            )
        );
    }

    wp_send_json_success(
        [
            'redirect' => $redirect_url,
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

    $config              = bw_mew_get_supabase_config();
    $debug_log           = (bool) get_option( 'bw_supabase_debug_log', 0 );
    $registration_mode   = get_option( 'bw_supabase_registration_mode', 'R2' );
    $supabase_with_oidc  = (bool) get_option( 'bw_supabase_with_plugins', 0 );

    if ( 'R1' === $registration_mode ) {
        wp_send_json_error(
            array_merge(
                [ 'message' => __( 'Registration is handled by the provider signup flow.', 'bw' ) ],
                bw_mew_supabase_debug_payload( $config )
            ),
            400
        );
    }

    if ( empty( $config['has_url'] ) || empty( $config['has_anon'] ) ) {
        if ( $debug_log ) {
            $context = bw_mew_supabase_build_diagnostics( $config );
            $context['registration_mode'] = $registration_mode;
            $context['supabase_with_oidc'] = $supabase_with_oidc ? 'yes' : 'no';
            error_log( sprintf( 'Supabase config missing (register): %s', wp_json_encode( $context ) ) );
        }

        wp_send_json_error(
            array_merge(
                [ 'message' => __( 'Supabase is not configured yet.', 'bw' ) ],
                bw_mew_supabase_debug_payload( $config )
            ),
            400
        );
    }

    $confirm_redirect = bw_mew_supabase_sanitize_redirect_url(
        get_option( 'bw_supabase_email_confirm_redirect_url', site_url( '/my-account/?bw_email_confirmed=1' ) )
    );

    // Supabase signup endpoint (server-side).
    $endpoint = trailingslashit( untrailingslashit( $config['project_url'] ) ) . 'auth/v1/signup';

    if ( $confirm_redirect ) {
        $endpoint = add_query_arg( 'redirect_to', rawurlencode( $confirm_redirect ), $endpoint );
    }

    $payload_body = [
        'email'    => $email,
        'password' => $password,
    ];

    if ( $debug_log ) {
        $redirect_for_log = $confirm_redirect ? $confirm_redirect : 'empty';
        $payload_keys     = implode( ', ', array_keys( $payload_body ) );
        error_log( sprintf( 'Supabase register redirect: %s', $redirect_for_log ) );
        error_log( sprintf( 'Supabase register signup URL: %s', $endpoint ) );
        error_log( sprintf( 'Supabase register payload keys: %s', $payload_keys ) );
    }

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
                $payload_body
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

    // Store tokens if returned (signup can also require email confirmation).
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
 * Handle Supabase password recovery via AJAX.
 */
function bw_mew_handle_supabase_recover() {
    check_ajax_referer( 'bw-supabase-login', 'nonce' );

    if ( is_user_logged_in() ) {
        wp_send_json_error(
            [ 'message' => __( 'You are already logged in.', 'bw' ) ],
            400
        );
    }

    $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

    if ( ! $email && is_user_logged_in() ) {
        $email = wp_get_current_user()->user_email;
    }

    if ( ! $email ) {
        wp_send_json_error(
            [ 'message' => __( 'Email is required.', 'bw' ) ],
            400
        );
    }

    $config    = bw_mew_get_supabase_config();
    $debug_log = (bool) get_option( 'bw_supabase_debug_log', 0 );

    if ( empty( $config['has_url'] ) || empty( $config['has_anon'] ) ) {
        if ( $debug_log ) {
            error_log( sprintf( 'Supabase config missing (recover): %s', wp_json_encode( bw_mew_supabase_build_diagnostics( $config ) ) ) );
        }

        wp_send_json_error(
            array_merge(
                [ 'message' => __( 'Supabase is not configured yet.', 'bw' ) ],
                bw_mew_supabase_debug_payload( $config )
            ),
            400
        );
    }

    // Supabase recover endpoint (server-side).
    $endpoint = trailingslashit( untrailingslashit( $config['project_url'] ) ) . 'auth/v1/recover';

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
                    'email' => $email,
                ]
            ),
        ]
    );

    if ( is_wp_error( $response ) ) {
        if ( $debug_log ) {
            error_log( sprintf( 'Supabase recover error: %s', $response->get_error_message() ) );
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
        error_log( sprintf( 'Supabase recover status: %d', (int) $status_code ) );
    }

    if ( $status_code < 200 || $status_code >= 300 ) {
        $message = __( 'Unable to send reset email.', 'bw' );

        if ( is_array( $payload ) ) {
            $message = $payload['error_description'] ?? $payload['msg'] ?? $message;
        }

        wp_send_json_error(
            [ 'message' => $message ],
            401
        );
    }

    wp_send_json_success(
        [
            'message' => __( 'If the email exists, a reset link has been sent.', 'bw' ),
        ]
    );
}
add_action( 'wp_ajax_nopriv_bw_supabase_recover', 'bw_mew_handle_supabase_recover' );
add_action( 'wp_ajax_bw_supabase_recover', 'bw_mew_handle_supabase_recover' );

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
        return new WP_Error( 'bw_supabase_update_failed', __( 'Supabase update failed.', 'bw' ) );
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
 * Invite Supabase users after guest checkout when orders become valid.
 *
 * @param int $order_id Order ID.
 */
function bw_mew_handle_supabase_checkout_invite( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order instanceof WC_Order ) {
        return;
    }

    if ( $order->get_user_id() ) {
        return;
    }

    $provision_enabled = get_option( 'bw_supabase_checkout_provision_enabled', '0' );
    if ( '1' !== $provision_enabled ) {
        return;
    }

    $email = $order->get_billing_email();
    if ( ! $email ) {
        return;
    }

    $config         = bw_mew_get_supabase_config();
    $service_key    = trim( (string) get_option( 'bw_supabase_service_role_key', '' ) );
    $debug_log      = (bool) get_option( 'bw_supabase_debug_log', 0 );
    $redirect_to    = get_option( 'bw_supabase_invite_redirect_url', '' );
    $default_redirect = home_url( '/my-account/set-password/' );
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
        return;
    }

    $user = get_user_by( 'email', $email );
    if ( $user instanceof WP_User ) {
        $onboarded = (int) get_user_meta( $user->ID, 'bw_supabase_onboarded', true );
        if ( 1 === $onboarded ) {
            return;
        }
    }

    if ( $order->get_meta( '_bw_supabase_invite_sent' ) && $last_invite_at && ( $now - $last_invite_at ) < $min_interval ) {
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
            }
        }
    }

    $result = bw_mew_send_supabase_invite(
        [
            'email'       => $email,
            'redirect_to' => $redirect_to,
            'service_key' => $service_key,
            'project_url' => $config['project_url'],
            'debug_log'   => $debug_log,
            'context'     => sprintf( 'order %d', $order_id ),
        ]
    );

    if ( 'sent' === $result['status'] ) {
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
        $order->update_meta_data( '_bw_supabase_invite_sent', 1 );
        $order->update_meta_data( '_bw_supabase_invite_sent_at', $now );
        $order->save();
        return;
    }

    $order->update_meta_data( '_bw_supabase_invite_error', $result['body'] ?? '' );
    $order->save();
}
add_action( 'woocommerce_order_status_processing', 'bw_mew_handle_supabase_checkout_invite', 10, 1 );
add_action( 'woocommerce_order_status_completed', 'bw_mew_handle_supabase_checkout_invite', 10, 1 );

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

    if ( ! is_wp_error( $response ) && 404 === (int) $status_code ) {
        if ( $debug_log ) {
            error_log(
                sprintf(
                    'Supabase invite fallback endpoint (%s): %s',
                    $context,
                    $fallback_endpoint
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
    $default_redirect = home_url( '/my-account/set-password/' );
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
