# Blackwork Governance — Task Closure Artifact

## 1) Task Identification
- Task ID: `BW-TASK-20260306-06`
- Task title: Payments Webhook Exactly-Once Hardening
- Domain: Payments / Webhook Convergence
- Tier classification: 1
- Risk reference: `R-PAY-08`

## 2) Scope
Implemented scope:
- `includes/Gateways/class-bw-abstract-stripe-gateway.php`
- `docs/00-governance/risk-register.md`
- `docs/tasks/BW-TASK-20260306-06-closure.md`

Declared scope not modified:
- `includes/Gateways/class-bw-google-pay-gateway.php`
- `includes/Gateways/class-bw-apple-pay-gateway.php`
- `includes/Gateways/class-bw-klarna-gateway.php`

Reason gateway-specific files were not touched:
- Webhook authority logic is centralized in `BW_Abstract_Stripe_Gateway::handle_webhook()` and shared helpers.
- Hardening was completed without endpoint, hook, or contract drift in concrete gateway classes.

## 3) Implementation Summary
1. Webhook authenticity gate
- Preserved strict signature verification as mandatory first gate.
- Invalid signatures still exit immediately with no order mutation.

2. Event idempotency ledger
- Added deterministic per-order event claim marker (`_bw_evt_claim_{gateway}_{md5(event_id)}`).
- Event claim record lifecycle: `processing` -> `completed`.
- Duplicate events (or concurrent inflight event claims) now no-op safely.

3. Exactly-once processing sequence
- Processing now claims `event_id` before order mutation.
- Mutation path executes once per claimed event.
- Event is finalized into completed claim + rolling `processed_events` history.

4. Monotonic status guard
- Added `can_apply_webhook_status_transition()` guard.
- Disallowed out-of-order or regressive transitions no-op deterministically.
- Paid/terminal states are protected against failure/cancel regressions.

5. Return-flow vs webhook convergence
- Webhook success event applies `payment_complete()` only when order is not already paid.
- If return flow already converged payment, webhook event becomes deterministic no-op.
- Payment failure/cancel events are blocked when current state indicates paid/terminal authority.

6. Side-effect dedupe
- Deduped notes/emails/metadata side effects by event claim gate.
- Event notes now include `event_id` trace for deterministic auditability.

7. Fail-safe behavior
- Unknown event types, duplicate events, and out-of-order transitions resolve to safe no-op with HTTP 200.

## 4) Determinism Evidence
- Input/output determinism:
  - Same `(order_id, event_id, event_type)` converges to a single outcome due to claim marker and completed-state checks.
- Ordering determinism:
  - Webhook flow order is fixed: signature verify -> payload parse -> order resolve -> event claim -> guarded mutation -> mark completed.
- Retry/re-entry convergence:
  - Retries for an already completed `event_id` no-op.
  - Concurrent duplicate deliveries are serialized by unique event claim meta key.

## 5) Runtime Surfaces Touched
- Existing webhook endpoint handlers registered on `woocommerce_api_{gateway_id}` (unchanged registration).
- `BW_Abstract_Stripe_Gateway::handle_webhook()` runtime flow hardened.
- No new hooks/actions/endpoints introduced.

## 6) Manual Verification Checklist
- [ ] Guest checkout then webhook `payment_intent.succeeded`: order converges once and only once.
- [ ] Logged-in checkout then webhook `payment_intent.succeeded`: no duplicate order notes or duplicate side effects.
- [ ] Replay same signed webhook `event_id`: HTTP 200 and no new mutation.
- [ ] Out-of-order sequence (`succeeded` then `payment_failed`): second event no-op.
- [ ] Unknown event type for valid order/event envelope: safe no-op, no state mutation.
- [ ] Return flow marks order paid before webhook arrives: webhook success no-op with stable final state.
- [ ] Valid non-paid path with `payment_intent.processing`: monotonic on-hold transition only where allowed.

## 7) Residual Risks
- Claim marker uses order-meta storage and is as strong as WordPress meta write guarantees; full distributed lock semantics are out of scope.
- Multi-provider event semantics beyond supported `payment_intent.*` set remain intentionally no-op until explicitly modeled.
- End-to-end replay hardening still depends on upstream Stripe signature and event integrity guarantees.

## 8) Documentation / Governance Updates
- Updated `R-PAY-08` mitigation text and monitoring status in:
  - `docs/00-governance/risk-register.md`
- Added closure evidence artifact:
  - `docs/tasks/BW-TASK-20260306-06-closure.md`
- No runtime hook-map update required (runtime surface unchanged).
- No decision-log update required (no new standing operational rule beyond scoped mitigation implementation).

## 9) Performance / Cost Note
- The new query/runtime path is cheaper under duplicate webhook traffic because duplicate and concurrent replays exit at claim-check stage before status transitions and side effects.
