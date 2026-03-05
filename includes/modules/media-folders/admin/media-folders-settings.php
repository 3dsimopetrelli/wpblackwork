<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'bw_mf_register_settings_submenu', 60);

if (!function_exists('bw_mf_get_badge_tooltip_enabled')) {
    function bw_mf_get_badge_tooltip_enabled()
    {
        return absint(get_option('bw_mf_badge_tooltip_enabled', 0)) === 1;
    }
}

if (!function_exists('bw_mf_register_settings_submenu')) {
    function bw_mf_register_settings_submenu()
    {
        add_submenu_page(
            'blackwork-site-settings',
            __('Media Folders', 'bw'),
            __('Media Folders', 'bw'),
            'manage_options',
            'bw-media-folders-settings',
            'bw_mf_render_settings_page'
        );
    }
}

if (!function_exists('bw_mf_render_settings_page')) {
    function bw_mf_render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['bw_mf_settings_submit'])) {
            check_admin_referer('bw_mf_settings_save', 'bw_mf_settings_nonce');
            $core_flags = (isset($_POST['bw_core_flags']) && is_array($_POST['bw_core_flags'])) ? wp_unslash($_POST['bw_core_flags']) : [];
            $enabled = !empty($core_flags['media_folders']) ? 1 : 0;
            $corner_indicator = !empty($core_flags['media_folders_corner_indicator']) ? 1 : 0;
            $use_media = !empty($core_flags['media_folders_use_media']) ? 1 : 0;
            $use_posts = !empty($core_flags['media_folders_use_posts']) ? 1 : 0;
            $use_pages = !empty($core_flags['media_folders_use_pages']) ? 1 : 0;
            $use_products = !empty($core_flags['media_folders_use_products']) ? 1 : 0;
            $badge_tooltip_enabled = isset($_POST['bw_mf_badge_tooltip_enabled']) ? 1 : 0;
            bw_core_update_flags([
                'media_folders' => $enabled,
                'media_folders_corner_indicator' => $corner_indicator,
                'media_folders_use_media' => $use_media,
                'media_folders_use_posts' => $use_posts,
                'media_folders_use_pages' => $use_pages,
                'media_folders_use_products' => $use_products,
            ]);
            update_option('bw_mf_badge_tooltip_enabled', $badge_tooltip_enabled ? 1 : 0);

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Media Folders settings saved.', 'bw') . '</p></div>';
        }

        $flags = bw_core_get_flags();
        $enabled = !empty($flags['media_folders']);
        $corner_indicator_enabled = !empty($flags['media_folders_corner_indicator']);
        $use_media_enabled = !empty($flags['media_folders_use_media']);
        $use_posts_enabled = !empty($flags['media_folders_use_posts']);
        $use_pages_enabled = !empty($flags['media_folders_use_pages']);
        $use_products_enabled = !empty($flags['media_folders_use_products']);
        $badge_tooltip_enabled = bw_mf_get_badge_tooltip_enabled();
        ?>
        <div class="wrap bw-admin-root bw-admin-page bw-admin-page-media-folders">
            <div class="bw-admin-header">
                <h1 class="bw-admin-title"><?php esc_html_e('Media Folders', 'bw'); ?></h1>
                <p class="bw-admin-subtitle"><?php esc_html_e('Manage Media Library folder organization behavior for Blackwork admin users.', 'bw'); ?></p>
            </div>

            <form method="post">
                <?php wp_nonce_field('bw_mf_settings_save', 'bw_mf_settings_nonce'); ?>

                <div class="bw-admin-action-bar">
                    <div class="bw-admin-action-meta">
                        <?php esc_html_e('Changes affect Media Library admin tools only.', 'bw'); ?>
                    </div>
                    <div class="bw-admin-action-buttons">
                        <button type="submit" class="button button-primary" name="bw_mf_settings_submit" value="1">
                            <?php esc_html_e('Save Settings', 'bw'); ?>
                        </button>
                    </div>
                </div>

                <section class="bw-admin-card bw-admin-card-media-folders">
                    <h2 class="bw-admin-card-title"><?php esc_html_e('Module Controls', 'bw'); ?></h2>
                    <p class="bw-admin-card-helper"><?php esc_html_e('Enable or refine folder assignment indicators used in the Media Library.', 'bw'); ?></p>

                    <div class="bw-admin-card-divider bw-admin-field-list">
                        <div class="bw-admin-field-row">
                            <p class="bw-admin-field-title"><?php esc_html_e('Enable Media Folders', 'bw'); ?></p>
                            <label>
                                <input type="checkbox" name="bw_core_flags[media_folders]" value="1" data-bw-mf-master-toggle="1" <?php checked($enabled); ?> />
                                <?php esc_html_e('Enable folder sidebar and media organization in Media Library.', 'bw'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When disabled, Media Folders module is a no-op (no assets, no filters, no AJAX endpoints).', 'bw'); ?>
                            </p>
                        </div>

                        <div class="bw-admin-field-row" data-bw-mf-visible-when-master="1" <?php if (!$enabled) : ?>style="display:none;"<?php endif; ?>>
                            <p class="bw-admin-field-title"><?php esc_html_e('Use folders with', 'bw'); ?></p>
                            <label>
                                <input type="checkbox" name="bw_core_flags[media_folders_use_media]" value="1" <?php checked($use_media_enabled); ?> />
                                <?php esc_html_e('Media', 'bw'); ?>
                            </label><br />
                            <label>
                                <input type="checkbox" name="bw_core_flags[media_folders_use_posts]" value="1" <?php checked($use_posts_enabled); ?> />
                                <?php esc_html_e('Posts', 'bw'); ?>
                            </label><br />
                            <label>
                                <input type="checkbox" name="bw_core_flags[media_folders_use_pages]" value="1" <?php checked($use_pages_enabled); ?> />
                                <?php esc_html_e('Pages', 'bw'); ?>
                            </label><br />
                            <label>
                                <input type="checkbox" name="bw_core_flags[media_folders_use_products]" value="1" <?php checked($use_products_enabled); ?> />
                                <?php esc_html_e('Products', 'bw'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('Enable folder sidebar and list filtering on selected admin list screens.', 'bw'); ?>
                            </p>
                        </div>

                        <div class="bw-admin-field-row" data-bw-mf-visible-when-master="1" <?php if (!$enabled) : ?>style="display:none;"<?php endif; ?>>
                            <p class="bw-admin-field-title"><?php esc_html_e('Folder assignment corner indicator', 'bw'); ?></p>
                            <label>
                                <input type="checkbox" name="bw_core_flags[media_folders_corner_indicator]" value="1" data-bw-mf-corner-toggle="1" <?php checked($corner_indicator_enabled); ?> />
                                <?php esc_html_e('Enable corner indicator on assigned media thumbnails.', 'bw'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('Shows a small colored corner on media thumbnails when an item is assigned to a folder.', 'bw'); ?>
                            </p>
                        </div>

                        <div class="bw-admin-field-row" data-bw-mf-visible-when-corner="1" <?php if (!($enabled && $corner_indicator_enabled)) : ?>style="display:none;"<?php endif; ?>>
                            <p class="bw-admin-field-title"><?php esc_html_e('Show folder name tooltip on badge', 'bw'); ?></p>
                            <label>
                                <input type="checkbox" name="bw_mf_badge_tooltip_enabled" value="1" <?php checked($badge_tooltip_enabled); ?> />
                                <?php esc_html_e('Enable badge tooltip with folder name.', 'bw'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When enabled, hovering the badge shows the folder name.', 'bw'); ?>
                            </p>
                        </div>
                    </div>
                </section>
            </form>
        </div>
        <script>
        (function () {
            var root = document.querySelector('.bw-admin-page-media-folders');
            if (!root) {
                return;
            }

            var master = root.querySelector('input[data-bw-mf-master-toggle="1"]');
            var corner = root.querySelector('input[data-bw-mf-corner-toggle="1"]');
            if (!master) {
                return;
            }

            function syncVisibility() {
                var masterOn = !!master.checked;
                var cornerOn = !!(corner && corner.checked);

                root.querySelectorAll('[data-bw-mf-visible-when-master="1"]').forEach(function (el) {
                    el.style.display = masterOn ? '' : 'none';
                });

                root.querySelectorAll('[data-bw-mf-visible-when-corner="1"]').forEach(function (el) {
                    el.style.display = (masterOn && cornerOn) ? '' : 'none';
                });
            }

            master.addEventListener('change', syncVisibility);
            if (corner) {
                corner.addEventListener('change', syncVisibility);
            }
            syncVisibility();
        })();
        </script>
        <?php
    }
}
