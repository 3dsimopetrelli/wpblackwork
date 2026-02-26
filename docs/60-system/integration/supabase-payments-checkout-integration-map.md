# Supabase ↔ Payments ↔ Checkout Integration Map

## 1) Purpose + Scope
This document defines the cross-domain coupling contract between Supabase onboarding, checkout runtime, and payment execution.
It formalizes shared state, lifecycle transitions, and failure-safe invariants across the three domains.

Scope boundaries:
- Architecture-level integration model only.
- No code refactor guidance.
- No operational checklist content.

## 2) Integration Surfaces (Coupling Points)

### Checkout templates/hooks depending on auth state
- `woocommerce/templates/global/form-login.php` branches CTA behavior on:
  - `bw_account_login_provider`
  - `bw_supabase_checkout_provision_enabled`
- `woocommerce/templates/checkout/order-received.php` branches post-order CTA and messaging based on:
  - user login state
  - provider/provisioning flags
  - paid vs unpaid order state

### Order-received branching (guest + Supabase provisioning)
- Guest paid orders can show activation-oriented CTA instead of direct account-entry CTA.
- Supabase/provision-enabled branches route users toward account activation callback and invite continuation.

### Payments selector + gateway availability dependency on session/auth
- Payment selector contract is centered on:
  - `woocommerce/templates/checkout/payment.php`
  - `assets/js/bw-payment-methods.js`
  - wallet scripts (`bw-google-pay.js`, `bw-apple-pay.js`)
- Gateway availability is driven primarily by checkout/payment readiness and method state.
- Auth state changes post-order behavior, but must not alter payment submission authority after checkout confirm.

### My Account downloads gating dependency on onboarding marker
- `bw_supabase_onboarded` determines whether account is fully usable for Supabase users.
- Order/download ownership can be attached after bridge/onboarding via `bw_mew_claim_guest_orders_for_user(...)`.
- Result: guest purchase can transition into authenticated download ownership after onboarding completion.

## 3) Shared State Model
Canonical cross-domain state flags and values:

- Authentication runtime:
  - `is_user_logged_in()`
  - `bw_account_login_provider` (`wordpress` | `supabase`)
  - `bw_supabase_onboarded` (1 = onboarded)
- Commerce/payment runtime:
  - order status (`processing` / `completed` / `on-hold` / fail states)
  - payment status (`success` / `pending` / `failed`)
- Provisioning runtime:
  - `bw_supabase_checkout_provision_enabled`

State ownership model:
- Checkout owns order submission and post-order render branch.
- Payments own gateway execution and payment result authority.
- Supabase owns invite/onboarding/claim transition after order success points.

## 4) End-to-End Lifecycle Model

### A) Guest arrives -> checkout -> payment -> order -> provisioning -> invite -> callback -> password -> downloads
1. Guest completes checkout with selected payment method.
2. Payment flow returns success/pending/fail according to gateway contract.
3. On eligible order hooks/statuses, Supabase provisioning attempts invite dispatch (if enabled and configured).
4. User receives order confirmation plus invite/activation path.
5. Invite/callback enters `bw_auth_callback` bridge route.
6. Token bridge establishes WP session; onboarding gate applies when `bw_supabase_onboarded != 1`.
7. Password setup finalizes onboarding marker.
8. Guest orders/download permissions are attached to mapped user; downloads become available in My Account.

### B) Logged-in user -> checkout -> payment -> order-received normal path
1. Logged-in user completes checkout.
2. Payment/order lifecycle proceeds normally.
3. Order-received follows standard confirmed-order path with account access continuity.
4. No onboarding gate is required when user is already valid/onboarded.

### C) Existing Supabase user without onboarding complete -> payment/order -> gated access until password set
1. User can reach post-order/account entry but remains onboarding-gated.
2. Bridge/session can be valid while onboarding remains incomplete.
3. Password completion flips onboarding state and unlocks full account/download surfaces.

## 5) Failure Model & Safe Degrade

### Payment succeeds but provisioning fails
- Expected UX: order remains valid/paid; onboarding CTA can still direct to retry account activation.
- Invariant: payment completion is preserved; no silent loss of paid order.

### Provisioning succeeds but payment pending/failed
- Expected UX: payment status remains authoritative in order-received branch (error/pending messaging retained).
- Invariant: invite/onboarding must not reclassify an unpaid/failed order as paid success.

### Invite expired (`otp_expired`) after successful payment
- Expected UX: callback redirects to configured expired-link destination; user can request new invite.
- Invariant: no order loss; re-entry path remains available without breaking paid order state.

### Bridge fails (user remains logged out)
- Expected UX: callback/loader path degrades to explicit re-entry (account/login/invite resend path), not hidden failure.
- Invariant: no loop and no fake logged-in rendering.

### Duplicate invite/resend loops
- Expected UX: controlled resend behavior with throttling and already-exists handling.
- Invariant: no infinite resend loop, no duplicate state explosion, no orphaned order entitlement.

## 6) Non-Break Invariants
- Payment completion must not depend on Supabase onboarding state.
- Supabase onboarding must not block checkout payment execution.
- Order ownership claim must be idempotent across repeated callbacks or retries.
- No duplicate CTAs should conflict between order-received and My Account routing.
- Callback anti-flash logic must not interfere with payment postback/order-received routes.

## 7) High-Risk Zones (Blast Radius)
Cross-domain hotspots:

- Checkout domain:
  - `woocommerce/templates/checkout/payment.php`
  - `woocommerce/templates/checkout/order-received.php`
  - `woocommerce/templates/global/form-login.php`
  - `assets/js/bw-payment-methods.js`
- Payments domain:
  - `includes/Gateways/class-bw-abstract-stripe-gateway.php`
  - `includes/woocommerce-overrides/class-bw-google-pay-gateway.php`
  - `includes/Gateways/class-bw-apple-pay-gateway.php`
  - `includes/Gateways/class-bw-klarna-gateway.php`
  - `assets/js/bw-google-pay.js`
  - `assets/js/bw-apple-pay.js`
  - `assets/js/bw-stripe-upe-cleaner.js`
- Supabase domain:
  - `includes/woocommerce-overrides/class-bw-supabase-auth.php`
  - `includes/woocommerce-overrides/class-bw-my-account.php`
  - `assets/js/bw-supabase-bridge.js`
  - `assets/js/bw-account-page.js`
  - `woocommerce/templates/myaccount/my-account.php`
  - `woocommerce/templates/myaccount/form-login.php`

## 8) References
- [Supabase Architecture Map](../../40-integrations/supabase/supabase-architecture-map.md)
- [Payments Architecture Map](../../40-integrations/payments/payments-architecture-map.md)
- [Checkout Architecture Map](../../30-features/checkout/checkout-architecture-map.md)
- [Regression Protocol](../../50-ops/regression-protocol.md)
