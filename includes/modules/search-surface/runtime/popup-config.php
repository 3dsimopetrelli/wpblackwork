<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Single source of truth for search popup mode and scope metadata.
 *
 * Both PHP rendering and JS (via wp_localize_script) read from these
 * definitions. The sidebar now uses five explicit modes (filter, trending,
 * new, sale, free) instead of the old browse-group model.
 */

function bw_ss_get_group_definitions() {
    $filter_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M10 5H3"/><path d="M12 19H3"/><path d="M14 3v4"/><path d="M16 17v4"/><path d="M21 12h-9"/><path d="M21 19h-5"/><path d="M21 5h-7"/><path d="M8 10v4"/><path d="M8 12H3"/></svg>';

    $trending_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M16 7h6v6"/><path d="m22 7-8.5 8.5-5-5L2 17"/></svg>';

    $new_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 2v4"/><path d="M12 18v4"/><path d="m4.93 4.93 2.83 2.83"/><path d="m16.24 16.24 2.83 2.83"/><path d="M2 12h4"/><path d="M18 12h4"/><path d="m4.93 19.07 2.83-2.83"/><path d="m16.24 7.76 2.83-2.83"/></svg>';

    $sale_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M9 9h.01"/><path d="M15 15h.01"/><path d="M9.5 14.5 15 9"/><path d="m14 2-8.5 8.5c-.83.83-.83 2.17 0 3L6.8 14.8c.83.83 2.17.83 3 0L18.5 6"/><path d="M14 2h8v8"/><path d="m5 15-3 3 3 3 3-3"/></svg>';

    $free_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>';

    return [
        'filter'   => [
            'label'     => __( 'Filter', 'bw-elementor-widgets' ),
            'icon_svg'  => $filter_svg,
            'mode_type' => 'filter',
            'param_key' => '',
        ],
        'trending' => [
            'label'     => __( 'Trending', 'bw-elementor-widgets' ),
            'icon_svg'  => $trending_svg,
            'mode_type' => 'feed',
            'param_key' => '',
        ],
        'new'      => [
            'label'     => __( 'New', 'bw-elementor-widgets' ),
            'icon_svg'  => $new_svg,
            'mode_type' => 'feed',
            'param_key' => '',
        ],
        'sale'     => [
            'label'     => __( 'On Sale', 'bw-elementor-widgets' ),
            'icon_svg'  => $sale_svg,
            'mode_type' => 'feed',
            'param_key' => '',
        ],
        'free'     => [
            'label'     => __( 'Free', 'bw-elementor-widgets' ),
            'icon_svg'  => $free_svg,
            'mode_type' => 'feed',
            'param_key' => '',
        ],
    ];
}

function bw_ss_get_scope_definitions() {
    $modes = [ 'filter', 'trending', 'new', 'sale', 'free' ];

    return [
        'all'                 => [
            'label'        => __( 'All', 'bw-elementor-widgets' ),
            'context_slug' => '',
            'groups'       => $modes,
        ],
        'digital-collections' => [
            'label'        => __( 'Digital Collections', 'bw-elementor-widgets' ),
            'context_slug' => 'digital-collections',
            'groups'       => $modes,
        ],
        'books'               => [
            'label'        => __( 'Books', 'bw-elementor-widgets' ),
            'context_slug' => 'books',
            'groups'       => $modes,
        ],
        'prints'              => [
            'label'        => __( 'Prints', 'bw-elementor-widgets' ),
            'context_slug' => 'prints',
            'groups'       => $modes,
        ],
    ];
}

function bw_ss_get_group_icon_map() {
    $map = [];

    foreach ( bw_ss_get_group_definitions() as $group_key => $def ) {
        $map[ $group_key ] = isset( $def['icon_svg'] ) ? (string) $def['icon_svg'] : '';
    }

    return $map;
}
