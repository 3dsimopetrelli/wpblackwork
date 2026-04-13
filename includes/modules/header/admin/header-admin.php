<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_header_admin_menu')) {
    function bw_header_admin_menu()
    {
        add_submenu_page(
            'blackwork-site-settings',
            __('Header', 'bw'),
            __('Header', 'bw'),
            'manage_options',
            'bw-header-settings',
            'bw_header_render_admin_page'
        );
    }
}
add_action('admin_menu', 'bw_header_admin_menu', 20);

if (!function_exists('bw_header_admin_enqueue_assets')) {
    function bw_header_admin_enqueue_assets($hook)
    {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $is_header_page = ('bw-header-settings' === $page);
        if (!$is_header_page) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_script(
            'bw-header-admin',
            BW_MEW_URL . 'includes/modules/header/admin/header-admin.js',
            ['jquery', 'media-editor', 'media-models', 'media-views'],
            file_exists(BW_MEW_PATH . 'includes/modules/header/admin/header-admin.js') ? filemtime(BW_MEW_PATH . 'includes/modules/header/admin/header-admin.js') : '1.0.0',
            true
        );
    }
}
add_action('admin_enqueue_scripts', 'bw_header_admin_enqueue_assets');

if (!function_exists('bw_header_get_menu_options')) {
    function bw_header_get_menu_options()
    {
        $menus = wp_get_nav_menus();
        $out = [0 => __('Select a menu', 'bw')];

        if (is_wp_error($menus) || empty($menus)) {
            return $out;
        }

        foreach ($menus as $menu) {
            $out[(int) $menu->term_id] = $menu->name;
        }

        return $out;
    }
}

if (!function_exists('bw_header_get_page_options')) {
    function bw_header_get_page_options()
    {
        $pages = get_posts([
            'post_type' => 'page',
            'post_status' => ['publish', 'draft', 'private', 'pending', 'future'],
            'posts_per_page' => -1,
            'orderby' => 'menu_order title',
            'order' => 'ASC',
        ]);

        if (empty($pages)) {
            return [];
        }

        $out = [];
        foreach ($pages as $page) {
            $out[(int) $page->ID] = $page->post_title !== '' ? $page->post_title : sprintf(__('Page #%d', 'bw'), (int) $page->ID);
        }

        return $out;
    }
}

if (!function_exists('bw_header_render_media_field')) {
    function bw_header_render_media_field($name, $attachment_id, $label, $svg_code_name = '', $svg_code = '')
    {
        $attachment_id = absint($attachment_id);
        $url = $attachment_id ? wp_get_attachment_url($attachment_id) : '';
        $field_id_base = sanitize_html_class(str_replace(['[', ']'], ['-', ''], $name));
        ?>
        <tr>
            <th scope="row"><label><?php echo esc_html($label); ?></label></th>
            <td>
                <input type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($attachment_id); ?>" class="bw-header-media-id" />
                <div class="bw-header-media-preview">
                    <?php if ($url) : ?>
                        <img src="<?php echo esc_url($url); ?>" alt="" class="bw-header-media-preview-image" />
                    <?php endif; ?>
                </div>
                <div class="bw-header-media-actions">
                    <button type="button" class="button button-secondary bw-header-media-upload"><?php esc_html_e('Upload/Select', 'bw'); ?></button>
                    <button type="button" class="button button-secondary bw-header-media-remove" <?php disabled(!$attachment_id); ?>><?php esc_html_e('Remove', 'bw'); ?></button>
                </div>
                <p class="description"><?php esc_html_e('Supports SVG and regular image attachments.', 'bw'); ?></p>
                <?php if ($svg_code_name !== '') : ?>
                    <div class="bw-header-inline-svg" style="margin-top:12px;max-width:760px;">
                        <label for="<?php echo esc_attr($field_id_base . '-svg-code'); ?>" style="display:block;font-weight:600;margin-bottom:6px;">
                            <?php esc_html_e('SVG code', 'bw'); ?>
                        </label>
                        <textarea
                            id="<?php echo esc_attr($field_id_base . '-svg-code'); ?>"
                            name="<?php echo esc_attr($svg_code_name); ?>"
                            rows="8"
                            class="large-text code"
                            style="width:100%;"
                            placeholder="<?php echo esc_attr('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor">...</svg>'); ?>"
                        ><?php echo esc_textarea($svg_code); ?></textarea>
                        <p class="description"><?php esc_html_e('Optional. If filled, this inline SVG code overrides the uploaded attachment for this icon.', 'bw'); ?></p>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }
}

if (!function_exists('bw_header_render_admin_page')) {
    function bw_header_render_admin_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = bw_header_get_settings();
        $menus = bw_header_get_menu_options();
        $pages = bw_header_get_page_options();
        $hero_overlap_page_ids = array_map('intval', isset($settings['hero_overlap']['page_ids']) ? (array) $settings['hero_overlap']['page_ids'] : []);
        $active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!in_array($active_tab, ['general', 'scroll', 'hero-overlap'], true)) {
            $active_tab = 'general';
        }
        $tab_links = [
            'general' => admin_url('admin.php?page=bw-header-settings&tab=general#bw-header-tab-general'),
            'scroll' => admin_url('admin.php?page=bw-header-settings&tab=scroll#bw-header-tab-scroll'),
            'hero-overlap' => admin_url('admin.php?page=bw-header-settings&tab=hero-overlap#bw-header-tab-hero-overlap'),
        ];
        $settings_updated = isset($_GET['settings-updated']) && '' !== sanitize_key(wp_unslash($_GET['settings-updated'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ?>
        <div class="wrap bw-admin-root bw-admin-page bw-admin-page-header">
            <div class="bw-admin-header">
                <h1 class="bw-admin-title"><?php esc_html_e('Header', 'bw'); ?></h1>
                <p class="bw-admin-subtitle"><?php esc_html_e('Configure header layout, navigation, media assets, and responsive behavior.', 'bw'); ?></p>
            </div>
            <?php if ($settings_updated) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Header settings saved successfully.', 'bw'); ?></p>
                </div>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php settings_fields('bw_header_settings_group'); ?>
                <div class="bw-admin-action-bar">
                    <div class="bw-admin-action-meta">
                        <?php esc_html_e('Configure header layout, navigation, and responsive behavior.', 'bw'); ?>
                    </div>
                    <div class="bw-admin-action-buttons">
                        <?php submit_button(__('Save Settings', 'bw'), 'primary', 'submit', false); ?>
                    </div>
                </div>

                <section class="bw-admin-card">
                    <h2 class="bw-admin-card-title"><?php esc_html_e('Sections', 'bw'); ?></h2>
                    <p class="bw-admin-card-helper"><?php esc_html_e('Switch between core header configuration and scroll behavior controls.', 'bw'); ?></p>
                    <nav class="nav-tab-wrapper bw-admin-tabs" id="bw-header-tabs">
                        <a href="<?php echo esc_url($tab_links['general']); ?>" data-target="#bw-header-tab-general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('General', 'bw'); ?></a>
                        <a href="<?php echo esc_url($tab_links['scroll']); ?>" data-target="#bw-header-tab-scroll" class="nav-tab <?php echo $active_tab === 'scroll' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Header Scroll', 'bw'); ?></a>
                        <a href="<?php echo esc_url($tab_links['hero-overlap']); ?>" data-target="#bw-header-tab-hero-overlap" class="nav-tab <?php echo $active_tab === 'hero-overlap' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Hero Overlap', 'bw'); ?></a>
                    </nav>
                </section>
                <div id="bw-header-tab-general" class="bw-header-tab-panel <?php echo $active_tab === 'general' ? 'is-active' : ''; ?>" style="<?php echo $active_tab === 'general' ? '' : 'display:none;'; ?>">
                    <div class="bw-admin-card">
                        <h2 class="bw-admin-card-title"><?php esc_html_e('Core Settings', 'bw'); ?></h2>
                        <p class="bw-admin-card-helper"><?php esc_html_e('Core header activation and base visual behavior.', 'bw'); ?></p>
                        <table class="form-table bw-admin-table bw-admin-form-grid" role="presentation">
                        <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Custom Header', 'bw'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[enabled]" value="1" <?php checked(!empty($settings['enabled'])); ?> />
                                <?php esc_html_e('Render custom header on frontend', 'bw'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-title"><?php esc_html_e('Header Title', 'bw'); ?></label></th>
                        <td>
                            <input id="bw-header-title" type="text" class="regular-text" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[header_title]" value="<?php echo esc_attr($settings['header_title']); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-background-color"><?php esc_html_e('Header Background Color', 'bw'); ?></label></th>
                        <td>
                            <input id="bw-header-background-color" type="color" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[background_color]" value="<?php echo esc_attr($settings['background_color']); ?>" />
                            <p class="description"><?php esc_html_e('Used when Smart Header Scroll is OFF. If Smart Header Scroll is ON, Header Scroll colors/opacities take precedence.', 'bw'); ?></p>
                            <label style="display:block;margin-top:8px;">
                                <input type="checkbox" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[background_transparent]" value="1" <?php checked(!empty($settings['background_transparent'])); ?> />
                                <?php esc_html_e('Transparent background (override color in General tab)', 'bw'); ?>
                            </label>
                        </td>
                    </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bw-admin-card">
                    <h2 class="bw-admin-card-title"><?php esc_html_e('Branding', 'bw'); ?></h2>
                    <p class="bw-admin-card-helper"><?php esc_html_e('Set logo assets and sizing for header branding.', 'bw'); ?></p>
                    <table class="form-table bw-admin-table bw-admin-form-grid" role="presentation">
                        <tbody>
                    <?php bw_header_render_media_field(BW_HEADER_OPTION_KEY . '[logo_attachment_id]', $settings['logo_attachment_id'], __('Logo Upload', 'bw')); ?>
                    <tr>
                        <th scope="row"><label for="bw-header-logo-width"><?php esc_html_e('Logo Width (px)', 'bw'); ?></label></th>
                        <td><input id="bw-header-logo-width" type="number" min="10" max="1200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[logo_width]" value="<?php echo esc_attr((int) $settings['logo_width']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-logo-height"><?php esc_html_e('Logo Height (px)', 'bw'); ?></label></th>
                        <td>
                            <input id="bw-header-logo-height" type="number" min="0" max="1200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[logo_height]" value="<?php echo esc_attr((int) $settings['logo_height']); ?>" />
                            <p class="description"><?php esc_html_e('Use 0 for auto height.', 'bw'); ?></p>
                        </td>
                    </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bw-admin-card">
                    <h2 class="bw-admin-card-title"><?php esc_html_e('Navigation', 'bw'); ?></h2>
                    <p class="bw-admin-card-helper"><?php esc_html_e('Configure menus, breakpoint, and desktop inner spacing.', 'bw'); ?></p>
                    <table class="form-table bw-admin-table bw-admin-form-grid" role="presentation">
                        <tbody>
                    <tr>
                        <th scope="row"><label for="bw-header-desktop-menu"><?php esc_html_e('Desktop Menu', 'bw'); ?></label></th>
                        <td>
                            <select id="bw-header-desktop-menu" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[menus][desktop_menu_id]">
                                <?php foreach ($menus as $menu_id => $menu_name) : ?>
                                    <option value="<?php echo esc_attr($menu_id); ?>" <?php selected((int) $settings['menus']['desktop_menu_id'], (int) $menu_id); ?>><?php echo esc_html($menu_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-mobile-menu"><?php esc_html_e('Mobile Menu', 'bw'); ?></label></th>
                        <td>
                            <select id="bw-header-mobile-menu" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[menus][mobile_menu_id]">
                                <?php foreach ($menus as $menu_id => $menu_name) : ?>
                                    <option value="<?php echo esc_attr($menu_id); ?>" <?php selected((int) $settings['menus']['mobile_menu_id'], (int) $menu_id); ?>><?php echo esc_html($menu_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e('If empty, desktop menu is used.', 'bw'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-mobile-breakpoint"><?php esc_html_e('Mobile Breakpoint (px)', 'bw'); ?></label></th>
                        <td><input id="bw-header-mobile-breakpoint" type="number" min="320" max="1920" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[breakpoints][mobile]" value="<?php echo esc_attr((int) $settings['breakpoints']['mobile']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-inner-padding-unit"><?php esc_html_e('Header Inner Padding Unit', 'bw'); ?></label></th>
                        <td>
                            <select id="bw-header-inner-padding-unit" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[inner_padding_unit]">
                                <option value="px" <?php selected($settings['inner_padding_unit'], 'px'); ?>>px</option>
                                <option value="%" <?php selected($settings['inner_padding_unit'], '%'); ?>>%</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Header Inner Padding (Top Right Bottom Left)', 'bw'); ?></th>
                        <td>
                            <input type="number" step="0.1" min="0" max="400" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[inner_padding][top]" value="<?php echo esc_attr((float) $settings['inner_padding']['top']); ?>" placeholder="Top" style="width:90px;" />
                            <input type="number" step="0.1" min="0" max="400" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[inner_padding][right]" value="<?php echo esc_attr((float) $settings['inner_padding']['right']); ?>" placeholder="Right" style="width:90px;margin-left:8px;" />
                            <input type="number" step="0.1" min="0" max="400" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[inner_padding][bottom]" value="<?php echo esc_attr((float) $settings['inner_padding']['bottom']); ?>" placeholder="Bottom" style="width:90px;margin-left:8px;" />
                            <input type="number" step="0.1" min="0" max="400" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[inner_padding][left]" value="<?php echo esc_attr((float) $settings['inner_padding']['left']); ?>" placeholder="Left" style="width:90px;margin-left:8px;" />
                            <p class="description"><?php esc_html_e('Controls padding of .bw-custom-header__inner (the container shown in DevTools).', 'bw'); ?></p>
                        </td>
                    </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bw-admin-card">
                    <h2 class="bw-admin-card-title"><?php esc_html_e('Responsive', 'bw'); ?></h2>
                    <p class="bw-admin-card-helper"><?php esc_html_e('Configure mobile icon assets.', 'bw'); ?></p>
                    <table class="form-table bw-admin-table bw-admin-form-grid" role="presentation">
                        <tbody>
                    <?php bw_header_render_media_field(BW_HEADER_OPTION_KEY . '[icons][mobile_hamburger_attachment_id]', $settings['icons']['mobile_hamburger_attachment_id'], __('Mobile Hamburger SVG', 'bw'), BW_HEADER_OPTION_KEY . '[icons][mobile_hamburger_svg_code]', isset($settings['icons']['mobile_hamburger_svg_code']) ? $settings['icons']['mobile_hamburger_svg_code'] : ''); ?>
                    <?php bw_header_render_media_field(BW_HEADER_OPTION_KEY . '[icons][mobile_search_attachment_id]', $settings['icons']['mobile_search_attachment_id'], __('Mobile Search SVG', 'bw'), BW_HEADER_OPTION_KEY . '[icons][mobile_search_svg_code]', isset($settings['icons']['mobile_search_svg_code']) ? $settings['icons']['mobile_search_svg_code'] : ''); ?>
                    <?php bw_header_render_media_field(BW_HEADER_OPTION_KEY . '[icons][mobile_cart_attachment_id]', $settings['icons']['mobile_cart_attachment_id'], __('Mobile Cart SVG', 'bw'), BW_HEADER_OPTION_KEY . '[icons][mobile_cart_svg_code]', isset($settings['icons']['mobile_cart_svg_code']) ? $settings['icons']['mobile_cart_svg_code'] : ''); ?>
                    <tr>
                        <th scope="row"><?php esc_html_e('Mobile Layout Controls', 'bw'); ?></th>
                        <td>
                            <p class="description"><?php esc_html_e('Mobile spacing and badge position are now fixed in code to keep the header layout consistent.', 'bw'); ?></p>
                        </td>
                    </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bw-admin-card">
                    <h2 class="bw-admin-card-title"><?php esc_html_e('Links & Labels', 'bw'); ?></h2>
                    <p class="bw-admin-card-helper"><?php esc_html_e('Set header labels and destination links for account and cart actions.', 'bw'); ?></p>
                    <table class="form-table bw-admin-table bw-admin-form-grid" role="presentation">
                        <tbody>
                    <tr>
                        <th scope="row"><label for="bw-header-label-search"><?php esc_html_e('Search Label', 'bw'); ?></label></th>
                        <td><input id="bw-header-label-search" type="text" class="regular-text" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[labels][search]" value="<?php echo esc_attr($settings['labels']['search']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-label-account"><?php esc_html_e('Account Label', 'bw'); ?></label></th>
                        <td><input id="bw-header-label-account" type="text" class="regular-text" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[labels][account]" value="<?php echo esc_attr($settings['labels']['account']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-label-cart"><?php esc_html_e('Cart Label', 'bw'); ?></label></th>
                        <td><input id="bw-header-label-cart" type="text" class="regular-text" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[labels][cart]" value="<?php echo esc_attr($settings['labels']['cart']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-link-account"><?php esc_html_e('Account Link', 'bw'); ?></label></th>
                        <td><input id="bw-header-link-account" type="text" class="regular-text" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[links][account]" value="<?php echo esc_attr($settings['links']['account']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-link-cart"><?php esc_html_e('Cart Link', 'bw'); ?></label></th>
                        <td><input id="bw-header-link-cart" type="text" class="regular-text" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[links][cart]" value="<?php echo esc_attr($settings['links']['cart']); ?>" /></td>
                    </tr>
                        </tbody>
                    </table>
                </div>
                </div>

                <div id="bw-header-tab-scroll" class="bw-header-tab-panel <?php echo $active_tab === 'scroll' ? 'is-active' : ''; ?>" style="<?php echo $active_tab === 'scroll' ? '' : 'display:none;'; ?>">
                    <div class="bw-admin-card">
                        <h2 class="bw-admin-card-title"><?php esc_html_e('Scroll Behavior', 'bw'); ?></h2>
                        <p class="bw-admin-card-helper"><?php esc_html_e('Control smart scroll behavior and interaction thresholds.', 'bw'); ?></p>
                        <table class="form-table bw-admin-table bw-admin-form-grid" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><?php esc_html_e('Enable Smart Header Scroll', 'bw'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[features][smart_scroll]" value="1" <?php checked(!empty($settings['features']['smart_scroll'])); ?> />
                                        <?php esc_html_e('Hide on scroll down, show on scroll up', 'bw'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-scroll-down-threshold"><?php esc_html_e('Scroll Down Threshold (px)', 'bw'); ?></label></th>
                                <td>
                                    <input id="bw-header-scroll-down-threshold" type="number" min="0" max="2000" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][scroll_down_threshold]" value="<?php echo esc_attr((int) $settings['smart_header']['scroll_down_threshold']); ?>" />
                                    <p class="description"><?php esc_html_e('Header hides after this scroll distance when moving down.', 'bw'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-scroll-up-threshold"><?php esc_html_e('Scroll Up Threshold (px)', 'bw'); ?></label></th>
                                <td>
                                    <input id="bw-header-scroll-up-threshold" type="number" min="0" max="2000" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][scroll_up_threshold]" value="<?php echo esc_attr((int) $settings['smart_header']['scroll_up_threshold']); ?>" />
                                    <p class="description"><?php esc_html_e('Minimum upward scroll to show header. Use 0 for immediate show.', 'bw'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-scroll-delta"><?php esc_html_e('Scroll Delta (px)', 'bw'); ?></label></th>
                                <td>
                                    <input id="bw-header-scroll-delta" type="number" min="1" max="100" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][scroll_delta]" value="<?php echo esc_attr((int) $settings['smart_header']['scroll_delta']); ?>" />
                                    <p class="description"><?php esc_html_e('Minimum movement to detect direction changes.', 'bw'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bw-admin-card">
                    <h2 class="bw-admin-card-title"><?php esc_html_e('Scroll Appearance', 'bw'); ?></h2>
                    <p class="bw-admin-card-helper"><?php esc_html_e('Set Smart Header colors and opacity transitions.', 'bw'); ?></p>
                    <table class="form-table bw-admin-table bw-admin-form-grid" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><label for="bw-header-smart-bg-color"><?php esc_html_e('Header BG Color (Smart)', 'bw'); ?></label></th>
                                <td><input id="bw-header-smart-bg-color" type="color" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][header_bg_color]" value="<?php echo esc_attr($settings['smart_header']['header_bg_color']); ?>" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-smart-bg-opacity"><?php esc_html_e('Header BG Opacity (Smart)', 'bw'); ?></label></th>
                                <td>
                                    <input id="bw-header-smart-bg-opacity-range" type="range" min="0" max="1" step="0.01" value="<?php echo esc_attr((float) $settings['smart_header']['header_bg_opacity']); ?>" oninput="document.getElementById('bw-header-smart-bg-opacity').value=this.value;" />
                                    <input id="bw-header-smart-bg-opacity" type="number" min="0" max="1" step="0.01" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][header_bg_opacity]" value="<?php echo esc_attr((float) $settings['smart_header']['header_bg_opacity']); ?>" oninput="document.getElementById('bw-header-smart-bg-opacity-range').value=this.value;" style="width:90px;margin-left:10px;" />
                                    <p class="description"><?php esc_html_e('0 = fully transparent, 1 = fully opaque.', 'bw'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-smart-scrolled-bg-color"><?php esc_html_e('Header BG Color (Scrolled)', 'bw'); ?></label></th>
                                <td><input id="bw-header-smart-scrolled-bg-color" type="color" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][header_scrolled_bg_color]" value="<?php echo esc_attr($settings['smart_header']['header_scrolled_bg_color']); ?>" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-smart-scrolled-bg-opacity"><?php esc_html_e('Header BG Opacity (Scrolled)', 'bw'); ?></label></th>
                                <td>
                                    <input id="bw-header-smart-scrolled-bg-opacity-range" type="range" min="0" max="1" step="0.01" value="<?php echo esc_attr((float) $settings['smart_header']['header_scrolled_bg_opacity']); ?>" oninput="document.getElementById('bw-header-smart-scrolled-bg-opacity').value=this.value;" />
                                    <input id="bw-header-smart-scrolled-bg-opacity" type="number" min="0" max="1" step="0.01" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][header_scrolled_bg_opacity]" value="<?php echo esc_attr((float) $settings['smart_header']['header_scrolled_bg_opacity']); ?>" oninput="document.getElementById('bw-header-smart-scrolled-bg-opacity-range').value=this.value;" style="width:90px;margin-left:10px;" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bw-admin-card">
                    <h2 class="bw-admin-card-title"><?php esc_html_e('Blur Panel', 'bw'); ?></h2>
                    <p class="bw-admin-card-helper"><?php esc_html_e('Configure desktop menu blur panel tint, radius, and padding.', 'bw'); ?></p>
                    <table class="form-table bw-admin-table bw-admin-form-grid" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><?php esc_html_e('Desktop Menu Blur Panel', 'bw'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][menu_blur_enabled]" value="1" <?php checked(!empty($settings['smart_header']['menu_blur_enabled'])); ?> />
                                        <?php esc_html_e('Enable blur panel (same visual logic as legacy Smart Header)', 'bw'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-menu-blur-amount"><?php esc_html_e('Blur Amount (px)', 'bw'); ?></label></th>
                                <td><input id="bw-header-menu-blur-amount" type="number" min="0" max="100" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][menu_blur_amount]" value="<?php echo esc_attr((int) $settings['smart_header']['menu_blur_amount']); ?>" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-menu-blur-radius"><?php esc_html_e('Panel Border Radius (px)', 'bw'); ?></label></th>
                                <td><input id="bw-header-menu-blur-radius" type="number" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][menu_blur_radius]" value="<?php echo esc_attr((int) $settings['smart_header']['menu_blur_radius']); ?>" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-menu-blur-tint-color"><?php esc_html_e('Panel Tint Color', 'bw'); ?></label></th>
                                <td><input id="bw-header-menu-blur-tint-color" type="color" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][menu_blur_tint_color]" value="<?php echo esc_attr($settings['smart_header']['menu_blur_tint_color']); ?>" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-menu-blur-tint-opacity"><?php esc_html_e('Panel Tint Opacity', 'bw'); ?></label></th>
                                <td>
                                    <input id="bw-header-menu-blur-tint-opacity-range" type="range" min="0" max="1" step="0.01" value="<?php echo esc_attr((float) $settings['smart_header']['menu_blur_tint_opacity']); ?>" oninput="document.getElementById('bw-header-menu-blur-tint-opacity').value=this.value;" />
                                    <input id="bw-header-menu-blur-tint-opacity" type="number" min="0" max="1" step="0.01" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][menu_blur_tint_opacity]" value="<?php echo esc_attr((float) $settings['smart_header']['menu_blur_tint_opacity']); ?>" oninput="document.getElementById('bw-header-menu-blur-tint-opacity-range').value=this.value;" style="width:90px;margin-left:10px;" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-menu-blur-scrolled-tint-color"><?php esc_html_e('Panel Tint Color (Scrolled)', 'bw'); ?></label></th>
                                <td><input id="bw-header-menu-blur-scrolled-tint-color" type="color" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][menu_blur_scrolled_tint_color]" value="<?php echo esc_attr($settings['smart_header']['menu_blur_scrolled_tint_color']); ?>" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-menu-blur-scrolled-tint-opacity"><?php esc_html_e('Panel Tint Opacity (Scrolled)', 'bw'); ?></label></th>
                                <td>
                                    <input id="bw-header-menu-blur-scrolled-tint-opacity-range" type="range" min="0" max="1" step="0.01" value="<?php echo esc_attr((float) $settings['smart_header']['menu_blur_scrolled_tint_opacity']); ?>" oninput="document.getElementById('bw-header-menu-blur-scrolled-tint-opacity').value=this.value;" />
                                    <input id="bw-header-menu-blur-scrolled-tint-opacity" type="number" min="0" max="1" step="0.01" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][menu_blur_scrolled_tint_opacity]" value="<?php echo esc_attr((float) $settings['smart_header']['menu_blur_scrolled_tint_opacity']); ?>" oninput="document.getElementById('bw-header-menu-blur-scrolled-tint-opacity-range').value=this.value;" style="width:90px;margin-left:10px;" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Panel Padding (px)', 'bw'); ?></th>
                                <td>
                                    <input type="number" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][menu_blur_padding_top]" value="<?php echo esc_attr((int) $settings['smart_header']['menu_blur_padding_top']); ?>" placeholder="Top" style="width:90px;" />
                                    <input type="number" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][menu_blur_padding_right]" value="<?php echo esc_attr((int) $settings['smart_header']['menu_blur_padding_right']); ?>" placeholder="Right" style="width:90px;margin-left:8px;" />
                                    <input type="number" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][menu_blur_padding_bottom]" value="<?php echo esc_attr((int) $settings['smart_header']['menu_blur_padding_bottom']); ?>" placeholder="Bottom" style="width:90px;margin-left:8px;" />
                                    <input type="number" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][menu_blur_padding_left]" value="<?php echo esc_attr((int) $settings['smart_header']['menu_blur_padding_left']); ?>" placeholder="Left" style="width:90px;margin-left:8px;" />
                                    <p class="description"><?php esc_html_e('Legacy default: 10 10 10 10.', 'bw'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                </div>
                <div id="bw-header-tab-hero-overlap" class="bw-header-tab-panel <?php echo $active_tab === 'hero-overlap' ? 'is-active' : ''; ?>" style="<?php echo $active_tab === 'hero-overlap' ? '' : 'display:none;'; ?>">
                    <div class="bw-admin-card">
                        <h2 class="bw-admin-card-title"><?php esc_html_e('Hero Overlap', 'bw'); ?></h2>
                        <p class="bw-admin-card-helper"><?php esc_html_e('Let the first hero section sit under the header on selected pages while keeping the existing dark-zone detection logic.', 'bw'); ?></p>
                        <table class="form-table bw-admin-table bw-admin-form-grid" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Enable Hero Overlap', 'bw'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[hero_overlap][enabled]" value="1" <?php checked(!empty($settings['hero_overlap']['enabled'])); ?> />
                                            <?php esc_html_e('Overlay the header on top of the first section for selected pages.', 'bw'); ?>
                                        </label>
                                        <p class="description"><?php esc_html_e('The dark background recognition stays the same: automatic detection plus optional .smart-header-dark-zone on the Elementor hero section.', 'bw'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="bw-header-hero-overlap-pages"><?php esc_html_e('Selected Pages', 'bw'); ?></label></th>
                                    <td>
                                        <select
                                            id="bw-header-hero-overlap-pages"
                                            name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[hero_overlap][page_ids][]"
                                            multiple="multiple"
                                        size="12"
                                            style="min-width: 340px; max-width: 100%;"
                                        >
                                            <?php foreach ($pages as $page_id => $page_title) : ?>
                                                <option value="<?php echo esc_attr($page_id); ?>" <?php selected(in_array((int) $page_id, $hero_overlap_page_ids, true)); ?>>
                                                    <?php echo esc_html(sprintf('%s (#%d)', $page_title, (int) $page_id)); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="description"><?php esc_html_e('Use Cmd/Ctrl click to select multiple pages. Only these pages will start with the header above the hero content.', 'bw'); ?></p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="submit">
                    <?php submit_button(__('Save Settings', 'bw')); ?>
                </div>
            </form>
        </div>
        <?php
    }
}
