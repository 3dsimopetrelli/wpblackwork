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

### 3d — media-folders/runtime/ajax.php (1,282 → 51 loader)  ✅ DONE @ 7fb37b5e
Contiguous 3-way split (chosen over crud/meta/cache because read-handlers were non-contiguous — contiguous ranges = lower risk, byte-identical move).

- [x] `ajax-helpers.php` (142) — request/permission/normalize helpers (orig 46–173)
- [x] `folder-counts.php` (706) — counts + cache engine + invalidation hooks (orig 175–866)
- [x] `ajax-handlers.php` (429) — 11 wp_ajax endpoint handlers + shared term helper (orig 868–1282)
- [x] ajax.php → loader: constants + 3 requires + 11 wp_ajax_* registrations
- [x] Static gate: fn-set EXACT (36), hook-set EXACT (16 + 4 closures), bodies byte-identical, php -l clean, lint:main green

### 3b — woocommerce/woocommerce-init.php (2,138 → 117 loader)  ✅ DONE @ 4372882e
Moved 49 callback units (48 top-level + 1 guarded) via the PHP-tokenizer extractor `scratchpad/phpsplit.php`. Loader keeps the `plugins_loaded` init hub + file-scope registrations + requires.

- [x] `runtime/templates.php` — locate_template + related-products assets
- [x] `runtime/account.php` — account/my-account customizations (9)
- [x] `runtime/auth-social.php` — Supabase bridge + social login (11)
- [x] `runtime/checkout.php` — checkout layout/assets/headers/shipping (14 incl. guarded normalize)
- [x] `runtime/order-received.php` — order-received/confirmation + wallet (5)
- [x] `runtime/payment-gateways.php` — Stripe/PayPal/GPay (4)
- [x] `runtime/cart.php` — cart layout/assets + coupon AJAX (4)
- [x] Static gate: fn-set EXACT (50), hook-call set EXACT (49), 49 bodies byte-identical, php -l clean, lint:main green

**TOOLING:** `scratchpad/phpmap.php` (top-level fn map) + `scratchpad/phpsplit.php` (manifest-driven verbatim extractor with docblock capture + rawRanges for guarded fns). Reused for 3a.

### 3a — admin/class-blackwork-site-settings.php (13,557 → 47 loader)  ✅ DONE @ 90b404fa
Procedural (202 top-level + 11 guarded fns). Moved via `scratchpad/phpsplit.php` + `classify_settings.php` (auto-assigned all 202 by name-prefix, 0 unclassified). 15 files under `admin/site-settings/`. Loader keeps constants + 20 top-level registrations + requires. Menu slug `blackwork-site-settings` preserved.

Verification: fn-set EXACT (213), hook-set EXACT (20), all 202 bodies + guarded cluster byte-identical, php -l clean on 16 files, **dynamic load test** (all 15 files included together → 213 bw_ fns, no redeclare/parse/fatal), phpcs no net new violations, lint:main green.

DEFERRED (need live-site validation): options-manager (typed get/save for 299 option calls); inline JS/CSS extraction from tab renderers. Both flagged in the 3a commit body.

Original detailed extraction map (for reference):

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

### 3c — checkout-subscribe-admin.php (3,221 → 2,977 admin + 261 settings)  ✅ DONE (conservative) @ 66690762
The file held TWO classes. Extracted the self-contained settings store; deferred the singleton method-split.

- [x] Extracted `BW_Mail_Marketing_Settings` (guarded, orig lines 19–264) → `class-bw-mail-marketing-settings.php`, required before the admin class.
- [x] Verification: reflection load test (both classes: IDENTICAL method+const sets vs pre-split), moved body + main class body byte-identical, php -l clean, phpcs phpcbf-fixable unchanged (3208), lint:main green.
- [x] DEFERRED (logged, per rule #6): splitting the 67-method `BW_Checkout_Subscribe_Admin` singleton into traits (rendering / handling / Brevo service). Safe via traits but payment-adjacent → needs live-site validation.

---

## Manual smoke (run before merge, on a live site)

_Populated by each sub-phase as it completes._

- **3d:** Media Library → folders tree loads; create / rename / delete / assign folder; set + reset folder color; pin/unpin; counts update.
- **3b:** Shop/cart/checkout/my-account pages render; plugin template overrides still win (`bw_mew_locate_template`); WC assets load; checkout completes.
- **3a:** Blackwork Site → every tab renders and saves; CSV import round-trip; CSV/digital export download; gateway test-connection buttons; redirects; coming-soon toggle. Confirm the `admin/site-settings/asset-validator`-style debug log is clean.
- **3c:** Brevo/checkout-subscribe settings page renders + saves; subscription flow on checkout.

---

**Last action:** Phase 3 COMPLETE on `refactor/phase3-file-splits`. All four sub-phases done (3d, 3b, 3a, 3c-conservative), each a bisectable commit, each passing the static-equivalence gate. Two items deferred to human-reviewed live-site work: the 3a options-manager + inline-asset extraction, and the 3c admin-singleton trait split.

---

## Final summary

Phase 3 (file splits) complete on branch `refactor/phase3-file-splits`, off `main` @ bfc52687. All behavior-preserving; every step verified and committed individually (bisectable). No running WordPress was available, so verification was static-equivalence + dynamic load/reflection tests (no manual browser smoke — see the checklist above for what a human should run before merge).

| Sub | File | Before | After (loader) | Commit |
|---|---|---:|---:|---|
| 3d | media-folders/runtime/ajax.php | 1,282 | 51 (+3 files) | 7fb37b5e |
| 3b | woocommerce/woocommerce-init.php | 2,138 | 117 (+7 files) | 4372882e |
| 3a | admin/class-blackwork-site-settings.php | 13,557 | 47 (+15 files) | 90b404fa |
| 3c | checkout-subscribe-admin.php | 3,221 | 2,977 (+1 file) | 66690762 |

**Tooling (reusable, in scratchpad):** `phpmap.php` (top-level fn map, curly-open-interpolation aware), `phpsplit.php` (manifest-driven verbatim extractor with docblock capture + rawRanges), `bodycmp.php` (per-fn byte-identity diff), `classify_settings.php` (prefix-based target assignment).

**Verification totals:** 3d 36 fns + 16 hooks exact; 3b 50 fns + 49 hooks exact; 3a 213 fns + 20 hooks exact + dynamic load test (213 fns, no redeclare); 3c reflection-identical both classes. All moved bodies byte-identical. `php -l` clean on every file. `lint:main` green throughout (it only lints the bootstrap). Pre-existing per-module style baselines preserved to keep moves byte-identical; no net-new phpcs violations from any move.

**Deferred (need live-site validation, NOT done):**
- 3a: options-manager (typed get/save wrapper for the 299 get_option/update_option calls) and inline JS/CSS extraction from tab renderers → external files + wp_localize_script.
- 3c: trait-based split of the BW_Checkout_Subscribe_Admin singleton (67 methods).
