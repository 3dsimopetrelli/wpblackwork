# Blackwork Governance — Task Start Template

## Context
- Task title: Media Folders — Add "New Subfolder" action across Media/Post/Page/Product trees
- Request source: User request in current thread
- Expected outcome:
  - Add "New Subfolder" entry in pencil context menu for every Media Folders context (Media, Posts, Pages, Products).
  - Clicking it creates a nested folder under the clicked node using current create-folder UX.
  - Child appears immediately in current tree with existing refresh flow.
- Constraints:
  - Folder isolation per content type must remain intact.
  - Global "New Folder" stays root-only.
  - Prefer extending existing create endpoint payload; no contract break.
  - Preserve caching/invalidation and drag UX contracts.

Normative rules:
- Context fields are REQUIRED.
- Implementation MUST NOT begin with missing context.

## Task Classification
- Domain: Media Folders / Admin UX + Runtime validation
- Incident/Task type: Feature increment (bounded)
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `includes/modules/media-folders/admin/assets/media-folders.js`
  - `includes/modules/media-folders/runtime/ajax.php`
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `docs/20-development/admin-panel-map.md`
  - `docs/00-planning/decision-log.md`
- Integration impact:
  - Admin list screens with Media Folders sidebar:
    - `upload.php`
    - `edit.php`
    - `edit.php?post_type=page`
    - `edit.php?post_type=product`
- Regression scope required:
  - Context menu actions
  - Create folder endpoint parent validation behavior
  - Tree refresh and folder isolation

Normative rules:
- Task classification is REQUIRED and BLOCKING.
- Tier 0 authority-impacting tasks MUST trigger governance escalation before implementation.
- Misclassified tasks MUST be corrected before implementation.

## Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/media-folders/media-folders-module-spec.md`
- Integration docs to read:
  - `docs/20-development/admin-panel-map.md`
- Ops/control docs to read:
  - `docs/50-ops/runtime-hook-map.md` (awareness)
- Governance docs to read:
  - `docs/00-governance/ai-task-protocol.md`
  - `docs/00-governance/risk-register.md`
  - `docs/00-planning/decision-log.md`
- Runbook to follow:
  - N/A
- Architecture references to read:
  - `docs/templates/task-start-template.md`
  - `docs/templates/task-closure-template.md`

Normative rules:
- Reading checklist completion is REQUIRED.
- Implementation MUST NOT begin without declaring required references.

## Scope Declaration
- Proposed strategy:
  - Extend context menu render with `New Subfolder` item in required order.
  - Reuse existing create-folder prompt flow (`window.prompt`) and endpoint `bw_media_create_folder`.
  - Send `parent=<current folder id>` from menu action; keep root button behavior unchanged.
  - Harden backend parent sanitization with `wp_unslash` + `absint` while preserving taxonomy-context validation.
  - Reuse existing `refreshTree()` to render nested folder immediately.
- Files likely impacted:
  - `includes/modules/media-folders/admin/assets/media-folders.js`
  - `includes/modules/media-folders/runtime/ajax.php`
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `docs/20-development/admin-panel-map.md`
  - `docs/00-planning/decision-log.md`
- Explicitly out-of-scope surfaces:
  - Drag behavior logic and handle placement
  - List/grid query filters and caching engine
  - Frontend/storefront behavior
- Risk analysis:
  - Medium: cross-context contamination risk if parent validation drifts.
  - Low: UI ordering/action mapping mismatch in context menu.
- ADR evaluation (REQUIRED / NOT REQUIRED):
  - NOT REQUIRED (no authority ownership changes; scoped feature extension).

Normative rules:
- Scope declaration is REQUIRED and BLOCKING.
- All target files MUST be declared before implementation.
- Out-of-scope boundaries MUST be explicit.

## Runtime Surface Declaration

Declare expected runtime surfaces affected.

- New hooks expected:
  - None.
- Hook priority modifications:
  - None.
- Filters expected:
  - None.
- AJAX endpoints expected:
  - Existing `bw_media_create_folder` payload extended in usage with `parent` for subfolder action.
- Admin routes expected:
  - None.

Normative rule:
All expected runtime mutations MUST be declared before implementation.

## 3.1) Implementation Scope Lock

Confirm that the declared scope is complete.

- All files expected to change are listed? (Yes/No): Yes
- Hidden coupling risks discovered? (Yes/No): Yes
  - Menu command wiring depends on current context menu command dispatcher.

Normative rules:

Implementation MUST NOT modify files outside the declared scope.

If new surfaces are discovered during development, the task MUST pause and the scope MUST be updated.

## Governance Impact Analysis
- Authority surfaces touched:
  - Media Folders admin UI + existing create-folder runtime endpoint validation.
- Data integrity risk:
  - Medium (must prevent cross-taxonomy parent assignment).
- Security surface changes:
  - None new; existing nonce/capability checks retained.
- Runtime hook/order changes:
  - None.
- Requires ADR? (Yes/No): No
- Risk register impact required? (Yes/No): No (existing risks unchanged; behavior bounded by current validation pattern).

Normative rules:
- Governance impact analysis is REQUIRED and BLOCKING.
- If authority ownership changes, ADR is REQUIRED before implementation.
- If risk posture changes, risk documentation update is REQUIRED.

## System Invariants Check
- Declared invariants that MUST remain true:
  - Folder isolation per content type remains strict.
  - Root "New Folder" continues creating root nodes.
  - Existing AJAX contracts remain valid (same endpoint/action/nonce/capability model).
  - Drag UX contracts unchanged (handle position, single-item drag, title ghost).
  - Caching/invalidation strategy unchanged.
- Any invariant at risk? (Yes/No): No
- Mitigation plan for invariant protection:
  - Keep parent validation by same taxonomy/context (`bw_mf_get_folder_term_or_error($parent, $taxonomy)`).
  - Reuse current request context resolver and endpoint.

Normative rules:
- Invariants are binding constraints and MUST NOT be violated.
- If invariant safety is unclear, implementation MUST stop and escalate.

## Determinism Statement
- Input/output determinism declared? (Yes/No): Yes
- Ordering determinism declared? (Yes/No): Yes
- Retry determinism declared? (Yes/No): Yes
- Pagination/state convergence determinism declared? (Yes/No): Yes
- Determinism risks and controls:
  - Risk: wrong parent target in menu action.
  - Control: derive parent from current row context id and validate server-side in taxonomy scope.

Normative rules:
- Determinism expectations are REQUIRED and BLOCKING.
- Non-deterministic behavior in critical flows MUST be escalated to governance review.
- If deterministic behavior cannot be guaranteed, implementation MUST NOT proceed.

## Testing Strategy

Describe how the implementation will be verified.

- Local testing plan:
  - Open context menu via pencil in each context (Media/Post/Page/Product).
  - Create subfolder under an existing node and verify nested rendering immediately.
  - Validate root new-folder button still creates root node.
  - Validate cross-context isolation (trees stay taxonomy-specific).
- Edge cases expected:
  - Empty tree (no parent to subfolder from menu).
  - Invalid parent id payload (server rejects).
- Failure scenarios considered:
  - Parent term from another taxonomy/context (must fail).

Acceptance criteria:
1. `New Subfolder` appears in pencil context menu across all supported contexts.
2. Clicking it creates a child under selected folder using existing create flow.
3. Parent validation blocks cross-context contamination.
4. No regression in root New Folder, drag contracts, caching/invalidation, query guards.

Normative rule:
Critical flows MUST have explicit testing strategy.

## Documentation Update Plan
Documentation layers that MUST be considered before implementation:

- `docs/00-governance/`
  - Impacted? (Yes/No): No
  - Target documents (if known): N/A
- `docs/00-planning/`
  - Impacted? (Yes/No): Yes
  - Target documents (if known): `docs/00-planning/decision-log.md`
- `docs/10-architecture/`
  - Impacted? (Yes/No): No
  - Target documents (if known): N/A
- `docs/20-development/`
  - Impacted? (Yes/No): Yes
  - Target documents (if known): `docs/20-development/admin-panel-map.md`
- `docs/30-features/`
  - Impacted? (Yes/No): Yes
  - Target documents (if known): `docs/30-features/media-folders/media-folders-module-spec.md`
- `docs/40-integrations/`
  - Impacted? (Yes/No): No
  - Target documents (if known): N/A
- `docs/50-ops/`
  - Impacted? (Yes/No): No
  - Target documents (if known): N/A
- `docs/60-adr/`
  - Impacted? (Yes/No): No
  - Target documents (if known): N/A
- `docs/60-system/`
  - Impacted? (Yes/No): No
  - Target documents (if known): N/A

Normative rules:
- Documentation impact declaration is REQUIRED and BLOCKING.
- Implementation MUST NOT begin unless this declaration is completed.
- If behavior changes are expected, documentation targets MUST be declared before coding.

## Rollback Strategy

Describe rollback feasibility.

- Revert via commit possible? (Yes/No): Yes
- Database migration involved? (Yes/No): No
- Manual rollback steps required?
  - Revert task commits.
  - Emergency no-op fallback: `bw_core_flags['media_folders']=0`.

Normative rule:
If rollback is non-trivial, mitigation steps MUST be declared.

## 6A) Documentation Alignment Requirement
Before implementation begins, the documentation architecture MUST be evaluated.

The following documentation layers MUST be checked for potential updates:
- `docs/00-governance/`
- `docs/00-planning/`
- `docs/10-architecture/`
- `docs/20-development/`
- `docs/30-features/`
- `docs/40-integrations/`
- `docs/50-ops/`
- `docs/60-adr/`
- `docs/60-system/`

For each layer specify:
- Impacted? (Yes/No)
- Target documents (if known)

Normative rule:
- Implementation MUST NOT begin until documentation impact has been declared.

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

Normative rules:
- All gate items are REQUIRED.
- Any `No` is BLOCKING.
- Implementation MUST NOT begin until all gate items are `Yes`.

## Abort Conditions
- Scope drift detected
- Undeclared authority surface discovered
- Invariant breach risk not mitigated
- Determinism cannot be guaranteed
- Required documentation alignment cannot be completed
- ADR required but not approved

Normative rules:
- Any abort condition MUST stop the task immediately.
- Work MUST NOT continue until governance review resolves the blocking condition.

## Governance Enforcement Rule

This template defines the mandatory governance protocol for task execution in the Blackwork repository.

Implementation MUST NOT begin until this template is fully completed.

Any violation of declared scope, governance rules, determinism guarantees, or documentation alignment MUST stop the task immediately.

All AI agents operating in this repository MUST treat this template as a binding governance protocol.
