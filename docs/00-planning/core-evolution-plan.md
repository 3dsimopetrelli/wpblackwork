# Blackwork Core – Evolution Plan

This document is the governance planning layer for roadmap execution.
It tracks priority, risk, execution intent, and acceptance gates.
It MUST be used as planning reference only and MUST NOT replace ADRs.

## Tier 0 – Critical Data / Authority

### Import Products — Import Engine v2 (Implementation)
- Status: Implemented — awaiting runtime validation
- Risk classification: Tier 0 Data Integrity
- Short description: Implement the vNext bulk-safe importer with SKU-canonical identity, chunked execution, checkpoint resume, and run-level audit trail.
- Latest hardening increment: run-lock reclaim CAS guard, active-run authority guard, and replay-safe processed-row dedupe (`processed_row_keys`) implemented in current runtime.
- Manual runtime validation will occur during real catalog import operations.
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
