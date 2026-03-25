# Blackwork Governance -- Task Start

## 1) Context
- Task ID: `BW-TASK-20260324-01`
- Task title: Governed architecture and implementation plan for `Mosaic Slider` Elementor widget
- Request source: User request on 2026-03-24
- Expected outcome:
  - inspect the current Embla-based slider architecture before implementation
  - inspect the current canonical `bw-product-slider` runtime and documentation
  - inspect the reusable product card/component authority already used in the repository
  - define the cleanest architecture for a new `Mosaic Slider` Elementor widget
  - preserve governed documentation alignment from task start through implementation and closure
- Constraints:
  - do not reinvent slider architecture if the repository already provides a reusable Embla pattern
  - when queried content is `product`, rendering must reuse the existing shared product component/card authority
  - the new widget must support three desktop layout variants:
    - `Big post center`
    - `Big post left`
    - `Big post right`
  - below `1000px`, the asymmetric mosaic layout must collapse to a normal draggable slider with uniform card sizing
  - first deliverable is a technical proposal; implementation must not begin if authority or architecture is unclear

## 2) Task Classification
- Domain: Elementor Widgets / Slider Architecture / Editorial Query Surfaces
- Incident/Task type: Governed feature design + planned implementation
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - Elementor widget runtime
  - shared Embla runtime pattern
  - shared product-card component authority
  - feature/architecture/governance documentation layers
- Integration impact: Medium
- Regression scope required:
  - widget registration/loading
  - query determinism across post types and manual IDs
  - breakpoint/layout switching between desktop mosaic and mobile slider
  - product-card delegation when `post_type = product`
  - Elementor frontend/editor re-init stability

## 3) Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
  - `docs/30-features/product-slide/README.md`
  - `docs/30-features/elementor-widgets/rationalization-policy.md`
- Architecture docs to read:
  - `docs/10-architecture/elementor-widget-architecture-context.md`
  - `docs/30-features/elementor-widgets/architecture-direction.md`
- Ops/control docs to read:
  - `docs/50-ops/maintenance-workflow.md`
  - `docs/50-ops/regression-protocol.md`
- Governance docs to read:
  - `docs/00-governance/ai-task-protocol.md`
  - `docs/templates/task-start-template.md`
  - `docs/templates/task-closure-template.md`
  - `docs/governance/task-close.md`
- Code references to read:
  - `includes/widgets/class-bw-product-slider-widget.php`
  - `assets/js/bw-product-slider.js`
  - `assets/js/bw-embla-core.js`
  - `assets/css/bw-embla-core.css`
  - `includes/components/product-card/class-bw-product-card-component.php`
  - `includes/widgets/class-bw-showcase-slide-widget.php`
  - `includes/widgets/class-bw-product-grid-widget.php`

## 4) Scope Declaration
- Proposed widget identity:
  - working name: `Mosaic Slider`
  - proposed runtime slug: `bw-mosaic-slider`
  - proposed class name: `BW_Mosaic_Slider_Widget`
- Proposed strategy:
  - create a new widget instead of mutating `bw-product-slider`
  - keep the current shared Embla pattern:
    - PHP server-rendered query + wrapper markup
    - widget-local JS adapter initialized on top of `BWEmblaCore`
    - widget-local CSS skin/layout rules
  - use a desktop-only asymmetric mosaic layout driven primarily by CSS classes/variables
  - switch below `1000px` to a standard single-row Embla slider with equalized slide sizing
  - reuse `BW_Product_Card_Component::render()` for product results
  - provide a lightweight non-product editorial card renderer for posts or other queried content
- Query contract to evaluate/implement:
  - post type/source selection
  - category selection
  - sub-category selection
  - manual IDs override
  - item count
  - order / order by
  - randomize on/off
- Explicitly out of scope for the proposal phase:
  - immediate code implementation before architectural sign-off
  - popup/modal behavior
  - duplicate product card system
  - rewriting `BWEmblaCore`
  - cross-widget slider-core extraction wave beyond what is required for this widget

## 5) Runtime Surface Declaration
- New widget class expected: Yes
- New script/style handles likely expected: Yes
- Shared runtime reuse expected: Yes, via `BWEmblaCore`
- Shared component reuse expected: Yes, via `BW_Product_Card_Component`
- New hooks expected: No custom runtime hooks beyond normal Elementor widget loading
- New AJAX endpoints expected: No
- New metabox fields expected: No

## 6) System Invariants
- `bw-product-slider` remains the canonical current product slider authority.
- `BWEmblaCore` remains the shared Embla engine rather than being bypassed.
- Product rendering authority remains `BW_Product_Card_Component`.
- Query ordering must remain deterministic whenever random mode is off.
- Manual IDs must remain an explicit override surface if provided.
- Mobile behavior must remain a real draggable Embla slider, not a CSS-only fake carousel.

## 7) Risks
- The desktop mosaic composition is structurally different from the repository's current equal-width slider pattern and can drift into a custom layout engine if over-designed.
- Mixing `product` and non-product content paths can create duplicate card/rendering logic unless the split is kept explicit.
- Randomized ordering can conflict with caching and editor preview expectations if not declared early.
- A desktop mosaic that spans two visual rows must still degrade cleanly to one-dimensional Embla scrolling on mobile.
- If the desktop layout depends too heavily on JS measurements, Elementor re-render stability risk increases.

## 8) Testing Strategy
- Verify the architecture proposal against current runtime authority before implementation.
- On implementation, verify widget registration and editor visibility.
- Verify product queries render through `BW_Product_Card_Component`.
- Verify non-product queries render safely through the widget-local editorial card renderer.
- Verify desktop mosaic variants render correctly for center/left/right featured placement.
- Verify `<1000px` layout switches to uniform draggable slider behavior.
- Verify manual IDs ordering remains exact when randomize is off.
- Verify randomize mode behaves predictably and does not reuse deterministic cache keys incorrectly.
- Verify Elementor editor re-render destroys/rebuilds instances cleanly.

## 9) Documentation Update Plan
- Create a dedicated feature doc for `Mosaic Slider` once implementation scope is approved.
- Update widget inventory when the widget becomes real runtime surface.
- Update architecture docs if the slider-family map changes.
- Update regression protocol if new widget-specific regression checks are required.
- Update changelog when implementation begins landing.

## 10) Current Status
- Status: OPEN
- Implementation: NOT STARTED
- Current phase: ARCHITECTURE REVIEW / TECHNICAL PROPOSAL
