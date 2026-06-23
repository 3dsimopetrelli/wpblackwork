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

### Phase 0 — phpcbf formatting auto-fix (risk: none)
- [ ] Run `phpcbf` on the 10 flagged files (cart-popup x3, licenses x3, media-folders-admin, accordion, license-table, newsletter)
- [ ] Re-run phpcs summary; hand-resolve / document any non-auto-fixable remainder
- [ ] `php -l` touched files + `composer run lint:main`
- [ ] Commit: `style: phpcbf auto-fix formatting debt on new feature files`

### Phase 1 — bootstrap decomposition (risk: low)
- [ ] Extract SVG-upload security (`blackwork-core-plugin.php` ~35–418) → `includes/svg-upload/class-bw-svg-upload-handler.php`
- [ ] Extract asset registry (~731–1616) → `includes/assets/class-bw-asset-registry.php` (declarative config + shared register helper)
- [ ] Extract CDN/SRI (~797–885) → `includes/assets/class-bw-cdn-sri-manager.php`
- [ ] Extract deprecated-widget unregistration (~702–729) → `includes/widgets/class-bw-widget-unregistration.php`
- [ ] grep-verify every moved handle/function still referenced correctly
- [ ] `php -l` + `composer run lint:main` + `lint:strict`
- [ ] Commit per extraction (4 commits)

### Phase 2 — widget control de-duplication (risk: low–med)
- [ ] Add `BW_Widget_Helper` methods: `register_widget_dependencies`, `add_color_var_control`, `add_typography_color_pair`, `add_dimensions_control`, `get_svg_allowed_tags`+`sanitize_svg_content`, `sanitize_html_tag`
- [ ] Refactor `class-bw-license-table-widget.php` to use helpers (commit)
- [ ] Refactor `class-bw-accordion-widget.php` (commit)
- [ ] Refactor `class-bw-newsletter-subscription-widget.php` (commit)
- [ ] Refactor `class-bw-button-widget.php` (SVG only) (commit)
- [ ] `lint:strict` on `includes`; confirm no control output changed (diff reasoning in commit body)

---

**Last action:** _(loop updates this each iteration — e.g. "Phase 0 committed at <sha>; starting Phase 1 SVG extraction")_

**Final summary:** _(loop fills this in before stopping)_
