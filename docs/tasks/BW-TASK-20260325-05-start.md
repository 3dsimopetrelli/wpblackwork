# Blackwork Governance -- Task Start

## 1) Context
- Task ID: `BW-TASK-20260325-05`
- Task title: Governed analysis for Wallpost/Product Grid column-count refinement
- Request source: User request on 2026-03-25
- Expected outcome:
  - inspect the current documentation and runtime ownership for the former `Wallpost` widget
  - determine where the active column-count authority lives after the governed replacement wave
  - inspect the current `BW Product Grid` control surface, PHP render contract, JS width calculation, and CSS breakpoints related to column count
  - identify the cleanest implementation path for changing the number of columns without regressing the current masonry/grid behavior
  - keep the task aligned with the repository governance workflow before implementation starts
- Constraints:
  - do not assume the removed `bw-wallpost` widget is still the active authority
  - preserve the canonical replacement path: `bw-product-grid` with `Enable Filter = No`
  - any future column-count change must stay coherent across PHP data attributes, JS width calculations, and CSS breakpoint behavior

## 2) Task Classification
- Domain: Elementor Widgets / Product Grid / Legacy Wallpost Replacement
- Incident/Task type: Governed analysis + pending feature refinement
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `BW Product Grid` Elementor widget
  - residual Wallpost CSS layout surface
  - product-grid frontend JS layout calculations
  - product-grid/widget documentation
- Integration impact: Medium
- Regression scope required:
  - desktop/tablet/mobile column calculations
  - masonry/grid width calculations
  - Elementor control surface for columns
  - replacement-path documentation consistency

## 3) Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/product-grid/README.md`
  - `docs/30-features/product-grid/product-grid-architecture.md`
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
  - `docs/30-features/elementor-widgets/architecture-direction.md`
- Code references to read:
  - `includes/widgets/class-bw-product-grid-widget.php`
  - `assets/js/bw-product-grid.js`
  - `assets/css/bw-product-grid.css`
  - `assets/css/bw-wallpost.css`
  - `includes/components/product-card/class-bw-product-card-component.php`
- Governance docs to read:
  - `docs/templates/task-start-template.md`
  - `docs/templates/task-closure-template.md`
  - `docs/governance/task-close.md`

## 4) Scope Declaration
- Proposed strategy:
  - treat `BW Product Grid` as the active runtime authority for Wallpost-style columns
  - inspect the current desktop-only column control and the hardcoded tablet/mobile values
  - map the full authority chain:
    - Elementor control
    - PHP sanitization/defaulting
    - `data-columns-*` render output
    - JS `getColumns()` / `setItemWidths()`
    - CSS fallback grid definitions
  - define the safest implementation path only after the above inspection is complete
- Files likely impacted in the later implementation phase:
  - `includes/widgets/class-bw-product-grid-widget.php`
  - `assets/js/bw-product-grid.js`
  - `assets/css/bw-product-grid.css`
  - `docs/30-features/product-grid/*`
  - `docs/30-features/elementor-widgets/*`
  - `docs/tasks/BW-TASK-20260325-05-closure.md`
- Explicitly out-of-scope surfaces for this analysis phase:
  - unrelated Showcase Slide work
  - unrelated header/hero/grid features
  - reintroducing the removed `bw-wallpost` widget as an active authority

## 5) Runtime Surface Declaration
- New hooks expected in analysis phase: none
- Hook priority modifications expected: none
- AJAX endpoints expected: none
- Admin routes expected: none

## 6) System Invariants
- `BW Product Grid` remains the canonical current wall/query-grid widget.
- `bw-wallpost` remains a removed widget, not a revived runtime authority.
- Column-count behavior must stay deterministic for the same saved settings.
- JS and CSS column/layout behavior must remain aligned with the PHP-emitted `data-*` contract.

## 7) Testing Strategy
- Verify where desktop/tablet/mobile columns are currently set.
- Verify whether the current implementation intentionally hardcodes tablet/mobile at 2.
- Verify how Masonry width calculations consume the column values.
- Verify whether docs already declare the current column-count contract.
