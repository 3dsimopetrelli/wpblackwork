<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/data/installer.php';
if (is_admin()) {
    require_once __DIR__ . '/admin/media-folders-settings.php';
}

if (!bw_mf_is_enabled()) {
    return;
}

if (!is_admin()) {
    return;
}

require_once __DIR__ . '/data/taxonomy.php';
require_once __DIR__ . '/data/term-meta.php';
require_once __DIR__ . '/runtime/media-query-filter.php';
require_once __DIR__ . '/runtime/attachment-corner-indicator.php';
require_once __DIR__ . '/runtime/ajax.php';
require_once __DIR__ . '/admin/media-folders-admin.php';
