# Cart Pop-up: Struttura Completa, Flussi e Gestione Coupon

Documento tecnico aggiornato del sottosistema **Cart Pop-up** del plugin `wpblackwork`.

## 1) Obiettivo del modulo

Il Cart Pop-up sostituisce (in specifici contesti) il comportamento standard di WooCommerce dopo l'`Add to cart`, aprendo un pannello laterale con:
- elenco prodotti nel carrello,
- modifica quantità e rimozione item,
- applicazione/rimozione coupon,
- ricalcolo totale in tempo reale,
- CTA checkout/continue shopping.

## 2) Mappa file coinvolti

### Nucleo Cart Pop-up
- `cart-popup/cart-popup.php`
- `cart-popup/frontend/cart-popup-frontend.php`
- `cart-popup/assets/js/bw-cart-popup.js`
- `cart-popup/assets/css/bw-cart-popup.css`
- `cart-popup/admin/settings-page.php`

### Integrazioni plugin / WooCommerce
- `bw-main-elementor-widgets.php` (bootstrap modulo)
- `admin/class-blackwork-site-settings.php` (pannello unificato “Blackwork Site”, tab Cart Pop-up)
- `includes/modules/header/templates/header.php` (trigger cart icona header)
- `includes/modules/header/assets/js/bw-navshop.js` (apertura popup da header)
- `includes/widgets/class-bw-slick-slider-widget.php` (data attribute `data-open-cart-popup="1"`)
- `includes/widgets/class-bw-filtered-post-wall-widget.php` (data attribute `data-open-cart-popup="1"`)
- `includes/widgets/class-bw-related-products-widget.php` (opzione Elementor per popup)
- `woocommerce/templates/single-product/related.php` (button con `data-open-cart-popup="1"`)
- `templates/woocommerce/content-product.php` (flag `open_cart_popup` per renderer card)

### Flusso coupon checkout (correlato, ma separato dal popup)
- `woocommerce/woocommerce-init.php` (`bw_mew_ajax_apply_coupon`, `bw_mew_ajax_remove_coupon`)
- `assets/js/bw-checkout.js` (AJAX apply/remove coupon in checkout)
- `woocommerce/templates/checkout/form-coupon.php`
- `woocommerce/templates/checkout/review-order.php`

### Empty cart page allineata al linguaggio popup
- `woocommerce/templates/cart/cart-empty.php`

## 3) Bootstrap e inizializzazione

1. `bw-main-elementor-widgets.php` include `cart-popup/cart-popup.php`.
2. `cart-popup/cart-popup.php`:
- definisce costanti percorso/URL modulo,
- include admin settings e logica frontend,
- registra/enqueue CSS+JS,
- passa config JS via `wp_localize_script` (`bwCartPopupConfig`),
- inietta CSS anti `View cart` WooCommerce.
3. `cart-popup/frontend/cart-popup-frontend.php` renderizza markup pannello in `wp_footer` e registra endpoint AJAX custom.
4. `cart-popup/assets/js/bw-cart-popup.js` inizializza `window.BW_CartPopup` al `document.ready`.

Nota: gli asset del popup vengono caricati sempre nel frontend (non solo quando toggle globale è attivo), per supportare widget che forzano l'apertura popup via data-attribute.

## 4) Struttura UI del pannello

Markup principale (`bw_cart_popup_render_panel`):
- overlay: `#bw-cart-popup-overlay`
- panel: `#bw-cart-popup-panel`
- loading state
- header con badge + close
- notifica “item added”
- stato empty cart (SVG custom/default + Return to Shop)
- stato full cart:
  - lista item (`.bw-cart-popup-items`),
  - promo area (input coupon + apply + messaggi),
  - totali (subtotal, discount, total)
- footer CTA (checkout + continue)
- trigger flottante opzionale

## 5) Trigger apertura popup

### Trigger automatico da Add to cart
- Evento WooCommerce `added_to_cart` intercettato in JS.
- Popup si apre se:
  - `bw_cart_popup_slide_animation = 1` (globale), oppure
  - il bottone ha `data-open-cart-popup="1"` (widget).

### Trigger manuale da header
- Link header con `data-use-popup="yes"`.
- `includes/modules/header/assets/js/bw-navshop.js` chiama `window.BW_CartPopup.openPanel()`.
- fallback a redirect cart URL se oggetto popup non disponibile.

## 6) Endpoint AJAX Cart Pop-up

Definiti in `cart-popup/frontend/cart-popup-frontend.php`:
- `bw_cart_popup_get_contents`
- `bw_cart_popup_add_to_cart`
- `bw_cart_popup_remove_item`
- `bw_cart_popup_update_quantity`
- `bw_cart_popup_apply_coupon`
- `bw_cart_popup_remove_coupon`

Tutti protetti da nonce `bw_cart_popup_nonce`.

## 7) Flusso runtime completo del popup

1. `openPanel()`:
- blocca in editor Elementor,
- applica classi active a overlay/panel,
- blocca scroll body,
- invoca `loadCartContents()`.

2. `loadCartContents()` -> `bw_cart_popup_get_contents`:
- legge carrello WooCommerce,
- serializza item + prezzi raw/formattati,
- ritorna coupon applicati e importi per coupon,
- ritorna subtotal/discount/tax/total.

3. Render frontend:
- `renderCartItems(data)` ricostruisce lista prodotti,
- `updateTotals(data)` aggiorna subtotale/sconti/totale,
- `updateCouponDisplay(data.applied_coupons)` gestisce visibilità remove coupon.

4. Azioni utente:
- quantità `+/-` -> `bw_cart_popup_update_quantity` -> `calculate_totals()` -> refresh popup,
- remove item -> `bw_cart_popup_remove_item` -> `calculate_totals()` -> refresh popup,
- apply coupon -> `bw_cart_popup_apply_coupon` -> `calculate_totals()` -> refresh totals,
- remove coupon -> `bw_cart_popup_remove_coupon` -> `calculate_totals()` -> refresh totals.

5. Sincronizzazione WooCommerce fragments:
- in punti chiave il JS triggera `wc_fragment_refresh` per tenere coerenti mini-cart/frammenti.

## 8) Metodo di calcolo coupon nel Cart Pop-up

### Apply coupon (popup)
Handler: `bw_cart_popup_apply_coupon()`
- input: `coupon_code`
- applicazione: `$cart->add_discount($coupon_code)`
- ricalcolo: `$cart->calculate_totals()`
- valori restituiti:
  - `subtotal = get_subtotal()`
  - `discount = get_discount_total()`
  - `tax = get_total_tax()`
  - `total = get_total('')`
  - `applied_coupons = get_applied_coupons()`
  - `coupons[]` dettagliato con importi da `get_coupon_discount_amounts()`

### Remove coupon (popup)
Handler: `bw_cart_popup_remove_coupon()`
- input opzionale: `coupon_code`
- se presente: `remove_coupon($coupon_code)`
- se assente: `remove_coupons()` (legacy behavior)
- ricalcolo: `$cart->calculate_totals()`
- output speculare all'apply.

### Rendering sconti lato JS
`updateTotals(data)`:
- non usa una singola riga “discount totale”,
- costruisce righe dinamiche per ciascun coupon con:
  - codice coupon,
  - amount coupon-specific,
  - link `[Remove]` per rimozione puntuale.

## 9) Flusso coupon in Checkout (distinto dal popup)

In checkout non si usano gli endpoint popup: si usano endpoint custom in `woocommerce/woocommerce-init.php`:
- `bw_mew_ajax_apply_coupon` (action `bw_apply_coupon`)
- `bw_mew_ajax_remove_coupon` (action `bw_remove_coupon`)

Differenze chiave rispetto al popup:
- apply usa `WC()->cart->apply_coupon()` (non `add_discount`),
- forzatura persistenza sessione WooCommerce (`persistent_cart_update`, `session->save_data`, set esplicito di `applied_coupons`/`coupon_discount_totals`),
- ritorno `cart_hash` per sincronizzazione robusta,
- integrazione eventi `woocommerce_applied_coupon` / `woocommerce_removed_coupon`.

JS checkout (`assets/js/bw-checkout.js`) poi triggera `update_checkout` per rigenerare il riepilogo ordine.

## 10) Pannello di controllo “Blackwork Site”

Menu: `Blackwork Site` (`admin/class-blackwork-site-settings.php`)
Tab disponibili:
- `Cart Pop-up`
- `BW Coming Soon`
- `Login Page`
- `My Account Page`
- `Checkout`
- `Redirect`
- `Import Product`
- `Loading`

Il tab `Cart Pop-up` usa la funzione di salvataggio centralizzata `bw_cart_popup_save_settings()` definita in `cart-popup/admin/settings-page.php`.

## 11) Tutti i campi del tab Cart Pop-up (con option key)

### A) Generale
- `bw_cart_popup_active`: abilita/disabilita popup globale.
- `bw_cart_popup_show_floating_trigger`: bottone cart fisso flottante.
- `bw_cart_popup_slide_animation`: apertura automatica popup su add-to-cart.
- `bw_cart_popup_panel_width`: larghezza pannello desktop (px).
- `bw_cart_popup_mobile_width`: larghezza pannello mobile (%).
- `bw_cart_popup_overlay_color`: colore overlay.
- `bw_cart_popup_overlay_opacity`: opacità overlay (0-1).
- `bw_cart_popup_panel_bg`: background pannello.
- `bw_cart_popup_show_quantity_badge`: badge quantità su thumbnail item.

### B) CTA Checkout
- `bw_cart_popup_checkout_text`: testo bottone checkout.
- `bw_cart_popup_checkout_bg`: bg normale.
- `bw_cart_popup_checkout_bg_hover`: bg hover.
- `bw_cart_popup_checkout_text_color`: testo normale.
- `bw_cart_popup_checkout_text_hover`: testo hover.
- `bw_cart_popup_checkout_font_size`: font-size.
- `bw_cart_popup_checkout_border_radius`: border-radius.
- `bw_cart_popup_checkout_border_enabled`: toggle bordo.
- `bw_cart_popup_checkout_border_width`: spessore bordo.
- `bw_cart_popup_checkout_border_style`: stile bordo.
- `bw_cart_popup_checkout_border_color`: colore bordo.
- `bw_cart_popup_checkout_padding_top`
- `bw_cart_popup_checkout_padding_right`
- `bw_cart_popup_checkout_padding_bottom`
- `bw_cart_popup_checkout_padding_left`

### C) CTA Continue Shopping
- `bw_cart_popup_continue_text`: testo bottone.
- `bw_cart_popup_continue_url`: URL personalizzato continue.
- `bw_cart_popup_continue_bg`: bg normale.
- `bw_cart_popup_continue_bg_hover`: bg hover.
- `bw_cart_popup_continue_text_color`: testo normale.
- `bw_cart_popup_continue_text_hover`: testo hover.
- `bw_cart_popup_continue_font_size`: font-size.
- `bw_cart_popup_continue_border_radius`: border-radius.
- `bw_cart_popup_continue_border_enabled`: toggle bordo.
- `bw_cart_popup_continue_border_width`: spessore bordo.
- `bw_cart_popup_continue_border_style`: stile bordo.
- `bw_cart_popup_continue_border_color`: colore bordo.
- `bw_cart_popup_continue_padding_top`
- `bw_cart_popup_continue_padding_right`
- `bw_cart_popup_continue_padding_bottom`
- `bw_cart_popup_continue_padding_left`

### D) Promo code section
- `bw_cart_popup_promo_section_label`: label sezione promo.
- `bw_cart_popup_promo_input_padding_top`
- `bw_cart_popup_promo_input_padding_right`
- `bw_cart_popup_promo_input_padding_bottom`
- `bw_cart_popup_promo_input_padding_left`
- `bw_cart_popup_promo_placeholder_font_size`: dimensione placeholder input.
- `bw_cart_popup_apply_button_font_weight`: peso font bottone Apply.

### E) Empty cart
- `bw_cart_popup_return_shop_url`: URL pulsante Return to Shop.
- `bw_cart_popup_empty_cart_svg`: SVG custom stato empty.
- `bw_cart_popup_empty_cart_padding_top`
- `bw_cart_popup_empty_cart_padding_right`
- `bw_cart_popup_empty_cart_padding_bottom`
- `bw_cart_popup_empty_cart_padding_left`

### F) SVG cart icon
- `bw_cart_popup_additional_svg`: SVG custom aggiuntivo nel popup.
- `bw_cart_popup_cart_icon_margin_top`
- `bw_cart_popup_cart_icon_margin_right`
- `bw_cart_popup_cart_icon_margin_bottom`
- `bw_cart_popup_cart_icon_margin_left`
- `bw_cart_popup_svg_black`: forza fill nero su SVG custom.

### G) Opzioni legacy ancora salvate
- `bw_cart_popup_checkout_color`
- `bw_cart_popup_continue_color`

Sono ancora persistite e localizzate in JS config, ma lo stile effettivo corrente è guidato soprattutto dai campi `*_bg`, `*_text_color`, `*_border_*`, `*_padding_*` e dal CSS dinamico.

## 12) Note tecniche importanti

- Checkout URL popup: nel JS localizzato viene forzato `home_url('/checkout/')`; nel markup PHP è `wc_get_checkout_url()`. Il JS forza poi l'href del bottone.
- Nel popup il calcolo sconto totale usa `get_discount_total()`, mentre il dettaglio per coupon usa `get_coupon_discount_amounts()`.
- Esistono due stack coupon separati:
  - stack popup (`bw_cart_popup_*`) per pannello cart,
  - stack checkout (`bw_apply_coupon`/`bw_remove_coupon`) per pagina checkout con persistenza sessione hardening.
- La UI checkout è stata stilisticamente allineata al cart popup (pill coupon, input floating label), ma il backend coupon rimane separato.
- L'opzione legacy `bw_cart_popup_checkout_url` viene ripulita in `admin_init` e non è più usata.
- `bw_cart_popup_promo_section_label` è salvata in admin ma il testo visibile della promo area nel popup è hardcoded nel markup frontend corrente.

## 13) File sorgente principali da consultare rapidamente

- Flusso completo popup: `cart-popup/assets/js/bw-cart-popup.js`
- Endpoint popup: `cart-popup/frontend/cart-popup-frontend.php`
- Bootstrap/config popup: `cart-popup/cart-popup.php`
- Salvataggio campi admin popup: `cart-popup/admin/settings-page.php`
- Tab admin unificato: `admin/class-blackwork-site-settings.php`
- Coupon checkout custom: `woocommerce/woocommerce-init.php`
- Coupon checkout frontend: `assets/js/bw-checkout.js`
