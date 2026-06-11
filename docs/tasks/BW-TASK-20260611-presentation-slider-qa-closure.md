# Blackwork Governance -- Task Closure

**Protocol reference:** `docs/governance/task-close.md`

## 1) Completed Outcome
- Task ID: `BW-TASK-20260611-PRESENTATION-SLIDER-QA`
- Title: BW-UI Presentation Slider PNG transparency and featured image toggle QA
- Status: `CLOSED`

Completed:
- verified the `BW-UI Presentation Slider` fix against the audited runtime and CSS surfaces
- confirmed the `Include Featured Image` switcher is now placed in the `Slider Settings` section
- confirmed the switcher defaults to `yes` and is limited to `Images Source = Query (Product Gallery)`
- confirmed the query image list is deduped at source before render arrays are built
- confirmed the wrapper matte is removed from Presentation Slider image shells so transparent PNGs can display correctly

## 2) Files Updated
- `includes/widgets/class-bw-presentation-slide-widget.php`
- `assets/css/bw-presentation-slide.css`
- `docs/tasks/BW-TASK-20260611-presentation-slider-qa-closure.md`

## 3) Final Documentation Contract
- `get_images_for_render()` remains the single source of truth for horizontal, vertical, and popup image lists
- the `Include Featured Image` control is scoped to query-based product galleries and defaults to the existing backward-compatible behavior
- `.bw-ps-image` now stays transparent so PNG alpha is preserved
- loading shimmer remains a temporary loading affordance only and is hidden once images load

## 4) QA Notes
- the first implementation pass placed `Include Featured Image` in the wrong control section; QA caught and corrected that by moving it into `Slider Settings`
- no shared Embla core files were changed
- no unrelated widgets were modified

## 5) Validation
- `php -l includes/widgets/class-bw-presentation-slide-widget.php` -> PASS
- `vendor/bin/phpcs --standard=phpcs.xml.dist blackwork-core-plugin.php` via bundled PHP binary -> PASS
- CSS inspection:
  - `#e8e8e8` no longer applies to `.bw-ps-image`
  - Presentation Slider selectors remain scoped locally

## 6) Manual Test Checklist
- frontend product with featured image + gallery
- frontend product with only featured image
- frontend product with transparent PNG gallery images
- `Include Featured Image = yes`
- `Include Featured Image = no`
- Elementor editor preview
- horizontal layout
- vertical layout
- popup image opening

## 7) Risks / Follow-up
- transparent PNGs will now reveal the page or container background behind the slider, which is the intended behavior
- if the featured image is also present in the product gallery, the dedupe logic prevents duplicate slides
- no risk register or broader documentation update was required because the QA pass did not change risk posture

