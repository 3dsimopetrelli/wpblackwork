# Blackwork Governance -- Task Start

## 1) Context
- Task ID: `BW-TASK-20260327-03`
- Task title: Related Products mobile-only overlay actions toggle
- Request source: User request on 2026-03-27
- Expected outcome:
  - analyze the existing `BW-SP Related Products` widget
  - add a `Layout` control to toggle `View Product` / `Add to Cart` overlay actions on mobile only
  - keep the default mobile state off
  - preserve current desktop and tablet behavior
- Constraints:
  - do not modify the shared `BW_Product_Card_Component` authority globally
  - implement the behavior as a local extension of the existing widget
  - avoid introducing duplicate rendering logic

## 2) Task Classification
- Domain: Elementor Widgets / Related Products / Responsive Overlay Behavior
- Incident/Task type: Governed feature extension
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `includes/widgets/class-bw-related-products-widget.php`
  - `assets/css/bw-related-products.css`
  - Related Products documentation
- Integration impact: Low
- Regression scope required:
  - related-products desktop rendering
  - related-products tablet rendering
  - related-products mobile overlay behavior

## 3) Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/elementor-widgets/related-products-widget.md`
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
- Code references to read:
  - `includes/widgets/class-bw-related-products-widget.php`
  - `assets/css/bw-related-products.css`
  - `includes/components/product-card/class-bw-product-card-component.php`
  - `assets/css/bw-product-card.css`

## 4) Analysis Notes
- current widget delegates all card markup to `BW_Product_Card_Component`
- current `card_settings` hardcode:
  - `show_buttons => true`
  - `show_add_to_cart => true`
- shared overlay CSS lives in `bw-product-card.css`
- the safest extension point is:
  - add a widget-local layout control
  - emit a wrapper class or data attribute
  - use widget-local CSS below `767px`

## 5) Current Readiness
- Status: OPEN
- Analysis status: READY FOR IMPLEMENTATION
- Immediate recommendation:
  - implement mobile-only overlay suppression in widget-local CSS rather than mutating the shared product-card component contract
