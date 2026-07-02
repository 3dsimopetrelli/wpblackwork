<?php
/**
 * Blackwork Site Settings — import run-state, locking, and session-state helpers.
 *
 * Extracted verbatim from class-blackwork-site-settings.php (Phase 3a, BW-TASK-20260629). Function bodies are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Chunk size for importer run steps.
 *
 * @return int
 */
function bw_import_chunk_size()
{
    return (int) apply_filters('bw_import_chunk_size', 50);
}

/**
 * Percorso di upload personalizzato per i CSV dell'importer.
 *
 * @param array $dirs Directory upload corrente.
 *
 * @return array
 */
function bw_import_upload_dir($dirs)
{
    $dirs['subdir'] = '/blackwork-import';
    $dirs['path'] = $dirs['basedir'] . $dirs['subdir'];
    $dirs['url'] = $dirs['baseurl'] . $dirs['subdir'];
    return $dirs;
}

/**
 * Option keys and helpers for durable import runtime state.
 */
function bw_import_lock_option_key()
{
    return 'bw_import_run_lock';
}

function bw_import_active_run_option_key()
{
    return 'bw_import_active_run';
}

function bw_import_run_option_key($run_id)
{
    return 'bw_import_run_' . sanitize_key((string) $run_id);
}

function bw_import_generate_run_id()
{
    if (function_exists('wp_generate_uuid4')) {
        return sanitize_key(str_replace('-', '', wp_generate_uuid4()));
    }

    return sanitize_key(uniqid('bwimp_', true));
}

function bw_import_file_fingerprint($file_path)
{
    if (!is_string($file_path) || $file_path === '' || !file_exists($file_path)) {
        return '';
    }

    $sha1 = @sha1_file($file_path);
    if (is_string($sha1) && $sha1 !== '') {
        return $sha1;
    }

    return md5($file_path . '|' . filesize($file_path) . '|' . filemtime($file_path));
}

function bw_import_get_active_run_id()
{
    $run_id = get_option(bw_import_active_run_option_key(), '');
    return is_string($run_id) ? sanitize_text_field($run_id) : '';
}

function bw_import_set_active_run_id($run_id)
{
    $run_id = sanitize_text_field((string) $run_id);

    if ($run_id === '') {
        delete_option(bw_import_active_run_option_key());
        return;
    }

    update_option(bw_import_active_run_option_key(), $run_id, false);
}

function bw_import_get_run_state($run_id)
{
    $run_id = sanitize_text_field((string) $run_id);
    if ($run_id === '') {
        return [];
    }

    $state = get_option(bw_import_run_option_key($run_id), []);
    if (!is_array($state)) {
        return [];
    }

    if (!isset($state['processed_row_keys']) || !is_array($state['processed_row_keys'])) {
        $state['processed_row_keys'] = [];
    }

    if (!empty($state['row_outcomes']) && is_array($state['row_outcomes'])) {
        foreach (array_keys($state['row_outcomes']) as $row_key) {
            $row_key = sanitize_text_field((string) $row_key);
            if ($row_key !== '' && !isset($state['processed_row_keys'][$row_key])) {
                $state['processed_row_keys'][$row_key] = true;
            }
        }
    }

    return $state;
}

function bw_import_sync_totals_from_counters($run_state)
{
    $run_state = is_array($run_state) ? $run_state : [];
    $counters = isset($run_state['counters']) && is_array($run_state['counters']) ? $run_state['counters'] : [];

    $created = isset($counters['created']) ? absint($counters['created']) : 0;
    $updated = isset($counters['updated']) ? absint($counters['updated']) : 0;
    $skipped = isset($counters['skipped']) ? absint($counters['skipped']) : 0;
    $failed = isset($counters['failed']) ? absint($counters['failed']) : 0;

    $run_state['counters'] = [
        'created' => $created,
        'updated' => $updated,
        'skipped' => $skipped,
        'failed' => $failed,
    ];
    $run_state['totals'] = [
        'created' => $created,
        'updated' => $updated,
        'skipped' => $skipped,
        'errors' => $failed,
        'failed' => $failed,
    ];

    return $run_state;
}

function bw_import_save_run_state($run_state)
{
    if (!is_array($run_state) || empty($run_state['run_id'])) {
        return;
    }

    $run_state = bw_import_sync_totals_from_counters($run_state);
    $run_state['updated_at'] = time();
    if (!isset($run_state['processed_row_keys']) || !is_array($run_state['processed_row_keys'])) {
        $run_state['processed_row_keys'] = [];
    }

    update_option(bw_import_run_option_key($run_state['run_id']), $run_state, false);
}

function bw_import_lock_compare_and_swap($expected_lock, $new_lock)
{
    global $wpdb;

    $option_name = bw_import_lock_option_key();
    $expected = is_array($expected_lock) ? $expected_lock : [];
    $replacement = is_array($new_lock) ? $new_lock : [];

    if (empty($expected)) {
        return add_option($option_name, $replacement, '', false);
    }

    $table = $wpdb->options;
    $updated = $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$table} SET option_value = %s WHERE option_name = %s AND option_value = %s",
            maybe_serialize($replacement),
            $option_name,
            maybe_serialize($expected)
        )
    );

    return 1 === (int) $updated;
}

function bw_import_lock_delete_if_matches($expected_lock)
{
    global $wpdb;

    $expected = is_array($expected_lock) ? $expected_lock : [];
    if (empty($expected)) {
        return false;
    }

    $option_name = bw_import_lock_option_key();
    $table = $wpdb->options;
    $deleted = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$table} WHERE option_name = %s AND option_value = %s",
            $option_name,
            maybe_serialize($expected)
        )
    );

    return 1 === (int) $deleted;
}

function bw_import_clear_run_state($run_id)
{
    $run_id = sanitize_text_field((string) $run_id);
    if ($run_id === '') {
        return;
    }

    delete_option(bw_import_run_option_key($run_id));
    if (bw_import_get_active_run_id() === $run_id) {
        bw_import_set_active_run_id('');
    }
}

function bw_import_lock_ttl()
{
    return (int) apply_filters('bw_import_lock_ttl', 15 * MINUTE_IN_SECONDS);
}

function bw_import_get_active_lock()
{
    $lock = bw_import_get_lock_payload();
    if (empty($lock)) {
        return [];
    }

    $now = time();
    $lock['run_id'] = isset($lock['run_id']) ? sanitize_text_field((string) $lock['run_id']) : '';
    $lock['owner_user_id'] = isset($lock['owner_user_id']) ? absint($lock['owner_user_id']) : 0;
    $lock['acquired_at'] = isset($lock['acquired_at']) ? (int) $lock['acquired_at'] : 0;
    $lock['heartbeat_at'] = isset($lock['heartbeat_at']) ? (int) $lock['heartbeat_at'] : 0;
    $lock['expires_at'] = isset($lock['expires_at']) ? (int) $lock['expires_at'] : 0;
    $lock['age_seconds'] = $lock['acquired_at'] > 0 ? max(0, $now - $lock['acquired_at']) : 0;
    $lock['expires_in'] = $lock['expires_at'] > 0 ? ($lock['expires_at'] - $now) : 0;
    $lock['is_stale'] = bw_import_is_lock_stale($lock);

    return $lock;
}

function bw_import_clear_lock($clear_run_state = true)
{
    $lock = bw_import_get_active_lock();
    $run_id = !empty($lock['run_id']) ? sanitize_text_field((string) $lock['run_id']) : '';

    delete_option(bw_import_lock_option_key());

    if ($run_id !== '') {
        if ($clear_run_state) {
            bw_import_clear_run_state($run_id);
        } elseif (bw_import_get_active_run_id() === $run_id) {
            bw_import_set_active_run_id('');
        }
    } else {
        bw_import_set_active_run_id('');
    }

    return $lock;
}

function bw_import_is_lock_stale($lock)
{
    if (!is_array($lock) || empty($lock['expires_at'])) {
        return true;
    }

    return (int) $lock['expires_at'] <= time();
}

function bw_import_acquire_lock($run_id, $user_id)
{
    $run_id = sanitize_text_field((string) $run_id);
    $user_id = absint($user_id);
    $attempts = 0;

    while ($attempts < 3) {
        $attempts++;
        $existing_lock = bw_import_get_lock_payload();
        $stale = bw_import_is_lock_stale($existing_lock);

        if (!empty($existing_lock) && !$stale) {
            $locked_run = isset($existing_lock['run_id']) ? sanitize_text_field((string) $existing_lock['run_id']) : '';
            $locked_owner = isset($existing_lock['owner_user_id']) ? absint($existing_lock['owner_user_id']) : 0;

            if (!($locked_run === $run_id && $locked_owner === $user_id)) {
                return [
                    'ok' => false,
                    'message' => sprintf(
                        /* translators: 1: user id, 2: run id */
                        __('Another import run is active (owner user ID: %1$d, run: %2$s). Please retry later.', 'bw'),
                        $locked_owner,
                        $locked_run !== '' ? $locked_run : 'n/a'
                    ),
                ];
            }
        }

        $now = time();
        $lock_payload = [
            'run_id' => $run_id,
            'owner_user_id' => $user_id,
            'acquired_at' => $now,
            'expires_at' => $now + bw_import_lock_ttl(),
            'heartbeat_at' => $now,
        ];

        if (bw_import_lock_compare_and_swap($existing_lock, $lock_payload)) {
            $response = ['ok' => true];
            if ($stale && !empty($existing_lock)) {
                $response['warning'] = __('A stale import lock was reclaimed before continuing.', 'bw');
            }

            return $response;
        }
    }

    return [
        'ok' => false,
        'message' => __('Unable to acquire import lock safely due to concurrent updates. Please retry.', 'bw'),
    ];
}

function bw_import_refresh_lock($run_id, $user_id)
{
    $run_id = sanitize_text_field((string) $run_id);
    $user_id = absint($user_id);
    $lock = bw_import_get_lock_payload();

    if (
        empty($lock) ||
        (isset($lock['run_id']) ? sanitize_text_field((string) $lock['run_id']) : '') !== $run_id ||
        (isset($lock['owner_user_id']) ? absint($lock['owner_user_id']) : 0) !== $user_id
    ) {
        return false;
    }

    $now = time();
    $updated_lock = $lock;
    $updated_lock['heartbeat_at'] = $now;
    $updated_lock['expires_at'] = $now + bw_import_lock_ttl();
    return bw_import_lock_compare_and_swap($lock, $updated_lock);
}

function bw_import_release_lock($run_id, $user_id, $force = false)
{
    $run_id = sanitize_text_field((string) $run_id);
    $user_id = absint($user_id);
    $lock = bw_import_get_lock_payload();
    if (empty($lock)) {
        return true;
    }

    $locked_run = isset($lock['run_id']) ? sanitize_text_field((string) $lock['run_id']) : '';
    $locked_owner = isset($lock['owner_user_id']) ? absint($lock['owner_user_id']) : 0;

    if ($force) {
        delete_option(bw_import_lock_option_key());
        return true;
    }

    if ($locked_run === $run_id && ($locked_owner === $user_id || bw_import_is_lock_stale($lock))) {
        if (bw_import_lock_delete_if_matches($lock)) {
            return true;
        }

        $latest = bw_import_get_lock_payload();
        if (empty($latest)) {
            return true;
        }

        $latest_run = isset($latest['run_id']) ? sanitize_text_field((string) $latest['run_id']) : '';
        if ($latest_run === $run_id && bw_import_is_lock_stale($latest)) {
            delete_option(bw_import_lock_option_key());
            return true;
        }

        return false;
    }

    return false;
}

function bw_import_make_row_identity($row_offset, $row_index, $sku)
{
    $cursor = absint($row_offset + $row_index + 2);
    $sku_hash = md5(sanitize_text_field((string) $sku));
    return 'r' . $cursor . '_s' . $sku_hash;
}

function bw_import_record_row_outcome(&$run_state, $row_identity, $outcome, $message = '')
{
    $allowed_outcomes = ['created', 'updated', 'skipped', 'failed'];
    if (!in_array($outcome, $allowed_outcomes, true)) {
        return false;
    }

    if (!is_array($run_state)) {
        $run_state = [];
    }

    if (!isset($run_state['row_outcomes']) || !is_array($run_state['row_outcomes'])) {
        $run_state['row_outcomes'] = [];
    }
    if (!isset($run_state['row_outcome_order']) || !is_array($run_state['row_outcome_order'])) {
        $run_state['row_outcome_order'] = [];
    }
    if (!isset($run_state['counters']) || !is_array($run_state['counters'])) {
        $run_state['counters'] = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
    }
    if (!isset($run_state['processed_row_keys']) || !is_array($run_state['processed_row_keys'])) {
        $run_state['processed_row_keys'] = [];
    }

    if ($row_identity === '' || isset($run_state['processed_row_keys'][$row_identity])) {
        return false;
    }

    $run_state['processed_row_keys'][$row_identity] = true;
    $run_state['row_outcomes'][$row_identity] = [
        'outcome' => $outcome,
        'message' => sanitize_text_field((string) $message),
        'at' => time(),
    ];
    $run_state['row_outcome_order'][] = $row_identity;
    $run_state['counters'][$outcome] = isset($run_state['counters'][$outcome]) ? ((int) $run_state['counters'][$outcome] + 1) : 1;

    while (count($run_state['row_outcome_order']) > 500) {
        $oldest = array_shift($run_state['row_outcome_order']);
        if ($oldest !== null) {
            unset($run_state['row_outcomes'][$oldest]);
        }
    }

    $run_state = bw_import_sync_totals_from_counters($run_state);
    return true;
}

function bw_import_merge_detail_totals($existing, $delta)
{
    $keys = [
        'products_created',
        'products_updated',
        'variations_created',
        'variations_updated',
        'variations_skipped',
        'variations_failed',
    ];

    $merged = [];
    foreach ($keys as $key) {
        $merged[$key] = (int) (isset($existing[$key]) ? $existing[$key] : 0) + (int) (isset($delta[$key]) ? $delta[$key] : 0);
    }

    return $merged;
}

function bw_import_format_warning_summary($warnings, $limit = 5)
{
    $warnings = array_values(array_filter(array_map('sanitize_text_field', (array) $warnings)));
    if (empty($warnings)) {
        return '';
    }

    $limit = max(1, (int) $limit);
    $visible = array_slice($warnings, 0, $limit);
    $summary = sprintf(
        /* translators: %d: warning count */
        __('Warnings (%d):', 'bw'),
        count($warnings)
    ) . ' ' . implode(' | ', array_map('esc_html', $visible));

    $remaining = count($warnings) - count($visible);
    if ($remaining > 0) {
        $summary .= ' ' . sprintf(
            /* translators: %d: remaining warning count */
            __('(+%d more warnings)', 'bw'),
            $remaining
        );
    }

    return $summary;
}

/**
 * Salva stato import come transient UI mirror (non-authoritative).
 *
 * @param array $state Stato da salvare.
 */
function bw_import_save_state($state)
{
    set_transient('bw_import_state_' . get_current_user_id(), $state, HOUR_IN_SECONDS);
}

/**
 * Recupera lo stato import: preferisce run state durevole attivo.
 *
 * @return array
 */
function bw_import_get_state()
{
    $active_run_id = bw_import_get_active_run_id();
    if ($active_run_id !== '') {
        $run_state = bw_import_get_run_state($active_run_id);
        if (!empty($run_state)) {
            bw_import_save_state($run_state);
            return $run_state;
        }

        bw_import_set_active_run_id('');
    }

    $state = get_transient('bw_import_state_' . get_current_user_id());
    return is_array($state) ? $state : [];
}

/**
 * Pulisce lo stato di importazione.
 */
function bw_import_clear_state($run_id = '', $preserve_run_state = true)
{
    delete_transient('bw_import_state_' . get_current_user_id());

    if ($run_id !== '' && !$preserve_run_state) {
        bw_import_clear_run_state($run_id);
        return;
    }

    bw_import_set_active_run_id('');
}
