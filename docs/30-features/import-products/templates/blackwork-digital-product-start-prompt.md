You are filling a Blackwork Digital Product CSV.

Read the attached files in this order:
1. `blackwork-digital-product-import-guide.md`
2. `blackwork-digital-product-import-template.csv`
3. the filled `product-upload-details-template.yml` or `product-upload-details-template.yaml` attached by Blackwork

Use the YAML file as the source for Blackwork-provided product data and media URLs.

Fill the CSV according to the guide.

Image access is mandatory:
- Before filling the CSV, you must visually access and analyze the featured image and product gallery images from the YAML.
- Never attempt to analyze the original Dropbox `dl=0` preview URL.
- Use the Dropbox direct URL access sequence below before opening, analyzing, previewing, or writing any Dropbox URL.
- If you cannot access or visually analyze the featured image and product gallery images, stop the operation.
- Do not generate the completed CSV.
- Do not continue using only YAML text/source notes.
- Do not invent tags, subcategories, title, description, subject matter, or visual details.
- Return a blocking message explaining which images could not be accessed and ask Blackwork to provide accessible links or upload the images directly.

Dropbox direct URL access sequence:
- Step 1: convert `dl=0` to `raw=1`.
- Step 2: if the `raw=1` version is inaccessible for visual analysis, try `dl=1`.
- Step 3: if that is still inaccessible, change the host from `www.dropbox.com` to `dl.dropboxusercontent.com` and remove preview/download flags like `dl=0`, `dl=1`, or `raw=1`.
- Preserve the `/scl/fi/...` path, filename, `rlkey`, `st`, and all other non-preview query parameters exactly.
- Use the first accessible direct-media Dropbox variant for image analysis.
- Write only a direct-access Dropbox URL into the CSV, never the original `dl=0` preview URL.

Manual image upload recovery:
- If the featured image or product gallery images cannot be visually accessed through the Dropbox direct URL sequence, stop the operation and ask Blackwork to upload the required images manually into the chat/session.
- Do not generate the CSV until the required images are visually available.
- Specify which mandatory images are missing or inaccessible, for example:
  - `FEATURED IMAGE URL`
  - `PRODUCT GALLERY 01`
  - `PRODUCT GALLERY 02`
- When Blackwork uploads the images manually:
  - use the uploaded images only for visual analysis
  - continue filling the CSV
  - keep using the YAML media URLs for CSV media fields
  - convert Dropbox URLs into direct/WordPress-compatible URLs before writing them into the CSV
  - do not write chat attachment URLs or internal upload references into the CSV

Filename matching for manual uploads:
- When Blackwork uploads images manually because Dropbox access failed, match each uploaded image to the YAML field by filename.
- Extract filenames from the YAML media URLs.
- Compare them with the uploaded image filenames.
- Use exact filename matches whenever possible.
- If exact match fails, URL-decode filenames and compare again.
- If exact match still fails, compare case-insensitively only after safe normalization.
- Use uploaded images only for visual analysis.
- Keep using the converted YAML URLs in the CSV.
- If an uploaded filename does not exactly or safely match a YAML media filename, do not guess.
- If two uploaded files have the same filename, do not guess.
- If any required YAML media filename is missing from the uploaded files, stop and ask Blackwork for the missing file.
- If matching is still ambiguous because of URL encoding, spaces, `&`, or case differences, stop and ask Blackwork for confirmation.

Do not rename columns.
Do not remove `meta:` prefixes.
Do not add comment rows.
Keep the CSV importable.
Preserve the existing parent product row and the two standard variation rows: Commercial and Extended.

Instructions:
- Analyze the featured image and gallery images referenced in the YAML before writing title, descriptions, subcategories, and tags.
- Never attempt to analyze the original Dropbox `dl=0` preview URL.
- Before opening, analyzing, previewing, or writing any Dropbox URL, use this sequence:
  - Step 1: convert `dl=0` to `raw=1`
  - Step 2: if inaccessible, try `dl=1`
  - Step 3: if inaccessible, switch the host to `dl.dropboxusercontent.com` and remove `dl=0`, `dl=1`, or `raw=1`
- Preserve the `/scl/fi/...` path, filename, `rlkey`, `st`, and all other non-preview query parameters exactly.
- Use the first accessible direct-media Dropbox variant for image analysis.
- Use the first accessible direct-media Dropbox variant or the most stable direct Dropbox variant in the final CSV output.
- Do not write Dropbox `dl=0` links into the CSV.
- Apply this to all Dropbox media URLs before writing them into the CSV.
- The final CSV must contain a direct-access Dropbox URL, not the original `dl=0` preview link.
- If none of the Dropbox direct-media variants can be visually accessed for the featured image or product gallery images, stop immediately and do not generate the CSV.
- If Dropbox direct-media access fails for mandatory images, ask Blackwork to upload those images manually into the chat/session before continuing.
- Use manually uploaded images only for visual analysis.
- Continue using converted YAML media URLs in the CSV fields.
- Do not write chat attachment URLs or internal upload references into the CSV.
- Use `PRODUCT TITLE` for `post_title`; if empty, generate a clear title from the visual subject and source context.
- Generate `post_name` from the final `post_title`.
- Generate parent SKU from the final `post_title`.
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
- Keep the same delimiter, quoting style, and column order as the template.
- The CSV file must contain only valid CSV content.
- Preserve the exact template columns.
- Do not add extra columns.
- Do not include Markdown.
- Do not include explanations.
- Do not include guide text.
- Do not add extra rows.
- Include the completed parent product row and the completed Commercial and Extended variation rows.
- If the platform cannot create a downloadable file, return only the raw CSV content in a single CSV code block.
