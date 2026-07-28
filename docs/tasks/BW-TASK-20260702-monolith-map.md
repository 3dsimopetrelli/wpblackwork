# BW-TASK-20260702 Monolith Map

- Date: 2026-07-02
- Repository: `wpblackwork`
- Scope: PHP monolith audit only
- Method:
  - ranked PHP files over 800 lines
  - prioritized files with mixed responsibilities, hook density, unrelated functions, and admin/frontend/AJAX overlap
  - no code changes

## Top 5 remaining monoliths

### 1. `admin/site-settings/product-import-export/import-engine.php`
- Line count: `3284`
- Risk level: `Critical`
- Responsibilities found:
  - CSV upload handling and nonce/capability validation
  - temporary run-state persistence and lock ownership
  - CSV parsing, chunking, header detection, mapping guessing
  - import orchestration and progress checkpoints
  - product creation/update logic
  - variation creation/update logic
  - SKU matching and uniqueness validation
  - taxonomy/category assignment
  - image lookup/download/attachment handling
  - digital product / license variation repair paths
  - row outcome accounting, warnings, and bounded message management
- Why it is still a monolith:
  - this is effectively a full import subsystem collapsed into one file
  - it mixes admin request handling, parser infrastructure, domain mutation rules, media ingestion, variation repair, and run-state bookkeeping
  - a failure inside one path is hard to isolate because the orchestration and the low-level mutators live together
- Suggested split strategy:
  - `request-controller.php` for upload/run/clear endpoints
  - `state-store.php` for active run, lock, cursor, warnings, totals
  - `csv-reader.php` for open/read/chunk/header parsing
  - `mapping.php` for aliases, guessing, mapping normalization
  - `product-writer.php` for product row persistence
  - `variation-writer.php` for variation row persistence / repair
  - `media-import.php` for image lookup/download/attachment bookkeeping
  - `taxonomy.php` for term/category assignment
- Refactor now or defer: `Refactor now`
- Why now:
  - highest blast radius in the repo for data mutation
  - largest remaining procedural subsystem
  - hardest file to validate and reason about safely as-is

### 2. `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`
- Line count: `2977`
- Risk level: `High`
- Responsibilities found:
  - Mail Marketing submenu registration
  - settings save handling for multiple tabs
  - Brevo connection testing AJAX
  - admin asset enqueueing
  - order newsletter metabox rendering
  - order status refresh/retry/list loading AJAX
  - user mail-marketing admin panels
  - shop order list columns, filters, HPOS integration, bulk actions
  - diagnostics rendering and legacy bridge output
- Why it is still a monolith:
  - it owns a whole admin product surface, not just one page
  - it mixes page rendering, business rules, diagnostics, AJAX endpoints, order list integration, and legacy compatibility in one class
  - many hooks are registered in the constructor, which makes the file a global control tower
- Suggested split strategy:
  - `mail-marketing-page-controller.php` for page routing/save flow
  - `brevo-test-ajax.php` for connection/test endpoints
  - `order-newsletter-admin.php` for metaboxes/order list columns/filters/bulk actions
  - `user-mail-marketing-admin.php` for user-side panels and sync actions
  - `diagnostics-renderer.php` for debug/status panels
  - `legacy-bridge.php` for old checkout-subscribe route compatibility
- Refactor now or defer: `Refactor now`
- Why now:
  - major admin ownership hotspot
  - hook density is high and concerns are clearly separable
  - future changes to Mail Marketing will keep stressing this file unless it is decomposed

### 3. `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- Line count: `2125`
- Risk level: `High`
- Responsibilities found:
  - Supabase config normalization and diagnostics
  - token / refresh-token storage and refresh flow
  - Supabase user fetch and WP user sync
  - social / passwordless login callbacks
  - multiple public AJAX auth endpoints
  - profile/email/password update handlers
  - checkout invite orchestration and resend flows
  - guest-order claim/assignment logic
  - session cookie storage/clearing
  - order-status hooks for provisioning
- Why it is still a monolith:
  - this is auth, session, account sync, AJAX controller, and checkout provisioning all in one procedural file
  - it mixes security-sensitive flows with WooCommerce order hooks and frontend account operations
  - it has a very high chance of accidental side effects because identity and commerce flows share the same implementation surface
- Suggested split strategy:
  - `config.php` for option normalization/diagnostics helpers
  - `session-store.php` for cookie/usermeta token persistence
  - `supabase-client.php` for remote auth/user calls
  - `account-sync.php` for WP user synchronization
  - `ajax-login.php` for login/token/email-exists/session endpoints
  - `ajax-profile.php` for update-profile/update-email/update-password endpoints
  - `checkout-invite.php` for order invite / guest-order claim logic
- Refactor now or defer: `Refactor now`
- Why now:
  - security-sensitive
  - mixed frontend/AJAX/Woo hooks
  - difficult to test in slices while it remains unified

### 4. `admin/site-settings/product-import-export/export-engine.php`
- Line count: `1672`
- Risk level: `High`
- Responsibilities found:
  - export request parsing and profile selection
  - category/product resolution
  - CSV stream orchestration
  - master-template and Woo-native schema definition
  - product/variation row building
  - dynamic meta discovery and filtering
  - attachment/media serialization
  - attribute normalization
  - export logging / payload validation
- Why it is still a monolith:
  - like the import engine, it is an entire export subsystem collapsed into one file
  - it combines admin request handling, schema definition, row builders, metadata discovery, and output transport
  - “what columns exist” and “how rows are built” are tightly interwoven
- Suggested split strategy:
  - `request-controller.php` for export trigger handling
  - `profiles.php` for master vs Woo-native export schemas
  - `product-query.php` for category/product resolution
  - `row-builders/product.php` and `row-builders/variation.php`
  - `dynamic-meta.php` for meta scan/filter/apply helpers
  - `csv-writer.php` for stream/write/order/validation helpers
- Refactor now or defer: `Refactor soon`
- Why soon:
  - large data-surface file with many unrelated helper families
  - not as dangerous as import because it does not mutate products, but still hard to evolve safely

### 5. `includes/modules/link-page/link-page-module.php`
- Line count: `1425`
- Risk level: `Medium-High`
- Responsibilities found:
  - option defaults and sanitization
  - admin settings registration
  - analytics table install/migration
  - click/view/subscription AJAX tracking
  - admin page registration and asset loading
  - frontend route detection and asset enqueue/dequeue
  - analytics summary and daily breakdown queries
  - settings tab rendering
  - analytics tab rendering
  - template hijack via `template_include`
- Why it is still a monolith:
  - it owns settings, frontend runtime, analytics storage, reporting queries, admin UI, AJAX, and template routing together
  - the file behaves like a mini-plugin living inside the plugin
  - analytics, rendering, and admin ergonomics are coupled more tightly than they need to be
- Suggested split strategy:
  - `settings.php` for options/sanitization/admin registration
  - `analytics-schema.php` for tables/install/versioning
  - `analytics-tracking.php` for click/view/subscription endpoints
  - `analytics-reporting.php` for summary and daily query helpers
  - `admin-page.php` for tabs/rendering/assets
  - `frontend-runtime.php` for route detection, enqueue/dequeue, runtime config
  - `template-router.php` for `template_include`
- Refactor now or defer: `Refactor soon`
- Why soon:
  - substantial product surface with clear seams already visible
  - future feature growth will make this file harder to stabilize if left unified

## Near-threshold but more deferrable

These are large, but more justifiably single-purpose than the top 5 above:

- `includes/widgets/class-bw-product-grid-widget.php` (`2168` lines)
  - still too large, but it remains one widget surface with controls + render path
  - refactor candidate after the higher-risk subsystem files
- `includes/widgets/class-bw-price-variation-widget.php` (`1792` lines)
  - very large control-heavy widget; risk is mostly maintainability rather than cross-subsystem coupling
- `admin/site-settings/tabs/checkout.php` (`1648` lines)
  - large tab renderer with strong coupling to checkout/account settings, but it is already bounded inside one admin tab surface
- `admin/site-settings/tabs/account.php` (`1645` lines)
  - similar story: large and complex, but more cohesive than the import/export and auth subsystems

## Ranking rationale

This ranking is based on more than line count:

1. breadth of responsibility
2. number of hooks/endpoints owned by one file
3. data mutation / security sensitivity
4. admin + runtime + AJAX mixing
5. likely blast radius of future changes

## Recommended next refactor target

### `admin/site-settings/product-import-export/import-engine.php`

Reason:
- it is the largest remaining PHP monolith
- it mixes request handling, state/locking, parsing, mapping, product persistence, variation persistence, taxonomy work, media import, and repair flows
- it is the most operationally dangerous file left because mistakes here can mutate catalog data at scale
- its split seams are already obvious, which makes it the highest-value next extraction target

## Final output

- Top 5 remaining monoliths:
  1. `admin/site-settings/product-import-export/import-engine.php`
  2. `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`
  3. `includes/woocommerce-overrides/class-bw-supabase-auth.php`
  4. `admin/site-settings/product-import-export/export-engine.php`
  5. `includes/modules/link-page/link-page-module.php`
- Recommended next refactor target: `admin/site-settings/product-import-export/import-engine.php`
- Reason: highest line count, widest responsibility spread, and highest catalog/data-mutation blast radius
