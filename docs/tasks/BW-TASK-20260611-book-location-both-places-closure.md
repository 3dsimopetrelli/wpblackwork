# Blackwork Governance -- Task Closure

**Protocol reference:** `docs/governance/task-close.md`

## 1) Completed Outcome
- Task ID: `BW-TASK-20260611-BOOK-LOCATION-BOTH-PLACES`
- Title: Fix both places that use the Books bibliographic location field
- Status: `CLOSED`

Completed:
- fixed the Books admin metabox field config so `_bw_biblio_location` is labeled `Place of Publication` and appears directly under `Book Year`
- fixed the frontend Books Product Details label source so `_bw_biblio_location` is labeled `Place of Publication` and appears directly under `Year`
- preserved the stored meta key so existing product data is unchanged

## 2) Files Updated
- `metabox/bibliographic-details-metabox.php`
- `includes/widgets/class-bw-product-details-widget.php`
- `docs/tasks/BW-TASK-20260611-book-location-both-places-start.md`
- `docs/tasks/BW-TASK-20260611-book-location-both-places-closure.md`

## 3) Final Documentation Contract
- Books admin uses `bw_get_books_admin_field_config()`
- frontend Books Product Details uses `bw_get_bibliographic_fields()` with a matching widget fallback
- `_bw_biblio_location` remains the saved meta key
- Prints and Digital Product Details remain unchanged

## 4) Validation
- `php -l metabox/bibliographic-details-metabox.php` -> PASS
- `php -l includes/widgets/class-bw-product-details-widget.php` -> PASS

## 5) Manual Test Checklist
- open a Book product in WooCommerce admin
- confirm the Books bibliographic section shows `Place of Publication` directly under `Book Year`
- confirm the frontend Product Details widget shows `Place of Publication` directly under `Year`
- confirm an existing saved location value still persists and displays
- confirm Prints fields remain unchanged
- confirm Digital Product Details fields remain unchanged

## 6) Risks / Follow-up
- no code-path risks identified after the helper and widget fallback were aligned
- the docs-only references to `Book Location` are historical and do not affect runtime behavior
