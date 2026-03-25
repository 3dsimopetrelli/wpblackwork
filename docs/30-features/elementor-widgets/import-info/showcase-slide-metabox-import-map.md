# Showcase Slide Metabox Import Map

## Purpose
This reference maps importer columns to the WordPress meta keys used by the `Metabox Slide Showcase` product metabox.

Use it when preparing:
- CSV imports
- XML imports
- bulk product enrichment pipelines
- manual importer field mapping

Important runtime rule:
- `_bw_product_type` decides which branch is shown in the product editor
- the importer does not need to "open" the dropdown in the admin UI
- it only needs to write the correct meta values

## Branch Selector
Always import this field:

| Import Column | Meta Key | Type | Allowed Values |
|---|---|---|---|
| `product_type` | `_bw_product_type` | text | `digital`, `physical` |

Behavior:
- `digital` shows the digital-product branch in the metabox
- `physical` shows the physical-product branch in the metabox

## Shared Fields
These fields can be used in both branches.

| Import Column | Meta Key | Type | Notes |
|---|---|---|---|
| `texts_color` | `_bw_texts_color` | hex color | Example: `#ffffff` |
| `showcase_image` | `_bw_showcase_image` | attachment ID or URL | Attachment ID is preferred |
| `button_text` | `_product_button_text` | text | CTA text |
| `button_link` | `_product_button_link` | URL | CTA destination |

## Digital Product Fields
Use these when `_bw_product_type = digital`.

| Import Column | Meta Key | Type | Notes |
|---|---|---|---|
| `showcase_title` | `_bw_showcase_title` | text | Main showcase title |
| `showcase_description` | `_bw_showcase_description` | textarea/text | Long description |
| `file_size_mb` | `_bw_file_size` | text or number | Example: `33` |
| `assets_count` | `_bw_assets_count` | text or number | Example: `56` |
| `formats` | `_bw_formats` | text | Comma-separated list, example: `SVG,PDF,AI` |

## Physical Product Fields
Use these when `_bw_product_type = physical`.

| Import Column | Meta Key | Type | Notes |
|---|---|---|---|
| `info_1` | `_bw_info_1` | text | First physical-product info row |
| `info_2` | `_bw_info_2` | text | Second physical-product info row |

## Legacy / Widget Compatibility Field
This field is still relevant if you use the legacy `BW Static Showcase` product-link behavior.

| Import Column | Meta Key | Type | Notes |
|---|---|---|---|
| `showcase_linked_product` | `_bw_showcase_linked_product` | post ID | Linked product used by `BW Static Showcase` when metabox-driven mode is enabled |

## Recommended Import Strategy
Use two clean input shapes:

### Digital rows
Import:
- `_bw_product_type = digital`
- showcase title
- showcase description
- digital metadata
- CTA fields
- shared fields

### Physical rows
Import:
- `_bw_product_type = physical`
- physical info fields
- CTA fields
- shared fields

This keeps the dataset explicit and avoids mixing branches in the same source row.

## Notes About Conditional Visibility
Conditional visibility in the metabox is an admin-only UI behavior.

That means:
- hidden fields are not technically blocked
- the importer can still write their meta values
- the editor later decides what to display based on `_bw_product_type`

## Attachment Handling Recommendation
For `showcase_image`:
- prefer attachment IDs over URLs when your importer supports media matching
- use URLs only when the importer cannot resolve attachment IDs reliably

## Digital Example
```csv
post_id,product_type,texts_color,showcase_image,showcase_title,showcase_description,file_size_mb,assets_count,formats,button_text,button_link
123,digital,#ffffff,456,"Explore digital collections, buy rare books and original prints","Curated digital archive.",33,56,"SVG,PDF,AI","View all collections","https://example.com/collections"
```

## Physical Example
```csv
post_id,product_type,texts_color,showcase_image,info_1,info_2,button_text,button_link
124,physical,#000000,789,"Giovanni Battista Della Porta","Padua, 1613","View details","https://example.com/product/book"
```

## Verification Checklist
After import, spot-check a few products:
- the correct `Product Type` is selected
- `Texts color` is present
- the correct image is attached
- digital products show digital fields
- physical products show physical fields
- CTA text/link render correctly in showcase widgets
