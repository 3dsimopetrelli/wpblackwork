# Brevo Runbook

## 1. Domain Scope
Includes newsletter subscription capture, consent-driven sync, Brevo API coupling, and admin diagnostics/reporting for marketing flows.

Related folders:
- `docs/40-integrations/brevo/`
- `docs/30-features/checkout/`

Related docs:
- `../../40-integrations/brevo/brevo-mail-marketing-architecture.md`
- `../../40-integrations/brevo/subscribe.md`
- `../../40-integrations/brevo/mail-marketing-qa-checklist.md`
- `../regression-protocol.md`

## 2. Critical Risk Points
- Consent capture not persisted correctly at checkout.
- API failures causing silent contact sync gaps.
- Status mapping inconsistencies (subscribed/unsubscribed/pending).
- Admin UI diagnostics diverging from actual runtime state.

High-risk integrations and dependencies:
- Brevo API credentials/list/attribute mapping.
- Checkout field injection and order meta persistence.
- Retry/resync logic.

## 3. Pre-Maintenance Checklist
- Read Brevo architecture and governance docs.
- Verify expected consent model (single vs double opt-in).
- Identify fragile areas: meta keys, sync trigger conditions, retry logic.

## 4. Safe Fix Protocol
- Preserve consent and compliance semantics.
- Keep metadata keys and mapping changes explicit and documented.
- Do not alter sync state machine without review.
- ADR required if Brevo integration architecture/state model changes.

## 5. Regression Checklist (Domain Specific)
- Validate checkout consent field display and persistence.
- Validate sync behavior for opted-in and non-opted-in flows.
- Validate duplicate email and unsubscribed handling.
- Validate admin diagnostics (order/user panels and filters).
- Scan console/log output and API responses for errors.

## 6. Documentation Update Requirements
- Update `CHANGELOG.md` for Brevo runtime/consent/sync changes.
- Update Brevo architecture/subscribe/checklist docs when behavior changes.
- Update ADR for structural changes to Brevo integration model.
