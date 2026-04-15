# Blackwork Governance — Task Start

## 1) Context
- Task ID: `BW-TASK-20260415-01`
- Task title: Duplicate Page — admin row action with full Elementor layout copy
- Request source: User request on 2026-04-15
- Expected outcome:
  - Add a **Duplicate** row action to the Pages (and Posts) list in wp-admin
  - The duplicate must preserve the complete WordPress post structure (template, taxonomies, meta) **and** the full Elementor layout (`_elementor_data` and all related keys)
  - The duplicate is created as a Draft and the editor is redirected to the new post
  - Feature is admin-only, gated by `current_user_can('edit_post')`
  - Implementation documented in `docs/30-features/duplicate-page/duplicate-page.md`

## 2) Task Classification
- Domain: WordPress Admin / Elementor Integration
- Incident/Task type: Governed feature implementation
- Risk level (L1/L2/L3): L1
- Tier classification (0/1/2/3): 2
- Affected systems:
  - WordPress admin post list screen (Pages, Posts)
  - Elementor editor (data integrity on copy)
  - Plugin main loader (`blackwork-core-plugin.php`)

## 3) Constraints
- Admin-only load (`is_admin()` guard in main plugin file)
- Never copy ephemeral meta: `_edit_lock`, `_edit_last`, `_wp_old_slug`, `_elementor_css`
- Always produce a Draft, never publish on duplication
- CSRF protection via nonce scoped to source post ID
- No external dependencies — pure WordPress API only

## 4) Deliverables
- `includes/class-bw-duplicate-page.php` — `BW_Duplicate_Page` class
- `docs/30-features/duplicate-page/duplicate-page.md` — feature documentation
- `docs/tasks/BW-TASK-20260415-01-start.md` — this file
- Registration in `blackwork-core-plugin.php`

## 5) Implementation notes
- `get_post_meta($id)` without a key returns all meta as `[ key => [value, …] ]`
- Values must be passed through `maybe_unserialize()` before `add_post_meta()` to correctly restore arrays and objects (Elementor stores its JSON in a serialised PHP array wrapper in some versions)
- `_elementor_css` is intentionally excluded; Elementor regenerates it automatically on first preview/save of the duplicate

## 6) Status
- [x] Implementation complete
- [x] Documentation written
- [ ] Closure report pending
