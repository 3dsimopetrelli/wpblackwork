# Blackwork Book Import Guide

## Purpose
Use this guide with the Books CSV template to help ChatGPT/Codex fill clean, importable product rows for Blackwork books from cover images, bibliographic notes, and catalog source material.

## Files to use
- `blackwork-book-import-template.csv`
- this guide: `blackwork-book-import-guide.md`

## How to give the files to ChatGPT/Codex
Provide:
- the CSV template
- this Markdown guide
- cover images, inside pages, spine images if available
- bibliographic notes, title page data, and source notes

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
- bibliographic meta

## Book defaults
- Default category: `Books`

## Subcategories
- In the broader master schema, subcategories belong in `product_subcategories`
- `product_subcategories` accepts comma-separated child categories under `Books`
- The compact book template does not currently include this column, so use the main `categories` field unless a later template revision adds it

## Gallery and media rules
- `product_gallery` is the gallery field to use
- It accepts multiple direct image URLs in one cell separated by commas
- Use `featured_image` for the main cover
- If a Dropbox URL is provided with `dl=0`, convert only `dl=0` to `raw=1` before writing it into `featured_image` or `product_gallery`
- Preserve the rest of the Dropbox URL exactly, including query parameters such as `rlkey` and `st`

## Column-by-column guide

| Column | Status | What to write | Example | Notes |
|---|---|---|---|---|
| `sku` | Required | Stable unique SKU | `BOOK-BATS-001` | Required for import identity |
| `post_title` | Required | Product title | `Bats of the World Encyclopedia` | Human-facing title |
| `post_name` | Optional | Product slug | `bats-of-the-world-encyclopedia` | Lowercase slug |
| `post_status` | Required | WordPress status | `publish` | Usually `publish` or `draft` |
| `product_type` | Required | Woo product type | `simple` | Use `simple` unless there is a real reason otherwise |
| `regular_price` | Optional | Regular price | `95.00` | Decimal |
| `sale_price` | Optional | Sale price | `79.00` | Leave empty if unused |
| `categories` | Required | Main category list | `Books` | Use default category unless otherwise needed |
| `tags` | Optional | Comma-separated tags | `Natural History,Rare Books` | Keep concise |
| `post_content` | Optional | Full description | `Reference volume covering bat species...` | Use source notes, not guesses |
| `post_excerpt` | Optional | Short description | `Natural history reference volume...` | Short summary |
| `featured_image` | Required | Main cover image URL | `https://example.com/images/book-cover.jpg` | Direct URL only |
| `product_gallery` | Optional | Comma-separated gallery image URLs | `https://example.com/images/book-spine.jpg,https://example.com/images/book-inside.jpg` | Direct URLs only |
| `stock_quantity` | Optional | Stock quantity | `3` | Integer |
| `manage_stock` | Optional | Stock management | `yes` | `yes` or `no` |
| `stock_status` | Optional | Stock status | `instock` | Standard Woo values |
| `weight` | Optional | Product weight | `1.2` | Decimal |
| `length` | Optional | Length | `30` | Decimal/text as used by your catalog |
| `width` | Optional | Width | `22` | Decimal/text |
| `height` | Optional | Height | `4` | Decimal/text |
| `shipping_class` | Optional | Shipping class slug/name | `books-standard` | Optional |
| `tax_status` | Optional | Tax status | `taxable` | Standard Woo value |
| `tax_class` | Optional | Tax class slug | `` | Often empty |
| `meta:_bw_product_type` | Optional | Blackwork branch | `physical` | Books are usually physical |
| `meta:_bw_biblio_title` | Optional | Bibliographic title | `Bats of the World Encyclopedia` | Can match title |
| `meta:_bw_biblio_author` | Optional | Author | `Johann Meyer` | Factual only |
| `meta:_bw_biblio_publisher` | Optional | Publisher | `Blackwork Press` | Factual only |
| `meta:_bw_biblio_year` | Optional | Publication year | `1888` | Exact year if known |
| `meta:_bw_biblio_language` | Optional | Language | `English` | Factual only |
| `meta:_bw_biblio_binding` | Optional | Binding | `Hardcover` | Examples: Hardcover, Softcover |
| `meta:_bw_biblio_pages` | Optional | Page count | `384` | Integer/text |
| `meta:_bw_biblio_edition` | Optional | Edition | `First edition` | Factual only |
| `meta:_bw_biblio_condition` | Optional | Condition | `Very Good` | Use consistent condition wording |
| `meta:_bw_biblio_location` | Optional | Storage/location | `Blackwork Milan` | Internal location if available |

## Common mistakes
- Writing comments into the CSV
- Renaming columns
- Guessing author/publisher/year from the cover alone
- Using non-direct image URLs
- Mixing condition notes into unrelated fields

## AI filling rules
- Keep the CSV clean and importable
- Keep exact column names
- Use direct image URLs
- If a bibliographic fact is uncertain, leave it empty
- Be conservative with condition and edition statements
