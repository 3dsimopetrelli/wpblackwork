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
- `blackwork-core-plugin.php` (bootstrap modulo)
- `admin/class-blackwork-site-settings.php` (pannello unificato “Blackwork Site”, tab Cart Pop-up)
- `includes/modules/header/templates/header.php` (trigger cart icona header)
- `includes/modules/header/assets/js/bw-navshop.js` (apertura popup da header)
- `includes/widgets/class-bw-slick-slider-widget.php` (data attribute `data-open-cart-popup="1"`)
- `includes/widgets/class-bw-product-grid-widget.php` (data attribute `data-open-cart-popup="1"`)
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

1. `blackwork-core-plugin.php` include `cart-popup/cart-popup.php`.
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

Visual contract attuale della shell popup:
- overlay leggero con blur, per lasciare percepibile il contesto pagina dietro al pannello
- pannello detached dark-glass con margini stretti viewport-edge e radius ampio
- close button circolare e CTA footer trattati come floating controls coerenti con le altre navigation surfaces mobili

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

#### SVG security hardening note (2026-03-09)
- Save-time sanitizer for `bw_cart_popup_additional_svg` and `bw_cart_popup_empty_cart_svg` now reuses canonical plugin pipeline:
  - `bw_mew_svg_sanitize_content()`
  - `bw_mew_svg_is_valid_document()`
- Invalid/malformed SVG payloads are rejected and not persisted.
- Frontend rendering now applies strict output-time `wp_kses` allowlist before echoing stored SVG options (defense in depth).
- `bw_cart_popup_svg_black` transformation now uses `fill="#000"` attribute before output allowlist sanitization.
- Fallback local allowlist removed `style` attribute to reduce SVG inline-style attack surface.

#### Admin input integrity hardening note (2026-03-10)
- Save path `bw_cart_popup_save_settings()` now normalizes scalar reads with `wp_unslash` before sanitization.
- Bounded numeric fields now apply deterministic clamping (panel/mobile width, overlay opacity, paddings, margins, font sizes, border radius/width).
- Enum-like fields now use allowlists:
  - `bw_cart_popup_checkout_border_style`
  - `bw_cart_popup_continue_border_style`
  - `bw_cart_popup_apply_button_font_weight`
- Option keys and admin UX remain unchanged.

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

## 14) Cart Domain Boundary (Non-Authority Classification)

### Cart domain owns
- Cart UI behavior (panel, mini-cart interactions, quantity/remove/coupon UX in cart surfaces).
- Cart presentation state and transition-to-checkout controls.
- Cart-side fragment refresh for cart visualization consistency.

### Cart domain is explicitly non-authoritative for
- Payment truth.
- Authentication/session truth.
- Provisioning/entitlement truth.
- Checkout payment selector authority.

### Cart MUST NOT mutate
- Checkout payment method authority or submit-path state.
- Order/payment lifecycle truth.
- Callback/webhook reconciliation outcomes.
- Provisioning claim state.

### Transition-to-checkout contract
- Cart MAY initiate navigation to checkout.
- Cart MUST hand off orchestration authority to Checkout at transition boundary.
- After handoff, payment eligibility, selector state, and return-surface behavior belong to Checkout domain.

### Fragment responsibility split
- Cart fragment updates: cart visualization only.
- Checkout fragment convergence: checkout orchestration concern (see checkout architecture map).

### Cross-domain reference
- Checkout boundary and authority rules: `../checkout/checkout-architecture-map.md` (section "Domain Boundary: Checkout vs Cart").
- Salvataggio campi admin popup: `cart-popup/admin/settings-page.php`
- Tab admin unificato: `admin/class-blackwork-site-settings.php`
- Coupon checkout custom: `woocommerce/woocommerce-init.php`
- Coupon checkout frontend: `assets/js/bw-checkout.js`

## 15) Hardening batch 2026-03-16

### 15.1 Security — XSS hardening in template literals (R-SEC-30, R-SEC-31)

Due helper aggiunti all'oggetto `BW_CartPopup` in `cart-popup/assets/js/bw-cart-popup.js`:

| Helper | Escapes | Uso |
|--------|---------|-----|
| `_escHtml(str)` | `& < > " '` | testo interpolato come contenuto di nodi HTML |
| `_escUrl(url)` | blocca `javascript:` e `data:` URI, poi escapa HTML | valori in attributo `href` |

Applicazioni:

- `renderCartItems()`: `item.permalink` → `self._escUrl(item.permalink)`, `item.name` → `self._escHtml(item.name)`.
- `updateTotals()` loop coupon: `code` → `var safeCode = self._escHtml(code)` applicato sia al nodo `<b>` che all'attributo `data-code`.

Campi lasciati non-escaped (HTML trusted server-side):
- `item.image` — output di `get_the_post_thumbnail()` (WP)
- `item.subtotal`, `item.regular_subtotal` — output di `wc_price()` (WC)
- `item.key` — hash MD5 (solo caratteri `[a-f0-9]`)
- `item.product_id` — già validato con `parseInt`

Rischi chiusi: R-SEC-30 (Medium), R-SEC-31 (Low).

### 15.2 Phase 2 — Rimozione campi stile dinamici dall'admin

Commit: `c8c0c7ef`, `c57254a6`.

- Rimossi dal tab `Cart Pop-up` in `admin/class-blackwork-site-settings.php` tutti i campi di stile per bottoni (checkout/continue: colori, font-size, border-radius, border, padding) e per la promo section.
- Rimossa la generazione di CSS dinamico inline (`bw_cart_popup_dynamic_css()`) in `cart-popup/frontend/cart-popup-frontend.php`.
- Lo stile del popup è ora interamente governato da `cart-popup/assets/css/bw-cart-popup.css`.
- Le option key corrispondenti rimangono nel DB ma non vengono più lette a runtime.

### 15.3 Bug fixes — Visual e funzionali

Commit: `25704c57`, `910fed76`, `b1f8c556`, `9c83329a`, `52cd7b48`.

| Issue | Soluzione |
|-------|-----------|
| Bottone Continue Shopping overflow nel footer | Fix CSS `overflow`/`flex` nel footer del popup |
| Badge quantità non circolare | Fix `border-radius` e `aspect-ratio` sul badge |
| Prezzi item non visibili (colore testo) | Fix variabili CSS colore nel layout dark |
| Stato "Added to Cart" non ripristinato su rimozione item | JS: `revertAddToCartButton()` invocato dopo `removeItem()` |
| Rimozione item: placeholder invece di animazione | Sostituzione con collasso animato (`max-height` + `opacity` → `0`) prima del `remove()` |
| Race condition su aggiornamento quantità rapido | Debounce + abort precedente request prima di inviarne una nuova |
| Validazione dati AJAX assente | Guard su `item.product_id`, `item.quantity`, `item.key` prima del render |
| Error handling mancante su network failure | `showCartError()` invocato su tutti i failure path |

### 15.4 Performance — Ottimizzazioni runtime

Commit: `de190d73`.

- **Open cache**: al secondo `openPanel()` nella stessa sessione il contenuto viene riscritto solo se i dati sono cambiati (confronto hash).
- **Partial qty update**: `updateQuantity()` ora invia solo la quantità e aggiorna solo i totali (`_patchTotals()`), senza ri-renderizzare l'intera lista item.
- **Batch coupon append**: le righe coupon vengono accumulate in una stringa e inserite nel DOM con un singolo `append()`, evitando reflow multipli.

### 15.5 Rimozione dead code

Commit: `cc90dcee`.

- `getCheckoutUrl()` — helper non più usato (URL checkout è hardcoded nel JS localizzato); rimosso.
- `bw_cart_popup_hex_to_rgb()` — helper per conversione hex → RGB usato solo dalla generazione CSS dinamica rimossa nella fase 2; rimosso.
- `bw_cart_popup_dynamic_css` filter in `cart-popup/frontend/cart-popup-frontend.php` rimosso.

## 16) Hardening batch 2026-03-27

Task: `BW-TASK-20260327-02`
Commit: `37b8921`
File modificati: `cart-popup/frontend/cart-popup-frontend.php`, `cart-popup/assets/js/bw-cart-popup.js`

### 16.1 Bug B1 — Discount row stale dopo cambio quantità (coupon percentuale)

**Root cause:** `update_quantity` e `remove_item` restituivano solo `subtotal/discount/tax/total` senza `coupons`/`applied_coupons`. Il JS chiamava `_patchTotals()` che aggiornava i numeri ma non ricostruiva le righe coupon dinamiche. Con coupon percentuale il totale era corretto ma la riga "Discount" mostrava l'importo calcolato per la quantità precedente.

**Fix PHP:** aggiunta `bw_cart_popup_build_totals_data($cart)` — helper condiviso da tutti e 4 gli handler di mutazione (`remove_item`, `update_quantity`, `apply_coupon`, `remove_coupon`). Ogni handler ora restituisce il payload completo: `item_count`, `empty`, `subtotal*`, `discount*`, `tax*`, `total*`, `applied_coupons`, `coupons[]`. Elimina ~60 righe duplicate e garantisce coerenza del contratto.

**Fix JS:** `_patchTotals()` ora delega integralmente a `updateTotals()`. `updateQuantity()` success handler chiama `updateTotals(response.data)` + `updateCouponDisplay(response.data.applied_coupons)` invece di `_patchTotals()`.

**Invariante:** il formato `applied_coupons` (array di stringhe) e `coupons` (array di oggetti `{code, amount, amount_raw}`) sono preservati in tutti i response.

### 16.2 Bug B2 — Messaggio errore coupon sempre generico

**Root cause:** `bw_cart_popup_apply_coupon()` restituiva sempre `'Coupon code invalid or expired.'` ignorando le WC notices impostate da `$cart->add_discount()`.

**Fix:** path di errore ora chiama `bw_cart_popup_get_first_error_notice()` (già usato da `bw_cart_popup_ajax_add_to_cart`) e poi `wc_clear_notices()`. Il messaggio reale di WooCommerce (spesa minima non raggiunta, limite utilizzi superato, prodotti non idonei, ecc.) viene ora restituito all'utente.

### 16.3 Bug B3 — Item a qty=0 rimane nel DOM se il carrello non è svuotato

**Root cause:** quando si premeva `-` su un prodotto a qty=1, il JS calcolava `newQty=0` e chiamava `updateQuantity(key, 0)`. Il PHP rimuoveva l'item. Il JS success handler per `empty === false` chiamava solo `_patchTotals` + `updateBadge`, lasciando l'elemento nel DOM a qty=0 fino alla prossima apertura del pannello.

**Fix:** nel branch `empty === false` il JS detecta `parseInt(quantity, 10) === 0`, applica la classe `bw-cart-item--removing` per l'animazione di collapse (identica a `removeItem()`), rimuove l'elemento dal DOM dopo 300ms, e aggiorna `this.cartItems` rimuovendo l'item dalla cache locale.

### 16.4 Bug B4 — `variation_id` mancante nei dati item

**Root cause:** `bw_cart_popup_build_cart_data()` non includeva `variation_id` per i prodotti variabili. Il JS `hasCartVariation()` leggeva `item.variation_id || 0` trovando sempre 0, rendendo impossibile distinguere varianti diverse dello stesso prodotto in `markButtonsAlreadyInCart()`.

**Fix:** aggiunto `'variation_id' => isset($cart_item['variation_id']) ? (int) $cart_item['variation_id'] : 0` nell'array item di `bw_cart_popup_build_cart_data()`.

### 16.5 Performance B5 — Doppia AJAX al caricamento pagina

**Root cause:** `init()` chiamava sia `markButtonsAlreadyInCart()` che `loadCartContents(true)` quando `show_floating_trigger` era attivo, eseguendo due AJAX identiche verso `bw_cart_popup_get_contents`.

**Fix:** `init()` ora esegue una sola chiamata. Quando `show_floating_trigger` è attivo: `loadCartContents(true)` + callback `.always(() => _markButtonsFromCache())`. Quando non è attivo: solo `markButtonsAlreadyInCart()`.

Aggiunto metodo `_markButtonsFromCache()` che esegue la marcatura DOM dai `this.cartItems` già in memoria, senza AJAX aggiuntivo. `markButtonsAlreadyInCart()` semplificato per delegare la logica DOM a `_markButtonsFromCache()` dopo il fetch.

### 16.6 Security B6 — XSS hardening in `showErrorModal`

**Root cause:** `showErrorModal()` interpolava `${errorMessage}` direttamente nella template literal HTML, a differenza di `showAlreadyInCartModal()` che usava `.text()`.

**Fix:** `errorMessage` rimosso dalla template literal; impostato via `$modal.find('.bw-cart-error-modal__message').text(errorMessage)` dopo l'append al DOM. Coerente con il comportamento di `showAlreadyInCartModal`.

### 16.7 Fix B7 — Selector `.bw-cart-popup-vat` rimosso

Il selector `.bw-cart-popup-vat .value` in `updateTotals()` e `_patchTotals()` non corrispondeva ad alcun elemento nel markup HTML del pannello. Rimosso. In `updateTotals()` è stata aggiunta la sincronizzazione di `this.appliedCoupons` da `data.applied_coupons` per tenere coerente il link legacy "Remove coupon".

### 16.8 Fix B8 — `bw_cart_popup_get_cart_contents` POST-only

Aggiunto check `$_SERVER['REQUEST_METHOD'] === 'POST'` come primo guard in `bw_cart_popup_get_cart_contents()`, coerente con tutti gli altri handler del modulo.
