# BW Trust Box Widget

## Purpose

`bw-trust-box` is the standalone trust/support widget extracted from the old lower trust stack previously rendered by `BW-SP Price Variation`.

It owns:
- global curated review slider rendering
- global fixed review summary box rendering
- widget-level digital product info cards
- widget-level FAQ CTA

It does not own:
- current-product review summary logic under the price
- price / variation / license / add-to-cart behavior
- global trust-copy authoring

## Widget Contract

Slug:

```text
bw-trust-box
```

Visible title:

```text
BW Trust Box
```

Primary files:
- `includes/widgets/class-bw-trust-box-widget.php`
- `assets/js/bw-trust-box.js`
- `assets/css/bw-trust-box.css`

## Governance Position

`bw-trust-box` is the authority surface for the lower trust/support stack on product pages.

Global trust content authority remains:
- `Blackwork Site -> Reviews Settings -> Trust Content`

Widget-local authority remains:
- digital product info repeater
- FAQ CTA label/link/toggle
- per-instance visibility toggles for the global slider and global fixed review box

## Current Runtime Reality

The widget may render, in this order:
- review slider
- fixed review summary box
- digital product info cards
- FAQ CTA box

Rendering rules:
- review slider renders only if enabled globally and locally, and if at least one review slide exists
- fixed review box renders only if enabled globally and locally, and if the HTML content is not empty
- digital product info renders only if enabled and the repeater has at least one non-empty item
- FAQ CTA renders only if enabled and a URL exists
  - editor preview may still show a disabled preview state when enabled without a URL

## Asset Authority

- shared Embla engine is reused through:
  - `embla-js`
  - `embla-autoplay-js`
  - `bw-embla-core-js`
  - `bw-embla-core-css`
- widget-specific trust runtime/styling lives in:
  - `bw-trust-box.js`
  - `bw-trust-box.css`

## Relationship to Price Variation

After extraction:
- `BW-SP Price Variation` remains a pricing/license widget
- `BW Trust Box` owns the lower trust/support stack
- the compact inline current-product reviews summary under the `Price Variation` price remains inside `Price Variation`
