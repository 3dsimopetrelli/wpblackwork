<?php
/**
 * Blackwork Site Settings Page
 *
 * Pagina unificata sotto Settings con tab per Cart Pop-up e BW Coming Soon
 *
 * @package BW_Elementor_Widgets
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registra la pagina Blackwork Site come voce principale nella sidebar
 */
function bw_site_settings_menu() {
    // SVG icona cerchio verde pieno
    $icon_svg = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><circle cx="10" cy="10" r="10" fill="#80FD03"/></svg>');

    add_menu_page(
        'Blackwork Site',           // Page title
        'Blackwork Site',           // Menu title
        'manage_options',           // Capability
        'blackwork-site-settings',  // Menu slug
        'bw_site_settings_page',    // Callback function
        $icon_svg,                  // Icon (cerchio verde #80FD03)
        30                          // Position (dopo Comments)
    );
}
add_action('admin_menu', 'bw_site_settings_menu');

/**
 * Carica gli assets per la pagina admin
 */
function bw_site_settings_admin_assets($hook) {
    // Carica solo nella nostra pagina (toplevel perché è un menu principale)
    if ($hook !== 'toplevel_page_blackwork-site-settings') {
        return;
    }

    // CSS per la pagina admin
    wp_enqueue_style(
        'bw-site-settings-admin',
        BW_MEW_URL . 'admin/css/blackwork-site-settings.css',
        [],
        '1.0.0'
    );

    wp_enqueue_style('wp-color-picker');

    // JavaScript per la pagina admin (se necessario)
    wp_enqueue_script('jquery');
    wp_enqueue_media();
    wp_enqueue_script('wp-color-picker');

    $redirects_script_path = BW_MEW_PATH . 'admin/js/bw-redirects.js';
    $redirects_version     = file_exists($redirects_script_path) ? filemtime($redirects_script_path) : '1.0.0';

    wp_enqueue_script(
        'bw-redirects-admin',
        BW_MEW_URL . 'admin/js/bw-redirects.js',
        ['jquery'],
        $redirects_version,
        true
    );

    // Border toggle script (shared across Cart Pop-up and Site Settings)
    $border_toggle_path = BW_MEW_PATH . 'assets/js/bw-border-toggle-admin.js';
    $border_toggle_version = file_exists($border_toggle_path) ? filemtime($border_toggle_path) : '1.0.0';

    wp_enqueue_script(
        'bw-border-toggle-admin',
        BW_MEW_URL . 'assets/js/bw-border-toggle-admin.js',
        ['jquery'],
        $border_toggle_version,
        true
    );
}
add_action('admin_enqueue_scripts', 'bw_site_settings_admin_assets');

/**
 * Renderizza la pagina delle impostazioni con tab
 */
function bw_site_settings_page() {
    // Verifica permessi
    if (!current_user_can('manage_options')) {
        return;
    }

    // Determina quale tab è attivo
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'cart-popup';

    ?>
    <div class="wrap">
        <h1>Blackwork Site Settings</h1>

        <!-- Tab Navigation -->
        <nav class="nav-tab-wrapper">
            <a href="?page=blackwork-site-settings&tab=cart-popup"
               class="nav-tab <?php echo $active_tab === 'cart-popup' ? 'nav-tab-active' : ''; ?>">
                Cart Pop-up
            </a>
            <a href="?page=blackwork-site-settings&tab=bw-coming-soon"
               class="nav-tab <?php echo $active_tab === 'bw-coming-soon' ? 'nav-tab-active' : ''; ?>">
                BW Coming Soon
            </a>
            <a href="?page=blackwork-site-settings&tab=account-page"
               class="nav-tab <?php echo $active_tab === 'account-page' ? 'nav-tab-active' : ''; ?>">
                Account Page
            </a>
            <a href="?page=blackwork-site-settings&tab=my-account-page"
               class="nav-tab <?php echo $active_tab === 'my-account-page' ? 'nav-tab-active' : ''; ?>">
                My Account Page
            </a>
            <a href="?page=blackwork-site-settings&tab=checkout"
               class="nav-tab <?php echo $active_tab === 'checkout' ? 'nav-tab-active' : ''; ?>">
                Checkout
            </a>
            <a href="?page=blackwork-site-settings&tab=redirect"
               class="nav-tab <?php echo $active_tab === 'redirect' ? 'nav-tab-active' : ''; ?>">
                Redirect
            </a>
            <a href="?page=blackwork-site-settings&tab=import-product"
               class="nav-tab <?php echo $active_tab === 'import-product' ? 'nav-tab-active' : ''; ?>">
                Import Product
            </a>
        </nav>

        <!-- Tab Content -->
        <div class="tab-content">
            <?php
            // Renderizza il contenuto del tab attivo
            if ($active_tab === 'cart-popup') {
                bw_site_render_cart_popup_tab();
            } elseif ($active_tab === 'bw-coming-soon') {
                bw_site_render_coming_soon_tab();
            } elseif ($active_tab === 'account-page') {
                bw_site_render_account_page_tab();
            } elseif ($active_tab === 'my-account-page') {
                bw_site_render_my_account_front_tab();
            } elseif ($active_tab === 'checkout') {
                bw_site_render_checkout_tab();
            } elseif ($active_tab === 'redirect') {
                bw_site_render_redirect_tab();
            } elseif ($active_tab === 'import-product') {
                bw_site_render_import_product_tab();
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Renderizza il tab Account Page
 */
function bw_site_render_account_page_tab() {
    $saved = false;

    if (isset($_POST['bw_account_page_submit'])) {
        check_admin_referer('bw_account_page_save', 'bw_account_page_nonce');

        $login_image          = isset($_POST['bw_account_login_image']) ? esc_url_raw($_POST['bw_account_login_image']) : '';
        $logo                 = isset($_POST['bw_account_logo']) ? esc_url_raw($_POST['bw_account_logo']) : '';
        $facebook             = isset($_POST['bw_account_facebook']) ? 1 : 0;
        $google               = isset($_POST['bw_account_google']) ? 1 : 0;
        $facebook_app_id      = isset($_POST['bw_account_facebook_app_id']) ? sanitize_text_field($_POST['bw_account_facebook_app_id']) : '';
        $facebook_app_secret  = isset($_POST['bw_account_facebook_app_secret']) ? sanitize_text_field($_POST['bw_account_facebook_app_secret']) : '';
        $google_client_id     = isset($_POST['bw_account_google_client_id']) ? sanitize_text_field($_POST['bw_account_google_client_id']) : '';
        $google_client_secret = isset($_POST['bw_account_google_client_secret']) ? sanitize_text_field($_POST['bw_account_google_client_secret']) : '';
        $description          = isset($_POST['bw_account_description']) ? wp_kses_post($_POST['bw_account_description']) : '';
        $back_text            = isset($_POST['bw_account_back_text']) ? sanitize_text_field($_POST['bw_account_back_text']) : 'go back to store';
        $back_url             = isset($_POST['bw_account_back_url']) ? esc_url_raw($_POST['bw_account_back_url']) : '';
        $passwordless_url     = isset($_POST['bw_account_passwordless_url']) ? esc_url_raw($_POST['bw_account_passwordless_url']) : '';

        update_option('bw_account_login_image', $login_image);
        update_option('bw_account_logo', $logo);
        update_option('bw_account_facebook', $facebook);
        update_option('bw_account_google', $google);
        update_option('bw_account_facebook_app_id', $facebook_app_id);
        update_option('bw_account_facebook_app_secret', $facebook_app_secret);
        update_option('bw_account_google_client_id', $google_client_id);
        update_option('bw_account_google_client_secret', $google_client_secret);
        update_option('bw_account_description', $description);
        update_option('bw_account_back_text', $back_text);
        update_option('bw_account_back_url', $back_url);
        update_option('bw_account_passwordless_url', $passwordless_url);

        $saved = true;
    }

    $login_image          = get_option('bw_account_login_image', '');
    $logo                 = get_option('bw_account_logo', '');
    $facebook             = (int) get_option('bw_account_facebook', 0);
    $google               = (int) get_option('bw_account_google', 0);
    $facebook_app_id      = get_option('bw_account_facebook_app_id', '');
    $facebook_app_secret  = get_option('bw_account_facebook_app_secret', '');
    $google_client_id     = get_option('bw_account_google_client_id', '');
    $google_client_secret = get_option('bw_account_google_client_secret', '');
    $description          = get_option('bw_account_description', '');
    $back_text            = get_option('bw_account_back_text', 'go back to store');
    $back_url             = get_option('bw_account_back_url', '');
    $passwordless_url     = get_option('bw_account_passwordless_url', '');

    $facebook_redirect = function_exists('bw_mew_get_social_redirect_uri') ? bw_mew_get_social_redirect_uri('facebook') : add_query_arg('bw_social_login_callback', 'facebook', wc_get_page_permalink('myaccount'));
    $google_redirect   = function_exists('bw_mew_get_social_redirect_uri') ? bw_mew_get_social_redirect_uri('google') : add_query_arg('bw_social_login_callback', 'google', wc_get_page_permalink('myaccount'));
    ?>
    <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Impostazioni salvate con successo!</strong></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('bw_account_page_save', 'bw_account_page_nonce'); ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="bw_account_login_image">Login Image (cover)</label>
                </th>
                <td>
                    <input type="text" id="bw_account_login_image" name="bw_account_login_image" value="<?php echo esc_attr($login_image); ?>" class="regular-text" />
                    <button type="button" class="button bw-media-upload" data-target="#bw_account_login_image">Seleziona immagine</button>
                    <p class="description">Immagine di copertina mostrata nella metà sinistra.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_account_logo">Logo</label>
                </th>
                <td>
                    <input type="text" id="bw_account_logo" name="bw_account_logo" value="<?php echo esc_attr($logo); ?>" class="regular-text" />
                    <button type="button" class="button bw-media-upload" data-target="#bw_account_logo">Seleziona logo</button>
                    <p class="description">Logo mostrato sopra il form di login.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Social login toggle</th>
                <td>
                    <label style="display:block; margin-bottom:8px;">
                        <input type="checkbox" id="bw_account_facebook" name="bw_account_facebook" value="1" <?php checked(1, $facebook); ?> />
                        Enable Facebook Login
                    </label>
                    <label style="display:block;">
                        <input type="checkbox" id="bw_account_google" name="bw_account_google" value="1" <?php checked(1, $google); ?> />
                        Enable Google Login
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_account_description">Testo descrizione</label>
                </th>
                <td>
                    <textarea id="bw_account_description" name="bw_account_description" rows="4" class="large-text"><?php echo esc_textarea($description); ?></textarea>
                    <p class="description">Paragrafo mostrato sotto il pulsante "Log in Without Password".</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_account_facebook_app_id">Facebook App ID</label>
                </th>
                <td>
                    <input type="text" id="bw_account_facebook_app_id" name="bw_account_facebook_app_id" value="<?php echo esc_attr($facebook_app_id); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_account_facebook_app_secret">Facebook App Secret</label>
                </th>
                <td>
                    <input type="text" id="bw_account_facebook_app_secret" name="bw_account_facebook_app_secret" value="<?php echo esc_attr($facebook_app_secret); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">Facebook Redirect URI</th>
                <td>
                    <input type="text" readonly class="regular-text" value="<?php echo esc_url($facebook_redirect); ?>" />
                    <p class="description">Usa questo URL nel pannello Facebook per configurare il redirect dell'app.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_account_google_client_id">Google Client ID</label>
                </th>
                <td>
                    <input type="text" id="bw_account_google_client_id" name="bw_account_google_client_id" value="<?php echo esc_attr($google_client_id); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_account_google_client_secret">Google Client Secret</label>
                </th>
                <td>
                    <input type="text" id="bw_account_google_client_secret" name="bw_account_google_client_secret" value="<?php echo esc_attr($google_client_secret); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">Google Redirect URI</th>
                <td>
                    <input type="text" readonly class="regular-text" value="<?php echo esc_url($google_redirect); ?>" />
                    <p class="description">Configura questo indirizzo tra gli URI autorizzati della console Google.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_account_back_text">Testo link "Go back to store"</label>
                </th>
                <td>
                    <input type="text" id="bw_account_back_text" name="bw_account_back_text" value="<?php echo esc_attr($back_text); ?>" class="regular-text" placeholder="go back to store" />
                    <p class="description">Testo mostrato in fondo al layout di login.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_account_back_url">URL link "Go back to store"</label>
                </th>
                <td>
                    <input type="url" id="bw_account_back_url" name="bw_account_back_url" value="<?php echo esc_attr($back_url); ?>" class="regular-text" placeholder="<?php echo esc_url(home_url('/')); ?>" />
                    <p class="description">Lascia vuoto per usare l'home URL del sito.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_account_passwordless_url">URL "Log in Without Password"</label>
                </th>
                <td>
                    <input type="url" id="bw_account_passwordless_url" name="bw_account_passwordless_url" value="<?php echo esc_attr($passwordless_url); ?>" class="regular-text" placeholder="<?php echo esc_url(wp_login_url()); ?>" />
                    <p class="description">Imposta il link da usare per il login senza password o magic link.</p>
                </td>
            </tr>
        </table>

        <?php submit_button('Salva impostazioni', 'primary', 'bw_account_page_submit'); ?>
    </form>

    <script>
        jQuery(document).ready(function($) {
            $('.bw-media-upload').on('click', function(e) {
                e.preventDefault();

                const targetInput = $(this).data('target');
                const frame = wp.media({
                    title: 'Seleziona immagine',
                    button: { text: 'Usa questa immagine' },
                    multiple: false
                });

                frame.on('select', function() {
                    const attachment = frame.state().get('selection').first().toJSON();
                    $(targetInput).val(attachment.url);
                });

                frame.open();
            });
        });
    </script>
    <?php
}

/**
 * Render the My Account front-end customization tab.
 */
function bw_site_render_my_account_front_tab() {
    $saved = false;

    if ( isset( $_POST['bw_myaccount_content_submit'] ) ) {
        check_admin_referer( 'bw_myaccount_front_save', 'bw_myaccount_front_nonce' );

        $black_box_text = isset( $_POST['bw_myaccount_black_box_text'] )
            ? wp_kses_post( wp_unslash( $_POST['bw_myaccount_black_box_text'] ) )
            : '';

        update_option( 'bw_myaccount_black_box_text', $black_box_text );

        $saved = true;
    }

    $black_box_text = get_option(
        'bw_myaccount_black_box_text',
        __( 'Your mockups will always be here, available to download. Please enjoy them!', 'bw' )
    );
    ?>
    <?php if ( $saved ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><strong><?php esc_html_e( 'Impostazioni salvate con successo!', 'bw' ); ?></strong></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field( 'bw_myaccount_front_save', 'bw_myaccount_front_nonce' ); ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="bw_myaccount_black_box_text"><?php esc_html_e( 'Testo Box Nero (My Account)', 'bw' ); ?></label>
                </th>
                <td>
                    <textarea
                        id="bw_myaccount_black_box_text"
                        name="bw_myaccount_black_box_text"
                        rows="6"
                        class="large-text"
                    ><?php echo esc_textarea( $black_box_text ); ?></textarea>
                    <p class="description">
                        <?php esc_html_e( 'Contenuto mostrato nel box nero in alto alla dashboard My Account. Puoi utilizzare HTML semplice; il testo verrà sanificato.', 'bw' ); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button( __( 'Salva impostazioni', 'bw' ), 'primary', 'bw_myaccount_content_submit' ); ?>
    </form>
    <?php
}

/**
 * Render the Checkout customization tab.
 */
function bw_site_render_checkout_tab() {
    $saved = false;

    if ( isset( $_POST['bw_checkout_settings_submit'] ) ) {
        check_admin_referer( 'bw_checkout_settings_save', 'bw_checkout_settings_nonce' );

        $logo                 = isset( $_POST['bw_checkout_logo'] ) ? esc_url_raw( wp_unslash( $_POST['bw_checkout_logo'] ) ) : '';
        $logo_align           = isset( $_POST['bw_checkout_logo_align'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_checkout_logo_align'] ) ) : 'left';
        $logo_width           = isset( $_POST['bw_checkout_logo_width'] ) ? absint( $_POST['bw_checkout_logo_width'] ) : 200;
        $logo_padding_top     = isset( $_POST['bw_checkout_logo_padding_top'] ) ? absint( $_POST['bw_checkout_logo_padding_top'] ) : 0;
        $logo_padding_right   = isset( $_POST['bw_checkout_logo_padding_right'] ) ? absint( $_POST['bw_checkout_logo_padding_right'] ) : 0;
        $logo_padding_bottom  = isset( $_POST['bw_checkout_logo_padding_bottom'] ) ? absint( $_POST['bw_checkout_logo_padding_bottom'] ) : 30;
        $logo_padding_left    = isset( $_POST['bw_checkout_logo_padding_left'] ) ? absint( $_POST['bw_checkout_logo_padding_left'] ) : 0;
        $show_order_heading   = isset( $_POST['bw_checkout_show_order_heading'] ) ? '1' : '0';
        $page_bg              = isset( $_POST['bw_checkout_page_bg'] ) ? sanitize_hex_color( wp_unslash( $_POST['bw_checkout_page_bg'] ) ) : '';
        $grid_bg              = isset( $_POST['bw_checkout_grid_bg'] ) ? sanitize_hex_color( wp_unslash( $_POST['bw_checkout_grid_bg'] ) ) : '';
        $left_bg              = isset( $_POST['bw_checkout_left_bg_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['bw_checkout_left_bg_color'] ) ) : '';
        $right_bg             = isset( $_POST['bw_checkout_right_bg_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['bw_checkout_right_bg_color'] ) ) : '';
        $right_sticky_top     = isset( $_POST['bw_checkout_right_sticky_top'] ) ? absint( $_POST['bw_checkout_right_sticky_top'] ) : 20;
        $right_padding_top    = isset( $_POST['bw_checkout_right_padding_top'] ) ? absint( $_POST['bw_checkout_right_padding_top'] ) : 0;
        $right_padding_right  = isset( $_POST['bw_checkout_right_padding_right'] ) ? absint( $_POST['bw_checkout_right_padding_right'] ) : 0;
        $right_padding_bottom = isset( $_POST['bw_checkout_right_padding_bottom'] ) ? absint( $_POST['bw_checkout_right_padding_bottom'] ) : 0;
        $right_padding_left   = isset( $_POST['bw_checkout_right_padding_left'] ) ? absint( $_POST['bw_checkout_right_padding_left'] ) : 28;
        $border_color         = isset( $_POST['bw_checkout_border_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['bw_checkout_border_color'] ) ) : '';
        $legal_text           = isset( $_POST['bw_checkout_legal_text'] ) ? wp_kses_post( wp_unslash( $_POST['bw_checkout_legal_text'] ) ) : '';
        $left_width_percent   = isset( $_POST['bw_checkout_left_width'] ) ? absint( $_POST['bw_checkout_left_width'] ) : 62;
        $right_width_percent  = isset( $_POST['bw_checkout_right_width'] ) ? absint( $_POST['bw_checkout_right_width'] ) : 38;
        $thumb_ratio          = isset( $_POST['bw_checkout_thumb_ratio'] ) ? sanitize_key( wp_unslash( $_POST['bw_checkout_thumb_ratio'] ) ) : 'square';
        $thumb_width          = isset( $_POST['bw_checkout_thumb_width'] ) ? absint( $_POST['bw_checkout_thumb_width'] ) : 110;
        $footer_text          = isset( $_POST['bw_checkout_footer_text'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_checkout_footer_text'] ) ) : '';

        if ( ! in_array( $thumb_ratio, [ 'square', 'portrait', 'landscape' ], true ) ) {
            $thumb_ratio = 'square';
        }

        // Ensure thumb_width is within reasonable bounds
        if ( $thumb_width < 50 ) {
            $thumb_width = 50;
        }
        if ( $thumb_width > 300 ) {
            $thumb_width = 300;
        }

        $page_bg      = $page_bg ?: '#ffffff';
        $grid_bg      = $grid_bg ?: '#ffffff';
        $left_bg      = $left_bg ?: '#ffffff';
        $right_bg     = $right_bg ?: 'transparent';
        $border_color = $border_color ?: '#262626';

        if ( ! in_array( $logo_align, [ 'left', 'center', 'right' ], true ) ) {
            $logo_align = 'left';
        }

        if ( function_exists( 'bw_mew_normalize_checkout_column_widths' ) ) {
            $widths              = bw_mew_normalize_checkout_column_widths( $left_width_percent, $right_width_percent );
            $left_width_percent  = $widths['left'];
            $right_width_percent = $widths['right'];
        }

        update_option( 'bw_checkout_logo', $logo );
        update_option( 'bw_checkout_logo_align', $logo_align );
        update_option( 'bw_checkout_logo_width', $logo_width );
        update_option( 'bw_checkout_logo_padding_top', $logo_padding_top );
        update_option( 'bw_checkout_logo_padding_right', $logo_padding_right );
        update_option( 'bw_checkout_logo_padding_bottom', $logo_padding_bottom );
        update_option( 'bw_checkout_logo_padding_left', $logo_padding_left );
        update_option( 'bw_checkout_show_order_heading', $show_order_heading );
        update_option( 'bw_checkout_page_bg', $page_bg );
        update_option( 'bw_checkout_grid_bg', $grid_bg );
        update_option( 'bw_checkout_left_bg_color', $left_bg );
        update_option( 'bw_checkout_right_bg_color', $right_bg );
        update_option( 'bw_checkout_right_sticky_top', $right_sticky_top );
        update_option( 'bw_checkout_right_padding_top', $right_padding_top );
        update_option( 'bw_checkout_right_padding_right', $right_padding_right );
        update_option( 'bw_checkout_right_padding_bottom', $right_padding_bottom );
        update_option( 'bw_checkout_right_padding_left', $right_padding_left );
        update_option( 'bw_checkout_border_color', $border_color );
        update_option( 'bw_checkout_legal_text', $legal_text );
        update_option( 'bw_checkout_left_width', $left_width_percent );
        update_option( 'bw_checkout_right_width', $right_width_percent );
        update_option( 'bw_checkout_thumb_ratio', $thumb_ratio );
        update_option( 'bw_checkout_thumb_width', $thumb_width );
        update_option( 'bw_checkout_footer_text', $footer_text );

        $saved = true;
    }

    $logo                = get_option( 'bw_checkout_logo', '' );
    $logo_align          = get_option( 'bw_checkout_logo_align', 'left' );
    if ( ! in_array( $logo_align, [ 'left', 'center', 'right' ], true ) ) {
        $logo_align = 'left';
    }
    $logo_width          = get_option( 'bw_checkout_logo_width', 200 );
    $logo_padding_top    = get_option( 'bw_checkout_logo_padding_top', 0 );
    $logo_padding_right  = get_option( 'bw_checkout_logo_padding_right', 0 );
    $logo_padding_bottom = get_option( 'bw_checkout_logo_padding_bottom', 30 );
    $logo_padding_left   = get_option( 'bw_checkout_logo_padding_left', 0 );
    $show_order_heading  = get_option( 'bw_checkout_show_order_heading', '1' );
    $page_bg             = get_option( 'bw_checkout_page_bg', get_option( 'bw_checkout_page_bg_color', '#ffffff' ) );
    $grid_bg             = get_option( 'bw_checkout_grid_bg', get_option( 'bw_checkout_grid_bg_color', '#ffffff' ) );
    $left_bg             = get_option( 'bw_checkout_left_bg_color', '#ffffff' );
    $right_bg            = get_option( 'bw_checkout_right_bg_color', 'transparent' );
    $right_sticky_top    = get_option( 'bw_checkout_right_sticky_top', 20 );
    $right_padding_top   = get_option( 'bw_checkout_right_padding_top', 0 );
    $right_padding_right = get_option( 'bw_checkout_right_padding_right', 0 );
    $right_padding_bottom = get_option( 'bw_checkout_right_padding_bottom', 0 );
    $right_padding_left  = get_option( 'bw_checkout_right_padding_left', 28 );
    $border_color        = get_option( 'bw_checkout_border_color', '#262626' );
    $legal_text          = get_option( 'bw_checkout_legal_text', '' );
    $left_width_percent  = get_option( 'bw_checkout_left_width', 62 );
    $right_width_percent = get_option( 'bw_checkout_right_width', 38 );
    $thumb_ratio         = get_option( 'bw_checkout_thumb_ratio', 'square' );
    $thumb_width         = get_option( 'bw_checkout_thumb_width', 110 );
    $footer_text         = get_option( 'bw_checkout_footer_text', '' );
    ?>

    <?php if ( $saved ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Impostazioni salvate con successo!</strong></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field( 'bw_checkout_settings_save', 'bw_checkout_settings_nonce' ); ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="bw_checkout_logo">Logo Checkout</label>
                </th>
                <td>
                    <input type="text" id="bw_checkout_logo" name="bw_checkout_logo" value="<?php echo esc_attr( $logo ); ?>" class="regular-text" />
                    <button type="button" class="button bw-media-upload" data-target="#bw_checkout_logo">Seleziona immagine</button>
                    <p class="description">Logo mostrato sopra il layout di checkout.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_logo_align">Checkout Logo Alignment</label>
                </th>
                <td>
                    <select id="bw_checkout_logo_align" name="bw_checkout_logo_align">
                        <option value="left" <?php selected( $logo_align, 'left' ); ?>>Left</option>
                        <option value="center" <?php selected( $logo_align, 'center' ); ?>>Center</option>
                        <option value="right" <?php selected( $logo_align, 'right' ); ?>>Right</option>
                    </select>
                    <p class="description">Posizione orizzontale del logo nel checkout.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label>Larghezza Logo</label>
                </th>
                <td>
                    <input type="number" name="bw_checkout_logo_width" value="<?php echo esc_attr( $logo_width ); ?>" min="50" max="800" style="width: 100px;" /> px
                    <p class="description">Larghezza massima del logo (default: 200px).</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label>Padding Logo</label>
                </th>
                <td>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <label style="display: inline-flex; align-items: center; gap: 5px;">
                            Top: <input type="number" name="bw_checkout_logo_padding_top" value="<?php echo esc_attr( $logo_padding_top ); ?>" min="0" max="200" style="width: 70px;" /> px
                        </label>
                        <label style="display: inline-flex; align-items: center; gap: 5px;">
                            Right: <input type="number" name="bw_checkout_logo_padding_right" value="<?php echo esc_attr( $logo_padding_right ); ?>" min="0" max="200" style="width: 70px;" /> px
                        </label>
                        <label style="display: inline-flex; align-items: center; gap: 5px;">
                            Bottom: <input type="number" name="bw_checkout_logo_padding_bottom" value="<?php echo esc_attr( $logo_padding_bottom ); ?>" min="0" max="200" style="width: 70px;" /> px
                        </label>
                        <label style="display: inline-flex; align-items: center; gap: 5px;">
                            Left: <input type="number" name="bw_checkout_logo_padding_left" value="<?php echo esc_attr( $logo_padding_left ); ?>" min="0" max="200" style="width: 70px;" /> px
                        </label>
                    </div>
                    <p class="description">Spazi intorno al logo (Top, Right, Bottom, Left).</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_show_order_heading">Mostra titolo "Your order"</label>
                </th>
                <td>
                    <label style="display: inline-flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="bw_checkout_show_order_heading" name="bw_checkout_show_order_heading" value="1" <?php checked( $show_order_heading, '1' ); ?> />
                        <span style="font-weight: 500;">Attiva</span>
                    </label>
                    <p class="description">Mostra o nascondi il titolo "Your order" nella colonna destra.</p>
                </td>
            </tr>
            <tr class="bw-section-break">
                <th scope="row" colspan="2" style="padding-bottom:0;">
                    <h3 style="margin:0;">Colori di sfondo checkout</h3>
                    <p class="description" style="margin-top:6px;">Gestisci il colore della pagina e del contenitore griglia per evitare stacchi visivi tra le colonne.</p>
                </th>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_page_bg">Checkout Page Background</label>
                </th>
                <td>
                    <input type="text" id="bw_checkout_page_bg" name="bw_checkout_page_bg" value="<?php echo esc_attr( $page_bg ); ?>" class="bw-color-picker" data-default-color="#ffffff" />
                    <p class="description">Colore di sfondo della pagina checkout (body/wrapper).</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_grid_bg">Checkout Grid Background</label>
                </th>
                <td>
                    <input type="text" id="bw_checkout_grid_bg" name="bw_checkout_grid_bg" value="<?php echo esc_attr( $grid_bg ); ?>" class="bw-color-picker" data-default-color="#ffffff" />
                    <p class="description">Colore di sfondo del contenitore griglia checkout (.bw-checkout-grid).</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_page_bg">Checkout Page Background</label>
                </th>
                <td>
                    <input type="text" id="bw_checkout_page_bg" name="bw_checkout_page_bg" value="<?php echo esc_attr( $page_bg ); ?>" class="bw-color-picker" data-default-color="#ffffff" />
                    <p class="description">Colore di sfondo della pagina checkout (body/wrapper).</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_grid_bg">Checkout Grid Background</label>
                </th>
                <td>
                    <input type="text" id="bw_checkout_grid_bg" name="bw_checkout_grid_bg" value="<?php echo esc_attr( $grid_bg ); ?>" class="bw-color-picker" data-default-color="#ffffff" />
                    <p class="description">Colore di sfondo del contenitore griglia checkout.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_left_bg_color">Background colonna sinistra</label>
                </th>
                <td>
                    <input type="text" id="bw_checkout_left_bg_color" name="bw_checkout_left_bg_color" value="<?php echo esc_attr( $left_bg ); ?>" class="bw-color-picker" data-default-color="#ffffff" />
                    <p class="description">Colore di sfondo della colonna principale con i campi checkout.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_right_bg_color">Background colonna destra (riepilogo)</label>
                </th>
                <td>
                    <input type="text" id="bw_checkout_right_bg_color" name="bw_checkout_right_bg_color" value="<?php echo esc_attr( $right_bg ); ?>" class="bw-color-picker" data-default-color="transparent" />
                    <p class="description">Colore di sfondo del riepilogo ordine sticky.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_right_sticky_top">Right Column Sticky Offset Top (px)</label>
                </th>
                <td>
                    <input type="number" id="bw_checkout_right_sticky_top" name="bw_checkout_right_sticky_top" value="<?php echo esc_attr( absint( $right_sticky_top ) ); ?>" min="0" step="1" style="width: 90px;" />
                    <p class="description">Controls the top offset for the sticky order summary (right column) on desktop.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_left_width">Larghezza colonna sinistra (%)</label>
                </th>
                <td>
                    <input type="number" id="bw_checkout_left_width" name="bw_checkout_left_width" value="<?php echo esc_attr( $left_width_percent ); ?>" min="10" max="90" step="1" style="width: 90px;" />
                    <p class="description">Percentuale dedicata al form (default 62%).</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_right_width">Larghezza colonna destra (%)</label>
                </th>
                <td>
                    <input type="number" id="bw_checkout_right_width" name="bw_checkout_right_width" value="<?php echo esc_attr( $right_width_percent ); ?>" min="10" max="90" step="1" style="width: 90px;" />
                    <p class="description">Percentuale dedicata al riepilogo (default 38%). Se la somma supera il 100%, verrà bilanciata automaticamente.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Padding colonna destra (px)</th>
                <td>
                    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                        <label for="bw_checkout_right_padding_top" style="display: inline-flex; align-items: center; gap: 6px;">
                            <span>Top</span>
                            <input type="number" id="bw_checkout_right_padding_top" name="bw_checkout_right_padding_top" value="<?php echo esc_attr( $right_padding_top ); ?>" min="0" max="200" style="width: 80px;" />
                        </label>
                        <label for="bw_checkout_right_padding_right" style="display: inline-flex; align-items: center; gap: 6px;">
                            <span>Right</span>
                            <input type="number" id="bw_checkout_right_padding_right" name="bw_checkout_right_padding_right" value="<?php echo esc_attr( $right_padding_right ); ?>" min="0" max="200" style="width: 80px;" />
                        </label>
                        <label for="bw_checkout_right_padding_bottom" style="display: inline-flex; align-items: center; gap: 6px;">
                            <span>Bottom</span>
                            <input type="number" id="bw_checkout_right_padding_bottom" name="bw_checkout_right_padding_bottom" value="<?php echo esc_attr( $right_padding_bottom ); ?>" min="0" max="200" style="width: 80px;" />
                        </label>
                        <label for="bw_checkout_right_padding_left" style="display: inline-flex; align-items: center; gap: 6px;">
                            <span>Left</span>
                            <input type="number" id="bw_checkout_right_padding_left" name="bw_checkout_right_padding_left" value="<?php echo esc_attr( $right_padding_left ); ?>" min="0" max="200" style="width: 80px;" />
                        </label>
                    </div>
                    <p class="description">Imposta il padding della colonna destra (riepilogo ordine) su desktop e mobile.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_thumb_ratio">Order Item Thumbnail Format (Nails)</label>
                </th>
                <td>
                    <select id="bw_checkout_thumb_ratio" name="bw_checkout_thumb_ratio">
                        <option value="square" <?php selected( $thumb_ratio, 'square' ); ?>>Square (1:1)</option>
                        <option value="portrait" <?php selected( $thumb_ratio, 'portrait' ); ?>>Portrait (2:3)</option>
                        <option value="landscape" <?php selected( $thumb_ratio, 'landscape' ); ?>>Landscape (3:2)</option>
                    </select>
                    <p class="description">Formato proporzioni miniature prodotto nel riepilogo ordine (consigliato per immagini "nails").</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_thumb_width">Tab Nails Width (px)</label>
                </th>
                <td>
                    <input type="number" id="bw_checkout_thumb_width" name="bw_checkout_thumb_width" value="<?php echo esc_attr( $thumb_width ); ?>" min="50" max="300" step="1" style="width: 90px;" />
                    <span style="margin-left: 5px;">px</span>
                    <p class="description">Larghezza delle miniature prodotto nel checkout (min: 50px, max: 300px, default: 110px). Le immagini vengono ridimensionate automaticamente mantenendo la qualità.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_border_color">Colore bordi centrali / separatore</label>
                </th>
                <td>
                    <input type="text" id="bw_checkout_border_color" name="bw_checkout_border_color" value="<?php echo esc_attr( $border_color ); ?>" class="bw-color-picker" data-default-color="#262626" />
                    <p class="description">Colore del bordo verticale tra le due colonne.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_legal_text">Testo informativo legale</label>
                </th>
                <td>
                    <textarea id="bw_checkout_legal_text" name="bw_checkout_legal_text" rows="6" class="large-text"><?php echo esc_textarea( $legal_text ); ?></textarea>
                    <p class="description">Testo mostrato sotto i metodi di pagamento; supporta link e HTML consentito.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_footer_text">Testo footer Copyright</label>
                </th>
                <td>
                    <input type="text" id="bw_checkout_footer_text" name="bw_checkout_footer_text" value="<?php echo esc_attr( $footer_text ); ?>" class="regular-text" />
                    <p class="description">Testo mostrato nel footer del checkout accanto a "Copyright © 2025,". Es: "Bendito Mockup. All rights reserved."</p>
                </td>
            </tr>
        </table>

        <?php submit_button( 'Salva impostazioni', 'primary', 'bw_checkout_settings_submit' ); ?>
    </form>

    <script>
        jQuery(document).ready(function($) {
            $('.bw-media-upload').on('click', function(e) {
                e.preventDefault();

                const targetInput = $(this).data('target');
                const frame = wp.media({
                    title: 'Seleziona immagine',
                    button: { text: 'Usa questa immagine' },
                    multiple: false
                });

                frame.on('select', function() {
                    const attachment = frame.state().get('selection').first().toJSON();
                    $(targetInput).val(attachment.url);
                });

                frame.open();
            });

            $('.bw-color-picker').wpColorPicker();
        });
    </script>
    <?php
}

/**
 * Renderizza il tab Cart Pop-up
 */
function bw_site_render_cart_popup_tab() {
    // Salva le impostazioni se il form è stato inviato
    $saved = false;
    if (isset($_POST['bw_cart_popup_submit'])) {
        $saved = bw_cart_popup_save_settings();
    }

    // Recupera le impostazioni correnti
    $active = get_option('bw_cart_popup_active', 0);
    $show_floating_trigger = get_option('bw_cart_popup_show_floating_trigger', 0);
    $panel_width = get_option('bw_cart_popup_panel_width', 400);
    $overlay_color = get_option('bw_cart_popup_overlay_color', '#000000');
    $overlay_opacity = get_option('bw_cart_popup_overlay_opacity', 0.5);
    $panel_bg = get_option('bw_cart_popup_panel_bg', '#ffffff');
    $checkout_text = get_option('bw_cart_popup_checkout_text', 'Proceed to checkout');
    // RIMOSSO: checkout_url personalizzato - si usa sempre wc_get_checkout_url()
    $checkout_color = get_option('bw_cart_popup_checkout_color', '#28a745');
    $continue_text = get_option('bw_cart_popup_continue_text', 'Continue shopping');
    $continue_url = get_option('bw_cart_popup_continue_url', '');
    $continue_color = get_option('bw_cart_popup_continue_color', '#6c757d');
    $additional_svg = get_option('bw_cart_popup_additional_svg', '');
    $empty_cart_svg = get_option('bw_cart_popup_empty_cart_svg', '');
    $svg_black = get_option('bw_cart_popup_svg_black', 0);

    // Margin per Cart Icon SVG
    $cart_icon_margin_top = get_option('bw_cart_popup_cart_icon_margin_top', 0);
    $cart_icon_margin_right = get_option('bw_cart_popup_cart_icon_margin_right', 0);
    $cart_icon_margin_bottom = get_option('bw_cart_popup_cart_icon_margin_bottom', 0);
    $cart_icon_margin_left = get_option('bw_cart_popup_cart_icon_margin_left', 0);

    // Padding per Empty Cart SVG
    $empty_cart_padding_top = get_option('bw_cart_popup_empty_cart_padding_top', 0);
    $empty_cart_padding_right = get_option('bw_cart_popup_empty_cart_padding_right', 0);
    $empty_cart_padding_bottom = get_option('bw_cart_popup_empty_cart_padding_bottom', 0);
    $empty_cart_padding_left = get_option('bw_cart_popup_empty_cart_padding_left', 0);

    // Proceed to Checkout button settings
    $checkout_bg = get_option('bw_cart_popup_checkout_bg', '#28a745');
    $checkout_bg_hover = get_option('bw_cart_popup_checkout_bg_hover', '#218838');
    $checkout_text_color = get_option('bw_cart_popup_checkout_text_color', '#ffffff');
    $checkout_text_hover = get_option('bw_cart_popup_checkout_text_hover', '#ffffff');
    $checkout_font_size = get_option('bw_cart_popup_checkout_font_size', 14);
    $checkout_border_radius = get_option('bw_cart_popup_checkout_border_radius', 6);
    $checkout_border_enabled = get_option('bw_cart_popup_checkout_border_enabled', 0);
    $checkout_border_width = get_option('bw_cart_popup_checkout_border_width', 1);
    $checkout_border_style = get_option('bw_cart_popup_checkout_border_style', 'solid');
    $checkout_border_color = get_option('bw_cart_popup_checkout_border_color', '#28a745');
    $checkout_padding_top = get_option('bw_cart_popup_checkout_padding_top', 12);
    $checkout_padding_right = get_option('bw_cart_popup_checkout_padding_right', 20);
    $checkout_padding_bottom = get_option('bw_cart_popup_checkout_padding_bottom', 12);
    $checkout_padding_left = get_option('bw_cart_popup_checkout_padding_left', 20);

    // Continue Shopping button settings
    $continue_bg = get_option('bw_cart_popup_continue_bg', '#6c757d');
    $continue_bg_hover = get_option('bw_cart_popup_continue_bg_hover', '#5a6268');
    $continue_text_color = get_option('bw_cart_popup_continue_text_color', '#ffffff');
    $continue_text_hover = get_option('bw_cart_popup_continue_text_hover', '#ffffff');
    $continue_font_size = get_option('bw_cart_popup_continue_font_size', 14);
    $continue_border_radius = get_option('bw_cart_popup_continue_border_radius', 6);
    $continue_border_enabled = get_option('bw_cart_popup_continue_border_enabled', 0);
    $continue_border_width = get_option('bw_cart_popup_continue_border_width', 1);
    $continue_border_style = get_option('bw_cart_popup_continue_border_style', 'solid');
    $continue_border_color = get_option('bw_cart_popup_continue_border_color', '#6c757d');
    $continue_padding_top = get_option('bw_cart_popup_continue_padding_top', 12);
    $continue_padding_right = get_option('bw_cart_popup_continue_padding_right', 20);
    $continue_padding_bottom = get_option('bw_cart_popup_continue_padding_bottom', 12);
    $continue_padding_left = get_option('bw_cart_popup_continue_padding_left', 20);

    // Empty cart settings
    $return_shop_url = get_option('bw_cart_popup_return_shop_url', '');
    $show_quantity_badge = get_option('bw_cart_popup_show_quantity_badge', 1);

    // Promo code section settings
    $promo_section_label = get_option('bw_cart_popup_promo_section_label', 'Promo code section');
    $promo_input_padding_top = get_option('bw_cart_popup_promo_input_padding_top', 10);
    $promo_input_padding_right = get_option('bw_cart_popup_promo_input_padding_right', 12);
    $promo_input_padding_bottom = get_option('bw_cart_popup_promo_input_padding_bottom', 10);
    $promo_input_padding_left = get_option('bw_cart_popup_promo_input_padding_left', 12);
    $promo_placeholder_font_size = get_option('bw_cart_popup_promo_placeholder_font_size', 14);
    $apply_button_font_weight = get_option('bw_cart_popup_apply_button_font_weight', 'normal');

    ?>
    <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Impostazioni salvate con successo!</strong></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('bw_cart_popup_save', 'bw_cart_popup_nonce'); ?>

        <table class="form-table" role="presentation">
            <!-- Toggle ON/OFF -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_active">Attiva Cart Pop-Up</label>
                </th>
                <td>
                    <label class="switch">
                        <input type="checkbox" id="bw_cart_popup_active" name="bw_cart_popup_active" value="1" <?php checked(1, $active); ?> />
                        <span class="description">Quando attivo, i pulsanti "Add to Cart" apriranno il pannello slide-in invece di andare alla pagina carrello.</span>
                    </label>
                </td>
            </tr>

            <!-- Floating cart trigger ON/OFF -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_show_floating_trigger">Mostra pulsante carrello fisso</label>
                </th>
                <td>
                    <label class="switch">
                        <input type="checkbox" id="bw_cart_popup_show_floating_trigger" name="bw_cart_popup_show_floating_trigger" value="1" <?php checked(1, $show_floating_trigger); ?> />
                        <span class="description">Attiva l'icona fissa in basso a destra con badge quantità; cliccandola si apre il cart pop-up.</span>
                    </label>
                </td>
            </tr>

            <!-- Slide-in Animation ON/OFF -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_slide_animation">Slide-in animation (cart open)</label>
                </th>
                <td>
                    <label class="switch">
                        <input type="checkbox" id="bw_cart_popup_slide_animation" name="bw_cart_popup_slide_animation" value="1" <?php checked(1, get_option('bw_cart_popup_slide_animation', 1)); ?> />
                        <span class="description">Quando attivo, il cart pop-up si apre automaticamente con slide-in da destra ogni volta che un prodotto viene aggiunto al carrello.</span>
                    </label>
                </td>
            </tr>

            <!-- Larghezza pannello -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_panel_width">Larghezza Pannello (px)</label>
                </th>
                <td>
                    <input type="number" id="bw_cart_popup_panel_width" name="bw_cart_popup_panel_width" value="<?php echo esc_attr($panel_width); ?>" min="300" max="800" step="10" class="regular-text" />
                    <p class="description">Larghezza del pannello laterale in pixel (default: 400px)</p>
                </td>
            </tr>

            <!-- Colore overlay -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_overlay_color">Colore Overlay</label>
                </th>
                <td>
                    <input type="color" id="bw_cart_popup_overlay_color" name="bw_cart_popup_overlay_color" value="<?php echo esc_attr($overlay_color); ?>" />
                    <p class="description">Colore della maschera overlay che oscura la pagina</p>
                </td>
            </tr>

            <!-- Opacità overlay -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_overlay_opacity">Opacità Overlay</label>
                </th>
                <td>
                    <input type="number" id="bw_cart_popup_overlay_opacity" name="bw_cart_popup_overlay_opacity" value="<?php echo esc_attr($overlay_opacity); ?>" min="0" max="1" step="0.1" class="small-text" />
                    <p class="description">Opacità dell'overlay (da 0 a 1, default: 0.5)</p>
                </td>
            </tr>

            <!-- Colore sfondo pannello -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_panel_bg">Colore Sfondo Pannello</label>
                </th>
                <td>
                    <input type="color" id="bw_cart_popup_panel_bg" name="bw_cart_popup_panel_bg" value="<?php echo esc_attr($panel_bg); ?>" />
                    <p class="description">Colore di sfondo del pannello slide-in</p>
                </td>
            </tr>

            <!-- Badge quantità -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_show_quantity_badge">Mostra badge quantità (thumbnail)</label>
                </th>
                <td>
                    <label class="switch">
                        <input type="checkbox" id="bw_cart_popup_show_quantity_badge" name="bw_cart_popup_show_quantity_badge" value="1" <?php checked(1, $show_quantity_badge); ?> />
                        <span class="description">Attiva o disattiva il pallino con il numero di pezzi sopra l’immagine prodotto nel cart pop-up.</span>
                    </label>
                </td>
            </tr>

            <!-- Sezione Pulsanti -->
            <tr>
                <th colspan="2">
                    <h2>Configurazione Pulsanti</h2>
                </th>
            </tr>

            <!-- === PROCEED TO CHECKOUT BUTTON === -->
            <tr>
                <th colspan="2">
                    <h3 style="margin: 20px 0 10px 0;">Proceed to Checkout Button Style</h3>
                </th>
            </tr>

            <!-- Testo -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_checkout_text">Testo Pulsante</label>
                </th>
                <td>
                    <input type="text" id="bw_cart_popup_checkout_text" name="bw_cart_popup_checkout_text" value="<?php echo esc_attr($checkout_text); ?>" class="regular-text" />
                    <p class="description">Testo del pulsante (default: "Proceed to checkout")</p>
                </td>
            </tr>

            <!-- RIMOSSO: Link Personalizzato - Il pulsante usa sempre wc_get_checkout_url() per garantire che punti alla checkout page di WooCommerce -->

            <!-- Background Color -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_checkout_bg">Colore Background</label>
                </th>
                <td>
                    <input type="color" id="bw_cart_popup_checkout_bg" name="bw_cart_popup_checkout_bg" value="<?php echo esc_attr($checkout_bg); ?>" />
                    <p class="description">Colore di sfondo normale</p>
                </td>
            </tr>

            <!-- Background Hover -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_checkout_bg_hover">Colore Background Hover</label>
                </th>
                <td>
                    <input type="color" id="bw_cart_popup_checkout_bg_hover" name="bw_cart_popup_checkout_bg_hover" value="<?php echo esc_attr($checkout_bg_hover); ?>" />
                    <p class="description">Colore di sfondo al passaggio del mouse</p>
                </td>
            </tr>

            <!-- Text Color -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_checkout_text_color">Colore Testo</label>
                </th>
                <td>
                    <input type="color" id="bw_cart_popup_checkout_text_color" name="bw_cart_popup_checkout_text_color" value="<?php echo esc_attr($checkout_text_color); ?>" />
                    <p class="description">Colore del testo normale</p>
                </td>
            </tr>

            <!-- Text Hover -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_checkout_text_hover">Colore Testo Hover</label>
                </th>
                <td>
                    <input type="color" id="bw_cart_popup_checkout_text_hover" name="bw_cart_popup_checkout_text_hover" value="<?php echo esc_attr($checkout_text_hover); ?>" />
                    <p class="description">Colore del testo al passaggio del mouse</p>
                </td>
            </tr>

            <!-- Font Size -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_checkout_font_size">Dimensione Testo (px)</label>
                </th>
                <td>
                    <input type="number" id="bw_cart_popup_checkout_font_size" name="bw_cart_popup_checkout_font_size" value="<?php echo esc_attr($checkout_font_size); ?>" min="10" max="30" class="small-text" />
                    <p class="description">Dimensione del testo in pixel</p>
                </td>
            </tr>

            <!-- Border Radius -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_checkout_border_radius">Border Radius (px)</label>
                </th>
                <td>
                    <input type="number" id="bw_cart_popup_checkout_border_radius" name="bw_cart_popup_checkout_border_radius" value="<?php echo esc_attr($checkout_border_radius); ?>" min="0" max="50" class="small-text" />
                    <p class="description">Arrotondamento degli angoli in pixel</p>
                </td>
            </tr>

            <!-- Border ON/OFF -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_checkout_border_enabled">Abilita Bordo</label>
                </th>
                <td>
                    <input type="checkbox" id="bw_cart_popup_checkout_border_enabled" name="bw_cart_popup_checkout_border_enabled" value="1" <?php checked(1, $checkout_border_enabled); ?> />
                    <span class="description">Attiva/disattiva il bordo del pulsante</span>
                </td>
            </tr>

            <!-- Border Width -->
            <tr class="bw-checkout-border-field">
                <th scope="row">
                    <label for="bw_cart_popup_checkout_border_width">Spessore Bordo (px)</label>
                </th>
                <td>
                    <input type="number" id="bw_cart_popup_checkout_border_width" name="bw_cart_popup_checkout_border_width" value="<?php echo esc_attr($checkout_border_width); ?>" min="0" max="10" class="small-text" />
                    <p class="description">Spessore del bordo in pixel</p>
                </td>
            </tr>

            <!-- Border Style -->
            <tr class="bw-checkout-border-field">
                <th scope="row">
                    <label for="bw_cart_popup_checkout_border_style">Stile Bordo</label>
                </th>
                <td>
                    <select id="bw_cart_popup_checkout_border_style" name="bw_cart_popup_checkout_border_style">
                        <option value="solid" <?php selected($checkout_border_style, 'solid'); ?>>Solid</option>
                        <option value="dashed" <?php selected($checkout_border_style, 'dashed'); ?>>Dashed</option>
                        <option value="dotted" <?php selected($checkout_border_style, 'dotted'); ?>>Dotted</option>
                        <option value="double" <?php selected($checkout_border_style, 'double'); ?>>Double</option>
                    </select>
                    <p class="description">Stile del bordo</p>
                </td>
            </tr>

            <!-- Border Color -->
            <tr class="bw-checkout-border-field">
                <th scope="row">
                    <label for="bw_cart_popup_checkout_border_color">Colore Bordo</label>
                </th>
                <td>
                    <input type="color" id="bw_cart_popup_checkout_border_color" name="bw_cart_popup_checkout_border_color" value="<?php echo esc_attr($checkout_border_color); ?>" />
                    <p class="description">Colore del bordo</p>
                </td>
            </tr>

            <!-- Padding (Layout Compatto) -->
            <tr>
                <th scope="row">
                    <label>Padding (px)</label>
                </th>
                <td>
                    <div class="bw-padding-grid">
                        <div class="bw-padding-field">
                            <input type="number" id="bw_cart_popup_checkout_padding_top" name="bw_cart_popup_checkout_padding_top" value="<?php echo esc_attr($checkout_padding_top); ?>" min="0" max="50" class="small-text" />
                            <label for="bw_cart_popup_checkout_padding_top">Top</label>
                        </div>
                        <div class="bw-padding-field">
                            <input type="number" id="bw_cart_popup_checkout_padding_right" name="bw_cart_popup_checkout_padding_right" value="<?php echo esc_attr($checkout_padding_right); ?>" min="0" max="50" class="small-text" />
                            <label for="bw_cart_popup_checkout_padding_right">Right</label>
                        </div>
                        <div class="bw-padding-field">
                            <input type="number" id="bw_cart_popup_checkout_padding_bottom" name="bw_cart_popup_checkout_padding_bottom" value="<?php echo esc_attr($checkout_padding_bottom); ?>" min="0" max="50" class="small-text" />
                            <label for="bw_cart_popup_checkout_padding_bottom">Bottom</label>
                        </div>
                        <div class="bw-padding-field">
                            <input type="number" id="bw_cart_popup_checkout_padding_left" name="bw_cart_popup_checkout_padding_left" value="<?php echo esc_attr($checkout_padding_left); ?>" min="0" max="50" class="small-text" />
                            <label for="bw_cart_popup_checkout_padding_left">Left</label>
                        </div>
                    </div>
                    <p class="description">Imposta il padding per ogni lato del pulsante</p>
                </td>
            </tr>

            <!-- === CONTINUE SHOPPING BUTTON === -->
            <tr>
                <th colspan="2">
                    <h3 style="margin: 30px 0 10px 0;">Continue Shopping Button Style</h3>
                </th>
            </tr>

            <!-- Testo -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_continue_text">Testo Pulsante</label>
                </th>
                <td>
                    <input type="text" id="bw_cart_popup_continue_text" name="bw_cart_popup_continue_text" value="<?php echo esc_attr($continue_text); ?>" class="regular-text" />
                    <p class="description">Testo del pulsante (default: "Continue shopping")</p>
                </td>
            </tr>

            <!-- Link Personalizzato -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_continue_url">Link Personalizzato</label>
                </th>
                <td>
                    <input type="url" id="bw_cart_popup_continue_url" name="bw_cart_popup_continue_url" value="<?php echo esc_attr($continue_url); ?>" class="regular-text" placeholder="/shop/" />
                    <p class="description">URL personalizzato per il pulsante Continue Shopping (lascia vuoto per usare /shop/ di default)</p>
                </td>
            </tr>

            <!-- Background Color -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_continue_bg">Colore Background</label>
                </th>
                <td>
                    <input type="color" id="bw_cart_popup_continue_bg" name="bw_cart_popup_continue_bg" value="<?php echo esc_attr($continue_bg); ?>" />
                    <p class="description">Colore di sfondo normale</p>
                </td>
            </tr>

            <!-- Background Hover -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_continue_bg_hover">Colore Background Hover</label>
                </th>
                <td>
                    <input type="color" id="bw_cart_popup_continue_bg_hover" name="bw_cart_popup_continue_bg_hover" value="<?php echo esc_attr($continue_bg_hover); ?>" />
                    <p class="description">Colore di sfondo al passaggio del mouse</p>
                </td>
            </tr>

            <!-- Text Color -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_continue_text_color">Colore Testo</label>
                </th>
                <td>
                    <input type="color" id="bw_cart_popup_continue_text_color" name="bw_cart_popup_continue_text_color" value="<?php echo esc_attr($continue_text_color); ?>" />
                    <p class="description">Colore del testo normale</p>
                </td>
            </tr>

            <!-- Text Hover -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_continue_text_hover">Colore Testo Hover</label>
                </th>
                <td>
                    <input type="color" id="bw_cart_popup_continue_text_hover" name="bw_cart_popup_continue_text_hover" value="<?php echo esc_attr($continue_text_hover); ?>" />
                    <p class="description">Colore del testo al passaggio del mouse</p>
                </td>
            </tr>

            <!-- Font Size -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_continue_font_size">Dimensione Testo (px)</label>
                </th>
                <td>
                    <input type="number" id="bw_cart_popup_continue_font_size" name="bw_cart_popup_continue_font_size" value="<?php echo esc_attr($continue_font_size); ?>" min="10" max="30" class="small-text" />
                    <p class="description">Dimensione del testo in pixel</p>
                </td>
            </tr>

            <!-- Border Radius -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_continue_border_radius">Border Radius (px)</label>
                </th>
                <td>
                    <input type="number" id="bw_cart_popup_continue_border_radius" name="bw_cart_popup_continue_border_radius" value="<?php echo esc_attr($continue_border_radius); ?>" min="0" max="50" class="small-text" />
                    <p class="description">Arrotondamento degli angoli in pixel</p>
                </td>
            </tr>

            <!-- Border ON/OFF -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_continue_border_enabled">Abilita Bordo</label>
                </th>
                <td>
                    <input type="checkbox" id="bw_cart_popup_continue_border_enabled" name="bw_cart_popup_continue_border_enabled" value="1" <?php checked(1, $continue_border_enabled); ?> />
                    <span class="description">Attiva/disattiva il bordo del pulsante</span>
                </td>
            </tr>

            <!-- Border Width -->
            <tr class="bw-continue-border-field">
                <th scope="row">
                    <label for="bw_cart_popup_continue_border_width">Spessore Bordo (px)</label>
                </th>
                <td>
                    <input type="number" id="bw_cart_popup_continue_border_width" name="bw_cart_popup_continue_border_width" value="<?php echo esc_attr($continue_border_width); ?>" min="0" max="10" class="small-text" />
                    <p class="description">Spessore del bordo in pixel</p>
                </td>
            </tr>

            <!-- Border Style -->
            <tr class="bw-continue-border-field">
                <th scope="row">
                    <label for="bw_cart_popup_continue_border_style">Stile Bordo</label>
                </th>
                <td>
                    <select id="bw_cart_popup_continue_border_style" name="bw_cart_popup_continue_border_style">
                        <option value="solid" <?php selected($continue_border_style, 'solid'); ?>>Solid</option>
                        <option value="dashed" <?php selected($continue_border_style, 'dashed'); ?>>Dashed</option>
                        <option value="dotted" <?php selected($continue_border_style, 'dotted'); ?>>Dotted</option>
                        <option value="double" <?php selected($continue_border_style, 'double'); ?>>Double</option>
                    </select>
                    <p class="description">Stile del bordo</p>
                </td>
            </tr>

            <!-- Border Color -->
            <tr class="bw-continue-border-field">
                <th scope="row">
                    <label for="bw_cart_popup_continue_border_color">Colore Bordo</label>
                </th>
                <td>
                    <input type="color" id="bw_cart_popup_continue_border_color" name="bw_cart_popup_continue_border_color" value="<?php echo esc_attr($continue_border_color); ?>" />
                    <p class="description">Colore del bordo</p>
                </td>
            </tr>

            <!-- Padding (Layout Compatto) -->
            <tr>
                <th scope="row">
                    <label>Padding (px)</label>
                </th>
                <td>
                    <div class="bw-padding-grid">
                        <div class="bw-padding-field">
                            <input type="number" id="bw_cart_popup_continue_padding_top" name="bw_cart_popup_continue_padding_top" value="<?php echo esc_attr($continue_padding_top); ?>" min="0" max="50" class="small-text" />
                            <label for="bw_cart_popup_continue_padding_top">Top</label>
                        </div>
                        <div class="bw-padding-field">
                            <input type="number" id="bw_cart_popup_continue_padding_right" name="bw_cart_popup_continue_padding_right" value="<?php echo esc_attr($continue_padding_right); ?>" min="0" max="50" class="small-text" />
                            <label for="bw_cart_popup_continue_padding_right">Right</label>
                        </div>
                        <div class="bw-padding-field">
                            <input type="number" id="bw_cart_popup_continue_padding_bottom" name="bw_cart_popup_continue_padding_bottom" value="<?php echo esc_attr($continue_padding_bottom); ?>" min="0" max="50" class="small-text" />
                            <label for="bw_cart_popup_continue_padding_bottom">Bottom</label>
                        </div>
                        <div class="bw-padding-field">
                            <input type="number" id="bw_cart_popup_continue_padding_left" name="bw_cart_popup_continue_padding_left" value="<?php echo esc_attr($continue_padding_left); ?>" min="0" max="50" class="small-text" />
                            <label for="bw_cart_popup_continue_padding_left">Left</label>
                        </div>
                    </div>
                    <p class="description">Imposta il padding per ogni lato del pulsante</p>
                </td>
            </tr>

            <!-- === PROMO CODE SECTION === -->
            <tr>
                <th colspan="2">
                    <hr style="margin: 30px 0 20px 0; border: none; border-top: 2px solid #ddd;">
                    <h2 style="margin: 20px 0 10px 0;">Promo Code Section</h2>
                </th>
            </tr>

            <!-- Section Label -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_promo_section_label">Section Label</label>
                </th>
                <td>
                    <input type="text" id="bw_cart_popup_promo_section_label" name="bw_cart_popup_promo_section_label" value="<?php echo esc_attr($promo_section_label); ?>" class="regular-text" />
                    <p class="description">Label per la sezione promo code (default: "Promo code section")</p>
                </td>
            </tr>

            <!-- Promo Input Padding -->
            <tr>
                <th scope="row">
                    <label>Input "Enter promo code" Padding (px)</label>
                </th>
                <td>
                    <div class="bw-padding-grid">
                        <div class="bw-padding-field">
                            <input type="number" id="bw_cart_popup_promo_input_padding_top" name="bw_cart_popup_promo_input_padding_top" value="<?php echo esc_attr($promo_input_padding_top); ?>" min="0" max="100" class="small-text" />
                            <label for="bw_cart_popup_promo_input_padding_top">Top</label>
                        </div>
                        <div class="bw-padding-field">
                            <input type="number" id="bw_cart_popup_promo_input_padding_right" name="bw_cart_popup_promo_input_padding_right" value="<?php echo esc_attr($promo_input_padding_right); ?>" min="0" max="100" class="small-text" />
                            <label for="bw_cart_popup_promo_input_padding_right">Right</label>
                        </div>
                        <div class="bw-padding-field">
                            <input type="number" id="bw_cart_popup_promo_input_padding_bottom" name="bw_cart_popup_promo_input_padding_bottom" value="<?php echo esc_attr($promo_input_padding_bottom); ?>" min="0" max="100" class="small-text" />
                            <label for="bw_cart_popup_promo_input_padding_bottom">Bottom</label>
                        </div>
                        <div class="bw-padding-field">
                            <input type="number" id="bw_cart_popup_promo_input_padding_left" name="bw_cart_popup_promo_input_padding_left" value="<?php echo esc_attr($promo_input_padding_left); ?>" min="0" max="100" class="small-text" />
                            <label for="bw_cart_popup_promo_input_padding_left">Left</label>
                        </div>
                    </div>
                    <p class="description">Padding dell'input del promo code (tutti i 4 valori in linea)</p>
                </td>
            </tr>

            <!-- Placeholder Font Size -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_promo_placeholder_font_size">Placeholder Font Size (px)</label>
                </th>
                <td>
                    <input type="number" id="bw_cart_popup_promo_placeholder_font_size" name="bw_cart_popup_promo_placeholder_font_size" value="<?php echo esc_attr($promo_placeholder_font_size); ?>" min="8" max="30" class="small-text" />
                    <p class="description">Dimensione del font del placeholder dell'input "Enter promo code" (solo placeholder, non il testo digitato)</p>
                </td>
            </tr>

            <!-- Apply Button Font Weight -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_apply_button_font_weight">Apply Button Font Weight</label>
                </th>
                <td>
                    <select id="bw_cart_popup_apply_button_font_weight" name="bw_cart_popup_apply_button_font_weight">
                        <option value="normal" <?php selected($apply_button_font_weight, 'normal'); ?>>Normal</option>
                        <option value="600" <?php selected($apply_button_font_weight, '600'); ?>>Semi-bold (600)</option>
                        <option value="bold" <?php selected($apply_button_font_weight, 'bold'); ?>>Bold</option>
                    </select>
                    <p class="description">Font weight del pulsante "Apply" (default: normal)</p>
                </td>
            </tr>

            <!-- === EMPTY CART SETTINGS === -->
            <tr>
                <th colspan="2">
                    <h3 style="margin: 30px 0 10px 0;">Empty Cart Settings</h3>
                </th>
            </tr>

            <!-- Return to Shop URL -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_return_shop_url">Return to Shop URL</label>
                </th>
                <td>
                    <input type="url" id="bw_cart_popup_return_shop_url" name="bw_cart_popup_return_shop_url" value="<?php echo esc_attr($return_shop_url); ?>" class="regular-text" placeholder="/shop/" />
                    <p class="description">URL personalizzato per il pulsante "Return to Shop" (lascia vuoto per usare /shop/ di default)</p>
                </td>
            </tr>

            <!-- Sezione SVG Personalizzato -->
            <tr>
                <th colspan="2">
                    <h2>SVG Personalizzato</h2>
                </th>
            </tr>

            <!-- SVG Aggiuntivo -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_additional_svg">Cart Pop-Up SVG Icon (Custom)</label>
                </th>
                <td>
                    <textarea id="bw_cart_popup_additional_svg" name="bw_cart_popup_additional_svg" rows="8" class="large-text code"><?php echo esc_textarea($additional_svg); ?></textarea>
                    <p class="description">Incolla qui il codice SVG completo da visualizzare nel Cart Pop-Up. Esempio: &lt;svg xmlns="http://www.w3.org/2000/svg"...&gt;...&lt;/svg&gt;</p>
                </td>
            </tr>

            <!-- Margin per Cart Icon SVG -->
            <tr>
                <th scope="row">
                    <label>Margin Cart Icon SVG (px)</label>
                </th>
                <td>
                    <div class="bw-padding-grid">
                        <div class="bw-padding-field">
                            <input type="number" id="bw_cart_popup_cart_icon_margin_top" name="bw_cart_popup_cart_icon_margin_top" value="<?php echo esc_attr($cart_icon_margin_top); ?>" min="0" max="100" class="small-text" />
                            <label for="bw_cart_popup_cart_icon_margin_top">Top</label>
                        </div>
                        <div class="bw-padding-field">
                            <input type="number" id="bw_cart_popup_cart_icon_margin_right" name="bw_cart_popup_cart_icon_margin_right" value="<?php echo esc_attr($cart_icon_margin_right); ?>" min="0" max="100" class="small-text" />
                            <label for="bw_cart_popup_cart_icon_margin_right">Right</label>
                        </div>
                        <div class="bw-padding-field">
                            <input type="number" id="bw_cart_popup_cart_icon_margin_bottom" name="bw_cart_popup_cart_icon_margin_bottom" value="<?php echo esc_attr($cart_icon_margin_bottom); ?>" min="0" max="100" class="small-text" />
                            <label for="bw_cart_popup_cart_icon_margin_bottom">Bottom</label>
                        </div>
                        <div class="bw-padding-field">
                            <input type="number" id="bw_cart_popup_cart_icon_margin_left" name="bw_cart_popup_cart_icon_margin_left" value="<?php echo esc_attr($cart_icon_margin_left); ?>" min="0" max="100" class="small-text" />
                            <label for="bw_cart_popup_cart_icon_margin_left">Left</label>
                        </div>
                    </div>
                    <p class="description">Imposta il margin per l'icona SVG del carrello</p>
                </td>
            </tr>

            <!-- Empty Cart SVG (Custom) -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_empty_cart_svg">Empty Cart SVG (Custom)</label>
                </th>
                <td>
                    <textarea id="bw_cart_popup_empty_cart_svg" name="bw_cart_popup_empty_cart_svg" rows="8" class="large-text code"><?php echo esc_textarea($empty_cart_svg); ?></textarea>
                    <p class="description">Incolla qui il codice SVG personalizzato per l'icona del carrello vuoto. Se vuoto, verrà usata l'icona di default.</p>
                </td>
            </tr>

            <!-- Padding per Empty Cart SVG -->
            <tr>
                <th scope="row">
                    <label>Padding Empty Cart SVG (px)</label>
                </th>
                <td>
                    <div class="bw-padding-grid">
                        <div class="bw-padding-field">
                            <input type="number" id="bw_cart_popup_empty_cart_padding_top" name="bw_cart_popup_empty_cart_padding_top" value="<?php echo esc_attr($empty_cart_padding_top); ?>" min="0" max="100" class="small-text" />
                            <label for="bw_cart_popup_empty_cart_padding_top">Top</label>
                        </div>
                        <div class="bw-padding-field">
                            <input type="number" id="bw_cart_popup_empty_cart_padding_right" name="bw_cart_popup_empty_cart_padding_right" value="<?php echo esc_attr($empty_cart_padding_right); ?>" min="0" max="100" class="small-text" />
                            <label for="bw_cart_popup_empty_cart_padding_right">Right</label>
                        </div>
                        <div class="bw-padding-field">
                            <input type="number" id="bw_cart_popup_empty_cart_padding_bottom" name="bw_cart_popup_empty_cart_padding_bottom" value="<?php echo esc_attr($empty_cart_padding_bottom); ?>" min="0" max="100" class="small-text" />
                            <label for="bw_cart_popup_empty_cart_padding_bottom">Bottom</label>
                        </div>
                        <div class="bw-padding-field">
                            <input type="number" id="bw_cart_popup_empty_cart_padding_left" name="bw_cart_popup_empty_cart_padding_left" value="<?php echo esc_attr($empty_cart_padding_left); ?>" min="0" max="100" class="small-text" />
                            <label for="bw_cart_popup_empty_cart_padding_left">Left</label>
                        </div>
                    </div>
                    <p class="description">Imposta il padding per l'icona SVG del carrello vuoto</p>
                </td>
            </tr>

            <!-- Opzione colore nero SVG -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_svg_black">Colora SVG di Nero</label>
                </th>
                <td>
                    <label class="switch">
                        <input type="checkbox" id="bw_cart_popup_svg_black" name="bw_cart_popup_svg_black" value="1" <?php checked(1, $svg_black); ?> />
                        <span class="description">Applica automaticamente fill: #000 su tutti i path dell'SVG</span>
                    </label>
                </td>
            </tr>
        </table>

        <?php submit_button('Salva Impostazioni', 'primary', 'bw_cart_popup_submit'); ?>
    </form>

    <!-- Note informative -->
    <div class="card" style="margin-top: 20px;">
        <h2>Note sull'utilizzo</h2>
        <ul>
            <li><strong>Funzionalità OFF:</strong> I pulsanti "Add to Cart" comportano in modo standard e portano alla pagina del carrello.</li>
            <li><strong>Funzionalità ON:</strong> Cliccando su "Add to Cart" si apre un pannello slide-in da destra con overlay scuro.</li>
            <li><strong>Design:</strong> Il pannello replica il design del mini-cart con header, lista prodotti, promo code, totali e pulsanti azione.</li>
            <li><strong>Promo Code:</strong> Al click su "Click here" appare un box per inserire il coupon con calcolo real-time dello sconto.</li>
            <li><strong>CSS Personalizzato:</strong> Puoi modificare ulteriormente lo stile editando il file <code>assets/css/bw-cart-popup.css</code></li>
        </ul>
    </div>

    <style>
        .switch input {
            margin-right: 10px;
        }
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .card h2 {
            margin-top: 0;
        }
        .card ul {
            list-style: disc;
            padding-left: 20px;
        }
        .card li {
            margin-bottom: 10px;
        }

        /* Layout compatto per padding */
        .bw-padding-grid {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .bw-padding-field {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .bw-padding-field input {
            margin-bottom: 5px;
        }
        .bw-padding-field label {
            font-size: 12px;
            color: #666;
            font-weight: 500;
        }
    </style>
    <?php
    // JavaScript for border toggle is now loaded via bw-border-toggle-admin.js
}

/**
 * Renderizza il tab Redirect.
 */
function bw_site_render_redirect_tab() {
    $saved = false;

    if (isset($_POST['bw_redirects_submit'])) {
        if (!current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('bw_redirects_save', 'bw_redirects_nonce');

        $redirects_input = isset($_POST['bw_redirects']) && is_array($_POST['bw_redirects']) ? wp_unslash($_POST['bw_redirects']) : [];
        $sanitized       = [];

        foreach ($redirects_input as $redirect) {
            $target_raw        = isset($redirect['target_url']) ? trim((string) $redirect['target_url']) : '';
            $source_raw        = isset($redirect['source_url']) ? trim((string) $redirect['source_url']) : '';
            $target            = esc_url_raw($target_raw);
            $normalized_source = bw_normalize_redirect_path($source_raw);
            $source_to_store   = '' !== $source_raw ? sanitize_text_field($source_raw) : '';

            if ('' === $target || '' === $normalized_source) {
                continue;
            }

            $sanitized[] = [
                'source' => $source_to_store,
                'target' => $target,
            ];
        }

        update_option('bw_redirects', $sanitized);
        $saved = true;
    }

    $redirects = get_option('bw_redirects', []);

    if (!is_array($redirects)) {
        $redirects = [];
    }

    if (empty($redirects)) {
        $redirects[] = [
            'source' => '',
            'target' => '',
        ];
    }

    $next_index = count($redirects);
    ?>

    <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Redirect salvati con successo!</strong></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('bw_redirects_save', 'bw_redirects_nonce'); ?>

        <table class="form-table bw-redirects-table" role="presentation">
            <thead>
                <tr>
                    <th scope="col">Link d'arrivo</th>
                    <th scope="col">Link di redirect</th>
                    <th scope="col">Azioni</th>
                </tr>
            </thead>
            <tbody id="bw-redirects-rows" data-next-index="<?php echo esc_attr($next_index); ?>">
                <?php foreach ($redirects as $index => $redirect) :
                    $target = isset($redirect['target']) ? $redirect['target'] : '';
                    $source = isset($redirect['source']) ? $redirect['source'] : '';
                    ?>
                    <tr class="bw-redirect-row">
                        <td>
                            <label>
                                Inserisci il link d'arrivo
                                <input type="text" name="bw_redirects[<?php echo esc_attr($index); ?>][target_url]" value="<?php echo esc_attr($target); ?>" class="regular-text" placeholder="https://esempio.com/pagina" />
                            </label>
                            <p class="description">URL assoluto verso cui reindirizzare l'utente.</p>
                        </td>
                        <td>
                            <label>
                                Inserisci il link di redirect
                                <input type="text" name="bw_redirects[<?php echo esc_attr($index); ?>][source_url]" value="<?php echo esc_attr($source); ?>" class="regular-text" placeholder="/promo/black-friday" />
                            </label>
                            <p class="description">Accetta un path relativo (es. /promo) o un URL completo.</p>
                        </td>
                        <td class="bw-redirect-actions">
                            <button type="button" class="button button-link-delete bw-remove-redirect">Rimuovi</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p>
            <button type="button" class="button" id="bw-add-redirect">Aggiungi redirect</button>
        </p>

        <script type="text/html" id="bw-redirect-row-template">
            <tr class="bw-redirect-row">
                <td>
                    <label>
                        Inserisci il link d'arrivo
                        <input type="text" name="bw_redirects[__index__][target_url]" value="" class="regular-text" placeholder="https://esempio.com/pagina" />
                    </label>
                    <p class="description">URL assoluto verso cui reindirizzare l'utente.</p>
                </td>
                <td>
                    <label>
                        Inserisci il link di redirect
                        <input type="text" name="bw_redirects[__index__][source_url]" value="" class="regular-text" placeholder="/promo/black-friday" />
                    </label>
                    <p class="description">Accetta un path relativo (es. /promo) o un URL completo.</p>
                </td>
                <td class="bw-redirect-actions">
                    <button type="button" class="button button-link-delete bw-remove-redirect">Rimuovi</button>
                </td>
            </tr>
        </script>

        <?php submit_button('Salva redirect', 'primary', 'bw_redirects_submit'); ?>
    </form>
    <?php
}

/**
 * Renderizza il tab BW Coming Soon
 */
function bw_site_render_coming_soon_tab() {
    // Salva le impostazioni se il form è stato inviato
    $saved = false;
    if (isset($_POST['bw_coming_soon_submit'])) {
        check_admin_referer('bw_coming_soon_save', 'bw_coming_soon_nonce');

        $active_value = isset($_POST['bw_coming_soon_toggle']) ? 1 : 0;
        update_option('bw_coming_soon_active', $active_value);
        $saved = true;
    }

    $active = (int) get_option('bw_coming_soon_active', 0);
    ?>
    <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Impostazioni salvate con successo!</strong></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('bw_coming_soon_save', 'bw_coming_soon_nonce'); ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="bw_coming_soon_toggle">Attiva modalità Coming Soon</label>
                </th>
                <td>
                    <input type="checkbox" id="bw_coming_soon_toggle" name="bw_coming_soon_toggle" value="1" <?php checked(1, $active); ?> />
                    <span class="description">Quando attivo, il sito mostrerà la pagina Coming Soon ai visitatori non loggati.</span>
                </td>
            </tr>
        </table>

        <?php submit_button('Salva impostazioni', 'primary', 'bw_coming_soon_submit'); ?>
    </form>
    <?php
}

/**
 * Renderizza il tab Import Product.
 */
function bw_site_render_import_product_tab() {
    if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
        return;
    }

    $notices = [];
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        bw_import_clear_state();
    }

    $state = bw_import_get_state();

    if (isset($_POST['bw_import_upload_submit'])) {
        $upload_result = bw_import_handle_upload_request();
        if (is_wp_error($upload_result)) {
            $notices[] = ['type' => 'error', 'message' => $upload_result->get_error_message()];
        } else {
            $state     = $upload_result;
            $notices[] = ['type' => 'success', 'message' => __('CSV uploaded successfully. Configure the mapping below.', 'bw')];
        }
    }

    if (isset($_POST['bw_import_run'])) {
        $import_result = bw_import_handle_run_request($state);

        if (is_wp_error($import_result)) {
            $notices[] = ['type' => 'error', 'message' => $import_result->get_error_message()];
        } elseif (!empty($import_result['message'])) {
            $notices[] = ['type' => 'success', 'message' => esc_html($import_result['message'])];
        }
    }

    if (!empty($notices)) {
        foreach ($notices as $notice) {
            $class = $notice['type'] === 'error' ? 'notice-error' : 'notice-success';
            ?>
            <div class="notice <?php echo esc_attr($class); ?> is-dismissible">
                <p><?php echo esc_html($notice['message']); ?></p>
            </div>
            <?php
        }
    }

    $state = bw_import_get_state();
    ?>
    <div class="wrap">
        <h2><?php esc_html_e('Import Product', 'bw'); ?></h2>
        <p><?php esc_html_e('Upload a CSV file to import or update WooCommerce products and custom meta fields.', 'bw'); ?></p>

        <h3><?php esc_html_e('1. Upload CSV', 'bw'); ?></h3>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('bw_import_upload', 'bw_import_upload_nonce'); ?>
            <input type="file" name="bw_import_csv" accept=".csv" />
            <div style="margin-top: 10px; display: flex; flex-direction: column; gap: 6px; max-width: 620px;">
                <strong><?php esc_html_e('Update existing products', 'bw'); ?></strong>
                <label style="display: flex; gap: 8px; align-items: flex-start;">
                    <input type="checkbox" name="bw_import_update_existing" value="1" <?php checked(!empty($state['update_existing'])); ?> />
                    <span><?php esc_html_e('Existing products that match by ID or SKU will be updated. Products that do not exist will be skipped.', 'bw'); ?></span>
                </label>
            </div>
            <?php submit_button(__('Upload & Analyze', 'bw'), 'primary', 'bw_import_upload_submit', false); ?>
        </form>

        <?php if (!empty($state['upload_summary'])) : ?>
            <hr />
            <h3><?php esc_html_e('Upload summary', 'bw'); ?></h3>
            <table class="widefat fixed" style="max-width:700px;">
                <tbody>
                    <tr>
                        <th><?php esc_html_e('Uploaded file', 'bw'); ?></th>
                        <td><?php echo esc_html($state['upload_summary']['file_name']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Total fields in file', 'bw'); ?></th>
                        <td><?php echo (int) $state['upload_summary']['total_fields']; ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Fields detected', 'bw'); ?></th>
                        <td><?php echo (int) $state['upload_summary']['loaded_fields']; ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Missing field names', 'bw'); ?></th>
                        <td>
                            <?php if (!empty($state['upload_summary']['missing'])) : ?>
                                <ul style="margin: 0; padding-left: 20px;">
                                    <?php foreach ($state['upload_summary']['missing'] as $missing_header) : ?>
                                        <li><?php echo esc_html($missing_header); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <?php esc_html_e('All fields were loaded successfully.', 'bw'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Replaced fields', 'bw'); ?></th>
                        <td>
                            <?php
                            $replaced_count = isset($state['upload_summary']['replaced_count']) ? (int) $state['upload_summary']['replaced_count'] : 0;
                            if ($replaced_count > 0) :
                                ?>
                                <strong><?php echo esc_html(sprintf(__('Replaced headers: %d', 'bw'), $replaced_count)); ?></strong>
                                <ul style="margin: 4px 0 0 20px;">
                                    <?php foreach ((array) $state['upload_summary']['replaced'] as $replaced_header) : ?>
                                        <li><?php echo esc_html($replaced_header); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <?php esc_html_e('No empty headers were replaced.', 'bw'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Duplicate headers', 'bw'); ?></th>
                        <td>
                            <?php
                            $duplicate_count = isset($state['upload_summary']['duplicate_count']) ? (int) $state['upload_summary']['duplicate_count'] : 0;
                            $duplicates      = isset($state['upload_summary']['duplicates']) ? (array) $state['upload_summary']['duplicates'] : [];
                            if ($duplicate_count > 0) :
                                ?>
                                <strong><?php echo esc_html(sprintf(__('Duplicated fields: %d', 'bw'), $duplicate_count)); ?></strong>
                                <ul style="margin: 4px 0 0 20px;">
                                    <?php foreach ($duplicates as $header => $positions) : ?>
                                        <li>
                                            <?php
                                            echo esc_html(
                                                sprintf(
                                                    /* translators: 1: header label, 2: column positions */
                                                    __('%1$s (columns: %2$s)', 'bw'),
                                                    $header,
                                                    implode(', ', array_map('intval', (array) $positions))
                                                )
                                            );
                                            ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <?php esc_html_e('No duplicate header names detected.', 'bw'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (!empty($state['headers'])) : ?>
            <hr />
            <h3><?php esc_html_e('2. Map CSV columns', 'bw'); ?></h3>
            <p><?php esc_html_e('Match each CSV column to a WooCommerce field or a custom meta field.', 'bw'); ?></p>

            <form method="post">
                <?php wp_nonce_field('bw_import_run', 'bw_import_run_nonce'); ?>
                <table class="widefat fixed" style="max-width:900px;">
                    <thead>
                    <tr>
                        <th style="width:50%;"><?php esc_html_e('CSV Column', 'bw'); ?></th>
                        <th><?php esc_html_e('Map To', 'bw'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $options = bw_import_get_mapping_options();
                    $auto_mapping = bw_import_guess_mapping($state['headers'], $options);
                    $submitted_mapping = [];

                    if (!empty($_POST['bw_import_mapping'])) {
                        foreach ((array) $_POST['bw_import_mapping'] as $submitted_header => $submitted_value) {
                            $submitted_mapping[$submitted_header] = sanitize_text_field(wp_unslash($submitted_value));
                        }
                    }

                    foreach ($state['headers'] as $header) :
                        $current_value = isset($submitted_mapping[$header])
                            ? $submitted_mapping[$header]
                            : (isset($auto_mapping[$header]) ? $auto_mapping[$header] : 'ignore');
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($header); ?></strong></td>
                            <td>
                                <select name="bw_import_mapping[<?php echo esc_attr($header); ?>]" style="width:100%;">
                                    <option value="ignore" <?php selected($current_value, 'ignore'); ?>><?php esc_html_e('Ignore this column', 'bw'); ?></option>
                                    <?php foreach ($options as $group_label => $group_options) : ?>
                                        <optgroup label="<?php echo esc_attr($group_label); ?>">
                                            <?php foreach ($group_options as $key => $label) : ?>
                                                <option value="<?php echo esc_attr($key); ?>" <?php selected($current_value, $key); ?>><?php echo esc_html($label); ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <?php
                    endforeach;
                    ?>
                    </tbody>
                </table>

                <p><strong><?php esc_html_e('Preview (first 5 rows):', 'bw'); ?></strong></p>
                <div style="overflow:auto; max-width:900px;">
                    <table class="widefat striped">
                        <thead>
                        <tr>
                            <?php foreach ($state['headers'] as $header) : ?>
                                <th><?php echo esc_html($header); ?></th>
                            <?php endforeach; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($state['sample'] as $sample_row) : ?>
                            <tr>
                                <?php foreach ($state['headers'] as $index => $header) : ?>
                                    <td><?php echo isset($sample_row[$index]) ? esc_html($sample_row[$index]) : ''; ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php submit_button(__('Save Mapping & Run Import', 'bw'), 'primary', 'bw_import_run'); ?>
            </form>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Gestisce il caricamento del CSV e salva lo stato temporaneo.
 *
 * @return array|WP_Error
 */
function bw_import_handle_upload_request() {
    if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
        return new WP_Error('bw_import_permission', __('You do not have permission to upload files.', 'bw'));
    }

    if (!isset($_POST['bw_import_upload_nonce']) || !wp_verify_nonce($_POST['bw_import_upload_nonce'], 'bw_import_upload')) {
        return new WP_Error('bw_import_nonce', __('Invalid nonce. Please try again.', 'bw'));
    }

    if (empty($_FILES['bw_import_csv']['name'])) {
        return new WP_Error('bw_import_file', __('Please select a CSV file to upload.', 'bw'));
    }

    add_filter('upload_dir', 'bw_import_upload_dir');
    $upload = wp_handle_upload(
        $_FILES['bw_import_csv'],
        [
            'test_form' => false,
            'mimes'     => [ 'csv' => 'text/csv', 'txt' => 'text/plain' ],
        ]
    );
    remove_filter('upload_dir', 'bw_import_upload_dir');

    if (isset($upload['error'])) {
        return new WP_Error('bw_import_upload_error', $upload['error']);
    }

    $parsed = bw_import_parse_csv_file($upload['file'], 5);
    if (is_wp_error($parsed)) {
        return $parsed;
    }

    $summary = bw_import_calculate_header_stats($parsed['headers']);

    $update_existing = !empty($_POST['bw_import_update_existing']);

    $state = [
        'file_path' => $upload['file'],
        'file_url'  => $upload['url'],
        'headers'   => $parsed['headers'],
        'sample'    => $parsed['rows'],
        'update_existing' => $update_existing,
        'upload_summary' => [
            'file_name'     => basename($upload['file']),
            'total_fields'  => $summary['total'],
            'loaded_fields' => $summary['loaded'],
            'missing'       => $summary['missing'],
            'replaced'      => $summary['replaced'],
            'replaced_count'=> $summary['replaced_count'],
            'duplicates'    => $summary['duplicates'],
            'duplicate_count' => $summary['duplicate_count'],
        ],
    ];

    bw_import_save_state($state);

    return $state;
}

/**
 * Gestisce l'esecuzione dell'import.
 *
 * @param array $state Stato corrente dell'upload.
 *
 * @return array|WP_Error
 */
function bw_import_handle_run_request($state) {
    if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
        return new WP_Error('bw_import_permission', __('You do not have permission to run the import.', 'bw'));
    }

    if (!isset($_POST['bw_import_run_nonce']) || !wp_verify_nonce($_POST['bw_import_run_nonce'], 'bw_import_run')) {
        return new WP_Error('bw_import_nonce', __('Invalid nonce. Please try again.', 'bw'));
    }

    if (empty($state['file_path']) || empty($state['headers'])) {
        return new WP_Error('bw_import_missing_state', __('No CSV file is attached. Upload a file before running the import.', 'bw'));
    }

    $raw_mapping = isset($_POST['bw_import_mapping']) ? (array) $_POST['bw_import_mapping'] : [];
    $mapping     = [];
    foreach ($state['headers'] as $header) {
        $value = isset($raw_mapping[$header]) ? sanitize_text_field(wp_unslash($raw_mapping[$header])) : 'ignore';
        if ('ignore' !== $value) {
            $mapping[$header] = $value;
        }
    }

    if (!bw_import_has_identifier($mapping)) {
        return new WP_Error('bw_import_missing_identifier', __('Please map at least Product ID, SKU, or Title to proceed.', 'bw'));
    }

    $parsed = bw_import_parse_csv_file($state['file_path']);
    if (is_wp_error($parsed)) {
        return $parsed;
    }

    $update_existing = !empty($state['update_existing']);

    $result  = bw_import_process_rows($parsed['headers'], $parsed['rows'], $mapping, $update_existing);
    $message = sprintf(
        /* translators: 1: created count, 2: updated count, 3: skipped count */
        __('Import completed. Created: %1$d, Updated: %2$d, Skipped: %3$d', 'bw'),
        (int) $result['created'],
        (int) $result['updated'],
        (int) $result['skipped']
    );

    if (!empty($result['errors'])) {
        $message .= ' — ' . implode(' | ', array_map('esc_html', $result['errors']));
    }

    bw_import_clear_state();

    return [
        'message' => $message,
    ];
}

/**
 * Percorso di upload personalizzato per i CSV dell'importer.
 *
 * @param array $dirs Directory upload corrente.
 *
 * @return array
 */
function bw_import_upload_dir($dirs) {
    $dirs['subdir'] = '/blackwork-import';
    $dirs['path']   = $dirs['basedir'] . $dirs['subdir'];
    $dirs['url']    = $dirs['baseurl'] . $dirs['subdir'];
    return $dirs;
}

/**
 * Salva lo stato dell'import in un transient per l'utente corrente.
 *
 * @param array $state Stato da salvare.
 */
function bw_import_save_state($state) {
    set_transient('bw_import_state_' . get_current_user_id(), $state, HOUR_IN_SECONDS);
}

/**
 * Recupera lo stato salvato.
 *
 * @return array
 */
function bw_import_get_state() {
    $state = get_transient('bw_import_state_' . get_current_user_id());
    return is_array($state) ? $state : [];
}

/**
 * Pulisce lo stato di importazione.
 */
function bw_import_clear_state() {
    delete_transient('bw_import_state_' . get_current_user_id());
}

/**
 * Effettua il parse del CSV.
 *
 * @param string $file_path Percorso del file.
 * @param int    $max_rows  Numero massimo di righe da leggere (0 = tutte).
 *
 * @return array|WP_Error
 */
function bw_import_parse_csv_file($file_path, $max_rows = 0) {
    if (!file_exists($file_path)) {
        return new WP_Error('bw_import_missing_file', __('The uploaded CSV file cannot be found.', 'bw'));
    }

    $handle = fopen($file_path, 'r');
    if (!$handle) {
        return new WP_Error('bw_import_open_error', __('Unable to open the CSV file.', 'bw'));
    }

    $headers = fgetcsv($handle);
    if (empty($headers)) {
        fclose($handle);
        return new WP_Error('bw_import_headers', __('The CSV file is missing a header row.', 'bw'));
    }

    $rows      = [];
    $row_count = 0;
    while (($data = fgetcsv($handle)) !== false) {
        $rows[] = $data;
        $row_count++;
        if ($max_rows > 0 && $row_count >= $max_rows) {
            break;
        }
    }

    fclose($handle);

    return [
        'headers' => $headers,
        'rows'    => $rows,
    ];
}

/**
 * Genera un riepilogo dei campi trovati nel CSV caricato.
 *
 * @param array $headers Elenco delle intestazioni.
 *
 * @return array
 */
function bw_import_calculate_header_stats($headers) {
    $clean_headers    = array_map('trim', (array) $headers);
    $total_fields     = count($clean_headers);
    $loaded_headers   = array_filter($clean_headers, static function ($header) {
        return '' !== $header;
    });
    $missing_headers  = [];
    $replaced_headers = [];
    $duplicates       = [];
    $header_positions = [];

    foreach ($clean_headers as $index => $header) {
        if ('' === $header) {
            $placeholder       = sprintf(
                /* translators: %d: column index */
                __('Column %d (missing header name)', 'bw'),
                (int) $index + 1
            );
            $missing_headers[]  = $placeholder;
            $replaced_headers[] = $placeholder;
            continue;
        }

        $normalized = strtolower($header);
        if (!isset($header_positions[$normalized])) {
            $header_positions[$normalized] = [];
        }

        $header_positions[$normalized][] = (int) $index + 1;
    }

    foreach ($header_positions as $header => $positions) {
        if (count($positions) > 1) {
            $duplicates[$header] = $positions;
        }
    }

    return [
        'total'   => $total_fields,
        'loaded'  => count($loaded_headers),
        'missing' => $missing_headers,
        'replaced' => $replaced_headers,
        'replaced_count' => count($replaced_headers),
        'duplicates' => $duplicates,
        'duplicate_count' => array_sum(array_map(static function ($positions) {
            return max(0, count($positions) - 1);
        }, $duplicates)),
    ];
}

/**
 * Restituisce le opzioni di mapping organizzate per gruppo.
 *
 * @return array
 */
function bw_import_get_mapping_options() {
    $options = [
        __('Product Core', 'bw') => [
            'product_id'    => __('Product ID', 'bw'),
            'sku'           => __('Product SKU', 'bw'),
            'post_title'    => __('Product Title (post_title)', 'bw'),
            'post_name'     => __('Product Slug (post_name)', 'bw'),
            'post_status'   => __('Product Status', 'bw'),
            'product_type'  => __('Product Type', 'bw'),
            'post_content'  => __('Product Description (post_content)', 'bw'),
            'post_excerpt'  => __('Product Short Description (post_excerpt)', 'bw'),
        ],
        __('Pricing', 'bw') => [
            'regular_price'        => __('Regular Price', 'bw'),
            'sale_price'           => __('Sale Price', 'bw'),
            'sale_price_dates_from'=> __('Sale Start Date', 'bw'),
            'sale_price_dates_to'  => __('Sale End Date', 'bw'),
        ],
        __('Inventory', 'bw') => [
            'stock_quantity'    => __('Stock Quantity', 'bw'),
            'manage_stock'      => __('Manage Stock (yes/no)', 'bw'),
            'stock_status'      => __('Stock Status', 'bw'),
            'backorders'        => __('Backorders', 'bw'),
            'sold_individually' => __('Sold Individually', 'bw'),
        ],
        __('Shipping', 'bw') => [
            'weight'         => __('Weight', 'bw'),
            'length'         => __('Length', 'bw'),
            'width'          => __('Width', 'bw'),
            'height'         => __('Height', 'bw'),
            'shipping_class' => __('Shipping Class', 'bw'),
        ],
        __('Tax', 'bw') => [
            'tax_status' => __('Tax Status', 'bw'),
            'tax_class'  => __('Tax Class', 'bw'),
        ],
        __('Categories & Tags', 'bw') => [
            'categories' => __('Product Categories (comma separated)', 'bw'),
            'tags'       => __('Product Tags (comma separated)', 'bw'),
        ],
        __('Images', 'bw') => [
            'featured_image' => __('Product Image (featured image URL)', 'bw'),
            'gallery_images' => __('Product Gallery (comma-separated image URLs)', 'bw'),
        ],
        __('Links', 'bw') => [
            'upsells'     => __('Upsells (comma-separated IDs or SKUs)', 'bw'),
            'cross_sells' => __('Cross-sells (comma-separated IDs or SKUs)', 'bw'),
        ],
    ];

    $attribute_options = bw_import_attribute_options();
    if (!empty($attribute_options)) {
        $options[__('Attributes', 'bw')] = $attribute_options;
    }

    $meta_fields = bw_import_detect_custom_meta_fields();

    $product_slider_meta = bw_import_product_slider_meta_options($meta_fields);
    if (!empty($product_slider_meta)) {
        $options[__('MetaFields', 'bw')] = $product_slider_meta;
    }

    if (!empty($meta_fields)) {
        $meta_fields = array_values(array_diff($meta_fields, ['_bw_slider_hover_image']));
        if (!empty($meta_fields)) {
            $meta_group = [];
            foreach ($meta_fields as $meta_key) {
                $meta_group['meta:' . $meta_key] = sprintf(__('Meta: %1$s (%2$s)', 'bw'), bw_import_pretty_meta_label($meta_key), $meta_key);
            }
            $options[__('Custom Meta Fields (Metabox)', 'bw')] = $meta_group;
        }
    }

    return $options;
}

/**
 * Prova ad effettuare un auto-mapping basato sul nome della colonna.
 *
 * @param array $headers  Header del CSV.
 * @param array $options  Opzioni di mapping organizzate per gruppo.
 *
 * @return array
 */
function bw_import_guess_mapping($headers, $options) {
    $flat_options = [];
    foreach ($options as $group_options) {
        foreach ($group_options as $key => $label) {
            $flat_options[$key] = [
                'normalized_key'   => bw_import_normalize_mapping_key($key),
                'normalized_label' => bw_import_normalize_string($label),
            ];
        }
    }

    $aliases = bw_import_get_mapping_aliases();
    $guessed = [];

    foreach ($headers as $header) {
        $normalized_header = bw_import_normalize_string($header);

        if (isset($aliases[$normalized_header]) && isset($flat_options[$aliases[$normalized_header]])) {
            $guessed[$header] = $aliases[$normalized_header];
            continue;
        }

        foreach ($flat_options as $key => $normalized) {
            if ($normalized_header === $normalized['normalized_key'] || $normalized_header === $normalized['normalized_label']) {
                $guessed[$header] = $key;
                break;
            }
        }
    }

    return $guessed;
}

/**
 * Normalizza una stringa per renderla confrontabile.
 *
 * @param string $value Valore da normalizzare.
 *
 * @return string
 */
function bw_import_normalize_string($value) {
    $value = strtolower((string) $value);
    $value = preg_replace('/[^a-z0-9]+/', '_', $value);
    return trim($value, '_');
}

/**
 * Normalizza la chiave di mapping.
 *
 * @param string $key Chiave di mapping (es. meta:_foo, attribute_color).
 *
 * @return string
 */
function bw_import_normalize_mapping_key($key) {
    if (strpos($key, 'meta:') === 0) {
        $key = substr($key, 5);
    }

    if (strpos($key, 'attribute_') === 0) {
        $key = substr($key, strlen('attribute_'));
    }

    return bw_import_normalize_string($key);
}

/**
 * Restituisce alias comuni per gli header del CSV.
 *
 * @return array
 */
function bw_import_get_mapping_aliases() {
    $aliases = [
        'title'             => 'post_title',
        'product_title'     => 'post_title',
        'producttitle'      => 'post_title',
        'name'              => 'post_title',
        'product_name'      => 'post_title',
        'slug'              => 'post_name',
        'status'            => 'post_status',
        'type'              => 'product_type',
        'description'       => 'post_content',
        'long_description'  => 'post_content',
        'short_description' => 'post_excerpt',
        'regular_price'     => 'regular_price',
        'price'             => 'regular_price',
        'sale_price'        => 'sale_price',
        'discount_price'    => 'sale_price',
        'qty'               => 'stock_quantity',
        'quantity'          => 'stock_quantity',
        'stock'             => 'stock_quantity',
        'featured_image'    => 'featured_image',
        'image'             => 'featured_image',
        'gallery'           => 'gallery_images',
        'category'          => 'categories',
        'categories'        => 'categories',
        'tag'               => 'tags',
        'tags'              => 'tags',
        'upsell'            => 'upsells',
        'upsells'           => 'upsells',
        'crosssell'         => 'cross_sells',
        'cross_sells'       => 'cross_sells',
    ];

    $normalized_aliases = [];
    foreach ($aliases as $alias => $target) {
        $normalized_aliases[bw_import_normalize_string($alias)] = $target;
    }

    return $normalized_aliases;
}

/**
 * Rileva i meta fields presenti nei file del metabox.
 *
 * @return array
 */
function bw_import_detect_custom_meta_fields() {
    $meta_keys  = [];

    $metabox_functions = [
        'bw_get_bibliographic_fields',
        'bw_get_prints_bibliographic_fields',
        'bw_get_digital_product_fields',
    ];

    foreach ($metabox_functions as $meta_function) {
        if (!function_exists($meta_function)) {
            continue;
        }

        $fields = call_user_func($meta_function);
        if (empty($fields) || !is_array($fields)) {
            continue;
        }

        foreach (array_keys($fields) as $meta_key) {
            if (strpos($meta_key, '_') === 0) {
                $meta_keys[$meta_key] = true;
            }
        }
    }

    $meta_directories = [
        trailingslashit(BW_MEW_PATH) . 'metabox/',
        trailingslashit(BW_MEW_PATH) . 'includes/product-types/',
    ];

    foreach ($meta_directories as $directory) {
        if (!is_dir($directory)) {
            continue;
        }

        foreach (glob($directory . '*.php') as $file) {
            $contents = file_get_contents($file);
            if (!$contents) {
                continue;
            }

            if (preg_match_all("/(?:update_post_meta|add_post_meta|get_post_meta)\s*\(\s*\$[a-zA-Z0-9_\->]+\s*,\s*'([^']+)'/", $contents, $matches)) {
                foreach ($matches[1] as $meta_key) {
                    if (strpos($meta_key, '_') === 0) {
                        $meta_keys[$meta_key] = true;
                    }
                }
            }
        }
    }

    return array_keys($meta_keys);
}

/**
 * Restituisce le opzioni di mapping per il meta field dello slider prodotto.
 *
 * @param array $detected_meta Meta rilevati automaticamente.
 *
 * @return array
 */
function bw_import_product_slider_meta_options($detected_meta) {
    $meta_key = '_bw_slider_hover_image';

    if (!in_array($meta_key, $detected_meta, true)) {
        $detected_meta[] = $meta_key;
    }

    return [
        'meta:' . $meta_key => sprintf(__('Image over (%s)', 'bw'), $meta_key),
    ];
}

/**
 * Genera opzioni per gli attributi globali WooCommerce.
 *
 * @return array
 */
function bw_import_attribute_options() {
    $options = [];
    if (!function_exists('wc_get_attribute_taxonomies')) {
        return $options;
    }

    $attributes = wc_get_attribute_taxonomies();
    if (empty($attributes)) {
        return $options;
    }

    foreach ($attributes as $attribute) {
        $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);
        $options['attribute_' . $taxonomy] = sprintf(__('Global Attribute: %s', 'bw'), $attribute->attribute_label);
    }

    return $options;
}

/**
 * Converte la chiave meta in etichetta leggibile.
 *
 * @param string $meta_key Meta key.
 *
 * @return string
 */
function bw_import_pretty_meta_label($meta_key) {
    $label = str_replace('_', ' ', $meta_key);
    $label = trim($label, ' _');
    return ucwords($label);
}

/**
 * Verifica che ci sia almeno un identificativo prodotto mappato.
 *
 * @param array $mapping Mapping selezionato.
 *
 * @return bool
 */
function bw_import_has_identifier($mapping) {
    $values = array_values($mapping);
    return in_array('product_id', $values, true) || in_array('sku', $values, true) || in_array('post_title', $values, true);
}

/**
 * Elabora le righe del CSV in base al mapping.
 *
 * @param array $headers  Header del CSV.
 * @param array $rows     Righe del CSV.
 * @param array $mapping  Mapping colonne -> campi.
 *
 * @return array
 */
function bw_import_process_rows($headers, $rows, $mapping, $update_existing = false) {
    $result = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors'  => [],
    ];

    foreach ($rows as $row_index => $row) {
        $row_data = [];
        foreach ($headers as $i => $header) {
            $row_data[$header] = isset($row[$i]) ? $row[$i] : '';
        }

        $prepared = bw_import_prepare_row_data($row_data, $mapping);
        if (is_wp_error($prepared)) {
            $result['skipped']++;
            $result['errors'][] = sprintf(__('Row %1$d: %2$s', 'bw'), $row_index + 2, $prepared->get_error_message());
            continue;
        }

        $save_result = bw_import_save_product_from_row($prepared, $update_existing);
        if (is_wp_error($save_result)) {
            $result['skipped']++;
            $result['errors'][] = sprintf(__('Row %1$d: %2$s', 'bw'), $row_index + 2, $save_result->get_error_message());
            continue;
        }

        if ($save_result === 'updated') {
            $result['updated']++;
        } else {
            $result['created']++;
        }
    }

    return $result;
}

/**
 * Prepara i dati della riga in base al mapping.
 *
 * @param array $row_data Dati riga.
 * @param array $mapping  Mapping.
 *
 * @return array|WP_Error
 */
function bw_import_prepare_row_data($row_data, $mapping) {
    $data = [
        'product'     => [],
        'meta'        => [],
        'categories'  => [],
        'tags'        => [],
        'attributes'  => [],
        'upsells'     => [],
        'cross_sells' => [],
    ];

    foreach ($row_data as $header => $value) {
        $target = isset($mapping[$header]) ? $mapping[$header] : 'ignore';
        if ('ignore' === $target) {
            continue;
        }

        $clean_value = is_string($value) ? trim(wp_unslash($value)) : $value;

        if (strpos($target, 'meta:') === 0) {
            $meta_key              = substr($target, 5);
            $data['meta'][$meta_key] = $clean_value;
            continue;
        }

        if (strpos($target, 'attribute_') === 0) {
            $taxonomy                          = substr($target, strlen('attribute_'));
            $data['attributes'][$taxonomy] = $clean_value;
            continue;
        }

        switch ($target) {
            case 'product_id':
                $data['product']['id'] = absint($clean_value);
                break;
            case 'sku':
                $data['product']['sku'] = sanitize_text_field($clean_value);
                break;
            case 'post_title':
                $data['product']['name'] = sanitize_text_field($clean_value);
                break;
            case 'post_name':
                $data['product']['slug'] = sanitize_title($clean_value);
                break;
            case 'post_status':
                $data['product']['status'] = sanitize_key($clean_value);
                break;
            case 'product_type':
                $data['product']['type'] = sanitize_key($clean_value);
                break;
            case 'post_content':
                $data['product']['description'] = wp_kses_post($clean_value);
                break;
            case 'post_excerpt':
                $data['product']['short_description'] = wp_kses_post($clean_value);
                break;
            case 'regular_price':
                $data['product']['regular_price'] = wc_format_decimal($clean_value);
                break;
            case 'sale_price':
                $data['product']['sale_price'] = wc_format_decimal($clean_value);
                break;
            case 'sale_price_dates_from':
                $data['product']['sale_start'] = sanitize_text_field($clean_value);
                break;
            case 'sale_price_dates_to':
                $data['product']['sale_end'] = sanitize_text_field($clean_value);
                break;
            case 'stock_quantity':
                $data['product']['stock_quantity'] = (float) $clean_value;
                break;
            case 'manage_stock':
                $data['product']['manage_stock'] = in_array(strtolower($clean_value), ['yes', '1', 'true'], true);
                break;
            case 'stock_status':
                $data['product']['stock_status'] = sanitize_key($clean_value);
                break;
            case 'backorders':
                $data['product']['backorders'] = sanitize_key($clean_value);
                break;
            case 'sold_individually':
                $data['product']['sold_individually'] = in_array(strtolower($clean_value), ['yes', '1', 'true'], true);
                break;
            case 'weight':
            case 'length':
            case 'width':
            case 'height':
                $data['product'][$target] = wc_format_decimal($clean_value);
                break;
            case 'shipping_class':
                $data['product']['shipping_class'] = sanitize_title($clean_value);
                break;
            case 'tax_status':
                $data['product']['tax_status'] = sanitize_key($clean_value);
                break;
            case 'tax_class':
                $data['product']['tax_class'] = sanitize_title($clean_value);
                break;
            case 'categories':
                $data['categories'] = bw_import_explode_list($clean_value);
                break;
            case 'tags':
                $data['tags'] = bw_import_explode_list($clean_value);
                break;
            case 'featured_image':
                $data['product']['featured_image'] = esc_url_raw($clean_value);
                break;
            case 'gallery_images':
                $data['product']['gallery'] = array_map('esc_url_raw', bw_import_explode_list($clean_value));
                break;
            case 'upsells':
                $data['upsells'] = bw_import_explode_list($clean_value);
                break;
            case 'cross_sells':
                $data['cross_sells'] = bw_import_explode_list($clean_value);
                break;
        }
    }

    if (empty($data['product']['id']) && empty($data['product']['sku']) && empty($data['product']['name'])) {
        return new WP_Error('bw_import_missing_identifiers', __('Missing Product ID, SKU or Title for this row.', 'bw'));
    }

    return $data;
}

/**
 * Suddivide una stringa in array usando virgola o pipe.
 *
 * @param string $value Valore da esplodere.
 *
 * @return array
 */
function bw_import_explode_list($value) {
    $value = (string) $value;
    $parts = preg_split('/[|,]/', $value);
    $parts = array_filter(array_map('trim', $parts));
    return $parts;
}

/**
 * Salva un prodotto a partire dai dati di riga.
 *
 * @param array $data             Dati preparati.
 * @param bool  $update_existing  Se true, aggiorna solo prodotti già esistenti.
 *
 * @return string|WP_Error
 */
function bw_import_save_product_from_row($data, $update_existing = false) {
    $product_id = isset($data['product']['id']) ? absint($data['product']['id']) : 0;
    $sku        = isset($data['product']['sku']) ? $data['product']['sku'] : '';
    $product    = null;
    $status     = 'created';

    if ($product_id) {
        $product = wc_get_product($product_id);
    }

    if (!$product && $sku) {
        $maybe_id = wc_get_product_id_by_sku($sku);
        if ($maybe_id) {
            $product   = wc_get_product($maybe_id);
            $product_id = $maybe_id;
        }
    }

    if ($product) {
        $status = 'updated';
    } elseif ($update_existing) {
        return new WP_Error(
            'bw_import_missing_product_match',
            __('Skipping row because no existing product matches the provided ID or SKU.', 'bw')
        );
    } else {
        $product_type = !empty($data['product']['type']) ? $data['product']['type'] : 'simple';

        try {
            $product = wc_get_product_object($product_type);
        } catch (Throwable $exception) {
            return new WP_Error('bw_import_product_object', $exception->getMessage());
        }

        if (!$product) {
            return new WP_Error('bw_import_product_object', __('Unable to create product object for type.', 'bw'));
        }
    }

    if (!empty($data['product']['name'])) {
        $product->set_name($data['product']['name']);
    }

    if (!empty($data['product']['slug'])) {
        $product->set_slug($data['product']['slug']);
    }

    if (!empty($data['product']['status'])) {
        $product->set_status($data['product']['status']);
    }

    if (!empty($data['product']['description'])) {
        $product->set_description($data['product']['description']);
    }

    if (!empty($data['product']['short_description'])) {
        $product->set_short_description($data['product']['short_description']);
    }

    if ($sku) {
        try {
            $product->set_sku($sku);
        } catch (WC_Data_Exception $exception) {
            return new WP_Error('bw_import_sku', $exception->getMessage());
        }
    }

    if (isset($data['product']['regular_price'])) {
        $product->set_regular_price($data['product']['regular_price']);
    }

    if (isset($data['product']['sale_price'])) {
        $product->set_sale_price($data['product']['sale_price']);
    }

    if (!empty($data['product']['sale_start'])) {
        $product->set_date_on_sale_from($data['product']['sale_start']);
    }

    if (!empty($data['product']['sale_end'])) {
        $product->set_date_on_sale_to($data['product']['sale_end']);
    }

    if (isset($data['product']['stock_quantity'])) {
        $product->set_stock_quantity($data['product']['stock_quantity']);
    }

    if (isset($data['product']['manage_stock'])) {
        $product->set_manage_stock((bool) $data['product']['manage_stock']);
    }

    if (!empty($data['product']['stock_status'])) {
        $product->set_stock_status($data['product']['stock_status']);
    }

    if (!empty($data['product']['backorders'])) {
        $product->set_backorders($data['product']['backorders']);
    }

    if (isset($data['product']['sold_individually'])) {
        $product->set_sold_individually((bool) $data['product']['sold_individually']);
    }

    foreach (['weight', 'length', 'width', 'height'] as $dimension) {
        if (isset($data['product'][$dimension])) {
            $setter = 'set_' . $dimension;
            $product->$setter($data['product'][$dimension]);
        }
    }

    if (!empty($data['product']['shipping_class'])) {
        $shipping_class_id = 0;

        if (is_numeric($data['product']['shipping_class'])) {
            $shipping_class_id = (int) $data['product']['shipping_class'];
        } else {
            $existing_shipping_class = term_exists($data['product']['shipping_class'], 'product_shipping_class');

            if ($existing_shipping_class && !is_wp_error($existing_shipping_class)) {
                $shipping_class_id = (int) $existing_shipping_class['term_id'];
            } else {
                $created_shipping_class = wp_insert_term($data['product']['shipping_class'], 'product_shipping_class');

                if ($created_shipping_class && !is_wp_error($created_shipping_class)) {
                    $shipping_class_id = (int) $created_shipping_class['term_id'];
                }
            }
        }

        if ($shipping_class_id) {
            $product->set_shipping_class_id($shipping_class_id);
        }
    }

    if (!empty($data['product']['tax_status'])) {
        $product->set_tax_status($data['product']['tax_status']);
    }

    if (!empty($data['product']['tax_class'])) {
        $product->set_tax_class($data['product']['tax_class']);
    }

    try {
        $product_id = $product->save();
    } catch (Throwable $exception) {
        return new WP_Error('bw_import_save', $exception->getMessage());
    }

    if (!$product_id) {
        return new WP_Error('bw_import_save', __('Unable to save the product.', 'bw'));
    }

    if (!empty($data['categories'])) {
        bw_import_assign_terms($product_id, $data['categories'], 'product_cat');
    }

    if (!empty($data['tags'])) {
        bw_import_assign_terms($product_id, $data['tags'], 'product_tag');
    }

    if (!empty($data['meta'])) {
        foreach ($data['meta'] as $meta_key => $meta_value) {
            update_post_meta($product_id, $meta_key, $meta_value);
        }
    }

    if (!empty($data['product']['featured_image'])) {
        $attachment_id = bw_import_handle_image($data['product']['featured_image'], $product_id);
        if ($attachment_id) {
            set_post_thumbnail($product_id, $attachment_id);
        }
    }

    if (!empty($data['product']['gallery'])) {
        $gallery_ids = [];
        foreach ($data['product']['gallery'] as $image_url) {
            $image_id = bw_import_handle_image($image_url, $product_id);
            if ($image_id) {
                $gallery_ids[] = $image_id;
            }
        }
        if (!empty($gallery_ids)) {
            $product->set_gallery_image_ids($gallery_ids);
            try {
                $product->save();
            } catch (Throwable $exception) {
                return new WP_Error('bw_import_save', $exception->getMessage());
            }
        }
    }

    if (!empty($data['attributes'])) {
        bw_import_apply_attributes($product_id, $data['attributes']);
    }

    if (!empty($data['upsells'])) {
        $product->set_upsell_ids(bw_import_locate_product_ids($data['upsells']));
    }

    if (!empty($data['cross_sells'])) {
        $product->set_cross_sell_ids(bw_import_locate_product_ids($data['cross_sells']));
    }

    try {
        $product->save();
    } catch (Throwable $exception) {
        return new WP_Error('bw_import_save', $exception->getMessage());
    }

    return $status;
}

/**
 * Recupera ID prodotto da ID o SKU.
 *
 * @param array $references Elenco di riferimenti.
 *
 * @return array
 */
function bw_import_locate_product_ids($references) {
    $ids = [];
    foreach ($references as $reference) {
        $reference = trim($reference);
        if (is_numeric($reference)) {
            $ids[] = (int) $reference;
            continue;
        }

        $maybe_id = wc_get_product_id_by_sku($reference);
        if ($maybe_id) {
            $ids[] = $maybe_id;
        }
    }

    return $ids;
}

/**
 * Imposta termini su tassonomie prodotto.
 *
 * @param int    $product_id ID prodotto.
 * @param array  $terms      Elenco termini.
 * @param string $taxonomy   Tassonomia.
 */
function bw_import_assign_terms($product_id, $terms, $taxonomy) {
    $term_ids = [];
    foreach ($terms as $term) {
        $existing = term_exists($term, $taxonomy);
        if ($existing && !is_wp_error($existing)) {
            $term_ids[] = (int) $existing['term_id'];
        } else {
            $created = wp_insert_term($term, $taxonomy);
            if (!is_wp_error($created)) {
                $term_ids[] = (int) $created['term_id'];
            }
        }
    }

    if (!empty($term_ids)) {
        wp_set_object_terms($product_id, $term_ids, $taxonomy, false);
    }
}

/**
 * Gestisce il download e l'associazione di immagini da URL.
 *
 * @param string $image_url  URL immagine.
 * @param int    $product_id ID prodotto.
 *
 * @return int Attachment ID.
 */
function bw_import_handle_image($image_url, $product_id) {
    if (empty($image_url)) {
        return 0;
    }

    if (!function_exists('media_sideload_image')) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    $image_id = attachment_url_to_postid($image_url);
    if ($image_id) {
        return $image_id;
    }

    $sideload = media_sideload_image($image_url, $product_id, null, 'id');
    if (is_wp_error($sideload)) {
        return 0;
    }

    return (int) $sideload;
}

/**
 * Applica attributi globali al prodotto.
 *
 * @param int   $product_id ID prodotto.
 * @param array $attributes Attributi.
 */
function bw_import_apply_attributes($product_id, $attributes) {
    $product_attributes = [];

    foreach ($attributes as $taxonomy => $value) {
        $terms = bw_import_explode_list($value);
        if (empty($terms)) {
            continue;
        }

        if (!taxonomy_exists($taxonomy)) {
            continue;
        }

        $term_ids = [];
        foreach ($terms as $term) {
            $existing = term_exists($term, $taxonomy);
            if ($existing && !is_wp_error($existing)) {
                $term_ids[] = (int) $existing['term_id'];
            } else {
                $inserted = wp_insert_term($term, $taxonomy);
                if (!is_wp_error($inserted)) {
                    $term_ids[] = (int) $inserted['term_id'];
                }
            }
        }

        if (!empty($term_ids)) {
            wp_set_object_terms($product_id, $term_ids, $taxonomy, false);
        }

        $attribute = new WC_Product_Attribute();
        $attribute->set_id(wc_attribute_taxonomy_id_by_name($taxonomy));
        $attribute->set_name($taxonomy);
        $attribute->set_options($term_ids);
        $attribute->set_visible(true);
        $attribute->set_variation(false);
        $product_attributes[$taxonomy] = $attribute;
    }

    if (!empty($product_attributes)) {
        $product = wc_get_product($product_id);
        if ($product) {
            $product->set_attributes($product_attributes);
            $product->save();
        }
    }
}

