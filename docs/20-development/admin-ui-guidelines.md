# Blackwork Admin UI Guidelines

## Purpose
Define the canonical Shopify-style admin UI integration pattern for Blackwork Site panel pages.

## Scope
Applies to Blackwork Site admin surfaces only:
- `blackwork-site-settings` top-level and subpages
- `blackwork-mail-marketing`
- `edit.php?post_type=bw_template` (All Templates)

## Core UI Kit
- Shared stylesheet: `admin/css/bw-admin-ui-kit.css`
- Root scope requirement: all page-level styling must render under `.bw-admin-root`
- Integration principle: wrapper + skinning first; preserve native WordPress behaviors

## Enqueue Rules
- UI Kit is enqueued via `bw_admin_enqueue_ui_kit_assets()` in `admin/class-blackwork-site-settings.php`
- Guard function: `bw_is_blackwork_site_admin_screen()`
- Must remain page-scoped to Blackwork surfaces only (no global WP admin bleed)
- Prefer page-local/module-local assets with strict hook + slug checks.
- Do not load heavy admin scripts globally across all Blackwork pages when only one tab/page needs them.

## Layout Contract
Use this structure for new/updated pages:
1. Page shell: `.bw-admin-root` + optional page class
2. Header: `.bw-admin-header`, title `.bw-admin-title`, subtitle `.bw-admin-subtitle`
3. Action bar: `.bw-admin-action-bar`
4. Content cards: `.bw-admin-card`
5. Tabs (when needed): `.bw-admin-tabs`
6. Tables/lists: keep WP-native mechanics; apply skin classes/wrappers only

Canonical flow:
- Header -> Action Bar -> Cards -> Tabs/Section content

## Functional Invariants (Non-negotiable)
- UI-only changes must not alter:
  - option keys/defaults/sanitizers
  - nonce/capability checks
  - save handlers and POST targets
  - WP_List_Table behavior (filters, search, bulk actions, sorting, pagination, row actions)
- If page uses WP list tables, do not rebuild table markup manually.
- Keep read-only diagnostics/admin observer endpoints read-only unless explicitly approved by feature contract.

## Styling Guidelines
- Keep all selectors scoped under `.bw-admin-root`
- Prefer generic utility classes over module-specific CSS names
- Reuse existing primitives before adding new classes
- If overflow is needed for large tables, use a wrapper (`.bw-table-wrap`) with `overflow-x:auto`
- Avoid large inline `<style>` blocks in render callbacks; prefer shared UI kit primitives or module-scoped static assets.
- Keep focus states visible and preserve WordPress baseline semantics for labels/inputs/buttons.

## Admin Security Checklist (New/Updated Page)
- Capability gate at page entry (`current_user_can(...)`) before rendering sensitive controls.
- Nonce field present on POST forms; server-side `check_admin_referer(...)` before writes.
- AJAX actions:
  - register only `wp_ajax_*` unless anonymous access is explicitly required.
  - verify nonce (`check_ajax_referer`) and capability before processing input.
  - sanitize all request values (`sanitize_key`, `sanitize_text_field`, `absint`, etc.).
- Escape all output (`esc_html`, `esc_attr`, `esc_url`, `esc_textarea`) in templates.

## Admin Request Input Handling Pattern
Use a canonical read pattern for all admin request parameters:

```php
$tab = isset($_GET['tab'])
    ? sanitize_key(wp_unslash($_GET['tab']))
    : '';
```

For text values from POST:

```php
$value = isset($_POST['something'])
    ? sanitize_text_field(wp_unslash($_POST['something']))
    : '';
```

For enum-like controls (`tab`, `section`, `view`), always apply an allowlist with fallback:

```php
$allowed_tabs = ['general', 'advanced'];
if (!in_array($tab, $allowed_tabs, true)) {
    $tab = 'general';
}
```

Notes:
- Keep `check_admin_referer(...)` and capability checks unchanged.
- Use `absint(wp_unslash(...))` for numeric request IDs.
- Escape on output separately (`esc_attr`, `esc_html`, `esc_url`), even after sanitization.

## Admin Performance Checklist (New/Updated Page)
- No heavy scans/queries on normal page load unless bounded and required.
- Prefer on-demand actions (button-triggered) for expensive diagnostics.
- Add bounded limits for scans (`LIMIT`, caps) and show partial-warning states.
- Cache expensive diagnostics snapshots where possible (transient/object cache).
- Ensure assets are loaded only on the intended screen/tab.
- Confirm no duplicate enqueue of same heavy dependencies across panel pages.

## Verification Checklist
For each UI rollout:
- `.bw-admin-root` present on page shell (or injected wrapper for WP-native list screens)
- UI Kit enqueued only on Blackwork surfaces
- Save flow unchanged
- No option key changes
- No PHP notices/fatals
- No CSS bleed outside Blackwork Site pages
- Nonce + capability checks still enforced on affected forms/endpoints
- Screen-specific assets still load on target page and do not leak to unrelated admin screens
