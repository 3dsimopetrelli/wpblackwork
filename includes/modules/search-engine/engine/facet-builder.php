<?php
if (!defined('ABSPATH')) {
    exit;
}

function bw_fpw_collect_tags_from_posts($taxonomy, $post_ids)
{
    if (empty($post_ids)) {
        return [];
    }

    $post_ids = bw_fpw_normalize_int_array($post_ids, bw_fpw_get_tag_source_posts_limit());

    if (empty($post_ids)) {
        return [];
    }

    $terms = wp_get_object_terms(
        $post_ids,
        $taxonomy,
        [
            'fields' => 'all_with_object_id',
        ]
    );

    if (empty($terms) || is_wp_error($terms)) {
        return [];
    }

    $results = [];

    foreach ($terms as $term) {
        $term_id = (int) $term->term_id;

        if (!isset($results[$term_id])) {
            $results[$term_id] = [
                'term_id' => $term_id,
                'name' => $term->name,
                'count' => 0,
            ];
        }

        $results[$term_id]['count']++;
    }

    usort(
        $results,
        static function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        }
    );

    return array_values($results);
}

function bw_fpw_get_related_tags_data($post_type, $category = 'all', $subcategories = [], $search = '', $year_from = null, $year_to = null, $context_slug = '', $advanced_filters = [])
{
    return bw_fpw_get_cached_derived_filter_dataset(
        'related_tags',
        [
            'post_type' => $post_type,
            'category' => $category,
            'subcategories' => $subcategories,
            'search' => $search,
            'year_from' => $year_from,
            'year_to' => $year_to,
            'context_slug' => $context_slug,
            'advanced_filters' => $advanced_filters,
        ],
        static function () use ($post_type, $category, $subcategories, $search, $year_from, $year_to, $context_slug, $advanced_filters) {
            $tag_taxonomy = 'product' === $post_type ? 'product_tag' : 'post_tag';
            $normalized_search = bw_fpw_normalize_search_value($search);

            if (('all' === $category || empty($category)) && '' === $normalized_search && null === $year_from && null === $year_to && !bw_fpw_has_active_advanced_filter_selections($advanced_filters)) {
                $terms = get_terms(
                    [
                        'taxonomy' => $tag_taxonomy,
                        'hide_empty' => true,
                    ]
                );

                if (empty($terms) || is_wp_error($terms)) {
                    return [];
                }

                $results = [];

                foreach ($terms as $term) {
                    $results[] = [
                        'term_id' => (int) $term->term_id,
                        'name' => $term->name,
                        'count' => (int) $term->count,
                    ];
                }

                return $results;
            }

            $post_ids = '' === $normalized_search
                ? bw_fpw_get_filtered_post_ids_for_tags($post_type, $category, $subcategories, $year_from, $year_to, $context_slug, $advanced_filters)
                : bw_fpw_get_matching_post_ids($post_type, $category, $subcategories, [], $normalized_search, $year_from, $year_to, $context_slug, $advanced_filters);

            return bw_fpw_collect_tags_from_posts($tag_taxonomy, $post_ids);
        }
    );
}

function bw_fpw_get_available_subcategories_data($post_type, $category = 'all', $tags = [], $search = '', $year_from = null, $year_to = null, $context_slug = '', $advanced_filters = [])
{
    return bw_fpw_get_cached_derived_filter_dataset(
        'available_subcategories',
        [
            'post_type' => $post_type,
            'category' => $category,
            'tags' => $tags,
            'search' => $search,
            'year_from' => $year_from,
            'year_to' => $year_to,
            'context_slug' => $context_slug,
            'advanced_filters' => $advanced_filters,
        ],
        static function () use ($post_type, $category, $tags, $search, $year_from, $year_to, $context_slug, $advanced_filters) {
            $taxonomy = 'product' === $post_type ? 'product_cat' : 'category';
            $normalized_search = bw_fpw_normalize_search_value($search);
            $post_ids = '' === $normalized_search
                ? bw_fpw_get_candidate_post_ids_without_search($post_type, $category, [], $tags, $year_from, $year_to, $context_slug, $advanced_filters, bw_fpw_get_tag_source_posts_limit())
                : bw_fpw_get_matching_post_ids($post_type, $category, [], $tags, $normalized_search, $year_from, $year_to, $context_slug, $advanced_filters);

            if (empty($post_ids)) {
                return [];
            }

            $terms = wp_get_object_terms(
                $post_ids,
                $taxonomy,
                [
                    'fields' => 'all_with_object_id',
                ]
            );

            if (empty($terms) || is_wp_error($terms)) {
                return [];
            }

            $results = [];
            $parent_category_id = 'all' !== $category ? absint($category) : 0;

            foreach ($terms as $term) {
                $term_id = (int) $term->term_id;
                $term_parent = (int) $term->parent;

                if ('all' === $category) {
                    if ($term_parent <= 0) {
                        continue;
                    }
                } elseif ($term_parent !== $parent_category_id) {
                    continue;
                }

                if (!isset($results[$term_id])) {
                    $results[$term_id] = [
                        'term_id' => $term_id,
                        'name' => $term->name,
                        'count' => 0,
                    ];
                }

                $results[$term_id]['count']++;
            }

            usort(
                $results,
                static function ($a, $b) {
                    if ($a['count'] === $b['count']) {
                        return strcmp($a['name'], $b['name']);
                    }

                    return $b['count'] <=> $a['count'];
                }
            );

            return array_values($results);
        }
    );
}

function bw_fpw_render_tag_markup($tags)
{
    if (empty($tags)) {
        return '';
    }

    ob_start();

    foreach ($tags as $tag) {
        ?>
        <button class="bw-fpw-filter-option bw-fpw-tag-button" data-tag="<?php echo esc_attr($tag['term_id']); ?>">
            <span class="bw-fpw-option-label"><?php echo esc_html($tag['name']); ?></span> <span
                class="bw-fpw-option-count">(<?php echo esc_html($tag['count']); ?>)</span>
        </button>
        <?php
    }

    return ob_get_clean();
}

function bw_fpw_build_filter_ui_payload($post_type, $category, $subcategories, $tags, $search, $year_from, $year_to, $effective_context_slug, $advanced_filters, $filter_ui_candidate_post_ids, $needs_refined_advanced_filter_scope, $result_count)
{
    $related_tags = bw_fpw_get_related_tags_data($post_type, $category, $subcategories, $search, $year_from, $year_to, $effective_context_slug, $advanced_filters);
    $available_types = bw_fpw_get_available_subcategories_data($post_type, $category, $tags, $search, $year_from, $year_to, $effective_context_slug, $advanced_filters);
    $year_ui = 'product' === $post_type ? bw_fpw_get_year_filter_ui($effective_context_slug) : [
        'supported' => false,
        'context' => $effective_context_slug ?: 'mixed',
        'min' => null,
        'max' => null,
        'quick_ranges' => [],
    ];
    $advanced_filter_ui = 'product' === $post_type
        ? bw_fpw_get_advanced_filter_ui(
            $effective_context_slug,
            $needs_refined_advanced_filter_scope ? $filter_ui_candidate_post_ids : null,
            $advanced_filters
        )
        : [];

    return [
        'tags_html' => bw_fpw_render_tag_markup($related_tags),
        'available_tags' => wp_list_pluck($related_tags, 'term_id'),
        'available_types' => wp_list_pluck($available_types, 'term_id'),
        'filter_ui' => [
            'types' => array_values($available_types),
            'tags' => array_values($related_tags),
            'result_count' => $result_count,
            'year' => $year_ui,
            'advanced' => $advanced_filter_ui,
        ],
    ];
}
