# Blackwork Governance -- Task Start

## 1) Context
- Task ID: `BW-TASK-20260326-03`
- Task title: Title Product responsive fluid-title sizing controls
- Request source: User request on 2026-03-26
- Expected outcome:
  - inspect the current `BW Title Product` style surface
  - inspect the responsive/fluid sizing strategy already implemented in `BW-UI Big Text`
  - bring the same governed sizing technique into `BW Title Product`
  - expose a dedicated style section for responsive title sizing controls
  - set default title weight to `500` (`medium`)
- Constraints:
  - keep the widget deterministic and editor-friendly
  - preserve existing Elementor typography controls where they still make sense
  - do not break source resolution for product/category/page/text modes
  - avoid introducing a second uncontrolled typography authority

## 2) Task Classification
- Domain: Elementor Widgets / Title Product / Responsive Typography
- Incident/Task type: Governed analysis + pending style refinement
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `includes/widgets/class-bw-title-product-widget.php`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
  - `docs/30-features/elementor-widgets/README.md`
- Integration impact: Medium
- Regression scope required:
  - title rendering across product/category/page/text modes
  - style controls in Elementor
  - fluid sizing behavior between desktop/tablet/mobile

## 3) Analysis Notes
- `BW-UI Big Text` already implements the desired strategy:
  - `Font Size Mode` (`fixed` / `fluid`)
  - fluid min/max font size
  - fluid min/max viewport
  - width control with `ch`, `rem`, `%`, `vw`, `px`
  - deterministic `clamp(...)` generated in PHP
- `BW Title Product` currently relies on a standard Elementor Typography control with large hard-coded defaults.
- Clean implementation path:
  - add a dedicated style section for responsive title sizing
  - compute a widget-local fluid `clamp(...)` expression in PHP
  - set default font weight to `500`
  - keep existing typography control as the authority for family/weight/etc. while extracting font-size ownership into the new fluid/fixed contract

## 4) Testing Strategy
- Verify the new style section appears correctly in Elementor.
- Verify fluid sizing outputs deterministic `clamp(...)` values.
- Verify fixed mode still works.
- Verify product/category/page/text title sources remain unchanged.
