<?php
/**
 * CDN Subresource Integrity (SRI) attribute injection.
 *
 * Adds integrity/crossorigin attributes to pinned static CDN script/style tags.
 *
 * Extracted verbatim from blackwork-core-plugin.php (Phase 1 bootstrap
 * decomposition, BW-TASK-20260623). Function names and filter registrations are
 * preserved unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return SRI metadata for pinned static CDN assets.
 *
 * @return array
 */
function bw_get_cdn_sri_map() {
	return array(
		'styles'  => array(
			'select2' => array(
				'src'       => 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
				'integrity' => 'sha384-OXVF05DQEe311p6ohU11NwlnX08FzMCsyoXzGOaL+83dKAb3qS17yZJxESl8YrJQ',
			),
		),
		'scripts' => array(
			'supabase-js' => array(
				'src'       => 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2.43.4/dist/umd/supabase.min.js',
				'integrity' => 'sha384-BV2dqVU6K3gwMR3iiAIxuWbMYbnQYo7u3jQXlR9cCWtBUVeIrrcuzn50r50eu9zk',
			),
			'select2'     => array(
				'src'       => 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
				'integrity' => 'sha384-d3UHjPdzJkZuk5H3qKYMLRyWLAQBJbby2yr2Q58hXXtAGF8RSNO9jpLDlKKPv5v3',
			),
		),
	);
}

/**
 * Inject integrity/crossorigin on selected script tags.
 *
 * @param string $tag    Script tag HTML.
 * @param string $handle Script handle.
 * @param string $src    Script source URL.
 *
 * @return string
 */
function bw_add_cdn_sri_script_attributes( $tag, $handle, $src ) {
	$map = bw_get_cdn_sri_map();
	if ( empty( $map['scripts'][ $handle ] ) ) {
		return $tag;
	}

	$entry = $map['scripts'][ $handle ];
	if ( empty( $entry['src'] ) || empty( $entry['integrity'] ) || strpos( (string) $src, $entry['src'] ) !== 0 ) {
		return $tag;
	}

	if ( strpos( $tag, ' integrity=' ) !== false ) {
		return $tag;
	}

	return str_replace(
		'<script ',
		'<script integrity="' . esc_attr( $entry['integrity'] ) . '" crossorigin="anonymous" ',
		$tag
	);
}
add_filter( 'script_loader_tag', 'bw_add_cdn_sri_script_attributes', 10, 3 );

/**
 * Inject integrity/crossorigin on selected stylesheet tags.
 *
 * @param string $html   Stylesheet tag HTML.
 * @param string $handle Style handle.
 * @param string $href   Stylesheet URL.
 * @param string $media  Media attribute.
 *
 * @return string
 */
function bw_add_cdn_sri_style_attributes( $html, $handle, $href, $media ) {
	$map = bw_get_cdn_sri_map();
	if ( empty( $map['styles'][ $handle ] ) ) {
		return $html;
	}

	$entry = $map['styles'][ $handle ];
	if ( empty( $entry['src'] ) || empty( $entry['integrity'] ) || strpos( (string) $href, $entry['src'] ) !== 0 ) {
		return $html;
	}

	if ( strpos( $html, ' integrity=' ) !== false ) {
		return $html;
	}

	return preg_replace(
		'/\\s*\\/?>\\s*$/',
		' integrity="' . esc_attr( $entry['integrity'] ) . '" crossorigin="anonymous" />',
		$html,
		1
	);
}
add_filter( 'style_loader_tag', 'bw_add_cdn_sri_style_attributes', 10, 4 );
