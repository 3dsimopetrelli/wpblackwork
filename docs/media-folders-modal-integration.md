# Media Folders Modal Integration

This note captures the current Media Folders modal work so the behavior can be maintained safely.

## Added Behavior

- Media Folders mounts inside WordPress media modals as an additional sidebar.
- The same sidebar works in Elementor and in classic WordPress media dialogs.
- The modal sidebar reuses the existing folder tree, counts, accordion state, saved colors, and pinned ordering.

## Supported Modal Contexts

- Elementor image controls
- Classic WordPress Add Media modal
- Featured image selector
- Product image selector
- Product gallery selector
- Any standard `wp.media` modal using the common media frame lifecycle

## Enqueue Behavior

- Assets are enqueued from `includes/modules/media-folders/admin/media-folders-admin.php`.
- Enqueue is admin-only and uses file timestamps for versioning.
- Elementor editor hooks remain supported.
- Classic `wp.media` entry points in wp-admin are also covered.

## Frame Patching Strategy

- The JS patches media frame classes idempotently.
- The shared patch attaches handlers for `open`, `content:render:browse`, and `close`.
- Existing `wp.media.frame` instances are also bound when the frame already exists.

## Modal Mount Fallback

- The sidebar mounts into the active media modal using the frame DOM when available.
- If the frame DOM is incomplete, the code falls back to the visible `.media-modal` root and retries mounting.
- Duplicate sidebar injection is guarded against.

## Filtering

- Folder clicks apply `bw_media_folder` to the active media collection.
- `All Files` clears folder filters.
- `Unassigned Files` applies the unassigned filter.
- The modal uses the active frame collection, with fallback to `wp.media.frame.state().get('library')` when needed.

## Accordion Behavior

- Parent folder chevrons expand and collapse nested folders.
- Chevron clicks do not trigger filtering.
- Folder row clicks still apply the folder filter.

## Saved Colors and Pinned Order

- The modal reuses the existing folder tree payload and markup.
- Saved icon colors are written into the modal row markup and used by the icon rendering.
- Pinned folders keep the same ordering as the standalone Media Library sidebar.

## Constraints

- The modal is navigation and filtering only.
- No rename, delete, pin editing, or move controls are exposed inside the modal.
- The standalone Media Library screen remains unchanged.

