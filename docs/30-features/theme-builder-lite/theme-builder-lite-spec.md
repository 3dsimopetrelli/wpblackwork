# Theme Builder Lite (Elementor Free) - Feasibility + Foundation Spec

## 1) Task Start Template (Completed)

### 1.1 Task Classification
- Task ID: `THEME-BUILDER-LITE-FOUNDATION-001`
- Task title: `Theme Builder Lite (Elementor Free) - Feasibility + Foundation Plan`
- Domain: `Header / Global Layout (Tier 2), Product UX (Tier 1), Woo Template Runtime (Tier 1), Elementor Integration (Tier 2)`
- Tier classification (0/1/2/3): `1`
  - Rationale: touches global runtime orchestration and Woo rendering entrypoints, without introducing Tier 0 authority truth.
- Authority surface touched? (Yes/No): `No`
- Data integrity risk? (Low/Medium/High/Critical): `Medium`
- Requires ADR? (Yes/No): `No` (for MVP foundation only)
- Blast radius assessment:
  - Primary surfaces impacted: `footer render lifecycle`, `single product presentation lifecycle`, `Elementor CPT support`, `frontend font enqueue`
  - Cross-domain collision points: `existing header module`, `WooCommerce template override stack`, `theme footer behavior`
  - Hook-sensitive surfaces impacted: `wp_footer`, `wp`, `wp_head`, `template_redirect`, Woo single-product hooks

Normative determination:
- No authority ownership shift is introduced.
- No Tier 0 hook priority mutation is proposed.
- If future implementation introduces template stack ownership changes (`template_include` global takeover), ADR escalation is required.

### 1.2 Pre-Task Reading Checklist
Reviewed for this foundation plan:
- [x] ADR index (`docs/60-adr/`)
- [x] Authority Matrix (`docs/00-governance/authority-matrix.md`)
- [x] Runtime Hook Map (`docs/50-ops/runtime-hook-map.md`)
- [x] Domain Map (`docs/00-overview/domain-map.md`)
- [x] Core Evolution Plan (`docs/00-planning/core-evolution-plan.md`)
- [x] Regression Protocol (`docs/50-ops/regression-protocol.md`)
- [x] Blast Radius Consolidation Map (`docs/00-governance/blast-radius-consolidation-map.md`)
- [x] Relevant domain specs/audits:
  - `docs/30-features/header/header-module-spec.md`
  - `docs/20-development/woocommerce-template-overrides.md`
  - `woocommerce/woocommerce-init.php`
  - `includes/modules/header/frontend/header-render.php`

### 1.3 Scope Declaration
- Declared files to modify (this task):
  - `docs/30-features/theme-builder-lite/theme-builder-lite-spec.md`
  - `docs/10-architecture/theme-builder-lite/runtime-hook-map.md`
  - `docs/00-governance/risk-notes-theme-builder-lite.md`
- Declared runtime surfaces (analysis only, no code in this task):
  - `Theme Builder Lite module bootstrap`
  - `Template resolution for footer/single_product`
  - `Condition Engine MVP`
  - `Custom Fonts runtime enqueue`
- Declared docs to update: same as above.
- Explicitly out-of-scope surfaces:
  - Checkout/payment/auth logic
  - Redirect authority
  - Import engine
  - Elementor Pro emulation beyond MVP scope
- Explicitly forbidden changes:
  - Full global WordPress template stack override
  - New builder/editor implementation
  - Mutating WooCommerce truth surfaces

### 1.4 Governance Impact Analysis
- Does this change authority ownership? `No`
- Does this introduce a new truth surface? `Yes` (new template assignment truth surface for presentation only)
- Does this modify runtime hook registration or priority? `Planned (new module hooks), no Tier 0 mutation`
- Does this touch Tier 0 state or Tier 0 hooks? `No`
- Does this alter data identity rules (SKU/order/payment/consent/auth)? `No`

Required determination:
- Governance impact status: `Controlled`

### 1.5 System Invariants Check
- Impacts declared invariants? `Yes` (single authority per truth surface for template selection in presentation domain)
- Invariant impacted: `Single authority per truth surface`
- Impact: Theme Builder Lite must be the only resolver for `bw_template` selection, and must remain presentation-only.
- ADR required? `No` for MVP if it stays non-authoritative and scoped.

### 1.6 Determinism Statement
- Same input + same state snapshot MUST produce same resolved template.
- Tie-break strategy:
  1. Highest `priority`
  2. Highest `specificity`
  3. Most recent `post_modified_gmt`
  4. Highest `post_id`
- Pagination/state convergence expectation: N/A for MVP.
- Retry/re-entry convergence expectation: Resolver is pure/read-only per request.

### 1.7 Documentation Update Plan
- Roadmap update required? `No` (explicitly excluded by task)
- Decision log update required? `No` (no implementation decision freeze yet)
- Regression coverage map update required? `No` (foundation phase)
- Runtime hook map update required? `Yes`
  - Target: `docs/10-architecture/theme-builder-lite/runtime-hook-map.md`
- Feature/domain spec update required? `Yes`
  - Target: `docs/30-features/theme-builder-lite/theme-builder-lite-spec.md`
- Governance doc update required? `Yes`
  - Target: `docs/00-governance/risk-notes-theme-builder-lite.md`

### 1.8 Acceptance Gate
1. Module architecture is defined and feature-flagged.
2. CPT and metadata schema are deterministic and versioned.
3. Footer and single product runtime strategies are explicit, with rollback behavior.
4. Hook map includes exact WP/Woo hooks and priorities.
5. Conflict resolution and explicit "what NOT to do" rules are documented.

Abort conditions:
- Any requirement to globally replace template stack without tight scope.
- Any collision with Tier 0 authorities.
- Any non-deterministic resolver behavior.

## 2) Repository Scan Findings (A)

### 2.1 Existing template and runtime override points
- Woo template resolver is already centralized in `woocommerce/woocommerce-init.php`:
  - `add_filter('woocommerce_locate_template', 'bw_mew_locate_template', 1, 3)`
  - `add_filter('woocommerce_locate_core_template', 'bw_mew_locate_template', 1, 3)`
- Single product runtime already touched:
  - `bw_mew_hide_single_product_notices()` on `template_redirect` (priority 9)
  - Removes `woocommerce_output_all_notices` hooks on single product.
- Global header runtime already injected and theme header suppressed:
  - `add_action('wp_body_open', 'bw_header_render_frontend', 5)`
  - `add_action('wp', 'bw_header_disable_theme_header', 1)`
  - `add_action('wp_head', 'bw_header_theme_header_fallback_css', 99)`
- Existing frontend footer injection point in project:
  - `add_action('wp_footer', 'bw_mew_output_password_gating_modal', 50)` in My Account override.

### 2.2 Elementor integration points in current plugin
- Elementor widgets bootstrapped from plugin runtime (`includes/class-bw-widget-loader.php`).
- Dynamic tags registered on `elementor/init`.
- Frontend/editor asset hooks already used (`elementor/frontend/*`, `elementor/editor/*`).

Implication:
- Theme Builder Lite should follow existing module style and use additive hooks, avoiding global runtime disruption.

## 3) Module Structure Proposal (B)

Proposed module root:
- `includes/modules/theme-builder-lite/`

Proposed file plan:
- `includes/modules/theme-builder-lite/theme-builder-lite-module.php`
  - Bootstrap + feature flags + includes.
- `includes/modules/theme-builder-lite/config/feature-flags.php`
  - Option defaults + read helpers.
- `includes/modules/theme-builder-lite/cpt/template-cpt.php`
  - `bw_template` registration + admin labels + supports.
- `includes/modules/theme-builder-lite/cpt/template-meta.php`
  - Conditions, type, priority, status fields and sanitization.
- `includes/modules/theme-builder-lite/conditions/condition-engine.php`
  - Resolver and matcher API (deterministic).
- `includes/modules/theme-builder-lite/runtime/footer-runtime.php`
  - Footer template resolution + render + theme-footer conflict handling.
- `includes/modules/theme-builder-lite/runtime/single-product-runtime.php`
  - Single product resolution + Woo hook-based override.
- `includes/modules/theme-builder-lite/runtime/template-renderer.php`
  - Safe Elementor content rendering wrappers.
- `includes/modules/theme-builder-lite/fonts/custom-fonts.php`
  - Upload registration + `@font-face` css generation + enqueue.
- `includes/modules/theme-builder-lite/admin/theme-builder-settings.php`
  - Module flags UI in Blackwork settings (or integrated section).

Bootstrap integration point in plugin entry:
- Load `theme-builder-lite-module.php` from `blackwork-core-plugin.php`.
- Module remains fully passive when master feature flag is disabled.

## 4) CPT Model (C)

### 4.1 Single CPT
- CPT slug: `bw_template`
- Single source for all Theme Builder Lite templates.

### 4.2 Template types (MVP)
- `footer`
- `single_product`
- Future-safe (not active in MVP): `archive_product`, `search_results`, `single_post`.

Storage of type:
- Post meta key: `_bw_tmpl_type` (`footer`, `single_product`, ...)

### 4.3 Status/eligibility constraints
- Only `publish` templates are runtime candidates.
- Optional boolean meta `_bw_tmpl_enabled` (default `1`) for quick operational disable.

### 4.4 Elementor compatibility
- Keep Elementor Free as editor for `bw_template`.
- Add `bw_template` to Elementor-supported post types via `elementor/cpt_support` filter.
- No new editor or visual engine introduced.

## 5) Condition Engine MVP (D)

## 5.1 Condition operators and targets
MVP condition set:
- Include Entire Site
- Include Single Product
- Include Product Category
- Exclude Product Category
- Optional Include Search Results
- Priority numeric tie-breaker

Condition representation (normalized):
- `relation`: `AND` at top level for include/exclude blocks.
- Include block is OR-match among include rules.
- Exclude block is OR-match among exclude rules.
- Final match rule: `(include_match == true) AND (exclude_match == false)`

### 5.2 Deterministic evaluation order
1. Filter by template type.
2. Filter `publish` + enabled.
3. Evaluate include/exclude match.
4. Sort deterministic tie-breaks:
   - `priority` DESC
   - `specificity_score` DESC
   - `post_modified_gmt` DESC
   - `post_id` DESC
5. Pick first candidate.

Specificity score (MVP baseline):
- Include Single Product = 300
- Include Product Category = 200
- Include Search Results = 150
- Include Entire Site = 100
- Exclude Product Category does not raise specificity, only removes eligibility.

### 5.3 Conflict resolution
- If two templates are both eligible, highest `priority` wins.
- Equal priority: highest specificity wins.
- Equal specificity: latest modified wins.
- Full tie: highest post ID wins.

This makes output stable and reproducible.

## 6) Runtime Rendering Strategy (E)

### 6.1 Footer strategy (preferred)
Primary hook:
- `wp_footer` priority `20` -> render resolved `footer` template.

Theme footer conflict handling:
- If a Theme Builder footer is resolved and renderable:
  - Try removing known theme footer callbacks on `wp` (theme-specific safe removals only).
  - For Hello Elementor compatibility, remove `hello_elementor_render_footer` from `hello_elementor_footer` if present.
- If callback removal is not possible, use targeted fallback CSS on `wp_head` priority `99` only when custom footer is active.

Fallback behavior:
- If no eligible footer template exists, do nothing: theme footer renders normally.
- If template render fails, log + fail open: theme footer remains.

### 6.2 Single product strategy (preferred)
Preferred approach: Woo hooks, not global `template_include` replacement.

Runtime wiring:
- `template_redirect` priority `9` (or module-specific initialization hook) checks if request is `is_product()` and module flag enabled.
- Resolve `single_product` template via condition engine.
- If resolved, remove default Woo single product render callbacks for this request only.
- Inject custom renderer via `woocommerce_before_single_product` priority `5`.

Why this strategy:
- Keeps WordPress template stack intact.
- Keeps theme fallback available.
- Limits blast radius to single product page lifecycle.

Template include policy:
- `template_include` full takeover is explicitly NOT default.
- Only consider scoped fallback if Woo-hook approach cannot satisfy mandatory UX contracts.

## 7) Data Storage Schema (F)

### 7.1 Post meta schema (versioned)
- `_bw_tmpl_version` (int) - schema version, default `1`
- `_bw_tmpl_type` (string) - template type
- `_bw_tmpl_priority` (int) - numeric priority, default `10`
- `_bw_tmpl_enabled` (int) - `1|0`
- `_bw_tmpl_conditions` (JSON string) - normalized condition payload

Normalized `_bw_tmpl_conditions` JSON (v1):
```json
{
  "version": 1,
  "include": [
    { "rule": "entire_site" },
    { "rule": "single_product", "ids": [123, 456] },
    { "rule": "product_category", "term_ids": [11, 22] },
    { "rule": "search_results" }
  ],
  "exclude": [
    { "rule": "product_category", "term_ids": [33] }
  ]
}
```

Sanitization rules:
- Unknown rule types are dropped.
- IDs and term IDs are normalized to unique positive integers.
- Empty include set is invalid for published templates.

## 8) Feature Flags and Rollback

Master flag option key:
- `bw_theme_builder_lite_flags`

Proposed flags:
- `enabled` (global master)
- `custom_fonts_enabled`
- `footer_templates_enabled`
- `single_product_templates_enabled`
- `search_results_condition_enabled` (optional MVP extension)

Behavior:
- If `enabled=0`, module registers no runtime render mutations.
- If sub-flag disabled, corresponding runtime path is skipped.

Rollback:
- Disable `enabled` -> module becomes passive, theme/Woo default rendering resumes.
- Disable only `footer_templates_enabled` -> theme footer resumes.
- Disable only `single_product_templates_enabled` -> native Woo single product resumes.

No data deletion required for rollback.

## 9) What NOT To Do (Non-negotiable)

- Do NOT replace the entire WordPress template stack using broad `template_include` logic.
- Do NOT create a second visual editor or pseudo page builder.
- Do NOT store conditions in ad-hoc unversioned blobs.
- Do NOT bypass WooCommerce single-product lifecycle globally.
- Do NOT remove theme fallback unconditionally.
- Do NOT mutate Tier 0 hooks or authority domains.

## 10) Feasibility Verdict

Feasibility: `YES` (MVP is feasible inside current plugin architecture).

Reasoning:
- Existing plugin already supports modular runtime injection and theme fallback patterns (header module).
- WooCommerce and Elementor integration points already exist and can be reused.
- MVP scope can stay in Tier 1/2 orchestration and presentation layers with controlled risk.

Implementation prerequisite for next task:
- Approve hook map and risk notes in companion documents before coding.
