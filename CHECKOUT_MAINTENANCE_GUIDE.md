# Checkout Maintenance Guide
**Analisi completa — Sicurezza, Stabilità, Efficienza**
Analisi iniziale: 2026-02-24 | Ultima revisione con fix applicati: 2026-02-24

> Le voci contrassegnate con ✅ **RISOLTO** sono state corrette nel codice nella sessione di hardening del 2026-02-24.

---

## Indice

1. [Stato generale](#1-stato-generale)
2. [Problemi di sicurezza — da risolvere](#2-problemi-di-sicurezza--da-risolvere)
3. [Rischi integrità pagamento — false transazioni](#3-rischi-integrità-pagamento--false-transazioni)
4. [Documentazione obsoleta o incoerente](#4-documentazione-obsoleta-o-incoerente)
5. [Gap nei test e nella copertura](#5-gap-nei-test-e-nella-copertura)
6. [Miglioramenti UX e stabilità frontend](#6-miglioramenti-ux-e-stabilità-frontend)
7. [Webhook — hardening mancante](#7-webhook--hardening-mancante)
8. [Performance e asset](#8-performance-e-asset)
9. [Accessibilità e conformità](#9-accessibilità-e-conformità)
10. [Checklist di regressione pre-release](#10-checklist-di-regressione-pre-release)
11. [Baseline — cosa funziona correttamente](#11-baseline--cosa-funziona-correttamente)
12. [Code Cleanup Backlog — vacuum audit](#12-code-cleanup-backlog--vacuum-audit)

---

## 1. Stato generale

Il checkout BlackWork è un sistema custom completo costruito sopra WooCommerce con:
- Layout a due colonne (CSS Grid, non float WooCommerce standard)
- Tre gateway custom Stripe (`bw_google_pay`, `bw_klarna`, `bw_apple_pay`)
- Classe base astratta condivisa (`BW_Abstract_Stripe_Gateway`)
- Gestione campi checkout configurabile da admin
- Newsletter checkout con Brevo
- Gestione notice e floating labels custom

**Giudizio complessivo:** L'architettura di pagamento è sostanzialmente corretta nel flusso principale (nessun completamento prematuro, dedup webhook presente, firma Stripe verificata). I problemi critici sono concentrati su console.log in produzione, path obsoleti nella documentazione, mancanza di rate limiting, e alcuni edge case del flow return/cancel.

---

## 2. Problemi di sicurezza — da risolvere

### 2.1 ✅ RISOLTO — `console.log` non protetti in produzione

**File:** `assets/js/bw-apple-pay.js`

**Problema originale:** La chiamata `console.log('[BW Apple Pay] canMakePayment:', result)` alla riga ~356 era priva del guard `BW_APPLE_PAY_DEBUG &&`, esponendo dati interni Stripe in produzione.

**Fix applicato:** Aggiunto il guard `BW_APPLE_PAY_DEBUG &&` davanti alla chiamata.

```js
// Ora corretto:
BW_APPLE_PAY_DEBUG && console.log('[BW Apple Pay] canMakePayment:', result);
```

La seconda istruzione `console.info` (riga ~508) era già protetta da `bwApplePayParams.adminDebug`, che è un flag server-side attivo solo per admin con `WP_DEBUG=true` — corretto e invariato.

---

### 2.2 ✅ RISOLTO — Rate limiting sugli AJAX handler coupon

**File:** `woocommerce/woocommerce-init.php`

**Fix applicato:** Aggiunta la funzione `bw_mew_coupon_rate_limit_check()` — transient-based, per IP, max 10 tentativi per 5 minuti per azione. Chiamata come prima istruzione in entrambi gli handler, subito dopo `check_ajax_referer()`.

```php
// Max 10 tentativi / 5 minuti / IP
bw_mew_coupon_rate_limit_check( 'apply_coupon' );
bw_mew_coupon_rate_limit_check( 'remove_coupon' );
```

Restituisce HTTP 429 con `wp_send_json_error()` se il limite è superato.

> Per protezione a livello server (Nginx/Cloudflare) su `/wp-admin/admin-ajax.php`, configurare regole di rate limiting lato infrastruttura in aggiunta.

---

### 2.3 Capability check mancante su coupon AJAX — MEDIO

**File:** `woocommerce/woocommerce-init.php`

```php
// bw_mew_ajax_apply_coupon() — nessun current_user_can()
// bw_mew_ajax_remove_coupon() — nessun current_user_can()
```

I due handler verificano il nonce ma non verificano capabilities. Gli utenti non autenticati possono applicare/rimuovere coupon direttamente tramite AJAX senza passare per il checkout form normale.

**Fix:** Per `nopriv`, il comportamento è corretto funzionalmente (ospiti devono poter usare coupon), ma aggiungere un check che la sessione WooCommerce esista e che l'operazione riguardi il carrello corrente della sessione attiva.

---

### 2.4 URL `/checkout/` hardcoded — BASSO / DESIGN RISK

**Nota in CLAUDE.md:** L'URL del checkout è hardcoded a `/checkout/` invece di `wc_get_checkout_url()`.

**Rischio:** Se la pagina checkout WooCommerce viene spostata, rinominata o il permalink cambia, i redirect dei gateway falliscono silenziosamente (il `return_url` nel PaymentIntent punta a un URL errato). L'utente completa il pagamento su Stripe ma viene reindirizzato a una pagina inesistente, perdendo il thank-you. Il webhook completerà comunque l'ordine, ma l'esperienza utente è rotta.

**Fix:** Sostituire gli usi hardcoded con `wc_get_checkout_url()` dove possibile, o aggiungere almeno un check di coerenza in admin (warning se la pagina checkout non corrisponde a `/checkout/`).

---

### 2.5 CSP (Content Security Policy) — non documentata

Nessun file di documentazione menziona i requisiti CSP per Stripe.js e Google/Apple Pay.

**Requisiti minimi per Stripe:**
```
script-src https://js.stripe.com;
frame-src https://js.stripe.com https://hooks.stripe.com;
connect-src https://api.stripe.com;
```

**Per Apple Pay aggiuntivo:**
```
connect-src https://apple-pay-gateway.apple.com;
```

Se il sito ha un CSP attivo (header o meta tag), questi domini mancanti bloccano silenziosamente i wallet. Aggiungere una sezione CSP a tutti e tre i gateway guide.

---

## 3. Rischi integrità pagamento — false transazioni

### 3.1 ✅ RISOLTO — Stato ordine standardizzato a `on-hold` in tutti i gateway

**Fix applicato:**
- `class-bw-google-pay-gateway.php`: `succeeded` → `on-hold` (era `pending`)
- `class-bw-klarna-gateway.php`: `succeeded` → `on-hold` (era `pending`)
- `class-bw-apple-pay-gateway.php`: già corretto (`on-hold`)

**Semantica corretta:**

| PI status | Stato ordine | Significato |
|---|---|---|
| `succeeded` / `processing` | `on-hold` | Payment attempted, waiting webhook |
| `requires_action` | `pending` | 3DS redirect in progress |

`on-hold` = pagamento avviato, in attesa di conferma esterna via webhook.
`pending` = ordine creato, nessun tentativo di pagamento effettuato.

---

### 3.2 Race condition: webhook arriva prima del return, ma l'utente è già reindirizzato — EDGE CASE

**Flow attuale:**
1. Utente completa Klarna/Google Pay/Apple Pay
2. Webhook `payment_intent.succeeded` arriva → ordine completato
3. Utente viene reindirizzato con `redirect_status=success`
4. Il router controlla `redirect_status` → non è `failed|canceled` → lascia passare normalmente ✓

**Edge case problematico:**
1. Utente completa il pagamento
2. Utente clicca "annulla" nella finestra Klarna/Google Pay PRIMA che il pagamento sia confermato
3. Webhook arriva e completa l'ordine
4. `redirect_status=canceled` → router prova a fare redirect a checkout con notice di errore
5. Ma l'ordine è già pagato

**Il codice gestisce questo?** Il router in `woocommerce-init.php` (righe 821-823) controlla `$order->is_paid()` prima di resettare. Se l'ordine è già pagato, lascia passare. **Questo è corretto.** Ma manca un redirect esplicito al thank-you page in quel ramo — verificare che il flow "already paid" porti davvero all'order-received.

**Fix consigliato:** Aggiungere al ramo "already paid" un redirect esplicito a `$order->get_checkout_order_received_url()` con un log di debug, invece di lasciare che il template_redirect naturale gestisca la situazione.

---

### 3.3 Free order bypass — da verificare

Il checkout nasconde la sezione pagamento per ordini con totale zero. Verificare che:
- Il totale venga sempre ricalcolato server-side prima di nascondere il form pagamento
- Non sia possibile manipolare il totale via coupon AJAX per portarlo a zero e completare senza pagare
- Il class `is-free-order` che nasconde la sezione payment venga applicato solo dopo aggiornamento server-side del totale (evento `updated_checkout`)

**File da verificare:** `assets/css/bw-checkout.css` (classe `.bw-free-order-active`) e `woocommerce/templates/checkout/payment.php` (condizione free order).

---

### 3.4 Klarna: billing details passati al PI non validati — BASSO

In `class-bw-klarna-gateway.php`, i billing details dell'ordine vengono passati a Stripe come `payment_method_data[billing_details]` per Klarna. Se i campi billing sono vuoti o malformati, Stripe può rifiutare la richiesta.

Il file non sanitiza esplicitamente i valori prima di passarli all'API (probabilmente sono già sanitizzati da WooCommerce al checkout, ma un layer di validazione esplicita sarebbe più sicuro).

---

### 3.5 Idempotency key su PaymentIntent — verifica collisioni

**Google Pay:** `bw_gpay_<order_id>_<hash(pm_id)>` — corretto, include pm_id che cambia a ogni tentativo.

**Klarna e Apple Pay:** verificare che usino lo stesso schema o uno equivalente. Se due tentativi di pagamento falliti usano la stessa idempotency key, il secondo tentativo restituisce il PI del primo (già fallito) senza crearne uno nuovo — l'utente non può riprovare.

**Fix:** Assicurarsi che la idempotency key includa l'ID del payment method E un timestamp o retry counter.

---

## 4. Documentazione obsoleta o incoerente

### 4.1 ✅ RISOLTO — `Gateway Google Pay Guide.md` — path file aggiornato

Il path è stato corretto da `includes/woocommerce-overrides/` a `includes/Gateways/` nella sezione "Core files" del guide.

---

### 4.2 ✅ RISOLTO — `docs/PAYMENTS.md` aggiornato

`docs/PAYMENTS.md` aggiornato per riflettere:
- Tutti e tre i gateway attivi in produzione
- Tabella file structure corretta
- Nuova sezione "Stripe Webhook Configuration" con i tre endpoint e le istruzioni
- Tabella stati ordine
- Sezione stuck order monitoring
- Rimossa la sezione "How to Add New Gateway (Klarna/Apple Pay)" (sostituita con template generico)

---

### 4.3 ✅ RISOLTO — `Gateway Apple Pay Guide.md` aggiornato

La sezione "Debug hook currently present" è stata rimossa e sostituita con una nota che documenta il comportamento corretto: tutti i log sono guardati da flag di debug e non producono output in produzione.

---

### 4.4 `Gateway Klarna Guide.md` — mancano informazioni critiche di mercato

Il guide non documenta:
- Paesi/valute supportati da Klarna via Stripe (Klarna non è disponibile in tutti i paesi)
- Importo minimo/massimo per transazione Klarna
- Cosa succede se la valuta del negozio non è supportata da Klarna (il gateway viene mostrato ugualmente?)

**Fix:** Aggiungere una sezione "Requisiti di mercato" con link alla documentazione Stripe Klarna.

---

### 4.5 `Gateway Apple Pay Guide.md` — chiave fallback Google Pay non documentata chiaramente

Il guide menziona il fallback chiavi Apple→Google, ma non documenta:
- Cosa succede se né Apple Pay né Google Pay hanno chiavi configurate
- Il comportamento quando solo Google Pay ha chiavi ma Apple Pay è abilitato
- Se il `bw_apple_pay_statement_descriptor` viene ignorato quando si usano chiavi Google Pay

---

### 4.6 `CHECKOUT_COMPLETE_GUIDE.md` — sezione Brevo/subscribe assente

Il guide completo del checkout non documenta il modulo Brevo checkout subscribe (`class-bw-checkout-subscribe-frontend.php`), che è parte integrante del checkout e inietta campi nel form WooCommerce.

---

### 4.7 Nessun documento descrive il campo phone country picker

`bw-checkout.js` implementa un phone country picker completo con 40+ paesi, flag emoji, dial codes, formato E.164, e max digits per paese. Non è documentato in nessun MD. Chi modifica il checkout billing form non sa che questo componente esiste e potrebbe romperlo.

---

## 5. Gap nei test e nella copertura

### 5.1 Nessun test mode per Klarna e Apple Pay

Google Pay ha modalità test/live separata con chiavi distinte.
Klarna e Apple Pay sono **solo live** — non è possibile testare il flusso completo in ambiente di sviluppo senza usare chiavi live reali.

**Fix consigliato:**
- Aggiungere test mode a Klarna (Stripe supporta Klarna in test mode con il token `pm_card_klarna`)
- Per Apple Pay, documentare il workaround Stripe test environment (Safari + Stripe test publishable key)

---

### 5.2 `payment-test-checklist.md` non copre i gateway custom

Il file di checklist è dettagliato per UI/visual testing, ma non ha sezioni specifiche per:
- Test flow completo Google Pay (approval → order → webhook → thank you)
- Test flow completo Klarna (redirect → return → webhook)
- Test flow completo Apple Pay (availability check → payment sheet → webhook)
- Test webhook replay (idempotency)
- Test cancellazione e retry

---

### 5.3 Nessun test per il modulo Brevo checkout subscribe

Non esiste documentazione di test per verificare:
- Che il flag `_bw_subscribe_newsletter` venga salvato correttamente
- Che la chiamata Brevo avvenga al timing corretto (created vs paid)
- Che il double opt-in non venga chiamato due volte se l'ordine cambia stato due volte

---

### 5.4 Webhook test locale assente

Nessun documento spiega come testare i webhook Stripe localmente (Stripe CLI `stripe listen --forward-to`). Per un sistema dove i webhook sono la source of truth per il completamento pagamento, questo è un gap operativo critico.

---

## 6. Miglioramenti UX e stabilità frontend

### 6.1 ✅ RISOLTO — Auto-hide notice portato a 6 secondi

**File:** `assets/js/bw-checkout-notices.js`

`REQUIRED_ERRORS_AUTO_HIDE_MS` cambiato da `3000` a `6000`. I messaggi di errore rimangono visibili per 6 secondi prima di scomparire automaticamente (o fino a quando l'utente corregge il campo).

---

### 6.2 `bw-apple-pay.js` — stato "express fallback" non documentato

Il file implementa un `enableExpressFallback` parametro (passato via `bwApplePayParams`) che modifica il comportamento di Apple Pay in contesti non-Safari. Questo path non è documentato e potrebbe attivare comportamenti inattesi in certi browser.

---

### 6.3 Sticky right column — comportamento su tablet non definito

Il CSS gestisce mobile (<768px) e desktop, ma il breakpoint 768-1024px (tablet) non ha un comportamento sticky esplicito definito. Il `payment-test-checklist.md` cita tablet 768-1024px come da testare, ma il CSS non lo copre esplicitamente.

---

### 6.4 Skeleton loader non sincronizzato con Google Pay initializing

Quando Google Pay è in stato "initializing", il checkout mostra un loader generico. Non è chiaro se il skeleton loader della colonna destra sia coordinato con l'inizializzazione di Google Pay, o se ci sia un momento dove entrambi i loader sono visibili insieme (sovrapposizione visiva).

---

### 6.5 Phone country picker — selezione non persistente tra aggiornamenti checkout

`bw-checkout.js` implementa il phone picker, ma WooCommerce triggera `update_checkout` che ridisegna i field. Verificare che la selezione del paese nel picker venga ripristinata correttamente dopo ogni `updated_checkout` event, o che il valore E.164 già nel campo billing_phone guidi la selezione iniziale.

---

## 7. Webhook — hardening mancante

### 7.1 ✅ RISOLTO — Configurazione Stripe webhook documentata

Aggiunta sezione "Stripe Webhook Configuration" in `docs/PAYMENTS.md` con tabella completa dei tre endpoint, URL, eventi da sottoscrivere, e warning critico: se si configura un solo webhook su Stripe, gli altri due gateway non riceveranno mai gli eventi.

---

### 7.2 ✅ RISOLTO — Cron monitoring ordini stuck implementato

**File:** `woocommerce/woocommerce-init.php` (fondo file)

Aggiunta funzione `bw_mew_run_stuck_order_check()` schedulata su `bw_mew_check_stuck_orders` (evento WP-Cron orario):
- Cerca ordini `pending`/`on-hold` con meta `_bw_gpay_pi_id`, `_bw_klarna_pi_id`, o `_bw_apple_pay_pi_id`
- Modificati da più di 4 ore e non pagati
- Invia email all'admin con lista ordini e link alla dashboard WooCommerce
- Logga su `wc-bw-gateway` logger

> In aggiunta, configurare Stripe Dashboard → Webhooks → Alerts per notifiche sui retry failures.

---

### 7.3 Timestamp tolerance di 300 secondi — verifica configurazione Stripe

L'abstract gateway usa una tolerance di 300s per la verifica della Stripe-Signature. Stripe raccomanda ≤300s. Verificare che i server non abbiano clock drift significativo (NTP configurato), altrimenti i webhook vengono rifiutati.

---

### 7.4 Rolling event list — limite 20 eventi non sufficiente per ordini con molti retry

Il dedup usa una rolling list di max 20 event id per ordine. Se un ordine subisce molti tentativi di pagamento falliti (ognuno genera eventi), i primi event id potrebbero essere espulsi dalla lista prima che un eventuale replay venga processato.

**Fix:** Aumentare il limite a 50, o usare una struttura key-value con timestamp invece di una semplice lista FIFO.

---

## 8. Performance e asset

### 8.1 `bw-checkout.css` — file troppo grande (~1100-1900 righe)

Un singolo file CSS che gestisce layout, gateway icons, skeleton loaders, responsive, stati loading, accordion, Stripe Elements overrides, e molto altro. È difficile da mantenere e viene caricato interamente anche quando alcuni componenti non sono presenti (es. checkout senza Apple Pay).

**Fix a lungo termine:** Split in moduli:
- `bw-checkout-layout.css` — grid, columns, separator
- `bw-checkout-form.css` — field styles, floating labels
- `bw-checkout-payment.css` — accordion, gateway icons, Stripe Elements
- `bw-checkout-states.css` — loading, skeleton, error states

---

### 8.2 ~~Stripe JS doppio include~~ — NON un bug

Entrambi i blocchi Google Pay e Apple Pay chiamano `wp_enqueue_script('stripe', 'https://js.stripe.com/v3/', ...)` con lo **stesso handle** `stripe`. WordPress gestisce questo correttamente: la seconda chiamata con lo stesso handle è un no-op e Stripe JS viene incluso una sola volta nel DOM.

Nessun intervento richiesto. Il pattern è idiomatico WordPress.

---

### 8.3 Supabase JS caricato da CDN senza SRI hash

`woocommerce-init.php` carica `@supabase/supabase-js@2` da `cdn.jsdelivr.net` senza Subresource Integrity (SRI) hash. Se il CDN venisse compromesso, codice malevolo verrebbe eseguito nel checkout con accesso ai dati utente.

**Fix:** Aggiungere `integrity` e `crossorigin` attribute al tag script, o self-hostare la libreria.

---

### 8.4 Google Maps API — nessun mention nelle guide

`woocommerce-init.php` include Google Maps API con una chiave API. Non documentato in nessun MD. Verificare che la chiave Maps sia:
- Ristretta per dominio (HTTP referrer restriction)
- Ristretta per API (solo Maps JavaScript API)
- Non esposta in commit history

---

## 9. Accessibilità e conformità

### 9.1 Keyboard navigation nel payment accordion — parzialmente implementata

`bw-payment-methods.js` implementa Enter, Space, Arrow keys per navigare tra i gateway. Non è chiaro se la navigazione tramite Tab rispetti correttamente l'ordine dei focus all'interno degli accordion aperti (campo carta, campo CVV, ecc.).

---

### 9.2 ✅ RISOLTO — ARIA attributes aggiunti all'accordion payment

**File:** `woocommerce/templates/checkout/payment.php`

Aggiunti:
- `id="bw-payment-heading"` sul `<h2>` del titolo sezione
- `role="radiogroup"` + `aria-labelledby="bw-payment-heading"` sul `<ul>` dei gateway
- `id="payment_method_label_<gateway_id>"` sul `<label>` di ogni gateway
- `role="button"` + `aria-expanded` + `aria-controls="bw-payment-panel-<gateway_id>"` sul `.bw-payment-method__header`
- `id="bw-payment-panel-<gateway_id>"` + `role="region"` + `aria-labelledby` su ogni panel `.bw-payment-method__content`

---

### 9.3 SCA / 3DS — documentato solo per `requires_action`, ma non per `requires_payment_method` dopo 3DS fallito

Tutti e tre i gateway gestiscono `requires_action` (redirect a Stripe auth). Ma se l'autenticazione 3DS fallisce, Stripe ritorna `requires_payment_method`. Il comportamento in questo caso deve:
1. Mostrare un messaggio chiaro all'utente
2. Permettere di riprovare con un metodo diverso
3. Non marcare l'ordine come fallito definitivamente alla prima mancata autenticazione

Verificare che il branch `requires_payment_method` nei gateway mostri un messaggio utile e non blocchi il retry.

---

## 10. Checklist di regressione pre-release

Prima di ogni deploy che modifica file checkout, gateway, o woocommerce-init.php:

### Pagamento
- [ ] Ordine Google Pay: completion via webhook (non via return URL)
- [ ] Ordine Klarna: redirect → return → webhook → thank you
- [ ] Ordine Apple Pay: availability check → payment → webhook → thank you
- [ ] Cancellazione Klarna: ritorna a checkout con notice, carrello intatto
- [ ] Cancellazione Google Pay: stato coherente, retry possibile
- [ ] Ordine free (totale zero): completato senza gateway
- [ ] Coupon valido: applicato correttamente, totale aggiornato
- [ ] Coupon invalido: errore mostrato, nessun loop

### Webhook
- [ ] Replay stesso event_id → nessun effetto doppio
- [ ] Evento per ordine con payment_method diverso → ignorato
- [ ] PI id diverso da quello in meta → ignorato

### UI
- [ ] Un solo accordion aperto alla volta
- [ ] Un solo CTA visibile alla volta (wallet button OPPURE place order)
- [ ] Stripe card label rimane "Credit / Debit Card"
- [ ] Google Pay: no "Initializing" infinito
- [ ] Apple Pay: stato available/unavailable coerente tra refresh
- [ ] Notice errori visibili e leggibili su mobile

### Accessibilità
- [ ] Tab navigation funziona nel form billing
- [ ] Errori di validazione annunciati a screen reader

---

## 11. Baseline — cosa funziona correttamente

Questi aspetti sono stati verificati nel codice e non richiedono intervento:

| Componente | Stato | Note |
|---|---|---|
| Nonce AJAX coupon | ✅ OK | `check_ajax_referer('bw-checkout-nonce', 'nonce')` presente |
| Firma webhook Stripe | ✅ OK | HMAC SHA-256, tolerance 300s, `hash_equals` |
| Anti-conflict gateway | ✅ OK | Controlla `get_payment_method()` prima di processare |
| Dedup eventi webhook | ✅ OK | Rolling list event_id, max 20 |
| PI consistency check | ✅ OK | Ignora eventi se PI id non corrisponde a meta |
| Credenziali hardcoded | ✅ OK | Nessuna — tutte da WordPress options |
| Template override sicuro | ✅ OK | `file_exists()` prima di servire |
| Debug log Google Pay | ✅ OK | Tutti guardati da `BW_GOOGLE_PAY_DEBUG &&` |
| Debug log woocommerce-init | ✅ OK | Limitato a `current_user_can('manage_options') && WP_DEBUG` |
| Refund Stripe | ✅ OK | Con idempotency key, mode-aware key selection |
| Wallet return router | ✅ OK | Controlla `is_paid()` prima di fare reset |
| Sanitizzazione input admin | ✅ OK | `sanitize_text_field`, `absint`, `sanitize_key` ovunque |
| Checkout fields admin | ✅ OK | Nonce + `current_user_can('manage_options')` |
| Subscribe Brevo — log errori | ✅ OK | Usa `wc_get_logger()` con context |

---

## Priorità di intervento — Stato aggiornato

| # | Problema | Priorità | Stato |
|---|---|---|---|
| 1 | console.log in bw-apple-pay.js (§2.1) | 🔴 CRITICO | ✅ Risolto 2026-02-24 |
| 2 | Stato ordine incoerente tra gateway (§3.1) | 🔴 CRITICO | ✅ Risolto 2026-02-24 |
| 3 | Stripe webhook config per 3 endpoint (§7.1) | 🔴 CRITICO | ✅ Risolto 2026-02-24 (doc) |
| 4 | Path obsoleto Google Pay Guide (§4.1) | 🟠 ALTO | ✅ Risolto 2026-02-24 |
| 5 | Rate limiting coupon AJAX (§2.2) | 🟠 ALTO | ✅ Risolto 2026-02-24 |
| 6 | Monitoring ordini stuck on-hold (§7.2) | 🟠 ALTO | ✅ Risolto 2026-02-24 |
| 7 | Auto-hide notice timing (§6.1) | 🟡 MEDIO | ✅ Risolto 2026-02-24 |
| 8 | ARIA accordion payment (§9.2) | 🟡 MEDIO | ✅ Risolto 2026-02-24 |
| 9 | docs/PAYMENTS.md aggiornamento (§4.2) | 🟡 MEDIO | ✅ Risolto 2026-02-24 |
| 10 | Stripe JS doppio include (§8.2) | 🟡 MEDIO | ✅ Non un bug (WordPress no-op) |
| 11 | Test mode Klarna (§5.1) | 🟠 ALTO | 🔲 Aperto |
| 12 | CSP documentazione (§2.5) | 🟡 MEDIO | 🔲 Aperto |
| 13 | Brevo retry su API failure (§5.3) | 🟡 MEDIO | 🔲 Aperto |
| 14 | SRI per Supabase CDN (§8.3) | 🟡 MEDIO | 🔲 Aperto |
| 15 | CSS split in moduli (§8.1) | 🟢 BASSO | 🔲 Aperto (bassa urgenza) |

---

## 12. Code Cleanup Backlog — vacuum audit

Audit completo eseguito il 2026-02-24 su tutti i 16 file del checkout (PHP, JS, CSS).
Nessuna modifica applicata — questo è il backlog ordinato da affrontare uno alla volta.

> Legenda: 🔲 = da fare | ✅ = fatto | 🔴 critico | 🟠 alto | 🟡 medio | 🟢 basso

---

### A. Codice duplicato

| # | File/i coinvolti | Descrizione | Priorità | Stato |
|---|---|---|---|---|
| A1 | `bw-checkout.js`, `bw-google-pay.js`, `bw-apple-pay.js`, `bw-klarna.js` | Logica `showLoader` / `hideLoader` replicata in tutti e 4 i file JS. Ogni file emette classi CSS diverse (`is-loading`, `bw-loading`, `bw-processing`). | 🟡 MEDIO | ✅ `removeLoadingState` consolidata in `bw-payment-methods.js`, esposta su `window.bwCheckout.removeLoadingState`. Google Pay e Apple Pay chiamano la funzione condivisa. |
| A2 | `bw-google-pay.js`, `bw-apple-pay.js`, `bw-klarna.js` | Pattern "prepara error notice → trova container → inserisci HTML → scroll" duplicato identicamente nei tre gateway JS. | 🟡 MEDIO | ✅ `renderCheckoutNotice` consolidata in `bw-payment-methods.js` (con `escapeHtml`), esposta su `window.bwCheckout.renderCheckoutNotice`. Rimosse le 3 definizioni locali (inclusa la doppia in Apple Pay). |
| A3 | `class-bw-google-pay-gateway.php`, `class-bw-klarna-gateway.php`, `class-bw-apple-pay-gateway.php` | Il blocco `get_stripe_key()` con selezione live/test è identico nei tre file gateway. Dovrebbe stare in `BW_Abstract_Stripe_Gateway`. | 🟠 ALTO | 🔲 |
| A4 | `woocommerce-init.php` | `bw_mew_ajax_apply_coupon()` e `bw_mew_ajax_remove_coupon()` condividono ~80% del codice (nonce check, rate limit, sessione WC, risposta JSON). Estrarre un `bw_mew_coupon_handler($action)` helper. | 🟡 MEDIO | 🔲 |
| A5 | `payment.php`, `bw-payment-methods.js` | La logica di selezione gateway attivo (quale accordion aprire) è implementata sia in PHP (con `$chosen_method`) che in JS (`bw_checkout_params.chosen_payment_method`). Il PHP determina lo stato iniziale, il JS lo gestisce dopo. Se i due non sono in sync, si ha un flash visivo al caricamento. Centralizzare. | 🟡 MEDIO | 🔲 |
| A6 | `bw-checkout.js`, `bw-checkout-notices.js` | Entrambi i file selezionano `.woocommerce-notices-wrapper` e `.woocommerce-error` per manipolare le notice. Non condividono nessuna funzione — codice parallelo indipendente. | 🟢 BASSO | 🔲 |
| A7 | `bw-checkout.css`, `bw-payment-methods.css` | Regole per `.bw-payment-method__header:hover` e `.bw-payment-method__header:focus` compaiono in entrambi i file con valori parzialmente diversi. L'ultimo file caricato vince ma la sovrapposizione crea ambiguità. | 🟢 BASSO | 🔲 |

---

### B. Codice morto / irraggiungibile

| # | File | Descrizione | Priorità | Stato |
|---|---|---|---|---|
| B1 | `bw-checkout.js` | Funzione `bwToggleEditMode()` definita ma mai chiamata nel file corrente. Verificare se è chiamata da template PHP inline; se no, rimuovere. | 🟡 MEDIO | 🔲 |
| B2 | `bw-apple-pay.js` | Branch `enableExpressFallback` implementa un path alternativo Apple Pay. Nessun template o admin panel imposta questo parametro a `true`. Se il path è inutilizzato, documentare o rimuovere. | 🟡 MEDIO | 🔲 |
| B3 | `class-bw-google-pay-gateway.php` | Il metodo `process_refund_legacy()` (o equivalente fallback pre-intents) non è raggiungibile perché `process_refund()` copre tutti i casi. Rimuovere o documentare il motivo della presenza. | 🟡 MEDIO | 🔲 |
| B4 | `woocommerce-init.php` | La funzione `bw_mew_maybe_clear_cart_on_checkout()` ha un branch `elseif` che è logicamente irraggiungibile dato il check precedente. Rivedere la condizione. | 🟢 BASSO | 🔲 |
| B5 | `bw-checkout.js` | Variabile `bwLastScrollY` definita e aggiornata ma mai letta per ripristinare la posizione scroll. Rimuovere o completare il restore. | 🟢 BASSO | 🔲 |
| B6 | `bw-checkout.css` | Selettori `.bw-checkout-v1 .col1-set` e `.bw-checkout-v1 .col2-set` — la classe `.bw-checkout-v1` non è assegnata da nessun PHP o JS nel codebase attuale. CSS lettera morta. | 🟡 MEDIO | 🔲 |
| B7 | `bw-checkout.css` | Regola `@supports (display: grid)` duplicata due volte nel file con contenuto diverso — la seconda override silenziosa la prima. Unire in un unico blocco. | 🟡 MEDIO | 🔲 |

---

### C. Nomi e pattern incoerenti

| # | File/i | Descrizione | Priorità | Stato |
|---|---|---|---|---|
| C1 | tutti i gateway JS | Le classi CSS emesse durante il loading sono: `is-loading` (Google Pay), `bw-loading` (Apple Pay), `bw-processing` (Klarna), `bw-checkout-loading` (checkout.js). Standardizzare a `bw-loading` o `is-loading`. | 🟠 ALTO | 🔲 |
| C2 | `bw-google-pay.js`, `bw-apple-pay.js` | `bwGooglePayParams` vs `bwApplePayParams` — naming diverso per struttura equivalente. Nessun impatto tecnico, ma rende il codice meno leggibile. Standardizzare a `bwGatewayParams` con campo `gateway_id`. | 🟢 BASSO | 🔲 |
| C3 | `woocommerce-init.php` | Handler coupon: `bw_mew_ajax_apply_coupon` / `bw_mew_ajax_remove_coupon` — non hanno prefisso `nopriv` nelle action hook ma gestiscono anche ospiti. Verificare se sono registrati su entrambi `wp_ajax_` e `wp_ajax_nopriv_` o solo uno. | 🟠 ALTO | 🔲 |
| C4 | `class-bw-google-pay-gateway.php` | Il meta key `_bw_gpay_pi_id` usa underscore+prefix, ma `_bw_klarna_pi_id` e `_bw_apple_pay_pi_id` usano schemi leggermente diversi. Standardizzare tutti i meta key a `_bw_{gateway}_pi_id` con gateway slug uguale all'ID WC. | 🟡 MEDIO | 🔲 |
| C5 | `payment.php` | La variabile `$gateway_id` viene usata sia come `esc_attr($gateway->id)` che direttamente come `$gateway_id` — verificare che tutte le occorrenze usino la versione escaped per evitare XSS nei gateway con id custom. | 🟠 ALTO | 🔲 |
| C6 | `bw-checkout.js` | Misto di `var`, `let`, `const` in stile jQuery/ES5 e ES6. File scritto in due momenti diversi. Non è un bug, ma rende il file difficile da leggere. Unificare gradualmente verso `const`/`let`. | 🟢 BASSO | 🔲 |
| C7 | `bw-checkout.css` | Alcune custom property sono definite su `:root`, altre su `body.woocommerce-checkout`, altre ancora inline nei selettori. Centralizzare tutte le custom property del checkout su `body.woocommerce-checkout`. | 🟢 BASSO | 🔲 |

---

### D. Event listener multipli / memory leak

| # | File | Descrizione | Priorità | Stato |
|---|---|---|---|---|
| D1 | `bw-checkout.js` | L'evento `update_checkout` di WooCommerce trigghera `bwInitFloatingLabels()` che ricollega i listener a ogni aggiornamento checkout. Se i listener non vengono prima rimossi (`.off()`) si accumulano. Verificare presenza di unbind prima del rebind. | 🟠 ALTO | 🔲 |
| D2 | `bw-payment-methods.js` | Il listener `keydown` per navigazione arrow keys è attaccato al `document` (event delegation), ma quando l'accordion cambia gateway, vecchi listener rimangono. Verificare che la navigazione keyboard non triggeri handler doppi. | 🟡 MEDIO | 🔲 |
| D3 | `bw-checkout-notices.js` | `$(document).on('updated_checkout', ...)` viene registrato ogni volta che lo script è incluso. Se il file viene incluso più volte (raro ma possibile con caching problematico), si hanno double-fire. Aggiungere un guard `if (!window.bwNoticesInit)`. | 🟡 MEDIO | 🔲 |
| D4 | `bw-google-pay.js` | Il click handler sul wallet button viene attaccato con `.on('click', ...)` dopo ogni `initGooglePay()`. Se `initGooglePay` viene chiamato più volte (es. dopo `updated_checkout`), i click handler si moltiplicano. Usare `.off('click').on('click', ...)`. | 🟠 ALTO | 🔲 |
| D5 | `bw-apple-pay.js` | Stesso problema di D4 per il session handler Apple Pay. Ogni `canMakePayment()` reinizializza il button senza fare cleanup dei listener precedenti. | 🟠 ALTO | 🔲 |
| D6 | `bw-klarna.js` | Listener sul button Klarna ricreato ad ogni cambio selezione gateway. Aggiungere `.off().on()` pattern. | 🟡 MEDIO | 🔲 |
| D7 | `bw-checkout.js` | `$(document.body).on('updated_checkout', ...)` registrato senza namespace jQuery (es. `.updated_checkout.bwCheckout`). Se altri plugin usano `trigger('updated_checkout')`, non è possibile rimuovere solo i listener BW. Aggiungere namespace. | 🟢 BASSO | 🔲 |

---

### E. CSS — conflitti e override silenziosi

| # | File/i | Descrizione | Priorità | Stato |
|---|---|---|---|---|
| E1 | `bw-checkout.css` | `.woocommerce-checkout .woocommerce-input-wrapper` ha specificità che override stili Stripe Elements. I selettori Stripe (`.StripeElement`) devono avere specificità >= per non essere schiacciati. Verificare il cascade. | 🟠 ALTO | 🔲 |
| E2 | `bw-checkout.css`, `bw-payment-methods.css` | `.bw-payment-method__content` ha `display: none` in entrambi i file, ma con `transition` definita solo in uno — al toggle JS la transizione non si applica se l'altro file carica dopo. Unificare in un unico file. | 🟡 MEDIO | 🔲 |
| E3 | `bw-checkout.css` | `body.woocommerce-checkout #order_review` ha regole di layout che entrano in conflitto con il plugin checkout fields customizer. Se il plugin aggiunge classi CSS aggiuntive al `#order_review`, il grid si rompe. | 🟡 MEDIO | 🔲 |
| E4 | `bw-checkout.css` | `@media (max-width: 767px)` e `@media (max-width: 768px)` usati in punti diversi del file per lo stesso breakpoint mobile. Standardizzare a uno solo (767px o 768px). | 🟢 BASSO | 🔲 |
| E5 | `bw-checkout.css` | Il skeleton loader animation usa `@keyframes bw-shimmer` definito due volte nel file (nella sezione skeleton e nella sezione loading states) con timing leggermente diverso. La seconda definizione vince. Unificare. | 🟡 MEDIO | 🔲 |
| E6 | `bw-checkout.css` | `.bw-checkout__separator` ha `display: none` su mobile ma è gestito anche con `visibility: hidden` in una regola più specifica — due approcci per nascondere lo stesso elemento. Scegliere uno. | 🟢 BASSO | 🔲 |
| E7 | `bw-payment-methods.css` | Gateway icons `.bw-payment-method__icon` hanno `width` fissa in pixel invece che `max-width` + `height: auto`. Su schermi HiDPI le icone SVG appaiono sfocate. | 🟢 BASSO | 🔲 |

---

### F. Modularizzazione

| # | Descrizione | Beneficio | Priorità | Stato |
|---|---|---|---|---|
| F1 | Estrarre il phone country picker da `bw-checkout.js` in `bw-checkout-phone-picker.js` separato. Il picker ha 40+ paesi, ~200 righe di codice e dati statici — è un componente autonomo non correlato al resto del checkout flow. | Manutenibilità, possibilità di riuso su altri form | 🟡 MEDIO | 🔲 |
| F2 | Estrarre `bwInitFloatingLabels()` da `bw-checkout.js` in `bw-checkout-floating-labels.js`. Il floating label system è usato anche fuori dal checkout (login, register). | Riuso + testing isolato | 🟡 MEDIO | 🔲 |
| F3 | Estrarre `bw_mew_coupon_rate_limit_check()` e la logica coupon in un file `class-bw-checkout-coupon.php` separato. Oggi è tutto in `woocommerce-init.php` che è già 900+ righe. | Leggibilità, principio single responsibility | 🟡 MEDIO | 🔲 |
| F4 | Estrarre il router return/cancel (`bw_mew_handle_wallet_return()`) in `class-bw-checkout-return-handler.php`. Logica critica per il completamento ordine sepolta a metà di woocommerce-init.php. | Separazione di concerns, testabilità | 🟠 ALTO | 🔲 |
| F5 | `get_stripe_key()` duplicato nei tre gateway → portare in `BW_Abstract_Stripe_Gateway` con override per Apple Pay fallback (che usa chiavi Google Pay come fallback). | DRY, manutenzione centralizzata | 🟠 ALTO | 🔲 |
| F6 | Creare `bw-checkout-utils.js` con le funzioni condivise tra i 4 file JS: `showLoader`, `hideLoader`, `showError`, `clearErrors`, `scrollToNotice`. Importato come primo script, gli altri lo usano come dipendenza. | Elimina duplicati A1+A2 | 🟡 MEDIO | 🔲 |
| F7 | Split `bw-checkout.css` nei 4 moduli descritti in §8.1 (`-layout`, `-form`, `-payment`, `-states`). Caricamento condizionale dove possibile. | Performance + manutenibilità | 🟢 BASSO | 🔲 |

---

### G. Variabili inutilizzate e cleanup minori

| # | File | Descrizione | Priorità | Stato |
|---|---|---|---|---|
| G1 | `bw-checkout.js` | `var bwLastScrollY = 0` — mai letta per restore (vedi B5). Rimuovere o completare l'implementazione. | 🟢 BASSO | 🔲 |
| G2 | `bw-google-pay.js` | `var gpayRetryCount = 0` definita nell'outer scope ma il retry non è implementato (il codice dentro il catch non incrementa il counter). Rimuovere o implementare. | 🟡 MEDIO | 🔲 |
| G3 | `woocommerce-init.php` | `$bw_checkout_settings` viene assegnata e poi ri-assegnata con `bw_mew_get_checkout_settings()` due righe dopo. Prima assegnazione è dead code. | 🟢 BASSO | 🔲 |
| G4 | `class-bw-apple-pay-gateway.php` | `private $statement_descriptor` definito nel costruttore ma non usato nel metodo `create_payment_intent()` — passato come parametro separato invece. Verificare se è legacy. | 🟢 BASSO | 🔲 |

---

### Riepilogo cleanup backlog

| Categoria | Items totali | Alta/Critica priorità | Bassa priorità |
|---|---|---|---|
| A — Codice duplicato | 7 | 1 | 2 |
| B — Codice morto | 7 | 2 | 3 |
| C — Naming incoerente | 7 | 3 | 3 |
| D — Event listener multipli | 7 | 3 | 1 |
| E — CSS conflitti | 7 | 1 | 4 |
| F — Modularizzazione | 7 | 2 | 1 |
| G — Variabili inutilizzate | 4 | 0 | 3 |
| **Totale** | **46** | **12** | **17** |

> **Ordine consigliato di attacco:** C3 (hook nopriv) → D1/D4/D5 (event listener leak) → A3/F5 (get_stripe_key in abstract) → C5 (XSS gate ID) → F4 (return handler) → poi tutto il resto in ordine di priorità.
