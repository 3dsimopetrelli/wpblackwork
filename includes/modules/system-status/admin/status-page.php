<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BW_SYSTEM_STATUS_PAGE_SLUG')) {
    define('BW_SYSTEM_STATUS_PAGE_SLUG', 'bw-system-status');
}

if (!defined('BW_SYSTEM_STATUS_CAPABILITY')) {
    define('BW_SYSTEM_STATUS_CAPABILITY', 'manage_options');
}

if (!function_exists('bw_system_status_register_admin_page')) {
    function bw_system_status_register_admin_page()
    {
        add_submenu_page(
            'blackwork-site-settings',
            __('Status', 'bw'),
            __('Status', 'bw'),
            BW_SYSTEM_STATUS_CAPABILITY,
            BW_SYSTEM_STATUS_PAGE_SLUG,
            'bw_system_status_render_admin_page'
        );
    }
}
add_action('admin_menu', 'bw_system_status_register_admin_page', 40);

if (!function_exists('bw_system_status_enqueue_admin_assets')) {
    function bw_system_status_enqueue_admin_assets($hook)
    {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (BW_SYSTEM_STATUS_PAGE_SLUG !== $page) {
            return;
        }

        $script_path = BW_MEW_PATH . 'includes/modules/system-status/admin/assets/system-status-admin.js';
        $script_version = file_exists($script_path) ? filemtime($script_path) : '1.0.0';

        wp_enqueue_script(
            'bw-system-status-admin',
            BW_MEW_URL . 'includes/modules/system-status/admin/assets/system-status-admin.js',
            ['jquery'],
            $script_version,
            true
        );

        wp_localize_script(
            'bw-system-status-admin',
            'bwSystemStatus',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bw_system_status_run_check'),
                'messages' => [
                    'running' => esc_html__('Running system checks…', 'bw'),
                    'failed' => esc_html__('Unable to run system checks. Please retry.', 'bw'),
                ],
            ]
        );
    }
}
add_action('admin_enqueue_scripts', 'bw_system_status_enqueue_admin_assets');

if (!function_exists('bw_system_status_render_admin_page')) {
    function bw_system_status_render_admin_page()
    {
        if (!current_user_can(BW_SYSTEM_STATUS_CAPABILITY)) {
            return;
        }
        ?>
        <div class="wrap">
            <style>
                #bw-system-status-results { max-width: 1100px; }
                #bw-system-overview { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px; }
                .bw-system-overview-item { padding:8px 10px; border:1px solid #dcdcde; background:#fff; border-radius:4px; }
                #bw-system-cards { display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:12px; margin-top:14px; }
                .bw-system-card { background:#fff; border:1px solid #dcdcde; border-radius:4px; padding:14px; }
                .bw-system-card-header { display:flex; align-items:center; justify-content:space-between; gap:8px; }
                .bw-system-card-title { margin:0; font-size:16px; }
                .bw-system-card-badge { font-weight:600; font-size:12px; padding:2px 7px; border-radius:999px; border:1px solid #dcdcde; }
                .bw-system-card-summary { margin:10px 0; color:#50575e; }
                .bw-system-list { margin:0; padding-left:18px; }
                .bw-system-list li { margin:4px 0; }
                .bw-status-ok { color:#1d7f35; }
                .bw-status-warn { color:#8a6d00; }
                .bw-status-error { color:#b32d2e; }
            </style>
            <h1><?php esc_html_e('Blackwork System Status', 'bw'); ?></h1>
            <p><?php esc_html_e('Owner-friendly diagnostics dashboard with read-only checks for storage, image sizes, database, WordPress environment, and PHP limits.', 'bw'); ?></p>

            <p>
                <button type="button" class="button button-primary" id="bw-system-status-run"><?php esc_html_e('Run System Check', 'bw'); ?></button>
                <button type="button" class="button" id="bw-system-status-refresh"><?php esc_html_e('Force Refresh', 'bw'); ?></button>
            </p>

            <div id="bw-system-status-feedback" class="notice" style="display:none;"><p></p></div>

            <div id="bw-system-status-results" style="display:none;">
                <div id="bw-system-overview">
                    <div class="bw-system-overview-item" data-overview="server">
                        <?php esc_html_e('PHP Limits: -', 'bw'); ?>
                    </div>
                    <div class="bw-system-overview-item" data-overview="database">
                        <?php esc_html_e('Database: -', 'bw'); ?>
                    </div>
                    <div class="bw-system-overview-item" data-overview="media">
                        <?php esc_html_e('Media Storage: -', 'bw'); ?>
                    </div>
                    <div class="bw-system-overview-item" data-overview="wordpress">
                        <?php esc_html_e('WordPress: -', 'bw'); ?>
                    </div>
                </div>

                <p>
                    <strong><?php esc_html_e('Generated at:', 'bw'); ?></strong>
                    <span id="bw-system-generated-at">-</span>
                    &nbsp;|&nbsp;
                    <strong><?php esc_html_e('Source:', 'bw'); ?></strong>
                    <span id="bw-system-source">-</span>
                    &nbsp;|&nbsp;
                    <strong><?php esc_html_e('TTL:', 'bw'); ?></strong>
                    <span id="bw-system-ttl">-</span>
                    &nbsp;|&nbsp;
                    <strong><?php esc_html_e('Execution time:', 'bw'); ?></strong>
                    <span id="bw-system-execution-time">-</span>
                </p>

                <div id="bw-system-cards">
                    <section class="bw-system-card" data-check="media">
                        <div class="bw-system-card-header">
                            <h2 class="bw-system-card-title"><?php esc_html_e('Images', 'bw'); ?></h2>
                            <div>
                                <span class="bw-system-card-badge">-</span>
                                <button type="button" class="button button-secondary bw-system-run-section" data-checks="media"><?php esc_html_e('Run Images Check', 'bw'); ?></button>
                            </div>
                        </div>
                        <p class="bw-system-card-summary">-</p>
                        <ul class="bw-system-list">
                            <li><strong><?php esc_html_e('Total attachments:', 'bw'); ?></strong> <span data-field="media-total-files">-</span></li>
                            <li><strong><?php esc_html_e('Total bytes:', 'bw'); ?></strong> <span data-field="media-total-bytes">-</span></li>
                            <li><strong><?php esc_html_e('JPEG:', 'bw'); ?></strong> <span data-field="media-type-jpeg">-</span></li>
                            <li><strong><?php esc_html_e('PNG:', 'bw'); ?></strong> <span data-field="media-type-png">-</span></li>
                            <li><strong><?php esc_html_e('SVG:', 'bw'); ?></strong> <span data-field="media-type-svg">-</span></li>
                            <li><strong><?php esc_html_e('VIDEO:', 'bw'); ?></strong> <span data-field="media-type-video">-</span></li>
                            <li><strong><?php esc_html_e('WEBP:', 'bw'); ?></strong> <span data-field="media-type-webp">-</span></li>
                            <li><strong><?php esc_html_e('OTHER:', 'bw'); ?></strong> <span data-field="media-type-other">-</span></li>
                        </ul>
                    </section>

                    <section class="bw-system-card" data-check="images">
                        <div class="bw-system-card-header">
                            <h2 class="bw-system-card-title"><?php esc_html_e('Image Sizes', 'bw'); ?></h2>
                            <div>
                                <span class="bw-system-card-badge">-</span>
                                <button type="button" class="button button-secondary bw-system-run-section" data-checks="images"><?php esc_html_e('Run Image Sizes Check', 'bw'); ?></button>
                            </div>
                        </div>
                        <p class="bw-system-card-summary">-</p>
                        <p><strong><?php esc_html_e('Registered sizes:', 'bw'); ?></strong> <span data-field="images-total-sizes">-</span></p>
                        <ul class="bw-system-list" data-field="images-size-list"></ul>
                    </section>

                    <section class="bw-system-card" data-check="database">
                        <div class="bw-system-card-header">
                            <h2 class="bw-system-card-title"><?php esc_html_e('Database', 'bw'); ?></h2>
                            <div>
                                <span class="bw-system-card-badge">-</span>
                                <button type="button" class="button button-secondary bw-system-run-section" data-checks="database"><?php esc_html_e('Run Database Check', 'bw'); ?></button>
                            </div>
                        </div>
                        <p class="bw-system-card-summary">-</p>
                        <ul class="bw-system-list" data-field="database-table-list"></ul>
                    </section>

                    <section class="bw-system-card" data-check="wordpress">
                        <div class="bw-system-card-header">
                            <h2 class="bw-system-card-title"><?php esc_html_e('WordPress', 'bw'); ?></h2>
                            <div>
                                <span class="bw-system-card-badge">-</span>
                                <button type="button" class="button button-secondary bw-system-run-section" data-checks="wordpress"><?php esc_html_e('Run WordPress Check', 'bw'); ?></button>
                            </div>
                        </div>
                        <p class="bw-system-card-summary">-</p>
                        <ul class="bw-system-list" data-field="wordpress-list"></ul>
                    </section>

                    <section class="bw-system-card" data-check="server">
                        <div class="bw-system-card-header">
                            <h2 class="bw-system-card-title"><?php esc_html_e('PHP Limits', 'bw'); ?></h2>
                            <div>
                                <span class="bw-system-card-badge">-</span>
                                <button type="button" class="button button-secondary bw-system-run-section" data-checks="server"><?php esc_html_e('Run PHP Limits Check', 'bw'); ?></button>
                            </div>
                        </div>
                        <p class="bw-system-card-summary">-</p>
                        <ul class="bw-system-list" data-field="server-list"></ul>
                    </section>
                </div>

                <details id="bw-system-debug-details" style="margin-top:14px;">
                    <summary><?php esc_html_e('Show debug details', 'bw'); ?></summary>
                    <p style="margin-top:10px;">
                        <button type="button" class="button" id="bw-system-download-json"><?php esc_html_e('Download JSON', 'bw'); ?></button>
                    </p>
                    <pre id="bw-system-status-json" style="max-height: 380px; overflow:auto; background:#fff; border:1px solid #dcdcde; padding:12px;"></pre>
                </details>
            </div>
        </div>
        <?php
    }
}
