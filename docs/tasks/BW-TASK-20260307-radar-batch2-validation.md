# BW-TASK-20260307 — Radar Batch 2 Validation

Date: 2026-03-07  
Type: Validation-only (no runtime code changes)  
Reference: Radar Batch 2 — Checkout Weakness Analysis

## Validated findings classification

### High
- #1 Strict-mode bootstrap crash risk: `arguments.callee` inside strict mode may throw and break checkout initialization.
  - Location: `assets/js/bw-checkout.js`
  - Routing: Risk Register (`R-CHK-02`)

### Medium
- #4 Inline script without CSP nonce.
  - Location: `woocommerce/templates/checkout/form-checkout.php`
  - Routing: Backlog
- #5 Checkout cart quantity sync trust boundary on posted payload.
  - Location: `woocommerce/woocommerce-init.php`
  - Routing: Backlog
- #6 Coupon rate-limit IP key trusts proxy header path.
  - Location: `woocommerce/woocommerce-init.php`
  - Routing: Backlog
- #7 MutationObserver `innerHTML` decode sink risk.
  - Location: `assets/js/bw-checkout.js`
  - Routing: Backlog

### Low / Backlog
- #2 Payment-method bootstrap flag re-init edge behavior.
  - Location: `assets/js/bw-payment-methods.js`
- #3 Direct POST read for selected `payment_method`.
  - Location: `woocommerce/templates/checkout/payment.php`
- #9 Coupon removal full redirect and form-state loss.
  - Location: `assets/js/bw-checkout.js`
- #10 `alert()` fallback for coupon errors.
  - Location: `assets/js/bw-checkout.js`
- #11 Sticky layout reimplemented in JS.
  - Location: `assets/js/bw-checkout.js`
- #12 Coupon AJAX calls without timeout.
  - Location: `assets/js/bw-checkout.js`
- #13 Gateway type detection via `strpos($gateway->id, ...)`.
  - Location: `woocommerce/templates/checkout/payment.php`
- #15 Raw `legal_text` returned from settings before render-context escaping.
  - Location: `woocommerce/woocommerce-init.php`
- #16 Duplicated inline style attributes in checkout template.
  - Location: `woocommerce/templates/checkout/form-checkout.php`
- #17 Policy content duplicated in DOM attributes and global JS object.
  - Location: `woocommerce/templates/checkout/form-checkout.php`
- #19 Skeleton feature flag hardcoded false.
  - Location: `woocommerce/templates/checkout/form-checkout.php`
- #20 Dial-code list hardcoded in checkout JS.
  - Location: `assets/js/bw-checkout.js`

### False positives
- #14 Double `wp_localize_script` on `bw-checkout-script`/`bwCheckout`.
  - Outcome: False positive (handle/object mismatch vs real code path).
- #18 Wallet DOM always rendered when disabled.
  - Outcome: False positive for current state (wrapper render is gateway-gated).

### Decision log item
- #8 Trusted WooCommerce filter boundary (`woocommerce_cart_item_remove_link`).
  - Outcome: Documented as extension trust boundary in Decision Log Entry 031.

## Governance routing summary
- Risk register:
  - #1 -> `R-CHK-02`
- Backlog / Core Evolution Plan:
  - #2 #3 #4 #5 #6 #7 #9 #10 #11 #12 #13 #15 #16 #17 #19 #20
- Decision log:
  - #8 (trusted Woo extension boundary)
- No action:
  - #14 #18

## Notes
This artifact records triage and routing only for Radar Batch 2 — Checkout Weakness Analysis.  
Implementation work is tracked separately through dedicated hardening tasks.
