# Blackwork Core – Decision Log

Purpose:
Track architectural and governance decisions that alter roadmap direction.
This log is planning-oriented and MUST NOT duplicate ADR content.
If a decision is normative and architecture-binding, the ADR process MUST be used.

## Entry Template

- Date:
- Decision summary:
- Affected domain:
- Rationale:
- Risk impact:
- Follow-up actions:

## Entries

### Entry 004
- Date: 2026-02-27
- Decision summary: SKU chosen as canonical unique key for Import Domain.
- Affected domain: Data Import / Product Identity
- Rationale: Woo-native uniqueness and deterministic idempotency for bulk imports.
- Risk impact: Reduces duplicate risk; constrains identity model.
- Follow-up actions:
  - Implement Import Engine v2 per `docs/30-features/import-products/import-products-vnext-spec.md`.

### Entry 003
- Date: 2026-02-27
- Decision summary: SKU chosen as canonical unique key for Import Products domain.
- Affected domain: Data Import / Product Identity
- Rationale: Woo-native uniqueness and deterministic idempotency for bulk imports.
- Risk impact: Reduces duplicate risk; constrains identity model.
- Follow-up actions:
  - Implement Import Engine v2 per `docs/30-features/import-products/import-products-vnext-spec.md`.

### Entry 002
- Date: 2026-02-27
- Decision summary: SKU selected as the canonical unique key for the Import Domain in the Import Products vNext specification.
- Affected domain: Data Import / Product Identity
- Rationale: Convergence and idempotency model requires one immutable canonical key to prevent duplicate product identity paths.
- Risk impact: High
- Follow-up actions:
  - Keep importer implementation backlog aligned with SKU-only identity rule.
  - Reject run configurations that do not provide SKU per row.
  - Validate deterministic duplicate-SKU failure behavior in regression runs.

### Entry 001
- Date: 2026-02-27
- Decision summary: Introduced a dedicated governance planning layer under `docs/00-planning/` to track evolution priorities and decision-driven roadmap shifts.
- Affected domain: Governance / Planning
- Rationale: The system reached multi-domain maturity and requires persistent planning artifacts separated from ADR normative contracts.
- Risk impact: Low
- Follow-up actions:
  - Keep `core-evolution-plan.md` synchronized with current governance priorities.
  - Register any roadmap direction change in this log.
  - Promote architecture-binding decisions to ADR, not to this file.
