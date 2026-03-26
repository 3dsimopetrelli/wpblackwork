# Blackwork Governance -- Task Start

## 1) Context
- Task ID: `BW-TASK-20260325-02`
- Task title: Governed `Header` Hero Overlap implementation and hardening
- Request source: User request on 2026-03-25
- Expected outcome:
  - inspect the current custom Header module and related documentation before changes
  - add a new page-scoped `Hero Overlap` mode under `Blackwork Site > Header`
  - keep the header above the first hero section on selected pages
  - reuse the existing dark-zone detection already used by the Header
  - make the overlay start stable, without first-paint jump or delayed white/black color correction
  - ensure glass treatment is visible in overlay, including on mobile
  - keep mobile icons visually aligned with logo dark-zone behavior
  - close the intervention with synchronized technical documentation
- Constraints:
  - keep the Header module presentation-only
  - do not introduce a second color-detection authority
  - avoid regressions on desktop/mobile navigation, search, and cart badge behavior
  - preserve existing Woo fragments and cart popup fallback behavior
  - keep the implementation minimal in V1: page selection only, no extra per-template or per-section control system

## 2) Task Classification
- Domain: Header / Frontend Runtime / Admin Settings / Responsive UX
- Incident/Task type: Governed feature implementation + hardening
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 0
- Affected systems:
  - Header admin settings surface
  - Header frontend render/runtime
  - Header responsive desktop/mobile presentation
  - Hero Slide editorial rendering touchpoint
- Integration impact: High
- Regression scope required:
  - Header admin tabs and save flow
  - desktop header startup
  - mobile header startup
  - dark-zone switching
  - search/cart/navigation icon states
  - hero overlap pages vs normal pages

## 3) Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/header/README.md`
  - `docs/30-features/header/custom-header-architecture.md`
  - `docs/30-features/header/header-module-spec.md`
  - `docs/30-features/header/header-responsive-contract.md`
  - `docs/30-features/header/header-admin-settings-map.md`
- Ops/control docs to read:
  - `docs/50-ops/runbooks/header-runbook.md`
  - `docs/50-ops/audits/header-system-technical-audit.md`
- Governance docs to read:
  - `docs/templates/task-start-template.md`
  - `docs/templates/task-closure-template.md`
  - `docs/governance/task-close.md`
- Architecture references to read:
  - `includes/modules/header/admin/settings-schema.php`
  - `includes/modules/header/admin/header-admin.php`
  - `includes/modules/header/frontend/assets.php`
  - `includes/modules/header/frontend/header-render.php`
  - `includes/modules/header/templates/header.php`
  - `includes/modules/header/assets/js/header-init.js`
  - `includes/modules/header/assets/css/header-layout.css`

## 4) Scope Declaration
- Proposed strategy:
  - add a new `Hero Overlap` tab in Header admin
  - persist settings under `bw_header_settings.hero_overlap.*`
  - activate overlap only on selected page IDs
  - expose a runtime class/data attribute for selected pages
  - start the header directly in overlay mode from server-rendered CSS
  - keep dark/on-dark color switching owned by the existing detector
  - harden mobile startup and icon parity only where needed
- Files likely impacted:
  - `includes/modules/header/admin/settings-schema.php`
  - `includes/modules/header/admin/header-admin.php`
  - `includes/modules/header/admin/header-admin.js`
  - `includes/modules/header/frontend/assets.php`
  - `includes/modules/header/frontend/header-render.php`
  - `includes/modules/header/templates/header.php`
  - `includes/modules/header/assets/js/header-init.js`
  - `includes/modules/header/assets/css/header-layout.css`
  - `docs/30-features/header/*`
  - `docs/tasks/BW-TASK-20260325-02-closure.md`
- Explicitly out-of-scope surfaces:
  - new template-level routing or page-template engine
  - new color-detection subsystem
  - theme-wide hero architecture changes outside the Header module
  - business logic changes in WooCommerce/cart/auth
- Risk analysis:
  - early paint mismatch can create a visible jump on hero pages
  - desktop/mobile duplicated markup can drift if only one branch is updated
  - admin tab structure can silently fail if panel wrappers are mis-nested
- ADR evaluation (REQUIRED / NOT REQUIRED): NOT REQUIRED

## 5) Runtime Surface Declaration
- New hooks expected:
  - none
- Hook priority modifications:
  - none expected
- Filters expected:
  - none
- AJAX endpoints expected:
  - none
- Admin routes expected:
  - none beyond the existing Header settings page

## 6) Governance Impact Analysis
- Authority surfaces touched:
  - Header admin settings
  - Header frontend runtime and responsive state machine
- Data integrity risk:
  - low; settings are presentational and page-scoped
- Security surface changes:
  - low; no new public AJAX or mutation path
- Runtime hook/order changes:
  - none expected beyond internal runtime branching
- Requires ADR? No
- Risk register impact required? No
- Risk dashboard impact required? No

## 7) System Invariants Check
- Declared invariants that MUST remain true:
  - Header remains Tier 0 presentation/runtime orchestration only
  - dark-zone detection remains single-authority
  - commerce/account/cart flows remain reachable under failure
  - desktop/mobile duplication remains presentation-only
- Any invariant at risk? Yes
  - first-paint convergence on overlap pages
- Mitigation plan for invariant protection:
  - force overlay startup server-side
  - recheck layout after load/fonts
  - validate both desktop and mobile paths

## 8) Testing Strategy
- Local testing plan:
  - verify new admin tab and save behavior
  - verify selected pages start in overlap mode
  - verify normal pages remain unchanged
  - verify dark-zone switching on hero pages
  - verify mobile glass and icon color switching
  - verify no breakage in search/cart/navigation behavior
- Edge cases expected:
  - hero image/layout settles late
  - manually marked `.smart-header-dark-zone`
  - mobile icons uploaded as custom images vs inline SVG fallback

## 9) Documentation Update Plan
- `docs/30-features/`
  - Impacted? Yes
  - Target documents:
    - `docs/30-features/header/README.md`
    - `docs/30-features/header/custom-header-architecture.md`
    - `docs/30-features/header/header-admin-settings-map.md`
    - `docs/30-features/header/header-module-spec.md`
    - `docs/30-features/header/header-responsive-contract.md`
- `docs/tasks/`
  - Impacted? Yes
  - Target documents:
    - `docs/tasks/BW-TASK-20260325-02-start.md`
    - `docs/tasks/BW-TASK-20260325-02-closure.md`
