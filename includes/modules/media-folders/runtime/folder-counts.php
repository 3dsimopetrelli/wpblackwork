<?php
/**
 * Media Folders — folder counts + cache engine and invalidation hooks.
 *
 * Extracted verbatim from runtime/ajax.php (Phase 3d, BW-TASK-20260629).
 * Function bodies and hook registrations are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_mf_cache_key')) {
    function bw_mf_cache_key($prefix, $taxonomy, $context)
    {
        $prefix = sanitize_key((string) $prefix);
        $taxonomy = sanitize_key((string) $taxonomy);
        $context = sanitize_key((string) $context);

        return $prefix . '_' . $taxonomy . '_' . $context;
    }
}

if (!function_exists('bw_mf_cache_get')) {
    function bw_mf_cache_get($key)
    {
        $found = false;
        $cached = wp_cache_get($key, BW_MF_CACHE_GROUP, false, $found);
        if ($found) {
            return $cached;
        }

        $cached = get_transient($key);
        if (false !== $cached) {
            wp_cache_set($key, $cached, BW_MF_CACHE_GROUP, BW_MF_COUNTS_CACHE_TTL);
            return $cached;
        }

        return null;
    }
}

if (!function_exists('bw_mf_cache_set')) {
    function bw_mf_cache_set($key, $value, $ttl = BW_MF_COUNTS_CACHE_TTL)
    {
        wp_cache_set($key, $value, BW_MF_CACHE_GROUP, (int) $ttl);
        set_transient($key, $value, (int) $ttl);
    }
}

if (!function_exists('bw_mf_cache_delete')) {
    function bw_mf_cache_delete($key)
    {
        wp_cache_delete($key, BW_MF_CACHE_GROUP);
        delete_transient($key);
    }
}

if (!function_exists('bw_mf_get_summary_counts')) {
    function bw_mf_get_summary_counts($post_type = 'attachment', $taxonomy = 'bw_media_folder')
    {
        $post_type = sanitize_key((string) $post_type);
        if ($post_type === '') {
            $post_type = 'attachment';
        }
        $taxonomy = sanitize_key((string) $taxonomy);
        if ($taxonomy === '') {
            return [
                'all' => 0,
                'unassigned' => 0,
            ];
        }

        $cache_key = bw_mf_cache_key(BW_MF_SUMMARY_CACHE_KEY, $taxonomy, $post_type);
        $cached = bw_mf_cache_get($cache_key);
        if (is_array($cached) && isset($cached['all'], $cached['unassigned'])) {
            return [
                'all' => (int) $cached['all'],
                'unassigned' => (int) $cached['unassigned'],
            ];
        }

        $all_count = wp_count_posts($post_type);
        if (!is_object($all_count)) {
            $all_files = 0;
        } elseif ($post_type === 'attachment') {
            $all_files = isset($all_count->inherit) ? (int) $all_count->inherit : 0;
        } else {
            $all_files = 0;
            foreach ((array) $all_count as $status => $value) {
                if (in_array((string) $status, ['trash', 'auto-draft'], true)) {
                    continue;
                }
                $all_files += (int) $value;
            }
        }

        $summary = [
            'all' => (int) $all_files,
            'unassigned' => bw_mf_get_unassigned_count($post_type, $taxonomy),
        ];

        bw_mf_cache_set($cache_key, $summary, BW_MF_COUNTS_CACHE_TTL);
        return $summary;
    }
}

if (!function_exists('bw_mf_get_unassigned_count')) {
    function bw_mf_get_unassigned_count($post_type = 'attachment', $taxonomy = 'bw_media_folder')
    {
        $post_type = sanitize_key((string) $post_type);
        if ($post_type === '') {
            $post_type = 'attachment';
        }
        $taxonomy = sanitize_key((string) $taxonomy);
        if ($taxonomy === '') {
            return 0;
        }

        $post_status = $post_type === 'attachment' ? 'inherit' : 'any';
        $query = new WP_Query([
            'post_type' => $post_type,
            'post_status' => $post_status,
            'post_status__not_in' => ($post_type === 'attachment') ? [] : ['trash', 'auto-draft'],
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => false,
            'tax_query' => [
                [
                    'taxonomy' => $taxonomy,
                    'operator' => 'NOT EXISTS',
                ],
            ],
        ]);

        return (int) $query->found_posts;
    }
}

if (!function_exists('bw_mf_build_folder_nodes')) {
    function bw_mf_build_folder_nodes($post_type = 'attachment', $taxonomy = 'bw_media_folder')
    {
        $post_type = sanitize_key((string) $post_type);
        if ($post_type === '') {
            $post_type = 'attachment';
        }
        $taxonomy = sanitize_key((string) $taxonomy);
        if ($taxonomy === '') {
            return [];
        }

        $tree_cache_key = bw_mf_cache_key(BW_MF_TREE_CACHE_KEY, $taxonomy, $post_type);
        $cached_nodes = bw_mf_cache_get($tree_cache_key);
        if (is_array($cached_nodes)) {
            return $cached_nodes;
        }

        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'hierarchical' => true,
        ]);

        if (is_wp_error($terms) || !is_array($terms)) {
            return [];
        }

        $counts_map = bw_mf_get_folder_counts_map($terms, $post_type, $taxonomy);

        $by_parent = [];
        foreach ($terms as $term) {
            $term_id = (int) $term->term_id;
            $parent_id = (int) $term->parent;
            $pinned = (int) get_term_meta($term->term_id, 'bw_mf_pinned', true);
            if ($pinned !== 1) {
                $pinned = (int) get_term_meta($term->term_id, 'bw_pinned', true);
            }

            if (!isset($by_parent[$parent_id])) {
                $by_parent[$parent_id] = [];
            }

            $by_parent[$parent_id][] = [
                'id' => $term_id,
                'name' => $term->name,
                'parent' => $parent_id,
                'count' => isset($counts_map[$term_id]) ? (int) $counts_map[$term_id] : 0,
                'color' => (string) get_term_meta($term->term_id, 'bw_color', true),
                'icon_color' => (string) get_term_meta($term->term_id, 'bw_mf_icon_color', true),
                'pinned' => $pinned ? 1 : 0,
                'sort' => (int) get_term_meta($term->term_id, 'bw_sort', true),
            ];
        }

        $nodes = [];
        $walk = static function ($parent_id) use (&$walk, &$nodes, &$by_parent) {
            if (!isset($by_parent[$parent_id]) || !is_array($by_parent[$parent_id])) {
                return;
            }

            usort($by_parent[$parent_id], static function ($a, $b) {
                if ((int) $a['pinned'] !== (int) $b['pinned']) {
                    return ((int) $b['pinned']) <=> ((int) $a['pinned']);
                }

                return strcasecmp((string) $a['name'], (string) $b['name']);
            });

            foreach ($by_parent[$parent_id] as $node) {
                $nodes[] = $node;
                $walk((int) $node['id']);
            }
        };

        $walk(0);

        bw_mf_cache_set($tree_cache_key, $nodes, BW_MF_TREE_CACHE_TTL);
        return $nodes;
    }
}

if (!function_exists('bw_mf_get_folder_counts_map')) {
    function bw_mf_get_folder_counts_map($terms = null, $post_type = 'attachment', $taxonomy = 'bw_media_folder')
    {
        $post_type = sanitize_key((string) $post_type);
        if ($post_type === '') {
            $post_type = 'attachment';
        }
        $taxonomy = sanitize_key((string) $taxonomy);
        if ($taxonomy === '') {
            return [];
        }

        if (!is_array($terms)) {
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'hierarchical' => true,
            ]);
        }

        if (is_wp_error($terms) || !is_array($terms) || empty($terms)) {
            return [];
        }

        $requested_term_ids = [];
        foreach ($terms as $term) {
            if ($term && isset($term->term_id)) {
                $requested_term_ids[] = (int) $term->term_id;
            }
        }

        if (empty($requested_term_ids)) {
            return [];
        }

        $requested_term_ids = array_values(array_unique($requested_term_ids));

        static $request_cache_by_scope = [];
        $cache_scope = $taxonomy . '|' . $post_type;
        if (!array_key_exists($cache_scope, $request_cache_by_scope)) {
            $cache_key = bw_mf_cache_key(BW_MF_COUNTS_CACHE_KEY, $taxonomy, $post_type);
            $request_cache = bw_mf_cache_get($cache_key);
            if (!is_array($request_cache)) {
                $request_cache = bw_mf_get_folder_counts_map_batched($terms, $post_type, $taxonomy);
                if (!is_array($request_cache)) {
                    $request_cache = bw_mf_get_folder_counts_map_fallback($terms, $post_type, $taxonomy);
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('[bw-media-folders] Falling back to per-term count queries.');
                    }
                }

                if (is_array($request_cache)) {
                    bw_mf_cache_set($cache_key, $request_cache, BW_MF_COUNTS_CACHE_TTL);
                }
            }

            if (!is_array($request_cache)) {
                $request_cache = [];
            }
            $request_cache_by_scope[$cache_scope] = $request_cache;
        }

        $request_cache = $request_cache_by_scope[$cache_scope];
        if (!is_array($request_cache)) {
            $request_cache = bw_mf_get_folder_counts_map_fallback($terms, $post_type, $taxonomy);
            if (!is_array($request_cache)) {
                $request_cache = [];
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('[bw-media-folders] Falling back to per-term count queries.');
                }
            }
        }

        $counts = [];
        foreach ($requested_term_ids as $term_id) {
            $counts[$term_id] = isset($request_cache[$term_id]) ? (int) $request_cache[$term_id] : 0;
        }

        return $counts;
    }
}

if (!function_exists('bw_mf_get_folder_counts_map_batched')) {
    function bw_mf_get_folder_counts_map_batched($terms, $post_type = 'attachment', $taxonomy = 'bw_media_folder')
    {
        global $wpdb;

        $post_type = sanitize_key((string) $post_type);
        if ($post_type === '') {
            $post_type = 'attachment';
        }
        $taxonomy = sanitize_key((string) $taxonomy);
        if ($taxonomy === '') {
            return false;
        }

        if (!is_array($terms) || empty($terms)) {
            return [];
        }

        $term_ids = [];
        $parent_map = [];

        foreach ($terms as $term) {
            if (!$term || !isset($term->term_id)) {
                continue;
            }

            $term_id = (int) $term->term_id;
            $parent_id = isset($term->parent) ? (int) $term->parent : 0;

            $term_ids[$term_id] = $term_id;
            $parent_map[$term_id] = $parent_id;

        }

        if (empty($term_ids)) {
            return [];
        }

        $tt_id_placeholders = implode(',', array_fill(0, count($term_ids), '%d'));
        $tt_sql = "
                SELECT term_taxonomy_id, term_id
                FROM {$wpdb->term_taxonomy}
                WHERE taxonomy = %s
                  AND term_id IN ({$tt_id_placeholders})
                ";
        $tt_prepare_args = array_merge([$tt_sql, $taxonomy], array_values($term_ids));
        $tt_prepared_sql = call_user_func_array([$wpdb, 'prepare'], $tt_prepare_args);
        $tt_rows = $wpdb->get_results($tt_prepared_sql, ARRAY_A);

        if (!is_array($tt_rows) || empty($tt_rows)) {
            return array_fill_keys(array_values($term_ids), 0);
        }

        $term_taxonomy_to_term = [];
        foreach ($tt_rows as $row) {
            if (!isset($row['term_taxonomy_id'], $row['term_id'])) {
                continue;
            }

            $mapped_term_id = (int) $row['term_id'];
            if (!isset($term_ids[$mapped_term_id])) {
                continue;
            }

            $term_taxonomy_to_term[(int) $row['term_taxonomy_id']] = $mapped_term_id;
        }

        if (empty($term_taxonomy_to_term)) {
            return array_fill_keys(array_values($term_ids), 0);
        }

        $requested_tt_ids = array_values(array_unique(array_map('absint', array_keys($term_taxonomy_to_term))));
        if (empty($requested_tt_ids)) {
            return array_fill_keys(array_values($term_ids), 0);
        }

        $tt_id_placeholders = implode(',', array_fill(0, count($requested_tt_ids), '%d'));

        // Batch read all assignment relationships once, then fan out to ancestor terms in PHP.
        $status_sql = "AND p.post_status = %s";
        $prepare_prefix = [$taxonomy, $post_type];
        if ($post_type === 'attachment') {
            $prepare_prefix[] = 'inherit';
        } else {
            $status_sql = "AND p.post_status NOT IN ('trash', 'auto-draft')";
        }

        $sql = "
                SELECT DISTINCT tr.object_id, tr.term_taxonomy_id
                FROM {$wpdb->term_relationships} AS tr
                INNER JOIN {$wpdb->posts} AS p ON p.ID = tr.object_id
                INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
                WHERE tt.taxonomy = %s
                  AND p.post_type = %s
                  {$status_sql}
                  AND tr.term_taxonomy_id IN ({$tt_id_placeholders})
                ORDER BY tr.object_id ASC
                ";

        $prepare_args = array_merge([$sql], $prepare_prefix, $requested_tt_ids);
        $prepared_sql = call_user_func_array([$wpdb, 'prepare'], $prepare_args);
        $rows = $wpdb->get_results($prepared_sql, ARRAY_A);

        if (!is_array($rows)) {
            return false;
        }

        $ancestors_map = [];
        foreach ($term_ids as $term_id) {
            $ancestors = [$term_id => true];
            $cursor = isset($parent_map[$term_id]) ? (int) $parent_map[$term_id] : 0;
            $guard = 0;
            while ($cursor > 0 && isset($term_ids[$cursor]) && $guard < 50) {
                $ancestors[$cursor] = true;
                $cursor = isset($parent_map[$cursor]) ? (int) $parent_map[$cursor] : 0;
                $guard++;
            }

            $ancestors_map[$term_id] = array_keys($ancestors);
        }

        $counts = array_fill_keys(array_values($term_ids), 0);
        $current_object_id = 0;
        $current_ancestors = [];

        $flush_current_object = static function () use (&$counts, &$current_ancestors) {
            if (empty($current_ancestors)) {
                return;
            }

            foreach ($current_ancestors as $ancestor_term_id => $_) {
                if (isset($counts[$ancestor_term_id])) {
                    $counts[$ancestor_term_id]++;
                }
            }
        };

        foreach ($rows as $row) {
            if (!isset($row['object_id'], $row['term_taxonomy_id'])) {
                continue;
            }

            $object_id = (int) $row['object_id'];
            $tt_id = (int) $row['term_taxonomy_id'];

            if (!isset($term_taxonomy_to_term[$tt_id])) {
                continue;
            }

            if ($current_object_id > 0 && $object_id !== $current_object_id) {
                $flush_current_object();
                $current_ancestors = [];
            }

            $current_object_id = $object_id;
            $direct_term_id = $term_taxonomy_to_term[$tt_id];

            if (!isset($ancestors_map[$direct_term_id])) {
                continue;
            }

            foreach ($ancestors_map[$direct_term_id] as $ancestor_term_id) {
                $current_ancestors[(int) $ancestor_term_id] = true;
            }
        }

        $flush_current_object();

        return $counts;
    }
}

if (!function_exists('bw_mf_get_folder_counts_map_fallback')) {
    function bw_mf_get_folder_counts_map_fallback($terms, $post_type = 'attachment', $taxonomy = 'bw_media_folder')
    {
        $post_type = sanitize_key((string) $post_type);
        if ($post_type === '') {
            $post_type = 'attachment';
        }
        $taxonomy = sanitize_key((string) $taxonomy);
        if ($taxonomy === '') {
            return [];
        }

        $counts = [];
        foreach ($terms as $term) {
            if (!$term || !isset($term->term_id)) {
                continue;
            }

            $term_id = (int) $term->term_id;
            $query = new WP_Query([
                'post_type' => $post_type,
                'post_status' => ($post_type === 'attachment') ? 'inherit' : 'any',
                'post_status__not_in' => ($post_type === 'attachment') ? [] : ['trash', 'auto-draft'],
                'posts_per_page' => 1,
                'fields' => 'ids',
                'no_found_rows' => false,
                'tax_query' => [
                    [
                        'taxonomy' => $taxonomy,
                        'field' => 'term_id',
                        'terms' => [$term_id],
                        'include_children' => true,
                    ],
                ],
            ]);

            $counts[$term_id] = (int) $query->found_posts;
        }

        return $counts;
    }
}

if (!function_exists('bw_mf_invalidate_folder_counts_cache')) {
    function bw_mf_invalidate_folder_counts_cache($post_type = '', $taxonomy = '')
    {
        $post_type = sanitize_key((string) $post_type);
        $taxonomy = sanitize_key((string) $taxonomy);

        if ($post_type !== '' && $taxonomy !== '') {
            bw_mf_cache_delete(bw_mf_cache_key(BW_MF_COUNTS_CACHE_KEY, $taxonomy, $post_type));
            bw_mf_cache_delete(bw_mf_cache_key(BW_MF_TREE_CACHE_KEY, $taxonomy, $post_type));
            bw_mf_cache_delete(bw_mf_cache_key(BW_MF_SUMMARY_CACHE_KEY, $taxonomy, $post_type));
            return;
        }

        foreach (bw_mf_taxonomy_map() as $map_post_type => $map_taxonomy) {
            bw_mf_cache_delete(bw_mf_cache_key(BW_MF_COUNTS_CACHE_KEY, $map_taxonomy, $map_post_type));
            bw_mf_cache_delete(bw_mf_cache_key(BW_MF_TREE_CACHE_KEY, $map_taxonomy, $map_post_type));
            bw_mf_cache_delete(bw_mf_cache_key(BW_MF_SUMMARY_CACHE_KEY, $map_taxonomy, $map_post_type));
        }
    }
}

if (!function_exists('bw_mf_is_cache_invalidation_suspended')) {
    function bw_mf_is_cache_invalidation_suspended()
    {
        return !empty($GLOBALS['bw_mf_suspend_cache_invalidation']);
    }
}

if (!function_exists('bw_mf_suspend_cache_invalidation')) {
    function bw_mf_suspend_cache_invalidation()
    {
        if (!isset($GLOBALS['bw_mf_suspend_cache_invalidation'])) {
            $GLOBALS['bw_mf_suspend_cache_invalidation'] = 0;
        }
        $GLOBALS['bw_mf_suspend_cache_invalidation']++;
    }
}

if (!function_exists('bw_mf_resume_cache_invalidation')) {
    function bw_mf_resume_cache_invalidation()
    {
        if (!isset($GLOBALS['bw_mf_suspend_cache_invalidation'])) {
            return;
        }

        $GLOBALS['bw_mf_suspend_cache_invalidation']--;
        if ((int) $GLOBALS['bw_mf_suspend_cache_invalidation'] < 0) {
            $GLOBALS['bw_mf_suspend_cache_invalidation'] = 0;
        }
    }
}

if (!function_exists('bw_mf_invalidate_folder_counts_cache_on_set_terms')) {
    function bw_mf_invalidate_folder_counts_cache_on_set_terms($object_id, $terms, $tt_ids, $taxonomy)
    {
        if (bw_mf_is_cache_invalidation_suspended()) {
            return;
        }

        if (!in_array($taxonomy, bw_mf_get_supported_taxonomies(), true)) {
            return;
        }

        $post_type = get_post_type((int) $object_id);
        if (!$post_type) {
            bw_mf_invalidate_folder_counts_cache();
            return;
        }

        $resolved_taxonomy = bw_mf_get_taxonomy_for_post_type($post_type);
        if ($resolved_taxonomy === $taxonomy) {
            bw_mf_invalidate_folder_counts_cache($post_type, $taxonomy);
            return;
        }

        bw_mf_invalidate_folder_counts_cache();
    }
}
add_action('set_object_terms', 'bw_mf_invalidate_folder_counts_cache_on_set_terms', 10, 4);
foreach (bw_mf_get_supported_taxonomies() as $bw_mf_taxonomy_key) {
    add_action('created_' . $bw_mf_taxonomy_key, static function () use ($bw_mf_taxonomy_key) {
        foreach (bw_mf_taxonomy_map() as $post_type => $taxonomy) {
            if ($taxonomy === $bw_mf_taxonomy_key) {
                bw_mf_invalidate_folder_counts_cache($post_type, $taxonomy);
                return;
            }
        }
        bw_mf_invalidate_folder_counts_cache();
    });
    add_action('edited_' . $bw_mf_taxonomy_key, static function () use ($bw_mf_taxonomy_key) {
        foreach (bw_mf_taxonomy_map() as $post_type => $taxonomy) {
            if ($taxonomy === $bw_mf_taxonomy_key) {
                bw_mf_invalidate_folder_counts_cache($post_type, $taxonomy);
                return;
            }
        }
        bw_mf_invalidate_folder_counts_cache();
    });
    add_action('delete_' . $bw_mf_taxonomy_key, static function () use ($bw_mf_taxonomy_key) {
        foreach (bw_mf_taxonomy_map() as $post_type => $taxonomy) {
            if ($taxonomy === $bw_mf_taxonomy_key) {
                bw_mf_invalidate_folder_counts_cache($post_type, $taxonomy);
                return;
            }
        }
        bw_mf_invalidate_folder_counts_cache();
    });
}

if (!function_exists('bw_mf_invalidate_folder_counts_cache_on_term_meta')) {
    function bw_mf_invalidate_folder_counts_cache_on_term_meta($meta_id, $term_id, $meta_key, $_meta_value)
    {
        $watched = ['bw_color', 'bw_mf_icon_color', 'bw_pinned', 'bw_mf_pinned', 'bw_sort'];
        if (!in_array((string) $meta_key, $watched, true)) {
            return;
        }

        $term = get_term((int) $term_id);
        if (!$term || is_wp_error($term) || empty($term->taxonomy)) {
            return;
        }

        $taxonomy = sanitize_key((string) $term->taxonomy);
        if (!in_array($taxonomy, bw_mf_get_supported_taxonomies(), true)) {
            return;
        }

        foreach (bw_mf_taxonomy_map() as $post_type => $mapped_taxonomy) {
            if ($mapped_taxonomy === $taxonomy) {
                bw_mf_invalidate_folder_counts_cache($post_type, $taxonomy);
                return;
            }
        }

        bw_mf_invalidate_folder_counts_cache();
    }
}
add_action('added_term_meta', 'bw_mf_invalidate_folder_counts_cache_on_term_meta', 10, 4);
add_action('updated_term_meta', 'bw_mf_invalidate_folder_counts_cache_on_term_meta', 10, 4);
add_action('deleted_term_meta', 'bw_mf_invalidate_folder_counts_cache_on_term_meta', 10, 4);

if (!function_exists('bw_mf_is_counted_post_status')) {
    function bw_mf_is_counted_post_status($post_type, $post_status)
    {
        $post_type = sanitize_key((string) $post_type);
        $post_status = sanitize_key((string) $post_status);

        if ($post_type === '' || $post_status === '') {
            return false;
        }

        if ($post_type === 'attachment') {
            return $post_status === 'inherit';
        }

        return !in_array($post_status, ['trash', 'auto-draft'], true);
    }
}

if (!function_exists('bw_mf_invalidate_folder_counts_cache_on_post_status_transition')) {
    function bw_mf_invalidate_folder_counts_cache_on_post_status_transition($new_status, $old_status, $post)
    {
        if (bw_mf_is_cache_invalidation_suspended()) {
            return;
        }

        if (!$post instanceof WP_Post || empty($post->post_type)) {
            return;
        }

        $post_type = sanitize_key((string) $post->post_type);
        $taxonomy = bw_mf_get_taxonomy_for_post_type($post_type);
        if ($taxonomy === '') {
            return;
        }

        $was_counted = bw_mf_is_counted_post_status($post_type, $old_status);
        $is_counted = bw_mf_is_counted_post_status($post_type, $new_status);
        if ($was_counted === $is_counted) {
            return;
        }

        bw_mf_invalidate_folder_counts_cache($post_type, $taxonomy);
    }
}
add_action('transition_post_status', 'bw_mf_invalidate_folder_counts_cache_on_post_status_transition', 10, 3);
