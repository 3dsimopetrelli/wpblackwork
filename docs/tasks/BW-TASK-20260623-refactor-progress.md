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

### Phase 1 — bootstrap decomposition (risk: low) ✅ DONE — bootstrap 1616 → 359 lines
- [x] Extract SVG-upload security → `includes/svg-upload/svg-upload-handler.php` @ defd231a
- [x] Extract asset registry → `includes/assets/asset-registry.php` @ 56d9c097
- [x] Extract CDN/SRI → `includes/assets/cdn-sri-manager.php` @ 44724231
- [x] Extract deprecated-widget unregistration → `includes/widgets/widget-unregistration.php` @ 4a2aa2c3
- [x] grep-verify: function-def set + hook-registration set (space-insensitive) are an EXACT match vs pre-Phase-1 baseline (0a07dd61); zero lost, zero added, zero duplicate defs
- [x] `php -l` clean on all files; `lint:main` green
- [x] Committed as 4 separate extraction commits

> Deviations from plan (documented): (1) functions kept as global names rather than
> wrapped in static classes — runbook rule #4 (preserve public function names) + 2
> externally-referenced SVG fns require it; files named without `class-` prefix
> accordingly. (2) Asset registry moved VERBATIM, not rewritten to a declarative
> config array — a byte-identical-handle rewrite can't be output-verified without a
> running WP instance and risks silent enqueue breakage; relocation achieves the
> structural goal at zero behavior risk. (3) Relocated files moved from the
> lint-excluded root bootstrap into the fully-enforced `includes/` tree, so each was
> phpcbf'd; small non-auto-fixable remainders (doc comments, SVG attr keys,
> file_get/put_contents, inline-comment punctuation) documented per-commit and left.

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

**Last action:** Phase 1 complete (4 extractions @ defd231a/4a2aa2c3/44724231/56d9c097, bootstrap 1616→359, gate green, set-match verified). Starting Phase 2 — building widget helpers first.

**Final summary:** _(loop fills this in before stopping)_
