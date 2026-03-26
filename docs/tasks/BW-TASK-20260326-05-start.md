# Blackwork Governance -- Task Start

## 1) Context
- Task ID: `BW-TASK-20260326-05`
- Task title: Price Variation trust-column evolution
- Request source: User request on 2026-03-26
- Expected outcome:
  - extend `BW-SP Price Variation` with governed trust/support surfaces below the existing price/license/Add to Cart box
  - preserve the current pricing/license authority while allowing additional trust-oriented information in the same column
  - keep global review-marketing copy in Reviews Settings and keep per-instance support cards in widget-local controls
  - reuse the existing Embla core already present in the repository
- Constraints:
  - `bw-price-variation` must remain the pricing/license authority widget
  - compact reviews summary must remain a subordinate trust surface, not a second reviews authority
  - the license accordion must remain a disclosure surface for the active variation, not a second configurator
  - any new trust content added to the column must stay synchronized with the widget's single active variation state

## 2) Task Classification
- Domain: Elementor Widgets / Price Variation / Product Trust UX
- Incident/Task type: Governed analysis + pending feature refinement
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `includes/widgets/class-bw-price-variation-widget.php`
  - `assets/js/bw-price-variation.js`
  - `assets/css/bw-price-variation.css`
  - `includes/modules/reviews/services/class-bw-reviews-settings.php`
  - `includes/modules/reviews/admin/class-bw-reviews-admin.php`
  - `blackwork-core-plugin.php`
  - Price Variation widget documentation
- Integration impact: Medium
- Regression scope required:
  - active variation state synchronization
  - reviews trust summary rendering
  - review slider / fixed review box render gating
  - license disclosure accordion behavior
  - Add to Cart state and checkout-shortcut state
  - Embla dependency/init stability
  - Reviews Settings Trust Content save behavior
  - Elementor content/style control coherence

## 3) Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/elementor-widgets/price-variation-widget.md`
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
- Architecture/governance docs to read:
  - `docs/10-architecture/elementor-widget-architecture-context.md`
  - `docs/50-ops/regression-protocol.md`
  - `docs/templates/task-start-template.md`
  - `docs/templates/task-closure-template.md`
- Code references to read:
  - `includes/widgets/class-bw-price-variation-widget.php`
  - `assets/js/bw-price-variation.js`
  - `assets/css/bw-price-variation.css`
  - `metabox/variation-license-html-field.php`

## 4) Scope Declaration
- Proposed strategy:
  - treat the current widget column as a governed composite surface:
    - pricing
    - compact trust summary
    - variation selector
    - license disclosure
    - CTA / checkout shortcut
    - global review slider
    - global fixed review trust box
    - widget-level digital product info cards
    - widget-level FAQ CTA
  - keep global trust/review marketing copy under `Blackwork Site -> Reviews Settings -> Trust Content`
  - keep per-instance informational support items in widget controls
  - accept additional trust-oriented information only if it remains subordinate to the active variation state and does not introduce new authority drift
- Files likely impacted in the later implementation phase:
  - `includes/widgets/class-bw-price-variation-widget.php`
  - `assets/js/bw-price-variation.js`
  - `assets/css/bw-price-variation.css`
  - `includes/modules/reviews/services/class-bw-reviews-settings.php`
  - `includes/modules/reviews/admin/class-bw-reviews-admin.php`
  - `blackwork-core-plugin.php`
  - `docs/30-features/elementor-widgets/price-variation-widget.md`
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
  - `docs/30-features/reviews/reviews-system-guide.md`
  - `docs/10-architecture/elementor-widget-architecture-context.md`
  - `docs/50-ops/regression-protocol.md`
  - `docs/tasks/BW-TASK-20260326-05-closure.md`
- Explicitly out-of-scope until the next prompt clarifies otherwise:
  - multi-axis variation-model redesign
  - WooCommerce variation data model changes
  - unrelated checkout/payment architecture changes
  - making `bw-price-variation` a live review browser

## 5) Current Readiness
- Documentation status:
  - current docs/code alignment for `Price Variation` was refreshed before this implementation wave
  - this task now locks the new trust-stack implementation scope
- Ready state:
  - OPEN
  - IMPLEMENTATION IN PROGRESS
