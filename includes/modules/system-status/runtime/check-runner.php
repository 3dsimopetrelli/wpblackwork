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
    function bw_system_status_build_snapshot($force_refresh = false, $requested_checks = [])
    {
        $registry = bw_system_status_get_check_registry();
        $available_check_keys = array_keys($registry);

        if (is_string($requested_checks)) {
            $requested_checks = array_filter(array_map('trim', explode(',', $requested_checks)));
        }
        if (!is_array($requested_checks)) {
            $requested_checks = [];
        }

        $requested_checks = array_values(array_intersect($available_check_keys, $requested_checks));
        if (empty($requested_checks)) {
            $requested_checks = $available_check_keys;
        }

        $cached_snapshot = get_transient(BW_SYSTEM_STATUS_TRANSIENT_KEY);
        if (
            !$force_refresh
            && is_array($cached_snapshot)
            && isset($cached_snapshot['checks'])
            && is_array($cached_snapshot['checks'])
        ) {
            $all_requested_cached = true;
            foreach ($requested_checks as $check_key) {
                if (!isset($cached_snapshot['checks'][$check_key])) {
                    $all_requested_cached = false;
                    break;
                }
            }

            if ($all_requested_cached) {
                $cached_snapshot['ttl_seconds'] = BW_SYSTEM_STATUS_TRANSIENT_TTL;
                $cached_snapshot['execution_time_ms'] = isset($cached_snapshot['execution_time_ms']) ? (int) $cached_snapshot['execution_time_ms'] : 0;
                $cached_snapshot['cached'] = true;
                return $cached_snapshot;
            }
        }

        $snapshot = is_array($cached_snapshot) ? $cached_snapshot : [];
        if (!isset($snapshot['checks']) || !is_array($snapshot['checks'])) {
            $snapshot['checks'] = [];
        }

        $started_at = microtime(true);
        foreach ($requested_checks as $check_key) {
            if (!isset($registry[$check_key])) {
                continue;
            }

            $snapshot['checks'][$check_key] = bw_system_status_safe_check(
                $registry[$check_key]['callback'],
                $registry[$check_key]['label']
            );
        }

        $snapshot['ok'] = true;
        foreach ($snapshot['checks'] as $check_data) {
            if (isset($check_data['status']) && 'error' === $check_data['status']) {
                $snapshot['ok'] = false;
                break;
            }
        }

        $snapshot['generated_at'] = current_time('mysql');
        $snapshot['cached'] = false;
        $snapshot['ttl_seconds'] = BW_SYSTEM_STATUS_TRANSIENT_TTL;
        $snapshot['execution_time_ms'] = (int) round((microtime(true) - $started_at) * 1000);

        set_transient(BW_SYSTEM_STATUS_TRANSIENT_KEY, $snapshot, BW_SYSTEM_STATUS_TRANSIENT_TTL);

        return $snapshot;
    }
}

if (!function_exists('bw_system_status_get_check_registry')) {
    function bw_system_status_get_check_registry()
    {
        return [
            'media' => [
                'callback' => 'bw_system_status_check_media',
                'label' => 'Media Library',
            ],
            'images' => [
                'callback' => 'bw_system_status_check_images',
                'label' => 'Registered Image Sizes',
            ],
            'database' => [
                'callback' => 'bw_system_status_check_database',
                'label' => 'Database',
            ],
            'wordpress' => [
                'callback' => 'bw_system_status_check_wordpress',
                'label' => 'WordPress Environment',
            ],
            'server' => [
                'callback' => 'bw_system_status_check_server',
                'label' => 'PHP Limits',
            ],
        ];
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
        $requested_checks = [];
        if (isset($_POST['checks'])) {
            $raw_checks = wp_unslash($_POST['checks']);
            if (is_array($raw_checks)) {
                $requested_checks = array_map('sanitize_key', $raw_checks);
            } else {
                $requested_checks = array_map('sanitize_key', explode(',', sanitize_text_field($raw_checks)));
            }
        }

        $snapshot = bw_system_status_build_snapshot($force_refresh, $requested_checks);
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
