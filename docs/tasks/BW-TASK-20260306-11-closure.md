# BW-TASK-20260306-11 — FPW Cache Key Determinism Hardening

## Scope
- `blackwork-core-plugin.php`
- `docs/00-governance/risk-register.md`

## Implementation Summary
- Replaced truncation-based FPW cache key generation with deterministic hashed keys.
- Added `bw_fpw_generate_cache_key($params)` to compute `bw_fpw_{sha256}` from canonical payload serialized with `wp_json_encode()`.
- Added canonical array normalization helper for `subcategories` and `tags` (int cast, dedupe, numeric sort).
- Updated `bw_fpw_filter_posts()` cache-key construction to use helper and removed `implode(...)+substr(...,172)` strategy.
- Preserved existing cache TTL, cache bypass for `order_by=rand`, transient APIs, and response schema.

## Canonical Payload Fields
- `schema`
- `widget_id`
- `post_type`
- `category`
- `subcategories`
- `tags`
- `image_toggle`
- `image_size`
- `hover_effect`
- `open_cart_popup`
- `order_by`
- `order`
- `per_page`
- `page`

## Determinism Explanation
- Identical normalized filter inputs now always produce the same key.
- Different filter combinations produce different hash outputs.
- Array input order no longer affects key identity because arrays are canonicalized.
- Key length is stable and safely below WordPress transient key limits.

## Verification Steps
1. Confirm no `substr($transient_key, 0, 172)` remains in FPW cache path.
2. Confirm `bw_fpw_filter_posts()` uses `bw_fpw_generate_cache_key()`.
3. Validate same inputs -> same key, reordered arrays -> same key, changed filter -> different key.
4. Validate `order_by=rand` still bypasses cache.
5. Run mandatory checks:
   - `php -l blackwork-core-plugin.php`
   - `composer run lint:main`
