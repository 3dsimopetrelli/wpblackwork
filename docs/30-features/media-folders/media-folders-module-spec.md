# Media Folders Module Spec

## Scope
Native Blackwork Media Library organization using virtual folders (`bw_media_folder`) assigned to `attachment` posts.

Goals delivered:
- Sidebar folder tree in Media Library (`upload.php`).
- Optional sidebar folder tree on selected post list tables (`edit.php` for Posts/Pages/Products).
- Hierarchical folders (parent/child).
- Drag & drop and bulk assignment.
- Folder/unassigned filtering for grid and list views.
- Folder metadata UI (pin + icon color).
- Assigned media badge marker with optional folder-name tooltip.
- Quick type filters (Video, JPEG, PNG, SVG, Fonts) with live counts.

Out of scope:
- Physical file move/rename.
- Frontend/media URL mutation.
- Custom DB tables.
- Media modal takeover.

## Module Isolation & Flags
- Module bootstrap path:
  - `includes/modules/media-folders/media-folders-module.php`
- Global flag storage:
  - option `bw_core_flags`
- Effective gating:
- `bw_core_flags['media_folders'] = 1` enables module runtime.
- `bw_core_flags['media_folders_corner_indicator'] = 1` enables marker feature.
- Post type targets:
  - `bw_core_flags['media_folders_use_media']`
  - `bw_core_flags['media_folders_use_posts']`
  - `bw_core_flags['media_folders_use_pages']`
  - `bw_core_flags['media_folders_use_products']`
- option `bw_mf_badge_tooltip_enabled = 1` enables marker tooltip (only meaningful when marker feature is on).
- If disabled:
  - module is no-op (no taxonomy/runtime/admin assets/ajax registration execution path from module loader).

## Data Model
### Taxonomy (Strict Isolation)
- `bw_media_folder` -> `attachment`
- `bw_post_folder` -> `post`
- `bw_page_folder` -> `page`
- `bw_product_folder` -> `product`
- All are hierarchical and admin-virtual (`public=false`, `show_ui=false`, `show_in_rest=false`, `rewrite=false`, `query_var=false`)
- Isolation contract:
  - each admin content type reads/writes only its mapped taxonomy
  - folder trees are not shared across content types
  - enabling Posts/Pages/Products starts with empty trees by design

### Term Meta
- Legacy metadata:
  - `bw_color` (string, hex)
  - `bw_pinned` (int 0/1)
  - `bw_sort` (int)
- Current module metadata:
  - `bw_mf_icon_color` (string, hex)
  - `bw_mf_pinned` (int 0/1)

### Semantics
- Unassigned files:
  - attachments where taxonomy `bw_media_folder` is `NOT EXISTS`.
- Folder counts:
  - computed server-side as aggregate parent counts (`include_children` semantics).
  - runtime uses a batched relationship query (`term_relationships + term_taxonomy + posts`) and a PHP ancestor pass to avoid per-term `WP_Query` loops.
  - deterministic cache keys are taxonomy + context scoped:
    - `bw_mf_folder_counts_v1_{taxonomy}_{post_type}` (folder counts map)
    - `bw_mf_folder_tree_v1_{taxonomy}_{post_type}` (render-ready tree nodes)
    - `bw_mf_folder_summary_v1_{taxonomy}_{post_type}` (`all` + `unassigned`)
  - transient/object-cache TTL: 180s.
  - fail-open fallback: if batched query fails, legacy per-term `WP_Query` counting is used.
  - invalidation contract:
    - `set_object_terms` (scoped to supported taxonomies),
    - `created_{taxonomy}` / `edited_{taxonomy}` / `delete_{taxonomy}`,
    - `added_term_meta` / `updated_term_meta` / `deleted_term_meta` for tree-affecting keys (`bw_color`, `bw_mf_icon_color`, `bw_pinned`, `bw_mf_pinned`, `bw_sort`).
  - batch assignment path suspends per-item invalidation and performs one post-batch invalidation.

## Capability Model
- Read tree + assign + bulk assign:
  - requires `upload_files`.
- Folder CRUD + folder meta writes (create/rename/delete/pin/color):
  - requires both `upload_files` and `manage_categories`.

Rationale:
- Keep media assignment accessible to users allowed to upload/manage media.
- Restrict folder-structure mutation and metadata writes to category-management authority.

## Runtime Hooks
### Data registration
- `init` (priority 10): `bw_mf_register_taxonomy`
- `init` (priority 11): `bw_mf_register_term_meta`

### Admin integration
- `admin_menu` (priority 60): settings submenu (`Blackwork Site -> Media Folders`)
- `admin_enqueue_scripts` (priority 20): enqueue `media-folders.css/js` only on enabled list screens (`upload.php`, `edit.php` with selected post type)
- `admin_footer-upload.php` (priority 20): sidebar mount HTML output
- `admin_footer-edit.php` (priority 20): sidebar mount HTML output for selected post types

### Media query filters
- `pre_get_posts` (priority 20): list mode filtering, guarded to enabled list-table main query (`attachment`/`post`/`page`/`product`)
- `ajax_query_attachments_args` (priority 20, accepted args 2): grid mode filtering with fail-open guards

## Query Filter Contract
### List mode (`pre_get_posts`, upload/edit list tables)
- Applies only when:
  - enabled supported screen (`upload.php` or `edit.php`),
  - main query,
  - post type context enabled via module flags,
  - `bw_media_folder` or `bw_media_unassigned=1` provided.
- Taxonomy used is resolved deterministically by post type (`attachment/post/page/product`).

### Grid mode (`ajax_query_attachments_args`)
- Applies only when:
  - admin ajax + `action=query-attachments`,
  - custom vars present (`bw_media_folder` / `bw_media_unassigned`).
- Supports second arg as array or `WP_Query` (`query_vars` extraction).
- Request fallback reads `$_REQUEST['query']` for custom vars when needed.
- Tax query merge is append-based and preserves existing relation/clauses.
- Fail-open:
  - invalid/missing payload returns original args unchanged.

## AJAX Endpoints
Registered endpoints:
- `bw_media_get_folders_tree`
- `bw_media_get_folder_counts`
- `bw_media_create_folder`
- `bw_media_rename_folder`
- `bw_media_delete_folder`
- `bw_media_assign_folder`
- `bw_media_update_folder_meta`
- `bw_mf_set_folder_color`
- `bw_mf_reset_folder_color`
- `bw_mf_toggle_folder_pin`
- `bw_mf_get_corner_markers`

Validation contract (all endpoints):
- action name exact match.
- nonce `bw_media_folders_nonce`.
- admin list-table context `bw_mf_context` mapped to enabled post type (`upload` => `attachment`, `post`, `page`, `product`).
- capability check per endpoint.

Data sanitization:
- `term_id`: `absint`.
- `attachment_ids`: `array_map('absint')` + dedupe + keep only `attachment` post type.
- `name`: `sanitize_text_field` + `trim` + non-empty.
- `color`: strict `sanitize_hex_color` fallback to empty string.
- assign batch hard limit: `BW_MF_ASSIGN_BATCH_LIMIT = 200`.

Corner markers payload:
- returns per attachment id:
  - `assigned` bool
  - `color` nullable hex
  - `folder_name` string

## Frontend/Admin UI Contract (Admin Only)
### Sidebar
- Mount node: `#bw-media-folders-root`.
- Enabled on selected admin list screens according to post-type flags.
- Collapsible with body classes:
  - `bw-mf-enabled`
  - `bw-mf-collapsed`
- Collapse persistence:
  - `localStorage['bw_mf_collapsed']`.

### Tree & actions
- Folder rows rendered in JS with:
  - chevron (parent only),
  - folder icon SVG,
  - name,
  - pin indicator,
  - count,
  - pencil button for action menu.
- Context menu actions:
  - Rename, Pin/Unpin, Icon Color, Delete.

### DnD / Bulk
- Grid/list draggable sources.
- Drop targets:
  - folder rows + unassigned default row.
- Bulk move uses selected media ids + selected folder id.
- Constraint:
  - bulk assignment remains Media-only.
  - Posts/Pages/Products use single-item drag assignment only.
  - list-table drag starts only from dedicated drag handle column.

### Marker
- Badge class:
  - `.attachments-browser .attachment.bw-mf-marked`
- Color via CSS var:
  - `--bw-mf-marker-color`
- Tooltip singleton:
  - `#bw-mf-badge-tooltip`
  - enabled only when tooltip flag is on and folder name exists.
  - media-only (`attachment`) surface.

### Quick Type Filters
- Chips:
  - Video, JPEG, PNG, SVG, Fonts.
- Placement wrapper:
  - `.bw-mf-toolbar-inline`
  - injected idempotently into media toolbar/list tablenav.
- Filtering:
  - class toggle `.bw-mf-type-hidden` on tiles/rows.
  - media-only (`upload.php`) surface.

## Settings Page UI Contract
- Settings page controls update visibility live without refresh:
  - master toggle `media_folders` controls dependent sections.
  - corner indicator toggle controls tooltip sub-toggle visibility.
- "Use folders with" controls govern runtime activation by post type:
  - Media / Posts / Pages / Products.
- Save semantics remain unchanged:
  - existing keys preserved,
  - same nonce/save flow.

## List Table UX Contract (Posts/Pages/Products)
- Screens:
  - `edit.php` (Posts)
  - `edit.php?post_type=page` (Pages)
  - `edit.php?post_type=product` (Products)
- Dedicated drag-handle column (`bw_mf_drag_handle`) is rendered before the primary label column:
  - products: before `name` (fallback `title`, then `cb`)
  - posts/pages: before `title` (fallback `cb`)
  - no append-to-end fallback is allowed.
- Handle icon: 4-arrows (`dashicons-move`), drag start source is handle only.
- Drag ghost label shows current row title.
- Row/checkbox drag start is disabled for non-media post types.

## Settings Page UI Contract
- Settings submenu page (`Blackwork Site -> Media Folders`) keeps the same option semantics and save flow.
- Presentation uses the shared Blackwork Admin UI Kit (`admin/css/bw-admin-ui-kit.css`) with scoped wrapper `.bw-admin-root`.
- Layout contract:
  - page header (title + subtitle)
  - action bar below notices with primary `Save Settings` CTA
  - card-based grouped controls for module flags
- Accessibility baseline is preserved:
  - native form controls remain WordPress-standard inputs/buttons
  - focus outlines are retained/enhanced by the scoped UI kit
- No runtime/media-library behavior changes are introduced by this UI alignment.

## Performance Contract
- Idempotent init guard:
  - `window.__BW_MF_INIT_DONE`.
- Coalesced refresh scheduler:
  - `scheduleBwMfRefresh(reason)` with `requestAnimationFrame`.
- Observers:
  - marker observer (attachment DOM changes),
  - quick filter observer (attachment DOM changes),
  - layout observer (toolbar/tablenav/grid/list container changes).
- Caching:
  - marker cache (`markerCache`) by attachment id.
  - mime cache (`quickTypeMimeCache`) by attachment id.
  - cached roots (`attachmentsBrowserEl`, toolbar roots).
- Corner marker fetch single-flight queue:
  - one XHR at a time,
  - pending id set batched (max 200),
  - follow-up fetch scheduled through global refresh scheduler.
- Tree/count refresh:
  - sidebar refresh uses `bw_media_get_folders_tree` as primary source of folder counts and summary counters.
  - duplicate count refresh requests are avoided after tree refresh.

## Rollback
- Set `bw_core_flags['media_folders'] = 0`.
- Result:
  - no sidebar assets,
  - no module query filter effects,
  - no media-folders runtime actions from module bootstrap.
