# Site Settings Monolith — Full Audit (Roadmap Item 1)

- Audit date: 2026-05-18
- Target: `admin/class-blackwork-site-settings.php` (10,013 lines / 465 KB)
- Roadmap reference: Blackwork Full Audit Roadmap — Priority 1, Item 1
- Risk classification: **Critical**
- Scope: audit only — radar audit, risk mapping, extraction strategy. No code changes.
- Governance: extraction-first, no rewrites. Supabase logic is **frozen** (audit only).

---

## Context

`admin/class-blackwork-site-settings.php` is the project's #1 critical monolith. It is a single
10,013-line procedural PHP file that is `require`d unconditionally by the plugin bootstrap, so
everything it registers at file scope runs on **every** WordPress request — admin and frontend.

It is not, despite its name and `admin/` location, an admin-only file. It is simultaneously:
the unified "Blackwork > Site Settings" admin page (10 tabs), the Site Layout **frontend** runtime,
the product CSV **export engine**, the product CSV **import engine**, and the host of five payment
provider AJAX test endpoints.

This audit maps its structure across the six roadmap dimensions, ranks the risks, and proposes a
phased extraction strategy that preserves runtime behavior. It does **not** change code; the
implementation of any extraction is a separate governed task.

---

## Executive Summary

| Dimension | Headline finding |
|---|---|
| Architecture | Pure procedural — 0 classes, ~130 top-level `bw_*` functions, 19 distinct sub-systems in one file. Two ~1.4k–1.6k-line god functions; one ~2.3k-line import engine. |
| Hooks | 18 hook registrations. **5 are frontend hooks registered unconditionally** in an admin-named file. |
| Admin/Frontend coupling | The entire `bw_site_layout_*` frontend runtime module (~200 lines + 5 hooks) lives inside this admin file and executes on every public page load with **no caching**. |
| Provider integrations | Supabase, Stripe (Google Pay / Klarna / Apple Pay), Google Maps, Google + Facebook OAuth. **Inconsistent secret masking** — Stripe keys masked, but OAuth secrets, Supabase keys, Maps key render as plaintext. |
| Settings lifecycle | No `register_setting()` anywhere. ~150 flat option keys, save handlers inlined per tab. Option sprawl, legacy orphan keys, dual-source sync. Save handlers are correctly nonce + capability gated. |
| Extraction boundaries | 6 clean-seam sub-systems extractable now; 7 moderate; 2 (Account/Login + Checkout tabs) **Supabase-frozen — do not extract**. |

Overall: the file is **not broken** — save handlers are consistently secured, and the engines work.
It suffers from monolith growth and mixed responsibilities. The single highest-value, lowest-risk
architectural fix is extracting the Site Layout frontend runtime out of this admin file.

---

## Dimension 1 — Architecture

Pure procedural PHP. One `define()`, zero class definitions (the only OOP reference is a delegation
to the external `BW_Checkout_Fields_Admin`). ~130 top-level `function bw_*()` declarations hooked
directly onto WordPress. No namespaces, no autoloading, no interfaces.

### Sub-system inventory

| # | Sub-system | Lines | ~LOC | Responsibility |
|---|---|---|---|---|
| 1 | Constant definition | 1–15 | 15 | `BW_SITE_LAYOUT_OPTION` (`bw_site_layout_settings_v1`) |
| 2 | Admin menu registration | 20–105 | 86 | `add_menu_page`, `add_submenu_page`, `$submenu` reorder |
| 3 | Asset enqueue | 107–443 | 337 | Conditional CSS/JS matrix for all tabs + frontend |
| 4 | **Site Layout frontend module** | 446–650 | 204 | CSS custom properties, body classes, `the_content` wrap, WC shell |
| 5 | Payment/Maps AJAX handlers | 654–1167 | 514 | Live connectivity tests: Google Pay, Maps, Klarna, Apple Pay |
| 6 | Main tab router | 1172–1333 | 162 | Page chrome + per-tab dispatch |
| 7 | Info tab | 1338–1454 | 117 | Static reference table + clipboard JS |
| 8 | Layout tab | 1459–1578 | 120 | Save/render for global width settings |
| 9 | Promotions & Labels | 1580–1925 | 346 | Labels submenu page + Labels tab |
| 10 | **Account/Login tab** (god fn) | 1930–3290 | 1361 | Saves ~60 options incl. all Supabase auth config |
| 11 | My Account Front tab | 3295–3365 | 71 | Save/render 2 options |
| 12 | **Checkout tab** (god fn) | 3370–5001 | 1632 | Saves ~45 options incl. Supabase provisioning; 8 sub-tabs |
| 13 | Cart Popup tab | 5006–5314 | 309 | Render shell; save delegated externally |
| 14 | Redirect tab | 5319–5448 | 130 | CRUD on `bw_redirects` |
| 15 | Coming Soon tab | 5453–5600 | 148 | Toggle + Brevo settings |
| 16 | **Export engine** | 5602–7277 | 1676 | ~45 fns: column discovery, CSV streaming, chunked export |
| 17 | Import/Export tab renderer | 7278–7693 | 416 | UI shell over both engines |
| 18 | **Import engine** | 7695–9961 | 2267 | ~65 fns: CSV parse, lock, chunked upsert, image sideload |
| 19 | Loading tab | 9966–10011 | 46 | Single checkbox: WC spinner suppression |

### Structural debt

- **God functions.** `bw_site_render_account_page_tab()` (~1,361 lines) and
  `bw_site_render_checkout_tab()` (~1,632 lines) each mix three concerns: POST persistence,
  ~200 lines of inline jQuery, and HTML rendering. The import engine alone is ~2,267 lines.
- **Duplicated logic.** Term resolution appears 3× with diverging signatures
  (`bw_import_assign_terms` L9686, `bw_import_resolve_term_ids` L9714, inline loop in
  `bw_import_apply_attributes` L9772). The save-then-redirect pattern (check `$_POST` →
  `check_admin_referer` → `update_option` → `$saved = true` → notice) is copy-pasted into
  ~8 tab functions with no shared helper.
- **Cross-hook global state.** `$GLOBALS['bw_export_request_result']` is written by the
  `admin_init` export handler (~L5602) and read by the tab renderer (~L7278).

---

## Dimension 2 — Hooks

18 total registrations. Full table:

| Line | Hook | Callback | Prio | Context |
|---|---|---|---|---|
| 46 | `admin_menu` | `bw_site_settings_menu` | 10 | Admin |
| 62 | `admin_menu` | `bw_product_labels_admin_menu` | 61 | Admin |
| 98 | `admin_menu` | `bw_site_settings_force_default_submenu` | 999 | Admin |
| 119 | `admin_enqueue_scripts` | `bw_site_settings_admin_menu_icon_styles` | 10 | Admin — **no page guard, loads on ALL admin pages** |
| 176 | `admin_enqueue_scripts` | `bw_admin_enqueue_ui_kit_assets` | 12 | Admin — page-guarded inside callback |
| 444 | `admin_enqueue_scripts` | `bw_site_settings_admin_assets` | 10 | Admin — page-guarded inside callback |
| 536 | `wp_enqueue_scripts` | `bw_site_layout_enqueue_frontend_runtime_styles` | 40 | **FRONTEND — unconditional** |
| 568 | `body_class` | `bw_site_layout_add_body_classes` | 10 | **FRONTEND — unconditional** |
| 616 | `the_content` | `bw_site_layout_wrap_the_content` | 20 | **FRONTEND — unconditional** (has internal `is_admin()` guard) |
| 633 | `woocommerce_before_main_content` | `bw_site_layout_open_woocommerce_shell` | 5 | **FRONTEND/WC — unconditional** |
| 650 | `woocommerce_after_main_content` | `bw_site_layout_close_woocommerce_shell` | 50 | **FRONTEND/WC — unconditional** |
| 749 | `wp_ajax_bw_google_pay_test_connection` | `bw_google_pay_test_connection_ajax_handler` | 10 | Admin AJAX |
| 849 | `wp_ajax_bw_google_maps_test_connection` | `bw_google_maps_test_connection_ajax_handler` | 10 | Admin AJAX |
| 931 | `wp_ajax_bw_klarna_test_connection` | `bw_klarna_test_connection_ajax_handler` | 10 | Admin AJAX |
| 1032 | `wp_ajax_bw_apple_pay_test_connection` | `bw_apple_pay_test_connection_ajax_handler` | 10 | Admin AJAX |
| 1167 | `wp_ajax_bw_apple_pay_verify_domain` | `bw_apple_pay_verify_domain_ajax_handler` | 10 | Admin AJAX |
| 5969 | `admin_init` | `bw_export_maybe_handle_admin_request` | 5 | Admin — internal `is_admin()` guard |
| 7714 | `upload_dir` | `bw_import_upload_dir` | 10 | Transient — added/removed inside `wp_handle_upload` call |

`apply_filters` extension points declared here: `bw_import_checkpoint_every` (default 10, L7955),
`bw_import_chunk_size` (default 50, L8053).

### Hook findings

- **No `wp_ajax_nopriv_` variants** — all 5 AJAX endpoints require authentication. Each callback
  verifies `current_user_can('manage_options')` + `check_ajax_referer()`. Good.
- The 3 `admin_menu` registrations (L46/62/98) are intentionally sequenced, not duplicates.
- `bw_site_settings_admin_menu_icon_styles` (L119) has **no `$hook` page guard** — its inline
  menu-icon CSS loads on every admin screen. Low severity; may relate to the already-resolved
  `R-ADM-19` asset-scoping work — verify whether it was in scope.

---

## Dimension 3 — Admin / Frontend Coupling

This is the most significant architectural finding.

The file is named, located (`admin/`), and conceptually treated as an admin settings page — but
it contains a **complete frontend runtime module**, the `bw_site_layout_*` family (lines 446–650):

| Function | Line | Role |
|---|---|---|
| `bw_site_layout_default_settings` | 447 | Default option array |
| `bw_site_layout_sanitize_settings` | 464 | Sanitize on save (used by Layout tab) |
| `bw_site_layout_get_settings` | 485 | Reads `BW_SITE_LAYOUT_OPTION` — called by every frontend hook |
| `bw_site_layout_is_enabled` | 495 | Reads `enabled` flag |
| `bw_site_layout_build_runtime_css` | 504 | Builds CSS custom-property string |
| `bw_site_layout_enqueue_frontend_runtime_styles` | 525 | `wp_enqueue_scripts` callback |
| `bw_site_layout_add_body_classes` | 539 | `body_class` callback |
| `bw_site_layout_wrap_the_content` | 569 | `the_content` callback |
| `bw_site_layout_open_woocommerce_shell` | 617 | `woocommerce_before_main_content` callback |
| `bw_site_layout_close_woocommerce_shell` | 634 | `woocommerce_after_main_content` callback |

Consequences:

- **Frontend runtime executes from an admin file on every public page.** The five hooks (L536–650)
  are registered at file scope with no `is_admin()` wrapping. Because the bootstrap requires this
  file unconditionally, they always register.
- **No caching on the read path.** `bw_site_layout_get_settings()` calls
  `get_option(BW_SITE_LAYOUT_OPTION, ...)` and is invoked by multiple frontend hooks per request,
  with no transient or static memoization. Each layout-active page load re-reads the option
  several times (mitigated by WP's object cache, but still uncached at the module level).
- **Clean read/write split** — write path is admin-only (`bw_site_render_layout_tab()` ~L1476 →
  `update_option`), read path is the frontend hooks. The split is correct; only the *file location*
  is wrong.

The Loading tab option `bw_loading_global_spinner_hidden` (written L9978) is also consumed by
frontend enqueue logic that lives **outside** this file — another write-here/read-elsewhere split.

---

## Dimension 4 — Provider Integrations

### Summary

| Provider | Where configured | Secret field rendering |
|---|---|---|
| Supabase (auth) | Account/Login tab — **FROZEN** | `<textarea>` plaintext (anon key, service role key, Apple private key) |
| Supabase (provisioning) | Checkout tab — **FROZEN** | invite/expired redirect URLs only |
| Stripe — Google Pay | Checkout tab L4381–4599 | `type="password"` ✅ |
| Stripe — Klarna | Checkout tab L4601–4695 | `type="password"` ✅ |
| Stripe — Apple Pay | Checkout tab L4697–4824 | `type="password"` ✅ |
| Google Maps/Places | Checkout tab L4159–4379 | `type="text"` plaintext ⚠️ |
| Google OAuth (WP path) | Account/Login tab L2481+ | client secret `type="text"` ⚠️ |
| Facebook OAuth (WP path) | Account/Login tab L2399+ | app secret `type="text"` ⚠️ |
| Brevo | configured on separate `blackwork-mail-marketing` page | only `bw_coming_soon_brevo_settings` array touched here |

### Findings

- **F-PROV-1 — Inconsistent secret masking.** Stripe secret keys are correctly masked
  (`type="password"`). But the Google Maps API key (L4196, `type="text"`), the WordPress-path
  Google OAuth client secret (L2560, `type="text"`) and Facebook app secret (L2469, `type="text"`)
  render as plaintext. They are escaped (`esc_attr`) and the page is `manage_options`-gated, but
  the values are readable in page source / DOM and visible over-the-shoulder. Supabase keys have
  the same issue (`<textarea>` plaintext, L2607/2620/2924) — **frozen, audit-only**.
- **F-PROV-2 — Cross-provider credential fallback.** The Apple Pay AJAX handlers fall back to
  reading `bw_google_pay_secret_key` when the Apple Pay secret is empty (`bw_apple_pay_test_connection`
  L948, `bw_apple_pay_verify_domain` L1047→1050). This couples two providers' credentials and can
  produce misleading "connection OK" results against the wrong account.
- **F-PROV-3 — Nonce inlined into HTML.** The Google Maps test nonce is echoed directly into a
  `<script>` block (L4361, `esc_js`) rather than passed via `wp_localize_script`. Low severity,
  inconsistent with the plugin's documented localize pattern.
- AJAX handlers test live keys submitted in `$_POST` (not the stored key) — correct for a
  "test before save" UX.

---

## Dimension 5 — Settings Lifecycle

- **No `register_setting()` anywhere.** Every tab inlines its own POST save handler at the top of
  its render function. Pattern: `isset($_POST[submit])` → `current_user_can('manage_options')` →
  `check_admin_referer()` / `wp_verify_nonce()` → per-field sanitize → `update_option()`.
- **Save handlers are consistently secured.** All 9 tab save handlers verify both a nonce and
  `manage_options`; all 5 AJAX handlers verify `check_ajax_referer()` + capability. This is
  consistent with the already-resolved `R-ADM-21`. No unguarded `$_POST` save path was found.
- **Option sprawl.** ~150 distinct option keys. The Checkout tab alone writes 45+ flat keys; the
  Account/Login tab 50+ flat keys. Only `bw_site_layout_settings_v1` and
  `bw_product_labels_settings_v1` use grouped array options.
- **Legacy orphans.** `bw_checkout_page_bg_color` and `bw_checkout_grid_bg_color` are read as
  fallbacks (L3604–3605) but never written by this file.
- **Legacy alias writes.** `bw_account_login_title` / `bw_account_login_subtitle` are written
  redundantly (L2059–2060) on every account save alongside the `_supabase` / `_wordpress` variants.
- **Dual-source sync.** `bw_checkout_fields_settings['section_headings']` is kept in sync with
  three flat keys (`bw_checkout_hide_billing_heading` etc., L3562–3574); both representations are
  read live.
- **Import run option leakage.** The import engine creates ephemeral `bw_import_run_{uuid}` options
  per run. `bw_import_clear_run_state()` (L8247) deletes them only if explicitly called — abandoned
  runs can leave orphaned rows in `wp_options`. The lock uses direct `$wpdb->query()` compare-and-swap
  (L8213–8222) bypassing the options cache (intentional, for atomicity).
- Defaults are declared inline at each `get_option()` call, not in a central registry; several are
  computed dynamically (`site_url('/my-account/')`).

Full option-key table in Appendix A.

---

## Dimension 6 — Extraction Boundaries

Assessed for behavior-preserving extraction into separate files.

### Clean seam — safe to extract now

| Sub-system | Lines | Notes / target |
|---|---|---|
| Loading tab | 9966–10011 | 46 lines, zero deps → `admin/tabs/` |
| Info tab | 1338–1454 | static output, zero option I/O → `admin/tabs/` |
| My Account Front tab | 3295–3365 | 71 lines, 2 options → `admin/tabs/` |
| Site Layout frontend module | 446–650 | all fns `function_exists`-guarded → `includes/modules/site-layout/` |
| Coming Soon tab | 5453–5600 | no Supabase, no globals → `admin/tabs/` |
| Redirect tab | 5319–5448 | move `bw_normalize_redirect_path()` with it → `admin/tabs/` |

### Moderate coupling — extractable with defined seams

| Sub-system | Lines | Blocking coupling |
|---|---|---|
| Payment/Maps AJAX handlers | 654–1167 | read-only on option keys; 5 independent handlers |
| Cart Popup tab | 5006–5314 | thin shell; depends on `cart-popup/` load order |
| Export engine | 5602–7277 | `$GLOBALS['bw_export_request_result']` seam + `admin_init` hook |
| Import engine | 7695–9961 | `global $wpdb` in 2 lock fns; otherwise self-contained |
| Import/Export tab renderer | 7278–7693 | must follow both engines |
| Promotions & Labels | 1580–1925 | submenu registration + Select2 in asset matrix |
| Asset enqueue | 107–443 | conditional matrix keyed to every tab/subtab slug |

### Heavily entangled / frozen

| Sub-system | Lines | Reason |
|---|---|---|
| Layout tab | 1459–1578 | inseparable from Site Layout module — extract together |
| Admin menu registration | 20–105 | skeleton for all tabs — extract last |
| **Account/Login tab** | 1930–3290 | **SUPABASE-FROZEN — do not extract** |
| **Checkout tab** | 3370–5001 | **SUPABASE-FROZEN — do not extract** |

---

## Risk Map

Findings ranked. Severity reflects governance impact, not just bug size. IDs are *candidates* for
routing into `risk-register.md` / `core-evolution-plan.md` per `radar-analysis-workflow.md` — this
audit does not itself create those entries.

| # | Finding | Severity | Supabase-frozen? | Candidate route |
|---|---|---|---|---|
| R1 | Site Layout frontend runtime embedded in admin file; 5 unconditional frontend hooks, uncached option reads on every page load | High | No | New risk (`R-ADM-2x`) |
| R2 | Two god functions (~1.4k / ~1.6k lines) mixing persistence + inline JS + rendering | High | Tabs themselves frozen | core-evolution-plan |
| R3 | Inconsistent secret masking — Maps key + WP-path OAuth secrets render as plaintext `type="text"` | Medium | No (Supabase variant frozen) | New risk (security) |
| R4 | Apple Pay AJAX handlers fall back to Google Pay secret key — cross-provider credential bleed | Medium | No | New risk |
| R5 | Option sprawl — ~150 flat keys, no `register_setting`, no central defaults | Medium | No | core-evolution-plan |
| R6 | Import `bw_import_run_{uuid}` options can leak into `wp_options` on abandoned runs | Medium | No | core-evolution-plan |
| R7 | `$GLOBALS['bw_export_request_result']` cross-hook global state | Low–Med | No | core-evolution-plan |
| R8 | Duplicated term-resolution logic (3 variants) in import engine | Low | No | core-evolution-plan |
| R9 | `bw_site_settings_admin_menu_icon_styles` (L119) loads on all admin pages, no page guard | Low | No | check vs `R-ADM-19` |
| R10 | Legacy orphan option keys + redundant alias writes | Low | No | core-evolution-plan |
| R11 | Google Maps test nonce inlined in `<script>` instead of `wp_localize_script` | Low | No | core-evolution-plan |

Positive confirmations (no risk): all save handlers nonce + capability gated; all AJAX endpoints
authenticated + nonce-checked; Stripe secret keys correctly masked; read/write split for Site
Layout is clean.

---

## Extraction Strategy

Behavior-preserving, no rewrites. Sequenced lowest-blast-radius first. Each step is its own
governed task with mandatory `php -l` + `composer run lint:main` verification, per `AGENTS.md`.

**Phase A — Trivial tab extractions (lowest risk).**
Move Loading, Info, and My Account Front tabs into `admin/tabs/`. Pure cut-and-include; no shared
state. Validates the extraction pattern before touching anything load-bearing.

**Phase B — Site Layout frontend module (highest architectural value).**
Extract sub-systems #4 + #8 (Site Layout module + Layout tab) together into
`includes/modules/site-layout/`. This resolves R1 — moving the frontend runtime out of the admin
file — and is the natural point to add a static/transient memoization layer to the read path.
The 5 frontend hooks move with the module. Layout tab keeps calling the module's getters/sanitizer.

**Phase C — Remaining clean-seam tabs.**
Coming Soon and Redirect tabs into `admin/tabs/` (Redirect carries `bw_normalize_redirect_path()`).

**Phase D — Payment AJAX handlers.**
Extract sub-system #5 to `admin/ajax/`. Address R4 (remove the Google Pay → Apple Pay fallback) as
part of this task, not before.

**Phase E — Import/Export engines.**
Extract Export engine, Import engine, then the tab renderer (in that order). Replace the
`$GLOBALS['bw_export_request_result']` seam (R7) with an explicit return/transient. Largest LOC
move (~4.3k lines) but logically self-contained.

**Phase F — Promotions & Labels, asset enqueue, admin menu.**
Higher coupling: the asset matrix is keyed to tab slugs, so centralize tab/subtab slug constants
first. Admin menu registration stays as the thin shell or moves last.

**Deferred — Account/Login tab + Checkout tab.**
`SUPABASE-FROZEN`. No extraction until the dedicated final Supabase audit (Roadmap Item 21) is
complete. God-function decomposition of these two tabs is blocked by the freeze because their
save blocks and inline JS are interleaved with Supabase auth/provisioning config.

---

## Supabase Freeze Boundary

Per the governance protocol, the following must not be refactored, moved, or rewritten until the
final Supabase audit:

- `bw_site_render_account_page_tab()` (L1930–3290) — all `bw_supabase_*` auth options, Supabase
  OAuth provider config (Google/Facebook/Apple under the Supabase path).
- `bw_site_render_checkout_tab()` Supabase provisioning sub-tab — `bw_supabase_checkout_provision_enabled`,
  `bw_supabase_invite_redirect_url`, `bw_supabase_expired_link_redirect_url`.

If any future task touches this code: **stop and report "Blocked by Supabase freeze policy."**

---

## Appendix A — Option Key Reference

~150 keys. Grouped by tab. `R` = read line, `W` = write line.

### Site Layout / Labels
- `bw_site_layout_settings_v1` — R:489 W:1476 — sanitized via `bw_site_layout_sanitize_settings()`
- `bw_product_labels_settings_v1` — W:1685 — external `bw_sanitize_product_labels_settings()`

### Account/Login tab (non-Supabase)
`bw_account_login_provider` (allowlist), `bw_account_login_image`/`_id`, `bw_account_logo`/`_id`/
`_width`/`_padding_top`/`_padding_bottom`, `bw_account_login_title`/`_subtitle` (+`_supabase`/
`_wordpress` variants), `bw_account_show_social_buttons`, `bw_account_passwordless_url`,
`bw_account_facebook`/`_app_id`/`_app_secret`, `bw_account_google`/`_client_id`/`_client_secret`
— save block L1934–2123, render L2125–2188.

### Account/Login tab (Supabase — FROZEN)
`bw_supabase_project_url`, `bw_supabase_anon_key`, `bw_supabase_service_role_key`,
`bw_supabase_auth_mode`, `bw_supabase_login_mode`, `bw_supabase_jwt_cookie_name`,
`bw_supabase_session_storage`, `bw_supabase_enable_wp_user_linking`, `bw_supabase_debug_log`,
`bw_supabase_with_plugins`, `bw_supabase_registration_mode`, `bw_supabase_provider_signup_url`,
`bw_supabase_provider_reset_url`, `bw_supabase_email_confirm_redirect_url`,
`bw_supabase_magic_link_enabled`, `bw_supabase_otp_allow_signup`,
`bw_supabase_oauth_{google,facebook,apple}_enabled`, `bw_supabase_google_*` (client_id/secret/
redirect/scopes/prompt), `bw_supabase_facebook_*`, `bw_supabase_apple_*` (client_id/team_id/
key_id/private_key/redirect), `bw_supabase_login_password_enabled`,
`bw_supabase_magic_link_redirect_url`, `bw_supabase_oauth_redirect_url`,
`bw_supabase_signup_redirect_url`, `bw_supabase_auto_login_after_confirm`,
`bw_supabase_create_wp_users` — save L2076–2114.

### My Account Front tab
`bw_myaccount_black_box_text` (`wp_kses_post`), `bw_myaccount_support_link` (`esc_url_raw`).

### Checkout tab — appearance/footer/policy
`bw_checkout_logo`/`_align`/`_width`/`_padding_*`, `bw_checkout_show_order_heading`,
`bw_checkout_page_bg`/`grid_bg` (+ legacy `_color` orphans), `bw_checkout_left_bg_color`,
`bw_checkout_right_bg_color`, `bw_checkout_right_sticky_top`/`_margin_top`/`_padding_*`,
`bw_checkout_border_color`, `bw_checkout_legal_text`, `bw_checkout_footer_copyright_text`,
`bw_checkout_show_footer_copyright`, `bw_checkout_show_return_to_shop`,
`bw_checkout_left_width`/`right_width`, `bw_checkout_thumb_ratio`/`_width`, `bw_checkout_footer_text`,
`bw_checkout_hide_billing_heading`, `bw_checkout_hide_additional_heading`,
`bw_checkout_address_heading_label`, `bw_checkout_free_order_message`/`_button_text`,
`bw_checkout_fields_settings`, `bw_checkout_policy_{refund,shipping,privacy,terms,contact}`.

### Checkout tab — Supabase provisioning (FROZEN)
`bw_supabase_checkout_provision_enabled`, `bw_supabase_invite_redirect_url`,
`bw_supabase_expired_link_redirect_url`.

### Checkout tab — payment providers
`bw_google_pay_*` (enabled/test_mode/publishable_key/secret_key/test_*/statement_descriptor/
webhook_secret/test_webhook_secret), `bw_klarna_*`, `bw_apple_pay_*`, `bw_google_maps_*`
(enabled/api_key/autofill/restrict_country) — save L3521–3546.

### Other tabs
`bw_cart_popup_*` (save delegated to `cart-popup/admin/settings-page.php`), `bw_redirects`,
`bw_coming_soon_active`, `bw_coming_soon_brevo_settings`, `bw_loading_global_spinner_hidden`.

### Import engine runtime options
`bw_import_run_lock`, `bw_import_active_run`, `bw_import_run_{uuid}` — ephemeral, see R6.

---

## Appendix B — Verification Notes

This audit is read-only. No PHP was modified, so `php -l` / `composer run lint:main` are not
applicable to this change (docs only). Line numbers were gathered by full reads of the target
file; they reflect the file at audit date and should be re-confirmed before any extraction task,
since extraction will shift them.
