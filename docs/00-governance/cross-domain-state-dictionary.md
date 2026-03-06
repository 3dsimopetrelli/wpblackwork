# Cross-Domain State Dictionary

## 1) Purpose
This document is the single authoritative reference for state flags, ownership authority, and cross-domain read/write boundaries across core Blackwork domains.

Normative reference:
- [System Normative Charter](./system-normative-charter.md)

---

## 2) State Domains Overview Table

| Domain | Canonical State Object | Authority Owner | Stored Where | Can Be Read By | Can Be Written By | Must NEVER Override |
|---|---|---|---|---|---|---|
| Payment | Provider payment outcome + local payment mapping | Payment provider + gateway contract | Provider payload + gateway/order payment meta | Checkout runtime, order rendering, provisioning logic (read-only) | Gateway + validated webhook handlers | Order authority, auth authority |
| Order | Woo order status + order meta lifecycle | WooCommerce | `shop_order` status + order meta | Payments, auth gating, provisioning, marketing sync | Woo order lifecycle handlers + controlled integrations | Payment UI transient state, marketing status |
| Auth (WP session) | Logged-in / anonymous / expired session | WordPress session system | WP auth cookies/session context | Checkout, My Account, callback bridge, gating logic | WP auth handlers + bridge session establishment | Payment status, order completion, marketing sync |
| Supabase Identity | Invite/callback/onboarding identity state | Supabase auth + bridge layer | Supabase tokens + WP user/order meta markers | Auth flows, provisioning, account gating | Supabase bridge handlers + onboarding handlers | Payment completion, newsletter outcomes |
| Supabase Provisioning | Order-linked provisioning and ownership attachment state | Provisioning handlers | Order meta + user meta + downloadable permissions linkage | My Account access layer, order-received/account transitions | Provisioning hooks + claim/attach handlers | Newsletter/admin display toggles |
| Newsletter Consent (Brevo) | Consent evidence + sync outcome state | Local consent metadata | Order meta + user meta + Brevo sync meta | Brevo sync runtime, admin observability | Consent capture + gated sync handlers | Payment/order authority, auth authority |
| Checkout Runtime (UI-only transient) | Current selected method + wallet visibility + fragment refresh state | Frontend runtime JS | Browser DOM + in-memory state | Checkout UI scripts | Checkout JS only | Persisted cross-request state, order/payment authority |

---

## 3) Domain State Definitions

### 3.1 Payment State
Defines:
- payment intent/result status,
- provider confirmation outcome,
- webhook idempotency marker/processed-event guard.

Authority:
- payment provider and gateway/webhook contract.

Never overridden by:
- Auth, Supabase provisioning, Brevo sync.

---

### 3.2 Order State (WooCommerce)
Defines:
- order status lifecycle (`pending`/`processing`/`completed`/failure states),
- order meta ownership and persistence boundaries.

Authority:
- WooCommerce order lifecycle.

Never overridden by:
- payment UI transient state, auth flow, marketing sync state.

---

### 3.3 Auth State (WP Session)
Defines:
- logged-in,
- anonymous,
- session expired.

Authority:
- WordPress session/auth system.

Never overridden by:
- Supabase provisioning state, payment state.

---

### 3.4 Supabase Identity State
Defines:
- invited,
- otp_verified,
- password_set,
- onboarded marker (`bw_supabase_onboarded`).

Authority:
- Supabase identity flow + bridge layer that establishes local session continuity.

Convergence contract:
- Marker writes must be deterministic and repeat-safe across callback/token-login/modal paths.
- Downgrade from onboarded (`1`) to non-onboarded (`0`) is allowed only in explicit onboarding-pending contexts.
- Authenticated stale-marker reconciliation may promote marker to onboarded only when invite/error pending signals are absent.

Never overridden by:
- order completion alone.

---

### 3.5 Supabase Provisioning State
Defines:
- order-linked provisioning eligibility,
- claimed ownership attachment,
- files/download accessibility readiness.

Authority:
- provisioning handlers and ownership-claim routines.

Never overridden by:
- newsletter status, admin display toggles.

---

### 3.6 Newsletter Consent State (Brevo)
Defines:
- consent given flag,
- consent timestamp,
- consent source,
- Brevo remote sync status.

Authority:
- local consent metadata.

Rule:
- remote API status does not redefine local consent truth.

---

### 3.7 Checkout Runtime State (Transient)
Defines:
- selected payment method,
- wallet button/method visibility,
- fragment refresh/rebind state.

Authority:
- checkout frontend runtime scripts.

Rule:
- this state must remain request-transient and must not become persisted business truth.

---

## 4) Cross-Domain Write Prohibitions (Normative Rules)
- Payment completion must not auto-mark Supabase onboarded.
- Newsletter subscription must not alter order/payment state.
- Auth login must not alter payment authority.
- Order creation must not imply identity completion.
- Provisioning failure must not revert payment/order state.

---

## 5) Idempotency & Convergence Guarantees
- Webhook idempotency: repeated payment events must converge to one stable local order/payment outcome.
- Supabase callback idempotency: repeated callback/bridge transitions must converge without loops or duplicate ownership effects.
- Brevo upsert convergence: repeated sync attempts converge on one contact identity/state intent.
- Order-provision reattachment safety: repeated claim/attach routines must be safe and non-duplicative.
