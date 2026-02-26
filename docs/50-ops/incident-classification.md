# Incident Classification

## Level 1 - Critical
Examples:
- Checkout broken
- Payment failure
- Auth failure
- Security issue

Response priority:
- Immediate triage and same-day mitigation.

Documentation requirement:
- Update incident notes and impacted domain docs.
- Add changelog entry.
- Add ADR note if architectural contract changed.

Test depth:
- Full regression protocol (mandatory).
- Incident-specific deep verification.

## Level 2 - Functional
Examples:
- Feature partially broken
- Integration unstable
- Layout breaking

Response priority:
- High priority, planned in current maintenance cycle.

Documentation requirement:
- Update affected domain documentation.
- Add changelog entry.

Test depth:
- Full regression protocol (mandatory).
- Focused tests on impacted domains.

## Level 3 - Cosmetic
Examples:
- CSS issue
- Minor UI inconsistency
- Non-blocking bug

Response priority:
- Medium/low priority based on visibility.

Documentation requirement:
- Update docs only if behavior/contracts/usage notes changed.

Test depth:
- Targeted validation on touched surface.
- Full regression protocol optional.
