# Product Grid — Hardening Report

**Date**: 2026-03-14
**Branch**: `claude/review-docs-document-risks-vxNRW`
**Status**: ✅ All fixes applied and pushed

---

## Context

Six issues were identified in a review of the product grid widget against a
"bomb-proof" quality bar.  All six were fixed in the same session.  No
Elementor controls were added or removed; all changes are internal
correctness and robustness improvements.

---

## Issues Found and Fixed

### Issue 1 — Race condition on subcategory / tag AJAX requests

**Severity**: High

**Problem**:  `loadSubcategories()` and `loadTags()` each issued a new
`$.ajax()` call without aborting any in-flight request from a prior
invocation.  When a user clicked two category buttons in quick succession
the second response could arrive before the first, but the first response
would then overwrite the correct (second) result.

**Root cause**: No entry in `ajaxRequestQueue` for these two actions — only
`filterPosts()` had the abort pattern.

**Fix**: Both functions now store their `$.ajax()` return value in
`ajaxRequestQueue` under `widgetId + '_subcats'` and `widgetId + '_tags'`
respectively.  Each function aborts any existing entry before issuing the
new call.  Error handlers now guard on `status === 'abort'` and return
early without rendering error UI.

---

### Issue 2 — Fade-out clear timer not cancelled on rapid category change

**Severity**: High

**Problem**: Before clearing the subcategories or tags container, both
functions set `opacity: 0` and then called `empty()` after a 150 ms
`setTimeout`.  If a second call fired before the 150 ms expired, the timer
from the first call was still live.  When it fired it `empty()`-ed the
container that had just been filled by the second response, leaving the UI
blank.

**Fix**: A new module-level dictionary `filterAnimTimers` was added.  Each
`loadSubcategories()` / `loadTags()` call:
1. Checks for an existing timer and calls `clearTimeout()` if present.
2. Creates a new timer, stores it in `filterAnimTimers`, and deletes the key
   from the dictionary inside the callback so the entry does not accumulate.

---

### Issue 3 — Hardcoded render settings not propagated through data-attributes

**Severity**: Medium

**Problem**: `render_posts()` declared `$image_size = 'large'`, `$image_mode
= 'proportional'`, `$hover_effect = true`, and `$open_cart_popup = false`
as local variables that were never exposed to the JS layer.  `render_post_item()`
redeclared `$image_size` and `$image_mode` internally.  In `filterPosts()`,
the JS hardcoded `imageSize = 'large'`, `imageMode = 'proportional'`, and
`hoverEffect = 'yes'` as literals.

Consequence: any future Elementor control for these settings would have been
read by PHP but silently ignored by JS (AJAX reloads would continue using
the hardcoded values) and by `render_post_item()` (initial render would also
ignore the control).

**Fix**:
- `render_posts()` now emits `data-image-size`, `data-image-mode`,
  `data-hover-effect`, and `data-open-cart-popup` on the `.bw-fpw-grid`
  element from the PHP variables.  `$open_cart_popup` was previously
  hardcoded as `'no'` in the attributes array.
- `render_post_item()` receives `$image_size` and `$image_mode` as
  parameters (with defaults matching the previous hardcoded values), and its
  internal hardcoded declarations were removed.
- The call site passes the outer `$image_size` and `$image_mode`.
- `filterPosts()` in JS now reads `imageSize`, `imageMode`, and `hoverEffect`
  from the grid's data-attributes with fallback defaults.
- `$image_toggle` (which was declared in PHP but never used in PHP) was
  removed.

---

### Issue 4 — Rate limiting bypassed for authenticated users

**Severity**: Medium

**Problem**: `bw_fpw_is_throttled_request()` returned `false` immediately
for any `is_user_logged_in()` call, providing no protection against
authenticated bots, scripts, or abusive accounts.

**Fix**: Authenticated users are now subject to higher but finite limits
(300 / 300 / 200 requests per minute for subcategories / tags / filter_posts).
Their fingerprint key uses `'u' + get_current_user_id()` instead of the
IP+UA hash used for anonymous visitors.  This avoids false positives on
shared networks (offices, NAT gateways) while still capping runaway
authenticated requests.

---

### Issue 5 — Widget state not cleaned up on re-render or deletion

**Severity**: Medium

**Problem**: When Elementor re-rendered a widget (e.g., after changing a
control in the editor), the old `filterState`, stagger timers, stagger
observers, and the AJAX abort entries remained in their respective
dictionaries.  When a widget was deleted from the editor, the same entries
persisted indefinitely, keeping references to detached DOM nodes and
preventing garbage collection.

The `widgetPagingState` detection (line `state.gridEl !== $grid[0]`) already
caught the re-render case for paging state, but the other dictionaries were
not touched.

**Fix**: A new `destroyWidgetState(widgetId)` function performs a complete
teardown:
- `clearStaggerTimers()` — cancel reveal timers and observers
- `disconnectInfiniteObserver()` — disconnect sentinel IO
- Clear `filterAnimTimers` for `_subcats` and `_tags` keys
- Abort in-flight AJAX for all three queue keys
- Clear `loadingIndicatorTimers`
- Unbind `scroll.bwreveal{widgetId}` listener
- Delete all per-widget state dictionary entries

`initWidget()` now calls `destroyWidgetState()` when it detects that a
widget's `gridEl` has changed (re-render case).

`registerElementorHooks()` attaches a `MutationObserver` on `document.body`
(subtree + childList, editor-only) that calls `destroyWidgetState()` when a
`.bw-fpw-grid` element is removed from the DOM (deletion case).

---

### Issue 6 — Undocumented coupling between is-loading and is-loading-visible

**Severity**: Low

**Problem**: The `.bw-fpw-load-state` element received two CSS classes
(`is-loading` and `is-loading-visible`) for what appeared to be the same
purpose.  Their distinct roles were invisible from the code, creating a
maintenance trap: a developer removing one of them could silently break
either the functional guard in `syncInfiniteObserver()` or the visual CSS
transition.

**Fix**: A documentation block was added above `updateInfiniteUi()` that
explains:
- `is-loading` is the logical flag read by `syncInfiniteObserver()` and
  `loadNextPage()` to block concurrent requests.
- `is-loading-visible` is the visual flag added after a 400 ms delay to
  avoid indicator flash on fast/cached loads.
- A warning that removing `is-loading` requires auditing the two guard
  sites.

---

## Commits

| Hash | Message |
|------|---------|
| `5e9a3ca8` | Fix race condition: abort stale subcategory/tag AJAX requests |
| `7d45f45c` | Eliminate hardcoded render settings; wire PHP→data-attr→JS pipeline |
| `5d198d9c` | Extend rate limiting to authenticated users |
| `e441af61` | Add destroyWidgetState() and wire it to re-render and editor deletion |
| `963c7000` | Document is-loading vs is-loading-visible coupling in updateInfiniteUi |

---

## Testing Checklist

### Filter behaviour
- [ ] Clicking two category buttons rapidly shows only the result of the second click
- [ ] Subcategories and tags containers do not flash empty between rapid changes
- [ ] Reset Filters button restores default state
- [ ] Category → subcategory → tag cascade works in both desktop and mobile mode

### Rendering consistency
- [ ] Initial PHP render and AJAX-rendered cards use the same image size
- [ ] Hover images are shown / hidden consistently between initial render and filter
- [ ] Cart popup integration (if enabled) works on AJAX-rendered cards

### Rate limiting
- [ ] Anonymous requests above 35/min for filter_posts receive a safe empty-state response
- [ ] Authenticated requests above 200/min for filter_posts receive a safe empty-state response
- [ ] Normal browsing (< 5 requests/min) is never throttled

### Elementor editor lifecycle
- [ ] Changing a grid setting (e.g., columns) re-renders without JS errors in console
- [ ] Deleting the widget leaves no stale timers (verify via DevTools → Performance → memory snapshot)
- [ ] Adding the widget after deletion initialises cleanly

### Infinite scroll
- [ ] Loading indicator does not flash for cached responses (appears only after ~400 ms)
- [ ] Scrolling to the bottom loads the next batch once
- [ ] No duplicate batch loads when scrolling rapidly

---

## Residual Risks

| Risk | Owner | Action |
|------|-------|--------|
| `filterState` is not reset between Elementor re-renders (intentional — preserves active filters during editing) | Architecture decision | If filter-reset-on-re-render is ever required, call `delete filterState[widgetId]` before `initFilterState()` in `initWidget()` |
| MutationObserver on `document.body` has a subtree scan cost; acceptable for editor-only but should not be backported to frontend | Known constraint | `isElementorEditor()` guard is in place; verify it holds if Elementor Popup or Theme Builder renders the widget outside the main editor |
| Server-side transient cache is shared across all users; a filter combination that returns zero results is cached as empty | Known behaviour | Intended — avoids repeated empty queries.  Cache TTL is 5 min. |
