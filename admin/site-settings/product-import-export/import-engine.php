<?php
/**
 * Blackwork Site Settings — CSV import engine (parse, map, create/update products & variations).
 *
 * Extracted verbatim from class-blackwork-site-settings.php (Phase 3a, BW-TASK-20260629). Function bodies are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gestisce il caricamento del CSV e salva lo stato temporaneo.
 *
 * @return array|WP_Error
 */
function bw_import_handle_upload_request()
{
    if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
        return new WP_Error('bw_import_permission', __('You do not have permission to upload files.', 'bw'));
    }

    if (!isset($_POST['bw_import_upload_nonce']) || !wp_verify_nonce($_POST['bw_import_upload_nonce'], 'bw_import_upload')) {
        return new WP_Error('bw_import_nonce', __('Invalid nonce. Please try again.', 'bw'));
    }

    if (empty($_FILES['bw_import_csv']['name'])) {
        return new WP_Error('bw_import_file', __('Please select a CSV file to upload.', 'bw'));
    }

    add_filter('upload_dir', 'bw_import_upload_dir');
    $upload = wp_handle_upload(
        $_FILES['bw_import_csv'],
        [
            'test_form' => false,
            'mimes' => ['csv' => 'text/csv', 'txt' => 'text/plain'],
        ]
    );
    remove_filter('upload_dir', 'bw_import_upload_dir');

    if (isset($upload['error'])) {
        return new WP_Error('bw_import_upload_error', $upload['error']);
    }

    $parsed = bw_import_parse_csv_file($upload['file'], 5);
    if (is_wp_error($parsed)) {
        return $parsed;
    }

    $active_run_id = bw_import_get_active_run_id();
    if ($active_run_id !== '') {
        bw_import_release_lock($active_run_id, get_current_user_id(), true);
    }

    $summary = bw_import_calculate_header_stats($parsed['headers']);
    $default_skip_images = bw_import_default_skip_images_for_uploaded_csv($parsed['headers'], $parsed['rows']);

    $update_existing = !empty($_POST['bw_import_update_existing']);
    $run_id = bw_import_generate_run_id();
    $chunk_size = bw_import_chunk_size();
    $file_fingerprint = bw_import_file_fingerprint($upload['file']);
    $now = time();

    $state = [
        'run_id' => $run_id,
        'status' => 'queued',
        'owner_user_id' => get_current_user_id(),
        'file_path' => $upload['file'],
        'file_fingerprint' => $file_fingerprint,
        'file_url' => $upload['url'],
        'headers' => $parsed['headers'],
        'sample' => $parsed['rows'],
        'update_existing' => $update_existing,
        'skip_images' => $default_skip_images,
        'row_cursor' => 0,
        'in_progress' => false,
        'mapping' => [],
        'totals' => [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'failed' => 0,
        ],
        'counters' => [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
        ],
        'detail_totals' => [
            'products_created' => 0,
            'products_updated' => 0,
            'variations_created' => 0,
            'variations_updated' => 0,
            'variations_skipped' => 0,
            'variations_failed' => 0,
        ],
        'row_outcomes' => [],
        'row_outcome_order' => [],
        'processed_row_keys' => [],
        'mapping_snapshot' => [],
        'options_snapshot' => [
            'update_existing' => $update_existing,
            'skip_images' => $default_skip_images,
            'chunk_size' => $chunk_size,
        ],
        'started_at' => $now,
        'updated_at' => $now,
        'completed_at' => 0,
        'lock' => [],
        'last_errors' => [],
        'last_warnings' => [],
        'upload_summary' => [
            'file_name' => basename($upload['file']),
            'total_fields' => $summary['total'],
            'loaded_fields' => $summary['loaded'],
            'missing' => $summary['missing'],
            'replaced' => $summary['replaced'],
            'replaced_count' => $summary['replaced_count'],
            'duplicates' => $summary['duplicates'],
            'duplicate_count' => $summary['duplicate_count'],
        ],
    ];

    bw_import_save_run_state($state);
    bw_import_set_active_run_id($run_id);
    bw_import_save_state($state);

    return $state;
}

function bw_import_default_skip_images_for_uploaded_csv($headers, $rows)
{
    $headers = array_values((array) $headers);
    $header_index = array_flip($headers);

    $digital_header_markers = [
        'meta:_digital_source',
        'meta:_digital_publisher',
        'meta:_digital_year',
        'meta:_digital_technique',
        'variation_name',
        'parent_sku',
        'default_variation',
    ];

    $digital_marker_count = 0;
    foreach ($digital_header_markers as $marker) {
        if (isset($header_index[$marker])) {
            $digital_marker_count++;
        }
    }

    $bw_product_type_index = isset($header_index['meta:_bw_product_type']) ? (int) $header_index['meta:_bw_product_type'] : -1;
    $row_type_index = isset($header_index['row_type']) ? (int) $header_index['row_type'] : -1;

    foreach ((array) $rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $row_type = $row_type_index >= 0 && isset($row[$row_type_index]) ? sanitize_key((string) $row[$row_type_index]) : '';
        if ($row_type !== '' && $row_type !== 'product') {
            continue;
        }

        $bw_product_type = $bw_product_type_index >= 0 && isset($row[$bw_product_type_index])
            ? sanitize_key((string) $row[$bw_product_type_index])
            : '';

        if ($bw_product_type === 'digital') {
            return false;
        }
    }

    if ($digital_marker_count >= 3) {
        return false;
    }

    return true;
}

/**
 * Gestisce l'esecuzione dell'import.
 *
 * @param array $state Stato corrente dell'upload.
 *
 * @return array|WP_Error
 */
function bw_import_handle_run_request($state)
{
    if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
        return new WP_Error('bw_import_permission', __('You do not have permission to run the import.', 'bw'));
    }

    if (function_exists('set_time_limit')) {
        @set_time_limit(300);
    }

    if (!isset($_POST['bw_import_run_nonce']) || !wp_verify_nonce($_POST['bw_import_run_nonce'], 'bw_import_run')) {
        return new WP_Error('bw_import_nonce', __('Invalid nonce. Please try again.', 'bw'));
    }

    $active_run_id = bw_import_get_active_run_id();
    $requested_run_id = isset($state['run_id']) ? sanitize_text_field((string) $state['run_id']) : '';
    $run_id = $active_run_id !== '' ? $active_run_id : $requested_run_id;

    if ($active_run_id !== '' && $requested_run_id !== '' && $active_run_id !== $requested_run_id) {
        return new WP_Error('bw_import_run_mismatch', __('An active import run is already authoritative. Refresh and continue the active run.', 'bw'));
    }

    $run_state = $run_id !== '' ? bw_import_get_run_state($run_id) : [];

    if (empty($run_state['file_path']) || empty($run_state['headers'])) {
        return new WP_Error('bw_import_missing_state', __('No CSV file is attached. Upload a file before running the import.', 'bw'));
    }

    if (empty($run_state['run_id'])) {
        $run_state['run_id'] = bw_import_generate_run_id();
    }
    $run_id = (string) $run_state['run_id'];
    $current_status = isset($run_state['status']) ? sanitize_key((string) $run_state['status']) : '';
    if ($current_status === 'completed') {
        return [
            'message' => __('This import run is already completed. Upload a new CSV to start another run.', 'bw'),
        ];
    }

    $is_first_step = empty($run_state['mapping_snapshot']);

    $lock_result = bw_import_acquire_lock($run_id, get_current_user_id());
    if (empty($lock_result['ok'])) {
        return new WP_Error('bw_import_lock_held', isset($lock_result['message']) ? $lock_result['message'] : __('An import run is currently locked by another operator.', 'bw'));
    }

    if (!empty($lock_result['warning'])) {
        $run_state['last_warnings'] = bw_import_merge_bounded_messages(
            isset($run_state['last_warnings']) ? (array) $run_state['last_warnings'] : [],
            [$lock_result['warning']]
        );
    }

    $run_state['owner_user_id'] = get_current_user_id();
    $run_state['status'] = 'running';
    $run_state['updated_at'] = time();
    $run_state['lock'] = bw_import_get_lock_payload();
    bw_import_save_run_state($run_state);
    bw_import_set_active_run_id($run_id);
    bw_import_save_state($run_state);

    bw_import_refresh_lock($run_id, get_current_user_id());

    $mapping = [];

    if ($is_first_step) {
        $raw_mapping = isset($_POST['bw_import_mapping']) ? (array) $_POST['bw_import_mapping'] : [];
        foreach ($run_state['headers'] as $header) {
            $value = isset($raw_mapping[$header]) ? sanitize_text_field(wp_unslash($raw_mapping[$header])) : 'ignore';
            if ('ignore' !== $value) {
                $mapping[$header] = $value;
            }
        }

        if (!bw_import_has_identifier($mapping)) {
            bw_import_release_lock($run_id, get_current_user_id());
            return new WP_Error('bw_import_missing_identifier', __('Please map SKU to proceed. Product ID may be mapped as secondary, but SKU is mandatory.', 'bw'));
        }

        $sku_validation = bw_import_validate_unique_skus($run_state['file_path'], $run_state['headers'], $mapping);
        if (is_wp_error($sku_validation)) {
            bw_import_release_lock($run_id, get_current_user_id());
            return $sku_validation;
        }

        $run_state['mapping'] = $mapping;
        $run_state['mapping_snapshot'] = $mapping;
        $run_state['row_cursor'] = 0;
        $run_state['in_progress'] = true;
        $run_state['status'] = 'running';
        $run_state['totals'] = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'failed' => 0,
        ];
        $run_state['counters'] = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];
        $run_state['detail_totals'] = [
            'products_created' => 0,
            'products_updated' => 0,
            'variations_created' => 0,
            'variations_updated' => 0,
            'variations_skipped' => 0,
            'variations_failed' => 0,
        ];
        $run_state['row_outcomes'] = [];
        $run_state['row_outcome_order'] = [];
        $run_state['processed_row_keys'] = [];
        $run_state['last_errors'] = [];
        $run_state['last_warnings'] = [];
        $run_state['skip_images'] = empty($_POST['bw_import_import_images']);
        $run_state['options_snapshot'] = [
            'update_existing' => !empty($run_state['update_existing']),
            'skip_images' => !empty($run_state['skip_images']),
            'chunk_size' => bw_import_chunk_size(),
        ];
    } else {
        $mapping = isset($run_state['mapping_snapshot']) && is_array($run_state['mapping_snapshot']) ? $run_state['mapping_snapshot'] : [];
        if (!bw_import_has_identifier($mapping)) {
            $run_state['status'] = 'failed';
            $run_state['updated_at'] = time();
            bw_import_save_run_state($run_state);
            bw_import_release_lock($run_id, get_current_user_id());
            return new WP_Error('bw_import_missing_identifier', __('Import state is missing SKU mapping. Re-upload the CSV and map SKU again.', 'bw'));
        }
    }

    $chunk_size = isset($run_state['options_snapshot']['chunk_size']) ? absint($run_state['options_snapshot']['chunk_size']) : bw_import_chunk_size();
    if ($chunk_size < 1) {
        $chunk_size = bw_import_chunk_size();
    }

    $parsed_chunk = bw_import_parse_csv_chunk($run_state['file_path'], (int) $run_state['row_cursor'], $chunk_size);
    if (is_wp_error($parsed_chunk)) {
        $run_state['status'] = 'failed';
        $run_state['last_errors'] = bw_import_merge_bounded_messages(
            isset($run_state['last_errors']) ? (array) $run_state['last_errors'] : [],
            [$parsed_chunk->get_error_message()]
        );
        $run_state['updated_at'] = time();
        bw_import_save_run_state($run_state);
        bw_import_save_state($run_state);
        bw_import_release_lock($run_id, get_current_user_id());
        return $parsed_chunk;
    }

    $update_existing = !empty($run_state['update_existing']);
    $checkpoint_every = (int) apply_filters('bw_import_checkpoint_every', 10);
    if ($checkpoint_every < 1) {
        $checkpoint_every = 1;
    }
    if ($checkpoint_every > $chunk_size) {
        $checkpoint_every = $chunk_size;
    }

    $options = [
        'skip_images' => !empty($run_state['skip_images']),
        'checkpoint_every' => $checkpoint_every,
        'checkpoint_callback' => static function ($absolute_row) use (&$run_state, $run_id) {
            $absolute_row = max(0, (int) $absolute_row);
            if ($absolute_row <= (int) $run_state['row_cursor']) {
                return;
            }

            $run_state['row_cursor'] = $absolute_row;
            $run_state['updated_at'] = time();
            $run_state['lock'] = bw_import_get_lock_payload();
            bw_import_save_run_state($run_state);
            bw_import_save_state($run_state);
            bw_import_refresh_lock($run_id, get_current_user_id());
        },
    ];
    $result = bw_import_process_rows(
        $parsed_chunk['headers'],
        $parsed_chunk['rows'],
        $mapping,
        $update_existing,
        (int) $run_state['row_cursor'],
        $options,
        $run_state
    );

    $run_state['row_cursor'] = (int) $parsed_chunk['next_row'];
    $run_state['last_errors'] = bw_import_merge_bounded_messages(
        isset($run_state['last_errors']) ? (array) $run_state['last_errors'] : [],
        isset($result['errors']) ? (array) $result['errors'] : []
    );
    $run_state['last_warnings'] = bw_import_merge_bounded_messages(
        isset($run_state['last_warnings']) ? (array) $run_state['last_warnings'] : [],
        isset($result['warnings']) ? (array) $result['warnings'] : []
    );
    $run_state['detail_totals'] = bw_import_merge_detail_totals(
        isset($run_state['detail_totals']) && is_array($run_state['detail_totals']) ? $run_state['detail_totals'] : [],
        isset($result['details']) && is_array($result['details']) ? $result['details'] : []
    );
    $run_state['updated_at'] = time();
    $run_state['lock'] = bw_import_get_lock_payload();

    if (empty($parsed_chunk['eof'])) {
        $run_state['status'] = 'running';
        bw_import_save_run_state($run_state);
        bw_import_save_state($run_state);
        bw_import_refresh_lock($run_id, get_current_user_id());
        $detail_message = '';
        if (!empty($result['details']) && is_array($result['details'])) {
            $detail_message = sprintf(
                __(' Products: %1$d created, %2$d updated. Variations: %3$d created, %4$d updated, %5$d skipped, %6$d failed.', 'bw'),
                (int) $result['details']['products_created'],
                (int) $result['details']['products_updated'],
                (int) $result['details']['variations_created'],
                (int) $result['details']['variations_updated'],
                (int) $result['details']['variations_skipped'],
                (int) $result['details']['variations_failed']
            );
        }
        return [
            'message' => sprintf(
                /* translators: 1: processed rows, 2: created count, 3: updated count, 4: skipped count */
                __('Chunk completed. Processed rows: %1$d — Created: %2$d, Updated: %3$d, Skipped: %4$d. Click continue to process the next chunk.', 'bw'),
                (int) $run_state['row_cursor'],
                (int) $run_state['totals']['created'],
                (int) $run_state['totals']['updated'],
                (int) $run_state['totals']['skipped']
            ) . $detail_message,
        ];
    }

    $run_state['status'] = 'completed';
    $run_state['in_progress'] = false;
    $run_state['completed_at'] = time();
    bw_import_save_run_state($run_state);

    $message = sprintf(
        /* translators: 1: created count, 2: updated count, 3: skipped count, 4: errors count */
        __('Import completed. Created: %1$d, Updated: %2$d, Skipped: %3$d, Errors: %4$d', 'bw'),
        (int) $run_state['totals']['created'],
        (int) $run_state['totals']['updated'],
        (int) $run_state['totals']['skipped'],
        (int) $run_state['totals']['errors']
    );

    if (!empty($run_state['detail_totals']) && is_array($run_state['detail_totals'])) {
        $message .= ' ' . sprintf(
            __('Products: %1$d created, %2$d updated. Variations: %3$d created, %4$d updated, %5$d skipped, %6$d failed.', 'bw'),
            (int) $run_state['detail_totals']['products_created'],
            (int) $run_state['detail_totals']['products_updated'],
            (int) $run_state['detail_totals']['variations_created'],
            (int) $run_state['detail_totals']['variations_updated'],
            (int) $run_state['detail_totals']['variations_skipped'],
            (int) $run_state['detail_totals']['variations_failed']
        );
    }

    if (!empty($run_state['last_errors'])) {
        $message .= ' — ' . implode(' | ', array_map('esc_html', (array) $run_state['last_errors']));
    }

    $warning_summary = bw_import_format_warning_summary(
        isset($run_state['last_warnings']) ? (array) $run_state['last_warnings'] : []
    );
    if ($warning_summary !== '') {
        $message .= ' — ' . $warning_summary;
    }

    bw_import_release_lock($run_id, get_current_user_id());
    bw_import_set_active_run_id('');
    bw_import_clear_state($run_id, true);

    return [
        'message' => $message,
    ];
}

function bw_import_handle_clear_request($state)
{
    if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
        return new WP_Error('bw_import_permission', __('You do not have permission to clear the import state.', 'bw'));
    }

    if (!isset($_POST['bw_import_clear_state_nonce']) || !wp_verify_nonce($_POST['bw_import_clear_state_nonce'], 'bw_import_clear_state')) {
        return new WP_Error('bw_import_nonce', __('Invalid nonce. Please try again.', 'bw'));
    }

    $active_run_id = bw_import_get_active_run_id();
    $state_run_id = isset($state['run_id']) ? sanitize_text_field((string) $state['run_id']) : '';
    $run_id = $active_run_id !== '' ? $active_run_id : $state_run_id;

    if ($run_id !== '') {
        bw_import_release_lock($run_id, get_current_user_id(), true);
    }

    bw_import_clear_state($run_id, false);

    return true;
}

function bw_import_handle_clear_lock_request()
{
    if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
        return new WP_Error('bw_import_permission', __('You do not have permission to clear the import lock.', 'bw'));
    }

    if (!isset($_POST['bw_import_clear_lock_nonce']) || !wp_verify_nonce($_POST['bw_import_clear_lock_nonce'], 'bw_import_clear_lock')) {
        return new WP_Error('bw_import_nonce', __('Invalid nonce. Please try again.', 'bw'));
    }

    $lock = bw_import_get_active_lock();
    if (empty($lock)) {
        return new WP_Error('bw_import_lock_missing', __('No active import lock was found.', 'bw'));
    }

    bw_import_clear_lock(true);

    return [
        'run_id' => isset($lock['run_id']) ? (string) $lock['run_id'] : '',
        'owner_user_id' => isset($lock['owner_user_id']) ? (int) $lock['owner_user_id'] : 0,
    ];
}

function bw_import_get_lock_payload()
{
    $lock = get_option(bw_import_lock_option_key(), []);
    return is_array($lock) ? $lock : [];
}

/**
 * Effettua il parse del CSV.
 *
 * @param string $file_path Percorso del file.
 * @param int    $max_rows  Numero massimo di righe da leggere (0 = tutte).
 *
 * @return array|WP_Error
 */
function bw_import_parse_csv_file($file_path, $max_rows = 0)
{
    if (!file_exists($file_path)) {
        return new WP_Error('bw_import_missing_file', __('The uploaded CSV file cannot be found.', 'bw'));
    }

    $handle = fopen($file_path, 'r');
    if (!$handle) {
        return new WP_Error('bw_import_open_error', __('Unable to open the CSV file.', 'bw'));
    }

    $headers = fgetcsv($handle);
    if (empty($headers)) {
        fclose($handle);
        return new WP_Error('bw_import_headers', __('The CSV file is missing a header row.', 'bw'));
    }

    $rows = [];
    $row_count = 0;
    while (($data = fgetcsv($handle)) !== false) {
        if (bw_import_is_empty_csv_row($data)) {
            continue;
        }

        $rows[] = $data;
        $row_count++;
        if ($max_rows > 0 && $row_count >= $max_rows) {
            break;
        }
    }

    fclose($handle);

    return [
        'headers' => $headers,
        'rows' => $rows,
    ];
}

/**
 * Open CSV file handle for importer chunk reads.
 *
 * @param string $file_path CSV file path.
 *
 * @return resource|WP_Error
 */
function bw_import_open_csv($file_path)
{
    if (!file_exists($file_path)) {
        return new WP_Error('bw_import_missing_file', __('The uploaded CSV file cannot be found.', 'bw'));
    }

    $handle = fopen($file_path, 'r');
    if (!$handle) {
        return new WP_Error('bw_import_open_error', __('Unable to open the CSV file.', 'bw'));
    }

    return $handle;
}

/**
 * Read CSV headers from an opened handle.
 *
 * @param resource $handle CSV handle.
 *
 * @return array|WP_Error
 */
function bw_import_read_headers($handle)
{
    $headers = fgetcsv($handle);
    if (empty($headers)) {
        return new WP_Error('bw_import_headers', __('The CSV file is missing a header row.', 'bw'));
    }

    return $headers;
}

/**
 * Read one CSV chunk from an opened handle.
 *
 * @param resource $handle    CSV handle.
 * @param int      $start_row Zero-based row offset excluding header.
 * @param int      $limit     Chunk size.
 *
 * @return array
 */
function bw_import_read_chunk($handle, $start_row, $limit)
{
    $start_row = max(0, (int) $start_row);
    $limit = max(1, (int) $limit);

    $current_row = 0;
    while ($current_row < $start_row && ($data = fgetcsv($handle)) !== false) {
        if (bw_import_is_empty_csv_row($data)) {
            continue;
        }
        $current_row++;
    }

    $rows = [];
    $read_count = 0;
    while ($read_count < $limit && ($data = fgetcsv($handle)) !== false) {
        if (bw_import_is_empty_csv_row($data)) {
            continue;
        }

        $rows[] = $data;
        $read_count++;
        $current_row++;
    }

    return [
        'rows' => $rows,
        'next_row' => $current_row,
        'eof' => feof($handle),
    ];
}

function bw_import_is_empty_csv_row($row)
{
    if (!is_array($row) || empty($row)) {
        return true;
    }

    foreach ($row as $value) {
        if (trim((string) $value) !== '') {
            return false;
        }
    }

    return true;
}

/**
 * Parse CSV in deterministic chunks without loading full file in memory.
 *
 * @param string $file_path  CSV file path.
 * @param int    $start_row  Zero-based row index in data rows (header excluded).
 * @param int    $limit      Max number of rows to return.
 *
 * @return array|WP_Error
 */
function bw_import_parse_csv_chunk($file_path, $start_row, $limit)
{
    $start_row = max(0, (int) $start_row);
    $limit = max(1, (int) $limit);

    $handle = bw_import_open_csv($file_path);
    if (is_wp_error($handle)) {
        return $handle;
    }

    $headers = bw_import_read_headers($handle);
    if (is_wp_error($headers)) {
        fclose($handle);
        return $headers;
    }

    $chunk = bw_import_read_chunk($handle, $start_row, $limit);
    fclose($handle);

    return [
        'headers' => $headers,
        'rows' => $chunk['rows'],
        'next_row' => $chunk['next_row'],
        'eof' => $chunk['eof'],
    ];
}

/**
 * Genera un riepilogo dei campi trovati nel CSV caricato.
 *
 * @param array $headers Elenco delle intestazioni.
 *
 * @return array
 */
function bw_import_calculate_header_stats($headers)
{
    $clean_headers = array_map('trim', (array) $headers);
    $total_fields = count($clean_headers);
    $loaded_headers = array_filter($clean_headers, static function ($header) {
        return '' !== $header;
    });
    $missing_headers = [];
    $replaced_headers = [];
    $duplicates = [];
    $header_positions = [];

    foreach ($clean_headers as $index => $header) {
        if ('' === $header) {
            $placeholder = sprintf(
                /* translators: %d: column index */
                __('Column %d (missing header name)', 'bw'),
                (int) $index + 1
            );
            $missing_headers[] = $placeholder;
            $replaced_headers[] = $placeholder;
            continue;
        }

        $normalized = strtolower($header);
        if (!isset($header_positions[$normalized])) {
            $header_positions[$normalized] = [];
        }

        $header_positions[$normalized][] = (int) $index + 1;
    }

    foreach ($header_positions as $header => $positions) {
        if (count($positions) > 1) {
            $duplicates[$header] = $positions;
        }
    }

    return [
        'total' => $total_fields,
        'loaded' => count($loaded_headers),
        'missing' => $missing_headers,
        'replaced' => $replaced_headers,
        'replaced_count' => count($replaced_headers),
        'duplicates' => $duplicates,
        'duplicate_count' => array_sum(array_map(static function ($positions) {
            return max(0, count($positions) - 1);
        }, $duplicates)),
    ];
}

/**
 * Restituisce le opzioni di mapping organizzate per gruppo.
 *
 * @return array
 */
function bw_import_get_mapping_options()
{
    $options = [
        __('Product Core', 'bw') => [
            'row_type' => __('Row Type (product/variation)', 'bw'),
            'parent_sku' => __('Parent SKU', 'bw'),
            'product_id' => __('Product ID', 'bw'),
            'sku' => __('Product SKU', 'bw'),
            'post_title' => __('Product Title (post_title)', 'bw'),
            'post_name' => __('Product Slug (post_name)', 'bw'),
            'post_status' => __('Product Status', 'bw'),
            'product_type' => __('Product Type', 'bw'),
            'default_variation' => __('Default Variation', 'bw'),
            'post_content' => __('Product Description (post_content)', 'bw'),
            'post_excerpt' => __('Product Short Description (post_excerpt)', 'bw'),
        ],
        __('Pricing', 'bw') => [
            'regular_price' => __('Regular Price', 'bw'),
            'sale_price' => __('Sale Price', 'bw'),
            'sale_price_dates_from' => __('Sale Start Date', 'bw'),
            'sale_price_dates_to' => __('Sale End Date', 'bw'),
        ],
        __('Inventory', 'bw') => [
            'stock_quantity' => __('Stock Quantity', 'bw'),
            'manage_stock' => __('Manage Stock (yes/no)', 'bw'),
            'stock_status' => __('Stock Status', 'bw'),
            'backorders' => __('Backorders', 'bw'),
            'sold_individually' => __('Sold Individually', 'bw'),
        ],
        __('Shipping', 'bw') => [
            'weight' => __('Weight', 'bw'),
            'length' => __('Length', 'bw'),
            'width' => __('Width', 'bw'),
            'height' => __('Height', 'bw'),
            'shipping_class' => __('Shipping Class', 'bw'),
        ],
        __('Tax', 'bw') => [
            'tax_status' => __('Tax Status', 'bw'),
            'tax_class' => __('Tax Class', 'bw'),
        ],
        __('Categories & Tags', 'bw') => [
            'categories' => __('Product Categories (comma separated)', 'bw'),
            'product_subcategories' => __('Product Subcategories (comma separated)', 'bw'),
            'tags' => __('Product Tags (comma separated)', 'bw'),
        ],
        __('Images', 'bw') => [
            'featured_image' => __('Product Image (featured image URL)', 'bw'),
            'product_gallery' => __('Product Gallery (comma-separated image URLs)', 'bw'),
            'gallery_images' => __('Product Gallery (legacy alias)', 'bw'),
        ],
        __('Links', 'bw') => [
            'upsells' => __('Upsells (comma-separated IDs or SKUs)', 'bw'),
            'cross_sells' => __('Cross-sells (comma-separated IDs or SKUs)', 'bw'),
        ],
        __('Variations', 'bw') => [
            'variation_name' => __('Variation Name', 'bw'),
            'variation_regular_price' => __('Variation Regular Price', 'bw'),
            'variation_sale_price' => __('Variation Sale Price', 'bw'),
            'variation_image' => __('Variation Image URL', 'bw'),
            'variation_enabled' => __('Variation Enabled (yes/no)', 'bw'),
            'variation_virtual' => __('Variation Virtual (yes/no)', 'bw'),
            'variation_downloadable' => __('Variation Downloadable (yes/no)', 'bw'),
            'variation_download_name' => __('Variation Download Name', 'bw'),
            'variation_download_url' => __('Variation Download URL', 'bw'),
            'variation_attributes_json' => __('Variation Attributes JSON', 'bw'),
        ],
    ];

    $attribute_options = bw_import_attribute_options();
    if (!empty($attribute_options)) {
        $options[__('Attributes', 'bw')] = $attribute_options;
    }

    $meta_fields = bw_import_detect_custom_meta_fields();

    $product_slider_meta = bw_import_product_slider_meta_options($meta_fields);
    if (!empty($product_slider_meta)) {
        $options[__('MetaFields', 'bw')] = $product_slider_meta;
    }

    if (!empty($meta_fields)) {
        $meta_fields = array_values(array_diff($meta_fields, ['_bw_slider_hover_image']));
        if (!empty($meta_fields)) {
            $meta_group = [];
            foreach ($meta_fields as $meta_key) {
                $meta_group['meta:' . $meta_key] = sprintf(__('Meta: %1$s (%2$s)', 'bw'), bw_import_pretty_meta_label($meta_key), $meta_key);
            }
            $options[__('Custom Meta Fields (Metabox)', 'bw')] = $meta_group;
        }
    }

    return $options;
}

/**
 * Prova ad effettuare un auto-mapping basato sul nome della colonna.
 *
 * @param array $headers  Header del CSV.
 * @param array $options  Opzioni di mapping organizzate per gruppo.
 *
 * @return array
 */
function bw_import_guess_mapping($headers, $options)
{
    $flat_options = [];
    foreach ($options as $group_options) {
        foreach ($group_options as $key => $label) {
            $flat_options[$key] = [
                'normalized_key' => bw_import_normalize_mapping_key($key),
                'normalized_label' => bw_import_normalize_string($label),
            ];
        }
    }

    $aliases = bw_import_get_mapping_aliases();
    $guessed = [];

    foreach ($headers as $header) {
        $raw_header = trim((string) $header);
        $normalized_header = bw_import_normalize_string($header);

        // Treat explicit meta:* CSV headers as first-class mapping targets.
        if ($raw_header !== '' && isset($flat_options[$raw_header])) {
            $guessed[$header] = $raw_header;
            continue;
        }

        if (isset($aliases[$normalized_header]) && isset($flat_options[$aliases[$normalized_header]])) {
            $guessed[$header] = $aliases[$normalized_header];
            continue;
        }

        foreach ($flat_options as $key => $normalized) {
            if ($normalized_header === $normalized['normalized_key'] || $normalized_header === $normalized['normalized_label']) {
                $guessed[$header] = $key;
                break;
            }
        }
    }

    return $guessed;
}

/**
 * Normalizza una stringa per renderla confrontabile.
 *
 * @param string $value Valore da normalizzare.
 *
 * @return string
 */
function bw_import_normalize_string($value)
{
    $value = strtolower((string) $value);
    $value = preg_replace('/[^a-z0-9]+/', '_', $value);
    return trim($value, '_');
}

/**
 * Normalizza la chiave di mapping.
 *
 * @param string $key Chiave di mapping (es. meta:_foo, attribute_color).
 *
 * @return string
 */
function bw_import_normalize_mapping_key($key)
{
    if (strpos($key, 'meta:') === 0) {
        $key = substr($key, 5);
    }

    if (strpos($key, 'attribute_') === 0) {
        $key = substr($key, strlen('attribute_'));
    }

    return bw_import_normalize_string($key);
}

/**
 * Restituisce alias comuni per gli header del CSV.
 *
 * @return array
 */
function bw_import_get_mapping_aliases()
{
    $aliases = [
        'row_type' => 'row_type',
        'rowtype' => 'row_type',
        'parent_sku' => 'parent_sku',
        'parentsku' => 'parent_sku',
        'title' => 'post_title',
        'product_title' => 'post_title',
        'producttitle' => 'post_title',
        'name' => 'post_title',
        'product_name' => 'post_title',
        'slug' => 'post_name',
        'status' => 'post_status',
        'type' => 'product_type',
        'description' => 'post_content',
        'long_description' => 'post_content',
        'short_description' => 'post_excerpt',
        'regular_price' => 'regular_price',
        'price' => 'regular_price',
        'sale_price' => 'sale_price',
        'discount_price' => 'sale_price',
        'qty' => 'stock_quantity',
        'quantity' => 'stock_quantity',
        'stock' => 'stock_quantity',
        'featured_image' => 'featured_image',
        'image' => 'featured_image',
        'gallery' => 'product_gallery',
        'product_gallery' => 'product_gallery',
        'gallery_images' => 'product_gallery',
        'default_variation' => 'default_variation',
        'defaultvariation' => 'default_variation',
        'category' => 'categories',
        'categories' => 'categories',
        'product_subcategories' => 'product_subcategories',
        'productsubcategories' => 'product_subcategories',
        'subcategories' => 'product_subcategories',
        'tag' => 'tags',
        'tags' => 'tags',
        'upsell' => 'upsells',
        'upsells' => 'upsells',
        'crosssell' => 'cross_sells',
        'cross_sells' => 'cross_sells',
        'variation_name' => 'variation_name',
        'variationname' => 'variation_name',
        'variation_regular_price' => 'variation_regular_price',
        'variationregularprice' => 'variation_regular_price',
        'variation_sale_price' => 'variation_sale_price',
        'variationsaleprice' => 'variation_sale_price',
        'variation_image' => 'variation_image',
        'variationimage' => 'variation_image',
        'variation_enabled' => 'variation_enabled',
        'variationenabled' => 'variation_enabled',
        'variation_virtual' => 'variation_virtual',
        'variationvirtual' => 'variation_virtual',
        'variation_downloadable' => 'variation_downloadable',
        'variationdownloadable' => 'variation_downloadable',
        'variation_download_name' => 'variation_download_name',
        'variationdownloadname' => 'variation_download_name',
        'variation_download_url' => 'variation_download_url',
        'variationdownloadurl' => 'variation_download_url',
        'variation_attributes_json' => 'variation_attributes_json',
        'variationattributesjson' => 'variation_attributes_json',
    ];

    $normalized_aliases = [];
    foreach ($aliases as $alias => $target) {
        $normalized_aliases[bw_import_normalize_string($alias)] = $target;
    }

    return $normalized_aliases;
}

/**
 * Rileva i meta fields presenti nei file del metabox.
 *
 * @return array
 */
function bw_import_detect_custom_meta_fields()
{
    $meta_keys = [];

    $metabox_functions = [
        'bw_get_bibliographic_fields',
        'bw_get_prints_bibliographic_fields',
        'bw_get_digital_product_fields',
    ];

    foreach ($metabox_functions as $meta_function) {
        if (!function_exists($meta_function)) {
            continue;
        }

        $fields = call_user_func($meta_function);
        if (empty($fields) || !is_array($fields)) {
            continue;
        }

        foreach (array_keys($fields) as $meta_key) {
            if (strpos($meta_key, '_') === 0) {
                $meta_keys[$meta_key] = true;
            }
        }
    }

    $meta_directories = [
        trailingslashit(BW_MEW_PATH) . 'metabox/',
        trailingslashit(BW_MEW_PATH) . 'includes/product-types/',
    ];

    foreach ($meta_directories as $directory) {
        if (!is_dir($directory)) {
            continue;
        }

        foreach (glob($directory . '*.php') as $file) {
            $contents = file_get_contents($file);
            if (!$contents) {
                continue;
            }

            if (preg_match_all("/(?:update_post_meta|add_post_meta|get_post_meta)\s*\(\s*\$[a-zA-Z0-9_\->]+\s*,\s*'([^']+)'/", $contents, $matches)) {
                foreach ($matches[1] as $meta_key) {
                    if (strpos($meta_key, '_') === 0) {
                        $meta_keys[$meta_key] = true;
                    }
                }
            }
        }
    }

    return array_keys($meta_keys);
}

/**
 * Restituisce le opzioni di mapping per il meta field dello slider prodotto.
 *
 * @param array $detected_meta Meta rilevati automaticamente.
 *
 * @return array
 */
function bw_import_product_slider_meta_options($detected_meta)
{
    $meta_key = '_bw_slider_hover_image';

    if (!in_array($meta_key, $detected_meta, true)) {
        $detected_meta[] = $meta_key;
    }

    return [
        'meta:' . $meta_key => sprintf(__('Image over (%s)', 'bw'), $meta_key),
    ];
}

/**
 * Genera opzioni per gli attributi globali WooCommerce.
 *
 * @return array
 */
function bw_import_attribute_options()
{
    $options = [];
    if (!function_exists('wc_get_attribute_taxonomies')) {
        return $options;
    }

    $attributes = wc_get_attribute_taxonomies();
    if (empty($attributes)) {
        return $options;
    }

    foreach ($attributes as $attribute) {
        $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);
        $options['attribute_' . $taxonomy] = sprintf(__('Global Attribute: %s', 'bw'), $attribute->attribute_label);
    }

    return $options;
}

/**
 * Converte la chiave meta in etichetta leggibile.
 *
 * @param string $meta_key Meta key.
 *
 * @return string
 */
function bw_import_pretty_meta_label($meta_key)
{
    $label = str_replace('_', ' ', $meta_key);
    $label = trim($label, ' _');
    return ucwords($label);
}

/**
 * Verifica che ci sia almeno un identificativo prodotto mappato.
 *
 * @param array $mapping Mapping selezionato.
 *
 * @return bool
 */
function bw_import_has_identifier($mapping)
{
    $values = array_values($mapping);
    return in_array('sku', $values, true);
}

function bw_import_determine_row_type($row_data, $mapping)
{
    foreach ((array) $row_data as $header => $value) {
        if (!isset($mapping[$header]) || $mapping[$header] !== 'row_type') {
            continue;
        }

        $row_type = sanitize_key(trim((string) $value));
        if ($row_type === 'variation') {
            return 'variation';
        }

        if ($row_type === 'product' || $row_type === '') {
            return 'product';
        }

        return 'unknown';
    }

    return 'product';
}

/**
 * Elabora le righe del CSV in base al mapping.
 *
 * @param array $headers  Header del CSV.
 * @param array $rows     Righe del CSV.
 * @param array $mapping  Mapping colonne -> campi.
 *
 * @return array
 */
function bw_import_process_rows($headers, $rows, $mapping, $update_existing = false, $row_offset = 0, $options = [], &$run_state = null)
{
    $result = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors_count' => 0,
        'errors' => [],
        'warnings' => [],
        'details' => [
            'products_created' => 0,
            'products_updated' => 0,
            'variations_created' => 0,
            'variations_updated' => 0,
            'variations_skipped' => 0,
            'variations_failed' => 0,
        ],
    ];

    $checkpoint_callback = (isset($options['checkpoint_callback']) && is_callable($options['checkpoint_callback']))
        ? $options['checkpoint_callback']
        : null;
    $checkpoint_every = isset($options['checkpoint_every']) ? absint($options['checkpoint_every']) : 0;
    $processed_since_checkpoint = 0;
    $last_absolute_row = -1;
    $digital_parent_variations = [];

    $maybe_checkpoint = static function ($absolute_row) use (&$processed_since_checkpoint, &$last_absolute_row, $checkpoint_every, $checkpoint_callback) {
        if (!$checkpoint_callback || $checkpoint_every < 1) {
            return;
        }

        $last_absolute_row = (int) $absolute_row;
        $processed_since_checkpoint++;
        if ($processed_since_checkpoint >= $checkpoint_every) {
            call_user_func($checkpoint_callback, $last_absolute_row);
            $processed_since_checkpoint = 0;
        }
    };

    $prepared_entries = [];

    foreach ($rows as $row_index => $row) {
        $row_data = [];
        foreach ($headers as $i => $header) {
            $row_data[$header] = isset($row[$i]) ? $row[$i] : '';
        }

        $row_type = bw_import_determine_row_type($row_data, $mapping);
        $prepared = bw_import_prepare_row_data($row_data, $mapping);
        if (is_wp_error($prepared)) {
            $row_identity = bw_import_make_row_identity($row_offset, $row_index, '');
            $error_code = $prepared->get_error_code();
            $skip_codes = ['bw_import_missing_parent_match', 'bw_import_invalid_variation_row', 'bw_import_unknown_row_type', 'bw_import_existing_sku_requires_update'];
            if ($row_type === 'variation') {
                $skip_codes[] = 'bw_import_missing_identifiers';
            }
            $outcome = in_array($error_code, $skip_codes, true) ? 'skipped' : 'failed';
            $recorded = bw_import_record_row_outcome($run_state, $row_identity, $outcome, $prepared->get_error_message());
            if ($recorded) {
                $result['skipped']++;
                if ($outcome === 'failed') {
                    $result['errors_count']++;
                    if ($row_type === 'variation') {
                        $result['details']['variations_failed']++;
                    }
                } else {
                    if ($row_type === 'variation') {
                        $result['details']['variations_skipped']++;
                    }
                }
                $message = sprintf(__('Row %1$d: %2$s', 'bw'), $row_offset + $row_index + 2, $prepared->get_error_message());
                if ($outcome === 'failed') {
                    $result['errors'][] = $message;
                } else {
                    $result['warnings'][] = $message;
                }
            }
            $maybe_checkpoint($row_offset + $row_index + 1);
            continue;
        }

        if (!empty($prepared['warnings'])) {
            foreach ((array) $prepared['warnings'] as $warning) {
                $result['warnings'][] = sprintf(__('Row %1$d: %2$s', 'bw'), $row_offset + $row_index + 2, $warning);
            }
        }

        $row_sku = isset($prepared['product']['sku']) ? (string) $prepared['product']['sku'] : '';
        $row_identity = bw_import_make_row_identity($row_offset, $row_index, $row_sku);
        if (!empty($run_state['processed_row_keys']) && is_array($run_state['processed_row_keys']) && isset($run_state['processed_row_keys'][$row_identity])) {
            $maybe_checkpoint($row_offset + $row_index + 1);
            continue;
        }

        $prepared_entries[] = [
            'row_index' => $row_index,
            'absolute_row' => $row_offset + $row_index + 1,
            'prepared' => $prepared,
            'row_identity' => $row_identity,
            'row_type' => !empty($prepared['row_type']) ? $prepared['row_type'] : 'product',
        ];
    }

    $parent_default_variations = [];
    foreach ($prepared_entries as $entry) {
        if ($entry['row_type'] !== 'variation' && !empty($entry['prepared']['product']['sku']) && !empty($entry['prepared']['product']['default_variation'])) {
            $parent_default_variations[(string) $entry['prepared']['product']['sku']] = (string) $entry['prepared']['product']['default_variation'];
        }
    }

    usort($prepared_entries, static function ($left, $right) {
        $left_weight = $left['row_type'] === 'variation' ? 1 : 0;
        $right_weight = $right['row_type'] === 'variation' ? 1 : 0;
        if ($left_weight === $right_weight) {
            return $left['row_index'] <=> $right['row_index'];
        }

        return $left_weight <=> $right_weight;
    });

    foreach ($prepared_entries as $entry) {
        $prepared = $entry['prepared'];
        $row_identity = $entry['row_identity'];
        $row_index = (int) $entry['row_index'];
        $row_type = $entry['row_type'];

        if (
            $row_type === 'variation'
            && empty($prepared['product']['default_variation'])
            && !empty($prepared['parent_sku'])
            && isset($parent_default_variations[(string) $prepared['parent_sku']])
        ) {
            $prepared['product']['default_variation'] = $parent_default_variations[(string) $prepared['parent_sku']];
        }

        $save_result = $row_type === 'variation'
            ? bw_import_save_variation_from_row($prepared, $update_existing, $options)
            : bw_import_save_product_from_row($prepared, $update_existing, $options);
        if (is_wp_error($save_result)) {
            $error_code = $save_result->get_error_code();
            $row_recorded = false;
            $skip_codes = ['bw_import_missing_product_match', 'bw_import_missing_parent_match', 'bw_import_invalid_variation_row', 'bw_import_unknown_row_type', 'bw_import_existing_sku_requires_update'];
            if (in_array($error_code, $skip_codes, true)) {
                $row_recorded = bw_import_record_row_outcome($run_state, $row_identity, 'skipped', $save_result->get_error_message());
                if ($row_recorded) {
                    $result['skipped']++;
                    if ($row_type === 'variation') {
                        $result['details']['variations_skipped']++;
                    }
                }
            } else {
                $row_recorded = bw_import_record_row_outcome($run_state, $row_identity, 'failed', $save_result->get_error_message());
                if ($row_recorded) {
                    $result['skipped']++;
                    $result['errors_count']++;
                    if ($row_type === 'variation') {
                        $result['details']['variations_failed']++;
                    }
                }
            }
            if ($row_recorded) {
                $message = sprintf(__('Row %1$d: %2$s', 'bw'), $row_offset + $row_index + 2, $save_result->get_error_message());
                if (in_array($error_code, $skip_codes, true)) {
                    $result['warnings'][] = $message;
                } else {
                    $result['errors'][] = $message;
                }
            }
            $maybe_checkpoint($row_offset + $row_index + 1);
            continue;
        }

        if (!empty($save_result['warnings'])) {
            foreach ((array) $save_result['warnings'] as $warning) {
                $result['warnings'][] = sprintf(__('Row %1$d: %2$s', 'bw'), $row_offset + $row_index + 2, $warning);
            }
        }

        if (
            $row_type === 'variation'
            && !empty($save_result['parent_id'])
            && !empty($save_result['variation_id'])
            && !empty($prepared['variation']['name'])
        ) {
            $parent_id = absint($save_result['parent_id']);
            $variation_id = absint($save_result['variation_id']);
            $variation_name = bw_import_canonicalize_license_value((string) $prepared['variation']['name']);
            if ($parent_id > 0 && $variation_id > 0 && $variation_name !== '') {
                if (!isset($digital_parent_variations[$parent_id]) || !is_array($digital_parent_variations[$parent_id])) {
                    $digital_parent_variations[$parent_id] = [];
                }
                $digital_parent_variations[$parent_id][$variation_id] = $variation_name;
            }
        }

        $entity_type = isset($save_result['entity_type']) ? (string) $save_result['entity_type'] : ($row_type === 'variation' ? 'variation' : 'product');
        if (!empty($save_result['status']) && $save_result['status'] === 'updated') {
            if (bw_import_record_row_outcome($run_state, $row_identity, 'updated')) {
                $result['updated']++;
                if ($entity_type === 'variation') {
                    $result['details']['variations_updated']++;
                } else {
                    $result['details']['products_updated']++;
                }
            }
        } else {
            if (bw_import_record_row_outcome($run_state, $row_identity, 'created')) {
                $result['created']++;
                if ($entity_type === 'variation') {
                    $result['details']['variations_created']++;
                } else {
                    $result['details']['products_created']++;
                }
            }
        }

        $maybe_checkpoint($row_offset + $row_index + 1);
    }

    if ($checkpoint_callback && $processed_since_checkpoint > 0 && $last_absolute_row >= 0) {
        call_user_func($checkpoint_callback, $last_absolute_row);
    }

    foreach ($digital_parent_variations as $parent_id => $variation_map) {
        $repair_warnings = [];
        bw_import_repair_digital_license_variations((int) $parent_id, $variation_map, $repair_warnings);
        foreach ($repair_warnings as $warning) {
            $result['warnings'][] = $warning;
        }
    }

    return $result;
}

/**
 * Prepara i dati della riga in base al mapping.
 *
 * @param array $row_data Dati riga.
 * @param array $mapping  Mapping.
 *
 * @return array|WP_Error
 */
function bw_import_prepare_row_data($row_data, $mapping)
{
    $data = [
        'row_type' => 'product',
        'parent_sku' => '',
        'product' => [],
        'variation' => [],
        'meta' => [],
        'categories' => [],
        'tags' => [],
        'attributes' => [],
        'upsells' => [],
        'cross_sells' => [],
        'warnings' => [],
    ];

    foreach ($row_data as $header => $value) {
        $target = isset($mapping[$header]) ? $mapping[$header] : 'ignore';
        if ('ignore' === $target) {
            continue;
        }

        $clean_value = is_string($value) ? trim(wp_unslash($value)) : $value;

        if (strpos($target, 'meta:') === 0) {
            $meta_key = substr($target, 5);
            $data['meta'][$meta_key] = $clean_value;
            continue;
        }

        if (strpos($target, 'attribute_') === 0) {
            $taxonomy = substr($target, strlen('attribute_'));
            $data['attributes'][$taxonomy] = $clean_value;
            continue;
        }

        switch ($target) {
            case 'row_type':
                $row_type = sanitize_key($clean_value);
                if ($row_type === '') {
                    $data['row_type'] = 'product';
                } elseif (in_array($row_type, ['product', 'variation'], true)) {
                    $data['row_type'] = $row_type;
                } else {
                    return new WP_Error('bw_import_unknown_row_type', sprintf(__('Unknown row_type "%s".', 'bw'), $row_type));
                }
                break;
            case 'parent_sku':
                $data['parent_sku'] = sanitize_text_field($clean_value);
                break;
            case 'product_id':
                $data['product']['id'] = absint($clean_value);
                break;
            case 'sku':
                $data['product']['sku'] = sanitize_text_field($clean_value);
                break;
            case 'post_title':
                $data['product']['name'] = sanitize_text_field($clean_value);
                break;
            case 'post_name':
                $data['product']['slug'] = sanitize_title($clean_value);
                break;
            case 'post_status':
                $status = strtolower(sanitize_key($clean_value));
                $allowed_statuses = ['draft', 'publish', 'pending', 'private'];
                if ($status !== '' && !in_array($status, $allowed_statuses, true)) {
                    $data['product']['status'] = 'draft';
                    $data['warnings'][] = sprintf(
                        /* translators: %s: invalid status value */
                        __('Invalid status "%s" normalized to draft.', 'bw'),
                        $status
                    );
                } else {
                    $data['product']['status'] = $status;
                }
                break;
            case 'product_type':
                $product_type = sanitize_key($clean_value);
                $allowed_product_types = ['simple', 'variable', 'grouped', 'external'];
                if ($product_type !== '' && !in_array($product_type, $allowed_product_types, true)) {
                    $data['product']['type'] = '';
                    $data['warnings'][] = sprintf(
                        /* translators: %s: invalid product type */
                        __('Invalid product type "%s" ignored.', 'bw'),
                        $product_type
                    );
                } else {
                    $data['product']['type'] = $product_type;
                }
                break;
            case 'default_variation':
                $data['product']['default_variation'] = sanitize_title($clean_value);
                break;
            case 'post_content':
                $data['product']['description'] = wp_kses_post($clean_value);
                break;
            case 'post_excerpt':
                $data['product']['short_description'] = wp_kses_post($clean_value);
                break;
            case 'regular_price':
                $data['product']['regular_price'] = wc_format_decimal($clean_value);
                break;
            case 'sale_price':
                $data['product']['sale_price'] = wc_format_decimal($clean_value);
                break;
            case 'sale_price_dates_from':
                $data['product']['sale_start'] = sanitize_text_field($clean_value);
                break;
            case 'sale_price_dates_to':
                $data['product']['sale_end'] = sanitize_text_field($clean_value);
                break;
            case 'stock_quantity':
                $data['product']['stock_quantity'] = (float) $clean_value;
                break;
            case 'manage_stock':
                $data['product']['manage_stock'] = in_array(strtolower($clean_value), ['yes', '1', 'true'], true);
                break;
            case 'stock_status':
                $stock_status = sanitize_key($clean_value);
                $allowed_stock_statuses = ['instock', 'outofstock', 'onbackorder'];
                if ($stock_status !== '' && !in_array($stock_status, $allowed_stock_statuses, true)) {
                    $data['product']['stock_status'] = '';
                    $data['warnings'][] = sprintf(
                        /* translators: %s: invalid stock status */
                        __('Invalid stock status "%s" ignored.', 'bw'),
                        $stock_status
                    );
                } else {
                    $data['product']['stock_status'] = $stock_status;
                }
                break;
            case 'backorders':
                $backorders = sanitize_key($clean_value);
                $allowed_backorders = ['no', 'notify', 'yes'];
                if ($backorders !== '' && !in_array($backorders, $allowed_backorders, true)) {
                    $data['product']['backorders'] = '';
                    $data['warnings'][] = sprintf(
                        /* translators: %s: invalid backorders value */
                        __('Invalid backorders value "%s" ignored.', 'bw'),
                        $backorders
                    );
                } else {
                    $data['product']['backorders'] = $backorders;
                }
                break;
            case 'sold_individually':
                $data['product']['sold_individually'] = in_array(strtolower($clean_value), ['yes', '1', 'true'], true);
                break;
            case 'weight':
            case 'length':
            case 'width':
            case 'height':
                $data['product'][$target] = wc_format_decimal($clean_value);
                break;
            case 'shipping_class':
                $data['product']['shipping_class'] = sanitize_title($clean_value);
                break;
            case 'tax_status':
                $tax_status = sanitize_key($clean_value);
                $allowed_tax_statuses = ['taxable', 'shipping', 'none'];
                if ($tax_status !== '' && !in_array($tax_status, $allowed_tax_statuses, true)) {
                    $data['product']['tax_status'] = '';
                    $data['warnings'][] = sprintf(
                        /* translators: %s: invalid tax status */
                        __('Invalid tax status "%s" ignored.', 'bw'),
                        $tax_status
                    );
                } else {
                    $data['product']['tax_status'] = $tax_status;
                }
                break;
            case 'tax_class':
                $data['product']['tax_class'] = sanitize_title($clean_value);
                break;
            case 'categories':
                $data['categories'] = bw_import_explode_list($clean_value);
                break;
            case 'product_subcategories':
                $data['product_subcategories'] = bw_import_normalize_product_subcategories($clean_value);
                break;
            case 'tags':
                $data['tags'] = bw_import_explode_list($clean_value);
                break;
            case 'featured_image':
                $data['product']['featured_image'] = esc_url_raw($clean_value);
                break;
            case 'product_gallery':
            case 'gallery_images':
                $data['product']['gallery'] = array_map('esc_url_raw', bw_import_explode_list($clean_value));
                break;
            case 'upsells':
                $data['upsells'] = bw_import_explode_list($clean_value);
                break;
            case 'cross_sells':
                $data['cross_sells'] = bw_import_explode_list($clean_value);
                break;
            case 'variation_name':
                $data['variation']['name'] = sanitize_text_field($clean_value);
                break;
            case 'variation_regular_price':
                $data['variation']['regular_price'] = wc_format_decimal($clean_value);
                break;
            case 'variation_sale_price':
                $data['variation']['sale_price'] = wc_format_decimal($clean_value);
                break;
            case 'variation_image':
                $data['variation']['image'] = esc_url_raw($clean_value);
                break;
            case 'variation_enabled':
                $data['variation']['enabled'] = in_array(strtolower((string) $clean_value), ['yes', '1', 'true'], true);
                break;
            case 'variation_virtual':
                $data['variation']['virtual'] = in_array(strtolower((string) $clean_value), ['yes', '1', 'true'], true);
                break;
            case 'variation_downloadable':
                $data['variation']['downloadable'] = in_array(strtolower((string) $clean_value), ['yes', '1', 'true'], true);
                break;
            case 'variation_download_name':
                $data['variation']['download_name'] = sanitize_text_field($clean_value);
                break;
            case 'variation_download_url':
                $data['variation']['download_url'] = esc_url_raw($clean_value);
                break;
            case 'variation_attributes_json':
                $data['variation']['attributes_json'] = is_string($clean_value) ? trim($clean_value) : '';
                break;
        }
    }

    if (empty($data['product']['sku'])) {
        return new WP_Error('bw_import_missing_identifiers', __('Missing SKU for this row.', 'bw'));
    }

    if ($data['row_type'] === 'variation') {
        if (empty($data['parent_sku'])) {
            return new WP_Error('bw_import_invalid_variation_row', __('Variation row is missing parent_sku.', 'bw'));
        }

        if (empty($data['variation']['name'])) {
            return new WP_Error('bw_import_invalid_variation_row', __('Variation row is missing variation_name.', 'bw'));
        }
    }

    return $data;
}

function bw_import_normalize_meta_aliases($meta)
{
    $normalized = is_array($meta) ? $meta : [];
    $mirror_pairs = [
        ['_bw_artist_name', '_digital_artist_name'],
        ['_bw_assets_count', '_digital_total_assets'],
        ['_bw_formats', '_digital_formats'],
        ['_bw_file_size', '_digital_file_size'],
    ];

    foreach ($mirror_pairs as $pair) {
        $left_key = $pair[0];
        $right_key = $pair[1];
        $left_value = isset($normalized[$left_key]) ? trim((string) $normalized[$left_key]) : '';
        $right_value = isset($normalized[$right_key]) ? trim((string) $normalized[$right_key]) : '';

        if ($left_value !== '' && $right_value === '') {
            $normalized[$right_key] = $normalized[$left_key];
        } elseif ($right_value !== '' && $left_value === '') {
            $normalized[$left_key] = $normalized[$right_key];
        }
    }

    return $normalized;
}

function bw_import_find_product_id_by_sku($sku, $allowed_post_types = ['product', 'product_variation'])
{
    global $wpdb;

    $sku = sanitize_text_field((string) $sku);
    if ($sku === '') {
        return 0;
    }

    $allowed_post_types = array_values(array_filter(array_map('sanitize_key', (array) $allowed_post_types)));
    if (empty($allowed_post_types)) {
        $allowed_post_types = ['product', 'product_variation'];
    }

    $product_id = (int) wc_get_product_id_by_sku($sku);
    if ($product_id > 0) {
        $post_type = get_post_type($product_id);
        $post_status = get_post_status($product_id);
        if ($post_type && $post_status !== 'trash' && in_array($post_type, $allowed_post_types, true)) {
            return $product_id;
        }
    }

    $placeholders = implode(',', array_fill(0, count($allowed_post_types), '%s'));
    $sql = "
        SELECT posts.ID
        FROM {$wpdb->posts} AS posts
        INNER JOIN {$wpdb->postmeta} AS postmeta
            ON posts.ID = postmeta.post_id
        WHERE postmeta.meta_key = '_sku'
          AND postmeta.meta_value = %s
          AND posts.post_type IN ({$placeholders})
          AND posts.post_status <> 'trash'
        ORDER BY posts.ID DESC
        LIMIT 1
    ";

    $prepared = array_merge([$sql, $sku], $allowed_post_types);
    $resolved_id = (int) $wpdb->get_var($wpdb->prepare(...$prepared));

    return $resolved_id > 0 ? $resolved_id : 0;
}

function bw_import_assign_sku_to_object($product, $sku)
{
    $sku = sanitize_text_field((string) $sku);
    if ($sku === '' || !$product || !method_exists($product, 'get_id')) {
        return true;
    }

    $current_product_id = absint($product->get_id());
    $current_sku = method_exists($product, 'get_sku') ? sanitize_text_field((string) $product->get_sku()) : '';

    if ($current_product_id > 0 && $current_sku === $sku) {
        return true;
    }

    $conflict_id = bw_import_find_product_id_by_sku($sku, ['product', 'product_variation']);
    if ($conflict_id > 0 && $conflict_id !== $current_product_id) {
        $conflict_type = get_post_type($conflict_id);
        $conflict_label = $conflict_type === 'product_variation'
            ? __('variation', 'bw')
            : __('product', 'bw');

        return new WP_Error(
            'bw_import_sku_conflict',
            sprintf(
                /* translators: 1: SKU, 2: object type, 3: post ID */
                __('SKU %1$s is already assigned to %2$s ID %3$d.', 'bw'),
                $sku,
                $conflict_label,
                $conflict_id
            )
        );
    }

    try {
        $product->set_sku($sku);
    } catch (WC_Data_Exception $exception) {
        return new WP_Error('bw_import_sku', $exception->getMessage());
    }

    return true;
}

function bw_import_format_existing_sku_requires_update_message($sku, $entity_label = 'Product')
{
    $sku = sanitize_text_field((string) $sku);
    $entity_label = sanitize_text_field((string) $entity_label);

    return sprintf(
        /* translators: 1: entity label, 2: SKU */
        __('%1$s with SKU %2$s already exists. Enable Update existing products to update it instead of creating a duplicate.', 'bw'),
        $entity_label,
        $sku
    );
}

/**
 * Suddivide una stringa in array usando virgola o pipe.
 *
 * @param string $value Valore da esplodere.
 *
 * @return array
 */
function bw_import_explode_list($value)
{
    $value = (string) $value;
    $parts = preg_split('/[|,]/', $value);
    $parts = array_filter(array_map('trim', $parts));
    return $parts;
}

function bw_import_normalize_product_subcategories($value)
{
    $items = is_array($value) ? $value : bw_import_explode_list((string) $value);
    $items = array_map(static function ($item) {
        return sanitize_text_field(trim((string) $item));
    }, (array) $items);

    $items = array_values(array_filter($items, static function ($item) {
        return $item !== '';
    }));

    return $items;
}

function bw_import_product_subcategories_to_meta_string($value)
{
    $items = bw_import_normalize_product_subcategories($value);
    if (empty($items)) {
        return '';
    }

    return implode(', ', $items);
}

function bw_import_find_product_cat_term_id_by_name_under_parent($name, $parent_term_id)
{
    $name = sanitize_text_field(trim((string) $name));
    $parent_term_id = absint($parent_term_id);

    if ($name === '' || $parent_term_id < 1) {
        return 0;
    }

    $target_slug = sanitize_title($name);
    $terms = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'parent' => $parent_term_id,
        'number' => 200,
    ]);

    if (is_wp_error($terms) || empty($terms)) {
        return 0;
    }

    foreach ($terms as $term) {
        if (!$term instanceof WP_Term) {
            continue;
        }

        if (strcasecmp((string) $term->name, $name) === 0 || sanitize_title((string) $term->slug) === $target_slug) {
            return (int) $term->term_id;
        }
    }

    return 0;
}

function bw_import_maybe_create_allowed_digital_product_subcategory($name, $parent_term_id)
{
    $name = sanitize_text_field(trim((string) $name));
    $parent_term_id = absint($parent_term_id);

    if ($name === '' || $parent_term_id < 1) {
        return 0;
    }

    $allowed_subcategories = bw_digital_csv_builder_allowed_subcategories();
    if (!in_array($name, $allowed_subcategories, true)) {
        return 0;
    }

    $created = wp_insert_term(
        $name,
        'product_cat',
        [
            'parent' => $parent_term_id,
            'slug' => sanitize_title($name),
        ]
    );

    if (is_wp_error($created) || empty($created['term_id'])) {
        return 0;
    }

    return (int) $created['term_id'];
}

function bw_import_assign_digital_product_subcategories($product_id, $subcategory_string, &$warnings = [])
{
    $product_id = absint($product_id);
    if ($product_id < 1) {
        return false;
    }

    $subcategory_items = bw_import_normalize_product_subcategories($subcategory_string);
    if (empty($subcategory_items)) {
        return true;
    }

    $parent_term = get_term_by('slug', sanitize_title('Digital Collections'), 'product_cat');
    if (!$parent_term || is_wp_error($parent_term)) {
        $parent_term = get_term_by('name', 'Digital Collections', 'product_cat');
    }

    if (!$parent_term || is_wp_error($parent_term) || !($parent_term instanceof WP_Term)) {
        $warnings[] = __('Digital Collections category was not found, so product subcategories could not be assigned.', 'bw');
        return false;
    }

    $existing_term_ids = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
    if (is_wp_error($existing_term_ids) || !is_array($existing_term_ids)) {
        $existing_term_ids = [];
    }

    $assigned_term_ids = array_map('absint', $existing_term_ids);
    $assigned_term_ids[] = (int) $parent_term->term_id;

    foreach ($subcategory_items as $subcategory_name) {
        $subcategory_term_id = bw_import_find_product_cat_term_id_by_name_under_parent($subcategory_name, (int) $parent_term->term_id);
        if ($subcategory_term_id < 1) {
            $subcategory_term_id = bw_import_maybe_create_allowed_digital_product_subcategory($subcategory_name, (int) $parent_term->term_id);
        }
        if ($subcategory_term_id > 0) {
            $assigned_term_ids[] = $subcategory_term_id;
            continue;
        }

        $warnings[] = sprintf(
            /* translators: %s: subcategory name */
            __('Product subcategory "%s" was not found under Digital Collections.', 'bw'),
            $subcategory_name
        );
    }

    $assigned_term_ids = array_values(array_unique(array_filter(array_map('absint', $assigned_term_ids))));
    if (empty($assigned_term_ids)) {
        return false;
    }

    wp_set_object_terms($product_id, $assigned_term_ids, 'product_cat', false);
    return true;
}

/**
 * Salva un prodotto a partire dai dati di riga.
 *
 * @param array $data             Dati preparati.
 * @param bool  $update_existing  Se true, aggiorna solo prodotti già esistenti.
 *
 * @return string|WP_Error
 */
function bw_import_save_product_from_row($data, $update_existing = false, $options = [])
{
    $options = wp_parse_args(
        $options,
        [
            'skip_images' => true,
            'sku_retry_done' => false,
        ]
    );

    $product_id = isset($data['product']['id']) ? absint($data['product']['id']) : 0;
    $sku = isset($data['product']['sku']) ? $data['product']['sku'] : '';
    $product = null;
    $status = 'created';
    $warnings = [];
    $resolved_product_id = 0;
    $variation_conflict_id = 0;

    if ($product_id) {
        $product = wc_get_product($product_id);
    }

    if ($sku) {
        $resolved_product_id = bw_import_find_product_id_by_sku($sku, ['product']);
        $variation_conflict_id = bw_import_find_product_id_by_sku($sku, ['product_variation']);

        if ($variation_conflict_id > 0) {
            return new WP_Error(
                'bw_import_sku_conflict',
                sprintf(
                    /* translators: 1: SKU, 2: conflicting variation ID */
                    __('SKU %1$s is already assigned to variation ID %2$d.', 'bw'),
                    sanitize_text_field((string) $sku),
                    $variation_conflict_id
                )
            );
        }

        if (!$product && $resolved_product_id > 0 && $update_existing) {
            $product = wc_get_product($resolved_product_id);
            if ($product) {
                $product_id = $resolved_product_id;
            }
        }
    }

    $requested_product_type = !empty($data['product']['type']) ? $data['product']['type'] : '';
    if ($product && $requested_product_type !== '') {
        $product = bw_import_maybe_convert_product_type($product, $requested_product_type);
        if (!$product) {
            return new WP_Error('bw_import_product_object', __('Unable to reload product after type conversion.', 'bw'));
        }
        $product_id = $product->get_id();
    }

    if ($product) {
        $status = 'updated';
    } elseif ($update_existing) {
        return new WP_Error(
            'bw_import_missing_product_match',
            __('Skipping row because no existing product matches the provided ID or SKU.', 'bw')
        );
    } else {
        if ($sku && $resolved_product_id > 0) {
            return new WP_Error(
                'bw_import_existing_sku_requires_update',
                bw_import_format_existing_sku_requires_update_message($sku, __('Product', 'bw'))
            );
        }

        if (!$product) {
            $product_type = !empty($data['product']['type']) ? $data['product']['type'] : 'simple';

            try {
                $product = wc_get_product_object($product_type);
            } catch (Throwable $exception) {
                return new WP_Error('bw_import_product_object', $exception->getMessage());
            }

            if (!$product) {
                return new WP_Error('bw_import_product_object', __('Unable to create product object for type.', 'bw'));
            }
        }
    }

    if ($sku) {
        $sku_assignment = bw_import_assign_sku_to_object($product, $sku);
        if (is_wp_error($sku_assignment)) {
            if (
                $sku_assignment->get_error_code() === 'bw_import_sku'
                && empty($options['sku_retry_done'])
            ) {
                $resolved_product_id = bw_import_find_product_id_by_sku($sku, ['product']);
                if ($resolved_product_id) {
                    $retry_data = $data;
                    $retry_data['product']['id'] = $resolved_product_id;
                    $retry_options = $options;
                    $retry_options['sku_retry_done'] = true;
                    return bw_import_save_product_from_row($retry_data, false, $retry_options);
                }
            }

            return $sku_assignment;
        }
    }

    if (!empty($data['product']['name'])) {
        $product->set_name($data['product']['name']);
    }

    if (!empty($data['product']['slug'])) {
        $product->set_slug($data['product']['slug']);
    }

    if (!empty($data['product']['status'])) {
        $product->set_status($data['product']['status']);
    }

    if (!empty($data['product']['description'])) {
        $product->set_description($data['product']['description']);
    }

    if (!empty($data['product']['short_description'])) {
        $product->set_short_description($data['product']['short_description']);
    }

    if (isset($data['product']['regular_price'])) {
        $product->set_regular_price($data['product']['regular_price']);
    }

    if (isset($data['product']['sale_price'])) {
        $product->set_sale_price($data['product']['sale_price']);
    }

    if (!empty($data['product']['sale_start'])) {
        $product->set_date_on_sale_from($data['product']['sale_start']);
    }

    if (!empty($data['product']['sale_end'])) {
        $product->set_date_on_sale_to($data['product']['sale_end']);
    }

    if (isset($data['product']['stock_quantity'])) {
        $product->set_stock_quantity($data['product']['stock_quantity']);
    }

    if (isset($data['product']['manage_stock'])) {
        $product->set_manage_stock((bool) $data['product']['manage_stock']);
    }

    if (!empty($data['product']['stock_status'])) {
        $product->set_stock_status($data['product']['stock_status']);
    }

    if (!empty($data['product']['backorders'])) {
        $product->set_backorders($data['product']['backorders']);
    }

    if (isset($data['product']['sold_individually'])) {
        $product->set_sold_individually((bool) $data['product']['sold_individually']);
    }

    foreach (['weight', 'length', 'width', 'height'] as $dimension) {
        if (isset($data['product'][$dimension])) {
            $setter = 'set_' . $dimension;
            $product->$setter($data['product'][$dimension]);
        }
    }

    if (!empty($data['product']['shipping_class'])) {
        $shipping_class_id = 0;

        if (is_numeric($data['product']['shipping_class'])) {
            $shipping_class_id = (int) $data['product']['shipping_class'];
        } else {
            $existing_shipping_class = term_exists($data['product']['shipping_class'], 'product_shipping_class');

            if ($existing_shipping_class && !is_wp_error($existing_shipping_class)) {
                $shipping_class_id = (int) $existing_shipping_class['term_id'];
            } else {
                $created_shipping_class = wp_insert_term($data['product']['shipping_class'], 'product_shipping_class');

                if ($created_shipping_class && !is_wp_error($created_shipping_class)) {
                    $shipping_class_id = (int) $created_shipping_class['term_id'];
                }
            }
        }

        if ($shipping_class_id) {
            $product->set_shipping_class_id($shipping_class_id);
        }
    }

    if (!empty($data['product']['tax_status'])) {
        $product->set_tax_status($data['product']['tax_status']);
    }

    if (!empty($data['product']['tax_class'])) {
        $product->set_tax_class($data['product']['tax_class']);
    }

    if (!empty($data['categories'])) {
        $category_ids = bw_import_resolve_term_ids($data['categories'], 'product_cat');
        if (!empty($category_ids)) {
            $product->set_category_ids($category_ids);
        }
    }

    if (!empty($data['tags'])) {
        $tag_ids = bw_import_resolve_term_ids($data['tags'], 'product_tag');
        if (!empty($tag_ids)) {
            $product->set_tag_ids($tag_ids);
        }
    }

    if (isset($data['product_subcategories'])) {
        $subcategory_value = bw_import_product_subcategories_to_meta_string($data['product_subcategories']);
        $product->update_meta_data('product_subcategories', $subcategory_value);
        $product->update_meta_data('_bw_product_subcategories', $subcategory_value);
    }

    $normalized_meta = bw_import_normalize_meta_aliases(isset($data['meta']) ? $data['meta'] : []);

    if (!empty($normalized_meta)) {
        foreach ($normalized_meta as $meta_key => $meta_value) {
            $product->update_meta_data($meta_key, $meta_value);
        }
    }

    if (!empty($data['attributes'])) {
        $attribute_objects = bw_import_build_attributes($data['attributes']);
        if (!empty($attribute_objects)) {
            $product->set_attributes($attribute_objects);
        }
    }

    if (!empty($data['upsells'])) {
        $product->set_upsell_ids(bw_import_locate_product_ids($data['upsells']));
    }

    if (!empty($data['cross_sells'])) {
        $product->set_cross_sell_ids(bw_import_locate_product_ids($data['cross_sells']));
    }

    if (!empty($options['skip_images'])) {
        if (!empty($data['product']['featured_image']) || !empty($data['product']['gallery'])) {
            $warnings[] = __('Images skipped by configuration.', 'bw');
        }
        if (!empty($normalized_meta['_bw_slider_hover_image'])) {
            $warnings[] = __('Hover image skipped by configuration.', 'bw');
        }
    } else {
        if (!empty($data['product']['featured_image'])) {
            $featured_image_error = '';
            $attachment_id = bw_import_handle_image($data['product']['featured_image'], $product_id, $featured_image_error);
            if ($attachment_id) {
                $product->set_image_id($attachment_id);
            } else {
                $warnings[] = sprintf(
                    __('Featured image sideload failed: %1$s (%2$s)', 'bw'),
                    esc_url_raw($data['product']['featured_image']),
                    $featured_image_error !== '' ? $featured_image_error : __('unknown error', 'bw')
                );
            }
        }

        if (!empty($data['product']['gallery'])) {
            $gallery_ids = [];
            foreach ($data['product']['gallery'] as $image_url) {
                $gallery_error = '';
                $image_id = bw_import_handle_image($image_url, $product_id, $gallery_error);
                if ($image_id) {
                    $gallery_ids[] = $image_id;
                } else {
                    $warnings[] = sprintf(
                        /* translators: 1: image URL, 2: error detail */
                        __('Gallery image sideload failed: %1$s (%2$s)', 'bw'),
                        esc_url_raw($image_url),
                        $gallery_error !== '' ? $gallery_error : __('unknown error', 'bw')
                    );
                }
            }
            $product->set_gallery_image_ids($gallery_ids);
        }

        if (!empty($normalized_meta['_bw_slider_hover_image'])) {
            $hover_image_error = '';
            $hover_image_id = bw_import_handle_image($normalized_meta['_bw_slider_hover_image'], $product_id, $hover_image_error);
            if ($hover_image_id) {
                $product->update_meta_data('_bw_slider_hover_image', $hover_image_id);
            } else {
                $warnings[] = sprintf(
                    __('Hover image sideload failed: %1$s (%2$s)', 'bw'),
                    esc_url_raw($normalized_meta['_bw_slider_hover_image']),
                    $hover_image_error !== '' ? $hover_image_error : __('unknown error', 'bw')
                );
            }
        }
    }

    try {
        $product->save();
    } catch (Throwable $exception) {
        return new WP_Error('bw_import_save', $exception->getMessage());
    }

    if (isset($data['product_subcategories'])) {
        $subcategory_value = bw_import_product_subcategories_to_meta_string($data['product_subcategories']);
        update_post_meta($product->get_id(), 'product_subcategories', $subcategory_value);
        update_post_meta($product->get_id(), '_bw_product_subcategories', $subcategory_value);
        if ($subcategory_value === '') {
            $warnings[] = __('Product subcategories were present in mapping but resolved to an empty value.', 'bw');
        } elseif (!bw_import_assign_digital_product_subcategories($product->get_id(), $subcategory_value, $warnings)) {
            $warnings[] = __('Product subcategories meta was saved, but no matching WooCommerce product categories could be assigned.', 'bw');
        }
    }

    if (
        !empty($data['product']['default_variation'])
        && !empty($data['product']['type'])
        && $data['product']['type'] === 'variable'
    ) {
        update_post_meta($product->get_id(), '_bw_import_default_variation', sanitize_title((string) $data['product']['default_variation']));
    }

    return [
        'entity_type' => 'product',
        'status' => $status,
        'warnings' => $warnings,
    ];
}

function bw_import_maybe_convert_product_type($product, $requested_product_type)
{
    if (!$product || !method_exists($product, 'get_id')) {
        return $product;
    }

    $requested_product_type = sanitize_key((string) $requested_product_type);
    if ($requested_product_type === '' || !$product->get_id() || $product->is_type($requested_product_type)) {
        return $product;
    }

    if ($requested_product_type !== 'variable') {
        return $product;
    }

    wp_set_object_terms($product->get_id(), $requested_product_type, 'product_type', false);
    clean_post_cache($product->get_id());
    wc_delete_product_transients($product->get_id());

    return wc_get_product($product->get_id());
}

function bw_import_find_variation_by_sku($sku, $parent_id = 0)
{
    $sku = sanitize_text_field((string) $sku);
    if ($sku === '') {
        return 0;
    }

    $variation_id = bw_import_find_product_id_by_sku($sku, ['product_variation']);
    if ($variation_id < 1) {
        return 0;
    }

    $variation = wc_get_product($variation_id);
    if (!$variation || !$variation->is_type('variation')) {
        return 0;
    }

    if ($parent_id > 0 && (int) $variation->get_parent_id() !== (int) $parent_id) {
        return 0;
    }

    return $variation_id;
}

function bw_import_build_license_attribute_key()
{
    return sanitize_title('License');
}

function bw_import_canonicalize_license_value($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $normalized = sanitize_title($value);
    if ($normalized === 'commercial') {
        return 'Commercial';
    }

    if ($normalized === 'extended') {
        return 'Extended';
    }

    return $value;
}

function bw_import_ensure_parent_license_attribute($parent_product, $variation_name)
{
    if (!$parent_product || !method_exists($parent_product, 'get_attributes')) {
        return '';
    }

    $variation_name = bw_import_canonicalize_license_value($variation_name);
    if ($variation_name === '') {
        return '';
    }

    $attribute_key = bw_import_build_license_attribute_key();
    $existing_attributes = $parent_product->get_attributes();
    $license_attribute = null;
    $license_attribute_index = null;

    foreach ((array) $existing_attributes as $index => $attribute) {
        if (!$attribute instanceof WC_Product_Attribute) {
            continue;
        }

        if (sanitize_title((string) $attribute->get_name()) === $attribute_key) {
            $license_attribute = $attribute;
            $license_attribute_index = $index;
            break;
        }
    }

    if (!$license_attribute) {
        $license_attribute = new WC_Product_Attribute();
        $license_attribute->set_id(0);
        $license_attribute->set_name('License');
        $license_attribute->set_options([$variation_name]);
        $license_attribute->set_position(count($existing_attributes));
        $license_attribute->set_visible(true);
        $license_attribute->set_variation(true);
        $existing_attributes[$attribute_key] = $license_attribute;
    } else {
        $options = [];
        foreach ((array) $license_attribute->get_options() as $option) {
            $canonical_option = bw_import_canonicalize_license_value($option);
            if ($canonical_option === '') {
                continue;
            }

            $option_index = sanitize_title($canonical_option);
            if (!isset($options[$option_index])) {
                $options[$option_index] = $canonical_option;
            }
        }

        $variation_option_index = sanitize_title($variation_name);
        if (!isset($options[$variation_option_index])) {
            $options[$variation_option_index] = $variation_name;
        }

        $license_attribute->set_options(array_values($options));
        $license_attribute->set_visible(true);
        $license_attribute->set_variation(true);
        if ($license_attribute_index !== null) {
            $existing_attributes[$license_attribute_index] = $license_attribute;
        }
    }

    $parent_product->set_attributes($existing_attributes);
    return $attribute_key;
}

function bw_import_get_parent_attribute_option_value($parent_product, $attribute_key, $requested_value)
{
    if (!$parent_product || $attribute_key === '') {
        return '';
    }

    $requested_value = bw_import_canonicalize_license_value($requested_value);
    if ($requested_value === '') {
        return '';
    }

    foreach ((array) $parent_product->get_attributes() as $attribute) {
        if (!$attribute instanceof WC_Product_Attribute) {
            continue;
        }

        if (sanitize_title((string) $attribute->get_name()) !== $attribute_key) {
            continue;
        }

        foreach ((array) $attribute->get_options() as $option) {
            if (sanitize_title((string) $option) === sanitize_title($requested_value)) {
                return (string) $option;
            }
        }
    }
    return $requested_value;
}

function bw_import_maybe_apply_parent_default_variation($parent_product, $attribute_key, $default_variation)
{
    if (!$parent_product || $attribute_key === '') {
        return;
    }

    $default_variation = sanitize_title((string) $default_variation);
    if ($default_variation === '') {
        return;
    }

    $license_attribute = null;
    foreach ((array) $parent_product->get_attributes() as $attribute) {
        if ($attribute instanceof WC_Product_Attribute && sanitize_title((string) $attribute->get_name()) === $attribute_key) {
            $license_attribute = $attribute;
            break;
        }
    }

    if (!$license_attribute) {
        return;
    }

    foreach ((array) $license_attribute->get_options() as $option) {
        if (sanitize_title((string) $option) === $default_variation) {
            $existing_defaults = $parent_product->get_default_attributes();
            if (!is_array($existing_defaults)) {
                $existing_defaults = [];
            }

            $existing_defaults[$attribute_key] = (string) $option;
            $parent_product->set_default_attributes($existing_defaults);
            return;
        }
    }
}

function bw_import_sync_variable_parent($parent_id)
{
    $parent_id = absint($parent_id);
    if ($parent_id < 1) {
        return;
    }

    if (class_exists('WC_Product_Variable')) {
        WC_Product_Variable::sync($parent_id);
    }

    wc_delete_product_transients($parent_id);
}

function bw_import_build_variation_downloads($download_url, $download_name)
{
    $download_url = esc_url_raw((string) $download_url);
    if ($download_url === '') {
        return [];
    }

    $download_name = sanitize_text_field((string) $download_name);
    if ($download_name === '') {
        $path = wp_parse_url($download_url, PHP_URL_PATH);
        $download_name = $path ? basename((string) $path) : __('Download', 'bw');
    }

    return [
        md5($download_url) => [
            'name' => $download_name,
            'file' => $download_url,
        ],
    ];
}

function bw_import_persist_variation_attribute_value($variation_id, $attribute_key, $attribute_value, &$warnings = [])
{
    $variation_id = absint($variation_id);
    $attribute_key = sanitize_title((string) $attribute_key);
    $attribute_value = trim((string) $attribute_value);

    if ($variation_id < 1 || $attribute_key === '' || $attribute_value === '') {
        $warnings[] = __('Variation attribute assignment failed because the attribute key or value was empty.', 'bw');
        return false;
    }

    $meta_key = 'attribute_' . $attribute_key;
    update_post_meta($variation_id, $meta_key, $attribute_value);
    delete_post_meta($variation_id, 'attribute_pa_' . $attribute_key);
    clean_post_cache($variation_id);

    $stored_value = (string) get_post_meta($variation_id, $meta_key, true);
    if (sanitize_title($stored_value) !== sanitize_title($attribute_value)) {
        update_post_meta($variation_id, $meta_key, $attribute_value);
        clean_post_cache($variation_id);
        $stored_value = (string) get_post_meta($variation_id, $meta_key, true);
    }

    if (sanitize_title($stored_value) !== sanitize_title($attribute_value)) {
        $warnings[] = sprintf(
            /* translators: 1: attribute label, 2: expected value */
            __('Variation attribute %1$s could not be persisted as %2$s.', 'bw'),
            $attribute_key,
            $attribute_value
        );
        return false;
    }

    return true;
}

function bw_import_repair_digital_license_variations($parent_id, $variation_map, &$warnings = [])
{
    $parent_id = absint($parent_id);
    if ($parent_id < 1 || empty($variation_map) || !is_array($variation_map)) {
        return;
    }

    $parent_product = wc_get_product($parent_id);
    if (!$parent_product || !$parent_product->is_type('variable')) {
        $warnings[] = sprintf(__('Digital license repair skipped because parent product ID %d is not a variable product.', 'bw'), $parent_id);
        return;
    }

    $attribute_key = bw_import_build_license_attribute_key();
    $canonical_options = ['Commercial', 'Extended'];
    $attribute_objects = $parent_product->get_attributes();
    if (!is_array($attribute_objects)) {
        $attribute_objects = [];
    }

    $license_attribute = new WC_Product_Attribute();
    $license_attribute->set_id(0);
    $license_attribute->set_name('License');
    $license_attribute->set_options($canonical_options);
    $license_attribute->set_visible(true);
    $license_attribute->set_variation(true);
    $license_attribute->set_position(count($attribute_objects));

    $replaced = false;
    foreach ($attribute_objects as $index => $attribute) {
        if ($attribute instanceof WC_Product_Attribute && sanitize_title((string) $attribute->get_name()) === $attribute_key) {
            $license_attribute->set_position($attribute->get_position());
            $attribute_objects[$index] = $license_attribute;
            $replaced = true;
            break;
        }
    }

    if (!$replaced) {
        $attribute_objects[$attribute_key] = $license_attribute;
    }

    $parent_product->set_attributes($attribute_objects);
    bw_import_maybe_apply_parent_default_variation($parent_product, $attribute_key, 'commercial');

    try {
        $parent_product->save();
    } catch (Throwable $exception) {
        $warnings[] = $exception->getMessage();
        return;
    }

    foreach ($variation_map as $variation_id => $variation_name) {
        $variation_id = absint($variation_id);
        $variation_name = bw_import_canonicalize_license_value($variation_name);

        if ($variation_id < 1 || $variation_name === '') {
            $warnings[] = __('Variation license repair skipped because the variation ID or canonical variation_name was empty.', 'bw');
            continue;
        }

        $variation = wc_get_product($variation_id);
        if (!$variation || !$variation->is_type('variation')) {
            $warnings[] = sprintf(__('Digital license repair skipped because variation ID %d could not be loaded.', 'bw'), $variation_id);
            continue;
        }

        $variation->set_parent_id($parent_id);
        $variation->set_attributes([
            $attribute_key => $variation_name,
        ]);

        try {
            $variation->save();
        } catch (Throwable $exception) {
            $warnings[] = $exception->getMessage();
            continue;
        }

        bw_import_persist_variation_attribute_value($variation_id, $attribute_key, $variation_name, $warnings);
    }

    bw_import_sync_variable_parent($parent_id);
}

function bw_import_normalize_source_image_url_for_lookup($url)
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');
    return esc_url_raw($url);
}

function bw_import_get_source_image_url_hash($url)
{
    $normalized_url = bw_import_normalize_source_image_url_for_lookup($url);
    if ($normalized_url === '') {
        return '';
    }

    return hash('sha256', $normalized_url);
}

function bw_import_find_attachment_by_source_url($url)
{
    global $wpdb;
    global $bw_import_source_image_attachment_cache;

    if (!is_array($bw_import_source_image_attachment_cache)) {
        $bw_import_source_image_attachment_cache = [];
    }

    $normalized_url = bw_import_normalize_source_image_url_for_lookup($url);
    if ($normalized_url === '') {
        return 0;
    }

    $url_hash = bw_import_get_source_image_url_hash($normalized_url);
    if ($url_hash === '') {
        return 0;
    }

    if (array_key_exists($url_hash, $bw_import_source_image_attachment_cache)) {
        return (int) $bw_import_source_image_attachment_cache[$url_hash];
    }

    $attachment_ids = $wpdb->get_col(
        $wpdb->prepare(
            "
            SELECT posts.ID
            FROM {$wpdb->posts} AS posts
            INNER JOIN {$wpdb->postmeta} AS postmeta
                ON posts.ID = postmeta.post_id
            WHERE posts.post_type = 'attachment'
              AND posts.post_status <> 'trash'
              AND postmeta.meta_key = '_bw_source_image_url_hash'
              AND postmeta.meta_value = %s
            ORDER BY posts.ID DESC
            ",
            $url_hash
        )
    );

    if (empty($attachment_ids)) {
        $bw_import_source_image_attachment_cache[$url_hash] = 0;
        return 0;
    }

    foreach (array_map('absint', (array) $attachment_ids) as $attachment_id) {
        if ($attachment_id < 1) {
            continue;
        }

        $stored_url = bw_import_normalize_source_image_url_for_lookup(get_post_meta($attachment_id, '_bw_source_image_url', true));
        if ($stored_url === $normalized_url) {
            $bw_import_source_image_attachment_cache[$url_hash] = $attachment_id;
            return $attachment_id;
        }
    }

    $bw_import_source_image_attachment_cache[$url_hash] = 0;
    return 0;
}

function bw_import_store_attachment_source_url($attachment_id, $url)
{
    global $bw_import_source_image_attachment_cache;

    if (!is_array($bw_import_source_image_attachment_cache)) {
        $bw_import_source_image_attachment_cache = [];
    }

    $attachment_id = absint($attachment_id);
    if ($attachment_id < 1) {
        return;
    }

    $normalized_url = bw_import_normalize_source_image_url_for_lookup($url);
    $url_hash = bw_import_get_source_image_url_hash($normalized_url);
    if ($normalized_url === '' || $url_hash === '') {
        return;
    }

    update_post_meta($attachment_id, '_bw_source_image_url', $normalized_url);
    update_post_meta($attachment_id, '_bw_source_image_url_hash', $url_hash);
    $bw_import_source_image_attachment_cache[$url_hash] = $attachment_id;
}

function bw_import_save_variation_from_row($data, $update_existing = false, $options = [])
{
    $options = wp_parse_args(
        $options,
        [
            'skip_images' => true,
        ]
    );

    $parent_sku = isset($data['parent_sku']) ? sanitize_text_field((string) $data['parent_sku']) : '';
    $variation_sku = isset($data['product']['sku']) ? sanitize_text_field((string) $data['product']['sku']) : '';
    $variation_name = isset($data['variation']['name']) ? bw_import_canonicalize_license_value(sanitize_text_field((string) $data['variation']['name'])) : '';

    if ($parent_sku === '' || $variation_sku === '' || $variation_name === '') {
        return new WP_Error('bw_import_invalid_variation_row', __('Variation rows require parent_sku, sku, and variation_name.', 'bw'));
    }

    $parent_id = bw_import_find_product_id_by_sku($parent_sku, ['product']);
    if ($parent_id < 1) {
        return new WP_Error('bw_import_missing_parent_match', sprintf(__('Variation SKU %1$s skipped because parent SKU %2$s was not found.', 'bw'), $variation_sku, $parent_sku));
    }

    $parent_product = wc_get_product($parent_id);
    if (!$parent_product) {
        return new WP_Error('bw_import_missing_parent_match', sprintf(__('Variation SKU %1$s skipped because parent SKU %2$s could not be loaded.', 'bw'), $variation_sku, $parent_sku));
    }

    $parent_product = bw_import_maybe_convert_product_type($parent_product, 'variable');
    if (!$parent_product || !$parent_product->is_type('variable')) {
        return new WP_Error('bw_import_missing_parent_match', sprintf(__('Variation SKU %1$s skipped because parent SKU %2$s is not a variable product.', 'bw'), $variation_sku, $parent_sku));
    }

    $status = 'created';
    $warnings = [];
    $variation_id = bw_import_find_variation_by_sku($variation_sku, $parent_product->get_id());
    $variation = $variation_id > 0 ? wc_get_product($variation_id) : null;
    $sku_owner_id = bw_import_find_product_id_by_sku($variation_sku, ['product', 'product_variation']);
    $sku_owner_type = $sku_owner_id > 0 ? get_post_type($sku_owner_id) : '';

    if ($variation) {
        if (!$update_existing) {
            return new WP_Error(
                'bw_import_existing_sku_requires_update',
                bw_import_format_existing_sku_requires_update_message($variation_sku, __('Variation', 'bw'))
            );
        }
        $status = 'updated';
    } elseif ($sku_owner_id > 0) {
        $conflict_label = $sku_owner_type === 'product_variation' ? __('variation', 'bw') : __('product', 'bw');
        return new WP_Error(
            'bw_import_sku_conflict',
            sprintf(
                /* translators: 1: SKU, 2: conflicting object type, 3: conflicting ID */
                __('SKU %1$s is already assigned to %2$s ID %3$d.', 'bw'),
                $variation_sku,
                $conflict_label,
                $sku_owner_id
            )
        );
    } elseif ($update_existing) {
        return new WP_Error('bw_import_missing_parent_match', sprintf(__('Variation SKU %1$s skipped because it does not exist and update-only mode is enabled.', 'bw'), $variation_sku));
    } else {
        $variation = new WC_Product_Variation();
        $variation->set_parent_id($parent_product->get_id());
    }

    if (!$variation) {
        return new WP_Error('bw_import_product_object', __('Unable to initialize variation product object.', 'bw'));
    }

    $variation_sku_assignment = bw_import_assign_sku_to_object($variation, $variation_sku);
    if (is_wp_error($variation_sku_assignment)) {
        return $variation_sku_assignment;
    }

    $attribute_key = bw_import_ensure_parent_license_attribute($parent_product, $variation_name);
    if ($attribute_key === '') {
        return new WP_Error('bw_import_invalid_variation_row', __('Unable to prepare the parent License attribute for variation import.', 'bw'));
    }

    try {
        $parent_product->save();
    } catch (Throwable $exception) {
        return new WP_Error('bw_import_save', $exception->getMessage());
    }

    $variation_attribute_value = bw_import_get_parent_attribute_option_value($parent_product, $attribute_key, $variation_name);
    $variation->set_parent_id($parent_product->get_id());
    $variation->set_attributes([$attribute_key => $variation_attribute_value]);
    $variation->set_status(!empty($data['variation']['enabled']) ? 'publish' : 'private');
    $variation->set_virtual(!empty($data['variation']['virtual']));
    $variation->set_downloadable(!empty($data['variation']['downloadable']));

    if (isset($data['variation']['regular_price'])) {
        $variation->set_regular_price($data['variation']['regular_price']);
    }

    if (isset($data['variation']['sale_price'])) {
        $variation->set_sale_price($data['variation']['sale_price']);
    }

    if (!empty($data['variation']['downloadable']) && !empty($data['variation']['download_url'])) {
        $variation->set_downloads(
            bw_import_build_variation_downloads(
                $data['variation']['download_url'],
                isset($data['variation']['download_name']) ? $data['variation']['download_name'] : ''
            )
        );
    } elseif (empty($data['variation']['downloadable'])) {
        $variation->set_downloads([]);
    } else {
        if (!empty($data['variation']['downloadable'])) {
            $warnings[] = __('Variation is marked downloadable but variation_download_url is empty.', 'bw');
        }
    }

    try {
        $variation_id = $variation->save();
    } catch (Throwable $exception) {
        return new WP_Error('bw_import_save', $exception->getMessage());
    }

    if ($variation_id > 0 && $variation_attribute_value !== '') {
        bw_import_persist_variation_attribute_value($variation_id, $attribute_key, $variation_attribute_value, $warnings);
    } else {
        $warnings[] = __('Variation License attribute value was empty after save.', 'bw');
    }

    if (!empty($options['skip_images'])) {
        if (!empty($data['variation']['image'])) {
            $warnings[] = __('Variation image skipped by configuration.', 'bw');
        }
    } elseif (!empty($data['variation']['image'])) {
        $variation_image_error = '';
        $image_id = bw_import_handle_image($data['variation']['image'], $variation_id, $variation_image_error);
        if ($image_id) {
            $variation->set_image_id($image_id);
            try {
                $variation->save();
            } catch (Throwable $exception) {
                $warnings[] = $exception->getMessage();
            }
        } else {
            $warnings[] = sprintf(
                /* translators: 1: image URL, 2: error detail */
                __('Variation image sideload failed: %1$s (%2$s)', 'bw'),
                esc_url_raw($data['variation']['image']),
                $variation_image_error !== '' ? $variation_image_error : __('unknown error', 'bw')
            );
        }
    }

    $default_variation = isset($data['product']['default_variation']) ? $data['product']['default_variation'] : '';
    if ($default_variation === '') {
        $default_variation = get_post_meta($parent_product->get_id(), '_bw_import_default_variation', true);
    }
    bw_import_maybe_apply_parent_default_variation($parent_product, $attribute_key, $default_variation);

    try {
        $parent_product->save();
    } catch (Throwable $exception) {
        $warnings[] = $exception->getMessage();
    }

    if ($default_variation !== '') {
        delete_post_meta($parent_product->get_id(), '_bw_import_default_variation');
    }

    bw_import_sync_variable_parent($parent_product->get_id());

    return [
        'entity_type' => 'variation',
        'status' => $status,
        'parent_id' => $parent_product->get_id(),
        'variation_id' => $variation_id,
        'warnings' => $warnings,
    ];
}

/**
 * Recupera ID prodotto da ID o SKU.
 *
 * @param array $references Elenco di riferimenti.
 *
 * @return array
 */
function bw_import_locate_product_ids($references)
{
    $ids = [];
    foreach ($references as $reference) {
        $reference = trim($reference);
        if (is_numeric($reference)) {
            $ids[] = (int) $reference;
            continue;
        }

        $maybe_id = wc_get_product_id_by_sku($reference);
        if ($maybe_id) {
            $ids[] = $maybe_id;
        }
    }

    return $ids;
}

/**
 * Imposta termini su tassonomie prodotto.
 *
 * @param int    $product_id ID prodotto.
 * @param array  $terms      Elenco termini.
 * @param string $taxonomy   Tassonomia.
 */
function bw_import_assign_terms($product_id, $terms, $taxonomy)
{
    $term_ids = [];
    foreach ($terms as $term) {
        $existing = term_exists($term, $taxonomy);
        if ($existing && !is_wp_error($existing)) {
            $term_ids[] = (int) $existing['term_id'];
        } else {
            $created = wp_insert_term($term, $taxonomy);
            if (!is_wp_error($created)) {
                $term_ids[] = (int) $created['term_id'];
            }
        }
    }

    if (!empty($term_ids)) {
        wp_set_object_terms($product_id, $term_ids, $taxonomy, false);
    }
}

/**
 * Resolve term IDs for a taxonomy, creating missing terms when possible.
 *
 * @param array  $terms    Terms list.
 * @param string $taxonomy Taxonomy slug.
 *
 * @return array
 */
function bw_import_resolve_term_ids($terms, $taxonomy)
{
    $term_ids = [];
    foreach ((array) $terms as $term) {
        $existing = term_exists($term, $taxonomy);
        if ($existing && !is_wp_error($existing)) {
            $term_ids[] = (int) $existing['term_id'];
            continue;
        }

        $created = wp_insert_term($term, $taxonomy);
        if (!is_wp_error($created) && !empty($created['term_id'])) {
            $term_ids[] = (int) $created['term_id'];
        }
    }

    return $term_ids;
}

/**
 * Gestisce il download e l'associazione di immagini da URL.
 *
 * @param string $image_url  URL immagine.
 * @param int    $product_id ID prodotto.
 *
 * @return int Attachment ID.
 */
function bw_import_handle_image($image_url, $product_id, &$error_message = '')
{
    $error_message = '';
    $normalized_image_url = bw_import_normalize_source_image_url_for_lookup($image_url);
    if ($normalized_image_url === '') {
        return 0;
    }

    if (!function_exists('media_sideload_image') || !function_exists('media_handle_sideload')) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    $image_id = bw_import_find_attachment_by_source_url($normalized_image_url);
    if ($image_id > 0) {
        return $image_id;
    }

    $image_id = attachment_url_to_postid($normalized_image_url);
    if ($image_id) {
        bw_import_store_attachment_source_url($image_id, $normalized_image_url);
        return $image_id;
    }

    $parsed_path = wp_parse_url($normalized_image_url, PHP_URL_PATH);
    $file_name = $parsed_path ? basename((string) $parsed_path) : '';
    if ($file_name === '') {
        $file_name = 'blackwork-import-image';
    }

    $timeout = (int) apply_filters('bw_import_image_timeout', 20, $normalized_image_url);
    if ($timeout < 5) {
        $timeout = 5;
    }

    $temporary_file = download_url($normalized_image_url, $timeout);
    if (is_wp_error($temporary_file)) {
        $error_message = $temporary_file->get_error_message();
        return 0;
    }

    $file_array = [
        'name' => sanitize_file_name($file_name),
        'tmp_name' => $temporary_file,
    ];

    $attachment_id = media_handle_sideload($file_array, absint($product_id));
    if (is_wp_error($attachment_id)) {
        $error_message = $attachment_id->get_error_message();
        if (!empty($file_array['tmp_name']) && file_exists($file_array['tmp_name'])) {
            @unlink($file_array['tmp_name']);
        }
        return 0;
    }

    bw_import_store_attachment_source_url((int) $attachment_id, $normalized_image_url);

    return (int) $attachment_id;
}

/**
 * Applica attributi globali al prodotto.
 *
 * @param int   $product_id ID prodotto.
 * @param array $attributes Attributi.
 */
function bw_import_apply_attributes($product_id, $attributes)
{
    $product_attributes = [];

    foreach ($attributes as $taxonomy => $value) {
        $terms = bw_import_explode_list($value);
        if (empty($terms)) {
            continue;
        }

        if (!taxonomy_exists($taxonomy)) {
            continue;
        }

        $term_ids = [];
        foreach ($terms as $term) {
            $existing = term_exists($term, $taxonomy);
            if ($existing && !is_wp_error($existing)) {
                $term_ids[] = (int) $existing['term_id'];
            } else {
                $inserted = wp_insert_term($term, $taxonomy);
                if (!is_wp_error($inserted)) {
                    $term_ids[] = (int) $inserted['term_id'];
                }
            }
        }

        if (!empty($term_ids)) {
            wp_set_object_terms($product_id, $term_ids, $taxonomy, false);
        }

        $attribute = new WC_Product_Attribute();
        $attribute->set_id(wc_attribute_taxonomy_id_by_name($taxonomy));
        $attribute->set_name($taxonomy);
        $attribute->set_options($term_ids);
        $attribute->set_visible(true);
        $attribute->set_variation(false);
        $product_attributes[$taxonomy] = $attribute;
    }

    if (!empty($product_attributes)) {
        $product = wc_get_product($product_id);
        if ($product) {
            $product->set_attributes($product_attributes);
            $product->save();
        }
    }
}

/**
 * Build product attributes array without saving the product.
 *
 * @param array $attributes Raw attributes map.
 *
 * @return array
 */
function bw_import_build_attributes($attributes)
{
    $product_attributes = [];

    foreach ((array) $attributes as $taxonomy => $value) {
        $terms = bw_import_explode_list($value);
        if (empty($terms) || !taxonomy_exists($taxonomy)) {
            continue;
        }

        $term_ids = bw_import_resolve_term_ids($terms, $taxonomy);
        if (empty($term_ids)) {
            continue;
        }

        $attribute = new WC_Product_Attribute();
        $attribute->set_id(wc_attribute_taxonomy_id_by_name($taxonomy));
        $attribute->set_name($taxonomy);
        $attribute->set_options($term_ids);
        $attribute->set_visible(true);
        $attribute->set_variation(false);
        $product_attributes[$taxonomy] = $attribute;
    }

    return $product_attributes;
}

/**
 * Find mapped CSV header for a target field.
 *
 * @param array  $headers Headers list.
 * @param array  $mapping Mapping array.
 * @param string $target  Mapping target.
 *
 * @return string
 */
function bw_import_find_mapped_header($headers, $mapping, $target)
{
    foreach ((array) $headers as $header) {
        if (isset($mapping[$header]) && $mapping[$header] === $target) {
            return (string) $header;
        }
    }

    return '';
}

/**
 * Validate duplicate SKU rows before any write operation.
 *
 * @param string $file_path CSV path.
 * @param array  $headers   CSV headers.
 * @param array  $mapping   Mapping snapshot.
 *
 * @return true|WP_Error
 */
function bw_import_validate_unique_skus($file_path, $headers, $mapping)
{
    $sku_header = bw_import_find_mapped_header($headers, $mapping, 'sku');
    if ($sku_header === '') {
        return new WP_Error('bw_import_missing_sku_mapping', __('SKU mapping is required to run the import.', 'bw'));
    }

    $sku_column_index = array_search($sku_header, (array) $headers, true);
    if ($sku_column_index === false) {
        return new WP_Error('bw_import_missing_sku_column', __('Mapped SKU column was not found in CSV headers.', 'bw'));
    }

    $seen_skus = [];
    $cursor = 0;
    $chunk_size = bw_import_chunk_size();
    $duplicate_examples = [];

    while (true) {
        $chunk = bw_import_parse_csv_chunk($file_path, $cursor, $chunk_size);
        if (is_wp_error($chunk)) {
            return $chunk;
        }

        foreach ($chunk['rows'] as $row) {
            $raw_sku = isset($row[$sku_column_index]) ? $row[$sku_column_index] : '';
            $sku = sanitize_text_field(trim((string) $raw_sku));
            if ($sku === '') {
                continue;
            }

            if (isset($seen_skus[$sku])) {
                $duplicate_examples[] = $sku;
                if (count($duplicate_examples) >= 10) {
                    break 2;
                }
            } else {
                $seen_skus[$sku] = true;
            }
        }

        if (!empty($chunk['eof'])) {
            break;
        }
        $cursor = (int) $chunk['next_row'];
    }

    if (!empty($duplicate_examples)) {
        return new WP_Error(
            'bw_import_duplicate_sku',
            sprintf(
                /* translators: %s: duplicate SKU list */
                __('Duplicate SKU values found in CSV. Import aborted before writes. Examples: %s', 'bw'),
                implode(', ', array_unique($duplicate_examples))
            )
        );
    }

    return true;
}

/**
 * Merge and bound user-visible importer messages.
 *
 * @param array $existing Existing list.
 * @param array $incoming New list.
 * @param int   $limit    Max retained messages.
 *
 * @return array
 */
function bw_import_merge_bounded_messages($existing, $incoming, $limit = 20)
{
    $merged = array_merge((array) $existing, (array) $incoming);
    if (count($merged) <= $limit) {
        return $merged;
    }

    return array_slice($merged, -1 * absint($limit));
}
