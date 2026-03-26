# Blackwork Governance -- Task Start

## 1) Context
- Task ID: `BW-TASK-20260326-02`
- Task title: Shared product-card typography default alignment across component consumers
- Request source: User request on 2026-03-26
- Expected outcome:
  - inspect the shared `BW_Product_Card_Component` typography defaults and verify where they are inherited vs overridden
  - set new shared defaults:
    - title `14px`
    - description `14px`
    - price `12px`
  - realign component-consuming widgets so these values remain the default whenever Elementor style controls are not explicitly used
  - preserve existing widget-level typography controls as the final authority when the user configures them in Elementor
- Constraints:
  - no authority drift between shared component CSS and widget-local style controls
  - no regression in widgets that intentionally use bespoke editorial typography
  - do not break product-card markup or product-card loading/overlay behavior

## 2) Task Classification
- Domain: Shared Components / Product Card / Widget Typography Defaults
- Incident/Task type: Governed analysis + pending runtime/style alignment
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `includes/components/product-card/class-bw-product-card-component.php`
  - `assets/css/bw-product-card.css`
  - widgets that consume `BW_Product_Card_Component`
  - feature documentation for shared product-card consumers
- Integration impact: Medium
- Regression scope required:
  - product-card default typography when no widget-level Elementor style is set
  - widget-local overrides that intentionally diverge
  - shared content rendering in Product Grid, Product Slider, Related Products, and Mosaic Slider product-card paths

## 3) Analysis Notes
- Confirmed shared component typography exists in `assets/css/bw-product-card.css`.
- Confirmed current shared defaults are larger than the requested target.
- Confirmed some widgets inherit these defaults more directly, while others add local CSS classes and local typography rules.
- Clean authority chain for implementation:
  - shared component default
  - widget-local default override only where intentionally bespoke
  - Elementor style-control output remains final authority

## 4) Testing Strategy
- Verify shared component defaults render as `14 / 14 / 12` when no Elementor widget style override is present.
- Verify widgets with style controls still override the defaults correctly.
- Verify widgets with intentional bespoke typography are only changed if explicitly included in the alignment wave.
