# Theme Builder Lite (Elementor Free) - Specification

## Status
- Phase 1: Implemented
- Phase 2 Step 1: Implemented (resolver skeleton only)
- Scope delivered in Phase 1:
  - Custom Fonts module
  - Footer Template module
- Scope delivered in Phase 2 Step 1:
  - `template_include` resolver skeleton with strict bypass guards
  - Deterministic winner selection (`priority DESC`, `template_id ASC`)
  - Runtime wrapper render path (Elementor builder first, `the_content` fallback)
  - New feature flag: `templates_enabled`
- Out of scope (not implemented):
  - Single Product override (deferred to later Phase 2 step)
  - Condition engine include/exclude matrix (deferred to later Phase 2 step)
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
  - Step 1 default: no condition rows yet, so match-all within resolved type context
- Winner selection:
  - highest `bw_template_priority` first (default `10`)
  - tie-break: lowest template id
- Rendering:
  - Elementor builder output first
  - fallback to classic `the_content`
- Fail-open invariant:
  - if no winner / invalid winner / empty render / wrapper missing/error -> return original theme template unchanged.

## Rollback
1. Disable master flag `bw_theme_builder_lite_flags[enabled]`.
2. Optionally disable only one sub-feature:
   - `custom_fonts_enabled=0`
   - `footer_override_enabled=0`
3. Keep stored data for safe re-enable.

When disabled:
- Theme footer renders normally.
- No Theme Builder Lite fonts CSS is output.
