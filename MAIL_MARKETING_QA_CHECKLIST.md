<<<<<<< HEAD
# Mail Marketing -- QA Checklist (Verification Report)

Blackwork Site Plugin  
Brevo Integration -- Checkout & Admin Refactor

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
=======
# Mail Marketing -- QA Checklist

Blackwork Site Plugin\
Brevo Integration -- Checkout & Admin Refactor

------------------------------------------------------------------------

## Pre-flight

-   [ ] Backup database (or export relevant options)
-   [ ] Enable WP_DEBUG_LOG (staging/local recommended)
-   [ ] Confirm valid Brevo API key
-   [ ] Ensure test list exists in Brevo

------------------------------------------------------------------------

## 1. Admin UI -- Structure

-   [ ] New submenu exists: **Blackwork Site → Mail Marketing**
-   [ ] Tabs visible: **General** and **Checkout**
-   [ ] Old page "Checkout → Subscribe":
    -   [ ] Removed OR
    -   [ ] Displays notice + redirect to new page
-   [ ] No duplicated configuration pages exist

------------------------------------------------------------------------

## 2. Settings Migration

-   [ ] Previous values migrated automatically
-   [ ] New options exist in DB:
    -   `bw_mail_marketing_general_settings`
    -   `bw_mail_marketing_checkout_settings`
-   [ ] Changing values persists correctly after refresh
-   [ ] Old option `bw_checkout_subscribe_settings` not used as primary
    source

------------------------------------------------------------------------
>>>>>>> c4ab51a159e694d64e63aed86345d420199118c9

## 3. General Tab -- Brevo Connection

### Test Connection

<<<<<<< HEAD
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
=======
-   [ ] Button works with nonce & capability check
-   [ ] Invalid API key shows proper error
-   [ ] Valid API key returns success message
-   [ ] No PHP warnings or JS errors

### API Base URL

-   [ ] Not editable (hardcoded or readonly)

### List Selector

-   [ ] Dropdown loads lists (if implemented)
-   [ ] Selected list saves correctly

------------------------------------------------------------------------

## 4. Checkout Tab -- UI & Behavior

-   [ ] Enable newsletter checkbox toggle works
-   [ ] Default checked behaves correctly
-   [ ] Label and privacy text render properly
-   [ ] Subscribe timing select exists (paid / created)
-   [ ] Placement compatible with Checkout Fields

------------------------------------------------------------------------
>>>>>>> c4ab51a159e694d64e63aed86345d420199118c9

## 5. Checkout -- Consent Save

### Checkbox NOT checked

<<<<<<< HEAD
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
=======
-   [ ] `_bw_subscribe_newsletter` = 0 or absent
-   [ ] `_bw_brevo_subscribed` = skipped or empty
-   [ ] No Brevo API call made

### Checkbox checked

-   [ ] `_bw_subscribe_newsletter` = 1
-   [ ] `_bw_subscribe_consent_at` saved
-   [ ] `_bw_subscribe_consent_source` correct
-   [ ] `_bw_brevo_subscribed` = pending (if timing=paid)

------------------------------------------------------------------------

## 6. Paid Trigger

-   [ ] On order status processing/completed:
    -   [ ] Contact added to Brevo list
    -   [ ] `_bw_brevo_subscribed` updated to subscribed
    -   [ ] Log written under `bw-brevo`

------------------------------------------------------------------------

## 7. Double Opt-in (If Enabled)

-   [ ] DOI email sent
-   [ ] Redirect works
-   [ ] Contact appears in list after confirmation

------------------------------------------------------------------------

## 8. Duplicate Email Handling

-   [ ] No duplicate contacts created
-   [ ] Status logged correctly

------------------------------------------------------------------------

## 9. Unsubscribed / Blocklisted Handling

-   [ ] No automatic resubscribe
-   [ ] Status marked skipped
-   [ ] Log contains correct reason

------------------------------------------------------------------------

## 10. Security & Validation

-   [ ] AJAX protected with nonce
-   [ ] Capability checks in place
-   [ ] Inputs sanitized properly

------------------------------------------------------------------------

## 11. Regression Checks

-   [ ] Checkout still completes normally
-   [ ] No JS errors on frontend
-   [ ] No impact on Supabase login flow
-   [ ] No impact on cart popup

------------------------------------------------------------------------

## Quick Smoke Test (2-Minute Check)

1.  Test connection works ✅
2.  Place guest order with opt-in ✅
3.  Set order to paid ✅
4.  Contact appears in Brevo list ✅
5.  Log entry created ✅

------------------------------------------------------------------------

End of Checklist
>>>>>>> c4ab51a159e694d64e63aed86345d420199118c9
