<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bw_fpw_get_discovery_sort_default_key() {
	return 'random_seeded';
}

function bw_fpw_get_discovery_sort_aliases() {
	return [
		'default' => 'random_seeded',
		'recent'  => 'newest',
	];
}

function bw_fpw_get_discovery_sort_options() {
	return [
		'random_seeded' => [
			'trigger_label' => __( 'Default', 'bw-elementor-widgets' ),
			'menu_label'    => __( 'Default order', 'bw-elementor-widgets' ),
			'order_by'      => null,
			'order'         => null,
			'icon_key'      => 'arrow-down-up',
		],
		'newest' => [
			'trigger_label' => __( 'Latest', 'bw-elementor-widgets' ),
			'menu_label'    => __( 'Recently added', 'bw-elementor-widgets' ),
			'order_by'      => 'date',
			'order'         => 'DESC',
			'icon_key'      => 'clock-arrow-down',
		],
		'oldest' => [
			'trigger_label' => __( 'Earliest', 'bw-elementor-widgets' ),
			'menu_label'    => __( 'Oldest added', 'bw-elementor-widgets' ),
			'order_by'      => 'date',
			'order'         => 'ASC',
			'icon_key'      => 'clock-arrow-up',
		],
		'title_asc' => [
			'trigger_label' => __( 'A–Z', 'bw-elementor-widgets' ),
			'menu_label'    => __( 'Alphabetical A to Z', 'bw-elementor-widgets' ),
			'order_by'      => 'title',
			'order'         => 'ASC',
			'icon_key'      => 'arrow-down-a-z',
		],
		'title_desc' => [
			'trigger_label' => __( 'Z–A', 'bw-elementor-widgets' ),
			'menu_label'    => __( 'Alphabetical Z to A', 'bw-elementor-widgets' ),
			'order_by'      => 'title',
			'order'         => 'DESC',
			'icon_key'      => 'arrow-up-z-a',
		],
		'year_asc' => [
			'trigger_label' => __( 'Year ↑', 'bw-elementor-widgets' ),
			'menu_label'    => __( 'Year, oldest first', 'bw-elementor-widgets' ),
			'order_by'      => 'year_int',
			'order'         => 'ASC',
			'icon_key'      => 'calendar-arrow-up',
		],
		'year_desc' => [
			'trigger_label' => __( 'Year ↓', 'bw-elementor-widgets' ),
			'menu_label'    => __( 'Year, newest first', 'bw-elementor-widgets' ),
			'order_by'      => 'year_int',
			'order'         => 'DESC',
			'icon_key'      => 'calendar-arrow-down',
		],
	];
}

function bw_fpw_get_discovery_sort_icon_svg_by_key( $icon_key ) {
	switch ( sanitize_key( (string) $icon_key ) ) {
		case 'clock-arrow-down':
			return '<svg class="bw-fpw-sort-trigger__icon-svg" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8"/><path d="M12 8v5"/><path d="m12 13 2.5 2.5"/><path d="M10 16h4"/></svg>';

		case 'clock-arrow-up':
			return '<svg class="bw-fpw-sort-trigger__icon-svg" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8"/><path d="M12 16v-5"/><path d="m12 11-2.5-2.5"/><path d="M10 8h4"/></svg>';

		case 'arrow-down-a-z':
			return '<svg class="bw-fpw-sort-trigger__icon-svg" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M7 4v14"/><path d="m4 15 3 3 3-3"/><text x="15" y="10" text-anchor="middle" font-family="Arial, sans-serif" font-size="5.75" font-weight="700" fill="currentColor">A</text><text x="15" y="18" text-anchor="middle" font-family="Arial, sans-serif" font-size="5.75" font-weight="700" fill="currentColor">Z</text></svg>';

		case 'arrow-up-z-a':
			return '<svg class="bw-fpw-sort-trigger__icon-svg" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M7 20V6"/><path d="m4 9 3-3 3 3"/><text x="15" y="10" text-anchor="middle" font-family="Arial, sans-serif" font-size="5.75" font-weight="700" fill="currentColor">Z</text><text x="15" y="18" text-anchor="middle" font-family="Arial, sans-serif" font-size="5.75" font-weight="700" fill="currentColor">A</text></svg>';

		case 'calendar-arrow-up':
			return '<svg class="bw-fpw-sort-trigger__icon-svg" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 9h18"/><path d="M12 16V8"/><path d="m9 11 3-3 3 3"/></svg>';

		case 'calendar-arrow-down':
			return '<svg class="bw-fpw-sort-trigger__icon-svg" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 9h18"/><path d="M12 8v8"/><path d="m9 13 3 3 3-3"/></svg>';

		case 'arrow-down-up':
		default:
			return '<svg class="bw-fpw-sort-trigger__icon-svg" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m21 16-4 4-4-4"/><path d="M17 20V4"/><path d="m3 8 4-4 4 4"/><path d="M7 4v16"/></svg>';
	}
}

function bw_fpw_get_discovery_sort_icon_svg( $sort_key ) {
	$sort_key = sanitize_key( (string) $sort_key );
	$options  = bw_fpw_get_discovery_sort_options();
	$option   = isset( $options[ $sort_key ] ) ? $options[ $sort_key ] : $options[ bw_fpw_get_discovery_sort_default_key() ];
	$icon_key = isset( $option['icon_key'] ) ? $option['icon_key'] : 'arrow-down-up';

	return bw_fpw_get_discovery_sort_icon_svg_by_key( $icon_key );
}
