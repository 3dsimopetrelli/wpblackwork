# Brevo

## Files
- [brevo-architecture-map.md](brevo-architecture-map.md): official Brevo integration architecture map (consent gate, triggers, idempotent sync, observability).
- [brevo-mail-marketing-architecture.md](brevo-mail-marketing-architecture.md): Brevo integration architecture and runtime model.
- [brevo-mail-marketing-roadmap.md](brevo-mail-marketing-roadmap.md): Brevo roadmap and phases.
- [mail-marketing-qa-checklist.md](mail-marketing-qa-checklist.md): QA checklist for Brevo integration.
- [subscribe.md](subscribe.md): governance and update workflow for subscription model.

## Admin UI Contract
- `Blackwork Site > Mail Marketing` adopts the shared Blackwork Admin UI Kit (`admin/css/bw-admin-ui-kit.css`).
- Layout pattern: `.bw-admin-root` shell, header + subtitle, action bar with save CTA, card-grouped settings, and UI-kit tab styling.
- Scope and behavior constraints:
  - UI-only alignment (no option-key/default/validation/runtime behavior changes).
  - CSS remains scoped under `.bw-admin-root` and loaded only on Blackwork Site admin pages.
