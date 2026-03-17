# Blackwork Governance — Task Start Template

## Context
- Task title: Media Folders counts invalidation hardening for new/unassigned content lifecycle
- Request source: User report on 2026-03-17
- Expected outcome: `Unassigned Items` and summary/tree counts must update immediately when new supported content is created or when supported content changes counted state, without waiting for cache TTL expiry.
- Constraints:
  - Preserve existing folder isolation per content type.
  - Preserve existing assignment/query filter behavior.
  - Keep caching model, but fix invalidation coverage.
  - Scope to Media Folders runtime/admin only.

## Task Classification
- Domain: Media Folders runtime cache invalidation
- Incident/Task type: Bug fix / cache lifecycle hardening
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 2
- Affected systems: sidebar summary counts, folder tree counts, count cache invalidation hooks
- Integration impact: internal module only
- Regression scope required: Pages, Posts, Products, Media summary counts; assignment flows; cached counts refresh

## Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/media-folders/media-folders-module-spec.md`
- Integration docs to read:
  - `docs/20-development/admin-panel-map.md`
- Ops/control docs to read:
  - `docs/50-ops/regression-protocol.md`
- Governance docs to read:
  - `docs/00-governance/ai-task-protocol.md`
  - `docs/governance/task-close.md`
  - `docs/templates/task-closure-template.md`
- Runbook to follow:
  - Media Folders summary/count regression checks
- Architecture references to read:
  - `includes/modules/media-folders/runtime/ajax.php`

## Scope Declaration
- Proposed strategy:
  - Extend count-cache invalidation to post lifecycle transitions for supported post types.
  - Invalidate caches only when a post enters or leaves a counted state to avoid unnecessary churn.
- Files likely impacted:
  - `includes/modules/media-folders/runtime/ajax.php`
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `docs/50-ops/regression-protocol.md`
  - `docs/tasks/BW-TASK-20260317-01-start.md`
- Explicitly out-of-scope surfaces:
  - Query filter logic
  - Folder CRUD flows
  - Storefront behavior
  - New cache layer or TTL changes
- Risk analysis:
  - Main risk is over-invalidating on every post save.
  - Mitigation: invalidate only on counted-state transitions, keyed by supported post type + mapped taxonomy.
- ADR evaluation (REQUIRED / NOT REQUIRED): NOT REQUIRED

## Runtime Surface Declaration
- New hooks expected:
  - `transition_post_status` (scoped invalidation)
- Hook priority modifications: None
- Filters expected: None
- AJAX endpoints expected: None
- Admin routes expected: None

### Supabase Flow Risk Alert
- Not applicable.

## 3.1) Implementation Scope Lock
- All files expected to change are listed? (Yes/No): Yes
- Hidden coupling risks discovered? (Yes/No): No

## Governance Impact Analysis
- Authority surfaces touched: Media Folders cache invalidation only
- Data integrity risk: Low; improves convergence of already-authoritative counts
- Security surface changes: None
- Runtime hook/order changes: additive invalidation hook only
- Requires ADR? (Yes/No): No
- Risk register impact required? (Yes/No): No
- Risk dashboard impact required? (Yes/No): No

## System Invariants Check
- Declared invariants that MUST remain true:
  - Folder isolation per content type remains strict
  - Cache keys remain taxonomy + post_type scoped
  - Assignment flows keep existing invalidation semantics
  - No query mutation behavior changes
- Any invariant at risk? (Yes/No): No
- Mitigation plan for invariant protection:
  - Resolve taxonomy via authoritative post_type -> taxonomy map only.
  - Invalidate only if post type is supported and counted-state changes.

## Determinism Statement
- Input/output determinism declared? (Yes/No): Yes
- Ordering determinism declared? (Yes/No): Yes
- Retry determinism declared? (Yes/No): Yes
- Pagination/state convergence determinism declared? (Yes/No): Yes
- Determinism risks and controls:
  - Same lifecycle transition must always invalidate the same cache namespace.
  - Unsupported post types must never invalidate Media Folders caches.
  - Repeated transitions with no counted-state change must not churn caches.

## Testing Strategy
- Local testing plan:
  - create new unassigned Page -> `Unassigned Items` increments immediately
  - create new unassigned Post -> increments immediately
  - upload new unassigned Media -> increments immediately
  - create new unassigned Product -> increments immediately
  - move assigned item to trash / restore -> counts converge
- Edge cases expected:
  - auto-draft to draft/publish
  - publish to trash
  - draft to publish
- Failure scenarios considered:
  - unsupported post types should no-op
  - same counted-state transition should no-op

## Documentation Update Plan
Documentation layers that MUST be considered before implementation:

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
  - Impacted? (Yes/No): Yes
  - Target documents (if known): `docs/30-features/media-folders/media-folders-module-spec.md`
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
- Manual rollback steps required?
  - Revert implementation commit(s); no data migration rollback required.

## 6A) Documentation Alignment Requirement
Before implementation begins, the documentation architecture MUST be evaluated.

The following documentation layers MUST be checked for potential updates:
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
  - Impacted? (Yes/No): Yes
  - Target documents (if known): `docs/30-features/media-folders/media-folders-module-spec.md`
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

## Acceptance Gate
DO NOT IMPLEMENT YET.

Gate checklist:
- Task Classification completed? (Yes/No): Yes
- Pre-Task Reading Checklist completed? (Yes/No): Yes
- Scope Declaration completed? (Yes/No): Yes
- Implementation Scope Lock passed? (Yes/No): Yes
- Governance Impact Analysis completed? (Yes/No): Yes
- System Invariants Check completed? (Yes/No): Yes
- Determinism Statement completed? (Yes/No): Yes
- Documentation Update Plan completed? (Yes/No): Yes
- Documentation Alignment Requirement completed? (Yes/No): Yes

## Release Gate Awareness
All tasks will pass through the Release Gate before deployment.

Task planning and implementation MUST preserve:
- rollback safety
- determinism guarantees
- documentation alignment

to ensure successful release validation.

Release Gate checklist reference:
`docs/50-ops/release-gate.md`

## Abort Conditions
- Scope drift detected
- Undeclared authority surface discovered
- Invariant breach risk not mitigated
- Determinism cannot be guaranteed
- Required documentation alignment cannot be completed
