<?php
/**
 * Blackwork Site Settings — Cart Popup, Shipping, Redirect, Coming Soon, Loading tab renderers.
 *
 * Extracted verbatim from class-blackwork-site-settings.php (Phase 3a, BW-TASK-20260629). Function bodies are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renderizza il tab Cart Pop-up
 */
function bw_site_render_cart_popup_tab()
{
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
                        <span class="description">Attiva l'icona fissa in basso a destra con badge quantità; cliccandola si
                            apre il cart pop-up.</span>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_disable_on_checkout">Disabilita Cart Pop-Up in checkout</label>
                </th>
                <td>
                    <label class="switch">
                        <input type="checkbox" id="bw_cart_popup_disable_on_checkout"
                            name="bw_cart_popup_disable_on_checkout" value="1" <?php checked(1, $disable_on_checkout); ?> />
                        <span class="description">Quando attivo, in checkout non vengono caricati icona flottante,
                            pannello, CSS e JS del Cart Pop-Up.</span>
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
                        <span class="description">Attiva o disattiva il pallino con il numero di pezzi sopra l’immagine
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

            <!-- Testo Proceed to Checkout -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_checkout_text">Testo Pulsante Checkout</label>
                </th>
                <td>
                    <input type="text" id="bw_cart_popup_checkout_text" name="bw_cart_popup_checkout_text"
                        value="<?php echo esc_attr($checkout_text); ?>" class="regular-text" />
                    <p class="description">Testo del pulsante (default: "Proceed to checkout")</p>
                </td>
            </tr>

            <!-- Testo Continue Shopping -->
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
            <li><strong>Funzionalità OFF:</strong> I pulsanti "Add to Cart" comportano in modo standard e portano alla
                pagina del carrello.</li>
            <li><strong>Funzionalità ON:</strong> Cliccando su "Add to Cart" si apre un pannello slide-in da destra con
                overlay scuro.</li>
            <li><strong>Design:</strong> Il pannello replica il design del mini-cart con header, lista prodotti, promo code,
                totali e pulsanti azione.</li>
            <li><strong>Promo Code:</strong> Al click su "Click here" appare un box per inserire il coupon con calcolo
                real-time dello sconto.</li>
            <li><strong>CSS Personalizzato:</strong> Puoi modificare ulteriormente lo stile editando il file
                <code>assets/css/bw-cart-popup.css</code>
            </li>
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
}

/**
 * Render the Shipping settings tab.
 */
function bw_site_render_shipping_tab()
{
    $saved = false;
    if (isset($_POST['bw_cart_shipping_submit'])) {
        $saved = bw_cart_popup_save_settings();
    }

    $shipping_notice_enabled = bw_cart_popup_get_shipping_notice_enabled();
    $shipping_notice_url = bw_cart_popup_get_shipping_notice_url();
    $checkout_shipping_info_popup_text = bw_get_checkout_shipping_info_popup_text();

    ?>
    <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Impostazioni salvate con successo!</strong></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('bw_cart_popup_save', 'bw_cart_popup_nonce'); ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="bw_cart_shipping_notice_enabled"><?php esc_html_e('Show cart shipping notice', 'bw'); ?></label>
                </th>
                <td>
                    <label class="switch">
                        <input
                            type="checkbox"
                            id="bw_cart_shipping_notice_enabled"
                            name="bw_cart_shipping_notice_enabled"
                            value="1"
                            <?php checked(1, $shipping_notice_enabled); ?>
                        />
                        <span class="description">
                            <?php esc_html_e('Show the “Tax included. Final shipping confirmed at checkout.” notice above the cart pop-up checkout button.', 'bw'); ?>
                        </span>
                    </label>
                </td>
            </tr>

            <tr data-bw-shipping-link-row>
                <th scope="row">
                    <label for="bw_cart_shipping_notice_url"><?php esc_html_e('Shipping page link', 'bw'); ?></label>
                </th>
                <td>
                    <input
                        type="text"
                        class="regular-text"
                        id="bw_cart_shipping_notice_url"
                        name="bw_cart_shipping_notice_url"
                        value="<?php echo esc_attr($shipping_notice_url); ?>"
                        placeholder="/shipping/"
                    />
                    <p class="description">
                        <?php esc_html_e('The page linked by the word “Shipping” in the cart pop-up notice. Relative internal URLs like /shipping/ are allowed.', 'bw'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="bw_checkout_shipping_info_popup_text"><?php esc_html_e('Checkout shipping info popup text', 'bw'); ?></label>
                </th>
                <td>
                    <?php
                    wp_editor(
                        $checkout_shipping_info_popup_text,
                        'bw_checkout_shipping_info_popup_text',
                        [
                            'textarea_name' => 'bw_checkout_shipping_info_popup_text',
                            'media_buttons' => true,
                            'textarea_rows' => 8,
                            'teeny' => false,
                            'quicktags' => true,
                        ]
                    );
                    ?>
                    <p class="description">
                        <?php esc_html_e('Text shown inside the checkout Shipping info popup opened from the question mark next to the Shipping row. Leave empty to hide the question mark.', 'bw'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" name="bw_cart_shipping_submit" class="button button-primary">
                <?php esc_html_e('Save Shipping Settings', 'bw'); ?>
            </button>
        </p>
    </form>
    <style>
        .bw-admin-page-site-settings .bw-is-hidden {
            display: none;
        }
    </style>
    <script>
        (function () {
            var toggle = document.getElementById('bw_cart_shipping_notice_enabled');
            var linkRow = document.querySelector('[data-bw-shipping-link-row]');

            if (!toggle || !linkRow) {
                return;
            }

            function syncShippingLinkVisibility() {
                linkRow.classList.toggle('bw-is-hidden', !toggle.checked);
            }

            syncShippingLinkVisibility();
            toggle.addEventListener('change', syncShippingLinkVisibility);
        }());
    </script>
    <?php
}

/**
 * Renderizza il tab Redirect.
 */
function bw_site_render_redirect_tab()
{
    $saved = false;

    if (isset($_POST['bw_redirects_submit'])) {
        if (!current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('bw_redirects_save', 'bw_redirects_nonce');

        $redirects_input = isset($_POST['bw_redirects']) && is_array($_POST['bw_redirects']) ? wp_unslash($_POST['bw_redirects']) : [];
        $sanitized = [];

        foreach ($redirects_input as $redirect) {
            $target_raw = isset($redirect['target_url']) ? trim((string) $redirect['target_url']) : '';
            $source_raw = isset($redirect['source_url']) ? trim((string) $redirect['source_url']) : '';
            $target = esc_url_raw($target_raw);
            $normalized_source = bw_normalize_redirect_path($source_raw);
            $source_to_store = '' !== $source_raw ? sanitize_text_field($source_raw) : '';

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
                <?php foreach ($redirects as $index => $redirect):
                    $target = isset($redirect['target']) ? $redirect['target'] : '';
                    $source = isset($redirect['source']) ? $redirect['source'] : '';
                    ?>
                    <tr class="bw-redirect-row">
                        <td>
                            <label>
                                Inserisci il link d'arrivo
                                <input type="text" name="bw_redirects[<?php echo esc_attr($index); ?>][target_url]"
                                    value="<?php echo esc_attr($target); ?>" class="regular-text"
                                    placeholder="https://esempio.com/pagina" />
                            </label>
                            <p class="description">URL assoluto verso cui reindirizzare l'utente.</p>
                        </td>
                        <td>
                            <label>
                                Inserisci il link di redirect
                                <input type="text" name="bw_redirects[<?php echo esc_attr($index); ?>][source_url]"
                                    value="<?php echo esc_attr($source); ?>" class="regular-text"
                                    placeholder="/promo/black-friday" />
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
function bw_site_render_coming_soon_tab()
{
    $settings_defaults = [
        'subscribe_enabled' => 0,
        'list_id_override' => 0,
        'channel_optin_mode' => 'inherit',
        'success_message' => __('Thanks for subscribing! Please check your inbox.', 'bw'),
        'error_message' => __('Unable to subscribe right now. Please try again later.', 'bw'),
    ];

    $saved_settings = get_option('bw_coming_soon_brevo_settings', []);
    if (!is_array($saved_settings)) {
        $saved_settings = [];
    }
    $brevo_settings = array_merge($settings_defaults, $saved_settings);

    // Salva le impostazioni se il form è stato inviato
    $saved = false;
    if (isset($_POST['bw_coming_soon_submit'])) {
        if (!current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('bw_coming_soon_save', 'bw_coming_soon_nonce');

        $active_value = isset($_POST['bw_coming_soon_toggle']) ? 1 : 0;
        update_option('bw_coming_soon_active', $active_value);

        $channel_optin_mode = isset($_POST['bw_coming_soon_channel_optin_mode'])
            ? sanitize_key(wp_unslash($_POST['bw_coming_soon_channel_optin_mode']))
            : 'inherit';
        if (!in_array($channel_optin_mode, ['inherit', 'single_opt_in', 'double_opt_in'], true)) {
            $channel_optin_mode = 'inherit';
        }

        $updated_brevo_settings = [
            'subscribe_enabled' => !empty($_POST['bw_coming_soon_subscribe_enabled']) ? 1 : 0,
            'list_id_override' => isset($_POST['bw_coming_soon_list_id_override']) ? absint(wp_unslash($_POST['bw_coming_soon_list_id_override'])) : 0,
            'channel_optin_mode' => $channel_optin_mode,
            'success_message' => isset($_POST['bw_coming_soon_success_message'])
                ? sanitize_textarea_field(wp_unslash($_POST['bw_coming_soon_success_message']))
                : $settings_defaults['success_message'],
            'error_message' => isset($_POST['bw_coming_soon_error_message'])
                ? sanitize_textarea_field(wp_unslash($_POST['bw_coming_soon_error_message']))
                : $settings_defaults['error_message'],
        ];
        update_option('bw_coming_soon_brevo_settings', $updated_brevo_settings);
        $brevo_settings = array_merge($settings_defaults, $updated_brevo_settings);
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
                    <span class="description">Quando attivo, il sito mostrerà la pagina Coming Soon ai visitatori non
                        loggati.</span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_coming_soon_subscribe_enabled"><?php esc_html_e('Enable Coming Soon subscribe form', 'bw'); ?></label>
                </th>
                <td>
                    <input
                        type="checkbox"
                        id="bw_coming_soon_subscribe_enabled"
                        name="bw_coming_soon_subscribe_enabled"
                        value="1"
                        <?php checked(1, (int) $brevo_settings['subscribe_enabled']); ?>
                    />
                    <span class="description"><?php esc_html_e('Use Mail Marketing Brevo settings for Coming Soon subscriptions.', 'bw'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_coming_soon_list_id_override"><?php esc_html_e('Brevo list override', 'bw'); ?></label>
                </th>
                <td>
                    <input
                        type="number"
                        id="bw_coming_soon_list_id_override"
                        name="bw_coming_soon_list_id_override"
                        value="<?php echo esc_attr((int) $brevo_settings['list_id_override']); ?>"
                        min="0"
                        step="1"
                        class="small-text"
                    />
                    <span class="description"><?php esc_html_e('Optional. Leave empty/0 to use Mail Marketing main list.', 'bw'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_coming_soon_channel_optin_mode"><?php esc_html_e('Channel opt-in mode', 'bw'); ?></label>
                </th>
                <td>
                    <select id="bw_coming_soon_channel_optin_mode" name="bw_coming_soon_channel_optin_mode">
                        <option value="inherit" <?php selected($brevo_settings['channel_optin_mode'], 'inherit'); ?>><?php esc_html_e('Inherit General setting', 'bw'); ?></option>
                        <option value="single_opt_in" <?php selected($brevo_settings['channel_optin_mode'], 'single_opt_in'); ?>><?php esc_html_e('Force single opt-in', 'bw'); ?></option>
                        <option value="double_opt_in" <?php selected($brevo_settings['channel_optin_mode'], 'double_opt_in'); ?>><?php esc_html_e('Force double opt-in', 'bw'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_coming_soon_success_message"><?php esc_html_e('Success message', 'bw'); ?></label>
                </th>
                <td>
                    <textarea
                        id="bw_coming_soon_success_message"
                        name="bw_coming_soon_success_message"
                        rows="3"
                        class="large-text"
                    ><?php echo esc_textarea((string) $brevo_settings['success_message']); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_coming_soon_error_message"><?php esc_html_e('Error message', 'bw'); ?></label>
                </th>
                <td>
                    <textarea
                        id="bw_coming_soon_error_message"
                        name="bw_coming_soon_error_message"
                        rows="3"
                        class="large-text"
                    ><?php echo esc_textarea((string) $brevo_settings['error_message']); ?></textarea>
                </td>
            </tr>
        </table>

        <?php submit_button('Salva impostazioni', 'primary', 'bw_coming_soon_submit'); ?>
    </form>
    <?php
}

/**
 * Renderizza il tab Loading
 */
function bw_site_render_loading_tab()
{
    $saved = false;

    if (isset($_POST['bw_loading_settings_submit'])) {
        if (!current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('bw_loading_settings_save', 'bw_loading_settings_nonce');

        $global_spinner_hidden = isset($_POST['bw_loading_global_spinner_hidden']) ? 1 : 0;
        update_option('bw_loading_global_spinner_hidden', $global_spinner_hidden);

        $saved = true;
    }

    $global_spinner_hidden = get_option('bw_loading_global_spinner_hidden', 1);
    ?>

    <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Impostazioni salvate con successo!</strong></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('bw_loading_settings_save', 'bw_loading_settings_nonce'); ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">Nascondi Spinner WC</th>
                <td>
                    <label for="bw_loading_global_spinner_hidden">
                        <input name="bw_loading_global_spinner_hidden" type="checkbox" id="bw_loading_global_spinner_hidden"
                            value="1" <?php checked(1, $global_spinner_hidden); ?> />
                        Nascondi caricamento standard e maschera grigia di WooCommerce (Checkout e altro)
                    </label>
                </td>
            </tr>
        </table>

        <?php submit_button('Salva Impostazioni', 'primary', 'bw_loading_settings_submit'); ?>
    </form>
    <?php
}
