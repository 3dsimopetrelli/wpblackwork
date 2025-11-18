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

                <!-- Pulsante Checkout - Testo -->
                <tr>
                    <th scope="row">
                        <label for="bw_cart_popup_checkout_text">Testo Pulsante Checkout</label>
                    </th>
                    <td>
                        <input type="text" id="bw_cart_popup_checkout_text" name="bw_cart_popup_checkout_text" value="<?php echo esc_attr($checkout_text); ?>" class="regular-text" />
                        <p class="description">Testo personalizzato per il pulsante "Proceed to checkout"</p>
                    </td>
                </tr>

                <!-- Pulsante Checkout - Colore -->
                <tr>
                    <th scope="row">
                        <label for="bw_cart_popup_checkout_color">Colore Pulsante Checkout</label>
                    </th>
                    <td>
                        <input type="color" id="bw_cart_popup_checkout_color" name="bw_cart_popup_checkout_color" value="<?php echo esc_attr($checkout_color); ?>" />
                        <p class="description">Colore di sfondo del pulsante checkout (default: verde #28a745)</p>
                    </td>
                </tr>

                <!-- Pulsante Continue Shopping - Testo -->
                <tr>
                    <th scope="row">
                        <label for="bw_cart_popup_continue_text">Testo Pulsante Continue Shopping</label>
                    </th>
                    <td>
                        <input type="text" id="bw_cart_popup_continue_text" name="bw_cart_popup_continue_text" value="<?php echo esc_attr($continue_text); ?>" class="regular-text" />
                        <p class="description">Testo personalizzato per il pulsante "Continue shopping"</p>
                    </td>
                </tr>

                <!-- Pulsante Continue Shopping - Colore -->
                <tr>
                    <th scope="row">
                        <label for="bw_cart_popup_continue_color">Colore Pulsante Continue Shopping</label>
                    </th>
                    <td>
                        <input type="color" id="bw_cart_popup_continue_color" name="bw_cart_popup_continue_color" value="<?php echo esc_attr($continue_color); ?>" />
                        <p class="description">Colore di sfondo del pulsante continue shopping</p>
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
