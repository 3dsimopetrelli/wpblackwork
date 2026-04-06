<?php
if (!defined('ABSPATH')) {
    exit;
}

function bw_fpw_get_allowed_post_types()
{
    return ['product', 'post'];
}

function bw_fpw_get_default_post_type()
{
    return 'product';
}

function bw_fpw_get_supported_product_family_slugs()
{
    return ['digital-collections', 'books', 'prints'];
}

function bw_fpw_get_canonical_year_meta_key()
{
    return '_bw_filter_year_int';
}

function bw_fpw_get_canonical_author_meta_key()
{
    return '_bw_filter_author_text';
}

function bw_fpw_get_canonical_artist_meta_key()
{
    return '_bw_filter_artist_text';
}

function bw_fpw_get_canonical_publisher_meta_key()
{
    return '_bw_filter_publisher_text';
}

function bw_fpw_get_canonical_source_meta_key()
{
    return '_bw_filter_source_text';
}

function bw_fpw_get_canonical_technique_meta_key()
{
    return '_bw_filter_technique_text';
}

function bw_fpw_get_advanced_filter_group_definitions()
{
    return [
        'artist' => [
            'label' => 'Artist',
            'contexts' => ['digital-collections', 'prints'],
            'canonical_key' => bw_fpw_get_canonical_artist_meta_key(),
            'source_map_key' => 'artist_keys',
            'searchable' => true,
        ],
        'author' => [
            'label' => 'Author',
            'contexts' => ['books'],
            'canonical_key' => bw_fpw_get_canonical_author_meta_key(),
            'source_map_key' => 'author_keys',
            'searchable' => true,
        ],
        'publisher' => [
            'label' => 'Publisher',
            'contexts' => ['digital-collections', 'books', 'prints'],
            'canonical_key' => bw_fpw_get_canonical_publisher_meta_key(),
            'source_map_key' => 'publisher_keys',
            'searchable' => true,
        ],
        'source' => [
            'label' => 'Source',
            'contexts' => ['digital-collections'],
            'canonical_key' => bw_fpw_get_canonical_source_meta_key(),
            'source_map_key' => 'source_keys',
            'searchable' => false,
        ],
        'technique' => [
            'label' => 'Technique',
            'contexts' => ['digital-collections', 'prints'],
            'canonical_key' => bw_fpw_get_canonical_technique_meta_key(),
            'source_map_key' => 'technique_keys',
            'searchable' => false,
        ],
    ];
}

function bw_fpw_get_advanced_filter_group_keys()
{
    return array_keys(bw_fpw_get_advanced_filter_group_definitions());
}

function bw_fpw_is_advanced_filter_group($group_key)
{
    return isset(bw_fpw_get_advanced_filter_group_definitions()[$group_key]);
}

function bw_fpw_get_canonical_meta_key_for_advanced_filter_group($group_key)
{
    $definitions = bw_fpw_get_advanced_filter_group_definitions();
    return isset($definitions[$group_key]['canonical_key']) ? (string) $definitions[$group_key]['canonical_key'] : '';
}

function bw_fpw_get_source_meta_map_key_for_filter_group($group_key)
{
    switch ($group_key) {
        case 'year':
            return 'year_keys';
        case 'author':
            return 'author_keys';
        default:
            $definitions = bw_fpw_get_advanced_filter_group_definitions();
            return isset($definitions[$group_key]['source_map_key']) ? (string) $definitions[$group_key]['source_map_key'] : '';
    }
}

function bw_fpw_get_all_filter_canonical_meta_keys()
{
    $keys = [
        bw_fpw_get_canonical_year_meta_key(),
        bw_fpw_get_canonical_author_meta_key(),
    ];

    foreach (bw_fpw_get_advanced_filter_group_keys() as $group_key) {
        $canonical_key = bw_fpw_get_canonical_meta_key_for_advanced_filter_group($group_key);
        if ('' !== $canonical_key) {
            $keys[] = $canonical_key;
        }
    }

    return array_values(array_unique($keys));
}

function bw_fpw_get_product_filter_source_meta_map()
{
    return [
        'digital-collections' => [
            'year_keys' => ['_digital_year'],
            'author_keys' => ['_bw_artist_name', '_digital_artist_name'],
            'artist_keys' => ['_digital_artist_name', '_bw_artist_name'],
            'publisher_keys' => ['_digital_publisher'],
            'source_keys' => ['_digital_source'],
            'technique_keys' => ['_digital_technique'],
        ],
        'books' => [
            'year_keys' => ['_bw_biblio_year'],
            'author_keys' => ['_bw_biblio_author', '_bw_artist_name', '_digital_artist_name'],
            'artist_keys' => [],
            'publisher_keys' => ['_bw_biblio_publisher'],
            'source_keys' => [],
            'technique_keys' => [],
        ],
        'prints' => [
            'year_keys' => ['_print_year'],
            'author_keys' => ['_print_artist', '_bw_artist_name', '_digital_artist_name'],
            'artist_keys' => ['_print_artist', '_bw_artist_name', '_digital_artist_name'],
            'publisher_keys' => ['_print_publisher'],
            'source_keys' => [],
            'technique_keys' => ['_print_technique'],
        ],
    ];
}

function bw_fpw_get_all_filter_source_meta_keys_for_group($group_key)
{
    $map_key = bw_fpw_get_source_meta_map_key_for_filter_group($group_key);
    $source_map = bw_fpw_get_product_filter_source_meta_map();
    $keys = [];

    if ('' === $map_key) {
        return [];
    }

    foreach ($source_map as $context_map) {
        if (!empty($context_map[$map_key]) && is_array($context_map[$map_key])) {
            $keys = array_merge($keys, $context_map[$map_key]);
        }
    }

    return array_values(array_unique(array_filter($keys)));
}

function bw_fpw_get_all_filter_source_year_meta_keys()
{
    return bw_fpw_get_all_filter_source_meta_keys_for_group('year');
}

function bw_fpw_get_all_filter_source_author_meta_keys()
{
    return bw_fpw_get_all_filter_source_meta_keys_for_group('author');
}

function bw_fpw_get_all_filter_relevant_meta_keys()
{
    return array_values(
        array_unique(
            array_merge(
                bw_fpw_get_all_filter_source_year_meta_keys(),
                bw_fpw_get_all_filter_source_author_meta_keys(),
                bw_fpw_get_all_filter_source_meta_keys_for_group('artist'),
                bw_fpw_get_all_filter_source_meta_keys_for_group('publisher'),
                bw_fpw_get_all_filter_source_meta_keys_for_group('source'),
                bw_fpw_get_all_filter_source_meta_keys_for_group('technique'),
                bw_fpw_get_all_filter_canonical_meta_keys()
            )
        )
    );
}

function bw_fpw_normalize_context_slug($context_slug)
{
    if (!is_string($context_slug)) {
        return '';
    }

    $normalized = sanitize_title(wp_unslash($context_slug));

    if ('mixed' === $normalized) {
        return 'mixed';
    }

    return in_array($normalized, bw_fpw_get_supported_product_family_slugs(), true) ? $normalized : '';
}

function bw_fpw_is_supported_context_slug($context_slug)
{
    return in_array(bw_fpw_normalize_context_slug($context_slug), bw_fpw_get_supported_product_family_slugs(), true);
}

function bw_fpw_get_context_source_meta_map($context_slug)
{
    $map = bw_fpw_get_product_filter_source_meta_map();
    $normalized = bw_fpw_normalize_context_slug($context_slug);

    return isset($map[$normalized]) ? $map[$normalized] : null;
}

function bw_fpw_get_candidate_source_meta_keys($context_slug, $kind)
{
    $kind = bw_fpw_get_source_meta_map_key_for_filter_group($kind);
    $map = bw_fpw_get_context_source_meta_map($context_slug);

    if (is_array($map) && !empty($map[$kind])) {
        return $map[$kind];
    }

    switch ($kind) {
        case 'author_keys':
            return bw_fpw_get_all_filter_source_author_meta_keys();
        case 'year_keys':
            return bw_fpw_get_all_filter_source_year_meta_keys();
        case 'artist_keys':
            return bw_fpw_get_all_filter_source_meta_keys_for_group('artist');
        case 'publisher_keys':
            return bw_fpw_get_all_filter_source_meta_keys_for_group('publisher');
        case 'source_keys':
            return bw_fpw_get_all_filter_source_meta_keys_for_group('source');
        case 'technique_keys':
            return bw_fpw_get_all_filter_source_meta_keys_for_group('technique');
        default:
            return [];
    }
}

function bw_fpw_get_supported_advanced_filter_groups_for_context($context_slug)
{
    $normalized = bw_fpw_normalize_context_slug($context_slug);
    $definitions = bw_fpw_get_advanced_filter_group_definitions();
    $supported = [];

    foreach ($definitions as $group_key => $definition) {
        $contexts = isset($definition['contexts']) && is_array($definition['contexts']) ? $definition['contexts'] : [];

        if (in_array($normalized, $contexts, true)) {
            $supported[$group_key] = $definition;
        }
    }

    return $supported;
}

function bw_fpw_resolve_product_family_slug_from_term_id($term_id, $taxonomy = 'product_cat')
{
    $term_id = absint($term_id);
    if ($term_id <= 0) {
        return '';
    }

    $term = get_term($term_id, $taxonomy);
    if (!$term instanceof WP_Term || is_wp_error($term)) {
        return '';
    }

    $supported = bw_fpw_get_supported_product_family_slugs();
    $lineage = array_reverse(get_ancestors($term_id, $taxonomy, 'taxonomy'));
    $lineage[] = $term_id;

    foreach ($lineage as $candidate_id) {
        $candidate = get_term((int) $candidate_id, $taxonomy);
        if ($candidate instanceof WP_Term && !is_wp_error($candidate) && in_array($candidate->slug, $supported, true)) {
            return $candidate->slug;
        }
    }

    return '';
}

function bw_fpw_resolve_product_family_slug_from_product($post_id)
{
    $post_id = absint($post_id);
    if ($post_id <= 0 || 'product' !== get_post_type($post_id)) {
        return '';
    }

    $term_ids = wp_get_post_terms($post_id, 'product_cat', ['fields' => 'ids']);
    if (is_wp_error($term_ids) || empty($term_ids)) {
        return '';
    }

    $slugs = [];
    foreach ($term_ids as $term_id) {
        $slug = bw_fpw_resolve_product_family_slug_from_term_id((int) $term_id, 'product_cat');
        if ('' !== $slug) {
            $slugs[$slug] = true;
        }
    }

    $resolved = array_keys($slugs);
    if (1 === count($resolved)) {
        return $resolved[0];
    }

    return count($resolved) > 1 ? 'mixed' : '';
}

function bw_fpw_get_context_root_term_id($context_slug)
{
    $normalized = bw_fpw_normalize_context_slug($context_slug);
    if ('' === $normalized) {
        return 0;
    }

    $term = get_term_by('slug', $normalized, 'product_cat');
    return $term instanceof WP_Term ? (int) $term->term_id : 0;
}

function bw_get_product_grid_desktop_filter_groups_by_context()
{
    $all_groups = ['types', 'tags', 'artist', 'author', 'publisher', 'source', 'technique', 'years'];
    $contexts = ['', 'mixed', 'books', 'digital-collections', 'prints'];
    $map = [];

    foreach ($contexts as $context_slug) {
        $groups = ['types', 'tags', 'years'];

        if (
            '' !== $context_slug
            && 'mixed' !== $context_slug
            && function_exists('bw_fpw_get_supported_advanced_filter_groups_for_context')
        ) {
            $groups = array_merge(
                $groups,
                array_keys((array) bw_fpw_get_supported_advanced_filter_groups_for_context($context_slug))
            );
        } else {
            $groups = $all_groups;
        }

        $map[$context_slug] = array_values(array_unique(array_filter($groups)));
    }

    return $map;
}

function bw_get_product_category_context_map_for_editor()
{
    if (!function_exists('bw_fpw_resolve_product_family_slug_from_term_id')) {
        return [];
    }

    $term_ids = get_terms(
        [
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'fields' => 'ids',
        ]
    );

    if (is_wp_error($term_ids) || empty($term_ids)) {
        return [];
    }

    $map = [];

    foreach ($term_ids as $term_id) {
        $resolved_context = (string) bw_fpw_resolve_product_family_slug_from_term_id((int) $term_id, 'product_cat');

        if ('' !== $resolved_context) {
            $map[(string) (int) $term_id] = $resolved_context;
        }
    }

    return $map;
}
