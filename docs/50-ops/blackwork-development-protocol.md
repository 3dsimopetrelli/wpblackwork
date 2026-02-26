# Blackwork Development & Maintenance Protocol

## Purpose
This protocol defines the mandatory workflow for any task in Blackwork:
- Bug fix
- Refactor
- Integration update
- UX change
- Feature evolution

No task is allowed outside this protocol.

## Phase 1 — Classification
Before touching code:
- Use `incident-classification.md`
- Use `maintenance-decision-matrix.md`
- Identify:
  - Domain
  - Risk level
  - Impacted systems
  - Regression scope

Output required:
- Written action plan

No implementation allowed in this phase.

## Phase 2 — Controlled Implementation
Rules:
- Follow `maintenance-workflow.md`
- Follow relevant domain runbook
- Preserve architecture boundaries
- Do not modify unrelated domains
- Flag architectural deviation

Output required:
- Files modified
- Scope summary

## Phase 3 — Regression Validation
Mandatory for:
- Level 1
- Level 2

Includes:
- Global regression protocol
- Domain-specific checklist

No release allowed without regression confirmation.

## Phase 4 — Documentation Synchronization
Based on `maintenance-decision-matrix.md`:

Update:
- `CHANGELOG.md`
- Feature docs (if required)
- Runbook (if knowledge improved)
- ADR (if architecture changed)

Maintenance is NOT complete until documentation is aligned.

## Golden Rules
- No direct fix without classification.
- No merge without regression.
- No change without documentation sync.
- Architecture boundaries must be preserved.
