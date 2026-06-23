<?php
/**
 * Deprecated-widget unregistration.
 *
 * Defensive cleanup that unregisters Elementor widgets removed from this plugin
 * but possibly still registered via stale caches/flows.
 *
 * Extracted verbatim from blackwork-core-plugin.php (Phase 1 bootstrap
 * decomposition, BW-TASK-20260623). Function name and hook registrations are
 * preserved unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defensive cleanup for removed widgets that may still be registered by stale caches/flows.
 *
 * @param mixed $widgets_manager Elementor widgets manager when provided by hook.
 *
 * @return void
 */
function bw_unregister_removed_blackwork_widgets( $widgets_manager = null ) {
	if ( null === $widgets_manager && class_exists( '\Elementor\Plugin' ) ) {
		$widgets_manager = \Elementor\Plugin::instance()->widgets_manager;
	}

	if ( ! is_object( $widgets_manager ) ) {
		return;
	}

	$removed_widgets = array(
		'bw-add-to-cart',
		'bw-add-to-cart-variation',
		'bw-wallpost',
		'bw-slick-slider',
	);

	foreach ( $removed_widgets as $widget_slug ) {
		if ( method_exists( $widgets_manager, 'unregister' ) ) {
			$widgets_manager->unregister( $widget_slug );
			continue;
		}

		if ( method_exists( $widgets_manager, 'unregister_widget_type' ) ) {
			$widgets_manager->unregister_widget_type( $widget_slug );
		}
	}
}

add_action( 'elementor/widgets/register', 'bw_unregister_removed_blackwork_widgets', 999 );
add_action( 'elementor/widgets/widgets_registered', 'bw_unregister_removed_blackwork_widgets', 999 );
