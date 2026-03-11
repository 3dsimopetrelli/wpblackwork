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

## Out of scope
- no runtime implementation in this documentation phase
- no immediate widget deletions or rebuilds in code
