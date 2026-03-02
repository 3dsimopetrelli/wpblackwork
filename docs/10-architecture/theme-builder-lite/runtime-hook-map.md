# Theme Builder Lite - Runtime Hook Map (Phase 1 + Phase 2 Step 4)

## Purpose
Actual runtime hooks used by implemented Theme Builder Lite surfaces.
Current scope includes:
- Phase 1: Custom Fonts + Footer Template
- Phase 2 Step 4: resolver for `single_post`, `single_page`, `archive` (non-Woo), `search`, `error_404`

## Hook Inventory

| Hook | Priority | Callback | File | Function |
|---|---:|---|---|---|
| `init` | 9 | `bw_tbl_register_template_cpt` | `includes/modules/theme-builder-lite/cpt/template-cpt.php` | Register `bw_template` CPT |
| `init` | 10 | `bw_tbl_register_template_type_meta` | `includes/modules/theme-builder-lite/cpt/template-meta.php` | Register `bw_template_type` post meta |
| `plugins_loaded` | 20 | `bw_tbl_bootstrap_elementor_fonts_integration` | `includes/modules/theme-builder-lite/integrations/elementor-fonts.php` | Register immediately if Elementor already loaded, otherwise defer registration |
| `elementor/loaded` | 20 | `bw_tbl_register_elementor_fonts_integration` | `includes/modules/theme-builder-lite/integrations/elementor-fonts.php` | Deferred registration path when Elementor loads after bootstrap check |
| `admin_init` | 10 (default) | `bw_tbl_register_admin_settings` | `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.php` | Register options and sanitizers |
| `admin_init` | 20 | `bw_tbl_ensure_elementor_cpt_support_option` | `includes/modules/theme-builder-lite/cpt/template-cpt.php` | Persist `bw_template` in `elementor_cpt_support` option |
| `admin_init` | 30 | `bw_tbl_maybe_flush_template_rewrite_rules` | `includes/modules/theme-builder-lite/cpt/template-cpt.php` | One-time rewrite flush for `bw_template` preview URLs |
| `admin_menu` | 21 | `bw_tbl_admin_menu` | `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.php` | Add Theme Builder Lite submenu |
| `admin_enqueue_scripts` | 10 (default) | `bw_tbl_admin_enqueue_assets` | `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.php` | Enqueue admin JS/media uploader |
| `add_meta_boxes` | 10 (default) | `bw_tbl_add_template_type_metabox` | `includes/modules/theme-builder-lite/cpt/template-meta.php` | Add template type metabox |
| `save_post_bw_template` | 10 (default) | `bw_tbl_save_template_type_metabox` | `includes/modules/theme-builder-lite/cpt/template-meta.php` | Persist template type |
| `wp_insert_post` | 10 (default) | `bw_tbl_default_template_type_on_insert` | `includes/modules/theme-builder-lite/cpt/template-meta.php` | Default type on first insert |
| `elementor/cpt_support` (filter) | 10 (default) | `bw_tbl_add_elementor_cpt_support` | `includes/modules/theme-builder-lite/cpt/template-cpt.php` | Enable Elementor Free editing for `bw_template` |
| `elementor/fonts/groups` (filter) | 20 | `bw_tbl_elementor_fonts_groups` | `includes/modules/theme-builder-lite/integrations/elementor-fonts.php` | Register `Custom Fonts` group in typography family control |
| `elementor/fonts/additional_fonts` (filter) | 20 | `bw_tbl_elementor_additional_fonts` | `includes/modules/theme-builder-lite/integrations/elementor-fonts.php` | Inject families from `bw_custom_fonts_v1` into Elementor typography list |
| `elementor/editor/after_enqueue_styles` | 20 | `bw_tbl_enqueue_custom_fonts_css` | `includes/modules/theme-builder-lite/integrations/elementor-fonts.php` | Enqueue `@font-face` CSS in Elementor editor context |
| `elementor/preview/enqueue_styles` | 20 | `bw_tbl_enqueue_custom_fonts_css` | `includes/modules/theme-builder-lite/integrations/elementor-fonts.php` | Enqueue same `@font-face` CSS in Elementor preview iframe |
| `update_option_bw_theme_builder_lite_flags` | 10 (default) | `bw_tbl_ensure_elementor_cpt_support_option` | `includes/modules/theme-builder-lite/cpt/template-cpt.php` | Re-assert Elementor CPT support after flags save |
| `upload_mimes` (filter) | 10 (default) | `bw_tbl_allow_font_upload_mimes` | `includes/modules/theme-builder-lite/fonts/custom-fonts.php` | Allow WOFF/WOFF2 uploads for admins |
| `wp_check_filetype_and_ext` (filter) | 10 | `bw_tbl_fix_font_filetype_and_ext` | `includes/modules/theme-builder-lite/fonts/custom-fonts.php` | Normalize font file type detection |
| `template_include` (filter) | 99 | `bw_tbl_include_single_template_preview` | `includes/modules/theme-builder-lite/runtime/template-preview.php` | Route `bw_template` singular to safe preview template |
| `template_include` (filter) | 50 | `bw_tbl_runtime_resolve_template_include` | `includes/modules/theme-builder-lite/runtime/template-resolver.php` | Phase 2 resolver with strict guards, conditions filtering, and deterministic winner selection |
| `wp_robots` (filter) | 10 (default) | `bw_tbl_add_noindex_for_bw_template` | `includes/modules/theme-builder-lite/runtime/template-preview.php` | Enforce noindex for public preview URLs |
| `wp_enqueue_scripts` | 20 | `bw_tbl_enqueue_custom_fonts_css` | `includes/modules/theme-builder-lite/fonts/custom-fonts.php` | Enqueue generated `@font-face` CSS |
| `wp` | 20 | `bw_tbl_prepare_footer_runtime` | `includes/modules/theme-builder-lite/runtime/footer-runtime.php` | Resolve active footer and remove known theme footer callback |
| `wp_head` | 99 | `bw_tbl_footer_theme_fallback_css` | `includes/modules/theme-builder-lite/runtime/footer-runtime.php` | Scoped CSS fallback to suppress theme footer |
| `wp_footer` | 20 | `bw_tbl_render_footer_template` | `includes/modules/theme-builder-lite/runtime/footer-runtime.php` | Render active Elementor footer template |

## Feature Flag Gates
Option: `bw_theme_builder_lite_flags`
- Master gate: `enabled`
- Fonts gate: `custom_fonts_enabled`
- Footer gate: `footer_override_enabled`
- Templates gate: `templates_enabled`

Runtime behavior:
- All frontend output paths are gated by these flags.
- If flag checks fail, hooks return without mutation/output.
- Admin assets are scoped to `blackwork-site-settings_page_bw-theme-builder-lite-settings` only and are not loaded on Elementor editor routes.
- Elementor font integration is soft-dependent and executes only after `elementor/loaded`; otherwise callbacks no-op.
- Phase 2 resolver bypasses: admin/ajax/feed/embed, `is_singular('bw_template')`, Elementor editor/preview, Woo safety endpoints (`is_cart`, `is_checkout`, `is_account_page`, `is_wc_endpoint_url`), and Woo archive contexts (`is_shop`, `is_product_taxonomy`, `is_post_type_archive('product')`).

## Fallback Contract

Footer:
- Active template must be `bw_template` + `publish` + `bw_template_type=footer`.
- Runtime bypasses override on Elementor editor/preview requests and on `is_singular('bw_template')`.
- If invalid/missing/error: no custom output, theme footer remains.

Fonts:
- At least one valid source is required.
- Invalid rows are skipped without blocking frontend.

## Explicitly Not Used in Current Implementation
- WooCommerce single product hooks
- Header runtime hooks
