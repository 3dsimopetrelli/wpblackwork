# Blackwork Governance -- Task Closure

**Protocol reference:** `docs/governance/task-close.md`

## 1) Completed Outcome
- Task ID: `BW-TASK-20260611-BOOK-BIBLIOGRAPHIC-METABOX-ORDER-LABEL`
- Title: Update book bibliographic metabox field order and label
- Status: `CLOSED`

Completed:
- renamed the WooCommerce product edit metabox label from `Book Location` to `Place of Publication`
- moved the field so it appears directly under `Book Year`
- preserved the existing meta key so saved product data remains intact
- verified the frontend Product Details widget uses a separate field-label helper, so the frontend label was left unchanged in this task

## 2) Files Updated
- `metabox/bibliographic-details-metabox.php`
- `docs/tasks/BW-TASK-20260611-book-bibliographic-metabox-order-label-start.md`
- `docs/tasks/BW-TASK-20260611-book-bibliographic-metabox-order-label-closure.md`

## 3) Final Documentation Contract
- the saved meta key remains `_bw_biblio_location`
- the admin metabox now shows `Place of Publication` immediately after `Book Year`
- Prints and Digital Product Details fields were not changed
- frontend Product Details continues to read from `bw_get_bibliographic_fields()` and therefore still shows the existing frontend label unless a separate follow-up changes that helper

## 4) Validation
- `php -l metabox/bibliographic-details-metabox.php` -> PASS
- `composer run lint:main` -> could not be run because `composer` is not available on PATH in this environment

## 5) Manual Test Checklist
- open a WooCommerce product edit screen
- confirm the Books bibliographic section shows:
  - Book Title
  - Book Author
  - Book Publisher
  - Book Year
  - Place of Publication
  - Book Language
  - Book Binding
  - Book Pages
  - Book Edition
  - Book Condition
- confirm the field value persists for existing products
- confirm Prints fields are unchanged
- confirm Digital Product Details fields are unchanged

## 6) Risks / Follow-up
- the frontend Product Details widget still uses its own label helper, so if the frontend should also say `Place of Publication`, that would be a separate follow-up change
- the repository-wide composer lint command could not be executed in this environment because `composer` is unavailable on PATH
