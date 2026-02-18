# BW Checkout — Guida Completa

**Ultimo aggiornamento:** 2026-02-18
**Plugin:** BW Elementor Widgets
**Tema:** BlackWork

Questo documento è la fonte di verità unica per tutto ciò che riguarda la pagina checkout custom. Sostituisce tutti i precedenti file dispersi (`CHECKOUT_CUSTOMIZATION.md`, `CHECKOUT_REDESIGN_SUMMARY.md`, `CHECKOUT_UX_FIXES.md`, `CHECKOUT_FIX_SUMMARY.md`, `CHECKOUT_CSS_AUDIT.md`, `CHECKOUT_CSS_BEFORE_AFTER.md`, `CHECKOUT_CSS_REVIEW_INDEX.md`, `CHECKOUT_CSS_VISUAL_EXPLANATION.md`).

---

## Indice

1. [Panoramica e Architettura](#1-panoramica-e-architettura)
2. [File Coinvolti](#2-file-coinvolti)
3. [Header Minimale](#3-header-minimale)
4. [Layout a Due Colonne — Colonna Sinistra](#4-layout-a-due-colonne--colonna-sinistra)
5. [Campi del Form — Colonna Sinistra](#5-campi-del-form--colonna-sinistra)
6. [Sezione Pagamento — payment.php](#6-sezione-pagamento--paymentphp)
7. [Order Review — Colonna Destra](#7-order-review--colonna-destra)
8. [Prodotti nel Carrello (review-order.php)](#8-prodotti-nel-carrello-review-orderphp)
9. [Controlli Quantità](#9-controlli-quantità)
10. [Coupon — Form e Logica AJAX](#10-coupon--form-e-logica-ajax)
11. [Calcoli e Totali](#11-calcoli-e-totali)
12. [Sticky della Colonna Destra](#12-sticky-della-colonna-destra)
13. [Responsive Design](#13-responsive-design)
14. [JavaScript — bw-checkout.js](#14-javascript--bw-checkoutjs)
15. [Impostazioni Admin](#15-impostazioni-admin)
16. [CSS Variables e Temi](#16-css-variables-e-temi)
17. [Integrazione Stripe](#17-integrazione-stripe)
18. [Fix Tecnici Applicati](#18-fix-tecnici-applicati)
19. [Checklist di Test](#19-checklist-di-test)
20. [Manutenzione Futura](#20-manutenzione-futura)

---

## 1. Panoramica e Architettura

La pagina checkout è una **sovrascrittura completa** di WooCommerce, costruita su CSS Grid. Non usa il layout float di WooCommerce.

### Struttura HTML globale

```
body.woocommerce-checkout
├── .bw-minimal-checkout-header          ← Header custom (logo + cart icon)
└── form.bw-checkout-form
    └── .bw-checkout-wrapper             ← max-width: 980px, centrato
        └── .bw-checkout-grid            ← CSS Grid a 3 colonne [left | sep | right]
            ├── .bw-checkout-left        ← Colonna 1: form + pagamento
            ├── ::before (separator)     ← 1px linea verticale
            ├── .bw-checkout-order-heading__wrap   ← "Your Order" (opzionale)
            └── .bw-checkout-right       ← Colonna 2: order summary (sticky)
```

### Tecnica CSS Grid desktop

```css
/* @media (min-width: 900px) */
.bw-checkout-grid {
    display: grid;
    grid-template-columns: minmax(0, var(--bw-checkout-left-col))  /* es. 62% */
                           1px                                      /* separatore */
                           minmax(0, var(--bw-checkout-right-col)); /* es. 38% */
}
.bw-checkout-left  { grid-column: 1 / 2; grid-row: 1 / 10; }
.bw-checkout-grid::before { grid-column: 2 / 3; /* separatore verticale */ }
.bw-checkout-right { grid-column: 3 / 4; grid-row: 1 / 3; }
```

### Mobile (< 900px)

Layout a blocchi sovrapposti: prima appare il toggle "Your order" con il totale, poi al click si apre il pannello dell'order summary. Poi vengono i campi del form.

---

## 2. File Coinvolti

### Template PHP (override WooCommerce)

| File | Ruolo |
|------|-------|
| `woocommerce/templates/checkout/form-checkout.php` | Layout principale, CSS Grid, header, impostazioni |
| `woocommerce/templates/checkout/review-order.php` | Prodotti, coupon form, totali |
| `woocommerce/templates/checkout/form-coupon.php` | Form coupon con floating label |
| `woocommerce/templates/checkout/payment.php` | Sezione pagamento stile accordion |
| `woocommerce/templates/checkout/form-billing.php` | Campi billing |
| `woocommerce/templates/checkout/form-shipping.php` | Campi shipping |
| `woocommerce/templates/checkout/order-received.php` | Pagina conferma ordine |

### CSS

| File | Contenuto |
|------|-----------|
| `assets/css/bw-checkout.css` | Tutto lo stile del checkout (~1900 righe) |
| `assets/css/bw-checkout-fields.css` | Layout campi (flex grid, half-width, full-width) |
| `assets/css/bw-checkout-notices.css` | Stile messaggi/notifiche |
| `assets/css/bw-checkout-subscribe.css` | Newsletter subscribe nel checkout |

### JavaScript

| File | Contenuto |
|------|-----------|
| `assets/js/bw-checkout.js` | Quantità, coupon AJAX, sticky, floating labels, Stripe fixes |
| `assets/js/bw-checkout-notices.js` | Gestione notifiche WooCommerce |

### PHP Backend

| File | Funzione |
|------|----------|
| `woocommerce/woocommerce-init.php` | `bw_mew_get_checkout_settings()`, AJAX coupon handlers, Stripe appearance |
| `admin/class-blackwork-site-settings.php` | Admin panel Checkout Tab |
| `includes/admin/checkout-fields/class-bw-checkout-fields-admin.php` | Admin dei campi custom |
| `includes/admin/checkout-fields/class-bw-checkout-fields-frontend.php` | Rendering campi frontend |

---

## 3. Header Minimale

Il tema nasconde header e footer nativi sulla pagina checkout. Viene usato un header custom minimale.

### Come funziona

In `form-checkout.php`:
```php
if (function_exists('bw_mew_render_checkout_header')) {
    bw_mew_render_checkout_header();
}
```

### CSS — `bw-checkout.css`

```css
/* Nasconde header/footer del tema */
body.woocommerce-checkout:not(.elementor-editor-active) header,
body.woocommerce-checkout:not(.elementor-editor-active) footer,
body.woocommerce-checkout:not(.elementor-editor-active) .woocommerce-breadcrumb,
body.woocommerce-checkout:not(.elementor-editor-active) .page-title { display: none !important; }

/* Header minimale */
.bw-minimal-checkout-header {
    width: 100%;
    background: #ffffff;
    border-bottom: 1px solid #e0e0e0;
    z-index: 100;
}
.bw-minimal-checkout-header__inner {
    max-width: 980px;
    margin: 0 auto;
    padding: 20px 16px;
    display: flex;
    align-items: center;
}
```

### Allineamento Logo (configurabile via Admin)

- `--left`: logo a sinistra (default)
- `--center`: logo centrato
- `--right`: logo a destra, con cart icon assoluto a destra

### Responsive header

```css
@media (max-width: 768px) {
    .bw-minimal-checkout-header__inner { padding: 16px 12px; }
    .bw-minimal-checkout-header__cart svg { width: 20px; height: 20px; }
}
```

---

## 4. Layout a Due Colonne — Colonna Sinistra

### CSS Grid Desktop (`@media min-width: 900px`)

```css
body.woocommerce-checkout .bw-checkout-grid {
    display: grid;
    grid-template-columns: minmax(0, var(--bw-checkout-left-col, 62%))
                           1px
                           minmax(0, var(--bw-checkout-right-col, 38%));
    gap: 0;
    align-items: start;
}
```

La colonna sinistra occupa di default il **62%** della larghezza, la colonna destra il **38%**. Entrambi i valori sono configurabili dall'admin.

### Separatore verticale centrale

Realizzato con lo pseudo-elemento `::before` del grid container:

```css
body.woocommerce-checkout .bw-checkout-grid::before {
    content: "";
    grid-column: 2 / 3;
    grid-row: 1 / 10;
    width: 1px;
    background: var(--bw-checkout-border-color, #262626);
    height: 100%;
}
```

L'**estensione del background destro** (`::after`) usa un trick per colorare l'intera area destra fino al bordo del viewport:

```css
body.woocommerce-checkout .bw-checkout-grid::after {
    content: "";
    position: absolute;
    top: 0;
    left: var(--bw-checkout-left-col, 62%);
    width: calc(50vw + 50% - var(--bw-checkout-left-col, 62%));
    height: 100%;
    background: var(--bw-checkout-right-bg, transparent);
    z-index: 0;
    pointer-events: none;
}
```

### Colonna sinistra desktop

```css
body.woocommerce-checkout .bw-checkout-left {
    background: var(--bw-checkout-left-bg, #ffffff);
    padding: 35px 28px 0 0;
    grid-column: 1 / 2;
    grid-row: 1 / 10;
    position: relative;
    z-index: 1;
}
```

### Protezione del Grid da elementi estranei

WooCommerce inietta elementi (banner notifiche, separatori Stripe) direttamente nel form. Vengono nascosti:

```css
.bw-checkout-grid > *:not(.bw-checkout-left)
                    :not(.bw-checkout-right)
                    :not(.bw-checkout-order-heading__wrap)
                    :not(.bw-order-summary-toggle)
                    :not(.bw-express-divider)
                    :not(#wc-stripe-payment-request-wrapper)
                    :not(#wc-stripe-express-checkout-element)
                    /* ... altri separatori Stripe/WCPay */ {
    display: none !important;
    position: absolute !important;
    left: -9999px !important;
}
```

---

## 5. Campi del Form — Colonna Sinistra

### Struttura HTML

```html
<div id="customer_details" class="bw-checkout-customer-details">
    <!-- woocommerce_checkout_billing hook → form-billing.php -->
    <!-- woocommerce_checkout_shipping hook → form-shipping.php -->
</div>
```

### Stile dei campi input

```css
.bw-checkout-wrapper input[type="text"],
.bw-checkout-wrapper input[type="email"],
.bw-checkout-wrapper input[type="tel"],
.bw-checkout-wrapper input[type="password"],
.bw-checkout-wrapper select,
.bw-checkout-wrapper textarea {
    background: #ffffff;
    border: 1px solid #dedede;
    border-radius: 12px;
    padding: 13px 14px;
    color: #111111;
    font-size: 14px;
    min-height: 44px;
}
```

**Focus state:**
```css
.bw-checkout-wrapper input:focus,
.bw-checkout-wrapper select:focus {
    border-color: #7e3cfd;
    box-shadow: 0 0 0 1px rgba(126, 60, 253, 0.2);
}
```

### Floating Labels sui campi

Lo script `bw-checkout.js` trasforma i campi standard WooCommerce in **floating label fields**. Al focus, la label si sposta in alto e rimpicciolisce:

```javascript
function initCheckoutFloatingLabels() {
    var fieldSelectors = [
        '#billing_first_name', '#billing_last_name', '#billing_email',
        '#billing_phone', '#billing_address_1', '#billing_city',
        '#billing_postcode', '#billing_country', '#billing_state',
        '#shipping_first_name', /* ... */
    ];
    // Per ogni campo: wrappa in .bw-field-wrapper, aggiunge label floating
}
```

### Layout campi — `bw-checkout-fields.css`

Attivato dalla classe `bw-checkout-fields-active` sul body:

```css
/* Campo full width (default) */
.bw-checkout-field { flex: 0 0 100%; }

/* Campo metà larghezza */
.bw-checkout-field--half { flex: 0 0 calc(50% - 8px); }

/* Su mobile: tutti full width */
@media (max-width: 768px) {
    .bw-checkout-field--half { flex: 0 0 100%; }
}
```

### Heading billing nascondibile

```css
body.woocommerce-checkout.bw-hide-billing-heading .woocommerce-billing-fields > h3 {
    display: none;
}
```

### Express Checkout Buttons (Stripe Apple Pay / Google Pay)

I pulsanti express sono posizionati nella colonna sinistra prima dei campi, con layout a 2 colonne:

```css
body.woocommerce-checkout #wc-stripe-express-checkout-element {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 12px !important;
}

/* Ogni bottone occupa 50% */
body.woocommerce-checkout [id*="wc-stripe-express-checkout-element-applePay"],
body.woocommerce-checkout [id*="wc-stripe-express-checkout-element-googlePay"] {
    flex: 1 1 calc(50% - 6px) !important;
    height: 48px !important;
}
```

Separatore "OR" tra i pulsanti express e il form:

```html
<div class="bw-express-divider"><span>or</span></div>
```

---

## 6. Sezione Pagamento — payment.php

### Struttura generale

Il template `payment.php` è completamente ridisegnato con stile accordion. Ogni gateway di pagamento è una card cliccabile.

```html
<div id="payment" class="woocommerce-checkout-payment">
    <h2 class="bw-payment-section-title">Payment</h2>
    <p class="bw-payment-section-subtitle">All transactions are secure and encrypted.</p>

    <ul class="bw-payment-methods wc_payment_methods">
        <li class="bw-payment-method payment_method_{gateway_id}">
            <div class="bw-payment-method__header">
                <input type="radio" id="payment_method_{id}" name="payment_method" />
                <label class="bw-payment-method__label">
                    <span class="bw-payment-method__title">{Gateway Title}</span>
                </label>
            </div>
            <div class="bw-payment-method__content payment_box is-open">
                <div class="bw-payment-method__inner">
                    <!-- Fields / descrizione / redirect message -->
                </div>
            </div>
        </li>
    </ul>

    <!-- Place Order button area -->
    <div class="form-row place-order">
        <div class="bw-mobile-total-row">
            <span class="bw-mobile-total-label">Total</span>
            <span class="bw-mobile-total-amount">—</span>
        </div>
        <button class="bw-place-order-btn" id="place_order">
            <span class="bw-place-order-btn__text">Place order</span>
        </button>
    </div>
</div>
```

### Gestione gateway speciali

| Gateway | Comportamento speciale |
|---------|----------------------|
| **Stripe (card)** | Wrapper `.bw-stripe-fields-wrapper` per fields |
| **PayPal/PPCP** | Nessuna descrizione nativa — mostra `.bw-paypal-redirect` con messaggio redirect |
| **Google Pay** | Mostra icona del gateway + `.bw-google-pay-info` con messaggio redirect |
| **Gateway senza fields** | Mostra `.bw-payment-method__selected-indicator` con check verde |

### Ordine free (coupon al 100%)

Se l'ordine è gratuito, JS aggiunge la classe `bw-free-order` al body:

```css
body.woocommerce-checkout.bw-free-order .bw-payment-section-title,
body.woocommerce-checkout.bw-free-order .bw-payment-section-subtitle,
body.woocommerce-checkout.bw-free-order #payment .payment_methods { display: none !important; }

body.woocommerce-checkout.bw-free-order .bw-free-order-banner { display: block !important; }
```

### Mobile total row

Prima del bottone "Place order" appare una riga con il totale (visibile solo su mobile):
```html
<div class="bw-mobile-total-row">
    <span class="bw-mobile-total-label">Total</span>
    <span class="bw-mobile-total-amount">—</span>  <!-- aggiornato da JS -->
</div>
```

```css
.bw-mobile-total-row { display: none; } /* nascosto di default */
@media (max-width: 899px) {
    body.woocommerce-checkout .bw-mobile-total-row { display: flex; }
}
```

---

## 7. Order Review — Colonna Destra

### Struttura HTML della colonna destra

```html
<!-- "Your Order" heading opzionale -->
<div class="bw-checkout-order-heading__wrap">
    <h3 class="bw-checkout-order-heading">Your order</h3>
</div>

<!-- Colonna destra sticky -->
<div class="bw-checkout-right" id="order_review">
    <div id="order_review_inner" class="woocommerce-checkout-review-order">
        <!-- hook woocommerce_checkout_order_review → review-order.php -->
    </div>
</div>
```

### CSS colonna destra desktop

```css
body.woocommerce-checkout .bw-checkout-right {
    grid-column: 3 / 4;
    grid-row: 1 / 3;
    position: relative;
    margin-top: var(--bw-checkout-right-margin-top, 0px);
    padding: var(--bw-checkout-right-pad-top)
             var(--bw-checkout-right-pad-right)
             var(--bw-checkout-right-pad-bottom)
             var(--bw-checkout-right-pad-left, 28px);
    background: var(--bw-checkout-right-bg, transparent);
    align-self: start;
    z-index: 1;
}
```

### Table struttura (review-order.php)

La tabella usa `table-layout: fixed` (fix critico per stabilità thumbnail):

```css
.bw-checkout-right table.shop_table {
    width: 100%;
    table-layout: fixed; /* CRITICO: impedisce ricalcolo colonne quando cambiano coupon */
    border: none;
    background: transparent !important;
    margin: 0;
}
```

---

## 8. Prodotti nel Carrello (review-order.php)

### Struttura HTML di ogni prodotto

```html
<tr class="cart_item bw-review-item" data-cart-item="{cart_item_key}">
    <!-- Cella thumbnail -->
    <td class="product-thumbnail"
        style="width: {thumb_width}px; min-width: {thumb_width}px;
               max-width: {thumb_width}px; padding-right: 0; padding-bottom: 15px;">
        <div class="bw-review-item__media"
             style="width: {thumb_width}px; aspect-ratio: {thumb_aspect};">
            <a href="..."><img src="..." alt="..."></a>
        </div>
    </td>

    <!-- Cella contenuto -->
    <td class="product-name" style="padding-left: 15px;">
        <div class="bw-review-item__content">
            <div class="bw-review-item__header">
                <div class="bw-review-item__title">Nome prodotto</div>
                <!-- X button (nascosto via CSS) -->
                <a class="bw-review-item__remove remove" ...>&times;</a>
                <div class="bw-review-item__price">€ 100,00</div>
            </div>
            <div class="bw-review-item__controls">
                <div class="bw-qty-control">
                    <!-- Quantità o badge "Sold individually" -->
                </div>
                <a class="bw-review-item__remove-text remove" ...>Remove</a>
            </div>
        </div>
    </td>
</tr>
```

### Thumbnail — impostazioni configurabili dall'Admin

| Setting | Default | Opzioni |
|---------|---------|---------|
| `thumb_width` | 110px | qualsiasi numero intero |
| `thumb_ratio` | `square` (1:1) | `square`, `portrait` (2:3), `landscape` (3:2) |

Le dimensioni sono passate via **inline styles** per evitare conflitti CSS. La `table-layout: fixed` garantisce che la colonna thumbnail non venga espansa dal browser.

### CSS immagine prodotto

```css
body.woocommerce-checkout .bw-checkout-right .bw-review-item__media {
    overflow: hidden;
    border-radius: 12px;
    border: 1px solid #e1e1e1;
    position: relative;
    box-sizing: border-box; /* include il border nel calcolo della larghezza */
}

body.woocommerce-checkout .bw-checkout-right .bw-review-item__media img {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    object-fit: cover;
    object-position: center;
}
```

### Titolo e prezzo prodotto

```css
.bw-review-item__title {
    flex: 1;
    font-weight: 600;
    font-size: 14px;
    color: #111111;
}
.bw-review-item__price {
    font-weight: 700;
    font-size: 14px;
    white-space: nowrap;
    margin-left: auto;
}
```

Il prezzo è il **subtotale della riga** (prezzo × quantità), aggiornato automaticamente da WooCommerce AJAX.

### Bottone "Remove" (testo grigio)

Il pulsante X (`bw-review-item__remove`) è nascosto via CSS. Viene mostrato il link testuale grigio:

```css
/* X button: nascosto */
body.woocommerce-checkout .bw-checkout-right .bw-review-item__remove {
    display: none;
}

/* Link "Remove" testuale */
a.bw-review-item__remove-text.remove {
    font-size: 11px !important;
    color: #999999 !important;
    text-decoration: none !important;
}
a.bw-review-item__remove-text.remove:hover {
    text-decoration: underline !important;
}
```

**Comportamento JS (rimozione prodotto):** cliccare "Remove" imposta la quantità a 0 nell'input nascosto e triggera `change`, che WooCommerce AJAX intercetta per aggiornare il carrello.

### Badge "Sold individually"

Per i prodotti con `is_sold_individually()`:

```html
<span class="bw-qty-badge">Sold individually</span>
```

```css
.bw-qty-badge {
    padding: 3px 12px;
    background: #e0e0e0;
    border-radius: 10px;
    font-size: 11px;
    color: #000000;
    font-weight: 700;
}
```

---

## 9. Controlli Quantità

### Struttura HTML

```html
<div class="bw-qty-shell">
    <button type="button" class="bw-qty-btn bw-qty-btn--minus">-</button>
    <input type="number" class="qty" name="cart[{key}][qty]" value="1" min="0" />
    <button type="button" class="bw-qty-btn bw-qty-btn--plus">+</button>
</div>
```

### CSS Checkout-Specific (override ultra-specifico)

```css
/* Contenitore */
body.woocommerce-checkout .bw-checkout-right .bw-qty-shell {
    display: inline-flex;
    align-items: center;
    height: 25px;
    border: 1px solid #000000;
    border-radius: 10px;
    overflow: hidden;
    background: #ffffff;
}

/* Bottoni */
body.woocommerce-checkout .bw-checkout-right .bw-qty-btn {
    width: 24px;
    min-width: 24px;
    height: 25px;
    border: none;
    background: transparent;
    font-size: 14px !important;
    color: #000000 !important;
}

body.woocommerce-checkout .bw-checkout-right .bw-qty-btn:hover { background: #f5f5f5; }
body.woocommerce-checkout .bw-checkout-right .bw-qty-btn:active { background: #ebebeb; }

/* Input numerico */
body.woocommerce-checkout .bw-checkout-right .bw-qty-shell input.qty {
    width: 32px;
    height: 25px;
    border: none;
    text-align: center;
    font-size: 13px;
    background: transparent;
    -moz-appearance: textfield;
}
/* Nasconde le frecce native del browser */
input.qty::-webkit-outer-spin-button,
input.qty::-webkit-inner-spin-button { -webkit-appearance: none; }
```

### Logica JS — aggiornamento quantità

```javascript
function updateQuantity(input, delta) {
    var current = parseFloat(input.value || 0);
    var step = parseFloat(input.getAttribute('step')) || 1;
    var min = input.hasAttribute('min') ? parseFloat(input.getAttribute('min')) : 0;
    var max = input.hasAttribute('max') ? parseFloat(input.getAttribute('max')) : Infinity;
    var next = Math.max(min, Math.min(max, current + delta * step));

    if (next !== current) {
        input.value = next;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }
}
```

Il click su `+` o `-` → chiama `updateQuantity` → triggera `change` → WooCommerce AJAX aggiorna il carrello.

### Responsive quantità

```css
@media (max-width: 899px) {
    body.woocommerce-checkout .bw-checkout-right .bw-qty-btn {
        width: 22px;
        min-width: 22px;
        font-size: 13px !important;
    }
    body.woocommerce-checkout .bw-checkout-right .bw-qty-shell input.qty {
        width: 30px;
        font-size: 12px;
    }
}
```

---

## 10. Coupon — Form e Logica AJAX

### Template form-coupon.php

Il form coupon custom ha un **floating label** e un bottone "Apply" separato:

```html
<div class="checkout_coupon woocommerce-form-coupon">
    <div class="bw-coupon-fields">
        <div class="bw-coupon-input-wrapper">
            <input type="text" name="coupon_code" class="bw-coupon-code-input" />
            <label class="bw-floating-label"
                   data-full="Enter coupon code"
                   data-short="Coupon code">Enter coupon code</label>
        </div>
        <button type="button" class="bw-apply-button" name="apply_coupon">Apply</button>
    </div>
    <div class="bw-coupon-error" style="display:none;"></div>
    <input type="hidden" name="woocommerce-apply-coupon-nonce" value="{nonce}" />
</div>
```

### CSS Floating Label Coupon

```css
/* Input */
.bw-review-coupon input[type="text"] {
    padding: 20px 18px 10px 18px !important; /* spazio per la label sopra */
    border: 1px solid #e0e0e0 !important;
    border-radius: 8px !important;
    background: #f8f8f8 !important;
}
.bw-review-coupon input[type="text"]:focus {
    border: 2px solid #000 !important;
    background: #fff;
}

/* Label centrata (stato default) */
.bw-review-coupon .bw-floating-label {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 15px;
    color: #999;
    pointer-events: none;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Label in alto (quando ha focus o valore) */
.bw-review-coupon input[type="text"]:focus ~ .bw-floating-label,
.bw-review-coupon .bw-coupon-input-wrapper.has-value .bw-floating-label {
    top: 8px;
    transform: translateY(0);
    font-size: 11px;
    color: #666;
}
```

### Bottone Apply — stato attivo/inattivo

```css
/* Default: grigio inattivo */
.bw-review-coupon .button {
    background: #c0c0c0 !important;
    color: #fff !important;
}

/* Attivo quando input ha valore */
.bw-review-coupon .bw-coupon-input-wrapper.has-value ~ .bw-apply-button {
    background: #000 !important;
    color: #fff !important;
    font-weight: 700 !important;
}
```

### Logica AJAX — Applicazione Coupon

**Problema risolto:** WooCommerce intercettava la submit del form coupon e faceva partire la validazione completa del checkout (inclusa Stripe), bloccando l'utente che non aveva ancora inserito i dati di pagamento.

**Soluzione:** Il bottone ha `type="button"` (non `submit`) e l'invio è completamente AJAX:

```javascript
// Global delegation — sopravvive a clonazione e DOM updates
jQuery(document.body).on('click.bwCoupon', '.bw-apply-button', function(e) {
    e.preventDefault();
    e.stopPropagation();
    handleCouponSubmission(inputElement);
});

function handleCouponSubmission(inputElement) {
    var couponCode = inputElement.value.trim();
    if (!couponCode) { showErr('Please enter a coupon code'); return; }

    setOrderSummaryLoading(true);

    jQuery.ajax({
        type: 'POST',
        url: bwCheckoutParams.ajax_url,
        data: {
            action: 'bw_apply_coupon',
            nonce: bwCheckoutParams.nonce,
            coupon_code: couponCode
        },
        success: function(response) {
            if (response.success) {
                jQuery(document.body).trigger('update_checkout');
                showCouponMessage('Coupon applied successfully', 'success');
                // Svuota tutti gli input coupon (desktop + mobile)
                document.querySelectorAll('.bw-coupon-code-input').forEach(function(inp) {
                    inp.value = '';
                    if (inp.updateHasValue) inp.updateHasValue();
                });
            } else {
                setOrderSummaryLoading(false);
                showErr(response.data.message || 'Invalid coupon code');
            }
        }
    });
}
```

### Logica AJAX — Rimozione Coupon

**Problema risolto:** dopo la rimozione del coupon, WooCommerce riapplicava il coupon dalla sessione al page reload.

**Soluzione:** AJAX personalizzato con `persistent_cart_update()` + `session->save_data()` + redirect GET (invece di `reload()` che riproduceva il POST):

```javascript
document.addEventListener('click', function(event) {
    var couponRemove = event.target.closest('.woocommerce-remove-coupon');
    if (!couponRemove) return;

    event.preventDefault();
    var couponCode = couponRemove.getAttribute('data-coupon');
    setOrderSummaryLoading(true);

    jQuery.ajax({
        type: 'POST',
        url: bwCheckoutParams.ajax_url,
        data: { action: 'bw_remove_coupon', nonce: bwCheckoutParams.nonce, coupon: couponCode },
        success: function(response) {
            if (response.success) {
                // Redirect GET per evitare POST replay (che riapplicherebbe il coupon)
                window.location.href = window.location.pathname + window.location.search;
            }
        }
    });
});
```

**PHP handler (woocommerce-init.php):**
```php
function bw_mew_ajax_remove_coupon() {
    WC()->cart->remove_coupon($coupon_code);
    WC()->cart->calculate_totals();
    WC()->cart->persistent_cart_update(); // CRITICO: persiste in DB
    WC()->session->save_data();           // CRITICO: salva in sessione
    wp_send_json_success();
}
```

### Messaggi Coupon Custom

```javascript
function showCouponMessage(message, type) {
    var messageEl = document.getElementById('bw-coupon-message');
    messageEl.className = 'bw-coupon-message ' + type;
    messageEl.textContent = message;
    messageEl.style.opacity = '1';
    // Auto-nasconde dopo 5 secondi con fade-out
    setTimeout(function() {
        messageEl.style.opacity = '0';
    }, 5000);
}
```

```css
.bw-coupon-message.success {
    background: #d4edda !important;
    color: #155724 !important;
    border: 1px solid #c3e6cb !important;
    display: block !important;
}
.bw-coupon-message.error {
    background: #f8d7da !important;
    color: #721c24 !important;
    border: 1px solid #f5c6cb !important;
    display: block !important;
}
```

Il messaggio viene **ripristinato** dopo gli aggiornamenti AJAX del checkout tramite `restoreCouponMessage()`.

### Coupon applicato — Display nella tabella

```html
<!-- tfoot della review-order.php -->
<tr class="bw-total-row bw-total-row--coupon coupon-{codice}">
    <th>
        <span class="bw-coupon-label">Coupon:</span>
        <span class="bw-coupon-chip">
            <span class="bw-coupon-chip__icon"></span>
            {codice}
        </span>
    </th>
    <td>
        <span class="bw-coupon-value">
            -€ 10,00
            <a class="woocommerce-remove-coupon" data-coupon="{codice}">[Remove]</a>
        </span>
    </td>
</tr>
```

```css
/* Chip del coupon */
.bw-coupon-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 4px 10px;
    background: #e8e8e8;
    border-radius: 999px;
    font-size: 12px;
}
/* Valore coupon in verde */
.bw-total-row--coupon td {
    color: #27ae60 !important;
    font-weight: 700 !important;
}
/* Link Remove */
.bw-total-row--coupon .woocommerce-remove-coupon { color: #666666; }
.bw-total-row--coupon .woocommerce-remove-coupon:hover { color: #d32f2f; }
```

---

## 11. Calcoli e Totali

### Struttura tfoot — review-order.php

```html
<tfoot>
    <!-- Coupon form -->
    <tr><td colspan="2"><div class="bw-review-coupon">...</div></td></tr>

    <!-- Subtotale -->
    <tr class="bw-total-row bw-total-row--subtotal">
        <th>Subtotal</th>
        <td>wc_cart_totals_subtotal_html()</td>
    </tr>

    <!-- Coupon applicato (per ciascuno) -->
    <tr class="bw-total-row bw-total-row--coupon coupon-{code}">...</tr>

    <!-- Spedizione (se necessaria) -->
    <tr>wc_cart_totals_shipping_html()</tr>

    <!-- Fee aggiuntive (se presenti) -->
    <tr class="bw-total-row">fee.name | wc_cart_totals_fee_html()</tr>

    <!-- Tasse (itemized o totale) -->
    <tr class="bw-total-row tax-rate-{code}">...</tr>

    <!-- Totale finale -->
    <tr class="bw-total-row bw-total-row--grand order-total">
        <th>Total</th>
        <td>wc_cart_totals_order_total_html()</td>
    </tr>
</tfoot>
```

### CSS Totali

```css
/* Tutte le righe totale */
.bw-total-row th, .bw-total-row td {
    padding: 12px 0 !important;
    font-size: 14px;
    color: #111111;
}
.bw-total-row th { font-weight: 500; text-align: left; }
.bw-total-row td { text-align: right; font-weight: 500; }

/* Riga subtotale — separatore superiore */
.bw-total-row--subtotal {
    border-top: 1px solid #d8d8d8;
}
.bw-total-row--subtotal th,
.bw-total-row--subtotal td { padding-top: 16px !important; }

/* Totale finale — bordo spesso */
.bw-total-row--grand th,
.bw-total-row--grand td {
    border-top: 3px solid #000000 !important;
    padding: 16px 0 2px 0 !important;
    font-size: 16px;
    font-weight: 700;
    color: #000000;
}

/* Sconto coupon in verde */
.bw-total-row--coupon td {
    color: #27ae60 !important;
    font-weight: 700 !important;
}
```

### Percentuale di sconto — come funziona

WooCommerce calcola autonomamente sconti percentuali e fissi. Il plugin non sovrascrive questa logica: il prezzo mostrato in `.bw-review-item__price` è già il subtotale calcolato da WooCommerce (`get_product_subtotal()`). Il valore del coupon nel tfoot è calcolato da WooCommerce (`get_coupon_discount_amount()`).

Il JS aggiorna i totali nel pannello mobile dopo ogni `updated_checkout`:

```javascript
function updateOrderSummaryTotals() {
    var totalText = document.querySelector(
        '.bw-checkout-right .order-total .amount'
    )?.textContent.trim();

    if (totalText) {
        document.querySelector('.bw-order-summary-total').textContent = totalText;
        document.querySelector('.bw-mobile-total-amount').textContent = totalText;
    }
}
// Chiamato su: jQuery(document.body).on('updated_checkout', updateOrderSummaryTotals)
```

---

## 12. Sticky della Colonna Destra

### Perché non CSS `position: sticky`

CSS `sticky` con `margin-top` non funziona correttamente: la `margin-top` è considerata parte del box, quindi l'elemento si "attacca" a `margin-top + sticky-offset` dal top del viewport, non a `sticky-offset` px.

### Soluzione: Sticky JavaScript custom

Implementato in `bw-checkout.js` (funzione `initCustomSticky`):

```javascript
function initCustomSticky() {
    var BREAKPOINT = 900;
    var rightColumn = document.querySelector('.bw-checkout-right');

    // Legge i CSS variables impostati dal PHP
    var style = window.getComputedStyle(rightColumn);
    var stickyTop   = parseInt(style.getPropertyValue('--bw-checkout-right-sticky-top')) || 20;
    var marginTop   = parseInt(style.getPropertyValue('--bw-checkout-right-margin-top')) || 0;

    var initialOffset = null; // posizione iniziale nel documento
    var isSticky = false;
    var placeholder = null;  // div che mantiene il layout quando la colonna diventa fixed

    function onScroll() {
        if (window.innerWidth < BREAKPOINT) { resetSticky(); return; }

        var scrollY = window.pageYOffset;
        var threshold = initialOffset - stickyTop;

        if (scrollY >= threshold && !isSticky) {
            // Crea placeholder per mantenere il layout
            placeholder = document.createElement('div');
            placeholder.style.height = rightColumn.offsetHeight + 'px';
            parent.insertBefore(placeholder, rightColumn);

            // Fissa la colonna
            rightColumn.style.position = 'fixed';
            rightColumn.style.top = stickyTop + 'px';
            rightColumn.style.left = rect.left + 'px';
            rightColumn.style.width = rect.width + 'px';
            rightColumn.style.marginTop = '0';
            isSticky = true;
        } else if (scrollY < threshold && isSticky) {
            resetSticky(); // Ripristina margin-top originale
        }
    }
}
```

### Parametri configurabili (Admin)

| Setting | Default | Significato |
|---------|---------|-------------|
| `right_sticky_top` | 20px | Distanza dal top viewport quando sticky è attivo |
| `right_margin_top` | 0px | Offset iniziale per allineare la colonna con il form |

**Esempio:** con `margin_top = 150px` e `sticky_top = 25px`, la colonna inizia a 150px dall'alto. Quando lo scroll raggiunge i 125px (150 - 25), la colonna si fissa a 25px dal top.

### Ricalcolo dopo update checkout

```javascript
jQuery(document.body).on('updated_checkout', function() {
    setTimeout(function() {
        initialOffset = null;
        calculateOffsets();
        onScroll();
    }, 500); // attende il reflow del DOM
});
```

---

## 13. Responsive Design

### Breakpoints

| Breakpoint | Comportamento |
|-----------|---------------|
| `≥ 900px` | CSS Grid a due colonne, sticky JS attivo |
| `< 900px` | Layout a blocchi sovrapposti, sticky disattivato |
| `< 768px` | Adattamenti header minimale |

### Mobile (< 900px) — comportamento principale

**Order Summary nascosto di default**, con toggle:

```css
@media (max-width: 899px) {
    /* Toggle visibile */
    body.woocommerce-checkout .bw-order-summary-toggle { display: flex; }

    /* Order summary nascosto */
    body.woocommerce-checkout .bw-checkout-order-heading__wrap,
    body.woocommerce-checkout .bw-checkout-right { display: none; }

    /* Visibile quando aperto */
    body.woocommerce-checkout.bw-order-summary-open .bw-checkout-right,
    body.woocommerce-checkout.bw-order-summary-open .bw-checkout-order-heading__wrap {
        display: block;
    }

    /* Totale visibile prima del Place Order */
    body.woocommerce-checkout .bw-mobile-total-row { display: flex; }
}
```

### Toggle Order Summary HTML

```html
<button class="bw-order-summary-toggle" aria-expanded="false">
    <span class="bw-order-summary-label">
        <span class="bw-order-summary-caret"></span>
        Order summary
    </span>
    <span class="bw-order-summary-total">{totale}</span>
</button>
```

```javascript
function initOrderSummaryToggle() {
    toggle.addEventListener('click', function() {
        var isOpen = document.body.classList.toggle('bw-order-summary-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
}
```

### Mobile — Adattamenti specifici

| Elemento | Desktop | Mobile |
|----------|---------|--------|
| Padding colonna sinistra | `35px 28px 0 0` | `16px` |
| Padding wrapper | `0 0 16px 0` | `0 10px 20px 10px` |
| Font title prodotto | 14px | 13px |
| Bottoni quantità width | 24px | 22px |
| Input quantità width | 32px | 30px |
| Logo max-width | illimitato | 150px |
| Padding prodotto | `0 0 15px 0` | `0 0 12px 0` |
| Coupon input padding | `20px 18px 10px` | `16px 12px 8px` |

### Express checkout su mobile

```css
@media (max-width: 899px) {
    .bw-checkout-express__gateway-grid { grid-template-columns: 1fr; }
}
```

---

## 14. JavaScript — bw-checkout.js

### Inizializzazione

Il file si avvolge in un IIFE e aspetta jQuery prima di procedere. Se jQuery non è disponibile, riprova ogni 100ms.

### Funzioni principali

| Funzione | Scopo |
|----------|-------|
| `updateQuantity(input, delta)` | Incrementa/decrementa quantità con step, min, max |
| `setOrderSummaryLoading(bool)` | Mostra/nasconde overlay skeleton loading |
| `triggerCheckoutUpdate()` | Triggera `update_checkout` WooCommerce |
| `showCouponMessage(msg, type)` | Mostra messaggio success/error coupon |
| `restoreCouponMessage()` | Ripristina messaggio dopo DOM update |
| `handleCouponSubmission(input)` | AJAX apply coupon |
| `initCustomSticky()` | Sticky JS custom della colonna destra |
| `initFloatingLabel(scope)` | Floating label per input coupon |
| `initCheckoutFloatingLabels()` | Floating label per tutti i campi billing/shipping |
| `initOrderSummaryToggle()` | Toggle mobile order summary |
| `updateOrderSummaryTotals()` | Sincronizza totale mobile/toggle con colonna destra |
| `observeStripeErrors()` | Osserva e corregge layout errori Stripe |
| `fixStripeErrorLayout()` | Forza layout inline per errori Stripe |
| `initEntityDecoder()` | Decodifica HTML entities nei messaggi di errore |

### Event Listeners

| Evento | Delegato su | Azione |
|--------|------------|--------|
| `click` su `.bw-qty-btn` | `document` | Aggiorna quantità |
| `click` su `.bw-review-item__remove` | `document` | Rimuove prodotto (qty → 0) |
| `click` su `.woocommerce-remove-coupon` | `document` | Rimuove coupon via AJAX |
| `click` su `.bw-apply-button` | `jQuery(body)` | Applica coupon via AJAX |
| `change` su `input.qty` | `document` | Triggera checkout update |
| `updated_checkout` | `jQuery(body)` | Rimuove loading, ripristina messaggio, aggiorna totali |
| `applied_coupon` | `jQuery(body)` | Mostra messaggio success |
| `update_checkout` | `jQuery(body)` | Attiva loading |
| `scroll` | `window` | Logica sticky |
| `resize` | `window` | Ricalcola sticky |

### Loading State (Skeleton Overlay)

```javascript
function setOrderSummaryLoading(isLoading) {
    var loaders = document.querySelectorAll('.bw-order-summary, .bw-checkout-left, .bw-checkout-right');
    loaders.forEach(function(el) {
        el.classList.toggle('is-loading', isLoading);
    });
}
```

CSS skeleton con animazione shimmer:

```css
.bw-order-summary__loader {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: #f2f2f2;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.4s ease;
}
.bw-order-summary.is-loading .bw-order-summary__loader {
    opacity: 1;
    visibility: visible;
}
/* Shimmer animation */
.bw-order-summary__loader::after {
    content: "";
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    animation: bw-skeleton-shimmer 1.5s infinite;
}
```

---

## 15. Impostazioni Admin

**Percorso:** `WP Admin → Blackwork → Site Settings → Checkout Tab`

### Tabella settings disponibili

| Option Key | Tipo | Default | Descrizione |
|-----------|------|---------|-------------|
| `bw_checkout_logo` | URL | — | Logo URL |
| `bw_checkout_logo_width` | int | 200 | Larghezza logo (px) |
| `bw_checkout_logo_align` | string | `left` | Allineamento logo: `left`, `center`, `right` |
| `bw_checkout_logo_padding_top` | int | 0 | Padding top logo (px) |
| `bw_checkout_logo_padding_right` | int | 0 | Padding right logo (px) |
| `bw_checkout_logo_padding_bottom` | int | 0 | Padding bottom logo (px) |
| `bw_checkout_logo_padding_left` | int | 0 | Padding left logo (px) |
| `bw_checkout_show_order_heading` | `1`/`0` | `1` | Mostra/nasconde "Your Order" |
| `bw_checkout_left_width` | int | 62 | Larghezza colonna sinistra (%) |
| `bw_checkout_right_width` | int | 38 | Larghezza colonna destra (%) |
| `bw_checkout_page_bg` | color | `#ffffff` | Background pagina |
| `bw_checkout_grid_bg` | color | `#ffffff` | Background griglia |
| `bw_checkout_left_bg` | color | `#ffffff` | Background colonna sinistra |
| `bw_checkout_right_bg` | color | `transparent` | Background colonna destra |
| `bw_checkout_border_color` | color | `#262626` | Colore separatore e bordi |
| `bw_checkout_right_padding_top` | int | 0 | Padding top colonna destra (px) |
| `bw_checkout_right_padding_right` | int | 0 | Padding right colonna destra (px) |
| `bw_checkout_right_padding_bottom` | int | 0 | Padding bottom colonna destra (px) |
| `bw_checkout_right_padding_left` | int | 28 | Padding left colonna destra (px) |
| `bw_checkout_right_sticky_top` | int | 20 | Sticky offset top (px) |
| `bw_checkout_right_margin_top` | int | 0 | Margin top iniziale colonna destra (px) |
| `bw_checkout_legal_text` | HTML | — | Testo legale sotto il bottone |
| `bw_checkout_footer_copyright` | string | — | Testo copyright nel footer |
| `bw_checkout_show_return_to_shop` | `1`/`0` | `1` | Link "Return to shop" |
| `bw_checkout_show_footer_copyright` | `1`/`0` | `1` | Mostra copyright |
| `bw_checkout_thumb_width` | int | 110 | Larghezza thumbnail prodotto (px) |
| `bw_checkout_thumb_ratio` | string | `square` | Rapporto thumbnail: `square`, `portrait`, `landscape` |
| `bw_checkout_policy_{key}` | array | — | Dati policy (refund, shipping, privacy, terms, contact) |

### Funzione getter — `bw_mew_get_checkout_settings()`

```php
function bw_mew_get_checkout_settings() {
    return [
        'logo'                => esc_url_raw(get_option('bw_checkout_logo', '')),
        'logo_width'          => absint(get_option('bw_checkout_logo_width', 200)),
        'logo_align'          => get_option('bw_checkout_logo_align', 'left'),
        'logo_padding_top'    => absint(get_option('bw_checkout_logo_padding_top', 0)),
        // ... tutti gli altri settings
        'thumb_width'         => absint(get_option('bw_checkout_thumb_width', 110)),
        'thumb_ratio'         => get_option('bw_checkout_thumb_ratio', 'square'),
        'show_order_heading'  => get_option('bw_checkout_show_order_heading', '1'),
        'left_width'          => absint(get_option('bw_checkout_left_width', 62)),
        'right_width'         => absint(get_option('bw_checkout_right_width', 38)),
        'right_padding_top'   => absint(get_option('bw_checkout_right_padding_top', 0)),
        'right_padding_left'  => absint(get_option('bw_checkout_right_padding_left', 28)),
        'right_sticky_top'    => absint(get_option('bw_checkout_right_sticky_top', 20)),
        'right_margin_top'    => absint(get_option('bw_checkout_right_margin_top', 0)),
        // ...
    ];
}
```

---

## 16. CSS Variables e Temi

Tutte le variabili vengono impostate via inline style nel PHP del template e poi ereditate dal CSS:

```css
/* Variabili disponibili */
--bw-checkout-page-bg         /* background pagina */
--bw-checkout-grid-bg         /* background griglia */
--bw-checkout-left-bg         /* background colonna sinistra */
--bw-checkout-right-bg        /* background colonna destra */
--bw-checkout-border-color    /* colore separatore/bordi */
--bw-checkout-left-col        /* larghezza % colonna sinistra */
--bw-checkout-right-col       /* larghezza % colonna destra */
--bw-checkout-right-pad-top   /* padding top colonna destra */
--bw-checkout-right-pad-right /* padding right colonna destra */
--bw-checkout-right-pad-bottom/* padding bottom colonna destra */
--bw-checkout-right-pad-left  /* padding left colonna destra */
--bw-checkout-right-sticky-top/* offset sticky */
--bw-checkout-right-margin-top/* margin top iniziale */
--bw-thumb-aspect             /* aspect-ratio thumbnail (es. "1 / 1") */
--bw-thumb-width              /* larghezza thumbnail px (es. "110px") */
```

---

## 17. Integrazione Stripe

### Appearance API — Errori inline

**Problema risolto:** icone di errore Stripe appaiono sopra il testo invece che inline.

**Soluzione nel PHP** (`woocommerce-init.php`):
```php
function bw_mew_customize_stripe_upe_appearance($params) {
    $params['appearance']['rules'] = array_merge(
        $params['appearance']['rules'] ?? [],
        [
            '.Error' => [
                'display'     => 'flex',
                'alignItems'  => 'center',
                'gap'         => '6px',
                'marginTop'   => '8px',
                'fontSize'    => '13px',
                'color'       => '#991b1b',
            ],
            '.ErrorIcon' => [
                'flexShrink'  => '0',
                'width'       => '16px',
                'height'      => '16px',
                'marginTop'   => '0',
            ],
            '.ErrorText' => [
                'flex'        => '1',
                'marginTop'   => '0',
            ],
        ]
    );
    return $params;
}
```

**Fix aggiuntivo in JS** (MutationObserver per errori dinamici):
```javascript
function observeStripeErrors() {
    var observer = new MutationObserver(function(mutations) {
        // Quando vengono aggiunti errori Stripe, forza layout flex inline
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.querySelector && node.querySelector('.Error')) {
                    fixStripeErrorLayout();
                }
            });
        });
    });
    observer.observe(document.querySelector('#payment'), { childList: true, subtree: true });
}
```

### billing_details — Auto-collection

**Problema risolto:** errore console `billing_details.name` — Stripe richiedeva i dati che non veniva passati.

**Soluzione:**
```php
$params['fields'] = [
    'billingDetails' => [
        'name'    => 'auto',  // auto-collect from WC form
        'email'   => 'auto',
        'phone'   => 'auto',
        'address' => [
            'country'    => 'auto',
            'line1'      => 'auto',
            'city'       => 'auto',
            'state'      => 'auto',
            'postalCode' => 'auto',
        ],
    ],
];
```

### Express checkout buttons (Apple Pay / Google Pay)

I pulsanti express Stripe sono mostrati nella colonna sinistra sopra i campi, in layout a 2 colonne (50% ciascuno). Se è presente solo un bottone, occupa il 100%.

I separatori nativi di Stripe sono nascosti con CSS e sostituiti da `.bw-express-divider`.

---

## 18. Fix Tecnici Applicati

### FIX 1 — `table-layout: fixed` (Critico)

**Data:** 2026-01-05
**Problema:** thumbnail prodotto 65px→144px, salto dimensioni al apply/remove coupon.
**Causa:** `table-layout: auto` (default) ignora larghezze specificate e ricalcola al cambio contenuto.
**Fix:**
```css
.bw-checkout-right table.shop_table {
    table-layout: fixed; /* ← aggiunto */
}
```

### FIX 2 — `box-sizing: border-box` su media container

**Data:** 2026-01-05
**Problema:** thumbnail 2px più largo del previsto (border incluso nel content-box).
**Fix:**
```css
.bw-checkout-right .bw-review-item__media {
    box-sizing: border-box; /* ← aggiunto */
}
```

### FIX 3 — Coupon AJAX senza validazione pagamento

**Data:** 2026-01-20
**Problema:** impossibile applicare coupon senza inserire prima i dati della carta.
**Fix:** bottone con `type="button"`, AJAX custom bypassando checkout validation WooCommerce.

### FIX 4 — Coupon ricomparso dopo rimozione

**Data:** varie iterazioni
**Fix:** AJAX custom con `persistent_cart_update()` + `session->save_data()` + redirect GET.

### FIX 5 — Sticky con margin-top

**Data:** 2024-12-28
**Problema:** CSS sticky non funziona con margin-top (il margin è incluso nel calcolo).
**Fix:** Custom sticky JS con placeholder e `position: fixed`.

### FIX 6 — Errori Stripe sopra il testo

**Data:** 2026-01-20
**Fix:** Stripe Appearance API + MutationObserver JS.

### FIX 7 — `billing_details.name` console error

**Data:** 2026-01-20
**Fix:** `fields.billingDetails` con `'auto'` per tutti i campi.

### FIX 8 — Padding Elementor indesiderati

**Soluzione permanente:**
```css
.woocommerce-checkout .elementor-section,
.woocommerce-checkout .elementor-element-populated,
.woocommerce-checkout .elementor-widget-wrap {
    padding: 0 !important;
    margin: 0 !important;
}
```

---

## 19. Checklist di Test

### Layout Desktop (≥ 900px)

- [ ] Grid a due colonne visibile e proporzionato (default 62/38)
- [ ] Separatore verticale visibile
- [ ] Colonna destra si allinea correttamente con la sinistra
- [ ] Nessun padding/margin indesiderato da Elementor (verifica DevTools)
- [ ] Header minimale visibile con logo
- [ ] Sticky colonna destra funziona: si fissa allo scroll e torna su scroll-up
- [ ] Sticky rispetta i parametri admin (sticky_top, margin_top)

### Layout Mobile (< 900px)

- [ ] Toggle "Order summary" visibile con totale aggiornato
- [ ] Click toggle apre/chiude order summary
- [ ] "Return to shop" → link torna al shop
- [ ] Totale visibile prima del bottone Place Order
- [ ] Sticky JS disattivato
- [ ] Campi form occupano tutta la larghezza
- [ ] Express buttons in colonna singola

### Prodotti nel carrello

- [ ] Thumbnail dimensioni corrette (default 110px)
- [ ] Thumbnail non cambia dimensioni al apply/remove coupon (table-layout fix)
- [ ] Aspect ratio corretto (square/portrait/landscape)
- [ ] Immagine usa `object-fit: cover`
- [ ] Titolo prodotto a 14px, font-weight 600
- [ ] Prezzo a destra (subtotale × quantità)
- [ ] Badge "Sold individually" per prodotti singoli
- [ ] Link "Remove" grigio (#999) con hover underline
- [ ] X button nascosto

### Controlli Quantità

- [ ] Bottoni +/- funzionano
- [ ] Quantità non scende sotto il minimo (min=0)
- [ ] Border nero, border-radius 10px, altezza 25px
- [ ] Hover bottoni: background #f5f5f5
- [ ] Input nasconde frecce browser (webkit/moz)
- [ ] Cambio quantità triggera AJAX update checkout
- [ ] Loading overlay appare durante aggiornamento

### Coupon

- [ ] Floating label si sposta su focus/valore
- [ ] Bottone grigio senza testo, nero con testo inserito
- [ ] Coupon valido: applica senza richiedere dati carta
- [ ] Coupon valido: mostra messaggio verde "Coupon applied successfully"
- [ ] Coupon non valido: mostra errore rosso inline (non nel form)
- [ ] Coupon applicato visibile nel tfoot con chip stilizzato
- [ ] Sconto mostrato in verde
- [ ] [Remove] rimuove coupon, non ricompare dopo reload
- [ ] Messaggio coupon persiste dopo updated_checkout (nei 5 secondi)

### Calcoli Totali

- [ ] Subtotale corretto (somma prezzi × quantità)
- [ ] Sconto coupon corretto (percentuale o fisso)
- [ ] Spedizione mostrata se applicabile
- [ ] Tasse mostrate se configurate
- [ ] Totale finale aggiornato dopo ogni modifica
- [ ] Separatore sopra subtotale (1px #d8d8d8)
- [ ] Bordo sopra totale finale (3px #000000)
- [ ] Font totale finale: 16px, font-weight 700

### Pagamento

- [ ] Accordion pagamento funziona (radio button → apre pannello)
- [ ] Primo gateway aperto di default
- [ ] Stripe fields visibili e funzionanti
- [ ] PayPal: messaggio redirect invece di descrizione
- [ ] Google Pay: messaggio redirect
- [ ] Errori Stripe mostrati inline (non sopra il testo)
- [ ] Nessun errore console `billing_details.name`
- [ ] Bottone "Place order" testo cambia in base al gateway selezionato
- [ ] Ordine free: payment nascosto, free order banner visibile

### Admin Panel

- [ ] Cambio larghezza colonne applica immediatamente
- [ ] Toggle "Your Order" mostra/nasconde heading
- [ ] Logo width/align/padding applicati
- [ ] Thumbnail width/ratio applicati
- [ ] Sticky top/margin top funzionano come descritto
- [ ] Colori background applicati a pagina e colonne

### Browser

- [ ] Chrome/Edge (Chromium) — desktop e mobile
- [ ] Firefox
- [ ] Safari (macOS + iOS)
- [ ] Chrome Mobile (Android)

---

## 20. Manutenzione Futura

### Se WooCommerce aggiorna i template

Verificare compatibilità di:
- `woocommerce/templates/checkout/form-checkout.php` → verificare hook mantenuti
- `woocommerce/templates/checkout/review-order.php` → struttura tbody/tfoot
- `woocommerce/templates/checkout/payment.php` → struttura `$available_gateways`

Template reference WooCommerce: https://woocommerce.com/document/template-structure/

### Se si aggiunge un nuovo campo checkout

1. Il campo entra automaticamente nella colonna sinistra
2. Verificare che rispetti il CSS dei form fields (border-radius 12px, padding 13px 14px)
3. Per half-width: aggiungere classe `bw-checkout-field--half`
4. Testare su mobile (torna full-width automaticamente)

### Se si aggiunge un nuovo gateway di pagamento

Il gateway viene incluso automaticamente nell'accordion. Verificare:
- Se ha `has_fields()` → i suoi campi appariranno in `.bw-payment-method__fields`
- Se non ha fields → appare `.bw-payment-method__selected-indicator`
- Se è PayPal/Google Pay → aggiungere la logica di riconoscimento in `payment.php` per il messaggio redirect

### Se il tema cambia

1. Verificare gli override Elementor (`padding: 0 !important` sulle classi elementor)
2. Verificare che `header` e `footer` del tema siano correttamente nascosti
3. Aggiungere nuovi selettori specifici del tema ai blocchi "FORCE ZERO SPACING"

### Se Stripe aggiorna le API

1. Verificare `bw_mew_customize_stripe_upe_appearance()` in `woocommerce-init.php`
2. Verificare che `.Error`, `.ErrorIcon`, `.ErrorText` siano ancora le classi usate
3. Verificare che `fields.billingDetails` con `'auto'` sia ancora valido

### Cache

Dopo modifiche a CSS/JS:
- Hard refresh browser: `Ctrl+Shift+R` (Win/Linux) o `Cmd+Shift+R` (Mac)
- Svuotare cache plugin caching (se presenti)
- Il versioning via `filemtime()` gestisce automaticamente la cache degli asset

---

## Riferimento Rapido — Classi CSS

```
.bw-minimal-checkout-header         → Header minimale
.bw-checkout-form                   → Form WooCommerce checkout
.bw-checkout-wrapper                → Contenitore max-width 980px
.bw-checkout-grid                   → CSS Grid a 3 colonne
.bw-checkout-left                   → Colonna sinistra (form + pagamento)
.bw-checkout-order-heading__wrap    → Wrapper "Your Order" heading
.bw-checkout-order-heading          → Titolo "Your Order"
.bw-checkout-right                  → Colonna destra (order summary)
.bw-checkout-customer-details       → Wrapper campi billing/shipping
.bw-checkout-payment                → Wrapper sezione pagamento
.bw-checkout-legal                  → Testo legale
.bw-checkout-left-footer            → Footer colonna sinistra
.bw-checkout-return-to-shop         → Link ritorno al negozio
.bw-checkout-copyright              → Testo copyright
.bw-checkout-footer-links           → Links policy footer
.bw-order-summary                   → Container order summary
.bw-order-summary-toggle            → Toggle mobile order summary
.bw-order-summary-total             → Totale nel toggle
.bw-order-summary-open              → Classe su body quando toggle aperto
.bw-review-table                    → Tabella prodotti
.bw-review-item                     → Riga prodotto (tr)
.bw-review-item__media              → Container immagine prodotto
.bw-review-item__content            → Contenuto testuale prodotto
.bw-review-item__header             → Header prodotto (titolo + X + prezzo)
.bw-review-item__title              → Titolo prodotto
.bw-review-item__price              → Prezzo prodotto (subtotale)
.bw-review-item__remove             → X button (nascosto)
.bw-review-item__remove-text        → Link "Remove" testuale grigio
.bw-review-item__controls           → Controls (qty + remove)
.bw-qty-control                     → Container controllo quantità
.bw-qty-shell                       → Shell (border arrotondato)
.bw-qty-btn                         → Bottone +/-
.bw-qty-btn--minus                  → Bottone -
.bw-qty-btn--plus                   → Bottone +
.bw-qty-badge                       → Badge "Sold individually"
.bw-review-coupon                   → Container form coupon
.bw-coupon-fields                   → Flex row: input + bottone
.bw-coupon-input-wrapper            → Wrapper con floating label
.bw-floating-label                  → Label floating coupon
.bw-apply-button                    → Bottone "Apply"
.bw-coupon-error                    → Messaggio errore coupon (rosso)
.bw-coupon-message                  → Messaggio success/error coupon
.bw-coupon-message.success          → Successo (verde)
.bw-coupon-message.error            → Errore (rosso)
.bw-total-row                       → Riga totali (tfoot)
.bw-total-row--subtotal             → Riga subtotale
.bw-total-row--coupon               → Riga coupon applicato
.bw-total-row--grand                → Riga totale finale
.bw-coupon-chip                     → Chip/badge con codice coupon
.bw-coupon-label                    → Label "Coupon:"
.bw-coupon-value                    → Valore sconto + link remove
.bw-mobile-total-row                → Riga totale mobile (sopra Place Order)
.bw-mobile-total-amount             → Amount nel totale mobile
.bw-payment-section-title           → Titolo sezione pagamento
.bw-payment-section-subtitle        → Sottotitolo sezione pagamento
.bw-payment-methods                 → Lista gateway (ul)
.bw-payment-method                  → Singolo gateway (li)
.bw-payment-method__header          → Header gateway (radio + label)
.bw-payment-method__label           → Label del gateway
.bw-payment-method__title           → Titolo del gateway
.bw-payment-method__content         → Contenuto accordion (campo/desc)
.bw-payment-method__fields          → Container fields gateway
.bw-payment-method__inner           → Inner wrapper contenuto
.bw-place-order-btn                 → Bottone Place Order
.bw-free-order-banner               → Banner ordine gratuito
.bw-express-divider                 → Separatore "or" tra express e form
.bw-policy-link                     → Link policy nel footer
.bw-policy-modal                    → Modal policy
.bw-checkout-left__loader           → Skeleton overlay colonna sinistra
.bw-checkout-right__loader          → Skeleton overlay colonna destra
.bw-order-summary__loader           → Skeleton overlay order summary
```
