# Blackwork Governance — Task Start Template

## Context
- Task title: Admin UX polish for Products list table when Media Folders sidebar is active
- Request source: User request in current thread
- Expected outcome:
  - Products list table (`edit.php?post_type=product`) readability improvements when Media Folders is active.
  - Narrow/fixed drag-handle and checkbox columns.
  - Larger square product thumbnail in admin list table (default 200x200, tweakable).
  - No behavior regression in existing Media Folders contracts.
- Constraints:
  - UI/UX scope only for admin Products list table.
  - Must be active only when Media Folders is enabled for products.
  - No AJAX contract changes.
  - No storefront changes.

Normative rules:
- Context fields are REQUIRED.
- Implementation MUST NOT begin with missing context.

## Task Classification
- Domain: Media Folders / Admin UX (Products list table)
- Incident/Task type: UX polish (scoped feature hardening)
- Risk level (L1/L2/L3): L1
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `includes/modules/media-folders/admin/media-folders-admin.php`
  - `includes/modules/media-folders/admin/assets/media-folders.css`
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `docs/20-development/admin-panel-map.md`
- Integration impact:
  - Admin-only Woo Products list table screen: `edit.php?post_type=product`
- Regression scope required:
  - Drag-handle column placement/width
  - Checkbox column width
  - Product thumbnail rendering size
  - Existing single-item drag + title ghost behavior

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
  - `docs/50-ops/runtime-hook-map.md` (awareness only)
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
  - Keep existing Media Folders assets; add product-screen scoped CSS for drag-handle/checkbox columns and thumbnail display.
  - Add admin-side thumbnail-size resolver helper + Woo filter hooks (best-effort compatibility with multiple Woo filter names), guarded to product list screen + Media Folders products enabled.
  - Provide default 200 with override path via constant/filter.
  - Preserve all DnD/assignment logic and contracts unchanged.
- Files likely impacted:
  - `includes/modules/media-folders/admin/media-folders-admin.php`
  - `includes/modules/media-folders/admin/assets/media-folders.css`
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `docs/20-development/admin-panel-map.md`
- Explicitly out-of-scope surfaces:
  - Any runtime/AJAX logic changes
  - Any storefront/frontend rendering
  - Media/Post/Page list screen UX changes
- Risk analysis:
  - Low: Woo filter name compatibility variance by version.
  - Low: CSS could leak if selector scoping is weak.
- ADR evaluation (REQUIRED / NOT REQUIRED):
  - NOT REQUIRED (no authority ownership or architecture truth-surface change).

Normative rules:
- Scope declaration is REQUIRED and BLOCKING.
- All target files MUST be declared before implementation.
- Out-of-scope boundaries MUST be explicit.

## Runtime Surface Declaration

Declare expected runtime surfaces affected.

- New hooks expected:
  - Woo admin thumbnail size filter callback(s) in Media Folders admin module.
- Hook priority modifications:
  - None expected.
- Filters expected:
  - WooCommerce admin product thumbnail size filter(s), guarded by product-list screen context.
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
  - WooCommerce version may expose different thumbnail size filter names.

Normative rules:

Implementation MUST NOT modify files outside the declared scope.

If new surfaces are discovered during development, the task MUST pause and the scope MUST be updated.

## Governance Impact Analysis
- Authority surfaces touched:
  - Media Folders admin UX only (Products screen).
- Data integrity risk:
  - Low.
- Security surface changes:
  - None.
- Runtime hook/order changes:
  - Adds guarded Woo thumbnail filter callback(s); no privilege changes.
- Requires ADR? (Yes/No): No
- Risk register impact required? (Yes/No): No

Normative rules:
- Governance impact analysis is REQUIRED and BLOCKING.
- If authority ownership changes, ADR is REQUIRED before implementation.
- If risk posture changes, risk documentation update is REQUIRED.

## System Invariants Check
- Declared invariants that MUST remain true:
  - Folder isolation per content type unchanged.
  - Drag-handle column remains before Product Name.
  - Products drag remains single-item only.
  - Drag ghost remains title-based.
  - No changes to Media Folders AJAX contracts.
- Any invariant at risk? (Yes/No): No
- Mitigation plan for invariant protection:
  - Restrict changes to CSS + thumbnail sizing filter path only.
  - Do not touch DnD JS behavior.
  - Guard product-only screen and product-flag enablement.

Normative rules:
- Invariants are binding constraints and MUST NOT be violated.
- If invariant safety is unclear, implementation MUST stop and escalate.

## Determinism Statement
- Input/output determinism declared? (Yes/No): Yes
- Ordering determinism declared? (Yes/No): Yes
- Retry determinism declared? (Yes/No): Yes
- Pagination/state convergence determinism declared? (Yes/No): Yes
- Determinism risks and controls:
  - Risk: Woo filter callback runs outside target screen.
  - Control: strict screen + post_type + enabled-flag guards; fallback returns original size unchanged.

Normative rules:
- Determinism expectations are REQUIRED and BLOCKING.
- Non-deterministic behavior in critical flows MUST be escalated to governance review.
- If deterministic behavior cannot be guaranteed, implementation MUST NOT proceed.

## Testing Strategy

Describe how the implementation will be verified.

- Local testing plan:
  - On `edit.php?post_type=product` with Media Folders products enabled:
    - drag-handle column visually minimal width
    - checkbox column visually minimal width
    - product thumb visibly large square (200x200 default)
  - Toggle products flag off: no Media Folders product UX/CSS effect.
  - Verify posts/pages/media screens unchanged.
- Edge cases expected:
  - Woo versions with/without specific thumbnail-size filter name.
  - Screen notices/header variations in products page.
- Failure scenarios considered:
  - Woo filter unavailable (fallback to CSS display sizing only).

Acceptance criteria:
1. Product list drag-handle and checkbox columns are reduced to minimal fixed widths.
2. Product thumbnail target size defaults to 200x200 and is easy to override.
3. Changes apply only to `edit.php?post_type=product` when Media Folders products support is enabled.
4. Existing Media Folders UX contracts remain unchanged.

Normative rule:
Critical flows MUST have explicit testing strategy.

## Documentation Update Plan
Documentation layers that MUST be considered before implementation:

- `docs/00-governance/`
  - Impacted? (Yes/No): No
  - Target documents (if known): N/A
- `docs/00-planning/`
  - Impacted? (Yes/No): No
  - Target documents (if known): N/A
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
  - Revert task commit(s)
  - Immediate runtime fallback: disable `media_folders_use_products`

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
