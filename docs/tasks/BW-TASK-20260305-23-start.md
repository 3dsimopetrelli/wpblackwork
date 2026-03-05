# Blackwork Governance — Task Start Template

## Context
- Task title: Media Folders multi-post-type: performance & scale hardening
- Request source: User request in current thread
- Expected outcome:
  - Performance audit + hardening of Media Folders for large datasets (10k+ posts/products, 50k+ media, many folders).
  - Responsive folder tree/counts and list-table filtering across Media/Posts/Pages/Products.
  - Behavior unchanged (no UX regression, no contract drift).
- Constraints:
  - No storefront/runtime frontend changes.
  - Preserve existing security invariants (nonce/capability/context validation).
  - Prefer batched aggregation, transients/object cache, and deterministic invalidation.
  - Avoid per-row/per-term heavy queries in hot paths.

Normative rules:
- Context fields are REQUIRED.
- Implementation MUST NOT begin with missing context.

## Task Classification
- Domain: Media Folders / Admin Runtime / Performance Hardening
- Incident/Task type: Hardening + optimization (no feature expansion)
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `includes/modules/media-folders/runtime/*`
  - `includes/modules/media-folders/admin/*`
  - `includes/modules/media-folders/data/*` (only if cache/invalidation hooks require)
  - `docs/30-features/media-folders/*`
  - `docs/20-development/admin-panel-map.md`
  - `docs/00-planning/decision-log.md`
  - `docs/00-governance/risk-register.md` (if new risk discovered)
- Integration impact:
  - Admin screens only:
    - `upload.php`
    - `edit.php`
    - `edit.php?post_type=page`
    - `edit.php?post_type=product`
- Regression scope required:
  - Media grid/list filters and assignment.
  - Folder tree render/counts and context switching.
  - List-table performance and filter responsiveness.

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
  - `docs/50-ops/runtime-hook-map.md` (as available, for surface awareness)
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
  - Baseline current hot paths with lightweight instrumentation (query count/time and endpoint timings) on admin-only surfaces.
  - Harden counts pipeline per taxonomy via cache-first deterministic strategy and strict invalidation on term/object mutations.
  - Reduce list-table filter cost by tightening guards, minimizing heavy joins, and ensuring tax-query merge remains minimal/fail-open.
  - Harden assign endpoint batching and avoid repeated expensive operations in a single request path.
  - Keep UI/UX behavior unchanged; optional lazy tree/count loading only if transparent and contract-equivalent.
- Files likely impacted:
  - `includes/modules/media-folders/runtime/ajax.php`
  - `includes/modules/media-folders/runtime/media-query-filter.php`
  - `includes/modules/media-folders/admin/media-folders-admin.php`
  - `includes/modules/media-folders/admin/assets/media-folders.js`
  - `includes/modules/media-folders/data/taxonomy.php` (only if invalidation hook placement requires)
  - `includes/modules/media-folders/data/term-meta.php` (only if invalidation hook placement requires)
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `docs/20-development/admin-panel-map.md`
  - `docs/00-planning/decision-log.md`
  - `docs/00-governance/risk-register.md` (conditional)
- Explicitly out-of-scope surfaces:
  - Frontend/storefront behavior.
  - New end-user feature additions.
  - Option key/schema redesign.
  - Non media-folders modules (checkout, widgets, Woo runtime outside admin list table behavior).
- Risk analysis:
  - Medium: stale cache/invalidation bugs causing incorrect counts.
  - Medium: over-optimization regressing filter correctness.
  - Low-Medium: admin selector drift if lazy loading logic is introduced.
- ADR evaluation (REQUIRED / NOT REQUIRED):
  - NOT REQUIRED (no authority ownership change expected).

Normative rules:
- Scope declaration is REQUIRED and BLOCKING.
- All target files MUST be declared before implementation.
- Out-of-scope boundaries MUST be explicit.

## Runtime Surface Declaration

Declare expected runtime surfaces affected.

- New hooks expected:
  - Possibly cache invalidation hooks on term/object relationship updates (admin/runtime scope).
- Hook priority modifications:
  - None expected unless required for deterministic invalidation ordering.
- Filters expected:
  - Existing list/grid query filters may be hardened (guards/early returns only).
- AJAX endpoints expected:
  - Existing endpoints only (`bw_media_get_folder_counts`, `bw_media_assign_folder`, marker/count endpoints) with optimized internals.
- Admin routes expected:
  - None new.

Normative rule:
All expected runtime mutations MUST be declared before implementation.

## 3.1) Implementation Scope Lock

Confirm that the declared scope is complete.

- All files expected to change are listed? (Yes/No): Yes
- Hidden coupling risks discovered? (Yes/No): Yes
  - Counts pipeline and filter runtime are shared across Media/Post/Page/Product contexts.
  - Observer-driven JS refresh paths can amplify backend calls if not coalesced.

Normative rules:

Implementation MUST NOT modify files outside the declared scope.

If new surfaces are discovered during development, the task MUST pause and the scope MUST be updated.

## Governance Impact Analysis
- Authority surfaces touched:
  - Media Folders admin runtime only.
- Data integrity risk:
  - Medium (incorrect cache invalidation may desync displayed counts).
- Security surface changes:
  - None expected; existing nonce/capability checks remain mandatory.
- Runtime hook/order changes:
  - Possible limited invalidation hooks; query hooks unchanged in authority.
- Requires ADR? (Yes/No): No
- Risk register impact required? (Yes/No): Conditional (Yes if new unresolved perf/data-integrity risk is introduced or elevated).

Normative rules:
- Governance impact analysis is REQUIRED and BLOCKING.
- If authority ownership changes, ADR is REQUIRED before implementation.
- If risk posture changes, risk documentation update is REQUIRED.

## System Invariants Check
- Declared invariants that MUST remain true:
  - `bw_core_flags['media_folders']=0` => module no-op.
  - Folder isolation per content type remains strict.
  - Drag/list UX contracts unchanged.
  - Security checks (nonce/capability/context) unchanged or stronger.
  - Media behavior remains backward-compatible.
- Any invariant at risk? (Yes/No): No
- Mitigation plan for invariant protection:
  - Keep API contracts stable; optimize internals only.
  - Add fail-open guards for cache miss/error paths.
  - Invalidation map explicitly tied to taxonomy/post-type resolver.

Normative rules:
- Invariants are binding constraints and MUST NOT be violated.
- If invariant safety is unclear, implementation MUST stop and escalate.

## Determinism Statement
- Input/output determinism declared? (Yes/No): Yes
- Ordering determinism declared? (Yes/No): Yes
- Retry determinism declared? (Yes/No): Yes
- Pagination/state convergence determinism declared? (Yes/No): Yes
- Determinism risks and controls:
  - Risk: stale/transient cache divergence across contexts.
  - Control: deterministic cache keys namespaced by taxonomy + scope; explicit invalidation events.
  - Risk: racing AJAX refreshes causing inconsistent counts.
  - Control: single-flight/coalesced refresh scheduling and idempotent apply paths.

Normative rules:
- Determinism expectations are REQUIRED and BLOCKING.
- Non-deterministic behavior in critical flows MUST be escalated to governance review.
- If deterministic behavior cannot be guaranteed, implementation MUST NOT proceed.

## Testing Strategy

Describe how the implementation will be verified.

- Local testing plan:
  - Baseline vs hardened timings for:
    - folder tree/count fetch,
    - list-table filtered queries,
    - assign endpoint processing.
  - Verify zero UX behavior changes on Media/Post/Page/Product screens.
  - Verify cache hit behavior after first load and correctness after invalidation-triggering actions.
- Edge cases expected:
  - Large folder trees with deep hierarchy.
  - Large page/list datasets with filters/search/pagination.
  - Repeated assign/unassign bursts.
- Failure scenarios considered:
  - transient/object cache unavailable.
  - invalidation missed after term/object mutation.
  - concurrent AJAX refresh storms.

Acceptance criteria (measurable hardening):
1. Folder counts endpoint/tree load performs no per-term query loops in hot path (batched aggregation or cache hit path in steady state).
2. Cache hit path is active after first request and invalidates deterministically after assign/create/rename/delete/pin/color changes.
3. List-table filtering (`edit.php`, `page`, `product`) remains functionally identical and avoids unnecessary query mutations when no folder params are set.
4. Assignment endpoint keeps existing behavior and stays within existing batch constraints while reducing repeated expensive operations.
5. No contract or UX regressions across Media/Post/Page/Product admin screens.

Normative rule:
Critical flows MUST have explicit testing strategy.

## Documentation Update Plan
Documentation layers that MUST be considered before implementation:

- `docs/00-governance/`
  - Impacted? (Yes/No): Conditional
  - Target documents (if known): `docs/00-governance/risk-register.md` (if risk posture changes)
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
  - Impacted? (Yes/No): Conditional
  - Target documents (if known): runtime/hook notes only if hook surface changes
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
  - Immediate fallback: set `bw_core_flags['media_folders']=0`.
  - Full rollback via commit revert.

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
