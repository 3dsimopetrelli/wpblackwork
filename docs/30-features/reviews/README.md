# Reviews System

## Scope
This directory documents the custom Blackwork reviews system.

It covers:
- the isolated Reviews module under `includes/modules/reviews/`
- the `BW Reviews` Elementor widget
- the `Blackwork Site -> Reviews` and `Reviews Settings` admin surfaces
- the runtime submission / confirmation / moderation flow

## Current Status
- Status: `Almost ready`
- Quality: `~9/10`
- Phase: `Final manual validation`
- Completed fixes:
  - confirmation email hardening (`pending_confirmation` no longer returns false success)
  - modal timeout/failure recovery
  - single modal instance per page
  - verified-buyers vs guest-review policy alignment
- Remaining manual validation:
  - multi-widget confirmation targeting
  - modal accessibility (`focus trap` / `focus restore`)
- Final reference:
  - `docs/tasks/BW-TASK-20260402-reviews-system-final-validation-summary.md`

## Documents
- `reviews-system-guide.md`: module authority, settings, data flow, admin model, frontend/runtime contract

## Runtime authority
- PHP module bootstrap: `includes/modules/reviews/reviews-module.php`
- Admin authority: `includes/modules/reviews/admin/`
- Data authority: `includes/modules/reviews/data/`
- Runtime/AJAX authority: `includes/modules/reviews/runtime/class-bw-reviews-runtime.php`
- Frontend rendering authority: `includes/modules/reviews/frontend/`
- Elementor adapter: `includes/widgets/class-bw-reviews-widget.php`

## Canonical behavior
- Reviews are **not** based on WooCommerce native comments/templates.
- WooCommerce remains the authority for:
  - products
  - customers
  - orders
  - verified-purchase checks
- The custom Reviews module is the authority for:
  - review storage
  - review moderation state
  - confirmation flow
  - frontend rendering
  - review-specific Brevo sync behavior
