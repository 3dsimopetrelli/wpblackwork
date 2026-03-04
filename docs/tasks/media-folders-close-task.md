# Media Folders — Task Closure

## Overview
Media Folders introduce a native folder system inside WordPress Media Library (`upload.php`) for organizing `attachment` items without changing physical file paths or URLs.

Problem solved:
- WordPress Media Library does not provide native hierarchical virtual folders for media organization.
- Teams needed folder/subfolder organization, fast filtering, and bulk assignment directly in admin.

Context:
- The module works only in WP admin Media Library screen (`upload.php`) and is feature-flagged.
- Folder organization is virtual through taxonomy (`bw_media_folder`) on attachments.

UX improvements delivered:
- Dedicated collapsible sidebar with folder tree, defaults, search, and bulk actions.
- Drag and drop assignment in grid/list.
- Visual assigned-media marker badge on thumbnails.
- Quick file-type filters with live counts (Video, JPEG, PNG, SVG, Fonts).

## Implemented Features
Folder tree:
- Renders hierarchical folder nodes from taxonomy terms.
- Available in the left sidebar (`#bw-media-folders-root`).
- Includes per-folder count, icon, pin indicator, and context actions.

Parent folders:
- Parent terms are supported via hierarchical taxonomy.
- Parent nodes can be expanded/collapsed through chevron.

Child folders:
- Child terms are rendered recursively in the same tree.
- Indentation is applied by depth (`padding-left` in inline style from JS).

Accordion navigation:
- Parent folders use chevron toggle.
- Collapsed state persists in `localStorage` key `bw_mf_folder_collapsed`.
- Children visibility updates via class/state sync (`is-collapsed`, `bw-mf-hidden-by-collapse`).

Parent indicator:
- Parent nodes show `.bw-mf-chevron`.
- Leaf nodes do not render a chevron.

Folder color:
- Icon color is stored in term meta `bw_mf_icon_color`.
- Color picker popover from folder action menu updates icon color live and persists.

Pin folders:
- Pin state stored in term meta `bw_mf_pinned` (and synced with legacy `bw_pinned`).
- Pinned folders sort first among siblings server-side and can be toggled from menu.
- Visual indicator (`📌`) shown in row metadata.

Media counter:
- Sidebar shows `All Files`, `Unassigned Files`, and per-folder counts.
- Folder counts are server computed with `include_children => true` (aggregate behavior).
- Unassigned count uses `NOT EXISTS` taxonomy query.

Indicator badges in thumbnails:
- For assigned media in grid, a top-left round badge is shown (`.bw-mf-marked`).
- Badge color is folder icon color when set, otherwise black fallback.
- Optional tooltip (feature toggle) shows folder name on hover.

Quick type filters:
- Inline chips: Video, JPEG, PNG, SVG, Fonts.
- Live counts recalculated from visible media rows/tiles.
- Click toggles active filter; second click resets.

JPEG / PNG filters:
- JPEG: `image/jpeg` and `image/jpg`.
- PNG: `image/png`.

SVG filter:
- `image/svg+xml`.

Fonts filter:
- Matches `font/*`, `application/font*`, `application/x-font*`, and common font mimes (`woff`, `woff2`, `ttf`, `otf`, `eot`).

Video filter:
- Matches `video/*`.

Toolbar integration:
- Type filters are inserted in a dedicated inline wrapper (`.bw-mf-toolbar-inline`) inside media toolbar/tablenav containers when available.
- Placement is idempotent and updated on DOM rebuild.

Grid/List compatibility:
- Grid mode and list mode are both supported for tree filtering and type chips.
- Drag & drop works in grid and list rows.
- Bulk move works in both modes using selected IDs.

UI improvements:
- Collapsible sidebar with vertical “Open Folders” tab in collapsed mode.
- Context menu (rename, pin/unpin, icon color, delete).
- DnD drop-target highlight + drag count badge.

## UI / UX Improvements
Media Library sidebar:
- Fixed left panel mounted in `#wpbody-content`.
- Collapse/expand persisted by `localStorage` (`bw_mf_collapsed`).

Folder tree layout:
- Rows with left group (chevron/icon/name) and right group (pin/count/pencil).
- Parent/child rows share consistent internal alignment slots.

Folder icons:
- Inline SVG folder icon (`fill="currentColor"`) driven by CSS color.
- Supports default/active/custom-color states.

Spacing:
- Refined row spacing, icon/text proximity, pencil offset, and count alignment.

Accordion animation:
- Chevron rotates with smooth easing.
- Collapsed children hide via animated row transition (`max-height`, `opacity`, `margin`).

Toolbar filters:
- Quick type chips styled as pill controls with active state.
- Inline integration in media toolbar/list tablenav.

Media indicators:
- Assigned media badge marker is lightweight and non-interactive (`pointer-events:none`).

Badges:
- Circular badge (`12x12`) with shadow on top-left.
- Uses `--bw-mf-marker-color` custom property.

Hover behaviours:
- Folder row hover/active/drop-target highlighting.
- Pencil appears on hover.
- Tooltip appears on marker hover only when enabled and folder name exists.

## Architecture
### PHP
Server-side responsibilities:
- Module bootstrap and feature-flag gating.
- Taxonomy + term-meta registration.
- Upload screen asset enqueue and sidebar mount.
- Media query filtering (list and grid).
- AJAX endpoints for folder tree/counts/CRUD/meta/assignment/markers.
- Capability, nonce, context, and payload validation.

### JavaScript
`includes/modules/media-folders/admin/assets/media-folders.js` responsibilities:
- Sidebar UI rendering and state (`state.folders`, active filters, counts).
- Folder tree rendering (recursive rows), search, accordion state persistence.
- Media filtering in grid (`wp.media` collection props) and URL fallback.
- DnD + bulk assignment flows and UI feedback.
- Context menu and color popover interactions.
- Marker badge application with caching and observer-based refresh.
- Quick type filters: render, placement, count calculation, row/tile hide/show.
- Idempotent init and guarded event binding.

### CSS
`includes/modules/media-folders/admin/assets/media-folders.css` responsibilities:
- Sidebar layout, collapse behavior, and responsive panel sizing.
- Tree row styling, chevron/icon alignment, hover/active/drop states.
- Context menu and color popover visuals.
- DnD drag badge visuals.
- Assigned-media marker badge styling.
- Quick type filters styling and toolbar integration polish.

## DOM Integration
Media toolbar:
- Quick type filters integrated through `.bw-mf-toolbar-inline`.
- Preferred insertion targets:
  - Grid: `.media-toolbar .media-toolbar-secondary` (fallback `.media-toolbar-primary` / `.media-toolbar`).
  - List: `.tablenav.top .actions` (fallback parent containers).

Tablenav:
- List mode placement uses `.tablenav.top` containers.

Attachments grid:
- Sidebar filters drive `wp.media` collection properties (`bw_media_folder`, `bw_media_unassigned`).
- Marker badges and quick type filters operate on `.attachments-browser .attachment[data-id]`.

Media list:
- Filtering applied through URL/main query integration.
- Quick type visibility acts on `#the-list tr[id^="post-"]`.

Inserted elements:
- Folder tree mount: `#bw-media-folders-root`.
- Collapse tab: `#bw-mf-collapse-tab`.
- Context menu: `#bw-mf-context-menu`.
- Color popover: `#bw-mf-color-popover`.
- Marker tooltip: `#bw-mf-badge-tooltip`.
- Type filters: `#bw-mf-type-filters`.

## Performance Considerations
MutationObserver:
- Separate observers for marker refresh and quick-filter placement/update.
- Observers are singleton/idempotent and re-used.

Debounce:
- Marker refresh uses debounced scheduling.
- Quick filter count/placement updates are debounced to limit DOM churn.

Prevention of DOM duplication:
- Init guarded by `window.__BW_MF_INIT_DONE`.
- Toolbar/filter wrappers created once and moved (not cloned).
- Event handlers use namespaced/off-on patterns and guard flags.

Caching:
- Marker cache by attachment ID (`markerCache`) avoids repeated marker fetch.
- Quick filter mime cache by attachment ID (`quickTypeMimeCache`) avoids repeated MIME detection.

## Risks
WordPress admin DOM changes:
- Toolbar/tablenav selectors are DOM-dependent and may require updates on WP admin markup changes.

Plugin conflicts:
- Other plugins mutating media toolbar, attachment classes, or admin-ajax payloads may affect placement and quick-filter detection.

Media library updates:
- Changes in `wp.media` collection APIs or query-attachments payload structure can affect grid filtering behavior.

JS observers:
- Observer over-triggering risk is mitigated by debounce/guards but should be monitored on very large libraries.

## Compatibility
WordPress admin:
- Scoped to admin only, `upload.php` only.

Grid view:
- Supported: folder filter, DnD assignment, bulk assignment, markers, quick type filters.

List view:
- Supported: folder filter, DnD rows, bulk assignment, quick type filters.

Media modal:
- Not targeted by this module.
- Assets and main runtime integrations are scoped to Media Library screen; modal flows are intentionally not modified.

## Maintenance Guide
Code locations:
- Bootstrap: `includes/modules/media-folders/media-folders-module.php`
- Data layer: `includes/modules/media-folders/data/`
- Admin UI: `includes/modules/media-folders/admin/`
- Runtime hooks/endpoints: `includes/modules/media-folders/runtime/`

Modify UI:
- Sidebar/toolbar/marker look & layout: `admin/assets/media-folders.css`
- Interactive behavior: `admin/assets/media-folders.js`

Add new file-type filters:
- Update `getQuickTypeDefinitions()` in JS.
- Extend `mapMimeToQuickType()` and optional extension/class fallback mappers.
- No PHP changes required for quick-filter chips.

Modify icons:
- Folder icon SVG is generated in `nodeHtml()` in JS.
- Icon color behavior is controlled by `--bw-mf-icon-color` and term meta `bw_mf_icon_color`.

Modify layout:
- Sidebar width/collapse variables are in CSS root vars under `body.upload-php.bw-mf-enabled`.
- Toolbar placement behavior is in `ensureTypeFiltersPlacement()`.

## Extensibility
Possible additional type filters can follow existing quick-filter pattern:
- PDF (`application/pdf`)
- Audio (`audio/*`)
- Documents (`application/msword`, `application/vnd.openxmlformats-officedocument.*`, etc.)

Recommended extension approach:
- Add key/label in `getQuickTypeDefinitions()`.
- Add MIME mapping in `mapMimeToQuickType()`.
- Keep caching and debounced recompute unchanged.

## Testing Checklist
- [ ] Grid view loads with sidebar, no infinite loading.
- [ ] List view loads with sidebar.
- [ ] Folder tree render includes parent and child rows.
- [ ] Parent chevron toggles collapse without triggering folder filter.
- [ ] Click folder row filters media; click again on defaults resets.
- [ ] Unassigned filter shows only media without folder term.
- [ ] Drag single tile/row to folder assigns correctly.
- [ ] Drag selected multi-items assigns all selected items.
- [ ] Bulk move selected works in grid and list.
- [ ] Folder counts update after assignment/unassignment.
- [ ] Pin/unpin updates indicator and sibling ordering.
- [ ] Icon color picker applies and persists on reload.
- [ ] Marker badge appears only on assigned media.
- [ ] Marker color matches folder icon color or falls back to black.
- [ ] Badge tooltip appears only when tooltip toggle is enabled.
- [ ] Quick type filter counts update after refresh/search/folder change.
- [ ] Quick filters work in both grid and list.
- [ ] Switching grid/list does not duplicate quick-filter wrappers.
- [ ] Collapse/expand sidebar state persists after refresh.

## Change Log
Implemented within Media Folders task lifecycle:
- Added isolated module bootstrap with feature-flag no-op behavior.
- Added admin settings page under Blackwork Site → Media Folders.
- Implemented taxonomy-based virtual folders + term meta model.
- Added runtime query filters for list (`pre_get_posts`) and grid (`ajax_query_attachments_args`) with strict guards.
- Implemented AJAX CRUD/meta/assignment/count/tree endpoints with nonce/capability/context checks.
- Built sidebar UI with defaults, search, bulk organizer, folder tree, context actions.
- Added accordion navigation with persistent collapsed state.
- Added drag & drop + bulk assignment for grid and list.
- Added pinned folder persistence + server-side ordering.
- Added icon color picker persistence.
- Added assigned-media badge marker and optional folder-name tooltip.
- Added quick type filters with live counts and grid/list toolbar integration.
- Replaced original Images quick filter with separate JPEG and PNG filters.
