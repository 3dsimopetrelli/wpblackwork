<?php
if (!defined('ABSPATH')) {
    exit;
}

function bw_fpw_get_advanced_filter_index_transient_key($context_slug)
{
    $normalized = bw_fpw_normalize_context_slug($context_slug);
    $generation = bw_fpw_get_advanced_filter_index_generation($normalized);

    return 'bw_fpw_advanced_filter_index_' . ($normalized ?: 'unknown') . '_g' . $generation;
}

function bw_fpw_get_advanced_filter_index_generation($context_slug)
{
    $normalized = bw_fpw_normalize_context_slug($context_slug);
    $option_name = 'bw_fpw_advanced_filter_index_gen_' . ($normalized ?: 'unknown');

    return (int) get_option($option_name, 0);
}

function bw_fpw_bump_advanced_filter_index_generation($context_slugs)
{
    $slugs = array_unique(array_filter(array_map('bw_fpw_normalize_context_slug', (array) $context_slugs), 'strlen'));

    foreach ($slugs as $slug) {
        $option_name = 'bw_fpw_advanced_filter_index_gen_' . ($slug ?: 'unknown');
        update_option($option_name, (int) get_option($option_name, 0) + 1, false);
    }
}

function bw_fpw_clear_advanced_filter_index_transients($context_slug = '')
{
    $slugs_to_clear = empty($context_slug)
        ? bw_fpw_get_supported_product_family_slugs()
        : (array) $context_slug;

    foreach ($slugs_to_clear as $slug) {
        $key = bw_fpw_get_advanced_filter_index_transient_key($slug);
        delete_transient($key);
        delete_transient($key . '_fresh');
    }

    if (empty($context_slug)) {
        bw_fpw_bump_advanced_filter_index_generation($slugs_to_clear);
        return;
    }

    bw_fpw_bump_advanced_filter_index_generation($slugs_to_clear);
}

function bw_fpw_build_advanced_filter_index($context_slug)
{
    global $wpdb;

    $context_slug = bw_fpw_normalize_context_slug($context_slug);
    $supported_groups = bw_fpw_get_supported_advanced_filter_groups_for_context($context_slug);

    if (empty($supported_groups)) {
        return [
            'context' => $context_slug ?: 'mixed',
            'supported' => false,
            'post_ids' => [],
            'groups' => [],
        ];
    }

    $root_term_id = bw_fpw_get_context_root_term_id($context_slug);
    if ($root_term_id <= 0) {
        return [
            'context' => $context_slug,
            'supported' => false,
            'post_ids' => [],
            'groups' => [],
        ];
    }

    $meta_keys = [];
    foreach ($supported_groups as $group_key => $definition) {
        $canonical_key = isset($definition['canonical_key']) ? (string) $definition['canonical_key'] : '';
        if ('' !== $canonical_key) {
            $meta_keys[] = $canonical_key;
        }

        $meta_keys = array_merge($meta_keys, bw_fpw_get_candidate_source_meta_keys($context_slug, $group_key));
    }

    $meta_keys = array_values(array_unique(array_filter($meta_keys)));

    $child_ids = get_term_children($root_term_id, 'product_cat');
    $all_term_ids = array_merge([$root_term_id], is_array($child_ids) ? array_map('absint', $child_ids) : []);
    $term_ids_in = implode(',', $all_term_ids);

    if (!empty($meta_keys)) {
        $meta_placeholders = implode(',', array_fill(0, count($meta_keys), '%s'));
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT p.ID, pm.meta_key, pm.meta_value
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
                 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
                 LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key IN ({$meta_placeholders})
                 WHERE p.post_type = 'product'
                   AND p.post_status = 'publish'
                   AND tt.taxonomy = 'product_cat'
                   AND tt.term_id IN ({$term_ids_in})",
                $meta_keys
            )
        );
    } else {
        $rows = $wpdb->get_results(
            "SELECT DISTINCT p.ID, NULL AS meta_key, NULL AS meta_value
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
             INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
             WHERE p.post_type = 'product'
               AND p.post_status = 'publish'
               AND tt.taxonomy = 'product_cat'
               AND tt.term_id IN ({$term_ids_in})"
        );
    }

    $product_ids = [];
    $seen_ids = [];
    $meta_by_post = [];

    foreach ((array) $rows as $row) {
        $post_id = absint($row->ID);
        $meta_key = is_string($row->meta_key) && '' !== $row->meta_key ? $row->meta_key : null;

        if ($post_id <= 0) {
            continue;
        }

        if (!isset($seen_ids[$post_id])) {
            $seen_ids[$post_id] = true;
            $product_ids[] = $post_id;
        }

        if (null !== $meta_key) {
            $meta_by_post[$post_id][$meta_key] = isset($row->meta_value) ? (string) $row->meta_value : '';
        }
    }

    $index = [
        'context' => $context_slug,
        'supported' => !empty($product_ids),
        'post_ids' => $product_ids,
        'groups' => [],
    ];

    foreach ($supported_groups as $group_key => $definition) {
        $index['groups'][$group_key] = [
            'supported' => true,
            'labels' => [],
            'counts' => [],
            'post_map' => [],
        ];
    }

    if (empty($product_ids) || empty($meta_keys)) {
        return $index;
    }

    foreach ($product_ids as $post_id) {
        $post_meta = isset($meta_by_post[$post_id]) ? $meta_by_post[$post_id] : [];

        foreach ($supported_groups as $group_key => $definition) {
            $canonical_key = isset($definition['canonical_key']) ? (string) $definition['canonical_key'] : '';
            $tokens = [];
            $seen = [];

            if ('' !== $canonical_key && !empty($post_meta[$canonical_key])) {
                $tokens = bw_fpw_extract_filter_tokens_from_value($post_meta[$canonical_key]);
            }

            if (empty($tokens)) {
                foreach (bw_fpw_get_candidate_source_meta_keys($context_slug, $group_key) as $source_meta_key) {
                    if (empty($post_meta[$source_meta_key])) {
                        continue;
                    }

                    foreach (bw_fpw_extract_filter_tokens_from_value($post_meta[$source_meta_key]) as $token) {
                        if (empty($token['value']) || isset($seen[$token['value']])) {
                            continue;
                        }

                        $seen[$token['value']] = true;
                        $tokens[] = $token;
                    }
                }
            }

            if (empty($tokens)) {
                continue;
            }

            $token_values = [];
            foreach ($tokens as $token) {
                if (empty($token['value']) || empty($token['label'])) {
                    continue;
                }

                $value = (string) $token['value'];
                $label = (string) $token['label'];
                $token_values[] = $value;

                if (!isset($index['groups'][$group_key]['labels'][$value])) {
                    $index['groups'][$group_key]['labels'][$value] = $label;
                }

                if (!isset($index['groups'][$group_key]['counts'][$value])) {
                    $index['groups'][$group_key]['counts'][$value] = 0;
                }

                $index['groups'][$group_key]['counts'][$value]++;
            }

            if (!empty($token_values)) {
                $index['groups'][$group_key]['post_map'][$post_id] = array_values(array_unique($token_values));
            }
        }
    }

    foreach ($index['groups'] as $group_key => $group_index) {
        if (empty($group_index['counts'])) {
            $index['groups'][$group_key]['supported'] = false;
        } else {
            ksort($index['groups'][$group_key]['counts'], SORT_NATURAL | SORT_FLAG_CASE);
        }
    }

    return $index;
}

function bw_fpw_get_advanced_filter_index($context_slug)
{
    $context_slug = bw_fpw_normalize_context_slug($context_slug);

    if (empty(bw_fpw_get_supported_advanced_filter_groups_for_context($context_slug))) {
        return [
            'context' => $context_slug ?: 'mixed',
            'supported' => false,
            'post_ids' => [],
            'groups' => [],
        ];
    }

    $data_key = bw_fpw_get_advanced_filter_index_transient_key($context_slug);
    $fresh_key = $data_key . '_fresh';
    $cached = get_transient($data_key);

    if (is_array($cached) && get_transient($fresh_key)) {
        return $cached;
    }

    if (!defined('DOING_CRON') || !DOING_CRON) {
        $hook = 'bw_fpw_async_rebuild_advanced_filter_index';
        if (!wp_next_scheduled($hook, [$context_slug])) {
            wp_schedule_single_event(time(), $hook, [$context_slug]);
        }

        if (is_array($cached)) {
            return $cached;
        }

        return [
            'context' => $context_slug,
            'supported' => false,
            'post_ids' => [],
            'groups' => [],
        ];
    }

    if (!bw_fpw_acquire_index_build_lock('advanced_filter_index', $context_slug)) {
        $attempts = bw_fpw_get_index_build_lock_wait_attempts();
        $wait_us = bw_fpw_get_index_build_lock_wait_interval_us();

        for ($i = 0; $i < $attempts; $i++) {
            if ($wait_us > 0) {
                usleep($wait_us);
            }
            $maybe = get_transient($data_key);
            if (is_array($maybe) && get_transient($fresh_key)) {
                return $maybe;
            }
        }

        $late = get_transient($data_key);
        if (is_array($late)) {
            return $late;
        }

        $index = bw_fpw_build_advanced_filter_index($context_slug);
        set_transient($data_key, $index, 2 * HOUR_IN_SECONDS);
        set_transient($fresh_key, 1, bw_fpw_get_advanced_filter_index_cache_ttl());

        return $index;
    }

    try {
        $index = bw_fpw_build_advanced_filter_index($context_slug);
        set_transient($data_key, $index, 2 * HOUR_IN_SECONDS);
        set_transient($fresh_key, 1, bw_fpw_get_advanced_filter_index_cache_ttl());
    } finally {
        bw_fpw_release_index_build_lock('advanced_filter_index', $context_slug);
    }

    return $index;
}

function bw_fpw_async_rebuild_advanced_filter_index_callback($context_slug)
{
    bw_fpw_get_advanced_filter_index((string) $context_slug);
}
