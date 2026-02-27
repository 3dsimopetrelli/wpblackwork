# Cart vs Checkout Responsibility Matrix

## 1) Domain Definitions

### Cart Domain (non-authoritative operational state)
Cart domain manages cart interaction surfaces and transition UX to checkout:
- cart quantity/remove interactions,
- cart popup and mini-cart behavior,
- floating cart trigger visibility,
- add-to-cart UI lifecycle.

Cart domain is operational and presentation-oriented. It is explicitly non-authoritative for payment, auth, provisioning, and order truth.

### Checkout Domain (payment orchestration authority)
Checkout domain manages checkout orchestration surfaces:
- checkout form interaction lifecycle,
- checkout summary refresh and cloned mobile summary behavior,
- checkout coupon interaction in checkout context,
- orchestration handoff to payment UI layer.

Checkout domain owns payment orchestration entrypoint behavior but does not define payment truth.

### Payment UI Layer
Payment UI layer manages payment method interaction and wallet UI coupling:
- payment method selection UI,
- wallet trigger visibility and hidden method payload fields,
- UPE/custom selector coexistence handling.

Payment UI layer can mutate payment selection state, but authoritative payment truth remains callback/webhook reconciliation.

### Shared Lifecycle Utilities
Shared utility scripts provide non-business lifecycle support across Cart and Checkout:
- loader/progress/skeleton behavior,
- generic event-driven UX glue.

Shared utilities must remain non-authoritative and non-mutating for business authority state.

## 2) JS File Classification

Each file is assigned to exactly one category.

| JS File | Category |
|---|---|
| `assets/js/bw-cart.js` | Cart |
| `includes/modules/header/assets/js/bw-navshop.js` | Cart |
| `assets/js/bw-price-variation.js` | Cart |
| `cart-popup/assets/js/bw-cart-popup.js` | Cart |
| `assets/js/bw-checkout.js` | Checkout |
| `assets/js/bw-checkout-notices.js` | Checkout |
| `assets/js/bw-payment-methods.js` | Payment |
| `assets/js/bw-google-pay.js` | Payment |
| `assets/js/bw-apple-pay.js` | Payment |
| `assets/js/bw-stripe-upe-cleaner.js` | Payment |
| `assets/js/bw-premium-loader.js` | Shared Utility |

Explicit required classification:
- `bw-premium-loader.js` -> Shared Utility
- `bw-cart-popup.js` -> Cart
- `bw-checkout.js` -> Checkout
- `bw-payment-methods.js` -> Payment
- wallet JS (`bw-google-pay.js`, `bw-apple-pay.js`) -> Payment

## 3) Responsibility Table

| Component | May Mutate Cart State? | May Trigger Checkout Refresh? | May Mutate Payment Selection? | May Define Authority? | Domain Owner |
|---|---|---|---|---|---|
| `assets/js/bw-cart.js` | Yes | No | No | No | Cart |
| `includes/modules/header/assets/js/bw-navshop.js` | No | No | No | No | Cart |
| `assets/js/bw-price-variation.js` | Yes (add-to-cart flow trigger) | No | No | No | Cart |
| `cart-popup/assets/js/bw-cart-popup.js` | Yes | Yes (`update_checkout`) | No | No | Cart |
| `assets/js/bw-checkout.js` | Yes (checkout cart rows/coupon in checkout scope) | Yes | No | No | Checkout |
| `assets/js/bw-checkout-notices.js` | No | No | No | No | Checkout |
| `assets/js/bw-payment-methods.js` | No | Indirect (reacts to refresh lifecycle) | Yes | No | Payment |
| `assets/js/bw-google-pay.js` | No | Yes (`update_checkout`) | Yes | No | Payment |
| `assets/js/bw-apple-pay.js` | No | Yes (`update_checkout`) | Yes | No | Payment |
| `assets/js/bw-stripe-upe-cleaner.js` | No | No | Yes (UI cleanup effect) | No | Payment |
| `assets/js/bw-premium-loader.js` | No | No (listens only) | No | No | Shared Utility |

Normative interpretation:
- "May Define Authority?" is `No` for all JS components.
- Business authority is defined only by authoritative server-side reconciliation and ADR doctrine.

## 4) Hard Prohibitions

- Cart MUST NOT mutate payment selection or payment truth.
- Cart MUST NOT define authority state.
- Checkout MUST NOT redefine cart business truth outside checkout orchestration scope.
- Checkout MUST NOT infer payment truth from UI success or redirect artifacts.
- Payment UI layer MUST NOT define payment authority truth.
- Shared utilities MUST NOT mutate business authority state.
- Shared utilities MUST remain presentation-only lifecycle helpers.

## 5) Fragment Refresh Classification

### Event emission layer
- Cart layer may emit fragment refresh triggers for cart synchronization (`wc_fragment_refresh`) in cart-popup operations.
- Cart and checkout flows may emit `update_checkout` when checkout recomputation is needed.

### State reflection layer
- Cart reflects cart visualization state (badge, popup list, mini-cart-facing UX).
- Checkout reflects checkout-form/order-summary state after `updated_checkout`.
- Payment UI reflects selected payment method and wallet visibility based on refreshed DOM and eligibility.

### State reconciliation layer
- Reconciliation is NOT owned by cart/checkout/payment JS.
- Authoritative reconciliation belongs to local server-side business logic and callback/webhook processing.
- JS layers consume reflected state and must remain non-authoritative.

## 6) Cross-References

- ADR-001 Selector Authority: `../60-adr/ADR-001-upe-vs-custom-selector.md`
- ADR-002 Authority Hierarchy: `../60-adr/ADR-002-authority-hierarchy.md`
- ADR-003 Callback Anti-Flash Model: `../60-adr/ADR-003-callback-anti-flash-model.md`
- ADR-005 Claim Idempotency Rule: `../60-adr/ADR-005-claim-idempotency-rule.md`
- ADR-006 Provider Switch Model: `../60-adr/ADR-006-provider-switch-model.md`

Related structural references:
- JS analysis source: `../50-ops/audits/cart-checkout-js-structure-analysis.md`
- Checkout architecture boundary: `../30-features/checkout/checkout-architecture-map.md`
- Payments architecture map: `./payments/payments-architecture-map.md`
