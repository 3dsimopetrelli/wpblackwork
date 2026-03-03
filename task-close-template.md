# task-close-template.md

## 1) Task Identity
- Task ID: TBL-SINGLE-PRODUCT-RULES-V2-008
- Task Title: Theme Builder Lite – Single Product rules repeater v2 + Quick Edit removal + List Type dropdown
- Date Closed: 2026-03-03
- Owner: Simo
- System: Blackwork Core Plugin (`wpblackwork`) — Theme Builder Lite module

## 2) Governance Classification
- Domain(s): Theme Builder Lite / Admin Configuration / Runtime Resolver / Woo Single Product / Admin List UX
- Tier classification: 1 (UX + runtime selection)
- Authority surface touched: Yes
- Authority surfaces used:
  - `template_include` resolver path for single-product template selection
  - Admin settings authority (`bw_theme_builder_lite_single_product_rules_v2`)
  - Admin AJAX type mutation endpoint for `bw_template`
- Data integrity risk: Medium

## 3) Scope Closure Summary
Implemented and closed:
1. Removed Quick Edit conditions authority surface for templates.
2. Introduced Settings-driven Single Product conditions authority.
3. Added repeater v2 rules option (`bw_theme_builder_lite_single_product_rules_v2`).
4. Implemented deterministic runtime evaluation (top-to-bottom, first-match wins).
5. Enforced parent-only category UI in rules (no subcategories rendered).
6. Added explicit Include behavior:
   - Apply to all categories (`include_product_cat = []`)
   - Apply only to selected categories.
7. Added optional Exclude behavior:
   - `Enable exclusions (optional)` toggle
   - Exclude evaluated before include.
8. Improved list UX:
   - Applies To badges (`Footer`, `Single Product`)
   - red `Not linked` badge
   - inline Type dropdown with autosave.
9. Added Single Product status summary block.
10. Updated Remove rule control to WordPress delete-style button.

## 4) Final Runtime Contract
### 4.1 Activation
Resolver branch is active only when:
- `bw_theme_builder_lite_flags[templates_enabled] = 1`
- request resolves to single product context
- single-product settings are enabled in v2 option

### 4.2 Formal Guarantee A (required)
- The single-product settings resolver branch executes **after** WooCommerce bypass guards.
- Bypassed contexts remain unchanged:
  - `is_cart()`
  - `is_checkout()`
  - `is_account_page()`
  - `is_wc_endpoint_url()`
- Therefore cart/checkout/account/endpoints are not overridden by Theme Builder Lite single-product rules.

### 4.3 Rule Evaluation
- Data source: `bw_theme_builder_lite_single_product_rules_v2` (fallback to converted v1 when v2 absent)
- Rule pass order: saved order, top-to-bottom
- Per-rule evaluation:
  1) Exclude first
  2) Include second
- Include semantics:
  - empty include list = match-all
  - selected include list = at least one category match required
- Winner: first matching valid rule

### 4.4 Parent-only + Ancestors
- UI stores only parent `product_cat` IDs.
- Runtime expands product assigned categories with ancestors.
- Parent rule matches products assigned to child categories via ancestor expansion.

### 4.5 Fail-open
If disabled/no valid rule/no match/invalid template/empty render/missing wrapper:
- resolver returns original theme template unchanged.

## 5) Determinism Confirmation
Confirmed deterministic:
- Snapshot-driven evaluation from one option key (`v2`), with explicit legacy fallback.
- Rule order deterministic (array order).
- First-match short-circuit deterministic.
- No randomization, no unstable tie-breaking logic in settings-driven branch.

## 6) Runtime Invariants (Confirmed)
- No Woo session/cart/account state mutation.
- No checkout flow mutation.
- No undeclared authority surfaces.
- Fail-open invariant preserved.
- Master flags and feature flags remain authoritative.

## 7) Acceptance Criteria Verification
- Quick Edit custom conditions removed as editable authority: Verified.
- Settings tab persists rule values across refresh: Verified.
- Include all vs selected persists correctly: Verified.
- Exclude toggle behavior persists and applies correctly: Verified.
- Parent-only category list shown in UI: Verified.
- Runtime first-match behavior works top-to-bottom: Verified.
- Applies To badges reflect active linkage: Verified.
- Inline Type dropdown persists changes and reloads row state via page refresh: Verified.
- Unauthorized/invalid type updates blocked: Verified.

## 8) Exact Files Modified
Code files updated in this task scope:
- `includes/modules/theme-builder-lite/runtime/single-product-runtime.php`
- `includes/modules/theme-builder-lite/runtime/template-resolver.php`
- `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.php`
- `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.js`
- `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.css` (new)
- `includes/modules/theme-builder-lite/admin/bw-templates-list-ux.php`
- `includes/modules/theme-builder-lite/admin/bw-template-type-inline.js` (new)

## 9) Data Model / Keys / Constants
### 9.1 Option Keys
- New authoritative option:
  - `bw_theme_builder_lite_single_product_rules_v2`
- Legacy compatibility option retained:
  - `bw_theme_builder_lite_single_product_v1`
- Existing feature flags option reused:
  - `bw_theme_builder_lite_flags`

### 9.2 Meta Keys
- `bw_template_type`
- `bw_template_priority`
- Legacy rules meta preserved:
  - `bw_tbl_display_rules_v1`

### 9.3 Constants
- `BW_TBL_SINGLE_PRODUCT_RULES_OPTION` (new)
- `BW_TBL_SINGLE_PRODUCT_OPTION` (legacy retained)

## 10) Hooks / AJAX / Nonces / Capabilities
### 10.1 Hooks
- `admin_init`
- `admin_menu`
- `admin_enqueue_scripts`
- `manage_edit-bw_template_columns`
- `manage_bw_template_posts_custom_column`
- `display_post_states`
- `restrict_manage_posts`
- `pre_get_posts`
- `template_include`

### 10.2 AJAX
- `wp_ajax_bw_tbl_update_template_type`

### 10.3 Nonce
- Action: `bw_tbl_inline_type_update`

### 10.4 Capability checks
- Settings page: `current_user_can('manage_options')`
- Inline type update: `current_user_can('edit_post', $post_id)`

## 11) CSS Selectors and JS Handlers
### 11.1 Selectors (added/changed)
- `.bw-tbl-single-product-rule`
- `.bw-tbl-include-mode-radio`
- `.bw-tbl-include-fields`
- `.bw-tbl-enable-exclude`
- `.bw-tbl-exclude-fields`
- `.bw-tbl-term-checklist-wrap`
- `.bw-tbl-remove-single-product-rule`
- `.bw-tbl-rules-toolbar`
- `.bw-tbl-inline-type-select`
- `.bw-tbl-inline-type-status`

### 11.2 JS handlers (added/changed)
- `click` `#bw-tbl-add-single-product-rule`
- `click` `.bw-tbl-remove-single-product-rule`
- `change` `.bw-tbl-enable-exclude`
- `change` `.bw-tbl-include-mode-radio`
- `change` `.bw-tbl-inline-type-select` (AJAX autosave)

## 12) Backward Compatibility Handling
- v1 option data is preserved and not deleted.
- If v2 option is absent, runtime derives an equivalent v2 payload from v1 (non-destructive fallback).
- Legacy post meta `bw_tbl_display_rules_v1` is retained (no cleanup in this task).
- Quick Edit data is not used as active authority for single-product settings.

## 13) Formal Guarantee B (required)
Inline type dropdown mutation safety:
- Invalid type values are rejected (enum validation).
- Unauthorized writes are rejected (capability + nonce checks).
- If type change invalidates existing linkage semantics, linkage is surfaced safely through badges (`Not linked`) and no fatal runtime behavior occurs (fail-open).

## 14) Security & Integrity Controls
- Input sanitization for template IDs and category IDs.
- Parent-only category enforcement in sanitize and UI rendering.
- Nonce validation on inline AJAX saves.
- Capability enforcement for post mutation.
- Post type validation (`bw_template`) before meta update.

## 15) Debug / Testing Notes
- Optional runtime debug path remains guarded by debug constant.
- Manual tests executed for:
  - settings save/reload
  - include all/selected behavior
  - exclude toggle behavior
  - badge correctness
  - inline type autosave behavior
- Tool checks:
  - `php -l` run on changed PHP files
  - `composer run lint:main` fails on existing baseline issues in `blackwork-core-plugin.php`

## 16) Rollback Plan
1. Disable single-product settings toggle in v2 option (`enabled=0`).
2. If needed, disable templates resolver via `bw_theme_builder_lite_flags[templates_enabled]=0`.
3. Revert admin/UI files listed in section 8.
4. Keep v1 and v2 data intact for re-enable/recovery.

## 17) Known Limitations
- No product ID include/exclude in settings UI (category-only MVP).
- No drag-and-drop visual reordering for rules (order follows saved payload sequence).
- Legacy `bw_tbl_display_rules_v1` still exists; cleanup/migration deferred.
- Inline type change may intentionally surface unlinked templates via badges; no auto-remapping is performed.

## 18) Future Extension Surface
- Add product ID conditions to settings repeater.
- Add drag/drop rule ordering controls.
- Add optional diagnostics panel showing matched rule for current product.
- Add one-time DB migration tool v1->v2 with report.
- Extend same pattern to additional contexts (single_page/single_post/archive/product_archive).

## 19) Risk Register Delta (must be reflected in docs)
- RISK: dual-surface compatibility (`v1/meta` legacy vs `v2` authority)
- RISK: rule precedence misunderstanding (operator expectation mismatch)
- RISK: inline type unlink integrity risk
- RISK: admin UX scaling risk with large rule counts

## 20) Decision Log Delta (must be reflected in docs)
- Abandon Quick Edit authority for single-product conditions.
- Centralize conditions in settings authority.
- Adopt repeater v2 option contract.
- Enforce parent-only UI with ancestor runtime matching.
- Use deterministic first-match rule evaluation.
- Introduce inline type dropdown with linkage-protection prompt and safe invalidation behavior.

## 21) Manual Regression Checklist (final)
1. Admin persistence
- Save/reload preserves enabled, rule order, template selections, include/exclude selections.
2. Rule order evaluation
- Two matching rules: first rule wins deterministically.
3. Include all vs selected
- `All` hides/clears include; `Selected` shows/persists checklist selections.
4. Exclude toggle behavior
- Exclude OFF ignores exclusions; Exclude ON applies exclusion-first behavior.
5. Inline type dropdown save
- Type change autosaves and persists after reload; invalid and unauthorized updates fail.
6. Badge truth reflection
- Active templates show `Applies to` badges; others show `Not linked`.
7. Woo unaffected surfaces
- Cart, checkout, my-account, endpoints remain unaffected by resolver branch.
