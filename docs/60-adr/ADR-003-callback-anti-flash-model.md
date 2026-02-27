# ADR-003: Callback Anti-Flash Model

## Status
Accepted

This decision is binding and may only be altered through a superseding ADR.

## Context

Blackwork checkout and post-payment surfaces combine asynchronous provider confirmation, return/redirect routes, fragment refresh cycles, and UI rendering layers.

ADR-001 establishes checkout selector orchestration authority.
ADR-002 establishes top-down authority precedence (Payment > Authentication > Provisioning).

This ADR formalizes the Callback Anti-Flash Model to ensure UI layers cannot speculate, downgrade, or misrepresent payment truth during asynchronous confirmation flows.

The model applies to:

- Provider callbacks/webhooks
- Redirect and return routes
- Checkout fragment refresh cycles
- Success or thank-you surfaces
- Any UI capable of rendering payment state before or during confirmation

## Problem Definition (Flash/Flicker Risks)

Without explicit anti-flash governance:

- Success UI may render before provider-confirmed payment.
- Pending/confirmed states may visually oscillate during callback races.
- Fragment refresh may reintroduce stale UI after authority convergence.
- Duplicate callback events may trigger repeated or regressive transitions.
- Redirect surfaces may be misinterpreted as confirmation authority.

These behaviors create false-positive payment perception, unstable journeys, and cross-layer divergence.

## Decision

Blackwork formally adopts the Callback Anti-Flash Model as a Tier 0 governance rule set.

Binding rules:

- Payment truth MUST be established ONLY by provider-confirmed webhook/callback processed through authoritative local reconciliation.
- Redirect/return URLs are transitional transport layers and MUST NOT be treated as authority confirmation.
- UI MUST render from reconciled local authoritative state only.
- UI MUST NOT speculate, infer, or upgrade payment state.
- Duplicate callback events MUST be idempotent and converge.
- Once authoritative confirmation is reached, visual state MUST NOT regress or flicker to lower-confidence states.

This model operates within ADR-002 authority hierarchy and preserves monotonic progression at the Payment Authority layer.

## Single Source of Render Truth

All rendering surfaces MUST read from the reconciled local authoritative order/payment state.

The following are explicitly NON-authoritative signals:

- Query parameters
- Stripe return flags
- Client-side redirect indicators
- Fragment timing order
- Browser event sequencing
- Frontend success triggers

These signals MAY influence UX flow but MUST NOT determine payment truth.

## Callback Convergence Model

Callback handling MUST satisfy:

### 1. Authenticity Gate
Callback/webhook input MUST be cryptographically or signature validated before mutation.

### 2. Idempotent Mutation Gate
Duplicate or replayed events MUST NOT duplicate side effects.
Repeated processing MUST converge to identical terminal state.

### 3. Monotonic State Progression
State transitions MUST be monotonic.
Confirmed payment CANNOT regress to pending or speculative state.

Once confirmed state is rendered, no subsequent async event may visually downgrade it.

### 4. Deterministic Reconciliation
Provider outcome MUST reconcile into authoritative local state exactly once per effective transition.
UI MUST render only after reconciliation snapshot is stable.

### 5. Retry Safety
Retry events MAY re-enter processing but MUST preserve terminal-state stability once reached.

## Redirect / Return Surface Rules

Return and redirect routes are operational transport layers only.

Rules:

- Return surfaces MUST NOT declare success without confirmed authoritative local state.
- Pending confirmation MUST render neutral or pending messaging.
- Delayed webhook confirmation MUST NOT create false-positive success.
- Duplicate return hits MUST be repeat-safe.
- Redirect handling MUST NOT introduce loops or oscillation.

Allowed stable outcomes:

- Confirmed → stable confirmed
- Pending → stable pending
- Duplicate webhook → stable previously converged
- Retry → no regression

## Fragment Refresh Discipline

Fragment refresh is presentation-only and MUST remain non-authoritative.

Rules:

- Fragment cycles MUST converge to a single deterministic state per authority snapshot.
- Stale pre-confirmation UI MUST NOT reappear after confirmation.
- Fragment-bound controls MUST re-bind deterministically after DOM replacement.
- Timing races MUST resolve in favor of reconciled local authority.
- Fragment rendering MUST NOT create contradictory payment signals.

## Idempotency Requirements

All callback-adjacent flows MUST satisfy:

- Duplicate webhook events MUST NOT alter terminal truth.
- Duplicate return/callback requests MUST NOT create new transitions.
- Processing MUST be deterministic for identical input + identical authority state.
- Deduplication or equivalent controls MUST prevent side-effect duplication.
- Idempotency MUST apply under network retry and delayed confirmation scenarios.

## Alternatives Considered

### 1) UI-first success model
Rejected.
Speculative and violates Payment Authority doctrine.

### 2) Redirect-as-confirmation model
Rejected.
Treats transport layer as authority and creates false confirmation risk.

### 3) Soft anti-flash without strict monotonicity
Rejected.
Cannot guarantee convergence under duplicate or delayed events.

### 4) Fragment-driven authority inference
Rejected.
Allows presentation timing to influence business truth.

## Consequences

- Checkout and return flows remain stable under delayed or duplicated confirmations.
- Success surfaces become authority-aligned and non-speculative.
- Fragment refresh is constrained to deterministic reflection of reconciled authority.
- Regression validation MUST include anti-flash and monotonic verification.
- Fully compatible with ADR-001 and ADR-002 authority boundaries.

## Invariants Protected

- Payment truth MUST originate only from provider-confirmed callback + local authoritative reconciliation.
- Redirect/return routes MUST NOT be treated as payment authority.
- UI layers MUST NOT speculate or upgrade payment state.
- State transitions MUST be monotonic.
- Confirmed state MUST NOT regress.
- Duplicate events MUST converge to a single stable terminal state.
- Fragment refresh MUST deterministically reflect authority without flicker-induced divergence.
