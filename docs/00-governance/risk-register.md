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
- Domain: Filtered Post Wall / Public AJAX Runtime
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
