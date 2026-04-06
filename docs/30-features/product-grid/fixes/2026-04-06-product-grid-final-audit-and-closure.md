# Product Grid — Final Audit And Closure

**Date**: 2026-04-06  
**Status**: documented, audited, ready for manual QA / closure review

---

## Scope

This report closes the current BW Product Grid implementation cycle and records
the effective code state after the recent restoration, UX, architecture, and
performance passes.

It is not a theoretical roadmap. It reflects the current code in:

- `includes/widgets/class-bw-product-grid-widget.php`
- `assets/js/bw-product-grid.js`
- `assets/css/bw-product-grid.css`
- `blackwork-core-plugin.php`

---

## Final Feature State

### Responsive discovery mode

The Product Grid now has a shared discovery architecture for responsive filter
mode:

- shared drawer for desktop/mobile discovery
- shared toolbar state
- real feature-flagged search
- shared chips / reset
- shared Year filtering
- shared advanced filters
- shared runtime sort

Legacy inline mode still exists for non-discovery configurations.

### Runtime sort

Runtime sort is implemented end-to-end.

- Elementor controls:
  - `Show Order By`
  - `Order By Trigger Style`
- shared client state:
  - `filterState[widgetId].sortKey`
- supported sort keys:
  - `default`
  - `recent`
  - `oldest`
  - `title_asc`
  - `title_desc`
  - `year_asc`
  - `year_desc`
- backend-authoritative mapping is implemented
- `specific_ids` intentionally disables runtime sort

### Visible desktop filters

Visible desktop filters are implemented as a second UI surface over the same
Product Grid filtering system.

Current visible-group order:

- `Categories`
- `Style / Subject`
- `Artists`
- `Author`
- `Source`
- `Technique`
- `Year`

Important meanings:

- `Categories` = existing `types` group
- `Style / Subject` = existing `tags` group

Visible filters remain:

- desktop only
- synchronized with the drawer
- synchronized with chips / reset / shared backend filtering

### Advanced filters

Advanced filters are fully implemented and context-aware:

- `Artist`
- `Author`
- `Publisher`
- `Source`
- `Technique`

They are supported through:

- canonical derived meta
- per-context indexes
- refined `filter_ui`
- shared drawer rendering
- shared visible-filter rendering where applicable

### Year filter

Year filtering is implemented on canonical `_bw_filter_year_int`.

Current behavior:

- slider
- `From / To`
- quick ranges for normal datasets
- tiny datasets suppress low-value quick-range noise
- visible-filter Year pill remains intentionally special:
  - inline active range
  - clear-only `X`

### Search feature flag

`Show Search` is a real feature flag across:

- PHP render output
- JS binding/setup
- AJAX payload
- backend search matching

When disabled, Product Grid bypasses search UI and effective search work.

---

## UX / Surface Consolidation

The widget now uses one shared filtering system across:

- popup / drawer filters
- desktop visible filters
- chips
- reset
- runtime sort
- Year state
- advanced filters

The work explicitly avoided:

- second filtering engines
- second sort systems
- fake UI-only sort labels
- separate desktop/mobile filter truth

---

## Backend Hardening Summary

### Search path

Default Product Grid search no longer scans raw `post_content`.

Search now focuses on:

- title
- slug
- excerpt
- taxonomy term names
- canonical searchable meta
- source-meta fallbacks during migration windows

Search query hardening also includes:

- JOIN-based Year filtering in the search path
- JOIN-based taxonomy-name search
- JOIN-based searchable-meta search

### Append-mode optimization

Append / infinite-scroll responses are intentionally smaller.

They do not recompute:

- `tags_html`
- `available_tags`
- `available_types`
- `filter_ui`
- Year UI
- advanced filter UI

### Advanced-filter prequery hardening

The backend no longer runs the expensive advanced-filter candidate prequery
just because a context supports advanced filters.

When the widget is in the default context-root browse state, cached indexes
are used directly.

### Response cache refinement

The Product Grid response cache now favors data reuse over full HTML
fragmentation.

- expensive filtered dataset is cached
- final HTML is still rendered with current widget visual settings
- purely visual widget settings no longer fragment the main cached data path

### Derived-data caching

Dedicated caches now exist for:

- `bw_fpw_get_related_tags_data()`
- `bw_fpw_get_available_subcategories_data()`

### Index hardening

Year index and advanced-filter index now use:

- lightweight per-context build locks
- safer generation-based invalidation

Advanced-filter index invalidation no longer relies on broad `DELETE LIKE`
purges as its normal path.

### Throttling

Request throttling remains active.

Current balance:

- authenticated users: server-side counters
- anonymous users: signed cookie fixed-window path
- sparse server-side fallback/block memory for anomalous anonymous traffic

This reduces normal DB churn without removing protection.

---

## Frontend Hardening Summary

### Drawer rendering

The discovery drawer no longer rebuilds its full DOM unnecessarily.

- group-level patching is used
- local search inside one group rerenders that group only
- unrelated groups are not replaced without need

### `filter_ui` syncing

Replace-mode uses section hashes so unchanged filter-ui sections can be
omitted from the payload and skipped on the client.

### AJAX cache hygiene

Client-side cache is now:

- bounded
- TTL-governed
- namespaced by request family

### Sort memoization

Derived runtime sort config is memoized per widget until the relevant sort
inputs change.

### State hygiene

Advanced-filter selection arrays are normalized earlier in the mutation path,
so duplicates / empties do not accumulate in shared state.

---

## Known Intentional Constraints

- runtime sort is discovery-toolbar only
- visible filters are desktop only
- `specific_ids` disables runtime sort intentionally
- `Author` remains context-aware and is expected primarily in `Books`
- replace-mode refinement is still intentionally server-authoritative and can
  remain expensive on very broad datasets
- large candidate safety guard caps `post__in` candidate arrays at `12000`

---

## Validation Position

Already run during the implementation cycle:

- `php -l` on modified PHP files
- `node -c assets/js/bw-product-grid.js`
- `composer run lint:main`

Recommended final browser QA focus:

- search + filters
- replace mode + append mode
- visible filters + drawer sync
- Years + year sort
- Digital / Books / Prints contexts
- search `ON/OFF`
- `specific_ids` sort-disable behavior

---

## Closure

The Product Grid widget is now:

- restored
- consolidated
- documented against the real codebase
- ready for final manual QA and review closure
