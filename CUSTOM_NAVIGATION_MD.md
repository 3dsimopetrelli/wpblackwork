# Custom Navigation MD

## Scope analizzato

File principali della custom navigation (header module):

- `includes/modules/header/header-module.php`
- `includes/modules/header/admin/settings-schema.php`
- `includes/modules/header/admin/header-admin.php`
- `includes/modules/header/frontend/header-render.php`
- `includes/modules/header/frontend/assets.php`
- `includes/modules/header/frontend/fragments.php`
- `includes/modules/header/helpers/menu.php`
- `includes/modules/header/templates/header.php`
- `includes/modules/header/templates/parts/mobile-nav.php`
- `includes/modules/header/assets/js/bw-navigation.js`
- `includes/modules/header/assets/js/header-init.js`
- `includes/modules/header/assets/css/bw-navigation.css`
- `includes/modules/header/assets/css/header-layout.css`
- `includes/modules/header/assets/js/bw-navshop.js`
- `includes/modules/header/assets/css/bw-navshop.css`

Documentazione correlata:

- `docs/CUSTOM_HEADER_ARCHITECTURE.md`
- `docs/custom-header-architecture-from-elementor.md`

## Flusso completo (bootstrap -> render -> runtime)

1. Il modulo header viene caricato da `includes/modules/header/header-module.php`.
2. Le impostazioni vengono registrate/sanificate in `includes/modules/header/admin/settings-schema.php` (`bw_header_settings`).
3. In frontend:
   - `includes/modules/header/frontend/assets.php` enqueue CSS/JS e genera inline CSS responsive con breakpoint dinamico.
   - `includes/modules/header/frontend/header-render.php` stampa il markup dell’header su `wp_body_open`.
4. Il template `includes/modules/header/templates/header.php` contiene due blocchi:
   - desktop (`.bw-custom-header__desktop`)
   - mobile (`.bw-custom-header__mobile`)
5. Il drawer menu mobile è in `includes/modules/header/templates/parts/mobile-nav.php`.
6. `includes/modules/header/assets/js/bw-navigation.js` gestisce open/close overlay mobile (toggle, close button, ESC, overlay click, click su link).
7. `includes/modules/header/assets/js/header-init.js` gestisce stato mobile/desktop, sticky/smart scroll, classe `is-mobile`, riordino DOM header in `body`.

## Desktop navigation flow

### Rendering

- In `includes/modules/header/frontend/header-render.php`:
  - `desktop_menu_id` obbligatorio; se non impostato/non valido, header non renderizza.
  - menu HTML generato da `bw_header_render_menu(...)`.
- In `includes/modules/header/templates/header.php`:
  - desktop nav renderizzata dentro:
    - `<nav class="bw-navigation__desktop bw-custom-header__desktop-menu">`
  - menu desktop stampato come `$desktop_menu_html`.

### Menu generation

- In `includes/modules/header/helpers/menu.php`:
  - `wp_nav_menu` con `depth => 1` (niente submenu nativi nel modulo custom).
  - filtro `nav_menu_link_attributes` aggiunge classe `bw-navigation__link` a tutti gli anchor.

### Styling desktop

- `includes/modules/header/assets/css/bw-navigation.css`:
  - lista desktop in inline-flex con gap fisso.
  - link con font-size 24 e line-height 1.
- `includes/modules/header/assets/css/header-layout.css`:
  - layout desktop a tre aree (logo, centro menu+search, destra account/cart).
  - colori hover e tipografia uniformati con navshop/search.

## Mobile navigation flow

### Rendering e fallback menu

- In `includes/modules/header/frontend/header-render.php`:
  - `mobile_menu_id` fallback automatico a `desktop_menu_id`.
  - se HTML mobile vuoto, fallback a HTML desktop.
- In `includes/modules/header/templates/header.php`:
  - toggle hamburger `.bw-navigation__toggle`.
  - include del drawer `parts/mobile-nav.php`.
- In `includes/modules/header/templates/parts/mobile-nav.php`:
  - overlay `.bw-navigation__mobile-overlay`.
  - pannello `.bw-navigation__mobile-panel`.
  - nav mobile `.bw-navigation__mobile`.
  - auth link finale (`Login`/`My Account`) fuori dalla lista menu.

### Behavior JS mobile

- `includes/modules/header/assets/js/bw-navigation.js`:
  - sposta overlay sotto `document.body` (evita clipping/stacking issues con ancestor trasformati).
  - assegna delay progressivo (`--bw-nav-stagger-delay`) agli item mobile.
  - apertura:
    - `.is-open` su overlay
    - `aria-hidden=false` overlay
    - `aria-expanded=true` toggle
    - `body.bw-navigation-mobile-open` per bloccare scroll pagina
  - chiusura su:
    - bottone close
    - click su backdrop
    - ESC
    - click su link del menu mobile

### Styling mobile

- `includes/modules/header/assets/css/bw-navigation.css`:
  - overlay full-screen fixed con fade.
  - panel slide-in da sinistra.
  - animazione ingresso item con stagger.
  - `@media (max-width: 769px)` forza panel full-width.
- `includes/modules/header/frontend/assets.php` (inline CSS):
  - breakpoint amministrabile (`breakpoints.mobile`).
  - sotto breakpoint:
    - desktop hidden, mobile shown.
    - toggle visibile.
    - nav overlay attivata.
    - label cart/search nascosta, icone mostrate.
  - sopra breakpoint:
    - mobile hidden, desktop shown.
    - toggle/overlay nascosti.

## Configurazione admin che impatta navigation

In `includes/modules/header/admin/header-admin.php`:

- scelta menu desktop e mobile.
- breakpoint mobile (320..1920).
- upload icona hamburger mobile.
- spacing/padding/margin area mobile.
- smart scroll + blur panel settings (impatto visuale anche su navigation).

Sanitizzazione in `includes/modules/header/admin/settings-schema.php`:

- clamp numerici coerenti (breakpoint, padding, margini, badge).
- fallback mobile menu a desktop in sanitize finale.

## Integrazione con Search/NavShop (impatto sul flusso nav)

- In `includes/modules/header/templates/header.php`, mobile-right contiene search + cart.
- In `includes/modules/header/frontend/fragments.php`, badge cart aggiornato via Woo fragments.
- In `includes/modules/header/assets/js/bw-navshop.js`, click cart usa popup se disponibile, altrimenti redirect.

Questo influisce sulla UX navigation mobile perché il drawer convive con icone azione nello stesso header.

## Criticita e osservazioni tecniche

1. Feature flags non applicate nel render
- `features.search`, `features.navigation`, `features.navshop` sono sanitizzate in `settings-schema.php`, ma non sono usate nel template/render.
- Effetto: anche disattivandole a livello settings, i blocchi restano renderizzati.

2. Accessibilita focus close button
- `bw-navigation__close` ha stile hover, ma non una regola `:focus-visible` dedicata come il toggle.
- Opportuno aggiungere focus ring esplicito anche sul bottone chiusura.

3. Overlay singleton per istanza
- La logica JS supporta piu `.bw-navigation` ma sposta ogni overlay nel `body`.
- Funziona, ma in scenari con header duplicati può produrre overlay multipli nel DOM.

4. `:has()` in inline CSS mobile
- In `assets.php` viene usato `:has()` per margine cart empty/non-empty.
- Compatibilità oggi buona nei browser moderni, ma resta un selettore avanzato con rischio su ambienti legacy.

5. Depth menu hardcoded a 1
- `bw_header_render_menu(... depth => 1)` blocca menu annidati.
- Se serve navigation gerarchica mobile, serve estensione specifica (toggle submenu, aria-controls, keyboard handling).

## Checklist verifica desktop/mobile

Desktop:

- menu desktop visibile e mobile hidden oltre breakpoint.
- hover/focus link navigation.
- badge cart desktop in posizione coerente con offset admin.
- smart scroll: hide/show senza flicker.

Mobile:

- toggle visibile sotto breakpoint.
- apertura drawer, close via X/backdrop/ESC/link.
- body lock attivo con drawer aperto.
- item animation stagger regolare.
- login/account link finale presente e corretto.
- fallback menu mobile -> desktop quando mobile non configurato.

## Conclusione

La custom navigation è strutturalmente solida e separata per desktop/mobile con fallback robusti e comportamento JS completo sul drawer. Il principale gap funzionale è la mancata applicazione dei flag `features.*` nel rendering effettivo, mentre il resto riguarda miglioramenti mirati (focus accessibility, gestione scenari multi-istanza, supporto submenu).
