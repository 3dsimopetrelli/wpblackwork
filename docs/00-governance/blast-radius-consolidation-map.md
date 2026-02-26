# Blast-Radius Consolidation Map

## 1) Purpose
This map identifies the highest-risk technical surfaces across Checkout, Payments, Auth/Supabase, Brevo, and My Account.
It is used to scope change risk before implementation and to prevent regressions when refactoring shared flows.

How to use:
- locate the target file/class/hook before changes
- classify the change as Tier 0/1/2
- execute the required validation path based on tier

## 2) Blast Radius Severity Scale
- Tier 0 (System-critical): a change can break global commerce or global authentication continuity.
- Tier 1 (Domain-critical): a change can break a major domain end-to-end but not all domains.
- Tier 2 (Localized): a change can break one UI surface, admin panel, or one bounded behavior.

## 3) Tier 0 Surfaces (System-critical)
### Checkout
- Anchor: `woocommerce/templates/checkout/payment.php`
- Why Tier 0: it defines the effective payment method selection contract for checkout submission.
- Primary failure modes:
  - selected radio state diverges from submitted gateway
  - payment fields render mismatch after fragment updates
  - wallet/custom selector duplication creates invalid submission path
- Dependent docs:
  - [Checkout Architecture Map](../30-features/checkout/checkout-architecture-map.md)
  - [Payments Architecture Map](../40-integrations/payments/payments-architecture-map.md)

- Anchor: `woocommerce/templates/checkout/order-received.php`
- Why Tier 0: it is the post-payment transition surface where commerce truth meets auth/provisioning guidance.
- Primary failure modes:
  - paid order shown with unstable CTA/gating branch
  - guest-to-account transition loops or dead-ends
  - payment-failed state represented as success path
- Dependent docs:
  - [Checkout Architecture Map](../30-features/checkout/checkout-architecture-map.md)
  - [Supabase-Checkout-Payments Integration Map](../60-system/integration/supabase-payments-checkout-integration-map.md)

### Payments
- Anchor: `includes/Gateways/class-bw-abstract-stripe-gateway.php` (`handle_webhook()`)
- Why Tier 0: webhook validation/idempotency is the authority bridge from provider payment truth to Woo order state.
- Primary failure modes:
  - invalid signature accepted/rejected incorrectly
  - duplicate webhook side effects on same order
  - wrong mode secret causes false failures in production
- Dependent docs:
  - [Payments Architecture Map](../40-integrations/payments/payments-architecture-map.md)
  - [Unified Callback Contracts](./callback-contracts.md)

- Anchor: `includes/Gateways/class-bw-google-pay-gateway.php`, `class-bw-apple-pay-gateway.php`, `class-bw-klarna-gateway.php` (`process_payment()`, `handle_webhook()`)
- Why Tier 0: these gateways control payment execution and final order transitions.
- Primary failure modes:
  - order marked pending forever (webhook/process race)
  - gateway return route diverges from webhook outcome
  - payment method appears available without key readiness
- Dependent docs:
  - [Payments Architecture Map](../40-integrations/payments/payments-architecture-map.md)
  - [Checkout Architecture Map](../30-features/checkout/checkout-architecture-map.md)

### Supabase/Auth
- Anchor: `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- Why Tier 0: central auth bridge, onboarding marker lifecycle, token/session continuity, and order claim attachment.
- Primary failure modes:
  - onboarding marker flips incorrectly (`bw_supabase_onboarded`)
  - callback/token bridge creates login loop or stale session
  - guest order claim fails or duplicates ownership mapping
- Dependent docs:
  - [Supabase Architecture Map](../40-integrations/supabase/supabase-architecture-map.md)
  - [Auth Architecture Map](../40-integrations/auth/auth-architecture-map.md)
  - [Cross-Domain State Dictionary](./cross-domain-state-dictionary.md)

- Anchor: `assets/js/bw-supabase-bridge.js` + `woocommerce/woocommerce-init.php` (`bw_mew_supabase_early_invite_redirect_hint`)
- Why Tier 0: callback routing and anti-flash behavior are first-paint critical for auth continuity.
- Primary failure modes:
  - infinite callback redirects
  - pre-auth content flash on My Account callback path
  - auth-in-progress state never cleared
- Dependent docs:
  - [Supabase Architecture Map](../40-integrations/supabase/supabase-architecture-map.md)
  - [Unified Callback Contracts](./callback-contracts.md)

### My Account
- Anchor: `includes/woocommerce-overrides/class-bw-my-account.php`
- Why Tier 0: controls My Account gating, endpoint behavior, auth callback normalization, and profile/settings save gates.
- Primary failure modes:
  - users blocked from account surfaces after valid login
  - set-password and callback routes diverge from session state
  - account endpoint redirects break downloads/orders access
- Dependent docs:
  - [My Account Architecture Map](../30-features/my-account/my-account-architecture-map.md)
  - [Auth Architecture Map](../40-integrations/auth/auth-architecture-map.md)

### Brevo
- Anchor: `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php` (`can_subscribe_order()`, paid hooks)
- Why Tier 0: consent gate and paid-trigger timing protect legal compliance and commerce non-blocking behavior.
- Primary failure modes:
  - API call without valid consent evidence
  - subscribe path blocks checkout/order processing
  - consent metadata overwritten by fallback path
- Dependent docs:
  - [Brevo Architecture Map](../40-integrations/brevo/brevo-architecture-map.md)
  - [System Normative Charter](./system-normative-charter.md)

### Governance callbacks
- Anchor: governance contracts `./callback-contracts.md` and runtime callback endpoints in gateway/auth layers
- Why Tier 0: these invariants constrain callback authority boundaries across payment/auth/order systems.
- Primary failure modes:
  - callback mutates non-owned state domain
  - non-idempotent callback side effects
  - callback result cannot converge to stable terminal state
- Dependent docs:
  - [Unified Callback Contracts](./callback-contracts.md)
  - [Cross-Domain State Dictionary](./cross-domain-state-dictionary.md)

## 4) Tier 1 Surfaces (Domain-critical)
- Anchor: `assets/js/bw-payment-methods.js`
- Why Tier 1: domain-critical checkout UI orchestrator for payment panels and selected method synchronization.
- Primary failure modes:
  - selected visual panel and radio input diverge
  - panel state breaks after checkout fragment refresh
  - hidden wallet/card sections remain active unexpectedly
- Dependent docs:
  - [Checkout Architecture Map](../30-features/checkout/checkout-architecture-map.md)

- Anchor: `assets/js/bw-checkout.js`
- Why Tier 1: checkout interaction flow can fail while payment/auth authority remains intact.
- Primary failure modes:
  - broken DOM listeners after checkout updates
  - free-order and field-state regressions
  - checkout-side UI errors without data corruption
- Dependent docs:
  - [Checkout Architecture Map](../30-features/checkout/checkout-architecture-map.md)

- Anchor: `assets/js/bw-account-page.js` and `assets/js/bw-password-modal.js`
- Why Tier 1: auth/onboarding UX can fail for My Account while global commerce still works.
- Primary failure modes:
  - OTP/create-password screens stuck
  - password modal cannot complete onboarding
  - stale pending-email/auth-flow client state
- Dependent docs:
  - [My Account Architecture Map](../30-features/my-account/my-account-architecture-map.md)
  - [Supabase Architecture Map](../40-integrations/supabase/supabase-architecture-map.md)

- Anchor: `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`
- Why Tier 1: admin observability/retry actions for Brevo can fail while storefront checkout remains functional.
- Primary failure modes:
  - retry/check endpoints fail silently
  - list columns/filters show stale sync state
  - bulk resync actions misreport results
- Dependent docs:
  - [Brevo Architecture Map](../40-integrations/brevo/brevo-architecture-map.md)

## 5) Tier 2 Surfaces (Localized)
- Anchor: `assets/js/bw-stripe-upe-cleaner.js`
- Why Tier 2: presentation cleanup layer around UPE/card UI.
- Primary failure modes:
  - duplicated card headings
  - cosmetic payment accordion inconsistencies
  - selector visual noise without payment state corruption
- Dependent docs:
  - [Payments Architecture Map](../40-integrations/payments/payments-architecture-map.md)

- Anchor: `assets/js/bw-checkout-notices.js`
- Why Tier 2: checkout notices placement/styling only.
- Primary failure modes:
  - notices render in wrong column
  - duplicate notice blocks
  - notice styling drift
- Dependent docs:
  - [Checkout Architecture Map](../30-features/checkout/checkout-architecture-map.md)

- Anchor: `assets/js/bw-my-account.js`
- Why Tier 2: tab switching and field UX enhancements on settings page.
- Primary failure modes:
  - tab activation glitches
  - floating-label visual regressions
  - password toggle UI mismatch
- Dependent docs:
  - [My Account Architecture Map](../30-features/my-account/my-account-architecture-map.md)

## 6) Cross-Domain Coupling Hotspots
- Collision point: order-received gating (`woocommerce/templates/checkout/order-received.php`)
- Domains colliding: Checkout + Payments + Auth/Supabase + My Account
- Protecting invariant: payment success remains authority; provisioning/auth can gate UI only, never payment truth.

- Collision point: `bw_auth_callback` bridge (`assets/js/bw-supabase-bridge.js`, `woocommerce/woocommerce-init.php`, `class-bw-my-account.php`)
- Domains colliding: Auth + Supabase + My Account
- Protecting invariant: callback is idempotent, anti-flash safe, and loop-free.

- Collision point: downloads visibility gate (`bw_supabase_onboarded`, guest-order claim in `class-bw-supabase-auth.php`)
- Domains colliding: Supabase + My Account + Orders
- Protecting invariant: order ownership claim is idempotent and does not mutate payment/order authority.

- Collision point: checkout paid-hook timing (`woocommerce_order_status_processing/completed`, `woocommerce_payment_complete`, `woocommerce_thankyou`)
- Domains colliding: Payments + Supabase provisioning + Brevo subscription
- Protecting invariant: non-blocking commerce; downstream sync/provision failures cannot roll back paid order truth.

- Collision point: payment selector rendering (`woocommerce/templates/checkout/payment.php`, `assets/js/bw-payment-methods.js`)
- Domains colliding: Checkout + Payments + Wallet integrations
- Protecting invariant: visible selected method, radio state, and submitted method remain deterministic.

## 7) Change Protocol (Normative)
For Tier 0 and Tier 1 changes:
- Regression protocol is mandatory: [Regression Protocol](../50-ops/regression-protocol.md)
- Mandatory governance review:
  - [Unified Callback Contracts](./callback-contracts.md)
  - [Cross-Domain State Dictionary](./cross-domain-state-dictionary.md)

Minimum verification surfaces:
- checkout payment selection + submit integrity
- payment webhook/callback idempotency path
- auth callback + My Account anti-flash path
- order-received guest/onboarded branching
- Brevo consent gate (no-consent = no remote write)

## 8) References
- [System Normative Charter](./system-normative-charter.md)
- [Cross-Domain State Dictionary](./cross-domain-state-dictionary.md)
- [Unified Callback Contracts](./callback-contracts.md)
- [Docs-Code Alignment Status](./docs-code-alignment-status.md)
- [Checkout Architecture Map](../30-features/checkout/checkout-architecture-map.md)
- [My Account Architecture Map](../30-features/my-account/my-account-architecture-map.md)
- [Payments Architecture Map](../40-integrations/payments/payments-architecture-map.md)
- [Auth Architecture Map](../40-integrations/auth/auth-architecture-map.md)
- [Supabase Architecture Map](../40-integrations/supabase/supabase-architecture-map.md)
- [Brevo Architecture Map](../40-integrations/brevo/brevo-architecture-map.md)
- [Supabase-Payments-Checkout Integration Map](../60-system/integration/supabase-payments-checkout-integration-map.md)
- [Regression Protocol](../50-ops/regression-protocol.md)
