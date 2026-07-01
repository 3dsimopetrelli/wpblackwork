<?php
/**
 * Blackwork Site Settings — Login/Account and My-Account tab renderers + login-slug helpers.
 *
 * Extracted verbatim from class-blackwork-site-settings.php (Phase 3a, BW-TASK-20260629). Function bodies are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renderizza il tab Account Page
 */
function bw_get_custom_wp_login_reserved_slugs()
{
    return [
        'wp-admin',
        'wp-login.php',
        'wp-json',
        'wp-content',
        'wp-includes',
        'admin',
        'login',
        'my-account',
        'account',
        'cart',
        'checkout',
        'shop',
        'product',
        'product-category',
        'category',
        'tag',
        'search',
    ];
}

function bw_sanitize_custom_wp_login_slug($raw_slug)
{
    $raw_slug = (string) wp_unslash($raw_slug);
    $raw_slug = trim($raw_slug);

    if ('' === $raw_slug) {
        return '';
    }

    if (false !== strpos($raw_slug, '://') || false !== strpos($raw_slug, '?') || false !== strpos($raw_slug, '&') || false !== strpos($raw_slug, '=')) {
        return '';
    }

    $raw_slug = trim($raw_slug, "/ \t\n\r\0\x0B");
    if ('' === $raw_slug || false !== strpos($raw_slug, '/')) {
        return '';
    }

    $slug = sanitize_title($raw_slug);
    if ('' === $slug) {
        return '';
    }

    if (in_array($slug, bw_get_custom_wp_login_reserved_slugs(), true)) {
        return '';
    }

    return $slug;
}

function bw_custom_wp_login_slug_has_conflict($slug)
{
    $slug = sanitize_title((string) $slug);
    if ('' === $slug) {
        return false;
    }

    $post_conflict = get_page_by_path($slug, OBJECT, ['page', 'post', 'product']);
    if ($post_conflict instanceof WP_Post) {
        return true;
    }

    if (term_exists($slug, 'product_cat') || term_exists($slug, 'category') || term_exists($slug, 'post_tag')) {
        return true;
    }

    return false;
}

function bw_site_render_account_page_tab()
{
    $saved = false;
    $custom_wp_login_slug_error = '';

    if (isset($_POST['bw_account_page_submit'])) {
        if (!current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('bw_account_page_save', 'bw_account_page_nonce');

        $login_provider = isset($_POST['bw_account_login_provider']) ? sanitize_key(wp_unslash($_POST['bw_account_login_provider'])) : 'wordpress';
        $login_image = isset($_POST['bw_account_login_image']) ? esc_url_raw(wp_unslash($_POST['bw_account_login_image'])) : '';
        $login_image_id = isset($_POST['bw_account_login_image_id']) ? absint(wp_unslash($_POST['bw_account_login_image_id'])) : 0;
        $logo = isset($_POST['bw_account_logo']) ? esc_url_raw(wp_unslash($_POST['bw_account_logo'])) : '';
        $logo_id = isset($_POST['bw_account_logo_id']) ? absint(wp_unslash($_POST['bw_account_logo_id'])) : 0;
        $logo_width = isset($_POST['bw_account_logo_width']) ? absint(wp_unslash($_POST['bw_account_logo_width'])) : 180;
        $logo_padding_top = isset($_POST['bw_account_logo_padding_top']) ? absint(wp_unslash($_POST['bw_account_logo_padding_top'])) : 0;
        $logo_padding_bottom = isset($_POST['bw_account_logo_padding_bottom']) ? absint(wp_unslash($_POST['bw_account_logo_padding_bottom'])) : 30;
        $login_title_supabase = isset($_POST['bw_account_login_title_supabase']) ? sanitize_text_field(wp_unslash($_POST['bw_account_login_title_supabase'])) : '';
        $login_subtitle_supabase = isset($_POST['bw_account_login_subtitle_supabase']) ? sanitize_textarea_field(wp_unslash($_POST['bw_account_login_subtitle_supabase'])) : '';
        $login_title_wordpress = isset($_POST['bw_account_login_title_wordpress']) ? sanitize_text_field(wp_unslash($_POST['bw_account_login_title_wordpress'])) : '';
        $login_subtitle_wordpress = isset($_POST['bw_account_login_subtitle_wordpress']) ? sanitize_textarea_field(wp_unslash($_POST['bw_account_login_subtitle_wordpress'])) : '';
        $show_social_buttons = isset($_POST['bw_account_show_social_buttons']) ? 1 : 0;
        $facebook = isset($_POST['bw_account_facebook']) ? 1 : 0;
        $google = isset($_POST['bw_account_google']) ? 1 : 0;
        $facebook_app_id = isset($_POST['bw_account_facebook_app_id']) ? sanitize_text_field(wp_unslash($_POST['bw_account_facebook_app_id'])) : '';
        $facebook_app_secret = isset($_POST['bw_account_facebook_app_secret']) ? sanitize_text_field(wp_unslash($_POST['bw_account_facebook_app_secret'])) : '';
        $google_client_id = isset($_POST['bw_account_google_client_id']) ? sanitize_text_field(wp_unslash($_POST['bw_account_google_client_id'])) : '';
        $google_client_secret = isset($_POST['bw_account_google_client_secret']) ? sanitize_text_field(wp_unslash($_POST['bw_account_google_client_secret'])) : '';
        $passwordless_url = isset($_POST['bw_account_passwordless_url']) ? esc_url_raw(wp_unslash($_POST['bw_account_passwordless_url'])) : '';
        $supabase_project_url = isset($_POST['bw_supabase_project_url']) ? esc_url_raw(trim(wp_unslash($_POST['bw_supabase_project_url']))) : '';
        $supabase_anon_key = isset($_POST['bw_supabase_anon_key']) ? sanitize_textarea_field(trim(wp_unslash($_POST['bw_supabase_anon_key']))) : '';
        $supabase_service_key = isset($_POST['bw_supabase_service_role_key']) ? sanitize_textarea_field(wp_unslash($_POST['bw_supabase_service_role_key'])) : '';
        $supabase_auth_mode = isset($_POST['bw_supabase_auth_mode']) ? sanitize_key(wp_unslash($_POST['bw_supabase_auth_mode'])) : 'password';
        $supabase_login_mode = isset($_POST['bw_supabase_login_mode']) ? sanitize_key(wp_unslash($_POST['bw_supabase_login_mode'])) : 'native';
        $supabase_cookie_name = isset($_POST['bw_supabase_jwt_cookie_name']) ? sanitize_key(wp_unslash($_POST['bw_supabase_jwt_cookie_name'])) : 'bw_supabase_session';
        $supabase_storage = isset($_POST['bw_supabase_session_storage']) ? sanitize_key(wp_unslash($_POST['bw_supabase_session_storage'])) : 'cookie';
        $supabase_link_users = isset($_POST['bw_supabase_enable_wp_user_linking']) ? 1 : 0;
        $supabase_debug_log = isset($_POST['bw_supabase_debug_log']) ? 1 : 0;
        $supabase_with_plugins = isset($_POST['bw_supabase_with_plugins']) ? 1 : 0;
        $supabase_registration = isset($_POST['bw_supabase_registration_mode']) ? sanitize_text_field(wp_unslash($_POST['bw_supabase_registration_mode'])) : 'R2';
        $supabase_signup_url = isset($_POST['bw_supabase_provider_signup_url']) ? esc_url_raw(wp_unslash($_POST['bw_supabase_provider_signup_url'])) : '';
        $supabase_reset_url = isset($_POST['bw_supabase_provider_reset_url']) ? esc_url_raw(wp_unslash($_POST['bw_supabase_provider_reset_url'])) : '';
        $supabase_confirm_url = isset($_POST['bw_supabase_email_confirm_redirect_url']) ? esc_url_raw(trim(wp_unslash($_POST['bw_supabase_email_confirm_redirect_url']))) : '';
        $supabase_magic_link_enabled = isset($_POST['bw_supabase_magic_link_enabled']) ? 1 : 0;
        $supabase_otp_allow_signup = isset($_POST['bw_supabase_otp_allow_signup']) ? 1 : 0;
        $supabase_oauth_google_enabled = isset($_POST['bw_supabase_oauth_google_enabled']) ? 1 : 0;
        $supabase_oauth_facebook_enabled = isset($_POST['bw_supabase_oauth_facebook_enabled']) ? 1 : 0;
        $supabase_oauth_apple_enabled = isset($_POST['bw_supabase_oauth_apple_enabled']) ? 1 : 0;
        $supabase_google_client_id = isset($_POST['bw_supabase_google_client_id']) ? sanitize_text_field(wp_unslash($_POST['bw_supabase_google_client_id'])) : '';
        $supabase_google_client_secret = isset($_POST['bw_supabase_google_client_secret']) ? sanitize_textarea_field(wp_unslash($_POST['bw_supabase_google_client_secret'])) : '';
        $supabase_google_redirect_url = isset($_POST['bw_supabase_google_redirect_url']) ? esc_url_raw(wp_unslash($_POST['bw_supabase_google_redirect_url'])) : '';
        $supabase_google_scopes = isset($_POST['bw_supabase_google_scopes']) ? sanitize_text_field(wp_unslash($_POST['bw_supabase_google_scopes'])) : '';
        $supabase_google_prompt = isset($_POST['bw_supabase_google_prompt']) ? sanitize_text_field(wp_unslash($_POST['bw_supabase_google_prompt'])) : '';
        $supabase_facebook_app_id = isset($_POST['bw_supabase_facebook_app_id']) ? sanitize_text_field(wp_unslash($_POST['bw_supabase_facebook_app_id'])) : '';
        $supabase_facebook_app_secret = isset($_POST['bw_supabase_facebook_app_secret']) ? sanitize_textarea_field(wp_unslash($_POST['bw_supabase_facebook_app_secret'])) : '';
        $supabase_facebook_redirect_url = isset($_POST['bw_supabase_facebook_redirect_url']) ? esc_url_raw(wp_unslash($_POST['bw_supabase_facebook_redirect_url'])) : '';
        $supabase_facebook_scopes = isset($_POST['bw_supabase_facebook_scopes']) ? sanitize_text_field(wp_unslash($_POST['bw_supabase_facebook_scopes'])) : '';
        $supabase_apple_client_id = isset($_POST['bw_supabase_apple_client_id']) ? sanitize_text_field(wp_unslash($_POST['bw_supabase_apple_client_id'])) : '';
        $supabase_apple_team_id = isset($_POST['bw_supabase_apple_team_id']) ? sanitize_text_field(wp_unslash($_POST['bw_supabase_apple_team_id'])) : '';
        $supabase_apple_key_id = isset($_POST['bw_supabase_apple_key_id']) ? sanitize_text_field(wp_unslash($_POST['bw_supabase_apple_key_id'])) : '';
        $supabase_apple_private_key = isset($_POST['bw_supabase_apple_private_key']) ? sanitize_textarea_field(wp_unslash($_POST['bw_supabase_apple_private_key'])) : '';
        $supabase_apple_redirect_url = isset($_POST['bw_supabase_apple_redirect_url']) ? esc_url_raw(wp_unslash($_POST['bw_supabase_apple_redirect_url'])) : '';
        $supabase_password_enabled = isset($_POST['bw_supabase_login_password_enabled']) ? 1 : 0;
        $supabase_magic_link_redirect = isset($_POST['bw_supabase_magic_link_redirect_url']) ? esc_url_raw(trim(wp_unslash($_POST['bw_supabase_magic_link_redirect_url']))) : '';
        $supabase_oauth_redirect = isset($_POST['bw_supabase_oauth_redirect_url']) ? esc_url_raw(trim(wp_unslash($_POST['bw_supabase_oauth_redirect_url']))) : '';
        $supabase_signup_redirect = isset($_POST['bw_supabase_signup_redirect_url']) ? esc_url_raw(trim(wp_unslash($_POST['bw_supabase_signup_redirect_url']))) : '';
        $supabase_auto_login = isset($_POST['bw_supabase_auto_login_after_confirm']) ? 1 : 0;
        $supabase_create_users = isset($_POST['bw_supabase_create_wp_users']) ? 1 : 0;
        $custom_wp_login_hide_default = isset($_POST['bw_custom_wp_login_hide_default']) ? 1 : 0;
        $custom_wp_login_recovery_mode = isset($_POST['bw_custom_wp_login_recovery_mode']) ? 1 : 0;
        $custom_wp_login_slug_raw = isset($_POST['bw_custom_wp_login_slug']) ? (string) $_POST['bw_custom_wp_login_slug'] : '';
        $custom_wp_login_slug_raw = trim((string) wp_unslash($custom_wp_login_slug_raw));
        $custom_wp_login_slug = bw_sanitize_custom_wp_login_slug($custom_wp_login_slug_raw);
        $previous_custom_wp_login_slug = bw_sanitize_custom_wp_login_slug((string) get_option('bw_custom_wp_login_slug', ''));

        if ('' !== $custom_wp_login_slug_raw) {
            if ('' === $custom_wp_login_slug) {
                $custom_wp_login_slug_error = __('Custom login slug not saved: use a valid slug (no URL, query string, slashes, or reserved values).', 'bw');
                $custom_wp_login_slug = $previous_custom_wp_login_slug;
            } elseif (bw_custom_wp_login_slug_has_conflict($custom_wp_login_slug)) {
                $custom_wp_login_slug_error = __('Custom login slug not saved: this slug is already used by an existing page, post, product, or taxonomy archive.', 'bw');
                $custom_wp_login_slug = $previous_custom_wp_login_slug;
            }
        }

        if (!in_array($login_provider, ['wordpress', 'supabase'], true)) {
            $login_provider = 'wordpress';
        }

        if (!in_array($supabase_auth_mode, ['password'], true)) {
            $supabase_auth_mode = 'password';
        }

        if (!in_array($supabase_login_mode, ['native', 'oidc'], true)) {
            $supabase_login_mode = 'native';
        }

        if (!in_array($supabase_storage, ['cookie', 'usermeta'], true)) {
            $supabase_storage = 'cookie';
        }
        if (!in_array($supabase_registration, ['R1', 'R2', 'R3'], true)) {
            $supabase_registration = 'R2';
        }

        if ($logo_width < 20) {
            $logo_width = 20;
        }
        if ($logo_width > 400) {
            $logo_width = 400;
        }
        if ($logo_padding_top > 200) {
            $logo_padding_top = 200;
        }
        if ($logo_padding_bottom > 200) {
            $logo_padding_bottom = 200;
        }

        if ($login_image_id) {
            $login_image_url = wp_get_attachment_url($login_image_id);
            if ($login_image_url) {
                $login_image = $login_image_url;
            }
        }
        if ($logo_id) {
            $logo_url = wp_get_attachment_url($logo_id);
            if ($logo_url) {
                $logo = $logo_url;
            }
        }

        update_option('bw_account_login_provider', $login_provider);
        update_option('bw_account_login_image', $login_image);
        update_option('bw_account_login_image_id', $login_image_id);
        update_option('bw_account_logo', $logo);
        update_option('bw_account_logo_id', $logo_id);
        update_option('bw_account_logo_width', $logo_width);
        update_option('bw_account_logo_padding_top', $logo_padding_top);
        update_option('bw_account_logo_padding_bottom', $logo_padding_bottom);
        update_option('bw_account_login_title_supabase', $login_title_supabase);
        update_option('bw_account_login_subtitle_supabase', $login_subtitle_supabase);
        update_option('bw_account_login_title_wordpress', $login_title_wordpress);
        update_option('bw_account_login_subtitle_wordpress', $login_subtitle_wordpress);
        // Legacy fallback options used by older code paths.
        update_option('bw_account_login_title', $login_title_wordpress);
        update_option('bw_account_login_subtitle', $login_subtitle_wordpress);
        update_option('bw_account_show_social_buttons', $show_social_buttons);
        update_option('bw_account_passwordless_url', $passwordless_url);
        update_option('bw_custom_wp_login_slug', $custom_wp_login_slug);
        update_option('bw_custom_wp_login_hide_default', $custom_wp_login_hide_default);
        update_option('bw_custom_wp_login_recovery_mode', $custom_wp_login_recovery_mode);

        if ($custom_wp_login_slug !== $previous_custom_wp_login_slug) {
            update_option('bw_custom_wp_login_slug_needs_flush', 1, false);
        }

        // WordPress provider options - only save if WordPress is selected (preserve Supabase settings when switching)
        if ('wordpress' === $login_provider) {
            update_option('bw_account_facebook', $facebook);
            update_option('bw_account_google', $google);
            update_option('bw_account_facebook_app_id', $facebook_app_id);
            update_option('bw_account_facebook_app_secret', $facebook_app_secret);
            update_option('bw_account_google_client_id', $google_client_id);
            update_option('bw_account_google_client_secret', $google_client_secret);
        }

        // Supabase provider options - only save if Supabase is selected (preserve WordPress settings when switching)
        if ('supabase' === $login_provider) {
            update_option('bw_supabase_project_url', $supabase_project_url);
            update_option('bw_supabase_anon_key', $supabase_anon_key);
            update_option('bw_supabase_service_role_key', $supabase_service_key);
            update_option('bw_supabase_auth_mode', $supabase_auth_mode);
            update_option('bw_supabase_login_mode', $supabase_login_mode);
            update_option('bw_supabase_jwt_cookie_name', $supabase_cookie_name);
            update_option('bw_supabase_session_storage', $supabase_storage);
            update_option('bw_supabase_enable_wp_user_linking', $supabase_link_users);
            update_option('bw_supabase_debug_log', $supabase_debug_log);
            update_option('bw_supabase_with_plugins', $supabase_with_plugins);
            update_option('bw_supabase_registration_mode', $supabase_registration);
            update_option('bw_supabase_provider_signup_url', $supabase_signup_url);
            update_option('bw_supabase_provider_reset_url', $supabase_reset_url);
            update_option('bw_supabase_email_confirm_redirect_url', $supabase_confirm_url);
            update_option('bw_supabase_magic_link_enabled', $supabase_magic_link_enabled);
            update_option('bw_supabase_otp_allow_signup', $supabase_otp_allow_signup);
            update_option('bw_supabase_oauth_google_enabled', $supabase_oauth_google_enabled);
            update_option('bw_supabase_oauth_facebook_enabled', $supabase_oauth_facebook_enabled);
            update_option('bw_supabase_oauth_apple_enabled', $supabase_oauth_apple_enabled);
            update_option('bw_supabase_google_client_id', $supabase_google_client_id);
            update_option('bw_supabase_google_client_secret', $supabase_google_client_secret);
            update_option('bw_supabase_google_redirect_url', $supabase_google_redirect_url);
            update_option('bw_supabase_google_scopes', $supabase_google_scopes);
            update_option('bw_supabase_google_prompt', $supabase_google_prompt);
            update_option('bw_supabase_facebook_app_id', $supabase_facebook_app_id);
            update_option('bw_supabase_facebook_app_secret', $supabase_facebook_app_secret);
            update_option('bw_supabase_facebook_redirect_url', $supabase_facebook_redirect_url);
            update_option('bw_supabase_facebook_scopes', $supabase_facebook_scopes);
            update_option('bw_supabase_apple_client_id', $supabase_apple_client_id);
            update_option('bw_supabase_apple_team_id', $supabase_apple_team_id);
            update_option('bw_supabase_apple_key_id', $supabase_apple_key_id);
            update_option('bw_supabase_apple_private_key', $supabase_apple_private_key);
            update_option('bw_supabase_apple_redirect_url', $supabase_apple_redirect_url);
            update_option('bw_supabase_login_password_enabled', $supabase_password_enabled);
            update_option('bw_supabase_magic_link_redirect_url', $supabase_magic_link_redirect);
            update_option('bw_supabase_oauth_redirect_url', $supabase_oauth_redirect);
            update_option('bw_supabase_signup_redirect_url', $supabase_signup_redirect);
            update_option('bw_supabase_auto_login_after_confirm', $supabase_auto_login);
            update_option('bw_supabase_create_wp_users', $supabase_create_users);
        }

        // Clear social login settings cache.
        if (class_exists('BW_Social_Login')) {
            BW_Social_Login::clear_cache();
        }

        $saved = true;
    }

    $login_provider = get_option('bw_account_login_provider', 'wordpress');
    $login_image = get_option('bw_account_login_image', '');
    $login_image_id = (int) get_option('bw_account_login_image_id', 0);
    $logo = get_option('bw_account_logo', '');
    $logo_id = (int) get_option('bw_account_logo_id', 0);
    $logo_width = (int) get_option('bw_account_logo_width', 180);
    $logo_padding_top = (int) get_option('bw_account_logo_padding_top', 0);
    $logo_padding_bottom = (int) get_option('bw_account_logo_padding_bottom', 30);
    $legacy_login_title = get_option('bw_account_login_title', 'Log in to Blackwork');
    $legacy_login_subtitle = get_option(
        'bw_account_login_subtitle',
        "If you are new, we will create your account automatically.\nNew or returning, this works the same."
    );
    $login_title_supabase = get_option('bw_account_login_title_supabase', $legacy_login_title);
    $login_subtitle_supabase = get_option('bw_account_login_subtitle_supabase', $legacy_login_subtitle);
    $login_title_wordpress = get_option('bw_account_login_title_wordpress', $legacy_login_title);
    $login_subtitle_wordpress = get_option('bw_account_login_subtitle_wordpress', $legacy_login_subtitle);
    $show_social_buttons = (int) get_option('bw_account_show_social_buttons', 1);
    $facebook = (int) get_option('bw_account_facebook', 0);
    $google = (int) get_option('bw_account_google', 0);
    $facebook_app_id = get_option('bw_account_facebook_app_id', '');
    $facebook_app_secret = get_option('bw_account_facebook_app_secret', '');
    $google_client_id = get_option('bw_account_google_client_id', '');
    $google_client_secret = get_option('bw_account_google_client_secret', '');
    $passwordless_url = get_option('bw_account_passwordless_url', '');
    $supabase_project_url = get_option('bw_supabase_project_url', '');
    $supabase_anon_key = get_option('bw_supabase_anon_key', '');
    $supabase_service_key = get_option('bw_supabase_service_role_key', '');
    $supabase_auth_mode = get_option('bw_supabase_auth_mode', 'password');
    $supabase_login_mode = get_option('bw_supabase_login_mode', 'native');
    $supabase_cookie_name = get_option('bw_supabase_jwt_cookie_name', 'bw_supabase_session');
    $supabase_storage = get_option('bw_supabase_session_storage', 'cookie');
    $supabase_link_users = (int) get_option('bw_supabase_enable_wp_user_linking', 0);
    $supabase_debug_log = (int) get_option('bw_supabase_debug_log', 0);
    $supabase_with_plugins = (int) get_option('bw_supabase_with_plugins', 0);
    $supabase_registration = get_option('bw_supabase_registration_mode', 'R2');
    $supabase_signup_url = get_option('bw_supabase_provider_signup_url', '');
    $supabase_reset_url = get_option('bw_supabase_provider_reset_url', '');
    $supabase_confirm_url = get_option('bw_supabase_email_confirm_redirect_url', site_url('/my-account/?bw_email_confirmed=1'));
    $supabase_magic_link_enabled = (int) get_option('bw_supabase_magic_link_enabled', 1);
    $supabase_otp_allow_signup = (int) get_option('bw_supabase_otp_allow_signup', 1);
    $supabase_oauth_google_enabled = (int) get_option('bw_supabase_oauth_google_enabled', 1);
    $supabase_oauth_facebook_enabled = (int) get_option('bw_supabase_oauth_facebook_enabled', 1);
    $supabase_oauth_apple_enabled = (int) get_option('bw_supabase_oauth_apple_enabled', 0);
    $supabase_google_client_id = get_option('bw_supabase_google_client_id', '');
    $supabase_google_client_secret = get_option('bw_supabase_google_client_secret', '');
    $supabase_google_redirect_url = get_option('bw_supabase_google_redirect_url', site_url('/my-account/'));
    $supabase_google_scopes = get_option('bw_supabase_google_scopes', 'email profile');
    $supabase_google_prompt = get_option('bw_supabase_google_prompt', 'select_account');
    $supabase_facebook_app_id = get_option('bw_supabase_facebook_app_id', '');
    $supabase_facebook_app_secret = get_option('bw_supabase_facebook_app_secret', '');
    $supabase_facebook_redirect_url = get_option('bw_supabase_facebook_redirect_url', site_url('/my-account/'));
    $supabase_facebook_scopes = get_option('bw_supabase_facebook_scopes', 'email,public_profile');
    $supabase_apple_client_id = get_option('bw_supabase_apple_client_id', '');
    $supabase_apple_team_id = get_option('bw_supabase_apple_team_id', '');
    $supabase_apple_key_id = get_option('bw_supabase_apple_key_id', '');
    $supabase_apple_private_key = get_option('bw_supabase_apple_private_key', '');
    $supabase_apple_redirect_url = get_option('bw_supabase_apple_redirect_url', site_url('/my-account/'));
    $supabase_password_enabled = (int) get_option('bw_supabase_login_password_enabled', 1);
    $supabase_magic_link_redirect = get_option('bw_supabase_magic_link_redirect_url', site_url('/my-account/'));
    $supabase_oauth_redirect = get_option('bw_supabase_oauth_redirect_url', site_url('/my-account/'));
    $supabase_signup_redirect = get_option('bw_supabase_signup_redirect_url', site_url('/my-account/?bw_email_confirmed=1'));
    $supabase_auto_login = (int) get_option('bw_supabase_auto_login_after_confirm', 0);
    $supabase_create_users = (int) get_option('bw_supabase_create_wp_users', 1);
    $custom_wp_login_hide_default = (int) get_option('bw_custom_wp_login_hide_default', 0);
    $custom_wp_login_recovery_mode_option = get_option('bw_custom_wp_login_recovery_mode', null);
    $custom_wp_login_slug = bw_sanitize_custom_wp_login_slug((string) get_option('bw_custom_wp_login_slug', ''));
    $custom_wp_login_recovery_mode = null !== $custom_wp_login_recovery_mode_option
        ? (int) $custom_wp_login_recovery_mode_option
        : (int) !($custom_wp_login_hide_default && '' !== $custom_wp_login_slug);
    $custom_wp_login_url = '';
    if ('' !== $custom_wp_login_slug) {
        $custom_wp_login_url = home_url('/' . $custom_wp_login_slug . '/');
    }
    $custom_wp_login_hide_warning = (1 === $custom_wp_login_hide_default && '' === $custom_wp_login_slug);

    $facebook_redirect = function_exists('bw_mew_get_social_redirect_uri') ? bw_mew_get_social_redirect_uri('facebook') : add_query_arg('bw_social_login_callback', 'facebook', wc_get_page_permalink('myaccount'));
    $google_redirect = function_exists('bw_mew_get_social_redirect_uri') ? bw_mew_get_social_redirect_uri('google') : add_query_arg('bw_social_login_callback', 'google', wc_get_page_permalink('myaccount'));

    $login_image_url = $login_image;
    if ($login_image_id) {
        $login_image_attachment = wp_get_attachment_url($login_image_id);
        if ($login_image_attachment) {
            $login_image_url = $login_image_attachment;
        }
    }
    $logo_url = $logo;
    if ($logo_id) {
        $logo_attachment = wp_get_attachment_url($logo_id);
        if ($logo_attachment) {
            $logo_url = $logo_attachment;
        }
    }
    ?>
    <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Impostazioni salvate con successo!</strong></p>
        </div>
    <?php endif; ?>
    <?php if ('' !== $custom_wp_login_slug_error): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($custom_wp_login_slug_error); ?></p>
        </div>
    <?php endif; ?>
    <?php if ($custom_wp_login_hide_warning): ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php esc_html_e('A valid custom login slug is required before hiding default WordPress login URLs.', 'bw'); ?></p>
        </div>
    <?php endif; ?>
    <form method="post" action="">
        <?php wp_nonce_field('bw_account_page_save', 'bw_account_page_nonce'); ?>

        <h2 class="nav-tab-wrapper bw-account-settings-tabs" role="tablist">
            <a href="#design" class="nav-tab nav-tab-active" role="tab" aria-selected="true" data-bw-account-tab="design">
                <?php esc_html_e('Design', 'bw'); ?>
            </a>
            <a href="#technical" class="nav-tab" role="tab" aria-selected="false" data-bw-account-tab="technical">
                <?php esc_html_e('Provider WordPress or Supabase', 'bw'); ?>
            </a>
            <a href="#admin-login-link" class="nav-tab" role="tab" aria-selected="false" data-bw-account-tab="admin-login-link">
                <?php esc_html_e('Admin Login Link', 'bw'); ?>
            </a>
        </h2>

        <div class="bw-account-settings-tab" data-bw-account-tab="design">
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="bw_account_login_image"><?php esc_html_e('Login Image (cover)', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bw_account_login_image" name="bw_account_login_image"
                                value="<?php echo esc_attr($login_image_url); ?>" class="regular-text" />
                            <input type="hidden" id="bw_account_login_image_id" name="bw_account_login_image_id"
                                value="<?php echo esc_attr($login_image_id); ?>" />
                            <button type="button" class="button bw-media-upload" data-target="#bw_account_login_image"
                                data-id-target="#bw_account_login_image_id"><?php esc_html_e('Select image', 'bw'); ?></button>
                            <p class="description"><?php esc_html_e('Cover image shown on the left side.', 'bw'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bw_account_logo"><?php esc_html_e('Logo', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bw_account_logo" name="bw_account_logo"
                                value="<?php echo esc_attr($logo_url); ?>" class="regular-text" />
                            <input type="hidden" id="bw_account_logo_id" name="bw_account_logo_id"
                                value="<?php echo esc_attr($logo_id); ?>" />
                            <button type="button" class="button bw-media-upload" data-target="#bw_account_logo"
                                data-id-target="#bw_account_logo_id"><?php esc_html_e('Select logo', 'bw'); ?></button>
                            <p class="description"><?php esc_html_e('Logo displayed above the login form.', 'bw'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bw_account_logo_width"><?php esc_html_e('Logo width (px)', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="bw_account_logo_width" name="bw_account_logo_width"
                                value="<?php echo esc_attr($logo_width); ?>" min="20" max="400" step="1"
                                class="small-text" />
                            <p class="description"><?php esc_html_e('Max logo width in pixels. Default: 180px', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_account_logo_padding_top"><?php esc_html_e('Padding top logo (px)', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="bw_account_logo_padding_top" name="bw_account_logo_padding_top"
                                value="<?php echo esc_attr($logo_padding_top); ?>" min="0" max="200" step="1"
                                class="small-text" />
                            <p class="description"><?php esc_html_e('Space above the logo in pixels.', 'bw'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_account_logo_padding_bottom"><?php esc_html_e('Padding bottom logo (px)', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="bw_account_logo_padding_bottom" name="bw_account_logo_padding_bottom"
                                value="<?php echo esc_attr($logo_padding_bottom); ?>" min="0" max="200" step="1"
                                class="small-text" />
                            <p class="description"><?php esc_html_e('Space below the logo in pixels.', 'bw'); ?></p>
                        </td>
                    </tr>
                    <tr class="bw-login-copy-field" data-bw-login-copy-provider="supabase" <?php echo 'supabase' === $login_provider ? '' : 'style="display:none;"'; ?>>
                        <th scope="row">
                            <label for="bw_account_login_title_supabase"><?php esc_html_e('Login Title (Supabase)', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bw_account_login_title_supabase" name="bw_account_login_title_supabase"
                                value="<?php echo esc_attr($login_title_supabase); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('Title shown when Login Provider is Supabase.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-login-copy-field" data-bw-login-copy-provider="supabase" <?php echo 'supabase' === $login_provider ? '' : 'style="display:none;"'; ?>>
                        <th scope="row">
                            <label for="bw_account_login_subtitle_supabase"><?php esc_html_e('Login Subtitle (Supabase)', 'bw'); ?></label>
                        </th>
                        <td>
                            <textarea id="bw_account_login_subtitle_supabase" name="bw_account_login_subtitle_supabase" rows="3"
                                class="large-text"><?php echo esc_textarea($login_subtitle_supabase); ?></textarea>
                            <p class="description">
                                <?php esc_html_e('Subtitle shown when Login Provider is Supabase. Use new lines for line breaks.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-login-copy-field" data-bw-login-copy-provider="wordpress" <?php echo 'wordpress' === $login_provider ? '' : 'style="display:none;"'; ?>>
                        <th scope="row">
                            <label for="bw_account_login_title_wordpress"><?php esc_html_e('Login Title (WordPress)', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bw_account_login_title_wordpress" name="bw_account_login_title_wordpress"
                                value="<?php echo esc_attr($login_title_wordpress); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('Title shown when Login Provider is WordPress.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-login-copy-field" data-bw-login-copy-provider="wordpress" <?php echo 'wordpress' === $login_provider ? '' : 'style="display:none;"'; ?>>
                        <th scope="row">
                            <label for="bw_account_login_subtitle_wordpress"><?php esc_html_e('Login Subtitle (WordPress)', 'bw'); ?></label>
                        </th>
                        <td>
                            <textarea id="bw_account_login_subtitle_wordpress" name="bw_account_login_subtitle_wordpress" rows="3"
                                class="large-text"><?php echo esc_textarea($login_subtitle_wordpress); ?></textarea>
                            <p class="description">
                                <?php esc_html_e('Subtitle shown when Login Provider is WordPress. Use new lines for line breaks.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_account_show_social_buttons"><?php esc_html_e('Show social login buttons', 'bw'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="bw_account_show_social_buttons"
                                    name="bw_account_show_social_buttons" value="1" <?php checked(1, $show_social_buttons); ?> />
                                <?php esc_html_e('Show social login buttons', 'bw'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('Hide or show the social login buttons without disabling providers.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="bw-account-settings-tab" data-bw-account-tab="technical" style="display:none;">
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label><?php esc_html_e('Login Provider', 'bw'); ?></label>
                        </th>
                        <td>
                            <fieldset>
                                <label style="display:block; margin-bottom:8px;">
                                    <input type="radio" name="bw_account_login_provider" value="wordpress" <?php checked('wordpress', $login_provider); ?> />
                                    <?php esc_html_e('WordPress', 'bw'); ?>
                                </label>
                                <label style="display:block;">
                                    <input type="radio" name="bw_account_login_provider" value="supabase" <?php checked('supabase', $login_provider); ?> />
                                    <?php esc_html_e('Supabase', 'bw'); ?>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('Choose which login provider is the default for the My Account page.', 'bw'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                </tbody>
                <tbody class="bw-login-provider-section" data-bw-login-provider="wordpress" <?php echo 'supabase' === $login_provider ? 'style="display:none;"' : ''; ?>>
                    <tr>
                        <th scope="row"><?php esc_html_e('Social login providers', 'bw'); ?></th>
                        <td>
                            <label style="display:block; margin-bottom:8px;">
                                <input type="checkbox" id="bw_account_facebook" name="bw_account_facebook" value="1" <?php checked(1, $facebook); ?> />
                                <?php esc_html_e('Enable Facebook Login', 'bw'); ?>
                            </label>
                            <label style="display:block;">
                                <input type="checkbox" id="bw_account_google" name="bw_account_google" value="1" <?php checked(1, $google); ?> />
                                <?php esc_html_e('Enable Google Login', 'bw'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('Enable providers to configure their credentials.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-account-provider-option" data-bw-account-provider="facebook" <?php echo $facebook ? '' : 'style="display:none;"'; ?>>
                        <td colspan="2">
                            <div class="bw-settings-group">
                                <div class="bw-settings-group__title"><?php esc_html_e('Facebook settings', 'bw'); ?>
                                </div>
                                <details class="bw-oauth-help-accordion"
                                    style="background: #f0f6fc; border: 1px solid #0969da; border-radius: 6px; padding: 12px; margin-bottom: 10px;">
                                    <summary
                                        style="cursor: pointer; font-weight: 600; color: #0969da; font-size: 14px; user-select: none;">
                                        📘 Come ottenere Facebook App ID e Secret
                                    </summary>
                                    <div style="padding: 12px 0 0 0; color: #1f2328; line-height: 1.6;">
                                        <p style="margin: 0 0 12px 0;"><strong>Segui questi passi:</strong></p>
                                        <ol style="margin: 0 0 12px 20px; padding: 0;">
                                            <li style="margin-bottom: 8px;">
                                                <strong>Vai alla console Facebook Developers:</strong><br>
                                                <a href="https://developers.facebook.com/apps/" target="_blank"
                                                    rel="noopener"
                                                    style="color: #0969da; text-decoration: none; font-weight: 500;">
                                                    🔗 https://developers.facebook.com/apps/
                                                </a>
                                            </li>
                                            <li style="margin-bottom: 8px;">
                                                <strong>Clicca su "Crea un'app"</strong> (Create App)
                                            </li>
                                            <li style="margin-bottom: 8px;">
                                                <strong>Seleziona tipo:</strong> "Consumatore" (Consumer)
                                            </li>
                                            <li style="margin-bottom: 8px;">
                                                <strong>Compila:</strong> Nome app e email di contatto
                                            </li>
                                            <li style="margin-bottom: 8px;">
                                                <strong>Aggiungi il prodotto "Facebook Login"</strong>
                                            </li>
                                            <li style="margin-bottom: 8px;">
                                                <strong>Vai su Impostazioni > Di base</strong> per trovare:
                                                <ul style="margin: 4px 0 0 20px;">
                                                    <li><code
                                                            style="background: #eff1f3; padding: 2px 6px; border-radius: 3px; font-family: monospace;">ID app</code>
                                                        → Copia in "Facebook App ID" sotto</li>
                                                    <li><code
                                                            style="background: #eff1f3; padding: 2px 6px; border-radius: 3px; font-family: monospace;">Chiave segreta dell'app</code>
                                                        → Clicca "Mostra", copia in "Facebook App Secret" sotto</li>
                                                </ul>
                                            </li>
                                            <li style="margin-bottom: 8px;">
                                                <strong>Configura Redirect URI:</strong><br>
                                                Vai su <strong>Facebook Login > Impostazioni</strong><br>
                                                Nel campo "Valid OAuth Redirect URIs" incolla l'URL dal campo
                                                <strong>"Facebook Redirect URI"</strong> sotto
                                            </li>
                                            <li style="margin-bottom: 0;">
                                                <strong style="color: #d1242f;">⚠️ IMPORTANTE:</strong> Pubblica l'app
                                                (passa da "Development" a "Live" in Impostazioni > Di base)
                                            </li>
                                        </ol>
                                        <p
                                            style="margin: 12px 0 0 0; padding: 10px; background: #fff8c5; border-left: 3px solid #9a6700; border-radius: 3px; font-size: 13px;">
                                            💡 <strong>Tip:</strong> Tieni aperta la console Facebook in un'altra tab mentre
                                            compili i campi sotto.
                                        </p>
                                    </div>
                                </details>
                                <div class="bw-settings-group__grid">
                                    <label
                                        for="bw_account_facebook_app_id"><?php esc_html_e('Facebook App ID', 'bw'); ?></label>
                                    <input type="text" id="bw_account_facebook_app_id" name="bw_account_facebook_app_id"
                                        value="<?php echo esc_attr($facebook_app_id); ?>" class="regular-text" />
                                    <label
                                        for="bw_account_facebook_app_secret"><?php esc_html_e('Facebook App Secret', 'bw'); ?></label>
                                    <input type="text" id="bw_account_facebook_app_secret"
                                        name="bw_account_facebook_app_secret"
                                        value="<?php echo esc_attr($facebook_app_secret); ?>" class="regular-text" />
                                    <label><?php esc_html_e('Facebook Redirect URI', 'bw'); ?></label>
                                    <input type="text" readonly class="regular-text"
                                        value="<?php echo esc_url($facebook_redirect); ?>" />
                                </div>
                                <p class="description">
                                    <?php esc_html_e('Use this URL in the Facebook app panel to configure the redirect URI.', 'bw'); ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr class="bw-account-provider-option" data-bw-account-provider="google" <?php echo $google ? '' : 'style="display:none;"'; ?>>
                        <td colspan="2">
                            <div class="bw-settings-group">
                                <div class="bw-settings-group__title"><?php esc_html_e('Google settings', 'bw'); ?></div>
                                <details class="bw-oauth-help-accordion"
                                    style="background: #f0f6fc; border: 1px solid #0969da; border-radius: 6px; padding: 12px; margin-bottom: 10px;">
                                    <summary
                                        style="cursor: pointer; font-weight: 600; color: #0969da; font-size: 14px; user-select: none;">
                                        📗 Come ottenere Google Client ID e Secret
                                    </summary>
                                    <div style="padding: 12px 0 0 0; color: #1f2328; line-height: 1.6;">
                                        <p style="margin: 0 0 12px 0;"><strong>Segui questi passi:</strong></p>
                                        <ol style="margin: 0 0 12px 20px; padding: 0;">
                                            <li style="margin-bottom: 8px;">
                                                <strong>Vai alla Google Cloud Console:</strong><br>
                                                <a href="https://console.cloud.google.com/apis/credentials" target="_blank"
                                                    rel="noopener"
                                                    style="color: #0969da; text-decoration: none; font-weight: 500;">
                                                    🔗 https://console.cloud.google.com/apis/credentials
                                                </a>
                                            </li>
                                            <li style="margin-bottom: 8px;">
                                                <strong>Crea un nuovo progetto</strong> (se non ne hai già uno)<br>
                                                Clicca sul menu progetti in alto e poi "Nuovo progetto"
                                            </li>
                                            <li style="margin-bottom: 8px;">
                                                <strong>Configura schermata consenso OAuth:</strong><br>
                                                <a href="https://console.cloud.google.com/apis/credentials/consent"
                                                    target="_blank" rel="noopener"
                                                    style="color: #0969da; text-decoration: none;">
                                                    🔗 Vai alla schermata consenso
                                                </a><br>
                                                Seleziona "Esterno" (External) e compila i campi obbligatori
                                            </li>
                                            <li style="margin-bottom: 8px;">
                                                <strong>Crea credenziali OAuth 2.0:</strong>
                                                <ul style="margin: 4px 0 0 20px;">
                                                    <li>Clicca "+ Crea credenziali" > "ID client OAuth"</li>
                                                    <li>Tipo: "Applicazione web" (Web application)</li>
                                                    <li>Nome: "BlackWork Login" (o un nome a tua scelta)</li>
                                                </ul>
                                            </li>
                                            <li style="margin-bottom: 8px;">
                                                <strong>Configura Redirect URI:</strong><br>
                                                Nella sezione "URI di reindirizzamento autorizzati":<br>
                                                Clicca "+ Aggiungi URI" e incolla l'URL dal campo <strong>"Google Redirect
                                                    URI"</strong> sotto
                                            </li>
                                            <li style="margin-bottom: 8px;">
                                                <strong>Clicca "Crea"</strong> e copia le credenziali:
                                                <ul style="margin: 4px 0 0 20px;">
                                                    <li><code
                                                            style="background: #eff1f3; padding: 2px 6px; border-radius: 3px; font-family: monospace;">ID client</code>
                                                        → Copia in "Google Client ID" sotto</li>
                                                    <li><code
                                                            style="background: #eff1f3; padding: 2px 6px; border-radius: 3px; font-family: monospace;">Segreto client</code>
                                                        → Copia in "Google Client Secret" sotto</li>
                                                </ul>
                                            </li>
                                            <li style="margin-bottom: 0;">
                                                <strong style="color: #d1242f;">⚠️ IMPORTANTE:</strong> Pubblica l'app OAuth
                                                (passa da "Testing" a "Production" nella schermata consenso)
                                            </li>
                                        </ol>
                                        <p
                                            style="margin: 12px 0 0 0; padding: 10px; background: #fff8c5; border-left: 3px solid #9a6700; border-radius: 3px; font-size: 13px;">
                                            💡 <strong>Tip:</strong> Tieni aperta la console Google in un'altra tab mentre
                                            compili i campi sotto.
                                        </p>
                                    </div>
                                </details>
                                <div class="bw-settings-group__grid">
                                    <label
                                        for="bw_account_google_client_id"><?php esc_html_e('Google Client ID', 'bw'); ?></label>
                                    <input type="text" id="bw_account_google_client_id" name="bw_account_google_client_id"
                                        value="<?php echo esc_attr($google_client_id); ?>" class="regular-text" />
                                    <label
                                        for="bw_account_google_client_secret"><?php esc_html_e('Google Client Secret', 'bw'); ?></label>
                                    <input type="text" id="bw_account_google_client_secret"
                                        name="bw_account_google_client_secret"
                                        value="<?php echo esc_attr($google_client_secret); ?>" class="regular-text" />
                                    <label><?php esc_html_e('Google Redirect URI', 'bw'); ?></label>
                                    <input type="text" readonly class="regular-text"
                                        value="<?php echo esc_url($google_redirect); ?>" />
                                </div>
                                <p class="description">
                                    <?php esc_html_e('Configure this URL in the authorized redirect URIs in the Google Cloud Console.', 'bw'); ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_account_passwordless_url"><?php esc_html_e('URL "Log in Without Password"', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="bw_account_passwordless_url" name="bw_account_passwordless_url"
                                value="<?php echo esc_attr($passwordless_url); ?>" class="regular-text"
                                placeholder="<?php echo esc_url(wp_login_url()); ?>" />
                            <p class="description">
                                <?php esc_html_e('Imposta il link da usare per il login senza password o magic link.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
                <tbody class="bw-login-provider-section" data-bw-login-provider="supabase" <?php echo 'supabase' === $login_provider ? '' : 'style="display:none;"'; ?>>
                    <tr>
                        <th scope="row">
                            <label for="bw_supabase_project_url"><?php esc_html_e('Supabase Project URL', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="bw_supabase_project_url" name="bw_supabase_project_url"
                                value="<?php echo esc_attr($supabase_project_url); ?>" class="regular-text"
                                placeholder="https://xxxx.supabase.co" />
                            <p class="description">
                                <?php esc_html_e('Found in Supabase Dashboard → Settings → API Keys.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bw_supabase_anon_key"><?php esc_html_e('Supabase Anon/Public Key', 'bw'); ?></label>
                        </th>
                        <td>
                            <textarea id="bw_supabase_anon_key" name="bw_supabase_anon_key" rows="4"
                                class="large-text"><?php echo esc_textarea($supabase_anon_key); ?></textarea>
                            <p class="description">
                                <?php esc_html_e('The anon key is safe for client-side usage with RLS enabled. It is used here for server-side Auth calls.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_supabase_service_role_key"><?php esc_html_e('Supabase Service Role Key (optional)', 'bw'); ?></label>
                        </th>
                        <td>
                            <textarea id="bw_supabase_service_role_key" name="bw_supabase_service_role_key" rows="4"
                                class="large-text"><?php echo esc_textarea($supabase_service_key); ?></textarea>
                            <p class="description">
                                <?php esc_html_e('Service role bypasses RLS and must never be exposed to the browser. Keep it server-side only.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bw_supabase_auth_mode"><?php esc_html_e('Auth Mode', 'bw'); ?></label>
                        </th>
                        <td>
                            <select id="bw_supabase_auth_mode" name="bw_supabase_auth_mode">
                                <option value="password" <?php selected('password', $supabase_auth_mode); ?>>
                                    <?php esc_html_e('Email + Password', 'bw'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Uses POST /auth/v1/token?grant_type=password for server-side login.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bw_supabase_login_mode"><?php esc_html_e('Login Mode', 'bw'); ?></label>
                        </th>
                        <td>
                            <select id="bw_supabase_login_mode" name="bw_supabase_login_mode">
                                <option value="native" <?php selected('native', $supabase_login_mode); ?>>
                                    <?php esc_html_e('Native Supabase Login (email/password)', 'bw'); ?>
                                </option>
                                <option value="oidc" <?php selected('oidc', $supabase_login_mode); ?>>
                                    <?php esc_html_e('OIDC Login (OpenID Connect redirect)', 'bw'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Choose whether login uses the Supabase password flow or redirects to OpenID Connect.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_supabase_with_plugins"><?php esc_html_e('SupabaseWithPlugins (OIDC)', 'bw'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="bw_supabase_with_plugins" name="bw_supabase_with_plugins"
                                    value="1" <?php checked(1, $supabase_with_plugins); ?> />
                                <?php esc_html_e('Enable OIDC plugin integration', 'bw'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When enabled, authentication is handled by OpenID Connect Generic Client (OIDC redirect flow). The frontend form keeps the same style, but password is not submitted to WordPress.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-supabase-oidc-warning" <?php echo ($supabase_with_plugins && 'native' === $supabase_login_mode) ? '' : 'style="display:none;"'; ?>>
                        <th scope="row"><?php esc_html_e('OIDC login notice', 'bw'); ?></th>
                        <td>
                            <p class="description">
                                <?php esc_html_e('OIDC enabled but login is set to native email/password. OIDC will not hijack the login submit.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_supabase_registration_mode"><?php esc_html_e('Registration Mode', 'bw'); ?></label>
                        </th>
                        <td>
                            <select id="bw_supabase_registration_mode" name="bw_supabase_registration_mode">
                                <option value="R1" <?php selected('R1', $supabase_registration); ?>>
                                    <?php esc_html_e('Redirect to Provider Signup (recommended)', 'bw'); ?>
                                </option>
                                <option value="R2" <?php selected('R2', $supabase_registration); ?>>
                                    <?php esc_html_e('Native Supabase Registration (email/password)', 'bw'); ?>
                                </option>
                                <option value="R3" <?php selected('R3', $supabase_registration); ?>>
                                    <?php esc_html_e('Disable Registration', 'bw'); ?>
                                </option>
                            </select>
                            <p class="description"><strong><?php esc_html_e('R1 (Redirect):', 'bw'); ?></strong>
                                <?php esc_html_e('Register tab will show a CTA button that redirects to the Provider signup page. In OIDC mode, WordPress user is created after first successful login if the OIDC plugin is configured to create users.', 'bw'); ?>
                            </p>
                            <p class="description"><strong><?php esc_html_e('R2 (Native):', 'bw'); ?></strong>
                                <?php esc_html_e('Register tab will show the full Supabase email/password registration form and will create the Supabase user via Supabase Auth API. In OIDC mode, login remains OIDC; registration is still native via Supabase API.', 'bw'); ?>
                            </p>
                            <p class="description"><strong><?php esc_html_e('R3 (Disable):', 'bw'); ?></strong>
                                <?php esc_html_e('Register tab is hidden or disabled. Users can only log in. Use this if you manage accounts externally.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-supabase-registration-option" data-bw-registration-mode="R1">
                        <th scope="row">
                            <label
                                for="bw_supabase_provider_signup_url"><?php esc_html_e('Provider Signup URL', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="bw_supabase_provider_signup_url" name="bw_supabase_provider_signup_url"
                                value="<?php echo esc_attr($supabase_signup_url); ?>" class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('Where users create a new account (Supabase/Provider hosted signup).', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-supabase-oidc-option" data-bw-oidc="1">
                        <th scope="row">
                            <label
                                for="bw_supabase_provider_reset_url"><?php esc_html_e('Provider Reset URL', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="bw_supabase_provider_reset_url" name="bw_supabase_provider_reset_url"
                                value="<?php echo esc_attr($supabase_reset_url); ?>" class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('Reset password page hosted by your provider (used in OIDC mode).', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-supabase-registration-option" data-bw-registration-mode="R2">
                        <th scope="row">
                            <label
                                for="bw_supabase_email_confirm_redirect_url"><?php esc_html_e('Email Confirm Redirect URL', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bw_supabase_email_confirm_redirect_url"
                                name="bw_supabase_email_confirm_redirect_url"
                                value="<?php echo esc_attr($supabase_confirm_url); ?>" class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('URL where Supabase redirects the user after email confirmation. Must be allowlisted in Supabase Redirect URLs.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-supabase-registration-option" data-bw-registration-mode="R2">
                        <th scope="row">
                            <label
                                for="bw_supabase_auto_login_after_confirm"><?php esc_html_e('Auto-login after email confirmation', 'bw'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="bw_supabase_auto_login_after_confirm"
                                    name="bw_supabase_auto_login_after_confirm" value="1" <?php checked(1, $supabase_auto_login); ?> />
                                <?php esc_html_e('Attempt to log users into WordPress after Supabase email confirmation.', 'bw'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When enabled, the frontend bridges the #access_token fragment to WordPress via AJAX to create a WP session.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-supabase-registration-option" data-bw-registration-mode="R2">
                        <th scope="row">
                            <label
                                for="bw_supabase_create_wp_users"><?php esc_html_e('Create WordPress user if missing', 'bw'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="bw_supabase_create_wp_users" name="bw_supabase_create_wp_users"
                                    value="1" <?php checked(1, $supabase_create_users); ?> />
                                <?php esc_html_e('Create a WordPress user automatically when Supabase confirms a new email.', 'bw'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('If enabled, create a WP user automatically when a Supabase-confirmed email does not exist in WordPress.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-supabase-registration-note">
                        <th scope="row"><?php esc_html_e('Email confirmation auto-login', 'bw'); ?></th>
                        <td>
                            <p class="description">
                                <?php esc_html_e('Email confirmation auto-login settings are only used for R2 Native Supabase Registration.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Magic link login', 'bw'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="bw_supabase_magic_link_enabled"
                                    name="bw_supabase_magic_link_enabled" value="1" <?php checked(1, $supabase_magic_link_enabled); ?> />
                                <?php esc_html_e('Enable magic link email login', 'bw'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('Uses Supabase /auth/v1/otp magic link. Users receive a sign-in link by email.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('OTP signup behavior', 'bw'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="bw_supabase_otp_allow_signup" name="bw_supabase_otp_allow_signup"
                                    value="1" <?php checked(1, $supabase_otp_allow_signup); ?> />
                                <?php esc_html_e('Allow OTP login to create Supabase users', 'bw'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When disabled, OTP login only works for existing Supabase users and will show an error for unknown emails.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('OAuth login providers', 'bw'); ?></th>
                        <td>
                            <label style="display:block; margin-bottom:8px;">
                                <input type="checkbox" id="bw_supabase_oauth_google_enabled"
                                    name="bw_supabase_oauth_google_enabled" value="1" <?php checked(1, $supabase_oauth_google_enabled); ?> />
                                <?php esc_html_e('Enable Google OAuth', 'bw'); ?>
                            </label>
                            <label style="display:block;">
                                <input type="checkbox" id="bw_supabase_oauth_facebook_enabled"
                                    name="bw_supabase_oauth_facebook_enabled" value="1" <?php checked(1, $supabase_oauth_facebook_enabled); ?> />
                                <?php esc_html_e('Enable Facebook OAuth', 'bw'); ?>
                            </label>
                            <label style="display:block; margin-top:8px;">
                                <input type="checkbox" id="bw_supabase_oauth_apple_enabled"
                                    name="bw_supabase_oauth_apple_enabled" value="1" <?php checked(1, $supabase_oauth_apple_enabled); ?> />
                                <?php esc_html_e('Enable Apple OAuth', 'bw'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('These fields are used to configure the provider and will be needed in Supabase Auth settings. Keep secrets private.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-supabase-google-option" <?php echo $supabase_oauth_google_enabled ? '' : 'style="display:none;"'; ?>>
                        <th scope="row"><?php esc_html_e('Google settings', 'bw'); ?></th>
                        <td>
                            <div class="bw-provider-box bw-provider-box--google bw-settings-group">
                                <div class="bw-provider-box__title"><?php esc_html_e('Google settings', 'bw'); ?></div>
                                <div class="bw-provider-box__grid">
                                    <label
                                        for="bw_supabase_google_client_id"><?php esc_html_e('Client ID', 'bw'); ?></label>
                                    <input type="text" id="bw_supabase_google_client_id" name="bw_supabase_google_client_id"
                                        value="<?php echo esc_attr($supabase_google_client_id); ?>" class="regular-text" />
                                    <label
                                        for="bw_supabase_google_client_secret"><?php esc_html_e('Client Secret', 'bw'); ?></label>
                                    <input type="text" id="bw_supabase_google_client_secret"
                                        name="bw_supabase_google_client_secret"
                                        value="<?php echo esc_attr($supabase_google_client_secret); ?>"
                                        class="regular-text" />
                                    <label
                                        for="bw_supabase_google_redirect_url"><?php esc_html_e('Redirect URL', 'bw'); ?></label>
                                    <input type="url" id="bw_supabase_google_redirect_url"
                                        name="bw_supabase_google_redirect_url"
                                        value="<?php echo esc_attr($supabase_google_redirect_url); ?>"
                                        class="regular-text" />
                                    <label for="bw_supabase_google_scopes"><?php esc_html_e('Scopes', 'bw'); ?></label>
                                    <input type="text" id="bw_supabase_google_scopes" name="bw_supabase_google_scopes"
                                        value="<?php echo esc_attr($supabase_google_scopes); ?>" class="regular-text" />
                                    <label for="bw_supabase_google_prompt"><?php esc_html_e('Prompt', 'bw'); ?></label>
                                    <input type="text" id="bw_supabase_google_prompt" name="bw_supabase_google_prompt"
                                        value="<?php echo esc_attr($supabase_google_prompt); ?>" class="regular-text" />
                                </div>
                                <p class="description">
                                    <?php esc_html_e('These fields are used to configure the provider and will be needed in Supabase Auth settings. Keep secrets private.', 'bw'); ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr class="bw-supabase-facebook-option" <?php echo $supabase_oauth_facebook_enabled ? '' : 'style="display:none;"'; ?>>
                        <th scope="row"><?php esc_html_e('Facebook settings', 'bw'); ?></th>
                        <td>
                            <div class="bw-provider-box bw-provider-box--facebook bw-settings-group">
                                <div class="bw-provider-box__title"><?php esc_html_e('Facebook settings', 'bw'); ?></div>
                                <div class="bw-provider-box__grid">
                                    <label for="bw_supabase_facebook_app_id"><?php esc_html_e('App ID', 'bw'); ?></label>
                                    <input type="text" id="bw_supabase_facebook_app_id" name="bw_supabase_facebook_app_id"
                                        value="<?php echo esc_attr($supabase_facebook_app_id); ?>" class="regular-text" />
                                    <label
                                        for="bw_supabase_facebook_app_secret"><?php esc_html_e('App Secret', 'bw'); ?></label>
                                    <input type="text" id="bw_supabase_facebook_app_secret"
                                        name="bw_supabase_facebook_app_secret"
                                        value="<?php echo esc_attr($supabase_facebook_app_secret); ?>"
                                        class="regular-text" />
                                    <label
                                        for="bw_supabase_facebook_redirect_url"><?php esc_html_e('Redirect URL', 'bw'); ?></label>
                                    <input type="url" id="bw_supabase_facebook_redirect_url"
                                        name="bw_supabase_facebook_redirect_url"
                                        value="<?php echo esc_attr($supabase_facebook_redirect_url); ?>"
                                        class="regular-text" />
                                    <label for="bw_supabase_facebook_scopes"><?php esc_html_e('Scopes', 'bw'); ?></label>
                                    <input type="text" id="bw_supabase_facebook_scopes" name="bw_supabase_facebook_scopes"
                                        value="<?php echo esc_attr($supabase_facebook_scopes); ?>" class="regular-text" />
                                </div>
                                <p class="description">
                                    <?php esc_html_e('These fields are used to configure the provider and will be needed in Supabase Auth settings. Keep secrets private.', 'bw'); ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr class="bw-supabase-apple-option" <?php echo $supabase_oauth_apple_enabled ? '' : 'style="display:none;"'; ?>>
                        <th scope="row"><?php esc_html_e('Apple settings', 'bw'); ?></th>
                        <td>
                            <div class="bw-provider-box bw-provider-box--apple bw-settings-group">
                                <div class="bw-provider-box__title"><?php esc_html_e('Apple settings', 'bw'); ?></div>
                                <div class="bw-provider-box__grid">
                                    <label for="bw_supabase_apple_client_id"><?php esc_html_e('Client ID', 'bw'); ?></label>
                                    <input type="text" id="bw_supabase_apple_client_id" name="bw_supabase_apple_client_id"
                                        value="<?php echo esc_attr($supabase_apple_client_id); ?>" class="regular-text" />
                                    <label for="bw_supabase_apple_team_id"><?php esc_html_e('Team ID', 'bw'); ?></label>
                                    <input type="text" id="bw_supabase_apple_team_id" name="bw_supabase_apple_team_id"
                                        value="<?php echo esc_attr($supabase_apple_team_id); ?>" class="regular-text" />
                                    <label for="bw_supabase_apple_key_id"><?php esc_html_e('Key ID', 'bw'); ?></label>
                                    <input type="text" id="bw_supabase_apple_key_id" name="bw_supabase_apple_key_id"
                                        value="<?php echo esc_attr($supabase_apple_key_id); ?>" class="regular-text" />
                                    <label
                                        for="bw_supabase_apple_private_key"><?php esc_html_e('Private Key', 'bw'); ?></label>
                                    <textarea id="bw_supabase_apple_private_key" name="bw_supabase_apple_private_key"
                                        rows="4"
                                        class="large-text"><?php echo esc_textarea($supabase_apple_private_key); ?></textarea>
                                    <label
                                        for="bw_supabase_apple_redirect_url"><?php esc_html_e('Redirect URL', 'bw'); ?></label>
                                    <input type="url" id="bw_supabase_apple_redirect_url"
                                        name="bw_supabase_apple_redirect_url"
                                        value="<?php echo esc_attr($supabase_apple_redirect_url); ?>"
                                        class="regular-text" />
                                </div>
                                <p class="description">
                                    <?php esc_html_e('These fields are used to configure the provider and will be needed in Supabase Auth settings. Keep secrets private.', 'bw'); ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Password login', 'bw'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="bw_supabase_login_password_enabled"
                                    name="bw_supabase_login_password_enabled" value="1" <?php checked(1, $supabase_password_enabled); ?> />
                                <?php esc_html_e('Enable login with password button', 'bw'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_supabase_magic_link_redirect_url"><?php esc_html_e('Magic link redirect URL', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bw_supabase_magic_link_redirect_url"
                                name="bw_supabase_magic_link_redirect_url"
                                value="<?php echo esc_attr($supabase_magic_link_redirect); ?>" class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('Redirect after magic link login (must be allowlisted in Supabase).', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_supabase_oauth_redirect_url"><?php esc_html_e('OAuth redirect URL', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bw_supabase_oauth_redirect_url" name="bw_supabase_oauth_redirect_url"
                                value="<?php echo esc_attr($supabase_oauth_redirect); ?>" class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('Redirect after Google/Facebook OAuth (must be allowlisted in Supabase).', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_supabase_signup_redirect_url"><?php esc_html_e('Signup confirmation redirect URL', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bw_supabase_signup_redirect_url" name="bw_supabase_signup_redirect_url"
                                value="<?php echo esc_attr($supabase_signup_redirect); ?>" class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('Redirect after confirming signup email (must be allowlisted in Supabase).', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <?php if ($supabase_with_plugins): ?>
                        <?php
                        $oidc_active = function_exists('bw_oidc_is_active') ? bw_oidc_is_active() : false;
                        $oidc_auth_url = function_exists('bw_oidc_get_auth_url') ? bw_oidc_get_auth_url() : '';
                        $oidc_redirect = function_exists('bw_oidc_get_redirect_uri') ? bw_oidc_get_redirect_uri() : '';
                        $oidc_provider = function_exists('bw_oidc_get_provider_base_url') ? bw_oidc_get_provider_base_url() : '';
                        ?>
                        <tr>
                            <th scope="row"><?php esc_html_e('OIDC Integration Status', 'bw'); ?></th>
                            <td>
                                <p><strong><?php esc_html_e('Plugin active:', 'bw'); ?></strong>
                                    <?php echo $oidc_active ? esc_html__('Yes', 'bw') : esc_html__('No', 'bw'); ?></p>
                                <p><strong><?php esc_html_e('Redirect URI:', 'bw'); ?></strong>
                                    <?php echo $oidc_redirect ? esc_html($oidc_redirect) : esc_html__('Not available', 'bw'); ?>
                                </p>
                                <p><strong><?php esc_html_e('Auth URL:', 'bw'); ?></strong>
                                    <?php echo $oidc_auth_url ? esc_html($oidc_auth_url) : esc_html__('Not available', 'bw'); ?>
                                </p>
                                <?php if ($oidc_provider): ?>
                                    <p><strong><?php esc_html_e('Provider base URL:', 'bw'); ?></strong>
                                        <?php echo esc_html($oidc_provider); ?></p>
                                <?php endif; ?>
                                <?php if (!$oidc_active): ?>
                                    <div class="notice notice-warning inline">
                                        <p><?php esc_html_e('Install/activate OpenID Connect Generic Client and configure Client ID/Secret + endpoints.', 'bw'); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_supabase_jwt_cookie_name"><?php esc_html_e('Supabase JWT Cookie Name', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bw_supabase_jwt_cookie_name" name="bw_supabase_jwt_cookie_name"
                                value="<?php echo esc_attr($supabase_cookie_name); ?>" class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('Base name used for access/refresh cookies when session storage is set to secure cookie.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bw_supabase_session_storage"><?php esc_html_e('Session Storage', 'bw'); ?></label>
                        </th>
                        <td>
                            <select id="bw_supabase_session_storage" name="bw_supabase_session_storage">
                                <option value="cookie" <?php selected('cookie', $supabase_storage); ?>>
                                    <?php esc_html_e('Secure cookie only', 'bw'); ?>
                                </option>
                                <option value="usermeta" <?php selected('usermeta', $supabase_storage); ?>>
                                    <?php esc_html_e('WP usermeta', 'bw'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Choose where Supabase session tokens are stored after login.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_supabase_enable_wp_user_linking"><?php esc_html_e('Link Supabase users to WP users', 'bw'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="bw_supabase_enable_wp_user_linking"
                                    name="bw_supabase_enable_wp_user_linking" value="1" <?php checked(1, $supabase_link_users); ?> />
                                <?php esc_html_e('Match existing WordPress users by email on Supabase login.', 'bw'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bw_supabase_debug_log"><?php esc_html_e('Debug logging', 'bw'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="bw_supabase_debug_log" name="bw_supabase_debug_log" value="1"
                                    <?php checked(1, $supabase_debug_log); ?> />
                                <?php esc_html_e('Log Supabase Auth status codes (never logs credentials).', 'bw'); ?>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="bw-account-settings-tab" data-bw-account-tab="admin-login-link" style="display:none;">
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('WordPress Admin Login Shortcut', 'bw'); ?>
                        </th>
                        <td>
                            <p class="description">
                                <?php esc_html_e('Create a custom URL that opens the standard WordPress login screen.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bw_custom_wp_login_slug"><?php esc_html_e('Customize WordPress Login', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bw_custom_wp_login_slug" name="bw_custom_wp_login_slug"
                                value="<?php echo esc_attr($custom_wp_login_slug); ?>" class="regular-text"
                                placeholder="blackwork-private" />
                            <p class="description">
                                <?php esc_html_e('This creates a custom shortcut to the standard WordPress login page. It does not disable wp-login.php or wp-admin.', 'bw'); ?>
                            </p>
                            <?php if ('' !== $custom_wp_login_url): ?>
                                <p class="description">
                                    <strong><?php esc_html_e('Current custom login URL:', 'bw'); ?></strong>
                                    <a href="<?php echo esc_url($custom_wp_login_url); ?>" target="_blank" rel="noopener">
                                        <?php echo esc_html($custom_wp_login_url); ?>
                                    </a>
                                </p>
                            <?php else: ?>
                                <p class="description">
                                    <strong><?php esc_html_e('Custom login shortcut disabled.', 'bw'); ?></strong>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bw_custom_wp_login_hide_default"><?php esc_html_e('Hide default WordPress login URLs', 'bw'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="bw_custom_wp_login_hide_default" name="bw_custom_wp_login_hide_default" value="1" <?php checked(1, $custom_wp_login_hide_default); ?> />
                                <?php esc_html_e('Hide default WordPress login URLs', 'bw'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When enabled, logged-out visitors opening /wp-login.php or /wp-admin/ will receive a 404 only if recovery mode is disabled and your custom login shortcut is configured correctly.', 'bw'); ?>
                            </p>
                            <p class="description">
                                <strong><?php esc_html_e('Warning:', 'bw'); ?></strong>
                                <?php esc_html_e('Hiding default WordPress login URLs can lock you out. Disable recovery mode only after you have saved and tested the custom login URL end to end.', 'bw'); ?>
                            </p>
                            <label>
                                <input type="checkbox" id="bw_custom_wp_login_recovery_mode" name="bw_custom_wp_login_recovery_mode" value="1" <?php checked(1, $custom_wp_login_recovery_mode); ?> />
                                <?php esc_html_e('Recovery mode: keep default /wp-login.php and /wp-admin/ available for admin recovery.', 'bw'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When recovery mode is enabled, hide-default protection is not active. Default WordPress login/admin URLs remain accessible until you explicitly disable recovery mode.', 'bw'); ?>
                            </p>
                            <p class="description">
                                <?php esc_html_e('If the custom login slug is missing or invalid, the plugin will fail open and keep default login/admin URLs available to prevent lockout.', 'bw'); ?>
                            </p>
                            <p class="description">
                                <?php esc_html_e('If you still lose access, define BLACKWORK_ALLOW_DEFAULT_WP_LOGIN as true in wp-config.php as a last-resort emergency override.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php submit_button('Salva impostazioni', 'primary', 'bw_account_page_submit'); ?>
    </form>

    <script>
        jQuery(document).ready(function($) {
            $('.bw-media-upload').on('click', function (e) {
                e.preventDefault();

                const targetInput = $(this).data('target');
                const targetIdInput = $(this).data('id-target');
                const frame = wp.media({
                    title: 'Seleziona immagine',
                    button: { text: 'Usa questa immagine' },
                    multiple: false
                });

                frame.on('select', function () {
                    const attachment = frame.state().get('selection').first().toJSON();
                    $(targetInput).val(attachment.url);
                    if (targetIdInput) {
                        $(targetIdInput).val(attachment.id);
                    }
                });

                frame.open();
            });

            $('#bw_account_login_image').on('input', function () {
                $('#bw_account_login_image_id').val('');
            });

            $('#bw_account_logo').on('input', function () {
                $('#bw_account_logo_id').val('');
            });

            var accountTabLinks = $('.bw-account-settings-tabs .nav-tab');
            var accountTabs = $('.bw-account-settings-tab');

            var providerRadios = $('input[name="bw_account_login_provider"]');
            var providerSections = $('.bw-login-provider-section');
            var loginCopyRows = $('.bw-login-copy-field');
            var registrationMode = $('#bw_supabase_registration_mode');
            var registrationRows = $('.bw-supabase-registration-option');
            var registrationNote = $('.bw-supabase-registration-note');
            var oidcRows = $('.bw-supabase-oidc-option');
            var oidcToggle = $('#bw_supabase_with_plugins');
            var loginMode = $('#bw_supabase_login_mode');
            var oidcWarning = $('.bw-supabase-oidc-warning');
            var appleToggle = $('#bw_supabase_oauth_apple_enabled');
            var appleRows = $('.bw-supabase-apple-option');
            var googleToggle = $('#bw_supabase_oauth_google_enabled');
            var facebookToggle = $('#bw_supabase_oauth_facebook_enabled');
            var googleRows = $('.bw-supabase-google-option');
            var facebookRows = $('.bw-supabase-facebook-option');
            var accountFacebookToggle = $('#bw_account_facebook');
            var accountGoogleToggle = $('#bw_account_google');
            var accountProviderRows = $('.bw-account-provider-option');

            var setAccountTab = function (tab) {
                accountTabLinks.each(function () {
                    var $link = $(this);
                    var linkTab = ($link.attr('href') || '').replace('#', '');
                    var isActive = linkTab === tab;
                    $link.toggleClass('nav-tab-active', isActive);
                    $link.attr('aria-selected', isActive ? 'true' : 'false');
                });

                accountTabs.each(function () {
                    var $tab = $(this);
                    var tabName = $tab.data('bw-account-tab');
                    $tab.toggle(tabName === tab);
                });
            };

            var toggleProviderSections = function (provider) {
                providerSections.each(function () {
                    var $section = $(this);
                    var sectionProvider = $section.data('bw-login-provider');
                    $section.toggle(sectionProvider === provider);
                });
            };

            var toggleLoginCopyFields = function (provider) {
                loginCopyRows.each(function () {
                    var $row = $(this);
                    var rowProvider = $row.data('bw-login-copy-provider');
                    $row.toggle(rowProvider === provider);
                });
            };

            var toggleRegistrationMode = function (mode) {
                registrationRows.each(function () {
                    var $row = $(this);
                    $row.toggle($row.data('bw-registration-mode') === mode);
                });
                if (registrationNote.length) {
                    registrationNote.toggle(mode !== 'R2');
                }
            };

            var toggleOidcRows = function (enabled) {
                oidcRows.toggle(!!enabled);
            };

            var toggleOidcWarning = function (enabled, mode) {
                if (!oidcWarning.length) {
                    return;
                }

                oidcWarning.toggle(!!enabled && mode === 'native');
            };

            var toggleAppleRows = function (enabled) {
                appleRows.toggle(!!enabled);
            };

            var toggleGoogleRows = function (enabled) {
                googleRows.toggle(!!enabled);
            };

            var toggleFacebookRows = function (enabled) {
                facebookRows.toggle(!!enabled);
            };

            var toggleAccountProviderRows = function (provider, enabled) {
                accountProviderRows.filter('[data-bw-account-provider="' + provider + '"]').toggle(!!enabled);
            };

            var initialAccountTab = window.location.hash ? window.location.hash.replace('#', '') : 'design';
            if(initialAccountTab !== 'technical' && initialAccountTab !== 'design') {
            initialAccountTab = 'design';
        }
        setAccountTab(initialAccountTab);

        toggleProviderSections(providerRadios.filter(':checked').val() || 'wordpress');
        toggleLoginCopyFields(providerRadios.filter(':checked').val() || 'wordpress');
        toggleRegistrationMode(registrationMode.val());
        toggleOidcRows(oidcToggle.is(':checked'));
        toggleOidcWarning(oidcToggle.is(':checked'), loginMode.val());
        toggleAppleRows(appleToggle.is(':checked'));
        toggleGoogleRows(googleToggle.is(':checked'));
        toggleFacebookRows(facebookToggle.is(':checked'));
        toggleAccountProviderRows('facebook', accountFacebookToggle.is(':checked'));
        toggleAccountProviderRows('google', accountGoogleToggle.is(':checked'));

        accountTabLinks.on('click', function (event) {
            event.preventDefault();
            var tab = ($(this).attr('href') || '').replace('#', '');
            if (!tab) {
                return;
            }
            setAccountTab(tab);
            if (history.replaceState) {
                history.replaceState(null, document.title, '#' + tab);
            } else {
                window.location.hash = tab;
            }
        });

        $(window).on('hashchange', function () {
            var tab = window.location.hash ? window.location.hash.replace('#', '') : 'design';
            if (tab !== 'technical' && tab !== 'design') {
                tab = 'design';
            }
            setAccountTab(tab);
        });

        providerRadios.on('change', function () {
            var provider = $(this).val();
            toggleProviderSections(provider);
            toggleLoginCopyFields(provider);
        });

        registrationMode.on('change', function () {
            toggleRegistrationMode($(this).val());
        });

        oidcToggle.on('change', function () {
            var enabled = $(this).is(':checked');
            toggleOidcRows(enabled);
            toggleOidcWarning(enabled, loginMode.val());
        });

        loginMode.on('change', function () {
            toggleOidcWarning(oidcToggle.is(':checked'), $(this).val());
        });

        appleToggle.on('change', function () {
            toggleAppleRows($(this).is(':checked'));
        });

        googleToggle.on('change', function () {
            toggleGoogleRows($(this).is(':checked'));
        });

        facebookToggle.on('change', function () {
            toggleFacebookRows($(this).is(':checked'));
        });

        accountFacebookToggle.on('change', function () {
            toggleAccountProviderRows('facebook', $(this).is(':checked'));
        });

        accountGoogleToggle.on('change', function () {
            toggleAccountProviderRows('google', $(this).is(':checked'));
        });
                                                                                        });
    </script>
    <?php
}

/**
 * Render the My Account front-end customization tab.
 */
function bw_site_render_my_account_front_tab()
{
    $saved = false;

    if (isset($_POST['bw_myaccount_content_submit'])) {
        if (!current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('bw_myaccount_front_save', 'bw_myaccount_front_nonce');

        $black_box_text = isset($_POST['bw_myaccount_black_box_text'])
            ? wp_kses_post(wp_unslash($_POST['bw_myaccount_black_box_text']))
            : '';
        $support_link = isset($_POST['bw_myaccount_support_link'])
            ? esc_url_raw(wp_unslash($_POST['bw_myaccount_support_link']))
            : '';

        update_option('bw_myaccount_black_box_text', $black_box_text);
        update_option('bw_myaccount_support_link', $support_link);

        $saved = true;
    }

    $black_box_text = get_option(
        'bw_myaccount_black_box_text',
        __('Your mockups will always be here, available to download. Please enjoy them!', 'bw')
    );
    $support_link = get_option('bw_myaccount_support_link', '');
    ?>
    <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong><?php esc_html_e('Impostazioni salvate con successo!', 'bw'); ?></strong></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('bw_myaccount_front_save', 'bw_myaccount_front_nonce'); ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label
                        for="bw_myaccount_black_box_text"><?php esc_html_e('Testo Box Nero (My Account)', 'bw'); ?></label>
                </th>
                <td>
                    <textarea id="bw_myaccount_black_box_text" name="bw_myaccount_black_box_text" rows="6"
                        class="large-text"><?php echo esc_textarea($black_box_text); ?></textarea>
                    <p class="description">
                        <?php esc_html_e('Contenuto mostrato nel box nero in alto alla dashboard My Account. Puoi utilizzare HTML semplice; il testo verrà sanificato.', 'bw'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_myaccount_support_link"><?php esc_html_e('Link Support (My Account)', 'bw'); ?></label>
                </th>
                <td>
                    <input id="bw_myaccount_support_link" name="bw_myaccount_support_link" type="url"
                        class="regular-text" value="<?php echo esc_attr($support_link); ?>" placeholder="https://blackwork.pro/support/" />
                    <p class="description">
                        <?php esc_html_e('URL del pulsante "Contact support" nel box nero della dashboard My Account.', 'bw'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Salva impostazioni', 'bw'), 'primary', 'bw_myaccount_content_submit'); ?>
    </form>
    <?php
}
