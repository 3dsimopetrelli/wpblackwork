You are filling a Blackwork Digital Product CSV.

Read the attached files in this order:
1. `blackwork-digital-product-import-guide.md`
2. `blackwork-digital-product-import-template.csv`
3. the filled `product-upload-details-template.yml` attached by Blackwork

Use the YAML file as the source for Blackwork-provided product data and media URLs.

Fill the CSV according to the guide.

Do not rename columns.
Do not remove `meta:` prefixes.
Do not add comment rows.
Keep the CSV importable.
Preserve the existing parent product row and the two standard variation rows: Commercial and Extended.

Instructions:
- Analyze the featured image and gallery images referenced in the YAML before writing title, descriptions, subcategories, and tags.
- Before writing any Dropbox URL into the CSV, convert `dl=0` to `raw=1`.
- Preserve the rest of the URL exactly.
- Apply this to all Dropbox media URLs before writing them into the CSV.
- The final CSV must contain the converted `raw=1` Dropbox URLs, not `dl=0` preview links.
- Use `PRODUCT TITLE` for `post_title`.
- Generate `post_name` from `PRODUCT TITLE`.
- Generate parent SKU from `PRODUCT TITLE`.
- Generate Commercial and Extended variation SKUs from the parent SKU.
- Use `FEATURED IMAGE URL` in `featured_image`.
- Use `FEATURED IMAGE URL` as `variation_image` for both Commercial and Extended unless separate variation images are provided in the future.
- Use `PRODUCT GALLERY` in `product_gallery`.
- Use `HOVER IMAGE URL` in `meta:_bw_slider_hover_image`.
- Use `HOVER VIDEO URL` in `meta:_bw_slider_hover_video` only if provided.
- Use `SHOWCASE IMAGE URLS` for `meta:_bw_showcase_image`, using the first URL if multiple are provided.
- Use `PRICE COMMERCIAL` in the Commercial row `variation_regular_price`.
- Use `PRICE EXTENDED` in the Extended row `variation_regular_price`.
- Use `PRICE` in the parent row `regular_price` only if appropriate.
- Use `TOTAL ASSETS` in:
  - `meta:_bw_assets_count`
  - `meta:_digital_total_assets`
- Use `FORMATS` in:
  - `meta:_bw_formats`
  - `meta:_digital_formats`
- Use `DIGITAL ASSETS LIST` in `meta:_digital_assets_list`; if empty, generate a concise factual assets list from the YAML context.
- Keep `meta:_digital_assets_list` concise and factual.
- Use `YEAR` in `meta:_digital_year` if provided.
- Use `AUTHOR / ARTIST` in `meta:_bw_artist_name` if provided.
- Use `TECHNIQUE` in `meta:_digital_technique`; if empty, use `Unknown`.
- Do not visually guess technique unless Blackwork explicitly asks.
- Choose `product_subcategories` only from the allowed list in the guide.
- Choose up to 5 subcategories; if 5 coherent ones are not available, choose at least 3.
- Write exactly 10 `tags` for internal Blackwork site search/discovery, not SEO.
- Create a detailed, natural, SEO-oriented `post_content`.
- Create a concise `post_excerpt`.
- Do not invent facts not present in the images, YAML, or source notes.
- Create and return the completed CSV as a downloadable `.csv` file.
- Use the filename pattern:
  - `blackwork-digital-product-completed-{product-slug}.csv`
- The CSV file must contain only valid CSV content.
- Do not include Markdown.
- Do not include explanations.
- Do not include guide text.
- Do not add extra rows.
- Preserve the exact template columns.
- Include the completed parent product row and the completed Commercial and Extended variation rows.
- If the platform cannot create a downloadable file, return only the raw CSV content in a single CSV code block.
