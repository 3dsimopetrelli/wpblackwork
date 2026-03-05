<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_system_status_ini_to_bytes')) {
    function bw_system_status_ini_to_bytes($value)
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        $value = trim((string) $value);
        if ('' === $value) {
            return 0;
        }

        $last = strtolower($value[strlen($value) - 1]);
        $number = (float) $value;

        switch ($last) {
            case 'g':
                $number *= 1024;
                // no break
            case 'm':
                $number *= 1024;
                // no break
            case 'k':
                $number *= 1024;
        }

        return (int) round($number);
    }
}

if (!function_exists('bw_system_status_check_server')) {
    function bw_system_status_check_server()
    {
        $warnings = [];

        $upload_max_filesize_raw = (string) ini_get('upload_max_filesize');
        $post_max_size_raw = (string) ini_get('post_max_size');
        $max_execution_time = (int) ini_get('max_execution_time');
        $memory_limit_raw = (string) ini_get('memory_limit');

        $upload_max_filesize = bw_system_status_ini_to_bytes($upload_max_filesize_raw);
        $post_max_size = bw_system_status_ini_to_bytes($post_max_size_raw);
        $memory_limit = bw_system_status_ini_to_bytes($memory_limit_raw);

        $status = 'ok';

        if ($upload_max_filesize > 0 && $upload_max_filesize < (32 * 1024 * 1024)) {
            $status = 'warn';
            $warnings[] = __('upload_max_filesize is below 32MB.', 'bw');
        }

        if ($post_max_size > 0 && $post_max_size < (32 * 1024 * 1024)) {
            $status = 'warn';
            $warnings[] = __('post_max_size is below 32MB.', 'bw');
        }

        if ($max_execution_time > 0 && $max_execution_time < 60) {
            $status = 'warn';
            $warnings[] = __('max_execution_time is below 60 seconds.', 'bw');
        }

        if ($memory_limit > 0 && $memory_limit < (128 * 1024 * 1024)) {
            $status = 'warn';
            $warnings[] = __('PHP memory_limit is below 128MB.', 'bw');
        }

        return [
            'status' => $status,
            'summary' => sprintf(
                /* translators: 1: upload max file size, 2: memory limit */
                __('Upload limit %1$s, memory %2$s', 'bw'),
                $upload_max_filesize_raw,
                $memory_limit_raw
            ),
            'metrics' => [
                'upload_max_filesize' => $upload_max_filesize_raw,
                'upload_max_filesize_bytes' => $upload_max_filesize,
                'post_max_size' => $post_max_size_raw,
                'post_max_size_bytes' => $post_max_size,
                'max_execution_time' => $max_execution_time,
                'memory_limit' => $memory_limit_raw,
                'memory_limit_bytes' => $memory_limit,
            ],
            'warnings' => $warnings,
        ];
    }
}
