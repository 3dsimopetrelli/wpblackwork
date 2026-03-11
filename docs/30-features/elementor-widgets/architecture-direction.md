# Architecture Direction

## Objective
Define a realistic target architecture for the Blackwork Elementor widget subsystem without a big-bang rewrite.

## Implemented baseline (current state)
- Product-card authority already in use through `BW_Product_Card_Renderer` across multiple product surfaces.
- `bw-filtered-post-wall` already supports dual mode via `Enable Filter`:
  - enabled: filtered grid behavior
  - disabled: simple grid behavior
- `bw-wallpost` remains active only for compatibility and is explicitly deprecated.
- `bw-add-to-cart` and `bw-add-to-cart-variation` remain active only for compatibility and are explicitly deprecated.

## Target family boundaries
- `Product Grid Family`
  - canonical wall/query-grid widget (future convergence of `bw-filtered-post-wall` + `bw-wallpost`)
  - `bw-related-products` as product-card reuse reference
- `Product Slider Family`
  - `bw-product-slide` as canonical product slider
- `Presentation Slider Family`
  - `bw-presentation-slide` as specialized gallery/presentation runtime
- `Generic Showcase Family`
  - rationalized outcome of `bw-slick-slider` + `bw-slide-showcase` (single direction under review)
- `Product Utility Family`
  - price/details/variation CTA widgets (`bw-price-variation`, `bw-product-details-table`, `bw-add-to-cart-variation`)

## Shared product-card authority
Canonical authority:
- Markup renderer: `includes/woocommerce-overrides/class-bw-product-card-renderer.php`
- Skin: `assets/css/bw-product-card.css`

Direction:
- product-card-like product rendering should use shared renderer and shared skin
- avoid re-implementing card markup/CTA/price logic in each widget/template
- allow controlled variants through explicit renderer settings (show/hide fields, class modifiers)

## Shared slider-core authority
Direction:
- create a shared `slider-core` runtime authority for lifecycle and common behavior
- keep widget-specific behavior in thin adapters
- maintain one shared engine contract (Slick for current phase)

Expected outcomes:
- one core lifecycle (`init`, `reinit`, `destroy`) per scope
- multi-instance safe behavior on frontend/editor/preview
- no duplicated breakpoint parsing and rebuild logic per widget
- widget-level config isolated per instance via data payload/config object

## Shared controls strategy
Direction:
- extract repeated Elementor control blocks into reusable control groups/helpers where practical:
  - query controls
  - layout spacing/columns
  - CTA and overlay button styles
  - slider settings and responsive breakpoints
  - typography/style clusters for product card

Constraint:
- gradual extraction only; no breaking control migrations in one step.

## Asset authority model
Current: mixed bootstrap + per-widget depends.
Target:
- registration authority centralized
- enqueue authority remains widget-dependency driven in Elementor
- no unconditional global widget payload for pages without that widget
- shared handles stable; no handle renaming during initial migration waves

## Migration strategy (gradual)
- no big-bang rewrite
- converge by family in waves
- keep backward-compatible selectors/contracts until each wave is validated
- use closure artifacts and regression protocol updates per wave

## Deferred target steps
- finalize wall/query-grid convergence and retire `bw-wallpost` in governed removal wave
- retire deprecated add-to-cart widgets in governed removal wave
- complete generic slider rationalization (`bw-slick-slider` + `bw-slide-showcase`)
- add shared slider-core lifecycle authority after convergence prerequisites
