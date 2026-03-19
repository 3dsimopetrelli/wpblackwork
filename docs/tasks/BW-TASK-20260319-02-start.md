# Blackwork Governance — Task Start Template

## Context
- Task title: Add reusable sticky sidebar controls for Elementor containers
- Request source: User request on 2026-03-19
- Expected outcome: Add a BW-managed sticky sidebar feature for Elementor container-based layouts, applied to the outer pricing/sidebar container without relying on Elementor Pro sticky controls.
- Constraints:
  - Must be CSS-first (`position: sticky`) unless a JS fallback becomes strictly necessary
  - Must attach to the outer Elementor container selected by the editor
  - Must be reusable across layouts handled by the plugin
  - Must remain safe for responsive layouts
  - Must avoid broad global editor/runtime conflicts

## Task Classification
- Domain: Elementor container runtime extension / frontend layout behavior
- Incident/Task type: Reusable feature addition
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - Elementor container controls
  - Elementor frontend container rendering
  - BW plugin frontend/editor asset loading
- Integration impact: Medium
- Regression scope required:
  - Elementor container control registration
  - Elementor frontend rendering
  - responsive container layouts
  - editor preview behavior

## Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/elementor-widgets/README.md`
- Integration docs to read:
  - `docs/10-architecture/elementor-widget-architecture-context.md`
- Ops/control docs to read:
  - `docs/templates/task-closure-template.md`
- Governance docs to read:
  - `docs/00-governance/ai-task-protocol.md`
  - `docs/templates/task-start-template.md`
  - `docs/templates/task-closure-template.md`
- Runbook to follow:
  - Elementor frontend/widget validation flow
- Architecture references to read:
  - `blackwork-core-plugin.php`
  - `includes/class-bw-widget-loader.php`
  - `assets/js/bw-elementor-widget-panel.js`
  - existing sticky references in `assets/css/bw-product-slide.css`, `assets/css/bw-presentation-slide.css`, `assets/js/bw-checkout.js`

## Scope Declaration
- Proposed strategy:
  - Add a reusable module that extends Elementor `container` controls.
  - Add a dedicated BW section with:
    - sticky enable switcher
    - sticky top offset control
    - responsive activation control
  - Apply sticky classes/CSS variables to the selected container wrapper at render time.
  - Implement CSS-first sticky behavior scoped to the chosen container.
  - Do not add JS unless CSS-only proves insufficient for the targeted layout.
- Files likely impacted:
  - `blackwork-core-plugin.php`
  - `includes/modules/elementor-sticky-sidebar/elementor-sticky-sidebar-module.php`
  - `includes/modules/elementor-sticky-sidebar/assets/elementor-sticky-sidebar.css`
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/10-architecture/elementor-widget-architecture-context.md`
  - `docs/tasks/BW-TASK-20260319-02-start.md`
- Explicitly out-of-scope surfaces:
  - Elementor Pro sticky controls
  - widget-specific sticky rewrites
  - checkout sticky runtime refactor
  - non-Elementor templates
- Risk analysis:
  - sticky can fail if ancestor containers use incompatible `overflow`
  - sticky can behave badly in stretched flex contexts unless `align-self: flex-start` is applied
  - responsive activation must be explicit to avoid mobile regressions
- ADR evaluation (REQUIRED / NOT REQUIRED): NOT REQUIRED

## Runtime Surface Declaration
- New hooks expected:
  - Elementor container control extension hook(s)
  - Elementor container render attribute hook(s)
  - frontend/editor style enqueue hook(s)
- Hook priority modifications: none expected
- Filters expected: none
- AJAX endpoints expected: none
- Admin routes expected: none

## 3.1) Implementation Scope Lock
- All files expected to change are listed? Yes
- Hidden coupling risks discovered? Low, pending container hook verification

## Governance Impact Analysis
- Authority surfaces touched:
  - Elementor container control surface
  - Elementor frontend render attributes
  - plugin asset loading
- Data integrity risk: Low
- Security surface changes: None expected
- Runtime hook/order changes: Moderate but bounded to Elementor container lifecycle hooks
- Requires ADR? No
- Risk register impact required? No

## System Invariants Check
- Declared invariants that MUST remain true:
  - feature applies to selected outer container, not implicitly to inner CTA blocks
  - sticky remains opt-in and disabled by default
  - responsive behavior is explicit and deterministic
  - no global sticky behavior is introduced site-wide
- Any invariant at risk? No major risk expected
- Mitigation plan for invariant protection:
  - container-only controls
  - wrapper-scoped classes and CSS variables
  - CSS-first implementation with no unnecessary JS

## Determinism Statement
- Input/output determinism declared? Yes
- Ordering determinism declared? Yes
- Retry determinism declared? Yes
- Pagination/state convergence determinism declared? Yes
- Determinism risks and controls:
  - same control values must always yield the same wrapper classes and sticky behavior
  - responsive activation modes must resolve through fixed breakpoints

## Testing Strategy
- Local testing plan:
  - enable sticky on an outer pricing/sidebar container
  - verify top offset behavior
  - verify disabled-by-default behavior
  - verify desktop-only behavior
  - verify editor preview and frontend consistency
- Edge cases expected:
  - ancestor overflow blocks sticky
  - flex stretch prevents expected sticky behavior
  - very tall sticky container larger than viewport
- Failure scenarios considered:
  - class/attribute not attached to the container wrapper
  - sticky activates on wrong breakpoint
  - Elementor inline styles conflict with sticky CSS

## Documentation Update Plan
- `docs/00-governance/`
  - Impacted? No
  - Target documents (if known): —
- `docs/00-planning/`
  - Impacted? Maybe
  - Target documents (if known): `decision-log.md`
- `docs/10-architecture/`
  - Impacted? Yes
  - Target documents (if known): `docs/10-architecture/elementor-widget-architecture-context.md`
- `docs/20-development/`
  - Impacted? Maybe
  - Target documents (if known): if the container-extension pattern is worth documenting later
- `docs/30-features/`
  - Impacted? Yes
  - Target documents (if known): `docs/30-features/elementor-widgets/README.md`
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
  - Remove the sticky sidebar module include and related asset file

## 6A) Documentation Alignment Requirement
Before implementation begins, evaluate:
- `docs/00-governance/` — No
- `docs/00-planning/` — Maybe
- `docs/10-architecture/` — Yes
- `docs/20-development/` — Maybe
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
- Elementor container hooks do not allow clean control insertion/render attributes
- CSS-only sticky cannot be made stable enough for the target container layouts
- implementation would require unsafe global overrides on Elementor containers

## Governance Enforcement Rule
Implementation must not begin until architecture inspection confirms the correct Elementor container hooks and the safest wrapper-attribute strategy.

---

## Post-Implementation Note (2026-03-19)

The CSS-first constraint (criterion 6) was overridden during live testing:

**Why CSS-first was abandoned:**
Elementor ancestor containers use `overflow:hidden` (set by Elementor's layout engine), which causes `position:sticky` to be silently ignored — sticky requires no `overflow` restriction on any ancestor. This is a structural constraint of Elementor layouts that cannot be worked around with CSS alone.

**Final approach:**
JS-based `position:fixed` with an in-place placeholder (DOM element stays in original position; a `visibility:hidden` placeholder holds the layout gap). This approach:
- bypasses `overflow:hidden` on ancestors (fixed elements are not clipped by overflow)
- preserves CSS inheritance from Elementor parent containers (no DOM teleportation)
- uses negative `top` values to simulate the bound behavior without moving the element in the DOM

Three post-implementation bugs were resolved (overflow clipping, width shrinking, padding expansion). See `BW-TASK-20260319-02-closure.md` § Post-Implementation Fixes for full details.
