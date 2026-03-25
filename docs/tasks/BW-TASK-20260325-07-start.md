# Blackwork Governance -- Task Start

## 1) Context
- Task ID: `BW-TASK-20260325-07`
- Task title: Header menu color reveal glitch hardening
- Request source: User request on 2026-03-25
- Expected outcome:
  - investigate the visible white-to-black or black-to-white color correction when the Header reappears
  - remove the perceived delay so the Header reveals with the correct color already resolved
  - keep the existing dark-zone detector as the single authority
  - document the intervention while leaving final closure pending post-validation
- Constraints:
  - no second color detection system
  - no behavioral regression in smart scroll hide/show
  - no authority change to Woo/search/navigation/cart logic

## 2) Task Classification
- Domain: Header / Smart Scroll Runtime / Visual Convergence
- Incident/Task type: Runtime hardening
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 0
- Affected systems:
  - `includes/modules/header/assets/js/header-init.js`
  - `includes/modules/header/assets/css/header-layout.css`
- Integration impact: Medium
- Regression scope required:
  - header reveal on scroll up
  - dark-zone enter/leave around reveal
  - desktop/mobile color convergence

## 3) Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/header/README.md`
  - `docs/30-features/header/header-scroll-state-machine-spec.md`
  - `docs/30-features/header/header-responsive-contract.md`
- Code references to read:
  - `includes/modules/header/assets/js/header-init.js`
  - `includes/modules/header/assets/css/header-layout.css`
  - `includes/modules/header/assets/css/bw-navigation.css`
  - `includes/modules/header/assets/css/bw-navshop.css`

## 4) Scope Declaration
- Proposed strategy:
  - detect the future visible header color state before reveal instead of after reveal
  - disable color/filter transitions only during the reveal sync micro-phase
  - keep the existing dark-zone overlap detector as the only state authority
- Explicitly out of scope:
  - new dark-zone heuristics
  - admin settings changes
  - theme/layout restructuring

## 5) Current Status
- Status: OPEN
- Implementation: ROLLED BACK AFTER FAILED HARDENING ATTEMPTS
- Closure: deferred; no successful runtime fix retained in code
