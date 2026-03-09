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
- Risk: `R-ADM-19` (patch A, patch B) - `MITIGATED`
- Scope:
  - Patch A closed: Blackwork admin menu CSS enqueue scoped to Blackwork admin surfaces only.
  - Patch B closed: Digital Products metabox Select2 loading moved to scoped `admin_enqueue_scripts` (`post.php`/`post-new.php` + `post_type=product`), with runtime preference (`selectWoo` -> `select2` -> local fallback).
  - Patch C deferred (optional backlog): stricter screen/hook replacement for remaining `$_GET['page']` checks in low-risk admin modules (Header, System Status).
  - Local fallback assets added:
    - `assets/lib/select2/js/select2.full.min.js`
    - `assets/lib/select2/css/select2.css`
- Required regression checks completed:
  - Dashboard / Posts / Orders do not load Blackwork menu CSS outside intended scope.
  - Product edit/new screens load Select2 correctly for Showcase linked-product search.
  - No CDN Select2 requests remain from metabox path.
  - No duplicate Select2/SelectWoo runtime on the same product editor page.
  - Non-product admin pages do not load metabox Select2 assets.

## Media Folders Counts Pipeline Audit
- Date: 2026-03-09
- Risk: `R-MF-01` - `WATCHLIST / PLANNED MITIGATION`
- Audit scope:
  - Media Folders counts computation, cache and invalidation pipeline.
  - No implementation changes in this phase (analysis-only closure).
- Confirmed paths:
  - Primary: `bw_mf_get_folder_counts_map_batched()`
  - Fallback: `bw_mf_get_folder_counts_map_fallback()`
- Confirmed risks:
  - stale-count window until TTL when invalidation misses lifecycle events
  - cache churn from broad invalidation
  - expensive fallback behavior under large datasets
- Planned mitigation watchlist:
  - monitor/log fallback activation
  - extend invalidation hooks to object lifecycle events
  - split tree/meta invalidation from counts invalidation
  - add bulk integrity regression scenarios
- Supabase note:
  - No Supabase-adjacent surfaces involved.

## Payments Webhook Hardening (Patch 1)
- Date: 2026-03-09
- Risk: `R-PAY-08` (patch 1) - `CLOSED`
- Scope:
  - Added explicit POST-only method enforcement for Blackwork-owned Stripe webhook handlers.
  - Updated files:
    - `includes/Gateways/class-bw-abstract-stripe-gateway.php`
    - `includes/woocommerce-overrides/class-bw-google-pay-gateway.php`
- Runtime decision record:
  - Active Google Pay runtime/webhook source of truth:
    - `includes/woocommerce-overrides/class-bw-google-pay-gateway.php`
  - In-repo but inactive/not bootstrapped path:
    - `includes/Gateways/class-bw-google-pay-gateway.php`
  - Same class name + same gateway id means both paths cannot be active together at runtime.
  - Google Pay runtime convergence is deferred to a dedicated architecture cleanup task.
- Required regression checks:
  - valid webhook via POST still processes
  - invalid signature via POST still fails
  - GET to webhook endpoint is rejected (`405`)
  - unsupported event keeps expected no-op behavior
