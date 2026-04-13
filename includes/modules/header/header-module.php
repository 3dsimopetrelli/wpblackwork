<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/admin/settings-schema.php';
require_once __DIR__ . '/admin/header-admin.php';

require_once __DIR__ . '/helpers/menu.php';
require_once __DIR__ . '/helpers/svg.php';

require_once __DIR__ . '/frontend/assets.php';
require_once __DIR__ . '/frontend/header-render.php';
require_once __DIR__ . '/frontend/fragments.php';
require_once __DIR__ . '/frontend/ajax-search.php';

if (!function_exists('bw_header_register_nav_menus')) {
    function bw_header_register_nav_menus()
    {
        register_nav_menus([
            'bw_mobile_menu' => __('Mobile Menu', 'bw'),
            'bw_mobile_footer_menu' => __('Footer Menu', 'bw'),
        ]);
    }
}
add_action('after_setup_theme', 'bw_header_register_nav_menus');
