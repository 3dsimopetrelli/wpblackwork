# Blackwork Governance — Task Start Template

## Context
- Task title: Media Folders duplicate-assignment warning popup across Media, Posts, Pages, Products
- Request source: User request on 2026-03-16
- Expected outcome: When dragging a media item, post, page, or product into a folder that already contains that same item, Media Folders must not silently no-op; it must show a small admin popup warning that the item already exists in that folder, dismissable by clicking the background.
- Constraints:
  - Preserve existing drag-and-drop UX contracts and assignment endpoint shapes as much as possible.
  - Scope to Media Folders admin surfaces only.
  - Keep folder isolation per content type intact.
  - Keep fail-open behavior for invalid contexts or payloads.

## Task Classification
- Domain: Media Folders admin runtime
- Incident/Task type: UX hardening / duplicate-assignment feedback
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 2
- Affected systems: Media Folders AJAX assignment, admin sidebar drag/drop UI, Media Library and list-table admin screens
- Integration impact: Internal module only; no storefront impact
- Regression scope required: Media Library drag/drop, Posts/Pages/Products single-item drag assignment, bulk media assignment, folder isolation, admin popup behavior

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
  - Media Folders admin regression checklist already captured in module spec; extend only if popup adds new visible behavior.
- Architecture references to read:
  - `includes/modules/media-folders/runtime/ajax.php`
  - `includes/modules/media-folders/admin/assets/media-folders.js`
  - `includes/modules/media-folders/admin/assets/media-folders.css`

## Scope Declaration
- Proposed strategy:
  - Extend the existing assignment endpoint to detect duplicate target-folder assignments before writing.
  - Return a deterministic duplicate marker in the existing JSON response without changing the success/failure transport contract.
  - Add a lightweight admin popup/overlay in Media Folders JS that appears only when duplicate assignment is detected and closes on background click.
- Files likely impacted:
  - `includes/modules/media-folders/runtime/ajax.php`
  - `includes/modules/media-folders/admin/assets/media-folders.js`
  - `includes/modules/media-folders/admin/assets/media-folders.css`
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `docs/20-development/admin-panel-map.md`
  - `docs/50-ops/regression-protocol.md`
  - `docs/tasks/BW-TASK-20260316-01-start.md`
  - `docs/tasks/BW-TASK-20260316-01-closure.md`
- Explicitly out-of-scope surfaces:
  - Media query filtering logic
  - Folder create/rename/delete flows
  - Storefront behavior
  - New endpoint introduction unless strictly necessary
- Risk analysis:
  - Primary risk is changing assignment semantics in a way that breaks successful assignment responses.
  - Mitigation: preserve success responses and add duplicate metadata as additive payload only.
- ADR evaluation (REQUIRED / NOT REQUIRED): NOT REQUIRED

## Runtime Surface Declaration
- New hooks expected: None
- Hook priority modifications: None
- Filters expected: None
- AJAX endpoints expected:
  - Modify `bw_media_assign_folder` response payload only
- Admin routes expected: None

### Supabase Flow Risk Alert
- Not applicable; no Supabase protected surface touched.

## 3.1) Implementation Scope Lock
- All files expected to change are listed? (Yes/No): Yes
- Hidden coupling risks discovered? (Yes/No): No

## Governance Impact Analysis
- Authority surfaces touched: Media Folders term assignment authority via existing AJAX endpoint
- Data integrity risk: Low; duplicate detection prevents redundant writes
- Security surface changes: No new surface; existing nonce/capability checks remain authoritative
- Runtime hook/order changes: None
- Requires ADR? (Yes/No): No
- Risk register impact required? (Yes/No): No
- Risk dashboard impact required? (Yes/No): No

## System Invariants Check
- Declared invariants that MUST remain true:
  - Folder isolation per content type remains strict
  - Media bulk assignment remains supported
  - Posts/Pages/Products remain single-item drag only
  - Drag ghost still shows title for list tables
  - Existing endpoint/capability/nonce model remains intact
- Any invariant at risk? (Yes/No): No
- Mitigation plan for invariant protection:
  - Detect duplicates only inside the resolved taxonomy/context
  - Additive response fields only; do not change assignment payload requirements

## Determinism Statement
- Input/output determinism declared? (Yes/No): Yes
- Ordering determinism declared? (Yes/No): Yes
- Retry determinism declared? (Yes/No): Yes
- Pagination/state convergence determinism declared? (Yes/No): Yes
- Determinism risks and controls:
  - Repeated drag into the same folder must always produce the same duplicate warning outcome.
  - Mixed batches (media bulk) must deterministically separate `assigned_ids` and `duplicate_ids`.
  - Popup visibility is driven only by explicit duplicate response data and dismissed by explicit user click.

## Testing Strategy
- Local testing plan:
  - Drag a media item into a new folder -> assignment succeeds, no popup
  - Drag same media item into same folder again -> popup appears
  - Drag a post/page/product into its assigned folder again -> popup appears
  - Click overlay background -> popup closes
  - Drag into a different folder -> normal assignment works
- Edge cases expected:
  - Unassigned target (`term_id = 0`) should not trigger duplicate warning
  - Bulk media assignment with some duplicates and some new items
  - Invalid parent context should still fail-open through existing endpoint guards
- Failure scenarios considered:
  - Duplicate detection must not convert valid success into hard error
  - Popup must not remain stuck after refresh or background click

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
  - Revert the implementation commit(s); no data cleanup needed.

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
