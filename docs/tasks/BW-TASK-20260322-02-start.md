# BW-TASK-20260322-02 Start

## Title
Re-audit Product Slider after recent runtime updates

## Trigger
The previous audit was no longer trustworthy because the widget changed again and the repository runtime no longer matched older conclusions.

## Objective
Re-read the live Product Slider implementation from code and realign active documentation to the current repository state.

## Scope
- `includes/widgets/class-bw-product-slider-widget.php`
- `assets/js/bw-product-slider.js`
- `assets/css/bw-product-slider.css`
- `blackwork-core-plugin.php`
- active architecture / inventory / regression docs

## Constraints
- no runtime rewrite in this task
- historical Slick-era fix notes remain historical and are not rewritten as if current
- current-state docs must reflect the live `bw-product-slider` authority

## Governing Reference
- `docs/governance/task-close.md`
