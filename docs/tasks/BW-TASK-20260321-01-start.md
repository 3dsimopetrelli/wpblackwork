# Blackwork Governance — Task Start Template

## Context
- Task title: Governed Hover Media upgrade for shared product-card component
- Request source: User request on 2026-03-21
- Expected outcome: Extend the shared product-card system so WooCommerce products can optionally use a hover video with video-first priority, falling back to the existing hover image when no video is configured.
- Constraints:
  - the implementation must be centered on the shared product-card authority, not duplicated per widget
  - a new product admin field must be added next to the existing hover image control
  - hover video must have higher priority than hover image
  - if no hover video is configured, existing hover image behavior must remain intact
  - mobile/touch behavior must fail safe and avoid broken hover expectations
  - the admin label must be clarified because `Product slider (Slick Slider)` is no longer semantically accurate once the box manages generic hover media

Normative rules:
- Context fields are REQUIRED.
- Implementation MUST NOT begin with missing context.

## Task Classification
- Domain: WooCommerce Product UX / Shared Components / Elementor Widgets / Product Admin
- Incident/Task type: Governed feature enhancement
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - shared product-card component
  - WooCommerce product edit metabox
  - product-grid and related-products card rendering
  - slick-slider product rendering
  - any future widget that reuses `BW_Product_Card_Component`
- Integration impact: Medium
- Regression scope required:
  - product-card hover image behavior
  - product-card overlay/link behavior
  - product admin metabox save flow
  - widget rendering in product-grid / related-products / slick-slider

Normative rules:
- Task classification is REQUIRED and BLOCKING.
- Tier 0 authority-impacting tasks MUST trigger governance escalation before implementation.
- Misclassified tasks MUST be corrected before implementation.

## Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
  - `docs/30-features/elementor-widgets/price-variation-widget.md`
- Integration docs to read:
  - `docs/10-architecture/elementor-widget-architecture-context.md`
- Ops/control docs to read:
  - `docs/50-ops/regression-protocol.md`
- Governance docs to read:
  - `docs/templates/task-start-template.md`
  - `docs/templates/task-closure-template.md`
  - `docs/governance/task-close.md`
- Runbook to follow:
  - Blackwork governed feature flow with documentation-first scope lock
- Architecture references to read:
  - `includes/components/product-card/class-bw-product-card-component.php`
  - `includes/product-types/class-bw-product-slider-metabox.php`
  - `includes/widgets/class-bw-product-grid-widget.php`
  - `includes/widgets/class-bw-related-products-widget.php`
  - `includes/widgets/class-bw-slick-slider-widget.php`
  - `assets/css/bw-product-card.css`
  - `assets/css/bw-wallpost.css`

Normative rules:
- Reading checklist completion is REQUIRED.
- Implementation MUST NOT begin without declaring required references.

## Scope Declaration
- Proposed strategy:
  - rename the existing product metabox conceptually from slider-specific wording to a generic hover-media meaning
  - extend the product metabox with a new hover-video media field stored as product meta
  - update the shared product-card component so it resolves hover media in this priority order:
    1. hover video
    2. hover image
    3. no hover media fallback
  - keep hover-media authority centralized in the shared component so all consuming widgets inherit the behavior without parallel implementations
  - keep video playback safe for storefront usage with muted/loop/playsinline semantics and conservative loading behavior
- Files likely impacted:
  - `docs/tasks/BW-TASK-20260321-01-start.md`
  - `includes/product-types/class-bw-product-slider-metabox.php`
  - `includes/components/product-card/class-bw-product-card-component.php`
  - `includes/widgets/class-bw-product-grid-widget.php`
  - `includes/widgets/class-bw-related-products-widget.php`
  - `includes/widgets/class-bw-slick-slider-widget.php`
  - `assets/css/bw-product-card.css`
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
  - feature/system docs for product card hover media
- Explicitly out-of-scope surfaces:
  - remote video hosting/CDN logic
  - adaptive streaming
  - autoplay video behavior on touch/mobile
  - review-system changes
  - Price Variation widget changes
- Risk analysis:
  - the product-card component is shared authority; regressions will fan out across multiple widgets
  - video hover can hurt performance if preload, resolution, or playback policy are too aggressive
  - hover UX is inherently weaker on touch devices and needs deterministic fallback
  - media metabox changes must preserve existing saved hover image data
  - admin wording change must remain understandable for editors already using the current box
- ADR evaluation (REQUIRED / NOT REQUIRED): NOT REQUIRED

Normative rules:
- Scope declaration is REQUIRED and BLOCKING.
- All target files MUST be declared before implementation.
- Out-of-scope boundaries MUST be explicit.

## Runtime Surface Declaration

Declare expected runtime surfaces affected.

- New hooks expected: none
- Hook priority modifications: none
- Filters expected: none
- AJAX endpoints expected: none
- Admin routes expected: none

Normative rule:
All expected runtime mutations MUST be declared before implementation.

## 3.1) Implementation Scope Lock

Confirm that the declared scope is complete.

- All files expected to change are listed? Yes
- Hidden coupling risks discovered? Yes

Normative rules:

Implementation MUST NOT modify files outside the declared scope.

If new surfaces are discovered during development, the task MUST pause and the scope MUST be updated.

## Governance Impact Analysis
- Authority surfaces touched:
  - shared product-card hover-media resolution
  - WooCommerce product admin metabox data entry
- Data integrity risk: Medium
- Security surface changes: Low
- Runtime hook/order changes: None expected
- Requires ADR? No
- Risk register impact required? No
- Risk dashboard impact required? No

Risk synchronization rule:
- Whenever a risk investigation begins, ensure the risk exists in:
  - `docs/00-governance/risk-register.md`
  - `docs/00-governance/risk-status-dashboard.md`
- If a new risk is discovered, it MUST be registered in both files before implementation begins.

Normative rules:
- Governance impact analysis is REQUIRED and BLOCKING.
- If authority ownership changes, ADR is REQUIRED before implementation.
- If risk posture changes, risk documentation update is REQUIRED.

## System Invariants Check
- Declared invariants that MUST remain true:
  - the shared product-card component remains the single storefront authority for hover media
  - hover image fallback must continue working for products that already use `_bw_slider_hover_image`
  - widgets must not each implement their own video-hover logic
  - no storefront surface should break if a product has no hover video
  - product links, overlay buttons, and image wrappers must remain deterministic and clickable
- Any invariant at risk? Yes
- Mitigation plan for invariant protection:
  - implement video resolution inside `BW_Product_Card_Component`
  - keep product admin save logic backward-compatible
  - use video-first resolution only when a valid media id is configured
  - preserve current image-only rendering path when video is absent

Normative rules:
- Invariants are binding constraints and MUST NOT be violated.
- If invariant safety is unclear, implementation MUST stop and escalate.

## Determinism Statement
- Input/output determinism declared? Yes
- Ordering determinism declared? Yes
- Retry determinism declared? Yes
- Pagination/state convergence determinism declared? Yes
- Determinism risks and controls:
  - hover-media precedence is explicitly declared: video > image > none
  - products without configured hover video must render the same as before
  - touch/mobile must not produce ambiguous half-hover states

Normative rules:
- Determinism expectations are REQUIRED and BLOCKING.
- Non-deterministic behavior in critical flows MUST be escalated to governance review.
- If deterministic behavior cannot be guaranteed, implementation MUST NOT proceed.

## Testing Strategy

Describe how the implementation will be verified.

- Local testing plan:
  - verify product metabox can save/remove hover image and hover video independently
  - verify a product with hover video shows video-first behavior
  - verify a product without hover video still uses hover image
  - verify a product with neither value still renders safely
  - verify product-grid / related-products / slick-slider inherit the same behavior
- Edge cases expected:
  - invalid or non-video media selected in the hover-video field
  - large or portrait video sources
  - products with gallery image but no custom hover media
  - mobile/touch devices with no hover state
- Failure scenarios considered:
  - video hides image but does not render
  - hover media blocks overlay or product link click target
  - admin metabox overwrite clears existing image accidentally

Normative rule:
Critical flows MUST have explicit testing strategy.

## Documentation Update Plan
Documentation layers that MUST be considered before implementation:

- `docs/00-governance/`
  - Impacted? No
  - Target documents (if known): -
- `docs/00-planning/`
  - Impacted? No
  - Target documents (if known): -
- `docs/10-architecture/`
  - Impacted? Yes
  - Target documents (if known):
    - `docs/10-architecture/elementor-widget-architecture-context.md`
- `docs/20-development/`
  - Impacted? Maybe
  - Target documents (if known):
    - `docs/20-development/admin-panel-map.md` if product edit metabox mapping needs explicit update
- `docs/30-features/`
  - Impacted? Yes
  - Target documents (if known):
    - `docs/30-features/elementor-widgets/README.md`
    - `docs/30-features/elementor-widgets/widget-inventory.md`
    - product-card / widget technical notes if new hover-media behavior is added
- `docs/40-integrations/`
  - Impacted? No
  - Target documents (if known): -
- `docs/50-ops/`
  - Impacted? Yes
  - Target documents (if known):
    - `docs/50-ops/regression-protocol.md`
- `docs/60-adr/`
  - Impacted? No
  - Target documents (if known): -
- `docs/60-system/`
  - Impacted? No
  - Target documents (if known): -

Normative rules:
- Documentation impact declaration is REQUIRED and BLOCKING.
- Implementation MUST NOT begin unless this declaration is completed.
- If behavior changes are expected, documentation targets MUST be declared before coding.

## Rollback Strategy

Describe rollback feasibility.

- Revert via commit possible? Yes
- Database migration involved? No
- Manual rollback steps required?
  - remove the new hover-video product meta usage
  - restore the previous metabox wording/fields if needed
  - revert shared component hover-media resolution changes

Normative rule:
If rollback is non-trivial, mitigation steps MUST be declared.

## 6A) Documentation Alignment Requirement
Before implementation begins, the documentation architecture MUST be evaluated.

The following documentation layers MUST be checked for potential updates:
- `docs/00-governance/` — No
- `docs/00-planning/` — No
- `docs/10-architecture/` — Yes
- `docs/20-development/` — Maybe
- `docs/30-features/` — Yes
- `docs/40-integrations/` — No
- `docs/50-ops/` — Yes
- `docs/60-adr/` — No
- `docs/60-system/` — No

For each layer specify:
- Impacted? Declared above
- Target documents (if known): Declared above

Normative rule:
- Implementation MUST NOT begin until documentation impact has been declared.

## Acceptance Gate
DO NOT IMPLEMENT YET.

Gate checklist:
- Task Classification completed? Yes
- Pre-Task Reading Checklist completed? Yes
- Scope Declaration completed? Yes
- Implementation Scope Lock passed? Yes
- Governance Impact Analysis completed? Yes
- System Invariants Check completed? Yes
- Determinism Statement completed? Yes
- Documentation Update Plan completed? Yes
- Documentation Alignment Requirement completed? Yes

Normative rules:
- All gate items are REQUIRED.
- Any `No` is BLOCKING.
- Implementation MUST NOT begin until all gate items are `Yes`.

## Release Gate Awareness
All tasks will pass through the Release Gate before deployment.

Task planning and implementation MUST preserve:
- rollback safety
- determinism guarantees
- documentation alignment

to ensure successful release validation.

Release Gate checklist reference:
`docs/50-ops/release-gate.md`

Normative rule:
- Work that cannot satisfy Release Gate requirements MUST be escalated before deployment.

## Current Technical Finding Snapshot
- current product admin metabox:
  - file: `includes/product-types/class-bw-product-slider-metabox.php`
  - current field: `_bw_slider_hover_image`
- current shared hover-media authority:
  - file: `includes/components/product-card/class-bw-product-card-component.php`
  - current resolution: hover image only
- current candidate naming direction:
  - rename admin box meaning from `Product slider (Slick Slider)` to a generic hover-media label, with current preferred naming direction around `Hover Media`
- current media recommendation for hover video:
  - MP4 / H.264 / muted / loop / playsinline
  - short clip (`~3–5s`)
  - lightweight target (`~0.5–2 MB`)

## Abort Conditions
- Scope drift detected
- Undeclared authority surface discovered
- Invariant breach risk not mitigated
- Determinism cannot be guaranteed
- Required documentation alignment cannot be completed
