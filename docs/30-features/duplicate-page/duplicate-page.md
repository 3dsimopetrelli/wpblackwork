# Duplicate Page — Feature Documentation

## Overview

The **Duplicate Page** feature adds a one-click "Duplicate" action to the WordPress admin page and post list screens. The copy preserves the complete WordPress post structure **and** the full Elementor layout, so the duplicated page opens in the Elementor editor exactly as the original was designed.

## Entry point

`includes/class-bw-duplicate-page.php` → `BW_Duplicate_Page` class  
Loaded in `blackwork-core-plugin.php` (admin-only, wrapped in `is_admin()`).

---

## How it works

### 1. Row action

The class hooks into `page_row_actions` and `post_row_actions` to append a **Duplicate** link next to Edit / Quick Edit / Trash on every row.

```
Title ↑                              Link    Date ↑
Documentation — Elementor            ✏ 🔗    …
  Edit | Quick Edit | Trash | Duplicate | View
```

The link URL contains:
- `action=bw_duplicate_page` (routed via `admin_action_bw_duplicate_page`)
- `post={ID}` — source post ID
- `_wpnonce={nonce}` — CSRF token scoped to the specific post

### 2. Duplication process

When the action fires:

| Step | What happens |
|---|---|
| **Security** | Nonce verified (`bw_duplicate_page_{ID}`); capability check `edit_post` |
| **Post insert** | `wp_insert_post()` creates a **Draft** with title appended `(Copy)` |
| **Meta copy** | All `get_post_meta()` rows copied, except ephemeral keys (see below) |
| **Taxonomy copy** | All term IDs from every registered taxonomy re-applied |
| **Redirect** | Browser is sent to `post.php?action=edit&post={new_id}` |

### 3. Elementor data

Elementor stores its layout in post meta. The following keys are copied verbatim:

| Meta key | Purpose |
|---|---|
| `_elementor_data` | Full JSON layout — all sections, columns, widgets |
| `_elementor_edit_mode` | Must be `'builder'` for Elementor to load the editor |
| `_elementor_template_type` | e.g. `'wp-page'` |
| `_elementor_version` | Elementor version string |
| `_elementor_page_settings` | Per-page Elementor settings (custom CSS, etc.) |

The CSS cache key `_elementor_css` is **intentionally skipped** — Elementor regenerates it automatically on first save or preview.

### 4. Keys intentionally skipped

```php
private const SKIP_META = [
    '_edit_lock',      // editing lock (session-bound)
    '_edit_last',      // last editor user ID
    '_wp_old_slug',    // redirect slug history
    '_wp_old_date',    // date-based redirect
    '_elementor_css',  // CSS cache — regenerated automatically
];
```

---

## Security

| Concern | Mitigation |
|---|---|
| CSRF | Nonce scoped to post ID, verified before any write |
| Privilege escalation | `current_user_can('edit_post', $post_id)` checked twice (row action + handler) |
| Output escaping | All HTML output uses `esc_url()`, `esc_html()`, `esc_attr()` |
| Input sanitisation | `$_GET['post']` cast with `absint()`; nonce sanitised before verification |

---

## UX

- The duplicate is always created as **Draft** — never published accidentally.
- An admin notice confirms the operation on the editor screen: *"Page duplicated successfully. You are now editing the copy."*
- Works for both **Pages** and **Posts** (and any custom post type if the `post_row_actions` filter applies).

---

## Adding support for custom post types

To show the Duplicate action on a custom post type (e.g. `product`), add a filter in the plugin:

```php
add_filter('product_row_actions', [new BW_Duplicate_Page(), 'add_row_action'], 10, 2);
```

Or extend `BW_Duplicate_Page::__construct()` to hook additional post type filters.

---

## Changelog

| Date | Version | Notes |
|---|---|---|
| 2026-04-15 | 1.0.0 | Initial implementation — Pages + Posts, full Elementor meta copy |
