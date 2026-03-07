# BW-TASK-20260307 — Stripe Payment Element Internal Card Subview Resolution

## Task Identification
- Domain: Checkout / Payments UI
- Surface: Stripe Payment Element (UPE) inside Stripe-hosted rendering context
- Objective: Remove/eliminate duplicate internal Stripe "Card + icon" mini-subview without runtime hacks.

## Incident Summary
Checkout showed an unwanted internal Stripe card mini-subview ("Card" + icon) in addition to Blackwork's outer payment method label.
Prior CSS attempts targeted Stripe internal classes directly, but this was not deterministic in the current runtime ownership model.

## Root Cause
- The Stripe Payment Element internal card mini-subview is controlled by Stripe-rendered runtime UI and can be recreated/re-styled after Blackwork static CSS is loaded.
- Prior overrides treated external stylesheet selectors as authoritative, but Stripe runtime ownership and injection timing made that assumption non-deterministic.
- The checkout integration already had an authoritative supported customization path (`wc_stripe_upe_params`) that is applied by Stripe runtime itself.

## Why external CSS failed
- External CSS against Stripe internal `p-*` nodes was brittle:
  - runtime-injected Stripe styles could win the cascade by timing/specificity,
  - node naming/structure can vary across Stripe/Woo versions,
  - behavior required additional JS cleaner fallbacks to stay effective.
- Result: unstable hide behavior and maintenance-heavy overrides.

## Why the iframe-only assumption was incomplete
- The problematic visual block was not safely controllable as a pure "inside-iframe CSS" issue.
- In this integration, parts of UPE card selector UI may be represented through Stripe runtime-managed DOM surfaces where external CSS is still not a stable contract.
- The correct conclusion is not "always impossible to influence", but "must use supported Stripe/Woo params pipeline instead of external CSS hacks".

## Path Evaluation

### Path 1 — Supported Stripe configuration (selected)
- Integration point found in:
  - `woocommerce/woocommerce-init.php`
  - filter: `wc_stripe_upe_params`
  - function: `bw_mew_customize_stripe_upe_appearance()`
- Action:
  - Kept supported Payment Element layout configuration in accordion mode:
    - `layout.type = accordion`
    - `layout.defaultCollapsed = false`
  - Neutralized internal "Card + icon" mini-subview via `appearance.rules` in `wc_stripe_upe_params` (plugin-level supported integration surface).
- Governance assessment:
  - Safe: uses documented Stripe/Woo parameter surface, no DOM/runtime hacks.
  - Deterministic: relies on authoritative integration options rather than brittle class-level styling.

### Path 2 — External mask/cover workaround
- Feasible only as cosmetic wrapper masking outside Stripe rendering context.
- Risks:
  - Responsive drift and clipped content on future Stripe UI changes.
  - Accessibility and focus-state side effects.
  - High maintenance cost and non-deterministic behavior.
- Decision: rejected as primary fix.

### Path 3 — Architectural alternative (separate task)
- Alternative: replace current card presentation strategy with a lower-level Stripe card integration for tighter UI control.
- Blast radius:
  - Payment flow lifecycle, validation semantics, and regression matrix expansion.
  - Requires dedicated architecture/governance task.
- Decision: not suitable as immediate fix path.

## Implementation Summary
- Runtime config update:
  - `woocommerce/woocommerce-init.php`
  - Kept accordion layout under `wc_stripe_upe_params`.
  - Added/maintained `appearance.rules` entries to collapse internal card/icon mini-subview nodes.
- Cleanup:
  - Removed prior aggressive CSS overrides for Stripe internal `p-*` subview selectors from:
    - `assets/css/bw-payment-methods.css`

### Exact appearance.rules used for mini-subview neutralization
- `.PaymentAccordionButtonView`
- `.p-PaymentAccordionButtonView`
- `.PaymentAccordionButtonIconContainer`
- `.p-PaymentAccordionButtonIconContainer`
- `.PaymentAccordionButtonText`
- `.p-PaymentAccordionButtonText`
- `.p-HeightObserver`
- `.p-HeightObserver--delayIncrease`

## Determinism and Safety
- No Stripe JS modifications.
- No DOM manipulation added.
- No unsupported launch/runtime hooks introduced.
- Configuration remains inside Woo/Stripe-supported params pipeline.

## Regression Verification Targets
- Credit / Debit Card flow renders and submits normally.
- PayPal/Klarna flows unaffected by Stripe layout config.
- No dependency on direct styling of Stripe internal subnodes.

## Regression Risk
- Classification: Low-Medium.
- Main residual risk: Stripe/Woo runtime updates may alter internal selector behavior interpreted by `appearance.rules`.
- Mitigation: keep `wc_stripe_upe_params` as the single control surface and revalidate after Stripe/Woo updates.

## Maintenance Note
Future WooCommerce Stripe / WCPay or Stripe.js updates may require revalidation of the internal `appearance.rules` neutralization set.
This check is mandatory during payment-stack update cycles.

## Closure Note
Best viable path is Path 1 (supported `wc_stripe_upe_params` configuration while preserving accordion).
Masking and architecture replacement remain fallback options, but are not governance-preferred for immediate stabilization.
