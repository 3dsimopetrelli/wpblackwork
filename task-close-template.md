# task-close-template.md

## 1) Task Identity
- Task ID: TBL-IMPORT-TEMPLATE-TAB-CLOSE
- Task Title: Theme Builder Lite — Import Template (Elementor JSON -> bw_template)
- Date Closed: 2026-03-03
- Owner: Simo
- System: Blackwork Core (plugin) — Theme Builder Lite

## 2) Governance Classification
- Domain(s): Theme Builder Lite / Admin Settings / Data Import / Elementor Integration
- Tier: 1 (admin upload + content creation surface)
- Authority surface touched: Yes
- Authority touch points:
  - Admin file upload (`admin-post.php` action)
  - `bw_template` CPT creation
  - Elementor metadata persistence (`_elementor_*`)
  - Elementor Library mirror entry creation (`elementor_library`)
- Data integrity risk: Medium
- Determinism status: Confirmed deterministic
- Undeclared authority surfaces: None
- Woo session/cart mutation: None (confirmed)

## 3) Scope Completed
1. Added `Import Template` tab under Theme Builder Lite settings.
2. Implemented nonce-protected JSON uploader with strict admin capability checks.
3. Implemented automatic type detection (no manual override UI).
4. Implemented safe fallback behavior for unmappable types:
   - import continues as `draft`
   - `bw_template_type` is set to fallback `single_page`
   - admin success notice explicitly states fallback type was used.
5. Imported template title prefix added: `Imported — {Original Title}`.
6. Added import marker meta: `bw_tbl_imported = 1`.
7. Added recent imports list (last 10) with title, detected type, date/time, and edit link.
8. Added Elementor Library mirror creation so imported templates appear in Elementor template library popup.
9. Added imported-meta repair path for older malformed `_elementor_page_settings` values.
10. Fail-open preserved: validation failures and metadata write failures do not keep partial posts.

## 4) Runtime Contract (Import Layer)
- Import runs only in admin context via `admin_post_bw_tbl_import_template`.
- Import does not hook into `template_include` and does not alter resolver behavior directly.
- Imported templates affect runtime only if later linked through existing Settings authority surfaces.
- Determinism:
  - JSON decoded once per request.
  - Type mapping uses local deterministic map + deterministic widget heuristic.
  - No external API calls.
- Fail-open behavior:
  - invalid upload / invalid JSON / missing content -> error notice + no post created.
  - metadata write failure -> created post is hard-deleted.
  - Elementor Library mirror creation failure -> created `bw_template` is hard-deleted.

## 5) Data Model / Keys / Constants
### CPT and Post Types
- Target CPT: `bw_template`
- Mirror post type: `elementor_library` (when available)

### Meta keys written on imported `bw_template`
- `bw_template_type`
- `bw_tbl_imported`
- `_elementor_data`
- `_elementor_edit_mode`
- `_elementor_version`
- `_elementor_page_settings`
- `_elementor_template_type`
- `bw_tbl_library_template_id` (link to mirror library item)

### Meta keys written on mirror `elementor_library`
- `_elementor_data`
- `_elementor_edit_mode`
- `_elementor_version`
- `_elementor_page_settings`
- `_elementor_template_type`
- `bw_tbl_imported`
- `bw_tbl_source_bw_template_id`

### Option keys introduced
- None (importer uses admin action + post meta only)

### Constants introduced
- None

## 6) Hooks / AJAX / Nonce / Capability
### Hooks used
- `admin_post_bw_tbl_import_template` -> import handler
- Tab render invoked from Theme Builder Lite admin settings page render pipeline

### AJAX endpoints
- None added for this task

### Nonce and capability
- Nonce action: `bw_tbl_import_template`
- Capability check: `manage_options`

### Upload validation
- Extension: `.json` only
- MIME/ext verification via `wp_check_filetype_and_ext`
- Max size: filterable through `bw_tbl_import_template_max_size` (default 8MB)

## 7) CSS Selectors and JS Handlers
- No dedicated new importer JS handlers were required.
- Existing tab router JS was extended to include `import-template` tab key.
- Import flow is server-post based and deterministic (no client-side serialization logic).

## 8) Security and Integrity Controls
- Strict nonce verification before processing upload.
- Strict capability gate (`manage_options`).
- Upload sanity checks (`is_uploaded_file`, size, extension, MIME/ext).
- JSON decode checks with explicit error paths.
- Content structure guard (`content[]` or `elements[]` required).
- Strict post type creation target (`bw_template` only).
- No arbitrary post type mutation exposed.

## 9) Backward Compatibility
- Existing Theme Builder Lite options are untouched.
- Existing resolver logic and linked-rule settings remain unchanged.
- Legacy/non-imported templates continue to function without migration.
- Import history relies on additive marker `bw_tbl_imported` and does not alter existing non-imported records.

## 10) Acceptance Criteria Verification
1. Import tab visible under Theme Builder Lite: Verified.
2. Valid Elementor JSON creates `bw_template` draft: Verified.
3. Imported title is prefixed (`Imported — ...`): Verified.
4. Type detection is automatic (no manual override field): Verified.
5. Unmappable type falls back to draft + safe fallback type notice: Verified.
6. Invalid JSON shows clear error and creates no post: Verified.
7. Import history shows recent imports with edit links: Verified.
8. Elementor library popup visibility path implemented through mirror `elementor_library` post: Verified.

## 11) Invariants Verification
- No impact on Single Product/Product Archive/Footer resolvers: Verified.
- No new resolver branch added for import flow: Verified.
- No Woo cart/checkout/account mutation: Verified.
- No Quick Edit authority reintroduced: Verified.
- Determinism preserved: Verified.

## 12) Exact Files Modified
Code:
- `includes/modules/theme-builder-lite/admin/import-template.php` (new)
- `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.php`
- `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.js`
- `includes/modules/theme-builder-lite/theme-builder-lite-module.php`

Documentation (close execution):
- `task-close-template.md`
- `docs/30-features/theme-builder-lite/theme-builder-lite-spec.md`
- `docs/00-planning/decision-log.md`
- `docs/00-governance/risk-register.md`

## 13) Risk Register Updates (Applied)
- `R-TBL-14`: untrusted/malformed JSON import risk (kept active, mitigated by strict validation and rollback-on-failure).
- `R-TBL-15` (new): Elementor metadata/version compatibility drift risk for imported payloads.
- `R-TBL-16` (new): admin misuse/semantic mismatch risk when fallback type is auto-applied.

## 14) Decision Log Entries (Applied)
- Import Template authority resides in Theme Builder Lite settings tab.
- No manual type override field; type is auto-detected.
- If unmappable, importer applies safe fallback type and keeps draft status.
- Imported templates are prefixed with `Imported —` and flagged `bw_tbl_imported=1`.
- Import creates Elementor Library mirror for popup discoverability.
- Importer remains fail-open with no partial writes on failure.

## 15) Debug / Testing Notes
- Import result notice supports success/error diagnostics in admin UI.
- Repair helper normalizes malformed imported `_elementor_page_settings` when opening import tab/history.
- Manual tests run against:
  - valid mapped JSON
  - valid unmapped JSON (fallback)
  - invalid JSON payload
  - history rendering

## 16) Rollback Plan
1. Revert importer wiring by removing `admin/import-template.php` require from module bootstrap.
2. Remove `Import Template` tab panel render from Theme Builder Lite admin page.
3. Keep imported posts/data intact unless cleanup is explicitly requested.
4. If needed, filter/hide imported entries by `bw_tbl_imported` without deleting source templates.

## 17) Known Limitations
- No Elementor Pro -> Free widget conversion layer.
- No batch/zip import.
- No media remapping pipeline.
- Fallback type may require manual correction via existing template type controls.

## 18) Future Extension Surface
- Add explicit preflight diagnostics panel before commit.
- Add import dry-run mode.
- Add duplicate-content detection/hash to avoid repeated imports.
- Add optional mapping profiles for common external template exports.
- Add bulk import queue with per-file result ledger.

## 19) Documentation Patch Plan Executed
Updated:
- `docs/30-features/theme-builder-lite/theme-builder-lite-spec.md`
  - Import tab authority and flow
  - auto-detect + fallback behavior (no manual override)
  - Elementor library popup visibility contract
- `docs/00-planning/decision-log.md`
  - import authority and behavior decisions
- `docs/00-governance/risk-register.md`
  - import-related active risks and mitigations

Verification:
- Outdated references implying manual type override were corrected.
- No unrelated sections were removed.
- Authority model remains settings-driven and deterministic.

## 20) Manual Regression Checklist
1. Import valid mapped JSON (`product-archive`) -> draft created with prefixed title and expected type.
2. Import valid unmapped JSON -> draft created with fallback type notice.
3. Import invalid JSON -> error notice and no post creation.
4. Open Recent Imports -> row visible with detected type and edit action.
5. Open Elementor library popup -> imported mirror template discoverable.
6. Verify existing Single Product/Product Archive/Footer runtime behavior unchanged.
7. Verify Woo cart/checkout/account pages unaffected.
