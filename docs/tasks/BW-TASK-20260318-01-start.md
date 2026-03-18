# Blackwork Governance — Task Start Template

## Context
- Task title: Media Folders list-table copy-link column for Posts / Pages / Products
- Request source: User request on 2026-03-18
- Expected outcome: Add a compact `Link` column near `Author` on Posts / Pages / Products list tables, with a copy-to-clipboard button that copies the row permalink directly from the list view.
- Constraints:
  - Scope to admin list tables only (`edit.php`, `edit.php?post_type=page`, `edit.php?post_type=product`).
  - Integrate cleanly with the existing Media Folders list-table layer.
  - No new endpoints.
  - No storefront/runtime changes outside admin.

## Task Classification
- Domain: Media Folders admin list-table UX
- Incident/Task type: Admin productivity feature
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 2
- Affected systems: list-table column registration/rendering, admin JS clipboard interaction, Media Folders scoped admin CSS
- Integration impact: internal admin only
- Regression scope required: Posts/Pages/Products list tables, drag-handle column placement, row actions/title/author layout

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
  - Media Folders list-table regression checks
- Architecture references to read:
  - `includes/modules/media-folders/admin/media-folders-admin.php`
  - `includes/modules/media-folders/admin/assets/media-folders.js`
  - `includes/modules/media-folders/admin/assets/media-folders.css`

## Scope Declaration
- Proposed strategy:
  - Add a new compact list-table column key for supported non-media post types.
  - Insert it deterministically before `author` when available, with safe fallback ordering.
  - Render a copy-link button using the row permalink as data attribute and handle clipboard copy in existing Media Folders admin JS.
- Files likely impacted:
  - `includes/modules/media-folders/admin/media-folders-admin.php`
  - `includes/modules/media-folders/admin/assets/media-folders.js`
  - `includes/modules/media-folders/admin/assets/media-folders.css`
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `docs/20-development/admin-panel-map.md`
  - `docs/50-ops/regression-protocol.md`
  - `docs/tasks/BW-TASK-20260318-01-start.md`
- Explicitly out-of-scope surfaces:
  - Media Library (`upload.php`)
  - New AJAX endpoints
  - Storefront templates
  - Folder assignment logic
- Risk analysis:
  - Main risk is disrupting list-table column order or widening the table.
  - Mitigation: use a narrow fixed-width column and insert before `author` with deterministic fallback order.
- ADR evaluation (REQUIRED / NOT REQUIRED): NOT REQUIRED

## Runtime Surface Declaration
- New hooks expected: None
- Hook priority modifications: None
- Filters expected:
  - existing `manage_*_posts_columns` / `manage_edit-*` filters extended to include copy-link column
- AJAX endpoints expected: None
- Admin routes expected: None

### Supabase Flow Risk Alert
- Not applicable.

## 3.1) Implementation Scope Lock
- All files expected to change are listed? (Yes/No): Yes
- Hidden coupling risks discovered? (Yes/No): No

## Governance Impact Analysis
- Authority surfaces touched: Media Folders admin list-table UX only
- Data integrity risk: Low
- Security surface changes: None; copy uses already available row permalink only
- Runtime hook/order changes: existing admin column filters/actions extended
- Requires ADR? (Yes/No): No
- Risk register impact required? (Yes/No): No
- Risk dashboard impact required? (Yes/No): No

## System Invariants Check
- Declared invariants that MUST remain true:
  - Folder isolation per content type remains strict
  - Drag-handle column remains present and unchanged in behavior
  - No new query/filter behavior is introduced
  - Feature remains admin-only
- Any invariant at risk? (Yes/No): No
- Mitigation plan for invariant protection:
  - Scope column registration to supported non-media list screens only.
  - Reuse the existing Media Folders enqueue/screen gating.

## Determinism Statement
- Input/output determinism declared? (Yes/No): Yes
- Ordering determinism declared? (Yes/No): Yes
- Retry determinism declared? (Yes/No): Yes
- Pagination/state convergence determinism declared? (Yes/No): Yes
- Determinism risks and controls:
  - Same row always renders the same copy URL for the current permalink.
  - Column order is deterministic by explicit anchor priority.
  - Re-clicking the copy button repeats the same clipboard payload.

## Testing Strategy
- Local testing plan:
  - Pages list -> copy button copies permalink
  - Posts list -> copy button copies permalink
  - Products list -> copy button copies permalink
  - drag-handle and marker columns remain aligned
- Edge cases expected:
  - rows without usable permalink fallback gracefully
  - narrow screens do not break column alignment excessively
- Failure scenarios considered:
  - clipboard API unavailable -> fallback copy path
  - no JS error if clipboard fails

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
  - Impacted? (Yes/No): Yes
  - Target documents (if known): `docs/20-development/admin-panel-map.md`
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
  - Revert implementation commit(s); no data rollback required.

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
  - Impacted? (Yes/No): Yes
  - Target documents (if known): `docs/20-development/admin-panel-map.md`
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
