# Blackwork Governance — Task Start Template

## Context
- Task title: Media Folders list-table folder marker dots for Posts / Pages / Products
- Request source: User request on 2026-03-16
- Expected outcome: Show a small folder-assignment marker dot in the drag-handle column for Posts, Pages, and Products list tables. The dot is visible only when the row is assigned to a folder; it uses the folder custom color when present, otherwise black. No dot for unassigned items.
- Constraints:
  - Reuse Media Folders existing assignment/color model.
  - Scope to admin list tables only (`edit.php`, `edit.php?post_type=page`, `edit.php?post_type=product`).
  - No new storefront behavior.
  - Avoid per-row heavy queries; use batched retrieval for current page rows.

## Task Classification
- Domain: Media Folders admin runtime
- Incident/Task type: UI feature / admin indicator extension
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 2
- Affected systems: Media Folders admin JS/CSS, list-table drag-handle column, AJAX marker data path
- Integration impact: Internal module only
- Regression scope required: Posts/Pages/Products list tables, Media marker behavior, drag handle UX, folder isolation

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
  - `includes/modules/media-folders/runtime/ajax.php`
  - `includes/modules/media-folders/admin/assets/media-folders.js`
  - `includes/modules/media-folders/admin/assets/media-folders.css`
  - `includes/modules/media-folders/admin/media-folders-admin.php`

## Scope Declaration
- Proposed strategy:
  - Extend the existing marker endpoint/data flow so current-page list-table rows receive marker metadata in batch.
  - Render a compact dot inside the drag-handle cell, under the 4-arrows handle, only for assigned rows.
  - Use folder custom color when available; fallback black when assigned but no custom color.
- Files likely impacted:
  - `includes/modules/media-folders/runtime/ajax.php`
  - `includes/modules/media-folders/admin/assets/media-folders.js`
  - `includes/modules/media-folders/admin/assets/media-folders.css`
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `docs/20-development/admin-panel-map.md`
  - `docs/50-ops/regression-protocol.md`
  - `docs/tasks/BW-TASK-20260316-02-start.md`
- Explicitly out-of-scope surfaces:
  - Media query filtering
  - Folder assignment semantics
  - New list-table columns
  - Storefront and frontend templates
- Risk analysis:
  - Main risk is adding row-level queries or duplicating marker logic in a non-batched way.
  - Mitigation: only fetch marker data for visible/current-page rows in one batch per refresh cycle.
- ADR evaluation (REQUIRED / NOT REQUIRED): NOT REQUIRED

## Runtime Surface Declaration
- New hooks expected: None
- Hook priority modifications: None
- Filters expected: None
- AJAX endpoints expected:
  - reuse/extend existing marker endpoint payload handling for non-media list contexts if needed
- Admin routes expected: None

### Supabase Flow Risk Alert
- Not applicable.

## 3.1) Implementation Scope Lock
- All files expected to change are listed? (Yes/No): Yes
- Hidden coupling risks discovered? (Yes/No): No

## Governance Impact Analysis
- Authority surfaces touched: Media Folders admin marker/indicator surface
- Data integrity risk: Low
- Security surface changes: No new surface; existing nonce/capability model retained
- Runtime hook/order changes: None
- Requires ADR? (Yes/No): No
- Risk register impact required? (Yes/No): No
- Risk dashboard impact required? (Yes/No): No

## System Invariants Check
- Declared invariants that MUST remain true:
  - Folder isolation per content type remains strict
  - Drag-handle column position and single-item drag behavior remain unchanged
  - No marker for unassigned items
  - Media marker behavior remains intact
- Any invariant at risk? (Yes/No): No
- Mitigation plan for invariant protection:
  - Resolve markers strictly through current context taxonomy
  - Render dot only when endpoint/cache reports assigned=true

## Determinism Statement
- Input/output determinism declared? (Yes/No): Yes
- Ordering determinism declared? (Yes/No): Yes
- Retry determinism declared? (Yes/No): Yes
- Pagination/state convergence determinism declared? (Yes/No): Yes
- Determinism risks and controls:
  - Same row/context must always map to the same marker color until assignment changes.
  - Unassigned rows must consistently render no marker.
  - Refresh/reload must converge to the same visible marker state.

## Testing Strategy
- Local testing plan:
  - Assigned Post/Page/Product with folder custom color -> colored dot visible in drag column
  - Assigned item without folder custom color -> black dot visible
  - Unassigned item -> no dot
  - Drag handle UX remains intact
- Edge cases expected:
  - Mixed list pages with assigned and unassigned items
  - Folder color removed after previous assignment
  - Pagination / current page refresh
- Failure scenarios considered:
  - endpoint returns no marker data -> fail-open, no dot
  - CSS must not widen the drag column unexpectedly

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
