# Full Codebase Bug Audit

**Date:** 2026-05-16  
**Branch:** main  
**Commit:** f63500f5  
**Scope:** Security, functionality, WooCommerce, Elementor widgets, performance, repo hygiene  
**Total issues found:** 45

---

## Table of Contents

- [P0 — Critical: Breaks Production](#p0--critical-breaks-production)
- [P1 — High: Security Vulnerabilities](#p1--high-security-vulnerabilities)
- [P1 — High: Broken WooCommerce & Payment Logic](#p1--high-broken-woocommerce--payment-logic)
- [P2 — Medium: Wrong Behavior / Silent Data Corruption](#p2--medium-wrong-behavior--silent-data-corruption)
- [P2 — Medium: Performance](#p2--medium-performance)
- [P3 — Low: Logic & Convention Issues](#p3--low-logic--convention-issues)
- [Repo Hygiene](#repo-hygiene)
- [Priority Action Table](#priority-action-table)

---

## P0 — Critical: Breaks Production

### 1. Payment gateway renders empty at checkout

**File:** `woocommerce/templates/checkout/payment.php:29`

`$available_gateways` is never passed to the template when `wc_get_template()` is called from `form-checkout.php`. The variable is undefined, `!empty($available_gateways)` evaluates to `false`, and **no payment gateways are rendered at checkout**. The variable is referenced at lines 36, 39, 44, 45, 454, 455, 464, and 470.

**Fix:** Pass `$available_gateways` in the `$args` array when calling `wc_get_template('checkout/payment.php', ...)`, or fetch it inside the template via `WC()->payment_gateways()->get_available_payment_gateways()`.

---

### 2. Fatal PHP error on the My Account Coupons page

**File:** `includes/woocommerce-overrides/class-bw-my-account.php:1210`

Calls `wc_get_coupons()`, a function that does not exist in WooCommerce core. Any page load that triggers the coupons template crashes with `Call to undefined function wc_get_coupons()` unless the Smart Coupons plugin is installed.

**Fix:** Replace with a `WP_Query` on `post_type = 'shop_coupon'` or the appropriate WooCommerce coupon API.

---

### 3. Apple Pay gateway silently never registers

**File:** `includes/Gateways/class-bw-apple-pay-gateway.php` (never loaded)

The file is never `require_once`'d anywhere in the codebase. `blackwork-core-plugin.php` checks `class_exists('BW_Apple_Pay_Gateway')` to register the gateway with WooCommerce — this check always returns `false`. Apple Pay is permanently disabled with no error or log message.

**Fix:** Add `require_once BW_MEW_PATH . 'includes/Gateways/class-bw-apple-pay-gateway.php';` before the `class_exists` check, or consolidate the file into the active `woocommerce-overrides/` loading path.

---

### 4. `$default_sort_key` undefined in `render_posts()`

**File:** `includes/widgets/class-bw-product-grid-widget.php:1540`

`$default_sort_key` is a local variable assigned inside `render_responsive_filter_drawer_shell()`. It does not exist in the scope of the separate `render_posts()` method. The `data-default-sort-key` attribute on `.bw-fpw-grid` is always an empty string, and a PHP Notice is emitted on every render. The JS falls back to `getDiscoverySortDefaultKey()`, so the sort UI still initialises, but the PHP-controlled initial sort state is lost.

**Fix:** Pass `$default_sort_key` as a parameter to `render_posts()`, or compute it inside `render_posts()` directly.

---

## P1 — High: Security Vulnerabilities

### 5. CSRF bypass — open nonce-refresh endpoint

**File:** `includes/modules/search-engine/adapters/product-grid/product-grid-adapter.php:112–115`  
**Hook registration:** `includes/modules/search-engine/search-engine-module.php:28–29`

`bw_fpw_refresh_nonce` is registered for both authenticated and unauthenticated users (`wp_ajax_nopriv_bw_fpw_refresh_nonce`) with zero verification — no `check_ajax_referer`, no `current_user_can`, no rate-limit check. Any anonymous caller can obtain a valid `bw_fpw_nonce` on demand, negating the CSRF protection on `bw_fpw_filter_posts`, `bw_fpw_get_subcategories`, and `bw_fpw_get_tags`.

```php
// product-grid-adapter.php:112–115
function bw_fpw_ajax_refresh_nonce() {
    wp_send_json_success(['nonce' => wp_create_nonce('bw_fpw_nonce')]);
}
```

**Fix:** Remove `wp_ajax_nopriv_bw_fpw_refresh_nonce` or add at minimum a rate-limit check matching the other endpoints.

---

### 6. PHP exception message leaked to unauthenticated clients

**File:** `includes/modules/search-engine/adapters/product-grid/product-grid-adapter.php:119–124`

```php
wp_send_json_error(['message' => 'server_error', 'debug' => $e->getMessage()]);
```

Raw exception text (which can include table names, file paths, SQL fragments) is sent in the JSON response to any visitor. No `WP_DEBUG` guard.

**Fix:** Replace with `wp_send_json_error(['message' => 'server_error'])` and keep the detail only in `error_log`.

---

### 7. XSS — `apply_filters` return value echoed raw

**File:** `woocommerce/templates/checkout/order-received.php:246`

The initial value passed to `woocommerce_thankyou_order_received_text` is `esc_html()`-encoded, but the filter return value is assigned back to `$message` and echoed raw with a `// phpcs:ignore` suppressor. Any registered callback can inject unescaped HTML including the `$order` object.

**Fix:** `echo wp_kses_post($message);`

---

### 8. XSS — HTML variables echoed without terminal escaping in header templates

**Files:** `includes/modules/header/templates/header.php` (lines 30, 36, 41, 52), `parts/mobile-nav.php` (line 49), `parts/account-module.php` (line 16), `parts/mobile-nav-footer.php` (line 16)

Seven output sites echo pre-built HTML strings (`$logo_html`, `$desktop_menu_html`, `$search_desktop_markup`, `$cart_icon`, `$mobile_menu_html`, `$avatar_html`, `$footer_menu_html`) with `// phpcs:ignore` suppressors. No terminal `wp_kses()` or `wp_kses_post()` call at the output boundary.

**Fix:** Wrap each echo with `wp_kses_post()` and remove the ignore comments.

---

### 9. XSS — `$gateway_id` echoed without escaping at output sites

**File:** `woocommerce/templates/checkout/payment.php:54–55, 68–69, 185–188`

`esc_attr()` is called once at assignment into `$gateway_id`, but bare `echo $gateway_id` is used at every output site. WordPress coding standards require escaping at the point of output.

**Fix:** Replace `echo $gateway_id` with `echo esc_attr($gateway_id)` at each site.

---

## P1 — High: Broken WooCommerce & Payment Logic

### 10. `sanitize_text_field()` on Stripe-Signature header

**File:** `includes/Gateways/class-bw-abstract-stripe-gateway.php:238`

The sibling `BW_Google_Pay_Gateway` (`includes/woocommerce-overrides/class-bw-google-pay-gateway.php:442–444`) has a comment explicitly documenting that `sanitize_text_field()` must NOT be applied to the `HTTP_STRIPE_SIGNATURE` header as it can corrupt the HMAC hex string. The abstract gateway (used by Klarna and Apple Pay) applies it anyway, inconsistently.

**Fix:** Use `wp_unslash($_SERVER['HTTP_STRIPE_SIGNATURE'])` without `sanitize_text_field()`, matching the documented decision in the standalone gateway.

---

### 11. Duplicate `BW_Google_Pay_Gateway` class — dead file in `Gateways/`

**Files:**
- `includes/Gateways/class-bw-google-pay-gateway.php` — `extends BW_Abstract_Stripe_Gateway` (never loaded)
- `includes/woocommerce-overrides/class-bw-google-pay-gateway.php` — `extends WC_Payment_Gateway` (active)

Only the `woocommerce-overrides` version is ever required. The `Gateways/` version is a dead file that also defines the same class name — if ever inadvertently loaded it would cause a fatal redeclaration error.

**Fix:** Delete `includes/Gateways/class-bw-google-pay-gateway.php`.

---

### 12. Mismatched Stripe API versions across gateways

`BW_Stripe_Api_Client` (`includes/Stripe/class-bw-stripe-api-client.php:29`) uses `2023-10-16`. `BW_Google_Pay_Gateway` uses `2024-12-18`. Klarna and Apple Pay PaymentIntents are created against an older API surface than Google Pay, risking behavioral discrepancies.

**Fix:** Centralise the Stripe API version constant and use it everywhere.

---

### 13. `wc_set_customer_auth_cookie()` called before `wp_set_current_user()` in social login

**File:** `includes/woocommerce-overrides/class-bw-social-login.php:539–540`

```php
wc_set_customer_auth_cookie($user->ID);
wp_set_current_user($user->ID);
```

WooCommerce core always calls `wp_set_current_user()` first so `$current_user` is accurate before the auth cookie is set.

**Fix:** Swap the two lines.

---

### 14. Social login stores full display name as `first_name`, no `last_name` extracted

**File:** `includes/woocommerce-overrides/class-bw-social-login.php:517`

```php
wc_create_new_customer($email, $username, wp_generate_password(), ['first_name' => $name]);
```

`$name` is the full Google/Facebook display name (e.g. "John Smith"). `first_name` becomes the full name string; `last_name` is never set. Corrupts WooCommerce billing address fields.

**Fix:** Split `$name` on the first space to extract `first_name` and `last_name` before passing to `wc_create_new_customer`.

---

### 15. `woocommerce_add_error` / `woocommerce_add_success` filters don't exist

**File:** `includes/woocommerce-overrides/class-bw-notice-manager.php:20, 23`

These filters were removed from WooCommerce many versions ago. The registered callbacks never fire. The suppression logic for error-type notices only works through the `woocommerce_add_notice` filter at line 22.

**Fix:** Remove the two dead `add_filter` calls.

---

### 16. `orders` menu item inserted even when endpoint is absent from `$items`

**File:** `includes/woocommerce-overrides/class-bw-my-account.php:21–38`

When `$endpoint === 'orders'`, the code unconditionally inserts the menu item without checking `isset($items['orders'])`. All other endpoints correctly `continue` when missing. If the `orders` endpoint slug is changed or disabled, a broken link is added to the menu.

**Fix:** Add `if (!isset($items['orders'])) { continue; }` before the `orders`-specific label assignment.

---

### 17. Redundant `set_transaction_id()` + `save()` after `payment_complete()`

**File:** `includes/woocommerce-overrides/class-bw-google-pay-gateway.php:274–276, 494–496`

`WC_Order::payment_complete($pi_id)` already sets the transaction ID and saves internally. The subsequent `set_transaction_id($pi_id)` and `save()` are a redundant double DB write.

**Fix:** Remove the `set_transaction_id()` and `save()` calls that follow `payment_complete()`.

---

## P2 — Medium: Wrong Behavior / Silent Data Corruption

### 18. Analytics date ranges wrong due to double timezone offset

**File:** `includes/modules/link-page/link-page-module.php:716–718, 733, 765, 781`

`wp_date()` expects a UTC Unix timestamp and applies the site timezone internally. `current_time('timestamp')` returns the site's local timestamp. Passing local time to `wp_date()` double-applies the UTC offset, shifting `today_start`, `seven_days_start`, and `thirty_days_start` by the site's offset. Records around midnight are incorrectly included or excluded.

**Fix:** Replace `current_time('timestamp')` with `time()` (pure UTC).

---

### 19. `$fallback_image_id` holds a URL, not a JSON-LD `@id` fragment

**File:** `includes/seo/runtime-seo.php:569, 592, 623, 633`

The variable name implies it should be a JSON-LD `@id` fragment identifier. In practice it holds the raw attachment URL. `ImageObject['@id']` becomes equal to `ImageObject['url']`, breaking the JSON-LD cross-referencing contract that Rank Math establishes.

**Fix:** Append `#primaryimage` (or the appropriate fragment) to form a proper `@id` value, or use the attachment URL only for `url` and leave `@id` as a proper URI fragment.

---

### 20. SEO social image state capture may fire after the fallback renderer

**File:** `includes/seo/runtime-seo.php:438–475, 535`

`bw_seo_render_missing_social_image_meta` runs at `wp_head` priority 999. The state-capture callbacks are attached to Rank Math's `rank_math/opengraph/*` filters. If Rank Math outputs its OG tags after priority 999 on `wp_head`, the state flags are not yet set and the fallback emits duplicate OG tags even when Rank Math already has an image.

**Fix:** Lower the fallback renderer priority or hook it to a Rank Math-specific action that fires after OG meta output is complete.

---

### 21. `social_image_id` key referenced in SEO but never populated in link-page settings

**File:** `includes/seo/runtime-seo.php:355–358`

`$settings['social_image_id']` does not exist in `bw_link_page_get_settings()` defaults or in `bw_link_page_sanitize_settings()`. This resolution branch is permanently unreachable.

**Fix:** Either add a `social_image_id` field to the link-page settings schema (with UI) or remove the dead branch.

---

### 22. `get_pages()` may return `false`, passed directly into `foreach`

**File:** `includes/modules/link-page/link-page-module.php:1377, 1401`

`get_pages()` returns `false` on failure. It is passed directly to a function that iterates it with `foreach`, producing a PHP warning `Invalid argument supplied for foreach()`.

**Fix:** `$pages = get_pages([...]) ?: [];`

---

### 23. Product grid: first category always gets `active` class incorrectly

**File:** `includes/widgets/class-bw-product-grid-widget.php:1238–1244`

When `show_all_button=false` and `default_category='all'`, `$has_active_category` starts as `false`, so the first category in the loop always receives the `active` CSS class — showing a false selected state inconsistent with the actual initial query (which loads all categories).

**Fix:** When no specific default category is selected and the "All" button is hidden, either select the first category as a true default (and filter posts accordingly on load) or render no category as initially active.

---

### 24. CSS always registered even when the file does not exist

**File:** `includes/helpers.php:244–254`

`bw_register_widget_assets()` unconditionally registers the CSS style URL regardless of whether the file exists on disk — resulting in a 404 on every page that enqueues it. The JS path has a `file_exists` guard; CSS does not.

**Fix:** Add the same `if (file_exists($css_file))` guard before `wp_register_style`.

---

### 25. `register_setting` default receives live option value instead of static defaults

**File:** `includes/modules/link-page/link-page-module.php:172`

`bw_link_page_get_settings()` (which internally calls `get_option`) is passed as the `default` argument to `register_setting`. This triggers `get_option` twice on every `admin_init` and is semantically wrong — `register_setting`'s `default` is a schema value, not a live database value.

**Fix:** Pass the static `$defaults` array directly instead of calling `bw_link_page_get_settings()`.

---

### 26. `sanitize_text_field` corrupts URL paths with percent-encoded characters in redirects

**File:** `admin/class-blackwork-site-settings.php:5338–5350`

Redirect source paths are stored via `sanitize_text_field()`. Paths with percent-encoded characters (e.g. `/café`) are distorted at save time. The `pre_update_option_bw_redirects` filter normalises the `source` field, but `sanitize_text_field` may have already mangled non-ASCII sequences.

**Fix:** Use `sanitize_url()` or `wp_parse_url()` to extract and normalise only the path component.

---

## P2 — Medium: Performance

### 27. `bw_cleanup_account_description_option` runs on every request with no run-once guard

**File:** `blackwork-core-plugin.php:614–628`

Hooked to `init` at priority 6. Calls `get_option()` three times per request, then `delete_option()` if the values exist — forever, on every frontend and admin page load. No guard to stop running once all three options are confirmed absent.

**Fix:** After confirming all three options are absent, store a flag option to skip subsequent runs.

---

### 28. `wc_get_orders()` queries run on every My Account page load

**File:** `includes/woocommerce-overrides/class-bw-my-account.php:639–813`

`bw_sync_profile_names_from_purchase_data()` and `bw_sync_account_address_fields_from_latest_order()` fire on every `template_redirect` for any user with missing meta fields. No transient or one-time flag prevents repeated DB queries and writes.

**Fix:** Set a user meta flag after the sync completes and skip execution when it is present.

---

### 29. `WC()->mailer()` invoked on `init` hook

**File:** `includes/woocommerce-overrides/class-bw-email-styles.php:9–15`

Calling `WC()->mailer()` on `init` forces premature instantiation and loading of all WooCommerce email classes on every page load. WooCommerce defers mailer instantiation to the `woocommerce_email` action (i.e., only when emails are actually sent).

**Fix:** Hook to `woocommerce_email_styles` or another late email-specific action.

---

### 30. Cart popup style injected unconditionally on every page

**File:** `cart-popup/cart-popup.php:223–226`

`bw_cart_popup_hide_view_cart_css` prints an inline `<style>` in `<head>` on every public page with no check for WooCommerce availability, whether cart popup is enabled, or whether it is an admin page.

**Fix:** Wrap with `bw_cart_popup_should_load_assets()` before registering the `wp_head` hook.

---

### 31. No-op filter registered unconditionally on every request

**File:** `cart-popup/cart-popup.php:213–217`

`bw_cart_popup_hide_view_cart_button` returns `$button` unchanged. The `add_filter` call on `woocommerce_loop_add_to_cart_link` registers a pointless filter on every request.

**Fix:** Remove the function and its `add_filter` call, or implement the intended behaviour.

---

## P3 — Low: Logic & Convention Issues

### 32. `wp_safe_redirect` silently fails for relative-path redirect targets

**File:** `includes/class-bw-redirects.php:334–373`

`wp_safe_redirect` calls `wp_validate_redirect`, which rejects targets with no host component. Relative paths (e.g. `/other-page`) are rejected and the function returns `false`. The page then renders normally with no redirect and no log entry.

**Fix:** Prefix relative targets with `home_url()` before calling `wp_safe_redirect`, or use `wp_redirect` with a pre-validated absolute URL.

---

### 33. `bw_unregister_removed_blackwork_widgets` registered on two hooks — runs twice

**File:** `blackwork-core-plugin.php:671–672`

Both `elementor/widgets/register` and the deprecated `elementor/widgets/widgets_registered` are hooked. On older Elementor versions the first hook passes a `WP_Hook` object instead of the widget manager; `is_object()` passes but `method_exists($manager, 'unregister')` fails, silently skipping the unregistration.

**Fix:** Remove the legacy `elementor/widgets/widgets_registered` hook; keep only `elementor/widgets/register`.

---

### 34. SEO debug marker always reports empty state (priority 1 fires too early)

**File:** `includes/seo/runtime-seo.php:778`

`bw_seo_runtime_debug_frontend_marker` is hooked to `wp_head` at priority 1, before Rank Math or any other hook has run. `$GLOBALS['bw_seo_social_meta_state']` is always empty at that point, making the debug output always report `og_present=no` and `tw_present=no`.

**Fix:** Move the debug marker to `wp_footer` priority 999 so state is fully populated.

---

### 35. Double redirect on product labels save

**File:** `admin/class-blackwork-site-settings.php:1693–1699`

After saving, the handler redirects to `tab=product-labels`. `bw_site_settings_page` does not include `product-labels` in `$allowed_tabs` and immediately redirects again to the dedicated labels page. Every save triggers two HTTP redirects.

**Fix:** Redirect directly to `admin_url('admin.php?page=bw-product-labels-settings')`.

---

### 36. `maybe_show_notice` shows success message to any admin adding `?bw_duplicated=1`

**File:** `includes/class-bw-duplicate-page.php:122–130`

No validation that a duplication actually occurred. Any admin user can trigger the success notice by manually appending `?bw_duplicated=1` to any admin URL.

**Fix:** Pass a signed value (e.g. the new post ID with a nonce) instead of a plain flag.

---

### 37. Newsletter form allows immediate re-submit after success

**File:** `includes/modules/link-page/assets/link-page.js:308, 319–322`

After a successful subscription, `form.reset()` is called, then `finally` re-enables the submit button on the empty form. A user can immediately submit again. No guard prevents a double submission on the success path.

**Fix:** After success, disable the submit button permanently (or hide the form entirely) rather than re-enabling it in `finally`.

---

### 38. Client-side error strings are not translatable

**File:** `includes/modules/link-page/assets/link-page.js:245, 250, 257, 279, 314, 317`

Hardcoded English strings are injected directly via `textContent`. No `wp_localize_script` equivalent for UI strings. Non-English sites always see English error messages.

**Fix:** Pass the translated strings via `wp_localize_script` from PHP and use the localised values in JS.

---

### 39. `fetch keepalive` fallback unreliable for same-tab navigation clicks

**File:** `includes/modules/link-page/assets/link-page.js:35–45, 110`

For links that open in the same tab (`_self`), `sendBeacon` is used first (correct). If `sendBeacon` fails or is unavailable, the fallback is `fetch` with `keepalive: true`. `keepalive` fetch is not supported in Safari pre-16.4. Click events for same-tab links can be silently lost on older Safari.

**Fix:** Add a synchronous XMLHttpRequest as a last-resort fallback, or accept the loss and document the browser limitation.

---

### 40. `get_the_title()` called without post ID in full-page replacement template

**File:** `includes/modules/link-page/templates/template-link-page.php:118`

Relies on the global `$post` being set in a template loaded via the `template_include` filter. `$page_id` is already available in the template at line 26.

**Fix:** `echo esc_html(get_the_title((int) $page_id));`

---

### 41. `bw_get_safe_product_permalink` catches `\Exception` but not `\Error`

**File:** `includes/helpers.php:400–417`

PHP 7+ `TypeError` and `ValueError` extend `\Error`, not `\Exception`, and propagate uncaught through the try/catch block.

**Fix:** Replace `catch (\Exception $e)` with `catch (\Throwable $e)`.

---

### 42. Default empty Instagram social link row silently disappears on first save

**File:** `includes/modules/link-page/link-page-module.php:61–68`

The migration seeds a default Instagram row with `'url' => ''`. The sanitizer discards rows with empty URLs. After the first save the row is gone, but the key `social_links` now exists in the DB (as an empty array), so the migration block is skipped on subsequent loads. The row reappears in the form but vanishes again on save.

**Fix:** Do not seed default rows with empty URLs. Seed no rows and let the UI guide the user.

---

### 43. `data-consent-required` attribute emitted without `esc_attr()`

**File:** `includes/modules/link-page/templates/template-link-page.php:140`

The output is always `'1'` or `'0'` (boolean ternary), so not currently exploitable. Every other attribute in the file uses `esc_attr`. Inconsistency that fails linting.

**Fix:** `echo esc_attr($newsletter_consent_required ? '1' : '0');`

---

## Repo Hygiene

### 44. Stray empty file with garbled name at repo root

**Path:** `connect_error}\n; exit(1);} =->query(SHOW` (repo root)

Empty file committed to git. The filename is a PHP/MySQL error-handling snippet that became a filename due to an accidental shell redirect. Safe to delete.

---

### 45. `admin/.DS_Store` committed to git

**File:** `admin/.DS_Store`

The `.gitignore` entry `.DS_Store` (line 16) lacks a `**/` prefix, so it only matches at the repo root. Subdirectory `.DS_Store` files are unprotected.

**Fix:** Change `.DS_Store` to `**/.DS_Store` in `.gitignore` and remove `admin/.DS_Store` from tracking: `git rm --cached admin/.DS_Store`.

---

### 46. `.wp-env.json` not gitignored

**File:** `.wp-env.json` (untracked, repo root)

Local dev environment config (PHP version, debug flags, plugin download URLs). Not ignored and could be committed accidentally.

**Fix:** Add `.wp-env.json` to `.gitignore`.

---

### 47. Dead `includes/Gateways/class-bw-apple-pay-gateway.php`

This file is never required. The Apple Pay class it defines is the one checked via `class_exists()` in the plugin bootstrap — which always returns `false`. See [Issue 3](#3-apple-pay-gateway-silently-never-registers).

---

### 48. `task-close-template.md` left at repo root

**File:** `task-close-template.md` (repo root)

A filled-out task closure document that was dropped at the root instead of `docs/tasks/`. Move or delete.

---

## Priority Action Table

| Priority | # | Issue | File |
|----------|---|-------|------|
| P0 | 1 | Payment gateway renders empty at checkout | `woocommerce/templates/checkout/payment.php:29` |
| P0 | 2 | Fatal error on Coupons page (`wc_get_coupons`) | `class-bw-my-account.php:1210` |
| P0 | 3 | Apple Pay gateway never registers | `includes/Gateways/class-bw-apple-pay-gateway.php` (never loaded) |
| P0 | 4 | `$default_sort_key` undefined in product grid render | `class-bw-product-grid-widget.php:1540` |
| P1 | 5 | CSRF — open nonce-refresh endpoint | `product-grid-adapter.php:112–115` |
| P1 | 6 | Exception message leaked to unauthenticated clients | `product-grid-adapter.php:119–124` |
| P1 | 7 | XSS — filter return echoed raw in order-received | `checkout/order-received.php:246` |
| P1 | 8 | XSS — HTML vars echoed without terminal wp_kses in header | `header/templates/*.php` |
| P1 | 10 | `sanitize_text_field` on Stripe-Signature header | `class-bw-abstract-stripe-gateway.php:238` |
| P1 | 13 | Auth cookie set before `wp_set_current_user` in social login | `class-bw-social-login.php:539–540` |
| P1 | 14 | Full display name stored as `first_name` in social login | `class-bw-social-login.php:517` |
| P2 | 18 | Analytics dates wrong — double timezone offset | `link-page-module.php:716–718` |
| P2 | 19 | JSON-LD `@id` holds URL instead of fragment identifier | `runtime-seo.php:569` |
| P2 | 23 | First category always gets `active` class incorrectly | `class-bw-product-grid-widget.php:1238–1244` |
| P2 | 24 | CSS registered even when file doesn't exist | `includes/helpers.php:244–254` |
| P2 | 27 | `bw_cleanup_account_description_option` runs every request | `blackwork-core-plugin.php:614–628` |
| P2 | 30 | Cart popup style injected on every page unconditionally | `cart-popup/cart-popup.php:223–226` |
| P3 | 32 | `wp_safe_redirect` silently fails for relative paths | `class-bw-redirects.php:334–373` |
| P3 | 35 | Double redirect on product labels save | `class-blackwork-site-settings.php:1693–1699` |
| Hygiene | 44 | Stray garbled-name file at repo root | `connect_error}\n; exit(1);} =->query(SHOW` |
| Hygiene | 45 | `admin/.DS_Store` committed; `.gitignore` missing `**/` | `admin/.DS_Store` |
| Hygiene | 46 | `.wp-env.json` not gitignored | `.wp-env.json` |
