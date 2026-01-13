# RM — Resoconto completo lavori My Account / Login

## Contesto e obiettivi
- **Rimozione del controllo legacy “Go back to store”** e relativa configurazione admin, per semplificare la UI di login/registrazione.
- **Unificazione del controllo “Back to Login”** in un solo elemento coerente e stilisticamente identico ovunque, evitando eredità di stili Elementor.
- **Comportamento coerente di ritorno alla schermata magic-link** con la stessa animazione “fade-up” del flusso auth.
- **Pulizia di CSS/opzioni legacy** non più utilizzate.

## Modifiche principali (frontend templates)
- `woocommerce/templates/myaccount/form-login.php`
  - Rimossa la logica del vecchio link “Go back to store”.
  - Tutti i controlli “Back” sono ora **button** con classe `.bw-account-login__back-link`, racchiusi in `.bw-account-login__back-to-login`.
  - Utilizzo unificato dei data-attribute: `data-bw-go-magic` (per tornare al magic-link) e `data-bw-auth-tab="login"` (per tornare al tab login). 

## Modifiche principali (CSS)
- `assets/css/bw-account-page.css`
  - Styling della classe `.bw-account-login__back-link` mantenuto e allineato ai tab Login/Register.
  - Eliminata la porzione di stile non utilizzata per `.bw-account-login__back` (contenitore legacy del vecchio link store).

## Modifiche principali (JS)
- `assets/js/bw-account-page.js`
  - Gestione dei click su elementi con `data-bw-go-magic` per tornare alla schermata magic-link.
  - Uso di `switchAuthScreen('magic')` per applicare la transizione fade-up e mantenere coerenza con il resto della UI auth.

## Modifiche admin e cleanup opzioni
- `admin/class-blackwork-site-settings.php`
  - Rimozione campi e opzioni legate a **“Go back to store”** (testo + URL).
- `bw-main-elementor-widgets.php`
  - Cleanup all’init delle opzioni legacy: `bw_account_back_text`, `bw_account_back_url`.

## Supabase / onboarding / auth flow
- `assets/js/bw-supabase-bridge.js`
  - Bridge JS per integrare flussi di auth e onboarding.
- `woocommerce/templates/myaccount/set-password.php`
  - Nuovo template per gestione set password nel flusso auth.
- `includes/woocommerce-overrides/class-bw-my-account.php`
- `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- `woocommerce/woocommerce-init.php`
  - Aggiornamenti per mantenere coerenti i flussi auth e token handling, in linea con la nuova UX di login.

## Elenco file chiave toccati
- `woocommerce/templates/myaccount/form-login.php`
- `assets/css/bw-account-page.css`
- `assets/js/bw-account-page.js`
- `admin/class-blackwork-site-settings.php`
- `bw-main-elementor-widgets.php`
- `assets/js/bw-supabase-bridge.js`
- `woocommerce/templates/myaccount/set-password.php`
- `includes/woocommerce-overrides/class-bw-my-account.php`
- `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- `woocommerce/woocommerce-init.php`

## Note finali
- Non sono stati eseguiti test automatici per queste modifiche.
- L’obiettivo ora è proseguire con nuove implementazioni su login e my account con una base più pulita e coerente.
