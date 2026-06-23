<?php
/**
 * SVG upload security.
 *
 * Enables safe SVG uploads for administrators/editors, sanitizes/validates SVG
 * content on upload, fixes the detected filetype, and skips raster metadata
 * generation for SVG attachments.
 *
 * Extracted verbatim from blackwork-core-plugin.php (Phase 1 bootstrap
 * decomposition, BW-TASK-20260623). Function names and hook registrations are
 * preserved unchanged; bw_mew_svg_sanitize_content() and
 * bw_mew_svg_is_valid_document() are referenced externally.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enable safe SVG uploads for administrators/editors and prevent raster metadata processing.
 */
function bw_mew_allow_svg_uploads( $mimes ) {
	if ( current_user_can( 'manage_options' ) ) {
		$mimes['svg'] = 'image/svg+xml';
	}

	return $mimes;
}
add_filter( 'upload_mimes', 'bw_mew_allow_svg_uploads' );

function bw_mew_get_svg_upload_error_message( $type ) {
	$messages = array(
		'svgz_not_allowed' => __( 'SVG upload blocked: compressed SVGZ files are not allowed.', 'bw-elementor-widgets' ),
		'read_failed'      => __( 'SVG upload blocked: file could not be read for validation.', 'bw-elementor-widgets' ),
		'invalid_xml'      => __( 'SVG upload blocked: file is malformed or not a valid SVG document.', 'bw-elementor-widgets' ),
		'unsafe_content'   => __( 'SVG upload blocked: unsafe content was detected.', 'bw-elementor-widgets' ),
		'sanitize_failed'  => __( 'SVG upload blocked: sanitizer failed to produce a safe result.', 'bw-elementor-widgets' ),
		'write_failed'     => __( 'SVG upload blocked: sanitized content could not be persisted.', 'bw-elementor-widgets' ),
	);

	return isset( $messages[ $type ] ) ? $messages[ $type ] : __( 'SVG upload blocked: validation failed.', 'bw-elementor-widgets' );
}

function bw_mew_get_svg_allowed_tags() {
	return array(
		'svg'            => array(
			'class'               => true,
			'id'                  => true,
			'xmlns'               => true,
			'xmlns:xlink'         => true,
			'viewbox'             => true,
			'width'               => true,
			'height'              => true,
			'role'                => true,
			'aria-hidden'         => true,
			'focusable'           => true,
			'preserveaspectratio' => true,
		),
		'g'              => array(
			'class'        => true,
			'id'           => true,
			'transform'    => true,
			'fill'         => true,
			'stroke'       => true,
			'stroke-width' => true,
			'opacity'      => true,
			'clip-path'    => true,
			'mask'         => true,
			'filter'       => true,
		),
		'path'           => array(
			'class'             => true,
			'id'                => true,
			'd'                 => true,
			'transform'         => true,
			'fill'              => true,
			'stroke'            => true,
			'stroke-width'      => true,
			'stroke-linecap'    => true,
			'stroke-linejoin'   => true,
			'stroke-miterlimit' => true,
			'stroke-dasharray'  => true,
			'stroke-dashoffset' => true,
			'opacity'           => true,
			'fill-rule'         => true,
			'clip-rule'         => true,
		),
		'rect'           => array(
			'class'        => true,
			'id'           => true,
			'x'            => true,
			'y'            => true,
			'width'        => true,
			'height'       => true,
			'rx'           => true,
			'ry'           => true,
			'transform'    => true,
			'fill'         => true,
			'stroke'       => true,
			'stroke-width' => true,
			'opacity'      => true,
		),
		'circle'         => array(
			'class'        => true,
			'id'           => true,
			'cx'           => true,
			'cy'           => true,
			'r'            => true,
			'transform'    => true,
			'fill'         => true,
			'stroke'       => true,
			'stroke-width' => true,
			'opacity'      => true,
		),
		'ellipse'        => array(
			'class'        => true,
			'id'           => true,
			'cx'           => true,
			'cy'           => true,
			'rx'           => true,
			'ry'           => true,
			'transform'    => true,
			'fill'         => true,
			'stroke'       => true,
			'stroke-width' => true,
			'opacity'      => true,
		),
		'line'           => array(
			'class'        => true,
			'id'           => true,
			'x1'           => true,
			'y1'           => true,
			'x2'           => true,
			'y2'           => true,
			'transform'    => true,
			'fill'         => true,
			'stroke'       => true,
			'stroke-width' => true,
			'opacity'      => true,
		),
		'polyline'       => array(
			'class'        => true,
			'id'           => true,
			'points'       => true,
			'transform'    => true,
			'fill'         => true,
			'stroke'       => true,
			'stroke-width' => true,
			'opacity'      => true,
		),
		'polygon'        => array(
			'class'        => true,
			'id'           => true,
			'points'       => true,
			'transform'    => true,
			'fill'         => true,
			'stroke'       => true,
			'stroke-width' => true,
			'opacity'      => true,
		),
		'title'          => array(),
		'desc'           => array(),
		'defs'           => array(),
		'clippath'       => array(
			'id'            => true,
			'clipPathUnits' => true,
		),
		'mask'           => array(
			'id'               => true,
			'x'                => true,
			'y'                => true,
			'width'            => true,
			'height'           => true,
			'maskUnits'        => true,
			'maskContentUnits' => true,
		),
		'symbol'         => array(
			'id'                  => true,
			'viewbox'             => true,
			'preserveaspectratio' => true,
		),
		'use'            => array(
			'href'       => true,
			'xlink:href' => true,
			'x'          => true,
			'y'          => true,
			'width'      => true,
			'height'     => true,
			'transform'  => true,
		),
		'lineargradient' => array(
			'id'                => true,
			'x1'                => true,
			'y1'                => true,
			'x2'                => true,
			'y2'                => true,
			'gradientunits'     => true,
			'gradienttransform' => true,
			'spreadmethod'      => true,
		),
		'radialgradient' => array(
			'id'                => true,
			'cx'                => true,
			'cy'                => true,
			'r'                 => true,
			'fx'                => true,
			'fy'                => true,
			'gradientunits'     => true,
			'gradienttransform' => true,
			'spreadmethod'      => true,
		),
		'stop'           => array(
			'offset'       => true,
			'stop-color'   => true,
			'stop-opacity' => true,
		),
	);
}

function bw_mew_svg_sanitize_content( $content ) {
	$content = (string) $content;
	$content = preg_replace( '/^\xEF\xBB\xBF/', '', $content );
	$content = str_replace( "\0", '', $content );
	$content = preg_replace( '/<\?(?:xml|php).*?\?>/is', '', $content );
	$content = preg_replace( '/<!DOCTYPE.*?>/is', '', $content );
	$content = preg_replace( '/<!ENTITY.*?>/is', '', $content );

	if ( null === $content ) {
		return '';
	}

	return wp_kses( $content, bw_mew_get_svg_allowed_tags(), array( 'http', 'https', 'mailto' ) );
}

function bw_mew_svg_is_valid_document( $svg ) {
	if ( '' === trim( (string) $svg ) ) {
		return false;
	}

	if ( ! class_exists( 'DOMDocument' ) ) {
		return false;
	}

	$previous = libxml_use_internal_errors( true );
	$dom      = new DOMDocument();
	$loaded   = $dom->loadXML( (string) $svg, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_COMPACT );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	if ( ! $loaded || ! $dom->documentElement ) {
		return false;
	}

	if ( 'svg' !== strtolower( $dom->documentElement->nodeName ) ) {
		return false;
	}

	$dangerous_tags = array( 'script', 'foreignobject', 'iframe', 'object', 'embed', 'audio', 'video' );
	$xpath          = new DOMXPath( $dom );
	foreach ( $dangerous_tags as $tag ) {
		if ( $xpath->query( '//*[translate(local-name(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="' . $tag . '"]' )->length > 0 ) {
			return false;
		}
	}

	$nodes = $dom->getElementsByTagName( '*' );
	foreach ( $nodes as $node ) {
		if ( ! $node->hasAttributes() ) {
			continue;
		}

		foreach ( $node->attributes as $attribute ) {
			$attr_name  = strtolower( $attribute->nodeName );
			$attr_value = strtolower( trim( (string) $attribute->nodeValue ) );

			if ( 0 === strpos( $attr_name, 'on' ) ) {
				return false;
			}

			if ( in_array( $attr_name, array( 'href', 'xlink:href' ), true ) ) {
				if ( '' === $attr_value || '#' === $attr_value || 0 === strpos( $attr_value, '#' ) ) {
					continue;
				}

				if ( 0 === strpos( $attr_value, 'javascript:' ) || 0 === strpos( $attr_value, 'data:' ) ) {
					return false;
				}
			}
		}
	}

	return true;
}

function bw_mew_svg_upload_prefilter( $file ) {
	$filename  = isset( $file['name'] ) ? (string) $file['name'] : '';
	$tmp_name  = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';
	$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

	if ( ! in_array( $extension, array( 'svg', 'svgz' ), true ) ) {
		return $file;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		$file['error'] = bw_mew_get_svg_upload_error_message( 'unsafe_content' );
		return $file;
	}

	if ( 'svgz' === $extension ) {
		$file['error'] = bw_mew_get_svg_upload_error_message( 'svgz_not_allowed' );
		return $file;
	}

	if ( '' === $tmp_name || ! is_readable( $tmp_name ) ) {
		$file['error'] = bw_mew_get_svg_upload_error_message( 'read_failed' );
		return $file;
	}

	$raw_content = file_get_contents( $tmp_name );
	if ( false === $raw_content || '' === trim( (string) $raw_content ) ) {
		$file['error'] = bw_mew_get_svg_upload_error_message( 'read_failed' );
		return $file;
	}

	if ( false !== strpos( (string) $raw_content, "\0" ) ) {
		$file['error'] = bw_mew_get_svg_upload_error_message( 'invalid_xml' );
		return $file;
	}

	$sanitized = bw_mew_svg_sanitize_content( (string) $raw_content );
	if ( '' === trim( (string) $sanitized ) ) {
		$file['error'] = bw_mew_get_svg_upload_error_message( 'sanitize_failed' );
		return $file;
	}

	if ( ! bw_mew_svg_is_valid_document( $sanitized ) ) {
		$file['error'] = bw_mew_get_svg_upload_error_message( 'unsafe_content' );
		return $file;
	}

	$bytes_written = file_put_contents( $tmp_name, $sanitized, LOCK_EX );
	if ( false === $bytes_written ) {
		$file['error'] = bw_mew_get_svg_upload_error_message( 'write_failed' );
		return $file;
	}

	return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'bw_mew_svg_upload_prefilter' );

function bw_mew_fix_svg_filetype( $data, $file, $filename, $mimes ) {
	$extension = strtolower( pathinfo( (string) $filename, PATHINFO_EXTENSION ) );

	if ( 'svgz' === $extension ) {
		return $data;
	}

	if ( 'svg' !== $extension ) {
		return $data;
	}

	if ( ! is_string( $file ) || '' === $file || ! is_readable( $file ) ) {
		return $data;
	}

	$content = file_get_contents( $file );
	if ( false === $content ) {
		return $data;
	}

	$sanitized = bw_mew_svg_sanitize_content( (string) $content );
	if ( ! bw_mew_svg_is_valid_document( $sanitized ) ) {
		return $data;
	}

	$filetype = wp_check_filetype( $filename, $mimes );

	if ( ! empty( $filetype['ext'] ) && 'svg' === $filetype['ext'] ) {
		$data['ext']             = 'svg';
		$data['type']            = 'image/svg+xml';
		$data['proper_filename'] = $filename;
	}

	return $data;
}
add_filter( 'wp_check_filetype_and_ext', 'bw_mew_fix_svg_filetype', 10, 4 );

function bw_mew_skip_svg_metadata( $metadata, $attachment_id ) {
	$mime = get_post_mime_type( $attachment_id );

	if ( 'image/svg+xml' === $mime ) {
		return array();
	}

	return $metadata;
}
add_filter( 'wp_generate_attachment_metadata', 'bw_mew_skip_svg_metadata', 10, 2 );
