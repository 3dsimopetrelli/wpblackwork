You are filling a Blackwork Print Product CSV.

First read the attached files in this order:
1. `blackwork-print-import-guide.md`
2. `blackwork-print-import-template.csv`

Blackwork is creating a new Print product for the Blackwork site. Use the guide as the source of truth for how every CSV column must be filled.

Do not rename columns.
Do not add comment rows.
Do not add guide text inside the CSV.
Keep the CSV importable.

FEATURED IMAGE URL:
[PASTE FEATURED IMAGE URL HERE]

PRODUCT GALLERY URLS:
[PASTE PRODUCT GALLERY URLS HERE, ONE PER LINE OR COMMA-SEPARATED]

ARTIST, IF KNOWN:
[PASTE ARTIST HERE OR LEAVE EMPTY]

PUBLISHER, IF KNOWN:
[PASTE PUBLISHER HERE OR LEAVE EMPTY]

YEAR, IF KNOWN:
[PASTE YEAR HERE OR LEAVE EMPTY]

TECHNIQUE, IF KNOWN:
[PASTE TECHNIQUE HERE OR LEAVE EMPTY]

MATERIAL, IF KNOWN:
[PASTE MATERIAL HERE OR LEAVE EMPTY]

PLATE SIZE, IF KNOWN:
[PASTE PLATE SIZE HERE OR LEAVE EMPTY]

CONDITION NOTES:
[PASTE CONDITION NOTES HERE OR LEAVE EMPTY]

DIMENSIONS / WEIGHT:
[PASTE DIMENSIONS / WEIGHT HERE OR LEAVE EMPTY]

SOURCE NOTES:
[PASTE SOURCE NOTES, CATALOG NOTES, OR ANY KNOWN METADATA HERE]

PRODUCT CONTEXT:
[PASTE COLLECTION NOTES, CATALOG CONTEXT, OR EXTRA DETAILS HERE]

Instructions:
- Read the matching guide first and the matching CSV second.
- Analyze the featured image and any gallery images before filling the CSV.
- Use direct image URLs only.
- Use the featured image URL in `featured_image`.
- Put all gallery URLs into `product_gallery` as comma-separated direct URLs in one cell.
- Create a clean product title in `post_title`.
- Create a lowercase URL slug in `post_name`.
- Fill artist, year, technique, material, and condition only from source notes or clearly supported evidence.
- Create a detailed, natural, SEO-oriented `post_content`.
- Create a concise `post_excerpt`.
- Do not invent facts.
- Return the completed CSV content only.
