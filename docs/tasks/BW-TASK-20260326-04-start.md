# Blackwork Governance -- Task Start

## 1) Context
- Task ID: `BW-TASK-20260326-04`
- Task title: Product Grid filter-system analysis for next control addition
- Request source: User request on 2026-03-26
- Expected outcome:
  - analyze the full `BW Product Grid` widget in detail before the next filter change
  - focus especially on the filter subsystem:
    - Elementor control surface
    - server-rendered filter markup
    - mobile filter panel
    - JS state and event flow
    - AJAX handlers, caches, and rate limiting
  - identify the exact place where a new filter-related field can be added cleanly
- Constraints:
  - preserve `BW Product Grid` as the canonical active authority for the old Wallpost family
  - do not introduce a second filter state authority outside the current PHP -> data attribute -> JS -> AJAX contract
  - any future new field must work coherently in both desktop inline filters and mobile panel filters

## 2) Task Classification
- Domain: Elementor Widgets / Product Grid / Filter Runtime
- Incident/Task type: Governed analysis before implementation
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `includes/widgets/class-bw-product-grid-widget.php`
  - `assets/js/bw-product-grid.js`
  - `assets/css/bw-product-grid.css`
  - `blackwork-core-plugin.php`
  - Product Grid documentation
- Integration impact: Medium
- Regression scope required:
  - desktop inline filters
  - mobile slide-out filter panel
  - category -> subcategory -> tag cascade
  - AJAX request abort/cache behavior
  - infinite-scroll state after filter changes

## 3) Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/product-grid/product-grid-architecture.md`
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
- Code references to read:
  - `includes/widgets/class-bw-product-grid-widget.php`
  - `assets/js/bw-product-grid.js`
  - `assets/css/bw-product-grid.css`
  - `blackwork-core-plugin.php`

## 4) Analysis Notes
- Current filter control surface in PHP:
  - `show_filters`
  - `default_category`
  - `show_categories`
  - `filter_categories_title`
  - `show_subcategories`
  - `filter_subcategories_title`
  - `show_tags`
  - `filter_tags_title`
  - `show_all_button`
- Current render structure:
  - desktop inline filter rows
  - parallel mobile filter panel with matching categories / subcategories / tags groups
  - both surfaces share the same `data-widget-id` and state authority
- Current JS state authority:
  - `filterState[widgetId] = { category, subcategories[], tags[] }`
  - category changes reset subcategories and tags
  - subcategory changes trigger tag reload
  - desktop filters apply immediately
  - mobile filters wait for `Show results`
- Current async contract:
  - `loadSubcategories()` -> `bw_fpw_get_subcategories`
  - `loadTags()` -> `bw_fpw_get_tags`
  - `filterPosts()` -> `bw_fpw_filter_posts`
  - all three use per-widget abortable queue entries plus client/server cache layers
- Important structural observation:
  - any new filter field will likely need to exist in both desktop and mobile markup
  - if the new field changes query results, it must be carried through:
    - Elementor control
    - render markup / data attributes
    - `filterState`
    - `filterPosts()` payload
    - `bw_fpw_filter_posts()` normalization/query/cache key

## 5) Current Readiness
- Status: OPEN
- Analysis status: READY FOR NEXT REQUIREMENT
- Immediate recommendation:
  - decide first whether the upcoming extra field is:
    - visual-only filter UI
    - local client-side state
    - real query-affecting filter parameter
  - that choice determines whether it belongs only in widget PHP/JS or also in AJAX/server cache contracts
