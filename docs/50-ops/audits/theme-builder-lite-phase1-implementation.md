# Theme Builder Lite - Phase 1 Implementation Audit

## 1) Scope Delivered
Phase 1 includes only:
- Custom Fonts
- Footer Template

Excluded from Phase 1:
- Single product template override
- Condition engine include/exclude resolver
- Woo template takeover

## 2) File Inventory

### Core bootstrap
- `blackwork-core-plugin.php`

### Theme Builder Lite module
- `includes/modules/theme-builder-lite/theme-builder-lite-module.php`
- `includes/modules/theme-builder-lite/config/feature-flags.php`
- `includes/modules/theme-builder-lite/cpt/template-cpt.php`
- `includes/modules/theme-builder-lite/cpt/template-meta.php`
- `includes/modules/theme-builder-lite/fonts/custom-fonts.php`
- `includes/modules/theme-builder-lite/runtime/footer-runtime.php`
- `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.php`
- `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.js`

### Documentation
- `docs/30-features/theme-builder-lite/theme-builder-lite-spec.md`
- `docs/10-architecture/theme-builder-lite/runtime-hook-map.md`
- `docs/50-ops/audits/theme-builder-lite-phase1-implementation.md`

## 3) Options and Meta Keys Introduced

### Options
- `bw_theme_builder_lite_flags`
  - `enabled`
  - `custom_fonts_enabled`
  - `footer_override_enabled`

- `bw_custom_fonts_v1`
  - `version`
  - `fonts[]`
    - `font_family`
    - `sources.woff2`
    - `sources.woff`
    - `font_weight`
    - `font_style`

- `bw_theme_builder_lite_footer_v1`
  - `version`
  - `active_footer_template_id`

### Post Type and Meta
- CPT: `bw_template`
- Post meta: `bw_template_type` (Phase 1 value: `footer`)

## 4) Feature Flags
- Master switch: `bw_theme_builder_lite_flags[enabled]`
- Fonts switch: `bw_theme_builder_lite_flags[custom_fonts_enabled]`
- Footer switch: `bw_theme_builder_lite_flags[footer_override_enabled]`

## 5) Rollback Steps
1. Open `Blackwork Site -> Theme Builder Lite`.
2. Disable `Enable Theme Builder Lite` (master switch).
3. Save settings.
4. Verify frontend:
   - Theme footer is visible again.
   - No custom `@font-face` output from Theme Builder Lite.
5. Optional partial rollback:
   - disable only `Custom Fonts` or only `Footer Override`.

## 6) Test Checklist

### Admin checks
- [ ] Theme Builder Lite submenu is visible under Blackwork Site.
- [ ] Fonts rows can be added/removed and media picker selects WOFF2/WOFF URLs.
- [ ] Invalid/non-font sources are dropped after save.
- [ ] `bw_template` CPT appears and template type metabox is present.
- [ ] Active footer dropdown lists published `bw_template` posts with type `footer`.

### Frontend checks
- [ ] With flags off, no footer override and no custom fonts output.
- [ ] With fonts enabled + valid sources, `@font-face` CSS is present.
- [ ] With footer override enabled + valid active template, Elementor footer content renders.
- [ ] With missing/invalid active template, theme footer remains (fail-open).
- [ ] Header behavior remains unchanged.
