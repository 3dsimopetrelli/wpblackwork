# Redirect Engine — Technical Audit (Current Implementation)

## 1) Executive Summary
The Redirect feature provides admin-managed URL rewrite rules under the Blackwork Site admin panel and applies them on frontend requests before most other plugin layout hooks.  
Admin location: `Blackwork Site -> Redirect` (`?page=blackwork-site-settings&tab=redirect`).  
Current runtime behavior supports only permanent redirects (`301`) via `wp_safe_redirect()`, with exact match on normalized `path + query`.  
Primary risks observed:
- Loop/chain safety is partial (self-loop guard exists; multi-rule chains/cycles are not fully analyzed).
- Matching precedence can override normal frontend routes because it runs early on `template_redirect` (priority `5`).
- Performance is linear per request (`O(n)` on stored rules, no cache).
- Security is generally constrained by nonce/capability and `wp_safe_redirect`, but external-host behavior depends on WordPress allowed host policy/filters.

## 2) Entry Points & File Inventory
### Core bootstrap/load
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/bw-main-elementor-widgets.php`
  - Responsibility: plugin bootstrap.
  - Key point: conditionally requires Redirect runtime file:
    - `includes/class-bw-redirects.php`.

### Runtime redirect engine
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/class-bw-redirects.php`
  - Responsibility: normalization + frontend redirect execution.
  - Key functions:
    - `bw_normalize_redirect_path($url)`
    - `bw_maybe_redirect_request()`
  - Hook:
    - `add_action('template_redirect', 'bw_maybe_redirect_request', 5)`

### Admin UI + save handler
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/admin/class-blackwork-site-settings.php`
  - Responsibility: admin menu/tab rendering, form handling, option persistence.
  - Key functions:
    - `bw_site_settings_menu()` (registers top-level menu)
    - `bw_site_settings_page()` (tab router; includes Redirect tab)
    - `bw_site_render_redirect_tab()` (render + save for redirects)
    - `bw_site_settings_admin_assets($hook)` (enqueues redirect admin JS/CSS)
  - Key admin identifiers:
    - Menu slug: `blackwork-site-settings`
    - Redirect tab slug: `tab=redirect`
    - Nonce action/key: `bw_redirects_save` / `bw_redirects_nonce`

### Admin JS
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/admin/js/bw-redirects.js`
  - Responsibility: add/remove repeater rows in Redirect tab UI.
  - Behavior: DOM only (no AJAX save; submit is regular form POST).

### Admin CSS
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/admin/css/blackwork-site-settings.css`
  - Responsibility: Redirect table row action styling (`.bw-redirects-table .bw-redirect-actions`).

### Enqueue model
- JS handle: `bw-redirects-admin`
- Enqueued from: `bw_site_settings_admin_assets($hook)` in `admin/class-blackwork-site-settings.php`
- Scope: all allowed Blackwork admin pages (not redirect-tab-only), filtered by hook/page checks:
  - `toplevel_page_blackwork-site-settings`
  - `blackwork-site-settings_page_blackwork-mail-marketing`
  - `blackwork-site_page_blackwork-mail-marketing`
  - or `?page=blackwork-mail-marketing`

## 3) Storage Model
Redirect rules are stored in `wp_options` under option key:
- `bw_redirects`

Storage structure (array of rules):
```php
[
  [
    'source' => '/promo/black-friday',
    'target' => 'https://example.com/page'
  ],
  ...
]
```

Observed normalization/sanitization:
- On save:
  - `source_url` is trimmed, validated by `bw_normalize_redirect_path()` (must parse as URL/path and produce non-empty normalized value), then stored as sanitized raw text (`sanitize_text_field($source_raw)`), not the normalized result.
  - `target_url` is trimmed and sanitized with `esc_url_raw()`.
- On runtime match:
  - Request URI and stored source are normalized through `bw_normalize_redirect_path()`:
    - path always forced to leading slash
    - query (if any) appended as raw `?query`
- No explicit persistent ordering metadata; execution order is array order as stored.
- No explicit priority field per rule.
- Trailing slash and query ordering canonicalization are not rewritten; comparison remains strict string equality after normalization.

## 4) Admin Workflow
1. Admin opens `Blackwork Site -> Redirect`.
2. UI renders repeater rows from `bw_redirects` (or one blank row when empty).
3. Admin adds/removes rows client-side via `admin/js/bw-redirects.js`.
4. On submit (`bw_redirects_submit`):
   - capability check: `current_user_can('manage_options')`
   - nonce check: `check_admin_referer('bw_redirects_save', 'bw_redirects_nonce')`
   - loops through posted `bw_redirects[*][target_url|source_url]`
   - skips invalid entries (empty/invalid source or target after sanitization/normalization checks)
   - persists sanitized list with `update_option('bw_redirects', $sanitized)`
5. Success message shown: `Redirect salvati con successo!`
6. Edit/remove model:
   - No dedicated CRUD endpoints.
   - Editing is overwrite-by-resubmit.
   - Deletion is omission from submitted list.

Error surfacing:
- No granular per-row validation errors are displayed.
- Invalid rows are silently skipped.

## 5) Runtime Application Model
Runtime function: `bw_maybe_redirect_request()` (`includes/class-bw-redirects.php`).

Hook:
- `template_redirect` at priority `5`.

Execution flow:
1. Skip in admin (`is_admin()`).
2. Load `bw_redirects` option.
3. Read request from `$_SERVER['REQUEST_URI']`.
4. Normalize request path/query via `bw_normalize_redirect_path()`.
5. Iterate rules sequentially:
   - normalize source
   - sanitize target (`esc_url_raw`)
   - exact compare: `normalized_request === normalized_source`
6. Loop guard checks before redirect:
   - skip if full current URL equals target
   - skip if normalized target path equals normalized source
7. Execute:
   - `wp_safe_redirect($safe_target, 301); exit;`

Matching characteristics:
- Exact match only (no wildcard/prefix/regex).
- Querystring-sensitive exact string match.
- Trailing slash differences can produce non-match.
- Redirect type is fixed to `301`.

## 6) Precedence & Interactions
### WordPress canonical redirects
- This engine runs on `template_redirect` priority `5`.
- WordPress canonical redirect typically runs later (`template_redirect`, default priority `10` in core).
- Result: matched Blackwork redirect exits early and usually wins before canonical logic.

### WooCommerce/plugin template_redirect hooks in this plugin
Within this plugin, several template_redirect hooks are present at priorities `1`, `2`, `5`, `6+`, `9+` across checkout/account modules.
- Blackwork redirect at `5` runs:
  - after priority `1/2` handlers
  - before many layout/account handlers at `6/7/9/...`
- On matched rule, execution exits and downstream hooks do not run.

### Rank Math / other redirect plugins
- No direct integration logic detected in this plugin for Rank Math or third-party redirect plugins.
- Effective precedence against external plugins is **unknown** without active plugin list and their hook priorities.
- Where to verify:
  - active plugins + their `template_redirect` hooks
  - `has_action('template_redirect', ...)` inspection at runtime

## 7) Loop Prevention & Safety
Implemented:
- Self-target prevention by comparing current full URL with target.
- Source-target same-path prevention (`target_path === normalized_source`).

Not implemented:
- No global graph/cycle detection across multiple rules (A->B->A).
- No max-hop protection internal to this module (relies on browser/server behavior across multiple requests).
- No validation against redirecting protected/critical routes (e.g., checkout/account endpoints).

Admin validation sufficiency:
- Basic field sanitization and parse checks exist.
- Structural loop-chain safety is partial.

## 8) Performance Model
- Runtime complexity: `O(n)` per frontend request (`foreach` all rules).
- Storage access: reads full `bw_redirects` option each request where hook runs.
- No in-memory index, no transient/object-cache strategy in this module.
- For large rule sets (hundreds/thousands), request-time scan cost scales linearly.
- Early exit on first matched rule reduces cost only when matches are near start.

## 9) Security & Permissions
Admin-side:
- Capability check on submit: `manage_options`.
- Nonce verification: `bw_redirects_save`.
- Input sanitization:
  - source: `sanitize_text_field` (+ normalization validity gate)
  - target: `esc_url_raw`
- Output escaping in form: `esc_attr`.

Runtime-side:
- Uses `wp_safe_redirect` (safer than `wp_redirect`) + fixed status code `301`.
- Open redirect profile:
  - Target allows URL input format, but `wp_safe_redirect` enforces safe host policy.
  - Final behavior for external hosts depends on WordPress allowed redirect host rules/filters (`allowed_redirect_hosts`), not overridden in this module.
- No direct header injection primitives observed (no manual `Location` header output).

## 10) Failure Modes
- Invalid rule input:
  - skipped silently at save time.
- Conflicting rules:
  - first matching rule in stored order wins.
- Redirect target non-existing route:
  - redirect still executed; destination may 404.
- Endpoint collisions:
  - rules can match Woo/account/checkout paths due to early template_redirect execution.
- Loop chains:
  - simple self-loop guard exists; multi-step loops not comprehensively blocked.
- Permalink or path format drift:
  - exact string matching may break with slash/query variations.
- Multilingual/path prefix scenarios:
  - no locale-aware matching logic detected.

## 11) Verified Risk Summary
- Routing integrity risk: **Medium-High**
  - Exact-match engine with early hook and no explicit route protection can override critical frontend routes.
- SEO risk: **Medium**
  - Always-301 behavior; misconfigured rules can create persistent wrong canonical destinations.
- Performance risk: **Medium**
  - Linear scan per request, no indexing/caching.
- Security risk: **Medium**
  - Admin access + nonce controls are present; runtime relies on `wp_safe_redirect`.
  - External redirect behavior depends on global allowed-host policy (environment-dependent).

## 12) Open Questions / Unknowns
- External plugin precedence (Rank Math/other redirect plugins): **unknown** from repository-only inspection.
  - Check active plugin stack and runtime hook priorities.
- Effective `allowed_redirect_hosts` policy in target environment: **unknown** in this plugin.
  - Check theme/mu-plugin/other plugin filters.
- Production scale of `bw_redirects` dataset: **unknown**.
  - Check actual option size/count in DB.
- Operational ordering guarantees when admin reorders rules: **unknown** (UI does not expose drag-sort; order depends on submitted array positions).
