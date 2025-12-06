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

    // JavaScript per la pagina admin (se necessario)
    wp_enqueue_script('jquery');
    wp_enqueue_media();

    $redirects_script_path = BW_MEW_PATH . 'admin/js/bw-redirects.js';
    $redirects_version     = file_exists($redirects_script_path) ? filemtime($redirects_script_path) : '1.0.0';

    wp_enqueue_script(
        'bw-redirects-admin',
        BW_MEW_URL . 'admin/js/bw-redirects.js',
        ['jquery'],
        $redirects_version,
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
            <a href="?page=blackwork-site-settings&tab=redirect"
               class="nav-tab <?php echo $active_tab === 'redirect' ? 'nav-tab-active' : ''; ?>">
                Redirect
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
            } elseif ($active_tab === 'redirect') {
                bw_site_render_redirect_tab();
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

    <script>
        jQuery(document).ready(function($) {
            // Toggle visibilità campi bordo per Checkout button
            function toggleCheckoutBorderFields() {
                const isEnabled = $('#bw_cart_popup_checkout_border_enabled').is(':checked');
                $('.bw-checkout-border-field').toggle(isEnabled);
            }

            // Toggle visibilità campi bordo per Continue button
            function toggleContinueBorderFields() {
                const isEnabled = $('#bw_cart_popup_continue_border_enabled').is(':checked');
                $('.bw-continue-border-field').toggle(isEnabled);
            }

            // Inizializza stato al caricamento pagina
            toggleCheckoutBorderFields();
            toggleContinueBorderFields();

            // Listener per checkbox
            $('#bw_cart_popup_checkout_border_enabled').on('change', toggleCheckoutBorderFields);
            $('#bw_cart_popup_continue_border_enabled').on('change', toggleContinueBorderFields);
        });
    </script>
    <?php
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
