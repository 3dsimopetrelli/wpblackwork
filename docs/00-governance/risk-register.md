# Risk Register

## 1) Purpose
This document is the authoritative registry of open technical risks at governance level.
It tracks systemic threats to stability, authority boundaries, and non-break invariants across domains.

Difference:
- Risk: governance-level threat that can cause cross-domain instability or invariant break.
- TODO: implementation task/action item. A TODO may address a risk, but it is not the risk itself.

## 2) Severity Model
Impact:
- Low: limited UX/admin inconvenience, no critical state corruption.
- Medium: domain degradation with recoverable user impact.
- High: major domain failure or repeated regressions affecting core flows.
- Critical: commerce/auth integrity threat, cross-domain authority breach, or release blocker.

Likelihood:
- Low: rare, requires unusual conditions.
- Medium: plausible under normal updates/config changes.
- High: likely under routine updates, refresh cycles, or provider variability.

Risk level (qualitative):
- Critical: High/Critical impact + Medium/High likelihood.
- High: High impact + Low likelihood, or Medium impact + High likelihood.
- Medium: Medium impact + Medium likelihood, or Low impact + High likelihood.
- Low: Low impact + Low/Medium likelihood.

## 3) Risk Entries
### Risk ID: R-CHK-01
- Domain: Checkout / Payments
- Surface Anchor: `assets/js/bw-payment-methods.js`, `assets/js/bw-google-pay.js`, `assets/js/bw-apple-pay.js` (`updated_checkout` handlers)
- Description: Multiple checkout refresh listeners can race and temporarily desynchronize selected method, wallet visibility, and button state.
- Invariant Threatened: Selected gateway UI/radio/submit determinism.
- Impact: High
- Likelihood: Medium
- Risk Level: High
- Current Mitigation: Re-sync on `updated_checkout`, fallback method selection, dedupe helpers, scheduled sync.
- Monitoring Status: Open
- Linked Documents:
  - [Blast-Radius Consolidation Map](./blast-radius-consolidation-map.md)
  - [Checkout Payment Selector Audit](../50-ops/audits/checkout-payment-selector-audit.md)

### Risk ID: R-PAY-02
- Domain: Payments / Checkout
- Surface Anchor: `assets/js/bw-stripe-upe-cleaner.js`, `wc_stripe_upe_params` customization in `woocommerce/woocommerce-init.php`
- Description: UPE cleanup relies on volatile Stripe DOM/class selectors; upstream changes can reintroduce duplicate/competing controls.
- Invariant Threatened: Single visible payment selector contract.
- Impact: High
- Likelihood: Medium
- Risk Level: High
- Current Mitigation: Triple-layer suppression (UPE params style rules, cleaner script with MutationObserver, polling fallback).
- Monitoring Status: Monitoring
- Linked Documents:
  - [Technical Hardening Plan](./technical-hardening-plan.md)
  - [Checkout Payment Selector Audit](../50-ops/audits/checkout-payment-selector-audit.md)

### Risk ID: R-PAY-03
- Domain: Payments / Wallets
- Surface Anchor: `assets/js/bw-google-pay.js`, `assets/js/bw-apple-pay.js`, wallet availability flags (`BW_GPAY_AVAILABLE`, `BW_APPLE_PAY_AVAILABLE`)
- Description: Device/browser eligibility may drift from selected wallet state during refreshes, causing invalid actionable UI.
- Invariant Threatened: UI must never present non-actionable payment method as actionable.
- Impact: High
- Likelihood: Medium
- Risk Level: High
- Current Mitigation: Availability checks, disabled wallet selection fallback, dynamic UI synchronization.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Blast-Radius Consolidation Map](./blast-radius-consolidation-map.md)
  - [Checkout Payment Selector Audit](../50-ops/audits/checkout-payment-selector-audit.md)

### Risk ID: R-AUTH-04
- Domain: Auth / Supabase / My Account
- Surface Anchor: `assets/js/bw-supabase-bridge.js`, `woocommerce/woocommerce-init.php` callback preload, `myaccount/auth-callback.php`
- Description: Callback routing may loop or remain stuck in loader state when auth bridge/session convergence fails.
- Invariant Threatened: Callback must converge to stable state without loops or ghost loader.
- Impact: Critical
- Likelihood: Medium
- Risk Level: Critical
- Current Mitigation: `bw_auth_in_progress` state controls, stale callback cleanup, session check endpoint, callback query normalization.
- Monitoring Status: Open
- Linked Documents:
  - [Callback Contracts](./callback-contracts.md)
  - [My Account Domain Audit](../50-ops/audits/my-account-domain-audit.md)
  - [Technical Hardening Plan](./technical-hardening-plan.md)

### Risk ID: R-AUTH-05
- Domain: Supabase / My Account
- Surface Anchor: `includes/woocommerce-overrides/class-bw-supabase-auth.php` (`bw_supabase_onboarded` writes)
- Description: Onboarding marker transitions across invite/token-login/modal flows can desynchronize from real account readiness.
- Invariant Threatened: Onboarding marker must deterministically represent setup completion.
- Impact: High
- Likelihood: Medium
- Risk Level: High
- Current Mitigation: Explicit marker writes per flow, password modal gate checks, provider-specific branching.
- Monitoring Status: Open
- Linked Documents:
  - [Cross-Domain State Dictionary](./cross-domain-state-dictionary.md)
  - [My Account Domain Audit](../50-ops/audits/my-account-domain-audit.md)

### Risk ID: R-SUPA-06
- Domain: Supabase / Orders / My Account
- Surface Anchor: `bw_mew_claim_guest_orders_for_user()` in `class-bw-supabase-auth.php`
- Description: Guest-order claim linkage may duplicate or miss ownership attachment in repeated callback/onboarding paths.
- Invariant Threatened: Guest-to-account claim idempotency and deterministic downloads/order visibility.
- Impact: High
- Likelihood: Medium
- Risk Level: High
- Current Mitigation: Repeated claim invocation designed as convergence path in token-login and modal success.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Technical Hardening Plan](./technical-hardening-plan.md)
  - [My Account Domain Audit](../50-ops/audits/my-account-domain-audit.md)

### Risk ID: R-ACC-07
- Domain: My Account / Supabase
- Surface Anchor: Pending email lifecycle (`bw_supabase_pending_email`) + security tab handlers in `assets/js/bw-my-account.js`
- Description: Pending-email banner and confirmation state can remain stale if callback/pending state cleanup diverges.
- Invariant Threatened: Unconfirmed email must not be treated as confirmed identity.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation: Pending-email clear on confirmed email match; security tab forced on callback; URL param cleanup.
- Monitoring Status: Monitoring
- Linked Documents:
  - [My Account Domain Audit](../50-ops/audits/my-account-domain-audit.md)
  - [Cross-Domain State Dictionary](./cross-domain-state-dictionary.md)

### Risk ID: R-PAY-08
- Domain: Payments / Webhooks
- Surface Anchor: `includes/Gateways/class-bw-abstract-stripe-gateway.php` (`handle_webhook()`) + gateway webhook implementations
- Description: Replay/idempotency edge cases can still produce duplicate transitions if provider/event edge conditions bypass assumptions.
- Invariant Threatened: Payment webhook must converge order state exactly once.
- Impact: Critical
- Likelihood: Low-Medium
- Risk Level: High
- Current Mitigation: Signature verification, event/order checks, meta-based replay guards in gateway implementations.
- Monitoring Status: Open
- Linked Documents:
  - [Callback Contracts](./callback-contracts.md)
  - [Blast-Radius Consolidation Map](./blast-radius-consolidation-map.md)
  - [Technical Hardening Plan](./technical-hardening-plan.md)

### Risk ID: R-BRE-09
- Domain: Brevo / Checkout
- Surface Anchor: `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php` (`can_subscribe_order()`, paid hooks)
- Description: Consent gate bypass or inconsistent consent metadata could trigger unauthorized subscribe writes.
- Invariant Threatened: No consent = no remote write; marketing flow must remain non-blocking.
- Impact: High
- Likelihood: Low-Medium
- Risk Level: Medium-High
- Current Mitigation: Hard consent gate checks, paid-hook gating, local meta audit trail, non-blocking behavior.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Blast-Radius Consolidation Map](./blast-radius-consolidation-map.md)
  - [Technical Hardening Plan](./technical-hardening-plan.md)

## 4) Governance Rules
- All Tier 0 changes must be reviewed against this register before implementation.
- Risks cannot be marked `Resolved` without audit confirmation evidence.
- New integration work must declare risk entries whenever authority boundaries are touched.

## 5) References
- [Blast-Radius Consolidation Map](./blast-radius-consolidation-map.md)
- [Technical Hardening Plan](./technical-hardening-plan.md)
- [Callback Contracts](./callback-contracts.md)
- [Cross-Domain State Dictionary](./cross-domain-state-dictionary.md)
- [Checkout Payment Selector Audit](../50-ops/audits/checkout-payment-selector-audit.md)
- [My Account Domain Audit](../50-ops/audits/my-account-domain-audit.md)
