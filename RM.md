# RM — Architettura backend Blackwork Site (Account, Checkout, Supabase)

> Documento tecnico completo per comprendere la struttura backend e i flussi di autenticazione Supabase.

## 1) Panorama generale (backend + template + JS)
Il plugin BW Elementor Widgets aggancia l’inizializzazione WooCommerce e carica le override personalizzate per **My Account**, **login** e **checkout**. Il punto di ingresso per queste override è `woocommerce/woocommerce-init.php`, che:
- include le classi custom (`class-bw-my-account.php`, `class-bw-supabase-auth.php`),
- forza l’uso dei template del plugin,
- carica asset CSS/JS per login, My Account e checkout,
- applica filtri di layout (body class, title bypass, checkout notices).【F:woocommerce/woocommerce-init.php†L7-L214】

## 2) Configurazioni admin (Account Page + Checkout)
Le impostazioni Supabase e dell’Account Page vengono gestite nella pagina **Blackwork Site Settings → Account Page** con sotto-tab Design/Technical. Qui vengono salvate tutte le chiavi e i flag usati dai flussi di autenticazione e dai template. Le opzioni principali includono:
- **Supabase project URL / anon key / service role key**
- **Login mode** (native/OIDC), **registration mode** (R1/R2/R3)
- **Magic link**, **OAuth provider toggles** (Google/Facebook/Apple)
- **Redirect URL** (magic link, OAuth, signup conferma email)
- **Cookie base** e **session storage** (cookie/usermeta)
- **Flag debug logging**
- **Provisioning checkout** e redirect invite

Questi valori sono letti e salvati in `admin/class-blackwork-site-settings.php` e alimentano direttamente i flussi front/back-end.【F:admin/class-blackwork-site-settings.php†L340-L1004】【F:admin/class-blackwork-site-settings.php†L1220-L1463】

### Opzioni Supabase principali (keywords/chiavi)
Queste sono le keyword/option key più rilevanti salvate nelle opzioni WP e usate in tutto il backend:
- `bw_supabase_project_url`, `bw_supabase_anon_key`, `bw_supabase_service_role_key`【F:admin/class-blackwork-site-settings.php†L340-L357】
- `bw_supabase_login_mode`, `bw_supabase_registration_mode`, `bw_supabase_with_plugins`【F:admin/class-blackwork-site-settings.php†L340-L364】
- `bw_supabase_magic_link_enabled`, `bw_supabase_login_password_enabled`, `bw_supabase_register_prompt_enabled`【F:admin/class-blackwork-site-settings.php†L340-L360】
- `bw_supabase_oauth_google_enabled`, `bw_supabase_oauth_facebook_enabled`, `bw_supabase_oauth_apple_enabled`【F:admin/class-blackwork-site-settings.php†L340-L343】
- OAuth provider config: `bw_supabase_google_client_id`, `bw_supabase_google_client_secret`, `bw_supabase_google_scopes`, `bw_supabase_google_prompt`, ecc.【F:admin/class-blackwork-site-settings.php†L344-L357】
- `bw_supabase_magic_link_redirect_url`, `bw_supabase_oauth_redirect_url`, `bw_supabase_signup_redirect_url`【F:admin/class-blackwork-site-settings.php†L360-L363】
- `bw_supabase_jwt_cookie_name`, `bw_supabase_session_storage`, `bw_supabase_enable_wp_user_linking`, `bw_supabase_create_wp_users`【F:admin/class-blackwork-site-settings.php†L358-L364】【F:admin/class-blackwork-site-settings.php†L966-L1004】
- Checkout invite: `bw_supabase_checkout_provision_enabled`, `bw_supabase_invite_redirect_url`【F:admin/class-blackwork-site-settings.php†L1249-L1308】【F:admin/class-blackwork-site-settings.php†L1436-L1453】

## 3) Flussi My Account NON loggato (login/registrazione)
Quando l’utente visita `/my-account/` **da guest**, il sistema carica il template personalizzato `woocommerce/templates/myaccount/form-login.php` e gli asset `bw-account-page.js` + Supabase SDK.

### UI / Template (form-login.php)
Il template definisce:
- **Magic link / OTP** con email (`data-bw-supabase-action="magic-link"`).【F:woocommerce/templates/myaccount/form-login.php†L43-L118】
- **OAuth** pulsanti per Google/Facebook/Apple (`data-bw-oauth-provider`).【F:woocommerce/templates/myaccount/form-login.php†L120-L147】
- **Password login** + reset + set password + regole password (screen interne).【F:woocommerce/templates/myaccount/form-login.php†L149-L214】
- **OTP screen** con 6 input numerici + resend code.【F:woocommerce/templates/myaccount/form-login.php†L216-L263】
- **OTP set password** (post-OTP) con regole e conferma password.【F:woocommerce/templates/myaccount/form-login.php†L264-L312】
- **Registrazione**: modalità R1 (link provider) o R2 (email + password).【F:woocommerce/templates/myaccount/form-login.php†L324-L395】

### JS client (bw-account-page.js)
Il client gestisce:
- chiavi di sessionStorage/localStorage per OTP, redirect e bridge (es: `bw_otp_pending_session`, `bw_supabase_access_token`, `bw_bridge_redirected`, ecc.)【F:assets/js/bw-account-page.js†L1-L123】
- clean/reset di sessione e cookie Supabase (`bw_supabase_session_access/refresh`).【F:assets/js/bw-account-page.js†L120-L123】
- validazione email, gestione parametri URL e callback auth (hash/code).【F:assets/js/bw-account-page.js†L151-L166】

L’SDK Supabase e la configurazione vengono iniettati in `bwAccountAuth` tramite `woocommerce-init.php`.【F:woocommerce/woocommerce-init.php†L64-L170】

## 4) Flussi My Account LOGGATO (dashboard + security)
Quando l’utente è autenticato:
- vengono caricati `bw-my-account.css/js` e `form-edit-account.php`,
- il layout è suddiviso in **Profile / Billing / Shipping / Security** con tab client-side.【F:woocommerce/templates/myaccount/form-edit-account.php†L50-L240】【F:assets/js/bw-my-account.js†L1-L25】

### Security tab (password + email)
- Aggiornamento password con regole lato UI e messaggi di sessione mancante.【F:woocommerce/templates/myaccount/form-edit-account.php†L180-L224】
- Aggiornamento email Supabase con banner “pending email”.【F:woocommerce/templates/myaccount/form-edit-account.php†L151-L240】

### Onboarding lock (set-password endpoint)
Se l’utente Supabase è invitato o non onboarded:
- viene forzato l’endpoint `/my-account/set-password/`
- viene bloccata la navigazione nelle altre sezioni.

Questo comportamento è definito in `class-bw-my-account.php` con endpoint `set-password` e redirect automatici.【F:includes/woocommerce-overrides/class-bw-my-account.php†L63-L157】

Il template `set-password.php` gestisce la UI di onboarding con resend invite se manca il token valido.【F:woocommerce/templates/myaccount/set-password.php†L12-L62】

## 5) Backend Supabase (API/Auth server-side)
Tutti i flussi Supabase lato backend sono centralizzati in `includes/woocommerce-overrides/class-bw-supabase-auth.php`.

### Config e diagnostica
- `bw_mew_get_supabase_config()` normalizza project URL + anon key.
- `bw_mew_supabase_build_diagnostics()` produce una diagnostica admin-friendly con opzioni mancanti.【F:includes/woocommerce-overrides/class-bw-supabase-auth.php†L12-L66】

### Token login (bridge Supabase → WP)
- AJAX `bw_supabase_token_login`: verifica access token su `/auth/v1/user`, crea/aggancia utente WP e setta cookie/sessione WP.
- Gestisce onboarding per invite/OTP (`bw_supabase_onboarded`, `bw_supabase_invited`).【F:includes/woocommerce-overrides/class-bw-supabase-auth.php†L88-L258】

### Login password / signup / recover
- **Password login** usa `/auth/v1/token?grant_type=password` e poi salva sessione (`bw_mew_supabase_store_session`).【F:includes/woocommerce-overrides/class-bw-supabase-auth.php†L300-L470】
- **Register** usa `/auth/v1/signup` con redirect di conferma email.【F:includes/woocommerce-overrides/class-bw-supabase-auth.php†L624-L767】
- **Recover** usa `/auth/v1/recover` per reset password via email.【F:includes/woocommerce-overrides/class-bw-supabase-auth.php†L769-L861】

### Update profile, password, email
- `bw_mew_supabase_update_user()` invia PUT `/auth/v1/user`.
- AJAX per **update profile**, **update password**, **update email**, con validazioni e messaggi. 【F:includes/woocommerce-overrides/class-bw-supabase-auth.php†L883-L1124】

### Session storage (cookie/usermeta)
`bw_mew_supabase_store_session()` salva access/refresh token su:
- **cookie**: `${cookie_base}_access`, `${cookie_base}_refresh`
- **usermeta**: `bw_supabase_access_token`, `bw_supabase_refresh_token`

Il comportamento è controllato da `bw_supabase_session_storage` e `bw_supabase_jwt_cookie_name`.【F:includes/woocommerce-overrides/class-bw-supabase-auth.php†L1358-L1448】

## 6) Supabase keywords e storage client-side
Nel front-end vengono usate chiavi per gestire le fasi OTP, redirect e bridge:
- Session keys principali: `bw_otp_pending_session`, `bw_otp_needs_password`, `bw_otp_mode`, `bw_supabase_access_token`, `bw_supabase_refresh_token`, `bw_bridge_redirected` ecc.【F:assets/js/bw-account-page.js†L10-L118】
- `bw_handled_supabase_hash` / `bw_handled_supabase_code` per evitare doppi bridge; `bw_oauth_bridge_done` per redirect guard.【F:assets/js/bw-supabase-bridge.js†L60-L132】
- `bw_onboarded` / `bw_onboarded_email` in localStorage durante onboarding/bridge.【F:assets/js/bw-account-page.js†L87-L114】【F:assets/js/bw-supabase-bridge.js†L39-L45】

## 7) Bridge Supabase (OAuth/Invite → WP session)
Il file `assets/js/bw-supabase-bridge.js` intercetta:
- token da hash (access_token/refresh_token),
- code/state OAuth,
- inviti (`type=invite`) e recovery.

Invia `bw_supabase_token_login` e, dopo check di sessione, effettua redirect a `/my-account/`.【F:assets/js/bw-supabase-bridge.js†L19-L181】

## 8) Checkout + provisioning Supabase
Nel checkout, se l’utente acquista come guest e **Supabase provisioning** è attivo:
- viene creato un utente WP e inviata una **invite Supabase** con redirect a `/my-account/set-password/`.
- il processo è eseguito su `woocommerce_order_status_processing|completed`.

Le opzioni di provisioning sono configurate nel tab Checkout (admin).【F:admin/class-blackwork-site-settings.php†L1436-L1453】
La logica server è in `bw_mew_handle_supabase_checkout_invite()` e `bw_mew_send_supabase_invite()`.【F:includes/woocommerce-overrides/class-bw-supabase-auth.php†L1136-L1336】

## 9) Schema operativo (flow sintetico)
```
GUEST /my-account
 └─ form-login.php (magic link / OTP / OAuth / password / register)
     └─ bw-account-page.js
         ├─ Supabase JS SDK (signInWithOtp, verifyOtp, OAuth redirect)
         ├─ sessionStorage keys (otp, bridge, tokens)
         └─ AJAX → bw_supabase_token_login

SUPABASE CALLBACK (/my-account/#access_token=...)
 └─ bw-supabase-bridge.js
     └─ AJAX bw_supabase_token_login → WP auth cookie
         └─ redirect /my-account/ (or /set-password if invite)

LOGGED-IN /my-account
 └─ form-edit-account.php (Profile/Billing/Shipping/Security)
     ├─ bw-my-account.js (tabs, password/email updates)
     └─ AJAX: bw_supabase_update_password / bw_supabase_update_email

CHECKOUT guest
 └─ ordine → bw_mew_handle_supabase_checkout_invite → invite Supabase
```

## 10) Punti di estensione consigliati
Per implementare nuove funzionalità in modo consistente:
- **Backend**: estendere `class-bw-supabase-auth.php` per nuove API Supabase (usare `bw_mew_get_supabase_config()` + debug payload).【F:includes/woocommerce-overrides/class-bw-supabase-auth.php†L12-L66】
- **My Account**: aggiungere nuove schede in `form-edit-account.php` + logica JS in `bw-my-account.js` (pattern tab e form).【F:woocommerce/templates/myaccount/form-edit-account.php†L50-L240】【F:assets/js/bw-my-account.js†L1-L25】
- **Login**: aggiungere nuovi step in `form-login.php` + handler in `bw-account-page.js` (pattern `data-bw-*`).【F:woocommerce/templates/myaccount/form-login.php†L43-L395】【F:assets/js/bw-account-page.js†L1-L118】
- **Checkout**: nuove automazioni post-ordine vanno integrate vicino a `bw_mew_handle_supabase_checkout_invite()` per mantenere coerenza sui meta order/user.【F:includes/woocommerce-overrides/class-bw-supabase-auth.php†L1136-L1336】

---

## Note finali
Questo file sostituisce il precedente RM focalizzato su modifiche puntuali. Ora funge da **documento tecnico completo** per capire la struttura backend di Blackwork Site, la gestione Supabase e i flussi My Account/Checkout.
