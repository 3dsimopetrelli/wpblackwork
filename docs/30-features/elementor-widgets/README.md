# Elementor Widgets System

## Scope
This feature area documents the Blackwork custom Elementor widget subsystem.

Canonical naming note:
- `BW Product Grid` is the current canonical wall/query-grid widget.
- Runtime identifiers:
  - slug: `bw-product-grid`
  - class: `BW_Product_Grid_Widget`
- Historical reference:
  - `BW Product Grid` (formerly `bw-filtered-post-wall`)

Current runtime authority:
- Widget classes: `includes/widgets/`
- Widget registration loader: `includes/class-bw-widget-loader.php`
- Core asset registration: `blackwork-core-plugin.php`
- Reusable Elementor container extensions: `includes/modules/`

## Why this doc set exists
The widget subsystem has grown with mixed patterns:
- duplicated product-card rendering across widget/template surfaces
- duplicated slider runtime logic across multiple JS files
- mixed asset ownership between shared bootstrap and per-widget depends

This directory is the governed documentation baseline for the audit/rebuild program.

## Documents
- `widget-inventory.md`: complete widget inventory and current family map
- `architecture-direction.md`: target architecture (shared product-card, slider-core, controls, asset authority)
- `rationalization-policy.md`: keep/merge/rebuild/delete decisions and deprecation policy
- `migration-sequence.md`: gradual migration sequence and ops/regression alignment
- `editor-panel-widget-families.md`: editor-only widget card family colors, slug/title mapping rules, and deprecated-card hiding behavior inside the Elementor panel
- `newsletter-subscription-widget.md`: fixed-design Mail Marketing/Brevo widget contract for Elementor surfaces
- `product-details-widget.md`: `BW-SP Product Details` — single-product details/compatibility/info-box widget backed by the existing WooCommerce Product Details metabox
- `related-products-widget.md`: BW-SP Related Products widget — proportional grid, component delegation, live preview contract
- `reviews-widget.md`: BW Reviews widget — custom Reviews module adapter, premium modal flow, AJAX list loading, and optional global fallback mode
- `price-variation-widget.md`: BW-SP Price Variation widget — pricing/licensing selector with compact inline trust summary, variation-bound license disclosure, and direct-checkout shortcut
- `trust-box-widget.md`: BW Trust Box widget — standalone trust/support stack with curated review slider, fixed review box, digital product info cards, and FAQ CTA
- `big-text-widget.md`: `BW-UI Big Text` — premium editorial statement widget with controlled line length, fluid `clamp()` scaling, automatic balance mode, and optional manual editorial line grouping
- `hero-slide-widget.md`: `BW-UI Hero Slide` — premium static hero widget with future-ready `Slide` mode surface, centered copy, background image, and glass CTA button grid
- `mosaic-slider-widget.md`: `BW-UI Mosaic Slider` — Embla-based mixed-content slider with 4 desktop mosaic variants, auto-scale/square modes, responsive partial-slide reveal, and shared product-card reuse
- `showcase-slide-widget.md`: `BW-UI Showcase Slide` — Embla-based curated showcase slider powered by product showcase metabox content, with digital/physical footer branching, breakpoint-level fixed frame ratios, curated `Classic Photo (3:2)` peek presets, mobile full-slide CTA behavior, and responsive CTA link-button typography
- `import-info/showcase-slide-metabox-import-map.md`: importer mapping for the Showcase metabox (`product_type`, digital fields, physical fields, CTA, and shared meta keys)

## Confirmed decisions (current)
- `bw-add-to-cart` -> DELETE (completed)
- `bw-add-to-cart-variation` -> DELETE (completed)
- `bw-wallpost` -> DELETE (completed)
- `bw-product-grid` -> canonical wall/query-grid widget
- `bw-product-slider` -> canonical current product slider
- `bw-big-text` -> implemented premium editorial statement widget with fluid size + controlled/manual composition modes
- `bw-hero-slide` -> implemented static-first hero widget with future-ready slide mode surface
- `bw-mosaic-slider` -> implemented mixed-content Embla slider with four desktop mosaic variants, auto-scale modes, and responsive partial-slide reveal
- `bw-product-breadcrumbs` -> canonical single-product breadcrumb utility widget
  - current content surface supports per-instance toggles for `Home`, `Shop`, and category path, plus word-limit truncation on the current product title crumb
- `bw-product-description` -> canonical single-product description utility widget
- `bw-product-details-table` -> canonical single-product details utility widget with Product Details / Compatibility / Info Box content branching
- `bw-title-product` -> canonical single-product title utility widget with fluid/fixed responsive title sizing and width-measure controls
- `bw-presentation-slide` -> specialized presentation/gallery slider
- `bw-showcase-slide` -> implemented Embla-based showcase slider driven by showcase metabox content
- `bw-slick-slider` + `bw-slide-showcase` -> rationalization/merge path under review
- `bw-related-products` -> current best reference for shared product-card reuse
  - current widget-local extension also supports tablet/mobile suppression of overlay CTA actions without mutating the shared component globally
- `bw-reviews` -> canonical custom product-reviews widget backed by the Reviews module
- `bw-price-variation` -> pricing widget that can consume a compact read-only Reviews summary for the current product and expose variation-bound license disclosure without becoming a second reviews authority
- `bw-trust-box` -> standalone trust/support stack widget consuming global Reviews trust content plus widget-level info/FAQ controls

## BW-UI Presentation Slider
- Widget slug: `bw-presentation-slide`
- Visible editor title: `BW-UI Presentation Slider`
- Current runtime in this repository state is **Embla-based** for:
  - horizontal carousel mode
  - vertical responsive main/thumb mode
- Vertical desktop remains a non-Embla elevator layout with thumbnail rail + main image panel.
- Current feature surface:
  - horizontal Embla carousel mode with breakpoint repeater controls
  - vertical elevator/gallery mode with desktop thumbnails
  - responsive vertical fallback implemented as synchronized Embla main/thumb viewports
  - optional popup overlay gallery
  - optional custom cursor runtime with zoom / prev / next states
  - image source: custom gallery or current WooCommerce product gallery query
- Asset model:
  - PHP widget depends on:
    - `embla-js`
    - `embla-autoplay-js`
    - `bw-embla-core-js`
    - `bw-embla-core-css`
    - `bw-presentation-slide-script`
  - asset registration remains centralized in `blackwork-core-plugin.php`
- Audit note (2026-03-19):
  - the active JS runtime initializes `BWEmblaCore`
  - shared Embla base assets live in:
    - `assets/js/bw-embla-core.js`
    - `assets/css/bw-embla-core.css`
  - popup overlay runtime appends to `<body>`
  - popup style surface was later intentionally simplified back to fixed CSS defaults instead of Elementor popup style controls
  - popup title / close defaults now live in widget CSS:
    - title desktop/tablet: `16px / 20px`
    - title mobile: `12px / 18px`
    - close text: `16px`
  - popup image height is bounded to the viewport
  - popup opening is guarded by a real `pointerdown -> pointerup` sequence
  - arrow buttons render hidden by default and are shown only after breakpoint JS confirms `show arrows`

## Visible title alignment (editor)
- Internal slug `bw-product-grid` -> visible title `BW Product Grid`
- Internal slug `bw-slick-slider` -> visible title `BW-UI Product Slider`
- Internal slug `bw-product-slider` -> visible title `BW-UI Product Slider`
- Internal slug `bw-product-breadcrumbs` -> visible title `BW-SP Product Breadcrumbs`
- Internal slug `bw-product-description` -> visible title `BW-SP Product Description`
- Internal slug `bw-title-product` -> visible title `BW Title Product`
- Note: this is editor labeling only (`get_title()`); internal slugs/contracts remain unchanged.
- WooCommerce editor identity note:
  - `bw-title-product` uses a slug-scoped panel mapping so it still receives the BW-SP purple widget card without requiring a `BW-SP` title prefix or a `BW-SP` visible title.
- Elementor panel family-color note:
  - editor panel colors are now governed by a slug-first family-class system, not by visible title prefixes alone
  - `BW Reviews` and `BW Title Product` are explicit exceptions so they still receive the purple SP family treatment
  - `BW Product Grid` is explicitly mapped to the black UI family
  - the dedicated guide is `editor-panel-widget-families.md`

## Current implementation status (completed waves)
- Widget panel naming applied:
  - `BW-UI ...`
  - `BW-SP ...`
  - `DEPRECATED - ...`
- Elementor editor panel differentiation applied (BW-UI, BW-SP, deprecated).
- Reusable BW sticky sidebar controls are available on Elementor containers through the plugin runtime:
  - opt-in only (default: `BW Sticky = None`)
  - JS-based sticky (`position:fixed` + placeholder) — works regardless of ancestor `overflow` constraints
  - optional `Stay Within Column` bound: element stops at parent row bottom without DOM teleportation
  - intended target: the outer pricing/sidebar container

## Elementor Sticky Sidebar Extension
- Scope: Elementor containers (not widget-specific).
- Controls added to containers (in the Motion Effects section of the Advanced tab):
  - `BW Sticky` — `None` / `Top`
  - `Sticky Offset` — px slider
  - `Sticky On` — Desktop Only / Desktop + Tablet / All Devices
  - `Stay Within Column` — stops the element at the parent row bottom edge
- Intended usage:
  - apply the control to the outer pricing/sidebar container
  - do not apply it to inner CTA or quantity blocks unless that narrower target is explicitly desired
- Implementation: **JS-based** (`position:fixed` + in-place placeholder). CSS `position:sticky` was evaluated and abandoned — Elementor ancestor containers use `overflow:hidden` which silently suppresses CSS sticky positioning. JS fixed positioning is not affected by overflow constraints.
- JS file: `includes/modules/elementor-sticky-sidebar/assets/elementor-sticky-sidebar.js`
- Key implementation contracts:
  - element stays in its original DOM position; an invisible `bw-ess-placeholder` div holds the layout gap
  - placeholder copies flex-item properties (`flexGrow`, `flexShrink`, `flexBasis`, `alignSelf`) from the original element so the parent flex container does not reflow
  - padding values are frozen as computed px at stick time to prevent Elementor percentage paddings (`--container-padding-*`) from expanding against the viewport
  - `Stay Within Column` uses negative `top` values — no DOM teleportation — CSS inheritance is fully preserved
  - `bw-ess-stuck` class is added to the container while sticky, removable for project-specific CSS hooks
- Breakpoints:
  - Desktop Only: ≥ 1025 px
  - Desktop + Tablet: ≥ 768 px
  - All Devices: always active
- Shared product-card authority extended and adopted in:
  - Woo related template
  - `bw-slick-slider` product path
  - `bw-product-grid` product path (including AJAX HTML response path)
- `bw-product-grid` supports filtered/simple grid mode via `Show Filters = yes/no`.
- `bw-product-grid` also completed a post-implementation runtime hardening pass covering settings alignment, dead-code removal, duplicate-markup reduction, debug-log removal, and resize-handler consolidation.
- Removal/replacement path finalized:
  - `bw-wallpost` -> use `bw-product-grid` with `Show Filters = No`
  - `bw-add-to-cart` and `bw-add-to-cart-variation` removed; use maintained BW-SP surfaces
- Mail Marketing wave completed:
  - `bw-newsletter-subscription` added as the governed subscription widget for site-wide Brevo capture
  - runtime logic delegated to `BW_MailMarketing_Subscription_Channel`
  - admin behavior delegated to `Mail Marketing -> Subscription`

## BW Product Grid Stabilization (2026)
- A dedicated stabilization wave was completed on `BW Product Grid` to harden runtime behavior and remove residual drift before further feature work.
- Active Elementor controls were aligned with the current runtime contract:
  - filtered/simple mode via `Show Filters`
  - infinite loading (`Initial Items`, `Load Batch Size`)
  - layout toggles (`Desktop Columns`, `Container Max Width`, `Masonry Effect`)
  - content visibility (`Show Title`, `Show Description`, `Show Price`)
  - touch-device hover suppression
- Internal/runtime-only values remain non-exposed in Elementor:
  - `image_toggle`
  - `image_size`
  - `image_mode`
  - `hover_effect`
  - `open_cart_popup`
  - filter breakpoint (`900`)
- Removed dead JavaScript utilities from the runtime:
  - `clearWidgetCache()`
  - `debounce()`
  - `debounceTimers`
  - `loadAndOpenTagsInMobile()`
- Subsequent refinement wave added:
  - mobile filter trigger redesign to a bordered white pill with a green icon shell
  - CSS-first mobile filter visibility below the responsive breakpoint to prevent desktop-filter FOUC on reload
  - `Layout > Disable Hover Actions on Tablet & Mobile` to suppress product-card hover overlays and hover media below desktop widths
- Cleaned up unused PHP methods and dead branches.
- Consolidated resize handling into a single dispatcher.
- Aligned responsive breakpoint behavior across PHP, JS, and CSS.

Final responsive contract:
- mobile <= 767px
- tablet 768-1024px
- desktop >= 1025px

### Lazy Loading and Animation Stabilization
- `BW_Product_Card_Component` now supports explicit loading controls:
  - `image_loading`
  - `hover_image_loading`
- AJAX loading policy is now explicit:
  - initial batch can be eager
  - appended batches remain lazy
  - hover images stay lazy
- Initial server-side render now uses:
  - eager for first-row main images
  - lazy for later initial items
  - lazy for hover images
- Masonry/reveal waits are now limited to primary images only:
  - `img.bw-slider-main`
- Initial reveal and replace-mode reveal are now sequenced behind grid-ready completion.
- Stale stagger timers are cleared before new reveal cycles so replaced content cannot be affected by orphaned timeouts.
- `BW-UI Mosaic Slider` now follows the same governed image-loading approach:
  - server markup starts from `auto`/`lazy`, not duplicate eager defaults on both responsive branches
  - JS promotes only the active viewport primary images
  - the hidden inactive viewport is demoted to lazy
  - wrapper reveal waits for the first active primary image instead of bare Embla init timing

## Deferred future work
- slider-core convergence (`bw-slick-slider` + `bw-slide-showcase` rationalization)
- control-group extraction/reuse where safe

## Out of scope
- no runtime implementation in this documentation phase
- no immediate widget deletions or rebuilds in code
