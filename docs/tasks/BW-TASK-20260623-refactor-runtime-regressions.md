# BW-TASK-20260623 Refactor Runtime Regressions

- Date: 2026-06-29
- Local site: `blackwork.local`
- Plugin path: `~/Local Sites/blackwork/app/public/wp-content/plugins/wpblackwork`
- Base commit under validation: `7c1567a5900a921b62c1b7d336e3663536c84f26`
- Current inspected HEAD: `5092b3545c4a0e72185dac3a05936fefdd453bde`
- Scope: runtime inspection only, no refactor work, no new fixes applied in this pass

## Runtime notes

- Public routes are reachable on `blackwork.local`.
- A previous direct regression from the refactor base was already reproduced earlier in this validation cycle:
  - extracted asset registry emitted plugin asset URLs under `includes/assets/assets/...`
  - that regression was fixed later on `main` in commit `22ef51b4`
  - it is included below because it is a confirmed post-refactor runtime regression against the requested base commit
- The current local runtime is heavily affected by a WooCommerce coming-soon shell on most public routes, which blocks meaningful public validation of many widgets and page templates.
- Admin-only checks remain blocked in this pass because no authenticated wp-admin / Elementor browser session was available.

## Issue 01 — Extracted asset registry emitted broken plugin-root paths

Status: confirmed
Severity: critical
Area: assets
Page tested:
- `/`
- asset URLs emitted from the public homepage source during prior reproduction against the refactor validation target

Steps to reproduce:
1. Load homepage source on the refactor build under validation.
2. Inspect emitted plugin CSS/JS URLs for `bw-product-grid` and `bw-product-slider`.
3. Request the emitted asset URLs directly.

Expected result:
- Asset URLs resolve under `/wp-content/plugins/wpblackwork/assets/...`
- CSS/JS for registry-managed widgets returns `200 OK`

Actual result:
- Extracted registry emitted URLs under `/wp-content/plugins/wpblackwork/includes/assets/assets/...`
- Affected assets returned `404 Not Found`
- Confirmed affected handles included:
  - `bw-product-grid-style`
  - `bw-product-grid-js`
  - `bw-product-slider-style`
  - `bw-product-slider-script`

Console errors:
- Not captured in an interactive browser during this pass

Network missing assets:
- `http://blackwork.local/wp-content/plugins/wpblackwork/includes/assets/assets/js/bw-product-grid.js`
- `http://blackwork.local/wp-content/plugins/wpblackwork/includes/assets/assets/js/bw-product-slider.js`
- `http://blackwork.local/wp-content/plugins/wpblackwork/includes/assets/assets/css/bw-product-grid.css`
- `http://blackwork.local/wp-content/plugins/wpblackwork/includes/assets/assets/css/bw-product-slider.css`

PHP/debug.log errors:
- No local `debug.log` file was present during this pass

Likely files involved:
- `includes/assets/asset-registry.php`
- `includes/widgets/class-bw-product-grid-widget.php`
- `includes/widgets/class-bw-product-slider-widget.php`

Notes:
- This is a confirmed post-refactor regression against base commit `7c1567a5900a921b62c1b7d336e3663536c84f26`.
- It is no longer reproducible on the current inspected HEAD because a follow-up fix already landed later on `main`.

## Issue 02 — Most public pages render WooCommerce coming-soon shell instead of page/product content

Status: confirmed
Severity: critical
Area: WooCommerce
Page tested:
- `/`
- `/about/`
- `/newsletter/`
- `/links/`
- `/shop/`
- `/product-category/books/`
- `/product/bats-of-the-world/`
- `/?s=bat`

Steps to reproduce:
1. Open any of the listed public routes.
2. Strip the HTML down to visible text or inspect the body content directly.
3. Compare the visible body content across routes.

Expected result:
- Homepage renders homepage content.
- About renders About page content.
- Newsletter/Links render their own templates.
- Shop/category/product render real WooCommerce catalog/product content.

Actual result:
- Most tested public routes render the same shell with a WooCommerce coming-soon banner:
  - `Pardon our dust! We're working on something amazing — check back soon!`
- Product title content is missing from product body content:
  - `Bats of the World` appears in metadata only
  - it does not appear in the rendered `<body>` content
- Shop/category pages likewise do not expose real product-card content in the rendered body during public inspection
- Standard search route `/?s=bat` is also covered by the same coming-soon shell
- This blocks meaningful runtime validation for many frontend templates and widgets because the real page body is not being served publicly

Console errors:
- Not captured in an interactive browser during this pass

Network missing assets:
- None confirmed on the current inspected HEAD

PHP/debug.log errors:
- No local `debug.log` file was present during this pass

Likely files involved:
- No `wpblackwork` file is confirmed yet from this pass
- Runtime markup points to WooCommerce coming-soon output (`woocommerce-coming-soon-banner`)
- Likely involves WooCommerce/site configuration rather than the extracted plugin bootstrap directly

Notes:
- This is the biggest runtime blocker on the current local site.
- It prevents reliable public validation of homepage, About, Newsletter, Links, Shop, category, and product body output.
- `/search/` is a notable exception and appears to bypass this shell.

## Issue 03 — Standard search route and custom search route diverge sharply

Status: confirmed
Severity: high
Area: search
Page tested:
- `/?s=bat`
- `/search/`

Steps to reproduce:
1. Open `http://blackwork.local/?s=bat`.
2. Open `http://blackwork.local/search/`.
3. Compare rendered body content.

Expected result:
- Both search surfaces should either render valid search results or intentionally resolve through the same search experience.

Actual result:
- `/?s=bat` renders the same coming-soon shell described above and does not expose real search results.
- `/search/` renders the custom filtered search surface with real product results, filters, sort options, and add-to-cart CTAs.
- This creates two very different runtime behaviors for search depending on route.

Console errors:
- Not captured in an interactive browser during this pass

Network missing assets:
- None confirmed on the current inspected HEAD

PHP/debug.log errors:
- No local `debug.log` file was present during this pass

Likely files involved:
- `includes/modules/search-surface/search-surface-module.php`
- `includes/modules/search-surface/runtime/search-results-page.php`
- `includes/modules/search-surface/frontend/search-surface-template.php`
- WooCommerce/site coming-soon configuration affecting default search route

## Issue 04 — `illustrated-books` / product-slider route not present on local runtime

Status: suspected
Severity: medium
Area: widget
Page tested:
- `/illustrated-books/`

Steps to reproduce:
1. Open `http://blackwork.local/illustrated-books/`.

Expected result:
- A valid public page hosting the illustrated books / product slider surface, if that route is expected in this environment.

Actual result:
- Route returns `404 Not Found`.
- No matching page was found in the public sitemap/REST page list during this pass.

Console errors:
- Not captured in an interactive browser during this pass

Network missing assets:
- N/A

PHP/debug.log errors:
- No local `debug.log` file was present during this pass

Likely files involved:
- No plugin file confirmed from this pass
- Could be missing local content/config rather than code

Notes:
- This is marked suspected because the route may simply not exist in this local dataset.
- It still blocks the requested runtime validation of the “illustrated books / product slider page” surface.

## Issue 05 — Admin-side widget validation remains blocked without authenticated session

Status: confirmed
Severity: medium
Area: Elementor
Page tested:
- Media Library
- Elementor editor
- widget control panels
- SVG upload flow

Steps to reproduce:
1. Attempt to validate admin-side runtime without an authenticated wp-admin / Elementor browser session.

Expected result:
- Open Media Library.
- Open Elementor editor.
- Verify widget controls, live preview updates, console, and SVG upload behavior.

Actual result:
- Could not be executed from the available tooling in this pass.
- No authenticated admin/browser session was attached, so the following could not be reproduced:
  - Media Library open
  - SVG upload
  - Elementor editor open
  - style control preview updates
  - Elementor editor JS errors

Console errors:
- Not captured

Network missing assets:
- Not captured in wp-admin

PHP/debug.log errors:
- No local `debug.log` file was present during this pass

Likely files involved:
- Not enough evidence yet
- Candidate areas once admin access is available:
  - `includes/svg-upload/svg-upload-handler.php`
  - `includes/widgets/`
  - `includes/assets/asset-registry.php`
  - `includes/widgets/widget-unregistration.php`

Notes:
- This is a validation blocker rather than a proven code regression.
- It matters because the requested widget-control checks cannot be signed off from public runtime alone.

## Widget coverage notes

Observed in current public runtime:
- `/search/` renders real searchable product results and add-to-cart CTAs
- Embla-family dependencies are emitted on homepage source:
  - `embla-js`
  - `embla-autoplay-js`
  - `bw-embla-core-js`
  - `bw-showcase-slide-script-js`
  - `bw-hero-slide-script-js`
- Current inspected HEAD does **not** show missing plugin CSS/JS 404s on the tested public routes after the follow-up asset-registry fix

Not conclusively validated in this pass because public content is masked or admin access is missing:
- Product Slider
- Mosaic Slider
- Hero Slide
- Static Showcase
- Showcase Slide
- Product Grid
- Related Products
- License Table
- Accordion
- Newsletter Subscription
- Button
- Tags
- Title Product
- Product Details

## Summary

- total confirmed issues: 4
- total suspected issues: 1
- highest-risk area: public frontend runtime is masked by WooCommerce coming-soon output, which blocks meaningful validation of real page/product/widget rendering
- recommended fix order:
  1. Confirm whether WooCommerce coming-soon mode is intentionally enabled in the local dataset; disable or bypass it for validation if not intentional
  2. Re-test homepage, About, Newsletter, Links, Shop, category, and single product after real page content is visible
  3. Re-run admin-side validation with authenticated Elementor/wp-admin session
  4. Validate the illustrated-books / product-slider route against the expected local content set
  5. Keep the asset-registry regression in the bug history for the refactor base commit, but do not reopen it on current HEAD unless it reappears
- whether main is safe or not safe: `not safe`
