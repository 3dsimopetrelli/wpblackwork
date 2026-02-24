# Checkout Maintenance Guide
**Analisi completa — Sicurezza, Stabilità, Efficienza**
Data: 2026-02-24

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

### 2.1 `console.log` non protetti in produzione — CRITICO

**File:** `assets/js/bw-apple-pay.js`

Due istruzioni di log senza guard di debug:

```js
// RIGA ~356 — NESSUN GUARD
console.log('[BW Apple Pay] canMakePayment:', result);

// RIGA ~508 — protetta solo da adminDebug, NON da BW_APPLE_PAY_DEBUG
console.info('[BW Wallet Debug][apple_pay_cancel]', { ... });
```

**Rischio:** In produzione espone al browser informazioni interne sul risultato di `canMakePayment` (struttura oggetto Stripe, dati wallet) e sugli eventi cancel. Un attaccante che ispeziona la console vede il flow interno.

**Fix:** Aggiungere il guard `BW_APPLE_PAY_DEBUG &&` davanti a entrambe, come già fatto correttamente per tutti gli altri log del file e per l'intero `bw-google-pay.js`.

```js
// Corretto:
BW_APPLE_PAY_DEBUG && console.log('[BW Apple Pay] canMakePayment:', result);
BW_APPLE_PAY_DEBUG && console.info('[BW Wallet Debug][apple_pay_cancel]', { ... });
```

---

### 2.2 Mancanza di rate limiting sugli AJAX handler — ALTO

**File:** `woocommerce/woocommerce-init.php`

Gli handler AJAX `bw_apply_coupon` e `bw_remove_coupon` accettano richieste da utenti non autenticati (`wp_ajax_nopriv_*`) con solo verifica nonce, senza limitazione di frequenza.

**Rischio:** Un attore malevolo può eseguire brute-force di codici coupon in automazione, verificando quali codici sono validi. Il nonce di sessione non cambia abbastanza frequentemente da prevenire attacchi rapidi all'interno della stessa sessione.

**Fix da valutare:**
- Aggiungere transient-based rate limiting per IP: max N tentativi coupon per finestra temporale (es. 10 tentativi / 5 minuti / IP)
- Considerare `WP_Hook` throttle o soluzione server-level (Nginx/Cloudflare rate limit su `/wp-admin/admin-ajax.php`)

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

### 3.1 Stato status: Apple Pay usa `on-hold`, Google Pay e Klarna usano `pending` — INCOERENZA

**Verificato nel codice:**
- Google Pay `process_payment()`: PaymentIntent `succeeded` → `$order->update_status('pending')`
- Klarna `process_payment()`: PaymentIntent `succeeded` → `$order->update_status('pending')`
- Apple Pay `process_payment()`: PaymentIntent `succeeded` → `$order->update_status('on-hold')`

**Problema:** I tre gateway trattano lo stesso caso (PI creato con successo, in attesa di conferma webhook) con stati WooCommerce diversi. Questo causa:
- Dashboard admin con stati misti per ordini in attesa
- Regole di automazione (email, fulfillment) che si attivano in modo diverso tra gateway
- I filtri `woocommerce_order_status_processing` e `woocommerce_order_status_completed` usati da Brevo subscribe si comportano diversamente

**Fix:** Standardizzare a `on-hold` per tutti e tre i gateway nel caso PI created/awaiting webhook. `on-hold` è la semantica WooCommerce corretta per "pagamento avviato, in attesa di conferma esterna". `pending` significa invece "ordine creato ma pagamento non ancora tentato".

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

### 4.1 `Gateway Google Pay Guide.md` — path file obsoleto — CRITICO per manutenzione

Il file cita:
```
includes/woocommerce-overrides/class-bw-google-pay-gateway.php
```

Il file reale è in:
```
includes/Gateways/class-bw-google-pay-gateway.php
```

**Impatto:** Chiunque usi la documentazione per trovare il file del gateway Google Pay cerca nella directory sbagliata. Tutti i path nel Google Pay Guide sono da aggiornare.

---

### 4.2 `docs/PAYMENTS.md` — documenta Klarna e Apple Pay come "futuri" ma sono già implementati

Il file menziona "instructions for adding new gateways (Klarna, Apple Pay)" come lavoro futuro. Entrambi sono implementati e in produzione da tempo.

**Fix:** Aggiornare `docs/PAYMENTS.md` per riflettere lo stato attuale: tre gateway custom attivi, con base astratta condivisa, e rimuovere le sezioni "planned".

---

### 4.3 `Gateway Apple Pay Guide.md` — debug hook documentato come "presente" ma da rimuovere

La sezione "Debug hook currently present" documenta:
```
console.log('[BW Apple Pay] canMakePayment:', result);
```
come fatto intenzionale. Questo ha portato a non rimuoverlo. Il file dovrebbe invece segnalarlo come **da rimuovere** (vedi §2.1).

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

### 6.1 Auto-hide notice dopo 3 secondi — aggressivo per accessibilità

**File:** `assets/js/bw-checkout-notices.js`

I messaggi di errore sui campi required vengono nascosti automaticamente dopo 3 secondi. Per utenti con disabilità cognitive o che leggono lentamente, questo non è sufficiente. WCAG 2.1 (guideline 2.2.1) richiede che i timeout siano configurabili o disabilitabili.

**Fix:** Aumentare a 6-8 secondi o non nascondere automaticamente gli errori di validazione (tenerli fino a quando l'utente corregge il campo).

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

### 7.1 Configurazione Stripe webhook non documentata per tre endpoint separati

Il sistema ha tre endpoint webhook distinti:
```
/?wc-api=bw_google_pay
/?wc-api=bw_klarna
/?wc-api=bw_apple_pay
```

Se tutti e tre i gateway usano lo stesso account Stripe, occorre configurare **tre webhook separati** nel dashboard Stripe, ognuno puntando al proprio endpoint. In alternativa, un singolo webhook con routing interno.

**Non documentato in nessun MD:** quale configurazione Stripe è quella corretta. Se viene configurato un solo webhook, gli altri due gateway non riceveranno mai gli eventi.

**Fix:** Aggiungere a `Gateway Total Guide.md` una sezione "Stripe Webhook Configuration" che specifica esattamente quanti webhook configurare e su quali URL.

---

### 7.2 Nessun monitoring/alerting per webhook falliti

Se Stripe non riesce a consegnare un webhook (sito down, timeout), riprova con backoff esponenziale per 72h, poi abbandona. Un ordine rimane a `pending`/`on-hold` indefinitamente senza che nessuno venga avvisato.

**Fix consigliato:**
- Aggiungere un cron job WP che controlla ordini `pending`/`on-hold` con `_bw_*_pi_id` settato da più di X ore e non `is_paid()`, e invia notifica admin
- Oppure configurare Stripe webhook retry alerts nel dashboard Stripe

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

### 8.2 Stripe JS caricato due volte se entrambi Apple Pay e Google Pay sono attivi

**File:** `woocommerce/woocommerce-init.php`

Sia il blocco Google Pay (riga ~643) che il blocco Apple Pay (riga ~679) registrano `https://js.stripe.com/v3/` con lo stesso handle o handle diversi. Se entrambi i gateway sono abilitati, verificare che Stripe JS non venga incluso due volte nel DOM.

**Fix:** Usare un unico handle `stripe-v3` registrato una volta sola, e dipendere da esso in entrambi i blocchi.

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

### 9.2 ARIA attributes sull'accordion payment — non documentati

Il template `payment.php` usa HTML custom per l'accordion. Verificare presenza di:
- `aria-expanded` sul trigger
- `aria-controls` sul trigger → `id` sul panel
- `role="radiogroup"` sul container dei gateway

Assenza di questi attributi rende il checkout inaccessibile a screen reader.

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

## Priorità di intervento

| # | Problema | Priorità | Effort |
|---|---|---|---|
| 1 | console.log in bw-apple-pay.js (§2.1) | 🔴 CRITICO | Basso |
| 2 | Stato ordine incoerente tra gateway (§3.1) | 🔴 CRITICO | Medio |
| 3 | Stripe webhook config per 3 endpoint (§7.1) | 🔴 CRITICO | Basso (doc) |
| 4 | Path obsoleto Google Pay Guide (§4.1) | 🟠 ALTO | Basso |
| 5 | Rate limiting coupon AJAX (§2.2) | 🟠 ALTO | Medio |
| 6 | Monitoring ordini stuck on-hold (§7.2) | 🟠 ALTO | Medio |
| 7 | Test mode Klarna (§5.1) | 🟠 ALTO | Alto |
| 8 | CSP documentazione (§2.5) | 🟡 MEDIO | Basso |
| 9 | docs/PAYMENTS.md aggiornamento (§4.2) | 🟡 MEDIO | Basso |
| 10 | Brevo retry su API failure (§5.3) | 🟡 MEDIO | Medio |
| 11 | Auto-hide notice timing (§6.1) | 🟡 MEDIO | Basso |
| 12 | Stripe JS doppio include (§8.2) | 🟡 MEDIO | Basso |
| 13 | SRI per Supabase CDN (§8.3) | 🟡 MEDIO | Basso |
| 14 | ARIA accordion payment (§9.2) | 🟡 MEDIO | Medio |
| 15 | CSS split in moduli (§8.1) | 🟢 BASSO | Alto |
