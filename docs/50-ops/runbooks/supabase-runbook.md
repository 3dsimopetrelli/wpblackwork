# Supabase Runbook

## 1. Domain Scope
Includes Supabase-linked account provisioning, callback bridging, create-password lifecycle, and related audits.

Verification Hold Note:
- Onboarding marker convergence hardening is implemented, but verification identified anomalies requiring deeper investigation before closure.

Related folders:
- `docs/40-integrations/supabase/`
- `docs/40-integrations/supabase/audits/`
- `docs/40-integrations/auth/`

Related docs:
- `../../40-integrations/supabase/supabase-create-password.md`
- `../../40-integrations/supabase/audits/README.md`
- `../../40-integrations/supabase/audits/2026-02-13-supabase-create-password-audit.md`
- `../../40-integrations/auth/social-login-setup-guide.md`
- `../regression-protocol.md`

## 2. Critical Risk Points
- Broken token bridge between Supabase callback and WP session.
- Password flow regressions (modal gating, invite link handling, expired links).
- Inconsistent policy validation between UI and backend.
- Onboarding state stuck or bypassed.

High-risk integrations and dependencies:
- Supabase invite/confirmation templates.
- Account callback handlers and AJAX endpoints.
- My Account template rendering conditions.

## 3. Pre-Maintenance Checklist
- Read Supabase canonical doc and latest audit.
- Confirm expected provider mode and flow entry points.
- Identify fragile areas: invite callback, create-password modal, post-checkout onboarding.

## 4. Safe Fix Protocol
- Preserve stable baseline rules defined in Supabase docs.
- Apply incremental changes with focused verification.
- Do not modify callback URLs/contracts casually.
- ADR required for structural changes in Supabase auth architecture.

## 5. Regression Checklist (Domain Specific)
- Test new guest purchase flow with Supabase onboarding.
- Test logged-in safety path (no incorrect gating).
- Test expired/invalid invite behavior.
- Test password policy validation consistency UI/backend.
- Check browser console and `debug.log` for Supabase/auth errors.
- Test deterministic onboarding marker convergence for:
  - token-login (`invite`, `otp`, ready user paths)
  - password modal completion
  - callback refresh/re-entry with authenticated session
- Confirm stale marker reconciliation does not unlock users with pending invite/error signals.

## 6. Documentation Update Requirements
- Update `CHANGELOG.md` for flow-affecting Supabase changes.
- Update Supabase main doc and audits when findings/fixes evolve.
- Update ADR when Supabase architectural decisions change.

## 7. Flow-Indexed Triage Matrix (Canonical Flows 1-11)

| Flow | Symptom | Likely broken layer | first evidence to check | first comparator file | Smoke test relevance | Rollback relevance |
|---|---|---|---|---|---|---|
| 1 Provider selection | Wrong login/provider UX | Option/config branch | provider value + template branch logs | `docs/40-integrations/supabase/supabase-architecture-map.md` | High | Medium |
| 2 Native login | Email/password login fails | AJAX/nonce/Supabase token grant | AJAX response + nonce + `/auth/v1/token` result | `includes/woocommerce-overrides/class-bw-supabase-auth.php` | Critical | High |
| 3 Callback/token bridge | Callback loop, loading stuck, wrong landing | JS bridge/callback exchange/session set | callback params + bridge network + callback traces | `assets/js/bw-supabase-bridge.js` | Critical | Critical |
| 4 Guest provisioning invite | No invite after purchase | Woo hook/status/invite dispatch | order status trace + invite trace logs/meta | `includes/woocommerce-overrides/class-bw-supabase-auth.php` | Critical | High |
| 5 Order-received CTA | Wrong CTA copy/action post-purchase | Template branch logic | order-received branch trace + provider/onboarding state | `woocommerce/templates/checkout/order-received.php` | High | Medium |
| 6 Create password gate | Cannot complete password or gate loops | Password handler/token context/marker convergence | handler responses + marker + token presence | `includes/woocommerce-overrides/class-bw-my-account.php` | Critical | Critical |
| 7 Guest order claim | Orders/downloads missing after onboarding | Claim/mapping layer | order owner/meta vs mapped user + claim traces | `includes/woocommerce-overrides/class-bw-supabase-auth.php` | Critical | High |
| 8 Resend invite | Resend blocked/confusing status | Resend handler/throttle/active-account branch | resend response + invite throttle markers | `woocommerce/templates/myaccount/form-login.php` | High | Medium |
| 9 Expired link handling | Expired link lands wrong place | Error parser/redirect resolver | final URL + `otp_expired` params + setting | `includes/woocommerce-overrides/class-bw-supabase-auth.php` | High | High |
| 10 Refresh/session persistence | Random session loss, auth drift | refresh token + storage mode fallback | cookie/usermeta token presence + refresh response | `includes/woocommerce-overrides/class-bw-supabase-auth.php` | Critical | High |
| 11 Logout cleanup | Callback/loading page after logout | logout cleanup + stale callback URL | URL params + logout hooks + cookies | `includes/woocommerce-overrides/class-bw-supabase-auth.php` | Medium | Medium |

## 8. First-Response Playbook by Symptom

1. User cannot log in with email/password.
- Check Flow 2.
- Validate AJAX nonce and Supabase password grant response.
- Compare with rollback-stable handler behavior in `class-bw-supabase-auth.php`.

2. User clicks invite/magic link and flow does not complete.
- Check Flow 3 first, then Flow 9 if expired-token signature appears.
- Inspect callback marker, bridge JS execution, and callback traces.

3. Guest checkout succeeds but invite is missing.
- Check Flow 4.
- Inspect order status transition traces and invite throttle/sent markers.

4. Order received page shows wrong guidance.
- Check Flow 5.
- Validate provider branch and onboarding state resolution.

5. Create password fails or repeats.
- Check Flow 6.
- Confirm token context exists, onboarding validator path is used, and marker convergence occurs.

6. Orders/downloads are missing after account setup.
- Check Flow 7.
- Verify claim/mapping execution and order owner metadata.

7. Resend invite gives inconsistent responses.
- Check Flow 8.
- Distinguish throttle condition vs already-active account branch.

8. Expired link does not go to recovery page.
- Check Flow 9.
- Verify `otp_expired` parsing and configured redirect target.

9. Session randomly disappears.
- Check Flow 10.
- Validate dual-mode session persistence (cookie/usermeta) with fallback and refresh call outcomes.

10. Logout shows callback/loading screen.
- Check Flow 11.
- Remove stale callback marker from URL and verify logout cleanup path behavior.

## 9. Runtime Realities (must not be mis-documented)

- Invite provisioning hooks are broader than just `processing`/`completed`.
- Token-login nonce policy includes controlled same-site callback allowances.
- Two different password validators exist and are intentionally split by flow.
- Order-received onboarding reminder path may be implemented as static/non-clickable onboarding reminder CTA branch.
- Logout cleanup expires cookies but does not clear all token usermeta in the same path.
- Session handling uses dual-mode session persistence (cookie/usermeta) with fallback.

### Canonical cross-links
- Architecture source of truth: `docs/40-integrations/supabase/supabase-architecture-map.md` (`## 12) Canonical Recovery Flow Inventory (Rollback-Stable Baseline`).
- Onboarding/recovery source of truth: `docs/40-integrations/supabase/supabase-create-password.md` (`## Canonical Onboarding and Recovery Flows (Rollback-Stable Baseline)`).

## Protected Surfaces Quick Matrix

| Surface | Severity | Flows at risk | Required smoke tests |
|---|---|---|---|
| `includes/woocommerce-overrides/class-bw-supabase-auth.php` | Critical | 1,2,3,4,6,7,8,9,10,11 | S1 guest purchase->invite; S2 callback bridge; S3 create-password success; S4 claimed orders/downloads; S5 logout cleanup |
| `includes/woocommerce-overrides/class-bw-my-account.php` | Critical | 6,7 | S3 gate enforcement + unlock; S4 claimed orders visible |
| `woocommerce/woocommerce-init.php` | Critical | 3,6,9,11 | S2 callback flow; S6 expired-link recovery route; S7 logout->clean login |
| `assets/js/bw-supabase-bridge.js` | Critical | 3,6,9,10 | S2 callback bridge; S6 expired handling; S8 refresh/re-entry |
| `assets/js/bw-account-page.js` | High | 2,6,8 | S9 native login submit; S10 resend invite |
| `assets/js/bw-my-account.js` | High | 3,6,10,11 | S3 create-password UI path; S8 session continuity; S7 logout rendering |
| `assets/js/bw-password-modal.js` | High | 6 | S3 password modal validation + submit |
| `woocommerce/templates/myaccount/form-login.php` | High | 1,2,8,9 | S9 provider-correct login; S10 resend; S6 expired recovery entry |
| `woocommerce/templates/myaccount/my-account.php` | High | 1,3,6,7,11 | S2 callback landing; S3 onboarding gate; S7 logout view |
| `woocommerce/templates/myaccount/auth-callback.php` | Critical | 3,9 | S2 callback completion; S6 expired callback branch |
| `woocommerce/templates/myaccount/set-password.php` | High | 6 | S3 set-password completion |
| `woocommerce/templates/checkout/order-received.php` | Critical | 4,5,7 | S1 guest order-received CTA; S4 post-setup order visibility |

### Required governance rule
- Any change on these surfaces triggers a **Supabase Flow Risk Alert**.
- Mandatory actions before merge:
  - run required smoke tests from the matrix,
  - compare behavior with the Rollback-Stable Baseline,
  - record result in task close notes.
