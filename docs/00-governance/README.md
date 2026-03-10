# Governance Documentation Precedence

This index defines the governance document hierarchy for Blackwork task execution and closure.

## Governance hierarchy (highest to lowest)
1. `docs/00-governance/ai-task-protocol.md` — task lifecycle authority
2. `docs/governance/task-close.md` — closure procedure authority
3. `docs/00-governance/risk-register.md` — canonical risk data
4. `docs/00-governance/risk-status-dashboard.md` — risk visualization layer
5. `docs/50-ops/regression-protocol.md` — operational validation and regression checks

## Conflict rule
If two governance documents conflict, the higher-precedence document in this hierarchy wins.

## Usage note
- Use the risk register as the source of truth for risk status and lifecycle details.
- Keep the risk dashboard synchronized with the risk register for quick orientation.
- Follow `task-close.md` for every closure; template files are structural aids only.
