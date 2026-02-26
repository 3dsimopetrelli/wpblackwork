# Checkout Payment Selector Audit

## 1) Audit Scope
Real anchors analyzed:
- `woocommerce/templates/checkout/payment.php`
- `assets/js/bw-payment-methods.js`
- `assets/js/bw-stripe-upe-cleaner.js`
- `woocommerce/woocommerce-init.php`:
  - `add_filter('woocommerce_payment_gateways', 'bw_mew_add_google_pay_gateway')`
  - `add_filter('woocommerce_available_payment_gateways', 'bw_mew_hide_paypal_advanced_card_processing')`
  - `add_filter('wc_stripe_upe_params', 'bw_mew_customize_stripe_upe_appearance')`
- Wallet coupling scripts:
  - `assets/js/bw-google-pay.js`
  - `assets/js/bw-apple-pay.js`
- Gateway runtime classes:
  - `includes/Gateways/class-bw-abstract-stripe-gateway.php`
  - `includes/Gateways/class-bw-google-pay-gateway.php`
  - `includes/Gateways/class-bw-apple-pay-gateway.php`
  - `includes/Gateways/class-bw-klarna-gateway.php`

Fragment refresh triggers considered:
- WooCommerce `updated_checkout` lifecycle (checkout fragment replacement)
- custom `update_checkout` triggers from wallet scripts on cancel/error
- `checkout_error` listeners used to recover UI state

## 2) Selection Determinism Analysis
### How active method is decided
- Server-side (`payment.php`):
  - priority 1: `$_POST['payment_method']`
  - priority 2: `WC()->session->get('chosen_payment_method')`
  - fallback: first available gateway
- Client-side (`bw-payment-methods.js`):
  - enforces single checked radio in `syncAccordionState()`
  - tracks `BW_LAST_SELECTED_METHOD`
  - if checked radio disappears/disabled, picks remembered or first enabled fallback

Determinism conclusion:
- radio input `name="payment_method"` remains the intended authority.
- JS actively re-normalizes UI and checked state after events and fragment updates.

### What happens on fragment refresh
- `updated_checkout` handlers run in `bw-payment-methods.js`, `bw-google-pay.js`, `bw-apple-pay.js`, `bw-stripe-upe-cleaner.js`.
- `bw-payment-methods.js` re-syncs:
  - selected radio
  - `.is-selected`/`.is-open` classes
  - action button visibility (wallet vs place order)
- wallet scripts re-dedupe their DOM and re-apply availability UI.

### Gateway availability changes mid-session
- Server can remove gateways via `woocommerce_available_payment_gateways` (e.g. `ppcp-credit-card-gateway`, `woocommerce_payments` removed).
- If selected gateway is no longer present after refresh:
  - server fallback picks first available
  - client fallback re-selects remembered valid gateway or first enabled gateway.

### Is radio state always authoritative?
- In checkout submit contract: yes, `payment_method` radio is the main selector.
- Wallet flows add hidden IDs (`bw_google_pay_method_id`, `bw_apple_pay_method_id`) but gateway PHP still validates order payment method and requires wallet method id only for that selected wallet gateway.

### Is UI ever source-of-truth instead of gateway?
- UI is not intended as authority, but multiple UI normalizers operate in parallel.
- Effective authority remains:
  - server available gateways + checked radio at submit
  - webhook confirmation for final payment truth.

## 3) UPE vs Custom Selector Coupling
- Duplicate controls risk exists by design because Stripe UPE can inject accordion/tab controls.
- Current cleanup layers:
  1. `wc_stripe_upe_params` appearance rules hide Stripe tabs/accordion headers.
  2. `bw-stripe-upe-cleaner.js` force-hides Stripe UPE accordion nodes with inline `display:none !important`, MutationObserver, and polling.
  3. custom selector (`payment.php` + `bw-payment-methods.js`) remains visible control layer.

If UPE and custom selector disagree:
- custom radio can still indicate one gateway while UPE internal controls reappear transiently.
- mitigation exists (cleaner + sync), but it depends on fragile DOM selectors (`.p-AccordionButton...`, `data-testid`).
- this coupling is functional but structurally brittle to Stripe markup changes.

## 4) Failure Modes (Enumerate)
| Failure mode | Severity | Domain impacted | Protecting invariant | Current mitigation | Risk level |
|---|---|---|---|---|---|
| Fragment reload loses active state | High | Checkout, Payments | One checked `payment_method` must exist | server fallback + `syncAccordionState()` fallback | Medium |
| Double active gateway (UI mismatch) | High | Checkout | selected radio = visible active method | JS forces single checked radio, toggles `.is-selected` | Medium |
| Gateway available server-side but hidden client-side | Medium | Checkout UI | submit authority is server + radio | re-sync on `updated_checkout`, mutation observer | Medium |
| Wallet eligibility mismatch (selected wallet but unavailable on device) | High | Checkout, Payments | unavailable wallet cannot remain actionable | `BW_GPAY_AVAILABLE` / `BW_APPLE_PAY_AVAILABLE`, disable selection + fallback | Medium |
| Race between JS sync and fragment injection | High | Checkout, Payments | post-refresh state converges before submit | multiple event hooks + scheduled sync (`bwScheduleSync`) | Medium-High |
| UPE duplicate controls reappear | Medium | Checkout UI | custom selector remains unique visible control | UPE params hide + `bw-stripe-upe-cleaner.js` | Medium-High |
| Stale wallet hidden field survives method switch | Medium | Payments submit path | selected gateway must match processed gateway | gateway `process_payment()` checks method + required wallet method id | Low-Medium |

## 5) Idempotency & Submission Path
- Posted submit contract:
  - `payment_method=<gateway_id>` from selected radio
  - optional wallet hidden field:
    - `bw_google_pay_method_id`
    - `bw_apple_pay_method_id`
- Wallet scripts submit checkout through Woo AJAX endpoint after tokenizing payment method.
- If stale DOM exists:
  - selector script attempts to restore a single valid checked radio.
  - wallet hidden IDs can persist, but gateway handlers require matching `payment_method` and validate method-id presence/format in their own lane.

Cross-domain authority check:
- no direct authority inversion detected in selector path.
- final payment authority still resolves in gateway webhook/order state, not in selector UI.

## 6) Hardening Gaps
Structurally fragile:
- heavy reliance on concurrent JS handlers across four scripts on `updated_checkout`.
- UPE suppression depends on Stripe DOM/class selectors that may change.
- Apple Pay script contains duplicated function definitions (`submitCheckoutWithApplePay`, `runAvailabilityCheck`), increasing behavior drift risk.

Structurally safe:
- server-side chosen-method fallback in template.
- selector script enforces single checked radio and fallback selection.
- gateway processing validates method-specific requirements.

Requires stronger test coverage:
- repeated fragment refresh with gateway changes.
- wallet selection -> cancel -> switch gateway -> submit.
- UPE markup/version changes and selector coexistence.
- checkout error recovery with preserved deterministic selection.

## 7) Audit Verdict
- Determinism: **Mostly deterministic**
- Risk category: **Medium-High**
- Safe for controlled refactor: **Yes, with constraints**
  - safe only if selector contract and webhook/payment authority boundaries remain unchanged
  - requires strict regression on fragment refresh, wallet availability transitions, and UPE cleanup behavior
