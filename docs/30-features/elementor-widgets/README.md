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
- `newsletter-subscription-widget.md`: fixed-design Mail Marketing/Brevo widget contract for Elementor surfaces

## Confirmed decisions (current)
- `bw-add-to-cart` -> DELETE (completed)
- `bw-add-to-cart-variation` -> DELETE (completed)
- `bw-wallpost` -> DELETE (completed)
- `bw-product-grid` -> canonical wall/query-grid widget
- `bw-product-slide` -> canonical product slider
- `bw-product-description` -> canonical single-product description utility widget
- `bw-title-product` -> canonical single-product title utility widget
- `bw-presentation-slide` -> specialized presentation/gallery slider
- `bw-slick-slider` + `bw-slide-showcase` -> rationalization/merge path under review
- `bw-related-products` -> current best reference for shared product-card reuse

## Visible title alignment (editor)
- Internal slug `bw-product-grid` -> visible title `BW Product Grid`
- Internal slug `bw-slick-slider` -> visible title `BW-UI Product Slider`
- Internal slug `bw-product-slide` -> visible title `BW-SP Gallery Product`
- Internal slug `bw-product-description` -> visible title `BW-SP Product Description`
- Internal slug `bw-title-product` -> visible title `BW Title Product`
- Note: this is editor labeling only (`get_title()`); internal slugs/contracts remain unchanged.
- WooCommerce editor identity note:
  - `bw-title-product` uses a slug-scoped panel mapping so it still receives the BW-SP purple widget card without requiring a `BW-SP` title prefix or a `BW-SP` visible title.

## Current implementation status (completed waves)
- Widget panel naming applied:
  - `BW-UI ...`
  - `BW-SP ...`
  - `DEPRECATED - ...`
- Elementor editor panel differentiation applied (BW-UI, BW-SP, deprecated).
- Shared product-card authority extended and adopted in:
  - Woo related template
  - `bw-slick-slider` product path
  - `bw-product-grid` product path (including AJAX HTML response path)
- `bw-product-grid` supports `Enable Filter = yes/no`.
- `bw-product-grid` also completed a post-implementation runtime hardening pass covering settings alignment, dead-code removal, duplicate-markup reduction, debug-log removal, and resize-handler consolidation.
- Removal/replacement path finalized:
  - `bw-wallpost` -> use `bw-product-grid` with `Enable Filter = No`
  - `bw-add-to-cart` and `bw-add-to-cart-variation` removed; use maintained BW-SP surfaces
- Mail Marketing wave completed:
  - `bw-newsletter-subscription` added as the governed subscription widget for site-wide Brevo capture
  - runtime logic delegated to `BW_MailMarketing_Subscription_Channel`
  - admin behavior delegated to `Mail Marketing -> Subscription`

## BW Product Grid Stabilization (2026)
- A dedicated stabilization wave was completed on `BW Product Grid` to harden runtime behavior and remove residual drift before further feature work.
- Activated previously dormant Elementor controls:
  - `image_toggle`
  - `image_size`
  - `hover_effect`
  - `filter_responsive_breakpoint`
  - responsive filter mobile controls
- Removed dead JavaScript utilities from the runtime:
  - `clearWidgetCache()`
  - `debounce()`
  - `debounceTimers`
  - `loadAndOpenTagsInMobile()`
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

## Deferred future work
- slider-core convergence (`bw-slick-slider` + `bw-slide-showcase` rationalization)
- control-group extraction/reuse where safe

## Out of scope
- no runtime implementation in this documentation phase
- no immediate widget deletions or rebuilds in code
