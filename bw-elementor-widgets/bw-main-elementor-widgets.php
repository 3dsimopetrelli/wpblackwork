<?php
/**
 * Plugin Name: BW Elementor Widgets
 * Description: Collezione di widget personalizzati per Elementor
 * Version: 1.0.0
 * Author: Simone
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Loader dei widget
require_once __DIR__ . '/includes/class-bw-widget-loader.php';

// Registrazione eventuali asset globali
function bw_widgets_register_assets() {
    // Se vuoi aggiungere un CSS/JS condiviso tra tutti i widget
    // wp_register_style('bw-widgets-global', plugins_url('/assets/css/global.css', __FILE__));
    // wp_enqueue_style('bw-widgets-global');
}
add_action('wp_enqueue_scripts', 'bw_widgets_register_assets');
