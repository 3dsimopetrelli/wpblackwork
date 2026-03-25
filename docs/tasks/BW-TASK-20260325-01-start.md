# Blackwork Governance -- Task Start

## 1) Context
- Task ID: `BW-TASK-20260325-01`
- Task title: Governed architecture and implementation of `Hero Slide` Elementor widget
- Request source: User request on 2026-03-25
- Expected outcome:
  - inspect current Elementor widget architecture before implementation
  - inspect how current widgets structure content/style controls and responsive controls
  - inspect whether reusable repository patterns already exist for buttons, background media, and archive/category links
  - define the cleanest structure for a new `Hero Slide` widget
  - implement `Hero Slide` with `Static` and `Slide` mode selector, but only `Static` mode runtime in V1
  - keep the implementation responsive, configurable in Elementor, and future-ready for a later slide mode
  - align implementation and documentation to repository governance standards from start through closure
- Constraints:
  - follow repository architecture and coding style
  - reuse existing patterns where practical
  - do not overengineer V1
  - do not implement real slide runtime in V1
  - preserve a clean future path for a later `Slide` mode
  - widget must support:
    - hero background image
    - large centered title
    - centered subtitle
    - CTA button row/grid
    - responsive hero height in `vh`
    - responsive padding/spacing/typography
  - visual references provided by the user show:
    - centered hero copy
    - dark premium background
    - subtle purple glow / overlay atmosphere
    - rounded glass-like CTA buttons that wrap responsively

## 2) Task Classification
- Domain: Elementor Widgets / Hero UI Surface / Responsive Visual Components
- Incident/Task type: Governed feature implementation
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - Elementor widget runtime
  - core widget asset registration
  - widget inventory / feature docs / regression docs
- Integration impact: Medium
- Regression scope required:
  - widget discovery/loading
  - Elementor editor rendering
  - responsive control output
  - button link output
  - background image rendering
  - future-safe mode switch surface (`Static` now, `Slide` later)

## 3) Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
  - `docs/30-features/elementor-widgets/architecture-direction.md`
  - `docs/30-features/elementor-widgets/mosaic-slider-widget.md`
  - `docs/30-features/elementor-widgets/showcase-slide-widget.md`
- Integration docs to read:
  - none required for V1
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
  - `includes/widgets/class-bw-button-widget.php`
  - `includes/widgets/class-bw-go-to-app-widget.php`
  - `includes/widgets/class-bw-animated-banner-widget.php`
  - `includes/widgets/class-bw-mosaic-slider-widget.php`
  - `includes/widgets/class-bw-showcase-slide-widget.php`
  - `includes/helpers.php`

## 4) Scope Declaration
- Proposed strategy:
  - create a new widget `bw-hero-slide`
  - keep V1 non-slider and implement only `Static` mode rendering
  - still expose a `Mode` control with `Static` / `Slide` so the data surface and class structure are future-ready
  - use server-rendered PHP output with widget-local CSS
  - keep JS absent or minimal unless required for editor/runtime behavior
  - use Elementor-native responsive controls with direct selectors where possible
  - use `Controls_Manager::URL` as the base CTA link surface
  - add a small repository-aligned link resolver only if category/archive support cannot be expressed cleanly through URL controls alone
- Files likely impacted:
  - `includes/widgets/class-bw-hero-slide-widget.php`
  - `assets/css/bw-hero-slide.css`
  - `assets/js/bw-hero-slide.js` (only if needed)
  - `blackwork-core-plugin.php`
  - `docs/30-features/elementor-widgets/hero-slide-widget.md`
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
  - `docs/50-ops/regression-protocol.md`
  - `docs/tasks/BW-TASK-20260325-01-closure.md`
- Explicitly out-of-scope surfaces:
  - live slider runtime for `Slide` mode
  - autoplay / arrows / dots / Embla integration
  - query-driven hero content rotation
  - popup behavior
  - generalized repository-wide link-builder framework
- Risk analysis:
  - if CTA link flexibility is overdesigned, V1 can drift into a generic navigation system
  - if slide mode scaffolding is overbuilt now, the widget will carry dead complexity
  - if glass effect is implemented too aggressively, performance and readability can regress
- ADR evaluation (REQUIRED / NOT REQUIRED): NOT REQUIRED

## 5) Runtime Surface Declaration
- New hooks expected:
  - none beyond asset registration hooks already used for widgets
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
  - link target flexibility may require small helper logic for term/archive resolution
  - asset registration must stay aligned with existing widget bootstrap patterns

## Governance Impact Analysis
- Authority surfaces touched:
  - Elementor widget runtime
  - widget asset registration
  - feature and regression documentation
- Data integrity risk:
  - low; widget is presentational and does not mutate stored data beyond Elementor settings
- Security surface changes:
  - output escaping for title/subtitle/button text and resolved links is required
- Runtime hook/order changes:
  - one new widget asset registration surface in `blackwork-core-plugin.php`
- Requires ADR? No
- Risk register impact required? No
- Risk dashboard impact required? No

## System Invariants Check
- Declared invariants that MUST remain true:
  - widget loader remains file-discovery based
  - asset registration authority remains centralized
  - V1 does not invent a slider runtime when `Slide` mode is not implemented
  - responsive controls remain Elementor-native and selector-driven where practical
  - no global/shared button authority is duplicated unnecessarily
- Any invariant at risk? No
- Mitigation plan for invariant protection:
  - keep runtime server-rendered and static
  - use widget-local CSS tokens instead of broad shared abstractions
  - keep `Slide` mode as control/state scaffolding only

## Determinism Statement
- Input/output determinism declared? Yes
- Ordering determinism declared? Yes
- Retry determinism declared? Yes
- Pagination/state convergence determinism declared? Yes
- Determinism risks and controls:
  - button repeater order must render exactly as authored
  - responsive selectors must map consistently to Elementor control values
  - unresolved future `Slide` mode must fail closed to `Static` behavior in V1

## Testing Strategy
- Local testing plan:
  - verify widget registers and renders in Elementor
  - verify `Mode` selector defaults to and renders `Static`
  - verify title/subtitle/buttons/background image render correctly
  - verify responsive typography, hero height, padding, and button wrapping
  - verify link output is correct for supported CTA target types
- Edge cases expected:
  - empty subtitle
  - no buttons
  - long button labels that wrap
  - very tall vs shallow hero heights
  - missing background image
- Failure scenarios considered:
  - slide mode selected while unimplemented
  - invalid archive/category target configuration
  - visual imbalance on mobile/tablet

## Documentation Update Plan
- `docs/00-governance/`
  - Impacted? No
  - Target documents (if known):
    - none
- `docs/00-planning/`
  - Impacted? No
  - Target documents (if known):
    - none
- `docs/10-architecture/`
  - Impacted? Possibly no
  - Target documents (if known):
    - none unless family mapping needs explicit mention
- `docs/20-development/`
  - Impacted? No
  - Target documents (if known):
    - none
- `docs/30-features/`
  - Impacted? Yes
  - Target documents (if known):
    - `docs/30-features/elementor-widgets/hero-slide-widget.md`
    - `docs/30-features/elementor-widgets/README.md`
    - `docs/30-features/elementor-widgets/widget-inventory.md`
- `docs/40-integrations/`
  - Impacted? No
  - Target documents (if known):
    - none
- `docs/50-ops/`
  - Impacted? Yes
  - Target documents (if known):
    - `docs/50-ops/regression-protocol.md`
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
  - remove widget file and asset registration if rollback is needed before release

## 6A) Documentation Alignment Requirement
- `docs/00-governance/`
  - Impacted? No
  - Target documents (if known):
    - none
- `docs/00-planning/`
  - Impacted? No
  - Target documents (if known):
    - none
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
    - hero widget feature doc + widget indexes
- `docs/40-integrations/`
  - Impacted? No
  - Target documents (if known):
    - none
- `docs/50-ops/`
  - Impacted? Yes
  - Target documents (if known):
    - regression protocol
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
- Release Gate reference acknowledged:
  - `docs/50-ops/release-gate.md`
- Planning must preserve:
  - rollback safety
  - deterministic output
  - documentation alignment

## Abort Conditions
- Scope drift detected
- a shared reusable CTA/link authority is discovered and implementation would conflict with it
- future `Slide` mode scaffolding starts forcing V1 runtime complexity
- responsive behavior cannot be kept balanced across desktop/tablet/mobile
