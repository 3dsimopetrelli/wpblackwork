<?php
if (!defined('ABSPATH')) {
    exit;
}

function bw_fpw_has_active_advanced_filter_selections($filters)
{
    foreach (bw_fpw_normalize_advanced_filter_selections($filters) as $values) {
        if (!empty($values)) {
            return true;
        }
    }

    return false;
}

function bw_fpw_apply_advanced_filters_to_post_ids($post_ids, $context_slug, $filters, $ignore_group = '')
{
    $normalized_filters = bw_fpw_normalize_advanced_filter_selections($filters);
    $context_slug = bw_fpw_normalize_context_slug($context_slug);
    $supported_groups = bw_fpw_get_supported_advanced_filter_groups_for_context($context_slug);
    $index = bw_fpw_get_advanced_filter_index($context_slug);
    $candidate_ids = is_array($post_ids)
        ? array_values(array_unique(array_map('absint', $post_ids)))
        : array_values(array_map('absint', isset($index['post_ids']) ? (array) $index['post_ids'] : []));

    if (empty($candidate_ids) || empty($supported_groups)) {
        return $candidate_ids;
    }

    $candidate_lookup = array_fill_keys($candidate_ids, true);

    foreach ($normalized_filters as $group_key => $selected_tokens) {
        if ($group_key === $ignore_group || empty($selected_tokens) || !isset($supported_groups[$group_key])) {
            continue;
        }

        $selected_lookup = array_fill_keys($selected_tokens, true);
        $post_map = isset($index['groups'][$group_key]['post_map']) ? (array) $index['groups'][$group_key]['post_map'] : [];

        foreach (array_keys($candidate_lookup) as $post_id) {
            $post_tokens = isset($post_map[$post_id]) ? (array) $post_map[$post_id] : [];
            $matched = false;

            foreach ($post_tokens as $token) {
                if (isset($selected_lookup[$token])) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                unset($candidate_lookup[$post_id]);
            }
        }

        if (empty($candidate_lookup)) {
            break;
        }
    }

    return array_map('intval', array_keys($candidate_lookup));
}

function bw_fpw_build_advanced_filter_options_from_post_ids($context_slug, $group_key, $post_ids)
{
    $index = bw_fpw_get_advanced_filter_index($context_slug);
    $group_index = isset($index['groups'][$group_key]) ? (array) $index['groups'][$group_key] : [];
    $labels = isset($group_index['labels']) ? (array) $group_index['labels'] : [];
    $post_map = isset($group_index['post_map']) ? (array) $group_index['post_map'] : [];
    $candidate_ids = is_array($post_ids)
        ? array_values(array_unique(array_map('absint', $post_ids)))
        : array_values(array_map('absint', isset($index['post_ids']) ? (array) $index['post_ids'] : []));
    $counts = [];
    $options = [];

    foreach ($candidate_ids as $post_id) {
        if (empty($post_map[$post_id])) {
            continue;
        }

        foreach ((array) $post_map[$post_id] as $token) {
            if (!isset($counts[$token])) {
                $counts[$token] = 0;
            }

            $counts[$token]++;
        }
    }

    foreach ($counts as $token => $count) {
        $label = isset($labels[$token]) ? (string) $labels[$token] : '';
        if ('' === $label) {
            continue;
        }

        $options[] = [
            'value' => (string) $token,
            'name' => $label,
            'count' => (int) $count,
        ];
    }

    usort(
        $options,
        static function ($a, $b) {
            if ($a['count'] === $b['count']) {
                return strcmp($a['name'], $b['name']);
            }

            return $b['count'] <=> $a['count'];
        }
    );

    return array_values($options);
}

function bw_fpw_get_advanced_filter_ui($context_slug, $post_ids = null, $filters = [])
{
    $context_slug = bw_fpw_normalize_context_slug($context_slug);
    $supported_groups = bw_fpw_get_supported_advanced_filter_groups_for_context($context_slug);
    $base_post_ids = is_array($post_ids) ? array_values(array_unique(array_map('absint', $post_ids))) : null;
    $has_active_filters = bw_fpw_has_active_advanced_filter_selections($filters);
    $ui = [];

    foreach (bw_fpw_get_advanced_filter_group_definitions() as $group_key => $definition) {
        if (!isset($supported_groups[$group_key])) {
            $ui[$group_key] = [
                'supported' => false,
                'options' => [],
            ];
            continue;
        }

        $scoped_post_ids = $has_active_filters
            ? bw_fpw_apply_advanced_filters_to_post_ids($base_post_ids, $context_slug, $filters, $group_key)
            : $base_post_ids;

        $ui[$group_key] = [
            'supported' => true,
            'options' => bw_fpw_build_advanced_filter_options_from_post_ids($context_slug, $group_key, $scoped_post_ids),
        ];
    }

    return $ui;
}
