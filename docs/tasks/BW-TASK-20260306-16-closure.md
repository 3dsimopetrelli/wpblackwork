# BW-TASK-20260306-16 — CDN Subresource Integrity Hardening

## Scope
- `blackwork-core-plugin.php`
- `woocommerce/woocommerce-init.php` (consumed via existing `supabase-js` handle path)
- `metabox/digital-products-metabox.php` (consumed via existing `select2` handle path)
- `docs/tasks/BW-TASK-20260306-16-closure.md`

## Assets Protected with SRI
- `slick-css`
  - URL: `https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css`
  - Integrity: `sha384-MUdXdzn1OB/0zkr4yGLnCqZ/n9ut5N7Ifes9RP2d5xKsTtcPiuiwthWczWuiqFOn`
- `slick-js`
  - URL: `https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js`
  - Integrity: `sha384-YGnnOBKslPJVs35GG0TtAZ4uO7BHpHlqJhs0XK3k6cuVb6EBtl+8xcvIIOKV5wB+`
- `supabase-js`
  - URL: `https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2.43.4/dist/umd/supabase.min.js`
  - Integrity: `sha384-BV2dqVU6K3gwMR3iiAIxuWbMYbnQYo7u3jQXlR9cCWtBUVeIrrcuzn50r50eu9zk`
- `select2` (style)
  - URL: `https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css`
  - Integrity: `sha384-OXVF05DQEe311p6ohU11NwlnX08FzMCsyoXzGOaL+83dKAb3qS17yZJxESl8YrJQ`
- `select2` (script)
  - URL: `https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js`
  - Integrity: `sha384-d3UHjPdzJkZuk5H3qKYMLRyWLAQBJbby2yr2Q58hXXtAGF8RSNO9jpLDlKKPv5v3`

## Exceptions (No SRI Applied)
- Stripe (`https://js.stripe.com/v3/`)
- Google Maps JS API (`https://maps.googleapis.com/maps/api/js?...`)
- Google Fonts CSS endpoint (`https://fonts.googleapis.com/css2?...`)

Reason for exception:
- These providers serve dynamic/versionless responses where strict hash pinning is not stable or operationally compatible.

## Implementation Notes
- Added SRI injection via:
  - `script_loader_tag`
  - `style_loader_tag`
- Injection is strictly handle + URL scoped to pinned static assets only.
- Added `crossorigin="anonymous"` alongside `integrity`.

## Verification Steps
1. Confirm script tags for `slick-js`, `supabase-js`, `select2` contain integrity + crossorigin.
2. Confirm stylesheet tags for `slick-css`, `select2` contain integrity + crossorigin.
3. Confirm Stripe/Maps/Fonts tags remain unchanged (no SRI).
4. Run:
   - `php -l blackwork-core-plugin.php`
   - `php -l woocommerce/woocommerce-init.php`
   - `composer run lint:main`
