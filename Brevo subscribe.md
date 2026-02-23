# Brevo Subscribe

## Obiettivo
Centralizzare e standardizzare l'iscrizione newsletter Brevo in tutto il sito Blackwork, mantenendo coerenza tra:
- Panel di controllo (Blackwork Site > Checkout > Subscribe)
- Frontend checkout
- Altre aree sito (es. Coming Soon, My Account, footer, popup, form custom)

## Stato attuale (esame struttura)

### 1) Panel di controllo principale: Blackwork Site
Percorso: `WP Admin > Blackwork Site > Checkout`

Sotto-tab attuali checkout:
- `style`
- `supabase`
- `fields`
- `subscribe`
- `google-maps`
- `google-pay`
- `klarna-pay`
- `apple-pay`
- `footer`

Il tab `subscribe` usa il modulo dedicato `BW_Checkout_Subscribe_Admin`.

### 2) Subscribe (admin) - campi già creati
Opzione salvata: `bw_checkout_subscribe_settings`

Campi configurabili nel tab Subscribe:
- `enabled` (checkbox): abilita checkbox newsletter al checkout
- `default_checked` (checkbox): pre-selezione checkbox
- `label_text` (text): etichetta checkbox
- `privacy_text` (textarea): testo privacy sotto checkbox
- `api_key` (password): chiave API Brevo
- `api_base` (url): base URL API (default `https://api.brevo.com/v3`)
- `list_id` (number): audience/list Brevo
- `double_optin_enabled` (checkbox): abilita double opt-in
- `double_optin_template_id` (number): template ID double opt-in
- `double_optin_redirect_url` (url): redirect post conferma
- `sender_name` (text): nome mittente
- `sender_email` (email): email mittente
- `subscribe_timing` (select): `created` oppure `paid`

Azioni admin presenti:
- Salvataggio impostazioni con nonce `bw_checkout_subscribe_save`
- Test connessione AJAX: action `bw_brevo_test_connection`

### 3) Checkout frontend - campi e processo attuale
Classe: `BW_Checkout_Subscribe_Frontend`

Campo checkout iniettato:
- key: `bw_subscribe_newsletter`
- tipo: checkbox
- sezione: `billing`
- posizione: subito dopo `billing_email` (priority +5)

Meta ordine salvati:
- `_bw_subscribe_newsletter` (0/1)
- `_bw_subscribe_consent_at` (timestamp)
- `_bw_subscribe_consent_source` (attualmente `checkout`)
- `_bw_brevo_subscribed` (`pending` oppure `1`)

Timing invio a Brevo:
- `created`: su `woocommerce_checkout_order_processed`
- `paid`: su `woocommerce_order_status_processing` + `woocommerce_order_status_completed`

Log errori:
- Woo logger source `bw-brevo`

Note implementative:
- Applicazione solo su checkout classico (non block checkout)
- Template checkout billing prioritizza rendering `billing_email` + `bw_subscribe_newsletter`

### 4) Checkout Fields (admin) - quadro campi
Tab `Checkout Fields` gestisce configurazione dinamica di tutti i campi WooCommerce classici (billing, shipping, order, account) con:
- `enabled`
- `required`
- `priority`
- `width` (`full`/`half`)
- `label` custom

Opzione: `bw_checkout_fields_settings`

Questo è importante perché i nuovi ingressi newsletter in checkout devono restare compatibili con questa gestione campi e con l'ordine visuale dei campi.

### 5) Brevo client condiviso
Classe: `BW_Brevo_Client`

Funzioni disponibili:
- `get_account()` (test connessione)
- `upsert_contact($email, $attributes, $list_ids)`
- `send_double_opt_in($email, $template_id, $redirect_url, $list_ids, $attributes, $sender)`

Questo client è il punto corretto da riusare per tutte le future iscrizioni newsletter nel sito.

### 6) Altra iscrizione newsletter già presente fuori checkout
Area `BW Coming Soon`:
- form con campi: `bw_email`, `bw_privacy`, submit `bw_subscribe`
- handler: `bw_handle_subscription()`
- chiamata Brevo diretta con API key hardcoded e listId fisso

Stato: funziona ma è separato dalla strategia centralizzata checkout/admin.

## Gap attuali da risolvere nella nuova strategia
- Configurazione Brevo frammentata (checkout centralizzato, coming soon no)
- Campi consenso non uniformi in tutte le entry point
- Mancanza di una "fonte unica" per regole subscribe (lista, DOI, attributi, source)
- Tracciamento sorgente iscritto incompleto fuori checkout
- Rischio inconsistenza privacy/compliance tra form diversi

## Strategia target (roadmap)

### Fase 1 - Unificazione configurazione
- Riusare `bw_checkout_subscribe_settings` come base globale Brevo
- Aggiungere eventuale sezione "Global Subscribe" se serve distinguere per canale
- Eliminare chiavi hardcoded dai form secondari (Coming Soon incluso)

### Fase 2 - Servizio unico subscribe
- Introdurre un service centralizzato (wrapper su `BW_Brevo_Client`) per:
  - validazione input
  - deduplica chiamate
  - gestione DOI/single opt-in
  - mappatura attributi
  - logging standard
- Esporre metodo unico tipo `subscribe_contact($email, $context, $consent, $attributes)`

### Fase 3 - Standard campi/form nei vari punti sito
Per ogni nuovo punto newsletter nel sito, definire standard minimo:
- `email` (required)
- `consenso privacy/marketing` esplicito (required dove necessario)
- eventuale `first_name/last_name` opzionali
- `source` tecnico obbligatorio (es. `checkout`, `coming_soon`, `footer`, `my_account`, `popup_cart`, ecc.)

### Fase 4 - Tracciamento e audit
- Salvare metadati uniformi (DB o order/user meta a seconda contesto)
- Tracciare almeno:
  - source
  - timestamp consenso
  - esito (`pending`, `subscribed`, `error`)
  - eventuale errore Brevo
- Report log centralizzato `bw-brevo`

### Fase 5 - Rollout progressivo
Ordine suggerito:
1. Coming Soon (migrazione da hardcoded)
2. Footer newsletter globale
3. My Account opt-in
4. Eventuali popup/landing dedicate
5. Hook post-registrazione account

## Regole tecniche da mantenere
- Compatibilità checkout classico vs checkout block
- Nessun invio a Brevo senza consenso esplicito dove richiesto
- Sanitizzazione/validazione su tutti i nuovi endpoint
- Non duplicare iscrizioni già confermate
- Uniformare UX testo privacy e messaggi di successo/errore

## Prossimo step operativo
Quando iniziamo l'implementazione, useremo questo file come documento master e apriremo sezioni incrementali per:
- specifiche per singolo punto del sito
- decisioni su campi/attributi Brevo
- checklist QA per ogni rilascio
