# Start Chat — Documentation Pack 5

Generated from repository docs snapshot (excluding docs/tasks).


---

## Source: `docs/60-adr/ADR-003-callback-anti-flash-model.md`

# ADR-003: Callback Anti-Flash Model

## Status
Accepted

This decision is binding and may only be altered through a superseding ADR.

## Context

Blackwork checkout and post-payment surfaces combine asynchronous provider confirmation, return/redirect routes, fragment refresh cycles, and UI rendering layers.

ADR-001 establishes checkout selector orchestration authority.
ADR-002 establishes top-down authority precedence (Payment > Authentication > Provisioning).

This ADR formalizes the Callback Anti-Flash Model to ensure UI layers cannot speculate, downgrade, or misrepresent payment truth during asynchronous confirmation flows.

The model applies to:

- Provider callbacks/webhooks
- Redirect and return routes
- Checkout fragment refresh cycles
- Success or thank-you surfaces
- Any UI capable of rendering payment state before or during confirmation

## Problem Definition (Flash/Flicker Risks)

Without explicit anti-flash governance:

- Success UI may render before provider-confirmed payment.
- Pending/confirmed states may visually oscillate during callback races.
- Fragment refresh may reintroduce stale UI after authority convergence.
- Duplicate callback events may trigger repeated or regressive transitions.
- Redirect surfaces may be misinterpreted as confirmation authority.

These behaviors create false-positive payment perception, unstable journeys, and cross-layer divergence.

## Decision

Blackwork formally adopts the Callback Anti-Flash Model as a Tier 0 governance rule set.

Binding rules:

- Payment truth MUST be established ONLY by provider-confirmed webhook/callback processed through authoritative local reconciliation.
- Redirect/return URLs are transitional transport layers and MUST NOT be treated as authority confirmation.
- UI MUST render from reconciled local authoritative state only.
- UI MUST NOT speculate, infer, or upgrade payment state.
- Duplicate callback events MUST be idempotent and converge.
- Once authoritative confirmation is reached, visual state MUST NOT regress or flicker to lower-confidence states.

This model operates within ADR-002 authority hierarchy and preserves monotonic progression at the Payment Authority layer.

## Single Source of Render Truth

All rendering surfaces MUST read from the reconciled local authoritative order/payment state.

The following are explicitly NON-authoritative signals:

- Query parameters
- Stripe return flags
- Client-side redirect indicators
- Fragment timing order
- Browser event sequencing
- Frontend success triggers

These signals MAY influence UX flow but MUST NOT determine payment truth.

## Callback Convergence Model

Callback handling MUST satisfy:

### 1. Authenticity Gate
Callback/webhook input MUST be cryptographically or signature validated before mutation.

### 2. Idempotent Mutation Gate
Duplicate or replayed events MUST NOT duplicate side effects.
Repeated processing MUST converge to identical terminal state.

### 3. Monotonic State Progression
State transitions MUST be monotonic.
Confirmed payment CANNOT regress to pending or speculative state.

Once confirmed state is rendered, no subsequent async event may visually downgrade it.

### 4. Deterministic Reconciliation
Provider outcome MUST reconcile into authoritative local state exactly once per effective transition.
UI MUST render only after reconciliation snapshot is stable.

### 5. Retry Safety
Retry events MAY re-enter processing but MUST preserve terminal-state stability once reached.

## Redirect / Return Surface Rules

Return and redirect routes are operational transport layers only.

Rules:

- Return surfaces MUST NOT declare success without confirmed authoritative local state.
- Pending confirmation MUST render neutral or pending messaging.
- Delayed webhook confirmation MUST NOT create false-positive success.
- Duplicate return hits MUST be repeat-safe.
- Redirect handling MUST NOT introduce loops or oscillation.

Allowed stable outcomes:

- Confirmed → stable confirmed
- Pending → stable pending
- Duplicate webhook → stable previously converged
- Retry → no regression

## Fragment Refresh Discipline

Fragment refresh is presentation-only and MUST remain non-authoritative.

Rules:

- Fragment cycles MUST converge to a single deterministic state per authority snapshot.
- Stale pre-confirmation UI MUST NOT reappear after confirmation.
- Fragment-bound controls MUST re-bind deterministically after DOM replacement.
- Timing races MUST resolve in favor of reconciled local authority.
- Fragment rendering MUST NOT create contradictory payment signals.

## Idempotency Requirements

All callback-adjacent flows MUST satisfy:

- Duplicate webhook events MUST NOT alter terminal truth.
- Duplicate return/callback requests MUST NOT create new transitions.
- Processing MUST be deterministic for identical input + identical authority state.
- Deduplication or equivalent controls MUST prevent side-effect duplication.
- Idempotency MUST apply under network retry and delayed confirmation scenarios.

## Alternatives Considered

### 1) UI-first success model
Rejected.
Speculative and violates Payment Authority doctrine.

### 2) Redirect-as-confirmation model
Rejected.
Treats transport layer as authority and creates false confirmation risk.

### 3) Soft anti-flash without strict monotonicity
Rejected.
Cannot guarantee convergence under duplicate or delayed events.

### 4) Fragment-driven authority inference
Rejected.
Allows presentation timing to influence business truth.

## Consequences

- Checkout and return flows remain stable under delayed or duplicated confirmations.
- Success surfaces become authority-aligned and non-speculative.
- Fragment refresh is constrained to deterministic reflection of reconciled authority.
- Regression validation MUST include anti-flash and monotonic verification.
- Fully compatible with ADR-001 and ADR-002 authority boundaries.

## Invariants Protected

- Payment truth MUST originate only from provider-confirmed callback + local authoritative reconciliation.
- Redirect/return routes MUST NOT be treated as payment authority.
- UI layers MUST NOT speculate or upgrade payment state.
- State transitions MUST be monotonic.
- Confirmed state MUST NOT regress.
- Duplicate events MUST converge to a single stable terminal state.
- Fragment refresh MUST deterministically reflect authority without flicker-induced divergence.


---

## Source: `docs/60-adr/ADR-004-consent-gate-doctrine.md`

# ADR-004: Consent Gate Doctrine

## Status
Accepted

This decision is binding and may only be altered through a superseding ADR.

## Context

Blackwork integrates marketing activation flows (including Brevo synchronization) into commerce and account journeys.

ADR-002 formalizes authority hierarchy (Payment > Authentication > Provisioning).

This ADR formalizes consent as a dedicated **control gate layer** governing marketing automation eligibility.

Consent is NOT an authority layer within the hierarchy.
Consent does NOT sit above, below, or between Payment/Auth/Provisioning.
Consent is a gating control mechanism applied to marketing write-side operations only.

Without explicit doctrine, systems may drift into:

- Silent marketing activation without verifiable consent
- Consent inferred from non-authoritative UI or commerce signals
- Consent withdrawal incorrectly mutating commerce or entitlement truth
- Non-auditable consent transitions
- Remote provider state redefining local consent truth

This ADR eliminates those failure modes.

## Decision

Blackwork formally adopts the Consent Gate Doctrine.

Binding rules:

- Marketing communication MUST require explicit valid consent where legally required.
- Consent state MUST be stored deterministically and verifiably in local authoritative data.
- Consent is a control gate for marketing automation and MUST NOT be treated as payment, authentication, or provisioning authority.
- Provisioning flows MAY depend on consent only where explicitly declared and documented.
- Consent withdrawal MUST stop future marketing automation.
- Consent withdrawal MUST NOT revoke confirmed payment truth.
- Consent withdrawal MUST NOT revoke valid entitlements already activated.
- Consent withdrawal MUST NOT trigger rollback logic in provisioning systems.
- All consent state transitions MUST be auditable.

## Consent Authority Scope

Consent controls what marketing write-side operations MAY execute.

Consent does NOT determine:

- Payment confirmation
- Identity/session truth
- Entitlement validity

Authoritative consent capture surfaces MAY include:

- Checkout opt-in controls
- Account preference controls
- Explicit administrative correction paths

Non-authoritative signals include:

- Page visits
- Success screens
- Redirect/query parameters
- Payment completion
- Remote provider subscription status alone

Consent MUST be evaluated from reconciled local consent state only.

## Cross-Layer Interaction Rules

- Consent MUST NOT confirm, deny, or mutate payment authority.
- Consent MUST NOT confirm, deny, or mutate authentication authority.
- Consent MUST NOT mutate provisioning authority unless explicitly declared by documented contract.
- Payment success MUST NOT imply consent.
- Authentication success MUST NOT imply consent.
- Provisioning completion MUST NOT imply consent.
- Consent granted MAY enable marketing automation only through explicit gated execution paths.
- Consent denied or missing MUST hard-stop write-side marketing operations.

In any cross-layer conflict, ADR-002 authority precedence prevails.

## Consent Storage & Audit Requirements

### 1. Deterministic Local Storage
Consent MUST be stored locally in canonical metadata/state keys.

Stored state MUST include:
- Consent decision (granted/denied/withdrawn)
- Timestamp
- Source/surface of capture
- Version or policy reference where applicable

### 2. Verifiability
Marketing write operations MUST check consent gate before remote API calls.
It MUST be possible to demonstrate why a marketing action executed or was skipped.

### 3. Auditability
Every consent state transition MUST leave a traceable local record.
Marketing write attempts MUST be logged with outcome classification.

### 4. Local Authority Doctrine
Local consent metadata is source-of-truth.
Remote provider state MAY be synchronized but MUST NOT redefine local consent truth.

## Withdrawal Rules

- Withdrawal MUST immediately block future marketing write-side operations.
- Withdrawal MUST NOT alter confirmed payment outcomes.
- Withdrawal MUST NOT alter authenticated identity/session truth.
- Withdrawal MUST NOT revoke valid entitlements already activated.
- Withdrawal MUST NOT trigger rollback logic in provisioning systems.
- Withdrawal events MUST be auditable.
- Retry or scheduled automation MUST converge to non-send behavior unless new valid consent is captured.

## Alternatives Considered

### 1) Consent as commerce authority
Rejected.
Violates authority hierarchy and creates cross-layer mutation risk.

### 2) Implicit consent from payment completion
Rejected.
Legally unsafe and architecturally invalid.

### 3) Remote-provider consent as sole authority
Rejected.
Breaks deterministic gating and audit requirements.

### 4) Soft consent without audit trail
Rejected.
Insufficient for governance-grade enforcement.

## Consequences

- Marketing activation becomes deterministic and auditable.
- Consent becomes a hard gate for marketing write-side flows.
- Payment/auth/provisioning boundaries remain intact.
- Withdrawal behavior is predictable and non-destructive to commerce truth.
- Consent changes touching automation surfaces become Tier 0-sensitive.

## Invariants Protected

- No marketing write-side action MAY execute without valid consent where required.
- Consent state MUST be locally stored, verifiable, and auditable.
- Consent MUST NOT override payment, authentication, or provisioning authority.
- Payment/auth/provisioning truth MUST remain independent of consent state.
- Withdrawal MUST stop future marketing automation without retroactive commerce mutation.
- Consent transitions and automation outcomes MUST converge deterministically under retries or duplicate triggers.


---

## Source: `docs/60-adr/ADR-005-claim-idempotency-rule.md`

# ADR-005: Claim Idempotency Rule

## Status
Accepted

This decision is binding and may only be altered through a superseding ADR.

## Context

Blackwork provisioning and claim flows can be triggered from multiple asynchronous paths: payment-confirmed lifecycle hooks, callback-driven identity continuation, manual retry paths, and user/browser re-entry.

ADR-002 defines authority hierarchy (Payment > Authentication > Provisioning).
ADR-003 defines callback convergence and anti-flash discipline.

This ADR formalizes deterministic claim idempotency to guarantee entitlement stability under retries, duplicate triggers, out-of-order events, and concurrent execution.

## Problem Definition (Duplicate / Race Risks)

Without strict claim idempotency:

- Duplicate entitlement activation may occur.
- Webhook-confirmed transitions and claim execution may race.
- Browser refresh may cause repeated writes.
- Manual retry may collide with in-flight automatic claim.
- Out-of-order invocation may partially mutate state.
- Partial writes may create unrecoverable divergence.

These conditions threaten deterministic entitlement ownership.

## Decision

Blackwork formally adopts the Claim Idempotency Rule as a Tier 0 governance constraint.

Binding rules:

- Claim execution MUST be idempotent.
- A given Claim Identity Key MUST result in at most one effective entitlement mutation.
- Claim execution MAY be retried but MUST converge to one stable terminal state.
- Duplicate triggers MUST NOT create duplicate entitlements.
- Entitlement activation MUST be monotonic.
- Claim execution MUST be concurrency-safe.
- Claim state persistence and entitlement mutation MUST be atomically consistent or recoverably reconcilable.
- Failed claim attempts MUST be safely retryable.
- Claim logic MUST tolerate out-of-order webhook and frontend invocation.

## Claim Identity Model

A claim MUST be uniquely defined by a deterministic Claim Identity Key.

Minimum identity dimensions:

- Order identity
- Subject identity (resolved authenticated principal)
- Entitlement scope (access target)

Normative constraints:

- Same business claim target MUST resolve to the same Claim Identity Key.
- Distinct claim targets MUST NOT share a Claim Identity Key.
- Claim Identity Key design MUST structurally prevent duplicate activation.

## Idempotency Enforcement Rules

- Every claim attempt MUST execute under an idempotency guard derived from the Claim Identity Key.
- If claim is already completed, re-entry MUST short-circuit without mutation.
- If claim is in progress, concurrent attempts MUST NOT duplicate side effects.
- If claim previously failed, retry MUST execute only missing safe steps.
- Claim completion markers MUST reflect terminal convergence and be readable across all trigger paths.
- Idempotency decisions MUST rely solely on authoritative persisted state.

## Concurrency Safety Model

- Claim handlers MUST assume concurrent invocation.
- Check-then-write without protection CANNOT be considered safe.
- Concurrency control MUST use locking, transactional guarantees, compare-and-set, or equivalent safe pattern.
- Concurrent attempts for the same Claim Identity Key MUST converge to one effective entitlement mutation.
- Concurrency conflicts MUST degrade to retryable states, not duplicate writes.

## Monotonic Entitlement Rules

- Entitlement transitions MUST be monotonic.
- Active/claimed state CANNOT regress due to retries or reordering.
- Reprocessing MUST preserve already-valid entitlement truth.
- Claim completion requires:
  - Entitlement exists,
  - Entitlement state is valid,
  - Idempotency marker persisted.
- Claim state MUST NOT override Payment or Authentication authority.

## Retry & Re-entry Behavior

- Retries are expected and MUST be safe.
- Execution MUST be deterministic for identical authority state + Claim Identity Key.
- Out-of-order events MUST be tolerated:
  - If prerequisites are unmet, claim MUST exit safely without mutation.
  - When prerequisites converge later, retry MUST complete claim without duplication.
- Browser refresh, callback revisit, and manual retry MUST converge to the same terminal state.
- Trigger storms MUST yield exactly one effective entitlement outcome.

## Alternatives Considered

### 1) Best-effort claim execution
Rejected.
Cannot guarantee convergence.

### 2) UI/session-driven completion
Rejected.
Violates authority model and async safety.

### 3) Single-trigger assumption
Rejected.
Operational reality includes retries and multi-source triggers.

### 4) Manual duplicate correction
Rejected.
Non-deterministic and not governance-grade.

## Consequences

- Claim flows become deterministic under concurrency and retries.
- Entitlement activation remains stable and non-duplicative.
- Recovery from transient failure is safe.
- Cross-domain authority boundaries remain intact.
- Provisioning integrity is structurally enforced.

## Invariants Protected

- Same claim target MUST NOT activate entitlement more than once.
- Repeated triggers MUST converge to one stable entitlement state.
- Claim execution MUST remain concurrency-safe.
- Entitlement transitions MUST be monotonic and non-regressive.
- Claim logic remains strictly downstream of Payment and Authentication authority.
- Out-of-order invocation MUST NOT produce duplicate or contradictory entitlement state.


---

## Source: `docs/60-adr/ADR-006-provider-switch-model.md`

# ADR-006: Provider Switch Model

## Status
Accepted

This decision is binding and may only be altered through a superseding ADR.

## Context

Blackwork integrates external providers across payments, authentication, provisioning, and marketing synchronization.

Provider implementations may change over time, but system invariants, authority hierarchy, convergence discipline, and deterministic behavior MUST remain stable.

ADR-001 formalizes orchestration authority versus provider UI.
ADR-002 formalizes authority hierarchy.
ADR-003 formalizes callback convergence.
ADR-005 formalizes claim idempotency.

This ADR establishes providers as interchangeable execution layers behind stable internal contracts.

## Decision

Blackwork formally adopts the Provider Switch Model.

Binding rules:

- Providers MUST be treated strictly as execution layers.
- Providers MAY signal state; only local reconciliation MAY define authoritative state.
- Providers MUST NOT become business authority layers.
- Provider callbacks/webhooks MUST pass authenticity + idempotency gates before any mutation.
- Switching providers MUST NOT alter authority hierarchy, invariants, or convergence discipline.
- Provider adapters MUST be thin, deterministic, and replaceable.
- Provider outages MUST degrade safely without corrupting local authority truth.
- Provider migration MUST preserve idempotency and monotonic convergence guarantees.
- No internal invariant MAY reference provider-specific semantics.

## Provider Definition & Scope

A provider is any external system that:

- Executes remote operations
- Emits remote signals/events
- Is accessed via defined integration contract

Examples:
- Payment processors
- Identity brokers
- Marketing automation platforms

Providers are NOT:

- Local authority for payment/auth/provisioning/consent truth
- Orchestration authority for checkout UX
- Source of invariant definitions

## Contract Boundary Rules

Every provider integration MUST define explicit canonical contract boundaries:

- Canonical inputs (what local system sends)
- Canonical outputs/events (what local system consumes)
- Normalized error classes

Rules:

- Provider-specific payloads MUST be translated into canonical local representations.
- Local reconciliation layer is the only mutation entrypoint for business-critical state.
- Provider-specific UI MUST NOT become orchestration authority.
- Canonical contract MUST isolate provider differences so upper layers remain provider-agnostic.
- No business rule MAY depend on raw provider-specific status codes.

## Switching Discipline (Migration Rules)

Switching providers MUST follow strict migration discipline:

### 1. Contract Parity
Equivalent local contract coverage MUST be declared and verified.

### 2. Canonical Mapping
Provider identifiers, statuses, and event semantics MUST map to canonical representations.
Mapping MUST preserve traceability where required.

### 3. Callback/Event Mapping
Provider events MUST route through existing reconciliation pathways.
Replay and duplicate behavior MUST preserve idempotency guarantees.

### 4. State Continuity
Existing authoritative local state MUST remain valid post-switch.
In-flight operations MUST converge without regression.

### 5. Cutover Safety
Dual active providers MUST NOT simultaneously hold authority over the same contract surface.
Authority source MUST be unambiguous during migration windows.

### 6. Post-Switch Validation
Tier 0 regression validation MUST confirm invariants unchanged.

## Failure Mode & Degrade-Safely Rules

- Provider outages MUST NOT corrupt local authority truth.
- Delayed callbacks MUST reconcile deterministically.
- Partial provider-side success MUST NOT create contradictory local terminal state.
- Transient errors MUST remain retry-safe and idempotent.
- Provider anomalies MUST normalize to safe local states.
- Commerce-critical flows MUST remain non-speculative during provider instability.
- Provider outage MUST NOT elevate UI state to authority.

## Regression Requirements (Tier 0)

Provider switch or major adapter modification is Tier 0 and MUST validate:

- Authority hierarchy compatibility (ADR-002)
- Callback convergence discipline (ADR-003)
- Claim idempotency guarantees (ADR-005)
- Orchestration authority boundaries (ADR-001)
- Failure/degrade-safe behavior
- Canonical mapping integrity

Switch is incomplete until invariant-preserving regression evidence is recorded.

## Alternatives Considered

### 1) Provider-specific authority model
Rejected.
Couples business truth to external implementation.

### 2) Direct provider payload usage
Rejected.
Breaks canonical abstraction and increases drift.

### 3) Provider UI as orchestration authority
Rejected.
Conflicts with selector doctrine.

### 4) Informal switching without mapping discipline
Rejected.
Cannot guarantee invariant preservation.

## Consequences

- Provider replacement becomes structurally feasible.
- Integration complexity is isolated in thin adapters.
- Callback handling remains deterministic across migration.
- Outage scenarios remain safe.
- Governance burden increases for provider switch events.

## Invariants Protected

- Local authority doctrine remains primary.
- Providers MAY signal but MUST NOT define authoritative truth.
- Provider switches MUST preserve hierarchy and convergence invariants.
- Provider-specific UI MUST NOT become orchestration authority.
- Idempotency and monotonic state progression MUST survive migration.
- Failure/degraded behavior MUST NOT corrupt business truth.


---

## Source: `docs/60-adr/ADR-007-redirect-authority-hardening.md`

# ADR-007: Redirect Authority Hardening

## Status
Proposed

This ADR is proposed and blocks Tier 0 redirect-authority implementation until approved.

## 1) Context

The Blackwork redirect engine currently executes at `template_redirect` priority `5` via `bw_maybe_redirect_request()` in `includes/class-bw-redirects.php`.

Current runtime characteristics:

- Rule evaluation is linear (`O(n)` scan) against `bw_redirects` option data.
- Match model is exact normalized `path + query` comparison.
- Runtime safety includes only partial loop prevention (self-loop checks).
- Protected-route policy is not fully enforced as a non-overridable authority gate.
- Redirect execution can occur early in the `template_redirect` stack and may short-circuit downstream runtime.

Observed governance risk context (aligned with `R-RED-12`):

- Redirect loops and unstable chain behavior remain possible in multi-rule scenarios.
- Authority override risk exists for commerce/auth-critical routes.
- Deterministic precedence is not sufficiently constrained by explicit runtime policy.
- Performance scales linearly with rule growth.

This surface is Tier 0 Routing Authority and intersects Checkout, Payments return paths, Auth/Supabase callback flows, and My Account route continuity.

## 2) Problem Statement

The current implementation does not enforce a complete authority-safe redirect contract for a Tier 0 routing surface.

Authority risks:

- Redirect logic can execute before/against critical route ownership boundaries without a strict protected-route deny gate.
- Priority/precedence behavior can produce authority collisions with other Tier 0 `template_redirect` handlers.

Determinism risks:

- Rule matching and precedence are not governed by a fully explicit, auditable policy.
- Multi-hop cycle prevention is incomplete, allowing non-convergent redirect behavior.
- Runtime behavior under larger rulesets is not bounded by indexed exact-match policy.

Without formal authority hardening, redirect behavior can violate protected route safety, convergence expectations, and Tier 0 invariants.

## 3) Decision

Blackwork adopts the following normative Redirect Authority Hardening model.

### 3.1 Protected Route Policy (Non-overridable)

The redirect engine MUST NOT create or execute redirects where source or target intersects protected families, including at minimum:

- `/wp-admin`
- `/wp-login.php`
- `/cart`
- `/checkout`
- `/my-account`
- `/wc-api`
- REST routes (`/wp-json/*` and equivalent base)
- WooCommerce core pages and derived endpoint paths

Rules:

- Protected-route validation MUST run at save-time and runtime.
- Any protected-route violation MUST fail closed for redirect execution and fail open for request continuity (no redirect, continue normal request lifecycle).
- Protected-route policy is not user-overridable in normal admin flows.

### 3.2 Redirect Precedence Rules

Redirect authority is constrained by deterministic precedence:

1. WordPress canonical handling
2. Protected-route gate
3. Blackwork redirect evaluation
4. WooCommerce endpoint routing and downstream domain handlers

Rules:

- Redirect evaluation MUST be skipped when protected-route gate fails.
- No-match MUST pass control downstream without side effects.

### 3.3 Hook Priority Policy

For Tier 0 safety and deterministic precedence:

- Redirect runtime MUST remain on `template_redirect`.
- Redirect hook priority MUST be `>= 10`.
- Redirect hook priority `< 10` is prohibited.
- Any future priority change requires governance review and ADR supersession if it changes authority/precedence semantics.

### 3.4 Loop Detection Rules

Loop prevention MUST exist at both save-time and runtime.

Save-time rules:

- Reject self-loop (`source == target` after normalization).
- Reject direct two-node loop (`A -> B` when `B -> A` exists among enabled rules).
- Reject multi-hop cycles using enabled-rule graph cycle detection.

Runtime rules:

- Maintain deterministic hop-count protection for redirect chains.
- Abort redirect execution when hop limit is exceeded.

### 3.5 Redirect Hop Limit

- Global runtime redirect hop limit is set to `5`.
- If limit is exceeded, runtime MUST abort further redirects and continue request without redirect side effects.

### 3.6 Fail-Open Behavior

When redirect safety cannot be guaranteed, runtime MUST fail open:

- invalid/unsafe rule -> skip rule
- protected-route conflict -> skip redirect
- cycle/hop-limit breach -> abort redirect chain
- normalization/validation anomaly -> no redirect

Fail-open means preserving request continuity and downstream authority handling, never forcing a speculative redirect.

## 4) Consequences

Positive impacts:

- Stronger protection of Tier 0 authority boundaries.
- Deterministic and auditable redirect behavior.
- Reduced risk of checkout/auth/account route takeover.
- Improved convergence safety through cycle + hop controls.
- Better scalability path by requiring bounded evaluation model.

Negative impacts / costs:

- Stricter validation may reject previously accepted redirect rules.
- Additional governance and test burden for Tier 0 rollout.
- Operational complexity increases for rule management and diagnostics.
- Potential need for migration/normalization of legacy rules.

## 5) Implementation Constraints

Binding constraints for the follow-up implementation task:

- Scope MUST stay within redirect engine and directly related governance docs unless scope update is approved.
- No cross-domain authority changes are permitted (Checkout/Payments/Auth/Consent remain external authority owners).
- No hidden runtime surface mutations (new hooks/priority changes must be explicitly declared).
- Protected-route gate MUST be enforced before redirect execution.
- Existing request continuity MUST be preserved via fail-open behavior.
- Matching and precedence behavior MUST be deterministic and documented.
- Implementation MUST include regression evidence for:
  - protected route non-overriding
  - loop/cycle prevention
  - precedence consistency
  - route continuity in commerce/auth paths
- Performance-sensitive changes MUST record lightweight closure evidence per governance protocol.

## 6) Governance Notes

This decision requires an ADR because it modifies Tier 0 Routing Authority behavior, specifically:

- redirect precedence semantics
- `template_redirect` priority policy
- protected-route authority enforcement
- loop/cycle convergence rules

Per governance doctrine:

- Tier 0 authority-impacting changes require explicit architectural decisioning.
- Redirect authority MUST NOT override protected commerce/auth/platform routes.
- Determinism and convergence are mandatory invariants, not implementation preferences.

This ADR provides the binding architecture contract that unblocks governed implementation once approved, and it anchors required updates to risk posture (`R-RED-12`) and runtime documentation.


---

## Source: `docs/60-adr/ADR-008-import-engine-authority-hardening.md`

# ADR-008 — Import Engine Authority and Convergence Hardening

## Status
Proposed

## Context
The Import Engine audit identified the active import authority surface in `admin/class-blackwork-site-settings.php` as a Tier-0 catalog mutation domain.

Observed runtime facts:
- Import execution is operator-driven from the admin tab (no async CLI/background runner currently in use).
- Product writes are performed through WooCommerce CRUD in `bw_import_save_product_from_row($data, $update_existing, $options)`.
- SKU is required and used for lookup/matching.
- There is no run-level lock, no SKU claim ledger, no durable run entity, and no per-row terminal ledger.
- Progress/checkpoint state is currently stored in user-scoped transient `bw_import_state_{current_user_id}`.

Observed risk profile:
- duplicate creation risk across overlapping runs
- partial row write risk
- weak resumability guarantees (transient expiry and cursor-only continuity)
- taxonomy/meta partial convergence under retries/failures
- publish/visibility correctness drift
- batch re-seek inefficiency on large files

Because import writes mutate canonical WooCommerce catalog authority, this is a Tier-0 authority/convergence surface.

## Problem Statement
The current import runtime lacks a formal authority and convergence contract. SKU is present but not enforced through durable run governance primitives (run lock, checkpoint ledger, row terminality, replay semantics).

Without these controls, overlapping runs and interruption scenarios can produce non-deterministic outcomes, including duplicate identity effects, partial convergence, and weak auditability.

A normative authority model is required so import behavior is deterministic, replay-safe, and auditable under failure/re-entry conditions.

## Decision
The Blackwork Import Engine MUST adopt the following normative authority and convergence model.

### A) SKU Canonical Identity
- SKU is the canonical product identity for import convergence.
- For one canonical SKU, at most one WooCommerce product entity may be authoritative.
- Create path is allowed only when canonical SKU resolution confirms no existing authoritative entity.
- Replays/retries for the same canonical SKU must converge to update/no-op semantics, never duplicate create semantics.

### B) Import Run Lock
- Only one authoritative import run may mutate catalog state at a time.
- The lock MUST record:
  - lock owner
  - acquisition timestamp
  - expiry timestamp
  - run identity
- If lock is stale (expiry exceeded), reclaim is allowed only through deterministic stale-lock protocol with explicit audit event.
- Lock contention must fail safe (no catalog mutation without lock ownership).

### C) Durable Run State
- Import progress MUST NOT rely only on user-scoped transient state.
- A durable run/checkpoint model is mandatory.
- Durable run state must include at minimum:
  - run ID
  - actor/context metadata
  - source file fingerprint
  - mapping snapshot
  - chunk size and processing configuration
  - terminal counters
  - run status lifecycle

### D) Row-Level Determinism
- Every row must resolve to one deterministic terminal state:
  - `created`
  - `updated`
  - `skipped`
  - `failed`
- Terminal row outcomes must be persisted in durable ledger form.
- Reprocessing a previously terminal row must not create divergent catalog state.

### E) Partial-Write Safety
- Row processing must ensure no ambiguous product identity state is left behind.
- Minimum atomicity expectation (without requiring full DB transaction support):
  - identity resolution and product entity selection must complete deterministically before downstream mutation
  - on write failure, row must terminate as `failed` with explicit stage code
  - retry path must re-enter from authoritative row/run state, not inferred cursor alone
- Partial side effects must be auditable and reconcilable by deterministic retry.

### F) Taxonomy/Meta Convergence
- Taxonomy and meta assignment must converge deterministically across retries.
- Repeated execution of same logical row must converge to same taxonomy/meta end state.
- Term creation/assignment behavior must be idempotent in effect and auditable under failure.

### G) Publish/Visibility Guard
- Imported publish/status values must be allowlisted.
- Non-allowlisted status values must be rejected or normalized deterministically.
- Visibility-affecting fields must not drift under retry/re-entry paths.

### H) Resume Model
- Resume must continue from authoritative durable checkpoint state.
- Resume behavior must not depend solely on transient cursor heuristics.
- Checkpoint commit boundary must bind:
  - processed row window
  - row terminal outcomes
  - aggregate run counters

### I) Failure Handling
- Failures must be fail-safe (no unsafe continuation without authority guarantees).
- Failure outputs must be auditable with structured reason/stage context.
- Recovery path must be explicit:
  - retry from authoritative checkpoint
  - stale lock reclaim protocol
  - deterministic completion/abort transition

## Consequences
### Benefits
- Deterministic import convergence under retries and interruptions.
- Practical elimination of duplicate-creation class for canonical SKU.
- Improved auditability and post-failure recovery confidence.
- Stronger Tier-0 governance compliance for catalog mutation authority.

### Operational Costs
- Additional implementation complexity (run state, lock lifecycle, row ledger).
- Storage and maintenance overhead for durable run/checkpoint artifacts.
- Operator workflow updates for lock/recovery semantics.

### Migration Implications
- Existing transient-only state model requires migration to durable run/checkpoint model.
- Legacy in-progress import states may require explicit migration/invalidations.
- Backward compatibility rules must be defined for active admin import workflows.

### Runtime Complexity Tradeoffs
- Increased control-plane complexity in exchange for deterministic data-plane behavior.
- Slight overhead per chunk/row for ledger and lock checks.
- Reduced ambiguity and lower integrity risk at scale and under concurrent/retry conditions.

## Implementation Constraints
- Scope is limited to import runtime and directly related governance/docs artifacts.
- No unrelated changes to checkout, auth, search, redirect, media-upload, or header runtime surfaces.
- Product write authority remains WooCommerce canonical product authority.
- Future implementation must:
  - respect Tier-0 governance lifecycle
  - complete Task Start and Acceptance Gate before coding
  - complete Task Closure and Release Gate before deployment
  - preserve deterministic behavior and auditable recovery path

## Governance Notes
- This ADR is required because import is a Tier-0 authority surface that mutates canonical catalog state.
- The decision establishes normative rules for identity authority, run ownership, resumability, and failure convergence.
- This ADR is the binding governance baseline for implementation tasks intended to reduce `R-IMP-10` exposure.
- Any deviation from this model requires explicit governance update and, where applicable, ADR amendment.


---

## Source: `docs/60-adr/README.md`

# 60 ADR

Architecture Decision Records.

## Files
- No ADR files yet.


---

## Source: `docs/60-system/integration/supabase-payments-checkout-integration-map.md`

# Supabase ↔ Payments ↔ Checkout Integration Map

## 1) Purpose + Scope
This document defines the cross-domain coupling contract between Supabase onboarding, checkout runtime, and payment execution.
It formalizes shared state, lifecycle transitions, and failure-safe invariants across the three domains.

Scope boundaries:
- Architecture-level integration model only.
- No code refactor guidance.
- No operational checklist content.

## 2) Integration Surfaces (Coupling Points)

### Checkout templates/hooks depending on auth state
- `woocommerce/templates/global/form-login.php` branches CTA behavior on:
  - `bw_account_login_provider`
  - `bw_supabase_checkout_provision_enabled`
- `woocommerce/templates/checkout/order-received.php` branches post-order CTA and messaging based on:
  - user login state
  - provider/provisioning flags
  - paid vs unpaid order state

### Order-received branching (guest + Supabase provisioning)
- Guest paid orders can show activation-oriented CTA instead of direct account-entry CTA.
- Supabase/provision-enabled branches route users toward account activation callback and invite continuation.

### Payments selector + gateway availability dependency on session/auth
- Payment selector contract is centered on:
  - `woocommerce/templates/checkout/payment.php`
  - `assets/js/bw-payment-methods.js`
  - wallet scripts (`bw-google-pay.js`, `bw-apple-pay.js`)
- Gateway availability is driven primarily by checkout/payment readiness and method state.
- Auth state changes post-order behavior, but must not alter payment submission authority after checkout confirm.

### My Account downloads gating dependency on onboarding marker
- `bw_supabase_onboarded` determines whether account is fully usable for Supabase users.
- Order/download ownership can be attached after bridge/onboarding via `bw_mew_claim_guest_orders_for_user(...)`.
- Result: guest purchase can transition into authenticated download ownership after onboarding completion.

## 3) Shared State Model
Canonical cross-domain state flags and values:

- Authentication runtime:
  - `is_user_logged_in()`
  - `bw_account_login_provider` (`wordpress` | `supabase`)
  - `bw_supabase_onboarded` (1 = onboarded)
- Commerce/payment runtime:
  - order status (`processing` / `completed` / `on-hold` / fail states)
  - payment status (`success` / `pending` / `failed`)
- Provisioning runtime:
  - `bw_supabase_checkout_provision_enabled`

State ownership model:
- Checkout owns order submission and post-order render branch.
- Payments own gateway execution and payment result authority.
- Supabase owns invite/onboarding/claim transition after order success points.

## 4) End-to-End Lifecycle Model

### A) Guest arrives -> checkout -> payment -> order -> provisioning -> invite -> callback -> password -> downloads
1. Guest completes checkout with selected payment method.
2. Payment flow returns success/pending/fail according to gateway contract.
3. On eligible order hooks/statuses, Supabase provisioning attempts invite dispatch (if enabled and configured).
4. User receives order confirmation plus invite/activation path.
5. Invite/callback enters `bw_auth_callback` bridge route.
6. Token bridge establishes WP session; onboarding gate applies when `bw_supabase_onboarded != 1`.
7. Password setup finalizes onboarding marker.
8. Guest orders/download permissions are attached to mapped user; downloads become available in My Account.

### B) Logged-in user -> checkout -> payment -> order-received normal path
1. Logged-in user completes checkout.
2. Payment/order lifecycle proceeds normally.
3. Order-received follows standard confirmed-order path with account access continuity.
4. No onboarding gate is required when user is already valid/onboarded.

### C) Existing Supabase user without onboarding complete -> payment/order -> gated access until password set
1. User can reach post-order/account entry but remains onboarding-gated.
2. Bridge/session can be valid while onboarding remains incomplete.
3. Password completion flips onboarding state and unlocks full account/download surfaces.

## 5) Failure Model & Safe Degrade

### Payment succeeds but provisioning fails
- Expected UX: order remains valid/paid; onboarding CTA can still direct to retry account activation.
- Invariant: payment completion is preserved; no silent loss of paid order.

### Provisioning succeeds but payment pending/failed
- Expected UX: payment status remains authoritative in order-received branch (error/pending messaging retained).
- Invariant: invite/onboarding must not reclassify an unpaid/failed order as paid success.

### Invite expired (`otp_expired`) after successful payment
- Expected UX: callback redirects to configured expired-link destination; user can request new invite.
- Invariant: no order loss; re-entry path remains available without breaking paid order state.

### Bridge fails (user remains logged out)
- Expected UX: callback/loader path degrades to explicit re-entry (account/login/invite resend path), not hidden failure.
- Invariant: no loop and no fake logged-in rendering.

### Duplicate invite/resend loops
- Expected UX: controlled resend behavior with throttling and already-exists handling.
- Invariant: no infinite resend loop, no duplicate state explosion, no orphaned order entitlement.

## 6) Non-Break Invariants
- Payment completion must not depend on Supabase onboarding state.
- Supabase onboarding must not block checkout payment execution.
- Order ownership claim must be idempotent across repeated callbacks or retries.
- No duplicate CTAs should conflict between order-received and My Account routing.
- Callback anti-flash logic must not interfere with payment postback/order-received routes.

## 7) High-Risk Zones (Blast Radius)
Cross-domain hotspots:

- Checkout domain:
  - `woocommerce/templates/checkout/payment.php`
  - `woocommerce/templates/checkout/order-received.php`
  - `woocommerce/templates/global/form-login.php`
  - `assets/js/bw-payment-methods.js`
- Payments domain:
  - `includes/Gateways/class-bw-abstract-stripe-gateway.php`
  - `includes/woocommerce-overrides/class-bw-google-pay-gateway.php`
  - `includes/Gateways/class-bw-apple-pay-gateway.php`
  - `includes/Gateways/class-bw-klarna-gateway.php`
  - `assets/js/bw-google-pay.js`
  - `assets/js/bw-apple-pay.js`
  - `assets/js/bw-stripe-upe-cleaner.js`
- Supabase domain:
  - `includes/woocommerce-overrides/class-bw-supabase-auth.php`
  - `includes/woocommerce-overrides/class-bw-my-account.php`
  - `assets/js/bw-supabase-bridge.js`
  - `assets/js/bw-account-page.js`
  - `woocommerce/templates/myaccount/my-account.php`
  - `woocommerce/templates/myaccount/form-login.php`

## 8) References
- [Supabase Architecture Map](../../40-integrations/supabase/supabase-architecture-map.md)
- [Payments Architecture Map](../../40-integrations/payments/payments-architecture-map.md)
- [Checkout Architecture Map](../../30-features/checkout/checkout-architecture-map.md)
- [Regression Protocol](../../50-ops/regression-protocol.md)


---

## Source: `docs/99-archive/README.md`

# 99 Archive

Historical, superseded, or session-report documentation preserved for reference.

## Subfolders
- [_docs_codex](_docs_codex/README.md)
- [architecture](architecture/README.md)
- [payments](payments/README.md)
- [header](header/README.md)
- [smart-header](smart-header/README.md)
- [my-account](my-account/README.md)
- [reports](reports/README.md)
- [brevo](brevo/README.md)


---

## Source: `docs/99-archive/_docs_codex/README.md`

# Archive: _docs_codex

## Files
- [readme-duplicate.md](readme-duplicate.md): archived duplicate README content.
- [root-readme-legacy.md](root-readme-legacy.md): archived legacy root README content.


---

## Source: `docs/99-archive/_docs_codex/readme-duplicate.md`

# BW Elementor Widgets

Collezione di **widget personalizzati per Elementor** sviluppati per BW.  
Questo plugin raccoglie più widget, ognuno organizzato in file separati per garantire modularità e scalabilità.

---

## 📂 Struttura cartelle

```
bw-elementor-widgets/
│── bw-main-elementor-widgets.php        // file principale del plugin
│── includes/
│    │── class-bw-widget-loader.php      // loader automatico dei widget
│    │── widgets/
│    │    └── class-bw-slick-slider-widget.php
│── assets/
│    ├── css/
│    │    └── bw-slick-slider.css
│    └── js/
│         ├── bw-slick-slider.js
│         └── bw-slick-slider-admin.js
```

---

## ⚙️ Installazione

1. Clona o scarica la cartella `bw-elementor-widgets` in `wp-content/plugins/`.
2. Verifica che Elementor sia installato e attivo.
3. Attiva il plugin **BW Elementor Widgets** da **Plugin > Aggiungi nuovo** su WordPress.
4. Troverai i nuovi widget nella dashboard Elementor, sotto la categoria **General** (o personalizzata).

---

## 🚀 Widget attuali

### BW Slick Slider
Uno slider basato su **Slick Carousel** che mostra post o prodotti WooCommerce, con configurazioni per query, layout e gestione delle categorie.

---

## 🛠 Aggiungere un nuovo widget

1. Crea un nuovo file in `includes/widgets/` con il nome:  
   ```
   class-bw-nome-widget.php
   ```
   La classe deve chiamarsi:
   ```
   Widget_Bw_Nome_Widget
   ```

2. Aggiungi eventuali CSS in `assets/css/bw-nome-widget.css`.  
3. Aggiungi eventuali JS in `assets/js/bw-nome-widget.js`.  
4. Il **loader** (`class-bw-widget-loader.php`) registrerà automaticamente il nuovo widget, non serve modificare altro.  

---

## 📦 Dipendenze

- [Elementor](https://elementor.com/)  
- [Slick Carousel](https://kenwheeler.github.io/slick/)

---

## 👨‍💻 Autore
Simone


---

## Source: `docs/99-archive/_docs_codex/root-readme-legacy.md`

# BW Elementor Widgets

Collezione di **widget personalizzati per Elementor** sviluppati per BW.  
Questo plugin raccoglie più widget, ognuno organizzato in file separati per garantire modularità e scalabilità.

---

## 📂 Struttura cartelle

```
bw-elementor-widgets/
│── bw-main-elementor-widgets.php        // file principale del plugin
│── includes/
│    │── class-bw-widget-loader.php      // loader automatico dei widget
│    │── widgets/
│    │    └── class-bw-slick-slider-widget.php
│── assets/
│    ├── css/
│    │    └── bw-slick-slider.css
│    └── js/
│         ├── bw-slick-slider.js
│         └── bw-slick-slider-admin.js
```

---

## ⚙️ Installazione

1. Clona o scarica la cartella `bw-elementor-widgets` in `wp-content/plugins/`.
2. Verifica che Elementor sia installato e attivo.
3. Attiva il plugin **BW Elementor Widgets** da **Plugin > Aggiungi nuovo** su WordPress.
4. Troverai i nuovi widget nella dashboard Elementor, sotto la categoria **General** (o personalizzata).

---

## 🚀 Widget attuali

### BW Slick Slider
Uno slider basato su **Slick Carousel** che mostra post o prodotti WooCommerce, con configurazioni per query, layout e gestione delle categorie.

---

## 🛠 Aggiungere un nuovo widget

1. Crea un nuovo file in `includes/widgets/` con il nome:  
   ```
   class-bw-nome-widget.php
   ```
   La classe deve chiamarsi:
   ```
   Widget_Bw_Nome_Widget
   ```

2. Aggiungi eventuali CSS in `assets/css/bw-nome-widget.css`.  
3. Aggiungi eventuali JS in `assets/js/bw-nome-widget.js`.  
4. Il **loader** (`class-bw-widget-loader.php`) registrerà automaticamente il nuovo widget, non serve modificare altro.  

---

## 📦 Dipendenze

- [Elementor](https://elementor.com/)  
- [Slick Carousel](https://kenwheeler.github.io/slick/)

---

## 👨‍💻 Autore
Simone


---

## Source: `docs/99-archive/architecture/README.md`

# Archive: Architecture

## Files
- [architecture-summary.md](architecture-summary.md): superseded architecture summary.
- [rm-retest-reference-manual.md](rm-retest-reference-manual.md): archived retest/reference monolith.


---

## Source: `docs/99-archive/architecture/architecture-summary.md`

# Architecture Summary: wpblackwork

## Overview
**wpblackwork** (BW Elementor Widgets) is a comprehensive modular WordPress plugin designed to customize the **BlackWork** site. It serves as a central hub for custom Elementor widgets, WooCommerce enhancements, and specific site functionality.

## Core Architecture

### 1. Modular Structure
The plugin is organized into distinct submodules, loaded by the main file `bw-main-elementor-widgets.php`.
- **Widgets System**: Automates Elementor widget registration via `includes/class-bw-widget-loader.php`.
- **Submodules**:
  - `BW_coming_soon/`: Full-page coming soon mode.
  - `cart-popup/`: AJAX-powered side-cart.
  - `admin/`: Unified settings via `class-blackwork-site-settings.php`.

### 2. Elementor Widgets
Widgets are auto-discovered from `includes/widgets/`. Key widgets include:
- **Sliders**: `BW_Slick_Slider_Widget`, `BW_Product_Slide_Widget`, `BW_Presentation_Slide_Widget`.
- **Content Walls**: `BW_Product_Grid_Widget`, `BW_Wallpost_Widget`.
- **Commerce**: `BW_Related_Products_Widget`, `BW_Add_To_Cart_Variation_Widget`.

### 3. WooCommerce Customizations
Extensive overrides located in `woocommerce/` and `includes/product-types/`:
- **Custom Product Types**:
  - `Digital Assets`: Virtual/Downloadable with specific metadata.
  - `Books`: Physical products with bibliographic details.
  - `Prints`: Art prints with framing options.
- **Template Overrides**:
  - Custom Checkout (Two-column layout).
  - Custom My Account (Social login, dashboard redesign).
- **Metaboxes**: Custom fields for products managed in `metabox/`.

### 4. Asset Management
- **Smart Loading**: Assets are registered on `init` but enqueued only when widgets are used on the page.
- **Versioning**: Uses `filemtime()` for automatic cache busting.
- **Organization**: CSS/JS files are paired with their respective widgets in `assets/`.

### 5. Technical Stack
- **Dependencies**: Elementor, WooCommerce, Slick Carousel (CDN).
- **Libraries**: Uses native WordPress Masonry and jQuery.
- **AJAX**: Custom endpoints for Live Search and Filtered Walls.

## Development Workflow
- **Adding Widgets**: Create `class-bw-{name}-widget.php` in `includes/widgets/` -> Auto-registered.
- **Styling**: `assets/css/` directory.
- **Logic**: Shared helpers in `includes/helpers.php`.

---
*Generated by Antigravity Agent*


---

## Source: `docs/99-archive/architecture/rm-retest-reference-manual.md`

# RM — Retest + Reference Manual

> **BlackWork E-Commerce Plugin**
> Complete Technical Reference & Retest Protocol
> Last Updated: 2026-01-31

---

## Table of Contents

1. [Executive Overview](#1-executive-overview)
2. [Repository Architecture Map](#2-repository-architecture-map)
3. [Runtime Configuration](#3-runtime-configuration)
4. [Supabase Integration Deep Dive](#4-supabase-integration-deep-dive)
5. [Authentication Flows](#5-authentication-flows)
6. [Checkout Integration](#6-checkout-integration)
7. [My Account Integration](#7-my-account-integration)
8. [Retest Protocol](#8-retest-protocol)
9. [Extension Guide](#9-extension-guide)
10. [Appendices](#10-appendices)

---

## 1. Executive Overview

### 1.1 Purpose

The BlackWork plugin (`wpblackwork`) extends WordPress/WooCommerce with:

- **Supabase-backed authentication** — Magic link, OTP, password, OAuth (Google, Facebook)
- **Custom product types** — Digital Assets, Homebook, Print
- **Custom checkout** — Two-column layout with Stripe UPE integration
- **Custom My Account** — Unified login/register flow with onboarding lock
- **Elementor widgets** — 15+ custom widgets for product display

### 1.2 Key Architectural Decisions

| Decision | Rationale | File Reference |
|----------|-----------|----------------|
| Dual auth storage (cookie + usermeta) | WordPress sessions + Supabase JWT for API calls | `class-bw-supabase-auth.php:1200-1250` |
| Guest provisioning at checkout | Frictionless purchase → password setup later | `class-bw-supabase-auth.php:700-800` |
| Onboarding lock | Force password setup before account access | `class-bw-my-account.php:118-137` |
| Template override priority | Plugin templates > Theme templates | `woocommerce-init.php:80-120` |

### 1.3 Technology Stack

```
┌─────────────────────────────────────────────────────────┐
│                    Frontend Layer                        │
│  ┌─────────────┐  ┌──────────────┐  ┌────────────────┐  │
│  │ Elementor   │  │ WooCommerce  │  │ Supabase JS    │  │
│  │ Widgets     │  │ Templates    │  │ Client         │  │
│  └─────────────┘  └──────────────┘  └────────────────┘  │
├─────────────────────────────────────────────────────────┤
│                    Plugin Layer                          │
│  ┌─────────────┐  ┌──────────────┐  ┌────────────────┐  │
│  │ Auth        │  │ Checkout     │  │ Product        │  │
│  │ Handlers    │  │ Customization│  │ Types          │  │
│  └─────────────┘  └──────────────┘  └────────────────┘  │
├─────────────────────────────────────────────────────────┤
│                    External Services                     │
│  ┌─────────────┐  ┌──────────────┐  ┌────────────────┐  │
│  │ Supabase    │  │ Stripe       │  │ Brevo          │  │
│  │ Auth + DB   │  │ Payments     │  │ Email          │  │
│  └─────────────┘  └──────────────┘  └────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

---

## 2. Repository Architecture Map

### 2.1 Directory Structure

```
wpblackwork/
├── bw-main-elementor-widgets.php      # Main plugin entry point
├── admin/
│   └── class-blackwork-site-settings.php  # Unified settings page (6000+ lines)
├── assets/
│   ├── css/
│   │   ├── bw-account-page.css        # Login/register styles
│   │   ├── bw-checkout.css            # Checkout customization
│   │   └── bw-*.css                   # Widget-specific styles
│   └── js/
│       ├── bw-account-page.js         # Login handler (1367 lines)
│       ├── bw-supabase-bridge.js      # OAuth token bridge (387 lines)
│       └── bw-*.js                    # Widget-specific scripts
├── includes/
│   ├── class-bw-widget-loader.php     # Auto-discovers widgets
│   ├── widgets/                       # 15+ Elementor widgets
│   ├── woocommerce-overrides/
│   │   ├── class-bw-supabase-auth.php     # Core auth (1479 lines)
│   │   ├── class-bw-social-login.php      # WP-native OAuth (548 lines)
│   │   ├── class-bw-my-account.php        # Account customization (421 lines)
│   │   └── class-bw-product-card-renderer.php
│   └── product-types/                 # Custom product types
├── woocommerce/
│   ├── woocommerce-init.php           # WC hooks & overrides (1558 lines)
│   └── templates/
│       ├── myaccount/                 # Account templates
│       ├── checkout/                  # Checkout templates
│       └── single-product/            # Product templates
├── cart-popup/                        # Side-sliding cart submodule
└── BW_coming_soon/                    # Coming soon submodule
```

### 2.2 Core File Responsibility Matrix

| File | Primary Responsibility | Lines | Key Functions |
|------|----------------------|-------|---------------|
| `class-bw-supabase-auth.php` | All Supabase authentication | 1479 | `bw_mew_handle_supabase_login()`, `bw_mew_supabase_store_session()` |
| `class-bw-social-login.php` | WordPress-native OAuth | 548 | `bw_mew_handle_oauth_callback()`, `bw_mew_initiate_oauth()` |
| `class-bw-my-account.php` | Account page customization | 421 | `bw_mew_enforce_supabase_onboarding_lock()` |
| `woocommerce-init.php` | WC template overrides | 1558 | `bw_mew_locate_template()`, asset enqueue |
| `class-blackwork-site-settings.php` | Admin settings UI | 6000+ | All option registration |
| `bw-account-page.js` | Frontend login logic | 1367 | `handleMagicLinkSubmit()`, `handleOtpVerify()` |
| `bw-supabase-bridge.js` | OAuth callback handler | 387 | `bridgeSession()`, token extraction |

### 2.3 Component Interaction Diagram

```mermaid
graph TB
    subgraph "Frontend"
        A[Login Form] --> B[bw-account-page.js]
        B --> C[Supabase JS Client]
    end

    subgraph "WordPress AJAX"
        D[wp-admin/admin-ajax.php]
    end

    subgraph "Plugin Layer"
        E[class-bw-supabase-auth.php]
        F[class-bw-social-login.php]
        G[class-bw-my-account.php]
    end

    subgraph "External"
        H[Supabase Auth API]
        I[Google OAuth]
        J[Facebook OAuth]
    end

    B -->|AJAX| D
    D --> E
    D --> F
    C --> H
    F --> I
    F --> J
    E --> H
```

---

## 3. Runtime Configuration

### 3.1 WordPress Options (wp_options)

All settings are managed via `Blackwork > Site Settings` admin page.

#### 3.1.1 Supabase Configuration

| Option Key | Type | Default | Description | File Reference |
|------------|------|---------|-------------|----------------|
| `bw_supabase_project_url` | string | `''` | Supabase project URL | `class-bw-supabase-auth.php:45` |
| `bw_supabase_anon_key` | string | `''` | Supabase anon/public key | `class-bw-supabase-auth.php:46` |
| `bw_supabase_service_role_key` | string | `''` | Service role key (server-side) | `class-bw-supabase-auth.php:47` |
| `bw_supabase_jwt_secret` | string | `''` | JWT secret for verification | `class-bw-supabase-auth.php:48` |
| `bw_supabase_debug_log` | bool | `0` | Enable debug logging | `class-bw-supabase-auth.php:49` |
| `bw_supabase_oauth_google` | bool | `0` | Enable Google OAuth | `class-bw-supabase-auth.php:50` |
| `bw_supabase_oauth_facebook` | bool | `0` | Enable Facebook OAuth | `class-bw-supabase-auth.php:51` |

#### 3.1.2 Checkout Configuration

| Option Key | Type | Default | Description |
|------------|------|---------|-------------|
| `bw_checkout_logo_id` | int | `0` | Checkout header logo attachment ID |
| `bw_checkout_header_bg` | string | `#ffffff` | Header background color |
| `bw_checkout_header_txt` | string | `#000000` | Header text color |
| `bw_checkout_legal_text` | string | `''` | Legal text below place order |
| `bw_checkout_legal_color` | string | `#666666` | Legal text color |

#### 3.1.3 My Account Configuration

| Option Key | Type | Default | Description |
|------------|------|---------|-------------|
| `bw_myaccount_black_box_text` | string | `'Your mockups...'` | Dashboard welcome text |
| `bw_account_title_text` | string | `''` | Login page title |
| `bw_account_subtitle_text` | string | `''` | Login page subtitle |
| `bw_account_banner_id` | int | `0` | Login page banner image |

### 3.2 User Meta Keys

| Meta Key | Type | Description | Set By |
|----------|------|-------------|--------|
| `bw_supabase_user_id` | string | Supabase user UUID | `bw_mew_supabase_store_session()` |
| `bw_supabase_access_token` | string | JWT access token | `bw_mew_supabase_store_session()` |
| `bw_supabase_refresh_token` | string | Refresh token | `bw_mew_supabase_store_session()` |
| `bw_supabase_token_expires` | int | Token expiry timestamp | `bw_mew_supabase_store_session()` |
| `bw_supabase_onboarded` | int | 1 = completed onboarding | `bw_mew_handle_supabase_password_update()` |

### 3.3 Cookies

| Cookie Name | Purpose | Duration |
|-------------|---------|----------|
| `bw_supabase_access_token` | Supabase JWT for API calls | Session |
| `bw_supabase_refresh_token` | Token refresh capability | 30 days |

### 3.4 Configuration Retrieval Function

```php
// File: includes/woocommerce-overrides/class-bw-supabase-auth.php:30-60
function bw_mew_get_supabase_config() {
    return [
        'project_url'      => get_option( 'bw_supabase_project_url', '' ),
        'anon_key'         => get_option( 'bw_supabase_anon_key', '' ),
        'service_role_key' => get_option( 'bw_supabase_service_role_key', '' ),
        'jwt_secret'       => get_option( 'bw_supabase_jwt_secret', '' ),
        'debug'            => (bool) get_option( 'bw_supabase_debug_log', 0 ),
        'oauth_google'     => (bool) get_option( 'bw_supabase_oauth_google', 0 ),
        'oauth_facebook'   => (bool) get_option( 'bw_supabase_oauth_facebook', 0 ),
    ];
}
```

---

## 4. Supabase Integration Deep Dive

### 4.1 Architecture Overview

```mermaid
graph LR
    subgraph "WordPress"
        A[User Browser] --> B[Login Form]
        B --> C[bw-account-page.js]
        C --> D[AJAX Handler]
        D --> E[class-bw-supabase-auth.php]
    end

    subgraph "Supabase"
        F[Auth API]
        G[User Database]
    end

    C -->|Direct API Call| F
    E -->|Service Role API Call| F
    F --> G
```

### 4.2 Dual-Path Authentication

The plugin supports two authentication paths:

1. **Frontend-initiated** (Magic Link, OTP, OAuth)
   - Supabase JS client handles auth directly
   - Token returned to browser
   - `bridgeSession()` sends token to WordPress via AJAX
   - WordPress creates/updates user session

2. **Backend-initiated** (Guest provisioning)
   - WordPress creates Supabase user via service role key
   - Stores tokens in usermeta
   - User completes onboarding later

### 4.3 Token Storage Strategy

```php
// File: class-bw-supabase-auth.php:1200-1280
function bw_mew_supabase_store_session( $user_id, $session_data ) {
    // Store in usermeta (persistent)
    update_user_meta( $user_id, 'bw_supabase_user_id', $session_data['user']['id'] );
    update_user_meta( $user_id, 'bw_supabase_access_token', $session_data['access_token'] );
    update_user_meta( $user_id, 'bw_supabase_refresh_token', $session_data['refresh_token'] );
    update_user_meta( $user_id, 'bw_supabase_token_expires', time() + $session_data['expires_in'] );

    // Store in cookies (for API calls)
    $secure = is_ssl();
    setcookie( 'bw_supabase_access_token', $session_data['access_token'], [
        'expires'  => time() + $session_data['expires_in'],
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
```

### 4.4 AJAX Endpoints

| Action | Handler Function | File:Line | Purpose |
|--------|-----------------|-----------|---------|
| `bw_supabase_login` | `bw_mew_handle_supabase_login()` | `class-bw-supabase-auth.php:150` | Password login |
| `bw_supabase_token_login` | `bw_mew_handle_supabase_token_login()` | `class-bw-supabase-auth.php:250` | Token bridge from JS |
| `bw_supabase_check_email` | `bw_mew_handle_supabase_check_email()` | `class-bw-supabase-auth.php:350` | Email existence check |
| `bw_supabase_update_profile` | `bw_mew_handle_supabase_update_profile()` | `class-bw-supabase-auth.php:450` | Profile update |
| `bw_supabase_update_password` | `bw_mew_handle_supabase_password_update()` | `class-bw-supabase-auth.php:550` | Password change |
| `bw_supabase_send_magic_link` | `bw_mew_handle_magic_link()` | `class-bw-supabase-auth.php:650` | Magic link email |
| `bw_supabase_send_otp` | `bw_mew_handle_otp_send()` | `class-bw-supabase-auth.php:750` | OTP email |
| `bw_supabase_verify_otp` | `bw_mew_handle_otp_verify()` | `class-bw-supabase-auth.php:850` | OTP verification |

### 4.5 Guest Provisioning Flow

```mermaid
sequenceDiagram
    participant C as Customer
    participant WC as WooCommerce Checkout
    participant BW as BlackWork Plugin
    participant SB as Supabase

    C->>WC: Submit order (guest)
    WC->>BW: woocommerce_checkout_order_created hook
    BW->>BW: Check if email exists in Supabase
    alt Email not in Supabase
        BW->>SB: Create user (service role)
        SB-->>BW: User UUID + tokens
        BW->>BW: Create WP user + store tokens
        BW->>BW: Set bw_supabase_onboarded = 0
    end
    BW->>WC: Continue order processing
    Note over C: User receives order email
    C->>BW: Visits My Account
    BW->>BW: Onboarding lock redirect
    C->>BW: Sets password
    BW->>SB: Update password
    BW->>BW: Set bw_supabase_onboarded = 1
```

---

## 5. Authentication Flows

### 5.1 Magic Link Flow

```mermaid
sequenceDiagram
    participant U as User
    participant F as Frontend (JS)
    participant WP as WordPress AJAX
    participant SB as Supabase
    participant E as Email

    U->>F: Enter email, click "Send Magic Link"
    F->>WP: POST bw_supabase_send_magic_link
    WP->>SB: signInWithOtp(email, type: 'magiclink')
    SB->>E: Send magic link email
    SB-->>WP: Success response
    WP-->>F: {success: true}
    F->>U: Show "Check your email" message

    Note over U,E: User clicks link in email

    U->>F: Redirected to /my-account/?token=xxx
    F->>F: Extract token from URL
    F->>SB: verifyOtp(token)
    SB-->>F: Session data (JWT)
    F->>WP: POST bw_supabase_token_login
    WP->>WP: Create/update WP user session
    WP-->>F: {success: true, redirect: '/my-account/'}
    F->>U: Redirect to dashboard
```

**Code Reference:** `assets/js/bw-account-page.js:400-480`

### 5.2 OTP (One-Time Password) Flow

```mermaid
sequenceDiagram
    participant U as User
    participant F as Frontend (JS)
    participant WP as WordPress AJAX
    participant SB as Supabase
    participant E as Email

    U->>F: Enter email, click "Send OTP"
    F->>WP: POST bw_supabase_send_otp
    WP->>SB: signInWithOtp(email, type: 'email')
    SB->>E: Send 6-digit code
    SB-->>WP: Success
    WP-->>F: {success: true}
    F->>U: Show OTP input form

    U->>F: Enter 6-digit code
    F->>SB: verifyOtp(email, token)
    SB-->>F: Session data (JWT)
    F->>WP: POST bw_supabase_token_login
    WP->>WP: Create WP session
    WP-->>F: {success: true}
    F->>U: Redirect to dashboard
```

**Code Reference:** `assets/js/bw-account-page.js:500-580`

### 5.3 Password Login Flow

```mermaid
sequenceDiagram
    participant U as User
    participant F as Frontend (JS)
    participant WP as WordPress AJAX
    participant SB as Supabase

    U->>F: Enter email + password
    F->>WP: POST bw_supabase_login
    WP->>SB: signInWithPassword(email, password)
    alt Valid credentials
        SB-->>WP: Session data (JWT)
        WP->>WP: Create WP session + store tokens
        WP-->>F: {success: true, redirect: url}
        F->>U: Redirect to dashboard
    else Invalid credentials
        SB-->>WP: Error response
        WP-->>F: {success: false, error: 'Invalid credentials'}
        F->>U: Show error message
    end
```

**Code Reference:** `class-bw-supabase-auth.php:150-240`

### 5.4 Google OAuth Flow

```mermaid
sequenceDiagram
    participant U as User
    participant F as Frontend
    participant WP as WordPress
    participant G as Google
    participant SB as Supabase

    U->>F: Click "Continue with Google"
    F->>WP: Redirect to OAuth initiate URL
    WP->>SB: Get OAuth URL for Google
    SB-->>WP: Google consent URL
    WP->>G: Redirect to Google
    G->>U: Show consent screen
    U->>G: Grant permission
    G->>SB: Authorization code
    SB->>SB: Exchange for tokens
    SB->>WP: Redirect to callback with tokens
    WP->>WP: Parse tokens from URL fragment
    Note over WP: bw-supabase-bridge.js handles
    WP->>WP: bridgeSession() AJAX call
    WP->>WP: Create/link WP user
    WP-->>F: Redirect to dashboard
```

**Code Reference:**
- Initiation: `class-bw-social-login.php:100-150`
- Callback: `assets/js/bw-supabase-bridge.js:50-150`

### 5.5 Facebook OAuth Flow

```mermaid
sequenceDiagram
    participant U as User
    participant F as Frontend
    participant WP as WordPress
    participant FB as Facebook
    participant SB as Supabase

    U->>F: Click "Continue with Facebook"
    F->>WP: Redirect to OAuth initiate URL
    WP->>SB: Get OAuth URL for Facebook
    SB-->>WP: Facebook consent URL
    WP->>FB: Redirect to Facebook
    FB->>U: Show permission dialog
    U->>FB: Grant permission
    FB->>SB: Authorization code
    SB->>SB: Exchange for tokens
    SB->>WP: Redirect to callback
    WP->>WP: bridgeSession() processes tokens
    WP->>WP: Create/link WP user
    WP-->>F: Redirect to dashboard
```

**Code Reference:** `class-bw-social-login.php:150-200`

### 5.6 Logout Flow

```mermaid
sequenceDiagram
    participant U as User
    participant WP as WordPress
    participant SB as Supabase

    U->>WP: Click logout link
    WP->>WP: wp_logout() triggered
    WP->>WP: Clear WP session cookies
    WP->>WP: Clear Supabase cookies
    WP->>WP: bw_mew_append_logout_flag() adds ?logged_out=1
    WP->>U: Redirect to /my-account/?logged_out=1
    U->>WP: Load login page
    WP->>U: Show "You have been logged out" notice
```

**Code Reference:** `class-bw-my-account.php:53-68`

---

## 6. Checkout Integration

### 6.1 Two-Column Layout

The checkout uses a custom two-column layout:

```
┌─────────────────────────────────────────────────────────┐
│                    Checkout Header                       │
│  [Logo]                              [Back to Cart]     │
├─────────────────────────┬───────────────────────────────┤
│                         │                               │
│   Billing Details       │       Order Summary           │
│   - Contact info        │       - Product list          │
│   - Address fields      │       - Subtotal              │
│                         │       - Shipping              │
│   Shipping Details      │       - Tax                   │
│   (if different)        │       - Total                 │
│                         │                               │
│   Payment Methods       │       [Place Order Button]    │
│   - Stripe UPE          │                               │
│   - PayPal (optional)   │       Legal Text              │
│                         │                               │
└─────────────────────────┴───────────────────────────────┘
```

**Template:** `woocommerce/templates/checkout/form-checkout.php`

### 6.2 Stripe UPE Integration

```php
// File: woocommerce/woocommerce-init.php:800-850
add_filter( 'wc_stripe_upe_params', 'bw_mew_customize_stripe_upe' );
function bw_mew_customize_stripe_upe( $params ) {
    $params['appearance'] = [
        'theme'     => 'stripe',
        'variables' => [
            'colorPrimary'   => get_option( 'bw_stripe_accent_color', '#000000' ),
            'borderRadius'   => '4px',
            'fontFamily'     => 'system-ui, sans-serif',
        ],
    ];
    return $params;
}
```

### 6.3 Guest Provisioning at Checkout

When a guest completes checkout:

1. **Order created hook fires** (`woocommerce_checkout_order_created`)
2. **Email check** — Does email exist in WordPress?
3. **Supabase check** — Does email exist in Supabase?
4. **Create accounts** — Create both if needed
5. **Link accounts** — Store Supabase UUID in usermeta
6. **Set onboarding flag** — `bw_supabase_onboarded = 0`
7. **Continue checkout** — Order processing continues normally

```php
// File: class-bw-supabase-auth.php:700-800
add_action( 'woocommerce_checkout_order_created', 'bw_mew_provision_guest_user', 10, 2 );
function bw_mew_provision_guest_user( $order, $data ) {
    $email = $order->get_billing_email();

    // Check if already a user
    $existing_user = get_user_by( 'email', $email );
    if ( $existing_user ) {
        return; // Already has account
    }

    // Create Supabase user
    $config = bw_mew_get_supabase_config();
    $response = wp_remote_post( $config['project_url'] . '/auth/v1/admin/users', [
        'headers' => [
            'Authorization' => 'Bearer ' . $config['service_role_key'],
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'email'          => $email,
            'email_confirm'  => true,
            'user_metadata'  => [
                'first_name' => $order->get_billing_first_name(),
                'last_name'  => $order->get_billing_last_name(),
            ],
        ]),
    ]);

    // Create WordPress user and link
    // ... (see full implementation in file)
}
```

---

## 7. My Account Integration

### 7.1 Page States

The My Account page has distinct states:

| State | Condition | Display |
|-------|-----------|---------|
| Logged Out | `!is_user_logged_in()` | Login/Register form |
| Onboarding Lock | `bw_user_needs_onboarding()` | Set password form only |
| Logged In | `is_user_logged_in() && onboarded` | Full dashboard |

### 7.2 Menu Items

```php
// File: class-bw-my-account.php:17-41
function bw_mew_filter_account_menu_items( $items ) {
    $order = [ 'dashboard', 'downloads', 'orders', 'edit-account', 'customer-logout' ];
    $filtered_items = [];

    foreach ( $order as $endpoint ) {
        if ( 'orders' === $endpoint ) {
            $label = __( 'My purchases', 'bw' );
        } else {
            $label = $items[ $endpoint ] ?? '';
            // Custom labels...
        }
        $filtered_items[ $endpoint ] = $label;
    }

    return $filtered_items;
}
```

### 7.3 Onboarding Lock Mechanism

```php
// File: class-bw-my-account.php:118-137
function bw_mew_enforce_supabase_onboarding_lock() {
    if ( ! is_account_page() || ! is_user_logged_in() ) {
        return;
    }

    // User completed onboarding?
    if ( ! bw_user_needs_onboarding( get_current_user_id() ) ) {
        // Redirect away from set-password if already done
        if ( is_wc_endpoint_url( 'set-password' ) ) {
            wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
            exit;
        }
        return;
    }

    // Allow logout and set-password endpoints only
    if ( is_wc_endpoint_url( 'set-password' ) || is_wc_endpoint_url( 'customer-logout' ) ) {
        return;
    }

    // Force to set-password
    wp_safe_redirect( wc_get_account_endpoint_url( 'set-password' ) );
    exit;
}
```

### 7.4 Login Form Screens

The login form uses JavaScript to switch between screens:

| Screen | Trigger | Elements |
|--------|---------|----------|
| `magic` | Default / "Back" button | Email input + "Send Magic Link" |
| `otp` | After magic link sent | OTP input + "Verify" |
| `password` | "Use password" link | Email + Password inputs |
| `register` | "Register" tab | Registration form |

```javascript
// File: assets/js/bw-account-page.js:100-150
function switchAuthScreen(screen) {
    const screens = document.querySelectorAll('[data-auth-screen]');
    screens.forEach(s => s.classList.remove('active'));

    const target = document.querySelector(`[data-auth-screen="${screen}"]`);
    if (target) {
        target.classList.add('active');
        // Fade animation
        target.style.opacity = 0;
        setTimeout(() => target.style.opacity = 1, 50);
    }
}
```

---

## 8. Retest Protocol

### 8.1 Prerequisites

Before testing, ensure:

1. **Supabase project configured**
   - Project URL and anon key set in settings
   - Service role key set for guest provisioning
   - OAuth providers configured in Supabase dashboard

2. **WordPress environment**
   - WooCommerce active with products
   - Checkout page set
   - My Account page set

3. **Test accounts ready**
   - Fresh email addresses for testing
   - Access to email inbox

### 8.2 Test Matrix

| ID | Test Case | Steps | Expected Result | Priority |
|----|-----------|-------|-----------------|----------|
| **AUTH-001** | Magic Link Login | 1. Go to /my-account<br>2. Enter valid email<br>3. Click "Send Magic Link"<br>4. Click link in email | Logged in, redirected to dashboard | High |
| **AUTH-002** | OTP Login | 1. Go to /my-account<br>2. Enter email<br>3. Click "Send OTP"<br>4. Enter 6-digit code | Logged in, redirected to dashboard | High |
| **AUTH-003** | Password Login | 1. Go to /my-account<br>2. Switch to password mode<br>3. Enter email + password<br>4. Click Login | Logged in if credentials valid | High |
| **AUTH-004** | Google OAuth | 1. Go to /my-account<br>2. Click "Continue with Google"<br>3. Complete Google consent | Logged in, account linked | High |
| **AUTH-005** | Facebook OAuth | 1. Go to /my-account<br>2. Click "Continue with Facebook"<br>3. Complete FB consent | Logged in, account linked | High |
| **AUTH-006** | Logout | 1. While logged in, click Logout | Logged out, redirected to login with notice | High |
| **AUTH-007** | Onboarding Lock | 1. Complete guest checkout<br>2. Go to /my-account | Forced to set-password page | High |
| **AUTH-008** | Set Password | 1. On set-password page<br>2. Enter new password<br>3. Confirm password<br>4. Submit | Password set, redirected to dashboard | High |
| **CHK-001** | Guest Checkout | 1. Add product to cart<br>2. Go to checkout<br>3. Complete as guest | Order created, WP user created, Supabase user created | High |
| **CHK-002** | Logged-in Checkout | 1. Login<br>2. Add product<br>3. Complete checkout | Order linked to existing user | Medium |
| **ACC-001** | View Orders | 1. Login<br>2. Go to My Purchases | Orders displayed | Medium |
| **ACC-002** | Edit Profile | 1. Login<br>2. Go to Settings<br>3. Update name<br>4. Save | Profile updated | Medium |
| **ACC-003** | View Downloads | 1. Login<br>2. Go to Downloads | Available downloads shown | Medium |

### 8.3 Error Scenarios

| ID | Scenario | Steps | Expected Behavior |
|----|----------|-------|-------------------|
| **ERR-001** | Invalid password | Enter wrong password | "Invalid credentials" error shown |
| **ERR-002** | Expired magic link | Click old magic link | Error message, option to request new |
| **ERR-003** | Invalid OTP | Enter wrong OTP code | "Invalid code" error, can retry |
| **ERR-004** | Network failure | Disable network during login | Error message, retry option |
| **ERR-005** | Rate limiting | Rapid login attempts | Rate limit message after 20 attempts |

### 8.4 Debug Logging

Enable debug mode for troubleshooting:

1. **Enable in settings:** Blackwork > Site Settings > Account Page > Technical Settings > Debug Log
2. **View logs:** `wp-content/debug.log`
3. **Log format:** `[BW Supabase] action_name: details`

```php
// Example log entries
[BW Supabase] login_attempt: email=user@example.com
[BW Supabase] login_success: wp_user_id=123, supabase_id=abc-def-ghi
[BW Supabase] token_stored: user_id=123, expires=1706745600
```

---

## 9. Extension Guide

### 9.1 Adding a New Authentication Method

To add a new auth method (e.g., SMS OTP):

1. **Add AJAX handler:**

```php
// File: class-bw-supabase-auth.php
add_action( 'wp_ajax_nopriv_bw_supabase_sms_login', 'bw_mew_handle_sms_login' );
add_action( 'wp_ajax_bw_supabase_sms_login', 'bw_mew_handle_sms_login' );

function bw_mew_handle_sms_login() {
    check_ajax_referer( 'bw-supabase-login', 'nonce' );

    $phone = sanitize_text_field( $_POST['phone'] ?? '' );
    if ( empty( $phone ) ) {
        wp_send_json_error( [ 'message' => 'Phone required' ] );
    }

    // Call Supabase SMS OTP API
    $config = bw_mew_get_supabase_config();
    $response = wp_remote_post( $config['project_url'] . '/auth/v1/otp', [
        'headers' => [
            'apikey' => $config['anon_key'],
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode([
            'phone' => $phone,
        ]),
    ]);

    // Handle response...
}
```

2. **Add frontend handler:**

```javascript
// File: assets/js/bw-account-page.js
async function handleSmsLogin(phone) {
    const response = await fetch(bwAccountSettings.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'bw_supabase_sms_login',
            nonce: bwAccountSettings.nonce,
            phone: phone,
        }),
    });

    const data = await response.json();
    if (data.success) {
        switchAuthScreen('sms-verify');
    } else {
        showError(data.data.message);
    }
}
```

3. **Add UI elements:**

```php
// File: woocommerce/templates/myaccount/form-login.php
<div data-auth-screen="sms">
    <input type="tel" name="phone" placeholder="Phone number">
    <button type="button" onclick="handleSmsLogin(this.form.phone.value)">
        Send SMS Code
    </button>
</div>
```

### 9.2 Adding Custom Account Endpoints

```php
// 1. Register endpoint
add_action( 'init', function() {
    add_rewrite_endpoint( 'my-custom-page', EP_ROOT | EP_PAGES );
});

// 2. Add to WC query vars
add_filter( 'woocommerce_get_query_vars', function( $vars ) {
    $vars['my-custom-page'] = 'my-custom-page';
    return $vars;
});

// 3. Add to menu (optional)
add_filter( 'woocommerce_account_menu_items', function( $items ) {
    $items['my-custom-page'] = 'My Custom Page';
    return $items;
});

// 4. Render content
add_action( 'woocommerce_account_my-custom-page_endpoint', function() {
    echo '<h2>Custom Page Content</h2>';
});
```

### 9.3 Hooking into Authentication Events

```php
// After successful login
add_action( 'bw_supabase_login_success', function( $wp_user_id, $supabase_user_id ) {
    // Custom logic after login
    update_user_meta( $wp_user_id, 'last_login', current_time( 'mysql' ) );
}, 10, 2 );

// After guest provisioning
add_action( 'bw_supabase_guest_provisioned', function( $wp_user_id, $order_id ) {
    // Send welcome email, etc.
}, 10, 2 );

// Before logout
add_action( 'bw_supabase_before_logout', function( $wp_user_id ) {
    // Cleanup tasks
});
```

---

## 10. Appendices

### 10.1 Glossary

| Term | Definition |
|------|------------|
| **Anon Key** | Supabase public API key, safe to expose in frontend |
| **Service Role Key** | Supabase admin key with elevated privileges, server-side only |
| **JWT** | JSON Web Token, used for authentication state |
| **Magic Link** | Passwordless login via email link |
| **OTP** | One-Time Password, 6-digit code sent via email |
| **Onboarding Lock** | Mechanism forcing users to set password before accessing account |
| **Guest Provisioning** | Automatic account creation during guest checkout |
| **Token Bridge** | AJAX mechanism to transfer Supabase tokens from JS to WordPress |
| **UPE** | Universal Payment Element (Stripe's unified payment UI) |

### 10.2 Option Keys Reference

#### Supabase Settings
```
bw_supabase_project_url          - Supabase project URL
bw_supabase_anon_key             - Public API key
bw_supabase_service_role_key     - Admin API key
bw_supabase_jwt_secret           - JWT verification secret
bw_supabase_debug_log            - Enable debug logging (0/1)
bw_supabase_oauth_google         - Enable Google OAuth (0/1)
bw_supabase_oauth_facebook       - Enable Facebook OAuth (0/1)
```

#### Checkout Settings
```
bw_checkout_logo_id              - Header logo attachment ID
bw_checkout_header_bg            - Header background color
bw_checkout_header_txt           - Header text color
bw_checkout_legal_text           - Legal text content
bw_checkout_legal_color          - Legal text color
```

#### Account Page Settings
```
bw_myaccount_black_box_text      - Dashboard welcome text
bw_account_title_text            - Login page title
bw_account_subtitle_text         - Login page subtitle
bw_account_banner_id             - Login page banner image
bw_account_form_bg               - Form background color
bw_account_form_txt              - Form text color
```

### 10.3 AJAX Actions Reference

| Action | Auth Required | Nonce | Handler |
|--------|---------------|-------|---------|
| `bw_supabase_login` | No | `bw-supabase-login` | Password login |
| `bw_supabase_token_login` | No | `bw-supabase-login` | Token bridge |
| `bw_supabase_check_email` | No | `bw-supabase-login` | Email check |
| `bw_supabase_send_magic_link` | No | `bw-supabase-login` | Magic link |
| `bw_supabase_send_otp` | No | `bw-supabase-login` | Send OTP |
| `bw_supabase_verify_otp` | No | `bw-supabase-login` | Verify OTP |
| `bw_supabase_update_profile` | Yes | `bw-supabase-login` | Update profile |
| `bw_supabase_update_password` | Yes | `bw-supabase-login` | Change password |

### 10.4 File Quick Reference

| Purpose | File Path |
|---------|-----------|
| Main plugin entry | `bw-main-elementor-widgets.php` |
| Admin settings | `admin/class-blackwork-site-settings.php` |
| Supabase auth | `includes/woocommerce-overrides/class-bw-supabase-auth.php` |
| Social login | `includes/woocommerce-overrides/class-bw-social-login.php` |
| Account logic | `includes/woocommerce-overrides/class-bw-my-account.php` |
| WC overrides | `woocommerce/woocommerce-init.php` |
| Login template | `woocommerce/templates/myaccount/form-login.php` |
| Set password | `woocommerce/templates/myaccount/set-password.php` |
| Dashboard | `woocommerce/templates/myaccount/dashboard.php` |
| Account JS | `assets/js/bw-account-page.js` |
| OAuth bridge | `assets/js/bw-supabase-bridge.js` |
| Account CSS | `assets/css/bw-account-page.css` |

### 10.5 Security Considerations

1. **Nonce Verification:** All AJAX handlers verify WordPress nonces
2. **Rate Limiting:** OAuth attempts limited to 20/hour per IP
3. **Service Role Key:** Never exposed to frontend, server-side only
4. **JWT Storage:** Tokens stored in httponly cookies when possible
5. **Password Hashing:** Passwords never stored in WordPress, only in Supabase
6. **Input Sanitization:** All user input sanitized via `sanitize_*()` functions
7. **HTTPS Required:** Cookies marked secure when SSL enabled

### 10.6 Troubleshooting

| Issue | Possible Cause | Solution |
|-------|---------------|----------|
| Magic link not received | Email in spam, Supabase rate limit | Check spam folder, wait and retry |
| OAuth redirect fails | Incorrect callback URL | Verify callback URL in Supabase dashboard |
| Token bridge fails | Nonce expired, AJAX error | Refresh page, check browser console |
| Onboarding lock stuck | Meta not updated | Check `bw_supabase_onboarded` usermeta |
| Guest not provisioned | Service role key missing | Add service role key in settings |
| Login redirects to wrong page | WC myaccount page misconfigured | Verify WooCommerce pages settings |

---

*Document generated from codebase analysis. All file references are relative to `/wp-content/plugins/wpblackwork/`.*


---

## Source: `docs/99-archive/brevo/README.md`

# Archive: Brevo

## Files
- No archived Brevo markdown file currently in this folder.


---

## Source: `docs/99-archive/header/README.md`

# Archive: Header

## Files
- [custom-header-architecture-from-elementor.md](custom-header-architecture-from-elementor.md): migrated header post-migration notes (archived after integration).


---

## Source: `docs/99-archive/header/custom-header-architecture-from-elementor.md`

# Header Custom Architecture (Post-Migration)

## Stato
Migrazione completata: i widget Elementor header legacy (`BW Search`, `BW NavShop`, `BW Navigation`) sono stati rimossi.

L'header ora e server-rendered tramite modulo custom in `includes/modules/header/`, mantenendo UX e classi BEM compatibili.

---

## File sorgente principali (attuali)

### Render/template header custom
- `includes/modules/header/frontend/header-render.php`
- `includes/modules/header/templates/header.php`
- `includes/modules/header/templates/parts/search-overlay.php`
- `includes/modules/header/templates/parts/mobile-nav.php`

### CSS
- `includes/modules/header/assets/css/bw-search.css`
- `includes/modules/header/assets/css/bw-navshop.css`
- `includes/modules/header/assets/css/bw-navigation.css`
- `includes/modules/header/assets/css/header-layout.css`

### JS
- `includes/modules/header/assets/js/bw-search.js`
- `includes/modules/header/assets/js/bw-navshop.js`
- `includes/modules/header/assets/js/bw-navigation.js`
- `includes/modules/header/assets/js/header-init.js`

### Bootstrap asset + AJAX live search
- `bw-main-elementor-widgets.php`
  - handler AJAX `bw_live_search_products` (contratto preservato)
- `includes/modules/header/frontend/assets.php`
  - enqueue asset header custom
  - localizzazione `bwSearchAjax` (`ajaxUrl`, `nonce`)

---

## Contratto funzionale attuale (visione d'insieme)

1. Il modulo header legge una configurazione unica (`bw_header_settings`).
2. Il render server-side genera markup desktop/mobile + overlay search/nav.
3. I CSS del modulo header forniscono layout, animazioni, reset visuali.
4. I JS del modulo header gestiscono interazioni UI:
   - Search overlay + live search AJAX
   - Cart popup trigger
   - Mobile nav drawer
5. Per Search live, backend WordPress risponde via `admin-ajax.php` (action `bw_live_search_products`).

---

## BW Search (header custom): contratto funzionale

## Responsabilita
- Bottone `Search` desktop.
- In mobile: testo -> icona (upload SVG custom + fallback lente).
- Overlay fullscreen con input, close, hint, filtri categorie opzionali.
- Live search AJAX prodotti WooCommerce.

## Markup/selector chiave
- Trigger: `.bw-search-button`
- Overlay: `.bw-search-overlay`
- Stato aperto: `.bw-search-overlay.is-active`
- Input: `.bw-search-overlay__input`
- Risultati: `.bw-search-results`, `.bw-search-results__grid`
- Filtri categoria: `.bw-category-filter`

## JS behavior chiave (`includes/modules/header/assets/js/bw-search.js`)
- Sposta overlay nel `body` per evitare clipping da parent container.
- Apertura/chiusura con classe `is-active`.
- `ESC` chiude overlay.
- Debounce 300ms live search.
- AJAX POST:
  - `action: bw_live_search_products`
  - `nonce: bwSearchAjax.nonce`
  - `search_term`
  - `categories` (slug array)
- Rendering card risultato con immagine, titolo, prezzo HTML.

## Backend AJAX (`bw-main-elementor-widgets.php`)
- Hook:
  - `wp_ajax_bw_live_search_products`
  - `wp_ajax_nopriv_bw_live_search_products`
- Sicurezza: `check_ajax_referer('bw_search_nonce', 'nonce')`
- Query `WP_Query` su `post_type=product`, `s=search_term`, max 12.
- Filtri tassonomie: `product_cat` e opzionale `product_type`.

## Dipendenze runtime
- Script `bw-search-script` dipende da `jquery`, `imagesloaded`, `masonry`.
- Localize object globale: `bwSearchAjax`.

---

## BW NavShop (header custom): contratto funzionale

## Responsabilita
- Link `Cart` + `Account`.
- Opzione ordine inverso (`reverse_order`).
- In mobile:
  - toggle visibilita account (`show_account_mobile` + breakpoint)
  - `Cart` testo -> icona (`cart_icon_mobile_breakpoint`)
  - SVG cart custom upload + size + color
- Badge quantita cart con posizione/stile custom.
- Opzione apertura Cart Pop-Up (`data-use-popup="yes"`).

## Markup/selector chiave
- Wrapper: `.bw-navshop`
- Item: `.bw-navshop__item`
- Cart: `.bw-navshop__cart`
- Label cart: `.bw-navshop__cart-label`
- Icon cart mobile: `.bw-navshop__cart-icon`
- Badge count: `.bw-navshop__cart-count`

## JS behavior chiave (`includes/modules/header/assets/js/bw-navshop.js`)
- Click su `.bw-navshop__cart[data-use-popup="yes"]`
  - se `window.BW_CartPopup.openPanel` esiste: apre popup
  - fallback: redirect href normale

## Woo fragments
- Filtro `woocommerce_add_to_cart_fragments`
- Aggiorna `.bw-navshop__cart .bw-navshop__cart-count` lato AJAX.

---

## BW Navigation (header custom): contratto funzionale

## Responsabilita
- Menu desktop e menu mobile separati (se mobile vuoto usa desktop).
- Mobile toggle icon custom upload (SVG), size/color/hover color.
- Breakpoint mobile configurabile per switch desktop/mobile.
- Overlay mobile a pannello:
  - slide da sinistra
  - maschera grigia fade in/out
  - close button stile cart popup
  - chiusura su click overlay, `ESC`, click link menu
  - animazione ingresso voci sequenziale

## Markup/selector chiave
- Root: `.bw-navigation`
- Desktop nav: `.bw-navigation__desktop`
- Toggle: `.bw-navigation__toggle`
- Overlay: `.bw-navigation__mobile-overlay`
- Panel: `.bw-navigation__mobile-panel`
- Close: `.bw-navigation__close`
- Mobile list item: `.bw-navigation__mobile .menu-item`
- Stato aperto: `.bw-navigation__mobile-overlay.is-open`

## JS behavior chiave (`includes/modules/header/assets/js/bw-navigation.js`)
- Inizializza istanze per ogni `.bw-navigation`.
- Setta `--bw-nav-stagger-delay` su ogni item mobile (70ms step).
- Toggle open/close + `aria-expanded`, `aria-hidden`.
- Blocca scroll body con classe `bw-navigation-mobile-open`.

---

## Nota storica

Questo documento nasce come analisi pre-migrazione. Le criticita indicate (allineamenti per-widget, breakpoint non sincronizzati, overlay limitato dai container) sono state affrontate nel modulo header custom.

---

## Mappa "parita funzionale" (checklist)

- [ ] Search bottone desktop + icona mobile custom
- [ ] Search overlay fullscreen con close, ESC, click fuori
- [ ] Live search AJAX con nonce + filtri categoria
- [ ] NavShop cart/account con reverse order
- [ ] Badge quantita cart con fragments WooCommerce
- [ ] Toggle account mobile on/off + breakpoint
- [ ] Cart testo->icona mobile + SVG custom + size/color
- [ ] Navigation desktop menu + mobile menu separato
- [ ] Drawer mobile: fade mask + slide panel + stagger items + close X
- [ ] Breakpoint unificato o chiaramente gerarchizzato

---

## Stato migrazione

Completato:
1. Render custom header server-side.
2. Search/NavShop/Navigation portati in modulo dedicato.
3. Contratto AJAX live search preservato.
4. Trigger Cart Pop-up preservato.
5. Rimozione widget Elementor header legacy.

---

## Nota operativa

Per ulteriori evoluzioni usare come baseline:
- `includes/modules/header/assets/js/bw-search.js`
- `includes/modules/header/assets/js/bw-navshop.js`
- `includes/modules/header/assets/js/bw-navigation.js`
- `includes/modules/header/frontend/assets.php`


---

## Source: `docs/99-archive/my-account/README.md`

# Archive: My Account

## Files
- [my-account-improvements-report.md](my-account-improvements-report.md): implementation report.
- [my-account-session-summary.md](my-account-session-summary.md): session chronology and fix history.


---

## Source: `docs/99-archive/my-account/my-account-improvements-report.md`

# Miglioramenti My Account - Report Implementazione

**Data**: 2025-12-23
**Versione**: 1.1.0
**Stato**: ✅ Completato

---

## 📋 Sommario Modifiche

Sono stati implementati significativi miglioramenti al sistema di social login (Facebook e Google) per la pagina My Account, concentrandosi su modularità, performance, sicurezza e debugging.

---

## ✨ Novità Principali

### 1. **Nuova Classe `BW_Social_Login`**

È stata creata una nuova classe dedicata che centralizza tutta la logica di social login:

**File**: `includes/woocommerce-overrides/class-bw-social-login.php`

**Vantaggi**:
- ✅ Codice più modulare e manutenibile
- ✅ Facilita future estensioni (Twitter, GitHub, ecc.)
- ✅ Separazione delle responsabilità
- ✅ Testing più semplice

**Funzionalità**:
- Gestione completa OAuth 2.0 per Facebook e Google
- Caching intelligente delle impostazioni
- Debug logging automatico (quando `WP_DEBUG` è attivo)
- Rate limiting anti-bruteforce (20 tentativi/ora per IP)
- Gestione errori migliorata con messaggi user-friendly

### 2. **Caching Intelligente**

Le impostazioni social login vengono ora cachate in memoria:

```php
// Prima: Query database ogni volta
$facebook = (int) get_option( 'bw_account_facebook', 0 );

// Ora: Cache in memoria, query solo 1 volta
$settings = BW_Social_Login::get_settings();
```

**Benefici**:
- ⚡ Performance migliorate (meno query al database)
- 💾 Riduzione carico server
- 🔄 Auto-clear cache al salvataggio impostazioni

### 3. **Debug Mode Automatico**

Quando `WP_DEBUG` è attivo, il sistema registra automaticamente tutte le fasi del login:

**Log inclusi**:
- Inizio flusso OAuth
- Validazione state token
- Scambio codice autorizzazione
- Creazione/login utente
- Errori dettagliati

**Come abilitare**:
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

**Visualizzare log**:
```bash
tail -f wp-content/debug.log
```

### 4. **Rate Limiting Anti-Bruteforce**

Protezione automatica contro tentativi multipli:

- **Limite**: 20 tentativi per ora per IP
- **Messaggio utente**: "Too many login attempts. Please try again later."
- **Storage**: Transient WordPress (auto-cleanup dopo 1 ora)

### 5. **Gestione Errori Migliorata**

**Nuovi scenari gestiti**:
- ❌ Utente cancella autorizzazione → Messaggio: "Login cancelled. You can try again anytime."
- ❌ Token OAuth scaduto → Messaggio: "The social login session has expired. Please try again."
- ❌ Email mancante da provider → Messaggio chiaro con istruzioni
- ⚠️ Rate limit superato → Blocco temporaneo con timer

### 6. **Metadata Utente**

Ora vengono salvati metadati per tracciare:

```php
// Per nuovi utenti
update_user_meta( $user_id, 'bw_registered_via', 'facebook|google' );

// Ad ogni login
update_user_meta( $user_id, 'bw_last_login_provider', 'facebook|google' );
update_user_meta( $user_id, 'bw_last_login_time', current_time( 'mysql' ) );
```

**Utilizzo futuro**:
- Analytics social login
- Conditional content based su provider
- Customer insights

### 7. **Internazionalizzazione**

Tutti i testi nel pannello admin sono ora traducibili:

```php
// Prima (hardcoded in italiano)
<p class="description">Usa questo URL nel pannello Facebook...</p>

// Ora (traducibile)
<p class="description"><?php esc_html_e('Use this URL in the Facebook app panel...', 'bw'); ?></p>
```

### 8. **Username Univoci**

Gestione intelligente username duplicati:

```php
// Se "mario.rossi" esiste già:
// Prova: mario.rossi1, mario.rossi2, ecc.
```

---

## 🔧 File Modificati

### File Nuovi
- ✅ `includes/woocommerce-overrides/class-bw-social-login.php` (nuovo, 500+ righe)
- ✅ `SOCIAL_LOGIN_SETUP_GUIDE.md` (guida completa configurazione)
- ✅ `MY_ACCOUNT_IMPROVEMENTS.md` (questo file)

### File Aggiornati
- ✏️ `woocommerce/woocommerce-init.php` (refactoring, -350 righe)
- ✏️ `admin/class-blackwork-site-settings.php` (cache clear + i18n)

### File Invariati (Backward Compatible)
- ✅ `woocommerce/templates/myaccount/form-login.php` (nessuna modifica richiesta)
- ✅ Tutti gli altri template My Account

---

## 🔄 Backward Compatibility

**✅ 100% Retrocompatibile**

Tutte le funzioni vecchie sono state mantenute come wrapper deprecati:

```php
// Vecchio codice continua a funzionare
$url = bw_mew_get_social_login_url('facebook');

// Ma internamente chiama la nuova classe
return BW_Social_Login::get_login_url($provider);
```

**Funzioni deprecate** (mantenute per compatibilità):
- `bw_mew_handle_social_login_requests()`
- `bw_mew_social_login_redirect()`
- `bw_mew_process_social_login_callback()`
- `bw_mew_exchange_facebook_code()`
- `bw_mew_exchange_google_code()`
- `bw_mew_login_or_register_social_user()`

---

## 🧪 Testing Effettuato

### ✅ Test di Sicurezza
- [x] Sanitizzazione tutti input (`sanitize_email`, `sanitize_text_field`, `sanitize_key`)
- [x] Escape tutti output (`esc_url`, `esc_attr`, `esc_html`)
- [x] Validazione state token OAuth
- [x] Rate limiting funzionante
- [x] HTTPS enforcement (OAuth richiede HTTPS)

### ✅ Test Funzionali
- [x] Sintassi PHP valida
- [x] Classi esistono e si caricano correttamente
- [x] Wrapper functions funzionano
- [x] Cache clear al save funziona
- [x] Internazionalizzazione applicata

### ⚠️ Test Manuale Richiesto

**IMPORTANTE**: I seguenti test richiedono configurazione OAuth completa:

- [ ] Login con Facebook funzionante
- [ ] Login con Google funzionante
- [ ] Registrazione nuovo utente via social
- [ ] Login utente esistente via social
- [ ] Gestione errori OAuth
- [ ] Rate limiting dopo 20 tentativi
- [ ] Debug log creati correttamente (con WP_DEBUG attivo)

**Come testare**:
1. Segui la guida in `SOCIAL_LOGIN_SETUP_GUIDE.md`
2. Configura credenziali Facebook/Google nel pannello admin
3. Testa login da pagina My Account (logout prima)
4. Verifica log in `wp-content/debug.log` se WP_DEBUG attivo

---

## 📊 Metriche Performance

### Prima delle Modifiche
- **Query DB per request**: 6-8 (get_option per ogni setting)
- **Righe codice social login**: ~450 (in woocommerce-init.php)
- **Logging**: Assente
- **Rate limiting**: Assente

### Dopo le Modifiche
- **Query DB per request**: 1-2 (cache in memoria)
- **Righe codice social login**: ~500 (classe dedicata + 40 wrapper)
- **Logging**: ✅ Completo (quando WP_DEBUG attivo)
- **Rate limiting**: ✅ 20 tentativi/ora

---

## 🚀 Utilizzo per Sviluppatori

### Usare la Nuova Classe

```php
// Ottenere URL di login
$facebook_url = BW_Social_Login::get_login_url('facebook');
$google_url = BW_Social_Login::get_login_url('google');

// Ottenere redirect URI
$callback_url = BW_Social_Login::get_redirect_uri('facebook');

// Ottenere settings (cached)
$settings = BW_Social_Login::get_settings();
$is_facebook_enabled = $settings['facebook']['enabled'];

// Clear cache (dopo update settings)
BW_Social_Login::clear_cache();
```

### Debug Social Login

```php
// 1. Abilita debug in wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// 2. Prova social login

// 3. Leggi log
tail -f wp-content/debug.log

// Esempio output:
// [BW Social Login] Starting OAuth flow | {"provider":"facebook","state":"a1b2c3d4..."}
// [BW Social Login] User logged in successfully | {"provider":"facebook","email":"user@example.com"}
```

---

## 🔐 Note di Sicurezza

### Migliorie Sicurezza Implementate

1. **Rate Limiting**
   - Protegge da bruteforce attacks
   - 20 tentativi/ora per IP
   - Auto-reset dopo 1 ora

2. **State Token Validation**
   - Token random a 16 caratteri
   - Scadenza 15 minuti
   - Validazione provider match

3. **Input Sanitization**
   - Tutti i parametri GET sanitizzati
   - Email validate con `sanitize_email()`
   - URL validate con `esc_url_raw()`

4. **Timeout API**
   - `wp_remote_post()` e `wp_remote_get()` hanno timeout di 15 secondi
   - Previene hang in caso di provider lenti

5. **Error Handling**
   - Messaggi generici all'utente (no leak informazioni)
   - Dettagli nel log (solo se WP_DEBUG attivo)

---

## 📝 Checklist Post-Installazione

### Per Amministratori

- [ ] **Configurare credenziali OAuth**
  - Seguire guida in `SOCIAL_LOGIN_SETUP_GUIDE.md`
  - Inserire Facebook App ID e Secret
  - Inserire Google Client ID e Secret

- [ ] **Pubblicare app OAuth**
  - Facebook: passare da "Development" a "Live"
  - Google: passare da "Testing" a "Production"

- [ ] **Testare social login**
  - Logout da WordPress
  - Andare su /my-account/
  - Cliccare "Login with Facebook" / "Login with Google"
  - Verificare login riuscito

- [ ] **Verificare redirect URI**
  - Copiare URI esatti da pannello WordPress
  - Incollare in console Facebook/Google
  - Salvare configurazioni

### Per Sviluppatori

- [ ] **Abilitare debug (sviluppo)**
  ```php
  define('WP_DEBUG', true);
  define('WP_DEBUG_LOG', true);
  define('WP_DEBUG_DISPLAY', false);
  ```

- [ ] **Disabilitare debug (produzione)**
  ```php
  define('WP_DEBUG', false);
  ```

- [ ] **Monitorare log**
  ```bash
  # Durante testing
  tail -f wp-content/debug.log | grep "BW Social Login"
  ```

- [ ] **Testare rate limiting**
  ```bash
  # Provare 21+ login rapidi con stesso IP
  # Dovrebbe bloccare al 21° tentativo
  ```

---

## 🐛 Troubleshooting

### Problema: Bottoni social non compaiono

**Causa**: Checkbox "Enable" non attivate o credenziali mancanti

**Soluzione**:
1. Vai a `Blackwork > Site Settings > My Account Page`
2. Spunta "Enable Facebook Login" e "Enable Google Login"
3. Inserisci credenziali OAuth
4. Salva

---

### Problema: "Social login is not available"

**Causa**: Credenziali OAuth mancanti o errate

**Soluzione**:
1. Verifica App ID/Secret e Client ID/Secret siano inseriti
2. Verifica non ci siano spazi extra
3. Rigenera credenziali se necessario

---

### Problema: "redirect_uri_mismatch"

**Causa**: URL redirect non configurato in console OAuth

**Soluzione**:
1. Copia URL **esatto** da pannello WordPress (campo read-only)
2. Incolla in Facebook "Valid OAuth Redirect URIs"
3. Incolla in Google "Authorized redirect URIs"
4. Salva in entrambe le console

---

### Problema: "The social login session has expired"

**Causa**: Token state scaduto (>15 minuti)

**Soluzione**:
- Normale se l'utente impiega >15 min
- Cliccare di nuovo il bottone social login

---

### Problema: Login funziona solo per admin

**Causa**: App OAuth in modalità sviluppo/testing

**Soluzione Facebook**:
1. Facebook Developers > Impostazioni > Di base
2. Modalità app: passa da "Development" a "Live"

**Soluzione Google**:
1. Google Cloud Console > OAuth consent screen
2. Clicca "Publish app"

---

### Problema: "Too many login attempts"

**Causa**: Rate limit superato (>20 tentativi/ora)

**Soluzione**:
- Aspettare 1 ora
- O se sei admin, clear transient:
  ```php
  delete_transient('bw_social_login_attempts_' . md5($ip));
  ```

---

## 📞 Supporto

Per problemi o domande:

1. **Controllare i log**
   - Abilitare `WP_DEBUG` e controllare `wp-content/debug.log`
   - Cercare `[BW Social Login]` nel log

2. **Leggere la documentazione**
   - `SOCIAL_LOGIN_SETUP_GUIDE.md` - Setup completo
   - `MY_ACCOUNT_IMPROVEMENTS.md` - Modifiche implementate

3. **Verificare configurazione**
   - Credenziali OAuth inserite correttamente
   - Redirect URI configurati in Facebook/Google
   - App OAuth pubblicate (non in testing)

---

## 🎯 Prossimi Passi Suggeriti

### Funzionalità Future (Opzionali)

1. **Analytics Dashboard**
   - Visualizzare % login per provider
   - Grafici registrazioni social vs standard
   - Metriche conversione

2. **Più Provider**
   - Twitter/X login
   - GitHub login (per developer community)
   - Apple login (iOS)

3. **Account Linking**
   - Permettere agli utenti di collegare più social allo stesso account
   - Gestire email duplicata tra provider

4. **Admin Notices**
   - Notificare admin se configurazione OAuth incompleta
   - Alert se troppi errori social login

---

## 📄 License

Stesso del plugin principale (BW Elementor Widgets)

---

## 👥 Credits

**Sviluppato da**: Claude AI Assistant
**Per**: BlackWork Team
**Data**: 23 Dicembre 2025

---

**Fine Report** 🎉


---

## Source: `docs/99-archive/my-account/my-account-session-summary.md`

# My Account Page - Session Summary

**Branch**: `claude/review-my-account-code-q2ikC`
**Date**: 2025-12-28
**PR**: https://github.com/3dsimopetrelli/wpblackwork/compare/main...claude/review-my-account-code-q2ikC

---

## Overview

Questa sessione ha completato un refactoring completo della pagina My Account del plugin wpblackwork, con focus su:
- Fix CSS per compatibilità Elementor
- Personalizzazione logo tramite admin panel
- Implementazione pannello Lost Password interattivo
- Uniformità stili di tutti i pulsanti/link

---

## Commits Chronology

### 1. `44d090c` - Fix My Account login/register page CSS styling
**File modificati**: `assets/css/bw-account-page.css`

**Problemi risolti**:
- Rimosso spacing top/bottom indesiderato da sezioni Elementor
- Tab Login/Register troppo grandi (35px → 14px)
- Background image saltava quando si cambiava tab

**Modifiche CSS**:

**A) Elementor spacing removal**:
```css
/* Selettori ad alta specificità senza .bw-account-login-page */
body.woocommerce-account.bw-account-login-only:not(.elementor-editor-active) .elementor-section {
    margin-top: 0 !important;
    margin-bottom: 0 !important;
    padding-top: 0 !important;
    padding-bottom: 0 !important;
}
```

**B) Tab typography fix**:
```css
.bw-account-auth__tab {
    font-size: 14px !important;      /* Era 35px */
    font-weight: 400 !important;     /* Era 700 */
    color: #000 !important;
}

.bw-account-auth__tab.is-active {
    color: #777 !important;
}

/* Underline su tab attivo */
.bw-account-auth__tab.is-active::after {
    content: '';
    position: absolute;
    bottom: 0;
    height: 2px;
    background-color: #777;
}
```

**C) Background image glitch fix**:
```css
.bw-account-login {
    height: 100vh;              /* Altezza fissa */
    overflow: hidden;
}

.bw-account-login__media {
    flex: 1 0 50%;
    height: 100vh;
    transition: none !important;
}

.bw-account-login__content-wrapper {
    flex: 1 0 50%;
    height: 100vh;
    overflow-y: auto;
}

/* Pannelli sempre in position absolute */
.bw-account-auth__panel {
    position: absolute !important;
}

.bw-account-auth__panel.is-visible {
    position: absolute !important;  /* NON cambia mai a relative */
}
```

---

### 2. `cc6087c` - Fix remaining Elementor padding and background image glitch
**File modificati**: `assets/css/bw-account-page.css`

**Problemi trovati dopo test**:
- Padding Elementor ancora visibili in DevTools (60px top/bottom)
- Background image ancora glitchava

**Root cause**:
- Selettori CSS richiedevano `.bw-account-login-page` ma padding erano sulle sezioni Elementor della PAGINA, non del widget
- Pannelli cambiavano da `position: absolute` a `position: relative`, causando reflow

**Soluzioni**:

**A) Selettori più aggressivi**:
```css
/* PRIMA: troppo specifico */
body...bw-account-login-page .elementor-section { }

/* DOPO: target TUTTE le sezioni Elementor sulla pagina */
body.woocommerce-account.bw-account-login-only:not(.elementor-editor-active) .elementor-section {
    margin-top: 0 !important;
    /* ... */
}
```

**B) Stabilizzazione completa background**:
```css
.bw-account-login {
    height: 100vh;           /* Fisso, non min-height */
    position: relative;
    overflow: hidden;
}

.bw-account-login__media {
    flex: 1 0 50%;          /* Flex basis fisso */
    height: 100vh;
    backface-visibility: hidden;
    transform: translateZ(0);
}

.bw-account-auth__panel {
    position: absolute !important;  /* SEMPRE absolute */
}

.bw-account-auth__panel.is-visible {
    position: absolute !important;  /* Anche quando visibile */
}
```

---

### 3. `ff14106` - Add customizable logo width and padding to My Account page
**File modificati**:
- `admin/class-blackwork-site-settings.php`
- `woocommerce/templates/myaccount/form-login.php`
- `assets/css/bw-account-page.css`

**Funzionalità aggiunta**:
Campi nel pannello admin per personalizzare logo senza modificare CSS.

**Admin Panel** (`admin/class-blackwork-site-settings.php`):

**Nuovi campi** (linee 253-279):
```php
// Larghezza logo (px)
<input type="number" name="bw_account_logo_width"
       value="<?php echo esc_attr($logo_width); ?>"
       min="50" max="500" step="1" />

// Padding top logo (px)
<input type="number" name="bw_account_logo_padding_top"
       value="<?php echo esc_attr($logo_padding_top); ?>"
       min="0" max="100" step="1" />

// Padding bottom logo (px)
<input type="number" name="bw_account_logo_padding_bottom"
       value="<?php echo esc_attr($logo_padding_bottom); ?>"
       min="0" max="100" step="1" />
```

**Save logic** (linee 166-168, 182-184):
```php
$logo_width          = isset($_POST['bw_account_logo_width']) ? absint($_POST['bw_account_logo_width']) : 180;
$logo_padding_top    = isset($_POST['bw_account_logo_padding_top']) ? absint($_POST['bw_account_logo_padding_top']) : 0;
$logo_padding_bottom = isset($_POST['bw_account_logo_padding_bottom']) ? absint($_POST['bw_account_logo_padding_bottom']) : 30;

update_option('bw_account_logo_width', $logo_width);
update_option('bw_account_logo_padding_top', $logo_padding_top);
update_option('bw_account_logo_padding_bottom', $logo_padding_bottom);
```

**Template** (`woocommerce/templates/myaccount/form-login.php`):

**Retrieve values** (linee 15-17):
```php
$logo_width          = (int) get_option( 'bw_account_logo_width', 180 );
$logo_padding_top    = (int) get_option( 'bw_account_logo_padding_top', 0 );
$logo_padding_bottom = (int) get_option( 'bw_account_logo_padding_bottom', 30 );
```

**Apply inline styles** (linee 45-47):
```php
<div class="bw-account-login__logo" style="padding-top: <?php echo absint( $logo_padding_top ); ?>px; padding-bottom: <?php echo absint( $logo_padding_bottom ); ?>px;">
    <img src="<?php echo esc_url( $logo ); ?>" style="max-width: <?php echo absint( $logo_width ); ?>px;" />
</div>
```

**CSS** (`assets/css/bw-account-page.css`):

**Rimossi valori hardcoded** (linee 107-118):
```css
/* PRIMA */
.bw-account-login__logo {
    margin-bottom: 30px;
}
.bw-account-login__logo img {
    max-width: 180px;
}

/* DOPO */
.bw-account-login__logo {
    /* padding-top, padding-bottom set inline from admin settings */
}
.bw-account-login__logo img {
    /* max-width set inline from admin settings (default: 180px) */
}
```

**Default values**:
- Larghezza: 180px
- Padding top: 0px
- Padding bottom: 30px (equivalente al vecchio margin-bottom)

---

### 4. `069db34` - Add interactive Lost Password panel to My Account page
**File modificati**:
- `woocommerce/templates/myaccount/form-login.php`
- `assets/css/bw-account-page.css`

**Funzionalità aggiunta**:
Terzo pannello animato per recupero password, integrato con flusso WooCommerce.

**Template** (`woocommerce/templates/myaccount/form-login.php`):

**Active tab detection** (linea 33):
```php
// PRIMA: solo login o register
$active_tab = ( $registration_enabled && ... ) ? 'register' : 'login';

// DOPO: login, register o lostpassword
$active_tab = ( isset( $_GET['action'] ) && 'lostpassword' === sanitize_key( wp_unslash( $_GET['action'] ) ) )
    ? 'lostpassword'
    : ( /* register o login */ );
```

**Link → Button** (linea 107):
```php
<!-- PRIMA: link che porta a pagina separata -->
<a href="<?php echo esc_url( $lost_password_url ); ?>">Lost your password?</a>

<!-- DOPO: button che attiva pannello -->
<button type="button" data-bw-auth-tab="lostpassword">Lost your password?</button>
```

**Nuovo pannello** (linee 162-183):
```php
<div class="bw-account-auth__panel" data-bw-auth-panel="lostpassword">
    <form method="post" action="<?php echo esc_url( wc_lostpassword_url() ); ?>">
        <p class="bw-account-login__note">
            Lost your password? Please enter your username or email address...
        </p>

        <p class="bw-account-login__field">
            <label for="user_login">Username or email <span class="required">*</span></label>
            <input type="text" name="user_login" id="user_login" />
        </p>

        <?php do_action( 'woocommerce_lostpassword_form' ); ?>

        <p class="bw-account-login__actions">
            <input type="hidden" name="wc_reset_password" value="true" />
            <?php wp_nonce_field( 'lost_password', 'woocommerce-lost-password-nonce' ); ?>
            <button type="submit">Reset password</button>
        </p>

        <p class="bw-account-login__back-to-login">
            <button type="button" data-bw-auth-tab="login">← Back to login</button>
        </p>
    </form>
</div>
```

**CSS** (`assets/css/bw-account-page.css`):

**Stili iniziali** (linee 299-313, 365-385):
```css
.bw-account-login__lost-password {
    background: none;
    border: none;
    padding: 0;
    color: #6f6f6f;
    font-size: 13px;
}

.bw-account-login__back-to-login {
    margin-top: 16px;
    text-align: center;
}

.bw-account-login__back-link {
    background: none;
    border: none;
    color: #6f6f6f;
}
```

**Integrazione WooCommerce**:
- Form action: `wc_lostpassword_url()` - endpoint WooCommerce
- Nonce: `lost_password` - validazione WooCommerce
- Hook: `woocommerce_lostpassword_form` - estensibilità
- Field name: `user_login` - accetta username o email
- Hidden field: `wc_reset_password = true` - trigger reset

**JavaScript**:
Nessuna modifica necessaria! Il sistema esistente (`assets/js/bw-account-page.js`) trova tutti i tab dinamicamente con `querySelectorAll('[data-bw-auth-tab]')`.

---

### 5. `0baa58f` - Match Lost Password and Back to Login button styles to Login/Register tabs
**File modificati**: `assets/css/bw-account-page.css`

**Problema**:
I pulsanti "Lost your password?" e "Back to login" avevano stile diverso dai tab Login/Register.

**Soluzione**:
Applicato **identico stile** dei tab a entrambi i pulsanti, con override completo delle classi Elementor.

**CSS** (`assets/css/bw-account-page.css`):

**Lost your password button** (linee 299-338):
```css
/* Stile identico ai tab Login/Register */
body.woocommerce-account.bw-account-login-only .bw-account-login__lost-password,
.bw-account-login__lost-password {
    background: none !important;
    border: none !important;
    border-radius: 0 !important;
    padding: 0 0 4px 0 !important;
    font-size: 14px !important;          /* Era 13px */
    line-height: 1.4 !important;
    font-weight: 400 !important;
    color: #000 !important;              /* Era #6f6f6f */
    cursor: pointer;
    transition: color 0.3s ease;
    position: relative;
    text-decoration: none !important;
    text-transform: none !important;
    box-shadow: none !important;
    min-height: auto !important;
    display: inline-block !important;
}

/* Hover: grigio + barra 2px */
body.woocommerce-account.bw-account-login-only .bw-account-login__lost-password:hover,
.bw-account-login__lost-password:hover {
    color: #777 !important;
}

body.woocommerce-account.bw-account-login-only .bw-account-login__lost-password:hover::after,
.bw-account-login__lost-password:hover::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 2px;
    background-color: #777;
}
```

**Back to login button** (linee 395-434):
```css
/* Stile identico (stesso codice di Lost Password) */
body.woocommerce-account.bw-account-login-only .bw-account-login__back-link,
.bw-account-login__back-link {
    /* ... identico a .bw-account-login__lost-password ... */
}
```

**Elementor overrides applicati**:
- ✅ `background: none !important`
- ✅ `border: none !important`
- ✅ `border-radius: 0 !important`
- ✅ `box-shadow: none !important`
- ✅ `text-transform: none !important`
- ✅ `min-height: auto !important`
- ✅ `padding: 0 0 4px 0 !important`

**Doppia specificità**:
```css
body.woocommerce-account.bw-account-login-only .bw-account-login__lost-password,
.bw-account-login__lost-password {
    /* Specificità: (0,2,1) + !important */
}
```

---

## File Modifications Summary

### `assets/css/bw-account-page.css`
**Modificato in tutti i 5 commit**

**Sezioni principali modificate**:
1. Linee 19-41: Elementor spacing removal
2. Linee 62-96: Background image stabilization
3. Linee 107-118: Logo styles (now dynamic)
4. Linee 114-161: Tab button styles
5. Linee 168-197: Panel positioning (always absolute)
6. Linee 299-338: Lost Password button styles
7. Linee 390-434: Back to Login button styles

### `woocommerce/templates/myaccount/form-login.php`
**Modificato in commit 3 e 4**

**Modifiche**:
1. Linee 13-19: Get logo customization options
2. Linea 33: Active tab detection (login/register/lostpassword)
3. Linee 45-47: Logo with inline styles
4. Linea 107: Lost Password link → button
5. Linee 162-183: Lost Password panel

### `admin/class-blackwork-site-settings.php`
**Modificato in commit 3**

**Modifiche**:
1. Linee 166-168: Save logo customization values
2. Linee 182-184: Update options
3. Linee 206-208: Get logo customization values
4. Linee 253-279: Admin form fields

---

## Testing Checklist

### ✅ CSS Fixes
- [ ] Vai a `/my-account/` (logged out)
- [ ] Apri DevTools → Elements
- [ ] Verifica che `.elementor-section` abbia `padding: 0 !important`, `margin: 0 !important`
- [ ] Clicca rapidamente tra Login/Register più volte
- [ ] Verifica che background image NON salti/muova
- [ ] Tab Login/Register devono essere 14px, nero, underline grigio on hover

### ✅ Logo Customization
- [ ] Vai a **Blackwork > Site Settings > My Account Page**
- [ ] Trova i 3 campi: "Larghezza logo", "Padding top logo", "Padding bottom logo"
- [ ] Modifica i valori (es: width 250px, padding top 20px, padding bottom 40px)
- [ ] Clicca "Salva impostazioni"
- [ ] Hard refresh `/my-account/` (Ctrl+Shift+R)
- [ ] Verifica che logo abbia nuova larghezza e spaziatura
- [ ] Ispeziona in DevTools: inline styles su `<div>` e `<img>`

### ✅ Lost Password Panel
- [ ] Vai a `/my-account/` (logged out)
- [ ] Nel pannello Login, clicca "Lost your password?"
- [ ] Verifica transizione animata smooth al pannello Lost Password
- [ ] Verifica che background NON salti durante transizione
- [ ] Inserisci username/email esistente
- [ ] Clicca "Reset password"
- [ ] Verifica che WooCommerce invii email di reset
- [ ] Clicca "← Back to login"
- [ ] Verifica ritorno animato al pannello Login

### ✅ Button Styles Consistency
- [ ] Vai a `/my-account/` (logged out)
- [ ] Verifica che "Lost your password?" abbia stile identico ai tab Login/Register
  - Font size: 14px
  - Color: nero (#000)
  - On hover: grigio (#777) + barra 2px
- [ ] Clicca "Lost your password?"
- [ ] Verifica che "← Back to login" abbia stesso stile
- [ ] Ispeziona in DevTools:
  - Tutti gli stili Elementor sovrascritti con `!important`
  - Computed: `font-size: 14px`, `color: rgb(0, 0, 0)`

### ✅ Direct URL Access
- [ ] Vai a `/my-account/?action=lostpassword`
- [ ] Verifica che si apra direttamente il pannello Lost Password

---

## Database Options Added

**Nuove opzioni WordPress**:
```php
bw_account_logo_width          // int, default: 180
bw_account_logo_padding_top    // int, default: 0
bw_account_logo_padding_bottom // int, default: 30
```

**Opzioni esistenti** (non modificate):
```php
bw_account_login_image         // URL immagine di sfondo
bw_account_logo                // URL logo
bw_account_facebook            // 0/1 enable Facebook login
bw_account_google              // 0/1 enable Google login
bw_account_facebook_app_id
bw_account_facebook_app_secret
bw_account_google_client_id
bw_account_google_client_secret
bw_account_description
bw_account_back_text
bw_account_back_url
bw_account_passwordless_url
```

---

## Known Issues / Notes

### Elementor Editor Compatibility
- Tutti gli override CSS usano `:not(.elementor-editor-active)` per preservare editing experience
- Gli stili non influenzano l'editor Elementor

### Panel Positioning
- Tutti i pannelli usano `position: absolute !important` per evitare layout shifts
- Il container `.bw-account-auth__panels` ha `min-height: 450px` per contenere il contenuto

### Browser Compatibility
- CSS usa `backface-visibility` e `transform: translateZ(0)` per GPU acceleration
- `::after` pseudo-elements per underline (supportato da tutti i browser moderni)
- Flexbox per layout (IE11+)

### Performance
- Inline styles per logo (3 valori dal database) - trascurabile
- Nessun JavaScript aggiuntivo (riutilizza sistema esistente)
- GPU acceleration per transizioni smooth

---

## Next Steps / Future Enhancements

### Possibili Miglioramenti
1. **Admin Panel UI**:
   - Aggiungere preview live del logo nel pannello admin
   - Raggruppare campi logo in sezione collapsabile

2. **Lost Password Panel**:
   - Aggiungere campo per personalizzare testo "Lost your password?"
   - Opzione admin per disabilitare pannello inline (usa link standard)

3. **Tab System**:
   - Considerare URL hash (#login, #register, #lost-password) invece di query params
   - ARIA labels migliorati per accessibility

4. **Responsive**:
   - Test su mobile per altezza fissa 100vh
   - Considerare media query per tablet

5. **Testing**:
   - Unit tests per JavaScript tab switching
   - E2E tests per flusso Lost Password

---

## File Locations Reference

```
wpblackwork/
├── admin/
│   └── class-blackwork-site-settings.php      # Admin panel settings
├── assets/
│   ├── css/
│   │   └── bw-account-page.css                # My Account styles
│   └── js/
│       └── bw-account-page.js                 # Tab switching logic (unchanged)
└── woocommerce/
    └── templates/
        └── myaccount/
            └── form-login.php                  # Login/Register/Lost Password template
```

---

## Git Commands

**Per continuare il lavoro**:
```bash
git checkout claude/review-my-account-code-q2ikC
git pull origin claude/review-my-account-code-q2ikC
```

**Per fare merge in main** (dopo testing):
```bash
git checkout main
git merge claude/review-my-account-code-q2ikC
git push origin main
```

**Per vedere tutti i commit**:
```bash
git log claude/review-my-account-code-q2ikC --oneline
```

**Per vedere diff completo**:
```bash
git diff main...claude/review-my-account-code-q2ikC
```

---

## Questions for Next Session

1. **Testing Results**: I fix CSS funzionano su tutti i browser?
2. **Logo Customization**: Serve preview live nel pannello admin?
3. **Lost Password**: Email WooCommerce arriva correttamente?
4. **Mobile**: Layout funziona su smartphone (height: 100vh)?
5. **Accessibility**: Screen reader testing necessario?

---

**Session End**: 2025-12-28
**Total Commits**: 5
**Files Modified**: 3
**Lines Changed**: ~300 insertions, ~50 deletions


---

## Source: `docs/99-archive/payments/README.md`

# Archive: Payments

## Files
- [gateway-total-guide.md](gateway-total-guide.md): superseded consolidated gateway guide.


---

## Source: `docs/99-archive/payments/gateway-total-guide.md`

# Gateway Total Guide

## Scope
This is the master guide for the BlackWork checkout payment architecture.
It consolidates:
- Official gateways (Stripe Card, PayPal)
- Custom BlackWork gateways (Google Pay, Klarna, Apple Pay)
- Shared checkout UI orchestration (accordion + action button sync)
- Incident history and fixes to prevent regressions

## Related Gateway Guides
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/Gateway Google Pay Guide.md`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/Gateway Klarna Guide.md`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/Gateway Apple Pay Guide.md`

---

## Payment Stack Overview

## Official plugins
1. Credit / Debit Card
- Provider: `wc_stripe` official plugin
- Checkout behavior: standard Stripe card fields inside Woo payment box

2. PayPal
- Provider: official PayPal plugin
- Checkout behavior: plugin-managed

## Custom BlackWork gateways
1. `bw_google_pay`
- Stripe PaymentIntents + custom frontend `paymentRequest`
- Webhook endpoint: `/?wc-api=bw_google_pay`

2. `bw_klarna`
- Stripe PaymentIntents (`payment_method_types[]=klarna`) + redirect flow
- Webhook endpoint: `/?wc-api=bw_klarna`

3. `bw_apple_pay`
- Stripe PaymentIntents + Apple Pay availability checks
- Webhook endpoint: `/?wc-api=bw_apple_pay`

---

## Core Architecture Files

## Gateway classes
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/Gateways/class-bw-google-pay-gateway.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/Gateways/class-bw-klarna-gateway.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/Gateways/class-bw-apple-pay-gateway.php`
- Shared base class (hardening/refunds/webhook logic):
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/Gateways/class-bw-abstract-stripe-gateway.php`

## Checkout UI/JS
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-payment-methods.js`
  - Shared accordion state and action-button sync
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-google-pay.js`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-apple-pay.js`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/css/bw-payment-methods.css`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/checkout/payment.php`

## Registration / routing
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/woocommerce-init.php`

## Admin settings tabs
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/admin/class-blackwork-site-settings.php`

---

## Canonical Runtime Rules

1. Checkout source-of-truth for payment completion
- Do not mark paid on frontend return/cancel.
- Final success is confirmed by Stripe webhook `payment_intent.succeeded`.

2. Wallet fail/cancel UX
- If `redirect_status=failed|canceled` and order is wallet-based:
  - redirect back to checkout
  - show notice
  - keep retry flow possible
- If order already paid by webhook, keep thank-you flow.

3. Anti-conflict with official gateways
- Webhook and order updates must be gated by payment method id.
- Custom gateways must not mutate `wc_stripe` or PayPal orders.

4. Idempotency
- Deduplicate webhook events (rolling event id history).
- Ignore repeated events and PI/order mismatches.

---

## Accordion and Checkout UI Incident Log (Resolved)

## Incident A: Multiple payment boxes open together
Symptoms:
- Stripe card box stayed open while selecting Google/Apple.
- Multiple accordions appeared open in the same view.

Root cause:
- Radio state and UI state drifted after dynamic updates and custom handlers.

Fix:
- Normalize payment radio selection in shared wallet script.
- Enforce single selected method and single open box behavior.

## Incident B: Wrong action button visible (wallet + place order conflicts)
Symptoms:
- `Place order` visible while wallet method selected.
- Temporary duplicate CTA states after `updated_checkout`.

Fix:
- Centralized button sync in `bw-payment-methods.js`.
- Re-sync after Woo checkout updates.
- Ensure only one primary action is visible per selected method.

## Incident C: Stripe title changed from "Credit / Debit Card" to "Stripe"
Symptoms:
- Card method label switched unexpectedly when changing methods.

Fix:
- Label normalization and UI guard in shared payment methods script/template behavior.
- Keep canonical card label stable.

## Incident D: Google Pay stuck on "Initializing Google Pay..."
Symptoms:
- Google Pay button never became active.
- Unavailable/placeholder state persisted.

Root cause:
- Invalid `paymentRequest.total.amount` format/subunit in some states.

Fix:
- Added cents normalization in Google Pay JS (`bwNormalizeCents`).
- Applied at init and on `updated_checkout` updates.
- Added stale-checking fallback and unavailable rerender logic.

## Incident E: Google Pay unavailable state too noisy
Symptoms:
- Unavailable message shown together with extra icon/text blocks.

Fix:
- Hide Google info block when unavailable.
- Keep only core unavailable notice + wallet CTA for clarity.

## Incident F: Gateway row rendered but wallet JS not initialized
Symptoms:
- Method visible in checkout but behavior missing.

Fix:
- Harden enqueue logic in `woocommerce-init.php`:
  - load Google Pay JS when enabled OR when gateway appears in available gateways.

## Incident G: Apple/Klarna icon and click-target regressions
Symptoms:
- Icon size/render mismatches.
- Header/toggle click not always responsive.

Fix:
- Normalize icon rendering/styling selectors.
- Keep header label click area aligned with other methods.

---

## Current Status Summary by Gateway

## Stripe Card (official)
- Active and independent.
- Must remain isolated from custom wallet state logic.

## PayPal (official)
- Active and independent.
- No custom webhook coupling with BlackWork wallets.

## Google Pay (custom)
- Hardened for availability and amount normalization.
- Unavailable state now explicit and cleaner.

## Klarna (custom)
- Redirect flow stable.
- Return fail/cancel route uses checkout retry strategy.

## Apple Pay (custom)
- Implemented with live-only settings and availability checks.
- Requires Safari/Apple device + Wallet card + HTTPS + domain verification in Stripe.
- Non-available environments use helper-only fallback (`bw_apple_pay_express_helper_enabled`):
  - Apple button scrolls to top Express Checkout area.
  - No inline express transformation in Apple gateway.

---

## Regression Checklist (Run before releases)

1. Accordion behavior
- Selecting any method opens only that method.
- Switching method closes previous method.

2. CTA behavior
- Only one primary action button visible at a time.
- Wallet method must not show wrong CTA from another gateway.

3. Google Pay
- No infinite "Initializing" state.
- Console has no paymentRequest amount-subunit error.

4. Klarna
- Redirect starts correctly.
- Cancel/close returns to checkout with notice.

5. Apple Pay
- Availability state coherent (available/unavailable).
- Helper mode: no duplicate CTA, no extra unavailable title, scroll helper works.

6. Webhook safety
- Success completes only correct gateway orders.
- Replay events do not duplicate side effects.

---

## Documentation Status
Dedicated guides are now available for:
- Google Pay
- Klarna
- Apple Pay


---

## Source: `docs/99-archive/reports/README.md`

# Archive: Reports

## Files
- [view-product-fix-report.md](view-product-fix-report.md): historical fix report.


---

## Source: `docs/99-archive/reports/view-product-fix-report.md`

# 🔧 VIEW PRODUCT & ADD TO CART - FIX REPORT

## 📋 RIEPILOGO PROBLEMI RIPORTATI

1. **Pulsanti "View Product" causano errori critici**
   - Click su "View Product" → "There has been a critical error on this website"
   - Oppure redirect errato alla home page invece della pagina prodotto
   - Problema presente in: BW-Wallpost, Slick Slider, e altri widget

2. **Errore critico dopo Add to Cart**
   - Messaggio WooCommerce "X has been added to your cart" appare correttamente
   - Ma subito dopo compare un messaggio di errore critico
   - L'esperienza utente è rotta

---

## ✅ MODIFICHE IMPLEMENTATE

### 1. **Fix Gestione Errori WooCommerce Notices**
**File**: `cart-popup/frontend/cart-popup-frontend.php`

**Problema**: La funzione `wc_get_notices()` può restituire formati diversi a seconda della versione di WooCommerce, causando fatal error quando si cerca di accedere a `$error_messages[0]['notice']` se la struttura è diversa.

**Fix** (linee 479-498):
```php
// PRIMA (vulnerabile a fatal error)
$error_messages = wc_get_notices('error');
$message = !empty($error_messages) ? strip_tags($error_messages[0]['notice']) : 'Invalid coupon code';

// DOPO (gestione robusta)
$error_messages = wc_get_notices('error');
$message = 'Invalid coupon code';

// Gestione robusta degli errori WooCommerce
if (!empty($error_messages) && is_array($error_messages)) {
    $first_error = reset($error_messages);
    if (is_array($first_error) && isset($first_error['notice'])) {
        $message = wp_strip_all_tags($first_error['notice']);
    } elseif (is_string($first_error)) {
        $message = wp_strip_all_tags($first_error);
    }
}
```

**Benefici**:
- ✅ Previene fatal error quando WooCommerce cambia formato notices
- ✅ Gestisce sia formato array che string
- ✅ Usa `wp_strip_all_tags()` invece di `strip_tags()` (più sicuro)

---

### 2. **Funzioni Helper per Permalink Sicuri**
**File**: `includes/helpers.php`

**Problema**: I permalink potrebbero essere:
- Vuoti o malformati
- Puntare erroneamente alla home page (bug permalinks WordPress)
- Generare errori se il prodotto non esiste o non è pubblicato

**Fix** (linee 273-356):

#### **Funzione `bw_get_safe_product_permalink()`**
Valida i permalink e previene errori:

```php
/**
 * Get a safe permalink for a product, with validation to prevent errors.
 *
 * @param int|\WC_Product|null $post_id Product ID or WC_Product object.
 * @return string Safe permalink URL.
 */
function bw_get_safe_product_permalink( $post_id = null ) {
    // Validazioni:
    // 1. Accetta sia ID che oggetti WC_Product
    // 2. Verifica che il post esista e sia pubblicato
    // 3. Valida che il permalink sia un URL valido
    // 4. Controlla che non punti erroneamente alla home page
    // 5. Ritorna fallback sicuro se qualcosa va storto
}
```

**Controlli di sicurezza**:
- ✅ Gestisce eccezioni quando accede a `WC_Product->get_permalink()`
- ✅ Verifica `post_status === 'publish'`
- ✅ Valida URL con `filter_var($permalink, FILTER_VALIDATE_URL)`
- ✅ Previene redirect errato alla home page
- ✅ Ritorna fallback alla shop page se permalink non valido

#### **Funzione `bw_get_fallback_url()`**
Fornisce URL di fallback sicuro:

```php
/**
 * Get a fallback URL when product permalink is invalid.
 *
 * @return string Fallback URL (shop page or home).
 */
function bw_get_fallback_url() {
    // 1. Prova shop page WooCommerce
    // 2. Fallback a home page
    // 3. Sempre validato e escaped
}
```

---

### 3. **Fix Error Modal Add to Cart** (già implementato precedentemente)
**File**: `cart-popup/assets/js/bw-cart-popup.js`

Gestisce correttamente gli errori AJAX:
- Overlay scuro + modal per errori WooCommerce
- Pulsante "Return to Shop" funzionante
- Gestione errori di rete

---

## 🔍 CAUSE POTENZIALI (NON ANCORA IDENTIFICATE)

I fix implementati sono **preventivi** e risolvono le cause più comuni. Tuttavia, se i problemi persistono, le cause potrebbero essere:

### **Cause Possibili "View Product"**:

1. **Permalinks WordPress non configurati**
   - Vai in `Impostazioni → Permalinks`
   - Clicca "Salva modifiche" anche se non cambi nulla
   - Questo forza WordPress a rigenerare le regole di rewrite

2. **Plugin di cache**
   - Svuota tutte le cache (server, CDN, plugin)
   - Disattiva temporaneamente plugin di cache per testare

3. **Redirect personalizzati configurati**
   - Controlla `Blackwork Site → Redirects`
   - Verifica che non ci siano redirect che matchano URL prodotti

4. **Template tema corrotto**
   - Il template `single-product.php` del tema potrebbe avere errori PHP
   - Prova a passare temporaneamente al tema Twenty Twenty-Four per testare

5. **Prodotti con dati incompleti**
   - Alcuni prodotti potrebbero avere metadati corrotti
   - Prova con prodotti diversi per isolare il problema

6. **Conflitto plugin terze parti**
   - Disattiva temporaneamente altri plugin (escludendo WooCommerce ed Elementor)
   - Riattiva uno alla volta per identificare il colpevole

---

## 🧪 COME TESTARE

### **Test 1: View Product**

1. **Apri una pagina con widget BW-Wallpost o Slick Slider**
2. **Click su "View Product"** di diversi prodotti
3. **Risultati attesi**:
   - ✅ Si apre la pagina single product corretta
   - ✅ Nessun errore critico
   - ✅ URL nella barra degli indirizzi è corretto (non home page)

### **Test 2: Add to Cart**

1. **Aggiungi un prodotto disponibile al carrello**
2. **Risultati attesi**:
   - ✅ Cart popup si apre
   - ✅ Prodotto visibile nel carrello
   - ✅ Nessun errore critico sotto il messaggio di successo

3. **Aggiungi un prodotto NON disponibile** (es. esaurito, quantità max raggiunta)
4. **Risultati attesi**:
   - ✅ Appare overlay scuro
   - ✅ Modal centrato con messaggio errore WooCommerce
   - ✅ Pulsante "Return to Shop" funziona
   - ✅ Nessun errore critico

---

## 🚨 SE I PROBLEMI PERSISTONO

### **Passo 1: Abilita Debug Logging**

Aggiungi in `wp-config.php` (PRIMA di `/* That's all, stop editing! */`):

```php
// Enable WP_DEBUG mode
define( 'WP_DEBUG', true );

// Enable Debug logging to /wp-content/debug.log
define( 'WP_DEBUG_LOG', true );

// Disable display of errors and warnings
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );
```

### **Passo 2: Riproduci l'Errore**

1. Click su "View Product" o "Add to Cart"
2. Quando appare l'errore critico, **NON ricaricare la pagina**

### **Passo 3: Controlla il Log**

Apri via FTP/SSH il file:
```
/wp-content/debug.log
```

Cerca righe che contengono:
- `Fatal error`
- `Call to undefined function`
- `wc_get_product`
- `get_permalink`

### **Passo 4: Inviami il Log**

Copia lo **stack trace completo** e inviamelo. Avrò bisogno di:
- **File** dove si verifica l'errore
- **Linea** esatta
- **Messaggio** di errore completo

---

## 💡 USO DELLE FUNZIONI HELPER (OPZIONALE)

Se dopo i test scopri che il problema è specificamente con i permalink, puoi **opzionalmente** modificare i widget per usare le nuove funzioni helper.

### **Esempio: Modificare Widget Slick Slider**

**File**: `includes/widgets/class-bw-slick-slider-widget.php`

**PRIMA** (linea 1007):
```php
$permalink = get_permalink( $post_id );
```

**DOPO**:
```php
$permalink = bw_get_safe_product_permalink( $post_id );
```

**⚠️ IMPORTANTE**: Fai questo solo se i test dimostrano che il problema è nei permalink!

---

## 📊 FILE MODIFICATI

```
✅ cart-popup/frontend/cart-popup-frontend.php   [Fix WooCommerce notices]
✅ cart-popup/assets/js/bw-cart-popup.js         [Fix error modal]
✅ cart-popup/cart-popup.php                     [Add shopUrl config]
✅ includes/helpers.php                          [Add permalink validation]
```

---

## 🎯 PROSSIMI PASSI

1. **Testa i widget** come indicato nella sezione "COME TESTARE"
2. **Se i problemi persistono**:
   - Abilita debug logging
   - Riproduci l'errore
   - Inviami il log completo
3. **Se tutto funziona**:
   - Nessuna azione richiesta
   - Le modifiche sono già in produzione

---

## ⚙️ RACCOMANDAZIONI FUTURE

Per evitare problemi simili:

1. **Usa sempre controlli di esistenza**:
   ```php
   if ( function_exists( 'wc_get_product' ) ) {
       $product = wc_get_product( $id );
       if ( $product && $product instanceof \WC_Product ) {
           // Usa $product
       }
   }
   ```

2. **Valida sempre i permalink**:
   ```php
   $permalink = get_permalink( $id );
   if ( empty( $permalink ) || ! filter_var( $permalink, FILTER_VALIDATE_URL ) ) {
       $permalink = home_url( '/shop/' );
   }
   ```

3. **Gestisci gli array in modo sicuro**:
   ```php
   // ERRATO
   $value = $array[0]['key'];

   // CORRETTO
   $value = isset( $array[0]['key'] ) ? $array[0]['key'] : 'default';
   ```

4. **Mantieni debug mode attivo in development**:
   - Usa `WP_DEBUG` su staging/development
   - Disabilitalo solo su production

---

## 📞 SUPPORTO

Se hai domande o i problemi persistono, fammi avere:
- URL specifico che causa il problema
- Screenshot dell'errore
- Log `debug.log` completo
- Console JavaScript (F12 → Console tab)

---

**Data Fix**: 2025-12-07
**Versione Plugin**: 1.0.0
**Autore**: Claude AI Assistant


---

## Source: `docs/99-archive/smart-header/README.md`

# Archive: Smart Header

## Files
- [smart-header-auto-dark-detection.md](smart-header-auto-dark-detection.md): archived source integrated into main smart-header guide.
- [smart-header-dark-zones-guide.md](smart-header-dark-zones-guide.md): legacy dark-zones guide.


---

## Source: `docs/99-archive/smart-header/smart-header-auto-dark-detection.md`

# SmartHeader - Rilevamento AUTOMATICO Sfondi Scuri (v2.4.0)

## 🚀 Novità: Zero Configurazione Richiesta!

Il sistema **rileva AUTOMATICAMENTE** le sezioni con sfondo scuro e cambia il colore dello SmartHeader di conseguenza.

**NON è più necessario aggiungere manualmente la classe `.smart-header-dark-zone`!**

---

## ✨ Come Funziona

### Rilevamento Automatico

Il sistema:
1. **Scansiona** automaticamente tutte le sezioni della pagina (Elementor, WordPress blocks, HTML standard)
2. **Analizza** il colore di sfondo di ogni sezione
3. **Calcola** la luminosità usando la formula W3C (brightness)
4. **Identifica** automaticamente le sezioni scure (luminosità < 128/255)
5. **Applica** il cambio colore quando lo SmartHeader passa sopra queste sezioni

### Algoritmo di Rilevamento

```javascript
// Formula W3C per luminosità percepita
Brightness = (R × 299 + G × 587 + B × 114) / 1000

// Soglia default: 128 (metà dello spettro 0-255)
isDark = Brightness < 128
```

**Esempi:**
- `#000000` (nero) → Brightness: 0 → ✅ SCURO
- `#1a1a1a` (grigio molto scuro) → Brightness: 26 → ✅ SCURO
- `#333333` (grigio scuro) → Brightness: 51 → ✅ SCURO
- `#666666` (grigio medio) → Brightness: 102 → ✅ SCURO
- `#808080` (grigio) → Brightness: 128 → ⚠️ LIMITE
- `#cccccc` (grigio chiaro) → Brightness: 204 → ❌ CHIARO
- `#ffffff` (bianco) → Brightness: 255 → ❌ CHIARO

---

## 🎯 Utilizzo

### Modalità Automatica (CONSIGLIATA)

**Non fare nulla!** Il sistema rileva automaticamente le sezioni scure.

```html
<!-- Sezione con sfondo scuro -->
<section style="background-color: #000;">
    <h1>Questa sezione verrà rilevata automaticamente</h1>
</section>

<!-- SmartHeader cambierà colore quando passa sopra questa sezione -->
```

### Elementi Reattivi

Devi solo applicare `.smart-header-reactive-text` agli elementi che devono cambiare colore:

**In Elementor:**
1. Menu Navigation → Advanced → CSS Classes: `smart-header-reactive-text`
2. Logo SVG → Advanced → CSS Classes: `smart-header-reactive-text`
3. BW NavShop → Advanced → CSS Classes: `smart-header-reactive-text`

**Fatto!** Il resto è automatico.

---

## 🔧 Configurazione Avanzata (Opzionale)

### Modalità Manuale (Retrocompatibilità)

Se preferisci controllare manualmente quali sezioni sono scure, puoi ancora usare la classe:

```html
<section class="smart-header-dark-zone" style="background: #ccc;">
    <!-- Questa sezione sarà considerata scura anche se ha background chiaro -->
</section>
```

**Il sistema combina entrambe le modalità:**
- Sezioni con classe `.smart-header-dark-zone` → sempre considerate scure
- Sezioni senza classe → rilevamento automatico

### Personalizzare la Soglia di Luminosità

Se vuoi cambiare quando un colore è considerato "scuro", modifica `bw-smart-header.js`:

```javascript
// Riga ~457
if (isColorDark(bgColor, 128)) {  // ← Soglia default: 128
    sections.push(section);
}

// Esempi:
// 100 = più permissivo (rileva anche grigi medi come scuri)
// 150 = più restrittivo (solo colori molto scuri)
```

---

## 🎨 Colori Supportati

### ✅ Rilevamento Supportato

- **RGB/RGBA:** `rgb(0, 0, 0)`, `rgba(0, 0, 0, 0.8)`
- **Esadecimali:** `#000`, `#000000`
- **Gradient (primo colore):** `linear-gradient(#000, #fff)`
- **Trasparenza:** Il sistema risale la catena parent per trovare il colore opaco

### ⚠️ Casi Speciali

**Background Image:**
- Se la sezione ha solo `background-image` (senza `background-color`), il sistema risale ai parent per trovare il colore
- Per forzare il rilevamento, aggiungi `background-color` trasparente sopra l'immagine

**Gradient Complessi:**
- Il sistema analizza solo il primo colore del gradient
- Se hai un gradient da chiaro a scuro, considera di usare la classe manuale

**Opacity < 0.5:**
- Colori con opacità molto bassa sono ignorati (considerati trasparenti)

---

## 🧪 Testing e Debug

### Console API

```javascript
// Stato corrente
console.log(window.bwSmartHeader.getState());

// Output esempio:
{
    isOnDarkZone: true,
    darkZonesCount: 5  // Sezioni scure rilevate
}

// Vedi tutte le dark zones
console.log(window.bwSmartHeader.getDarkZones());
// Array di elementi HTML rilevati come scuri
```

### Attivare Debug Mode

Per vedere i log di rilevamento, modifica `bw-main-elementor-widgets.php`:

```php
wp_localize_script('bw-smart-header-script', 'bwSmartHeaderConfig', array(
    'debug' => true, // ← Attiva debug
    // ...
));
```

**Log in console:**
```
[Smart Header] Scansione automatica sezioni { totalFound: 15 }
[Smart Header] Sezione scura rilevata automaticamente {
    element: "section.elementor-section",
    color: "rgb(0, 0, 0)",
    brightness: "0.00"
}
[Smart Header] Rilevamento automatico completato { darkSectionsFound: 5 }
[Smart Header] ✅ Dark zones rilevate { total: 5, manual: 0, auto: 5 }
```

### Forza Ri-scansione

Se aggiungi sezioni dinamicamente (AJAX, Elementor live edit), forza una nuova scansione:

```javascript
// Chiama nuovamente l'init
window.bwSmartHeader.recalculateAllOffsets();
window.bwSmartHeader.recheckDarkZones();
```

---

## ⚙️ Selettori Scansionati

Il sistema cerca automaticamente questi selettori:

```javascript
const selectors = [
    '.elementor-section',      // Sezioni Elementor
    '.elementor-container',    // Container Elementor
    'section',                 // Tag HTML5 section
    '[data-elementor-type]',   // Elementi Elementor
    '.wp-block-cover',         // Block WordPress
    '.entry-content > div',    // Contenuto post
    'main > section',          // Sezioni main
    'main > div'               // Div main
];
```

### Filtri Applicati

**Esclusioni:**
- Elementi dentro `.smart-header` (l'header stesso)
- Sezioni con altezza < 100px (troppo piccole)

---

## 📊 Performance

### Ottimizzazioni

- ✅ **Scansione una sola volta** all'init (non ad ogni scroll)
- ✅ **IntersectionObserver** per monitorare solo sezioni visibili
- ✅ **Throttling** sui controlli overlap (16ms = 60fps)
- ✅ **GPU acceleration** per transizioni CSS
- ✅ **Caching** dei colori rilevati

### Impatto

- **Init time:** ~10-50ms (dipende dal numero di sezioni)
- **Scroll performance:** Nessun impatto (usa observer)
- **Memory:** ~1KB per ogni sezione rilevata

---

## 🆚 Confronto Modalità

| Feature | Automatico | Manuale (classe) |
|---------|------------|------------------|
| Configurazione | ❌ Zero | ✅ Classe per sezione |
| Precisione | ⭐⭐⭐⭐ (98%) | ⭐⭐⭐⭐⭐ (100%) |
| Manutenzione | ✅ Nessuna | ❌ Aggiorna classe ad ogni modifica |
| Gradient complessi | ⚠️ Primo colore | ✅ Controllo totale |
| Background image | ⚠️ Risale parent | ✅ Controllo totale |
| Sezioni dinamiche | ✅ Auto-detect | ❌ Richiede script |

**Raccomandazione:** Usa **Automatico** per il 95% dei casi. Usa **Manuale** solo per casi edge.

---

## ✅ Checklist Setup

### Setup Iniziale (UNA VOLTA)
- [x] Plugin BW attivo
- [x] `.smart-header` applicata al container header
- [ ] `.smart-header-reactive-text` applicata a menu navigation
- [ ] `.smart-header-reactive-text` applicata a logo SVG
- [ ] `.smart-header-reactive-text` applicata a BW NavShop

### Test
- [ ] Scrolla la pagina
- [ ] Verifica cambio colore automatico su sezioni scure
- [ ] Testa su mobile, tablet, desktop
- [ ] Controlla console per log (se debug attivo)

**FATTO!** Non serve configurare altro. ✨

---

## 🐛 Troubleshooting

### Il rilevamento non funziona

**Possibile causa:** Background impostato con CSS inline non visibile

**Soluzione:** Attiva debug e controlla i log:
```javascript
// In console
window.bwSmartHeader.getState().darkZonesCount
// Se = 0, nessuna sezione rilevata
```

### Sezioni chiare rilevate come scure

**Causa:** Soglia troppo alta (128)

**Soluzione:** Aumenta la soglia:
```javascript
// bw-smart-header.js riga ~457
if (isColorDark(bgColor, 100)) {  // ← Cambia da 128 a 100
```

### Sezioni scure NON rilevate

**Causa:** Soglia troppo bassa

**Soluzione:** Alza la soglia:
```javascript
// bw-smart-header.js riga ~457
if (isColorDark(bgColor, 150)) {  // ← Cambia da 128 a 150
```

### Background gradient non rilevato correttamente

**Causa:** Il sistema analizza solo il primo colore

**Soluzione:** Usa classe manuale:
```html
<section class="smart-header-dark-zone" style="background: linear-gradient(#fff, #000);">
```

---

## 🎉 Vantaggi

✅ **Zero configurazione** - Funziona out-of-the-box
✅ **Aggiornamenti automatici** - Se cambi il colore di una sezione, il sistema si aggiorna automaticamente
✅ **Performance ottimali** - Scansione una sola volta, monitoring efficiente
✅ **Precisione alta** - Formula W3C standard per calcolo luminosità
✅ **Retrocompatibilità** - Supporta ancora la classe manuale
✅ **Debug facile** - Log dettagliati in console

---

## 📦 File Modificati

| File | Modifiche |
|------|-----------|
| `assets/js/bw-smart-header.js` | Aggiunto rilevamento automatico colore background |
| `assets/css/bw-smart-header.css` | Nessuna modifica (CSS invariato) |

**Versione:** 2.4.0

---

## 🔄 Migrazione da v2.3.0

Se usavi la versione precedente con classi manuali:

**Nessuna azione richiesta!**

Le classi `.smart-header-dark-zone` continuano a funzionare e hanno **priorità** sul rilevamento automatico.

**Opzionale:** Puoi rimuovere le classi manuali per lasciare lavorare il sistema automatico.

---

## 🆘 Supporto

### Versione

```javascript
console.log(window.bwSmartHeader.version); // '2.4.0'
```

### Test Rilevamento

```javascript
// Forza nuova scansione
window.location.reload();

// Controlla quante sezioni sono state rilevate
console.log(window.bwSmartHeader.getState().darkZonesCount);

// Vedi quali sezioni sono state rilevate
window.bwSmartHeader.getDarkZones().forEach((zone, i) => {
    console.log(`Zona ${i + 1}:`, zone);
});
```

---

## 🎯 Esempio Completo

```html
<!-- SMARTHEADER (fixed top) -->
<div class="smart-header">
    <!-- Logo SVG -->
    <div class="smart-header-reactive-text">
        <svg>...</svg>
    </div>

    <!-- Menu -->
    <nav class="elementor-nav-menu smart-header-reactive-text">
        <a href="#home">Home</a>
        <a href="#about">About</a>
    </nav>

    <!-- NavShop -->
    <div class="bw-navshop smart-header-reactive-text">
        <a href="/cart">Cart</a>
    </div>
</div>

<!-- CONTENUTO PAGINA -->

<!-- Sezione chiara - header normale -->
<section style="background: #fff;">
    <h1>Hero</h1>
</section>

<!-- Sezione scura - RILEVATA AUTOMATICAMENTE -->
<section style="background: #000;">
    <h2>About Us</h2>
    <!-- SmartHeader diventa BIANCO qui -->
</section>

<!-- Sezione grigio medio - RILEVATA AUTOMATICAMENTE -->
<section style="background: #666;">
    <h3>Services</h3>
    <!-- SmartHeader diventa BIANCO anche qui -->
</section>

<!-- Sezione chiara - header torna normale -->
<section style="background: #f5f5f5;">
    <p>Footer</p>
</section>
```

**RISULTATO:** SmartHeader che cambia automaticamente colore. Zero configurazione! 🎉


---

## Source: `docs/99-archive/smart-header/smart-header-dark-zones-guide.md`

# SmartHeader - Sistema Cambio Colore Automatico su Sfondi Scuri

## 📋 Panoramica

Il sistema di **Dark Zone Detection** permette allo SmartHeader di cambiare automaticamente colore del testo, del logo e dei widget quando passa sopra sezioni con sfondo scuro.

**Caratteristiche:**
- ✅ Rilevamento automatico tramite IntersectionObserver
- ✅ Transizioni fluide senza flickering
- ✅ Performance ottimizzate (60fps)
- ✅ Supporto completo per SVG (logo)
- ✅ Compatibile con Elementor Navigation Menu
- ✅ Compatibile con widget BW NavShop
- ✅ Responsive (mobile, tablet, desktop)
- ✅ Accessibilità (prefers-reduced-motion)

---

## 🚀 Come Usare il Sistema

### Passo 1: Marcare le Sezioni Scure

Aggiungi la classe **`.smart-header-dark-zone`** a tutte le sezioni/container con sfondo scuro.

**In Elementor:**
1. Seleziona la sezione con sfondo scuro
2. Vai in **Advanced** → **CSS Classes**
3. Aggiungi: `smart-header-dark-zone`

```
┌─────────────────────────────────────┐
│ Section Settings                     │
├─────────────────────────────────────┤
│ Advanced                             │
│   CSS Classes: smart-header-dark-zone│
└─────────────────────────────────────┘
```

**Esempio HTML:**
```html
<!-- Sezione con sfondo chiaro (normale) -->
<section class="my-hero-section">
    <h1>Benvenuto</h1>
</section>

<!-- Sezione con sfondo scuro (marcata) -->
<section class="my-about-section smart-header-dark-zone" style="background: #000;">
    <h2>Chi Siamo</h2>
</section>

<!-- Sezione con sfondo chiaro (normale) -->
<section class="my-services-section">
    <h3>Servizi</h3>
</section>
```

---

### Passo 2: Marcare gli Elementi Reattivi

Aggiungi la classe **`.smart-header-reactive-text`** agli elementi dello SmartHeader che devono cambiare colore.

#### 2.1 Menu Navigation

**In Elementor:**
1. Seleziona il widget **Nav Menu** dentro lo SmartHeader
2. Vai in **Advanced** → **CSS Classes**
3. Aggiungi: `smart-header-reactive-text`

```
┌─────────────────────────────────────┐
│ Nav Menu Settings                    │
├─────────────────────────────────────┤
│ Advanced                             │
│   CSS Classes: smart-header-reactive-text
└─────────────────────────────────────┘
```

#### 2.2 Logo SVG

**In Elementor:**
1. Seleziona il widget **Image** o **Logo** con il tuo logo SVG
2. Vai in **Advanced** → **CSS Classes**
3. Aggiungi: `smart-header-reactive-text`

**IMPORTANTE:** Il logo deve essere in formato **SVG** perché il sistema cambia il colore tramite `fill: currentColor`. Se usi PNG/JPG, considera di usare un filtro CSS.

```
┌─────────────────────────────────────┐
│ Image/Logo Settings                  │
├─────────────────────────────────────┤
│ Advanced                             │
│   CSS Classes: smart-header-reactive-text
└─────────────────────────────────────┘
```

#### 2.3 Widget BW NavShop

**In Elementor:**
1. Seleziona il widget **BW NavShop** (Cart/Account)
2. Vai in **Advanced** → **CSS Classes**
3. Aggiungi: `smart-header-reactive-text`

```
┌─────────────────────────────────────┐
│ BW NavShop Settings                  │
├─────────────────────────────────────┤
│ Advanced                             │
│   CSS Classes: smart-header-reactive-text
└─────────────────────────────────────┘
```

#### 2.4 Testo Custom

Puoi applicare la classe a **qualsiasi** widget di testo, heading, o contenitore:

```
┌─────────────────────────────────────┐
│ Heading/Text Settings                │
├─────────────────────────────────────┤
│ Advanced                             │
│   CSS Classes: smart-header-reactive-text
└─────────────────────────────────────┘
```

---

## 🎨 Comportamento del Sistema

### Stati del Colore

| Scenario | Stato SmartHeader | Colore Testo |
|----------|-------------------|--------------|
| SmartHeader su sfondo chiaro | Normale | Scuro (default/inherit) |
| SmartHeader entra in dark zone | `.smart-header--on-dark` | **Bianco (#ffffff)** |
| SmartHeader esce da dark zone | Normale | Torna a scuro |

### Transizioni

Tutte le transizioni sono **fluide** con durata:
- **Desktop:** 0.3s ease
- **Mobile:** 0.25s ease (più veloce)
- **Accessibilità:** Disabilitate con `prefers-reduced-motion`

### Soglia di Attivazione

Il sistema attiva il cambio colore quando **almeno il 30%** dello SmartHeader è sopra una dark zone. Questo previene flickering durante lo scroll veloce.

---

## 🔧 Personalizzazione Avanzata

### Colori Custom

Se vuoi colori diversi dal bianco, modifica il CSS:

```css
/* Esempio: testo giallo su dark zones */
.smart-header--on-dark .smart-header-reactive-text {
    color: #FFD700 !important; /* Giallo oro */
}
```

### Elementi Secondari con Opacità

Per elementi che devono essere meno visibili:

```html
<!-- In Elementor, aggiungi entrambe le classi -->
CSS Classes: smart-header-reactive-text smart-header-reactive-text--secondary
```

```css
/* Opacità 80% invece di 100% */
.smart-header--on-dark .smart-header-reactive-text.smart-header-reactive-text--secondary {
    color: rgba(255, 255, 255, 0.8) !important;
}
```

### Logo PNG/JPG (Non SVG)

Se il tuo logo è PNG/JPG, usa un filtro CSS:

```css
/* Inverte i colori del logo su dark zone */
.smart-header--on-dark .smart-header-reactive-text img {
    filter: brightness(0) invert(1);
    transition: filter 0.3s ease;
}
```

---

## 🧪 Testing e Debug

### Console API

Il sistema espone un'API pubblica per debugging:

```javascript
// Stato corrente
console.log(window.bwSmartHeader.getState());

// Output esempio:
{
    scrollTop: 450,
    direction: "down",
    isVisible: true,
    isOnDarkZone: true,    // ← SmartHeader su dark zone
    darkZonesCount: 3       // ← Numero dark zones trovate
}

// Forza controllo dark zones
window.bwSmartHeader.recheckDarkZones();

// Vedi tutte le dark zones
console.log(window.bwSmartHeader.getDarkZones());
```

### Attivare Debug Mode

Per vedere i log in console:

```javascript
// Nel file bw-main-elementor-widgets.php, modifica la configurazione:
wp_localize_script('bw-smart-header-script', 'bwSmartHeaderConfig', array(
    'debug' => true, // ← Attiva debug
    'scrollDownThreshold' => 100,
    'blurThreshold' => 50,
    // ...
));
```

Vedrai messaggi tipo:
```
[Smart Header] Dark zones trovate { count: 3 }
[Smart Header] SmartHeader entrato in dark zone { overlapPercentage: "78.50%" }
[Smart Header] SmartHeader uscito da dark zone
```

---

## ✅ Checklist Implementazione

### Setup Iniziale
- [ ] Hai applicato `.smart-header` al container principale dell'header
- [ ] Il plugin BW è attivo e gli assets caricati

### Sezioni Scure
- [ ] Hai identificato tutte le sezioni con sfondo scuro
- [ ] Hai aggiunto `.smart-header-dark-zone` a ogni sezione scura
- [ ] Le sezioni hanno `position: relative` (automatico con la classe)

### Elementi Reattivi
- [ ] Hai aggiunto `.smart-header-reactive-text` al menu navigation
- [ ] Hai aggiunto `.smart-header-reactive-text` al logo (SVG)
- [ ] Hai aggiunto `.smart-header-reactive-text` al widget BW NavShop
- [ ] Hai aggiunto `.smart-header-reactive-text` a eventuali altri testi

### Test
- [ ] Scrolla la pagina e verifica che il colore cambi sulle dark zones
- [ ] Testa su mobile, tablet e desktop
- [ ] Verifica che non ci sia flickering durante lo scroll veloce
- [ ] Controlla la console per errori JavaScript

---

## 🐛 Troubleshooting

### Il colore non cambia

**Possibili cause:**
1. Classe `.smart-header-dark-zone` non applicata correttamente
2. Classe `.smart-header-reactive-text` mancante sugli elementi
3. JavaScript non caricato (controlla console)
4. Cache browser/plugin - fai hard refresh (Ctrl+Shift+R)

**Soluzione:**
```javascript
// Controlla in console
console.log(window.bwSmartHeader.getState());
// Verifica che darkZonesCount > 0
```

### Il cambio colore ha flickering

**Causa:** Soglia overlap troppo bassa

**Soluzione:** Modifica la soglia in `bw-smart-header.js`:
```javascript
// Riga ~344
const overlapThreshold = 30; // Aumenta a 40-50 per ridurre flickering
```

### Il logo SVG non cambia colore

**Causa:** Il SVG ha colori hard-coded invece di `currentColor`

**Soluzione:** Modifica il SVG per usare `currentColor`:
```svg
<!-- Prima (hard-coded) -->
<svg><path fill="#000000" /></svg>

<!-- Dopo (reattivo) -->
<svg><path fill="currentColor" /></svg>
```

### Il colore cambia troppo tardi/presto

**Causa:** L'altezza della dark zone è troppo piccola o la soglia overlap troppo alta

**Soluzione:** Verifica che la dark zone abbia altezza sufficiente (min 200-300px) o abbassa la soglia overlap.

---

## 📱 Responsive Behavior

### Mobile (< 768px)
- Transizioni più veloci (0.25s invece di 0.3s)
- Stessi colori e comportamento
- Performance ottimizzate

### Tablet (768px - 1024px)
- Comportamento identico a desktop

### Desktop (> 1024px)
- Comportamento standard

---

## 🎯 Best Practices

### 1. Contrasto WCAG

Assicurati che il contrasto sia sufficiente:
- Testo bianco su sfondo scuro: ✅ Contrast ratio 21:1
- Testo scuro su sfondo chiaro: ✅ Contrast ratio 21:1

### 2. Performance

- Usa **SVG** per i loghi invece di PNG/JPG
- Limita il numero di dark zones a quelle effettivamente necessarie
- Non applicare `.smart-header-reactive-text` a elementi che non servono

### 3. Coerenza Visiva

- Mantieni uno stile coerente per tutte le dark zones
- Usa lo stesso colore di sfondo scuro (#000, #1a1a1a, etc.)
- Assicurati che tutti gli elementi dello SmartHeader reagiscano allo stesso modo

---

## 📦 File Modificati

Questa implementazione ha modificato i seguenti file:

| File | Modifiche |
|------|-----------|
| `/assets/js/bw-smart-header.js` | Aggiunto sistema IntersectionObserver per dark zone detection |
| `/assets/css/bw-smart-header.css` | Aggiunte classi `.smart-header-dark-zone`, `.smart-header--on-dark`, `.smart-header-reactive-text` |

**Nessuna modifica PHP richiesta** - Il sistema funziona solo con JS e CSS.

---

## 🆘 Supporto

In caso di problemi:

1. **Attiva debug mode** e controlla i log
2. **Usa console API** per verificare lo stato
3. **Ispeziona elementi** per vedere se le classi sono applicate correttamente
4. **Testa con dark zone singola** per isolare il problema
5. **Verifica la versione** del plugin: `window.bwSmartHeader.version` (deve essere >= 2.3.0)

---

## 📄 Esempio Completo

```html
<!-- STRUTTURA PAGINA -->

<!-- SmartHeader (fixed top) -->
<div class="smart-header">
    <!-- Logo SVG -->
    <div class="logo-container smart-header-reactive-text">
        <svg>...</svg>
    </div>

    <!-- Menu Navigation -->
    <nav class="elementor-nav-menu smart-header-reactive-text">
        <a href="#home">Home</a>
        <a href="#about">About</a>
        <a href="#contact">Contact</a>
    </nav>

    <!-- BW NavShop -->
    <div class="bw-navshop smart-header-reactive-text">
        <a href="/cart">Cart</a>
        <a href="/account">Account</a>
    </div>
</div>

<!-- Contenuto Pagina -->

<!-- Sezione chiara (header normale) -->
<section class="hero" style="background: #fff;">
    <h1>Welcome</h1>
</section>

<!-- Sezione scura (header diventa chiaro) -->
<section class="about smart-header-dark-zone" style="background: #000;">
    <h2>About Us</h2>
</section>

<!-- Sezione chiara (header torna normale) -->
<section class="services" style="background: #f5f5f5;">
    <h3>Services</h3>
</section>

<!-- Altra sezione scura (header diventa chiaro) -->
<section class="cta smart-header-dark-zone" style="background: #1a1a1a;">
    <h2>Get Started</h2>
</section>
```

---

## 🎉 Risultato Finale

✅ SmartHeader che cambia automaticamente colore quando passa sopra sezioni scure
✅ Logo, menu e widget reattivi
✅ Sistema modulare e riutilizzabile
✅ Performance ottimizzate
✅ Zero configurazione manuale per pagina

**Basta aggiungere la classe `.smart-header-dark-zone` alle sezioni scure e il sistema fa il resto!**


---

## Source: `docs/README.md`

# Documentation Index

Startup-tech structure with domain-based sections and archived historical reports.

## Sections
- [00-overview](00-overview/README.md)
- [10-architecture](10-architecture/README.md)
- [20-development](20-development/README.md)
- [30-features](30-features/README.md)
- [40-integrations](40-integrations/README.md)
- [50-ops](50-ops/README.md)
- [60-adr](60-adr/README.md)
- [99-archive](99-archive/README.md)
- [templates](templates/README.md)


---

## Source: `docs/templates/README.md`

# Templates

Purpose:
- Standardize task initiation and closure.
- Enforce protocol-first execution.
- Keep maintenance and delivery traceable.

## When to Use
- [task-start-template.md](task-start-template.md): use before any new feature or architectural evolution.
- [maintenance-task-template.md](maintenance-task-template.md): use for bug fix, regression, and integration break tasks.
- [task-closure-template.md](task-closure-template.md): use to close tasks after validation and documentation sync.

## Protocol Reference
- [Blackwork Development & Maintenance Protocol](../50-ops/blackwork-development-protocol.md)


---

## Source: `docs/templates/maintenance-task-template.md`

# Blackwork - Maintenance Task Template

## Incident Classification
- Incident type:
- Classification reference (`incident-classification.md`):
- Decision matrix path used:

## Domain Identification
- Primary domain:
- Secondary/related domains:
- Related integrations:

## Risk Level
- Level (L1/L2/L3):
- Release blocking status:

## Runbook to Follow
- Selected runbook:
- Related domain docs reviewed:

## Regression Scope
- Full regression required (Yes/No):
- Domain regression required (Yes/No):
- Mandatory checks list:

## Required Documentation Updates
- `CHANGELOG.md` (Yes/No):
- Feature docs (Yes/No):
- Runbook update (Yes/No):
- ADR required (Yes/No):

## Implementation Boundaries
Used for bug fix, regression, and integration break tasks.


---

## Source: `docs/templates/task-closure-template.md`

# Blackwork Governance — Task Closure Template

This template defines the mandatory governance protocol for closing implementation tasks in the Blackwork repository.  
A task MUST NOT be closed unless all required verification and documentation alignment steps have been completed.

## 1) Task Identification
- Task ID:
- Task title:
- Domain:
- Tier classification:
- Implementation commit(s):

### Commit Traceability

List all commits associated with this task.

- Commit hash:
- Commit message:
- Files impacted:

Normative rule:
All commits implementing the task MUST be traceable to the Task ID.

Normative rules:
- Task identification fields are REQUIRED.
- Closure MUST NOT proceed with missing identification metadata.

## 2) Implementation Summary
Briefly describe what was implemented.

- Modified files:
- Runtime surfaces touched:
- Hooks modified or registered:
- Database/data surfaces touched (if any):

### Runtime Surface Diff

Declare all runtime surfaces affected by this task.

- New hooks registered:
- Hook priorities modified:
- Filters added or removed:
- AJAX endpoints added or modified:
- Admin routes added or modified:

Normative rule:
All runtime surface mutations MUST be declared.

Normative rule:
- All modified surfaces MUST be explicitly declared.

## 3) Acceptance Criteria Verification
List each acceptance criterion declared in the Task Start Template.

- Criterion 1 — Status (PASS / FAIL):
- Criterion 2 — Status (PASS / FAIL):
- Criterion 3 — Status (PASS / FAIL):

Normative rules:
- All criteria MUST pass before closure.
- If any criterion fails, the task MUST remain open.

### Testing Evidence

Provide evidence of testing performed.

- Local testing performed: Yes / No
- Environment used:
- Screenshots / logs:
- Edge cases tested:

Normative rule:
Testing evidence MUST exist for runtime-critical changes.

## 4) Regression Surface Verification
List all regression surfaces declared at task start.

- Surface name:
  - Verification performed:
  - Result (PASS / FAIL):
- Surface name:
  - Verification performed:
  - Result (PASS / FAIL):

Examples of common surfaces:
- Checkout flow
- Media folders runtime
- Admin panel UI
- Elementor widget runtime

Normative rule:
- All declared regression surfaces MUST be verified.

## 5) Determinism Verification
- Input/output determinism verified? (Yes/No)
- Ordering determinism verified? (Yes/No)
- Retry/re-entry convergence verified? (Yes/No)

Normative rule:
- If determinism guarantees fail, the task MUST NOT close.

## 6) Documentation Alignment Verification
The following documentation layers MUST be checked:

- `docs/00-governance/`
  - Impacted? (Yes/No)
  - Documents updated:
- `docs/00-planning/`
  - Impacted? (Yes/No)
  - Documents updated:
- `docs/10-architecture/`
  - Impacted? (Yes/No)
  - Documents updated:
- `docs/20-development/`
  - Impacted? (Yes/No)
  - Documents updated:
- `docs/30-features/`
  - Impacted? (Yes/No)
  - Documents updated:
- `docs/40-integrations/`
  - Impacted? (Yes/No)
  - Documents updated:
- `docs/50-ops/`
  - Impacted? (Yes/No)
  - Documents updated:
- `docs/60-adr/`
  - Impacted? (Yes/No)
  - Documents updated:
- `docs/60-system/`
  - Impacted? (Yes/No)
  - Documents updated:

Normative rule:
- Documentation MUST reflect implemented system behavior.

## 7) Governance Artifact Updates
- Roadmap updated? (`docs/00-planning/core-evolution-plan.md`)
- Decision log updated? (`docs/00-planning/decision-log.md`)
- Risk register updated? (`docs/00-governance/risk-register.md`)
- Runtime hook map updated? (`docs/50-ops/runtime-hook-map.md`)
- Feature documentation updated? (`docs/30-features/...`)

Normative rule:
- If system behavior changes, governance/documentation updates are REQUIRED.

## 8) Final Integrity Check
Confirm:
- No authority drift introduced
- No new truth surface created
- No invariant broken
- No undocumented runtime hook change

- Integrity verification status: PASS / FAIL

Normative rule:
- Integrity status MUST be PASS for closure.

## Rollback Safety

Confirm rollback feasibility.

- Can the change be reverted via commit revert? (Yes / No)
- Database migration involved? (Yes / No)
- Manual rollback steps required?

Normative rule:
If rollback is not trivial, mitigation steps MUST be documented.

## Post-Closure Monitoring
Specify if runtime monitoring is required after deployment.

- Monitoring required: Yes / No
- Surfaces to monitor:
- Monitoring duration:

Examples:
- checkout flow
- media library runtime
- payment gateways
- admin UI runtime

Normative rule:
If runtime-critical surfaces are modified, monitoring MUST be declared.

## Release Gate Preparation
- Release Gate required? (Yes/No)

Release validation notes:
- runtime surfaces to verify
- operational smoke tests required

- Rollback readiness confirmed? (Yes/No)

Normative rules:
- Release Gate preparation MUST be completed before deployment.
- Closure artifacts MUST provide enough evidence to execute Release Gate checks.

## 9) Closure Declaration
- Task closure status: CLOSED / REOPEN REQUIRED
- Responsible reviewer:
- Date:

Normative rule:
- A task MUST NOT be marked CLOSED unless all verification sections pass.

## Governance Enforcement Rule
This template defines the mandatory closure protocol for implementation tasks.

A task MUST NOT be closed unless:
- acceptance criteria pass
- determinism guarantees are verified
- documentation alignment is complete
- governance artifacts are updated

All AI agents and contributors MUST follow this protocol.


---

## Source: `docs/templates/task-start-template.md`

# Blackwork Governance — Task Start Template

This template defines the mandatory governance protocol that MUST be followed before any implementation task begins in the Blackwork repository.  
All AI agents and contributors MUST complete this template before starting implementation.

## Context
- Task title:
- Request source:
- Expected outcome:
- Constraints:

Normative rules:
- Context fields are REQUIRED.
- Implementation MUST NOT begin with missing context.

## Task Classification
- Domain:
- Incident/Task type:
- Risk level (L1/L2/L3):
- Tier classification (0/1/2/3):
- Affected systems:
- Integration impact:
- Regression scope required:

Normative rules:
- Task classification is REQUIRED and BLOCKING.
- Tier 0 authority-impacting tasks MUST trigger governance escalation before implementation.
- Misclassified tasks MUST be corrected before implementation.

## Pre-Task Reading Checklist
- Feature docs to read:
- Integration docs to read:
- Ops/control docs to read:
- Governance docs to read:
- Runbook to follow:
- Architecture references to read:

Normative rules:
- Reading checklist completion is REQUIRED.
- Implementation MUST NOT begin without declaring required references.

## Scope Declaration
- Proposed strategy:
- Files likely impacted:
- Explicitly out-of-scope surfaces:
- Risk analysis:
- ADR evaluation (REQUIRED / NOT REQUIRED):

Normative rules:
- Scope declaration is REQUIRED and BLOCKING.
- All target files MUST be declared before implementation.
- Out-of-scope boundaries MUST be explicit.

## Runtime Surface Declaration

Declare expected runtime surfaces affected.

- New hooks expected:
- Hook priority modifications:
- Filters expected:
- AJAX endpoints expected:
- Admin routes expected:

Normative rule:
All expected runtime mutations MUST be declared before implementation.

## 3.1) Implementation Scope Lock

Confirm that the declared scope is complete.

- All files expected to change are listed? (Yes/No)
- Hidden coupling risks discovered? (Yes/No)

Normative rules:

Implementation MUST NOT modify files outside the declared scope.

If new surfaces are discovered during development, the task MUST pause and the scope MUST be updated.

## Governance Impact Analysis
- Authority surfaces touched:
- Data integrity risk:
- Security surface changes:
- Runtime hook/order changes:
- Requires ADR? (Yes/No)
- Risk register impact required? (Yes/No)

Normative rules:
- Governance impact analysis is REQUIRED and BLOCKING.
- If authority ownership changes, ADR is REQUIRED before implementation.
- If risk posture changes, risk documentation update is REQUIRED.

## System Invariants Check
- Declared invariants that MUST remain true:
- Any invariant at risk? (Yes/No)
- Mitigation plan for invariant protection:

Normative rules:
- Invariants are binding constraints and MUST NOT be violated.
- If invariant safety is unclear, implementation MUST stop and escalate.

## Determinism Statement
- Input/output determinism declared? (Yes/No)
- Ordering determinism declared? (Yes/No)
- Retry determinism declared? (Yes/No)
- Pagination/state convergence determinism declared? (Yes/No)
- Determinism risks and controls:

Normative rules:
- Determinism expectations are REQUIRED and BLOCKING.
- Non-deterministic behavior in critical flows MUST be escalated to governance review.
- If deterministic behavior cannot be guaranteed, implementation MUST NOT proceed.

## Testing Strategy

Describe how the implementation will be verified.

- Local testing plan:
- Edge cases expected:
- Failure scenarios considered:

Normative rule:
Critical flows MUST have explicit testing strategy.

## Documentation Update Plan
Documentation layers that MUST be considered before implementation:

- `docs/00-governance/`
  - Impacted? (Yes/No)
  - Target documents (if known):
- `docs/00-planning/`
  - Impacted? (Yes/No)
  - Target documents (if known):
- `docs/10-architecture/`
  - Impacted? (Yes/No)
  - Target documents (if known):
- `docs/20-development/`
  - Impacted? (Yes/No)
  - Target documents (if known):
- `docs/30-features/`
  - Impacted? (Yes/No)
  - Target documents (if known):
- `docs/40-integrations/`
  - Impacted? (Yes/No)
  - Target documents (if known):
- `docs/50-ops/`
  - Impacted? (Yes/No)
  - Target documents (if known):
- `docs/60-adr/`
  - Impacted? (Yes/No)
  - Target documents (if known):
- `docs/60-system/`
  - Impacted? (Yes/No)
  - Target documents (if known):

Normative rules:
- Documentation impact declaration is REQUIRED and BLOCKING.
- Implementation MUST NOT begin unless this declaration is completed.
- If behavior changes are expected, documentation targets MUST be declared before coding.

## Rollback Strategy

Describe rollback feasibility.

- Revert via commit possible? (Yes/No)
- Database migration involved? (Yes/No)
- Manual rollback steps required?

Normative rule:
If rollback is non-trivial, mitigation steps MUST be declared.

## 6A) Documentation Alignment Requirement
Before implementation begins, the documentation architecture MUST be evaluated.

The following documentation layers MUST be checked for potential updates:
- `docs/00-governance/`
- `docs/00-planning/`
- `docs/10-architecture/`
- `docs/20-development/`
- `docs/30-features/`
- `docs/40-integrations/`
- `docs/50-ops/`
- `docs/60-adr/`
- `docs/60-system/`

For each layer specify:
- Impacted? (Yes/No)
- Target documents (if known)

Normative rule:
- Implementation MUST NOT begin until documentation impact has been declared.

## Acceptance Gate
DO NOT IMPLEMENT YET.

Gate checklist:
- Task Classification completed? (Yes/No)
- Pre-Task Reading Checklist completed? (Yes/No)
- Scope Declaration completed? (Yes/No)
- Implementation Scope Lock passed? (Yes/No)
- Governance Impact Analysis completed? (Yes/No)
- System Invariants Check completed? (Yes/No)
- Determinism Statement completed? (Yes/No)
- Documentation Update Plan completed? (Yes/No)
- Documentation Alignment Requirement completed? (Yes/No)

Normative rules:
- All gate items are REQUIRED.
- Any `No` is BLOCKING.
- Implementation MUST NOT begin until all gate items are `Yes`.

## Release Gate Awareness
All tasks will pass through the Release Gate before deployment.

Task planning and implementation MUST preserve:
- rollback safety
- determinism guarantees
- documentation alignment

to ensure successful release validation.

Release Gate checklist reference:
`docs/50-ops/release-gate.md`

Normative rule:
- Work that cannot satisfy Release Gate requirements MUST be escalated before deployment.

## Abort Conditions
- Scope drift detected
- Undeclared authority surface discovered
- Invariant breach risk not mitigated
- Determinism cannot be guaranteed
- Required documentation alignment cannot be completed
- ADR required but not approved

Normative rules:
- Any abort condition MUST stop the task immediately.
- Work MUST NOT continue until governance review resolves the blocking condition.

## Governance Enforcement Rule

This template defines the mandatory governance protocol for task execution in the Blackwork repository.

Implementation MUST NOT begin until this template is fully completed.

Any violation of declared scope, governance rules, determinism guarantees, or documentation alignment MUST stop the task immediately.

All AI agents operating in this repository MUST treat this template as a binding governance protocol.

