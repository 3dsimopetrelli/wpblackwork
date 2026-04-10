<?php
if (!defined('ABSPATH')) {
    exit;
}

function bw_fpw_compute_canonical_year_for_product($post_id)
{
    $context_slug = bw_fpw_resolve_product_family_slug_from_product($post_id);
    $meta_keys = bw_fpw_get_candidate_source_meta_keys($context_slug, 'year');

    foreach ($meta_keys as $meta_key) {
        $year = bw_fpw_extract_year_int(get_post_meta($post_id, $meta_key, true));
        if (null !== $year) {
            return $year;
        }
    }

    return null;
}

function bw_fpw_compute_canonical_author_for_product($post_id)
{
    $context_slug = bw_fpw_resolve_product_family_slug_from_product($post_id);
    $meta_keys = bw_fpw_get_candidate_source_meta_keys($context_slug, 'author');

    $tokens = [];

    foreach ($meta_keys as $meta_key) {
        $tokens = array_merge($tokens, bw_fpw_extract_filter_tokens_from_value((string) get_post_meta($post_id, $meta_key, true)));
        if (!empty($tokens)) {
            return bw_fpw_join_filter_token_labels_for_storage($tokens);
        }
    }

    return '';
}

function bw_fpw_compute_canonical_text_for_product($post_id, $group_key)
{
    $context_slug = bw_fpw_resolve_product_family_slug_from_product($post_id);
    $meta_keys = bw_fpw_get_candidate_source_meta_keys($context_slug, $group_key);
    $seen = [];
    $tokens = [];

    foreach ($meta_keys as $meta_key) {
        foreach (bw_fpw_extract_filter_tokens_from_value((string) get_post_meta($post_id, $meta_key, true)) as $token) {
            if (empty($token['value']) || isset($seen[$token['value']])) {
                continue;
            }

            $seen[$token['value']] = true;
            $tokens[] = $token;
        }
    }

    return bw_fpw_join_filter_token_labels_for_storage($tokens);
}

function bw_fpw_sync_product_filter_meta($post_id)
{
    static $sync_in_progress = [];

    $post_id = absint($post_id);
    if ($post_id <= 0 || 'product' !== get_post_type($post_id)) {
        return;
    }

    if (!empty($sync_in_progress[$post_id])) {
        return;
    }

    $sync_in_progress[$post_id] = true;

    $canonical_year_key = bw_fpw_get_canonical_year_meta_key();
    $canonical_author_key = bw_fpw_get_canonical_author_meta_key();
    $advanced_group_definitions = bw_fpw_get_advanced_filter_group_definitions();

    $year = bw_fpw_compute_canonical_year_for_product($post_id);
    $author = bw_fpw_compute_canonical_author_for_product($post_id);

    if (null !== $year) {
        update_post_meta($post_id, $canonical_year_key, (int) $year);
    } else {
        delete_post_meta($post_id, $canonical_year_key);
    }

    if ('' !== $author) {
        update_post_meta($post_id, $canonical_author_key, $author);
    } else {
        delete_post_meta($post_id, $canonical_author_key);
    }

    foreach ($advanced_group_definitions as $group_key => $definition) {
        $canonical_key = isset($definition['canonical_key']) ? (string) $definition['canonical_key'] : '';
        if ('' === $canonical_key || ('author' === $group_key && $canonical_key === $canonical_author_key)) {
            continue;
        }

        $value = bw_fpw_compute_canonical_text_for_product($post_id, $group_key);

        if ('' !== $value) {
            update_post_meta($post_id, $canonical_key, $value);
        } else {
            delete_post_meta($post_id, $canonical_key);
        }
    }

    unset($sync_in_progress[$post_id]);
}

function bw_fpw_get_scoped_product_family_slugs_for_invalidation($post_id, $extra_term_ids = [], $extra_tt_ids = [])
{
    $post_id = absint($post_id);

    if ($post_id <= 0 || 'product' !== get_post_type($post_id)) {
        return null;
    }

    $slugs = [];
    $current_term_ids = wp_get_post_terms($post_id, 'product_cat', ['fields' => 'ids']);

    if (!is_wp_error($current_term_ids) && is_array($current_term_ids)) {
        $extra_term_ids = array_merge($extra_term_ids, $current_term_ids);
    }

    foreach ((array) $extra_term_ids as $term_id) {
        $slug = bw_fpw_resolve_product_family_slug_from_term_id((int) $term_id, 'product_cat');

        if ('' !== $slug && 'mixed' !== $slug) {
            $slugs[$slug] = true;
        }
    }

    foreach ((array) $extra_tt_ids as $tt_id) {
        $term = get_term_by('term_taxonomy_id', (int) $tt_id, 'product_cat');

        if (!$term instanceof WP_Term || is_wp_error($term)) {
            continue;
        }

        $slug = bw_fpw_resolve_product_family_slug_from_term_id((int) $term->term_id, 'product_cat');

        if ('' !== $slug && 'mixed' !== $slug) {
            $slugs[$slug] = true;
        }
    }

    return !empty($slugs) ? array_values(array_keys($slugs)) : null;
}

function bw_fpw_clear_grid_transients($post_id)
{
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    if ('product' === get_post_type($post_id)) {
        $slugs = bw_fpw_get_scoped_product_family_slugs_for_invalidation($post_id);
        bw_fpw_clear_grid_transient_cache($slugs);
        bw_fpw_clear_advanced_filter_index_transients($slugs ?: '');
        bw_fpw_sync_product_filter_meta($post_id);
        bw_fpw_clear_year_index_transients($slugs ?: '');
    } else {
        bw_fpw_clear_grid_transient_cache();
        bw_fpw_clear_advanced_filter_index_transients();
        bw_fpw_clear_year_index_transients();
    }
}

function bw_fpw_handle_product_filter_meta_change($meta_id_or_ids, $object_id, $meta_key, $meta_value = '')
{
    $object_id = absint($object_id);
    if ($object_id <= 0 || 'product' !== get_post_type($object_id)) {
        return;
    }

    if (!in_array($meta_key, bw_fpw_get_all_filter_relevant_meta_keys(), true)) {
        return;
    }

    if (in_array($meta_key, bw_fpw_get_all_filter_canonical_meta_keys(), true)) {
        return;
    }

    bw_fpw_sync_product_filter_meta($object_id);

    $slugs = bw_fpw_get_scoped_product_family_slugs_for_invalidation($object_id);
    bw_fpw_clear_grid_transient_cache($slugs);
    bw_fpw_clear_year_index_transients($slugs ?: '');
    bw_fpw_clear_advanced_filter_index_transients($slugs ?: '');
}

function bw_fpw_handle_product_filter_term_change($object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids)
{
    $object_id = absint($object_id);
    if ($object_id <= 0 || 'product' !== get_post_type($object_id) || 'product_cat' !== $taxonomy) {
        return;
    }

    bw_fpw_sync_product_filter_meta($object_id);

    $slugs = bw_fpw_get_scoped_product_family_slugs_for_invalidation($object_id, (array) $terms, (array) $old_tt_ids);
    bw_fpw_clear_grid_transient_cache($slugs);
    bw_fpw_clear_year_index_transients($slugs ?: '');
    bw_fpw_clear_advanced_filter_index_transients($slugs ?: '');
}

function bw_fpw_handle_product_filter_status_change($new_status, $old_status, $post)
{
    if (!$post instanceof WP_Post || 'product' !== $post->post_type || $new_status === $old_status) {
        return;
    }

    bw_fpw_sync_product_filter_meta($post->ID);

    $slugs = bw_fpw_get_scoped_product_family_slugs_for_invalidation($post->ID);
    bw_fpw_clear_grid_transient_cache($slugs);
    bw_fpw_clear_year_index_transients($slugs ?: '');
    bw_fpw_clear_advanced_filter_index_transients($slugs ?: '');
}
