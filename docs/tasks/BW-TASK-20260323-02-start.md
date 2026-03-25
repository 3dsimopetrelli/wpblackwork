# Blackwork Governance -- Task Start

## 1) Context
- Task ID: `BW-TASK-20260323-02`
- Task title: Governed build plan for `Showcase Slide` Elementor widget
- Request source: User request on 2026-03-23
- Expected outcome:
  - define and then implement a new Embla-based `Showcase Slide` widget
  - source slide content from the existing showcase metabox fields on products/posts
  - reuse proven slider controls from `BW Presentation Slide` where appropriate
  - keep the documentation updated in parallel with the widget build
- Constraints:
  - no popup settings in the new widget
  - `Texts color` from the metabox is the single authority for in-slide text/badge color
  - CTA must replicate the approved split-pill design (text pill + detached green arrow circle)
  - content must support manual ID-driven slide selection
  - query/slider architecture must stay coherent with current Embla direction in the repo

## 2) Task Classification
- Domain: Elementor Widgets / Showcase UX / Product & Post Presentation
- Incident/Task type: Governed feature implementation
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - Elementor widget runtime
  - showcase/product metabox data model
  - shared Embla slider pattern
  - feature documentation for widget inventory and architecture
- Integration impact: Medium
- Regression scope required:
  - widget registration/loading
  - Embla initialization and breakpoint behavior
  - metabox field consumption for product/post showcase content
  - responsive rendering on desktop/tablet/mobile

## 3) Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
  - `docs/30-features/presentation-slide/README.md`
- Architecture docs to read:
  - `docs/10-architecture/elementor-widget-architecture-context.md`
  - `docs/30-features/elementor-widgets/architecture-direction.md`
- Code references to read:
  - `includes/widgets/class-bw-presentation-slide-widget.php`
  - `assets/js/bw-presentation-slide.js`
  - `includes/widgets/class-bw-product-slider-widget.php`
  - `assets/js/bw-product-slider.js`
  - `includes/widgets/class-bw-static-showcase-widget.php`
- Governance docs to follow:
  - `docs/templates/task-start-template.md`
  - `docs/templates/task-closure-template.md`
  - `docs/governance/task-close.md`

## 4) Scope Declaration
- Proposed widget identity:
  - working name: `Showcase Slide`
  - proposed runtime slug: `bw-showcase-slide`
  - proposed class name: `BW_Showcase_Slide_Widget`
- Proposed strategy:
  - create a new slider widget rather than mutating `bw-presentation-slide` or `bw-static-showcase`
  - use Embla-based slider runtime patterns already proven in `bw-presentation-slide` / `bw-product-slider`
  - source slide body content from showcase metabox fields
  - allow manual slide composition by IDs
  - mirror `Presentation Slide` slider settings and responsive breakpoint controls
  - exclude popup settings entirely
- Content contract to implement:
  - `Showcase Title`
  - `Showcase Description`
  - digital-data labels with `1px` border
  - CTA button using approved split-pill green design
  - optional custom cursor, same on/off concept as `Presentation Slide`
- Color contract:
  - metabox `Texts color` is the only authority for title, description, data labels, and CTA text color where applicable
  - no automatic contrast detection is allowed
- Query contract:
  - manual IDs input is required
  - title resolution should still surface the real linked post/product title when needed
- Explicitly out of scope:
  - popup/modal gallery
  - automatic contrast detection
  - generic wall/grid behavior
  - reviews integration

## 5) Runtime Surface Declaration
- New widget class expected: Yes
- New script/style handles likely expected: Yes
- Shared runtime reuse expected: Yes, via Embla pattern already used in current slider family
- New metabox fields expected: No, current showcase metabox should be reused
- New AJAX endpoints expected: No

## 6) System Invariants
- The showcase metabox remains the content authority.
- `Texts color` remains editor-controlled and deterministic.
- The new widget must not reintroduce Slick-specific runtime assumptions.
- `Presentation Slide` must remain stable and unmodified beyond any safe shared extraction if required.
- Responsive behavior must remain deterministic through breakpoint controls rather than ad-hoc CSS hacks.

## 7) Risks
- `bw-static-showcase`, `bw-slide-showcase`, and the planned new widget overlap conceptually; naming and scope must stay explicit.
- `Presentation Slide` has a larger runtime surface; copying it blindly would create unnecessary complexity.
- ID-driven composition can drift if manual-content fallback and linked-post title behavior are not defined early.
- CTA design fidelity is high-importance and easy to under-specify.

## 8) Testing Strategy
- Verify widget registration and editor visibility.
- Verify manual ID-driven slide composition works for posts/products.
- Verify metabox content is rendered consistently.
- Verify `Texts color` propagates to all declared text surfaces.
- Verify CTA split-pill structure renders correctly.
- Verify slider settings and responsive breakpoints behave consistently with the approved family pattern.
- Verify no popup settings/runtime are present.

## 9) Documentation Update Plan
- Create a dedicated widget spec doc for `Showcase Slide`.
- Update widget inventory with a planned-governed addition note.
- Update Elementor widget architecture docs to record the new family direction.
- Close the task with a governed closure artifact once implementation and validation are complete.

## 10) Current Status
- Status: OPEN
- Implementation: NOT STARTED
- Documentation baseline for the new widget: IN PROGRESS
