<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BW_SYSTEM_STATUS_TRANSIENT_KEY')) {
    define('BW_SYSTEM_STATUS_TRANSIENT_KEY', 'bw_system_status_snapshot_v1');
}

if (!defined('BW_SYSTEM_STATUS_TRANSIENT_TTL')) {
    define('BW_SYSTEM_STATUS_TRANSIENT_TTL', 10 * MINUTE_IN_SECONDS);
}

if (!function_exists('bw_system_status_build_snapshot')) {
    function bw_system_status_build_snapshot($force_refresh = false)
    {
        $cached_snapshot = get_transient(BW_SYSTEM_STATUS_TRANSIENT_KEY);
        if (!$force_refresh && is_array($cached_snapshot)) {
            $cached_snapshot['cached'] = true;
            return $cached_snapshot;
        }

        $started_at = microtime(true);
        $checks = [
            'media' => bw_system_status_safe_check('bw_system_status_check_media', 'Media Library'),
            'database' => bw_system_status_safe_check('bw_system_status_check_database', 'Database'),
            'images' => bw_system_status_safe_check('bw_system_status_check_images', 'Registered Image Sizes'),
            'wordpress' => bw_system_status_safe_check('bw_system_status_check_wordpress', 'WordPress Environment'),
            'server' => bw_system_status_safe_check('bw_system_status_check_server', 'Server Limits'),
        ];

        $snapshot = [
            'ok' => true,
            'generated_at' => current_time('mysql'),
            'cached' => false,
            'ttl_seconds' => BW_SYSTEM_STATUS_TRANSIENT_TTL,
            'execution_time_ms' => (int) round((microtime(true) - $started_at) * 1000),
            'checks' => $checks,
        ];

        set_transient(BW_SYSTEM_STATUS_TRANSIENT_KEY, $snapshot, BW_SYSTEM_STATUS_TRANSIENT_TTL);

        return $snapshot;
    }
}

if (!function_exists('bw_system_status_ajax_run_check')) {
    function bw_system_status_ajax_run_check()
    {
        if (!current_user_can(BW_SYSTEM_STATUS_CAPABILITY)) {
            wp_send_json_error([
                'message' => __('You are not allowed to run system checks.', 'bw'),
            ], 403);
        }

        check_ajax_referer('bw_system_status_run_check', 'nonce');

        $force_refresh = isset($_POST['force_refresh']) && '1' === sanitize_text_field(wp_unslash($_POST['force_refresh']));

        $snapshot = bw_system_status_build_snapshot($force_refresh);
        wp_send_json_success($snapshot);
    }
}
add_action('wp_ajax_bw_system_status_run_check', 'bw_system_status_ajax_run_check');

if (!function_exists('bw_system_status_safe_check')) {
    function bw_system_status_safe_check($callback, $label)
    {
        try {
            if (!is_callable($callback)) {
                return [
                    'status' => 'error',
                    'summary' => sprintf(
                        /* translators: %s: check label */
                        __('%s check is unavailable.', 'bw'),
                        $label
                    ),
                    'metrics' => [],
                    'warnings' => [__('Check callback is not callable.', 'bw')],
                ];
            }

            $result = call_user_func($callback);
            if (!is_array($result)) {
                return [
                    'status' => 'error',
                    'summary' => sprintf(
                        /* translators: %s: check label */
                        __('%s check returned an invalid payload.', 'bw'),
                        $label
                    ),
                    'metrics' => [],
                    'warnings' => [__('Invalid check payload.', 'bw')],
                ];
            }

            if (!isset($result['status'])) {
                $result['status'] = 'error';
            }
            if (!isset($result['summary'])) {
                $result['summary'] = sprintf(
                    /* translators: %s: check label */
                    __('%s check completed.', 'bw'),
                    $label
                );
            }
            if (!isset($result['metrics']) || !is_array($result['metrics'])) {
                $result['metrics'] = [];
            }
            if (!isset($result['warnings']) || !is_array($result['warnings'])) {
                $result['warnings'] = [];
            }

            return $result;
        } catch (Throwable $throwable) {
            return [
                'status' => 'error',
                'summary' => sprintf(
                    /* translators: %s: check label */
                    __('%s check failed.', 'bw'),
                    $label
                ),
                'metrics' => [],
                'warnings' => [__('Check failed gracefully. Review logs for details.', 'bw')],
            ];
        }
    }
}
