# Brevo Runbook

## 1. Domain Scope
Includes newsletter subscription capture, consent-driven sync, Brevo API coupling, public widget submit handling, and admin diagnostics/reporting for marketing flows.

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
- Public widget submit bypassing consent or validation.
- Public widget endpoint abuse/rate-limit gaps.
- API failures causing silent contact sync gaps.
- Status mapping inconsistencies (subscribed/unsubscribed/pending).
- Admin UI diagnostics diverging from actual runtime state.

High-risk integrations and dependencies:
- Brevo API credentials/list/attribute mapping.
- Checkout field injection and order meta persistence.
- Retry/resync logic.
- Elementor subscription widget public endpoint and asset loading in custom footer runtime.

## 3. Pre-Maintenance Checklist
- Read Brevo architecture and governance docs.
- Verify expected consent model (single vs double opt-in).
- Identify fragile areas: meta keys, sync trigger conditions, retry logic, public widget response codes, and rate-limit behavior.

## 4. Safe Fix Protocol
- Preserve consent and compliance semantics.
- Keep metadata keys and mapping changes explicit and documented.
- Do not alter sync state machine without review.
- ADR required if Brevo integration architecture/state model changes.

## 5. Regression Checklist (Domain Specific)
- Validate checkout consent field display and persistence.
- Validate sync behavior for opted-in and non-opted-in flows.
- Validate duplicate email and unsubscribed handling.
- Validate widget submit states:
  - empty email
  - invalid email
  - missing consent
  - already subscribed
  - rate limited
  - generic failure
- Validate widget render inside custom footer/frontend/editor contexts.
- Validate admin diagnostics (order/user panels and filters).
- Scan console/log output and API responses for errors.

## 6. Documentation Update Requirements
- Update `CHANGELOG.md` for Brevo runtime/consent/sync changes.
- Update Brevo architecture/subscribe/checklist docs when behavior changes.
- Update Elementor widget architecture docs when the subscription widget contract changes.
- Update ADR for structural changes to Brevo integration model.
