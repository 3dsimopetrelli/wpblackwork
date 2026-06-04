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

if (!function_exists('bw_custom_wp_login_is_shortcut_request')) {
    function bw_custom_wp_login_is_shortcut_request()
    {
        return defined('BW_CUSTOM_WP_LOGIN_SHORTCUT_FLOW') && BW_CUSTOM_WP_LOGIN_SHORTCUT_FLOW;
    }
}

if (!function_exists('bw_custom_wp_login_get_current_request_url')) {
    function bw_custom_wp_login_get_current_request_url()
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
        return home_url($request_uri);
    }
}

if (!function_exists('bw_custom_wp_login_allow_default_urls')) {
    function bw_custom_wp_login_allow_default_urls()
    {
        return defined('BLACKWORK_ALLOW_DEFAULT_WP_LOGIN') && true === BLACKWORK_ALLOW_DEFAULT_WP_LOGIN;
    }
}

if (!function_exists('bw_custom_wp_login_hide_default_enabled')) {
    function bw_custom_wp_login_hide_default_enabled()
    {
        return 1 === (int) get_option('bw_custom_wp_login_hide_default', 0);
    }
}

if (!function_exists('bw_custom_wp_login_hide_default_active')) {
    function bw_custom_wp_login_hide_default_active()
    {
        if (bw_custom_wp_login_allow_default_urls()) {
            return false;
        }

        if (!bw_custom_wp_login_hide_default_enabled()) {
            return false;
        }

        return '' !== bw_custom_wp_login_get_slug();
    }
}

if (!function_exists('bw_custom_wp_login_build_custom_url')) {
    function bw_custom_wp_login_build_custom_url($query_args = [])
    {
        $slug = bw_custom_wp_login_get_slug();
        if ('' === $slug) {
            return home_url('/');
        }

        $url = home_url('/' . $slug . '/');
        if (!empty($query_args) && is_array($query_args)) {
            $url = add_query_arg($query_args, $url);
        }

        return $url;
    }
}

if (!function_exists('bw_custom_wp_login_is_custom_url')) {
    function bw_custom_wp_login_is_custom_url($url)
    {
        $url = (string) $url;
        if ('' === $url) {
            return false;
        }

        $custom_url = bw_custom_wp_login_build_custom_url();
        return '' !== $custom_url && 0 === strpos($url, $custom_url);
    }
}

if (!function_exists('bw_custom_wp_login_is_custom_postback')) {
    function bw_custom_wp_login_is_custom_postback()
    {
        $request_method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
        if ('POST' !== $request_method) {
            return false;
        }

        $referer = isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : '';
        if (bw_custom_wp_login_is_custom_url($referer)) {
            return true;
        }

        return bw_custom_wp_login_is_custom_url(bw_custom_wp_login_get_current_request_url());
    }
}

if (!function_exists('bw_custom_wp_login_should_rewrite_internal_urls')) {
    function bw_custom_wp_login_should_rewrite_internal_urls()
    {
        if (!bw_custom_wp_login_hide_default_active()) {
            return false;
        }

        return bw_custom_wp_login_is_shortcut_request() || bw_custom_wp_login_is_custom_postback();
    }
}

if (!function_exists('bw_custom_wp_login_extract_login_query_args')) {
    function bw_custom_wp_login_extract_login_query_args($path)
    {
        $path = (string) $path;
        if ('' === $path) {
            return [];
        }

        $parts = wp_parse_url($path);
        if (!is_array($parts)) {
            return [];
        }

        $path_part = isset($parts['path']) ? basename((string) $parts['path']) : '';
        if ('wp-login.php' !== $path_part) {
            return [];
        }

        $query_args = [];
        if (!empty($parts['query'])) {
            wp_parse_str($parts['query'], $query_args);
        }

        return is_array($query_args) ? $query_args : [];
    }
}

if (!function_exists('bw_custom_wp_login_filter_site_url')) {
    function bw_custom_wp_login_filter_site_url($url, $path, $scheme = null, $blog_id = null)
    {
        unset($scheme, $blog_id);

        if (!bw_custom_wp_login_should_rewrite_internal_urls()) {
            return $url;
        }

        $query_args = bw_custom_wp_login_extract_login_query_args($path);
        if (empty($query_args) && 'wp-login.php' !== basename((string) wp_parse_url((string) $path, PHP_URL_PATH))) {
            return $url;
        }

        return bw_custom_wp_login_build_custom_url($query_args);
    }
}

if (!function_exists('bw_custom_wp_login_filter_login_url')) {
    function bw_custom_wp_login_filter_login_url($login_url, $redirect, $force_reauth)
    {
        if (!bw_custom_wp_login_should_rewrite_internal_urls()) {
            return $login_url;
        }

        $query_args = [];
        if (!empty($redirect)) {
            $query_args['redirect_to'] = $redirect;
        }
        if ($force_reauth) {
            $query_args['reauth'] = '1';
        }

        return bw_custom_wp_login_build_custom_url($query_args);
    }
}

if (!function_exists('bw_custom_wp_login_filter_lostpassword_url')) {
    function bw_custom_wp_login_filter_lostpassword_url($lostpassword_url, $redirect)
    {
        if (!bw_custom_wp_login_should_rewrite_internal_urls()) {
            return $lostpassword_url;
        }

        $query_args = ['action' => 'lostpassword'];
        if (!empty($redirect)) {
            $query_args['redirect_to'] = $redirect;
        }

        return bw_custom_wp_login_build_custom_url($query_args);
    }
}

if (!function_exists('bw_custom_wp_login_filter_logout_url')) {
    function bw_custom_wp_login_filter_logout_url($logout_url, $redirect)
    {
        if (!bw_custom_wp_login_should_rewrite_internal_urls()) {
            return $logout_url;
        }

        $query_args = ['action' => 'logout'];
        if (!empty($redirect)) {
            $query_args['redirect_to'] = $redirect;
        }

        $nonce = wp_create_nonce('log-out');
        if (!empty($nonce)) {
            $query_args['_wpnonce'] = $nonce;
        }

        return bw_custom_wp_login_build_custom_url($query_args);
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

        if (!bw_custom_wp_login_is_shortcut_request()) {
            define('BW_CUSTOM_WP_LOGIN_SHORTCUT_FLOW', true);
        }

        require ABSPATH . 'wp-login.php';
        exit;
    }
}

if (!function_exists('bw_custom_wp_login_render_404')) {
    function bw_custom_wp_login_render_404()
    {
        global $wp_query;

        if ($wp_query instanceof WP_Query) {
            $wp_query->set_404();
        }

        status_header(404);
        nocache_headers();

        $template = get_404_template();
        if (!empty($template) && file_exists($template)) {
            include $template;
            exit;
        }

        wp_die(esc_html__('404 Not Found', 'bw'), esc_html__('404 Not Found', 'bw'), ['response' => 404]);
    }
}

if (!function_exists('bw_custom_wp_login_block_default_login')) {
    function bw_custom_wp_login_block_default_login()
    {
        if (!bw_custom_wp_login_hide_default_active() || is_user_logged_in()) {
            return;
        }

        if (bw_custom_wp_login_is_shortcut_request()) {
            return;
        }

        $script_name = isset($_SERVER['SCRIPT_NAME']) ? basename((string) $_SERVER['SCRIPT_NAME']) : '';
        if ('wp-login.php' !== $script_name) {
            return;
        }

        if (bw_custom_wp_login_is_custom_postback()) {
            return;
        }

        bw_custom_wp_login_render_404();
        exit;
    }
}

if (!function_exists('bw_custom_wp_login_block_default_admin')) {
    function bw_custom_wp_login_block_default_admin()
    {
        if (!bw_custom_wp_login_hide_default_active() || is_user_logged_in()) {
            return;
        }

        $script_name = isset($_SERVER['SCRIPT_NAME']) ? basename((string) $_SERVER['SCRIPT_NAME']) : '';
        if (in_array($script_name, ['admin-ajax.php', 'admin-post.php', 'async-upload.php'], true)) {
            return;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        if (false === strpos($request_uri, '/wp-admin')) {
            return;
        }

        bw_custom_wp_login_render_404();
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
add_action('init', 'bw_custom_wp_login_block_default_admin', 0);
add_filter('query_vars', 'bw_custom_wp_login_register_query_var');
add_filter('site_url', 'bw_custom_wp_login_filter_site_url', 10, 4);
add_filter('network_site_url', 'bw_custom_wp_login_filter_site_url', 10, 3);
add_filter('login_url', 'bw_custom_wp_login_filter_login_url', 10, 3);
add_filter('lostpassword_url', 'bw_custom_wp_login_filter_lostpassword_url', 10, 2);
add_filter('logout_url', 'bw_custom_wp_login_filter_logout_url', 10, 2);
add_action('login_init', 'bw_custom_wp_login_block_default_login', 0);
add_action('template_redirect', 'bw_custom_wp_login_template_redirect', 5);
add_action('admin_init', 'bw_custom_wp_login_maybe_flush_rewrite');
