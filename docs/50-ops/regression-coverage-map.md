# Regression Coverage Map

## 1) Purpose
This document defines which regression journeys protect which governance invariants.
It is the operational link between governance contracts and release validation.

Scope:
- map invariant -> concrete regression journey
- map journey -> protected risk IDs
- identify explicit coverage gaps
- define minimum release gate for Tier 0 safety

## 2) Invariant Coverage Table
| Invariant | Source Document | Covered By Test Journey | Risk ID Protected | Coverage Level (Full/Partial/Gap) |
|---|---|---|---|---|
| Payment authority invariant | `system-normative-charter.md` | R-01, R-02, R-03, R-04 | R-PAY-08, R-CHK-01 | Partial |
| Webhook idempotency invariant | `callback-contracts.md` | R-04, R-01 | R-PAY-08 | Partial |
| Checkout selector determinism | `blast-radius-consolidation-map.md`, checkout audit | R-07, R-01, R-02 | R-CHK-01, R-PAY-02, R-PAY-03 | Partial |
| Callback convergence invariant | `callback-contracts.md`, `system-state-matrix.md` | R-05, R-06 | R-AUTH-04 | Partial |
| Auth/session authority boundary | `cross-domain-state-dictionary.md`, `system-state-matrix.md` | R-05, R-06, R-02 | R-AUTH-04, R-AUTH-05 | Partial |
| Onboarding marker semantics | `system-state-matrix.md`, My Account audit | R-05, R-02 | R-AUTH-05 | Partial |
| Guest claim idempotency | `system-state-matrix.md`, hardening plan | R-01, R-05 | R-SUPA-06 | Partial |
| Download entitlement invariant | `system-state-matrix.md`, My Account audit | R-01, R-02 | R-SUPA-06 | Partial |
| Consent local authority invariant | `system-normative-charter.md`, risk register | R-08, R-09 | R-BRE-09 | Full |
| Authority non-override rule | `system-normative-charter.md`, `callback-contracts.md` | R-01, R-04, R-05, R-08 | R-PAY-08, R-AUTH-04, R-BRE-09 | Partial |

## 3) Regression Test Journeys
### R-01 Guest checkout paid -> webhook -> claim -> downloads
- Validates invariants:
  - payment authority
  - claim idempotency
  - download entitlement
  - authority non-override
- Domains touched: Checkout, Payments, Supabase, My Account
- Failure signals:
  - paid order without accessible claimed downloads after onboarding
  - duplicate or missing claim linkage
  - UI indicates success but order/payment not converged

### R-02 Logged-in Supabase checkout paid
- Validates invariants:
  - payment authority
  - auth/session boundary
  - onboarding marker semantics
- Domains touched: Checkout, Payments, Auth/Supabase, My Account
- Failure signals:
  - paid order path diverges by onboarding marker unexpectedly
  - authenticated user routed into callback/loader loop
  - selected gateway differs from processed gateway

### R-03 Payment failure path
- Validates invariants:
  - payment authority
  - order lifecycle consistency
  - non-false-success UX
- Domains touched: Checkout, Payments
- Failure signals:
  - failed/canceled payment shown as successful order
  - order state converges incorrectly to processing/completed

### R-04 Webhook replay simulation
- Validates invariants:
  - webhook idempotency
  - authority non-override
- Domains touched: Payments, Order lifecycle
- Failure signals:
  - duplicate side effects/order transitions on replay
  - conflicting status transitions from repeated events

### R-05 Supabase invite -> OTP -> password -> onboard
- Validates invariants:
  - callback convergence
  - auth/session authority
  - onboarding marker semantics
  - guest claim idempotency
- Domains touched: Auth/Supabase, My Account, Orders/Downloads
- Failure signals:
  - callback loop or ghost loader
  - onboarding marker stuck/misaligned
  - claimed orders not attached after onboarding completion

### R-06 Email change flow
- Validates invariants:
  - auth/session boundary
  - pending email semantics
  - callback convergence
- Domains touched: My Account, Auth/Supabase
- Failure signals:
  - pending email banner never clears after confirmation
  - security tab redirect loops or stale callback params
  - local email state updates without confirmation path

### R-07 Fragment refresh stress (checkout)
- Validates invariants:
  - selector determinism
  - payment UI/submission coherence
- Domains touched: Checkout, Payments
- Failure signals:
  - double active gateway
  - selected radio and visible panel mismatch
  - wallet button visibility mismatched with eligibility

### R-08 Consent opt-in + Brevo sync
- Validates invariants:
  - consent local authority
  - non-blocking commerce
- Domains touched: Checkout, Brevo, Order hooks
- Failure signals:
  - opt-in not persisted locally
  - sync write missing despite valid consent and paid trigger
  - checkout/payment impacted by Brevo sync issue

### R-09 Consent no-opt-in
- Validates invariants:
  - consent local authority
  - no unauthorized remote write
- Domains touched: Checkout, Brevo
- Failure signals:
  - Brevo sync attempt executed without local consent
  - consent metadata contradictory (`no consent` + `synced`)

## 4) Coverage Gaps
Invariants not fully covered:
- webhook idempotency under mixed gateway/provider edge events is only partially covered (R-04 baseline replay only).
- authority non-override across simultaneous callback + payment return events lacks dedicated stress scenario.

Transitions not explicitly tested:
- payment confirmed while callback bridge is mid-resolution (high-risk transitional race).
- repeated callback invocations with stale query/hash in mixed logged-in/logged-out transitions.

Tier 0 surfaces without full coverage:
- UPE/custom selector coupling resilience to Stripe DOM version drift.
- callback anti-flash behavior under repeated hard refresh on callback URL.
- claim idempotency under repeated onboarding completion retries.

## 5) Release Gate Definition
Minimum journeys required before release:
- R-01
- R-02
- R-03
- R-04
- R-05
- R-07
- R-08
- R-09

Stop conditions:
- any Tier 0 journey fails
- callback convergence fails (loop/ghost loader/flash)
- payment/order authority divergence detected
- consent gate violated (remote sync without local consent)

Escalate to re-audit when:
- two or more failures occur in the same cross-domain transition family
- failures indicate invariant ambiguity instead of implementation bug
- new behavior touches authority boundaries not mapped in current state matrix

## 6) References
- [System Normative Charter](../00-governance/system-normative-charter.md)
- [Unified Callback Contracts](../00-governance/callback-contracts.md)
- [Blast-Radius Consolidation Map](../00-governance/blast-radius-consolidation-map.md)
- [Risk Register](../00-governance/risk-register.md)
- [System State Matrix](../00-governance/system-state-matrix.md)
- [Checkout Payment Selector Audit](../50-ops/audits/checkout-payment-selector-audit.md)
- [My Account Domain Audit](../50-ops/audits/my-account-domain-audit.md)
