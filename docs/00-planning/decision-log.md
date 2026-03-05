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
  - Keep media bulk assignment as media-only behavior.

### Entry 021
- Date: 2026-03-05
- Decision summary: Opened a phased Blackwork Site admin hardening program after Shopify rollout audit, prioritizing enqueue-scope correctness and modular maintainability without changing runtime/storefront behavior.
- Affected domain: Admin Architecture / Governance / Performance
- Rationale: UI unification is complete, but audit evidence shows residual technical debt in broad asset loading and monolithic admin controller structure; incremental hardening is needed to keep the panel stable and scalable.
- Risk impact: Medium (admin regressions during refactor) mitigated by phased rollout, strict invariants, and regression gates.
- Follow-up actions:
  - Execute P1 enqueue tightening first (`bw_site_settings_admin_assets` page/tab matrix).
  - Keep all refactors UI/admin-only unless separately approved at governance level.
  - Prepare ADR before any large decomposition of `admin/class-blackwork-site-settings.php`.

## Governance Layer Closure

Status: CLOSED  
Date: 2026-02-27  
Notes:
Governance structure finalized after structural review and normalization.
Further changes require ADR escalation.

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
