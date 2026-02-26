# Maintenance Framework

## Scope
In Blackwork, maintenance is the discipline of keeping the system stable, secure, and coherent while preserving architectural intent.

## Maintenance Categories

### Bug Fix
Correction of behavior that deviates from expected domain documentation.

### Refactor
Internal improvement of structure/readability/performance without changing documented behavior.

### Security Patch
Targeted change that reduces or removes a security risk (validation, authorization, sanitization, exposure).

### UX Regression
Fix for degraded user interaction introduced by recent changes (flows, feedback, consistency, accessibility).

### Integration Break
Recovery of failing contracts between Blackwork and external services (payments, auth, Brevo, Supabase).

## Operating Principles
- Maintenance must preserve architecture and domain contracts.
- No feature modification is allowed without explicit domain alignment.
- Any maintenance outcome must be reflected in documentation updates.
- Historical continuity matters: operational knowledge must remain traceable.
