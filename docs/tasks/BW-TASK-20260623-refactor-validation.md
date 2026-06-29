# BW-TASK-20260623 Refactor Validation

- Date: 2026-06-29 12:33:00 CEST
- Branch tested: `main`
- Commit SHA tested: `5b45de67aa9017f89b86cacf70c61059150cd6f3`
- Runtime target: `http://blackwork.local`
- Validator: Codex
- Final recommendation: `not safe`

## Scope

Validate the refactor currently merged into `main` without starting new refactor phases and without changing settings or WooCommerce architecture.

## Commands Run

```bash
git branch --show-current
git rev-parse HEAD
cat "$HOME/Library/Application Support/Local/sites.json"
lsof -nP -iTCP:10008 -sTCP:LISTEN
lsof -nP -iTCP:10009 -sTCP:LISTEN
curl -I -s http://blackwork.local
curl -L -A 'Mozilla/5.0' -s http://blackwork.local/wp-sitemap.xml
curl -L -A 'Mozilla/5.0' -s http://blackwork.local/wp-sitemap-posts-product-1.xml
curl -L -A 'Mozilla/5.0' -s http://blackwork.local/wp-sitemap-taxonomies-product_cat-1.xml
curl -L -A 'Mozilla/5.0' -s http://blackwork.local/wp-sitemap-posts-page-1.xml
curl -L -A 'Mozilla/5.0' -s http://blackwork.local/
curl -L -A 'Mozilla/5.0' -s http://blackwork.local/shop/
curl -L -A 'Mozilla/5.0' -s http://blackwork.local/product-category/books/
curl -L -A 'Mozilla/5.0' -s http://blackwork.local/product/bats-of-the-world/
curl -L -A 'Mozilla/5.0' -s 'http://blackwork.local/?s=bat'
find '/Users/simonezanon/Local Sites/blackwork/app/public/wp-content' -maxdepth 2 \( -name 'debug.log' -o -name 'error_log' \) -print
"$HOME/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -l includes/assets/asset-registry.php
"$HOME/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" vendor/bin/phpcs -d memory_limit=512M --standard=phpcs.xml.dist blackwork-core-plugin.php
"$HOME/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" vendor/bin/phpcs -d memory_limit=512M --standard=phpcs.xml.dist includes/assets/asset-registry.php
```

## Environment Detection

- PHP path found:
  - `~/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php`
- Composer status:
  - `composer` not available on PATH
  - `composer.phar` not present in this repository checkout
  - `vendor/` is already installed, including `vendor/bin/phpcs`
- Local runtime status:
  - Local site registry includes `BlackWork`
  - path: `~/Local Sites/blackwork`
  - domain: `blackwork.local`
  - database: `local`
  - HTTP port: `10008`
  - MySQL port: `10009`
- Runtime availability:
  - `http://blackwork.local` returned `HTTP/1.1 200 OK`
  - nginx and mysqld listeners for the BlackWork site were active during validation

## Check Results

### 1. Latest `main`

- Pass: runtime checkout is on `main`
- Pass: tested commit resolved to `5b45de67aa9017f89b86cacf70c61059150cd6f3`

### 2. Dependency and lint checks

- Fail: exact `composer run lint:main` could not run
  - `composer` is not installed on PATH in this environment
  - `composer.phar` is not present in the repository
- Pass with equivalent fallback:
  - `php vendor/bin/phpcs -d memory_limit=512M --standard=phpcs.xml.dist blackwork-core-plugin.php`
- Note on modified file lint:
  - `php vendor/bin/phpcs -d memory_limit=512M --standard=phpcs.xml.dist includes/assets/asset-registry.php` reports 53 pre-existing PHPCS violations in the extracted file, mainly missing docblocks and comment punctuation
  - those violations were already present in the extracted monolith slice and were not introduced by this regression fix

### 3. PHP syntax checks on modified PHP files

- Pass: `includes/assets/asset-registry.php`
- Result: no syntax errors detected after the regression fix

### 4. Required extracted files exist and are loaded

- Pass: `includes/svg-upload/svg-upload-handler.php`
- Pass: `includes/assets/asset-registry.php`
- Pass: `includes/assets/cdn-sri-manager.php`
- Pass: `includes/widgets/widget-unregistration.php`
- Pass: `includes/class-bw-widget-helper.php`
- Pass: `blackwork-core-plugin.php` requires them directly

### 5. Runtime smoke test in local WordPress

#### Public frontend routes

- Pass: homepage `/`
- Pass: shop `/shop/`
- Pass: product category `/product-category/books/`
- Pass: single product `/product/bats-of-the-world/`
- Pass: search `/?s=bat`
- Pass: homepage source still contains header runtime markers
- Pass: homepage source still contains header search form markers
- Pass: homepage source still contains cart popup markup and config markers

#### Admin and authenticated runtime

- Fail: Media Library not runtime-validated
- Fail: SVG upload not runtime-validated
- Fail: Elementor editor not runtime-validated
- Root blocker:
  - this validation run had no authenticated admin browser session or browser automation surface attached to the thread
  - public source checks were possible, but interactive wp-admin and Elementor checks were not executable from the available tooling in this run

### 6. Elementor widget regression test

#### Public runtime evidence

- Pass: Newsletter widget assets present on `/` and `/newsletter/`
- Pass: Hero Slide widget assets present on `/`
- Pass: Product Slider widget assets present on `/`
- Pass: Product Grid widget assets present on `/`

#### Not conclusively runtime-validated

- License Table
- Accordion
- Static Showcase
- Product Details
- Remaining requested widgets inside Elementor editor controls
- Root blocker:
  - no authenticated Elementor editor session was available, so control panels, live preview edits, and editor-side JS errors could not be exercised

### 7. Browser console / PHP logs

- Fail: browser console could not be checked because no interactive browser session was attached to the thread
- Fail: Elementor editor JS errors could not be checked for the same reason
- Partial pass: runtime asset 404 regression reproduced and then cleared in public HTML/source
- Partial pass: no `wp-content/debug.log` or `error_log` file was present under the local BlackWork site during this run

### 8. Confirmed regression caused by the refactor

- Confirmed: `includes/assets/asset-registry.php` was extracted from the plugin bootstrap but still built asset paths from its own directory
- Runtime symptom before fix:
  - frontend emitted URLs under `wp-content/plugins/wpblackwork/includes/assets/assets/...`
  - public requests for those URLs returned `404 Not Found`
  - affected confirmed handles included at least:
    - `bw-product-grid-style`
    - `bw-product-grid-js`
    - `bw-product-slider-style`
    - `bw-product-slider-script`
- Root cause:
  - extracted registry used `__DIR__ . '/assets/...` and `plugin_dir_url( __FILE__ ) . 'assets/...` after moving from plugin root into `includes/assets/`
  - file existence checks therefore missed the real files and fell back to `1.0.0`
  - runtime URLs therefore pointed to a non-existent nested directory

### 9. Fixes applied

- Applied direct regression fix in:
  - `includes/assets/asset-registry.php`
- Fix strategy:
  - replaced extracted-registry asset path resolution from local file directory to plugin root constants:
    - `BW_MEW_PATH . 'assets/...` for file existence / filemtime
    - `BW_MEW_URL . 'assets/...` for public URLs
- Post-fix verification:
  - homepage source no longer contains `includes/assets/assets/`
  - runtime now emits:
    - `/wp-content/plugins/wpblackwork/assets/js/bw-product-grid.js`
    - `/wp-content/plugins/wpblackwork/assets/js/bw-product-slider.js`
    - `/wp-content/plugins/wpblackwork/assets/css/bw-product-grid.css`
    - `/wp-content/plugins/wpblackwork/assets/css/bw-product-slider.css`
  - direct HTTP checks for those assets returned `200 OK`

## Screenshots

- None captured
- Reason: no interactive browser session or browser automation tool was available in this run

## Safe / Not Safe Decision

- `not safe`

Reason:

- Public runtime is now materially healthier after the confirmed asset-registry regression fix
- Core public smoke routes load and the broken extracted asset URLs were corrected
- However, the requested validation still remains incomplete because the admin/authenticated half of the runtime could not be exercised
- Without Media Library, SVG upload, Elementor editor, widget control, and console verification, `main` still cannot be signed off as fully safe to continue from

## Next Minimal Action

1. Re-run the blocked admin validation with an authenticated browser session against `blackwork.local`:
   - Media Library
   - SVG upload
   - Elementor editor
   - requested widget control checks
2. Check browser console and Elementor editor console during those steps
3. If no further regressions appear, flip the recommendation from `not safe` to `safe`
