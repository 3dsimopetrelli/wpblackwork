<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_system_status_check_database')) {
    function bw_system_status_check_database()
    {
        global $wpdb;

        $top_n = 8;
        $table_rows = [];
        $warnings = [];
        $source = 'information_schema';

        if (defined('DB_NAME') && DB_NAME) {
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
            $like = $wpdb->esc_like($wpdb->prefix) . '%';
            $status_rows = $wpdb->get_results(
                $wpdb->prepare('SHOW TABLE STATUS LIKE %s', $like),
                ARRAY_A
            );

            if (is_array($status_rows)) {
                foreach ($status_rows as $status_row) {
                    $table_rows[] = [
                        'table_name' => isset($status_row['Name']) ? $status_row['Name'] : '',
                        'data_length' => isset($status_row['Data_length']) ? (int) $status_row['Data_length'] : 0,
                        'index_length' => isset($status_row['Index_length']) ? (int) $status_row['Index_length'] : 0,
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
        $top_total = 0;

        foreach ($table_rows as $row) {
            $size = ((int) $row['data_length']) + ((int) $row['index_length']);
            $top_total += $size;
            $largest_tables[] = [
                'name' => (string) $row['table_name'],
                'size_bytes' => $size,
                'size_human' => bw_system_status_format_bytes($size),
            ];
        }

        $status = 'ok';
        if ('show_table_status' === $source) {
            $status = 'warn';
        }

        return [
            'status' => $status,
            'summary' => sprintf(
                /* translators: %1$s: size, %2$d: number of tables */
                __('Top %2$d tables estimate: %1$s', 'bw'),
                bw_system_status_format_bytes($top_total),
                count($largest_tables)
            ),
            'metrics' => [
                'source' => $source,
                'total_bytes' => $top_total,
                'total_bytes_human' => bw_system_status_format_bytes($top_total),
                'largest_tables' => $largest_tables,
            ],
            'warnings' => $warnings,
        ];
    }
}
