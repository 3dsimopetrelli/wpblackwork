# Blackwork Governance — Task Closure

## 1) Task Identification
- Task ID: `BW-TASK-20260318-01`
- Task title: Brevo DOI — pre-assign contact to Unconfirmed list before confirmation
- Domain: Brevo / Mail Marketing Integration
- Tier classification: Integration — admin settings + runtime widget channel
- Implementation commit(s): `41dc5c5`

### Commit Traceability

- Commit hash: `41dc5c5`
- Commit message: `Add unconfirmed list pre-assignment to DOI flow`
- Files impacted:
  - `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`
  - `includes/integrations/brevo/class-bw-mailmarketing-subscription-channel.php`

---

## 2) Implementation Summary

**Root cause identified:**
Brevo's `/contacts/doubleOptinConfirmation` endpoint adds the contact to `includeListIds` only **after** the confirmation link is clicked. Before that, the contact exists in Brevo but is not assigned to any list, making pre-confirmation subscribers invisible in the Brevo panel.

**What was implemented:**

1. Added `unconfirmed_list_id` setting to General Mail Marketing settings:
   - Added to `get_general_defaults()` with default `0`
   - Added save handler for `bw_mail_marketing_general_unconfirmed_list_id` POST field
   - Added admin UI row in the Brevo Connection section (after Main list) with dropdown (when API is connected) or numeric input fallback

2. Updated DOI submit flow in the subscription channel:
   - Before calling `send_double_opt_in()`, calls `upsert_contact()` with `[unconfirmed_list_id]` if the setting is configured and > 0
   - The upsert step is non-fatal: DOI email is sent regardless of upsert outcome
   - `includeListIds` for DOI remains `[$list_id]` (Main/Marketing list, assigned after confirmation)

- Modified files:
  - `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`
  - `includes/integrations/brevo/class-bw-mailmarketing-subscription-channel.php`

- Runtime surfaces touched:
  - Admin settings save handler (General tab)
  - Admin UI HTML (General tab — Brevo Connection section)
  - Widget AJAX handler `bw_mail_marketing_subscribe` (DOI branch only)

- Hooks modified or registered: none
- Database/data surfaces touched:
  - `bw_mail_marketing_general_settings` WordPress option — new key `unconfirmed_list_id`

### Runtime Surface Diff

- New hooks registered: none
- Hook priorities modified: none
- Filters added or removed: none
- AJAX endpoints added or modified: none (existing `bw_mail_marketing_subscribe` endpoint behavior extended — DOI branch only)
- Admin routes added or modified: none (existing General settings form extended)

---

## 3) Acceptance Criteria Verification

- Contact created in Brevo immediately on DOI submit — **PASS** (upsert_contact called before send_double_opt_in)
- Contact explicitly assigned to Unconfirmed list (#11) before confirmation — **PASS** (upsert with `[unconfirmed_list_id]` if configured)
- DOI confirmation email still sent correctly — **PASS** (send_double_opt_in called immediately after, not gated on upsert result)
- Contact added to Marketing list (#10) after confirmation — **PASS** (includeListIds: [list_id] unchanged)
- Unconfirmed list ID configurable via admin panel — **PASS** (dropdown in General > Brevo Connection)
- If Unconfirmed list not configured, behavior identical to previous — **PASS** (upsert skipped when unconfirmed_list_id = 0)
- No hardcoded list IDs in code — **PASS** (both list_id and unconfirmed_list_id read from $general_settings)

### Testing Evidence

- Local testing performed: Yes
- Environment used: development
- Edge cases tested:
  - `unconfirmed_list_id = 0` → upsert skipped, DOI proceeds normally
  - `unconfirmed_list_id > 0` → upsert called, DOI proceeds regardless of upsert result
  - Single opt-in path: not affected (new code is inside `if ( 'double_opt_in' === $mode )` branch only)
  - Checkout channel: not affected (change is in subscription channel only)

---

## 4) Regression Surface Verification

- Surface: Elementor subscription widget — DOI submit
  - Verification: DOI email sent, contact pre-assigned to Unconfirmed list when configured
  - Result: **PASS**

- Surface: Elementor subscription widget — Single opt-in submit
  - Verification: path unaffected by change (branch not entered)
  - Result: **PASS**

- Surface: Checkout subscription channel
  - Verification: channel uses own code path, not modified
  - Result: **PASS**

- Surface: General settings save
  - Verification: new field saves and reloads correctly; existing fields unchanged
  - Result: **PASS**

- Surface: Admin panel HTML — Brevo Connection section
  - Verification: new Unconfirmed list row renders after Main list row; dropdown populated from Brevo API; fallback numeric input shown when API unavailable
  - Result: **PASS**

---

## 5) Determinism Verification

- Input/output determinism verified? **Yes** — upsert_contact is idempotent; send_double_opt_in behavior unchanged
- Ordering determinism verified? **Yes** — upsert always precedes DOI call; DOI always fires regardless of upsert outcome
- Retry/re-entry convergence verified? **Yes** — repeated submits with same email produce the same Brevo state (idempotent upsert + Brevo's own DOI dedup)

---

## 6) Documentation Alignment Verification

- `docs/00-governance/`
  - Impacted? No
  - Documents updated: —
- `docs/00-planning/`
  - Impacted? Yes
  - Documents updated: `decision-log.md` — Entry 035
- `docs/10-architecture/`
  - Impacted? No
  - Documents updated: —
- `docs/20-development/`
  - Impacted? No
  - Documents updated: —
- `docs/30-features/`
  - Impacted? No
  - Documents updated: —
- `docs/40-integrations/`
  - Impacted? Yes
  - Documents updated: `brevo/brevo-mail-marketing-architecture.md` — Lists section, DOI behavior section, General settings fields
- `docs/50-ops/`
  - Impacted? No
  - Documents updated: —
- `docs/60-adr/`
  - Impacted? No
  - Documents updated: —
- `docs/60-system/`
  - Impacted? No
  - Documents updated: —

---

## 7) Governance Artifact Updates

- Roadmap updated? No — this is a bug fix / visibility gap, not a roadmap phase item
- Decision log updated? **Yes** — Entry 035
- Risk register updated? No — no new risk introduced
- Risk status dashboard updated? No
- Runtime hook map updated? No — no hook changes
- Feature documentation updated? **Yes** — `docs/40-integrations/brevo/brevo-mail-marketing-architecture.md`

---

## 8) Final Integrity Check

- No authority drift introduced: **Yes** — `unconfirmed_list_id` follows the same admin authority pattern as `list_id`
- No new truth surface created: **Yes** — stored under existing `bw_mail_marketing_general_settings` option key
- No invariant broken: **Yes** — DOI email always sent; upsert non-fatal; single opt-in path untouched
- No undocumented runtime hook change: **Yes** — no hooks added or modified

- Integrity verification status: **PASS**

### Rollback Safety

- Can the change be reverted via commit revert? **Yes** — single commit `41dc5c5`
- Database migration involved? No — new option key `unconfirmed_list_id` defaults to `0`; reverting leaves the key unused but harmless
- Manual rollback steps required? No

### Post-Closure Monitoring

- Monitoring required: Yes
- Surfaces to monitor:
  - Brevo panel: verify contacts appear in Unconfirmed list before confirmation
  - Brevo panel: verify contacts appear in Marketing list after confirmation
  - Widget submit: verify DOI email is still delivered correctly
- Monitoring duration: first release cycle after deployment

---

## Release Gate Preparation

- Release Gate required? Yes
- Runtime surfaces to verify:
  - General settings page loads and saves `unconfirmed_list_id` correctly
  - Dropdown shows Brevo lists when API key is valid
  - Widget DOI submit: contact visible in Unconfirmed list in Brevo before confirmation
  - Widget DOI submit: DOI email received by subscriber
  - After confirmation: contact in Marketing list
- Operational smoke tests required:
  - Submit widget form with DOI mode configured and Unconfirmed list set
  - Check Brevo panel immediately — contact must appear in Unconfirmed list
  - Click confirmation link — contact must appear in Marketing list
- Rollback readiness confirmed? **Yes**

---

## 9) Closure Declaration

- Task closure status: **CLOSED**
- Responsible reviewer: Codex
- Date: 2026-03-18
