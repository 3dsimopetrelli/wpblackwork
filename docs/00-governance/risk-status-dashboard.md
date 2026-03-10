# Blackwork Risk Status Dashboard

## Current High-Priority Focus
- Non-Supabase architecture cleanup in progress.
- Supabase surfaces are under governance freeze unless explicitly required.
- WooCommerce template override drift remediation completed.
- Current work focuses on risk closure, stability hardening, and reducing maintenance surface.

## New Chat Orientation
When starting a new engineering chat or session, this file can be provided to quickly understand the current state of the project.

It gives a one-glance overview of:
- all active and resolved risks
- current priorities
- areas already stabilized
- areas still under monitoring

This dashboard complements the full risk register but is optimized for fast orientation.

## Quick risk snapshot
| Risk | Area | Status | Priority | Last Update |
|---|---|---|---|---|
| R-WOO-24 | WooCommerce templates | <span style="background:#2ecc71;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">RESOLVED</span> | High | 2026-03-09 |
| R-SEC-22 | SVG pipeline | <span style="background:#2ecc71;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">RESOLVED</span> | High | 2026-03-10 |
| R-ADM-21 | Admin settings integrity | <span style="background:#2ecc71;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">RESOLVED</span> | High | 2026-03-10 |
| R-ADM-19 | Admin asset scoping | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Medium | 2026-03-09 |
| R-PAY-08 | Payment webhook architecture | <span style="background:#f39c12;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">PARTIAL</span> | Critical | 2026-03-09 |
| R-MF-01 | Media folders counts pipeline | <span style="background:#95a5a6;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">WATCHLIST</span> | Medium-High | 2026-03-09 |
| R-IMP-10 | Import / Catalog Data Integrity | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Critical | 2026-03-10 |

## Dashboard purpose
The Risk Status Dashboard provides a single-glance overview of all governance risks and their current state.  
It complements the full risk register but is optimized for quick orientation when starting new work sessions or opening new engineering chats.

## 1. Executive snapshot
- Total risks: 54
- Resolved: 7
- Mitigated: 18
- Partial Mitigation Complete: 1
- Open: 27
- Watchlist / Deferred: 1

Last governance-aligned updates:
- 2026-03-10: `R-BRE-09` closure verification completed (`xkeysib-...` finding confirmed stale; canonical Brevo config centralized).
- 2026-03-10: `R-FPW-20` patch 2 closed (authenticated product search capability hardening).
- 2026-03-10: `R-IMP-10` mitigated (patch 1 + patch 2 complete).
- 2026-03-10: `R-BRE-09` resolved (patch 1 + patch 2 complete).
- 2026-03-10: `R-BRE-09` patch 1 closed (legacy Coming Soon public Brevo handler removed).
- 2026-03-10: `R-ADM-21` resolved (patch A + patch B).
- 2026-03-09: `R-WOO-24` resolved (patch 1..5 complete).
- 2026-03-09: `R-ADM-19` mitigated (patch A/B complete, patch C deferred).
- 2026-03-09: `R-PAY-08` partial mitigation complete (patch 1 closed, Google Pay convergence deferred).

## 2. Status Legend
| Status | Meaning |
|---|---|
| <span style="background:#2ecc71;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">RESOLVED</span> | Risk fully mitigated |
| <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Major risk reduced but monitoring continues |
| <span style="background:#f39c12;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">PARTIAL</span> | Partial mitigation completed |
| <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Active risk requiring remediation |
| <span style="background:#95a5a6;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">WATCHLIST</span> | Potential future risk |
| <span style="background:#7f8c8d;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">DEFERRED</span> | Known risk intentionally postponed |

## 3. Risk summary table

| Risk ID | Title | Area | Priority | Current Status | Last Action | Remaining Work | Notes |
|---|---|---|---|---|---|---|---|
| R-AUTH-04 | Supabase auth coupling risk | Auth / Supabase / My Account | Critical | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Guardrails documented | Full hardening wave pending | Supabase-adjacent |
| R-IMP-10 | Import data integrity risk | Import / Catalog | Critical | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Patch 1 + patch 2 completed | Optional chunk read efficiency + media idempotency | Non-Supabase |
| R-RED-12 | Redirect authority drift | Redirect / Routing | Critical | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Redirect sanitizer path active | Cross-path normalization hardening | Non-Supabase |
| R-PAY-08 | Webhook architecture drift | Payments / Webhooks | Critical | <span style="background:#f39c12;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">PARTIAL</span> | Patch 1 POST-only enforcement closed | Google Pay runtime convergence task | Non-Supabase |
| R-SEC-22 | SVG sanitization hardening follow-up | Security / SVG | Critical | <span style="background:#2ecc71;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">RESOLVED</span> | Secondary SVG hardening completed | None | Non-Supabase |
| R-CHK-01 | Payment selector race/convergence | Checkout / Payments | High | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Selector authority model shipped | Ongoing monitoring | Non-Supabase |
| R-CHK-02 | Strict bootstrap runtime break | Checkout / Runtime | High | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | `arguments.callee` removal completed | Monitoring only | Non-Supabase |
| R-PAY-02 | Checkout payment state integrity | Payments / Checkout | High | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Baseline controls in place | Additional hardening waves | Non-Supabase |
| R-PAY-03 | Wallet availability/state drift | Payments / Wallets | High | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Compatibility notes + constraints | Wallet determinism hardening | Non-Supabase |
| R-AUTH-05 | Supabase auth flow stabilization | Auth / Supabase | High | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Mitigation wave completed | Monitoring | Supabase-adjacent |
| R-AUTH-25 | Public auth endpoint exposure | Auth / Supabase | High | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Exposure controls tightened | Rate-limit/abuse posture review | Supabase-adjacent |
| R-SUPA-06 | Supabase orders/account coupling | Supabase / Orders / Account | High | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Mitigation controls documented | Monitoring | Supabase-adjacent |
| R-BRE-09 | Brevo checkout sync drift | Brevo / Checkout | High | <span style="background:#2ecc71;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">RESOLVED</span> | Patch 1 + patch 2 closed; stale `xkeysib-...` finding verified | None | Closed state; non-Supabase |
| R-SRCH-11 | Search runtime coupling risk | Search / Header | High | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Existing safeguards retained | Performance/isolation follow-up | Non-Supabase |
| R-HDR-13 | Header orchestration complexity | Header / UX | High | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Existing guardrails documented | Additional isolation hardening | Non-Supabase |
| R-FPW-20 | Public AJAX filtered wall risk | Filtered Post Wall | High | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Patch 1 + patch 2 closed | Patch 3 abuse/rate hardening review | Non-Supabase |
| R-ADM-21 | Admin settings input integrity | Admin / Settings | High | <span style="background:#2ecc71;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">RESOLVED</span> | Patch A + B closed (2026-03-10) | None | Includes non-Supabase surfaces only |
| R-PERF-26 | Supabase sync runtime latency | Performance / Supabase | High | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Transient guard added | Monitoring | Supabase-adjacent |
| R-WOO-24 | Woo template override stale risk | WooCommerce Templates | High | <span style="background:#2ecc71;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">RESOLVED</span> | Patch sequence 1..5 complete | None | No Supabase blast radius in patches |
| R-TBL-04 | Elementor fonts compatibility drift | Theme Builder Lite | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Runtime soft-deps + monitoring | Compatibility follow-up | Non-Supabase |
| R-TBL-05 | Template resolver precedence conflicts | Theme Builder Lite | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Fail-open resolver guards | Conflict diagnostics backlog | Non-Supabase |
| R-TBL-06 | v1/v2 config dual-surface ambiguity | Theme Builder Lite | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | v2 authority documented | Legacy cleanup task | Non-Supabase |
| R-TBL-07 | Rule determinism misunderstanding | Theme Builder Lite | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Rule-order contract documented | UX clarity/backlog | Non-Supabase |
| R-TBL-08 | Inline type mutation integrity | Theme Builder Lite | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Validation + confirmation guard | Monitoring | Non-Supabase |
| R-TBL-09 | Admin scaling/usability drift | Theme Builder Lite | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Checklist simplification | Scaling UX backlog | Non-Supabase |
| R-TBL-10 | Product archive rule precedence drift | Theme Builder Lite | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Deterministic contract documented | Monitoring | Non-Supabase |
| R-TBL-11 | Archive rule UX scaling risk | Theme Builder Lite | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Parent-only checklist | Scaling UX backlog | Non-Supabase |
| R-TBL-12 | Template type linkage drift | Theme Builder Lite | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Sanitizer + runtime revalidation | Monitoring | Non-Supabase |
| R-TBL-13 | Resolver branching regressions | Theme Builder Lite | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Scoped branch + fail-open | Monitoring | Non-Supabase |
| R-TBL-14 | Import template payload risk | Theme Builder Lite | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Validation and rollback guards | Schema-drift monitoring | Non-Supabase |
| R-TBL-15 | Elementor import schema drift | Theme Builder Lite | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Draft-first import policy | Compatibility review cycle | Non-Supabase |
| R-TBL-16 | Import fallback type ambiguity | Theme Builder Lite | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Fallback warnings/markers | Operator guidance + cleanup | Non-Supabase |
| R-TBL-18 | Rule authority dual-surface drift | Theme Builder Lite | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | v2 authority bounded fallback | Explicit deprecation/migration | Non-Supabase |
| R-CHK-03 | Payment method input trust | Checkout / Payments | Medium | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Gateway allowlist validation shipped | Monitoring | Non-Supabase |
| R-CHK-04 | Checkout DOM sink safety | Checkout / Runtime | Medium | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Text-only normalization shipped | Monitoring | Non-Supabase |
| R-CHK-05 | Coupon AJAX timeout resilience | Checkout / Coupon | Medium | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Timeout + recovery shipped | Monitoring | Non-Supabase |
| R-CHK-06 | Coupon UX convergence | Checkout / Coupon UX | Medium | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Redirect removal and in-place refresh | Monitoring | Non-Supabase |
| R-MF-01 | Media Folders counts integrity/perf | Media Folders | Medium-High | <span style="background:#95a5a6;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">WATCHLIST</span> | Audit closed as watchlist | Planned mitigation wave | Non-Supabase |
| R-MF-02 | Folder assignment data integrity | Media Folders | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Baseline controls present | Integrity hardening backlog | Non-Supabase |
| R-MF-03 | Media admin runtime drift | Media Folders | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Existing runtime guards | Monitoring | Non-Supabase |
| R-AUTH-12 | Auth frontend runtime scope | Auth / Supabase | Medium | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Scope reduction completed | Monitoring | Supabase-adjacent |
| R-AUTH-13 | Auth preload scope | Auth / Supabase | Medium | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Preload hardening completed | Monitoring | Supabase-adjacent |
| R-ACC-07 | My Account auth integration drift | My Account / Supabase | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Existing safeguards retained | Additional convergence hardening | Supabase-adjacent |
| R-GOV-14 | Governance operational continuity | Governance / Tooling | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Governance docs updated | Ongoing process hardening | Cross-domain |
| R-TBL-17 | Elementor preview context drift | Theme Builder Lite | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Preview-context safeguards | Monitoring | Non-Supabase |
| R-ADM-18 | Admin diagnostics integrity risk | Admin / System Status | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Diagnostics framework documented | Hardening backlog | Non-Supabase |
| R-ADM-19 | Admin asset scope/perf risk | Admin / Enqueue Scope | Medium | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Patch A/B closed; C deferred | Optional patch C only | Non-Supabase |
| R-FE-23 | Slick dependency longevity risk | Frontend / Dependencies | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Pinned version strategy | Migration program (future) | Non-Supabase |
| R-PERF-27 | Order-received checkout scope | Performance / Checkout | Medium | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Guard excludes order-received runtime | Monitoring | Non-Supabase |
| R-TBL-01 | bw_template preview 404 risk | Theme Builder Lite | Medium | <span style="background:#2ecc71;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">RESOLVED</span> | Closed with previewable CPT path | None | Historical resolved risk |
| R-TBL-02 | Admin asset/editor interference | Theme Builder Lite | Medium | <span style="background:#2ecc71;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">RESOLVED</span> | Closed with strict enqueue scoping | None | Historical resolved risk |
| R-TBL-03 | Custom fonts Elementor visibility | Theme Builder Lite | Medium | <span style="background:#2ecc71;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">RESOLVED</span> | Closed with Elementor font integration | None | Historical resolved risk |
| R-PERF-28 | Stripe enqueue duplication | Performance / Payments | Low | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Canonical Stripe enqueue path shipped | Monitoring | Non-Supabase |
| R-PERF-29 | Cart popup dynamic CSS lookup cost | Performance / Cart Popup | Low | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Consolidated option retrieval pass | Monitoring | Non-Supabase |

## 4. Detailed risk notes

### R-TBL-01 — bw_template preview bootstrap 404
- Area: Theme Builder Lite
- Priority: Medium
- Status: Resolved
- Summary: Template preview path caused editor bootstrap failures.
- What has been completed: CPT previewability + rewrite handling + noindex guard implemented.
- What is still pending: None.
- Supabase-adjacent blast radius: No.
- Recommended next step: Keep in historical closure only.

### R-TBL-02 — Theme Builder admin/editor runtime isolation
- Area: Theme Builder Lite
- Priority: Medium
- Status: Resolved
- Summary: Admin assets could interfere with Elementor editor startup.
- What has been completed: Strict enqueue scoping + Elementor route bypass.
- What is still pending: None.
- Supabase-adjacent blast radius: No.
- Recommended next step: None.

### R-TBL-03 — Custom fonts injection consistency
- Area: Theme Builder Lite
- Priority: Medium
- Status: Resolved
- Summary: Custom fonts were not consistently visible in Elementor typography controls.
- What has been completed: Dedicated Elementor integration + shared CSS builder.
- What is still pending: None.
- Supabase-adjacent blast radius: No.
- Recommended next step: None.

### R-TBL-04 — Elementor compatibility drift
- Area: Theme Builder Lite
- Priority: Medium
- Status: Open
- Summary: Elementor internal filter/API drift can silently break font injection.
- What has been completed: Soft-deps and fail-open guards.
- What is still pending: Compatibility monitoring and future adapter updates.
- Supabase-adjacent blast radius: No.
- Recommended next step: Add versioned compatibility checks in CI smoke.

### R-TBL-05 — Template resolver precedence conflicts
- Area: Theme Builder Lite
- Priority: Medium
- Status: Open
- Summary: `template_include` conflicts can alter precedence unexpectedly.
- What has been completed: Bypass/fail-open guards.
- What is still pending: Conflict diagnostics and precedence observability.
- Supabase-adjacent blast radius: No.
- Recommended next step: Add resolver decision logging in debug mode.

### R-TBL-06 — Legacy vs v2 config ambiguity
- Area: Theme Builder Lite
- Priority: Medium
- Status: Open
- Summary: Coexisting rule stores can confuse operator expectations.
- What has been completed: v2 authority documented.
- What is still pending: Legacy deprecation/migration cleanup.
- Supabase-adjacent blast radius: No.
- Recommended next step: Plan one-way migration and retire v1 fallback.

### R-TBL-07 — Rule-order determinism misunderstanding
- Area: Theme Builder Lite
- Priority: Medium
- Status: Open
- Summary: Overlapping rules may be interpreted as specificity-based, not first-match.
- What has been completed: Contract clarified in docs/UI.
- What is still pending: UX affordances for overlap diagnostics.
- Supabase-adjacent blast radius: No.
- Recommended next step: Add rule simulation preview in admin.

### R-TBL-08 — Inline template type mutation risk
- Area: Theme Builder Lite
- Priority: Medium
- Status: Open
- Summary: Inline type changes can invalidate template linkages.
- What has been completed: Prompt + validation + safe reject path.
- What is still pending: Better post-mutation diagnostics.
- Supabase-adjacent blast radius: No.
- Recommended next step: Add explicit linkage impact warning panel.

### R-TBL-09 — Admin scaling/usability risk
- Area: Theme Builder Lite
- Priority: Medium
- Status: Open
- Summary: Large rule sets degrade operability and raise error risk.
- What has been completed: Parent-only checklist and status summaries.
- What is still pending: Search/reorder/perf UX improvements.
- Supabase-adjacent blast radius: No.
- Recommended next step: Add rule search and drag-order controls.

### R-TBL-10 — Product archive precedence drift
- Area: Theme Builder Lite
- Priority: Medium
- Status: Open
- Summary: Archive rule precedence can be misread and misconfigured.
- What has been completed: Deterministic contract documented.
- What is still pending: Monitoring and operator diagnostics.
- Supabase-adjacent blast radius: No.
- Recommended next step: Add archive rule match tracer.

### R-TBL-11 — Archive admin scaling risk
- Area: Theme Builder Lite
- Priority: Medium
- Status: Open
- Summary: Rule/term growth can create operational friction.
- What has been completed: Simplified term selection model.
- What is still pending: Scalability UX backlog.
- Supabase-adjacent blast radius: No.
- Recommended next step: Batch-edit and search support.

### R-TBL-12 — Template type linkage integrity
- Area: Theme Builder Lite
- Priority: Medium
- Status: Open
- Summary: Manual type drift can break valid rule linkage.
- What has been completed: Sanitizer drops invalid template IDs.
- What is still pending: Monitoring and admin guidance.
- Supabase-adjacent blast radius: No.
- Recommended next step: Add preventive guard before type changes.

### R-TBL-13 — Resolver branching regressions
- Area: Theme Builder Lite
- Priority: Medium
- Status: Open
- Summary: Branch regressions can alter Woo archive rendering precedence.
- What has been completed: Scoped branch and fail-open safeguards.
- What is still pending: Runtime regression monitoring.
- Supabase-adjacent blast radius: No.
- Recommended next step: Add archive resolver regression suite.

### R-TBL-14 — Import template payload integrity
- Area: Theme Builder Lite
- Priority: Medium
- Status: Open
- Summary: Malformed JSON can still stress import boundaries over time.
- What has been completed: Capability/nonce/type/size/structure validation + rollback.
- What is still pending: Ongoing schema drift compatibility checks.
- Supabase-adjacent blast radius: No.
- Recommended next step: Add schema-version compatibility checklist.

### R-TBL-15 — Elementor schema/version drift on imports
- Area: Theme Builder Lite
- Priority: Medium
- Status: Open
- Summary: Imported templates may degrade if Elementor internals change.
- What has been completed: Draft-first safe import posture.
- What is still pending: Periodic compatibility validation.
- Supabase-adjacent blast radius: No.
- Recommended next step: Periodic import/edit smoke test per Elementor update.

### R-TBL-16 — Import fallback semantic ambiguity
- Area: Theme Builder Lite
- Priority: Medium
- Status: Open
- Summary: Auto-detect fallback type can be misunderstood as semantic match.
- What has been completed: Explicit notices and imported markers.
- What is still pending: Stronger post-import review tooling.
- Supabase-adjacent blast radius: No.
- Recommended next step: Add explicit "requires manual type validation" flag.

### R-TBL-17 — Elementor preview context drift
- Area: Theme Builder Lite
- Priority: Medium
- Status: Open
- Summary: Preview context injection may diverge from runtime context.
- What has been completed: Preview safeguards and context logic.
- What is still pending: Ongoing validation.
- Supabase-adjacent blast radius: No.
- Recommended next step: Add preview-vs-runtime parity checks.

### R-TBL-18 — Rule authority dual-surface drift
- Area: Theme Builder Lite
- Priority: Medium
- Status: Open
- Summary: v1 and v2 rule surfaces increase audit/authority confusion.
- What has been completed: Bounded fallback and docs.
- What is still pending: Migration/deprecation.
- Supabase-adjacent blast radius: No.
- Recommended next step: Execute v1 retirement plan.

### R-CHK-01 — Checkout payment selector race
- Area: Checkout / Payments
- Priority: High
- Status: Mitigated
- Summary: Multi-listener races could desync selected payment state.
- What has been completed: Selector convergence authority model delivered.
- What is still pending: Monitoring under wallet edge conditions.
- Supabase-adjacent blast radius: No.
- Recommended next step: Keep targeted checkout regression smoke.

### R-CHK-02 — Strict mode bootstrap break
- Area: Checkout / Runtime
- Priority: High
- Status: Mitigated
- Summary: Strict-mode-incompatible bootstrap call could break checkout init.
- What has been completed: Retry bootstrap refactor completed.
- What is still pending: Monitoring.
- Supabase-adjacent blast radius: No.
- Recommended next step: None beyond regression checks.

### R-CHK-03 — Payment method input trust
- Area: Checkout / Payments
- Priority: Medium
- Status: Mitigated
- Summary: Posted method could influence runtime without strict validation.
- What has been completed: Scalar normalization + gateway validation fallback.
- What is still pending: Monitoring.
- Supabase-adjacent blast radius: No.
- Recommended next step: Keep server-side validation tests.

### R-CHK-04 — DOM safety in error normalization
- Area: Checkout / Runtime
- Priority: Medium
- Status: Mitigated
- Summary: HTML reinsertion sink existed in error normalization path.
- What has been completed: Text-only normalization path shipped.
- What is still pending: Monitoring.
- Supabase-adjacent blast radius: No.
- Recommended next step: Keep DOM sink audit in checkout JS reviews.

### R-CHK-05 — Coupon runtime timeout resilience
- Area: Checkout / Coupon
- Priority: Medium
- Status: Mitigated
- Summary: Stalled coupon requests could lock checkout UI.
- What has been completed: Explicit timeout + recovery behavior added.
- What is still pending: Monitoring.
- Supabase-adjacent blast radius: No.
- Recommended next step: Keep network-failure regression case.

### R-CHK-06 — Coupon update UX convergence
- Area: Checkout / Coupon UX
- Priority: Medium
- Status: Mitigated
- Summary: Redirect-on-remove could reset in-progress form state.
- What has been completed: In-place update flow implemented.
- What is still pending: Monitoring.
- Supabase-adjacent blast radius: No.
- Recommended next step: None beyond routine checkout smoke.

### R-PAY-02 — Payments checkout integrity
- Area: Payments / Checkout
- Priority: High
- Status: Open
- Summary: Payment path integrity risk remains active.
- What has been completed: Baseline controls.
- What is still pending: Additional deterministic hardening waves.
- Supabase-adjacent blast radius: No.
- Recommended next step: Prioritize after webhook convergence planning.

### R-PAY-03 — Wallet runtime state drift
- Area: Payments / Wallets
- Priority: High
- Status: Open
- Summary: Wallet availability and runtime state can diverge.
- What has been completed: Compatibility and ownership notes.
- What is still pending: Deterministic runtime convergence.
- Supabase-adjacent blast radius: No.
- Recommended next step: Define single wallet ownership contract tests.

### R-PAY-08 — Payments webhook architecture
- Area: Payments / Webhooks
- Priority: Critical
- Status: Partial Mitigation Complete
- Summary: Webhook hardening progressed, architecture convergence pending.
- What has been completed: POST-only method enforcement patch closed.
- What is still pending: Google Pay runtime path convergence (legacy vs abstract path).
- Supabase-adjacent blast radius: No.
- Recommended next step: Dedicated architecture convergence task.

### R-MF-01 — Media Folders counts pipeline
- Area: Media Folders
- Priority: Medium-High
- Status: Watchlist
- Summary: Counts pipeline has stale-window and fallback-cost risk.
- What has been completed: Audit completed, no runtime change applied.
- What is still pending: Planned mitigation wave (fallback logging, invalidation expansion, cache split).
- Supabase-adjacent blast radius: No.
- Recommended next step: Execute planned mitigation when capacity opens.

### R-MF-02 — Media folder assignment integrity
- Area: Media Folders
- Priority: Medium
- Status: Open
- Summary: Assignment integrity can drift under edge operations.
- What has been completed: Baseline protections.
- What is still pending: Additional integrity hardening.
- Supabase-adjacent blast radius: No.
- Recommended next step: Add bulk-operation integrity scenarios.

### R-MF-03 — Media admin runtime stability
- Area: Media Folders
- Priority: Medium
- Status: Open
- Summary: Media-library runtime behavior may drift under plugin/admin changes.
- What has been completed: Core runtime hooks in place.
- What is still pending: Monitoring/hardening follow-up.
- Supabase-adjacent blast radius: No.
- Recommended next step: Extend admin regression matrix.

### R-AUTH-04 — Supabase auth/my-account coupling
- Area: Auth / Supabase / My Account
- Priority: Critical
- Status: Open
- Summary: Core auth path remains a high-blast-radius surface.
- What has been completed: Existing controls and governance freeze discipline.
- What is still pending: Further hardening under controlled Supabase wave.
- Supabase-adjacent blast radius: Yes.
- Recommended next step: Defer until non-Supabase backlog is closed.

### R-AUTH-05 — Supabase auth stability
- Area: Auth / Supabase
- Priority: High
- Status: Mitigated
- Summary: Risk reduced with implemented controls.
- What has been completed: Mitigation path completed.
- What is still pending: Monitoring.
- Supabase-adjacent blast radius: Yes.
- Recommended next step: Keep strict regression protocol.

### R-AUTH-12 — Frontend runtime scope
- Area: Auth / Supabase
- Priority: Medium
- Status: Mitigated
- Summary: Frontend auth runtime scope risk reduced.
- What has been completed: Scope hardening completed.
- What is still pending: Monitoring.
- Supabase-adjacent blast radius: Yes.
- Recommended next step: Keep payload/asset scope audits.

### R-AUTH-13 — Frontend preload scope
- Area: Auth / Supabase
- Priority: Medium
- Status: Mitigated
- Summary: Preload scope reduced and stabilized.
- What has been completed: Mitigation completed.
- What is still pending: Monitoring.
- Supabase-adjacent blast radius: Yes.
- Recommended next step: Keep auth preload checks in release gate.

### R-AUTH-25 — Public endpoint exposure
- Area: Auth / Supabase
- Priority: High
- Status: Mitigated
- Summary: Enumeration/exposure risk reduced but still sensitive.
- What has been completed: Mitigation controls applied.
- What is still pending: Abuse posture/rate-limiting review.
- Supabase-adjacent blast radius: Yes.
- Recommended next step: Evaluate explicit server-side throttling hardening.

### R-SUPA-06 — Supabase orders/account integration
- Area: Supabase / Orders / My Account
- Priority: High
- Status: Mitigated
- Summary: Cross-domain coupling risk reduced with controls.
- What has been completed: Mitigation delivered.
- What is still pending: Monitoring.
- Supabase-adjacent blast radius: Yes.
- Recommended next step: Maintain protected-surface smoke binding.

### R-ACC-07 — My Account Supabase runtime
- Area: My Account / Supabase
- Priority: Medium
- Status: Open
- Summary: Account runtime remains sensitive to auth coupling changes.
- What has been completed: Existing safeguards.
- What is still pending: Controlled convergence hardening.
- Supabase-adjacent blast radius: Yes.
- Recommended next step: Keep deferred until final Supabase wave.

### R-BRE-09 — Brevo checkout integration risk
- Area: Brevo / Checkout
- Priority: High
- Status: Resolved
- Summary: High-priority Brevo checkout integrity gaps are closed.
- What has been completed: Integration controls/admin tooling, patch 1 legacy public handler removal, and patch 2 positive-state preservation (`subscribed|pending|1` no-op on duplicate passes).
- What is still pending: None.
- Closure note: CLOSED after repository-wide verification confirmed no hardcoded Brevo key (`xkeysib-...`) in current snapshot; previous finding is stale.
- Supabase-adjacent blast radius: No.
- Recommended next step: Keep in monitoring cadence and reopen only on new runtime evidence.

### R-IMP-10 — Import pipeline integrity
- Area: Import / Catalog
- Priority: Critical
- Status: Mitigated
- Summary: Import integrity risk reduced with checkpoint resume hardening and strict enum normalization allowlists.
- What has been completed: Locking/capability/nonce/state guards, patch 1 in-chunk progress checkpoints, patch 2 enum allowlist validation (`product_type`, `stock_status`, `backorders`, `tax_status`).
- What is still pending: Optional future hardening only (chunk read efficiency and media idempotency).
- Supabase-adjacent blast radius: No.
- Recommended next step: Keep monitoring; schedule optional efficiency/idempotency hardening only if runtime evidence justifies it.

### R-SRCH-11 — Search/header runtime coupling
- Area: Search / Header
- Priority: High
- Status: Open
- Summary: Search runtime coupling can propagate regressions to header UX.
- What has been completed: Current guards and docs.
- What is still pending: Isolation/perf follow-up.
- Supabase-adjacent blast radius: No.
- Recommended next step: Strengthen query bounds and fallback behavior tests.

### R-RED-12 — Redirect authority boundary
- Area: Redirect / Routing
- Priority: Critical
- Status: Open
- Summary: Redirect authority errors can create high-impact routing faults.
- What has been completed: Sanitized storage path implemented.
- What is still pending: End-to-end normalization and conflict controls.
- Supabase-adjacent blast radius: No.
- Recommended next step: Add strict source-path normalization contract tests.

### R-HDR-13 — Header global orchestration risk
- Area: Header / UX orchestration
- Priority: High
- Status: Open
- Summary: Header remains a high-coupling runtime with broad blast radius.
- What has been completed: Existing controls and docs.
- What is still pending: Additional hardening and regression coverage.
- Supabase-adjacent blast radius: No.
- Recommended next step: Increase cross-device/header state regression matrix.

### R-GOV-14 — Governance continuity risk
- Area: Governance / Tooling
- Priority: Medium
- Status: Open
- Summary: Process/tooling drift can reduce enforcement reliability.
- What has been completed: Governance framework and protocols updated.
- What is still pending: Continuous operational discipline.
- Supabase-adjacent blast radius: Cross-domain.
- Recommended next step: Keep governance dashboard and cadence reviews current.

### R-ADM-18 — Admin diagnostics integrity
- Area: Admin / System Status
- Priority: Medium
- Status: Open
- Summary: Diagnostics surfaces can drift in accuracy/scope.
- What has been completed: Structured status module and scoped checks.
- What is still pending: Additional hardening and verification.
- Supabase-adjacent blast radius: No.
- Recommended next step: Add deterministic fixture checks for diagnostics output.

### R-ADM-19 — Admin asset enqueue scope
- Area: Admin / Performance
- Priority: Medium
- Status: Mitigated
- Summary: Global admin asset scope reduced; optional hardening remains deferred.
- What has been completed: Patch A and B completed.
- What is still pending: Optional patch C (`$_GET['page']` strict hook/screen hardening).
- Supabase-adjacent blast radius: No.
- Recommended next step: Keep patch C in backlog unless new regressions appear.

### R-ADM-21 — Admin settings input integrity
- Area: Admin / Settings
- Priority: High
- Status: Resolved
- Summary: Input integrity hardening completed on practical hotspots.
- What has been completed: Patch A (Cart Popup normalize/allowlist/clamp) + patch B (remove variation-license `nopriv` route).
- What is still pending: None.
- Supabase-adjacent blast radius: No.
- Recommended next step: None; maintain regression checks.

### R-SEC-22 — SVG sanitization hardening follow-up
- Area: Security / SVG
- Priority: Critical
- Status: Resolved
- Summary: SVG upload and secondary intake/output surfaces are hardened with canonical sanitize/validate pipeline.
- What has been completed: Primary upload guards + secondary cart-popup/widget hardening completed.
- What is still pending: None.
- Supabase-adjacent blast radius: No.
- Recommended next step: Keep periodic SVG regression security checks.

### R-FPW-20 — Filtered post wall public AJAX
- Area: Filtered Post Wall
- Priority: High
- Status: Mitigated
- Summary: Public AJAX runtime risk reduced with transport + capability hardening; remaining work is abuse/rate posture for read-only endpoints.
- What has been completed: Transient cache/throttle guard, patch 1 POST-only transport hardening for public mutation endpoints, patch 2 capability enforcement (`edit_products`) for authenticated `bw_search_products_ajax`.
- What is still pending: patch 3 abuse/rate hardening review for public read-only endpoints.
- Supabase-adjacent blast radius: No.
- Recommended next step: Add strict result limits in `category=all` branch.

### R-FE-23 — Frontend dependency longevity (Slick)
- Area: Frontend / UI dependencies
- Priority: Medium
- Status: Open
- Summary: Heavy reliance on legacy Slick library remains a long-term maintainability risk.
- What has been completed: Version pinning for deterministic behavior.
- What is still pending: Controlled migration/refactor program.
- Supabase-adjacent blast radius: No.
- Recommended next step: Define phased replacement plan by widget family.

### R-WOO-24 — WooCommerce template override staleness
- Area: WooCommerce / Template Overrides
- Priority: High
- Status: Resolved
- Summary: Stale template override risk closed via incremental patch sequence.
- What has been completed: Patches 1..5 completed with compatibility rebases and regressions validated.
- What is still pending: None.
- Supabase-adjacent blast radius: No (explicitly avoided during patches).
- Recommended next step: Keep periodic override-version audit cadence.

### R-PERF-26 — Supabase sync latency coupling
- Area: Performance / Supabase runtime
- Priority: High
- Status: Mitigated
- Summary: Blocking sync pressure reduced with per-user guard.
- What has been completed: Transient sync guard deployed.
- What is still pending: Monitoring for latency and stale windows.
- Supabase-adjacent blast radius: Yes.
- Recommended next step: Observe guard hit/miss telemetry.

### R-PERF-27 — Checkout runtime scope on order-received
- Area: Performance / Checkout
- Priority: Medium
- Status: Mitigated
- Summary: Unneeded checkout runtime on thank-you endpoint removed.
- What has been completed: Guard excludes `is_order_received_page()`.
- What is still pending: Monitoring.
- Supabase-adjacent blast radius: No.
- Recommended next step: Keep thank-you flow regression smoke.

### R-PERF-28 — Stripe enqueue deduplication
- Area: Performance / Payments assets
- Priority: Low
- Status: Mitigated
- Summary: Duplicate Stripe enqueue paths consolidated.
- What has been completed: Canonical enqueue guard delivered.
- What is still pending: Monitoring.
- Supabase-adjacent blast radius: No.
- Recommended next step: None beyond periodic checkout network audit.

### R-PERF-29 — Cart popup dynamic CSS lookup cost
- Area: Performance / Cart Popup
- Priority: Low
- Status: Mitigated
- Summary: Repeated option lookups in CSS generation reduced.
- What has been completed: Single-pass local options map.
- What is still pending: Monitoring.
- Supabase-adjacent blast radius: No.
- Recommended next step: None beyond TTFB watch.

## 5. Recommended next wave
1. `R-RED-12` — High-impact routing authority risk with critical priority and open status.
2. `R-AUTH-04` — Core Supabase auth surface still open; requires a dedicated, controlled hardening wave.
3. `R-PAY-08` — Complete Google Pay runtime convergence to close partial mitigation state.
4. `R-FPW-20` — Public AJAX query-bounds hardening offers strong risk-reduction/value ratio.
