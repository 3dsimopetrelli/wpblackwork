# Elementor Widgets System

## Scope
This feature area documents the Blackwork custom Elementor widget subsystem.

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

## Confirmed decisions (current)
- `bw-add-to-cart` -> DELETE
- `bw-filtered-post-wall` + `bw-wallpost` -> merge into one canonical wall/query-grid widget
- `bw-product-slide` -> canonical product slider
- `bw-presentation-slide` -> specialized presentation/gallery slider
- `bw-slick-slider` + `bw-slide-showcase` -> rationalization/merge path under review
- `bw-related-products` -> current best reference for shared product-card reuse

## Visible title alignment (editor)
- Internal slug `bw-slick-slider` -> visible title `BW-UI Product Slider`
- Internal slug `bw-product-slide` -> visible title `BW-SP Gallery Product`
- Note: this is editor labeling only (`get_title()`); internal slugs/contracts remain unchanged.

## Current implementation status (completed waves)
- Widget panel naming applied:
  - `BW-UI ...`
  - `BW-SP ...`
  - `DEPRECATED - ...`
- Elementor editor panel differentiation applied (BW-UI, BW-SP, deprecated).
- Shared product-card authority extended and adopted in:
  - Woo related template
  - `bw-slick-slider` product path
  - `bw-wallpost` product path
  - `bw-filtered-post-wall` product path (including AJAX HTML response path)
- `bw-filtered-post-wall` supports `Enable Filter = yes/no`.
- Deprecation path active:
  - `bw-wallpost` -> use `bw-filtered-post-wall` with `Enable Filter = No`
  - `bw-add-to-cart` and `bw-add-to-cart-variation` marked deprecated (kept for compatibility)

## Deferred future work
- final removal wave for deprecated widgets after migration validation
- wall/query-grid consolidation completion (`bw-filtered-post-wall` as canonical single surface)
- slider-core convergence (`bw-slick-slider` + `bw-slide-showcase` rationalization)
- control-group extraction/reuse where safe

## Out of scope
- no runtime implementation in this documentation phase
- no immediate widget deletions or rebuilds in code
