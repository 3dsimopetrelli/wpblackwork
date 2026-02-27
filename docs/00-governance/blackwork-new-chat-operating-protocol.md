# Blackwork --- New Chat Operating Protocol

## Purpose

This document defines how a new ChatGPT session must behave when
starting a new task inside the Blackwork governance system.

The objective is: - Deterministic task handling - Governance-aware
execution - Clean documentation lifecycle - Controlled interaction
between ChatGPT and Codex - Proper use of Start and Close templates

This protocol MUST be followed in every new chat.

------------------------------------------------------------------------

# 1. Chat Initialization Behavior

When a new task begins, ChatGPT MUST:

1.  Ask the user to clearly describe:
    -   The objective
    -   The domain affected
    -   Whether code or documentation is involved
    -   Whether existing files are impacted
    -   The desired outcome
2.  Classify the task:
    -   Tier (0--3)
    -   Authority surface touched? (Yes/No)
    -   Data integrity risk (Low/Medium/High/Critical)
    -   ADR potentially required?
3.  Generate a completed `task-start-template.md` instance based on the
    user's description.

ChatGPT MUST NOT: - Skip classification - Start proposing solutions
before the task-start template is produced - Ignore governance structure

------------------------------------------------------------------------

# 2. Interaction Loop Between ChatGPT and Codex

After the Start Template is generated:

1.  The user copies it into Codex.

2.  Codex evaluates scope and may:

    -   Confirm direction
    -   Suggest structural adjustments
    -   Raise warnings
    -   Request clarifications

3.  The user pastes Codex feedback back into ChatGPT.

4.  ChatGPT:

    -   Analyzes Codex output
    -   Proposes optimal structural direction
    -   Refines strategy
    -   May update start template if necessary

This loop continues until: - Architecture is clean - Risk is
understood - Scope is clear - Determinism is preserved

Only then implementation proceeds.

------------------------------------------------------------------------

# 3. During Task Execution

ChatGPT MUST:

-   Maintain scope discipline
-   Respect declared surfaces
-   Protect authority boundaries
-   Avoid undeclared coupling
-   Keep determinism guarantees intact
-   Track documentation updates required

If governance drift is detected: - STOP - Escalate to ADR if needed

------------------------------------------------------------------------

# 4. Task Completion Protocol

A task is considered complete ONLY when:

1.  ChatGPT confirms:
    -   Acceptance criteria satisfied
    -   Determinism preserved
    -   No authority drift
    -   No undeclared surface modifications
    -   Regression surfaces verified
2.  Codex confirms:
    -   Implementation consistent
    -   No structural violations
    -   No unexpected changes

At that point:

ChatGPT MUST generate a completed `task-close-template.md` instance.

The user then: - Pastes it into Codex - Codex updates documentation
accordingly

------------------------------------------------------------------------

# 5. Documentation Awareness Rules

Every new chat MUST assume the following structure exists:

-   ADR system (`docs/60-adr/`)
-   Authority Matrix
-   Runtime Hook Map
-   Domain Map
-   Core Evolution Plan
-   Decision Log
-   Regression Protocol
-   Tier classification system
-   Invariant protection layer

ChatGPT MUST always consider:

-   Does this change authority ownership?
-   Does this introduce a new truth surface?
-   Does this weaken invariants?
-   Does this affect Tier 0 state?
-   Does this require roadmap update?
-   Does this require decision log entry?

------------------------------------------------------------------------

# 6. Non-Negotiable Principles

-   Determinism over cleverness
-   Authority clarity over convenience
-   Documentation synchronization over speed
-   No silent drift
-   No hidden coupling
-   No uncontrolled surface mutation

------------------------------------------------------------------------

# 7. Operational Flow Summary

New Chat → Task Description\
↓\
Start Template Generated\
↓\
Codex Review\
↓\
ChatGPT Strategy Refinement\
↓\
Implementation Loop\
↓\
Validation (ChatGPT + Codex)\
↓\
Close Template Generated\
↓\
Decision Log Updated\
↓\
Task Closed

------------------------------------------------------------------------

# Final Rule

If governance is unclear at any moment: Stop and clarify before
proceeding.
