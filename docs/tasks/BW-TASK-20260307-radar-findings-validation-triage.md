# BW-TASK-20260307 — Radar Findings Validation Triage

Date: 2026-03-07
Type: Validation-only (no runtime changes)
Scope: Auth / Supabase / Checkout / Payments / Search

## Confirmed routing outcome

### Risk register destination
- #3 Public enumeration path (`bw_mew_handle_supabase_email_exists`) — High
- #5 Guest-order claim unbounded query (`limit => -1`) — High
- #8 Public live search endpoint without server rate limiting — High
- #13 Onboarding marker convergence watchpoint — Medium (existing auth risk update)

### Backlog / core evolution destination
- #1 external HTTP on authenticated page load
- #2 Supabase user fetch only first 100
- #6 coupon rate limit trusts `HTTP_CF_CONNECTING_IP`
- #7 double session store in token login
- #10 cart quantity mutation from POST data
- #12 inline script without CSP nonce
- #14 hardcoded account menu override
- #15 Supabase bridge loaded on every anonymous page
- #16 unsanitized upstream Supabase error messages
- #17 unconditional `orders` menu item insertion
- #18 early invite runtime on every page
- #19 dual Google Pay gateway ownership / include-drift risk

### Decision log destination
- #4 guest nonce-bypass compatibility/security tradeoff in Supabase token-login callback path

### No action (false positive)
- #9 Stripe webhook `respond_ok()` early 200+exit path is intentional deterministic control flow

### Needs external context
- #11 Google Maps API key restriction posture depends on external Google Cloud project restrictions (not verifiable from repository code alone)

## Governance note
This triage records routing only. Implementation tasks must be opened separately for confirmed risk/backlog items.
