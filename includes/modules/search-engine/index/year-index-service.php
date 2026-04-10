<?php
if (!defined('ABSPATH')) {
    exit;
}

function bw_fpw_get_year_index_transient_key($context_slug)
{
    $normalized = bw_fpw_normalize_context_slug($context_slug);
    return 'bw_fpw_year_index_' . ($normalized ?: 'unknown');
}

function bw_fpw_get_year_postmap_transient_key($context_slug)
{
    $normalized = bw_fpw_normalize_context_slug($context_slug);
    return 'bw_fpw_year_postmap_' . ($normalized ?: 'unknown');
}

function bw_fpw_get_year_postmap($context_slug)
{
    $context_slug = bw_fpw_normalize_context_slug($context_slug);

    if (!bw_fpw_is_supported_context_slug($context_slug)) {
        return [];
    }

    $cached = get_transient(bw_fpw_get_year_postmap_transient_key($context_slug));
    if (is_array($cached)) {
        return $cached;
    }

    bw_fpw_get_year_index($context_slug);

    $cached = get_transient(bw_fpw_get_year_postmap_transient_key($context_slug));
    return is_array($cached) ? $cached : [];
}

function bw_fpw_clear_year_index_transients($context_slug = '')
{
    global $wpdb;

    $slugs_to_clear = array_values(
        array_unique(
            array_filter(
                array_map('bw_fpw_normalize_context_slug', (array) $context_slug),
                'strlen'
            )
        )
    );

    if (!empty($slugs_to_clear)) {
        foreach ($slugs_to_clear as $normalized) {
            delete_transient(bw_fpw_get_year_index_transient_key($normalized));
            delete_transient(bw_fpw_get_year_postmap_transient_key($normalized));
        }

        return;
    }

    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_bw_fpw_year_index_%'
            OR option_name LIKE '_transient_timeout_bw_fpw_year_index_%'
            OR option_name LIKE '_transient_bw_fpw_year_postmap_%'
            OR option_name LIKE '_transient_timeout_bw_fpw_year_postmap_%'"
    );
}

function bw_fpw_build_year_quick_ranges($years_map)
{
    if (!is_array($years_map) || empty($years_map)) {
        return [];
    }

    $years = array_keys($years_map);
    $years = array_map('intval', $years);
    sort($years, SORT_NUMERIC);

    $distinct_years = array_values(array_unique($years));
    $total = count($distinct_years);
    if (0 === $total) {
        return [];
    }

    if ($total < 6) {
        return [];
    }

    $bucket_count = min(4, $total);
    $bucket_size = (int) ceil($total / $bucket_count);
    $chunks = array_chunk($distinct_years, max(1, $bucket_size));
    $ranges = [];

    if (count($chunks) > 1) {
        $last_index = count($chunks) - 1;

        if (isset($chunks[$last_index]) && count($chunks[$last_index]) < 2) {
            $chunks[$last_index - 1] = array_merge($chunks[$last_index - 1], $chunks[$last_index]);
            unset($chunks[$last_index]);
            $chunks = array_values($chunks);
        }
    }

    foreach ($chunks as $chunk) {
        if (empty($chunk)) {
            continue;
        }

        $from = (int) reset($chunk);
        $to = (int) end($chunk);

        $ranges[] = [
            'key' => $from . '-' . $to,
            'label' => $from === $to ? (string) $from : $from . '–' . $to,
            'from' => $from,
            'to' => $to,
        ];
    }

    return $ranges;
}

function bw_fpw_build_year_index($context_slug)
{
    $context_slug = bw_fpw_normalize_context_slug($context_slug);
    if (!bw_fpw_is_supported_context_slug($context_slug)) {
        return [
            'context' => $context_slug ?: 'mixed',
            'supported' => false,
            'min_year' => null,
            'max_year' => null,
            'years' => [],
            'quick_ranges' => [],
        ];
    }

    $root_term_id = bw_fpw_get_context_root_term_id($context_slug);
    if ($root_term_id <= 0) {
        return [
            'context' => $context_slug,
            'supported' => false,
            'min_year' => null,
            'max_year' => null,
            'years' => [],
            'quick_ranges' => [],
        ];
    }

    global $wpdb;

    $child_ids = get_term_children($root_term_id, 'product_cat');
    $all_term_ids = array_merge([$root_term_id], is_array($child_ids) ? array_map('absint', $child_ids) : []);
    $term_ids_in = implode(',', $all_term_ids);

    $canonical_year_key = bw_fpw_get_canonical_year_meta_key();

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT DISTINCT p.ID, pm.meta_value AS year_value
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
             INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
             LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
             WHERE p.post_type = 'product'
               AND p.post_status = 'publish'
               AND tt.taxonomy = 'product_cat'
               AND tt.term_id IN ({$term_ids_in})",
            $canonical_year_key
        )
    );

    $years = [];
    $post_map = [];

    foreach ((array) $rows as $row) {
        $year = bw_fpw_extract_year_int($row->year_value);
        if (null === $year) {
            continue;
        }

        if (!isset($years[$year])) {
            $years[$year] = 0;
        }

        $years[$year]++;
        $post_map[(int) $row->ID] = $year;
    }

    if (!empty($years)) {
        ksort($years, SORT_NUMERIC);
    }

    set_transient(
        bw_fpw_get_year_postmap_transient_key($context_slug),
        $post_map,
        30 * MINUTE_IN_SECONDS
    );

    $year_keys = array_keys($years);
    $min_year = !empty($year_keys) ? (int) reset($year_keys) : null;
    $max_year = !empty($year_keys) ? (int) end($year_keys) : null;

    return [
        'context' => $context_slug,
        'supported' => !empty($years),
        'min_year' => $min_year,
        'max_year' => $max_year,
        'years' => $years,
        'quick_ranges' => bw_fpw_build_year_quick_ranges($years),
    ];
}

function bw_fpw_get_year_index($context_slug)
{
    $context_slug = bw_fpw_normalize_context_slug($context_slug);
    if (!bw_fpw_is_supported_context_slug($context_slug)) {
        return [
            'context' => $context_slug ?: 'mixed',
            'supported' => false,
            'min_year' => null,
            'max_year' => null,
            'years' => [],
            'quick_ranges' => [],
        ];
    }

    $transient_key = bw_fpw_get_year_index_transient_key($context_slug);

    return bw_fpw_get_cached_index_with_lock(
        $transient_key,
        'year_index',
        $context_slug,
        static function () use ($context_slug) {
            return bw_fpw_build_year_index($context_slug);
        },
        bw_fpw_get_year_index_cache_ttl()
    );
}

function bw_fpw_get_year_filter_ui($context_slug)
{
    $index = bw_fpw_get_year_index($context_slug);

    return [
        'supported' => !empty($index['supported']),
        'context' => isset($index['context']) ? (string) $index['context'] : 'mixed',
        'min' => isset($index['min_year']) ? $index['min_year'] : null,
        'max' => isset($index['max_year']) ? $index['max_year'] : null,
        'quick_ranges' => isset($index['quick_ranges']) && is_array($index['quick_ranges']) ? array_values($index['quick_ranges']) : [],
    ];
}
