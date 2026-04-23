# Blackwork — GPT Handoff & Documentation Method

## Purpose
This document is the fastest professional handoff for a new GPT/Codex chat working inside the Blackwork repository.

It explains:
- how the documentation is structured
- how tasks are opened
- how implementation is governed
- how tasks are closed
- how documentation must be updated
- how to keep work traceable, deterministic, and production-safe

Use this file as the operational briefing before asking a new chat to analyze, implement, review, or document changes.

## What Blackwork Optimizes For
Blackwork does not treat code changes as isolated patches.
Every non-trivial task is governed by:
- explicit scope
- architectural boundaries
- determinism requirements
- documentation traceability
- risk visibility
- regression accountability

The goal is not just to "fix code", but to keep the system understandable, auditable, and stable over time.

## Documentation Structure
The `docs/` tree is layered on purpose.
Each layer answers a different question.

### `docs/00-overview/`
Use this first for orientation.

Key files:
- `system-map.md`
  - quick architecture entrypoint
  - repository map
  - runtime/admin entry points
  - mandatory governance entrypoints
- `domain-map.md`
  - authority boundaries
  - truth ownership
  - what each domain may or may not override

### `docs/00-governance/`
Use this for rules, risk posture, and mandatory process.

Key files:
- `ai-task-protocol.md`
  - mandatory lifecycle for AI work
- `risk-register.md`
  - authoritative list of active risks
- `risk-status-dashboard.md`
  - quick operational view of those risks

### `docs/00-planning/`
Use this for roadmap and planning intent.

Key files:
- `core-evolution-plan.md`
  - current roadmap
  - status of major initiatives
- `decision-log.md`
  - important planning and architecture decisions

### `docs/10-architecture/`
Use this for architectural contracts, runtime ownership maps, and system behavior boundaries.

### `docs/20-development/`
Use this for developer-facing technical maps, admin/system maps, maintenance notes, and lower-level implementation guidance.

### `docs/30-features/`
Use this for domain and feature documentation.
This is where component/widget/module behavior should be documented in practical terms.

### `docs/40-integrations/`
Use this for provider or external integration behavior.
Examples: Brevo, payments, auth, Supabase.

### `docs/50-ops/`
Use this for regression checks, release validation, audits, and runbooks.

Key files:
- `regression-protocol.md`
  - manual validation surfaces that must be re-tested when behavior changes
- `release-gate.md`
  - pre-production release validation

### `docs/60-adr/`
Use this when authority or architectural decisions need durable justification.

### `docs/tasks/`
Use this for task artifacts.
This is the traceability layer for:
- task starts
- task closures
- final validation summaries
- targeted audit reports

## Mandatory Working Method
All non-trivial work follows a governed lifecycle:

Task initialization  
→ Task Start  
→ Acceptance Gate  
→ Implementation  
→ Verification  
→ Documentation alignment  
→ Task Close  
→ Release Gate  
→ Deployment

This is not optional.
Implementation should not start before the task-start protocol is completed.

Primary source:
- `docs/00-governance/ai-task-protocol.md`

## How We Open Tasks
Before implementation, we create or update a task artifact in `docs/tasks/` using the start structure.

Canonical template:
- `docs/templates/task-start-template.md`

The task start is blocking and must define:
- task title and context
- scope and out-of-scope surfaces
- affected systems
- risk level and tier
- runtime surfaces expected to change
- invariants that must remain true
- determinism expectations
- testing strategy
- documentation layers likely impacted

Important rule:
- if scope changes during implementation, the task must pause and the scope must be updated

## Acceptance Gate Before Coding
No code should be written until the task start passes the acceptance gate.

Minimum gate items:
- task classification completed
- reading checklist completed
- scope declared
- implementation scope lock passed
- governance impact analysis completed
- invariants declared
- determinism declared
- documentation impact declared

If one of these is missing, implementation is considered premature.

## How We Implement
Implementation in Blackwork is intentionally conservative.

Core rules:
- work only inside declared scope
- avoid unrelated refactors
- preserve current behavior unless a change is required
- prefer low-risk, production-safe changes
- keep domain ownership clear
- do not create new truth surfaces casually
- treat determinism as a hard requirement in critical flows

Special caution:
- Supabase-related surfaces are governance-sensitive and must not be touched casually
- authority boundaries from `domain-map.md` must be respected

Repo execution rule:
- after every PHP change, run `php -l` on every modified PHP file
- after every task that modifies PHP, run `composer run lint:main`

This comes from the repository workflow instructions and is mandatory.

## How We Close Tasks
Task closure has an authoritative protocol.

Canonical source:
- `docs/governance/task-close.md`

Closure is not just "code is done".
A task is only considered closed when:
- implementation scope is complete
- required checks/regressions were executed
- documentation alignment is done
- governance artifacts are updated
- final status wording is consistent

Closure structure guidance:
- `docs/templates/task-closure-template.md`

## Mandatory Governance Updates at Closure
When risk posture or regression surfaces changed, task closure must update:
- `docs/00-governance/risk-register.md`
- `docs/00-governance/risk-status-dashboard.md`
- `docs/50-ops/regression-protocol.md`

This is mandatory.
A task is not fully closed until these are synchronized where relevant.

## How We Document Changes
We do not dump all changes into one file.
We update only the relevant documentation layer.

### Update `00-governance` when:
- a risk is added, mitigated, resolved, deferred, or reclassified
- governance posture changes
- a new launch blocker is identified

### Update `00-planning` when:
- roadmap status changes
- a component moves from backlog to in progress / almost ready / ready
- major initiatives or follow-up waves change

### Update `10-architecture` when:
- runtime ownership changes
- hook contracts or architectural flow change
- a reusable domain model or integration pattern becomes canonical

### Update `20-development` when:
- developer-facing technical maps or maintenance instructions change

### Update `30-features` when:
- a feature/widget/module behavior changes
- a component receives hardening, cleanup, or validation updates
- implementation details need to stay aligned with actual behavior

### Update `40-integrations` when:
- provider contracts, flows, or safeguards change

### Update `50-ops` when:
- manual validation or regression requirements change
- release gate or runbook behavior changes

### Update `60-adr` when:
- there is a durable architecture or authority decision that should outlive the task itself

### Update `docs/tasks/` when:
- opening a task
- closing a task
- publishing a final validation summary
- recording a focused audit or closure artifact

## Status Language We Use
We try to keep status wording consistent and objective.

Typical states:
- `Backlog`
- `In progress`
- `In review`
- `Almost ready`
- `Ready`
- `Completed`

We often pair them with:
- quality score
- current phase
- explicit manual validation gates

Example:
- `Status: Almost ready`
- `Quality: ~9/10`
- `Phase: Final manual validation`

## Launch Gate Mentality
We prefer explicit launch gates over vague optimism.

That means:
- if something is still pending, we name the exact manual validation
- if a component is almost ready, we say what must happen before it becomes ready
- if risks remain, we place them in the risk register/dashboard instead of hiding them in prose

Good closure language is objective:
- what is done
- what is blocked
- what is non-blocking
- what must be validated before production

## Professional Documentation Style
Our documentation style is:
- concise
- specific
- technical
- layered
- traceable
- non-redundant

Preferred behavior:
- update only relevant sections
- avoid rewriting entire files unnecessarily
- do not duplicate large blocks across multiple docs
- keep one “source of truth” per task when possible
- use other docs to summarize and link, not to copy-paste everything

## Recommended Reading Order for a New Chat
If a new GPT chat needs to work on Blackwork professionally, the best starting order is:

1. `docs/00-overview/system-map.md`
2. `docs/00-overview/domain-map.md`
3. `docs/00-governance/ai-task-protocol.md`
4. `docs/templates/task-start-template.md`
5. `docs/governance/task-close.md`
6. `docs/00-governance/risk-register.md`
7. `docs/00-governance/risk-status-dashboard.md`
8. `docs/00-planning/core-evolution-plan.md`
9. the specific feature/integration docs for the task domain
10. the relevant task artifacts already present in `docs/tasks/`

## Recommended Prompt for a New GPT Chat
You can pass a new chat something like this:

```text
Work inside this repository using the Blackwork governance method.

Before implementing anything:
- read docs/00-overview/system-map.md
- read docs/00-overview/domain-map.md
- read docs/00-governance/ai-task-protocol.md
- follow docs/templates/task-start-template.md before coding
- follow docs/governance/task-close.md before considering the task closed

Important working rules:
- respect declared scope strictly
- preserve current behavior unless change is required
- avoid unrelated refactors
- keep documentation aligned with real implementation
- update risk-register, risk-status-dashboard, and regression-protocol when relevant
- after every PHP change run php -l on each modified PHP file
- after every task that modifies PHP run composer run lint:main

Use the relevant feature/integration docs as the domain source of truth, and keep status wording consistent across planning, governance, feature docs, and task artifacts.
```

## Final Principle
In Blackwork, professional work means:
- clear scope before coding
- clean implementation inside boundaries
- explicit verification
- exact documentation alignment
- objective launch readiness

If code changes but docs, risks, regressions, and closure artifacts are not aligned, the work is not truly finished.
