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
| R-SEC-30 | Cart popup XSS — item.name/permalink | <span style="background:#2ecc71;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">RESOLVED</span> | Medium | 2026-03-16 |
| R-SEC-31 | Cart popup XSS — coupon code | <span style="background:#2ecc71;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">RESOLVED</span> | Low | 2026-03-16 |
| R-WOO-24 | WooCommerce templates | <span style="background:#2ecc71;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">RESOLVED</span> | High | 2026-03-09 |
| R-SEC-22 | SVG pipeline | <span style="background:#2ecc71;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">RESOLVED</span> | High | 2026-03-10 |
| R-ADM-21 | Admin settings integrity | <span style="background:#2ecc71;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">RESOLVED</span> | High | 2026-03-10 |
| R-REVIEWS-01 | Reviews confirmation targeting | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Medium | 2026-04-02 |
| R-REVIEWS-02 | Reviews modal accessibility | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Low | 2026-04-02 |
| R-ADM-19 | Admin asset scoping | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Medium | 2026-03-09 |
| R-PAY-08 | Payment webhook architecture | <span style="background:#f39c12;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">PARTIAL</span> | Critical | 2026-03-09 |
| R-MF-01 | Media folders counts pipeline | <span style="background:#95a5a6;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">WATCHLIST</span> | Medium-High | 2026-03-09 |
| R-IMP-10 | Import / Catalog Data Integrity | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Critical | 2026-03-10 |

## Dashboard purpose
The Risk Status Dashboard provides a single-glance overview of all governance risks and their current state.  
It complements the full risk register but is optimized for quick orientation when starting new work sessions or opening new engineering chats.

## 1. Executive snapshot
- Total risks: 58
- Resolved: 9
- Mitigated: 26
- Partial Mitigation Complete: 2
- Open: 20
- Watchlist / Deferred: 1

Snapshot integrity rule:
- Executive snapshot counts MUST be synchronized with the totals derived from the rows in **Risk summary table**.

Last governance-aligned updates:
- 2026-04-02: Reviews system + modal flow moved to `Almost ready` after blocker fixes: confirmation-email false-success handling closed, timeout/failure recovery added for submit/edit/list flows, modal rendering consolidated to one canonical page-level instance, and verified-buyers-only policy now explicitly disables guest write paths. Remaining manual validation risks recorded as `R-REVIEWS-01` and `R-REVIEWS-02`. Reference: `docs/tasks/BW-TASK-20260402-reviews-system-final-validation-summary.md`.
- 2026-03-19: `BW-TASK-20260319-PS-01` closed — BW Presentation Slide full hardening: glassmorphism cursor redesign (single toggle, 10+ controls removed), three bug fixes (orphaned popup overlay in `destroy()`, selector cache race before `emblaCore.init()`, system cursor hidden over arrow buttons). Feature docs created at `docs/30-features/presentation-slide/`. No Tier 0 surface affected.
- 2026-03-16: `R-SEC-30` resolved — XSS hardening in `renderCartItems()`: `item.name` and `item.permalink` now HTML-escaped via `_escHtml()`/`_escUrl()` helpers on `BW_CartPopup`; `javascript:` and `data:` URI schemes blocked in href.
- 2026-03-16: `R-SEC-31` resolved — coupon code XSS hardening in `updateTotals()`: `code` → `safeCode` via `_escHtml()` before `<b>` text node and `data-code` attribute injection.
- 2026-03-16: Cart popup batch hardening completed — Phase 2 dynamic CSS/admin field removal, visual bug fixes, functional race-condition hardening, performance optimizations (open-cache, partial qty update, batch coupon append), dead code removal (`getCheckoutUrl()`, `bw_cart_popup_hex_to_rgb()`), item-collapse animation, Add-to-Cart button state revert on item removal.
- 2026-03-16: Product grid — Elementor frontend hook fallback added (`elementorFrontend.on('init')` guard); reveal animation speed increased (CSS `1.8s → 0.45s`, stagger `80ms → 40ms`).
- 2026-03-10: `R-FE-23` asset-scope hardening closed (`blackwork-core-plugin.php` moved Slick/bootstrap to register-only `init`; dependency-driven loading prevents global Slick CDN payload on pages without Slick widgets).
- 2026-03-10: `R-PERF-29` task closed (Cart Popup runtime scope hardening + checkout suppression option; popup assets/panel/CSS/JS suppressed on checkout when enabled by default).
- 2026-03-10: `R-FPW-02` closed (FPW transient key canonicalization in `bw_fpw_get_tags`; stable cache reuse for identical subcategory sets).
- 2026-03-10: ReRadar FPW tag N+1 finding closed as **False Positive** (`bw_fpw_collect_tags_from_posts()` already uses batched `wp_get_object_terms` over bounded ID set; no runtime patch required).
- 2026-03-10: `R-FE-23` mitigated with fail-soft Slick hardening (shared/product guard + bounded presentation retry stop) without architecture refactor.
- 2026-03-10: `R-ADM-18` mitigated with deterministic diagnostics freshness metadata (`is_partial_refresh`, `refreshed_checks`, `last_full_generated_at`) and `Mixed` source indicator for scoped refreshes.
- 2026-03-10: `R-HDR-13` mitigated via deterministic multi-instance search overlay coordination (`BW_HEADER_SEARCH_OPEN_COUNT`) in `includes/modules/header/assets/js/bw-search.js`.
- 2026-03-10: `R-MF-02` and `R-MF-03` mitigated after Media Folders query-merge hardening (`bw_mf_merge_tax_query` outer `AND`) and assignment integrity re-validation.
- 2026-03-10: `R-SRCH-11` mitigated with live-search visibility alignment (`exclude-from-search` only) in `includes/modules/header/frontend/ajax-search.php`; search semantics drift reduced with no Supabase/auth scope impact.
- 2026-03-10: External radar HTTP-timeout finding marked stale/not applicable after repository-wide verification (all relevant calls have explicit timeout; no unsafe timeout/cURL path).
- 2026-03-10: `R-RED-12` mitigated on strict non-Supabase scope (`bw_show_coming_soon` precedence clarified to `template_redirect@1`); auth/Supabase redirect scope remains frozen.
- 2026-03-10: `R-PAY-02` mitigated and task closed after manual checkout validation (payment convergence hardening in `bw-payment-methods.js`).
- 2026-03-10: `R-PAY-03` partial mitigation recorded (Google Pay availability ownership hardened; manual wallet regression verification pending).
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
| R-RED-12 | Redirect authority drift | Redirect / Routing | Critical | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Coming Soon precedence clarified (`template_redirect@1`) | Auth/Supabase redirect scope frozen for later review | Non-Supabase mitigated |
| R-PAY-08 | Webhook architecture drift | Payments / Webhooks | Critical | <span style="background:#f39c12;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">PARTIAL</span> | Patch 1 POST-only enforcement closed | Google Pay runtime convergence task | Non-Supabase |
| R-SEC-22 | SVG sanitization hardening follow-up | Security / SVG | Critical | <span style="background:#2ecc71;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">RESOLVED</span> | Secondary SVG hardening completed | None | Non-Supabase |
| R-CHK-01 | Payment selector race/convergence | Checkout / Payments | High | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Selector authority model shipped | Ongoing monitoring | Non-Supabase |
| R-CHK-02 | Strict bootstrap runtime break | Checkout / Runtime | High | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | `arguments.callee` removal completed | Monitoring only | Non-Supabase |
| R-PAY-02 | Checkout payment state integrity | Payments / Checkout | High | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Minimal JS hardening shipped + manual validation passed | Monitoring only | Non-Supabase |
| R-PAY-03 | Wallet availability/state drift | Payments / Wallets | High | <span style="background:#f39c12;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">PARTIAL</span> | Google Pay availability ownership hardened | Manual wallet regression validation | Non-Supabase |
| R-AUTH-05 | Supabase auth flow stabilization | Auth / Supabase | High | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Mitigation wave completed | Monitoring | Supabase-adjacent |
| R-AUTH-25 | Public auth endpoint exposure | Auth / Supabase | High | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Exposure controls tightened | Rate-limit/abuse posture review | Supabase-adjacent |
| R-SUPA-06 | Supabase orders/account coupling | Supabase / Orders / Account | High | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Mitigation controls documented | Monitoring | Supabase-adjacent |
| R-BRE-09 | Brevo checkout sync drift | Brevo / Checkout | High | <span style="background:#2ecc71;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">RESOLVED</span> | Patch 1 + patch 2 closed; stale `xkeysib-...` finding verified | None | Closed state; non-Supabase |
| R-SRCH-11 | Search runtime coupling risk | Search / Header | High | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Live-search visibility aligned to WooCommerce search semantics | Monitoring + optional abuse/rate hardening follow-up | Non-Supabase |
| R-HDR-13 | Header orchestration complexity | Header / UX | High | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Search overlay global state hardened with reference counter | Monitoring + cross-device orchestration checks | Non-Supabase |
| R-FPW-20 | Public AJAX filtered wall risk | Product Grid (formerly Filtered Post Wall) | High | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Patch 1 + patch 2 + R-FPW-02 closed | Patch 3 abuse/rate hardening review | Non-Supabase |
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
| R-MF-02 | Folder assignment data integrity | Media Folders | Medium | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Assignment pipeline re-validated (no critical bug found) | Monitoring under large-library scenarios | Non-Supabase |
| R-MF-03 | Media admin runtime drift | Media Folders | Medium | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Restrictive tax_query merge hardening shipped | Monitoring + plugin-interop observation | Non-Supabase |
| R-AUTH-12 | Auth frontend runtime scope | Auth / Supabase | Medium | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Scope reduction completed | Monitoring | Supabase-adjacent |
| R-AUTH-13 | Auth preload scope | Auth / Supabase | Medium | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Preload hardening completed | Monitoring | Supabase-adjacent |
| R-ACC-07 | My Account auth integration drift | My Account / Supabase | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Existing safeguards retained | Additional convergence hardening | Supabase-adjacent |
| R-GOV-14 | Governance operational continuity | Governance / Tooling | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Governance docs updated | Ongoing process hardening | Cross-domain |
| R-TBL-17 | Elementor preview context drift | Theme Builder Lite | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Preview-context safeguards | Monitoring | Non-Supabase |
| R-ADM-18 | Admin diagnostics integrity risk | Admin / System Status | Medium | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Partial-refresh freshness hardening shipped | Monitoring only | Non-Supabase |
| R-ADM-19 | Admin asset scope/perf risk | Admin / Enqueue Scope | Medium | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Patch A/B closed; C deferred | Optional patch C only | Non-Supabase |
| R-FE-23 | Slick dependency longevity risk | Frontend / Dependencies | Medium | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Fail-soft + register-only Slick scope hardening shipped | Monitoring + long-term migration planning | Non-Supabase |
| R-PERF-27 | Order-received checkout scope | Performance / Checkout | Medium | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Guard excludes order-received runtime | Monitoring | Non-Supabase |
| R-TBL-01 | bw_template preview 404 risk | Theme Builder Lite | Medium | <span style="background:#2ecc71;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">RESOLVED</span> | Closed with previewable CPT path | None | Historical resolved risk |
| R-TBL-02 | Admin asset/editor interference | Theme Builder Lite | Medium | <span style="background:#2ecc71;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">RESOLVED</span> | Closed with strict enqueue scoping | None | Historical resolved risk |
| R-TBL-03 | Custom fonts Elementor visibility | Theme Builder Lite | Medium | <span style="background:#2ecc71;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">RESOLVED</span> | Closed with Elementor font integration | None | Historical resolved risk |
| R-PERF-28 | Stripe enqueue duplication | Performance / Payments | Low | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Canonical Stripe enqueue path shipped | Monitoring | Non-Supabase |
| R-PERF-29 | Cart popup runtime scope/load drift | Performance / Cart Popup | Low | <span style="background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">MITIGATED</span> | Runtime-needed guard + checkout suppression setting shipped | Monitoring | Non-Supabase |
| R-REVIEWS-01 | Confirmation notice attaches to wrong widget | Reviews / Modal Flow | Medium | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Canonical single-modal architecture shipped | Manual validation on multi-widget pages; future targeting fix if needed | Non-Supabase |
| R-REVIEWS-02 | Modal accessibility incomplete | Reviews / Accessibility | Low | <span style="background:#e74c3c;color:white;padding:2px 8px;border-radius:10px;font-size:12px;">OPEN</span> | Modal timeout recovery + single instance shipped | Post-launch focus trap and focus-restore improvement | Non-Supabase |

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
- Status: Mitigated
- Summary: Residual checkout payment convergence drift was hardened with minimal JS-only guards.
- What has been completed: Explicit-selection re-apply guard hardened; pre-submit reconciliation added; free-order button-label drift guard added; manual checkout validation passed.
- What is still pending: Routine monitoring only.
- Supabase-adjacent blast radius: No.
- Recommended next step: Keep the payment-state regression checks in regular checkout validation cycles.

### R-PAY-03 — Wallet runtime state drift
- Area: Payments / Wallets
- Priority: High
- Status: Partial Mitigation Complete
- Summary: Google Pay availability ownership drift was reduced by removing optimistic availability state and deriving from runtime readiness.
- What has been completed: Deterministic `BW_GPAY_AVAILABLE` setter in Google Pay runtime, failure/negative capability hardening, and convergence sync on availability transitions.
- What is still pending: Full manual wallet regression pass (supported/unsupported capability scenarios and refresh paths).
- Supabase-adjacent blast radius: No.
- Recommended next step: Complete manual wallet regression checklist and promote status to mitigated if all checks pass.

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
- Status: Mitigated
- Summary: Assignment integrity is stable under current capability/context/batch guards.
- What has been completed: Assignment lifecycle re-validation confirmed no active critical bug; integrity controls remain robust.
- What is still pending: Monitoring only for extreme-volume/interop edge cases.
- Supabase-adjacent blast radius: No.
- Recommended next step: Keep bulk assignment regression checks in normal cadence.

### R-MF-03 — Media admin runtime stability
- Area: Media Folders
- Priority: Medium
- Status: Mitigated
- Summary: Runtime drift risk from tax-query relation inheritance has been corrected.
- What has been completed: `bw_mf_merge_tax_query()` hardened with explicit outer `AND` so folder/unassigned filter stays restrictive with third-party tax clauses.
- What is still pending: Monitoring only for plugin interop regressions.
- Supabase-adjacent blast radius: No.
- Recommended next step: Keep list/grid + mixed tax_query interop checks in regression matrix.

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
- Status: Mitigated
- Summary: Live search semantics were misaligned with WooCommerce visibility and could hide searchable products.
- What has been completed: Search visibility tax-query aligned to search context (`exclude-from-search` only) in `includes/modules/header/frontend/ajax-search.php`; no auth/Supabase surface changes.
- What is still pending: Monitoring and optional abuse/rate hardening for public nopriv endpoint.
- Supabase-adjacent blast radius: No.
- Recommended next step: Keep reusable live-search regression checks in normal release cadence.

### R-RED-12 — Redirect authority boundary
- Area: Redirect / Routing
- Priority: Critical
- Status: Mitigated (non-Supabase scope)
- Summary: Non-auth redirect authority ambiguity in maintenance mode was resolved by explicit Coming Soon precedence over generic redirect engine.
- What has been completed: `bw_show_coming_soon` moved to `template_redirect@1` (minimal precedence fix), preserving existing redirect engine and route protections.
- What is still pending: Auth/Supabase redirect domain remains frozen and out of scope for this mitigation wave.
- Supabase-adjacent blast radius: Frozen / excluded.
- Recommended next step: Re-open dedicated redirect review for auth/Supabase surfaces after freeze lift.

### R-HDR-13 — Header global orchestration risk
- Area: Header / UX orchestration
- Priority: High
- Status: Mitigated
- Summary: Global search overlay body-state drift across desktop/mobile instances has been corrected.
- What has been completed: Added deterministic reference-counted ownership (`BW_HEADER_SEARCH_OPEN_COUNT`) for `body.bw-search-overlay-active` in header search runtime.
- What is still pending: Monitoring via cross-device orchestration regression checks.
- Supabase-adjacent blast radius: No.
- Recommended next step: Keep header overlay/cart/nav interaction checks in standard regression cadence.

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
- Status: Mitigated
- Summary: Diagnostics freshness signaling drift in scoped refreshes has been corrected.
- What has been completed: Added deterministic partial-refresh metadata in snapshot payloads and `Mixed` UI source indicator; DB fallback accumulation micro-hardening shipped.
- What is still pending: Monitoring and routine regression execution.
- Supabase-adjacent blast radius: No.
- Recommended next step: Keep diagnostics mixed-freshness checks in regular release regression cadence.

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

### R-FPW-20 — Product Grid public AJAX (formerly Filtered Post Wall)
- Area: Product Grid (formerly Filtered Post Wall)
- Priority: High
- Status: Mitigated
- Summary: Public AJAX runtime risk reduced with transport + capability hardening; remaining work is abuse/rate posture for read-only endpoints.
- What has been completed: Transient cache/throttle guard, patch 1 POST-only transport hardening for public mutation endpoints, patch 2 capability enforcement (`edit_products`) for authenticated `bw_search_products_ajax`, R-FPW-02 cache-key canonicalization for deterministic transient reuse, ReRadar suspected FPW tag N+1 closed as false positive (batched `wp_get_object_terms` already in place).
- What is still pending: patch 3 abuse/rate hardening review for public read-only endpoints.
- Supabase-adjacent blast radius: No.
- Recommended next step: Add strict result limits in `category=all` branch.

### R-FE-23 — Frontend dependency longevity (Slick)
- Area: Frontend / UI dependencies
- Priority: Medium
- Status: Mitigated
- Summary: Slick runtime fragility and global over-scope loading have been reduced with fail-soft guards and register-only bootstrap.
- What has been completed: Added explicit Slick availability guards in shared/product slider runtimes, replaced infinite presentation retry with bounded stop, and moved Slick/bootstrap payloads to register-only `init` so loading is widget-dependency-driven.
- What is still pending: Long-term migration planning away from legacy Slick dependency.
- Supabase-adjacent blast radius: No.
- Recommended next step: Keep Slick-unavailable and Slick-scope checks in release cadence while planning migration.

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

### R-PERF-29 — Cart popup runtime scope/load drift
- Area: Performance / Cart Popup
- Priority: Low
- Status: Mitigated
- Summary: Cart Popup runtime load was hardened to avoid unnecessary global surfaces and to suppress popup runtime on checkout by default.
- What has been completed: Single-pass dynamic CSS option map, centralized runtime-needed guard for assets/panel/CSS, and admin setting `bw_cart_popup_disable_on_checkout` (default enabled) in Blackwork Site Panel.
- What is still pending: Monitoring.
- Supabase-adjacent blast radius: No.
- Recommended next step: Keep checkout/non-checkout runtime suppression checks in regression protocol.

### R-BREVO-32 — Already subscribed behavior drift after lookup optimization
- Area: Brevo / Newsletter Subscription Widget
- Priority: Medium
- Status: Open
- Summary: Conditional pre-lookup optimization reduced provider calls, but the explicit `already_subscribed` UX now requires manual confirmation in non-policy-sensitive paths.
- What has been completed: Lookup-before-write is now limited to `no_auto_resubscribe` enforcement paths, reducing one provider round trip from the normal path.
- What is still pending: Manual validation of `already_subscribed` response semantics and form-reset behavior.
- Supabase-adjacent blast radius: No.
- Recommended next step: Run live validation against an email already present in the target list.

### R-BREVO-33 — Brevo attribute schema mismatch
- Area: Brevo / Newsletter Subscription Metadata
- Priority: Medium
- Status: Open
- Summary: Metadata hardening now blocks false-success fallback, but the required audit attributes must still be confirmed against the real production Brevo schema.
- What has been completed: Required consent/source metadata is now non-droppable during fallback, preventing degraded-success subscriptions.
- What is still pending: Production schema validation for required attributes.
- Supabase-adjacent blast radius: No.
- Recommended next step: Verify the required attribute set directly in the production Brevo account before launch.

### R-REVIEWS-01 — Confirmation notice attaches to wrong widget
- Area: Reviews / Modal Flow
- Priority: Medium
- Status: Open
- Summary: The reviews system now uses one canonical modal per page, but confirmation notices still resolve against the first widget runtime context on multi-widget pages.
- What has been completed: Duplicate modal DOM and duplicate IDs were removed by switching to a shared page-level modal instance.
- What is still pending: Manual validation on pages with multiple review widgets and, if needed, a follow-up targeting fix.
- Supabase-adjacent blast radius: No.
- Recommended next step: Test confirmation success/expired/invalid flows on a page that renders multiple review widgets.

### R-REVIEWS-02 — Modal accessibility incomplete
- Area: Reviews / Accessibility
- Priority: Low
- Status: Open
- Summary: Modal create/edit flows are now stable under timeout/failure conditions, but keyboard accessibility is still incomplete because focus is not trapped and not restored to the opener.
- What has been completed: Timeout-based request hardening, failure recovery, and canonical single-modal ownership were implemented.
- What is still pending: Focus trap and focus-restore behavior.
- Supabase-adjacent blast radius: No.
- Recommended next step: Schedule a post-launch accessibility pass for the reviews modal.

## 5. Recommended next wave
1. `R-AUTH-04` — Core Supabase auth surface still open; requires a dedicated, controlled hardening wave.
2. `R-PAY-08` — Complete Google Pay runtime convergence to close partial mitigation state.
3. `R-FPW-20` — Public AJAX query-bounds hardening offers strong risk-reduction/value ratio.
4. `R-RED-12` — Execute frozen auth/Supabase redirect review only after freeze lift.
