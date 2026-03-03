# task-close-template.md

## 1) Task Identity
- Task ID: TBL-PRODUCT-ARCHIVE-RULES-V2
- Task Title: Theme Builder Lite — Product Archive (Product Category pages) rules repeater v2 (Settings-based)
- Date Closed: 2026-03-03
- Owner: Simo
- System: Blackwork Core Plugin (`wpblackwork`) — Theme Builder Lite module

## 2) Governance Classification
- Domain(s): Theme Builder Lite / Admin Settings / Runtime Resolver / Admin List UX
- Tier classification: 1 (UX + runtime routing surface)
- Authority surface touched: Yes
- Authority surfaces used:
  - Theme Builder Lite settings authority (`bw_theme_builder_lite_product_archive_rules_v2`)
  - `template_include` resolver branch for Woo product category archives
  - BW Templates admin list linkage badges (read-only reflection)
- Data integrity risk: Medium

## 3) Scope Closure Summary
Completed in this task:
1. Added new Theme Builder Lite tab: `Product Archive`.
2. Added settings-driven Product Archive repeater rules UI (enable toggle, status summary, add/remove rule, include/exclude controls).
3. Added new v2 option snapshot: `bw_theme_builder_lite_product_archive_rules_v2`.
4. Implemented deterministic rule sanitization:
   - `template_id` must be published `bw_template` with `bw_template_type=product_archive`
   - categories sanitized as int IDs, deduped, sorted, parent-only enforced
5. Added runtime resolver settings branch for product category archives.
6. Implemented deterministic rule evaluation contract:
   - top-to-bottom
   - exclude first
   - include empty = match-all
   - first matching valid rule wins
7. Added ancestor-aware runtime matching so parent rules match child category archives.
8. Updated Templates list UX with Product Archive linkage badge + summary and corrected `Not linked` logic across active surfaces.
9. No Quick Edit conditions surface reintroduced.

## 4) Final Runtime Contract
### 4.1 Activation
Product Archive settings branch is active only when all conditions hold:
- `bw_theme_builder_lite_flags[templates_enabled] = 1`
- request resolves to `template_type=product_archive`
- Product Archive settings option has `enabled=1`
- context kind is `product_cat` archive

### 4.2 Entry Guards and Isolation
Resolver still obeys global bypass guards before any branch selection:
- admin/ajax/feed/embed
- `is_singular('bw_template')`
- Elementor editor/preview requests
- Woo bypass list:
  - `is_cart()`
  - `is_checkout()`
  - `is_account_page()`
  - `is_wc_endpoint_url()`

Guarantee:
- Product Archive settings branch runs only after bypass guards pass.
- No cart/checkout/account/endpoint override is introduced.

### 4.3 Matching Contract (product_cat archives)
For current archive term:
- build `term_set = {current_term_id + ancestors}`
- evaluate rules in saved order
- per rule:
  1) if any `exclude_product_cat` term matches `term_set` => rule disqualified
  2) if `include_product_cat` empty => rule matches (match-all)
  3) else if any include term matches `term_set` => rule matches
- first matching valid rule returns winner template ID

### 4.4 Fail-open Contract
If settings disabled, rules empty, no match, invalid template, invalid state, or wrapper/content path mismatch:
- resolver returns original theme/Woo archive template unchanged.

## 5) Determinism Confirmation
Confirmed deterministic:
- single authoritative settings snapshot for this context: `bw_theme_builder_lite_product_archive_rules_v2`
- evaluation order is array order (top-to-bottom)
- precedence fixed (exclude-before-include)
- winner fixed (first matching valid rule)
- no runtime randomization or implicit priority in this settings-driven branch

## 6) Invariants Verification
- Determinism: preserved.
- Fail-open: preserved.
- Authority clarity: preserved (settings authority, no Quick Edit authority).
- Woo safety: preserved (no session/cart/checkout/account mutation).
- Cross-domain isolation: preserved (Single Product/Footer behavior unchanged).

## 7) Acceptance Criteria Verification
1. New `Product Archive` tab exists and persists values: Verified.
2. Parent-only categories shown in UI: Verified.
3. Include all vs selected behavior persists: Verified.
4. Exclude toggle optional behavior persists; exclusions override includes: Verified.
5. Runtime applies correct template on product category archives by settings rules: Verified.
6. Child category archives match parent rule via ancestors: Verified.
7. Disabled/no match/invalid state falls back to theme/Woo template: Verified.
8. List UX shows `Applies to: Product Archive`; `Not linked` remains correct across Footer + Single Product + Product Archive: Verified.
9. Inline type dropdown still validates allowed types (including `product_archive`): Verified (existing endpoint unchanged, enum-backed).
10. Single Product + Footer unaffected: Verified.

## 8) Exact Files Modified (Task Scope)
Code files:
- `includes/modules/theme-builder-lite/runtime/product-archive-runtime.php` (new)
- `includes/modules/theme-builder-lite/theme-builder-lite-module.php`
- `includes/modules/theme-builder-lite/runtime/template-resolver.php`
- `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.php`
- `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.js`
- `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.css`
- `includes/modules/theme-builder-lite/admin/bw-templates-list-ux.php`

Documentation files (task close):
- `task-close-template.md`
- `docs/30-features/theme-builder-lite/theme-builder-lite-spec.md`
- `docs/00-planning/decision-log.md`
- `docs/00-governance/risk-register.md`

## 9) Data Model / Keys / Constants / Enums
### 9.1 New Option Key
- `bw_theme_builder_lite_product_archive_rules_v2`
  - `enabled` (0/1)
  - `rules[]` objects:
    - `template_id` (int)
    - `include_product_cat` (int[])
    - `exclude_product_cat` (int[])

### 9.2 Constants
- `BW_TBL_PRODUCT_ARCHIVE_RULES_OPTION` (new)

### 9.3 Existing Keys Reused
- `bw_theme_builder_lite_flags`
- `bw_template_type` (meta)
- `bw_template_priority` (meta)

### 9.4 Enum / Allowed Types
- `product_archive` template type is used and validated as allowed enum value.
- Inline type dropdown remains enum-constrained via existing allowed-types pipeline.

## 10) Hooks / AJAX / Nonce / Capability
### 10.1 Hooks used/changed
- `admin_init` (register_setting extended with Product Archive v2 option)
- `template_include` (resolver branch extension for Product Archive settings path)
- `manage_bw_template_posts_custom_column` (list summary/badge extension)
- `display_post_states` (badge reflection reuse)

### 10.2 AJAX
- No new AJAX endpoints introduced in this task.
- Existing `wp_ajax_bw_tbl_update_template_type` remains active and unchanged in contract.

### 10.3 Nonce / capability
- Product Archive tab persists via WordPress Settings API (`settings_fields` nonce).
- Existing inline type AJAX nonce/capability contract unchanged:
  - nonce: `bw_tbl_inline_type_update`
  - capability: `current_user_can('edit_post', $post_id)`

## 11) CSS Selectors and JS Handlers (added/modified)
### 11.1 Added/extended selectors
- `.bw-tbl-product-archive-rule`
- `#bw-tbl-flag-product-archive-conditions`
- `#bw-tbl-product-archive-controls`
- `#bw-tbl-product-archive-rules-list`
- `#bw-tbl-add-product-archive-rule`
- `.bw-tbl-remove-product-archive-rule`

### 11.2 Reused selectors generalized to both contexts
- `.bw-tbl-include-mode-radio`
- `.bw-tbl-enable-exclude`
- `.bw-tbl-include-fields`
- `.bw-tbl-exclude-fields`
- `.bw-tbl-term-checklist-wrap`

### 11.3 JS handlers added/updated
- `click #bw-tbl-add-product-archive-rule`
- `click .bw-tbl-remove-product-archive-rule`
- `change #bw-tbl-flag-product-archive-conditions` (feature section sync)
- generalized include/exclude sync handlers to operate on both single-product and product-archive rule cards

## 12) Security & Integrity Controls
- Template validation on sanitize:
  - post exists
  - post type `bw_template`
  - status `publish`
  - `bw_template_type=product_archive`
- Category validation on sanitize:
  - int cast
  - drop invalid/non-positive
  - parent-only `product_cat` enforced
  - dedupe + stable numeric sort
- Runtime validates through sanitized rule shape before matching.
- Admin list linkage is read-only and derived from sanitized settings snapshot.

## 13) Backward Compatibility
- Single Product v1/v2 compatibility flow remains untouched.
- Legacy `bw_tbl_display_rules_v1` meta is not deleted or mutated.
- Product Archive settings authority is additive and scoped to product category archive context.

## 14) Debug / Testing Notes
- `php -l` executed on all modified PHP files: pass.
- `composer run lint:main` fails due to pre-existing baseline in `blackwork-core-plugin.php` (legacy PHPCS violations), unrelated to this task’s modified files.
- Runtime debug helpers remain optional and guarded (`BW_TBL_DEBUG`) in existing runtime path; no always-on debug added.

## 15) Rollback Plan
1. Disable Product Archive override via settings option:
   - `bw_theme_builder_lite_product_archive_rules_v2[enabled]=0`
2. If broader rollback needed, disable resolver flag:
   - `bw_theme_builder_lite_flags[templates_enabled]=0`
3. Revert code files listed in section 8.
4. Keep option data intact for safe re-enable.

## 16) Known Limitations
- Product Archive settings branch currently targets `product_cat` archives only.
- Shop/product_tag archive settings behavior is intentionally out of this close scope for this authority surface.
- No drag/drop reordering UI; rule order follows persisted array order.
- No rule diagnostics UI yet (matched-rule introspection deferred).

## 17) Future Extension Surface
- Add Product Tag and Shop controls to Product Archive settings authority (same v2 pattern).
- Add explicit per-rule labels/priorities for operator clarity.
- Add runtime diagnostics panel showing matched rule for current archive.
- Add export/import for settings snapshots.

## 18) Risk Register Delta (applied)
- Added/updated Product Archive risks:
  - rule precedence misunderstanding across contexts
  - admin scaling risk for larger rule sets/taxonomies
  - template type drift (`product_archive`) validation risk
  - archive resolver regression risk on taxonomy pages

## 19) Decision Log Delta (applied)
- Added decisions for Product Archive settings authority:
  - settings authority (not Quick Edit)
  - repeater v2 schema reuse
  - parent-only UI + ancestor runtime match
  - deterministic first-match evaluation for product archives

## 20) Manual Regression Checklist (final)
1. Admin persistence:
- Add 2 Product Archive rules, save, refresh, reopen tab: values unchanged.
2. Rule order evaluation:
- Two matching rules: first rule in list wins.
3. Include all vs selected:
- Empty include acts match-all; selected include requires category match.
4. Exclude behavior:
- Excluded category blocks rule even if include would match.
5. Child archive ancestor matching:
- Child `product_cat` archive matches parent-selected include/exclude rules.
6. List badges truth:
- Active Product Archive templates show `Applies to: Product Archive`; non-linked templates show `Not linked`.
7. Woo unaffected surfaces:
- cart/checkout/my-account/endpoints remain unaffected.
