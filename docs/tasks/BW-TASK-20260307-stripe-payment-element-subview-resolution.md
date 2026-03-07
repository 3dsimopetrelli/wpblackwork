# BW-TASK-20260307 — Stripe Payment Element Internal Card Subview Resolution

## Task Identification
- Domain: Checkout / Payments UI
- Surface: Stripe Payment Element (UPE) inside Stripe-hosted rendering context
- Objective: Remove/eliminate duplicate internal Stripe "Card + icon" mini-subview without runtime hacks.

## Incident Summary
Checkout showed an unwanted internal Stripe card mini-subview ("Card" + icon) in addition to Blackwork's outer payment method label.
Multiple selector-based/internal hide attempts were tested during investigation but were exploratory and not accepted as final solution.

## Root Cause
- The duplicated "Card + icon" subview is Stripe-owned UI behavior inside the Payment Element lifecycle.
- Internal-selector suppression (CSS/internal node targeting) is brittle and not a stable contract in this integration path.
- The reliable supported control surface in this repository is the Woo Stripe params filter: `wc_stripe_upe_params`.

## Investigation Outcome
- Selector-based/internal hide attempts happened and informed diagnosis.
- Those attempts are **not** the adopted solution.
- Final accepted solution: switch Payment Element layout to `tabs` via `wc_stripe_upe_params`.
- Reason: cleaner UX outcome with lower fragility and no reliance on brittle Stripe-internal selector hacks.

## Adopted Configuration
- Integration point:
  - `woocommerce/woocommerce-init.php`
  - `bw_mew_customize_stripe_upe_appearance($params)`
  - filter `wc_stripe_upe_params`
- Final runtime choice:
  - `layout.type = tabs`
  - supported `appearance` params retained for normal styling

## Implementation Summary
- Runtime config update:
  - `woocommerce/woocommerce-init.php`
  - Set Payment Element layout to `tabs` via `wc_stripe_upe_params`.
  - Kept normal appearance styling; dropped dependence on internal subview hide hacks as solution strategy.
- UI spacing:
  - handled through Blackwork wrapper (`.bw-stripe-fields-wrapper`) in checkout styling.

## Determinism and Safety
- No Stripe JS modifications.
- No DOM manipulation added.
- No unsupported launch/runtime hooks introduced.
- Configuration remains inside Woo/Stripe-supported params pipeline.

## Regression Verification Targets
- Credit / Debit Card flow renders and submits normally.
- PayPal/Klarna flows unaffected by Stripe layout config.
- No dependency on direct styling of Stripe internal subnodes as primary solution.

## Regression Risk
- Classification: Low-Medium.
- Main residual risk: Stripe/Woo updates can change Payment Element behavior.
- Mitigation: keep `wc_stripe_upe_params` as single supported control surface and revalidate on payment-stack updates.

## Maintenance Note
Future WooCommerce Stripe / WCPay or Stripe.js updates may require revalidation of the `tabs` layout behavior.
Do not reintroduce unsupported Stripe-internal selector hacks as primary fix.

## Closure Note
Final adopted solution is `layout.type = tabs` through `wc_stripe_upe_params`.
This is the accepted UX-safe resolution for the duplicated internal Stripe card/icon subview.
