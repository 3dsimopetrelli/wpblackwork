<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_system_status_check_database')) {
    function bw_system_status_check_database()
    {
        global $wpdb;

        $top_n = 10;
        $autoload_warning_threshold = 3 * 1024 * 1024;
        $table_rows = [];
        $warnings = [];
        $source = 'information_schema';
        $total_db_bytes = 0;
        $table_count = 0;

        if (defined('DB_NAME') && DB_NAME) {
            $totals_row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT
                        COUNT(*) AS table_count,
                        COALESCE(SUM(data_length + index_length), 0) AS total_bytes
                     FROM information_schema.TABLES
                     WHERE table_schema = %s",
                    DB_NAME
                ),
                ARRAY_A
            );

            if (is_array($totals_row)) {
                $table_count = isset($totals_row['table_count']) ? (int) $totals_row['table_count'] : 0;
                $total_db_bytes = isset($totals_row['total_bytes']) ? (int) $totals_row['total_bytes'] : 0;
            }

            $table_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT table_name, data_length, index_length
                     FROM information_schema.TABLES
                     WHERE table_schema = %s
                     ORDER BY (data_length + index_length) DESC
                     LIMIT %d",
                    DB_NAME,
                    $top_n
                ),
                ARRAY_A
            );
        }

        if (empty($table_rows)) {
            $source = 'show_table_status';
            $status_rows = $wpdb->get_results('SHOW TABLE STATUS', ARRAY_A);

            if (is_array($status_rows)) {
                foreach ($status_rows as $status_row) {
                    $data_length = isset($status_row['Data_length']) ? (int) $status_row['Data_length'] : 0;
                    $index_length = isset($status_row['Index_length']) ? (int) $status_row['Index_length'] : 0;
                    $size = $data_length + $index_length;
                    $table_count++;
                    $total_db_bytes += $size;

                    $table_rows[] = [
                        'table_name' => isset($status_row['Name']) ? $status_row['Name'] : '',
                        'data_length' => $data_length,
                        'index_length' => $index_length,
                    ];
                }

                usort(
                    $table_rows,
                    static function ($left, $right) {
                        $left_size = ((int) $left['data_length']) + ((int) $left['index_length']);
                        $right_size = ((int) $right['data_length']) + ((int) $right['index_length']);
                        return $right_size <=> $left_size;
                    }
                );
                $table_rows = array_slice($table_rows, 0, $top_n);
                $warnings[] = __('Using fallback DB size source (SHOW TABLE STATUS). Values are estimates.', 'bw');
            }
        }

        if (empty($table_rows)) {
            return [
                'status' => 'error',
                'summary' => __('Unable to read DB table sizes on this host.', 'bw'),
                'metrics' => [
                    'source' => $source,
                    'total_bytes' => 0,
                    'total_bytes_human' => bw_system_status_format_bytes(0),
                    'largest_tables' => [],
                ],
                'warnings' => [
                    __('Database size is unavailable due to permissions or host restrictions.', 'bw'),
                ],
            ];
        }

        $largest_tables = [];
        $largest_table = null;

        foreach ($table_rows as $row) {
            $size = ((int) $row['data_length']) + ((int) $row['index_length']);
            $table_item = [
                'name' => (string) $row['table_name'],
                'size_bytes' => $size,
                'size_human' => bw_system_status_format_bytes($size),
            ];
            $largest_tables[] = $table_item;
            if (null === $largest_table) {
                $largest_table = $table_item;
            }
        }

        $autoload_size_bytes = (int) $wpdb->get_var(
            "SELECT COALESCE(SUM(LENGTH(option_value)), 0) FROM {$wpdb->options} WHERE autoload = 'yes'"
        );
        $autoload_warning_exceeded = $autoload_size_bytes > $autoload_warning_threshold;

        $status = 'ok';
        if ('show_table_status' === $source) {
            $status = 'warn';
        }
        if ($autoload_warning_exceeded) {
            $status = 'warn';
            $warnings[] = sprintf(
                /* translators: 1: autoload size, 2: warning threshold */
                __('Autoload options are large (%1$s > %2$s threshold).', 'bw'),
                bw_system_status_format_bytes($autoload_size_bytes),
                bw_system_status_format_bytes($autoload_warning_threshold)
            );
        }

        return [
            'status' => $status,
            'summary' => sprintf(
                /* translators: 1: total DB size, 2: number of tables */
                __('Total DB estimate: %1$s across %2$d tables', 'bw'),
                bw_system_status_format_bytes($total_db_bytes),
                $table_count
            ),
            'metrics' => [
                'source' => $source,
                'total_db_size_bytes' => $total_db_bytes,
                'total_db_size_human' => bw_system_status_format_bytes($total_db_bytes),
                'total_table_count' => $table_count,
                'largest_table' => $largest_table,
                'top_largest_tables' => $largest_tables,
                'largest_tables' => $largest_tables,
                'autoload' => [
                    'total_size_bytes' => $autoload_size_bytes,
                    'total_size_human' => bw_system_status_format_bytes($autoload_size_bytes),
                    'warning_threshold_bytes' => $autoload_warning_threshold,
                    'warning_threshold_exceeded' => $autoload_warning_exceeded,
                ],
            ],
            'warnings' => $warnings,
        ];
    }
}
