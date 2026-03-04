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
            $core_flags = (isset($_POST['bw_core_flags']) && is_array($_POST['bw_core_flags'])) ? $_POST['bw_core_flags'] : [];
            $enabled = !empty($core_flags['media_folders']) ? 1 : 0;
            $corner_indicator = !empty($core_flags['media_folders_corner_indicator']) ? 1 : 0;
            $badge_tooltip_enabled = isset($_POST['bw_mf_badge_tooltip_enabled']) ? 1 : 0;
            bw_core_update_flags([
                'media_folders' => $enabled,
                'media_folders_corner_indicator' => $corner_indicator,
            ]);
            update_option('bw_mf_badge_tooltip_enabled', $badge_tooltip_enabled ? 1 : 0);

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Media Folders settings saved.', 'bw') . '</p></div>';
        }

        $flags = bw_core_get_flags();
        $enabled = !empty($flags['media_folders']);
        $corner_indicator_enabled = !empty($flags['media_folders_corner_indicator']);
        $badge_tooltip_enabled = bw_mf_get_badge_tooltip_enabled();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Media Folders', 'bw'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('bw_mf_settings_save', 'bw_mf_settings_nonce'); ?>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable Media Folders', 'bw'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="bw_core_flags[media_folders]" value="1" <?php checked($enabled); ?> />
                                    <?php esc_html_e('Enable folder sidebar and media organization in Media Library.', 'bw'); ?>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('When disabled, Media Folders module is a no-op (no assets, no filters, no AJAX endpoints).', 'bw'); ?>
                                </p>
                            </td>
                        </tr>
                        <?php if ($enabled) : ?>
                        <tr>
                            <th scope="row"><?php esc_html_e('Folder assignment corner indicator', 'bw'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="bw_core_flags[media_folders_corner_indicator]" value="1" <?php checked($corner_indicator_enabled); ?> />
                                    <?php esc_html_e('Enable corner indicator on assigned media thumbnails.', 'bw'); ?>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('Shows a small colored corner on media thumbnails when an item is assigned to a folder.', 'bw'); ?>
                                </p>
                            </td>
                        </tr>
                        <?php if ($corner_indicator_enabled) : ?>
                        <tr>
                            <th scope="row"><?php esc_html_e('Show folder name tooltip on badge', 'bw'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="bw_mf_badge_tooltip_enabled" value="1" <?php checked($badge_tooltip_enabled); ?> />
                                    <?php esc_html_e('Enable badge tooltip with folder name.', 'bw'); ?>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('When enabled, hovering the badge shows the folder name.', 'bw'); ?>
                                </p>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary" name="bw_mf_settings_submit" value="1">
                        <?php esc_html_e('Save Settings', 'bw'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }
}
