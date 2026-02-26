# Header Runbook

## 1. Domain Scope
Includes custom header rendering, navigation/search/navshop interactions, and runtime behavior in desktop/mobile contexts.

Related folders:
- `docs/30-features/header/`
- `docs/30-features/navigation/`
- `docs/30-features/smart-header/`

Related docs:
- `../../30-features/header/custom-header-architecture.md`
- `../../30-features/navigation/custom-navigation.md`
- `../../30-features/smart-header/smart-header-guide.md`
- `../regression-protocol.md`

## 2. Critical Risk Points
- Mobile drawer interaction regressions (open/close/ESC/overlay).
- Search overlay break due to DOM move/clipping assumptions.
- Cart/account trigger regressions in navshop.
- Smart header behavior drift during scroll/responsive transitions.

High-risk integrations and dependencies:
- Header module frontend assets and selectors.
- Cart popup coupling from header cart trigger.
- Woo fragments for cart count updates.

## 3. Pre-Maintenance Checklist
- Read header/navigation/smart-header docs first.
- Confirm expected selector contracts and behavior states.
- Identify fragile areas: mobile breakpoints, overlay behavior, cart popup trigger.

## 4. Safe Fix Protocol
- Preserve CSS class/selector contracts used by module JS.
- Isolate visual fixes from behavior logic when possible.
- Do not rename core selectors without full review.
- ADR required for header architecture contract changes.

## 5. Regression Checklist (Domain Specific)
- Test desktop header rendering and interactions.
- Test mobile navigation open/close and escape paths.
- Test search overlay open/close and live-search behavior.
- Test navshop cart/account actions and cart count updates.
- Test smart-header scroll behavior and dark/light transitions.
- Scan console for JS errors on page load and interactions.

## 6. Documentation Update Requirements
- Update `CHANGELOG.md` for behavior-impacting header changes.
- Update header/navigation/smart-header docs when contracts or expected behavior change.
- Update ADR for structural module contract changes.
