# BW-TASK-20260623 — Full Refactoring Plan (branch: `refactoring-by-jc`)

**Author:** JC
**Date:** 2026-06-23
**Goal:** Reduce duplication and over-large files; enforce the standing rule — *keep everything modularized, do not overcrowd entry files with logic that belongs in modules* — without changing runtime behavior.

> **Guiding constraint:** This is a **behavior-preserving** refactor. No feature changes. Every phase ends with `php -l` + `composer run lint:*` green and a manual smoke check. Each phase is a separate commit (and ideally a separate review checkpoint) so any regression is bisectable.

---

## 0. Baseline (measured 2026-06-23)

| File | Lines | Problem |
|---|---:|---|
| `admin/class-blackwork-site-settings.php` | **13,557** | Procedural god-file: menu + 9 tab renderers + 4 gateway AJAX validators + full CSV import/export engine + 299 `get_option`/`update_option` calls + 7 inline `<script>` blocks |
| `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php` | 3,221 | Settings UI + Brevo API client + scheduling in one |
| `woocommerce/woocommerce-init.php` | 2,138 | ~80 inline hook callbacks across template/cart/checkout/account concerns |
| `blackwork-core-plugin.php` | 1,616 | Loader is fine, but ~880 lines of `bw_register_*_assets()` boilerplate + 383 lines of SVG-upload security live inline |
| `includes/modules/media-folders/runtime/ajax.php` | 1,282 | 12+ AJAX handlers (CRUD + meta + cache) in one file |
| New widgets (license-table 1,882 / newsletter 1,623 / accordion 615) | — | Copy-pasted Elementor control blocks; ~6,000 phpcs violations (97% auto-fixable) |

The official lint gate (`lint:main`) only covers `blackwork-core-plugin.php` and passes clean. All the debt above is **outside** the gate.

---

## Phase 0 — Formatting debt (mechanical, risk: NONE)

Clear the phpcs backlog on the new/changed files so later diffs are reviewable.

1. `php ./vendor/bin/phpcbf -d memory_limit=512M --standard=phpcs.xml.dist <new files>` on the 10 files flagged in the audit.
2. Re-run `phpcs --report=summary` to confirm only non-auto-fixable items remain; fix those by hand or document why they stay.
3. `php -l` every touched file; `composer run lint:main`.
4. Commit: `style: phpcbf auto-fix formatting debt on new widgets/cart-popup/media-folders`.

**Verification:** phpcs summary error count drops from ~5,782 to the small non-auto-fixable remainder. No behavior change (whitespace only).

---

## Phase 1 — Decompose the bootstrap (`blackwork-core-plugin.php`, risk: LOW)

Extract self-contained concerns into modules; bootstrap keeps only constants + loader + init hooks.

| Extract | Source lines | New file | Notes |
|---|---|---|---|
| SVG-upload security (7 pure fns) | ~35–418 | `includes/svg-upload/class-bw-svg-upload-handler.php` | No external state; wrap as static class, register hooks from a small init fn |
| Asset registration boilerplate (~50 `bw_register_*` fns) | ~731–1616 | `includes/assets/class-bw-asset-registry.php` | Convert repeated register/enqueue bodies to a **declarative config array** + one shared `register_asset()` helper using `filemtime()` |
| CDN/SRI attribute injection | ~797–885 | `includes/assets/class-bw-cdn-sri-manager.php` | Self-contained filter |
| Deprecated-widget unregistration | ~702–729 | `includes/widgets/class-bw-widget-unregistration.php` | Defensive cleanup |

**Result:** bootstrap ~1,616 → ~500 lines. **Keep every existing function name** (or wire back-compat shims) because asset handles like `bw-*-style/script` are referenced elsewhere — verify with grep before deleting any registration fn.

**Verification:** load a page with each widget; confirm all `bw-*` styles/scripts still enqueue (Network tab / `wp_style_is`). `composer run lint:main` + `lint:strict`.

---

## Phase 2 — Widget control de-duplication (risk: LOW–MED)

Add shared helpers to `includes/class-bw-widget-helper.php`, then refactor the new widgets to use them.

New `BW_Widget_Helper` methods:
1. `register_widget_dependencies($name)` → kills the repeated `get_style_depends`/`get_script_depends` boilerplate (license-table, accordion).
2. `add_color_var_control($w,$id,$label,$selector,$css_var,$default)` → the COLOR + CSS-variable block repeated **20+ times**.
3. `add_typography_color_pair($w,$base,$label,$selector,$css_var,$default)` → repeated **5+ times**.
4. `add_dimensions_control($w,$id,$label,$selector,$defaults,$units)` → padding/border-radius block, **4+ times**.
5. `get_svg_allowed_tags()` + `sanitize_svg_content($svg)` → merge the two near-identical whitelists (accordion = superset).
6. `sanitize_html_tag($tag,$allowed,$default)` → the title-tag guard.

Refactor consumers: `class-bw-license-table-widget.php`, `class-bw-accordion-widget.php`, `class-bw-newsletter-subscription-widget.php`, `class-bw-button-widget.php` (SVG only).

**Risk control:** Elementor control output must be byte-identical. Refactor **one widget at a time**, diff the rendered control panel + frontend CSS variables before/after. Saves ~300+ lines.

**Verification:** open each widget in Elementor editor, confirm all controls present and styling applies; `lint:strict` on `includes`.

---

## Phase 3 — Split the oversized files (the main structural win)

### 3a. `admin/class-blackwork-site-settings.php` (13,557 → ~600 dispatcher) — risk: MED-HIGH

It's procedural, so extraction = moving function groups into includes and `require_once`-ing them from a thin orchestrator. Mapped boundaries:

| Group | Source fns / lines | New file (`admin/site-settings/`) |
|---|---|---|
| Menu + screen detection + asset enqueue | 21–475 | `menu.php` |
| Site Layout runtime (default/sanitize/get/css/body-class/wrap) | 476–682 | `layout-runtime.php` |
| Gateway test-connection AJAX (GPay/Maps/Klarna/ApplePay/verify-domain) | 683–1199 | `gateway-validators.php` |
| Page dispatch (`bw_site_settings_page`) | 1200–1370 | `page-dispatch.php` |
| **CSV import/export engine** (template registry, parse, validate, URL convert, generate, download) | 1371–2605 | `product-import-export/` (split further: `csv-template.php`, `csv-validate.php`, `csv-generate.php`, `csv-download.php`) |
| Tab renderers: info, layout, product-labels | 2606–3197 | `tabs/info-layout-labels.php` |
| Tab renderers: account, my-account-front | 3198–4830 | `tabs/account.php` |
| Tab renderers: checkout | 4831–6466 | `tabs/checkout.php` |
| Tab renderers: cart-popup, shipping, (redirect, coming-soon, loading) | 6467–end | `tabs/cart-shipping-misc.php` |
| 7 inline `<script>` blocks (lines 1338/2684/2915/4545/5765/6414/6877) | — | `assets/js/admin-site-settings.js` + `wp_localize_script` |
| 299 `get_option/update_option` calls | scattered | `class-bw-admin-options-manager.php` (typed get/save with defaults) — **incremental**, do not big-bang |

**Approach:** move one group per commit, `require_once` it back from the original file (now a manifest), keep all function names. The options-manager is the only *logic* change — introduce it gradually behind the existing reads.

### 3b. `woocommerce/woocommerce-init.php` (2,138 → ~40 loader) — risk: MED
Split ~80 inline hook callbacks into `woocommerce/runtime/`: `template-locator.php`, `checkout-customizer.php`, `cart-customizer.php`, `account-customizer.php`, `asset-enqueuer.php`. Init file just `require`s + registers.

### 3c. `includes/admin/checkout-subscribe/...-admin.php` (3,221) — risk: MED
Separate the three concerns: `class-bw-brevo-settings.php` (defaults/validation), `class-bw-checkout-subscribe-admin.php` (UI only), `class-bw-brevo-subscription-service.php` (API + scheduling).

### 3d. `includes/modules/media-folders/runtime/ajax.php` (1,282) — risk: MED
Split by domain: `ajax-crud.php` (create/rename/delete/assign), `ajax-meta.php` (color/pin), `ajax-cache.php` (tree/counts).

**Verification (each sub-phase):** `php -l` all moved files; `lint:strict` on the touched dir; manual smoke — settings page renders all tabs + saves; CSV import round-trip; WC checkout/cart/account load; media-folders CRUD works.

---

## Token estimate (full completion)

Estimation basis: dense PHP ≈ ~12 tokens/line. Cost per phase ≈ (source reads, often 2–3× for large files) + (new/edited code output) + (lint cycles + their output) + (verification + fix iterations). Figures are **single-threaded** (one main agent); fanning work to subagents trades ~1.5–2× more total tokens for wall-clock speed.

| Phase | Work | Est. tokens |
|---|---|---:|
| 0 | phpcbf auto-fix + verify 10 files | ~150K |
| 1 | Bootstrap extraction (4 modules) + verify | ~400K |
| 2 | 6 helper methods + refactor 4 widgets + verify | ~480K |
| 3a | Settings god-file split (~10 includes, CSV engine, options mgr) — re-reads of a 13.5k-line file dominate | ~1.1M |
| 3b | woocommerce-init split | ~300K |
| 3c | checkout-subscribe split | ~350K |
| 3d | media-folders ajax split | ~175K |
| — | Cross-phase: review cycles, regression fixes, commits, plan upkeep (+~25%) | ~740K |
| **Total** | | **≈ 3.7M tokens** |

**Headline: full refactoring ≈ 3.5M–4.5M tokens (point estimate ~3.7M), ±30%.**
Phase 3a alone is ~30% of the budget because the 13.5k-line file must be read/re-read repeatedly. If budget-constrained, **Phases 0–2 (~1.0M tokens) deliver most of the de-duplication and bootstrap cleanup at low risk**; Phase 3 is the larger structural payoff but carries the regression risk and the bulk of the cost.

---

## Execution order & checkpoints

1. Phase 0 → commit → quick review.
2. Phase 1 → commit → smoke check assets.
3. Phase 2 → one widget per commit → editor + frontend diff.
4. Phase 3d → 3b → 3c → 3a (smallest/safest first; settings god-file last when the pattern is proven).
5. Open PR `refactoring-by-jc → main` only after all phases green; or merge phase-by-phase if preferred.

**Rollback:** every phase is an isolated commit on `refactoring-by-jc`; revert the offending commit without losing the rest.
