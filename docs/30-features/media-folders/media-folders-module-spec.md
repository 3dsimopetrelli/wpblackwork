# Media Folders Module Spec (Phase 1 MVP)

## Scope
Native Blackwork Media Library folder organization using virtual folders (`bw_media_folder`) over `attachment` posts.

## Capability Model (Deliberate MVP Decision)
- Read tree + assign + bulk assign:
  - requires `upload_files`
- Folder CRUD and folder metadata updates (create/rename/delete/color/pin):
  - requires both `upload_files` and `manage_categories`

Rationale:
- Keep media assignment accessible to users who can manage media uploads.
- Restrict taxonomy-like structure mutations to users with category management authority.
- Avoid `manage_options` over-restriction while keeping write authority controlled.

## Runtime Guard Model
- Sidebar/assets load only on `upload.php` and only when `get_current_screen()->id === 'upload'`.
- List mode filtering uses `pre_get_posts` only on Media Library main query.
- Grid mode filtering uses `ajax_query_attachments_args` only when:
  - admin ajax context,
  - `action=query-attachments`,
  - custom Media Folders query vars are present.

## Security Contract
- Every AJAX endpoint validates:
  - exact action name,
  - nonce (`bw_media_folders_nonce`),
  - capability model,
  - module context (`bw_mf_context=upload`).

## Data Validation Contract
- `term_id`: `absint`.
- `attachment_ids`: `array_map('absint')`, dedupe, drop invalid/zero, keep only `attachment` post type.
- `name`: `sanitize_text_field` + `trim` + non-empty.
- `color`: strict sanitized hex or empty string.
- Assign batch limit: max `200` attachment IDs per request.

## Rollback
- Set `bw_core_flags['media_folders'] = 0`.
- Module becomes no-op (no runtime hooks/assets/UI mutation).
