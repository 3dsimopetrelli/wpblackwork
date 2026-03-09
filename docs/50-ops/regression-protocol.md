# Regression Protocol

## Mandatory Checks
After any maintenance task, validate:
- Checkout test
- Payment gateway test
- Auth test
- Cart popup test
- Header behavior test
- My Account test
- CSS regression scan
- Console errors scan

## Applicability by Incident Level
- Level 1: mandatory
- Level 2: mandatory
- Level 3: recommended based on impact

## Execution Notes
- Run targeted checks first for the impacted domain.
- Then run cross-domain sanity checks to detect side effects.
- Record anomalies and link them to incident level and affected domain docs.

## Supabase Protected Surface Smoke Binding
When tasks touch Supabase protected surfaces, these smoke tests are mandatory:

1. guest checkout provisioning
2. invite email click callback
3. onboarding password completion
4. order claim visibility
5. resend invite
6. expired link recovery
7. logout cleanup

## WooCommerce Template Override Rebase Notes
- Date: 2026-03-09
- Risk: `R-WOO-24` (patch 1, patch 2, patch 3, patch 4, patch 5) - `RESOLVED`
- Templates updated:
  - `woocommerce/templates/checkout/form-coupon.php`
  - `woocommerce/templates/checkout/payment.php`
  - `woocommerce/templates/checkout/form-checkout.php`
  - `woocommerce/templates/cart/cart.php`
  - `woocommerce/templates/single-product/related.php`
- Alignment action:
  - Applied minimal structural compatibility patching against WooCommerce core contracts while preserving BlackWork custom UX/layout.
  - Avoided Supabase-adjacent surfaces entirely during the patch sequence.
- Required regression checks completed:
  - apply valid coupon
  - invalid coupon handling
  - submit via button and Enter key
  - guest and logged-in checkout
  - mobile and desktop layout
  - payment gateway visibility unaffected (Card / PayPal / Klarna)
  - gateway switching and `updated_checkout` selection persistence
  - noscript update-totals fallback present
  - checkout form submit still works with accessibility/form contract intact (`aria-label` + trailing after-form hook restored)
  - cart last-item removal transitions to empty-cart view on mobile and desktop
  - related products render correctly with Wallpost layout on mobile and desktop

## Checkout Payment Selector Interaction
- Risk/Task: `CHECKOUT-02` (resolved)
- Mandatory regression assertions:
  - radio click must select payment method on first click
  - row/label click must behave identically
  - selection must persist after `updated_checkout`

## Admin Asset Scope Hardening
- Date: 2026-03-09
- Risk: `R-ADM-19` (patch A, patch B)
- Scope:
  - Patch A closed: Blackwork admin menu CSS enqueue scoped to Blackwork admin surfaces only.
  - Patch B closed: Digital Products metabox Select2 loading moved to scoped `admin_enqueue_scripts` (`post.php`/`post-new.php` + `post_type=product`), with runtime preference (`selectWoo` -> `select2` -> local fallback).
  - Local fallback assets added:
    - `assets/lib/select2/js/select2.full.min.js`
    - `assets/lib/select2/css/select2.css`
- Required regression checks completed:
  - Dashboard / Posts / Orders do not load Blackwork menu CSS outside intended scope.
  - Product edit/new screens load Select2 correctly for Showcase linked-product search.
  - No CDN Select2 requests remain from metabox path.
  - No duplicate Select2/SelectWoo runtime on the same product editor page.
  - Non-product admin pages do not load metabox Select2 assets.
