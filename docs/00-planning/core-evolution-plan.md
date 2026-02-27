# Blackwork Core – Evolution Plan

This document is the governance planning layer for roadmap execution.
It tracks priority, risk, and execution intent.
It MUST be used as planning reference only and MUST NOT replace ADRs.

## Tier 0 – Critical Surfaces (High Risk / High Impact)

### Import Products Bulk System (audit + spec)
- Status: Backlog
- Short description: Produce implementation-faithful audit and formal specification of the bulk import surface, including validation and failure containment.
- Risk classification: High
- Notes: Tier 0 due to broad data and catalog impact.

### Redirect Engine Validation
- Status: Backlog
- Short description: Validate redirect engine runtime rules, conflict handling, and deterministic route resolution under mixed auth/checkout paths.
- Risk classification: High
- Notes: Any drift can affect critical navigation and conversion paths.

### Mail Marketing Consent Validation
- Status: Backlog
- Short description: Validate consent gate integrity and ensure execution remains compliant with local consent authority doctrine.
- Risk classification: High
- Notes: Must remain aligned with Consent Gate Doctrine and non-blocking commerce principles.

### Header Performance Pass
- Status: Backlog
- Short description: Run targeted performance audit on scroll listeners, overlays, and responsive runtime behavior while preserving current governance contracts.
- Risk classification: High
- Notes: Tier 0 UX surface with global impact.

### Checkout/Cart Regression Monitoring
- Status: Backlog
- Short description: Formalize recurring monitoring of checkout-cart interaction surfaces and regression drift signals.
- Risk classification: High
- Notes: Must preserve directional flow and authority boundaries.

## Tier 1 – UX & Runtime Enhancements

### Woo Loading -> Skeleton System
- Status: Backlog
- Short description: Define controlled UX enhancement path from spinner-based loading to deterministic skeleton patterns.
- Risk classification: Medium
- Notes: Must avoid introducing authority-coupled UI assumptions.

### Coming Soon Hardening
- Status: Backlog
- Short description: Stabilize coming-soon behavior under routing, access-control, and admin toggle transitions.
- Risk classification: Medium
- Notes: Ensure safe degrade and no accidental commerce lockout.

### Admin Panel Simplification (Header)
- Status: Backlog
- Short description: Use Header Admin Settings Map to simplify panel complexity without changing runtime contracts.
- Risk classification: Medium
- Notes: Scope is UI simplification, not behavior redesign.

### Cart Popup UX refinements
- Status: Backlog
- Short description: Improve cart popup interaction quality while preserving progressive enhancement and non-authority constraints.
- Risk classification: Medium
- Notes: Must remain compatible with Cart vs Checkout responsibility rules.

## Tier 2 – Refactors & Cleanup

### Elementor Widgets Inventory & Cleanup
- Status: Backlog
- Short description: Build full widget inventory and identify cleanup candidates under controlled, non-breaking governance constraints.
- Risk classification: Low
- Notes: Documentation-first sequencing recommended.

### Plugin Rename Strategy (BW -> Blackwork Core)
- Status: Backlog
- Short description: Define migration plan for naming standardization across code, docs, and runtime identifiers.
- Risk classification: Medium
- Notes: Requires compatibility and rollout strategy to prevent identifier drift.

### Legacy Smart Header deprecation strategy
- Status: Backlog
- Short description: Plan governance-safe deprecation path for legacy smart header fallback while preserving current fallback guarantees.
- Risk classification: Medium
- Notes: Legacy path is frozen fallback; deprecation must be staged and validated.
