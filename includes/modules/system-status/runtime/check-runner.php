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
        $default_full_checks = ['media', 'images', 'database', 'wordpress', 'limits'];

        if (is_string($requested_checks)) {
            $requested_checks = array_filter(array_map('trim', explode(',', $requested_checks)));
        }
        if (!is_array($requested_checks)) {
            $requested_checks = [];
        }

        $requested_checks = array_values(array_intersect($available_check_keys, $requested_checks));
        if (empty($requested_checks)) {
            $requested_checks = $default_full_checks;
        }

        if (
            in_array('image_sizes_counts', $requested_checks, true)
            && !in_array('images', $requested_checks, true)
        ) {
            $requested_checks[] = 'images';
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
                $cached_snapshot['is_partial_refresh'] = isset($cached_snapshot['is_partial_refresh']) ? (bool) $cached_snapshot['is_partial_refresh'] : false;
                $cached_snapshot['refreshed_checks'] = isset($cached_snapshot['refreshed_checks']) && is_array($cached_snapshot['refreshed_checks']) ? array_values($cached_snapshot['refreshed_checks']) : [];
                $cached_snapshot['last_full_generated_at'] = isset($cached_snapshot['last_full_generated_at']) ? (string) $cached_snapshot['last_full_generated_at'] : '';
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

        if (
            isset($snapshot['checks']['image_sizes_counts']['metrics']['generated_counts'])
            && isset($snapshot['checks']['images'])
        ) {
            $generated_counts = $snapshot['checks']['image_sizes_counts']['metrics']['generated_counts'];

            if (!isset($snapshot['checks']['images']['metrics']) || !is_array($snapshot['checks']['images']['metrics'])) {
                $snapshot['checks']['images']['metrics'] = [];
            }
            $snapshot['checks']['images']['metrics']['generated_counts'] = $generated_counts;

            if (
                isset($snapshot['checks']['image_sizes_counts']['status'])
                && 'warn' === $snapshot['checks']['image_sizes_counts']['status']
                && isset($snapshot['checks']['images']['status'])
                && 'error' !== $snapshot['checks']['images']['status']
            ) {
                $snapshot['checks']['images']['status'] = 'warn';
            }

            if (
                isset($snapshot['checks']['image_sizes_counts']['warnings'])
                && is_array($snapshot['checks']['image_sizes_counts']['warnings'])
            ) {
                if (!isset($snapshot['checks']['images']['warnings']) || !is_array($snapshot['checks']['images']['warnings'])) {
                    $snapshot['checks']['images']['warnings'] = [];
                }
                $snapshot['checks']['images']['warnings'] = array_values(array_unique(array_merge(
                    $snapshot['checks']['images']['warnings'],
                    $snapshot['checks']['image_sizes_counts']['warnings']
                )));
            }
        }

        $snapshot['ok'] = true;
        foreach ($snapshot['checks'] as $check_data) {
            if (isset($check_data['status']) && 'error' === $check_data['status']) {
                $snapshot['ok'] = false;
                break;
            }
        }

        $is_full_refresh = !array_diff($default_full_checks, $requested_checks) && !array_diff($requested_checks, $default_full_checks);
        $generated_at = current_time('mysql');

        if ($is_full_refresh) {
            $snapshot['last_full_generated_at'] = $generated_at;
        } elseif (!isset($snapshot['last_full_generated_at'])) {
            $snapshot['last_full_generated_at'] = '';
        }

        $snapshot['generated_at'] = $generated_at;
        $snapshot['cached'] = false;
        $snapshot['is_partial_refresh'] = !$is_full_refresh;
        $snapshot['refreshed_checks'] = $requested_checks;
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
            'limits' => [
                'callback' => 'bw_system_status_check_server',
                'label' => 'PHP Limits',
            ],
            'image_sizes_counts' => [
                'callback' => 'bw_system_status_check_image_sizes_counts',
                'label' => 'Image Sizes Generated Counts',
            ],
        ];
    }
}

if (!function_exists('bw_system_status_map_scope_to_checks')) {
    function bw_system_status_map_scope_to_checks($scope)
    {
        $scope = sanitize_key((string) $scope);

        if ('' === $scope || 'all' === $scope) {
            return [];
        }

        $allowed_scopes = ['media', 'images', 'database', 'wordpress', 'limits', 'image_sizes_counts'];
        if (!in_array($scope, $allowed_scopes, true)) {
            return [];
        }

        return [$scope];
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
        $requested_checks = bw_system_status_map_scope_to_checks(
            isset($_POST['check_scope']) ? wp_unslash($_POST['check_scope']) : 'all'
        );

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
