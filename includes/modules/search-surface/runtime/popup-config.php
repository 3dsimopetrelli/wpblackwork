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
    $filter_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="lucide lucide-sliders-horizontal-icon lucide-sliders-horizontal"><path d="M10 5H3"/><path d="M12 19H3"/><path d="M14 3v4"/><path d="M16 17v4"/><path d="M21 12h-9"/><path d="M21 19h-5"/><path d="M21 5h-7"/><path d="M8 10v4"/><path d="M8 12H3"/></svg>';

    $trending_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="lucide lucide-trending-up-icon lucide-trending-up"><path d="M16 7h6v6"/><path d="m22 7-8.5 8.5-5-5L2 17"/></svg>';

    $new_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="lucide lucide-flower-icon lucide-flower"><circle cx="12" cy="12" r="3"/><path d="M12 16.5A4.5 4.5 0 1 1 7.5 12 4.5 4.5 0 1 1 12 7.5a4.5 4.5 0 1 1 4.5 4.5 4.5 4.5 0 1 1-4.5 4.5"/><path d="M12 7.5V9"/><path d="M7.5 12H9"/><path d="M16.5 12H15"/><path d="M12 16.5V15"/><path d="m8 8 1.88 1.88"/><path d="M14.12 9.88 16 8"/><path d="m8 16 1.88-1.88"/><path d="M14.12 14.12 16 16"/></svg>';

    $sale_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="lucide lucide-tag-icon lucide-tag"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/></svg>';

    $free_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="lucide lucide-party-popper-icon lucide-party-popper"><path d="M5.8 11.3 2 22l10.7-3.79"/><path d="M4 3h.01"/><path d="M22 8h.01"/><path d="M15 2h.01"/><path d="M22 20h.01"/><path d="m22 2-2.24.75a2.9 2.9 0 0 0-1.96 3.12c.1.86-.57 1.63-1.45 1.63h-.38c-.86 0-1.6.6-1.76 1.44L14 10"/><path d="m22 13-.82-.33c-.86-.34-1.82.2-1.98 1.11c-.11.7-.72 1.22-1.43 1.22H17"/><path d="m11 2 .33.82c.34.86-.2 1.82-1.11 1.98C9.52 4.9 9 5.52 9 6.23V7"/><path d="M11 13c1.93 1.93 2.83 4.17 2 5-.83.83-3.07-.07-5-2-1.93-1.93-2.83-4.17-2-5 .83-.83 3.07.07 5 2Z"/></svg>';

    return [
        'filter'   => [
            'label'     => __( 'Filters', 'bw-elementor-widgets' ),
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
