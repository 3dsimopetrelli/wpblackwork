# BW-TASK-20260307 — Radar Batch 3 Performance Validation

Date: 2026-03-07  
Type: Validation-only (no runtime code changes)  
Reference: Radar Batch 3 — Performance Analysis

## Validated findings summary

### High
- #1 `bw_mew_sync_supabase_user_on_load()` external HTTP on authenticated page load path.  
  - Location: `includes/woocommerce-overrides/class-bw-supabase-auth.php`  
  - Classification: cross-domain resilience/performance risk.

### Medium
- #4 High number of separate asset requests from per-file enqueue model.  
  - Locations: `blackwork-core-plugin.php`, `woocommerce/woocommerce-init.php`
- #5 Assets loaded more globally than necessary (scope tightening required).  
  - Locations: `cart-popup/cart-popup.php`, `woocommerce/woocommerce-init.php`
- #6 Cart popup inline dynamic CSS not cacheable as static asset.  
  - Location: `cart-popup/frontend/cart-popup-frontend.php`
- #7 Google Maps API eagerly loaded on checkout.  
  - Location: `woocommerce/woocommerce-init.php`
- #9 Repeated `file_exists()` + `filemtime()` versioning pattern.  
  - Locations: `blackwork-core-plugin.php`, `woocommerce/woocommerce-init.php`
- #11 Sticky layout implemented in JS.  
  - Location: `assets/js/bw-checkout.js`
- #14 Slick loaded from CDN.  
  - Location: `blackwork-core-plugin.php`
- #17 Checkout footprint claim requires measurement/profiling before sizing final impact.  
  - Location: checkout enqueue/runtime stack.

### Low / backlog
- #2 Cart popup dynamic CSS with many `get_option()` reads.
- #3 No dedicated build/minify/bundle pipeline.
- #8 Missing resource hints (`preconnect` / `dns-prefetch`).
- #12 `wp_localize_script` payload review/minimization.
- #15 Duplicated Stripe enqueue logic across wallet branches.
- #16 Payment method icons without explicit width/height attributes.
- #13 Widget N+1 concern retained as profiling/context item only.

### No action
- #10 “jQuery hard dependency for all JS” -> False positive (some runtime scripts are non-jQuery).

## Governance routing summary
- Risk register:
  - #1 (new risk entry for sync-on-load external HTTP path)
- Core evolution backlog:
  - #2 #3 #4 #5 #6 #7 #8 #9 #11 #12 #14 #15 #16 #17 #13
- No action:
  - #10

## Top 5 highest-impact confirmed items
1. #1 Authenticated page-load external Supabase sync path.
2. #5 Global-ish asset loading scope (cart popup + anonymous bridge surfaces).
3. #7 Eager Google Maps API load on checkout.
4. #6 Inline generated cart-popup CSS (non-static cacheability).
5. #9 Repeated request-path `file_exists()` + `filemtime()` asset version checks.

## Quick wins
- #15 dedupe Stripe enqueue logic while preserving behavior.
- #8 add resource hints for known external domains.
- #12 reduce localized config payload to strict minimum.
- #16 add intrinsic icon dimensions in payment template output.

## Separate implementation task suggestions
- P1: Supabase sync-on-load scope hardening (#1).
- P2: Asset load-scope tightening for cart popup + bridge (#5).
- P3: Checkout maps lazy-load strategy (#7).
- P4: Cart popup CSS generation/caching strategy (#2/#6).
- P5: Versioning manifest/stat-call reduction (#9).
- P6: Checkout performance profiling baseline for footprint claim (#17 + #13 context checks).

## Note
This step records governance routing only.  
No runtime code was modified during Radar Batch 3 validation documentation.
