# Blackwork Governance — Task Start Template

## Context
- Task title: Extend Elementor widget `BW-SP Product Breadcrumbs`
- Request source: User request on 2026-03-20
- Expected outcome: Extend the existing single-product breadcrumb widget so each major breadcrumb segment can be selectively hidden and the current product title can be truncated by word count with ellipsis.
- Constraints:
  - Must follow the existing BW Elementor Widgets modular architecture
  - Must remain scoped to WooCommerce single-product context
  - Must preserve deterministic category-path selection
  - Must fail safely outside valid product context
  - New behavior must be configurable per widget instance, not as a global plugin option

## Task Classification
- Domain: Elementor Widgets / WooCommerce / Single Product
- Incident/Task type: Feature enhancement
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - BW Elementor Widgets plugin
  - Elementor widget rendering/preview
  - WooCommerce single-product breadcrumb generation
- Integration impact: Medium
- Regression scope required:
  - frontend breadcrumb rendering
  - editor preview placeholder rendering
  - content-template preview output
  - deterministic category resolution

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
  - `includes/widgets/class-bw-product-breadcrumbs-widget.php`
  - `docs/tasks/BW-TASK-20260318-03-closure.md`

## Scope Declaration
- Proposed strategy:
  - Extend the current widget with Content controls that selectively include or exclude major breadcrumb segments.
  - Keep the breadcrumb chain deterministic by continuing to resolve category ancestry first, then applying visibility rules at render time.
  - Add a word-limit control that truncates only the current product title crumb and appends an ellipsis when needed.
  - Keep these controls widget-local in `Content`, not plugin-global, because different templates may need different breadcrumb variants.
- Files likely impacted:
  - `includes/widgets/class-bw-product-breadcrumbs-widget.php`
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
  - `docs/10-architecture/elementor-widget-architecture-context.md`
  - `docs/tasks/BW-TASK-20260320-01-start.md`
- Explicitly out-of-scope surfaces:
  - global WooCommerce breadcrumb settings
  - theme breadcrumb overrides
  - archive/shop breadcrumb redesign
  - unrelated widget refactors
  - style-tab redesign for this widget
- Risk analysis:
  - segment-hiding logic must not produce malformed separator flow
  - title truncation must be deterministic and should operate on words, not arbitrary character slices
  - editor placeholder and frontend render must stay aligned
- ADR evaluation (REQUIRED / NOT REQUIRED): NOT REQUIRED

## Runtime Surface Declaration
- New hooks expected: none
- Hook priority modifications: none
- Filters expected: none
- AJAX endpoints expected: none
- Admin routes expected: none

## 3.1) Implementation Scope Lock
- All files expected to change are listed? Yes
- Hidden coupling risks discovered? Low

## Governance Impact Analysis
- Authority surfaces touched:
  - Elementor widget content controls
  - WooCommerce single-product breadcrumb render logic
- Data integrity risk: Low
- Security surface changes: None expected
- Runtime hook/order changes: None expected
- Requires ADR? No
- Risk register impact required? No

## System Invariants Check
- Declared invariants that MUST remain true:
  - widget registration remains modular and deterministic
  - widget still resolves only a valid single-product context
  - category path selection remains deterministic
  - widget fails safely outside product context
  - controls remain per-instance and do not leak into plugin-global behavior
- Any invariant at risk? No major risk expected
- Mitigation plan for invariant protection:
  - build the full breadcrumb model first, then apply segment-visibility rules
  - truncate only the current product-title crumb
  - mirror the same rules in frontend render and editor placeholder/template output

## Determinism Statement
- Input/output determinism declared? Yes
- Ordering determinism declared? Yes
- Retry determinism declared? Yes
- Pagination/state convergence determinism declared? Yes
- Determinism risks and controls:
  - category path continues to use deepest path first, lowest term ID tie-break
  - title truncation uses a stable word-count rule with fixed ellipsis behavior

## Testing Strategy
- Local testing plan:
  - verify the widget still renders in single-product context
  - verify `Home`, `Shop`, and category path can each be disabled independently
  - verify title word-limit truncates only the current crumb
  - verify no separators are left dangling when segments are removed
  - verify editor preview matches frontend logic
  - verify safe behavior outside valid product context
- Edge cases expected:
  - products without categories
  - products with long titles
  - products with multiple category paths
  - title limit `0` / empty meaning unlimited
- Failure scenarios considered:
  - removed segments still leave orphan separators
  - title truncation affects linked crumbs instead of current crumb only
  - editor placeholder diverges from frontend render

## Documentation Update Plan
- `docs/00-governance/`
  - Impacted? No
  - Target documents (if known): —
- `docs/00-planning/`
  - Impacted? Maybe
  - Target documents (if known): `decision-log.md` if implementation lands
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
  - Revert the widget file and related docs

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
- segment visibility rules require a broader Woo breadcrumb rewrite
- title truncation cannot be implemented deterministically on the current crumb only
- implementation reveals unsafe coupling between editor preview and frontend render logic

## Governance Enforcement Rule
Implementation must not begin until the breadcrumb model, segment-toggle behavior, and title-truncation rule are aligned and documented.
