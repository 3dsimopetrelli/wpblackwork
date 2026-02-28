# Theme Builder Lite - Runtime Hook Map (Phase 1 Actual)

## Purpose
Actual runtime hooks used by Phase 1 implementation.
Scope is limited to Custom Fonts and Footer Template.

## Hook Inventory

| Hook | Priority | Callback | File | Function |
|---|---:|---|---|---|
| `init` | 9 | `bw_tbl_register_template_cpt` | `includes/modules/theme-builder-lite/cpt/template-cpt.php` | Register `bw_template` CPT |
| `init` | 10 | `bw_tbl_register_template_type_meta` | `includes/modules/theme-builder-lite/cpt/template-meta.php` | Register `bw_template_type` post meta |
| `admin_init` | 10 (default) | `bw_tbl_register_admin_settings` | `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.php` | Register options and sanitizers |
| `admin_menu` | 21 | `bw_tbl_admin_menu` | `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.php` | Add Theme Builder Lite submenu |
| `admin_enqueue_scripts` | 10 (default) | `bw_tbl_admin_enqueue_assets` | `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.php` | Enqueue admin JS/media uploader |
| `add_meta_boxes` | 10 (default) | `bw_tbl_add_template_type_metabox` | `includes/modules/theme-builder-lite/cpt/template-meta.php` | Add template type metabox |
| `save_post_bw_template` | 10 (default) | `bw_tbl_save_template_type_metabox` | `includes/modules/theme-builder-lite/cpt/template-meta.php` | Persist template type |
| `wp_insert_post` | 10 (default) | `bw_tbl_default_template_type_on_insert` | `includes/modules/theme-builder-lite/cpt/template-meta.php` | Default type on first insert |
| `elementor/cpt_support` (filter) | 10 (default) | `bw_tbl_add_elementor_cpt_support` | `includes/modules/theme-builder-lite/cpt/template-cpt.php` | Enable Elementor Free editing for `bw_template` |
| `upload_mimes` (filter) | 10 (default) | `bw_tbl_allow_font_upload_mimes` | `includes/modules/theme-builder-lite/fonts/custom-fonts.php` | Allow WOFF/WOFF2 uploads for admins |
| `wp_check_filetype_and_ext` (filter) | 10 | `bw_tbl_fix_font_filetype_and_ext` | `includes/modules/theme-builder-lite/fonts/custom-fonts.php` | Normalize font file type detection |
| `wp_enqueue_scripts` | 20 | `bw_tbl_enqueue_custom_fonts_css` | `includes/modules/theme-builder-lite/fonts/custom-fonts.php` | Enqueue generated `@font-face` CSS |
| `wp` | 20 | `bw_tbl_prepare_footer_runtime` | `includes/modules/theme-builder-lite/runtime/footer-runtime.php` | Resolve active footer and remove known theme footer callback |
| `wp_head` | 99 | `bw_tbl_footer_theme_fallback_css` | `includes/modules/theme-builder-lite/runtime/footer-runtime.php` | Scoped CSS fallback to suppress theme footer |
| `wp_footer` | 20 | `bw_tbl_render_footer_template` | `includes/modules/theme-builder-lite/runtime/footer-runtime.php` | Render active Elementor footer template |

## Feature Flag Gates
Option: `bw_theme_builder_lite_flags`
- Master gate: `enabled`
- Fonts gate: `custom_fonts_enabled`
- Footer gate: `footer_override_enabled`

Runtime behavior:
- All frontend output paths are gated by these flags.
- If flag checks fail, hooks return without mutation/output.

## Fallback Contract

Footer:
- Active template must be `bw_template` + `publish` + `bw_template_type=footer`.
- If invalid/missing/error: no custom output, theme footer remains.

Fonts:
- At least one valid source is required.
- Invalid rows are skipped without blocking frontend.

## Explicitly Not Used in Phase 1
- `template_include`
- WooCommerce single product hooks
- Header runtime hooks
