# Blackwork Print Import Guide

## Purpose
Use this guide with the Prints CSV template to help ChatGPT/Codex prepare clean, importable print product rows from catalog images, plate details, and source notes.

## Files to use
- `blackwork-print-import-template.csv`
- this guide: `blackwork-print-import-guide.md`

## How to give the files to ChatGPT/Codex
Provide:
- the CSV template
- this Markdown guide
- front image, detail images, reverse image if available
- source notes about artist, year, technique, material, and condition

## Required fields
- `sku`
- `post_title`
- `post_status`
- `product_type`
- `categories`
- `featured_image`

## Optional fields
- `post_name`
- pricing fields
- tags
- descriptions
- stock fields
- shipping/dimensions
- print-specific meta fields

## Print defaults
- Default category: `Prints`

## Subcategories
- In the broader master schema, subcategories belong in `product_subcategories`
- `product_subcategories` accepts comma-separated child categories under `Prints`
- The compact print template does not currently include this column, so use the main `categories` field unless a later template revision adds it

## Gallery and media rules
- `product_gallery` is the gallery field to use
- It accepts multiple direct image URLs in one cell separated by commas
- Use `featured_image` for the main print image
- If a Dropbox URL is provided with `dl=0`, convert only `dl=0` to `raw=1` before writing it into `featured_image` or `product_gallery`
- Preserve the rest of the Dropbox URL exactly, including query parameters such as `rlkey` and `st`

## Column-by-column guide

| Column | Status | What to write | Example | Notes |
|---|---|---|---|---|
| `sku` | Required | Stable unique SKU | `PRINT-BATS-001` | Required for import identity |
| `post_title` | Required | Product title | `Bats of the World Lithograph` | Human-facing title |
| `post_name` | Optional | Product slug | `bats-of-the-world-lithograph` | Lowercase slug |
| `post_status` | Required | WordPress status | `publish` | Usually `publish` or `draft` |
| `product_type` | Required | Woo product type | `simple` | Use `simple` unless there is a real reason otherwise |
| `regular_price` | Optional | Regular price | `320.00` | Decimal |
| `sale_price` | Optional | Sale price | `280.00` | Leave empty if unused |
| `categories` | Required | Main category list | `Prints` | Use default category unless otherwise needed |
| `tags` | Optional | Comma-separated tags | `Lithograph,Natural History` | Keep concise |
| `post_content` | Optional | Full description | `Original historical print featuring bat studies...` | Use source notes, not guesses |
| `post_excerpt` | Optional | Short description | `Original lithograph with natural history subject matter.` | Short summary |
| `featured_image` | Required | Main print image URL | `https://example.com/images/print-cover.jpg` | Direct URL only |
| `product_gallery` | Optional | Comma-separated gallery image URLs | `https://example.com/images/print-detail-1.jpg,https://example.com/images/print-detail-2.jpg` | Direct URLs only |
| `stock_quantity` | Optional | Stock quantity | `1` | Usually low for prints |
| `manage_stock` | Optional | Stock management | `yes` | `yes` or `no` |
| `stock_status` | Optional | Stock status | `instock` | Standard Woo values |
| `weight` | Optional | Weight | `0.4` | Decimal |
| `length` | Optional | Length | `70` | Decimal/text |
| `width` | Optional | Width | `50` | Decimal/text |
| `height` | Optional | Height | `2` | Decimal/text |
| `shipping_class` | Optional | Shipping class slug/name | `prints-flat` | Optional |
| `tax_status` | Optional | Tax status | `taxable` | Standard Woo value |
| `tax_class` | Optional | Tax class slug | `` | Often empty |
| `meta:_bw_product_type` | Optional | Blackwork branch | `physical` | Prints are usually physical |
| `meta:_print_artist` | Optional | Artist | `Johann Meyer` | Factual only |
| `meta:_print_publisher` | Optional | Publisher | `Blackwork Press` | Factual only |
| `meta:_print_year` | Optional | Year | `1888` | Exact year if known |
| `meta:_print_technique` | Optional | Technique | `Lithography` | Use standard art terms |
| `meta:_print_material` | Optional | Material | `Paper` | Factual only |
| `meta:_print_plate_size` | Optional | Plate or sheet size | `42 x 28 cm` | Keep format consistent |
| `meta:_print_condition` | Optional | Condition | `Good` | Be conservative and factual |

## Common mistakes
- Adding comments or notes inside the CSV
- Guessing technique or artist from style alone
- Mixing dimensions and plate size inconsistently
- Using protected image links instead of direct URLs
- Overstating condition

## AI filling rules
- Keep the CSV clean and importable
- Keep exact column names
- Use direct image URLs
- If uncertain, leave the field empty
- Do not invent artist, date, or technique
