# Blackwork Governance — Task Start Template

## Context
- Task title: Create Elementor widget `BW-SP Product Breadcrumbs`
- Request source: User request on 2026-03-18
- Expected outcome: Add a new Elementor widget that renders a breadcrumb trail for the current WooCommerce single product, following the visual direction shown in the reference screenshot.
- Constraints:
  - Must follow the existing BW Elementor Widgets modular architecture
  - Must be scoped to WooCommerce single-product context
  - Must be visually grouped as a BW-SP widget in Elementor editor
  - Must fail safely outside valid product context
  - Must remain deterministic and minimally invasive

## Task Classification
- Domain: Elementor Widgets / WooCommerce / Single Product
- Incident/Task type: New feature
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - BW Elementor Widgets plugin
  - Elementor widget loader/registration
  - WooCommerce single-product runtime context
  - Elementor editor widget-card identity system
- Integration impact: Medium
- Regression scope required:
  - widget registration
  - Woo single-product context resolution
  - Elementor editor preview
  - frontend single-product rendering

## Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
- Integration docs to read:
  - `docs/10-architecture/elementor-widget-architecture-context.md`
- Ops/control docs to read:
  - `docs/templates/task-closure-template.md`
- Governance docs to read:
  - `docs/00-governance/ai-task-protocol.md`
  - `docs/templates/task-start-template.md`
  - `docs/templates/task-closure-template.md`
- Runbook to follow:
  - Elementor widget validation flow
- Architecture references to read:
  - `includes/class-bw-widget-loader.php`
  - `includes/widgets/class-bw-title-product-widget.php`
  - `assets/js/bw-elementor-widget-panel.js`

## Scope Declaration
- Proposed strategy:
  - Create a new single-product breadcrumb widget using the current widget loader pattern.
  - Reuse the existing BW-SP editor identity convention through widget title/family behavior.
  - Resolve the current product context safely and build a breadcrumb trail from WooCommerce/product hierarchy.
  - Match the screenshot direction at a structural level first, then refine controls only if the current architecture supports them safely.
- Files likely impacted:
  - `includes/widgets/class-bw-product-breadcrumbs-widget.php`
  - optional minimal supporting CSS asset only if required by the widget design
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
  - `docs/10-architecture/elementor-widget-architecture-context.md`
  - `docs/tasks/BW-TASK-20260318-03-start.md`
- Explicitly out-of-scope surfaces:
  - WooCommerce archive/shop breadcrumb redesign
  - unrelated widget refactors
  - product title/description/price widgets
  - checkout/auth/payment/runtime systems
- Risk analysis:
  - breadcrumb source must be deterministic across single-product templates and editor preview
  - visual fidelity to the screenshot may require scoped CSS, which must not leak globally
  - Woo breadcrumb behavior can vary if category ancestry is ambiguous; a clear precedence rule will be required
- ADR evaluation (REQUIRED / NOT REQUIRED): NOT REQUIRED

## Runtime Surface Declaration
- New hooks expected: none
- Hook priority modifications: none
- Filters expected: none unless existing Woo breadcrumb filters are intentionally reused
- AJAX endpoints expected: none
- Admin routes expected: none

## 3.1) Implementation Scope Lock
- All files expected to change are listed? Yes
- Hidden coupling risks discovered? Low, pending breadcrumb source inspection

## Governance Impact Analysis
- Authority surfaces touched:
  - Elementor widget rendering layer
  - WooCommerce single-product display context
  - editor panel identity pattern already present for BW-SP widgets
- Data integrity risk: Low
- Security surface changes: None expected
- Runtime hook/order changes: Minimal, registration-only via existing loader
- Requires ADR? No
- Risk register impact required? No

## System Invariants Check
- Declared invariants that MUST remain true:
  - widget registration remains modular and deterministic
  - widget renders only in a valid single-product context
  - widget fails safely outside product context
  - editor identity remains tightly scoped to the widget family
  - no global CSS leakage is introduced
- Any invariant at risk? No major risk expected
- Mitigation plan for invariant protection:
  - follow existing `BW-SP` widget conventions
  - scope any selectors to the widget wrapper
  - keep context resolution shared and guarded

## Determinism Statement
- Input/output determinism declared? Yes
- Ordering determinism declared? Yes
- Retry determinism declared? Yes
- Pagination/state convergence determinism declared? Yes
- Determinism risks and controls:
  - breadcrumb chain must always be derived from the same product/category context in a stable order
  - if no valid product exists, render safe-empty rather than unstable fallback text

## Testing Strategy
- Local testing plan:
  - verify widget appears in Elementor
  - verify visible title follows BW-SP family naming
  - verify breadcrumb renders in Woo single-product context
  - verify safe behavior outside valid product context
  - verify editor preview resolves the intended product
  - verify styling remains scoped to the widget
- Edge cases expected:
  - product with multiple categories
  - product without categories
  - editor preview on template vs direct product edit
- Failure scenarios considered:
  - widget registers but breadcrumb is empty in valid context
  - breadcrumb uses unstable category resolution
  - editor identity styling does not apply as expected

## Documentation Update Plan
- `docs/00-governance/`
  - Impacted? No
  - Target documents (if known): —
- `docs/00-planning/`
  - Impacted? Maybe
  - Target documents (if known): `decision-log.md` if the widget is implemented
- `docs/10-architecture/`
  - Impacted? Yes
  - Target documents (if known): `docs/10-architecture/elementor-widget-architecture-context.md`
- `docs/20-development/`
  - Impacted? No
  - Target documents (if known): —
- `docs/30-features/`
  - Impacted? Yes
  - Target documents (if known):
    - `docs/30-features/elementor-widgets/README.md`
    - `docs/30-features/elementor-widgets/widget-inventory.md`
- `docs/40-integrations/`
  - Impacted? No
  - Target documents (if known): —
- `docs/50-ops/`
  - Impacted? No
  - Target documents (if known): —
- `docs/60-adr/`
  - Impacted? No
  - Target documents (if known): —
- `docs/60-system/`
  - Impacted? No
  - Target documents (if known): —

## Rollback Strategy
- Revert via commit possible? Yes
- Database migration involved? No
- Manual rollback steps required?
  - Remove the widget file and revert related documentation updates

## 6A) Documentation Alignment Requirement
Before implementation begins, evaluate:
- `docs/00-governance/` — No
- `docs/00-planning/` — Maybe
- `docs/10-architecture/` — Yes
- `docs/20-development/` — No
- `docs/30-features/` — Yes
- `docs/40-integrations/` — No
- `docs/50-ops/` — No
- `docs/60-adr/` — No
- `docs/60-system/` — No

## Acceptance Gate
- Task Classification completed? Yes
- Pre-Task Reading Checklist completed? Yes
- Scope Declaration completed? Yes
- Implementation Scope Lock passed? Yes
- Governance Impact Analysis completed? Yes
- System Invariants Check completed? Yes
- Determinism Statement completed? Yes
- Documentation Update Plan completed? Yes
- Documentation Alignment Requirement completed? Yes

## Abort Conditions
- breadcrumb source cannot be resolved deterministically in single-product context
- visual requirement implies unsafe global editor/runtime CSS
- widget registration requires a broader loader refactor
- implementation reveals undeclared coupling with Woo breadcrumb filters or theme markup

## Governance Enforcement Rule
Implementation must not begin until architecture inspection confirms the correct breadcrumb source strategy, widget placement, and safest BW-SP editor identity method.
