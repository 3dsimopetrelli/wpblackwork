# Admin -> Runtime Propagation Map

## 1) Purpose
This map defines how admin configuration propagates into runtime behavior and invariants across Checkout, Payments, Supabase/Auth, My Account, and Brevo.

It is used to:
- verify that a toggle has a real runtime effect
- detect configuration drift (set value not respected at runtime)
- identify high-risk toggles that require regression validation

## 2) Propagation Model
Standard propagation pipeline:

`Admin UI -> Option Key -> Storage (wp_options/usermeta) -> Runtime Reader -> Behavioral Effect -> Surface Impact`

Runtime evaluation points used in this codebase:
- plugin bootstrap/init (`plugins_loaded`, gateway constructors)
- per-request template rendering (checkout/my-account templates)
- script localization at enqueue time
- webhook/callback execution path
- order status hooks

## 3) Payment Toggles
| Option Key | Where Saved | Runtime Reader | Evaluated When | Surface Affected | Invariant Impacted |
|---|---|---|---|---|---|
| `bw_stripe_enabled` | Not observed as active key in current implementation | N/A | N/A | N/A | Drift risk: expected key without effective reader |
| `bw_google_pay_enabled` | Checkout admin settings (wp_options) | `woocommerce-init.php`, `BW_Google_Pay_Gateway` | enqueue + gateway construct + template readiness checks | checkout payment selector, wallet script load, gateway availability | selector determinism, payment authority |
| `bw_apple_pay_enabled` | Checkout admin settings (wp_options) | `woocommerce-init.php`, `BW_Apple_Pay_Gateway`, `payment.php` readiness text | enqueue + gateway construct + template render | checkout selector + Apple Pay flow | selector determinism, non-false-actionable UI |
| `bw_klarna_enabled` | Checkout admin settings (wp_options) | `BW_Klarna_Gateway`, `payment.php` readiness checks | gateway construct + template render | checkout selector + Klarna availability messaging | actionable readiness integrity |
| Stripe test/live keys (`bw_google_pay_test_publishable_key`, `bw_google_pay_publishable_key`, `*_secret_key`, `*_webhook_secret`, Klarna equivalents, Apple equivalents) | Checkout admin settings (wp_options) | gateway classes + localized wallet params | gateway init/process/webhook + checkout render | payment processing, webhook validation, wallet initialization | payment authority, webhook idempotency/validity |

Observed Stripe enable model (implementation reality):
- no active `bw_stripe_enabled` toggle found as primary runtime guard
- effective availability is driven by:
  - Woo gateway enable states (`woocommerce_<gateway>_settings[enabled]`)
  - Blackwork gateway toggles and key readiness checks
  - `woocommerce_available_payment_gateways` filtering

High-risk payment propagation notes:
- test/live mode drift if publishable/secret/webhook keys are mixed across modes.
- Apple Pay fallback to Google Pay keys can hide misconfiguration and create mode ambiguity.

## 4) Auth / Supabase Toggles
| Option Key | Where Saved | Runtime Reader | Evaluated When | Behavioral Effect | Surface Impact |
|---|---|---|---|---|---|
| `bw_account_login_provider` | Admin auth/login settings (`wp_options`) | my-account templates, `class-bw-my-account.php`, `class-bw-supabase-auth.php` | per-request + AJAX checks | provider switch (WordPress vs Supabase), gating behavior | login form, callback path, onboarding lock |
| `bw_supabase_login_mode` | Supabase settings (`wp_options`) | localized auth config in `woocommerce-init.php` + auth templates | script enqueue/request | native vs OIDC auth path selection | login UI and redirect behavior |
| `bw_supabase_project_url` | Supabase settings | supabase bridge/auth handlers + localized JS | request/AJAX + enqueue | API endpoint base for auth/token/user calls | callback, login, password/email updates |
| `bw_supabase_anon_key` | Supabase settings | supabase bridge/auth handlers + localized JS | request/AJAX + enqueue | client/server auth calls | OTP/login/callback behavior |
| `bw_supabase_service_role_key` | Supabase settings | `class-bw-supabase-auth.php` invite/provision path | order paid/invite flow | server-side invite provisioning | guest->account onboarding continuity |
| `bw_supabase_session_storage` | Supabase settings | token storage/retrieval helpers in `class-bw-supabase-auth.php` | auth actions + session reads | cookie vs usermeta token persistence | callback/session bridge stability |
| `bw_supabase_enable_wp_user_linking` | Supabase settings | session store bridge (`bw_mew_supabase_store_session`) | token-login/auth bridge | link supabase identity to WP user | auth/session authority boundary |
| `bw_supabase_create_wp_users` | Supabase settings | session store bridge | token-login/auth bridge | auto-create WP user if not existing | onboarding and claim continuity |
| `bw_supabase_checkout_provision_enabled` | Supabase settings | order-received template + invite handler | post-order and order-status hooks | enables provisioning/invite post checkout | guest paid flow and claim path |
| onboarding controls (`bw_supabase_magic_link_enabled`, `bw_supabase_login_password_enabled`, `bw_supabase_otp_allow_signup`, oauth toggles, redirect URLs) | Supabase settings | localized auth JS + auth handlers | enqueue + runtime auth steps | controls auth method availability and callback target | login surface, callback convergence |

Propagation path (Auth/Supabase):
- Admin save (`wp_options`) -> localized script config and PHP `get_option` reads -> runtime branch selection -> callback/onboarding state transitions -> My Account gating/render outcome.

Key drift risk:
- provider switched in admin while active user sessions/callback URLs still reflect previous mode.

## 5) Brevo / Consent Toggles
Primary settings storage (current implementation):
- `bw_mail_marketing_general_settings` (array)
- `bw_mail_marketing_checkout_settings` (array)
- legacy fallback: `bw_checkout_subscribe_settings`

| Config Item | Storage Key / Field | Runtime Reader | Evaluated When | Effect Surface | Failure Isolation |
|---|---|---|---|---|---|
| Brevo API key | `bw_mail_marketing_general_settings[api_key]` | checkout subscribe frontend/admin | checkout/order hooks + admin actions | remote sync enablement | missing key -> skip/error without blocking checkout |
| list IDs | `...general_settings[list_id]` (+ resolver) | frontend subscribe processor + service helper | paid/created subscribe trigger | target Brevo list assignment | missing list -> skip with reason |
| consent enable toggle | `...checkout_settings[enabled]` | frontend inject/save/subscribe hooks | checkout render + order lifecycle hooks | newsletter field + processing activation | disabled -> no field/no subscribe path |
| subscribe timing | `...checkout_settings[subscribe_timing]` (`created`/`paid`) | frontend hook branching | order created vs paid transitions | trigger timing | prevents premature/duplicate writes |
| double opt-in mode/settings | `...general_settings[default_optin_mode]`, template/redirect/sender fields, channel mode | frontend subscribe processor | subscribe execution | DOI vs SOI flow | invalid DOI config -> controlled error path |

Consent propagation path:
- checkout field capture -> order meta (`_bw_subscribe_newsletter`, `_bw_subscribe_consent_*`) -> paid/created hooks -> Brevo client call -> status meta update (`_bw_brevo_*`) -> admin observability.

## 6) My Account / UX Toggles (if any)
| Option Key | Runtime Reader | Effect |
|---|---|---|
| `bw_account_login_provider` | my-account templates + gating hooks | controls login surface and onboarding enforcement |
| `bw_myaccount_black_box_text` | dashboard helper | support/help content rendering |
| `bw_myaccount_support_link` | dashboard helper | support CTA target |
| `bw_supabase_debug_log` | auth/account bridge handlers and localized JS | debug diagnostics visibility/log behavior |

My Account gating is primarily state-driven (session + onboarding marker), with provider toggle as high-impact branch control.

## 7) High-Risk Propagation Zones
- Toggles evaluated at gateway constructor/init:
  - changes may not affect in-flight sessions/requests until next request lifecycle.
- Toggles cached in localized JS:
  - values are fixed at enqueue/render time for that page load; admin changes mid-session are not reflected until refresh.
- Mode drift risk:
  - test/live key mismatch across publishable, secret, webhook keys.
- Provider switch mid-session risk:
  - `bw_account_login_provider` changed while callback/auth-in-progress state from previous mode persists.
- Fallback-key behavior risk:
  - Apple Pay fallback to Google Pay keys can mask missing Apple-specific configuration.

## 8) Drift Detection Rules
How to detect config drift:
1. Compare admin option values with runtime localized payload (`bwAccountAuth`, `bwSupabaseBridge`, wallet params) on fresh page load.
2. Verify gateway availability list in checkout matches enabled + key-ready toggles.
3. Trigger critical flows after toggle changes:
   - payment selection/render
   - callback/login
   - provisioning invite
   - consent subscribe path

How to validate toggle runtime effect:
- For each changed key, execute one direct behavioral check in the owning surface.
- Confirm storage -> reader -> effect chain with logs/meta/UI evidence.

Toggles that require regression validation:
- `bw_account_login_provider`
- `bw_supabase_login_mode`
- `bw_supabase_checkout_provision_enabled`
- `bw_google_pay_enabled`, `bw_apple_pay_enabled`, `bw_klarna_enabled`
- any key affecting Stripe mode/keys/webhook secrets
- Brevo checkout `enabled`, `subscribe_timing`, and DOI mode settings

## 9) References
- [Blast-Radius Consolidation Map](./blast-radius-consolidation-map.md)
- [System State Matrix](./system-state-matrix.md)
- [Callback Contracts](./callback-contracts.md)
- [Risk Register](./risk-register.md)
- [Payments Architecture Map](../40-integrations/payments/payments-architecture-map.md)
- [Auth Architecture Map](../40-integrations/auth/auth-architecture-map.md)
- [Supabase Architecture Map](../40-integrations/supabase/supabase-architecture-map.md)
- [Brevo Architecture Map](../40-integrations/brevo/brevo-architecture-map.md)

## 10) Tier 0 Admin Toggles
These toggles are classified as Tier 0 because changing them can impact cross-domain authority, payment truth, onboarding continuity, or consent guarantees.
Any change to these requires execution of the Regression Minimum Suite.

| Toggle Key | Domain | Why Tier 0 | Requires Regression Journeys |
|---|---|---|---|
| `bw_account_login_provider` | Auth / My Account / Supabase | switches provider authority model and callback/onboarding gating behavior | `R-01`, `R-02`, `R-05`, `R-06` |
| `bw_supabase_login_mode` | Supabase / Auth | changes runtime path (native vs OIDC) and callback/session ownership flow | `R-02`, `R-05`, `R-06` |
| `bw_supabase_checkout_provision_enabled` | Supabase / Checkout / My Account | enables/disables guest post-order provisioning and claim path | `R-01`, `R-05` |
| `bw_supabase_session_storage` | Supabase / Auth | changes token persistence strategy (cookie/usermeta) affecting callback/session convergence | `R-05`, `R-06` |
| `bw_google_pay_enabled` | Payments / Checkout | controls wallet surface availability and selector determinism under refresh | `R-01`, `R-02`, `R-07` |
| `bw_apple_pay_enabled` | Payments / Checkout | controls Apple Pay actionability and selector + wallet fallback behavior | `R-01`, `R-02`, `R-07` |
| `bw_klarna_enabled` | Payments / Checkout | controls Klarna gateway readiness and paid/failed branch behavior | `R-01`, `R-03`, `R-07` |
| Stripe mode/secret/webhook keys (`bw_google_pay_test_mode`, `bw_google_pay_*secret*`, `bw_google_pay_*publishable*`, `bw_google_pay_*webhook_secret*`, `bw_apple_pay_*secret*`, `bw_apple_pay_*publishable*`, `bw_apple_pay_*webhook_secret*`, `bw_klarna_*secret*`, `bw_klarna_*publishable*`, `bw_klarna_*webhook_secret*`) | Payments | mode/key mismatch can break payment truth, webhook validation, and gateway convergence | `R-01`, `R-02`, `R-03`, `R-04`, `R-07` |
| Brevo checkout `enabled` (`bw_mail_marketing_checkout_settings[enabled]`) | Brevo / Checkout | toggles consent capture + subscribe processing path; must remain non-blocking | `R-08`, `R-09` |
| Brevo `subscribe_timing` (`bw_mail_marketing_checkout_settings[subscribe_timing]`) | Brevo / Checkout / Orders | shifts trigger from created->paid path and can alter consistency with payment authority timing | `R-01`, `R-08`, `R-09` |
| Brevo opt-in mode SOI/DOI (`bw_mail_marketing_general_settings[default_optin_mode]`, channel opt-in mode) | Brevo / Consent | changes consent execution mode and remote sync semantics; can drift from local authority expectations | `R-08`, `R-09` |
