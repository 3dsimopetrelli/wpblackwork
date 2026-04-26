# BW Product Master Import Template

## Purpose
This file documents the master import schema for Blackwork products.

Use it when you want one spreadsheet/CSV that can cover:
- standard WordPress product content
- standard WooCommerce product data
- product taxonomies
- Blackwork custom metabox fields
- variation-related payload fields

Template file:
- [bw-product-master-import-template.csv](/Users/simonezanon/Documents/local%20site/BlackWork/wp-content/plugins/wpblackwork/docs/30-features/import-products/bw-product-master-import-template.csv)

## Important Scope Notes
- This schema is designed as a practical master spreadsheet for an external product-management system.
- It mixes:
  - native WordPress post fields
  - WooCommerce product fields
  - taxonomy assignment columns
  - exact Blackwork meta keys
- Some columns are transport columns rather than raw DB keys, for example:
  - `featured_image`
  - `product_gallery`
  - `attributes_json`
  - `default_attributes_json`
  - `variation_attributes_json`
  - `_bw_variation_license_col1_json`
  - `_bw_variation_license_col2_json`
- Variation lifecycle is not formalized in the current Import Engine vNext spec, but variation columns are included here because they are useful for your external software.

## Row Model
- `row_type`
  - `product` for parent/simple/grouped/external rows
  - `variation` for variation child rows
- `parent_sku`
  - empty for parent/simple rows
  - required for variation rows
- `sku`
  - canonical product identity

Recommended usage:
- one parent row per product
- one extra row per variation when needed

## Group 1 — WordPress Core Content Fields

| Column | Meaning | Notes |
|---|---|---|
| `post_id` | Existing post ID | Optional for updates; SKU should stay the main identity |
| `post_status` | WordPress status | Typical values: `publish`, `draft`, `private` |
| `post_title` | Product title | WordPress `post_title` |
| `post_name` | Product slug | WordPress `post_name` |
| `post_content` | Main description | WordPress `post_content` |
| `post_excerpt` | Short description | WordPress `post_excerpt` |
| `menu_order` | Manual order | WordPress `menu_order` |

## Group 2 — Standard WooCommerce Product Fields

| Column | Meaning | Typical Values / Notes |
|---|---|---|
| `woo_product_type` | Woo product type | `simple`, `variable`, `grouped`, `external` |
| `featured` | Featured product flag | `yes/no` or `1/0` depending importer |
| `catalog_visibility` | Catalog visibility | `visible`, `catalog`, `search`, `hidden` |
| `virtual` | Virtual product | `yes/no` |
| `downloadable` | Downloadable product | `yes/no` |
| `regular_price` | Regular price | Decimal |
| `sale_price` | Sale price | Decimal |
| `current_price` | Current/effective price | Optional convenience column |
| `date_on_sale_from` | Sale start | ISO/date format |
| `date_on_sale_to` | Sale end | ISO/date format |
| `tax_status` | Tax status | `taxable`, `shipping`, `none` |
| `tax_class` | Tax class | Text slug |
| `manage_stock` | Stock management | `yes/no` |
| `stock_quantity` | Stock quantity | Integer |
| `stock_status` | Stock status | `instock`, `outofstock`, `onbackorder` |
| `backorders` | Backorders | `no`, `notify`, `yes` |
| `sold_individually` | Sold individually | `yes/no` |
| `weight` | Weight | Decimal/text |
| `length` | Length | Decimal/text |
| `width` | Width | Decimal/text |
| `height` | Height | Decimal/text |
| `shipping_class` | Shipping class | Woo shipping class slug/name |
| `purchase_note` | Purchase note | Text |
| `reviews_allowed` | Reviews enabled | `yes/no` |
| `external_url` | External product URL | For `external` products |
| `external_button_text` | External CTA text | For `external` products |
| `download_limit` | Download limit | Integer |
| `download_expiry` | Download expiry | Days |
| `downloadable_files_json` | Download file list | JSON payload recommended |

## Group 3 — Media

| Column | Meaning | Notes |
|---|---|---|
| `featured_image` | Main product image | Prefer attachment ID or media URL, depending importer |
| `product_gallery` | Product gallery | Comma-separated IDs or URLs |

Relevant Woo storage:
- featured image usually maps to `_thumbnail_id`
- gallery usually maps to `_product_image_gallery`

## Group 4 — Taxonomies

| Column | Meaning | Notes |
|---|---|---|
| `product_cat` | Main product categories | Comma-separated names/slugs/IDs by importer policy |
| `product_subcategories` | Child category assignments | Optional helper column |
| `product_tag` | Product tags / Style-Subject tags | Comma-separated |

Notes:
- `product_cat` and `product_tag` are taxonomy assignments, not post meta.
- In Blackwork, product family context is driven by product category assignment:
  - Digital Collections
  - Books
  - Prints

## Group 5 — Product Relations

| Column | Meaning | Notes |
|---|---|---|
| `upsell_skus` | Upsell product SKUs | Comma-separated |
| `cross_sell_skus` | Cross-sell product SKUs | Comma-separated |
| `grouped_skus` | Grouped product children | Comma-separated |

## Group 6 — Attributes And Variations

These are transport columns for your external software.

| Column | Meaning | Notes |
|---|---|---|
| `attributes_json` | Product attribute definitions | JSON recommended |
| `default_attributes_json` | Default variation attributes | JSON recommended |
| `variation_attributes_json` | Variation attribute values | JSON recommended |
| `variation_regular_price` | Variation regular price | Use on `row_type = variation` |
| `variation_sale_price` | Variation sale price | Use on `row_type = variation` |
| `variation_stock_quantity` | Variation stock qty | Use on variation rows |
| `variation_stock_status` | Variation stock status | Use on variation rows |
| `variation_manage_stock` | Variation manage stock | `yes/no` |
| `variation_backorders` | Variation backorders | `no/notify/yes` |
| `variation_weight` | Variation weight | Optional |
| `variation_length` | Variation length | Optional |
| `variation_width` | Variation width | Optional |
| `variation_height` | Variation height | Optional |
| `variation_description` | Variation description | Optional |

Variation license custom fields:
- `_bw_variation_license_col1_json`
- `_bw_variation_license_col2_json`

Recommended shape:
- JSON arrays with up to 10 slots each
- index-aligned between column 1 and column 2

Example:
```json
["Personal use","Commercial use"]
```

## Group 7 — Blackwork Showcase Slide Metabox

Source:
- `metabox/digital-products-metabox.php`

| Column | Real Key | Meaning |
|---|---|---|
| `_bw_product_type` | `_bw_product_type` | Blackwork showcase branch: `digital` or `physical` |
| `_bw_texts_color` | `_bw_texts_color` | Text/badge color |
| `_bw_showcase_image` | `_bw_showcase_image` | Showcase image |
| `_bw_showcase_title` | `_bw_showcase_title` | Showcase title |
| `_bw_showcase_description` | `_bw_showcase_description` | Showcase description |
| `_bw_file_size` | `_bw_file_size` | Digital file size |
| `_bw_assets_count` | `_bw_assets_count` | Digital asset count |
| `_bw_formats` | `_bw_formats` | Digital formats |
| `_bw_info_1` | `_bw_info_1` | Physical info row 1 |
| `_bw_info_2` | `_bw_info_2` | Physical info row 2 |
| `_product_button_text` | `_product_button_text` | CTA text |
| `_product_button_link` | `_product_button_link` | CTA URL |
| `_bw_showcase_linked_product` | `_bw_showcase_linked_product` | Linked product ID for showcase mode |

## Group 8 — Blackwork Product Details Metabox: Books

Source:
- `metabox/bibliographic-details-metabox.php`

| Real Key | Meaning |
|---|---|
| `_bw_biblio_title` | Book title |
| `_bw_biblio_author` | Book author |
| `_bw_biblio_publisher` | Book publisher |
| `_bw_biblio_year` | Book year |
| `_bw_biblio_language` | Book language |
| `_bw_biblio_binding` | Book binding |
| `_bw_biblio_pages` | Book pages |
| `_bw_biblio_edition` | Book edition |
| `_bw_biblio_condition` | Book condition |
| `_bw_biblio_location` | Book location |

## Group 9 — Blackwork Product Details Metabox: Prints

Source:
- `metabox/bibliographic-details-metabox.php`

| Real Key | Meaning |
|---|---|
| `_print_artist` | Print artist |
| `_print_publisher` | Print publisher |
| `_print_year` | Print year |
| `_print_technique` | Print technique |
| `_print_material` | Print material |
| `_print_plate_size` | Print plate size |
| `_print_condition` | Print condition |

## Group 10 — Blackwork Product Details Metabox: Digital Metadata

Source:
- `metabox/bibliographic-details-metabox.php`

| Real Key | Meaning | Notes |
|---|---|---|
| `_digital_total_assets` | Digital total assets | Text/number |
| `_digital_assets_list` | Digital assets list | Textarea/text |
| `_digital_file_size` | Digital file size | Text |
| `_digital_formats` | Digital formats | Text |
| `_bw_artist_name` | Digital author/artist | Canonical active key |
| `_digital_artist_name` | Legacy mirror of artist | Saved as mirror of `_bw_artist_name` |
| `_digital_source` | Digital source | Text |
| `_digital_publisher` | Digital publisher | Text |
| `_digital_year` | Digital year | Text |
| `_digital_technique` | Digital technique | Text |

## Group 11 — Blackwork Compatibility Flags

Source:
- `metabox/bibliographic-details-metabox.php`

| Real Key | Meaning |
|---|---|
| `_bw_compatibility_configured` | Compatibility flags have been explicitly configured |
| `_bw_compatibility_adobe_illustrator_photoshop` | Adobe Illustrator, Photoshop |
| `_bw_compatibility_figma_sketch_adobe_xd` | Figma, Sketch, Adobe XD |
| `_bw_compatibility_affinity_designer_photo` | Affinity Designer & Photo |
| `_bw_compatibility_coreldraw_inkscape` | CorelDRAW, Inkscape |
| `_bw_compatibility_canva_powerpoint` | Canva, PowerPoint |
| `_bw_compatibility_cricut_silhouette` | Cricut, Silhouette |
| `_bw_compatibility_blender_cinema4d` | Blender, Cinema 4D |

Recommended values:
- `1` for enabled
- empty for disabled

## Group 12 — Hover Media Metabox

Source:
- `includes/product-types/class-bw-product-slider-metabox.php`

| Real Key | Meaning |
|---|---|
| `_bw_slider_hover_image` | Hover image attachment ID |
| `_bw_slider_hover_video` | Hover video attachment ID |

## Group 13 — Legacy Compatibility Keys Still Present In The Plugin

These are still read in compatibility paths and are useful if your external system must backfill old products too.

| Real Key | Meaning |
|---|---|
| `_product_showcase_image` | Legacy showcase image |
| `_product_size_mb` | Legacy file size |
| `_product_assets_count` | Legacy assets count |
| `_product_formats` | Legacy formats |
| `_product_color` | Legacy text color |

## Practical Import Recommendations
- Keep `sku` as the main identity.
- Use `row_type` + `parent_sku` if you need variation rows.
- Prefer canonical Blackwork keys over legacy ones.
- Use categories to place products in the right Blackwork family:
  - digital collections
  - books
  - prints
- Prefer attachment IDs for images if your source system can resolve media safely.
- Use JSON columns for:
  - attributes
  - default attributes
  - variation attributes
  - variation license columns

## If You Want The Next Step
If useful, the next pass can generate one of these automatically:
- a sample filled CSV row for each family: Digital / Books / Prints
- a slimmer CSV with only the columns you really need for your new software
- a direct exporter for one existing Woo product by SKU or product ID
