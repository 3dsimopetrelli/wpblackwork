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
 * Sanitizza l'SVG permettendo tutti i tag e attributi SVG necessari
 */
function bw_cart_popup_sanitize_svg($svg)
{
    $svg = (string) $svg;
    if ('' === trim($svg)) {
        return '';
    }

    // Canonical hardening pipeline from plugin core.
    if (function_exists('bw_mew_svg_sanitize_content') && function_exists('bw_mew_svg_is_valid_document')) {
        $sanitized = (string) bw_mew_svg_sanitize_content($svg);
        if ('' === trim($sanitized)) {
            return '';
        }

        if (!bw_mew_svg_is_valid_document($sanitized)) {
            return '';
        }

        return $sanitized;
    }

    // Safe fallback if core helpers are unavailable.
    $allowed_tags = [
        'svg' => [
            'xmlns' => true,
            'viewbox' => true,
            'width' => true,
            'height' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'stroke-linecap' => true,
            'stroke-linejoin' => true,
            'class' => true,
            'id' => true,
            'preserveaspectratio' => true,
            'aria-hidden' => true,
            'role' => true,
        ],
        'path' => [
            'd' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'stroke-linecap' => true,
            'stroke-linejoin' => true,
            'class' => true,
            'id' => true,
            'opacity' => true,
            'transform' => true,
        ],
        'circle' => [
            'cx' => true,
            'cy' => true,
            'r' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'class' => true,
            'id' => true,
            'opacity' => true,
            'transform' => true,
        ],
        'rect' => [
            'x' => true,
            'y' => true,
            'width' => true,
            'height' => true,
            'rx' => true,
            'ry' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'class' => true,
            'id' => true,
            'opacity' => true,
            'transform' => true,
        ],
        'polygon' => [
            'points' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'class' => true,
            'id' => true,
            'opacity' => true,
            'transform' => true,
        ],
        'polyline' => [
            'points' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'class' => true,
            'id' => true,
            'opacity' => true,
            'transform' => true,
        ],
        'ellipse' => [
            'cx' => true,
            'cy' => true,
            'rx' => true,
            'ry' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'class' => true,
            'id' => true,
            'opacity' => true,
            'transform' => true,
        ],
        'line' => [
            'x1' => true,
            'y1' => true,
            'x2' => true,
            'y2' => true,
            'stroke' => true,
            'stroke-width' => true,
            'class' => true,
            'id' => true,
            'opacity' => true,
            'transform' => true,
        ],
        'g' => [
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'class' => true,
            'id' => true,
            'opacity' => true,
            'transform' => true,
        ],
        'defs' => true,
        'clippath' => [
            'id' => true,
        ],
        'lineargradient' => [
            'id' => true,
            'x1' => true,
            'y1' => true,
            'x2' => true,
            'y2' => true,
        ],
        'radialgradient' => [
            'id' => true,
            'cx' => true,
            'cy' => true,
            'r' => true,
        ],
        'stop' => [
            'offset' => true,
            'stop-color' => true,
            'stop-opacity' => true,
        ],
    ];

    return wp_kses($svg, $allowed_tags);
}

/**
 * Aggiungi la pagina di menu nel pannello admin
 * NOTA: Questa registrazione è stata disabilitata perché il menu è ora unificato
 * nella pagina "Blackwork Site" sotto Settings
 */
/*
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
*/

/**
 * Salva le impostazioni del Cart Pop-Up
 */
function bw_cart_popup_save_settings()
{
    $posted_nonce = isset($_POST['bw_cart_popup_nonce']) ? (string) wp_unslash($_POST['bw_cart_popup_nonce']) : '';
    if ('' === $posted_nonce || !wp_verify_nonce($posted_nonce, 'bw_cart_popup_save')) {
        return;
    }

    if (!current_user_can('manage_options')) {
        return;
    }

    $post_value = static function ($key, $default = '') {
        if (!isset($_POST[$key])) {
            return $default;
        }

        return wp_unslash($_POST[$key]);
    };

    $clamp_int = static function ($value, $min, $max, $default) {
        $normalized = is_numeric($value) ? (int) $value : (int) $default;
        if ($normalized < $min) {
            return $min;
        }
        if ($normalized > $max) {
            return $max;
        }

        return $normalized;
    };

    $clamp_float = static function ($value, $min, $max, $default) {
        $normalized = is_numeric($value) ? (float) $value : (float) $default;
        if ($normalized < $min) {
            return $min;
        }
        if ($normalized > $max) {
            return $max;
        }

        return $normalized;
    };

    // Toggle ON/OFF
    update_option('bw_cart_popup_active', isset($_POST['bw_cart_popup_active']) ? 1 : 0);

    // Floating trigger ON/OFF
    update_option('bw_cart_popup_show_floating_trigger', isset($_POST['bw_cart_popup_show_floating_trigger']) ? 1 : 0);

    // Disable popup runtime on checkout pages ON/OFF
    update_option('bw_cart_popup_disable_on_checkout', isset($_POST['bw_cart_popup_disable_on_checkout']) ? 1 : 0);

    // Slide-in animation ON/OFF
    update_option('bw_cart_popup_slide_animation', isset($_POST['bw_cart_popup_slide_animation']) ? 1 : 0);

    // Testo pulsante checkout
    $checkout_text = sanitize_text_field((string) $post_value('bw_cart_popup_checkout_text', 'Proceed to checkout'));
    update_option('bw_cart_popup_checkout_text', $checkout_text);

    // RIMOSSO: Link personalizzato checkout - ora si usa sempre wc_get_checkout_url()
    // per garantire che il pulsante porti sempre alla pagina di checkout WooCommerce

    // Testo pulsante continue shopping
    $continue_text = sanitize_text_field((string) $post_value('bw_cart_popup_continue_text', 'Continue shopping'));
    update_option('bw_cart_popup_continue_text', $continue_text);

    // Link personalizzato continue shopping
    $continue_url = esc_url_raw((string) $post_value('bw_cart_popup_continue_url', ''));
    update_option('bw_cart_popup_continue_url', $continue_url);

    // SVG personalizzato per Cart Pop-Up (usa sanitize_textarea per preservare SVG)
    $additional_svg = bw_cart_popup_sanitize_svg((string) $post_value('bw_cart_popup_additional_svg', ''));
    update_option('bw_cart_popup_additional_svg', $additional_svg);

    // Empty Cart SVG personalizzato (usa sanitize_textarea per preservare SVG)
    $empty_cart_svg = bw_cart_popup_sanitize_svg((string) $post_value('bw_cart_popup_empty_cart_svg', ''));
    update_option('bw_cart_popup_empty_cart_svg', $empty_cart_svg);

    // Opzione per colorare SVG di nero
    update_option('bw_cart_popup_svg_black', isset($_POST['bw_cart_popup_svg_black']) ? 1 : 0);

    // Quantity badge toggle
    update_option('bw_cart_popup_show_quantity_badge', isset($_POST['bw_cart_popup_show_quantity_badge']) ? 1 : 0);

    // === PROMO CODE SECTION ===
    // Promo code section label
    $promo_section_label = sanitize_text_field((string) $post_value('bw_cart_popup_promo_section_label', 'Promo code section'));
    update_option('bw_cart_popup_promo_section_label', $promo_section_label);

    // === EMPTY CART SETTINGS ===
    // Return to shop link
    $return_shop_url = esc_url_raw((string) $post_value('bw_cart_popup_return_shop_url', ''));
    update_option('bw_cart_popup_return_shop_url', $return_shop_url);

    return true;
}

/**
 * Renderizza la pagina delle impostazioni
 */
function bw_cart_popup_settings_page()
{
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
    $show_floating_trigger = get_option('bw_cart_popup_show_floating_trigger', 0);
    $disable_on_checkout = get_option('bw_cart_popup_disable_on_checkout', 1);
    $checkout_text = get_option('bw_cart_popup_checkout_text', 'Proceed to checkout');
    $continue_text = get_option('bw_cart_popup_continue_text', 'Continue shopping');
    $continue_url = get_option('bw_cart_popup_continue_url', '');
    $additional_svg = get_option('bw_cart_popup_additional_svg', '');
    $empty_cart_svg = get_option('bw_cart_popup_empty_cart_svg', '');
    $svg_black = get_option('bw_cart_popup_svg_black', 0);
    $return_shop_url = get_option('bw_cart_popup_return_shop_url', '');
    $show_quantity_badge = get_option('bw_cart_popup_show_quantity_badge', 1);
    $promo_section_label = get_option('bw_cart_popup_promo_section_label', 'Promo code section');

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
                            <span class="description">Quando attivo, i pulsanti "Add to Cart" apriranno il pannello slide-in
                                invece di andare alla pagina carrello.</span>
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
                            <input type="checkbox" id="bw_cart_popup_show_floating_trigger"
                                name="bw_cart_popup_show_floating_trigger" value="1" <?php checked(1, $show_floating_trigger); ?> />
                            <span class="description">Abilita l'icona fissa in basso a destra con il numero di prodotti;
                                cliccandola si apre il cart pop-up.</span>
                        </label>
                    </td>
                </tr>

                <!-- Checkout runtime suppression ON/OFF -->
                <tr>
                    <th scope="row">
                        <label for="bw_cart_popup_disable_on_checkout">Disabilita Cart Pop-Up in checkout</label>
                    </th>
                    <td>
                        <label class="switch">
                            <input type="checkbox" id="bw_cart_popup_disable_on_checkout"
                                name="bw_cart_popup_disable_on_checkout" value="1" <?php checked(1, $disable_on_checkout); ?> />
                            <span class="description">Quando attivo, in checkout il runtime Cart Pop-Up viene soppresso (icona floating, markup pannello, CSS e JS).</span>
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
                            <input type="checkbox" id="bw_cart_popup_slide_animation" name="bw_cart_popup_slide_animation"
                                value="1" <?php checked(1, get_option('bw_cart_popup_slide_animation', 1)); ?> />
                            <span class="description">Quando attivo, il cart pop-up si apre automaticamente con slide-in da
                                destra ogni volta che un prodotto viene aggiunto al carrello.</span>
                        </label>
                    </td>
                </tr>

                <!-- Badge quantità -->
                <tr>
                    <th scope="row">
                        <label for="bw_cart_popup_show_quantity_badge">Mostra badge quantità (thumbnail)</label>
                    </th>
                    <td>
                        <label class="switch">
                            <input type="checkbox" id="bw_cart_popup_show_quantity_badge"
                                name="bw_cart_popup_show_quantity_badge" value="1" <?php checked(1, $show_quantity_badge); ?> />
                            <span class="description">Attiva/disattiva il pallino con il numero di pezzi sopra l’immagine
                                prodotto nel cart pop-up.</span>
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
                        <input type="text" id="bw_cart_popup_checkout_text" name="bw_cart_popup_checkout_text"
                            value="<?php echo esc_attr($checkout_text); ?>" class="regular-text" />
                        <p class="description">Testo del pulsante (default: "Proceed to checkout")</p>
                    </td>
                </tr>

                <!-- === CONTINUE SHOPPING BUTTON === -->
                <tr>
                    <th colspan="2">
                        <h3 style="margin: 30px 0 10px 0;">Continue Shopping Button</h3>
                    </th>
                </tr>

                <!-- Testo -->
                <tr>
                    <th scope="row">
                        <label for="bw_cart_popup_continue_text">Testo Pulsante</label>
                    </th>
                    <td>
                        <input type="text" id="bw_cart_popup_continue_text" name="bw_cart_popup_continue_text"
                            value="<?php echo esc_attr($continue_text); ?>" class="regular-text" />
                        <p class="description">Testo del pulsante (default: "Continue shopping")</p>
                    </td>
                </tr>

                <!-- Link Personalizzato -->
                <tr>
                    <th scope="row">
                        <label for="bw_cart_popup_continue_url">Link Personalizzato</label>
                    </th>
                    <td>
                        <input type="url" id="bw_cart_popup_continue_url" name="bw_cart_popup_continue_url"
                            value="<?php echo esc_attr($continue_url); ?>" class="regular-text" placeholder="/shop/" />
                        <p class="description">URL personalizzato per il pulsante Continue Shopping (lascia vuoto per usare
                            /shop/ di default)</p>
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
                        <input type="text" id="bw_cart_popup_promo_section_label" name="bw_cart_popup_promo_section_label"
                            value="<?php echo esc_attr($promo_section_label); ?>" class="regular-text" />
                        <p class="description">Label per la sezione promo code (default: "Promo code section")</p>
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
                        <input type="url" id="bw_cart_popup_return_shop_url" name="bw_cart_popup_return_shop_url"
                            value="<?php echo esc_attr($return_shop_url); ?>" class="regular-text" placeholder="/shop/" />
                        <p class="description">URL personalizzato per il pulsante "Return to Shop" (lascia vuoto per usare
                            /shop/ di default)</p>
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
                        <textarea id="bw_cart_popup_additional_svg" name="bw_cart_popup_additional_svg" rows="8"
                            class="large-text code"><?php echo esc_textarea($additional_svg); ?></textarea>
                        <p class="description">Incolla qui il codice SVG completo da visualizzare nel Cart Pop-Up. Esempio:
                            &lt;svg xmlns="http://www.w3.org/2000/svg"...&gt;...&lt;/svg&gt;</p>
                    </td>
                </tr>

                <!-- Empty Cart SVG (Custom) -->
                <tr>
                    <th scope="row">
                        <label for="bw_cart_popup_empty_cart_svg">Empty Cart SVG (Custom)</label>
                    </th>
                    <td>
                        <textarea id="bw_cart_popup_empty_cart_svg" name="bw_cart_popup_empty_cart_svg" rows="8"
                            class="large-text code"><?php echo esc_textarea($empty_cart_svg); ?></textarea>
                        <p class="description">Incolla qui il codice SVG personalizzato per l'icona del carrello vuoto. Se
                            vuoto, verrà usata l'icona di default.</p>
                    </td>
                </tr>

                <!-- Opzione colore nero SVG -->
                <tr>
                    <th scope="row">
                        <label for="bw_cart_popup_svg_black">Colora SVG di Nero</label>
                    </th>
                    <td>
                        <label class="switch">
                            <input type="checkbox" id="bw_cart_popup_svg_black" name="bw_cart_popup_svg_black" value="1"
                                <?php checked(1, $svg_black); ?> />
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
                <li><strong>Funzionalità OFF:</strong> I pulsanti "Add to Cart" comportano in modo standard e portano alla
                    pagina del carrello.</li>
                <li><strong>Funzionalità ON:</strong> Cliccando su "Add to Cart" si apre un pannello slide-in da destra con
                    overlay scuro.</li>
                <li><strong>Design:</strong> Il pannello replica il design del mini-cart con header, lista prodotti, promo
                    code, totali e pulsanti azione.</li>
                <li><strong>Promo Code:</strong> Al click su "Click here" appare un box per inserire il coupon con calcolo
                    real-time dello sconto.</li>
                <li><strong>CSS Personalizzato:</strong> Puoi modificare ulteriormente lo stile editando il file
                    <code>assets/css/bw-cart-popup.css</code>
                </li>
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
            box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
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
    // (enqueued in bw_site_settings_admin_assets hook in class-blackwork-site-settings.php)
}
