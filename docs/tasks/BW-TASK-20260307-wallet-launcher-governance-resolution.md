# Blackwork Governance — Task Closure

## 1) Task Identification
- Task ID: `BW-TASK-20260307-wallet-launcher-governance-resolution`
- Task title: Wallet Launcher Governance Resolution
- Domain: Checkout / Payments / Wallet Runtime Ownership
- Tier classification: Governance resolution (runtime ownership boundary)
- Scope type: Documentation + disable-path verification closure

## 2) Incident Summary
Custom Blackwork Google Pay and Apple Pay launcher runtimes were investigated after repeated non-deterministic launch behavior in checkout.
The investigation confirmed launch-authority conflicts between Blackwork custom scripts and the native WooCommerce Stripe / WCPay express runtime.

## 3) Investigation Timeline
1. Reproduced wallet launch instability and inconsistent launcher behavior in checkout runtime sessions.
2. Traced wallet ownership through runtime containers and mounted launcher surfaces.
3. Verified native Stripe/WCPay launcher ownership in:
   - `#wc-stripe-express-checkout-element`
   - `#wc-stripe-payment-request-wrapper`
   - `#wcpay-express-checkout-element`
4. Verified Blackwork custom scripts were creating parallel PaymentRequest lifecycles.
5. Executed disable-path audit for Google Pay and Apple Pay.
6. Applied template cleanup so disabled means no wrapper residue in checkout DOM.

## 4) Root Cause
Blackwork custom wallet launchers were non-authoritative relative to the Stripe/WCPay express runtime that already owns wallet lifecycle, mounting, and launch control.
Parallel custom launch attempts introduced authority conflict and nondeterministic behavior.

## 5) Ownership Findings
- Authoritative wallet launch runtime: WooCommerce Stripe / WCPay express runtime.
- Blackwork custom runtime (`bw-google-pay.js`, `bw-apple-pay.js`) was a parallel, non-authoritative layer.
- No stable governance-safe runtime bridge was identified for direct custom triggering of the native Stripe/WCPay launcher lifecycle.

## 6) Governance Decision
Blackwork will not implement custom wallet launchers when Stripe/WCPay already owns wallet runtime in checkout.
Wallet launching is delegated to native Stripe/WCPay express runtime ownership.

## 7) Disable-Path Audit Summary
### Google Pay
- Toggle source: `bw_google_pay_enabled`
- Settings source: `admin/class-blackwork-site-settings.php`
- Gateway availability: `includes/Gateways/class-bw-google-pay-gateway.php`
- Enqueue condition: `woocommerce/woocommerce-init.php` (`$should_enqueue_google_pay`)
- Disabled-state outcome: no row render, no launcher render, no accordion render, no JS enqueue, no init, no listeners, no hidden launch path.

### Apple Pay
- Toggle source: `bw_apple_pay_enabled`
- Settings source: `admin/class-blackwork-site-settings.php`
- Gateway availability: `includes/Gateways/class-bw-apple-pay-gateway.php`
- Enqueue condition: `woocommerce/woocommerce-init.php` (`$should_enqueue_apple_pay`)
- Disabled-state outcome: no row render, no launcher render, no accordion render, no JS enqueue, no init, no listeners, no hidden launch path.

## 8) Cleanup Implementation Summary
- Modified runtime template surface:
  - `woocommerce/templates/checkout/payment.php`
- Cleanup:
  - `#bw-google-pay-button-wrapper` rendered only when Google Pay gateway is available.
  - `#bw-apple-pay-button-wrapper` rendered only when Apple Pay gateway is available.
- Result: disabled state now removes dead wrapper DOM residue and aligns with full disable semantics.

## 9) Acceptance Verification
Acceptance target: disabled must mean `no render`, `no enqueue`, `no init`, `no dead DOM`.

Verified outcomes:
- Google Pay: PASS
- Apple Pay: PASS
- Template wrapper residue cleanup: PASS

## 10) Regression Verification
- Existing payment methods outside disabled custom wallet launchers remained unchanged by this closure scope.
- Cleanup was template-only and deterministic (conditional wrapper output by gateway availability).
- Regression risk classification: LOW.

## 11) Determinism Verification
- Authority ownership is now explicit: wallet launch lifecycle belongs to Stripe/WCPay runtime.
- Blackwork no longer runs a competing launcher path when disabled.
- Checkout state converges without parallel wallet launch ownership conflict.

## 12) Rollback Safety
- Rollback method: revert template conditional wrapper gating and related documentation updates.
- Database migrations: none.
- Runtime rollback complexity: low.

## 13) Closure Declaration
- Task status: CLOSED
- Closure rationale: governance-safe ownership boundary established, disable-path audited, cleanup completed, and documentation aligned.
- Final operational rule:
  - When Stripe/WCPay owns wallet runtime, Blackwork must not implement parallel custom wallet launchers.
