# Blackwork Governance — Task Start Template

This template defines the mandatory governance protocol that MUST be followed before any implementation task begins in the Blackwork repository.  
All AI agents and contributors MUST complete this template before starting implementation.

## Context
- Task title:
- Request source:
- Expected outcome:
- Constraints:

Normative rules:
- Context fields are REQUIRED.
- Implementation MUST NOT begin with missing context.

## Task Classification
- Domain:
- Incident/Task type:
- Risk level (L1/L2/L3):
- Tier classification (0/1/2/3):
- Affected systems:
- Integration impact:
- Regression scope required:

Normative rules:
- Task classification is REQUIRED and BLOCKING.
- Tier 0 authority-impacting tasks MUST trigger governance escalation before implementation.
- Misclassified tasks MUST be corrected before implementation.

## Pre-Task Reading Checklist
- Feature docs to read:
- Integration docs to read:
- Ops/control docs to read:
- Governance docs to read:
- Runbook to follow:
- Architecture references to read:

Normative rules:
- Reading checklist completion is REQUIRED.
- Implementation MUST NOT begin without declaring required references.

## Scope Declaration
- Proposed strategy:
- Files likely impacted:
- Explicitly out-of-scope surfaces:
- Risk analysis:
- ADR evaluation (REQUIRED / NOT REQUIRED):

Normative rules:
- Scope declaration is REQUIRED and BLOCKING.
- All target files MUST be declared before implementation.
- Out-of-scope boundaries MUST be explicit.

## Runtime Surface Declaration

Declare expected runtime surfaces affected.

- New hooks expected:
- Hook priority modifications:
- Filters expected:
- AJAX endpoints expected:
- Admin routes expected:

Normative rule:
All expected runtime mutations MUST be declared before implementation.

## 3.1) Implementation Scope Lock

Confirm that the declared scope is complete.

- All files expected to change are listed? (Yes/No)
- Hidden coupling risks discovered? (Yes/No)

Normative rules:

Implementation MUST NOT modify files outside the declared scope.

If new surfaces are discovered during development, the task MUST pause and the scope MUST be updated.

## Governance Impact Analysis
- Authority surfaces touched:
- Data integrity risk:
- Security surface changes:
- Runtime hook/order changes:
- Requires ADR? (Yes/No)
- Risk register impact required? (Yes/No)

Normative rules:
- Governance impact analysis is REQUIRED and BLOCKING.
- If authority ownership changes, ADR is REQUIRED before implementation.
- If risk posture changes, risk documentation update is REQUIRED.

## System Invariants Check
- Declared invariants that MUST remain true:
- Any invariant at risk? (Yes/No)
- Mitigation plan for invariant protection:

Normative rules:
- Invariants are binding constraints and MUST NOT be violated.
- If invariant safety is unclear, implementation MUST stop and escalate.

## Determinism Statement
- Input/output determinism declared? (Yes/No)
- Ordering determinism declared? (Yes/No)
- Retry determinism declared? (Yes/No)
- Pagination/state convergence determinism declared? (Yes/No)
- Determinism risks and controls:

Normative rules:
- Determinism expectations are REQUIRED and BLOCKING.
- Non-deterministic behavior in critical flows MUST be escalated to governance review.
- If deterministic behavior cannot be guaranteed, implementation MUST NOT proceed.

## Testing Strategy

Describe how the implementation will be verified.

- Local testing plan:
- Edge cases expected:
- Failure scenarios considered:

Normative rule:
Critical flows MUST have explicit testing strategy.

## Documentation Update Plan
Documentation layers that MUST be considered before implementation:

- `docs/00-governance/`
  - Impacted? (Yes/No)
  - Target documents (if known):
- `docs/00-planning/`
  - Impacted? (Yes/No)
  - Target documents (if known):
- `docs/10-architecture/`
  - Impacted? (Yes/No)
  - Target documents (if known):
- `docs/20-development/`
  - Impacted? (Yes/No)
  - Target documents (if known):
- `docs/30-features/`
  - Impacted? (Yes/No)
  - Target documents (if known):
- `docs/40-integrations/`
  - Impacted? (Yes/No)
  - Target documents (if known):
- `docs/50-ops/`
  - Impacted? (Yes/No)
  - Target documents (if known):
- `docs/60-adr/`
  - Impacted? (Yes/No)
  - Target documents (if known):
- `docs/60-system/`
  - Impacted? (Yes/No)
  - Target documents (if known):

Normative rules:
- Documentation impact declaration is REQUIRED and BLOCKING.
- Implementation MUST NOT begin unless this declaration is completed.
- If behavior changes are expected, documentation targets MUST be declared before coding.

## Rollback Strategy

Describe rollback feasibility.

- Revert via commit possible? (Yes/No)
- Database migration involved? (Yes/No)
- Manual rollback steps required?

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
- Task Classification completed? (Yes/No)
- Pre-Task Reading Checklist completed? (Yes/No)
- Scope Declaration completed? (Yes/No)
- Implementation Scope Lock passed? (Yes/No)
- Governance Impact Analysis completed? (Yes/No)
- System Invariants Check completed? (Yes/No)
- Determinism Statement completed? (Yes/No)
- Documentation Update Plan completed? (Yes/No)
- Documentation Alignment Requirement completed? (Yes/No)

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
