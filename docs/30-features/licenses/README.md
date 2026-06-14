# Reusable Licenses

## Purpose

Reusable Licenses provide a shared admin-managed source of License Terms that WooCommerce product variations can reference without duplicating the same two-column table on every variation.

## Admin Location

```text
Blackwork Site → Licenses
```

## Post Type

- CPT slug: `bw_license`
- visibility: admin-only
- menu parent: `blackwork-site-settings`

## Stored Meta

Reusable License rows:

```text
_bw_license_rows
```

Variation-selected reusable License:

```text
_bw_variation_license_id
```

Legacy variation-local fallback meta:

```text
_bw_variation_license_col1
_bw_variation_license_col2
```

Legacy transport/import keys:

```text
_bw_variation_license_col1_json
_bw_variation_license_col2_json
```

## License Rows Structure

Each reusable License stores ordered rows with:

- `label`
- `value`

Default labels for a new License:

1. `Company size:`
2. `Time period:`
3. `Projects:`
4. `Usage:`
5. `Distribution:`
6. `Promotion:`
7. `Know More`

Column 2 supports safe HTML.

## Sanitization

- Column 1 / label: `sanitize_text_field`
- Column 2 / value: KSES-safe HTML
- links are allowed
- `target` and `rel` are preserved when allowed
- scripts, iframes, javascript URLs, and unsafe attributes are stripped

## Variation Contract

Each WooCommerce variation can select one reusable License record via:

```text
_bw_variation_license_id
```

The variation admin UI no longer edits the large inline License Terms table directly.

## Frontend Compatibility

The frontend contract remains unchanged:

```text
license_html
```

The compatibility resolver is still:

```php
bw_get_variation_license_table_html( $variation_id )
```

Resolver order:

1. Read `_bw_variation_license_id`
2. If it points to a valid published `bw_license`, build HTML from `_bw_license_rows`
3. Otherwise fall back to legacy variation-local rows:
   - `_bw_variation_license_col1`
   - `_bw_variation_license_col2`

This preserves the existing `BW-SP Price Variation` runtime and avoids frontend JS changes.
