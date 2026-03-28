# Blackwork Governance -- Task Start

## 1) Context
- Task ID: `BW-TASK-20260328-01`
- Task title: Extract Price Variation trust stack into new `BW Trust Box` widget
- Request source: User request on 2026-03-28
- Expected outcome:
  - inspect the current trust-stack implementation inside `BW-SP Price Variation`
  - create a new standalone Elementor widget dedicated to the lower trust content currently rendered under the pricing box
  - remove trust-stack controls and rendering from `BW-SP Price Variation`
  - preserve the current global Reviews Settings authority for slider content and fixed review-box content
  - move widget-level controls for digital product info and FAQ CTA into the new widget
- Constraints:
  - this is a controlled extraction from an existing widget, not a redesign from scratch
  - the new widget must reuse existing repository patterns for Elementor widgets, Embla runtime, and admin/global settings
  - global review slider / fixed review box content must remain sourced from `Blackwork Site -> Reviews Settings -> Trust Content`
  - avoid duplicate trust-stack systems or shadow settings

## 2) Task Classification
- Domain: Elementor Widgets / Price Variation / Reviews Settings / Trust UI
- Incident/Task type: Governed analysis + extraction + new widget implementation
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `includes/widgets/class-bw-price-variation-widget.php`
  - new widget file under `includes/widgets/`
  - `assets/js/bw-price-variation.js` or extracted shared trust runtime if required
  - `assets/css/bw-price-variation.css` or extracted shared trust styles if required
  - Reviews Settings trust-content authority surfaces
  - widget documentation / inventory docs
- Integration impact: Medium
- Regression scope required:
  - single product pages using existing `Price Variation`
  - new standalone trust widget rendering
  - global review slider / fixed review box toggles
  - widget-level digital info and FAQ controls
  - Embla trust slider runtime initialization

## 3) Pre-Task Reading Checklist
- Governance docs to read:
  - `docs/00-governance/ai-task-protocol.md`
  - `docs/templates/task-start-template.md`
  - `docs/templates/task-closure-template.md`
- Feature / runtime docs to read:
  - `docs/30-features/elementor-widgets/price-variation-widget.md`
  - `docs/30-features/reviews/reviews-system-guide.md`
  - widget inventory / widget README docs impacted by the new widget
- Code references to read:
  - `includes/widgets/class-bw-price-variation-widget.php`
  - `assets/js/bw-price-variation.js`
  - `assets/css/bw-price-variation.css`
  - `includes/modules/reviews/services/class-bw-reviews-settings.php`
  - `includes/modules/reviews/admin/class-bw-reviews-admin.php`
  - `includes/class-bw-widget-loader.php`

## 4) Scope Declaration
- Proposed strategy:
  - introduce a new widget tentatively named `BW Trust Box`
  - move the trust-stack surface out of `BW-SP Price Variation`:
    - global review slider
    - global fixed review box
    - widget-level digital product info
    - widget-level FAQ button
  - keep product-review summary under the main price inside `Price Variation` unchanged
  - keep Reviews Settings as the global authority for slider rows and fixed review-box content
  - decide whether trust slider/styles remain in `bw-price-variation.*` temporarily or are extracted into shared trust-box assets
- Files likely impacted in the implementation phase:
  - `includes/widgets/class-bw-price-variation-widget.php`
  - new widget file under `includes/widgets/`
  - one or more JS/CSS assets for trust stack rendering
  - widget docs and task closure docs
- Explicitly out-of-scope unless later analysis proves otherwise:
  - changing Reviews Settings content model
  - changing review submission/list widget behavior
  - changing core pricing/license behavior in `Price Variation`
  - changing cart/checkout logic

## 5) Runtime Surface Declaration
- New hooks expected:
  - none beyond normal Elementor widget auto-loading, unless shared asset registration must be adjusted
- Hook priority modifications:
  - none expected initially
- Filters expected:
  - none
- AJAX endpoints expected:
  - none
- Admin routes expected:
  - no new admin page; trust-content global settings remain in Reviews Settings

## 6) Risk Analysis
- Main architectural risk:
  - copying trust logic instead of extracting/reusing it would create two trust-stack implementations that drift quickly
- Asset risk:
  - the trust slider runtime currently lives in `bw-price-variation.js`; moving only markup without runtime extraction would leave the new widget coupled to a price-variation asset
- UI/UX risk:
  - removing controls from `Price Variation` without a migration-friendly replacement could surprise existing templates
- Backward-compatibility risk:
  - existing pages that rely on trust blocks inside `Price Variation` may visually lose them after migration if no transitional path is defined

## 7) System Invariants
- Declared invariants that must remain true:
  - `Price Variation` remains authority for price / variation / license / add-to-cart
  - global review slider and fixed review box remain governed by Reviews Settings trust content
  - the new widget is the only authority for trust-stack composition after extraction
  - no duplicate Embla review-slider system should exist
- Any invariant at risk?:
  - Yes; asset ownership and backward compatibility need explicit handling
- Mitigation plan:
  - inspect current asset and helper coupling before coding
  - choose a single runtime/style authority for trust-stack rendering
  - document migration behavior clearly

## 8) Determinism Statement
- Input/output determinism declared?: Yes
- Ordering determinism declared?: Yes
- Retry determinism declared?: Yes
- Pagination/state convergence determinism declared?: Yes
- Determinism risks and controls:
  - new widget settings must sanitize deterministically like existing Elementor widgets
  - global trust content must still resolve from one canonical Reviews Settings payload
  - any shared runtime initialization must be idempotent across Elementor renders

## 9) Testing Strategy
- Local testing plan:
  - verify existing `Price Variation` without trust stack still renders correctly
  - verify new `BW Trust Box` renders slider, review box, info cards, and FAQ CTA correctly
  - verify global Reviews Settings toggles still gate slider and fixed review box
  - verify widget-level toggles gate info cards and FAQ
  - verify Embla slider initializes once per widget instance
- Edge cases expected:
  - widget placed on pages without product context
  - slider disabled globally but enabled locally
  - FAQ enabled without link in editor vs frontend
  - multiple trust widgets on the same page
- Failure scenarios considered:
  - stale trust markup still rendered by `Price Variation`
  - missing runtime because the new widget does not enqueue the needed asset
  - duplicated CSS classes with conflicting ownership

## 10) Documentation Update Plan
- `docs/10-architecture/`
  - Impacted?: Yes
  - Target documents:
    - widget architecture context docs if authority boundaries change
- `docs/30-features/`
  - Impacted?: Yes
  - Target documents:
    - `docs/30-features/elementor-widgets/price-variation-widget.md`
    - widget inventory / widget README
    - trust/reviews docs if trust-content ownership wording changes
- `docs/50-ops/`
  - Impacted?: Possible
  - Target documents:
    - regression protocol if widget coverage changes

## 11) Current Readiness
- Status: OPEN
- Analysis status:
  - initial extraction framing completed
  - implementation-ready plan still requires one explicit decision:
    - whether trust markup/runtime/styles are fully extracted into dedicated shared/new assets
    - or temporarily remain price-variation-owned while the new widget consumes them
- Immediate recommendation:
  - continue with detailed code audit focused on:
    - trust-stack helper ownership
    - asset dependency extraction path
    - backward-compatibility strategy for existing `Price Variation` instances
