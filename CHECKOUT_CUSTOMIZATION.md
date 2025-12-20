# WooCommerce Checkout Customization - Documentazione Completa

Questo documento descrive tutte le personalizzazioni implementate per la pagina checkout di WooCommerce nel plugin BW Elementor Widgets.

## Panoramica

La pagina checkout è stata completamente ridisegnata con un layout moderno a due colonne, utilizzando CSS Grid invece del vecchio sistema float. Il design è completamente responsive e include controlli admin per la personalizzazione.

---

## Sintesi rapida

- **Template checkout**: layout a due colonne con logo personalizzabile e titolo "Your Order" opzionale.
- **Order review**: card prodotto ridisegnate con pulsante di rimozione, quantità custom e coupon message personalizzato.
- **Stili CSS**: reset aggressivo di padding/margin/notice, griglia responsive e pulsanti lime green per la quantità.
- **JavaScript**: gestione +/- quantità e messaggi coupon custom su eventi WooCommerce.
- **Impostazioni Admin**: controlli per larghezza/padding logo e toggle del titolo riepilogo ordine.

---

## File Modificati

### 1. **Template PHP**

#### `woocommerce/templates/checkout/form-checkout.php`
**Modifiche principali:**
- Implementato layout a due colonne con CSS Grid
- Aggiunta sezione logo con padding personalizzabile tramite admin
- Implementato toggle per il titolo "Your Order"
- Rimossi wrapper non necessari
- Mantenuti tutti gli hook WooCommerce per compatibilità

**Codice chiave:**
```php
// Logo con padding inline personalizzabile
<div class="bw-checkout-logo" style="padding-top: <?php echo esc_attr($logo_padding_top); ?>px; padding-right: <?php echo esc_attr($logo_padding_right); ?>px; padding-bottom: <?php echo esc_attr($logo_padding_bottom); ?>px; padding-left: <?php echo esc_attr($logo_padding_left); ?>px;">

// Toggle condizionale per "Your Order"
<?php if ( $settings['show_order_heading'] === '1' ) : ?>
    <div class="bw-checkout-order-heading__wrap">
        <h3 id="order_review_heading" class="bw-checkout-order-heading"><?php esc_html_e( 'Your order', 'woocommerce' ); ?></h3>
    </div>
<?php endif; ?>
```

#### `woocommerce/templates/checkout/review-order.php`
**Modifiche principali:**
- Ristrutturato layout prodotti: titolo, X button (absolute positioned), prezzo
- Rimosso bottone "Edit"
- Aggiunti controlli quantità con stile lime green circolare (#c4ff0d)
- Implementato aspect ratio corretto per immagini prodotto
- Aggiunto container per messaggi coupon personalizzati

**Struttura HTML prodotto:**
```html
<div class="bw-review-item__header">
    <div class="bw-review-item__title">[Titolo prodotto]</div>
    [X button - position: absolute; top: 0; right: 0]
    <div class="bw-review-item__price">[Prezzo]</div>
</div>
```

---

### 2. **CSS Styling**

#### `assets/css/bw-checkout.css`
**Struttura completa del file:**

##### **A) Gestione Notifiche WooCommerce**
```css
/* Hide all WooCommerce notices - AGGRESSIVE */
/* 20+ selettori per nascondere tutti i banner di notifica */
```

##### **B) Force Zero Spacing - Override Theme/Plugin CSS**
```css
/* Override aggressivo di Elementor e tema */
body.woocommerce-checkout:not(.elementor-editor-active)
.woocommerce-checkout .elementor-section
.woocommerce-checkout .elementor-element-populated
.woocommerce-checkout .elementor-widget-wrap
/* Tutti forzati a padding: 0 !important; margin: 0 !important; */
```

##### **C) CSS Grid Layout**
```css
/* Desktop: min-width 900px */
.bw-checkout-grid {
    display: grid;
    grid-template-columns: 1fr 1px 480px; /* Left | Separator | Right */
    gap: 0;
}

/* Left column */
.bw-checkout-left {
    grid-column: 1 / 2;
    grid-row: 1 / 10;
}

/* Center separator */
.bw-checkout-grid::before {
    grid-column: 2 / 3;
    width: 1px;
    background: var(--bw-checkout-border-color, #e0e0e0);
}

/* Right column - sticky */
.bw-checkout-right {
    grid-column: 3 / 4;
    grid-row: 1 / 3; /* Allineato al top anche quando heading è nascosto */
    position: sticky;
    top: 20px;
}
```

##### **D) Product Card Styling**
```css
/* Immagine prodotto */
.bw-review-item__media {
    width: 120px;
    border-radius: 12px;
}

.bw-review-item__media img {
    object-fit: contain; /* Preserva aspect ratio */
}

/* X button */
.bw-review-item__remove {
    position: absolute;
    top: 0;
    right: 0;
    color: #000;
    background: transparent;
}

.bw-review-item__remove:hover {
    opacity: 0.6; /* Hover nero */
}

/* Quantity buttons - lime green circular */
.bw-qty-btn {
    width: 48px !important;
    height: 48px !important;
    background: #c4ff0d !important;
    border-radius: 50% !important;
}
```

##### **E) Coupon Form**
```css
.bw-review-coupon .checkout_coupon {
    display: flex !important;
    opacity: 1 !important;
    visibility: visible !important;
}

/* Custom message container */
.bw-coupon-message.success {
    background: #d4edda;
    color: #155724;
}
```

##### **F) Backgrounds & Spacing**
```css
/* Tutti i background forzati a transparent */
.bw-order-summary,
.bw-checkout-right table.shop_table,
.bw-review-item {
    background: transparent !important;
}

/* Tutti i padding/margin forzati a zero con !important */
```

---

### 3. **JavaScript**

#### `assets/js/bw-checkout.js`
**Funzionalità implementate:**

##### **A) Quantity Controls**
```javascript
// Increment/Decrement quantity
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('bw-qty-plus')) {
        // Incrementa quantità
    }
    if (e.target.classList.contains('bw-qty-minus')) {
        // Decrementa quantità
    }
});
```

##### **B) Custom Coupon Messages**
```javascript
function showCouponMessage(message, type) {
    var messageEl = document.getElementById('bw-coupon-message');
    messageEl.className = 'bw-coupon-message ' + type;
    messageEl.textContent = message;
    setTimeout(function() {
        messageEl.className = 'bw-coupon-message';
    }, 5000);
}

// Intercetta eventi WooCommerce
jQuery(document.body).on('applied_coupon', function() {
    showCouponMessage('Coupon applicato con successo!', 'success');
});
```

---

### 4. **Admin Settings**

#### `admin/class-blackwork-site-settings.php`
**Nuovi controlli aggiunti:**

##### **A) Logo Width Control**
```php
<label>Logo Width (px):</label>
<input type="number" name="bw_checkout_logo_width"
       value="<?php echo esc_attr($logo_width); ?>"
       min="50" max="800" />
```

##### **B) Logo Padding Controls (4 campi)**
```php
<div style="display: flex; gap: 15px; align-items: center;">
    <label>Top: <input type="number" name="bw_checkout_logo_padding_top" /></label>
    <label>Right: <input type="number" name="bw_checkout_logo_padding_right" /></label>
    <label>Bottom: <input type="number" name="bw_checkout_logo_padding_bottom" /></label>
    <label>Left: <input type="number" name="bw_checkout_logo_padding_left" /></label>
</div>
```

##### **C) "Your Order" Heading Toggle**
```php
<label>
    <input type="checkbox" name="bw_checkout_show_order_heading" value="1"
           <?php checked($show_order_heading, '1'); ?> />
    Show "Your Order" heading
</label>
```

**Default values:**
```php
$defaults = [
    'logo_width' => 200,
    'logo_padding_top' => 0,
    'logo_padding_right' => 0,
    'logo_padding_bottom' => 0,
    'logo_padding_left' => 0,
    'show_order_heading' => '1',
];
```

#### `woocommerce/woocommerce-init.php`
**Funzione settings getter aggiornata:**
```php
function bw_mew_get_checkout_settings() {
    $settings = [
        'logo' => esc_url_raw(get_option('bw_checkout_logo', $defaults['logo'])),
        'logo_width' => absint(get_option('bw_checkout_logo_width', $defaults['logo_width'])),
        'logo_padding_top' => absint(get_option('bw_checkout_logo_padding_top', 0)),
        'logo_padding_right' => absint(get_option('bw_checkout_logo_padding_right', 0)),
        'logo_padding_bottom' => absint(get_option('bw_checkout_logo_padding_bottom', 0)),
        'logo_padding_left' => absint(get_option('bw_checkout_logo_padding_left', 0)),
        'show_order_heading' => get_option('bw_checkout_show_order_heading', '1'),
        // ... altri settings
    ];
    return $settings;
}
```

---

## Caratteristiche Tecniche

### Layout System
- **Metodo**: CSS Grid (invece di float)
- **Breakpoint**: 900px
- **Desktop Grid**: `1fr | 1px | 480px` (Left | Separator | Right)
- **Mobile**: Stacked layout (block display)

### Sticky Positioning
- Colonna destra sticky con `position: sticky; top: 20px`
- Sempre allineata al top, anche quando "Your Order" heading è nascosto
- Grid positioning: `grid-row: 1 / 3` per garantire allineamento

### Zero Spacing Strategy
**3 livelli di override:**
1. **Reset base**: `.bw-checkout-wrapper`, `.bw-checkout-left`, `.bw-checkout-right`
2. **Override Elementor**: `body.woocommerce-checkout`, `.elementor-element-populated`, `.elementor-widget-wrap`
3. **Force interno**: Ogni elemento interno (tables, rows, items) con `!important`

### Color Variables
```css
--bw-checkout-left-bg: #ffffff (default)
--bw-checkout-right-bg: #f7f7f7 (default)
--bw-checkout-border-color: #e0e0e0 (default)
```

### Responsive Design
```css
/* Mobile: max-width 899px */
- Logo max-width: 150px
- Image width: 90px (invece di 120px)
- Quantity buttons: 40px (invece di 48px)
- Font sizes ridotti (15px → 14px)
```

---

## Problemi Risolti

### 1. **Layout Destruction dopo Coupon Apply**
**Problema**: WooCommerce AJAX inseriva banner di notifica che rompevano CSS Grid
**Soluzione**:
- Nascosti tutti i banner con 20+ selettori aggressivi
- Protezione Grid: `> *:not(.bw-checkout-left):not(.bw-checkout-right) { display: none !important; }`
- Messaggi coupon custom via JavaScript

### 2. **Padding/Margin da Elementor**
**Problema**: Elementor e tema aggiungevano padding indesiderati
**Soluzione**:
- Override specifico per `body.woocommerce-checkout:not(.elementor-editor-active)`
- Target di tutte le classi Elementor (section, element, populated, widget-wrap)
- Forzatura con `!important` su ogni regola

### 3. **Colonna Destra al Bottom**
**Problema**: Grid positioning errato faceva apparire la colonna destra in fondo
**Soluzione**:
- Posizionamento esplicito: `grid-column: 3 / 4; grid-row: 1 / 3`
- `align-self: start` per garantire allineamento top

### 4. **Coupon Form Scomparso**
**Problema**: CSS o WooCommerce nascondevano il form coupon dopo AJAX
**Soluzione**:
```css
.bw-review-coupon .checkout_coupon {
    display: flex !important;
    opacity: 1 !important;
    visibility: visible !important;
    height: auto !important;
    overflow: visible !important;
}
```

### 5. **White Backgrounds Indesiderati**
**Problema**: Background bianchi su order summary box e tabelle
**Soluzione**: Forzatura `background: transparent !important` su:
- `.bw-order-summary`
- `table.shop_table` e tutti i suoi elementi (tbody, tfoot, tr, th, td)
- `.bw-review-item`

---

## Testing Checklist

### Funzionalità da Testare
- [ ] Layout due colonne funziona su desktop (>900px)
- [ ] Layout stacked funziona su mobile (<900px)
- [ ] Colonna destra rimane sticky durante scroll
- [ ] Logo si ridimensiona correttamente (width control)
- [ ] Padding logo applicato correttamente (4 controlli)
- [ ] Toggle "Your Order" mostra/nasconde il titolo
- [ ] Nessun padding/margin indesiderato visibile in DevTools
- [ ] Quantity buttons +/- funzionano
- [ ] Quantity buttons sono lime green circolari (#c4ff0d)
- [ ] X button per rimuovere prodotto funziona
- [ ] X button è nero con hover opacity 0.6
- [ ] Immagini prodotto mantengono aspect ratio (object-fit: contain)
- [ ] Coupon apply mostra messaggio custom (non banner WooCommerce)
- [ ] Coupon remove funziona correttamente
- [ ] Nessun banner WooCommerce visibile (success/error/info)
- [ ] Background trasparenti su order summary
- [ ] Separatore centrale visibile e corretto
- [ ] Form payment e place order funzionano
- [ ] AJAX update checkout non rompe il layout

### Browser Testing
- [ ] Chrome/Edge (Chromium)
- [ ] Firefox
- [ ] Safari (macOS/iOS)
- [ ] Mobile devices (responsive)

---

## Manutenzione Futura

### Se si Aggiunge un Nuovo Campo al Checkout
1. Verificare che non rompa il Grid layout
2. Aggiungere padding/margin zero se necessario
3. Testare su mobile

### Se WooCommerce Cambia Struttura HTML
1. Verificare selettori CSS in `bw-checkout.css`
2. Aggiornare selettori di nascondimento notifiche se necessario
3. Verificare compatibilità con `review-order.php`

### Se si Cambia Tema
1. Verificare CSS variables `--bw-checkout-*`
2. Verificare override di `body.woocommerce-checkout`
3. Aggiungere nuovi selettori se il tema forza padding/margin

---

## Performance Notes

- **CSS Grid** è più performante di float
- **Sticky positioning** non richiede JavaScript
- **Transient caching** non utilizzato (non necessario per checkout)
- **Asset loading** condizionale su pagina checkout
- **File versioning** via `filemtime()` per cache-busting

---

## Compatibilità

- ✅ WordPress 5.8+
- ✅ WooCommerce 5.0+
- ✅ Elementor 3.0+
- ✅ PHP 7.4+
- ✅ Tutti i browser moderni (CSS Grid support)

---

## Riferimenti Codice

### File Structure
```
wpblackwork/
├── woocommerce/
│   ├── templates/checkout/
│   │   ├── form-checkout.php          [Layout principale]
│   │   └── review-order.php           [Order summary]
│   └── woocommerce-init.php           [Settings getter]
├── assets/
│   ├── css/bw-checkout.css            [Tutti gli stili]
│   └── js/bw-checkout.js              [Quantity & Coupon logic]
└── admin/
    └── class-blackwork-site-settings.php [Admin controls]
```

### Key CSS Classes
```
.bw-checkout-wrapper           → Container principale
.bw-checkout-grid              → CSS Grid container
.bw-checkout-left              → Colonna sinistra (form)
.bw-checkout-right             → Colonna destra (order summary)
.bw-checkout-order-heading__wrap → Wrapper titolo "Your Order"
.bw-order-summary              → Order summary box
.bw-review-item                → Singolo prodotto
.bw-review-item__remove        → X button
.bw-qty-btn                    → Quantity +/- buttons
.bw-review-coupon              → Coupon form
.bw-coupon-message             → Custom coupon message
```

---

## Credits

**Sviluppato per**: BlackWork Theme
**Plugin**: BW Elementor Widgets
**Versione**: 1.0
**Data ultima modifica**: Dicembre 2025

---

## Link Utili

- **Admin Settings**: `Blackwork > Site Settings > Checkout`
- **CSS Variables**: Definite in `bw-checkout.css` linea 1-10
- **WooCommerce Hooks**: Tutti preservati in `form-checkout.php`
- **Documentazione WooCommerce**: https://woocommerce.com/document/template-structure/

---

## Note Finali

Questa customizzazione è stata progettata per essere:
- **Maintainable**: Codice ben commentato e strutturato
- **Extensible**: Facile aggiungere nuovi controlli admin
- **Compatible**: Non modifica core WooCommerce, solo template override
- **Performance-first**: CSS moderno, no JavaScript pesante
- **User-friendly**: Controlli admin intuitivi

Per qualsiasi modifica futura, consultare questo documento e i commenti nel codice.
