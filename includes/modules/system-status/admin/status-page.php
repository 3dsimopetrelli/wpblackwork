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
        <div class="wrap bw-admin-root bw-admin-page bw-admin-page-status">
            <div id="bw-system-status-app">
                <div class="bw-system-header">
                    <h1 class="bw-system-title"><?php esc_html_e('Status', 'bw'); ?></h1>
                    <p class="bw-system-subtitle"><?php esc_html_e('Run read-only health checks for storage, database, WordPress, and image configuration.', 'bw'); ?></p>
                </div>

                <div id="bw-system-status-feedback" class="notice" style="display:none;"><p></p></div>
                <div class="bw-system-action-bar">
                    <div class="bw-system-meta">
                        <?php esc_html_e('Last check:', 'bw'); ?> <span id="bw-system-generated-at">-</span>
                        &nbsp;•&nbsp;
                        <?php esc_html_e('Source:', 'bw'); ?> <span id="bw-system-source">-</span>
                        &nbsp;•&nbsp;
                        <?php esc_html_e('TTL:', 'bw'); ?> <span id="bw-system-ttl">-</span>
                        &nbsp;•&nbsp;
                        <?php esc_html_e('Exec:', 'bw'); ?> <span id="bw-system-execution-time">-</span>
                    </div>
                    <div class="bw-system-action-buttons">
                        <button type="button" class="button button-primary" id="bw-system-status-run"><?php esc_html_e('Run full check', 'bw'); ?></button>
                        <button type="button" class="button" id="bw-system-status-refresh"><?php esc_html_e('Force refresh', 'bw'); ?></button>
                    </div>
                </div>

                <div id="bw-system-status-results" style="display:none;">
                    <div class="bw-system-overview-strip" id="bw-system-overview">
                        <div class="bw-overview-pill" data-overview="limits">
                            <div class="bw-overview-pill-label"><?php esc_html_e('Server / PHP Limits', 'bw'); ?></div>
                            <div class="bw-overview-pill-status">-</div>
                            <div class="bw-overview-pill-summary">-</div>
                        </div>
                        <div class="bw-overview-pill" data-overview="database">
                            <div class="bw-overview-pill-label"><?php esc_html_e('Database', 'bw'); ?></div>
                            <div class="bw-overview-pill-status">-</div>
                            <div class="bw-overview-pill-summary">-</div>
                        </div>
                        <div class="bw-overview-pill" data-overview="media">
                            <div class="bw-overview-pill-label"><?php esc_html_e('Media Storage', 'bw'); ?></div>
                            <div class="bw-overview-pill-status">-</div>
                            <div class="bw-overview-pill-summary">-</div>
                        </div>
                        <div class="bw-overview-pill" data-overview="wordpress">
                            <div class="bw-overview-pill-label"><?php esc_html_e('WordPress', 'bw'); ?></div>
                            <div class="bw-overview-pill-status">-</div>
                            <div class="bw-overview-pill-summary">-</div>
                        </div>
                    </div>

                    <div id="bw-system-cards">
                        <section class="bw-system-card" data-check="media">
                            <div class="bw-system-card-top">
                                <div>
                                    <h2 class="bw-system-card-title"><?php esc_html_e('Images & Media Storage', 'bw'); ?></h2>
                                    <p class="bw-system-card-helper"><?php esc_html_e('Understand how much storage your media library uses and where it is concentrated.', 'bw'); ?></p>
                                    <p class="bw-system-card-summary">-</p>
                                </div>
                                <div class="bw-system-stat-grid">
                                    <div class="bw-system-stat"><div class="bw-system-stat-label"><?php esc_html_e('Total files', 'bw'); ?></div><div class="bw-system-stat-value" data-field="media-total-files">-</div></div>
                                    <div class="bw-system-stat"><div class="bw-system-stat-label"><?php esc_html_e('Total size', 'bw'); ?></div><div class="bw-system-stat-value" data-field="media-total-bytes">-</div></div>
                                    <div class="bw-system-stat"><div class="bw-system-stat-label"><?php esc_html_e('Largest file', 'bw'); ?></div><div class="bw-system-stat-value" data-field="media-largest-file">-</div></div>
                                </div>
                                <div class="bw-system-card-actions">
                                    <span class="bw-system-card-badge">-</span>
                                    <div><button type="button" class="button button-secondary bw-system-run-section" data-scope="media"><?php esc_html_e('Run images check', 'bw'); ?></button></div>
                                </div>
                            </div>
                            <ul class="bw-system-list">
                                <li><strong><?php esc_html_e('JPEG:', 'bw'); ?></strong> <span data-field="media-type-jpeg">-</span></li>
                                <li><strong><?php esc_html_e('PNG:', 'bw'); ?></strong> <span data-field="media-type-png">-</span></li>
                                <li><strong><?php esc_html_e('SVG:', 'bw'); ?></strong> <span data-field="media-type-svg">-</span></li>
                                <li><strong><?php esc_html_e('Video:', 'bw'); ?></strong> <span data-field="media-type-video">-</span></li>
                                <li><strong><?php esc_html_e('WebP:', 'bw'); ?></strong> <span data-field="media-type-webp">-</span></li>
                                <li><strong><?php esc_html_e('Other:', 'bw'); ?></strong> <span data-field="media-type-other">-</span></li>
                            </ul>
                            <details class="bw-system-details">
                                <summary><?php esc_html_e('Show details', 'bw'); ?></summary>
                                <div style="margin-top:8px;">
                                    <h4 style="margin:0 0 6px;"><?php esc_html_e('Top 10 largest files', 'bw'); ?></h4>
                                    <ul class="bw-system-list" data-field="media-largest-list"></ul>
                                    <h4 style="margin:10px 0 6px;"><?php esc_html_e('Warnings', 'bw'); ?></h4>
                                    <ul class="bw-system-list" data-field="media-warnings"></ul>
                                </div>
                            </details>
                        </section>

                        <section class="bw-system-card" data-check="images">
                            <div class="bw-system-card-top">
                                <div>
                                    <h2 class="bw-system-card-title"><?php esc_html_e('Image Sizes', 'bw'); ?></h2>
                                    <p class="bw-system-card-helper"><?php esc_html_e('See the registered image sizes WordPress generates for uploads.', 'bw'); ?></p>
                                    <p class="bw-system-card-summary">-</p>
                                </div>
                                <div class="bw-system-stat-grid">
                                    <div class="bw-system-stat"><div class="bw-system-stat-label"><?php esc_html_e('Registered sizes', 'bw'); ?></div><div class="bw-system-stat-value" data-field="images-total-sizes">-</div></div>
                                </div>
                                <div class="bw-system-card-actions">
                                    <span class="bw-system-card-badge">-</span>
                                    <div><button type="button" class="button button-secondary bw-system-run-section" data-scope="images"><?php esc_html_e('Run image sizes check', 'bw'); ?></button></div>
                                    <div style="margin-top:6px;"><button type="button" class="button bw-system-run-section" data-scope="image_sizes_counts"><?php esc_html_e('Compute generated counts', 'bw'); ?></button></div>
                                </div>
                            </div>
                            <details class="bw-system-details">
                                <summary><?php esc_html_e('Show details', 'bw'); ?></summary>
                                <p style="margin-top:8px;"><?php esc_html_e('These are configuration sizes; removing sizes requires a separate optimization task.', 'bw'); ?></p>
                                <p data-field="images-generated-hint" style="color:#646970;">—</p>
                                <table class="bw-system-table">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('Name', 'bw'); ?></th>
                                            <th><?php esc_html_e('Dimensions', 'bw'); ?></th>
                                            <th><?php esc_html_e('Crop', 'bw'); ?></th>
                                            <th><?php esc_html_e('Generated files', 'bw'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody data-field="images-size-table-body"></tbody>
                                </table>
                            </details>
                        </section>

                        <section class="bw-system-card" data-check="database">
                            <div class="bw-system-card-top">
                                <div>
                                    <h2 class="bw-system-card-title"><?php esc_html_e('Database', 'bw'); ?></h2>
                                    <p class="bw-system-card-helper"><?php esc_html_e('Estimate database size and identify large tables.', 'bw'); ?></p>
                                    <p class="bw-system-card-summary">-</p>
                                </div>
                                <div class="bw-system-stat-grid">
                                    <div class="bw-system-stat"><div class="bw-system-stat-label"><?php esc_html_e('Total DB estimate', 'bw'); ?></div><div class="bw-system-stat-value" data-field="database-total-size">-</div></div>
                                    <div class="bw-system-stat"><div class="bw-system-stat-label"><?php esc_html_e('Tables', 'bw'); ?></div><div class="bw-system-stat-value" data-field="database-table-count">-</div></div>
                                    <div class="bw-system-stat"><div class="bw-system-stat-label"><?php esc_html_e('Largest table', 'bw'); ?></div><div class="bw-system-stat-value" data-field="database-largest-table">-</div></div>
                                </div>
                                <div class="bw-system-card-actions">
                                    <span class="bw-system-card-badge">-</span>
                                    <div><button type="button" class="button button-secondary bw-system-run-section" data-scope="database"><?php esc_html_e('Run database check', 'bw'); ?></button></div>
                                </div>
                            </div>
                            <details class="bw-system-details">
                                <summary><?php esc_html_e('Show details', 'bw'); ?></summary>
                                <ul class="bw-system-list" data-field="database-table-list"></ul>
                                <ul class="bw-system-list" data-field="database-autoload"></ul>
                            </details>
                        </section>

                        <section class="bw-system-card" data-check="wordpress">
                            <div class="bw-system-card-top">
                                <div>
                                    <h2 class="bw-system-card-title"><?php esc_html_e('WordPress Environment', 'bw'); ?></h2>
                                    <p class="bw-system-card-helper"><?php esc_html_e('Confirm core versions and safety-relevant configuration.', 'bw'); ?></p>
                                    <p class="bw-system-card-summary">-</p>
                                </div>
                                <div class="bw-system-stat-grid">
                                    <div class="bw-system-stat"><div class="bw-system-stat-label"><?php esc_html_e('WordPress', 'bw'); ?></div><div class="bw-system-stat-value" data-field="wp-version">-</div></div>
                                    <div class="bw-system-stat"><div class="bw-system-stat-label"><?php esc_html_e('PHP', 'bw'); ?></div><div class="bw-system-stat-value" data-field="wp-php-version">-</div></div>
                                    <div class="bw-system-stat"><div class="bw-system-stat-label"><?php esc_html_e('WooCommerce', 'bw'); ?></div><div class="bw-system-stat-value" data-field="wp-wc-version">-</div></div>
                                    <div class="bw-system-stat"><div class="bw-system-stat-label"><?php esc_html_e('WP_DEBUG', 'bw'); ?></div><div class="bw-system-stat-value" data-field="wp-debug">-</div></div>
                                </div>
                                <div class="bw-system-card-actions">
                                    <span class="bw-system-card-badge">-</span>
                                    <div><button type="button" class="button button-secondary bw-system-run-section" data-scope="wordpress"><?php esc_html_e('Run WP check', 'bw'); ?></button></div>
                                </div>
                            </div>
                            <details class="bw-system-details">
                                <summary><?php esc_html_e('Show details', 'bw'); ?></summary>
                                <ul class="bw-system-list" data-field="wordpress-list"></ul>
                                <ul class="bw-system-list" data-field="wordpress-warnings"></ul>
                            </details>
                        </section>

                        <section class="bw-system-card" data-check="limits">
                            <div class="bw-system-card-top">
                                <div>
                                    <h2 class="bw-system-card-title"><?php esc_html_e('PHP Limits', 'bw'); ?></h2>
                                    <p class="bw-system-card-helper"><?php esc_html_e('These are PHP runtime limits (not hosting disk quota).', 'bw'); ?></p>
                                    <p class="bw-system-card-summary">-</p>
                                </div>
                                <div class="bw-system-stat-grid">
                                    <div class="bw-system-stat"><div class="bw-system-stat-label"><?php esc_html_e('upload_max_filesize', 'bw'); ?></div><div class="bw-system-stat-value" data-field="limits-upload">-</div></div>
                                    <div class="bw-system-stat"><div class="bw-system-stat-label"><?php esc_html_e('post_max_size', 'bw'); ?></div><div class="bw-system-stat-value" data-field="limits-post">-</div></div>
                                    <div class="bw-system-stat"><div class="bw-system-stat-label"><?php esc_html_e('memory_limit', 'bw'); ?></div><div class="bw-system-stat-value" data-field="limits-memory">-</div></div>
                                    <div class="bw-system-stat"><div class="bw-system-stat-label"><?php esc_html_e('max_execution_time', 'bw'); ?></div><div class="bw-system-stat-value" data-field="limits-time">-</div></div>
                                </div>
                                <div class="bw-system-card-actions">
                                    <span class="bw-system-card-badge">-</span>
                                    <div><button type="button" class="button button-secondary bw-system-run-section" data-scope="limits"><?php esc_html_e('Run limits check', 'bw'); ?></button></div>
                                </div>
                            </div>
                            <details class="bw-system-details">
                                <summary><?php esc_html_e('Show details', 'bw'); ?></summary>
                                <ul class="bw-system-list" data-field="limits-list"></ul>
                            </details>
                        </section>
                    </div>

                    <div class="bw-system-debug-link">
                        <details id="bw-system-debug-details">
                            <summary><?php esc_html_e('Show debug JSON', 'bw'); ?></summary>
                            <p style="margin-top:10px;">
                                <button type="button" class="button" id="bw-system-download-json"><?php esc_html_e('Download JSON', 'bw'); ?></button>
                            </p>
                            <pre id="bw-system-status-json"></pre>
                        </details>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
