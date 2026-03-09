# Payments Integration Architecture Map

## 1) Purpose + Scope
This document is the official architecture reference for the Payments integration domain.
It defines how payment configuration, gateway registration, checkout rendering, wallet orchestration, Stripe execution, and webhook-driven order updates work together in the current implementation.

Scope:
- Google Pay, Apple Pay, Klarna, Stripe coupling, checkout selector coupling.
- Runtime architecture and contracts only.
- No code refactor guidance and no implementation change instructions.

## 2) Payment Stack Layers

### Layer A: Admin toggle & key layer
Primary writer: `admin/class-blackwork-site-settings.php` (Checkout > payment sub-tabs).

Persisted keys include:
- Google Pay: `bw_google_pay_*`
- Klarna: `bw_klarna_*`
- Apple Pay: `bw_apple_pay_*`

Admin layer also provides:
- connection test AJAX endpoints
- Apple domain verification endpoint
- admin scripts for mode pills/test actions

### Layer B: WooCommerce gateway registration
Bootstrap path: `woocommerce/woocommerce-init.php`.

Registration/loading model:
- `add_filter('woocommerce_payment_gateways', 'bw_mew_add_google_pay_gateway')`
- gateway classes loaded from:
  - `includes/woocommerce-overrides/class-bw-google-pay-gateway.php`
  - `includes/Gateways/class-bw-klarna-gateway.php`
  - `includes/Gateways/class-bw-apple-pay-gateway.php`
  - shared base: `includes/Gateways/class-bw-abstract-stripe-gateway.php`

Google Pay runtime authority note (2026-03-09):
- Active runtime source of truth is the legacy class:
  - `includes/woocommerce-overrides/class-bw-google-pay-gateway.php`
- `includes/Gateways/class-bw-google-pay-gateway.php` exists in repository but is not bootstrapped in current runtime path.
- Both cannot coexist at runtime because they define the same class name (`BW_Google_Pay_Gateway`) and gateway id (`bw_google_pay`).
- Until convergence is approved as a dedicated architecture task, Google Pay fixes must target the active legacy runtime path.

Operational invariant (CHECKOUT-01):
- Gateway registration depends on class availability at runtime (`class_exists(...)` checks).
- If a custom gateway class is not required before registration, it will not enter WooCommerce gateway lists.
- Confirmed incident: Klarna visibility regression occurred when `BW_Klarna_Gateway` was not loaded in bootstrap; result was missing `bw_klarna` in `$available_gateways` and no checkout render row.
- Resolution anchor: explicit class loading in `woocommerce/woocommerce-init.php` for Klarna dependency chain.

### Layer C: Stripe SDK integration
Stripe is integrated at multiple levels:
- server side: `BW_Stripe_Api_Client` + gateway API requests
- client side: `https://js.stripe.com/v3/` in checkout
- Woo Stripe appearance customization via:
  - `wc_stripe_elements_options`
  - `wc_stripe_elements_styling`
  - `wc_stripe_upe_params`

### Layer D: Wallet orchestration layer
Wallet runtime JS:
- `assets/js/bw-google-pay.js`
- `assets/js/bw-apple-pay.js`

Responsibilities:
- capability checks (`canMakePayment`)
- hidden method field injection (`bw_google_pay_method_id`, `bw_apple_pay_method_id`)
- fallback to non-wallet gateway when unavailable
- sync with checkout refresh lifecycle (`updated_checkout`)

### Layer E: Checkout selector coupling
Selector core:
- template: `woocommerce/templates/checkout/payment.php`
- orchestrator: `assets/js/bw-payment-methods.js`

Contract:
- selected radio method is the effective submission method
- wallet button visibility and place-order visibility are synchronized from selector state

### Layer F: Webhook processing layer
Webhook endpoints handled per gateway via `woocommerce_api_{gateway_id}`.

Core webhook logic lives in `BW_Abstract_Stripe_Gateway::handle_webhook()` and maps Stripe PaymentIntent events to WooCommerce order state.

Google Pay webhook runtime-path decision:
- Active webhook handler path:
  - `includes/woocommerce-overrides/class-bw-google-pay-gateway.php` (`/?wc-api=bw_google_pay`)
- Convergence to the abstract Stripe-backed Google class is deferred and tracked as architecture cleanup follow-up.

## 3) Runtime Flow Model
Lifecycle:

1. Admin Intent
- Merchant enables/disables methods and configures keys/secrets/mode-related settings.

2. Gateway Registration
- WooCommerce bootstrap loads gateway classes and hooks.
- gateway availability list is filtered before checkout render.

3. Eligibility
- Eligibility is derived from combined conditions:
  - custom enable toggles (`bw_*_enabled`)
  - WooCommerce gateway enabled status
  - key readiness
  - context/runtime constraints

4. Checkout Render
- `payment.php` renders payment methods and readiness hints.
- payment selector structure is output for JS orchestration.

5. Client Capability
- wallet scripts instantiate Stripe PaymentRequest.
- `canMakePayment` determines device/browser wallet support.
- unavailable wallet methods are visually/behaviorally disabled with fallback path.

6. Stripe Execution
- gateway `process_payment()` creates PaymentIntent, handles immediate statuses, persists PI metadata.
- wallet hidden method ID participates in request payload.

7. Webhook
- Stripe webhook is signature-validated.
- event id deduplication and PI/order consistency checks run.

8. Order Status Update
- `payment_intent.succeeded` -> complete payment
- `payment_intent.payment_failed` -> failed
- `payment_intent.processing` -> on-hold
- `payment_intent.canceled` -> cancelled

## 4) Gateway Contract
Each gateway implementation is expected to satisfy these responsibilities.

### `is_available()`
- Determines if method can be offered in checkout context.
- In current codebase, explicit `is_available()` is implemented in the Google Pay override class; Klarna/Apple rely on parent/selector/readiness coupling.

### `process_payment()`
- Validates order and gateway-specific payload.
- Verifies key readiness.
- Creates/confirms Stripe PaymentIntent.
- Persists PI and mode metadata.
- Returns WooCommerce result + redirect, or customer-safe error.

### Key readiness
- Mode-aware key resolution (test/live) is required.
- Missing or invalid key material must fail fast with safe message + log.

### Error handling
- Customer errors should be generic and actionable.
- Detailed provider diagnostics stay in logs/order notes.

### Webhook validation
- Signature verification with gateway webhook secret is mandatory.
- Invalid signature/payload must terminate with non-success HTTP status.

### Order status mapping
- Stripe event outcomes must deterministically map to WooCommerce statuses.
- Event replay must be idempotent (processed-event tracking and PI consistency checks).

## 5) Wallet Model

### Google Pay
- Runtime script: `assets/js/bw-google-pay.js`
- Uses Stripe PaymentRequest with localized params from `bwGooglePayParams`.
- Injects `bw_google_pay_method_id` for gateway processing.

### Apple Pay
- Runtime script: `assets/js/bw-apple-pay.js`
- Uses Stripe PaymentRequest with localized params from `bwApplePayParams`.
- Injects `bw_apple_pay_method_id` for gateway processing.

### Klarna
- Implemented as Stripe-backed custom gateway (`BW_Klarna_Gateway`) with selector and readiness checks in checkout template.

### Express fallback logic
- Apple Pay runtime includes explicit express fallback flag (`enableExpressFallback`).
- Selector orchestration ensures only one actionable submission path is visible.

### Stripe UPE interaction
- Stripe UPE styling is overridden by filters.
- `assets/js/bw-stripe-upe-cleaner.js` removes conflicting UPE rows to keep custom selector coherent.

## Wallet Runtime Ownership

### Authoritative owner
- Stripe/WCPay express runtime owns wallet launch lifecycle in checkout.
- Native runtime containers include:
  - `#wc-stripe-express-checkout-element`
  - `#wc-stripe-payment-request-wrapper`
  - `#wcpay-express-checkout-element`

### Blackwork ownership boundary
- In this integration setup, Blackwork must not directly trigger wallet launch.
- Parallel custom PaymentRequest launcher paths are unsupported and non-authoritative.
- Custom Google Pay / Apple Pay launchers are disabled under this ownership model.

### Disabled-state cleanliness contract
- Disabled must mean:
  - no payment-row render
  - no wallet launcher render
  - no custom wallet JS enqueue
  - no custom runtime init/listeners
  - no dead wallet wrapper DOM residue
- Wrapper gating in `woocommerce/templates/checkout/payment.php` enforces clean disabled DOM output.

### Future integration rule
- Any future wallet integration must use authoritative Stripe/WCPay integration points only.
- Do not implement parallel custom wallet launcher runtimes when Stripe/WCPay already owns the wallet lifecycle.

## Stripe Payment Element UI Control Boundary

### Authoritative control point
- Stripe Payment Element UI structure must be controlled through supported Stripe/Woo params pipeline, not by targeting internal rendered subnodes.
- In Blackwork this control point is:
  - filter `wc_stripe_upe_params`
  - function `bw_mew_customize_stripe_upe_appearance()` in `woocommerce/woocommerce-init.php`.

### Supported approach
- Payment Element layout/appearance changes must be applied via supported params (for example layout type), not via fragile CSS overrides on Stripe internal `p-*` class hierarchy.
- Adopted configuration for this issue:
  - `layout.type = tabs`
  - applied via `wc_stripe_upe_params` in `bw_mew_customize_stripe_upe_appearance()`.

### Unsupported primary approach
- Using external CSS as primary control to remove Stripe internal card/icon subviews is non-deterministic and not architecture-safe as a long-term contract.
- Internal selector/appearance-rule hacks targeting Stripe mini-subview internals are exploratory only and not the final governance-approved solution path.

### Escalation rule
- If supported layout params cannot satisfy UX requirements, raise a dedicated architecture task before introducing lower-level Stripe integration alternatives.

## 6) Precedence & Mode Rules

### Custom toggle vs Woo enable
- Effective availability is intersection logic, not single-flag logic:
  - custom toggle intent (`bw_*_enabled`)
  - WooCommerce gateway enabled state
  - runtime readiness and context

### Test vs Live keys
- Google Pay has explicit `bw_google_pay_test_mode` with test/live key switching.
- Base/derived gateway logic resolves secrets/webhook keys by mode.

### UPE vs custom selector
- Custom selector (`payment.php` + `bw-payment-methods.js`) is the operative checkout payment UI.
- Stripe UPE components are visually constrained/cleaned to prevent duplicate method controls.

## Checkout Payment Selector Determinism Model

### 1) Explicit User Selection Authority
- The explicit payment method selected by the user is authoritative.
- Runtime variables enforcing this rule:
  - `BW_PENDING_USER_SELECTION`
  - `BW_LAST_EXPLICIT_SELECTION`
- These sources must always take priority over transient DOM states.

### 2) WooCommerce Refresh Cycles
- WooCommerce frequently re-renders checkout during:
  - checkout validation errors
  - shipping changes
  - address changes
  - payment updates
- These refresh cycles can temporarily reset the DOM checked radio.
- The system must never treat transient DOM refresh state as authoritative.

### 3) Payment Method Persistence Contract
- Explicit user choice must survive:
  - checkout validation errors
  - `update_checkout` / `updated_checkout` events
  - fragment refresh
  - wallet cancel/return flows
  - focus/visibility/page restore
- Fallback to another payment method is allowed only when the previously selected method becomes unavailable.

### 4) Wallet Special Handling
- Wallet methods (Apple Pay / Google Pay) have additional runtime states:
  - checking
  - available
  - unavailable
- Wallet availability state must not override explicit user selection unless the wallet is truly unavailable.

### 5) UI Convergence Rule
- At all times the following must remain aligned:
  - selected radio input
  - visible accordion panel
  - active payment controls
- Any mismatch between these three states is a checkout integrity defect.

### 6) Governance Rule
- Any future modification touching:
  - `bw-payment-methods.js`
  - wallet availability logic
  - checkout payment selector UI
  must preserve this determinism contract.
- Violations require:
  - governance review
  - regression testing against selector persistence scenarios.

## 7) High-Risk Zones
Blast-radius hotspots:
- `woocommerce/templates/checkout/payment.php`
  - central render contract for payment methods and readiness messaging.
- `assets/js/bw-payment-methods.js`
  - source of truth for selector state, fallback method switching, and checkout button visibility.
- `assets/js/bw-google-pay.js`
- `assets/js/bw-apple-pay.js`
  - wallet capability gating + hidden-field coupling + checkout submission path.
- `includes/Gateways/class-bw-abstract-stripe-gateway.php`
  - shared webhook/payment/refund semantics across gateway family.
- `woocommerce/woocommerce-init.php`
  - registration/orchestration nexus (gateway load, enqueue, Stripe/UPE filters).

Typical failure classes in these zones:
- selected gateway mismatch vs submitted gateway
- wallet shown while unavailable (or hidden while available)
- duplicate/conflicting payment UIs
- webhook-driven order state drift

## 8) Maintenance & Regression References
- Regression protocol: [`docs/50-ops/regression-protocol.md`](../../50-ops/regression-protocol.md)
- Payments runbook: [`docs/50-ops/runbooks/payments-runbook.md`](../../50-ops/runbooks/payments-runbook.md)
- Checkout architecture dependency: [`docs/30-features/checkout/checkout-architecture-map.md`](../../30-features/checkout/checkout-architecture-map.md)

## Normative Payments Architecture Principles

### 1) Readiness Gating (Keys + Toggle Discipline)
- Payment toggles express integration intent, not automatic runtime availability.
- A payment method is actionable only if all required key material and gateway readiness conditions are valid for the active mode.
- Rendering and submission paths must enforce the same readiness gates.

### 2) Deterministic Order State Transitions
- Payment lifecycle must map to deterministic WooCommerce order statuses.
- Equivalent provider events must never produce divergent statuses for the same order state.
- Manual/UI status updates must not contradict gateway/webhook authority for payment completion/failure events.

### 3) Webhook Integrity + Idempotency Invariant
- Webhook signature validation is mandatory before any state mutation.
- Event replay must be idempotent through processed-event tracking and PaymentIntent consistency checks.
- Event claim/reclaim must remain concurrency-safe; stale claim recovery must use compare-and-swap semantics to avoid dual execution during parallel deliveries.
- A webhook event may be safely ignored only when invariant checks fail (invalid signature, mismatched gateway/order/PI, already processed event).

### 4) Wallet Capability Discipline
- Wallet methods (Google Pay, Apple Pay) must be shown as actionable only when both:
  - server eligibility passes
  - client capability checks succeed (`canMakePayment` or equivalent)
- If capability fails after render, UI must downgrade to a safe non-wallet path without ambiguous state.

### 5) UPE vs Custom Selector Non-Duplication Invariant
- The custom selector (`payment.php` + selector JS) is the canonical user-facing payment state model.
- Stripe UPE artifacts must not create duplicate or conflicting selection controls.
- Any UPE cleanup/styling logic must preserve a single, unambiguous selectable method per effective payment path.

### 6) Mode Consistency (Test/Live Isolation)
- Test and live credentials, webhook secrets, and mode-dependent behavior must remain isolated.
- A request executed in one mode must never read secrets from the opposite mode except where an explicit, documented fallback is intentionally designed.
- Operational logs and error diagnostics should include mode context for traceability.

### 7) Error Normalization Contract
- Provider/API errors must be normalized into:
  - customer-safe messages for checkout UX
  - detailed diagnostics for logs/order notes
- Error handling must not leak sensitive key/secret details.
- Equivalent failures across gateways should produce coherent user-facing outcomes.

### 8) High-Risk Change Policy (Blast-Radius Rule)
- Changes touching the payment state contract or webhook semantics are high-risk by definition.
- High-risk surfaces include:
  - `woocommerce/templates/checkout/payment.php`
  - selector/wallet scripts
  - gateway classes
  - `BW_Abstract_Stripe_Gateway`
  - payments orchestration in `woocommerce-init.php`
- Any change in these surfaces requires full payment + checkout regression validation before release.
