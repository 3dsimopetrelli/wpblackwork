# Unified Callback Contracts

## 1) Purpose
Define callback authority, invariants, and failure-containment rules across payment and auth domains.

Normative reference:
- [System Normative Charter](./system-normative-charter.md)
- [Cross-Domain State Dictionary](./cross-domain-state-dictionary.md)

---

## 2) Payment Webhook Contract

### Authority
Payment provider confirmation is final authority for payment outcome.

### Invariants
- Webhook must be idempotent.
- Webhook must converge order state exactly once.
- Repeated events must not duplicate side effects.
- Webhook must not modify identity or marketing state.

### Failure Containment
- Invalid events must not mutate order state.
- Logging required.
- Commerce must not regress after confirmation.

---

## 3) Supabase Auth Callback Contract

### Authority
Supabase + bridge establish identity/session continuity.
Not commerce authority.

### Invariants
- Callback must not override payment/order lifecycle.
- Must resolve session before rendering account UI.
- Anti-flash invariant (no intermediate My Account render before state resolution).
- Idempotent bridge behavior (no duplicate onboarding/claims).

### Failure Containment
- Callback failure must degrade to retry/auth state.
- Must not loop infinitely.
- Must not corrupt onboarding marker.

---

## 4) Order-Received Transition Contract

### Authority
Order state is authoritative.
Auth/provisioning may gate UI, not payment truth.

### Invariants
- Payment success must render stable order state.
- Provisioning failures must not revert payment state.
- Guest→Account claim must be idempotent.

---

## 5) Cross-Domain Callback Prohibitions

- No callback may override payment authority.
- No callback may rewrite Woo order lifecycle.
- No callback may persist UI-transient state.
- No callback may escalate privilege beyond WP session authority.

---

## 6) Callback Convergence Model

Every callback path must converge to a stable, repeat-safe state.
Repeated invocation must produce same terminal state.
No infinite redirect or re-execution loops allowed.
