# Architecture Direction

## Objective
Define a realistic target architecture for the Blackwork Elementor widget subsystem without a big-bang rewrite.

## Implemented baseline (current state)
- `BW Product Grid` is the canonical name for the former `bw-filtered-post-wall` widget, with:
  - slug: `bw-product-grid`
  - class: `BW_Product_Grid_Widget`
- Product-card authority is `BW_Product_Card_Component` across migrated product surfaces (`BW_Product_Card_Renderer` remains compatibility bridge).
- Product Grid current runtime authority lives in:
  - [`../product-grid/product-grid-architecture.md`](../product-grid/product-grid-architecture.md)
- `bw-wallpost` has been removed after governed replacement validation.
- `bw-add-to-cart` and `bw-add-to-cart-variation` have been removed in the governed removal wave.

## Target family boundaries
- `Product Grid Family`
  - canonical wall/query-grid widget (`bw-product-grid`, visible title `BW Product Grid`)
  - `bw-related-products` as product-card reuse reference
- `Product Slider Family`
  - `bw-product-slider` as canonical current product slider
- `Editorial Slider Family`
  - `bw-mosaic-slider` as a mixed-content asymmetric slider
  - desktop uses 5-item mosaic pages
  - mobile collapses to a linear Embla slider
  - product rendering continues to delegate to `BW_Product_Card_Component`
- `Presentation Slider Family`
  - `bw-presentation-slide` as specialized gallery/presentation runtime
  - audit status (2026-03-20): active implementation is widget-local Embla runtime for horizontal and responsive-vertical flows; desktop vertical remains a non-Embla elevator layout
- `Basic Gallery Family`
  - `bw-basic-slide` as lightweight generic image-gallery surface
  - `Slide` mode uses Embla-family runtime with gallery-only content authority
  - `Wall` mode remains a CSS grid, not a masonry/query surface
  - intended role: simple reusable image-gallery widget without presentation-popup, product-card, or showcase-metabox complexity
- `Hero / Banner Family`
  - `bw-hero-slide` as the static-first premium hero surface
  - V1 intentionally avoids slider runtime
  - mode contract is `Static now / Slide later`
  - if slide mode becomes real, it should reuse existing Embla-family architecture rather than inventing a second hero-slider engine
- `Showcase Slider Family`
  - current `bw-showcase-slide` as a curated showcase/content slider
  - reuses Embla-family slider controls and breakpoint direction without inheriting popup/gallery complexity
  - content authority comes from the showcase metabox, not from popup-oriented gallery logic
- `Generic Showcase Family`
  - rationalized outcome of `bw-slick-slider` + `bw-slide-showcase` (single direction under review)
- `Product Utility Family`
  - price/details/variation CTA widgets (`bw-price-variation`, `bw-product-details-table`)

## Shared product-card authority
Canonical authority:
- Markup renderer: `includes/woocommerce-overrides/class-bw-product-card-renderer.php`
- Skin: `assets/css/bw-product-card.css`

Direction:
- product-card-like product rendering should use shared renderer and shared skin
- avoid re-implementing card markup/CTA/price logic in each widget/template
- allow controlled variants through explicit renderer settings (show/hide fields, class modifiers)

### Product Grid family note

This file defines widget-family direction only.

For `BW Product Grid`:
- keep the family-level boundary here:
  - canonical wall/query-grid widget
  - product-card reuse reference
  - future control-group extraction candidate
- keep runtime implementation, filter architecture, cache strategy, and
  performance rules only in:
  - [`../product-grid/product-grid-architecture.md`](../product-grid/product-grid-architecture.md)

## Shared slider-core authority
Direction:
- create a shared `slider-core` runtime authority for lifecycle and common behavior
- keep widget-specific behavior in thin adapters
- maintain one shared engine contract (Slick for current phase)

Current audit note for `bw-product-slider`:
- active runtime is already Embla-based
- query and breakpoint CSS remain widget-local
- card rendering is delegated to `BW_Product_Card_Component`
- the widget is thinner than the historical Slick-era product-slide stack and should be treated as the current product-slider authority

Current audit note for `bw-presentation-slide`:
- the widget still owns a substantial widget-local runtime in `assets/js/bw-presentation-slide.js`
- that runtime directly initializes `BWEmblaCore` for horizontal and responsive-vertical flows, and still owns popup logic, custom cursor behavior, and vertical desktop elevator behavior
- the widget has migrated away from Slick, but it has not yet converged to a thinner shared slider-core adapter
- popup styling direction has been simplified:
  - fixed CSS defaults instead of a growing Elementor popup style surface
  - viewport-bounded popup images
  - explicit interaction gating for popup opening
  - initial arrow visibility controlled defensively to avoid breakpoint flicker before JS init

Implemented direction note for `bw-showcase-slide`:
- built as a new widget, not as a popup-free fork hidden inside `bw-presentation-slide`
- borrows slider settings and responsive breakpoint structure from the Embla slider family
- keeps content authority aligned to the showcase metabox and CTA contract
- excludes popup settings from day one so the widget surface stays focused
- current runtime also owns an explicit widget-local image/overlay stacking contract so showcase copy cannot be covered by late image-state rules

Expected outcomes:
- one core lifecycle (`init`, `reinit`, `destroy`) per scope
- multi-instance safe behavior on frontend/editor/preview
- no duplicated breakpoint parsing and rebuild logic per widget
- widget-level config isolated per instance via data payload/config object
- keep desktop-only elevator behavior as an explicit widget-level concern unless/until a shared gallery-core is introduced

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
- complete generic slider rationalization (`bw-slick-slider` + `bw-slide-showcase`)
- add shared slider-core lifecycle authority after convergence prerequisites
