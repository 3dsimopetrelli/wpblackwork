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

        $media_check = bw_system_status_check_media();
        $database_check = bw_system_status_check_database();
        $images_check = bw_system_status_check_images();

        $snapshot = [
            'ok' => true,
            'generated_at' => current_time('mysql'),
            'cached' => false,
            'ttl_seconds' => BW_SYSTEM_STATUS_TRANSIENT_TTL,
            'checks' => [
                'media' => $media_check,
                'database' => $database_check,
                'images' => $images_check,
            ],
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
