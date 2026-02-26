# Maintenance Decision Matrix

## 1. Purpose
This matrix prevents random fixes.

Every maintenance action must pass through a decision filter before implementation. The filter determines impact scope, required validation depth, documentation obligations, and release implications.

## 2. Decision Tree

### A) Issue Nature
Classify the issue first:
- UI only
- Business logic
- Integration
- Security
- Architectural

### B) Impact Surface
Then classify impact scope:
- Checkout
- Payments
- Auth
- External APIs
- Multiple domains

### C) Change Origin
Then classify incident origin:
- Regression
- New bug
- Side effect of refactor
- External provider change

### Decision Flow
1. Identify issue nature (A).
2. Map affected domain(s) (B).
3. Determine origin type (C).
4. Assign incident level using `incident-classification.md`.
5. Apply required actions from the matrix table.
6. Execute validation and documentation updates before closure.

## 3. Required Actions Table

| Incident Type | Update `CHANGELOG.md` | Update feature doc | Update runbook | Create ADR | Run full regression | Run domain regression only |
|---|---|---|---|---|---|---|
| UI only (minor, Level 3) | No* | No* | No | No | No | Yes |
| UI regression (Level 2) | Yes | Yes | Yes | No | Yes | No |
| Business logic bug (single domain) | Yes | Yes | Yes | No | Yes | No |
| Integration failure (payments/auth/API) | Yes | Yes | Yes | Maybe** | Yes | No |
| Security issue | Yes | Yes | Yes | Yes** | Yes | No |
| Architectural issue/change | Yes | Yes | Yes | Yes | Yes | No |
| Refactor side effect | Yes | Yes | Yes | Maybe** | Yes | No |
| External provider change impact | Yes | Yes | Yes | Maybe** | Yes | No |

Notes:
- `No*`: becomes `Yes` if user-visible behavior, constraints, or troubleshooting guidance changed.
- `Maybe**`: mandatory if contracts, system boundaries, or decision-level architecture changed.

## 4. Release Blocking Rules
- Level 1 issues block release.
- Integration failures block release.
- Security issues always block release.
- Minor UI issues (Level 3 cosmetic) do not block release.

## 5. Documentation Discipline
No maintenance task is complete until all three conditions are true:
- Regression passed.
- Documentation updated at required level.
- Decision recorded (incident type + chosen action path).
