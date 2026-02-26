# Docs ↔ Code Alignment Status

## 1) Purpose
This report provides a single architecture-level view of documentation coverage against current code reality.
Use it to quickly identify what is aligned, what is fragile, and which documentation updates should be prioritized next.

## 2) Coverage Matrix (by domain)

### Admin
- Primary docs:
  - [Admin Panel Map](../../20-development/admin-panel-map.md)
  - [Admin Panel Reality Audit](../../50-ops/admin-panel-reality-audit.md)
- Code reality anchors:
  - `admin/class-blackwork-site-settings.php`
- Alignment status: `Mostly aligned`
- Known gaps:
  - Admin field inventory can drift as tabs evolve.
  - Mixed save models remain a long-term consistency risk.
  - AJAX action catalog is documented but high-churn.
- Highest-risk surfaces:
  - provider toggles and conditional sections
  - checkout/payment tab settings persistence
  - mail-marketing admin actions
  - nonce handling per tab/action
  - admin JS conditional rendering logic

### Checkout
- Primary docs:
  - [Checkout Architecture Map](../../30-features/checkout/checkout-architecture-map.md)
- Code reality anchors:
  - `woocommerce/woocommerce-init.php`
  - `woocommerce/templates/checkout/payment.php`
  - `woocommerce/templates/checkout/order-received.php`
  - `assets/js/bw-payment-methods.js`
- Alignment status: `Aligned`
- Known gaps:
  - Dynamic fragment-refresh edge cases remain inherently fragile.
  - Wallet fallback/UI sync behavior is sensitive to DOM contract changes.
  - Cross-domain CTA rendering can drift with template edits.
- Highest-risk surfaces:
  - payment selector contract
  - order-received gating branch
  - wallet scripts + selector sync
  - Stripe UPE cleaner interplay
  - checkout runtime localize payload contract

### Payments
- Primary docs:
  - [Payments Architecture Map](../../40-integrations/payments/payments-architecture-map.md)
- Code reality anchors:
  - `includes/Gateways/class-bw-abstract-stripe-gateway.php`
  - `includes/Gateways/class-bw-google-pay-gateway.php`
  - `includes/Gateways/class-bw-klarna-gateway.php`
  - `includes/Gateways/class-bw-apple-pay-gateway.php`
  - `assets/js/bw-google-pay.js`, `assets/js/bw-apple-pay.js`
- Alignment status: `Aligned`
- Known gaps:
  - Gateway readiness permutations can change with provider updates.
  - UPE/custom selector interoperability is structurally sensitive.
  - Webhook mapping assumptions require periodic verification.
- Highest-risk surfaces:
  - webhook validation/idempotency path
  - process_payment mapping logic
  - wallet capability fallback paths
  - test/live key mode resolution
  - checkout selector coupling

### Auth
- Primary docs:
  - [Auth Architecture Map](../../40-integrations/auth/auth-architecture-map.md)
- Code reality anchors:
  - `woocommerce/templates/myaccount/form-login.php`
  - `includes/woocommerce-overrides/class-bw-my-account.php`
  - `admin/class-blackwork-site-settings.php`
- Alignment status: `Mostly aligned`
- Known gaps:
  - Provider-specific branch complexity can drift with UI changes.
  - OIDC broker assumptions depend on external plugin behavior.
  - Social provider sub-flow boundaries require periodic reconfirmation.
- Highest-risk surfaces:
  - provider switch model
  - callback routing contract
  - login template branch rendering
  - WP session establishment assumptions
  - checkout/auth coupling points

### Supabase
- Primary docs:
  - [Supabase Architecture Map](../../40-integrations/supabase/supabase-architecture-map.md)
- Code reality anchors:
  - `includes/woocommerce-overrides/class-bw-supabase-auth.php`
  - `assets/js/bw-account-page.js`
  - `assets/js/bw-supabase-bridge.js`
  - `woocommerce/templates/myaccount/form-login.php`
- Alignment status: `Aligned`
- Known gaps:
  - Native + OIDC coexistence raises branch-complexity risk.
  - Callback anti-flash logic is sensitive to route/template edits.
  - Onboarding transitions require strict state consistency.
- Highest-risk surfaces:
  - token bridge and callback pipeline
  - onboarding marker transitions
  - invite/provisioning triggers
  - session storage mode behavior
  - guest-order ownership claim attachment

### Brevo
- Primary docs:
  - [Brevo Architecture Map](../../40-integrations/brevo/brevo-architecture-map.md)
- Code reality anchors:
  - `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php`
  - `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`
  - `includes/integrations/brevo/class-bw-brevo-client.php`
- Alignment status: `Aligned`
- Known gaps:
  - Legacy status/value variants still exist in runtime metadata.
  - DOI/single-opt-in branch behavior depends on admin configuration quality.
  - Remote-check vs sync action boundaries need ongoing discipline.
- Highest-risk surfaces:
  - consent hard gate
  - paid-hook trigger timing
  - retry/bulk resync action path
  - attribute fallback/retry chain
  - admin observability actions and filters

## 3) Cross-Domain Contracts Status

### Supabase ↔ Checkout
- Contract doc: [Supabase Architecture Map](../../40-integrations/supabase/supabase-architecture-map.md), [Checkout Architecture Map](../../30-features/checkout/checkout-architecture-map.md)
- Status: `Aligned`
- Known fragility points:
  - order-received guest/auth branching
  - onboarding gate consistency after callback
  - invite redirect path stability

### Payments ↔ Checkout
- Contract doc: [Payments Architecture Map](../../40-integrations/payments/payments-architecture-map.md), [Checkout Architecture Map](../../30-features/checkout/checkout-architecture-map.md)
- Status: `Aligned`
- Known fragility points:
  - selector vs submission method sync
  - wallet fallback precedence
  - fragment refresh re-bind integrity

### Brevo ↔ Checkout
- Contract doc: [Brevo Architecture Map](../../40-integrations/brevo/brevo-architecture-map.md), [Checkout Architecture Map](../../30-features/checkout/checkout-architecture-map.md)
- Status: `Mostly aligned`
- Known fragility points:
  - consent metadata capture at checkout boundaries
  - timing mode (`created` vs `paid`) expectations
  - admin retry semantics vs consent hard gate

### Supabase ↔ Payments ↔ Checkout
- Contract doc: [Supabase-Payments-Checkout Integration Map](../../60-system/integration/supabase-payments-checkout-integration-map.md)
- Status: `Aligned`
- Known fragility points:
  - post-payment provisioning failure handling
  - callback state transitions after paid orders
  - cross-domain CTA consistency (order-received vs account)

## 4) Non-Break Invariants Compliance Snapshot
Normative baseline: [System Normative Charter](../../00-governance/system-normative-charter.md)

- Admin:
  - Non-blocking commerce: `Pass` - admin controls do not execute commerce authority directly.
  - Authority hierarchy: `Pass` - config writes intent; runtime owns authority.
  - Callback discipline: `Risk` - callback-sensitive flags can be altered from admin model changes.
  - Idempotency: `Unknown` - depends on downstream runtime implementation per domain.

- Checkout:
  - Non-blocking commerce: `Pass` - primary commerce path remains authoritative.
  - Authority hierarchy: `Pass` - checkout respects payment/order authority boundaries.
  - Callback discipline: `Risk` - order-received/callback branches are high-coupling surfaces.
  - Idempotency: `Risk` - fragment refresh + JS rebinding can drift if contracts change.

- Payments:
  - Non-blocking commerce: `Pass` - payment flow authority explicit; non-core integrations remain external.
  - Authority hierarchy: `Pass` - webhook/process contracts define payment/order mapping boundaries.
  - Callback discipline: `Pass` - webhook integrity and idempotency are documented as mandatory.
  - Idempotency: `Pass` - normative gateway/webhook convergence model is explicit.

- Auth:
  - Non-blocking commerce: `Pass` - auth gating does not redefine payment completion authority.
  - Authority hierarchy: `Pass` - WP session remains auth authority.
  - Callback discipline: `Risk` - provider/OIDC callback paths are sensitive to routing changes.
  - Idempotency: `Mostly aligned` - bridge has guards, but route/UI drift remains risk.

- Supabase:
  - Non-blocking commerce: `Pass` - provisioning/onboarding degrade without blocking payment completion.
  - Authority hierarchy: `Pass` - onboarding/provisioning state separated from payment/order authority.
  - Callback discipline: `Pass` - anti-flash and callback invariants explicitly documented.
  - Idempotency: `Mostly aligned` - token/login/invite guards exist; repeated edge paths remain sensitive.

- Brevo:
  - Non-blocking commerce: `Pass` - sync failures do not block checkout/payment/order.
  - Authority hierarchy: `Pass` - local consent/state authority is explicit.
  - Callback discipline: `Unknown` - callback model is not primary in Brevo domain.
  - Idempotency: `Pass` - email-upsert and retry convergence model is explicit.

## 5) Next Actions (Strict Priority)
1. Add a governance index file for `docs/00-governance/` and link it from global docs navigation.
2. Normalize status taxonomy across Brevo docs (canonical vs runtime status names) in one short domain note.
3. Add a concise “cross-domain state dictionary” page (auth/payment/order/consent/provisioning flags and owners) under governance.
4. Add explicit versioned “contract change log” section to each architecture map (starting with Checkout, Supabase, Brevo).
5. Create a single “callback contracts” doc that consolidates webhook/callback invariants across Payments and Supabase.
