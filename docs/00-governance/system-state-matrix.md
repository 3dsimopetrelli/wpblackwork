# System State Matrix

## 1) Purpose
This matrix defines cross-domain state combinations across Auth, Payment, Order, Supabase onboarding, Provisioning, and Newsletter consent.

It is used to:
- validate refactor safety against governance invariants
- detect invalid state outcomes during regression
- standardize re-audit checks on cross-domain convergence

## 2) Core State Domains
### Auth State
- Anonymous
- Logged-in
- Logged-in (Supabase mode, not onboarded)
- Logged-in (Supabase onboarded)

### Payment State
- Not started
- Processing
- Paid (confirmed by webhook)
- Failed

### Order State
- Created (pending)
- Processing
- Completed
- Cancelled/Failed

### Supabase Identity State
- Not provisioned
- Invited
- OTP verified
- Password set
- Onboarded

### Provisioning State
- No order linked
- Order linked
- Claimed
- Downloads accessible

### Newsletter Consent State
- No consent
- Consent given (local only)
- Synced to Brevo
- Sync error

## 3) Valid State Combinations
### Valid combinations
| ID | Auth | Payment | Order | Supabase Identity | Provisioning | Newsletter Consent | Classification |
|---|---|---|---|---|---|---|---|
| V-01 | Anonymous | Not started | Created (pending) | Not provisioned | No order linked | No consent | Transitional |
| V-02 | Anonymous | Processing | Processing | Not provisioned or Invited | Order linked | No consent or Consent given (local only) | Transitional |
| V-03 | Anonymous | Paid | Processing/Completed | Invited | Order linked | No consent or Consent given (local only) | Transitional |
| V-04 | Logged-in (Supabase mode, not onboarded) | Paid | Processing/Completed | OTP verified or Password set | Claimed | No consent / local / synced | Transitional |
| V-05 | Logged-in (Supabase onboarded) | Paid | Completed | Onboarded | Downloads accessible | No consent / local / synced / sync error | Stable terminal |
| V-06 | Logged-in | Paid | Completed | Not provisioned (WP provider path) | Downloads accessible or Order linked | No consent / local / synced / sync error | Stable terminal |
| V-07 | Logged-in | Failed | Created (pending) or Cancelled/Failed | any non-authority-mutating state | No order linked or Order linked | any | Transitional |
| V-08 | Anonymous | Failed | Cancelled/Failed | Not provisioned | No order linked or Order linked | any | Stable terminal |

### Transitional states
- Payment `Processing` with Order `Processing` and Auth `Anonymous` is valid during checkout return + webhook finalization window.
- Auth callback resolving with Supabase Identity `Invited`/`OTP verified` is valid if convergence target is `Logged-in (Supabase onboarded)` or gated non-onboarded state.
- Consent `Sync error` is valid if local consent remains authoritative and commerce flow is unaffected.

### Stable terminal states
- Commerce complete terminal: `Payment = Paid` + `Order = Completed` + provisioning converged (`Claimed` or `Downloads accessible`).
- Failed terminal: `Payment = Failed` + `Order = Cancelled/Failed` with no forced identity mutation.
- Marketing terminal variants are independent of payment/order authority as long as consent authority is local.

## 4) Invalid / Impossible States
| Invalid Combination | Why Invalid | Invariant Preventing It |
|---|---|---|
| Paid + Order Cancelled/Failed | Payment truth cannot converge to failed order lifecycle without explicit refund/reversal model | Payment authority and order convergence contract |
| Onboarded = true while Auth = Anonymous | Onboarded requires authenticated bridge/session continuity for effective account access | Auth/session authority + onboarding gate |
| Downloads accessible without order linkage | Downloads must be derived from owned order/download entitlement | Provisioning ownership invariant |
| Consent synced to Brevo without local consent | Remote sync cannot create consent truth | Local consent authority doctrine |
| UI-selected gateway differs from submitted `payment_method` | Checkout must submit deterministic selected gateway | Submission integrity rule |
| Callback loop state with no convergence terminal | Callback paths must be repeat-safe and loop-free | Callback convergence invariant |

## 5) High-Risk Transitional States
- Payment just confirmed + Auth callback in progress
  - Risk: order success branch and auth bridge race causing unstable UI.
- Guest paid order + Claim in progress
  - Risk: temporary mismatch between paid order and downloads visibility.
- Callback resolving + Fragment refresh
  - Risk: stale client state if checkout/account listeners rebind unpredictably.
- Email pending + Security tab redirect
  - Risk: pending-email banner/callback params desync.

These transitions require explicit regression validation because they combine independent authority domains under asynchronous events.

## 6) Convergence Rules
- Every flow must converge to a stable terminal state.
- No domain may mutate outside its authority boundary.
- Repeated triggers must converge (idempotent behavior).
- Payment and order authority cannot be overridden by auth, onboarding, or marketing flows.
- Consent state cannot be created by remote destination systems.

## 7) Usage Protocol
During refactor:
- map intended changes to impacted state domains
- verify no invalid combination is introduced
- declare convergence target states before implementation

During re-audit:
- sample high-risk transitions
- check observed states against valid/invalid matrix rules
- open/update risk entries for non-convergent behavior

During regression validation:
- run journeys that traverse high-risk transitional states
- verify final state lands in a valid stable terminal state
- confirm authority boundaries remained intact throughout the flow
