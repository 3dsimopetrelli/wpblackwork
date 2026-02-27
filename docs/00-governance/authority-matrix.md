# Blackwork Core — Authority Matrix

## 1. Purpose
This document prevents authority drift and cross-domain override violations.

This matrix MUST be used to verify:
- ownership of truth surfaces,
- allowed mutation boundaries,
- prohibited override paths,
- ADR escalation conditions for authority-impacting changes.

This document is governance-normative and MUST be interpreted together with the Domain Map and Runtime Hook Map.

## 2. Authority Doctrine Summary
Authority levels:
- **Defines Truth**: Domain owns canonical truth for a declared surface.
- **Mutates State**: Domain may write state within its declared boundary but does not own all truth surfaces it touches.
- **Signals Only**: Domain may emit coordination/lifecycle signals and MUST NOT own truth.
- **Presentation Only**: Domain may render UX/view state and MUST NOT create or redefine truth.

Tier meaning:
- **Tier 0**: Authority-critical surfaces. Changes affecting authority behavior REQUIRE ADR.
- **Tier 1**: Orchestration and integration surfaces. Changes REQUIRE regression validation.
- **Tier 2**: Presentation/utilities surfaces. Changes are UX/coordination scoped and MUST remain non-authoritative.

Reference doctrine:
- `docs/60-adr/ADR-002-authority-hierarchy.md`

## 3. Authority Matrix Table
| Domain | Tier | Defines Truth | May Mutate | May Read | MUST NOT Override | ADR Required? |
|---|---:|---|---|---|---|---|
| Redirect / Routing Authority | 0 | Yes | Yes | Yes | MUST NOT override protected commerce routes, admin/platform-critical routes, or payment/auth/consent authority surfaces. | Yes |
| Import Products Authority | 0 | Yes | Yes | Yes | MUST NOT override runtime cart/checkout/payment truth; MUST NOT bypass canonical product identity (SKU). | Yes |
| Payment Confirmation Authority | 0 | Yes | Yes | Yes | MUST NOT override routing authority, auth authority, or consent authority; MUST NOT accept UI signals as payment truth. | Yes |
| Payment Provider Integration | 1 | No | Yes | Yes | MUST NOT override Payment Confirmation Authority, routing authority, or auth authority boundaries. | No |
| Checkout Orchestration | 1 | No | Yes | Yes | MUST NOT override payment confirmation truth, routing authority, or consent authority. | No |
| Cart Interaction | 1 | No | Yes | Yes | MUST NOT override payment/order truth, redirect authority, or checkout authority boundaries. | No |
| Consent / Brevo Sync | 1 | No | Yes | Yes | MUST NOT override payment truth, order lifecycle authority, auth authority, or provisioning authority. | No |
| Supabase/Auth Sync | 1 | No | Yes | Yes | MUST NOT override payment confirmation truth, routing authority, or consent authority boundaries. | No |
| Header / Global Layout | 2 | No | No | Yes | MUST NOT override routing, payment, order, auth, provisioning, consent, or cart/checkout business truth. | No |
| Shared Runtime Utilities | 2 | No | No | Yes | MUST NOT override any authority domain; MUST NOT transform signaling into truth ownership. | No |

## 4. Override Prohibition Rules
- No cross-tier override is permitted.
- Presentation layers MUST NOT create or redefine authority truth.
- Silent ownership shifts are prohibited.
- Canonical SKU identity cannot be replaced without ADR.
- Protected commerce routes cannot be overridden.

Mandatory control rules:
- Domains MAY read upstream state when required by contract.
- Domains MUST NOT mutate outside declared ownership boundaries.
- Signaling and presentation domains MUST remain non-authoritative.

## 5. Escalation Rule
If a change modifies any of the following, ADR is mandatory:
- Authority ownership
- Tier classification
- Runtime hook priority for Tier 0 surfaces

Escalation requirements:
- Governance review MUST occur before merge.
- Domain Map and Runtime Hook Map references MUST be included in change rationale.
- Authority-impacting changes MUST be recorded through ADR before implementation finalization.

Cross-references:
- `docs/00-overview/domain-map.md`
- `docs/50-ops/runtime-hook-map.md`
- `docs/30-features/redirect/redirect-engine-v2-spec.md`
- `docs/30-features/import-products/import-products-vnext-spec.md`
- `docs/60-adr/ADR-002-authority-hierarchy.md`
