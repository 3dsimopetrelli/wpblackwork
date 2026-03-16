# Blackwork Governance — Task Start Template

## Context
- Task title: Media Folders bulk organize support for Posts / Pages / Products list tables
- Request source: User request on 2026-03-16
- Expected outcome: Enable the existing `Bulk organize` sidebar control on Posts, Pages, and Products list tables so selected rows can be moved to a folder via checkbox selection, using the existing Media Folders assignment flow.
- Constraints:
  - Preserve folder isolation per content type.
  - Reuse the existing assignment endpoint and current nonce/capability model.
  - Keep existing Media behavior intact.
  - No storefront changes.

## Task Classification
- Domain: Media Folders admin runtime
- Incident/Task type: Admin UX capability extension
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 2
- Affected systems: Media Folders sidebar UI, list-table checkbox selection flow, assignment endpoint usage on Posts/Pages/Products
- Integration impact: Internal module only
- Regression scope required: Posts/Pages/Products list tables, Media bulk organize, duplicate assignment popup, drag-handle UX

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
  - `includes/modules/media-folders/admin/media-folders-sidebar.php`
  - `includes/modules/media-folders/admin/assets/media-folders.js`
  - `includes/modules/media-folders/runtime/ajax.php`

## Scope Declaration
- Proposed strategy:
  - Stop hiding the shared `Bulk organize` UI on non-media list screens.
  - Generalize checkbox-selected ID collection so Posts/Pages/Products can use the same bulk move action.
  - Keep bulk assignment routed through `bw_media_assign_folder` with the current context resolution.
- Files likely impacted:
  - `includes/modules/media-folders/admin/assets/media-folders.js`
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `docs/20-development/admin-panel-map.md`
  - `docs/50-ops/regression-protocol.md`
  - `docs/tasks/BW-TASK-20260316-03-start.md`
- Explicitly out-of-scope surfaces:
  - New endpoints
  - Query filtering logic
  - Storefront behavior
  - Changes to drag-handle placement
- Risk analysis:
  - Main risk is changing the documented UX contract from single-item drag only to include checkbox-based bulk organize on list tables.
  - Mitigation: limit the change to the existing sidebar bulk control and keep drag behavior unchanged.
- ADR evaluation (REQUIRED / NOT REQUIRED): NOT REQUIRED

## Runtime Surface Declaration
- New hooks expected: None
- Hook priority modifications: None
- Filters expected: None
- AJAX endpoints expected:
  - reuse existing `bw_media_assign_folder`
- Admin routes expected: None

### Supabase Flow Risk Alert
- Not applicable.

## 3.1) Implementation Scope Lock
- All files expected to change are listed? (Yes/No): Yes
- Hidden coupling risks discovered? (Yes/No): No

## Governance Impact Analysis
- Authority surfaces touched: Media Folders assignment UX only
- Data integrity risk: Low
- Security surface changes: None; existing nonce/capability checks remain in force
- Runtime hook/order changes: None
- Requires ADR? (Yes/No): No
- Risk register impact required? (Yes/No): No
- Risk dashboard impact required? (Yes/No): No

## System Invariants Check
- Declared invariants that MUST remain true:
  - Folder isolation per content type remains strict
  - Drag handle remains the only drag start source for list tables
  - Existing duplicate assignment popup remains functional
  - Existing Media bulk assignment remains functional
- Any invariant at risk? (Yes/No): No
- Mitigation plan for invariant protection:
  - Bulk organize uses checkbox selection only; drag behavior remains unchanged.
  - Assignment endpoint continues to validate post type and taxonomy by context.

## Determinism Statement
- Input/output determinism declared? (Yes/No): Yes
- Ordering determinism declared? (Yes/No): Yes
- Retry determinism declared? (Yes/No): Yes
- Pagination/state convergence determinism declared? (Yes/No): Yes
- Determinism risks and controls:
  - Checkbox-selected rows produce a deterministic normalized ID list from current page DOM.
  - Repeated bulk move to same folder converges through existing duplicate detection.

## Testing Strategy
- Local testing plan:
  - select multiple Posts and bulk move -> assigned correctly
  - select multiple Pages and bulk move -> assigned correctly
  - select multiple Products and bulk move -> assigned correctly
  - repeat move into same folder -> duplicate popup appears
  - Media bulk organize still works
- Edge cases expected:
  - no rows selected
  - mixed selected rows already assigned and unassigned
  - target folder `Unassigned`
- Failure scenarios considered:
  - checkbox selection must not accidentally include header checkbox row
  - non-media list tables must not lose existing drag UX

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
