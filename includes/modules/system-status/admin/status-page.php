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
                #bw-system-status-table .bw-status-badge { font-weight: 600; }
                #bw-system-status-table tr.bw-status-ok .bw-status-badge,
                #bw-system-overview .bw-status-ok { color: #1d7f35; }
                #bw-system-status-table tr.bw-status-warn .bw-status-badge,
                #bw-system-overview .bw-status-warn { color: #8a6d00; }
                #bw-system-status-table tr.bw-status-error .bw-status-badge,
                #bw-system-overview .bw-status-error { color: #b32d2e; }
            </style>
            <h1><?php esc_html_e('Blackwork System Status', 'bw'); ?></h1>
            <p><?php esc_html_e('Run a read-only health snapshot for media storage, database health, server limits, WordPress environment, and registered image sizes.', 'bw'); ?></p>

            <p>
                <button type="button" class="button button-primary" id="bw-system-status-run"><?php esc_html_e('Run System Check', 'bw'); ?></button>
                <button type="button" class="button" id="bw-system-status-refresh"><?php esc_html_e('Force Refresh', 'bw'); ?></button>
            </p>

            <div id="bw-system-status-feedback" class="notice" style="display:none;"><p></p></div>

            <div id="bw-system-status-results" style="display:none; max-width: 980px;">
                <h2><?php esc_html_e('Overview', 'bw'); ?></h2>
                <div id="bw-system-overview" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px;">
                    <div class="bw-system-overview-item" data-overview="server" style="padding:8px 10px;border:1px solid #dcdcde;background:#fff;">
                        <?php esc_html_e('Server: -', 'bw'); ?>
                    </div>
                    <div class="bw-system-overview-item" data-overview="database" style="padding:8px 10px;border:1px solid #dcdcde;background:#fff;">
                        <?php esc_html_e('Database: -', 'bw'); ?>
                    </div>
                    <div class="bw-system-overview-item" data-overview="media" style="padding:8px 10px;border:1px solid #dcdcde;background:#fff;">
                        <?php esc_html_e('Media Storage: -', 'bw'); ?>
                    </div>
                    <div class="bw-system-overview-item" data-overview="wordpress" style="padding:8px 10px;border:1px solid #dcdcde;background:#fff;">
                        <?php esc_html_e('WordPress: -', 'bw'); ?>
                    </div>
                </div>

                <h2><?php esc_html_e('Snapshot', 'bw'); ?></h2>
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

                <table class="widefat striped" id="bw-system-status-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Check', 'bw'); ?></th>
                            <th><?php esc_html_e('Status', 'bw'); ?></th>
                            <th><?php esc_html_e('Summary', 'bw'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr data-check="media">
                            <td><?php esc_html_e('Media Library', 'bw'); ?></td>
                            <td class="bw-status-badge">-</td>
                            <td class="bw-status-summary">-</td>
                        </tr>
                        <tr data-check="database">
                            <td><?php esc_html_e('Database', 'bw'); ?></td>
                            <td class="bw-status-badge">-</td>
                            <td class="bw-status-summary">-</td>
                        </tr>
                        <tr data-check="images">
                            <td><?php esc_html_e('Registered Image Sizes', 'bw'); ?></td>
                            <td class="bw-status-badge">-</td>
                            <td class="bw-status-summary">-</td>
                        </tr>
                        <tr data-check="wordpress">
                            <td><?php esc_html_e('WordPress Environment', 'bw'); ?></td>
                            <td class="bw-status-badge">-</td>
                            <td class="bw-status-summary">-</td>
                        </tr>
                        <tr data-check="server">
                            <td><?php esc_html_e('Server Limits', 'bw'); ?></td>
                            <td class="bw-status-badge">-</td>
                            <td class="bw-status-summary">-</td>
                        </tr>
                    </tbody>
                </table>

                <h2 style="margin-top: 20px;"><?php esc_html_e('Detailed Output', 'bw'); ?></h2>
                <pre id="bw-system-status-json" style="max-height: 480px; overflow:auto; background:#fff; border:1px solid #dcdcde; padding:12px;"></pre>
            </div>
        </div>
        <?php
    }
}
