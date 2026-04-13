<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_header_get_account_context')) {
    function bw_header_get_account_context()
    {
        $account_url = function_exists('wc_get_page_permalink')
            ? wc_get_page_permalink('myaccount')
            : home_url('/my-account/');

        if (empty($account_url)) {
            $account_url = home_url('/my-account/');
        }

        $current_user = is_user_logged_in() ? wp_get_current_user() : null;
        $is_logged_in = $current_user instanceof WP_User && $current_user->exists();
        $user_display_name = '';
        $user_email = '';

        if ($is_logged_in) {
            $user_display_name = trim((string) $current_user->display_name);
            $user_email = sanitize_email((string) $current_user->user_email);

            if ($user_display_name === '' && $user_email !== '') {
                $user_display_name = $user_email;
            }

            if ($user_display_name === '') {
                $user_display_name = __('My account', 'bw');
            }
        }

        $profile_title = $is_logged_in ? $user_display_name : __('Login or Join', 'bw');
        $profile_subtitle = $is_logged_in
            ? ($user_email !== '' ? $user_email : __('Member profile', 'bw'))
            : __('Access your account', 'bw');
        $profile_cta_label = $is_logged_in ? __('Account', 'bw') : __('Login or Join', 'bw');
        $logout_url = $is_logged_in ? wp_logout_url(add_query_arg('logged_out', '1', $account_url)) : '';
        $avatar_html = $is_logged_in ? get_avatar($current_user->ID, 96, '', $user_display_name, ['class' => 'bw-navigation__profile-avatar-image']) : '';

        return [
            'account_url' => $account_url,
            'avatar_html' => $avatar_html,
            'is_logged_in' => $is_logged_in,
            'logout_url' => $logout_url,
            'profile_cta_label' => $profile_cta_label,
            'profile_subtitle' => $profile_subtitle,
            'profile_title' => $profile_title,
            'user_display_name' => $user_display_name,
            'user_email' => $user_email,
        ];
    }
}

if (!function_exists('bw_header_render_account_module')) {
    function bw_header_render_account_module($account_context = null)
    {
        if (!is_array($account_context)) {
            $account_context = bw_header_get_account_context();
        }

        $account_url = isset($account_context['account_url']) ? (string) $account_context['account_url'] : home_url('/my-account/');
        $avatar_html = isset($account_context['avatar_html']) ? (string) $account_context['avatar_html'] : '';
        $is_logged_in = !empty($account_context['is_logged_in']);
        $logout_url = isset($account_context['logout_url']) ? (string) $account_context['logout_url'] : '';
        $profile_cta_label = isset($account_context['profile_cta_label']) ? (string) $account_context['profile_cta_label'] : __('Account', 'bw');
        $profile_subtitle = isset($account_context['profile_subtitle']) ? (string) $account_context['profile_subtitle'] : __('Member profile', 'bw');
        $profile_title = isset($account_context['profile_title']) ? (string) $account_context['profile_title'] : __('My account', 'bw');

        ob_start();
        include BW_MEW_PATH . 'includes/modules/header/templates/parts/account-module.php';
        return (string) ob_get_clean();
    }
}
