# BW Price Variation Widget

## Purpose

`bw-price-variation` is the pricing/license-selection widget for variable WooCommerce products.

It owns:
- price display
- variation/license switching
- Add to Cart CTA
- license-information disclosure box

It also includes governed supporting integrations:
- compact product review trust summary under the price
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

## Governance Position

`bw-price-variation` remains a pricing/license authority widget.

Current governed boundaries:
- owns the active variation state for the widget column
- owns price, selected license state, Add to Cart state, and license disclosure content
- may consume compact read-only trust signals for the current product
- must not become a second reviews authority
- must not introduce a second product configurator inside the license box
- lower trust/supporting stacks now belong to `BW Trust Box`, not to this widget

## Current Runtime Reality

Validated against the current widget render and current repository state:
- price is rendered first
- a compact reviews trust summary may render directly under the price
- the customer selects between license-oriented variation buttons
- a license disclosure accordion may appear between the variation buttons and Add to Cart
- the open accordion body renders the variation-specific license table

In current storefront usage, the accordion label can be configured per widget instance and may be rendered simply as:

```text
License
```

The accordion itself is not the selector. The selector remains the variation button group.
The accordion is a disclosure surface that explains the currently active license.

## Trust Boundary

The lower trust/support stack previously rendered under `BW-SP Price Variation` now belongs to `BW Trust Box`.

That extracted widget owns:
- curated global review slider rendering
- curated global fixed review box rendering
- widget-level digital product info cards
- widget-level FAQ CTA

`BW-SP Price Variation` now stops at:
- price
- compact inline product review summary
- variation buttons
- license disclosure
- Add to Cart
- optional `More payment options`

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
- is intended as a compact trust signal, not as a review browsing surface

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

### Governance rule
When new trust elements are added to the pricing column, the reviews summary must remain visually and behaviorally compact:
- summary only
- current product only
- no list rendering
- no sorting
- no global fallback behavior inside this widget

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

### Current code reality note
The current PHP render requires both:
- `Show More Payment Options`
- style-section toggle `Payment Options -> Enable`

for the link to render.

This is a docs-to-code alignment note, not a target-state endorsement.
Governance direction remains: content visibility should not be gated by style-only controls.

### Style controls
Section:

```text
Style -> Payment Options
```

Controls:
- `More Payment Options Typography`
- `More Payment Options Margin`

## License Disclosure Box

### Current behavior
- variation-specific license HTML is generated server-side from structured variation meta
- the license content is embedded into the variation payload used by the widget runtime
- the license surface updates when the active variation changes
- if accordion mode is enabled, the disclosure body opens and closes client-side without changing the active variation

### Accordion controls
The widget supports a license disclosure accordion with:
- mobile enable toggle
- desktop enable toggle
- configurable label

Current runtime note:
- if neither mobile nor desktop toggle is active, the license box renders as a normal non-accordion block
- if accordion mode is active, the trigger is the disclosure control only; it must not be treated as a second variation control

### Governance rules
- the license box is a disclosure surface, not a configurator
- license content must always reflect the active variation
- future trust/content additions in this column must not break the one-state relationship between variation, price, CTA, and disclosure
- empty-state behavior for license content must remain explicit and deterministic

## Variation Model Constraint

Current code reality:
- the widget builds its visible variation button group from the first/main variation attribute only
- the active widget state then resolves the matching variation payload from that axis

Governance implication:
- the current widget is safe when used as a license-first, single-axis selector
- if future products require multiple independent variation axes, the widget contract must be revised before expanding the surface further

## JS Contract

The widget JS keeps a single active variation state and updates all dependent surfaces together:
- price display
- license box
- Add to Cart URL/data attributes
- More payment options checkout URL

This keeps the widget deterministic and prevents the checkout shortcut from drifting away from the selected license.

Any adjacent trust/support widget placed in the same column, such as `BW Trust Box`, remains subordinate to that same deterministic commerce state:
- it must not mutate the active variation
- it must not create an alternate add-to-cart path
- it may support trust/comprehension but not reconfigure the product

## Reviews Settings Integration

Reviews Settings now exposes a dedicated tab:

```text
Blackwork Site -> Reviews Settings -> Trust Content
```

This tab owns:
- `Enable review slider`
- `Enable fixed review box`
- up to 6 curated review slides
- fixed review box WYSIWYG content

This keeps global trust copy centralized instead of duplicating the same review marketing copy across widget instances.

## Default Variation Note

Current code reality:
- initial widget state resolves to the first in-stock variation, or the first available variation when none are in stock

This is the runtime behavior that documentation must currently reflect.
If the project later wants strict WooCommerce default-attributes authority, that must be implemented as an explicit contract change.
