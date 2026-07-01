# BW-TASK-20260629 — Phase 3 Runbook & Progress Tracker

**Branch:** `refactor/phase3-file-splits` (off `main` @ bfc52687) · **Plan basis:** `BW-TASK-20260623-full-refactoring-plan.md` (Phase 3), updated with the 2026-06-29 structural re-map.

This file is the single source of truth for Phase 3. After each step, update the checkboxes and the **Last action** line so the work is resumable from this doc + `git log` after any context compaction.

---

## Objective

Behavior-preserving split of the four oversized files into focused modules. **Zero logic change** except the explicitly-flagged optional options-manager (last step of 3a). Phases 0–2 (formatting, bootstrap decomposition, widget de-dup) are already complete and merged to `main`.

| Target | Baseline lines | Kind | Risk |
|---|---:|---|---|
| `includes/modules/media-folders/runtime/ajax.php` (3d) | 1,282 | procedural | MED |
| `woocommerce/woocommerce-init.php` (3b) | 2,138 | procedural | MED |
| `admin/class-blackwork-site-settings.php` (3a) | 13,557 | procedural (202 fns) | MED-HIGH |
| `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php` (3c) | 3,221 | **singleton class** | HIGH |

---

## Operating rules (read every iteration)

1. **Behavior-preserving only.** Move code verbatim; do not rewrite logic. The only permitted logic change is the options-manager, which is the OPTIONAL final step of 3a and must be gated behind everything else green.
2. **Preserve every public symbol.** All `bw_*` function names, `add_action`/`add_filter`/`wp_ajax_*` registrations, `wp_register_*` asset handles, and the `blackwork-site-settings` menu slug (≈10 modules attach submenus to it — grep before touching). Add a back-compat shim if any external reference would break.
3. **The static-equivalence gate (per step, since the loop has no live WordPress):**
   - `grep` the **function-definition set** and the **hook-registration set** (`add_action`/`add_filter`/`wp_ajax_*`, space-insensitive) across the file(s) touched → must EXACTLY match the pre-step baseline: zero lost, zero added, zero duplicated. Capture both sets to scratchpad before the move; diff after.
   - `php -l` every touched file.
   - `php ./vendor/bin/phpcs -d memory_limit=512M --standard=phpcs.xml.dist <files>` + `composer run lint:main` (use `vendor/bin/phpcs` directly if `composer` is not on PATH).
4. **One group = one commit**, self-contained and bisectable. Never mix sub-phases in a commit. Conventional message; record the def-set/hook-set match result in the commit body.
5. **Verify before claiming done.** Paste the grep/lint output. Evidence before assertions.
6. **3c is class-based — conservative.** Only extract seams that are statically provable (stateless collaborators). If a clean seam can't be proven without a running site, SKIP the deep split and log it here for human daytime work. Never force a risky class split under the static gate.
7. **Morning manual smoke checklist:** each sub-phase appends its checklist under "Manual smoke (run before merge)" below. These are for the human to run on a live site before merging the branch.

---

## Extraction mechanics (the proven Phase 1 pattern)

For a procedural file `X.php` with function groups G1…Gn:

1. Create `sibling-dir/g1.php` with the `<?php` header + `if ( ! defined( 'ABSPATH' ) ) { exit; }` guard.
2. Move the verbatim function bodies (and their immediately-associated `add_action`/`add_filter` lines) for G1 into it.
3. In `X.php`, replace the removed block with nothing; add `require_once __DIR__ . '/sibling-dir/g1.php';` in the load section (order matters if a group registers hooks on `init`/`admin_init` — keep original include order).
4. Run the static gate. Commit.

`X.php` ends as a thin manifest: header + constants + a block of `require_once` lines + any hook wiring that must stay at top level.

---

## Progress

### 3d — media-folders/runtime/ajax.php (1,282 → ~40 loader)  [ ] 
Handlers are named fns guarded by `if ( ! function_exists() )`, registered by string callback (lines 34–44). Clean move.

- [ ] `ajax-helpers.php` — `bw_mf_ajax_error`, `bw_mf_get_request_post_type`, `bw_mf_get_request_taxonomy`, `bw_mf_user_can_manage_folders`, `bw_mf_ajax_require`, `bw_mf_normalize_object_ids`, other shared helpers
- [ ] `ajax-crud.php` — create/rename/delete/assign folder handlers
- [ ] `ajax-meta.php` — color/pin/meta handlers (`bw_mf_ajax_set_folder_color`, `_reset_folder_color`, `_toggle_folder_pin`, `_update_folder_meta`)
- [ ] `ajax-cache.php` — tree/counts/corner-markers (`bw_mf_ajax_get_folders_tree`, `_get_folder_counts`, `_get_corner_markers`)
- [ ] ajax.php becomes loader (keep the 11 `add_action` registrations at top, or move each next to its handler — pick one, keep set identical)
- [ ] Static gate green + commit

### 3b — woocommerce/woocommerce-init.php (2,138 → ~40 loader)  [ ]
49 functions / 49 hook registrations. Preserve `bw_mew_locate_template()` and `bw_mew_get_checkout_settings()`.

- [ ] `woocommerce/runtime/template-locator.php` — `bw_mew_locate_template` + template override plumbing
- [ ] `woocommerce/runtime/checkout.php` — checkout hook callbacks
- [ ] `woocommerce/runtime/cart.php` — cart hook callbacks
- [ ] `woocommerce/runtime/account.php` — my-account hook callbacks
- [ ] `woocommerce/runtime/assets.php` — WC asset enqueue callbacks
- [ ] `woocommerce/runtime/misc.php` — anything not fitting the above (only if needed)
- [ ] woocommerce-init.php becomes loader
- [ ] Static gate green + commit(s)

### 3a — admin/class-blackwork-site-settings.php (13,557 → ~400 manifest)  [ ]
Procedural, 202 fns. Extract the import/export engine FIRST (≈88% of the file), then the rest. New home: `admin/site-settings/`.

Import/export engine (do these commits first — biggest reduction):
- [ ] `product-import-export/csv-template.php` — template registry + template download (`bw_product_import_template_*`, lines ~1371–1551)
- [ ] `product-import-export/digital-csv-builder.php` — `bw_digital_csv_builder_*` (~1553–2581)
- [ ] `product-import-export/export-engine.php` — `bw_export_*` (~7186–8862)
- [ ] `product-import-export/import-engine.php` — `bw_import_*` core (parse/process/save product+variation, ~9710–13506)
- [ ] `product-import-export/import-locking.php` — `bw_import_*` lock/state/run helpers (~10226–10749)

Remaining groups:
- [ ] `menu.php` — menu registration, submenu ordering, screen detection (`bw_site_settings_menu`, `bw_product_labels_admin_menu`, `bw_site_settings_force_default_submenu`, `bw_is_blackwork_site_admin_screen`, ~21–213)
- [ ] `assets.php` — `bw_site_settings_admin_menu_icon_styles`, `bw_admin_enqueue_ui_kit_assets`, `bw_site_settings_admin_assets` (~117–475)
- [ ] `layout-runtime.php` — frontend layout hooks (`bw_site_layout_*`, ~564–678)
- [ ] `gateway-validators.php` — 5 gateway test-connection AJAX handlers (~683–1199)
- [ ] `page-dispatch.php` — `bw_site_settings_page` main router (~1200–1369)
- [ ] `tabs/info-layout-labels.php` — info, layout, product-labels renderers
- [ ] `tabs/account.php` — account-page + my-account-front renderers
- [ ] `tabs/checkout.php` — checkout renderer (largest single fn, ~4831–6464)
- [ ] `tabs/cart-shipping-redirect-comingsoon.php` — cart-popup, shipping, redirect, coming-soon, loading renderers
- [ ] `tabs/import-product.php` — `bw_site_render_import_product_tab` (~8865–9708)
- [ ] Extract 7 inline `<script>` + 3 `<style>` blocks → `admin/site-settings/assets/js|css/` + `wp_localize_script`
- [ ] class-blackwork-site-settings.php becomes manifest
- [ ] Static gate green + commit(s)
- [ ] **OPTIONAL, higher-risk, gated:** options-manager (`admin/site-settings/options-manager.php`) — typed get/save with defaults, introduced behind existing reads incrementally. Only if all above green. Flag clearly in commit.

### 3c — checkout-subscribe-admin.php (3,221, singleton class)  [ ]
CONSERVATIVE. Only statically-provable, stateless extractions.

- [ ] Assess seams: Brevo settings defaults/validation (candidate for `class-bw-brevo-settings.php` if pure); API/scheduling already delegates to `BW_Brevo_Client`.
- [ ] Extract only provable seams; keep the singleton and all method names intact via delegation.
- [ ] If no clean static-provable seam: SKIP, log the reason here, leave for human review.
- [ ] Static gate green + commit (or documented skip)

---

## Manual smoke (run before merge, on a live site)

_Populated by each sub-phase as it completes._

- **3d:** Media Library → folders tree loads; create / rename / delete / assign folder; set + reset folder color; pin/unpin; counts update.
- **3b:** Shop/cart/checkout/my-account pages render; plugin template overrides still win (`bw_mew_locate_template`); WC assets load; checkout completes.
- **3a:** Blackwork Site → every tab renders and saves; CSV import round-trip; CSV/digital export download; gateway test-connection buttons; redirects; coming-soon toggle. Confirm the `admin/site-settings/asset-validator`-style debug log is clean.
- **3c:** Brevo/checkout-subscribe settings page renders + saves; subscription flow on checkout.

---

**Last action:** Branch created off `main` @ bfc52687; runbook written. Next: execute 3d.
