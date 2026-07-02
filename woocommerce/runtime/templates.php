<?php
/**
 * WooCommerce runtime — template locator + related-products assets.
 *
 * Extracted verbatim from woocommerce-init.php (Phase 3b, BW-TASK-20260629). Function bodies are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Force WooCommerce to use plugin templates before theme overrides.
 *
 * @param string $template      Located template path.
 * @param string $template_name Template name relative to template path.
 * @param string $template_path WooCommerce template path.
 *
 * @return string
 */
function bw_mew_locate_template($template, $template_name, $template_path)
{
    $plugin_template_path = trailingslashit(BW_MEW_PATH . 'woocommerce/templates');

    if ($template_name && file_exists($plugin_template_path . $template_name)) {
        return $plugin_template_path . $template_name;
    }

    return $template;
}

/**
 * Enqueue assets for WooCommerce related products layout.
 */
function bw_mew_enqueue_related_products_assets()
{
    if (!class_exists('WooCommerce') || !is_product()) {
        return;
    }

    wp_enqueue_style('bw-related-products-style');
}
