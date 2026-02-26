# Brevo Integration Architecture Map

## 1) Purpose + Scope
This document defines the official architecture model for the Brevo integration in Blackwork.

Core guarantees:
- GDPR-safe opt-in model based on explicit consent evidence.
- Non-blocking checkout behavior (marketing sync is decoupled from payment completion).
- Idempotent contact synchronization (email-based upsert to Brevo).

Out of scope / non-guarantees:
- No continuous background polling model unless explicitly triggered by existing admin actions.
- No external-source authority override of local consent truth.

## 2) Integration Layers

### Admin Config Layer
- Settings source: `bw_mail_marketing_general_settings` and `bw_mail_marketing_checkout_settings`.
- Core controls: API key, list selection, opt-in mode/timing, channel flags, sender/DOI options, debug flag.

### Consent Capture Layer
- Checkout field injection (`bw_subscribe_newsletter`) and consent metadata persistence.
- Consent evidence saved on order before/after checkout save pipeline.

### Runtime Trigger Layer
- Automatic triggers:
  - order created hook (optional mode)
  - order paid hooks (`processing` / `completed`) as default model
- Manual/admin triggers:
  - order metabox retry
  - orders bulk resync action
  - explicit remote status check actions

### Brevo API Client Layer
- API wrapper: `BW_Brevo_Client`.
- Main operations:
  - account connectivity test
  - contact fetch
  - contact upsert + list assignment
  - double opt-in confirmation trigger

### Local State Layer
- Order meta + user meta are local authority for consent/sync status.
- Brevo is treated as delivery destination, not local truth owner.

### Admin Observability Layer
- Order-level panel/metabox actions (refresh, retry, load lists).
- Orders list columns + filters + bulk resync.
- User profile mail-marketing panel with check/sync actions.

### Logging Layer
- Logging via `wc_get_logger()` with source `bw-brevo`.
- Structured reason/status traces are persisted in meta and log messages.

## 3) Data Model (Local)
Canonical order-level meta keys:

Consent meta:
- `_bw_subscribe_newsletter` (0/1 opt-in flag)
- `_bw_subscribe_consent_at` (timestamp)
- `_bw_subscribe_consent_source` (e.g. checkout)

Sync meta:
- `_bw_brevo_subscribed` (runtime status)
- `_bw_brevo_status_reason` (reason code)
- `_bw_brevo_contact_id` (remote contact id when available)
- `_bw_brevo_last_attempt_at`
- `_bw_brevo_last_attempt_source`
- `_bw_brevo_last_checked_at`
- `_bw_brevo_error_last`

User-level observability mirrors:
- `_bw_brevo_user_status`
- `_bw_brevo_user_reason`
- `_bw_brevo_user_last_checked_at`
- `_bw_brevo_user_contact_id`
- `_bw_brevo_user_error_last`

Allowed canonical statuses for architecture mapping:
- `subscribed`
- `error`
- `skipped_no_consent`
- `skipped_other`

Implementation note:
- Runtime local values may include internal variants like `pending` and `skipped`; the canonical map above is the normalized cross-document model.

## 4) Runtime Flow Model

### A) Checkout opt-in -> order created -> order paid -> sync attempt -> local state update
1. Consent checkbox is captured on checkout.
2. Consent metadata is persisted to order.
3. At configured timing (default: paid), subscription attempt executes.
4. Brevo API call attempts upsert/DOI.
5. Local order meta is updated with status/reason/attempt/check/error fields.

### B) Manual retry from Order metabox -> same consent gate -> sync attempt -> update
1. Admin retry action triggers order resync.
2. Same consent gate (`can_subscribe_order`) is enforced.
3. Brevo call executes only when gate allows.
4. Local state/logs are updated as authoritative audit output.

### C) Bulk resync from Orders list -> safe processing -> update
1. Selected orders run through the same server-side resync path.
2. Each order is processed independently with per-order status result.
3. Local state updates are deterministic and idempotent by email/list model.

### D) Remote check action (“Check Brevo”) -> explicit admin-only remote call -> reconcile view
1. Admin explicitly triggers check action.
2. Remote contact/list status is queried.
3. Admin panel/view state is reconciled.
4. No implicit background mutation model is assumed beyond defined action behavior.

## 5) Consent Security Model (GDPR Gate)
- `can_subscribe_order()` is the hard gate for write-side subscribe operations.

Required consent conditions:
- opt-in flag present and true
- consent timestamp present
- consent source present

Invariants:
- No Brevo subscribe API call without valid consent evidence.
- Retry and bulk actions cannot bypass the consent gate.

## 6) Idempotency & Precedence Rules
- Primary identity key for Brevo sync: email.
- Upsert semantics ensure repeated sync attempts converge on same contact identity.
- Local consent is source-of-truth for eligibility.
- Brevo is downstream destination, not authority for local consent state.
- Subscribe timing precedence:
  - default: `paid` (safer alignment with completed commerce intent)
  - optional: `created` (explicitly configured alternative)

## 7) Failure Model & Safe Degrade

### Invalid API key / missing credentials
- Local status: error or skip-with-reason metadata.
- Logging: reason code/message persisted.
- Checkout impact: none (non-blocking).

### Brevo API down / timeout
- Local status: error.
- Logging: API failure message and attempt trace.
- Checkout impact: none.

### Rate limit / transient provider failure
- Local status: error or retry-later operational state via manual action.
- Logging: provider error persisted.
- Checkout impact: none.

### Attribute validation/schema rejection
- Runtime may retry with reduced/empty attributes.
- Local outcome can remain warning-like/error-like while preserving order flow.
- Checkout impact: none.

## 8) High-Risk Zones (Blast Radius)
- Checkout injection + consent persistence:
  - `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php`
- Paid-hook/runtime trigger path:
  - same frontend class (`maybe_subscribe_on_paid`, `process_subscription`)
- Consent hard gate:
  - `can_subscribe_order()` in frontend/admin handlers
- API client:
  - `includes/integrations/brevo/class-bw-brevo-client.php`
- Order/user admin actions and observability:
  - `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`
  - `admin/js/bw-order-newsletter-status.js`
  - `admin/js/bw-user-mail-marketing.js`
- Orders list columns/filters/bulk resync:
  - `BW_Checkout_Subscribe_Admin` order list hooks and bulk handlers

## 9) Maintenance & Regression References
- [Regression Protocol](../../50-ops/regression-protocol.md)
- [Checkout Architecture Map](../../30-features/checkout/checkout-architecture-map.md)
- [Payments Architecture Map](../../40-integrations/payments/payments-architecture-map.md)
- [Brevo Mail Marketing Architecture](./brevo-mail-marketing-architecture.md)
- [Subscribe Governance](./subscribe.md)

## Normative Brevo Architecture Principles

### 1) Consent Gate Invariant (GDPR hard gate)
- `can_subscribe_order` is mandatory for any write-side subscribe operation (automatic, retry, bulk).
- No consent evidence means no Brevo subscribe API call.

### 2) Non-Blocking Commerce Invariant
- Brevo failures must never block checkout, payment execution, or order placement.
- Subscribe timing configuration must not alter payment/order authority.

### 3) Idempotency & Convergence Invariant
- Email is the primary contact identity for sync/upsert.
- Retries must converge on a single contact state (no duplicate-contact intent from integration flow).
- Local status transitions must remain deterministic for equal inputs.

### 4) Local Authority Invariant
- Consent truth is owned by WordPress/WooCommerce metadata.
- Brevo is delivery destination and synchronization target, not consent source-of-truth.

### 5) Observability Discipline
- No remote API calls should be executed purely during passive list rendering.
- Remote checks are allowed only through explicit admin actions.
- Every remote action must leave a local audit trail (meta updates + logger trace).

### 6) Error Normalization & Sensitive Data Policy
- Customer-facing errors must remain safe and minimal.
- Diagnostic detail belongs to logs/admin diagnostics.
- API keys and raw sensitive payloads must never be logged; only necessary identifiers should appear.

### 7) High-Risk Change Policy (Blast Radius Rule)
- Changes to consent capture, consent gate, paid-hook triggers, or Brevo API client behavior require regression validation.
- Such changes must follow the project regression protocol before release.
