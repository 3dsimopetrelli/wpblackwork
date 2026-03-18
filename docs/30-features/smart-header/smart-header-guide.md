# Smart Header — Guida operativa

## Panoramica

Il sistema Smart Header è integrato in `includes/modules/header/` e si attiva automaticamente su qualsiasi pagina che contiene un elemento con la classe `bw-custom-header`. Non richiede configurazione manuale nel template Elementor — l'attivazione avviene tramite il pannello **Blackwork › Site Settings**.

---

## Attivazione

### Smart Scroll (hide/show)

Nel pannello admin **Blackwork › Site Settings → Header**, abilita l'opzione **Smart Scroll**. Questo aggiunge `data-smart-scroll="yes"` all'elemento header nel markup server-rendered. Il JS lo rileva automaticamente.

### Dark Zone Detection

Sempre attiva — non richiede configurazione. Funziona su header sia sticky che non-sticky.

---

## Comportamento a runtime

Il JS (`includes/modules/header/assets/js/header-init.js`) applica classi CSS sull'elemento `.bw-custom-header` in base allo stato:

| Classe CSS | Condizione |
|---|---|
| `is-desktop` / `is-mobile` | Viewport sopra/sotto il breakpoint configurato |
| `bw-header-scrolled` | `scrollTop > 2px` |
| `bw-header-hidden` | Smart scroll attivo, scroll verso il basso oltre la soglia |
| `bw-header-visible` | Smart scroll attivo, scroll verso l'alto |
| `bw-header-on-dark` | Header sovrapposto a una sezione con sfondo scuro |

### Soglie smart scroll (configurabili da admin)

| Parametro | Default | Descrizione |
|---|---|---|
| `scrollDownThreshold` | 100px | Pixel di scroll down prima di nascondere |
| `scrollUpThreshold` | 0px | Pixel di scroll up minimi per mostrare |
| `scrollDelta` | 1px | Sensibilità minima di movimento |

---

## Dark Zone Detection — Come funziona

Ogni frame di scroll, `checkDarkZoneOverlap()` sonda tre punti orizzontali al centro verticale dell'header usando `document.elementFromPoint()` con `pointer-events: none` (così il sondaggio "vede attraverso" l'header fisso). Per ogni elemento trovato, `isSectionDark()` percorre tutta la catena degli antenati con questi check in ordine:

1. **`background-color` scuro** — check diretto via `getComputedStyle`
2. **Slick carousel** — campiona la slide attiva (`.slick-active`) via canvas 8×8
3. **Overlay Elementor/Gutenberg** — controlla child div `.elementor-background-overlay`, `.wp-block-cover__background`
4. **`background-image` + testo chiaro** — se i primi 3 heading figli hanno colore > 180 di luminanza, lo sfondo è considerato scuro

### Override manuale

Aggiungi la classe CSS `smart-header-dark-zone` a qualsiasi sezione Elementor (tab Avanzate → Classi CSS) per forzare la zona come scura indipendentemente dal rilevamento automatico. Ha priorità assoluta.

```
Elementor → Sezione → Tab Avanzate → Classi CSS → smart-header-dark-zone
```

### Campionamento immagini (slider)

Per le sezioni con slider Slick che usano tag `<img>` (nessun `background-image` CSS), il sistema campiona i pixel dell'immagine tramite canvas HTML5 e ne calcola la luminanza media:

```
Luminanza = (R × 299 + G × 587 + B × 114) / 1000
isDark = Luminanza / 64 < 100
```

Il risultato è cached per immagine in una `WeakMap` — il canvas viene creato **una volta sola** per ogni elemento `<img>` durante la sessione.

### Debounce alla rimozione

L'aggiunta di `bw-header-on-dark` è immediata. La rimozione è ritardata di 150ms per assorbire il momento di transizione dei carousel (le slide si muovono per ~300ms e possono restituire un elemento neutro durante il movimento).

---

## Variabili CSS esposte

Il JS scrive queste custom properties su `:root` (`<html>`):

| Variabile | Valore |
|---|---|
| `--bw-header-top-offset` | Altezza admin bar (0px se assente) |
| `--bw-header-body-padding` | Altezza header corrente |
| `--animated-banner-height` | Altezza banner animato (0px se assente/nascosto) |

Usale nel CSS del tema per calcolare offset corretti:

```css
.bw-header-spacer {
    height: var(--bw-header-body-padding);
}

.hero-section {
    padding-top: calc(var(--bw-header-top-offset) + var(--bw-header-body-padding));
}
```

---

## File del modulo

```
includes/modules/header/
├── class-bw-header-module.php          # Entry point PHP
├── frontend/
│   ├── assets.php                      # Registrazione e localizzazione asset
│   └── renderer.php                    # Render server-side dell'HTML header
└── assets/
    ├── css/
    │   └── bw-header.css               # Stili base + stati scroll/dark
    └── js/
        └── header-init.js              # Tutta la logica JS (scroll, dark zone)
```

### Configurazione JS via `bwHeaderConfig`

Il PHP inietta la configurazione tramite `wp_localize_script`:

```js
window.bwHeaderConfig = {
    breakpoint: 1024,          // px — soglia mobile/desktop
    smartScroll: true,         // bool — smart scroll attivo
    smartHeader: {
        scrollDownThreshold: 100,
        scrollUpThreshold: 0,
        scrollDelta: 1,
    }
};
```

---

## Aggiungere elementi reattivi al dark zone

Qualsiasi elemento dentro `.bw-custom-header` reagisce automaticamente a `bw-header-on-dark` tramite CSS. Esempio nel foglio di stile dell'header:

```css
/* Stato default (sfondo chiaro) */
.bw-custom-header .bw-nav-link {
    color: #000000;
}

/* Quando l'header è sopra uno sfondo scuro */
.bw-custom-header.bw-header-on-dark .bw-nav-link {
    color: #ffffff;
}
```

---

## Debugging

Apri la console del browser e ispeziona le classi sull'elemento `.bw-custom-header`:

```js
// Verifica classi applicate in tempo reale
document.querySelector('.bw-custom-header').className;

// Forza uno stato per test visivo
document.querySelector('.bw-custom-header').classList.add('bw-header-on-dark');

// Controlla cosa vede il sistema al punto corrente
(function() {
    var h = document.querySelector('.bw-custom-header');
    var r = h.getBoundingClientRect();
    h.style.pointerEvents = 'none';
    var el = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2);
    h.style.pointerEvents = '';
    console.log('Elemento dietro l\'header:', el);
    console.log('Antenati:', el && el.closest('[class]'));
})();
```

---

## Casi limite noti

| Scenario | Comportamento | Soluzione |
|---|---|---|
| Immagine di sfondo su CDN con CORS bloccato | Canvas sampling fallisce silenziosamente, euristica testo usata come fallback | Aggiungere `smart-header-dark-zone` manualmente alla sezione |
| Sezione con foto chiara (non scura) rilevata come scura | Euristica testo falso positivo se il testo è bianco per altri motivi | Non aggiungere `smart-header-dark-zone`; verificare il colore testo della sezione |
| Slider con slide miste (scure e chiare) | La slide attiva determina lo stato; transizione debounced 150ms | Comportamento corretto by design |
| Header non-sticky con dark zone | Rilevamento attivo tramite scroll listener dedicato nel `boot()` | Nessuna azione richiesta |
