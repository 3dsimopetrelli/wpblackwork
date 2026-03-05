# Theme Builder Lite (Elementor Free) - Specification

## Status
- Phase 1: Implemented
- Phase 2 Step 1: Implemented (resolver skeleton only)
- Phase 2 Step 2: Implemented (conditions engine core, no UI yet)
- Phase 2 Step 3: Implemented (Display Rules metabox + deterministic persistence)
- Phase 2 Step 4: Implemented (archive non-Woo contexts + archive rules)
- Phase 2 Step 5: Implemented (Woo single product context + conditions)
- Phase 2 Step 6: Implemented (Woo product archive context + conditions)
- Single Product conditions UX stabilization: Implemented (settings-tab source of truth; Quick Edit conditions removed)
- Scope delivered in Phase 1:
  - Custom Fonts module
  - Footer Template module
- Scope delivered in Phase 2 Step 1:
  - `template_include` resolver skeleton with strict bypass guards
  - Deterministic winner selection (`priority DESC`, `template_id ASC`)
  - Runtime wrapper render path (Elementor builder first, `the_content` fallback)
  - New feature flag: `templates_enabled`
- Scope delivered in Phase 2 Step 2:
  - Display rules storage contract via `bw_tbl_display_rules_v1` post meta
  - Rules normalization pipeline (`include[]`, `exclude[]`) with deterministic invalid-rule stripping
  - Exclude-first evaluation + include evaluation contract integrated into resolver candidate filtering
- Scope delivered in Phase 2 Step 3:
  - WordPress-native `Display Rules` metabox on `bw_template`
  - Priority field persisted to `bw_template_priority` (`0..999`, default `10`)
  - Include/Exclude sections persisted to `bw_tbl_display_rules_v1` using normalized shape
- Scope delivered in Phase 2 Step 4:
  - Added `archive` template type (non-Woo contexts only)
  - Resolver context mapping for blog archive, category archive, tag archive, and post type archive
  - Conditions engine support for `archive_blog`, `archive_category`, `archive_tag`, `archive_post_type`
  - Archive-specific Include/Exclude controls in metabox with deterministic sanitize/save
- Scope delivered in Phase 2 Step 5:
  - Added `single_product` template type
  - Resolver context mapping for Woo single product requests (`is_product()`) with endpoint safety bypass unchanged
  - Conditions engine support for `product_category` (`product_cat` terms) and `product_id` (specific product IDs)
  - Single Product settings authority finalized in Theme Builder Lite tab with deterministic repeater v2 sanitize/save
- Scope delivered in Phase 2 Step 6:
  - Added `product_archive` template type
  - Resolver context mapping for Woo shop + product category/tag archives
  - Conditions engine support for `product_archive_shop`, `product_archive_category`, `product_archive_tag`
  - Product Archive settings authority finalized in Theme Builder Lite tab with deterministic repeater v2 sanitize/save
- Out of scope (not implemented):
  - Woo template stack takeover

## Task Start Template (Phase 1)

### 1) Task Classification
- Task ID: `TBL-PHASE1-FONTS-FOOTER-001`
- Task title: `Theme Builder Lite - Phase 1 Implementation (Custom Fonts + Footer Template)`
- Domain: `Theme Builder Lite / Global Layout / Elementor Integration`
- Tier classification: `1`
- Authority surface touched: `No`
- Data integrity risk: `Low-Medium`
- ADR required: `No`

### 2) Scope Declaration
- In scope:
  - Feature-flagged custom fonts storage + frontend `@font-face` output
  - `bw_template` CPT + footer template type
  - Active footer template selector in admin
  - Footer runtime rendering with fail-open fallback
- Out of scope:
  - Single product rendering changes
  - Woo hooks changes for product rendering
  - Header runtime changes

### 3) Determinism
- Fonts output is deterministic from `bw_custom_fonts_v1` option snapshot.
- Footer output is deterministic from one active template id (`bw_theme_builder_lite_footer_v1`).
- If active template is invalid/unpublished/missing -> runtime falls back to theme footer.

## Phase 1 Architecture (Implemented)

## A) Module Layout
- `includes/modules/theme-builder-lite/theme-builder-lite-module.php`
- `includes/modules/theme-builder-lite/config/feature-flags.php`
- `includes/modules/theme-builder-lite/cpt/template-cpt.php`
- `includes/modules/theme-builder-lite/cpt/template-meta.php`
- `includes/modules/theme-builder-lite/fonts/custom-fonts.php`
- `includes/modules/theme-builder-lite/runtime/footer-runtime.php`
- `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.php`
- `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.js`

Bootstrap wiring:
- Loaded from `blackwork-core-plugin.php`.

## B) Feature Flags
Option key: `bw_theme_builder_lite_flags`
- `enabled` (master)
- `custom_fonts_enabled`
- `footer_override_enabled`
- `templates_enabled`

Behavior:
- If master flag is off, runtime output is disabled.
- Sub-flags gate fonts, footer, and Phase 2 template resolver independently.

## Single Product Conditions (Settings-Driven MVP)
- Quick Edit condition controls for `bw_template` are removed by design (stability hardening).
- Single Product conditions are configured in `Theme Builder Lite` admin tab: `Single Product`.
- Dedicated option key: `bw_theme_builder_lite_single_product_v1`
  - `enabled`
  - `active_single_product_template_id`
  - `include_product_cat` (parent `product_cat` IDs only)
  - `exclude_product_cat` (parent `product_cat` IDs only)
- Runtime precedence contract:
  - when this option is `enabled=1`, it is authoritative for `is_product()` resolution
  - `exclude` evaluates first
  - empty `include` = match-all
  - invalid/missing template or no-match => fail-open to theme template
- Backward compatibility:
  - legacy `bw_tbl_display_rules_v1` meta is retained in DB and can be migrated later
  - legacy data is not mutated by this settings-driven path

### 2026-03 Update - Elementor Preview Product Bridge Contract
- Elementor preview iframe does not reliably expose `elementor-preview` parameter inside widget render callbacks.
- Theme Builder Lite sets a preview bridge during preview context bootstrap:
  - global: `$GLOBALS['bw_tbl_preview_product_id']`
  - query var: `bw_tbl_preview_product_id`
- BW product-dependent widgets must resolve product context through shared resolver `bw_tbl_resolve_product_context_id()` (not by direct `$_GET` checks in render methods).
- Resolver order for preview fallback is deterministic:
  - bridge global -> bridge query var -> saved preview option.
- This avoids `WP_Query` spoofing and keeps frontend behavior unchanged.

### 2026-03 Operational Note
- `WP_Scripts::add ... slick-js not registered` notice is unrelated to Theme Builder Lite preview bridge/product-context resolution and should be handled in a separate asset-dependency task.

## C) Custom Fonts (Implemented)
Storage option: `bw_custom_fonts_v1`
- `version`
- `fonts[]` entries:
  - `font_family`
  - `sources.woff2`
  - `sources.woff`
  - `font_weight`
  - `font_style`

Security and validation:
- Upload mimes allow only `woff2`/`woff` for admins.
- Filetype/ext corrected through `wp_check_filetype_and_ext` filter.
- URLs sanitized and accepted only when they resolve to WP media attachments.
- MIME + extension validation enforced for each source.

Frontend behavior:
- `@font-face` CSS generated and enqueued only when:
  - master flag is enabled,
  - `custom_fonts_enabled` is enabled,
  - at least one valid font source exists.

### Custom Fonts - Elementor Typography Integration Contract
- Activation conditions:
  - `did_action('elementor/loaded')` is true
  - `bw_theme_builder_lite_flags[enabled] = 1`
  - `bw_theme_builder_lite_flags[custom_fonts_enabled] = 1`
- Data source:
  - Only `bw_custom_fonts_v1` snapshot
  - Only valid entries (attachment-backed source + mime/ext valid)
  - `font_family` values are deduplicated before injection
- UI mapping:
  - Injected via `elementor/fonts/additional_fonts` with priority `20`
  - Group key registered via `elementor/fonts/groups` as `Custom Fonts`
  - Dropdown label matches the exact stored `font_family` string
- Fail-open:
  - If Elementor is absent/not loaded, flags are off, or snapshot is invalid/empty, Elementor font list is returned unchanged.

### Custom Fonts - Editor Preview Enqueue Contract
- CSS builder is shared between frontend and editor contexts (single deterministic builder path).
- Editor hooks:
  - `elementor/editor/after_enqueue_styles`
  - `elementor/preview/enqueue_styles`
- Guards:
  - same feature flags as frontend
  - no enqueue when CSS output is empty
- Isolation:
  - no global wp-admin enqueue; styles are attached only through Elementor editor/preview hooks.

## D) Footer Template (Implemented)
CPT: `bw_template`
- Registered in admin under Blackwork Site menu.
- Elementor Free support added via `elementor/cpt_support` filter.
- Elementor support is also enforced via `elementor_cpt_support` option sync (`admin_init`) to avoid manual Elementor settings steps.
- Previewability for Elementor editor is enabled (`publicly_queryable=true`, rewrite slug `bw-template`, `show_in_rest=true`, `exclude_from_search=true`, `has_archive=false`).

Template type:
- Meta key `bw_template_type`
- Phase 1 allowed value: `footer`

Active footer selector:
- Option key: `bw_theme_builder_lite_footer_v1`
- Field: `active_footer_template_id` (single active footer)

Runtime behavior:
- If feature flag on and active template is valid (`publish`, type `footer`):
  - attempts to suppress known theme footer callback (Hello Elementor)
  - applies conservative fallback CSS for common theme footer selectors
  - renders Elementor content in `wp_footer`
- `bw_template` single requests use a dedicated preview template include path and are marked `noindex/nofollow/noarchive` via `wp_robots`.
- Fail-open:
  - invalid/missing template -> no override
  - render failure -> no override
  - theme footer remains the default fallback

### Footer Override - Final Rendering Contract
- Activation conditions:
  - Master flag `bw_theme_builder_lite_flags[enabled] = 1`
  - Footer flag `bw_theme_builder_lite_flags[footer_override_enabled] = 1`
  - Active footer template id resolves to a published `bw_template` with `bw_template_type=footer`
  - Request is frontend and not admin/ajax/feed/embed, not `is_singular('bw_template')`, and not Elementor editor/preview request.
- Rendering priority:
  - Runtime resolves a single deterministic active template id.
  - Elementor builder render (`get_builder_content_for_display`) is attempted first.
  - If Elementor render is empty/unavailable, classic `the_content` fallback is attempted.
- Preview safeguards:
  - Footer override is bypassed on Elementor editor/preview requests and on `bw_template` singular preview pages.
  - `bw_template` preview path returns normal frontend context (200 + WP head/footer hooks) through dedicated template include wiring.
- Fail-open invariant:
  - Any invalid template state, missing content, runtime exception, or guard mismatch returns control to theme footer with no hard failure.

## E) Explicit Non-Goals (Phase 1)
- No single product override logic.
- No `template_include` global interception.
- No WooCommerce template resolver changes.
- No header system modifications.

## F) Phase 2 Step 1 - Resolver Skeleton (Implemented)
Supported template contexts in this step:
- `single_post` (`is_singular('post')`)
- `single_page` (`is_page()`)
- `search` (`is_search()`)
- `error_404` (`is_404()`)

Resolver contract:
- Hook: `template_include` (priority `50`)
- Strict bypasses:
  - admin/ajax/feed/embed
  - `is_singular('bw_template')`
  - Elementor editor/preview requests
  - Woo safety list: `is_cart()`, `is_checkout()`, `is_account_page()`, `is_wc_endpoint_url()`
- Candidate selection:
  - `bw_template` + `publish` + matching `bw_template_type`
  - Step 2: candidates are filtered by conditions engine before winner selection
- Winner selection:
  - highest `bw_template_priority` first (default `10`)
  - tie-break: lowest template id
- Rendering:
  - Elementor builder output first
  - fallback to classic `the_content`
- Fail-open invariant:
  - if no winner / invalid winner / empty render / wrapper missing/error -> return original theme template unchanged.

### Phase 2 Step 2 - Conditions Engine Contract (Implemented)
- Storage meta key:
  - `bw_tbl_display_rules_v1` with normalized shape:
    - `include` => list of rules
    - `exclude` => list of rules
- Evaluation order:
  1) Exclude rules first: any match disqualifies template
  2) Include rules second:
     - if no applicable include rules => match-all in current template context
     - else at least one include rule must match
- Supported rule types in current implementation:
  - `single_post`:
    - `post_category` (term IDs)
    - `post_id` (post IDs)
  - `single_page`:
    - `page_id` (page IDs)
  - `search`, `error_404`:
    - no applicable include/exclude rule types (match-all by contract)
- Fail-open behavior:
  - missing/invalid/unparseable rules meta normalizes to empty include/exclude and does not hard-fail resolver flow.

### Phase 2 Step 3 - Display Rules Admin Contract (Implemented)
- Metabox scope:
  - `bw_template` edit screen only
- Fields:
  - `Priority` number input (`bw_template_priority`)
  - `Include Rules` table
  - `Exclude Rules` table
- Supported rule types in UI:
  - `post_category` (category term IDs)
  - `post_id` (post IDs)
  - `page_id` (page IDs)
- Persistence:
  - `bw_tbl_display_rules_v1` always saved with both keys:
    - `include` => array
    - `exclude` => array
  - Unknown rule types and invalid/non-positive IDs are dropped
  - IDs are deduplicated and sorted ascending before save
- Safety:
  - nonce + capability checks on save
  - empty/invalid input normalizes to empty arrays (fail-open with resolver contract).

### Admin UX - BW Templates List Table (Implemented)
- `bw_template` list table shows additional informative columns:
  - `Type` (label from `bw_template_type`)
  - `Priority` (`bw_template_priority`, default `10`)
  - `Applies To` (summary from `bw_tbl_display_rules_v1`, include/exclude with concise truncation)
- Optional type filter dropdown is available above the list table (`All Types` + specific template types).
- Quick Edit custom conditions are removed (core inline edit only).
- The `bw_template` post edit screen remains guidance-only for conditions.
- Single Product conditions are configured from Theme Builder Lite settings tab `Single Product`.
- Legacy storage schema `bw_tbl_display_rules_v1` remains unchanged in DB for backward compatibility.
- UI Contract (2026-03):
  - `Blackwork Site > All Templates` keeps WordPress native list table mechanics (`WP_List_Table`) intact.
  - UI layer is wrapper/skin only: `.bw-admin-root` shell, action bar, responsive table wrapper, and pill styling.
  - No changes to filters, bulk actions, search, sorting, pagination, row actions, URLs, nonce, or query behavior.

### 2026-03 Update - Single Product Settings Authority (v2 Repeater)
- Authoritative single-product conditions surface is the Theme Builder Lite settings tab (`Single Product`); Quick Edit is not an authority surface.
- New option snapshot: `bw_theme_builder_lite_single_product_rules_v2`
  - `enabled` (bool)
  - `rules[]`:
    - `template_id` (published `bw_template`, type `single_product`)
    - `include_product_cat[]` (parent `product_cat` term IDs only)
    - `exclude_product_cat[]` (parent `product_cat` term IDs only)
- Backward compatibility:
  - `bw_theme_builder_lite_single_product_v1` is preserved as legacy compatibility source.
  - If v2 is absent, resolver derives effective v2 payload from v1 without destructive migration.
- Deterministic evaluation contract:
  - Rules evaluate top-to-bottom.
  - Exclude-first, include-second.
  - First matching valid rule wins.
  - Include empty means match-all.
- Parent-only UI + ancestor runtime match:
  - UI shows only parent product categories (`parent=0`), flat checklist.
  - Runtime matches product terms including ancestors so parent rules match child-assigned products.
- Status summary behavior:
  - Single Product tab shows enabled state, rules count, and active template count.
  - Warning shown when enabled but no valid linked template exists.
- List UX behavior:
  - Badges: `Applies to: Footer`, `Applies to: Single Product`, `Applies to: Product Archive`, `Not linked`.
  - Inline Type dropdown on list table autosaves with nonce/capability checks.
  - Linked templates require confirmation before type mutation.
  - Invalid/unauthorized type mutations are rejected; linkage invalidation surfaces safely as `Not linked`.

### 2026-03 Update - Product Archive Settings Authority (v2 Repeater)
- Authoritative product-archive conditions surface is Theme Builder Lite settings tab `Product Archive`; Quick Edit is not an authority surface.
- New option snapshot: `bw_theme_builder_lite_product_archive_rules_v2`
  - `enabled` (bool)
  - `rules[]`:
    - `template_id` (published `bw_template`, type `product_archive`)
    - `include_product_cat[]` (parent `product_cat` IDs only)
    - `exclude_product_cat[]` (parent `product_cat` IDs only)
- UI contract:
  - Status summary (enabled/disabled, rule count, active template count, warning when enabled with no valid linked templates)
  - Repeater with explicit include mode (`all` vs `selected`)
  - Optional exclude toggle (`Enable exclusions`)
  - Parent-only flat product category checklist (no subcategories rendered)
- Runtime contract for product category archives:
  - Settings branch runs only for `product_cat` archive context.
  - Rules evaluate top-to-bottom, exclude-first.
  - Include empty means match-all.
  - First matching valid rule wins.
  - Archive term matching uses current term + ancestors so parent rules match child category archives.
- Fail-open:
  - Disabled/no match/invalid template => resolver returns original theme/Woo template path unchanged.
- Admin list UX:
  - Template row shows `Applies to: Product Archive` when linked by any enabled Product Archive rule.
  - `Not linked` remains true only when template is not referenced by active Footer/Single Product/Product Archive settings surfaces.

### 2026-03 Update - Import Template Tab (Elementor JSON -> bw_template)
- New admin tab: `Import Template` inside Theme Builder Lite settings.
- Upload contract:
  - accepts `.json` only
  - nonce-protected submission
  - capability gate: `manage_options`
  - size limited by filterable cap (default 8 MB)
- Import validation contract:
  - payload must be valid JSON object
  - must include Elementor content structure (`content[]` or `elements[]`)
  - template type is auto-detected from JSON payload (`type`/`template_type`/`doc_type`) with deterministic widget-heuristic fallback
  - no manual type override field is exposed in UI
- Type mapping contract:
  - `product-archive` -> `product_archive`
  - `single-product` -> `single_product`
  - `product` -> `single_product`
  - additional allowed BW types supported through same map/enum validation
  - if still unmappable, importer applies safe fallback type `single_page` and keeps post as `draft` with explicit admin notice
- Persistence contract:
  - creates `bw_template` post as `draft`
  - prefixes title as `Imported — {Original Title}`
  - sets `bw_template_type` to validated mapped type
  - marks imported record with `bw_tbl_imported=1`
  - writes required Elementor meta:
    - `_elementor_data`
    - `_elementor_edit_mode` (`builder`)
    - `_elementor_version`
    - `_elementor_page_settings`
    - `_elementor_template_type`
  - creates mirrored `elementor_library` post to ensure imported templates are discoverable in Elementor library popup
  - stores linkage between imported `bw_template` and mirrored library template via meta
- Fail-open + integrity:
  - on any validation/import error: no partial template kept
  - on metadata or library-mirror failure: created draft is deleted
  - importer reports explicit admin error notice and exits safely
- History:
  - tab renders deterministic `Recent Imports` list (last 10 by `bw_tbl_imported=1`) with title, detected type, date/time, and edit link
- Limitations:
  - no conversion of Elementor Pro widgets to free equivalents
  - no batch/zip import
  - media asset remapping is out of scope

## Extending Theme Builder Lite to New Contexts
Reusable extension pattern (2026-03):

1. Storage schema
- Create a versioned option snapshot per context (`*_vN`).
- Keep shape deterministic with explicit booleans and normalized arrays.
- Preserve previous version for compatibility fallback; avoid destructive migration in runtime path.

2. Admin UI pattern
- Use settings-tab authority for stable persistence.
- Repeater rows for multi-rule contexts.
- Explicit toggles for optional behavior (e.g., include mode, exclude enable).
- Keep UI fail-open: if JS fails, fields remain editable.

### Settings Page UI Contract (2026-03)
- `Blackwork Site > Theme Builder Lite` adopts the shared Blackwork Admin UI Kit (`admin/css/bw-admin-ui-kit.css`).
- Pattern: `.bw-admin-root` shell, page header + subtitle, top action bar with primary save CTA, `Sections` card with pill tabs, and card-grouped tab panels.
- Constraints:
  - UI-only styling alignment; no changes to option keys/defaults/sanitizers/save handlers.
  - Styling scope remains under `.bw-admin-root`; Theme Builder Lite module CSS selectors are additionally scoped to `.bw-admin-root.bw-tbl-admin-wrap`.

3. Runtime branch pattern
- Gate by master + feature flags before resolver branch.
- Apply global + Woo safety bypass first.
- Build normalized context payload once.
- Evaluate rules deterministically (document order + explicit precedence).
- On mismatch/error/invalid render, fail-open to theme template.

4. Validation rules
- Validate IDs/capabilities/types at sanitize time.
- Restrict taxonomy selectors to intended subset (e.g., parent-only categories).
- Reject invalid AJAX payloads with nonce + capability + enum validation.

5. List UX expectations
- Reflect active linkage with explicit badges.
- Surface unlinked state clearly (`Not linked`).
- Any inline mutation that can break linkage must warn and remain safe on failure.

6. Minimal test checklist for each new context
- Save/reload persistence.
- Deterministic winner behavior.
- Include/exclude precedence.
- Guard/bypass confirmation.
- Badge truth reflection.
- Fail-open fallback verification.

Implemented example:
- `Product Archive` follows this exact pattern with option `bw_theme_builder_lite_product_archive_rules_v2`, parent-only UI, ancestor-aware runtime matching, and linkage badges in Templates list UX.
- `Import Template` follows the same governance pattern as a settings-driven authority surface: explicit validation, deterministic type mapping, strict capability/nonce gates, and fail-open rollback on import errors.

### Phase 2 Step 4 - Archive Contexts (Non-Woo) (Implemented)
- Resolver type mapping:
  - `archive` when request is `is_home()` or `is_archive()`, excluding Woo archive surfaces (`is_shop`, `is_product_taxonomy`, `is_post_type_archive('product')`)
- Archive sub-context payload passed to conditions engine:
  - `archive_kind` in `{blog, category, tag, post_type, generic}`
  - `archive_term_id` for category/tag
  - `archive_post_types[]` for post type archives (excluding `product` and `bw_template`)
- Supported archive rule types:
  - `archive_blog`
  - `archive_category` (category term IDs)
  - `archive_tag` (tag term IDs)
  - `archive_post_type` (post type names)
- Evaluation and precedence remain unchanged:
  - Exclude-first
  - Include empty => match-all within archive context
  - Winner: highest priority, tie-break by lowest template ID

### Phase 2 Step 5 - Woo Single Product (Implemented)
- Resolver type mapping:
  - `single_product` when request is Woo product singular (`is_product()`)
  - Woo endpoint safety bypass remains strict: `is_cart()`, `is_checkout()`, `is_account_page()`, `is_wc_endpoint_url()`
- Single product context payload passed to conditions engine:
  - `product_id`
  - `product_category_term_ids` (`product_cat`)
- Supported single product rule types:
  - `product_category` (product category term IDs)
  - `product_id` (specific product IDs)
- Product category contract (parent-only):
  - `product_category` conditions accept only parent `product_cat` terms (`parent=0`).
  - Subcategories are excluded from admin condition selectors.
  - Save normalization drops non-parent IDs.
  - Matching is direct term ID comparison on assigned product terms (no parent-chain traversal).
- Evaluation and precedence remain unchanged:
  - Exclude-first
  - Include empty => match-all within `single_product` context (Elementor-like “All Products” behavior)
  - Winner: highest priority, tie-break by lowest template ID
- Settings-surface precedence update:
  - When `bw_theme_builder_lite_single_product_v1[enabled]=1`, settings tab conditions are authoritative for `single_product`.
  - If settings do not match or configured template is invalid, resolver fails open to theme template.

### Phase 2 Step 6 - Woo Product Archive (Implemented)
- Resolver type mapping:
  - `product_archive` when request is Woo shop archive (`is_shop()` or `is_post_type_archive('product')`)
  - `product_archive` when request is product category/tag archive (`is_product_category()`, `is_product_tag()`)
  - Unknown Woo product taxonomies remain bypassed (`is_product_taxonomy()` fallback branch)
- Product archive context payload passed to conditions engine:
  - `product_archive_kind` in `{shop, product_cat, product_tag, generic}`
  - `product_archive_term_id` for taxonomy archives
- Supported product archive rule types:
  - `product_archive_shop`
  - `product_archive_category` (`product_cat` term IDs)
  - `product_archive_tag` (`product_tag` term IDs)
- Product category contract (parent-only):
  - `product_archive_category` accepts only parent `product_cat` terms (`parent=0`).
  - Subcategories are intentionally excluded from UI and matching.
- Evaluation and precedence remain unchanged:
  - Exclude-first
  - Include empty => match-all within `product_archive` context (Elementor-like “All Product Archives” behavior)
  - Winner: highest priority, tie-break by lowest template ID

## Rollback
1. Disable master flag `bw_theme_builder_lite_flags[enabled]`.
2. Optionally disable only one sub-feature:
   - `custom_fonts_enabled=0`
   - `footer_override_enabled=0`
3. Keep stored data for safe re-enable.

When disabled:
- Theme footer renders normally.
- No Theme Builder Lite fonts CSS is output.
