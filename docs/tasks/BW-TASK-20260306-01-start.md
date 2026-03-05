# Blackwork Governance — Task Start Template

## Context
- Task title: Media Folders query hardening: strict guards for main-query-only + screen-only + fail-open
- Request source: User request in current thread
- Expected outcome:
  - Prevent Media Folders query filters from touching unintended WP queries.
  - Preserve all existing Media Folders UX/contract behavior.
  - Enforce strict fail-open guard model for list/grid query modifications.
- Constraints:
  - Hardening only (guards + fail-open).
  - No new features, no UX changes, no payload contract changes.
  - Keep existing cache strategy/keys untouched.

Normative rules:
- Context fields are REQUIRED.
- Implementation MUST NOT begin with missing context.

## Task Classification
- Domain: Media Folders / Admin Runtime / Query Guarding
- Incident/Task type: Runtime hardening
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `includes/modules/media-folders/runtime/media-query-filter.php`
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `docs/00-planning/decision-log.md`
  - `docs/20-development/admin-panel-map.md` (only if screen targeting contract changes)
- Integration impact:
  - WP admin list contexts: `upload.php`, `edit.php` (+ post_type variants)
- Regression scope required:
  - Media list/grid filter behavior
  - Posts/pages/products list-table filter behavior
  - DnD and assignment UX unaffected

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
  - Introduce/extend shared guard helpers in Media Folders query filter runtime so all query mutation callbacks use a centralized deterministic predicate.
  - Apply strict preconditions for list-query mutation (`pre_get_posts`) and media-grid mutation (`ajax_query_attachments_args`) with fail-open early returns.
  - Normalize/sanitize folder params consistently (`absint`, strict `'1'` for unassigned, `wp_unslash` where applicable).
  - Keep existing taxonomy resolver and enabled-post-type checks as authority source.
- Files likely impacted:
  - `includes/modules/media-folders/runtime/media-query-filter.php`
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `docs/00-planning/decision-log.md`
  - `docs/20-development/admin-panel-map.md` (conditional)
- Explicitly out-of-scope surfaces:
  - UI/UX assets, DnD behavior, column rendering, AJAX payload schemas.
  - caching logic and key naming.
  - storefront/frontend behavior.
- Risk analysis:
  - Medium: over-restrictive guards could suppress intended filtering.
  - Medium: under-restrictive guards could keep unintended side effects.
- ADR evaluation (REQUIRED / NOT REQUIRED):
  - NOT REQUIRED (no authority ownership change; hardening of existing runtime contract).

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
  - Existing `pre_get_posts` and `ajax_query_attachments_args` callbacks hardened (same hooks).
- AJAX endpoints expected:
  - None.
- Admin routes expected:
  - None.

Normative rule:
All expected runtime mutations MUST be declared before implementation.

## 3.1) Implementation Scope Lock

Confirm that the declared scope is complete.

- All files expected to change are listed? (Yes/No): Yes
- Hidden coupling risks discovered? (Yes/No): Yes
  - WordPress/third-party query execution paths vary by screen/action and may call hooks in non-obvious contexts.

Normative rules:

Implementation MUST NOT modify files outside the declared scope.

If new surfaces are discovered during development, the task MUST pause and the scope MUST be updated.

## Governance Impact Analysis
- Authority surfaces touched:
  - Media Folders runtime query guard layer only.
- Data integrity risk:
  - Low-Medium (incorrect guard could mis-filter or skip intended filter).
- Security surface changes:
  - Neutral-to-positive (stricter context gating).
- Runtime hook/order changes:
  - No hook additions/removals; logic hardening inside existing callbacks.
- Requires ADR? (Yes/No): No
- Risk register impact required? (Yes/No): No (hardening reduces existing risk; no new risk introduced expected).

Normative rules:
- Governance impact analysis is REQUIRED and BLOCKING.
- If authority ownership changes, ADR is REQUIRED before implementation.
- If risk posture changes, risk documentation update is REQUIRED.

## System Invariants Check
- Declared invariants that MUST remain true:
  - Folder isolation per content type unchanged.
  - Drag-handle position and single-item drag contracts unchanged.
  - Title-based drag ghost unchanged.
  - Existing AJAX contracts/payload shapes unchanged.
  - Existing caching keys/strategy unchanged.
- Any invariant at risk? (Yes/No): No
- Mitigation plan for invariant protection:
  - Hardening only in query filter file.
  - Guard-first early returns (fail-open) without touching UI/AJAX logic.

Normative rules:
- Invariants are binding constraints and MUST NOT be violated.
- If invariant safety is unclear, implementation MUST stop and escalate.

## Determinism Statement
- Input/output determinism declared? (Yes/No): Yes
- Ordering determinism declared? (Yes/No): Yes
- Retry determinism declared? (Yes/No): Yes
- Pagination/state convergence determinism declared? (Yes/No): Yes
- Determinism risks and controls:
  - Risk: context ambiguity causing non-deterministic query mutation.
  - Control: centralized shared guard helper with explicit allowlist checks and strict param presence requirement.

Normative rules:
- Determinism expectations are REQUIRED and BLOCKING.
- Non-deterministic behavior in critical flows MUST be escalated to governance review.
- If deterministic behavior cannot be guaranteed, implementation MUST NOT proceed.

## Testing Strategy

Describe how the implementation will be verified.

- Local testing plan:
  - Validate folder filters still apply on intended `upload.php`/`edit.php` main queries when params are present.
  - Validate no query mutation when folder params absent.
  - Validate no mutation in AJAX/non-screen contexts not intended (quick edit/modals/secondary queries).
- Edge cases expected:
  - `query-attachments` payload with missing/invalid custom vars.
  - list screen with enabled post type but no folder params.
- Failure scenarios considered:
  - false negative guards (filter not applied when should).
  - false positive guards (filter applied when should not).

Acceptance criteria:
1. Query filters mutate only when all strict admin/screen/main-query/post-type/param guards pass.
2. Query filters fail-open (no mutation) for secondary queries, missing params, unsupported contexts.
3. Existing Media Folders UX and data contracts remain unchanged.
4. Minimal performance evidence shows reduced unintended modifications and no extra query churn.

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
  - Impacted? (Yes/No): Conditional
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
  - Revert task commit(s).
  - Emergency no-op fallback: disable `bw_core_flags['media_folders']`.

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
