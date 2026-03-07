# Blackwork Supabase Radar Audit

## Purpose

This document defines the standard AI audit prompt/process for reviewing the Supabase integration in the Blackwork repository.

This audit covers:

- authentication methods
- token-bearing callback flows
- session lifecycle
- WordPress user linkage
- guest order claim
- invite/recovery flows
- public AJAX auth surface
- trust boundaries
- performance/resilience risks
- failure modes

This audit does **not** implement fixes.
It only analyzes, validates, classifies, and routes findings to governance documents.

---

## Repository-Specific Source of Truth

### Core backend auth surface
- `includes/woocommerce-overrides/class-bw-supabase-auth.php`

### Frontend runtimes
- `assets/js/bw-supabase-bridge.js`
- `assets/js/bw-account-page.js`
- `assets/js/bw-my-account.js`
- `assets/js/bw-password-modal.js`

### Templates / routing
- `woocommerce/templates/myaccount/form-login.php`
- `woocommerce/templates/myaccount/my-account.php`
- `woocommerce/templates/myaccount/auth-callback.php`
- `woocommerce/templates/myaccount/set-password.php`

### Bootstrap / integration
- `woocommerce/woocommerce-init.php`
- `includes/woocommerce-overrides/class-bw-my-account.php`

---

## 1. Audit Scope

Inspect the exact Supabase surfaces implemented in this repository.

Authentication methods to audit:

- token login (bridge -> WP session)
- email/password login
- magic-link / OTP flow
- OAuth / PKCE callback flow
- invite callback flow
- recovery callback flow
- create-password / reset-password flow
- logged-in onboarding password modal flow

System areas to audit:

- session lifecycle
- WordPress user creation/linking
- onboarding marker logic
- guest order claim
- public AJAX auth endpoints
- callback routing
- performance/resilience surfaces

---

## 2. Repository-Specific Auth-Flow Inventory to Inspect

Validate these concrete flows:

### Flow A — Password login
Frontend form -> JS submit -> `bw_supabase_login` -> Supabase password grant -> `bw_mew_supabase_store_session()` -> WP user link/create -> auth cookie -> redirect.

### Flow B — Token bridge login
Frontend obtains access/refresh token -> AJAX `bw_supabase_token_login` -> PHP `/auth/v1/user` verification -> WP user create/link -> session write -> auth cookie -> redirect.

### Flow C — OTP / magic-link flow
OTP request -> OTP verify -> Supabase tokens -> token bridge -> WP session.

### Flow D — OAuth / PKCE callback flow
`?code=...` callback -> JS exchange (`exchangeCodeForSession` or REST pkce fallback) -> token bridge -> WP session.

### Flow E — Invite / recovery callback flow
query/hash callback -> early redirect helper -> bridge parsing -> token bridge -> account/set-password redirect.

### Flow F — Create-password / reset-password completion
set-password UI -> Supabase `PUT /auth/v1/user` -> token bridge -> WP session/redirect.

### Flow G — Logged-in password modal flow
modal status endpoint -> password set endpoint / direct user update -> state reconcile.

---

## 3. Auth API Inventory Audit Rules

Audit and validate all auth-related Supabase calls, including:

- `GET /auth/v1/user`
- `POST /auth/v1/token?grant_type=password`
- `POST /auth/v1/token?grant_type=refresh_token`
- `POST /auth/v1/token?grant_type=pkce`
- `POST /auth/v1/otp`
- `POST /auth/v1/verify`
- redirects to `/auth/v1/authorize`
- `GET /auth/v1/admin/users?page=1&per_page=100`
- `POST /auth/v1/invite`
- `POST /auth/v1/admin/invite`
- `PUT /auth/v1/user`

For every call, report:

- caller type (PHP or JS)
- file + function
- method
- timeout
- headers
- payload shape
- response fields consumed
- whether the call is identity-authoritative or auxiliary

---

## 4. Token-Bearing URL and Callback Audit Rules

Explicitly inspect:

### Hash fragment tokens
- `access_token`
- `refresh_token`
- `type`
- `error_code`
- `error_description`

### Query params
- `code`
- `type`
- `state`
- `provider`
- `bw_auth_callback`
- `bw_set_password`
- `bw_invite_error`
- `bw_invite_error_description`
- `bw_email_confirmed`
- `bw_email_changed`
- `bw_post_checkout`
- `bw_invite_email`
- `bw_after_login`

For each callback flow, verify:

- where tokens first appear
- where they are parsed
- JS vs PHP parsing responsibility
- temporary storage usage
- whether they are POSTed to PHP
- where Supabase re-validates them
- redirect behavior
- stale/missing token behavior

---

## 5. Trust Boundary Audit Rules

For each major auth flow, explicitly explain:

- what the browser knows
- what JS trusts
- what PHP verifies independently
- what Supabase is authoritative for
- at what exact step WordPress session creation is allowed
- where nonce is enforced
- where nonce is softened or bypassed
- where the system relies entirely on Supabase token validation

Mandatory review points:

- guest nonce-softening branch in token login
- distinction between frontend token handling and PHP authoritative verification

---

## 6. Public AJAX Auth Surface Audit Rules

Audit these endpoints:

- `bw_supabase_token_login`
- `bw_supabase_login`
- `bw_supabase_email_exists`
- `bw_supabase_create_password`
- `bw_supabase_check_wp_session`
- `bw_supabase_resend_invite`
- `bw_get_password_status`
- `bw_set_password_modal`

For each endpoint, report:

- priv vs nopriv
- nonce requirement
- input shape
- output shape
- public exposure
- enumeration/leakage risk
- throttling/dedupe/transient guards
- whether it participates directly in auth, onboarding, or session confirmation

---

## 7. Session Lifecycle Audit Rules

Explicitly verify:

- `cookie` mode vs `usermeta` mode
- access token retrieval path
- refresh token retrieval path
- refresh flow trigger
- refresh on page-load possibility
- logout cleanup
- duplicate session writes
- split-brain risk between cookie and usermeta modes
- failure behavior when refresh fails

Mandatory mentions:

- token-login double session write path
- page-load sync path that may trigger refresh

---

## 8. Performance and Resilience Audit Rules

Check:

- synchronous Supabase HTTP during authenticated page load
- timeout values (`15s`)
- lack of response caching
- duplicate writes/calls
- bridge script enqueued broadly on anonymous pages
- guest order claim scalability
- callback flows sensitive to caching/sessionStorage
- invite/recovery redirect helper dependency

---

## 9. Known Mandatory Review Surfaces

Always audit:

- token callback guest nonce-softening branch
- public email existence endpoint
- guest order claim with `limit => -1`
- page-load sync to Supabase on authenticated requests
- broad anonymous-page bridge enqueue
- duplicate/parallel password update paths
- session storage split (`cookie` vs `usermeta`)
- callback redirect helper + bridge + My Account redirect chain
- duplicate token-login session write path

---

## 10. Classification Rules

Classify every finding as exactly one of:

- TRUE
- PARTIALLY TRUE
- FALSE POSITIVE
- NEEDS CONTEXT

Each finding must include:

1. verdict
2. evidence (file + lines)
3. real impact
4. hidden mitigations
5. validated severity
6. governance destination

---

## 11. Governance Routing Rules

### Risk register
Use when:

- auth/security boundary is affected
- public endpoint leaks identity/state
- session integrity is at risk
- external auth dependency can block critical login flows

### Core evolution plan
Use when:

- performance hardening
- callback flow cleanup
- duplication removal
- asset scope tightening
- maintainability improvements

### Decision log
Use when:

- tradeoffs are intentional
- compatibility/security balance is explicit
- trust boundaries are intentionally softened

### No action
Use when:

- false positive
- expected behavior
- mitigation already sufficient

---

## 12. Required Output Format

Return reports in this exact structure:

- Section A — Auth security findings
- Section B — Session lifecycle findings
- Section C — Sync / performance findings
- Section D — Public surface / enumeration findings
- Section E — UX / flow findings
- Section F — False positives / needs context

Then include:

- governance routing map
- top 5 auth risks
- top 5 resilience/performance risks
- implementation task suggestions
- escalation check

---

## 13. Final Constraints

- rely only on repository evidence
- do not speculate beyond code
- do not implement fixes
- be explicit about tokens, redirects, callbacks, session writes, and trust boundaries
- this audit is a reusable Blackwork analysis tool, not a one-off report
