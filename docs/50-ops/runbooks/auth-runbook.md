# Auth Runbook

## 1. Domain Scope
Includes account access flows and provider logic across WordPress auth, Supabase auth, and social login coupling.

Verification Hold Note:
- Onboarding marker convergence hardening is implemented, but verification identified anomalies requiring deeper investigation before closure.

Related folders:
- `docs/40-integrations/auth/`
- `docs/40-integrations/supabase/`
- `docs/30-features/my-account/`

Related docs:
- `../../40-integrations/auth/social-login-setup-guide.md`
- `../../40-integrations/supabase/supabase-create-password.md`
- `../../30-features/my-account/my-account-complete-guide.md`
- `../regression-protocol.md`

## 2. Critical Risk Points
- Provider mismatch (WordPress vs Supabase) causing wrong gate behavior.
- Login state drift between frontend and backend session assumptions.
- Social login callback/config mismatches.
- Account page UX regressions around auth gating.

High-risk integrations and dependencies:
- Supabase token bridge + onboarding flow.
- WordPress session and WooCommerce account rendering.
- OAuth provider app settings and callback URLs.

## 3. Pre-Maintenance Checklist
- Read auth/supabase/my-account docs first.
- Confirm active provider mode and expected branching behavior.
- Identify fragile areas: create-password gating, callback handling, session transitions.

## 4. Safe Fix Protocol
- Keep provider-specific behavior isolated and explicit.
- Avoid mixed assumptions across WordPress and Supabase paths.
- Do not alter callback contracts without integration review.
- ADR required when auth provider strategy or cross-provider architecture changes.

## 5. Regression Checklist (Domain Specific)
- Validate login/logout cycle for active provider.
- Validate social login button/callback flow.
- Validate provider-specific account access rules.
- Validate onboarding/password gate behavior where applicable.
- Scan console and server logs for auth callback/session errors.
- Validate onboarding marker convergence (`bw_supabase_onboarded`) across token-login, callback re-entry, and password-modal completion.
- Validate that invite/resend paths do not regress already-onboarded users into false onboarding lock.
- Validate callback convergence invariants on `bw_auth_callback` routes:
  - no loader persistence after failed/invalid callback payload
  - no callback re-entry loop after browser refresh
  - deterministic terminal fallback to account or set-password route
  - stale callback markers in session storage are cleared

## 6. Documentation Update Requirements
- Update `CHANGELOG.md` for auth behavior changes.
- Update auth/supabase/my-account docs when flows or constraints change.
- Update ADR for provider model or auth architecture changes.
