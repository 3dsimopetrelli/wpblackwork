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
