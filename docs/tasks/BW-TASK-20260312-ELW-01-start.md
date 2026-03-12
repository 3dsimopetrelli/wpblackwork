# Blackwork Governance — Task Start Template

## Context
- Task title: Implement governed infinite loading architecture for BW-UI Product Grid
- Request source: User request in Codex session (`BW-TASK-20260312-ELW-01`)
- Expected outcome: Safe frontend-only infinite loading for `bw-filtered-post-wall` with aligned initial/AJAX pagination contract, append-based loading, lightweight loader, and real sequential reveal behavior.
- Constraints:
  - Preserve `bw-filtered-post-wall` as canonical wall/grid widget
  - Preserve `BW_Product_Card_Component` authority
  - No big-bang rewrite
  - No global asset loading changes outside declared scope
  - Frontend behavior prioritized over Elementor editor behavior

## Task Classification
- Domain: Elementor Widgets / Filtered Post Wall / Frontend Runtime
- Incident/Task type: Governed feature hardening / architecture convergence
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `bw-filtered-post-wall` widget PHP render
  - FPW frontend JS/CSS runtime
  - FPW public AJAX endpoint
- Integration impact:
  - WooCommerce product-card rendering via `BW_Product_Card_Component`
  - Masonry / ImagesLoaded runtime
  - Elementor frontend widget boot path
- Regression scope required:
  - FPW initial render
  - FPW filter changes
  - FPW infinite loading append path
  - CSS-grid mode
  - Masonry safe fallback/adaptation

## Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
  - `docs/30-features/elementor-widgets/architecture-direction.md`
  - `docs/30-features/elementor-widgets/rationalization-policy.md`
  - `docs/30-features/elementor-widgets/migration-sequence.md`
- Integration docs to read:
  - `docs/30-features/theme-builder-lite/theme-builder-lite-spec.md`
- Ops/control docs to read:
  - `docs/50-ops/regression-protocol.md`
- Governance docs to read:
  - `docs/00-governance/ai-task-protocol.md`
  - `docs/00-governance/working-protocol.md`
- Runbook to follow: N/A
- Architecture references to read:
  - `docs/10-architecture/elementor-widget-architecture-context.md`
  - `docs/00-planning/core-evolution-plan.md`
  - `docs/00-planning/decision-log.md`

## Scope Declaration
- Proposed strategy:
  - Reuse the existing FPW endpoint and extend it compatibly with pagination metadata
  - Expose per-widget frontend pagination config through data attributes
  - Add append-based infinite loading in the existing FPW JS runtime
  - Treat CSS-grid as first-class path and keep Masonry on minimal safe relayout behavior
- Files likely impacted:
  - `includes/widgets/class-bw-filtered-post-wall-widget.php`
  - `assets/js/bw-filtered-post-wall.js`
  - `assets/css/bw-filtered-post-wall.css`
  - `blackwork-core-plugin.php`
  - `docs/tasks/BW-TASK-20260312-ELW-01-start.md`
  - `docs/tasks/BW-TASK-20260312-ELW-01-closure.md`
  - `docs/50-ops/regression-protocol.md`
- Explicitly out-of-scope surfaces:
  - unrelated widgets
  - header module
  - checkout/auth/supabase flows
  - broad `BW_Product_Card_Component` refactor unless strictly required for scope safety
- Risk analysis:
  - initial render vs AJAX behavior currently diverges
  - Masonry append behavior can regress layout if over-refactored
  - product-card image loading policy is broader than this task and should stay minimal
- ADR evaluation (REQUIRED / NOT REQUIRED): NOT REQUIRED

## Runtime Surface Declaration
- New hooks expected: none
- Hook priority modifications: none
- Filters expected: none
- AJAX endpoints expected:
  - modify existing `bw_fpw_filter_posts` response contract compatibly
- Admin routes expected: none

## 3.1) Implementation Scope Lock
- All files expected to change are listed? (Yes/No): Yes
- Hidden coupling risks discovered? (Yes/No): Yes

## Governance Impact Analysis
- Authority surfaces touched:
  - FPW frontend render and public AJAX pagination contract
- Data integrity risk:
  - low-medium; query/filter behavior must remain deterministic while append state is introduced
- Security surface changes:
  - none expected beyond existing nonce-protected endpoint reuse
- Runtime hook/order changes:
  - none expected
- Requires ADR? (Yes/No): No
- Risk register impact required? (Yes/No): No
- Risk dashboard impact required? (Yes/No): No

## System Invariants Check
- Declared invariants that MUST remain true:
  - `bw-filtered-post-wall` remains canonical wall/query-grid widget
  - current filter/query semantics remain intact
  - `BW_Product_Card_Component` remains product-card authority
  - no global asset loading regression is introduced
  - initial render remains server-rendered
- Any invariant at risk? (Yes/No): Yes
- Mitigation plan for invariant protection:
  - keep endpoint fields backward-compatible
  - isolate append mode to page > 1
  - reset pagination deterministically on filter changes
  - keep diffs local to FPW files and endpoint

## Determinism Statement
- Input/output determinism declared? (Yes/No): Yes
- Ordering determinism declared? (Yes/No): Yes
- Retry determinism declared? (Yes/No): Yes
- Pagination/state convergence determinism declared? (Yes/No): Yes
- Determinism risks and controls:
  - one in-flight request max per widget instance
  - explicit page/per_page payload
  - append state reset on every filter change
  - compatible `has_more/next_page` metadata returned by server

## Testing Strategy
- Local testing plan:
  - lint modified PHP files
  - run `composer run lint:main`
  - manual regression reasoning against initial render, filter reset, append loading, CSS-grid, Masonry safe path
- Edge cases expected:
  - no-more-results termination
  - rapid scroll near sentinel
  - filter change during/after append state
  - widgets with legacy unlimited `posts_per_page`
- Failure scenarios considered:
  - duplicate AJAX requests
  - stuck loading state
  - append duplicate page
  - grid layout instability in Masonry mode

## Documentation Update Plan
- `docs/00-governance/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/00-planning/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/10-architecture/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/20-development/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/30-features/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/40-integrations/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/50-ops/`
  - Impacted? (Yes/No): Yes
  - Target documents (if known): `docs/50-ops/regression-protocol.md`
- `docs/60-adr/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/60-system/`
  - Impacted? (Yes/No): No
  - Target documents (if known):

## Rollback Strategy
- Revert via commit possible? (Yes/No): Yes
- Database migration involved? (Yes/No): No
- Manual rollback steps required? No

## 6A) Documentation Alignment Requirement
- `docs/00-governance/`: Impacted? No
- `docs/00-planning/`: Impacted? No
- `docs/10-architecture/`: Impacted? No
- `docs/20-development/`: Impacted? No
- `docs/30-features/`: Impacted? No
- `docs/40-integrations/`: Impacted? No
- `docs/50-ops/`: Impacted? Yes — `docs/50-ops/regression-protocol.md`
- `docs/60-adr/`: Impacted? No
- `docs/60-system/`: Impacted? No

## Acceptance Gate
- Task Classification completed? (Yes/No): Yes
- Pre-Task Reading Checklist completed? (Yes/No): Yes
- Scope Declaration completed? (Yes/No): Yes
- Implementation Scope Lock passed? (Yes/No): Yes
- Governance Impact Analysis completed? (Yes/No): Yes
- System Invariants Check completed? (Yes/No): Yes
- Determinism Statement completed? (Yes/No): Yes
- Documentation Update Plan completed? (Yes/No): Yes
- Documentation Alignment Requirement completed? (Yes/No): Yes
