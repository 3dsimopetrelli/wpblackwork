# Blackwork Governance -- Task Start

## 1) Context
- Task ID: `BW-TASK-20260325-06`
- Task title: Governed Mosaic Slider image-loading hardening analysis
- Request source: User request on 2026-03-25
- Expected outcome:
  - inspect the current `Mosaic Slider` runtime/documentation before changes
  - inspect the existing loading-policy contract already implemented in `BW Product Grid`
  - define the cleanest way to bring an equally efficient image-loading strategy into `Mosaic Slider`
  - pay special attention to first paint, hidden desktop/mobile duplicate markup, eager-vs-lazy policy, hover image loading, and runtime reveal timing
  - keep the intervention aligned with repository governance before implementation starts
- Constraints:
  - image loading must be fast and efficient, not just “lazy by default”
  - do not introduce a second product-card loading authority when `BW_Product_Card_Component` already owns part of the product image contract
  - preserve the current desktop mosaic / mobile Embla architecture unless a change is clearly justified
  - desktop and mobile viewports are both rendered server-side, so loading policy must explicitly account for hidden markup

## 2) Task Classification
- Domain: Elementor Widgets / Mosaic Slider / Image Loading / Frontend Performance
- Incident/Task type: Governed analysis + pending runtime hardening
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `BW_Mosaic_Slider_Widget` PHP render pipeline
  - widget-local Mosaic Slider JS startup/reveal behavior
  - shared `BW_Product_Card_Component` image loading contract
  - Mosaic Slider documentation
- Integration impact: Medium
- Regression scope required:
  - first paint and initial reveal
  - desktop hidden/mobile hidden markup loading behavior
  - product-card image loading consistency
  - editorial card image loading consistency
  - Elementor re-render lifecycle

## 3) Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/elementor-widgets/mosaic-slider-widget.md`
  - `docs/30-features/product-grid/README.md`
  - `docs/30-features/product-grid/product-grid-architecture.md`
  - `docs/30-features/product-grid/fixes/2026-03-14-product-grid-hardening-report.md`
  - `docs/30-features/elementor-widgets/README.md`
- Code references to read:
  - `includes/widgets/class-bw-mosaic-slider-widget.php`
  - `assets/js/bw-mosaic-slider.js`
  - `assets/css/bw-mosaic-slider.css`
  - `includes/widgets/class-bw-product-grid-widget.php`
  - `includes/components/product-card/class-bw-product-card-component.php`
- Governance docs to read:
  - `docs/templates/task-start-template.md`
  - `docs/templates/task-closure-template.md`
  - `docs/governance/task-close.md`

## 4) Scope Declaration
- Proposed strategy:
  - treat `BW Product Grid` as the reference implementation for explicit loading policy
  - map the full loading path in `Mosaic Slider`:
    - PHP `loading` / `fetchpriority` assignment
    - product vs editorial render branch
    - hidden desktop/mobile duplicate markup
    - JS wrapper `.loading` reveal timing
  - identify where the current contract is insufficient or wasteful
  - define the minimal clean hardening required before implementation
- Files likely impacted in the later implementation phase:
  - `includes/widgets/class-bw-mosaic-slider-widget.php`
  - `assets/js/bw-mosaic-slider.js`
  - `assets/css/bw-mosaic-slider.css`
  - `docs/30-features/elementor-widgets/mosaic-slider-widget.md`
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/tasks/BW-TASK-20260325-06-closure.md`
- Explicitly out-of-scope surfaces for this analysis phase:
  - unrelated Product Grid features
  - non-image Mosaic layout redesign
  - popup/modal features

## 5) Runtime Surface Declaration
- New hooks expected in analysis phase: none
- Hook priority modifications expected: none
- AJAX endpoints expected: none
- Admin routes expected: none

## 6) System Invariants
- `Mosaic Slider` remains the canonical asymmetric editorial/mixed-content slider for this layout family.
- `BW_Product_Card_Component` remains the shared authority for product image markup.
- Loading policy must remain deterministic for the same saved widget settings.
- Hidden duplicate markup must not silently consume high-priority image loading without an explicit reason.

## 7) Analysis Notes
- Current Product Grid loading contract already documents:
  - explicit `image_loading` and `hover_image_loading`
  - eager only for first-row main images
  - lazy for later initial items
  - lazy for hover images
- Current Mosaic Slider findings:
  - desktop layout and mobile layout are both rendered in the DOM
  - desktop first page items are marked `eager`
  - mobile first card is also marked `eager`
  - JS reveal currently removes `.loading` after double `requestAnimationFrame`, not after image-readiness checks
- These findings make Mosaic Slider a good candidate for a governed loading-policy hardening pass.

## 8) Testing Strategy
- Verify whether hidden desktop/mobile markup causes duplicate eager downloads.
- Verify first paint / reveal sequencing with slow image loads.
- Verify product-card and editorial-card branches behave consistently.
- Verify no editor/runtime regressions when the widget is re-rendered by Elementor.
