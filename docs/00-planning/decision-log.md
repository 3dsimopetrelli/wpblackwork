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

### Entry 013A
- Date: 2026-03-16
- Decision summary: Added a dedicated `Shop` settings tab in Theme Builder Lite as a footer-style authority surface for Woo shop archive root, while keeping `Product Archive` focused on category/tag archive rules.
- Affected domain: Theme Builder Lite / Woo Shop / Admin Configuration / Runtime Resolver
- Rationale: Operators need a simple, explicit control surface for the shop page that does not inherit the complexity of category-rule repeaters; separating Shop removes ambiguity and matches the current runtime split between shop root and category archives.
- Risk impact: Medium managed through explicit branch precedence, published-template validation, and fail-open resolver behavior.
- Follow-up actions:
  - Keep Shop using `product_archive` templates in this phase.
  - Keep Shop branch scoped to `product_archive_kind=shop` only.
  - Keep All Templates linkage badges synchronized with Shop selection.

### Entry 013B
- Date: 2026-03-16
- Decision summary: Promoted `Shop` to a first-class template type in All Templates/admin selectors while preserving legacy Shop compatibility with existing `product_archive` templates.
- Affected domain: Theme Builder Lite / Template Type Authority / Shop Surface
- Rationale: Once Shop has its own admin authority tab, operators also need a matching explicit type in the All Templates dropdown to avoid semantic ambiguity and keep template inventory understandable.
- Risk impact: Medium managed through bounded compatibility validation and unchanged resolver context branching.
- Follow-up actions:
  - Keep Shop validation accepting legacy `product_archive` templates during compatibility window.
  - Keep Product Archive category/tag routing authority separate from Shop.

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

### Entry 023
- Date: 2026-03-12
- Decision summary: Unified the former `bw-filtered-post-wall` Elementor widget under the canonical Product Grid naming model: visible title `BW Product Grid`, slug `bw-product-grid`, class `BW_Product_Grid_Widget`.
- Affected domain: Elementor Widgets / Product Grid / Documentation Alignment
- Rationale: The previous mixed naming (`BW-UI Product Grid`, `bw-filtered-post-wall`, `BW_Filtered_Post_Wall_Widget`) created avoidable drift between editor UI, runtime code, and architecture docs. One canonical name makes future audit/rebuild work easier and reduces ambiguity for contributors and AI sessions.
- Risk impact: Low-Medium, because top-level naming is now clearer while some low-level internal `bw_fpw_*` runtime contracts remain intentionally unchanged for scoped compatibility.
- Follow-up actions:
  - Keep `BW Product Grid` as the only canonical current name in active documentation.
  - Allow legacy naming only as explicit historical reference where traceability matters.
  - Reassess whether internal `bw_fpw_*` runtime contracts should be renamed in a future dedicated wave.
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

### Entry 028
- Date: 2026-03-07
- Decision summary: Wallet launcher ownership delegated to Stripe/WCPay runtime.
- Affected domain: Checkout Payments / Wallet Runtime Ownership
- Rationale: Investigation confirmed native wallet launchers are internally owned by WooCommerce Stripe / WCPay express runtime and custom Blackwork launcher paths were parallel, non-authoritative, and non-deterministic.
- Risk impact: Medium reduced to Low after disable-path hardening and template residue cleanup.
- Follow-up actions:
  - Keep `bw_google_pay_enabled` and `bw_apple_pay_enabled` as authoritative custom-runtime toggles.
  - Preserve enqueue gates in `woocommerce/woocommerce-init.php` so disabled wallets do not load custom JS.
  - Preserve wrapper gating in `woocommerce/templates/checkout/payment.php` so disabled wallets leave no dead DOM containers.
  - Do not introduce parallel custom wallet launcher flows when Stripe/WCPay already owns the runtime.

Problem:
- Repeated checkout wallet incidents showed custom launcher attempts were not reliably authoritative versus native Stripe/WCPay express wallet lifecycle.

Evidence:
- Authoritative native wallet surfaces mounted in Stripe/WCPay containers:
  - `#wc-stripe-express-checkout-element`
  - `#wc-stripe-payment-request-wrapper`
  - `#wcpay-express-checkout-element`
- Custom launchers were parallel PaymentRequest lifecycles (`bw-google-pay.js`, `bw-apple-pay.js`) with ownership mismatch.
- Disable-path audit confirmed full shutdown after conditional wrapper cleanup.

Decision:
- Delegate wallet launch lifecycle ownership to Stripe/WCPay express runtime.
- Keep Blackwork custom wallet launchers disabled in this ownership model.

Consequences:
- Prevents authority conflict and nondeterministic wallet launch behavior.
- Eliminates dead disabled-wallet residue in checkout template.
- Keeps checkout runtime deterministic under native Stripe/WCPay wallet ownership.

Future strategy:
- Blackwork must not implement parallel custom wallet launchers when Stripe/WCPay already owns wallet runtime.
- Future wallet integrations must use authoritative Stripe/WCPay integration points only.

### Entry 029
- Date: 2026-03-07
- Decision summary: Stripe Payment Element internal card/icon subview issue resolved by adopting `tabs` layout through `wc_stripe_upe_params`.
- Affected domain: Checkout / Stripe Payment Element UI ownership boundary
- Rationale: Internal Stripe subviews are Stripe-owned UI; selector-level hide attempts were exploratory and brittle. `tabs` removes the duplicated internal card/icon subview with lower fragility on the supported config surface.
- Risk impact: Medium reduced to Low-Medium through stable layout configuration and removal of internal selector hacks from solution strategy.
- Follow-up actions:
  - Keep Payment Element layout pinned to `tabs` in `bw_mew_customize_stripe_upe_appearance()`.
  - Use `wc_stripe_upe_params` as the authoritative control surface for Stripe Payment Element configuration.
  - Avoid reintroducing Stripe-internal selector hide hacks as primary behavior control.
  - Treat any migration to alternative Stripe card integration as a separate architecture task with full regression gate.

### Entry 030
- Date: 2026-03-07
- Decision summary: Retain guest-compatible nonce fallback in Supabase token-login callback path, with explicit governance watchpoint.
- Affected domain: Auth / Supabase callback bridge / anonymous invite re-entry
- Rationale: Cached anonymous callback landings can deliver stale localized nonces; strict nonce failure in this path breaks deterministic invite/token convergence for legitimate users.
- Risk impact: Medium (reduced CSRF strictness in guest callback path) with mitigations preserved by mandatory Supabase access-token verification before WP session creation.
- Follow-up actions:
  - Keep strict nonce enforcement for authenticated requests.
  - Keep guest fallback constrained to token-login callback path only.
  - Track public email-exists enumeration and callback-surface hardening separately in risk register updates.
  - Reassess fallback policy if cache architecture changes make fresh nonce guarantee deterministic.

### Entry 031
- Date: 2026-03-07
- Decision summary: Trusted WooCommerce filter boundary in checkout templates.
- Affected domain: Checkout / Template extension boundary
- Rationale: `woocommerce_cart_item_remove_link` is intentionally filter-extensible HTML in WooCommerce; unescaped echo of the filtered fragment is a trusted extension boundary, not an intrinsic vulnerability in Blackwork.
- Risk impact: Low (context-dependent on third-party filter trust), no direct Blackwork exploit path confirmed in baseline runtime.
- Follow-up actions:
  - Keep boundary documented in governance triage artifacts for Radar Batch 2 — Checkout Weakness Analysis.
  - Do not apply blind escaping that would break valid WooCommerce extension HTML.
  - Reassess only if untrusted plugin injection is introduced in the deployment profile.

Problem:
- Radar Batch 2 flagged `apply_filters('woocommerce_cart_item_remove_link', ...)` output as unsafe due to unescaped echo in checkout review template.

Evidence:
- Runtime template echoes filtered remove-link HTML in `woocommerce/templates/checkout/review-order.php` and follows WooCommerce extension contract for markup override.
- Baseline Blackwork payload is escaped before filter application; trust boundary applies to third-party filter injectors.

Decision:
- No sanitization change applied in this path.
- Classified as an extension trust-boundary decision, not a standalone vulnerability.

Consequences:
- Preserves WooCommerce ecosystem compatibility for remove-link customizations.
- Keeps risk governance-visible without introducing breaking output restrictions.

Future strategy:
- Maintain strict plugin trust posture in production stacks.
- Track this as a decision-log boundary item linked to Radar Batch 2 triage, not as an immediate runtime hotfix.

### Entry 032
- Date: 2026-03-09
- Decision summary: Re-aligned `Blackwork Site` admin panel to the current Shopify-style governance baseline after rollback drift on panel authority files.
- Affected domain: Admin Navigation / Admin Asset Scope / Mail Marketing Submenu / Panel Governance Traceability
- Rationale: A Supabase-oriented rollback unintentionally pulled older versions of panel authority files, causing drift from the documented panel contract and risking inconsistent admin landing/scope behavior.
- Risk impact: Medium reduced to Low through deterministic baseline restore and narrow blast-radius recovery.
- Follow-up actions:
  - Keep `admin/class-blackwork-site-settings.php` and `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php` aligned with admin-panel governance docs.
  - Keep `Site Settings` deterministic first submenu behavior under `blackwork-site-settings`.
  - Keep UI kit and page/tab asset scope guarded by current panel gating functions.
  - Preserve closure trace in `docs/tasks/BW-TASK-20260309-01-closure.md`.

### Entry 033
- Date: 2026-03-09
- Decision summary: Added Theme Builder Lite governed controls for footer exclusions and theme CSS suppression (breakpoints-only/full) with explicit bounded guard behavior.
- Affected domain: Theme Builder Lite / Admin Controls / Frontend + Elementor Styling
- Rationale: Theme stylesheet constraints (especially responsive breakpoints) created layout interference for Theme Builder Lite contexts; operators required explicit, panel-driven suppression controls with safety boundaries.
- Risk impact: Medium reduced to Low-Medium through bounded scope and fail-open behavior.
- Follow-up actions:
  - Keep control authority in Theme Builder Lite settings surfaces (Settings + Footer tabs).
  - Keep guard scope limited to child/parent theme stylesheet roots only.
  - Keep full-disable behavior authoritative when both suppression toggles are enabled.
  - Preserve closure trace in `docs/tasks/BW-TASK-20260309-02-closure.md`.

### Entry 034
- Date: 2026-03-12
- Decision summary: Executed a targeted BW Product Grid hardening pass after the rename and infinite-loading implementation to remove legacy artifacts and stabilize the widget runtime.
- Affected domain: Elementor Widgets / BW Product Grid / Frontend Runtime Hardening
- Rationale: The widget had completed naming convergence and infinite-loading delivery, but still contained hardcoded settings reads, dead branches, duplicated render logic, debug traces, and duplicated resize handling that increased drift risk and reduced maintainability.
- Risk impact: Medium reduced to Low-Medium through bounded cleanup with no architecture rewrite.
- Follow-up actions:
  - Keep the hardening wave scoped to incremental cleanup tasks `PG-01` through `PG-07`.
  - Preserve the current infinite-loading architecture and canonical widget naming while removing dead/runtime-misaligned code.
  - Revisit editor-control activation separately where controls exist in code but are not yet on the active registration path.
  - Keep closure trace synchronized in `docs/tasks/BW-TASK-20260312-ELW-01-closure.md`.

### 2026 — BW Product Grid Stabilization

Decision:
Perform a full cleanup and alignment pass on the Product Grid widget before further feature development.

Motivation:
- reduce legacy drift
- remove dead code
- ensure Elementor controls match runtime behavior
- unify responsive breakpoints across runtime layers

Key outcomes:
- dead JS utilities removed
- unused PHP code removed
- responsive breakpoints aligned
- editor controls activated
- resize runtime simplified

Impact:
The widget is now considered stable and ready for future UI refinement and feature work.

### 2026 — BW Product Grid Loading and Animation Hardening

Decision:
Correct loading-policy propagation and animation sequencing before further UX refinements on `BW Product Grid`.

Motivation:
- initial and appended image loading needed explicit eager/lazy authority
- hover-image loading was incorrectly allowed to influence Masonry/reveal timing
- reveal sequencing still had race-risk during initial and replace-mode flows
- UX refinement work would remain unstable unless loading and layout readiness were deterministic first

What was fixed:
- `BW_Product_Card_Component` now accepts explicit `image_loading` and `hover_image_loading`
- AJAX and initial render now use explicit loading policy propagation
- initial server render no longer treats all first-batch images the same
- Masonry/reveal waits were narrowed to primary images only
- initial and replace-mode reveals now start only after grid-ready completion
- stale stagger timers are cleared before new reveal cycles

Impact:
Loading and animation behavior is now deterministic enough to support further visual refinement without preserving legacy race-condition compensations.

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

### Entry 035
- Date: 2026-03-18
- Decision summary: Brevo DOI flow extended with explicit pre-assign of the contact to the Unconfirmed list before the confirmation email is sent, making pre-confirmation subscribers visible in Brevo immediately.
- Affected domain: Brevo / Mail Marketing Integration / Elementor subscription widget
- Rationale: Brevo's `/contacts/doubleOptinConfirmation` endpoint adds the contact to `includeListIds` only after the confirmation link is clicked. Without a preceding `upsert_contact` call, the contact is invisible in all Brevo lists before confirmation, making the pending subscriber untrackable in the Brevo panel.
- Risk impact: Low — upsert is non-fatal; DOI email is sent regardless of upsert result; single opt-in and checkout paths are unaffected; the Unconfirmed list field defaults to `0` preserving backward-compatible behavior for unconfigured installs.
- Follow-up actions:
  - Configure `Unconfirmed list` to `#11 – Blackwork – Unconfirmed` in General settings after deployment.
  - Removal from the Unconfirmed list after confirmation is not handled by the plugin; implement via Brevo Automation (trigger: added to list #10 → remove from list #11).
  - Keep closure trace in `docs/tasks/BW-TASK-20260318-01-closure.md`.

### Entry 036
- Date: 2026-03-18
- Decision summary: Added `bw-product-description` as the canonical single-product description utility widget for Elementor, using existing BW-SP editor identity and shared product-context resolution patterns.
- Affected domain: Elementor Widgets / WooCommerce / Single Product
- Rationale: Designers need a dedicated widget to place the product long description anywhere inside single-product layouts while preserving the original HTML markup and avoiding duplicated manual text blocks.
- Risk impact: Low-Medium — runtime scope is limited to single-product rendering, failure mode outside product context is safe-empty, and editor identity reuses an existing BW-SP convention.
- Follow-up actions:
  - Keep product-context resolution aligned with `bw_tbl_resolve_product_context_id()`.
  - Reuse the existing BW-SP editor-family convention for future Woo single-product utility widgets where appropriate.
  - Keep closure trace in `docs/tasks/BW-TASK-20260318-02-closure.md`.

### Entry 037
- Date: 2026-03-18
- Decision summary: Added `bw-product-breadcrumbs` as the canonical single-product breadcrumb utility widget for Elementor, with deterministic category-path selection and a self-contained scoped style surface.
- Affected domain: Elementor Widgets / WooCommerce / Single Product
- Rationale: Product templates need a reusable breadcrumb utility that can be positioned freely in Elementor while remaining stable across products with multiple categories and without depending on theme-specific breadcrumb markup.
- Risk impact: Low-Medium — runtime is limited to single-product rendering, category resolution uses a fixed precedence rule, and styling is scoped to the widget.
- Follow-up actions:
  - Keep the breadcrumb category-path rule deterministic (deepest path first, lowest term ID tie-break).
  - Keep the widget style surface local to the breadcrumb component.
  - Keep closure trace in `docs/tasks/BW-TASK-20260318-03-closure.md`.

### Entry 038
- Date: 2026-03-19
- Decision summary: Extended `bw-product-description` to support deterministic description source modes: full description, short description, or both in fixed order.
- Affected domain: Elementor Widgets / WooCommerce / Single Product
- Rationale: Product templates need a single reusable description widget that can surface either the merchant-authored long description, the short description, or both without forcing duplicate widgets or manual text duplication.
- Risk impact: Low-Medium — runtime scope remains limited to single-product rendering, empty sources fail safely, and `both` mode uses a fixed render order (`short_description` then `description`).
- Follow-up actions:
  - Keep `both` mode order deterministic.
  - Preserve HTML formatting paths for both long and short product descriptions.
  - Keep closure trace in `docs/tasks/BW-TASK-20260319-01-closure.md`.

### Entry 039
- Date: 2026-03-19
- Decision summary: Added a reusable Elementor container sticky sidebar extension managed by the plugin, using CSS-first `position: sticky` and container-level controls instead of Elementor Pro sticky features.
- Affected domain: Elementor Runtime / Container Controls / Frontend Layout
- Rationale: Product layouts need a reusable sticky pricing/sidebar behavior applied to the outer container itself, not hardcoded per widget or delegated to Elementor Pro.
- Risk impact: Low-Medium — behavior is opt-in and CSS-first, but remains sensitive to ancestor overflow and flex/stretch constraints inherent to sticky positioning.
- Follow-up actions:
  - Keep the sticky target on the outer pricing/sidebar container as the default usage contract.
  - Keep JS out of the implementation unless a concrete edge case requires fallback behavior.
  - Keep closure trace in `docs/tasks/BW-TASK-20260319-02-closure.md`.
