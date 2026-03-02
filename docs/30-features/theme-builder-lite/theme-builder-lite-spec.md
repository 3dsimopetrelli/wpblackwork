# Theme Builder Lite (Elementor Free) - Specification

## Status
- Phase 1: Implemented
- Phase 2 Step 1: Implemented (resolver skeleton only)
- Phase 2 Step 2: Implemented (conditions engine core, no UI yet)
- Phase 2 Step 3: Implemented (Display Rules metabox + deterministic persistence)
- Phase 2 Step 4: Implemented (archive non-Woo contexts + archive rules)
- Phase 2 Step 5: Implemented (Woo single product context + conditions)
- Phase 2 Step 6: Implemented (Woo product archive context + conditions)
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
  - Single Product-specific Include/Exclude controls in metabox with deterministic sanitize/save
- Scope delivered in Phase 2 Step 6:
  - Added `product_archive` template type
  - Resolver context mapping for Woo shop + product category/tag archives
  - Conditions engine support for `product_archive_shop`, `product_archive_category`, `product_archive_tag`
  - Product Archive-specific Include/Exclude controls in metabox with deterministic sanitize/save
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
- No runtime resolver behavior is changed by this admin UX enhancement.

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
- Evaluation and precedence remain unchanged:
  - Exclude-first
  - Include empty => match-all within `single_product` context (Elementor-like “All Products” behavior)
  - Winner: highest priority, tie-break by lowest template ID

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
