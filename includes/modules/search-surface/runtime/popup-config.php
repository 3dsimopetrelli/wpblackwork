<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Single source of truth for search popup group and scope metadata.
 *
 * Both PHP rendering and JS (via wp_localize_script) read from these
 * definitions, eliminating the duplicated SVG switch blocks and scattered
 * hardcoded arrays that previously lived across search-surface-template.php,
 * url-state.php, and ajax-search-surface.php.
 */

function bw_ss_get_group_definitions() {
    $trending_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M16 7h6v6"/><path d="m22 7-8.5 8.5-5-5L2 17"/></svg>';

    $categories_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M7 2h10"/><path d="M5 6h14"/><rect width="18" height="12" x="3" y="10" rx="2"/></svg>';

    // Used by: tags, technique, source, artist
    $tag_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 12V4a1 1 0 0 1 1-1h6.297a1 1 0 0 1 .651 1.759l-4.696 4.025"/><path d="m12 21-7.414-7.414A2 2 0 0 1 4 12.172V6.415a1.002 1.002 0 0 1 1.707-.707L20 20.009"/><path d="m12.214 3.381 8.414 14.966a1 1 0 0 1-.167 1.199l-1.168 1.163a1 1 0 0 1-.706.291H6.351a1 1 0 0 1-.625-.219L3.25 18.8a1 1 0 0 1 .631-1.781l4.165.027"/></svg>';

    // Used by: author, publisher
    $book_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 7v14"/><path d="M16 12h2"/><path d="M16 8h2"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/><path d="M6 12h2"/><path d="M6 8h2"/></svg>';

    $years_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M16 14v2.2l1.6 1"/><path d="M16 2v4"/><path d="M21 7.5V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h3.5"/><path d="M3 10h5"/><path d="M8 2v4"/><circle cx="16" cy="16" r="6"/></svg>';

    return [
        'trending'   => [
            'label'       => __( 'Trending', 'bw-elementor-widgets' ),
            'icon_svg'    => $trending_svg,
            'param_key'   => '',
            'browse_type' => '',
        ],
        'categories' => [
            'label'       => __( 'Categories', 'bw-elementor-widgets' ),
            'icon_svg'    => $categories_svg,
            'param_key'   => 'category',
            'browse_type' => 'category',
        ],
        'tags'       => [
            'label'       => __( 'Style / Subject', 'bw-elementor-widgets' ),
            'icon_svg'    => $tag_svg,
            'param_key'   => 'tag',
            'browse_type' => 'tag',
        ],
        'years'      => [
            'label'       => __( 'Year', 'bw-elementor-widgets' ),
            'icon_svg'    => $years_svg,
            'param_key'   => 'year',
            'browse_type' => 'year',
        ],
        'source'     => [
            'label'       => __( 'Sources', 'bw-elementor-widgets' ),
            'icon_svg'    => $tag_svg,
            'param_key'   => 'source',
            'browse_type' => 'advanced',
        ],
        'technique'  => [
            'label'       => __( 'Technique', 'bw-elementor-widgets' ),
            'icon_svg'    => $tag_svg,
            'param_key'   => 'technique',
            'browse_type' => 'advanced',
        ],
        'author'     => [
            'label'       => __( 'Authors', 'bw-elementor-widgets' ),
            'icon_svg'    => $book_svg,
            'param_key'   => 'author',
            'browse_type' => 'advanced',
        ],
        'publisher'  => [
            'label'       => __( 'Publisher', 'bw-elementor-widgets' ),
            'icon_svg'    => $book_svg,
            'param_key'   => 'publisher',
            'browse_type' => 'advanced',
        ],
        'artist'     => [
            'label'       => __( 'Artists', 'bw-elementor-widgets' ),
            'icon_svg'    => $tag_svg,
            'param_key'   => 'artist',
            'browse_type' => 'advanced',
        ],
    ];
}

function bw_ss_get_scope_definitions() {
    return [
        'all'                 => [
            'label'        => __( 'All', 'bw-elementor-widgets' ),
            'context_slug' => '',
            'groups'       => [ 'trending', 'categories', 'tags', 'years' ],
        ],
        'digital-collections' => [
            'label'        => __( 'Digital Collections', 'bw-elementor-widgets' ),
            'context_slug' => 'digital-collections',
            'groups'       => [ 'trending', 'categories', 'source', 'technique', 'years' ],
        ],
        'books'               => [
            'label'        => __( 'Books', 'bw-elementor-widgets' ),
            'context_slug' => 'books',
            'groups'       => [ 'trending', 'categories', 'author', 'publisher', 'years' ],
        ],
        'prints'              => [
            'label'        => __( 'Prints', 'bw-elementor-widgets' ),
            'context_slug' => 'prints',
            'groups'       => [ 'trending', 'categories', 'artist', 'technique', 'years' ],
        ],
    ];
}

/**
 * Returns a flat icon map keyed by group key, for use in wp_localize_script.
 * Eliminates the duplicate SVG switch block that previously lived in search-surface.js.
 */
function bw_ss_get_group_icon_map() {
    $map = [];

    foreach ( bw_ss_get_group_definitions() as $group_key => $def ) {
        $map[ $group_key ] = isset( $def['icon_svg'] ) ? (string) $def['icon_svg'] : '';
    }

    return $map;
}
