# Checkout Architecture Map

## 1) Purpose + Scope
This document is the official reference model for the Checkout domain in Blackwork.
It describes how checkout behavior is produced by the current implementation across admin configuration, runtime orchestration, template overrides, frontend scripts, and integration couplings.

Scope boundaries:
- Functional architecture of Checkout only.
- Based on audited implementation reality from `docs/50-ops/checkout-reality-audit.md`.
- No normative refactor decisions, no code-level modification guidance.

## 2) Architecture Overview (Layers)

### Layer A: Admin writer layer (options/toggles)
- Main writer: `admin/class-blackwork-site-settings.php` (`bw_site_render_checkout_tab()`).
- Writes checkout layout, footer/legal, policies, payment toggles/keys, Supabase provisioning flags, Google Maps flags.
- Dedicated checkout-fields writer module persists `bw_checkout_fields_settings`.

### Layer B: Runtime orchestrator (`woocommerce/woocommerce-init.php`)
- Central runtime coordinator.
- Reads options and normalizes settings through `bw_mew_get_checkout_settings()`.
- Enqueues checkout assets and localizes runtime payloads.
- Applies checkout hooks/filters and integration orchestration.

### Layer C: Template overrides (`woocommerce/templates/checkout/*`)
- Checkout flow rendered through custom override templates.
- `payment.php` is the highest coupling template (gateway readiness checks + custom payment UI structure).

### Layer D: Frontend orchestration JS
- `assets/js/bw-checkout.js`: layout/interaction orchestration.
- `assets/js/bw-payment-methods.js`: payment method selector, fallback handling, place-order button sync.
- Wallet scripts:
  - `assets/js/bw-google-pay.js`
  - `assets/js/bw-apple-pay.js`
- Stripe cleanup shim:
  - `assets/js/bw-stripe-upe-cleaner.js`

### Layer E: Integrations coupling
- Payments (Stripe-backed Google Pay / Apple Pay / Klarna).
- Supabase checkout provisioning path.
- Google Maps autocomplete path.
- Brevo checkout opt-in path.
- Checkout-fields shape layer (`bw_checkout_fields_settings`).

## 3) Checkout State Machine

State flow (implementation reality):

1. Admin Intent
- Merchant/admin sets options in Checkout tab and related modules.
- Options are stored via `update_option(...)` and module-specific save handlers.

2. Runtime Eligibility
- Runtime reads options.
- Eligibility gates evaluate toggles plus readiness (keys, gateway availability, context, dependency state).

3. Render
- `woocommerce-init.php` enqueues CSS/JS and localized params.
- Checkout templates render structure (`form-*`, `review-order`, `payment`).

4. Interaction
- Frontend scripts manage payment selector, wallet availability, free-order behavior, autocomplete, and checkout UI synchronization.

5. Submit
- WooCommerce checkout submission executes selected payment path.
- Wallet scripts may inject payment method IDs and submit through checkout flow.

6. Post-order hooks
- Checkout/order hooks execute post-submit logic:
  - consent metadata persistence
  - mail marketing subscription flow (created/paid timing)
  - order status-linked integration callbacks

## 4) Precedence & Fallback Rules

### A) Soft-gating model (toggle vs readiness)
- Toggle = intent, not guarantee.
- Effective runtime availability requires readiness conditions.

| Aspect | Admin toggle | Runtime readiness required |
|---|---|---|
| Google Pay | `bw_google_pay_enabled` | keys/mode coherence + gateway/runtime availability |
| Apple Pay | `bw_apple_pay_enabled` | Apple/Stripe readiness + runtime capability |
| Klarna | `bw_klarna_enabled` | key presence + WooCommerce gateway enabled state |
| Maps | `bw_google_maps_enabled` | API key present + script load + checkout context |
| Supabase provisioning | `bw_supabase_checkout_provision_enabled` | Supabase configuration and redirect integrity |

### B) Apple Pay fallback behavior
- Apple Pay runtime can fallback to Google Pay publishable/secret key sources in specific code paths when Apple-specific key is absent.
- This creates cross-gateway key coupling that must be considered during maintenance.

### C) Stripe UPE cleaner vs custom payment selector
- Custom selector logic is owned by `bw-payment-methods.js` + custom `payment.php` markup.
- `bw-stripe-upe-cleaner.js` force-hides Stripe UPE accordion rows to avoid duplicated/conflicting method UI.
- Any selector/UPE DOM change can break synchronization between visible gateway rows and active payment state.

### D) Default/fallback settings (`bw_mew_get_checkout_settings`)
- Runtime defaults are enforced even when options are missing/invalid.
- Legacy fallback keys are still read for some values (e.g. old page/grid background keys).
- Settings are normalized (allowed enums, bounds, sanitized colors/text, width normalization).

## 5) High-Risk Zones (Blast Radius)

### `woocommerce/templates/checkout/payment.php`
Why high risk:
- Central payment render contract for all methods.
- Contains readiness checks and per-gateway UX branches.
- Changes can desync JS selectors, gateway availability display, and submission behavior.

Potential break types:
- Payment method not selectable/visible.
- Wrong method appears selected vs submitted.
- Wallet fallback or readiness messaging mismatch.

### Frontend payment orchestration scripts
Primary files:
- `assets/js/bw-payment-methods.js`
- `assets/js/bw-google-pay.js`
- `assets/js/bw-apple-pay.js`

Why high risk:
- They manage runtime selection state, fallback method switching, and checkout action button visibility.
- They interact with WooCommerce fragment refresh lifecycle (DOM replacement behavior).

Potential break types:
- Place-order button hidden when needed.
- Wallet button shown for unavailable method.
- Radio/check state drift causing wrong gateway processing.

## 6) Dependency Map (Cross-domain)

Checkout depends on these documentation domains:
- Payments integration docs: [`docs/40-integrations/payments/`](../../40-integrations/payments/)
- Supabase integration docs: [`docs/40-integrations/supabase/`](../../40-integrations/supabase/)
- Brevo integration docs: [`docs/40-integrations/brevo/`](../../40-integrations/brevo/)
- Checkout feature docs:
  - [`complete-guide.md`](./complete-guide.md)
  - [`maintenance-guide.md`](./maintenance-guide.md)

## 7) Maintenance References
- Regression protocol: [`docs/50-ops/regression-protocol.md`](../../50-ops/regression-protocol.md)
- Checkout runbook: [`docs/50-ops/runbooks/checkout-runbook.md`](../../50-ops/runbooks/checkout-runbook.md)
- Checkout reality audit: [`docs/50-ops/checkout-reality-audit.md`](../../50-ops/checkout-reality-audit.md)
