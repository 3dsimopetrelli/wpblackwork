<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_system_status_format_bytes')) {
    function bw_system_status_format_bytes($bytes)
    {
        $bytes = (float) $bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        if ($bytes <= 0) {
            return '0 B';
        }

        $power = (int) floor(log($bytes, 1024));
        $power = max(0, min($power, count($units) - 1));
        $value = $bytes / (1024 ** $power);

        return number_format_i18n($value, $power >= 2 ? 2 : 0) . ' ' . $units[$power];
    }
}

if (!function_exists('bw_system_status_map_media_bucket')) {
    function bw_system_status_map_media_bucket($mime_type)
    {
        if ('image/jpeg' === $mime_type || 'image/jpg' === $mime_type) {
            return 'jpeg';
        }

        if ('image/png' === $mime_type) {
            return 'png';
        }

        if ('image/svg+xml' === $mime_type) {
            return 'svg';
        }

        if ('image/webp' === $mime_type) {
            return 'webp';
        }

        if (0 === strpos((string) $mime_type, 'video/')) {
            return 'video';
        }

        return 'other';
    }
}

if (!function_exists('bw_system_status_check_media')) {
    function bw_system_status_check_media()
    {
        global $wpdb;

        $limit = 3000;
        $top_n = 5;
        $posts_table = $wpdb->posts;
        $postmeta_table = $wpdb->postmeta;

        $total_attachments = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$posts_table} WHERE post_type = 'attachment' AND post_status = 'inherit'"
        );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID, p.post_mime_type, pm.meta_value AS attached_file
                 FROM {$posts_table} p
                 LEFT JOIN {$postmeta_table} pm
                    ON pm.post_id = p.ID AND pm.meta_key = %s
                 WHERE p.post_type = 'attachment' AND p.post_status = 'inherit'
                 ORDER BY p.ID ASC
                 LIMIT %d",
                '_wp_attached_file',
                $limit
            ),
            ARRAY_A
        );

        $uploads = wp_upload_dir();
        $basedir = isset($uploads['basedir']) ? (string) $uploads['basedir'] : '';
        $total_bytes = 0;
        $missing_files = 0;
        $unknown_type_bytes = 0;
        $largest_file = null;
        $top_largest_files = [];

        $bytes_by_type = [
            'jpeg' => 0,
            'png' => 0,
            'svg' => 0,
            'video' => 0,
            'webp' => 0,
        ];

        foreach ($rows as $row) {
            $relative_file = isset($row['attached_file']) ? (string) $row['attached_file'] : '';
            if ('' === $relative_file || '' === $basedir) {
                $missing_files++;
                continue;
            }

            $absolute_file = trailingslashit($basedir) . ltrim($relative_file, '/');
            if (!is_file($absolute_file) || !is_readable($absolute_file)) {
                $missing_files++;
                continue;
            }

            $size = (int) @filesize($absolute_file); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            if ($size <= 0) {
                continue;
            }

            $total_bytes += $size;
            $bucket = bw_system_status_map_media_bucket(isset($row['post_mime_type']) ? (string) $row['post_mime_type'] : '');
            if (isset($bytes_by_type[$bucket])) {
                $bytes_by_type[$bucket] += $size;
            } else {
                $unknown_type_bytes += $size;
            }

            $current_item = [
                'id' => isset($row['ID']) ? (int) $row['ID'] : 0,
                'file' => $relative_file,
                'mime_type' => isset($row['post_mime_type']) ? (string) $row['post_mime_type'] : '',
                'size_bytes' => $size,
                'size_human' => bw_system_status_format_bytes($size),
            ];

            if (null === $largest_file || $size > (int) $largest_file['size_bytes']) {
                $largest_file = $current_item;
            }

            $top_largest_files[] = $current_item;
        }

        usort(
            $top_largest_files,
            static function ($left, $right) {
                return ((int) $right['size_bytes']) <=> ((int) $left['size_bytes']);
            }
        );
        $top_largest_files = array_slice($top_largest_files, 0, $top_n);

        $status = 'ok';
        $warnings = [];

        if ($total_attachments > $limit) {
            $status = 'warn';
            $warnings[] = sprintf(
                /* translators: 1: total attachments, 2: analyzed attachments */
                __('Partial scan: analyzed %2$d of %1$d attachments.', 'bw'),
                $total_attachments,
                count($rows)
            );
        }

        if ($missing_files > 0) {
            $status = 'warn';
            $warnings[] = sprintf(
                /* translators: %d: missing files count */
                __('%d attachments have missing/unreadable files.', 'bw'),
                $missing_files
            );
        }

        $bytes_by_type['other'] = $unknown_type_bytes;
        $type_distribution = [];
        $type_breakdown = [];
        foreach ($bytes_by_type as $type => $bytes) {
            $percent = $total_bytes > 0 ? round((((float) $bytes / (float) $total_bytes) * 100), 2) : 0;
            $type_distribution[$type] = $percent;
            $type_breakdown[$type] = [
                'bytes' => (int) $bytes,
                'bytes_human' => bw_system_status_format_bytes((int) $bytes),
                'percent' => $percent,
            ];
        }

        return [
            'status' => $status,
            'summary' => sprintf(
                /* translators: 1: attachment count, 2: total size */
                __('%1$s attachments, %2$s total', 'bw'),
                number_format_i18n($total_attachments),
                bw_system_status_format_bytes($total_bytes)
            ),
            'total_files' => $total_attachments,
            'total_bytes' => $total_bytes,
            'by_type' => $type_breakdown,
            'largest_file' => $largest_file,
            'top_largest_files' => $top_largest_files,
            'metrics' => [
                'total_files' => $total_attachments,
                'attachments_total' => $total_attachments,
                'attachments_analyzed' => count($rows),
                'total_bytes' => $total_bytes,
                'total_bytes_human' => bw_system_status_format_bytes($total_bytes),
                'by_type' => $type_breakdown,
                'bytes_by_type' => $type_breakdown,
                'type_distribution_percent' => $type_distribution,
                'largest_file' => $largest_file,
                'top_largest_files' => $top_largest_files,
            ],
            'warnings' => $warnings,
        ];
    }
}
