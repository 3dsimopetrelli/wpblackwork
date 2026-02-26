# System Normative Charter

## 1) Purpose
This charter defines the binding cross-system invariants that protect business integrity, commerce continuity, and data authority across Supabase, Payments, Checkout, and Brevo integrations.
It is the top-level architectural contract for system behavior under normal and failure conditions.

## 2) Authority Hierarchy (Source-of-Truth Model)
Authority order is explicit and non-interchangeable:

- Payment authority: payment provider confirmation and platform payment status.
- Order authority: WooCommerce order state and lifecycle.
- Auth authority: WordPress session state.
- Consent authority: local consent metadata.
- External providers (Supabase remote services, Brevo remote services, Stripe remote services): downstream execution systems.

External systems may execute and report outcomes, but they do not define final local business truth.

## 3) Non-Blocking Commerce Principle
- No integration may block checkout submission, payment completion, or order creation.
- Marketing sync, onboarding/provisioning, and external synchronization failures must degrade safely.
- Commerce continuity has precedence over non-core auxiliary workflows.

## 4) Identity & State Separation Principle
- Auth state, payment state, consent state, and provisioning state are distinct domains.
- One state domain must not overwrite another domain’s authority.
- Cross-domain reads are permitted; cross-domain authority takeover is prohibited.

## 5) Idempotency & Convergence Principle
- Repeated triggers, retries, and callbacks must converge toward a single stable local outcome.
- The system must prevent duplicate ownership assignment, duplicate provisioning effects, and duplicate marketing-contact intent.
- Retry safety is mandatory for all external write paths.

## 6) Callback & External Event Discipline
All callback/webhook/external-event flows must:
- validate authenticity before mutation,
- enforce idempotency before state transition,
- avoid unstable first-render states,
- avoid redirect loops and repeated terminal routing.

Callback correctness is both a security and integrity requirement.

## 7) Local Authority Doctrine
- The local system owns policy truth and final business state.
- External integrations are execution engines, not policy engines.
- Remote outcomes are accepted only through controlled local reconciliation paths.

## 8) Observability & Audit Principle
- Every external write action must produce local, traceable audit evidence.
- Local state must reflect outcome class (success/skip/error/pending-equivalent).
- Silent remote mutations without local audit reflection are not compliant.

## 9) Blast-Radius Rule
Any change affecting one or more of the following requires full regression validation:
- payment flow,
- consent gate,
- auth bridge,
- provisioning logic,
- external API client behavior.

High-coupling surfaces are treated as release-sensitive boundaries.

## 10) Evolution Guardrail
Any new integration is valid only if it explicitly declares:
- authority model,
- blocking behavior,
- failure model,
- idempotency guarantees.

No integration can be accepted into system architecture without this declaration.
