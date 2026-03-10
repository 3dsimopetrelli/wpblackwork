# Task Close Protocol

## Purpose
This protocol defines the authoritative closure procedure for all Blackwork tasks.

## Mandatory Governance Updates
Every task closure MUST update the following governance documents:

- `docs/00-governance/risk-register.md`
- `docs/50-ops/regression-protocol.md`
- `docs/00-governance/risk-status-dashboard.md`

These updates are required whenever a task affects risk mitigation status.

Checklist to perform during task close:

1. Update the **Risk Register** entry for the risk ID.
2. Update the **Regression Protocol** if the task introduces new regression checks.
3. Update the **Risk Status Dashboard** so the risk state is visible at a glance.

Failure to update these documents results in an incomplete task closure.

## Closure Sequence
1. Verify implementation scope is complete.
2. Verify mandatory checks/regressions were executed.
3. Apply mandatory governance updates.
4. Record final status wording consistently across governance files.
5. Publish closure summary.

## Status Consistency Rule
Status wording MUST be consistent across:

- risk register
- risk status dashboard
- closure summary

## IMPORTANT

A task is NOT considered closed until:

- `risk-register.md` is updated
- `regression-protocol.md` is updated
- `risk-status-dashboard.md` is updated
