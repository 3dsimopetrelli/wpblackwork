You are filling a Blackwork Digital Product CSV.

First read the attached files in this order:
1. `blackwork-digital-product-import-guide.md`
2. `blackwork-digital-product-import-template.csv`

Blackwork is creating a new Digital product for the Blackwork site. Use the guide as the source of truth for how every CSV column must be filled.

Do not rename columns.
Do not add comment rows.
Do not add guide text inside the CSV.
Keep the CSV importable.
Preserve the existing parent product row and the two standard variation rows: Commercial and Extended.

FEATURED IMAGE URL:
[PASTE FEATURED IMAGE URL HERE]

PRODUCT GALLERY / SLIDE IMAGE URLS:
[PASTE GALLERY / SLIDE IMAGE URLS HERE, ONE PER LINE OR COMMA-SEPARATED]

HOVER IMAGE URL:
[PASTE HOVER IMAGE URL HERE]

HOVER VIDEO URL, IF AVAILABLE:
[PASTE HOVER VIDEO URL HERE OR LEAVE EMPTY]

YEAR, IF KNOWN:
[PASTE YEAR HERE OR LEAVE EMPTY]

AUTHOR / ARTIST, IF KNOWN:
[PASTE AUTHOR OR ARTIST HERE OR LEAVE EMPTY]

TOTAL ASSETS:
[PASTE TOTAL ASSETS NUMBER HERE]

FILE SIZE:
[PASTE FILE SIZE HERE]

FORMATS:
[PASTE FORMATS HERE, FOR EXAMPLE: JPG, PNG, TIFF]

SOURCE NOTES:
[PASTE SOURCE NOTES, ARCHIVE NOTES, TITLE-PAGE NOTES, OR ANY KNOWN METADATA HERE]

PRODUCT CONTEXT / DOWNLOAD DETAILS:
[PASTE COLLECTION NOTES, DOWNLOAD DETAILS, INTENDED PACKAGE CONTENT, OR EXTRA CONTEXT HERE]

Instructions:
- Analyze the featured image and gallery/slide images before filling the CSV.
- Before writing any Dropbox URL into the CSV, convert `dl=0` to `raw=1`.
- Preserve the rest of the URL exactly.
- Apply this to featured image, gallery images, hover image, hover video, and variation images.
- The final CSV must contain the converted `raw=1` Dropbox URLs, not `dl=0` preview links.
- Use the featured image URL in `featured_image`.
- Use the same featured image URL as `variation_image` for both Commercial and Extended unless separate variation images are provided.
- Put all gallery/slide image URLs into `product_gallery` as comma-separated direct URLs in one cell.
- Put the hover image URL into `meta:_bw_slider_hover_image`.
- Put the hover video URL into `meta:_bw_slider_hover_video` only if provided; otherwise leave it empty.
- Use TOTAL ASSETS in:
  - `meta:_bw_assets_count`
  - `meta:_digital_total_assets`
- Use FILE SIZE in:
  - `meta:_bw_file_size`
  - `meta:_digital_file_size`
- Use FORMATS in:
  - `meta:_bw_formats`
  - `meta:_digital_formats`
- Blackwork will not normally provide a ready-made assets list.
- Generate `meta:_digital_assets_list` from the provided total assets, formats, product context, and download details.
- Keep `meta:_digital_assets_list` concise and factual.
- Do not invent specific files or formats that were not provided.
- Good generic assets list examples:
  - `High-resolution image files|Cleaned source exports|Preview images`
  - `JPG image set|PNG transparent assets|Source scans`
  - `Digital image pack|Reference previews|Cleaned archive files`
- Create a clean product title in `post_title`.
- Create a lowercase URL slug in `post_name`.
- Generate a stable parent SKU from the title using the guide rules.
- Generate Commercial and Extended variation SKUs from the parent SKU.
- Use provided year in `meta:_digital_year`; if not provided, fill only if clearly supported.
- Use provided author/artist in `meta:_bw_artist_name`; if not provided, fill only if clearly supported.
- Choose `product_subcategories` only from the allowed list in the guide.
- Choose up to 5 subcategories; if 5 coherent ones are not available, choose at least 3.
- Write exactly 10 `tags` for internal Blackwork site search/discovery, not SEO.
- Create a detailed, natural, SEO-oriented `post_content`.
- Create a concise `post_excerpt`.
- Choose `meta:_digital_technique` only from the controlled technique list in the guide.
- Use `Unknown` for technique if uncertain.
- Do not invent facts not visible in the images or source notes.
- Return the completed CSV content only.
