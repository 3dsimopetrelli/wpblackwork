# Blackwork Governance -- Task Start

## 1) Context
- Task ID: `BW-TASK-20260325-03`
- Task title: Governed architecture and implementation of `Big Text` Elementor widget
- Request source: User request on 2026-03-25
- Expected outcome:
  - inspect current Elementor widget architecture before implementation
  - inspect how current widgets structure content/style controls, sanitization, and responsive controls
  - inspect whether reusable repository helpers already exist for typography/layout handling
  - evaluate the best practical CSS strategy for premium editorial large text composition
  - recommend the best real-world approach for `clamp()`, `max-inline-size`, `text-wrap: balance`, and optional manual editorial grouping
  - implement a new `Big Text` widget with strong responsive composition across desktop, tablet, and mobile
  - set the widget default text to the statement shown in the user-provided reference
  - keep implementation and documentation aligned with repository governance standards
- Constraints:
  - follow repository widget architecture and coding style
  - keep the widget premium/editorial, not generic
  - do not pretend pure CSS guarantees identical line breaks across all viewports
  - if exact composition cannot be guaranteed automatically, support a controlled editorial fallback
  - keep the runtime deterministic and server-rendered
  - avoid introducing unnecessary JS if CSS + controlled markup is sufficient

## 2) Task Classification
- Domain: Elementor Widgets / Editorial Typography / Responsive Composition
- Incident/Task type: Governed feature implementation + architectural recommendation
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - Elementor widget runtime
  - core widget asset registration
  - widget documentation and inventory
- Integration impact: Medium
- Regression scope required:
  - widget discovery/loading
  - Elementor editor rendering
  - content sanitization/output escaping
  - responsive control output
  - editorial/manual composition mode behavior

## 3) Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/10-architecture/elementor-widget-architecture-context.md`
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
  - `docs/30-features/elementor-widgets/architecture-direction.md`
- Integration docs to read:
  - none required
- Ops/control docs to read:
  - `docs/50-ops/regression-protocol.md`
  - `docs/50-ops/release-gate.md`
- Governance docs to read:
  - `docs/templates/task-start-template.md`
  - `docs/templates/task-closure-template.md`
  - `docs/governance/task-close.md`
- Runbook to follow:
  - repository widget creation + governed documentation workflow
- Architecture references to read:
  - `includes/class-bw-widget-loader.php`
  - `blackwork-core-plugin.php`
  - `includes/helpers.php`
  - `includes/class-bw-widget-helper.php`
  - `includes/widgets/class-bw-button-widget.php`
  - `includes/widgets/class-bw-animated-banner-widget.php`
  - `includes/widgets/class-bw-product-description-widget.php`
  - `includes/widgets/class-bw-product-breadcrumbs-widget.php`

## 4) Scope Declaration
- Proposed strategy:
  - create a new widget `bw-big-text`
  - keep the render path server-side and CSS-first
  - use a constrained textarea content surface with limited inline HTML allowlist instead of a fully open WYSIWYG surface
  - support a composition mode selector so the widget can switch between automatic balancing, width-led composition, and manual editorial grouping
  - use `max-inline-size` as the primary line-length control surface
  - support a fluid-size option based on `clamp()` for premium scaling without relying only on breakpoint jumps
  - support manual editorial grouping via newline-delimited line groups when tighter composition control is required
- Files likely impacted:
  - `includes/widgets/class-bw-big-text-widget.php`
  - `assets/css/bw-big-text.css`
  - `blackwork-core-plugin.php`
  - `docs/30-features/elementor-widgets/big-text-widget.md`
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
  - `docs/00-planning/decision-log.md`
  - `docs/tasks/BW-TASK-20260325-03-start.md`
  - `docs/tasks/BW-TASK-20260325-03-closure.md`
- Explicitly out-of-scope surfaces:
  - JavaScript-driven text measurement/reflow engine
  - per-device manual line authoring UI
  - generalized repository-wide typography helper framework
  - animation/marquee behavior
- Risk analysis:
  - long text can expose the practical limits of `text-wrap: balance`
  - too-open content HTML can create inconsistent editor output and sanitization complexity
  - fluid type controls can conflict with Elementor-native typography if the authority is unclear
- ADR evaluation (REQUIRED / NOT REQUIRED): NOT REQUIRED

## 5) Runtime Surface Declaration
- New hooks expected:
  - none beyond the widget discovery/asset registration surfaces already used for widgets
- Hook priority modifications:
  - none expected
- Filters expected:
  - none expected
- AJAX endpoints expected:
  - none
- Admin routes expected:
  - none

## 3.1) Implementation Scope Lock
- All files expected to change are listed? Yes
- Hidden coupling risks discovered? Yes
  - asset registration must stay aligned with the mixed widget bootstrap model
  - content/title-based widget family styling in the Elementor panel should keep working via existing `BW-UI` naming rules

## Governance Impact Analysis
- Authority surfaces touched:
  - Elementor widget runtime
  - widget asset registration
  - feature/planning documentation
- Data integrity risk:
  - low; widget stores only Elementor-authored presentation settings
- Security surface changes:
  - sanitized inline content output is required
  - manual composition mode must not introduce unsafe HTML output
- Runtime hook/order changes:
  - one new widget asset registration surface in `blackwork-core-plugin.php`
- Requires ADR? No
- Risk register impact required? No
- Risk dashboard impact required? No

## System Invariants Check
- Declared invariants that MUST remain true:
  - widget loader remains file-discovery based
  - asset registration authority remains centralized/mixed exactly as current widget architecture allows
  - content rendering remains deterministic for the same saved settings
  - the widget does not create a second typography authority outside Elementor controls + widget-local CSS vars
  - the widget fails soft when advanced CSS features are unsupported
- Any invariant at risk? No
- Mitigation plan for invariant protection:
  - keep output server-rendered
  - keep composition modes explicit
  - provide CSS fallbacks where feature support varies

## Determinism Statement
- Input/output determinism declared? Yes
- Ordering determinism declared? Yes
- Retry determinism declared? Yes
- Pagination/state convergence determinism declared? Yes
- Determinism risks and controls:
  - manual editorial line groups must render in authored order
  - automatic composition must map consistently from saved mode/width/fluid settings
  - unsupported CSS enhancements must degrade to predictable wrapped text instead of broken layout

## Testing Strategy
- Local testing plan:
  - verify widget registers and appears in Elementor
  - verify default text matches the provided statement
  - verify all composition modes render correctly
  - verify content sanitization/output escaping
  - verify fluid sizing, max width, alignment, spacing, and typography controls
  - verify desktop/tablet/mobile composition remains readable and visually compact
- Edge cases expected:
  - very long unbroken words
  - empty content
  - manual grouping lines longer than viewport width
  - balance mode on longer multi-line text
- Failure scenarios considered:
  - unsupported `text-wrap: balance`
  - conflicting fixed vs fluid font-size controls
  - malformed inline HTML in textarea content

## Documentation Update Plan
- `docs/00-governance/`
  - Impacted? No
  - Target documents (if known):
    - none
- `docs/00-planning/`
  - Impacted? Yes
  - Target documents (if known):
    - `docs/00-planning/decision-log.md`
- `docs/10-architecture/`
  - Impacted? No
  - Target documents (if known):
    - none
- `docs/20-development/`
  - Impacted? No
  - Target documents (if known):
    - none
- `docs/30-features/`
  - Impacted? Yes
  - Target documents (if known):
    - `docs/30-features/elementor-widgets/README.md`
    - `docs/30-features/elementor-widgets/widget-inventory.md`
    - `docs/30-features/elementor-widgets/big-text-widget.md`
- `docs/40-integrations/`
  - Impacted? No
  - Target documents (if known):
    - none
- `docs/50-ops/`
  - Impacted? No
  - Target documents (if known):
    - none
- `docs/60-adr/`
  - Impacted? No
  - Target documents (if known):
    - none
- `docs/60-system/`
  - Impacted? No
  - Target documents (if known):
    - none

## Rollback Strategy
- Revert via commit possible? Yes
- Database migration involved? No
- Manual rollback steps required?
  - remove the widget file, CSS asset registration, and documentation entries via commit revert

## 6A) Documentation Alignment Requirement
- `docs/00-governance/`
  - Impacted? No
  - Target documents (if known):
    - none
- `docs/00-planning/`
  - Impacted? Yes
  - Target documents (if known):
    - `docs/00-planning/decision-log.md`
- `docs/10-architecture/`
  - Impacted? No
  - Target documents (if known):
    - none
- `docs/20-development/`
  - Impacted? No
  - Target documents (if known):
    - none
- `docs/30-features/`
  - Impacted? Yes
  - Target documents (if known):
    - `docs/30-features/elementor-widgets/README.md`
    - `docs/30-features/elementor-widgets/widget-inventory.md`
    - `docs/30-features/elementor-widgets/big-text-widget.md`
- `docs/40-integrations/`
  - Impacted? No
  - Target documents (if known):
    - none
- `docs/50-ops/`
  - Impacted? No
  - Target documents (if known):
    - none
- `docs/60-adr/`
  - Impacted? No
  - Target documents (if known):
    - none
- `docs/60-system/`
  - Impacted? No
  - Target documents (if known):
    - none

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

## Release Gate Awareness
All tasks will pass through the Release Gate before deployment.
