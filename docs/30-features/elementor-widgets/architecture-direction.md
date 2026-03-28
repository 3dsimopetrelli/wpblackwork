# Architecture Direction

## Objective
Define a realistic target architecture for the Blackwork Elementor widget subsystem without a big-bang rewrite.

## Implemented baseline (current state)
- `BW Product Grid` is the canonical name for the former `bw-filtered-post-wall` widget, with:
  - slug: `bw-product-grid`
  - class: `BW_Product_Grid_Widget`
- Product-card authority is `BW_Product_Card_Component` across migrated product surfaces (`BW_Product_Card_Renderer` remains compatibility bridge).
- `bw-product-grid` already supports dual mode via `Show Filters`:
  - enabled: filtered grid behavior
  - disabled: simple grid behavior
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

### Runtime Cleanup and Consistency Improvements
Under the `BW Product Grid` stabilization wave, the widget runtime was hardened without introducing new feature scope.

Implemented improvements:
- removal of duplicate logic
- extraction of shared helpers where safe
- price markup centralized through `bw_fpw_get_price_markup()`
- responsive behavior unified across CSS and JS
- removal of console debug statements
- elimination of dead JavaScript code
- alignment of active Elementor controls with the current runtime contract
- explicit retention of some values as internal/runtime-only defaults (`image_*`, hover, popup, filter breakpoint)

This wave focused on:
- code health
- runtime stability
- editor/runtime consistency

This wave did not introduce new user-facing features; it was a bounded cleanup and consistency pass ahead of future UI refinement and feature work.

### Loading Policy and Animation Sequencing Hardening
This hardening wave also corrected loading-policy propagation and animation sequencing without changing the widget architecture.

Implemented direction:
- loading policy now distinguishes between main images and hover images
- first paint and append flow now follow different loading behavior
- Masonry is no longer blocked by hover-image loading
- reveal starts only after layout-ready completion in both initial and replace flows
- stale stagger timers are cleared before a new reveal cycle starts

Architectural effect:
- improved layout determinism
- reduced visible jumps during first paint and replace-mode updates
- preserved the current infinite-loading and Masonry architecture while making sequencing explicit

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
