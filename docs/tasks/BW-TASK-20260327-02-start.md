# Blackwork Governance -- Task Start

## 1) Context
- Task ID: `BW-TASK-20260327-02`
- Task title: Cart Popup — bug fix batch: coupon rows stale, error messages, qty=0 DOM, variation_id, double AJAX, XSS
- Request source: User audit request on 2026-03-27 — full review of add-to-cart, coupon, quantity, totals, and badge logic
- Expected outcome:
  - discount rows nel popup sempre sincronizzate con il server dopo ogni modifica quantità
  - messaggi errore coupon mostrano il motivo reale di WooCommerce (non messaggio generico)
  - item rimosso dal DOM immediatamente quando qty raggiunge 0 (non solo alla prossima apertura)
  - `variation_id` incluso nei dati item per corretta identificazione varianti nel carrello
  - eliminata la doppia chiamata AJAX al caricamento pagina quando floating trigger è attivo
  - `showErrorModal` non interpola HTML non-escaped nella template literal
  - selector morto `.bw-cart-popup-vat` rimosso; `appliedCoupons` mantenuto sincronizzato in `updateTotals`
  - `bw_cart_popup_get_cart_contents` protetto da richieste GET
- Constraints:
  - non modificare altri moduli (checkout, header, product-grid)
  - non cambiare la struttura dei dati restituiti agli endpoint già funzionanti
  - preservare retrocompatibilità con il formato `applied_coupons` (array legacy) e `coupons` (array dettagliato)
  - non introdurre nuovi endpoint AJAX

## 2) Task Classification
- Domain: Cart Popup
- Incident/Task type: Bug fix batch + security hardening
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `cart-popup/frontend/cart-popup-frontend.php`
  - `cart-popup/assets/js/bw-cart-popup.js`
- Integration impact: Low — modifiche interne al modulo cart popup; nessuna modifica alle API esposte verso altri moduli
- Regression scope required:
  - flusso add to cart (AJAX e nativo WooCommerce)
  - aggiornamento quantità con e senza coupon applicato
  - rimozione item singolo e svuotamento carrello
  - apply/remove coupon (fixed e percentuale)
  - apertura pannello da header, da widget, da floating trigger
  - badge count (header + floating)
  - marcatura pulsanti "Already in cart"

## 3) Pre-Task Reading Checklist
- Feature docs letti:
  - `docs/30-features/cart-popup/cart-popup-technical-guide.md`
  - `docs/30-features/cart-popup/cart-popup-module-spec.md`
- Codice letto:
  - `cart-popup/frontend/cart-popup-frontend.php` (completo)
  - `cart-popup/assets/js/bw-cart-popup.js` (completo)
  - `cart-popup/cart-popup.php`
  - `cart-popup/admin/settings-page.php`
  - `includes/modules/header/assets/js/bw-navshop.js`
  - `includes/modules/header/frontend/fragments.php`
  - `assets/js/bw-cart.js`

## 4) Analysis Notes

### Bug identificati dall'audit

| ID | Gravità | Descrizione | File |
|----|---------|-------------|------|
| B1 | Alta | Discount rows stale dopo cambio quantità con coupon percentuale — `_patchTotals` non aggiornava le righe coupon | `bw-cart-popup.js`, `cart-popup-frontend.php` |
| B2 | Alta | Messaggio errore coupon sempre generico — `apply_coupon` ignorava le WC notices reali | `cart-popup-frontend.php` |
| B3 | Media | Item a qty=0 rimane nel DOM — il JS non rimuoveva l'elemento se il carrello non era completamente svuotato | `bw-cart-popup.js` |
| B4 | Media | `variation_id` mancante nei dati item — `hasCartVariation()` e `markButtonsAlreadyInCart` non distinguevano varianti | `cart-popup-frontend.php` |
| B5 | Bassa | Doppia AJAX su page load — `init()` chiamava sia `markButtonsAlreadyInCart()` che `loadCartContents()` quando floating trigger era attivo | `bw-cart-popup.js` |
| B6 | Bassa | XSS hardening inconsistente — `showErrorModal` interpolava `errorMessage` direttamente in template literal HTML | `bw-cart-popup.js` |
| B7 | Info | Selector morto `.bw-cart-popup-vat` in `updateTotals` e `_patchTotals` (elemento non esiste nel markup) | `bw-cart-popup.js` |
| B8 | Info | `bw_cart_popup_get_cart_contents` accettava richieste GET (gli altri handler tutti POST-only) | `cart-popup-frontend.php` |

### Root cause B1 (più critico)
`update_quantity` e `remove_item` restituivano solo subtotal/discount/tax/total senza `coupons`/`applied_coupons`. Il JS chiamava `_patchTotals()` che aggiornava solo i numeri ma non ricostruiva le righe coupon dinamiche. Con coupon percentuale: il totale si aggiornava correttamente ma la riga mostrasse lo sconto calcolato per la quantità precedente.

### Strategia adottata
- Aggiungere `bw_cart_popup_build_totals_data($cart)` come helper condiviso da tutti e 4 gli handler di mutazione (elimina ~60 righe duplicate e garantisce payload uniforme con coupon inclusi)
- `_patchTotals()` delega a `updateTotals()` che ora ha sempre i dati coupon
- `updateTotals()` sincronizza `this.appliedCoupons` da `data.applied_coupons`
- `_markButtonsFromCache()` estrae la logica di marcatura pulsanti per essere riusata senza AJAX aggiuntivo

## 5) Scope Declaration
- Proposed strategy: bug fix mirati sui due file del modulo, senza toccare markup HTML, CSS, o altri moduli
- Files impacted:
  - `cart-popup/frontend/cart-popup-frontend.php`
  - `cart-popup/assets/js/bw-cart-popup.js`
- Explicitly out-of-scope:
  - `cart-popup/cart-popup.php` (bootstrap, nessun bug)
  - `cart-popup/assets/css/bw-cart-popup.css`
  - checkout coupon stack (`woocommerce/woocommerce-init.php`, `assets/js/bw-checkout.js`)
  - header module
  - product grid
  - Supabase surfaces (nessun contatto)
- Risk analysis: Basso. Tutte le modifiche sono interne al modulo cart popup. Le API AJAX restituiscono un superset dei dati precedenti (aggiungono `coupons`/`applied_coupons` nei response che prima non li avevano) — retrocompatibile.
- ADR evaluation: NOT REQUIRED

## 6) Runtime Surface Declaration
- New hooks expected: nessuno
- Hook priority modifications: nessuna
- Filters expected: nessuno
- AJAX endpoints added: nessuno
- AJAX endpoints modified (payload espanso, retrocompatibile):
  - `bw_cart_popup_remove_item` — aggiunto `coupons`, `applied_coupons`, `item_count` nel response
  - `bw_cart_popup_update_quantity` — aggiunto `coupons`, `applied_coupons`, `item_count` nel response
  - `bw_cart_popup_get_cart_contents` — aggiunto check POST method
  - `bw_cart_popup_build_cart_data()` — aggiunto `variation_id` per item

## 7) Governance Impact Analysis
- Authority surfaces touched: nessuna
- Data integrity risk: nessuno
- Security surface changes: riduzione rischio XSS in `showErrorModal`; POST-only enforcement su `get_contents`
- Runtime hook/order changes: nessuno
- Requires ADR: No
- Risk register impact required: No (nessun nuovo rischio aperto; rischio XSS ridotto — consistente con hardening precedente R-SEC-30/31)
- Risk dashboard impact required: No

## 8) System Invariants Check
- Invarianti dichiarate che DEVONO rimanere vere:
  - il formato `applied_coupons` (array di stringhe) DEVE essere preservato in tutti i response
  - il formato `coupons` (array di oggetti `{code, amount, amount_raw}`) DEVE essere preservato
  - nonce `bw_cart_popup_nonce` DEVE proteggere tutti gli endpoint
  - `calculate_totals()` DEVE essere chiamato prima di leggere i totali
- Invarianti a rischio: No
- Piano di mitigazione: n/a

## 9) Acceptance Gate
- Task Classification completed: Yes
- Pre-Task Reading Checklist completed: Yes
- Scope Declaration completed: Yes
- Implementation Scope Lock passed: Yes
- Governance Impact Analysis completed: Yes
- System Invariants Check completed: Yes
- Documentation Update Plan completed: Yes

## 10) Current Readiness
- Status: OPEN
- Analysis status: READY FOR IMPLEMENTATION
