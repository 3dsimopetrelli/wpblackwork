<?php
/**
 * Blackwork Site Settings — import template registry + import/export page + transfer mode.
 *
 * Extracted verbatim from class-blackwork-site-settings.php (Phase 3a, BW-TASK-20260629). Function bodies are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bw_product_import_template_registry()
{
    $base_dir = BW_MEW_PATH . 'docs/30-features/import-products/templates/';

    return [
        'digital_csv' => [
            'group' => __('Digital Product', 'bw'),
            'label' => __('↓ CSV', 'bw'),
            'filename' => 'blackwork-digital-product-import-template.csv',
            'path' => $base_dir . 'blackwork-digital-product-import-template.csv',
            'content_type' => 'text/csv; charset=utf-8',
        ],
        'digital_md' => [
            'group' => __('Digital Product', 'bw'),
            'label' => __('↓ Guide', 'bw'),
            'filename' => 'blackwork-digital-product-import-guide.md',
            'path' => $base_dir . 'blackwork-digital-product-import-guide.md',
            'content_type' => 'text/markdown; charset=utf-8',
        ],
        'digital_prompt' => [
            'group' => __('Digital Product', 'bw'),
            'label' => __('Copy Prompt', 'bw'),
            'filename' => 'blackwork-digital-product-start-prompt.md',
            'path' => $base_dir . 'blackwork-digital-product-start-prompt.md',
            'content_type' => 'text/markdown; charset=utf-8',
        ],
        'books_csv' => [
            'group' => __('Books', 'bw'),
            'label' => __('↓ CSV', 'bw'),
            'filename' => 'blackwork-book-import-template.csv',
            'path' => $base_dir . 'blackwork-book-import-template.csv',
            'content_type' => 'text/csv; charset=utf-8',
        ],
        'books_md' => [
            'group' => __('Books', 'bw'),
            'label' => __('↓ Guide', 'bw'),
            'filename' => 'blackwork-book-import-guide.md',
            'path' => $base_dir . 'blackwork-book-import-guide.md',
            'content_type' => 'text/markdown; charset=utf-8',
        ],
        'books_prompt' => [
            'group' => __('Books', 'bw'),
            'label' => __('Copy Prompt', 'bw'),
            'filename' => 'blackwork-book-start-prompt.md',
            'path' => $base_dir . 'blackwork-book-start-prompt.md',
            'content_type' => 'text/markdown; charset=utf-8',
        ],
        'prints_csv' => [
            'group' => __('Prints', 'bw'),
            'label' => __('↓ CSV', 'bw'),
            'filename' => 'blackwork-print-import-template.csv',
            'path' => $base_dir . 'blackwork-print-import-template.csv',
            'content_type' => 'text/csv; charset=utf-8',
        ],
        'prints_md' => [
            'group' => __('Prints', 'bw'),
            'label' => __('↓ Guide', 'bw'),
            'filename' => 'blackwork-print-import-guide.md',
            'path' => $base_dir . 'blackwork-print-import-guide.md',
            'content_type' => 'text/markdown; charset=utf-8',
        ],
        'prints_prompt' => [
            'group' => __('Prints', 'bw'),
            'label' => __('Copy Prompt', 'bw'),
            'filename' => 'blackwork-print-start-prompt.md',
            'path' => $base_dir . 'blackwork-print-start-prompt.md',
            'content_type' => 'text/markdown; charset=utf-8',
        ],
    ];
}

function bw_product_import_template_download_url($template_key)
{
    $template_key = sanitize_key((string) $template_key);
    $templates = bw_product_import_template_registry();

    if (!isset($templates[$template_key])) {
        return '';
    }

    return wp_nonce_url(
        bw_product_import_export_admin_url([
            'product_flow' => 'import',
            'bw_download_import_template' => $template_key,
        ]),
        'bw_download_import_template_' . $template_key
    );
}

function bw_product_import_prompt_payload()
{
    $templates = bw_product_import_template_registry();
    $payload = [];

    foreach (['digital_prompt', 'books_prompt', 'prints_prompt'] as $template_key) {
        if (!isset($templates[$template_key])) {
            continue;
        }

        $template = $templates[$template_key];
        $file_path = isset($template['path']) ? (string) $template['path'] : '';
        if ($file_path === '' || !file_exists($file_path) || !is_readable($file_path)) {
            continue;
        }

        $payload[$template_key] = [
            'group' => isset($template['group']) ? (string) $template['group'] : '',
            'content' => file_get_contents($file_path),
        ];
    }

    return $payload;
}

function bw_product_import_template_maybe_download()
{
    if (!is_admin()) {
        return;
    }

    $template_key = isset($_GET['bw_download_import_template']) ? sanitize_key(wp_unslash($_GET['bw_download_import_template'])) : '';
    if ($template_key === '') {
        return;
    }

    if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download import templates.', 'bw'), 403);
    }

    $templates = bw_product_import_template_registry();
    if (!isset($templates[$template_key])) {
        wp_die(esc_html__('Unknown import template requested.', 'bw'), 400);
    }

    if (
        !isset($_GET['_wpnonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_GET['_wpnonce'])),
            'bw_download_import_template_' . $template_key
        )
    ) {
        wp_die(esc_html__('Invalid download nonce. Please retry from the admin page.', 'bw'), 403);
    }

    $template = $templates[$template_key];
    $file_path = isset($template['path']) ? (string) $template['path'] : '';
    $file_name = isset($template['filename']) ? (string) $template['filename'] : '';
    $content_type = isset($template['content_type']) ? (string) $template['content_type'] : 'text/plain; charset=utf-8';

    if ($file_path === '' || $file_name === '' || !file_exists($file_path) || !is_readable($file_path)) {
        wp_die(esc_html__('The requested import template is not available.', 'bw'), 404);
    }

    nocache_headers();
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: attachment; filename="' . rawurlencode($file_name) . '"');
    header('Content-Length: ' . (string) filesize($file_path));
    header('X-Content-Type-Options: nosniff');
    readfile($file_path);
    exit;
}

function bw_product_transfer_get_active_mode()
{
    $mode = 'export';

    $is_import_upload_action = isset($_POST['bw_import_action'])
        && 'upload' === sanitize_key(wp_unslash($_POST['bw_import_action']));

    if (isset($_POST['bw_import_upload_submit']) || isset($_POST['bw_import_run']) || $is_import_upload_action) {
        $mode = 'import';
    } elseif (isset($_POST['bw_export_products_submit'])) {
        $mode = 'export';
    } elseif (isset($_GET['product_flow'])) {
        $requested = sanitize_key(wp_unslash($_GET['product_flow']));
        if (in_array($requested, ['export', 'import'], true)) {
            $mode = $requested;
        }
    }

    return $mode;
}
