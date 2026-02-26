# Technical Hardening Plan

## 1) Purpose
This plan defines a controlled, docs-first hardening sequence for Tier 0 system-critical surfaces.
It is used to reduce regression risk before any refactor by locking invariants, verification expectations, and release stop signals.

Usage model:
- read this plan before Tier 0 changes
- execute phase-by-phase validation gates
- release only when Tier 0 acceptance criteria are satisfied

## 2) Tier 0 Hardening Targets (from Blast Radius Map)
- Surface: `woocommerce/templates/checkout/payment.php`
  - What can break: payment selector state diverges from submitted gateway; fragment refresh leaves invalid active state.
  - Protecting invariant: visible selected method, radio state, and submit payload must remain deterministic and synchronized.
  - Must verify: method select -> fragment refresh -> submit path.

- Surface: `woocommerce/templates/checkout/order-received.php`
  - What can break: paid orders show unstable post-order branch; guest/account CTA loops.
  - Protecting invariant: payment truth is authoritative; auth/provisioning can gate UI only.
  - Must verify: paid order guest path, logged-in path, failed payment path.

- Surface: `includes/Gateways/class-bw-abstract-stripe-gateway.php` (`handle_webhook()`)
  - What can break: webhook idempotency and signature validation failures mutate order state incorrectly.
  - Protecting invariant: authenticated webhook events converge order state exactly once.
  - Must verify: duplicate webhook replay safety and invalid signature rejection.

- Surface: gateway handlers in `class-bw-google-pay-gateway.php`, `class-bw-apple-pay-gateway.php`, `class-bw-klarna-gateway.php`
  - What can break: process/webhook race leaves inconsistent order/payment state.
  - Protecting invariant: gateway return path must converge with webhook outcome deterministically.
  - Must verify: return flow + webhook completion alignment per gateway.

- Surface: `includes/woocommerce-overrides/class-bw-supabase-auth.php`
  - What can break: onboarding marker corruption, callback/session instability, non-idempotent order claim.
  - Protecting invariant: auth/provisioning transitions are idempotent and never override payment/order authority.
  - Must verify: callback login, onboarding completion, repeated claim safety.

- Surface: `assets/js/bw-supabase-bridge.js` + `woocommerce/woocommerce-init.php` callback preload
  - What can break: auth callback loops, stale callback rendering, pre-auth flash.
  - Protecting invariant: callback path resolves session before rendering sensitive account surfaces.
  - Must verify: callback from invite/recovery, stale callback cleanup, no-flash first paint.

- Surface: `includes/woocommerce-overrides/class-bw-my-account.php`
  - What can break: endpoint gating blocks valid users or misroutes set-password/callback flows.
  - Protecting invariant: My Account routing respects WP session authority + onboarding gate rules.
  - Must verify: dashboard/downloads/orders/settings/logout + set-password endpoint transitions.

- Surface: `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php` (`can_subscribe_order()` + paid hooks)
  - What can break: consent bypass, subscribe path blocking commerce, inconsistent consent meta.
  - Protecting invariant: no consent = no API call; Brevo failures never block payment/order.
  - Must verify: no-opt-in order, opt-in paid order, retry path non-blocking behavior.

- Surface: governance callback contract (`./callback-contracts.md`) as runtime review gate
  - What can break: callback handlers mutate non-owned states or fail to converge.
  - Protecting invariant: callback authority boundaries are enforced across payment/auth/order domains.
  - Must verify: callback side effects mapped only to owned state domains.

## 3) Hardening Actions (Sequenced)
### Phase 1: Callback & webhook resilience hardening
- Objective: lock callback/webhook authority and idempotency behavior.
- Scope:
  - `class-bw-abstract-stripe-gateway.php` webhook path
  - gateway webhook handlers (Google Pay / Apple Pay / Klarna)
  - Supabase auth callback bridge model
- Acceptance criteria:
  - callback/webhook ownership boundaries documented and reviewed
  - replay-safe/idempotent behavior explicitly verified on critical paths
  - invalid callback/webhook input does not mutate authoritative states
- Rollback principle (docs-level):
  - if convergence or ownership cannot be demonstrated, freeze release and revert to previous documented callback behavior baseline.

### Phase 2: Checkout render stability hardening
- Objective: prevent selector/fragment/UPE regressions in checkout payment UI.
- Scope:
  - `woocommerce/templates/checkout/payment.php`
  - `assets/js/bw-payment-methods.js`
  - UPE interaction surfaces documented in payments/checkout maps
- Acceptance criteria:
  - selector contract preserved after fragment refresh
  - no duplicated active methods or conflicting UPE/custom selector states
  - submit method always matches active UI/radio state
- Rollback principle (docs-level):
  - if selector determinism fails, restore last known stable payment rendering contract and block rollout.

### Phase 3: Supabase bridge stability hardening
- Objective: stabilize callback, onboarding, anti-flash, and claim idempotency.
- Scope:
  - `class-bw-supabase-auth.php`
  - `assets/js/bw-supabase-bridge.js`
  - callback preload and routing helpers in `woocommerce-init.php`
- Acceptance criteria:
  - no infinite callback loops
  - no pre-resolution My Account flash during callback path
  - onboarding marker and guest claim paths are repeat-safe
- Rollback principle (docs-level):
  - if callback stability is not guaranteed, force documented safe degrade to explicit login/retry path and stop release.

### Phase 4: My Account stability hardening
- Objective: protect My Account end-to-end navigation, settings, and gated access flows.
- Scope:
  - `class-bw-my-account.php`
  - My Account templates (navigation, edit-account, downloads, orders, set-password)
  - account-side JS controllers influencing state transitions
- Acceptance criteria:
  - navigation and endpoint routing remain stable across auth states
  - settings updates preserve authority boundaries (WP/Woo/Supabase lanes)
  - downloads/orders access follows documented claim/onboarding contracts
- Rollback principle (docs-level):
  - if endpoint gating misroutes authenticated users, revert to last stable route matrix before release.

### Phase 5: Brevo non-blocking + consent hardening
- Objective: confirm and preserve existing consent-gated, non-blocking behavior.
- Scope:
  - checkout consent capture and meta persistence
  - paid hook subscribe timing
  - admin retry/check observability path
- Acceptance criteria:
  - consent gate remains mandatory in all write paths
  - Brevo failures do not affect checkout/payment/order completion
  - local audit state remains coherent after retries/checks
- Rollback principle (docs-level):
  - if consent or non-blocking invariant is uncertain, disable write-side subscription behavior until invariants are restored.

## 4) Regression Minimum Suite (Tier 0)
Before release, the following minimum journeys must pass:
- Guest checkout with successful payment -> stable order-received branch -> valid account/onboarding CTA.
- Logged-in checkout with successful payment -> deterministic order status and account visibility.
- Payment webhook replay simulation -> no duplicate side effects.
- Invalid webhook/callback input simulation -> no unauthorized state mutation.
- Supabase invite/callback flow -> no loop, no flash, onboarding completion converges.
- My Account core surfaces -> dashboard, downloads, orders, settings, logout.
- Brevo consent gate:
  - opt-in path triggers subscribe attempt after paid signal
  - no-opt-in path performs no remote subscribe call

## 5) Stop Conditions
Stop release immediately if any of the following occurs:
- Payment authority is ambiguous (UI success with non-converged payment state).
- Webhook/callback idempotency cannot be demonstrated.
- Callback loops, ghost loaders, or pre-auth content flash appear.
- My Account route/gating prevents valid authenticated access.
- Consent-gated marketing writes occur without valid consent evidence.
- Any Tier 0 journey in the minimum suite fails.

## 6) References
- [Blast-Radius Consolidation Map](./blast-radius-consolidation-map.md)
- [Unified Callback Contracts](./callback-contracts.md)
- [Cross-Domain State Dictionary](./cross-domain-state-dictionary.md)
- [System Normative Charter](./system-normative-charter.md)
- [Regression Protocol](../50-ops/regression-protocol.md)
