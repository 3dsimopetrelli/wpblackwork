# task-close-template.md

## 1) Task Identity
- Task ID: TBL-PREVIEW-BRIDGE-WIDGET-RESOLVER-CLOSE
- Task Title: BW Widgets Product Context Resolver standardization (Elementor preview safe)
- Date Closed: 2026-03-03
- Owner: Simo
- System: Blackwork Core plugin (Theme Builder Lite + BW widgets)

## 2) Governance Classification
- Domain(s): Theme Builder Lite runtime preview / widgets product context
- Tier: 1 (editor UX + widget rendering context)
- Authority surface touched: Preview/editor only
- Frontend authority mutation: None
- Data integrity risk: Low-Medium (preview context wiring only)
- Determinism: Confirmed

## 3) Scope Completed
1. Added preview bridge in Theme Builder Lite preview context:
   - `$GLOBALS['bw_tbl_preview_product_id']`
   - `set_query_var('bw_tbl_preview_product_id', $product_id)`
2. Kept preview hook deterministic at `wp` priority `20`.
3. Standardized product resolution via shared resolver:
   - `bw_tbl_resolve_product_context_id()`
   - `real_context -> preview_fallback -> manual_setting -> missing`
4. Standardized preview gating helper:
   - `bw_tbl_is_bw_single_product_template_preview()`
5. Removed direct `elementor-preview` dependency from widget render paths.
6. Removed noisy temporary per-widget debug logs; retained minimal bridge+resolver logs behind `BW_TBL_DEBUG_PREVIEW`.
7. Updated docs (spec + runtime hook map) to document the bridge contract.

## 4) Audit Evidence (Workspace-Real)
### Evidence A: No `elementor-preview` usage in widgets
Command:
```bash
rg -n "\$_GET\['elementor-preview'\]|\$_GET\['elementor_preview'\]|elementor-preview" includes/widgets -g"*.php"
```
Output:
```text
(no matches)
```

### Evidence B: Bridge set + hook timing + no query mutation flag
File:
- `includes/modules/theme-builder-lite/runtime/elementor-preview-context.php`

Code snippet:
```php
$GLOBALS['bw_tbl_preview_product_id'] = $product_id;
set_query_var('bw_tbl_preview_product_id', $product_id);
...
bw_tbl_preview_debug_log('preview context applied without wp_query mutation', [
    ...
    'wp_query_mutated' => false,
]);
...
add_action('wp', 'bw_tbl_apply_elementor_single_product_preview_context', 20);
```

### Evidence C: Resolver order
File:
- `includes/modules/theme-builder-lite/runtime/single-product-runtime.php`

Observed order in code:
1) `real_context` (`is_product()` + `get_queried_object_id()`)
2) `preview_fallback` by source:
   - global `bw_tbl_preview_product_id`
   - query var `bw_tbl_preview_product_id`
   - option `bw_tbl_get_preview_product_id()`
   - option legacy `bw_tbl_get_single_product_preview_product_id(false)`
3) `manual_setting` (`$settings['product_id']`)
4) `missing`

### Evidence D: WP_Scripts notice not emitted by BW for `elementor-v2-editor-components`
Command:
```bash
rg -n "elementor-v2-editor-components" includes -g"*.php"
```
Output:
```text
(no matches)
```

## 5) Exact Files Changed (Task Scope)
Runtime:
- `includes/modules/theme-builder-lite/runtime/elementor-preview-context.php`
- `includes/modules/theme-builder-lite/runtime/single-product-runtime.php`

Widgets:
- `includes/widgets/class-bw-price-variation-widget.php`
- `includes/widgets/class-bw-static-showcase-widget.php`
- `includes/widgets/class-bw-product-details-widget.php`
- `includes/widgets/class-bw-related-products-widget.php`
- `includes/widgets/class-bw-add-to-cart-widget.php`
- `includes/widgets/class-bw-add-to-cart-variation-widget.php`
- `includes/widgets/class-bw-product-slide-widget.php`
- `includes/widgets/class-bw-tags-widget.php`
- `includes/widgets/class-bw-presentation-slide-widget.php`

Docs:
- `docs/30-features/theme-builder-lite/theme-builder-lite-spec.md`
- `docs/10-architecture/theme-builder-lite/runtime-hook-map.md`
- `docs/00-planning/decision-log.md`
- `docs/00-governance/risk-register.md`
- `task-close-template.md`

## 6) Runtime Contract (Final)
- Bridge activation conditions:
  - Elementor editor/preview request
  - previewed post is `bw_template`
  - `bw_template_type = single_product`
  - preview product id is valid published Woo product
- Hook order:
  - bridge set on `wp@20` before widget render
- Resolver precedence:
  - `real_context > preview_fallback > manual_setting > missing`
- Invariants:
  - No `WP_Query` spoofing
  - No `$GLOBALS['post']` replacement
  - No frontend behavior mutation

## 7) Acceptance Checklist
- Elementor iframe warning-output mitigation applied (`WP_DEBUG_DISPLAY=false`, `display_errors=0`): Verified.
- Bridge values are set before render (`global + query_var`): Verified by code and bridge logs.
- Resolver can produce `preview_fallback` from bridge: Verified by code and observed logs.
- Widgets no longer parse `$_GET['elementor-preview']` in render paths: Verified by grep (no matches).
- `WP_Scripts::add` notice classified external to BW for `elementor-v2-editor-components`: Verified by grep (no matches in BW includes).

## 8) Known Limitations (Explicit)
- Remote server log path for blackwork.pro is not accessible from current workspace environment.
  - Accessible local log here: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/debug.log`
  - Remote path requested for audit (`/home/u216997858/domains/blackwork.pro/public_html/wp-content/debug.log`) is unavailable in this session.
- Therefore, per-widget proof lines from production server logs are an **audit limitation due to environment access**, not an implementation gap.
- If hard audit requires remote per-widget lines, operator must provide pasted lines from remote `debug.log`.

## 9) Debug Strategy and Cleanup
- Kept minimal debug behind `BW_TBL_DEBUG_PREVIEW`:
  - bridge logs (`preview context applied...`, `preview bridge values set`)
  - resolver logs (`resolver_preview_source`, `widget=<class> source=<source> id=<id>`)
- Removed noisy temporary logs (`BW_PRICE_VARIATION_DEBUG` removed).

## 10) Rollback Plan
1. Disable debug:
   - `BW_TBL_DEBUG_PREVIEW=false`
2. Bridge rollback:
   - remove/guard `add_action('wp', 'bw_tbl_apply_elementor_single_product_preview_context', 20)`
3. Widget rollback:
   - revert resolver-call changes in widget files listed above.
4. Keep fail-open:
   - unresolved context remains `missing` and widgets keep editor-only notices.

## 11) WP_Scripts Notice Classification
- Notice:
  - `WP_Scripts::add ... elementor-v2-editor-components dependencies not registered`
- Classification:
  - not emitted by BW plugin code (no matching handle in `includes/**/*.php`)
- Mitigation in place:
  - keep `WP_DEBUG_LOG=true`
  - keep `WP_DEBUG_DISPLAY=false`
  - keep `display_errors=0`
  - warnings go to log file, not HTML iframe response
