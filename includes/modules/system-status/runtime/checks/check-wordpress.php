<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_system_status_check_wordpress')) {
    function bw_system_status_check_wordpress()
    {
        $warnings = [];

        $wp_version = get_bloginfo('version');
        $woocommerce_version = defined('WC_VERSION') ? WC_VERSION : '';
        $php_version = PHP_VERSION;
        $php_memory_limit = ini_get('memory_limit');
        $wp_debug_enabled = (bool) (defined('WP_DEBUG') && WP_DEBUG);
        $disallow_file_edit = (bool) (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT);

        $status = 'ok';

        if (version_compare($php_version, '8.0.0', '<')) {
            $status = 'warn';
            $warnings[] = __('PHP version is below 8.0. Consider upgrading for better stability and security.', 'bw');
        }

        if ($wp_debug_enabled) {
            $status = 'warn';
            $warnings[] = __('WP_DEBUG is enabled. This is not recommended on production.', 'bw');
        }

        if (!$disallow_file_edit) {
            $status = 'warn';
            $warnings[] = __('DISALLOW_FILE_EDIT is disabled. Enabling it improves admin hardening.', 'bw');
        }

        return [
            'status' => $status,
            'summary' => sprintf(
                /* translators: 1: WP version, 2: PHP version */
                __('WordPress %1$s on PHP %2$s', 'bw'),
                $wp_version,
                $php_version
            ),
            'metrics' => [
                'wordpress_version' => $wp_version,
                'woocommerce_version' => '' !== $woocommerce_version ? $woocommerce_version : __('Not detected', 'bw'),
                'php_version' => $php_version,
                'php_memory_limit' => $php_memory_limit,
                'wp_debug' => $wp_debug_enabled,
                'disallow_file_edit' => $disallow_file_edit,
            ],
            'warnings' => $warnings,
        ];
    }
}
