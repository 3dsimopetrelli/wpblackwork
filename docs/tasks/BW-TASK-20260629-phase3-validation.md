# BW-TASK-20260629 Phase 3 Validation

- Date: 2026-07-02
- Validation baseline: current `main`
- Tested commit: `a971773273a0fd2111f6e4b2de7f766adb41cef2`
- Runtime target: `http://blackwork.local`
- Validator: Codex
- Final recommendation: `conditionally safe`

## Baseline sync

Commands run:

```bash
git checkout main
git pull
git status --short --branch
git rev-parse HEAD
git log --oneline -10
```

Result:
- Local checkout is on `main`
- `git pull` reported `Already up to date.`
- HEAD validated: `a971773273a0fd2111f6e4b2de7f766adb41cef2`
- Working tree state at validation start:
  - `## main...origin/main`
  - no modified/untracked files were reported

## Static checks

### Files / modules inspected

Phase 3a site-settings split:
- `admin/class-blackwork-site-settings.php`
- `admin/site-settings/assets.php`
- `admin/site-settings/gateway-validators.php`
- `admin/site-settings/layout-runtime.php`
- `admin/site-settings/menu.php`
- `admin/site-settings/page-dispatch.php`
- `admin/site-settings/product-import-export/csv-template.php`
- `admin/site-settings/product-import-export/digital-csv-builder.php`
- `admin/site-settings/product-import-export/export-engine.php`
- `admin/site-settings/product-import-export/import-engine.php`
- `admin/site-settings/product-import-export/import-locking.php`
- `admin/site-settings/tabs/account.php`
- `admin/site-settings/tabs/cart-shipping-redirect-comingsoon.php`
- `admin/site-settings/tabs/checkout.php`
- `admin/site-settings/tabs/import-product.php`
- `admin/site-settings/tabs/info-layout-labels.php`

Phase 3b WooCommerce split:
- `woocommerce/runtime/account.php`
- `woocommerce/runtime/auth-social.php`
- `woocommerce/runtime/cart.php`
- `woocommerce/runtime/checkout.php`
- `woocommerce/runtime/order-received.php`
- `woocommerce/runtime/payment-gateways.php`
- `woocommerce/runtime/templates.php`
- `woocommerce/woocommerce-init.php`

Phase 3c Checkout Subscribe / Mail Marketing extraction:
- `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`
- `includes/admin/checkout-subscribe/class-bw-mail-marketing-settings.php`
- `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php`

Phase 3d Media Folders split:
- `includes/modules/media-folders/runtime/ajax-handlers.php`
- `includes/modules/media-folders/runtime/ajax-helpers.php`
- `includes/modules/media-folders/runtime/ajax.php`
- `includes/modules/media-folders/runtime/folder-counts.php`
- `includes/modules/media-folders/runtime/media-query-filter.php`

### PHP syntax

Command pattern used:

```bash
php -l <each PHP file changed by commits 90b404fa, 4372882e, 66690762, 7fb37b5e, 7e944fea>
```

Result:
- PASS on all PHP files touched by the Phase 3 commits above
- No syntax errors detected

### Lint

Requested command:

```bash
composer run lint:main
```

Environment result:
- `composer` is not available on PATH in this environment
- `vendor/bin/phpcs` is present

Fallback run:

```bash
php vendor/bin/phpcs -d memory_limit=512M --standard=phpcs.xml.dist blackwork-core-plugin.php
```

Result:
- PASS

## Runtime validation matrix

### Public runtime

| Area | Result | Notes |
| --- | --- | --- |
| Homepage `/` | Fail | Public body is replaced by WooCommerce coming-soon shell |
| About `/about/` | Fail | Same coming-soon shell |
| Shop `/shop/` | Fail | Same coming-soon shell |
| Category `/product-category/books/` | Fail | Same coming-soon shell |
| Single product `/product/bats-of-the-world/` | Fail | Same coming-soon shell |
| Cart `/cart/` | Fail | Same coming-soon shell overlays cart surface |
| Checkout `/checkout/` | Fail | Checkout runtime exists, but coming-soon shell is still present in page output |
| My Account `/my-account/` | Fail | Same coming-soon shell |
| Search `/?s=bat` | Fail | Same coming-soon shell |
| Custom search `/search/` | Pass | Real search results surface renders |
| Newsletter `/newsletter/` | Fail | Same coming-soon shell |
| Links `/links/` | Fail | Same coming-soon shell |

### Asset loading

Checked on:
- `/`
- `/shop/`
- `/product-category/books/`
- `/product/bats-of-the-world/`
- `/cart/`
- `/checkout/`
- `/my-account/`
- `/search/`

Result:
- PASS
- No current plugin CSS/JS 404s reproduced on the tested routes

### Search / cart / checkout flow

Validated with a real session seeded from `/search/`:
- `/search/` renders live results, filters, sort UI, header, cart popup, and `bw-product-grid` runtime
- add-to-cart from search result works
- `/cart/` renders Woo cart runtime
- `/checkout/` renders Woo checkout runtime with `woocommerce-checkout`, `order_review`, and `Place order`

Result:
- Mixed
- Core Woo cart/checkout flow is technically reachable
- Public output still contains the coming-soon shell, which blocks clean validation of intended user-facing runtime

### Admin runtime

Checked routes:
- `/wp-admin/`
- `/wp-admin/admin.php?page=blackwork-site-settings`
- `/wp-admin/upload.php`

Result:
- Blocked by authentication
- All tested admin routes redirect to `wp-login.php`
- No authenticated admin/browser session was available in this validation pass

### Logs / console / AJAX

- `wp-content/debug.log`: not present
- `error_log`: not present under `wp-content`
- Browser console: not available in this pass (no interactive browser tool/session)
- Admin AJAX / media-folders AJAX: not exercisable without authenticated admin session
- Public asset HEAD checks: all tested plugin assets returned `200 OK`

## Confirmed regressions on current HEAD

## Issue 01 — Public runtime is still masked by WooCommerce coming-soon output

Status: confirmed
Severity: critical
Area: WooCommerce / public runtime
Page tested:
- `/`
- `/about/`
- `/shop/`
- `/product-category/books/`
- `/product/bats-of-the-world/`
- `/cart/`
- `/checkout/`
- `/my-account/`
- `/?s=bat`
- `/newsletter/`
- `/links/`

Steps to reproduce:
1. Open any of the routes above on `blackwork.local`.
2. Extract visible text or inspect rendered body content.
3. Compare the page body across routes.

Expected result:
- Each route renders its actual page, shop, product, cart, account, or newsletter content.

Actual result:
- Most tested routes render the same WooCommerce coming-soon shell.
- The body content includes the banner text:
  - `Pardon our dust! We're working on something amazing — check back soon!`
- This masks the actual public runtime for the majority of the site and prevents meaningful validation of the intended frontend architecture.

Console errors:
- Not captured in this pass

Network missing assets:
- None confirmed on current HEAD

PHP/debug.log errors:
- No `debug.log` or `error_log` file was present under `wp-content`

Likely files involved:
- No Phase 3 code file is directly implicated from this pass
- Current evidence points to site/WooCommerce coming-soon configuration or runtime gating rather than a reproduced syntax/loader break in the split modules

Notes:
- This is a current HEAD runtime issue, but it may be configuration-driven rather than caused by the Phase 3 refactor itself.
- It is still a blocker for validating current frontend behavior.

## Validation blockers (not counted as confirmed regressions)

### Blocker 01 — Admin validation could not be executed without authenticated session

Blocked surfaces:
- Blackwork Site menu
- Blackwork Site tabs
- settings save
- Product Import / Export submenu
- Promotions & Labels submenu
- Mail Marketing page
- Media Library / folders UI
- SVG upload
- Elementor editor
- widget controls / live preview
- admin AJAX inspection

Observed behavior:
- `/wp-admin/`, `/wp-admin/admin.php?page=blackwork-site-settings`, and `/wp-admin/upload.php` all redirect to `wp-login.php`
- No authenticated admin/browser session was available in this pass

Impact:
- No confirmed admin-side Phase 3 regressions were reproduced
- Those surfaces remain unvalidated, not passed

## Historical notes only (not current regressions)

- The older Phase 1/2 extracted asset-registry URL regression is **not** reproduced on current HEAD.
- Current HEAD emits working plugin asset URLs and tested asset requests returned `200 OK`.

## Summary

- Confirmed current-HEAD regressions reproduced: 1
- Suspected regressions reported: 0
- Validation blockers: 1 major blocker (admin authentication unavailable), plus public coming-soon masking on most routes
- Highest-risk area: public WooCommerce/site runtime gating, because it prevents meaningful validation of the intended frontend architecture
- Safe / not safe: `conditionally safe`

Reason:
- No confirmed Phase 3 regression remains on current HEAD.
- The previous asset regression is fixed and no longer reproducible.
- Runtime validation is partially complete.
- Remaining gaps are environment blockers, not confirmed code regressions:
  - WooCommerce Coming Soon shell masking public routes
  - no authenticated admin session for wp-admin / Elementor / Media Library

## Pending checklist

- admin login
- Blackwork Site panel
- Media Folders
- SVG upload
- Elementor editor
- widget controls/live preview
- browser console
- admin AJAX
- debug.log
