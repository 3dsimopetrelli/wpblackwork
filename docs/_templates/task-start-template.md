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

## Implementation Scope Lock
- Confirm all target files are declared? (Yes/No)
- Any potential hidden coupling discovered during reading? (Yes/No)
- If Yes, list coupling surfaces:

Normative rules:
- Implementation MUST NOT modify files outside declared scope.
- Discovery of additional surfaces MUST pause the task and trigger scope review.
- Scope expansion without review is PROHIBITED.

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

## Documentation Alignment Requirement
Before implementation begins, documentation alignment obligations MUST be declared.

The following documentation layers MUST be reviewed and aligned if implementation changes behavior:
- Governance layer (`docs/00-governance/`)
- Planning layer (`docs/00-planning/`)
- Architecture layer (`docs/10-architecture/`)
- Development rules (`docs/20-development/`)
- Feature documentation (`docs/30-features/`)
- Integration documentation (`docs/40-integrations/`)
- Operations and runbooks (`docs/50-ops/`)
- ADR records (`docs/60-adr/`)
- System integration maps (`docs/60-system/`)

Normative rules:
- Documentation MUST remain consistent with implementation.
- Behavioral changes REQUIRE documentation updates.
- Documentation alignment planning is REQUIRED before coding begins.

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

Implementation MUST NOT begin until this template is fully completed.

Any violation of declared scope, governance rules, or invariants MUST stop the task immediately.

All AI agents operating in this repository MUST treat this template as a binding governance protocol.
