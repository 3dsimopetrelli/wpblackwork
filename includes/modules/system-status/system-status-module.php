<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!is_admin()) {
    return;
}

require_once __DIR__ . '/admin/status-page.php';
require_once __DIR__ . '/runtime/check-runner.php';
require_once __DIR__ . '/runtime/checks/check-media.php';
require_once __DIR__ . '/runtime/checks/check-database.php';
require_once __DIR__ . '/runtime/checks/check-images.php';
