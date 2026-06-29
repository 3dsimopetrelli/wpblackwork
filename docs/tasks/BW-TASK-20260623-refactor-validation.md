# BW-TASK-20260623 Refactor Validation

- Date: 2026-06-29 10:39:03 CEST
- Branch tested: `main`
- Commit SHA tested: `7c1567a5900a921b62c1b7d336e3663536c84f26`
- Validator: Codex
- Final recommendation: `not safe`

## Scope

Validate the refactor currently merged into `main` without starting new refactor phases and without changing settings or WooCommerce architecture.

## Commands Run

```bash
git status --short --branch
git checkout main
git pull
date "+%Y-%m-%d %H:%M:%S %Z"
git rev-parse HEAD
git branch --show-current
rg --files | rg "composer\.phar$|composer\.json$|composer\.lock$"
git diff --name-only HEAD~30..HEAD -- '*.php'
rg -n "svg-upload-handler|asset-registry|cdn-sri-manager|widget-unregistration|class-bw-widget-helper" blackwork-core-plugin.php includes
ls -l includes/svg-upload/svg-upload-handler.php includes/assets/asset-registry.php includes/assets/cdn-sri-manager.php includes/widgets/widget-unregistration.php includes/class-bw-widget-helper.php
"$HOME/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" vendor/bin/phpcs -d memory_limit=512M --standard=phpcs.xml.dist blackwork-core-plugin.php
"$HOME/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -l blackwork-core-plugin.php
for f in $(git diff --name-only HEAD~30..HEAD -- '*.php'); do "$HOME/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -l "$f"; done
sed -n '30,45p' blackwork-core-plugin.php
sed -n '200,210p' blackwork-core-plugin.php
sed -n '283,290p' blackwork-core-plugin.php
sed -n '1,220p' ../../../wp-config.php
tail -n 80 ../../../wp-content/debug.log
lsof -nP -iTCP -sTCP:LISTEN | head -n 80
curl -I -s http://127.0.0.1
"$HOME/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -r '$m=@new mysqli("127.0.0.1","root","root","blackwork"); ...'
```

## Environment Detection

- PHP path found:
  - `~/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php`
- Composer status:
  - `composer` not available on PATH
  - `composer.phar` not present in this repository checkout
  - `vendor/` is already installed, including `vendor/bin/phpcs`
- Local site registry status:
  - `~/Library/Application Support/Local/sites.json` contains exactly one registered Local site
  - Registered site:
    - name: `Martina Sarritzu`
    - path: `~/Local Sites/martina-sarritzu`
    - domain: `martina-sarritzu.local`
    - database: `local`
    - MySQL port: `10004`
    - HTTP port: `10003`
- BlackWork project path under validation:
  - `/Users/simonezanon/Documents/local site/BlackWork`
- Result:
  - No Local site registration was found for the BlackWork project path under validation

## Check Results

### 1. Latest `main`

- Pass: `git checkout main`
- Pass: `git pull`
- Result: local `main` is up to date with `origin/main`

### 2. Dependency and lint checks

- Fail: `php composer.phar install`
  - `php` is not on PATH in this shell
  - `composer.phar` is not present in the repository
- Fail: `php composer.phar run lint:main`
  - same blocker as above
- Pass with equivalent fallback:
  - local PHP runtime found at `~/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php`
  - `vendor/bin/phpcs` exists
  - fallback lint command passed against `blackwork-core-plugin.php`

### 3. PHP syntax checks on recently changed PHP files

- Pass: `blackwork-core-plugin.php`
- Pass: all PHP files returned by `git diff --name-only HEAD~30..HEAD -- '*.php'`
- Result: no syntax errors detected

### 4. Required extracted files exist and are loaded

- Pass: `includes/svg-upload/svg-upload-handler.php`
- Pass: `includes/assets/asset-registry.php`
- Pass: `includes/assets/cdn-sri-manager.php`
- Pass: `includes/widgets/widget-unregistration.php`
- Pass: `includes/class-bw-widget-helper.php`
- Pass: `blackwork-core-plugin.php` requires them directly
  - `includes/svg-upload/svg-upload-handler.php`
  - `includes/class-bw-widget-helper.php`
  - `includes/assets/asset-registry.php`
  - `includes/widgets/widget-unregistration.php`
  - `includes/assets/cdn-sri-manager.php`

### 5. Runtime smoke test in local WordPress

- Fail: could not verify homepage/shop/category/product/cart popup/header/search/Media Library/SVG upload/Elementor editor in a live local runtime
- Root blockers:
  - `wp-config.php` for this checkout points to:
    - `DB_NAME=blackwork`
    - `DB_USER=root`
    - `DB_PASSWORD=root`
    - `DB_HOST=localhost`
  - The only Local site registered and started is not BlackWork:
    - `Martina Sarritzu`
    - database `local`
    - domain `martina-sarritzu.local`
  - Therefore the active Local runtime does not correspond to the BlackWork project being validated
  - Because the BlackWork Local site is not registered/running, the configured `blackwork` database is unavailable to this WordPress checkout
  - A temporary attempt to check MySQL before detection returned `Connection refused`; after detection, the real issue is confirmed as project/runtime mismatch rather than plugin failure
  - Starting an ad hoc PHP server from the sandboxed session is not permitted and would not solve the missing Local site registration mismatch anyway

### 6. Elementor widget regression test

- Fail: not executable because the local WordPress runtime and Elementor editor were not reachable
- Widgets not runtime-validated:
  - License Table
  - Accordion
  - Newsletter Subscription
  - Button
  - Hero Slide
  - Product Slider
  - Static Showcase
  - Showcase Slide
  - Product Grid
  - Product Details
  - Related Products
  - Tags
  - Title Product

### 7. Browser console / PHP logs

- Fail: browser console and Elementor editor JS could not be checked because runtime was unavailable
- Partial pass: inspected existing `wp-content/debug.log`
- Findings in existing historical log:
  - old fatal errors from `blackwork-plugin-old`
  - Elementor cloud-library 403 fatals from 2025
  - PHP 8.2 deprecations for dynamic properties in `includes/woocommerce-overrides/class-bw-google-pay-gateway.php`
  - missing dependency notice for `bw-presentation-slide-script` requiring `slick-js`
- Note: these log entries are historical and were not generated by this validation session

### 8. Local runtime status

- Pass: detected active Local control runtime on `127.0.0.1:4000`
- Pass: detected active MySQL listener on `127.0.0.1:10004`
- Fail: those running services belong to the registered Local site `Martina Sarritzu`, not the BlackWork project checkout under test
- Exact blocker fixed:
  - narrowed from generic "runtime unavailable" to exact cause:
  - `BlackWork codebase present, but no matching Local site registration/runtime exists for it`
  - `wp-config.php` expects database `blackwork`, while the only running Local site uses database `local`

## Regressions Found

### Confirmed direct regressions caused by the refactor

- None confirmed

### Validation blockers / unresolved risks

- Repository does not contain `composer.phar`, so the exact requested Composer commands are not runnable as written
- Local WordPress runtime for this checkout is not currently executable because the BlackWork project is not the Local site that is currently registered and started
- Because runtime was unavailable, no frontend, admin, Media Library, SVG upload, cart popup, search, or Elementor widget behavior could be conclusively validated

## Fixes Applied

- None

## Screenshots

- None captured
- Reason: local WordPress runtime and Elementor editor were not reachable during this validation session

## Safe / Not Safe Decision

- `not safe`

Reason:

- Static validation passed for file presence, bootstrap wiring, and PHP syntax
- The requested runtime validation could not be completed
- Without live verification of frontend, admin flows, and Elementor widgets, `main` cannot be signed off as safe to continue from
- The exact blocker is environmental and specific:
  - there is no started Local site corresponding to the BlackWork project checkout
  - the only running Local site is `Martina Sarritzu`
  - this checkout's `wp-config.php` expects a different database name (`blackwork`)

## Next Minimal Action

1. Start or register the actual BlackWork Local site in Local so that:
   - the site path matches `/Users/simonezanon/Documents/local site/BlackWork`
   - the configured database `blackwork` exists in that site runtime
   - the Local domain/HTTP service for that site is reachable
2. Re-run only the blocked runtime section of this validation:
   - smoke pages
   - Media Library
   - SVG upload
   - Elementor editor
   - listed widget render/control checks
3. If a regression reproduces in runtime, fix only that direct regression and commit it separately as:
   - `fix(refactor): describe exact regression fixed`
