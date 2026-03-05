# Blackwork Governance — Task Closure Template

## 1) Task Identification
- Task ID: `BW-TASK-20260305-24`
- Task title: Admin UX polish for Products list table when Media Folders sidebar is active
- Domain: Media Folders / Admin UX (Products list table)
- Tier classification: 1
- Implementation commit(s): `700533b`, `2ac8638`, `72bc225`, `4916a69`, `fb14db1`

### Commit Traceability

- Commit hash: `700533b`
- Commit message: `style(media-folders): polish product list columns and thumbnail sizing`
- Files impacted:
  - `includes/modules/media-folders/admin/media-folders-admin.php`
  - `includes/modules/media-folders/admin/assets/media-folders.css`
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `docs/20-development/admin-panel-map.md`

- Commit hash: `2ac8638`
- Commit message: `style(media-folders): tune product list columns and 130px thumb render`
- Files impacted:
  - `includes/modules/media-folders/admin/media-folders-admin.php`
  - `includes/modules/media-folders/admin/assets/media-folders.css`
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `docs/20-development/admin-panel-map.md`

- Commit hash: `72bc225`
- Commit message: `style(media-folders): tighten product columns and force 130px thumb square`
- Files impacted:
  - `includes/modules/media-folders/admin/assets/media-folders.css`

- Commit hash: `4916a69`
- Commit message: `style(media-folders): tighten product table columns and expand title column`
- Files impacted:
  - `includes/modules/media-folders/admin/assets/media-folders.css`

- Commit hash: `fb14db1`
- Commit message: `style(media-folders): add vertical gap between bulk actions and filters on products`
- Files impacted:
  - `includes/modules/media-folders/admin/assets/media-folders.css`

## 2) Implementation Summary

- Summary of UX change (Products list only):
  - Compact widths for drag-handle/checkbox/product image columns.
  - Product thumbnail source targeted to Woo/WP thumbnail size (`150x150`), rendered at compact square `130x130`.
  - Expanded `Name` column space for readability.
  - Added vertical spacing between bulk-actions row and filter row when wrapped.
- Modified files:
  - `includes/modules/media-folders/admin/media-folders-admin.php`
  - `includes/modules/media-folders/admin/assets/media-folders.css`
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `docs/20-development/admin-panel-map.md`
- Runtime surfaces touched:
  - Admin-only products list table presentation and thumbnail size filter callbacks.
- Hooks modified or registered:
  - Added Woo admin thumbnail-size filter callbacks in `media-folders-admin.php`.
- Database/data surfaces touched (if any):
  - None.

### Runtime Surface Diff

- New hooks registered:
  - `woocommerce_product_table_thumbnail_size`
  - `woocommerce_admin_product_list_table_image_size`
  - `woocommerce_product_list_table_thumbnail_size`
  all mapped to `bw_mf_filter_product_admin_thumbnail_size()`.
- Hook priorities modified:
  - None.
- Filters added or removed:
  - Added Woo product admin thumbnail sizing filters (no removals).
- AJAX endpoints added or modified:
  - None.
- Admin routes added or modified:
  - None.

### Exact guards/scope

All behavior is guarded to:
- `is_admin()`
- current screen is `edit.php?post_type=product` (`bw_mf_is_product_list_screen()`)
- Media Folders products support enabled (`bw_mf_is_post_type_enabled('product')`)

No changes apply to posts/pages/media list screens when these guards are not true.

### Woo filters used for thumbnail sizing

- `woocommerce_product_table_thumbnail_size`
- `woocommerce_admin_product_list_table_image_size`
- `woocommerce_product_list_table_thumbnail_size`

Callback returns square `[size, size]` where default is `150` (thumbnail source), overridable via:
- constant: `BW_MF_PRODUCT_ADMIN_THUMB_SIZE`
- filter: `bw_mf_product_admin_thumbnail_size`

### CSS scoping selectors used

Key selectors (products-only, Media Folders enabled):
- `body.edit-php.post-type-product.bw-mf-enabled .column-bw_mf_drag_handle`
- `body.edit-php.post-type-product.bw-mf-enabled .wp-list-table th.check-column`
- `body.edit-php.post-type-product.bw-mf-enabled .wp-list-table td.check-column`
- `body.edit-php.post-type-product.bw-mf-enabled .wp-list-table .column-thumb`
- `body.edit-php.post-type-product.bw-mf-enabled .wp-list-table .column-image`
- `body.edit-php.post-type-product.bw-mf-enabled .wp-list-table .column-thumb img`
- `body.edit-php.post-type-product.bw-mf-enabled .wp-list-table th.column-name`
- `body.edit-php.post-type-product.bw-mf-enabled .tablenav.top .actions.bulkactions`

## 3) Acceptance Criteria Verification

- Criterion 1 — Product drag-handle + checkbox minimal fixed widths: PASS
- Criterion 2 — Product thumbnail sizing (source 150x150, render 130x130): PASS
- Criterion 3 — Scope limited to Products list + products-enabled guard: PASS
- Criterion 4 — Existing UX contracts preserved (isolation, single-item drag, title ghost): PASS

### Testing Evidence

- Local testing performed: Yes (UI verification via provided screenshots + DOM/CSS inspection).
- Environment used: wp-admin products list with Media Folders sidebar active.
- Screenshots / logs:
  - Verified CSS application on `post-type-product` body class with `bw-mf-enabled`.
  - Verified previous `max-height: 40px` clamp overridden for 130x130 image display.
- Edge cases tested:
  - Products toolbar wrap spacing fixed (`bulk actions` vs filters row).
  - Column widths tightened without changing drag behavior.

PASS/FAIL evidence notes (4 checks requested):
1. Product-only scope and guards: PASS
2. Thumbnail size control and 130x130 visual render: PASS
3. Column spacing compaction (drag/check/image + wider title): PASS
4. UX contract unchanged (single-item drag, title ghost, isolation): PASS

## 4) Regression Surface Verification

- Surface name: Products list table UI
  - Verification performed: column sizing + thumbnail render + toolbar spacing
  - Result (PASS / FAIL): PASS
- Surface name: Media Folders DnD contract
  - Verification performed: no JS/AJAX contract changes
  - Result (PASS / FAIL): PASS
- Surface name: Other list screens (posts/pages/media)
  - Verification performed: selector and hook guards are product-only
  - Result (PASS / FAIL): PASS

## 5) Determinism Verification
- Input/output determinism verified? (Yes/No): Yes
- Ordering determinism verified? (Yes/No): Yes
- Retry/re-entry convergence verified? (Yes/No): Yes

## 6) Documentation Alignment Verification

- `docs/00-governance/`
  - Impacted? (Yes/No): No
  - Documents updated: N/A
- `docs/00-planning/`
  - Impacted? (Yes/No): No
  - Documents updated: N/A
- `docs/10-architecture/`
  - Impacted? (Yes/No): No
  - Documents updated: N/A
- `docs/20-development/`
  - Impacted? (Yes/No): Yes
  - Documents updated: `docs/20-development/admin-panel-map.md`
- `docs/30-features/`
  - Impacted? (Yes/No): Yes
  - Documents updated: `docs/30-features/media-folders/media-folders-module-spec.md`
- `docs/40-integrations/`
  - Impacted? (Yes/No): No
  - Documents updated: N/A
- `docs/50-ops/`
  - Impacted? (Yes/No): No
  - Documents updated: N/A
- `docs/60-adr/`
  - Impacted? (Yes/No): No
  - Documents updated: N/A
- `docs/60-system/`
  - Impacted? (Yes/No): No
  - Documents updated: N/A

## 7) Governance Artifact Updates
- Roadmap updated? (`docs/00-planning/core-evolution-plan.md`): No
- Decision log updated? (`docs/00-planning/decision-log.md`): No
- Risk register updated? (`docs/00-governance/risk-register.md`): No
- Runtime hook map updated? (`docs/50-ops/runtime-hook-map.md`): No
- Feature documentation updated? (`docs/30-features/...`): Yes

## 8) Final Integrity Check
Confirm:
- No authority drift introduced
- No new truth surface created
- No invariant broken
- No undocumented runtime hook change

- Integrity verification status: PASS

## Rollback Safety

- Can the change be reverted via commit revert? (Yes / No): Yes
- Database migration involved? (Yes / No): No
- Manual rollback steps required?
  - Revert task commits listed above
  - or disable products integration via `bw_core_flags['media_folders_use_products']=0`

## Post-Closure Monitoring

- Monitoring required: Yes
- Surfaces to monitor:
  - product list table layout under Woo admin updates
  - thumbnail sizing filter compatibility across Woo versions
- Monitoring duration: 1 release cycle

## 9) Closure Declaration
- Task closure status: CLOSED
- Responsible reviewer: Codex
- Date: 2026-03-05
