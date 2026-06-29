<?php
/**
 * Debug-only registered-asset existence validator.
 *
 * Guards against the asset-path regression class documented in
 * BW-TASK-20260629. When a plugin asset is registered with a URL that does not
 * map to a real file on disk, the browser receives an HTTP 404 and the
 * dependent widget silently fails to initialise (e.g. Product Slider / Product
 * Grid not booting because their JS never loaded).
 *
 * This validator runs only when WP_DEBUG and WP_DEBUG_LOG are enabled. It walks
 * every registered style/script handle served from BW_MEW_URL, maps the public
 * URL back to a filesystem path, and logs any handle whose file is missing.
 * The problem therefore surfaces in the debug log during development instead of
 * as a silent production 404. There is zero overhead when WP_DEBUG is off.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bw_validate_registered_assets' ) ) {
	/**
	 * Verify that every registered plugin asset resolves to a file on disk.
	 *
	 * Only assets served from BW_MEW_URL are checked; WordPress core and
	 * third-party handles are ignored. Missing files are reported via
	 * error_log() together with the handle, resolved path and source URL.
	 *
	 * @param string $context Human-readable context label for log messages.
	 *
	 * @return void
	 */
	function bw_validate_registered_assets( $context = '' ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG || ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) {
			return;
		}

		if ( ! defined( 'BW_MEW_URL' ) || ! defined( 'BW_MEW_PATH' ) ) {
			return;
		}

		$collections = array(
			'style'  => wp_styles(),
			'script' => wp_scripts(),
		);

		foreach ( $collections as $type => $collection ) {
			if ( ! $collection instanceof WP_Dependencies ) {
				continue;
			}

			foreach ( $collection->registered as $handle => $dependency ) {
				$src = isset( $dependency->src ) ? (string) $dependency->src : '';

				if ( '' === $src || 0 !== strpos( $src, BW_MEW_URL ) ) {
					continue;
				}

				// Map the public URL back to a filesystem path, dropping any
				// version query string WordPress may have appended.
				$relative = substr( $src, strlen( BW_MEW_URL ) );
				$relative = strtok( $relative, '?' );
				$path     = BW_MEW_PATH . $relative;

				if ( file_exists( $path ) ) {
					continue;
				}

				error_log(
					sprintf(
						'[BW asset validator%s] Missing %s file for handle "%s": %s (src: %s)',
						'' === $context ? '' : ' ' . $context,
						$type,
						$handle,
						$path,
						$src
					)
				);
			}
		}
	}
}

if ( ! function_exists( 'bw_validate_frontend_assets' ) ) {
	/**
	 * Validate plugin assets on the public frontend.
	 *
	 * @return void
	 */
	function bw_validate_frontend_assets() {
		bw_validate_registered_assets( 'frontend' );
	}
}
add_action( 'wp_print_footer_scripts', 'bw_validate_frontend_assets', 999 );

if ( ! function_exists( 'bw_validate_admin_assets' ) ) {
	/**
	 * Validate plugin assets in the admin and Elementor editor.
	 *
	 * @return void
	 */
	function bw_validate_admin_assets() {
		bw_validate_registered_assets( 'admin' );
	}
}
add_action( 'admin_print_footer_scripts', 'bw_validate_admin_assets', 999 );
