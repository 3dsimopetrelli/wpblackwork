# Mail Marketing -- QA Checklist (Verification Report)

Blackwork Site Plugin  
Brevo Integration -- Checkout, Elementor Widget & Admin Refactor

Data verifica: 2026-02-24

---

## Pre-flight

- [ ] Backup database (or export relevant options)
- [ ] Enable WP_DEBUG_LOG (staging/local recommended)
- [ ] Confirm valid Brevo API key
- [ ] Ensure test list exists in Brevo

Esito: ⚠️ Partially implemented

Note QA:
- Questi sono prerequisiti operativi/manuali e non sono enforceati dal codice plugin.

---

## 1. Admin UI -- Structure

- [x] New submenu exists: **Blackwork Site → Mail Marketing**
- [x] Tabs visible: **General** and **Checkout**
- [x] Old page "Checkout → Subscribe":
  - [ ] Removed OR
  - [x] Displays notice + redirect/link to new page
- [x] No duplicated configuration pages exist

Esito: ⚠️ Partially implemented

Dettaglio:
- Submenu nuovo implementato (`add_submenu_page(... blackwork-mail-marketing ...)`).
- `General`/`Checkout` visibili nella nuova pagina.
- Vecchio `Checkout > Subscribe` non salva più impostazioni: mostra solo notice+link.
- Il route legacy `checkout_tab=subscribe` è ancora raggiungibile (nascosto dalla nav, ma ammesso dal controllo tab). Non crea divergenza perché non contiene più form di salvataggio.

Cosa manca (strict):
- Se si vuole “hard remove”, eliminare `subscribe` da `$allowed_checkout_tabs`.

---

## 2. Settings Migration

- [x] Previous values migrated automatically
- [x] New options exist in DB:
  - `bw_mail_marketing_general_settings`
  - `bw_mail_marketing_checkout_settings`
- [x] Changing values persists correctly after refresh (code path)
- [ ] Old option `bw_checkout_subscribe_settings` not used as primary source

Esito: ⚠️ Partially implemented

Dettaglio:
- Migrazione automatica presente su `admin_init` (`maybe_migrate_legacy_settings`).
- Mapping split corretto:
  - API/list/DOI → general settings
  - enabled/default_checked/label/privacy/timing → checkout settings
- Salvataggio nuove opzioni con `update_option(...)` implementato.
- Fallback legacy ancora presente (`get_general_settings()` / `get_checkout_settings()` leggono old option se nuove vuote).

Cosa manca (strict):
- “Single source of truth assoluta”: rimuovere fallback legacy dopo migrazione consolidata (o introdurre flag `migration_completed`).

---

## 3. General Tab -- Brevo Connection

### Test Connection

- [x] Button works with nonce & capability check (code path)
- [x] Invalid API key shows proper error
- [x] Valid API key returns success message
- [ ] No PHP warnings or JS errors (runtime browser test not eseguito in questa review)

### API Base URL

- [x] Not editable (hardcoded/readonly)

### List Selector

- [x] Dropdown loads lists (if API works)
- [x] Selected list saves correctly

Esito: ⚠️ Partially implemented

Dettaglio:
- AJAX protetto con `current_user_can('manage_options')` + `check_ajax_referer(...)`.
- Se API non disponibile, fallback a input numerico list ID implementato.
- Non effettuato test runtime browser/console in questa passata (solo code-trace).

---

## 4. Checkout Tab -- UI & Behavior

- [x] Enable newsletter checkbox toggle works
- [x] Default checked behaves correctly
- [x] Label and privacy text render properly
- [x] Subscribe timing select exists (paid / created)
- [ ] Placement compatible with Checkout Fields

Esito: ⚠️ Partially implemented

Dettaglio:
- Campi checkout tab presenti e persistiti.
- Frontend usa settings nuovi (con fallback) per inject newsletter field.
- Gap reale: il template `form-billing.php` forza rendering `billing_email` + `bw_subscribe_newsletter` in testa, quindi `placement_after_key`/`priority_offset` non hanno effetto pieno in questa architettura.

Cosa manca:
- Rimuovere hardcoded priority rendering dal template billing oppure adattarlo a rispettare `priority` del field.

---

## 5. Checkout -- Consent Save

### Checkbox NOT checked

- [x] `_bw_subscribe_newsletter` = 0
- [x] `_bw_brevo_subscribed` = skipped
- [x] No Brevo API call made

### Checkbox checked

- [x] `_bw_subscribe_newsletter` = 1
- [x] `_bw_subscribe_consent_at` saved
- [x] `_bw_subscribe_consent_source` = checkout
- [ ] `_bw_brevo_subscribed` = pending (if timing=paid)

Esito: ⚠️ Partially implemented

Dettaglio:
- OFF path coerente: stato `skipped`, niente chiamata API.
- ON path salva correttamente consenso/meta.
- Gap: `pending` non viene impostato subito su submit quando `timing=paid` (single opt-in). `pending` è usato per DOI request inviata.

Cosa manca:
- Se richiesto dal criterio QA, impostare `pending` già in fase submit quando timing=paid e opt-in=1.

---

## 6. Paid Trigger

- [x] On order status processing/completed:
  - [x] Contact added to Brevo list
  - [x] `_bw_brevo_subscribed` updated to subscribed
  - [x] Log written under `bw-brevo`

Esito: ✅ Fully implemented

Dettaglio:
- Hook presenti su `woocommerce_order_status_processing` e `woocommerce_order_status_completed`.
- Single opt-in usa `upsert_contact(...)`.
- Stato `subscribed` scritto su successo.
- Logging con `source => bw-brevo` + `order_id/email/context/result`.

---

## 7. Double Opt-in (If Enabled)

- [x] DOI email sent (code path)
- [x] Redirect works (redirect URL passed to API)
- [ ] Contact appears in list after confirmation (richiede test live)

Esito: ⚠️ Partially implemented

Dettaglio:
- Chiamata DOI implementata (`send_double_opt_in(...)`) con template, redirect, list, sender.
- Verifica “appears after confirmation” non dimostrabile solo da code review.

---

## 8. Duplicate Email Handling

- [x] No duplicate contacts created (upsert semantics)
- [x] Status logged correctly

Esito: ✅ Fully implemented (code-level)

Dettaglio:
- `upsert_contact` usa `updateEnabled=true`, quindi update contatto esistente invece di creare duplicati.
- Stati/log aggiornati coerentemente su successo/errore.

---

## 9. Unsubscribed / Blocklisted Handling

- [x] No automatic resubscribe (for blocklisted branch)
- [x] Status marked skipped
- [x] Log contains correct reason

Esito: ⚠️ Partially implemented

Dettaglio:
- Controllo pre-subscribe via `get_contact()` e skip se `emailBlacklisted`.
- Stato `skipped` + log reason presenti.
- Gap strict: la copertura “unsubscribed” dipende da come Brevo espone lo stato; codice oggi verifica esplicitamente `emailBlacklisted`.

Cosa manca:
- Estendere guardia con tutti i flag/stati Brevo rilevanti per opt-out/unsubscribed, non solo `emailBlacklisted`.

---

## 10. Security & Validation

- [x] AJAX protected with nonce
- [x] Capability checks in place
- [x] Inputs sanitized properly

Esito: ✅ Fully implemented

Dettaglio:
- AJAX test protetto con capability + nonce.
- Sanitizzazione coerente su campi general/checkout.

---

## 11. Regression Checks

- [ ] Checkout still completes normally (non eseguito runtime)
- [ ] No JS errors on frontend (non eseguito browser runtime)
- [ ] No impact on Supabase login flow (non eseguito e2e)
- [ ] No impact on cart popup (non eseguito e2e)

Esito: ⚠️ Partially implemented

Dettaglio:
- Code diff non mostra modifiche dirette ai moduli Supabase/cart popup.
- Mancano test e2e/manuali runtime per chiudere il punto con evidenza.

---

## Quick Smoke Test (2-Minute Check)

1. Test connection works
2. Place guest order with opt-in
3. Set order to paid
4. Contact appears in Brevo list
5. Log entry created

Esito: ❌ Missing execution evidence

Dettaglio:
- Checklist smoke non eseguita live in questa review (solo code verification rigorosa).

---

## Requested Focus Checks (Strict)

- [x] Migration legacy → new options: implementata
- [ ] New options as only source of truth: non al 100% (fallback legacy presente)
- [x] Old Checkout→Subscribe page no longer saves data
- [x] No duplicate editable subscribe config page
- [x] AJAX test protected (nonce + capability)
- [x] Paid timing hook wired and active
- [x] `_bw_brevo_subscribed` state machine implemented (`subscribed|pending|skipped|error`)
- [x] Logs written with source `bw-brevo`
- [ ] Unsubscribed/blocklisted never auto-resubscribed in all Brevo semantics (partial coverage: `emailBlacklisted`)

---

## Addendum -- Elementor Subscription Widget (Verification Report)

Data verifica addendum: 2026-03-17

## 12. Mail Marketing -> Subscription Admin Surface

- [x] Tab exists: **Blackwork Site -> Mail Marketing -> Subscription**
- [x] Channel enable toggle exists
- [x] Source key field exists
- [x] List mode exists (`inherit` / `custom`)
- [x] Custom list selector or numeric fallback exists
- [x] Channel opt-in mode exists
- [x] Widget copy fields exist for fixed-design widget baseline
- [x] Privacy policy URL override exists

Esito: ✅ Fully implemented

Dettaglio:
- Subscription tab is a first-class Mail Marketing authority surface.
- The list selector follows the same Brevo list-loading pattern as General, with numeric fallback if API lookup fails.
- Fixed-design widget copy is centrally governed here rather than per-instance style controls.

## 13. Elementor Widget -- Render Contract

- [x] Widget registered in Elementor widget loader
- [x] Widget slug is stable: `bw-newsletter-subscription`
- [x] Fixed frontend structure exists:
  - optional Name field
  - Email field
  - privacy checkbox
  - submit button
  - message live region
- [x] Minimal editor controls exist:
  - show name field
  - consent text override
  - name float label override
  - email float label override
- [x] Widget remains visible in editor when channel is disabled (preview notice)

Esito: ✅ Fully implemented

## 14. Frontend Validation & UX States

- [x] Empty email blocked client-side
- [x] Invalid email blocked client-side
- [x] Missing consent blocked client-side when required
- [x] Submit button enters loading/busy state
- [x] Double-submit is prevented
- [x] Safe generic failure shown on network/API issues
- [x] Success message shown on successful submit
- [x] Already-subscribed case handled explicitly
- [x] Form reset behavior is deterministic

Esito: ✅ Fully implemented

Dettaglio:
- JS runtime validates before request and mirrors server-side codes.
- Button is disabled while request is running.
- Already-subscribed success does not unnecessarily reset the form.

## 15. Backend Validation & Security

- [x] Nonce enforced on public submit endpoint
- [x] Server-side email normalization implemented
- [x] Empty email rejected server-side
- [x] Invalid email rejected server-side
- [x] Missing consent rejected server-side
- [x] Structured JSON response shape implemented
- [x] Raw Brevo/provider errors not exposed to users
- [x] Brevo API key remains server-side only
- [x] Rate limiting/cooldown implemented

Esito: ✅ Fully implemented

Dettaglio:
- Cooldown uses transient-based throttling keyed by normalized email + request IP hash.
- Stable public response codes:
  - `empty_email`
  - `invalid_email`
  - `missing_consent`
  - `rate_limited`
  - `already_subscribed`
  - `success`
  - `generic_failure`

## 16. Asset Loading -- Custom Footer Runtime

- [x] Widget CSS/JS register centrally
- [x] Widget CSS/JS enqueue from widget render
- [x] Custom footer pre-enqueue exists to avoid late-style misses
- [ ] Browser-level verification executed for every footer permutation

Esito: ⚠️ Partially implemented

Dettaglio:
- Runtime fix exists: when the active custom footer contains `bw-newsletter-subscription`, assets are enqueued during `wp_enqueue_scripts`.
- This closes the previously observed unstyled-footer problem.
- Strict browser coverage across all Theme Builder Lite footer combinations was not executed in this verification pass.

## 17. Accessibility Essentials

- [x] Label/input associations are present
- [x] `aria-describedby` applied to email and consent controls
- [x] `aria-invalid` toggled on validation errors
- [x] `aria-disabled` applied during busy state
- [x] Live region exists for status messages
- [x] No-JS path shows explicit notice

Esito: ✅ Fully implemented

## 18. Overall Widget Hardening Status

- [x] Public write path is GDPR-gated
- [x] Public write path is rate-limited
- [x] Public write path is deterministic
- [x] Public write path keeps provider detail server-side
- [x] Code-level hardening / cleanup / staged CSS cleanup complete
- [ ] Validate `already_subscribed` behavior after conditional pre-lookup optimization
- [ ] Validate required Brevo audit attributes against the real production Brevo schema
- [ ] Full live E2E verification with Brevo on staging/local documented here

Esito: ⚠️ Almost ready

Dettaglio:
- Static/code-level hardening is complete.
- Remaining gap is final manual validation, not missing implementation.

---

## Mental Flow Simulation

### Guest checkout with opt-in OFF
- `_bw_subscribe_newsletter=0`
- `_bw_brevo_subscribed=skipped`
- Paid hook exits before API subscribe

Esito: ✅

### Guest checkout with opt-in ON
- Consenso/meta salvati
- Se timing=paid: subscribe in fase status change
- `pending` solo su DOI request inviata

Esito: ⚠️ (pending semantics non allineata al punto checklist “pending on submit for paid”)

### Order moves to processing
- Hook paid attivo, subscribe path eseguito

Esito: ✅

### Email already exists in Brevo
- `upsert_contact` update, non duplica

Esito: ✅

### Email unsubscribed in Brevo
- Skip garantito se contatto risulta `emailBlacklisted`
- Copertura non totale per eventuali altri stati opt-out

Esito: ⚠️

---

## Production Gate Summary

Stato complessivo: ⚠️ NOT READY FOR STRICT PRODUCTION PUSH (senza chiudere i gap sotto)

Gap bloccanti consigliati:
1. Rendere effettivo il placement checkout (oggi parzialmente bypassato dal template billing hardcoded).
2. Decidere e uniformare semantica `pending` per timing=paid al submit.
3. Completare guardia no-resubscribe per tutti gli stati Brevo opt-out rilevanti.
4. Eseguire smoke/e2e reali (admin + checkout + paid + Brevo) con evidenza log.
