# BW-TASK-20260307 — Stripe Payment Element Internal Card Subview Resolution

## Task Identification
- Domain: Checkout / Payments UI
- Surface: Stripe Payment Element (UPE) inside Stripe-hosted rendering context
- Objective: Remove/eliminate duplicate internal Stripe "Card + icon" mini-subview without runtime hacks.

## Incident Summary
Checkout showed an unwanted internal Stripe card mini-subview ("Card" + icon) in addition to Blackwork's outer payment method label.
Prior CSS attempts targeted Stripe internal classes directly, but this was not deterministic in the current runtime ownership model.

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

## Determinism and Safety
- No Stripe JS modifications.
- No DOM manipulation added.
- No unsupported launch/runtime hooks introduced.
- Configuration remains inside Woo/Stripe-supported params pipeline.

## Regression Verification Targets
- Credit / Debit Card flow renders and submits normally.
- PayPal/Klarna flows unaffected by Stripe layout config.
- No dependency on direct styling of Stripe internal subnodes.

## Closure Note
Best viable path is Path 1 (supported `wc_stripe_upe_params` configuration while preserving accordion).
Masking and architecture replacement remain fallback options, but are not governance-preferred for immediate stabilization.
