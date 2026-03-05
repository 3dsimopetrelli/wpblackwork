# Header

## Files
- [custom-header-architecture.md](custom-header-architecture.md): main custom header architecture and runtime contract.

## Admin UI Contract
- `Blackwork Site > Header` adopts the shared Blackwork Admin UI Kit (`admin/css/bw-admin-ui-kit.css`).
- Pattern: `.bw-admin-root` shell, top header + subtitle, action bar with primary save CTA, and card-wrapped tab panels.
- Scope and behavior constraints:
  - UI-only alignment (no changes to `bw_header_settings` keys/defaults/sanitization/save behavior).
  - Styling remains scoped under `.bw-admin-root` and available only on Blackwork Site admin pages.
