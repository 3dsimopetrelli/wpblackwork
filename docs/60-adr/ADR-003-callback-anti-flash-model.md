# ADR-003: Callback Anti-Flash Model

## Status
Accepted

This decision is binding and may only be altered through a superseding ADR.

## Context
Blackwork checkout and post-payment surfaces combine asynchronous provider confirmation, return/redirect routes, fragment refresh cycles, and UI rendering layers.

ADR-001 establishes checkout selector orchestration authority.
ADR-002 establishes top-down authority precedence (Payment > Authentication > Provisioning).
This ADR formalizes callback anti-flash discipline so runtime rendering cannot speculate on payment truth before authoritative confirmation.

The model applies to payment callbacks/webhooks, return pages, checkout fragments, and any success-like UI surface that can be reached before or during provider confirmation.

## Problem Definition (Flash/Flicker Risks)
Without explicit anti-flash governance, transitional surfaces can misrepresent state and create non-deterministic behavior.

Primary risks:
- Success-like UI rendered before provider-confirmed payment.
- Visual flicker between pending and confirmed states during callback races.
- Fragment refresh re-introducing stale UI state after authority already converged.
- Duplicate callback/retry events causing repeated transitions or temporary regressions.
- Redirect/return pages treated as authority confirmation instead of transitional transport.

These risks can create false-positive payment perception, unstable user journeys, and state divergence across checkout/order surfaces.

## Decision
Blackwork formally adopts the Callback Anti-Flash Model as a Tier 0 governance rule set.

Binding decision:
- Payment truth MUST be established ONLY by provider-confirmed webhook/callback processed through authoritative local mapping.
- Redirect/return URLs MUST be treated as transitional surfaces and MUST NOT be treated as authority confirmation.
- UI MUST reflect authoritative order/payment state and MUST NOT speculate.
- Duplicate callback events MUST be idempotent and MUST converge.
- Once authority is established, visual state MUST NOT flicker to a lower-confidence state.

## Callback Convergence Model
Callback handling MUST satisfy all of the following:

1. Authenticity gate
- Callback/webhook input MUST be validated before any state mutation.

2. Idempotent mutation gate
- Replayed or duplicate events MUST NOT duplicate side effects.
- Repeated processing of equivalent events MUST converge to the same terminal state.

3. Monotonic state progression
- State transitions MUST be monotonic.
- Confirmed payment CANNOT regress to pending or speculative states.

4. Local authority reconciliation
- Provider outcomes MUST reconcile into authoritative local order/payment state exactly once per effective transition.
- UI rendering MUST read reconciled local authority, not pre-reconciliation transient inputs.

5. Retry safety
- Retry events MAY re-enter processing but MUST preserve terminal-state stability once reached.

## Redirect/Return Surface Rules
Return and redirect surfaces are operational transport layers.

Rules:
- Return/thank-you surfaces MUST NOT declare payment success unless local authoritative state is confirmed.
- Pending or unresolved confirmation MUST render safe neutral/pending messaging, not success certainty.
- Return route rendering MUST tolerate delayed webhook confirmation without false-positive success display.
- Duplicate return hits MUST be repeat-safe and MUST NOT trigger divergent state presentation.
- Redirect handling MUST NOT introduce loops or state oscillation.

Required safe rendering outcomes:
- Confirmed payment: stable confirmed state.
- Pending confirmation: stable pending state.
- Duplicate webhook/return: stable previously converged state.
- Retry events: no regression, no speculative success.

## Fragment Refresh Discipline
Fragment refresh is a presentation update mechanism and MUST remain non-authoritative.

Rules:
- Fragment refresh cycles MUST converge to one deterministic state per authority snapshot.
- Fragment updates MUST NOT re-activate stale pre-confirmation UI once payment authority has converged.
- Fragment-bound UI controls MUST re-bind deterministically after DOM replacement.
- Fragment refresh MUST NOT create contradictory state signals across payment method selection, order status display, or success indicators.
- Fragment/UI timing races MUST resolve in favor of authoritative local state, not client timing order.

## Idempotency Requirements
All callback-adjacent flows MUST satisfy idempotency invariants:

- Duplicate webhook events MUST be safely ignored or re-applied without changing terminal truth.
- Duplicate return/callback requests MUST NOT create additional business transitions.
- Callback processing MUST be deterministic for identical inputs against identical authority state.
- Event processing history or equivalent deduplication controls MUST prevent side-effect duplication.
- Idempotency MUST apply to payment/order convergence even when network retries or delayed confirmations occur.

## Alternatives Considered

### 1) UI-first success model
Rejected.
Allows speculative success rendering and violates payment authority doctrine.

### 2) Redirect-as-confirmation model
Rejected.
Treats transport surfaces as authority, causing false confirmations under delayed or failed provider confirmation.

### 3) Best-effort anti-flash without strict monotonic rules
Rejected.
Cannot guarantee deterministic convergence under duplicates, retries, and fragment races.

### 4) Fragment-driven authority inference
Rejected.
Inverts trust boundaries by allowing presentation timing to influence business truth.

## Consequences
- Checkout and return flows remain stable under delayed confirmation and duplicate callback events.
- Success surfaces become authority-aligned and non-speculative.
- Fragment refresh behavior is constrained to deterministic reflection of authoritative state.
- Regression validation for callback/return/payment surfaces MUST include anti-flash and monotonic-transition verification.
- Cross-domain behavior remains compatible with ADR-001 and ADR-002 authority boundaries.

## Invariants Protected
- Payment truth MUST originate only from provider-confirmed callback/webhook + local authoritative mapping.
- Redirect/return surfaces MUST NOT be treated as payment authority.
- UI layers MUST NOT speculate, infer, or upgrade payment truth.
- State transitions MUST be monotonic; confirmed MUST NOT regress to pending.
- Duplicate callback/retry events MUST converge to one stable terminal state.
- Fragment refresh cycles MUST deterministically reflect authority and MUST NOT create flicker-driven state divergence.
