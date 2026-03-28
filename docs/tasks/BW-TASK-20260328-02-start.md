# Blackwork Governance -- Task Start

## 1) Context
- Task ID: `BW-TASK-20260328-02`
- Task title: Align Product Details title contract to `Collection Content`
- Request source: User request on 2026-03-28
- Expected outcome:
  - make `Collection Content` the default visible title for the `Product Details` content branch
  - remove the duplicated `Collection content` subtitle currently rendered above the assets hero row
  - keep the existing `BW-SP Product Details` widget architecture unchanged apart from this title-contract correction
- Constraints:
  - do not create a new widget
  - do not redesign the row/table structure
  - preserve current compatibility and info-box branches

## 2) Task Classification
- Domain: Elementor Widgets / Product Details
- Incident/Task type: Governed widget contract correction
- Risk level (L1/L2/L3): L1
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `includes/widgets/class-bw-product-details-widget.php`
  - product-details widget documentation
- Integration impact: Low

## 3) Scope Declaration
- Proposed strategy:
  - change the `table_title` default/placeholder for `product_details` to `Collection Content`
  - change the frontend fallback title for the `product_details` branch to `Collection Content`
  - remove the hardcoded digital-section subtitle so the heading is not duplicated
- Explicitly out-of-scope:
  - any metabox data-model change
  - any CSS redesign
  - compatibility or info-box behavior changes

## 4) Risk Analysis
- Main risk:
  - low-risk wording change only; the main concern is avoiding regression in the `Compatibility` and `Info Box` branches
- Mitigation:
  - keep the change isolated to the `product_details` title/fallback path and digital section subtitle

## 5) Testing Strategy
- Local testing plan:
  - lint the modified PHP file
  - run project PHP lint command required by governance
  - verify documentation reflects the new default title behavior

## 6) Current Readiness
- Status: OPEN
- Analysis status:
  - implementation-ready
