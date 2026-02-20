# Custom Navigation MD

## Scope analizzato (stato attuale)

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

## Flusso desktop

### Rendering

- In `includes/modules/header/frontend/header-render.php`:
  - i blocchi `navigation/search/navshop` vengono renderizzati in base ai feature flag.
  - fallback compatibilità: se i 3 flag risultano tutti spenti per salvataggi legacy, vengono riattivati runtime.
  - `desktop_menu_id` usato per generare HTML desktop.
- In `includes/modules/header/templates/header.php`:
  - blocco desktop nav dentro `<nav class="bw-navigation__desktop bw-custom-header__desktop-menu">`.
  - search/navshop condizionali ai relativi flag.

### Menu generation

- In `includes/modules/header/helpers/menu.php`:
  - `wp_nav_menu` con `depth => 2` (submenu nativi abilitati).
  - filtro `nav_menu_link_attributes` aggiunge classe `bw-navigation__link` a tutti gli anchor.

### Styling desktop

- `includes/modules/header/assets/css/bw-navigation.css`:
  - lista desktop in inline-flex con gap fisso.
  - freccia su voci parent (`.menu-item-has-children > .bw-navigation__link::after`).
  - dropdown submenu desktop (`.sub-menu`) con apertura hover/focus.
  - hover bridge invisibile (`.sub-menu::before`) per evitare chiusura del dropdown nel passaggio mouse tra parent e submenu.
- `includes/modules/header/assets/css/header-layout.css`:
  - layout desktop a tre aree (logo, centro menu+search, destra account/cart).
  - colori hover e tipografia uniformati con navshop/search.

## Flusso mobile

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
  - non esiste un toggle JS dedicato per aprire/chiudere submenu in mobile: i submenu vengono visualizzati come lista annidata (sempre visibile nel pannello).

### Styling mobile

- `includes/modules/header/assets/css/bw-navigation.css`:
  - overlay full-screen fixed con fade.
  - panel slide-in da sinistra.
  - animazione ingresso item con stagger.
  - close button accessibile con `:focus-visible`.
  - submenu mobile annidato con indentazione (`.bw-navigation__mobile .sub-menu`).
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
- preserva i feature flag `search/navigation/navshop` anche quando non inviati dal form (compatibilità con form legacy).

## Integrazione con Search/NavShop (impatto sul flusso nav)

- In `includes/modules/header/templates/header.php`, mobile-right contiene search + cart.
- In `includes/modules/header/frontend/fragments.php`, badge cart aggiornato via Woo fragments.
- In `includes/modules/header/assets/js/bw-navshop.js`, click cart usa popup se disponibile, altrimenti redirect.

Questo influisce sulla UX navigation mobile perché il drawer convive con icone azione nello stesso header.

## CSS Glass/Blur (gradazione effetto blur)

Logica principale in `includes/modules/header/frontend/assets.php`:

- Parametri admin:
  - `menu_blur_enabled`
  - `menu_blur_amount` (px)
  - `menu_blur_radius` (px)
  - `menu_blur_tint_color` + `menu_blur_tint_opacity`
  - `menu_blur_scrolled_tint_color` + `menu_blur_scrolled_tint_opacity`
  - `menu_blur_padding_*`
- Conversione colore+tinta in RGBA via `bw_header_hex_to_rgba(...)`.

CSS inline generato (desktop):

```css
.bw-custom-header__desktop-panel.is-blur-enabled {
  -webkit-backdrop-filter: blur(<amount>px);
  backdrop-filter: blur(<amount>px) !important;
  background-color: rgba(..., <tint_opacity>) !important;
  padding: <top>px <right>px <bottom>px <left>px !important;
  border-radius: <radius>px !important;
}

.bw-custom-header.bw-header-scrolled .bw-custom-header__desktop-panel.is-blur-enabled {
  background-color: rgba(..., <scrolled_tint_opacity>) !important;
}
```

CSS inline generato (mobile):

```css
.bw-custom-header__mobile-panel.is-blur-enabled {
  -webkit-backdrop-filter: blur(<amount>px);
  backdrop-filter: blur(<amount>px) !important;
  background-color: rgba(..., <tint_opacity>) !important;
  padding: <mobile_blur_v>px <mobile_blur_h>px !important;
  border-radius: <radius>px !important;
}

.bw-custom-header.bw-header-scrolled .bw-custom-header__mobile-panel.is-blur-enabled {
  background-color: rgba(..., <scrolled_tint_opacity>) !important;
}
```

Note gradazione:

- La “gradazione glass” è data dalla combinazione:
  - blur fisico (`backdrop-filter`)
  - tinta RGBA sopra il blur (normale vs scrolled)
- In stato `scrolled` la tinta può diventare più/meno intensa in base ai valori `*_scrolled_*`.
- Se blur è disabilitato, `assets.php` forza reset:
  - `backdrop-filter: none`
  - `background: transparent`
  - `padding: 0`
  - `border-radius: 0`

## Checklist verifica desktop/mobile

Desktop:

- menu desktop visibile e mobile hidden oltre breakpoint.
- hover/focus link navigation.
- dropdown submenu desktop raggiungibile senza chiusura prematura (hover bridge).
- badge cart desktop in posizione coerente con offset admin.
- smart scroll: hide/show senza flicker.

Mobile:

- toggle visibile sotto breakpoint.
- apertura drawer, close via X/backdrop/ESC/link.
- body lock attivo con drawer aperto.
- item animation stagger regolare.
- login/account link finale presente e corretto.
- fallback menu mobile -> desktop quando mobile non configurato.
- close button navigabile con tastiera e focus visibile.
- submenu annidati leggibili nel pannello mobile.

## Conclusione

La custom navigation è ora documentata in modo coerente con lo stato reale: feature flag applicati con fallback compatibilità, submenu desktop nativi (depth 2), accessibilità focus su close mobile, e dettaglio completo del sistema blur/glass con gradazione tinta normale/scrolled.
