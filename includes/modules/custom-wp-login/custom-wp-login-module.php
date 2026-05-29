<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_custom_wp_login_get_reserved_slugs')) {
    function bw_custom_wp_login_get_reserved_slugs()
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
}

if (!function_exists('bw_custom_wp_login_get_slug')) {
    function bw_custom_wp_login_get_slug()
    {
        $slug = (string) get_option('bw_custom_wp_login_slug', '');
        $slug = sanitize_title(trim($slug, "/ \t\n\r\0\x0B"));
        if ('' === $slug || in_array($slug, bw_custom_wp_login_get_reserved_slugs(), true)) {
            return '';
        }

        return $slug;
    }
}

if (!function_exists('bw_custom_wp_login_register_rewrite')) {
    function bw_custom_wp_login_register_rewrite()
    {
        $slug = bw_custom_wp_login_get_slug();
        if ('' === $slug) {
            return;
        }

        add_rewrite_tag('%bw_custom_login%', '1');
        add_rewrite_rule('^' . preg_quote($slug, '#') . '/?$', 'index.php?bw_custom_login=1', 'top');
    }
}

if (!function_exists('bw_custom_wp_login_register_query_var')) {
    function bw_custom_wp_login_register_query_var($vars)
    {
        $vars = is_array($vars) ? $vars : [];
        $vars[] = 'bw_custom_login';

        return array_values(array_unique($vars));
    }
}

if (!function_exists('bw_custom_wp_login_template_redirect')) {
    function bw_custom_wp_login_template_redirect()
    {
        if (!get_query_var('bw_custom_login')) {
            return;
        }

        require ABSPATH . 'wp-login.php';
        exit;
    }
}

if (!function_exists('bw_custom_wp_login_flush_rewrite')) {
    function bw_custom_wp_login_flush_rewrite()
    {
        bw_custom_wp_login_register_rewrite();
        flush_rewrite_rules(false);
    }
}

if (!function_exists('bw_custom_wp_login_on_activation')) {
    function bw_custom_wp_login_on_activation()
    {
        bw_custom_wp_login_flush_rewrite();
    }
}

if (!function_exists('bw_custom_wp_login_maybe_flush_rewrite')) {
    function bw_custom_wp_login_maybe_flush_rewrite()
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        $needs_flush = (int) get_option('bw_custom_wp_login_slug_needs_flush', 0);
        if (1 !== $needs_flush) {
            return;
        }

        bw_custom_wp_login_flush_rewrite();
        delete_option('bw_custom_wp_login_slug_needs_flush');
    }
}

add_action('init', 'bw_custom_wp_login_register_rewrite', 9);
add_filter('query_vars', 'bw_custom_wp_login_register_query_var');
add_action('template_redirect', 'bw_custom_wp_login_template_redirect', 5);
add_action('admin_init', 'bw_custom_wp_login_maybe_flush_rewrite');

