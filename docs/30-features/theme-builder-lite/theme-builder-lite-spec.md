# Theme Builder Lite (Elementor Free) - Specification

## Status
- Phase 1: Implemented
- Scope delivered in Phase 1:
  - Custom Fonts module
  - Footer Template module
- Out of scope (not implemented):
  - Single Product override
  - Condition engine include/exclude matrix
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

Behavior:
- If master flag is off, runtime output is disabled.
- Sub-flags gate fonts and footer independently.

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

## D) Footer Template (Implemented)
CPT: `bw_template`
- Registered in admin under Blackwork Site menu.
- Elementor Free support added via `elementor/cpt_support` filter.

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
- Fail-open:
  - invalid/missing template -> no override
  - render failure -> no override
  - theme footer remains the default fallback

## E) Explicit Non-Goals (Phase 1)
- No single product override logic.
- No `template_include` global interception.
- No WooCommerce template resolver changes.
- No header system modifications.

## Rollback
1. Disable master flag `bw_theme_builder_lite_flags[enabled]`.
2. Optionally disable only one sub-feature:
   - `custom_fonts_enabled=0`
   - `footer_override_enabled=0`
3. Keep stored data for safe re-enable.

When disabled:
- Theme footer renders normally.
- No Theme Builder Lite fonts CSS is output.
