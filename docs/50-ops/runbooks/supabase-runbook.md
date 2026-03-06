# Supabase Runbook

## 1. Domain Scope
Includes Supabase-linked account provisioning, callback bridging, create-password lifecycle, and related audits.

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
