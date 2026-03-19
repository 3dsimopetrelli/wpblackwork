# Blackwork Governance — Task Start Template

## Context
- Task title: Extend `BW-SP Product Description` with description source modes
- Request source: User request on 2026-03-19
- Expected outcome: Update the existing Elementor widget `BW-SP Product Description` so it can render:
  - product description
  - product short description
  - both, in this exact order:
    1. short description
    2. product description
- Constraints:
  - Must follow the current BW Elementor Widgets modular architecture
  - Must remain scoped to WooCommerce single-product context
  - Must preserve existing BW-SP editor identity
  - Must fail safely outside valid product context
  - Must remain deterministic and minimally invasive

## Task Classification
- Domain: Elementor Widgets / WooCommerce / Single Product
- Incident/Task type: Existing widget feature extension
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - BW Elementor Widgets plugin
  - existing `bw-product-description` widget runtime
  - WooCommerce product content resolution
  - Elementor editor preview/template rendering
- Integration impact: Medium
- Regression scope required:
  - widget registration/name unchanged
  - single-product context resolution
  - description HTML rendering
  - editor placeholder behavior
  - style selector scoping

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
  - `includes/widgets/class-bw-product-description-widget.php`
  - `includes/widgets/class-bw-title-product-widget.php`
  - `includes/class-bw-widget-loader.php`

## Scope Declaration
- Proposed strategy:
  - Extend the existing `bw-product-description` widget rather than creating a new widget.
  - Add a `description_source` select control with three deterministic modes:
    - `description`
    - `short_description`
    - `both`
  - In `both`, render short description first and full description immediately after.
  - Preserve HTML markup for both content sources and keep output safely scoped to the existing widget wrapper.
- Files likely impacted:
  - `includes/widgets/class-bw-product-description-widget.php`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
  - optional doc alignment in:
    - `docs/30-features/elementor-widgets/README.md`
    - `docs/10-architecture/elementor-widget-architecture-context.md`
  - `docs/tasks/BW-TASK-20260319-01-start.md`
- Explicitly out-of-scope surfaces:
  - creation of a new widget slug
  - unrelated product title/breadcrumb/price widgets
  - shop/archive description behavior
  - checkout/auth/payment/runtime systems
- Risk analysis:
  - combining short + full description can duplicate content visually if merchants authored overlapping copy
  - rendering both sources must preserve HTML without leaking styles or introducing malformed wrapper order
  - editor placeholder behavior must stay understandable across the three modes
- ADR evaluation (REQUIRED / NOT REQUIRED): NOT REQUIRED

## Runtime Surface Declaration
- New hooks expected: none
- Hook priority modifications: none
- Filters expected: none beyond existing `the_content` formatting path if retained
- AJAX endpoints expected: none
- Admin routes expected: none

## 3.1) Implementation Scope Lock
- All files expected to change are listed? Yes
- Hidden coupling risks discovered? Low

## Governance Impact Analysis
- Authority surfaces touched:
  - existing Elementor widget rendering layer
  - WooCommerce product content resolution
- Data integrity risk: Low
- Security surface changes: None expected
- Runtime hook/order changes: None expected
- Requires ADR? No
- Risk register impact required? No

## System Invariants Check
- Declared invariants that MUST remain true:
  - widget slug remains `bw-product-description`
  - widget title remains `BW-SP Product Description`
  - widget remains safe outside valid product context
  - HTML markup from product content remains preserved
  - styles remain scoped to the widget instance only
- Any invariant at risk? No major risk expected
- Mitigation plan for invariant protection:
  - keep the current product resolver unchanged
  - add only one bounded content-source selector
  - reuse existing wrapper and style selectors

## Determinism Statement
- Input/output determinism declared? Yes
- Ordering determinism declared? Yes
- Retry determinism declared? Yes
- Pagination/state convergence determinism declared? Yes
- Determinism risks and controls:
  - `both` mode must always render in fixed order:
    1. short description
    2. full description
  - empty sources must be skipped deterministically without fatal or unstable placeholders

## Testing Strategy
- Local testing plan:
  - verify widget still appears in Elementor unchanged
  - verify `description_source = description` renders full description only
  - verify `description_source = short_description` renders short description only
  - verify `description_source = both` renders short then full description
  - verify HTML markup survives for all modes
  - verify safe behavior outside valid product context
- Edge cases expected:
  - product with no short description
  - product with no full description
  - product with both empty
- Failure scenarios considered:
  - both mode duplicates wrappers incorrectly
  - short description is stripped to plain text unintentionally
  - editor placeholder becomes misleading for chosen mode

## Documentation Update Plan
- `docs/00-governance/`
  - Impacted? No
  - Target documents (if known): —
- `docs/00-planning/`
  - Impacted? Maybe
  - Target documents (if known): `decision-log.md` if closure formalizes the new behavior
- `docs/10-architecture/`
  - Impacted? Maybe
  - Target documents (if known): `docs/10-architecture/elementor-widget-architecture-context.md`
- `docs/20-development/`
  - Impacted? No
  - Target documents (if known): —
- `docs/30-features/`
  - Impacted? Yes
  - Target documents (if known):
    - `docs/30-features/elementor-widgets/widget-inventory.md`
    - `docs/30-features/elementor-widgets/README.md` if current widget capability summary needs extension
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
  - Revert the widget file change and any related doc updates

## 6A) Documentation Alignment Requirement
Before implementation begins, evaluate:
- `docs/00-governance/` — No
- `docs/00-planning/` — Maybe
- `docs/10-architecture/` — Maybe
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
- short description and full description require incompatible render pipelines
- preserving markup safely would require a broader content-render refactor
- widget behavior becomes non-deterministic when one source is empty

## Governance Enforcement Rule
Implementation must not begin until architecture inspection confirms the safest extension path inside the existing `bw-product-description` widget.
