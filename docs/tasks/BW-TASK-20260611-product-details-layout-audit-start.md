# Blackwork Governance -- Task Start

## 1) Context
- Task ID: `BW-TASK-20260611-PRODUCT-DETAILS-LAYOUT-AUDIT`
- Task title: Audit BW-SP Product Details layout spacing and expandable elements list
- Request source: User request on 2026-06-11
- Expected outcome:
  - audit the BW-SP Product Details widget layout for the Collection Content row spacing issue
  - identify how to safely add a small expandable read-more treatment for the elements list
  - report current markup, CSS, JS, and accordion reuse opportunities without changing runtime behavior
- Constraints:
  - no implementation in this task pass
  - no unrelated widget changes
  - no broad refactors

## 2) Task Classification
- Domain: Elementor Widgets / Product Details / WooCommerce Product Metabox
- Incident/Task type: Governed audit / layout investigation
- Risk level (L1/L2/L3): L1
- Tier classification (0/1/2/3): 2
- Affected systems:
  - `BW-SP Product Details` widget runtime
  - widget CSS and accordion JS
  - Product Details metabox-driven content rendering
- Integration impact: Low
- Regression scope required:
  - Product Details layout rows
  - accordion behavior on frontend and editor preview
  - responsive spacing contract

## 3) Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/elementor-widgets/product-details-widget.md`
  - `docs/30-features/elementor-widgets/README.md`
- Integration docs to read:
  - `docs/10-architecture/elementor-widget-architecture-context.md`
- Ops/control docs to read:
  - `docs/50-ops/regression-protocol.md`
- Governance docs to read:
  - `docs/templates/task-start-template.md`
  - `docs/governance/task-close.md`
- Runbook to follow:
  - targeted widget regression checks
- Architecture references to read:
  - `includes/widgets/class-bw-product-details-widget.php`
  - `assets/css/bw-product-details.css`
  - `assets/js/bw-product-details.js`

## 4) Scope Declaration
- Proposed strategy:
  - inspect the Collection Content row markup and the current accordion implementation
  - identify the safest location for row-specific spacing adjustments and a line-clamp/read-more affordance
  - confirm whether the existing accordion JS can be reused or whether a lighter row-local toggle is needed
- Files likely impacted:
  - `includes/widgets/class-bw-product-details-widget.php`
  - `assets/css/bw-product-details.css`
  - `assets/js/bw-product-details.js`
- Explicitly out-of-scope surfaces:
  - unrelated Product Details rows
  - other Elementor widgets
  - metabox data model changes
- Risk analysis:
  - the main risk is changing the shared row shell and affecting unrelated content blocks
- ADR evaluation (REQUIRED / NOT REQUIRED): NOT REQUIRED

## 5) Runtime Surface Declaration
- New hooks expected: None
- Hook priority modifications: None
- Filters expected: None
- AJAX endpoints expected: None
- Admin routes expected: None

## 3.1) Implementation Scope Lock
- All files expected to change are listed? Yes
- Hidden coupling risks discovered? No

## Governance Impact Analysis
- Authority surfaces touched: Product Details widget presentation only
- Data integrity risk: Low
- Security surface changes: None
- Runtime hook/order changes: None
- Requires ADR? No
- Risk register impact required? No
- Risk dashboard impact required? No

## 6) System Invariants Check
- Declared invariants that MUST remain true:
  - non-Collection Content rows must remain visually unchanged
  - accordion behavior must remain deterministic on frontend and editor preview
  - Product Details content authority remains in the existing metabox flow
- Any invariant at risk? No
- Mitigation plan for invariant protection:
  - keep any spacing/read-more changes scoped to the Collection Content row and its elements-list markup

## 7) Determinism Statement
- Input/output determinism declared? Yes
- Ordering determinism declared? Yes
- Retry determinism declared? Yes
- Pagination/state convergence determinism declared? Yes
- Determinism risks and controls:
  - any read-more state should be driven by explicit row-local state, not implicit layout heuristics

## 8) Testing Strategy
- Local testing plan:
  - inspect current markup and CSS selectors
  - confirm whether current accordion JS can be reused
  - verify responsive behavior on frontend and editor preview
- Edge cases expected:
  - products with long element lists
  - products with short element lists
  - products where accordion is disabled
- Failure scenarios considered:
  - list clamping hides too much content
  - row spacing changes bleed into unrelated rows

## 9) Documentation Update Plan
Documentation layers that MUST be considered before implementation:

- `docs/00-governance/`
  - Impacted? No
  - Target documents (if known):
- `docs/00-planning/`
  - Impacted? No
  - Target documents (if known):
- `docs/10-architecture/`
  - Impacted? No
  - Target documents (if known):
- `docs/20-development/`
  - Impacted? No
  - Target documents (if known):
- `docs/30-features/`
  - Impacted? No
  - Target documents (if known):
- `docs/40-integrations/`
  - Impacted? No
  - Target documents (if known):
- `docs/50-ops/`
  - Impacted? No
  - Target documents (if known):
- `docs/60-adr/`
  - Impacted? No
  - Target documents (if known):
- `docs/60-system/`
  - Impacted? No
  - Target documents (if known):

## 10) Rollback Strategy
- Revert via commit possible? Yes
- Database migration involved? No
- Manual rollback steps required? None

## 6A) Documentation Alignment Requirement
Before implementation begins, the documentation architecture MUST be evaluated.

The following documentation layers MUST be checked for potential updates:
- `docs/00-governance/`
  - Impacted? No
  - Target documents (if known):
- `docs/00-planning/`
  - Impacted? No
  - Target documents (if known):
- `docs/10-architecture/`
  - Impacted? No
  - Target documents (if known):
- `docs/20-development/`
  - Impacted? No
  - Target documents (if known):
- `docs/30-features/`
  - Impacted? No
  - Target documents (if known):
- `docs/40-integrations/`
  - Impacted? No
  - Target documents (if known):
- `docs/50-ops/`
  - Impacted? No
  - Target documents (if known):
- `docs/60-adr/`
  - Impacted? No
  - Target documents (if known):
- `docs/60-system/`
  - Impacted? No
  - Target documents (if known):

## Acceptance Gate
DO NOT IMPLEMENT YET.

Gate checklist:
- Task Classification completed? Yes
- Pre-Task Reading Checklist completed? Yes
- Scope Declaration completed? Yes
- Implementation Scope Lock passed? Yes
- Governance Impact Analysis completed? Yes
- System Invariants Check completed? Yes
- Determinism Statement completed? Yes
- Documentation Update Plan completed? Yes
- Documentation Alignment Requirement completed? Yes

