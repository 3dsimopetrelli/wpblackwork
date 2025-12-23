# Guida Configurazione Social Login - BlackWork

## 🔵 Facebook Login Setup

### Step 1: Creare un'App Facebook

1. Vai su **https://developers.facebook.com/apps/**
2. Clicca su **"Crea un'app"** (Create App)
3. Seleziona il tipo: **"Consumatore"** (Consumer)
4. Compila i campi:
   - **Nome app**: Nome del tuo sito (es. "BlackWork Store")
   - **Email di contatto**: La tua email
   - **Scopo**: Seleziona "Altro"
5. Clicca **"Crea app"**

### Step 2: Configurare Facebook Login

1. Nella dashboard dell'app, cerca **"Facebook Login"** nei prodotti
2. Clicca su **"Configura"** (Set Up)
3. Seleziona **"Web"**
4. Inserisci l'URL del tuo sito (es. `https://tuosito.com`)

### Step 3: Configurare Valid OAuth Redirect URIs

1. Nel menu laterale: **Facebook Login > Impostazioni** (Settings)
2. Trova il campo **"Valid OAuth Redirect URIs"**
3. **IMPORTANTE**: Copia l'URL esatto dal pannello WordPress:
   - Vai su **WordPress Admin > Blackwork > Site Settings > My Account Page**
   - Sotto "Facebook Redirect URI" c'è un campo read-only con l'URL
   - Copia quell'URL (es. `https://tuosito.com/my-account/?bw_social_login_callback=facebook`)
4. Incolla l'URL nel campo "Valid OAuth Redirect URIs" di Facebook
5. Clicca **"Salva modifiche"**

### Step 4: Ottenere App ID e App Secret

1. Nel menu laterale: **Impostazioni > Di base** (Settings > Basic)
2. Troverai:
   - **ID app** (App ID): es. `1234567890123456`
   - **Chiave segreta dell'app** (App Secret): clicca su "Mostra" per visualizzarla
3. **Copia questi valori**

### Step 5: Inserire in WordPress

1. Vai su **WordPress Admin > Blackwork > Site Settings > My Account Page**
2. Spunta la checkbox **"Enable Facebook Login"**
3. Incolla:
   - **Facebook App ID**: il valore dell'ID app
   - **Facebook App Secret**: il valore della chiave segreta
4. Clicca **"Salva impostazioni"**

### Step 6: Pubblicare l'App (IMPORTANTE!)

**⚠️ L'app è in modalità sviluppo per default!**

Per rendere il login pubblico:
1. Nel menu laterale: **Impostazioni > Di base** (Settings > Basic)
2. Scorri in basso fino a **"Modalità app"** (App Mode)
3. Attiva l'interruttore per passare da **"Development"** a **"Live"**
4. Facebook potrebbe richiedere:
   - URL Privacy Policy
   - URL Terms of Service
   - Icona dell'app
5. Compila i campi richiesti e conferma

### Permessi Richiesti

L'app richiede solo:
- ✅ **email** (permesso base, sempre disponibile)
- ✅ **public_profile** (permesso base, sempre disponibile)

Non serve richiedere permessi aggiuntivi a Facebook.

---

## 🔴 Google Login Setup

### Step 1: Creare un Progetto Google Cloud

1. Vai su **https://console.cloud.google.com/**
2. In alto a sinistra, clicca sul menu a tendina dei progetti
3. Clicca **"Nuovo progetto"** (New Project)
4. Compila:
   - **Nome progetto**: Nome del tuo sito (es. "BlackWork Store")
   - **Organizzazione**: lascia vuoto se non hai un'organizzazione
5. Clicca **"Crea"**

### Step 2: Abilitare Google+ API

1. Nel menu laterale: **API e servizi > Libreria** (APIs & Services > Library)
2. Cerca **"Google+ API"**
3. Clicca su **"Google+ API"**
4. Clicca **"Abilita"** (Enable)

### Step 3: Configurare OAuth Consent Screen

1. Nel menu laterale: **API e servizi > Schermata consenso OAuth** (OAuth consent screen)
2. Seleziona:
   - **Tipo utente**: **"Esterno"** (External)
3. Clicca **"Crea"**
4. Compila la schermata di consenso:
   - **Nome dell'app**: Nome del tuo sito
   - **Email di supporto utenti**: La tua email
   - **Logo dell'app**: (facoltativo)
   - **Dominio app**: `tuosito.com`
   - **Email dello sviluppatore**: La tua email
5. Clicca **"Salva e continua"**
6. **Ambiti** (Scopes): clicca "Salva e continua" (gli ambiti base sono sufficienti)
7. **Utenti test**: se l'app è in "Testing", aggiungi le email di test
8. Clicca **"Salva e continua"**

### Step 4: Creare Credenziali OAuth 2.0

1. Nel menu laterale: **API e servizi > Credenziali** (Credentials)
2. Clicca su **"+ Crea credenziali"** (Create Credentials)
3. Seleziona **"ID client OAuth"** (OAuth client ID)
4. Scegli tipo: **"Applicazione web"** (Web application)
5. Compila:
   - **Nome**: "BlackWork Login" (o un nome a tua scelta)
   - **URI di reindirizzamento autorizzati** (Authorized redirect URIs):
     - Clicca **"+ Aggiungi URI"**
     - **IMPORTANTE**: Copia l'URL esatto dal pannello WordPress:
       - Vai su **WordPress Admin > Blackwork > Site Settings > My Account Page**
       - Sotto "Google Redirect URI" c'è un campo read-only con l'URL
       - Copia quell'URL (es. `https://tuosito.com/my-account/?bw_social_login_callback=google`)
     - Incolla l'URL
6. Clicca **"Crea"**

### Step 5: Ottenere Client ID e Client Secret

1. Dopo aver creato le credenziali, apparirà un popup con:
   - **ID client**: es. `123456789-abc123xyz.apps.googleusercontent.com`
   - **Segreto client**: es. `GOCSPX-AbCdEfGhIjKlMnOpQrStUvWx`
2. **Copia questi valori** (puoi anche scaricare il JSON)
3. Se chiudi il popup, puoi sempre trovarli in **API e servizi > Credenziali**

### Step 6: Inserire in WordPress

1. Vai su **WordPress Admin > Blackwork > Site Settings > My Account Page**
2. Spunta la checkbox **"Enable Google Login"**
3. Incolla:
   - **Google Client ID**: il valore dell'ID client
   - **Google Client Secret**: il valore del segreto client
4. Clicca **"Salva impostazioni"**

### Step 7: Pubblicare l'App (IMPORTANTE!)

**⚠️ L'app è in modalità "Testing" per default!**

Per rendere il login pubblico:
1. Vai su **API e servizi > Schermata consenso OAuth**
2. In alto, sotto "Stato pubblicazione", clicca **"Pubblica app"** (Publish app)
3. Conferma la pubblicazione

**Nota**: Se lasci l'app in modalità "Testing", solo gli utenti aggiunti come "Utenti test" potranno fare login.

### Permessi Richiesti

L'app richiede solo:
- ✅ **openid** (identificativo utente)
- ✅ **email** (indirizzo email)
- ✅ **profile** (nome e foto profilo)

Questi sono permessi base e non richiedono verifica da Google.

---

## ✅ Checklist Finale di Verifica

### Facebook
- [ ] App creata su Facebook Developers
- [ ] Facebook Login configurato
- [ ] Valid OAuth Redirect URI configurato (URL esatto da WordPress)
- [ ] App ID copiato
- [ ] App Secret copiato
- [ ] App pubblicata (modalità "Live")
- [ ] Valori inseriti nel pannello WordPress
- [ ] Checkbox "Enable Facebook Login" attivata

### Google
- [ ] Progetto creato su Google Cloud Console
- [ ] Google+ API abilitata (o People API)
- [ ] OAuth Consent Screen configurato
- [ ] Credenziali OAuth 2.0 create
- [ ] Authorized Redirect URI configurato (URL esatto da WordPress)
- [ ] Client ID copiato
- [ ] Client Secret copiato
- [ ] App pubblicata (non in "Testing" o utenti test aggiunti)
- [ ] Valori inseriti nel pannello WordPress
- [ ] Checkbox "Enable Google Login" attivata

---

## 🧪 Test Funzionalità

### Come Testare

1. **Logout da WordPress** (se sei loggato)
2. Vai alla pagina **My Account** (es. `https://tuosito.com/my-account/`)
3. Dovresti vedere i bottoni:
   - **"Login with Google"** (con icona colorata)
   - **"Login with Facebook"** (con icona bianca su nero)
4. **Clicca su un bottone**
5. Dovresti essere reindirizzato a Facebook/Google
6. **Autorizza l'accesso**
7. Dovresti essere reindirizzato al tuo sito e **loggato automaticamente**

### Cosa Fare se Non Funziona

**Se vedi errore "Social login is not available at the moment":**
- Verifica che le checkbox "Enable" siano attive
- Verifica che App ID/Secret o Client ID/Secret siano inseriti correttamente
- Controlla che non ci siano spazi extra copiando/incollando

**Se vieni reindirizzato ma vedi errore "redirect_uri_mismatch":**
- L'URL di redirect nel pannello Facebook/Google NON corrisponde
- Copia ESATTAMENTE l'URL dal pannello WordPress (incluso `?bw_social_login_callback=...`)
- Verifica che sia HTTPS (non HTTP)

**Se vieni reindirizzato ma vedi errore "invalid_client":**
- Client ID o Client Secret errati
- Verifica di aver copiato i valori corretti
- Prova a rigenerare le credenziali

**Se il login funziona solo per te (admin) ma non per altri utenti:**
- L'app è ancora in modalità "Development" (Facebook) o "Testing" (Google)
- Pubblica l'app seguendo gli step sopra

---

## 🔐 Note di Sicurezza

### Protezione delle Credenziali

**⚠️ IMPORTANTE**: Non condividere mai pubblicamente:
- Facebook App Secret
- Google Client Secret

Questi valori sono sensibili e permettono di impersonare la tua app.

### HTTPS Obbligatorio

OAuth 2.0 richiede **HTTPS** per sicurezza. Se il tuo sito è solo HTTP:
1. Installa un certificato SSL (Let's Encrypt è gratuito)
2. Configura WordPress per usare HTTPS
3. Aggiorna gli URL di redirect nelle console Facebook/Google

### Backup delle Credenziali

Salva le credenziali in un password manager o file sicuro:
```
Facebook:
- App ID: 1234567890123456
- App Secret: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6

Google:
- Client ID: 123456789-abc123xyz.apps.googleusercontent.com
- Client Secret: GOCSPX-AbCdEfGhIjKlMnOpQrStUvWx
```

---

## 📞 Supporto

Se dopo aver seguito questa guida i login social continuano a non funzionare:

1. **Attiva il debug di WordPress**:
   ```php
   // wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

2. **Controlla il log**: `wp-content/debug.log`

3. **Verifica gli URL di redirect**:
   - Devono essere IDENTICI in WordPress e nelle console OAuth
   - Inclusi protocollo (https://), dominio, path e query parameters

4. **Test con Browser DevTools**:
   - Apri DevTools (F12)
   - Tab "Network"
   - Clicca sul bottone social login
   - Verifica i redirect e eventuali errori

---

## 📚 Risorse Utili

### Facebook
- Developers Console: https://developers.facebook.com/apps/
- Documentazione Facebook Login: https://developers.facebook.com/docs/facebook-login/web
- Testing Login Flow: https://developers.facebook.com/tools/debug/

### Google
- Cloud Console: https://console.cloud.google.com/
- Documentazione OAuth 2.0: https://developers.google.com/identity/protocols/oauth2
- OAuth 2.0 Playground: https://developers.google.com/oauthplayground/

---

**Ultimo aggiornamento**: 2025-12-23
**Versione Plugin**: BW Elementor Widgets
**Compatibilità**: WordPress 5.0+, WooCommerce 3.0+
