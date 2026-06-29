# Asset Registration & Paths

How CSS/JS assets are registered and enqueued for the Elementor widgets and
shared features, the path rule that must always be followed, and the
debug-time guard that protects against the regression class first seen in
`BW-TASK-20260629`.

## The path rule (non-negotiable)

**Always build asset paths from the plugin-root constants, never from
`__FILE__` / `__DIR__`.**

| Use | For |
| --- | --- |
| `BW_MEW_URL . 'assets/...'`  | the public URL passed to `wp_register_*` / `wp_enqueue_*` |
| `BW_MEW_PATH . 'assets/...'` | the filesystem path passed to `file_exists()` / `filemtime()` |

Both constants are defined in `blackwork-core-plugin.php` and point at the
plugin root (where the `assets/` directory actually lives).

### Why `__FILE__` / `__DIR__` are forbidden here

`includes/assets/asset-registry.php` lives two directories below the plugin
root. `plugin_dir_url( __FILE__ )` / `__DIR__` therefore resolve to
`includes/assets/`, so an asset reference such as
`plugin_dir_url( __FILE__ ) . 'assets/js/bw-product-slider.js'` produces:

```
.../wp-content/plugins/wpblackwork/includes/assets/assets/js/bw-product-slider.js   ← 404
```

instead of the correct:

```
.../wp-content/plugins/wpblackwork/assets/js/bw-product-slider.js
```

This is exactly what happened during the Phase 1 bootstrap extraction
(`BW-TASK-20260623`): the asset code was moved out of the root bootstrap
"verbatim", but `__FILE__`/`__DIR__` are relative to the file, so every URL
silently gained an extra `includes/assets/` segment. The symptoms were broad
and non-obvious — HTTP 404s, Product Slider never initialising, Product Grid
assets missing, embla/dependent scripts not loading — because a failed asset
load does not raise a PHP error.

The fix (`BW-TASK-20260629`) converted all 74 references in
`asset-registry.php` to `BW_MEW_URL` / `BW_MEW_PATH`.

> Note: a module that is fully self-contained (e.g.
> `includes/modules/media-folders/`) ships its assets *next to* its PHP, so
> `plugin_dir_url( __FILE__ )` is correct there. The rule above is specifically
> for code under `includes/assets/` that serves plugin-root `assets/`.

## Registration patterns

Two equivalent styles exist; both must obey the path rule.

1. **Helper (preferred for simple widgets)** —
   `bw_register_widget_assets( $slug, $deps = ['jquery'], $register_js = true )`
   in `includes/helpers.php`. It registers `bw-{slug}-style` /
   `bw-{slug}-script` from `assets/css/bw-{slug}.css` and
   `assets/js/bw-{slug}.js` using the constants and `filemtime()` versioning.

2. **Inline functions** in `includes/assets/asset-registry.php` — used where a
   widget needs custom handles, extra dependencies (embla, product-card,
   masonry), or `wp_localize_script()` payloads. These follow the same
   `file_exists()`-guarded `filemtime()` versioning pattern.

### Dependency registration

- Shared/base handles are registered first so widgets can depend on them:
  `embla-js` → `embla-autoplay-js` → `bw-embla-core-js`/`bw-embla-core-css`,
  `bw-product-labels-style` → `bw-product-card-style`, `bw-wallpost-style`.
- Slider/showcase widgets depend on the embla core triplet
  (`embla-js`, `embla-autoplay-js`, `bw-embla-core-js` + `bw-embla-core-css`).
- Product-card-based widgets (related-products, product-grid, mosaic-slider)
  depend on `bw-product-card-style` (which itself depends on
  `bw-product-labels-style`).
- Widgets that expose assets via Elementor's `get_style_depends()` /
  `get_script_depends()` only register on `init` and enqueue in the editor via
  `elementor/editor/after_enqueue_scripts`; Elementor handles frontend enqueue.
- Versioning is always `filemtime()` of the on-disk file (cache-busting), with
  a static fallback string when the file is missing.

## Debug-time regression guard

`includes/assets/asset-validator.php` walks every registered style/script
handle served from `BW_MEW_URL`, maps the URL back to a filesystem path, and
logs any handle whose file does not exist. It runs **only** when both
`WP_DEBUG` and `WP_DEBUG_LOG` are enabled (zero overhead in production) and
fires late on `wp_print_footer_scripts` (frontend) and
`admin_print_footer_scripts` (admin / Elementor editor).

A reintroduced path bug now surfaces immediately in `wp-content/debug.log`:

```
[BW asset validator frontend] Missing script file for handle "bw-product-slider-script": .../includes/assets/assets/js/bw-product-slider.js (src: ...)
```

To use it locally, enable in `wp-config.php`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

then load a page (or open the Elementor editor) and check `debug.log`.

## Release runtime smoke checklist

Static checks (`php -l`, `composer run lint:main`) do **not** catch asset 404s.
Before merging any structural/asset refactor, with `WP_DEBUG_LOG` on, verify:

- [ ] `debug.log` contains no `[BW asset validator ...]` lines after loading the
      home page, `/shop/`, a product page, and a category page.
- [ ] Browser DevTools **Network** tab shows no 404 for any `assets/css/` or
      `assets/js/` request.
- [ ] Browser **Console** is clean (no missing-dependency or undefined errors).
- [ ] Open the **Elementor editor**: widget panel loads, widgets render, style
      controls (typography/color) update the live preview.
- [ ] Spot-check the asset-heavy widgets: Product Slider, Hero Slide, Static
      Showcase, Product Grid, Newsletter, plus Cart Popup, Search and Header.
- [ ] `wp-content/debug.log` (PHP) shows no new warnings/notices from the plugin.

See also: [linting-and-phpcs-baseline.md](linting-and-phpcs-baseline.md).
