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

**Step 2.0 — build helpers.** ✅ DONE @ 72b69d69
- [x] Helpers added + committed: `register_widget_dependencies`, `add_color_var_control` (null-default omission + `$extra`), `add_dimensions_control`, `add_typography_group` (replaces the planned `add_typography_color_pair` to avoid reordering), `sanitize_html_tag`. SKIPPED `get_svg_allowed_tags`/`sanitize_svg_content` merge (behavior-changing — accordion whitelist is a superset of button's). Proven via equivalence harness (15/15 checks).

> VERIFICATION METHOD: a control-capture harness (scratchpad/widget_capture.php) stubs Elementor+WP, runs each widget's `register_controls()` on HEAD vs working copy, and var_export-diffs the full control tree. EMPTY diff = byte-identical Elementor output. Used as the gate for every widget below. (Note: real Elementor add_control/add_responsive_control/add_group_control are PUBLIC; harness matches.)

**Step 2.1 — PILOT (the 4 worst offenders, do these FIRST and stop to self-check).** For each: replace duplicated control blocks with helper calls, then prove the generated Elementor controls + frontend CSS-variable selectors are **byte-identical** to before (reason about it in the commit body; if a helper changes any output, fix the helper, not the widget). One commit per widget.
- [x] `class-bw-license-table-widget.php` @ 94068877 — 34 conversions, capture diff EMPTY
- [x] `class-bw-accordion-widget.php` @ 16490932 — 14 conversions, capture diff EMPTY
- [x] `class-bw-newsletter-subscription-widget.php` @ 96dc4e89 — 19 conversions, capture diff EMPTY
- [x] `class-bw-button-widget.php` @ 6edb8362 — 3 conversions (SVG merge skipped as behavior-changing), capture diff EMPTY
- [x] **Checkpoint PASSED:** helper pattern sound; no helper fixes needed. Each pilot's register_controls() proven byte-identical via capture harness.

**Step 2.2 — SWEEP the remaining 24 widgets.** Same protocol, one widget per commit (small batches OK only if trivially identical). A widget that has *no* matching duplication just gets skipped — note it, don't force a change.
- [x] `class-bw-about-menu-widget.php` @ ca036ff0 — dim x2, typo x1
- [x] `class-bw-animated-banner-widget.php` @ 06e6d37f — typo x1, dim x1
- [x] `class-bw-basic-slide-widget.php` — SKIPPED (no exact matches; only candidate had key-order mismatch)
- [x] `class-bw-big-text-widget.php` @ b352817f — typo x1, dim x1
- [x] `class-bw-divider-widget.php` @ 44ab5444 — color x1
- [x] `class-bw-go-to-app-widget.php` @ 3e25b7f5 — typo x2
- [x] `class-bw-hero-slide-widget.php` @ c4127dc2 — color x2, dim x4, typo x3
- [x] `class-bw-mosaic-slider-widget.php` @ eb8e89c1 — typo x3, dim x3
- [x] `class-bw-presentation-slide-widget.php` @ 2a06b3b4 — dim x1
- [x] `class-bw-price-variation-widget.php` @ d6af49ce — dim x8, typo x2
- [x] `class-bw-product-breadcrumbs-widget.php` @ 92f22160 — dim x1, typo x1
- [x] `class-bw-product-description-widget.php` @ 0d701320 — typo x1
- [x] `class-bw-product-details-widget.php` @ 284e210a — dim x2, typo x4
- [x] `class-bw-product-grid-widget.php` @ 5f954642 — typo x3, dim x3
- [x] `class-bw-product-slider-widget.php` @ a5f8a257 — color x4, dim x3
- [x] `class-bw-psychadelic-banner-widget.php` @ 92938537 — color x3, typo x1, dim x1
- [x] `class-bw-related-post-widget.php` — SKIPPED (only a NUMBER control; no matches)
- [x] `class-bw-related-products-widget.php` @ 10ffa5da — typo x3, dim x1
- [x] `class-bw-reviews-widget.php` — SKIPPED (only a NUMBER control; no matches)
- [x] `class-bw-showcase-slide-widget.php` @ 1f19729e — typo x5, dim x1
- [x] `class-bw-static-showcase-widget.php` @ f8398320 — typo x5, dim x5
- [x] `class-bw-tags-widget.php` @ 0ead9748 — typo x1
- [x] `class-bw-title-product-widget.php` @ 0283d5cc — typo x1
- [x] `class-bw-trust-box-widget.php` — SKIPPED (literal-prop colors, SLIDER radii, slug-specific depends; no matches)

**Step 2.3 — close out.** ✅ DONE — `php -l` clean on all 28 widgets; lint:main green. Definitive aggregate proof: every widget's `register_controls()` capture is byte-identical to the pre-Phase-2 state (commit 72b69d69) — 0 regressions across all 28. (lint:strict on the whole `includes` tree was not used as a gate: it reports thousands of pre-existing baseline violations unrelated to this work; each touched file was phpcbf-normalized + php -l verified instead.)

> NOTE: Phase 3 — including the control-panel file split (`admin/class-blackwork-site-settings.php`) — remains OUT of scope tonight. Do NOT start it.

---

**Last action:** Phases 0–2 COMPLETE. All 28 widgets verified byte-identical to pre-Phase-2 (0 regressions). HARD STOP before Phase 3.

**Final summary:**

Phases 0–2 of BW-TASK-20260623 complete on `refactoring-by-jc` (HARD STOP before Phase 3, as scoped). All behavior-preserving; every step `php -l` + lint verified and committed individually (bisectable).

- **Phase 0** (style): phpcbf on the 10 audit files — 6056 violations auto-fixed (5782→177 errors); remainder documented & deferred. @ f5204038
- **Phase 1** (bootstrap): `blackwork-core-plugin.php` 1616 → 359 lines. Extracted SVG-upload (defd231a), deprecated-widget unregistration (4a2aa2c3), CDN/SRI (44724231), asset-registry (56d9c097). Verified the function-definition set and hook-registration set are an EXACT match vs pre-Phase-1 baseline (zero lost/added/duplicated).
- **Phase 2** (widget de-dup): added 5 `BW_Widget_Helper` methods (72b69d69), proven via equivalence harness (15/15). Refactored 24 of 28 widgets (~155 control-block conversions). SKIPPED with no matching duplication: basic-slide, related-post, reviews, trust-box. SKIPPED as behavior-changing: the SVG-whitelist merge (accordion whitelist is a superset of button's) and the typography "pair" (replaced by add_typography_group). Per-widget verification: a control-capture harness (stubs Elementor+WP) confirmed each widget's `register_controls()` output is byte-identical HEAD-vs-working; final aggregate check confirms all 28 widgets are byte-identical to pre-Phase-2 (0 regressions).

Verification method note: helpers pass already-translated labels (callers keep `__()`), so i18n extraction is unaffected. Color/dimension blocks were converted only when the selector was a single CSS-custom-property (`--var: {{VALUE}};`) or the exact 4-side box template; literal-property, multi-selector, multi-property, and key-reordering cases were deliberately left unchanged to preserve byte-identical output.

Checks at close: lint:main green; php -l clean across all branch-changed PHP; working tree clean (only unrelated untracked .wp-env.json).