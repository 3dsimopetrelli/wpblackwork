# Blackwork System Map (AI Quickstart)

## 1) Purpose
- This file is a **2-minute orientation map** for AI agents.
- Use it to find entrypoints, authority surfaces, and canonical governance docs fast.
- It is **not** the full spec; detailed behavior stays in module/feature docs.

## 2) Repository Top-Level Map
- `blackwork-core-plugin.php`: main plugin bootstrap and loader.
- `admin/`: Blackwork Site admin panel router/settings and shared admin helpers.
- `includes/`: core runtime, modules, integrations, helpers, Woo overrides.
- `includes/modules/`: feature modules (`header`, `media-folders`, `system-status`, `theme-builder-lite`).
- `includes/admin/`: admin subsystems outside `includes/modules` (e.g. Mail Marketing/Brevo, checkout admin).
- `assets/`: shared frontend/admin JS/CSS assets used by widgets/runtime.
- `woocommerce/`: WooCommerce runtime integration and overrides bootstrap.
- `docs/`: governance, planning, architecture, feature specs, ops, tasks.

## 3) Runtime Entry Points (High-Level)
- Main bootstrap: [`blackwork-core-plugin.php`](../../blackwork-core-plugin.php)
  - Loads module entry files:
    - `includes/modules/header/header-module.php`
    - `includes/modules/theme-builder-lite/theme-builder-lite-module.php`
    - `includes/modules/media-folders/media-folders-module.php`
    - `includes/modules/system-status/system-status-module.php`
  - Loads Woo/runtime/support subsystems (`woocommerce/woocommerce-init.php`, integrations, helpers).
- Runtime hook registration hotspots:
  - `includes/modules/*/runtime/*.php`
  - `admin/class-blackwork-site-settings.php` (admin ajax/settings hooks)
  - `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php` (mail marketing ajax/admin)

## 4) Admin Entry Points (High-Level)
- Main Blackwork Site menu/router:
  - [`admin/class-blackwork-site-settings.php`](../../admin/class-blackwork-site-settings.php)
  - Registers top-level + submenu screens (`add_menu_page` / `add_submenu_page`).
- Module admin screens (examples):
  - Media Folders settings: `includes/modules/media-folders/admin/media-folders-settings.php`
  - Header settings: `includes/modules/header/admin/header-admin.php`
  - Theme Builder Lite settings: `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.php`
  - System Status: `includes/modules/system-status/admin/status-page.php`
  - Mail Marketing page: `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`
- Shared Admin UI kit:
  - CSS: `admin/css/bw-admin-ui-kit.css`
  - Enqueue/scoping: `bw_admin_enqueue_ui_kit_assets()` + `bw_is_blackwork_site_admin_screen()` in `admin/class-blackwork-site-settings.php`
  - Scope rule: styles are intended for `.bw-admin-root` Blackwork admin surfaces.

## 5) Module Map (Feature Modules)

### Media Folders (`includes/modules/media-folders/`)
- Purpose: Virtual folders for Media + selected admin list tables (Posts/Pages/Products) with isolated taxonomies.
- Key files:
  - Module: `media-folders-module.php`
  - Admin: `admin/media-folders-admin.php`, `admin/media-folders-settings.php`, `admin/assets/media-folders.js`, `admin/assets/media-folders.css`
  - Runtime: `runtime/ajax.php`, `runtime/media-query-filter.php`
  - Data: `data/installer.php`, `data/taxonomy.php`, `data/term-meta.php`
- Main AJAX endpoints:
  - `bw_media_get_folders_tree`, `bw_media_get_folder_counts`, `bw_media_create_folder`, `bw_media_rename_folder`, `bw_media_delete_folder`, `bw_media_assign_folder`, `bw_media_update_folder_meta`, `bw_mf_toggle_folder_pin`, `bw_mf_set_folder_color`, `bw_mf_reset_folder_color`, `bw_mf_get_corner_markers`
- Important invariants:
  - Admin-only behavior; no file-path/frontend media URL mutation.
  - Folder isolation by post type taxonomy mapping (`attachment/post/page/product`).

### System Status (`includes/modules/system-status/`)
- Purpose: Admin diagnostics dashboard with scoped health checks.
- Key files:
  - Module: `system-status-module.php`
  - Admin: `admin/status-page.php`, `admin/assets/system-status-admin.js`
  - Runtime: `runtime/check-runner.php`, `runtime/checks/*.php`
- Main AJAX endpoint:
  - `bw_system_status_run_check`
- Important invariants:
  - Read-only diagnostics; admin-scoped execution.

### Header (`includes/modules/header/`)
- Purpose: Custom header runtime + admin settings + live product search ajax.
- Key files:
  - Module: `header-module.php`
  - Admin: `admin/header-admin.php`, `admin/header-admin.js`
  - Frontend: `frontend/assets.php`, `frontend/fragments.php`, `frontend/header-render.php`, `frontend/ajax-search.php`
- Main AJAX endpoint:
  - `bw_live_search_products` (+ `nopriv`)
- Important invariants:
  - Header runtime isolated from Media Folders and checkout flows.

### Theme Builder Lite (`includes/modules/theme-builder-lite/`)
- Purpose: Template CPT + resolver runtime for footer/single/archive template application.
- Key files:
  - Module: `theme-builder-lite-module.php`
  - Admin: `admin/theme-builder-lite-admin.php`, `admin/bw-templates-list-ux.php`, `admin/import-template.php`
  - Runtime: `runtime/template-resolver.php`, `runtime/*-runtime.php`, `runtime/template-preview.php`
  - Data/CPT: `cpt/template-cpt.php`, `cpt/template-meta.php`
- Main AJAX endpoints:
  - `bw_tbl_search_preview_products`, `bw_tbl_update_template_type`
- Important invariants:
  - Fail-open resolver behavior (fallback to theme template on mismatch).

### Mail Marketing (Brevo) (`includes/admin/checkout-subscribe/` + `includes/integrations/brevo/`)
- Purpose: Admin + checkout subscription flow and Brevo sync/status operations.
- Key files:
  - Admin: `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`
  - Frontend: `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php`
  - Integration: `includes/integrations/brevo/class-bw-brevo-client.php`, `class-bw-mailmarketing-service.php`
- Main AJAX endpoints (admin):
  - `bw_brevo_test_connection`, `bw_brevo_order_refresh_status`, `bw_brevo_order_retry_subscribe`, `bw_brevo_order_load_lists`, `bw_brevo_user_check_status`, `bw_brevo_user_sync_status`
- Important invariants:
  - Capability + nonce guarded admin operations.

## 6) Data/Authority Surfaces
- **Options** (WordPress options table):
  - Blackwork core flags and module settings (e.g. Media Folders flags in `bw_core_flags`).
- **Taxonomies / terms / term meta**:
  - Media Folders authority via per-post-type taxonomies and term meta.
- **Post meta / user meta**:
  - Used by Theme Builder Lite and Mail Marketing state tracking.
- **Transient/object-cache surfaces**:
  - Used in Media Folders counts/tree/summary and other modules as optimization layers.
- Canonical authority/invariant references:
  - Media Folders spec: [`docs/30-features/media-folders/media-folders-module-spec.md`](../30-features/media-folders/media-folders-module-spec.md)
  - Admin panel map: [`docs/20-development/admin-panel-map.md`](../20-development/admin-panel-map.md)
  - Risk register: [`docs/00-governance/risk-register.md`](../00-governance/risk-register.md)

## 7) Governance & Workflow (AI Mandatory)
- [`docs/00-governance/ai-task-protocol.md`](../00-governance/ai-task-protocol.md): mandatory lifecycle (start template -> implementation -> closure template).
- [`docs/templates/task-start-template.md`](../templates/task-start-template.md): blocking pre-implementation declaration (scope, determinism, docs impact).
- [`docs/templates/task-closure-template.md`](../templates/task-closure-template.md): required closure verification/traceability format.
- [`docs/00-governance/risk-register.md`](../00-governance/risk-register.md): active governance risks and mitigations.
- [`docs/00-planning/decision-log.md`](../00-planning/decision-log.md): architecture/planning decisions and follow-ups.
- [`docs/20-development/admin-panel-map.md`](../20-development/admin-panel-map.md): admin routes, assets, screen contracts.

## 8) “If you want to change X, read Y”
- **Media Folders runtime/filtering/caching**:
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `includes/modules/media-folders/runtime/ajax.php`
  - `includes/modules/media-folders/runtime/media-query-filter.php`
- **Media Folders admin UX (sidebar, DnD, quick filters)**:
  - `includes/modules/media-folders/admin/media-folders-admin.php`
  - `includes/modules/media-folders/admin/assets/media-folders.js`
  - `includes/modules/media-folders/admin/assets/media-folders.css`
- **Admin UI look/placement/scoping**:
  - `docs/20-development/admin-panel-map.md`
  - `admin/css/bw-admin-ui-kit.css`
  - `admin/class-blackwork-site-settings.php`
- **Performance/caching changes**:
  - module spec performance sections + risk register entries (esp. Media Folders scalability)
  - follow “Performance Evidence Recording” in governance protocol.
- **Security/nonce/capability surfaces**:
  - module runtime ajax files + admin handlers
  - governance protocol + risk register before changing guards.

## 9) Notes / Non-goals
- This map is a **navigation aid** only.
- Detailed contracts, invariants, and edge-case behavior remain in module specs, architecture docs, and governed task artifacts.
- If documentation and code diverge, update governed docs via task lifecycle before implementation.

This file is the architectural entry point for AI agents.
If repository structure changes, this file must be updated.
