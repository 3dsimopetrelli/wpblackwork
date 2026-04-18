<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bw_fpw_get_discovery_sort_default_key' ) ) {
	function bw_fpw_get_discovery_sort_default_key() {
		return 'newest';
	}
}

if ( ! function_exists( 'bw_fpw_get_discovery_sort_aliases' ) ) {
	function bw_fpw_get_discovery_sort_aliases() {
		return [
			'default'       => 'newest',
			'recent'        => 'newest',
			'random_seeded' => 'newest',
		];
	}
}

if ( ! function_exists( 'bw_fpw_get_discovery_sort_options' ) ) {
	function bw_fpw_get_discovery_sort_options() {
		return [
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
}

if ( ! function_exists( 'bw_fpw_get_discovery_sort_icon_svg_by_key' ) ) {
	function bw_fpw_get_discovery_sort_icon_svg_by_key( $icon_key ) {
		switch ( sanitize_key( (string) $icon_key ) ) {
			case 'clock-arrow-down':
				return '<svg class="bw-fpw-sort-trigger__icon-svg" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 6v6l2 1"/><path d="M12.337 21.994a10 10 0 1 1 9.588-8.767"/><path d="m14 18 4 4 4-4"/><path d="M18 14v8"/></svg>';

			case 'clock-arrow-up':
				return '<svg class="bw-fpw-sort-trigger__icon-svg" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 6v6l1.56.78"/><path d="M13.227 21.925a10 10 0 1 1 8.767-9.588"/><path d="m14 18 4-4 4 4"/><path d="M18 22v-8"/></svg>';

			case 'arrow-down-a-z':
				return '<svg class="bw-fpw-sort-trigger__icon-svg" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m3 16 4 4 4-4"/><path d="M7 20V4"/><path d="M20 8h-5"/><path d="M15 10V6.5a2.5 2.5 0 0 1 5 0V10"/><path d="M15 14h5l-5 6h5"/></svg>';

			case 'arrow-up-z-a':
				return '<svg class="bw-fpw-sort-trigger__icon-svg" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m3 8 4-4 4 4"/><path d="M7 4v16"/><path d="M15 4h5l-5 6h5"/><path d="M15 20v-3.5a2.5 2.5 0 0 1 5 0V20"/><path d="M20 18h-5"/></svg>';

			case 'calendar-arrow-up':
				return '<svg class="bw-fpw-sort-trigger__icon-svg" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m14 18 4-4 4 4"/><path d="M16 2v4"/><path d="M18 22v-8"/><path d="M21 11.343V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h9"/><path d="M3 10h18"/><path d="M8 2v4"/></svg>';

			case 'calendar-arrow-down':
				return '<svg class="bw-fpw-sort-trigger__icon-svg" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m14 18 4 4 4-4"/><path d="M16 2v4"/><path d="M18 14v8"/><path d="M21 11.354V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h7.343"/><path d="M3 10h18"/><path d="M8 2v4"/></svg>';

			case 'arrow-down-up':
			default:
				return '<svg class="bw-fpw-sort-trigger__icon-svg" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m3 16 4 4 4-4"/><path d="M7 20V4"/><path d="m21 8-4-4-4 4"/><path d="M17 4v16"/></svg>';
		}
	}
}

if ( ! function_exists( 'bw_fpw_get_discovery_sort_chevron_svg' ) ) {
	function bw_fpw_get_discovery_sort_chevron_svg() {
		return '<svg class="bw-fpw-sort-trigger__chevron-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6"/></svg>';
	}
}

if ( ! function_exists( 'bw_fpw_get_discovery_sort_icon_svg' ) ) {
	function bw_fpw_get_discovery_sort_icon_svg( $sort_key ) {
		$sort_key = sanitize_key( (string) $sort_key );
		$options  = bw_fpw_get_discovery_sort_options();
		$option   = isset( $options[ $sort_key ] ) ? $options[ $sort_key ] : $options[ bw_fpw_get_discovery_sort_default_key() ];
		$icon_key = isset( $option['icon_key'] ) ? $option['icon_key'] : 'clock-arrow-down';

		return bw_fpw_get_discovery_sort_icon_svg_by_key( $icon_key );
	}
}
