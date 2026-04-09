<?php
if (!defined('ABSPATH')) {
    exit;
}

function bw_fpw_run_matching_post_ids_query($post_type, $category, $subcategories, $tags, $normalized_search, $year_from = null, $year_to = null, $context_slug = '', $advanced_filters = [], $ignore_advanced_group = '')
{
    global $wpdb;
    $taxonomy = 'product' === $post_type ? 'product_cat' : 'category';
    $tag_taxonomy = 'product' === $post_type ? 'product_tag' : 'post_tag';
    $post_type_safe = sanitize_key($post_type);

    $cat_term_ids = [];
    if ('all' !== $category && absint($category) > 0) {
        $cat_id = absint($category);
        if (!empty($subcategories)) {
            $cat_term_ids = array_values(array_filter(array_map('absint', (array) $subcategories)));
        } else {
            $cat_term_ids = [$cat_id];
            $children = get_term_children($cat_id, $taxonomy);
            if (is_array($children) && !empty($children)) {
                $cat_term_ids = array_merge($cat_term_ids, array_map('absint', $children));
            }
        }
    }

    $tag_term_ids = array_values(array_filter(array_map('absint', (array) $tags)));

    $joins = '';
    $wheres = [
        "p.post_type   = '" . esc_sql($post_type_safe) . "'",
        "p.post_status = 'publish'",
    ];

    if (!empty($cat_term_ids)) {
        $ids_in = implode(',', $cat_term_ids);
        $joins .= " INNER JOIN {$wpdb->term_relationships} tr_cat"
            . "   ON  tr_cat.object_id = p.ID"
            . " INNER JOIN {$wpdb->term_taxonomy} tt_cat"
            . "   ON  tt_cat.term_taxonomy_id = tr_cat.term_taxonomy_id";
        $wheres[] = "tt_cat.taxonomy = '" . esc_sql($taxonomy) . "'";
        $wheres[] = "tt_cat.term_id IN ({$ids_in})";
    }

    if (!empty($tag_term_ids)) {
        $ids_in = implode(',', $tag_term_ids);
        $joins .= " INNER JOIN {$wpdb->term_relationships} tr_tag"
            . "   ON  tr_tag.object_id = p.ID"
            . " INNER JOIN {$wpdb->term_taxonomy} tt_tag"
            . "   ON  tt_tag.term_taxonomy_id = tr_tag.term_taxonomy_id";
        $wheres[] = "tt_tag.taxonomy = '" . esc_sql($tag_taxonomy) . "'";
        $wheres[] = "tt_tag.term_id IN ({$ids_in})";
    }

    if (null !== $year_from || null !== $year_to) {
        $joins .= $wpdb->prepare(
            " INNER JOIN {$wpdb->postmeta} pm_year
                ON pm_year.post_id = p.ID
               AND pm_year.meta_key = %s",
            bw_fpw_get_canonical_year_meta_key()
        );

        if (null !== $year_from && null !== $year_to) {
            $wheres[] = 'CAST(pm_year.meta_value AS UNSIGNED) BETWEEN ' . (int) $year_from . ' AND ' . (int) $year_to;
        } elseif (null !== $year_from) {
            $wheres[] = 'CAST(pm_year.meta_value AS UNSIGNED) >= ' . (int) $year_from;
        } else {
            $wheres[] = 'CAST(pm_year.meta_value AS UNSIGNED) <= ' . (int) $year_to;
        }
    }

    if ('' !== $normalized_search) {
        $like = '%' . $wpdb->esc_like($normalized_search) . '%';
        $like_sql = "'" . esc_sql($like) . "'";
        $searchable_meta_keys = array_values(
            array_unique(
                array_merge(
                    [bw_fpw_get_canonical_year_meta_key(), bw_fpw_get_canonical_author_meta_key()],
                    array_filter([
                        bw_fpw_get_canonical_artist_meta_key(),
                        bw_fpw_get_canonical_publisher_meta_key(),
                    ]),
                    bw_fpw_get_all_filter_source_year_meta_keys(),
                    bw_fpw_get_all_filter_source_author_meta_keys(),
                    bw_fpw_get_all_filter_source_meta_keys_for_group('artist'),
                    bw_fpw_get_all_filter_source_meta_keys_for_group('publisher')
                )
            )
        );
        $searchable_meta_keys_sql = "'" . implode("','", array_map('esc_sql', $searchable_meta_keys)) . "'";

        $joins .= " LEFT JOIN {$wpdb->term_relationships} tr_search
                    ON tr_search.object_id = p.ID";
        $joins .= " LEFT JOIN {$wpdb->term_taxonomy} tt_search
                    ON tt_search.term_taxonomy_id = tr_search.term_taxonomy_id
                   AND tt_search.taxonomy IN ('" . esc_sql($taxonomy) . "', '" . esc_sql($tag_taxonomy) . "')";
        $joins .= " LEFT JOIN {$wpdb->terms} t_search
                    ON t_search.term_id = tt_search.term_id";
        $joins .= " LEFT JOIN {$wpdb->postmeta} pm_search
                    ON pm_search.post_id = p.ID
                   AND pm_search.meta_key IN ({$searchable_meta_keys_sql})";

        $wheres[] = "(LOWER(p.post_title) LIKE {$like_sql}"
            . " OR LOWER(p.post_name) LIKE {$like_sql}"
            . " OR LOWER(p.post_excerpt) LIKE {$like_sql}"
            . " OR LOWER(COALESCE(t_search.name, '')) LIKE {$like_sql}"
            . " OR LOWER(COALESCE(pm_search.meta_value, '')) LIKE {$like_sql}"
            . ")";
    }

    $sql = "SELECT DISTINCT p.ID FROM {$wpdb->posts} p"
        . $joins
        . ' WHERE ' . implode(' AND ', $wheres);
    $raw_col = $wpdb->get_col($sql);
    $post_ids = array_map('absint', (array) $raw_col);

    if (!bw_fpw_has_active_advanced_filter_selections($advanced_filters)) {
        return $post_ids;
    }

    return bw_fpw_apply_advanced_filters_to_post_ids($post_ids, $context_slug, $advanced_filters, $ignore_advanced_group);
}

function bw_fpw_get_matching_post_ids($post_type, $category, $subcategories, $tags, $search, $year_from = null, $year_to = null, $context_slug = '', $advanced_filters = [], $ignore_advanced_group = '')
{
    $normalized_search = bw_fpw_normalize_search_value($search);

    if ('' === $normalized_search) {
        return bw_fpw_run_matching_post_ids_query(
            $post_type,
            $category,
            $subcategories,
            $tags,
            $normalized_search,
            $year_from,
            $year_to,
            $context_slug,
            $advanced_filters,
            $ignore_advanced_group
        );
    }

    $dataset = 'matching_post_ids';

    if ('' !== $ignore_advanced_group) {
        $dataset .= '_' . sanitize_key((string) $ignore_advanced_group);
    }

    return bw_fpw_get_cached_derived_filter_dataset(
        $dataset,
        [
            'post_type' => $post_type,
            'category' => $category,
            'subcategories' => $subcategories,
            'tags' => $tags,
            'search' => $normalized_search,
            'year_from' => $year_from,
            'year_to' => $year_to,
            'context_slug' => $context_slug,
            'advanced_filters' => $advanced_filters,
        ],
        static function () use ($post_type, $category, $subcategories, $tags, $normalized_search, $year_from, $year_to, $context_slug, $advanced_filters, $ignore_advanced_group) {
            return bw_fpw_run_matching_post_ids_query(
                $post_type,
                $category,
                $subcategories,
                $tags,
                $normalized_search,
                $year_from,
                $year_to,
                $context_slug,
                $advanced_filters,
                $ignore_advanced_group
            );
        }
    );
}
