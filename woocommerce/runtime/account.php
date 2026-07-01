<?php
/**
 * WooCommerce runtime — account / my-account page customizations.
 *
 * Extracted verbatim from woocommerce-init.php (Phase 3b, BW-TASK-20260629). Function bodies are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove the theme-rendered page title on WooCommerce account pages.
 *
 * @param string $title   The post title.
 * @param int    $post_id Post ID.
 *
 * @return string
 */
function bw_mew_filter_account_page_title($title, $post_id)
{
    if (is_admin()) {
        return $title;
    }

    if (!function_exists('is_account_page') || !is_account_page()) {
        return $title;
    }

    if (!is_main_query() || !in_the_loop()) {
        return $title;
    }

    if (!is_singular()) {
        return $title;
    }

    return '';
}

/**
 * Enqueue assets for the custom account/login layout.
 */
function bw_mew_enqueue_account_page_assets()
{
    if (!function_exists('is_account_page') || !is_account_page() || is_user_logged_in()) {
        return;
    }

    $css_file = BW_MEW_PATH . 'assets/css/bw-account-page.css';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';
    $js_file = BW_MEW_PATH . 'assets/js/bw-account-page.js';
    $js_version = file_exists($js_file) ? filemtime($js_file) : '1.0.0';

    wp_enqueue_script(
        'supabase-js',
        'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2',
        [],
        null,
        true
    );

    wp_enqueue_style(
        'bw-account-page',
        BW_MEW_URL . 'assets/css/bw-account-page.css',
        [],
        $css_version
    );

    wp_enqueue_script(
        'bw-account-page',
        BW_MEW_URL . 'assets/js/bw-account-page.js',
        ['supabase-js'],
        $js_version,
        true
    );

    wp_localize_script(
        'bw-account-page',
        'bwAccountAuth',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bw-supabase-login'),
            'supabaseWithOidc' => (int) get_option('bw_supabase_with_plugins', 0),
            'loginMode' => get_option('bw_supabase_login_mode', 'native'),
            'oidcAuthUrl' => function_exists('bw_oidc_get_auth_url') ? bw_oidc_get_auth_url() : '',
            'autoLoginAfterConfirm' => (int) get_option('bw_supabase_auto_login_after_confirm', 0),
            'projectUrl' => get_option('bw_supabase_project_url', ''),
            'anonKey' => get_option('bw_supabase_anon_key', ''),
            'magicLinkRedirectUrl' => get_option('bw_supabase_magic_link_redirect_url', site_url('/my-account/')),
            'oauthRedirectUrl' => get_option('bw_supabase_oauth_redirect_url', site_url('/my-account/')),
            'resetRedirectUrl' => site_url('/my-account/'),
            'magicLinkEnabled' => (int) get_option('bw_supabase_magic_link_enabled', 1),
            'oauthGoogleEnabled' => (int) get_option('bw_supabase_oauth_google_enabled', 1),
            'oauthFacebookEnabled' => (int) get_option('bw_supabase_oauth_facebook_enabled', 1),
            'oauthAppleEnabled' => (int) get_option('bw_supabase_oauth_apple_enabled', 0),
            'passwordLoginEnabled' => (int) get_option('bw_supabase_login_password_enabled', 1),
            'otpAllowSignup' => (int) get_option('bw_supabase_otp_allow_signup', 1),
            'debug' => (int) get_option('bw_supabase_debug_log', 0),
            'cookieBase' => sanitize_key((string) get_option('bw_supabase_jwt_cookie_name', 'bw_supabase_session')) ?: 'bw_supabase_session',
            'messages' => [
                'missingConfig' => esc_html__('Supabase configuration is missing.', 'bw'),
                'enterEmail' => esc_html__('Please enter your email address.', 'bw'),
                'magicLinkError' => esc_html__('Unable to send magic link.', 'bw'),
                'loginError' => esc_html__('Unable to login.', 'bw'),
                'otpSent' => esc_html__('If the email is valid, we sent you a code.', 'bw'),
                'enterOtp' => esc_html__('Please enter the 6-digit code.', 'bw'),
                'otpVerifyError' => esc_html__('Unable to verify the code.', 'bw'),
                'otpInvalid' => esc_html__('Invalid or expired code. Please try again.', 'bw'),
                'otpResent' => esc_html__('We sent you a new code.', 'bw'),
                'otpResendError' => esc_html__('Unable to resend the code right now.', 'bw'),
                'otpSignupDisabledNeutral' => esc_html__('We could not send a code. Please try a different email.', 'bw'),
                'otpRateLimit' => esc_html__('Too many attempts. Please wait and try again.', 'bw'),
                'otpRedirectInvalid' => esc_html__('Login is unavailable right now. Please contact support.', 'bw'),
                'createPasswordError' => esc_html__('Unable to update password.', 'bw'),
                'passwordMismatch' => esc_html__('Passwords do not match.', 'bw'),
                'passwordRules' => esc_html__('Please meet all password requirements.', 'bw'),
                'oauthAppleSoon' => esc_html__('Apple login is coming soon.', 'bw'),
                'supabaseSdkMissing' => esc_html__('Supabase JS SDK is not loaded.', 'bw'),
            ],
        ]
    );
}

/**
 * Add a specific body class and hide theme wrappers on the custom login page.
 */
function bw_mew_prepare_account_page_layout()
{
    if (!function_exists('is_account_page') || !is_account_page() || is_user_logged_in()) {
        return;
    }

    add_filter('woocommerce_show_page_title', '__return_false');
    add_filter('body_class', static function ($classes) {
        $classes[] = 'bw-account-login-only';
        return $classes;
    });

    // Ensure the hiding styles are available even if the theme prints header/footer.
    add_action('wp_enqueue_scripts', static function () {
        if (wp_style_is('bw-account-page', 'enqueued')) {
            $css = 'body.bw-account-login-only header, body.bw-account-login-only .site-header, body.bw-account-login-only footer, body.bw-account-login-only .site-footer, body.bw-account-login-only .woocommerce-breadcrumb, body.bw-account-login-only .page-title, body.bw-account-login-only .entry-title { display: none !important; } body.bw-account-login-only .entry-content { padding: 0; margin: 0; } body.bw-account-login-only .site-content, body.bw-account-login-only .content-area, body.bw-account-login-only .site-main { padding: 0; margin: 0; }';
            wp_add_inline_style('bw-account-page', $css);
        }
    }, 25);
}

/**
 * Hide the default page title on logged-in account pages.
 */
function bw_mew_hide_logged_in_account_title()
{
    if (!function_exists('is_account_page') || !is_account_page() || !is_user_logged_in()) {
        return;
    }

    add_filter('woocommerce_show_page_title', '__return_false');
}

/**
 * Determine whether the theme title bypass is enabled for a context.
 *
 * @param string $context Context key (account|checkout).
 *
 * @return bool
 */
function bw_mew_is_theme_title_bypass_enabled($context)
{
    $enabled = false;

    if ('account' === $context) {
        $enabled = function_exists('is_account_page') && is_account_page() && is_user_logged_in();
    } elseif ('checkout' === $context) {
        $enabled = bw_mew_is_checkout_request()
            && (!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url('order-received'));
    }

    return (bool) apply_filters('bw_mew_enable_theme_title_bypass', $enabled, $context);
}

/**
 * Prepare theme title bypass helpers on account/checkout pages.
 */
function bw_mew_prepare_theme_title_bypass()
{
    if (!bw_mew_is_theme_title_bypass_enabled('account') && !bw_mew_is_theme_title_bypass_enabled('checkout')) {
        return;
    }

    add_filter('body_class', 'bw_mew_add_theme_title_bypass_class');
    add_action('wp_enqueue_scripts', 'bw_mew_dequeue_theme_title_styles', 100);
    add_action('wp_enqueue_scripts', 'bw_mew_enqueue_theme_title_bypass_css', 100);
}

/**
 * Add a body class for the theme title bypass scope.
 *
 * @param array $classes Body classes.
 *
 * @return array
 */
function bw_mew_add_theme_title_bypass_class($classes)
{
    if (bw_mew_is_theme_title_bypass_enabled('account') || bw_mew_is_theme_title_bypass_enabled('checkout')) {
        $classes[] = 'bw-theme-title-bypass';
    }

    return $classes;
}

/**
 * Dequeue parent theme title styles on targeted pages.
 */
function bw_mew_dequeue_theme_title_styles()
{
    if (!bw_mew_is_theme_title_bypass_enabled('account') && !bw_mew_is_theme_title_bypass_enabled('checkout')) {
        return;
    }

    $handles = (array) apply_filters('bw_mew_theme_title_bypass_handles', [], [
        'account' => bw_mew_is_theme_title_bypass_enabled('account'),
        'checkout' => bw_mew_is_theme_title_bypass_enabled('checkout'),
    ]);

    foreach ($handles as $handle) {
        if (!is_string($handle) || '' === $handle) {
            continue;
        }

        wp_dequeue_style($handle);
        wp_deregister_style($handle);
    }
}

/**
 * Add scoped reset styles to neutralize theme title CSS on targeted pages.
 */
function bw_mew_enqueue_theme_title_bypass_css()
{
    if (!bw_mew_is_theme_title_bypass_enabled('account') && !bw_mew_is_theme_title_bypass_enabled('checkout')) {
        return;
    }

    $css = '.bw-theme-title-bypass .page-header .entry-title,'
        . '.bw-theme-title-bypass .entry-header .entry-title,'
        . '.bw-theme-title-bypass .page-title{'
        . 'font-size:inherit;line-height:inherit;letter-spacing:normal;'
        . 'text-align:inherit;font-weight:inherit;}';

    if (bw_mew_is_theme_title_bypass_enabled('account') && wp_style_is('bw-my-account', 'enqueued')) {
        wp_add_inline_style('bw-my-account', $css);
    }

    if (bw_mew_is_theme_title_bypass_enabled('checkout') && wp_style_is('bw-checkout', 'enqueued')) {
        wp_add_inline_style('bw-checkout', $css);
    }
}
