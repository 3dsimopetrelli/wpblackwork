# BLACKWORK RADAR EXECUTION MAP

## Phase 1 — Critical Stability (Tier 0)

### BW-TASK-20260308-01
- Task ID: `BW-TASK-20260308-01`
- Title: Checkout Strict-Mode Bootstrap Crash Fix
- Domain: Checkout Runtime
- Surface (file/function/endpoint): `assets/js/bw-checkout.js` (`arguments.callee` bootstrap path)
- Problem description: Strict-mode-incompatible bootstrap pattern can throw runtime errors and block checkout initialization.
- Impact: Checkout initialization may fail for users, causing payment flow interruption.
- Tier classification: `Tier 0`
- Risk level: `L3`
- Governance destination: `Risk Register`

### BW-TASK-20260308-02
- Task ID: `BW-TASK-20260308-02`
- Title: Supabase Authenticated Page-Load Sync Hardening
- Domain: Auth / Supabase
- Surface (file/function/endpoint): `includes/woocommerce-overrides/class-bw-supabase-auth.php` (`bw_mew_sync_supabase_user_on_load`)
- Problem description: External Supabase sync runs on authenticated frontend page loads, introducing blocking dependency and resilience risk.
- Impact: Slowdowns and cross-domain availability dependency on core authenticated navigation.
- Tier classification: `Tier 0`
- Risk level: `L3`
- Governance destination: `Risk Register`

### BW-TASK-20260308-03
- Task ID: `BW-TASK-20260308-03`
- Title: Supabase Email Enumeration Surface Closure
- Domain: Auth / Public AJAX
- Surface (file/function/endpoint): `includes/woocommerce-overrides/class-bw-supabase-auth.php` (`bw_mew_handle_supabase_email_exists`, `wp_ajax_nopriv_bw_supabase_email_exists`)
- Problem description: Public endpoint returns email existence signal for Supabase users.
- Impact: Account discovery/enumeration risk and abuse potential.
- Tier classification: `Tier 0`
- Risk level: `L3`
- Governance destination: `Risk Register`

### BW-TASK-20260308-04
- Task ID: `BW-TASK-20260308-04`
- Title: Guest Order Claim Query Bounding
- Domain: Checkout / Account Convergence
- Surface (file/function/endpoint): `includes/woocommerce-overrides/class-bw-supabase-auth.php` (`bw_mew_claim_guest_orders_for_user`)
- Problem description: Guest-order claim uses unbounded `limit => -1` query.
- Impact: Large-run performance degradation and recovery instability during auth/onboarding convergence.
- Tier classification: `Tier 0`
- Risk level: `L3`
- Governance destination: `Risk Register`

## Phase 2 — Checkout Security Hardening (Tier 1)

### BW-TASK-20260308-05
- Task ID: `BW-TASK-20260308-05`
- Title: Checkout Payment Method POST Trust-Boundary Hardening
- Domain: Checkout Security
- Surface (file/function/endpoint): `woocommerce/templates/checkout/payment.php` (`$_POST['payment_method']` read path)
- Problem description: Checkout rendering path consumes posted payment method state directly.
- Impact: Potential state spoof/desync risk if trust boundaries are not strictly enforced.
- Tier classification: `Tier 1`
- Risk level: `L2`
- Governance destination: `Core Evolution Plan`

### BW-TASK-20260308-06
- Task ID: `BW-TASK-20260308-06`
- Title: MutationObserver HTML Sink Hardening
- Domain: Frontend Security
- Surface (file/function/endpoint): Checkout JS MutationObserver decode/render path (payment/checkout runtime)
- Problem description: HTML decode/insert pattern may introduce unsafe sink behavior.
- Impact: Potential XSS-class injection surface under malformed or hostile HTML payloads.
- Tier classification: `Tier 1`
- Risk level: `L2`
- Governance destination: `Core Evolution Plan`

### BW-TASK-20260308-07
- Task ID: `BW-TASK-20260308-07`
- Title: Checkout AJAX Timeout and Recovery Controls
- Domain: Checkout Reliability
- Surface (file/function/endpoint): Checkout coupon/payment AJAX request handlers in frontend runtime
- Problem description: Missing timeout handling can leave requests hanging and UI in non-terminal states.
- Impact: User lock-in on loading states, higher abandonment risk.
- Tier classification: `Tier 1`
- Risk level: `L2`
- Governance destination: `Core Evolution Plan`

### BW-TASK-20260308-08
- Task ID: `BW-TASK-20260308-08`
- Title: Coupon UX State-Convergence Hardening
- Domain: Checkout UX / Reliability
- Surface (file/function/endpoint): Coupon apply/remove flow (`woocommerce-init.php` coupon AJAX + checkout frontend)
- Problem description: Coupon removal/apply flow may reset state via full-page-style transitions or lose form context.
- Impact: Checkout disruption, field state loss, inconsistent user journey.
- Tier classification: `Tier 1`
- Risk level: `L2`
- Governance destination: `Core Evolution Plan`

## Phase 3 — Performance Optimization (Tier 1)

### BW-TASK-20260308-09
- Task ID: `BW-TASK-20260308-09`
- Title: Payment/Checkout Asset Bundling Strategy
- Domain: Frontend Performance
- Surface (file/function/endpoint): checkout script/style enqueue map in `woocommerce/woocommerce-init.php`
- Problem description: Per-file asset loading increases request count and parse overhead.
- Impact: Higher checkout latency and reduced performance on constrained devices/networks.
- Tier classification: `Tier 1`
- Risk level: `L2`
- Governance destination: `Core Evolution Plan`

### BW-TASK-20260308-10
- Task ID: `BW-TASK-20260308-10`
- Title: Supabase Bridge Scope Tightening
- Domain: Auth / Performance
- Surface (file/function/endpoint): `woocommerce/woocommerce-init.php` (`bw_mew_enqueue_supabase_bridge`)
- Problem description: Supabase bridge is enqueued broadly on anonymous pages beyond strict callback needs.
- Impact: Unnecessary payload/runtime cost and wider execution surface.
- Tier classification: `Tier 1`
- Risk level: `L2`
- Governance destination: `Core Evolution Plan`

### BW-TASK-20260308-11
- Task ID: `BW-TASK-20260308-11`
- Title: Google Maps Checkout Lazy-Load Hardening
- Domain: Checkout Performance
- Surface (file/function/endpoint): Checkout Google Maps load path in frontend integrations
- Problem description: Maps API load is eager in checkout lifecycle.
- Impact: Increased critical-path blocking and slower checkout render.
- Tier classification: `Tier 1`
- Risk level: `L2`
- Governance destination: `Core Evolution Plan`

### BW-TASK-20260308-12
- Task ID: `BW-TASK-20260308-12`
- Title: Cart Popup Dynamic CSS Optimization
- Domain: Frontend Performance
- Surface (file/function/endpoint): Dynamic cart popup style generation path (options-heavy rendering)
- Problem description: Dynamic CSS generation performs repeated option lookups each request.
- Impact: Server-side overhead and weaker cache efficiency.
- Tier classification: `Tier 1`
- Risk level: `L2`
- Governance destination: `Core Evolution Plan`

## Phase 4 — Architecture Cleanup (Tier 2)

### BW-TASK-20260308-13
- Task ID: `BW-TASK-20260308-13`
- Title: Gateway Detection Refactor
- Domain: Payments Architecture
- Surface (file/function/endpoint): Gateway-type detection branches in checkout/payment integration (`strpos($gateway->id, ...)` style patterns)
- Problem description: Detection relies on gateway ID naming conventions.
- Impact: Fragile compatibility with renamed/new gateways and silent behavior drift.
- Tier classification: `Tier 2`
- Risk level: `L2`
- Governance destination: `Core Evolution Plan`

### BW-TASK-20260308-14
- Task ID: `BW-TASK-20260308-14`
- Title: Supabase Session Storage Model Convergence
- Domain: Auth Architecture
- Surface (file/function/endpoint): `includes/woocommerce-overrides/class-bw-supabase-auth.php` (cookie/usermeta token paths)
- Problem description: Dual storage model increases complexity and split-brain risk across flows.
- Impact: Harder determinism, maintenance cost, and edge-case failure exposure.
- Tier classification: `Tier 2`
- Risk level: `L2`
- Governance destination: `Core Evolution Plan`

### BW-TASK-20260308-15
- Task ID: `BW-TASK-20260308-15`
- Title: Stripe Enqueue Ownership Cleanup
- Domain: Payments Integration
- Surface (file/function/endpoint): Stripe/wallet enqueue and runtime ownership branches in checkout integration
- Problem description: Duplicated or fragmented Stripe enqueue ownership increases coupling and drift risk.
- Impact: Integration fragility and regressions in wallet/payment runtime behavior.
- Tier classification: `Tier 2`
- Risk level: `L2`
- Governance destination: `Core Evolution Plan`

## Summary Table

| Phase | Tasks | Focus |
|------|------|------|
| Phase 1 — Critical Stability (Tier 0) | BW-TASK-20260308-01, BW-TASK-20260308-02, BW-TASK-20260308-03, BW-TASK-20260308-04 | checkout crash, Supabase page-load sync, email enumeration endpoint, guest order claim query |
| Phase 2 — Checkout Security Hardening (Tier 1) | BW-TASK-20260308-05, BW-TASK-20260308-06, BW-TASK-20260308-07, BW-TASK-20260308-08 | payment_method POST validation, MutationObserver HTML sink, AJAX timeout handling, coupon UX reliability |
| Phase 3 — Performance Optimization (Tier 1) | BW-TASK-20260308-09, BW-TASK-20260308-10, BW-TASK-20260308-11, BW-TASK-20260308-12 | asset bundling, Supabase bridge scope, Google Maps lazy loading, cart popup dynamic CSS optimization |
| Phase 4 — Architecture Cleanup (Tier 2) | BW-TASK-20260308-13, BW-TASK-20260308-14, BW-TASK-20260308-15 | gateway detection refactor, session storage model cleanup, Stripe enqueue duplication |

## Execution Strategy (Phase 1 Recommended Order)

1. `BW-TASK-20260308-01` — Stabilize checkout bootstrap first to remove immediate user-facing crash risk.
2. `BW-TASK-20260308-03` — Close public enumeration surface quickly to reduce active security exposure.
3. `BW-TASK-20260308-04` — Bound guest-order claim query to protect convergence and runtime stability.
4. `BW-TASK-20260308-02` — Refactor Supabase page-load sync with controlled rollout after immediate integrity/stability items.

