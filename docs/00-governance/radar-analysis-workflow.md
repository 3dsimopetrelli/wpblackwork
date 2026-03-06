# Radar Findings Intake & Classification Workflow

## 1. Purpose
This workflow standardizes how radar/audit findings are ingested and routed in Blackwork governance.

Why it exists:
- Prevent ad-hoc handling of findings.
- Keep governance traceable and deterministic across chats/agents.
- Ensure findings are stored in existing governance containers, not scattered into random new files.

Rule:
- Radar findings must be classified first, then routed into existing documentation layers.

## 2. Core Principle
Radar does **not** create a parallel documentation system.

Radar output is discovery input only:
- identify issue
- classify issue
- route issue to an existing governance container

No standalone “radar universe” is allowed when current governance files already provide the destination.

## 3. The Existing Governance Containers
- Real governance risks  
  Destination: `docs/00-governance/risk-register.md`
- Standing operational rules / normative decisions  
  Destination: `docs/00-planning/decision-log.md` or ADR in `docs/60-adr/` (when authority-surface/normative architecture change requires it)
- Implemented fixes (task evidence)  
  Destination: `docs/tasks/BW-TASK-XXXX-closure.md`
- Technical bugs / future technical debt (not elevated to governance risk)  
  Destination: `docs/00-planning/core-evolution-plan.md`

## 4. End-to-End Flow
```text
Radar / Audit
    ↓
Analysis
    ↓
Classification
    ↓
Destination Decision
    ↓
Risk Register / Decision Log / Core Evolution Plan / Task Closure
```

## 5. Classification Table
| Classification | Meaning | Destination file | Implementation task required? |
|---|---|---|---|
| `VALID NEW RISK` | New governance-level threat not already tracked | `docs/00-governance/risk-register.md` | Usually yes (now/next based on severity) |
| `EXISTING RISK UPDATE` | Valid finding already covered by existing risk | `docs/00-governance/risk-register.md` (update existing entry) | Usually yes if mitigation gap remains |
| `MEDIUM BUG` | Real technical issue below governance-risk threshold | `docs/00-planning/core-evolution-plan.md` (or immediate task if prioritized) | Optional; required only when scheduled |
| `FALSE POSITIVE` | Not a real issue in current context/contract | Normally no repository change | No |
| `NEEDS CONTEXT` | Insufficient evidence or environment context to classify safely | Manual review note (then route after validation) | Blocked until context is resolved |

## 6. Storage Rules
- Do not create a new “radar backlog” file when existing governance files already cover the destination.
- Findings must be stored in the correct existing file by classification.
- False positives normally require no repository change.
- Medium bugs go to `core-evolution-plan.md` unless immediately promoted into a governed task.
- If a finding introduces a new standing operational rule, update `decision-log.md`; if architecture authority changes, escalate to ADR.

## 7. Real Examples From Blackwork
- SVG upload without sanitization  
  Route: new risk in risk register (`R-SEC-22`) -> governed implementation task -> closure artifact.
- Import bulk without durable state/checkpoint authority  
  Route: existing import risk update (`R-IMP-10`) -> Tier-0 hardening task.
- FPW transient key collision risk  
  Route: medium bug -> governed task execution -> closure evidence.
- Duplicate risk ID `R-MF-01`  
  Route: governance/docs cleanup (traceability fix), not runtime hardening.
- Supabase bridge globally loaded on anonymous pages  
  Route: medium bug -> `core-evolution-plan.md` backlog item.
- Dual authority `_v1/_v2` rules in Theme Builder Lite  
  Route: new governance risk (`R-TBL-18`) in risk register.

## 8. Relationship With AI Task Protocol
- `docs/00-governance/ai-task-protocol.md` governs **how tasks are executed** (start, gate, implementation, closure, release gate).
- This workflow governs **how findings are classified and routed before becoming tasks**.
- They are complementary:
  - Radar workflow decides *what* and *where*.
  - AI task protocol decides *how* to execute once a task exists.

## 9. Operational Rule For New Chats
At the start of any new chat handling audit/radar findings, this document should be provided (or explicitly referenced) so the assistant routes findings into the correct existing governance containers from the first pass.

## 10. Quick Checklist
- Is this a real risk?
- Is it already covered by an existing risk?
- Is it a technical bug instead of a governance risk?
- Does it require a standing-rule decision (`decision-log`) or ADR?
- Does it need a task now, next, or only backlog placement?
