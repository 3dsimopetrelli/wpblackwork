# Regression Protocol

## Mandatory Checks
After any maintenance task, validate:
- Checkout test
- Payment gateway test
- Auth test
- Cart popup test
- Header behavior test
- My Account test
- CSS regression scan
- Console errors scan

## Applicability by Incident Level
- Level 1: mandatory
- Level 2: mandatory
- Level 3: recommended based on impact

## Execution Notes
- Run targeted checks first for the impacted domain.
- Then run cross-domain sanity checks to detect side effects.
- Record anomalies and link them to incident level and affected domain docs.

## Risk Work Protocol (Start / Close)
### Task Start / Risk Work Protocol
Whenever a new risk investigation starts, ensure the risk is registered both in:
- `docs/00-governance/risk-register.md`
- `docs/00-governance/risk-status-dashboard.md`

The Risk Status Dashboard is the quick overview of all governance risks and must be kept synchronized with the Risk Register.

If a new risk is discovered during investigation, add it to both files before proceeding with mitigation planning.

### Task Close / Status Change Protocol
When a risk is closed or status changes, update:
- `docs/00-governance/risk-register.md`
- `docs/00-governance/risk-status-dashboard.md`
- `docs/50-ops/regression-protocol.md` (if testing procedures changed)
- feature technical docs (if behavior changed)

### Risk Closure Documentation Checklist
Before marking a risk as `CLOSED` or `RESOLVED`:
- risk-register.md updated
- risk-status-dashboard.md updated
- regression protocol updated if needed
- feature technical docs updated if relevant

## Supabase Protected Surface Smoke Binding
When tasks touch Supabase protected surfaces, these smoke tests are mandatory:

1. guest checkout provisioning
2. invite email click callback
3. onboarding password completion
4. order claim visibility
5. resend invite
6. expired link recovery
7. logout cleanup

## WooCommerce Template Override Rebase Notes
- Date: 2026-03-09
- Risk: `R-WOO-24` (patch 1, patch 2, patch 3, patch 4, patch 5) - `RESOLVED`
- Templates updated:
  - `woocommerce/templates/checkout/form-coupon.php`
  - `woocommerce/templates/checkout/payment.php`
  - `woocommerce/templates/checkout/form-checkout.php`
  - `woocommerce/templates/cart/cart.php`
  - `woocommerce/templates/single-product/related.php`
- Alignment action:
  - Applied minimal structural compatibility patching against WooCommerce core contracts while preserving BlackWork custom UX/layout.
  - Avoided Supabase-adjacent surfaces entirely during the patch sequence.
- Required regression checks completed:
  - apply valid coupon
  - invalid coupon handling
  - submit via button and Enter key
  - guest and logged-in checkout
  - mobile and desktop layout
  - payment gateway visibility unaffected (Card / PayPal / Klarna)
  - gateway switching and `updated_checkout` selection persistence
  - noscript update-totals fallback present
  - checkout form submit still works with accessibility/form contract intact (`aria-label` + trailing after-form hook restored)
  - cart last-item removal transitions to empty-cart view on mobile and desktop
  - related products render correctly with Wallpost layout on mobile and desktop

## Checkout Payment Selector Interaction
- Risk/Task: `CHECKOUT-02` (resolved)
- Mandatory regression assertions:
  - radio click must select payment method on first click
  - row/label click must behave identically
  - selection must persist after `updated_checkout`

## Admin Asset Scope Hardening
- Date: 2026-03-09
- Risk: `R-ADM-19` (patch A, patch B) - `MITIGATED`
- Scope:
  - Patch A closed: Blackwork admin menu CSS enqueue scoped to Blackwork admin surfaces only.
  - Patch B closed: Digital Products metabox Select2 loading moved to scoped `admin_enqueue_scripts` (`post.php`/`post-new.php` + `post_type=product`), with runtime preference (`selectWoo` -> `select2` -> local fallback).
  - Patch C deferred (optional backlog): stricter screen/hook replacement for remaining `$_GET['page']` checks in low-risk admin modules (Header, System Status).
  - Local fallback assets added:
    - `assets/lib/select2/js/select2.full.min.js`
    - `assets/lib/select2/css/select2.css`
- Required regression checks completed:
  - Dashboard / Posts / Orders do not load Blackwork menu CSS outside intended scope.
  - Product edit/new screens load Select2 correctly for Showcase linked-product search.
  - No CDN Select2 requests remain from metabox path.
  - No duplicate Select2/SelectWoo runtime on the same product editor page.
  - Non-product admin pages do not load metabox Select2 assets.

## Admin Settings Input Integrity (Patch A + Patch B)
- Date: 2026-03-10
- Risk: `R-ADM-21` (patch A, patch B) - `RESOLVED`
- Scope:
  - Hardened Cart Popup admin save path in `cart-popup/admin/settings-page.php`.
  - Added normalized scalar input reads (`wp_unslash`), enum allowlists, and bounded numeric clamping.
  - Preserved option names and admin UX; SVG pipeline unchanged.
  - Hardened variation license AJAX exposure by removing unauthenticated route registration in `metabox/variation-license-html-field.php`:
    - removed `wp_ajax_nopriv_bw_get_variation_license_html`
    - kept authenticated hook + handler intact
  - No Supabase-adjacent surfaces touched.
- Required regression checks completed:
  - normal values save and persist
  - panel/mobile width settings persist correctly
  - border styles still apply correctly
  - font weight still applies correctly
  - SVG fields still behave correctly
  - out-of-range values are clamped
  - frontend cart popup behavior unchanged
  - logged-out product variation license HTML still renders correctly
  - logged-in behavior unchanged
  - no frontend dependency on `bw_get_variation_license_html` AJAX action
  - unauthenticated direct call to old AJAX action no longer works

## Media Folders Counts Pipeline Audit
- Date: 2026-03-09
- Risk: `R-MF-01` - `WATCHLIST / PLANNED MITIGATION`
- Audit scope:
  - Media Folders counts computation, cache and invalidation pipeline.
  - No implementation changes in this phase (analysis-only closure).
- Confirmed paths:
  - Primary: `bw_mf_get_folder_counts_map_batched()`
  - Fallback: `bw_mf_get_folder_counts_map_fallback()`
- Confirmed risks:
  - stale-count window until TTL when invalidation misses lifecycle events
  - cache churn from broad invalidation
  - expensive fallback behavior under large datasets
- Planned mitigation watchlist:
  - monitor/log fallback activation
  - extend invalidation hooks to object lifecycle events
  - split tree/meta invalidation from counts invalidation
  - add bulk integrity regression scenarios
- Supabase note:
  - No Supabase-adjacent surfaces involved.

## Media Folders Query Restrictiveness Hardening (R-MF-02 / R-MF-03)
- Date: 2026-03-10
- Risks:
  - `R-MF-02` - `MITIGATED`
  - `R-MF-03` - `MITIGATED`
- Scope:
  - Hardened `bw_mf_merge_tax_query()` in `includes/modules/media-folders/runtime/media-query-filter.php`.
  - Existing tax clauses remain grouped with their internal relation; Media Folders clause is merged under explicit outer `AND`.
  - Prevents pre-existing `OR` relations from weakening folder/unassigned filtering.
- Required regression checks completed:
  - assign media to folder, reload, confirm persistence
  - remove folder, confirm attachment becomes unassigned
  - grid media filter by folder remains correct
  - grid media filter by unassigned remains correct
  - list view media filter remains correct
  - pagination/search still work in media library
  - folder filter remains restrictive even with another tax_query present
  - counts/tree still update correctly after assignment/delete

## Payments Webhook Hardening (Patch 1)
- Date: 2026-03-09
- Risk: `R-PAY-08` (patch 1) - `CLOSED`
- Scope:
  - Added explicit POST-only method enforcement for Blackwork-owned Stripe webhook handlers.
  - Updated files:
    - `includes/Gateways/class-bw-abstract-stripe-gateway.php`
    - `includes/woocommerce-overrides/class-bw-google-pay-gateway.php`
- Runtime decision record:
  - Active Google Pay runtime/webhook source of truth:
    - `includes/woocommerce-overrides/class-bw-google-pay-gateway.php`
  - In-repo but inactive/not bootstrapped path:
    - `includes/Gateways/class-bw-google-pay-gateway.php`
  - Same class name + same gateway id means both paths cannot be active together at runtime.
  - Google Pay runtime convergence is deferred to a dedicated architecture cleanup task.
- Required regression checks:
  - valid webhook via POST still processes
  - invalid signature via POST still fails
  - GET to webhook endpoint is rejected (`405`)
  - unsupported event keeps expected no-op behavior

## SVG Sanitization Hardening Follow-up
- Date: 2026-03-09
- Risk: `R-SEC-22` - `RESOLVED`
- Scope:
  - Secondary SVG intake/output paths hardened to reuse canonical security pipeline.
  - Updated files:
    - `cart-popup/admin/settings-page.php`
    - `cart-popup/frontend/cart-popup-frontend.php`
    - `includes/widgets/class-bw-button-widget.php`
- Applied controls:
  - Cart popup option sanitizer now uses:
    - `bw_mew_svg_sanitize_content()`
    - `bw_mew_svg_is_valid_document()`
  - Frontend output of stored cart popup SVG now passes strict `wp_kses` allowlist.
  - Remote SVG in button widget now requires SVG content type + canonical sanitize/validate; invalid payload falls back to default icon.
  - Fallback cart popup SVG allowlist no longer permits inline `style`.
- Scope constraints respected:
  - Media Library SVG upload pipeline unchanged.
  - No upload hooks changed.
  - Header/logo SVG path unchanged.
  - No Supabase-adjacent surfaces touched.
- Required regression checks completed:
  - valid cart popup SVG saves and renders
  - malformed/unsafe cart popup SVG rejected
  - empty-cart custom SVG renders correctly
  - additional custom SVG renders correctly
  - `svg_black` coloring preserved
  - widget attachment SVG renders
  - widget remote valid SVG renders
  - widget remote invalid/non-SVG response falls back to default icon
  - Media Library SVG upload behavior unchanged

## Brevo Checkout Hardening (Patch 1 + Patch 2)
- Date: 2026-03-10
- Risk: `R-BRE-09` - `RESOLVED`
- Scope:
  - Patch 1: removed unsafe legacy Coming Soon Brevo subscription runtime from:
    - `BW_coming_soon/includes/functions.php`
  - Patch 1 removed:
    - `bw_handle_subscription()` implementation
    - `add_action("init", "bw_handle_subscription")`
  - Patch 2: preserved positive checkout subscribe state in:
    - `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php`
  - Patch 2 changed duplicate-pass handling:
    - existing `subscribed|pending|1` now exits as no-op
    - removed destructive rewrite to `skipped` for already-valid positive states
  - No redesign of canonical checkout/mail-marketing Brevo runtime.
  - No Supabase-adjacent surfaces touched.
- Required regression checks completed:
  - Coming Soon page still renders normally
  - legacy POST path no longer performs Brevo writes
  - no request to Brevo contacts API originates from removed Coming Soon path
  - checkout newsletter flow unchanged
  - consented order reaches `subscribed`/`pending` correctly
  - duplicate hook passes do not downgrade `subscribed`
  - duplicate hook passes do not downgrade `pending`
  - no-consent path still becomes `skipped` when appropriate
  - mail-marketing admin tools unchanged

## Brevo Secret Exposure Verification
- Date: 2026-03-10
- Risk: `R-BRE-09` - `CLOSED` (closure verification)
- Result: `PASS`
- Verification coverage:
  - repository-wide scan confirms no `xkeysib-` token present
  - legacy Coming Soon runtime path is absent (`bw_handle_subscription()` and `add_action("init", "bw_handle_subscription")` not present)
  - Coming Soon Brevo runtime uses canonical Mail Marketing configuration (`bw_mail_marketing_general_settings`) via `BW_Mail_Marketing_Settings::get_general_settings()['api_key']`
  - previous secret-exposure radar hit is classified as stale (older revision/external snapshot)

## HTTP Timeout Verification (External Radar)
- Date: 2026-03-10
- Scope:
  - `admin/class-blackwork-site-settings.php`
  - `BW_coming_soon/includes/functions.php`
  - `includes/integrations/brevo/class-bw-brevo-client.php`
- Result: `PASS`
- Verification summary:
  - all relevant HTTP calls use explicit `timeout` values
  - no `timeout => 0` usage found
  - no unsafe raw cURL path detected
  - `BW_coming_soon/includes/functions.php` has no outbound HTTP request
  - Brevo client uses explicit timeout (default `15`)
- Classification: stale / not applicable finding in current snapshot
- Recommended action: no runtime patch required

## Import Pipeline Integrity Hardening (R-IMP-10)
- Date: 2026-03-10
- Risk: `R-IMP-10` - `MITIGATED`
- Scope:
  - Patch 1: import progress checkpoint hardening in `admin/class-blackwork-site-settings.php`.
  - Patch 2: strict enum allowlist validation in importer normalization path (`bw_import_prepare_row_data`).
- Required regression checks completed:
  - interrupted import resumes close to actual progress
  - duplicate replay of already processed rows reduced
  - enum values validated during import
  - invalid enum values ignored safely
  - valid imports unaffected

## Search Live Runtime Visibility Alignment (R-SRCH-11)
- Date: 2026-03-10
- Risk: `R-SRCH-11` - `MITIGATED`
- Scope:
  - Live AJAX search visibility alignment in `includes/modules/header/frontend/ajax-search.php`.
  - Search context now excludes only `exclude-from-search` (no `exclude-from-catalog` exclusion in live search).
- Required regression checks completed:
  - live search finds products that are searchable but excluded from catalog
  - products excluded from search remain excluded
  - category/type filters still work
  - guest and logged-in AJAX responses still work
  - no obvious performance regression in live search
  - no runtime errors in header search UI

## Public AJAX Mutation Transport Hardening (R-FPW-20 Patch 1)
- Date: 2026-03-10
- Risk: `R-FPW-20` (patch 1) - `CLOSED`
- Scope:
  - Added explicit POST-only method enforcement for non-Supabase public mutation endpoints.
  - Updated files:
    - `cart-popup/frontend/cart-popup-frontend.php`
    - `woocommerce/woocommerce-init.php`
  - Patched handlers:
    - `bw_cart_popup_add_to_cart`
    - `bw_cart_popup_remove_item`
    - `bw_cart_popup_update_quantity`
    - `bw_cart_popup_apply_coupon`
    - `bw_cart_popup_remove_coupon`
    - `bw_apply_coupon`
    - `bw_remove_coupon`
- Required regression checks completed:
  - valid POST requests still work for all patched actions
  - GET requests return HTTP 405
  - Cart Popup behavior unchanged
  - checkout coupon apply/remove behavior unchanged
  - public read-only AJAX endpoints remain unaffected

## Authenticated Product Search Capability Hardening (R-FPW-20 Patch 2)
- Date: 2026-03-10
- Risk: `R-FPW-20` (patch 2) - `CLOSED`
- Scope:
  - Added explicit capability enforcement in authenticated AJAX endpoint `bw_search_products_ajax`.
  - Updated file:
    - `metabox/digital-products-metabox.php`
  - Control added:
    - requires `current_user_can( 'edit_products' )`
    - unauthorized requests return JSON permission error (`403`)
- Required regression checks completed:
  - authorized users with `edit_products` can search products normally
  - unauthorized logged-in users receive permission error (`403`)
  - nonce behavior unchanged
  - metabox product search UI still works for authorized users

## Checkout Payment State Integrity Hardening (R-PAY-02)
- Date: 2026-03-10
- Risk: `R-PAY-02` - `MITIGATED`
- Scope:
  - Minimal JS-only hardening in `assets/js/bw-payment-methods.js`.
  - Hardened explicit-selection re-apply behavior to avoid unnecessary overrides of valid checked method.
  - Added pre-submit reconciliation guard so checked `payment_method` and selected/open UI row converge before submit.
  - Added minimal free-order guard to avoid `#place_order` label drift while `body.bw-free-order` is active.
  - No Supabase-adjacent surfaces touched.
- Required regression checks completed:
  - checkout load with default gateway
  - repeated payment method switching
  - `updated_checkout` / fragment refresh after address or shipping change
  - coupon apply/remove flow
  - wallet-capable gateway visibility behavior
  - submit order with non-default gateway selected
  - unavailable gateway after refresh
  - zero-total / free-order path (when present)

## Wallet Availability / State Drift Hardening (R-PAY-03)
- Date: 2026-03-10
- Risk: `R-PAY-03` - `PARTIAL MITIGATION`
- Scope:
  - Minimal JS-only hardening in `assets/js/bw-google-pay.js`.
  - `BW_GPAY_AVAILABLE` ownership moved to deterministic setter (`setGooglePayAvailability`).
  - Removed optimistic Google Pay availability (`true`) on init and `updated_checkout`.
  - Availability now converges to `false` during pending check, negative capability, and failure/error paths.
  - Selector convergence is re-triggered on availability changes via existing `scheduleSelectorSync(...)`.
  - No Supabase-adjacent surfaces touched.
- Regression checks required (manual):
  - checkout load with Google Pay supported
  - checkout load with Google Pay unsupported
  - switch between wallet and non-wallet gateways
  - `updated_checkout` after shipping/address change
  - coupon apply/remove
  - wallet visibility after fragment refresh
  - fallback: wallet unavailable -> `place_order` visible
  - Apple Pay behavior unchanged

## Redirect Authority Drift Hardening (R-RED-12)
- Date: 2026-03-10
- Risk: `R-RED-12` - `MITIGATED` (strict non-Supabase scope)
- Scope:
  - Minimal precedence clarification in `BW_coming_soon/includes/functions.php`.
  - `bw_show_coming_soon` now runs at `template_redirect@1` to establish deterministic authority in anonymous maintenance mode.
  - Generic redirect engine (`includes/class-bw-redirects.php`) left unchanged.
  - Auth/Supabase redirect surfaces excluded by freeze policy.
- Regression checks required (manual):
  - Coming Soon active + anonymous visit on URL with custom redirect rule
  - Coming Soon active + anonymous visit on URL without custom redirect rule
  - Coming Soon inactive + custom redirect rule still works
  - cart/checkout routes remain unaffected by custom redirect rules
  - wallet failed return still redirects to checkout
  - admin save/import redirects unchanged
