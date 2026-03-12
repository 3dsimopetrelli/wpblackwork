# Start Chat — Documentation Pack 1

Generated from repository docs snapshot (excluding docs/tasks).


---

## Source: `docs/00-governance/admin-runtime-propagation-map.md`

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

## 10) Tier 0 Admin Toggles

These toggles are classified as Tier 0 because changing them can impact cross-domain authority, payment truth, onboarding continuity, or consent guarantees.
Any change to these requires execution of the Regression Minimum Suite.

| Toggle Key | Domain | Why Tier 0 | Requires Regression Journeys |
|------------|--------|------------|-----------------------------|
| `bw_account_login_provider` | Auth / My Account | Switches provider authority boundary and callback logic | R-01, R-02, R-05 |
| `bw_supabase_login_mode` | Supabase / Auth | Changes authentication flow shape (native vs OIDC) | R-05, R-06 |
| `bw_supabase_checkout_provision_enabled` | Supabase / Checkout | Alters provisioning + claim flow | R-01, R-05 |
| `bw_supabase_session_storage` | Supabase | Affects session/token convergence | R-05 |
| `bw_google_pay_enabled` | Payments | Alters gateway availability and selector determinism | R-01, R-02, R-07 |
| `bw_apple_pay_enabled` | Payments | Alters wallet availability and UI eligibility | R-01, R-07 |
| `bw_klarna_enabled` | Payments | Alters selector determinism and readiness logic | R-01, R-07 |
| Stripe publishable/secret/webhook keys | Payments | Impacts payment truth and webhook authority | R-01, R-02, R-04 |
| Brevo checkout `enabled` | Brevo / Checkout | Controls consent capture + remote write activation | R-08, R-09 |
| Brevo `subscribe_timing` | Brevo | Alters order lifecycle hook timing | R-08 |
| Brevo opt-in mode (SOI/DOI) | Brevo | Changes confirmation model and consent finalization | R-08 |


---

## Source: `docs/00-governance/ai-task-protocol.md`

# Blackwork AI Task Protocol

This document defines the mandatory operational protocol that ALL AI agents
must follow when working inside the Blackwork repository.

All implementation work MUST follow the governance-controlled task lifecycle.

AI agents MUST treat this document as the authoritative entry point.

## 0) Task Initialization

All work MUST begin with a governed task.

AI agents MUST:

1. Create or receive a task description
2. Complete the Task Start Template
3. Validate the Acceptance Gate
4. Only then begin implementation

Normative rule:

Implementation MUST NOT begin without a completed Task Start Template.

## 1) Mandatory Task Lifecycle

All implementation work MUST follow this lifecycle:

Task Initialization  
→ Task Start Template completion  
→ Acceptance Gate  
→ Implementation  
→ Verification  
→ Documentation alignment  
→ Task Closure Template completion  
→ Release Gate  
→ Deployment

Normative rule:

AI agents MUST NOT implement code outside a governed task lifecycle.

Normative rule:

No deployment should occur until the Release Gate checklist passes.

## 2) Required Templates

Before starting implementation, the following template MUST be completed:

`docs/templates/task-start-template.md`

Before closing a task, the following template MUST be completed:

`docs/templates/task-closure-template.md`

Normative rules:

- Implementation MUST NOT begin until the Task Start Template is completed.
- A task MUST NOT be considered finished until the Task Closure Template is completed.

## 3) Mandatory Documentation Alignment

All AI agents MUST verify documentation impact before implementation.

The following documentation layers MUST be evaluated:

`docs/00-governance/`  
`docs/00-planning/`  
`docs/10-architecture/`  
`docs/20-development/`  
`docs/30-features/`  
`docs/40-integrations/`  
`docs/50-ops/`  
`docs/60-adr/`  
`docs/60-system/`

Normative rule:

If system behavior changes, the corresponding documentation MUST be updated.

## 4) Governance Compliance

AI agents MUST respect the following governance artifacts:

Risk Register  
`docs/00-governance/risk-register.md`

Decision Log  
`docs/00-planning/decision-log.md`

Core Evolution Plan  
`docs/00-planning/core-evolution-plan.md`

Normative rule:

Changes that affect system behavior, authority ownership, or risk posture
MUST update the corresponding governance documents.

## 5) Determinism Requirement

All implementations MUST respect system determinism guarantees.

AI agents MUST explicitly verify:

- Input/output determinism
- Ordering determinism
- Retry determinism
- State convergence determinism

Normative rule:

Non-deterministic behavior in critical system flows MUST NOT be introduced.

## 6) Scope Discipline

Implementation MUST respect declared task scope.

AI agents MUST NOT:

- modify files outside the declared scope
- introduce hidden coupling
- mutate runtime surfaces without declaration

Normative rule:

If additional surfaces are discovered during implementation,
the task MUST pause and the scope MUST be updated.

## 7) Abort Conditions

AI agents MUST immediately stop implementation if any of the following occurs:

- Scope drift detected
- Authority ownership unclear
- System invariants at risk
- Determinism cannot be guaranteed
- Required documentation alignment cannot be completed
- ADR required but not approved

## 8) Required Reading Before Implementation

Before implementing code, AI agents MUST read:

`docs/templates/task-start-template.md`  
`docs/templates/task-closure-template.md`

AI agents MUST also review the following documentation layers
when they are relevant to the task:

`docs/00-governance/`  
`docs/10-architecture/`  
`docs/20-development/`

Normative rule:

AI agents MUST understand system governance before modifying the system.

## 9) Governance Enforcement Rule

This protocol is mandatory for all AI agents operating inside the repository.

AI agents MUST treat:

`docs/templates/task-start-template.md`  
`docs/templates/task-closure-template.md`

as binding governance documents.

Any implementation performed outside this protocol
is considered a governance violation.

If this repository contains a `docs/` directory,
AI agents MUST treat it as the authoritative system documentation.

## 10) Performance Evidence Recording

When a task introduces performance-related changes (query optimization, caching, batching, request reduction, observer/coalescing behavior, etc.), the closure report SHOULD include minimal observable evidence of impact.

Recommended lightweight evidence types:

- Network request comparison (before vs after)
- Reduction of duplicate AJAX calls
- Cache-hit observation after initial load
- Approximate response-time band comparison
- Query-path rationale (why the new path is cheaper)

Note:
This does NOT require full benchmarking infrastructure.
Lightweight, observable verification is sufficient for governance closure evidence.

## 11) Release Gate Requirement

All deployable changes MUST pass the Release Gate before production deployment.

Release Gate checklist is defined in:

`docs/50-ops/release-gate.md`

Normative rules:

- Release Gate runs after task closure and before deployment.
- Deployment MUST NOT proceed until all mandatory Release Gate checks pass.


---

## Source: `docs/00-governance/authority-matrix.md`

# Blackwork Core — Authority Matrix

## 1. Purpose
This document prevents authority drift and cross-domain override violations.

This matrix MUST be used to verify:
- ownership of truth surfaces,
- allowed mutation boundaries,
- prohibited override paths,
- ADR escalation conditions for authority-impacting changes.

This document is governance-normative and MUST be interpreted together with the Domain Map and Runtime Hook Map.

## 2. Authority Doctrine Summary
Authority levels:
- **Defines Truth**: Domain owns canonical truth for a declared surface.
- **Mutates State**: Domain may write state within its declared boundary but does not own all truth surfaces it touches.
- **Signals Only**: Domain may emit coordination/lifecycle signals and MUST NOT own truth.
- **Presentation Only**: Domain may render UX/view state and MUST NOT create or redefine truth.

Tier meaning:
- **Tier 0**: Authority-critical surfaces. Changes affecting authority behavior REQUIRE ADR.
- **Tier 1**: Orchestration and integration surfaces. Changes REQUIRE regression validation.
- **Tier 2**: Presentation/utilities surfaces. Changes are UX/coordination scoped and MUST remain non-authoritative.

Reference doctrine:
- `docs/60-adr/ADR-002-authority-hierarchy.md`

## 3. Authority Matrix Table
| Domain | Tier | Defines Truth | May Mutate | May Read | MUST NOT Override | ADR Required? |
|---|---:|---|---|---|---|---|
| Redirect / Routing Authority | 0 | Yes | Yes | Yes | MUST NOT override protected commerce routes, admin/platform-critical routes, or payment/auth/consent authority surfaces. | Yes |
| Import Products Authority | 0 | Yes | Yes | Yes | MUST NOT override runtime cart/checkout/payment truth; MUST NOT bypass canonical product identity (SKU). | Yes |
| Payment Confirmation Authority | 0 | Yes | Yes | Yes | MUST NOT override routing authority, auth authority, or consent authority; MUST NOT accept UI signals as payment truth. | Yes |
| Payment Provider Integration | 1 | No | Yes | Yes | MUST NOT override Payment Confirmation Authority, routing authority, or auth authority boundaries. | No |
| Checkout Orchestration | 1 | No | Yes | Yes | MUST NOT override payment confirmation truth, routing authority, or consent authority. | No |
| Cart Interaction | 1 | No | Yes | Yes | MUST NOT override payment/order truth, redirect authority, or checkout authority boundaries. | No |
| Consent / Brevo Sync | 1 | No | Yes | Yes | MUST NOT override payment truth, order lifecycle authority, auth authority, or provisioning authority. | No |
| Supabase/Auth Sync | 1 | No | Yes | Yes | MUST NOT override payment confirmation truth, routing authority, or consent authority boundaries. | No |
| Header / Global Layout | 2 | No | No | Yes | MUST NOT override routing, payment, order, auth, provisioning, consent, or cart/checkout business truth. | No |
| Shared Runtime Utilities | 2 | No | No | Yes | MUST NOT override any authority domain; MUST NOT transform signaling into truth ownership. | No |

## 4. Override Prohibition Rules
- No cross-tier override is permitted.
- Presentation layers MUST NOT create or redefine authority truth.
- Silent ownership shifts are prohibited.
- Canonical SKU identity cannot be replaced without ADR.
- Protected commerce routes cannot be overridden.

Mandatory control rules:
- Domains MAY read upstream state when required by contract.
- Domains MUST NOT mutate outside declared ownership boundaries.
- Signaling and presentation domains MUST remain non-authoritative.

## 5. Escalation Rule
If a change modifies any of the following, ADR is mandatory:
- Authority ownership
- Tier classification
- Runtime hook priority for Tier 0 surfaces

Escalation requirements:
- Governance review MUST occur before merge.
- Domain Map and Runtime Hook Map references MUST be included in change rationale.
- Authority-impacting changes MUST be recorded through ADR before implementation finalization.

Cross-references:
- `docs/00-overview/domain-map.md`
- `docs/50-ops/runtime-hook-map.md`
- `docs/30-features/redirect/redirect-engine-v2-spec.md`
- `docs/30-features/import-products/import-products-vnext-spec.md`
- `docs/60-adr/ADR-002-authority-hierarchy.md`

## 6. Authority Conflict Resolution

Conflict rules:

- In any cross-domain conflict, Tier 0 authority MUST prevail.
- If two Tier 1 domains conflict, the domain that defines truth for the specific surface MUST prevail.
- Signal and Presentation domains MUST yield to Mutate/Define domains.
- UI state MUST NEVER override confirmed authority state.
- No runtime signal may redefine an authority-confirmed truth surface.

## 7. Authority Surface Invariant

- There MUST be exactly one defining authority per truth surface.
- Parallel authority definitions are prohibited.
- Creation of a new authority surface REQUIRES ADR.
- Authority duplication or shadow authority behavior is forbidden.
- Any detected authority overlap MUST trigger governance review.


---

## Source: `docs/00-governance/blackwork-new-chat-operating-protocol.md`

# Blackwork --- New Chat Operating Protocol

## Purpose

This document defines how a new ChatGPT session must behave when
starting a new task inside the Blackwork governance system.

The objective is: - Deterministic task handling - Governance-aware
execution - Clean documentation lifecycle - Controlled interaction
between ChatGPT and Codex - Proper use of Start and Close templates

This protocol MUST be followed in every new chat.

------------------------------------------------------------------------

# 1. Chat Initialization Behavior

When a new task begins, ChatGPT MUST:

1.  Ask the user to clearly describe:
    -   The objective
    -   The domain affected
    -   Whether code or documentation is involved
    -   Whether existing files are impacted
    -   The desired outcome
2.  Classify the task:
    -   Tier (0--3)
    -   Authority surface touched? (Yes/No)
    -   Data integrity risk (Low/Medium/High/Critical)
    -   ADR potentially required?
3.  Generate a completed `task-start-template.md` instance based on the
    user's description.

ChatGPT MUST NOT: - Skip classification - Start proposing solutions
before the task-start template is produced - Ignore governance structure

------------------------------------------------------------------------

# 2. Interaction Loop Between ChatGPT and Codex

After the Start Template is generated:

1.  The user copies it into Codex.

2.  Codex evaluates scope and may:

    -   Confirm direction
    -   Suggest structural adjustments
    -   Raise warnings
    -   Request clarifications

3.  The user pastes Codex feedback back into ChatGPT.

4.  ChatGPT:

    -   Analyzes Codex output
    -   Proposes optimal structural direction
    -   Refines strategy
    -   May update start template if necessary

This loop continues until: - Architecture is clean - Risk is
understood - Scope is clear - Determinism is preserved

Only then implementation proceeds.

------------------------------------------------------------------------

# 3. During Task Execution

ChatGPT MUST:

-   Maintain scope discipline
-   Respect declared surfaces
-   Protect authority boundaries
-   Avoid undeclared coupling
-   Keep determinism guarantees intact
-   Track documentation updates required

If governance drift is detected: - STOP - Escalate to ADR if needed

------------------------------------------------------------------------

# 4. Task Completion Protocol

A task is considered complete ONLY when:

1.  ChatGPT confirms:
    -   Acceptance criteria satisfied
    -   Determinism preserved
    -   No authority drift
    -   No undeclared surface modifications
    -   Regression surfaces verified
2.  Codex confirms:
    -   Implementation consistent
    -   No structural violations
    -   No unexpected changes

At that point:

ChatGPT MUST generate a completed `task-close-template.md` instance.

The user then: - Pastes it into Codex - Codex updates documentation
accordingly

------------------------------------------------------------------------

# 5. Documentation Awareness Rules

Every new chat MUST assume the following structure exists:

-   ADR system (`docs/60-adr/`)
-   Authority Matrix
-   Runtime Hook Map
-   Domain Map
-   Core Evolution Plan
-   Decision Log
-   Regression Protocol
-   Tier classification system
-   Invariant protection layer

ChatGPT MUST always consider:

-   Does this change authority ownership?
-   Does this introduce a new truth surface?
-   Does this weaken invariants?
-   Does this affect Tier 0 state?
-   Does this require roadmap update?
-   Does this require decision log entry?

------------------------------------------------------------------------

# 6. Non-Negotiable Principles

-   Determinism over cleverness
-   Authority clarity over convenience
-   Documentation synchronization over speed
-   No silent drift
-   No hidden coupling
-   No uncontrolled surface mutation

------------------------------------------------------------------------

# 7. Operational Flow Summary

New Chat → Task Description\
↓\
Start Template Generated\
↓\
Codex Review\
↓\
ChatGPT Strategy Refinement\
↓\
Implementation Loop\
↓\
Validation (ChatGPT + Codex)\
↓\
Close Template Generated\
↓\
Decision Log Updated\
↓\
Task Closed

------------------------------------------------------------------------

# Final Rule

If governance is unclear at any moment: Stop and clarify before
proceeding.


---

## Source: `docs/00-governance/blast-radius-consolidation-map.md`

# Blast-Radius Consolidation Map

## 1) Purpose
This map identifies the highest-risk technical surfaces across Checkout, Payments, Auth/Supabase, Brevo, and My Account.
It is used to scope change risk before implementation and to prevent regressions when refactoring shared flows.

How to use:
- locate the target file/class/hook before changes
- classify the change as Tier 0/1/2
- execute the required validation path based on tier

## 2) Blast Radius Severity Scale
- Tier 0 (System-critical): a change can break global commerce or global authentication continuity.
- Tier 1 (Domain-critical): a change can break a major domain end-to-end but not all domains.
- Tier 2 (Localized): a change can break one UI surface, admin panel, or one bounded behavior.

## 3) Tier 0 Surfaces (System-critical)
### Checkout
- Anchor: `woocommerce/templates/checkout/payment.php`
- Why Tier 0: it defines the effective payment method selection contract for checkout submission.
- Primary failure modes:
  - selected radio state diverges from submitted gateway
  - payment fields render mismatch after fragment updates
  - wallet/custom selector duplication creates invalid submission path
- Dependent docs:
  - [Checkout Architecture Map](../30-features/checkout/checkout-architecture-map.md)
  - [Payments Architecture Map](../40-integrations/payments/payments-architecture-map.md)

- Anchor: `woocommerce/templates/checkout/order-received.php`
- Why Tier 0: it is the post-payment transition surface where commerce truth meets auth/provisioning guidance.
- Primary failure modes:
  - paid order shown with unstable CTA/gating branch
  - guest-to-account transition loops or dead-ends
  - payment-failed state represented as success path
- Dependent docs:
  - [Checkout Architecture Map](../30-features/checkout/checkout-architecture-map.md)
  - [Supabase-Checkout-Payments Integration Map](../60-system/integration/supabase-payments-checkout-integration-map.md)

### Payments
- Anchor: `includes/Gateways/class-bw-abstract-stripe-gateway.php` (`handle_webhook()`)
- Why Tier 0: webhook validation/idempotency is the authority bridge from provider payment truth to Woo order state.
- Primary failure modes:
  - invalid signature accepted/rejected incorrectly
  - duplicate webhook side effects on same order
  - wrong mode secret causes false failures in production
- Dependent docs:
  - [Payments Architecture Map](../40-integrations/payments/payments-architecture-map.md)
  - [Unified Callback Contracts](./callback-contracts.md)

- Anchor: `includes/Gateways/class-bw-google-pay-gateway.php`, `class-bw-apple-pay-gateway.php`, `class-bw-klarna-gateway.php` (`process_payment()`, `handle_webhook()`)
- Why Tier 0: these gateways control payment execution and final order transitions.
- Primary failure modes:
  - order marked pending forever (webhook/process race)
  - gateway return route diverges from webhook outcome
  - payment method appears available without key readiness
- Dependent docs:
  - [Payments Architecture Map](../40-integrations/payments/payments-architecture-map.md)
  - [Checkout Architecture Map](../30-features/checkout/checkout-architecture-map.md)

### Supabase/Auth
- Anchor: `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- Why Tier 0: central auth bridge, onboarding marker lifecycle, token/session continuity, and order claim attachment.
- Primary failure modes:
  - onboarding marker flips incorrectly (`bw_supabase_onboarded`)
  - callback/token bridge creates login loop or stale session
  - guest order claim fails or duplicates ownership mapping
- Dependent docs:
  - [Supabase Architecture Map](../40-integrations/supabase/supabase-architecture-map.md)
  - [Auth Architecture Map](../40-integrations/auth/auth-architecture-map.md)
  - [Cross-Domain State Dictionary](./cross-domain-state-dictionary.md)

- Anchor: `assets/js/bw-supabase-bridge.js` + `woocommerce/woocommerce-init.php` (`bw_mew_supabase_early_invite_redirect_hint`)
- Why Tier 0: callback routing and anti-flash behavior are first-paint critical for auth continuity.
- Primary failure modes:
  - infinite callback redirects
  - pre-auth content flash on My Account callback path
  - auth-in-progress state never cleared
- Dependent docs:
  - [Supabase Architecture Map](../40-integrations/supabase/supabase-architecture-map.md)
  - [Unified Callback Contracts](./callback-contracts.md)

### My Account
- Anchor: `includes/woocommerce-overrides/class-bw-my-account.php`
- Why Tier 0: controls My Account gating, endpoint behavior, auth callback normalization, and profile/settings save gates.
- Primary failure modes:
  - users blocked from account surfaces after valid login
  - set-password and callback routes diverge from session state
  - account endpoint redirects break downloads/orders access
- Dependent docs:
  - [My Account Architecture Map](../30-features/my-account/my-account-architecture-map.md)
  - [Auth Architecture Map](../40-integrations/auth/auth-architecture-map.md)

### Brevo
- Anchor: `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php` (`can_subscribe_order()`, paid hooks)
- Why Tier 0: consent gate and paid-trigger timing protect legal compliance and commerce non-blocking behavior.
- Primary failure modes:
  - API call without valid consent evidence
  - subscribe path blocks checkout/order processing
  - consent metadata overwritten by fallback path
- Dependent docs:
  - [Brevo Architecture Map](../40-integrations/brevo/brevo-architecture-map.md)
  - [System Normative Charter](./system-normative-charter.md)

### Governance callbacks
- Anchor: governance contracts `./callback-contracts.md` and runtime callback endpoints in gateway/auth layers
- Why Tier 0: these invariants constrain callback authority boundaries across payment/auth/order systems.
- Primary failure modes:
  - callback mutates non-owned state domain
  - non-idempotent callback side effects
  - callback result cannot converge to stable terminal state
- Dependent docs:
  - [Unified Callback Contracts](./callback-contracts.md)
  - [Cross-Domain State Dictionary](./cross-domain-state-dictionary.md)

## 4) Tier 1 Surfaces (Domain-critical)
- Anchor: `assets/js/bw-payment-methods.js`
- Why Tier 1: domain-critical checkout UI orchestrator for payment panels and selected method synchronization.
- Primary failure modes:
  - selected visual panel and radio input diverge
  - panel state breaks after checkout fragment refresh
  - hidden wallet/card sections remain active unexpectedly
- Dependent docs:
  - [Checkout Architecture Map](../30-features/checkout/checkout-architecture-map.md)

- Anchor: `assets/js/bw-checkout.js`
- Why Tier 1: checkout interaction flow can fail while payment/auth authority remains intact.
- Primary failure modes:
  - broken DOM listeners after checkout updates
  - free-order and field-state regressions
  - checkout-side UI errors without data corruption
- Dependent docs:
  - [Checkout Architecture Map](../30-features/checkout/checkout-architecture-map.md)

- Anchor: `assets/js/bw-account-page.js` and `assets/js/bw-password-modal.js`
- Why Tier 1: auth/onboarding UX can fail for My Account while global commerce still works.
- Primary failure modes:
  - OTP/create-password screens stuck
  - password modal cannot complete onboarding
  - stale pending-email/auth-flow client state
- Dependent docs:
  - [My Account Architecture Map](../30-features/my-account/my-account-architecture-map.md)
  - [Supabase Architecture Map](../40-integrations/supabase/supabase-architecture-map.md)

- Anchor: `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`
- Why Tier 1: admin observability/retry actions for Brevo can fail while storefront checkout remains functional.
- Primary failure modes:
  - retry/check endpoints fail silently
  - list columns/filters show stale sync state
  - bulk resync actions misreport results
- Dependent docs:
  - [Brevo Architecture Map](../40-integrations/brevo/brevo-architecture-map.md)

## 5) Tier 2 Surfaces (Localized)
- Anchor: `assets/js/bw-stripe-upe-cleaner.js`
- Why Tier 2: presentation cleanup layer around UPE/card UI.
- Primary failure modes:
  - duplicated card headings
  - cosmetic payment accordion inconsistencies
  - selector visual noise without payment state corruption
- Dependent docs:
  - [Payments Architecture Map](../40-integrations/payments/payments-architecture-map.md)

- Anchor: `assets/js/bw-checkout-notices.js`
- Why Tier 2: checkout notices placement/styling only.
- Primary failure modes:
  - notices render in wrong column
  - duplicate notice blocks
  - notice styling drift
- Dependent docs:
  - [Checkout Architecture Map](../30-features/checkout/checkout-architecture-map.md)

- Anchor: `assets/js/bw-my-account.js`
- Why Tier 2: tab switching and field UX enhancements on settings page.
- Primary failure modes:
  - tab activation glitches
  - floating-label visual regressions
  - password toggle UI mismatch
- Dependent docs:
  - [My Account Architecture Map](../30-features/my-account/my-account-architecture-map.md)

## 6) Cross-Domain Coupling Hotspots
- Collision point: order-received gating (`woocommerce/templates/checkout/order-received.php`)
- Domains colliding: Checkout + Payments + Auth/Supabase + My Account
- Protecting invariant: payment success remains authority; provisioning/auth can gate UI only, never payment truth.

- Collision point: `bw_auth_callback` bridge (`assets/js/bw-supabase-bridge.js`, `woocommerce/woocommerce-init.php`, `class-bw-my-account.php`)
- Domains colliding: Auth + Supabase + My Account
- Protecting invariant: callback is idempotent, anti-flash safe, and loop-free.

- Collision point: downloads visibility gate (`bw_supabase_onboarded`, guest-order claim in `class-bw-supabase-auth.php`)
- Domains colliding: Supabase + My Account + Orders
- Protecting invariant: order ownership claim is idempotent and does not mutate payment/order authority.

- Collision point: checkout paid-hook timing (`woocommerce_order_status_processing/completed`, `woocommerce_payment_complete`, `woocommerce_thankyou`)
- Domains colliding: Payments + Supabase provisioning + Brevo subscription
- Protecting invariant: non-blocking commerce; downstream sync/provision failures cannot roll back paid order truth.

- Collision point: payment selector rendering (`woocommerce/templates/checkout/payment.php`, `assets/js/bw-payment-methods.js`)
- Domains colliding: Checkout + Payments + Wallet integrations
- Protecting invariant: visible selected method, radio state, and submitted method remain deterministic.

## 7) Change Protocol (Normative)
For Tier 0 and Tier 1 changes:
- Regression protocol is mandatory: [Regression Protocol](../50-ops/regression-protocol.md)
- Mandatory governance review:
  - [Unified Callback Contracts](./callback-contracts.md)
  - [Cross-Domain State Dictionary](./cross-domain-state-dictionary.md)

Minimum verification surfaces:
- checkout payment selection + submit integrity
- payment webhook/callback idempotency path
- auth callback + My Account anti-flash path
- order-received guest/onboarded branching
- Brevo consent gate (no-consent = no remote write)

## 8) References
- [System Normative Charter](./system-normative-charter.md)
- [Cross-Domain State Dictionary](./cross-domain-state-dictionary.md)
- [Unified Callback Contracts](./callback-contracts.md)
- [Docs-Code Alignment Status](./docs-code-alignment-status.md)
- [Checkout Architecture Map](../30-features/checkout/checkout-architecture-map.md)
- [My Account Architecture Map](../30-features/my-account/my-account-architecture-map.md)
- [Payments Architecture Map](../40-integrations/payments/payments-architecture-map.md)
- [Auth Architecture Map](../40-integrations/auth/auth-architecture-map.md)
- [Supabase Architecture Map](../40-integrations/supabase/supabase-architecture-map.md)
- [Brevo Architecture Map](../40-integrations/brevo/brevo-architecture-map.md)
- [Supabase-Payments-Checkout Integration Map](../60-system/integration/supabase-payments-checkout-integration-map.md)
- [Regression Protocol](../50-ops/regression-protocol.md)


---

## Source: `docs/00-governance/callback-contracts.md`

# Unified Callback Contracts

## 1) Purpose
Define callback authority, invariants, and failure-containment rules across payment and auth domains.

Normative reference:
- [System Normative Charter](./system-normative-charter.md)
- [Cross-Domain State Dictionary](./cross-domain-state-dictionary.md)

---

## 2) Payment Webhook Contract

### Authority
Payment provider confirmation is final authority for payment outcome.

### Invariants
- Webhook must be idempotent.
- Webhook must converge order state exactly once.
- Repeated events must not duplicate side effects.
- Webhook must not modify identity or marketing state.

### Failure Containment
- Invalid events must not mutate order state.
- Logging required.
- Commerce must not regress after confirmation.

---

## 3) Supabase Auth Callback Contract

### Authority
Supabase + bridge establish identity/session continuity.
Not commerce authority.

### Invariants
- Callback must not override payment/order lifecycle.
- Must resolve session before rendering account UI.
- Anti-flash invariant (no intermediate My Account render before state resolution).
- Idempotent bridge behavior (no duplicate onboarding/claims).

### Failure Containment
- Callback failure must degrade to retry/auth state.
- Must not loop infinitely.
- Must not corrupt onboarding marker.

---

## 4) Order-Received Transition Contract

### Authority
Order state is authoritative.
Auth/provisioning may gate UI, not payment truth.

### Invariants
- Payment success must render stable order state.
- Provisioning failures must not revert payment state.
- Guest→Account claim must be idempotent.

---

## 5) Cross-Domain Callback Prohibitions

- No callback may override payment authority.
- No callback may rewrite Woo order lifecycle.
- No callback may persist UI-transient state.
- No callback may escalate privilege beyond WP session authority.

---

## 6) Callback Convergence Model

Every callback path must converge to a stable, repeat-safe state.
Repeated invocation must produce same terminal state.
No infinite redirect or re-execution loops allowed.


---

## Source: `docs/00-governance/cross-domain-state-dictionary.md`

# Cross-Domain State Dictionary

## 1) Purpose
This document is the single authoritative reference for state flags, ownership authority, and cross-domain read/write boundaries across core Blackwork domains.

Normative reference:
- [System Normative Charter](./system-normative-charter.md)

---

## 2) State Domains Overview Table

| Domain | Canonical State Object | Authority Owner | Stored Where | Can Be Read By | Can Be Written By | Must NEVER Override |
|---|---|---|---|---|---|---|
| Payment | Provider payment outcome + local payment mapping | Payment provider + gateway contract | Provider payload + gateway/order payment meta | Checkout runtime, order rendering, provisioning logic (read-only) | Gateway + validated webhook handlers | Order authority, auth authority |
| Order | Woo order status + order meta lifecycle | WooCommerce | `shop_order` status + order meta | Payments, auth gating, provisioning, marketing sync | Woo order lifecycle handlers + controlled integrations | Payment UI transient state, marketing status |
| Auth (WP session) | Logged-in / anonymous / expired session | WordPress session system | WP auth cookies/session context | Checkout, My Account, callback bridge, gating logic | WP auth handlers + bridge session establishment | Payment status, order completion, marketing sync |
| Supabase Identity | Invite/callback/onboarding identity state | Supabase auth + bridge layer | Supabase tokens + WP user/order meta markers | Auth flows, provisioning, account gating | Supabase bridge handlers + onboarding handlers | Payment completion, newsletter outcomes |
| Supabase Provisioning | Order-linked provisioning and ownership attachment state | Provisioning handlers | Order meta + user meta + downloadable permissions linkage | My Account access layer, order-received/account transitions | Provisioning hooks + claim/attach handlers | Newsletter/admin display toggles |
| Newsletter Consent (Brevo) | Consent evidence + sync outcome state | Local consent metadata | Order meta + user meta + Brevo sync meta | Brevo sync runtime, admin observability | Consent capture + gated sync handlers | Payment/order authority, auth authority |
| Checkout Runtime (UI-only transient) | Current selected method + wallet visibility + fragment refresh state | Frontend runtime JS | Browser DOM + in-memory state | Checkout UI scripts | Checkout JS only | Persisted cross-request state, order/payment authority |

---

## 3) Domain State Definitions

### 3.1 Payment State
Defines:
- payment intent/result status,
- provider confirmation outcome,
- webhook idempotency marker/processed-event guard.

Authority:
- payment provider and gateway/webhook contract.

Never overridden by:
- Auth, Supabase provisioning, Brevo sync.

---

### 3.2 Order State (WooCommerce)
Defines:
- order status lifecycle (`pending`/`processing`/`completed`/failure states),
- order meta ownership and persistence boundaries.

Authority:
- WooCommerce order lifecycle.

Never overridden by:
- payment UI transient state, auth flow, marketing sync state.

---

### 3.3 Auth State (WP Session)
Defines:
- logged-in,
- anonymous,
- session expired.

Authority:
- WordPress session/auth system.

Never overridden by:
- Supabase provisioning state, payment state.

---

### 3.4 Supabase Identity State
Defines:
- invited,
- otp_verified,
- password_set,
- onboarded marker (`bw_supabase_onboarded`).

Authority:
- Supabase identity flow + bridge layer that establishes local session continuity.

Never overridden by:
- order completion alone.

---

### 3.5 Supabase Provisioning State
Defines:
- order-linked provisioning eligibility,
- claimed ownership attachment,
- files/download accessibility readiness.

Authority:
- provisioning handlers and ownership-claim routines.

Never overridden by:
- newsletter status, admin display toggles.

---

### 3.6 Newsletter Consent State (Brevo)
Defines:
- consent given flag,
- consent timestamp,
- consent source,
- Brevo remote sync status.

Authority:
- local consent metadata.

Rule:
- remote API status does not redefine local consent truth.

---

### 3.7 Checkout Runtime State (Transient)
Defines:
- selected payment method,
- wallet button/method visibility,
- fragment refresh/rebind state.

Authority:
- checkout frontend runtime scripts.

Rule:
- this state must remain request-transient and must not become persisted business truth.

---

## 4) Cross-Domain Write Prohibitions (Normative Rules)
- Payment completion must not auto-mark Supabase onboarded.
- Newsletter subscription must not alter order/payment state.
- Auth login must not alter payment authority.
- Order creation must not imply identity completion.
- Provisioning failure must not revert payment/order state.

---

## 5) Idempotency & Convergence Guarantees
- Webhook idempotency: repeated payment events must converge to one stable local order/payment outcome.
- Supabase callback idempotency: repeated callback/bridge transitions must converge without loops or duplicate ownership effects.
- Brevo upsert convergence: repeated sync attempts converge on one contact identity/state intent.
- Order-provision reattachment safety: repeated claim/attach routines must be safe and non-duplicative.


---

## Source: `docs/00-governance/docs-code-alignment-status.md`

# Docs ↔ Code Alignment Status

## 1) Purpose
This report provides a single architecture-level view of documentation coverage against current code reality.
Use it to quickly identify what is aligned, what is fragile, and which documentation updates should be prioritized next.

## 2) Coverage Matrix (by domain)

### Admin
- Primary docs:
  - [Admin Panel Map](../../20-development/admin-panel-map.md)
  - [Admin Panel Reality Audit](../../50-ops/admin-panel-reality-audit.md)
- Code reality anchors:
  - `admin/class-blackwork-site-settings.php`
- Alignment status: `Mostly aligned`
- Known gaps:
  - Admin field inventory can drift as tabs evolve.
  - Mixed save models remain a long-term consistency risk.
  - AJAX action catalog is documented but high-churn.
- Highest-risk surfaces:
  - provider toggles and conditional sections
  - checkout/payment tab settings persistence
  - mail-marketing admin actions
  - nonce handling per tab/action
  - admin JS conditional rendering logic

### Media Library Admin (Media Folders)
- Primary docs:
  - [Media Folders Spec](../../30-features/media-folders/media-folders-module-spec.md)
  - [Media Folders Task Closure](../../tasks/media-folders-close-task.md)
- Code reality anchors:
  - `includes/modules/media-folders/media-folders-module.php`
  - `includes/modules/media-folders/admin/media-folders-admin.php`
  - `includes/modules/media-folders/admin/assets/media-folders.js`
  - `includes/modules/media-folders/runtime/media-query-filter.php`
  - `includes/modules/media-folders/runtime/ajax.php`
- Alignment status: `Aligned`
- Known gaps:
  - WordPress admin DOM drift can affect toolbar placement selectors over time.
  - Observer-heavy UI integration requires periodic performance revalidation on large libraries.
- Highest-risk surfaces:
  - `upload.php` toolbar/tablenav DOM anchors
  - `ajax_query_attachments_args` payload shape compatibility
  - marker endpoint batching/caching behavior
  - drag/drop event interaction with core Media Library uploader surface

### Checkout
- Primary docs:
  - [Checkout Architecture Map](../../30-features/checkout/checkout-architecture-map.md)
- Code reality anchors:
  - `woocommerce/woocommerce-init.php`
  - `woocommerce/templates/checkout/payment.php`
  - `woocommerce/templates/checkout/order-received.php`
  - `assets/js/bw-payment-methods.js`
- Alignment status: `Aligned`
- Known gaps:
  - Dynamic fragment-refresh edge cases remain inherently fragile.
  - Wallet fallback/UI sync behavior is sensitive to DOM contract changes.
  - Cross-domain CTA rendering can drift with template edits.
- Highest-risk surfaces:
  - payment selector contract
  - order-received gating branch
  - wallet scripts + selector sync
  - Stripe UPE cleaner interplay
  - checkout runtime localize payload contract

### Payments
- Primary docs:
  - [Payments Architecture Map](../../40-integrations/payments/payments-architecture-map.md)
- Code reality anchors:
  - `includes/Gateways/class-bw-abstract-stripe-gateway.php`
  - `includes/Gateways/class-bw-google-pay-gateway.php`
  - `includes/Gateways/class-bw-klarna-gateway.php`
  - `includes/Gateways/class-bw-apple-pay-gateway.php`
  - `assets/js/bw-google-pay.js`, `assets/js/bw-apple-pay.js`
- Alignment status: `Aligned`
- Known gaps:
  - Gateway readiness permutations can change with provider updates.
  - UPE/custom selector interoperability is structurally sensitive.
  - Webhook mapping assumptions require periodic verification.
- Highest-risk surfaces:
  - webhook validation/idempotency path
  - process_payment mapping logic
  - wallet capability fallback paths
  - test/live key mode resolution
  - checkout selector coupling

### Auth
- Primary docs:
  - [Auth Architecture Map](../../40-integrations/auth/auth-architecture-map.md)
- Code reality anchors:
  - `woocommerce/templates/myaccount/form-login.php`
  - `includes/woocommerce-overrides/class-bw-my-account.php`
  - `admin/class-blackwork-site-settings.php`
- Alignment status: `Mostly aligned`
- Known gaps:
  - Provider-specific branch complexity can drift with UI changes.
  - OIDC broker assumptions depend on external plugin behavior.
  - Social provider sub-flow boundaries require periodic reconfirmation.
- Highest-risk surfaces:
  - provider switch model
  - callback routing contract
  - login template branch rendering
  - WP session establishment assumptions
  - checkout/auth coupling points

### Supabase
- Primary docs:
  - [Supabase Architecture Map](../../40-integrations/supabase/supabase-architecture-map.md)
- Code reality anchors:
  - `includes/woocommerce-overrides/class-bw-supabase-auth.php`
  - `assets/js/bw-account-page.js`
  - `assets/js/bw-supabase-bridge.js`
  - `woocommerce/templates/myaccount/form-login.php`
- Alignment status: `Aligned`
- Known gaps:
  - Native + OIDC coexistence raises branch-complexity risk.
  - Callback anti-flash logic is sensitive to route/template edits.
  - Onboarding transitions require strict state consistency.
- Highest-risk surfaces:
  - token bridge and callback pipeline
  - onboarding marker transitions
  - invite/provisioning triggers
  - session storage mode behavior
  - guest-order ownership claim attachment

### Brevo
- Primary docs:
  - [Brevo Architecture Map](../../40-integrations/brevo/brevo-architecture-map.md)
- Code reality anchors:
  - `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php`
  - `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`
  - `includes/integrations/brevo/class-bw-brevo-client.php`
- Alignment status: `Aligned`
- Known gaps:
  - Legacy status/value variants still exist in runtime metadata.
  - DOI/single-opt-in branch behavior depends on admin configuration quality.
  - Remote-check vs sync action boundaries need ongoing discipline.
- Highest-risk surfaces:
  - consent hard gate
  - paid-hook trigger timing
  - retry/bulk resync action path
  - attribute fallback/retry chain
  - admin observability actions and filters

## 3) Cross-Domain Contracts Status

### Supabase ↔ Checkout
- Contract doc: [Supabase Architecture Map](../../40-integrations/supabase/supabase-architecture-map.md), [Checkout Architecture Map](../../30-features/checkout/checkout-architecture-map.md)
- Status: `Aligned`
- Known fragility points:
  - order-received guest/auth branching
  - onboarding gate consistency after callback
  - invite redirect path stability

### Payments ↔ Checkout
- Contract doc: [Payments Architecture Map](../../40-integrations/payments/payments-architecture-map.md), [Checkout Architecture Map](../../30-features/checkout/checkout-architecture-map.md)
- Status: `Aligned`
- Known fragility points:
  - selector vs submission method sync
  - wallet fallback precedence
  - fragment refresh re-bind integrity

### Brevo ↔ Checkout
- Contract doc: [Brevo Architecture Map](../../40-integrations/brevo/brevo-architecture-map.md), [Checkout Architecture Map](../../30-features/checkout/checkout-architecture-map.md)
- Status: `Mostly aligned`
- Known fragility points:
  - consent metadata capture at checkout boundaries
  - timing mode (`created` vs `paid`) expectations
  - admin retry semantics vs consent hard gate

### Supabase ↔ Payments ↔ Checkout
- Contract doc: [Supabase-Payments-Checkout Integration Map](../../60-system/integration/supabase-payments-checkout-integration-map.md)
- Status: `Aligned`
- Known fragility points:
  - post-payment provisioning failure handling
  - callback state transitions after paid orders
  - cross-domain CTA consistency (order-received vs account)

## 4) Non-Break Invariants Compliance Snapshot
Normative baseline: [System Normative Charter](../../00-governance/system-normative-charter.md)

- Admin:
  - Non-blocking commerce: `Pass` - admin controls do not execute commerce authority directly.
  - Authority hierarchy: `Pass` - config writes intent; runtime owns authority.
  - Callback discipline: `Risk` - callback-sensitive flags can be altered from admin model changes.
  - Idempotency: `Unknown` - depends on downstream runtime implementation per domain.

- Checkout:
  - Non-blocking commerce: `Pass` - primary commerce path remains authoritative.
  - Authority hierarchy: `Pass` - checkout respects payment/order authority boundaries.
  - Callback discipline: `Risk` - order-received/callback branches are high-coupling surfaces.
  - Idempotency: `Risk` - fragment refresh + JS rebinding can drift if contracts change.

- Payments:
  - Non-blocking commerce: `Pass` - payment flow authority explicit; non-core integrations remain external.
  - Authority hierarchy: `Pass` - webhook/process contracts define payment/order mapping boundaries.
  - Callback discipline: `Pass` - webhook integrity and idempotency are documented as mandatory.
  - Idempotency: `Pass` - normative gateway/webhook convergence model is explicit.

- Auth:
  - Non-blocking commerce: `Pass` - auth gating does not redefine payment completion authority.
  - Authority hierarchy: `Pass` - WP session remains auth authority.
  - Callback discipline: `Risk` - provider/OIDC callback paths are sensitive to routing changes.
  - Idempotency: `Mostly aligned` - bridge has guards, but route/UI drift remains risk.

- Supabase:
  - Non-blocking commerce: `Pass` - provisioning/onboarding degrade without blocking payment completion.
  - Authority hierarchy: `Pass` - onboarding/provisioning state separated from payment/order authority.
  - Callback discipline: `Pass` - anti-flash and callback invariants explicitly documented.
  - Idempotency: `Mostly aligned` - token/login/invite guards exist; repeated edge paths remain sensitive.

- Brevo:
  - Non-blocking commerce: `Pass` - sync failures do not block checkout/payment/order.
  - Authority hierarchy: `Pass` - local consent/state authority is explicit.
  - Callback discipline: `Unknown` - callback model is not primary in Brevo domain.
  - Idempotency: `Pass` - email-upsert and retry convergence model is explicit.

## 5) Next Actions (Strict Priority)
1. Add a governance index file for `docs/00-governance/` and link it from global docs navigation.
2. Normalize status taxonomy across Brevo docs (canonical vs runtime status names) in one short domain note.
3. Add a concise “cross-domain state dictionary” page (auth/payment/order/consent/provisioning flags and owners) under governance.
4. Add explicit versioned “contract change log” section to each architecture map (starting with Checkout, Supabase, Brevo).
5. Create a single “callback contracts” doc that consolidates webhook/callback invariants across Payments and Supabase.


---

## Source: `docs/00-governance/radar-analysis-workflow.md`

# Radar Findings Intake & Classification Workflow

## 1. Purpose
This workflow standardizes how radar/audit findings are ingested and routed in Blackwork governance.

Why it exists:
- Prevent ad-hoc handling of findings.
- Keep governance traceable and deterministic across chats/agents.
- Ensure findings are stored in existing governance containers, not scattered into random new files.

Rule:
- Radar findings must be classified first, then routed into existing documentation layers.

## 2. Core Principle
Radar does **not** create a parallel documentation system.

Radar output is discovery input only:
- identify issue
- classify issue
- route issue to an existing governance container

No standalone “radar universe” is allowed when current governance files already provide the destination.

## 3. The Existing Governance Containers
- Real governance risks  
  Destination: `docs/00-governance/risk-register.md`
- Standing operational rules / normative decisions  
  Destination: `docs/00-planning/decision-log.md` or ADR in `docs/60-adr/` (when authority-surface/normative architecture change requires it)
- Implemented fixes (task evidence)  
  Destination: `docs/tasks/BW-TASK-XXXX-closure.md`
- Technical bugs / future technical debt (not elevated to governance risk)  
  Destination: `docs/00-planning/core-evolution-plan.md`

## 4. End-to-End Flow
```text
Radar / Audit
    ↓
Analysis
    ↓
Classification
    ↓
Destination Decision
    ↓
Risk Register / Decision Log / Core Evolution Plan / Task Closure
```

## 5. Classification Table
| Classification | Meaning | Destination file | Implementation task required? |
|---|---|---|---|
| `VALID NEW RISK` | New governance-level threat not already tracked | `docs/00-governance/risk-register.md` | Usually yes (now/next based on severity) |
| `EXISTING RISK UPDATE` | Valid finding already covered by existing risk | `docs/00-governance/risk-register.md` (update existing entry) | Usually yes if mitigation gap remains |
| `MEDIUM BUG` | Real technical issue below governance-risk threshold | `docs/00-planning/core-evolution-plan.md` (or immediate task if prioritized) | Optional; required only when scheduled |
| `FALSE POSITIVE` | Not a real issue in current context/contract | Normally no repository change | No |
| `NEEDS CONTEXT` | Insufficient evidence or environment context to classify safely | Manual review note (then route after validation) | Blocked until context is resolved |

## 6. Storage Rules
- Do not create a new “radar backlog” file when existing governance files already cover the destination.
- Findings must be stored in the correct existing file by classification.
- False positives normally require no repository change.
- Medium bugs go to `core-evolution-plan.md` unless immediately promoted into a governed task.
- If a finding introduces a new standing operational rule, update `decision-log.md`; if architecture authority changes, escalate to ADR.

## 7. Real Examples From Blackwork
- SVG upload without sanitization  
  Route: new risk in risk register (`R-SEC-22`) -> governed implementation task -> closure artifact.
- Import bulk without durable state/checkpoint authority  
  Route: existing import risk update (`R-IMP-10`) -> Tier-0 hardening task.
- FPW transient key collision risk  
  Route: medium bug -> governed task execution -> closure evidence.
- Duplicate risk ID `R-MF-01`  
  Route: governance/docs cleanup (traceability fix), not runtime hardening.
- Supabase bridge globally loaded on anonymous pages  
  Route: medium bug -> `core-evolution-plan.md` backlog item.
- Dual authority `_v1/_v2` rules in Theme Builder Lite  
  Route: new governance risk (`R-TBL-18`) in risk register.

## 8. Relationship With AI Task Protocol
- `docs/00-governance/ai-task-protocol.md` governs **how tasks are executed** (start, gate, implementation, closure, release gate).
- This workflow governs **how findings are classified and routed before becoming tasks**.
- They are complementary:
  - Radar workflow decides *what* and *where*.
  - AI task protocol decides *how* to execute once a task exists.

## 9. Operational Rule For New Chats
At the start of any new chat handling audit/radar findings, this document should be provided (or explicitly referenced) so the assistant routes findings into the correct existing governance containers from the first pass.

## 10. Quick Checklist
- Is this a real risk?
- Is it already covered by an existing risk?
- Is it a technical bug instead of a governance risk?
- Does it require a standing-rule decision (`decision-log`) or ADR?
- Does it need a task now, next, or only backlog placement?


---

## Source: `docs/00-governance/risk-notes-theme-builder-lite.md`

# Theme Builder Lite - Risk Notes (Foundation)

## 1) Purpose
This document tracks governance-level risks for Theme Builder Lite MVP foundation.
It is risk-only (no roadmap planning content).

## 2) Risk Entries

### Risk ID: R-TBL-01
- Domain: Global Layout / Footer Runtime
- Surface Anchor: `wp_footer`, `wp`, `wp_head` footer suppression path
- Description: Custom footer render may coexist with active theme footer, causing duplicate footer output and UX inconsistency.
- Invariant Threatened: Single active presentation owner for footer surface per request.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation:
  - Render custom footer only when resolver returns valid template.
  - Remove known theme footer callback only when custom footer is active.
  - Apply fallback CSS only as scoped last resort.
- Monitoring Status: Open

### Risk ID: R-TBL-02
- Domain: Product UX / Woo Runtime
- Surface Anchor: `template_redirect`, Woo single-product hook replacement path
- Description: Request-local removal of Woo default callbacks may conflict with third-party product hooks, causing partial render or duplicate sections.
- Invariant Threatened: Deterministic single-product rendering and non-breaking fallback.
- Impact: High
- Likelihood: Medium
- Risk Level: High
- Current Mitigation:
  - Perform hook replacement only when an eligible template resolves.
  - Keep module feature-flagged with immediate disable rollback.
  - Keep fallback path to native Woo rendering.
- Monitoring Status: Open

### Risk ID: R-TBL-03
- Domain: Condition Engine
- Surface Anchor: Template resolver ordering logic
- Description: Equal-priority template collisions can produce non-deterministic template selection if tie-break order is incomplete.
- Invariant Threatened: Same input/state must yield same template.
- Impact: High
- Likelihood: Medium
- Risk Level: High
- Current Mitigation:
  - Mandatory deterministic tie-break chain (priority, specificity, modified_gmt, id).
  - Normalized and versioned condition schema.
- Monitoring Status: Open

### Risk ID: R-TBL-04
- Domain: Elementor Integration
- Surface Anchor: `elementor/cpt_support` and `bw_template` editor usage
- Description: Elementor Free/editor context mismatch may lead to templates editable but not safely renderable in frontend context.
- Invariant Threatened: Editor/runtime convergence for template content.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation:
  - Restrict runtime rendering to validated published templates.
  - Use centralized renderer wrappers with fail-open behavior.
- Monitoring Status: Monitoring

### Risk ID: R-TBL-05
- Domain: Custom Fonts
- Surface Anchor: Generated `@font-face` CSS and asset enqueue
- Description: Invalid font metadata or unsupported sources can generate broken CSS and visual regressions.
- Invariant Threatened: Non-breaking global presentation and deterministic font loading.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation:
  - Validate mime/type and source before CSS generation.
  - Skip invalid font entries without blocking page render.
- Monitoring Status: Monitoring

### Risk ID: R-TBL-06
- Domain: Governance / Authority Boundaries
- Surface Anchor: Future extension pressure toward full template stack takeover
- Description: Scope creep can push the module into global template authority (`template_include` full control), exceeding MVP governance boundary.
- Invariant Threatened: Presentation layer must remain non-authoritative.
- Impact: High
- Likelihood: Medium
- Risk Level: High
- Current Mitigation:
  - Explicit non-goal: no global template stack override in MVP.
  - Require escalation if authority ownership changes.
- Monitoring Status: Open

## 3) Governance Rules for This Module
- Theme Builder Lite remains Tier 1/2 scoped in MVP.
- Any shift toward template-authority ownership requires governance re-evaluation.
- Any Tier 0 hook priority mutation remains forbidden.
- Resolver determinism is mandatory and test-gated.

## 4) Cross-References
- `docs/30-features/theme-builder-lite/theme-builder-lite-spec.md`
- `docs/10-architecture/theme-builder-lite/runtime-hook-map.md`
- `docs/00-governance/authority-matrix.md`
- `docs/50-ops/runtime-hook-map.md`
- `docs/00-overview/domain-map.md`


---

## Source: `docs/00-governance/risk-register.md`

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
- Description: Elementor internal API/filter behavior may drift across releases, potentially degrading custom font group/list injection without hard failures, including silent no-op behavior when internal font contracts change.
- Invariant Threatened: Deterministic visibility of configured custom families in Elementor Typography controls.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation: Soft dependency + defer bootstrap pattern + idempotent registration guard + fail-open behavior when Elementor is absent or filters are unavailable; runtime monitoring for internal API drift keeps failures non-fatal.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Theme Builder Lite Spec](../30-features/theme-builder-lite/theme-builder-lite-spec.md)
  - [Theme Builder Lite Runtime Hook Map](../10-architecture/theme-builder-lite/runtime-hook-map.md)

### Risk ID: R-TBL-05
- Domain: Theme Builder Lite / Template Resolver
- Surface Anchor: `includes/modules/theme-builder-lite/runtime/template-resolver.php` (`template_include` priority `50`)
- Description: Resolver conflicts with theme or third-party `template_include` logic could produce unexpected template precedence behavior, including Woo single-product/product-archive selection drift and child-theme override displacement.
- Invariant Threatened: Fail-open template selection must never break native theme rendering.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation: strict bypass guards (admin/editor/preview/Woo safety endpoints), explicit bypass for Woo archive contexts, deterministic winner contract, fail-open fallback to original `$template` on mismatch/error/empty render, and precedence monitoring for filter-order bypass scenarios.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Theme Builder Lite Spec](../30-features/theme-builder-lite/theme-builder-lite-spec.md)
  - [Theme Builder Lite Runtime Hook Map](../10-architecture/theme-builder-lite/runtime-hook-map.md)

### Risk ID: R-TBL-06
- Domain: Theme Builder Lite / Configuration Surfaces
- Surface Anchor: `bw_theme_builder_lite_single_product_rules_v2` (authority) with legacy `bw_theme_builder_lite_single_product_v1` and `bw_tbl_display_rules_v1` retained
- Description: During compatibility period, legacy single-product data remains present while settings repeater v2 is authoritative; operator confusion can occur if precedence is misunderstood.
- Invariant Threatened: Deterministic and explainable single-product template selection.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation: explicit precedence contract (v2 authoritative; v1 fallback only if v2 absent), Quick Edit conditions removed, fail-open fallback, migration deferred to dedicated cleanup task.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Theme Builder Lite Spec](../30-features/theme-builder-lite/theme-builder-lite-spec.md)

### Risk ID: R-TBL-07
- Domain: Theme Builder Lite / Rule Determinism
- Surface Anchor: Single-product repeater evaluation order in settings resolver path
- Description: Operators may misinterpret precedence when multiple rules overlap, expecting “most specific” instead of first-match list order.
- Invariant Threatened: Deterministic, explainable template selection.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation: explicit contract in docs/UI (top-to-bottom, exclude-first, include-empty=match-all, first-match wins), status summary, fail-open fallback.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Theme Builder Lite Spec](../30-features/theme-builder-lite/theme-builder-lite-spec.md)

### Risk ID: R-TBL-08
- Domain: Theme Builder Lite / Admin List UX Integrity
- Surface Anchor: Inline type dropdown (`wp_ajax_bw_tbl_update_template_type`) and linkage badges
- Description: Inline type mutation can invalidate existing settings linkages and leave templates unlinked if changed unintentionally.
- Invariant Threatened: Linkage visibility and safe mutation behavior.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation: linked-template confirmation prompt, nonce/capability/enum validation, safe rejection path, explicit `Not linked` badge after mutation.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Theme Builder Lite Spec](../30-features/theme-builder-lite/theme-builder-lite-spec.md)

### Risk ID: R-TBL-09
- Domain: Theme Builder Lite / Admin UX Scaling
- Surface Anchor: Single-product repeater settings tab with rule cards/checklists
- Description: Large rule sets may reduce operator usability/performance and increase configuration error probability.
- Invariant Threatened: Maintainable and reliable admin configuration experience.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation: parent-only flat checklist, explicit include/exclude toggles, clearer spacing/status summary; backlog for reorder/search/diagnostics enhancements.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Theme Builder Lite Spec](../30-features/theme-builder-lite/theme-builder-lite-spec.md)

### Risk ID: R-TBL-10
- Domain: Theme Builder Lite / Product Archive Rules
- Surface Anchor: `bw_theme_builder_lite_product_archive_rules_v2` rule ordering and include/exclude controls
- Description: Operators may misread precedence across archive rules and expect specificity scoring instead of saved-order first-match evaluation.
- Invariant Threatened: Deterministic, explainable resolver behavior on product category archives.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation: Explicit contract in UI/docs (`exclude` before `include`, include-empty=match-all, top-to-bottom first-match wins), status summary, fail-open fallback.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Theme Builder Lite Spec](../30-features/theme-builder-lite/theme-builder-lite-spec.md)

### Risk ID: R-TBL-11
- Domain: Theme Builder Lite / Admin UX Scaling
- Surface Anchor: Product Archive repeater with parent-category checklist per rule
- Description: Large numbers of rules and terms can reduce admin usability and increase configuration mistakes.
- Invariant Threatened: Reliable operator configuration and deterministic rule intent.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation: Parent-only checklist (no subcategories), optional exclusion toggle, concise status summary, deterministic sanitize on save.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Theme Builder Lite Spec](../30-features/theme-builder-lite/theme-builder-lite-spec.md)

### Risk ID: R-TBL-12
- Domain: Theme Builder Lite / Template Type Integrity
- Surface Anchor: Product Archive rule sanitize/validation (`template_id` must be `publish` + `bw_template_type=product_archive`)
- Description: Type drift after manual type changes can silently invalidate rule links unless strict validation is enforced.
- Invariant Threatened: Valid template linkage for archive runtime selection.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation: Sanitizer drops invalid template IDs, runtime revalidates before match, list badges surface `Not linked` when linkage is broken.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Theme Builder Lite Spec](../30-features/theme-builder-lite/theme-builder-lite-spec.md)

### Risk ID: R-TBL-13
- Domain: Theme Builder Lite / Resolver Branching
- Surface Anchor: `template_include` branch for product category archives with ancestor-aware matching
- Description: Archive resolver regressions could alter Woo product-category page rendering precedence when plugins/themes also filter `template_include`.
- Invariant Threatened: Fail-open and non-breaking Woo archive rendering.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation: Existing bypass guards preserved, branch scoped to `product_cat` context, deterministic first-match, fail-open to original theme template on no-match/invalid state.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Theme Builder Lite Spec](../30-features/theme-builder-lite/theme-builder-lite-spec.md)
  - [Theme Builder Lite Runtime Hook Map](../10-architecture/theme-builder-lite/runtime-hook-map.md)

### Risk ID: R-TBL-14
- Domain: Theme Builder Lite / Import Template
- Surface Anchor: `Import Template` tab JSON upload handler (`admin_post_bw_tbl_import_template`)
- Description: Importing malformed or untrusted Elementor JSON can trigger invalid template creation attempts or oversized payload stress if validation boundaries drift.
- Invariant Threatened: Deterministic and safe admin import with no partial writes and no unauthorized content mutation.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation: strict capability + nonce checks, `.json` extension/type validation, size cap, JSON decode validation, required content structure checks, allowed-type mapping validation, rollback via hard delete on metadata write failure, and compatibility monitoring for future Elementor schema drift.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Theme Builder Lite Spec](../30-features/theme-builder-lite/theme-builder-lite-spec.md)

### Risk ID: R-TBL-15
- Domain: Theme Builder Lite / Import Template / Elementor Compatibility
- Surface Anchor: Imported `_elementor_*` metadata and mirror `elementor_library` records
- Description: Elementor schema/version drift can make some imported templates editable with partial degradation or widget-level incompatibilities.
- Invariant Threatened: Deterministic editability and predictable imported-template behavior in Elementor editor/library flows.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation: importer persists raw Elementor payload without destructive transformation, sets required baseline meta, keeps import as draft for manual validation before linking, and requires periodic validation against Elementor schema/version drift.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Theme Builder Lite Spec](../30-features/theme-builder-lite/theme-builder-lite-spec.md)
  - [Decision Log](../00-planning/decision-log.md)

### Risk ID: R-TBL-16
- Domain: Theme Builder Lite / Import Template / Admin Integrity
- Surface Anchor: Auto-detect type mapping with `single_page` fallback for unmappable payloads
- Description: Operators may assume fallback type reflects semantic intent; incorrect linkage can occur if fallback imports are not reviewed before activation.
- Invariant Threatened: Correct template-type authority and predictable routing intent.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation: explicit fallback success notice, imported-title prefix (`Imported —`), `bw_tbl_imported` marker for audit/filtering, draft-default status to force review before linkage, and post-import review expectation when schema semantics are uncertain.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Theme Builder Lite Spec](../30-features/theme-builder-lite/theme-builder-lite-spec.md)
  - [Decision Log](../00-planning/decision-log.md)

### Risk ID: R-TBL-18
- Domain: Theme Builder Lite / Rule Authority
- Surface Anchor: Rule option surfaces `bw_tbl_display_rules_v1` and `bw_theme_builder_lite_single_product_rules_v2`
- Description: Coexisting legacy `_v1` and active `_v2` rule stores can create dual-authority ambiguity, deterministic drift in operator expectations, and audit confusion if read/write boundaries are unclear.
- Invariant Threatened: Single authoritative truth surface for template-rule resolution and explainable operator behavior.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation: `_v2` remains runtime authority with bounded legacy fallback behavior; governance follow-up requires explicit legacy deprecation/migration and docs alignment to prevent dual-surface confusion.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Theme Builder Lite Spec](../30-features/theme-builder-lite/theme-builder-lite-spec.md)
  - [Core Evolution Plan](../00-planning/core-evolution-plan.md)

### Risk ID: R-CHK-01
- Domain: Checkout / Payments
- Surface Anchor: `assets/js/bw-payment-methods.js`, `assets/js/bw-google-pay.js`, `assets/js/bw-apple-pay.js` (`updated_checkout` handlers)
- Description: Multiple checkout refresh listeners can race and temporarily desynchronize selected method, wallet visibility, and button state.
- Invariant Threatened: Selected gateway UI/radio/submit determinism.
- Impact: High
- Likelihood: Medium
- Risk Level: High
- Current Mitigation:
  - Central selector convergence routine in `bw-payment-methods.js` (`bwConvergeCheckoutSelectorState`) acts as single authority for final UI/radio/submit state.
  - Deterministic fallback selects remembered valid gateway or first enabled gateway when selected method is missing/disabled after fragment refresh.
  - Idempotent rebind protections: bootstrapping guard, namespaced/off-on jQuery handlers, and deduped tooltip/wallet DOM hooks.
  - Wallet/UPE interference containment: unavailable wallet selection is reconciled to valid non-wallet gateway with synchronized action-button visibility.
  - Shared scheduled reconciliation on `updated_checkout`, `payment_method_selected`, and `checkout_error`.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Blast-Radius Consolidation Map](./blast-radius-consolidation-map.md)
  - [Checkout Payment Selector Audit](../50-ops/audits/checkout-payment-selector-audit.md)
  - [BW-TASK-20260306-05 Closure](../tasks/BW-TASK-20260306-05-closure.md)

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

### Risk ID: R-MF-01
- Domain: Media Folders / Admin Runtime / Counts Pipeline
- Surface Anchor: `includes/modules/media-folders/runtime/ajax.php` (`bw_mf_get_folder_counts_map_batched`, tree/summary cache paths)
- Risk type: Performance scalability
- Description: On very large datasets (e.g. 50k+ media items, 10k+ posts/products, deep folder hierarchies), even batched aggregation queries for folder counts can become expensive depending on hosting and DB performance characteristics.
- Invariant Threatened: Responsive admin folder tree/counts under high-volume installations.
- Impact: Medium-High
- Likelihood: Low (current scale)
- Risk Level: Medium
- Current Mitigation: Batched relationship aggregation + taxonomy/post_type scoped transient/object-cache with deterministic invalidation, with large-dataset watchpoint for heavy batch-query degradation.
- Planned Mitigation Strategy: Introduce materialized folder counts (incremental updates on assignment/mutation events) to avoid repeated relationship recomputation at very large scale.
- Monitoring Status: Planning / Watchlist
- Linked Documents:
  - [Media Folders Module Spec](../30-features/media-folders/media-folders-module-spec.md)
  - [Decision Log](../00-planning/decision-log.md)
  - [Blast-Radius Consolidation Map](./blast-radius-consolidation-map.md)
  - [Checkout Payment Selector Audit](../50-ops/audits/checkout-payment-selector-audit.md)

### Risk ID: R-MF-03
- Domain: Media Folders / Media Library Admin
- Surface Anchor: `includes/modules/media-folders/admin/assets/media-folders.js` (toolbar/list-table placement selectors + observers + DnD interaction layer)
- Description: WordPress admin list-table/media DOM structure (`.media-toolbar`, `.tablenav.top`, `#the-list`, row classes) can change across releases, causing placement drift or degraded JS behavior on Media/Posts/Pages/Products list surfaces.
- Invariant Threatened: Admin-only fail-open behavior with non-breaking Media Library default operations.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation: strict `upload.php` scoping, idempotent DOM insertion, coalesced refresh scheduler, selector fallback chain, and no-op/fail-open guards when targets are missing.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Media Folders Spec](../30-features/media-folders/media-folders-module-spec.md)
  - [Media Folders Task Closure](../tasks/media-folders-close-task.md)

### Risk ID: R-MF-02
- Domain: Media Folders / Data Integrity
- Surface Anchor: `includes/modules/media-folders/runtime/ajax.php` (`bw_media_assign_folder`, folder CRUD/meta endpoints, counts/markers endpoints)
- Description: High-volume media assignment or conflicting plugin taxonomy operations may create temporary count/marker mismatches or perceived state lag in admin lists/grids.
- Invariant Threatened: Deterministic media-folder assignment visibility in current admin session.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation: attachment/object ID normalization + batch limit (200), capability/nonce/context validation, deterministic post_type->taxonomy isolation (`bw_media_folder`/`bw_post_folder`/`bw_page_folder`/`bw_product_folder`), server-side counts API, marker cache invalidation on assignment, single-flight marker fetch queue, runtime query-isolation guards in `runtime/media-query-filter.php` (signature-safe `ajax_query_attachments_args`, attachment-only context gate, taxonomy validation, deterministic `tax_query` merge, fail-open bypass on invalid payload/context), and monitoring for very-large-library batch-query pressure.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Media Folders Spec](../30-features/media-folders/media-folders-module-spec.md)
  - [Media Folders Task Closure](../tasks/media-folders-close-task.md)
  - [BW-TASK-20260306-07 Closure](../tasks/BW-TASK-20260306-07-closure.md)
  - [Technical Hardening Plan](./technical-hardening-plan.md)

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
- Current Mitigation: Pending-email clear on confirmed email match; security tab forced on callback; URL param cleanup; convergence checks across callback/onboarding re-entry to reduce stale-banner persistence windows.
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
- Current Mitigation: Signature-first authenticity gate, event-id claim ledger (`_bw_evt_claim_*`) with completed-state dedupe, deterministic unknown/duplicate/out-of-order no-op behavior, monotonic transition guard, and return-flow-safe convergence checks before order mutation.
- Monitoring Status: Monitoring
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
- Current Mitigation: Hard consent gate checks, paid-hook gating, local meta audit trail, non-blocking behavior, and timing-aware monitoring to detect anomalous hook-order paths.
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
- Likelihood: Low-Medium
- Risk Level: High
- Current Mitigation:
  - Run-level lock in `wp_options` (`bw_import_run_lock`) enforces single authoritative import mutator with stale-lock reclaim and deterministic operator-safe abort on active contention.
  - Durable run-state model in `wp_options` (`bw_import_run_{run_id}` + `bw_import_active_run`) is now authoritative for resume/checkpoint state; user transient remains UI mirror only.
  - Deterministic row outcome ledger (`created|updated|skipped|failed`) with bounded retention and non-double-count convergence by row identity.
  - SKU identity hardening in save path: ID-first + SKU lookup, pre-create re-resolve, and deterministic SKU-conflict retry-to-update gate.
  - Publish status allowlist enforcement (`draft|publish|pending|private`) with deterministic invalid-value normalization to `draft` + warning trace.
- Monitoring Status: Monitoring
- Linked Documents:
  - [ADR-008 Import Engine Authority Hardening](../60-adr/ADR-008-import-engine-authority-hardening.md)
  - [Import Products vNext Spec](../30-features/import-products/import-products-vnext-spec.md)
  - [Core Evolution Plan](../00-planning/core-evolution-plan.md)
  - [System Normative Charter](./system-normative-charter.md)
  - [BW-TASK-20260306-13 Closure](../tasks/BW-TASK-20260306-13-closure.md)

### Risk ID: R-SRCH-11
- Domain: Search / Header Runtime Coupling
- Surface Anchor: `docs/50-ops/audits/search-system-technical-audit.md`, `includes/modules/header/frontend/ajax-search.php`, search runtime contracts
- Description: Search requests are still request-bound and uncached in current runtime, with partial filter wiring risk between UI/CSS expectations and effective query constraints.
- Invariant Threatened: Deterministic read-only discovery behavior and non-blocking navigation under load/failure.
- Impact: High
- Likelihood: Medium
- Risk Level: High
- Current Mitigation:
  - Request normalization guard (`search_term` sanitize+trim, min length gate, category/product_type normalization).
  - Safe-empty response contract for malformed/invalid/too-short requests with stable JSON schema.
  - Bounded query constraints (`posts_per_page=10`, `no_found_rows`, `ignore_sticky_posts`, disabled meta/term cache hydration).
  - Deterministic ordering (`orderby=title`, `order=ASC`) and stable response shaping (`products` + `results` aliases).
  - Publish-only + WooCommerce visibility exclusion via `product_visibility` NOT IN (`exclude-from-search`, `exclude-from-catalog`).
- Monitoring Status: Monitoring
- Linked Documents:
  - [Search System Technical Audit](../50-ops/audits/search-system-technical-audit.md)
  - [Search Module Spec](../30-features/search/search-module-spec.md)
  - [Search vNext Spec](../30-features/search/search-vnext-spec.md)
  - [BW-TASK-20260306-09 Closure](../tasks/BW-TASK-20260306-09-closure.md)

### Risk ID: R-RED-12
- Domain: Redirect / Routing Authority
- Surface Anchor: `includes/class-bw-redirects.php`, `template_redirect` precedence, legacy redirect storage/validation path
- Description: Current redirect runtime can still experience chain/precedence instability before full v2 policy enforcement (protected-route gate, deterministic precedence, multi-hop loop validation).
- Invariant Threatened: Protected commerce routes and deterministic routing authority convergence.
- Impact: Critical
- Likelihood: Medium
- Risk Level: Critical
- Current Mitigation:
  - Single canonical normalization path (`bw_normalize_redirect_path`) enforced across save-time validation, runtime matching, protected-route checks, loop checks, and target-path validation.
  - Protected-route policy enforced in save-time and runtime gates (`/wp-admin`, `/wp-login.php`, `/wp-json`, `/cart`, `/checkout`, `/my-account`, `/wc-api` families).
  - Redirect runtime priority hardened to `template_redirect@10` to align deterministic precedence with canonical handling and downstream route ownership.
  - Save-time rule safety filter (`pre_update_option_bw_redirects`) rejects self-loop and direct two-node loop patterns.
  - Runtime fail-open controls include direct reverse-loop guard and hop-limit guard (`max_redirect_hops=5`) with chain-abort on unsafe paths.
  - Deterministic first-match source indexing per request avoids duplicate source ambiguity and unnecessary repeated scans.
- Monitoring Status: Monitoring
- Linked Documents:
  - [Redirect Engine Technical Audit](../50-ops/audits/redirect-engine-technical-audit.md)
  - [Redirect Engine v2 Spec](../30-features/redirect/redirect-engine-v2-spec.md)
  - [ADR-007 Redirect Authority Hardening](../60-adr/ADR-007-redirect-authority-hardening.md)
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

### Risk ID: R-TBL-17
- Domain: Theme Builder Lite / Elementor Preview / BW Widgets
- Surface Anchor: `includes/modules/theme-builder-lite/runtime/elementor-preview-context.php`, `includes/modules/theme-builder-lite/runtime/single-product-runtime.php`, BW widget render paths using product context.
- Description: Preview context convergence depends on bridge values being set and observable in the same request scope; audits can fail if server-side logs are not accessible from execution environment.
- Invariant Threatened: Deterministic preview product resolution for bw_template(single_product) without WP_Query mutation.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation:
  - Bridge set on `wp@20` (`$GLOBALS['bw_tbl_preview_product_id']` + `set_query_var('bw_tbl_preview_product_id', ...)`)
  - Shared resolver precedence (`real_context -> preview_fallback -> manual_setting -> missing`)
  - Debug flag `BW_TBL_DEBUG_PREVIEW` with bridge/resolver logs only
  - Explicitly disable warning output in HTML (`WP_DEBUG_DISPLAY=false`, `display_errors=0`)
- Monitoring Status: Monitoring
- Linked Documents:
  - [Theme Builder Lite Spec](../30-features/theme-builder-lite/theme-builder-lite-spec.md)
  - [Theme Builder Lite Runtime Hook Map](../10-architecture/theme-builder-lite/runtime-hook-map.md)
  - [Decision Log](../00-planning/decision-log.md)

### Risk ID: R-ADM-18
- Domain: Admin / System Status Diagnostics
- Surface Anchor: `includes/modules/system-status/runtime/check-runner.php` and check modules under `includes/modules/system-status/runtime/checks/`
- Description: On-demand diagnostics can become heavy on large datasets or expose privileged infrastructure metadata if endpoint protections drift.
- Invariant Threatened: Admin diagnostics must remain read-only, capability-gated, nonce-protected, and non-blocking for normal admin navigation.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation:
  - Capability gate: `manage_options`
  - Nonce verification on `bw_system_status_run_check`
  - Read-only check contract (no write/delete/update paths)
  - On-demand execution only (no heavy operations on normal page load)
  - Transient snapshot caching with bounded scans and partial warning behavior
  - Structured graceful-failure responses to avoid dashboard-wide breakage
  - Capability/nonce gates remain required first-check controls before any diagnostic payload assembly.
- Monitoring Status: Monitoring
- Linked Documents:
  - [System Status (Admin Diagnostics)](../30-features/system-status/README.md)
  - [System Normative Charter](./system-normative-charter.md)

### Risk ID: R-ADM-19
- Domain: Admin / Asset Enqueue Scope / Panel Performance
- Surface Anchor: `admin/class-blackwork-site-settings.php` (`bw_site_settings_admin_assets`) and Blackwork admin screen guard (`bw_is_blackwork_site_admin_screen`)
- Description: Broad asset enqueue strategy can load non-required scripts/styles (media uploader, color picker, gateway test scripts) on unrelated Blackwork admin pages, degrading responsiveness and increasing maintenance risk.
- Invariant Threatened: Admin pages should load only the assets required for their own behavior, with no cross-page performance drag.
- Impact: Medium
- Likelihood: Medium
- Risk Level: Medium
- Current Mitigation:
  - Shared UI kit is centrally guarded and scoped to Blackwork screens.
  - Additional refactor program planned to split/enforce page/tab-specific enqueue matrix.
  - Explicit watchpoint for tab/page-scoped asset matrix enforcement in `bw_site_settings_admin_assets`.
- Monitoring Status: Open
- Linked Documents:
  - [Admin Panel Map](../20-development/admin-panel-map.md)
  - [Admin UI Guidelines](../20-development/admin-ui-guidelines.md)
  - [BW-TASK-20260305-08 Admin Audit](../tasks/BW-TASK-20260305-08-admin-panel-architecture-audit.md)

### Risk ID: R-FPW-20
- Domain: Product Grid / Public AJAX Runtime (formerly Filtered Post Wall)
- Surface Anchor: `blackwork-core-plugin.php` (`bw_fpw_get_subcategories`, `bw_fpw_get_tags`, `bw_fpw_filter_posts`, FPW tag aggregation helpers)
- Description: Public FPW AJAX endpoints can degrade under catalog growth or automation pressure if request normalization, post-type boundaries, query caps, and abuse guards drift from hardened defaults.
- Invariant Threatened: Deterministic, non-blocking FPW filtering with bounded read-path cost and intended public visibility only.
- Impact: High
- Likelihood: Medium
- Risk Level: High
- Current Mitigation:
  - Strict request normalization (`post_type` allowlist, bounded term arrays, deterministic sort/order defaults).
  - Bounded query contract (no unbounded `posts_per_page=-1` paths, capped `per_page`, capped tag-source post scan).
  - Additional watchpoint: `bw_fpw_get_related_tags_data()` uses `get_terms()` without `number` cap when `category='all'`; public AJAX cache/throttle reduce repeated pressure, but first-hit payload can still be large on high-tag catalogs.
  - Batched tag aggregation (`wp_get_object_terms` over bounded ID set) to avoid per-post N+1 loops.
  - Transient-based nopriv throttle guard with fail-soft compatible responses.
  - Deterministic hashed FPW cache keys (`bw_fpw_{sha256}` from canonical normalized payload) replacing truncation-based key construction, eliminating prefix-collision risk.
  - Publish-only query constraints preserved.
- Monitoring Status: Monitoring
- Linked Documents:
  - [BW-TASK-20260306-03 Closure](../tasks/BW-TASK-20260306-03-closure.md)
  - [Elementor Widget Architecture Context](../10-architecture/elementor-widget-architecture-context.md)
  - [Docs-Code Alignment Status](./docs-code-alignment-status.md)

Additional Evidence (Radar Verification – FPW Tags Query)

Radar analysis identified an additional unbounded term query path in
`bw_fpw_get_related_tags_data()` when `category='all'`.

Surface:
blackwork-core-plugin.php (approx lines 1683–1710)

Behavior:
The function calls:

get_terms([
  'taxonomy' => ...,
  'hide_empty' => true
])

without a `number` limit, potentially returning all non-empty tags
in large catalogs.

Context:
This path is reachable via the public AJAX endpoint
`bw_fpw_get_tags()` and also during `bw_fpw_filter_posts()` response assembly.

Existing mitigations already reduce repeated load:

- transient caching (5 minutes)
- throttle guard
- normalized query constraints

However the first-hit query size may still be large on installations
with high tag cardinality.

Mitigation follow-up:
Evaluate adding a bounded `number` limit or alternative deterministic
term sampling strategy for the `category='all'` branch.

### Risk ID: R-ADM-21
- Domain: Admin / Settings Input Integrity
- Surface Anchor: `admin/class-blackwork-site-settings.php`, `includes/admin/checkout-fields/class-bw-checkout-fields-admin.php`, `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`, `includes/modules/media-folders/admin/media-folders-settings.php`, Theme Builder Lite admin settings/AJAX handlers
- Description: Admin settings write paths can introduce unsafe persistence if capability checks, nonce verification, input sanitization, or option-schema constraints drift across modules.
- Invariant Threatened: Admin option writes must be capability-gated, nonce-protected, sanitized, schema-safe, and deterministic.
- Impact: High
- Likelihood: Medium
- Risk Level: High
- Current Mitigation:
  - Guard-first save handlers with explicit capability checks before POST payload processing in Blackwork Site Settings tabs.
  - Nonce verification on state-changing POST/AJAX handlers (`check_admin_referer` / `check_ajax_referer`) before persistence.
  - Type-specific input sanitization (`sanitize_text_field`, `sanitize_key`, `absint`, `sanitize_email`, `esc_url_raw`, `wp_kses_post`).
  - Schema-safe array normalization for policy option payloads (array validation + allowlist key construction) before `update_option`.
  - Fail-safe behavior: invalid capability/nonce/context paths abort without option writes.
- Monitoring Status: Monitoring
- Linked Documents:
  - [BW-TASK-20260306-08 Closure](../tasks/BW-TASK-20260306-08-closure.md)
  - [Admin Panel Map](../20-development/admin-panel-map.md)
  - [Technical Hardening Plan](./technical-hardening-plan.md)

### Risk ID: R-SEC-22
- Domain: Media Upload / SVG Sanitization
- Surface Anchor: `blackwork-core-plugin.php` (`upload_mimes`, `wp_handle_upload_prefilter`, `wp_check_filetype_and_ext`)
- Description: SVG uploads can become a stored-XSS vector if executable markup survives upload validation or sanitizer hardening drifts over time.
- Invariant Threatened: No executable SVG payload may be persisted through media upload flow.
- Impact: Critical
- Likelihood: Medium
- Risk Level: High
- Current Mitigation:
  - Capability-gated SVG allowance (`manage_options`) with no broadened role scope.
  - Fail-closed SVG upload prefilter (`wp_handle_upload_prefilter`) validates/sanitizes SVG before attachment creation.
  - Deterministic reject path for malformed/unsafe SVG and explicit reject policy for `svgz`.
  - Strict SVG filetype normalization path limited to validated real SVG content.
- Monitoring Status: Monitoring
- Linked Documents:
  - [BW-TASK-20260306-10 Closure](../tasks/BW-TASK-20260306-10-closure.md)
  - [Technical Hardening Plan](./technical-hardening-plan.md)

### Risk ID: R-FE-23

Domain:
Frontend / UI Dependencies

Surface Anchor:
blackwork-core-plugin.php (`bw_enqueue_slick_slider_assets`)
CDN dependency `slick-carousel@1.8.1`

Description:
The plugin relies heavily on Slick Carousel 1.8.1 loaded from a CDN.
The library is effectively frozen upstream and is no longer actively
maintained.

Multiple storefront widgets and scripts depend on Slick runtime
behavior and CSS contracts.

Invariant Threatened:
Long-term maintainability and security posture of frontend UI
dependencies.

Impact:
Medium

Likelihood:
Medium

Risk Level:
Medium

Current Mitigation:
Pinned CDN version ensures deterministic runtime behavior.

Monitoring Status:
Monitoring

Notes:
Due to extensive coupling across widgets and JS runtime,
replacement or migration would require a controlled frontend
refactor effort.

### Risk ID: R-WOO-24

Domain:
WooCommerce Integration / Template Overrides

Surface Anchor:
woocommerce/templates/

Description:
The plugin maintains a large set of WooCommerce template overrides
(27 files). Several overrides are version-stale relative to the
current WooCommerce core templates.

Examples identified:

checkout/payment.php
plugin version: 8.1.0
core version: 9.8.0

cart/cart.php
plugin version: 7.9.0
core version: 10.1.0

single-product/related.php
plugin version: 3.9.0
core version: 10.3.0

myaccount/navigation.php
plugin version: 2.6.0
core version: 9.3.0

Invariant Threatened:
Template overrides must remain compatible with WooCommerce core
template contracts.

Impact:
High

Likelihood:
Medium

Risk Level:
High

Current Mitigation:
None systematic.

Monitoring Status:
Open

Recommended Mitigation:
Perform a systematic template override audit and align overrides
with current WooCommerce template versions.

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


---

## Source: `docs/00-governance/system-normative-charter.md`

# System Normative Charter

## 1) Purpose
This charter defines the binding cross-system invariants that protect business integrity, commerce continuity, and data authority across Supabase, Payments, Checkout, and Brevo integrations.
It is the top-level architectural contract for system behavior under normal and failure conditions.

## 2) Authority Hierarchy (Source-of-Truth Model)
Authority order is explicit and non-interchangeable:

- Payment authority: payment provider confirmation and platform payment status.
- Order authority: WooCommerce order state and lifecycle.
- Auth authority: WordPress session state.
- Consent authority: local consent metadata.
- External providers (Supabase remote services, Brevo remote services, Stripe remote services): downstream execution systems.

External systems may execute and report outcomes, but they do not define final local business truth.

## 3) Non-Blocking Commerce Principle
- No integration may block checkout submission, payment completion, or order creation.
- Marketing sync, onboarding/provisioning, and external synchronization failures must degrade safely.
- Commerce continuity has precedence over non-core auxiliary workflows.

## 4) Identity & State Separation Principle
- Auth state, payment state, consent state, and provisioning state are distinct domains.
- One state domain must not overwrite another domain’s authority.
- Cross-domain reads are permitted; cross-domain authority takeover is prohibited.

## 5) Idempotency & Convergence Principle
- Repeated triggers, retries, and callbacks must converge toward a single stable local outcome.
- The system must prevent duplicate ownership assignment, duplicate provisioning effects, and duplicate marketing-contact intent.
- Retry safety is mandatory for all external write paths.

## 6) Callback & External Event Discipline
All callback/webhook/external-event flows must:
- validate authenticity before mutation,
- enforce idempotency before state transition,
- avoid unstable first-render states,
- avoid redirect loops and repeated terminal routing.

Callback correctness is both a security and integrity requirement.

## 7) Local Authority Doctrine
- The local system owns policy truth and final business state.
- External integrations are execution engines, not policy engines.
- Remote outcomes are accepted only through controlled local reconciliation paths.

## 8) Observability & Audit Principle
- Every external write action must produce local, traceable audit evidence.
- Local state must reflect outcome class (success/skip/error/pending-equivalent).
- Silent remote mutations without local audit reflection are not compliant.

## 9) Blast-Radius Rule
Any change affecting one or more of the following requires full regression validation:
- payment flow,
- consent gate,
- auth bridge,
- provisioning logic,
- external API client behavior.

High-coupling surfaces are treated as release-sensitive boundaries.

## 10) Evolution Guardrail
Any new integration is valid only if it explicitly declares:
- authority model,
- blocking behavior,
- failure model,
- idempotency guarantees.

No integration can be accepted into system architecture without this declaration.


---

## Source: `docs/00-governance/system-state-matrix.md`

# System State Matrix

## 1) Purpose
This matrix defines cross-domain state combinations across Auth, Payment, Order, Supabase onboarding, Provisioning, and Newsletter consent.

It is used to:
- validate refactor safety against governance invariants
- detect invalid state outcomes during regression
- standardize re-audit checks on cross-domain convergence

## 2) Core State Domains
### Auth State
- Anonymous
- Logged-in
- Logged-in (Supabase mode, not onboarded)
- Logged-in (Supabase onboarded)

### Payment State
- Not started
- Processing
- Paid (confirmed by webhook)
- Failed

### Order State
- Created (pending)
- Processing
- Completed
- Cancelled/Failed

### Supabase Identity State
- Not provisioned
- Invited
- OTP verified
- Password set
- Onboarded

### Provisioning State
- No order linked
- Order linked
- Claimed
- Downloads accessible

### Newsletter Consent State
- No consent
- Consent given (local only)
- Synced to Brevo
- Sync error

## 3) Valid State Combinations
### Valid combinations
| ID | Auth | Payment | Order | Supabase Identity | Provisioning | Newsletter Consent | Classification |
|---|---|---|---|---|---|---|---|
| V-01 | Anonymous | Not started | Created (pending) | Not provisioned | No order linked | No consent | Transitional |
| V-02 | Anonymous | Processing | Processing | Not provisioned or Invited | Order linked | No consent or Consent given (local only) | Transitional |
| V-03 | Anonymous | Paid | Processing/Completed | Invited | Order linked | No consent or Consent given (local only) | Transitional |
| V-04 | Logged-in (Supabase mode, not onboarded) | Paid | Processing/Completed | OTP verified or Password set | Claimed | No consent / local / synced | Transitional |
| V-05 | Logged-in (Supabase onboarded) | Paid | Completed | Onboarded | Downloads accessible | No consent / local / synced / sync error | Stable terminal |
| V-06 | Logged-in | Paid | Completed | Not provisioned (WP provider path) | Downloads accessible or Order linked | No consent / local / synced / sync error | Stable terminal |
| V-07 | Logged-in | Failed | Created (pending) or Cancelled/Failed | any non-authority-mutating state | No order linked or Order linked | any | Transitional |
| V-08 | Anonymous | Failed | Cancelled/Failed | Not provisioned | No order linked or Order linked | any | Stable terminal |

### Transitional states
- Payment `Processing` with Order `Processing` and Auth `Anonymous` is valid during checkout return + webhook finalization window.
- Auth callback resolving with Supabase Identity `Invited`/`OTP verified` is valid if convergence target is `Logged-in (Supabase onboarded)` or gated non-onboarded state.
- Consent `Sync error` is valid if local consent remains authoritative and commerce flow is unaffected.

### Stable terminal states
- Commerce complete terminal: `Payment = Paid` + `Order = Completed` + provisioning converged (`Claimed` or `Downloads accessible`).
- Failed terminal: `Payment = Failed` + `Order = Cancelled/Failed` with no forced identity mutation.
- Marketing terminal variants are independent of payment/order authority as long as consent authority is local.

## 4) Invalid / Impossible States
| Invalid Combination | Why Invalid | Invariant Preventing It |
|---|---|---|
| Paid + Order Cancelled/Failed | Payment truth cannot converge to failed order lifecycle without explicit refund/reversal model | Payment authority and order convergence contract |
| Onboarded = true while Auth = Anonymous | Onboarded requires authenticated bridge/session continuity for effective account access | Auth/session authority + onboarding gate |
| Downloads accessible without order linkage | Downloads must be derived from owned order/download entitlement | Provisioning ownership invariant |
| Consent synced to Brevo without local consent | Remote sync cannot create consent truth | Local consent authority doctrine |
| UI-selected gateway differs from submitted `payment_method` | Checkout must submit deterministic selected gateway | Submission integrity rule |
| Callback loop state with no convergence terminal | Callback paths must be repeat-safe and loop-free | Callback convergence invariant |

## 5) High-Risk Transitional States
- Payment just confirmed + Auth callback in progress
  - Risk: order success branch and auth bridge race causing unstable UI.
- Guest paid order + Claim in progress
  - Risk: temporary mismatch between paid order and downloads visibility.
- Callback resolving + Fragment refresh
  - Risk: stale client state if checkout/account listeners rebind unpredictably.
- Email pending + Security tab redirect
  - Risk: pending-email banner/callback params desync.

These transitions require explicit regression validation because they combine independent authority domains under asynchronous events.

## 6) Convergence Rules
- Every flow must converge to a stable terminal state.
- No domain may mutate outside its authority boundary.
- Repeated triggers must converge (idempotent behavior).
- Payment and order authority cannot be overridden by auth, onboarding, or marketing flows.
- Consent state cannot be created by remote destination systems.

## 7) Usage Protocol
During refactor:
- map intended changes to impacted state domains
- verify no invalid combination is introduced
- declare convergence target states before implementation

During re-audit:
- sample high-risk transitions
- check observed states against valid/invalid matrix rules
- open/update risk entries for non-convergent behavior

During regression validation:
- run journeys that traverse high-risk transitional states
- verify final state lands in a valid stable terminal state
- confirm authority boundaries remained intact throughout the flow


---

## Source: `docs/00-governance/technical-hardening-plan.md`

# Technical Hardening Plan

## 1) Purpose
This plan defines a controlled, docs-first hardening sequence for Tier 0 system-critical surfaces.
It is used to reduce regression risk before any refactor by locking invariants, verification expectations, and release stop signals.

Usage model:
- read this plan before Tier 0 changes
- execute phase-by-phase validation gates
- release only when Tier 0 acceptance criteria are satisfied

## 2) Tier 0 Hardening Targets (from Blast Radius Map)
- Surface: `woocommerce/templates/checkout/payment.php`
  - What can break: payment selector state diverges from submitted gateway; fragment refresh leaves invalid active state.
  - Protecting invariant: visible selected method, radio state, and submit payload must remain deterministic and synchronized.
  - Must verify: method select -> fragment refresh -> submit path.

- Surface: `woocommerce/templates/checkout/order-received.php`
  - What can break: paid orders show unstable post-order branch; guest/account CTA loops.
  - Protecting invariant: payment truth is authoritative; auth/provisioning can gate UI only.
  - Must verify: paid order guest path, logged-in path, failed payment path.

- Surface: `includes/Gateways/class-bw-abstract-stripe-gateway.php` (`handle_webhook()`)
  - What can break: webhook idempotency and signature validation failures mutate order state incorrectly.
  - Protecting invariant: authenticated webhook events converge order state exactly once.
  - Must verify: duplicate webhook replay safety and invalid signature rejection.

- Surface: gateway handlers in `class-bw-google-pay-gateway.php`, `class-bw-apple-pay-gateway.php`, `class-bw-klarna-gateway.php`
  - What can break: process/webhook race leaves inconsistent order/payment state.
  - Protecting invariant: gateway return path must converge with webhook outcome deterministically.
  - Must verify: return flow + webhook completion alignment per gateway.

- Surface: `includes/woocommerce-overrides/class-bw-supabase-auth.php`
  - What can break: onboarding marker corruption, callback/session instability, non-idempotent order claim.
  - Protecting invariant: auth/provisioning transitions are idempotent and never override payment/order authority.
  - Must verify: callback login, onboarding completion, repeated claim safety.

- Surface: `assets/js/bw-supabase-bridge.js` + `woocommerce/woocommerce-init.php` callback preload
  - What can break: auth callback loops, stale callback rendering, pre-auth flash.
  - Protecting invariant: callback path resolves session before rendering sensitive account surfaces.
  - Must verify: callback from invite/recovery, stale callback cleanup, no-flash first paint.

- Surface: `includes/woocommerce-overrides/class-bw-my-account.php`
  - What can break: endpoint gating blocks valid users or misroutes set-password/callback flows.
  - Protecting invariant: My Account routing respects WP session authority + onboarding gate rules.
  - Must verify: dashboard/downloads/orders/settings/logout + set-password endpoint transitions.

- Surface: `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php` (`can_subscribe_order()` + paid hooks)
  - What can break: consent bypass, subscribe path blocking commerce, inconsistent consent meta.
  - Protecting invariant: no consent = no API call; Brevo failures never block payment/order.
  - Must verify: no-opt-in order, opt-in paid order, retry path non-blocking behavior.

- Surface: governance callback contract (`./callback-contracts.md`) as runtime review gate
  - What can break: callback handlers mutate non-owned states or fail to converge.
  - Protecting invariant: callback authority boundaries are enforced across payment/auth/order domains.
  - Must verify: callback side effects mapped only to owned state domains.

## 3) Hardening Actions (Sequenced)
### Phase 1: Callback & webhook resilience hardening
- Objective: lock callback/webhook authority and idempotency behavior.
- Scope:
  - `class-bw-abstract-stripe-gateway.php` webhook path
  - gateway webhook handlers (Google Pay / Apple Pay / Klarna)
  - Supabase auth callback bridge model
- Acceptance criteria:
  - callback/webhook ownership boundaries documented and reviewed
  - replay-safe/idempotent behavior explicitly verified on critical paths
  - invalid callback/webhook input does not mutate authoritative states
- Rollback principle (docs-level):
  - if convergence or ownership cannot be demonstrated, freeze release and revert to previous documented callback behavior baseline.

### Phase 2: Checkout render stability hardening
- Objective: prevent selector/fragment/UPE regressions in checkout payment UI.
- Scope:
  - `woocommerce/templates/checkout/payment.php`
  - `assets/js/bw-payment-methods.js`
  - UPE interaction surfaces documented in payments/checkout maps
- Acceptance criteria:
  - selector contract preserved after fragment refresh
  - no duplicated active methods or conflicting UPE/custom selector states
  - submit method always matches active UI/radio state
- Rollback principle (docs-level):
  - if selector determinism fails, restore last known stable payment rendering contract and block rollout.

### Phase 3: Supabase bridge stability hardening
- Objective: stabilize callback, onboarding, anti-flash, and claim idempotency.
- Scope:
  - `class-bw-supabase-auth.php`
  - `assets/js/bw-supabase-bridge.js`
  - callback preload and routing helpers in `woocommerce-init.php`
- Acceptance criteria:
  - no infinite callback loops
  - no pre-resolution My Account flash during callback path
  - onboarding marker and guest claim paths are repeat-safe
- Rollback principle (docs-level):
  - if callback stability is not guaranteed, force documented safe degrade to explicit login/retry path and stop release.

### Phase 4: My Account stability hardening
- Objective: protect My Account end-to-end navigation, settings, and gated access flows.
- Scope:
  - `class-bw-my-account.php`
  - My Account templates (navigation, edit-account, downloads, orders, set-password)
  - account-side JS controllers influencing state transitions
- Acceptance criteria:
  - navigation and endpoint routing remain stable across auth states
  - settings updates preserve authority boundaries (WP/Woo/Supabase lanes)
  - downloads/orders access follows documented claim/onboarding contracts
- Rollback principle (docs-level):
  - if endpoint gating misroutes authenticated users, revert to last stable route matrix before release.

### Phase 5: Brevo non-blocking + consent hardening
- Objective: confirm and preserve existing consent-gated, non-blocking behavior.
- Scope:
  - checkout consent capture and meta persistence
  - paid hook subscribe timing
  - admin retry/check observability path
- Acceptance criteria:
  - consent gate remains mandatory in all write paths
  - Brevo failures do not affect checkout/payment/order completion
  - local audit state remains coherent after retries/checks
- Rollback principle (docs-level):
  - if consent or non-blocking invariant is uncertain, disable write-side subscription behavior until invariants are restored.

## 4) Regression Minimum Suite (Tier 0)
Before release, the following minimum journeys must pass:
- Guest checkout with successful payment -> stable order-received branch -> valid account/onboarding CTA.
- Logged-in checkout with successful payment -> deterministic order status and account visibility.
- Payment webhook replay simulation -> no duplicate side effects.
- Invalid webhook/callback input simulation -> no unauthorized state mutation.
- Supabase invite/callback flow -> no loop, no flash, onboarding completion converges.
- My Account core surfaces -> dashboard, downloads, orders, settings, logout.
- Brevo consent gate:
  - opt-in path triggers subscribe attempt after paid signal
  - no-opt-in path performs no remote subscribe call

## 5) Stop Conditions
Stop release immediately if any of the following occurs:
- Payment authority is ambiguous (UI success with non-converged payment state).
- Webhook/callback idempotency cannot be demonstrated.
- Callback loops, ghost loaders, or pre-auth content flash appear.
- My Account route/gating prevents valid authenticated access.
- Consent-gated marketing writes occur without valid consent evidence.
- Any Tier 0 journey in the minimum suite fails.

## 6) References
- [Blast-Radius Consolidation Map](./blast-radius-consolidation-map.md)
- [Unified Callback Contracts](./callback-contracts.md)
- [Cross-Domain State Dictionary](./cross-domain-state-dictionary.md)
- [System Normative Charter](./system-normative-charter.md)
- [Regression Protocol](../50-ops/regression-protocol.md)


---

## Source: `docs/00-governance/working-protocol.md`

# Blackwork Working Protocol

## 1. Purpose
This protocol defines the official governance-driven workflow for system changes and audits in Blackwork.
It formalizes how to execute risk mitigation, controlled refactors, Tier 0 discipline, regression validation, and periodic re-audit.

This protocol is binding for all high-impact domains:
- Checkout
- Payments
- Auth / Supabase
- My Account
- Brevo
- Cross-domain callback and state contracts

Any technical task that touches these domains must follow this protocol before implementation starts.

## 1.1 Task Templates
Task templates are located in:

`docs/templates/`

Templates:
- `task-start-template.md`
- `task-closure-template.md`
- `maintenance-task-template.md`

## 2. Core Principle
Code is subordinate to governance.

This means:
- architecture invariants are primary constraints, not optional guidelines
- Tier 0 surfaces cannot be modified without blast-radius and risk review
- implementation speed cannot override authority boundaries
- release decisions must be based on proven convergence, not assumptions

No Tier 0 modification is allowed without:
1. Blast Radius review
2. Risk Register review
3. Explicit invariants preservation statement
4. Regression path declaration

## 3. Standard Work Cycle
### Phase A – Identification
Objective: classify the change and establish governance context before touching code.

Required actions:
1. Identify the domain and sub-domain:
   - checkout / payments / auth / supabase / my-account / brevo / cross-domain
2. Review Blast Radius:
   - identify whether impacted surfaces are Tier 0, Tier 1, or Tier 2
3. Review Risk Register:
   - list existing risk IDs relevant to the intended change
4. Identify invariants:
   - payment authority
   - order authority
   - auth/session authority
   - consent authority
   - callback idempotency and convergence constraints

Mandatory output of Phase A:
- `Domain`
- `Tier classification`
- `Relevant Risk IDs`
- `Invariants to preserve`
- `Initial regression scope`

No implementation is allowed before this output is complete.

### Phase B – Refactor Activation Model
Objective: activate implementation with a controlled prompt that enforces governance constraints.

Use the following template as mandatory activation payload:

```md
# Refactor Activation Prompt

## Context
- Problem statement:
- Domain:
- Tier level:
- Related Risk IDs:

## Governance Constraints
- Invariants to preserve:
  1.
  2.
  3.
- Authority boundaries that must not change:
  - Payment:
  - Order:
  - Auth/Session:
  - Consent:

## Scope
- File scope (explicit allowlist):
  - 
  - 
- Explicit out-of-scope files:
  - 
  - 

## Diff Discipline
- Minimal diff rule: apply the smallest change that restores/implements intended behavior.
- No unrelated edits.
- No opportunistic cleanup outside declared scope.

## Regression Requirement
- Mandatory checklist to execute:
  - Guest paid checkout
  - Logged-in paid checkout
  - Webhook replay/idempotency
  - Callback flow convergence
  - Domain-specific critical path

## Deliverables
- Summary of modified files
- Invariants verification statement
- Regression results
- Risk Register status suggestion (Open/Monitoring/Mitigated/Resolved)
```

Activation gate:
- if Risk IDs are missing for a Tier 0/Tier 1 task, create or map them before implementation
- if invariants cannot be stated clearly, stop and escalate clarification

### Phase C – Controlled Implementation Rules
Objective: execute only changes that remain inside governance boundaries.

Mandatory rules:
1. Do not change authority boundaries unless explicitly declared and approved.
2. Do not modify webhook authority semantics.
3. Do not bypass consent gate logic.
4. Do not alter onboarding semantics implicitly.
5. Payment truth cannot be changed by UI refactors.
6. Do not change callback ownership model through convenience edits.
7. Do not merge cross-domain side effects into a single unbounded patch.

Implementation discipline:
- keep diffs small and bounded
- preserve existing idempotency markers/guards
- preserve fallback/degrade behavior for non-blocking commerce
- avoid mixed concerns in a single commit (transport/state/render in one patch is discouraged for Tier 0)

### Phase D – Regression Minimum Suite
Objective: prove invariants were preserved after implementation.

Mandatory journeys before release:
1. Guest paid checkout
2. Logged-in paid checkout
3. Webhook replay handling
4. Supabase callback flow
5. My Account navigation
6. Downloads visibility
7. Brevo opt-in / no-opt-in behavior

Expected validation outcomes:
- deterministic gateway submission
- single-source payment truth
- stable callback convergence (no loop, no ghost loader, no flash)
- consistent onboarding marker behavior
- deterministic account route behavior
- consent gate never bypassed

If any mandatory journey fails, release is blocked.

### Phase E – Governance Update
Objective: synchronize governance artifacts with real system state after change validation.

Mandatory post-change actions:
1. Update Risk Register status for involved risk IDs.
2. Update Technical Hardening Plan if sequencing, mitigation priority, or acceptance criteria changed.
3. Update domain audit documents only if runtime behavior changed.
4. Record whether invariants were preserved or intentionally modified.

Status discipline:
- `Open`: risk still active and unmanaged
- `Monitoring`: mitigation exists but stability still being observed
- `Mitigated`: mitigation validated, residual risk low
- `Resolved`: independently re-audited and confirmed closed

## 4. Periodic System Re-Audit
Periodic re-audit is mandatory to detect drift between governance contracts and runtime reality.

Suggested cadence:
- Tier 0 domains: monthly or before major release
- Tier 1 domains: per milestone
- Cross-domain contracts: every time callback or authority semantics change

Use the following structured re-audit prompt:

```md
# Re-Audit Prompt

## Scope
- Domains:
- Anchors:
- Related Risk IDs:

## Validation Targets
1. Authority hierarchy validation
   - Payment authority unchanged?
   - Order authority unchanged?
   - Auth/session authority unchanged?
   - Consent authority unchanged?

2. Callback convergence validation
   - Any loop path?
   - Any ghost loader/stuck state?
   - Any non-idempotent callback side effect?

3. Idempotency guarantees
   - Webhook replay-safe?
   - Guest claim replay-safe?
   - Marketing sync replay-safe?

4. State boundary validation
   - Cross-domain writes within allowed boundaries?
   - Any UI layer overriding authoritative state?

## Output Required
- Findings by severity
- Updated risk status recommendation
- Regression gaps identified
- Governance document updates required
```

Re-audit stop condition:
- if authority, callback convergence, idempotency, or state boundary checks fail, open/raise corresponding risks immediately and block release.

## 5. Absolute Rules
Never do the following on Tier 0 surfaces:
- Never let UI state redefine payment or order authority.
- Never bypass webhook validation/idempotency guards.
- Never disable or weaken consent gate checks to unblock flow.
- Never silently change onboarding marker semantics.
- Never introduce callback redirects without loop/convergence proof.
- Never mix unrelated Tier 0 domains in an unscoped patch.
- Never mark risks as `Resolved` without audit-backed evidence.
- Never release when mandatory regression journeys are incomplete.

## 6. Governance Stack Reference
This protocol depends on the following governance stack:
- [System Normative Charter](./system-normative-charter.md)
- [Cross-Domain State Dictionary](./cross-domain-state-dictionary.md)
- [Unified Callback Contracts](./callback-contracts.md)
- [Blast-Radius Consolidation Map](./blast-radius-consolidation-map.md)
- [Risk Register](./risk-register.md)
- [Technical Hardening Plan](./technical-hardening-plan.md)
- Domain audits:
  - [Checkout Payment Selector Audit](../50-ops/audits/checkout-payment-selector-audit.md)
  - [My Account Domain Audit](../50-ops/audits/my-account-domain-audit.md)

If conflicts appear, the normative order is:
1. System Normative Charter
2. Cross-Domain State Dictionary
3. Callback Contracts
4. Blast Radius + Risk Register
5. Hardening Plan + domain audits

## 7. Launching a Check in a New GPT Session
When opening a new GPT session for a technical task, provide a structured context package.

Mandatory input payload:
1. Problem statement:
   - precise runtime issue or desired change
2. Domain:
   - one or more of checkout/payments/auth/supabase/my-account/brevo
3. Invariants:
   - explicit list of invariants that must remain unchanged
4. Risk context:
   - relevant Risk IDs from Risk Register
5. Scope:
   - file allowlist and out-of-scope list

Request format:
- ask GPT to generate a `Refactor Activation Prompt` based on this protocol
- require explicit mapping between planned edits and invariants
- require regression checklist before implementation

Recommended bootstrap message:

```md
Follow Blackwork Working Protocol.

Problem:
Domain:
Tier:
Risk IDs:
Invariants to preserve:
Allowed files:
Out-of-scope files:

Generate the Refactor Activation Prompt only.
Do not implement yet.
```

## 8. Maturity Model
### If this protocol is followed
- Tier 0 changes become predictable and auditable
- regression detection happens before release impact
- authority boundaries remain stable across refactors
- risk closure becomes evidence-based, not assumption-based
- system knowledge compounds through structured governance updates

### If this protocol is ignored
- cross-domain regressions increase and become harder to isolate
- callback and state drift accumulate silently
- release quality depends on manual memory instead of contracts
- risk statuses lose meaning and governance layer degrades
- emergency hotfix frequency rises due to preventable invariant breaks

Governance maturity target:
- every high-impact change traces to Risk IDs, invariants, and validated regression outcomes
- no Tier 0 change proceeds without protocol-compliant activation and closure


---

## Source: `docs/00-overview/README.md`

# 00 Overview

High-level entry points and orientation for the documentation set.

## Identity Layer
- [Project Identity](project-identity.md)
- [System Mental Map](system-mental-map.md)
- [Cultural Manifesto](cultural-manifesto.md)
- [Business Model](business-model.md)

## Root technical files (not moved)
- `AGENTS.md`: workflow checks and execution constraints for coding agents.
- `CLAUDE.md`: project guidance for assistant tooling.


---

## Source: `docs/00-overview/business-model.md`

# Business Model

## Current Model Overview
Blackwork currently operates with a hybrid model that combines productized archive content (web shop) and a dedicated software layer (app).

## Web Shop (Current)
The web shop monetization is based on one-time purchases:
- Single-purchase digital collections
- Books
- Potential print products

The shop model is designed to fund archive curation, production quality, and long-term preservation work.

## App (Current)
The app model is intentionally simple at this stage:
- Free limited access
- Lifetime license (approximately 100 EUR)
- No subscription model for now

This structure lowers entry barriers while keeping a sustainable path for continued development.

## Strategic Notes
- The model may evolve as the archive and tooling mature.
- Current priority is sustainability, archive growth, and product quality.
- Monetization decisions should remain aligned with cultural integrity and long-term brand positioning.

## Guiding Principle
Revenue is a support system for cultural and product continuity, not the sole objective of the project.


---

## Source: `docs/00-overview/cultural-manifesto.md`

# Cultural Manifesto

Blackwork begins from a simple conviction: images are not disposable. Visual culture carries memory, technique, and language across generations. When those traces disappear, creative practice becomes flatter, faster, and easier to standardize.

Our work is to resist that flattening.

Blackwork operates as a bridge between historical material and contemporary design practice. We do not treat the past as nostalgia, and we do not treat the present as a race for novelty. We treat both as part of the same continuum: what came before is not an obstacle to innovation, but one of its necessary foundations.

Many visual archives remain fragmented, hidden, or inaccessible. Some survive only in fragile physical formats, scattered private collections, out-of-print books, or forgotten editorial contexts. Blackwork is committed to the digital resurrection of that material with care, context, and intent. The goal is not to collect endlessly, but to preserve meaning and make it usable.

This commitment requires discipline.

Curation matters more than volume. Context matters more than trend velocity. Metadata matters because it protects interpretation. Provenance matters because culture without memory quickly becomes decoration. For us, each collection is not just a product: it is an editorial decision about what deserves to remain visible.

We build for designers and artists who need depth, not noise. People who understand that references are not interchangeable, and that quality emerges from relationships between forms, periods, techniques, and symbols. Blackwork should help them discover, connect, and transform material without erasing its origin.

With this comes responsibility.

Cultural work in digital environments is never neutral. Choices about what to preserve, how to classify, and how to distribute shape what is remembered and what is forgotten. Blackwork accepts this ethical dimension. We do not claim to be exhaustive, but we do commit to rigor, transparency, and respect for source material.

Our pace is intentionally different from fast internet culture.

The dominant online model rewards immediacy, repetition, and constant turnover. Archive work requires the opposite: patience, verification, structure, and long-term stewardship. Blackwork is a slow archive in a fast environment. That is not a limitation; it is a strategic and cultural choice.

Technology is a means, not the center.

We use digital systems to improve access, navigation, and practical use, but technical sophistication is valuable only when it serves cultural integrity. The archive must remain legible, coherent, and trustworthy as it grows.

Blackwork is therefore not only a shop, and not only a tool. It is a living cultural system: preserving visual memory, enabling creative work, and building durable continuity between past and present.

This is the standard we set for ourselves.


---

## Source: `docs/00-overview/domain-map.md`

# Blackwork Core — Domain Map

## 1. Purpose
This document defines Blackwork Core system topology, authority surfaces, and domain boundaries.

This map MUST be used to determine:
- which domain owns truth,
- which domain may mutate state,
- which domain may only signal or render,
- which cross-domain overrides are prohibited.

This document is architectural and normative. It MUST NOT be used as roadmap content.

## 2. System Topology Overview
Text-based layered topology:

- **Layer 0: Authority Domains (Tier 0)**
  - Redirect / Routing Authority
  - Import Products Authority
  - Payment confirmation and order-state convergence surfaces
  - Auth/session authority convergence surfaces
- **Layer 1: Orchestration Domains (Tier 1)**
  - Checkout Orchestration
  - Cart Interaction orchestration
  - Consent/Brevo orchestration
  - Supabase/Auth integration orchestration
  - Runtime hook/event coordination
- **Layer 2: Presentation Domains (Tier 2)**
  - Header / Global Layout presentation
  - UI overlays, loaders, and view composition layers
  - Non-authoritative visual reflection surfaces

Layering rules:
- Lower layers MUST NOT override higher-layer authority.
- Presentation layers MUST NOT become authority surfaces.
- Orchestration layers MAY coordinate but MUST NOT redefine truth ownership.

## Truth Ownership Matrix
| Domain | Tier | Defines Truth | Mutates State | Signals Only | Presentation Only |
|---|---:|---|---|---|---|
| Redirect / Routing Authority | 0 | Yes | No | No | No |
| Import Products Authority | 0 | Yes | Yes | No | No |
| Payment Confirmation Authority | 0 | Yes | Yes | No | No |
| Payment Provider Integration | 1 | No | Yes | No | No |
| Checkout Orchestration | 1 | No | Yes | No | No |
| Cart Interaction (Cart Popup + Cart Page fallback) | 1 | No | Yes | No | No |
| Header / Global Layout | 2 | No | No | No | Yes |
| Consent / Brevo Sync | 1 | No | Yes | No | No |
| Supabase/Auth Sync | 1 | No | Yes | No | No |
| Shared Runtime Utilities (fragments, loaders, JS bridges) | 2 | No | No | Yes | No |

## 3. Domain Inventory

### 3.1 Redirect / Routing Authority
- Domain Name: Redirect / Routing Authority
- Tier: 0
- Primary Responsibility: Deterministic route redirection under protected-route policy.
- Authority Level: Defines Truth
- Owns: Redirect rule resolution and routing decision surface.
- Reads From: Persisted redirect rules and runtime request path/query.
- Cannot Override:
  - Protected commerce routes (`/cart`, `/checkout`, `/my-account` and protected prefixes)
  - Admin/platform critical routes
- Related Specs / ADR references:
  - `docs/30-features/redirect/redirect-engine-v2-spec.md`
  - `docs/50-ops/runtime-hook-map.md`
  - `docs/60-adr/ADR-002-authority-hierarchy.md`

### 3.2 Import Products Authority
- Domain Name: Import Products Authority
- Tier: 0
- Primary Responsibility: Deterministic product identity and idempotent product import convergence.
- Authority Level: Defines Truth
- Owns: Import identity contract and Woo product write authority for import flow.
- Reads From: Structured import input and canonical SKU identity.
- Cannot Override:
  - Runtime cart state
  - Checkout/payment runtime behavior
  - Presentation-layer state
- Related Specs / ADR references:
  - `docs/30-features/import-products/import-products-vnext-spec.md`
  - `docs/00-planning/decision-log.md`
  - `docs/60-adr/ADR-002-authority-hierarchy.md`

### 3.3 Checkout Orchestration
- Domain Name: Checkout Orchestration
- Tier: 1
- Primary Responsibility: Coordinate checkout runtime flow, render orchestration, and submission consistency.
- Authority Level: Mutates State
- Owns: Checkout runtime orchestration surface and checkout-specific recomputation lifecycle.
- Reads From: Cart state, payment availability, integration readiness.
- Cannot Override:
  - Payment confirmation truth
  - Redirect authority
  - Consent authority
- Related Specs / ADR references:
  - `docs/30-features/checkout/checkout-architecture-map.md`
  - `docs/40-integrations/cart-checkout-responsibility-matrix.md`
  - `docs/60-adr/ADR-001-upe-vs-custom-selector.md`
  - `docs/60-adr/ADR-003-callback-anti-flash-model.md`

### 3.4 Payment Confirmation Authority
- Domain Name: Payment Confirmation Authority
- Tier: 0
- Primary Responsibility: Establish final payment outcome truth and converge local order payment state from authoritative provider confirmation events.
- Authority Level: Defines Truth
- Owns: Provider-confirmed payment truth and terminal payment-state convergence surface.
- Reads From: Provider callbacks/webhooks and authoritative local order context.
- Cannot Override:
  - Routing authority policy
  - Auth/session authority boundaries
  - Consent authority boundaries
- Related Specs / ADR references:
  - `docs/40-integrations/payments/payments-architecture-map.md`
  - `docs/50-ops/runtime-hook-map.md`
  - `docs/60-adr/ADR-002-authority-hierarchy.md`
  - `docs/60-adr/ADR-003-callback-anti-flash-model.md`

### 3.5 Payment Provider Integration
- Domain Name: Payment Provider Integration
- Tier: 1
- Primary Responsibility: Execute provider-specific payment flows under local authority contracts.
- Authority Level: Mutates State
- Owns: Gateway/runtime integration surface, provider request/response adaptation, and provider-specific orchestration.
- Reads From: Checkout intent, gateway configuration, provider callbacks/webhooks.
- Cannot Override:
  - Payment Confirmation Authority
  - Redirect authority policy
  - Auth/session authority boundaries
- Related Specs / ADR references:
  - `docs/40-integrations/payments/payments-architecture-map.md`
  - `docs/60-adr/ADR-002-authority-hierarchy.md`
  - `docs/60-adr/ADR-003-callback-anti-flash-model.md`
  - `docs/60-adr/ADR-006-provider-switch-model.md`

### 3.6 Cart Interaction (Cart Popup + Cart Page fallback)
- Domain Name: Cart Interaction
- Tier: 1
- Primary Responsibility: Operate cart interaction UX over canonical Woo cart state.
- Authority Level: Mutates State
- Owns: Cart interaction lifecycle and progressive enhancement behavior.
- Reads From: Canonical Woo cart session and fragment lifecycle events.
- Cannot Override:
  - Payment truth
  - Order truth
  - Redirect authority
- Related Specs / ADR references:
  - `docs/30-features/cart-popup/cart-popup-module-spec.md`
  - `docs/40-integrations/cart-checkout-responsibility-matrix.md`
  - `docs/60-adr/ADR-001-upe-vs-custom-selector.md`

### 3.7 Header / Global Layout
- Domain Name: Header / Global Layout
- Tier: 2
- Primary Responsibility: Global layout rendering, navigation presentation, and non-authoritative runtime UI state.
- Authority Level: Presentation Only
- Owns: Header render surface and responsive/scroll presentation state machine.
- Reads From: Runtime context, menu configuration, read-only cart indicators.
- Cannot Override:
  - Routing decisions
  - Payment/order/provisioning/consent authority
  - Checkout/cart business truth
- Related Specs / ADR references:
  - `docs/30-features/header/header-module-spec.md`
  - `docs/30-features/header/header-scroll-state-machine-spec.md`
  - `docs/30-features/header/header-responsive-contract.md`
  - `docs/30-features/header/header-admin-settings-map.md`

### 3.8 Consent / Brevo Sync
- Domain Name: Consent / Brevo Sync
- Tier: 1
- Primary Responsibility: Enforce consent gate and execute marketing sync as downstream non-blocking flow.
- Authority Level: Mutates State
- Owns: Consent-gated marketing write operations and local consent-sync state.
- Reads From: Local consent metadata and order lifecycle triggers.
- Cannot Override:
  - Payment truth
  - Order lifecycle authority
  - Auth/provisioning authority
- Related Specs / ADR references:
  - `docs/40-integrations/brevo/brevo-architecture-map.md`
  - `docs/60-adr/ADR-004-consent-gate-doctrine.md`
  - `docs/60-adr/ADR-002-authority-hierarchy.md`

### 3.9 Supabase/Auth Sync
- Domain Name: Supabase/Auth Sync
- Tier: 1
- Primary Responsibility: Coordinate identity/session bridge and onboarding/provisioning convergence with local authority constraints.
- Authority Level: Mutates State
- Owns: Auth callback/session bridge and onboarding claim convergence path.
- Reads From: Auth provider outputs, local session state, order-linked provisioning signals.
- Cannot Override:
  - Payment confirmation truth
  - Routing authority policy
  - Consent authority boundaries
- Related Specs / ADR references:
  - `docs/40-integrations/auth/auth-architecture-map.md`
  - `docs/40-integrations/supabase/supabase-architecture-map.md`
  - `docs/60-adr/ADR-002-authority-hierarchy.md`
  - `docs/60-adr/ADR-005-claim-idempotency-rule.md`

### 3.10 Shared Runtime Utilities (fragments, loaders, JS bridges)
- Domain Name: Shared Runtime Utilities
- Tier: 2
- Primary Responsibility: Runtime event propagation, UI sync triggers, and non-authoritative glue behavior.
- Authority Level: Signals Only
- Owns: Lifecycle signaling surfaces (fragments, `updated_checkout`, loader/bridge utilities).
- Reads From: Domain state snapshots and frontend lifecycle events.
- Cannot Override:
  - Any domain authority truth
  - Payment/order/auth/consent routing decisions
- Related Specs / ADR references:
  - `docs/50-ops/runtime-hook-map.md`
  - `docs/50-ops/regression-coverage-map.md`
  - `docs/60-adr/ADR-003-callback-anti-flash-model.md`

## 4. Cross-Domain Interaction Model
Cross-domain interaction rules:
- Redirect domain MUST NOT override protected Checkout routes.
- Import domain MUST NOT mutate runtime Cart state.
- Header domain MUST NOT define business logic or authority truth.
- Cart Popup domain MUST NOT define payment truth.
- Payment domain MUST NOT override routing authority.
- Consent/Brevo domain MUST NOT alter payment or order authority.
- Supabase/Auth domain MUST NOT redefine payment confirmation truth.

Interaction discipline:
- Domains MAY read required upstream state when explicitly needed.
- Domains MUST NOT mutate authority outside owned boundaries.
- Presentation and utility domains MUST remain non-authoritative.

## 5. Authority Hierarchy Enforcement
Authority hierarchy is governed by ADR-002.

Enforcement rules:
- Higher-tier authority surfaces MUST NOT be overridden by lower-tier domains.
- Lower-tier orchestration/presentation layers MAY coordinate or reflect state but MUST NOT redefine truth.
- In conflicts, declared authority owner MUST prevail.

Reference:
- `docs/60-adr/ADR-002-authority-hierarchy.md`

## 6. Change Governance
Change governance by tier:
- Tier 0 changes REQUIRE ADR and explicit governance traceability.
- Tier 1 changes REQUIRE regression validation against affected domain contracts.
- Tier 2 changes are UX-scoped and MUST remain non-authoritative.

Global governance constraints:
- Any change that shifts authority boundaries MUST be documented as architecture decision.
- Any change that modifies runtime hook priority on Tier 0 surfaces MUST be treated as behavior-contract modification.
- Documentation MUST be updated when domain boundaries or authority ownership change.

## System Invariants
- Exactly one authority MUST exist per truth surface.
- Authority MUST NOT be overridden by lower tiers.
- Presentation layers MUST NOT create or redefine truth.
- Canonical product identity MUST be SKU.
- Runtime hook priority MUST be treated as behavioral contract.
- Tier 0 domains REQUIRE ADR for change.


---

## Source: `docs/00-overview/project-identity.md`

# Project Identity

## Vision
Blackwork exists to become a long-term cultural reference for visual memory in blackwork and adjacent design languages: not only a store, but an evolving archive that preserves, contextualizes, and reactivates historical imagery for contemporary creative practice.

## Mission
Build a sustainable ecosystem where archival depth, curatorial rigor, and practical design utility coexist. The project must help people discover, understand, and use rare visual material responsibly across research, inspiration, and production.

## Core Pillars

### 1. Cultural Archive
A curated, growing body of historical and niche visual material, organized with editorial intent and contextual metadata. The archive is not a random content feed: it is a structured memory layer.

### 2. Digital Collections
High-quality collections distributed through the web shop, designed as usable cultural assets for designers, artists, and studios. Collections are selected for depth, relevance, and formal coherence.

### 3. Designer Tool (App)
A dedicated application ecosystem for exploration, filtering, and creative workflow support. The app extends beyond e-commerce logic and is oriented to professional use, discovery speed, and knowledge retrieval.

## Strategic Positioning
- Blackwork is an artistic niche brand with clear cultural intent.
- The project is archive-driven, not trend-driven.
- It is not a marketplace: quality and coherence are prioritized over volume.
- It is not VC-driven SaaS: growth is guided by cultural value and sustainability, not aggressive monetization cycles.

## Long-Term Potential
- AI-assisted semantic archive navigation (conceptual, stylistic, historical relationships).
- Deeper metadata systems for provenance, periodization, motifs, and cross-references.
- Progressive expansion of formats: collections, books, prints, and research-oriented digital utilities.
- A durable knowledge graph that connects imagery, context, and creative application.

## Platform Separation

### Web Shop (WordPress)
The current WordPress plugin stack powers the shop experience: product delivery, checkout flows, account logic, and commerce-related integrations.

### App Ecosystem (Electron + Web App)
The app is a separate product layer with distinct architecture, UX goals, and roadmap. It should be documented as an autonomous system, interoperable with the brand, but not reduced to shop mechanics.

## Identity Principle
Blackwork should remain culturally serious, technically reliable, and strategically independent: a project built to last, not to peak quickly.


---

## Source: `docs/00-overview/system-map.md`

# Blackwork System Map (AI Quickstart)

## 1) Purpose
- This file is a **2-minute orientation map** for AI agents.
- Use it to find entrypoints, authority surfaces, and canonical governance docs fast.
- It is **not** the full spec; detailed behavior stays in module/feature docs.

## 2) Repository Top-Level Map
- `blackwork-core-plugin.php`: main plugin bootstrap and loader.
- `admin/`: Blackwork Site admin panel router/settings and shared admin helpers.
- `includes/`: core runtime, modules, integrations, helpers, Woo overrides.
- `includes/modules/`: feature modules (`header`, `media-folders`, `system-status`, `theme-builder-lite`).
- `includes/admin/`: admin subsystems outside `includes/modules` (e.g. Mail Marketing/Brevo, checkout admin).
- `assets/`: shared frontend/admin JS/CSS assets used by widgets/runtime.
- `woocommerce/`: WooCommerce runtime integration and overrides bootstrap.
- `docs/`: governance, planning, architecture, feature specs, ops, tasks.

## 3) Runtime Entry Points (High-Level)
- Main bootstrap: [`blackwork-core-plugin.php`](../../blackwork-core-plugin.php)
  - Loads module entry files:
    - `includes/modules/header/header-module.php`
    - `includes/modules/theme-builder-lite/theme-builder-lite-module.php`
    - `includes/modules/media-folders/media-folders-module.php`
    - `includes/modules/system-status/system-status-module.php`
  - Loads Woo/runtime/support subsystems (`woocommerce/woocommerce-init.php`, integrations, helpers).
- Runtime hook registration hotspots:
  - `includes/modules/*/runtime/*.php`
  - `admin/class-blackwork-site-settings.php` (admin ajax/settings hooks)
  - `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php` (mail marketing ajax/admin)

## 4) Admin Entry Points (High-Level)
- Main Blackwork Site menu/router:
  - [`admin/class-blackwork-site-settings.php`](../../admin/class-blackwork-site-settings.php)
  - Registers top-level + submenu screens (`add_menu_page` / `add_submenu_page`).
- Module admin screens (examples):
  - Media Folders settings: `includes/modules/media-folders/admin/media-folders-settings.php`
  - Header settings: `includes/modules/header/admin/header-admin.php`
  - Theme Builder Lite settings: `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.php`
  - System Status: `includes/modules/system-status/admin/status-page.php`
  - Mail Marketing page: `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`
- Shared Admin UI kit:
  - CSS: `admin/css/bw-admin-ui-kit.css`
  - Enqueue/scoping: `bw_admin_enqueue_ui_kit_assets()` + `bw_is_blackwork_site_admin_screen()` in `admin/class-blackwork-site-settings.php`
  - Scope rule: styles are intended for `.bw-admin-root` Blackwork admin surfaces.

## 5) Module Map (Feature Modules)

### Media Folders (`includes/modules/media-folders/`)
- Purpose: Virtual folders for Media + selected admin list tables (Posts/Pages/Products) with isolated taxonomies.
- Key files:
  - Module: `media-folders-module.php`
  - Admin: `admin/media-folders-admin.php`, `admin/media-folders-settings.php`, `admin/assets/media-folders.js`, `admin/assets/media-folders.css`
  - Runtime: `runtime/ajax.php`, `runtime/media-query-filter.php`
  - Data: `data/installer.php`, `data/taxonomy.php`, `data/term-meta.php`
- Main AJAX endpoints:
  - `bw_media_get_folders_tree`, `bw_media_get_folder_counts`, `bw_media_create_folder`, `bw_media_rename_folder`, `bw_media_delete_folder`, `bw_media_assign_folder`, `bw_media_update_folder_meta`, `bw_mf_toggle_folder_pin`, `bw_mf_set_folder_color`, `bw_mf_reset_folder_color`, `bw_mf_get_corner_markers`
- Important invariants:
  - Admin-only behavior; no file-path/frontend media URL mutation.
  - Folder isolation by post type taxonomy mapping (`attachment/post/page/product`).

### System Status (`includes/modules/system-status/`)
- Purpose: Admin diagnostics dashboard with scoped health checks.
- Key files:
  - Module: `system-status-module.php`
  - Admin: `admin/status-page.php`, `admin/assets/system-status-admin.js`
  - Runtime: `runtime/check-runner.php`, `runtime/checks/*.php`
- Main AJAX endpoint:
  - `bw_system_status_run_check`
- Important invariants:
  - Read-only diagnostics; admin-scoped execution.

### Header (`includes/modules/header/`)
- Purpose: Custom header runtime + admin settings + live product search ajax.
- Key files:
  - Module: `header-module.php`
  - Admin: `admin/header-admin.php`, `admin/header-admin.js`
  - Frontend: `frontend/assets.php`, `frontend/fragments.php`, `frontend/header-render.php`, `frontend/ajax-search.php`
- Main AJAX endpoint:
  - `bw_live_search_products` (+ `nopriv`)
- Important invariants:
  - Header runtime isolated from Media Folders and checkout flows.

### Theme Builder Lite (`includes/modules/theme-builder-lite/`)
- Purpose: Template CPT + resolver runtime for footer/single/archive template application.
- Key files:
  - Module: `theme-builder-lite-module.php`
  - Admin: `admin/theme-builder-lite-admin.php`, `admin/bw-templates-list-ux.php`, `admin/import-template.php`
  - Runtime: `runtime/template-resolver.php`, `runtime/*-runtime.php`, `runtime/template-preview.php`
  - Data/CPT: `cpt/template-cpt.php`, `cpt/template-meta.php`
- Main AJAX endpoints:
  - `bw_tbl_search_preview_products`, `bw_tbl_update_template_type`
- Important invariants:
  - Fail-open resolver behavior (fallback to theme template on mismatch).

### Mail Marketing (Brevo) (`includes/admin/checkout-subscribe/` + `includes/integrations/brevo/`)
- Purpose: Admin + checkout subscription flow and Brevo sync/status operations.
- Key files:
  - Admin: `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`
  - Frontend: `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php`
  - Integration: `includes/integrations/brevo/class-bw-brevo-client.php`, `class-bw-mailmarketing-service.php`
- Main AJAX endpoints (admin):
  - `bw_brevo_test_connection`, `bw_brevo_order_refresh_status`, `bw_brevo_order_retry_subscribe`, `bw_brevo_order_load_lists`, `bw_brevo_user_check_status`, `bw_brevo_user_sync_status`
- Important invariants:
  - Capability + nonce guarded admin operations.

## 6) Data/Authority Surfaces
- **Options** (WordPress options table):
  - Blackwork core flags and module settings (e.g. Media Folders flags in `bw_core_flags`).
- **Taxonomies / terms / term meta**:
  - Media Folders authority via per-post-type taxonomies and term meta.
- **Post meta / user meta**:
  - Used by Theme Builder Lite and Mail Marketing state tracking.
- **Transient/object-cache surfaces**:
  - Used in Media Folders counts/tree/summary and other modules as optimization layers.
- Canonical authority/invariant references:
  - Media Folders spec: [`docs/30-features/media-folders/media-folders-module-spec.md`](../30-features/media-folders/media-folders-module-spec.md)
  - Admin panel map: [`docs/20-development/admin-panel-map.md`](../20-development/admin-panel-map.md)
  - Risk register: [`docs/00-governance/risk-register.md`](../00-governance/risk-register.md)

## 7) Governance & Workflow (AI Mandatory)
- [`docs/00-governance/ai-task-protocol.md`](../00-governance/ai-task-protocol.md): mandatory lifecycle (start template -> implementation -> closure template).
- [`docs/templates/task-start-template.md`](../templates/task-start-template.md): blocking pre-implementation declaration (scope, determinism, docs impact).
- [`docs/templates/task-closure-template.md`](../templates/task-closure-template.md): required closure verification/traceability format.
- [`docs/00-governance/risk-register.md`](../00-governance/risk-register.md): active governance risks and mitigations.
- [`docs/00-planning/decision-log.md`](../00-planning/decision-log.md): architecture/planning decisions and follow-ups.
- [`docs/20-development/admin-panel-map.md`](../20-development/admin-panel-map.md): admin routes, assets, screen contracts.

## 8) “If you want to change X, read Y”
- **Media Folders runtime/filtering/caching**:
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `includes/modules/media-folders/runtime/ajax.php`
  - `includes/modules/media-folders/runtime/media-query-filter.php`
- **Media Folders admin UX (sidebar, DnD, quick filters)**:
  - `includes/modules/media-folders/admin/media-folders-admin.php`
  - `includes/modules/media-folders/admin/assets/media-folders.js`
  - `includes/modules/media-folders/admin/assets/media-folders.css`
- **Admin UI look/placement/scoping**:
  - `docs/20-development/admin-panel-map.md`
  - `admin/css/bw-admin-ui-kit.css`
  - `admin/class-blackwork-site-settings.php`
- **Performance/caching changes**:
  - module spec performance sections + risk register entries (esp. Media Folders scalability)
  - follow “Performance Evidence Recording” in governance protocol.
- **Security/nonce/capability surfaces**:
  - module runtime ajax files + admin handlers
  - governance protocol + risk register before changing guards.

## 9) Notes / Non-goals
- This map is a **navigation aid** only.
- Detailed contracts, invariants, and edge-case behavior remain in module specs, architecture docs, and governed task artifacts.
- If documentation and code diverge, update governed docs via task lifecycle before implementation.

This file is the architectural entry point for AI agents.
If repository structure changes, this file must be updated.


---

## Source: `docs/00-overview/system-mental-map.md`

# System Mental Map

This document defines the navigation logic of the project as a cognitive system, not only as a file tree.

Blackwork documentation is organized around 5 core layers that explain how the project thinks, operates, and evolves.

## 1) Identity & Vision
This layer defines why the project exists, what it protects, and where it is going.

Primary references:
- [Project Identity](project-identity.md)
- [Cultural Manifesto](cultural-manifesto.md)
- [Business Model](business-model.md)

## 2) Architecture
This layer describes structural foundations, core modules, and technical contracts.

Reference:
- [10-architecture](../10-architecture/README.md)

## 3) Functional Domains
This layer maps product behavior and user-facing capabilities by domain (checkout, header, smart header, my account, product types, etc.).

Reference:
- [30-features](../30-features/README.md)

## 4) External Integrations
This layer covers integrations with third-party systems (payments, auth, Brevo, Supabase).

Reference:
- [40-integrations](../40-integrations/README.md)

## 5) Operations & Memory
This layer preserves operational practices and project memory.

References:
- [50-ops](../50-ops/README.md)
- [99-archive](../99-archive/README.md)

## System Boundary Clarification
- The WordPress plugin powers the SHOP domain only (commerce workflows, account flows, integrations tied to web sales).
- The App (Electron + Web App) is a separate ecosystem with its own architecture and product logic.
- Documentation is the shared cognitive infrastructure that keeps both present implementation and future expansion coherent.

## How to Use This Map
1. Start from identity if the question is strategic.
2. Move to architecture for structural decisions.
3. Move to features/integrations for implementation details.
4. Use ops/archive for maintenance context and historical continuity.

This mental map is the reference model for understanding where decisions belong.


---

## Source: `docs/00-planning/core-evolution-plan.md`

# Blackwork Core – Evolution Plan

This document is the governance planning layer for roadmap execution.
It tracks priority, risk, execution intent, and acceptance gates.
It MUST be used as planning reference only and MUST NOT replace ADRs.

## Tier 0 – Critical Data / Authority

### Import Products — Import Engine v2 (Implementation)
- Status: Backlog (planned in ~2 weeks)
- Risk classification: Tier 0 Data Integrity
- Short description: Implement the vNext bulk-safe importer with SKU-canonical identity, chunked execution, checkpoint resume, and run-level audit trail.
- Reference docs:
  - `docs/50-ops/audits/import-products-technical-audit.md`
  - `docs/50-ops/audits/import-products-developer-notes.md`
  - `docs/30-features/import-products/import-products-vnext-spec.md`
- Acceptance:
  - Re-run converges (no duplicates) per SKU
  - Chunked execution does not timeout on 800 rows
  - Persistent run log exists (run id + row errors)
  - Resume from checkpoint works after forced interruption
  - Image failures do not kill the entire run

### Redirect Engine Audit
- Status: Backlog
- Risk classification: High
- Short description: Audit redirect engine runtime behavior, rule precedence, and deterministic resolution across auth/checkout paths.
- Reference docs:
  - `docs/20-development/admin-panel-map.md`
  - `docs/50-ops/admin-panel-reality-audit.md`
- Acceptance:
  - Redirect rule precedence documented and reproducible
  - Conflict cases are enumerated with deterministic expected outcome
  - No route introduces circular redirect behavior
  - Admin setting to runtime propagation is verified
  - Audit output is linkable from planning/governance docs

### Mail Marketing / Brevo Validation
- Status: Backlog
- Risk classification: High
- Short description: Validate consent-gated mail marketing behavior and Brevo integration boundaries against governance invariants.
- Reference docs:
  - `docs/40-integrations/brevo/brevo-architecture-map.md`
  - `docs/60-adr/ADR-004-consent-gate-doctrine.md`
  - `docs/00-governance/system-normative-charter.md`
- Acceptance:
  - Consent gate behavior is validated end-to-end
  - Non-blocking commerce invariant is confirmed
  - Local authority over consent remains intact
  - Retry/observability paths are documented
  - Validation findings are captured in ops/audit docs

## Tier 1 – UX & Runtime

### Theme Builder Lite — Phase 1 (Fonts + Footer Override)
- Status: Completed (2026-03-02)
- Risk classification: Medium
- Short description: Delivered modular Theme Builder Lite Phase 1 with custom fonts, BW footer templates, Elementor support automation, preview-safe template rendering, and fail-open footer override behavior.
- Reference docs:
  - `docs/30-features/theme-builder-lite/theme-builder-lite-spec.md`
  - `docs/10-architecture/theme-builder-lite/runtime-hook-map.md`
  - `docs/50-ops/audits/theme-builder-lite-phase1-implementation.md`
- Acceptance:
  - `bw_template` editable with Elementor Free without manual settings changes
  - `bw_template` preview permalink resolves with HTTP 200
  - Footer override respects master/sub-feature flags and remains fail-open
  - Elementor editor preview recursion/conflict safeguards are active
  - Admin controls delivered as tabs (`Settings`, `Fonts`, `Footer`)

### Media Folders — Admin Media Library Module
- Status: Completed (2026-03-04)
- Risk classification: Medium
- Short description: Delivered isolated Media Library folder system (`upload.php`) with virtual taxonomy folders, list/grid filtering, drag/drop + bulk assignment, folder metadata actions (pin/color), assigned-media badge markers, optional marker tooltip, and quick type filters (Video/JPEG/PNG/SVG/Fonts).
- Reference docs:
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `docs/tasks/media-folders-close-task.md`
  - `docs/00-governance/risk-register.md`
- Acceptance:
  - Admin-only and feature-flag-gated (`bw_core_flags['media_folders']`)
  - No physical file path/URL mutation
  - Grid/list filtering stable with fail-open guards
  - Folder CRUD/meta + assignment protected by nonce/capability/context checks
  - Runtime remains no-op when module flag is off

### Coming Soon Hardening
- Status: Backlog
- Risk classification: Medium
- Short description: Harden Coming Soon mode behavior across routing, visibility controls, and logged-in bypass logic.
- Reference docs:
  - `docs/20-development/admin-panel-map.md`
  - `docs/50-ops/admin-panel-reality-audit.md`
- Acceptance:
  - Visibility rules are deterministic for guest vs authenticated users
  - No accidental commerce lockout occurs
  - Toggle propagation from admin to runtime is verified
  - Edge routing cases are documented
  - Regression checks pass on critical surfaces

### Header Performance Pass
- Status: Backlog
- Risk classification: High
- Short description: Validate and harden header runtime performance under smart scroll, responsive overlays, and global listener load.
- Reference docs:
  - `docs/50-ops/audits/header-system-technical-audit.md`
  - `docs/30-features/header/header-module-spec.md`
  - `docs/30-features/header/header-scroll-state-machine-spec.md`
  - `docs/30-features/header/header-responsive-contract.md`
- Acceptance:
  - Scroll and resize behavior remains deterministic
  - Listener binding remains bounded and non-duplicative
  - Responsive and off-canvas flows remain stable
  - No commerce-blocking regression introduced by header layer
  - Findings and measurements are documented

### Header Admin Simplification
- Status: Backlog
- Risk classification: Medium
- Short description: Simplify header admin configuration UX while preserving existing runtime contracts and precedence rules.
- Reference docs:
  - `docs/30-features/header/header-admin-settings-map.md`
  - `docs/30-features/header/header-module-spec.md`
- Acceptance:
  - No setting behavior changes relative to current runtime contract
  - General vs Smart Scroll precedence remains intact
  - Mobile override behavior remains intact
  - Settings storage/sanitization contract remains unchanged
  - Admin flow is documented after simplification

### Search System — vNext Implementation (Filters + Index + Cache)
- Status: Backlog
- Risk classification: Medium
- Short description: Implement Search vNext according to the approved runtime/dataflow contracts, including deterministic query behavior, full filter model, initials indexing, cache layer, and observability.
- Reference docs:
  - `docs/30-features/search/search-vnext-spec.md`
  - `docs/30-features/search/search-vnext-dataflow.md`
- Includes:
  - Deterministic query contract
  - Full filter coverage
  - Initial letter indexing
  - Server-side caching layer
  - Structured observability
  - Deterministic sorting and pagination
- Acceptance:
  - Response envelope matches spec
  - Cache layer functional with measurable hit ratio
  - Initials index deterministic and convergent
  - No mutation of commerce authority
  - Regression journeys pass

## Tier 2 – Refactor & Cleanup

### Blackwork Site Admin Hardening Program (Post Shopify rollout)
- Status: Backlog
- Risk classification: Medium
- Short description: Incremental admin-only hardening focused on enqueue scope optimization, security consistency, and maintainability deduplication for the Blackwork Site panel.
- Reference docs:
  - `docs/tasks/BW-TASK-20260305-08-admin-panel-architecture-audit.md`
  - `docs/20-development/admin-panel-map.md`
  - `docs/20-development/admin-ui-guidelines.md`
  - `docs/00-governance/risk-register.md`
- Acceptance:
  - Page/tab-specific enqueue matrix implemented for Blackwork admin assets
  - No save flow or option-key behavior changes across migrated pages
  - WP list-table mechanics preserved for All Templates
  - `.bw-admin-root` scope and no-bleed guarantees preserved
  - Regression checklist executed for all panel surfaces

### Supabase Bridge Anonymous Scope Tightening
- Status: Backlog
- Risk classification: Medium
- Short description: Reduce anonymous-page runtime overhead by tightening `bw_mew_enqueue_supabase_bridge()` load scope to contexts that actually require invite/callback token handling.
- Reference docs:
  - `docs/00-governance/risk-register.md`
  - `docs/50-ops/audits/my-account-domain-audit.md`
- Acceptance:
  - Bridge script no longer enqueues on unrelated anonymous pages
  - Invite/callback/auth convergence flows remain functional
  - No regression in anonymous account entry paths
  - Runtime behavior remains deterministic under repeated page loads

### Governance Traceability Cleanup (Risk ID Uniqueness)
- Status: Backlog
- Risk classification: Medium
- Short description: Enforce periodic uniqueness/consistency checks for governance identifiers (risk IDs, decision entries, cross-doc references) to prevent traceability drift.
- Reference docs:
  - `docs/00-governance/risk-register.md`
  - `docs/00-planning/decision-log.md`
- Acceptance:
  - Risk IDs are unique and sequentially coherent per domain family
  - Duplicate/conflicting IDs are eliminated and references normalized
  - Decision-log numbering and references remain unambiguous
  - Governance docs pass cross-reference validation checklist

### Legacy Option Cleanup Migration

Status:
Backlog

Risk classification:
Low

Description:
The function `bw_cleanup_account_description_option()` runs on every
`init` and performs repeated `get_option()` checks for legacy options.

File:
blackwork-core-plugin.php

The routine should be converted into a one-time migration step
executed during plugin upgrade instead of running on every request.

Reason:
Improve bootstrap hygiene and avoid unnecessary option lookups.

### PHPCS Legacy Baseline Reduction Program
- Status: Backlog
- Risk classification: Low
- Short description: Incrementally reduce historical PHPCS violations in `blackwork-core-plugin.php` while keeping `lint:main` green for active development.
- Reference docs:
  - `docs/20-development/linting-and-phpcs-baseline.md`
  - `phpcs.xml.dist`
- Acceptance:
  - `lint:main` passes for all non-legacy paths
  - `lint:legacy` trend shows decreasing violation count over time
  - baseline scope remains limited to approved legacy file(s) only
  - baseline exclusion removed once legacy file is compliant

### Elementor Widgets Cleanup
- Status: Backlog
- Risk classification: Low
- Short description: Inventory and clean unused or legacy widget surfaces under controlled non-breaking constraints.
- Reference docs:
  - `docs/10-architecture/blackwork-technical-documentation.md`
  - `docs/00-governance/blast-radius-consolidation-map.md`
- Acceptance:
  - Widget inventory is complete and classified
  - Cleanup scope excludes Tier 0 authority-sensitive paths unless explicitly gated
  - Removed/deprecated surfaces are documented
  - Regression checks cover affected UI surfaces
  - No unresolved runtime references remain

### Plugin Rename (BW -> Blackwork Core)
- Status: Backlog
- Risk classification: Medium
- Short description: Define and execute naming migration strategy across docs, code identifiers, and runtime-facing labels.
- Reference docs:
  - `docs/00-governance/system-normative-charter.md`
  - `docs/00-governance/docs-code-alignment-status.md`
- Acceptance:
  - Migration map exists for identifiers and user-facing names
  - Backward compatibility constraints are explicit
  - Rollout sequencing and rollback conditions are documented
  - Documentation references are normalized
  - No functional drift introduced by rename-only changes

## Tier 3 – Final Stabilization Layer

### Woo Loading -> Skeleton System
- Status: Deferred (Final Phase Only)
- Risk classification: Medium
- Short description: Skeleton Loading system MUST be implemented only after runtime surfaces are frozen and validated as stable.
- Reference docs:
  - `docs/50-ops/regression-coverage-map.md`
  - `docs/50-ops/regression-protocol.md`
  - `docs/30-features/redirect/redirect-engine-v2-spec.md`
  - `docs/30-features/import-products/import-products-vnext-spec.md`
- Gating prerequisites:
  - Cart flow is stable
  - Checkout flow is stable
  - Header state machine is frozen
  - Redirect Engine v2 implemented
  - Import Engine v2 implemented
- Rule:
  - No skeleton implementation allowed before runtime surfaces are frozen.
- Acceptance:
  - No interference with Woo AJAX
  - No race conditions with cart updates
  - Deterministic loading states
  - No layout shift regressions


---

## Source: `docs/00-planning/decision-log.md`

# Blackwork Core – Decision Log

Purpose:
Track architectural and governance decisions that alter roadmap direction.
This log is planning-oriented and MUST NOT duplicate ADR content.
If a decision is normative and architecture-binding, the ADR process MUST be used.

## Entry Template

- Date:
- Decision summary:
- Affected domain:
- Rationale:
- Risk impact:
- Follow-up actions:

## Entries

### Entry 001
- Date: 2026-02-27
- Decision summary: Introduced a dedicated governance planning layer under `docs/00-planning/` to track evolution priorities and decision-driven roadmap shifts.
- Affected domain: Governance / Planning
- Rationale: The system reached multi-domain maturity and requires persistent planning artifacts separated from ADR normative contracts.
- Risk impact: Low
- Follow-up actions:
  - Keep `core-evolution-plan.md` synchronized with current governance priorities.
  - Register any roadmap direction change in this log.
  - Promote architecture-binding decisions to ADR, not to this file.

### Entry 002
- Date: 2026-02-27
- Decision summary: SKU selected as the canonical unique key for the Import Domain in the Import Products vNext specification.
- Affected domain: Data Import / Product Identity
- Rationale: Convergence and idempotency model requires one immutable canonical key to prevent duplicate product identity paths. Woo-native uniqueness ensures deterministic bulk import behavior.
- Risk impact: High
- Follow-up actions:
  - Keep importer implementation backlog aligned with SKU-only identity rule.
  - Reject run configurations that do not provide SKU per row.
  - Validate deterministic duplicate-SKU failure behavior in regression runs.
  - Implement Import Engine v2 per `docs/30-features/import-products/import-products-vnext-spec.md`.

### Entry 003
- Date: 2026-03-02
- Decision summary: Theme Builder Lite `bw_template` remains non-public in site navigation but is intentionally previewable for Elementor via controlled singular rendering and noindex policy.
- Affected domain: Theme Builder Lite / Elementor Integration / Runtime Isolation
- Rationale: Elementor editor requires a valid frontend preview response (HTTP 200 + WP head/footer hooks). A controlled preview path resolves editor bootstrap failures while preserving SEO/privacy constraints.
- Risk impact: Medium reduced to Low for Phase 1 Footer Override editor stability.
- Follow-up actions:
  - Keep admin assets scoped to Theme Builder Lite settings page only.
  - Preserve preview guards that bypass footer override during Elementor editor/preview and `bw_template` singular requests.
  - Re-evaluate this contract if future template types introduce public routing requirements.

### Entry 004
- Date: 2026-03-02
- Decision summary: Theme Builder Lite Custom Fonts Elementor integration uses deferred bootstrap (`plugins_loaded` immediate check + `elementor/loaded` fallback) with idempotent registration guard.
- Affected domain: Theme Builder Lite / Custom Fonts / Elementor Integration
- Rationale: Elementor may emit `elementor/loaded` before dependent plugin hook registration depending on load order; deferred bootstrap guarantees deterministic registration without duplicate hooks.
- Risk impact: Medium reduced to Low-Medium for runtime registration timing failures.
- Follow-up actions:
  - Keep hook surfaces limited to `elementor/fonts/groups`, `elementor/fonts/additional_fonts`, and editor/preview style enqueue hooks.
  - Revalidate integration filters when Elementor major versions change.
  - Maintain fail-open behavior if Elementor is unavailable or filters are altered upstream.

### Entry 005
- Date: 2026-03-03
- Decision summary: Abandoned `bw_template` Quick Edit conditions UX and moved Single Product category conditions to Theme Builder Lite settings tab as the authoritative admin surface.
- Affected domain: Theme Builder Lite / Admin Configuration / Runtime Resolver
- Rationale: Quick Edit DOM lifecycle produced unstable persistence/restore behavior and risked interference with core inline edit fields; settings-based storage provides deterministic saves and clearer ownership.
- Risk impact: Medium reduced to Low-Medium for admin edit stability; introduces managed dual-surface legacy-data monitoring.
- Follow-up actions:
  - Keep `bw_theme_builder_lite_single_product_v1` as source of truth when enabled.
  - Preserve fail-open behavior and Woo endpoint bypass invariants.
  - Plan a separate migration/cleanup task for legacy `bw_tbl_display_rules_v1` single-product rules.

### Entry 006
- Date: 2026-03-03
- Decision summary: Finalized Single Product conditions authority in Settings with repeater snapshot `bw_theme_builder_lite_single_product_rules_v2`; Quick Edit is not an editable authority surface.
- Affected domain: Theme Builder Lite / Admin Configuration / Runtime Resolver
- Rationale: Settings-based authority is deterministic and avoids Quick Edit lifecycle instability; repeater enables explicit ordered rule evaluation.
- Risk impact: Medium reduced to Low-Medium for persistence reliability; introduces managed dual-surface compatibility monitoring.
- Follow-up actions:
  - Keep v1 option as legacy fallback only when v2 is absent.
  - Keep runtime fail-open and Woo bypass guards unchanged.
  - Schedule explicit migration cleanup task for legacy meta/option convergence.

### Entry 007
- Date: 2026-03-03
- Decision summary: Enforced parent-only product category selection in admin UI while preserving ancestor-aware runtime matching for products assigned to child categories.
- Affected domain: Theme Builder Lite / Woo Single Product Conditions
- Rationale: Reduces operator clutter and ambiguity while preserving practical matching behavior across parent-child taxonomy assignments.
- Risk impact: Medium reduced to Low-Medium for operator misconfiguration and rule drift.
- Follow-up actions:
  - Keep sanitize hardening for parent-only IDs.
  - Keep runtime ancestor expansion for single-product category matching.

### Entry 008
- Date: 2026-03-03
- Decision summary: Locked deterministic first-match rule evaluation (top-to-bottom) for single-product settings-driven resolver.
- Affected domain: Theme Builder Lite / Runtime Determinism
- Rationale: First-match semantics are explainable and stable; avoids tie ambiguity in settings-driven rule lists.
- Risk impact: Medium (precedence misunderstanding) managed through explicit UI/docs contract.
- Follow-up actions:
  - Keep precedence contract visible in docs and admin copy.
  - Add future diagnostics surface to show matched rule.

### Entry 009
- Date: 2026-03-03
- Decision summary: Added list-table inline type dropdown mutation with linkage-protection prompt and safe unlink behavior.
- Affected domain: Theme Builder Lite / Admin List UX / Data Integrity
- Rationale: Improves operator velocity while protecting linked templates from accidental silent breakage.
- Risk impact: Medium (unlink integrity) mitigated by nonce/capability/enum validation and explicit `Not linked` feedback.
- Follow-up actions:
  - Keep confirmation prompt for linked templates.
  - Keep rejection path for invalid/unauthorized mutations.
  - Keep badge truth reflection synchronized with settings state.

### Entry 010
- Date: 2026-03-03
- Decision summary: Implemented Product Archive conditions as Settings authority (`Product Archive` tab) using repeater snapshot `bw_theme_builder_lite_product_archive_rules_v2`; Quick Edit is not an authority surface.
- Affected domain: Theme Builder Lite / Woo Product Archive / Admin Configuration
- Rationale: Product-archive template routing requires deterministic, stable persistence; settings authority avoids inline-edit instability and keeps archive routing contract explicit.
- Risk impact: Medium managed through strict sanitize/validation and fail-open resolver behavior.
- Follow-up actions:
  - Keep parent-only product category UI in settings.
  - Keep ancestor-aware runtime matching so parent selections match child category archives.
  - Keep deterministic first-match evaluation (top-to-bottom, exclude-first, include-empty=match-all).
  - Keep template validation strict (`publish` + `bw_template_type=product_archive`) for runtime integrity.

### Entry 011
- Date: 2026-03-03
- Decision summary: Restored explicit `Site Settings` submenu under `Blackwork Site` using the same slug (`blackwork-site-settings`) to keep the unified settings router as stable admin entrypoint.
- Affected domain: Admin Navigation / Site Settings
- Rationale: Without explicit submenu alias, top-level navigation could default to the first child module (`All Templates`), hiding the expected multi-tab settings surface (Checkout, Supabase, Coming Soon, Redirect, Import, Loading).
- Risk impact: Low reduced to Very Low for admin navigation regression.
- Follow-up actions:
  - Keep `blackwork-site-settings` as canonical parent slug for module submenus.
  - Keep Site Settings submenu visible as first child to preserve predictable landing behavior.

### Entry 012
- Date: 2026-03-03
- Decision summary: Added Theme Builder Lite `Import Template` settings tab to import Elementor JSON into `bw_template` with deterministic type mapping and Elementor meta persistence.
- Affected domain: Theme Builder Lite / Admin Settings / Elementor Integration / Data Import
- Rationale: Manual template recreation is error-prone; controlled JSON import accelerates setup while preserving authority boundaries and fail-open behavior.
- Risk impact: Medium managed through strict file/type/capability validation and no-partial-create rollback on failure.
- Follow-up actions:
  - Keep importer scoped to admin settings authority (no Quick Edit/import side channels).
  - Keep JSON validation strict and type mapping explicit.
  - Track unsupported Elementor Pro widgets as non-blocking compatibility limitation in docs.

### Entry 013
- Date: 2026-03-03
- Decision summary: Finalized Import Template behavior as auto-detect-only authority with safe fallback type and Elementor Library mirror creation for popup discoverability.
- Affected domain: Theme Builder Lite / Admin Settings / Data Import / Elementor Integration
- Rationale: Import flow must remain deterministic and operator-safe without manual override ambiguity, while ensuring imported templates are visible in Elementor library insert workflow.
- Risk impact: Medium reduced to Low-Medium for operator friction; import compatibility drift remains monitored.
- Follow-up actions:
  - Keep manual type override removed from Import tab UI.
  - Keep fallback behavior explicit in admin notice (`single_page` fallback when type is not mappable).
  - Keep mirror `elementor_library` creation transactional with rollback on failure.
  - Preserve `bw_tbl_imported` traceability marker for filters/audits.

### Entry 014
- Date: 2026-03-03
- Decision summary: Standardized all BW product-dependent widgets on shared resolver `bw_tbl_resolve_product_context_id()` and banned direct `$_GET['elementor-preview']` checks in widget render paths.
- Affected domain: Theme Builder Lite Preview Runtime / BW Widgets Product Context
- Rationale: Elementor preview iframe does not reliably expose `elementor-preview` at widget render time; deterministic bridge (`global` + `query_var`) is required to prevent context misses and editor regressions.
- Risk impact: Medium reduced to Low-Medium via single-source resolver and bridge-first precedence.
- Follow-up actions:
  - Keep preview bridge authoritative (`$GLOBALS['bw_tbl_preview_product_id']`, `set_query_var`).
  - Keep resolver precedence fixed (`real_context -> preview_fallback -> manual_setting -> missing`).
  - Keep `BW_TBL_DEBUG_PREVIEW` scoped to bridge+resolver logs only (no per-widget debug noise).
  - Validate per-widget server log evidence on the target environment during audit closure.

### Entry 015
- Date: 2026-03-04
- Decision summary: Completed Media Folders admin module as isolated `upload.php` surface with taxonomy-backed virtual folders, guarded grid/list query filtering, marker/badge UX, and quick type filters.
- Affected domain: Media Library Admin / Media Folders
- Rationale: Media operations needed deterministic folder organization and assignment workflows without modifying file paths, frontend media behavior, or non-media runtime domains.
- Risk impact: Medium (admin DOM coupling and observer/event complexity), mitigated by strict screen guards, fail-open query filters, capability/nonce/context validation, and coalesced refresh scheduling.
- Follow-up actions:
  - Keep module gated by `bw_core_flags['media_folders']`.
  - Revalidate toolbar/tablenav selector contracts on WordPress major admin UI changes.
  - Monitor marker + quick-filter observer behavior on very large libraries.
  - Keep closure reference synchronized in `docs/tasks/media-folders-close-task.md`.

### Entry 016
- Date: 2026-03-05
- Decision summary: Enforced `Site Settings` as deterministic landing page for top-level `Blackwork Site` and introduced a dedicated admin-only `Status` diagnostics module with on-demand, cached, read-only health checks.
- Affected domain: Admin Navigation / Diagnostics / Governance
- Rationale: Preserve predictable admin entrypoint behavior while adding a safe observability surface that does not run heavy scans during normal admin page loads.
- Risk impact: Low-Medium (AJAX diagnostics endpoint + large-library scan pressure) managed by capability/nonce gates and transient caching.
- Follow-up actions:
  - Keep diagnostics checks read-only and capability-gated.
  - Keep transient-cached snapshots to avoid repeated heavy scans on reload.
  - Reassess storage/DB query strategy if large-site scan latency increases.

### Entry 017
- Date: 2026-03-05
- Decision summary: Adopted a Shopify-style admin dashboard architecture for `Blackwork Site > Status`, using metric-first cards, section-scoped actions, and demand-only technical details.
- Affected domain: Admin UX / System Status Diagnostics
- Rationale: Previous diagnostics presentation was too developer-centric; owner/admin operation requires rapid interpretation of health indicators with minimal technical noise while keeping deep data available on demand.
- Risk impact: Low-Medium (UI complexity growth) mitigated by preserving existing module boundaries, immutable read-only runtime checks, and stable AJAX response contracts.
- Follow-up actions:
  - Keep overview + card UX consistent with status payload contract (`status`, `summary`, `metrics`, `warnings`).
  - Keep per-section scopes (`media`, `images`, `database`, `wordpress`, `limits`, `image_sizes_counts`) stable for maintainability.
  - Keep debug details collapsed by default and avoid expanding technical output in primary UX.

### Entry 018
- Date: 2026-03-05
- Decision summary: Introduced a reusable Blackwork Admin UI Kit (`admin/css/bw-admin-ui-kit.css`) and applied it to `Blackwork Site > Media Folders` settings page to standardize panel UX language.
- Affected domain: Admin UX / Blackwork Site Settings Surfaces
- Rationale: Status established a clearer Shopify-like visual model; a shared lightweight UI kit reduces duplicated CSS and keeps future panel pages visually consistent without altering feature behavior.
- Risk impact: Low (UI-only change), controlled by scoped CSS root and Blackwork-page-only enqueue strategy.
- Follow-up actions:
  - Keep UI Kit style scope under `.bw-admin-root` to avoid admin-wide bleed.
  - Reuse the same primitives (header/action bar/card/field/table) on future Blackwork admin pages.
  - Preserve semantic form controls and focus visibility in all UI Kit adoptions.

### Entry 019
- Date: 2026-03-05
- Decision summary: Applied the shared Blackwork Admin UI Kit to `Blackwork Site > Site Settings` main router page, including header, action bar, and card-based tab/content containers with a non-invasive save-proxy CTA.
- Affected domain: Admin UX / Site Settings Router
- Rationale: Site Settings was the main remaining panel surface with inconsistent visual language; aligning it with Status/Media Folders improves consistency while preserving existing tab-specific save logic.
- Risk impact: Low (UI-only), mitigated by preserving existing nonce/POST handlers and forwarding save action to existing submit controls.
- Follow-up actions:
  - Keep save-proxy mapped to existing tab submit names only (no new save flow).
  - Keep tab content renderers unchanged in behavior.
  - Continue progressive UI kit adoption to other Blackwork admin surfaces without runtime logic changes.

### Entry 020
- Date: 2026-03-05
- Decision summary: Closed Shopify-style Admin UI rollout as a panel-wide standard across all target Blackwork Site admin surfaces using a shared, scoped UI Kit.
- Affected domain: Admin UX / Governance / Blackwork Site Panel
- Rationale: Unified visual language reduces operator friction, accelerates support/training, and enables future admin pages to reuse a stable layout contract without behavior rewrites.
- Risk impact: Low (UI-only standardization) with controlled residual risk on CSS bleed via strict root scoping and guarded enqueue surfaces.
- Follow-up actions:
  - Keep `.bw-admin-root` as mandatory root contract for Blackwork UI kit pages.
  - Keep WP-native mechanics untouched on list-table surfaces (search, filters, bulk, sorting, pagination).
  - Require new admin pages to follow `docs/20-development/admin-ui-guidelines.md`.

### Entry 021
- Date: 2026-03-05
- Decision summary: Extended Media Folders from Media-only scope to a flag-controlled multi-post-type admin list-table contract (Media/Posts/Pages/Products), while keeping media-specific marker/type-filter UX bound to Media Library only.
- Affected domain: Media Folders / Admin List Tables / Settings UX
- Rationale: Folder organization workflows are needed for editorial/admin list tables beyond attachments; controlled post-type flags preserve isolation and deterministic rollout.
- Risk impact: Medium (admin list-table DOM coupling + broader query filter surface) mitigated by strict screen/post-type guards, fail-open filtering, and unchanged nonce/capability contracts.
- Follow-up actions:
  - Keep master no-op behavior (`media_folders=0`) as immediate rollback path.
  - Keep media-grid (`ajax_query_attachments_args`) behavior attachment-only.
  - Revalidate selectors/placement contracts on WordPress admin UI updates.

### Entry 022
- Date: 2026-03-05
- Decision summary: Enforced strict folder isolation per content type using dedicated taxonomies and constrained list-table drag UX for Posts/Pages/Products to handle-only single-item assignment.
- Affected domain: Media Folders / Admin List Tables / Data Isolation
- Rationale: Cross-content folder sharing created ambiguity and contamination risk; separate taxonomy truth surfaces guarantee deterministic isolation and clearer operator intent.
- Risk impact: Medium, mitigated by deterministic post_type->taxonomy mapping, unchanged nonce/capability gates, and fail-open guards on unsupported contexts.
- Follow-up actions:
  - Keep taxonomy resolver as single source of truth.
  - Keep list-table drag source restricted to handle column.
  - Keep product list-table drag column anchored before `name` (fallback `title`, then `cb`) with no append fallback.

### Entry 023
- Date: 2026-03-05
- Decision summary: Adopted taxonomy-context cache hardening for Media Folders counts/tree/summary with deterministic invalidation and post-batch assignment invalidation.
- Affected domain: Media Folders / Admin Performance / Runtime Determinism
- Rationale: Multi-post-type folder runtime on large datasets requires eliminating repeated hot-path count queries and invalidation storms while preserving existing UX contracts.
- Risk impact: Medium reduced to Low-Medium for large-library latency and repeated AJAX refresh pressure.
- Follow-up actions:
  - Keep cache keys scoped by taxonomy + post_type context.
  - Keep one invalidation per assignment operation (suspend per-item invalidation during batch updates).
  - Keep list-table query filters fail-open unless folder params are explicitly present.

### Entry 024
- Date: 2026-03-06
- Decision summary: Hardened Media Folders query mutation contract to strict screen-only + main-query-only + fail-open guards for list/grid filters.
- Affected domain: Media Folders / Admin Runtime / Query Safety
- Rationale: Prevent unintended mutation of secondary/admin-adjacent queries (quick edit/modal/ajax/rest/cron paths) while preserving existing filtering behavior on intended screens.
- Risk impact: Medium reduced to Low-Medium for unintended query side effects.
- Follow-up actions:
  - Keep shared guard helpers as the single source for list/grid query eligibility.
  - Keep strict folder payload normalization (`absint` + strict `'1'` for unassigned).
  - Keep no-op behavior when filter params are absent or invalid.

### Entry 025
- Date: 2026-03-06
- Decision summary: Standardized folder context menu action `New Subfolder` across Media/Posts/Pages/Products by reusing existing folder-create endpoint with explicit parent-in-same-context validation.
- Affected domain: Media Folders / Admin UX / Taxonomy Isolation
- Rationale: Subfolder creation must be context-consistent and isolated without introducing new endpoints or divergent create flows.
- Risk impact: Low-Medium, mitigated by existing nonce/capability gates and taxonomy-scoped parent validation.
- Follow-up actions:
  - Keep root `New Folder` behavior unchanged (parent `0` only from global button).
  - Keep parent validation tied to resolved taxonomy context.
  - Keep tree refresh path single-flow (`refreshTree`) after create/subcreate.
  - Keep media bulk assignment as media-only behavior.

### Entry 026
- Date: 2026-03-05
- Decision summary: Opened a phased Blackwork Site admin hardening program after Shopify rollout audit, prioritizing enqueue-scope correctness and modular maintainability without changing runtime/storefront behavior.
- Affected domain: Admin Architecture / Governance / Performance
- Rationale: UI unification is complete, but audit evidence shows residual technical debt in broad asset loading and monolithic admin controller structure; incremental hardening is needed to keep the panel stable and scalable.
- Risk impact: Medium (admin regressions during refactor) mitigated by phased rollout, strict invariants, and regression gates.
- Follow-up actions:
  - Execute P1 enqueue tightening first (`bw_site_settings_admin_assets` page/tab matrix).
  - Keep all refactors UI/admin-only unless separately approved at governance level.
  - Prepare ADR before any large decomposition of `admin/class-blackwork-site-settings.php`.

### Entry 027
- Date: 2026-03-06
- Decision summary: Established import authority hardening baseline with a single active run lock, durable run state, deterministic row outcome ledger, and SKU conflict-safe convergence.
- Affected domain: Import Engine / Catalog Authority / Convergence
- Rationale: Import runtime is a Tier-0 catalog mutator surface; transient-only progress and lockless execution created duplicate/partial-write risk under re-entry, retries, and overlapping operator actions.
- Risk impact: Critical reduced to High through run-level lock ownership, stale lock reclaim policy, durable checkpoint authority, and deterministic row/sku convergence guards.
- Follow-up actions:
  - Keep `bw_import_run_lock` as the sole runtime lock authority (no parallel lock surfaces).
  - Keep durable run options (`bw_import_run_{run_id}` and `bw_import_active_run`) authoritative for resume state; transient remains UI mirror only.
  - Keep row terminal outcome model (`created|updated|skipped|failed`) idempotent and non-double-counting by deterministic row identity.
  - Keep publish status allowlist enforcement (`draft|publish|pending|private`) with invalid-value normalization to `draft`.
  - Reassess need for dedicated import-run table if run-history retention and telemetry requirements outgrow option-backed storage.

## Governance Layer Closure

Status: CLOSED  
Date: 2026-02-27  
Notes:
Governance structure finalized after structural review and normalization.
Further changes require ADR escalation.

## Search AS-IS Documentation Closure

Status: CLOSED
Date: 2026-02-27
Notes:
Search runtime documented completely.
Initial letter indexing not present in current implementation.
Enhancements will be defined under Search vNext spec.

## Search Architecture Documentation Closure

Status: CLOSED
Date: 2026-02-27
Notes:
Search runtime fully documented (AS-IS).
Search vNext architecture defined and approved.
Implementation scheduled in roadmap backlog.

## Template Hardening — Governance Upgrade

Status: CLOSED
Notes:
Start and Close templates updated to include invariant protection layer.
No authority drift.
No Tier reclassification.
Governance strengthened without increasing procedural weight.

## Plugin Identity Rename — Metadata Update

Status: CLOSED
Date: 2026-02-27
Notes:
Plugin display identity updated from BW Elementor Widgets to Blackwork Core Plugin.
Metadata-only change (name, description, version, authors) with no runtime authority mutation.

## Bootstrap Filename Migration

Status: CLOSED
Date: 2026-02-27
Notes:
Bootstrap file renamed to `blackwork-core-plugin.php` from the previous bootstrap filename.
Runtime references and tooling script paths were aligned.
Plugin slug, text-domain, internal prefixes, and runtime authority model remain unchanged.


---

## Source: `docs/10-architecture/README.md`

# 10 Architecture

Core architecture and canonical technical references.

## Files
- [blackwork-technical-documentation.md](blackwork-technical-documentation.md): primary architecture and system reference.
- [elementor-widget-architecture-context.md](elementor-widget-architecture-context.md): architecture context for Elementor widget bootstrap and contracts.
- [../30-features/system-status/README.md](../30-features/system-status/README.md): architecture and contracts for the admin-only System Status diagnostics module.


---

## Source: `docs/10-architecture/blackwork-technical-documentation.md`

# BlackWork Site - Complete Technical Documentation

**Version:** 1.2
**Last Updated:** 2026-03-05
**Plugin Path:** `wp-content/plugins/wpblackwork/`

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Site Settings Backend](#2-site-settings-backend)
3. [Supabase Authentication System](#3-supabase-authentication-system)
4. [Authentication Methods](#4-authentication-methods)
5. [WooCommerce Checkout System](#5-woocommerce-checkout-system)
6. [Stripe Payment Gateway](#6-stripe-payment-gateway)
7. [My Account System](#7-my-account-system)
8. [Configuration Options Reference](#8-configuration-options-reference)
9. [AJAX Endpoints Reference](#9-ajax-endpoints-reference)
10. [Implementation Guide for New Features](#10-implementation-guide-for-new-features)
11. [Data Flow Diagrams](#11-data-flow-diagrams)
12. [Security Considerations](#12-security-considerations)

---

## Executive Architecture Summary

`wpblackwork` is a modular WordPress plugin for BlackWork, with three primary pillars:
- Elementor widgets (auto-loaded by `includes/class-bw-widget-loader.php`)
- WooCommerce customizations (checkout, my-account, product-type extensions)
- Shared site modules (`admin/`, `cart-popup/`, `BW_coming_soon/`, `includes/modules/system-status/`)

Core implementation principles:
- Keep WooCommerce core flows intact, then layer custom UX and integrations.
- Centralize settings and runtime contracts to minimize regressions.
- Use module-level assets and hooks to avoid global side effects.

---

## 1. Architecture Overview

### 1.1 Plugin Structure

```
wpblackwork/
├── admin/
│   ├── class-blackwork-site-settings.php    # Main settings page (6000+ lines)
│   ├── css/
│   │   ├── blackwork-site-settings.css      # Admin styles
│   │   └── blackwork-site-menu.css          # Menu styles
│   └── js/
│       ├── bw-redirects.js                  # Redirect manager
│       └── bw-checkout-subscribe.js         # Brevo test connection
├── assets/
│   ├── css/
│   │   ├── bw-account-page.css              # Login page styles
│   │   ├── bw-my-account.css                # Dashboard styles
│   │   ├── bw-checkout.css                  # Checkout styles (97KB)
│   │   ├── bw-payment-methods.css           # Payment accordion
│   │   └── bw-checkout-notices.css          # Checkout notices
│   └── js/
│       ├── bw-account-page.js               # Login page handler (1367 lines)
│       ├── bw-my-account.js                 # Dashboard handler (549 lines)
│       ├── bw-supabase-bridge.js            # OAuth token bridge (387 lines)
│       ├── bw-checkout.js                   # Checkout handler (99KB)
│       ├── bw-payment-methods.js            # Payment accordion (310 lines)
│       ├── bw-google-pay.js                 # Google Pay handler
│       └── bw-stripe-upe-cleaner.js         # Stripe UPE customization
├── includes/
│   ├── woocommerce-overrides/
│   │   ├── class-bw-supabase-auth.php       # Supabase auth (1479 lines)
│   │   ├── class-bw-social-login.php        # OAuth provider (548 lines)
│   │   ├── class-bw-my-account.php          # My Account logic (421 lines)
│   │   ├── class-bw-google-pay-gateway.php  # Google Pay gateway
│   │   └── class-bw-product-card-renderer.php
│   ├── admin/
│   │   ├── checkout-fields/
│   │   │   ├── class-bw-checkout-fields-admin.php
│   │   │   └── class-bw-checkout-fields-frontend.php
│   │   └── checkout-subscribe/
│   │       ├── class-bw-checkout-subscribe-admin.php
│   │       └── class-bw-checkout-subscribe-frontend.php
│   ├── modules/
│   │   └── system-status/
│   │       ├── system-status-module.php
│   │       ├── admin/
│   │       │   ├── status-page.php
│   │       │   └── assets/system-status-admin.js
│   │       └── runtime/
│   │           ├── check-runner.php
│   │           └── checks/
│   │               ├── check-media.php
│   │               ├── check-database.php
│   │               ├── check-images.php
│   │               ├── check-wordpress.php
│   │               └── check-server.php
│   └── widgets/                              # Elementor widgets
├── woocommerce/
│   ├── woocommerce-init.php                 # WooCommerce customizations (1558 lines)
│   └── templates/
│       ├── checkout/
│       │   ├── form-checkout.php            # Main checkout template
│       │   ├── form-billing.php             # Billing form
│       │   ├── form-shipping.php            # Shipping form
│       │   ├── payment.php                  # Payment accordion
│       │   └── review-order.php             # Order summary
│       └── myaccount/
│           ├── my-account.php               # Account wrapper
│           ├── form-login.php               # Login page (236 lines)
│           ├── navigation.php               # Dashboard nav
│           ├── dashboard.php                # Dashboard content
│           ├── form-edit-account.php        # Settings tabs
│           └── set-password.php             # Onboarding password
├── cart-popup/                              # Cart popup submodule
├── BW_coming_soon/                          # Coming soon submodule
└── blackwork-core-plugin.php            # Main plugin file
```

### 1.2 Core Dependencies

| Dependency | Purpose |
|------------|---------|
| WordPress | Core CMS |
| WooCommerce | E-commerce functionality |
| Elementor | Page builder integration |
| Supabase JS SDK | Authentication client |
| Stripe JS | Payment processing |
| Google Maps API | Address autocomplete |
| Brevo API | Newsletter subscription |

### 1.3 Initialization Flow

```
plugins_loaded (priority 5)
    ↓
bw_mew_initialize_woocommerce_overrides()
    ├─ Load class-bw-supabase-auth.php
    ├─ Load class-bw-my-account.php
    ├─ Load class-bw-social-login.php
    ├─ Load class-bw-google-pay-gateway.php
    └─ Register filters & actions
    ↓
init (priority 5)
    ├─ Register rewrite endpoints
    └─ Register widget assets
    ↓
wp_enqueue_scripts (priority 20-30)
    └─ Enqueue page-specific assets
```

---

## 2. Site Settings Backend

### 2.1 Settings Page Structure

**Main File:** `admin/class-blackwork-site-settings.php`

**Admin Menu Location:** Blackwork Site → Settings

**Top-Level Tabs:**
| Tab Key | Label | Description |
|---------|-------|-------------|
| `cart-popup` | Cart Pop-up | Side-sliding cart configuration |
| `bw-coming-soon` | BW Coming Soon | Coming soon page with video |
| `account-page` | Account Page | Login page design + Supabase settings |
| `my-account-page` | My Account Page | Dashboard customization |
| `checkout` | Checkout | Checkout layout and integrations |
| `redirect` | Redirect | URL redirect management |
| `import-product` | Import Product | Product import tools |
| `loading` | Loading | Loading screen settings |
| `google-pay` | Google Pay | Google Pay gateway config |

### 2.2 Account Page Tab (Sub-tabs)

**Design Tab:**
- Login Image (cover)
- Logo URL and dimensions
- Logo padding (top/bottom)
- Login title and subtitle
- Show social login buttons toggle

**Technical Settings Tab:**
Contains ALL Supabase configuration fields (see Section 3.3)

### 2.3 Checkout Tab (Sub-tabs)

| Sub-tab | Key | Description |
|---------|-----|-------------|
| Style | `style` | Logo, colors, widths, padding |
| Supabase Provider | `supabase` | Guest checkout provisioning |
| Checkout Fields | `fields` | Field visibility and order |
| Subscribe | `subscribe` | Brevo newsletter integration |
| Google Maps | `google-maps` | Address autocomplete |
| Footer Cleanup | `footer` | Legal text and policies |

### 2.4 Settings Save Mechanism

**Form Handling:**
```php
// Account Page save
if (isset($_POST['bw_account_page_submit'])) {
    check_admin_referer('bw_account_page_save', 'bw_account_page_nonce');
    // Process each option with appropriate sanitization
    update_option('bw_account_login_image', esc_url_raw($_POST['bw_account_login_image']));
    // ... etc
}
```

**Sanitization Functions Used:**
- `esc_url_raw()` - URLs
- `sanitize_text_field()` - Single-line text
- `sanitize_textarea_field()` - Multi-line text
- `absint()` - Integers
- `sanitize_hex_color()` - Colors

---

## 3. Supabase Authentication System

### 3.1 Core Files

| File | Purpose | Lines |
|------|---------|-------|
| `class-bw-supabase-auth.php` | Main auth handler | 1479 |
| `bw-supabase-bridge.js` | OAuth token bridge | 387 |
| `bw-account-page.js` | Login form handler | 1367 |

### 3.2 Supabase API Endpoints Used

**Public Endpoints (Anon Key):**
```
POST /auth/v1/token?grant_type=password     # Password login
POST /auth/v1/otp                            # Request OTP
POST /auth/v1/verify                         # Verify OTP
GET  /auth/v1/user                           # Get user profile
PUT  /auth/v1/user                           # Update user profile
POST /auth/v1/token?grant_type=pkce          # PKCE code exchange
```

**Admin Endpoints (Service Role Key):**
```
POST /auth/v1/admin/invite                   # Send user invite
GET  /auth/v1/admin/users                    # List users
```

### 3.3 Configuration Options (wp_options)

**Core Connection:**
```php
bw_supabase_project_url       // Supabase project URL (e.g., https://xxx.supabase.co)
bw_supabase_anon_key          // Anon/public key for frontend
bw_supabase_service_role_key  // Service role key for admin operations
```

**Authentication Modes:**
```php
bw_supabase_auth_mode              // 'password', 'otp', 'magic_link'
bw_supabase_login_mode             // 'native' or 'iframe'
bw_supabase_registration_mode      // 'R2', 'R1', 'R0'
bw_supabase_with_plugins           // Enable OIDC plugin integration
```

**Feature Toggles:**
```php
bw_supabase_magic_link_enabled       // Enable magic link/OTP
bw_supabase_login_password_enabled   // Enable password login
bw_supabase_otp_allow_signup         // Allow signup via OTP
bw_supabase_create_wp_users          // Auto-create WP users
bw_supabase_enable_wp_user_linking   // Link Supabase to WP users
```

**OAuth Provider Toggles:**
```php
bw_supabase_oauth_google_enabled     // Enable Google OAuth
bw_supabase_oauth_facebook_enabled   // Enable Facebook OAuth
bw_supabase_oauth_apple_enabled      // Enable Apple OAuth
```

**OAuth Provider Credentials:**
```php
// Google
bw_supabase_google_client_id
bw_supabase_google_client_secret
bw_supabase_google_redirect_url
bw_supabase_google_scopes
bw_supabase_google_prompt

// Facebook
bw_supabase_facebook_app_id
bw_supabase_facebook_app_secret
bw_supabase_facebook_redirect_url
bw_supabase_facebook_scopes

// Apple
bw_supabase_apple_client_id
bw_supabase_apple_team_id
bw_supabase_apple_key_id
bw_supabase_apple_private_key
bw_supabase_apple_redirect_url
```

**Redirect URLs:**
```php
bw_supabase_magic_link_redirect_url      // After OTP confirmation
bw_supabase_oauth_redirect_url           // After OAuth callback
bw_supabase_signup_redirect_url          // After signup confirmation
bw_supabase_email_confirm_redirect_url   // After email confirmation
bw_supabase_provider_signup_url          // Provider signup URL
bw_supabase_provider_reset_url           // Password reset URL
```

**Session Management:**
```php
bw_supabase_session_storage         // 'cookie' or 'usermeta'
bw_supabase_jwt_cookie_name         // Cookie base name (default: 'bw_supabase_session')
bw_supabase_auto_login_after_confirm
```

**Checkout Provisioning:**
```php
bw_supabase_checkout_provision_enabled   // Enable guest user invites
bw_supabase_invite_redirect_url          // Invite redirect (default: /my-account/set-password/)
```

**Debug:**
```php
bw_supabase_debug_log                    // Enable debug logging
```

### 3.4 Session Storage

**Cookie-Based (Default):**
```php
Cookie: {cookie_name}_access   // Access token (1 hour)
Cookie: {cookie_name}_refresh  // Refresh token (30 days)

Flags: httponly, secure (if HTTPS), SameSite=Lax
```

**UserMeta-Based:**
```php
User Meta: bw_supabase_access_token
User Meta: bw_supabase_refresh_token
User Meta: bw_supabase_expires_at
```

### 3.5 User Meta Keys

| Meta Key | Purpose |
|----------|---------|
| `bw_supabase_onboarded` | 0=needs password, 1=complete |
| `bw_supabase_invited` | 1=invited via checkout |
| `bw_supabase_pending_email` | Email change pending |
| `bw_supabase_user_id` | Supabase UUID |
| `bw_supabase_invited_at` | Invite timestamp |
| `bw_supabase_invite_resend_count` | Resend attempts |

---

## 4. Authentication Methods

### 4.1 Magic Link / OTP

**Flow Diagram:**
```
┌─────────────────┐
│  User enters    │
│     email       │
└────────┬────────┘
         ↓
┌─────────────────┐
│ Check if email  │ → AJAX: bw_supabase_email_exists
│    exists       │
└────────┬────────┘
         ↓
┌─────────────────┐
│  Request OTP    │ → Supabase: POST /auth/v1/otp
│                 │   {email, should_create_user}
└────────┬────────┘
         ↓
┌─────────────────┐
│ User receives   │
│  6-digit code   │
└────────┬────────┘
         ↓
┌─────────────────┐
│  Enter OTP in   │
│   6 inputs      │
└────────┬────────┘
         ↓
┌─────────────────┐
│  Verify OTP     │ → Supabase: POST /auth/v1/verify
│                 │   {email, token, type: 'email'}
└────────┬────────┘
         ↓
┌─────────────────┐
│ Get tokens      │ → access_token, refresh_token
└────────┬────────┘
         ↓
┌─────────────────┐
│ Bridge to WP    │ → AJAX: bw_supabase_token_login
└────────┬────────┘
         ↓
┌─────────────────┐
│ Create/link     │ → wp_set_auth_cookie()
│   WP user       │
└────────┬────────┘
         ↓
┌─────────────────┐
│   Redirect to   │
│   /my-account/  │
└─────────────────┘
```

**Key Functions:**
- `requestOtp(email, shouldCreateUser)` - JS function in bw-account-page.js
- `verifyOtp(email, code)` - JS function for code verification
- `bridgeSupabaseSession()` - Bridges Supabase tokens to WordPress

### 4.2 Password Login

**Flow Diagram:**
```
┌─────────────────┐
│ User enters     │
│ email+password  │
└────────┬────────┘
         ↓
┌─────────────────┐
│   AJAX POST     │ → bw_supabase_login
└────────┬────────┘
         ↓
┌─────────────────┐
│ Backend calls   │ → POST /auth/v1/token?grant_type=password
│   Supabase      │   {email, password}
└────────┬────────┘
         ↓
┌─────────────────┐
│ Store session   │ → bw_mew_supabase_store_session()
│ (cookie/meta)   │
└────────┬────────┘
         ↓
┌─────────────────┐
│ Set WP cookie   │ → wp_set_auth_cookie($user_id)
└────────┬────────┘
         ↓
┌─────────────────┐
│   Redirect to   │
│   /my-account/  │
└─────────────────┘
```

**Backend Handler:** `bw_mew_handle_supabase_login()` (Line 470)

### 4.3 OAuth (Google/Facebook/Apple)

**Flow Diagram:**
```
┌─────────────────┐
│ User clicks     │
│ "Continue with  │
│    Google"      │
└────────┬────────┘
         ↓
┌─────────────────┐
│ JS: signInWith  │ → supabase.auth.signInWithOAuth({
│    OAuth()      │      provider: 'google',
│                 │      options: {redirectTo: ...}
│                 │   })
└────────┬────────┘
         ↓
┌─────────────────┐
│ Redirect to     │
│ Google OAuth    │
│ consent screen  │
└────────┬────────┘
         ↓
┌─────────────────┐
│ User authorizes │
└────────┬────────┘
         ↓
┌─────────────────┐
│ Google returns  │ → ?code=XXX&state=YYY
│ auth code       │
└────────┬────────┘
         ↓
┌─────────────────┐
│ bw-supabase-    │ → Detects ?code param
│ bridge.js       │
└────────┬────────┘
         ↓
┌─────────────────┐
│ PKCE Exchange   │ → POST /auth/v1/token?grant_type=pkce
│ (or SDK)        │   OR exchangeCodeForSession(code)
└────────┬────────┘
         ↓
┌─────────────────┐
│ Bridge tokens   │ → AJAX: bw_supabase_token_login
│ to WordPress    │
└────────┬────────┘
         ↓
┌─────────────────┐
│   Redirect to   │
│   /my-account/  │
└─────────────────┘
```

**JavaScript Handler:** `bw-supabase-bridge.js` (Lines 337-383)

### 4.4 Guest Checkout Provisioning (Invite)

**Flow Diagram:**
```
┌─────────────────┐
│ Guest places    │
│ order           │
└────────┬────────┘
         ↓
┌─────────────────┐
│ Order status    │ → woocommerce_order_status_processing
│ changes         │   woocommerce_order_status_completed
└────────┬────────┘
         ↓
┌─────────────────┐
│ Check option    │ → bw_supabase_checkout_provision_enabled
└────────┬────────┘
         ↓
┌─────────────────┐
│ Create WP user  │ → Username from email prefix
│ (if needed)     │   Random 32-char password
└────────┬────────┘
         ↓
┌─────────────────┐
│ Send Supabase   │ → POST /auth/v1/admin/invite
│ invite          │   Headers: Authorization: Bearer {service_key}
│                 │   Body: {email, redirect_to, data}
└────────┬────────┘
         ↓
┌─────────────────┐
│ User receives   │ → Link contains: #access_token=...
│ email           │   &refresh_token=...&type=invite
└────────┬────────┘
         ↓
┌─────────────────┐
│ User clicks     │ → Redirected to /my-account/set-password/
│ invite link     │
└────────┬────────┘
         ↓
┌─────────────────┐
│ Extract tokens  │ → bw-supabase-bridge.js parses hash
│ from URL hash   │
└────────┬────────┘
         ↓
┌─────────────────┐
│ Bridge to WP    │ → bw_supabase_token_login with type=invite
└────────┬────────┘
         ↓
┌─────────────────┐
│ User creates    │ → AJAX: bw_supabase_update_password
│ password        │
└────────┬────────┘
         ↓
┌─────────────────┐
│ Set onboarded=1 │
│ Redirect to     │
│ /my-account/    │
└─────────────────┘
```

**Backend Handler:** `bw_mew_handle_supabase_checkout_invite()` (Line 998)

### 4.5 Password Requirements

```
- Minimum 8 characters
- At least 1 uppercase letter (A-Z)
- At least 1 number (0-9) OR special character (!@#$%, etc.)
```

**Validation Function:**
```php
// PHP (class-bw-supabase-auth.php:869)
function bw_mew_supabase_password_meets_requirements($password) {
    if (strlen($password) < 8) return false;
    if (!preg_match('/[A-Z]/', $password)) return false;
    if (!preg_match('/[0-9]|[^A-Za-z0-9]/', $password)) return false;
    return true;
}
```

```javascript
// JS (bw-account-page.js:380-407)
const rules = [
    { id: 'length', test: v => v.length >= 8 },
    { id: 'upper', test: v => /[A-Z]/.test(v) },
    { id: 'number', test: v => /[0-9]|[^A-Za-z0-9]/.test(v) }
];
```

---

## 5. WooCommerce Checkout System

### 5.1 Template Structure

**Template Override Hierarchy:**
```php
// woocommerce-init.php:104-113
add_filter('woocommerce_locate_template', 'bw_mew_locate_template', 1, 3);

function bw_mew_locate_template($template, $template_name, $template_path) {
    $plugin_path = BW_MEW_PATH . 'woocommerce/templates/';
    if (file_exists($plugin_path . $template_name)) {
        return $plugin_path . $template_name;
    }
    return $template;
}
```

**Key Templates:**

| Template | Purpose |
|----------|---------|
| `form-checkout.php` | Main two-column layout |
| `form-billing.php` | Billing fields with newsletter |
| `form-shipping.php` | Shipping address fields |
| `payment.php` | Payment methods accordion |
| `review-order.php` | Order summary with quantity controls |

### 5.2 Two-Column Layout

**CSS Variables (form-checkout.php):**
```css
--bw-checkout-left-col: 62%;           /* Left column width */
--bw-checkout-right-col: 38%;          /* Right column width */
--bw-checkout-page-bg: #ffffff;        /* Page background */
--bw-checkout-grid-bg: #ffffff;        /* Grid background */
--bw-checkout-left-bg: #ffffff;        /* Left column bg */
--bw-checkout-right-bg: transparent;   /* Right column bg */
--bw-checkout-border-color: #262626;   /* Column separator */
--bw-checkout-right-sticky-top: 20px;  /* Sticky offset */
```

**Grid Structure:**
```html
<div class="bw-checkout-wrapper">
    <div class="bw-checkout-grid">
        <div class="bw-checkout-left">
            <!-- Logo, Contact/Email, Billing, Shipping, Payment, Legal, Footer -->
        </div>
        <div class="bw-checkout-right">
            <!-- Order Summary (sticky) -->
        </div>
    </div>
</div>
```

### 5.3 Checkout Fields System

**Settings Storage:** `bw_checkout_fields_settings` option

**Structure:**
```php
[
    'version' => 1,
    'billing' => [
        'billing_first_name' => [
            'enabled' => true,
            'priority' => 10,
            'width' => 'half',  // 'half' or 'full'
            'label' => 'Custom Label',
            'required' => true
        ],
        // ... more fields
    ],
    'shipping' => [...],
    'order' => [...],
    'account' => [...],
    'section_headings' => [
        'hide_billing_details' => 0,
        'hide_additional_info' => 0,
        'address_heading_text' => 'Delivery'
    ]
]
```

**Filter:** `woocommerce_checkout_fields`

### 5.4 Newsletter Integration (Brevo)

**Settings:** `bw_checkout_subscribe_settings` option

**Fields:**
| Setting | Type | Purpose |
|---------|------|---------|
| `enabled` | bool | Show newsletter checkbox |
| `default_checked` | bool | Pre-check checkbox |
| `label_text` | string | Checkbox label |
| `privacy_text` | string | Disclaimer text |
| `api_key` | string | Brevo API key |
| `api_base` | string | API base URL |
| `list_id` | int | Brevo list ID |
| `double_optin_enabled` | bool | Send confirmation email |
| `double_optin_template_id` | int | Email template ID |
| `double_optin_redirect_url` | string | Confirmation redirect |
| `sender_name` | string | Email sender name |
| `sender_email` | string | Email sender address |
| `subscribe_timing` | string | 'created' or 'paid' |

**Subscription Hooks:**
- `woocommerce_checkout_order_processed` - When order created
- `woocommerce_order_status_processing` - When payment received
- `woocommerce_order_status_completed` - When order completed

### 5.5 Google Maps Autocomplete

**Settings:**
```php
bw_google_maps_enabled           // '0' or '1'
bw_google_maps_api_key           // Google Maps API key
bw_google_maps_autofill          // Auto-fill city/postcode
bw_google_maps_restrict_country  // Restrict to store country
```

**Script Loading:**
```php
if ($enabled && $api_key) {
    wp_enqueue_script(
        'google-maps-places',
        'https://maps.googleapis.com/maps/api/js?key=' . $api_key . '&libraries=places'
    );
}
```

### 5.6 Policy Modals

**Settings Pattern:** `bw_checkout_policy_{key}`

**Keys:** `refund`, `shipping`, `privacy`, `terms`, `contact`

**Structure per policy:**
```php
[
    'title' => 'Refund Policy',
    'subtitle' => 'Our commitment to you',
    'content' => '<p>Full HTML content...</p>'
]
```

---

## 6. Stripe Payment Gateway

### 6.1 Stripe Elements Customization

**Filter:** `wc_stripe_elements_options`

**Appearance Configuration:**
```php
[
    'theme' => 'flat',
    'variables' => [
        'colorPrimary' => '#000000',
        'colorBackground' => '#ffffff',
        'colorText' => '#1f2937',
        'colorDanger' => '#991b1b',
        'colorSuccess' => '#27ae60',
        'borderRadius' => '0px'
    ],
    'rules' => [
        '.Input' => [
            'border' => '1px solid #d1d5db',
            'padding' => '16px 18px',
            'borderRadius' => '8px'
        ]
    ]
]
```

### 6.2 Stripe UPE Customization

**Filter:** `wc_stripe_upe_params`

**Hidden Elements:**
- `.AccordionItemHeader` - Tab headers
- `.PaymentMethodHeader` - Method headers
- `.TabLabel`, `.TabIcon`, `.Tab` - Tab navigation

**Error Styling:**
```php
'.Error' => [
    'display' => 'flex',
    'flexDirection' => 'row',
    'alignItems' => 'flex-start',
    'gap' => '8px',
    'color' => '#991b1b'
]
```

### 6.3 Payment Methods Accordion

**Template:** `woocommerce/templates/checkout/payment.php`

**Structure:**
```html
<ul class="bw-payment-methods wc_payment_methods">
    <li class="bw-payment-method" data-gateway-id="stripe">
        <div class="bw-payment-method__header">
            <input type="radio" name="payment_method" value="stripe" />
            <label>Pay with Card</label>
        </div>
        <div class="bw-payment-method__content">
            <!-- Stripe Elements renders here -->
        </div>
    </li>
</ul>
```

**JavaScript Handler:** `bw-payment-methods.js`

**Key Functions:**
- `initPaymentMethodsAccordion()` - Setup accordion behavior
- `handlePaymentMethodChange()` - Handle selection
- `updatePlaceOrderButton()` - Update button text per gateway

### 6.4 Google Pay Integration

**Gateway Class:** `BW_Google_Pay_Gateway extends WC_Payment_Gateway`

**Gateway ID:** `bw_google_pay`

**Configuration:**
```php
bw_google_pay_enabled                 // '0' or '1'
bw_google_pay_test_mode               // '0' or '1'
bw_google_pay_test_publishable_key    // Test Stripe key
bw_google_pay_publishable_key         // Live Stripe key
```

**JavaScript Implementation:**
```javascript
// bw-google-pay.js
const paymentRequest = stripe.paymentRequest({
    country: 'IT',
    currency: 'eur',
    total: { label: 'Ordine BlackWork', amount: amountInCents },
    requestPayerName: true,
    requestPayerEmail: true,
    requestPayerPhone: true
});

paymentRequest.canMakePayment().then(result => {
    if (result) {
        // Show Google Pay button
    }
});
```

---

## 7. My Account System

### 7.1 Not Logged In State (Login Page)

**Template:** `woocommerce/templates/myaccount/form-login.php`

**Layout:**
- Left side: Cover image (50% width)
- Right side: Auth forms (50% width)
- Mobile: Full-width form only

**Authentication Screens:**
| Screen | Data Attribute | Purpose |
|--------|---------------|---------|
| Magic | `data-bw-screen="magic"` | Email input for OTP |
| Password | `data-bw-screen="password"` | Email + password |
| OTP | `data-bw-screen="otp"` | 6-digit code input |
| Create Password | `data-bw-screen="create-password"` | Set new password |

**Screen Navigation:**
```javascript
switchAuthScreen(target, options)
// targets: 'magic', 'password', 'otp', 'create-password'
```

### 7.2 Logged In State (Dashboard)

**Template:** `woocommerce/templates/myaccount/my-account.php`

**Layout:**
```css
.bw-account-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 32px;
}
```

**Navigation Items:**
| Endpoint | Label | Icon |
|----------|-------|------|
| `dashboard` | Dashboard | Home icon |
| `downloads` | Downloads | Download icon |
| `orders` | My purchases | Bag icon |
| `edit-account` | Settings | Gear icon |
| `customer-logout` | Logout | Door icon |

### 7.3 Settings Tabs

**Template:** `woocommerce/templates/myaccount/form-edit-account.php`

**Tabs:**
| Tab | ID | Fields |
|-----|-----|--------|
| Profile | `#bw-tab-profile` | First name, Last name, Display name |
| Billing Details | `#bw-tab-billing` | Country, Address, City, Postcode |
| Shipping Details | `#bw-tab-shipping` | Same as billing checkbox, Address fields |
| Security | `#bw-tab-security` | Password change, Email change |

### 7.4 Onboarding Flow

**Endpoint:** `/my-account/set-password/`

**Enforcement Logic:**
```php
function bw_mew_enforce_supabase_onboarding_lock() {
    if (!is_user_logged_in()) return;

    $user_id = get_current_user_id();
    if (get_user_meta($user_id, 'bw_supabase_onboarded', true) === '1') return;

    // Only allow set-password and logout endpoints
    $allowed = ['set-password', 'customer-logout'];
    $current = WC()->query->get_current_endpoint();

    if (!in_array($current, $allowed)) {
        wp_redirect(wc_get_account_endpoint_url('set-password'));
        exit;
    }
}
```

**Body Class:** `.bw-onboarding-lock` (dims navigation)

---

## 8. Configuration Options Reference

### 8.1 Account Page Options

| Option | Type | Default | Purpose |
|--------|------|---------|---------|
| `bw_account_login_image` | URL | - | Cover image URL |
| `bw_account_login_image_id` | int | 0 | Cover image ID |
| `bw_account_logo` | URL | - | Logo URL |
| `bw_account_logo_id` | int | 0 | Logo attachment ID |
| `bw_account_logo_width` | int | 180 | Logo width (px) |
| `bw_account_logo_padding_top` | int | 0 | Logo top padding |
| `bw_account_logo_padding_bottom` | int | 30 | Logo bottom padding |
| `bw_account_login_title` | string | - | Login heading |
| `bw_account_login_subtitle` | string | - | Login subheading |
| `bw_account_show_social_buttons` | bool | 0 | Show OAuth buttons |
| `bw_account_facebook` | bool | 0 | Enable Facebook |
| `bw_account_facebook_app_id` | string | - | Facebook App ID |
| `bw_account_facebook_app_secret` | string | - | Facebook App Secret |
| `bw_account_google` | bool | 0 | Enable Google |
| `bw_account_google_client_id` | string | - | Google Client ID |
| `bw_account_google_client_secret` | string | - | Google Client Secret |

### 8.2 Checkout Options

| Option | Type | Default | Purpose |
|--------|------|---------|---------|
| `bw_checkout_logo` | URL | - | Checkout logo |
| `bw_checkout_logo_align` | string | left | Logo alignment |
| `bw_checkout_logo_width` | int | 200 | Logo width |
| `bw_checkout_page_bg` | color | #ffffff | Page background |
| `bw_checkout_left_bg_color` | color | #ffffff | Left column bg |
| `bw_checkout_right_bg_color` | color | transparent | Right column bg |
| `bw_checkout_border_color` | color | #262626 | Border color |
| `bw_checkout_left_width` | int | 62 | Left column % |
| `bw_checkout_right_width` | int | 38 | Right column % |
| `bw_checkout_thumb_ratio` | string | square | Thumbnail aspect |
| `bw_checkout_thumb_width` | int | 110 | Thumbnail width |
| `bw_checkout_legal_text` | text | - | Legal disclaimer |
| `bw_checkout_footer_copyright_text` | text | - | Footer copyright |
| `bw_checkout_show_footer_copyright` | bool | 0 | Show copyright |
| `bw_checkout_show_return_to_shop` | bool | 0 | Show return link |

### 8.3 Complete Supabase Options

See [Section 3.3](#33-configuration-options-wp_options) for complete list.

---

## 9. AJAX Endpoints Reference

### 9.1 Authentication Endpoints

| Action | Handler | Auth Required | Purpose |
|--------|---------|---------------|---------|
| `bw_supabase_login` | `bw_mew_handle_supabase_login` | No | Password login |
| `bw_supabase_token_login` | `bw_mew_handle_supabase_token_login` | No | Bridge tokens |
| `bw_supabase_email_exists` | `bw_mew_handle_supabase_email_exists` | No | Check email |
| `bw_supabase_update_profile` | `bw_mew_handle_supabase_update_profile` | Yes | Update profile |
| `bw_supabase_update_password` | `bw_mew_handle_supabase_update_password` | Yes | Change password |
| `bw_supabase_update_email` | `bw_mew_handle_supabase_update_email` | Yes | Change email |
| `bw_supabase_check_wp_session` | `bw_mew_handle_supabase_session_check` | No | Check session |
| `bw_supabase_resend_invite` | `bw_mew_handle_supabase_resend_invite` | No | Resend invite |

### 9.2 Checkout Endpoints

| Action | Handler | Purpose |
|--------|---------|---------|
| `bw_apply_coupon` | `bw_mew_ajax_apply_coupon` | Apply coupon |
| `bw_remove_coupon` | `bw_mew_ajax_remove_coupon` | Remove coupon |
| `bw_brevo_test_connection` | Admin handler | Test Brevo API |

### 9.3 Admin Diagnostics Endpoints

| Action | Handler | Auth Required | Purpose |
|--------|---------|---------------|---------|
| `bw_system_status_run_check` | `bw_system_status_handle_run_check` | Admin (`manage_options`) + nonce | On-demand read-only diagnostics snapshot (`all` or scoped checks) |

### 9.4 Nonce Keys

| Nonce | Action | Used In |
|-------|--------|---------|
| `bw-supabase-login` | Supabase auth | All auth AJAX |
| `bw-checkout-nonce` | Checkout | Coupon operations |
| `bw-google-pay-nonce` | Google Pay | Payment |
| `bw_checkout_subscribe_test` | Brevo test | Admin |
| `bw_account_page_nonce` | Settings save | Admin |
| `bw_system_status_run_check` | System Status checks | Admin diagnostics |

---

## 10. Implementation Guide for New Features

### 10.1 Adding a New Authentication Method

**Step 1: Add Configuration Options**
```php
// admin/class-blackwork-site-settings.php
// In Account Page > Technical Settings section

// Add to options array (around line 400)
'bw_supabase_new_method_enabled' => get_option('bw_supabase_new_method_enabled', '0'),

// Add form field (around line 1020)
<tr>
    <th scope="row">New Method</th>
    <td>
        <label>
            <input type="checkbox"
                   name="bw_supabase_new_method_enabled"
                   value="1"
                   <?php checked(1, $new_method_enabled); ?> />
            Enable new authentication method
        </label>
    </td>
</tr>

// Add save logic (around line 235)
update_option('bw_supabase_new_method_enabled',
    isset($_POST['bw_supabase_new_method_enabled']) ? '1' : '0');
```

**Step 2: Add Frontend UI**
```php
// woocommerce/templates/myaccount/form-login.php

<?php if ($new_method_enabled): ?>
<button data-bw-auth-method="new-method">
    Continue with New Method
</button>
<?php endif; ?>
```

**Step 3: Add JavaScript Handler**
```javascript
// assets/js/bw-account-page.js

// Add to initAuth() function
if (settings.newMethodEnabled) {
    initNewMethodAuth();
}

function initNewMethodAuth() {
    $('[data-bw-auth-method="new-method"]').addEventListener('click', async (e) => {
        e.preventDefault();
        // Implementation here
    });
}
```

**Step 4: Add Backend Handler (if needed)**
```php
// includes/woocommerce-overrides/class-bw-supabase-auth.php

add_action('wp_ajax_nopriv_bw_supabase_new_method', 'bw_mew_handle_new_method');
add_action('wp_ajax_bw_supabase_new_method', 'bw_mew_handle_new_method');

function bw_mew_handle_new_method() {
    check_ajax_referer('bw-supabase-login', 'nonce');
    // Implementation here
    wp_send_json_success(['message' => 'Success']);
}
```

### 10.2 Adding a New Checkout Field

**Step 1: Add to Fields Admin**
```php
// includes/admin/checkout-fields/class-bw-checkout-fields-admin.php

// Add to default fields array
$default_fields['billing']['billing_new_field'] = [
    'enabled' => true,
    'priority' => 25,
    'width' => 'full',
    'label' => 'New Field',
    'required' => false
];
```

**Step 2: Register with WooCommerce**
```php
// includes/admin/checkout-fields/class-bw-checkout-fields-frontend.php

add_filter('woocommerce_checkout_fields', function($fields) {
    $fields['billing']['billing_new_field'] = [
        'type' => 'text',
        'label' => __('New Field', 'bw'),
        'required' => false,
        'class' => ['form-row-wide'],
        'priority' => 25
    ];
    return $fields;
});
```

### 10.3 Adding a New Payment Gateway

**Step 1: Create Gateway Class**
```php
// includes/woocommerce-overrides/class-bw-new-gateway.php

class BW_New_Gateway extends WC_Payment_Gateway {
    public function __construct() {
        $this->id = 'bw_new_gateway';
        $this->has_fields = true;
        $this->method_title = 'New Gateway';
        $this->init_settings();
    }

    public function payment_fields() {
        echo '<div id="bw-new-gateway-container"></div>';
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        // Process payment
        return [
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        ];
    }
}
```

**Step 2: Register Gateway**
```php
// woocommerce/woocommerce-init.php

add_filter('woocommerce_payment_gateways', function($gateways) {
    $gateways[] = 'BW_New_Gateway';
    return $gateways;
});
```

**Step 3: Add JavaScript**
```javascript
// assets/js/bw-new-gateway.js

jQuery(document).ready(function($) {
    const container = document.getElementById('bw-new-gateway-container');
    if (!container) return;

    // Initialize gateway UI
});
```

### 10.4 Adding a New Settings Tab

**Step 1: Add Tab Navigation**
```php
// admin/class-blackwork-site-settings.php (around line 170)

<a href="?page=blackwork-site-settings&tab=new-tab"
   class="nav-tab <?php echo $active_tab === 'new-tab' ? 'nav-tab-active' : ''; ?>">
    New Tab
</a>
```

**Step 2: Add Tab Content**
```php
// admin/class-blackwork-site-settings.php (around line 2500)

case 'new-tab':
    bw_site_render_new_tab();
    break;

// Define render function
function bw_site_render_new_tab() {
    ?>
    <form method="post" action="">
        <?php wp_nonce_field('bw_new_tab_save', 'bw_new_tab_nonce'); ?>
        <table class="form-table">
            <tr>
                <th>Setting</th>
                <td><input type="text" name="bw_new_setting" /></td>
            </tr>
        </table>
        <?php submit_button('Save Settings'); ?>
    </form>
    <?php
}
```

**Step 3: Handle Save**
```php
// At top of bw_site_settings_page() function

if (isset($_POST['bw_new_tab_submit'])) {
    check_admin_referer('bw_new_tab_save', 'bw_new_tab_nonce');
    update_option('bw_new_setting', sanitize_text_field($_POST['bw_new_setting']));
    echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
}
```

---

## 11. Data Flow Diagrams

### 11.1 Complete Authentication Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                        USER AUTHENTICATION                          │
└─────────────────────────────────────────────────────────────────────┘
                                   │
         ┌─────────────────────────┼─────────────────────────┐
         ↓                         ↓                         ↓
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Magic Link    │    │    Password     │    │     OAuth       │
│      (OTP)      │    │     Login       │    │ (Google/FB)     │
└────────┬────────┘    └────────┬────────┘    └────────┬────────┘
         │                      │                      │
         ↓                      ↓                      ↓
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│ POST /auth/v1/  │    │ POST /auth/v1/  │    │ signInWithOAuth │
│      otp        │    │     token       │    │   → Provider    │
└────────┬────────┘    └────────┬────────┘    └────────┬────────┘
         │                      │                      │
         ↓                      │                      ↓
┌─────────────────┐             │             ┌─────────────────┐
│  6-digit code   │             │             │ ?code=...       │
│    entered      │             │             │  callback       │
└────────┬────────┘             │             └────────┬────────┘
         │                      │                      │
         ↓                      │                      ↓
┌─────────────────┐             │             ┌─────────────────┐
│ POST /auth/v1/  │             │             │ PKCE exchange   │
│     verify      │             │             │   or SDK        │
└────────┬────────┘             │             └────────┬────────┘
         │                      │                      │
         └──────────────────────┼──────────────────────┘
                                ↓
                    ┌─────────────────────┐
                    │ access_token +      │
                    │ refresh_token       │
                    └──────────┬──────────┘
                               ↓
                    ┌─────────────────────┐
                    │ AJAX: bw_supabase_  │
                    │    token_login      │
                    └──────────┬──────────┘
                               ↓
                    ┌─────────────────────┐
                    │ bw_mew_supabase_    │
                    │   store_session()   │
                    └──────────┬──────────┘
                               ↓
              ┌────────────────┴────────────────┐
              ↓                                 ↓
    ┌─────────────────┐               ┌─────────────────┐
    │  Cookie Storage │               │ UserMeta Storage│
    │  _access        │               │ access_token    │
    │  _refresh       │               │ refresh_token   │
    └────────┬────────┘               └────────┬────────┘
              └────────────────┬───────────────┘
                               ↓
                    ┌─────────────────────┐
                    │ wp_set_auth_cookie  │
                    │    ($user_id)       │
                    └──────────┬──────────┘
                               ↓
                    ┌─────────────────────┐
                    │ Check: onboarded=1? │
                    └──────────┬──────────┘
                               │
              ┌────────────────┴────────────────┐
              ↓                                 ↓
    ┌─────────────────┐               ┌─────────────────┐
    │ YES: Redirect   │               │ NO: Redirect to │
    │ to /my-account/ │               │ /set-password/  │
    └─────────────────┘               └─────────────────┘
```

### 11.2 Checkout Order Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                       CHECKOUT ORDER FLOW                           │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────┐
│  User fills     │
│  checkout form  │
└────────┬────────┘
         ↓
┌─────────────────┐
│ Newsletter      │
│ checkbox        │─────→ Store: _bw_subscribe_newsletter (order meta)
└────────┬────────┘
         ↓
┌─────────────────┐
│ Select payment  │
│ method          │
└────────┬────────┘
         ↓
┌─────────────────────────────────────────────────────────────────┐
│                     PAYMENT PROCESSING                          │
├─────────────────┬─────────────────┬─────────────────────────────┤
│     Stripe      │    PayPal       │      Google Pay             │
│  Card Element   │   Redirect      │   Payment Request API       │
└────────┬────────┴────────┬────────┴────────┬────────────────────┘
         └─────────────────┼─────────────────┘
                           ↓
                 ┌─────────────────┐
                 │ Order Created   │ → status: pending
                 └────────┬────────┘
                          ↓
                 ┌─────────────────┐
                 │ Payment Success │
                 └────────┬────────┘
                          ↓
                 ┌─────────────────┐
                 │ Order Status    │ → processing/completed
                 │    Change       │
                 └────────┬────────┘
                          │
         ┌────────────────┴────────────────┐
         ↓                                 ↓
┌─────────────────┐               ┌─────────────────┐
│ Newsletter?     │               │ Supabase        │
│ subscribe_timing│               │ Provisioning?   │
└────────┬────────┘               └────────┬────────┘
         │                                 │
         ↓                                 ↓
┌─────────────────┐               ┌─────────────────┐
│ Brevo API       │               │ Guest user?     │
│ - upsert contact│               │ Create WP user  │
│ - add to list   │               │ Send invite     │
└─────────────────┘               └─────────────────┘
```

---

## 12. Security Considerations

### 12.1 Authentication Security

**Rate Limiting:**
- Social login: 20 attempts per IP per hour (transient)
- Invite resend: 2-minute cooldown

**Token Security:**
- Cookies: `httponly`, `secure` (HTTPS), `SameSite=Lax`
- Access token: 1-hour expiry
- Refresh token: 30-day expiry
- Service role key: Server-side only (never in frontend)

**CSRF Protection:**
- WordPress nonce on all AJAX endpoints
- State token for OAuth flows (15-minute expiry)

### 12.2 Data Validation

**Input Sanitization:**
```php
esc_url_raw()           // URLs
sanitize_text_field()   // Single-line text
sanitize_textarea_field() // Multi-line text
sanitize_email()        // Email addresses
absint()                // Integers
wp_kses_post()          // HTML content
```

**Output Escaping:**
```php
esc_html()    // Text in HTML context
esc_attr()    // Attribute values
esc_url()     // URLs in HTML
wp_kses_post() // Safe HTML output
```

### 12.3 Sensitive Data Handling

**Never Logged:**
- Access tokens
- Refresh tokens
- Passwords
- API keys

**Hashed for Logging:**
- Email addresses (debug mode)

### 12.4 Best Practices Checklist

- [ ] Always use `check_ajax_referer()` for AJAX handlers
- [ ] Never expose service role key to frontend
- [ ] Sanitize all user inputs
- [ ] Escape all outputs
- [ ] Use prepared statements for database queries
- [ ] Validate redirect URLs before use
- [ ] Check user capabilities before admin operations
- [ ] Use transients with appropriate expiry for rate limiting

---

## Appendix A: File Reference Quick Lookup

| Feature | Primary File | Lines |
|---------|-------------|-------|
| Settings Page | `admin/class-blackwork-site-settings.php` | 6000+ |
| Supabase Auth | `includes/woocommerce-overrides/class-bw-supabase-auth.php` | 1479 |
| Social Login | `includes/woocommerce-overrides/class-bw-social-login.php` | 548 |
| My Account | `includes/woocommerce-overrides/class-bw-my-account.php` | 421 |
| WC Init | `woocommerce/woocommerce-init.php` | 1558 |
| Login Form | `woocommerce/templates/myaccount/form-login.php` | 236 |
| Checkout | `woocommerce/templates/checkout/form-checkout.php` | 280 |
| Payment | `woocommerce/templates/checkout/payment.php` | 250 |
| Login JS | `assets/js/bw-account-page.js` | 1367 |
| Bridge JS | `assets/js/bw-supabase-bridge.js` | 387 |
| Checkout JS | `assets/js/bw-checkout.js` | 99KB |

---

## Appendix B: Troubleshooting

### Common Issues

**Technical Settings Tab Not Working:**
- Check browser console for JavaScript errors
- Verify jQuery is loaded
- Clear browser cache
- Check for corrupted strings in settings file

**Supabase Login Not Working:**
- Verify `bw_supabase_project_url` is set
- Verify `bw_supabase_anon_key` is set
- Enable debug mode and check error_log
- Verify redirect URLs are allowlisted in Supabase

**OAuth Callback Issues:**
- Check redirect URL is registered with provider
- Verify HTTPS is enabled
- Check PKCE code_verifier in localStorage
- Verify state token hasn't expired

**Checkout Coupon Issues:**
- Check session persistence
- Verify nonce is valid
- Check for WooCommerce AJAX conflicts

---

**Document Version:** 1.0
**Last Updated:** January 2026
**Maintained by:** BlackWork Development Team


---

## Source: `docs/10-architecture/elementor-widget-architecture-context.md`

# Elementor Widget Architecture Context (wpblackwork)

## Obiettivo
Questo documento sintetizza l'architettura reale dei widget Elementor nel plugin `wpblackwork` e fornisce un blueprint pratico per creare un nuovo widget header.

## 1) Bootstrap e registrazione widget

### Entry point plugin
- File: `blackwork-core-plugin.php`
- Include il loader widget in:
  - `require_once __DIR__ . '/includes/class-bw-widget-loader.php';`

### Loader automatico
- File: `includes/class-bw-widget-loader.php`
- Hook usati:
  - `elementor/widgets/register`
  - `elementor/widgets/widgets_registered`
- Comportamento:
  - Scansiona `includes/widgets/class-bw-*-widget.php`
  - Richiede ogni file
  - Risolve automaticamente classi in due formati:
    - `Widget_Bw_*`
    - `BW_*_Widget`
  - Registra con API compatibile sia nuova (`register`) sia legacy (`register_widget_type`)
  - Evita doppia registrazione con flag interno `$widgets_registered`

## 2) Categoria Elementor custom
- File: `blackwork-core-plugin.php`
- Categoria registrata: `blackwork`
- Etichetta in editor: `Black Work Widgets`

## 3) Struttura standard di un widget nel progetto

Pattern prevalente in `includes/widgets/`:
1. Classe estende `Elementor\Widget_Base` (o classe base custom del plugin)
2. Metodi minimi:
   - `get_name()`
   - `get_title()`
   - `get_icon()`
   - `get_categories()`
   - `register_controls()`
   - `render()`
3. Metodi opzionali:
   - `get_style_depends()`
   - `get_script_depends()`
4. Controlli separati in metodi privati:
   - es. `register_content_controls()`, `register_style_controls()`

## 4) Asset architecture (CSS/JS)

### Helper comune
- File: `includes/helpers.php`
- Funzione chiave: `bw_register_widget_assets($widget_name, $js_dependencies = ['jquery'], $register_js = true)`
- Convenzione handle:
  - Style: `bw-{widget}-style`
  - Script: `bw-{widget}-script`
- Convenzione file:
  - `assets/css/bw-{widget}.css`
  - `assets/js/bw-{widget}.js`

### Registrazione/enqueue centralizzata
- File: `blackwork-core-plugin.php`
- Pattern:
  - `bw_register_*_widget_assets()`
  - `bw_enqueue_*_widget_assets()`
  - Hook sia frontend sia editor Elementor (`elementor/frontend/after_enqueue_scripts`, `elementor/editor/after_enqueue_scripts`)

Nota pratica:
- Molti widget registrano dipendenze sia via `get_*_depends()` sia via hook globali nel main plugin. Questo garantisce robustezza ma aumenta il coupling.

## 5) Stato attuale widget (inventario)

Widget trovati in `includes/widgets/`:
- `bw-about-menu`
- `bw-add-to-cart`
- `bw-add-to-cart-variation`
- `bw-animated-banner`
- `bw-button`
- `bw-divider`
- `bw-product-grid`
- `bw-navshop`
- `bw-presentation-slide`
- `bw-price-variation`
- `bw-product-details-table`
- `bw-product-slide`
- `bw-related-post`
- `bw-related-products`
- `bw-search`
- `bw-slick-slider`
- `bw-slide-showcase`
- `bw-static-showcase`
- `bw-tags`
- `bw-wallpost`

Totale: 20 widget.

## 6) Focus header: stato attuale

I widget header Elementor legacy sono stati rimossi.
L'header usa ora il modulo custom server-rendered:
- `includes/modules/header/header-module.php`
- `includes/modules/header/frontend/header-render.php`
- `includes/modules/header/frontend/assets.php`
- `includes/modules/header/assets/css/*`
- `includes/modules/header/assets/js/*`

Il contratto AJAX live search resta in `blackwork-core-plugin.php`:
- action: `bw_live_search_products`
- nonce: `bw_search_nonce`

### `BW About Menu` (`bw-about-menu`)
- File widget: `includes/widgets/class-bw-about-menu-widget.php`
- Asset:
  - `assets/css/bw-about-menu.css`
  - `assets/js/bw-about-menu.js`
- Ruolo:
  - Render menu WP (`wp_nav_menu`) con classe link custom (`bw-about-menu__link`)
  - Spotlight effect gestito con CSS variables + JS
  - In editor Elementor si reinizializza via hook `frontend/element_ready/bw-about-menu.default`

### Smart Header (sistema header globale)
- Asset:
  - `assets/css/bw-smart-header.css`
  - `assets/js/bw-smart-header.js`
- Enqueue globale frontend:
  - `bw_enqueue_smart_header_assets()` in `wp_enqueue_scripts`
- Comportamento:
  - Header fixed con classi `.visible/.hidden/.scrolled`
  - Evita caricamento in editor preview Elementor
  - Supporta dark zones (`.smart-header-dark-zone`) e testo reattivo (`.smart-header-reactive-text`)

## 7) Pattern importanti per nuovi widget header (storico)

1. Namespace CSS rigoroso:
   - prefisso univoco (`.bw-{widget}*`)
2. Supporto multi-istanza:
   - evitare selector globali non scoped
   - se overlay/popup usa `data-widget-id` + selector con `{{ID}}`
3. Compatibilita editor Elementor:
   - hook JS `frontend/element_ready/{widget}.default`
   - eventuale fallback quando dati WooCommerce non disponibili in preview
4. Asset safe-loading:
   - registrare handle prima di restituirli in `get_*_depends()`
5. Sanitizzazione output:
   - `esc_html`, `esc_attr`, `esc_url`
6. Accessibilita minima:
   - `aria-label`, focus states, gestione ESC per overlay
7. Performance:
   - debounce per ricerca live
   - abort richieste AJAX precedenti

## 8) Blueprint consigliato per nuovo widget header

### Naming
- File: `includes/widgets/class-bw-header-{nome}-widget.php`
- Classe: `BW_Header_{Nome}_Widget` (oppure `Widget_Bw_Header_{Nome}`; il loader supporta entrambi)
- `get_name()`: `bw-header-{nome}`
- Asset:
  - `assets/css/bw-header-{nome}.css`
  - `assets/js/bw-header-{nome}.js`

### Minimo contratto PHP
1. `get_name/get_title/get_icon/get_categories`
2. `get_style_depends/get_script_depends`
3. `register_controls` separato in content/style
4. `render` con markup semanticamente stabile

### Minimo contratto JS
1. Init on document ready
2. Init su hook Elementor `frontend/element_ready/bw-header-{nome}.default`
3. Guard per doppia init
4. Cleanup handlers se il widget viene re-renderizzato

## 9) Rischi concreti da evitare

1. Overlay dentro container header con `overflow:hidden`
   - soluzione: append in `body` (come `bw-search`)
2. Stili che perdono priorita in editor
   - usare selector scoped e, solo dove necessario, specificita maggiore/`!important`
3. Dipendenze non registrate
   - validare che l'handle sia `registered` prima di restituirlo
4. Coupling forte tra widget e hook globali
   - per nuovi widget preferire robustezza in `get_*_depends()` + registrazione in `init`

## 10) Checklist pre-implementazione nuovo widget header

1. Definire il comportamento funzionale (es. menu, cta, account/cart, search, sticky state)
2. Definire se serve overlay/popup
3. Definire controlli Elementor minimi (content + style)
4. Definire dipendenze JS/CSS e strategia di enqueue
5. Definire comportamento in editor Elementor vs frontend
6. Definire integrazione con smart-header (classi richieste)
7. Definire scenari di test:
   - desktop/tablet/mobile
   - editor Elementor
   - pagina frontend reale con WooCommerce attivo

---

Nota: il blueprint sopra resta utile per nuovi widget Elementor non-header. Per l'header, usare il modulo custom.


---

## Source: `docs/10-architecture/theme-builder-lite/runtime-hook-map.md`

# Theme Builder Lite - Runtime Hook Map (Phase 1 + Phase 2 Step 6)

## Purpose
Actual runtime hooks used by implemented Theme Builder Lite surfaces.
Current scope includes:
- Phase 1: Custom Fonts + Footer Template
- Phase 2 Step 6: resolver for `single_post`, `single_page`, `single_product` (Woo singular), `product_archive` (Woo shop/category/tag), `archive` (non-Woo), `search`, `error_404`

## Hook Inventory

| Hook | Priority | Callback | File | Function |
|---|---:|---|---|---|
| `init` | 9 | `bw_tbl_register_template_cpt` | `includes/modules/theme-builder-lite/cpt/template-cpt.php` | Register `bw_template` CPT |
| `init` | 10 | `bw_tbl_register_template_type_meta` | `includes/modules/theme-builder-lite/cpt/template-meta.php` | Register `bw_template_type` post meta |
| `plugins_loaded` | 20 | `bw_tbl_bootstrap_elementor_fonts_integration` | `includes/modules/theme-builder-lite/integrations/elementor-fonts.php` | Register immediately if Elementor already loaded, otherwise defer registration |
| `elementor/loaded` | 20 | `bw_tbl_register_elementor_fonts_integration` | `includes/modules/theme-builder-lite/integrations/elementor-fonts.php` | Deferred registration path when Elementor loads after bootstrap check |
| `admin_init` | 10 (default) | `bw_tbl_register_admin_settings` | `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.php` | Register options and sanitizers |
| `admin_init` | 20 | `bw_tbl_ensure_elementor_cpt_support_option` | `includes/modules/theme-builder-lite/cpt/template-cpt.php` | Persist `bw_template` in `elementor_cpt_support` option |
| `admin_init` | 30 | `bw_tbl_maybe_flush_template_rewrite_rules` | `includes/modules/theme-builder-lite/cpt/template-cpt.php` | One-time rewrite flush for `bw_template` preview URLs |
| `admin_menu` | 21 | `bw_tbl_admin_menu` | `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.php` | Add Theme Builder Lite submenu |
| `admin_enqueue_scripts` | 10 (default) | `bw_tbl_admin_enqueue_assets` | `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.php` | Enqueue admin JS/media uploader |
| `add_meta_boxes` | 10 (default) | `bw_tbl_add_template_type_metabox` | `includes/modules/theme-builder-lite/cpt/template-meta.php` | Add template type metabox |
| `save_post_bw_template` | 10 (default) | `bw_tbl_save_template_type_metabox` | `includes/modules/theme-builder-lite/cpt/template-meta.php` | Persist template type |
| `wp_insert_post` | 10 (default) | `bw_tbl_default_template_type_on_insert` | `includes/modules/theme-builder-lite/cpt/template-meta.php` | Default type on first insert |
| `elementor/cpt_support` (filter) | 10 (default) | `bw_tbl_add_elementor_cpt_support` | `includes/modules/theme-builder-lite/cpt/template-cpt.php` | Enable Elementor Free editing for `bw_template` |
| `elementor/fonts/groups` (filter) | 20 | `bw_tbl_elementor_fonts_groups` | `includes/modules/theme-builder-lite/integrations/elementor-fonts.php` | Register `Custom Fonts` group in typography family control |
| `elementor/fonts/additional_fonts` (filter) | 20 | `bw_tbl_elementor_additional_fonts` | `includes/modules/theme-builder-lite/integrations/elementor-fonts.php` | Inject families from `bw_custom_fonts_v1` into Elementor typography list |
| `elementor/editor/after_enqueue_styles` | 20 | `bw_tbl_enqueue_custom_fonts_css` | `includes/modules/theme-builder-lite/integrations/elementor-fonts.php` | Enqueue `@font-face` CSS in Elementor editor context |
| `elementor/preview/enqueue_styles` | 20 | `bw_tbl_enqueue_custom_fonts_css` | `includes/modules/theme-builder-lite/integrations/elementor-fonts.php` | Enqueue same `@font-face` CSS in Elementor preview iframe |
| `update_option_bw_theme_builder_lite_flags` | 10 (default) | `bw_tbl_ensure_elementor_cpt_support_option` | `includes/modules/theme-builder-lite/cpt/template-cpt.php` | Re-assert Elementor CPT support after flags save |
| `upload_mimes` (filter) | 10 (default) | `bw_tbl_allow_font_upload_mimes` | `includes/modules/theme-builder-lite/fonts/custom-fonts.php` | Allow WOFF/WOFF2 uploads for admins |
| `wp_check_filetype_and_ext` (filter) | 10 | `bw_tbl_fix_font_filetype_and_ext` | `includes/modules/theme-builder-lite/fonts/custom-fonts.php` | Normalize font file type detection |
| `template_include` (filter) | 99 | `bw_tbl_include_single_template_preview` | `includes/modules/theme-builder-lite/runtime/template-preview.php` | Route `bw_template` singular to safe preview template |
| `template_include` (filter) | 50 | `bw_tbl_runtime_resolve_template_include` | `includes/modules/theme-builder-lite/runtime/template-resolver.php` | Phase 2 resolver with strict guards, conditions filtering, and deterministic winner selection |
| `wp_robots` (filter) | 10 (default) | `bw_tbl_add_noindex_for_bw_template` | `includes/modules/theme-builder-lite/runtime/template-preview.php` | Enforce noindex for public preview URLs |
| `wp_enqueue_scripts` | 20 | `bw_tbl_enqueue_custom_fonts_css` | `includes/modules/theme-builder-lite/fonts/custom-fonts.php` | Enqueue generated `@font-face` CSS |
| `wp` | 20 | `bw_tbl_apply_elementor_single_product_preview_context` | `includes/modules/theme-builder-lite/runtime/elementor-preview-context.php` | In Elementor bw_template(single_product) preview, set Woo product context + preview bridge (`$GLOBALS['bw_tbl_preview_product_id']` and `set_query_var`) without mutating `WP_Query` |
| `wp` | 20 | `bw_tbl_prepare_footer_runtime` | `includes/modules/theme-builder-lite/runtime/footer-runtime.php` | Resolve active footer and remove known theme footer callback |
| `wp_head` | 99 | `bw_tbl_footer_theme_fallback_css` | `includes/modules/theme-builder-lite/runtime/footer-runtime.php` | Scoped CSS fallback to suppress theme footer |
| `wp_footer` | 20 | `bw_tbl_render_footer_template` | `includes/modules/theme-builder-lite/runtime/footer-runtime.php` | Render active Elementor footer template |

## Feature Flag Gates
Option: `bw_theme_builder_lite_flags`
- Master gate: `enabled`
- Fonts gate: `custom_fonts_enabled`
- Footer gate: `footer_override_enabled`
- Templates gate: `templates_enabled`

Runtime behavior:
- All frontend output paths are gated by these flags.
- If flag checks fail, hooks return without mutation/output.
- Admin assets are scoped to `blackwork-site-settings_page_bw-theme-builder-lite-settings` only and are not loaded on Elementor editor routes.
- Elementor font integration is soft-dependent and executes only after `elementor/loaded`; otherwise callbacks no-op.
- Phase 2 resolver bypasses: admin/ajax/feed/embed, `is_singular('bw_template')`, Elementor editor/preview, Woo safety endpoints (`is_cart`, `is_checkout`, `is_account_page`, `is_wc_endpoint_url`), and non-target Woo product taxonomies (`is_product_taxonomy()` fallback path).
- Woo context coverage in current implementation: `single_product` + `product_archive` (shop + product category/tag).

## Fallback Contract

Footer:
- Active template must be `bw_template` + `publish` + `bw_template_type=footer`.
- Runtime bypasses override on Elementor editor/preview requests and on `is_singular('bw_template')`.
- If invalid/missing/error: no custom output, theme footer remains.

Fonts:
- At least one valid source is required.
- Invalid rows are skipped without blocking frontend.

## Explicitly Not Used in Current Implementation
- WooCommerce product archive/shop hooks
- Header runtime hooks


---

## Source: `docs/20-development/README.md`

# 20 Development

Developer-focused implementation patterns and extension context.

## Files
- [woocommerce-template-overrides.md](woocommerce-template-overrides.md): WooCommerce template override usage.
- [linting-and-phpcs-baseline.md](linting-and-phpcs-baseline.md): PHPCS lint strategy, legacy baseline policy, and incremental cleanup workflow.


---

## Source: `docs/20-development/admin-panel-map.md`

# Blackwork Admin Panel Map

## Purpose
This document is the official architectural reference for the WordPress admin panel `Blackwork Site`.
It consolidates the structural reality already audited in [`docs/50-ops/admin-panel-reality-audit.md`](../50-ops/admin-panel-reality-audit.md) without introducing implementation changes.

## 1) Global Admin Architecture

### 1.1 Menu registration
- Main menu (`blackwork-site-settings`): registered with `add_menu_page(...)` in `admin/class-blackwork-site-settings.php`.
- Main submenu alias (`blackwork-site-settings`): explicit `Site Settings` submenu registered with `add_submenu_page(...)` in `admin/class-blackwork-site-settings.php`.
- Header submenu (`bw-header-settings`): registered with `add_submenu_page(...)` in `includes/modules/header/admin/header-admin.php`.
- Mail Marketing submenu (`blackwork-mail-marketing`): registered with `add_submenu_page(...)` in `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`.

### 1.1.1 2026-03 Navigation Restore
- `Site Settings` submenu was explicitly restored under `Blackwork Site`.
- Purpose: prevent WordPress from opening the first child module (`All Templates`) when clicking the top-level menu.
- Result:
  - `Blackwork Site` consistently lands on the unified settings router (`bw_site_settings_page`).
  - Checkout / Supabase / Coming Soon / Redirect / Import / Loading tabs remain reachable from a stable entrypoint.

### 1.2 Capability model
- Primary capability baseline: `manage_options` (main menu + Header + Mail Marketing).
- Import Product tab extends gate with `manage_woocommerce` OR `manage_options`.

### 1.3 Save models
- Custom POST save model (most main tabs):
  - tab renderer handles submit
  - `check_admin_referer(...)`
  - sanitized values persisted via `update_option(...)`
- Settings API model (Header submenu):
  - `register_setting('bw_header_settings_group', 'bw_header_settings', sanitize_callback)`
  - persisted through `options.php`
- Custom modular save model (Mail Marketing submenu):
  - centralized `handle_post()`
  - tab-based payload parsing
  - explicit sanitization + `update_option(...)`

### 1.4 AJAX endpoints
Main settings module:
- `bw_google_pay_test_connection`
- `bw_google_maps_test_connection`
- `bw_klarna_test_connection`
- `bw_apple_pay_test_connection`
- `bw_apple_pay_verify_domain`

Mail Marketing module:
- `bw_brevo_test_connection`
- `bw_brevo_order_refresh_status`
- `bw_brevo_order_retry_subscribe`
- `bw_brevo_order_load_lists`
- `bw_brevo_user_check_status`
- `bw_brevo_user_sync_status`

### 1.5 Admin assets
Main settings assets:
- `admin/css/blackwork-site-settings.css`
- `admin/css/bw-admin-ui-kit.css` (shared Shopify-style Blackwork admin UI primitives; scoped to Blackwork Site pages)
- `admin/js/bw-redirects.js`
- `admin/js/bw-google-pay-admin.js`
- `admin/js/bw-klarna-admin.js`
- `admin/js/bw-apple-pay-admin.js`
- `admin/js/bw-checkout-subscribe.js` (Mail Marketing General tab context)
- `assets/js/bw-border-toggle-admin.js`

Header assets:
- `includes/modules/header/admin/header-admin.js`
- WordPress media uploader (`wp_enqueue_media()`)

Mail Marketing operational assets:
- `admin/js/bw-order-newsletter-status.js`
- `admin/js/bw-user-mail-marketing.js`
- `admin/css/bw-order-newsletter-status.css`

### 1.5.1 Shared Admin UI Kit pattern
- Kit file: `admin/css/bw-admin-ui-kit.css`
- Enqueue strategy:
  - centralized in `admin/class-blackwork-site-settings.php` via `bw_admin_enqueue_ui_kit_assets()`
  - gated by `bw_is_blackwork_site_admin_screen(...)`
  - loads only on Blackwork Site panel surfaces (`blackwork-site-settings` top-level/subpages, `blackwork-mail-marketing`, and `edit.php?post_type=bw_template`)
- Scope strategy:
  - all reusable rules are namespaced under `.bw-admin-root` to prevent bleed into unrelated WordPress admin screens.
- Adoption pattern for other Blackwork admin pages:
  1. Wrap page container with `.bw-admin-root`.
  2. Compose layout with kit primitives (`.bw-admin-header`, `.bw-admin-action-bar`, `.bw-admin-card`, `.bw-admin-field-row`, `.bw-admin-table`).
  3. Keep native WordPress form controls and existing save handlers unchanged.

Current adoption:
- `Blackwork Site > Status` page
- `Blackwork Site > Media Folders` settings page
- `Blackwork Site > Site Settings` router page (header, action bar, card-wrapped tabs/content, save-proxy CTA bound to existing tab submit buttons)
- `Blackwork Site > Mail Marketing` page (header, action bar with save CTA, UI-kit tabs, card-grouped General/Checkout settings)
- `Blackwork Site > Header` page (header shell, action bar save CTA, UI-kit primary tabs, and section-card settings groups)
- `Blackwork Site > Theme Builder Lite` page (header shell, action bar save CTA, Sections card with UI-kit tabs, card-grouped tab content)
- `Blackwork Site > All Templates` list screen (WP-native list table wrapped in UI-kit shell/action bar and skinned styles; behavior unchanged)

### 1.6 Blackwork Site Page Inventory (Post Shopify rollout)
| Page | Route / Slug | Entrypoint callback | Primary owner file | Save/Action model | UI root |
|---|---|---|---|---|---|
| Site Settings | `admin.php?page=blackwork-site-settings` | `bw_site_settings_page` | `admin/class-blackwork-site-settings.php` | custom POST per tab + nonce | `.bw-admin-root` |
| Status | `admin.php?page=bw-system-status` | `bw_system_status_render_admin_page` | `includes/modules/system-status/admin/status-page.php` | admin-ajax (`bw_system_status_run_check`) | `.bw-admin-root` |
| Media Folders (settings) | `admin.php?page=bw-media-folders-settings` | `bw_mf_render_settings_page` | `includes/modules/media-folders/admin/media-folders-settings.php` | custom POST + nonce | `.bw-admin-root` |
| Mail Marketing | `admin.php?page=blackwork-mail-marketing` | `render_mail_marketing_page` | `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php` | custom POST + nonce | `.bw-admin-root` |
| Header | `admin.php?page=bw-header-settings` | `bw_header_render_admin_page` | `includes/modules/header/admin/header-admin.php` | Settings API (`options.php`) | `.bw-admin-root` |
| Theme Builder Lite | `admin.php?page=bw-theme-builder-lite-settings` | `bw_tbl_render_admin_page` | `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.php` | Settings API + import nonce handler | `.bw-admin-root` |
| All Templates | `edit.php?post_type=bw_template` | WP list table (`edit.php`) + UX wrapper JS | `includes/modules/theme-builder-lite/admin/bw-template-type-inline.js` | WP List Table native + inline ajax type update | `.bw-admin-root` (injected) |

### 1.7 Asset Strategy (Admin)
- Shared visual primitives:
  - `admin/css/bw-admin-ui-kit.css`
  - always loaded on all Blackwork Site admin surfaces (Site Settings, Mail Marketing, Header, Theme Builder Lite, Status, Media Folders settings, All Templates).
  - enqueue path: `bw_admin_enqueue_ui_kit_assets()` via `bw_is_blackwork_site_admin_screen(...)`.
  - remains scoped by selectors under `.bw-admin-root` to avoid admin-wide CSS bleed.
- Site-settings legacy/admin utilities:
  - `admin/class-blackwork-site-settings.php::bw_site_settings_admin_assets()`.
  - Whitelist matrix (post `BW-TASK-20260305-09`):
    - `blackwork-site-settings` (all tabs): `admin/css/blackwork-site-settings.css`
    - `blackwork-site-settings&tab=account-page`: `wp_enqueue_media()`
    - `blackwork-site-settings&tab=checkout`: `wp_enqueue_media()`, `wp-color-picker`, `bw-google-pay-admin.js`, `bw-klarna-admin.js`, `bw-apple-pay-admin.js`
    - `blackwork-site-settings&tab=redirect`: `bw-redirects.js`
    - `blackwork-site-settings&tab=cart-popup`: `bw-border-toggle-admin.js`
    - `blackwork-mail-marketing&tab=general`: `bw-checkout-subscribe.js`
  - Out-of-scope pages (Header, Theme Builder Lite, Status, Media Folders, All Templates) do not receive the Site Settings tab-specific assets.
- Module-local assets:
  - Status:
    - behavior: `includes/modules/system-status/admin/assets/system-status-admin.js`
    - styling: shared UI kit (`admin/css/bw-admin-ui-kit.css`) with Status-scoped selectors under `.bw-admin-root.bw-admin-page-status` (no inline `<style>` block)
  - Theme Builder Lite: `.../theme-builder-lite-admin.css|js`, `bw-template-type-inline.js`
  - Header: `includes/modules/header/admin/header-admin.js`
  - Media Folders library screen: `includes/modules/media-folders/admin/assets/media-folders.css|js`
    - enabled surfaces controlled by `bw_core_flags`:
      - Media Library (`upload.php`) when `media_folders_use_media=1`
      - Posts list (`edit.php`) when `media_folders_use_posts=1`
      - Pages list (`edit.php?post_type=page`) when `media_folders_use_pages=1`
      - Products list (`edit.php?post_type=product`) when `media_folders_use_products=1`
    - runtime hardening contract:
      - folder tree + summary counters are cache-backed per taxonomy/context (`taxonomy + post_type`)
      - assignment batching invalidates cache once per operation (no per-item invalidation storms)
      - list-table filters mutate queries only when folder params are present (fail-open otherwise)
    - products list UI polish contract (`edit.php?post_type=product`, when `media_folders_use_products=1`):
      - compact fixed widths for drag-handle and checkbox columns
      - admin product thumbnail source target set to `150x150` (overridable via Media Folders admin filter/constant) and rendered as compact square `130x130` with product-screen-only CSS
    - list-table UX contract (posts/pages/products):
      - dedicated drag-handle column before `Title`
      - products anchor before `name` (fallback `title`, then `cb`) to stay deterministic with WooCommerce column maps
      - drag start only from handle (`dashicons-move`)
      - single-item drag assignment only (no list-table bulk drag)
      - folder node pencil context menu actions: Rename, New Subfolder, Pin/Unpin, Icon Color, Delete
  - Mail Marketing auxiliary panels: `admin/js/bw-order-newsletter-status.js`, `admin/js/bw-user-mail-marketing.js`

## 2) Tab-by-Tab Structural Map

## Cart Pop-up
- Renderer: `bw_site_render_cart_popup_tab()`
- Save delegate: `bw_cart_popup_save_settings()` (`cart-popup/admin/settings-page.php`)
- Nonce: `bw_cart_popup_save` (`bw_cart_popup_nonce`)
- Option groups:
  - Activation and behavior: `bw_cart_popup_active`, `bw_cart_popup_show_floating_trigger`, `bw_cart_popup_slide_animation`, `bw_cart_popup_show_quantity_badge`
  - Panel and overlay: `bw_cart_popup_panel_width`, `bw_cart_popup_mobile_width`, `bw_cart_popup_overlay_color`, `bw_cart_popup_overlay_opacity`, `bw_cart_popup_panel_bg`
  - Checkout CTA style/content: `bw_cart_popup_checkout_*`
  - Continue CTA style/content: `bw_cart_popup_continue_*`
  - SVG/icons spacing: `bw_cart_popup_additional_svg`, `bw_cart_popup_empty_cart_svg`, `bw_cart_popup_svg_black`, `bw_cart_popup_cart_icon_margin_*`, `bw_cart_popup_empty_cart_padding_*`
  - Promo block: `bw_cart_popup_promo_*`, `bw_cart_popup_apply_button_font_weight`
  - Navigation URL: `bw_cart_popup_return_shop_url`
- Frontend consumers:
  - `cart-popup/cart-popup.php`
  - `cart-popup/frontend/cart-popup-frontend.php`
  - `woocommerce/templates/cart/cart-empty.php`
- Dependencies:
  - WooCommerce cart runtime and related AJAX operations (`bw_cart_popup_*`).

## BW Coming Soon
- Renderer: `bw_site_render_coming_soon_tab()`
- Nonce: `bw_coming_soon_save`
- Option keys:
  - `bw_coming_soon_active`
- Frontend consumer:
  - `BW_coming_soon/includes/functions.php`
- Dependencies:
  - global site request gating logic.

## Login Page
- Renderer: `bw_site_render_account_page_tab()`
- Nonce: `bw_account_page_save`
- Option groups:
  - Provider and visual: `bw_account_login_provider`, image/logo keys, provider-specific titles/subtitles
  - Social toggles (WordPress path): `bw_account_facebook`, `bw_account_google`, provider secrets/IDs
  - Supabase auth model: `bw_supabase_*` configuration set
  - Compatibility keys: `bw_account_login_title`, `bw_account_login_subtitle`
- Frontend consumers:
  - `woocommerce/templates/global/form-login.php`
  - `woocommerce/templates/myaccount/form-login.php`
  - `woocommerce/templates/checkout/order-received.php`
  - `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- Dependencies:
  - provider routing between WordPress and Supabase auth ecosystems.

## My Account Page
- Renderer: `bw_site_render_my_account_front_tab()`
- Nonce: `bw_myaccount_front_save`
- Option keys:
  - `bw_myaccount_black_box_text`
  - `bw_myaccount_support_link`
- Frontend consumers:
  - My Account customization templates/override layer
- Dependencies:
  - My Account rendering path.

## Checkout
- Renderer: `bw_site_render_checkout_tab()`
- Nonce: `bw_checkout_settings_save`
- Option groups:
  - Layout and branding: `bw_checkout_logo*`, `bw_checkout_*_bg*`, widths, paddings, border settings, thumbnail settings
  - Content and legal: `bw_checkout_legal_text`, footer text and toggles
  - Section behavior: `bw_checkout_hide_*`, heading labels, free order message/button text
  - Policy blocks: `bw_checkout_policy_refund|shipping|privacy|terms|contact`
  - Checkout field sync: `bw_checkout_fields_settings`
  - Supabase provisioning: `bw_supabase_checkout_provision_enabled`, invite/expired redirect URLs
  - Payment toggles/config:
    - Google Pay: `bw_google_pay_*`
    - Klarna: `bw_klarna_*`
    - Apple Pay: `bw_apple_pay_*`
  - Address autocomplete: `bw_google_maps_enabled`, API key, autofill/restrict flags
- Frontend consumers:
  - `woocommerce/woocommerce-init.php`
  - `woocommerce/templates/checkout/payment.php`
  - `includes/Gateways/class-bw-google-pay-gateway.php`
  - `includes/Gateways/class-bw-klarna-gateway.php`
  - `includes/Gateways/class-bw-apple-pay-gateway.php`
- Dependencies:
  - WooCommerce checkout rendering and gateway availability
  - Supabase service-role/project configuration for provisioning flows
  - Google Maps API validity for autocomplete path.

## Redirect
- Renderer: `bw_site_render_redirect_tab()`
- Nonce: `bw_redirects_save`
- Option keys:
  - `bw_redirects` (list of source/target rules)
- Frontend consumer:
  - `includes/class-bw-redirects.php`
- Dependencies:
  - request interception and redirect execution order.

## Import Product
- Renderer: `bw_site_render_import_product_tab()`
- Capability: `manage_woocommerce` OR `manage_options`
- Nonces:
  - `bw_import_upload`
  - `bw_import_run`
- Data model:
  - transient state (`bw_import_state_{user_id}`), not long-lived option set
- Inputs:
  - CSV file, update-existing toggle, column mapping array
- Runtime consumers:
  - import parser/mapping/product-save functions in `admin/class-blackwork-site-settings.php`
- Dependencies:
  - WooCommerce product APIs, taxonomy/meta/media write paths.

## Loading
- Renderer: `bw_site_render_loading_tab()`
- Nonce: `bw_loading_settings_save`
- Option keys:
  - `bw_loading_global_spinner_hidden`
- Frontend consumer:
  - `blackwork-core-plugin.php`
- Dependencies:
  - WooCommerce loading/spinner behavior hooks.

## Header (submenu)
- Renderer: `bw_header_render_admin_page()`
- Save model: Settings API (`bw_header_settings_group`)
- Option key:
  - `bw_header_settings` (array)
- Key groups:
  - General header state and branding
  - Menus and breakpoints
  - Mobile layout and icon media IDs
  - Labels and links
  - Smart scroll configuration (`features.smart_scroll`, `smart_header.*`)
- Frontend consumer:
  - `includes/modules/header/frontend/header-render.php`
- Dependencies:
  - nav menu availability
  - smart-scroll CSS/JS precedence logic.

## Mail Marketing (Brevo) (submenu)
- Renderer: `render_mail_marketing_page()` (`general`/`checkout` internal tabs)
- Save handler: `handle_post()`
- Nonce: `bw_mail_marketing_save`
- Option keys:
  - Canonical: `bw_mail_marketing_general_settings`, `bw_mail_marketing_checkout_settings`
  - Legacy bridge source: `bw_checkout_subscribe_settings`
- Key groups:
  - General: API key/base/list, default opt-in mode, DOI settings, sender identity, debug/sync flags
  - Checkout channel: enabled/default checked, label/privacy text, timing, channel mode, placement key, priority offset
- Consumers:
  - Mail marketing admin, order/user status tooling, checkout subscription execution path
- Dependencies:
  - Brevo API reachability and key validity
  - checkout channel insertion point consistency.

## 3) Cross-Domain Dependencies Model

### 3.1 Auth provider logic
- `bw_account_login_provider` is the primary selector.
- WordPress social flags and Supabase OAuth flags can coexist in storage, but only one provider path should drive runtime behavior.

### 3.2 Payment gateway toggle logic
- `bw_google_pay_enabled`, `bw_klarna_enabled`, `bw_apple_pay_enabled` are intent toggles.
- Effective runtime availability also depends on gateway class readiness, required keys, and checkout context.

### 3.3 Supabase provisioning
- Checkout provisioning is controlled by `bw_supabase_checkout_provision_enabled` + redirect URLs.
- Functional validity depends on upstream Supabase credentials/configuration from auth settings.

### 3.4 Smart Header override precedence
- With Smart Header OFF: general Header background settings apply.
- With Smart Header ON: scroll-driven `smart_header.*` colors/opacities/blur settings take precedence.

### 3.5 Mail Marketing split model
- The current architecture is split:
  - General account/config model
  - Checkout channel behavior model
- Legacy option remains as migration/fallback compatibility layer.

## 4) Configuration Coherence Model

### 4.1 Mixed save patterns
- The admin panel intentionally combines:
  - custom POST saves (main settings tabs)
  - Settings API save (Header)
  - modular custom handler (Mail Marketing)
- This is functional but creates heterogeneous validation and lifecycle semantics.

### 4.2 Soft gating model
- Multiple domains rely on toggle + dependency readiness rather than hard blocking.
- Example pattern: feature can be enabled in UI while external prerequisites remain incomplete.

### 4.3 Legacy compatibility keys
- Some legacy keys are still written/read to preserve backward compatibility and migration continuity.
- This compatibility layer is deliberate but increases cognitive load during maintenance and troubleshooting.

## Maintenance note
For operational checks, incident handling, and validation posture, refer to:
- [`docs/50-ops/admin-panel-reality-audit.md`](../50-ops/admin-panel-reality-audit.md)
- [`docs/50-ops/maintenance-decision-matrix.md`](../50-ops/maintenance-decision-matrix.md)
- [`docs/50-ops/blackwork-development-protocol.md`](../50-ops/blackwork-development-protocol.md)

## 5) Normative Architecture Principles

### 5.1 Provider isolation rule
- Provider-specific configuration can coexist in storage, but runtime execution must resolve to one active provider path per domain.
- Auth domain: WordPress provider and Supabase provider configurations are both maintainable, but the effective provider is selected by the provider selector and must not blend execution paths.
- Integration-level logic must avoid implicit fallback between providers unless explicitly defined and documented.

### 5.2 Feature toggle soft-gating philosophy
- Admin toggles express operational intent, not unconditional runtime guarantee.
- A feature marked as enabled remains subject to dependency readiness checks (credentials, external availability, plugin/gateway readiness, context constraints).
- Soft-gating is therefore a two-step model:
  1. Administrative enablement
  2. Runtime eligibility validation

### 5.3 Runtime state determination hierarchy
- Runtime state is determined by the following hierarchy:
  1. Hard capability/security constraints (permissions, nonce, required context)
  2. Domain selector state (for example provider selection)
  3. Feature toggle state
  4. Dependency readiness (keys, APIs, classes, external provider status)
  5. Compatibility and fallback layer (legacy keys/defaults)
- Any downstream state cannot override an upstream failed condition.

### 5.4 Precedence rules (header, auth, payments)
- Header precedence:
  - Smart Header OFF -> General header visual settings apply.
  - Smart Header ON -> `smart_header.*` scroll settings override General visual values where overlapping.
- Auth precedence:
  - `bw_account_login_provider` is authoritative for runtime provider routing.
  - Non-selected provider settings remain passive configuration.
- Payments precedence:
  - Gateway toggle enables candidacy only.
  - Effective availability requires gateway readiness checks and valid key/config state.
  - Checkout runtime decides final availability in context.

### 5.5 Legacy compatibility posture
- Legacy keys/options are preserved as a controlled compatibility layer for migration continuity and backward behavior stability.
- Canonical models remain the current split/domain options; legacy paths must be treated as bridge/fallback, not as primary design targets.
- Maintenance policy: compatibility may be retained long-term when it reduces migration risk, but architectural documentation must always identify the canonical source of truth.


---

## Source: `docs/20-development/admin-ui-guidelines.md`

# Blackwork Admin UI Guidelines

## Purpose
Define the canonical Shopify-style admin UI integration pattern for Blackwork Site panel pages.

## Scope
Applies to Blackwork Site admin surfaces only:
- `blackwork-site-settings` top-level and subpages
- `blackwork-mail-marketing`
- `edit.php?post_type=bw_template` (All Templates)

## Core UI Kit
- Shared stylesheet: `admin/css/bw-admin-ui-kit.css`
- Root scope requirement: all page-level styling must render under `.bw-admin-root`
- Integration principle: wrapper + skinning first; preserve native WordPress behaviors

## Enqueue Rules
- UI Kit is enqueued via `bw_admin_enqueue_ui_kit_assets()` in `admin/class-blackwork-site-settings.php`
- Guard function: `bw_is_blackwork_site_admin_screen()`
- Must remain page-scoped to Blackwork surfaces only (no global WP admin bleed)
- Prefer page-local/module-local assets with strict hook + slug checks.
- Do not load heavy admin scripts globally across all Blackwork pages when only one tab/page needs them.

## Layout Contract
Use this structure for new/updated pages:
1. Page shell: `.bw-admin-root` + optional page class
2. Header: `.bw-admin-header`, title `.bw-admin-title`, subtitle `.bw-admin-subtitle`
3. Action bar: `.bw-admin-action-bar`
4. Content cards: `.bw-admin-card`
5. Tabs (when needed): `.bw-admin-tabs`
6. Tables/lists: keep WP-native mechanics; apply skin classes/wrappers only

Canonical flow:
- Header -> Action Bar -> Cards -> Tabs/Section content

## Functional Invariants (Non-negotiable)
- UI-only changes must not alter:
  - option keys/defaults/sanitizers
  - nonce/capability checks
  - save handlers and POST targets
  - WP_List_Table behavior (filters, search, bulk actions, sorting, pagination, row actions)
- If page uses WP list tables, do not rebuild table markup manually.
- Keep read-only diagnostics/admin observer endpoints read-only unless explicitly approved by feature contract.

## Styling Guidelines
- Keep all selectors scoped under `.bw-admin-root`
- Prefer generic utility classes over module-specific CSS names
- Reuse existing primitives before adding new classes
- If overflow is needed for large tables, use a wrapper (`.bw-table-wrap`) with `overflow-x:auto`
- Avoid large inline `<style>` blocks in render callbacks; prefer shared UI kit primitives or module-scoped static assets.
- Keep focus states visible and preserve WordPress baseline semantics for labels/inputs/buttons.

## Admin Security Checklist (New/Updated Page)
- Capability gate at page entry (`current_user_can(...)`) before rendering sensitive controls.
- Nonce field present on POST forms; server-side `check_admin_referer(...)` before writes.
- AJAX actions:
  - register only `wp_ajax_*` unless anonymous access is explicitly required.
  - verify nonce (`check_ajax_referer`) and capability before processing input.
  - sanitize all request values (`sanitize_key`, `sanitize_text_field`, `absint`, etc.).
- Escape all output (`esc_html`, `esc_attr`, `esc_url`, `esc_textarea`) in templates.

## Admin Request Input Handling Pattern
Use a canonical read pattern for all admin request parameters:

```php
$tab = isset($_GET['tab'])
    ? sanitize_key(wp_unslash($_GET['tab']))
    : '';
```

For text values from POST:

```php
$value = isset($_POST['something'])
    ? sanitize_text_field(wp_unslash($_POST['something']))
    : '';
```

For enum-like controls (`tab`, `section`, `view`), always apply an allowlist with fallback:

```php
$allowed_tabs = ['general', 'advanced'];
if (!in_array($tab, $allowed_tabs, true)) {
    $tab = 'general';
}
```

Notes:
- Keep `check_admin_referer(...)` and capability checks unchanged.
- Use `absint(wp_unslash(...))` for numeric request IDs.
- Escape on output separately (`esc_attr`, `esc_html`, `esc_url`), even after sanitization.

## Admin Performance Checklist (New/Updated Page)
- No heavy scans/queries on normal page load unless bounded and required.
- Prefer on-demand actions (button-triggered) for expensive diagnostics.
- Add bounded limits for scans (`LIMIT`, caps) and show partial-warning states.
- Cache expensive diagnostics snapshots where possible (transient/object cache).
- Ensure assets are loaded only on the intended screen/tab.
- Confirm no duplicate enqueue of same heavy dependencies across panel pages.

## Verification Checklist
For each UI rollout:
- `.bw-admin-root` present on page shell (or injected wrapper for WP-native list screens)
- UI Kit enqueued only on Blackwork surfaces
- Save flow unchanged
- No option key changes
- No PHP notices/fatals
- No CSS bleed outside Blackwork Site pages
- Nonce + capability checks still enforced on affected forms/endpoints
- Screen-specific assets still load on target page and do not leak to unrelated admin screens


---

## Source: `docs/20-development/linting-and-phpcs-baseline.md`

# Linting and PHPCS Baseline Strategy

## Purpose
`composer run lint:main` must stay green for active development while legacy PHPCS debt is reduced incrementally.

## Current Strategy
Blackwork uses a temporary narrow baseline by excluding only:
- `blackwork-core-plugin.php`

This is configured in:
- `phpcs.xml.dist`

Why this shape:
- `lint:main` stays stable/green for mandatory developer checks.
- legacy debt remains visible via dedicated scripts (`lint:legacy`, `lint:strict`).
- incremental enforcement is done with `lint:changed` on touched PHP files.

## Commands
- Main lint (CI/local default):
  - `composer run lint:main`
- Main alias:
  - `composer run lint`
- Legacy debt visibility (tracked file only):
  - `composer run lint:legacy`
- Changed PHP files gate (incremental workflow):
  - `composer run lint:changed`
- Full strict audit (known to fail on legacy debt until cleanup is done):
  - `composer run lint:strict`

## Governance Rules
1. No new PHPCS violations are allowed in changed PHP files (`lint:changed`).
2. Legacy debt in `blackwork-core-plugin.php` is tracked by `lint:legacy`.
3. Repository-wide debt trend is tracked by `lint:strict`.
4. Baseline scope must not expand without explicit governance approval.

## How to Reduce the Baseline
1. Create a dedicated cleanup task for `blackwork-core-plugin.php`.
2. Fix a bounded subset of violations.
3. Validate with:
   - `composer run lint:legacy`
   - `composer run lint:changed`
   - `composer run lint:strict` (expected to improve over time)
4. Repeat until `lint:legacy` passes.
5. Remove the exclude from `phpcs.xml.dist` once the legacy file is compliant.

## How to Regenerate/Update Baseline
This repository currently uses a narrow file exclusion baseline (not a generated violations snapshot).

To update baseline scope:
1. Edit `phpcs.xml.dist` exclude rules.
2. Document rationale in governance docs.
3. Verify:
   - `lint:main` remains green/stable
   - `lint:changed` still enforces changed files
   - `lint:legacy` still reports legacy debt


---

## Source: `docs/20-development/woocommerce-template-overrides.md`

# WooCommerce Template Overrides

Questa directory contiene template personalizzati per WooCommerce che utilizzano il sistema centralizzato delle product card del plugin BW Elementor Widgets.

## Come Usare

### Metodo 1: Copia nel tema

Per utilizzare questi template, copiali nella directory del tuo tema seguendo questa struttura:

```
your-theme/
└── woocommerce/
    ├── content-product.php
    └── loop/
        └── no-products-found.php
```

### Metodo 2: Usa i filtri PHP

Puoi anche integrare il renderer direttamente tramite filtri nel tuo tema o in un plugin personalizzato:

```php
// In functions.php o in un plugin custom
add_filter( 'woocommerce_product_loop_start', 'custom_product_loop_start' );
function custom_product_loop_start() {
    // Usa la classe BW_Product_Card_Renderer per personalizzare le card
}
```

## Template Disponibili

### content-product.php
Template per la card prodotto singola negli archivi e nei loop.
Utilizza `BW_Product_Card_Renderer::render_card()` per generare card in stile BW Wallpost.

## Personalizzazione

Tutti i template utilizzano la classe `BW_Product_Card_Renderer` che supporta queste opzioni:

- `image_size`: Dimensione immagine (thumbnail, medium, large, full)
- `show_image`: Mostra/nascondi immagine
- `show_hover_image`: Abilita immagine hover
- `show_title`: Mostra/nascondi titolo
- `show_description`: Mostra/nascondi descrizione
- `show_price`: Mostra/nascondi prezzo
- `show_buttons`: Mostra/nascondi pulsanti overlay
- `show_add_to_cart`: Mostra/nascondi pulsante add to cart
- `open_cart_popup`: Apri cart popup invece di andare alla pagina carrello

## Note

- Assicurati che il plugin BW Elementor Widgets sia attivo
- I template utilizzano le stesse classi CSS del widget BW Wallpost
- Puoi personalizzare l'aspetto tramite i CSS del tuo tema

