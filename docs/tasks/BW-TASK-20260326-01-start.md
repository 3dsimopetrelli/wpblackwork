# Blackwork Governance -- Task Start

## 1) Context
- Task ID: `BW-TASK-20260326-01`
- Task title: Showcase Slide CTA link-button typography control
- Request source: User request on 2026-03-26
- Expected outcome:
  - inspect the current `Showcase Slide` style-control surface and CTA runtime structure
  - identify the exact authority for the green CTA pill text button
  - add a new Style dropdown/section dedicated to the link button
  - expose responsive Elementor typography controls for the green CTA text button only
  - keep the intervention minimal so the user can reduce CTA size through typography before deciding on further button refinements
- Constraints:
  - no conflict with the existing `Text`, `Images`, `Navigation Arrows`, `Dots (Pagination)`, and `Custom Cursor` sections
  - typography must use Elementor responsive controls
  - the detached circular arrow button must remain a separate authority unless explicitly requested later
  - no regression to breakpoint repeater logic, card ratio logic, or CTA mobile tap contract

## 2) Task Classification
- Domain: Elementor Widgets / Showcase Slide / CTA Style Controls
- Incident/Task type: Governed analysis + pending style refinement
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `includes/widgets/class-bw-showcase-slide-widget.php`
  - `assets/css/bw-showcase-slide.css`
  - Showcase Slide widget documentation
- Integration impact: Low to Medium
- Regression scope required:
  - Elementor Style panel section ordering
  - CTA pill typography on desktop/tablet/mobile
  - separation between CTA pill typography and detached arrow button styling
  - documentation alignment for the Showcase Slide style surface

## 3) Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/elementor-widgets/showcase-slide-widget.md`
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
- Code references to read:
  - `includes/widgets/class-bw-showcase-slide-widget.php`
  - `assets/css/bw-showcase-slide.css`
  - `assets/js/bw-showcase-slide.js`
- Governance docs to read:
  - `docs/templates/task-start-template.md`
  - `docs/templates/task-closure-template.md`
  - `docs/governance/task-close.md`

## 4) Scope Declaration
- Proposed strategy:
  - keep CTA structure unchanged:
    - `.bw-showcase-slide-button` remains the green text pill
    - `.bw-showcase-slide-arrow` remains the detached circular arrow control
  - add a new Style section dedicated to the CTA text button instead of overloading the generic Text section
  - expose `Group_Control_Typography` on `.bw-showcase-slide-button` so Elementor responsive device tabs control the CTA text size per breakpoint family
  - avoid adding padding/size/color controls in this wave unless required after typography validation
- Files likely impacted in the later implementation phase:
  - `includes/widgets/class-bw-showcase-slide-widget.php`
  - `docs/30-features/elementor-widgets/showcase-slide-widget.md`
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
  - `docs/tasks/BW-TASK-20260326-01-closure.md`
- Explicitly out-of-scope surfaces for this analysis phase:
  - CTA arrow size/styling changes
  - CTA padding/height changes
  - new CTA layout modes
  - changes to the slide breakpoint repeater

## 5) Analysis Notes
- Current `Showcase Slide` style sections are:
  - `Text`
  - `Images`
  - `Navigation Arrows`
  - `Dots (Pagination)`
  - `Custom Cursor`
- Current CTA pill runtime authority:
  - markup: `.bw-showcase-slide-button`
  - CSS defaults in `assets/css/bw-showcase-slide.css`:
    - `min-height: 52px`
    - `padding: 0 24px`
    - `font-size: 20px`
    - `font-weight: 500`
    - `line-height: 1`
- The detached circular CTA arrow is a separate element:
  - markup: `.bw-showcase-slide-arrow`
- This separation means a dedicated CTA typography section can be added cleanly without mutating the arrow button.

## 6) Testing Strategy
- Verify the new control appears as a separate Style dropdown/section.
- Verify Elementor responsive device tabs change CTA typography on desktop/tablet/mobile.
- Verify the arrow button remains visually unaffected.
- Verify mobile CTA card-tap behavior remains unchanged.
