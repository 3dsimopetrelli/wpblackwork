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

## Styling Guidelines
- Keep all selectors scoped under `.bw-admin-root`
- Prefer generic utility classes over module-specific CSS names
- Reuse existing primitives before adding new classes
- If overflow is needed for large tables, use a wrapper (`.bw-table-wrap`) with `overflow-x:auto`

## Verification Checklist
For each UI rollout:
- `.bw-admin-root` present on page shell (or injected wrapper for WP-native list screens)
- UI Kit enqueued only on Blackwork surfaces
- Save flow unchanged
- No option key changes
- No PHP notices/fatals
- No CSS bleed outside Blackwork Site pages
