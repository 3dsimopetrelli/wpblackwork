<?php
/**
 * BW Cart Pop-Up Settings Page
 *
 * Pannello admin per la configurazione del Cart Pop-Up
 *
 * @package BW_Cart_Popup
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Aggiungi la pagina di menu nel pannello admin
 */
function bw_cart_popup_admin_menu() {
    add_menu_page(
        'Cart Pop-Up Settings',
        'Cart Pop-Up',
        'manage_options',
        'bw-cart-popup',
        'bw_cart_popup_settings_page',
        'dashicons-cart',
        56
    );
}
add_action('admin_menu', 'bw_cart_popup_admin_menu');

/**
 * Salva le impostazioni del Cart Pop-Up
 */
function bw_cart_popup_save_settings() {
    if (!isset($_POST['bw_cart_popup_nonce']) || !wp_verify_nonce($_POST['bw_cart_popup_nonce'], 'bw_cart_popup_save')) {
        return;
    }

    if (!current_user_can('manage_options')) {
        return;
    }

    // Toggle ON/OFF
    update_option('bw_cart_popup_active', isset($_POST['bw_cart_popup_active']) ? 1 : 0);

    // Larghezza pannello (percentuale)
    $panel_width = isset($_POST['bw_cart_popup_panel_width']) ? intval($_POST['bw_cart_popup_panel_width']) : 400;
    update_option('bw_cart_popup_panel_width', $panel_width);

    // Colore overlay
    $overlay_color = isset($_POST['bw_cart_popup_overlay_color']) ? sanitize_hex_color($_POST['bw_cart_popup_overlay_color']) : '#000000';
    update_option('bw_cart_popup_overlay_color', $overlay_color);

    // Opacità overlay
    $overlay_opacity = isset($_POST['bw_cart_popup_overlay_opacity']) ? floatval($_POST['bw_cart_popup_overlay_opacity']) : 0.5;
    update_option('bw_cart_popup_overlay_opacity', $overlay_opacity);

    // Colore sfondo pannello
    $panel_bg = isset($_POST['bw_cart_popup_panel_bg']) ? sanitize_hex_color($_POST['bw_cart_popup_panel_bg']) : '#ffffff';
    update_option('bw_cart_popup_panel_bg', $panel_bg);

    // Testo pulsante checkout
    $checkout_text = isset($_POST['bw_cart_popup_checkout_text']) ? sanitize_text_field($_POST['bw_cart_popup_checkout_text']) : 'Proceed to checkout';
    update_option('bw_cart_popup_checkout_text', $checkout_text);

    // Colore pulsante checkout
    $checkout_color = isset($_POST['bw_cart_popup_checkout_color']) ? sanitize_hex_color($_POST['bw_cart_popup_checkout_color']) : '#28a745';
    update_option('bw_cart_popup_checkout_color', $checkout_color);

    // Testo pulsante continue shopping
    $continue_text = isset($_POST['bw_cart_popup_continue_text']) ? sanitize_text_field($_POST['bw_cart_popup_continue_text']) : 'Continue shopping';
    update_option('bw_cart_popup_continue_text', $continue_text);

    // Colore pulsante continue shopping
    $continue_color = isset($_POST['bw_cart_popup_continue_color']) ? sanitize_hex_color($_POST['bw_cart_popup_continue_color']) : '#6c757d';
    update_option('bw_cart_popup_continue_color', $continue_color);

    // SVG personalizzato per Cart Pop-Up
    $additional_svg = isset($_POST['bw_cart_popup_additional_svg']) ? wp_kses_post($_POST['bw_cart_popup_additional_svg']) : '';
    update_option('bw_cart_popup_additional_svg', $additional_svg);

    // Opzione per colorare SVG di nero
    update_option('bw_cart_popup_svg_black', isset($_POST['bw_cart_popup_svg_black']) ? 1 : 0);

    // === PROCEED TO CHECKOUT BUTTON SETTINGS ===
    // Background color
    $checkout_bg = isset($_POST['bw_cart_popup_checkout_bg']) ? sanitize_hex_color($_POST['bw_cart_popup_checkout_bg']) : '#28a745';
    update_option('bw_cart_popup_checkout_bg', $checkout_bg);

    // Background hover color
    $checkout_bg_hover = isset($_POST['bw_cart_popup_checkout_bg_hover']) ? sanitize_hex_color($_POST['bw_cart_popup_checkout_bg_hover']) : '#218838';
    update_option('bw_cart_popup_checkout_bg_hover', $checkout_bg_hover);

    // Text color
    $checkout_text_color = isset($_POST['bw_cart_popup_checkout_text_color']) ? sanitize_hex_color($_POST['bw_cart_popup_checkout_text_color']) : '#ffffff';
    update_option('bw_cart_popup_checkout_text_color', $checkout_text_color);

    // Text hover color
    $checkout_text_hover = isset($_POST['bw_cart_popup_checkout_text_hover']) ? sanitize_hex_color($_POST['bw_cart_popup_checkout_text_hover']) : '#ffffff';
    update_option('bw_cart_popup_checkout_text_hover', $checkout_text_hover);

    // Font size
    $checkout_font_size = isset($_POST['bw_cart_popup_checkout_font_size']) ? intval($_POST['bw_cart_popup_checkout_font_size']) : 14;
    update_option('bw_cart_popup_checkout_font_size', $checkout_font_size);

    // Border radius
    $checkout_border_radius = isset($_POST['bw_cart_popup_checkout_border_radius']) ? intval($_POST['bw_cart_popup_checkout_border_radius']) : 6;
    update_option('bw_cart_popup_checkout_border_radius', $checkout_border_radius);

    // Border enabled
    update_option('bw_cart_popup_checkout_border_enabled', isset($_POST['bw_cart_popup_checkout_border_enabled']) ? 1 : 0);

    // Border width
    $checkout_border_width = isset($_POST['bw_cart_popup_checkout_border_width']) ? intval($_POST['bw_cart_popup_checkout_border_width']) : 1;
    update_option('bw_cart_popup_checkout_border_width', $checkout_border_width);

    // Border style
    $checkout_border_style = isset($_POST['bw_cart_popup_checkout_border_style']) ? sanitize_text_field($_POST['bw_cart_popup_checkout_border_style']) : 'solid';
    update_option('bw_cart_popup_checkout_border_style', $checkout_border_style);

    // Border color
    $checkout_border_color = isset($_POST['bw_cart_popup_checkout_border_color']) ? sanitize_hex_color($_POST['bw_cart_popup_checkout_border_color']) : '#28a745';
    update_option('bw_cart_popup_checkout_border_color', $checkout_border_color);

    // Padding
    $checkout_padding_top = isset($_POST['bw_cart_popup_checkout_padding_top']) ? intval($_POST['bw_cart_popup_checkout_padding_top']) : 12;
    update_option('bw_cart_popup_checkout_padding_top', $checkout_padding_top);

    $checkout_padding_right = isset($_POST['bw_cart_popup_checkout_padding_right']) ? intval($_POST['bw_cart_popup_checkout_padding_right']) : 20;
    update_option('bw_cart_popup_checkout_padding_right', $checkout_padding_right);

    $checkout_padding_bottom = isset($_POST['bw_cart_popup_checkout_padding_bottom']) ? intval($_POST['bw_cart_popup_checkout_padding_bottom']) : 12;
    update_option('bw_cart_popup_checkout_padding_bottom', $checkout_padding_bottom);

    $checkout_padding_left = isset($_POST['bw_cart_popup_checkout_padding_left']) ? intval($_POST['bw_cart_popup_checkout_padding_left']) : 20;
    update_option('bw_cart_popup_checkout_padding_left', $checkout_padding_left);

    // === CONTINUE SHOPPING BUTTON SETTINGS ===
    // Background color
    $continue_bg = isset($_POST['bw_cart_popup_continue_bg']) ? sanitize_hex_color($_POST['bw_cart_popup_continue_bg']) : '#6c757d';
    update_option('bw_cart_popup_continue_bg', $continue_bg);

    // Background hover color
    $continue_bg_hover = isset($_POST['bw_cart_popup_continue_bg_hover']) ? sanitize_hex_color($_POST['bw_cart_popup_continue_bg_hover']) : '#5a6268';
    update_option('bw_cart_popup_continue_bg_hover', $continue_bg_hover);

    // Text color
    $continue_text_color = isset($_POST['bw_cart_popup_continue_text_color']) ? sanitize_hex_color($_POST['bw_cart_popup_continue_text_color']) : '#ffffff';
    update_option('bw_cart_popup_continue_text_color', $continue_text_color);

    // Text hover color
    $continue_text_hover = isset($_POST['bw_cart_popup_continue_text_hover']) ? sanitize_hex_color($_POST['bw_cart_popup_continue_text_hover']) : '#ffffff';
    update_option('bw_cart_popup_continue_text_hover', $continue_text_hover);

    // Font size
    $continue_font_size = isset($_POST['bw_cart_popup_continue_font_size']) ? intval($_POST['bw_cart_popup_continue_font_size']) : 14;
    update_option('bw_cart_popup_continue_font_size', $continue_font_size);

    // Border radius
    $continue_border_radius = isset($_POST['bw_cart_popup_continue_border_radius']) ? intval($_POST['bw_cart_popup_continue_border_radius']) : 6;
    update_option('bw_cart_popup_continue_border_radius', $continue_border_radius);

    // Border enabled
    update_option('bw_cart_popup_continue_border_enabled', isset($_POST['bw_cart_popup_continue_border_enabled']) ? 1 : 0);

    // Border width
    $continue_border_width = isset($_POST['bw_cart_popup_continue_border_width']) ? intval($_POST['bw_cart_popup_continue_border_width']) : 1;
    update_option('bw_cart_popup_continue_border_width', $continue_border_width);

    // Border style
    $continue_border_style = isset($_POST['bw_cart_popup_continue_border_style']) ? sanitize_text_field($_POST['bw_cart_popup_continue_border_style']) : 'solid';
    update_option('bw_cart_popup_continue_border_style', $continue_border_style);

    // Border color
    $continue_border_color = isset($_POST['bw_cart_popup_continue_border_color']) ? sanitize_hex_color($_POST['bw_cart_popup_continue_border_color']) : '#6c757d';
    update_option('bw_cart_popup_continue_border_color', $continue_border_color);

    // Padding
    $continue_padding_top = isset($_POST['bw_cart_popup_continue_padding_top']) ? intval($_POST['bw_cart_popup_continue_padding_top']) : 12;
    update_option('bw_cart_popup_continue_padding_top', $continue_padding_top);

    $continue_padding_right = isset($_POST['bw_cart_popup_continue_padding_right']) ? intval($_POST['bw_cart_popup_continue_padding_right']) : 20;
    update_option('bw_cart_popup_continue_padding_right', $continue_padding_right);

    $continue_padding_bottom = isset($_POST['bw_cart_popup_continue_padding_bottom']) ? intval($_POST['bw_cart_popup_continue_padding_bottom']) : 12;
    update_option('bw_cart_popup_continue_padding_bottom', $continue_padding_bottom);

    $continue_padding_left = isset($_POST['bw_cart_popup_continue_padding_left']) ? intval($_POST['bw_cart_popup_continue_padding_left']) : 20;
    update_option('bw_cart_popup_continue_padding_left', $continue_padding_left);

    // === EMPTY CART SETTINGS ===
    // Return to shop link
    $return_shop_url = isset($_POST['bw_cart_popup_return_shop_url']) ? esc_url_raw($_POST['bw_cart_popup_return_shop_url']) : '';
    update_option('bw_cart_popup_return_shop_url', $return_shop_url);

    return true;
}

/**
 * Renderizza la pagina delle impostazioni
 */
function bw_cart_popup_settings_page() {
    // Verifica permessi
    if (!current_user_can('manage_options')) {
        return;
    }

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
    $checkout_color = get_option('bw_cart_popup_checkout_color', '#28a745');
    $continue_text = get_option('bw_cart_popup_continue_text', 'Continue shopping');
    $continue_color = get_option('bw_cart_popup_continue_color', '#6c757d');
    $additional_svg = get_option('bw_cart_popup_additional_svg', '');
    $svg_black = get_option('bw_cart_popup_svg_black', 0);

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

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

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
                <tr>
                    <th scope="row">
                        <label for="bw_cart_popup_checkout_border_width">Spessore Bordo (px)</label>
                    </th>
                    <td>
                        <input type="number" id="bw_cart_popup_checkout_border_width" name="bw_cart_popup_checkout_border_width" value="<?php echo esc_attr($checkout_border_width); ?>" min="0" max="10" class="small-text" />
                        <p class="description">Spessore del bordo in pixel</p>
                    </td>
                </tr>

                <!-- Border Style -->
                <tr>
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
                <tr>
                    <th scope="row">
                        <label for="bw_cart_popup_checkout_border_color">Colore Bordo</label>
                    </th>
                    <td>
                        <input type="color" id="bw_cart_popup_checkout_border_color" name="bw_cart_popup_checkout_border_color" value="<?php echo esc_attr($checkout_border_color); ?>" />
                        <p class="description">Colore del bordo</p>
                    </td>
                </tr>

                <!-- Padding Top -->
                <tr>
                    <th scope="row">
                        <label for="bw_cart_popup_checkout_padding_top">Padding Top (px)</label>
                    </th>
                    <td>
                        <input type="number" id="bw_cart_popup_checkout_padding_top" name="bw_cart_popup_checkout_padding_top" value="<?php echo esc_attr($checkout_padding_top); ?>" min="0" max="50" class="small-text" />
                        <p class="description">Padding superiore in pixel</p>
                    </td>
                </tr>

                <!-- Padding Right -->
                <tr>
                    <th scope="row">
                        <label for="bw_cart_popup_checkout_padding_right">Padding Right (px)</label>
                    </th>
                    <td>
                        <input type="number" id="bw_cart_popup_checkout_padding_right" name="bw_cart_popup_checkout_padding_right" value="<?php echo esc_attr($checkout_padding_right); ?>" min="0" max="50" class="small-text" />
                        <p class="description">Padding destro in pixel</p>
                    </td>
                </tr>

                <!-- Padding Bottom -->
                <tr>
                    <th scope="row">
                        <label for="bw_cart_popup_checkout_padding_bottom">Padding Bottom (px)</label>
                    </th>
                    <td>
                        <input type="number" id="bw_cart_popup_checkout_padding_bottom" name="bw_cart_popup_checkout_padding_bottom" value="<?php echo esc_attr($checkout_padding_bottom); ?>" min="0" max="50" class="small-text" />
                        <p class="description">Padding inferiore in pixel</p>
                    </td>
                </tr>

                <!-- Padding Left -->
                <tr>
                    <th scope="row">
                        <label for="bw_cart_popup_checkout_padding_left">Padding Left (px)</label>
                    </th>
                    <td>
                        <input type="number" id="bw_cart_popup_checkout_padding_left" name="bw_cart_popup_checkout_padding_left" value="<?php echo esc_attr($checkout_padding_left); ?>" min="0" max="50" class="small-text" />
                        <p class="description">Padding sinistro in pixel</p>
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
                <tr>
                    <th scope="row">
                        <label for="bw_cart_popup_continue_border_width">Spessore Bordo (px)</label>
                    </th>
                    <td>
                        <input type="number" id="bw_cart_popup_continue_border_width" name="bw_cart_popup_continue_border_width" value="<?php echo esc_attr($continue_border_width); ?>" min="0" max="10" class="small-text" />
                        <p class="description">Spessore del bordo in pixel</p>
                    </td>
                </tr>

                <!-- Border Style -->
                <tr>
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
                <tr>
                    <th scope="row">
                        <label for="bw_cart_popup_continue_border_color">Colore Bordo</label>
                    </th>
                    <td>
                        <input type="color" id="bw_cart_popup_continue_border_color" name="bw_cart_popup_continue_border_color" value="<?php echo esc_attr($continue_border_color); ?>" />
                        <p class="description">Colore del bordo</p>
                    </td>
                </tr>

                <!-- Padding Top -->
                <tr>
                    <th scope="row">
                        <label for="bw_cart_popup_continue_padding_top">Padding Top (px)</label>
                    </th>
                    <td>
                        <input type="number" id="bw_cart_popup_continue_padding_top" name="bw_cart_popup_continue_padding_top" value="<?php echo esc_attr($continue_padding_top); ?>" min="0" max="50" class="small-text" />
                        <p class="description">Padding superiore in pixel</p>
                    </td>
                </tr>

                <!-- Padding Right -->
                <tr>
                    <th scope="row">
                        <label for="bw_cart_popup_continue_padding_right">Padding Right (px)</label>
                    </th>
                    <td>
                        <input type="number" id="bw_cart_popup_continue_padding_right" name="bw_cart_popup_continue_padding_right" value="<?php echo esc_attr($continue_padding_right); ?>" min="0" max="50" class="small-text" />
                        <p class="description">Padding destro in pixel</p>
                    </td>
                </tr>

                <!-- Padding Bottom -->
                <tr>
                    <th scope="row">
                        <label for="bw_cart_popup_continue_padding_bottom">Padding Bottom (px)</label>
                    </th>
                    <td>
                        <input type="number" id="bw_cart_popup_continue_padding_bottom" name="bw_cart_popup_continue_padding_bottom" value="<?php echo esc_attr($continue_padding_bottom); ?>" min="0" max="50" class="small-text" />
                        <p class="description">Padding inferiore in pixel</p>
                    </td>
                </tr>

                <!-- Padding Left -->
                <tr>
                    <th scope="row">
                        <label for="bw_cart_popup_continue_padding_left">Padding Left (px)</label>
                    </th>
                    <td>
                        <input type="number" id="bw_cart_popup_continue_padding_left" name="bw_cart_popup_continue_padding_left" value="<?php echo esc_attr($continue_padding_left); ?>" min="0" max="50" class="small-text" />
                        <p class="description">Padding sinistro in pixel</p>
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
                        <label for="bw_cart_popup_additional_svg">Cart Pop-Up Additional SVG</label>
                    </th>
                    <td>
                        <textarea id="bw_cart_popup_additional_svg" name="bw_cart_popup_additional_svg" rows="8" class="large-text code"><?php echo esc_textarea($additional_svg); ?></textarea>
                        <p class="description">Incolla qui il codice SVG completo da visualizzare nel Cart Pop-Up. Esempio: &lt;svg xmlns="http://www.w3.org/2000/svg"...&gt;...&lt;/svg&gt;</p>
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
    </style>
    <?php
}
