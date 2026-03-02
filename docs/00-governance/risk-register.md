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
### Recently Resolved (Closed and Removed from Active Register)
These risks were active during Theme Builder Lite Phase 1 and are now closed with implementation evidence. They are retained here as closure notes only and are not active risks.

#### Resolved Risk ID: R-TBL-01 (Closed)
- Domain: Theme Builder Lite / Elementor Editor
- Previous threat: `bw_template` permalink returned 404, causing Elementor editor preview bootstrap failure and editor freeze.
- Resolution evidence:
  - `bw_template` made previewable with stable rewrite/permalink + one-time rewrite flush.
  - Dedicated `template_include` preview path added for `is_singular('bw_template')`.
  - `wp_robots` noindex guard applied to prevent indexing of previewable templates.
  - Audit: `docs/50-ops/audits/theme-builder-lite-phase1-implementation.md`
- Closure status: Resolved

#### Resolved Risk ID: R-TBL-02 (Closed)
- Domain: Theme Builder Lite / Admin Runtime Isolation
- Previous threat: Theme Builder Lite admin assets could interfere with Elementor editor initialization.
- Resolution evidence:
  - Strict `admin_enqueue_scripts` scoping to Theme Builder Lite settings page.
  - Explicit bypass for Elementor editor route (`action=elementor`).
  - Runtime docs updated with actual hook isolation contract.
- Closure status: Resolved

#### Resolved Risk ID: R-TBL-03 (Closed)
- Domain: Theme Builder Lite / Custom Fonts / Elementor UI
- Previous threat: Custom fonts configured in `bw_custom_fonts_v1` were not reliably injected into Elementor Typography family dropdown and not consistently available in editor preview context.
- Resolution evidence:
  - Dedicated Elementor fonts integration module added with group + family injection hooks.
  - Shared deterministic CSS builder reused for frontend and Elementor editor/preview enqueue paths.
  - Manual validation confirmed: `Custom Fonts` group visible, configured family selectable, and rendered in editor preview iframe.
  - Hook map + spec updated with final contracts.
- Closure status: Resolved

### Risk ID: R-TBL-04
- Domain: Theme Builder Lite / Elementor Compatibility
- Surface Anchor: `includes/modules/theme-builder-lite/integrations/elementor-fonts.php` (`elementor/fonts/groups`, `elementor/fonts/additional_fonts`, bootstrap defer on `plugins_loaded` + `elementor/loaded`)
- Description: Elementor internal API/filter behavior may drift across releases, potentially degrading custom font group/list injection without hard failures.
- Invariant Threatened: Deterministic visibility of configured custom families in Elementor Typography controls.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation: Soft dependency + defer bootstrap pattern + idempotent registration guard + fail-open behavior when Elementor is absent or filters are unavailable.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Theme Builder Lite Spec](../30-features/theme-builder-lite/theme-builder-lite-spec.md)
  - [Theme Builder Lite Runtime Hook Map](../10-architecture/theme-builder-lite/runtime-hook-map.md)

### Risk ID: R-TBL-05
- Domain: Theme Builder Lite / Template Resolver
- Surface Anchor: `includes/modules/theme-builder-lite/runtime/template-resolver.php` (`template_include` priority `50`)
- Description: Resolver conflicts with theme or third-party `template_include` logic could produce unexpected template precedence behavior.
- Invariant Threatened: Fail-open template selection must never break native theme rendering.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation: strict bypass guards (admin/editor/preview/Woo safety endpoints), deterministic winner contract, and fail-open fallback to original `$template` on any mismatch/error/empty render.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Theme Builder Lite Spec](../30-features/theme-builder-lite/theme-builder-lite-spec.md)
  - [Theme Builder Lite Runtime Hook Map](../10-architecture/theme-builder-lite/runtime-hook-map.md)

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

### Risk ID: R-IMP-10
- Domain: Import / Catalog Data Integrity
- Surface Anchor: Current import runtime (legacy sync path) vs vNext requirements in `docs/30-features/import-products/import-products-vnext-spec.md`
- Description: Bulk import execution remains exposed to duplicate/partial-write risk until Import Engine v2 is implemented with chunking, checkpointing, and deterministic SKU convergence.
- Invariant Threatened: Canonical SKU identity and single authority per product entity.
- Impact: Critical
- Likelihood: Medium
- Risk Level: Critical
- Current Mitigation: Governance lock in vNext spec (SKU-only identity, resumable model, run-level audit requirements) and Tier 0 backlog gating in planning.
- Monitoring Status: Open
- Linked Documents:
  - [Import Products vNext Spec](../30-features/import-products/import-products-vnext-spec.md)
  - [Core Evolution Plan](../00-planning/core-evolution-plan.md)
  - [System Normative Charter](./system-normative-charter.md)

### Risk ID: R-SRCH-11
- Domain: Search / Header Runtime Coupling
- Surface Anchor: `docs/50-ops/audits/search-system-technical-audit.md`, `includes/modules/header/frontend/ajax-search.php`, search runtime contracts
- Description: Search requests are still request-bound and uncached in current runtime, with partial filter wiring risk between UI/CSS expectations and effective query constraints.
- Invariant Threatened: Deterministic read-only discovery behavior and non-blocking navigation under load/failure.
- Impact: High
- Likelihood: Medium
- Risk Level: High
- Current Mitigation: AS-IS documentation, vNext deterministic contract (filters/index/cache), and explicit read-only authority boundary.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Search System Technical Audit](../50-ops/audits/search-system-technical-audit.md)
  - [Search Module Spec](../30-features/search/search-module-spec.md)
  - [Search vNext Spec](../30-features/search/search-vnext-spec.md)

### Risk ID: R-RED-12
- Domain: Redirect / Routing Authority
- Surface Anchor: `includes/class-bw-redirects.php`, `template_redirect` precedence, legacy redirect storage/validation path
- Description: Current redirect runtime can still experience chain/precedence instability before full v2 policy enforcement (protected-route gate, deterministic precedence, multi-hop loop validation).
- Invariant Threatened: Protected commerce routes and deterministic routing authority convergence.
- Impact: Critical
- Likelihood: Medium
- Risk Level: Critical
- Current Mitigation: Existing self-loop safeguards, governance v2 spec constraints, and Tier 0 freeze until implementation task begins.
- Monitoring Status: Open
- Linked Documents:
  - [Redirect Engine Technical Audit](../50-ops/audits/redirect-engine-technical-audit.md)
  - [Redirect Engine v2 Spec](../30-features/redirect/redirect-engine-v2-spec.md)
  - [Runtime Hook Map](../50-ops/runtime-hook-map.md)

### Risk ID: R-HDR-13
- Domain: Header / Global UX Orchestration
- Surface Anchor: `wp_body_open` injection (`bw_header_render_frontend`), responsive/off-canvas/search bindings, smart scroll runtime listeners
- Description: Header global listener and dual-block responsive behavior remain sensitive to binding drift (double-init, keydown/resize overlap), creating cross-surface UX instability.
- Invariant Threatened: Header must remain presentation-only and MUST NOT block commerce/account navigation under failure states.
- Impact: High
- Likelihood: Medium
- Risk Level: High
- Current Mitigation: Initialization guards, module spec contracts, responsive/scroll state-machine documentation, and Tier 0 regression checklist.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Header System Technical Audit](../50-ops/audits/header-system-technical-audit.md)
  - [Header Module Spec](../30-features/header/header-module-spec.md)
  - [Header Responsive Contract](../30-features/header/header-responsive-contract.md)

### Risk ID: R-GOV-14
- Domain: Governance / Tooling / Operational Continuity
- Surface Anchor: Bootstrap path coupling (`blackwork-core-plugin.php`), tooling path dependencies (`composer.json` lint scripts), planning/decision-log operational controls
- Description: Metadata/path migrations and governance doc drift can create operational interruption (activation path changes, stale tooling targets, inconsistent control docs) without explicit authority change.
- Invariant Threatened: Deterministic operational behavior contract and governance traceability for Tier-sensitive changes.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation: Decision-log closure discipline, runtime-hook-map governance rules, and explicit migration tracking in planning/governance docs.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Core Evolution Plan](../00-planning/core-evolution-plan.md)
  - [Decision Log](../00-planning/decision-log.md)
  - [Runtime Hook Map](../50-ops/runtime-hook-map.md)

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
