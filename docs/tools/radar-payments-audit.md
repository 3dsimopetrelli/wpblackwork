# Blackwork Payments Radar Audit

## Purpose

This document defines the standard AI audit prompt/process used to review the payment layer of the Blackwork plugin.

The audit covers:

- WooCommerce checkout payment architecture
- payment gateway handling
- Stripe / wallet integration
- checkout frontend payment scripts
- order creation boundaries
- payment-related AJAX endpoints
- wallet button rendering
- checkout fragment updates
- security and abuse surfaces
- performance and UX reliability

This audit analyzes code but does **not** implement fixes.
All findings must be classified and routed to governance documents.

---

## Core Repository Surfaces to Inspect

### Checkout runtime scripts
- `assets/js/bw-checkout.js`
- `assets/js/bw-payment-methods.js`
- `assets/js/bw-checkout-notices.js`
- `assets/js/bw-stripe-upe-cleaner.js`

### Checkout templates
- `woocommerce/templates/checkout/payment.php`
- `woocommerce/templates/checkout/review-order.php`
- `woocommerce/templates/checkout/form-checkout.php`

### WooCommerce integration layer
- `woocommerce/woocommerce-init.php`

### Related CSS/UI layers
- `assets/css/bw-payment-methods.css`
- `assets/css/bw-checkout.css`

---

## 1. Payment Architecture Mapping

Reconstruct the payment architecture first.

Identify:

- how gateways are enumerated
- how payment methods are rendered
- how gateway UI state is toggled
- how wallet buttons are injected
- how checkout fragments update payment UI
- how checkout JS initializes and re-initializes

Output must include this flow:

checkout load
-> payment methods render
-> payment selection
-> gateway script initialization
-> order submission
-> WooCommerce order processing

---

## 2. Payment Gateway Detection Logic

Audit how gateway type is inferred.

Check for patterns like:

- `strpos($gateway->id, 'klarna')`
- `strpos($gateway->id, 'stripe')`

Validate:

- whether gateway detection is robust
- whether it depends on naming conventions
- whether gateways could break silently if renamed
- whether new gateways would render correctly

---

## 3. Wallet Integration Audit

Inspect:

- Apple Pay
- Google Pay
- Stripe UPE behavior

Verify:

- where Stripe JS is loaded
- whether Stripe is loaded multiple times
- whether wallet scripts depend on gateway presence
- whether wallet buttons are rendered conditionally
- whether disabled gateways still leave DOM artifacts

Also verify:

- interaction between Stripe and WooCommerce checkout fragments
- wallet behavior during checkout updates

---

## 4. Checkout JS Lifecycle Audit

Inspect payment-related JS initialization.

Verify:

- initial checkout bootstrap
- jQuery dependency
- event listeners
- `updated_checkout` / `update_checkout` triggers
- reinitialization behavior when fragments refresh

Special focus:

- bootstrap flags
- broken re-init scenarios
- accordion/tab payment UI logic
- state desynchronization between DOM and WooCommerce state

---

## 5. Checkout AJAX / Fragment Update Behavior

Verify payment UI behavior when:

- shipping changes
- address fields change
- coupons applied or removed
- checkout fragments refresh

Check:

- whether payment UI breaks after fragment reload
- whether JS handlers rebind correctly
- whether wallet buttons survive fragment reload
- whether payment state persists correctly

---

## 6. Checkout Security Boundaries

Audit handling of payment data.

Focus on:

- `$_POST['payment_method']`
- WooCommerce gateway selection
- template logic using POST values
- nonce protection in checkout flows
- injection risks via gateway filters

Verify whether:

- payment method selection can be spoofed
- POST data is trusted too early
- templates output filterable HTML unsafely

---

## 7. Wallet / Payment DOM Audit

Verify DOM surfaces for:

- payment icons
- wallet buttons
- payment method containers
- dynamic notices

Check for:

- CLS issues (missing width/height)
- hidden but rendered payment elements
- DOM scanning by third-party scripts
- duplicate wallet elements

---

## 8. Checkout Performance Risks

Check:

- JS bundle size
- number of payment-related scripts
- number of CSS files affecting checkout
- heavy wallet libraries (Stripe)
- unnecessary scripts loaded on checkout

Verify:

- whether Stripe loads only when needed
- whether wallet scripts block page load
- whether checkout JS does heavy DOM work

---

## 9. Checkout UX Reliability Risks

Audit the checkout experience for:

- coupon removal behavior
- error rendering
- fallback UI behavior
- loading states
- `alert()` usage
- form state loss

Verify whether:

- removing coupons resets checkout fields
- error rendering blocks UI
- network failures trap the user in loading state

---

## 10. Required Classification Format

Classify all findings as exactly one of:

- TRUE
- PARTIALLY TRUE
- FALSE POSITIVE
- NEEDS CONTEXT

Each finding must include:

1. verdict
2. evidence (file + lines)
3. real impact
4. hidden mitigations
5. validated severity
6. governance destination

---

## 11. Governance Routing

Route findings to:

### Risk register
Use for:

- payment integrity risks
- checkout security vulnerabilities
- gateway spoofing risks
- wallet execution risks

### Core evolution plan
Use for:

- architecture cleanup
- performance improvements
- gateway detection refactors
- JS lifecycle improvements

### Decision log
Use for:

- intentional UX tradeoffs
- WooCommerce compatibility choices

### No action
Use for:

- expected WooCommerce patterns
- false positives

---

## 12. Required Output Structure

Return results in this exact format:

- Section A — Payment architecture findings
- Section B — Gateway detection findings
- Section C — Wallet integration findings
- Section D — Checkout lifecycle findings
- Section E — Security findings
- Section F — Performance findings
- Section G — UX findings
- Section H — False positives / needs context

Then include:

- governance routing map
- top 5 payment risks
- top 5 checkout reliability risks
- recommended implementation tasks
- escalation check

---

## Final Constraints

- rely only on repository evidence
- do not speculate beyond code
- do not implement fixes
- this audit must remain reusable for future Radar scans
