<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/config/feature-flags.php';
require_once __DIR__ . '/cpt/template-cpt.php';
require_once __DIR__ . '/cpt/template-meta.php';
require_once __DIR__ . '/fonts/custom-fonts.php';
require_once __DIR__ . '/integrations/elementor-fonts.php';
require_once __DIR__ . '/runtime/footer-runtime.php';
require_once __DIR__ . '/runtime/template-wrapper.php';
require_once __DIR__ . '/runtime/conditions-engine.php';
require_once __DIR__ . '/runtime/template-resolver.php';
require_once __DIR__ . '/runtime/template-preview.php';
require_once __DIR__ . '/admin/theme-builder-lite-admin.php';

if (is_admin()) {
    require_once __DIR__ . '/admin/bw-templates-list-ux.php';
}
