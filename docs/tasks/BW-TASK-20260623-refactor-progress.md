# BW-TASK-20260623 — Refactor Runbook & Progress Tracker

**Branch:** `refactoring-by-jc` · **Scope tonight:** Phases 0–2 ONLY · **Plan:** `BW-TASK-20260623-full-refactoring-plan.md`

This file is the single source of truth for the overnight loop. After each step, update the checkboxes and the "Last action" line below so that if context is compacted or the loop re-enters, it can resume from here + `git log`.

## Operating rules (read every iteration)

1. **Behavior-preserving only.** No feature changes, no logic changes except the explicitly-planned options-manager (which is NOT in tonight's 0–2 scope).
2. **One phase = one or more commits**, each self-contained and bisectable. Never mix phases in a commit.
3. **After every PHP edit:** `php -l <file>`; **after every task that touches PHP:** `php ./vendor/bin/phpcs -d memory_limit=512M --standard=phpcs.xml.dist <files>` and `composer run lint:main`. Record results in the commit body.
4. **Preserve all public function names and asset handles.** Before deleting/moving any `bw_*` function or `wp_register_*` handle, `grep -rn` for references first. Add back-compat shims if anything external references a moved symbol.
5. **Verify before claiming done.** Run the checks; paste the output. Evidence before assertions.
6. **HARD STOP after Phase 2.** Do NOT begin Phase 3 (settings god-file / WooCommerce / checkout-subscribe / media-folders splits). Leave that for human-reviewed daytime work.
7. When Phase 2 is committed and green: write the final summary at the bottom, send a PushNotification with a one-line outcome, then stop the loop.

## Progress

### Phase 0 — phpcbf formatting auto-fix (risk: none) ✅ DONE
- [x] Run `phpcbf` on the 10 flagged files (cart-popup x3, licenses cpt x2, media-folders-admin, accordion, license-table, newsletter) — 6056 fixed, errors 5782→177
- [x] Re-run phpcs summary; non-auto-fixable remainder (194) documented in commit body (doc-comments, security sniffs, Yoda/style) — deferred, out of whitespace-only scope
- [x] `php -l` touched files (clean) + `composer run lint:main` (green)
- [x] Commit: `style: phpcbf auto-fix formatting debt on new feature files` @ f5204038

### Phase 1 — bootstrap decomposition (risk: low)
- [ ] Extract SVG-upload security (`blackwork-core-plugin.php` ~35–418) → `includes/svg-upload/class-bw-svg-upload-handler.php`
- [ ] Extract asset registry (~731–1616) → `includes/assets/class-bw-asset-registry.php` (declarative config + shared register helper)
- [ ] Extract CDN/SRI (~797–885) → `includes/assets/class-bw-cdn-sri-manager.php`
- [ ] Extract deprecated-widget unregistration (~702–729) → `includes/widgets/class-bw-widget-unregistration.php`
- [ ] grep-verify every moved handle/function still referenced correctly
- [ ] `php -l` + `composer run lint:main` + `lint:strict`
- [ ] Commit per extraction (4 commits)

### Phase 2 — widget control de-duplication: ALL 28 widgets (risk: low per widget, high volume)

**Step 2.0 — build helpers.** Add to `includes/class-bw-widget-helper.php`:
`register_widget_dependencies`, `add_color_var_control`, `add_typography_color_pair`, `add_dimensions_control`, `get_svg_allowed_tags`+`sanitize_svg_content`, `sanitize_html_tag`. Commit helpers alone first.
- [ ] Helpers added + committed

**Step 2.1 — PILOT (the 4 worst offenders, do these FIRST and stop to self-check).** For each: replace duplicated control blocks with helper calls, then prove the generated Elementor controls + frontend CSS-variable selectors are **byte-identical** to before (reason about it in the commit body; if a helper changes any output, fix the helper, not the widget). One commit per widget.
- [ ] `class-bw-license-table-widget.php`
- [ ] `class-bw-accordion-widget.php`
- [ ] `class-bw-newsletter-subscription-widget.php`
- [ ] `class-bw-button-widget.php` (SVG sanitization only)
- [ ] **Checkpoint:** after the 4 pilots, re-confirm the helper pattern is sound before sweeping the rest. If any pilot needed a helper fix, re-verify the others.

**Step 2.2 — SWEEP the remaining 24 widgets.** Same protocol, one widget per commit (small batches OK only if trivially identical). A widget that has *no* matching duplication just gets skipped — note it, don't force a change.
- [ ] `class-bw-about-menu-widget.php`
- [ ] `class-bw-animated-banner-widget.php`
- [ ] `class-bw-basic-slide-widget.php`
- [ ] `class-bw-big-text-widget.php`
- [ ] `class-bw-divider-widget.php`
- [ ] `class-bw-go-to-app-widget.php`
- [ ] `class-bw-hero-slide-widget.php`
- [ ] `class-bw-mosaic-slider-widget.php`
- [ ] `class-bw-presentation-slide-widget.php`
- [ ] `class-bw-price-variation-widget.php`
- [ ] `class-bw-product-breadcrumbs-widget.php`
- [ ] `class-bw-product-description-widget.php`
- [ ] `class-bw-product-details-widget.php`
- [ ] `class-bw-product-grid-widget.php`
- [ ] `class-bw-product-slider-widget.php`
- [ ] `class-bw-psychadelic-banner-widget.php`
- [ ] `class-bw-related-post-widget.php`
- [ ] `class-bw-related-products-widget.php`
- [ ] `class-bw-reviews-widget.php`
- [ ] `class-bw-showcase-slide-widget.php`
- [ ] `class-bw-static-showcase-widget.php`
- [ ] `class-bw-tags-widget.php`
- [ ] `class-bw-title-product-widget.php`
- [ ] `class-bw-trust-box-widget.php`

**Step 2.3 — close out.** `php -l` all touched widgets; `composer run lint:strict` on `includes`; summarize which widgets were refactored vs skipped (no-duplication) in the final summary below.

> NOTE: Phase 3 — including the control-panel file split (`admin/class-blackwork-site-settings.php`) — remains OUT of scope tonight. Do NOT start it.

---

**Last action:** Phase 0 committed @ f5204038 (whitespace/style only, gate green). Starting Phase 1 — bootstrap decomposition, SVG-upload extraction first.

**Final summary:** _(loop fills this in before stopping)_
