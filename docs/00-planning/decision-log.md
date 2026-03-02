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

### Entry 002
- Date: 2026-02-27
- Decision summary: SKU selected as the canonical unique key for the Import Domain in the Import Products vNext specification.
- Affected domain: Data Import / Product Identity
- Rationale: Convergence and idempotency model requires one immutable canonical key to prevent duplicate product identity paths. Woo-native uniqueness ensures deterministic bulk import behavior.
- Risk impact: High
- Follow-up actions:
  - Keep importer implementation backlog aligned with SKU-only identity rule.
  - Reject run configurations that do not provide SKU per row.
  - Validate deterministic duplicate-SKU failure behavior in regression runs.
  - Implement Import Engine v2 per `docs/30-features/import-products/import-products-vnext-spec.md`.

### Entry 003
- Date: 2026-03-02
- Decision summary: Theme Builder Lite `bw_template` remains non-public in site navigation but is intentionally previewable for Elementor via controlled singular rendering and noindex policy.
- Affected domain: Theme Builder Lite / Elementor Integration / Runtime Isolation
- Rationale: Elementor editor requires a valid frontend preview response (HTTP 200 + WP head/footer hooks). A controlled preview path resolves editor bootstrap failures while preserving SEO/privacy constraints.
- Risk impact: Medium reduced to Low for Phase 1 Footer Override editor stability.
- Follow-up actions:
  - Keep admin assets scoped to Theme Builder Lite settings page only.
  - Preserve preview guards that bypass footer override during Elementor editor/preview and `bw_template` singular requests.
  - Re-evaluate this contract if future template types introduce public routing requirements.

## Governance Layer Closure

Status: CLOSED  
Date: 2026-02-27  
Notes:
Governance structure finalized after structural review and normalization.
Further changes require ADR escalation.

## Governance Layer Closure

Status: CLOSED  
Date: 2026-02-27  
Notes:
Governance structure finalized after structural review and normalization.
Further changes require ADR escalation.

## Search AS-IS Documentation Closure

Status: CLOSED
Date: 2026-02-27
Notes:
Search runtime documented completely.
Initial letter indexing not present in current implementation.
Enhancements will be defined under Search vNext spec.

## Search Architecture Documentation Closure

Status: CLOSED
Date: 2026-02-27
Notes:
Search runtime fully documented (AS-IS).
Search vNext architecture defined and approved.
Implementation scheduled in roadmap backlog.

## Template Hardening — Governance Upgrade

Status: CLOSED
Notes:
Start and Close templates updated to include invariant protection layer.
No authority drift.
No Tier reclassification.
Governance strengthened without increasing procedural weight.

## Plugin Identity Rename — Metadata Update

Status: CLOSED
Date: 2026-02-27
Notes:
Plugin display identity updated from BW Elementor Widgets to Blackwork Core Plugin.
Metadata-only change (name, description, version, authors) with no runtime authority mutation.

## Bootstrap Filename Migration

Status: CLOSED
Date: 2026-02-27
Notes:
Bootstrap file renamed to `blackwork-core-plugin.php` from the previous bootstrap filename.
Runtime references and tooling script paths were aligned.
Plugin slug, text-domain, internal prefixes, and runtime authority model remain unchanged.
