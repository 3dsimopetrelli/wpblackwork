# Elementor Widget Architecture Context (wpblackwork)

## Obiettivo
Questo documento sintetizza l'architettura reale dei widget Elementor nel plugin `wpblackwork` e fornisce un blueprint pratico per creare un nuovo widget header.

## 1) Bootstrap e registrazione widget

### Entry point plugin
- File: `bw-main-elementor-widgets.php`
- Include il loader widget in:
  - `require_once __DIR__ . '/includes/class-bw-widget-loader.php';`

### Loader automatico
- File: `includes/class-bw-widget-loader.php`
- Hook usati:
  - `elementor/widgets/register`
  - `elementor/widgets/widgets_registered`
- Comportamento:
  - Scansiona `includes/widgets/class-bw-*-widget.php`
  - Richiede ogni file
  - Risolve automaticamente classi in due formati:
    - `Widget_Bw_*`
    - `BW_*_Widget`
  - Registra con API compatibile sia nuova (`register`) sia legacy (`register_widget_type`)
  - Evita doppia registrazione con flag interno `$widgets_registered`

## 2) Categoria Elementor custom
- File: `bw-main-elementor-widgets.php`
- Categoria registrata: `blackwork`
- Etichetta in editor: `Black Work Widgets`

## 3) Struttura standard di un widget nel progetto

Pattern prevalente in `includes/widgets/`:
1. Classe estende `Elementor\Widget_Base` (o classe base custom del plugin)
2. Metodi minimi:
   - `get_name()`
   - `get_title()`
   - `get_icon()`
   - `get_categories()`
   - `register_controls()`
   - `render()`
3. Metodi opzionali:
   - `get_style_depends()`
   - `get_script_depends()`
4. Controlli separati in metodi privati:
   - es. `register_content_controls()`, `register_style_controls()`

## 4) Asset architecture (CSS/JS)

### Helper comune
- File: `includes/helpers.php`
- Funzione chiave: `bw_register_widget_assets($widget_name, $js_dependencies = ['jquery'], $register_js = true)`
- Convenzione handle:
  - Style: `bw-{widget}-style`
  - Script: `bw-{widget}-script`
- Convenzione file:
  - `assets/css/bw-{widget}.css`
  - `assets/js/bw-{widget}.js`

### Registrazione/enqueue centralizzata
- File: `bw-main-elementor-widgets.php`
- Pattern:
  - `bw_register_*_widget_assets()`
  - `bw_enqueue_*_widget_assets()`
  - Hook sia frontend sia editor Elementor (`elementor/frontend/after_enqueue_scripts`, `elementor/editor/after_enqueue_scripts`)

Nota pratica:
- Molti widget registrano dipendenze sia via `get_*_depends()` sia via hook globali nel main plugin. Questo garantisce robustezza ma aumenta il coupling.

## 5) Stato attuale widget (inventario)

Widget trovati in `includes/widgets/`:
- `bw-about-menu`
- `bw-add-to-cart`
- `bw-add-to-cart-variation`
- `bw-animated-banner`
- `bw-button`
- `bw-divider`
- `bw-filtered-post-wall`
- `bw-navshop`
- `bw-presentation-slide`
- `bw-price-variation`
- `bw-product-details-table`
- `bw-product-slide`
- `bw-related-post`
- `bw-related-products`
- `bw-search`
- `bw-slick-slider`
- `bw-slide-showcase`
- `bw-static-showcase`
- `bw-tags`
- `bw-wallpost`

Totale: 20 widget.

## 6) Focus header: stato attuale

I widget header Elementor legacy sono stati rimossi.
L'header usa ora il modulo custom server-rendered:
- `includes/modules/header/header-module.php`
- `includes/modules/header/frontend/header-render.php`
- `includes/modules/header/frontend/assets.php`
- `includes/modules/header/assets/css/*`
- `includes/modules/header/assets/js/*`

Il contratto AJAX live search resta in `bw-main-elementor-widgets.php`:
- action: `bw_live_search_products`
- nonce: `bw_search_nonce`

### `BW About Menu` (`bw-about-menu`)
- File widget: `includes/widgets/class-bw-about-menu-widget.php`
- Asset:
  - `assets/css/bw-about-menu.css`
  - `assets/js/bw-about-menu.js`
- Ruolo:
  - Render menu WP (`wp_nav_menu`) con classe link custom (`bw-about-menu__link`)
  - Spotlight effect gestito con CSS variables + JS
  - In editor Elementor si reinizializza via hook `frontend/element_ready/bw-about-menu.default`

### Smart Header (sistema header globale)
- Asset:
  - `assets/css/bw-smart-header.css`
  - `assets/js/bw-smart-header.js`
- Enqueue globale frontend:
  - `bw_enqueue_smart_header_assets()` in `wp_enqueue_scripts`
- Comportamento:
  - Header fixed con classi `.visible/.hidden/.scrolled`
  - Evita caricamento in editor preview Elementor
  - Supporta dark zones (`.smart-header-dark-zone`) e testo reattivo (`.smart-header-reactive-text`)

## 7) Pattern importanti per nuovi widget header (storico)

1. Namespace CSS rigoroso:
   - prefisso univoco (`.bw-{widget}*`)
2. Supporto multi-istanza:
   - evitare selector globali non scoped
   - se overlay/popup usa `data-widget-id` + selector con `{{ID}}`
3. Compatibilita editor Elementor:
   - hook JS `frontend/element_ready/{widget}.default`
   - eventuale fallback quando dati WooCommerce non disponibili in preview
4. Asset safe-loading:
   - registrare handle prima di restituirli in `get_*_depends()`
5. Sanitizzazione output:
   - `esc_html`, `esc_attr`, `esc_url`
6. Accessibilita minima:
   - `aria-label`, focus states, gestione ESC per overlay
7. Performance:
   - debounce per ricerca live
   - abort richieste AJAX precedenti

## 8) Blueprint consigliato per nuovo widget header

### Naming
- File: `includes/widgets/class-bw-header-{nome}-widget.php`
- Classe: `BW_Header_{Nome}_Widget` (oppure `Widget_Bw_Header_{Nome}`; il loader supporta entrambi)
- `get_name()`: `bw-header-{nome}`
- Asset:
  - `assets/css/bw-header-{nome}.css`
  - `assets/js/bw-header-{nome}.js`

### Minimo contratto PHP
1. `get_name/get_title/get_icon/get_categories`
2. `get_style_depends/get_script_depends`
3. `register_controls` separato in content/style
4. `render` con markup semanticamente stabile

### Minimo contratto JS
1. Init on document ready
2. Init su hook Elementor `frontend/element_ready/bw-header-{nome}.default`
3. Guard per doppia init
4. Cleanup handlers se il widget viene re-renderizzato

## 9) Rischi concreti da evitare

1. Overlay dentro container header con `overflow:hidden`
   - soluzione: append in `body` (come `bw-search`)
2. Stili che perdono priorita in editor
   - usare selector scoped e, solo dove necessario, specificita maggiore/`!important`
3. Dipendenze non registrate
   - validare che l'handle sia `registered` prima di restituirlo
4. Coupling forte tra widget e hook globali
   - per nuovi widget preferire robustezza in `get_*_depends()` + registrazione in `init`

## 10) Checklist pre-implementazione nuovo widget header

1. Definire il comportamento funzionale (es. menu, cta, account/cart, search, sticky state)
2. Definire se serve overlay/popup
3. Definire controlli Elementor minimi (content + style)
4. Definire dipendenze JS/CSS e strategia di enqueue
5. Definire comportamento in editor Elementor vs frontend
6. Definire integrazione con smart-header (classi richieste)
7. Definire scenari di test:
   - desktop/tablet/mobile
   - editor Elementor
   - pagina frontend reale con WooCommerce attivo

---

Nota: il blueprint sopra resta utile per nuovi widget Elementor non-header. Per l'header, usare il modulo custom.
