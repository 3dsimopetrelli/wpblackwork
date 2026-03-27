# Blackwork Governance -- Task Start

## 1) Context
- Task ID: `BW-TASK-20260327-04`
- Task title: Global layout width lock system in Site Settings
- Request source: User request on 2026-03-27
- Expected outcome:
  - inspect the repository surfaces behind `Blackwork Site -> Site Settings`
  - design and implement a professional global content-width / layout-shell system
  - add a new admin section or tab in `Site Settings` for repository-consistent width controls
  - make the system work across Elementor content, custom header, custom footer, and general site content
  - preserve intentionally full-width / full-bleed sections when allowed
- Constraints:
  - this is not a breakpoint-only task
  - implementation must be a real global max-width / inner-wrapper system, not a broad CSS hack
  - avoid destructive CSS against every Elementor container globally
  - maintain repository consistency with existing admin UI, save patterns, and frontend runtime architecture

## 2) Task Classification
- Domain: Blackwork Site / Site Settings / Frontend Global Layout System
- Incident/Task type: Governed analysis + implementation
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `admin/class-blackwork-site-settings.php`
  - `admin/css/blackwork-site-settings.css`
  - frontend global CSS/runtime injection surfaces to be confirmed during implementation
  - custom header render/runtime surfaces
  - custom footer / Theme Builder Lite runtime surfaces
  - site-content wrapper behavior on frontend
- Integration impact: Medium
- Regression scope required:
  - widescreen frontend layout behavior
  - Elementor page content width behavior
  - custom header width behavior
  - custom footer width behavior
  - full-bleed exception behavior
  - responsive horizontal padding on desktop / tablet / mobile

## 3) Pre-Task Reading Checklist
- Governance docs to read:
  - `docs/00-governance/ai-task-protocol.md`
  - `docs/templates/task-start-template.md`
  - `docs/templates/task-closure-template.md`
- Admin architecture docs to read:
  - `docs/20-development/admin-panel-map.md`
  - `docs/20-development/admin-ui-guidelines.md`
- Feature / runtime docs to read:
  - `docs/30-features/theme-builder-lite/theme-builder-lite-spec.md`
  - `docs/10-architecture/theme-builder-lite/runtime-hook-map.md`
  - any header/frontend runtime docs discovered during analysis
- Code references to read:
  - `admin/class-blackwork-site-settings.php`
  - header runtime/render files under `includes/modules/header/`
  - Theme Builder Lite admin/runtime files under `includes/modules/theme-builder-lite/`
  - frontend CSS enqueue / inline-style surfaces discovered during analysis

## 4) Scope Declaration
- Proposed strategy:
  - add a dedicated `Layout` or `Global Width` authority surface under `Blackwork Site -> Site Settings`
  - store a centralized option set for:
    - enable/disable global width system
    - max content width
    - desktop/tablet/mobile horizontal padding
    - apply to main content
    - apply to header
    - apply to footer
    - allow full-bleed sections
  - generate frontend CSS variables such as:
    - `--bw-site-max-width`
    - `--bw-site-padding-x`
    - `--bw-site-padding-x-tablet`
    - `--bw-site-padding-x-mobile`
  - implement a shell / inner-wrapper strategy rather than container-wide Elementor overrides
  - use explicit opt-in / opt-out wrapper logic for header/footer/content where needed
- Files likely impacted in the implementation phase:
  - `admin/class-blackwork-site-settings.php`
  - `admin/css/blackwork-site-settings.css`
  - one or more frontend CSS files for the global shell system
  - one or more frontend runtime/render files for header integration
  - one or more frontend runtime/render files for footer integration
  - documentation files in `docs/10-architecture/`, `docs/20-development/`, `docs/30-features/`, and task closure docs
- Explicitly out-of-scope unless deeper analysis proves otherwise:
  - global redesign of Elementor container markup
  - unrelated breakpoint framework changes
  - destructive rewrites of header/footer structure
  - per-widget layout overrides unrelated to global shell behavior

## 5) Runtime Surface Declaration
- New hooks expected:
  - possible frontend enqueue or inline-style output hook for global width variables
  - possible body/class or wrapper filter integration depending on the existing runtime
- Hook priority modifications:
  - none expected initially, but runtime order must be checked if header/footer wrappers depend on existing output timing
- Filters expected:
  - possibly wrapper/body-class/template selection helpers if required by the existing header/footer architecture
- AJAX endpoints expected:
  - none
- Admin routes expected:
  - no new admin page; extension of `blackwork-site-settings`

## 6) Risk Analysis
- Main architectural risk:
  - forcing a max-width at the wrong DOM layer could break intentionally full-width Elementor sections
- Header/footer risk:
  - width-locking the outer shell instead of the inner shell could unintentionally shrink backgrounds, sticky behavior, or visual edge alignment
- Runtime consistency risk:
  - different render paths may exist for regular content, custom header, and Theme Builder Lite footer
- UX risk:
  - an unclear tab/section name or weak help text could make the feature feel like a breakpoint control instead of a true layout system
- Compatibility risk:
  - existing templates may rely on browser-full-width sections and must retain an escape hatch for full-bleed behavior

## 7) System Invariants
- Declared invariants that must remain true:
  - viewport remains full width; only inner content should stop at the configured max width
  - intentionally full-width / full-bleed sections must remain possible
  - admin save model must remain deterministic and repository-consistent
  - existing custom header and footer authority ownership must not be displaced by this feature
- Any invariant at risk?:
  - Yes; full-bleed preservation and header/footer wrapper placement require careful runtime selection
- Mitigation plan:
  - inspect actual render layers before coding
  - prefer CSS variables + explicit shell classes over wide global selectors
  - keep the system toggleable and scoped by dedicated wrapper contracts

## 8) Determinism Statement
- Input/output determinism declared?: Yes
- Ordering determinism declared?: Yes
- Retry determinism declared?: Yes
- Pagination/state convergence determinism declared?: Yes
- Determinism risks and controls:
  - settings sanitization must normalize numeric values and booleans
  - frontend CSS output must derive from one canonical option payload
  - wrapper/class emission must be stable across repeated page loads and independent of editor/runtime mode where applicable

## 9) Testing Strategy
- Local testing plan:
  - verify admin save/load roundtrip for the new layout fields
  - verify frontend on widescreen, desktop, tablet, and mobile widths
  - verify header-only, footer-only, and main-content-only application toggles
  - verify full-bleed exceptions still render edge-to-edge when allowed
  - verify Elementor pages, WooCommerce shop/product-related pages, and general content pages
- Edge cases expected:
  - empty or invalid numeric values
  - pages without Blackwork header/footer overrides
  - Elementor templates with nested containers and intentional full-width hero bands
  - sticky header or transparent header states
- Failure scenarios considered:
  - content width applied to wrong outer container
  - full-bleed sections unintentionally constrained
  - duplicate padding from theme + shell layer
  - editor/frontend mismatch if Elementor preview uses a different DOM path

## 10) Documentation Update Plan
- `docs/00-governance/`
  - Impacted?: No, unless new risk posture is discovered
- `docs/00-planning/`
  - Impacted?: No, unless scope escalates into an ADR-level architectural change
- `docs/10-architecture/`
  - Impacted?: Yes
  - Target documents:
    - frontend/runtime wrapper behavior docs to be confirmed during implementation
- `docs/20-development/`
  - Impacted?: Yes
  - Target documents:
    - `docs/20-development/admin-panel-map.md`
    - `docs/20-development/admin-ui-guidelines.md` if new admin patterns are introduced
- `docs/30-features/`
  - Impacted?: Yes
  - Target documents:
    - Site Settings and/or Theme Builder/Header-related docs to be confirmed after implementation analysis
- `docs/40-integrations/`
  - Impacted?: No
- `docs/50-ops/`
  - Impacted?: Possible
  - Target documents:
    - admin/runtime audit docs only if the implementation changes documented panel/runtime reality
- `docs/60-adr/`
  - Impacted?: No, unless implementation reveals authority ownership changes
- `docs/60-system/`
  - Impacted?: Possible
  - Target documents:
    - global frontend shell/layout docs if such layer exists or is created

## 11) Current Readiness
- Status: OPEN
- Analysis status:
  - initial governance framing completed
  - implementation-ready architecture still requires deeper inspection of frontend render/wrapper surfaces
- Immediate recommendation:
  - continue with repository inspection focused on:
    - Site Settings save/render architecture
    - global frontend style output patterns
    - header/footer runtime wrappers
    - safest shell point for Elementor/general content without breaking full-bleed sections
