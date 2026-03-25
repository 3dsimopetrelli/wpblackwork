# Header

## Current State
- The production header is the custom module under `includes/modules/header/*`, not an Elementor widget.
- `Hero Overlap` is the page-scoped mode that lets the header sit above the first hero section while reusing the same dark-zone detection already used by Smart Header.
- The current implementation includes the V1 hardening pass for:
  - fixed overlay startup without white first-paint jump
  - immediate glass panel visibility on desktop and mobile
  - dark-zone color parity for mobile icons and logo
  - server-rendered admin tab fallback for `General`, `Header Scroll`, and `Hero Overlap`

## Files
- [custom-header-architecture.md](custom-header-architecture.md): main custom header architecture and runtime contract.
- [header-admin-settings-map.md](header-admin-settings-map.md): canonical storage, defaults, sanitize flow, and admin/runtime settings map.
- [header-module-spec.md](header-module-spec.md): Tier 0 module boundaries, mode switching, rendering contract, and failure rules.
- [header-responsive-contract.md](header-responsive-contract.md): desktop/mobile duplication contract, responsive state rules, and overlay behavior.
- [fixes/2026-03-25-hero-overlap-hardening.md](fixes/2026-03-25-hero-overlap-hardening.md): task closeout note for the `Hero Overlap` V1 and related hardening work.

## Settings Page UI Contract
- `Blackwork Site > Header` adopts the shared Blackwork Admin UI Kit (`admin/css/bw-admin-ui-kit.css`).
- Pattern: `.bw-admin-root` shell, top header + subtitle, action bar with primary save CTA, primary tabs, and section cards for settings groups.
- Scope and behavior constraints:
  - UI-only alignment (no changes to `bw_header_settings` keys/defaults/sanitization/save behavior).
  - Styling remains scoped under `.bw-admin-root` and available only on Blackwork Site admin pages.
