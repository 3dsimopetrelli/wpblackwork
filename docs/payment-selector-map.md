# Payment Section Selector Map

## Overview
Stable selector map for WooCommerce checkout payment section.
Avoids auto-generated Elementor classes and ensures cascade specificity.

## Scope Strategy
- **Primary Scope**: `body.woocommerce-checkout`
- **Fallback Scope**: `.woocommerce-checkout-payment`
- **Avoid**: Elementor classes (`.elementor-*`, `.e-*`)

---

## DOM Structure & Selectors

### Level 1: Container
```html
<div id="payment" class="woocommerce-checkout-payment">
```

**Stable Selectors:**
- `body.woocommerce-checkout #payment`
- `body.woocommerce-checkout .woocommerce-checkout-payment`

**Specificity**: `(0,1,2)` - Wins over most theme CSS

---

### Level 2: Section Title & Subtitle

#### Title
```html
<h2 class="bw-payment-section-title">Payment</h2>
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-section-title`
- `.woocommerce-checkout-payment .bw-payment-section-title`

**Specificity**: `(0,1,2)` or `(0,0,2)`

#### Subtitle
```html
<p class="bw-payment-section-subtitle">All transactions are secure...</p>
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-section-subtitle`

---

### Level 3: Payment Methods List

```html
<ul class="bw-payment-methods wc_payment_methods payment_methods methods">
```

**Stable Selectors:**
- `body.woocommerce-checkout ul.bw-payment-methods`
- `body.woocommerce-checkout .wc_payment_methods`

**Specificity**: `(0,1,2)`

---

### Level 4: Individual Payment Method

```html
<li class="bw-payment-method wc_payment_method payment_method_stripe" data-gateway-id="stripe">
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method`
- `body.woocommerce-checkout .wc_payment_method`

**States:**
- **Selected**: `.bw-payment-method.is-selected`
- **Has checked radio**: `.bw-payment-method:has(input[type="radio"]:checked)`

**Specificity**: `(0,1,2)` for base, `(0,2,2)` for states

---

### Level 5: Payment Method Header (Radio + Label)

#### Header Container
```html
<div class="bw-payment-method__header">
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method__header`

**States:**
- **Hover**: `.bw-payment-method__header:hover`
- **Focus-within**: `.bw-payment-method__header:focus-within`

#### Radio Button
```html
<input type="radio" class="input-radio" name="payment_method" id="payment_method_stripe">
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method__header input[type="radio"]`
- `body.woocommerce-checkout input[name="payment_method"]`

**States:**
- **Checked**: `input[type="radio"]:checked`
- **Focus**: `input[type="radio"]:focus`
- **Hover**: `input[type="radio"]:hover`

**Specificity**: `(0,1,3)` for scoped input

#### Label
```html
<label for="payment_method_stripe" class="bw-payment-method__label">
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method__label`

---

### Level 6: Payment Method Title & Icons

#### Title
```html
<span class="bw-payment-method__title">Credit card</span>
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method__title`

#### Icons Container
```html
<span class="bw-payment-method__icons">
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method__icons`

#### Individual Icon
```html
<span class="bw-payment-icon"><img src="..." alt="Visa"></span>
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-icon`
- `body.woocommerce-checkout .bw-payment-icon img`

#### More Icons Badge
```html
<span class="bw-payment-icon bw-payment-icon--more">
  <span class="bw-payment-icon__badge">+2</span>
  <span class="bw-payment-icon__tooltip" id="tooltip-stripe">...</span>
</span>
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-icon--more`
- `body.woocommerce-checkout .bw-payment-icon__badge`
- `body.woocommerce-checkout .bw-payment-icon__tooltip`

**States:**
- **Hover**: `.bw-payment-icon--more:hover .bw-payment-icon__badge`
- **Tooltip visible**: `.bw-payment-icon--more:hover .bw-payment-icon__tooltip`

---

### Level 7: Payment Method Content (Accordion Panel)

```html
<div class="bw-payment-method__content payment_box payment_method_stripe is-open">
  <div class="bw-payment-method__inner">
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method__content`
- `body.woocommerce-checkout .payment_box`

**States:**
- **Open**: `.bw-payment-method__content.is-open`
- **Closed**: `.bw-payment-method__content:not(.is-open)` (default)

**Specificity**: `(0,1,2)` for base, `(0,2,2)` for state

---

### Level 8: Inner Content Elements

#### Description
```html
<div class="bw-payment-method__description">
  <p>Pay with your credit card...</p>
</div>
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method__description`
- `body.woocommerce-checkout .bw-payment-method__description p`

#### Payment Fields Container
```html
<div class="bw-payment-method__fields">
  <!-- Gateway renders fields here (Stripe Elements, etc.) -->
</div>
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method__fields`
- `body.woocommerce-checkout .bw-payment-method__fields .form-row`

#### Input Fields
```html
<input type="text" name="card-number" placeholder="Card number">
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method__fields input[type="text"]`
- `body.woocommerce-checkout .bw-payment-method__fields input[type="email"]`
- `body.woocommerce-checkout .bw-payment-method__fields input[type="tel"]`
- `body.woocommerce-checkout .bw-payment-method__fields select`

**States:**
- **Hover**: `input:hover`
- **Focus**: `input:focus`
- **Error**: `input.woocommerce-invalid` or `.woocommerce-invalid-required-field`
- **Valid**: `input:valid` (HTML5 validation)

**Specificity**: `(0,1,4)` - Ensures override of WooCommerce defaults

---

### Level 9: Stripe Elements Overrides

```html
<div class="wc-stripe-elements-field">
  <!-- Stripe Elements iframe injected here -->
</div>
```

**Stable Selectors:**
- `body.woocommerce-checkout .wc-stripe-elements-field`
- `body.woocommerce-checkout .wc-stripe-iban-element-field`

**States:**
- **Hover**: `.wc-stripe-elements-field:hover`
- **Focus**: `.wc-stripe-elements-field:focus-within`

**Note**: Stripe Elements styles must be configured via JavaScript `stripe.elements()` API.

---

### Level 10: Selected Indicator (for no-fields methods)

```html
<div class="bw-payment-method__selected-indicator">
  <svg class="bw-payment-check-icon">...</svg>
  <span>Credit card selected</span>
</div>
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method__selected-indicator`
- `body.woocommerce-checkout .bw-payment-check-icon`

---

### Level 11: Instruction Text

```html
<p class="bw-payment-method__instruction">
  Click the "Pay now" button to submit...
</p>
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method__instruction`

---

### Level 12: Place Order Button

```html
<button type="submit" class="button alt bw-place-order-btn" id="place_order">
  <span class="bw-place-order-btn__text">Place order</span>
</button>
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-place-order-btn`
- `body.woocommerce-checkout #place_order`

**States:**
- **Hover**: `.bw-place-order-btn:hover`
- **Active**: `.bw-place-order-btn:active`
- **Disabled**: `.bw-place-order-btn:disabled`
- **Processing**: `.bw-place-order-btn.processing`

**Specificity**: `(0,1,2)` for class, `(0,1,1)` for ID

---

## Error States

### WooCommerce Validation Errors

```html
<ul class="woocommerce-error" role="alert">
  <li>Payment error: Your card was declined.</li>
</ul>
```

**Stable Selectors:**
- `body.woocommerce-checkout .woocommerce-error`
- `body.woocommerce-checkout .woocommerce-checkout-payment .woocommerce-error`

### Invalid Field

```html
<input type="text" class="woocommerce-invalid">
```

**Stable Selectors:**
- `body.woocommerce-checkout input.woocommerce-invalid`
- `body.woocommerce-checkout .woocommerce-invalid-required-field`

---

## Specificity Strategy

### Winning the Cascade

1. **Elementor Theme Overrides**: `(0,1,1)` typical
   - **Our Override**: `(0,1,2)` - Add `body.woocommerce-checkout`

2. **WooCommerce Core**: `(0,0,2)` to `(0,1,2)`
   - **Our Override**: `(0,1,2)` to `(0,1,3)` - Match or exceed

3. **When to use !important**:
   - Only for critical resets (e.g., hiding elements)
   - Background/border overrides on payment methods
   - Never for layout properties

### Selector Patterns

```css
/* ✅ GOOD - Scoped, stable, specific */
body.woocommerce-checkout .bw-payment-method__header input[type="radio"]:checked {
  /* Specificity: (0,1,3) */
}

/* ❌ BAD - Too generic, conflicts possible */
.bw-payment-method input {
  /* Specificity: (0,0,2) */
}

/* ❌ BAD - Elementor dependency */
.elementor-section .bw-payment-method {
  /* Fragile, breaks if layout changes */
}
```

---

## CSS Variable Scoping

Design tokens should be scoped to `body.woocommerce-checkout` or `:root`:

```css
/* Global tokens */
:root {
  --bw-payment-primary: #000000;
  --bw-payment-border: #d1d5db;
}

/* Checkout-specific tokens */
body.woocommerce-checkout {
  --bw-payment-bg: #ffffff;
  --bw-payment-selected-bg: #f9fafb;
}
```

---

## Testing Checklist Reference

See `payment-test-checklist.md` for complete testing scenarios.
