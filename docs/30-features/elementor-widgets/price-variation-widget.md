# BW Price Variation Widget

## Purpose

`bw-price-variation` is the pricing/license-selection widget for variable WooCommerce products.

It owns:
- price display
- variation/license switching
- Add to Cart CTA
- license-information box

It now also includes two review/payment-adjacent integrations:
- product review mini-summary under the price
- `More payment options` direct-checkout link under Add to Cart

## Widget Contract

Slug:

```text
bw-price-variation
```

Visible title:

```text
BW-SP Price Variation
```

Primary files:
- `includes/widgets/class-bw-price-variation-widget.php`
- `assets/js/bw-price-variation.js`
- `assets/css/bw-price-variation.css`

## Rates Section

### Content controls
Section:

```text
Content -> Rates
```

Controls:
- `Show Product Reviews`
- `Show Reviews Count`
- responsive `Alignment`

Behavior:
- uses only the current product
- reads only from the custom BW Reviews module
- renders only if the current product has approved reviews
- never falls back to global/site-wide review summaries

### Visual contract
- rendered directly under the price
- small stars
- `12px` text baseline
- inline structure:
  - stars
  - average rating
  - separator
  - optional review count

### Style controls
Section:

```text
Style -> Rates
```

Controls:
- `Margin`

## More Payment Options Link

### Content control
Section:

```text
Content -> Add To Cart
```

Control:
- `Show More Payment Options`

Behavior:
- shows a centered text link under `Add to Cart`
- label:

```text
More payment options
```

- visually underlined by default
- intended as a direct checkout shortcut

### Checkout behavior
The link always follows the selected variation/license:
- if the customer changes variation, the checkout URL updates
- if the customer does not change variation, the widget uses the default selected variation

The generated checkout link includes:
- product id
- variation id
- quantity
- selected variation attributes

### Style controls
Section:

```text
Style -> Add To Cart Button
```

Controls:
- `More Payment Options Typography`
- `More Payment Options Margin`

## JS Contract

The widget JS keeps a single active variation state and updates all dependent surfaces together:
- price display
- license box
- Add to Cart URL/data attributes
- More payment options checkout URL

This keeps the widget deterministic and prevents the checkout shortcut from drifting away from the selected license.
