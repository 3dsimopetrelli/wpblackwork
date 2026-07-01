<?php
/**
 * WooCommerce runtime — Supabase bridge + social login.
 *
 * Extracted verbatim from woocommerce-init.php (Phase 3b, BW-TASK-20260629). Function bodies are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue Supabase invite token bridge on the frontend.
 */
function bw_mew_enqueue_supabase_bridge()
{
    if (is_user_logged_in()) {
        return;
    }

    $js_file = BW_MEW_PATH . 'assets/js/bw-supabase-bridge.js';
    if (!file_exists($js_file)) {
        return;
    }

    // Ensure the dependency exists on every landing page (home included),
    // otherwise the bridge script is skipped by WP when invites redirect there.
    if (!wp_script_is('supabase-js', 'registered')) {
        wp_register_script(
            'supabase-js',
            'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2',
            [],
            null,
            true
        );
    }
    if (!wp_script_is('supabase-js', 'enqueued')) {
        wp_enqueue_script('supabase-js');
    }

    wp_enqueue_script(
        'bw-supabase-bridge',
        BW_MEW_URL . 'assets/js/bw-supabase-bridge.js',
        ['supabase-js'],
        filemtime($js_file),
        true
    );

    $expired_link_url = trim((string) get_option('bw_supabase_expired_link_redirect_url', ''));
    if (!$expired_link_url) {
        $expired_link_url = site_url('/link-expired/');
    }

    $account_url  = wc_get_page_permalink( 'myaccount' );
    $callback_url = $account_url ? add_query_arg( 'bw_auth_callback', '1', $account_url ) : site_url( '/my-account/?bw_auth_callback=1' );

    wp_localize_script(
        'bw-supabase-bridge',
        'bwSupabaseBridge',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bw-supabase-login'),
            'accountUrl' => $account_url,
            'callbackUrl' => $callback_url,
            'setPasswordUrl' => wc_get_account_endpoint_url('set-password'),
            'expiredLinkUrl' => $expired_link_url,
            'projectUrl' => get_option('bw_supabase_project_url', ''),
            'anonKey' => get_option('bw_supabase_anon_key', ''),
            'debug' => (int) get_option('bw_supabase_debug_log', 0),
        ]
    );
}

/**
 * Early invite redirect to avoid home-page flash before My Account bridge.
 *
 * Supabase invite links may land on non-account pages with hash tokens.
 * We move the browser to /my-account/ immediately (keeping hash) so the
 * bridge and password modal flow starts without visible intermediate page.
 */
function bw_mew_supabase_early_invite_redirect_hint()
{
    $account_url = wc_get_page_permalink('myaccount');
    if (!$account_url) {
        return;
    }
    $callback_url = add_query_arg( 'bw_auth_callback', '1', $account_url );
    $set_password_url = wc_get_account_endpoint_url('set-password');
    if (!$set_password_url) {
        $set_password_url = $account_url;
    }
    $expired_link_url = trim((string) get_option('bw_supabase_expired_link_redirect_url', ''));
    if (!$expired_link_url) {
        $expired_link_url = site_url('/link-expired/');
    }
    $is_logged_in = is_user_logged_in();

    ?>
    <style id="bw-supabase-auth-preload">
        html.bw-auth-preload body.woocommerce-account .bw-account-login__content {
            visibility: hidden !important;
        }

        html.bw-auth-preload body.woocommerce-account .bw-auth-callback {
            display: flex !important;
            min-height: 60vh;
            align-items: center;
            justify-content: center;
        }
    </style>
    <script>
    (function () {
        var isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
        var normalizePath = function (path) {
            return (path || '').replace(/\/+$/, '') || '/';
        };
        var accountPath = normalizePath(new URL(<?php echo wp_json_encode($account_url); ?>, window.location.origin).pathname);
        var currentUrl = new URL(window.location.href);
        var currentPath = normalizePath(currentUrl.pathname);
        var onAccountPage = currentPath === accountPath;
        var search = currentUrl.searchParams;
        var callbackMode = search.get('bw_auth_callback') === '1';
        var setPasswordMode = search.get('bw_set_password') === '1';
        var code = search.get('code') || '';
        var typeFromQuery = search.get('type') || '';
        var hash = window.location.hash || '';
        var hashParams = hash ? new URLSearchParams(hash.replace(/^#/, '')) : null;
        var hashType = hashParams ? (hashParams.get('type') || '') : '';
        var hasInviteHash = !!hash && hash.indexOf('access_token=') !== -1 && (hashType === 'invite' || hashType === 'recovery');
        var hasInviteCode = !!code && (typeFromQuery === 'invite' || typeFromQuery === 'recovery');

        try {
            if (window.sessionStorage) {
                if (isLoggedIn) {
                    sessionStorage.removeItem('bw_auth_in_progress');
                }
                if (hasInviteHash || hasInviteCode || callbackMode || setPasswordMode) {
                    sessionStorage.setItem('bw_auth_in_progress', '1');
                }
                if (search.get('logged_out') === '1') {
                    sessionStorage.removeItem('bw_auth_in_progress');
                }
            }
        } catch (e) {}

        var authInProgress = false;
        try {
            authInProgress = !!(window.sessionStorage && sessionStorage.getItem('bw_auth_in_progress') === '1');
        } catch (e) {}

        // Stale callback URL (common after logout): no auth payload available.
        // Avoid showing the callback loader forever and go back to clean My Account.
        if (!isLoggedIn && onAccountPage && callbackMode && !setPasswordMode && !hasInviteHash && !hasInviteCode) {
            try {
                if (window.sessionStorage) {
                    sessionStorage.removeItem('bw_auth_in_progress');
                }
            } catch (e) {}
            window.location.replace(<?php echo wp_json_encode($account_url); ?>);
            return;
        }

        if (!isLoggedIn && onAccountPage && (authInProgress || hasInviteHash || hasInviteCode || callbackMode || setPasswordMode)) {
            document.documentElement.classList.add('bw-auth-preload');
        }

        if (!isLoggedIn && onAccountPage && authInProgress && !callbackMode && !setPasswordMode && !hasInviteHash && !hasInviteCode) {
            window.location.replace(<?php echo wp_json_encode($callback_url); ?>);
            return;
        }

        if (code && (typeFromQuery === 'invite' || typeFromQuery === 'recovery')) {
            if (currentUrl.searchParams.get('bw_auth_callback') !== '1') {
                var codeTarget = new URL(<?php echo wp_json_encode($callback_url); ?>, window.location.origin);
                codeTarget.searchParams.set('code', code);
                codeTarget.searchParams.set('type', typeFromQuery);

                var state = currentUrl.searchParams.get('state') || '';
                var provider = currentUrl.searchParams.get('provider') || '';
                if (state) {
                    codeTarget.searchParams.set('state', state);
                }
                if (provider) {
                    codeTarget.searchParams.set('provider', provider);
                }

                window.location.replace(codeTarget.toString());
            }
            return;
        }

        if (!hash) {
            return;
        }

        var params = new URLSearchParams(hash.replace(/^#/, ''));
        var errorCode = params.get('error_code') || '';
        if (errorCode === 'otp_expired') {
            var targetBase = isLoggedIn
                ? <?php echo wp_json_encode($set_password_url); ?>
                : <?php echo wp_json_encode($expired_link_url); ?>;
            var targetUrl = new URL(targetBase, window.location.origin);
            if (isLoggedIn) {
                targetUrl.searchParams.set('bw_set_password', '1');
            }
            window.location.replace(targetUrl.toString());
            return;
        }

        if (hash.indexOf('access_token=') === -1) {
            return;
        }

        var type = params.get('type') || '';
        if (type !== 'invite' && type !== 'recovery') {
            return;
        }

        var target = new URL(<?php echo wp_json_encode($callback_url); ?>, window.location.origin);
        var current = new URL(window.location.href);
        var targetPath = target.pathname.replace(/\/+$/, '');
        var currentPath = current.pathname.replace(/\/+$/, '');

        if (targetPath === currentPath && current.search.indexOf('bw_auth_callback=1') !== -1) {
            return;
        }

        target.hash = hash.replace(/^#/, '');
        window.location.replace(target.toString());
    })();
    </script>
    <?php
}

/**
 * Handle social login start and callback.
 *
 * @deprecated Use BW_Social_Login class instead.
 */
function bw_mew_handle_social_login_requests()
{
    if (!function_exists('is_account_page') || !is_account_page()) {
        return;
    }

    if (isset($_GET['bw_social_login'])) {
        bw_mew_social_login_redirect(sanitize_key(wp_unslash($_GET['bw_social_login'])));
    }

    if (isset($_GET['bw_social_login_callback'])) {
        bw_mew_process_social_login_callback(sanitize_key(wp_unslash($_GET['bw_social_login_callback'])));
    }
}

/**
 * Build social login start URL.
 *
 * @param string $provider Social provider key.
 * @return string
 */
function bw_mew_get_social_login_url($provider)
{
    if (!class_exists('BW_Social_Login')) {
        return '';
    }

    return BW_Social_Login::get_login_url($provider);
}

/**
 * Get the callback URL for the provider.
 *
 * @param string $provider Provider key.
 * @return string
 */
function bw_mew_get_social_redirect_uri($provider)
{
    if (!class_exists('BW_Social_Login')) {
        return '';
    }

    return BW_Social_Login::get_redirect_uri($provider);
}

/**
 * @deprecated Use BW_Social_Login::start_oauth_flow() instead.
 */
function bw_mew_social_login_redirect($provider)
{
    // Deprecated: Handled by BW_Social_Login class.
}

/**
 * @deprecated Use BW_Social_Login::process_oauth_callback() instead.
 */
function bw_mew_process_social_login_callback($provider)
{
    // Deprecated: Handled by BW_Social_Login class.
}

/**
 * @deprecated Use BW_Social_Login::exchange_facebook_code() instead.
 */
function bw_mew_exchange_facebook_code($code, $redirect_uri)
{
    // Deprecated: Handled by BW_Social_Login class.
    return new WP_Error('bw_deprecated', __('This function is deprecated.', 'bw'));
}

/**
 * @deprecated Use BW_Social_Login::exchange_google_code() instead.
 */
function bw_mew_exchange_google_code($code, $redirect_uri)
{
    // Deprecated: Handled by BW_Social_Login class.
    return new WP_Error('bw_deprecated', __('This function is deprecated.', 'bw'));
}

/**
 * @deprecated Use BW_Social_Login::login_or_register_user() instead.
 */
function bw_mew_login_or_register_social_user($email, $name)
{
    // Deprecated: Handled by BW_Social_Login class.
    return new WP_Error('bw_deprecated', __('This function is deprecated.', 'bw'));
}

/**
 * Retrieve the passwordless URL if configured.
 *
 * @return string
 */
function bw_mew_get_passwordless_url()
{
    $url = get_option('bw_account_passwordless_url', '');

    if (!empty($url)) {
        return esc_url($url);
    }

    return '';
}
