# Blackwork Governance -- Task Closure

## Protocol reference
- Closure executed following: `docs/governance/task-close.md`

## 1) Task Identification
- Task ID: `BW-TASK-20260327-02`
- Task title: Cart Popup — bug fix batch: coupon rows stale, error messages, qty=0 DOM, variation_id, double AJAX, XSS
- Domain: Cart Popup
- Tier classification: 1
- Start artifact: `docs/tasks/BW-TASK-20260327-02-start.md`
- Implementation commit(s): `37b8921`

### Commit Traceability
- Commit hash: `37b8921`
- Commit message: `Fix cart popup: coupon rows stale, error messages, qty=0 DOM removal, variation_id, double AJAX, XSS`
- Files impacted:
  - `cart-popup/frontend/cart-popup-frontend.php`
  - `cart-popup/assets/js/bw-cart-popup.js`

## 2) Implementation Summary

### Cosa è stato implementato

**PHP — `cart-popup/frontend/cart-popup-frontend.php`**

- Aggiunta funzione helper `bw_cart_popup_build_totals_data($cart)` che costruisce il payload completo (totali + coupon) in un unico posto. Usata da `remove_item`, `update_quantity`, `apply_coupon`, `remove_coupon` — elimina ~60 righe di codice duplicato e garantisce coerenza del contratto di risposta.
- `bw_cart_popup_apply_coupon()`: path di errore ora chiama `bw_cart_popup_get_first_error_notice()` per restituire il messaggio reale di WooCommerce (spesa minima, limite utilizzi, prodotti esclusi, ecc.) invece di un generico "Coupon code invalid or expired."
- `bw_cart_popup_build_cart_data()`: aggiunto campo `variation_id` per ogni item (`isset($cart_item['variation_id']) ? (int) $cart_item['variation_id'] : 0`).
- `bw_cart_popup_get_cart_contents()`: aggiunto check `POST` method (coerente con tutti gli altri handler).
- `bw_cart_popup_remove_item()` e `bw_cart_popup_update_quantity()`: response ora include `coupons`, `applied_coupons`, `item_count` tramite il nuovo helper.

**JS — `cart-popup/assets/js/bw-cart-popup.js`**

- `_patchTotals()`: ora delega a `updateTotals()`. Siccome il server restituisce sempre `coupons` nel response di aggiornamento quantità, le righe discount vengono ricostruite correttamente.
- `updateTotals()`: rimosso selector morto `.bw-cart-popup-vat` (elemento non esiste nel markup HTML); aggiunta sincronizzazione di `this.appliedCoupons` da `data.applied_coupons` al termine del metodo.
- `updateQuantity()` success handler:
  - sostituito `_patchTotals()` con `updateTotals()` + `updateCouponDisplay()` per tenere coerenti anche il link legacy "Remove coupon" e le righe dinamiche coupon;
  - aggiunta logica di rimozione DOM per il caso `qty → 0` con carrello non svuotato: l'item viene animato e rimosso con la stessa classe `bw-cart-item--removing` usata da `removeItem()`, e rimosso dalla cache `this.cartItems`.
- `init()`: eliminata la doppia chiamata AJAX al caricamento pagina. Quando `show_floating_trigger` è attivo, `loadCartContents(true)` viene eseguito una sola volta e il callback `.always()` chiama `_markButtonsFromCache()` (nuovo metodo). Quando il trigger non è attivo, viene chiamato solo `markButtonsAlreadyInCart()`.
- Nuovo metodo `_markButtonsFromCache()`: esegue la marcatura dei pulsanti "Added to cart" usando `this.cartItems` già in memoria, senza AJAX aggiuntivo.
- `markButtonsAlreadyInCart()`: semplificato per delegare la logica DOM a `_markButtonsFromCache()`.
- `showErrorModal()`: `errorMessage` rimosso dalla template literal HTML e impostato tramite `.text()` dopo l'append al DOM (coerente con `showAlreadyInCartModal`).

### Runtime surfaces touched
- Nessun nuovo hook registrato
- Nessuna modifica alle priorità hook
- Nessun nuovo endpoint AJAX
- Payload AJAX espanso (retrocompatibile): `remove_item` e `update_quantity` ora restituiscono anche `coupons` e `applied_coupons`

## 3) Acceptance Criteria Verification

- Criterion B1 — Discount row aggiornata dopo cambio quantità con coupon percentuale: PASS
  - Il server restituisce `coupons[]` aggiornato nel response di `update_quantity`; `updateTotals()` ricostruisce le righe.
- Criterion B2 — Messaggio errore coupon mostra ragione reale WC: PASS
  - `apply_coupon` usa `bw_cart_popup_get_first_error_notice()` e pulisce le notice con `wc_clear_notices()` dopo l'estrazione.
- Criterion B3 — Item rimosso dal DOM quando qty → 0 con altri item presenti: PASS
  - Il JS detecta `parseInt(quantity, 10) === 0` nel success handler e anima la rimozione prima dell'aggiornamento totali.
- Criterion B4 — `variation_id` incluso nei dati item: PASS
  - `bw_cart_popup_build_cart_data()` popola `variation_id` per ogni item; `hasCartVariation()` lo legge correttamente.
- Criterion B5 — Singola AJAX al caricamento pagina: PASS
  - `init()` ora esegue un solo fetch quando `show_floating_trigger` è attivo, usando `_markButtonsFromCache()` nel callback.
- Criterion B6 — `showErrorModal` non interpola HTML grezzo: PASS
  - Messaggio impostato via `.text()` dopo l'append, identico al comportamento di `showAlreadyInCartModal`.
- Criterion B7 — Selector `.bw-cart-popup-vat` rimosso: PASS
  - Rimosso da `updateTotals()` e `_patchTotals()`.
- Criterion B8 — `get_contents` POST-only: PASS
  - Check `$_SERVER['REQUEST_METHOD'] === 'POST'` aggiunto come primo guard.

### Testing Evidence
- Local testing performed: Partial
- Environment used: repository workspace, static code inspection, PHP syntax verification
- Checks executed:
  - `php -l cart-popup/frontend/cart-popup-frontend.php` → PASS
  - `php -l cart-popup/assets/js/bw-cart-popup.js` → N/A (JS)
  - ispezione statica di tutti i metodi modificati
- Edge cases tested (code inspection):
  - carrello svuotato via qty=0 (ultimo item): `empty === true` → `showEmptyState()` → non tocca la logica di rimozione DOM singola
  - carrello con più item e un item ridotto a 0: rimozione DOM + aggiornamento totali coupon
  - coupon percentuale con quantità modificata: `updateTotals(response.data)` ricostruisce righe con nuovi importi
  - coupon con errore WC specifico (spesa minima): `bw_cart_popup_get_first_error_notice()` restituisce il messaggio reale
  - floating trigger disabilitato: `markButtonsAlreadyInCart()` invocato normalmente (un solo AJAX)

## 4) Regression Surface Verification

- Surface: add to cart (AJAX intercept e nativo WooCommerce)
  - Verifica: `addToCartAjax()`, `interceptAddToCart()`, `interceptWidgetAddToCart()` non toccati
  - Result: PASS
- Surface: aggiornamento quantità
  - Verifica: success handler modificato; path `empty === true` (showEmptyState) invariato; path `empty === false` ora include rimozione DOM per qty=0 e updateTotals con coupon
  - Result: PASS
- Surface: rimozione item
  - Verifica: `removeItem()` e handler PHP `bw_cart_popup_remove_item` non modificati nel comportamento (solo payload espanso con coupon); animazione collapse e `loadCartContents` post-remove invariati
  - Result: PASS
- Surface: apply/remove coupon
  - Verifica: `apply_coupon` PHP semplificato con helper ma stesso output; `remove_coupon` PHP semplificato con helper ma stesso output; JS `applyCoupon()` e `removeCoupon()` invariati
  - Result: PASS
- Surface: apertura pannello da header e floating trigger
  - Verifica: `openPanel()`, `syncHeaderCartBadge()` non toccati; `init()` modificato solo nella sequenza di chiamate iniziali
  - Result: PASS
- Surface: badge count
  - Verifica: `updateBadge()`, `syncHeaderCartBadge()` non toccati; chiamate a `updateBadge` nel success handler di `updateQuantity` invariate
  - Result: PASS
- Surface: `markButtonsAlreadyInCart` — pulsanti già nel carrello al page load
  - Verifica: logica identica, ora separata in `_markButtonsFromCache()`; risultato DOM identico
  - Result: PASS

## 5) Determinism Verification
- Input/output determinism verified: Yes
  - Stesso stato carrello produce sempre lo stesso payload da `bw_cart_popup_build_totals_data()`
- Ordering determinism verified: Yes
  - `calculate_totals()` sempre chiamato prima di leggere valori; helper legge dopo il ricalcolo
- Retry/re-entry convergence verified: Yes
  - Guard `_pendingItemKeys` invariato; debounce qty invariato

## 6) Documentation Alignment Verification
- `docs/30-features/cart-popup/`
  - Impacted: Yes
  - Documents updated:
    - `docs/30-features/cart-popup/cart-popup-technical-guide.md` — aggiunta sezione 16 "Hardening batch 2026-03-27"
- `docs/tasks/`
  - Impacted: Yes
  - Documents updated:
    - `docs/tasks/BW-TASK-20260327-02-start.md`
    - `docs/tasks/BW-TASK-20260327-02-closure.md`
- root docs:
  - Impacted: Yes
  - Documents updated:
    - `CHANGELOG.md`

## 7) Governance Artifact Updates
- Roadmap updated: No (bug fix, nessun impatto roadmap)
- Decision log updated: No
- Risk register updated: No (nessun nuovo rischio aperto)
- Risk status dashboard updated: No
- Runtime hook map updated: No (nessun hook modificato)
- Feature documentation updated: Yes — `cart-popup-technical-guide.md` sezione 16

## 8) Final Integrity Check
- Nessuna nuova authority surface introdotta
- Nessun secondo truth source creato
- Nessun invariante violato (formato `applied_coupons`/`coupons` preservato)
- Nessun runtime hook aggiunto o modificato
- Payload AJAX espanso retrocompatibilmente (solo campi aggiunti)

- Integrity verification status: PASS

## Rollback Safety
- Can the change be reverted via commit revert: Yes — `git revert 37b8921`
- Database migration involved: No
- Manual rollback steps required: None

## Post-Closure Monitoring
- Monitoring required: Yes
- Surfaces to monitor:
  - cart popup — aggiornamento quantità con coupon percentuale attivo
  - cart popup — apply coupon con codici a requisiti specifici (spesa minima, prodotti limitati)
  - cart popup — rimozione item via qty=0 con più prodotti nel carrello
  - badge count header — coerenza dopo add/remove/qty update
- Monitoring duration: primo ciclo di utilizzo in produzione

## Release Gate Preparation
- Release Gate required: Yes (modifica runtime cart popup)
- Runtime surfaces to verify:
  - add to cart → popup si apre, lista item corretta, badge aggiornato
  - cambio quantità con coupon percentuale → discount row aggiornata correttamente
  - inserimento coupon con requisiti non soddisfatti → messaggio reale WC visibile
  - qty → 0 con altri item presenti → item scompare animato, totali corretti
  - page load con floating trigger attivo → una sola AJAX visibile in Network tab
- Rollback readiness confirmed: Yes

## 9) Closure Declaration
- Task closure status: CLOSED
- Responsible reviewer: Codex
- Date: 2026-03-27
