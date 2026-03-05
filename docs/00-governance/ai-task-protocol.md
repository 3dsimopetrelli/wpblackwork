# Blackwork AI Task Protocol

This document defines the mandatory operational protocol that ALL AI agents
must follow when working inside the Blackwork repository.

All implementation work MUST follow the governance-controlled task lifecycle.

AI agents MUST treat this document as the authoritative entry point.

## 0) Task Initialization

All work MUST begin with a governed task.

AI agents MUST:

1. Create or receive a task description
2. Complete the Task Start Template
3. Validate the Acceptance Gate
4. Only then begin implementation

Normative rule:

Implementation MUST NOT begin without a completed Task Start Template.

## 1) Mandatory Task Lifecycle

All implementation work MUST follow this lifecycle:

Task Initialization  
→ Task Start Template completion  
→ Implementation  
→ Verification  
→ Documentation alignment  
→ Task Closure Template completion

Normative rule:

AI agents MUST NOT implement code outside a governed task lifecycle.

## 2) Required Templates

Before starting implementation, the following template MUST be completed:

`docs/templates/task-start-template.md`

Before closing a task, the following template MUST be completed:

`docs/templates/task-closure-template.md`

Normative rules:

- Implementation MUST NOT begin until the Task Start Template is completed.
- A task MUST NOT be considered finished until the Task Closure Template is completed.

## 3) Mandatory Documentation Alignment

All AI agents MUST verify documentation impact before implementation.

The following documentation layers MUST be evaluated:

`docs/00-governance/`  
`docs/00-planning/`  
`docs/10-architecture/`  
`docs/20-development/`  
`docs/30-features/`  
`docs/40-integrations/`  
`docs/50-ops/`  
`docs/60-adr/`  
`docs/60-system/`

Normative rule:

If system behavior changes, the corresponding documentation MUST be updated.

## 4) Governance Compliance

AI agents MUST respect the following governance artifacts:

Risk Register  
`docs/00-governance/risk-register.md`

Decision Log  
`docs/00-planning/decision-log.md`

Core Evolution Plan  
`docs/00-planning/core-evolution-plan.md`

Normative rule:

Changes that affect system behavior, authority ownership, or risk posture
MUST update the corresponding governance documents.

## 5) Determinism Requirement

All implementations MUST respect system determinism guarantees.

AI agents MUST explicitly verify:

- Input/output determinism
- Ordering determinism
- Retry determinism
- State convergence determinism

Normative rule:

Non-deterministic behavior in critical system flows MUST NOT be introduced.

## 6) Scope Discipline

Implementation MUST respect declared task scope.

AI agents MUST NOT:

- modify files outside the declared scope
- introduce hidden coupling
- mutate runtime surfaces without declaration

Normative rule:

If additional surfaces are discovered during implementation,
the task MUST pause and the scope MUST be updated.

## 7) Abort Conditions

AI agents MUST immediately stop implementation if any of the following occurs:

- Scope drift detected
- Authority ownership unclear
- System invariants at risk
- Determinism cannot be guaranteed
- Required documentation alignment cannot be completed
- ADR required but not approved

## 8) Required Reading Before Implementation

Before implementing code, AI agents MUST read:

`docs/templates/task-start-template.md`  
`docs/templates/task-closure-template.md`

AI agents MUST also review the following documentation layers
when they are relevant to the task:

`docs/00-governance/`  
`docs/10-architecture/`  
`docs/20-development/`

Normative rule:

AI agents MUST understand system governance before modifying the system.

## 9) Governance Enforcement Rule

This protocol is mandatory for all AI agents operating inside the repository.

AI agents MUST treat:

`docs/templates/task-start-template.md`  
`docs/templates/task-closure-template.md`

as binding governance documents.

Any implementation performed outside this protocol
is considered a governance violation.

If this repository contains a `docs/` directory,
AI agents MUST treat it as the authoritative system documentation.
