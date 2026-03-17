# Blackwork Governance -- Task Start

## Context
- Task title: Close documentation wave for Mail Marketing Elementor subscription widget
- Request source: user request in Codex session (`BW-TASK-20260317-01`)
- Expected outcome:
  - update all relevant Markdown authority documents for the Brevo-connected Elementor subscription widget
  - align Brevo, Elementor, admin-panel, QA, and task traceability docs
  - document the feature as a closed and operational task

## Task Classification
- Domain: Documentation / Brevo Mail Marketing / Elementor Widgets / Admin IA
- Incident/Task type: documentation backfill and closure hardening
- Risk level: L1
- Tier classification: 3

## Scope Declaration
- Documentation surfaces to align:
  - `docs/40-integrations/brevo/`
  - `docs/10-architecture/`
  - `docs/20-development/`
  - `docs/30-features/elementor-widgets/`
  - `docs/tasks/`
- Out of scope:
  - runtime code changes
  - Brevo API/client changes
  - CSS/JS/widget behavior changes

## Runtime Surface Declaration
- New hooks expected: none
- Hook priority modifications: none
- Database/data surfaces touched: none

## Documentation Update Plan
- Update Brevo architecture/governance docs with the final widget contract.
- Update Elementor widget architecture inventory and create a dedicated widget doc.
- Update admin panel map for `Mail Marketing -> Subscription` and `Site Settings -> Info`.
- Add closed-task artifact(s) for traceability.

## Acceptance Gate
- Task Classification completed: Yes
- Scope Declaration completed: Yes
- Documentation Update Plan completed: Yes
