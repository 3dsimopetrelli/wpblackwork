# Blackwork Radar Analysis Prompt v2

## Purpose

This document defines the standard prompt used to run **Radar repository audits** for the Blackwork project.

The goal is to allow any AI agent (Codex, ChatGPT, or internal analysis agents) to perform a **consistent technical audit** of the repository.

Radar analysis focuses on:

* security surfaces
* checkout/payment correctness
* Supabase authentication flows
* WooCommerce integration safety
* AJAX endpoints
* external HTTP dependencies
* performance bottlenecks
* asset loading discipline
* infrastructure assumptions

Radar **does not implement fixes**.
It only performs **analysis, validation, and classification**.

All validated findings must be routed to governance documents:

* risk register
* core evolution plan
* decision log
* no action / false positive

---

# Scope of Analysis

Radar audits must inspect the following areas:

### Authentication & Identity

* Supabase integration
* token validation flows
* session persistence
* user synchronization
* login / onboarding paths
* invite and recovery flows

### Payments

* Stripe integrations
* webhook handlers
* wallet integrations (Google Pay / Apple Pay)
* Payment Element configuration
* idempotency protections
* event replay protection

### Checkout

* WooCommerce checkout templates
* cart mutation logic
* quantity synchronization
* coupon application
* AJAX endpoints
* payment method selection logic

### Frontend JavaScript

* checkout scripts
* account scripts
* header/search modules
* MutationObserver usage
* DOM manipulation safety

### AJAX Endpoints

* authentication requirements
* nonce verification
* rate limiting
* enumeration surfaces

### Performance

* external HTTP calls
* synchronous API dependencies
* asset loading discipline
* dynamic CSS generation
* inline script payloads
* filesystem calls per request

### Infrastructure Assumptions

* proxy header trust
* CDN dependencies
* external script loading
* CSP compatibility
* caching compatibility

---

# Security Pattern Detection

Radar must detect:

### Authentication Risks

* public endpoints leaking identity information
* email existence enumeration
* session token exposure
* token verification bypass

### CSRF Risks

* POST actions without nonce
* AJAX endpoints missing nonce validation

### XSS Risks

* innerHTML sinks
* unsafe HTML decoding patterns
* unsanitized filter output

### Enumeration Risks

* endpoints returning existence booleans
* endpoints returning user presence signals

---

# WooCommerce Checkout Risk Patterns

Detect:

* cart mutations from POST data
* checkout form manipulation
* payment method selection drift
* coupon brute-force surfaces
* checkout fragment refresh bugs
* cart quantity synchronization risks

---

# Stripe / Payment Risks

Detect:

* webhook replay vulnerabilities
* missing idempotency protection
* event ownership race conditions
* external wallet launch paths
* duplicated script loads
* unsupported Payment Element modifications

---

# Performance Risk Patterns

Detect:

### External API blocking

* synchronous HTTP requests during page load
* external APIs with long timeouts

### Asset Discipline

* assets loaded globally instead of conditionally
* duplicated scripts
* large numbers of asset requests
* missing build/minification pipeline

### Dynamic Rendering Overhead

* inline CSS generation
* repeated option lookups
* repeated filesystem operations

---

# Infrastructure Risk Patterns

Detect:

* rate limit bypass through proxy headers
* reliance on `HTTP_CF_CONNECTING_IP`
* missing resource hints for external domains
* CDN dependency risks
* inline scripts incompatible with CSP

---

# Classification Rules

Each finding must be classified as one of:

TRUE
PARTIALLY TRUE
FALSE POSITIVE
NEEDS CONTEXT

Each finding must include:

1. verdict
2. evidence (file + lines)
3. impact explanation
4. hidden mitigations
5. validated severity

---

# Governance Routing

Every validated finding must be routed to one destination:

### Risk Register

Used when:

* security boundary is affected
* external dependency can block critical functionality
* user data integrity is at risk
* infrastructure trust boundaries are weak

### Core Evolution Plan

Used when:

* performance improvements
* architectural cleanup
* maintainability refactors
* asset loading improvements

### Decision Log

Used when:

* tradeoffs are intentional
* compatibility decisions exist
* architecture authority boundaries are defined

### No Action

Used when:

* issue is false positive
* behavior is expected
* mitigation already sufficient

---

# Output Format

Radar analysis results must be structured as:

Section A — High validated findings
Section B — Medium validated findings
Section C — Low / backlog findings
Section D — False positives / needs context

Followed by:

* Governance destination map
* Top 5 highest-impact findings
* Quick wins
* Implementation task suggestions
* Escalation check

---

# Rules

Radar analysis must:

* never modify code
* never implement fixes
* rely only on repository evidence
* clearly separate assumptions from verified facts
* avoid speculation when runtime context is missing

Radar is an **analysis engine**, not a repair tool.

---

# Expected Outcome

Running a Radar audit should produce:

* a structured findings report
* governance routing
* inputs for updating:

  * risk-register.md
  * core-evolution-plan.md
  * decision-log.md
  * docs/tasks/

This ensures that technical discoveries become **tracked governance knowledge**, not transient observations.
