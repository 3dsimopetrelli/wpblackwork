# BW Docs Architecture Snapshot

Date: 2026-03-05

## Full Tree

```text
docs
docs/00-governance
docs/00-governance/admin-runtime-propagation-map.md
docs/00-governance/authority-matrix.md
docs/00-governance/blackwork-new-chat-operating-protocol.md
docs/00-governance/blast-radius-consolidation-map.md
docs/00-governance/callback-contracts.md
docs/00-governance/cross-domain-state-dictionary.md
docs/00-governance/docs-code-alignment-status.md
docs/00-governance/risk-notes-theme-builder-lite.md
docs/00-governance/risk-register.md
docs/00-governance/system-normative-charter.md
docs/00-governance/system-state-matrix.md
docs/00-governance/task-close-template.md
docs/00-governance/task-start-template.md
docs/00-governance/technical-hardening-plan.md
docs/00-governance/working-protocol.md
docs/00-overview
docs/00-overview/README.md
docs/00-overview/business-model.md
docs/00-overview/cultural-manifesto.md
docs/00-overview/domain-map.md
docs/00-overview/project-identity.md
docs/00-overview/system-mental-map.md
docs/00-planning
docs/00-planning/core-evolution-plan.md
docs/00-planning/decision-log.md
docs/10-architecture
docs/10-architecture/README.md
docs/10-architecture/blackwork-technical-documentation.md
docs/10-architecture/elementor-widget-architecture-context.md
docs/10-architecture/theme-builder-lite
docs/10-architecture/theme-builder-lite/runtime-hook-map.md
docs/20-development
docs/20-development/README.md
docs/20-development/admin-panel-map.md
docs/20-development/admin-ui-guidelines.md
docs/20-development/linting-and-phpcs-baseline.md
docs/20-development/woocommerce-template-overrides.md
docs/30-features
docs/30-features/README.md
docs/30-features/cart-popup
docs/30-features/cart-popup/README.md
docs/30-features/cart-popup/cart-popup-module-spec.md
docs/30-features/cart-popup/cart-popup-technical-guide.md
docs/30-features/checkout
docs/30-features/checkout/README.md
docs/30-features/checkout/checkout-architecture-map.md
docs/30-features/checkout/complete-guide.md
docs/30-features/checkout/maintenance-guide.md
docs/30-features/checkout/order-confirmation-style-guide.md
docs/30-features/checkout/woocommerce-customizations-folder.md
docs/30-features/header
docs/30-features/header/README.md
docs/30-features/header/custom-header-architecture.md
docs/30-features/header/header-admin-settings-map.md
docs/30-features/header/header-module-spec.md
docs/30-features/header/header-responsive-contract.md
docs/30-features/header/header-scroll-state-machine-spec.md
docs/30-features/import-products
docs/30-features/import-products/import-engine-v2-architecture.md
docs/30-features/import-products/import-engine-v2-storage-executor.md
docs/30-features/import-products/import-products-vnext-spec.md
docs/30-features/media-folders
docs/30-features/media-folders/media-folders-module-spec.md
docs/30-features/my-account
docs/30-features/my-account/README.md
docs/30-features/my-account/my-account-architecture-map.md
docs/30-features/my-account/my-account-complete-guide.md
docs/30-features/navigation
docs/30-features/navigation/README.md
docs/30-features/navigation/custom-navigation.md
docs/30-features/product-slide
docs/30-features/product-slide/README.md
docs/30-features/product-slide/fixes
docs/30-features/product-slide/fixes/2025-12-10-fix-one-big-slide-responsive.md
docs/30-features/product-slide/fixes/2025-12-10-fix-resize-breakpoint-regression.md
docs/30-features/product-slide/fixes/2025-12-10-fix-responsive-arrows-slidecount.md
docs/30-features/product-slide/fixes/2025-12-10-fix-variable-width-responsive.md
docs/30-features/product-slide/fixes/2025-12-10-product-slide-popup-not-opening.md
docs/30-features/product-slide/fixes/2025-12-13-fix-drag-swipe-animation.md
docs/30-features/product-slide/fixes/2025-12-13-product-slide-vacuum-cleanup-report.md
docs/30-features/product-slide/fixes/2025-12-14-bwproductslide-cleanup-opportunities.md
docs/30-features/product-slide/fixes/README.md
docs/30-features/product-types
docs/30-features/product-types/README.md
docs/30-features/product-types/product-type-review.md
docs/30-features/product-types/product-types-implementation.md
docs/30-features/redirect
docs/30-features/redirect/redirect-engine-v2-spec.md
docs/30-features/search
docs/30-features/search/search-module-spec.md
docs/30-features/search/search-vnext-dataflow.md
docs/30-features/search/search-vnext-spec.md
docs/30-features/smart-header
docs/30-features/smart-header/README.md
docs/30-features/smart-header/smart-header-guide.md
docs/30-features/system-status
docs/30-features/system-status/README.md
docs/30-features/theme-builder-lite
docs/30-features/theme-builder-lite/theme-builder-lite-spec.md
docs/40-integrations
docs/40-integrations/README.md
docs/40-integrations/auth
docs/40-integrations/auth/README.md
docs/40-integrations/auth/auth-architecture-map.md
docs/40-integrations/auth/social-login-setup-guide.md
docs/40-integrations/brevo
docs/40-integrations/brevo/README.md
docs/40-integrations/brevo/brevo-architecture-map.md
docs/40-integrations/brevo/brevo-mail-marketing-architecture.md
docs/40-integrations/brevo/brevo-mail-marketing-roadmap.md
docs/40-integrations/brevo/mail-marketing-qa-checklist.md
docs/40-integrations/brevo/subscribe.md
docs/40-integrations/cart-checkout-responsibility-matrix.md
docs/40-integrations/payments
docs/40-integrations/payments/README.md
docs/40-integrations/payments/gateway-apple-pay-guide.md
docs/40-integrations/payments/gateway-google-pay-guide.md
docs/40-integrations/payments/gateway-klarna-guide.md
docs/40-integrations/payments/payment-selector-map.md
docs/40-integrations/payments/payment-test-checklist.md
docs/40-integrations/payments/payments-architecture-map.md
docs/40-integrations/payments/payments-overview.md
docs/40-integrations/supabase
docs/40-integrations/supabase/README.md
docs/40-integrations/supabase/audits
docs/40-integrations/supabase/audits/2026-02-13-supabase-create-password-audit.md
docs/40-integrations/supabase/audits/README.md
docs/40-integrations/supabase/supabase-architecture-map.md
docs/40-integrations/supabase/supabase-create-password.md
docs/50-ops
docs/50-ops/README.md
docs/50-ops/admin-panel-reality-audit.md
docs/50-ops/audits
docs/50-ops/audits/cart-checkout-js-structure-analysis.md
docs/50-ops/audits/checkout-payment-selector-audit.md
docs/50-ops/audits/header-system-technical-audit.md
docs/50-ops/audits/my-account-domain-audit.md
docs/50-ops/audits/redirect-engine-technical-audit.md
docs/50-ops/audits/repo-root-hygiene-audit.md
docs/50-ops/audits/search-system-technical-audit.md
docs/50-ops/audits/theme-builder-lite-phase1-implementation.md
docs/50-ops/blackwork-development-protocol.md
docs/50-ops/checkout-reality-audit.md
docs/50-ops/incident-classification.md
docs/50-ops/maintenance-decision-matrix.md
docs/50-ops/maintenance-framework.md
docs/50-ops/maintenance-workflow.md
docs/50-ops/regression-coverage-map.md
docs/50-ops/regression-protocol.md
docs/50-ops/runbooks
docs/50-ops/runbooks/README.md
docs/50-ops/runbooks/auth-runbook.md
docs/50-ops/runbooks/brevo-runbook.md
docs/50-ops/runbooks/checkout-runbook.md
docs/50-ops/runbooks/header-runbook.md
docs/50-ops/runbooks/payments-runbook.md
docs/50-ops/runbooks/supabase-runbook.md
docs/50-ops/runtime-hook-map.md
docs/60-adr
docs/60-adr/ADR-001-upe-vs-custom-selector.md
docs/60-adr/ADR-002-authority-hierarchy.md
docs/60-adr/ADR-003-callback-anti-flash-model.md
docs/60-adr/ADR-004-consent-gate-doctrine.md
docs/60-adr/ADR-005-claim-idempotency-rule.md
docs/60-adr/ADR-006-provider-switch-model.md
docs/60-adr/README.md
docs/60-system
docs/60-system/integration
docs/60-system/integration/supabase-payments-checkout-integration-map.md
docs/99-archive
docs/99-archive/README.md
docs/99-archive/_docs_codex
docs/99-archive/_docs_codex/README.md
docs/99-archive/_docs_codex/readme-duplicate.md
docs/99-archive/_docs_codex/root-readme-legacy.md
docs/99-archive/architecture
docs/99-archive/architecture/README.md
docs/99-archive/architecture/architecture-summary.md
docs/99-archive/architecture/rm-retest-reference-manual.md
docs/99-archive/brevo
docs/99-archive/brevo/README.md
docs/99-archive/header
docs/99-archive/header/README.md
docs/99-archive/header/custom-header-architecture-from-elementor.md
docs/99-archive/my-account
docs/99-archive/my-account/README.md
docs/99-archive/my-account/my-account-improvements-report.md
docs/99-archive/my-account/my-account-session-summary.md
docs/99-archive/payments
docs/99-archive/payments/README.md
docs/99-archive/payments/gateway-total-guide.md
docs/99-archive/reports
docs/99-archive/reports/README.md
docs/99-archive/reports/view-product-fix-report.md
docs/99-archive/root
docs/99-archive/root/checkout-css-fix.patch
docs/99-archive/root/elementor-smart-header.html
docs/99-archive/root/smart-header.html
docs/99-archive/smart-header
docs/99-archive/smart-header/README.md
docs/99-archive/smart-header/smart-header-auto-dark-detection.md
docs/99-archive/smart-header/smart-header-dark-zones-guide.md
docs/README.md
docs/_templates
docs/_templates/README.md
docs/_templates/maintenance-task-template.md
docs/_templates/task-closure-template.md
docs/_templates/task-start-template.md
docs/tasks
docs/tasks/BW-DOCS-ARCHITECTURE-SNAPSHOT.md
docs/tasks/BW-TASK-20260305-01-closure.md
docs/tasks/BW-TASK-20260305-01-implementation-report.md
docs/tasks/BW-TASK-20260305-02-closure.md
docs/tasks/BW-TASK-20260305-08-admin-panel-architecture-audit.md
docs/tasks/BW-TASK-20260305-shopify-admin-ui-rollout-closure.md
docs/tasks/media-folders-close-task.md
```

## Folder Purposes

### Top-Level
- `docs/00-overview`: orientation and identity layer for the project (business/context/system map).
- `docs/00-governance`: normative governance controls (authority, risk, protocols, templates, invariants).
- `docs/00-planning`: roadmap and decision tracking (execution planning, non-ADR planning decisions).
- `docs/10-architecture`: core architecture references and system-level technical maps.
- `docs/20-development`: developer implementation contracts, admin panel mapping, coding/linting workflow rules.
- `docs/30-features`: per-feature specs and deep guides grouped by domain.
- `docs/40-integrations`: external integration architecture (payments, auth, Brevo, Supabase).
- `docs/50-ops`: operational governance (maintenance system, audits, runbooks, regression controls).
- `docs/60-adr`: Architecture Decision Records (normative architecture decisions).
- `docs/60-system`: system integration-level maps not yet fully indexed.
- `docs/99-archive`: historical/superseded documents kept for traceability.
- `docs/_templates`: reusable templates for task start/maintenance/closure workflows.
- `docs/tasks`: task-level delivery records, implementation reports, and closure artifacts.

### Major Subfolders
- `docs/30-features/*`: feature-domain documentation modules (checkout, header, system-status, etc.).
- `docs/40-integrations/auth`: auth integration contracts and setup.
- `docs/40-integrations/brevo`: Brevo mail-marketing architecture + QA + roadmap artifacts.
- `docs/40-integrations/payments`: gateway architecture, selector map, testing guides.
- `docs/40-integrations/supabase`: Supabase architecture and flows (+ dedicated audits).
- `docs/50-ops/audits`: technical reality audits by domain.
- `docs/50-ops/runbooks`: domain runbooks for operations and incidents.
- `docs/99-archive/*`: archived by topic (architecture, payments, header, smart-header, reports, etc.).

## Key Governance Files Explained

### docs/00-governance
- `admin-runtime-propagation-map.md`: maps admin setting -> runtime behavior chain. Matters to prevent config drift and false toggles.
- `authority-matrix.md`: declares source-of-truth ownership boundaries. Matters to avoid authority drift and invalid cross-domain writes.
- `blackwork-new-chat-operating-protocol.md`: chat/session initiation protocol. Matters for deterministic task intake and governance compliance.
- `blast-radius-consolidation-map.md`: high-risk surface map by tier. Matters for scoping regression and change risk before coding.
- `callback-contracts.md`: callback/webhook invariants and authority limits. Matters for idempotency and safe failure handling.
- `cross-domain-state-dictionary.md`: canonical state ownership dictionary. Matters for read/write boundary correctness.
- `docs-code-alignment-status.md`: docs-vs-code coverage health. Matters to prioritize documentation alignment gaps.
- `risk-notes-theme-builder-lite.md`: focused risk notes for Theme Builder Lite. Matters for module-specific risk control.
- `risk-register.md`: authoritative risk registry. Matters as governance source for active systemic risks and mitigation status.
- `system-normative-charter.md`: top-level invariants and authority doctrine. Matters as binding contract for all domains.
- `system-state-matrix.md`: allowed/expected cross-domain states. Matters for convergence and regression validation.
- `task-start-template.md`: formal task classification template. Matters to enforce scope, tier, authority and risk declaration upfront.
- `task-close-template.md`: formal closure verification template. Matters to guarantee post-change governance checks and evidence.
- `technical-hardening-plan.md`: phased hardening plan for Tier-0 surfaces. Matters for controlled refactors on critical paths.
- `working-protocol.md`: binding execution protocol for high-impact domains. Matters as operational governance backbone.

### docs/00-planning
- `core-evolution-plan.md`: prioritized roadmap with acceptance gates. Matters for portfolio-level sequencing and risk-aware planning.
- `decision-log.md`: planning decision history (non-ADR). Matters for traceability of roadmap-level choices over time.

### docs/20-development
- `admin-panel-map.md`: canonical map of Blackwork admin panel architecture. Matters for safe admin changes and regression targeting.
- `admin-ui-guidelines.md`: shared admin UI kit contract and scoping rules. Matters to prevent CSS bleed and UX inconsistency.
- `linting-and-phpcs-baseline.md`: lint/baseline strategy and cleanup policy. Matters for enforceable code-quality governance.
- `woocommerce-template-overrides.md`: Woo template override usage model. Matters for integration-safe template customization.

## Potential Duplication Risks

1. **Header docs overlap**: `docs/30-features/header/*` vs `docs/99-archive/header/*` plus `docs/30-features/smart-header/*` and archive smart-header docs.
2. **Protocol duplication risk**: `docs/00-governance/working-protocol.md`, `docs/00-governance/blackwork-new-chat-operating-protocol.md`, and `docs/50-ops/blackwork-development-protocol.md` partially overlap process guidance.
3. **Task template duplication**: `docs/00-governance/task-*.md` and `docs/_templates/task-*.md` cover similar start/close flows.
4. **Checkout architecture overlap**: `docs/30-features/checkout/checkout-architecture-map.md` and `docs/40-integrations/cart-checkout-responsibility-matrix.md` and some `docs/50-ops/*checkout*` audits can drift.
5. **Supabase cross-location overlap**: `docs/40-integrations/supabase/*` and `docs/60-system/integration/*` can duplicate integration maps.
6. **Admin architecture overlap**: `docs/20-development/admin-panel-map.md` and `docs/50-ops/admin-panel-reality-audit.md` (map vs audit) may diverge if update cadence differs.

## Missing Index/README Recommendations

Recommended minimal additions (do not create yet):
- `docs/00-governance/README.md` — index of normative governance artifacts and precedence order.
- `docs/00-planning/README.md` — planning layer scope + relation to ADR/governance docs.
- `docs/tasks/README.md` — task record conventions and naming standard.
- `docs/50-ops/audits/README.md` — audit catalog and expected report format.
- `docs/60-system/README.md` — purpose of this namespace and relationship to integrations/architecture.
- `docs/60-system/integration/README.md` — integration-map index and ownership notes.
- `docs/30-features/import-products/README.md` — feature index linking spec + architecture + executor docs.
- `docs/30-features/media-folders/README.md` — index wrapper around module spec and operational references.
- `docs/30-features/redirect/README.md` — redirect feature index and lifecycle references.
- `docs/30-features/search/README.md` — search spec/index entrypoint for module/vnext docs.
- `docs/30-features/theme-builder-lite/README.md` — feature index for architecture/spec/ops links.
- `docs/10-architecture/theme-builder-lite/README.md` — local index for runtime hook map context.
- `docs/99-archive/root/README.md` — rationale for raw legacy root artifacts (html/patch files).

## Notes

- Snapshot generated from current repository state on 2026-03-05.
- Purpose lines were derived from existing README files where present, otherwise inferred from filenames and folder composition.
