# Blackwork Governance -- Task Start

## 1) Context
- Task ID: `BW-TASK-20260327-01`
- Task title: Product Details compatibility content-type and metabox extension
- Request source: User request on 2026-03-27
- Expected outcome:
  - extend the existing `BW-SP Product Details` Elementor widget with a new `Content Type` option: `Compatibility`
  - extend the existing WooCommerce `Product Details` metabox with a new `Compatibility` section
  - store compatibility selections at product level using the current metabox save flow style
  - render compatibility data on the frontend using the same visual/structural language as the existing Product Details accordion/table
- Constraints:
  - do not create a new widget
  - do not create a second product-metabox authority
  - do not duplicate accordion/table runtime logic
  - preserve current widget behavior for `Product Details` and `Info Box`
  - default behavior for products with no saved compatibility settings must be “all enabled”

## 2) Task Classification
- Domain: Elementor Widgets / Product Details / WooCommerce Product Metabox
- Incident/Task type: Governed feature extension
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `includes/widgets/class-bw-product-details-widget.php`
  - `metabox/bibliographic-details-metabox.php`
  - `assets/css/bw-product-details.css`
  - Product Details documentation
- Integration impact: Medium
- Regression scope required:
  - widget `Content Type` branching
  - accordion rendering and activation
  - WooCommerce product edit save flow
  - default fallback behavior for products without saved compatibility data

## 3) Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
- Code references to read:
  - `includes/widgets/class-bw-product-details-widget.php`
  - `metabox/bibliographic-details-metabox.php`
  - `metabox/digital-products-metabox.php`
  - `assets/css/bw-product-details.css`
  - `assets/js/bw-product-details.js`

## 4) Analysis Notes
- Current widget control surface:
  - `content_type` supports `product_details` and `info_box`
  - accordion behavior is already shared and responsive
  - box/title/label/value typography and divider styles are already exposed in Elementor
- Current frontend rendering:
  - wrapper `.bw-biblio-widget`
  - optional accordion trigger/body shell
  - `render_product_details()` builds sectioned table-like rows from product meta
- Current metabox authority:
  - `metabox/bibliographic-details-metabox.php`
  - same file registers fields, renders the WooCommerce product edit UI, and saves meta
  - digital/book/print field arrays already exist as helper functions and are reused by the widget
- Key design decision:
  - compatibility options should become another helper-defined field family in the same metabox file
  - frontend default-all behavior must distinguish:
    - untouched product: show all rows
    - explicitly saved with all unchecked: show nothing

## 5) Current Readiness
- Status: OPEN
- Analysis status: READY FOR IMPLEMENTATION
- Immediate recommendation:
  - extend the current metabox helper architecture with compatibility options + a saved/configured marker
  - keep compatibility rendering local to the existing Product Details widget
